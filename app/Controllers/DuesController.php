<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\DB;
use App\Services\ActivityLogger;

class DuesController
{
    private DB $db;
    private ActivityLogger $logger;

    public function __construct()
    {
        $this->db     = DB::getInstance();
        $this->logger = new ActivityLogger();
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  GET  /Business/{bookSlug}/Dues
    // ─────────────────────────────────────────────────────────────────────────
    public function index(array $params): void
    {
        $book = $this->requireBook($params['bookSlug'] ?? '');

        $filter  = $_GET['filter']  ?? 'all';   // all | unpaid | partial | paid
        $search  = trim($_GET['q']  ?? '');
        $page    = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 20;
        $offset  = ($page - 1) * $perPage;

        $where  = ['d.book_id = :book_id'];
        $binds  = [':book_id' => $book['id']];

        if ($filter !== 'all') {
            $where[]            = 'd.status = :status';
            $binds[':status']   = $filter;
        }
        if ($search !== '') {
            $where[]              = '(c.name LIKE :q OR c.phone LIKE :q OR d.title LIKE :q)';
            $binds[':q']          = "%{$search}%";
        }

        $whereSQL = implode(' AND ', $where);

        $total = (int)$this->db->fetch(
            "SELECT COUNT(*) AS n
               FROM dues d
               LEFT JOIN customers c ON c.id = d.customer_id
              WHERE {$whereSQL}",
            $binds
        )['n'];

        $dues = $this->db->fetchAll(
            "SELECT d.*,
                    c.name       AS customer_name,
                    c.phone      AS customer_phone,
                    c.photo      AS customer_photo,
                    cu.symbol    AS currency_symbol,
                    i.invoice_no AS invoice_no
               FROM dues d
               LEFT JOIN customers c  ON c.id  = d.customer_id
               LEFT JOIN currencies cu ON cu.id = d.currency_id
               LEFT JOIN invoices   i  ON i.id  = d.invoice_id
              WHERE {$whereSQL}
              ORDER BY d.status ASC, d.created_at DESC
              LIMIT {$perPage} OFFSET {$offset}",
            $binds
        );

        // Summary totals for this book
        $summary = $this->db->fetch(
            "SELECT
                SUM(CASE WHEN status IN ('unpaid','partial') THEN amount - paid_amount ELSE 0 END) AS outstanding,
                SUM(paid_amount) AS total_collected,
                COUNT(*)         AS total_count,
                SUM(CASE WHEN status = 'unpaid'  THEN 1 ELSE 0 END) AS unpaid_count,
                SUM(CASE WHEN status = 'partial' THEN 1 ELSE 0 END) AS partial_count,
                SUM(CASE WHEN status = 'paid'    THEN 1 ELSE 0 END) AS paid_count
             FROM dues WHERE book_id = :book_id",
            [':book_id' => $book['id']]
        );

        $totalPages = (int)ceil($total / $perPage);

        require views_path('business/dues/index.php');
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  GET  /Business/{bookSlug}/Dues/{id}
    // ─────────────────────────────────────────────────────────────────────────
    public function show(array $params): void
    {
        $book = $this->requireBook($params['bookSlug'] ?? '');
        $due  = $this->requireDue((int)($params['id'] ?? 0), $book['id']);

        $customer = $this->db->fetch(
            "SELECT * FROM customers WHERE id = :id",
            [':id' => $due['customer_id']]
        );

        $payments = $this->db->fetchAll(
            "SELECT dp.*, u.name AS paid_by_name
               FROM due_payments dp
               LEFT JOIN users u ON u.id = dp.paid_by
              WHERE dp.due_id = :due_id
              ORDER BY dp.paid_at DESC",
            [':due_id' => $due['id']]
        );

        $invoice = $due['invoice_id']
            ? $this->db->fetch("SELECT * FROM invoices WHERE id = :id", [':id' => $due['invoice_id']])
            : null;

        require views_path('business/dues/show.php');
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  POST  /Business/{bookSlug}/Dues/{id}/Pay
    // ─────────────────────────────────────────────────────────────────────────
    public function recordPayment(array $params): void
    {
        $book = $this->requireBook($params['bookSlug'] ?? '');
        $due  = $this->requireDue((int)($params['id'] ?? 0), $book['id']);

        if (!csrf_verify($_POST['csrf_token'] ?? '')) {
            redirect_back();
            return;
        }

        $amount = (float)($_POST['amount'] ?? 0);
        if ($amount <= 0) {
            set_flash('error', 'Payment amount must be greater than zero.');
            redirect_back();
            return;
        }

        $remaining = (float)$due['amount'] - (float)$due['paid_amount'];
        if ($amount > $remaining + 0.001) {
            set_flash('error', 'Payment exceeds the remaining balance.');
            redirect_back();
            return;
        }

        $method    = sanitize($_POST['payment_method'] ?? 'cash');
        $reference = sanitize($_POST['reference'] ?? '');
        $note      = sanitize($_POST['note'] ?? '');
        $userId    = Auth::id();

        $this->db->query(
            "INSERT INTO due_payments
                (due_id, book_id, amount, payment_method, reference, note, paid_by)
             VALUES (:due_id, :book_id, :amount, :method, :ref, :note, :paid_by)",
            [
                ':due_id'  => $due['id'],
                ':book_id' => $book['id'],
                ':amount'  => $amount,
                ':method'  => $method,
                ':ref'     => $reference,
                ':note'    => $note,
                ':paid_by' => $userId,
            ]
        );

        $newPaid = (float)$due['paid_amount'] + $amount;
        $newStatus = $newPaid >= ((float)$due['amount'] - 0.001) ? 'paid' : 'partial';

        $this->db->query(
            "UPDATE dues SET paid_amount = :paid, status = :status, updated_at = NOW()
              WHERE id = :id",
            [':paid' => $newPaid, ':status' => $newStatus, ':id' => $due['id']]
        );

        $this->logger->log(
            $book['id'],
            $userId,
            'due.payment',
            'Due',
            $due['id'],
            "Recorded payment of {$amount} for due #{$due['id']} (customer: {$due['customer_id']})"
        );

        set_flash('success', 'Payment recorded successfully.');
        redirect(base_url("Business/{$book['slug']}/Dues/{$due['id']}"));
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  POST  /Business/{bookSlug}/Dues (manual due – not from invoice)
    // ─────────────────────────────────────────────────────────────────────────
    public function store(array $params): void
    {
        $book = $this->requireBook($params['bookSlug'] ?? '');

        if (!csrf_verify($_POST['csrf_token'] ?? '')) {
            redirect_back();
            return;
        }

        $customerId = (int)($_POST['customer_id'] ?? 0);
        $customer   = $this->db->fetch(
            "SELECT id FROM customers WHERE id = :id AND book_id = :book_id",
            [':id' => $customerId, ':book_id' => $book['id']]
        );
        if (!$customer) {
            set_flash('error', 'Customer not found.');
            redirect_back();
            return;
        }

        $data = [
            ':book_id'     => $book['id'],
            ':customer_id' => $customerId,
            ':invoice_id'  => null,
            ':title'       => sanitize($_POST['title'] ?? 'Manual Due'),
            ':amount'      => (float)($_POST['amount'] ?? 0),
            ':paid_amount' => 0,
            ':currency_id' => $_POST['currency_id'] ? (int)$_POST['currency_id'] : null,
            ':due_date'    => $_POST['due_date'] ?: null,
            ':note'        => sanitize($_POST['note'] ?? ''),
            ':status'      => 'unpaid',
            ':created_by'  => Auth::id(),
        ];

        $this->db->query(
            "INSERT INTO dues
                (book_id, customer_id, invoice_id, title, amount, paid_amount,
                 currency_id, due_date, note, status, created_by)
             VALUES
                (:book_id, :customer_id, :invoice_id, :title, :amount, :paid_amount,
                 :currency_id, :due_date, :note, :status, :created_by)",
            $data
        );

        $this->logger->log(
            $book['id'], Auth::id(), 'due.created', 'Due',
            (int)$this->db->lastInsertId(),
            "Manual due created for customer #{$customerId}"
        );

        set_flash('success', 'Due added successfully.');
        redirect(base_url("Business/{$book['slug']}/Dues"));
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  POST  /Business/{bookSlug}/Dues/{id}/Cancel
    // ─────────────────────────────────────────────────────────────────────────
    public function cancel(array $params): void
    {
        $book = $this->requireBook($params['bookSlug'] ?? '');
        $due  = $this->requireDue((int)($params['id'] ?? 0), $book['id']);

        if (!csrf_verify($_POST['csrf_token'] ?? '')) {
            redirect_back();
            return;
        }

        $this->db->query(
            "UPDATE dues SET status = 'cancelled', updated_at = NOW() WHERE id = :id",
            [':id' => $due['id']]
        );

        $this->logger->log(
            $book['id'], Auth::id(), 'due.cancelled', 'Due', $due['id'],
            "Due #{$due['id']} cancelled"
        );

        set_flash('success', 'Due marked as cancelled.');
        redirect(base_url("Business/{$book['slug']}/Dues"));
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  Static helper: create a due automatically from an unpaid invoice
    // ─────────────────────────────────────────────────────────────────────────
    public static function createFromInvoice(array $invoice): ?int
    {
        if ($invoice['status'] !== 'unpaid') {
            return null;
        }

        $db = DB::getInstance();

        // Check if a due already exists for this invoice
        $existing = $db->fetch(
            "SELECT id FROM dues WHERE invoice_id = :inv_id AND book_id = :book_id",
            [':inv_id' => $invoice['id'], ':book_id' => $invoice['book_id']]
        );
        if ($existing) {
            return (int)$existing['id'];
        }

        $db->query(
            "INSERT INTO dues
                (book_id, customer_id, invoice_id, title, amount, paid_amount,
                 currency_id, status, created_by)
             VALUES
                (:book_id, :customer_id, :invoice_id, :title, :amount, 0,
                 :currency_id, 'unpaid', :created_by)",
            [
                ':book_id'     => $invoice['book_id'],
                ':customer_id' => $invoice['customer_id'],
                ':invoice_id'  => $invoice['id'],
                ':title'       => 'Invoice #' . $invoice['invoice_no'],
                ':amount'      => $invoice['grand_total'],
                ':currency_id' => $invoice['currency_id'] ?? null,
                ':created_by'  => Auth::id(),
            ]
        );

        $dueId = (int)$db->lastInsertId();

        // Update the invoice row to reference this due
        $db->query(
            "UPDATE invoices SET due_id = :due_id WHERE id = :id",
            [':due_id' => $dueId, ':id' => $invoice['id']]
        );

        return $dueId;
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  Helpers
    // ─────────────────────────────────────────────────────────────────────────
    private function requireBook(string $slug): array
    {
        $userId = Auth::id();
        $book   = $this->db->fetch(
            "SELECT b.* FROM books b
              INNER JOIN book_users bu ON bu.book_id = b.id
              WHERE b.slug = :slug AND bu.user_id = :uid AND b.is_active = 1",
            [':slug' => $slug, ':uid' => $userId]
        );
        if (!$book) {
            http_response_code(404);
            die('Book not found or access denied.');
        }
        return $book;
    }

    private function requireDue(int $id, int $bookId): array
    {
        $due = $this->db->fetch(
            "SELECT * FROM dues WHERE id = :id AND book_id = :book_id",
            [':id' => $id, ':book_id' => $bookId]
        );
        if (!$due) {
            http_response_code(404);
            die('Due record not found.');
        }
        return $due;
    }
}

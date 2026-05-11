<?php
namespace App\Controllers;
use App\Helpers\Database;

class DuesController
{
    public function index(array $params): void
    {
        if (guest()) redirect('/login');
        $book = $this->getBookOrFail($params['id']);

        $filter = $_GET['filter'] ?? 'all';
        $search = trim($_GET['q'] ?? '');

        $where = ['d.book_id=?'];
        $bind  = [$book['id']];

        if ($filter !== 'all') {
            $where[] = 'd.status=?';
            $bind[]  = $filter;
        }
        if ($search !== '') {
            $where[] = '(c.name LIKE ? OR c.phone LIKE ? OR d.title LIKE ?)';
            $bind[]  = "%{$search}%";
            $bind[]  = "%{$search}%";
            $bind[]  = "%{$search}%";
        }

        $whereSQL = implode(' AND ', $where);

        $dues = Database::query(
            "SELECT d.*,
                    c.name  AS customer_name,
                    c.phone AS customer_phone,
                    c.photo AS customer_photo,
                    i.invoice_no,
                    bc.symbol AS currency_symbol
             FROM dues d
             LEFT JOIN customers      c  ON c.id  = d.customer_id
             LEFT JOIN invoices       i  ON i.id  = d.invoice_id
             LEFT JOIN book_currencies bc ON bc.book_id = d.book_id AND bc.is_default = 1
             WHERE {$whereSQL}
             ORDER BY d.status ASC, d.created_at DESC",
            $bind
        );

        $summary = Database::row(
            "SELECT
                COALESCE(SUM(CASE WHEN status IN ('unpaid','partial') THEN amount - paid_amount ELSE 0 END), 0) AS outstanding,
                COALESCE(SUM(paid_amount), 0) AS total_collected,
                COUNT(*) AS total_count,
                SUM(status='unpaid')  AS unpaid_count,
                SUM(status='partial') AS partial_count,
                SUM(status='paid')    AS paid_count
             FROM dues WHERE book_id=?",
            [$book['id']]
        );

        $defaultCurrency = Database::row(
            'SELECT symbol FROM book_currencies WHERE book_id=? AND is_default=1 LIMIT 1',
            [$book['id']]
        );
        $symbol = $defaultCurrency['symbol'] ?? '৳';

        require BASE_PATH . '/views/business/dues/index.php';
    }

    public function store(array $params): void
    {
        if (guest()) redirect('/login');
        csrf_verify();
        $book = $this->getBookOrFail($params['id']);

        $customerId = (int)($_POST['customer_id'] ?? 0);
        $amount     = (float)($_POST['amount'] ?? 0);
        $title      = trim($_POST['title'] ?? '');
        $dueDate    = $_POST['due_date'] ?: null;
        $note       = trim($_POST['note'] ?? '');

        if (!$customerId || $amount <= 0 || !$title) {
            redirect('/books/'.$book['id'].'/dues', ['error' => 'Customer, title and amount are required.']);
        }

        $customer = Database::row(
            'SELECT id FROM customers WHERE id=? AND book_id=? AND deleted_at IS NULL',
            [$customerId, $book['id']]
        );
        if (!$customer) {
            redirect('/books/'.$book['id'].'/dues', ['error' => 'Customer not found.']);
        }

        Database::run(
            'INSERT INTO dues (book_id, customer_id, title, amount, paid_amount, due_date, status, created_by, created_at)
             VALUES (?,?,?,?,0,?,?,?,?)',
            [$book['id'], $customerId, $title, $amount, $dueDate, 'unpaid', auth()['id'], now()]
        );

        redirect('/books/'.$book['id'].'/dues', ['success' => 'Due added.']);
    }

    public function recordPayment(array $params): void
    {
        if (guest()) redirect('/login');
        csrf_verify();
        $book = $this->getBookOrFail($params['id']);
        $due  = $this->getDueOrFail($params['due_id'], $book['id']);

        $amount    = (float)($_POST['amount'] ?? 0);
        $remaining = (float)$due['amount'] - (float)$due['paid_amount'];
        $amount    = min($amount, $remaining);

        if ($amount <= 0) {
            redirect('/books/'.$book['id'].'/dues', ['error' => 'Invalid payment amount.']);
        }

        $newPaid   = (float)$due['paid_amount'] + $amount;
        $newStatus = $newPaid >= ((float)$due['amount'] - 0.001) ? 'paid' : 'partial';

        Database::run(
            'UPDATE dues SET paid_amount=?, status=?, updated_at=? WHERE id=?',
            [$newPaid, $newStatus, now(), $due['id']]
        );

        Database::run(
            'INSERT INTO due_payments (due_id, book_id, amount, payment_method, paid_by, paid_at)
             VALUES (?,?,?,?,?,?)',
            [$due['id'], $book['id'], $amount,
             trim($_POST['payment_method'] ?? 'cash'),
             auth()['id'], now()]
        );

        redirect('/books/'.$book['id'].'/dues', ['success' => format_money($amount).' recorded.']);
    }

    public function cancel(array $params): void
    {
        if (guest()) redirect('/login');
        csrf_verify();
        $book = $this->getBookOrFail($params['id']);
        $due  = $this->getDueOrFail($params['due_id'], $book['id']);

        Database::run(
            "UPDATE dues SET status='cancelled', updated_at=? WHERE id=?",
            [now(), $due['id']]
        );

        redirect('/books/'.$book['id'].'/dues', ['success' => 'Due cancelled.']);
    }

    public static function createFromInvoice(array $invoice): void
    {
        if (empty($invoice['customer_id'])) return;
        try {
            $existing = Database::row(
                'SELECT id FROM dues WHERE invoice_id=? AND book_id=?',
                [$invoice['id'], $invoice['book_id']]
            );
            if ($existing) return;

            Database::run(
                'INSERT INTO dues (book_id, customer_id, invoice_id, title, amount, paid_amount, status, created_by, created_at)
                 VALUES (?,?,?,?,?,0,?,?,?)',
                [
                    $invoice['book_id'],
                    $invoice['customer_id'],
                    $invoice['id'],
                    'Invoice #' . $invoice['invoice_no'],
                    $invoice['total'],
                    'unpaid',
                    auth()['id'] ?? null,
                    now()
                ]
            );
        } catch (\Throwable $e) {
            error_log('[DuesController::createFromInvoice] ' . $e->getMessage());
        }
    }

    private function getBookOrFail(string $id): array
    {
        $book = Database::row(
            'SELECT * FROM books WHERE id=? AND user_id=? AND deleted_at IS NULL AND type="business"',
            [$id, auth()['id']]
        );
        if (!$book) { http_response_code(404); require BASE_PATH.'/views/errors/404.php'; exit; }
        return $book;
    }

    private function getDueOrFail(string $dueId, int $bookId): array
    {
        $due = Database::row(
            'SELECT * FROM dues WHERE id=? AND book_id=?',
            [$dueId, $bookId]
        );
        if (!$due) { http_response_code(404); require BASE_PATH.'/views/errors/404.php'; exit; }
        return $due;
    }
}
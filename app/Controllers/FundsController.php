<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\DB;
use App\Services\ActivityLogger;

class FundsController
{
    private DB $db;
    private ActivityLogger $logger;

    public function __construct()
    {
        $this->db     = DB::getInstance();
        $this->logger = new ActivityLogger();
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  GET  /Business/{bookSlug}/Funds
    // ─────────────────────────────────────────────────────────────────────────
    public function index(array $params): void
    {
        $book = $this->requireBook($params['bookSlug'] ?? '');

        $filter  = $_GET['type']  ?? 'all';   // all | in | out
        $search  = trim($_GET['q'] ?? '');
        $page    = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 20;
        $offset  = ($page - 1) * $perPage;

        $where = ['f.book_id = :book_id'];
        $binds = [':book_id' => $book['id']];

        if ($filter === 'in' || $filter === 'out') {
            $where[]         = 'f.type = :type';
            $binds[':type']  = $filter;
        }
        if ($search !== '') {
            $where[]        = '(f.title LIKE :q OR f.reference LIKE :q OR f.description LIKE :q)';
            $binds[':q']    = "%{$search}%";
        }

        $whereSQL = implode(' AND ', $where);

        $total = (int)$this->db->fetch(
            "SELECT COUNT(*) AS n FROM funds f WHERE {$whereSQL}",
            $binds
        )['n'];

        $funds = $this->db->fetchAll(
            "SELECT f.*,
                    cu.symbol   AS currency_symbol,
                    cu.code     AS currency_code,
                    u.name      AS created_by_name
               FROM funds f
               LEFT JOIN currencies cu ON cu.id = f.currency_id
               LEFT JOIN users u       ON u.id  = f.created_by
              WHERE {$whereSQL}
              ORDER BY f.fund_date DESC, f.created_at DESC
              LIMIT {$perPage} OFFSET {$offset}",
            $binds
        );

        // Summary totals
        $summary = $this->db->fetch(
            "SELECT
                COALESCE(SUM(CASE WHEN type = 'in'  THEN amount ELSE 0 END), 0) AS total_in,
                COALESCE(SUM(CASE WHEN type = 'out' THEN amount ELSE 0 END), 0) AS total_out,
                COUNT(*)                                                          AS total_count
             FROM funds WHERE book_id = :book_id",
            [':book_id' => $book['id']]
        );

        $currencies = $this->db->fetchAll(
            "SELECT bc.*, cu.name, cu.symbol, cu.code
               FROM book_currencies bc
               JOIN currencies cu ON cu.id = bc.currency_id
              WHERE bc.book_id = :book_id",
            [':book_id' => $book['id']]
        );

        $totalPages = (int)ceil($total / $perPage);

        require views_path('business/funds/index.php');
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  POST  /Business/{bookSlug}/Funds  (create)
    // ─────────────────────────────────────────────────────────────────────────
    public function store(array $params): void
    {
        $book = $this->requireBook($params['bookSlug'] ?? '');

        if (!csrf_verify($_POST['csrf_token'] ?? '')) {
            redirect_back();
            return;
        }

        $type   = $_POST['type'] === 'out' ? 'out' : 'in';
        $amount = (float)($_POST['amount'] ?? 0);

        if ($amount <= 0) {
            set_flash('error', 'Amount must be greater than zero.');
            redirect_back();
            return;
        }

        $attachmentPath = null;
        if (!empty($_FILES['attachment']['name'])) {
            $attachmentPath = $this->handleUpload($_FILES['attachment'], $book['id']);
        }

        $data = [
            ':book_id'    => $book['id'],
            ':type'       => $type,
            ':title'      => sanitize($_POST['title'] ?? ($type === 'in' ? 'Add Funds' : 'Withdraw Funds')),
            ':description'=> sanitize($_POST['description'] ?? ''),
            ':amount'     => $amount,
            ':currency_id'=> !empty($_POST['currency_id']) ? (int)$_POST['currency_id'] : null,
            ':method'     => sanitize($_POST['payment_method'] ?? 'cash'),
            ':reference'  => sanitize($_POST['reference'] ?? ''),
            ':attachment' => $attachmentPath,
            ':fund_date'  => $_POST['fund_date'] ?: date('Y-m-d'),
            ':created_by' => Auth::id(),
        ];

        $this->db->query(
            "INSERT INTO funds
                (book_id, type, title, description, amount, currency_id,
                 payment_method, reference, attachment, fund_date, created_by)
             VALUES
                (:book_id, :type, :title, :description, :amount, :currency_id,
                 :method, :reference, :attachment, :fund_date, :created_by)",
            $data
        );

        $fundId = (int)$this->db->lastInsertId();

        $this->logger->log(
            $book['id'], Auth::id(), 'fund.' . $type, 'Fund', $fundId,
            ucfirst($type) . " fund of {$amount} recorded: " . sanitize($_POST['title'] ?? '')
        );

        set_flash('success', 'Fund entry saved successfully.');
        redirect(base_url("Business/{$book['slug']}/Funds"));
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  GET  /Business/{bookSlug}/Funds/{id}/Edit
    // ─────────────────────────────────────────────────────────────────────────
    public function edit(array $params): void
    {
        $book = $this->requireBook($params['bookSlug'] ?? '');
        $fund = $this->requireFund((int)($params['id'] ?? 0), $book['id']);

        $currencies = $this->db->fetchAll(
            "SELECT bc.*, cu.name, cu.symbol, cu.code
               FROM book_currencies bc
               JOIN currencies cu ON cu.id = bc.currency_id
              WHERE bc.book_id = :book_id",
            [':book_id' => $book['id']]
        );

        require views_path('business/funds/edit.php');
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  POST  /Business/{bookSlug}/Funds/{id}/Update
    // ─────────────────────────────────────────────────────────────────────────
    public function update(array $params): void
    {
        $book = $this->requireBook($params['bookSlug'] ?? '');
        $fund = $this->requireFund((int)($params['id'] ?? 0), $book['id']);

        if (!csrf_verify($_POST['csrf_token'] ?? '')) {
            redirect_back();
            return;
        }

        $amount = (float)($_POST['amount'] ?? 0);
        if ($amount <= 0) {
            set_flash('error', 'Amount must be greater than zero.');
            redirect_back();
            return;
        }

        $attachmentPath = $fund['attachment'];
        if (!empty($_FILES['attachment']['name'])) {
            $attachmentPath = $this->handleUpload($_FILES['attachment'], $book['id']);
        }

        $this->db->query(
            "UPDATE funds SET
                type            = :type,
                title           = :title,
                description     = :description,
                amount          = :amount,
                currency_id     = :currency_id,
                payment_method  = :method,
                reference       = :reference,
                attachment      = :attachment,
                fund_date       = :fund_date,
                updated_at      = NOW()
             WHERE id = :id AND book_id = :book_id",
            [
                ':type'        => $_POST['type'] === 'out' ? 'out' : 'in',
                ':title'       => sanitize($_POST['title'] ?? ''),
                ':description' => sanitize($_POST['description'] ?? ''),
                ':amount'      => $amount,
                ':currency_id' => !empty($_POST['currency_id']) ? (int)$_POST['currency_id'] : null,
                ':method'      => sanitize($_POST['payment_method'] ?? 'cash'),
                ':reference'   => sanitize($_POST['reference'] ?? ''),
                ':attachment'  => $attachmentPath,
                ':fund_date'   => $_POST['fund_date'] ?: date('Y-m-d'),
                ':id'          => $fund['id'],
                ':book_id'     => $book['id'],
            ]
        );

        $this->logger->log(
            $book['id'], Auth::id(), 'fund.updated', 'Fund', $fund['id'],
            "Fund #{$fund['id']} updated"
        );

        set_flash('success', 'Fund entry updated successfully.');
        redirect(base_url("Business/{$book['slug']}/Funds"));
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  POST  /Business/{bookSlug}/Funds/{id}/Delete
    // ─────────────────────────────────────────────────────────────────────────
    public function delete(array $params): void
    {
        $book = $this->requireBook($params['bookSlug'] ?? '');
        $fund = $this->requireFund((int)($params['id'] ?? 0), $book['id']);

        if (!csrf_verify($_POST['csrf_token'] ?? '')) {
            redirect_back();
            return;
        }

        $this->db->query(
            "DELETE FROM funds WHERE id = :id AND book_id = :book_id",
            [':id' => $fund['id'], ':book_id' => $book['id']]
        );

        $this->logger->log(
            $book['id'], Auth::id(), 'fund.deleted', 'Fund', $fund['id'],
            "Fund #{$fund['id']} deleted"
        );

        set_flash('success', 'Fund entry deleted.');
        redirect(base_url("Business/{$book['slug']}/Funds"));
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  Helpers
    // ─────────────────────────────────────────────────────────────────────────
    private function requireBook(string $slug): array
    {
        $book = $this->db->fetch(
            "SELECT b.* FROM books b
              INNER JOIN book_users bu ON bu.book_id = b.id
              WHERE b.slug = :slug AND bu.user_id = :uid AND b.is_active = 1",
            [':slug' => $slug, ':uid' => Auth::id()]
        );
        if (!$book) {
            http_response_code(404);
            die('Book not found or access denied.');
        }
        return $book;
    }

    private function requireFund(int $id, int $bookId): array
    {
        $fund = $this->db->fetch(
            "SELECT * FROM funds WHERE id = :id AND book_id = :book_id",
            [':id' => $id, ':book_id' => $bookId]
        );
        if (!$fund) {
            http_response_code(404);
            die('Fund record not found.');
        }
        return $fund;
    }

    private function handleUpload(array $file, int $bookId): ?string
    {
        $uploadDir = rtrim($_ENV['UPLOAD_PATH'] ?? '/Sites/byabsayee/uploads', '/') . "/funds/{$bookId}/";
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }

        $allowed = ['pdf', 'jpg', 'jpeg', 'png', 'webp'];
        $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed, true)) {
            return null;
        }

        $filename = uniqid('fund_', true) . '.' . $ext;
        $dest     = $uploadDir . $filename;

        if (move_uploaded_file($file['tmp_name'], $dest)) {
            return "funds/{$bookId}/{$filename}";
        }

        return null;
    }
}

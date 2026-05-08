<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\DB;
use App\Services\ActivityLogger;

class ExpensesController
{
    private DB $db;
    private ActivityLogger $logger;

    public function __construct()
    {
        $this->db     = DB::getInstance();
        $this->logger = new ActivityLogger();
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  GET  /Business/{bookSlug}/Expenses
    // ─────────────────────────────────────────────────────────────────────────
    public function index(array $params): void
    {
        $book = $this->requireBook($params['bookSlug'] ?? '');

        $filterCat    = (int)($_GET['category'] ?? 0);
        $filterStatus = $_GET['status'] ?? 'all';
        $search       = trim($_GET['q'] ?? '');
        $dateFrom     = $_GET['from'] ?? '';
        $dateTo       = $_GET['to']   ?? '';
        $page         = max(1, (int)($_GET['page'] ?? 1));
        $perPage      = 20;
        $offset       = ($page - 1) * $perPage;

        $where = ['e.book_id = :book_id'];
        $binds = [':book_id' => $book['id']];

        if ($filterCat > 0) {
            $where[]             = 'e.category_id = :cat_id';
            $binds[':cat_id']    = $filterCat;
        }
        if ($filterStatus !== 'all') {
            $where[]              = 'e.status = :status';
            $binds[':status']     = $filterStatus;
        }
        if ($search !== '') {
            $where[]              = '(e.title LIKE :q OR e.description LIKE :q OR e.reference LIKE :q)';
            $binds[':q']          = "%{$search}%";
        }
        if ($dateFrom !== '') {
            $where[]              = 'e.expense_date >= :date_from';
            $binds[':date_from']  = $dateFrom;
        }
        if ($dateTo !== '') {
            $where[]              = 'e.expense_date <= :date_to';
            $binds[':date_to']    = $dateTo;
        }

        $whereSQL = implode(' AND ', $where);

        $total = (int)$this->db->fetch(
            "SELECT COUNT(*) AS n FROM expenses e WHERE {$whereSQL}",
            $binds
        )['n'];

        $expenses = $this->db->fetchAll(
            "SELECT e.*,
                    ec.name     AS category_name,
                    ec.icon     AS category_icon,
                    ec.color    AS category_color,
                    cu.symbol   AS currency_symbol,
                    cu.code     AS currency_code,
                    u.name      AS created_by_name
               FROM expenses e
               LEFT JOIN expense_categories ec ON ec.id = e.category_id
               LEFT JOIN currencies cu          ON cu.id = e.currency_id
               LEFT JOIN users u                ON u.id  = e.created_by
              WHERE {$whereSQL}
              ORDER BY e.expense_date DESC, e.created_at DESC
              LIMIT {$perPage} OFFSET {$offset}",
            $binds
        );

        // Summary
        $summary = $this->db->fetch(
            "SELECT
                COALESCE(SUM(CASE WHEN status = 'paid'    THEN amount ELSE 0 END), 0) AS total_paid,
                COALESCE(SUM(CASE WHEN status = 'unpaid'  THEN amount ELSE 0 END), 0) AS total_unpaid,
                COUNT(*)                                                                AS total_count
             FROM expenses WHERE book_id = :book_id",
            [':book_id' => $book['id']]
        );

        // Category breakdown (for chart / filter sidebar)
        $categoryTotals = $this->db->fetchAll(
            "SELECT ec.id, ec.name, ec.icon, ec.color,
                    COUNT(e.id)   AS count,
                    SUM(e.amount) AS total
               FROM expense_categories ec
               LEFT JOIN expenses e ON e.category_id = ec.id AND e.book_id = :book_id
              WHERE ec.book_id = :book_id2
              GROUP BY ec.id
              ORDER BY total DESC",
            [':book_id' => $book['id'], ':book_id2' => $book['id']]
        );

        $categories = $this->db->fetchAll(
            "SELECT * FROM expense_categories WHERE book_id = :book_id AND is_active = 1 ORDER BY name",
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

        require views_path('business/expenses/index.php');
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  POST  /Business/{bookSlug}/Expenses  (create)
    // ─────────────────────────────────────────────────────────────────────────
    public function store(array $params): void
    {
        $book = $this->requireBook($params['bookSlug'] ?? '');

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

        $attachmentPath = null;
        if (!empty($_FILES['attachment']['name'])) {
            $attachmentPath = $this->handleUpload($_FILES['attachment'], $book['id']);
        }

        $categoryId = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;

        // Auto-create category if user typed a new one
        if (!empty($_POST['new_category'])) {
            $catName = sanitize(trim($_POST['new_category']));
            $existing = $this->db->fetch(
                "SELECT id FROM expense_categories WHERE book_id = :b AND name = :n",
                [':b' => $book['id'], ':n' => $catName]
            );
            if ($existing) {
                $categoryId = (int)$existing['id'];
            } else {
                $this->db->query(
                    "INSERT INTO expense_categories (book_id, name) VALUES (:b, :n)",
                    [':b' => $book['id'], ':n' => $catName]
                );
                $categoryId = (int)$this->db->lastInsertId();
            }
        }

        $isRecurring     = isset($_POST['is_recurring']) ? 1 : 0;
        $recurInterval   = $isRecurring && !empty($_POST['recur_interval'])
                            ? sanitize($_POST['recur_interval'])
                            : null;

        $data = [
            ':book_id'      => $book['id'],
            ':category_id'  => $categoryId,
            ':title'        => sanitize($_POST['title'] ?? 'Expense'),
            ':description'  => sanitize($_POST['description'] ?? ''),
            ':amount'       => $amount,
            ':currency_id'  => !empty($_POST['currency_id']) ? (int)$_POST['currency_id'] : null,
            ':method'       => sanitize($_POST['payment_method'] ?? 'cash'),
            ':reference'    => sanitize($_POST['reference'] ?? ''),
            ':attachment'   => $attachmentPath,
            ':expense_date' => $_POST['expense_date'] ?: date('Y-m-d'),
            ':status'       => in_array($_POST['status'] ?? '', ['paid','unpaid','cancelled'])
                                    ? $_POST['status'] : 'paid',
            ':is_recurring' => $isRecurring,
            ':recur_interval'=> $recurInterval,
            ':created_by'   => Auth::id(),
        ];

        $this->db->query(
            "INSERT INTO expenses
                (book_id, category_id, title, description, amount, currency_id,
                 payment_method, reference, attachment, expense_date, status,
                 is_recurring, recur_interval, created_by)
             VALUES
                (:book_id, :category_id, :title, :description, :amount, :currency_id,
                 :method, :reference, :attachment, :expense_date, :status,
                 :is_recurring, :recur_interval, :created_by)",
            $data
        );

        $expId = (int)$this->db->lastInsertId();

        // If unpaid, auto-create a debt record
        if ($data[':status'] === 'unpaid') {
            $this->db->query(
                "INSERT INTO debts
                    (book_id, supplier_id, creditor_name, title, amount, paid_amount,
                     currency_id, status, created_by)
                 VALUES
                    (:book_id, NULL, :creditor, :title, :amount, 0,
                     :currency_id, 'unpaid', :created_by)",
                [
                    ':book_id'     => $book['id'],
                    ':creditor'    => sanitize($_POST['creditor_name'] ?? ''),
                    ':title'       => 'Expense: ' . sanitize($_POST['title'] ?? ''),
                    ':amount'      => $amount,
                    ':currency_id' => $data[':currency_id'],
                    ':created_by'  => Auth::id(),
                ]
            );
        }

        $this->logger->log(
            $book['id'], Auth::id(), 'expense.created', 'Expense', $expId,
            "Expense '{$data[':title']}' of {$amount} recorded"
        );

        set_flash('success', 'Expense added successfully.');
        redirect(base_url("Business/{$book['slug']}/Expenses"));
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  GET  /Business/{bookSlug}/Expenses/{id}/Edit
    // ─────────────────────────────────────────────────────────────────────────
    public function edit(array $params): void
    {
        $book    = $this->requireBook($params['bookSlug'] ?? '');
        $expense = $this->requireExpense((int)($params['id'] ?? 0), $book['id']);

        $categories = $this->db->fetchAll(
            "SELECT * FROM expense_categories WHERE book_id = :book_id AND is_active = 1 ORDER BY name",
            [':book_id' => $book['id']]
        );
        $currencies = $this->db->fetchAll(
            "SELECT bc.*, cu.name, cu.symbol, cu.code
               FROM book_currencies bc
               JOIN currencies cu ON cu.id = bc.currency_id
              WHERE bc.book_id = :book_id",
            [':book_id' => $book['id']]
        );

        require views_path('business/expenses/edit.php');
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  POST  /Business/{bookSlug}/Expenses/{id}/Update
    // ─────────────────────────────────────────────────────────────────────────
    public function update(array $params): void
    {
        $book    = $this->requireBook($params['bookSlug'] ?? '');
        $expense = $this->requireExpense((int)($params['id'] ?? 0), $book['id']);

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

        $attachmentPath = $expense['attachment'];
        if (!empty($_FILES['attachment']['name'])) {
            $attachmentPath = $this->handleUpload($_FILES['attachment'], $book['id']);
        }

        $this->db->query(
            "UPDATE expenses SET
                category_id    = :category_id,
                title          = :title,
                description    = :description,
                amount         = :amount,
                currency_id    = :currency_id,
                payment_method = :method,
                reference      = :reference,
                attachment     = :attachment,
                expense_date   = :expense_date,
                status         = :status,
                is_recurring   = :is_recurring,
                recur_interval = :recur_interval,
                updated_at     = NOW()
             WHERE id = :id AND book_id = :book_id",
            [
                ':category_id'   => !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null,
                ':title'         => sanitize($_POST['title'] ?? ''),
                ':description'   => sanitize($_POST['description'] ?? ''),
                ':amount'        => $amount,
                ':currency_id'   => !empty($_POST['currency_id']) ? (int)$_POST['currency_id'] : null,
                ':method'        => sanitize($_POST['payment_method'] ?? 'cash'),
                ':reference'     => sanitize($_POST['reference'] ?? ''),
                ':attachment'    => $attachmentPath,
                ':expense_date'  => $_POST['expense_date'] ?: date('Y-m-d'),
                ':status'        => in_array($_POST['status'] ?? '', ['paid','unpaid','cancelled'])
                                        ? $_POST['status'] : 'paid',
                ':is_recurring'  => isset($_POST['is_recurring']) ? 1 : 0,
                ':recur_interval'=> !empty($_POST['recur_interval']) ? sanitize($_POST['recur_interval']) : null,
                ':id'            => $expense['id'],
                ':book_id'       => $book['id'],
            ]
        );

        $this->logger->log(
            $book['id'], Auth::id(), 'expense.updated', 'Expense', $expense['id'],
            "Expense #{$expense['id']} updated"
        );

        set_flash('success', 'Expense updated successfully.');
        redirect(base_url("Business/{$book['slug']}/Expenses"));
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  POST  /Business/{bookSlug}/Expenses/{id}/Delete
    // ─────────────────────────────────────────────────────────────────────────
    public function delete(array $params): void
    {
        $book    = $this->requireBook($params['bookSlug'] ?? '');
        $expense = $this->requireExpense((int)($params['id'] ?? 0), $book['id']);

        if (!csrf_verify($_POST['csrf_token'] ?? '')) {
            redirect_back();
            return;
        }

        $this->db->query(
            "DELETE FROM expenses WHERE id = :id AND book_id = :book_id",
            [':id' => $expense['id'], ':book_id' => $book['id']]
        );

        $this->logger->log(
            $book['id'], Auth::id(), 'expense.deleted', 'Expense', $expense['id'],
            "Expense #{$expense['id']} deleted"
        );

        set_flash('success', 'Expense deleted.');
        redirect(base_url("Business/{$book['slug']}/Expenses"));
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  POST  /Business/{bookSlug}/Expenses/Categories (AJAX – create category)
    // ─────────────────────────────────────────────────────────────────────────
    public function storeCategory(array $params): void
    {
        $book = $this->requireBook($params['bookSlug'] ?? '');
        header('Content-Type: application/json');

        if (!csrf_verify($_POST['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'message' => 'Invalid CSRF token.']);
            return;
        }

        $name = sanitize(trim($_POST['name'] ?? ''));
        if ($name === '') {
            echo json_encode(['success' => false, 'message' => 'Category name is required.']);
            return;
        }

        $existing = $this->db->fetch(
            "SELECT id FROM expense_categories WHERE book_id = :b AND name = :n",
            [':b' => $book['id'], ':n' => $name]
        );
        if ($existing) {
            echo json_encode(['success' => false, 'message' => 'Category already exists.', 'id' => $existing['id']]);
            return;
        }

        $this->db->query(
            "INSERT INTO expense_categories (book_id, name, icon, color)
             VALUES (:b, :n, :icon, :color)",
            [
                ':b'     => $book['id'],
                ':n'     => $name,
                ':icon'  => sanitize($_POST['icon']  ?? 'fa-tag'),
                ':color' => sanitize($_POST['color'] ?? '#6c757d'),
            ]
        );

        echo json_encode([
            'success' => true,
            'id'      => $this->db->lastInsertId(),
            'name'    => $name,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  Seed default expense categories for a newly created book
    // ─────────────────────────────────────────────────────────────────────────
    public static function seedDefaultCategories(int $bookId): void
    {
        $db = DB::getInstance();

        $defaults = [
            ['Utility',       'fa-bolt',        '#f0ad4e'],
            ['Rent',          'fa-building',    '#5bc0de'],
            ['Salary',        'fa-users',       '#5cb85c'],
            ['Transport',     'fa-truck',       '#337ab7'],
            ['Maintenance',   'fa-wrench',      '#9b59b6'],
            ['Marketing',     'fa-bullhorn',    '#e74c3c'],
            ['Miscellaneous', 'fa-ellipsis-h',  '#95a5a6'],
        ];

        foreach ($defaults as [$name, $icon, $color]) {
            $db->query(
                "INSERT IGNORE INTO expense_categories (book_id, name, icon, color)
                 VALUES (:b, :n, :i, :c)",
                [':b' => $bookId, ':n' => $name, ':i' => $icon, ':c' => $color]
            );
        }
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

    private function requireExpense(int $id, int $bookId): array
    {
        $exp = $this->db->fetch(
            "SELECT * FROM expenses WHERE id = :id AND book_id = :book_id",
            [':id' => $id, ':book_id' => $bookId]
        );
        if (!$exp) {
            http_response_code(404);
            die('Expense not found.');
        }
        return $exp;
    }

    private function handleUpload(array $file, int $bookId): ?string
    {
        $uploadDir = rtrim($_ENV['UPLOAD_PATH'] ?? '/Sites/byabsayee/uploads', '/') . "/expenses/{$bookId}/";
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }

        $allowed = ['pdf', 'jpg', 'jpeg', 'png', 'webp'];
        $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed, true)) {
            return null;
        }

        $filename = uniqid('exp_', true) . '.' . $ext;
        $dest     = $uploadDir . $filename;

        if (move_uploaded_file($file['tmp_name'], $dest)) {
            return "expenses/{$bookId}/{$filename}";
        }

        return null;
    }
}

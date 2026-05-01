<?php
namespace App\Controllers;
use App\Helpers\Database;

class CustomerController
{
    public function index(array $params): void
    {
        if (guest()) redirect('/login');
        $book   = $this->getBookOrFail($params['id']);
        $search = trim($_GET['q'] ?? '');

        $sql = 'SELECT c.*,
                    cp.name AS privilege_name,
                    cp.discount_type,
                    cp.discount_value,
                    COUNT(DISTINCT i.id) AS invoice_count,
                    COALESCE(SUM(i.total),0) AS total_billed,
                    COALESCE(SUM(i.paid),0)  AS total_paid
                FROM customers c
                LEFT JOIN customer_privileges cp ON cp.id=c.privilege_id
                LEFT JOIN invoices i ON i.customer_id=c.id AND i.deleted_at IS NULL
                WHERE c.book_id=? AND c.deleted_at IS NULL';
        $p = [$book['id']];

        if ($search) {
            $sql .= ' AND (c.name LIKE ? OR c.phone LIKE ? OR c.email LIKE ?)';
            $like = '%'.$search.'%';
            $p    = array_merge($p, [$like, $like, $like]);
        }

        $sql .= ' GROUP BY c.id ORDER BY c.name';
        $customers = Database::query($sql, $p);

        require BASE_PATH . '/views/business/customers/index.php';
    }

    public function show(array $params): void
    {
        if (guest()) redirect('/login');
        $book     = $this->getBookOrFail($params['id']);
        $customer = $this->getCustomerOrFail($params['customer_id'], $book['id']);

        $invoices   = Database::query(
            'SELECT * FROM invoices WHERE customer_id=? AND book_id=? AND deleted_at IS NULL ORDER BY date DESC',
            [$customer['id'], $book['id']]
        );
        $totals     = Database::row(
            'SELECT COALESCE(SUM(total),0) AS total_billed,
                    COALESCE(SUM(paid),0)  AS total_paid,
                    COALESCE(SUM(total)-SUM(paid),0) AS total_due
             FROM invoices WHERE customer_id=? AND book_id=? AND deleted_at IS NULL',
            [$customer['id'], $book['id']]
        );
        $privilege  = $customer['privilege_id']
            ? Database::row('SELECT * FROM customer_privileges WHERE id=?', [$customer['privilege_id']])
            : null;
        $privileges = Database::query(
            'SELECT * FROM customer_privileges WHERE book_id=? ORDER BY name',
            [$book['id']]
        );

        require BASE_PATH . '/views/business/customers/show.php';
    }

    public function store(array $params): void
    {
        if (guest()) redirect('/login');
        csrf_verify();
        $book = $this->getBookOrFail($params['id']);

        $name = trim($_POST['name'] ?? '');
        if (!$name) redirect('/books/'.$book['id'].'/customers', ['error' => 'Name is required.']);

        Database::run(
            'INSERT INTO customers (book_id,name,phone,email,address,notes,privilege_id,created_at)
             VALUES (?,?,?,?,?,?,?,?)',
            [$book['id'], $name,
             trim($_POST['phone']   ?? '') ?: null,
             trim($_POST['email']   ?? '') ?: null,
             trim($_POST['address'] ?? '') ?: null,
             trim($_POST['notes']   ?? '') ?: null,
             !empty($_POST['privilege_id']) ? (int)$_POST['privilege_id'] : null,
             now()]
        );

        redirect('/books/'.$book['id'].'/customers', ['success' => $name.' added.']);
    }

    public function update(array $params): void
    {
        if (guest()) redirect('/login');
        csrf_verify();
        $book     = $this->getBookOrFail($params['id']);
        $customer = $this->getCustomerOrFail($params['customer_id'], $book['id']);

        $name = trim($_POST['name'] ?? '');
        if (!$name) redirect('/books/'.$book['id'].'/customers/'.$customer['id'], ['error' => 'Name is required.']);

        Database::run(
            'UPDATE customers SET name=?,phone=?,email=?,address=?,notes=?,privilege_id=? WHERE id=?',
            [$name,
             trim($_POST['phone']   ?? '') ?: null,
             trim($_POST['email']   ?? '') ?: null,
             trim($_POST['address'] ?? '') ?: null,
             trim($_POST['notes']   ?? '') ?: null,
             !empty($_POST['privilege_id']) ? (int)$_POST['privilege_id'] : null,
             $customer['id']]
        );

        redirect('/books/'.$book['id'].'/customers/'.$customer['id'], ['success' => 'Customer updated.']);
    }

    public function delete(array $params): void
    {
        if (guest()) redirect('/login');
        csrf_verify();
        $book     = $this->getBookOrFail($params['id']);
        $customer = $this->getCustomerOrFail($params['customer_id'], $book['id']);

        Database::run('UPDATE customers SET deleted_at=? WHERE id=?', [now(), $customer['id']]);
        redirect('/books/'.$book['id'].'/customers', ['success' => $customer['name'].' deleted.']);
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

    private function getCustomerOrFail(string $cid, int $bookId): array
    {
        $c = Database::row('SELECT * FROM customers WHERE id=? AND book_id=? AND deleted_at IS NULL', [$cid, $bookId]);
        if (!$c) { http_response_code(404); require BASE_PATH.'/views/errors/404.php'; exit; }
        return $c;
    }
}

    // Update multi-privileges  →  POST /books/{id}/customers/{customer_id}/privileges
    public function updatePrivileges(array $params): void
    {
        if (guest()) redirect('/login');
        csrf_verify();
        $book     = $this->getBookOrFail($params['id']);
        $customer = $this->getCustomerOrFail($params['customer_id'], $book['id']);

        // Remove all existing assignments
        Database::run('DELETE FROM customer_privilege_assignments WHERE customer_id=?', [$customer['id']]);

        // Add new ones
        $selected = $_POST['privilege_ids'] ?? [];
        foreach ($selected as $privId) {
            $privId = (int)$privId;
            if (!$privId) continue;
            // Verify privilege belongs to this book
            $priv = Database::row('SELECT id FROM customer_privileges WHERE id=? AND book_id=?', [$privId, $book['id']]);
            if ($priv) {
                Database::run(
                    'INSERT IGNORE INTO customer_privilege_assignments (customer_id,privilege_id) VALUES (?,?)',
                    [$customer['id'], $privId]
                );
            }
        }

        redirect('/books/'.$book['id'].'/customers/'.$customer['id'], ['success' => 'Privileges updated.']);
    }

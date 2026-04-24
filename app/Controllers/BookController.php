<?php
// =============================================================================
// app/Controllers/BookController.php
// =============================================================================

namespace App\Controllers;

use App\Helpers\Database;

class BookController
{
    // =========================================================================
    // LIST ALL BOOKS  →  GET /books
    // =========================================================================
    public function index(): void
    {
        if (guest()) redirect('/login');

        $userId = auth()['id'];

        $books = Database::query(
            'SELECT b.*,
                (SELECT COUNT(*) FROM entries e WHERE e.book_id = b.id AND e.deleted_at IS NULL) AS entry_count,
                (SELECT COALESCE(SUM(amount),0) FROM entries e WHERE e.book_id = b.id AND e.type = "in"  AND e.deleted_at IS NULL) AS total_in,
                (SELECT COALESCE(SUM(amount),0) FROM entries e WHERE e.book_id = b.id AND e.type = "out" AND e.deleted_at IS NULL) AS total_out
             FROM books b
             WHERE b.user_id = ? AND b.deleted_at IS NULL
             ORDER BY b.created_at DESC',
            [$userId]
        );

        require BASE_PATH . '/views/books/index.php';
    }

    // =========================================================================
    // SHOW CREATE BOOK FORM  →  GET /books/create
    // =========================================================================
    public function create(): void
    {
        if (guest()) redirect('/login');
        require BASE_PATH . '/views/books/create.php';
    }

    // =========================================================================
    // STORE NEW BOOK  →  POST /books/create
    // =========================================================================
    public function store(): void
    {
        if (guest()) redirect('/login');
        csrf_verify();

        $userId = auth()['id'];
        $name   = trim($_POST['name'] ?? '');
        $type   = $_POST['type'] ?? 'personal';
        $color  = $_POST['color'] ?? '#1a6b4a';

        // Validate
        if (mb_strlen($name) < 1) {
            set_old(['name' => $name, 'type' => $type]);
            redirect('/books/create', ['error' => 'Please enter a book name.']);
        }

        if (!in_array($type, ['personal', 'business'])) {
            redirect('/books/create', ['error' => 'Invalid book type.']);
        }

        // Sanitize color
        if (!preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
            $color = '#1a6b4a';
        }

        // Create book
        Database::run(
            'INSERT INTO books (user_id, name, type, color, created_at) VALUES (?, ?, ?, ?, ?)',
            [$userId, $name, $type, $color, now()]
        );
        $bookId = Database::lastId();

        // If business, create the business details row too
        if ($type === 'business') {
            $businessName = trim($_POST['business_name'] ?? $name);
            $phone        = trim($_POST['phone'] ?? '');
            $address      = trim($_POST['address'] ?? '');

            Database::run(
                'INSERT INTO book_business_details (book_id, business_name, phone, address, invoice_prefix, invoice_counter)
                 VALUES (?, ?, ?, ?, ?, 1)',
                [$bookId, $businessName, $phone, $address, 'INV']
            );
        }

        redirect('/books/' . $bookId, ['success' => 'Book created successfully!']);
    }

    // =========================================================================
    // VIEW A BOOK  →  GET /books/{id}
    // =========================================================================
    public function show(array $params): void
    {
        if (guest()) redirect('/login');

        $book = $this->getBookOrFail($params['id']);

        if ($book['type'] === 'personal') {
            $this->showPersonal($book);
        } else {
            $this->showBusiness($book);
        }
    }

    // =========================================================================
    // SHOW EDIT FORM  →  GET /books/{id}/edit
    // =========================================================================
    public function edit(array $params): void
    {
        if (guest()) redirect('/login');
        $book = $this->getBookOrFail($params['id']);
        require BASE_PATH . '/views/books/edit.php';
    }

    // =========================================================================
    // UPDATE BOOK  →  POST /books/{id}/edit
    // =========================================================================
    public function update(array $params): void
    {
        if (guest()) redirect('/login');
        csrf_verify();

        $book  = $this->getBookOrFail($params['id']);
        $name  = trim($_POST['name'] ?? '');
        $color = $_POST['color'] ?? $book['color'];

        if (mb_strlen($name) < 1) {
            redirect('/books/' . $book['id'] . '/edit', ['error' => 'Please enter a book name.']);
        }

        if (!preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
            $color = $book['color'];
        }

        Database::run(
            'UPDATE books SET name = ?, color = ? WHERE id = ?',
            [$name, $color, $book['id']]
        );

        if ($book['type'] === 'business') {
            $businessName = trim($_POST['business_name'] ?? '');
            $phone        = trim($_POST['phone'] ?? '');
            $address      = trim($_POST['address'] ?? '');

            Database::run(
                'UPDATE book_business_details SET business_name = ?, phone = ?, address = ? WHERE book_id = ?',
                [$businessName, $phone, $address, $book['id']]
            );
        }

        redirect('/books/' . $book['id'], ['success' => 'Book updated.']);
    }

    // =========================================================================
    // DELETE BOOK  →  POST /books/{id}/delete
    // =========================================================================
    public function delete(array $params): void
    {
        if (guest()) redirect('/login');
        csrf_verify();

        $book = $this->getBookOrFail($params['id']);

        // Soft delete — keeps data safe, just hides it
        Database::run(
            'UPDATE books SET deleted_at = ? WHERE id = ?',
            [now(), $book['id']]
        );

        redirect('/books', ['success' => '"' . $book['name'] . '" has been deleted.']);
    }

    // =========================================================================
    // PRIVATE: personal book view
    // =========================================================================
    private function showPersonal(array $book): void
    {
        // Summary totals
        $totals = Database::row(
            'SELECT
                COALESCE(SUM(CASE WHEN type="in"  THEN amount ELSE 0 END), 0) AS total_in,
                COALESCE(SUM(CASE WHEN type="out" THEN amount ELSE 0 END), 0) AS total_out
             FROM entries WHERE book_id = ? AND deleted_at IS NULL',
            [$book['id']]
        );

        // Recent entries (latest 50)
        $entries = Database::query(
            'SELECT e.*, c.name AS contact_name
             FROM entries e
             LEFT JOIN contacts c ON e.contact_id = c.id
             WHERE e.book_id = ? AND e.deleted_at IS NULL
             ORDER BY e.entry_date DESC, e.created_at DESC
             LIMIT 50',
            [$book['id']]
        );

        // All contacts for this book (for the add-entry form dropdown)
        $contacts = Database::query(
            'SELECT id, name FROM contacts WHERE book_id = ? AND deleted_at IS NULL ORDER BY name',
            [$book['id']]
        );

        require BASE_PATH . '/views/books/personal.php';
    }

    // =========================================================================
    // PRIVATE: business book view (stub for now, expanded in Phase 3)
    // =========================================================================
    private function showBusiness(array $book): void
    {
        // Business details
        $details = Database::row(
            'SELECT * FROM book_business_details WHERE book_id = ?',
            [$book['id']]
        );

        // Quick stats
        $stats = Database::row(
            'SELECT
                (SELECT COUNT(*) FROM customers  WHERE book_id = ? AND deleted_at IS NULL) AS customers,
                (SELECT COUNT(*) FROM suppliers  WHERE book_id = ? AND deleted_at IS NULL) AS suppliers,
                (SELECT COUNT(*) FROM products   WHERE book_id = ? AND deleted_at IS NULL) AS products,
                (SELECT COUNT(*) FROM invoices   WHERE book_id = ? AND deleted_at IS NULL) AS invoices,
                (SELECT COALESCE(SUM(total),0) FROM invoices WHERE book_id = ? AND type="sale" AND status="paid" AND deleted_at IS NULL) AS total_sales,
                (SELECT COALESCE(SUM(total),0) FROM invoices WHERE book_id = ? AND type="purchase" AND status="paid" AND deleted_at IS NULL) AS total_purchases',
            array_fill(0, 6, $book['id'])
        );

        require BASE_PATH . '/views/books/business.php';
    }

    // =========================================================================
    // PRIVATE: fetch a book and make sure it belongs to the logged-in user
    // =========================================================================
    private function getBookOrFail(string $id): array
    {
        $book = Database::row(
            'SELECT * FROM books WHERE id = ? AND user_id = ? AND deleted_at IS NULL',
            [$id, auth()['id']]
        );

        if (!$book) {
            http_response_code(404);
            require BASE_PATH . '/views/errors/404.php';
            exit;
        }

        return $book;
    }
}

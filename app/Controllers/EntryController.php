<?php
// =============================================================================
// app/Controllers/EntryController.php
// =============================================================================

namespace App\Controllers;

use App\Helpers\Database;

class EntryController
{
    // =========================================================================
    // ADD ENTRY  →  POST /books/{id}/entries/add
    // =========================================================================
    public function store(array $params): void
    {
        if (guest()) redirect('/login');
        csrf_verify();

        $book = $this->getBookOrFail($params['id']);

        $type       = $_POST['type'] ?? 'in';
        $title      = trim($_POST['title'] ?? '');
        $amount     = (float)($_POST['amount'] ?? 0);
        $date       = $_POST['date'] ?? date('Y-m-d');
        $time       = $_POST['time'] ?? null;
        $contactId  = !empty($_POST['contact_id']) ? (int)$_POST['contact_id'] : null;
        $desc       = trim($_POST['description'] ?? '');

        // Validation
        if (!$title) {
            redirect('/books/' . $book['id'], ['error' => 'Please enter a title for the entry.']);
        }
        if ($amount <= 0) {
            redirect('/books/' . $book['id'], ['error' => 'Amount must be greater than zero.']);
        }
        if (!in_array($type, ['in', 'out'])) {
            redirect('/books/' . $book['id'], ['error' => 'Invalid entry type.']);
        }

        // Handle file attachment
        $attachments = [];
        if (!empty($_FILES['attachment']['name'])) {
            $uploaded = $this->handleUpload($_FILES['attachment']);
            if ($uploaded) {
                $attachments[] = $uploaded;
            }
        }

        Database::run(
            'INSERT INTO entries (book_id, contact_id, type, title, description, amount, entry_date, entry_time, attachments, created_by, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $book['id'],
                $contactId,
                $type,
                $title,
                $desc ?: null,
                $amount,
                $date,
                $time ?: null,
                $attachments ? json_encode($attachments) : null,
                auth()['id'],
                now()
            ]
        );

        redirect('/books/' . $book['id'], ['success' => 'Entry added.']);
    }

    // =========================================================================
    // DELETE ENTRY  →  POST /books/{id}/entries/{entry_id}/delete
    // =========================================================================
    public function delete(array $params): void
    {
        if (guest()) redirect('/login');
        csrf_verify();

        $book = $this->getBookOrFail($params['id']);

        // Make sure entry belongs to this book
        $entry = Database::row(
            'SELECT * FROM entries WHERE id = ? AND book_id = ? AND deleted_at IS NULL',
            [$params['entry_id'], $book['id']]
        );

        if (!$entry) {
            redirect('/books/' . $book['id'], ['error' => 'Entry not found.']);
        }

        Database::run(
            'UPDATE entries SET deleted_at = ? WHERE id = ?',
            [now(), $entry['id']]
        );

        redirect('/books/' . $book['id'], ['success' => 'Entry deleted.']);
    }

    // =========================================================================
    // PRIVATE: upload handler
    // =========================================================================
    private function handleUpload(array $file): ?string
    {
        $allowed  = config('upload.allowed');
        $maxSize  = config('upload.max_size');
        $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed))  return null;
        if ($file['size'] > $maxSize)   return null;
        if ($file['error'] !== 0)       return null;

        $uploadPath = config('upload.path') . '/attachments';
        if (!is_dir($uploadPath)) mkdir($uploadPath, 0755, true);

        $filename = date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $dest     = $uploadPath . '/' . $filename;

        if (move_uploaded_file($file['tmp_name'], $dest)) {
            return 'attachments/' . $filename;
        }

        return null;
    }

    // =========================================================================
    // PRIVATE: verify book ownership
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

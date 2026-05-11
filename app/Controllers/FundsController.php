<?php
namespace App\Controllers;
use App\Helpers\Database;

class FundsController
{
    public function index(array $params): void
    {
        if (guest()) redirect('/login');
        $book = $this->getBookOrFail($params['id']);

        $transactions = Database::query(
            "SELECT f.*,
                    f.fund_date AS date,
                    f.title     AS source
             FROM funds f
             WHERE f.book_id=?
             ORDER BY f.fund_date DESC, f.id DESC",
            [$book['id']]
        );

        $totals = Database::row(
            "SELECT
                COALESCE(SUM(CASE WHEN type='in'  THEN amount ELSE 0 END), 0) AS total_added,
                COALESCE(SUM(CASE WHEN type='out' THEN amount ELSE 0 END), 0) AS total_withdrawn
             FROM funds WHERE book_id=?",
            [$book['id']]
        );

        require BASE_PATH . '/views/business/funds/index.php';
    }

    public function store(array $params): void
    {
        if (guest()) redirect('/login');
        csrf_verify();
        $book = $this->getBookOrFail($params['id']);

        $type   = (($_POST['type'] ?? 'add') === 'withdraw') ? 'out' : 'in';
        $amount = (float)($_POST['amount'] ?? 0);
        $source = trim($_POST['source'] ?? '');
        $date   = $_POST['date'] ?? date('Y-m-d');
        $note   = trim($_POST['note'] ?? '');

        if ($amount <= 0) {
            redirect('/books/'.$book['id'].'/funds', ['error' => 'Amount must be greater than zero.']);
        }
        if (!$source) {
            redirect('/books/'.$book['id'].'/funds', ['error' => 'Please enter a source / reason.']);
        }

        Database::run(
            'INSERT INTO funds (book_id, type, title, amount, fund_date, note, created_by, created_at)
             VALUES (?,?,?,?,?,?,?,?)',
            [$book['id'], $type, $source, $amount, $date, $note ?: null, auth()['id'], now()]
        );

        $label = $type === 'in' ? 'Funds added.' : 'Withdrawal recorded.';
        redirect('/books/'.$book['id'].'/funds', ['success' => $label]);
    }

    public function delete(array $params): void
    {
        if (guest()) redirect('/login');
        csrf_verify();
        $book = $this->getBookOrFail($params['id']);

        Database::run(
            'DELETE FROM funds WHERE id=? AND book_id=?',
            [$params['fund_id'], $book['id']]
        );

        redirect('/books/'.$book['id'].'/funds', ['success' => 'Transaction deleted.']);
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
}
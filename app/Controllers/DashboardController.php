<?php
namespace App\Controllers;
use App\Helpers\Database;

class DashboardController
{
    public function index(): void
    {
        if (guest()) redirect('/login');
        $userId = auth()['id'];
        $books = Database::query(
            'SELECT b.*,
                COALESCE(SUM(CASE WHEN e.type="in"  THEN e.amount ELSE 0 END), 0) AS total_in,
                COALESCE(SUM(CASE WHEN e.type="out" THEN e.amount ELSE 0 END), 0) AS total_out,
                COUNT(DISTINCT e.id) AS entry_count
             FROM books b
             LEFT JOIN entries e ON e.book_id = b.id AND e.deleted_at IS NULL
             WHERE b.user_id = ? AND b.deleted_at IS NULL
             GROUP BY b.id
             ORDER BY b.created_at DESC',
            [$userId]
        );
        require BASE_PATH . '/views/dashboard/index.php';
    }
}

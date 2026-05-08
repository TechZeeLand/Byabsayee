<?php
namespace App\Controllers;
use App\Helpers\Database;

class DashboardController
{
    public function index(): void
    {
        if (guest()) redirect('/login');

        $userId = auth()['id'];

        // For personal books: total_in/out = entries
        // For business books: total_in = paid sales, total_out = paid purchases
        $books = Database::query(
            'SELECT b.*,
                CASE
                    WHEN b.type = "personal" THEN
                        COALESCE((SELECT SUM(e.amount) FROM entries e WHERE e.book_id=b.id AND e.type="in"  AND e.deleted_at IS NULL),0)
                    ELSE
                        COALESCE((SELECT SUM(i.total) FROM invoices i WHERE i.book_id=b.id AND i.type="sale"     AND i.status="paid" AND i.deleted_at IS NULL),0)
                END AS total_in,
                CASE
                    WHEN b.type = "personal" THEN
                        COALESCE((SELECT SUM(e.amount) FROM entries e WHERE e.book_id=b.id AND e.type="out" AND e.deleted_at IS NULL),0)
                    ELSE
                        COALESCE((SELECT SUM(i.total) FROM invoices i WHERE i.book_id=b.id AND i.type="purchase" AND i.status="paid" AND i.deleted_at IS NULL),0)
                END AS total_out
             FROM books b
             WHERE b.user_id=? AND b.deleted_at IS NULL
             ORDER BY b.created_at DESC',
            [$userId]
        );

        require BASE_PATH . '/views/dashboard/index.php';
    }
}

<?php
namespace App\Controllers;
use App\Helpers\Database;

class CouponController
{
    public function index(array $params): void
    {
        if (guest()) redirect('/login');
        $book = $this->getBookOrFail($params['id']);

        $filter = $_GET['filter'] ?? 'all';
        $search = trim($_GET['q'] ?? '');

        $where = ['book_id=?'];
        $bind  = [$book['id']];

        if ($filter === 'active')   { $where[] = 'is_active=1'; }
        if ($filter === 'inactive') { $where[] = 'is_active=0'; }
        if ($search !== '') {
            $where[] = '(name LIKE ? OR code LIKE ?)';
            $bind[]  = "%{$search}%";
            $bind[]  = "%{$search}%";
        }

        $whereSQL = implode(' AND ', $where);

        $coupons = Database::query(
            "SELECT * FROM coupons WHERE {$whereSQL} ORDER BY is_active DESC, created_at DESC",
            $bind
        );

        $counts = Database::row(
            'SELECT COUNT(*) AS total,
                    SUM(is_active=1) AS active_count,
                    SUM(is_active=0) AS inactive_count
             FROM coupons WHERE book_id=?',
            [$book['id']]
        );

        require BASE_PATH . '/views/business/coupons/index.php';
    }

    public function store(array $params): void
    {
        if (guest()) redirect('/login');
        csrf_verify();
        $book = $this->getBookOrFail($params['id']);

        $name   = trim($_POST['name']   ?? '');
        $code   = strtoupper(trim($_POST['code'] ?? ''));
        $type   = ($_POST['discount_type'] ?? 'fixed') === 'percent' ? 'percent' : 'fixed';
        $value  = (float)($_POST['discount_value'] ?? 0);
        $note   = trim($_POST['note']   ?? '');

        if (!$name || !$code || $value <= 0) {
            redirect('/books/'.$book['id'].'/coupons', ['error' => 'Name, code, and discount value are required.']);
        }

        if ($type === 'percent' && $value > 100) {
            redirect('/books/'.$book['id'].'/coupons', ['error' => 'Percentage discount cannot exceed 100%.']);
        }

        if (!preg_match('/^[A-Z0-9\-_]{2,30}$/', $code)) {
            redirect('/books/'.$book['id'].'/coupons', ['error' => 'Code must be 2–30 characters: letters, numbers, hyphens, underscores only.']);
        }

        $exists = Database::row(
            'SELECT id FROM coupons WHERE book_id=? AND code=?',
            [$book['id'], $code]
        );

        if ($exists) {
            redirect('/books/'.$book['id'].'/coupons', ['error' => "Coupon code \"{$code}\" already exists."]); 
        }

        Database::run(
            'INSERT INTO coupons (book_id, name, code, discount_type, discount_value, note, is_active, created_by, created_at)
             VALUES (?,?,?,?,?,?,1,?,?)',
            [$book['id'], $name, $code, $type, $value, $note ?: null, auth()['id'], now()]
        );

        redirect('/books/'.$book['id'].'/coupons', ['success' => "Coupon \"{$code}\" created."]);
    }

    public function update(array $params): void
    {
        if (guest()) redirect('/login');
        csrf_verify();
        $book   = $this->getBookOrFail($params['id']);
        $coupon = $this->getCouponOrFail($params['coupon_id'], $book['id']);

        $name   = trim($_POST['name']   ?? '');
        $code   = strtoupper(trim($_POST['code'] ?? ''));
        $type   = ($_POST['discount_type'] ?? 'fixed') === 'percent' ? 'percent' : 'fixed';
        $value  = (float)($_POST['discount_value'] ?? 0);
        $note   = trim($_POST['note']   ?? '');

        if (!$name || !$code || $value <= 0) {
            redirect('/books/'.$book['id'].'/coupons', ['error' => 'Name, code, and discount value are required.']);
        }

        if ($type === 'percent' && $value > 100) {
            redirect('/books/'.$book['id'].'/coupons', ['error' => 'Percentage discount cannot exceed 100%.']);
        }

        if (!preg_match('/^[A-Z0-9\-_]{2,30}$/', $code)) {
            redirect('/books/'.$book['id'].'/coupons', ['error' => 'Code must be 2–30 characters: letters, numbers, hyphens, underscores only.']);
        }

        // Check code uniqueness (excluding self)
        $conflict = Database::row(
            'SELECT id FROM coupons WHERE book_id=? AND code=? AND id!=?',
            [$book['id'], $code, $coupon['id']]
        );
        if ($conflict) {
            redirect('/books/'.$book['id'].'/coupons', ['error' => "Coupon code \"{$code}\" is already used by another coupon."]);
        }

        Database::run(
            'UPDATE coupons SET name=?, code=?, discount_type=?, discount_value=?, note=?, updated_at=? WHERE id=? AND book_id=?',
            [$name, $code, $type, $value, $note ?: null, now(), $coupon['id'], $book['id']]
        );

        redirect('/books/'.$book['id'].'/coupons', ['success' => 'Coupon updated.']);
    }

    public function toggle(array $params): void
    {
        if (guest()) redirect('/login');
        csrf_verify();
        $book   = $this->getBookOrFail($params['id']);
        $coupon = $this->getCouponOrFail($params['coupon_id'], $book['id']);

        $newState = $coupon['is_active'] ? 0 : 1;
        Database::run(
            'UPDATE coupons SET is_active=?, updated_at=? WHERE id=? AND book_id=?',
            [$newState, now(), $coupon['id'], $book['id']]
        );

        $msg = $newState ? "Coupon \"{$coupon['code']}\" activated." : "Coupon \"{$coupon['code']}\" deactivated.";
        redirect('/books/'.$book['id'].'/coupons', ['success' => $msg]);
    }

    public function delete(array $params): void
    {
        if (guest()) redirect('/login');
        csrf_verify();
        $book = $this->getBookOrFail($params['id']);

        Database::run('DELETE FROM coupons WHERE id=? AND book_id=?', [$params['coupon_id'], $book['id']]);
        redirect('/books/'.$book['id'].'/coupons', ['success' => 'Coupon deleted.']);
    }

    // Called from invoice controller to validate and apply a coupon
    public static function validate(int $bookId, string $code, float $subtotal): ?array
    {
        $coupon = Database::row(
            'SELECT * FROM coupons WHERE book_id=? AND code=? AND is_active=1',
            [$bookId, strtoupper($code)]
        );
        if (!$coupon) return null;

        $discount = $coupon['discount_type'] === 'percent'
            ? round($subtotal * $coupon['discount_value'] / 100, 2)
            : min((float)$coupon['discount_value'], $subtotal);

        return [
            'coupon'   => $coupon,
            'discount' => $discount,
        ];
    }

    private function getCouponOrFail(string $couponId, int $bookId): array
    {
        $c = Database::row('SELECT * FROM coupons WHERE id=? AND book_id=?', [$couponId, $bookId]);
        if (!$c) { http_response_code(404); require BASE_PATH.'/views/errors/404.php'; exit; }
        return $c;
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
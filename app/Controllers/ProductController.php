<?php
namespace App\Controllers;
use App\Helpers\Database;

class ProductController
{
    public function index(array $params): void
    {
        if (guest()) redirect('/login');
        $book   = $this->getBookOrFail($params['id']);
        $search = trim($_GET['q'] ?? '');
        $filter = $_GET['filter'] ?? 'all';

        $sql = 'SELECT p.*, c.name AS category_name
                FROM products p
                LEFT JOIN categories c ON c.id=p.category_id
                WHERE p.book_id=? AND p.deleted_at IS NULL';
        $p = [$book['id']];

        if ($search) {
            $sql .= ' AND (p.name LIKE ? OR p.sku LIKE ? OR p.barcode LIKE ?)';
            $like = '%'.$search.'%';
            $p    = array_merge($p, [$like, $like, $like]);
        }
        if ($filter === 'low')  $sql .= ' AND p.stock_qty <= p.low_stock_alert AND p.stock_qty > 0';
        if ($filter === 'out')  $sql .= ' AND p.stock_qty <= 0';

        $sql .= ' ORDER BY p.name';
        $products = Database::query($sql, $p);

        $categories = Database::query('SELECT * FROM categories WHERE book_id=? ORDER BY name', [$book['id']]);

        $summary = Database::row(
            'SELECT COUNT(*) AS total_products,
                    COALESCE(SUM(stock_qty * buy_price),0) AS stock_value,
                    SUM(CASE WHEN stock_qty <= 0 THEN 1 ELSE 0 END) AS out_of_stock,
                    SUM(CASE WHEN stock_qty <= low_stock_alert AND stock_qty > 0 THEN 1 ELSE 0 END) AS low_stock
             FROM products WHERE book_id=? AND deleted_at IS NULL',
            [$book['id']]
        );

        require BASE_PATH . '/views/business/products/index.php';
    }

    public function store(array $params): void
    {
        if (guest()) redirect('/login');
        csrf_verify();
        $book = $this->getBookOrFail($params['id']);

        $name = trim($_POST['name'] ?? '');
        if (!$name) redirect('/books/'.$book['id'].'/products', ['error' => 'Product name is required.']);

        $image = null;
        if (!empty($_FILES['image']['name'])) $image = $this->handleImageUpload($_FILES['image']);

        Database::run(
            'INSERT INTO products
                (book_id,category_id,name,sku,barcode,unit,buy_price,sell_price,
                 stock_qty,low_stock_alert,description,image,created_at)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)',
            [
                $book['id'],
                !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null,
                $name,
                trim($_POST['sku']      ?? '') ?: null,
                trim($_POST['barcode']  ?? '') ?: null,
                trim($_POST['unit']     ?? 'pcs'),
                (float)($_POST['buy_price']      ?? 0),
                (float)($_POST['sell_price']     ?? 0),
                (float)($_POST['stock_qty']      ?? 0),
                (float)($_POST['low_stock_alert']?? 5),
                trim($_POST['description'] ?? '') ?: null,
                $image,
                now()
            ]
        );

        redirect('/books/'.$book['id'].'/products', ['success' => '"'.$name.'" added to stock.']);
    }

    public function update(array $params): void
    {
        if (guest()) redirect('/login');
        csrf_verify();
        $book    = $this->getBookOrFail($params['id']);
        $product = $this->getProductOrFail($params['product_id'], $book['id']);

        $name = trim($_POST['name'] ?? '');
        if (!$name) redirect('/books/'.$book['id'].'/products', ['error' => 'Product name is required.']);

        $image = $product['image'];
        if (!empty($_FILES['image']['name'])) {
            $new = $this->handleImageUpload($_FILES['image']);
            if ($new) $image = $new;
        }

        Database::run(
            'UPDATE products SET category_id=?,name=?,sku=?,barcode=?,unit=?,
             buy_price=?,sell_price=?,low_stock_alert=?,description=?,image=? WHERE id=?',
            [
                !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null,
                $name,
                trim($_POST['sku']     ?? '') ?: null,
                trim($_POST['barcode'] ?? '') ?: null,
                trim($_POST['unit']    ?? 'pcs'),
                (float)($_POST['buy_price']       ?? 0),
                (float)($_POST['sell_price']      ?? 0),
                (float)($_POST['low_stock_alert'] ?? 5),
                trim($_POST['description'] ?? '') ?: null,
                $image,
                $product['id']
            ]
        );

        redirect('/books/'.$book['id'].'/products', ['success' => '"'.$name.'" updated.']);
    }

    public function adjustStock(array $params): void
    {
        if (guest()) redirect('/login');
        csrf_verify();
        $book    = $this->getBookOrFail($params['id']);
        $product = $this->getProductOrFail($params['product_id'], $book['id']);

        $type = $_POST['adjust_type'] ?? 'add';
        $qty  = abs((float)($_POST['qty'] ?? 0));
        $note = trim($_POST['note'] ?? '');

        if ($qty <= 0) redirect('/books/'.$book['id'].'/products', ['error' => 'Quantity must be greater than zero.']);

        $newQty = $type === 'add'
            ? $product['stock_qty'] + $qty
            : max(0, $product['stock_qty'] - $qty);

        Database::run('UPDATE products SET stock_qty=? WHERE id=?', [$newQty, $product['id']]);

        Database::run(
            'INSERT INTO stock_adjustments (product_id,type,qty,note,created_by,created_at) VALUES (?,?,?,?,?,?)',
            [$product['id'], $type, $qty, $note ?: null, auth()['id'], now()]
        );

        redirect('/books/'.$book['id'].'/products', [
            'success' => 'Stock updated: "'.$product['name'].'". New qty: '.$newQty
        ]);
    }

    public function delete(array $params): void
    {
        if (guest()) redirect('/login');
        csrf_verify();
        $book    = $this->getBookOrFail($params['id']);
        $product = $this->getProductOrFail($params['product_id'], $book['id']);

        Database::run('UPDATE products SET deleted_at=? WHERE id=?', [now(), $product['id']]);
        redirect('/books/'.$book['id'].'/products', ['success' => '"'.$product['name'].'" deleted.']);
    }

    private function handleImageUpload(array $file): ?string
    {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg','jpeg','png','webp']) || $file['error'] !== 0 || $file['size'] > 5*1024*1024) return null;
        $dir = config('upload.path').'/products';
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        $filename = date('Ymd_His').'_'.bin2hex(random_bytes(4)).'.'.$ext;
        return move_uploaded_file($file['tmp_name'], $dir.'/'.$filename) ? 'products/'.$filename : null;
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

    private function getProductOrFail(string $pid, int $bookId): array
    {
        $p = Database::row('SELECT * FROM products WHERE id=? AND book_id=? AND deleted_at IS NULL', [$pid, $bookId]);
        if (!$p) { http_response_code(404); require BASE_PATH.'/views/errors/404.php'; exit; }
        return $p;
    }
}

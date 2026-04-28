<?php
namespace App\Controllers;
use App\Helpers\Database;

class BookController
{
    public function index(): void
    {
        if (guest()) redirect('/login');
        $books = Database::query(
            'SELECT b.*,
                COALESCE(SUM(CASE WHEN e.type="in"  THEN e.amount ELSE 0 END),0) AS total_in,
                COALESCE(SUM(CASE WHEN e.type="out" THEN e.amount ELSE 0 END),0) AS total_out,
                COUNT(DISTINCT e.id) AS entry_count
             FROM books b
             LEFT JOIN entries e ON e.book_id=b.id AND e.deleted_at IS NULL
             WHERE b.user_id=? AND b.deleted_at IS NULL
             GROUP BY b.id ORDER BY b.created_at DESC',
            [auth()['id']]
        );
        require BASE_PATH . '/views/books/index.php';
    }

    public function create(): void
    {
        if (guest()) redirect('/login');
        require BASE_PATH . '/views/books/create.php';
    }

    public function store(): void
    {
        if (guest()) redirect('/login');
        csrf_verify();

        $name       = trim($_POST['name']           ?? '');
        $type       = $_POST['type']                ?? 'personal';
        $color      = $_POST['color']               ?? '#1a6b4a';
        $themeColor = $_POST['theme_color']         ?? '#1a6b4a';
        $email      = trim($_POST['email']          ?? '');
        $phone      = trim($_POST['phone']          ?? '');
        $address    = trim($_POST['address']        ?? '');

        if (!$name) {
            set_old(['name'=>$name,'type'=>$type]);
            redirect('/books/create', ['error' => 'Please enter a book name.']);
        }
        if (!in_array($type, ['personal','business'])) redirect('/books/create', ['error' => 'Invalid type.']);
        if (!preg_match('/^#[0-9a-fA-F]{6}$/', $color))      $color      = '#1a6b4a';
        if (!preg_match('/^#[0-9a-fA-F]{6}$/', $themeColor)) $themeColor = '#1a6b4a';

        $logo = null;
        if (!empty($_FILES['logo']['name'])) $logo = $this->handleLogoUpload($_FILES['logo']);

        Database::run(
            'INSERT INTO books (user_id,name,type,color,theme_color,logo,email,phone,address,created_at)
             VALUES (?,?,?,?,?,?,?,?,?,?)',
            [auth()['id'], $name, $type, $color, $themeColor,
             $logo, $email ?: null, $phone ?: null, $address ?: null, now()]
        );
        $bookId = Database::lastId();

        if ($type === 'business') {
            $businessName  = trim($_POST['business_name']  ?? $name);
            $invoicePrefix = strtoupper(trim($_POST['invoice_prefix'] ?? 'INV')) ?: 'INV';

            Database::run(
                'INSERT INTO book_business_details (book_id,business_name,phone,address,invoice_prefix,invoice_counter)
                 VALUES (?,?,?,?,?,1)',
                [$bookId, $businessName, $phone ?: null, $address ?: null, $invoicePrefix]
            );

            // Seed default delivery + payment methods
            foreach (['Home Delivery','Store Pickup','Courier','Express Delivery'] as $i => $m)
                Database::run('INSERT INTO invoice_method_options (book_id,type,label,sort_order) VALUES (?,?,?,?)',
                    [$bookId,'delivery',$m,$i]);

            foreach (['Cash','Cash on Delivery','bKash','Nagad','Rocket','Card','Bank Transfer','Cheque','Credit'] as $i => $m)
                Database::run('INSERT INTO invoice_method_options (book_id,type,label,sort_order) VALUES (?,?,?,?)',
                    [$bookId,'payment',$m,$i]);
        }

        redirect('/books/'.$bookId, ['success' => 'Book created!']);
    }

    public function show(array $params): void
    {
        if (guest()) redirect('/login');
        $book = $this->getBookOrFail($params['id']);
        $book['type'] === 'personal' ? $this->showPersonal($book) : $this->showBusiness($book);
    }

    public function edit(array $params): void
    {
        if (guest()) redirect('/login');
        $book    = $this->getBookOrFail($params['id']);
        $details = $book['type'] === 'business'
            ? Database::row('SELECT * FROM book_business_details WHERE book_id=?', [$book['id']])
            : null;
        $deliveryMethods = $book['type'] === 'business'
            ? Database::query('SELECT * FROM invoice_method_options WHERE book_id=? AND type="delivery" ORDER BY sort_order', [$book['id']])
            : [];
        $paymentMethods = $book['type'] === 'business'
            ? Database::query('SELECT * FROM invoice_method_options WHERE book_id=? AND type="payment" ORDER BY sort_order', [$book['id']])
            : [];
        require BASE_PATH . '/views/books/edit.php';
    }

    public function update(array $params): void
    {
        if (guest()) redirect('/login');
        csrf_verify();
        $book       = $this->getBookOrFail($params['id']);
        $name       = trim($_POST['name']       ?? '');
        $color      = $_POST['color']            ?? $book['color'];
        $themeColor = $_POST['theme_color']      ?? ($book['theme_color'] ?? '#1a6b4a');
        $email      = trim($_POST['email']       ?? '');
        $phone      = trim($_POST['phone']       ?? '');
        $address    = trim($_POST['address']     ?? '');

        if (!$name) redirect('/books/'.$book['id'].'/edit', ['error' => 'Name is required.']);
        if (!preg_match('/^#[0-9a-fA-F]{6}$/', $color))      $color      = $book['color'];
        if (!preg_match('/^#[0-9a-fA-F]{6}$/', $themeColor)) $themeColor = $book['theme_color'] ?? '#1a6b4a';

        $logo = $book['logo'];
        if (!empty($_FILES['logo']['name'])) {
            $new = $this->handleLogoUpload($_FILES['logo']);
            if ($new) $logo = $new;
        }

        Database::run(
            'UPDATE books SET name=?,color=?,theme_color=?,logo=?,email=?,phone=?,address=? WHERE id=?',
            [$name, $color, $themeColor, $logo, $email ?: null, $phone ?: null, $address ?: null, $book['id']]
        );

        if ($book['type'] === 'business') {
            $businessName  = trim($_POST['business_name']  ?? $name);
            $invoicePrefix = strtoupper(trim($_POST['invoice_prefix'] ?? 'INV')) ?: 'INV';
            Database::run(
                'UPDATE book_business_details SET business_name=?,phone=?,address=?,invoice_prefix=? WHERE book_id=?',
                [$businessName, $phone ?: null, $address ?: null, $invoicePrefix, $book['id']]
            );

            // Save custom methods if posted
            if (!empty($_POST['delivery_methods'])) {
                Database::run('DELETE FROM invoice_method_options WHERE book_id=? AND type="delivery"', [$book['id']]);
                foreach (array_filter(array_map('trim', $_POST['delivery_methods'])) as $i => $m)
                    Database::run('INSERT INTO invoice_method_options (book_id,type,label,sort_order) VALUES (?,?,?,?)',
                        [$book['id'],'delivery',$m,$i]);
            }
            if (!empty($_POST['payment_methods'])) {
                Database::run('DELETE FROM invoice_method_options WHERE book_id=? AND type="payment"', [$book['id']]);
                foreach (array_filter(array_map('trim', $_POST['payment_methods'])) as $i => $m)
                    Database::run('INSERT INTO invoice_method_options (book_id,type,label,sort_order) VALUES (?,?,?,?)',
                        [$book['id'],'payment',$m,$i]);
            }
        }

        redirect('/books/'.$book['id'], ['success' => 'Book updated.']);
    }

    public function delete(array $params): void
    {
        if (guest()) redirect('/login');
        csrf_verify();
        $book = $this->getBookOrFail($params['id']);
        Database::run('UPDATE books SET deleted_at=? WHERE id=?', [now(), $book['id']]);
        redirect('/books', ['success' => '"'.$book['name'].'" deleted.']);
    }

    private function showPersonal(array $book): void
    {
        $totals = Database::row(
            'SELECT COALESCE(SUM(CASE WHEN type="in"  THEN amount ELSE 0 END),0) AS total_in,
                    COALESCE(SUM(CASE WHEN type="out" THEN amount ELSE 0 END),0) AS total_out
             FROM entries WHERE book_id=? AND deleted_at IS NULL', [$book['id']]
        );
        $entries  = Database::query(
            'SELECT e.*, c.name AS contact_name FROM entries e
             LEFT JOIN contacts c ON e.contact_id=c.id
             WHERE e.book_id=? AND e.deleted_at IS NULL
             ORDER BY e.entry_date DESC, e.created_at DESC LIMIT 50',
            [$book['id']]
        );
        $contacts = Database::query(
            'SELECT id,name FROM contacts WHERE book_id=? AND deleted_at IS NULL ORDER BY name',
            [$book['id']]
        );
        require BASE_PATH . '/views/books/personal.php';
    }

    private function showBusiness(array $book): void
    {
        $details = Database::row('SELECT * FROM book_business_details WHERE book_id=?', [$book['id']]);
        $stats   = Database::row(
            'SELECT
                (SELECT COUNT(*) FROM customers WHERE book_id=? AND deleted_at IS NULL) AS customers,
                (SELECT COUNT(*) FROM suppliers WHERE book_id=? AND deleted_at IS NULL) AS suppliers,
                (SELECT COUNT(*) FROM products  WHERE book_id=? AND deleted_at IS NULL) AS products,
                (SELECT COUNT(*) FROM invoices  WHERE book_id=? AND deleted_at IS NULL) AS invoices,
                (SELECT COALESCE(SUM(total),0) FROM invoices WHERE book_id=? AND type="sale"     AND status="paid" AND deleted_at IS NULL) AS total_sales,
                (SELECT COALESCE(SUM(total),0) FROM invoices WHERE book_id=? AND type="purchase" AND status="paid" AND deleted_at IS NULL) AS total_purchases',
            array_fill(0, 6, $book['id'])
        );
        require BASE_PATH . '/views/books/business.php';
    }

    private function handleLogoUpload(array $file): ?string
    {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext,['jpg','jpeg','png','webp','svg']) || $file['error']!==0 || $file['size']>2*1024*1024) return null;
        $dir = config('upload.path').'/logos';
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        $filename = 'logo_'.date('Ymd_His').'_'.bin2hex(random_bytes(4)).'.'.$ext;
        return move_uploaded_file($file['tmp_name'], $dir.'/'.$filename) ? 'logos/'.$filename : null;
    }

    private function getBookOrFail(string $id): array
    {
        $book = Database::row(
            'SELECT * FROM books WHERE id=? AND user_id=? AND deleted_at IS NULL',
            [$id, auth()['id']]
        );
        if (!$book) { http_response_code(404); require BASE_PATH.'/views/errors/404.php'; exit; }
        return $book;
    }
}

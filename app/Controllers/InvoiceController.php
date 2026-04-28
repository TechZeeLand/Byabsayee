<?php
namespace App\Controllers;
use App\Helpers\Database;

class InvoiceController
{
    public function index(array $params): void
    {
        if (guest()) redirect('/login');
        $book   = $this->getBookOrFail($params['id']);
        $type   = $_GET['type']   ?? 'all';
        $status = $_GET['status'] ?? 'all';

        $sql = 'SELECT i.*, c.name AS customer_name, s.name AS supplier_name
                FROM invoices i
                LEFT JOIN customers c ON i.customer_id=c.id
                LEFT JOIN suppliers s ON i.supplier_id=s.id
                WHERE i.book_id=? AND i.deleted_at IS NULL';
        $p = [$book['id']];
        if ($type   !== 'all') { $sql .= ' AND i.type=?';   $p[] = $type;   }
        if ($status !== 'all') { $sql .= ' AND i.status=?'; $p[] = $status; }
        $sql .= ' ORDER BY i.date DESC, i.id DESC';
        $invoices = Database::query($sql, $p);

        $summary = Database::row(
            'SELECT
                COALESCE(SUM(CASE WHEN type="sale" THEN total ELSE 0 END),0) AS total_sales,
                COALESCE(SUM(CASE WHEN type="sale" THEN paid  ELSE 0 END),0) AS collected,
                COALESCE(SUM(CASE WHEN type="sale" AND status NOT IN ("paid","cancelled") THEN (total-paid) ELSE 0 END),0) AS outstanding,
                COALESCE(SUM(CASE WHEN type="purchase" THEN total ELSE 0 END),0) AS total_purchases
             FROM invoices WHERE book_id=? AND deleted_at IS NULL',
            [$book['id']]
        );

        require BASE_PATH . '/views/business/invoices/index.php';
    }

    public function create(array $params): void
    {
        if (guest()) redirect('/login');
        $book = $this->getBookOrFail($params['id']);
        $type = $_GET['type'] ?? 'sale';

        $customers       = Database::query('SELECT id,name,phone,points FROM customers WHERE book_id=? AND deleted_at IS NULL ORDER BY name', [$book['id']]);
        $suppliers       = Database::query('SELECT id,name,company FROM suppliers WHERE book_id=? AND deleted_at IS NULL ORDER BY name', [$book['id']]);
        $products        = Database::query('SELECT id,name,sell_price,buy_price,stock_qty,unit FROM products WHERE book_id=? AND deleted_at IS NULL ORDER BY name', [$book['id']]);
        $details         = Database::row('SELECT * FROM book_business_details WHERE book_id=?', [$book['id']]);
        $deliveryMethods = Database::query('SELECT * FROM invoice_method_options WHERE book_id=? AND type="delivery" ORDER BY sort_order', [$book['id']]);
        $paymentMethods  = Database::query('SELECT * FROM invoice_method_options WHERE book_id=? AND type="payment"  ORDER BY sort_order', [$book['id']]);

        $prefix    = $details['invoice_prefix'] ?? 'INV';
        $counter   = $details['invoice_counter'] ?? 1;
        $invoiceNo = $prefix . '-' . str_pad($counter, 4, '0', STR_PAD_LEFT);

        require BASE_PATH . '/views/business/invoices/create.php';
    }

    public function store(array $params): void
    {
        if (guest()) redirect('/login');
        csrf_verify();
        $book = $this->getBookOrFail($params['id']);

        $type           = $_POST['type']            ?? 'sale';
        $customerId     = !empty($_POST['customer_id'])  ? (int)$_POST['customer_id']  : null;
        $supplierId     = !empty($_POST['supplier_id'])  ? (int)$_POST['supplier_id']  : null;
        $invoiceNo      = trim($_POST['invoice_no']      ?? '');
        $date           = $_POST['date']            ?? date('Y-m-d');
        $dueDate        = $_POST['due_date']         ?? null;
        $notes          = trim($_POST['notes']       ?? '');
        $discount       = (float)($_POST['discount']        ?? 0);
        $pointsDiscount = (float)($_POST['points_discount'] ?? 0);
        $deliveryCharge = (float)($_POST['delivery_charge'] ?? 0);
        $tax            = (float)($_POST['tax']             ?? 0);
        $deliveryMethod = trim($_POST['delivery_method']    ?? '');
        $paymentMethod  = trim($_POST['payment_method']     ?? '');

        $itemNames    = $_POST['item_name']       ?? [];
        $itemQtys     = $_POST['item_qty']        ?? [];
        $itemPrices   = $_POST['item_price']      ?? [];
        $itemDiscs    = $_POST['item_discount']   ?? [];
        $itemPids     = $_POST['item_product_id'] ?? [];
        $itemVariants = $_POST['item_variant']    ?? [];

        if (!array_filter($itemNames)) {
            redirect('/books/'.$book['id'].'/invoices/create?type='.$type, ['error' => 'Add at least one item.']);
        }

        $subtotal = 0;
        $items    = [];
        foreach ($itemNames as $i => $itemName) {
            $itemName = trim($itemName);
            if (!$itemName) continue;
            $qty      = (float)($itemQtys[$i]     ?? 1);
            $price    = (float)($itemPrices[$i]   ?? 0);
            $discPct  = (float)($itemDiscs[$i]    ?? 0);
            $pid      = !empty($itemPids[$i])     ? (int)$itemPids[$i] : null;
            $variant  = trim($itemVariants[$i]    ?? '');
            $lineTot  = $qty * $price * (1 - $discPct / 100);
            $subtotal += $lineTot;
            $items[]  = compact('itemName','qty','price','discPct','lineTot','pid','variant');
        }

        $total = $subtotal - $discount - $pointsDiscount + $deliveryCharge + $tax;

        // Get theme color from book
        $themeColor = $book['theme_color'] ?? '#1a6b4a';

        Database::run(
            'INSERT INTO invoices
                (book_id,type,invoice_no,customer_id,supplier_id,date,due_date,
                 subtotal,discount,points_discount,delivery_charge,tax,total,paid,status,
                 notes,delivery_method,payment_method,theme_color,created_by,created_at)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,0,?,?,?,?,?,?,?)',
            [
                $book['id'], $type, $invoiceNo, $customerId, $supplierId,
                $date, $dueDate ?: null, $subtotal, $discount, $pointsDiscount,
                $deliveryCharge, $tax, $total, 'draft',
                $notes ?: null, $deliveryMethod ?: null, $paymentMethod ?: null,
                $themeColor, auth()['id'], now()
            ]
        );
        $invoiceId = Database::lastId();

        foreach ($items as $item) {
            Database::run(
                'INSERT INTO invoice_items (invoice_id,product_id,description,variant,qty,unit_price,discount_pct,line_total)
                 VALUES (?,?,?,?,?,?,?,?)',
                [$invoiceId, $item['pid'], $item['itemName'], $item['variant'] ?: null,
                 $item['qty'], $item['price'], $item['discPct'], $item['lineTot']]
            );
            if ($type === 'sale' && $item['pid']) {
                Database::run('UPDATE products SET stock_qty=stock_qty-? WHERE id=? AND book_id=?',
                    [$item['qty'], $item['pid'], $book['id']]);
            }
            if ($type === 'purchase' && $item['pid']) {
                Database::run('UPDATE products SET stock_qty=stock_qty+? WHERE id=? AND book_id=?',
                    [$item['qty'], $item['pid'], $book['id']]);
            }
        }

        // Deduct points from customer
        if ($customerId && $pointsDiscount > 0) {
            $pointsUsed = (int)$pointsDiscount; // 1 point = 1 taka
            Database::run('UPDATE customers SET points=GREATEST(0,points-?) WHERE id=?',
                [$pointsUsed, $customerId]);
        }

        Database::run('UPDATE book_business_details SET invoice_counter=invoice_counter+1 WHERE book_id=?',
            [$book['id']]);

        redirect('/books/'.$book['id'].'/invoices/'.$invoiceId, ['success' => 'Invoice '.$invoiceNo.' created.']);
    }

    public function show(array $params): void
    {
        if (guest()) redirect('/login');
        $book    = $this->getBookOrFail($params['id']);
        $invoice = $this->getInvoiceOrFail($params['invoice_id'], $book['id']);
        $items    = Database::query('SELECT * FROM invoice_items WHERE invoice_id=?', [$invoice['id']]);
        $customer = $invoice['customer_id'] ? Database::row('SELECT * FROM customers WHERE id=?', [$invoice['customer_id']]) : null;
        $supplier = $invoice['supplier_id'] ? Database::row('SELECT * FROM suppliers WHERE id=?', [$invoice['supplier_id']]) : null;
        $details  = Database::row('SELECT * FROM book_business_details WHERE book_id=?', [$book['id']]);
        require BASE_PATH . '/views/business/invoices/show.php';
    }

    public function pdf(array $params): void
    {
        if (guest()) redirect('/login');
        $book    = $this->getBookOrFail($params['id']);
        $invoice = $this->getInvoiceOrFail($params['invoice_id'], $book['id']);
        $items   = Database::query('SELECT * FROM invoice_items WHERE invoice_id=?', [$invoice['id']]);
        $customer= $invoice['customer_id'] ? Database::row('SELECT * FROM customers WHERE id=?', [$invoice['customer_id']]) : null;
        $supplier= $invoice['supplier_id'] ? Database::row('SELECT * FROM suppliers WHERE id=?', [$invoice['supplier_id']]) : null;
        $details = Database::row('SELECT * FROM book_business_details WHERE book_id=?', [$book['id']]);
        $creator = Database::row('SELECT name FROM users WHERE id=?', [$invoice['created_by'] ?? 0]);

        // Generate PDF using service
        $pdfService = new \App\Services\InvoicePdfService();
        $pdfService->generate($book, $invoice, $items, $customer, $supplier, $details, $creator);
    }

    public function recordPayment(array $params): void
    {
        if (guest()) redirect('/login');
        csrf_verify();
        $book    = $this->getBookOrFail($params['id']);
        $invoice = $this->getInvoiceOrFail($params['invoice_id'], $book['id']);

        $amount = (float)($_POST['amount'] ?? 0);
        $method = trim($_POST['method'] ?? 'cash');
        $note   = trim($_POST['note']   ?? '');

        if ($amount <= 0) redirect('/books/'.$book['id'].'/invoices/'.$invoice['id'], ['error' => 'Amount must be greater than zero.']);

        $due     = $invoice['total'] - $invoice['paid'];
        $amount  = min($amount, $due);
        $newPaid = $invoice['paid'] + $amount;
        $status  = $newPaid >= $invoice['total'] ? 'paid' : 'partial';

        Database::run('UPDATE invoices SET paid=?,status=? WHERE id=?', [$newPaid, $status, $invoice['id']]);
        Database::run('INSERT INTO payments (invoice_id,amount,method,date,note) VALUES (?,?,?,?,?)',
            [$invoice['id'], $amount, $method, date('Y-m-d'), $note ?: null]);

        if ($invoice['customer_id'] && $invoice['type'] === 'sale') {
            $points = (int)($amount / 100);
            if ($points > 0) Database::run('UPDATE customers SET points=points+? WHERE id=?', [$points, $invoice['customer_id']]);
        }

        redirect('/books/'.$book['id'].'/invoices/'.$invoice['id'], ['success' => format_money($amount).' payment recorded.']);
    }

    public function markSent(array $params): void
    {
        if (guest()) redirect('/login');
        csrf_verify();
        $book    = $this->getBookOrFail($params['id']);
        $invoice = $this->getInvoiceOrFail($params['invoice_id'], $book['id']);
        Database::run('UPDATE invoices SET status="sent" WHERE id=? AND status="draft"', [$invoice['id']]);
        redirect('/books/'.$book['id'].'/invoices/'.$invoice['id'], ['success' => 'Invoice marked as sent.']);
    }

    public function delete(array $params): void
    {
        if (guest()) redirect('/login');
        csrf_verify();
        $book    = $this->getBookOrFail($params['id']);
        $invoice = $this->getInvoiceOrFail($params['invoice_id'], $book['id']);
        Database::run('UPDATE invoices SET deleted_at=? WHERE id=?', [now(), $invoice['id']]);
        redirect('/books/'.$book['id'].'/invoices', ['success' => 'Invoice deleted.']);
    }

    private function getBookOrFail(string $id): array
    {
        $book = Database::row('SELECT * FROM books WHERE id=? AND user_id=? AND deleted_at IS NULL AND type="business"', [$id, auth()['id']]);
        if (!$book) { http_response_code(404); require BASE_PATH.'/views/errors/404.php'; exit; }
        return $book;
    }

    private function getInvoiceOrFail(string $iid, int $bookId): array
    {
        $inv = Database::row('SELECT * FROM invoices WHERE id=? AND book_id=? AND deleted_at IS NULL', [$iid, $bookId]);
        if (!$inv) { http_response_code(404); require BASE_PATH.'/views/errors/404.php'; exit; }
        return $inv;
    }
}

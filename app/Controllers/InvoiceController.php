<?php
namespace App\Controllers;
use App\Helpers\Database;

// Forward declarations so static methods work without full autoload paths
// (these classes live in the same namespace)

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
        $details         = Database::row('SELECT * FROM book_business_details WHERE book_id=?', [$book['id']]);
        $deliveryMethods = Database::query('SELECT * FROM invoice_method_options WHERE book_id=? AND type="delivery" ORDER BY sort_order', [$book['id']]);
        $paymentMethods  = Database::query('SELECT * FROM invoice_method_options WHERE book_id=? AND type="payment"  ORDER BY sort_order', [$book['id']]);
        $currencies      = Database::query('SELECT * FROM book_currencies WHERE book_id=? ORDER BY is_default DESC, sort_order', [$book['id']]);

        $inventoryMethod = $details['inventory_method'] ?? 'FIFO';
        $rawProducts = Database::query(
            'SELECT id,name,sell_price,buy_price,stock_qty,unit,product_code,sku,barcode
             FROM products WHERE book_id=? AND deleted_at IS NULL ORDER BY name',
            [$book['id']]
        );
        $products = [];
        foreach ($rawProducts as $p) {
            $batchOrder = ($inventoryMethod === 'FIFO') ? 'ASC' : 'DESC';
            try {
                $batches = Database::query(
                    "SELECT * FROM product_batches WHERE product_id=? AND remaining_qty>0 ORDER BY created_at {$batchOrder}",
                    [$p['id']]
                );
            } catch (\Throwable $e) { $batches = []; }
            $p['batches'] = $batches;
            $products[] = $p;
        }

        if ($type === 'purchase') {
            $prefix  = $details['invoice_prefix_purchase'] ?? 'PUR';
            $counter = $details['invoice_counter_purchase'] ?? 1;
        } else {
            $prefix  = $details['invoice_prefix'] ?? 'INV';
            $counter = $details['invoice_counter'] ?? 1;
        }
        $invoiceNo = $prefix . '-' . str_pad($counter, 6, '0', STR_PAD_LEFT);

        $defaultCurrency = ['symbol' => '৳', 'code' => 'BDT'];
        foreach ($currencies as $c) { if ($c['is_default']) { $defaultCurrency = $c; break; } }

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
        $dueDate        = !empty($_POST['due_date'])     ? $_POST['due_date'] : null;
        $noteCustomer   = trim($_POST['note_customer']   ?? '');
        $noteSeller     = trim($_POST['note_seller']     ?? '');
        $discount       = (float)($_POST['discount']        ?? 0);
        $pointsDiscount = (float)($_POST['points_discount'] ?? 0);
        $deliveryCharge = (float)($_POST['delivery_charge'] ?? 0);
        $handlingCharge = (float)($_POST['handling_charge'] ?? 0);
        $deliveryType   = $_POST['delivery_type']           ?? 'own';
        $roundingOn     = !empty($_POST['rounding_enabled']);
        $tax            = (float)($_POST['tax']             ?? 0);
        $deliveryMethod = trim($_POST['delivery_method']    ?? '');
        $paymentMethod  = trim($_POST['payment_method']     ?? '');
        $currencySymbol = trim($_POST['currency_symbol']    ?? '৳');
        $currencyCode   = trim($_POST['currency_code']      ?? 'BDT');
        $themeColor     = $book['theme_color'] ?? '#1a6b4a';

        $itemNames    = $_POST['item_name']       ?? [];
        $itemQtys     = $_POST['item_qty']        ?? [];
        $itemPrices   = $_POST['item_price']      ?? [];
        $itemDiscs    = $_POST['item_discount']   ?? [];
        $itemPids     = $_POST['item_product_id'] ?? [];
        $itemVariants = $_POST['item_variant']    ?? [];

        if (empty($itemNames) || !array_filter(array_map('trim', $itemNames))) {
            redirect('/books/'.$book['id'].'/invoices/create?type='.$type, ['error' => 'Add at least one item.']);
        }

        $subtotal = 0;
        $items    = [];
        foreach ($itemNames as $i => $itemName) {
            $itemName = trim($itemName);
            if (!$itemName) continue;
            $qty     = (float)($itemQtys[$i]   ?? 1);
            $price   = (float)($itemPrices[$i] ?? 0);
            $discPct = (float)($itemDiscs[$i]  ?? 0);
            $pid     = !empty($itemPids[$i])   ? (int)$itemPids[$i] : null;
            $variant = trim($itemVariants[$i]  ?? '');
            $lineTot = $qty * $price * (1 - $discPct / 100);
            $subtotal += $lineTot;
            $items[]  = compact('itemName','qty','price','discPct','lineTot','pid','variant');
        }

        $rounding = 0.0;
        if ($roundingOn) {
            $baseTotal = $subtotal - $discount - $pointsDiscount + $deliveryCharge + $handlingCharge + $tax;
            $rounding  = $baseTotal - floor($baseTotal);
        }

        $total = max(0, $subtotal - $discount - $pointsDiscount + $deliveryCharge + $handlingCharge - $rounding + $tax);

        Database::run(
            'INSERT INTO invoices
                (book_id,type,invoice_no,customer_id,supplier_id,date,due_date,
                 subtotal,discount,points_discount,delivery_charge,handling_charge,delivery_type,rounding,tax,
                 total,paid,status,note_customer,note_seller,
                 delivery_method,payment_method,theme_color,currency_symbol,currency_code,
                 public_token,created_by,created_at)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,0,?,?,?,?,?,?,?,?,?,?,?)',
            [
                $book['id'],$type,$invoiceNo,$customerId,$supplierId,$date,$dueDate,
                $subtotal,$discount,$pointsDiscount,$deliveryCharge,$handlingCharge,$deliveryType,$rounding,$tax,
                $total,'draft',
                $noteCustomer ?: null,$noteSeller ?: null,
                $deliveryMethod ?: null,$paymentMethod ?: null,
                $themeColor,$currencySymbol,$currencyCode,
                bin2hex(random_bytes(20)),auth()['id'],now()
            ]
        );
        $invoiceId = Database::lastId();

        $details         = Database::row('SELECT * FROM book_business_details WHERE book_id=?', [$book['id']]);
        $inventoryMethod = $details['inventory_method'] ?? 'FIFO';

        foreach ($items as $item) {
            Database::run(
                'INSERT INTO invoice_items (invoice_id,product_id,description,variant,qty,unit_price,discount_pct,line_total)
                 VALUES (?,?,?,?,?,?,?,?)',
                [$invoiceId,$item['pid'],$item['itemName'],$item['variant'] ?: null,
                 $item['qty'],$item['price'],$item['discPct'],$item['lineTot']]
            );
            if ($item['pid']) {
                if ($type === 'sale') {
                    try { $this->deductFromBatches($item['pid'], $book['id'], $item['qty'], $inventoryMethod); }
                    catch (\Throwable $e) {}
                    Database::run(
                        'UPDATE products SET stock_qty=GREATEST(0,stock_qty-?) WHERE id=? AND book_id=?',
                        [$item['qty'],$item['pid'],$book['id']]
                    );
                } else {
                    try { $this->createOrUpdateBatch($item['pid'], $book['id'], $item['qty'], $item['price']); }
                    catch (\Throwable $e) {}
                    Database::run(
                        'UPDATE products SET stock_qty=stock_qty+? WHERE id=? AND book_id=?',
                        [$item['qty'],$item['pid'],$book['id']]
                    );
                }
            }
        }

        if ($deliveryType === 'other' && $deliveryCharge > 0) {
            try {
                Database::run(
                    'INSERT INTO expenses (book_id,title,amount,expense_date,note,created_by,created_at)
                     VALUES (?,?,?,?,?,?,?)',
                    [$book['id'], 'Delivery (3rd party) — Invoice '.$invoiceNo,
                     $deliveryCharge, $date, 'Auto-created from invoice #'.$invoiceNo, auth()['id'], now()]
                );
            } catch (\Throwable $e) {}
        }

        try {
            $reportCat = $type === 'sale' ? 'invoice_sale' : 'invoice_purchase';
            $reportDir = $type === 'sale' ? 'in' : 'out';
            Database::run(
                'INSERT INTO report_entries (book_id,type,category,amount,description,source_table,source_id,date,created_at)
                 VALUES (?,?,?,?,?,?,?,?,?)',
                [$book['id'],$reportDir,$reportCat,$total,
                 ($type==='sale'?'Sale':'Purchase').' invoice '.$invoiceNo,'invoices',$invoiceId,$date,now()]
            );
        } catch (\Throwable $e) {}

        if ($customerId && $pointsDiscount > 0) {
            Database::run('UPDATE customers SET points=GREATEST(0,points-?) WHERE id=?',
                [(int)$pointsDiscount, $customerId]);
        }

        $counterCol = $type === 'purchase' ? 'invoice_counter_purchase' : 'invoice_counter';
        Database::run("UPDATE book_business_details SET {$counterCol}={$counterCol}+1 WHERE book_id=?",
            [$book['id']]);

        if ($type === 'purchase' && !empty($_FILES['attachment']['name']) && $_FILES['attachment']['error'] === 0) {
            $this->saveAttachment($invoiceId, $_FILES['attachment']);
        }

        // Auto-create due for sale invoices with a customer (unpaid/partial)
        if ($type === 'sale' && $customerId) {
            $invoiceRow = Database::row('SELECT * FROM invoices WHERE id=?', [$invoiceId]);
            if ($invoiceRow) {
                DuesController::createFromInvoice($invoiceRow);
            }
        }

        // Auto-create debt for purchase invoices with a supplier (unpaid/partial)
        if ($type === 'purchase' && $supplierId) {
            $invoiceRow = Database::row('SELECT * FROM invoices WHERE id=?', [$invoiceId]);
            if ($invoiceRow) {
                DebtController::createFromInvoice($invoiceRow);
            }
        }

        redirect('/books/'.$book['id'].'/invoices/'.$invoiceId, ['success' => 'Invoice '.$invoiceNo.' created.']);
    }

    private function deductFromBatches(int $productId, int $bookId, float $qty, string $method): void
    {
        $order   = ($method === 'FIFO') ? 'ASC' : 'DESC';
        $batches = Database::query(
            "SELECT * FROM product_batches WHERE product_id=? AND book_id=? AND remaining_qty>0 ORDER BY created_at {$order}",
            [$productId, $bookId]
        );
        $remaining = $qty;
        foreach ($batches as $batch) {
            if ($remaining <= 0) break;
            $take = min((float)$batch['remaining_qty'], $remaining);
            Database::run(
                'UPDATE product_batches SET remaining_qty=GREATEST(0,remaining_qty-?) WHERE id=?',
                [$take, $batch['id']]
            );
            $remaining -= $take;
        }
    }

    private function createOrUpdateBatch(int $productId, int $bookId, float $qty, float $buyPrice): void
    {
        $batchCount = Database::row('SELECT COUNT(*)+1 AS n FROM product_batches WHERE product_id=?', [$productId]);
        $n = (int)($batchCount['n'] ?? 1);
        $barcode = 'BC'
            . str_pad($bookId, 3, '0', STR_PAD_LEFT)
            . str_pad($productId, 5, '0', STR_PAD_LEFT)
            . str_pad($n, 4, '0', STR_PAD_LEFT);
        $check = Database::row('SELECT id FROM product_batches WHERE barcode=?', [$barcode]);
        if ($check) {
            $barcode .= rand(10,99);
        }
        Database::run(
            'INSERT INTO product_batches (product_id,book_id,barcode,buy_price,sell_price,initial_qty,remaining_qty,created_at)
             VALUES (?,?,?,?,0,?,?,?)',
            [$productId, $bookId, $barcode, $buyPrice, $qty, $qty, now()]
        );
    }

    public function show(array $params): void
    {
        if (guest()) redirect('/login');
        $book    = $this->getBookOrFail($params['id']);
        $invoice = $this->getInvoiceOrFail($params['invoice_id'], $book['id']);
        $items   = Database::query('SELECT * FROM invoice_items WHERE invoice_id=?', [$invoice['id']]);
        $customer= $invoice['customer_id'] ? Database::row('SELECT * FROM customers WHERE id=?', [$invoice['customer_id']]) : null;
        $supplier= $invoice['supplier_id'] ? Database::row('SELECT * FROM suppliers WHERE id=?', [$invoice['supplier_id']]) : null;
        $details = Database::row('SELECT * FROM book_business_details WHERE book_id=?', [$book['id']]);
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
        // Shared variables for the print view
        $themeColor = $invoice['theme_color'] ?? $book['theme_color'] ?? '#1a6b4a';
        $bizName    = $details['business_name'] ?? $book['name'];
        $bizAddress = $details['address'] ?? $book['address'] ?? '';
        $bizPhone   = $details['phone']   ?? $book['phone']   ?? '';
        $bizEmail   = $details['email']   ?? $book['email']   ?? '';
        $isPublic   = false;
        require BASE_PATH . '/views/business/invoices/print.php';
    }

    public function thermal(array $params): void
    {
        if (guest()) redirect('/login');
        $book    = $this->getBookOrFail($params['id']);
        $invoice = $this->getInvoiceOrFail($params['invoice_id'], $book['id']);
        $items   = Database::query('SELECT * FROM invoice_items WHERE invoice_id=?', [$invoice['id']]);
        $customer= $invoice['customer_id'] ? Database::row('SELECT * FROM customers WHERE id=?', [$invoice['customer_id']]) : null;
        $supplier= $invoice['supplier_id'] ? Database::row('SELECT * FROM suppliers WHERE id=?', [$invoice['supplier_id']]) : null;
        $details = Database::row('SELECT * FROM book_business_details WHERE book_id=?', [$book['id']]);
        $paperWidth = (int)($_GET['w'] ?? 80);
        require BASE_PATH . '/views/business/invoices/thermal.php';
    }

    public function recordPayment(array $params): void
    {
        if (guest()) redirect('/login');
        csrf_verify();
        $book    = $this->getBookOrFail($params['id']);
        $invoice = $this->getInvoiceOrFail($params['invoice_id'], $book['id']);

        $amount = min((float)($_POST['amount'] ?? 0), $invoice['total'] - $invoice['paid']);
        $method = trim($_POST['method'] ?? 'cash');
        $note   = trim($_POST['note']   ?? '');

        if ($amount <= 0) redirect('/books/'.$book['id'].'/invoices/'.$invoice['id'], ['error' => 'Invalid amount.']);

        $newPaid = $invoice['paid'] + $amount;
        $status  = $newPaid >= $invoice['total'] ? 'paid' : 'partial';

        Database::run('UPDATE invoices SET paid=?,status=? WHERE id=?', [$newPaid,$status,$invoice['id']]);
        Database::run('INSERT INTO payments (invoice_id,amount,method,date,note) VALUES (?,?,?,?,?)',
            [$invoice['id'],$amount,$method,date('Y-m-d'),$note ?: null]);

        if ($invoice['customer_id'] && $invoice['type'] === 'sale') {
            $pts = (int)($amount / 100);
            if ($pts > 0) Database::run('UPDATE customers SET points=points+? WHERE id=?', [$pts,$invoice['customer_id']]);
            // Sync due record
            DuesController::syncFromInvoicePayment($invoice['id'], $newPaid);
        }

        if ($invoice['type'] === 'purchase') {
            // Sync debt record
            DebtController::syncFromInvoicePayment($invoice['id'], $newPaid);
        }

        redirect('/books/'.$book['id'].'/invoices/'.$invoice['id'], ['success' => format_money($amount).' recorded.']);
    }

    public function markSent(array $params): void
    {
        if (guest()) redirect('/login');
        csrf_verify();
        $book    = $this->getBookOrFail($params['id']);
        $invoice = $this->getInvoiceOrFail($params['invoice_id'], $book['id']);
        Database::run('UPDATE invoices SET status="sent" WHERE id=? AND status="draft"', [$invoice['id']]);
        redirect('/books/'.$book['id'].'/invoices/'.$invoice['id'], ['success' => 'Marked as sent.']);
    }

    public function delete(array $params): void
    {
        if (guest()) redirect('/login');
        csrf_verify();
        $book    = $this->getBookOrFail($params['id']);
        $invoice = $this->getInvoiceOrFail($params['invoice_id'], $book['id']);
        Database::run('UPDATE invoices SET deleted_at=? WHERE id=?', [now(),$invoice['id']]);
        redirect('/books/'.$book['id'].'/invoices', ['success' => 'Invoice deleted.']);
    }

    public function uploadAttachment(array $params): void
    {
        if (guest()) redirect("/login");
        csrf_verify();
        $book    = $this->getBookOrFail($params["id"]);
        $invoice = $this->getInvoiceOrFail($params["invoice_id"], $book["id"]);
        if (!empty($_FILES["attachment"]["name"]) && $_FILES["attachment"]["error"] === 0) {
            $this->saveAttachment($invoice["id"], $_FILES["attachment"]);
            redirect("/books/".$book["id"]."/invoices/".$invoice["id"], ["success" => "Attachment uploaded."]);
        }
        redirect("/books/".$book["id"]."/invoices/".$invoice["id"], ["error" => "No file selected."]);
    }

    private function saveAttachment(int $invoiceId, array $file): void
    {
        $allowed = ['pdf','jpg','jpeg','png','webp'];
        $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed) || $file['size'] > 10*1024*1024) return;
        $dir = config('upload.path') . '/attachments';
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        if (!is_writable($dir)) return;
        $filename = 'inv_'.$invoiceId.'_'.date('Ymd_His').'_'.bin2hex(random_bytes(4)).'.'.$ext;
        if (move_uploaded_file($file['tmp_name'], $dir.'/'.$filename)) {
            Database::run(
                'INSERT INTO invoice_attachments (invoice_id,filename,path,size,created_at) VALUES (?,?,?,?,?)',
                [$invoiceId,$file['name'],'attachments/'.$filename,$file['size'],now()]
            );
        }
    }

    private function getBookOrFail(string $id): array
    {
        $book = Database::row('SELECT * FROM books WHERE id=? AND user_id=? AND deleted_at IS NULL AND type="business"', [$id,auth()['id']]);
        if (!$book) { http_response_code(404); require BASE_PATH.'/views/errors/404.php'; exit; }
        return $book;
    }

    private function getInvoiceOrFail(string $iid, int $bookId): array
    {
        $inv = Database::row('SELECT * FROM invoices WHERE id=? AND book_id=? AND deleted_at IS NULL', [$iid,$bookId]);
        if (!$inv) { http_response_code(404); require BASE_PATH.'/views/errors/404.php'; exit; }
        return $inv;
    }
}

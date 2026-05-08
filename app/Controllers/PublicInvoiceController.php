<?php
namespace App\Controllers;
use App\Helpers\Database;

class PublicInvoiceController
{
    // GET /invoice/{token}
    // Also: GET /Business/{name}/Invoice/{invoice_no}
    public function show(array $params): void
    {
        $token = $params['token'] ?? '';

        $invoice = Database::row(
            'SELECT i.*, b.name AS book_name, b.logo AS book_logo,
                    b.phone AS book_phone, b.email AS book_email,
                    b.address AS book_address, b.theme_color,
                    bd.business_name
             FROM invoices i
             JOIN books b ON b.id = i.book_id
             LEFT JOIN book_business_details bd ON bd.book_id = b.id
             WHERE i.public_token = ? AND i.deleted_at IS NULL',
            [$token]
        );

        if (!$invoice) {
            http_response_code(404);
            echo '<div style="font-family:sans-serif;text-align:center;padding:60px">
                    <h2>Invoice not found</h2>
                    <p style="color:#666">This invoice link is invalid or has expired.</p>
                  </div>';
            return;
        }

        $items    = Database::query('SELECT * FROM invoice_items WHERE invoice_id=?', [$invoice['id']]);
        $customer = $invoice['customer_id'] ? Database::row('SELECT * FROM customers WHERE id=?', [$invoice['customer_id']]) : null;
        $supplier = $invoice['supplier_id'] ? Database::row('SELECT * FROM suppliers WHERE id=?', [$invoice['supplier_id']]) : null;
        $payments = Database::query('SELECT * FROM payments WHERE invoice_id=? ORDER BY date', [$invoice['id']]);

        require BASE_PATH . '/views/public/invoice.php';
    }

    // GET /Business/{slug}/Invoice/{invoice_no}
    public function showByNo(array $params): void
    {
        $slug      = $params['slug']       ?? '';
        $invoiceNo = $params['invoice_no'] ?? '';

        $invoice = Database::row(
            'SELECT i.*, b.name AS book_name, b.logo AS book_logo,
                    b.phone AS book_phone, b.email AS book_email,
                    b.address AS book_address, b.theme_color,
                    bd.business_name
             FROM invoices i
             JOIN books b ON b.id = i.book_id
             LEFT JOIN book_business_details bd ON bd.book_id = b.id
             WHERE i.invoice_no = ?
               AND (LOWER(REPLACE(b.name," ","-")) = ? OR LOWER(REPLACE(bd.business_name," ","-")) = ?)
               AND i.deleted_at IS NULL
             LIMIT 1',
            [$invoiceNo, strtolower($slug), strtolower($slug)]
        );

        if (!$invoice) {
            http_response_code(404);
            echo '<div style="font-family:sans-serif;text-align:center;padding:60px">
                    <h2>Invoice not found</h2>
                    <p style="color:#666">Could not find invoice '
                    . htmlspecialchars($invoiceNo) . ' for ' . htmlspecialchars($slug) . '.</p>
                  </div>';
            return;
        }

        $items    = Database::query('SELECT * FROM invoice_items WHERE invoice_id=?', [$invoice['id']]);
        $customer = $invoice['customer_id'] ? Database::row('SELECT * FROM customers WHERE id=?', [$invoice['customer_id']]) : null;
        $supplier = $invoice['supplier_id'] ? Database::row('SELECT * FROM suppliers WHERE id=?', [$invoice['supplier_id']]) : null;
        $payments = Database::query('SELECT * FROM payments WHERE invoice_id=? ORDER BY date', [$invoice['id']]);

        require BASE_PATH . '/views/public/invoice.php';
    }
}

<?php
namespace App\Controllers;
use App\Helpers\Database;

class BookSearchController
{
    public function search(array $params): void
    {
        if (guest()) json_response(['error' => 'Unauthorized'], 401);

        $book = Database::row(
            'SELECT * FROM books WHERE id=? AND user_id=? AND deleted_at IS NULL',
            [$params['id'], auth()['id']]
        );
        if (!$book) json_response(['error' => 'Not found'], 404);

        $q    = trim($_GET['q'] ?? '');
        $like = '%' . $q . '%';

        if (strlen($q) < 1) json_response(['results' => []]);

        $results = [];

        if ($book['type'] === 'business') {
            // Customers
            $customers = Database::query(
                'SELECT id, name, phone, "customer" AS type FROM customers
                 WHERE book_id=? AND deleted_at IS NULL AND (name LIKE ? OR phone LIKE ? OR email LIKE ?)
                 LIMIT 5',
                [$book['id'], $like, $like, $like]
            );
            foreach ($customers as $c) {
                $results[] = [
                    'type'  => 'Customer',
                    'label' => $c['name'] . ($c['phone'] ? ' — '.$c['phone'] : ''),
                    'url'   => '/books/'.$book['id'].'/customers/'.$c['id'],
                ];
            }

            // Suppliers
            $suppliers = Database::query(
                'SELECT id, name, phone, company FROM suppliers
                 WHERE book_id=? AND deleted_at IS NULL AND (name LIKE ? OR phone LIKE ? OR company LIKE ?)
                 LIMIT 5',
                [$book['id'], $like, $like, $like]
            );
            foreach ($suppliers as $s) {
                $results[] = [
                    'type'  => 'Supplier',
                    'label' => $s['name'] . ($s['company'] ? ' ('.$s['company'].')' : ''),
                    'url'   => '/books/'.$book['id'].'/suppliers/'.$s['id'],
                ];
            }

            // Products
            $products = Database::query(
                'SELECT id, name, product_code, stock_qty, unit FROM products
                 WHERE book_id=? AND deleted_at IS NULL
                   AND (name LIKE ? OR product_code LIKE ? OR sku LIKE ? OR barcode LIKE ?)
                 LIMIT 5',
                [$book['id'], $like, $like, $like, $like]
            );
            foreach ($products as $p) {
                $results[] = [
                    'type'  => 'Product',
                    'label' => $p['name'].' ['.$p['product_code'].'] — stock: '.rtrim(rtrim(number_format($p['stock_qty'],3),'0'),'.').' '.$p['unit'],
                    'url'   => '/books/'.$book['id'].'/products',
                ];
            }

            // Invoices
            $invoices = Database::query(
                'SELECT id, invoice_no, type, total, status FROM invoices
                 WHERE book_id=? AND deleted_at IS NULL AND invoice_no LIKE ?
                 LIMIT 5',
                [$book['id'], $like]
            );
            foreach ($invoices as $inv) {
                $results[] = [
                    'type'  => ucfirst($inv['type']).' Invoice',
                    'label' => $inv['invoice_no'].' — ৳'.number_format($inv['total'],0).' ('.$inv['status'].')',
                    'url'   => '/books/'.$book['id'].'/invoices/'.$inv['id'],
                ];
            }
        } else {
            // Personal book: entries + contacts
            $contacts = Database::query(
                'SELECT id, name, phone FROM contacts
                 WHERE book_id=? AND deleted_at IS NULL AND (name LIKE ? OR phone LIKE ?)
                 LIMIT 5',
                [$book['id'], $like, $like]
            );
            foreach ($contacts as $c) {
                $results[] = [
                    'type'  => 'Contact',
                    'label' => $c['name'].($c['phone'] ? ' — '.$c['phone'] : ''),
                    'url'   => '/books/'.$book['id'].'/contacts',
                ];
            }

            $entries = Database::query(
                'SELECT id, title, amount, type FROM entries
                 WHERE book_id=? AND deleted_at IS NULL AND title LIKE ?
                 LIMIT 5',
                [$book['id'], $like]
            );
            foreach ($entries as $e) {
                $results[] = [
                    'type'  => $e['type'] === 'in' ? 'Income' : 'Expense',
                    'label' => $e['title'].' — ৳'.number_format($e['amount'],0),
                    'url'   => '/books/'.$book['id'],
                ];
            }
        }

        json_response(['results' => $results]);
    }
}

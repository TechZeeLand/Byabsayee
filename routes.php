<?php
use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\BookController;
use App\Controllers\EntryController;
use App\Controllers\ContactController;
use App\Controllers\CustomerController;
use App\Controllers\SupplierController;
use App\Controllers\ProductController;
use App\Controllers\InvoiceController;

// =====================================================================
// PUBLIC
// =====================================================================
$router->get('/', function() { auth() ? redirect('/dashboard') : redirect('/login'); });

$router->get( '/login',           [AuthController::class, 'showLogin']);
$router->post('/login',           [AuthController::class, 'login']);
$router->get( '/register',        [AuthController::class, 'showRegister']);
$router->post('/register',        [AuthController::class, 'register']);
$router->get( '/logout',          [AuthController::class, 'logout']);
$router->get( '/forgot-password', [AuthController::class, 'showForgotPassword']);
$router->post('/forgot-password', [AuthController::class, 'sendResetLink']);
$router->get( '/reset-password',  [AuthController::class, 'showResetPassword']);
$router->post('/reset-password',  [AuthController::class, 'resetPassword']);
$router->get( '/verify-email',    [AuthController::class, 'verifyEmail']);

// =====================================================================
// PROTECTED
// =====================================================================

// Dashboard
$router->get('/dashboard', [DashboardController::class, 'index']);

// Books
$router->get( '/books',             [BookController::class, 'index']);
$router->get( '/books/create',      [BookController::class, 'create']);
$router->post('/books/create',      [BookController::class, 'store']);
$router->get( '/books/{id}',        [BookController::class, 'show']);
$router->get( '/books/{id}/edit',   [BookController::class, 'edit']);
$router->post('/books/{id}/edit',   [BookController::class, 'update']);
$router->post('/books/{id}/delete', [BookController::class, 'delete']);

// Entries (personal books)
$router->post('/books/{id}/entries/add',               [EntryController::class, 'store']);
$router->post('/books/{id}/entries/{entry_id}/delete', [EntryController::class, 'delete']);

// Contacts (personal books)
$router->get( '/books/{id}/contacts',                       [ContactController::class, 'index']);
$router->post('/books/{id}/contacts/add',                   [ContactController::class, 'store']);
$router->post('/books/{id}/contacts/{contact_id}/delete',   [ContactController::class, 'delete']);

// Customers (business books)
$router->get( '/books/{id}/customers',                          [CustomerController::class, 'index']);
$router->post('/books/{id}/customers/add',                      [CustomerController::class, 'store']);
$router->get( '/books/{id}/customers/{customer_id}',            [CustomerController::class, 'show']);
$router->post('/books/{id}/customers/{customer_id}/edit',       [CustomerController::class, 'update']);
$router->post('/books/{id}/customers/{customer_id}/delete',     [CustomerController::class, 'delete']);

// Suppliers (business books)
$router->get( '/books/{id}/suppliers',                          [SupplierController::class, 'index']);
$router->post('/books/{id}/suppliers/add',                      [SupplierController::class, 'store']);
$router->get( '/books/{id}/suppliers/{supplier_id}',            [SupplierController::class, 'show']);
$router->post('/books/{id}/suppliers/{supplier_id}/edit',       [SupplierController::class, 'update']);
$router->post('/books/{id}/suppliers/{supplier_id}/delete',     [SupplierController::class, 'delete']);

// Products (business books)
$router->get( '/books/{id}/products',                           [ProductController::class, 'index']);
$router->post('/books/{id}/products/add',                       [ProductController::class, 'store']);
$router->post('/books/{id}/products/{product_id}/edit',         [ProductController::class, 'update']);
$router->post('/books/{id}/products/{product_id}/adjust',       [ProductController::class, 'adjustStock']);
$router->post('/books/{id}/products/{product_id}/delete',       [ProductController::class, 'delete']);

// Invoices (business books)
$router->get( '/books/{id}/invoices',                           [InvoiceController::class, 'index']);
$router->get( '/books/{id}/invoices/create',                    [InvoiceController::class, 'create']);
$router->post('/books/{id}/invoices/create',                    [InvoiceController::class, 'store']);
$router->get( '/books/{id}/invoices/{invoice_id}',              [InvoiceController::class, 'show']);
$router->post('/books/{id}/invoices/{invoice_id}/payment',      [InvoiceController::class, 'recordPayment']);
$router->post('/books/{id}/invoices/{invoice_id}/sent',         [InvoiceController::class, 'markSent']);
$router->post('/books/{id}/invoices/{invoice_id}/delete',       [InvoiceController::class, 'delete']);

// Invoice PDF
$router->get( '/books/{id}/invoices/{invoice_id}/pdf', [InvoiceController::class, 'pdf']);

// Customer privileges
$router->get( '/books/{id}/privileges',                      [\App\Controllers\PrivilegeController::class, 'index']);
$router->post('/books/{id}/privileges/add',                  [\App\Controllers\PrivilegeController::class, 'store']);
$router->post('/books/{id}/privileges/{priv_id}/edit',       [\App\Controllers\PrivilegeController::class, 'update']);
$router->post('/books/{id}/privileges/{priv_id}/delete',     [\App\Controllers\PrivilegeController::class, 'delete']);

// Product category
$router->post('/books/{id}/products/category/add',          [\App\Controllers\ProductController::class, 'storeCategory']);

// Product lookup API (barcode / product code search)
$router->get( '/books/{id}/products/lookup',                [\App\Controllers\ProductController::class, 'lookup']);

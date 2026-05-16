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
use App\Controllers\PrivilegeController;
use App\Controllers\PublicInvoiceController;
use App\Controllers\PosController;
use App\Controllers\BookSearchController;
use App\Controllers\ExpensesController;
use App\Controllers\FundsController;
use App\Controllers\DuesController;
use App\Controllers\DebtController;
use App\Controllers\CouponController;
use App\Controllers\ReturnController;
use App\Controllers\ReportsController;
use App\Controllers\EmployeeController;

// =============================================================================
// PUBLIC
// =============================================================================
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

$router->get('/invoice/{token}',                              [PublicInvoiceController::class, 'show']);
$router->get('/Business/{slug}/Invoice/{invoice_no}',         [PublicInvoiceController::class, 'showByNo']);

// =============================================================================
// PROTECTED
// =============================================================================
$router->get('/dashboard', [DashboardController::class, 'index']);

// ── Books ─────────────────────────────────────────────────────────────────────
$router->get( '/books',             [BookController::class, 'index']);
$router->get( '/books/create',      [BookController::class, 'create']);
$router->post('/books/create',      [BookController::class, 'store']);
$router->get( '/books/{id}',        [BookController::class, 'show']);
$router->get( '/books/{id}/edit',   [BookController::class, 'edit']);
$router->post('/books/{id}/edit',   [BookController::class, 'update']);
$router->post('/books/{id}/delete', [BookController::class, 'delete']);

// ── Book search ────────────────────────────────────────────────────────────────
$router->get('/books/{id}/search',  [BookSearchController::class, 'search']);

// ── Entries (personal) ────────────────────────────────────────────────────────
$router->post('/books/{id}/entries/add',               [EntryController::class, 'store']);
$router->post('/books/{id}/entries/{entry_id}/edit',   [EntryController::class, 'update']);
$router->post('/books/{id}/entries/{entry_id}/delete', [EntryController::class, 'delete']);

// ── Contacts ──────────────────────────────────────────────────────────────────
$router->get( '/books/{id}/contacts',                      [ContactController::class, 'index']);
$router->post('/books/{id}/contacts/add',                  [ContactController::class, 'store']);
$router->post('/books/{id}/contacts/{contact_id}/edit',    [ContactController::class, 'update']);
$router->post('/books/{id}/contacts/{contact_id}/delete',  [ContactController::class, 'delete']);

// ── Customers ──────────────────────────────────────────────────────────────────
$router->get( '/books/{id}/customers',                          [CustomerController::class, 'index']);
$router->post('/books/{id}/customers/add',                      [CustomerController::class, 'store']);
$router->get( '/books/{id}/customers/{customer_id}',            [CustomerController::class, 'show']);
$router->post('/books/{id}/customers/{customer_id}/edit',       [CustomerController::class, 'update']);
$router->post('/books/{id}/customers/{customer_id}/delete',     [CustomerController::class, 'delete']);
$router->post('/books/{id}/customers/{customer_id}/privileges', [CustomerController::class, 'updatePrivileges']);
$router->get( '/books/{id}/customers/search',                   [CustomerController::class, 'search']);

// ── Suppliers ──────────────────────────────────────────────────────────────────
$router->get( '/books/{id}/suppliers',                          [SupplierController::class, 'index']);
$router->post('/books/{id}/suppliers/add',                      [SupplierController::class, 'store']);
$router->get( '/books/{id}/suppliers/{supplier_id}',            [SupplierController::class, 'show']);
$router->post('/books/{id}/suppliers/{supplier_id}/edit',       [SupplierController::class, 'update']);
$router->post('/books/{id}/suppliers/{supplier_id}/delete',     [SupplierController::class, 'delete']);

// ── Products ───────────────────────────────────────────────────────────────────
$router->get( '/books/{id}/products',                           [ProductController::class, 'index']);
$router->post('/books/{id}/products/add',                       [ProductController::class, 'store']);
$router->post('/books/{id}/products/{product_id}/edit',         [ProductController::class, 'update']);
$router->post('/books/{id}/products/{product_id}/adjust',       [ProductController::class, 'adjustStock']);
$router->post('/books/{id}/products/{product_id}/delete',       [ProductController::class, 'delete']);
$router->post('/books/{id}/products/category/add',              [ProductController::class, 'storeCategory']);
$router->get( '/books/{id}/products/lookup',                    [ProductController::class, 'lookup']);
$router->get( '/books/{id}/products/barcodes',                  [ProductController::class, 'barcodes']);

// ── Invoices ───────────────────────────────────────────────────────────────────
$router->get( '/books/{id}/invoices',                           [InvoiceController::class, 'index']);
$router->get( '/books/{id}/invoices/create',                    [InvoiceController::class, 'create']);
$router->post('/books/{id}/invoices/create',                    [InvoiceController::class, 'store']);
$router->get( '/books/{id}/invoices/{invoice_id}',              [InvoiceController::class, 'show']);
$router->get( '/books/{id}/invoices/{invoice_id}/pdf',          [InvoiceController::class, 'pdf']);
$router->get( '/books/{id}/invoices/{invoice_id}/thermal',      [InvoiceController::class, 'thermal']);
$router->post('/books/{id}/invoices/{invoice_id}/payment',      [InvoiceController::class, 'recordPayment']);
$router->post('/books/{id}/invoices/{invoice_id}/sent',         [InvoiceController::class, 'markSent']);
$router->post('/books/{id}/invoices/{invoice_id}/delete',       [InvoiceController::class, 'delete']);
$router->post('/books/{id}/invoices/{invoice_id}/attachment',   [InvoiceController::class, 'uploadAttachment']);

// ── POS ───────────────────────────────────────────────────────────────────────
$router->get( '/books/{id}/pos',  [PosController::class, 'show']);
$router->post('/books/{id}/pos',  [PosController::class, 'store']);

// ── Returns ───────────────────────────────────────────────────────────────────
$router->get( '/books/{id}/returns',                            [ReturnController::class, 'index']);
$router->get( '/books/{id}/returns/create',                     [ReturnController::class, 'create']);
$router->post('/books/{id}/returns/create',                     [ReturnController::class, 'store']);
$router->get( '/books/{id}/returns/invoice-items',              [ReturnController::class, 'getInvoiceItems']);
$router->get( '/books/{id}/returns/{return_id}',                [ReturnController::class, 'show']);
$router->post('/books/{id}/returns/{return_id}/delete',         [ReturnController::class, 'delete']);

// ── Reports ───────────────────────────────────────────────────────────────────
$router->get( '/books/{id}/reports',                            [ReportsController::class, 'index']);

// ── Privileges ─────────────────────────────────────────────────────────────────
$router->get( '/books/{id}/privileges',                         [PrivilegeController::class, 'index']);
$router->post('/books/{id}/privileges/add',                     [PrivilegeController::class, 'store']);
$router->post('/books/{id}/privileges/{priv_id}/edit',          [PrivilegeController::class, 'update']);
$router->post('/books/{id}/privileges/{priv_id}/delete',        [PrivilegeController::class, 'delete']);

// ── Expenses ───────────────────────────────────────────────────────────────────
$router->get( '/books/{id}/expenses',                           [ExpensesController::class, 'index']);
$router->post('/books/{id}/expenses/add',                       [ExpensesController::class, 'store']);
$router->post('/books/{id}/expenses/{expense_id}/edit',         [ExpensesController::class, 'update']);
$router->post('/books/{id}/expenses/{expense_id}/delete',       [ExpensesController::class, 'delete']);
$router->post('/books/{id}/expenses/category/add',              [ExpensesController::class, 'storeCategory']);

// ── Funds ──────────────────────────────────────────────────────────────────────
$router->get( '/books/{id}/funds',                              [FundsController::class, 'index']);
$router->post('/books/{id}/funds/add',                          [FundsController::class, 'store']);
$router->post('/books/{id}/funds/{fund_id}/edit',               [FundsController::class, 'update']);
$router->post('/books/{id}/funds/{fund_id}/delete',             [FundsController::class, 'delete']);

// ── Dues ───────────────────────────────────────────────────────────────────────
$router->get( '/books/{id}/dues',                               [DuesController::class, 'index']);
$router->post('/books/{id}/dues/add',                           [DuesController::class, 'store']);
$router->post('/books/{id}/dues/{due_id}/edit',                 [DuesController::class, 'update']);
$router->post('/books/{id}/dues/{due_id}/pay',                  [DuesController::class, 'recordPayment']);
$router->post('/books/{id}/dues/{due_id}/cancel',               [DuesController::class, 'cancel']);
$router->post('/books/{id}/dues/{due_id}/delete',               [DuesController::class, 'delete']);

// ── Debts ──────────────────────────────────────────────────────────────────────
$router->get( '/books/{id}/debts',                              [DebtController::class, 'index']);
$router->post('/books/{id}/debts/add',                          [DebtController::class, 'store']);
$router->post('/books/{id}/debts/{debt_id}/edit',               [DebtController::class, 'update']);
$router->post('/books/{id}/debts/{debt_id}/pay',                [DebtController::class, 'recordPayment']);
$router->post('/books/{id}/debts/{debt_id}/cancel',             [DebtController::class, 'cancel']);
$router->post('/books/{id}/debts/{debt_id}/delete',             [DebtController::class, 'delete']);

// ── Coupons ────────────────────────────────────────────────────────────────────
$router->get( '/books/{id}/coupons',                            [CouponController::class, 'index']);
$router->post('/books/{id}/coupons/add',                        [CouponController::class, 'store']);
$router->post('/books/{id}/coupons/{coupon_id}/edit',           [CouponController::class, 'update']);
$router->post('/books/{id}/coupons/{coupon_id}/toggle',         [CouponController::class, 'toggle']);
$router->post('/books/{id}/coupons/{coupon_id}/delete',         [CouponController::class, 'delete']);
$router->get( '/books/{id}/coupons/print',                      [CouponController::class, 'printCoupons']);
$router->get( '/books/{id}/coupons/validate',                   [CouponController::class, 'validateAjax']);

// ── Employees ─────────────────────────────────────────────────────────────────
$router->get( '/books/{id}/employees',                                           [EmployeeController::class, 'index']);
$router->post('/books/{id}/employees/add',                                       [EmployeeController::class, 'store']);
$router->post('/books/{id}/employees/invite',                                    [EmployeeController::class, 'invite']);
$router->get( '/books/{id}/employees/{employee_id}',                             [EmployeeController::class, 'show']);
$router->post('/books/{id}/employees/{employee_id}/edit',                        [EmployeeController::class, 'update']);
$router->post('/books/{id}/employees/{employee_id}/delete',                      [EmployeeController::class, 'delete']);
$router->post('/books/{id}/employees/{employee_id}/permissions',                 [EmployeeController::class, 'updatePermissions']);
$router->post('/books/{id}/employees/{employee_id}/revoke',                      [EmployeeController::class, 'revokeAccess']);
$router->post('/books/{id}/employees/{employee_id}/restore',                     [EmployeeController::class, 'restoreAccess']);
$router->post('/books/{id}/employees/invitations/{inv_id}/cancel',               [EmployeeController::class, 'cancelInvitation']);
$router->post('/books/{id}/employees/designations/add',                          [EmployeeController::class, 'storeDesignation']);
$router->post('/books/{id}/employees/designations/{desig_id}/edit',              [EmployeeController::class, 'updateDesignation']);
$router->post('/books/{id}/employees/designations/{desig_id}/delete',            [EmployeeController::class, 'deleteDesignation']);
$router->get( '/books/{id}/employees/designations/{desig_id}/permissions',       [EmployeeController::class, 'getDesignationPermissions']);

// ── Invitations ───────────────────────────────────────────────────────────────
$router->get( '/invitations/{token}',          [EmployeeController::class, 'acceptPage']);
$router->post('/invitations/{token}/respond',  [EmployeeController::class, 'respondInvitation']);

// ── Notifications ─────────────────────────────────────────────────────────────
$router->get( '/books/{id}/notifications',     [EmployeeController::class, 'notifications']);
$router->get( '/notifications/count',          [EmployeeController::class, 'unreadCount']);

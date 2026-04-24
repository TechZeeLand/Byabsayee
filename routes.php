<?php
// =============================================================================
// routes.php — All URL routes for Byabsayee
// =============================================================================

use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\BookController;
use App\Controllers\EntryController;
use App\Controllers\ContactController;

// PUBLIC
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

// PROTECTED
$router->get('/dashboard', [DashboardController::class, 'index']);

// Books
$router->get( '/books',             [BookController::class, 'index']);
$router->get( '/books/create',      [BookController::class, 'create']);
$router->post('/books/create',      [BookController::class, 'store']);
$router->get( '/books/{id}',        [BookController::class, 'show']);
$router->get( '/books/{id}/edit',   [BookController::class, 'edit']);
$router->post('/books/{id}/edit',   [BookController::class, 'update']);
$router->post('/books/{id}/delete', [BookController::class, 'delete']);

// Entries
$router->post('/books/{id}/entries/add',                  [EntryController::class, 'store']);
$router->post('/books/{id}/entries/{entry_id}/delete',    [EntryController::class, 'delete']);

// Contacts
$router->get( '/books/{id}/contacts',                     [ContactController::class, 'index']);
$router->post('/books/{id}/contacts/add',                 [ContactController::class, 'store']);
$router->post('/books/{id}/contacts/{contact_id}/delete', [ContactController::class, 'delete']);

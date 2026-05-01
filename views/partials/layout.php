<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? 'Byabsayee') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Bengali:wght@400;600&family=DM+Sans:ital,wght@0,400;0,500;0,600;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
</head>
<body>

<?php
// Detect if we are inside a specific book
$uri        = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$bookMatch  = [];
$inBook     = preg_match('#^/books/(\d+)#', $uri, $bookMatch);
$currentBookId = $inBook ? (int)$bookMatch[1] : null;

// Load book info for sidebar if we're inside one
$sidebarBook    = null;
$sidebarDetails = null;
if ($currentBookId) {
    $sidebarBook = \App\Helpers\Database::row(
        'SELECT * FROM books WHERE id=? AND user_id=? AND deleted_at IS NULL',
        [$currentBookId, auth()['id']]
    );
    if ($sidebarBook && $sidebarBook['type'] === 'business') {
        $sidebarDetails = \App\Helpers\Database::row(
            'SELECT * FROM book_business_details WHERE book_id=?', [$currentBookId]
        );
    }
}
?>

<!-- ===================== SIDEBAR ===================== -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-top">
        <a href="/dashboard" class="sidebar-logo">
            <div class="s-logo-icon">৳</div>
            <span class="s-logo-text">Byabsayee</span>
        </a>
    </div>

    <nav class="sidebar-nav">

        <?php if ($sidebarBook): ?>
        <!-- ── INSIDE A BOOK: show back + book modules ── -->
        <a href="/dashboard" class="nav-item" style="color:var(--text-muted);font-size:12px;padding:6px 10px;margin-bottom:4px">
            <svg viewBox="0 0 20 20" fill="currentColor" width="14" height="14"><path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
            All Books
        </a>

        <!-- Book name in sidebar -->
        <div style="padding:8px 10px;margin-bottom:4px">
            <div style="display:flex;align-items:center;gap:8px">
                <?php if (!empty($sidebarBook['logo'])): ?>
                <img src="<?= asset('uploads/'.$sidebarBook['logo']) ?>"
                     style="height:22px;max-width:60px;object-fit:contain;border-radius:3px"
                     onerror="this.style.display='none'">
                <?php endif; ?>
                <span style="font-size:13px;font-weight:600;color:var(--text)">
                    <?= e($sidebarDetails['business_name'] ?? $sidebarBook['name']) ?>
                </span>
            </div>
            <div style="display:flex;align-items:center;gap:5px;margin-top:4px">
                <span style="display:inline-block;width:7px;height:7px;border-radius:50%;background:<?= e($sidebarBook['color']) ?>"></span>
                <span style="font-size:11px;color:var(--text-muted);text-transform:capitalize"><?= e($sidebarBook['type']) ?> book</span>
            </div>
        </div>

        <a href="/books/<?= $currentBookId ?>" class="nav-item <?= activePage('books/'.$currentBookId) && !preg_match('#/books/'.$currentBookId.'/.+#',$uri) ? 'active' : '' ?>">
            <svg viewBox="0 0 20 20" fill="currentColor" width="16" height="16"><path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h4a1 1 0 001-1v-3h2v3a1 1 0 001 1h4a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/></svg>
            Dashboard
        </a>

        <?php if ($sidebarBook['type'] === 'business'): ?>
        <a href="/books/<?= $currentBookId ?>/pos" class="nav-item <?= str_contains($uri,'/pos') ? 'active' : '' ?>" style="color:var(--green)">
            <span style="font-size:15px">🖨</span> Quick Sale (POS)
        </a>

        <div style="padding:6px 10px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:var(--text-muted);margin-top:6px">Modules</div>

        <?php
        $bookNav = [
            ['icon'=>'👥','label'=>'Customers', 'path'=>'customers'],
            ['icon'=>'🏭','label'=>'Suppliers',  'path'=>'suppliers'],
            ['icon'=>'📦','label'=>'Products',   'path'=>'products'],
            ['icon'=>'🧾','label'=>'Invoices',   'path'=>'invoices'],
            ['icon'=>'💸','label'=>'Expenses',   'path'=>'expenses'],
            ['icon'=>'👔','label'=>'Employees',  'path'=>'employees'],
            ['icon'=>'🚚','label'=>'Deliveries', 'path'=>'deliveries'],
            ['icon'=>'🎫','label'=>'Privileges', 'path'=>'privileges'],
            ['icon'=>'📊','label'=>'Reports',    'path'=>'reports'],
        ];
        foreach ($bookNav as $nav):
            $isActive = str_contains($uri, '/books/'.$currentBookId.'/'.$nav['path']);
        ?>
        <a href="/books/<?= $currentBookId ?>/<?= $nav['path'] ?>"
           class="nav-item <?= $isActive ? 'active' : '' ?>">
            <span style="font-size:14px"><?= $nav['icon'] ?></span>
            <?= $nav['label'] ?>
        </a>
        <?php endforeach; ?>

        <div style="padding:6px 10px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:var(--text-muted);margin-top:6px">Settings</div>
        <a href="/books/<?= $currentBookId ?>/edit" class="nav-item <?= str_contains($uri,'/edit') ? 'active' : '' ?>">
            <svg viewBox="0 0 20 20" fill="currentColor" width="16" height="16"><path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"/></svg>
            Edit Book
        </a>

        <?php else: ?>
        <!-- Personal book nav -->
        <a href="/books/<?= $currentBookId ?>/contacts" class="nav-item <?= str_contains($uri,'/contacts') ? 'active' : '' ?>">
            <svg viewBox="0 0 20 20" fill="currentColor" width="16" height="16"><path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/></svg>
            Contacts
        </a>
        <a href="/books/<?= $currentBookId ?>/edit" class="nav-item <?= str_contains($uri,'/edit') ? 'active' : '' ?>">
            <svg viewBox="0 0 20 20" fill="currentColor" width="16" height="16"><path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"/></svg>
            Edit Book
        </a>
        <?php endif; ?>

        <?php else: ?>
        <!-- ── NOT IN A BOOK: normal top-level nav ── -->
        <a href="/dashboard" class="nav-item <?= activePage('dashboard') ?>">
            <svg viewBox="0 0 20 20" fill="currentColor" width="16" height="16"><path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h4a1 1 0 001-1v-3h2v3a1 1 0 001 1h4a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/></svg>
            Dashboard
        </a>
        <a href="/books" class="nav-item <?= activePage('books') ?>">
            <svg viewBox="0 0 20 20" fill="currentColor" width="16" height="16"><path d="M9 4.804A7.968 7.968 0 005.5 4c-1.255 0-2.443.29-3.5.804v10A7.969 7.969 0 015.5 14c1.669 0 3.218.51 4.5 1.385A7.962 7.962 0 0114.5 14c1.255 0 2.443.29 3.5.804v-10A7.968 7.968 0 0014.5 4c-1.255 0-2.443.29-3.5.804V12a1 1 0 11-2 0V4.804z"/></svg>
            My Books
        </a>
        <?php endif; ?>

    </nav>

    <div class="sidebar-bottom">
        <div class="sidebar-user">
            <div class="s-avatar"><?= mb_strtoupper(mb_substr(auth()['name'], 0, 1)) ?></div>
            <div class="s-user-info">
                <div class="s-user-name"><?= e(auth()['name']) ?></div>
                <div class="s-user-email"><?= e(auth()['email']) ?></div>
            </div>
        </div>
        <a href="/logout" class="nav-item nav-logout">
            <svg viewBox="0 0 20 20" fill="currentColor" width="16" height="16"><path fill-rule="evenodd" d="M3 3a1 1 0 00-1 1v12a1 1 0 102 0V4a1 1 0 00-1-1zm10.293 9.293a1 1 0 001.414 1.414l3-3a1 1 0 000-1.414l-3-3a1 1 0 10-1.414 1.414L14.586 9H7a1 1 0 100 2h7.586l-1.293 1.293z" clip-rule="evenodd"/></svg>
            Log out
        </a>
    </div>
</aside>

<!-- ===================== MAIN ===================== -->
<div class="app-main">
    <div class="mobile-topbar">
        <button class="hamburger" onclick="document.getElementById('sidebar').classList.toggle('open')" aria-label="Menu">
            <span></span><span></span><span></span>
        </button>
        <span class="mobile-title"><?= e($pageTitle ?? 'Byabsayee') ?></span>
    </div>

    <div class="app-content">
        <?php if ($msg = flash('error')): ?>
            <div class="flash flash-error"><?= e($msg) ?></div>
        <?php endif; ?>
        <?php if ($msg = flash('success')): ?>
            <div class="flash flash-success"><?= e($msg) ?></div>
        <?php endif; ?>

        <?= $content ?? '' ?>
    </div>
</div>

<div class="sidebar-overlay" onclick="document.getElementById('sidebar').classList.remove('open')"></div>

<script src="<?= asset('js/app.js') ?>"></script>
</body>
</html>

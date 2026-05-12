<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? 'Byabsayee') ?></title>
    <link rel="icon" type="image/png" href="<?= asset('favicon.png') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Bengali:wght@400;600&family=DM+Sans:ital,wght@0,400;0,500;0,600;1,400&display=swap" rel="stylesheet">
    <script src="https://kit.fontawesome.com/86c0c1c09a.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
</head>
<body>

<?php
$uri           = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
$bookMatch     = [];
$inBook        = preg_match('#^/books/(\d+)#', $uri, $bookMatch);
$currentBookId = $inBook ? (int)$bookMatch[1] : null;

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

// Safe str_contains that handles null — recomputes URI directly (global scope not reliable in requires)
function navActive(string $path): string {
    $u = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
    return str_contains((string)$u, $path) ? 'active' : '';
}
?>

<!-- ===================== SIDEBAR ===================== -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-top">
        <a href="/dashboard" class="sidebar-logo">
            <div class="s-logo-icon">
                <img src="<?= asset('assets/images/ByabsayeeLogo.png') ?>"
                     onerror="this.parentElement.innerHTML='৳'"
                     style="width:20px;height:20px;object-fit:contain">
            </div>
            <span class="s-logo-text">Byabsayee</span>
        </a>
    </div>

    <nav class="sidebar-nav">

        <?php if ($sidebarBook): ?>

        <a href="/dashboard" class="nav-item nav-back">
            <i class="fa-solid fa-arrow-left"></i> All Books
        </a>

        <div class="sidebar-book-info">
            <?php if (!empty($sidebarBook['logo'])): ?>
            <img src="<?= asset('uploads/'.$sidebarBook['logo']) ?>"
                 class="sidebar-book-logo" onerror="this.style.display='none'">
            <?php endif; ?>
            <div>
                <div class="sidebar-book-name">
                    <?= e($sidebarDetails['business_name'] ?? $sidebarBook['name']) ?>
                </div>
                <div class="sidebar-book-type">
                    <span class="book-dot" style="background:<?= e($sidebarBook['color']) ?>"></span>
                    <?= e(ucfirst($sidebarBook['type'])) ?> book
                </div>
            </div>
        </div>

        <a href="/books/<?= $currentBookId ?>"
           class="nav-item <?= preg_match('#^/books/'.$currentBookId.'$#', $uri) ? 'active' : '' ?>">
            <i class="fa-solid fa-gauge"></i> Dashboard
        </a>

        <?php if ($sidebarBook['type'] === 'business'): ?>

        <div class="sidebar-quick-btns">
            <a href="/books/<?= $currentBookId ?>/invoices/create?type=sale" class="quick-btn quick-btn-sell">
                <i class="fa-solid fa-plus"></i> Sell
            </a>
            <a href="/books/<?= $currentBookId ?>/invoices/create?type=purchase" class="quick-btn btn-secondary" style="border: 1px solid #cccccc;">
                <i class="fa-solid fa-cart-shopping"></i> Purchase
            </a>
        </div>

        <a href="/books/<?= $currentBookId ?>/invoices"    class="nav-item <?= navActive('/books/'.$currentBookId.'/invoices') ?>">     <i class="fa-solid fa-file-invoice"></i> Invoices</a>
        <a href="/books/<?= $currentBookId ?>/products"    class="nav-item <?= navActive('/books/'.$currentBookId.'/products') ?>">     <i class="fa-solid fa-box"></i> Products</a>
        <a href="/books/<?= $currentBookId ?>/funds"       class="nav-item <?= navActive('/books/'.$currentBookId.'/funds') ?>">        <i class="fa-solid fa-piggy-bank"></i> Funds</a>
        <a href="/books/<?= $currentBookId ?>/expenses"    class="nav-item <?= navActive('/books/'.$currentBookId.'/expenses') ?>">     <i class="fa-solid fa-receipt"></i> Expenses</a>
        <a href="/books/<?= $currentBookId ?>/dues"        class="nav-item <?= navActive('/books/'.$currentBookId.'/dues') ?>">         <i class="fa-solid fa-hand-holding-dollar"></i> Dues</a>
        <a href="/books/<?= $currentBookId ?>/debts"       class="nav-item <?= navActive('/books/'.$currentBookId.'/debts') ?>">        <i class="fa-solid fa-file-circle-minus"></i> Debts</a>
        <a href="/books/<?= $currentBookId ?>/customers"   class="nav-item <?= navActive('/books/'.$currentBookId.'/customers') ?>">    <i class="fa-solid fa-users"></i> Customers</a>
        <a href="/books/<?= $currentBookId ?>/suppliers"   class="nav-item <?= navActive('/books/'.$currentBookId.'/suppliers') ?>">    <i class="fa-solid fa-truck"></i> Suppliers</a>
        <a href="/books/<?= $currentBookId ?>/privileges"  class="nav-item <?= navActive('/books/'.$currentBookId.'/privileges') ?>">   <i class="fa-solid fa-star"></i> Privileges</a>
        <a href="/books/<?= $currentBookId ?>/deliveries"  class="nav-item <?= navActive('/books/'.$currentBookId.'/deliveries') ?>">   <i class="fa-solid fa-truck-fast"></i> Deliveries</a>
        <a href="/books/<?= $currentBookId ?>/employees"   class="nav-item <?= navActive('/books/'.$currentBookId.'/employees') ?>">    <i class="fa-solid fa-id-badge"></i> Employees</a>
        <a href="/books/<?= $currentBookId ?>/reports"     class="nav-item <?= navActive('/books/'.$currentBookId.'/reports') ?>">      <i class="fa-solid fa-chart-line"></i> Reports</a>

        <?php else: ?>
        <a href="/books/<?= $currentBookId ?>/contacts" class="nav-item <?= navActive('/books/'.$currentBookId.'/contacts') ?>">
            <i class="fa-solid fa-address-book"></i> Contacts
        </a>
        <?php endif; ?>

        <a href="/books/<?= $currentBookId ?>/edit" class="nav-item <?= navActive('/books/'.$currentBookId.'/edit') ?>">
            <i class="fa-solid fa-gear"></i> Book Settings
        </a>

        <?php else: ?>

        <div class="sidebar-search-wrap">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" id="bookFilterInput" placeholder="Find a book…"
                   oninput="filterBooks(this.value)">
        </div>

        <a href="/dashboard" class="nav-item <?= activePage('dashboard') ?>">
            <i class="fa-solid fa-gauge"></i> Dashboard
        </a>

        <?php endif; ?>

    </nav>

    <div class="sidebar-bottom">
        <a href="/settings" class="nav-item">
            <i class="fa-solid fa-sliders"></i> App Settings
        </a>
        <div class="sidebar-user">
            <div class="s-avatar"><?= mb_strtoupper(mb_substr(auth()['name'] ?? 'U', 0, 1)) ?></div>
            <div class="s-user-info">
                <div class="s-user-name"><?= e(auth()['name'] ?? '') ?></div>
                <div class="s-user-email"><?= e(auth()['email'] ?? '') ?></div>
            </div>
            <a href="/logout" title="Log out" class="s-logout-btn">
                <i class="fa-solid fa-right-from-bracket"></i>
            </a>
        </div>
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
            <div class="flash flash-error"><i class="fa-solid fa-circle-xmark"></i> <?= e($msg) ?></div>
        <?php endif; ?>
        <?php if ($msg = flash('success')): ?>
            <div class="flash flash-success"><i class="fa-solid fa-circle-check"></i> <?= e($msg) ?></div>
        <?php endif; ?>
        <?php if ($msg = flash('warning')): ?>
            <div class="flash flash-warning"><i class="fa-solid fa-triangle-exclamation"></i> <?= e($msg) ?></div>
        <?php endif; ?>

        <?= $content ?? '' ?>
    </div>
</div>

<div class="sidebar-overlay" onclick="document.getElementById('sidebar').classList.remove('open')"></div>

<!-- ===================== NOTIFICATION PANEL ===================== -->
<div class="notif-backdrop" id="notifBackdrop" onclick="closeNotifPanel(event)">
    <div class="notif-panel">
        <div class="notif-panel-header">
            <span><i class="fa-solid fa-bell"></i> Notifications</span>
            <button onclick="document.getElementById('notifBackdrop').classList.remove('open')" class="notif-close">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="notif-panel-body" id="notifPanelBody">
            <div class="notif-loading"><i class="fa-solid fa-spinner fa-spin"></i> Loading…</div>
        </div>
    </div>
</div>

<script src="<?= asset('js/app.js') ?>"></script>
<script>
function filterBooks(q) {
    q = q.toLowerCase();
    document.querySelectorAll('.book-card, .book-row').forEach(el => {
        el.style.display = (!q || el.textContent.toLowerCase().includes(q)) ? '' : 'none';
    });
}

let notifLoaded = false;
function openNotifPanel(e) {
    if (e) e.preventDefault();
    document.getElementById('notifBackdrop').classList.add('open');
    if (notifLoaded) return;
    const bookId = <?= $currentBookId ? (int)$currentBookId : 'null' ?>;
    if (!bookId) {
        document.getElementById('notifPanelBody').innerHTML = '<div class="notif-empty">No notifications.</div>';
        return;
    }
    fetch('/books/' + bookId + '/notifications?json=1')
        .then(r => r.ok ? r.json() : [])
        .then(data => {
            notifLoaded = true;
            const body = document.getElementById('notifPanelBody');
            if (!data.length) {
                body.innerHTML = '<div class="notif-empty"><i class="fa-regular fa-bell-slash"></i><br>No notifications yet.</div>';
                return;
            }
            body.innerHTML = data.map(n =>
                `<div class="notif-item notif-${n.type}">
                    <div class="notif-item-title">${escHtml(n.title)}</div>
                    ${n.body ? `<div class="notif-item-body">${escHtml(n.body)}</div>` : ''}
                    <div class="notif-item-meta">${escHtml(n.sender_name||'System')} · ${escHtml(n.created_at||'')}</div>
                </div>`
            ).join('');
        })
        .catch(() => {
            document.getElementById('notifPanelBody').innerHTML = '<div class="notif-empty">Could not load.</div>';
        });
}
function closeNotifPanel(e) {
    if (e && e.target !== document.getElementById('notifBackdrop')) return;
    document.getElementById('notifBackdrop').classList.remove('open');
}
function escHtml(s) {
    const d = document.createElement('div');
    d.appendChild(document.createTextNode(String(s)));
    return d.innerHTML;
}
</script>
</body>
</html>
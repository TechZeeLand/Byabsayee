<?php
// views/books/business.php
// NOTE: $book, $details, $stats are already set by BookController::showBusiness()
// We only add the extra data this view needs on top of that.

$pageTitle = e($details['business_name'] ?? $book['name']) . ' — Byabsayee';
ob_start();

$bookId = $book['id'];

// ── Currency symbol (safe — book_currencies exists from original schema) ──────
$defaultCur = \App\Helpers\Database::row(
    'SELECT symbol FROM book_currencies WHERE book_id=? AND is_default=1 LIMIT 1',
    [$bookId]
);
$sym = $defaultCur['symbol'] ?? '৳';

// ── Financial totals — only use tables that EXIST in the original schema ───────
// funds / expenses / dues may not exist yet (need schema-addons.sql)
// We try each one individually so a missing table only zeros that stat.
$totalIn  = (float)($stats['total_sales'] ?? 0);
$totalOut = (float)($stats['total_purchases'] ?? 0);
$totalDues = 0;
$totalDebts = 0;

try {
    $fundsIn = \App\Helpers\Database::row(
        "SELECT COALESCE(SUM(amount),0) AS n FROM funds WHERE book_id=? AND type='in'",
        [$bookId]
    );
    $totalIn += (float)($fundsIn['n'] ?? 0);
} catch (\Throwable $e) { /* funds table not yet created */ }

try {
    $fundsOut = \App\Helpers\Database::row(
        "SELECT COALESCE(SUM(amount),0) AS n FROM funds WHERE book_id=? AND type='out'",
        [$bookId]
    );
    $totalOut += (float)($fundsOut['n'] ?? 0);
} catch (\Throwable $e) { /* funds table not yet created */ }

try {
    $expTotal = \App\Helpers\Database::row(
        "SELECT COALESCE(SUM(amount),0) AS n FROM expenses WHERE book_id=?",
        [$bookId]
    );
    $totalOut += (float)($expTotal['n'] ?? 0);
} catch (\Throwable $e) { /* expenses table not yet created */ }

try {
    $duesTotal = \App\Helpers\Database::row(
        "SELECT COALESCE(SUM(amount - paid_amount),0) AS n FROM dues
         WHERE book_id=? AND status IN ('unpaid','partial')",
        [$bookId]
    );
    $totalDues = (float)($duesTotal['n'] ?? 0);
} catch (\Throwable $e) { /* dues table not yet created */ }

try {
    $debtsRow = \App\Helpers\Database::row(
        "SELECT COALESCE(SUM(amount - paid_amount),0) AS n FROM debts
         WHERE book_id=? AND status IN ('unpaid','partial')",
        [$bookId]
    );
    $totalDebts = (float)($debtsRow['n'] ?? 0);
} catch (\Throwable $e) { /* debts table not yet created */ }

try {
    $deliveriesRow = \App\Helpers\Database::row(
        "SELECT COUNT(*) AS n FROM deliveries WHERE book_id=?",
        [$bookId]
    );
    $stats['deliveries'] = (int)($deliveriesRow['n'] ?? 0);
} catch (\Throwable $e) { /* deliveries table not yet created */ }

$availableFunds = $totalIn - $totalOut;

// ── Build comprehensive activity from all available tables ────────────────────
$allActivity = [];

// Invoices
try {
    foreach (\App\Helpers\Database::query(
        "SELECT i.id, i.type AS t, i.total, i.created_at, u.name AS un
         FROM invoices i LEFT JOIN users u ON u.id=i.created_by
         WHERE i.book_id=? AND i.deleted_at IS NULL ORDER BY i.created_at DESC LIMIT 20", [$bookId]
    ) as $r) {
        $s = $r['t'] === 'sale';
        $allActivity[] = ['icon'=>'fa-file-invoice','icon_color'=>$s?'var(--green)':'var(--blue)',
            'description'=>($s?'Sale invoice':'Purchase invoice').' — '.format_money((float)$r['total'],$sym),
            'user_name'=>$r['un']??'System','created_at'=>$r['created_at'],
            'href'=>'/books/'.$bookId.'/invoices/'.$r['id']];
    }
} catch (\Throwable $e) {}

// Products added
try {
    foreach (\App\Helpers\Database::query(
        "SELECT id, name, created_at FROM products WHERE book_id=? AND deleted_at IS NULL ORDER BY created_at DESC LIMIT 20", [$bookId]
    ) as $r) {
        $allActivity[] = ['icon'=>'fa-box','icon_color'=>'var(--amber)',
            'description'=>'Product added — '.$r['name'],
            'user_name'=>'System','created_at'=>$r['created_at'],
            'href'=>'/books/'.$bookId.'/products'];
    }
} catch (\Throwable $e) {}

// Funds
try {
    foreach (\App\Helpers\Database::query(
        "SELECT f.id, f.type, f.title, f.amount, f.created_at, u.name AS un
         FROM funds f LEFT JOIN users u ON u.id=f.created_by
         WHERE f.book_id=? ORDER BY f.created_at DESC LIMIT 20", [$bookId]
    ) as $r) {
        $in = $r['type'] === 'in';
        $allActivity[] = ['icon'=>$in?'fa-arrow-trend-up':'fa-arrow-trend-down',
            'icon_color'=>$in?'var(--green)':'var(--red)',
            'description'=>($in?'Fund received':'Fund withdrawn').' — '.format_money((float)$r['amount'],$sym).($r['title']?' ('.$r['title'].')':''),
            'user_name'=>$r['un']??'System','created_at'=>$r['created_at'],
            'href'=>'/books/'.$bookId.'/funds'];
    }
} catch (\Throwable $e) {}

// Expenses
try {
    foreach (\App\Helpers\Database::query(
        "SELECT e.id, e.title, e.amount, e.created_at, u.name AS un
         FROM expenses e LEFT JOIN users u ON u.id=e.created_by
         WHERE e.book_id=? ORDER BY e.created_at DESC LIMIT 20", [$bookId]
    ) as $r) {
        $allActivity[] = ['icon'=>'fa-receipt','icon_color'=>'var(--red)',
            'description'=>'Expense — '.$r['title'].' — '.format_money((float)$r['amount'],$sym),
            'user_name'=>$r['un']??'System','created_at'=>$r['created_at'],
            'href'=>'/books/'.$bookId.'/expenses'];
    }
} catch (\Throwable $e) {}

// Dues
try {
    foreach (\App\Helpers\Database::query(
        "SELECT d.id, d.title, d.amount, d.created_at, u.name AS un
         FROM dues d LEFT JOIN users u ON u.id=d.created_by
         WHERE d.book_id=? ORDER BY d.created_at DESC LIMIT 20", [$bookId]
    ) as $r) {
        $allActivity[] = ['icon'=>'fa-hand-holding-dollar','icon_color'=>'var(--amber)',
            'description'=>'Due created — '.$r['title'].' — '.format_money((float)$r['amount'],$sym),
            'user_name'=>$r['un']??'System','created_at'=>$r['created_at'],
            'href'=>'/books/'.$bookId.'/dues'];
    }
} catch (\Throwable $e) {}

// Debts
try {
    foreach (\App\Helpers\Database::query(
        "SELECT d.id, d.title, d.amount, d.created_at, u.name AS un
         FROM debts d LEFT JOIN users u ON u.id=d.created_by
         WHERE d.book_id=? ORDER BY d.created_at DESC LIMIT 20", [$bookId]
    ) as $r) {
        $allActivity[] = ['icon'=>'fa-file-circle-minus','icon_color'=>'var(--red)',
            'description'=>'Debt recorded — '.$r['title'].' — '.format_money((float)$r['amount'],$sym),
            'user_name'=>$r['un']??'System','created_at'=>$r['created_at'],
            'href'=>'/books/'.$bookId.'/debts'];
    }
} catch (\Throwable $e) {}

// Customers
try {
    foreach (\App\Helpers\Database::query(
        "SELECT id, name, created_at FROM customers WHERE book_id=? AND deleted_at IS NULL ORDER BY created_at DESC LIMIT 20", [$bookId]
    ) as $r) {
        $allActivity[] = ['icon'=>'fa-user-plus','icon_color'=>'var(--blue)',
            'description'=>'Customer added — '.$r['name'],
            'user_name'=>'System','created_at'=>$r['created_at'],
            'href'=>'/books/'.$bookId.'/customers/'.$r['id']];
    }
} catch (\Throwable $e) {}

// Suppliers
try {
    foreach (\App\Helpers\Database::query(
        "SELECT id, name, created_at FROM suppliers WHERE book_id=? AND deleted_at IS NULL ORDER BY created_at DESC LIMIT 20", [$bookId]
    ) as $r) {
        $allActivity[] = ['icon'=>'fa-truck','icon_color'=>'#8b5cf6',
            'description'=>'Supplier added — '.$r['name'],
            'user_name'=>'System','created_at'=>$r['created_at'],
            'href'=>'/books/'.$bookId.'/suppliers/'.$r['id']];
    }
} catch (\Throwable $e) {}

// Privileges
try {
    foreach (\App\Helpers\Database::query(
        "SELECT id, name, created_at FROM customer_privileges WHERE book_id=? ORDER BY created_at DESC LIMIT 20", [$bookId]
    ) as $r) {
        $allActivity[] = ['icon'=>'fa-star','icon_color'=>'var(--amber)',
            'description'=>'Privilege added — '.$r['name'],
            'user_name'=>'System','created_at'=>$r['created_at'],
            'href'=>'/books/'.$bookId.'/privileges'];
    }
} catch (\Throwable $e) {}

// Sort all by time desc, cap at 20
usort($allActivity, fn($a,$b) => strcmp($b['created_at']??'',$a['created_at']??''));
$recentActivity = array_slice($allActivity, 0, 20);

?>

<!-- ── Header ──────────────────────────────────────────────────────────── -->
<div class="biz-header">
    <div class="biz-header-left">
        <?php if (!empty($book['logo'])): ?>
        <img src="<?= asset('uploads/' . $book['logo']) ?>"
             class="biz-logo" onerror="this.style.display='none'" alt="">
        <?php endif; ?>
        <div>
            <h1 class="biz-title">
                <span class="biz-dot" style="background:<?= e($book['color']) ?>"></span>
                <?= e($details['business_name'] ?? $book['name']) ?>
            </h1>
            <div class="biz-sub">Business Book</div>
        </div>
    </div>
    <div class="biz-header-actions">
        <a href="/books/<?= $bookId ?>/invoices/create?type=sale" class="btn btn-primary">
            <i class="fa-solid fa-plus"></i> Sell
        </a>
        <a href="/books/<?= $bookId ?>/invoices/create?type=purchase" class="btn btn-secondary">
            <i class="fa-solid fa-cart-shopping"></i> Purchase
        </a>
        <button class="biz-notif-btn" onclick="openNotifPanel(event)" title="Notifications">
            <i class="fa-solid fa-bell"></i>
        </button>
    </div>
</div>

<!-- ── Financial stat row ─────────────────────────────────────────────── -->
 <p class="section-label"><i class="fa-solid fa-rectangle-list"></i> Summary</p>
<div class="dash-stat-grid" style="margin-bottom:12px">

    <a href="/books/<?= $bookId ?>/funds" class="stat-card stat-card-link"
       style="border-top:3px solid <?= $availableFunds >= 0 ? 'var(--brand)' : 'var(--red)' ?>">
        <div class="stat-card-icon"
             style="background:<?= $availableFunds >= 0 ? 'var(--brand-light)' : 'var(--red-bg)' ?>;
                    color:<?= $availableFunds >= 0 ? 'var(--brand)' : 'var(--red)' ?>">
            <i class="fa-solid fa-wallet"></i>
        </div>
        <div style="min-width:0;flex:1">
            <div class="stat-label">Available Funds</div>
            <div class="stat-value <?= $availableFunds >= 0 ? 'brand' : 'red' ?>">
                <?= format_money($availableFunds, $sym) ?>
            </div>
        </div>
    </a>

    <a href="/books/<?= $bookId ?>/funds" class="stat-card stat-card-link"
       style="border-top:3px solid var(--green)">
        <div class="stat-card-icon" style="background:var(--green-bg);color:var(--green)">
            <i class="fa-solid fa-arrow-trend-up"></i>
        </div>
        <div style="min-width:0;flex:1">
            <div class="stat-label">Total In</div>
            <div class="stat-value green"><?= format_money($totalIn, $sym) ?></div>
        </div>
    </a>

    <a href="/books/<?= $bookId ?>/expenses" class="stat-card stat-card-link"
       style="border-top:3px solid var(--red)">
        <div class="stat-card-icon" style="background:var(--red-bg);color:var(--red)">
            <i class="fa-solid fa-arrow-trend-down"></i>
        </div>
        <div style="min-width:0;flex:1">
            <div class="stat-label">Total Out</div>
            <div class="stat-value red"><?= format_money($totalOut, $sym) ?></div>
        </div>
    </a>

    <a href="/books/<?= $bookId ?>/dues" class="stat-card stat-card-link"
       style="border-top:3px solid var(--amber)">
        <div class="stat-card-icon" style="background:var(--amber-bg);color:var(--amber)">
            <i class="fa-solid fa-hand-holding-dollar"></i>
        </div>
        <div style="min-width:0;flex:1">
            <div class="stat-label">Total Dues</div>
            <div class="stat-value amber"><?= format_money($totalDues, $sym) ?></div>
        </div>
    </a>

    <a href="/books/<?= $bookId ?>/debts" class="stat-card stat-card-link"
       style="border-top:3px solid var(--red)">
        <div class="stat-card-icon" style="background:var(--red-bg);color:var(--red)">
            <i class="fa-solid fa-file-circle-minus"></i>
        </div>
        <div style="min-width:0;flex:1">
            <div class="stat-label">Total Debts</div>
            <div class="stat-value red"><?= format_money($totalDebts, $sym) ?></div>
        </div>
    </a>

</div>

<!-- ── Count row ──────────────────────────────────────────────────────── -->
<div class="dash-count-grid" style="margin-bottom:22px">
    <a href="/books/<?= $bookId ?>/invoices" class="count-card">
        <i class="fa-solid fa-file-invoice" style="color: #10b981"></i>
        <span class="count-val"><?= (int)($stats['invoices'] ?? 0) ?></span>
        <span class="count-label">Invoices</span>
    </a>
    <a href="/books/<?= $bookId ?>/products" class="count-card">
        <i class="fa-solid fa-box" style="color: #f59e0b"></i>
        <span class="count-val"><?= (int)($stats['products'] ?? 0) ?></span>
        <span class="count-label">Products</span>
    </a>
    <a href="/books/<?= $bookId ?>/customers" class="count-card">
        <i class="fa-solid fa-users" style="color: #3b82f6"></i>
        <span class="count-val"><?= (int)($stats['customers'] ?? 0) ?></span>
        <span class="count-label">Customers</span>
    </a>
    <a href="/books/<?= $bookId ?>/suppliers" class="count-card">
        <i class="fa-solid fa-user-tie" style="color: #8b5cf6"></i>
        <span class="count-val"><?= (int)($stats['suppliers'] ?? 0) ?></span>
        <span class="count-label">Suppliers</span>
    </a>
    <a href="/books/<?= $bookId ?>/deliveries" class="count-card">
        <i class="fa-solid fa-truck-fast" style="color: #0d9488"></i>
        <span class="count-val"><?= (int)($stats['deliveries'] ?? 0) ?></span>
        <span class="count-label">Deliveries</span>
    </a>
    <a href="/books/<?= $bookId ?>/employees" class="count-card">
        <i class="fa-solid fa-id-badge" style="color: #6366f1"></i>
        <span class="count-val"><?= (int)($stats['employees'] ?? 0) ?></span>
        <span class="count-label">Employees</span>
    </a>
</div>

<!-- ── Modules ──────────────────────────────────────────────────────────── -->
<p class="section-label"><i class="fa-solid fa-grip"></i> Modules</p>
<div class="modules-grid" style="margin-bottom:22px">
<?php
$modules = [
    ['icon'=>'fa-file-invoice',       'color'=>'#10b981','label'=>'Invoices',  'sub'=>'Add, Edit, Delete',  'url'=>'/books/'.$bookId.'/invoices'],
    ['icon'=>'fa-box',                'color'=>'#f59e0b','label'=>'Products',  'sub'=>'Add, Edit, Remove',  'url'=>'/books/'.$bookId.'/products'],
    ['icon'=>'fa-piggy-bank',         'color'=>'#0ea5e9','label'=>'Funds',     'sub'=>'Add or Withdraw',    'url'=>'/books/'.$bookId.'/funds'],
    ['icon'=>'fa-receipt',            'color'=>'#ef4444','label'=>'Expenses',  'sub'=>'Various Costs',      'url'=>'/books/'.$bookId.'/expenses'],
    ['icon'=>'fa-hand-holding-dollar','color'=>'#d97706','label'=>'Dues',      'sub'=>'Owed by Others',     'url'=>'/books/'.$bookId.'/dues'],
    ['icon'=>'fa-file-circle-minus',  'color'=>'#dc2626','label'=>'Debts',     'sub'=>'Owed to Others',     'url'=>'/books/'.$bookId.'/debts'],
    ['icon'=>'fa-users',              'color'=>'#3b82f6','label'=>'Customers', 'sub'=>'Add, Edit, Remove',  'url'=>'/books/'.$bookId.'/customers'],
    ['icon'=>'fa-user-tie',              'color'=>'#8b5cf6','label'=>'Suppliers', 'sub'=>'Add, Edit, Remove',  'url'=>'/books/'.$bookId.'/suppliers'],
    ['icon'=>'fa-address-book',       'color'=>'#f97316','label'=>'Contacts',  'sub'=>'Everyone Known',     'url'=>'/books/'.$bookId.'/contacts'],
    ['icon'=>'fa-truck-fast',         'color'=>'#0d9488','label'=>'Deliveries','sub'=>'Track Deliveries',   'url'=>'/books/'.$bookId.'/deliveries'],
    ['icon'=>'fa-id-badge',           'color'=>'#6366f1','label'=>'Employees', 'sub'=>'HR & Payroll',       'url'=>'/books/'.$bookId.'/employees'],
    ['icon'=>'fa-chart-line',         'color'=>'#14b8a6','label'=>'Reports',   'sub'=>'Profit & Loss',      'url'=>'/books/'.$bookId.'/reports'],
];
foreach ($modules as $m): ?>
<a href="<?= $m['url'] ?>" class="module-card <?= !empty($m['soon']) ? 'module-soon' : '' ?>">
    <div class="module-icon" style="background:<?= $m['color'] ?>22;color:<?= $m['color'] ?>">
        <i class="fa-solid <?= $m['icon'] ?>"></i>
    </div>
    <div class="module-body">
        <div class="module-label">
            <?= $m['label'] ?>
        </div>
        <div class="module-sub"><?= $m['sub'] ?></div>
    </div>
</a>
<?php endforeach; ?>
</div>

<!-- ── Search ──────────────────────────────────────────────────────────── -->
 <p class="section-label"><i class="fa-solid fa-list"></i> Discover</p>
<div style="margin-bottom:22px;position:relative;max-width:520px">
    <div style="position:relative">
        <i class="fa-solid fa-magnifying-glass"
           style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--text-muted);font-size:13px;pointer-events:none"></i>
        <input type="text" id="bookSearch"
               placeholder="Search customers, products, invoices, suppliers…"
               oninput="doBookSearch(this.value)" autocomplete="off"
               style="width:100%;padding:10px 12px 10px 36px;border:2px solid var(--border);
                      border-radius:var(--radius);font-size:13.5px;font-family:inherit;
                      outline:none;transition:border-color .15s"
               onfocus="this.style.borderColor='var(--brand)'"
               onblur="this.style.borderColor='var(--border)'">
    </div>
    <div id="searchResults"
         style="display:none;position:absolute;top:calc(100% + 4px);left:0;right:0;
                background:var(--white);border:1px solid var(--border);border-radius:var(--radius);
                box-shadow:var(--shadow-md);max-height:280px;overflow-y:auto;z-index:200"></div>
</div>

<!-- ── Recent Activity ────────────────────────────────────────────────── -->
<div style="background:var(--white);border:1px solid var(--border);border-radius:var(--radius-lg);overflow:hidden">
    <div style="padding:14px 18px;border-bottom:1px solid var(--border);
                display:flex;align-items:center;justify-content:space-between">
        <h3 style="font-size:14px;font-weight:700;display:flex;align-items:center;gap:8px;margin:0">
            <i class="fa-solid fa-clock-rotate-left" style="color:var(--brand)"></i>
            Recent Activity
        </h3>
        <span style="font-size:12px;color:var(--text-muted)">Last 20 entries</span>
    </div>
    <div style="padding:4px 18px 12px">
        <?php if (empty($recentActivity)): ?>
        <div class="empty-state" style="padding:28px 0">
            <div class="empty-icon"><i class="fa-solid fa-clock"></i></div>
            <p>No activities yet...</p>
        </div>
        <?php else: ?>
        <div class="activity-feed">
            <?php foreach ($recentActivity as $act): ?>
            <a href="<?= $act['href'] ?? '#' ?>" class="activity-link" style="text-decoration: none;">
            <div class="activity-item">
                <div class="activity-icon" style="color:<?= $act['icon_color'] ?? 'var(--text-muted)' ?>">
                    <i class="fa-solid <?= $act['icon'] ?? 'fa-circle-dot' ?>"></i>
                </div>
                <div class="activity-body">
                    <div class="activity-desc"><?= e($act['description'] ?? $act['action'] ?? 'Activity') ?></div>
                    <div class="activity-meta">
                        <strong><?= e($act['user_name'] ?? 'System') ?></strong>
                        · <?= e(date('d M Y, g:i a', strtotime($act['created_at']))) ?>
                    </div>
                </div>
            </div>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- ── Styles ─────────────────────────────────────────────────────────── -->
<style>
.biz-header{display:flex;align-items:center;justify-content:space-between;gap:14px;margin-bottom:22px;flex-wrap:wrap}
.biz-header-left{display:flex;align-items:center;gap:12px;flex:1;min-width:0}
.biz-logo{height:40px;max-width:100px;object-fit:contain;border-radius:6px}
.biz-title{font-size:20px;font-weight:700;display:flex;align-items:center;gap:8px;margin:0}
.biz-dot{display:inline-block;width:10px;height:10px;border-radius:50%;flex-shrink:0}
.biz-sub{font-size:12px;color:var(--text-muted);margin-top:2px}
.biz-header-actions{display:flex;align-items:center;gap:8px;flex-wrap:wrap}
.biz-notif-btn{width:36px;height:36px;border-radius:var(--radius);border:1.5px solid var(--border);background:var(--white);color:var(--text-muted);cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:15px;transition:background .12s,color .12s;flex-shrink:0}
.biz-notif-btn:hover{background:var(--bg);color:var(--brand)}
.dash-stat-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(170px,1fr));gap:12px}
.stat-card-link{display:flex;align-items:center;gap:12px;text-decoration:none;color:inherit;transition:box-shadow .15s,transform .15s}
.stat-card-link:hover{box-shadow:var(--shadow-md);transform:translateY(-1px)}
.stat-card-icon{width:38px;height:38px;border-radius:var(--radius);display:flex;align-items:center;justify-content:center;font-size:16px;flex-shrink:0}
.dash-count-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(110px,1fr));gap:10px}
.count-card{background:var(--white);border:1px solid var(--border);border-radius:var(--radius);padding:12px 14px;display:flex;flex-direction:column;align-items:center;gap:4px;text-decoration:none;color:inherit;transition:box-shadow .15s,transform .1s;text-align:center}
.count-card:hover{box-shadow:var(--shadow-md);transform:translateY(-1px)}
.count-card i{font-size:18px;margin-bottom:2px}
.count-val{font-size:18px;font-weight:700;color:var(--text)}
.count-label{font-size:11px;color:var(--text-muted);font-weight:500}
.modules-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(185px,1fr));gap:10px}
.module-card{background:var(--white);border:1px solid var(--border);border-radius:var(--radius);padding:13px 15px;display:flex;align-items:center;gap:12px;text-decoration:none;color:inherit;transition:box-shadow .15s,transform .1s}
.module-card:hover{box-shadow:var(--shadow-md);transform:translateY(-1px)}
.module-icon{width:38px;height:38px;border-radius:var(--radius-sm);display:flex;align-items:center;justify-content:center;font-size:16px;flex-shrink:0}
.module-label{font-size:13.5px;font-weight:600;display:flex;align-items:center;gap:6px}
.module-sub{font-size:11.5px;color:var(--text-muted);margin-top:1px}
</style>

<script>
let _searchTimer;
function doBookSearch(q) {
    clearTimeout(_searchTimer);
    const box = document.getElementById('searchResults');
    if (!q.trim()) { box.style.display = 'none'; return; }
    _searchTimer = setTimeout(() => {
        fetch('/books/<?= (int)$bookId ?>/search?q=' + encodeURIComponent(q))
            .then(r => r.json())
            .then(data => {
                if (!data.results || !data.results.length) { box.style.display = 'none'; return; }
                box.innerHTML = data.results.map(r =>
                    `<a href="${r.url}"
                        style="display:flex;align-items:center;gap:10px;padding:10px 14px;
                               text-decoration:none;color:inherit;border-bottom:1px solid var(--border)"
                        onmouseover="this.style.background='var(--bg)'"
                        onmouseout="this.style.background=''">
                        <span style="font-size:10px;font-weight:700;padding:2px 7px;border-radius:10px;
                                     background:var(--bg);color:var(--text-muted);white-space:nowrap">
                            ${escHtml(r.type)}
                        </span>
                        <span style="font-size:13px">${escHtml(r.label)}</span>
                    </a>`
                ).join('');
                box.style.display = 'block';
            })
            .catch(() => { box.style.display = 'none'; });
    }, 200);
}
document.addEventListener('click', e => {
    if (!e.target.closest('#bookSearch') && !e.target.closest('#searchResults'))
        document.getElementById('searchResults').style.display = 'none';
});
function escHtml(s) {
    const d = document.createElement('div');
    d.appendChild(document.createTextNode(String(s)));
    return d.innerHTML;
}
</script>

<?php $content = ob_get_clean(); require BASE_PATH . '/views/partials/layout.php'; ?>
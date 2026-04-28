<?php
$pageTitle = e($book['name']) . ' — Byabsayee';
ob_start();
?>

<div class="page-header">
    <div class="page-header-left">
        <div class="breadcrumb">
            <a href="/dashboard">Dashboard</a> <span>›</span>
            <span><?= e($book['name']) ?></span>
        </div>
        <h1 style="display:flex;align-items:center;gap:10px">
            <span style="display:inline-block;width:12px;height:12px;border-radius:50%;background:<?= e($book['color']) ?>;flex-shrink:0"></span>
            <?= e($details['business_name'] ?? $book['name']) ?>
        </h1>
        <p>Business book</p>
    </div>
    <a href="/books/<?= $book['id'] ?>/edit" class="btn btn-secondary">
        <svg viewBox="0 0 20 20" fill="currentColor" width="14" height="14"><path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"/></svg>
        Edit Book
    </a>
</div>

<!-- Stats -->
<div class="stat-grid">
    <div class="stat-card">
        <div class="stat-label">Total Sales</div>
        <div class="stat-value green"><?= format_money($stats['total_sales']) ?></div>
        <div class="stat-sub"><?= $stats['invoices'] ?> invoice<?= $stats['invoices'] != 1 ? 's' : '' ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Total Purchases</div>
        <div class="stat-value red"><?= format_money($stats['total_purchases']) ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Customers</div>
        <div class="stat-value brand"><?= $stats['customers'] ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Products</div>
        <div class="stat-value brand"><?= $stats['products'] ?></div>
    </div>
</div>

<!-- Module grid -->
<p class="section-label">Modules</p>

<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:12px">
<?php
$modules = [
    ['icon'=>'👥', 'label'=>'Customers',  'sub'=>$stats['customers'].' customers',  'url'=>'/books/'.$book['id'].'/customers',  'color'=>'#3b82f6'],
    ['icon'=>'🏭', 'label'=>'Suppliers',  'sub'=>$stats['suppliers'].' suppliers',  'url'=>'/books/'.$book['id'].'/suppliers',  'color'=>'#8b5cf6'],
    ['icon'=>'📦', 'label'=>'Products',   'sub'=>$stats['products'].' products',    'url'=>'/books/'.$book['id'].'/products',   'color'=>'#f59e0b'],
    ['icon'=>'🧾', 'label'=>'Invoices',   'sub'=>$stats['invoices'].' invoices',    'url'=>'/books/'.$book['id'].'/invoices',   'color'=>'#10b981'],
    ['icon'=>'💸', 'label'=>'Expenses',   'sub'=>'Other expenses',                  'url'=>'/books/'.$book['id'].'/expenses',   'color'=>'#ef4444'],
    ['icon'=>'👔', 'label'=>'Employees',  'sub'=>'HR & payroll',                    'url'=>'/books/'.$book['id'].'/employees',  'color'=>'#6366f1'],
    ['icon'=>'🚚', 'label'=>'Deliveries', 'sub'=>'Track deliveries',                'url'=>'/books/'.$book['id'].'/deliveries', 'color'=>'#0ea5e9'],
    ['icon'=>'🎫', 'label'=>'Privileges', 'sub'=>'Customer discounts',              'url'=>'/books/'.$book['id'].'/privileges', 'color'=>'#f97316'],
    ['icon'=>'📊', 'label'=>'Reports',    'sub'=>'Profit, loss & more',             'url'=>'/books/'.$book['id'].'/reports',    'color'=>'#14b8a6'],
];
foreach ($modules as $m): ?>
<a href="<?= $m['url'] ?>" class="module-card">
    <div class="module-icon" style="background:<?= $m['color'] ?>22;color:<?= $m['color'] ?>"><?= $m['icon'] ?></div>
    <div>
        <div class="module-label"><?= $m['label'] ?></div>
        <div class="module-sub"><?= $m['sub'] ?></div>
    </div>
</a>
<?php endforeach; ?>
</div>

<style>
.module-card {
    background:var(--white);border:1px solid var(--border);border-radius:var(--radius-lg);
    padding:16px;display:flex;align-items:center;gap:14px;
    text-decoration:none;color:inherit;transition:box-shadow .15s,transform .1s;
}
.module-card:hover { box-shadow:0 4px 16px rgba(0,0,0,.07);transform:translateY(-1px); }
.module-icon { width:42px;height:42px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:20px;flex-shrink:0; }
.module-label { font-size:14px;font-weight:600; }
.module-sub   { font-size:12px;color:var(--text-muted);margin-top:2px; }
</style>

<?php $content = ob_get_clean(); require BASE_PATH . '/views/partials/layout.php'; ?>

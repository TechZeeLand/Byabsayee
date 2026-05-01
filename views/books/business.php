<?php
$pageTitle = e($details['business_name'] ?? $book['name']) . ' — Byabsayee';
ob_start();
?>

<div class="page-header">
    <div class="page-header-left">
        <div class="breadcrumb">
            <a href="/dashboard">← All Books</a>
        </div>
        <h1 style="display:flex;align-items:center;gap:10px">
            <?php if (!empty($book['logo'])): ?>
            <img src="<?= asset('uploads/'.$book['logo']) ?>"
                 style="height:32px;max-width:80px;object-fit:contain;border-radius:4px"
                 onerror="this.style.display='none'">
            <?php endif; ?>
            <span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:<?= e($book['color']) ?>;flex-shrink:0"></span>
            <?= e($details['business_name'] ?? $book['name']) ?>
        </h1>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
        <a href="/books/<?= $book['id'] ?>/pos" class="btn btn-primary">
            🖨 Quick Sale
        </a>
        <a href="/books/<?= $book['id'] ?>/invoices/create?type=sale" class="btn btn-secondary">
            + Sale Invoice
        </a>
        <a href="/books/<?= $book['id'] ?>/edit" class="btn btn-secondary">Edit</a>
    </div>
</div>

<!-- Search bar -->
<div style="margin-bottom:20px;position:relative">
    <input type="text" id="bookSearch"
           placeholder="🔍  Search customers, products, invoices, suppliers…"
           oninput="doBookSearch(this.value)"
           autocomplete="off"
           style="width:100%;max-width:560px;padding:10px 16px;border:2px solid var(--border);border-radius:10px;font-size:14px;font-family:inherit;outline:none;transition:border-color .15s"
           onfocus="this.style.borderColor='var(--brand)'"
           onblur="this.style.borderColor='var(--border)'">
    <div id="searchResults"
         style="display:none;position:absolute;top:calc(100% + 4px);left:0;width:560px;max-height:300px;overflow-y:auto;background:#fff;border:1px solid var(--border);border-radius:10px;box-shadow:0 8px 24px rgba(0,0,0,.10);z-index:200">
    </div>
</div>

<!-- Stats — no global income/expense, just book-specific -->
<div class="stat-grid" style="grid-template-columns:repeat(auto-fit,minmax(140px,1fr));max-width:800px;margin-bottom:24px">
    <div class="stat-card">
        <div class="stat-label">Total Sales</div>
        <div class="stat-value green"><?= format_money($stats['total_sales']) ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Purchases</div>
        <div class="stat-value red"><?= format_money($stats['total_purchases']) ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Customers</div>
        <div class="stat-value brand"><?= $stats['customers'] ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Suppliers</div>
        <div class="stat-value brand"><?= $stats['suppliers'] ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Products</div>
        <div class="stat-value brand"><?= $stats['products'] ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Invoices</div>
        <div class="stat-value brand"><?= $stats['invoices'] ?></div>
    </div>
</div>

<!-- Modules -->
<p class="section-label">Modules</p>
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(190px,1fr));gap:12px">
<?php
$modules = [
    ['icon'=>'👥', 'label'=>'Customers',  'sub'=>$stats['customers'].' customers',  'url'=>'/books/'.$book['id'].'/customers',  'color'=>'#3b82f6'],
    ['icon'=>'🏭', 'label'=>'Suppliers',  'sub'=>$stats['suppliers'].' suppliers',  'url'=>'/books/'.$book['id'].'/suppliers',  'color'=>'#8b5cf6'],
    ['icon'=>'📦', 'label'=>'Products',   'sub'=>$stats['products'].' in stock',    'url'=>'/books/'.$book['id'].'/products',   'color'=>'#f59e0b'],
    ['icon'=>'🧾', 'label'=>'Invoices',   'sub'=>$stats['invoices'].' invoices',    'url'=>'/books/'.$book['id'].'/invoices',   'color'=>'#10b981'],
    ['icon'=>'🖨',  'label'=>'POS',        'sub'=>'Quick in-person sale',            'url'=>'/books/'.$book['id'].'/pos',        'color'=>'#059669'],
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
.module-card{background:var(--white);border:1px solid var(--border);border-radius:var(--radius-lg);padding:14px 16px;display:flex;align-items:center;gap:14px;text-decoration:none;color:inherit;transition:box-shadow .15s,transform .1s}
.module-card:hover{box-shadow:0 4px 16px rgba(0,0,0,.07);transform:translateY(-1px)}
.module-icon{width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0}
.module-label{font-size:14px;font-weight:600}
.module-sub{font-size:12px;color:var(--text-muted);margin-top:1px}
</style>

<script>
let searchTimer;
function doBookSearch(q) {
    clearTimeout(searchTimer);
    const box = document.getElementById('searchResults');
    if (!q.trim()) { box.style.display='none'; return; }
    searchTimer = setTimeout(() => {
        fetch('/books/<?= $book['id'] ?>/search?q='+encodeURIComponent(q))
            .then(r => r.json())
            .then(data => {
                if (!data.results || !data.results.length) {
                    box.style.display='none'; return;
                }
                box.innerHTML = data.results.map(r => `
                    <a href="${r.url}" style="display:flex;align-items:center;gap:10px;padding:10px 14px;text-decoration:none;color:inherit;border-bottom:1px solid var(--border)"
                       onmouseover="this.style.background='var(--bg)'" onmouseout="this.style.background=''">
                        <span style="font-size:11px;font-weight:600;padding:2px 7px;border-radius:10px;background:var(--bg);color:var(--text-muted);white-space:nowrap">${r.type}</span>
                        <span style="font-size:13px">${r.label}</span>
                    </a>`
                ).join('');
                box.style.display='block';
            });
    }, 200);
}

document.addEventListener('click', e => {
    if (!e.target.closest('#bookSearch') && !e.target.closest('#searchResults'))
        document.getElementById('searchResults').style.display='none';
});
</script>

<?php $content = ob_get_clean(); require BASE_PATH . '/views/partials/layout.php'; ?>

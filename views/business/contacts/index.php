<?php
$pageTitle = 'Contacts — ' . e($book['name']);
ob_start();
?>

<div class="page-header">
    <div class="page-header-left">
        <div class="breadcrumb">
            <a href="/books/<?= $book['id'] ?>">Dashboard</a> <span>›</span>
            <span>Contacts</span>
        </div>
        <h1><i class="fa-solid fa-address-book" style="color:var(--brand)"></i> Contacts</h1>
        <p><?= $totalCount ?> contact<?= $totalCount !== 1 ? 's' : '' ?> across all types</p>
    </div>
</div>

<!-- FILTER TABS + SEARCH -->
<div style="display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:16px;flex-wrap:wrap">
    <div style="display:flex;gap:6px;flex-wrap:wrap">
        <button class="filter-btn <?= $activeTab==='all'?'active':'' ?>" onclick="filterTab('all')">
            All <span class="badge badge-gray" style="margin-left:4px"><?= $totalCount ?></span>
        </button>
        <button class="filter-btn <?= $activeTab==='customer'?'active':'' ?>" onclick="filterTab('customer')">
            <i class="fa-solid fa-users"></i> Customers
            <span class="badge badge-gray" style="margin-left:4px"><?= count($customers) ?></span>
        </button>
        <button class="filter-btn <?= $activeTab==='supplier'?'active':'' ?>" onclick="filterTab('supplier')">
            <i class="fa-solid fa-user-tie"></i> Suppliers
            <span class="badge badge-gray" style="margin-left:4px"><?= count($suppliers) ?></span>
        </button>
        <button class="filter-btn <?= $activeTab==='employee'?'active':'' ?>" onclick="filterTab('employee')">
            <i class="fa-solid fa-id-badge"></i> Employees
            <span class="badge badge-gray" style="margin-left:4px"><?= count($employees) ?></span>
        </button>
    </div>
    <div style="position:relative;min-width:200px">
        <i class="fa-solid fa-magnifying-glass" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--text-muted);font-size:12px"></i>
        <input type="text" id="contactSearch" placeholder="Search contacts…"
               style="padding:7px 10px 7px 30px;border:1.5px solid var(--border);border-radius:var(--radius);font-size:13px;width:100%"
               oninput="searchContacts(this.value)">
    </div>
</div>

<!-- ALL CONTACTS (unified table) -->
<?php
$allContacts = [];
foreach ($customers as $c) {
    $allContacts[] = [
        'type'    => 'customer',
        'id'      => $c['id'],
        'name'    => $c['name'],
        'phone'   => $c['phone'] ?? '',
        'email'   => $c['email'] ?? '',
        'company' => '',
        'label'   => $c['invoice_count'] . ' invoice' . ($c['invoice_count']!=1?'s':''),
        'amount'  => $c['total_billed'] ?? 0,
        'url'     => '/books/'.$book['id'].'/customers/'.$c['id'],
    ];
}
foreach ($suppliers as $s) {
    $allContacts[] = [
        'type'    => 'supplier',
        'id'      => $s['id'],
        'name'    => $s['name'],
        'phone'   => $s['phone'] ?? '',
        'email'   => $s['email'] ?? '',
        'company' => $s['company'] ?? '',
        'label'   => $s['invoice_count'] . ' purchase' . ($s['invoice_count']!=1?'s':''),
        'amount'  => $s['total_billed'] ?? 0,
        'url'     => '/books/'.$book['id'].'/suppliers/'.$s['id'],
    ];
}
foreach ($employees as $e) {
    $allContacts[] = [
        'type'    => 'employee',
        'id'      => $e['id'],
        'name'    => $e['name'],
        'phone'   => $e['phone'] ?? '',
        'email'   => $e['email'] ?? '',
        'company' => $e['designation_name'] ?? $e['department'] ?? '',
        'label'   => ucfirst($e['status']),
        'amount'  => null,
        'url'     => '/books/'.$book['id'].'/employees/'.$e['id'],
    ];
}
usort($allContacts, fn($a,$b) => strcasecmp($a['name'], $b['name']));

$typeColors = ['customer'=>'blue','supplier'=>'amber','employee'=>'green'];
$typeIcons  = ['customer'=>'fa-users','supplier'=>'fa-user-tie','employee'=>'fa-id-badge'];
?>

<?php if (empty($allContacts)): ?>
<div class="table-wrap">
    <div class="empty-state">
        <div class="empty-icon">📋</div>
        <h3>No contacts yet</h3>
        <p>Contacts are automatically added when you create customers, suppliers, or employees.</p>
        <div style="display:flex;gap:8px;justify-content:center;margin-top:12px">
            <a href="/books/<?= $book['id'] ?>/customers" class="btn btn-secondary">+ Customer</a>
            <a href="/books/<?= $book['id'] ?>/suppliers" class="btn btn-secondary">+ Supplier</a>
            <a href="/books/<?= $book['id'] ?>/employees" class="btn btn-secondary">+ Employee</a>
        </div>
    </div>
</div>
<?php else: ?>
<div class="table-wrap">
    <table id="contactsTable">
        <thead>
            <tr>
                <th>Name</th>
                <th>Type</th>
                <th>Phone</th>
                <th>Email</th>
                <th>Company / Role</th>
                <th>Info</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($allContacts as $c): ?>
        <tr data-type="<?= $c['type'] ?>" data-search="<?= strtolower(e($c['name'].' '.$c['phone'].' '.$c['email'].' '.$c['company'])) ?>">
            <td>
                <a href="<?= $c['url'] ?>" style="font-weight:500;color:var(--brand);text-decoration:none">
                    <?= e($c['name']) ?>
                </a>
            </td>
            <td>
                <span class="badge badge-<?= $typeColors[$c['type']] ?>">
                    <i class="fa-solid <?= $typeIcons[$c['type']] ?>"></i>
                    <?= ucfirst($c['type']) ?>
                </span>
            </td>
            <td class="td-muted"><?= $c['phone'] ? e($c['phone']) : '—' ?></td>
            <td class="td-muted"><?= $c['email'] ? e($c['email']) : '—' ?></td>
            <td class="td-muted"><?= $c['company'] ? e($c['company']) : '—' ?></td>
            <td class="td-muted"><?= e($c['label']) ?></td>
            <td>
                <a href="<?= $c['url'] ?>" class="btn btn-sm btn-secondary">View</a>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<script>
var currentTab = 'all';

function filterTab(tab) {
    currentTab = tab;
    document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
    event.target.closest('.filter-btn').classList.add('active');
    applyFilter();
}

function searchContacts(q) {
    applyFilter(q);
}

function applyFilter(q) {
    q = (q || document.getElementById('contactSearch').value || '').toLowerCase().trim();
    document.querySelectorAll('#contactsTable tbody tr').forEach(row => {
        const typeMatch   = currentTab === 'all' || row.dataset.type === currentTab;
        const searchMatch = !q || row.dataset.search.includes(q);
        row.style.display = (typeMatch && searchMatch) ? '' : 'none';
    });
}
</script>

<?php
$content = ob_get_clean();
require BASE_PATH . '/views/partials/layout.php';
?>

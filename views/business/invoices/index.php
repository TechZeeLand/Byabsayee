<?php
$pageTitle = 'Invoices — ' . e($book['name']);
ob_start();
$type   = $_GET['type']   ?? 'all';
$status = $_GET['status'] ?? 'all';
?>
<div class="page-header">
    <div class="page-header-left">
        <div class="breadcrumb">
            <a href="/dashboard">Dashboard</a> <span>›</span>
            <a href="/books/<?= $book['id'] ?>"><?= e($book['name']) ?></a> <span>›</span>
            <span>Invoices</span>
        </div>
        <h1>Invoices</h1>
    </div>
    <div style="display:flex;gap:8px">
        <a href="/books/<?= $book['id'] ?>/invoices/create?type=sale" class="btn btn-primary">+ Sale Invoice</a>
        <a href="/books/<?= $book['id'] ?>/invoices/create?type=purchase" class="btn btn-secondary">+ Purchase</a>
    </div>
</div>

<!-- Summary -->
<div class="stat-grid" style="grid-template-columns:repeat(4,1fr);max-width:720px;margin-bottom:20px">
    <div class="stat-card">
        <div class="stat-label">Total Sales</div>
        <div class="stat-value green"><?= format_money($summary['total_sales']) ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Collected</div>
        <div class="stat-value brand"><?= format_money($summary['collected']) ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Outstanding</div>
        <div class="stat-value <?= $summary['outstanding']>0?'red':'green' ?>"><?= format_money($summary['outstanding']) ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Total Purchases</div>
        <div class="stat-value red"><?= format_money($summary['total_purchases']) ?></div>
    </div>
</div>

<!-- Filters -->
<div style="display:flex;gap:8px;margin-bottom:14px;flex-wrap:wrap">
    <?php
    $types   = ['all'=>'All','sale'=>'Sales','purchase'=>'Purchases'];
    $statuses = ['all'=>'All Status','draft'=>'Draft','sent'=>'Sent','partial'=>'Partial','paid'=>'Paid','overdue'=>'Overdue'];
    ?>
    <?php foreach ($types as $k=>$v): ?>
    <a href="?type=<?= $k ?>&status=<?= $status ?>"
       class="btn btn-sm btn-secondary <?= $type===$k?'btn-primary':'' ?>"><?= $v ?></a>
    <?php endforeach; ?>
    <div style="flex:1"></div>
    <?php foreach ($statuses as $k=>$v): ?>
    <a href="?type=<?= $type ?>&status=<?= $k ?>"
       class="btn btn-sm btn-secondary <?= $status===$k?'btn-primary':'' ?>"><?= $v ?></a>
    <?php endforeach; ?>
</div>

<?php if (empty($invoices)): ?>
<div class="table-wrap">
    <div class="empty-state">
        <div class="empty-icon">🧾</div>
        <h3>No invoices yet</h3>
        <p>Create your first sale or purchase invoice.</p>
    </div>
</div>
<?php else: ?>
<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>Invoice #</th>
                <th>Type</th>
                <th>Party</th>
                <th>Date</th>
                <th>Due Date</th>
                <th>Status</th>
                <th style="text-align:right">Total</th>
                <th style="text-align:right">Paid</th>
                <th style="text-align:right">Due</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($invoices as $inv):
            $sc  = ['draft'=>'gray','sent'=>'blue','partial'=>'amber','paid'=>'green','overdue'=>'red','cancelled'=>'gray'][$inv['status']] ?? 'gray';
            $due = $inv['total'] - $inv['paid'];
            $party = $inv['customer_name'] ?? $inv['supplier_name'] ?? '—';
        ?>
        <tr>
            <td><a href="/books/<?= $book['id'] ?>/invoices/<?= $inv['id'] ?>"
                   style="font-weight:600;color:var(--brand);text-decoration:none"><?= e($inv['invoice_no']) ?></a></td>
            <td><span class="badge <?= $inv['type']==='sale'?'badge-green':'badge-blue' ?>"><?= ucfirst($inv['type']) ?></span></td>
            <td class="td-muted"><?= e($party) ?></td>
            <td class="td-muted"><?= format_date($inv['date']) ?></td>
            <td class="td-muted"><?= $inv['due_date'] ? format_date($inv['due_date']) : '—' ?></td>
            <td><span class="badge badge-<?= $sc ?>"><?= ucfirst($inv['status']) ?></span></td>
            <td style="text-align:right" class="td-amount"><?= format_money($inv['total']) ?></td>
            <td style="text-align:right" class="td-amount in"><?= format_money($inv['paid']) ?></td>
            <td style="text-align:right" class="td-amount <?= $due>0?'out':'' ?>"><?= format_money($due) ?></td>
            <td><a href="/books/<?= $book['id'] ?>/invoices/<?= $inv['id'] ?>" class="btn btn-sm btn-secondary">View</a></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php $content = ob_get_clean(); require BASE_PATH . '/views/partials/layout.php'; ?>

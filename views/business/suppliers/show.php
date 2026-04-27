<?php
$pageTitle = e($supplier['name']) . ' — Byabsayee';
ob_start();
?>
<div class="page-header">
    <div class="page-header-left">
        <div class="breadcrumb">
            <a href="/dashboard">Dashboard</a> <span>›</span>
            <a href="/books/<?= $book['id'] ?>"><?= e($book['name']) ?></a> <span>›</span>
            <a href="/books/<?= $book['id'] ?>/suppliers">Suppliers</a> <span>›</span>
            <span><?= e($supplier['name']) ?></span>
        </div>
        <h1><?= e($supplier['name']) ?></h1>
        <?php if ($supplier['company']): ?>
            <p><?= e($supplier['company']) ?></p>
        <?php endif; ?>
    </div>
    <div style="display:flex;gap:8px">
        <a href="/books/<?= $book['id'] ?>/invoices/create?type=purchase&supplier_id=<?= $supplier['id'] ?>"
           class="btn btn-primary">+ New Purchase</a>
        <button class="btn btn-secondary" data-modal="editSupplierModal">Edit</button>
        <form method="POST" action="/books/<?= $book['id'] ?>/suppliers/<?= $supplier['id'] ?>/delete"
              data-confirm="Delete <?= e($supplier['name']) ?>?">
            <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
            <button class="btn btn-danger">Delete</button>
        </form>
    </div>
</div>

<div class="stat-grid" style="max-width:500px;grid-template-columns:repeat(3,1fr)">
    <div class="stat-card">
        <div class="stat-label">Total Purchased</div>
        <div class="stat-value brand"><?= format_money($totals['total_billed']) ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Total Paid</div>
        <div class="stat-value green"><?= format_money($totals['total_paid']) ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Outstanding</div>
        <div class="stat-value <?= $totals['total_due'] > 0 ? 'red':'green' ?>"><?= format_money($totals['total_due']) ?></div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 2fr;gap:16px">
    <div class="card">
        <p class="card-title">Contact Details</p>
        <div style="font-size:13px;display:flex;flex-direction:column;gap:8px">
            <?php if ($supplier['phone']): ?><div><span style="color:var(--text-muted)">Phone:</span> <?= e($supplier['phone']) ?></div><?php endif; ?>
            <?php if ($supplier['email']): ?><div><span style="color:var(--text-muted)">Email:</span> <?= e($supplier['email']) ?></div><?php endif; ?>
            <?php if ($supplier['address']): ?><div><span style="color:var(--text-muted)">Address:</span> <?= e($supplier['address']) ?></div><?php endif; ?>
            <?php if ($supplier['notes']): ?><div><span style="color:var(--text-muted)">Notes:</span> <?= e($supplier['notes']) ?></div><?php endif; ?>
        </div>
    </div>

    <div>
        <p class="section-label">Purchase History</p>
        <?php if (empty($invoices)): ?>
            <div class="table-wrap"><div class="empty-state" style="padding:30px"><p>No purchases yet.</p></div></div>
        <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Invoice #</th><th>Date</th><th>Status</th><th style="text-align:right">Total</th><th style="text-align:right">Due</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($invoices as $inv):
                    $sc = ['draft'=>'gray','sent'=>'blue','partial'=>'amber','paid'=>'green','overdue'=>'red','cancelled'=>'gray'][$inv['status']] ?? 'gray';
                ?>
                <tr>
                    <td style="font-weight:500"><?= e($inv['invoice_no']) ?></td>
                    <td class="td-muted"><?= format_date($inv['date']) ?></td>
                    <td><span class="badge badge-<?= $sc ?>"><?= ucfirst($inv['status']) ?></span></td>
                    <td style="text-align:right" class="td-amount"><?= format_money($inv['total']) ?></td>
                    <td style="text-align:right" class="td-amount <?= ($inv['total']-$inv['paid'])>0?'out':'' ?>"><?= format_money($inv['total']-$inv['paid']) ?></td>
                    <td><a href="/books/<?= $book['id'] ?>/invoices/<?= $inv['id'] ?>" class="btn btn-sm btn-secondary">View</a></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- EDIT MODAL -->
<div class="modal-backdrop" id="editSupplierModal">
    <div class="modal">
        <div class="modal-title">Edit Supplier</div>
        <form method="POST" action="/books/<?= $book['id'] ?>/suppliers/<?= $supplier['id'] ?>/edit">
            <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
            <div class="form-grid" style="gap:12px">
                <div class="form-group full"><label>Name *</label><input type="text" name="name" value="<?= e($supplier['name']) ?>" required></div>
                <div class="form-group full"><label>Company</label><input type="text" name="company" value="<?= e($supplier['company'] ?? '') ?>"></div>
                <div class="form-group"><label>Phone</label><input type="text" name="phone" value="<?= e($supplier['phone'] ?? '') ?>"></div>
                <div class="form-group"><label>Email</label><input type="email" name="email" value="<?= e($supplier['email'] ?? '') ?>"></div>
                <div class="form-group full"><label>Address</label><textarea name="address" style="min-height:56px"><?= e($supplier['address'] ?? '') ?></textarea></div>
                <div class="form-group full"><label>Notes</label><textarea name="notes" style="min-height:48px"><?= e($supplier['notes'] ?? '') ?></textarea></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-close-modal>Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<?php $content = ob_get_clean(); require BASE_PATH . '/views/partials/layout.php'; ?>

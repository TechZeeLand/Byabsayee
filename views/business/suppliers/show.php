<?php
$pageTitle = e($supplier['name']) . ' — Byabsayee';
ob_start();
?>
<div class="page-header">
    <div class="page-header-left">
        <div class="breadcrumb">
            <a href="/books/<?= $book['id'] ?>">Dashboard</a> <span>›</span>
            <a href="/books/<?= $book['id'] ?>/suppliers">Suppliers</a> <span>›</span>
            <span><?= e($supplier['name']) ?></span>
        </div>
        <h1><?= e($supplier['name']) ?></h1>
        <?php if ($supplier['company']): ?><p style="color:var(--text-muted);margin-top:2px"><?= e($supplier['company']) ?></p><?php endif; ?>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
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

<div class="stat-grid" style="max-width:600px;grid-template-columns:repeat(3,1fr)">
    <div class="stat-card"><div class="stat-label">Total Purchased</div>
        <div class="stat-value brand"><?= format_money($totals['total_billed']) ?></div></div>
    <div class="stat-card"><div class="stat-label">Total Paid</div>
        <div class="stat-value green"><?= format_money($totals['total_paid']) ?></div></div>
    <div class="stat-card"><div class="stat-label">Outstanding</div>
        <div class="stat-value <?= $totals['total_due']>0?'red':'green' ?>"><?= format_money($totals['total_due']) ?></div></div>
</div>

<div style="display:grid;grid-template-columns:280px 1fr;gap:16px;align-items:start">
    <div class="card">
        <p class="card-title">Contact Details</p>
        <div style="font-size:13px;display:flex;flex-direction:column;gap:8px">
            <?php if ($supplier['phone']): ?><div><span style="color:var(--text-muted)">Phone:</span> <?= e($supplier['phone']) ?></div><?php endif; ?>
            <?php if ($supplier['email']): ?><div><span style="color:var(--text-muted)">Email:</span> <?= e($supplier['email']) ?></div><?php endif; ?>
            <?php if ($supplier['address']): ?><div><span style="color:var(--text-muted)">Address:</span> <?= e($supplier['address']) ?></div><?php endif; ?>
            <?php if ($supplier['notes']): ?><div><span style="color:var(--text-muted)">Notes:</span> <?= e($supplier['notes']) ?></div><?php endif; ?>
            <div class="td-muted">Since <?= format_date($supplier['created_at']) ?></div>
        </div>
    </div>

    <div>
        <div class="tab-nav" id="supplierTabs">
            <button class="tab-btn active" data-tab="tab-purchases">
                <i class="fa-solid fa-cart-shopping"></i> Purchases <span class="badge badge-gray" style="margin-left:4px"><?= count($invoices) ?></span>
            </button>
            <button class="tab-btn" data-tab="tab-debts">
                <i class="fa-solid fa-file-circle-minus"></i> Debts <span class="badge badge-gray" style="margin-left:4px"><?= count($debts) ?></span>
            </button>
            <button class="tab-btn" data-tab="tab-returns">
                <i class="fa-solid fa-rotate-left"></i> Returns <span class="badge badge-gray" style="margin-left:4px"><?= count($returns) ?></span>
            </button>
        </div>

        <div id="tab-purchases" class="tab-pane active">
            <?php if (empty($invoices)): ?>
            <div class="table-wrap"><div class="empty-state" style="padding:30px"><p>No purchases yet.</p></div></div>
            <?php else: ?>
            <div class="table-wrap"><table>
                <thead><tr><th>Invoice #</th><th>Date</th><th>Status</th><th style="text-align:right">Total</th><th style="text-align:right">Due</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($invoices as $inv):
                    $sc = ['draft'=>'gray','sent'=>'blue','partial'=>'amber','paid'=>'green','overdue'=>'red','cancelled'=>'gray'][$inv['status']]??'gray'; ?>
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
            </table></div>
            <?php endif; ?>
        </div>

        <div id="tab-debts" class="tab-pane">
            <div style="display:flex;justify-content:flex-end;margin-bottom:10px">
                <a href="/books/<?= $book['id'] ?>/debts" class="btn btn-sm btn-secondary">View All Debts &rarr;</a>
            </div>
            <?php if (empty($debts)): ?>
            <div class="table-wrap"><div class="empty-state" style="padding:30px"><p>No debts for this supplier.</p></div></div>
            <?php else: ?>
            <div class="table-wrap"><table>
                <thead><tr><th>Title</th><th>Due Date</th><th>Status</th><th style="text-align:right">Amount</th><th style="text-align:right">Paid</th><th style="text-align:right">Remaining</th></tr></thead>
                <tbody>
                <?php foreach ($debts as $d):
                    $dsc = ['unpaid'=>'amber','partial'=>'blue','paid'=>'green','cancelled'=>'gray'][$d['status']??'pending']??'gray';
                    $remaining = ($d['amount']??0) - ($d['paid_amount']??0); ?>
                <tr>
                    <td style="font-weight:500"><?= e($d['title']) ?></td>
                    <td class="td-muted"><?= $d['due_date'] ? format_date($d['due_date']) : '—' ?></td>
                    <td><span class="badge badge-<?= $dsc ?>"><?= ucfirst($d['status']??'unpaid') ?></span></td>
                    <td style="text-align:right" class="td-amount"><?= format_money($d['amount']??0) ?></td>
                    <td style="text-align:right" class="td-amount in"><?= format_money($d['paid_amount']??0) ?></td>
                    <td style="text-align:right" class="td-amount <?= $remaining>0?'out':'' ?>"><?= format_money($remaining) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table></div>
            <?php endif; ?>
        </div>

        <div id="tab-returns" class="tab-pane">
            <div style="display:flex;justify-content:flex-end;margin-bottom:10px">
                <a href="/books/<?= $book['id'] ?>/returns/create?type=purchase_return&supplier_id=<?= $supplier['id'] ?>" class="btn btn-sm btn-primary">+ New Return</a>
            </div>
            <?php if (empty($returns)): ?>
            <div class="table-wrap"><div class="empty-state" style="padding:30px"><p>No returns for this supplier.</p></div></div>
            <?php else: ?>
            <div class="table-wrap"><table>
                <thead><tr><th>Return #</th><th>Date</th><th>Original Invoice</th><th style="text-align:right">Refund</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($returns as $r): ?>
                <tr>
                    <td style="font-weight:500"><?= e($r['return_no'] ?? '#'.$r['id']) ?></td>
                    <td class="td-muted"><?= format_date($r['date']) ?></td>
                    <td class="td-muted"><?= e($r['orig_invoice_no'] ?? '—') ?></td>
                    <td style="text-align:right" class="td-amount out"><?= format_money($r['total_refund']) ?></td>
                    <td><a href="/books/<?= $book['id'] ?>/returns/<?= $r['id'] ?>" class="btn btn-sm btn-secondary">View</a></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table></div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="modal-backdrop" id="editSupplierModal">
    <div class="modal">
        <div class="modal-title">Edit Supplier</div>
        <form method="POST" action="/books/<?= $book['id'] ?>/suppliers/<?= $supplier['id'] ?>/edit">
            <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
            <div class="form-grid" style="gap:12px">
                <div class="form-group full"><label>Name *</label><input type="text" name="name" value="<?= e($supplier['name']) ?>" required></div>
                <div class="form-group full"><label>Company</label><input type="text" name="company" value="<?= e($supplier['company']??'') ?>"></div>
                <div class="form-group"><label>Phone</label><input type="text" name="phone" value="<?= e($supplier['phone']??'') ?>"></div>
                <div class="form-group"><label>Email</label><input type="email" name="email" value="<?= e($supplier['email']??'') ?>"></div>
                <div class="form-group full"><label>Address</label><textarea name="address" style="min-height:56px"><?= e($supplier['address']??'') ?></textarea></div>
                <div class="form-group full"><label>Notes</label><textarea name="notes" style="min-height:48px"><?= e($supplier['notes']??'') ?></textarea></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-close-modal>Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
document.querySelectorAll('#supplierTabs .tab-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('#supplierTabs .tab-btn').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
        btn.classList.add('active');
        document.getElementById(btn.dataset.tab).classList.add('active');
    });
});
</script>

<?php $content = ob_get_clean(); require BASE_PATH . '/views/partials/layout.php'; ?>

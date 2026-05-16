<?php
$pageTitle = e($customer['name']) . ' — Byabsayee';

$assignedPrivileges = \App\Helpers\Database::query(
    'SELECT privilege_id FROM customer_privilege_assignments WHERE customer_id=?',
    [$customer['id']]
);
$assignedIds = array_column($assignedPrivileges, 'privilege_id');

$assignedPrivDetails = [];
foreach ($assignedIds as $pid) {
    $p = \App\Helpers\Database::row('SELECT * FROM customer_privileges WHERE id=?', [$pid]);
    if ($p) $assignedPrivDetails[] = $p;
}

ob_start();
?>

<div class="page-header">
    <div class="page-header-left">
        <div class="breadcrumb">
            <a href="/books/<?= $book['id'] ?>">Dashboard</a> <span>›</span>
            <a href="/books/<?= $book['id'] ?>/customers">Customers</a> <span>›</span>
            <span><?= e($customer['name']) ?></span>
        </div>
        <h1><?= e($customer['name']) ?></h1>
        <div style="display:flex;gap:6px;flex-wrap:wrap;margin-top:4px">
            <?php if ($customer['points'] > 0): ?>
                <span class="badge badge-amber"><?= $customer['points'] ?> pts</span>
            <?php endif; ?>
            <?php foreach ($assignedPrivDetails as $priv): ?>
                <span class="badge badge-green">
                    <?= e($priv['name']) ?> —
                    <?= $priv['discount_type']==='percent' ? $priv['discount_value'].'%' : '৳'.number_format($priv['discount_value'],2) ?> off
                </span>
            <?php endforeach; ?>
        </div>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
        <a href="/books/<?= $book['id'] ?>/invoices/create?type=sale&customer_id=<?= $customer['id'] ?>"
           class="btn btn-primary">+ New Invoice</a>
        <button class="btn btn-secondary" data-modal="editCustomerModal">Edit</button>
        <form method="POST" action="/books/<?= $book['id'] ?>/customers/<?= $customer['id'] ?>/delete"
              data-confirm="Delete <?= e($customer['name']) ?>?">
            <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
            <button class="btn btn-danger">Delete</button>
        </form>
    </div>
</div>

<div class="stat-grid" style="max-width:700px;grid-template-columns:repeat(4,1fr)">
    <div class="stat-card"><div class="stat-label">Total Billed</div>
        <div class="stat-value brand"><?= format_money($totals['total_billed']) ?></div></div>
    <div class="stat-card"><div class="stat-label">Total Paid</div>
        <div class="stat-value green"><?= format_money($totals['total_paid']) ?></div></div>
    <div class="stat-card"><div class="stat-label">Outstanding</div>
        <div class="stat-value <?= $totals['total_due']>0?'red':'green' ?>"><?= format_money($totals['total_due']) ?></div></div>
    <div class="stat-card"><div class="stat-label">Loyalty Points</div>
        <div class="stat-value" style="color:var(--accent)"><?= $customer['points'] ?></div></div>
</div>

<div style="display:grid;grid-template-columns:280px 1fr;gap:16px;align-items:start">

    <div style="display:flex;flex-direction:column;gap:12px">
        <div class="card">
            <p class="card-title">Contact Details</p>
            <div style="font-size:13px;display:flex;flex-direction:column;gap:7px">
                <?php if ($customer['phone']): ?><div><span style="color:var(--text-muted)">Phone:</span> <?= e($customer['phone']) ?></div><?php endif; ?>
                <?php if ($customer['email']): ?><div><span style="color:var(--text-muted)">Email:</span> <?= e($customer['email']) ?></div><?php endif; ?>
                <?php if ($customer['address']): ?><div><span style="color:var(--text-muted)">Address:</span> <?= e($customer['address']) ?></div><?php endif; ?>
                <?php if ($customer['notes']): ?><div><span style="color:var(--text-muted)">Notes:</span> <?= e($customer['notes']) ?></div><?php endif; ?>
                <div class="td-muted">Since <?= format_date($customer['created_at']) ?></div>
            </div>
        </div>

        <div class="card">
            <p class="card-title">Privileges &amp; Discounts</p>
            <?php if (empty($privileges)): ?>
                <p style="font-size:13px;color:var(--text-muted);margin-bottom:10px">No privileges defined.</p>
                <a href="/books/<?= $book['id'] ?>/privileges" class="btn btn-sm btn-secondary">Create Privileges &rarr;</a>
            <?php else: ?>
            <form method="POST" action="/books/<?= $book['id'] ?>/customers/<?= $customer['id'] ?>/privileges">
                <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
                <div style="display:flex;flex-direction:column;gap:8px;margin-bottom:12px">
                    <?php foreach ($privileges as $priv): ?>
                    <label style="display:flex;align-items:center;gap:10px;cursor:pointer;font-size:13px;padding:7px 10px;border-radius:8px;border:1.5px solid <?= in_array($priv['id'],$assignedIds)?'var(--brand)':'var(--border)' ?>;background:<?= in_array($priv['id'],$assignedIds)?'var(--brand-light)':'transparent' ?>">
                        <input type="checkbox" name="privilege_ids[]" value="<?= $priv['id'] ?>"
                               <?= in_array($priv['id'],$assignedIds) ? 'checked' : '' ?>
                               onchange="this.closest('label').style.borderColor=this.checked?'var(--brand)':'var(--border)';this.closest('label').style.background=this.checked?'var(--brand-light)':'transparent'"
                               style="width:16px;height:16px;accent-color:var(--brand)">
                        <div>
                            <div style="font-weight:500"><?= e($priv['name']) ?></div>
                            <div style="font-size:11px;color:var(--text-muted)">
                                <?= $priv['discount_type']==='percent' ? $priv['discount_value'].'% discount' : '৳'.number_format($priv['discount_value'],2).' fixed' ?>
                                <?php if ($priv['description']): ?> — <?= e($priv['description']) ?><?php endif; ?>
                            </div>
                        </div>
                    </label>
                    <?php endforeach; ?>
                </div>
                <button type="submit" class="btn btn-sm btn-primary">Save Privileges</button>
            </form>
            <?php endif; ?>
        </div>

        <div class="card">
            <p class="card-title">Loyalty Points</p>
            <div style="font-size:28px;font-weight:700;color:var(--accent)"><?= $customer['points'] ?></div>
            <div style="font-size:12px;color:var(--text-muted);margin-top:2px">1 point per ৳100 paid · 1 point = ৳1 discount</div>
        </div>
    </div>

    <div>
        <div class="tab-nav" id="customerTabs">
            <button class="tab-btn active" data-tab="tab-invoices">
                <i class="fa-solid fa-file-invoice"></i> Invoices <span class="badge badge-gray" style="margin-left:4px"><?= count($invoices) ?></span>
            </button>
            <button class="tab-btn" data-tab="tab-dues">
                <i class="fa-solid fa-hand-holding-dollar"></i> Dues <span class="badge badge-gray" style="margin-left:4px"><?= count($dues) ?></span>
            </button>
            <button class="tab-btn" data-tab="tab-returns">
                <i class="fa-solid fa-rotate-left"></i> Returns <span class="badge badge-gray" style="margin-left:4px"><?= count($returns) ?></span>
            </button>
        </div>

        <div id="tab-invoices" class="tab-pane active">
            <?php if (empty($invoices)): ?>
            <div class="table-wrap"><div class="empty-state" style="padding:30px"><p>No invoices yet.</p>
                <a href="/books/<?= $book['id'] ?>/invoices/create?type=sale&customer_id=<?= $customer['id'] ?>" class="btn btn-primary" style="margin-top:10px">+ Create Invoice</a>
            </div></div>
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

        <div id="tab-dues" class="tab-pane">
            <div style="display:flex;justify-content:flex-end;margin-bottom:10px">
                <a href="/books/<?= $book['id'] ?>/dues" class="btn btn-sm btn-secondary">View All Dues &rarr;</a>
            </div>
            <?php if (empty($dues)): ?>
            <div class="table-wrap"><div class="empty-state" style="padding:30px"><p>No dues for this customer.</p></div></div>
            <?php else: ?>
            <div class="table-wrap"><table>
                <thead><tr><th>Title</th><th>Due Date</th><th>Status</th><th style="text-align:right">Amount</th><th style="text-align:right">Paid</th><th style="text-align:right">Remaining</th></tr></thead>
                <tbody>
                <?php foreach ($dues as $d):
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
                <a href="/books/<?= $book['id'] ?>/returns/create?type=sales_return&customer_id=<?= $customer['id'] ?>" class="btn btn-sm btn-primary">+ New Return</a>
            </div>
            <?php if (empty($returns)): ?>
            <div class="table-wrap"><div class="empty-state" style="padding:30px"><p>No returns for this customer.</p></div></div>
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

<div class="modal-backdrop" id="editCustomerModal">
    <div class="modal">
        <div class="modal-title">Edit Customer</div>
        <form method="POST" action="/books/<?= $book['id'] ?>/customers/<?= $customer['id'] ?>/edit">
            <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
            <div class="form-grid" style="gap:12px">
                <div class="form-group full"><label>Name *</label><input type="text" name="name" value="<?= e($customer['name']) ?>" required></div>
                <div class="form-group"><label>Phone</label><input type="text" name="phone" value="<?= e($customer['phone']??'') ?>"></div>
                <div class="form-group"><label>Email</label><input type="email" name="email" value="<?= e($customer['email']??'') ?>"></div>
                <div class="form-group full"><label>Address</label><textarea name="address" style="min-height:56px"><?= e($customer['address']??'') ?></textarea></div>
                <div class="form-group full"><label>Notes</label><textarea name="notes" style="min-height:48px"><?= e($customer['notes']??'') ?></textarea></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-close-modal>Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
document.querySelectorAll('#customerTabs .tab-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('#customerTabs .tab-btn').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
        btn.classList.add('active');
        document.getElementById(btn.dataset.tab).classList.add('active');
    });
});
</script>

<?php $content = ob_get_clean(); require BASE_PATH . '/views/partials/layout.php'; ?>

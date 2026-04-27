<?php
$pageTitle = e($customer['name']) . ' — Byabsayee';
ob_start();
?>
<div class="page-header">
    <div class="page-header-left">
        <div class="breadcrumb">
            <a href="/dashboard">Dashboard</a> <span>›</span>
            <a href="/books/<?= $book['id'] ?>"><?= e($book['name']) ?></a> <span>›</span>
            <a href="/books/<?= $book['id'] ?>/customers">Customers</a> <span>›</span>
            <span><?= e($customer['name']) ?></span>
        </div>
        <h1><?= e($customer['name']) ?></h1>
        <?php if ($customer['points'] > 0): ?>
            <span class="badge badge-amber" style="margin-top:4px"><?= $customer['points'] ?> loyalty points</span>
        <?php endif; ?>
    </div>
    <div style="display:flex;gap:8px">
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

<!-- Stats -->
<div class="stat-grid" style="max-width:600px;grid-template-columns:repeat(3,1fr)">
    <div class="stat-card">
        <div class="stat-label">Total Billed</div>
        <div class="stat-value brand"><?= format_money($totals['total_billed']) ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Total Paid</div>
        <div class="stat-value green"><?= format_money($totals['total_paid']) ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Outstanding</div>
        <div class="stat-value <?= $totals['total_due'] > 0 ? 'red' : 'green' ?>">
            <?= format_money($totals['total_due']) ?>
        </div>
    </div>
</div>

<!-- Contact info -->
<div style="display:grid;grid-template-columns:1fr 2fr;gap:16px;margin-bottom:24px">
    <div class="card">
        <p class="card-title">Contact Details</p>
        <div style="display:flex;flex-direction:column;gap:8px;font-size:13px">
            <?php if ($customer['phone']): ?>
                <div><span style="color:var(--text-muted)">Phone:</span> <?= e($customer['phone']) ?></div>
            <?php endif; ?>
            <?php if ($customer['email']): ?>
                <div><span style="color:var(--text-muted)">Email:</span> <?= e($customer['email']) ?></div>
            <?php endif; ?>
            <?php if ($customer['address']): ?>
                <div><span style="color:var(--text-muted)">Address:</span> <?= e($customer['address']) ?></div>
            <?php endif; ?>
            <?php if ($customer['notes']): ?>
                <div><span style="color:var(--text-muted)">Notes:</span> <?= e($customer['notes']) ?></div>
            <?php endif; ?>
            <div class="td-muted">Customer since <?= format_date($customer['created_at']) ?></div>
        </div>
    </div>

    <!-- Invoice history -->
    <div>
        <p class="section-label">Invoice History (<?= count($invoices) ?>)</p>
        <?php if (empty($invoices)): ?>
            <div class="table-wrap"><div class="empty-state" style="padding:30px">
                <p>No invoices yet.</p>
            </div></div>
        <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Invoice #</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th style="text-align:right">Total</th>
                        <th style="text-align:right">Due</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($invoices as $inv):
                    $statusColors = ['draft'=>'gray','sent'=>'blue','partial'=>'amber','paid'=>'green','overdue'=>'red','cancelled'=>'gray'];
                    $sc = $statusColors[$inv['status']] ?? 'gray';
                ?>
                <tr>
                    <td style="font-weight:500"><?= e($inv['invoice_no']) ?></td>
                    <td class="td-muted"><?= format_date($inv['date']) ?></td>
                    <td><span class="badge badge-<?= $sc ?>"><?= ucfirst($inv['status']) ?></span></td>
                    <td style="text-align:right" class="td-amount"><?= format_money($inv['total']) ?></td>
                    <td style="text-align:right" class="td-amount <?= ($inv['total']-$inv['paid']) > 0 ? 'out':'' ?>">
                        <?= format_money($inv['total'] - $inv['paid']) ?>
                    </td>
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
<div class="modal-backdrop" id="editCustomerModal">
    <div class="modal">
        <div class="modal-title">Edit Customer</div>
        <form method="POST" action="/books/<?= $book['id'] ?>/customers/<?= $customer['id'] ?>/edit">
            <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
            <div class="form-grid" style="gap:12px">
                <div class="form-group full">
                    <label>Name *</label>
                    <input type="text" name="name" value="<?= e($customer['name']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Phone</label>
                    <input type="text" name="phone" value="<?= e($customer['phone'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" value="<?= e($customer['email'] ?? '') ?>">
                </div>
                <div class="form-group full">
                    <label>Address</label>
                    <textarea name="address" style="min-height:56px"><?= e($customer['address'] ?? '') ?></textarea>
                </div>
                <div class="form-group full">
                    <label>Notes</label>
                    <textarea name="notes" style="min-height:48px"><?= e($customer['notes'] ?? '') ?></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-close-modal>Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<?php $content = ob_get_clean(); require BASE_PATH . '/views/partials/layout.php'; ?>

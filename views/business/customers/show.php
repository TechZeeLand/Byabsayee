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
        <div style="display:flex;gap:8px;margin-top:4px;flex-wrap:wrap">
            <?php if ($customer['points'] > 0): ?>
                <span class="badge badge-amber"><?= $customer['points'] ?> loyalty points</span>
            <?php endif; ?>
            <?php if ($privilege): ?>
                <span class="badge badge-green">
                    <?= e($privilege['name']) ?> —
                    <?= $privilege['discount_type'] === 'percent'
                        ? $privilege['discount_value'].'% off'
                        : '৳'.number_format($privilege['discount_value'],2).' off' ?>
                </span>
            <?php endif; ?>
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

<div style="display:grid;grid-template-columns:280px 1fr;gap:16px;align-items:start">

    <!-- LEFT: contact info + privilege -->
    <div style="display:flex;flex-direction:column;gap:12px">

        <div class="card">
            <p class="card-title">Contact Details</p>
            <div style="font-size:13px;display:flex;flex-direction:column;gap:8px">
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

        <!-- Privilege card -->
        <div class="card">
            <p class="card-title">Privilege / Discount</p>
            <?php if ($privilege): ?>
                <div style="margin-bottom:12px">
                    <span class="badge badge-green" style="font-size:13px;padding:4px 12px">
                        <?= e($privilege['name']) ?>
                    </span>
                    <div style="font-size:13px;color:var(--text-muted);margin-top:6px">
                        <?= $privilege['discount_type'] === 'percent'
                            ? $privilege['discount_value'].'% discount on all invoices'
                            : '৳'.number_format($privilege['discount_value'],2).' fixed discount' ?>
                    </div>
                    <?php if ($privilege['description']): ?>
                        <div style="font-size:12px;color:var(--text-muted);margin-top:4px"><?= e($privilege['description']) ?></div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <p style="font-size:13px;color:var(--text-muted);margin-bottom:12px">No privilege assigned.</p>
            <?php endif; ?>

            <!-- Quick assign form -->
            <form method="POST" action="/books/<?= $book['id'] ?>/customers/<?= $customer['id'] ?>/edit">
                <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
                <input type="hidden" name="name"    value="<?= e($customer['name']) ?>">
                <input type="hidden" name="phone"   value="<?= e($customer['phone']   ?? '') ?>">
                <input type="hidden" name="email"   value="<?= e($customer['email']   ?? '') ?>">
                <input type="hidden" name="address" value="<?= e($customer['address'] ?? '') ?>">
                <input type="hidden" name="notes"   value="<?= e($customer['notes']   ?? '') ?>">
                <div class="form-group" style="margin-bottom:8px">
                    <label style="font-size:12px">Assign privilege</label>
                    <select name="privilege_id"
                            style="width:100%;padding:7px 10px;border:1.5px solid var(--border);border-radius:8px;font-size:13px;font-family:inherit;outline:none">
                        <option value="">— None —</option>
                        <?php foreach ($privileges as $priv): ?>
                        <option value="<?= $priv['id'] ?>"
                            <?= $customer['privilege_id'] == $priv['id'] ? 'selected' : '' ?>>
                            <?= e($priv['name']) ?>
                            (<?= $priv['discount_type'] === 'percent'
                                ? $priv['discount_value'].'%'
                                : '৳'.number_format($priv['discount_value'],2) ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-sm btn-primary">Update Privilege</button>
                <?php if (empty($privileges)): ?>
                    <a href="/books/<?= $book['id'] ?>/privileges"
                       style="font-size:12px;color:var(--brand);margin-left:8px">Create privileges →</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Points card -->
        <div class="card">
            <p class="card-title">Loyalty Points</p>
            <div style="font-size:28px;font-weight:700;color:var(--accent)"><?= $customer['points'] ?></div>
            <div style="font-size:12px;color:var(--text-muted);margin-top:2px">
                1 point earned per ৳100 paid<br>
                1 point = ৳1 discount (enter manually on invoice)
            </div>
        </div>
    </div>

    <!-- RIGHT: invoice history -->
    <div>
        <p class="section-label">Invoice History (<?= count($invoices) ?>)</p>
        <?php if (empty($invoices)): ?>
            <div class="table-wrap">
                <div class="empty-state" style="padding:30px">
                    <p>No invoices yet.</p>
                    <a href="/books/<?= $book['id'] ?>/invoices/create?type=sale&customer_id=<?= $customer['id'] ?>"
                       class="btn btn-primary" style="margin-top:10px">+ Create First Invoice</a>
                </div>
            </div>
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
                    $sc = ['draft'=>'gray','sent'=>'blue','partial'=>'amber',
                           'paid'=>'green','overdue'=>'red','cancelled'=>'gray'][$inv['status']] ?? 'gray';
                ?>
                <tr>
                    <td style="font-weight:500"><?= e($inv['invoice_no']) ?></td>
                    <td class="td-muted"><?= format_date($inv['date']) ?></td>
                    <td><span class="badge badge-<?= $sc ?>"><?= ucfirst($inv['status']) ?></span></td>
                    <td style="text-align:right" class="td-amount"><?= format_money($inv['total']) ?></td>
                    <td style="text-align:right" class="td-amount <?= ($inv['total']-$inv['paid'])>0?'out':'' ?>">
                        <?= format_money($inv['total']-$inv['paid']) ?>
                    </td>
                    <td>
                        <a href="/books/<?= $book['id'] ?>/invoices/<?= $inv['id'] ?>"
                           class="btn btn-sm btn-secondary">View</a>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- EDIT CUSTOMER MODAL -->
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
                <div class="form-group full">
                    <label>Privilege</label>
                    <select name="privilege_id">
                        <option value="">— None —</option>
                        <?php foreach ($privileges as $priv): ?>
                        <option value="<?= $priv['id'] ?>"
                            <?= $customer['privilege_id'] == $priv['id'] ? 'selected' : '' ?>>
                            <?= e($priv['name']) ?>
                            (<?= $priv['discount_type']==='percent'
                                ? $priv['discount_value'].'%'
                                : '৳'.number_format($priv['discount_value'],2) ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
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

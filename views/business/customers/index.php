<?php
$pageTitle = 'Customers — ' . e($book['name']);
ob_start();
?>
<div class="page-header">
    <div class="page-header-left">
        <div class="breadcrumb">
            <a href="/dashboard">Dashboard</a> <span>›</span>
            <a href="/books/<?= $book['id'] ?>"><?= e($book['name']) ?></a> <span>›</span>
            <span>Customers</span>
        </div>
        <h1><i class="fa-solid fa-users" style="color:var(--brand)"></i> Customers</h1>
        <p>Add, edit, remove customers and keep track of all of them</p>
        <p><?= count($customers) ?> customer<?= count($customers) !== 1 ? 's' : '' ?></p>
    </div>
    <button class="btn btn-primary" data-modal="addCustomerModal">+ Add Customer</button>
</div>

<!-- Search bar -->
<form method="GET" style="margin-bottom:16px;display:flex;gap:8px">
    <input type="text" name="q" value="<?= e($_GET['q'] ?? '') ?>"
           placeholder="Search by name, phone or email…"
           style="padding:8px 12px;border:1.5px solid var(--border);border-radius:var(--radius);font-size:14px;font-family:inherit;flex:1;outline:none">
    <button type="submit" class="btn btn-secondary">Search</button>
    <?php if (!empty($_GET['q'])): ?>
        <a href="/books/<?= $book['id'] ?>/customers" class="btn btn-secondary">Clear</a>
    <?php endif; ?>
</form>

<?php if (empty($customers)): ?>
<div class="table-wrap">
    <div class="empty-state">
        <div class="empty-icon">👥</div>
        <h3>No customers yet</h3>
        <p>Add your first customer to start creating invoices.</p>
    </div>
</div>
<?php else: ?>
<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Phone</th>
                <th>Points</th>
                <th>Invoices</th>
                <th style="text-align:right">Billed</th>
                <th style="text-align:right">Paid</th>
                <th style="text-align:right">Due</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($customers as $c):
            $due = $c['total_billed'] - $c['total_paid'];
        ?>
        <tr>
            <td>
                <a href="/books/<?= $book['id'] ?>/customers/<?= $c['id'] ?>"
                   style="font-weight:500;color:var(--brand);text-decoration:none">
                    <?= e($c['name']) ?>
                </a>
                <?php if ($c['email']): ?>
                    <div class="td-muted" style="font-size:12px"><?= e($c['email']) ?></div>
                <?php endif; ?>
            </td>
            <td class="td-muted"><?= $c['phone'] ? e($c['phone']) : '—' ?></td>
            <td>
                <?php if ($c['points'] > 0): ?>
                    <span class="badge badge-amber"><?= $c['points'] ?> pts</span>
                <?php else: ?>
                    <span class="td-muted">—</span>
                <?php endif; ?>
            </td>
            <td class="td-muted"><?= $c['invoice_count'] ?></td>
            <td style="text-align:right" class="td-amount"><?= format_money($c['total_billed']) ?></td>
            <td style="text-align:right" class="td-amount in"><?= format_money($c['total_paid']) ?></td>
            <td style="text-align:right">
                <span class="td-amount <?= $due > 0 ? 'out' : '' ?>"><?= format_money($due) ?></span>
            </td>
            <td style="text-align:right;white-space:nowrap">
                <a href="/books/<?= $book['id'] ?>/customers/<?= $c['id'] ?>" class="btn btn-sm btn-secondary">View</a>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<!-- ADD CUSTOMER MODAL -->
<div class="modal-backdrop" id="addCustomerModal">
    <div class="modal">
        <div class="modal-title">Add Customer</div>
        <form method="POST" action="/books/<?= $book['id'] ?>/customers/add">
            <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
            <div class="form-grid" style="gap:12px">
                <div class="form-group full">
                    <label>Name *</label>
                    <input type="text" name="name" placeholder="Customer name" required>
                </div>
                <div class="form-group">
                    <label>Phone</label>
                    <input type="text" name="phone" placeholder="+880…">
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" placeholder="email@example.com">
                </div>
                <div class="form-group full">
                    <label>Address</label>
                    <textarea name="address" placeholder="Address…" style="min-height:56px"></textarea>
                </div>
                <div class="form-group full">
                    <label>Notes</label>
                    <textarea name="notes" placeholder="Any notes…" style="min-height:48px"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-close-modal>Cancel</button>
                <button type="submit" class="btn btn-primary">Save Customer</button>
            </div>
        </form>
    </div>
</div>

<?php $content = ob_get_clean(); require BASE_PATH . '/views/partials/layout.php'; ?>

<?php
$pageTitle = 'Suppliers — ' . e($book['name']);
ob_start();
?>
<div class="page-header">
    <div class="page-header-left">
        <div class="breadcrumb">
            <a href="/dashboard">Dashboard</a> <span>›</span>
            <a href="/books/<?= $book['id'] ?>"><?= e($book['name']) ?></a> <span>›</span>
            <span>Suppliers</span>
        </div>
        <h1><i class="fa-solid fa-truck" style="color:var(--brand)"></i> Suppliers</h1>
        <p>Add, edit, remove suppliers and keep track of all of them</p>
        <p><?= count($suppliers) ?> supplier<?= count($suppliers) !== 1 ? 's' : '' ?></p>
    </div>
        <button class="btn btn-primary" data-modal="addSupplierModal">+ Add Supplier</button>
</div>

<form method="GET" style="margin-bottom:16px;display:flex;gap:8px">
    <input type="text" name="q" value="<?= e($_GET['q'] ?? '') ?>" placeholder="Search suppliers…"
           style="padding:8px 12px;border:1.5px solid var(--border);border-radius:var(--radius);font-size:14px;font-family:inherit;flex:1;outline:none">
    <button type="submit" class="btn btn-secondary">Search</button>
    <?php if (!empty($_GET['q'])): ?>
        <a href="/books/<?= $book['id'] ?>/suppliers" class="btn btn-secondary">Clear</a>
    <?php endif; ?>
</form>

<?php if (empty($suppliers)): ?>
<div class="table-wrap">
    <div class="empty-state">
        <div class="empty-icon">🏭</div>
        <h3>No suppliers yet</h3>
        <p>Add suppliers to track your purchases.</p>
    </div>
</div>
<?php else: ?>
<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Company</th>
                <th>Phone</th>
                <th>Invoices</th>
                <th style="text-align:right">Total Purchased</th>
                <th style="text-align:right">Paid</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($suppliers as $s): ?>
        <tr>
            <td>
                <a href="/books/<?= $book['id'] ?>/suppliers/<?= $s['id'] ?>"
                   style="font-weight:500;color:var(--brand);text-decoration:none"><?= e($s['name']) ?></a>
            </td>
            <td class="td-muted"><?= $s['company'] ? e($s['company']) : '—' ?></td>
            <td class="td-muted"><?= $s['phone']   ? e($s['phone'])   : '—' ?></td>
            <td class="td-muted"><?= $s['invoice_count'] ?></td>
            <td style="text-align:right" class="td-amount"><?= format_money($s['total_billed']) ?></td>
            <td style="text-align:right" class="td-amount in"><?= format_money($s['total_paid']) ?></td>
            <td style="white-space:nowrap">
                <a href="/books/<?= $book['id'] ?>/suppliers/<?= $s['id'] ?>" title="View" class="btn btn-sm btn-secondary"><i class="fa-solid fa-eye"></i></a>
                <button class="btn btn-sm btn-secondary" title="Edit" data-modal="editSupplierModal"><i class="fa-solid fa-pen"></i></button>
                <form method="POST" action="/books/<?= $book['id'] ?>/suppliers/<?= $supplier['id'] ?>/delete" style="display: inline;"
                data-confirm="Delete <?= e($supplier['name']) ?>?">
                <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
                <button class="btn btn-sm btn-danger" title="Delete"><i class="fa-solid fa-trash" style="color: #fff;"></i></button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<!-- ADD SUPPLIER MODAL -->
<div class="modal-backdrop" id="addSupplierModal">
    <div class="modal">
        <div class="modal-title">Add Supplier</div>
        <form method="POST" action="/books/<?= $book['id'] ?>/suppliers/add">
            <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
            <div class="form-grid" style="gap:12px">
                <div class="form-group full"><label>Name *</label><input type="text" name="name" required placeholder="Contact name"></div>
                <div class="form-group full"><label>Company</label><input type="text" name="company" placeholder="Company name"></div>
                <div class="form-group"><label>Phone</label><input type="text" name="phone" placeholder="+880…"></div>
                <div class="form-group"><label>Email</label><input type="email" name="email"></div>
                <div class="form-group full"><label>Address</label><textarea name="address" style="min-height:56px"></textarea></div>
                <div class="form-group full"><label>Notes</label><textarea name="notes" style="min-height:48px"></textarea></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-close-modal>Cancel</button>
                <button type="submit" class="btn btn-primary">Save Supplier</button>
            </div>
        </form>
    </div>
</div>

<!-- EDIT MODAL -->
<div class="modal-backdrop" id="editSupplierModal">
    <div class="modal">
        <div class="modal-title">Edit Supplier</div>
        <form method="POST" action="/books/<?= $book['id'] ?>/suppliers/<?= $s['id'] ?>/edit">
            <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
            <div class="form-grid" style="gap:12px">
                <div class="form-group full"><label>Name *</label><input type="text" name="name" value="<?= e($s['name']) ?>" required></div>
                <div class="form-group full"><label>Company</label><input type="text" name="company" value="<?= e($s['company'] ?? '') ?>"></div>
                <div class="form-group"><label>Phone</label><input type="text" name="phone" value="<?= e($s['phone'] ?? '') ?>"></div>
                <div class="form-group"><label>Email</label><input type="email" name="email" value="<?= e($s['email'] ?? '') ?>"></div>
                <div class="form-group full"><label>Address</label><textarea name="address" style="min-height:56px"><?= e($s['address'] ?? '') ?></textarea></div>
                <div class="form-group full"><label>Notes</label><textarea name="notes" style="min-height:48px"><?= e($s['notes'] ?? '') ?></textarea></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-close-modal>Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<?php $content = ob_get_clean(); require BASE_PATH . '/views/partials/layout.php'; ?>

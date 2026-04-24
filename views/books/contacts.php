<?php
$pageTitle = 'Contacts — ' . e($book['name']);
ob_start();
?>

<div class="page-header">
    <div class="page-header-left">
        <div class="breadcrumb">
            <a href="/dashboard">Dashboard</a> <span>›</span>
            <a href="/books/<?= $book['id'] ?>"><?= e($book['name']) ?></a> <span>›</span>
            <span>Contacts</span>
        </div>
        <h1>Contacts</h1>
        <p><?= count($contacts) ?> contact<?= count($contacts) !== 1 ? 's' : '' ?> in this book</p>
    </div>
    <button class="btn btn-primary" data-modal="addContactModal">
        <svg viewBox="0 0 20 20" fill="currentColor" width="14" height="14"><path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"/></svg>
        Add Contact
    </button>
</div>

<?php if (empty($contacts)): ?>
<div class="table-wrap">
    <div class="empty-state">
        <div class="empty-icon">👤</div>
        <h3>No contacts yet</h3>
        <p>Add contacts to link them to your entries.</p>
    </div>
</div>
<?php else: ?>
<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Phone</th>
                <th>Email</th>
                <th>Entries</th>
                <th style="text-align:right">In</th>
                <th style="text-align:right">Out</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($contacts as $c): ?>
        <tr>
            <td><span style="font-weight:500"><?= e($c['name']) ?></span></td>
            <td class="td-muted"><?= $c['phone'] ? e($c['phone']) : '—' ?></td>
            <td class="td-muted"><?= $c['email'] ? e($c['email']) : '—' ?></td>
            <td class="td-muted"><?= $c['entry_count'] ?></td>
            <td style="text-align:right" class="td-amount in"><?= format_money($c['total_in']) ?></td>
            <td style="text-align:right" class="td-amount out"><?= format_money($c['total_out']) ?></td>
            <td style="text-align:right">
                <form method="POST"
                      action="/books/<?= $book['id'] ?>/contacts/<?= $c['id'] ?>/delete"
                      data-confirm="Delete contact &quot;<?= e($c['name']) ?>&quot;?">
                    <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
                    <button class="btn btn-sm btn-danger">Delete</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<!-- ADD CONTACT MODAL -->
<div class="modal-backdrop" id="addContactModal">
    <div class="modal">
        <div class="modal-title">Add Contact</div>
        <form method="POST" action="/books/<?= $book['id'] ?>/contacts/add">
            <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
            <div class="form-grid" style="gap:12px">
                <div class="form-group full">
                    <label>Name *</label>
                    <input type="text" name="name" placeholder="Full name" required>
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
                    <textarea name="notes" placeholder="Any notes…" style="min-height:56px"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-close-modal>Cancel</button>
                <button type="submit" class="btn btn-primary">Save Contact</button>
            </div>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
require BASE_PATH . '/views/partials/layout.php';
?>

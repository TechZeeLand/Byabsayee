<?php
$pageTitle = 'Edit Book — Byabsayee';
$details   = null;
if ($book['type'] === 'business') {
    $details = \App\Helpers\Database::row(
        'SELECT * FROM book_business_details WHERE book_id = ?', [$book['id']]
    );
}
ob_start();
?>

<div class="page-header">
    <div class="page-header-left">
        <div class="breadcrumb">
            <a href="/dashboard">Dashboard</a> <span>›</span>
            <a href="/books/<?= $book['id'] ?>"><?= e($book['name']) ?></a> <span>›</span>
            <span>Edit</span>
        </div>
        <h1>Edit Book</h1>
    </div>
</div>

<div style="max-width:560px">

    <form action="/books/<?= $book['id'] ?>/edit" method="POST" class="card" style="margin-bottom:20px">
        <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">

        <div class="form-grid">
            <div class="form-group full">
                <label for="name">Book name *</label>
                <input type="text" id="name" name="name" value="<?= e($book['name']) ?>" required>
            </div>
            <div class="form-group">
                <label for="color">Colour tag</label>
                <input type="color" id="color" name="color" value="<?= e($book['color']) ?>"
                       style="width:42px;height:38px;padding:2px;cursor:pointer;border:1.5px solid var(--border);border-radius:8px;background:none">
            </div>
        </div>

        <?php if ($book['type'] === 'business' && $details): ?>
        <div style="margin-top:16px;padding-top:16px;border-top:1px solid var(--border)">
            <p class="card-title">Business Details</p>
            <div class="form-grid">
                <div class="form-group full">
                    <label>Business name</label>
                    <input type="text" name="business_name" value="<?= e($details['business_name'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Phone</label>
                    <input type="text" name="phone" value="<?= e($details['phone'] ?? '') ?>">
                </div>
                <div class="form-group full">
                    <label>Address</label>
                    <textarea name="address" style="min-height:64px"><?= e($details['address'] ?? '') ?></textarea>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div style="margin-top:20px;display:flex;gap:10px">
            <button type="submit" class="btn btn-primary">Save Changes</button>
            <a href="/books/<?= $book['id'] ?>" class="btn btn-secondary">Cancel</a>
        </div>
    </form>

    <!-- Danger zone -->
    <div class="card" style="border-color:#fecaca">
        <p class="card-title" style="color:var(--red)">Danger Zone</p>
        <p style="font-size:13px;color:var(--text-muted);margin-bottom:14px">
            Deleting this book is permanent and will hide all its entries, contacts, and data.
        </p>
        <form method="POST" action="/books/<?= $book['id'] ?>/delete"
              data-confirm="Delete &quot;<?= e($book['name']) ?>&quot;? This cannot be undone.">
            <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
            <button type="submit" class="btn btn-danger">Delete this book</button>
        </form>
    </div>

</div>

<?php
$content = ob_get_clean();
require BASE_PATH . '/views/partials/layout.php';
?>

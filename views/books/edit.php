<?php
$pageTitle = 'Edit Book — Byabsayee';
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

<div style="max-width:640px;display:flex;flex-direction:column;gap:16px">

<form action="/books/<?= $book['id'] ?>/edit" method="POST" enctype="multipart/form-data">
    <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">

    <!-- Basic -->
    <div class="card" style="margin-bottom:16px">
        <p class="card-title">Book Details</p>
        <div class="form-grid">
            <div class="form-group full">
                <label>Book name *</label>
                <input type="text" name="name" value="<?= e($book['name']) ?>" required>
            </div>
            <div class="form-group">
                <label>Card colour</label>
                <input type="color" name="color" value="<?= e($book['color'] ?? '#1a6b4a') ?>"
                       style="width:42px;height:38px;padding:2px;cursor:pointer;border:1.5px solid var(--border);border-radius:8px;background:none">
            </div>
        </div>
    </div>

    <?php if ($book['type'] === 'business'): ?>

    <div class="card" style="margin-bottom:16px">
        <p class="card-title">Business Details</p>
        <div class="form-grid">
            <div class="form-group full">
                <label>Business / Shop name</label>
                <input type="text" name="business_name"
                       value="<?= e($details['business_name'] ?? $book['name']) ?>">
            </div>
            <div class="form-group">
                <label>Phone</label>
                <input type="text" name="phone"
                       value="<?= e($book['phone'] ?? $details['phone'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" value="<?= e($book['email'] ?? '') ?>">
            </div>
            <div class="form-group full">
                <label>Address</label>
                <textarea name="address" style="min-height:64px"><?= e($book['address'] ?? $details['address'] ?? '') ?></textarea>
            </div>

            <!-- Logo -->
            <div class="form-group full">
                <label>Logo <span style="color:var(--text-muted);font-weight:400">(PNG/JPG/SVG, max 2MB)</span></label>
                <?php if (!empty($book['logo'])): ?>
                <div style="margin-bottom:8px;display:flex;align-items:center;gap:12px">
                    <img src="<?= asset('uploads/' . $book['logo']) ?>"
                         style="max-height:52px;max-width:160px;object-fit:contain;border:1px solid var(--border);border-radius:6px;padding:4px;background:#fff">
                    <span style="font-size:12px;color:var(--text-muted)">Upload new image to replace</span>
                </div>
                <?php endif; ?>
                <input type="file" name="logo" accept="image/png,image/jpeg,image/webp,image/svg+xml">
            </div>

            <!-- Invoice prefix -->
            <div class="form-group">
                <label>Invoice number prefix</label>
                <input type="text" name="invoice_prefix"
                       value="<?= e($details['invoice_prefix'] ?? 'INV') ?>"
                       style="text-transform:uppercase" maxlength="10"
                       placeholder="INV">
                <small style="font-size:11px;color:var(--text-muted)">
                    Next: <?= e($details['invoice_prefix'] ?? 'INV') ?>-<?= str_pad($details['invoice_counter'] ?? 1, 4, '0', STR_PAD_LEFT) ?>
                </small>
            </div>

            <!-- Theme color -->
            <div class="form-group">
                <label>Invoice theme colour</label>
                <div style="display:flex;gap:8px;align-items:center">
                    <input type="color" name="theme_color"
                           value="<?= e($book['theme_color'] ?? '#1a6b4a') ?>"
                           style="width:42px;height:38px;padding:2px;cursor:pointer;border:1.5px solid var(--border);border-radius:8px;background:none">
                    <span style="font-size:12px;color:var(--text-muted)">Accent color on PDFs</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Delivery methods -->
    <div class="card" style="margin-bottom:16px">
        <p class="card-title">Delivery Methods</p>
        <p style="font-size:12px;color:var(--text-muted);margin-bottom:12px">Options shown in invoice dropdowns. Add, edit or remove.</p>
        <div id="deliveryList" style="display:flex;flex-direction:column;gap:8px">
            <?php foreach ($deliveryMethods as $m): ?>
            <div class="method-row" style="display:flex;gap:8px;align-items:center">
                <input type="text" name="delivery_methods[]" value="<?= e($m['label']) ?>"
                       style="flex:1;padding:7px 10px;border:1.5px solid var(--border);border-radius:8px;font-size:13px;font-family:inherit;outline:none">
                <button type="button" onclick="this.closest('.method-row').remove()"
                        style="background:none;border:none;color:var(--red);cursor:pointer;font-size:22px;line-height:1;padding:0 4px">×</button>
            </div>
            <?php endforeach; ?>
            <?php if (empty($deliveryMethods)): ?>
            <!-- No methods yet — show one empty row -->
            <div class="method-row" style="display:flex;gap:8px;align-items:center">
                <input type="text" name="delivery_methods[]" placeholder="e.g. Home Delivery"
                       style="flex:1;padding:7px 10px;border:1.5px solid var(--border);border-radius:8px;font-size:13px;font-family:inherit;outline:none">
                <button type="button" onclick="this.closest('.method-row').remove()"
                        style="background:none;border:none;color:var(--red);cursor:pointer;font-size:22px;line-height:1;padding:0 4px">×</button>
            </div>
            <?php endif; ?>
        </div>
        <button type="button" onclick="addMethod('deliveryList','delivery_methods[]')"
                class="btn btn-sm btn-secondary" style="margin-top:10px">+ Add Option</button>
    </div>

    <!-- Payment methods -->
    <div class="card" style="margin-bottom:16px">
        <p class="card-title">Payment Methods</p>
        <p style="font-size:12px;color:var(--text-muted);margin-bottom:12px">Options shown in invoice dropdowns.</p>
        <div id="paymentList" style="display:flex;flex-direction:column;gap:8px">
            <?php foreach ($paymentMethods as $m): ?>
            <div class="method-row" style="display:flex;gap:8px;align-items:center">
                <input type="text" name="payment_methods[]" value="<?= e($m['label']) ?>"
                       style="flex:1;padding:7px 10px;border:1.5px solid var(--border);border-radius:8px;font-size:13px;font-family:inherit;outline:none">
                <button type="button" onclick="this.closest('.method-row').remove()"
                        style="background:none;border:none;color:var(--red);cursor:pointer;font-size:22px;line-height:1;padding:0 4px">×</button>
            </div>
            <?php endforeach; ?>
            <?php if (empty($paymentMethods)): ?>
            <div class="method-row" style="display:flex;gap:8px;align-items:center">
                <input type="text" name="payment_methods[]" placeholder="e.g. Cash"
                       style="flex:1;padding:7px 10px;border:1.5px solid var(--border);border-radius:8px;font-size:13px;font-family:inherit;outline:none">
                <button type="button" onclick="this.closest('.method-row').remove()"
                        style="background:none;border:none;color:var(--red);cursor:pointer;font-size:22px;line-height:1;padding:0 4px">×</button>
            </div>
            <?php endif; ?>
        </div>
        <button type="button" onclick="addMethod('paymentList','payment_methods[]')"
                class="btn btn-sm btn-secondary" style="margin-top:10px">+ Add Option</button>
    </div>

    <?php endif; ?>

    <div style="display:flex;gap:10px;margin-bottom:16px">
        <button type="submit" class="btn btn-primary">Save Changes</button>
        <a href="/books/<?= $book['id'] ?>" class="btn btn-secondary">Cancel</a>
    </div>

</form>

<!-- Danger zone — separate form -->
<div class="card" style="border-color:#fecaca">
    <p class="card-title" style="color:var(--red)">Danger Zone</p>
    <p style="font-size:13px;color:var(--text-muted);margin-bottom:14px">
        Deleting this book hides all its data. This action cannot be undone.
    </p>
    <form method="POST" action="/books/<?= $book['id'] ?>/delete"
          data-confirm="Delete &quot;<?= e($book['name']) ?>&quot;? This cannot be undone.">
        <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
        <button type="submit" class="btn btn-danger">Delete this book</button>
    </form>
</div>

</div><!-- end max-width -->

<script>
function addMethod(listId, fieldName) {
    const list = document.getElementById(listId);
    const div  = document.createElement('div');
    div.className = 'method-row';
    div.style.cssText = 'display:flex;gap:8px;align-items:center';
    div.innerHTML = `
        <input type="text" name="${fieldName}" placeholder="New option…"
               style="flex:1;padding:7px 10px;border:1.5px solid var(--border);border-radius:8px;font-size:13px;font-family:inherit;outline:none">
        <button type="button" onclick="this.closest('.method-row').remove()"
                style="background:none;border:none;color:var(--red);cursor:pointer;font-size:22px;line-height:1;padding:0 4px">×</button>`;
    list.appendChild(div);
    div.querySelector('input').focus();
}
</script>

<?php $content = ob_get_clean(); require BASE_PATH . '/views/partials/layout.php'; ?>

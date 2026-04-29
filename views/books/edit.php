<?php
$pageTitle = 'Edit Book — Byabsayee';
$fonts = ['DejaVu Sans','DejaVu Serif','DejaVu Sans Mono'];
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

<div style="max-width:660px;display:flex;flex-direction:column;gap:16px">

<form action="/books/<?= $book['id'] ?>/edit" method="POST" enctype="multipart/form-data">
<input type="hidden" name="_csrf" value="<?= csrf_token() ?>">

<!-- Basic -->
<div class="card">
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

<!-- Business details -->
<div class="card">
    <p class="card-title">Business Details</p>
    <div class="form-grid">
        <div class="form-group full">
            <label>Business / Shop name</label>
            <input type="text" name="business_name" value="<?= e($details['business_name'] ?? $book['name']) ?>">
        </div>
        <div class="form-group">
            <label>Phone</label>
            <input type="text" name="phone" value="<?= e($book['phone'] ?? $details['phone'] ?? '') ?>">
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
                <img src="<?= asset('uploads/'.$book['logo']) ?>"
                     style="max-height:52px;max-width:160px;object-fit:contain;border:1px solid var(--border);border-radius:6px;padding:4px;background:#fff"
                     onerror="this.style.display='none'">
                <span style="font-size:12px;color:var(--text-muted)">Upload new image to replace</span>
            </div>
            <?php endif; ?>
            <input type="file" name="logo" accept="image/png,image/jpeg,image/webp,image/svg+xml">
        </div>
    </div>
</div>

<!-- Invoice settings -->
<div class="card">
    <p class="card-title">Invoice Settings</p>
    <div class="form-grid">
        <div class="form-group">
            <label>Sale invoice prefix</label>
            <input type="text" name="invoice_prefix"
                   value="<?= e($details['invoice_prefix'] ?? 'INV') ?>"
                   style="text-transform:uppercase" maxlength="10" placeholder="INV">
            <small style="font-size:11px;color:var(--text-muted)">
                Next: <?= e($details['invoice_prefix'] ?? 'INV') ?>-<?= str_pad($details['invoice_counter'] ?? 1, 6, '0', STR_PAD_LEFT) ?>
            </small>
        </div>
        <div class="form-group">
            <label>Purchase invoice prefix</label>
            <input type="text" name="invoice_prefix_purchase"
                   value="<?= e($details['invoice_prefix_purchase'] ?? 'PUR') ?>"
                   style="text-transform:uppercase" maxlength="10" placeholder="PUR">
            <small style="font-size:11px;color:var(--text-muted)">
                Next: <?= e($details['invoice_prefix_purchase'] ?? 'PUR') ?>-<?= str_pad($details['invoice_counter_purchase'] ?? 1, 6, '0', STR_PAD_LEFT) ?>
            </small>
        </div>
        <div class="form-group">
            <label>Invoice theme colour</label>
            <div style="display:flex;gap:8px;align-items:center">
                <input type="color" name="theme_color"
                       value="<?= e($book['theme_color'] ?? '#1a6b4a') ?>"
                       style="width:42px;height:38px;padding:2px;cursor:pointer;border:1.5px solid var(--border);border-radius:8px;background:none">
                <span style="font-size:12px;color:var(--text-muted)">Accent color on PDFs</span>
            </div>
        </div>
        <div class="form-group">
            <label>Invoice font</label>
            <select name="invoice_font">
                <?php foreach ($fonts as $f): ?>
                <option value="<?= e($f) ?>" <?= ($details['invoice_font'] ?? 'DejaVu Sans') === $f ? 'selected' : '' ?>>
                    <?= e($f) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
</div>

<!-- Currencies -->
<div class="card">
    <p class="card-title">Currencies</p>
    <p style="font-size:12px;color:var(--text-muted);margin-bottom:12px">
        The default currency is used on new invoices. The symbol appears next to every amount.
    </p>
    <div id="currencyList" style="display:flex;flex-direction:column;gap:10px">
        <?php foreach ($currencies as $ci => $cur): ?>
        <div class="currency-row" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
            <input type="text" name="currencies[<?= $ci ?>][code]"   value="<?= e($cur['code'])   ?>" placeholder="BDT" maxlength="10"
                   style="width:65px;padding:7px 8px;border:1.5px solid var(--border);border-radius:8px;font-size:13px;font-family:inherit;outline:none;text-transform:uppercase">
            <input type="text" name="currencies[<?= $ci ?>][symbol]" value="<?= e($cur['symbol']) ?>" placeholder="৳"  maxlength="10"
                   style="width:55px;padding:7px 8px;border:1.5px solid var(--border);border-radius:8px;font-size:13px;font-family:inherit;outline:none">
            <input type="text" name="currencies[<?= $ci ?>][name]"   value="<?= e($cur['name'])   ?>" placeholder="Bangladeshi Taka"
                   style="flex:1;padding:7px 8px;border:1.5px solid var(--border);border-radius:8px;font-size:13px;font-family:inherit;outline:none">
            <label style="display:flex;align-items:center;gap:5px;font-size:13px;white-space:nowrap;cursor:pointer">
                <input type="radio" name="default_currency" value="<?= $ci ?>" <?= $cur['is_default'] ? 'checked' : '' ?>
                       onchange="setDefault(<?= $ci ?>)">
                Default
            </label>
            <input type="hidden" name="currencies[<?= $ci ?>][is_default]" id="cur_def_<?= $ci ?>" value="<?= $cur['is_default'] ? '1' : '0' ?>">
            <button type="button" onclick="this.closest('.currency-row').remove()"
                    style="background:none;border:none;color:var(--red);cursor:pointer;font-size:22px;line-height:1;padding:0 4px">×</button>
        </div>
        <?php endforeach; ?>
        <?php if (empty($currencies)): ?>
        <div class="currency-row" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
            <input type="text" name="currencies[0][code]"   value="BDT" maxlength="10"
                   style="width:65px;padding:7px 8px;border:1.5px solid var(--border);border-radius:8px;font-size:13px;font-family:inherit;outline:none;text-transform:uppercase">
            <input type="text" name="currencies[0][symbol]" value="৳"  maxlength="10"
                   style="width:55px;padding:7px 8px;border:1.5px solid var(--border);border-radius:8px;font-size:13px;font-family:inherit;outline:none">
            <input type="text" name="currencies[0][name]"   value="Bangladeshi Taka"
                   style="flex:1;padding:7px 8px;border:1.5px solid var(--border);border-radius:8px;font-size:13px;font-family:inherit;outline:none">
            <label style="display:flex;align-items:center;gap:5px;font-size:13px;white-space:nowrap;cursor:pointer">
                <input type="radio" name="default_currency" value="0" checked> Default
            </label>
            <input type="hidden" name="currencies[0][is_default]" id="cur_def_0" value="1">
            <button type="button" onclick="this.closest('.currency-row').remove()"
                    style="background:none;border:none;color:var(--red);cursor:pointer;font-size:22px;line-height:1;padding:0 4px">×</button>
        </div>
        <?php endif; ?>
    </div>
    <button type="button" onclick="addCurrency()" class="btn btn-sm btn-secondary" style="margin-top:10px">+ Add Currency</button>
</div>

<!-- Delivery methods -->
<div class="card">
    <p class="card-title">Delivery Methods</p>
    <div id="deliveryList" style="display:flex;flex-direction:column;gap:8px">
        <?php foreach ($deliveryMethods as $m): ?>
        <div class="method-row" style="display:flex;gap:8px;align-items:center">
            <input type="text" name="delivery_methods[]" value="<?= e($m['label']) ?>"
                   style="flex:1;padding:7px 10px;border:1.5px solid var(--border);border-radius:8px;font-size:13px;font-family:inherit;outline:none">
            <button type="button" onclick="this.closest('.method-row').remove()"
                    style="background:none;border:none;color:var(--red);cursor:pointer;font-size:22px;line-height:1;padding:0 4px">×</button>
        </div>
        <?php endforeach; ?>
    </div>
    <button type="button" onclick="addMethod('deliveryList','delivery_methods[]')"
            class="btn btn-sm btn-secondary" style="margin-top:10px">+ Add Option</button>
</div>

<!-- Payment methods -->
<div class="card">
    <p class="card-title">Payment Methods</p>
    <div id="paymentList" style="display:flex;flex-direction:column;gap:8px">
        <?php foreach ($paymentMethods as $m): ?>
        <div class="method-row" style="display:flex;gap:8px;align-items:center">
            <input type="text" name="payment_methods[]" value="<?= e($m['label']) ?>"
                   style="flex:1;padding:7px 10px;border:1.5px solid var(--border);border-radius:8px;font-size:13px;font-family:inherit;outline:none">
            <button type="button" onclick="this.closest('.method-row').remove()"
                    style="background:none;border:none;color:var(--red);cursor:pointer;font-size:22px;line-height:1;padding:0 4px">×</button>
        </div>
        <?php endforeach; ?>
    </div>
    <button type="button" onclick="addMethod('paymentList','payment_methods[]')"
            class="btn btn-sm btn-secondary" style="margin-top:10px">+ Add Option</button>
</div>

<?php endif; ?>

<div style="display:flex;gap:10px">
    <button type="submit" class="btn btn-primary">Save Changes</button>
    <a href="/books/<?= $book['id'] ?>" class="btn btn-secondary">Cancel</a>
</div>
</form>

<!-- Danger zone -->
<div class="card" style="border-color:#fecaca">
    <p class="card-title" style="color:var(--red)">Danger Zone</p>
    <p style="font-size:13px;color:var(--text-muted);margin-bottom:14px">Deleting this book hides all its data permanently.</p>
    <form method="POST" action="/books/<?= $book['id'] ?>/delete"
          data-confirm="Delete &quot;<?= e($book['name']) ?>&quot;? This cannot be undone.">
        <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
        <button type="submit" class="btn btn-danger">Delete this book</button>
    </form>
</div>

</div>

<script>
let curIdx = <?= count($currencies ?: [['x']]) ?>;

function addCurrency() {
    const list = document.getElementById('currencyList');
    const i    = curIdx++;
    const div  = document.createElement('div');
    div.className = 'currency-row';
    div.style.cssText = 'display:flex;gap:8px;align-items:center;flex-wrap:wrap';
    div.innerHTML = `
        <input type="text" name="currencies[${i}][code]"   placeholder="USD" maxlength="10"
               style="width:65px;padding:7px 8px;border:1.5px solid var(--border);border-radius:8px;font-size:13px;font-family:inherit;outline:none;text-transform:uppercase">
        <input type="text" name="currencies[${i}][symbol]" placeholder="$"   maxlength="10"
               style="width:55px;padding:7px 8px;border:1.5px solid var(--border);border-radius:8px;font-size:13px;font-family:inherit;outline:none">
        <input type="text" name="currencies[${i}][name]"   placeholder="US Dollar"
               style="flex:1;padding:7px 8px;border:1.5px solid var(--border);border-radius:8px;font-size:13px;font-family:inherit;outline:none">
        <label style="display:flex;align-items:center;gap:5px;font-size:13px;white-space:nowrap;cursor:pointer">
            <input type="radio" name="default_currency" value="${i}" onchange="setDefault(${i})"> Default
        </label>
        <input type="hidden" name="currencies[${i}][is_default]" id="cur_def_${i}" value="0">
        <button type="button" onclick="this.closest('.currency-row').remove()"
                style="background:none;border:none;color:var(--red);cursor:pointer;font-size:22px;line-height:1;padding:0 4px">×</button>`;
    list.appendChild(div);
    div.querySelector('input[type=text]').focus();
}

function setDefault(selectedIdx) {
    document.querySelectorAll('[id^="cur_def_"]').forEach(el => {
        const idx = parseInt(el.id.replace('cur_def_',''));
        el.value = idx === selectedIdx ? '1' : '0';
    });
}

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

<?php
$pageTitle = 'Create Book — Byabsayee';
ob_start();
?>

<div class="page-header">
    <div class="page-header-left">
        <div class="breadcrumb">
            <a href="/dashboard">Dashboard</a>
            <span>›</span>
            <span>New Book</span>
        </div>
        <h1>Create a Book</h1>
        <p>Choose personal for simple tracking, business for full features.</p>
    </div>
</div>

<!-- Book type selector -->
<div class="type-selector" id="typePicker" style="display:grid;grid-template-columns:1fr 1fr;gap:14px;max-width:560px;margin-bottom:28px">

    <label class="type-opt" id="opt-personal">
        <input type="radio" name="_type_pick" value="personal" checked onchange="pickType('personal')">
        <div class="type-opt-inner">
            <div class="type-opt-icon">👤</div>
            <div class="type-opt-title">Personal</div>
            <div class="type-opt-desc">Track your own income and expenses. Simple and clean.</div>
        </div>
    </label>

    <label class="type-opt" id="opt-business">
        <input type="radio" name="_type_pick" value="business" onchange="pickType('business')">
        <div class="type-opt-inner">
            <div class="type-opt-icon">🏪</div>
            <div class="type-opt-title">Business</div>
            <div class="type-opt-desc">Customers, invoices, stock, employees and more.</div>
        </div>
    </label>

</div>

<!-- The actual form -->
<form action="/books/create" method="POST" style="max-width:560px">
    <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
    <input type="hidden" name="type" id="bookType" value="personal">

    <div class="card">

        <!-- Always shown -->
        <div class="form-grid">
            <div class="form-group full">
                <label for="name">Book name *</label>
                <input type="text" id="name" name="name"
                       value="<?= old('name') ?>"
                       placeholder="e.g. My Savings, Rahim Store 2025"
                       required autofocus>
            </div>

            <div class="form-group">
                <label for="color">Colour tag</label>
                <div style="display:flex;gap:8px;align-items:center">
                    <input type="color" id="color" name="color" value="#1a6b4a"
                           style="width:42px;height:38px;padding:2px;cursor:pointer;border:1.5px solid var(--border);border-radius:8px;background:none">
                    <span style="font-size:12px;color:var(--text-muted)">Shows as accent on the book card</span>
                </div>
            </div>
        </div>

        <!-- Business-only fields (hidden by default) -->
        <div id="businessFields" style="display:none;margin-top:16px;padding-top:16px;border-top:1px solid var(--border)">
            <p class="card-title" style="margin-bottom:14px">Business Details</p>
            <div class="form-grid">
                <div class="form-group full">
                    <label for="business_name">Business / Shop name</label>
                    <input type="text" id="business_name" name="business_name"
                           placeholder="e.g. Rahim Electronics"
                           value="<?= old('business_name') ?>">
                </div>
                <div class="form-group">
                    <label for="phone">Phone</label>
                    <input type="text" id="phone" name="phone"
                           placeholder="+880 1700 000000"
                           value="<?= old('phone') ?>">
                </div>
                <div class="form-group full">
                    <label for="address">Address</label>
                    <textarea id="address" name="address"
                              placeholder="Shop address..."
                              style="min-height:64px"><?= old('address') ?></textarea>
                </div>
            </div>
        </div>

        <div style="margin-top:20px;display:flex;gap:10px">
            <button type="submit" class="btn btn-primary">Create Book</button>
            <a href="/dashboard" class="btn btn-secondary">Cancel</a>
        </div>

    </div>
</form>

<style>
.type-opt { cursor: pointer; }
.type-opt input { display: none; }

.type-opt-inner {
    border: 2px solid var(--border);
    border-radius: var(--radius-lg);
    padding: 18px;
    transition: border-color 0.15s, background 0.15s;
    height: 100%;
}

.type-opt input:checked + .type-opt-inner {
    border-color: var(--brand);
    background: var(--brand-light);
}

.type-opt-icon  { font-size: 26px; margin-bottom: 8px; }
.type-opt-title { font-size: 15px; font-weight: 600; margin-bottom: 4px; }
.type-opt-desc  { font-size: 12px; color: var(--text-muted); line-height: 1.5; }
</style>

<script>
function pickType(type) {
    document.getElementById('bookType').value = type;
    document.getElementById('businessFields').style.display = type === 'business' ? 'block' : 'none';
}
// Restore state if validation failed and page reloaded
(function() {
    var saved = '<?= old('type', 'personal') ?>';
    if (saved === 'business') {
        document.querySelector('input[value="business"]').checked = true;
        pickType('business');
    }
})();
</script>

<?php
$content = ob_get_clean();
require BASE_PATH . '/views/partials/layout.php';
?>

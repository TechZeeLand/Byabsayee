<?php
$pageTitle = 'New Invoice — ' . e($book['name']);
$isSale    = ($type !== 'purchase');
$sym       = $defaultCurrency['symbol'] ?? '৳';
ob_start();
?>

<div class="page-header">
    <div class="page-header-left">
        <div class="breadcrumb">
            <a href="/dashboard">Dashboard</a> <span>›</span>
            <a href="/books/<?= $book['id'] ?>"><?= e($book['name']) ?></a> <span>›</span>
            <a href="/books/<?= $book['id'] ?>/invoices">Invoices</a> <span>›</span>
            <span>New <?= $isSale ? 'Sale' : 'Purchase' ?></span>
        </div>
        <h1>New <?= $isSale ? 'Sale Invoice' : 'Purchase Invoice' ?></h1>
    </div>
</div>

<form method="POST" action="/books/<?= $book['id'] ?>/invoices/create" id="invoiceForm">
<input type="hidden" name="_csrf"           value="<?= csrf_token() ?>">
<input type="hidden" name="type"            value="<?= e($type) ?>">
<input type="hidden" name="currency_symbol" id="currencySymbol" value="<?= e($sym) ?>">
<input type="hidden" name="currency_code"   id="currencyCode"   value="<?= e($defaultCurrency['code'] ?? 'BDT') ?>">

<div style="display:grid;grid-template-columns:1fr 320px;gap:20px;align-items:start">

<!-- ══ LEFT ══ -->
<div style="display:flex;flex-direction:column;gap:16px">

    <!-- Header -->
    <div class="card">
        <div class="form-grid">
            <div class="form-group">
                <label>Invoice Number *</label>
                <input type="text" name="invoice_no" value="<?= e($invoiceNo) ?>" required>
            </div>
            <div class="form-group">
                <label>Currency</label>
                <select id="currencySelect" onchange="setCurrency(this)">
                    <?php foreach ($currencies as $c): ?>
                    <option value="<?= e($c['symbol']) ?>"
                            data-code="<?= e($c['code']) ?>"
                            <?= $c['is_default'] ? 'selected' : '' ?>>
                        <?= e($c['code']) ?> (<?= e($c['symbol']) ?>)
                    </option>
                    <?php endforeach; ?>
                    <?php if (empty($currencies)): ?>
                    <option value="৳" data-code="BDT" selected>BDT (৳)</option>
                    <?php endif; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Date *</label>
                <input type="date" name="date" value="<?= date('Y-m-d') ?>" required>
            </div>
            <div class="form-group">
                <label>Due Date</label>
                <input type="date" name="due_date">
            </div>

            <?php if ($isSale): ?>
            <div class="form-group full">
                <label>Customer</label>
                <select name="customer_id" id="customerSel" onchange="customerSelected(this)">
                    <option value="">— Walk-in Customer —</option>
                    <?php foreach ($customers as $c): ?>
                    <option value="<?= $c['id'] ?>"
                            data-points="<?= $c['points'] ?>"
                            <?= ($_GET['customer_id'] ?? '') == $c['id'] ? 'selected' : '' ?>>
                        <?= e($c['name']) ?><?= $c['phone'] ? ' — '.$c['phone'] : '' ?>
                        (<?= $c['points'] ?> pts)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php else: ?>
            <div class="form-group full">
                <label>Supplier</label>
                <select name="supplier_id">
                    <option value="">— Select Supplier —</option>
                    <?php foreach ($suppliers as $s): ?>
                    <option value="<?= $s['id'] ?>"
                            <?= ($_GET['supplier_id'] ?? '') == $s['id'] ? 'selected' : '' ?>>
                        <?= e($s['name']) ?><?= $s['company'] ? ' ('.$s['company'].')' : '' ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <?php if ($isSale): ?>
            <div class="form-group">
                <label>Delivery Method</label>
                <select name="delivery_method">
                    <option value="">— None —</option>
                    <?php foreach ($deliveryMethods as $m): ?>
                    <option value="<?= e($m['label']) ?>"><?= e($m['label']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Payment Method</label>
                <select name="payment_method">
                    <option value="">— None —</option>
                    <?php foreach ($paymentMethods as $m): ?>
                    <option value="<?= e($m['label']) ?>"><?= e($m['label']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Product search by ID / barcode -->
    <div class="card" style="padding:14px">
        <div style="display:flex;gap:8px;align-items:center">
            <div style="flex:1">
                <input type="text" id="productSearch"
                       placeholder="🔍  Scan barcode or type product ID / name to add…"
                       oninput="searchProduct(this.value)"
                       style="width:100%;padding:8px 12px;border:1.5px solid var(--border);border-radius:8px;font-size:13px;font-family:inherit;outline:none">
            </div>
        </div>
        <div id="searchResults" style="display:none;margin-top:8px;border:1px solid var(--border);border-radius:8px;overflow:hidden;max-height:200px;overflow-y:auto"></div>
    </div>

    <!-- Line items -->
    <div class="card">
        <p class="card-title">Items</p>
        <div style="overflow-x:auto">
        <table style="width:100%;border-collapse:collapse" id="itemsTable">
            <thead>
                <tr>
                    <th style="padding:6px;font-size:11px;font-weight:600;text-transform:uppercase;color:var(--text-muted);border-bottom:1px solid var(--border);text-align:left">Product / Description</th>
                    <th style="padding:6px;font-size:11px;font-weight:600;text-transform:uppercase;color:var(--text-muted);border-bottom:1px solid var(--border);width:100px">Color/Size</th>
                    <th style="padding:6px;font-size:11px;font-weight:600;text-transform:uppercase;color:var(--text-muted);border-bottom:1px solid var(--border);width:65px;text-align:right">Qty</th>
                    <th style="padding:6px;font-size:11px;font-weight:600;text-transform:uppercase;color:var(--text-muted);border-bottom:1px solid var(--border);width:90px;text-align:right">Price</th>
                    <th style="padding:6px;font-size:11px;font-weight:600;text-transform:uppercase;color:var(--text-muted);border-bottom:1px solid var(--border);width:60px;text-align:right">Disc%</th>
                    <th style="padding:6px;font-size:11px;font-weight:600;text-transform:uppercase;color:var(--text-muted);border-bottom:1px solid var(--border);width:90px;text-align:right">Total</th>
                    <th style="border-bottom:1px solid var(--border);width:28px"></th>
                </tr>
            </thead>
            <tbody id="itemsBody"></tbody>
        </table>
        </div>
        <button type="button" onclick="addRow()" class="btn btn-sm btn-secondary" style="margin-top:10px">+ Add Item</button>
    </div>

    <!-- Notes -->
    <div class="card">
        <div class="form-grid">
            <div class="form-group">
                <label>Customer Note</label>
                <textarea name="note_customer" placeholder="e.g. Thank you for your order!" style="min-height:60px"></textarea>
            </div>
            <div class="form-group">
                <label>Seller Note</label>
                <textarea name="note_seller" placeholder="e.g. No refund after 7 days" style="min-height:60px"></textarea>
            </div>
        </div>
    </div>

</div><!-- end LEFT -->

<!-- ══ RIGHT: summary panel ══ -->
<div style="position:sticky;top:20px;display:flex;flex-direction:column;gap:12px">
    <div class="card">
        <p class="card-title">Summary</p>
        <div style="display:flex;flex-direction:column;gap:9px;font-size:14px">

            <div style="display:flex;justify-content:space-between">
                <span style="color:var(--text-muted)">Subtotal</span>
                <strong id="summarySubtotal">0.00</strong>
            </div>

            <div style="display:flex;justify-content:space-between;align-items:center">
                <label style="color:var(--text-muted)">Discount</label>
                <input type="number" name="discount" id="inp_discount" value="0" min="0" step="0.01"
                       oninput="recalc()" class="summary-input">
            </div>

            <?php if ($isSale): ?>
            <!-- Points toggle -->
            <div style="display:flex;justify-content:space-between;align-items:center">
                <div style="display:flex;align-items:center;gap:8px">
                    <label style="color:var(--text-muted)">Use Points</label>
                    <label style="display:flex;align-items:center;gap:4px;cursor:pointer">
                        <input type="checkbox" id="usePointsToggle" onchange="togglePoints()"
                               style="width:14px;height:14px;accent-color:var(--brand)">
                        <span id="availablePoints" style="font-size:11px;color:var(--text-muted)"></span>
                    </label>
                </div>
                <input type="number" name="points_discount" id="inp_points" value="0" min="0" step="1"
                       oninput="recalc()" class="summary-input" disabled>
            </div>

            <div style="display:flex;justify-content:space-between;align-items:center">
                <label style="color:var(--text-muted)">Delivery</label>
                <input type="number" name="delivery_charge" id="inp_delivery" value="0" min="0" step="0.01"
                       oninput="recalc()" class="summary-input">
            </div>
            <?php endif; ?>

            <div style="display:flex;justify-content:space-between;align-items:center">
                <label style="color:var(--text-muted)">Tax</label>
                <input type="number" name="tax" id="inp_tax" value="0" min="0" step="0.01"
                       oninput="recalc()" class="summary-input">
            </div>

            <div style="display:flex;justify-content:space-between;align-items:center">
                <div>
                    <label style="color:var(--text-muted)">Rounding</label>
                    <div style="font-size:11px;color:var(--text-muted)">rounds down to nearest 5/10</div>
                </div>
                <input type="number" name="rounding" id="inp_rounding" value="0" min="0" step="0.01"
                       oninput="recalc()" class="summary-input">
            </div>

            <div style="border-top:2px solid var(--border);padding-top:10px;display:flex;justify-content:space-between">
                <strong style="font-size:16px">Grand Total</strong>
                <strong id="summaryTotal" style="font-size:18px;color:var(--brand)">0.00</strong>
            </div>
        </div>
    </div>

    <button type="submit" class="btn btn-primary" style="width:100%;height:46px;font-size:15px">
        Save Invoice
    </button>
    <a href="/books/<?= $book['id'] ?>/invoices" class="btn btn-secondary"
       style="width:100%;text-align:center">Cancel</a>
</div>

</div><!-- end grid -->
</form>

<style>
.summary-input {
    width:100px;padding:5px 8px;border:1.5px solid var(--border);
    border-radius:7px;font-size:13px;font-family:inherit;text-align:right;outline:none;
}
.summary-input:disabled { background:var(--bg);color:var(--text-muted); }
.summary-input:focus { border-color:var(--brand); }
</style>

<script>
// All products data for JS search/autocomplete
const PRODUCTS = <?= json_encode(array_map(fn($p) => [
    'id'    => $p['id'],
    'name'  => $p['name'],
    'code'  => $p['product_code'] ?? '',
    'sku'   => $p['sku'] ?? '',
    'price' => $isSale ? (float)$p['sell_price'] : (float)$p['buy_price'],
    'unit'  => $p['unit'],
    'stock' => (float)$p['stock_qty'],
], $products), JSON_UNESCAPED_UNICODE) ?>;

let rowCount    = 0;
let currentSym  = '<?= e($sym) ?>';
let customerPts = 0;

function esc(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ── Currency ─────────────────────────────────────────────────────────────────
function setCurrency(sel) {
    currentSym = sel.value;
    document.getElementById('currencySymbol').value = currentSym;
    document.getElementById('currencyCode').value   = sel.options[sel.selectedIndex].dataset.code;
    recalc();
}

// ── Customer points ───────────────────────────────────────────────────────────
function customerSelected(sel) {
    const opt = sel.options[sel.selectedIndex];
    customerPts = parseInt(opt.dataset.points || 0);
    const el = document.getElementById('availablePoints');
    if (el) el.textContent = customerPts > 0 ? `(${customerPts} pts available)` : '';
    const toggle = document.getElementById('usePointsToggle');
    if (toggle && !customerPts) { toggle.checked = false; togglePoints(); }
}

function togglePoints() {
    const cb  = document.getElementById('usePointsToggle');
    const inp = document.getElementById('inp_points');
    if (!inp) return;
    inp.disabled = !cb.checked;
    if (cb.checked) {
        inp.value = customerPts;
    } else {
        inp.value = 0;
    }
    recalc();
}

// ── Product search by ID / barcode / name ─────────────────────────────────────
function searchProduct(q) {
    const box = document.getElementById('searchResults');
    q = q.trim().toLowerCase();
    if (!q) { box.style.display = 'none'; return; }

    const matches = PRODUCTS.filter(p =>
        p.name.toLowerCase().includes(q) ||
        (p.code && p.code.toLowerCase().includes(q)) ||
        (p.sku  && p.sku.toLowerCase().includes(q))
    ).slice(0, 8);

    if (!matches.length) { box.style.display = 'none'; return; }

    box.innerHTML = matches.map(p => `
        <div onclick="addProductRow(${p.id})"
             style="padding:10px 12px;cursor:pointer;border-bottom:1px solid var(--border);font-size:13px;display:flex;justify-content:space-between;align-items:center"
             onmouseover="this.style.background='var(--bg)'" onmouseout="this.style.background=''">
            <div>
                <strong>${esc(p.name)}</strong>
                <span style="color:var(--text-muted);margin-left:8px;font-size:11px">${esc(p.code)} · ${p.unit}</span>
            </div>
            <div style="color:var(--brand);font-weight:600">${currentSym}${p.price.toFixed(2)}</div>
        </div>`
    ).join('');
    box.style.display = 'block';
}

function addProductRow(pid) {
    const p = PRODUCTS.find(x => x.id === pid);
    if (!p) return;
    addRow(p.name, 1, p.price, 0, pid);
    document.getElementById('productSearch').value = '';
    document.getElementById('searchResults').style.display = 'none';
}

// Close search on outside click
document.addEventListener('click', e => {
    if (!e.target.closest('#productSearch') && !e.target.closest('#searchResults')) {
        document.getElementById('searchResults').style.display = 'none';
    }
});

// ── Add item row ───────────────────────────────────────────────────────────────
function addRow(name='', qty=1, price=0, disc=0, pid='') {
    const i     = rowCount++;
    const tbody = document.getElementById('itemsBody');
    const tr    = document.createElement('tr');
    tr.id = 'row_'+i;

    let opts = '<option value="">— select —</option>';
    PRODUCTS.forEach(p => {
        opts += `<option value="${p.id}" data-price="${p.price}">${esc(p.name)} [${esc(p.code)}]</option>`;
    });

    tr.innerHTML = `
        <td style="padding:5px 4px">
            <select onchange="productSelected2(this,${i})"
                style="width:100%;padding:5px 6px;border:1.5px solid var(--border);border-radius:7px;font-size:12px;font-family:inherit;outline:none;margin-bottom:3px">
                ${opts}
            </select>
            <input type="text" name="item_name[]" id="iname_${i}" value="${esc(name)}"
                   placeholder="or type item name…" required oninput="recalc()"
                   style="width:100%;padding:5px 6px;border:1.5px solid var(--border);border-radius:7px;font-size:13px;font-family:inherit;outline:none">
            <input type="hidden" name="item_product_id[]" id="ipid_${i}" value="${pid}">
        </td>
        <td style="padding:5px 4px">
            <input type="text" name="item_variant[]" placeholder="Red/XL…"
                   style="width:100%;padding:5px 6px;border:1.5px solid var(--border);border-radius:7px;font-size:12px;font-family:inherit;outline:none">
        </td>
        <td style="padding:5px 4px">
            <input type="number" name="item_qty[]" id="iqty_${i}" value="${qty}"
                   min="0.001" step="0.001" oninput="recalc()"
                   style="width:100%;padding:5px 6px;border:1.5px solid var(--border);border-radius:7px;font-size:12px;text-align:right;font-family:inherit;outline:none">
        </td>
        <td style="padding:5px 4px">
            <input type="number" name="item_price[]" id="iprice_${i}" value="${price}"
                   min="0" step="0.01" oninput="recalc()"
                   style="width:100%;padding:5px 6px;border:1.5px solid var(--border);border-radius:7px;font-size:12px;text-align:right;font-family:inherit;outline:none">
        </td>
        <td style="padding:5px 4px">
            <input type="number" name="item_discount[]" id="idisc_${i}" value="${disc}"
                   min="0" max="100" step="0.01" oninput="recalc()"
                   style="width:100%;padding:5px 6px;border:1.5px solid var(--border);border-radius:7px;font-size:12px;text-align:right;font-family:inherit;outline:none">
        </td>
        <td style="padding:5px 4px;text-align:right;font-weight:600;font-size:12px" id="iline_${i}">0.00</td>
        <td style="padding:5px 4px;text-align:center">
            <button type="button" onclick="removeRow(${i})"
                style="background:none;border:none;color:var(--red);cursor:pointer;font-size:20px;line-height:1">×</button>
        </td>`;
    tbody.appendChild(tr);

    if (pid) {
        const sel = tr.querySelector('select');
        sel.value = pid;
    }
    recalc();
}

function productSelected2(sel, i) {
    const pid  = sel.value;
    const prod = PRODUCTS.find(p => p.id == pid);
    if (prod) {
        document.getElementById('iname_'+i).value  = prod.name;
        document.getElementById('iprice_'+i).value = prod.price;
        document.getElementById('ipid_'+i).value   = prod.id;
    } else {
        document.getElementById('ipid_'+i).value = '';
    }
    recalc();
}

function removeRow(i) {
    const r = document.getElementById('row_'+i);
    if (r) r.remove();
    recalc();
}

function recalc() {
    let subtotal = 0;
    for (let i = 0; i < rowCount; i++) {
        const qEl = document.getElementById('iqty_'+i);
        const pEl = document.getElementById('iprice_'+i);
        const dEl = document.getElementById('idisc_'+i);
        const lEl = document.getElementById('iline_'+i);
        if (!qEl) continue;
        const line = (parseFloat(qEl.value)||0) * (parseFloat(pEl.value)||0) * (1-(parseFloat(dEl.value)||0)/100);
        lEl.textContent = currentSym + line.toFixed(2);
        subtotal += line;
    }

    const disc     = parseFloat(document.getElementById('inp_discount')?.value)||0;
    const points   = parseFloat(document.getElementById('inp_points')?.value)||0;
    const delivery = parseFloat(document.getElementById('inp_delivery')?.value)||0;
    const tax      = parseFloat(document.getElementById('inp_tax')?.value)||0;
    const rounding = parseFloat(document.getElementById('inp_rounding')?.value)||0;
    const total    = Math.max(0, subtotal - disc - points + delivery + tax - rounding);

    document.getElementById('summarySubtotal').textContent = currentSym + subtotal.toFixed(2);
    document.getElementById('summaryTotal').textContent    = currentSym + total.toFixed(2);
}

// Start with one empty row
addRow();
</script>

<?php $content = ob_get_clean(); require BASE_PATH . '/views/partials/layout.php'; ?>

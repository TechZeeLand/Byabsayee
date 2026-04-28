<?php
$pageTitle = 'New Invoice — ' . e($book['name']);
$isSale    = ($type !== 'purchase');
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
<input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
<input type="hidden" name="type" value="<?= e($type) ?>">

<div style="display:grid;grid-template-columns:1fr 320px;gap:20px;align-items:start">

    <!-- LEFT -->
    <div style="display:flex;flex-direction:column;gap:16px">

        <!-- Header fields -->
        <div class="card">
            <div class="form-grid">
                <div class="form-group">
                    <label>Invoice Number *</label>
                    <input type="text" name="invoice_no" value="<?= e($invoiceNo) ?>" required>
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
                    <select name="customer_id">
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

        <!-- Line items -->
        <div class="card">
            <p class="card-title">Items</p>
            <div style="overflow-x:auto">
            <table style="width:100%;border-collapse:collapse" id="itemsTable">
                <thead>
                    <tr>
                        <th style="padding:6px 6px;font-size:11px;font-weight:600;text-transform:uppercase;color:var(--text-muted);border-bottom:1px solid var(--border);text-align:left">Product / Description</th>
                        <th style="padding:6px 6px;font-size:11px;font-weight:600;text-transform:uppercase;color:var(--text-muted);border-bottom:1px solid var(--border);width:110px">Color / Size</th>
                        <th style="padding:6px 6px;font-size:11px;font-weight:600;text-transform:uppercase;color:var(--text-muted);border-bottom:1px solid var(--border);width:70px;text-align:right">Qty</th>
                        <th style="padding:6px 6px;font-size:11px;font-weight:600;text-transform:uppercase;color:var(--text-muted);border-bottom:1px solid var(--border);width:100px;text-align:right">Price (৳)</th>
                        <th style="padding:6px 6px;font-size:11px;font-weight:600;text-transform:uppercase;color:var(--text-muted);border-bottom:1px solid var(--border);width:70px;text-align:right">Disc%</th>
                        <th style="padding:6px 6px;font-size:11px;font-weight:600;text-transform:uppercase;color:var(--text-muted);border-bottom:1px solid var(--border);width:100px;text-align:right">Total</th>
                        <th style="border-bottom:1px solid var(--border);width:30px"></th>
                    </tr>
                </thead>
                <tbody id="itemsBody"></tbody>
            </table>
            </div>
            <button type="button" onclick="addRow()" class="btn btn-sm btn-secondary" style="margin-top:12px">+ Add Item</button>
        </div>

        <div class="card">
            <div class="form-group">
                <label>Notes (printed on invoice)</label>
                <textarea name="notes" placeholder="Payment terms, delivery info, thank you message…" style="min-height:70px"></textarea>
            </div>
        </div>
    </div>

    <!-- RIGHT: totals panel -->
    <div style="position:sticky;top:20px;display:flex;flex-direction:column;gap:12px">
        <div class="card">
            <p class="card-title">Summary</p>
            <div style="display:flex;flex-direction:column;gap:10px;font-size:14px">

                <div style="display:flex;justify-content:space-between">
                    <span style="color:var(--text-muted)">Subtotal</span>
                    <strong id="summarySubtotal">৳0.00</strong>
                </div>

                <div style="display:flex;justify-content:space-between;align-items:center">
                    <label for="inp_discount" style="color:var(--text-muted)">Discount (৳)</label>
                    <input type="number" name="discount" id="inp_discount" value="0" min="0" step="0.01"
                           oninput="recalc()"
                           style="width:100px;padding:5px 8px;border:1.5px solid var(--border);border-radius:7px;font-size:13px;font-family:inherit;text-align:right;outline:none">
                </div>

                <?php if ($isSale): ?>
                <div style="display:flex;justify-content:space-between;align-items:center">
                    <label for="inp_points" style="color:var(--text-muted)">Points discount (৳)</label>
                    <input type="number" name="points_discount" id="inp_points" value="0" min="0" step="1"
                           oninput="recalc()"
                           style="width:100px;padding:5px 8px;border:1.5px solid var(--border);border-radius:7px;font-size:13px;font-family:inherit;text-align:right;outline:none">
                </div>
                <div style="display:flex;justify-content:space-between;align-items:center">
                    <label for="inp_delivery" style="color:var(--text-muted)">Delivery charge (৳)</label>
                    <input type="number" name="delivery_charge" id="inp_delivery" value="0" min="0" step="0.01"
                           oninput="recalc()"
                           style="width:100px;padding:5px 8px;border:1.5px solid var(--border);border-radius:7px;font-size:13px;font-family:inherit;text-align:right;outline:none">
                </div>
                <?php endif; ?>

                <div style="display:flex;justify-content:space-between;align-items:center">
                    <label for="inp_tax" style="color:var(--text-muted)">Tax (৳)</label>
                    <input type="number" name="tax" id="inp_tax" value="0" min="0" step="0.01"
                           oninput="recalc()"
                           style="width:100px;padding:5px 8px;border:1.5px solid var(--border);border-radius:7px;font-size:13px;font-family:inherit;text-align:right;outline:none">
                </div>

                <div style="border-top:2px solid var(--border);padding-top:10px;display:flex;justify-content:space-between">
                    <strong style="font-size:16px">Grand Total</strong>
                    <strong id="summaryTotal" style="font-size:18px;color:var(--brand)">৳0.00</strong>
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-primary" style="width:100%;height:46px;font-size:15px">Save Invoice</button>
        <a href="/books/<?= $book['id'] ?>/invoices" class="btn btn-secondary" style="width:100%;text-align:center">Cancel</a>
    </div>

</div>
</form>

<script>
const PRODUCTS = <?= json_encode(array_map(fn($p) => [
    'id'    => $p['id'],
    'name'  => $p['name'],
    'price' => $isSale ? (float)$p['sell_price'] : (float)$p['buy_price'],
    'unit'  => $p['unit'],
], $products), JSON_UNESCAPED_UNICODE) ?>;

let rowCount = 0;

function esc(s) { return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

function addRow() {
    const i = rowCount++;
    const tbody = document.getElementById('itemsBody');
    const tr = document.createElement('tr');
    tr.id = 'row_'+i;

    let opts = '<option value="">— select product —</option>';
    PRODUCTS.forEach(p => { opts += `<option value="${p.id}" data-price="${p.price}">${esc(p.name)}</option>`; });

    tr.innerHTML = `
        <td style="padding:5px 4px">
            <select onchange="productSelected(this,${i})"
                style="width:100%;padding:6px 8px;border:1.5px solid var(--border);border-radius:7px;font-size:13px;font-family:inherit;outline:none;margin-bottom:4px">
                ${opts}
            </select>
            <input type="text" name="item_name[]" id="iname_${i}" placeholder="or type item name…" required
                oninput="recalc()"
                style="width:100%;padding:6px 8px;border:1.5px solid var(--border);border-radius:7px;font-size:13px;font-family:inherit;outline:none">
            <input type="hidden" name="item_product_id[]" id="ipid_${i}" value="">
        </td>
        <td style="padding:5px 4px">
            <input type="text" name="item_variant[]" placeholder="Red / XL"
                style="width:100%;padding:6px 8px;border:1.5px solid var(--border);border-radius:7px;font-size:13px;font-family:inherit;outline:none">
        </td>
        <td style="padding:5px 4px">
            <input type="number" name="item_qty[]" id="iqty_${i}" value="1" min="0.001" step="0.001"
                oninput="recalc()"
                style="width:100%;padding:6px 8px;border:1.5px solid var(--border);border-radius:7px;font-size:13px;text-align:right;font-family:inherit;outline:none">
        </td>
        <td style="padding:5px 4px">
            <input type="number" name="item_price[]" id="iprice_${i}" value="0" min="0" step="0.01"
                oninput="recalc()"
                style="width:100%;padding:6px 8px;border:1.5px solid var(--border);border-radius:7px;font-size:13px;text-align:right;font-family:inherit;outline:none">
        </td>
        <td style="padding:5px 4px">
            <input type="number" name="item_discount[]" id="idisc_${i}" value="0" min="0" max="100" step="0.01"
                oninput="recalc()"
                style="width:100%;padding:6px 8px;border:1.5px solid var(--border);border-radius:7px;font-size:13px;text-align:right;font-family:inherit;outline:none">
        </td>
        <td style="padding:5px 4px;text-align:right;font-weight:600;font-size:13px" id="iline_${i}">৳0.00</td>
        <td style="padding:5px 4px;text-align:center">
            <button type="button" onclick="removeRow(${i})"
                style="background:none;border:none;color:var(--red);cursor:pointer;font-size:20px;line-height:1">×</button>
        </td>`;
    tbody.appendChild(tr);
    recalc();
}

function productSelected(sel, i) {
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
        lEl.textContent = '৳'+line.toFixed(2);
        subtotal += line;
    }
    const disc     = parseFloat(document.getElementById('inp_discount')?.value)||0;
    const points   = parseFloat(document.getElementById('inp_points')?.value)||0;
    const delivery = parseFloat(document.getElementById('inp_delivery')?.value)||0;
    const tax      = parseFloat(document.getElementById('inp_tax')?.value)||0;
    const total    = subtotal - disc - points + delivery + tax;
    document.getElementById('summarySubtotal').textContent = '৳'+subtotal.toFixed(2);
    document.getElementById('summaryTotal').textContent    = '৳'+Math.max(0,total).toFixed(2);
}

addRow(); // start with one empty row
</script>

<?php $content = ob_get_clean(); require BASE_PATH . '/views/partials/layout.php'; ?>

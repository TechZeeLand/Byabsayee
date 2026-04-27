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

<div style="display:grid;grid-template-columns:1fr 340px;gap:20px;align-items:start">

    <!-- LEFT: main invoice -->
    <div style="display:flex;flex-direction:column;gap:16px">

        <!-- Header info -->
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
                    <select name="customer_id" id="customerSelect">
                        <option value="">— Walk-in Customer —</option>
                        <?php foreach ($customers as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= ($_GET['customer_id'] ?? '') == $c['id'] ? 'selected' : '' ?>>
                            <?= e($c['name']) ?><?= $c['phone'] ? ' — '.$c['phone'] : '' ?>
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
                        <option value="<?= $s['id'] ?>" <?= ($_GET['supplier_id'] ?? '') == $s['id'] ? 'selected' : '' ?>>
                            <?= e($s['name']) ?><?= $s['company'] ? ' ('.$s['company'].')' : '' ?>
                        </option>
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
            <table style="width:100%;border:none" id="itemsTable">
                <thead>
                    <tr style="background:none">
                        <th style="padding:6px 8px;font-size:11px;text-align:left;color:var(--text-muted);font-weight:600;text-transform:uppercase;border-bottom:1px solid var(--border)">Item / Product</th>
                        <th style="padding:6px 8px;font-size:11px;text-align:right;color:var(--text-muted);font-weight:600;text-transform:uppercase;border-bottom:1px solid var(--border);width:80px">Qty</th>
                        <th style="padding:6px 8px;font-size:11px;text-align:right;color:var(--text-muted);font-weight:600;text-transform:uppercase;border-bottom:1px solid var(--border);width:110px">Price (৳)</th>
                        <th style="padding:6px 8px;font-size:11px;text-align:right;color:var(--text-muted);font-weight:600;text-transform:uppercase;border-bottom:1px solid var(--border);width:80px">Disc%</th>
                        <th style="padding:6px 8px;font-size:11px;text-align:right;color:var(--text-muted);font-weight:600;text-transform:uppercase;border-bottom:1px solid var(--border);width:110px">Total</th>
                        <th style="border-bottom:1px solid var(--border);width:36px"></th>
                    </tr>
                </thead>
                <tbody id="itemsBody">
                <!-- rows injected by JS -->
                </tbody>
            </table>
            </div>

            <button type="button" onclick="addRow()" class="btn btn-secondary btn-sm" style="margin-top:12px">
                + Add Item
            </button>
        </div>

        <!-- Notes -->
        <div class="card">
            <div class="form-group">
                <label>Notes (printed on invoice)</label>
                <textarea name="notes" placeholder="Payment terms, delivery info…" style="min-height:70px"></textarea>
            </div>
        </div>

    </div>

    <!-- RIGHT: totals -->
    <div style="position:sticky;top:20px;display:flex;flex-direction:column;gap:12px">
        <div class="card">
            <p class="card-title">Summary</p>

            <div style="display:flex;flex-direction:column;gap:10px;font-size:14px">
                <div style="display:flex;justify-content:space-between">
                    <span style="color:var(--text-muted)">Subtotal</span>
                    <strong id="summarySubtotal">৳0.00</strong>
                </div>

                <div style="display:flex;justify-content:space-between;align-items:center">
                    <label for="discount" style="color:var(--text-muted)">Discount (৳)</label>
                    <input type="number" name="discount" id="discount" value="0" min="0" step="0.01"
                           oninput="recalc()"
                           style="width:110px;padding:5px 8px;border:1.5px solid var(--border);border-radius:7px;font-size:13px;font-family:inherit;text-align:right;outline:none">
                </div>

                <div style="display:flex;justify-content:space-between;align-items:center">
                    <label for="tax" style="color:var(--text-muted)">Tax (৳)</label>
                    <input type="number" name="tax" id="tax" value="0" min="0" step="0.01"
                           oninput="recalc()"
                           style="width:110px;padding:5px 8px;border:1.5px solid var(--border);border-radius:7px;font-size:13px;font-family:inherit;text-align:right;outline:none">
                </div>

                <div style="border-top:2px solid var(--border);padding-top:10px;display:flex;justify-content:space-between">
                    <strong style="font-size:16px">Total</strong>
                    <strong id="summaryTotal" style="font-size:18px;color:var(--brand)">৳0.00</strong>
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-primary" style="width:100%;height:46px;font-size:15px">
            Save Invoice
        </button>
        <a href="/books/<?= $book['id'] ?>/invoices" class="btn btn-secondary" style="width:100%;text-align:center">Cancel</a>
    </div>

</div>
</form>

<!-- Products data for JS autocomplete -->
<script>
const PRODUCTS = <?= json_encode(array_map(fn($p) => [
    'id'    => $p['id'],
    'name'  => $p['name'],
    'price' => $isSale ? $p['sell_price'] : $p['buy_price'],
    'stock' => $p['stock_qty'],
    'unit'  => $p['unit'],
], $products), JSON_UNESCAPED_UNICODE) ?>;

let rowCount = 0;

function addRow(name='', qty=1, price=0, disc=0, pid='') {
    const i = rowCount++;
    const tbody = document.getElementById('itemsBody');
    const tr = document.createElement('tr');
    tr.id = 'row_' + i;

    // Build product options
    let opts = '<option value="">— Type or select —</option>';
    PRODUCTS.forEach(p => {
        opts += `<option value="${p.id}" data-price="${p.price}">${p.name} (${p.unit})</option>`;
    });

    tr.innerHTML = `
        <td style="padding:6px 4px">
            <select name="item_product_id[]" onchange="productSelected(this,${i})"
                    style="width:100%;padding:6px 8px;border:1.5px solid var(--border);border-radius:7px;font-size:13px;font-family:inherit;outline:none;margin-bottom:4px">
                ${opts}
            </select>
            <input type="text" name="item_name[]" placeholder="or type custom item…"
                   id="iname_${i}" value="${e(name)}" required
                   oninput="recalc()"
                   style="width:100%;padding:6px 8px;border:1.5px solid var(--border);border-radius:7px;font-size:13px;font-family:inherit;outline:none">
        </td>
        <td style="padding:6px 4px">
            <input type="number" name="item_qty[]" id="iqty_${i}" value="${qty}"
                   min="0.001" step="0.001" oninput="recalc()"
                   style="width:100%;padding:6px 8px;border:1.5px solid var(--border);border-radius:7px;font-size:13px;text-align:right;font-family:inherit;outline:none">
        </td>
        <td style="padding:6px 4px">
            <input type="number" name="item_price[]" id="iprice_${i}" value="${price}"
                   min="0" step="0.01" oninput="recalc()"
                   style="width:100%;padding:6px 8px;border:1.5px solid var(--border);border-radius:7px;font-size:13px;text-align:right;font-family:inherit;outline:none">
        </td>
        <td style="padding:6px 4px">
            <input type="number" name="item_discount[]" id="idisc_${i}" value="${disc}"
                   min="0" max="100" step="0.01" oninput="recalc()"
                   style="width:100%;padding:6px 8px;border:1.5px solid var(--border);border-radius:7px;font-size:13px;text-align:right;font-family:inherit;outline:none">
        </td>
        <td style="padding:6px 4px;text-align:right;font-weight:600" id="iline_${i}">৳0.00</td>
        <td style="padding:6px 4px;text-align:center">
            <button type="button" onclick="removeRow(${i})"
                    style="background:none;border:none;color:var(--red);cursor:pointer;font-size:18px;line-height:1">×</button>
        </td>
    `;
    tbody.appendChild(tr);

    // Set product if pid provided
    if (pid) {
        const sel = tr.querySelector('select');
        sel.value = pid;
        productSelected(sel, i);
    }

    recalc();
}

function e(str) { // basic escape for JS
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function productSelected(sel, i) {
    const pid   = sel.value;
    const prod  = PRODUCTS.find(p => p.id == pid);
    if (prod) {
        document.getElementById('iname_'  + i).value = prod.name;
        document.getElementById('iprice_' + i).value = prod.price;
    }
    recalc();
}

function removeRow(i) {
    const row = document.getElementById('row_' + i);
    if (row) row.remove();
    recalc();
}

function recalc() {
    let subtotal = 0;
    for (let i = 0; i < rowCount; i++) {
        const qtyEl   = document.getElementById('iqty_'   + i);
        const priceEl = document.getElementById('iprice_' + i);
        const discEl  = document.getElementById('idisc_'  + i);
        const lineEl  = document.getElementById('iline_'  + i);
        if (!qtyEl) continue;

        const qty   = parseFloat(qtyEl.value)   || 0;
        const price = parseFloat(priceEl.value) || 0;
        const disc  = parseFloat(discEl.value)  || 0;
        const line  = qty * price * (1 - disc / 100);
        lineEl.textContent = '৳' + line.toFixed(2);
        subtotal += line;
    }

    const discount = parseFloat(document.getElementById('discount').value) || 0;
    const tax      = parseFloat(document.getElementById('tax').value)      || 0;
    const total    = subtotal - discount + tax;

    document.getElementById('summarySubtotal').textContent = '৳' + subtotal.toFixed(2);
    document.getElementById('summaryTotal').textContent    = '৳' + total.toFixed(2);
}

// Start with one empty row
addRow();
</script>

<?php $content = ob_get_clean(); require BASE_PATH . '/views/partials/layout.php'; ?>

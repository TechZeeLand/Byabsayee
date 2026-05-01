<?php
$pageTitle = 'Products — ' . e($book['name']);
ob_start();
?>

<div class="page-header">
    <div class="page-header-left">
        <div class="breadcrumb">
            <a href="/books/<?= $book['id'] ?>">Dashboard</a> <span>›</span>
            <span>Products</span>
        </div>
        <h1>Products & Stock</h1>
    </div>
    <div style="display:flex;gap:8px">
        <button class="btn btn-secondary" data-modal="addCategoryModal">+ Category</button>
        <button class="btn btn-primary"   data-modal="addProductModal">+ Add Product</button>
    </div>
</div>

<!-- Summary -->
<div class="stat-grid" style="grid-template-columns:repeat(4,1fr);max-width:700px;margin-bottom:20px">
    <div class="stat-card"><div class="stat-label">Products</div><div class="stat-value brand"><?= $summary['total_products'] ?></div></div>
    <div class="stat-card"><div class="stat-label">Stock Value</div><div class="stat-value brand"><?= format_money($summary['stock_value']) ?></div></div>
    <div class="stat-card"><div class="stat-label">Low Stock</div><div class="stat-value <?= $summary['low_stock']>0?'red':'green' ?>"><?= $summary['low_stock'] ?></div></div>
    <div class="stat-card"><div class="stat-label">Out of Stock</div><div class="stat-value <?= $summary['out_of_stock']>0?'red':'green' ?>"><?= $summary['out_of_stock'] ?></div></div>
</div>

<!-- Filters -->
<div style="display:flex;gap:8px;margin-bottom:14px;flex-wrap:wrap;align-items:center">
    <form method="GET" style="display:flex;gap:8px;flex:1;min-width:200px">
        <input type="hidden" name="filter" value="<?= e($_GET['filter']??'all') ?>">
        <input type="text" name="q" value="<?= e($_GET['q']??'') ?>"
               placeholder="Search by name, code, barcode…"
               style="padding:7px 12px;border:1.5px solid var(--border);border-radius:var(--radius);font-size:13px;font-family:inherit;flex:1;outline:none">
        <button type="submit" class="btn btn-sm btn-secondary">Search</button>
    </form>
    <?php if (!empty($categories)): ?>
    <select onchange="window.location.href='?cat='+this.value"
            style="padding:7px 10px;border:1.5px solid var(--border);border-radius:var(--radius);font-size:13px;font-family:inherit;outline:none">
        <option value="0" <?= empty($_GET['cat'])?'selected':'' ?>>All Categories</option>
        <?php foreach ($categories as $cat): ?>
        <option value="<?= $cat['id'] ?>" <?= (($_GET['cat']??0)==$cat['id'])?'selected':'' ?>><?= e($cat['name']) ?></option>
        <?php endforeach; ?>
    </select>
    <?php endif; ?>
    <?php $f=$_GET['filter']??'all'; ?>
    <a href="?filter=all" class="btn btn-sm btn-secondary <?= $f==='all'?'btn-primary':'' ?>">All</a>
    <a href="?filter=low" class="btn btn-sm btn-secondary" style="color:var(--amber)">Low Stock</a>
    <a href="?filter=out" class="btn btn-sm btn-secondary" style="color:var(--red)">Out of Stock</a>
</div>

<?php if (empty($products)): ?>
<div class="table-wrap">
    <div class="empty-state">
        <div class="empty-icon">📦</div>
        <h3>No products yet</h3>
        <p>Add products to track inventory and use them in invoices.</p>
    </div>
</div>
<?php else: ?>
<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>Code</th>
                <th>Product</th>
                <th>Category</th>
                <th style="text-align:right">Buy</th>
                <th style="text-align:right">Sell</th>
                <th style="text-align:right">Stock</th>
                <th>Status</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($products as $p):
            $stockStatus = $p['stock_qty'] <= 0
                ? ['label'=>'Out','class'=>'badge-red']
                : ($p['stock_qty'] <= $p['low_stock_alert']
                    ? ['label'=>'Low','class'=>'badge-amber']
                    : ['label'=>'OK','class'=>'badge-green']);
            // Load variants for this product
            $variants = \App\Helpers\Database::query(
                'SELECT * FROM product_variants WHERE product_id=? ORDER BY label,value', [$p['id']]
            );
        ?>
        <tr>
            <td>
                <span style="font-family:monospace;font-size:11px;background:var(--bg);padding:2px 6px;border-radius:4px;border:1px solid var(--border)">
                    <?= e($p['product_code'] ?? 'PRD-?') ?>
                </span>
                <?php if ($p['barcode']): ?>
                <div style="font-size:10px;color:var(--text-muted);margin-top:2px">Bar: <?= e($p['barcode']) ?></div>
                <?php endif; ?>
            </td>
            <td>
                <div style="font-weight:500"><?= e($p['name']) ?></div>
                <?php if (!empty($variants)): ?>
                <div style="display:flex;flex-wrap:wrap;gap:3px;margin-top:3px">
                    <?php foreach (array_slice($variants,0,4) as $v): ?>
                    <span style="font-size:10px;background:var(--blue-bg);color:var(--blue);padding:1px 5px;border-radius:10px">
                        <?= e($v['label']) ?>: <?= e($v['value']) ?>
                    </span>
                    <?php endforeach; ?>
                    <?php if (count($variants) > 4): ?>
                    <span style="font-size:10px;color:var(--text-muted)">+<?= count($variants)-4 ?> more</span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </td>
            <td class="td-muted"><?= $p['category_name'] ? e($p['category_name']) : '—' ?></td>
            <td style="text-align:right" class="td-amount"><?= format_money($p['buy_price']) ?></td>
            <td style="text-align:right" class="td-amount"><?= format_money($p['sell_price']) ?></td>
            <td style="text-align:right;font-weight:600">
                <?= rtrim(rtrim(number_format($p['stock_qty'],3),'0'),'.') ?> <?= e($p['unit']) ?>
            </td>
            <td><span class="badge <?= $stockStatus['class'] ?>"><?= $stockStatus['label'] ?></span></td>
            <td style="white-space:nowrap">
                <button class="btn btn-sm btn-secondary"
                        onclick="openAdjust(<?= $p['id'] ?>,'<?= e(addslashes($p['name'])) ?>',<?= (float)$p['stock_qty'] ?>)">
                    Adjust
                </button>
                <button class="btn btn-sm btn-secondary"
                        onclick="openEdit(<?= htmlspecialchars(json_encode($p),ENT_QUOTES) ?>,<?= htmlspecialchars(json_encode($variants),ENT_QUOTES) ?>)">
                    Edit
                </button>
                <form method="POST" action="/books/<?= $book['id'] ?>/products/<?= $p['id'] ?>/delete"
                      style="display:inline" data-confirm="Delete &quot;<?= e($p['name']) ?>&quot;?">
                    <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
                    <button class="btn btn-sm btn-danger">Del</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<!-- ══ ADD CATEGORY MODAL ══ -->
<div class="modal-backdrop" id="addCategoryModal">
    <div class="modal">
        <div class="modal-title">Add Category</div>
        <form method="POST" action="/books/<?= $book['id'] ?>/products/category/add">
            <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
            <div class="form-grid" style="gap:12px">
                <div class="form-group full">
                    <label>Category Name *</label>
                    <input type="text" name="name" placeholder="e.g. Electronics, Clothing" required autofocus>
                </div>
                <div class="form-group full">
                    <label>Parent Category (optional)</label>
                    <select name="parent_id">
                        <option value="">— Top level —</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>"><?= e($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-close-modal>Cancel</button>
                <button type="submit" class="btn btn-primary">Create Category</button>
            </div>
        </form>
    </div>
</div>

<!-- ══ ADD PRODUCT MODAL ══ -->
<div class="modal-backdrop" id="addProductModal">
    <div class="modal" style="max-width:580px">
        <div class="modal-title">Add Product</div>
        <form method="POST" action="/books/<?= $book['id'] ?>/products/add" enctype="multipart/form-data">
            <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
            <div style="max-height:68vh;overflow-y:auto;padding-right:2px">
            <div class="form-grid" style="gap:12px">
                <div class="form-group full">
                    <label>Product Name *</label>
                    <input type="text" name="name" required placeholder="e.g. Messenger Bag">
                </div>
                <div class="form-group">
                    <label>Category</label>
                    <select name="category_id">
                        <option value="">— None —</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>"><?= e($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Or create new category</label>
                    <input type="text" name="new_category" placeholder="New category name">
                </div>
                <div class="form-group">
                    <label>Barcode (shared by all variants)</label>
                    <input type="text" name="barcode" placeholder="Scan or type barcode">
                </div>
                <div class="form-group">
                    <label>Unit</label>
                    <select name="unit">
                        <?php foreach (['pcs','kg','g','ltr','ml','box','pack','pair','set','m','cm','dozen'] as $u): ?>
                        <option value="<?= $u ?>"><?= $u ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Buy Price</label>
                    <input type="number" name="buy_price"  value="0" step="0.01" min="0">
                </div>
                <div class="form-group">
                    <label>Sell Price</label>
                    <input type="number" name="sell_price" value="0" step="0.01" min="0">
                </div>
                <div class="form-group">
                    <label>Opening Stock</label>
                    <input type="number" name="stock_qty" value="0" step="0.001" min="0">
                </div>
                <div class="form-group">
                    <label>Low Stock Alert</label>
                    <input type="number" name="low_stock_alert" value="5" step="0.001" min="0">
                </div>
                <div class="form-group full">
                    <label>Description</label>
                    <textarea name="description" style="min-height:52px" placeholder="Optional…"></textarea>
                </div>
                <div class="form-group full">
                    <label>Product Image</label>
                    <input type="file" name="image" accept="image/*">
                </div>
                <!-- Variants -->
                <div class="form-group full">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px">
                        <label style="margin:0">Variants (Size / Color / Type)</label>
                        <button type="button" onclick="addVariantRow('variantRows')"
                                class="btn btn-sm btn-secondary">+ Add Variant</button>
                    </div>
                    <div id="variantRows" style="display:flex;flex-direction:column;gap:6px"></div>
                    <div style="font-size:11px;color:var(--text-muted);margin-top:5px">e.g. Label: Color → Value: Red</div>
                </div>
            </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-close-modal>Cancel</button>
                <button type="submit" class="btn btn-primary">Add Product</button>
            </div>
        </form>
    </div>
</div>

<!-- ══ ADJUST STOCK MODAL ══ -->
<div class="modal-backdrop" id="adjustModal">
    <div class="modal">
        <div class="modal-title">Adjust Stock — <span id="adjustName"></span></div>
        <form method="POST" id="adjustForm">
            <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
            <div class="form-grid" style="gap:12px">
                <div class="form-group full">
                    <label>Current qty: <strong id="adjustQty"></strong></label>
                </div>
                <!-- Styled toggle buttons -->
                <div class="form-group full">
                    <label style="display:block;margin-bottom:8px">Action</label>
                    <div style="display:flex;gap:10px">
                        <label id="lbl_add" onclick="setAdjType('add')"
                               style="flex:1;display:flex;align-items:center;justify-content:center;gap:8px;padding:10px;border-radius:9px;cursor:pointer;border:2px solid var(--green);background:var(--green-bg);color:var(--green);font-weight:600;font-size:14px">
                            <input type="radio" name="adjust_type" value="add" id="adj_add" checked style="display:none">
                            ＋ Add Stock
                        </label>
                        <label id="lbl_rem" onclick="setAdjType('remove')"
                               style="flex:1;display:flex;align-items:center;justify-content:center;gap:8px;padding:10px;border-radius:9px;cursor:pointer;border:2px solid var(--border);background:transparent;color:var(--text-muted);font-weight:600;font-size:14px">
                            <input type="radio" name="adjust_type" value="remove" id="adj_rem" style="display:none">
                            － Remove Stock
                        </label>
                    </div>
                </div>
                <div class="form-group full">
                    <label>Quantity *</label>
                    <input type="number" name="qty" step="0.001" min="0.001" required placeholder="0"
                           style="font-size:18px;padding:10px 12px">
                </div>
                <div class="form-group full">
                    <label>Note (optional)</label>
                    <input type="text" name="note" placeholder="e.g. Received from supplier">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-close-modal>Cancel</button>
                <button type="submit" class="btn btn-primary">Update Stock</button>
            </div>
        </form>
    </div>
</div>

<!-- ══ EDIT PRODUCT MODAL ══ -->
<div class="modal-backdrop" id="editProductModal">
    <div class="modal" style="max-width:580px">
        <div class="modal-title">Edit Product</div>
        <form method="POST" id="editProductForm" enctype="multipart/form-data">
            <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
            <div style="max-height:68vh;overflow-y:auto;padding-right:2px">
            <div class="form-grid" style="gap:12px">
                <div class="form-group full"><label>Product Name *</label><input type="text" name="name" id="ep_name" required></div>
                <div class="form-group">
                    <label>Category</label>
                    <select name="category_id" id="ep_category">
                        <option value="">— None —</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>"><?= e($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Unit</label>
                    <select name="unit" id="ep_unit">
                        <?php foreach (['pcs','kg','g','ltr','ml','box','pack','pair','set','m','cm','dozen'] as $u): ?>
                        <option value="<?= $u ?>"><?= $u ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group"><label>Barcode</label><input type="text" name="barcode" id="ep_barcode"></div>
                <div class="form-group"><label>Buy Price</label><input type="number" name="buy_price" id="ep_buy" step="0.01" min="0"></div>
                <div class="form-group"><label>Sell Price</label><input type="number" name="sell_price" id="ep_sell" step="0.01" min="0"></div>
                <div class="form-group"><label>Low Stock Alert</label><input type="number" name="low_stock_alert" id="ep_low" step="0.001" min="0"></div>
                <div class="form-group full"><label>Description</label><textarea name="description" id="ep_desc" style="min-height:52px"></textarea></div>
                <div class="form-group full"><label>New Image</label><input type="file" name="image" accept="image/*"></div>
                <!-- Variants in edit -->
                <div class="form-group full">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px">
                        <label style="margin:0">Variants</label>
                        <button type="button" onclick="addVariantRow('editVariantRows')"
                                class="btn btn-sm btn-secondary">+ Add</button>
                    </div>
                    <div id="editVariantRows" style="display:flex;flex-direction:column;gap:6px"></div>
                </div>
            </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-close-modal>Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
// ── Stock adjust toggle ───────────────────────────────────────────────────────
function setAdjType(type) {
    document.getElementById('adj_add').checked = (type === 'add');
    document.getElementById('adj_rem').checked = (type === 'remove');

    const addLbl = document.getElementById('lbl_add');
    const remLbl = document.getElementById('lbl_rem');

    if (type === 'add') {
        addLbl.style.borderColor = 'var(--green)';
        addLbl.style.background  = 'var(--green-bg)';
        addLbl.style.color       = 'var(--green)';
        remLbl.style.borderColor = 'var(--border)';
        remLbl.style.background  = 'transparent';
        remLbl.style.color       = 'var(--text-muted)';
    } else {
        remLbl.style.borderColor = 'var(--red)';
        remLbl.style.background  = 'var(--red-bg)';
        remLbl.style.color       = 'var(--red)';
        addLbl.style.borderColor = 'var(--border)';
        addLbl.style.background  = 'transparent';
        addLbl.style.color       = 'var(--text-muted)';
    }
}

function openAdjust(id, name, qty) {
    document.getElementById('adjustName').textContent = name;
    document.getElementById('adjustQty').textContent  = qty;
    document.getElementById('adjustForm').action =
        '/books/<?= $book['id'] ?>/products/' + id + '/adjust';
    // Reset to add
    setAdjType('add');
    document.getElementById('adjustModal').classList.add('open');
}

// ── Edit product ──────────────────────────────────────────────────────────────
function openEdit(p, variants) {
    document.getElementById('ep_name').value     = p.name;
    document.getElementById('ep_barcode').value  = p.barcode || '';
    document.getElementById('ep_buy').value      = p.buy_price;
    document.getElementById('ep_sell').value     = p.sell_price;
    document.getElementById('ep_low').value      = p.low_stock_alert;
    document.getElementById('ep_desc').value     = p.description || '';
    document.getElementById('ep_unit').value     = p.unit;
    document.getElementById('ep_category').value = p.category_id || '';
    document.getElementById('editProductForm').action =
        '/books/<?= $book['id'] ?>/products/' + p.id + '/edit';

    // Load existing variants
    const container = document.getElementById('editVariantRows');
    container.innerHTML = '';
    editVarIdx = 0;
    if (variants && variants.length) {
        variants.forEach(v => addVariantRow('editVariantRows', v.label, v.value));
    }

    document.getElementById('editProductModal').classList.add('open');
}

// ── Variant rows ──────────────────────────────────────────────────────────────
let varIdx = 0;
let editVarIdx = 0;

function addVariantRow(containerId, label='', value='') {
    const container = document.getElementById(containerId);
    const isEdit    = containerId === 'editVariantRows';
    const i         = isEdit ? editVarIdx++ : varIdx++;
    const prefix    = isEdit ? 'ev' : 'av';

    const div = document.createElement('div');
    div.style.cssText = 'display:grid;grid-template-columns:1fr 1fr auto;gap:8px;align-items:center';
    div.innerHTML = `
        <input type="text" name="variants[${i}][label]" value="${esc(label)}"
               placeholder="Label (e.g. Color)"
               style="padding:6px 8px;border:1.5px solid var(--border);border-radius:8px;font-size:13px;font-family:inherit;outline:none">
        <input type="text" name="variants[${i}][value]" value="${esc(value)}"
               placeholder="Value (e.g. Red)"
               style="padding:6px 8px;border:1.5px solid var(--border);border-radius:8px;font-size:13px;font-family:inherit;outline:none">
        <button type="button" onclick="this.closest('div').remove()"
                style="background:none;border:none;color:var(--red);cursor:pointer;font-size:20px;line-height:1;padding:0 2px">×</button>`;
    container.appendChild(div);
    div.querySelector('input').focus();
}

function esc(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
</script>

<?php $content = ob_get_clean(); require BASE_PATH . '/views/partials/layout.php'; ?>

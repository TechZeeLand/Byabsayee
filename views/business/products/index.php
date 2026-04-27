<?php
$pageTitle = 'Products — ' . e($book['name']);
ob_start();
?>
<div class="page-header">
    <div class="page-header-left">
        <div class="breadcrumb">
            <a href="/dashboard">Dashboard</a> <span>›</span>
            <a href="/books/<?= $book['id'] ?>"><?= e($book['name']) ?></a> <span>›</span>
            <span>Products</span>
        </div>
        <h1>Products & Stock</h1>
    </div>
    <button class="btn btn-primary" data-modal="addProductModal">+ Add Product</button>
</div>

<!-- Summary -->
<div class="stat-grid" style="grid-template-columns:repeat(4,1fr);max-width:720px;margin-bottom:20px">
    <div class="stat-card">
        <div class="stat-label">Total Products</div>
        <div class="stat-value brand"><?= $summary['total_products'] ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Stock Value</div>
        <div class="stat-value brand"><?= format_money($summary['stock_value']) ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Low Stock</div>
        <div class="stat-value <?= $summary['low_stock'] > 0 ? 'red' : 'green' ?>"><?= $summary['low_stock'] ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Out of Stock</div>
        <div class="stat-value <?= $summary['out_of_stock'] > 0 ? 'red' : 'green' ?>"><?= $summary['out_of_stock'] ?></div>
    </div>
</div>

<!-- Filters -->
<div style="display:flex;gap:8px;margin-bottom:14px;flex-wrap:wrap;align-items:center">
    <form method="GET" style="display:flex;gap:8px;flex:1;min-width:200px">
        <input type="text" name="q" value="<?= e($_GET['q'] ?? '') ?>" placeholder="Search products…"
               style="padding:7px 12px;border:1.5px solid var(--border);border-radius:var(--radius);font-size:13px;font-family:inherit;flex:1;outline:none">
        <input type="hidden" name="filter" value="<?= e($_GET['filter'] ?? 'all') ?>">
        <button type="submit" class="btn btn-sm btn-secondary">Search</button>
    </form>
    <?php $f = $_GET['filter'] ?? 'all'; ?>
    <a href="?filter=all"  class="btn btn-sm btn-secondary <?= $f==='all' ?'btn-primary':'' ?>">All</a>
    <a href="?filter=low"  class="btn btn-sm btn-secondary <?= $f==='low' ?'btn-primary':'' ?>" style="<?= $f==='low'?'':'color:var(--amber)' ?>">Low Stock</a>
    <a href="?filter=out"  class="btn btn-sm btn-secondary <?= $f==='out' ?'btn-primary':'' ?>" style="<?= $f==='out'?'':'color:var(--red)' ?>">Out of Stock</a>
</div>

<?php if (empty($products)): ?>
<div class="table-wrap">
    <div class="empty-state">
        <div class="empty-icon">📦</div>
        <h3>No products yet</h3>
        <p>Add products to track your inventory and include them in invoices.</p>
    </div>
</div>
<?php else: ?>
<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>Product</th>
                <th>SKU</th>
                <th>Category</th>
                <th style="text-align:right">Buy Price</th>
                <th style="text-align:right">Sell Price</th>
                <th style="text-align:right">Stock</th>
                <th>Status</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($products as $p):
            $stockStatus = $p['stock_qty'] <= 0
                ? ['label'=>'Out of stock','class'=>'badge-red']
                : ($p['stock_qty'] <= $p['low_stock_alert']
                    ? ['label'=>'Low stock','class'=>'badge-amber']
                    : ['label'=>'In stock','class'=>'badge-green']);
        ?>
        <tr>
            <td>
                <div style="font-weight:500"><?= e($p['name']) ?></div>
                <?php if ($p['description']): ?>
                    <div class="td-muted" style="font-size:12px"><?= e(mb_strimwidth($p['description'],0,50,'…')) ?></div>
                <?php endif; ?>
            </td>
            <td class="td-muted"><?= $p['sku'] ? e($p['sku']) : '—' ?></td>
            <td class="td-muted"><?= $p['category_name'] ? e($p['category_name']) : '—' ?></td>
            <td style="text-align:right" class="td-amount"><?= format_money($p['buy_price']) ?></td>
            <td style="text-align:right" class="td-amount"><?= format_money($p['sell_price']) ?></td>
            <td style="text-align:right;font-weight:600"><?= rtrim(rtrim(number_format($p['stock_qty'],3),'0'),'.') ?> <?= e($p['unit']) ?></td>
            <td><span class="badge <?= $stockStatus['class'] ?>"><?= $stockStatus['label'] ?></span></td>
            <td style="white-space:nowrap">
                <button class="btn btn-sm btn-secondary"
                        onclick="openAdjust(<?= $p['id'] ?>, '<?= e(addslashes($p['name'])) ?>', <?= $p['stock_qty'] ?>)">
                    Adjust
                </button>
                <button class="btn btn-sm btn-secondary"
                        onclick="openEdit(<?= htmlspecialchars(json_encode($p), ENT_QUOTES) ?>)">
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

<!-- ADD PRODUCT MODAL -->
<div class="modal-backdrop" id="addProductModal">
    <div class="modal" style="max-width:560px">
        <div class="modal-title">Add Product</div>
        <form method="POST" action="/books/<?= $book['id'] ?>/products/add" enctype="multipart/form-data">
            <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
            <div class="form-grid" style="gap:12px">
                <div class="form-group full"><label>Product Name *</label><input type="text" name="name" required placeholder="e.g. Rice 50kg"></div>
                <div class="form-group"><label>SKU / Code</label><input type="text" name="sku" placeholder="e.g. RICE-50"></div>
                <div class="form-group"><label>Barcode</label><input type="text" name="barcode"></div>
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
                    <label>Unit</label>
                    <select name="unit">
                        <?php foreach (['pcs','kg','g','ltr','ml','box','pack','pair','set','m','cm'] as $u): ?>
                        <option value="<?= $u ?>"><?= $u ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group"><label>Buy Price (৳)</label><input type="number" name="buy_price" value="0" step="0.01" min="0"></div>
                <div class="form-group"><label>Sell Price (৳)</label><input type="number" name="sell_price" value="0" step="0.01" min="0"></div>
                <div class="form-group"><label>Opening Stock</label><input type="number" name="stock_qty" value="0" step="0.001" min="0"></div>
                <div class="form-group"><label>Low Stock Alert</label><input type="number" name="low_stock_alert" value="5" step="0.001" min="0"></div>
                <div class="form-group full"><label>Description</label><textarea name="description" style="min-height:56px" placeholder="Optional…"></textarea></div>
                <div class="form-group full"><label>Image (optional)</label><input type="file" name="image" accept="image/*"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-close-modal>Cancel</button>
                <button type="submit" class="btn btn-primary">Add Product</button>
            </div>
        </form>
    </div>
</div>

<!-- ADJUST STOCK MODAL -->
<div class="modal-backdrop" id="adjustModal">
    <div class="modal">
        <div class="modal-title">Adjust Stock — <span id="adjustProductName"></span></div>
        <form method="POST" id="adjustForm">
            <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
            <div class="form-grid" style="gap:12px">
                <div class="form-group full">
                    <label>Current Stock: <strong id="adjustCurrentQty"></strong></label>
                </div>
                <div class="form-group full">
                    <label>Action</label>
                    <div class="type-toggle">
                        <input type="radio" name="adjust_type" id="adj_add" value="add" checked>
                        <label for="adj_add">+ Add Stock</label>
                        <input type="radio" name="adjust_type" id="adj_rem" value="remove">
                        <label for="adj_rem">− Remove Stock</label>
                    </div>
                </div>
                <div class="form-group full"><label>Quantity *</label><input type="number" name="qty" step="0.001" min="0.001" required placeholder="0"></div>
                <div class="form-group full"><label>Note</label><input type="text" name="note" placeholder="e.g. Received from supplier"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-close-modal>Cancel</button>
                <button type="submit" class="btn btn-primary">Update Stock</button>
            </div>
        </form>
    </div>
</div>

<!-- EDIT PRODUCT MODAL -->
<div class="modal-backdrop" id="editProductModal">
    <div class="modal" style="max-width:560px">
        <div class="modal-title">Edit Product</div>
        <form method="POST" id="editProductForm" enctype="multipart/form-data">
            <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
            <div class="form-grid" style="gap:12px">
                <div class="form-group full"><label>Product Name *</label><input type="text" name="name" id="ep_name" required></div>
                <div class="form-group"><label>SKU</label><input type="text" name="sku" id="ep_sku"></div>
                <div class="form-group"><label>Barcode</label><input type="text" name="barcode" id="ep_barcode"></div>
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
                        <?php foreach (['pcs','kg','g','ltr','ml','box','pack','pair','set','m','cm'] as $u): ?>
                        <option value="<?= $u ?>"><?= $u ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group"><label>Buy Price (৳)</label><input type="number" name="buy_price" id="ep_buy" step="0.01" min="0"></div>
                <div class="form-group"><label>Sell Price (৳)</label><input type="number" name="sell_price" id="ep_sell" step="0.01" min="0"></div>
                <div class="form-group"><label>Low Stock Alert</label><input type="number" name="low_stock_alert" id="ep_low" step="0.001" min="0"></div>
                <div class="form-group full"><label>Description</label><textarea name="description" id="ep_desc" style="min-height:56px"></textarea></div>
                <div class="form-group full"><label>New Image (optional)</label><input type="file" name="image" accept="image/*"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-close-modal>Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
function openAdjust(id, name, qty) {
    document.getElementById('adjustProductName').textContent = name;
    document.getElementById('adjustCurrentQty').textContent  = qty;
    document.getElementById('adjustForm').action =
        '/books/<?= $book['id'] ?>/products/' + id + '/adjust';
    document.getElementById('adjustModal').classList.add('open');
}

function openEdit(p) {
    document.getElementById('ep_name').value     = p.name;
    document.getElementById('ep_sku').value      = p.sku     || '';
    document.getElementById('ep_barcode').value  = p.barcode || '';
    document.getElementById('ep_buy').value      = p.buy_price;
    document.getElementById('ep_sell').value     = p.sell_price;
    document.getElementById('ep_low').value      = p.low_stock_alert;
    document.getElementById('ep_desc').value     = p.description || '';
    document.getElementById('ep_unit').value     = p.unit;
    document.getElementById('ep_category').value = p.category_id || '';
    document.getElementById('editProductForm').action =
        '/books/<?= $book['id'] ?>/products/' + p.id + '/edit';
    document.getElementById('editProductModal').classList.add('open');
}
</script>

<?php $content = ob_get_clean(); require BASE_PATH . '/views/partials/layout.php'; ?>

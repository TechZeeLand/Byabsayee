<?php
$pageTitle = 'Coupons — ' . e($book['name']);
ob_start();
?>

<div class="page-header">
    <div class="page-header-left">
        <div class="breadcrumb">
            <a href="/books/<?= $book['id'] ?>">Dashboard</a> <span>›</span>
            <span>Coupons</span>
        </div>
        <h1><i class="fa-solid fa-ticket" style="color:var(--brand)"></i> Coupons</h1>
        <p>Create discount codes for customers</p>
    </div>
    <button class="btn btn-primary" data-modal="addCouponModal">
        <i class="fa-solid fa-plus"></i> New Coupon
    </button>
</div>

<!-- Summary strip -->
<div style="display:flex;gap:12px;margin-bottom:20px;flex-wrap:wrap">
    <div style="background:var(--white);border:1px solid var(--border);border-radius:var(--radius);padding:10px 18px;display:flex;align-items:center;gap:10px">
        <i class="fa-solid fa-ticket" style="color:var(--brand);font-size:18px"></i>
        <div>
            <div style="font-size:22px;font-weight:700;color:var(--brand)"><?= (int)($counts['total']??0) ?></div>
            <div style="font-size:11px;color:var(--text-muted)">Total Coupons</div>
        </div>
    </div>
    <div style="background:var(--white);border:1px solid var(--border);border-radius:var(--radius);padding:10px 18px;display:flex;align-items:center;gap:10px">
        <i class="fa-solid fa-circle-check" style="color:var(--green);font-size:18px"></i>
        <div>
            <div style="font-size:22px;font-weight:700;color:var(--green)"><?= (int)($counts['active_count']??0) ?></div>
            <div style="font-size:11px;color:var(--text-muted)">Active</div>
        </div>
    </div>
    <div style="background:var(--white);border:1px solid var(--border);border-radius:var(--radius);padding:10px 18px;display:flex;align-items:center;gap:10px">
        <i class="fa-solid fa-circle-xmark" style="color:var(--text-muted);font-size:18px"></i>
        <div>
            <div style="font-size:22px;font-weight:700;color:var(--text-muted)"><?= (int)($counts['inactive_count']??0) ?></div>
            <div style="font-size:11px;color:var(--text-muted)">Inactive</div>
        </div>
    </div>
</div>

<!-- Filters -->
<div style="display:flex;gap:6px;margin-bottom:16px;flex-wrap:wrap;align-items:center">
    <?php foreach (['all'=>'All','active'=>'Active','inactive'=>'Inactive'] as $val=>$label): ?>
    <a href="?filter=<?= $val ?><?= $search ? '&q='.urlencode($search) : '' ?>"
       class="btn btn-sm <?= $filter===$val ? 'btn-primary' : 'btn-secondary' ?>">
        <?= $label ?>
    </a>
    <?php endforeach; ?>

    <form method="GET" style="display:flex;gap:6px;margin-left:auto">
        <input type="hidden" name="filter" value="<?= e($filter) ?>">
        <input type="text" name="q" value="<?= e($search) ?>"
               placeholder="Search name or code…"
               style="padding:6px 10px;border:1.5px solid var(--border);border-radius:var(--radius);font-size:13px;font-family:inherit;outline:none;width:200px">
        <button type="submit" class="btn btn-sm btn-secondary">
            <i class="fa-solid fa-magnifying-glass"></i>
        </button>
        <?php if ($search): ?>
        <a href="?filter=<?= e($filter) ?>" class="btn btn-sm btn-secondary">
            <i class="fa-solid fa-xmark"></i>
        </a>
        <?php endif; ?>
    </form>
</div>

<!-- Coupons grid / table -->
<?php if (empty($coupons)): ?>
<div class="table-wrap">
    <div class="empty-state">
        <div class="empty-icon"><i class="fa-solid fa-ticket"></i></div>
        <h3>No coupons yet</h3>
        <p>Create your first discount coupon to use in invoices.</p>
    </div>
</div>
<?php else: ?>
<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Code</th>
                <th>Discount</th>
                <th>Note</th>
                <th>Status</th>
                <th>Created</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($coupons as $c): ?>
        <tr <?= !$c['is_active'] ? 'style="opacity:.6"' : '' ?>>
            <td style="font-weight:600"><?= e($c['name']) ?></td>
            <td>
                <code style="background:var(--bg);padding:3px 8px;border-radius:5px;font-family:monospace;font-size:13px;font-weight:700;letter-spacing:1px;color:var(--brand)">
                    <?= e($c['code']) ?>
                </code>
            </td>
            <td>
                <?php if ($c['discount_type'] === 'percent'): ?>
                <span style="background:#fff8e1;color:#b45309;padding:3px 10px;border-radius:99px;font-size:12px;font-weight:700">
                    <i class="fa-solid fa-percent"></i> <?= (float)$c['discount_value'] ?>% off
                </span>
                <?php else: ?>
                <span style="background:#f0fdf4;color:var(--green);padding:3px 10px;border-radius:99px;font-size:12px;font-weight:700">
                    <i class="fa-solid fa-minus"></i> <?= format_money((float)$c['discount_value']) ?> off
                </span>
                <?php endif; ?>
            </td>
            <td class="td-muted" style="font-size:12px;max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                <?= e($c['note'] ?? '—') ?>
            </td>
            <td>
                <?php if ($c['is_active']): ?>
                <span class="badge badge-green"><i class="fa-solid fa-circle" style="font-size:7px"></i> Active</span>
                <?php else: ?>
                <span class="badge badge-gray"><i class="fa-solid fa-circle" style="font-size:7px"></i> Inactive</span>
                <?php endif; ?>
            </td>
            <td class="td-muted"><?= format_date($c['created_at']) ?></td>
            <td style="white-space:nowrap;text-align:right">
                <!-- Toggle active/inactive -->
                <form method="POST" action="/books/<?= $book['id'] ?>/coupons/<?= $c['id'] ?>/toggle"
                      style="display:inline">
                    <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
                    <button class="btn btn-sm <?= $c['is_active'] ? 'btn-secondary' : 'btn-primary' ?>"
                            title="<?= $c['is_active'] ? 'Deactivate' : 'Activate' ?>">
                        <i class="fa-solid <?= $c['is_active'] ? 'fa-toggle-on' : 'fa-toggle-off' ?>"></i>
                        <?= $c['is_active'] ? 'Active' : 'Inactive' ?>
                    </button>
                </form>
                <!-- Edit -->
                <button class="btn btn-sm btn-secondary" title="Edit"
                        onclick="openCouponEdit(
                            <?= $c['id'] ?>,
                            '<?= e(addslashes($c['name'])) ?>',
                            '<?= e($c['code']) ?>',
                            '<?= $c['discount_type'] ?>',
                            <?= (float)$c['discount_value'] ?>,
                            '<?= e(addslashes($c['note'] ?? '')) ?>'
                        )">
                    <i class="fa-solid fa-pen"></i>
                </button>
                <!-- Delete -->
                <form method="POST" action="/books/<?= $book['id'] ?>/coupons/<?= $c['id'] ?>/delete"
                      style="display:inline" onsubmit="return confirm('Delete coupon <?= e($c['code']) ?>?')">
                    <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
                    <button class="btn btn-sm btn-secondary" title="Delete">
                        <i class="fa-solid fa-trash" style="color:var(--red)"></i>
                    </button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>


<!-- ══ ADD COUPON MODAL ══ -->
<div class="modal-backdrop" id="addCouponModal">
    <div class="modal">
        <div class="modal-title">
            <i class="fa-solid fa-ticket" style="color:var(--brand)"></i> New Coupon
        </div>
        <form method="POST" action="/books/<?= $book['id'] ?>/coupons/add">
            <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
            <div class="form-grid" style="gap:12px">
                <div class="form-group full">
                    <label>Coupon Name *</label>
                    <input type="text" name="name" required placeholder="e.g. Summer Sale, New Customer Discount…">
                </div>
                <div class="form-group full">
                    <label>Coupon Code *
                        <button type="button" class="btn btn-sm btn-secondary" style="margin-left:8px;padding:2px 8px;font-size:11px" onclick="generateCode('addCouponCode')">
                            <i class="fa-solid fa-wand-magic-sparkles"></i> Generate
                        </button>
                    </label>
                    <input type="text" name="code" id="addCouponCode" required
                           placeholder="e.g. SAVE10, SUMMER25"
                           oninput="this.value=this.value.toUpperCase().replace(/[^A-Z0-9\-_]/g,'')">
                    <small class="form-hint">Letters, numbers, hyphens, underscores only (auto-uppercased)</small>
                </div>
                <div class="form-group">
                    <label>Discount Type *</label>
                    <select name="discount_type" id="addDiscountType" onchange="updateValueLabel('add')">
                        <option value="fixed">Fixed Amount</option>
                        <option value="percent">Percentage</option>
                    </select>
                </div>
                <div class="form-group">
                    <label id="addValueLabel">Discount Amount *</label>
                    <div style="display:flex;align-items:center;gap:0">
                        <span id="addValuePrefix" style="padding:0 10px;background:var(--bg);border:1.5px solid var(--border);border-right:0;border-radius:var(--radius) 0 0 var(--radius);height:38px;display:flex;align-items:center;font-size:13px;color:var(--text-muted);white-space:nowrap">৳</span>
                        <input type="number" name="discount_value" min="0.01" step="0.01" required placeholder="0.00"
                               style="border-radius:0 var(--radius) var(--radius) 0;flex:1">
                    </div>
                </div>
                <div class="form-group full">
                    <label>Note (optional)</label>
                    <textarea name="note" style="min-height:50px" placeholder="Terms, expiry info, for internal reference…"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-close-modal>Cancel</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-plus"></i> Create Coupon
                </button>
            </div>
        </form>
    </div>
</div>


<!-- ══ EDIT COUPON MODAL ══ -->
<div class="modal-backdrop" id="editCouponModal">
    <div class="modal">
        <div class="modal-title">
            <i class="fa-solid fa-pen" style="color:var(--brand)"></i> Edit Coupon
        </div>
        <form method="POST" id="editCouponForm">
            <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
            <div class="form-grid" style="gap:12px">
                <div class="form-group full">
                    <label>Coupon Name *</label>
                    <input type="text" name="name" id="editCouponName" required>
                </div>
                <div class="form-group full">
                    <label>Coupon Code *</label>
                    <input type="text" name="code" id="editCouponCode" required
                           oninput="this.value=this.value.toUpperCase().replace(/[^A-Z0-9\-_]/g,'')">
                    <small class="form-hint">Letters, numbers, hyphens, underscores only</small>
                </div>
                <div class="form-group">
                    <label>Discount Type *</label>
                    <select name="discount_type" id="editDiscountType" onchange="updateValueLabel('edit')">
                        <option value="fixed">Fixed Amount</option>
                        <option value="percent">Percentage</option>
                    </select>
                </div>
                <div class="form-group">
                    <label id="editValueLabel">Discount Amount *</label>
                    <div style="display:flex;align-items:center;gap:0">
                        <span id="editValuePrefix" style="padding:0 10px;background:var(--bg);border:1.5px solid var(--border);border-right:0;border-radius:var(--radius) 0 0 var(--radius);height:38px;display:flex;align-items:center;font-size:13px;color:var(--text-muted);white-space:nowrap">৳</span>
                        <input type="number" name="discount_value" id="editCouponValue" min="0.01" step="0.01" required
                               style="border-radius:0 var(--radius) var(--radius) 0;flex:1">
                    </div>
                </div>
                <div class="form-group full">
                    <label>Note (optional)</label>
                    <textarea name="note" id="editCouponNote" style="min-height:50px"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-close-modal>Cancel</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-check"></i> Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<style>
.badge-gray{background:#f3f4f6;color:#6b7280}
</style>

<script>
function updateValueLabel(prefix) {
    const sel    = document.getElementById(prefix + 'DiscountType');
    const label  = document.getElementById(prefix + 'ValueLabel');
    const pfx    = document.getElementById(prefix + 'ValuePrefix');
    const isPercent = sel.value === 'percent';
    label.textContent = isPercent ? 'Discount Percentage *' : 'Discount Amount *';
    pfx.textContent   = isPercent ? '%' : '৳';
}

function generateCode(inputId) {
    const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    let code = '';
    for (let i = 0; i < 8; i++) code += chars[Math.floor(Math.random() * chars.length)];
    document.getElementById(inputId).value = code;
}

function openCouponEdit(id, name, code, discType, discValue, note) {
    document.getElementById('editCouponForm').action      = '/books/<?= $book['id'] ?>/coupons/' + id + '/edit';
    document.getElementById('editCouponName').value       = name;
    document.getElementById('editCouponCode').value       = code;
    document.getElementById('editDiscountType').value     = discType;
    document.getElementById('editCouponValue').value      = discValue;
    document.getElementById('editCouponNote').value       = note;
    updateValueLabel('edit');
    document.getElementById('editCouponModal').classList.add('open');
}
</script>

<?php $content = ob_get_clean(); require BASE_PATH . '/views/partials/layout.php'; ?>

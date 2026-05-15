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
    <div style="display:flex;gap:8px">
        <button class="btn btn-primary" data-modal="addCouponModal">
            <i class="fa-solid fa-plus"></i> New Coupon
        </button>
    </div>
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

<!-- Filters + search -->
<div style="display:flex;gap:6px;margin-bottom:16px;flex-wrap:wrap;align-items:center">
    <?php foreach (['all'=>'All','active'=>'Active','inactive'=>'Inactive'] as $val=>$label): ?>
    <a href="?filter=<?= $val ?><?= $search ? '&q='.urlencode($search) : '' ?>"
       class="btn btn-sm <?= $filter===$val ? 'btn-primary' : 'btn-secondary' ?>">
        <?= $label ?>
    </a>
    <?php endforeach; ?>

    <div style="margin-left:auto;display:flex;gap:6px;align-items:center">
        <!-- Selective print button -->
        <button id="printSelectedBtn" class="btn btn-sm btn-secondary" style="display:none" onclick="printSelected()">
            <i class="fa-solid fa-print"></i> Print Selected
        </button>
        <form method="GET" style="display:flex;gap:6px">
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
</div>

<!-- Table -->
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
                <th style="width:32px">
                    <input type="checkbox" id="selectAll" onchange="toggleSelectAll(this)" style="accent-color:var(--brand)">
                </th>
                <th>Name</th>
                <th>Code</th>
                <th>Discount</th>
                <th>Expiry</th>
                <th>Status</th>
                <th>Created</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($coupons as $c):
            $now      = time();
            $expired  = $c['expires_at'] && strtotime($c['expires_at']) < $now;
            $expiring = !$expired && $c['expires_at'] && strtotime($c['expires_at']) < ($now + 7*86400);
        ?>
        <tr <?= (!$c['is_active'] || $expired) ? 'style="opacity:.65"' : '' ?>>
            <td>
                <input type="checkbox" class="coupon-check" value="<?= $c['id'] ?>"
                       onchange="updatePrintBtn()" style="accent-color:var(--brand)">
            </td>
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
            <td style="font-size:12px">
                <?php if (!$c['expires_at']): ?>
                    <span style="color:var(--text-muted)"><i class="fa-solid fa-infinity" style="font-size:10px"></i> Permanent</span>
                <?php elseif ($expired): ?>
                    <span style="color:var(--red);font-weight:600"><i class="fa-solid fa-clock"></i> Expired <?= date('d M Y', strtotime($c['expires_at'])) ?></span>
                <?php elseif ($expiring): ?>
                    <span style="color:#b45309;font-weight:600"><i class="fa-solid fa-triangle-exclamation"></i> <?= date('d M Y', strtotime($c['expires_at'])) ?></span>
                <?php else: ?>
                    <span><?= date('d M Y', strtotime($c['expires_at'])) ?></span>
                    <div style="font-size:11px;color:var(--text-muted)"><?= date('h:i A', strtotime($c['expires_at'])) ?></div>
                <?php endif; ?>
            </td>
            <td>
                <?php if ($expired): ?>
                <span class="badge" style="background:#fee2e2;color:#dc2626"><i class="fa-solid fa-circle" style="font-size:7px"></i> Expired</span>
                <?php elseif ($c['is_active']): ?>
                <span class="badge badge-green"><i class="fa-solid fa-circle" style="font-size:7px"></i> Active</span>
                <?php else: ?>
                <span class="badge badge-gray"><i class="fa-solid fa-circle" style="font-size:7px"></i> Inactive</span>
                <?php endif; ?>
            </td>
            <td class="td-muted"><?= format_date($c['created_at']) ?></td>
            <td style="white-space:nowrap;text-align:right">
                <!-- Print single -->
                <a href="/books/<?= $book['id'] ?>/coupons/print?ids=<?= $c['id'] ?>"
                   class="btn btn-sm btn-secondary" title="Print this coupon" target="_blank">
                    <i class="fa-solid fa-print"></i>
                </a>
                <!-- Toggle -->
                <form method="POST" action="/books/<?= $book['id'] ?>/coupons/<?= $c['id'] ?>/toggle" style="display:inline">
                    <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
                    <button class="btn btn-sm <?= $c['is_active'] ? 'btn-secondary' : 'btn-primary' ?>"
                            title="<?= $c['is_active'] ? 'Deactivate' : 'Activate' ?>">
                        <i class="fa-solid <?= $c['is_active'] ? 'fa-toggle-on' : 'fa-toggle-off' ?>"></i>
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
                            '<?= e(addslashes($c['note'] ?? '')) ?>',
                            '<?= $c['expires_at'] ? 'date' : 'none' ?>',
                            '<?= $c['expires_at'] ? date('Y-m-d\TH:i', strtotime($c['expires_at'])) : '' ?>'
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
                    <input type="text" name="name" required placeholder="e.g. Summer Sale, New Customer…">
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
                    <div style="display:flex;align-items:center">
                        <span id="addValuePrefix" style="padding:0 10px;background:var(--bg);border:1.5px solid var(--border);border-right:0;border-radius:var(--radius) 0 0 var(--radius);height:38px;display:flex;align-items:center;font-size:13px;color:var(--text-muted)">৳</span>
                        <input type="number" name="discount_value" min="0.01" step="0.01" required placeholder="0.00"
                               style="border-radius:0 var(--radius) var(--radius) 0;flex:1">
                    </div>
                </div>

                <!-- Expiry -->
                <div class="form-group full">
                    <label>Expiration</label>
                    <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
                        <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-size:13px">
                            <input type="radio" name="expiry_type" value="none" checked style="accent-color:var(--brand)"
                                   onchange="toggleExpiry('add', this.value)"> No Expiry
                        </label>
                        <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-size:13px">
                            <input type="radio" name="expiry_type" value="date" style="accent-color:var(--brand)"
                                   onchange="toggleExpiry('add', this.value)"> Set Expiry Date &amp; Time
                        </label>
                    </div>
                    <div id="addExpiryField" style="display:none;margin-top:8px">
                        <input type="datetime-local" name="expires_at" id="addExpiresAt"
                               style="width:100%;padding:8px 10px;border:1.5px solid var(--border);border-radius:var(--radius);font-size:13px;font-family:inherit;outline:none">
                    </div>
                </div>

                <div class="form-group full">
                    <label>Note (optional)</label>
                    <textarea name="note" style="min-height:50px" placeholder="Terms or internal notes…"></textarea>
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
                    <div style="display:flex;align-items:center">
                        <span id="editValuePrefix" style="padding:0 10px;background:var(--bg);border:1.5px solid var(--border);border-right:0;border-radius:var(--radius) 0 0 var(--radius);height:38px;display:flex;align-items:center;font-size:13px;color:var(--text-muted)">৳</span>
                        <input type="number" name="discount_value" id="editCouponValue" min="0.01" step="0.01" required
                               style="border-radius:0 var(--radius) var(--radius) 0;flex:1">
                    </div>
                </div>

                <!-- Expiry -->
                <div class="form-group full">
                    <label>Expiration</label>
                    <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
                        <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-size:13px">
                            <input type="radio" name="expiry_type" id="editExpiryNone" value="none" style="accent-color:var(--brand)"
                                   onchange="toggleExpiry('edit', this.value)"> No Expiry
                        </label>
                        <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-size:13px">
                            <input type="radio" name="expiry_type" id="editExpiryDate" value="date" style="accent-color:var(--brand)"
                                   onchange="toggleExpiry('edit', this.value)"> Set Expiry Date &amp; Time
                        </label>
                    </div>
                    <div id="editExpiryField" style="display:none;margin-top:8px">
                        <input type="datetime-local" name="expires_at" id="editExpiresAt"
                               style="width:100%;padding:8px 10px;border:1.5px solid var(--border);border-radius:var(--radius);font-size:13px;font-family:inherit;outline:none">
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
.badge-gray { background:#f3f4f6; color:#6b7280; }
</style>

<script>
function updateValueLabel(prefix) {
    const sel       = document.getElementById(prefix + 'DiscountType');
    const label     = document.getElementById(prefix + 'ValueLabel');
    const pfx       = document.getElementById(prefix + 'ValuePrefix');
    const isPercent = sel.value === 'percent';
    label.textContent = isPercent ? 'Discount Percentage *' : 'Discount Amount *';
    pfx.textContent   = isPercent ? '%' : '৳';
}

function toggleExpiry(prefix, val) {
    document.getElementById(prefix + 'ExpiryField').style.display = val === 'date' ? 'block' : 'none';
}

function generateCode(inputId) {
    const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    let code = '';
    for (let i = 0; i < 8; i++) code += chars[Math.floor(Math.random() * chars.length)];
    document.getElementById(inputId).value = code;
}

function openCouponEdit(id, name, code, discType, discValue, note, expiryType, expiresAt) {
    document.getElementById('editCouponForm').action  = '/books/<?= $book['id'] ?>/coupons/' + id + '/edit';
    document.getElementById('editCouponName').value   = name;
    document.getElementById('editCouponCode').value   = code;
    document.getElementById('editDiscountType').value = discType;
    document.getElementById('editCouponValue').value  = discValue;
    document.getElementById('editCouponNote').value   = note;

    document.getElementById('editExpiryNone').checked = (expiryType === 'none');
    document.getElementById('editExpiryDate').checked = (expiryType === 'date');
    toggleExpiry('edit', expiryType);
    if (expiresAt) document.getElementById('editExpiresAt').value = expiresAt;

    updateValueLabel('edit');
    document.getElementById('editCouponModal').classList.add('open');
}

// Checkbox-based selective print
function toggleSelectAll(cb) {
    document.querySelectorAll('.coupon-check').forEach(c => c.checked = cb.checked);
    updatePrintBtn();
}

function updatePrintBtn() {
    const any = document.querySelectorAll('.coupon-check:checked').length > 0;
    document.getElementById('printSelectedBtn').style.display = any ? 'inline-flex' : 'none';
    const all  = document.querySelectorAll('.coupon-check').length;
    const chk  = document.querySelectorAll('.coupon-check:checked').length;
    const sa   = document.getElementById('selectAll');
    if (sa) { sa.checked = chk === all && all > 0; sa.indeterminate = chk > 0 && chk < all; }
}

function printSelected() {
    const ids = [...document.querySelectorAll('.coupon-check:checked')].map(c => c.value).join(',');
    if (!ids) return;
    window.open('/books/<?= $book['id'] ?>/coupons/print?ids=' + ids, '_blank');
}
</script>

<?php $content = ob_get_clean(); require BASE_PATH . '/views/partials/layout.php'; ?>

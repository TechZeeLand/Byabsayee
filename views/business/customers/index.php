<?php
$pageTitle = 'Customers — ' . e($book['name']);
ob_start();
?>
<div class="page-header">
    <div class="page-header-left">
        <div class="breadcrumb">
            <a href="/dashboard">Dashboard</a> <span>›</span>
            <a href="/books/<?= $book['id'] ?>"><?= e($book['name']) ?></a> <span>›</span>
            <span>Customers</span>
        </div>
        <h1><i class="fa-solid fa-users" style="color:var(--brand)"></i> Customers</h1>
        <p>Add, edit, remove customers and keep track of all of them</p>
        <p><?= count($customers) ?> customer<?= count($customers) !== 1 ? 's' : '' ?></p>
    </div>
    <div style="display:flex;gap:8px;align-items:center">
        <button class="btn btn-secondary" data-modal="managePrivModal">
            <i class="fa-solid fa-star" style="font-size:13px"></i> Manage Privileges
        </button>
        <button class="btn btn-primary" data-modal="addCustomerModal">+ Add Customer</button>
    </div>
</div>

<!-- Search bar -->
<form method="GET" style="margin-bottom:16px;display:flex;gap:8px">
    <input type="text" name="q" value="<?= e($_GET['q'] ?? '') ?>"
           placeholder="Search by name, phone or email…"
           style="padding:8px 12px;border:1.5px solid var(--border);border-radius:var(--radius);font-size:14px;font-family:inherit;flex:1;outline:none">
    <button type="submit" class="btn btn-secondary">Search</button>
    <?php if (!empty($_GET['q'])): ?>
        <a href="/books/<?= $book['id'] ?>/customers" class="btn btn-secondary">Clear</a>
    <?php endif; ?>
</form>

<?php if (empty($customers)): ?>
<div class="table-wrap">
    <div class="empty-state">
        <div class="empty-icon">👥</div>
        <h3>No customers yet</h3>
        <p>Add your first customer to start creating invoices.</p>
    </div>
</div>
<?php else: ?>
<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Phone</th>
                <th>Points</th>
                <th>Invoices</th>
                <th style="text-align:right">Billed</th>
                <th style="text-align:right">Paid</th>
                <th style="text-align:right">Due</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($customers as $c):
            $due = $c['total_billed'] - $c['total_paid'];
        ?>
        <tr>
            <td>
                <a href="/books/<?= $book['id'] ?>/customers/<?= $c['id'] ?>"
                   style="font-weight:500;color:var(--brand);text-decoration:none">
                    <?= e($c['name']) ?>
                </a>
                <?php if ($c['email']): ?>
                    <div class="td-muted" style="font-size:12px"><?= e($c['email']) ?></div>
                <?php endif; ?>
            </td>
            <td class="td-muted"><?= $c['phone'] ? e($c['phone']) : '—' ?></td>
            <td>
                <?php if ($c['points'] > 0): ?>
                    <span class="badge badge-amber"><?= $c['points'] ?> pts</span>
                <?php else: ?>
                    <span class="td-muted">—</span>
                <?php endif; ?>
            </td>
            <td class="td-muted"><?= $c['invoice_count'] ?></td>
            <td style="text-align:right" class="td-amount"><?= format_money($c['total_billed']) ?></td>
            <td style="text-align:right" class="td-amount in"><?= format_money($c['total_paid']) ?></td>
            <td style="text-align:right">
                <span class="td-amount <?= $due > 0 ? 'out' : '' ?>"><?= format_money($due) ?></span>
            </td>
            <td style="white-space:nowrap">
                <a href="/books/<?= $book['id'] ?>/customers/<?= $c['id'] ?>" title="View" class="btn btn-sm btn-secondary"><i class="fa-solid fa-eye"></i></a>
                <button class="btn btn-sm btn-secondary" title="Edit" data-modal="editCustomerModal"><i class="fa-solid fa-pen"></i></button>
                <form method="POST" action="/books/<?= $book['id'] ?>/customers/<?= $c['id'] ?>/delete"
                    data-confirm="Delete <?= e($c['name']) ?>?">
                    <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
                    <button class="btn btn-sm btn-danger" title="Delete"><i class="fa-solid fa-trash"></i></button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<!-- ADD CUSTOMER MODAL -->
<div class="modal-backdrop" id="addCustomerModal">
    <div class="modal">
        <div class="modal-title">Add Customer</div>
        <form method="POST" action="/books/<?= $book['id'] ?>/customers/add">
            <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
            <div class="form-grid" style="gap:12px">
                <div class="form-group full">
                    <label>Name *</label>
                    <input type="text" name="name" placeholder="Customer name" required>
                </div>
                <div class="form-group">
                    <label>Phone</label>
                    <input type="text" name="phone" placeholder="+880…">
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" placeholder="email@example.com">
                </div>
                <div class="form-group full">
                    <label>Address</label>
                    <textarea name="address" placeholder="Address…" style="min-height:56px"></textarea>
                </div>
                <div class="form-group full">
                    <label>Notes</label>
                    <textarea name="notes" placeholder="Any notes…" style="min-height:48px"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-close-modal>Cancel</button>
                <button type="submit" class="btn btn-primary">Save Customer</button>
            </div>
        </form>
    </div>
</div>


<!-- MANAGE PRIVILEGES MODAL -->
<div class="modal-backdrop" id="managePrivModal">
    <div class="modal" style="max-width:640px;width:100%">
        <div class="modal-title" style="display:flex;justify-content:space-between;align-items:center">
            <span><i class="fa-solid fa-star" style="color:var(--brand);margin-right:6px"></i> Customer Privileges</span>
            <button type="button" class="btn btn-primary btn-sm" data-modal="addPrivModal" style="font-size:13px">+ New Privilege</button>
        </div>

        <?php if (empty($privileges)): ?>
        <div class="empty-state" style="padding:32px 0">
            <div class="empty-icon">🎫</div>
            <h3>No privileges yet</h3>
            <p>Create discount groups like "Relative (2% off)" or "Staff (10% off)".</p>
        </div>
        <?php else: ?>
        <div style="margin-top:4px;max-height:360px;overflow-y:auto">
            <table style="width:100%;border-collapse:collapse;font-size:14px">
                <thead>
                    <tr style="border-bottom:1.5px solid var(--border)">
                        <th style="text-align:left;padding:8px 4px;font-weight:600;color:var(--text-muted);font-size:12px;text-transform:uppercase;letter-spacing:.5px">Name</th>
                        <th style="text-align:left;padding:8px 4px;font-weight:600;color:var(--text-muted);font-size:12px;text-transform:uppercase;letter-spacing:.5px">Discount</th>
                        <th style="text-align:left;padding:8px 4px;font-weight:600;color:var(--text-muted);font-size:12px;text-transform:uppercase;letter-spacing:.5px">Customers</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($privileges as $priv): ?>
                <tr style="border-bottom:1px solid var(--border)">
                    <td style="padding:10px 4px;font-weight:600"><?= e($priv['name']) ?></td>
                    <td style="padding:10px 4px">
                        <span class="badge badge-green">
                            <?php if ($priv['discount_type'] === 'percent'): ?>
                                <?= $priv['discount_value'] ?>% off
                            <?php else: ?>
                                ৳<?= number_format($priv['discount_value'], 2) ?> off
                            <?php endif; ?>
                        </span>
                    </td>
                    <td style="padding:10px 4px;color:var(--text-muted)"><?= $priv['customer_count'] ?> customer<?= $priv['customer_count'] != 1 ? 's' : '' ?></td>
                    <td style="padding:10px 4px;text-align:right;white-space:nowrap">
                        <button class="btn btn-sm btn-secondary"
                                onclick="openPrivEdit(<?= htmlspecialchars(json_encode($priv), ENT_QUOTES) ?>)">Edit</button>
                        <form method="POST" action="/books/<?= $book['id'] ?>/privileges/<?= $priv['id'] ?>/delete"
                              style="display:inline"
                              data-confirm="Delete &quot;<?= e($priv['name']) ?>&quot;? Customers with this privilege will lose their discount.">
                            <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
                            <button class="btn btn-sm btn-danger">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <div style="margin-top:16px;padding:14px;background:var(--brand-light);border:1px solid var(--brand);border-radius:var(--radius);font-size:13px;color:var(--text-muted);line-height:1.6">
            <strong style="color:var(--brand)">How privileges work:</strong>
            Assign a privilege to a customer on their profile page. When you create an invoice for that customer, the discount is shown automatically in the invoice summary.
        </div>

        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-close-modal>Close</button>
        </div>
    </div>
</div>

<!-- ADD PRIVILEGE MODAL -->
<div class="modal-backdrop" id="addPrivModal">
    <div class="modal">
        <div class="modal-title">New Privilege</div>
        <form method="POST" action="/books/<?= $book['id'] ?>/privileges/add">
            <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
            <div class="form-grid" style="gap:12px">
                <div class="form-group full">
                    <label>Name *</label>
                    <input type="text" name="name" placeholder="e.g. Relative, Staff, VIP" required>
                </div>
                <div class="form-group">
                    <label>Discount type</label>
                    <select name="discount_type" id="add_dtype" onchange="togglePrivLabel('add')">
                        <option value="percent">Percentage (%)</option>
                        <option value="fixed">Fixed amount (৳)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label id="add_dlabel">Discount value (%)</label>
                    <input type="number" name="discount_value" value="0" min="0" step="0.01" placeholder="e.g. 5">
                </div>
                <div class="form-group full">
                    <label>Description (optional)</label>
                    <textarea name="description" placeholder="e.g. Family and relatives of the owner" style="min-height:56px"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-close-modal>Cancel</button>
                <button type="submit" class="btn btn-primary">Save Privilege</button>
            </div>
        </form>
    </div>
</div>

<!-- EDIT PRIVILEGE MODAL -->
<div class="modal-backdrop" id="editPrivModal">
    <div class="modal">
        <div class="modal-title">Edit Privilege</div>
        <form method="POST" id="editPrivForm">
            <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
            <div class="form-grid" style="gap:12px">
                <div class="form-group full">
                    <label>Name *</label>
                    <input type="text" name="name" id="ep_name" required>
                </div>
                <div class="form-group">
                    <label>Discount type</label>
                    <select name="discount_type" id="ep_dtype" onchange="togglePrivLabel('ep')">
                        <option value="percent">Percentage (%)</option>
                        <option value="fixed">Fixed amount (৳)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label id="ep_dlabel">Discount value</label>
                    <input type="number" name="discount_value" id="ep_dvalue" min="0" step="0.01">
                </div>
                <div class="form-group full">
                    <label>Description</label>
                    <textarea name="description" id="ep_desc" style="min-height:56px"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-close-modal>Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<!-- EDIT MODAL -->
<div class="modal-backdrop" id="editCustomerModal">
    <div class="modal">
        <div class="modal-title">Edit Customer</div>
        <form method="POST" action="/books/<?= $book['id'] ?>/customers/<?= $c['id'] ?>/edit">
            <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
            <div class="form-grid" style="gap:12px">
                <div class="form-group full"><label>Name *</label><input type="text" name="name" value="<?= e($c['name']) ?>" required></div>
                <div class="form-group"><label>Phone</label><input type="text" name="phone" value="<?= e($c['phone']??'') ?>"></div>
                <div class="form-group"><label>Email</label><input type="email" name="email" value="<?= e($c['email']??'') ?>"></div>
                <div class="form-group full"><label>Address</label><textarea name="address" style="min-height:56px"><?= e($c['address']??'') ?></textarea></div>
                <div class="form-group full"><label>Notes</label><textarea name="notes" style="min-height:48px"><?= e($c['notes']??'') ?></textarea></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-close-modal>Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
function togglePrivLabel(prefix) {
    const type  = document.getElementById(prefix + '_dtype').value;
    const label = document.getElementById(prefix + '_dlabel');
    label.textContent = type === 'percent' ? 'Discount value (%)' : 'Discount value (৳)';
}

function openPrivEdit(p) {
    document.getElementById('ep_name').value   = p.name;
    document.getElementById('ep_dtype').value  = p.discount_type;
    document.getElementById('ep_dvalue').value = p.discount_value;
    document.getElementById('ep_desc').value   = p.description || '';
    document.getElementById('editPrivForm').action =
        '/books/<?= $book['id'] ?>/privileges/' + p.id + '/edit';
    togglePrivLabel('ep');
    // Close manage modal first, then open edit
    document.querySelectorAll('.modal-backdrop.open').forEach(el => el.classList.remove('open'));
    document.getElementById('editPrivModal').classList.add('open');
}
</script>

<?php $content = ob_get_clean(); require BASE_PATH . '/views/partials/layout.php'; ?>
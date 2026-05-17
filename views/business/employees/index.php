<?php
$pageTitle = 'Employees — ' . e($book['name']);
$isOwner   = $book['user_id'] === auth()['id'];

// Friendly module labels
$moduleLabels = [
    'invoices'      => 'Invoices',
    'pos'           => 'POS',
    'products'      => 'Products',
    'funds'         => 'Funds',
    'expenses'      => 'Expenses',
    'dues'          => 'Dues',
    'debts'         => 'Debts',
    'customers'     => 'Customers',
    'suppliers'     => 'Suppliers',
    'employees'     => 'Employees',
    'contacts'      => 'Contacts',
    'coupons'       => 'Coupons',
    'returns'       => 'Returns',
    'deliveries'    => 'Deliveries',
    'reports'       => 'Reports',
    'privileges'    => 'Privileges',
    'book_settings' => 'Book Settings',
];
$actionLabels = ['view'=>'View','create'=>'Create','edit'=>'Edit','delete'=>'Delete','adjust_stock'=>'Adjust Stock','pay'=>'Pay','invite'=>'Invite'];

ob_start();
?>

<div class="page-header">
    <div class="page-header-left">
        <div class="breadcrumb">
            <a href="/books/<?= $book['id'] ?>">Dashboard</a> <span>›</span>
            <span>Employees</span>
        </div>
        <h1><i class="fa-solid fa-id-badge" style="color:var(--brand)"></i> Employees</h1>
        <p><?= count($employees) ?> employee<?= count($employees) !== 1 ? 's' : '' ?></p>
    </div>
    <?php if ($isOwner): ?>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
        <a href="/books/<?= $book['id'] ?>/notifications/send" class="btn btn-secondary">
            <i class="fa-solid fa-paper-plane"></i> Send Notification
        </a>
        <button class="btn btn-secondary" data-modal="manageDesignationsModal">
            <i class="fa-solid fa-sitemap"></i> Designations
        </button>
        <button class="btn btn-primary" data-modal="inviteModal">
            <i class="fa-solid fa-envelope"></i> Invite User
        </button>
        <button class="btn btn-secondary" data-modal="addEmployeeModal">
            <i class="fa-solid fa-plus"></i> Add Employee
        </button>
    </div>
    <?php endif; ?>
</div>

<!-- PENDING INVITATIONS BANNER -->
<?php if ($isOwner && !empty($pending_invitations)): ?>
<div class="card" style="border-left:4px solid var(--brand);margin-bottom:16px">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px">
        <p class="card-title" style="margin:0"><i class="fa-solid fa-clock" style="color:var(--accent)"></i> Pending Invitations (<?= count($pending_invitations) ?>)</p>
    </div>
    <div class="table-wrap" style="margin:0">
        <table>
            <thead><tr><th>Email</th><th>Designation</th><th>Sent</th><th>Expires</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($pending_invitations as $inv): ?>
            <tr>
                <td><?= e($inv['email']) ?></td>
                <td><?= $inv['designation_name'] ? '<span class="badge badge-blue">'.e($inv['designation_name']).'</span>' : '<span class="td-muted">—</span>' ?></td>
                <td class="td-muted"><?= format_date($inv['created_at']) ?></td>
                <td class="td-muted <?= strtotime($inv['expires_at']) < time() ? 'red' : '' ?>"><?= format_date($inv['expires_at']) ?></td>
                <td>
                    <form method="POST" action="/books/<?= $book['id'] ?>/employees/invitations/<?= $inv['id'] ?>/cancel"
                          data-confirm="Cancel this invitation to <?= e($inv['email']) ?>?">
                        <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
                        <button class="btn btn-sm btn-danger">Cancel</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- EMPLOYEES TABLE -->
<?php if (empty($employees)): ?>
<!-- LM Controls -->
<div class="lm-controls">
    <div class="lm-search-wrap">
        <i class="fa-solid fa-magnifying-glass"></i>
        <input type="text" class="lm-search" id="empTableSearch" placeholder="Search name, designation, department…">
        <button class="lm-search-clear" id="empTableClear"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <select class="lm-select" id="empTableSort">
        <option value="az">Name A–Z</option>
        <option value="za">Name Z–A</option>
        <option value="amt-desc">Salary High–Low</option>
        <option value="amt-asc">Salary Low–High</option>
    </select>
</div>
<div class="lm-filter-pills">
    <span style="font-size:12px;font-weight:600;color:var(--text-muted)">Status:</span>
    <button class="btn btn-sm btn-primary" data-lmf="all">All</button>
    <button class="btn btn-sm btn-secondary" data-lmf="active">Active</button>
    <button class="btn btn-sm btn-secondary" data-lmf="inactive">Inactive</button>
    <button class="btn btn-sm btn-secondary" data-lmf="on_leave">On Leave</button>
</div>

<div class="table-wrap">
    <div class="empty-state">
        <div class="empty-icon">👥</div>
        <h3>No employees yet</h3>
        <p>Add employees or invite Byabsayee users to collaborate on this book.</p>
        <?php if ($isOwner): ?>
        <div style="display:flex;gap:8px;justify-content:center;margin-top:12px">
            <button class="btn btn-primary" data-modal="inviteModal">
                <i class="fa-solid fa-envelope"></i> Invite User
            </button>
            <button class="btn btn-secondary" data-modal="addEmployeeModal">
                <i class="fa-solid fa-plus"></i> Add Employee
            </button>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php else: ?>
<div class="table-wrap">
    <table id="empTable">
        <thead>
            <tr>
                <th>Name</th>
                <th>Designation</th>
                <th>Department</th>
                <th>Contact</th>
                <th>Status</th>
                <th>App Access</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($employees as $emp): ?>
        <?php
            $sc = ['active'=>'green','inactive'=>'gray','terminated'=>'red'][$emp['status']] ?? 'gray';
            $hasLogin = !empty($emp['user_id']);
        ?>
        <tr>
            <td>
                <a href="/books/<?= $book['id'] ?>/employees/<?= $emp['id'] ?>" style="font-weight:500;color:var(--brand);text-decoration:none">
                    <?= e($emp['name']) ?>
                </a>
                <?php if ($hasLogin): ?>
                    <span class="badge badge-green" style="font-size:10px;margin-left:4px">Has Login</span>
                <?php endif; ?>
            </td>
            <td>
                <?php if ($emp['user_id'] == $book['user_id']): ?>
                    <span class="badge badge-owner"><i class="fa-solid fa-crown"></i> Owner</span>
                    <?php if ($emp['designation_name'] && strtolower($emp['designation_name']) !== 'owner'): ?>
                    <span class="badge badge-blue" style="margin-left:4px"><?= e($emp['designation_name']) ?></span>
                    <?php endif; ?>
                <?php elseif ($emp['designation_name']): ?>
                    <span class="badge badge-blue"><?= e($emp['designation_name']) ?></span>
                <?php else: ?>
                    <span class="td-muted">—</span>
                <?php endif; ?>
            </td>
            <td class="td-muted"><?= e($emp['department'] ?? '—') ?></td>
            <td class="td-muted">
                <?php if ($emp['phone']): ?><div><?= e($emp['phone']) ?></div><?php endif; ?>
                <?php if ($emp['email']): ?><div style="font-size:11px"><?= e($emp['email']) ?></div><?php endif; ?>
            </td>
            <td><span class="badge badge-<?= $sc ?>"><?= ucfirst($emp['status']) ?></span></td>
            <td>
                <?php if ($hasLogin): ?>
                    <span class="badge badge-green"><i class="fa-solid fa-check"></i> Active</span>
                <?php else: ?>
                    <span class="badge badge-gray">No account</span>
                <?php endif; ?>
            </td>
            <td>
                <a href="/books/<?= $book['id'] ?>/employees/<?= $emp['id'] ?>" class="btn btn-sm btn-secondary">View</a>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<div id="empTablePager"></div>
<?php endif; ?>

<!-- ========== MODALS ========== -->

<!-- INVITE MODAL -->
<?php if ($isOwner): ?>
<div class="modal-backdrop" id="inviteModal">
    <div class="modal" style="max-width:640px">
        <div class="modal-title"><i class="fa-solid fa-envelope" style="color:var(--brand)"></i> Invite User to Book</div>
        <form method="POST" action="/books/<?= $book['id'] ?>/employees/invite">
            <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
            <p style="font-size:13px;color:var(--text-muted);margin-bottom:16px">
                The user must already have a Byabsayee account. They will receive an in-app notification and an email to accept or decline.
            </p>
            <div class="form-grid" style="gap:12px;margin-bottom:16px">
                <div class="form-group full">
                    <label>User Email *</label>
                    <input type="email" name="email" required placeholder="user@email.com">
                </div>
                <div class="form-group">
                    <label>Designation (Optional)</label>
                    <select name="designation_id" id="inviteDesigSelect" onchange="loadDesigPerms(this.value,'invite')">
                        <option value="">— Select or set manually —</option>
                        <?php foreach ($designations as $d): ?>
                        <option value="<?= $d['id'] ?>"><?= e($d['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Designation Name <span style="color:var(--text-muted)">(custom)</span></label>
                    <input type="text" name="designation_name" id="inviteDesigName" placeholder="e.g. Cashier, Manager">
                </div>
            </div>

            <!-- PERMISSION MATRIX -->
            <div style="margin-bottom:12px">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
                    <p style="font-weight:600;font-size:13px;margin:0">Permissions</p>
                    <div style="display:flex;gap:8px">
                        <button type="button" onclick="toggleAllPerms('invite',true)"  class="btn btn-sm btn-secondary">All</button>
                        <button type="button" onclick="toggleAllPerms('invite',false)" class="btn btn-sm btn-secondary">None</button>
                    </div>
                </div>
                <div class="perm-grid" id="invite-perm-grid">
                <?php foreach ($modules as $mod => $actions): ?>
                <div class="perm-row">
                    <div class="perm-module"><?= $moduleLabels[$mod] ?? $mod ?></div>
                    <div class="perm-actions">
                    <?php foreach ($actions as $action): ?>
                        <label class="perm-check">
                            <input type="checkbox" name="perm[<?= $mod ?>][<?= $action ?>]"
                                   class="invite-perm" data-mod="<?= $mod ?>" data-action="<?= $action ?>">
                            <?= $actionLabels[$action] ?? $action ?>
                        </label>
                    <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-close-modal>Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-paper-plane"></i> Send Invitation</button>
            </div>
        </form>
    </div>
</div>

<!-- ADD EMPLOYEE (offline) MODAL -->
<div class="modal-backdrop" id="addEmployeeModal">
    <div class="modal">
        <div class="modal-title">Add Employee (Offline)</div>
        <form method="POST" action="/books/<?= $book['id'] ?>/employees/add">
            <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
            <div class="form-grid" style="gap:12px">
                <div class="form-group full"><label>Name *</label><input type="text" name="name" required></div>
                <div class="form-group">
                    <label>Designation</label>
                    <select name="designation_id">
                        <option value="">— None —</option>
                        <?php foreach ($designations as $d): ?>
                        <option value="<?= $d['id'] ?>"><?= e($d['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Custom Designation</label>
                    <input type="text" name="designation_name" placeholder="e.g. Cashier">
                </div>
                <div class="form-group"><label>Phone</label><input type="text" name="phone"></div>
                <div class="form-group"><label>Email</label><input type="email" name="email"></div>
                <div class="form-group full"><label>Address</label><textarea name="address" style="min-height:48px"></textarea></div>
                <div class="form-group"><label>Department</label><input type="text" name="department"></div>
                <div class="form-group"><label>Join Date</label><input type="date" name="join_date"></div>
                <div class="form-group"><label>Salary</label><input type="number" name="salary" step="0.01" min="0"></div>
                <div class="form-group">
                    <label>Salary Type</label>
                    <select name="salary_type">
                        <option value="monthly">Monthly</option>
                        <option value="daily">Daily</option>
                        <option value="hourly">Hourly</option>
                    </select>
                </div>
                <div class="form-group full"><label>Notes</label><textarea name="notes" style="min-height:48px"></textarea></div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-close-modal>Cancel</button>
                <button type="submit" class="btn btn-primary">Add Employee</button>
            </div>
        </form>
    </div>
</div>

<!-- MANAGE DESIGNATIONS MODAL -->
<div class="modal-backdrop" id="manageDesignationsModal">
    <div class="modal" style="max-width:700px">
        <div class="modal-title"><i class="fa-solid fa-sitemap" style="color:var(--brand)"></i> Designations</div>
        <p style="font-size:13px;color:var(--text-muted);margin-bottom:16px">
            Designations are saved permission templates. Assign them to employees for quick setup.
        </p>

        <?php if (!empty($designations)): ?>
        <div style="margin-bottom:20px">
            <p class="section-label">Existing Designations</p>
            <?php foreach ($designations as $d): ?>
            <?php $dp = json_decode($d['permissions'] ?? '{}', true) ?? []; ?>
            <div class="card" style="margin-bottom:8px;padding:12px">
                <div style="display:flex;justify-content:space-between;align-items:center">
                    <div>
                        <span style="font-weight:600"><?= e($d['name']) ?></span>
                        <span class="td-muted" style="margin-left:8px"><?= $d['employee_count'] ?> employee<?= $d['employee_count']!=1?'s':'' ?></span>
                    </div>
                    <div style="display:flex;gap:6px">
                        <button class="btn btn-sm btn-secondary" onclick="openEditDesig(<?= htmlspecialchars(json_encode($d), ENT_QUOTES) ?>)">Edit</button>
                        <form method="POST" action="/books/<?= $book['id'] ?>/employees/designations/<?= $d['id'] ?>/delete"
                              data-confirm="Delete &quot;<?= e($d['name']) ?>&quot; designation?">
                            <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
                            <button class="btn btn-sm btn-danger">Delete</button>
                        </form>
                    </div>
                </div>
                <div style="margin-top:8px;display:flex;flex-wrap:wrap;gap:4px">
                <?php foreach ($dp as $mod => $actions): ?>
                <?php $granted = array_filter($actions); if (!$granted) continue; ?>
                    <span class="badge badge-green" style="font-size:10px">
                        <?= $moduleLabels[$mod] ?? $mod ?>:
                        <?= implode(', ', array_keys($granted)) ?>
                    </span>
                <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div>
            <p class="section-label">Create New Designation</p>
            <form method="POST" action="/books/<?= $book['id'] ?>/employees/designations/add">
                <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
                <div class="form-group" style="margin-bottom:12px">
                    <label>Designation Name *</label>
                    <input type="text" name="name" placeholder="e.g. Cashier, Manager, Accountant" required>
                </div>
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
                    <p style="font-weight:600;font-size:13px;margin:0">Permissions</p>
                    <div style="display:flex;gap:8px">
                        <button type="button" onclick="toggleAllPerms('desig',true)"  class="btn btn-sm btn-secondary">All</button>
                        <button type="button" onclick="toggleAllPerms('desig',false)" class="btn btn-sm btn-secondary">None</button>
                    </div>
                </div>
                <div class="perm-grid" id="desig-perm-grid">
                <?php foreach ($modules as $mod => $actions): ?>
                <div class="perm-row">
                    <div class="perm-module"><?= $moduleLabels[$mod] ?? $mod ?></div>
                    <div class="perm-actions">
                    <?php foreach ($actions as $action): ?>
                        <label class="perm-check">
                            <input type="checkbox" name="perm[<?= $mod ?>][<?= $action ?>]"
                                   class="desig-perm" data-mod="<?= $mod ?>" data-action="<?= $action ?>">
                            <?= $actionLabels[$action] ?? $action ?>
                        </label>
                    <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-close-modal>Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Designation</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- EDIT DESIGNATION MODAL -->
<div class="modal-backdrop" id="editDesigModal">
    <div class="modal" style="max-width:640px">
        <div class="modal-title">Edit Designation</div>
        <form method="POST" action="" id="editDesigForm">
            <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
            <div class="form-group" style="margin-bottom:12px">
                <label>Designation Name *</label>
                <input type="text" name="name" id="editDesigName" required>
            </div>
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
                <p style="font-weight:600;font-size:13px;margin:0">Permissions</p>
                <div style="display:flex;gap:8px">
                    <button type="button" onclick="toggleAllPerms('edit-desig',true)"  class="btn btn-sm btn-secondary">All</button>
                    <button type="button" onclick="toggleAllPerms('edit-desig',false)" class="btn btn-sm btn-secondary">None</button>
                </div>
            </div>
            <div class="perm-grid" id="edit-desig-perm-grid">
            <?php foreach ($modules as $mod => $actions): ?>
            <div class="perm-row">
                <div class="perm-module"><?= $moduleLabels[$mod] ?? $mod ?></div>
                <div class="perm-actions">
                <?php foreach ($actions as $action): ?>
                    <label class="perm-check">
                        <input type="checkbox" name="perm[<?= $mod ?>][<?= $action ?>]"
                               class="edit-desig-perm" data-mod="<?= $mod ?>" data-action="<?= $action ?>">
                        <?= $actionLabels[$action] ?? $action ?>
                    </label>
                <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-close-modal>Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<script>
// Toggle all permissions in a grid
function toggleAllPerms(prefix, val) {
    document.querySelectorAll('.' + prefix + '-perm').forEach(cb => cb.checked = val);
}

// Load designation permissions into a form
function loadDesigPerms(desigId, prefix) {
    if (!desigId) return;
    fetch('/books/<?= $book['id'] ?>/employees/designations/' + desigId + '/permissions')
        .then(r => r.json())
        .then(perms => {
            document.querySelectorAll('.' + prefix + '-perm').forEach(cb => {
                const mod    = cb.dataset.mod;
                const action = cb.dataset.action;
                cb.checked = !!(perms[mod] && perms[mod][action]);
            });
        }).catch(() => {});
}

// Open edit designation modal
function openEditDesig(desig) {
    document.getElementById('editDesigName').value = desig.name;
    document.getElementById('editDesigForm').action = '/books/<?= $book['id'] ?>/employees/designations/' + desig.id + '/edit';

    const perms = typeof desig.permissions === 'string'
        ? JSON.parse(desig.permissions)
        : (desig.permissions || {});

    document.querySelectorAll('.edit-desig-perm').forEach(cb => {
        const mod    = cb.dataset.mod;
        const action = cb.dataset.action;
        cb.checked = !!(perms[mod] && perms[mod][action]);
    });

    document.getElementById('editDesigModal').classList.add('open');
}

// When a designation is selected in the invite form, auto-fill the name
document.getElementById('inviteDesigSelect')?.addEventListener('change', function() {
    const opt = this.options[this.selectedIndex];
    if (opt.value) {
        document.getElementById('inviteDesigName').value = opt.text;
        loadDesigPerms(opt.value, 'invite');
    }
});
</script>

<?php
$content = ob_get_clean();
require BASE_PATH . '/views/partials/layout.php';
?>

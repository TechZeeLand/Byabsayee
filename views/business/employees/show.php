<?php
$pageTitle = e($employee['name']) . ' — Employees — ' . e($book['name']);
$isOwner   = $book['user_id'] === auth()['id'];

$moduleLabels = [
    'invoices'      => ['label'=>'Invoices',      'icon'=>'fa-file-invoice'],
    'pos'           => ['label'=>'POS',            'icon'=>'fa-cash-register'],
    'products'      => ['label'=>'Products',       'icon'=>'fa-box'],
    'funds'         => ['label'=>'Funds',          'icon'=>'fa-piggy-bank'],
    'expenses'      => ['label'=>'Expenses',       'icon'=>'fa-receipt'],
    'dues'          => ['label'=>'Dues',           'icon'=>'fa-hand-holding-dollar'],
    'debts'         => ['label'=>'Debts',          'icon'=>'fa-file-circle-minus'],
    'customers'     => ['label'=>'Customers',      'icon'=>'fa-users'],
    'suppliers'     => ['label'=>'Suppliers',      'icon'=>'fa-user-tie'],
    'employees'     => ['label'=>'Employees',      'icon'=>'fa-id-badge'],
    'contacts'      => ['label'=>'Contacts',       'icon'=>'fa-address-book'],
    'coupons'       => ['label'=>'Coupons',        'icon'=>'fa-ticket'],
    'returns'       => ['label'=>'Returns',        'icon'=>'fa-rotate-left'],
    'deliveries'    => ['label'=>'Deliveries',     'icon'=>'fa-truck-fast'],
    'reports'       => ['label'=>'Reports',        'icon'=>'fa-chart-line'],
    'privileges'    => ['label'=>'Privileges',     'icon'=>'fa-star'],
    'book_settings' => ['label'=>'Book Settings',  'icon'=>'fa-gear'],
];
$actionLabels = [
    'view'         => 'View',
    'create'       => 'Create',
    'edit'         => 'Edit',
    'delete'       => 'Delete',
    'adjust_stock' => 'Adjust Stock',
    'pay'          => 'Pay',
    'invite'       => 'Invite',
];

// Get current permissions from book_member or empty
$currentPerms = [];
if ($member) {
    $currentPerms = json_decode($member['permissions'] ?? '{}', true) ?? [];
}

ob_start();
?>

<div class="page-header">
    <div class="page-header-left">
        <div class="breadcrumb">
            <a href="/books/<?= $book['id'] ?>">Dashboard</a> <span>›</span>
            <a href="/books/<?= $book['id'] ?>/employees">Employees</a> <span>›</span>
            <span><?= e($employee['name']) ?></span>
        </div>
        <h1><?= e($employee['name']) ?></h1>
        <div style="display:flex;gap:6px;flex-wrap:wrap;margin-top:4px">
            <?php if ($employee['designation_name']): ?>
                <span class="badge badge-blue"><?= e($employee['designation_name']) ?></span>
            <?php endif; ?>
            <?php
            $sc = ['active'=>'green','inactive'=>'gray','terminated'=>'red'][$employee['status']] ?? 'gray';
            ?>
            <span class="badge badge-<?= $sc ?>"><?= ucfirst($employee['status']) ?></span>
            <?php if ($employee['user_id']): ?>
                <span class="badge badge-green"><i class="fa-solid fa-link"></i> Has Byabsayee Account</span>
            <?php else: ?>
                <span class="badge badge-gray">No account</span>
            <?php endif; ?>
        </div>
    </div>
    <?php if ($isOwner): ?>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
        <button class="btn btn-secondary" data-modal="editEmployeeModal">Edit</button>
        <?php if ($employee['user_id'] && $member): ?>
            <?php if ($member['status'] === 'active'): ?>
            <form method="POST" action="/books/<?= $book['id'] ?>/employees/<?= $employee['id'] ?>/revoke"
                  data-confirm="Revoke app access for <?= e($employee['name']) ?>? They will no longer be able to access this book.">
                <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
                <button class="btn btn-secondary" style="color:var(--red)">Revoke Access</button>
            </form>
            <?php else: ?>
            <form method="POST" action="/books/<?= $book['id'] ?>/employees/<?= $employee['id'] ?>/restore">
                <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
                <button class="btn btn-secondary" style="color:var(--green)">Restore Access</button>
            </form>
            <?php endif; ?>
        <?php endif; ?>
        <form method="POST" action="/books/<?= $book['id'] ?>/employees/<?= $employee['id'] ?>/delete"
              data-confirm="Remove <?= e($employee['name']) ?> from employees?">
            <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
            <button class="btn btn-danger">Delete</button>
        </form>
    </div>
    <?php endif; ?>
</div>

<div style="display:grid;grid-template-columns:280px 1fr;gap:16px;align-items:start">

    <!-- LEFT: profile info -->
    <div style="display:flex;flex-direction:column;gap:12px">

        <div class="card">
            <p class="card-title">Contact Details</p>
            <div style="font-size:13px;display:flex;flex-direction:column;gap:7px">
                <?php if ($employee['phone']): ?>
                    <div><span style="color:var(--text-muted)">Phone:</span> <?= e($employee['phone']) ?></div>
                <?php endif; ?>
                <?php if ($employee['email']): ?>
                    <div><span style="color:var(--text-muted)">Email:</span> <?= e($employee['email']) ?></div>
                <?php endif; ?>
                <?php if ($employee['address']): ?>
                    <div><span style="color:var(--text-muted)">Address:</span> <?= e($employee['address']) ?></div>
                <?php endif; ?>
                <?php if ($employee['department']): ?>
                    <div><span style="color:var(--text-muted)">Department:</span> <?= e($employee['department']) ?></div>
                <?php endif; ?>
                <?php if ($employee['join_date']): ?>
                    <div><span style="color:var(--text-muted)">Joined:</span> <?= format_date($employee['join_date']) ?></div>
                <?php endif; ?>
                <?php if ($employee['salary']): ?>
                    <div><span style="color:var(--text-muted)">Salary:</span>
                        <?= format_money($employee['salary']) ?> / <?= $employee['salary_type'] ?>
                    </div>
                <?php endif; ?>
                <?php if ($employee['notes']): ?>
                    <div><span style="color:var(--text-muted)">Notes:</span> <?= e($employee['notes']) ?></div>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($employee['user_id']): ?>
        <div class="card">
            <p class="card-title">App Access</p>
            <?php if ($member): ?>
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px">
                    <div class="s-avatar" style="width:32px;height:32px;font-size:12px;flex-shrink:0">
                        <?= mb_strtoupper(mb_substr($employee['name'], 0, 1)) ?>
                    </div>
                    <div>
                        <div style="font-size:13px;font-weight:600"><?= e($employee['name']) ?></div>
                        <div style="font-size:11px;color:var(--text-muted)"><?= e($employee['email'] ?? '') ?></div>
                    </div>
                </div>
                <div style="font-size:12px">
                    Status: <span class="badge badge-<?= $member['status']==='active'?'green':'gray' ?>">
                        <?= ucfirst($member['status']) ?>
                    </span>
                </div>
                <?php if ($member['designation_name']): ?>
                <div style="font-size:12px;margin-top:4px">
                    Designation: <span class="badge badge-blue"><?= e($member['designation_name']) ?></span>
                </div>
                <?php endif; ?>
            <?php else: ?>
                <p style="font-size:13px;color:var(--text-muted)">Linked to account but not yet a book member.</p>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <div class="card">
            <p class="card-title">App Access</p>
            <p style="font-size:13px;color:var(--text-muted);margin-bottom:10px">
                This employee does not have a Byabsayee account linked.
            </p>
            <?php if ($isOwner): ?>
            <button class="btn btn-sm btn-secondary" onclick="
                document.getElementById('inviteEmailField').value='<?= e($employee['email']??'') ?>';
                document.getElementById('inviteModal').classList.add('open');
            ">
                <i class="fa-solid fa-envelope"></i> Send Invitation
            </button>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- RIGHT: permissions -->
    <div>
        <?php if ($isOwner && $employee['user_id'] && $member && $member['status'] === 'active'): ?>
        <form method="POST" action="/books/<?= $book['id'] ?>/employees/<?= $employee['id'] ?>/permissions">
            <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
                <p class="section-label" style="margin:0">Permissions</p>
                <div style="display:flex;gap:8px;align-items:center">
                    <?php if (!empty($designations)): ?>
                    <select onchange="applyDesigPerms(this.value)" style="font-size:12px;padding:4px 8px;border:1.5px solid var(--border);border-radius:6px">
                        <option value="">Apply Designation…</option>
                        <?php foreach ($designations as $d): ?>
                        <option value="<?= $d['id'] ?>"><?= e($d['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php endif; ?>
                    <button type="button" onclick="toggleAll(true)"  class="btn btn-sm btn-secondary">All</button>
                    <button type="button" onclick="toggleAll(false)" class="btn btn-sm btn-secondary">None</button>
                    <button type="submit" class="btn btn-sm btn-primary">Save Permissions</button>
                </div>
            </div>

            <div class="perm-grid">
            <?php foreach ($modules as $mod => $actions): ?>
            <?php $ml = $moduleLabels[$mod] ?? ['label'=>$mod,'icon'=>'fa-circle']; ?>
            <div class="perm-row">
                <div class="perm-module">
                    <i class="fa-solid <?= $ml['icon'] ?>"></i> <?= $ml['label'] ?>
                </div>
                <div class="perm-actions">
                <?php foreach ($actions as $action): ?>
                <?php $checked = !empty($currentPerms[$mod][$action]); ?>
                    <label class="perm-check <?= $checked ? 'checked' : '' ?>"
                           id="lbl-<?= $mod ?>-<?= $action ?>">
                        <input type="checkbox"
                               name="perm[<?= $mod ?>][<?= $action ?>]"
                               class="emp-perm"
                               data-mod="<?= $mod ?>" data-action="<?= $action ?>"
                               <?= $checked ? 'checked' : '' ?>
                               onchange="this.closest('label').classList.toggle('checked', this.checked)">
                        <?= $actionLabels[$action] ?? $action ?>
                    </label>
                <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
            </div>
        </form>
        <?php elseif (!$employee['user_id']): ?>
        <div class="card">
            <div class="empty-state" style="padding:40px">
                <div class="empty-icon"><i class="fa-solid fa-lock" style="font-size:32px;color:var(--text-muted)"></i></div>
                <h3>No App Permissions</h3>
                <p>This employee doesn't have a Byabsayee account. Invite them to set up permissions.</p>
            </div>
        </div>
        <?php elseif ($member && $member['status'] !== 'active'): ?>
        <div class="card">
            <div class="empty-state" style="padding:40px">
                <div class="empty-icon"><i class="fa-solid fa-ban" style="font-size:32px;color:var(--red)"></i></div>
                <h3>Access Revoked</h3>
                <p>This employee's access has been revoked. Restore access to re-enable permissions.</p>
            </div>
        </div>
        <?php else: ?>
        <div class="card">
            <div class="empty-state" style="padding:40px">
                <div class="empty-icon"><i class="fa-solid fa-clock" style="font-size:32px;color:var(--accent)"></i></div>
                <h3>Invitation Pending</h3>
                <p>Waiting for the user to accept the invitation before permissions can be set.</p>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- EDIT EMPLOYEE MODAL -->
<?php if ($isOwner): ?>
<div class="modal-backdrop" id="editEmployeeModal">
    <div class="modal">
        <div class="modal-title">Edit Employee</div>
        <form method="POST" action="/books/<?= $book['id'] ?>/employees/<?= $employee['id'] ?>/edit">
            <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
            <div class="form-grid" style="gap:12px">
                <div class="form-group full"><label>Name *</label>
                    <input type="text" name="name" value="<?= e($employee['name']) ?>" required></div>
                <div class="form-group">
                    <label>Designation</label>
                    <select name="designation_id">
                        <option value="">— None —</option>
                        <?php foreach ($designations as $d): ?>
                        <option value="<?= $d['id'] ?>" <?= $employee['designation_id']==$d['id']?'selected':'' ?>>
                            <?= e($d['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Custom Designation</label>
                    <input type="text" name="designation_name" value="<?= e($employee['designation_name']??'') ?>">
                </div>
                <div class="form-group"><label>Phone</label>
                    <input type="text" name="phone" value="<?= e($employee['phone']??'') ?>"></div>
                <div class="form-group"><label>Email</label>
                    <input type="email" name="email" value="<?= e($employee['email']??'') ?>"></div>
                <div class="form-group full"><label>Address</label>
                    <textarea name="address" style="min-height:48px"><?= e($employee['address']??'') ?></textarea></div>
                <div class="form-group"><label>Department</label>
                    <input type="text" name="department" value="<?= e($employee['department']??'') ?>"></div>
                <div class="form-group"><label>Join Date</label>
                    <input type="date" name="join_date" value="<?= e($employee['join_date']??'') ?>"></div>
                <div class="form-group"><label>Salary</label>
                    <input type="number" name="salary" step="0.01" min="0" value="<?= e($employee['salary']??'') ?>"></div>
                <div class="form-group">
                    <label>Salary Type</label>
                    <select name="salary_type">
                        <?php foreach (['monthly','daily','hourly'] as $t): ?>
                        <option value="<?= $t ?>" <?= $employee['salary_type']===$t?'selected':'' ?>><?= ucfirst($t) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group full"><label>Notes</label>
                    <textarea name="notes" style="min-height:48px"><?= e($employee['notes']??'') ?></textarea></div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status">
                        <?php foreach (['active','inactive','terminated'] as $s): ?>
                        <option value="<?= $s ?>" <?= $employee['status']===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
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
function toggleAll(val) {
    document.querySelectorAll('.emp-perm').forEach(cb => {
        cb.checked = val;
        cb.closest('label').classList.toggle('checked', val);
    });
}

function applyDesigPerms(desigId) {
    if (!desigId) return;
    fetch('/books/<?= $book['id'] ?>/employees/designations/' + desigId + '/permissions')
        .then(r => r.json())
        .then(perms => {
            document.querySelectorAll('.emp-perm').forEach(cb => {
                const mod    = cb.dataset.mod;
                const action = cb.dataset.action;
                const val    = !!(perms[mod] && perms[mod][action]);
                cb.checked   = val;
                cb.closest('label').classList.toggle('checked', val);
            });
        }).catch(() => {});
}
</script>

<?php
$content = ob_get_clean();
require BASE_PATH . '/views/partials/layout.php';
?>

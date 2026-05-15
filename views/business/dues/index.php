<?php
$pageTitle = 'Dues — ' . e($book['name']);
ob_start();
?>

<div class="page-header">
    <div class="page-header-left">
        <div class="breadcrumb">
            <a href="/books/<?= $book['id'] ?>">Dashboard</a> <span>›</span>
            <span>Dues</span>
        </div>
        <h1><i class="fa-solid fa-hand-holding-dollar" style="color:var(--brand)"></i> Dues</h1>
        <p>Money customers owe your business</p>
    </div>
    <button class="btn btn-primary" data-modal="addDueModal">
        <i class="fa-solid fa-plus"></i> Add Due
    </button>
</div>

<!-- Summary -->
<div class="stat-grid" style="grid-template-columns:repeat(auto-fit,minmax(140px,1fr));max-width:720px;margin-bottom:22px">
    <div class="stat-card" style="border-left:4px solid var(--red)">
        <div class="stat-label"><i class="fa-solid fa-circle-exclamation" style="color:var(--red)"></i> Outstanding</div>
        <div class="stat-value red"><?= format_money((float)($summary['outstanding'] ?? 0), $symbol) ?></div>
        <div class="stat-sub"><?= (int)($summary['unpaid_count'] ?? 0) + (int)($summary['partial_count'] ?? 0) ?> unpaid</div>
    </div>
    <div class="stat-card" style="border-left:4px solid var(--green)">
        <div class="stat-label"><i class="fa-solid fa-money-bill-wave" style="color:var(--green)"></i> Collected</div>
        <div class="stat-value green"><?= format_money((float)($summary['total_collected'] ?? 0), $symbol) ?></div>
        <div class="stat-sub"><?= (int)($summary['paid_count'] ?? 0) ?> fully paid</div>
    </div>
    <div class="stat-card" style="border-left:4px solid var(--amber)">
        <div class="stat-label"><i class="fa-solid fa-hourglass-half" style="color:var(--amber)"></i> Partial</div>
        <div class="stat-value amber"><?= (int)($summary['partial_count'] ?? 0) ?></div>
        <div class="stat-sub">partially paid</div>
    </div>
    <div class="stat-card">
        <div class="stat-label"><i class="fa-solid fa-list"></i> Total Dues</div>
        <div class="stat-value brand"><?= (int)($summary['total_count'] ?? 0) ?></div>
        <div class="stat-sub">all records</div>
    </div>
</div>

<!-- Filter tabs -->
<div style="display:flex;gap:6px;margin-bottom:16px;flex-wrap:wrap;align-items:center">
    <?php foreach (['all'=>'All','unpaid'=>'Unpaid','partial'=>'Partial','paid'=>'Paid','cancelled'=>'Cancelled'] as $val=>$label): ?>
    <a href="?filter=<?= $val ?><?= $search ? '&q='.urlencode($search) : '' ?>"
       class="btn btn-sm <?= $filter===$val ? 'btn-primary' : 'btn-secondary' ?>">
        <?= $label ?>
        <?php if ($val==='unpaid' && ($summary['unpaid_count']??0)>0): ?>
        <span class="badge badge-red" style="font-size:10px;padding:1px 5px;margin-left:4px"><?= (int)$summary['unpaid_count'] ?></span>
        <?php endif; ?>
    </a>
    <?php endforeach; ?>

    <form method="GET" style="display:flex;gap:6px;margin-left:auto">
        <input type="hidden" name="filter" value="<?= e($filter) ?>">
        <input type="text" name="q" value="<?= e($search) ?>"
               placeholder="Search customer or title…"
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

<!-- Dues table -->
<?php if (empty($dues)): ?>
<div class="table-wrap">
    <div class="empty-state">
        <div class="empty-icon"><i class="fa-solid fa-hand-holding-dollar"></i></div>
        <h3>No dues found</h3>
        <p><?= $search ? 'Try a different search.' : 'Dues are created automatically when a sale invoice is marked unpaid, or add one manually.' ?></p>
    </div>
</div>
<?php else: ?>
<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>Customer</th>
                <th>Title / Invoice</th>
                <th>Amount</th>
                <th>Paid</th>
                <th>Remaining</th>
                <th>Due Date</th>
                <th>Status</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($dues as $due):
            $remaining = (float)$due['amount'] - (float)$due['paid_amount'];
            $sym       = $due['currency_symbol'] ?? $symbol;
            $pct       = $due['amount'] > 0 ? min(100, round($due['paid_amount'] / $due['amount'] * 100)) : 0;
            $statusMap = [
                'unpaid'    => ['badge-red',   'Unpaid'],
                'partial'   => ['badge-amber', 'Partial'],
                'paid'      => ['badge-green', 'Paid'],
                'cancelled' => ['badge-gray',  'Cancelled'],
            ];
            [$badgeClass, $badgeLabel] = $statusMap[$due['status']] ?? ['badge-gray', ucfirst($due['status'])];
        ?>
        <tr>
            <td>
                <div style="display:flex;align-items:center;gap:8px">
                    <?php if (!empty($due['customer_photo'])): ?>
                    <img src="<?= asset('uploads/'.$due['customer_photo']) ?>"
                         style="width:28px;height:28px;border-radius:50%;object-fit:cover;flex-shrink:0" alt="">
                    <?php else: ?>
                    <div style="width:28px;height:28px;border-radius:50%;background:var(--brand-light);color:var(--brand);display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;flex-shrink:0">
                        <?= mb_strtoupper(mb_substr($due['customer_name']??'C',0,1)) ?>
                    </div>
                    <?php endif; ?>
                    <div>
                        <div style="font-weight:600;font-size:13px"><?= e($due['customer_name'] ?? '—') ?></div>
                        <?php if (!empty($due['customer_phone'])): ?>
                        <div class="td-muted"><?= e($due['customer_phone']) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </td>
            <td>
                <div style="font-weight:500"><?= e($due['title']) ?></div>
                <?php if (!empty($due['invoice_no']) && !empty($due['invoice_id'])): ?>
                <div class="td-muted">
                    <a href="/books/<?= $book['id'] ?>/invoices/<?= (int)$due['invoice_id'] ?>"
                       style="color:var(--brand);text-decoration:none;font-size:12px"
                       title="View Invoice">
                        <i class="fa-solid fa-file-invoice fa-xs"></i> <?= e($due['invoice_no']) ?>
                    </a>
                </div>
                <?php elseif (!empty($due['invoice_no'])): ?>
                <div class="td-muted"><i class="fa-solid fa-file-invoice fa-xs"></i> <?= e($due['invoice_no']) ?></div>
                <?php endif; ?>
                <?php if (!empty($due['note'])): ?>
                <div class="td-muted" style="font-size:11px;font-style:italic"><?= e($due['note']) ?></div>
                <?php endif; ?>
            </td>
            <td style="font-weight:600"><?= format_money((float)$due['amount'], $sym) ?></td>
            <td>
                <div style="color:var(--green);font-weight:600"><?= format_money((float)$due['paid_amount'], $sym) ?></div>
                <div style="height:3px;background:var(--border);border-radius:99px;width:70px;margin-top:3px">
                    <div style="height:100%;border-radius:99px;width:<?= $pct ?>%;background:<?= $due['status']==='paid'?'var(--green)':'var(--amber)' ?>"></div>
                </div>
            </td>
            <td>
                <?php if ($remaining > 0.001): ?>
                <span style="color:var(--red);font-weight:700"><?= format_money($remaining, $sym) ?></span>
                <?php else: ?>
                <span style="color:var(--green)"><i class="fa-solid fa-check"></i> Settled</span>
                <?php endif; ?>
            </td>
            <td class="td-muted">
                <?php if (!empty($due['due_date'])):
                    $dueDate = new DateTime($due['due_date']);
                    $today   = new DateTime('today');
                    $overdue = $dueDate < $today && !in_array($due['status'], ['paid','cancelled']);
                ?>
                <span <?= $overdue ? 'style="color:var(--red);font-weight:600"' : '' ?>>
                    <?= format_date($due['due_date']) ?>
                    <?php if ($overdue): ?>
                    <br><small><?= (int)$today->diff($dueDate)->days ?> days overdue</small>
                    <?php endif; ?>
                </span>
                <?php else: ?>—<?php endif; ?>
            </td>
            <td><span class="badge <?= $badgeClass ?>"><?= $badgeLabel ?></span></td>
            <td style="white-space:nowrap;text-align:right">
                <?php if (!in_array($due['status'], ['paid','cancelled'])): ?>
                <button class="btn btn-sm btn-secondary"
                        onclick="openPayModal(<?= $due['id'] ?>,'<?= e(addslashes($due['title'])) ?>',<?= $remaining ?>,'<?= e($sym) ?>')"
                        title="Record payment">
                    <i class="fa-solid fa-money-bill-wave" style="color:var(--green)"></i>
                </button>
                <button class="btn btn-sm btn-secondary" title="Edit"
                        onclick="openDueEdit(<?= $due['id'] ?>,'<?= e(addslashes($due['title'])) ?>',<?= (float)$due['amount'] ?>,'<?= $due['due_date'] ?? '' ?>','<?= e(addslashes($due['note'] ?? '')) ?>')">
                    <i class="fa-solid fa-pen"></i>
                </button>
                <form method="POST" action="/books/<?= $book['id'] ?>/dues/<?= $due['id'] ?>/cancel"
                      style="display:inline" onsubmit="return confirm('Cancel this due?')">
                    <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
                    <button class="btn btn-sm btn-secondary" title="Cancel due">
                        <i class="fa-solid fa-ban" style="color:var(--amber)"></i>
                    </button>
                </form>
                <?php endif; ?>
                <form method="POST" action="/books/<?= $book['id'] ?>/dues/<?= $due['id'] ?>/delete"
                      style="display:inline" onsubmit="return confirm('Permanently delete this due record?')">
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


<!-- ══ RECORD PAYMENT MODAL ══ -->
<div class="modal-backdrop" id="payModal">
    <div class="modal">
        <div class="modal-title"><i class="fa-solid fa-money-bill-wave" style="color:var(--green)"></i> Record Payment</div>
        <form id="payModalForm" method="POST">
            <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
            <div class="form-grid" style="gap:12px">
                <div class="form-group full">
                    <label id="payModalLabel" style="font-weight:700;color:var(--brand)">Due title</label>
                </div>
                <div class="form-group">
                    <label>Amount *</label>
                    <input type="number" name="amount" id="payAmount" min="0.01" step="0.01" required placeholder="0.00">
                    <small class="form-hint" id="payRemaining"></small>
                </div>
                <div class="form-group">
                    <label>Payment Method</label>
                    <select name="payment_method">
                        <option value="cash">Cash</option>
                        <option value="bkash">bKash</option>
                        <option value="nagad">Nagad</option>
                        <option value="rocket">Rocket</option>
                        <option value="bank_transfer">Bank Transfer</option>
                        <option value="card">Card</option>
                        <option value="cheque">Cheque</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="form-group full">
                    <label>Note (optional)</label>
                    <textarea name="note" style="min-height:50px" placeholder="Any note…"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-close-modal>Cancel</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-check"></i> Save Payment
                </button>
            </div>
        </form>
    </div>
</div>


<!-- ══ EDIT DUE MODAL ══ -->
<div class="modal-backdrop" id="editDueModal">
    <div class="modal">
        <div class="modal-title"><i class="fa-solid fa-pen" style="color:var(--brand)"></i> Edit Due</div>
        <form id="editDueForm" method="POST">
            <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
            <div class="form-grid" style="gap:12px">
                <div class="form-group full">
                    <label>Title *</label>
                    <input type="text" name="title" id="editDueTitle" required>
                </div>
                <div class="form-group">
                    <label>Amount *</label>
                    <input type="number" name="amount" id="editDueAmount" min="0.01" step="0.01" required>
                </div>
                <div class="form-group">
                    <label>Due Date</label>
                    <input type="date" name="due_date" id="editDueDueDate">
                </div>
                <div class="form-group full">
                    <label>Note (optional)</label>
                    <textarea name="note" id="editDueNote" style="min-height:50px"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-close-modal>Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check"></i> Save Changes</button>
            </div>
        </form>
    </div>
</div>


<!-- ══ ADD DUE MODAL ══ -->
<div class="modal-backdrop" id="addDueModal">
    <div class="modal">
        <div class="modal-title"><i class="fa-solid fa-hand-holding-dollar" style="color:var(--amber)"></i> Add Manual Due</div>
        <form method="POST" action="/books/<?= $book['id'] ?>/dues/add">
            <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
            <div class="form-grid" style="gap:12px">
                <div class="form-group full" style="position:relative">
                    <label>Customer *</label>
                    <input type="text" id="dueCustomerSearch" autocomplete="off"
                           placeholder="Search by name or phone…"
                           oninput="searchDueCustomer(this.value)">
                    <input type="hidden" name="customer_id" id="dueCustomerId" required>
                    <div id="dueCustomerDropdown" class="autocomplete-dropdown" style="display:none"></div>
                </div>
                <div class="form-group full">
                    <label>Title *</label>
                    <input type="text" name="title" required placeholder="e.g. Unpaid balance from last order">
                </div>
                <div class="form-group">
                    <label>Amount *</label>
                    <input type="number" name="amount" min="0.01" step="0.01" required placeholder="0.00">
                </div>
                <div class="form-group">
                    <label>Due Date</label>
                    <input type="date" name="due_date">
                </div>
                <div class="form-group full">
                    <label>Note (optional)</label>
                    <textarea name="note" style="min-height:50px" placeholder="Any details…"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-close-modal>Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Add Due</button>
            </div>
        </form>
    </div>
</div>

<style>
.autocomplete-dropdown{position:absolute;top:100%;left:0;right:0;background:#fff;border:1px solid var(--border);border-radius:var(--radius);box-shadow:0 6px 20px rgba(0,0,0,.1);max-height:180px;overflow-y:auto;z-index:500}
.autocomplete-item{padding:8px 12px;cursor:pointer;font-size:13px}
.autocomplete-item:hover{background:var(--bg)}
.autocomplete-item small{color:var(--text-muted);display:block;font-size:11px}
.stat-sub{font-size:11px;color:var(--text-muted);margin-top:2px}
.amber{color:var(--amber)!important}
.badge-amber{background:var(--amber-bg,#fff8e1);color:var(--amber,#f59e0b)}
.badge-gray{background:#f3f4f6;color:#6b7280}
</style>

<script>
// ── Pay modal ──────────────────────────────────────────────────────────────
function openPayModal(dueId, title, remaining, sym) {
    document.getElementById('payModalForm').action = '/books/<?= $book['id'] ?>/dues/' + dueId + '/pay';
    document.getElementById('payModalLabel').textContent = title;
    document.getElementById('payAmount').value = remaining.toFixed(2);
    document.getElementById('payAmount').max   = remaining;
    document.getElementById('payRemaining').textContent = 'Remaining: ' + sym + remaining.toFixed(2);
    document.getElementById('payModal').classList.add('open');
}

// ── Edit due modal ─────────────────────────────────────────────────────────
function openDueEdit(id, title, amount, dueDate, note) {
    document.getElementById('editDueForm').action = '/books/<?= $book['id'] ?>/dues/' + id + '/edit';
    document.getElementById('editDueTitle').value   = title;
    document.getElementById('editDueAmount').value  = amount;
    document.getElementById('editDueDueDate').value = dueDate;
    document.getElementById('editDueNote').value    = note;
    document.getElementById('editDueModal').classList.add('open');
}

// ── Customer autocomplete ──────────────────────────────────────────────────
<?php
$customersJson = json_encode(array_map(fn($c) => [
    'id'    => $c['id'],
    'name'  => $c['name'],
    'phone' => $c['phone'] ?? '',
], $customers), JSON_UNESCAPED_UNICODE);
?>
const DUE_CUSTOMERS = <?= $customersJson ?>;

function searchDueCustomer(q) {
    const dd = document.getElementById('dueCustomerDropdown');
    if (!q.trim()) { dd.style.display='none'; return; }
    const matches = DUE_CUSTOMERS.filter(c =>
        c.name.toLowerCase().includes(q.toLowerCase()) ||
        (c.phone && c.phone.includes(q))
    ).slice(0, 8);
    if (!matches.length) { dd.style.display='none'; return; }
    dd.innerHTML = matches.map(c =>
        `<div class="autocomplete-item" onclick="selectDueCustomer(${c.id},'${c.name.replace(/'/g,"\\'")}')">
            ${c.name}<small>${c.phone || ''}</small>
        </div>`
    ).join('');
    dd.style.display='block';
}

function selectDueCustomer(id, name) {
    document.getElementById('dueCustomerSearch').value = name;
    document.getElementById('dueCustomerId').value     = id;
    document.getElementById('dueCustomerDropdown').style.display = 'none';
}

document.addEventListener('click', function(e) {
    if (!e.target.closest('#dueCustomerSearch') && !e.target.closest('#dueCustomerDropdown')) {
        document.getElementById('dueCustomerDropdown').style.display = 'none';
    }
});
</script>

<?php $content = ob_get_clean(); require BASE_PATH . '/views/partials/layout.php'; ?>
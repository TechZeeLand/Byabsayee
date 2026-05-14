<?php
$pageTitle = 'Debts — ' . e($book['name']);
ob_start();
?>

<div class="page-header">
    <div class="page-header-left">
        <div class="breadcrumb">
            <a href="/books/<?= $book['id'] ?>">Dashboard</a> <span>›</span>
            <span>Debts</span>
        </div>
        <h1><i class="fa-solid fa-file-circle-minus" style="color:var(--red)"></i> Debts</h1>
        <p>Money your business owes — loans, supplier credit, payables</p>
    </div>
    <button class="btn btn-primary" data-modal="addDebtModal">
        <i class="fa-solid fa-plus"></i> Add Debt
    </button>
</div>

<!-- Summary cards -->
<div class="stat-grid" style="grid-template-columns:repeat(auto-fit,minmax(150px,1fr));max-width:760px;margin-bottom:22px">
    <div class="stat-card" style="border-left:4px solid var(--red)">
        <div class="stat-label"><i class="fa-solid fa-circle-exclamation" style="color:var(--red)"></i> Outstanding</div>
        <div class="stat-value red"><?= format_money((float)($summary['outstanding'] ?? 0), $symbol) ?></div>
        <div class="stat-sub"><?= (int)($summary['unpaid_count']??0) + (int)($summary['partial_count']??0) ?> active</div>
    </div>
    <div class="stat-card" style="border-left:4px solid var(--green)">
        <div class="stat-label"><i class="fa-solid fa-check-circle" style="color:var(--green)"></i> Repaid</div>
        <div class="stat-value green"><?= format_money((float)($summary['total_paid'] ?? 0), $symbol) ?></div>
        <div class="stat-sub"><?= (int)($summary['paid_count']??0) ?> fully settled</div>
    </div>
    <div class="stat-card" style="border-left:4px solid var(--amber)">
        <div class="stat-label"><i class="fa-solid fa-hourglass-half" style="color:var(--amber)"></i> Partial</div>
        <div class="stat-value" style="color:var(--amber)"><?= (int)($summary['partial_count']??0) ?></div>
        <div class="stat-sub">partially repaid</div>
    </div>
    <div class="stat-card">
        <div class="stat-label"><i class="fa-solid fa-list"></i> Total</div>
        <div class="stat-value brand"><?= (int)($summary['total_count']??0) ?></div>
        <div class="stat-sub">all debt records</div>
    </div>
</div>

<!-- Filter tabs -->
<div style="display:flex;gap:6px;margin-bottom:16px;flex-wrap:wrap;align-items:center">
    <?php foreach (['all'=>'All','unpaid'=>'Unpaid','partial'=>'Partial','paid'=>'Paid','cancelled'=>'Cancelled'] as $val=>$label): ?>
    <a href="?filter=<?= $val ?><?= $search ? '&q='.urlencode($search) : '' ?>"
       class="btn btn-sm <?= $filter===$val ? 'btn-primary' : 'btn-secondary' ?>">
        <?= $label ?>
        <?php if ($val==='unpaid' && ($summary['unpaid_count']??0) > 0): ?>
        <span class="badge badge-red" style="font-size:10px;padding:1px 5px;margin-left:4px"><?= (int)$summary['unpaid_count'] ?></span>
        <?php endif; ?>
    </a>
    <?php endforeach; ?>

    <form method="GET" style="display:flex;gap:6px;margin-left:auto">
        <input type="hidden" name="filter" value="<?= e($filter) ?>">
        <input type="text" name="q" value="<?= e($search) ?>"
               placeholder="Search debt or creditor…"
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

<!-- Debts -->
<?php if (empty($debts)): ?>
<div class="table-wrap">
    <div class="empty-state">
        <div class="empty-icon"><i class="fa-solid fa-file-circle-minus"></i></div>
        <h3>No debts recorded</h3>
        <p>Track loans, supplier credit lines, or any money your business owes.</p>
    </div>
</div>
<?php else: ?>
<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>Title</th>
                <th>Creditor / Party</th>
                <th>Total Owed</th>
                <th>Repaid</th>
                <th>Remaining</th>
                <th>Due Date</th>
                <th>Status</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($debts as $debt):
            $remaining  = (float)$debt['amount'] - (float)$debt['paid_amount'];
            $pct        = $debt['amount'] > 0 ? min(100, round($debt['paid_amount'] / $debt['amount'] * 100)) : 0;
            $statusMap  = [
                'unpaid'    => ['badge-red',   'Unpaid'],
                'partial'   => ['badge-amber', 'Partial'],
                'paid'      => ['badge-green', 'Paid'],
                'cancelled' => ['badge-gray',  'Cancelled'],
            ];
            [$badgeClass, $badgeLabel] = $statusMap[$debt['status']] ?? ['badge-gray', ucfirst($debt['status'])];
        ?>
        <tr>
            <td>
                <div style="font-weight:600"><?= e($debt['title']) ?></div>
                <?php if (!empty($debt['note'])): ?>
                <div class="td-muted" style="font-size:11px;font-style:italic;max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= e($debt['note']) ?></div>
                <?php endif; ?>
                <?php if (!empty($paymentsByDebt[$debt['id']])): ?>
                <div style="margin-top:4px">
                    <?php foreach (array_slice($paymentsByDebt[$debt['id']], 0, 2) as $p): ?>
                    <span style="font-size:10px;background:var(--green-bg,#f0fdf4);color:var(--green);padding:1px 6px;border-radius:99px;margin-right:3px;display:inline-block;margin-top:2px">
                        <i class="fa-solid fa-check"></i> <?= format_money((float)$p['amount']) ?> · <?= date('d M', strtotime($p['paid_at'])) ?>
                    </span>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </td>
            <td>
                <?php if (!empty($debt['party'])): ?>
                <div style="display:flex;align-items:center;gap:6px">
                    <div style="width:28px;height:28px;border-radius:50%;background:var(--red-bg,#fef2f2);color:var(--red);display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;flex-shrink:0">
                        <?= mb_strtoupper(mb_substr($debt['party'],0,1)) ?>
                    </div>
                    <span style="font-weight:500"><?= e($debt['party']) ?></span>
                </div>
                <?php else: ?>
                <span class="td-muted">—</span>
                <?php endif; ?>
            </td>
            <td style="font-weight:600"><?= format_money((float)$debt['amount'], $symbol) ?></td>
            <td>
                <div style="color:var(--green);font-weight:600"><?= format_money((float)$debt['paid_amount'], $symbol) ?></div>
                <div style="height:3px;background:var(--border);border-radius:99px;width:80px;margin-top:3px">
                    <div style="height:100%;border-radius:99px;width:<?= $pct ?>%;background:<?= $debt['status']==='paid'?'var(--green)':'var(--amber)' ?>"></div>
                </div>
                <div style="font-size:10px;color:var(--text-muted);margin-top:1px"><?= $pct ?>%</div>
            </td>
            <td>
                <?php if ($remaining > 0.001): ?>
                <span style="color:var(--red);font-weight:700"><?= format_money($remaining, $symbol) ?></span>
                <?php else: ?>
                <span style="color:var(--green)"><i class="fa-solid fa-check"></i> Cleared</span>
                <?php endif; ?>
            </td>
            <td class="td-muted">
                <?php if (!empty($debt['due_date'])):
                    $dueDate = new DateTime($debt['due_date']);
                    $today   = new DateTime('today');
                    $overdue = $dueDate < $today && !in_array($debt['status'], ['paid','cancelled']);
                ?>
                <span <?= $overdue ? 'style="color:var(--red);font-weight:600"' : '' ?>>
                    <?= format_date($debt['due_date']) ?>
                    <?php if ($overdue): ?>
                    <br><small><?= (int)$today->diff($dueDate)->days ?> days overdue</small>
                    <?php endif; ?>
                </span>
                <?php else: ?>—<?php endif; ?>
            </td>
            <td><span class="badge <?= $badgeClass ?>"><?= $badgeLabel ?></span></td>
            <td style="white-space:nowrap;text-align:right">
                <?php if (!in_array($debt['status'], ['paid','cancelled'])): ?>
                <button class="btn btn-sm btn-secondary" title="Record repayment"
                        onclick="openDebtPay(<?= $debt['id'] ?>,'<?= e(addslashes($debt['title'])) ?>',<?= $remaining ?>,'<?= $symbol ?>')">
                    <i class="fa-solid fa-money-bill-wave" style="color:var(--green)"></i>
                </button>
                <button class="btn btn-sm btn-secondary" title="Edit"
                        onclick="openDebtEdit(
                            <?= $debt['id'] ?>,
                            '<?= e(addslashes($debt['title'])) ?>',
                            '<?= e(addslashes($debt['party'] ?? '')) ?>',
                            <?= (float)$debt['amount'] ?>,
                            '<?= $debt['due_date'] ?? '' ?>',
                            '<?= e(addslashes($debt['note'] ?? '')) ?>'
                        )">
                    <i class="fa-solid fa-pen"></i>
                </button>
                <form method="POST" action="/books/<?= $book['id'] ?>/debts/<?= $debt['id'] ?>/cancel"
                      style="display:inline" onsubmit="return confirm('Mark this debt as cancelled?')">
                    <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
                    <button class="btn btn-sm btn-secondary" title="Cancel debt">
                        <i class="fa-solid fa-ban" style="color:var(--amber)"></i>
                    </button>
                </form>
                <?php endif; ?>
                <form method="POST" action="/books/<?= $book['id'] ?>/debts/<?= $debt['id'] ?>/delete"
                      style="display:inline" onsubmit="return confirm('Delete this debt record permanently?')">
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


<!-- ══ ADD DEBT MODAL ══ -->
<div class="modal-backdrop" id="addDebtModal">
    <div class="modal">
        <div class="modal-title">
            <i class="fa-solid fa-file-circle-minus" style="color:var(--red)"></i> Add Debt
        </div>
        <form method="POST" action="/books/<?= $book['id'] ?>/debts/add">
            <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
            <div class="form-grid" style="gap:12px">
                <div class="form-group full">
                    <label>Title *</label>
                    <input type="text" name="title" required placeholder="e.g. Bank loan, Supplier credit, Equipment purchase…">
                </div>
                <div class="form-group">
                    <label>Creditor / Party</label>
                    <input type="text" name="party" placeholder="Bank name, supplier, person…">
                </div>
                <div class="form-group">
                    <label>Total Amount *</label>
                    <input type="number" name="amount" min="0.01" step="0.01" required placeholder="0.00">
                </div>
                <div class="form-group full">
                    <label>Due / Repay Date</label>
                    <input type="date" name="due_date">
                </div>
                <div class="form-group full">
                    <label>Note (optional)</label>
                    <textarea name="note" style="min-height:60px" placeholder="Loan terms, interest rate, reference number…"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-close-modal>Cancel</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-plus"></i> Add Debt
                </button>
            </div>
        </form>
    </div>
</div>


<!-- ══ EDIT DEBT MODAL ══ -->
<div class="modal-backdrop" id="editDebtModal">
    <div class="modal">
        <div class="modal-title">
            <i class="fa-solid fa-pen" style="color:var(--brand)"></i> Edit Debt
        </div>
        <form method="POST" id="editDebtForm">
            <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
            <div class="form-grid" style="gap:12px">
                <div class="form-group full">
                    <label>Title *</label>
                    <input type="text" name="title" id="editDebtTitle" required>
                </div>
                <div class="form-group">
                    <label>Creditor / Party</label>
                    <input type="text" name="party" id="editDebtParty">
                </div>
                <div class="form-group">
                    <label>Total Amount *</label>
                    <input type="number" name="amount" id="editDebtAmount" min="0.01" step="0.01" required>
                </div>
                <div class="form-group full">
                    <label>Due / Repay Date</label>
                    <input type="date" name="due_date" id="editDebtDueDate">
                </div>
                <div class="form-group full">
                    <label>Note (optional)</label>
                    <textarea name="note" id="editDebtNote" style="min-height:60px"></textarea>
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


<!-- ══ RECORD REPAYMENT MODAL ══ -->
<div class="modal-backdrop" id="debtPayModal">
    <div class="modal">
        <div class="modal-title">
            <i class="fa-solid fa-money-bill-wave" style="color:var(--green)"></i> Record Repayment
        </div>
        <form method="POST" id="debtPayForm">
            <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
            <div class="form-grid" style="gap:12px">
                <div class="form-group full">
                    <div id="debtPayLabel" style="font-weight:600;color:var(--brand);font-size:14px;padding-bottom:4px"></div>
                </div>
                <div class="form-group">
                    <label>Repayment Amount *</label>
                    <input type="number" name="amount" id="debtPayAmount" min="0.01" step="0.01" required placeholder="0.00">
                    <small class="form-hint" id="debtPayRemaining"></small>
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
                    <textarea name="note" style="min-height:50px" placeholder="Reference no., remarks…"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-close-modal>Cancel</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-check"></i> Save Repayment
                </button>
            </div>
        </form>
    </div>
</div>

<style>
.stat-sub{font-size:11px;color:var(--text-muted);margin-top:2px}
.badge-amber{background:#fff8e1;color:var(--amber,#f59e0b)}
.badge-gray{background:#f3f4f6;color:#6b7280}
</style>

<script>
function openDebtPay(id, title, remaining, sym) {
    document.getElementById('debtPayForm').action   = '/books/<?= $book['id'] ?>/debts/' + id + '/pay';
    document.getElementById('debtPayLabel').textContent = title;
    document.getElementById('debtPayAmount').value  = remaining.toFixed(2);
    document.getElementById('debtPayAmount').max    = remaining;
    document.getElementById('debtPayRemaining').textContent = 'Outstanding: ' + sym + remaining.toFixed(2);
    document.getElementById('debtPayModal').classList.add('open');
}

function openDebtEdit(id, title, party, amount, dueDate, note) {
    document.getElementById('editDebtForm').action       = '/books/<?= $book['id'] ?>/debts/' + id + '/edit';
    document.getElementById('editDebtTitle').value       = title;
    document.getElementById('editDebtParty').value       = party;
    document.getElementById('editDebtAmount').value      = amount;
    document.getElementById('editDebtDueDate').value     = dueDate;
    document.getElementById('editDebtNote').value        = note;
    document.getElementById('editDebtModal').classList.add('open');
}
</script>

<?php $content = ob_get_clean(); require BASE_PATH . '/views/partials/layout.php'; ?>

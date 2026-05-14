<?php
$pageTitle = 'Funds — ' . e($book['name']);
ob_start();
?>

<div class="page-header">
    <div class="page-header-left">
        <div class="breadcrumb">
            <a href="/books/<?= $book['id'] ?>">Dashboard</a> <span>›</span>
            <span>Funds</span>
        </div>
        <h1><i class="fa-solid fa-piggy-bank" style="color:var(--brand)"></i> Funds</h1>
        <p>Track money coming in and going out of the business account</p>
    </div>
    <div style="display:flex;gap:8px">
        <button class="btn btn-secondary" data-modal="withdrawModal">
            <i class="fa-solid fa-circle-arrow-up" style="color:var(--red)"></i> Withdraw
        </button>
        <button class="btn btn-primary" data-modal="addFundsModal">
            <i class="fa-solid fa-circle-arrow-down"></i> Add Funds
        </button>
    </div>
</div>

<!-- Summary cards -->
<div class="stat-grid" style="grid-template-columns:repeat(auto-fit,minmax(160px,1fr));max-width:640px;margin-bottom:22px">
    <div class="stat-card" style="border-top:3px solid var(--green)">
        <div class="stat-label"><i class="fa-solid fa-circle-arrow-down" style="color:var(--green)"></i> Total Added</div>
        <div class="stat-value green"><?= format_money((float)($totals['total_added'] ?? 0)) ?></div>
    </div>
    <div class="stat-card" style="border-top:3px solid var(--red)">
        <div class="stat-label"><i class="fa-solid fa-circle-arrow-up" style="color:var(--red)"></i> Total Withdrawn</div>
        <div class="stat-value red"><?= format_money((float)($totals['total_withdrawn'] ?? 0)) ?></div>
    </div>
    <div class="stat-card" style="border-top:3px solid var(--brand)">
        <div class="stat-label"><i class="fa-solid fa-wallet" style="color:var(--brand)"></i> Net Balance</div>
        <?php $net = (float)($totals['total_added'] ?? 0) - (float)($totals['total_withdrawn'] ?? 0); ?>
        <div class="stat-value <?= $net >= 0 ? 'brand' : 'red' ?>">
            <?= format_money($net) ?>
        </div>
    </div>
</div>

<!-- Transactions table -->
<?php if (empty($transactions)): ?>
<div class="table-wrap">
    <div class="empty-state">
        <div class="empty-icon"><i class="fa-solid fa-piggy-bank"></i></div>
        <h3>No fund transactions yet</h3>
        <p>Click "Add Funds" to record money brought into the business.</p>
    </div>
</div>
<?php else: ?>
<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Source / Reason</th>
                <th>Type</th>
                <th>Amount</th>
                <th>Note</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($transactions as $tx): ?>
        <tr>
            <td class="td-muted" style="white-space:nowrap">
                <?= format_date($tx['fund_date']) ?>
            </td>
            <td style="font-weight:500"><?= e($tx['title']) ?></td>
            <td>
                <?php if ($tx['type'] === 'in'): ?>
                <span class="badge badge-green">
                    <i class="fa-solid fa-circle-arrow-down"></i> Added
                </span>
                <?php else: ?>
                <span class="badge badge-red">
                    <i class="fa-solid fa-circle-arrow-up"></i> Withdrawn
                </span>
                <?php endif; ?>
            </td>
            <td style="font-weight:700;color:<?= $tx['type']==='in' ? 'var(--green)' : 'var(--red)' ?>">
                <?= $tx['type']==='in' ? '+' : '−' ?><?= format_money((float)$tx['amount']) ?>
            </td>
            <td class="td-muted"><?= e($tx['note'] ?? '—') ?></td>
            <td style="text-align:right;white-space:nowrap">
                <button class="btn btn-sm btn-secondary"
                        title="Edit"
                        onclick="openFundEdit(<?= $tx['id'] ?>,<?= $tx['type']==='in'?'\'in\'':'\'out\'' ?>,'<?= e(addslashes($tx['title'])) ?>',<?= (float)$tx['amount'] ?>,'<?= $tx['fund_date'] ?>','<?= e(addslashes($tx['note'] ?? '')) ?>')">
                    <i class="fa-solid fa-pen"></i>
                </button>
                <form method="POST"
                      action="/books/<?= $book['id'] ?>/funds/<?= $tx['id'] ?>/delete"
                      style="display:inline"
                      onsubmit="return confirm('Delete this transaction?')">
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


<!-- ══ ADD FUNDS MODAL ══ -->
<div class="modal-backdrop" id="addFundsModal">
    <div class="modal" style="max-width:420px">
        <div class="modal-title">
            <i class="fa-solid fa-circle-arrow-down" style="color:var(--green)"></i> Add Funds
        </div>
        <form method="POST" action="/books/<?= $book['id'] ?>/funds/add">
            <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
            <input type="hidden" name="type" value="add">
            <div class="form-grid" style="gap:12px">
                <div class="form-group full">
                    <label>Source / Reason *</label>
                    <input type="text" name="source" required placeholder="e.g. Owner deposit, Loan received…">
                </div>
                <div class="form-group">
                    <label>Amount *</label>
                    <input type="number" name="amount" min="0.01" step="0.01" required placeholder="0.00">
                </div>
                <div class="form-group">
                    <label>Date *</label>
                    <input type="date" name="date" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="form-group full">
                    <label>Note</label>
                    <textarea name="note" style="min-height:50px" placeholder="Any additional details…"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-close-modal>Cancel</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-check"></i> Add Funds
                </button>
            </div>
        </form>
    </div>
</div>


<!-- ══ WITHDRAW MODAL ══ -->
<div class="modal-backdrop" id="withdrawModal">
    <div class="modal" style="max-width:420px">
        <div class="modal-title">
            <i class="fa-solid fa-circle-arrow-up" style="color:var(--red)"></i> Withdraw Funds
        </div>
        <form method="POST" action="/books/<?= $book['id'] ?>/funds/add">
            <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
            <input type="hidden" name="type" value="withdraw">
            <div class="form-grid" style="gap:12px">
                <div class="form-group full">
                    <label>Reason *</label>
                    <input type="text" name="source" required placeholder="e.g. Owner withdrawal, Loan repayment…">
                </div>
                <div class="form-group">
                    <label>Amount *</label>
                    <input type="number" name="amount" min="0.01" step="0.01" required placeholder="0.00">
                </div>
                <div class="form-group">
                    <label>Date *</label>
                    <input type="date" name="date" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="form-group full">
                    <label>Note</label>
                    <textarea name="note" style="min-height:50px" placeholder="Any additional details…"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-close-modal>Cancel</button>
                <button type="submit" class="btn btn-danger">
                    <i class="fa-solid fa-circle-arrow-up"></i> Withdraw
                </button>
            </div>
        </form>
    </div>
</div>


<!-- ══ EDIT FUND MODAL ══ -->
<div class="modal-backdrop" id="editFundModal">
    <div class="modal" style="max-width:420px">
        <div class="modal-title">
            <i class="fa-solid fa-pen" style="color:var(--brand)"></i> Edit Transaction
        </div>
        <form method="POST" id="editFundForm">
            <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
            <input type="hidden" name="type" id="editFundType">
            <div class="form-grid" style="gap:12px">
                <div class="form-group full">
                    <label>Source / Reason *</label>
                    <input type="text" name="source" id="editFundSource" required>
                </div>
                <div class="form-group">
                    <label>Amount *</label>
                    <input type="number" name="amount" id="editFundAmount" min="0.01" step="0.01" required>
                </div>
                <div class="form-group">
                    <label>Date *</label>
                    <input type="date" name="date" id="editFundDate" required>
                </div>
                <div class="form-group full">
                    <label>Note</label>
                    <textarea name="note" id="editFundNote" style="min-height:50px"></textarea>
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

<script>
function openFundEdit(id, type, source, amount, date, note) {
    document.getElementById('editFundForm').action = '/books/<?= $book['id'] ?>/funds/' + id + '/edit';
    document.getElementById('editFundType').value   = type;
    document.getElementById('editFundSource').value = source;
    document.getElementById('editFundAmount').value = amount;
    document.getElementById('editFundDate').value   = date;
    document.getElementById('editFundNote').value   = note;
    document.getElementById('editFundModal').classList.add('open');
}
</script>

<?php $content = ob_get_clean(); require BASE_PATH . '/views/partials/layout.php'; ?>

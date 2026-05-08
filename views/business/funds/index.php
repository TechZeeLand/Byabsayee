<?php
$pageTitle = 'Funds — ' . e($book['name']);
$available = $totals['total_added'] - $totals['total_withdrawn'];
ob_start();
?>

<div class="page-header">
    <div class="page-header-left">
        <div class="breadcrumb">
            <a href="/books/<?= $book['id'] ?>">Dashboard</a> <span>›</span>
            <span>Funds</span>
        </div>
        <h1><i class="fa-solid fa-piggy-bank" style="color:var(--brand)"></i> Business Funds</h1>
        <p>Track capital additions and withdrawals</p>
    </div>
    <div style="display:flex;gap:8px">
        <button class="btn btn-primary" data-modal="addFundModal">
            <i class="fa-solid fa-plus"></i> Add Funds
        </button>
        <button class="btn btn-danger" data-modal="withdrawFundModal">
            <i class="fa-solid fa-minus"></i> Withdraw
        </button>
    </div>
</div>

<!-- Summary -->
<div class="stat-grid" style="grid-template-columns:repeat(3,1fr);max-width:560px;margin-bottom:22px">
    <div class="stat-card" style="border-left:4px solid var(--green)">
        <div class="stat-label"><i class="fa-solid fa-arrow-down" style="color:var(--green)"></i> Total Added</div>
        <div class="stat-value green"><?= format_money($totals['total_added']) ?></div>
    </div>
    <div class="stat-card" style="border-left:4px solid var(--red)">
        <div class="stat-label"><i class="fa-solid fa-arrow-up" style="color:var(--red)"></i> Total Withdrawn</div>
        <div class="stat-value red"><?= format_money($totals['total_withdrawn']) ?></div>
    </div>
    <div class="stat-card" style="border-left:4px solid var(--brand)">
        <div class="stat-label"><i class="fa-solid fa-wallet" style="color:var(--brand)"></i> Available</div>
        <div class="stat-value <?= $available >= 0 ? 'brand' : 'red' ?>"><?= format_money($available) ?></div>
    </div>
</div>

<?php if (empty($transactions)): ?>
<div class="table-wrap">
    <div class="empty-state">
        <div class="empty-icon"><i class="fa-solid fa-piggy-bank"></i></div>
        <h3>No fund transactions yet</h3>
        <p>Record capital injections and withdrawals to track your business funds.</p>
    </div>
</div>
<?php else: ?>
<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Type</th>
                <th>Source / Reason</th>
                <th>Note</th>
                <th style="text-align:right">Amount</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($transactions as $tx): ?>
        <tr>
            <td class="td-muted"><?= format_date($tx['date']) ?></td>
            <td>
                <?php if ($tx['type'] === 'add'): ?>
                    <span class="badge badge-green"><i class="fa-solid fa-arrow-down"></i> Added</span>
                <?php else: ?>
                    <span class="badge badge-red"><i class="fa-solid fa-arrow-up"></i> Withdrawn</span>
                <?php endif; ?>
            </td>
            <td style="font-weight:500"><?= $tx['source'] ? e($tx['source']) : '—' ?></td>
            <td class="td-muted"><?= $tx['note'] ? e(mb_strimwidth($tx['note'],0,50,'…')) : '—' ?></td>
            <td style="text-align:right;font-weight:700;color:<?= $tx['type']==='add'?'var(--green)':'var(--red)' ?>">
                <?= ($tx['type']==='add' ? '+' : '−') . format_money($tx['amount']) ?>
            </td>
            <td style="text-align:right">
                <form method="POST" action="/books/<?= $book['id'] ?>/funds/<?= $tx['id'] ?>/delete"
                      data-confirm="Delete this transaction?">
                    <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
                    <button class="btn btn-sm btn-danger"><i class="fa-solid fa-trash"></i></button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<!-- ══ ADD FUNDS MODAL ══ -->
<div class="modal-backdrop" id="addFundModal">
    <div class="modal">
        <div class="modal-title"><i class="fa-solid fa-plus-circle" style="color:var(--green)"></i> Add Funds</div>
        <form method="POST" action="/books/<?= $book['id'] ?>/funds/add">
            <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
            <input type="hidden" name="type" value="add">
            <div class="form-grid" style="gap:12px">
                <div class="form-group">
                    <label>Amount (৳) *</label>
                    <input type="number" name="amount" min="0.01" step="0.01" required placeholder="0.00">
                </div>
                <div class="form-group">
                    <label>Date *</label>
                    <input type="date" name="date" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="form-group full">
                    <label>Source *</label>
                    <input type="text" name="source" required placeholder="e.g. Owner capital, Loan from bank, Personal savings">
                </div>
                <div class="form-group full">
                    <label>Note (optional)</label>
                    <textarea name="note" style="min-height:56px" placeholder="Any additional details…"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-close-modal>Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Add Funds</button>
            </div>
        </form>
    </div>
</div>

<!-- ══ WITHDRAW MODAL ══ -->
<div class="modal-backdrop" id="withdrawFundModal">
    <div class="modal">
        <div class="modal-title"><i class="fa-solid fa-minus-circle" style="color:var(--red)"></i> Withdraw Funds</div>
        <form method="POST" action="/books/<?= $book['id'] ?>/funds/add">
            <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
            <input type="hidden" name="type" value="withdraw">
            <div class="form-grid" style="gap:12px">
                <div class="form-group">
                    <label>Amount (৳) *</label>
                    <input type="number" name="amount" min="0.01" step="0.01" required placeholder="0.00">
                </div>
                <div class="form-group">
                    <label>Date *</label>
                    <input type="date" name="date" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="form-group full">
                    <label>Reason *</label>
                    <input type="text" name="source" required placeholder="e.g. Owner withdrawal, Loan repayment">
                </div>
                <div class="form-group full">
                    <label>Note (optional)</label>
                    <textarea name="note" style="min-height:56px"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-close-modal>Cancel</button>
                <button type="submit" class="btn btn-danger"><i class="fa-solid fa-minus"></i> Withdraw</button>
            </div>
        </form>
    </div>
</div>

<?php $content = ob_get_clean(); require BASE_PATH . '/views/partials/layout.php'; ?>

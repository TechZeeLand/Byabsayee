<?php
$pageTitle = 'Expenses — ' . e($book['name']);
ob_start();
?>

<div class="page-header">
    <div class="page-header-left">
        <div class="breadcrumb">
            <a href="/books/<?= $book['id'] ?>">Dashboard</a> <span>›</span>
            <span>Expenses</span>
        </div>
        <h1><i class="fa-solid fa-receipt" style="color:var(--red)"></i> Expenses</h1>
        <p>Track all outgoing costs</p>
    </div>
    <div style="display:flex;gap:8px">
        <button class="btn btn-secondary btn-sm" data-modal="addCategoryModal">
            <i class="fa-solid fa-folder-plus"></i> Add Category
        </button>
        <button class="btn btn-primary" data-modal="addExpenseModal">
            <i class="fa-solid fa-plus"></i> Add Expense
        </button>
    </div>
</div>

<!-- Month navigator -->
<div style="display:flex;align-items:center;gap:10px;margin-bottom:18px;flex-wrap:wrap">
    <?php
    $prevMonth = date('Y-m', strtotime($month . '-01 -1 month'));
    $nextMonth = date('Y-m', strtotime($month . '-01 +1 month'));
    $isCurrent = $month === date('Y-m');
    ?>
    <a href="?month=<?= $prevMonth ?>&cat=<?= $catId ?>" class="btn btn-secondary btn-sm">
        <i class="fa-solid fa-chevron-left"></i>
    </a>
    <span style="font-weight:600;font-size:14px;min-width:120px;text-align:center">
        <?= date('F Y', strtotime($month . '-01')) ?>
    </span>
    <?php if (!$isCurrent): ?>
    <a href="?month=<?= $nextMonth ?>&cat=<?= $catId ?>" class="btn btn-secondary btn-sm">
        <i class="fa-solid fa-chevron-right"></i>
    </a>
    <?php else: ?>
    <span class="btn btn-secondary btn-sm" style="opacity:.4;cursor:not-allowed">
        <i class="fa-solid fa-chevron-right"></i>
    </span>
    <?php endif; ?>
    <span style="font-size:13px;color:var(--text-muted)">
        Total: <strong style="color:var(--red)"><?= format_money($monthTotal) ?></strong>
    </span>
</div>

<!-- Category filter pills -->
<?php if (!empty($categories)): ?>
<div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:16px">
    <a href="?month=<?= $month ?>&cat=0"
       class="btn btn-sm <?= $catId===0 ? 'btn-primary' : 'btn-secondary' ?>">
        All
    </a>
    <?php foreach ($categories as $cat): ?>
    <a href="?month=<?= $month ?>&cat=<?= $cat['id'] ?>"
       class="btn btn-sm <?= $catId===$cat['id'] ? 'btn-primary' : 'btn-secondary' ?>">
        <i class="fa-solid <?= e($cat['icon'] ?? 'fa-tag') ?>"></i>
        <?= e($cat['name']) ?>
    </a>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Expense list -->
<?php if (empty($expenses)): ?>
<div class="table-wrap">
    <div class="empty-state">
        <div class="empty-icon"><i class="fa-solid fa-receipt"></i></div>
        <h3>No expenses this month</h3>
        <p>Click "Add Expense" to record an outgoing cost.</p>
    </div>
</div>
<?php else: ?>
<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Title</th>
                <th>Category</th>
                <th>Paid To</th>
                <th>Amount</th>
                <th>Receipt</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($expenses as $exp): ?>
        <tr>
            <td class="td-muted" style="white-space:nowrap"><?= format_date($exp['date']) ?></td>
            <td>
                <div style="font-weight:500"><?= e($exp['title']) ?></div>
                <?php if (!empty($exp['note'])): ?>
                <div class="td-muted"><?= e($exp['note']) ?></div>
                <?php endif; ?>
            </td>
            <td>
                <?php if (!empty($exp['category_name'])): ?>
                <span style="display:inline-flex;align-items:center;gap:5px;background:var(--bg);padding:2px 8px;border-radius:99px;font-size:12px">
                    <i class="fa-solid <?= e($exp['category_icon'] ?? 'fa-tag') ?>" style="font-size:10px;color:var(--text-muted)"></i>
                    <?= e($exp['category_name']) ?>
                </span>
                <?php else: ?>
                <span class="td-muted">—</span>
                <?php endif; ?>
            </td>
            <td class="td-muted"><?= e($exp['paid_to'] ?? '—') ?></td>
            <td style="font-weight:700;color:var(--red)"><?= format_money((float)$exp['amount']) ?></td>
            <td>
                <?php if (!empty($exp['receipt'])): ?>
                <a href="<?= asset('uploads/'.$exp['receipt']) ?>"
                   target="_blank"
                   class="btn btn-sm btn-secondary"
                   title="View receipt">
                    <i class="fa-solid fa-file-image"></i>
                </a>
                <?php else: ?>
                <span class="td-muted">—</span>
                <?php endif; ?>
            </td>
            <td style="text-align:right">
                <form method="POST" action="/books/<?= $book['id'] ?>/expenses/<?= $exp['id'] ?>/delete"
                      data-confirm="Delete this expense?">
                    <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
                    <button class="btn btn-sm btn-secondary" title="Delete">
                        <i class="fa-solid fa-trash" style="color:var(--red)"></i>
                    </button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr style="background:var(--bg)">
                <td colspan="4" style="padding:10px 14px;font-weight:600;text-align:right">
                    Month Total
                </td>
                <td style="padding:10px 14px;font-weight:700;color:var(--red)">
                    <?= format_money($monthTotal) ?>
                </td>
                <td colspan="2"></td>
            </tr>
        </tfoot>
    </table>
</div>
<?php endif; ?>


<!-- ══ ADD EXPENSE MODAL ══ -->
<div class="modal-backdrop" id="addExpenseModal">
    <div class="modal">
        <div class="modal-title">
            <i class="fa-solid fa-receipt" style="color:var(--red)"></i> Add Expense
        </div>
        <form method="POST" action="/books/<?= $book['id'] ?>/expenses/add"
              enctype="multipart/form-data">
            <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
            <div class="form-grid" style="gap:12px">
                <div class="form-group full">
                    <label>Title *</label>
                    <input type="text" name="title" required placeholder="e.g. Electricity bill, Office rent…">
                </div>
                <div class="form-group">
                    <label>Amount *</label>
                    <input type="number" name="amount" min="0.01" step="0.01" required placeholder="0.00">
                </div>
                <div class="form-group">
                    <label>Date *</label>
                    <input type="date" name="date" value="<?= date('Y-m-d') ?>" required>
                </div>
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
                    <label>Paid To</label>
                    <input type="text" name="paid_to" placeholder="Vendor / person name">
                </div>
                <div class="form-group full">
                    <label>Note</label>
                    <textarea name="note" style="min-height:50px" placeholder="Any details…"></textarea>
                </div>
                <div class="form-group full">
                    <label>Receipt (optional)</label>
                    <input type="file" name="receipt" accept=".jpg,.jpeg,.png,.webp,.pdf">
                    <small class="form-hint">JPG, PNG, PDF — max 10 MB</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-close-modal>Cancel</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-plus"></i> Add Expense
                </button>
            </div>
        </form>
    </div>
</div>


<!-- ══ ADD CATEGORY MODAL ══ -->
<div class="modal-backdrop" id="addCategoryModal">
    <div class="modal" style="max-width:380px">
        <div class="modal-title">
            <i class="fa-solid fa-folder-plus" style="color:var(--brand)"></i> Add Category
        </div>
        <form method="POST" action="/books/<?= $book['id'] ?>/expenses/category/add">
            <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
            <div class="form-grid" style="gap:12px">
                <div class="form-group full">
                    <label>Category Name *</label>
                    <input type="text" name="name" required placeholder="e.g. Rent, Utility, Salary…">
                </div>
                <div class="form-group full">
                    <label>Icon (Font Awesome class)</label>
                    <input type="text" name="icon" value="fa-tag" placeholder="fa-tag">
                    <small class="form-hint">e.g. fa-bolt, fa-building, fa-truck</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-close-modal>Cancel</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-check"></i> Save Category
                </button>
            </div>
        </form>
    </div>
</div>

<?php $content = ob_get_clean(); require BASE_PATH . '/views/partials/layout.php'; ?>

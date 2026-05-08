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
        <h1><i class="fa-solid fa-money-bill-wave" style="color:var(--red)"></i> Expenses</h1>
        <p>Other outgoing costs — rent, utilities, transport, etc.</p>
    </div>
    <div style="display:flex;gap:8px">
        <button class="btn btn-secondary" data-modal="addCategoryModal">
            <i class="fa-solid fa-folder-plus"></i> Category
        </button>
        <button class="btn btn-primary" data-modal="addExpenseModal">
            <i class="fa-solid fa-plus"></i> Add Expense
        </button>
    </div>
</div>

<!-- Month filter + total -->
<div style="display:flex;gap:12px;align-items:center;margin-bottom:20px;flex-wrap:wrap">
    <form method="GET" style="display:flex;gap:8px;align-items:center">
        <input type="month" name="month" value="<?= e($month) ?>"
               style="padding:7px 10px;border:1.5px solid var(--border);border-radius:var(--radius);font-size:13px;font-family:inherit;outline:none">
        <button type="submit" class="btn btn-sm btn-secondary">
            <i class="fa-solid fa-filter"></i> Filter
        </button>
    </form>
    <?php if (!empty($categories)): ?>
    <select onchange="window.location.href='?month=<?= e($month) ?>&cat='+this.value"
            style="padding:7px 10px;border:1.5px solid var(--border);border-radius:var(--radius);font-size:13px;font-family:inherit;outline:none">
        <option value="0" <?= !$catId?'selected':'' ?>>All Categories</option>
        <?php foreach ($categories as $cat): ?>
        <option value="<?= $cat['id'] ?>" <?= $catId===$cat['id']?'selected':'' ?>><?= e($cat['name']) ?></option>
        <?php endforeach; ?>
    </select>
    <?php endif; ?>
    <div class="stat-card" style="padding:10px 16px;margin:0">
        <div class="stat-label">Total this month</div>
        <div class="stat-value red" style="font-size:18px"><?= format_money($monthTotal) ?></div>
    </div>
</div>

<?php if (empty($expenses)): ?>
<div class="table-wrap">
    <div class="empty-state">
        <div class="empty-icon"><i class="fa-solid fa-receipt"></i></div>
        <h3>No expenses this month</h3>
        <p>Record rent, utility bills, transport and other costs here.</p>
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
                <th style="text-align:right">Amount</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($expenses as $exp): ?>
        <tr>
            <td class="td-muted"><?= format_date($exp['date']) ?></td>
            <td>
                <div style="font-weight:500"><?= e($exp['title']) ?></div>
                <?php if ($exp['note']): ?>
                    <div class="td-muted" style="font-size:11px"><?= e(mb_strimwidth($exp['note'],0,50,'…')) ?></div>
                <?php endif; ?>
            </td>
            <td>
                <?php if ($exp['category_name']): ?>
                <span class="badge badge-gray">
                    <i class="fa-solid <?= e($exp['category_icon']??'fa-receipt') ?>"></i>
                    <?= e($exp['category_name']) ?>
                </span>
                <?php else: ?>
                <span class="td-muted">—</span>
                <?php endif; ?>
            </td>
            <td class="td-muted"><?= $exp['paid_to'] ? e($exp['paid_to']) : '—' ?></td>
            <td style="text-align:right;font-weight:600;color:var(--red)"><?= format_money($exp['amount']) ?></td>
            <td style="text-align:right;white-space:nowrap">
                <?php if ($exp['receipt']): ?>
                <a href="<?= asset('uploads/'.$exp['receipt']) ?>" target="_blank"
                   class="btn btn-sm btn-secondary">
                    <i class="fa-solid fa-paperclip"></i>
                </a>
                <?php endif; ?>
                <form method="POST" action="/books/<?= $book['id'] ?>/expenses/<?= $exp['id'] ?>/delete"
                      style="display:inline" data-confirm="Delete &quot;<?= e($exp['title']) ?>&quot;?">
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

<!-- ══ ADD CATEGORY MODAL ══ -->
<div class="modal-backdrop" id="addCategoryModal">
    <div class="modal">
        <div class="modal-title"><i class="fa-solid fa-folder-plus"></i> Add Expense Category</div>
        <form method="POST" action="/books/<?= $book['id'] ?>/expenses/category/add">
            <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
            <div class="form-grid" style="gap:12px">
                <div class="form-group full">
                    <label>Category Name *</label>
                    <input type="text" name="name" required placeholder="e.g. Rent, Transport, Marketing">
                </div>
                <div class="form-group full">
                    <label>Font Awesome Icon class</label>
                    <input type="text" name="icon" value="fa-receipt" placeholder="e.g. fa-building, fa-bolt, fa-car">
                    <small style="font-size:11px;color:var(--text-muted)">
                        Find icons at <a href="https://fontawesome.com/icons" target="_blank">fontawesome.com/icons</a>
                    </small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-close-modal>Cancel</button>
                <button type="submit" class="btn btn-primary">Create Category</button>
            </div>
        </form>
    </div>
</div>

<!-- ══ ADD EXPENSE MODAL ══ -->
<div class="modal-backdrop" id="addExpenseModal">
    <div class="modal">
        <div class="modal-title"><i class="fa-solid fa-plus"></i> Add Expense</div>
        <form method="POST" action="/books/<?= $book['id'] ?>/expenses/add" enctype="multipart/form-data">
            <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
            <div class="form-grid" style="gap:12px">
                <div class="form-group full">
                    <label>Title *</label>
                    <input type="text" name="title" required placeholder="e.g. Monthly rent, Electricity bill">
                </div>
                <div class="form-group">
                    <label>Amount (৳) *</label>
                    <input type="number" name="amount" required min="0.01" step="0.01" placeholder="0.00">
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
                    <input type="text" name="paid_to" placeholder="e.g. Landlord name, DESCO">
                </div>
                <div class="form-group full">
                    <label>Note</label>
                    <textarea name="note" style="min-height:56px" placeholder="Any details…"></textarea>
                </div>
                <div class="form-group full">
                    <label>Receipt (optional)</label>
                    <input type="file" name="receipt" accept="image/*,.pdf">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-close-modal>Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Add Expense</button>
            </div>
        </form>
    </div>
</div>

<?php $content = ob_get_clean(); require BASE_PATH . '/views/partials/layout.php'; ?>

<?php
$pageTitle = e($book['name']) . ' — Byabsayee';
$balance   = $totals['total_in'] - $totals['total_out'];
ob_start();
?>

<!-- Page header -->
<div class="page-header">
    <div class="page-header-left">
        <div class="breadcrumb">
            <a href="/dashboard">Dashboard</a>
            <span>›</span>
            <span><?= e($book['name']) ?></span>
        </div>
        <h1 style="display:flex;align-items:center;gap:10px">
            <span style="display:inline-block;width:12px;height:12px;border-radius:50%;background:<?= e($book['color']) ?>;flex-shrink:0"></span>
            <?= e($book['name']) ?>
        </h1>
        <p>Personal book</p>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
        <a href="/books/<?= $book['id'] ?>/contacts" class="btn btn-secondary">
            <svg viewBox="0 0 20 20" fill="currentColor" width="14" height="14"><path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/></svg>
            Contacts
        </a>
        <button class="btn btn-primary" data-modal="addEntryModal">
            <svg viewBox="0 0 20 20" fill="currentColor" width="14" height="14"><path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"/></svg>
            Add Entry
        </button>
        <a href="/books/<?= $book['id'] ?>/edit" class="btn btn-secondary" title="Edit book">
            <svg viewBox="0 0 20 20" fill="currentColor" width="14" height="14"><path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"/></svg>
        </a>
    </div>
</div>

<!-- Summary cards -->
<div class="stat-grid" style="grid-template-columns:repeat(3,1fr);max-width:600px">
    <div class="stat-card">
        <div class="stat-label">Total Income</div>
        <div class="stat-value green"><?= format_money($totals['total_in']) ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Total Expenses</div>
        <div class="stat-value red"><?= format_money($totals['total_out']) ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Balance</div>
        <div class="stat-value <?= $balance >= 0 ? 'brand' : 'red' ?>"><?= format_money($balance) ?></div>
    </div>
</div>

<!-- Entries table -->
<p class="section-label">Entries (<?= count($entries) ?>)</p>

<?php if (empty($entries)): ?>
<div class="table-wrap">
    <div class="empty-state">
        <div class="empty-icon">📝</div>
        <h3>No entries yet</h3>
        <p>Click "Add Entry" to record your first income or expense.</p>
    </div>
</div>
<?php else: ?>

<!-- Filter bar -->
<div style="display:flex;gap:8px;margin-bottom:12px;flex-wrap:wrap;align-items:center">
    <button class="btn btn-sm btn-secondary filter-btn active" data-filter="all">All</button>
    <button class="btn btn-sm btn-secondary filter-btn" data-filter="in" style="color:var(--green)">Income</button>
    <button class="btn btn-sm btn-secondary filter-btn" data-filter="out" style="color:var(--red)">Expense</button>
    <div style="flex:1"></div>
    <input type="text" id="entrySearch" placeholder="Search entries…"
           style="padding:6px 12px;border:1.5px solid var(--border);border-radius:8px;font-size:13px;font-family:inherit;outline:none;width:200px">
</div>

<div class="table-wrap">
    <table id="entriesTable">
        <thead>
            <tr>
                <th>Date</th>
                <th>Title</th>
                <th>Contact</th>
                <th>Type</th>
                <th style="text-align:right">Amount</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($entries as $entry): ?>
        <tr data-type="<?= $entry['type'] ?>" data-search="<?= e(strtolower($entry['title'] . ' ' . $entry['contact_name'])) ?>">
            <td class="td-muted"><?= format_date($entry['entry_date']) ?></td>
            <td>
                <div style="font-weight:500"><?= e($entry['title']) ?></div>
                <?php if ($entry['description']): ?>
                    <div class="td-muted" style="margin-top:2px;font-size:12px"><?= e(mb_strimwidth($entry['description'], 0, 60, '…')) ?></div>
                <?php endif; ?>
            </td>
            <td class="td-muted"><?= $entry['contact_name'] ? e($entry['contact_name']) : '—' ?></td>
            <td>
                <?php if ($entry['type'] === 'in'): ?>
                    <span class="badge badge-green">Income</span>
                <?php else: ?>
                    <span class="badge badge-red">Expense</span>
                <?php endif; ?>
            </td>
            <td style="text-align:right">
                <span class="td-amount <?= $entry['type'] ?>">
                    <?= ($entry['type'] === 'in' ? '+' : '−') . ' ' . format_money($entry['amount']) ?>
                </span>
            </td>
            <td style="text-align:right">
                <form method="POST"
                      action="/books/<?= $book['id'] ?>/entries/<?= $entry['id'] ?>/delete"
                      style="display:inline"
                      data-confirm="Delete this entry?">
                    <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
                    <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<!-- ===== ADD ENTRY MODAL ===== -->
<div class="modal-backdrop" id="addEntryModal">
    <div class="modal">
        <div class="modal-title">Add Entry</div>

        <form method="POST" action="/books/<?= $book['id'] ?>/entries/add" enctype="multipart/form-data">
            <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">

            <!-- In / Out toggle -->
            <div class="form-group" style="margin-bottom:16px">
                <label>Type</label>
                <div class="type-toggle">
                    <input type="radio" name="type" id="type_in"  value="in"  checked>
                    <label for="type_in">&#43; Income</label>
                    <input type="radio" name="type" id="type_out" value="out">
                    <label for="type_out">&#8722; Expense</label>
                </div>
            </div>

            <div class="form-grid" style="gap:12px">
                <div class="form-group full">
                    <label for="e_title">Title *</label>
                    <input type="text" id="e_title" name="title" placeholder="e.g. Salary, Groceries" required>
                </div>

                <div class="form-group">
                    <label for="e_amount">Amount (৳) *</label>
                    <input type="number" id="e_amount" name="amount" placeholder="0.00"
                           step="0.01" min="0.01" required>
                </div>

                <div class="form-group">
                    <label for="e_date">Date *</label>
                    <input type="date" id="e_date" name="date" value="<?= date('Y-m-d') ?>" required>
                </div>

                <div class="form-group">
                    <label for="e_time">Time (optional)</label>
                    <input type="time" id="e_time" name="time">
                </div>

                <?php if (!empty($contacts)): ?>
                <div class="form-group">
                    <label for="e_contact">Contact (optional)</label>
                    <select id="e_contact" name="contact_id">
                        <option value="">— None —</option>
                        <?php foreach ($contacts as $c): ?>
                        <option value="<?= $c['id'] ?>"><?= e($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>

                <div class="form-group full">
                    <label for="e_desc">Description (optional)</label>
                    <textarea id="e_desc" name="description" placeholder="Any notes…" style="min-height:60px"></textarea>
                </div>

                <div class="form-group full">
                    <label for="e_attach">Attachment (optional — image or PDF, max 10MB)</label>
                    <input type="file" id="e_attach" name="attachment"
                           accept=".jpg,.jpeg,.png,.gif,.webp,.pdf"
                           style="font-size:13px">
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-close-modal>Cancel</button>
                <button type="submit" class="btn btn-primary">Save Entry</button>
            </div>
        </form>
    </div>
</div>

<script>
// Filter buttons
document.querySelectorAll('.filter-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        var filter = btn.dataset.filter;
        document.querySelectorAll('#entriesTable tbody tr').forEach(function(row) {
            row.style.display = (filter === 'all' || row.dataset.type === filter) ? '' : 'none';
        });
    });
});

// Search
document.getElementById('entrySearch') && document.getElementById('entrySearch').addEventListener('input', function() {
    var q = this.value.toLowerCase();
    document.querySelectorAll('#entriesTable tbody tr').forEach(function(row) {
        row.style.display = row.dataset.search.includes(q) ? '' : 'none';
    });
});
</script>

<?php
$content = ob_get_clean();
require BASE_PATH . '/views/partials/layout.php';
?>

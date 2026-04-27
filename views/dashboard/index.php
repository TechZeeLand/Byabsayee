<?php
$pageTitle = 'Dashboard — Byabsayee';
ob_start();
?>

<div class="page-header">
    <div class="page-header-left">
        <h1>Dashboard</h1>
        <p>Welcome back, <?= e(auth()['name']) ?></p>
    </div>
    <a href="/books/create" class="btn btn-primary">
        <svg viewBox="0 0 20 20" fill="currentColor" width="15" height="15"><path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"/></svg>
        New Book
    </a>
</div>

<?php
$totalIn  = array_sum(array_column($books, 'total_in'));
$totalOut = array_sum(array_column($books, 'total_out'));
$balance  = $totalIn - $totalOut;
?>

<div class="stat-grid">
    <div class="stat-card">
        <div class="stat-label">Total Income</div>
        <div class="stat-value green"><?= format_money($totalIn) ?></div>
        <div class="stat-sub">across all books</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Total Expenses</div>
        <div class="stat-value red"><?= format_money($totalOut) ?></div>
        <div class="stat-sub">across all books</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Net Balance</div>
        <div class="stat-value <?= $balance >= 0 ? 'brand' : 'red' ?>"><?= format_money($balance) ?></div>
        <div class="stat-sub"><?= count($books) ?> book<?= count($books) !== 1 ? 's' : '' ?></div>
    </div>
</div>

<p class="section-label">Your Books</p>

<?php if (empty($books)): ?>
<div class="empty-state">
    <div class="empty-icon">📒</div>
    <h3>No books yet</h3>
    <p>Create a personal book to track income and expenses,<br>or a business book for full accounting features.</p>
    <a href="/books/create" class="btn btn-primary" style="margin-top:4px">+ Create your first book</a>
</div>
<?php else: ?>
<div class="books-grid">
    <?php foreach ($books as $book):
        $bal = $book['total_in'] - $book['total_out'];
    ?>
    <a href="/books/<?= $book['id'] ?>" class="book-card" style="--book-color: <?= e($book['color']) ?>">
        <div class="book-card-header">
            <span class="book-card-name"><?= e($book['name']) ?></span>
            <span class="book-type-badge"><?= e($book['type']) ?></span>
        </div>
        <div class="book-card-numbers">
            <div class="book-num">
                <span class="book-num-val g"><?= format_money($book['total_in']) ?></span>
                <span class="book-num-lab">Income</span>
            </div>
            <div class="book-num">
                <span class="book-num-val r"><?= format_money($book['total_out']) ?></span>
                <span class="book-num-lab">Expense</span>
            </div>
        </div>
        <div class="book-balance">
            <span>Balance</span>
            <strong style="color:<?= $bal >= 0 ? 'var(--green)' : 'var(--red)' ?>">
                <?= format_money($bal) ?>
            </strong>
        </div>
    </a>
    <?php endforeach; ?>

    <a href="/books/create" class="new-book-card">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="28" height="28">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
        </svg>
        New Book
    </a>
</div>
<?php endif; ?>

<?php
$content = ob_get_clean();
require BASE_PATH . '/views/partials/layout.php';
?>

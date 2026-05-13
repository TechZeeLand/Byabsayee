<?php
$pageTitle = 'Reports — ' . e($book['name']);
ob_start();

$prevMonth  = date('Y-m', strtotime($month . '-01 -1 month'));
$nextMonth  = date('Y-m', strtotime($month . '-01 +1 month'));
$isCurrent  = $month === date('Y-m');

$catColors = [
    'invoice_sale'            => ['var(--green)',  'fa-file-invoice',       'Sale Invoice'],
    'invoice_purchase'        => ['var(--blue)',   'fa-cart-shopping',      'Purchase Invoice'],
    'sales_return'            => ['var(--red)',    'fa-rotate-left',        'Sales Return (Refund)'],
    'purchase_return'         => ['var(--green)',  'fa-truck-ramp-box',     'Purchase Return'],
    'return_discount_kept'    => ['var(--green)',  'fa-piggy-bank',         'Return Discount Kept'],
    'return_loss'             => ['var(--red)',    'fa-triangle-exclamation','Return Loss'],
    'delivery_expense'        => ['var(--amber)',  'fa-truck',              'Delivery Expense'],
    'Expense: General'        => ['var(--red)',    'fa-receipt',            'Expense'],
    'Fund Received'           => ['var(--green)',  'fa-piggy-bank',         'Fund In'],
    'Fund Withdrawn'          => ['var(--red)',    'fa-piggy-bank',         'Fund Out'],
];
function catStyle(string $cat): array {
    global $catColors;
    foreach ($catColors as $k => $v) {
        if (str_contains($cat, $k)) return $v;
    }
    return ['var(--text-muted)', 'fa-circle', $cat];
}
?>

<div class="page-header">
    <div class="page-header-left">
        <div class="breadcrumb">
            <a href="/books/<?= $book['id'] ?>">Dashboard</a> <span>›</span>
            <span>Reports</span>
        </div>
        <h1><i class="fa-solid fa-chart-line" style="color:var(--brand)"></i> Reports & Statements</h1>
        <p style="color:var(--text-muted);font-size:13px">All incoming and outgoing transactions in one place. Click any row to view details.</p>
    </div>
</div>

<!-- Summary bar -->
<div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px;margin-bottom:20px">
    <div class="card" style="text-align:center;border-top:3px solid var(--green)">
        <div style="font-size:11px;font-weight:700;text-transform:uppercase;color:var(--text-muted);letter-spacing:.5px">Total Income</div>
        <div style="font-size:24px;font-weight:800;color:var(--green);margin-top:4px"><?= $sym.number_format($totalIn,0) ?></div>
    </div>
    <div class="card" style="text-align:center;border-top:3px solid var(--red)">
        <div style="font-size:11px;font-weight:700;text-transform:uppercase;color:var(--text-muted);letter-spacing:.5px">Total Outgoing</div>
        <div style="font-size:24px;font-weight:800;color:var(--red);margin-top:4px"><?= $sym.number_format($totalOut,0) ?></div>
    </div>
    <div class="card" style="text-align:center;border-top:3px solid var(--brand)">
        <div style="font-size:11px;font-weight:700;text-transform:uppercase;color:var(--text-muted);letter-spacing:.5px">Net Balance</div>
        <?php $net = $totalIn - $totalOut; ?>
        <div style="font-size:24px;font-weight:800;color:<?= $net>=0?'var(--green)':'var(--red)' ?>;margin-top:4px">
            <?= ($net >= 0 ? '+' : '') . $sym.number_format($net,0) ?>
        </div>
    </div>
</div>

<!-- Controls -->
<div style="display:flex;align-items:center;gap:10px;margin-bottom:16px;flex-wrap:wrap">
    <a href="?month=<?= $prevMonth ?>&type=<?= $typeFilter ?>" class="btn btn-secondary btn-sm">
        <i class="fa-solid fa-chevron-left"></i>
    </a>
    <span style="font-weight:600;font-size:14px;min-width:120px;text-align:center">
        <?= date('F Y', strtotime($month.'-01')) ?>
    </span>
    <?php if (!$isCurrent): ?>
    <a href="?month=<?= $nextMonth ?>&type=<?= $typeFilter ?>" class="btn btn-secondary btn-sm">
        <i class="fa-solid fa-chevron-right"></i>
    </a>
    <?php else: ?>
    <span class="btn btn-secondary btn-sm" style="opacity:.4;cursor:not-allowed">
        <i class="fa-solid fa-chevron-right"></i>
    </span>
    <?php endif; ?>

    <div style="display:flex;gap:6px;margin-left:auto;flex-wrap:wrap">
        <?php foreach (['all'=>'All','in'=>'Income Only','out'=>'Outgoing Only'] as $k=>$lbl): ?>
        <a href="?month=<?= $month ?>&type=<?= $k ?>"
           class="btn btn-sm <?= $typeFilter===$k ? 'btn-primary' : 'btn-secondary' ?>">
            <?= $lbl ?>
        </a>
        <?php endforeach; ?>
    </div>
</div>

<!-- Ledger table -->
<div class="card" style="padding:0;overflow:hidden">
    <?php if (empty($entries)): ?>
    <div style="padding:48px;text-align:center;color:var(--text-muted)">
        <i class="fa-solid fa-chart-line" style="font-size:40px;opacity:.3;display:block;margin-bottom:12px"></i>
        No transactions found for this period.
    </div>
    <?php else: ?>
    <div style="overflow-x:auto">
    <table style="width:100%;border-collapse:collapse">
        <thead>
            <tr style="background:var(--bg)">
                <th style="padding:10px 14px;font-size:11px;font-weight:700;text-transform:uppercase;color:var(--text-muted);text-align:left;border-bottom:1px solid var(--border)">Date</th>
                <th style="padding:10px 14px;font-size:11px;font-weight:700;text-transform:uppercase;color:var(--text-muted);text-align:left;border-bottom:1px solid var(--border)">Category</th>
                <th style="padding:10px 14px;font-size:11px;font-weight:700;text-transform:uppercase;color:var(--text-muted);text-align:left;border-bottom:1px solid var(--border)">Ref / Party</th>
                <th style="padding:10px 14px;font-size:11px;font-weight:700;text-transform:uppercase;color:var(--text-muted);text-align:right;border-bottom:1px solid var(--border)">In</th>
                <th style="padding:10px 14px;font-size:11px;font-weight:700;text-transform:uppercase;color:var(--text-muted);text-align:right;border-bottom:1px solid var(--border)">Out</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($entries as $e):
            [$clr,$icon,$catLabel] = catStyle($e['category']);
            $isIn   = $e['direction'] === 'in';
            $amount = (float)$e['amount'];
        ?>
        <tr style="border-bottom:1px solid var(--border);cursor:pointer"
            onclick="window.location='<?= e($e['href'] ?? '#') ?>'"
            onmouseover="this.style.background='var(--bg)'" onmouseout="this.style.background=''">
            <td style="padding:10px 14px;font-size:12px;color:var(--text-muted);white-space:nowrap">
                <?= date('d M', strtotime($e['date'])) ?>
            </td>
            <td style="padding:10px 14px">
                <span style="display:inline-flex;align-items:center;gap:6px;font-size:12px;font-weight:600;color:<?= $clr ?>">
                    <i class="fa-solid <?= $icon ?>"></i>
                    <?= e($catLabel) ?>
                </span>
            </td>
            <td style="padding:10px 14px;font-size:13px">
                <div style="font-weight:500"><?= e($e['invoice_no'] ?? '—') ?></div>
                <div style="font-size:11px;color:var(--text-muted)"><?= e($e['party'] ?? '') ?></div>
            </td>
            <td style="padding:10px 14px;text-align:right;font-weight:700;font-size:14px;color:var(--green)">
                <?= $isIn ? $sym.number_format($amount,0) : '' ?>
            </td>
            <td style="padding:10px 14px;text-align:right;font-weight:700;font-size:14px;color:var(--red)">
                <?= !$isIn ? $sym.number_format($amount,0) : '' ?>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr style="background:var(--bg);border-top:2px solid var(--border)">
                <td colspan="3" style="padding:10px 14px;font-weight:700;font-size:13px">TOTAL</td>
                <td style="padding:10px 14px;text-align:right;font-weight:800;font-size:15px;color:var(--green)"><?= $sym.number_format($totalIn,0) ?></td>
                <td style="padding:10px 14px;text-align:right;font-weight:800;font-size:15px;color:var(--red)"><?= $sym.number_format($totalOut,0) ?></td>
            </tr>
        </tfoot>
    </table>
    </div>
    <?php endif; ?>
</div>

<?php $content = ob_get_clean(); require BASE_PATH . '/views/partials/layout.php'; ?>

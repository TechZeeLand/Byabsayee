<?php
$pageTitle = 'Returns — ' . e($book['name']);
$sym = \App\Helpers\Database::row('SELECT symbol FROM book_currencies WHERE book_id=? AND is_default=1', [$book['id']]);
$sym = $sym['symbol'] ?? '৳';
$typeFilter = $_GET['type'] ?? 'all';
ob_start();
?>

<div class="page-header">
    <div class="page-header-left">
        <div class="breadcrumb">
            <a href="/books/<?= $book['id'] ?>">Dashboard</a> <span>›</span>
            <span>Returns</span>
        </div>
        <h1><i class="fa-solid fa-rotate-left" style="color:var(--brand)"></i> Returns</h1>
        <p>Sales returns from customers &amp; purchase returns to suppliers</p>
    </div>
    <div style="display:flex;gap:8px">
        <a href="/books/<?= $book['id'] ?>/returns/create?type=sales_return" class="btn btn-primary">
            <i class="fa-solid fa-plus"></i> Sales Return
        </a>
        <a href="/books/<?= $book['id'] ?>/returns/create?type=purchase_return" class="btn btn-secondary">
            <i class="fa-solid fa-truck-ramp-box"></i> Purchase Return
        </a>
    </div>
</div>

<!-- Summary cards -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:14px;margin-bottom:20px">
    <div class="card" style="text-align:center">
        <div style="font-size:11px;font-weight:700;text-transform:uppercase;color:var(--text-muted);letter-spacing:.5px">Sales Refunds Given</div>
        <div style="font-size:22px;font-weight:800;color:var(--red);margin-top:4px"><?= $sym.number_format($summary['sales_refunds'],0) ?></div>
    </div>
    <div class="card" style="text-align:center">
        <div style="font-size:11px;font-weight:700;text-transform:uppercase;color:var(--text-muted);letter-spacing:.5px">Purchase Refunds Received</div>
        <div style="font-size:22px;font-weight:800;color:var(--green);margin-top:4px"><?= $sym.number_format($summary['purchase_refunds'],0) ?></div>
    </div>
    <div class="card" style="text-align:center">
        <div style="font-size:11px;font-weight:700;text-transform:uppercase;color:var(--text-muted);letter-spacing:.5px">Total Returns</div>
        <div style="font-size:22px;font-weight:800;margin-top:4px"><?= (int)$summary['total_count'] ?></div>
    </div>
</div>

<!-- Filter tabs -->
<div style="display:flex;gap:8px;margin-bottom:16px;flex-wrap:wrap">
    <?php foreach (['all'=>'All','sales_return'=>'Sales Returns','purchase_return'=>'Purchase Returns'] as $k=>$label): ?>
    <a href="?type=<?= $k ?>" class="btn btn-sm <?= $typeFilter===$k ? 'btn-primary' : 'btn-secondary' ?>">
        <?= $label ?>
    </a>
    <?php endforeach; ?>
</div>

<!-- Returns table -->
<div class="card" style="padding:0;overflow:hidden">
    <?php if (empty($returns)): ?>
    <div style="padding:48px;text-align:center;color:var(--text-muted)">
        <i class="fa-solid fa-rotate-left" style="font-size:40px;opacity:.3;margin-bottom:12px;display:block"></i>
        No returns recorded yet.
    </div>
    <?php else: ?>
    <div style="overflow-x:auto">
    <table style="width:100%;border-collapse:collapse">
        <thead>
            <tr style="background:var(--bg)">
                <th style="padding:10px 14px;font-size:11px;font-weight:700;text-transform:uppercase;color:var(--text-muted);text-align:left;border-bottom:1px solid var(--border)">Return No</th>
                <th style="padding:10px 14px;font-size:11px;font-weight:700;text-transform:uppercase;color:var(--text-muted);text-align:left;border-bottom:1px solid var(--border)">Type</th>
                <th style="padding:10px 14px;font-size:11px;font-weight:700;text-transform:uppercase;color:var(--text-muted);text-align:left;border-bottom:1px solid var(--border)">Party</th>
                <th style="padding:10px 14px;font-size:11px;font-weight:700;text-transform:uppercase;color:var(--text-muted);text-align:left;border-bottom:1px solid var(--border)">Orig. Invoice</th>
                <th style="padding:10px 14px;font-size:11px;font-weight:700;text-transform:uppercase;color:var(--text-muted);text-align:left;border-bottom:1px solid var(--border)">Date</th>
                <th style="padding:10px 14px;font-size:11px;font-weight:700;text-transform:uppercase;color:var(--text-muted);text-align:right;border-bottom:1px solid var(--border)">Subtotal</th>
                <th style="padding:10px 14px;font-size:11px;font-weight:700;text-transform:uppercase;color:var(--text-muted);text-align:right;border-bottom:1px solid var(--border)">Discount</th>
                <th style="padding:10px 14px;font-size:11px;font-weight:700;text-transform:uppercase;color:var(--text-muted);text-align:right;border-bottom:1px solid var(--border)">Refund</th>
                <th style="border-bottom:1px solid var(--border);width:40px">Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($returns as $r): ?>
            <?php
            $isSale  = $r['type'] === 'sales_return';
            $party   = $isSale ? ($r['customer_name'] ?? '—') : ($r['supplier_name'] ?? '—');
            $typeClr = $isSale ? 'var(--amber)' : 'var(--blue)';
            $typeLabel = $isSale ? 'Sales Return' : 'Purchase Return';
            ?>
            <tr style="border-bottom:1px solid var(--border)" onmouseover="this.style.background='var(--bg)'" onmouseout="this.style.background=''">
                <td style="padding:10px 14px">
                    <a href="/books/<?= $book['id'] ?>/returns/<?= $r['id'] ?>" style="font-weight:700;color:var(--brand)">
                        <?= e($r['return_no']) ?>
                    </a>
                </td>
                <td style="padding:10px 14px">
                    <span style="background:<?= $typeClr ?>22;color:<?= $typeClr ?>;padding:2px 8px;border-radius:20px;font-size:11px;font-weight:700">
                        <?= $typeLabel ?>
                    </span>
                </td>
                <td style="padding:10px 14px;font-size:13px"><?= e($party) ?></td>
                <td style="padding:10px 14px;font-size:13px">
                    <?php if ($r['orig_invoice_no']): ?>
                    <a href="/books/<?= $book['id'] ?>/invoices/<?= $r['invoice_id'] ?>" style="color:var(--brand)">
                        <?= e($r['orig_invoice_no']) ?>
                    </a>
                    <?php else: ?>—<?php endif; ?>
                </td>
                <td style="padding:10px 14px;font-size:13px;color:var(--text-muted)"><?= date('d M Y', strtotime($r['date'])) ?></td>
                <td style="padding:10px 14px;text-align:right;font-size:13px"><?= $sym.number_format($r['subtotal'],0) ?></td>
                <td style="padding:10px 14px;text-align:right;font-size:13px;color:var(--red)">
                    <?= $r['discount'] > 0 ? $sym.number_format($r['discount'],0) : '—' ?>
                </td>
                <td style="padding:10px 14px;text-align:right;font-weight:700;font-size:14px;color:<?= $isSale ? 'var(--red)' : 'var(--green)' ?>">
                    <?= $sym.number_format($r['total_refund'],0) ?>
                </td>
                <td style="padding:10px 14px">
                    <a href="/books/<?= $book['id'] ?>/returns/<?= $r['id'] ?>" class="btn btn-sm btn-secondary" title="View"><i class="fa-solid fa-eye"></i></a>
                    <form method="POST" action="/books/<?= $book['id'] ?>/returns/<?= $return['id'] ?>/delete"
                        onsubmit="return confirm('Delete this return? Stock changes will NOT be reversed.')">
                        <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
                        <button class="btn btn-sm btn-danger" style="color:var(--red)"><i class="fa-solid fa-trash"></i></button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</div>

<?php $content = ob_get_clean(); require BASE_PATH . '/views/partials/layout.php'; ?>

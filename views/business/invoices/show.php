<?php
$pageTitle = e($invoice['invoice_no']) . ' — Byabsayee';
$due       = $invoice['total'] - $invoice['paid'];
$sym       = $invoice['currency_symbol'] ?? '৳';
$statusColors = [
    'draft'     => ['badge-gray',  'Draft'],
    'sent'      => ['badge-blue',  'Sent'],
    'partial'   => ['badge-amber', 'Partial'],
    'paid'      => ['badge-green', 'Paid'],
    'overdue'   => ['badge-red',   'Overdue'],
    'cancelled' => ['badge-gray',  'Cancelled'],
];
[$sc, $sl] = $statusColors[$invoice['status']] ?? ['badge-gray','Unknown'];

// Load attachments
$attachments = \App\Helpers\Database::query(
    'SELECT * FROM invoice_attachments WHERE invoice_id=? ORDER BY created_at',
    [$invoice['id']]
);

// Load customer privileges
$assignedPrivs = [];
if ($customer) {
    $assignedPrivs = \App\Helpers\Database::query(
        'SELECT cp.* FROM customer_privilege_assignments cpa
         JOIN customer_privileges cp ON cp.id=cpa.privilege_id
         WHERE cpa.customer_id=?',
        [$customer['id']]
    );
}

// Load payment methods for the payment modal
$paymentMethodOpts = \App\Helpers\Database::query(
    'SELECT * FROM invoice_method_options WHERE book_id=? AND type="payment" ORDER BY sort_order',
    [$book['id']]
);

ob_start();
?>

<div class="page-header">
    <div class="page-header-left">
        <div class="breadcrumb">
            <a href="/books/<?= $book['id'] ?>">Dashboard</a> <span>›</span>
            <a href="/books/<?= $book['id'] ?>/invoices">Invoices</a> <span>›</span>
            <span><?= e($invoice['invoice_no']) ?></span>
        </div>
        <h1 style="display:flex;align-items:center;gap:10px">
            <?= e($invoice['invoice_no']) ?>
            <span class="badge <?= $sc ?>"><?= $sl ?></span>
            <?php if ($invoice['type'] === 'pos'): ?>
            <span class="badge badge-blue">POS</span>
            <?php endif; ?>
        </h1>
        <p><?= ucfirst($invoice['type']) ?> · <?= format_date($invoice['date']) ?></p>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
        <a href="/books/<?= $book['id'] ?>/invoices/<?= $invoice['id'] ?>/pdf"
           class="btn btn-secondary" target="_blank">📄 PDF</a>
        <a href="/books/<?= $book['id'] ?>/invoices/<?= $invoice['id'] ?>/thermal?w=80"
           class="btn btn-secondary" target="_blank">🖨 58/80mm Print</a>
        <?php if ($invoice['status'] === 'draft'): ?>
        <form method="POST" action="/books/<?= $book['id'] ?>/invoices/<?= $invoice['id'] ?>/sent">
            <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
            <button class="btn btn-secondary">Mark Sent</button>
        </form>
        <?php endif; ?>
        <?php if ($due > 0 && $invoice['status'] !== 'cancelled'): ?>
        <button class="btn btn-primary" data-modal="paymentModal">Record Payment</button>
        <?php endif; ?>
        <form method="POST" action="/books/<?= $book['id'] ?>/invoices/<?= $invoice['id'] ?>/delete"
              data-confirm="Delete invoice <?= e($invoice['invoice_no']) ?>?">
            <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
            <button class="btn btn-danger">Delete</button>
        </form>
    </div>
</div>

<!-- Privilege hint -->
<?php if (!empty($assignedPrivs) && $invoice['type'] === 'sale'): ?>
<div style="background:var(--green-bg);border:1px solid #bbf7d0;border-radius:var(--radius);padding:10px 14px;margin-bottom:16px;font-size:13px;color:var(--green)">
    🎫 <strong><?= e($customer['name']) ?></strong> has:
    <?php foreach ($assignedPrivs as $priv): ?>
        <strong><?= e($priv['name']) ?></strong>
        (<?= $priv['discount_type']==='percent' ? $priv['discount_value'].'%' : '৳'.number_format($priv['discount_value'],2) ?> off)<?php echo !$loop ?? ''; ?>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:2fr 1fr;gap:20px;align-items:start">

<!-- LEFT -->
<div style="display:flex;flex-direction:column;gap:16px">

    <!-- Business + party info -->
    <div class="card">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">
            <div>
                <p style="font-size:11px;font-weight:600;text-transform:uppercase;color:var(--text-muted);margin-bottom:8px">From</p>
                <div style="font-size:13px;line-height:1.8">
                    <strong><?= e($details['business_name'] ?? $book['name']) ?></strong><br>
                    <?php if ($book['phone'] ?? $details['phone'] ?? ''): ?>
                        <?= e($book['phone'] ?? $details['phone']) ?><br>
                    <?php endif; ?>
                    <?php if ($book['address'] ?? $details['address'] ?? ''): ?>
                        <?= e($book['address'] ?? $details['address']) ?>
                    <?php endif; ?>
                </div>
            </div>
            <div>
                <p style="font-size:11px;font-weight:600;text-transform:uppercase;color:var(--text-muted);margin-bottom:8px">
                    <?= $invoice['type']==='sale'||$invoice['type']==='pos' ? 'Bill To' : 'From Supplier' ?>
                </p>
                <div style="font-size:13px;line-height:1.8">
                    <?php if ($customer): ?>
                        <strong><?= e($customer['name']) ?></strong><br>
                        <?php if ($customer['phone']): ?><?= e($customer['phone']) ?><br><?php endif; ?>
                        <?php if ($customer['address']): ?><?= e($customer['address']) ?><?php endif; ?>
                    <?php elseif ($supplier): ?>
                        <strong><?= e($supplier['name']) ?></strong><br>
                        <?php if ($supplier['company']): ?><?= e($supplier['company']) ?><br><?php endif; ?>
                        <?php if ($supplier['phone']): ?><?= e($supplier['phone']) ?><?php endif; ?>
                    <?php else: ?>
                        <span style="color:var(--text-muted)">Walk-in Customer</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Items table -->
    <div class="card">
        <div class="table-wrap" style="border:none;border-radius:0">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Item</th>
                        <th>Color/Size</th>
                        <th>ID</th>
                        <th style="text-align:right">Qty</th>
                        <th style="text-align:right">Price</th>
                        <th style="text-align:right">Disc%</th>
                        <th style="text-align:right">Total</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($items as $n => $item):
                    // Get product code for ID column
                    $productCode = '';
                    if ($item['product_id']) {
                        $prod = \App\Helpers\Database::row('SELECT product_code FROM products WHERE id=?', [$item['product_id']]);
                        $productCode = $prod['product_code'] ?? '';
                    }
                ?>
                <tr>
                    <td class="td-muted"><?= $n+1 ?></td>
                    <td style="font-weight:500"><?= e($item['description']) ?></td>
                    <td class="td-muted"><?= $item['variant'] ? e($item['variant']) : '—' ?></td>
                    <td>
                        <?php if ($productCode): ?>
                        <span style="font-family:monospace;font-size:11px;background:var(--bg);padding:1px 5px;border-radius:4px;border:1px solid var(--border)">
                            <?= e($productCode) ?>
                        </span>
                        <?php else: ?>—<?php endif; ?>
                    </td>
                    <td style="text-align:right" class="td-muted">
                        <?= rtrim(rtrim(number_format($item['qty'],3),'0'),'.') ?>
                    </td>
                    <td style="text-align:right"><?= $sym.number_format($item['unit_price'],0) ?></td>
                    <td style="text-align:right" class="td-muted">
                        <?= $item['discount_pct']>0 ? $item['discount_pct'].'%' : '—' ?>
                    </td>
                    <td style="text-align:right;font-weight:600"><?= $sym.number_format($item['line_total'],0) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Totals -->
        <div style="display:flex;justify-content:flex-end;margin-top:16px">
            <div style="width:260px;font-size:13px;display:flex;flex-direction:column;gap:5px">
                <div style="display:flex;justify-content:space-between">
                    <span style="color:var(--text-muted)">Subtotal</span>
                    <span><?= $sym.number_format($invoice['subtotal'],0) ?></span>
                </div>
                <?php if ($invoice['discount']>0): ?>
                <div style="display:flex;justify-content:space-between">
                    <span style="color:var(--text-muted)">Discount</span>
                    <span style="color:var(--red)">− <?= $sym.number_format($invoice['discount'],0) ?></span>
                </div>
                <?php endif; ?>
                <?php if (($invoice['points_discount']??0)>0): ?>
                <div style="display:flex;justify-content:space-between">
                    <span style="color:var(--text-muted)">Points</span>
                    <span style="color:var(--red)">− <?= $sym.number_format($invoice['points_discount'],0) ?></span>
                </div>
                <?php endif; ?>
                <?php if (($invoice['delivery_charge']??0)>0): ?>
                <div style="display:flex;justify-content:space-between">
                    <span style="color:var(--text-muted)">Delivery</span>
                    <span><?= $sym.number_format($invoice['delivery_charge'],0) ?></span>
                </div>
                <?php endif; ?>
                <?php if (($invoice['rounding']??0)>0): ?>
                <div style="display:flex;justify-content:space-between">
                    <span style="color:var(--text-muted)">Rounding</span>
                    <span style="color:var(--red)">− <?= $sym.number_format($invoice['rounding'],0) ?></span>
                </div>
                <?php endif; ?>
                <?php if ($invoice['tax']>0): ?>
                <div style="display:flex;justify-content:space-between">
                    <span style="color:var(--text-muted)">Tax</span>
                    <span><?= $sym.number_format($invoice['tax'],0) ?></span>
                </div>
                <?php endif; ?>
                <div style="display:flex;justify-content:space-between;border-top:2px solid var(--border);padding-top:8px;font-size:16px;font-weight:600">
                    <span>Grand Total</span>
                    <span style="color:var(--brand)"><?= $sym.number_format($invoice['total'],0) ?></span>
                </div>
                <div style="display:flex;justify-content:space-between">
                    <span style="color:var(--text-muted)">Paid</span>
                    <span style="color:var(--green)"><?= $sym.number_format($invoice['paid'],0) ?></span>
                </div>
                <div style="display:flex;justify-content:space-between;font-weight:600">
                    <span>Balance Due</span>
                    <span style="color:<?= $due>0?'var(--red)':'var(--green)' ?>"><?= $sym.number_format($due,0) ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Delivery + payment -->
    <?php if ($invoice['delivery_method'] || $invoice['payment_method']): ?>
    <div class="card">
        <div style="display:flex;gap:24px;font-size:13px">
            <?php if ($invoice['delivery_method']): ?>
            <div><span style="color:var(--text-muted)">Delivery:</span> <strong><?= e($invoice['delivery_method']) ?></strong></div>
            <?php endif; ?>
            <?php if ($invoice['payment_method']): ?>
            <div><span style="color:var(--text-muted)">Payment:</span> <strong><?= e($invoice['payment_method']) ?></strong></div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Notes -->
    <?php
    $noteCustomer = $invoice['note_customer'] ?? $invoice['notes'] ?? '';
    $noteSeller   = $invoice['note_seller']   ?? '';
    if ($noteCustomer || $noteSeller):
    ?>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
        <?php if ($noteCustomer): ?>
        <div class="card">
            <p class="card-title">Customer Note</p>
            <p style="font-size:13px;color:var(--text-muted)"><?= nl2br(e($noteCustomer)) ?></p>
        </div>
        <?php endif; ?>
        <?php if ($noteSeller): ?>
        <div class="card">
            <p class="card-title">Seller Note</p>
            <p style="font-size:13px;color:var(--text-muted)"><?= nl2br(e($noteSeller)) ?></p>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Attachments -->
    <?php if (!empty($attachments)): ?>
    <div class="card">
        <p class="card-title">Attachments</p>
        <div style="display:flex;flex-direction:column;gap:8px">
            <?php foreach ($attachments as $att): ?>
            <div style="display:flex;align-items:center;justify-content:space-between;padding:8px 12px;background:var(--bg);border-radius:8px;border:1px solid var(--border)">
                <div style="font-size:13px">
                    📎 <?= e($att['filename']) ?>
                    <span style="color:var(--text-muted);font-size:11px;margin-left:8px">
                        <?= number_format($att['size']/1024,1) ?> KB
                    </span>
                </div>
                <a href="<?= asset('uploads/'.$att['path']) ?>" target="_blank"
                   class="btn btn-sm btn-secondary">View</a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Add attachment form (purchase invoices) -->
    <?php if ($invoice['type'] === 'purchase'): ?>
    <div class="card">
        <p class="card-title">Add Attachment</p>
        <form method="POST" action="/books/<?= $book['id'] ?>/invoices/<?= $invoice['id'] ?>/attachment"
              enctype="multipart/form-data" style="display:flex;gap:10px;align-items:flex-end">
            <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
            <div class="form-group" style="flex:1;margin:0">
                <input type="file" name="attachment" accept=".pdf,.jpg,.jpeg,.png,.webp"
                       style="width:100%">
            </div>
            <button type="submit" class="btn btn-secondary">Upload</button>
        </form>
    </div>
    <?php endif; ?>

</div><!-- end LEFT -->

<!-- RIGHT: payment panel -->
<div style="display:flex;flex-direction:column;gap:12px">
    <div class="card">
        <p class="card-title">Payment</p>
        <div style="display:flex;flex-direction:column;gap:10px">
            <div class="stat-card" style="border:none;padding:0">
                <div class="stat-label">Total</div>
                <div class="stat-value brand" style="font-size:20px"><?= $sym.number_format($invoice['total'],0) ?></div>
            </div>
            <div style="display:flex;gap:10px">
                <div style="flex:1;background:var(--green-bg);border-radius:8px;padding:10px">
                    <div style="font-size:11px;color:var(--green);font-weight:600;margin-bottom:2px">PAID</div>
                    <div style="font-size:15px;font-weight:600;color:var(--green)"><?= $sym.number_format($invoice['paid'],0) ?></div>
                </div>
                <div style="flex:1;background:<?= $due>0?'var(--red-bg)':'var(--green-bg)' ?>;border-radius:8px;padding:10px">
                    <div style="font-size:11px;color:<?= $due>0?'var(--red)':'var(--green)' ?>;font-weight:600;margin-bottom:2px">DUE</div>
                    <div style="font-size:15px;font-weight:600;color:<?= $due>0?'var(--red)':'var(--green)' ?>"><?= $sym.number_format($due,0) ?></div>
                </div>
            </div>
            <?php if ($due>0 && $invoice['status']!=='cancelled'): ?>
            <button class="btn btn-primary" style="width:100%" data-modal="paymentModal">+ Record Payment</button>
            <?php else: ?>
            <div style="text-align:center;font-size:13px;color:var(--green);font-weight:500">✓ Fully Paid</div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <p class="card-title">Details</p>
        <div style="font-size:13px;display:flex;flex-direction:column;gap:6px">
            <div><span style="color:var(--text-muted)">Invoice:</span> <strong><?= e($invoice['invoice_no']) ?></strong></div>
            <div><span style="color:var(--text-muted)">Date:</span> <?= format_date($invoice['date']) ?></div>
            <?php if ($invoice['due_date']): ?>
            <div><span style="color:var(--text-muted)">Due:</span> <?= format_date($invoice['due_date']) ?></div>
            <?php endif; ?>
            <div><span style="color:var(--text-muted)">Type:</span> <?= ucfirst($invoice['type']) ?></div>
            <div><span style="color:var(--text-muted)">Currency:</span> <?= e($invoice['currency_code']??'BDT') ?> (<?= e($sym) ?>)</div>
            <div><span style="color:var(--text-muted)">Status:</span> <span class="badge <?= $sc ?>"><?= $sl ?></span></div>
        </div>
    </div>

    <?php if ($customer && $customer['points']>0): ?>
    <div class="card">
        <p class="card-title">Loyalty Points</p>
        <div style="font-size:22px;font-weight:700;color:var(--accent)"><?= $customer['points'] ?></div>
        <div style="font-size:12px;color:var(--text-muted);margin-top:2px">Available</div>
    </div>
    <?php endif; ?>

    <!-- Public link -->
    <?php if ($invoice['public_token'] ?? ''): ?>
    <div class="card">
        <p class="card-title">Public Link</p>
        <p style="font-size:12px;color:var(--text-muted);margin-bottom:8px">Share with your customer — no login needed</p>
        <a href="<?= asset('invoice/'.$invoice['public_token']) ?>" target="_blank"
           class="btn btn-sm btn-secondary" style="word-break:break-all">🔗 View Public Invoice</a>
    </div>
    <?php endif; ?>
</div>

</div><!-- end grid -->

<!-- PAYMENT MODAL -->
<div class="modal-backdrop" id="paymentModal">
    <div class="modal">
        <div class="modal-title">Record Payment</div>
        <form method="POST" action="/books/<?= $book['id'] ?>/invoices/<?= $invoice['id'] ?>/payment">
            <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
            <div class="form-grid" style="gap:12px">
                <div class="form-group full">
                    <label>Amount — Due: <?= $sym.number_format($due,0) ?></label>
                    <input type="number" name="amount" value="<?= $due ?>"
                           min="0.01" step="0.01" max="<?= $due ?>" required>
                </div>
                <div class="form-group full">
                    <label>Payment Method</label>
                    <select name="method">
                        <?php foreach ($paymentMethodOpts as $pm): ?>
                        <option value="<?= e($pm['label']) ?>"><?= e($pm['label']) ?></option>
                        <?php endforeach; ?>
                        <?php if (empty($paymentMethodOpts)): ?>
                        <option value="Cash">Cash</option>
                        <option value="bKash">bKash</option>
                        <option value="Bank Transfer">Bank Transfer</option>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="form-group full">
                    <label>Note (optional)</label>
                    <input type="text" name="note" placeholder="e.g. Cash received">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-close-modal>Cancel</button>
                <button type="submit" class="btn btn-primary">Save Payment</button>
            </div>
        </form>
    </div>
</div>

<?php $content = ob_get_clean(); require BASE_PATH . '/views/partials/layout.php'; ?>

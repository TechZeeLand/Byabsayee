<?php
$pageTitle = e($invoice['invoice_no']) . ' — Byabsayee';
$due       = $invoice['total'] - $invoice['paid'];
$statusColors = [
    'draft'     => ['badge-gray',  'Draft'],
    'sent'      => ['badge-blue',  'Sent'],
    'partial'   => ['badge-amber', 'Partial'],
    'paid'      => ['badge-green', 'Paid'],
    'overdue'   => ['badge-red',   'Overdue'],
    'cancelled' => ['badge-gray',  'Cancelled'],
];
[$sc, $sl] = $statusColors[$invoice['status']] ?? ['badge-gray','Unknown'];
ob_start();
?>

<div class="page-header">
    <div class="page-header-left">
        <div class="breadcrumb">
            <a href="/dashboard">Dashboard</a> <span>›</span>
            <a href="/books/<?= $book['id'] ?>"><?= e($book['name']) ?></a> <span>›</span>
            <a href="/books/<?= $book['id'] ?>/invoices">Invoices</a> <span>›</span>
            <span><?= e($invoice['invoice_no']) ?></span>
        </div>
        <h1 style="display:flex;align-items:center;gap:10px">
            <?= e($invoice['invoice_no']) ?>
            <span class="badge <?= $sc ?>"><?= $sl ?></span>
        </h1>
        <p><?= ucfirst($invoice['type']) ?> invoice · <?= format_date($invoice['date']) ?></p>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
        <!-- PDF link (Phase 4) -->
        <a href="/books/<?= $book['id'] ?>/invoices/<?= $invoice['id'] ?>/pdf"
           class="btn btn-secondary" target="_blank">
            📄 Download PDF
        </a>
        <?php if ($invoice['status'] === 'draft'): ?>
        <form method="POST" action="/books/<?= $book['id'] ?>/invoices/<?= $invoice['id'] ?>/sent">
            <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
            <button class="btn btn-secondary">Mark as Sent</button>
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

<div style="display:grid;grid-template-columns:2fr 1fr;gap:20px;align-items:start">

    <!-- LEFT: invoice body -->
    <div style="display:flex;flex-direction:column;gap:16px">

        <!-- Business + party info -->
        <div class="card">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">
                <div>
                    <p style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);margin-bottom:8px">From</p>
                    <div style="font-size:14px;line-height:1.7">
                        <strong><?= e($details['business_name'] ?? $book['name']) ?></strong><br>
                        <?php if ($details['phone']): ?><?= e($details['phone']) ?><br><?php endif; ?>
                        <?php if ($details['address']): ?><?= nl2br(e($details['address'])) ?><br><?php endif; ?>
                    </div>
                </div>
                <div>
                    <p style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);margin-bottom:8px">
                        <?= $invoice['type'] === 'sale' ? 'Bill To' : 'From Supplier' ?>
                    </p>
                    <div style="font-size:14px;line-height:1.7">
                        <?php if ($customer): ?>
                            <strong><?= e($customer['name']) ?></strong><br>
                            <?php if ($customer['phone']): ?><?= e($customer['phone']) ?><br><?php endif; ?>
                            <?php if ($customer['address']): ?><?= nl2br(e($customer['address'])) ?><?php endif; ?>
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

        <!-- Line items -->
        <div class="card">
            <div class="table-wrap" style="border:none;border-radius:0">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Item</th>
                            <th style="text-align:right">Qty</th>
                            <th style="text-align:right">Price</th>
                            <th style="text-align:right">Disc%</th>
                            <th style="text-align:right">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($items as $n => $item): ?>
                    <tr>
                        <td class="td-muted"><?= $n + 1 ?></td>
                        <td style="font-weight:500"><?= e($item['description']) ?></td>
                        <td style="text-align:right" class="td-muted"><?= rtrim(rtrim(number_format($item['qty'],3),'0'),'.') ?></td>
                        <td style="text-align:right"><?= format_money($item['unit_price']) ?></td>
                        <td style="text-align:right" class="td-muted"><?= $item['discount_pct'] > 0 ? $item['discount_pct'].'%' : '—' ?></td>
                        <td style="text-align:right;font-weight:600"><?= format_money($item['line_total']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Totals -->
            <div style="display:flex;justify-content:flex-end;margin-top:16px">
                <div style="width:260px;font-size:14px;display:flex;flex-direction:column;gap:6px">
                    <div style="display:flex;justify-content:space-between">
                        <span style="color:var(--text-muted)">Subtotal</span>
                        <span><?= format_money($invoice['subtotal']) ?></span>
                    </div>
                    <?php if ($invoice['discount'] > 0): ?>
                    <div style="display:flex;justify-content:space-between">
                        <span style="color:var(--text-muted)">Discount</span>
                        <span style="color:var(--red)">− <?= format_money($invoice['discount']) ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if ($invoice['tax'] > 0): ?>
                    <div style="display:flex;justify-content:space-between">
                        <span style="color:var(--text-muted)">Tax</span>
                        <span><?= format_money($invoice['tax']) ?></span>
                    </div>
                    <?php endif; ?>
                    <div style="display:flex;justify-content:space-between;border-top:2px solid var(--border);padding-top:8px;font-size:16px;font-weight:600">
                        <span>Total</span>
                        <span style="color:var(--brand)"><?= format_money($invoice['total']) ?></span>
                    </div>
                    <div style="display:flex;justify-content:space-between">
                        <span style="color:var(--text-muted)">Paid</span>
                        <span style="color:var(--green)"><?= format_money($invoice['paid']) ?></span>
                    </div>
                    <div style="display:flex;justify-content:space-between;font-weight:600">
                        <span>Balance Due</span>
                        <span style="color:<?= $due > 0 ? 'var(--red)' : 'var(--green)' ?>"><?= format_money($due) ?></span>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($invoice['notes']): ?>
        <div class="card">
            <p class="card-title">Notes</p>
            <p style="font-size:13px;color:var(--text-muted)"><?= nl2br(e($invoice['notes'])) ?></p>
        </div>
        <?php endif; ?>

    </div>

    <!-- RIGHT: payment status + info -->
    <div style="display:flex;flex-direction:column;gap:12px">

        <!-- Payment summary card -->
        <div class="card">
            <p class="card-title">Payment</p>
            <div style="display:flex;flex-direction:column;gap:10px">
                <div class="stat-card" style="border:none;padding:0">
                    <div class="stat-label">Total</div>
                    <div class="stat-value brand" style="font-size:20px"><?= format_money($invoice['total']) ?></div>
                </div>
                <div style="display:flex;gap:10px">
                    <div style="flex:1;background:var(--green-bg);border-radius:8px;padding:10px">
                        <div style="font-size:11px;color:var(--green);font-weight:600;margin-bottom:2px">PAID</div>
                        <div style="font-size:15px;font-weight:600;color:var(--green)"><?= format_money($invoice['paid']) ?></div>
                    </div>
                    <div style="flex:1;background:<?= $due>0?'var(--red-bg)':'var(--green-bg)' ?>;border-radius:8px;padding:10px">
                        <div style="font-size:11px;color:<?= $due>0?'var(--red)':'var(--green)' ?>;font-weight:600;margin-bottom:2px">DUE</div>
                        <div style="font-size:15px;font-weight:600;color:<?= $due>0?'var(--red)':'var(--green)' ?>"><?= format_money($due) ?></div>
                    </div>
                </div>
                <?php if ($due > 0 && $invoice['status'] !== 'cancelled'): ?>
                <button class="btn btn-primary" style="width:100%" data-modal="paymentModal">
                    + Record Payment
                </button>
                <?php else: ?>
                <div style="text-align:center;font-size:13px;color:var(--green);font-weight:500">✓ Fully Paid</div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Invoice meta -->
        <div class="card">
            <p class="card-title">Details</p>
            <div style="font-size:13px;display:flex;flex-direction:column;gap:6px">
                <div><span style="color:var(--text-muted)">Invoice:</span> <strong><?= e($invoice['invoice_no']) ?></strong></div>
                <div><span style="color:var(--text-muted)">Date:</span> <?= format_date($invoice['date']) ?></div>
                <?php if ($invoice['due_date']): ?>
                <div><span style="color:var(--text-muted)">Due:</span> <?= format_date($invoice['due_date']) ?></div>
                <?php endif; ?>
                <div><span style="color:var(--text-muted)">Type:</span> <?= ucfirst($invoice['type']) ?></div>
                <div><span style="color:var(--text-muted)">Status:</span> <span class="badge <?= $sc ?>"><?= $sl ?></span></div>
            </div>
        </div>

        <?php if ($customer && $customer['points'] > 0): ?>
        <div class="card">
            <p class="card-title">Customer Points</p>
            <div style="font-size:22px;font-weight:600;color:var(--accent)"><?= $customer['points'] ?> pts</div>
            <div style="font-size:12px;color:var(--text-muted);margin-top:2px">Available loyalty points</div>
        </div>
        <?php endif; ?>

    </div>
</div>

<!-- PAYMENT MODAL -->
<div class="modal-backdrop" id="paymentModal">
    <div class="modal">
        <div class="modal-title">Record Payment</div>
        <form method="POST" action="/books/<?= $book['id'] ?>/invoices/<?= $invoice['id'] ?>/payment">
            <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
            <div class="form-grid" style="gap:12px">
                <div class="form-group full">
                    <label>Amount (৳) — Balance due: <?= format_money($due) ?></label>
                    <input type="number" name="amount" value="<?= $due ?>"
                           min="0.01" step="0.01" max="<?= $due ?>" required>
                </div>
                <div class="form-group full">
                    <label>Payment Method</label>
                    <select name="method">
                        <option value="cash">Cash</option>
                        <option value="card">Card</option>
                        <option value="bkash">bKash</option>
                        <option value="nagad">Nagad</option>
                        <option value="rocket">Rocket</option>
                        <option value="bank_transfer">Bank Transfer</option>
                        <option value="cheque">Cheque</option>
                        <option value="credit">Credit</option>
                    </select>
                </div>
                <div class="form-group full">
                    <label>Note (optional)</label>
                    <input type="text" name="note" placeholder="e.g. Received from owner">
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

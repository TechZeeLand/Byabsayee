<?php
// =============================================================================
// app/Services/InvoicePdfService.php
// Matches the invoice design from the screenshot exactly:
// - Top-left: "Invoice" bold + QR code. Top-right: Logo
// - Invoice No left, Date right, thick lines above and below
// - Bill To (left) | Bill From (right), each with a Note field
// - Black (theme-colored) table header, NO/DESCRIPTION/COLOR/SIZE/ID/QTY/UNIT PRICE/TOTAL
// - Delivery Method + Payment Method bottom-left
// - Totals block bottom-right: SUBTOTAL/DISCOUNT/POINTS/DELIVERY/GRAND TOTAL with dashes
// - Signature lines at very bottom, Buyer left Seller right
// - Italic colored thank-you, small grey generated-by footer
// =============================================================================

namespace App\Services;

class InvoicePdfService
{
    public function generate(
        array  $book,
        array  $invoice,
        array  $items,
        ?array $customer,
        ?array $supplier,
        ?array $details,
        ?array $creator
    ): void {
        if (!class_exists('\Mpdf\Mpdf')) {
            die('mPDF not installed. Run: docker exec byabsayee composer require mpdf/mpdf --working-dir=/Sites/byabsayee');
        }

        // ── Theme color (replaces black everywhere) ─────────────────────────
        $theme = $invoice['theme_color'] ?? $book['theme_color'] ?? '#000000';

        // ── Names ────────────────────────────────────────────────────────────
        $businessName  = $details['business_name'] ?? $book['name'];
        $businessPhone = $book['phone']  ?? $details['phone']  ?? '';
        $businessEmail = $book['email']  ?? $details['email']  ?? '';
        $businessAddr  = $book['address'] ?? $details['address'] ?? '';
        $businessAddr  = str_replace("\n", ', ', trim($businessAddr));

        $party      = $customer ?? $supplier ?? null;
        $partyName  = $party['name']    ?? 'Walk-in Customer';
        $partyAddr  = $party['address'] ?? '';
        $partyAddr  = str_replace("\n", ', ', trim($partyAddr));
        $partyPhone = $party['phone']   ?? '';
        $partyEmail = $party['email']   ?? '';

        $creatorName = $creator['name'] ?? 'Staff';
        $invoiceNo   = $invoice['invoice_no'];
        $invoiceDate = date('d|m|Y', strtotime($invoice['date']));

        // ── Notes ────────────────────────────────────────────────────────────
        $noteCustomer = $invoice['note_customer'] ?? $invoice['notes'] ?? '';
        $noteSeller   = $invoice['note_seller']   ?? '';

        // ── Logo HTML ─────────────────────────────────────────────────────────
        $logoHtml = '';
        if (!empty($book['logo'])) {
            $logoPath = config('upload.path') . '/' . $book['logo'];
            if (file_exists($logoPath)) {
                $logoHtml = '<img src="' . $logoPath . '" style="max-height:70px;max-width:180px">';
            }
        }
        if (!$logoHtml) {
            $logoHtml = '<span style="font-size:28px;font-style:italic;color:#ccc;font-family:serif">Logo</span>';
        }

        // ── QR code URL ───────────────────────────────────────────────────────
        $invoiceUrl = config('url') . '/invoice/' . $invoice['id'];
        $qrUrl      = 'https://api.qrserver.com/v1/create-qr-code/?size=90x90&data=' . urlencode($invoiceUrl);

        // ── Totals ────────────────────────────────────────────────────────────
        $subtotal       = (float)$invoice['subtotal'];
        $discount       = (float)$invoice['discount'];
        $pointsDiscount = (float)($invoice['points_discount'] ?? 0);
        $deliveryCharge = (float)($invoice['delivery_charge'] ?? 0);
        $tax            = (float)$invoice['tax'];
        $total          = (float)$invoice['total'];

        $deliveryMethod = $invoice['delivery_method'] ?? '';
        $paymentMethod  = $invoice['payment_method']  ?? '';

        // ── Item rows ─────────────────────────────────────────────────────────
        $itemRowsHtml = '';
        foreach ($items as $n => $item) {
            $qty     = rtrim(rtrim(number_format((float)$item['qty'], 3, '.', ''), '0'), '.');
            $variant = $this->e($item['variant'] ?? '');
            $sku     = $this->e($item['sku'] ?? '');
            $bg      = ($n % 2 === 0) ? '#ffffff' : '#f9f9f9';
            $itemRowsHtml .= '
            <tr style="background:'.$bg.'">
                <td style="border:1px solid #ddd;padding:7px 8px;text-align:center;font-size:12px">'.($n+1).'</td>
                <td style="border:1px solid #ddd;padding:7px 8px;font-size:13px">'.$this->e($item['description']).'</td>
                <td style="border:1px solid #ddd;padding:7px 8px;text-align:center;font-size:12px">'.$variant.'</td>
                <td style="border:1px solid #ddd;padding:7px 8px;text-align:center;font-size:12px">'.$sku.'</td>
                <td style="border:1px solid #ddd;padding:7px 8px;text-align:center;font-size:12px">'.$qty.'</td>
                <td style="border:1px solid #ddd;padding:7px 8px;text-align:right;font-size:12px">'.number_format((float)$item['unit_price'], 0).'</td>
                <td style="border:1px solid #ddd;padding:7px 8px;text-align:right;font-size:12px;font-weight:600">'.number_format((float)$item['line_total'], 0).'</td>
            </tr>';
        }

        // ── Totals rows (right side) ──────────────────────────────────────────
        // Format: label | dashes | value
        $dash = '<span style="display:inline-block;width:60px;border-bottom:1px solid #999;vertical-align:middle;margin:0 6px"></span>';

        $totalsRows = '
        <tr>
            <td style="padding:4px 0;font-size:12px;font-weight:600;letter-spacing:.5px">SUBTOTAL</td>
            <td style="padding:4px 6px;text-align:center">'.$dash.'</td>
            <td style="padding:4px 0;text-align:right;font-size:12px">'.number_format($subtotal, 0).'৳</td>
        </tr>';

        if ($discount > 0 || true) { // always show
            $discLabel = $discount > 0 ? '['.($invoice['discount_percent'] ?? '').'%]('.number_format($discount,0).')৳' : '-------';
            $totalsRows .= '
        <tr>
            <td style="padding:4px 0;font-size:12px;font-weight:600;letter-spacing:.5px">DISCOUNT</td>
            <td style="padding:4px 6px;text-align:center">'.$dash.'</td>
            <td style="padding:4px 0;text-align:right;font-size:12px">'.($discount > 0 ? '['.number_format($discount/($subtotal>0?$subtotal:1)*100,0).'%]('.number_format($discount,0).')৳' : '-------').'</td>
        </tr>';
        }

        $totalsRows .= '
        <tr>
            <td style="padding:4px 0;font-size:12px;font-weight:600;letter-spacing:.5px">POINTS</td>
            <td style="padding:4px 6px;text-align:center">'.$dash.'</td>
            <td style="padding:4px 0;text-align:right;font-size:12px">'.($pointsDiscount > 0 ? '('.number_format($pointsDiscount,0).')৳' : '-------').'</td>
        </tr>
        <tr>
            <td style="padding:4px 0;font-size:12px;font-weight:600;letter-spacing:.5px">DELIVERY</td>
            <td style="padding:4px 6px;text-align:center">'.$dash.'</td>
            <td style="padding:4px 0;text-align:right;font-size:12px">'.($deliveryCharge > 0 ? number_format($deliveryCharge,0).'৳' : '-------').'</td>
        </tr>
        <tr>
            <td style="padding:6px 0 4px;font-size:12px;font-weight:700;letter-spacing:.5px;border-top:2px solid '.$theme.'">GRAND TOTAL</td>
            <td style="padding:6px 6px 4px;text-align:center;border-top:2px solid '.$theme.'">'.$dash.'</td>
            <td style="padding:6px 0 4px;text-align:right;font-size:13px;font-weight:700;border-top:2px solid '.$theme.'">'.number_format($total,0).'৳</td>
        </tr>';

        // ── Full HTML ─────────────────────────────────────────────────────────
        $html = '<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
* { box-sizing:border-box; margin:0; padding:0; }
body { font-family:"DejaVu Sans", sans-serif; font-size:13px; color:#1a1a1a; background:#fff; }
table { border-collapse:collapse; }
</style>
</head>
<body style="padding:0">

<!-- ══ TOP HEADER: Invoice + QR left, Logo right ══ -->
<table style="width:100%;margin-bottom:10px">
    <tr>
        <td style="vertical-align:top;width:60%">
            <table>
                <tr>
                    <td style="vertical-align:middle;padding-right:16px">
                        <span style="font-size:40px;font-weight:900;letter-spacing:-2px;color:#1a1a1a">Invoice</span>
                    </td>
                    <td style="vertical-align:middle">
                        <img src="'.$qrUrl.'" style="width:80px;height:80px">
                    </td>
                </tr>
            </table>
        </td>
        <td style="vertical-align:top;text-align:right;width:40%">
            '.$logoHtml.'
        </td>
    </tr>
</table>

<!-- ══ INVOICE NO + DATE with thick lines ══ -->
<div style="border-top:2.5px solid #1a1a1a;border-bottom:2.5px solid #1a1a1a;padding:7px 2px;margin-bottom:0">
    <table style="width:100%">
        <tr>
            <td style="font-size:13px;font-weight:700">Invoice No: '.$this->e($invoiceNo).'</td>
            <td style="text-align:right;font-size:13px;font-weight:700">Date: '.$invoiceDate.'</td>
        </tr>
    </table>
</div>

<!-- ══ BILL TO | BILL FROM ══ -->
<table style="width:100%;border-bottom:1px solid #ccc;margin-bottom:0">
    <tr>
        <!-- Bill To -->
        <td style="width:50%;vertical-align:top;padding:12px 10px 12px 2px;border-right:1px solid #ccc">
            <div style="font-size:12px;font-weight:700;margin-bottom:8px">BILL TO -</div>
            '.($party ? '
            <div style="font-size:13px;line-height:1.8">
                '.$this->e($partyName).'<br>
                '.($partyAddr  ? $this->e($partyAddr).'<br>'  : '').'
                '.($partyPhone ? $this->e($partyPhone).'<br>' : '').'
                '.($partyEmail ? $this->e($partyEmail)         : '').'
            </div>' : '<div style="font-size:13px;color:#888">Walk-in Customer</div>').'
            <div style="margin-top:12px;font-size:13px;font-weight:700">Note: <span style="font-weight:400">'.($noteCustomer ? $this->e($noteCustomer) : '').'</span></div>
        </td>
        <!-- Bill From -->
        <td style="width:50%;vertical-align:top;padding:12px 2px 12px 14px">
            <div style="font-size:12px;font-weight:700;margin-bottom:8px">BILL FROM -</div>
            <div style="font-size:13px;line-height:1.8">
                '.$this->e($businessName).'<br>
                '.($businessAddr  ? $this->e($businessAddr).'<br>'  : '').'
                '.($businessPhone ? $this->e($businessPhone).'<br>' : '').'
                '.($businessEmail ? $this->e($businessEmail)         : '').'
            </div>
            <div style="margin-top:12px;font-size:13px;font-weight:700">Note: <span style="font-weight:400">'.($noteSeller ? $this->e($noteSeller) : '').'</span></div>
        </td>
    </tr>
</table>

<!-- ══ ITEMS TABLE ══ -->
<table style="width:100%;margin-top:0;margin-bottom:0;border-collapse:collapse">
    <thead>
        <tr style="background:'.$theme.';color:#ffffff">
            <th style="padding:9px 8px;font-size:11px;font-weight:700;text-align:center;width:32px;border:1px solid '.$theme.'">NO</th>
            <th style="padding:9px 8px;font-size:11px;font-weight:700;text-align:center;border:1px solid '.$theme.'">DESCRIPTION</th>
            <th style="padding:9px 8px;font-size:11px;font-weight:700;text-align:center;width:90px;border:1px solid '.$theme.'">COLOR/SIZE</th>
            <th style="padding:9px 8px;font-size:11px;font-weight:700;text-align:center;width:55px;border:1px solid '.$theme.'">ID</th>
            <th style="padding:9px 8px;font-size:11px;font-weight:700;text-align:center;width:45px;border:1px solid '.$theme.'">QTY</th>
            <th style="padding:9px 8px;font-size:11px;font-weight:700;text-align:center;width:80px;border:1px solid '.$theme.'">UNIT PRICE</th>
            <th style="padding:9px 8px;font-size:11px;font-weight:700;text-align:center;width:70px;border:1px solid '.$theme.'">TOTAL</th>
        </tr>
    </thead>
    <tbody>
        '.$itemRowsHtml.'
    </tbody>
</table>

<!-- ══ BELOW TABLE: Delivery/Payment left, Totals right ══ -->
<table style="width:100%;margin-top:10px;margin-bottom:40px">
    <tr>
        <!-- Left: delivery + payment -->
        <td style="width:50%;vertical-align:top;padding-top:4px">
            '.($deliveryMethod ? '<div style="font-size:12px;margin-bottom:5px"><span style="font-weight:700">Delivery Method:</span>  '.$this->e($deliveryMethod).'</div>' : '').'
            '.($paymentMethod  ? '<div style="font-size:12px"><span style="font-weight:700">Payment Method:</span>  '.$this->e($paymentMethod).'</div>'  : '').'
        </td>
        <!-- Right: totals -->
        <td style="width:50%;vertical-align:top">
            <table style="width:100%">
                '.$totalsRows.'
            </table>
        </td>
    </tr>
</table>

<!-- ══ SIGNATURE LINES (pushed to bottom of page) ══ -->
<table style="width:100%;margin-top:160px;margin-bottom:12px">
    <tr>
        <td style="width:35%;border-top:1.5px solid #1a1a1a;padding-top:6px;font-size:12px;font-weight:700">
            Buyer ('.$this->e($partyName).')
        </td>
        <td style="width:30%"></td>
        <td style="width:35%;border-top:1.5px solid #1a1a1a;padding-top:6px;font-size:12px;font-weight:700;text-align:right">
            Seller ('.$this->e($creatorName).')
        </td>
    </tr>
</table>

<!-- ══ THANK YOU (italic, theme color) ══ -->
<div style="text-align:center;font-style:italic;color:'.$theme.';font-size:13px;margin-bottom:8px;border-top:2px solid '.$theme.';padding-top:8px">
    It was a pleasure doing business with you, we hope to hear from you soon!
</div>

<!-- ══ FOOTER ══ -->
<div style="text-align:center;font-size:10px;color:#888;border-top:1.5px solid #1a1a1a;padding-top:6px">
    This invoice was generated using Byabsayee (https://byabsayee.com)<br>
    by '.$this->e($creatorName).' on '.date('F jS, Y \a\t h:i A', strtotime($invoice['created_at'])).'
</div>

</body>
</html>';

        // ── mPDF output ───────────────────────────────────────────────────────
        $mpdf = new \Mpdf\Mpdf([
            'mode'          => 'utf-8',
            'format'        => 'A4',
            'margin_top'    => 12,
            'margin_bottom' => 12,
            'margin_left'   => 14,
            'margin_right'  => 14,
            'tempDir'       => '/tmp',
        ]);

        $mpdf->SetTitle('Invoice ' . $invoiceNo);
        $mpdf->WriteHTML($html);
        $mpdf->Output('Invoice-' . $invoiceNo . '.pdf', 'D');
    }

    private function e(string $s): string
    {
        return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

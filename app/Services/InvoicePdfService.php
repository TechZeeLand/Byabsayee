<?php
// =============================================================================
// app/Services/InvoicePdfService.php
// =============================================================================
// Generates a professional A4 invoice PDF using mPDF.
// Matches the template design: header with logo + business/customer info,
// itemised table with variant column, totals, QR code, signature lines.
// The theme color (set per book) replaces all accent/header colors.
// =============================================================================

namespace App\Services;

class InvoicePdfService
{
    public function generate(
        array $book,
        array $invoice,
        array $items,
        ?array $customer,
        ?array $supplier,
        ?array $details,
        ?array $creator
    ): void {
        if (!class_exists('\Mpdf\Mpdf')) {
            die('mPDF not installed. Run: docker exec byabsayee composer install --working-dir=/Sites/byabsayee');
        }

        $theme   = $invoice['theme_color'] ?? $book['theme_color'] ?? '#1a6b4a';
        $themeRgb= $this->hexToRgb($theme);
        $themeLight = $this->lighten($theme, 92); // very light bg for table header

        // Party info
        $party     = $customer ?? $supplier ?? null;
        $partyLabel= $invoice['type'] === 'sale' ? 'BILL TO' : 'BILL FROM';

        // Creator name
        $creatorName = $creator['name'] ?? 'Staff';

        // Build the QR code URL — points to the public invoice page
        $invoiceUrl = config('url') . '/invoice/' . $invoice['id'];
        $qrUrl      = 'https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=' . urlencode($invoiceUrl);

        // Logo path
        $logoHtml = '';
        if ($book['logo']) {
            $logoPath = config('upload.path') . '/' . $book['logo'];
            if (file_exists($logoPath)) {
                $logoHtml = '<img src="' . $logoPath . '" style="max-height:60px;max-width:160px;object-fit:contain">';
            }
        }

        $businessName = $details['business_name'] ?? $book['name'];
        $businessPhone= $book['phone'] ?? $details['phone'] ?? '';
        $businessEmail= $book['email'] ?? $details['email'] ?? '';
        $businessAddr = $book['address'] ?? $details['address'] ?? '';

        // Format invoice number and date
        $invoiceNo   = $invoice['invoice_no'];
        $invoiceDate = date('d|m|Y', strtotime($invoice['date']));
        $dueDate     = $invoice['due_date'] ? date('d|m|Y', strtotime($invoice['due_date'])) : '';

        // Totals
        $subtotal       = (float)$invoice['subtotal'];
        $discount       = (float)$invoice['discount'];
        $pointsDiscount = (float)($invoice['points_discount'] ?? 0);
        $deliveryCharge = (float)($invoice['delivery_charge'] ?? 0);
        $tax            = (float)$invoice['tax'];
        $total          = (float)$invoice['total'];
        $paid           = (float)$invoice['paid'];
        $due            = $total - $paid;

        $deliveryMethod = $invoice['delivery_method'] ?? '';
        $paymentMethod  = $invoice['payment_method']  ?? '';

        // Build items rows HTML
        $itemRows = '';
        foreach ($items as $n => $item) {
            $qty     = rtrim(rtrim(number_format((float)$item['qty'], 3), '0'), '.');
            $variant = $item['variant'] ?? '';
            $disc    = (float)$item['discount_pct'] > 0 ? $item['discount_pct'].'%' : '';
            $bgRow   = ($n % 2 === 0) ? '#ffffff' : '#f9fafb';

            $itemRows .= '
            <tr style="background:'.$bgRow.'">
                <td style="padding:8px 10px;border-bottom:1px solid #e5e7eb;text-align:center;color:#6b7280;font-size:12px">'.($n+1).'</td>
                <td style="padding:8px 10px;border-bottom:1px solid #e5e7eb;font-size:13px">'.htmlspecialchars($item['description']).'</td>
                <td style="padding:8px 10px;border-bottom:1px solid #e5e7eb;font-size:12px;color:#4b5563;text-align:center">'.htmlspecialchars($variant).'</td>
                <td style="padding:8px 10px;border-bottom:1px solid #e5e7eb;font-size:12px;color:#4b5563;text-align:center">'.htmlspecialchars($item['sku'] ?? '').'</td>
                <td style="padding:8px 10px;border-bottom:1px solid #e5e7eb;font-size:12px;text-align:right">'.htmlspecialchars($qty).'</td>
                <td style="padding:8px 10px;border-bottom:1px solid #e5e7eb;font-size:12px;text-align:right">'.number_format((float)$item['unit_price'],2).'</td>
                <td style="padding:8px 10px;border-bottom:1px solid #e5e7eb;font-size:12px;font-weight:600;text-align:right">'.number_format((float)$item['line_total'],2).'</td>
            </tr>';
        }

        // Build totals rows
        $totalsHtml = '
        <tr><td colspan="2" style="padding:6px 10px;text-align:right;color:#6b7280;font-size:13px">Subtotal</td>
            <td style="padding:6px 10px;text-align:right;font-size:13px">৳'.number_format($subtotal,2).'</td></tr>';

        if ($discount > 0) {
            $totalsHtml .= '
        <tr><td colspan="2" style="padding:4px 10px;text-align:right;color:#6b7280;font-size:13px">Discount</td>
            <td style="padding:4px 10px;text-align:right;color:#dc2626;font-size:13px">− ৳'.number_format($discount,2).'</td></tr>';
        }
        if ($pointsDiscount > 0) {
            $totalsHtml .= '
        <tr><td colspan="2" style="padding:4px 10px;text-align:right;color:#6b7280;font-size:13px">Points</td>
            <td style="padding:4px 10px;text-align:right;color:#dc2626;font-size:13px">− ৳'.number_format($pointsDiscount,2).'</td></tr>';
        }
        if ($deliveryCharge > 0) {
            $totalsHtml .= '
        <tr><td colspan="2" style="padding:4px 10px;text-align:right;color:#6b7280;font-size:13px">Delivery</td>
            <td style="padding:4px 10px;text-align:right;font-size:13px">৳'.number_format($deliveryCharge,2).'</td></tr>';
        }
        if ($tax > 0) {
            $totalsHtml .= '
        <tr><td colspan="2" style="padding:4px 10px;text-align:right;color:#6b7280;font-size:13px">Tax</td>
            <td style="padding:4px 10px;text-align:right;font-size:13px">৳'.number_format($tax,2).'</td></tr>';
        }

        $totalsHtml .= '
        <tr style="background:'.$themeLight.'">
            <td colspan="2" style="padding:10px;text-align:right;font-weight:700;font-size:14px;color:'.$theme.'">GRAND TOTAL</td>
            <td style="padding:10px;text-align:right;font-weight:700;font-size:15px;color:'.$theme.'">৳'.number_format($total,2).'</td>
        </tr>';

        // Buyer / Seller names for signature
        $buyerName  = $customer['name'] ?? ($supplier['name'] ?? 'Customer');
        $sellerName = $creatorName;

        // Full HTML for the PDF
        $html = '
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
    * { box-sizing:border-box; margin:0; padding:0; }
    body { font-family: "DejaVu Sans", sans-serif; font-size:13px; color:#111827; background:#fff; }
    table { border-collapse:collapse; width:100%; }
</style>
</head>
<body>

<!-- ===== HEADER ===== -->
<table style="width:100%;margin-bottom:20px">
    <tr>
        <td style="width:50%;vertical-align:top">
            '.$logoHtml.'
            <div style="margin-top:8px">
                <div style="font-size:18px;font-weight:700;color:'.$theme.'">'.$this->e($businessName).'</div>
                '.($businessAddr  ? '<div style="font-size:12px;color:#6b7280;margin-top:3px">'.$this->e($businessAddr).'</div>' : '').'
                '.($businessPhone ? '<div style="font-size:12px;color:#6b7280">'.$this->e($businessPhone).'</div>' : '').'
                '.($businessEmail ? '<div style="font-size:12px;color:#6b7280">'.$this->e($businessEmail).'</div>' : '').'
            </div>
        </td>
        <td style="width:50%;text-align:right;vertical-align:top">
            <div style="font-size:28px;font-weight:700;color:'.$theme.';letter-spacing:-1px">Invoice</div>
            <div style="margin-top:8px;font-size:12px;color:#6b7280">
                <div><strong style="color:#374151">Invoice No:</strong> '.$this->e($invoiceNo).'</div>
                <div style="margin-top:3px"><strong style="color:#374151">Date:</strong> '.$invoiceDate.'</div>
                '.($dueDate ? '<div style="margin-top:3px"><strong style="color:#374151">Due Date:</strong> '.$dueDate.'</div>' : '').'
            </div>
        </td>
    </tr>
</table>

<!-- ===== BILL FROM / TO ===== -->
<table style="width:100%;margin-bottom:20px">
    <tr>
        <td style="width:48%;vertical-align:top;background:'.$themeLight.';border-left:4px solid '.$theme.';padding:12px 14px;border-radius:4px">
            <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:'.$theme.';margin-bottom:6px">Bill From</div>
            <div style="font-weight:600;font-size:13px">'.$this->e($businessName).'</div>
            '.($businessAddr  ? '<div style="font-size:12px;color:#4b5563;margin-top:2px">'.$this->e($businessAddr).'</div>' : '').'
            '.($businessPhone ? '<div style="font-size:12px;color:#4b5563">'.$this->e($businessPhone).'</div>' : '').'
        </td>
        <td style="width:4%"></td>
        <td style="width:48%;vertical-align:top;background:'.$themeLight.';border-left:4px solid '.$theme.';padding:12px 14px;border-radius:4px">
            <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:'.$theme.';margin-bottom:6px">'.$partyLabel.'</div>
            '.($party ? '
            <div style="font-weight:600;font-size:13px">'.$this->e($party['name']).'</div>
            '.($party['address'] ?? false ? '<div style="font-size:12px;color:#4b5563;margin-top:2px">'.$this->e($party['address']).'</div>' : '').'
            '.($party['phone']   ?? false ? '<div style="font-size:12px;color:#4b5563">'.$this->e($party['phone']).'</div>' : '').'
            '.($party['email']   ?? false ? '<div style="font-size:12px;color:#4b5563">'.$this->e($party['email']).'</div>' : '').'
            ' : '<div style="font-size:13px;color:#9ca3af">Walk-in Customer</div>').'
        </td>
    </tr>
</table>

<!-- ===== ITEMS TABLE ===== -->
<table style="width:100%;margin-bottom:4px">
    <thead>
        <tr style="background:'.$theme.'">
            <th style="padding:10px;color:white;font-size:11px;font-weight:600;text-align:center;width:30px">NO</th>
            <th style="padding:10px;color:white;font-size:11px;font-weight:600;text-align:left">DESCRIPTION</th>
            <th style="padding:10px;color:white;font-size:11px;font-weight:600;text-align:center;width:90px">COLOR/SIZE</th>
            <th style="padding:10px;color:white;font-size:11px;font-weight:600;text-align:center;width:60px">ID</th>
            <th style="padding:10px;color:white;font-size:11px;font-weight:600;text-align:right;width:50px">QTY</th>
            <th style="padding:10px;color:white;font-size:11px;font-weight:600;text-align:right;width:80px">UNIT PRICE</th>
            <th style="padding:10px;color:white;font-size:11px;font-weight:600;text-align:right;width:80px">TOTAL</th>
        </tr>
    </thead>
    <tbody>
        '.$itemRows.'
    </tbody>
</table>

<!-- ===== TOTALS ===== -->
<table style="width:100%;margin-bottom:20px">
    <tr>
        <td style="width:55%"></td>
        <td style="width:45%">
            <table style="width:100%;border:1px solid #e5e7eb;border-radius:6px">
                '.$totalsHtml.'
            </table>
        </td>
    </tr>
</table>

<!-- ===== DELIVERY / PAYMENT + QR ===== -->
<table style="width:100%;margin-bottom:24px">
    <tr>
        <td style="width:55%;vertical-align:top">
            '.($deliveryMethod ? '<div style="font-size:12px;margin-bottom:4px"><strong>Delivery Method:</strong> '.$this->e($deliveryMethod).'</div>' : '').'
            '.($paymentMethod  ? '<div style="font-size:12px"><strong>Payment Method:</strong> '.$this->e($paymentMethod).'</div>'  : '').'
        </td>
        <td style="width:45%;text-align:right;vertical-align:top">
            <img src="'.$qrUrl.'" style="width:80px;height:80px" alt="QR">
            <div style="font-size:10px;color:#9ca3af;margin-top:4px">Scan for digital invoice</div>
        </td>
    </tr>
</table>

<!-- ===== NOTE ===== -->
'.($invoice['notes'] ? '
<div style="background:'.$themeLight.';border-left:4px solid '.$theme.';padding:10px 14px;margin-bottom:20px;font-size:12px;color:#4b5563;border-radius:4px">
    <strong>Note:</strong> '.$this->e($invoice['notes']).'
</div>' : '').'

<!-- ===== SIGNATURES ===== -->
<table style="width:100%;margin-bottom:16px">
    <tr>
        <td style="width:42%;text-align:center;padding-top:50px;border-top:1px solid #374151;font-size:12px;color:#374151">
            Buyer ('.$this->e($buyerName).')
        </td>
        <td style="width:16%"></td>
        <td style="width:42%;text-align:center;padding-top:50px;border-top:1px solid #374151;font-size:12px;color:#374151">
            Seller ('.$this->e($sellerName).')
        </td>
    </tr>
</table>

<!-- ===== FOOTER ===== -->
<div style="text-align:center;font-size:10px;color:#9ca3af;border-top:1px solid #e5e7eb;padding-top:10px">
    It was a pleasure doing business with you, we hope to hear from you soon!<br>
    This invoice was generated using Byabsayee &bull;
    by '.$this->e($creatorName).' on '.date('F jS, Y \a\t h:i A', strtotime($invoice['created_at'])).'
</div>

</body>
</html>';

        // Generate with mPDF
        $mpdf = new \Mpdf\Mpdf([
            'mode'          => 'utf-8',
            'format'        => 'A4',
            'margin_top'    => 15,
            'margin_bottom' => 15,
            'margin_left'   => 15,
            'margin_right'  => 15,
            'tempDir'       => '/tmp',
        ]);

        $mpdf->SetTitle('Invoice ' . $invoiceNo);
        $mpdf->WriteHTML($html);

        $filename = 'Invoice-' . $invoiceNo . '.pdf';
        $mpdf->Output($filename, 'D'); // D = force download, I = inline
    }

    // ---- helpers ----

    private function e(string $s): string
    {
        return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private function hexToRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');
        return [
            hexdec(substr($hex,0,2)),
            hexdec(substr($hex,2,2)),
            hexdec(substr($hex,4,2)),
        ];
    }

    private function lighten(string $hex, int $percent): string
    {
        [$r,$g,$b] = $this->hexToRgb($hex);
        $r = (int)($r + (255 - $r) * $percent / 100);
        $g = (int)($g + (255 - $g) * $percent / 100);
        $b = (int)($b + (255 - $b) * $percent / 100);
        return sprintf('#%02x%02x%02x', $r, $g, $b);
    }
}

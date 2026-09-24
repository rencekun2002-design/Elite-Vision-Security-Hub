<?php
/**
 * PDF Invoice Generator
 * Uses HTML to PDF conversion
 */

require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../config.php';

class PDFGenerator
{
    private $db;
    private static $logoDataUriCache = null;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Get the company logo as a base64 data URI, embedded so it renders
     * reliably in the headless-Chrome-generated PDF regardless of file paths.
     */
    private function getLogoDataUri()
    {
        if (self::$logoDataUriCache !== null) {
            return self::$logoDataUriCache;
        }

        $logo_path = __DIR__ . '/../assets/logo.jpeg';
        if (!file_exists($logo_path)) {
            self::$logoDataUriCache = '';
            return '';
        }

        $data = base64_encode(file_get_contents($logo_path));
        self::$logoDataUriCache = 'data:image/jpeg;base64,' . $data;
        return self::$logoDataUriCache;
    }

    /**
     * Generate PDF for invoice and save to disk
     */
    public function generateInvoicePDF($invoice_id)
    {
        try {
            $invoice = $this->getInvoiceData($invoice_id);
            if (!$invoice) {
                logMessage("Invoice {$invoice_id} not found for PDF generation", 'error');
                return false;
            }

            // Generate HTML
            $html = $this->generateInvoiceHTML($invoice);

            $stamp = date('Y-m-d-H-i-s');
            $operator_slug = preg_replace('/[^A-Za-z0-9]+/', '-', $invoice['operator_name']);
            $operator_slug = trim($operator_slug, '-');
            $base_name = 'invoice_' . ($operator_slug ?: 'operator') . '_' . $invoice['invoice_number'] . '_' . $stamp;
            $html_path = PDF_FOLDER . $base_name . '.html';
            $filename = $base_name . '.pdf';
            $filepath = PDF_FOLDER . $filename;

            file_put_contents($html_path, $html);
            $browser = $this->findPdfBrowser();
            if (!$browser)
                throw new RuntimeException('Chrome or Edge is required to generate PDF files.');

            $command = escapeshellarg($browser) . ' --headless --disable-gpu --no-pdf-header-footer --print-to-pdf=' . escapeshellarg($filepath) . ' ' . escapeshellarg('file:///' . str_replace('\\', '/', realpath($html_path))) . ' 2>&1';
            exec($command, $output, $exit_code);
            @unlink($html_path);
            if ($exit_code !== 0 || !file_exists($filepath) || filesize($filepath) === 0) {
                throw new RuntimeException('The browser could not create the PDF.');
            }

            // Update invoice with PDF path
            $stmt = $this->db->prepare('UPDATE invoices SET pdf_path = ? WHERE id = ?');
            $stmt->execute([$filepath, $invoice_id]);

            logMessage("Invoice PDF generated: {$filename}", 'info');
            return $filepath;
        } catch (Exception $e) {
            logMessage("Error generating PDF: " . $e->getMessage(), 'error');
            return false;
        }
    }

    private function findPdfBrowser()
    {
        $browsers = [
            'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
            'C:\\Program Files (x86)\\Google\\Chrome\\Application\\chrome.exe',
            'C:\\Program Files\\Microsoft\\Edge\\Application\\msedge.exe',
            'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe'
        ];
        foreach ($browsers as $browser) {
            if (file_exists($browser))
                return $browser;
        }
        return null;
    }

    /**
     * Get invoice data from database
     */
    private function getInvoiceData($invoice_id)
    {
        try {
            $stmt = $this->db->prepare('
                SELECT i.*, o.name as operator_name, o.email as operator_email, o.hourly_rate
                FROM invoices i
                LEFT JOIN operators o ON i.operator_id = o.id
                WHERE i.id = ?
            ');
            $stmt->execute([$invoice_id]);
            $invoice = $stmt->fetch();

            if ($invoice) {
                // Get items
                $items_stmt = $this->db->prepare('
                    SELECT * FROM invoice_items 
                    WHERE invoice_id = ?
                    ORDER BY work_date ASC
                ');
                $items_stmt->execute([$invoice_id]);
                $invoice['items'] = $items_stmt->fetchAll();
            }

            return $invoice;
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Generate invoice HTML (printable format)
     */
    private function generateInvoiceHTML($invoice)
    {
        $company_name = COMPANY_NAME;
        $company_email = COMPANY_EMAIL;
        $company_phone = COMPANY_PHONE;
        $company_address = COMPANY_ADDRESS;
        $logo_data_uri = $this->getLogoDataUri();
        $logo_img = $logo_data_uri ? "<img src='{$logo_data_uri}' class='band-logo' alt='Logo'>" : '';

        $invoice_number = htmlspecialchars($invoice['invoice_number']);
        $operator_name = htmlspecialchars($invoice['operator_name']);
        $period_start = $this->formatDate($invoice['period_start']);
        $period_end = $this->formatDate($invoice['period_end']);

        $subtotal = number_format($invoice['subtotal'], 2);
        $tax_amount = number_format($invoice['tax_amount'], 2);
        $total = number_format($invoice['total_amount'], 2);

        // Build table rows
        $table_rows = '';
        $total_hours = 0;

        if (!empty($invoice['items'])) {
            foreach ($invoice['items'] as $item) {
                $date = $this->formatDate($item['work_date']);
                $hours = number_format($item['hours'], 2);
                $rate = number_format($item['hourly_rate'], 2);
                $line_total = number_format($item['line_total'], 2);
                $total_hours += $item['hours'];

                $table_rows .= "
                    <tr class='filled'>
                        <td style='text-align: center; padding: 8px;'>{$hours}</td>
                        <td style='text-align: center; padding: 8px;'>{$date}</td>
                        <td style='text-align: center; padding: 8px;'>\${$rate}</td>
                        <td style='text-align: right; padding: 8px; font-weight: bold; background-color: #a9d189;'>\${$line_total}</td>
                    </tr>
                ";
            }
        }

        // Add empty row
        $table_rows .= "<tr><td colspan='4' style='padding: 10px;'>&nbsp;</td></tr>";

        $html = "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <title>Invoice {$invoice_number}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
        }
        @page { size: Letter; margin: 0; }
        .invoice-page {
            background: white;
            width: 8.5in;
            height: 11in;
            max-width: 100%;
            margin: 0 auto;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding-bottom: 40px;
        }
        .invoice-band {
            height: 76px;
            background: linear-gradient(90deg, #12271a 0%, #1c3524 55%, #1f3d1a 100%);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 40px;
            border-bottom: 3px solid #b99a55;
            color: white;
        }
        .band-brand {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .band-logo {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            object-fit: cover;
            background: #fff;
            flex-shrink: 0;
        }
        .band-brand-text {
            line-height: 1.15;
        }
        .band-brand-text .name {
            font-size: 15px;
            font-weight: 600;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }
        .band-brand-text .tag {
            color: #e3cd93;
            font-size: 9px;
            letter-spacing: 0.1em;
            text-transform: uppercase;
        }
        .invoice-band span.title {
            color: #fff;
            font-size: 28px;
            font-weight: 600;
            letter-spacing: 0.08em;
        }
        .invoice-meta {
            text-align: right;
            padding: 14px 40px 0;
            font-size: 13px;
        }
        .invoice-meta .label { font-weight: 700; color: #1a2418; }
        .invoice-body {
            padding: 20px 40px 0;
        }
        .to-row {
            display: flex;
            gap: 20px;
            margin-bottom: 6px;
        }
        .to-label {
            width: 60px;
            font-weight: 700;
            font-size: 13px;
            flex-shrink: 0;
        }
        .to-value {
            font-size: 18px;
            font-weight: 600;
            letter-spacing: 0.02em;
            color: #1c3524;
            text-transform: uppercase;
        }
        .dates-line {
            margin: 6px 0 4px 80px;
            font-size: 13px;
        }
        .op-row {
            display: flex;
            gap: 20px;
            margin: 10px 0 20px;
        }
        .op-label {
            width: 60px;
            font-weight: 700;
            font-size: 13px;
            flex-shrink: 0;
        }
        .op-value {
            font-size: 13px;
            font-weight: 700;
        }
        table.invoice-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
            margin-top: 10px;
        }
        table.invoice-table th {
            background: #1f3d1a;
            color: #fff;
            padding: 8px 10px;
            text-align: center;
            font-size: 12px;
            letter-spacing: 0.02em;
        }
        table.invoice-table td {
            padding: 6px 10px;
            border: 1px solid #c9d6c1;
            text-align: center;
        }
        .totals-row td {
            font-weight: 700;
            border: 1px solid #c9d6c1;
            padding: 8px 10px;
        }
        .totals-row td.tot-label {
            text-align: right;
            background: white;
        }
        .totals-row td.tot-val {
            background: #8ec46b;
            text-align: right;
        }
        .grand-total td.tot-val {
            font-size: 15px;
            background: #8ec46b;
        }
        .footer {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e0e5db;
            font-size: 12px;
            color: #66705f;
            text-align: center;
        }
        @media print {
            body { background: white; }
            .invoice-page { box-shadow: none; margin: 0; }
        }
    </style>
</head>
<body>
    <div class='invoice-page'>
        <div class='invoice-band'>
            <div class='band-brand'>
                {$logo_img}
                <div class='band-brand-text'>
                    <div class='name'>{$company_name}</div>
                    <div class='tag'>Always Watching, Always Protecting</div>
                </div>
            </div>
            <span class='title'>INVOICE</span>
        </div>
        
        <div class='invoice-meta'>
            <div><span class='label'>INVOICE NO.</span> {$invoice_number}</div>
        </div>
        
        <div class='invoice-body'>
            <div class='to-row'>
                <div class='to-label'>TO</div>
                <div class='to-value'>{$company_name}</div>
            </div>
            <div class='dates-line'>Dates Covered: {$period_start} - {$period_end}</div>
            
            <div class='op-row'>
                <div class='op-label'>OPERATOR</div>
                <div class='op-value'>{$operator_name}</div>
            </div>
            
            <table class='invoice-table'>
                <thead>
                    <tr>
                        <th>HRS/DAY</th>
                        <th>DESCRIPTION</th>
                        <th>UNIT PRICE</th>
                        <th>LINE TOTAL</th>
                    </tr>
                </thead>
                <tbody>
                    {$table_rows}
                    <tr class='totals-row'>
                        <td>TOTAL HOURS</td>
                        <td></td>
                        <td class='tot-label'>SUBTOTAL</td>
                        <td class='tot-val'>\${$subtotal}</td>
                    </tr>
                    <tr class='totals-row'>
                        <td>" . number_format($total_hours, 2) . "</td>
                        <td></td>
                        <td class='tot-label'>SALES TAX</td>
                        <td class='tot-val'>\${$tax_amount}</td>
                    </tr>
                    <tr class='totals-row grand-total'>
                        <td></td>
                        <td></td>
                        <td class='tot-label'>TOTAL</td>
                        <td class='tot-val'>\${$total}</td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <div class='footer'>
            <p>{$company_name} | {$company_email} | {$company_phone}</p>
            <p>{$company_address}</p>
            <p>Generated on " . date('F j, Y') . "</p>
        </div>
    </div>
</body>
</html>";

        return $html;
    }

    /**
     * Generate consolidated PDF for multiple invoices
     */
    public function generateConsolidatedPDF($invoice_ids, $period_name = '')
    {
        try {
            if (empty($invoice_ids)) {
                throw new RuntimeException('No invoices selected.');
            }

            // Fetch all invoices
            $invoices = [];
            foreach ($invoice_ids as $id) {
                $invoice = $this->getInvoiceData($id);
                if ($invoice) {
                    $invoices[] = $invoice;
                }
            }

            if (empty($invoices)) {
                throw new RuntimeException('Could not load any invoices.');
            }

            // Generate HTML for all invoices
            $all_invoice_html = '';
            foreach ($invoices as $index => $invoice) {
                $invoice_html = $this->generateInvoiceHTML($invoice);
                // Extract just the body content (skip DOCTYPE and head)
                preg_match('/<body[^>]*>(.*)<\/body>/is', $invoice_html, $matches);
                if (!empty($matches[1])) {
                    // Remove body tags and add page break
                    if ($index > 0) {
                        $all_invoice_html .= '<div style="page-break-before: always;"></div>';
                    }
                    $all_invoice_html .= $matches[1];
                }
            }

            // Create wrapper HTML with CSS
            $html = "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <title>Consolidated Invoice Report</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
        }
        @page { size: Letter; margin: 0; }
        .invoice-page {
            background: white;
            width: 8.5in;
            height: 11in;
            max-width: 100%;
            margin: 0 auto;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding-bottom: 40px;
        }
        .invoice-band {
            height: 76px;
            background: linear-gradient(90deg, #12271a 0%, #1c3524 55%, #1f3d1a 100%);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 40px;
            border-bottom: 3px solid #b99a55;
            color: white;
        }
        .band-brand {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .band-logo {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            object-fit: cover;
            background: #fff;
            flex-shrink: 0;
        }
        .band-brand-text {
            line-height: 1.15;
        }
        .band-brand-text .name {
            font-size: 15px;
            font-weight: 600;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }
        .band-brand-text .tag {
            color: #e3cd93;
            font-size: 9px;
            letter-spacing: 0.1em;
            text-transform: uppercase;
        }
        .invoice-band span.title {
            color: #fff;
            font-size: 28px;
            font-weight: 600;
            letter-spacing: 0.08em;
        }
        .invoice-meta {
            text-align: right;
            padding: 14px 40px 0;
            font-size: 13px;
        }
        .invoice-meta .label { font-weight: 700; color: #1a2418; }
        .invoice-body {
            padding: 20px 40px 0;
        }
        .to-row {
            display: flex;
            gap: 20px;
            margin-bottom: 6px;
        }
        .to-label {
            width: 60px;
            font-weight: 700;
            font-size: 13px;
            flex-shrink: 0;
        }
        .to-value {
            font-size: 18px;
            font-weight: 600;
            letter-spacing: 0.02em;
            color: #1c3524;
            text-transform: uppercase;
        }
        .dates-line {
            margin: 6px 0 4px 80px;
            font-size: 13px;
        }
        .op-row {
            display: flex;
            gap: 20px;
            margin: 10px 0 20px;
        }
        .op-label {
            width: 60px;
            font-weight: 700;
            font-size: 13px;
            flex-shrink: 0;
        }
        .op-value {
            font-size: 13px;
            font-weight: 700;
        }
        table.invoice-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
            margin-top: 10px;
        }
        table.invoice-table th {
            background: #1f3d1a;
            color: #fff;
            padding: 8px 10px;
            text-align: center;
            font-size: 12px;
            letter-spacing: 0.02em;
        }
        table.invoice-table td {
            padding: 6px 10px;
            border: 1px solid #c9d6c1;
            text-align: center;
        }
        .totals-row td {
            font-weight: 700;
            border: 1px solid #c9d6c1;
            padding: 8px 10px;
        }
        .totals-row td.tot-label {
            text-align: right;
            background: white;
        }
        .totals-row td.tot-val {
            background: #8ec46b;
            text-align: right;
        }
        .grand-total td.tot-val {
            font-size: 15px;
            background: #8ec46b;
        }
        .footer {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e0e5db;
            font-size: 12px;
            color: #66705f;
            text-align: center;
        }
        div[style*='page-break-before'] {
            page-break-before: always;
        }
        @media print {
            body { background: white; }
            .invoice-page { box-shadow: none; margin: 0; }
        }
    </style>
</head>
<body>
    {$all_invoice_html}
</body>
</html>";

            // Generate PDF
            $stamp = date('Y-m-d-H-i-s');
            $period_slug = $period_name ? preg_replace('/[^A-Za-z0-9]+/', '-', $period_name) : 'period';
            $period_slug = trim($period_slug, '-');
            $base_name = 'consolidated_' . $period_slug . '_' . $stamp;
            $html_path = PDF_FOLDER . $base_name . '.html';
            $filename = $base_name . '.pdf';
            $filepath = PDF_FOLDER . $filename;

            file_put_contents($html_path, $html);
            $browser = $this->findPdfBrowser();
            if (!$browser)
                throw new RuntimeException('Chrome or Edge is required to generate PDF files.');

            $command = escapeshellarg($browser) . ' --headless --disable-gpu --no-pdf-header-footer --print-to-pdf=' . escapeshellarg($filepath) . ' ' . escapeshellarg('file:///' . str_replace('\\', '/', realpath($html_path))) . ' 2>&1';
            exec($command, $output, $exit_code);
            @unlink($html_path);

            if ($exit_code !== 0 || !file_exists($filepath) || filesize($filepath) === 0) {
                throw new RuntimeException('The browser could not create the PDF.');
            }

            logMessage("Consolidated PDF generated: {$filename} with " . count($invoices) . " invoices", 'info');
            return $filepath;
        } catch (Exception $e) {
            logMessage("Error generating consolidated PDF: " . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Format date for display
     */
    private function formatDate($date)
    {
        if (!$date)
            return '';
        return date('F j, Y', strtotime($date));
    }
}
?>
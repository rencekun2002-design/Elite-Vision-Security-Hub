<?php
/**
 * Consolidated Invoices API
 * Handles retrieval and PDF generation for multiple invoices within a date range
 */

require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../classes/InvoicesManager.php';
require_once __DIR__ . '/../classes/PDFGenerator.php';

header('Content-Type: application/json');

try {
    $action = $_GET['action'] ?? 'list';
    $db = Database::getInstance()->getConnection();
    $invoices_mgr = new InvoicesManager();
    $pdf_gen = new PDFGenerator();

    if ($action === 'list') {
        // Get invoices for a date range
        $start_date = $_GET['start_date'] ?? date('Y-m-01');
        $end_date = $_GET['end_date'] ?? date('Y-m-t');

        // Validate dates
        if (!strtotime($start_date) || !strtotime($end_date)) {
            throw new Exception('Invalid date format. Use YYYY-MM-DD.');
        }

        // Ensure start_date is before end_date
        if ($start_date > $end_date) {
            $temp = $start_date;
            $start_date = $end_date;
            $end_date = $temp;
        }

        // Query invoices created within the date range
        $stmt = $db->prepare('
            SELECT i.id, i.invoice_number, i.operator_id, i.period_start, i.period_end, 
                   i.subtotal, i.tax_amount, i.total_amount, i.status, i.created_at,
                   o.name as operator_name
            FROM invoices i
            LEFT JOIN operators o ON i.operator_id = o.id
            WHERE DATE(i.created_at) BETWEEN ? AND ?
            ORDER BY i.created_at DESC
        ');
        $stmt->execute([$start_date, $end_date]);
        $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $total_amount = 0;
        foreach ($invoices as $invoice) {
            $total_amount += $invoice['total_amount'];
        }

        echo json_encode([
            'success' => true,
            'invoices' => $invoices,
            'count' => count($invoices),
            'total_amount' => $total_amount,
            'period' => [
                'start' => $start_date,
                'end' => $end_date
            ]
        ]);

    } elseif ($action === 'generate_pdf') {
        // Generate consolidated PDF for selected invoices
        $invoice_ids = $_POST['invoice_ids'] ?? [];
        $period_name = $_POST['period_name'] ?? date('M-Y');

        if (!is_array($invoice_ids) || empty($invoice_ids)) {
            throw new Exception('No invoices selected.');
        }

        // Validate all IDs are integers
        $invoice_ids = array_map('intval', $invoice_ids);

        // Generate the consolidated PDF
        $filepath = $pdf_gen->generateConsolidatedPDF($invoice_ids, $period_name);

        if (!$filepath) {
            throw new Exception('Failed to generate PDF. Check logs for details.');
        }

        echo json_encode([
            'success' => true,
            'filepath' => $filepath,
            'filename' => basename($filepath),
            'message' => 'Consolidated PDF generated successfully.'
        ]);

    } else {
        throw new Exception("Unknown action: {$action}");
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
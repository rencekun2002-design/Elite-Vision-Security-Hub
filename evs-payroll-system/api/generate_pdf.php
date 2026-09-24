<?php
/**
 * API Endpoint: PDF Generation
 * Generates PDF invoices
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../classes/PDFGenerator.php';
require_once __DIR__ . '/../classes/InvoicesManager.php';

$pdf_gen = new PDFGenerator();
$invoices_mgr = new InvoicesManager();

$invoice_id = $_GET['id'] ?? 0;

if (!$invoice_id) {
    http_response_code(400);
    die('Invoice ID is required');
}

// Get invoice
$invoice = $invoices_mgr->getInvoice($invoice_id);

if (!$invoice) {
    http_response_code(404);
    die('Invoice not found');
}

// Generate PDF
$filepath = $pdf_gen->generateInvoicePDF($invoice_id);

if (!$filepath || !file_exists($filepath)) {
    http_response_code(500);
    die('Failed to generate PDF');
}

// Download PDF
header('Content-Type: application/pdf');
$disposition = isset($_GET['inline']) && $_GET['inline'] === '1' ? 'inline' : 'attachment';
header('Content-Disposition: ' . $disposition . '; filename="' . $invoice['invoice_number'] . '.pdf"');
header('Content-Length: ' . filesize($filepath));

readfile($filepath);
exit;
?>
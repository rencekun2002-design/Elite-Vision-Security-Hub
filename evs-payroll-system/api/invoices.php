<?php
/**
 * API Endpoint: Invoice Operations
 * Handles CRUD operations for invoices
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../classes/InvoicesManager.php';
require_once __DIR__ . '/../classes/EmailManager.php';
require_once __DIR__ . '/../classes/PDFGenerator.php';

$invoices_mgr = new InvoicesManager();
$email_mgr = new EmailManager();
$pdf_gen = new PDFGenerator();

$action = $_REQUEST['action'] ?? '';
$response = ['success' => false, 'message' => 'Unknown action'];

try {
    switch ($action) {
        case 'list':
            // Get all invoices with optional filters
            $filters = [];
            if (isset($_GET['operator_id']))
                $filters['operator_id'] = $_GET['operator_id'];
            if (isset($_GET['status']))
                $filters['status'] = $_GET['status'];
            if (isset($_GET['payment_status']))
                $filters['payment_status'] = $_GET['payment_status'];

            $invoices = $invoices_mgr->getAllInvoices($filters);
            $response = [
                'success' => true,
                'count' => count($invoices),
                'invoices' => $invoices
            ];
            break;

        case 'get':
            // Get invoice details
            $id = $_GET['id'] ?? 0;
            $invoice = $invoices_mgr->getInvoice($id);

            if ($invoice) {
                $response = [
                    'success' => true,
                    'invoice' => $invoice
                ];
            } else {
                $response = ['success' => false, 'message' => 'Invoice not found'];
            }
            break;

        case 'create':
            // Create new invoice
            $data = json_decode(file_get_contents('php://input'), true);

            if (!isset($data['operator_id']) || !isset($data['items'])) {
                $response = ['success' => false, 'message' => 'Missing required fields'];
                break;
            }

            $invoice_id = $invoices_mgr->createInvoice(
                $data['operator_id'],
                $data['period_start'] ?? date('Y-m-d'),
                $data['period_end'] ?? date('Y-m-d'),
                $data['items'],
                $data['tax_rate'] ?? 0
            );

            if ($invoice_id) {
                $response = [
                    'success' => true,
                    'message' => 'Invoice created successfully',
                    'invoice_id' => $invoice_id
                ];
            } else {
                $response = ['success' => false, 'message' => 'Failed to create invoice'];
            }
            break;

        case 'update_status':
            // Update invoice status
            $data = json_decode(file_get_contents('php://input'), true);
            $id = $data['id'] ?? 0;
            $status = $data['status'] ?? 'draft';

            if ($invoices_mgr->updateInvoiceStatus($id, $status)) {
                $response = [
                    'success' => true,
                    'message' => 'Invoice status updated'
                ];
            } else {
                $response = ['success' => false, 'message' => 'Failed to update status'];
            }
            break;

        case 'update':
            $data = json_decode(file_get_contents('php://input'), true);
            if (!isset($data['id'], $data['operator_id'], $data['items']) || empty($data['items'])) {
                $response = ['success' => false, 'message' => 'Missing required fields'];
                break;
            }
            if ($invoices_mgr->updateInvoice($data['id'], $data['operator_id'], $data['period_start'] ?? date('Y-m-d'), $data['period_end'] ?? date('Y-m-d'), $data['items'], $data['tax_rate'] ?? 0)) {
                $updated_pdf = $pdf_gen->generateInvoicePDF($data['id']);
                if (!$updated_pdf) {
                    $response = ['success' => false, 'message' => 'Invoice was updated, but its PDF could not be generated.'];
                    break;
                }
                $response = [
                    'success' => true,
                    'message' => 'Invoice updated successfully',
                    'invoice_id' => $data['id']
                ];
            } else {
                $response = ['success' => false, 'message' => 'Failed to update invoice'];
            }
            break;

        case 'send_update_email':
            // Send email for updated invoice
            $invoice_id = $_POST['id'] ?? 0;
            $invoice = $invoices_mgr->getInvoice($invoice_id);

            if (!$invoice) {
                $response = ['success' => false, 'message' => 'Invoice not found'];
                break;
            }

            // Generate PDF if needed
            $pdf_path = $invoice['pdf_path'];
            if (!$pdf_path || !file_exists($pdf_path)) {
                $pdf_path = $pdf_gen->generateInvoicePDF($invoice_id);
            }
            if (!$pdf_path || !file_exists($pdf_path)) {
                $response = ['success' => false, 'message' => 'Unable to generate the invoice PDF.'];
                break;
            }

            // Send email as update
            if ($email_mgr->sendInvoiceEmail($invoice_id, $pdf_path, true)) {
                $response = [
                    'success' => true,
                    'message' => 'Updated invoice emailed successfully'
                ];
            } else {
                $response = [
                    'success' => false,
                    'message' => $email_mgr->getLastError() ?: 'Failed to send email'
                ];
            }
            break;

        case 'send_email':
            // Send invoice via email
            $invoice_id = $_POST['id'] ?? 0;

            // Generate PDF first
            $pdf_path = $pdf_gen->generateInvoicePDF($invoice_id);
            if (!$pdf_path || !file_exists($pdf_path)) {
                $response = ['success' => false, 'message' => 'Unable to generate the invoice PDF.'];
                break;
            }

            // Send email
            if ($email_mgr->sendInvoiceEmail($invoice_id, $pdf_path)) {
                $response = [
                    'success' => true,
                    'message' => 'Invoice emailed successfully'
                ];
            } else {
                $response = [
                    'success' => false,
                    'message' => $email_mgr->getLastError() ?: 'Failed to send email'
                ];
            }
            break;

        case 'resend_email':
            // Resend invoice email
            $invoice_id = $_POST['id'] ?? 0;

            if ($email_mgr->resendInvoiceEmail($invoice_id)) {
                $response = [
                    'success' => true,
                    'message' => 'Invoice resent successfully'
                ];
            } else {
                $response = [
                    'success' => false,
                    'message' => 'Failed to resend email'
                ];
            }
            break;

        case 'delete':
            // Delete invoice
            $id = $_POST['id'] ?? 0;

            if ($invoices_mgr->deleteInvoice($id)) {
                $response = [
                    'success' => true,
                    'message' => 'Invoice deleted successfully'
                ];
            } else {
                $response = ['success' => false, 'message' => 'Failed to delete invoice'];
            }
            break;

        case 'get_stats':
            // Get dashboard statistics
            $stats = $invoices_mgr->getDashboardStats();
            $response = [
                'success' => true,
                'stats' => $stats
            ];
            break;

        default:
            $response = ['success' => false, 'message' => 'Invalid action'];
    }
} catch (Exception $e) {
    logMessage('API Error: ' . $e->getMessage(), 'error');
    $response = [
        'success' => false,
        'message' => 'Server error'
    ];
}

echo json_encode($response);
?>
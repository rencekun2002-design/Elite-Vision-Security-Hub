<?php
/**
 * API Endpoint: Payments Management
 * Handles payment recording and tracking
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../classes/PaymentsManager.php';
require_once __DIR__ . '/../classes/EmailManager.php';

$payments_mgr = new PaymentsManager();
$email_mgr = new EmailManager();

$action = $_REQUEST['action'] ?? '';
$response = ['success' => false, 'message' => 'Unknown action'];

try {
    switch ($action) {
        case 'record':
            // Record a payment
            $data = json_decode(file_get_contents('php://input'), true);

            if (!isset($data['invoice_id']) || !isset($data['amount'])) {
                $response = ['success' => false, 'message' => 'Missing required fields'];
                break;
            }

            $payment_id = $payments_mgr->recordPayment(
                $data['invoice_id'],
                $data['operator_id'],
                $data['payment_date'] ?? date('Y-m-d'),
                $data['amount'],
                $data['payment_method'] ?? 'transfer',
                $data['reference_number'] ?? '',
                $data['notes'] ?? ''
            );

            if ($payment_id) {
                // Send payment confirmation email
                $email_mgr->sendPaymentConfirmationEmail(
                    $data['invoice_id'],
                    $data['amount'],
                    $data['payment_date'] ?? date('Y-m-d'),
                    $data['payment_method'] ?? 'transfer'
                );

                $response = [
                    'success' => true,
                    'message' => 'Payment recorded successfully',
                    'payment_id' => $payment_id
                ];
            } else {
                $response = ['success' => false, 'message' => 'Failed to record payment'];
            }
            break;

        case 'get_invoice_payments':
            // Get all payments for an invoice
            $invoice_id = $_GET['invoice_id'] ?? 0;
            $payments = $payments_mgr->getInvoicePayments($invoice_id);

            $response = [
                'success' => true,
                'count' => count($payments),
                'payments' => $payments
            ];
            break;

        case 'get_operator_payments':
            // Get all payments for an operator
            $operator_id = $_GET['operator_id'] ?? 0;
            $date_from = $_GET['date_from'] ?? null;
            $date_to = $_GET['date_to'] ?? null;

            $payments = $payments_mgr->getOperatorPayments($operator_id, $date_from, $date_to);

            $response = [
                'success' => true,
                'count' => count($payments),
                'payments' => $payments
            ];
            break;

        case 'get_summary':
            // Get payment summary
            $date_from = $_GET['date_from'] ?? null;
            $date_to = $_GET['date_to'] ?? null;

            $summary = $payments_mgr->getPaymentSummary($date_from, $date_to);

            $response = [
                'success' => true,
                'summary' => $summary
            ];
            break;

        case 'get_invoice_payment_status':
            // Get payment status for an invoice
            $invoice_id = $_GET['invoice_id'] ?? 0;
            $status = $payments_mgr->getInvoicePaymentStatus($invoice_id);

            if ($status) {
                $response = [
                    'success' => true,
                    'payment_status' => $status
                ];
            } else {
                $response = ['success' => false, 'message' => 'Invoice not found'];
            }
            break;

        case 'delete':
            // Delete a payment record
            $data = json_decode(file_get_contents('php://input'), true);

            if (!isset($data['id']) || !isset($data['invoice_id'])) {
                $response = ['success' => false, 'message' => 'Missing required fields'];
                break;
            }

            if ($payments_mgr->deletePayment($data['id'], $data['invoice_id'])) {
                $response = [
                    'success' => true,
                    'message' => 'Payment deleted successfully'
                ];
            } else {
                $response = ['success' => false, 'message' => 'Failed to delete payment'];
            }
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
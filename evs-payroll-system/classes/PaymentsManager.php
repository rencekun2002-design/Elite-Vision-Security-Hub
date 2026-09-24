<?php
/**
 * Payments Manager - Handle payment tracking and recording
 */

require_once __DIR__ . '/../database.php';

class PaymentsManager
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Record a payment
     */
    public function recordPayment($invoice_id, $operator_id, $payment_date, $amount, $payment_method = 'transfer', $reference_number = '', $notes = '')
    {
        try {
            if (!is_numeric($amount) || (float) $amount <= 0) {
                throw new InvalidArgumentException('Payment amount must be greater than zero.');
            }

            $invoice_stmt = $this->db->prepare('SELECT operator_id FROM invoices WHERE id = ?');
            $invoice_stmt->execute([$invoice_id]);
            $invoice = $invoice_stmt->fetch();
            if (!$invoice || (int) $invoice['operator_id'] !== (int) $operator_id) {
                throw new InvalidArgumentException('Payment does not match the selected invoice.');
            }

            $stmt = $this->db->prepare('
                INSERT INTO payments (invoice_id, operator_id, payment_date, amount, payment_method, reference_number, notes)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ');
            $stmt->execute([$invoice_id, $operator_id, $payment_date, $amount, $payment_method, $reference_number, $notes]);

            // Update invoice payment status
            $this->updateInvoicePaymentStatus($invoice_id);

            logMessage("Payment recorded: ${amount} for invoice {$invoice_id}", 'info');
            return $this->db->lastInsertId();
        } catch (Exception $e) {
            logMessage("Error recording payment: " . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Update invoice payment status based on payments received
     */
    private function updateInvoicePaymentStatus($invoice_id)
    {
        try {
            // Get invoice total
            $stmt = $this->db->prepare('SELECT total_amount, email_sent_count FROM invoices WHERE id = ?');
            $stmt->execute([$invoice_id]);
            $invoice = $stmt->fetch();
            if (!$invoice) {
                throw new RuntimeException('Invoice not found');
            }

            // Get payments total
            $stmt = $this->db->prepare('SELECT COALESCE(SUM(amount), 0) as paid, MAX(payment_date) as last_payment_date FROM payments WHERE invoice_id = ?');
            $stmt->execute([$invoice_id]);
            $payment_result = $stmt->fetch();

            $paid_amount = $payment_result['paid'] ?? 0;
            $invoice_total = $invoice['total_amount'];

            if ($paid_amount >= $invoice_total) {
                $payment_status = 'paid';
                $status = 'paid';
            } elseif ($paid_amount > 0) {
                $payment_status = 'partial';
                $status = 'partially_paid';
            } else {
                $payment_status = 'unpaid';
                $status = $invoice['email_sent_count'] > 0 ? 'sent' : 'draft';
            }

            $update_stmt = $this->db->prepare('
                UPDATE invoices 
                SET payment_status = ?, payment_amount = ?, payment_date = ?, status = ?, updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ');
            $update_stmt->execute([$payment_status, $paid_amount, $payment_result['last_payment_date'], $status, $invoice_id]);
        } catch (Exception $e) {
            logMessage("Error updating invoice payment status: " . $e->getMessage(), 'error');
        }
    }

    /**
     * Get payments for an invoice
     */
    public function getInvoicePayments($invoice_id)
    {
        try {
            $stmt = $this->db->prepare('
                SELECT * FROM payments
                WHERE invoice_id = ?
                ORDER BY payment_date DESC
            ');
            $stmt->execute([$invoice_id]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            logMessage("Error fetching payments: " . $e->getMessage(), 'error');
            return [];
        }
    }

    /**
     * Get payments for an operator
     */
    public function getOperatorPayments($operator_id, $date_from = null, $date_to = null)
    {
        try {
            $sql = 'SELECT * FROM payments WHERE operator_id = ?';
            $params = [$operator_id];

            if ($date_from) {
                $sql .= ' AND payment_date >= ?';
                $params[] = $date_from;
            }

            if ($date_to) {
                $sql .= ' AND payment_date <= ?';
                $params[] = $date_to;
            }

            $sql .= ' ORDER BY payment_date DESC';

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            logMessage("Error fetching operator payments: " . $e->getMessage(), 'error');
            return [];
        }
    }

    /**
     * Get payment summary for dashboard
     */
    public function getPaymentSummary($date_from = null, $date_to = null)
    {
        try {
            $sql = 'SELECT 
                COUNT(*) as total_payments,
                SUM(amount) as total_paid,
                COUNT(DISTINCT invoice_id) as invoices_paid,
                COUNT(DISTINCT operator_id) as operators_paid
            FROM payments
            WHERE 1=1';

            $params = [];

            if ($date_from) {
                $sql .= ' AND payment_date >= ?';
                $params[] = $date_from;
            }

            if ($date_to) {
                $sql .= ' AND payment_date <= ?';
                $params[] = $date_to;
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetch();
        } catch (Exception $e) {
            logMessage("Error getting payment summary: " . $e->getMessage(), 'error');
            return [];
        }
    }

    /**
     * Get invoice payment details
     */
    public function getInvoicePaymentStatus($invoice_id)
    {
        try {
            $stmt = $this->db->prepare('
                SELECT 
                    i.id, i.invoice_number, i.total_amount, i.payment_status,
                    SUM(p.amount) as paid_amount,
                    COUNT(p.id) as payment_count,
                    MAX(p.payment_date) as last_payment_date
                FROM invoices i
                LEFT JOIN payments p ON i.id = p.invoice_id
                WHERE i.id = ?
                GROUP BY i.id
            ');
            $stmt->execute([$invoice_id]);
            return $stmt->fetch();
        } catch (Exception $e) {
            logMessage("Error getting invoice payment status: " . $e->getMessage(), 'error');
            return null;
        }
    }

    /**
     * Delete a payment record
     */
    public function deletePayment($id, $invoice_id)
    {
        try {
            $stmt = $this->db->prepare('DELETE FROM payments WHERE id = ? AND invoice_id = ?');
            $stmt->execute([$id, $invoice_id]);
            if ($stmt->rowCount() !== 1) {
                throw new RuntimeException('Payment not found for this invoice.');
            }

            // Recalculate invoice payment status
            $this->updateInvoicePaymentStatus($invoice_id);

            logMessage("Payment {$id} deleted", 'info');
            return true;
        } catch (Exception $e) {
            logMessage("Error deleting payment: " . $e->getMessage(), 'error');
            return false;
        }
    }
}
?>
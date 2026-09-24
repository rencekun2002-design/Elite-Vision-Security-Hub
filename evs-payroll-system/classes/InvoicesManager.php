<?php
/**
 * Invoices Manager - Handle invoice creation, management, and tracking
 */

require_once __DIR__ . '/../database.php';

class InvoicesManager
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Create a new invoice with line items
     */
    public function createInvoice($operator_id, $period_start, $period_end, $items, $tax_rate = 0)
    {
        try {
            $this->db->beginTransaction();

            // Get next invoice number
            $invoice_number = $this->getNextInvoiceNumber();

            // Calculate totals
            $subtotal = 0;
            foreach ($items as $item) {
                $subtotal += $item['line_total'];
            }

            $tax_amount = $subtotal * ($tax_rate / 100);
            $total_amount = $subtotal + $tax_amount;

            // Insert invoice
            $stmt = $this->db->prepare('
                INSERT INTO invoices (invoice_number, operator_id, period_start, period_end, subtotal, tax_amount, total_amount, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ');
            $stmt->execute([$invoice_number, $operator_id, $period_start, $period_end, $subtotal, $tax_amount, $total_amount, 'draft']);

            $invoice_id = $this->db->lastInsertId();

            // Insert line items
            $item_stmt = $this->db->prepare('
                INSERT INTO invoice_items (invoice_id, work_date, hours, hourly_rate, line_total)
                VALUES (?, ?, ?, ?, ?)
            ');

            foreach ($items as $item) {
                $item_stmt->execute([
                    $invoice_id,
                    $item['work_date'],
                    $item['hours'],
                    $item['hourly_rate'],
                    $item['line_total']
                ]);
            }

            $this->db->commit();
            logMessage("Invoice created: {$invoice_number} for operator ID {$operator_id}", 'info');
            return $invoice_id;
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            logMessage("Error creating invoice: " . $e->getMessage(), 'error');
            return false;
        }
    }

    public function updateInvoice($id, $operator_id, $period_start, $period_end, $items, $tax_rate = 0)
    {
        try {
            $subtotal = 0;
            foreach ($items as $item)
                $subtotal += (float) $item['line_total'];
            $tax_amount = $subtotal * ((float) $tax_rate / 100);
            $total_amount = $subtotal + $tax_amount;

            $this->db->beginTransaction();
            $stmt = $this->db->prepare('UPDATE invoices SET operator_id = ?, period_start = ?, period_end = ?, subtotal = ?, tax_amount = ?, total_amount = ?, status = ?, pdf_path = NULL, updated_at = CURRENT_TIMESTAMP WHERE id = ?');
            $stmt->execute([$operator_id, $period_start, $period_end, $subtotal, $tax_amount, $total_amount, 'draft', $id]);

            $delete_items = $this->db->prepare('DELETE FROM invoice_items WHERE invoice_id = ?');
            $delete_items->execute([$id]);
            $item_stmt = $this->db->prepare('INSERT INTO invoice_items (invoice_id, work_date, hours, hourly_rate, line_total) VALUES (?, ?, ?, ?, ?)');
            foreach ($items as $item) {
                $item_stmt->execute([$id, $item['work_date'], $item['hours'], $item['hourly_rate'], $item['line_total']]);
            }
            $this->db->commit();
            logMessage("Invoice {$id} updated", 'info');
            return true;
        } catch (Exception $e) {
            if ($this->db->inTransaction())
                $this->db->rollBack();
            logMessage("Error updating invoice: " . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Get next invoice number
     */
    private function getNextInvoiceNumber()
    {
        try {
            $stmt = $this->db->query('SELECT next_invoice_number, invoice_prefix FROM settings ORDER BY id DESC LIMIT 1');
            $result = $stmt->fetch();

            $prefix = $result['invoice_prefix'] ?? '2026-';
            $next_num = $result['next_invoice_number'] ?? 1;

            // Increment for next call
            $update_stmt = $this->db->prepare('UPDATE settings SET next_invoice_number = ? WHERE id = (SELECT id FROM settings ORDER BY id DESC LIMIT 1)');
            $update_stmt->execute([$next_num + 1]);

            return $prefix . str_pad($next_num, 3, '0', STR_PAD_LEFT);
        } catch (Exception $e) {
            return date('Y-m-') . str_pad(1, 3, '0', STR_PAD_LEFT);
        }
    }

    /**
     * Get invoice by ID with all details
     */
    public function getInvoice($id)
    {
        try {
            $stmt = $this->db->prepare('
                SELECT i.*, o.name as operator_name, o.email as operator_email
                FROM invoices i
                LEFT JOIN operators o ON i.operator_id = o.id
                WHERE i.id = ?
            ');
            $stmt->execute([$id]);
            $invoice = $stmt->fetch();

            if ($invoice) {
                // Get line items
                $items_stmt = $this->db->prepare('
                    SELECT * FROM invoice_items 
                    WHERE invoice_id = ?
                    ORDER BY work_date ASC
                ');
                $items_stmt->execute([$id]);
                $invoice['items'] = $items_stmt->fetchAll();
            }

            return $invoice;
        } catch (Exception $e) {
            logMessage("Error fetching invoice: " . $e->getMessage(), 'error');
            return null;
        }
    }

    /**
     * Get all invoices with filters
     */
    public function getAllInvoices($filters = [])
    {
        try {
            $sql = '
                SELECT i.*, o.name as operator_name 
                FROM invoices i
                LEFT JOIN operators o ON i.operator_id = o.id
                WHERE 1=1
            ';
            $params = [];

            if (isset($filters['operator_id'])) {
                $sql .= ' AND i.operator_id = ?';
                $params[] = $filters['operator_id'];
            }

            if (isset($filters['status'])) {
                $sql .= ' AND i.status = ?';
                $params[] = $filters['status'];
            }

            if (isset($filters['payment_status'])) {
                $sql .= ' AND i.payment_status = ?';
                $params[] = $filters['payment_status'];
            }

            if (isset($filters['date_from'])) {
                $sql .= ' AND i.created_at >= ?';
                $params[] = $filters['date_from'];
            }

            if (isset($filters['date_to'])) {
                $sql .= ' AND i.created_at <= ?';
                $params[] = $filters['date_to'];
            }

            $sql .= ' ORDER BY i.created_at DESC';

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            logMessage("Error fetching invoices: " . $e->getMessage(), 'error');
            return [];
        }
    }

    /**
     * Update invoice status
     */
    public function updateInvoiceStatus($id, $status, $payment_status = null)
    {
        try {
            $sql = 'UPDATE invoices SET status = ?, updated_at = CURRENT_TIMESTAMP';
            $params = [$status, $id];

            if ($payment_status) {
                $sql .= ', payment_status = ?';
                array_splice($params, 1, 0, [$payment_status]);
            }

            $sql .= ' WHERE id = ?';

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            logMessage("Invoice {$id} status updated to {$status}", 'info');
            return true;
        } catch (Exception $e) {
            logMessage("Error updating invoice status: " . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Mark invoice as sent
     */
    public function markAsSent($id)
    {
        try {
            $stmt = $this->db->prepare('
                UPDATE invoices 
                SET status = ?, email_sent_count = email_sent_count + 1, last_email_sent = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ');
            $stmt->execute(['sent', $id]);
            logMessage("Invoice {$id} marked as sent", 'info');
            return true;
        } catch (Exception $e) {
            logMessage("Error marking invoice as sent: " . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Get dashboard statistics
     */
    public function getDashboardStats()
    {
        try {
            $stats = [];

            // Total invoices by status
            $stmt = $this->db->query('
                SELECT status, COUNT(*) as count, SUM(total_amount) as total
                FROM invoices
                GROUP BY status
            ');
            $stats['by_status'] = $stmt->fetchAll();

            // Payment statistics
            $stmt = $this->db->query('
                SELECT payment_status, COUNT(*) as count, SUM(total_amount) as total
                FROM invoices
                GROUP BY payment_status
            ');
            $stats['by_payment'] = $stmt->fetchAll();

            // Recent invoices
            $stmt = $this->db->query('
                SELECT i.*, o.name as operator_name
                FROM invoices i
                LEFT JOIN operators o ON i.operator_id = o.id
                ORDER BY i.created_at DESC
                LIMIT 10
            ');
            $stats['recent'] = $stmt->fetchAll();

            // Summary totals
            $stmt = $this->db->query('
                SELECT 
                    COUNT(*) as total_invoices,
                    SUM(total_amount) as total_revenue,
                    SUM(CASE WHEN status = "paid" THEN total_amount ELSE 0 END) as paid_revenue,
                    COUNT(DISTINCT operator_id) as total_operators
                FROM invoices
            ');
            $stats['summary'] = $stmt->fetch();

            return $stats;
        } catch (Exception $e) {
            logMessage("Error getting dashboard stats: " . $e->getMessage(), 'error');
            return [];
        }
    }

    /**
     * Delete invoice and its items
     */
    public function deleteInvoice($id)
    {
        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare('DELETE FROM payments WHERE invoice_id = ?');
            $stmt->execute([$id]);

            $stmt = $this->db->prepare('DELETE FROM email_logs WHERE invoice_id = ?');
            $stmt->execute([$id]);

            $stmt = $this->db->prepare('DELETE FROM invoice_items WHERE invoice_id = ?');
            $stmt->execute([$id]);

            $stmt = $this->db->prepare('DELETE FROM invoices WHERE id = ?');
            $stmt->execute([$id]);
            if ($stmt->rowCount() !== 1) {
                throw new RuntimeException('Invoice not found');
            }

            $this->db->commit();
            logMessage("Invoice {$id} deleted", 'info');
            return true;
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            logMessage("Error deleting invoice: " . $e->getMessage(), 'error');
            return false;
        }
    }
}
?>
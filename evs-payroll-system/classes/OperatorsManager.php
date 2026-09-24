<?php
/**
 * Operators Manager - Handle operator/employee management
 */

require_once __DIR__ . '/../database.php';

class OperatorsManager
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Add a new operator
     */
    public function addOperator($name, $email, $phone, $hourly_rate, $hours_per_day, $notes = '')
    {
        try {
            $stmt = $this->db->prepare('
                INSERT INTO operators (name, email, phone, hourly_rate, hours_per_day, notes)
                VALUES (?, ?, ?, ?, ?, ?)
            ');
            $stmt->execute([$name, $email, $phone, $hourly_rate, $hours_per_day, $notes]);
            logMessage("Operator added: {$name}", 'info');
            return $this->db->lastInsertId();
        } catch (Exception $e) {
            logMessage("Error adding operator: " . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Update operator details
     */
    public function updateOperator($id, $name, $email, $phone, $hourly_rate, $hours_per_day, $notes = '')
    {
        try {
            $stmt = $this->db->prepare('
                UPDATE operators 
                SET name = ?, email = ?, phone = ?, hourly_rate = ?, hours_per_day = ?, notes = ?, updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ');
            $stmt->execute([$name, $email, $phone, $hourly_rate, $hours_per_day, $notes, $id]);
            logMessage("Operator updated: {$name}", 'info');
            return true;
        } catch (Exception $e) {
            logMessage("Error updating operator: " . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Get operator by ID
     */
    public function getOperator($id)
    {
        try {
            $stmt = $this->db->prepare('SELECT * FROM operators WHERE id = ?');
            $stmt->execute([$id]);
            return $stmt->fetch();
        } catch (Exception $e) {
            logMessage("Error fetching operator: " . $e->getMessage(), 'error');
            return null;
        }
    }

    /**
     * Get all active operators
     */
    public function getAllOperators($status = 'active')
    {
        try {
            $stmt = $this->db->prepare('
                SELECT * FROM operators 
                WHERE status = ?
                ORDER BY name ASC
            ');
            $stmt->execute([$status]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            logMessage("Error fetching operators: " . $e->getMessage(), 'error');
            return [];
        }
    }

    /**
     * Delete operator (soft delete)
     */
    public function deleteOperator($id)
    {
        try {
            $stmt = $this->db->prepare('UPDATE operators SET status = ? WHERE id = ?');
            $stmt->execute(['archived', $id]);
            logMessage("Operator archived: ID {$id}", 'info');
            return true;
        } catch (Exception $e) {
            logMessage("Error deleting operator: " . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Get operator summary with invoice stats
     */
    public function getOperatorSummary($id)
    {
        try {
            $operator = $this->getOperator($id);

            $stmt = $this->db->prepare('
                SELECT 
                    COUNT(*) as total_invoices,
                    SUM(CASE WHEN status = "paid" THEN 1 ELSE 0 END) as paid_count,
                    SUM(CASE WHEN status = "unpaid" THEN 1 ELSE 0 END) as unpaid_count,
                    SUM(total_amount) as total_earned,
                    SUM(CASE WHEN status = "paid" THEN total_amount ELSE 0 END) as paid_amount
                FROM invoices 
                WHERE operator_id = ?
            ');
            $stmt->execute([$id]);
            $stats = $stmt->fetch();

            return array_merge($operator, $stats);
        } catch (Exception $e) {
            logMessage("Error getting operator summary: " . $e->getMessage(), 'error');
            return null;
        }
    }
}
?>
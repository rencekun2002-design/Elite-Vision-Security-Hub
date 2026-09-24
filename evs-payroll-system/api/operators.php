<?php
/**
 * API Endpoint: Operators Management
 * Handles CRUD operations for operators
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../classes/OperatorsManager.php';

$operators_mgr = new OperatorsManager();

$action = $_REQUEST['action'] ?? '';
$response = ['success' => false, 'message' => 'Unknown action'];

try {
    switch ($action) {
        case 'list':
            // Get all operators
            $status = $_GET['status'] ?? 'active';
            $operators = $operators_mgr->getAllOperators($status);
            $response = [
                'success' => true,
                'count' => count($operators),
                'operators' => $operators
            ];
            break;

        case 'get':
            // Get operator details
            $id = $_GET['id'] ?? 0;
            $operator = $operators_mgr->getOperator($id);

            if ($operator) {
                $response = [
                    'success' => true,
                    'operator' => $operator
                ];
            } else {
                $response = ['success' => false, 'message' => 'Operator not found'];
            }
            break;

        case 'get_summary':
            // Get operator with invoice summary
            $id = $_GET['id'] ?? 0;
            $summary = $operators_mgr->getOperatorSummary($id);

            if ($summary) {
                $response = [
                    'success' => true,
                    'operator' => $summary
                ];
            } else {
                $response = ['success' => false, 'message' => 'Operator not found'];
            }
            break;

        case 'create':
            // Create new operator
            $data = json_decode(file_get_contents('php://input'), true);

            if (!isset($data['name']) || !isset($data['hourly_rate'])) {
                $response = ['success' => false, 'message' => 'Missing required fields'];
                break;
            }

            $op_id = $operators_mgr->addOperator(
                $data['name'],
                $data['email'] ?? '',
                $data['phone'] ?? '',
                $data['hourly_rate'],
                $data['hours_per_day'] ?? 12,
                $data['notes'] ?? ''
            );

            if ($op_id) {
                $response = [
                    'success' => true,
                    'message' => 'Operator created successfully',
                    'operator_id' => $op_id
                ];
            } else {
                $response = ['success' => false, 'message' => 'Failed to create operator'];
            }
            break;

        case 'update':
            // Update operator
            $data = json_decode(file_get_contents('php://input'), true);

            if (!isset($data['id']) || !isset($data['name'])) {
                $response = ['success' => false, 'message' => 'Missing required fields'];
                break;
            }

            if (
                $operators_mgr->updateOperator(
                    $data['id'],
                    $data['name'],
                    $data['email'] ?? '',
                    $data['phone'] ?? '',
                    $data['hourly_rate'],
                    $data['hours_per_day'] ?? 12,
                    $data['notes'] ?? ''
                )
            ) {
                $response = [
                    'success' => true,
                    'message' => 'Operator updated successfully'
                ];
            } else {
                $response = ['success' => false, 'message' => 'Failed to update operator'];
            }
            break;

        case 'delete':
            // Soft delete operator
            $id = $_POST['id'] ?? 0;

            if ($operators_mgr->deleteOperator($id)) {
                $response = [
                    'success' => true,
                    'message' => 'Operator deleted successfully'
                ];
            } else {
                $response = ['success' => false, 'message' => 'Failed to delete operator'];
            }
            break;

        default:
            $response = ['success' => false, 'message' => 'Invalid action'];
    }
} catch (Exception $e) {
    logMessage('API Error: ' . $e->getMessage(), 'error');
    $response = [
        'success' => false,
        'message' => 'Server error',
        'error' => $e->getMessage()
    ];
}

echo json_encode($response);
?>
<?php
/**
 * Main Dashboard - Elite Vision Security Invoice System
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/classes/InvoicesManager.php';
require_once __DIR__ . '/classes/OperatorsManager.php';
require_once __DIR__ . '/classes/PaymentsManager.php';

$invoices_mgr = new InvoicesManager();
$operators_mgr = new OperatorsManager();
$payments_mgr = new PaymentsManager();

// Get dashboard statistics
$stats = $invoices_mgr->getDashboardStats();
$payment_summary = $payments_mgr->getPaymentSummary();
$operators = $operators_mgr->getAllOperators();
$payday_logs = $invoices_mgr->getAllInvoices();

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice Management System - Elite Vision Security</title>
    <style>
        :root {
            --ink: #0e1a10;
            --night: #12271a;
            --night-2: #1c3524;
            --brass: #b99a55;
            --brass-light: #e3cd93;
            --green-dark: #1f3d1a;
            --green-mid: #4f7942;
            --green-light: #8fbf6f;
            --green-row: #8ec46b;
            --border: #3f6b30;
            --bg: #f1f3ec;
            --panel: #ffffff;
            --text: #1a2418;
            --muted: #66705f;
        }

        * {
            box-sizing: border-box;
        }

        @import url('https://fonts.googleapis.com/css2?family=Oswald:wght@400;500;600;700&family=Inter:wght@400;500;600;700&display=swap');

        body {
            margin: 0;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Arial, sans-serif;
            background: var(--bg);
            color: var(--text);
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Masthead */
        .masthead {
            background: linear-gradient(180deg, var(--night) 0%, var(--night-2) 100%);
            margin: 0 -20px 24px;
            padding: 34px 20px 26px;
            border-bottom: 3px solid var(--brass);
        }

        .masthead-inner {
            max-width: 1360px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .brand-logo {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            object-fit: cover;
            flex-shrink: 0;
            border: 2px solid var(--brass-light);
            background: #fff;
        }

        .brand-text h1 {
            margin: 0;
            font-family: 'Oswald', sans-serif;
            font-weight: 600;
            font-size: 26px;
            letter-spacing: .06em;
            color: #f4f1e6;
            text-transform: uppercase;
        }

        .tagline {
            margin-top: 3px;
            font-size: 12px;
            letter-spacing: .18em;
            text-transform: uppercase;
            color: var(--brass-light);
            font-weight: 500;
        }

        .masthead-sub {
            margin-left: auto;
            text-align: right;
            font-size: 12px;
            color: #c9d3c0;
            letter-spacing: .03em;
        }

        /* Navigation */
        .nav-tabs {
            display: flex;
            gap: 0;
            border-bottom: 2px solid #e0e5db;
            margin-bottom: 24px;
        }

        .nav-tabs button {
            background: none;
            border: none;
            padding: 12px 20px;
            font-size: 14px;
            font-weight: 600;
            color: var(--muted);
            cursor: pointer;
            border-bottom: 3px solid transparent;
            text-transform: uppercase;
            letter-spacing: .05em;
            transition: all 0.3s ease;
        }

        .nav-tabs button:hover {
            color: var(--text);
        }

        .nav-tabs button.active {
            color: var(--night-2);
            border-bottom-color: var(--brass);
        }

        /* Content panels */
        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .panel {
            background: var(--panel);
            border: 1px solid #e0e5db;
            border-radius: 10px;
            padding: 18px;
            margin-bottom: 20px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
        }

        .panel h2 {
            font-family: 'Oswald', sans-serif;
            font-size: 14px;
            margin: 0 0 12px;
            color: var(--night-2);
            text-transform: uppercase;
            letter-spacing: .08em;
            font-weight: 600;
            border-bottom: 2px solid var(--brass);
            padding-bottom: 8px;
        }

        .panel h3 {
            font-size: 16px;
            margin: 16px 0 12px;
            color: var(--night-2);
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 20px;
        }

        .stat-card {
            background: linear-gradient(135deg, var(--night-2) 0%, var(--night) 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }

        .stat-card .label {
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .05em;
            opacity: 0.9;
            margin-bottom: 8px;
        }

        .stat-card .value {
            font-size: 28px;
            font-weight: 700;
            color: var(--brass-light);
            font-family: 'Oswald', sans-serif;
        }

        .stat-card .subtext {
            font-size: 11px;
            margin-top: 6px;
            opacity: 0.8;
        }

        /* Tables */
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
            margin-top: 12px;
        }

        thead {
            background: var(--green-dark);
            color: white;
        }

        th {
            padding: 10px;
            text-align: left;
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: .02em;
        }

        td {
            padding: 10px;
            border-bottom: 1px solid #e0e5db;
        }

        tr:hover {
            background-color: #fafbf7;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .02em;
        }

        .status-sent {
            background-color: #d1e7f0;
            color: #0c5460;
        }

        .status-paid {
            background-color: #d4edda;
            color: #155724;
        }

        .status-unpaid {
            background-color: #f8d7da;
            color: #721c24;
        }

        .status-draft {
            background-color: #e2e3e5;
            color: #383d41;
        }

        .status-partial {
            background-color: #fff3cd;
            color: #856404;
        }

        .status-partially_paid {
            background-color: #fff3cd;
            color: #856404;
        }

        .status-overdue {
            background-color: #f5c6cb;
            color: #721c24;
        }

        /* Buttons */
        button,
        .btn {
            cursor: pointer;
            border: none;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            padding: 9px 16px;
            transition: all 0.2s ease;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background: var(--night-2);
            color: #f4f1e6;
            border: 1px solid var(--night-2);
        }

        .btn-primary:hover {
            background: var(--night);
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        }

        .btn-secondary {
            background: #fff;
            color: var(--night-2);
            border: 1px solid var(--brass);
        }

        .btn-secondary:hover {
            background: #faf6ea;
        }

        .btn-small {
            padding: 6px 12px;
            font-size: 12px;
        }

        .btn-group {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        /* Forms */
        label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: var(--muted);
            margin-bottom: 4px;
        }

        input[type="text"],
        input[type="email"],
        input[type="number"],
        input[type="date"],
        select {
            width: 100%;
            padding: 8px 10px;
            border: 1px solid #d3dacb;
            border-radius: 6px;
            font-size: 14px;
            background: #fff;
        }

        input:focus,
        select:focus {
            outline: 2px solid var(--green-light);
            outline-offset: 1px;
        }

        .form-group {
            margin-bottom: 16px;
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 16px;
        }

        /* Alerts */
        .alert {
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 16px;
            font-size: 13px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .alert-info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }

        /* Empty state */
        .empty-state {
            text-align: center;
            color: var(--muted);
            padding: 40px 20px;
            font-size: 14px;
        }

        .empty-state svg {
            width: 64px;
            height: 64px;
            margin-bottom: 16px;
            opacity: 0.3;
        }

        /* Utility classes */
        .text-muted {
            color: var(--muted);
        }

        .text-success {
            color: #28a745;
        }

        .text-danger {
            color: #dc3545;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .mb-2 {
            margin-bottom: 8px;
        }

        .mb-3 {
            margin-bottom: 16px;
        }

        .mt-2 {
            margin-top: 8px;
        }

        .d-flex {
            display: flex;
        }

        .gap-2 {
            gap: 8px;
        }

        .align-center {
            align-items: center;
        }

        .justify-between {
            justify-content: space-between;
        }

        .money {
            font-weight: 600;
            color: var(--green-mid);
            font-family: 'Oswald', sans-serif;
        }

        /* Shared EVS workspace theme */
        body {
            background:
                radial-gradient(circle at 15% -10%, #e4f2e9 0%, transparent 45%),
                radial-gradient(circle at 100% 0%, #eaf3ec 0%, transparent 40%),
                #f3f5f3;
            color: #172420;
        }

        .masthead {
            background: #ffffff;
            margin: 0 -20px 18px;
            padding: 16px 20px;
            border-bottom: 1px solid #dde3de;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .brand-text h1 {
            color: #172420;
            font-family: "Segoe UI", system-ui, sans-serif;
            font-size: 17px;
            letter-spacing: .01em;
        }

        .tagline,
        .masthead-sub {
            color: #667b70;
            letter-spacing: .03em;
        }

        .nav-tabs {
            gap: 6px;
            background: #ffffff;
            padding: 5px;
            border: 1px solid #dde3de;
            border-radius: 999px;
            margin-bottom: 22px;
        }

        .nav-tabs button {
            border: none;
            border-radius: 999px;
            padding: 8px 14px;
            color: #667b70;
            text-transform: none;
            letter-spacing: 0;
            font-size: 13px;
        }

        .nav-tabs button.active {
            background: linear-gradient(135deg, #148a56, #0e6f44);
            color: #ffffff;
            border-bottom-color: transparent;
            box-shadow: 0 3px 10px rgba(20, 138, 86, .22);
        }

        .panel {
            border: 1px solid #dde3de;
            border-radius: 10px;
            box-shadow: 0 4px 16px rgba(23, 40, 30, .06);
        }

        .panel h2 {
            color: #1c2b22;
            border-bottom-color: #148a56;
        }

        .stat-card {
            background: #ffffff;
            color: #172420;
            border: 1px solid #dde3de;
            border-top: 3px solid #148a56;
            border-radius: 10px;
            text-align: left;
            box-shadow: 0 4px 16px rgba(23, 40, 30, .06);
        }

        .stat-card .value {
            color: #148a56;
        }

        .stat-card .subtext,
        .stat-card .label {
            color: #667b70;
        }

        thead {
            background: #1c2b22;
        }

        .btn-primary {
            background: linear-gradient(135deg, #148a56, #0e6f44);
            border-color: #0e6f44;
        }

        input[type="text"],
        input[type="email"],
        input[type="number"],
        input[type="date"],
        select {
            border-color: #dde3de;
            background: #f1f4f1;
        }

        .operator-form {
            display: none;
            margin-top: 18px;
            padding-top: 18px;
            border-top: 1px solid #dde3de;
        }

        .operator-form.is-visible {
            display: block;
        }

        .form-actions {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .form-message {
            min-height: 18px;
            margin: 10px 0 0;
            font-size: 13px;
            color: #667b70;
        }

        .work-day-row {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr auto;
            gap: 8px;
            margin-bottom: 8px;
        }

        @media (max-width: 768px) {
            .nav-tabs {
                border-radius: 10px;
                overflow-x: auto;
                flex-wrap: nowrap;
            }

            .nav-tabs button {
                flex: 0 0 auto;
            }

            .work-day-row {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .masthead-inner {
                flex-direction: column;
                align-items: flex-start;
            }

            .masthead-sub {
                margin-left: 0;
                text-align: left;
            }

            .grid {
                grid-template-columns: 1fr;
            }

            .nav-tabs {
                flex-wrap: wrap;
            }

            table {
                font-size: 12px;
            }

            th,
            td {
                padding: 8px;
            }
        }
    </style>
</head>

<body>

    <div class="masthead">
        <div class="masthead-inner container">
            <img src="assets/logo.jpeg" alt="Elite Vision Security logo" class="brand-logo">
            <div class="brand-text">
                <h1>Elite Vision Security</h1>
                <div class="tagline">Create and send operator invoices</div>
            </div>
            <div class="masthead-sub">Invoice workspace</div>
        </div>
    </div>

    <div class="container">

        <!-- Navigation Tabs -->
        <div class="nav-tabs">
            <button class="active" onclick="switchTab('create')">✦ Create Invoice</button>
            <button onclick="switchTab('payday-logs')">▣ Payday Logs</button>
            <button onclick="switchTab('edit-invoices')">✎ Edit Invoice</button>
            <button onclick="switchTab('operators')">♟ Operators</button>
            <button onclick="switchTab('consolidated')">📋 Consolidated Report</button>
        </div>

        <?php if (SMTP_USERNAME === 'your-email@gmail.com' || SMTP_PASSWORD === 'your-app-password'): ?>
            <div class="alert alert-info">Email is not configured yet. Update SMTP_USERNAME and SMTP_PASSWORD in config.php
                before sending invoices.</div>
        <?php endif; ?>

        <div id="payday-logs" class="tab-content">
            <div class="panel">
                <h2>Payday Logs</h2>
                <div class="d-flex justify-between align-center gap-2" style="flex-wrap: wrap;">
                    <p class="text-muted">A record of invoices created and sent for past pay periods.</p>
                    <div class="d-flex gap-2 align-center">
                        <button type="button" class="btn btn-secondary btn-small" onclick="toggleAllPaydayLogInvoices(this)">Select all</button>
                        <button type="button" class="btn btn-primary btn-small" id="sendSelectedInvoicesButton" onclick="sendSelectedInvoices()">Send selected emails</button>
                    </div>
                </div>
                <p id="paydayLogsMessage" class="form-message" role="status"></p>
                    <table id="paydayLogsTable" <?php echo empty($payday_logs) ? 'style="display: none;"' : ''; ?>>
                        <thead>
                            <tr>
                                <th><input type="checkbox" id="selectAllPaydayLogInvoices" aria-label="Select all invoices" onchange="toggleAllPaydayLogInvoices(this)"></th>
                                <th>Invoice #</th>
                                <th>Operator</th>
                                <th>Amount</th>
                                <th>Pay Period</th>
                                <th>Email</th>
                                <th>Created</th>
                                <th>Invoice</th>
                            </tr>
                        </thead>
                        <tbody id="paydayLogsBody">
                            <?php foreach ($payday_logs as $invoice): ?>
                                <tr data-invoice-id="<?php echo $invoice['id']; ?>" data-email-count="<?php echo $invoice['email_sent_count']; ?>">
                                    <td><input type="checkbox" class="payday-log-checkbox" value="<?php echo $invoice['id']; ?>" aria-label="Select invoice <?php echo htmlspecialchars($invoice['invoice_number']); ?>"></td>
                                    <td><strong><?php echo htmlspecialchars($invoice['invoice_number']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($invoice['operator_name'] ?? 'Unknown'); ?></td>
                                    <td class="money">$<?php echo number_format($invoice['total_amount'], 2); ?></td>
                                    <td><?php echo date('M d', strtotime($invoice['period_start'])) . ' - ' . date('M d, Y', strtotime($invoice['period_end'])); ?>
                                    </td>
                                    <td class="payday-log-email-status"><?php echo $invoice['email_sent_count'] > 0 ? 'Sent ' . $invoice['email_sent_count'] . 'x' : 'Not sent'; ?>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($invoice['created_at'])); ?></td>
                                    <td>
                                        <button class="btn btn-small btn-secondary"
                                            onclick="editInvoice(<?php echo $invoice['id']; ?>)">Edit</button>
                                        <button class="btn btn-small btn-primary"
                                            onclick="sendInvoiceEmail(<?php echo $invoice['id']; ?>)">Email</button>
                                        <button class="btn btn-small btn-secondary"
                                            onclick="downloadPDF(<?php echo $invoice['id']; ?>)">PDF</button>
                                        <button class="btn btn-small btn-danger"
                                            onclick="deletePaydayLog(<?php echo $invoice['id']; ?>)">Delete</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <div class="empty-state" id="paydayLogsEmpty" <?php echo empty($payday_logs) ? '' : 'style="display: none;"'; ?>>No payday records yet. Create your first invoice to start a history.</div>
            </div>
        </div>
    </div>

    <!-- INVOICES TAB -->
    <div id="invoices" class="tab-content">
        <div class="panel">
            <h2>Invoice Management</h2>
            <?php
            $all_invoices = $invoices_mgr->getAllInvoices();
            ?>
            <?php if (!empty($all_invoices)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Invoice #</th>
                            <th>Operator</th>
                            <th>Period</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Payment</th>
                            <th>Sent</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($all_invoices as $invoice): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($invoice['invoice_number']); ?></strong></td>
                                <td><?php echo htmlspecialchars($invoice['operator_name'] ?? 'Unknown'); ?></td>
                                <td>
                                    <?php
                                    echo date('M d', strtotime($invoice['period_start'])) . ' - ' .
                                        date('M d, Y', strtotime($invoice['period_end']));
                                    ?>
                                </td>
                                <td class="money">$<?php echo number_format($invoice['total_amount'], 2); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $invoice['status']; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $invoice['status'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo $invoice['payment_status']; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $invoice['payment_status'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php
                                    if ($invoice['email_sent_count'] > 0) {
                                        echo $invoice['email_sent_count'] . 'x';
                                    } else {
                                        echo '<span class="text-muted">Not sent</span>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <button class="btn btn-small btn-secondary"
                                            onclick="viewInvoice(<?php echo $invoice['id']; ?>)">View</button>
                                        <button class="btn btn-small btn-secondary"
                                            onclick="editInvoice(<?php echo $invoice['id']; ?>)">Edit</button>
                                        <button class="btn btn-small btn-secondary"
                                            onclick="downloadPDF(<?php echo $invoice['id']; ?>)">PDF</button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">No invoices found.</div>
            <?php endif; ?>
        </div>
    </div>

    <!-- OPERATORS TAB -->
    <div id="operators" class="tab-content">
        <div class="panel">
            <h2>Operators</h2>
            <button class="btn btn-primary"
                onclick="document.getElementById('operatorForm').reset(); document.getElementById('operatorIdValue').value=''; document.getElementById('operatorSubmit').textContent='Save Operator'; document.getElementById('operatorMessage').textContent=''; document.getElementById('operatorForm').classList.add('is-visible')">+
                Add New Operator</button>

            <form id="operatorForm" class="operator-form">
                <input type="hidden" id="operatorIdValue" value="">
                <div class="form-row">
                    <div class="form-group">
                        <label for="operatorName">Name</label>
                        <input type="text" id="operatorName" required>
                    </div>
                    <div class="form-group">
                        <label for="operatorEmail">Email</label>
                        <input type="email" id="operatorEmail">
                    </div>
                    <div class="form-group">
                        <label for="operatorPhone">Phone</label>
                        <input type="text" id="operatorPhone">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="operatorRate">Hourly Rate</label>
                        <input type="number" id="operatorRate" min="0" step="0.01" value="0" required>
                    </div>
                    <div class="form-group">
                        <label for="operatorHours">Hours Per Day</label>
                        <input type="number" id="operatorHours" min="0" step="0.5" value="12" required>
                    </div>
                    <div class="form-group">
                        <label for="operatorNotes">Notes</label>
                        <input type="text" id="operatorNotes">
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary" id="operatorSubmit">Save Operator</button>
                    <button type="button" class="btn btn-secondary"
                        onclick="document.getElementById('operatorForm').classList.remove('is-visible')">Cancel</button>
                </div>
                <p id="operatorMessage" class="form-message" role="status"></p>
            </form>

            <?php if (!empty($operators)): ?>
                <table style="margin-top: 20px;">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Hourly Rate</th>
                            <th>Hours/Day</th>
                            <th>Invoices</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($operators as $op):
                            $op_summary = $operators_mgr->getOperatorSummary($op['id']);
                            ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($op['name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($op['email'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($op['phone'] ?? '-'); ?></td>
                                <td class="money">$<?php echo number_format($op['hourly_rate'], 2); ?></td>
                                <td><?php echo number_format($op['hours_per_day'], 1); ?></td>
                                <td>
                                    <?php echo $op_summary['total_invoices'] ?? 0; ?> invoices
                                    <br><span class="text-muted"><?php echo $op_summary['paid_count'] ?? 0; ?> paid</span>
                                </td>
                                <td>
                                    <button class="btn btn-small btn-secondary"
                                        onclick="editOperator(<?php echo $op['id']; ?>)">Edit</button>
                                    <button class="btn btn-small btn-secondary"
                                        onclick="viewOperatorDetail(<?php echo $op['id']; ?>)">Detail</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">No operators found. Use Add New Operator to get started.</div>
            <?php endif; ?>
        </div>
    </div>

    <!-- PAYMENTS TAB -->
    <div id="payments" class="tab-content">
        <div class="panel">
            <h2>Payment Records</h2>
            <p>Track all payments received from operators. <button class="btn btn-primary"
                    onclick="window.location.href='payments.php'">+ Record
                    Payment</button></p>
            <p>This section allows you to record payments against invoices and track payment status.</p>
        </div>
    </div>

    <!-- CONSOLIDATED REPORT TAB -->
    <div id="consolidated" class="tab-content">
        <div class="panel">
            <h2>Consolidated Invoice Report</h2>
            <p class="text-muted">Generate a single PDF containing multiple invoices from a specific period.</p>

            <!-- Period Selection Controls -->
            <div class="period-selection"
                style="background: #f9fdf6; padding: 15px; border-radius: 4px; margin-bottom: 20px; border: 1px solid #d4e5c8;">
                <h3 style="margin-top: 0; font-size: 16px;">Select Period</h3>

                <div class="form-row" style="margin-bottom: 15px;">
                    <div style="flex: 1;">
                        <label><strong>Quick Filters</strong></label>
                        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                            <button type="button" class="btn btn-secondary" style="padding: 6px 12px; font-size: 13px;"
                                onclick="setPeriod('this-month')">This Month</button>
                            <button type="button" class="btn btn-secondary" style="padding: 6px 12px; font-size: 13px;"
                                onclick="setPeriod('last-month')">Last Month</button>
                            <button type="button" class="btn btn-secondary" style="padding: 6px 12px; font-size: 13px;"
                                onclick="setPeriod('last-30-days')">Last 30 Days</button>
                            <button type="button" class="btn btn-secondary" style="padding: 6px 12px; font-size: 13px;"
                                onclick="setPeriod('last-quarter')">Last Quarter</button>
                            <button type="button" class="btn btn-secondary" style="padding: 6px 12px; font-size: 13px;"
                                onclick="setPeriod('this-year')">This Year</button>
                        </div>
                    </div>
                </div>

                <div class="form-row" style="margin-bottom: 15px;">
                    <div class="form-group" style="flex: 1;">
                        <label for="monthSelector">Or Select Month</label>
                        <input type="month" id="monthSelector" onchange="setPeriod('month')">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group" style="flex: 1;">
                        <label for="consolidatedStartDate">Custom Date Range - From</label>
                        <input type="date" id="consolidatedStartDate">
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label for="consolidatedEndDate">To</label>
                        <input type="date" id="consolidatedEndDate">
                    </div>
                    <div class="form-group" style="display: flex; align-items: flex-end;">
                        <button type="button" class="btn btn-secondary" onclick="setPeriod('custom')">Apply Custom
                            Range</button>
                    </div>
                </div>
            </div>

            <!-- Invoices Preview -->
            <div id="consolidatedPreview" style="display: none;">
                <h3>Invoices Found: <span id="invoiceCount">0</span></h3>
                <p>Total Amount: <span id="totalAmount" class="money" style="font-weight: bold;">$0.00</span></p>

                <table id="consolidatedInvoicesList" style="margin-top: 15px;">
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="selectAllInvoices" onchange="toggleSelectAll(this)"></th>
                            <th>Invoice #</th>
                            <th>Operator</th>
                            <th>Amount</th>
                            <th>Pay Period</th>
                            <th>Created</th>
                        </tr>
                    </thead>
                    <tbody id="consolidatedInvoicesBody">
                    </tbody>
                </table>

                <div style="margin-top: 15px; display: flex; gap: 10px;">
                    <button type="button" class="btn btn-primary" onclick="generateConsolidatedPDF(this)">Generate
                        Consolidated PDF</button>
                    <button type="button" class="btn btn-secondary" onclick="clearConsolidatedSelection()">Clear
                        Selection</button>
                </div>
            </div>

            <div id="noInvoicesMessage" style="display: none; text-align: center; padding: 30px; color: #999;">
                <p>No invoices found for the selected period.</p>
            </div>

            <p id="consolidatedMessage" class="form-message" role="status"></p>
        </div>
    </div>

    <!-- CREATE INVOICE TAB -->
    <div id="create" class="tab-content active">
        <div class="panel" id="invoiceFormPanel">
            <h2 id="invoiceFormTitle">Create New Invoice</h2>
            <form id="createInvoiceForm">
                <input type="hidden" id="invoiceIdValue" value="">
                <div class="form-row">
                    <div class="form-group">
                        <label for="operatorId">Select Operator</label>
                        <select id="operatorId" required>
                            <option value="">-- Choose an operator --</option>
                            <?php foreach ($operators as $op): ?>
                                <option value="<?php echo $op['id']; ?>"
                                    data-rate="<?php echo htmlspecialchars($op['hourly_rate']); ?>"
                                    data-hours="<?php echo htmlspecialchars($op['hours_per_day']); ?>">
                                    <?php echo htmlspecialchars($op['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="periodStart">Period Start</label>
                        <input type="date" id="periodStart" required>
                    </div>

                    <div class="form-group">
                        <label for="periodEnd">Period End</label>
                        <input type="date" id="periodEnd" required>
                    </div>
                </div>

                <div id="daysContainer" style="display: none;">
                    <h3>Worked Days in Period</h3>
                    <p style="font-size: 13px; color: #666;">Check the days worked and adjust hours individually if
                        they vary. Rate: <strong>$<span id="operatorRateDisplay">0.00</span>/hr</strong></p>
                    <div id="daysCheckboxGrid"
                        style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 10px; margin: 15px 0;">
                    </div>
                    <div style="text-align: right; margin-top: 10px;">
                        <button type="button" class="btn btn-secondary" onclick="clearAllDays()"
                            style="margin-right: 10px;">Clear All</button>
                        <button type="button" class="btn btn-secondary" onclick="selectAllDays()">Select All</button>
                    </div>
                </div>

                <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #e0e5db;">
                    <button type="submit" class="btn btn-primary" id="invoiceSubmit">Create Invoice</button>
                    <button type="button" class="btn btn-secondary" onclick="resetInvoiceFields()">Reset operator &amp; hours</button>
                </div>
                <p id="invoiceMessage" class="form-message" role="status"></p>
            </form>
        </div>
    </div>

    <!-- EDIT INVOICE TAB -->
    <div id="edit-invoices" class="tab-content">
        <div class="panel" id="editInvoiceEmpty">
            <h2>Edit an Invoice</h2>
            <p class="text-muted">Open Payday Logs and select Edit beside the invoice you want to change.</p>
        </div>
        <div id="editInvoiceFormHost"></div>
    </div>

    <!-- REPORTS TAB -->
    <div id="reports" class="tab-content">
        <div class="panel">
            <h2>Reports & Analytics</h2>
            <p>This section provides detailed reports and analytics about your invoicing system.</p>

            <h3>Invoice Statistics by Status</h3>
            <?php if (!empty($stats['by_status'])): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Status</th>
                            <th>Count</th>
                            <th>Total Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stats['by_status'] as $row): ?>
                            <tr>
                                <td>
                                    <span class="status-badge status-<?php echo $row['status']; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $row['status'])); ?>
                                    </span>
                                </td>
                                <td><?php echo $row['count']; ?></td>
                                <td class="money">$<?php echo number_format($row['total'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    </div>

    <script>
        function switchTab(tabName) {
            const formPanel = document.getElementById('invoiceFormPanel');
            if (tabName === 'create' && formPanel.parentElement.id === 'editInvoiceFormHost') {
                document.getElementById('create').appendChild(formPanel);
                document.getElementById('editInvoiceEmpty').style.display = '';
                restoreInvoicePeriod();
                resetInvoiceFields();
            }

            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });

            // Show selected tab
            document.getElementById(tabName).classList.add('active');

            // Update button states
            document.querySelectorAll('.nav-tabs button').forEach(btn => {
                btn.classList.remove('active');
            });
            const activeButton = Array.from(document.querySelectorAll('.nav-tabs button'))
                .find(btn => btn.getAttribute('onclick')?.includes(`'${tabName}'`));
            if (activeButton) activeButton.classList.add('active');
        }

        function viewInvoice(id) {
            window.open(`api/generate_pdf.php?id=${encodeURIComponent(id)}&inline=1`, '_blank', 'noopener');
        }

        function editInvoice(id) {
            switchTab('edit-invoices');
            document.getElementById('editInvoiceFormHost').appendChild(document.getElementById('invoiceFormPanel'));
            document.getElementById('editInvoiceEmpty').style.display = 'none';
            const form = document.getElementById('createInvoiceForm');
            const message = document.getElementById('invoiceMessage');
            form.reset();
            document.getElementById('invoiceIdValue').value = id;
            document.getElementById('invoiceFormTitle').textContent = 'Edit Invoice';
            document.getElementById('invoiceSubmit').textContent = 'Update Invoice';
            if (message) message.textContent = 'Loading invoice...';

            fetch(`api/invoices.php?action=get&id=${encodeURIComponent(id)}`)
                .then(response => response.json())
                .then(result => {
                    if (!result.success) throw new Error(result.message || 'Unable to load invoice');
                    const invoice = result.invoice;
                    document.getElementById('operatorId').value = invoice.operator_id;
                    document.getElementById('periodStart').value = invoice.period_start;
                    document.getElementById('periodEnd').value = invoice.period_end;
                    updateOperatorRateDisplay();

                    // Trigger date checkbox generation
                    generateDateCheckboxes();

                    // Restore each day's checked state and its actual hours (may vary per day)
                    const hoursByDate = new Map(invoice.items.map(item => [item.work_date, item.hours]));
                    document.querySelectorAll('.work-date-row').forEach(row => {
                        const checkbox = row.querySelector('.work-date-checkbox');
                        const hoursInput = row.querySelector('.work-date-hours');
                        if (hoursByDate.has(checkbox.value)) {
                            checkbox.checked = true;
                            hoursInput.value = hoursByDate.get(checkbox.value);
                        } else {
                            checkbox.checked = false;
                        }
                    });

                    if (message) message.textContent = '';
                })
                .catch(error => { if (message) message.textContent = error.message; });
        }

        function downloadPDF(id) {
            window.location.href = `api/generate_pdf.php?id=${id}`;
        }

        async function sendInvoiceEmail(id) {
            if (!confirm('Send this invoice to the operator?')) return;
            try {
                const response = await fetch('api/invoices.php?action=send_email', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `id=${encodeURIComponent(id)}`
                });
                const result = await response.json();
                if (!result.success) throw new Error(result.message || 'Unable to send email');
                alert('Invoice emailed successfully.');
                window.location.reload();
            } catch (error) {
                alert(error.message);
            }
        }

        async function deletePaydayLog(id) {
            if (!confirm('Delete this payday log and its invoice? This cannot be undone.')) return;
            try {
                const response = await fetch('api/invoices.php?action=delete', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `id=${encodeURIComponent(id)}`
                });
                const result = await response.json();
                if (!result.success) throw new Error(result.message || 'Unable to delete record');
                window.location.reload();
            } catch (error) {
                alert(error.message || 'Unable to delete record');
            }
        }

        function editOperator(id) {
            switchTab('operators');
            const form = document.getElementById('operatorForm');
            const message = document.getElementById('operatorMessage');
            form.classList.add('is-visible');
            message.textContent = 'Loading operator...';
            fetch(`api/operators.php?action=get&id=${encodeURIComponent(id)}`)
                .then(response => response.json())
                .then(result => {
                    if (!result.success) throw new Error(result.message || 'Unable to load operator');
                    const operator = result.operator;
                    document.getElementById('operatorIdValue').value = operator.id;
                    document.getElementById('operatorName').value = operator.name || '';
                    document.getElementById('operatorEmail').value = operator.email || '';
                    document.getElementById('operatorPhone').value = operator.phone || '';
                    document.getElementById('operatorRate').value = operator.hourly_rate || 0;
                    document.getElementById('operatorHours').value = operator.hours_per_day || 12;
                    document.getElementById('operatorNotes').value = operator.notes || '';
                    document.getElementById('operatorSubmit').textContent = 'Update Operator';
                    message.textContent = '';
                })
                .catch(error => { message.textContent = error.message; });
        }

        function viewOperatorDetail(id) {
            window.location.href = `?action=view_operator&id=${id}`;
        }

        document.getElementById('operatorForm').addEventListener('submit', async function (e) {
            e.preventDefault();
            const message = document.getElementById('operatorMessage');
            message.textContent = 'Saving operator...';
            const operatorId = document.getElementById('operatorIdValue').value;

            try {
                const response = await fetch(`api/operators.php?action=${operatorId ? 'update' : 'create'}`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        id: operatorId ? Number(operatorId) : undefined,
                        name: document.getElementById('operatorName').value.trim(),
                        email: document.getElementById('operatorEmail').value.trim(),
                        phone: document.getElementById('operatorPhone').value.trim(),
                        hourly_rate: Number(document.getElementById('operatorRate').value),
                        hours_per_day: Number(document.getElementById('operatorHours').value),
                        notes: document.getElementById('operatorNotes').value.trim()
                    })
                });
                const result = await response.json();
                if (!result.success) throw new Error(result.message || 'Unable to save operator');
                message.textContent = operatorId ? 'Operator updated.' : 'Operator saved.';
                window.location.reload();
            } catch (error) {
                message.textContent = error.message;
            }
        });

        const invoicePeriodStorageKey = 'evs-payroll-invoice-period';

        function saveInvoicePeriod() {
            localStorage.setItem(invoicePeriodStorageKey, JSON.stringify({
                start: document.getElementById('periodStart').value,
                end: document.getElementById('periodEnd').value
            }));
        }

        function restoreInvoicePeriod() {
            const storedPeriod = localStorage.getItem(invoicePeriodStorageKey);
            if (!storedPeriod) return;

            try {
                const period = JSON.parse(storedPeriod);
                if (period.start) document.getElementById('periodStart').value = period.start;
                if (period.end) document.getElementById('periodEnd').value = period.end;
            } catch (error) {
                localStorage.removeItem(invoicePeriodStorageKey);
            }
        }

        // Regenerate worked days and retain the selected pay period between page reloads.
        document.getElementById('periodStart').addEventListener('change', function () {
            saveInvoicePeriod();
            generateDateCheckboxes();
        });
        document.getElementById('periodEnd').addEventListener('change', function () {
            saveInvoicePeriod();
            generateDateCheckboxes();
        });
        document.getElementById('operatorId').addEventListener('change', function () {
            if (this.value) {
                generateDateCheckboxes();
            }
            updateOperatorRateDisplay();
        });

        function updateOperatorRateDisplay() {
            const operator = document.getElementById('operatorId').selectedOptions[0];
            document.getElementById('operatorRateDisplay').textContent =
                parseFloat((operator && operator.dataset.rate) || 0).toFixed(2);
        }

        function generateDateCheckboxes() {
            const startStr = document.getElementById('periodStart').value;
            const endStr = document.getElementById('periodEnd').value;
            const grid = document.getElementById('daysCheckboxGrid');
            const container = document.getElementById('daysContainer');

            if (!startStr || !endStr) {
                container.style.display = 'none';
                return;
            }

            const startDate = parseLocalDate(startStr);
            const endDate = parseLocalDate(endStr);

            if (startDate > endDate) {
                container.style.display = 'none';
                return;
            }

            const operatorOption = document.getElementById('operatorId').selectedOptions[0];
            const defaultHours = (operatorOption && operatorOption.dataset.hours) || 12;
            grid.innerHTML = '';
            const currentDate = new Date(startDate);

            while (currentDate <= endDate) {
                const dateStr = formatLocalDate(currentDate);
                const dateObj = new Date(currentDate);
                const dayName = dateObj.toLocaleDateString('en-US', { weekday: 'short' });
                const displayDate = dateObj.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });

                const row = document.createElement('div');
                row.className = 'work-date-row';
                row.style.display = 'flex';
                row.style.alignItems = 'center';
                row.style.gap = '8px';
                row.style.padding = '8px';
                row.style.borderRadius = '4px';
                row.style.backgroundColor = '#f9fdf6';
                row.innerHTML = `
                    <label style="display: flex; align-items: center; gap: 8px; flex: 1; cursor: pointer; margin: 0;">
                        <input type="checkbox" class="work-date-checkbox" value="${dateStr}" data-display="${dayName} ${displayDate}">
                        <span style="font-size: 13px;">${dayName} ${displayDate}</span>
                    </label>
                    <input type="number" class="work-date-hours" min="0" step="0.5" value="${defaultHours}"
                        style="width: 70px; padding: 4px 6px;" title="Hours worked on this day">
                `;
                grid.appendChild(row);
                currentDate.setDate(currentDate.getDate() + 1);
            }

            container.style.display = 'block';
        }

        function clearAllDays() {
            document.querySelectorAll('.work-date-checkbox').forEach(cb => cb.checked = false);
        }

        function parseLocalDate(dateString) {
            const [year, month, day] = dateString.split('-').map(Number);
            return new Date(year, month - 1, day);
        }

        function formatLocalDate(date) {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        }

        function selectAllDays() {
            document.querySelectorAll('.work-date-checkbox').forEach(cb => cb.checked = true);
        }

        function resetInvoiceFields() {
            document.getElementById('operatorId').value = '';
            document.getElementById('operatorRateDisplay').textContent = '0.00';
            document.getElementById('invoiceIdValue').value = '';
            document.getElementById('invoiceFormTitle').textContent = 'Create New Invoice';
            document.getElementById('invoiceSubmit').textContent = 'Create Invoice';
            document.getElementById('invoiceMessage').textContent = '';
            generateDateCheckboxes();
            clearAllDays();
        }

        document.getElementById('createInvoiceForm').addEventListener('submit', async function (e) {
            e.preventDefault();
            const invoiceId = document.getElementById('invoiceIdValue').value;
            const operatorOption = document.getElementById('operatorId').selectedOptions[0];
            const operatorRate = parseFloat(operatorOption.dataset.rate) || 0;

            const items = Array.from(document.querySelectorAll('.work-date-row')).filter(row => {
                return row.querySelector('.work-date-checkbox').checked;
            }).map(row => {
                const hours = parseFloat(row.querySelector('.work-date-hours').value) || 0;
                return {
                    work_date: row.querySelector('.work-date-checkbox').value,
                    hours: hours,
                    hourly_rate: operatorRate,
                    line_total: hours * operatorRate
                };
            });

            if (items.length === 0) {
                alert('Please select at least one day.');
                return;
            }
            if (items.some(item => item.hours <= 0)) {
                alert('Please enter a valid number of hours for each selected day.');
                return;
            }

            showInvoicePreviewModal({
                invoiceId,
                operatorName: operatorOption.textContent.trim(),
                periodStart: document.getElementById('periodStart').value,
                periodEnd: document.getElementById('periodEnd').value,
                items,
                operatorRate
            });
        });

        let pendingInvoicePayload = null;

        function showInvoicePreviewModal(payload) {
            pendingInvoicePayload = payload;
            const subtotal = payload.items.reduce((sum, item) => sum + item.line_total, 0);
            const totalHours = payload.items.reduce((sum, item) => sum + item.hours, 0);

            document.getElementById('invoicePreviewOperator').textContent = payload.operatorName;
            document.getElementById('invoicePreviewPeriod').textContent =
                parseLocalDate(payload.periodStart).toLocaleDateString() + ' - ' + parseLocalDate(payload.periodEnd).toLocaleDateString();
            document.getElementById('invoicePreviewDays').textContent = payload.items.length;
            document.getElementById('invoicePreviewHours').textContent = totalHours;
            document.getElementById('invoicePreviewRate').textContent = payload.operatorRate.toFixed(2);
            document.getElementById('invoicePreviewTotal').textContent = subtotal.toFixed(2);

            const list = document.getElementById('invoicePreviewDaysList');
            list.innerHTML = payload.items.map(item =>
                `<li>${parseLocalDate(item.work_date).toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric' })} — ${item.hours}h × $${payload.operatorRate.toFixed(2)} = $${item.line_total.toFixed(2)}</li>`
            ).join('');

            const confirmButton = document.getElementById('confirmInvoiceButton');
            confirmButton.disabled = false;
            confirmButton.textContent = payload.invoiceId ? 'Update Invoice' : 'Create Invoice';
            document.getElementById('invoicePreviewModal').style.display = 'flex';
        }

        function closeInvoicePreviewModal() {
            document.getElementById('invoicePreviewModal').style.display = 'none';
        }

        async function confirmInvoiceSubmit(button) {
            if (!pendingInvoicePayload) return;
            const payload = pendingInvoicePayload;
            button.disabled = true;
            button.textContent = payload.invoiceId ? 'Updating...' : 'Creating...';

            try {
                const response = await fetch(`api/invoices.php?action=${payload.invoiceId ? 'update' : 'create'}`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        id: payload.invoiceId ? Number(payload.invoiceId) : undefined,
                        operator_id: Number(document.getElementById('operatorId').value),
                        period_start: payload.periodStart,
                        period_end: payload.periodEnd,
                        items: payload.items,
                        tax_rate: 0
                    })
                });
                const result = await response.json();
                if (!result.success) throw new Error(result.message || 'Unable to create invoice');

                closeInvoicePreviewModal();
                if (payload.invoiceId) {
                    // For updates, show email preview modal
                    showEmailPreviewModal(result.invoice_id);
                } else {
                    await addInvoiceToPaydayLogs(result.invoice_id);
                    resetInvoiceFields();
                    pendingInvoicePayload = null;
                    document.getElementById('invoiceMessage').textContent = 'Invoice created and added to Payday Logs.';
                    document.getElementById('operatorId').focus();
                }
            } catch (error) {
                alert(error.message);
                button.disabled = false;
                button.textContent = payload.invoiceId ? 'Update Invoice' : 'Create Invoice';
            }
        }

        async function addInvoiceToPaydayLogs(invoiceId) {
            const response = await fetch(`api/invoices.php?action=get&id=${encodeURIComponent(invoiceId)}`);
            const result = await response.json();
            if (!result.success) throw new Error(result.message || 'Invoice was created, but could not be added to Payday Logs.');

            const tbody = document.getElementById('paydayLogsBody');
            document.getElementById('paydayLogsTable').style.display = '';
            document.getElementById('paydayLogsEmpty').style.display = 'none';

            const invoice = result.invoice;
            const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, character => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            })[character]);
            const formatMoney = (value) => Number(value || 0).toFixed(2);
            const formatPeriod = (start, end) => {
                const startDate = parseLocalDate(start);
                const endDate = parseLocalDate(end);
                return `${startDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })} - ${endDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}`;
            };

            const row = document.createElement('tr');
            row.dataset.invoiceId = invoice.id;
            row.dataset.emailCount = invoice.email_sent_count || 0;
            row.innerHTML = `
                <td><input type="checkbox" class="payday-log-checkbox" value="${Number(invoice.id)}" aria-label="Select invoice ${escapeHtml(invoice.invoice_number)}"></td>
                <td><strong>${escapeHtml(invoice.invoice_number)}</strong></td>
                <td>${escapeHtml(invoice.operator_name || 'Unknown')}</td>
                <td class="money">$${formatMoney(invoice.total_amount)}</td>
                <td>${formatPeriod(invoice.period_start, invoice.period_end)}</td>
                <td class="payday-log-email-status">Not sent</td>
                <td>${new Date().toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}</td>
                <td>
                    <button class="btn btn-small btn-secondary" onclick="editInvoice(${Number(invoice.id)})">Edit</button>
                    <button class="btn btn-small btn-primary" onclick="sendInvoiceEmail(${Number(invoice.id)})">Email</button>
                    <button class="btn btn-small btn-secondary" onclick="downloadPDF(${Number(invoice.id)})">PDF</button>
                    <button class="btn btn-small btn-danger" onclick="deletePaydayLog(${Number(invoice.id)})">Delete</button>
                </td>`;
            tbody.prepend(row);
        }

        function toggleAllPaydayLogInvoices(control) {
            const selectAll = document.getElementById('selectAllPaydayLogInvoices');
            const shouldSelect = control === selectAll ? control.checked : !selectAll.checked;
            document.querySelectorAll('.payday-log-checkbox').forEach(checkbox => {
                checkbox.checked = shouldSelect;
            });
            selectAll.checked = shouldSelect;
        }

        async function sendSelectedInvoices() {
            const selected = Array.from(document.querySelectorAll('.payday-log-checkbox:checked'));
            const message = document.getElementById('paydayLogsMessage');
            const button = document.getElementById('sendSelectedInvoicesButton');
            if (selected.length === 0) {
                message.textContent = 'Select at least one invoice to email.';
                return;
            }
            if (!confirm(`Send ${selected.length} selected invoice${selected.length === 1 ? '' : 's'} by email?`)) return;

            button.disabled = true;
            document.querySelectorAll('.payday-log-checkbox, #selectAllPaydayLogInvoices').forEach(checkbox => {
                checkbox.disabled = true;
            });
            let sent = 0;
            const failures = [];

            for (let index = 0; index < selected.length; index++) {
                const checkbox = selected[index];
                const invoiceId = checkbox.value;
                message.textContent = `Sending ${index + 1} of ${selected.length}...`;
                try {
                    const response = await fetch('api/invoices.php?action=send_email', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: `id=${encodeURIComponent(invoiceId)}`
                    });
                    const result = await response.json();
                    if (!result.success) throw new Error(result.message || 'Unable to send email');

                    const row = checkbox.closest('tr');
                    const emailCount = Number(row.dataset.emailCount || 0) + 1;
                    row.dataset.emailCount = emailCount;
                    row.querySelector('.payday-log-email-status').textContent = `Sent ${emailCount}x`;
                    checkbox.checked = false;
                    sent++;
                } catch (error) {
                    failures.push(`Invoice ${invoiceId}: ${error.message}`);
                }
            }

            document.querySelectorAll('.payday-log-checkbox, #selectAllPaydayLogInvoices').forEach(checkbox => {
                checkbox.disabled = false;
            });
            document.getElementById('selectAllPaydayLogInvoices').checked = false;
            button.disabled = false;
            message.textContent = failures.length
                ? `${sent} sent. ${failures.length} failed: ${failures.join(' ')}`
                : `${sent} invoice${sent === 1 ? '' : 's'} emailed successfully.`;
        }

        function showEmailPreviewModal(invoiceId) {
            const modal = document.getElementById('emailPreviewModal');
            if (!modal) {
                console.error('Email preview modal not found');
                window.location.href = '?created=1#payday-logs';
                return;
            }

            fetch(`api/invoices.php?action=get&id=${invoiceId}`)
                .then(r => r.json())
                .then(result => {
                    if (!result.success) throw new Error('Unable to load invoice');
                    const invoice = result.invoice;

                    document.getElementById('previewInvoiceNumber').textContent = invoice.invoice_number;
                    document.getElementById('previewOperatorName').textContent = invoice.operator_name || 'Unknown';
                    document.getElementById('previewOperatorEmail').textContent = invoice.operator_email || '(No email)';
                    document.getElementById('previewAmount').textContent = '$' + parseFloat(invoice.total_amount).toFixed(2);
                    document.getElementById('previewPeriod').textContent = parseLocalDate(invoice.period_start).toLocaleDateString() + ' - ' + parseLocalDate(invoice.period_end).toLocaleDateString();

                    // Store invoice ID for confirmation
                    document.getElementById('emailPreviewInvoiceId').value = invoiceId;
                    modal.style.display = 'flex';
                })
                .catch(error => {
                    alert('Error loading invoice preview: ' + error.message);
                    window.location.href = '?created=1#payday-logs';
                });
        }

        function closeEmailPreviewModal() {
            const modal = document.getElementById('emailPreviewModal');
            if (modal) modal.style.display = 'none';
        }

        function confirmAndSendEmail(button) {
            const invoiceId = document.getElementById('emailPreviewInvoiceId').value;
            button.disabled = true;
            button.textContent = 'Sending...';

            fetch('api/invoices.php?action=send_update_email', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `id=${invoiceId}`
            })
                .then(r => r.json())
                .then(result => {
                    if (result.success) {
                        closeEmailPreviewModal();
                        alert('Invoice updated and email sent successfully.');
                    } else {
                        alert('Unable to send email: ' + (result.message || 'Unknown error'));
                    }
                    window.location.href = '?created=1#payday-logs';
                })
                .catch(error => {
                    alert('Error: ' + error.message);
                    button.disabled = false;
                    button.textContent = 'Send Email';
                });
        }

        function skipEmailAndClose() {
            closeEmailPreviewModal();
            window.location.href = '?created=1#payday-logs';
        }

        // Consolidated Report Functions
        function setPeriod(type) {
            const today = new Date();
            let startDate, endDate = today;
            const message = document.getElementById('consolidatedMessage');

            switch (type) {
                case 'this-month':
                    startDate = new Date(today.getFullYear(), today.getMonth(), 1);
                    endDate = new Date(today.getFullYear(), today.getMonth() + 1, 0);
                    break;
                case 'last-month':
                    startDate = new Date(today.getFullYear(), today.getMonth() - 1, 1);
                    endDate = new Date(today.getFullYear(), today.getMonth(), 0);
                    break;
                case 'last-30-days':
                    startDate = new Date(today);
                    startDate.setDate(today.getDate() - 30);
                    break;
                case 'last-quarter':
                    const currentQuarter = Math.floor(today.getMonth() / 3);
                    startDate = new Date(today.getFullYear(), currentQuarter * 3 - 3, 1);
                    endDate = new Date(today.getFullYear(), currentQuarter * 3, 0);
                    break;
                case 'this-year':
                    startDate = new Date(today.getFullYear(), 0, 1);
                    endDate = today;
                    break;
                case 'month':
                    const monthValue = document.getElementById('monthSelector').value;
                    if (!monthValue) {
                        message.textContent = 'Please select a month.';
                        message.style.color = 'red';
                        return;
                    }
                    const [year, month] = monthValue.split('-');
                    startDate = new Date(year, parseInt(month) - 1, 1);
                    endDate = new Date(year, parseInt(month), 0);
                    break;
                case 'custom':
                    const customStart = document.getElementById('consolidatedStartDate').value;
                    const customEnd = document.getElementById('consolidatedEndDate').value;
                    if (!customStart || !customEnd) {
                        message.textContent = 'Please provide both start and end dates.';
                        message.style.color = 'red';
                        return;
                    }
                    startDate = parseLocalDate(customStart);
                    endDate = parseLocalDate(customEnd);
                    if (startDate > endDate) {
                        message.textContent = 'The start date must be before the end date.';
                        message.style.color = 'red';
                        return;
                    }
                    break;
                default:
                    return;
            }

            // Format dates for API
            const start = formatLocalDate(startDate);
            const end = formatLocalDate(endDate);

            // Fetch invoices for the period
            fetchInvoicesForPeriod(start, end);
        }

        function fetchInvoicesForPeriod(startDate, endDate) {
            const message = document.getElementById('consolidatedMessage');
            const preview = document.getElementById('consolidatedPreview');
            const noInvoices = document.getElementById('noInvoicesMessage');

            message.textContent = 'Loading invoices...';
            message.style.color = '#666';

            fetch(`api/consolidated_invoices.php?action=list&start_date=${startDate}&end_date=${endDate}`)
                .then(response => response.json())
                .then(result => {
                    if (!result.success) throw new Error(result.error || 'Unable to load invoices');

                    const invoices = result.invoices || [];
                    if (invoices.length === 0) {
                        preview.style.display = 'none';
                        noInvoices.style.display = 'block';
                        message.textContent = 'No invoices found for this period.';
                        message.style.color = '#999';
                    } else {
                        preview.style.display = 'block';
                        noInvoices.style.display = 'none';
                        document.getElementById('invoiceCount').textContent = invoices.length;
                        document.getElementById('totalAmount').textContent = '$' + parseFloat(result.total_amount).toFixed(2);

                        const tbody = document.getElementById('consolidatedInvoicesBody');
                        tbody.innerHTML = invoices.map(inv => `
                            <tr>
                                <td><input type="checkbox" class="invoice-checkbox" value="${inv.id}"></td>
                                <td><strong>${inv.invoice_number}</strong></td>
                                <td>${inv.operator_name || 'Unknown'}</td>
                                <td class="money">$${parseFloat(inv.total_amount).toFixed(2)}</td>
                                <td>${parseLocalDate(inv.period_start).toLocaleDateString()} - ${parseLocalDate(inv.period_end).toLocaleDateString()}</td>
                                <td>${new Date(inv.created_at).toLocaleDateString()}</td>
                            </tr>
                        `).join('');

                        // Select all checkboxes by default
                        document.querySelectorAll('.invoice-checkbox').forEach(cb => cb.checked = true);
                        message.textContent = '';
                    }
                })
                .catch(error => {
                    message.textContent = 'Error: ' + error.message;
                    message.style.color = 'red';
                    preview.style.display = 'none';
                    noInvoices.style.display = 'none';
                });
        }

        function toggleSelectAll(checkbox) {
            document.querySelectorAll('.invoice-checkbox').forEach(cb => cb.checked = checkbox.checked);
        }

        function generateConsolidatedPDF(button) {
            const selected = Array.from(document.querySelectorAll('.invoice-checkbox:checked'))
                .map(cb => parseInt(cb.value));

            if (selected.length === 0) {
                alert('Please select at least one invoice.');
                return;
            }

            button.disabled = true;
            button.textContent = 'Generating PDF...';

            const message = document.getElementById('consolidatedMessage');
            message.textContent = 'Generating consolidated PDF...';
            message.style.color = '#666';

            const periodName = document.getElementById('monthSelector').value || new Date().toLocaleDateString();
            const idsParam = selected.map(id => `invoice_ids[]=${id}`).join('&');

            fetch('api/consolidated_invoices.php?action=generate_pdf', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `${idsParam}&period_name=${encodeURIComponent(periodName)}`
            })
                .then(response => response.json())
                .then(result => {
                    if (!result.success) throw new Error(result.error || 'Unable to generate PDF');
                    message.textContent = 'PDF generated successfully!';
                    message.style.color = 'green';

                    // Download the PDF
                    window.location.href = `api/download_consolidated_pdf.php?path=${encodeURIComponent(result.filepath)}`;
                })
                .catch(error => {
                    message.textContent = 'Error: ' + error.message;
                    message.style.color = 'red';
                })
                .finally(() => {
                    button.disabled = false;
                    button.textContent = 'Generate Consolidated PDF';
                });
        }

        function clearConsolidatedSelection() {
            document.getElementById('consolidatedStartDate').value = '';
            document.getElementById('consolidatedEndDate').value = '';
            document.getElementById('monthSelector').value = '';
            document.getElementById('consolidatedPreview').style.display = 'none';
            document.getElementById('noInvoicesMessage').style.display = 'none';
            document.getElementById('consolidatedMessage').textContent = '';
            document.getElementById('selectAllInvoices').checked = false;
        }

        restoreInvoicePeriod();
        generateDateCheckboxes();
        if (window.location.hash === '#payday-logs') switchTab('payday-logs');
    </script>

    <!-- Invoice Preview Modal (shown before create/update submission) -->
    <div id="invoicePreviewModal"
        style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); justify-content: center; align-items: center; z-index: 10000;">
        <div
            style="background: white; border-radius: 8px; padding: 30px; max-width: 520px; width: 90%; max-height: 85vh; overflow-y: auto; box-shadow: 0 4px 20px rgba(0,0,0,0.15);">
            <h2 style="margin-top: 0; color: #1f3d1a;">Invoice Preview</h2>
            <p style="color: #666; font-size: 14px;">Review the details below before saving this invoice.</p>

            <div
                style="background: #f9fdf6; padding: 15px; border-radius: 4px; border-left: 4px solid #8ec46b; margin: 20px 0;">
                <div style="margin-bottom: 10px;">
                    <strong style="display: block; font-size: 12px; color: #999;">Operator</strong>
                    <span id="invoicePreviewOperator" style="font-size: 14px; color: #1a2418;">-</span>
                </div>
                <div style="margin-bottom: 10px;">
                    <strong style="display: block; font-size: 12px; color: #999;">Period</strong>
                    <span id="invoicePreviewPeriod" style="font-size: 14px; color: #1a2418;">-</span>
                </div>
                <div style="margin-bottom: 10px;">
                    <strong style="display: block; font-size: 12px; color: #999;">Days Worked</strong>
                    <span id="invoicePreviewDays" style="font-size: 14px; color: #1a2418;">0</span>
                    (<span id="invoicePreviewHours">0</span> total hrs) @ $<span id="invoicePreviewRate">0.00</span>/hr
                </div>
                <ul id="invoicePreviewDaysList" style="font-size: 12px; color: #666; margin: 0 0 10px; padding-left: 18px; max-height: 150px; overflow-y: auto;"></ul>
                <div style="margin-bottom: 0;">
                    <strong style="display: block; font-size: 12px; color: #999;">Total Amount</strong>
                    <span id="invoicePreviewTotal" style="font-size: 18px; font-weight: 600; color: #1f3d1a;">$0.00</span>
                </div>
            </div>

            <div style="display: flex; gap: 10px; justify-content: flex-end;">
                <button onclick="closeInvoicePreviewModal()" class="btn btn-secondary" style="padding: 10px 20px;">Back
                    & Edit</button>
                <button id="confirmInvoiceButton" onclick="confirmInvoiceSubmit(this)" class="btn btn-primary" style="padding: 10px 20px;">Create Invoice</button>
            </div>
        </div>
    </div>

    <!-- Email Preview Modal -->
    <div id="emailPreviewModal"
        style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); justify-content: center; align-items: center; z-index: 10000;">
        <div
            style="background: white; border-radius: 8px; padding: 30px; max-width: 500px; box-shadow: 0 4px 20px rgba(0,0,0,0.15);">
            <h2 style="margin-top: 0; color: #1f3d1a;">Invoice Updated</h2>
            <p style="color: #666; font-size: 14px;">Would you like to send an updated invoice email to the operator?
            </p>

            <div
                style="background: #f9fdf6; padding: 15px; border-radius: 4px; border-left: 4px solid #8ec46b; margin: 20px 0;">
                <div style="margin-bottom: 10px;">
                    <strong style="display: block; font-size: 12px; color: #999;">Invoice Number</strong>
                    <span id="previewInvoiceNumber" style="font-size: 16px; font-weight: 600; color: #1f3d1a;">-</span>
                </div>
                <div style="margin-bottom: 10px;">
                    <strong style="display: block; font-size: 12px; color: #999;">Operator</strong>
                    <span id="previewOperatorName" style="font-size: 14px; color: #1a2418;">-</span>
                    <span id="previewOperatorEmail"
                        style="font-size: 12px; color: #999; display: block; margin-top: 2px;">-</span>
                </div>
                <div style="margin-bottom: 10px;">
                    <strong style="display: block; font-size: 12px; color: #999;">Period</strong>
                    <span id="previewPeriod" style="font-size: 14px; color: #1a2418;">-</span>
                </div>
                <div style="margin-bottom: 0;">
                    <strong style="display: block; font-size: 12px; color: #999;">Amount Due</strong>
                    <span id="previewAmount" style="font-size: 18px; font-weight: 600; color: #1f3d1a;">$0.00</span>
                </div>
            </div>

            <input type="hidden" id="emailPreviewInvoiceId" value="">

            <p style="font-size: 13px; color: #666; margin: 15px 0;">An email with the updated invoice PDF will be sent
                to the operator's email address.</p>

            <div style="display: flex; gap: 10px; justify-content: flex-end;">
                <button onclick="skipEmailAndClose()" class="btn btn-secondary" style="padding: 10px 20px;">Skip
                    Email</button>
                <button onclick="confirmAndSendEmail(this)" class="btn btn-primary" style="padding: 10px 20px;">Send Email &
                    Confirm</button>
            </div>
        </div>
    </div>

</body>

</html>
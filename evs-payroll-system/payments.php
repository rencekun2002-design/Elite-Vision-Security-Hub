<?php
/**
 * Payment Tracking Dashboard
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/classes/PaymentsManager.php';
require_once __DIR__ . '/classes/InvoicesManager.php';
require_once __DIR__ . '/classes/OperatorsManager.php';

$payments_mgr = new PaymentsManager();
$invoices_mgr = new InvoicesManager();
$operators_mgr = new OperatorsManager();

// Handle payment recording
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['record_payment'])) {
    $payment_id = $payments_mgr->recordPayment(
        $_POST['invoice_id'],
        $_POST['operator_id'],
        $_POST['payment_date'],
        $_POST['amount'],
        $_POST['payment_method'],
        $_POST['reference_number'] ?? '',
        $_POST['notes'] ?? ''
    );

    if ($payment_id) {
        $message = 'Payment recorded successfully!';
        $message_type = 'success';
    } else {
        $message = 'Failed to record payment';
        $message_type = 'error';
    }
}

// Get unpaid invoices
$unpaid_invoices = $invoices_mgr->getAllInvoices(['payment_status' => 'unpaid']);
$partially_paid_invoices = $invoices_mgr->getAllInvoices(['payment_status' => 'partial']);
$payable_invoices = array_merge($unpaid_invoices, $partially_paid_invoices);
$all_invoices = $invoices_mgr->getAllInvoices();

$payment_summary = $payments_mgr->getPaymentSummary();
$operators = $operators_mgr->getAllOperators();

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Tracking - Elite Vision Security</title>
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

        /* Tabs */
        .tabs {
            display: flex;
            gap: 0;
            border-bottom: 2px solid #e0e5db;
            margin-bottom: 20px;
        }

        .tab-btn {
            background: none;
            border: none;
            padding: 12px 20px;
            font-size: 13px;
            font-weight: 600;
            color: var(--muted);
            cursor: pointer;
            border-bottom: 3px solid transparent;
            text-transform: uppercase;
            letter-spacing: .05em;
        }

        .tab-btn:hover {
            color: var(--text);
        }

        .tab-btn.active {
            color: var(--night-2);
            border-bottom-color: var(--brass);
        }

        .tab-pane {
            display: none;
        }

        .tab-pane.active {
            display: block;
        }

        /* Form */
        .form-group {
            margin-bottom: 16px;
        }

        label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: var(--muted);
            margin-bottom: 4px;
        }

        input,
        select,
        textarea {
            width: 100%;
            padding: 8px 10px;
            border: 1px solid #d3dacb;
            border-radius: 6px;
            font-size: 14px;
            background: #fff;
        }

        input:focus,
        select:focus,
        textarea:focus {
            outline: 2px solid var(--green-light);
            outline-offset: 1px;
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 16px;
        }

        /* Alert */
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

        /* Table */
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
        }

        .status-unpaid {
            background-color: #f8d7da;
            color: #721c24;
        }

        .status-partial {
            background-color: #fff3cd;
            color: #856404;
        }

        .status-paid {
            background-color: #d4edda;
            color: #155724;
        }

        /* Button */
        button,
        .btn {
            cursor: pointer;
            border: none;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            padding: 9px 16px;
            background: var(--night-2);
            color: #f4f1e6;
            border: 1px solid var(--night-2);
            transition: all 0.2s ease;
        }

        button:hover,
        .btn:hover {
            background: var(--night);
            transform: translateY(-1px);
        }

        .money {
            font-weight: 600;
            color: var(--green-mid);
            font-family: 'Oswald', sans-serif;
        }

        .progress-bar {
            width: 100%;
            height: 20px;
            background: #e0e5db;
            border-radius: 10px;
            overflow: hidden;
            margin: 8px 0;
        }

        .progress-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--green-dark), var(--green-light));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 11px;
            font-weight: 600;
        }

        .back-link {
            display: inline-block;
            margin-bottom: 16px;
            color: var(--night-2);
            text-decoration: none;
            font-weight: 600;
        }

        .back-link:hover {
            text-decoration: underline;
        }
    </style>
    <style>
        body {
            background: radial-gradient(circle at 15% -10%, #e4f2e9 0%, transparent 45%), radial-gradient(circle at 100% 0%, #eaf3ec 0%, transparent 40%), #f3f5f3;
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
        .masthead-sub,
        .back-link {
            color: #667b70;
        }

        .panel {
            border-color: #dde3de;
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

        .stat-card .value,
        .stat-box .value {
            color: #148a56;
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
    </style>
</head>

<body>

    <div class="masthead">
        <div class="masthead-inner container">
            <div class="brand-text">
                <h1>Elite Vision Security</h1>
                <div class="tagline">Payment Tracking System</div>
            </div>
            <div class="masthead-sub"><a href="index.php" class="back-link">← Back to Dashboard</a></div>
        </div>
    </div>

    <div class="container">

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="panel">
            <h2>Payment Summary</h2>
            <div class="grid">
                <div class="stat-card">
                    <div class="label">Total Payments</div>
                    <div class="value"><?php echo $payment_summary['total_payments'] ?? 0; ?></div>
                </div>

                <div class="stat-card">
                    <div class="label">Total Amount Paid</div>
                    <div class="value">$<?php echo number_format($payment_summary['total_paid'] ?? 0, 0); ?></div>
                </div>

                <div class="stat-card">
                    <div class="label">Invoices Paid</div>
                    <div class="value"><?php echo $payment_summary['invoices_paid'] ?? 0; ?></div>
                </div>

                <div class="stat-card">
                    <div class="label">Operators Paid</div>
                    <div class="value"><?php echo $payment_summary['operators_paid'] ?? 0; ?></div>
                </div>
            </div>
        </div>

        <!-- Tabs -->
        <div class="panel">
            <div class="tabs">
                <button class="tab-btn active" onclick="switchTab('record', this)">Record Payment</button>
                <button class="tab-btn" onclick="switchTab('unpaid', this)">Unpaid Invoices</button>
                <button class="tab-btn" onclick="switchTab('partial', this)">Partially Paid</button>
                <button class="tab-btn" onclick="switchTab('history', this)">Payment History</button>
            </div>

            <!-- Record Payment Tab -->
            <div id="record" class="tab-pane active">
                <h2>Record a Payment</h2>
                <form method="POST">
                    <input type="hidden" name="record_payment" value="1">

                    <div class="form-row">
                        <div class="form-group">
                            <label for="invoice_id">Select Invoice</label>
                            <select id="invoice_id" name="invoice_id" required onchange="updateOperatorFromInvoice()">
                                <option value="">-- Choose an invoice --</option>
                                <?php foreach ($payable_invoices as $inv):
                                    $remaining = max(0, $inv['total_amount'] - ($inv['payment_amount'] ?? 0));
                                    ?>
                                    <option value="<?php echo $inv['id']; ?>"
                                        data-operator="<?php echo $inv['operator_id']; ?>"
                                        data-remaining="<?php echo htmlspecialchars($remaining); ?>">
                                        #<?php echo htmlspecialchars($inv['invoice_number']); ?> -
                                        <?php echo htmlspecialchars($inv['operator_name']); ?>
                                        ($<?php echo number_format($remaining, 2); ?> remaining)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="operator_id">Operator</label>
                            <select id="operator_id" name="operator_id" required>
                                <option value="">-- Select --</option>
                                <?php foreach ($operators as $op): ?>
                                    <option value="<?php echo $op['id']; ?>"><?php echo htmlspecialchars($op['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="payment_date">Payment Date</label>
                            <input type="date" id="payment_date" name="payment_date" required
                                value="<?php echo date('Y-m-d'); ?>">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="amount">Amount</label>
                            <input type="number" id="amount" name="amount" min="0.01" step="0.01" required
                                placeholder="0.00">
                        </div>

                        <div class="form-group">
                            <label for="payment_method">Payment Method</label>
                            <select id="payment_method" name="payment_method">
                                <option value="transfer">Bank Transfer</option>
                                <option value="check">Check</option>
                                <option value="cash">Cash</option>
                                <option value="card">Card</option>
                                <option value="other">Other</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="reference_number">Reference Number</label>
                            <input type="text" id="reference_number" name="reference_number"
                                placeholder="Check #, Transaction ID, etc.">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="notes">Notes</label>
                        <textarea id="notes" name="notes" rows="3"
                            placeholder="Additional payment details..."></textarea>
                    </div>

                    <button type="submit">Record Payment</button>
                </form>
            </div>

            <!-- Unpaid Invoices Tab -->
            <div id="unpaid" class="tab-pane">
                <h2>Unpaid Invoices</h2>
                <?php if (!empty($unpaid_invoices)): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Invoice #</th>
                                <th>Operator</th>
                                <th>Amount Due</th>
                                <th>Date Created</th>
                                <th>Days Overdue</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($unpaid_invoices as $inv):
                                $days_diff = (int) ((time() - strtotime($inv['created_at'])) / (60 * 60 * 24));
                                ?>
                                <tr>
                                    <td><strong>#<?php echo htmlspecialchars($inv['invoice_number']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($inv['operator_name']); ?></td>
                                    <td class="money">$<?php echo number_format($inv['total_amount'], 2); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($inv['created_at'])); ?></td>
                                    <td>
                                        <?php if ($days_diff > 30): ?>
                                            <span style="color: #dc3545; font-weight: 600;"><?php echo $days_diff; ?> days</span>
                                        <?php else: ?>
                                            <?php echo $days_diff; ?> days
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="color: #28a745; font-weight: 600;">✓ All invoices have been paid!</p>
                <?php endif; ?>
            </div>

            <!-- Partially Paid Tab -->
            <div id="partial" class="tab-pane">
                <h2>Partially Paid Invoices</h2>
                <?php if (!empty($partially_paid_invoices)): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Invoice #</th>
                                <th>Operator</th>
                                <th>Total Amount</th>
                                <th>Paid Amount</th>
                                <th>Remaining</th>
                                <th>Progress</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($partially_paid_invoices as $inv):
                                $remaining = $inv['total_amount'] - ($inv['payment_amount'] ?? 0);
                                $percent = ($inv['payment_amount'] ?? 0) / $inv['total_amount'] * 100;
                                ?>
                                <tr>
                                    <td><strong>#<?php echo htmlspecialchars($inv['invoice_number']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($inv['operator_name']); ?></td>
                                    <td class="money">$<?php echo number_format($inv['total_amount'], 2); ?></td>
                                    <td class="money">$<?php echo number_format($inv['payment_amount'] ?? 0, 2); ?></td>
                                    <td class="money">$<?php echo number_format($remaining, 2); ?></td>
                                    <td>
                                        <div class="progress-bar">
                                            <div class="progress-bar-fill" style="width: <?php echo $percent; ?>%">
                                                <?php echo number_format($percent, 0); ?>%
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No partially paid invoices.</p>
                <?php endif; ?>
            </div>

            <!-- Payment History Tab -->
            <div id="history" class="tab-pane">
                <h2>All Invoice Payment Status</h2>
                <?php if (!empty($all_invoices)): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Invoice #</th>
                                <th>Operator</th>
                                <th>Total</th>
                                <th>Paid</th>
                                <th>Status</th>
                                <th>Last Updated</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($all_invoices as $inv): ?>
                                <tr>
                                    <td><strong>#<?php echo htmlspecialchars($inv['invoice_number']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($inv['operator_name']); ?></td>
                                    <td class="money">$<?php echo number_format($inv['total_amount'], 2); ?></td>
                                    <td class="money">$<?php echo number_format($inv['payment_amount'] ?? 0, 2); ?></td>
                                    <td>
                                        <span
                                            class="status-badge status-<?php echo str_replace(' ', '-', strtolower($inv['payment_status'])); ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $inv['payment_status'])); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y H:i', strtotime($inv['updated_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <script>
        function switchTab(tabName, button) {
            document.querySelectorAll('.tab-pane').forEach(pane => {
                pane.classList.remove('active');
            });
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('active');
            });

            document.getElementById(tabName).classList.add('active');
            button.classList.add('active');
        }

        function updateOperatorFromInvoice() {
            const select = document.getElementById('invoice_id');
            const option = select.options[select.selectedIndex];
            const operatorId = option.getAttribute('data-operator');
            document.getElementById('operator_id').value = operatorId;
            const amount = document.getElementById('amount');
            const remaining = option.getAttribute('data-remaining');
            amount.max = remaining || '';
            if (remaining) amount.value = Number(remaining).toFixed(2);
        }
    </script>

</body>

</html>
<?php
/**
 * Reports & Analytics Dashboard
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/classes/InvoicesManager.php';
require_once __DIR__ . '/classes/PaymentsManager.php';
require_once __DIR__ . '/classes/OperatorsManager.php';

$invoices_mgr = new InvoicesManager();
$payments_mgr = new PaymentsManager();
$operators_mgr = new OperatorsManager();

// Get date range from query
$date_from = $_GET['date_from'] ?? date('Y-m-01');
$date_to = $_GET['date_to'] ?? date('Y-m-d');
$operator_filter = $_GET['operator_id'] ?? '';

// Get all invoices
$all_invoices = $invoices_mgr->getAllInvoices();
$operators = $operators_mgr->getAllOperators();

// Calculate statistics
$total_invoices = count($all_invoices);
$total_revenue = array_sum(array_column($all_invoices, 'total_amount'));
$paid_invoices = array_filter($all_invoices, fn($i) => $i['payment_status'] === 'paid');
$paid_revenue = array_sum(array_column($paid_invoices, 'total_amount'));
$unpaid_invoices = array_filter($all_invoices, fn($i) => $i['payment_status'] === 'unpaid');
$unpaid_revenue = array_sum(array_column($unpaid_invoices, 'total_amount'));
$partial_invoices = array_filter($all_invoices, fn($i) => $i['payment_status'] === 'partial');

// Group by operator
$by_operator = [];
foreach ($all_invoices as $invoice) {
    $op_id = $invoice['operator_id'];
    if (!isset($by_operator[$op_id])) {
        $by_operator[$op_id] = [
            'operator_name' => $invoice['operator_name'],
            'count' => 0,
            'total' => 0,
            'paid' => 0
        ];
    }
    $by_operator[$op_id]['count']++;
    $by_operator[$op_id]['total'] += $invoice['total_amount'];
    if ($invoice['payment_status'] === 'paid') {
        $by_operator[$op_id]['paid'] += $invoice['total_amount'];
    }
}

// Handle CSV export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="invoices_report_' . date('Y-m-d') . '.csv"');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['Invoice #', 'Operator', 'Amount', 'Status', 'Payment Status', 'Created Date']);

    foreach ($all_invoices as $invoice) {
        fputcsv($output, [
            $invoice['invoice_number'],
            $invoice['operator_name'],
            $invoice['total_amount'],
            $invoice['status'],
            $invoice['payment_status'],
            $invoice['created_at']
        ]);
    }

    fclose($output);
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports & Analytics - Elite Vision Security</title>
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
            justify-content: space-between;
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
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 16px;
            margin-bottom: 20px;
        }

        .stat-box {
            background: linear-gradient(135deg, var(--night-2) 0%, var(--night) 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }

        .stat-box .label {
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .05em;
            opacity: 0.9;
            margin-bottom: 8px;
        }

        .stat-box .value {
            font-size: 24px;
            font-weight: 700;
            color: var(--brass-light);
            font-family: 'Oswald', sans-serif;
        }

        .stat-box .percent {
            font-size: 12px;
            margin-top: 6px;
            opacity: 0.8;
        }

        /* Filters */
        .filter-group {
            display: flex;
            gap: 12px;
            margin-bottom: 20px;
            align-items: flex-end;
            flex-wrap: wrap;
        }

        .filter-item {
            display: flex;
            flex-direction: column;
        }

        label {
            font-size: 12px;
            font-weight: 600;
            color: var(--muted);
            margin-bottom: 4px;
        }

        input[type="date"],
        select {
            padding: 8px 10px;
            border: 1px solid #d3dacb;
            border-radius: 6px;
            font-size: 13px;
            background: white;
        }

        .btn {
            cursor: pointer;
            border: none;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            padding: 8px 16px;
            background: var(--night-2);
            color: #f4f1e6;
            transition: all 0.2s;
        }

        .btn:hover {
            background: var(--night);
            transform: translateY(-1px);
        }

        .btn-secondary {
            background: white;
            color: var(--night-2);
            border: 1px solid var(--brass);
        }

        .btn-secondary:hover {
            background: #faf6ea;
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

        .money {
            font-weight: 600;
            color: var(--green-mid);
            font-family: 'Oswald', sans-serif;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-paid {
            background-color: #d4edda;
            color: #155724;
        }

        .status-unpaid {
            background-color: #f8d7da;
            color: #721c24;
        }

        .status-partial {
            background-color: #fff3cd;
            color: #856404;
        }

        /* Charts (Simple bar representation) */
        .chart-container {
            margin: 20px 0;
            padding: 20px;
            background: #fafbf7;
            border-radius: 8px;
        }

        .chart-bar {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 12px;
        }

        .chart-label {
            width: 150px;
            font-size: 12px;
            font-weight: 600;
        }

        .chart-fill {
            flex: 1;
            height: 30px;
            background: linear-gradient(90deg, var(--green-dark), var(--green-light));
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            padding-right: 10px;
            color: white;
            font-size: 12px;
            font-weight: 600;
        }

        @media (max-width: 768px) {
            .grid {
                grid-template-columns: 1fr;
            }

            .filter-group {
                flex-direction: column;
                align-items: stretch;
            }

            .filter-item {
                width: 100%;
            }

            input,
            select {
                width: 100%;
            }
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

        .stat-box {
            background: #ffffff;
            border: 1px solid #dde3de;
            border-top: 3px solid #148a56;
            border-radius: 10px;
            box-shadow: 0 4px 16px rgba(23, 40, 30, .06);
        }

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
                <div class="tagline">Reports & Analytics</div>
            </div>
            <div style="text-align: right; font-size: 12px; color: #c9d3c0;">
                <a href="index.php" style="color: #e3cd93; text-decoration: none; font-weight: 600;">← Back to
                    Dashboard</a>
            </div>
        </div>
    </div>

    <div class="container">

        <!-- Filters -->
        <div class="panel">
            <form method="GET" style="display: flex; gap: 16px; flex-wrap: wrap; align-items: flex-end;">
                <div class="filter-item">
                    <label for="date_from">From Date</label>
                    <input type="date" id="date_from" name="date_from"
                        value="<?php echo htmlspecialchars($date_from); ?>">
                </div>

                <div class="filter-item">
                    <label for="date_to">To Date</label>
                    <input type="date" id="date_to" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>">
                </div>

                <div class="filter-item">
                    <label for="operator_id">Operator</label>
                    <select id="operator_id" name="operator_id">
                        <option value="">All Operators</option>
                        <?php foreach ($operators as $op): ?>
                            <option value="<?php echo $op['id']; ?>" <?php echo $operator_filter == $op['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($op['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="display: flex; gap: 8px;">
                    <button type="submit" class="btn">Filter</button>
                    <a href="?export=csv" class="btn btn-secondary">📥 Export CSV</a>
                </div>
            </form>
        </div>

        <!-- Summary Statistics -->
        <div class="panel">
            <h2>Summary Statistics</h2>
            <div class="grid">
                <div class="stat-box">
                    <div class="label">Total Invoices</div>
                    <div class="value"><?php echo $total_invoices; ?></div>
                </div>

                <div class="stat-box">
                    <div class="label">Total Revenue</div>
                    <div class="value">$<?php echo number_format($total_revenue, 0); ?></div>
                </div>

                <div class="stat-box">
                    <div class="label">Paid Revenue</div>
                    <div class="value">$<?php echo number_format($paid_revenue, 0); ?></div>
                    <div class="percent">
                        <?php echo $total_revenue > 0 ? number_format(($paid_revenue / $total_revenue) * 100, 1) : 0; ?>%
                    </div>
                </div>

                <div class="stat-box">
                    <div class="label">Unpaid Revenue</div>
                    <div class="value">$<?php echo number_format($unpaid_revenue, 0); ?></div>
                    <div class="percent">
                        <?php echo $total_revenue > 0 ? number_format(($unpaid_revenue / $total_revenue) * 100, 1) : 0; ?>%
                    </div>
                </div>

                <div class="stat-box">
                    <div class="label">Paid Invoices</div>
                    <div class="value"><?php echo count($paid_invoices); ?></div>
                    <div class="percent">
                        <?php echo $total_invoices > 0 ? number_format((count($paid_invoices) / $total_invoices) * 100, 1) : 0; ?>%
                    </div>
                </div>

                <div class="stat-box">
                    <div class="label">Partially Paid</div>
                    <div class="value"><?php echo count($partial_invoices); ?></div>
                    <div class="percent">
                        <?php echo $total_invoices > 0 ? number_format((count($partial_invoices) / $total_invoices) * 100, 1) : 0; ?>%
                    </div>
                </div>
            </div>
        </div>

        <!-- Revenue by Operator -->
        <div class="panel">
            <h2>Revenue by Operator</h2>
            <div class="chart-container">
                <?php
                usort($by_operator, function ($a, $b) {
                    return $b['total'] - $a['total'];
                });

                $max_revenue = max(array_column($by_operator, 'total'), 0) ?: 1;
                ?>

                <?php foreach ($by_operator as $data): ?>
                    <div class="chart-bar">
                        <div class="chart-label"><?php echo htmlspecialchars($data['operator_name']); ?></div>
                        <div class="chart-fill" style="width: <?php echo ($data['total'] / $max_revenue * 100); ?>%">
                            $<?php echo number_format($data['total'], 0); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <h3>Operator Details</h3>
            <table>
                <thead>
                    <tr>
                        <th>Operator</th>
                        <th>Invoices</th>
                        <th>Total Revenue</th>
                        <th>Paid Revenue</th>
                        <th>Outstanding</th>
                        <th>Collection Rate</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($by_operator as $data):
                        $outstanding = $data['total'] - $data['paid'];
                        $collection_rate = $data['total'] > 0 ? ($data['paid'] / $data['total'] * 100) : 0;
                        ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($data['operator_name']); ?></strong></td>
                            <td><?php echo $data['count']; ?></td>
                            <td class="money">$<?php echo number_format($data['total'], 2); ?></td>
                            <td class="money">$<?php echo number_format($data['paid'], 2); ?></td>
                            <td class="money">$<?php echo number_format($outstanding, 2); ?></td>
                            <td>
                                <span
                                    style="<?php echo $collection_rate >= 90 ? 'color: #28a745; font-weight: 600;' : ''; ?>">
                                    <?php echo number_format($collection_rate, 1); ?>%
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Invoice Status Breakdown -->
        <div class="panel">
            <h2>Invoice Status Breakdown</h2>
            <table>
                <thead>
                    <tr>
                        <th>Status</th>
                        <th>Count</th>
                        <th>Percentage</th>
                        <th>Total Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $statuses = ['paid' => 0, 'unpaid' => 0, 'partial' => 0];
                    foreach ($all_invoices as $inv) {
                        $status = $inv['payment_status'];
                        $statuses[$status]++;
                    }

                    foreach ($statuses as $status => $count):
                        $amount = match ($status) {
                            'paid' => $paid_revenue,
                            'unpaid' => $unpaid_revenue,
                            'partial' => array_sum(array_map(fn($i) => $i['payment_amount'] ?? 0, $partial_invoices)),
                            default => 0
                        };
                        $percentage = $total_invoices > 0 ? ($count / $total_invoices * 100) : 0;
                        ?>
                        <tr>
                            <td>
                                <span class="status-badge status-<?php echo $status; ?>">
                                    <?php echo ucfirst($status); ?>
                                </span>
                            </td>
                            <td><?php echo $count; ?></td>
                            <td><?php echo number_format($percentage, 1); ?>%</td>
                            <td class="money">$<?php echo number_format($amount, 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Recent Invoices -->
        <div class="panel">
            <h2>Recent Invoices</h2>
            <table>
                <thead>
                    <tr>
                        <th>Invoice #</th>
                        <th>Operator</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Payment</th>
                        <th>Created</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $recent = array_slice($all_invoices, 0, 10);
                    foreach ($recent as $inv):
                        ?>
                        <tr>
                            <td><strong>#<?php echo htmlspecialchars($inv['invoice_number']); ?></strong></td>
                            <td><?php echo htmlspecialchars($inv['operator_name']); ?></td>
                            <td class="money">$<?php echo number_format($inv['total_amount'], 2); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo $inv['status']; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $inv['status'])); ?>
                                </span>
                            </td>
                            <td>
                                <span class="status-badge status-<?php echo $inv['payment_status']; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $inv['payment_status'])); ?>
                                </span>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($inv['created_at'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    </div>

</body>

</html>
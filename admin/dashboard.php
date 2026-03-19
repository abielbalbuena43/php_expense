<?php
// Include database connection
include "connection.php";
include "header.php";

// ---------- Metrics ----------
$selectedMonth = intval($_GET['month'] ?? date('m'));
$selectedYear = intval($_GET['year'] ?? date('Y'));

// Total Expenses (Selected Month/Year) - Prepared for securitya
$totalExpensesStmt = $conn->prepare("
    SELECT COALESCE(SUM(expense_total_receipt_amount), 0) AS total 
    FROM expenses 
    WHERE MONTH(expense_date) = ? AND YEAR(expense_date) = ?
");
$totalExpensesStmt->bind_param("ii", $selectedMonth, $selectedYear);
$totalExpensesStmt->execute();
$totalExpensesResult = $totalExpensesStmt->get_result();
$totalExpensesRow = $totalExpensesResult->fetch_assoc();
$totalExpenses = floatval($totalExpensesRow['total'] ?? 0);
$totalExpensesStmt->close();

// Top Expense Category (Selected Month/Year) - Prepared
$topCategoryStmt = $conn->prepare("
    SELECT c.category_name, COALESCE(SUM(e.expense_total_receipt_amount), 0) AS total
    FROM expenses e
    JOIN expense_categories c ON e.expense_category_id = c.category_id
    WHERE MONTH(e.expense_date) = ? AND YEAR(e.expense_date) = ?
    GROUP BY c.category_name
    ORDER BY total DESC
    LIMIT 1
");
$topCategoryStmt->bind_param("ii", $selectedMonth, $selectedYear);
$topCategoryStmt->execute();
$topCategoryResult = $topCategoryStmt->get_result();
$topCategoryRow = $topCategoryResult->fetch_assoc();
$topCategory = $topCategoryRow['category_name'] ?? "None";
$topCategoryStmt->close();

// Fetch budget for selected month/year
$budgetStmt = $conn->prepare("SELECT amount FROM budgets WHERE month = ? AND year = ? LIMIT 1");
$budgetStmt->bind_param("ii", $selectedMonth, $selectedYear);
$budgetStmt->execute();
$budgetResult = $budgetStmt->get_result();
$budgetRow = $budgetResult->fetch_assoc();
$budgetTotal = $budgetRow['amount'] ?? null;
$budgetStmt->close();

// Calculate utilization if budget exists
if ($budgetTotal !== null && $budgetTotal > 0) {
    $budgetUtilization = ($totalExpenses / $budgetTotal) * 100;
    $budgetDisplay = number_format($budgetUtilization, 2) . '%';
} else {
    $budgetDisplay = "NOT YET SET";
}

// ---------- Chart Data ----------
$pieDataStmt = $conn->prepare("
    SELECT c.category_name, COALESCE(SUM(e.expense_total_receipt_amount), 0) AS total
    FROM expenses e
    JOIN expense_categories c ON e.expense_category_id = c.category_id
    WHERE MONTH(e.expense_date) = ? AND YEAR(e.expense_date) = ?
    GROUP BY c.category_name
    HAVING total > 0
");
$pieDataStmt->bind_param("ii", $selectedMonth, $selectedYear);
$pieDataStmt->execute();
$pieResult = $pieDataStmt->get_result();
$pieLabels = [];
$pieValues = [];
while ($row = $pieResult->fetch_assoc()) {
    $pieLabels[] = $row['category_name'];
    $pieValues[] = floatval($row['total']);
}
$pieDataStmt->close();

// ---------- Recent Activity ----------
$recentActivityStmt = $conn->prepare("
    SELECT e.expense_id, e.expense_or_number, e.expense_date, e.expense_total_receipt_amount,
           c.company_name, cat.category_name
    FROM expenses e
    LEFT JOIN companies c ON e.expense_company_id = c.company_id
    LEFT JOIN expense_categories cat ON e.expense_category_id = cat.category_id
    WHERE MONTH(e.expense_date) = ? AND YEAR(e.expense_date) = ?
    ORDER BY e.expense_date DESC
    LIMIT 5
");
$recentActivityStmt->bind_param("ii", $selectedMonth, $selectedYear);
$recentActivityStmt->execute();
$recentActivityResult = $recentActivityStmt->get_result();
$recentActivityStmt->close();

$selectedPeriodLabel = date('F Y', mktime(0, 0, 0, $selectedMonth, 1, $selectedYear));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <!-- CSS Files -->
    <link rel="stylesheet" href="css/bootstrap.min.css" />
    <link href="font-awesome/css/font-awesome.css" rel="stylesheet" />
    <link rel="stylesheet" href="css/layout.css" />

    <!-- Icons -->
    <link href="font-awesome/css/font-awesome.css" rel="stylesheet" />

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <title>Expense Tracker Dashboard</title>

    <style>
        /* ============================================
           DASHBOARD STYLING - Clean & Proper Positioning
           ============================================ */
        
        :root {
            --primary-color: #4e54c8;
            --primary-dark: #3a3f9e;
            --text-main: #1f2937;
            --text-muted: #6b7280;
            --bg-card: #ffffff;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        body {
            font-family: 'Open Sans', sans-serif;
            background-color: #f4f6f9;
        }

        /* Content Wrapper */
        #content {
            --primary-color: #4e54c8;
            --primary-dark: #3a3f9e;
            --text-main: #1f2937;
            --text-muted: #6b7280;

            --border-color: #e5e7eb;          /* tables/cards */
            --filter-border-color: #000000;   /* filter inputs */

            --bg-card: #ffffff;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        /* Content Header */
        #content-header {
            margin-bottom: 20px;
        }

        #breadcrumb {
            font-size: 14px;
            color: var(--text-muted);
        }

        #breadcrumb a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
        }

        #breadcrumb a:hover {
            text-decoration: underline;
        }

        /* Container Fluid */
        .container-fluid {
            max-width: 1640px;
            margin: 0 auto;
        }

        /* Filter Section */
        .filter-section {
            margin-bottom: 15px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 4px;
        }

        .filter-section form {
            display: inline-block;
        }

        .filter-section label {
            margin-right: 10px;
            font-weight: 600;
            color: var(--text-main);
        }

        .filter-section select {
            margin-right: 5px;
            padding: 5px;
            border: 1px solid var(--filter-border-color);
            border-radius: 3px;
            font-family: inherit;
        }

        .filter-section button {
            padding: 5px 10px;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            font-weight: 500;
        }

        .filter-section button:hover {
            background: var(--primary-dark);
        }

        /* Summary Section */
        .dashboard-summary {
            display: flex;
            gap: 20px;
            margin-bottom: 25px;
        }

        .summary-box {
            flex: 1;
            background: var(--bg-card);
            padding: 20px;
            border-radius: 8px;
            box-shadow: var(--shadow);
            border-left: 4px solid var(--primary-color);
        }

        .summary-box h4 {
            margin: 0 0 10px 0;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-muted);
            font-weight: 600;
        }

        .summary-box p {
            margin: 0;
            font-size: 24px;
            font-weight: 700;
            color: var(--text-main);
        }

        /* Chart Container */
        .chart-container {
            display: flex;
            gap: 20px;
            margin-bottom: 25px;
        }

        .chart-box, .logs-box {
            flex: 1;
            background: var(--bg-card);
            border-radius: 8px;
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .chart-box h4, .logs-box h4 {
            margin: 0;
            padding: 15px 20px;
            background: #f8f9fa;
            border-bottom: 1px solid var(--border-color);
            font-size: 16px;
            font-weight: 600;
            color: var(--text-main);
        }

        .chart-box {
          
            padding: 20px;
        }

        .chart-box canvas {
            width: 350px !important;
            height: 350px !important;
            margin: auto;
        }

        /* Logs Box */
        .widget-box {
            padding: 0;
        }

        .widget-content {
            padding: 0;
        }

        .widget-content table {
            width: 100%;
            border-collapse: collapse;
        }

        .widget-content th {
            padding: 12px 15px;
            background: #f8f9fa;
            text-align: left;
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
            color: var(--text-muted);
            border-bottom: 1px solid var(--border-color);
        }

        .widget-content td {
            padding: 12px 15px;
            border-bottom: 1px solid var(--border-color);
            color: var(--text-main);
            font-size: 14px;
        }

        .widget-content tr:last-child td {
            border-bottom: none;
        }

        .widget-content tr:hover td {
            background: #f8f9fa;
        }

        .amount-col {
            font-weight: 600;
            text-align: right;
        }

        .empty-state {
            text-align: center;
            padding: 20px;
            color: var(--text-muted);
            font-style: italic;
        }

        /* Responsive */
        @media (max-width: 900px) {
            #content {
                margin-left: 0;
                padding: 70px 15px 15px;
            }

            .dashboard-summary {
                flex-direction: column;
            }

            .chart-container {
                flex-direction: column;
            }
        }
    </style>
</head>

<body>

<div id="content">

    <div class="container-fluid">

            <!-- Filter Section -->
            <div class="filter-section dashboard-filter">

                <h4>
                    <i class="icon-filter"></i>
                    Filter Total Expenses
                </h4>

                <form method="get" id="filterForm">

                    <div class="period-row">

                        <select name="month">

                            <?php 
                            $months = [
                                '01'=>'January','02'=>'February','03'=>'March','04'=>'April',
                                '05'=>'May','06'=>'June','07'=>'July','08'=>'August',
                                '09'=>'September','10'=>'October','11'=>'November','12'=>'December'
                            ];

                            foreach ($months as $val => $name) {
                                $selected = ($val == $selectedMonth) ? 'selected' : '';
                                echo "<option value='$val' $selected>$name</option>";
                            }
                            ?>

                        </select>

                        <select name="year">

                            <?php 
                            $currentYear = date('Y');

                            for ($y = $currentYear; $y >= $currentYear - 9; $y--) {
                                $selected = ($y == $selectedYear) ? 'selected' : '';
                                echo "<option value='$y' $selected>$y</option>";
                            }
                            ?>

                        </select>

                    </div>

                    <div class="filter-actions">

                        <button type="submit" class="btn btn-primary">
                            Apply
                        </button>

                    </div>

                </form>

            </div>

        <!-- Summary Section -->
        <div class="dashboard-summary">

            <div class="summary-box">
                <h4>TOTAL EXPENSES (<?= htmlspecialchars($selectedPeriodLabel) ?>)</h4>
                <p>₱<?= number_format($totalExpenses, 2) ?></p>
            </div>

            <div class="summary-box">
                <h4>BUDGET UTILIZATION</h4>
                <p><?= htmlspecialchars($budgetDisplay) ?></p>
            </div>

            <div class="summary-box">
                <h4>TOP EXPENSE CATEGORY</h4>
                <p><?= htmlspecialchars($topCategory) ?></p>
            </div>

        </div>

        <!-- Charts & Recent Activity -->
        <div class="chart-container">

            <!-- Pie Chart -->
            <div class="chart-box">
                <h4>Expense Breakdown by Category</h4>
                <canvas id="idPieChart"></canvas>
            </div>

            <!-- Recent Activity -->
            <div class="logs-box">

                <h4>Recent Activity (<?= htmlspecialchars($selectedPeriodLabel) ?>)</h4>

                <div class="widget-box">
                    <div class="widget-content nopadding">

                        <table style="width:100%;border-collapse:collapse;">

                            <thead>
                                <tr>
                                    <th style="padding:8px;">OR Number</th>
                                    <th style="padding:8px;">Date</th>
                                    <th style="padding:8px;">Company</th>
                                    <th style="padding:8px;">Category</th>
                                    <th style="padding:8px;">Amount</th>
                                </tr>
                            </thead>

                            <tbody>

                            <?php if ($recentActivityResult && $recentActivityResult->num_rows > 0) { ?>

                                <?php while ($row = $recentActivityResult->fetch_assoc()) { ?>

                                <tr>
                                    <td style="padding:8px;">
                                        <?= htmlspecialchars($row['expense_or_number'] ?? '-') ?>
                                    </td>

                                    <td style="padding:8px;">
                                        <?= date('M d, Y', strtotime($row['expense_date'])) ?>
                                    </td>

                                    <td style="padding:8px;">
                                        <?= htmlspecialchars($row['company_name'] ?? '-') ?>
                                    </td>

                                    <td style="padding:8px;">
                                        <?= htmlspecialchars($row['category_name'] ?? '-') ?>
                                    </td>

                                    <td style="padding:8px;" class="amount-col">
                                        ₱<?= number_format($row['expense_total_receipt_amount'], 2) ?>
                                    </td>
                                </tr>

                                <?php } ?>

                            <?php } else { ?>

                                <tr>
                                    <td colspan="5"
                                        style="padding:10px;text-align:center;"
                                        class="empty-state">
                                        No recent activity recorded.
                                    </td>
                                </tr>

                            <?php } ?>

                            </tbody>

                        </table>

                    </div>
                </div>

            </div>

        </div>

    </div>
    

</div>


<!-- Chart.js Integration -->
<script>
    const chartColors = [
        '#4e54c8', '#10b981', '#f59e0b', '#ef4444',
        '#8b5cf6', '#ec4899', '#06b6d4', '#6366f1'
    ];

    const pieLabels = <?= json_encode($pieLabels) ?>;
    const pieValues = <?= json_encode($pieValues) ?>;
    const ctxPie = document.getElementById('idPieChart').getContext('2d');

    new Chart(ctxPie, {
        type: 'doughnut',
        data: {
            labels: pieLabels,
            datasets: [{
                data: pieValues,
                backgroundColor: chartColors.slice(0, pieLabels.length),
                borderColor: "#ffffff",
                borderWidth: 2,
                hoverOffset: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { 
                legend: { 
                    position: 'right',
                    labels: {
                        font: {
                            family: "'Open Sans', sans-serif",
                            size: 12
                        }
                    }
                } 
            },
            cutout: '60%'
        }
    });
</script>

</body>
</html>
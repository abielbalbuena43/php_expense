<?php
// Include database connection
include "connection.php";
include "header.php";

// ---------- Metrics ----------
$currentMonth = date('m');
$currentYear = date('Y');

// Total Expenses (This Month)
$totalExpensesQuery = "
    SELECT SUM(expense_total_receipt_amount) AS total 
    FROM expenses 
    WHERE MONTH(expense_date) = '$currentMonth' 
      AND YEAR(expense_date) = '$currentYear'
";
$totalExpensesResult = mysqli_query($conn, $totalExpensesQuery) or die("Error in totalExpensesQuery: " . mysqli_error($conn));
$totalExpensesRow = mysqli_fetch_assoc($totalExpensesResult);
$totalExpenses = $totalExpensesRow['total'] ?? 0;

// Top Expense Category (This Month)
$topCategoryQuery = "
    SELECT c.category_name, SUM(e.expense_total_receipt_amount) AS total
    FROM expenses e
    JOIN expense_categories c ON e.expense_category_id = c.category_id
    WHERE MONTH(e.expense_date) = '$currentMonth' 
      AND YEAR(e.expense_date) = '$currentYear'
    GROUP BY c.category_name
    ORDER BY total DESC
    LIMIT 1
";
$topCategoryResult = mysqli_query($conn, $topCategoryQuery) or die("Error in topCategoryQuery: " . mysqli_error($conn));
$topCategoryRow = mysqli_fetch_assoc($topCategoryResult);
$topCategory = $topCategoryRow['category_name'] ?? "None";

// Placeholder Budget Utilization
$budgetTotal = 50000; // Example budget amount (make dynamic later)
$budgetUtilization = $budgetTotal > 0 ? ($totalExpenses / $budgetTotal) * 100 : 0;

// ---------- Chart Data ----------
// Pie Chart - Expense Breakdown by Category
$pieDataQuery = "
    SELECT c.category_name, SUM(e.expense_total_receipt_amount) AS total
    FROM expenses e
    JOIN expense_categories c ON e.expense_category_id = c.category_id
    WHERE MONTH(e.expense_date) = '$currentMonth' 
      AND YEAR(e.expense_date) = '$currentYear'
    GROUP BY c.category_name
";
$pieResult = mysqli_query($conn, $pieDataQuery) or die("Error in pieDataQuery: " . mysqli_error($conn));
$pieLabels = [];
$pieValues = [];
while ($row = mysqli_fetch_assoc($pieResult)) {
    $pieLabels[] = $row['category_name'];
    $pieValues[] = $row['total'];
}

// ---------- Recent Activity ----------
// Fetch last 5 expenses (latest by created_at)
$recentActivityQuery = "
    SELECT e.expense_id, e.expense_or_number, e.expense_date, e.expense_total_receipt_amount,
           c.company_name, cat.category_name
    FROM expenses e
    LEFT JOIN companies c ON e.expense_company_id = c.company_id
    LEFT JOIN expense_categories cat ON e.expense_category_id = cat.category_id
    ORDER BY e.expense_created_at DESC
    LIMIT 5
";
$recentActivityResult = mysqli_query($conn, $recentActivityQuery) or die("Error in recentActivityQuery: " . mysqli_error($conn));
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <!-- Design assets -->
    <link rel="stylesheet" href="css/bootstrap.min.css" />
    <link rel="stylesheet" href="css/matrix-style.css" />
    <link rel="stylesheet" href="css/matrix-media.css" />
    <link rel="stylesheet" href="font-awesome/css/font-awesome.css" />
    <link rel="stylesheet" href="css/dashboard.css" />

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <title>Expense Tracker Dashboard</title>
</head>

<body>
<div id="content">
    <div id="content-header">
        <div id="breadcrumb">
            <a href="dashboard.php" class="tip-bottom"><i class="icon-home"></i> Home</a>
        </div>
    </div>

    <div class="container-fluid">

        <!-- Summary Section -->
        <div class="dashboard-summary">
            <div class="summary-box">
                <h4>TOTAL EXPENSES (THIS MONTH)</h4>
                <p>₱<?= number_format($totalExpenses, 2) ?></p>
            </div>
            <div class="summary-box">
                <h4>BUDGET UTILIZATION</h4>
                <p><?= number_format($budgetUtilization, 2) ?>%</p>
            </div>
            <div class="summary-box">
                <h4>TOP EXPENSE CATEGORY</h4>
                <p><?= htmlspecialchars($topCategory) ?></p>
            </div>
        </div>

        <!-- Charts & Recent Activity Section -->
        <div class="chart-container">
            <!-- Expense Breakdown Pie Chart -->
            <div class="chart-box">
                <h4>Expense Breakdown by Category</h4>
                <canvas id="idPieChart"></canvas>
            </div>

            <!-- Recent Activity Table -->
            <div class="logs-box">
                <h4>Recent Activity</h4>
                <div class="widget-box">
                    <div class="widget-content nopadding">
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr>
                                    <th style="padding: 8px;">OR Number</th>
                                    <th style="padding: 8px;">Date</th>
                                    <th style="padding: 8px;">Company</th>
                                    <th style="padding: 8px;">Category</th>
                                    <th style="padding: 8px;">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (mysqli_num_rows($recentActivityResult) > 0) { ?>
                                    <?php while ($row = mysqli_fetch_assoc($recentActivityResult)) { ?>
                                        <tr>
                                            <td style="padding: 8px;"><?= htmlspecialchars($row['expense_or_number'] ?? '-') ?></td>
                                            <td style="padding: 8px;"><?= date('M d, Y', strtotime($row['expense_date'])) ?></td>
                                            <td style="padding: 8px;"><?= htmlspecialchars($row['company_name'] ?? '-') ?></td>
                                            <td style="padding: 8px;"><?= htmlspecialchars($row['category_name'] ?? '-') ?></td>
                                            <td style="padding: 8px;">₱<?= number_format($row['expense_total_receipt_amount'], 2) ?></td>
                                        </tr>
                                    <?php } ?>
                                <?php } else { ?>
                                    <tr>
                                        <td colspan="5" style="padding: 10px; text-align: center;">No recent activity recorded.</td>
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
        '#FF0000', '#0000FF', '#FFFF00', '#008000',
        '#FFA500', '#800080', '#00CED1', '#FF4500'
    ];

    const pieLabels = <?= json_encode($pieLabels) ?>;
    const pieValues = <?= json_encode($pieValues) ?>;
    const ctxPie = document.getElementById('idPieChart').getContext('2d');

    new Chart(ctxPie, {
        type: 'pie',
        data: {
            labels: pieLabels,
            datasets: [{
                data: pieValues,
                backgroundColor: chartColors.slice(0, pieLabels.length),
                borderColor: "#fff",
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: true } }
        }
    });
</script>

</body>
</html>

<?php
// Include database connection
include "connection.php";
include "header.php";

// ---------- Metrics ----------
$selectedMonth = intval($_GET['month'] ?? date('m'));
$selectedYear = intval($_GET['year'] ?? date('Y'));

// Total Expenses (Selected Month/Year) - Prepared for security
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

// Placeholder Budget Utilization (based on selected total)
$budgetTotal = 50000; // Example budget amount (make dynamic later)
$budgetUtilization = $budgetTotal > 0 ? ($totalExpenses / $budgetTotal) * 100 : 0;

// ---------- Chart Data ----------
// Pie Chart - Expense Breakdown by Category (Selected Month/Year) - Prepared
$pieDataStmt = $conn->prepare("
    SELECT c.category_name, COALESCE(SUM(e.expense_total_receipt_amount), 0) AS total
    FROM expenses e
    JOIN expense_categories c ON e.expense_category_id = c.category_id
    WHERE MONTH(e.expense_date) = ? AND YEAR(e.expense_date) = ?
    GROUP BY c.category_name
    HAVING total > 0  -- Skip zero totals for cleaner chart
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

// ---------- Recent Activity (Synced to Filter) ----------
// Fetch exactly the 5 most recent expenses within the selected period (by transaction date DESC)
$recentActivityStmt = $conn->prepare("
    SELECT e.expense_id, e.expense_or_number, e.expense_date, e.expense_total_receipt_amount,
           c.company_name, cat.category_name
    FROM expenses e
    LEFT JOIN companies c ON e.expense_company_id = c.company_id
    LEFT JOIN expense_categories cat ON e.expense_category_id = cat.category_id
    WHERE MONTH(e.expense_date) = ? AND YEAR(e.expense_date) = ?
    ORDER BY e.expense_date DESC  -- Most recent transaction date first within the period
    LIMIT 5  -- Exactly 5 (or fewer if less available in period)
");
$recentActivityStmt->bind_param("ii", $selectedMonth, $selectedYear);
$recentActivityStmt->execute();
$recentActivityResult = $recentActivityStmt->get_result();
$recentActivityStmt->close();

// Format selected period label
$selectedPeriodLabel = date('F Y', mktime(0, 0, 0, $selectedMonth, 1, $selectedYear));
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

        <!-- Small Filter Dropdown (minimal addition above summary) -->
        <div style="margin-bottom: 15px; padding: 10px; background: #f8f9fa; border-radius: 4px;">
            <form method="get" style="display: inline-block;">
                <label style="margin-right: 10px; font-weight: bold;">Filter Total Expenses:</label>
                <select name="month" style="margin-right: 5px; padding: 5px;">
                    <?php 
                    $months = [
                        '01' => 'January', '02' => 'February', '03' => 'March', '04' => 'April', 
                        '05' => 'May', '06' => 'June', '07' => 'July', '08' => 'August', 
                        '09' => 'September', '10' => 'October', '11' => 'November', '12' => 'December'
                    ];
                    foreach ($months as $val => $name) {
                        $selected = ($val == $selectedMonth) ? 'selected' : '';
                        echo "<option value='$val' $selected>$name</option>";
                    }
                    ?>
                </select>
                <select name="year" style="margin-right: 10px; padding: 5px;">
                    <?php 
                    $currentYear = date('Y');
                    for ($y = $currentYear; $y >= $currentYear - 9; $y--) {  // Last 10 years
                        $selected = ($y == $selectedYear) ? 'selected' : '';
                        echo "<option value='$y' $selected>$y</option>";
                    }
                    ?>
                </select>
                <button type="submit" style="padding: 5px 10px; background: #007bff; color: white; border: none; border-radius: 3px; cursor: pointer;">Apply</button>
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

            <!-- Recent Activity Table (Synced to Filter - 5 Most Recent in Period) -->
            <div class="logs-box">
                <h4>Recent Activity (<?= htmlspecialchars($selectedPeriodLabel) ?>)</h4>  <!-- Updated title to reflect filter -->
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
                                <?php if ($recentActivityResult && $recentActivityResult->num_rows > 0) { ?>
                                    <?php while ($row = $recentActivityResult->fetch_assoc()) { ?>
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

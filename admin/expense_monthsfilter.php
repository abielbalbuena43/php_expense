<?php
session_start();
include "connection.php";
include "header.php";

// Get selected month and year
$selectedMonth = intval($_GET['month'] ?? date('m'));
$selectedYear = intval($_GET['year'] ?? date('Y'));

// Format selected period label
if ($selectedMonth > 0 && $selectedYear > 0) {
    $selectedPeriodLabel = date('F Y', mktime(0, 0, 0, $selectedMonth, 1, $selectedYear));
    $viewingLabel = "Viewing Dashboard for: $selectedPeriodLabel";
} else {
    $viewingLabel = "Viewing All Expenses";
}

// ============ DATA QUERIES FOR CHARTS ============

// 1. Get totals by category
$categoryQuery = "
    SELECT 
        cat.category_name,
        SUM(e.expense_total_receipt_amount) as total_amount,
        COUNT(e.expense_id) as expense_count
    FROM expenses e
    INNER JOIN expense_categories cat ON e.expense_category_id = cat.category_id
";
if ($selectedMonth > 0 && $selectedYear > 0) {
    $categoryQuery .= " WHERE MONTH(e.expense_date) = ? AND YEAR(e.expense_date) = ?";
    $catParams = [$selectedMonth, $selectedYear];
    $catTypes = "ii";
} else {
    $catParams = [];
    $catTypes = "";
}
$categoryQuery .= " GROUP BY cat.category_id, cat.category_name ORDER BY total_amount DESC";

$catStmt = $conn->prepare($categoryQuery);
if (!empty($catParams)) {
    $catStmt->bind_param($catTypes, ...$catParams);
}
$catStmt->execute();
$categoryResult = $catStmt->get_result();

$categoryData = [];
$categoryLabels = [];
$categoryValues = [];
while ($row = $categoryResult->fetch_assoc()) {
    $categoryLabels[] = $row['category_name'];
    $categoryValues[] = floatval($row['total_amount']);
    $categoryData[] = $row;
}
$catStmt->close();

// 2. Get totals by company
$companyQuery = "
    SELECT 
        c.company_name,
        SUM(e.expense_total_receipt_amount) as total_amount,
        COUNT(e.expense_id) as expense_count
    FROM expenses e
    INNER JOIN companies c ON e.expense_company_id = c.company_id
";
if ($selectedMonth > 0 && $selectedYear > 0) {
    $companyQuery .= " WHERE MONTH(e.expense_date) = ? AND YEAR(e.expense_date) = ?";
    $compParams = [$selectedMonth, $selectedYear];
    $compTypes = "ii";
} else {
    $compParams = [];
    $compTypes = "";
}
$companyQuery .= " GROUP BY c.company_id, c.company_name ORDER BY total_amount DESC LIMIT 10";

$compStmt = $conn->prepare($companyQuery);
if (!empty($compParams)) {
    $compStmt->bind_param($compTypes, ...$compParams);
}
$compStmt->execute();
$companyResult = $compStmt->get_result();

$companyData = [];
$companyLabels = [];
$companyValues = [];
while ($row = $companyResult->fetch_assoc()) {
    $companyLabels[] = $row['company_name'];
    $companyValues[] = floatval($row['total_amount']);
    $companyData[] = $row;
}
$compStmt->close();

// 3. Get daily expense trend for the month
$dailyQuery = "
    SELECT 
        DAY(e.expense_date) as day,
        SUM(e.expense_total_receipt_amount) as total_amount,
        COUNT(e.expense_id) as expense_count
    FROM expenses e
";
if ($selectedMonth > 0 && $selectedYear > 0) {
    $dailyQuery .= " WHERE MONTH(e.expense_date) = ? AND YEAR(e.expense_date) = ?";
    $dailyParams = [$selectedMonth, $selectedYear];
    $dailyTypes = "ii";
} else {
    $dailyParams = [];
    $dailyTypes = "";
}
$dailyQuery .= " GROUP BY DAY(e.expense_date) ORDER BY day ASC";

$dailyStmt = $conn->prepare($dailyQuery);
if (!empty($dailyParams)) {
    $dailyStmt->bind_param($dailyTypes, ...$dailyParams);
}
$dailyStmt->execute();
$dailyResult = $dailyStmt->get_result();

$dailyLabels = [];
$dailyValues = [];
while ($row = $dailyResult->fetch_assoc()) {
    $dailyLabels[] = $row['day'];
    $dailyValues[] = floatval($row['total_amount']);
}
$dailyStmt->close();

// 4. Get overall summary stats
$summaryQuery = "
    SELECT 
        COUNT(e.expense_id) as total_expenses,
        SUM(e.expense_total_receipt_amount) as total_amount,
        SUM(e.expense_total_input_tax) as total_vat,
        SUM(e.expense_total_purchases) as total_purchases
    FROM expenses e
";
if ($selectedMonth > 0 && $selectedYear > 0) {
    $summaryQuery .= " WHERE MONTH(e.expense_date) = ? AND YEAR(e.expense_date) = ?";
    $sumParams = [$selectedMonth, $selectedYear];
    $sumTypes = "ii";
} else {
    $sumParams = [];
    $sumTypes = "";
}

$sumStmt = $conn->prepare($summaryQuery);
if (!empty($sumParams)) {
    $sumStmt->bind_param($sumTypes, ...$sumParams);
}
$sumStmt->execute();
$summaryResult = $sumStmt->get_result();
$summary = $summaryResult->fetch_assoc();
$sumStmt->close();

// Convert PHP arrays to JSON for JavaScript charts
$categoryLabelsJSON = json_encode($categoryLabels);
$categoryValuesJSON = json_encode($categoryValues);
$companyLabelsJSON = json_encode($companyLabels);
$companyValuesJSON = json_encode($companyValues);
$dailyLabelsJSON = json_encode($dailyLabels);
$dailyValuesJSON = json_encode($dailyValues);
?>

<!-- Include Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div id="content">
    <div id="content-header">
        <div id="breadcrumb">
            <a href="dashboard.php" class="tip-bottom"><i class="icon-home"></i> Home</a>
            <a href="expenses.php" class="tip-bottom">Expenses</a>
            <a href="#" class="current">Monthly Dashboard</a>
        </div>
    </div>

    <div class="container-fluid">

        <!-- Filter Section -->
        <div class="row-fluid">
            <div class="span12">
                <div class="filter-section" style="display: inline-block; margin: 15px 0; vertical-align: middle;">
                    <form method="get" id="filterForm" style="display: inline;">
                        <label style="margin-right: 10px; font-weight: bold; font-size: 14px;">Filter by Transaction Month:</label>
                        <select name="month" style="margin-right: 5px; padding: 5px; font-size: 14px;">
                            <option value="0" <?= ($selectedMonth == 0) ? 'selected' : '' ?>>All Months</option>
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
                        <select name="year" style="margin-right: 10px; padding: 5px; font-size: 14px;">
                            <option value="0" <?= ($selectedYear == 0) ? 'selected' : '' ?>>All Years</option>
                            <?php 
                            $currentYear = date('Y');
                            for ($y = $currentYear; $y >= $currentYear - 9; $y--) {
                                $selected = ($y == $selectedYear) ? 'selected' : '';
                                echo "<option value='$y' $selected>$y</option>";
                            }
                            ?>
                        </select>
                        <button type="submit" class="btn btn-primary" style="padding: 5px 10px; font-size: 14px;">Apply Filter</button>
                        <button type="button" id="clearFilter" class="btn btn-secondary" style="padding: 5px 10px; margin-left: 5px; font-size: 14px;">Clear Filter</button>
                    </form>
                    <span style="margin-left: 10px; font-weight: bold; font-size: 14px;"><?= htmlspecialchars($viewingLabel) ?></span>
                </div>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="row-fluid">
            <div class="span3">
                <div class="widget-box">
                    <div class="widget-content nopadding">
                        <div style="padding: 20px; text-align: center; background: #3498db; color: white; border-radius: 5px;">
                            <h3 style="color: white; margin: 0;"><?php echo number_format($summary['total_expenses'] ?? 0); ?></h3>
                            <p style="margin: 5px 0 0 0;">Total Transactions</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="span3">
                <div class="widget-box">
                    <div class="widget-content nopadding">
                        <div style="padding: 20px; text-align: center; background: #2ecc71; color: white; border-radius: 5px;">
                            <h3 style="color: white; margin: 0;">₱<?php echo number_format($summary['total_amount'] ?? 0, 2); ?></h3>
                            <p style="margin: 5px 0 0 0;">Total Amount</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="span3">
                <div class="widget-box">
                    <div class="widget-content nopadding">
                        <div style="padding: 20px; text-align: center; background: #e74c3c; color: white; border-radius: 5px;">
                            <h3 style="color: white; margin: 0;">₱<?php echo number_format($summary['total_vat'] ?? 0, 2); ?></h3>
                            <p style="margin: 5px 0 0 0;">Total Input Tax</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="span3">
                <div class="widget-box">
                    <div class="widget-content nopadding">
                        <div style="padding: 20px; text-align: center; background: #f39c12; color: white; border-radius: 5px;">
                            <h3 style="color: white; margin: 0;">₱<?php echo number_format($summary['total_purchases'] ?? 0, 2); ?></h3>
                            <p style="margin: 5px 0 0 0;">Total Purchases</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row 1 -->
        <div class="row-fluid">
            <div class="span6">
                <div class="widget-box">
                    <div class="widget-title">
                        <span class="icon"><i class="icon-bar-chart"></i></span>
                        <h5>Expenses by Category</h5>
                    </div>
                    <div class="widget-content">
                        <canvas id="categoryChart" style="max-height: 300px;"></canvas>
                    </div>
                </div>
            </div>
            <div class="span6">
                <div class="widget-box">
                    <div class="widget-title">
                        <span class="icon"><i class="icon-bar-chart"></i></span>
                        <h5>Expenses by Company (Top 10)</h5>
                    </div>
                    <div class="widget-content">
                        <canvas id="companyChart" style="max-height: 300px;"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Daily Trend Chart -->
        <div class="row-fluid">
            <div class="span12">
                <div class="widget-box">
                    <div class="widget-title">
                        <span class="icon"><i class="icon-bar-chart"></i></span>
                        <h5>Daily Expense Trend</h5>
                    </div>
                    <div class="widget-content">
                        <canvas id="dailyChart" style="max-height: 250px;"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tables Row -->
        <div class="row-fluid">
            <div class="span6">
                <div class="widget-box">
                    <div class="widget-title">
                        <span class="icon"><i class="icon-list"></i></span>
                        <h5>Expenses by Category</h5>
                    </div>
                    <div class="widget-content nopadding">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Category</th>
                                    <th>Count</th>
                                    <th>Total Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($categoryData)): ?>
                                    <?php foreach ($categoryData as $row): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($row['category_name']); ?></td>
                                            <td><?php echo number_format($row['expense_count']); ?></td>
                                            <td>₱<?php echo number_format($row['total_amount'], 2); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3" style="text-align:center;">No data available</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="span6">
                <div class="widget-box">
                    <div class="widget-title">
                        <span class="icon"><i class="icon-list"></i></span>
                        <h5>Expenses by Company</h5>
                    </div>
                    <div class="widget-content nopadding">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Company</th>
                                    <th>Count</th>
                                    <th>Total Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($companyData)): ?>
                                    <?php foreach ($companyData as $row): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($row['company_name']); ?></td>
                                            <td><?php echo number_format($row['expense_count']); ?></td>
                                            <td>₱<?php echo number_format($row['total_amount'], 2); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3" style="text-align:center;">No data available</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<?php include "footer.php"; ?>

<script>
$(document).ready(function() {
    // Clear Filter functionality
    $('#clearFilter').click(function() {
        $('select[name="month"]').val(0);
        $('select[name="year"]').val(0);
        $('#filterForm').submit();
    });

    // Chart.js default settings
    Chart.defaults.font.family = "'Helvetica Neue', 'Helvetica', 'Arial', sans-serif";
    Chart.defaults.font.size = 12;

    // Category Pie/Doughnut Chart
    var categoryCtx = document.getElementById('categoryChart').getContext('2d');
    var categoryChart = new Chart(categoryCtx, {
        type: 'doughnut',
        data: {
            labels: <?php echo $categoryLabelsJSON; ?>,
            datasets: [{
                data: <?php echo $categoryValuesJSON; ?>,
                backgroundColor: [
                    '#3498db', '#e74c3c', '#2ecc71', '#f39c12', '#9b59b6', 
                    '#1abc9c', '#34495e', '#e67e22', '#95a5a6', '#d35400'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right',
                    labels: {
                        boxWidth: 12,
                        padding: 10
                    }
                }
            }
        }
    });

    // Daily Line Chart
var dailyCtx = document.getElementById('dailyChart').getContext('2d');
var dailyChart = new Chart(dailyCtx, {
    type: 'line',
    data: {
        labels: <?php echo $dailyLabelsJSON; ?>,
        datasets: [{
            label: 'Daily Expenses',
            data: <?php echo $dailyValuesJSON; ?>,
            backgroundColor: 'rgba(52, 152, 219, 0.2)',
            borderColor: '#3498db',
            borderWidth: 2,
            fill: true,
            tension: 0.3,
            pointBackgroundColor: '#3498db',
            pointRadius: 4,
            pointHoverRadius: 6
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return '₱' + value.toLocaleString();
                    }
                }
            },
            x: {
                title: {
                    display: true,
                    text: 'Day of Month'
                }
            }
        },
        plugins: {
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return '₱' + context.parsed.y.toLocaleString();
                    }
                }
            }
        }
    }
});
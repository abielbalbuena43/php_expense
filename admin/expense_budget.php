<?php
ob_start();
session_start();
include "header.php";
include "connection.php";

// Selected year
$selectedYear = intval($_GET['year'] ?? date('Y'));

// ---------- Monthly Expenses ----------
$expenseStmt = $conn->prepare("
    SELECT MONTH(expense_date) AS month,
           SUM(expense_total_receipt_amount) AS total
    FROM expenses
    WHERE YEAR(expense_date) = ?
    GROUP BY MONTH(expense_date)
");
$expenseStmt->bind_param("i", $selectedYear);
$expenseStmt->execute();
$expenseResult = $expenseStmt->get_result();

$expenseData = array_fill(1, 12, 0);

while ($row = $expenseResult->fetch_assoc()) {
    $expenseData[$row['month']] = floatval($row['total']);
}

$expenseStmt->close();

// ---------- Monthly Budgets ----------
$budgetStmt = $conn->prepare("
    SELECT month, amount
    FROM budgets
    WHERE year = ?
");
$budgetStmt->bind_param("i", $selectedYear);
$budgetStmt->execute();
$budgetResult = $budgetStmt->get_result();

$budgetData = array_fill(1, 12, 0);

while ($row = $budgetResult->fetch_assoc()) {
    $budgetData[$row['month']] = floatval($row['amount']);
}

$budgetStmt->close();
?>

<div id="content">
    <div id="content-header">
        <div id="breadcrumb">
            <a href="dashboard.php" class="tip-bottom">
                <i class="icon-home"></i> Dashboard
            </a>
            <a href="#" class="current">Expense vs Budget</a>
        </div>
    </div>

    <div class="container-fluid">

        <!-- Filter -->
        <div class="row-fluid">
            <div class="span12">
                <div class="widget-box">
                    <div class="widget-title">
                        <span class="icon"><i class="icon-filter"></i></span>
                        <h5>Select Year</h5>
                    </div>

                    <div class="widget-content" style="padding:15px;">

                        <form method="get" class="form-inline">

                            <label style="margin-right:10px;"><strong>Year:</strong></label>

                            <select name="year" style="margin-right:10px;">
                                <?php
                                $currentYear = date("Y");
                                for ($y = $currentYear; $y >= $currentYear - 10; $y--) {
                                    $selected = ($y == $selectedYear) ? "selected" : "";
                                    echo "<option value='$y' $selected>$y</option>";
                                }
                                ?>
                            </select>

                            <button type="submit" class="btn btn-primary">
                                Generate Report
                            </button>

                        </form>

                    </div>
                </div>
            </div>
        </div>

        <!-- Chart -->
        <div class="row-fluid">
            <div class="span12">
                <div class="widget-box">

                    <div class="widget-title">
                        <span class="icon"><i class="icon-bar-chart"></i></span>
                        <h5>Expense vs Budget Summary (<?= htmlspecialchars($selectedYear) ?>)</h5>
                    </div>

                    <div class="widget-content" style="padding:20px;">
                        <canvas id="expenseBudgetChart" height="120"></canvas>
                    </div>

                </div>
            </div>
        </div>

    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
const months = [
"Jan","Feb","Mar","Apr","May","Jun",
"Jul","Aug","Sep","Oct","Nov","Dec"
];

const expenses = <?= json_encode(array_values($expenseData)) ?>;
const budgets = <?= json_encode(array_values($budgetData)) ?>;

const selectedYear = <?= $selectedYear ?>;

const ctx = document.getElementById('expenseBudgetChart').getContext('2d');

const expenseChart = new Chart(ctx, {
    type: 'bar',
    data: {
        labels: months,
        datasets: [
            {
                label: "Expenses",
                data: expenses,
                backgroundColor: "#e74c3c"
            },
            {
                label: "Budget",
                data: budgets,
                backgroundColor: "#2ecc71"
            }
        ]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true
            }
        },
        plugins: {
            legend: {
                position: "top"
            }
        },

        // 👇 Change cursor when hovering bars
        onHover: function(event, elements) {
            event.native.target.style.cursor = elements.length ? 'pointer' : 'default';
        },

        // 👇 Redirect when clicking bar
        onClick: function(evt, elements) {

            if (elements.length > 0) {

                const index = elements[0].index;
                const month = index + 1;

                window.location.href =
                    "expenses.php?month=" + month + "&year=" + selectedYear;

            }

        }
    }
});
</script>

<?php include "footer.php"; ?>
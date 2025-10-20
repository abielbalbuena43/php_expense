<?php
session_start();  // For CSRF token
include "connection.php";
include "header.php";

// Handle delete (POST only, with CSRF)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id']) && $_POST['csrf_token'] === $_SESSION['csrf_token']) {
    $deleteId = intval($_POST['delete_id']);
    $deleteStmt = $conn->prepare("DELETE FROM expenses WHERE expense_id = ?");
    $deleteStmt->bind_param("i", $deleteId);
    if ($deleteStmt->execute()) {
        $_SESSION['alert'] = 'success';  // For display in header/footer if needed
    } else {
        $_SESSION['alert'] = 'error';
    }
    $deleteStmt->close();
    // Preserve filter state (including all=0)
    $redirect = $_SERVER['PHP_SELF'];
    if (isset($_GET['month'])) $redirect .= '?month=' . intval($_GET['month']);
    if (isset($_GET['year'])) $redirect .= (strpos($redirect, '?') !== false ? '&' : '?') . 'year=' . intval($_GET['year']);
    header("Location: $redirect");
    exit();
}

// Generate CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Fetch expense records (filtered by transaction dates OR all if cleared)
$selectedMonth = intval($_GET['month'] ?? date('m'));  // Default to current if no param
$selectedYear = intval($_GET['year'] ?? date('Y'));   // Default to current if no param

$sql = "
    SELECT 
        e.expense_id,
        e.expense_or_number,
        e.expense_date,
        e.expense_total_receipt_amount,  -- For amount column
        c.company_name,
        p.payee_name,
        cat.category_name
    FROM expenses e
    INNER JOIN companies c ON e.expense_company_id = c.company_id
    INNER JOIN payees p ON e.expense_payee_id = p.payee_id
    INNER JOIN expense_categories cat ON e.expense_category_id = cat.category_id
";

$params = [];
$types = "";
if ($selectedMonth > 0 && $selectedYear > 0) {
    $sql .= " WHERE MONTH(e.expense_date) = ? AND YEAR(e.expense_date) = ?";
    $params = [$selectedMonth, $selectedYear];
    $types = "ii";
}

$sql .= " ORDER BY e.expense_date DESC, e.expense_id DESC LIMIT 100";  // Limit for performance (all or filtered)

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();

// Format selected period label
if ($selectedMonth > 0 && $selectedYear > 0) {
    $selectedPeriodLabel = date('F Y', mktime(0, 0, 0, $selectedMonth, 1, $selectedYear));
    $viewingLabel = "Viewing Expenses for: $selectedPeriodLabel";
} else {
    $viewingLabel = "Viewing All Expenses";
}

// Build report link (preserves current filters)
$reportLink = "reports.php";
if ($selectedMonth > 0 || $selectedYear > 0) {
    $reportLink .= "?month=" . ($selectedMonth > 0 ? $selectedMonth : '') . "&year=" . ($selectedYear > 0 ? $selectedYear : '');
}
?>

<div id="content">
    <div id="content-header">
        <div id="breadcrumb">
            <a href="dashboard.php" class="tip-bottom"><i class="icon-home"></i> Home</a>
            <a href="#" class="current">Expenses</a>
        </div>
    </div>

    <div class="container-fluid">

        <!-- Action Button & Filter (uniform with dashboard) -->
        <div class="row-fluid">
            <div class="span12">
                <a href="expense_new.php" class="btn btn-success" style="margin-bottom:15px;">
                    <i class="icon-plus"></i> Create New Expense
                </a>
                
                <!-- Small Filter Dropdown -->
                <div class="filter-section" style="display: inline-block; margin-left: 20px; vertical-align: middle;">
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

        <!-- Expenses Table (uniform with dashboard.css) -->
        <div class="row-fluid">
            <div class="span12">
                <div class="widget-box">
                    <div class="widget-content nopadding">
                        <table class="table table-bordered table-striped" id="expensesTable">  <!-- Bootstrap for DataTables base -->
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>OR Number</th>
                                    <th>Date</th>  <!-- Transaction date (expense_date) -->
                                    <th>Company</th>
                                    <th>Payee</th>
                                    <th>Category</th>
                                    <th>Amount</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($result && $result->num_rows > 0): ?>
                                    <?php while ($row = $result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo $row['expense_id']; ?></td>
                                            <td><?php echo htmlspecialchars($row['expense_or_number']); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($row['expense_date'])); ?></td>
                                            <td><?php echo htmlspecialchars($row['company_name']); ?></td>
                                            <td><?php echo htmlspecialchars($row['payee_name']); ?></td>
                                            <td><?php echo htmlspecialchars($row['category_name']); ?></td>
                                            <td>₱<?php echo number_format($row['expense_total_receipt_amount'], 2); ?></td>
                                            <td style="white-space: nowrap;">
                                                <a href="expense_view.php?id=<?php echo $row['expense_id']; ?>" class="btn btn-info btn-mini" style="font-size: 12px;">View</a>
                                                <!-- POST Delete Form -->
                                                <form method="post" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this expense?');">
                                                    <input type="hidden" name="delete_id" value="<?php echo $row['expense_id']; ?>">
                                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                                    <button type="submit" class="btn btn-danger btn-mini" style="font-size: 12px;">Delete</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" style="text-align:center; padding: 20px; font-size: 14px;">
                                            <?php if ($selectedMonth > 0 && $selectedYear > 0): ?>
                                                No expenses found for the selected period.
                                            <?php else: ?>
                                                No expenses found.
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>

                        <!-- Report Button Below Table -->
                        <?php if ($result && $result->num_rows > 0): ?>
                            <div class="report-actions" style="text-align: center; padding: 15px; background: #f8f9fa; border-top: 1px solid #ddd; margin-top: 0;">
                                <a href="<?= $reportLink ?>" class="btn btn-primary" style="padding: 8px 12px; font-size: 14px; font-weight: bold;">
                                    <i class="icon-bar-chart"></i> Generate Report
                                </a>
                            </div>
                        <?php endif; ?>
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
        $('#filterForm').submit();  // Submit to reload with all
    });

    $('#expensesTable').DataTable({
        "scrollX": true,
        "pageLength": 25,  // Show 25 rows per page for better UX
        "lengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]]  // Pagination options
    });
});
</script>

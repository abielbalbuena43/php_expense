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
        // Log the action
        $logQuery = "INSERT INTO logs (log_action, log_user, log_details, log_date) VALUES ('Expense deleted', '" . mysqli_real_escape_string($conn, $_SESSION['username']) . "', 'Expense ID: $deleteId', NOW())";
        mysqli_query($conn, $logQuery);
        
        $_SESSION['alert'] = 'success';
    } else {
        $_SESSION['alert'] = 'error';
    }
    $deleteStmt->close();
    
    // Preserve filter state
    $redirect = $_SERVER['PHP_SELF'];
    if (isset($_GET['month'])) $redirect .= '?month=' . (is_array($_GET['month']) ? implode(',', $_GET['month']) : $_GET['month']);
    if (isset($_GET['year'])) $redirect .= (strpos($redirect, '?') !== false ? '&' : '?') . 'year=' . intval($_GET['year']);
    header("Location: $redirect");
    exit();
}

// Generate CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// --- UPDATED LOGIC FOR MULTI-SELECT ---
// Get months as an array. If empty, it means "All".
$selectedMonths = isset($_GET['month']) ? (array)$_GET['month'] : [];
$selectedYear = isset($_GET['year']) ? intval($_GET['year']) : date('Y');

// Check if "All Months" (0) is selected or if array is empty
$showAllMonths = (empty($selectedMonths) || in_array(0, $selectedMonths));

// Build the SQL Query
$sql = "
    SELECT 
        e.expense_id,
        e.expense_or_number,
        e.expense_date,
        e.expense_total_receipt_amount,
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

if (!$showAllMonths && $selectedYear > 0) {
    // Sanitize the months array to integers
    $cleanMonths = array_map('intval', $selectedMonths);
    $monthPlaceholders = implode(',', array_fill(0, count($cleanMonths), '?'));
    
    $sql .= " WHERE MONTH(e.expense_date) IN ($monthPlaceholders) AND YEAR(e.expense_date) = ?";
    $params = array_merge($cleanMonths, [$selectedYear]);
    $types = str_repeat('i', count($cleanMonths)) . 'i';
} elseif ($selectedYear > 0) {
    // If no months selected but year is, show all months for that year
    $sql .= " WHERE YEAR(e.expense_date) = ?";
    $params = [$selectedYear];
    $types = 'i';
}

$sql .= " ORDER BY e.expense_date DESC, e.expense_id DESC LIMIT 100";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();

// Format selected period label
if (!$showAllMonths && $selectedYear > 0) {
    $monthNames = [
        '01' => 'January', '02' => 'February', '03' => 'March', '04' => 'April', 
        '05' => 'May', '06' => 'June', '07' => 'July', '08' => 'August', 
        '09' => 'September', '10' => 'October', '11' => 'November', '12' => 'December'
    ];
    $displayMonths = [];
    foreach ($selectedMonths as $m) {
        if(isset($monthNames[$m])) $displayMonths[] = $monthNames[$m];
    }
    $viewingLabel = "Viewing Expenses for: " . implode(', ', $displayMonths) . " " . $selectedYear;
} else {
    $viewingLabel = "Viewing All Expenses";
}

// Build report link
$reportLink = "reports.php";
if (!$showAllMonths || $selectedYear > 0) {
    $monthParam = is_array($selectedMonths) ? implode(',', $selectedMonths) : $selectedMonths;
    $reportLink .= "?month=" . $monthParam . "&year=" . $selectedYear;
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

        <!-- Action Button & Filter -->
        <div class="row-fluid">
            <div class="span12">
                <a href="expense_new.php" class="btn btn-success" style="margin-bottom:15px;">
                    <i class="icon-plus"></i> Create New Expense
                </a>
                
                <!-- Filter Section -->
                <div class="filter-section" style="display: inline-block; margin-left: 20px; vertical-align: middle;">
                    <form method="get" id="filterForm" style="display: inline;">
                        <label style="margin-right: 10px; font-weight: bold; font-size: 14px;">Filter by Transaction Month:</label>
                        
                        <!-- FIXED: Added id="monthFilter" for JavaScript -->
                        <select name="month[]" id="monthFilter" class="form-control" style="width: 200px; margin-right: 5px; padding: 5px; font-size: 14px;">
                            <option value="0" <?= (empty($selectedMonths) || in_array(0, $selectedMonths)) ? 'selected' : '' ?>>All Months</option>
                            <?php 
                            $months = [
                                '01' => 'January', '02' => 'February', '03' => 'March', '04' => 'April', 
                                '05' => 'May', '06' => 'June', '07' => 'July', '08' => 'August', 
                                '09' => 'September', '10' => 'October', '11' => 'November', '12' => 'December'
                            ];
                            foreach ($months as $val => $name) {
                                $selected = in_array($val, $selectedMonths) ? 'selected' : '';
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

        <!-- Expenses Table -->
        <div class="row-fluid">
            <div class="span12">
                <div class="widget-box">
                    <div class="widget-content nopadding">
                        <table class="table table-bordered table-striped" id="expensesTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>OR Number</th>
                                    <th>Date</th>
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
                                            <?php if (!$showAllMonths && $selectedYear > 0): ?>
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

<!-- Bootstrap Multiselect CSS (Only if not already in header.php) -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-multiselect/1.0.1/css/bootstrap-multiselect.css" />

<!-- jQuery (Only if not already loaded in header.php) -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- Bootstrap Multiselect JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-multiselect/1.0.1/js/bootstrap-multiselect.min.js"></script>

<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap.min.js"></script>

<script>
$(document).ready(function() {
    // Initialize Bootstrap Multiselect for the Month Filter
    $('#monthFilter').multiselect({
        includeSelectAllOption: true,
        selectAllText: 'Select All Months',
        numberDisplayed: 3,
        nonSelectedText: 'Select Months',
        buttonWidth: '200px',
        onChange: function(option, checked, select) {
            // Optional: Auto-submit when selection changes
            // $('#filterForm').submit(); 
        }
    });

    // Clear Filter functionality
    $('#clearFilter').click(function() {
        // Reset Multiselect to "All Months" (value 0)
        $('#monthFilter').multiselect('deselect', '0');
        $('#monthFilter').multiselect('select', '0');
        $('select[name="year"]').val(0);
        $('#filterForm').submit();
    });

    // DataTables Initialization
    $('#expensesTable').DataTable({
        "scrollX": true,
        "pageLength": 25,
        "lengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]]
    });
});
</script>
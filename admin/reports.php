<?php
ob_start();
session_start();
include "connection.php";  // Assuming this is your database connection file
if (!isset($_GET['export'])) {
    include "header.php";  // UI only, not for Excel export
}


// Fetch data for dropdowns
$companiesQuery = "SELECT company_id, company_name FROM companies ORDER BY company_name ASC";
$companiesResult = mysqli_query($conn, $companiesQuery);

$categoriesQuery = "SELECT category_id, category_name FROM expense_categories ORDER BY category_name ASC";
$categoriesResult = mysqli_query($conn, $categoriesQuery);

$productsQuery = "SELECT product_id, product_name FROM expense_products ORDER BY product_name ASC";
$productsResult = mysqli_query($conn, $productsQuery);

// Handle the export request
if (isset($_GET['export']) && $_GET['export'] == 1) {
    // Get filters from the form
    $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
    $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';
    $company_id = isset($_GET['company_id']) ? intval($_GET['company_id']) : 0;  // Filter by company
    $category_id = isset($_GET['category_id']) ? intval($_GET['category_id']) : 0;  // Filter by category
    $product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;  // Filter by product

    // Validate dates
    if (empty($start_date) || empty($end_date)) {
        die("Please provide both start and end dates for filtering.");
    }
    if (strtotime($start_date) > strtotime($end_date)) {
        die("Start date must be before end date.");
    }

    // Build the query with filters - now including more detailed fields for richer export
    $query = "
        SELECT 
            e.expense_id,
            e.expense_or_number,
            e.expense_date,
            e.expense_remarks,
            e.expense_gross_taxable,
            e.expense_service_charge,
            e.expense_services,
            e.expense_capital_goods,
            e.expense_goods_other_than_capital,
            e.expense_exempt,
            e.expense_zero_rated,
            e.expense_vat_rate,
            e.expense_total_input_tax,
            e.expense_total_purchases,
            e.expense_taxable_net_vat,
            e.expense_total_receipt_amount,
            c.company_name,
            p.payee_name,
            cat.category_name
        FROM expenses e
        INNER JOIN companies c ON e.expense_company_id = c.company_id
        INNER JOIN payees p ON e.expense_payee_id = p.payee_id
        INNER JOIN expense_categories cat ON e.expense_category_id = cat.category_id
        WHERE e.expense_date BETWEEN ? AND ?
    ";
    $params = [$start_date, $end_date];
    $types = "ss";  // Types for prepared statement

    if ($company_id > 0) {
        $query .= " AND e.expense_company_id = ?";
        $params[] = $company_id;
        $types .= "i";
    }
    if ($category_id > 0) {
        $query .= " AND e.expense_category_id = ?";
        $params[] = $category_id;
        $types .= "i";
    }
    if ($product_id > 0) {
        $query .= " AND e.expense_product_id = ?";
        $params[] = $product_id;
        $types .= "i";
    }

    $query .= " ORDER BY e.expense_date DESC, e.expense_id DESC";

    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    // Calculate summary totals for export (include in a separate section)
    $totalExpenses = 0;
    $totalAmount = 0.00;
    $categoryTotals = [];
    $companyTotals = [];
    $data = []; // Store rows for summary calc and output

    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
        $totalExpenses++;
        $totalAmount += $row['expense_total_receipt_amount'];
        
        $cat = $row['category_name'];
        if (!isset($categoryTotals[$cat])) {
            $categoryTotals[$cat] = 0.00;
        }
        $categoryTotals[$cat] += $row['expense_total_receipt_amount'];
        
        $comp = $row['company_name'];
        if (!isset($companyTotals[$comp])) {
            $companyTotals[$comp] = 0.00;
        }
        $companyTotals[$comp] += $row['expense_total_receipt_amount'];
    }

    // Set headers for Excel export
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=expense_report.xls");
    header("Pragma: no-cache");
    header("Expires: 0");

    // Output summary first
    echo "<table border='1'>";
    echo "<tr><th colspan='18'>Expense Report Summary</th></tr>";
    echo "<tr><td colspan='2'>Total Expenses:</td><td colspan='16'>$totalExpenses</td></tr>";
    echo "<tr><td colspan='2'>Total Amount:</td><td colspan='16'>₱" . number_format($totalAmount, 2) . "</td></tr>";
    if (!empty($categoryTotals)) {
        echo "<tr><th colspan='18'>Totals by Category</th></tr>";
        foreach ($categoryTotals as $cat => $amt) {
            echo "<tr><td colspan='2'>" . htmlspecialchars($cat) . ":</td><td colspan='16'>₱" . number_format($amt, 2) . "</td></tr>";
        }
    }
    if (!empty($companyTotals)) {
        echo "<tr><th colspan='18'>Totals by Company</th></tr>";
        foreach ($companyTotals as $comp => $amt) {
            echo "<tr><td colspan='2'>" . htmlspecialchars($comp) . ":</td><td colspan='16'>₱" . number_format($amt, 2) . "</td></tr>";
        }
    }
    echo "</table><br>";

    // Output the detailed table
    echo "<table border='1'>";
    echo "<tr>
            <th>Expense ID</th>
            <th>OR Number</th>
            <th>Date</th>
            <th>Remarks</th>
            <th>Gross Taxable</th>
            <th>Service Charge</th>
            <th>Services</th>
            <th>Capital Goods</th>
            <th>Goods Other Than Capital</th>
            <th>Exempt</th>
            <th>Zero Rated</th>
            <th>VAT Rate</th>
            <th>Total Input Tax</th>
            <th>Total Purchases</th>
            <th>Taxable Net VAT</th>
            <th>Total Receipt Amount</th>
            <th>Company</th>
            <th>Payee</th>
            <th>Category</th>
          </tr>";

    foreach ($data as $row) {
        echo "<tr>
                <td>" . htmlspecialchars($row['expense_id']) . "</td>
                <td>" . htmlspecialchars($row['expense_or_number']) . "</td>
                <td>" . htmlspecialchars(date('M d, Y', strtotime($row['expense_date']))) . "</td>
                <td>" . htmlspecialchars($row['expense_remarks']) . "</td>
                <td>₱" . number_format($row['expense_gross_taxable'], 2) . "</td>
                <td>₱" . number_format($row['expense_service_charge'], 2) . "</td>
                <td>₱" . number_format($row['expense_services'], 2) . "</td>
                <td>₱" . number_format($row['expense_capital_goods'], 2) . "</td>
                <td>₱" . number_format($row['expense_goods_other_than_capital'], 2) . "</td>
                <td>₱" . number_format($row['expense_exempt'], 2) . "</td>
                <td>₱" . number_format($row['expense_zero_rated'], 2) . "</td>
                <td>" . number_format($row['expense_vat_rate'], 2) . "%</td>
                <td>₱" . number_format($row['expense_total_input_tax'], 2) . "</td>
                <td>₱" . number_format($row['expense_total_purchases'], 2) . "</td>
                <td>₱" . number_format($row['expense_taxable_net_vat'], 2) . "</td>
                <td>₱" . number_format($row['expense_total_receipt_amount'], 2) . "</td>
                <td>" . htmlspecialchars($row['company_name']) . "</td>
                <td>" . htmlspecialchars($row['payee_name']) . "</td>
                <td>" . htmlspecialchars($row['category_name']) . "</td>
              </tr>";
    }

    echo "</table>";
    $stmt->close();
    exit();  // Stop further execution
}

// For display: Get filters and show the report on page (like previous reports.php)
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01'); // Default to start of current month
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t'); // Default to end of current month
$company_id = isset($_GET['company_id']) ? intval($_GET['company_id']) : 0;
$category_id = isset($_GET['category_id']) ? intval($_GET['category_id']) : 0;
$product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;

// Build query for display (basic columns for table, but calculate summary)
$query = "
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
    WHERE e.expense_date BETWEEN ? AND ?
";
$params = [$start_date, $end_date];
$types = "ss";

if ($company_id > 0) {
    $query .= " AND e.expense_company_id = ?";
    $params[] = $company_id;
    $types .= "i";
}
if ($category_id > 0) {
    $query .= " AND e.expense_category_id = ?";
    $params[] = $category_id;
    $types .= "i";
}
if ($product_id > 0) {
    $query .= " AND e.expense_product_id = ?";
    $params[] = $product_id;
    $types .= "i";
}

$query .= " ORDER BY e.expense_date DESC, e.expense_id DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// Calculate summary for display
$totalExpenses = 0;
$totalAmount = 0.00;
$categoryTotals = [];
$companyTotals = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $totalExpenses++;
        $totalAmount += $row['expense_total_receipt_amount'];
        
        $cat = $row['category_name'];
        if (!isset($categoryTotals[$cat])) {
            $categoryTotals[$cat] = 0.00;
        }
        $categoryTotals[$cat] += $row['expense_total_receipt_amount'];
        
        $comp = $row['company_name'];
        if (!isset($companyTotals[$comp])) {
            $companyTotals[$comp] = 0.00;
        }
        $companyTotals[$comp] += $row['expense_total_receipt_amount'];
    }
    $result->data_seek(0); // Reset for table display
}

$reportTitle = "Expense Report from " . date('M d, Y', strtotime($start_date)) . " to " . date('M d, Y', strtotime($end_date));
?>

<div id="content">
    <div id="content-header">
        <div id="breadcrumb">
            <a href="dashboard.php" class="tip-bottom"><i class="icon-home"></i> Home</a>
            <a href="expenses.php" class="tip-bottom">Expenses</a>
            <a href="#" class="current">Expense Reports</a>
        </div>
    </div>

    <div class="container-fluid">
        <div class="row-fluid">
            <div class="span12">
                <div class="widget-box">
                    <div class="widget-title">
                        <span class="icon"><i class="icon-bar-chart"></i></span>
                        <h5><?php echo htmlspecialchars($reportTitle); ?></h5>
                        <div class="buttons" style="float: right;">
                            <a href="javascript:window.print();" class="btn btn-primary btn-mini"><i class="icon-print"></i> Print Report</a>
                            <a href="expenses.php" class="btn btn-secondary btn-mini"><i class="icon-arrow-left"></i> Back to Expenses</a>
                        </div>
                    </div>
                    <div class="widget-content nopadding">
                        <!-- Filter Form -->
                        <div style="padding: 20px; background: #f8f9fa; border-bottom: 1px solid #ddd;">
                            <form method="get" id="filterForm">
                                <label for="start_date" style="margin-right: 10px; font-weight: bold; font-size: 14px;">Start Date:</label>
                                <input type="date" name="start_date" id="start_date" value="<?php echo $start_date; ?>" style="margin-right: 10px; padding: 5px; font-size: 14px;">
                                
                                <label for="end_date" style="margin-right: 10px; font-weight: bold; font-size: 14px;">End Date:</label>
                                <input type="date" name="end_date" id="end_date" value="<?php echo $end_date; ?>" style="margin-right: 10px; padding: 5px; font-size: 14px;">
                                
                                <label for="company_id" style="margin-right: 10px; font-weight: bold; font-size: 14px;">Company:</label>
                                <select name="company_id" id="company_id" style="margin-right: 10px; padding: 5px; font-size: 14px;">
                                    <option value="0">All Companies</option>
                                    <?php mysqli_data_seek($companiesResult, 0); while ($row = mysqli_fetch_assoc($companiesResult)) { ?>
                                        <option value="<?php echo $row['company_id']; ?>" <?php if ($row['company_id'] == $company_id) echo 'selected'; ?>><?php echo htmlspecialchars($row['company_name']); ?></option>
                                    <?php } ?>
                                </select>
                                
                                <label for="category_id" style="margin-right: 10px; font-weight: bold; font-size: 14px;">Category:</label>
                                <select name="category_id" id="category_id" style="margin-right: 10px; padding: 5px; font-size: 14px;">
                                    <option value="0">All Categories</option>
                                    <?php mysqli_data_seek($categoriesResult, 0); while ($row = mysqli_fetch_assoc($categoriesResult)) { ?>
                                        <option value="<?php echo $row['category_id']; ?>" <?php if ($row['category_id'] == $category_id) echo 'selected'; ?>><?php echo htmlspecialchars($row['category_name']); ?></option>
                                    <?php } ?>
                                </select>
                                
                                <label for="product_id" style="margin-right: 10px; font-weight: bold; font-size: 14px;">Product:</label>
                                <select name="product_id" id="product_id" style="margin-right: 10px; padding: 5px; font-size: 14px;">
                                    <option value="0">All Products</option>
                                    <?php mysqli_data_seek($productsResult, 0); while ($row = mysqli_fetch_assoc($productsResult)) { ?>
                                        <option value="<?php echo $row['product_id']; ?>" <?php if ($row['product_id'] == $product_id) echo 'selected'; ?>><?php echo htmlspecialchars($row['product_name']); ?></option>
                                    <?php } ?>
                                </select>
                                
                                <button type="submit" class="btn btn-primary" style="padding: 5px 10px; font-size: 14px;">Apply Filters</button>
                                <button type="submit" name="export" value="1" class="btn btn-success" style="padding: 5px 10px; font-size: 14px; margin-left: 10px;">Export to Excel</button>
                            </form>
                        </div>

                        <!-- Summary Section -->
                        <div class="summary-section" style="padding: 20px; background: #f8f9fa; border-bottom: 1px solid #ddd;">
                            <h4>Report Summary</h4>
                            <p><strong>Total Expenses:</strong> <?php echo $totalExpenses; ?></p>
                            <p><strong>Total Amount:</strong> ₱<?php echo number_format($totalAmount, 2); ?></p>
                            <?php if (!empty($categoryTotals)): ?>
                                <h5>By Category:</h5>
                                <ul>
                                    <?php foreach ($categoryTotals as $cat => $amt): ?>
                                        <li><?php echo htmlspecialchars($cat); ?>: ₱<?php echo number_format($amt, 2); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                            <?php if (!empty($companyTotals)): ?>
                                <h5>By Company:</h5>
                                <ul>
                                    <?php foreach ($companyTotals as $comp => $amt): ?>
                                        <li><?php echo htmlspecialchars($comp); ?>: ₱<?php echo number_format($amt, 2); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>

                        <!-- Expenses Table -->
                        <table class="table table-bordered table-striped" id="reportTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>OR Number</th>
                                    <th>Date</th>
                                    <th>Company</th><think>
                                    <th>Payee</th>
                                    <th>Category</th>
                                    <th>Amount</th>
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
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" style="text-align:center; padding: 20px; font-size: 14px;">
                                            No expenses found for the selected period.
                                        </td>
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

<?php
if (!isset($_GET['export'])) {
    include "footer.php";
}
?>


<script>
$(document).ready(function() {
    $('#reportTable').DataTable({
        "scrollX": true,
        "pageLength": 50,
        "lengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        "dom": 'Bfrtip',
        "buttons": []
    });
});
</script>
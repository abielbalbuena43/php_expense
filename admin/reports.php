<?php
ob_start();
session_start();
include "connection.php";  // Assuming this is your database connection file
include "header.php";  // Keep this to maintain the UI design

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

    // Build the query with filters
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
    $types = "ss";  // Types for prepared statement

    if ($company_id > 0) {
        $query .= " AND e.expense_company_id = ?";  // Changed from += to .=
        $params[] = $company_id;
        $types .= "i";
    }
    if ($category_id > 0) {
        $query .= " AND e.expense_category_id = ?";  // Changed from += to .=
        $params[] = $category_id;
        $types .= "i";
    }
    if ($product_id > 0) {
        $query .= " AND e.expense_product_id = ?";  // Changed from += to .=
        $params[] = $product_id;
        $types .= "i";
    }

    $query .= " ORDER BY e.expense_date DESC, e.expense_id DESC";  // Note: This line was already correct, but ensuring consistency

    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);  // Bind all parameters
    $stmt->execute();
    $result = $stmt->get_result();

    // Set headers for Excel export
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=expense_report.xls");
    header("Pragma: no-cache");
    header("Expires: 0");

    // Output the table (based on columns from expenses.php)
    echo "<table border='1'>";
    echo "<tr>
            <th>Expense ID</th>
            <th>OR Number</th>
            <th>Date</th>
            <th>Company</th>
            <th>Payee</th>
            <th>Category</th>
            <th>Amount</th>
          </tr>";

    while ($row = $result->fetch_assoc()) {
        echo "<tr>
                <td>" . htmlspecialchars($row['expense_id']) . "</td>
                <td>" . htmlspecialchars($row['expense_or_number']) . "</td>
                <td>" . htmlspecialchars(date('M d, Y', strtotime($row['expense_date']))) . "</td>
                <td>" . htmlspecialchars($row['company_name']) . "</td>
                <td>" . htmlspecialchars($row['payee_name']) . "</td>
                <td>" . htmlspecialchars($row['category_name']) . "</td>
                <td>₱" . number_format($row['expense_total_receipt_amount'], 2) . "</td>
              </tr>";
    }

    echo "</table>";
    $stmt->close();
    exit();  // Stop further execution
}

// The main page: Display a simple form for date selection and additional filters
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
                        <h5 style="font-size: 20px; font-weight: 800; color: #000000;">Expense Report</h5>
                    </div>
                    <div class="widget-content">
                        <p>Select filters to export expense records.</p>
                        
                        <!-- Simplified form with date and additional filters -->
                        <form method="get" id="filterForm" style="margin-bottom: 20px;">
                            <!-- Date Filters -->
                            <label for="start_date" style="margin-right: 10px; font-weight: bold; font-size: 14px;">Start Date:</label>
                            <input type="date" name="start_date" id="start_date" style="margin-right: 10px; padding: 5px; font-size: 14px;">
                            
                            <label for="end_date" style="margin-right: 10px; font-weight: bold; font-size: 14px;">End Date:</label>
                            <input type="date" name="end_date" id="end_date" style="margin-right: 10px; padding: 5px; font-size: 14px;">
                            
                            <!-- Company Filter -->
                            <label for="company_id" style="margin-right: 10px; font-weight: bold; font-size: 14px;">Company:</label>
                            <select name="company_id" id="company_id" style="margin-right: 10px; padding: 5px; font-size: 14px;">
                                <option value="0">All Companies</option>
                                <?php while ($row = mysqli_fetch_assoc($companiesResult)) { ?>
                                    <option value="<?php echo $row['company_id']; ?>"><?php echo htmlspecialchars($row['company_name']); ?></option>
                                <?php } ?>
                            </select>
                            
                            <!-- Category Filter -->
                            <label for="category_id" style="margin-right: 10px; font-weight: bold; font-size: 14px;">Category:</label>
                            <select name="category_id" id="category_id" style="margin-right: 10px; padding: 5px; font-size: 14px;">
                                <option value="0">All Categories</option>
                                <?php while ($row = mysqli_fetch_assoc($categoriesResult)) { ?>
                                    <option value="<?php echo $row['category_id']; ?>"><?php echo htmlspecialchars($row['category_name']); ?></option>
                                <?php } ?>
                            </select>
                            
                            <!-- Product Filter -->
                            <label for="product_id" style="margin-right: 10px; font-weight: bold; font-size: 14px;">Product:</label>
                            <select name="product_id" id="product_id" style="margin-right: 10px; padding: 5px; font-size: 14px;">
                                <option value="0">All Products</option>
                                <?php while ($row = mysqli_fetch_assoc($productsResult)) { ?>
                                    <option value="<?php echo $row['product_id']; ?>"><?php echo htmlspecialchars($row['product_name']); ?></option>
                                <?php } ?>
                            </select>
                            
                            <button type="submit" name="export" value="1" class="btn btn-primary" style="padding: 8px 12px; font-size: 14px; font-weight: bold;">Export to Excel</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include "footer.php"; ?>

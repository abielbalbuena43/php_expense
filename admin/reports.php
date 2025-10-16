<?php
ob_start();  // Start output buffering
session_start();
include "connection.php";
include "header.php";

// Include PhpSpreadsheet
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

// Handle export at the top
if (isset($_GET['export'])) {
    $exportMonth = intval($_GET['month'] ?? 0);
    $exportYear = intval($_GET['year'] ?? 0);
    $exportCategory = intval($_GET['category'] ?? 0);
    $exportCompany = intval($_GET['company'] ?? 0);

    $exportWhereClauses = [];
    $exportParams = [];
    $exportTypes = "";

    if ($exportMonth > 0 && $exportYear > 0) {
        $exportWhereClauses[] = "MONTH(e.expense_date) = ? AND YEAR(e.expense_date) = ?";
        $exportParams[] = $exportMonth;
        $exportParams[] = $exportYear;
        $exportTypes .= "ii";
    }
    if ($exportCategory > 0) {
        $exportWhereClauses[] = "e.expense_category_id = ?";
        $exportParams[] = $exportCategory;
        $exportTypes .= "i";
    }
    if ($exportCompany > 0) {
        $exportWhereClauses[] = "e.expense_company_id = ?";
        $exportParams[] = $exportCompany;
        $exportTypes .= "i";
    }

    $exportWhereSql = !empty($exportWhereClauses) ? " WHERE " . implode(" AND ", $exportWhereClauses) : "";

    $exportType = $_GET['export'];
    
    if ($exportType === 'category') {
        $exportCatSql = "
            SELECT cat.category_name, 
                   COUNT(e.expense_id) AS expense_count,
                   COALESCE(SUM(e.expense_total_receipt_amount), 0) AS total_amount
            FROM expenses e
            INNER JOIN expense_categories cat ON e.expense_category_id = cat.category_id
            $exportWhereSql
            GROUP BY cat.category_id 
            ORDER BY total_amount DESC
        ";
        $exportCatStmt = $conn->prepare($exportCatSql);
        if (!empty($exportParams)) {
            $exportCatStmt->bind_param($exportTypes, ...$exportParams);
        }
        $exportCatStmt->execute();
        $exportCatRes = $exportCatStmt->get_result();
        
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Category Report');
        
        // Headers
        $sheet->setCellValue('A1', 'Category');
        $sheet->setCellValue('B1', 'Number of Expenses');
        $sheet->setCellValue('C1', 'Total Amount');
        $sheet->getStyle('A1:C1')->getFont()->setBold(true);
        $sheet->getStyle('A1:C1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('115486');
        $sheet->getStyle('A1:C1')->getFont()->getColor()->setRGB('FFFFFF');
        
        $rowNum = 2;
        $catGrandTotal = 0;
        while ($row = $exportCatRes->fetch_assoc()) {
            $sheet->setCellValue('A'.$rowNum, $row['category_name']);
            $sheet->setCellValue('B'.$rowNum, $row['expense_count']);
            $sheet->setCellValue('C'.$rowNum, $row['total_amount']);
            $sheet->getStyle('C'.$rowNum)->getNumberFormat()->setFormatCode('"$"#,##0.00');
            $catGrandTotal += $row['total_amount'];
            $rowNum++;
        }
        
        // Grand Total
        $sheet->setCellValue('A'.$rowNum, '*** GRAND TOTAL ***');
        $sheet->setCellValue('B'.$rowNum, mysqli_num_rows($exportCatRes));
        $sheet->setCellValue('C'.$rowNum, $catGrandTotal);
        $sheet->getStyle('A'.$rowNum.':C'.$rowNum)->getFont()->setBold(true);
        $sheet->getStyle('C'.$rowNum)->getNumberFormat()->setFormatCode('"$"#,##0.00');
        
        $writer = new Xlsx($spreadsheet);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="expense_report_by_category_' . date('Y-m-d') . '.xlsx"');
        header('Cache-Control: max-age=0');
        $writer->save('php://output');
        exit();
    } elseif ($exportType === 'company') {
        $exportCompSql = "
            SELECT c.company_name, 
                   COUNT(e.expense_id) AS expense_count,
                   COALESCE(SUM(e.expense_total_receipt_amount), 0) AS total_amount
            FROM expenses e
            INNER JOIN companies c ON e.expense_company_id = c.company_id
            $exportWhereSql
            GROUP BY c.company_id 
            ORDER BY total_amount DESC
        ";
        $exportCompStmt = $conn->prepare($exportCompSql);
        if (!empty($exportParams)) {
            $exportCompStmt->bind_param($exportTypes, ...$exportParams);
        }
        $exportCompStmt->execute();
        $exportCompRes = $exportCompStmt->get_result();
        
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Company Report');
        
        // Headers
        $sheet->setCellValue('A1', 'Company');
        $sheet->setCellValue('B1', 'Number of Expenses');
        $sheet->setCellValue('C1', 'Total Amount');
        $sheet->getStyle('A1:C1')->getFont()->setBold(true);
        $sheet->getStyle('A1:C1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('115486');
        $sheet->getStyle('A1:C1')->getFont()->getColor()->setRGB('FFFFFF');
        
        $rowNum = 2;
        $compGrandTotal = 0;
        while ($row = $exportCompRes->fetch_assoc()) {
            $sheet->setCellValue('A'.$rowNum, $row['company_name']);
            $sheet->setCellValue('B'.$rowNum, $row['expense_count']);
            $sheet->setCellValue('C'.$rowNum, $row['total_amount']);
            $sheet->getStyle('C'.$rowNum)->getNumberFormat()->setFormatCode('"$"#,##0.00');
            $compGrandTotal += $row['total_amount'];
            $rowNum++;
        }
        
        // Grand Total
        $sheet->setCellValue('A'.$rowNum, '*** GRAND TOTAL ***');
        $sheet->setCellValue('B'.$rowNum, mysqli_num_rows($exportCompRes));
        $sheet->setCellValue('C'.$rowNum, $compGrandTotal);
        $sheet->getStyle('A'.$rowNum.':C'.$rowNum)->getFont()->setBold(true);
        $sheet->getStyle('C'.$rowNum)->getNumberFormat()->setFormatCode('"$"#,##0.00');
        
        $writer = new Xlsx($spreadsheet);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="expense_report_by_company_' . date('Y-m-d') . '.xlsx"');
        header('Cache-Control: max-age=0');
        $writer->save('php://output');
        exit();
    }
}

// The rest of your code follows...
// Fetch filter options (categories and companies for dropdowns)
$categoriesQuery = "SELECT category_id, category_name FROM expense_categories ORDER BY category_name ASC";
$categoriesResult = mysqli_query($conn, $categoriesQuery);

$companiesQuery = "SELECT company_id, company_name FROM companies ORDER BY company_name ASC";
$companiesResult = mysqli_query($conn, $companiesQuery);

// Process filters
$selectedMonth = intval($_GET['month'] ?? 0);
$selectedYear = intval($_GET['year'] ?? 0);
$selectedCategory = intval($_GET['category'] ?? 0);
$selectedCompany = intval($_GET['company'] ?? 0);

$whereClauses = [];
$params = [];
$types = "";

if ($selectedMonth > 0 && $selectedYear > 0) {
    $whereClauses[] = "MONTH(e.expense_date) = ? AND YEAR(e.expense_date) = ?";
    $params[] = $selectedMonth;
    $params[] = $selectedYear;
    $types .= "ii";
}
if ($selectedCategory > 0) {
    $whereClauses[] = "e.expense_category_id = ?";
    $params[] = $selectedCategory;
    $types .= "i";
}
if ($selectedCompany > 0) {
    $whereClauses[] = "e.expense_company_id = ?";
    $params[] = $selectedCompany;
    $types .= "i";
}

$whereSql = !empty($whereClauses) ? " WHERE " . implode(" AND ", $whereClauses) : "";

// Category Summary Query
$categorySummarySql = "
    SELECT cat.category_name, 
           COUNT(e.expense_id) AS expense_count,
           COALESCE(SUM(e.expense_total_receipt_amount), 0) AS total_amount
    FROM expenses e
    INNER JOIN expense_categories cat ON e.expense_category_id = cat.category_id
    $whereSql
    GROUP BY cat.category_id 
    ORDER BY total_amount DESC
";
$categoryStmt = $conn->prepare($categorySummarySql);
if (!empty($params)) {
    $categoryStmt->bind_param($types, ...$params);
}
$categoryStmt->execute();
$categoryResult = $categoryStmt->get_result();
$categoryStmt->close();

// Company Summary Query
$companySummarySql = "
    SELECT c.company_name, 
           COUNT(e.expense_id) AS expense_count,
           COALESCE(SUM(e.expense_total_receipt_amount), 0) AS total_amount
    FROM expenses e
    INNER JOIN companies c ON e.expense_company_id = c.company_id
    $whereSql
    GROUP BY c.company_id 
    ORDER BY total_amount DESC
";
$companyStmt = $conn->prepare($companySummarySql);
if (!empty($params)) {
    $companyStmt->bind_param($types, ...$exportParams);  // Fix: Use correct params
}
$companyStmt->execute();
$companyResult = $companyStmt->get_result();
$companyStmt->close();

// Dynamic viewing label
$viewingLabel = "Viewing Report";
if ($selectedMonth > 0 && $selectedYear > 0) {
    $periodLabel = date('F Y', mktime(0, 0, 0, $selectedMonth, 1, $selectedYear));
    $viewingLabel += " for: $periodLabel";
}
if ($selectedCategory > 0) {
    $catName = '';
    mysqli_data_seek($categoriesResult, 0);
    while ($row = mysqli_fetch_assoc($categoriesResult)) {
        if ($row['category_id'] == $selectedCategory) {
            $catName = $row['category_name'];
            break;
        }
    }
    $viewingLabel += " | Category: " . htmlspecialchars($catName);
}
if ($selectedCompany > 0) {
    $compName = '';
    mysqli_data_seek($companiesResult, 0);
    while ($row = mysqli_fetch_assoc($companiesResult)) {
        if ($row['company_id'] == $selectedCompany) {
            $compName = $row['company_name'];
            break;
        }
    }
    $viewingLabel += " | Company: " . htmlspecialchars($compName);
}
if (empty($whereClauses)) {
    $viewingLabel += " (All Data)";
}
?>

<!-- The rest of your HTML code remains the same as before -->
<div id="content">
    <!-- ... (rest of the HTML) ... -->
</div>

<?php include "footer.php"; ?>

<!-- Handle CSV Export -->
<?php if (isset($_GET['export'])) {
    // Re-apply filters for export (same as above)
    $exportMonth = intval($_GET['month'] ?? 0);
    $exportYear = intval($_GET['year'] ?? 0);
    $exportCategory = intval($_GET['category'] ?? 0);
    $exportCompany = intval($_GET['company'] ?? 0);

    $exportWhereClauses = [];
    $exportParams = [];
    $exportTypes = "";

    if ($exportMonth > 0 && $exportYear > 0) {
        $exportWhereClauses[] = "MONTH(e.expense_date) = ? AND YEAR(e.expense_date) = ?";
        $exportParams[] = $exportMonth;
        $exportParams[] = $exportYear;
        $exportTypes .= "ii";
    }
    if ($exportCategory > 0) {
        $exportWhereClauses[] = "e.expense_category_id = ?";
        $exportParams[] = $exportCategory;
        $exportTypes .= "i";
    }
    if ($exportCompany > 0) {
        $exportWhereClauses[] = "e.expense_company_id = ?";
        $exportParams[] = $exportCompany;
        $exportTypes .= "i";
    }

    $exportWhereSql = !empty($exportWhereClauses) ? " WHERE " . implode(" AND ", $exportWhereClauses) : "";

    $exportType = $_GET['export'];
    if ($exportType === 'category') {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="expense_report_by_category_' . date('Y-m-d') . '.csv"');
        $output = fopen('php://output', 'w');
        fputcsv($output, ['Category', 'Number of Expenses', 'Total Amount']);  // Headers

        $exportCatSql = "
            SELECT cat.category_name, 
                   COUNT(e.expense_id) AS expense_count,
                   COALESCE(SUM(e.expense_total_receipt_amount), 0) AS total_amount
            FROM expenses e
            INNER JOIN expense_categories cat ON e.expense_category_id = cat.category_id
            $exportWhereSql
            GROUP BY cat.category_id 
            ORDER BY total_amount DESC
        ";
        $exportCatStmt = $conn->prepare($exportCatSql);
        if (!empty($exportParams)) {
            $exportCatStmt->bind_param($exportTypes, ...$exportParams);
        }
        $exportCatStmt->execute();
        $exportCatRes = $exportCatStmt->get_result();
        $catGrandTotal = 0;
        while ($row = $exportCatRes->fetch_assoc()) {
            fputcsv($output, [
                $row['category_name'],
                $row['expense_count'],
                '₱' . number_format($row['total_amount'], 2)
            ]);
            $catGrandTotal += $row['total_amount'];
        }
        // Grand Total row (bold via text; CSV has no styling)
        fputcsv($output, [
            '*** GRAND TOTAL ***',
            mysqli_num_rows($exportCatRes),
            '₱' . number_format($catGrandTotal, 2)
        ]);
        fclose($output);
        exit();
    } elseif ($exportType === 'company') {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="expense_report_by_company_' . date('Y-m-d') . '.csv"');
        $output = fopen('php://output', 'w');
        fputcsv($output, ['Company', 'Number of Expenses', 'Total Amount']);  // Headers

        $exportCompSql = "
            SELECT c.company_name, 
                   COUNT(e.expense_id) AS expense_count,
                   COALESCE(SUM(e.expense_total_receipt_amount), 0) AS total_amount
            FROM expenses e
            INNER JOIN companies c ON e.expense_company_id = c.company_id
            $exportWhereSql
            GROUP BY c.company_id 
            ORDER BY total_amount DESC
        ";
        $exportCompStmt = $conn->prepare($exportCompSql);
        if (!empty($exportParams)) {
            $exportCompStmt->bind_param($exportTypes, ...$exportParams);
        }
        $exportCompStmt->execute();
        $exportCompRes = $exportCompStmt->get_result();
        $compGrandTotal = 0;
        while ($row = $exportCompRes->fetch_assoc()) {
            fputcsv($output, [
                $row['company_name'],
                $row['expense_count'],
                '₱' . number_format($row['total_amount'], 2)
            ]);
            $compGrandTotal += $row['total_amount'];
        }
        // Grand Total row (bold via text; CSV has no styling)
        fputcsv($output, [
            '*** GRAND TOTAL ***',
            mysqli_num_rows($exportCompRes),
            '₱' . number_format($compGrandTotal, 2)
        ]);
        fclose($output);
        exit();
    }
}
?>


<script>
$(document).ready(function() {
    // Clear Filter functionality (resets all dropdowns)
    $('#clearFilter').click(function() {
        $('select[name="month"]').val(0);
        $('select[name="year"]').val(0);
        $('select[name="category"]').val(0);
        $('select[name="company"]').val(0);
        $('#filterForm').submit();  // Submit to reload with all
    });

    // DataTables for tables (sortable/searchable, like expenses.php)
    $('#categoryTable').DataTable({
        "scrollX": true,
        "pageLength": 25,
        "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "All"]]
    });

    $('#companyTable').DataTable({
        "scrollX": true,
        "pageLength": 25,
        "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "All"]]
    });
});
</script>

<style>
/* Global font uniformity (matches dashboard.php) */
body {
    font-family: 'Arial', sans-serif;
    font-size: 14px; /* Base size for consistency with dashboard */
    line-height: 1.4;
}

/* Filter section uniformity (matches dashboard summary boxes) */
.filter-section {
    background: #ffffff;
    padding: 20px;
    box-shadow: 2px 2px 10px rgba(0, 0, 0, 0.1);
    margin-bottom: 20px;
    border-radius: 4px; /* Subtle rounding to match boxes */
}

.filter-section label {
    font-weight: bold;
    color: #000000;
    margin-right: 10px;
    font-size: 14px; /* Matches dashboard labels */
}

.filter-section select,
.filter-section button {
    padding: 8px 12px;
    font-size: 14px; /* Uniform with dashboard form elements */
    border: 1px solid #ddd;
    border-radius: 3px;
    margin-right: 10px;
}

.filter-section button {
    background: #007bff;
    color: white;
    cursor: pointer;
    font-weight: bold;
    font-size: 14px; /* Explicit for buttons */
}

.filter-section button:hover {
    background: #0056b3;
}

/* Ensure table titles match dashboard h4 */
.widget-box h4 {
    font-size: 20px; /* Matches dashboard chart/logs h4 */
    font-weight: 800;
    color: #000000;
    margin-bottom: 15px;
    text-align: center;
}

/* Export buttons uniformity */
.btn-success {
    font-size: 14px; /* Matches dashboard buttons */
    font-weight: bold;
    padding: 8px 12px;
}

/* Table styles (uniform with dashboard.css) */
#categoryTable, #companyTable {
    width: 100% !important;
    background-color: #ffffff;
    box-shadow: 4px 4px 10px rgba(0, 0, 0, 0.1);
    overflow: hidden;
    border-radius: 12px;
}

#categoryTable thead th, #companyTable thead th {
    background-color: #115486 !important;  /* Blue header */
    color: #ffffff !important;
    font-size: 14px; /* Matches dashboard table headers */
    font-weight: bold;
    text-transform: uppercase;
    border-bottom: 2px solid #ddd;
    padding: 14px;
}

#categoryTable tbody td, #companyTable tbody td {
    padding: 12px;
    font-size: 13px; /* Matches dashboard table cells */
    color: #000000;
    border-bottom: 1px solid #ddd;
    vertical-align: middle;
}

#categoryTable tbody tr:nth-child(odd), #companyTable tbody tr:nth-child(odd) {
    background-color: #f0f0f0;
}

#categoryTable tbody tr:nth-child(even), #companyTable tbody tr:nth-child(even) {
    background-color: #ffffff;
}

#categoryTable tbody tr:hover, #companyTable tbody tr:hover {
    background-color: #d6d6d6;
}

/* Borders and rounding */
#categoryTable td, #categoryTable th, #companyTable td, #companyTable th {
    border-right: 1px solid #ddd;
}

#categoryTable th:last-child, #categoryTable td:last-child, #companyTable th:last-child, #companyTable td:last-child {
    border-right: none;
}

#categoryTable thead tr:first-child th:first-child, #companyTable thead tr:first-child th:first-child {
    border-top-left-radius: 12px;
}

#categoryTable thead tr:first-child th:last-child, #companyTable thead tr:first-child th:last-child {
    border-top-right-radius: 12px;
}

/* Footer grand total style */
tfoot tr {
    background-color: #f8f9fa !important;
    font-weight: bold;
}
</style>

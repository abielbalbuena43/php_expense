<?php
include "connection.php";  // Your database connection

// Get filters
$selectedMonth = intval($_GET['month'] ?? date('m'));
$selectedYear = intval($_GET['year'] ?? date('Y'));

// Query data (same as before)
$stmt = $conn->prepare("SELECT e.expense_id, e.expense_or_number, e.expense_date, e.expense_total_receipt_amount, c.company_name, cat.category_name
    FROM expenses e
    JOIN companies c ON e.expense_company_id = c.company_id
    JOIN expense_categories cat ON e.expense_category_id = cat.category_id
    WHERE MONTH(e.expense_date) = ? AND YEAR(e.expense_date) = ?");
$stmt->bind_param("ii", $selectedMonth, $selectedYear);
$stmt->execute();
$result = $stmt->get_result();
$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}
$stmt->close();

// Use PhpSpreadsheet
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;  // For layouting

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Set headers and basic layout
$sheet->setCellValue('A1', 'ID');
$sheet->setCellValue('B1', 'OR Number');
$sheet->setCellValue('C1', 'Date');
$sheet->setCellValue('D1', 'Amount');
$sheet->setCellValue('E1', 'Company');
$sheet->setCellValue('F1', 'Category');

// Style the header row
$sheet->getStyle('A1:F1')->getFont()->setBold(true);
$sheet->getStyle('A1:F1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('A9A9A9');  // Gray background
$sheet->getStyle('A1:F1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

// Add data starting from row 2
$rowNumber = 2;
foreach ($data as $row) {
    $sheet->setCellValue('A' . $rowNumber, $row['expense_id']);
    $sheet->setCellValue('B' . $rowNumber, $row['expense_or_number']);
    $sheet->setCellValue('C' . $rowNumber, $row['expense_date']);
    $sheet->setCellValue('D' . $rowNumber, $row['expense_total_receipt_amount']);
    $sheet->setCellValue('E' . $rowNumber, $row['company_name']);
    $sheet->setCellValue('F' . $rowNumber, $row['category_name']);
    $rowNumber++;
}

// Auto-size columns
foreach (range('A', 'F') as $column) {
    $sheet->getColumnDimension($column)->setAutoSize(true);
}

// Output the file
$writer = new Xlsx($spreadsheet);
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="expense_report.xlsx"');
header('Cache-Control: max-age=0');
$writer->save('php://output');
exit;
?>
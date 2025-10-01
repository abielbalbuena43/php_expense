<?php
include "connection.php";
include "header.php";

if (!isset($_POST['expense_id'])) {
    echo "No expense selected.";
    exit;
}

$expense_id = $_POST['expense_id'];

// Fetch full expense details
$sql = "
    SELECT e.*, 
           c.company_name, 
           p.payee_name, 
           cat.category_name, 
           r.reseller_name
    FROM expenses e
    INNER JOIN companies c ON e.expense_company_id = c.company_id
    INNER JOIN payees p ON e.expense_payee_id = p.payee_id
    INNER JOIN expense_categories cat ON e.expense_category_id = cat.category_id
    LEFT JOIN resellers r ON e.expense_reseller_id = r.reseller_id
    WHERE e.expense_id = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $expense_id);
$stmt->execute();
$result = $stmt->get_result();
$expense = $result->fetch_assoc();

if (!$expense) {
    echo "Expense not found.";
    exit;
}
?>

<div id="content">
    <div class="container-fluid">
        <h2>Expense Details - OR <?php echo htmlspecialchars($expense['expense_or_number']); ?></h2>

        <table class="table table-bordered">
            <tr>
                <th>Date</th>
                <td><?php echo date('M d, Y', strtotime($expense['expense_date'])); ?></td>
            </tr>
            <tr>
                <th>Company</th>
                <td><?php echo htmlspecialchars($expense['company_name']); ?></td>
            </tr>
            <tr>
                <th>Payee</th>
                <td><?php echo htmlspecialchars($expense['payee_name']); ?></td>
            </tr>
            <tr>
                <th>Reseller</th>
                <td><?php echo htmlspecialchars($expense['reseller_name'] ?? '-'); ?></td>
            </tr>
            <tr>
                <th>Category</th>
                <td><?php echo htmlspecialchars($expense['category_name']); ?></td>
            </tr>
            <tr>
                <th>Total Purchases</th>
                <td><?php echo number_format($expense['expense_total_purchases'], 2); ?></td>
            </tr>
            <tr>
                <th>Total Receipt</th>
                <td><?php echo number_format($expense['expense_total_receipt_amount'], 2); ?></td>
            </tr>
            <tr>
                <th>Gross Taxable</th>
                <td><?php echo number_format($expense['expense_gross_taxable'], 2); ?></td>
            </tr>
            <tr>
                <th>Service Charge</th>
                <td><?php echo number_format($expense['expense_service_charge'], 2); ?></td>
            </tr>
            <tr>
                <th>Capital Goods</th>
                <td><?php echo number_format($expense['expense_capital_goods'], 2); ?></td>
            </tr>
            <tr>
                <th>Other Goods</th>
                <td><?php echo number_format($expense['expense_goods_other_than_capital'], 2); ?></td>
            </tr>
            <tr>
                <th>Exempt</th>
                <td><?php echo number_format($expense['expense_exempt'], 2); ?></td>
            </tr>
            <tr>
                <th>Zero Rated</th>
                <td><?php echo number_format($expense['expense_zero_rated'], 2); ?></td>
            </tr>
            <tr>
                <th>VAT Rate</th>
                <td><?php echo number_format($expense['expense_vat_rate'], 2); ?>%</td>
            </tr>
            <tr>
                <th>Total Input Tax</th>
                <td><?php echo number_format($expense['expense_total_input_tax'], 2); ?></td>
            </tr>
            <tr>
                <th>Taxable Net VAT</th>
                <td><?php echo number_format($expense['expense_taxable_net_vat'], 2); ?></td>
            </tr>
            <tr>
                <th>Remarks</th>
                <td><?php echo htmlspecialchars($expense['expense_remarks']); ?></td>
            </tr>
        </table>

        <a href="expenses.php" class="btn btn-default">Back to Expenses</a>
    </div>
</div>

<?php include "footer.php"; ?>

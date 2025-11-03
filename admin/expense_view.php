<?php
ob_start();
session_start();
include "header.php"; 
include "connection.php";

// Validate expense ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "<div class='alert alert-danger'>Invalid Expense ID.</div>";
    exit();
}

$expense_id = intval($_GET['id']);

// Fetch expense details
$query = "
    SELECT e.*, 
           c.company_name,
           c.company_tin,
           p.payee_name, 
           cat.category_name, 
           r.reseller_name,
           eu.end_user_name,
           pr.product_name
    FROM expenses e
    INNER JOIN companies c ON e.expense_company_id = c.company_id
    INNER JOIN payees p ON e.expense_payee_id = p.payee_id
    INNER JOIN expense_categories cat ON e.expense_category_id = cat.category_id
    LEFT JOIN resellers r ON e.expense_reseller_id = r.reseller_id
    LEFT JOIN expense_end_users eu ON e.expense_user_id = eu.end_user_id
    LEFT JOIN expense_products pr ON e.expense_product_id = pr.product_id
    WHERE e.expense_id = '$expense_id'
    LIMIT 1
";
$result = mysqli_query($conn, $query);

if (!$result || mysqli_num_rows($result) == 0) {
    echo "<div class='alert alert-danger'>Expense record not found.</div>";
    exit();
}

$expense = mysqli_fetch_assoc($result);
?>

<div id="content">
    <div id="content-header">
        <div id="breadcrumb">
            <a href="expenses.php" class="tip-bottom"><i class="icon-home"></i> Expenses</a>
            <a href="#" class="current">View Expense</a>
        </div>
    </div>

    <div class="container-fluid">
        <div class="row-fluid" style="background-color: white; min-height: 600px; padding: 20px;">
            <div class="span12">

                <!-- View Expense -->
                <div class="widget-box" style="max-width: 800px; margin: 0 auto;">
                    <div class="widget-title">
                        <span class="icon"><i class="icon-align-justify"></i></span>
                        <h5>Expense Information</h5>
                    </div>

                    <div class="widget-content" style="padding: 20px;">
                        <form class="form-horizontal">

                            <!-- Company -->
                            <div class="control-group">
                                <label class="control-label">Company:</label>
                                <div class="controls">
                                    <input type="text" class="span11" value="<?= htmlspecialchars($expense['company_name']) ?>" disabled>
                                </div>
                            </div>

                            <!-- Company TIN -->
                            <div class="control-group">
                                <label class="control-label">Company TIN:</label>
                                <div class="controls">
                                    <input type="text" class="span11" value="<?= htmlspecialchars($expense['company_tin']) ?>" disabled>
                                </div>
                            </div>

                            <!-- Payee -->
                            <div class="control-group">
                                <label class="control-label">Payee:</label>
                                <div class="controls">
                                    <input type="text" class="span11" value="<?= htmlspecialchars($expense['payee_name']) ?>" disabled>
                                </div>
                            </div>

                            <!-- Category -->
                            <div class="control-group">
                                <label class="control-label">Category:</label>
                                <div class="controls">
                                    <input type="text" class="span11" value="<?= htmlspecialchars($expense['category_name']) ?>" disabled>
                                </div>
                            </div>

                            <!-- Reseller -->
                            <div class="control-group">
                                <label class="control-label">Reseller:</label>
                                <div class="controls">
                                    <input type="text" class="span11" value="<?= htmlspecialchars($expense['reseller_name'] ?? 'None') ?>" disabled>
                                </div>
                            </div>

                            <!-- End User -->
                                <div class="control-group">
                                    <label class="control-label">End User:</label>
                                    <div class="controls">
                                        <input type="text" class="span11" value="<?= htmlspecialchars($expense['end_user_name'] ?? 'None') ?>" disabled>
                                    </div>
                                </div>

                                <!-- Product -->
                                <div class="control-group">
                                    <label class="control-label">Product:</label>
                                    <div class="controls">
                                        <input type="text" class="span11" value="<?= htmlspecialchars($expense['product_name'] ?? 'None') ?>" disabled>
                                    </div>
                                </div>

                            <!-- OR Number -->
                            <div class="control-group">
                                <label class="control-label">OR Number:</label>
                                <div class="controls">
                                    <input type="text" class="span11" value="<?= htmlspecialchars($expense['expense_or_number']) ?>" disabled>
                                </div>
                            </div>

                            <!-- Date -->
                            <div class="control-group">
                                <label class="control-label">Date:</label>
                                <div class="controls">
                                    <input type="text" class="span11" value="<?= date('M d, Y', strtotime($expense['expense_date'])) ?>" disabled>
                                </div>
                            </div>

                            <!-- ===== New Fields Added Below ===== -->

                            <!-- Gross Taxable -->
                            <div class="control-group">
                                <label class="control-label">Gross Taxable:</label>
                                <div class="controls">
                                    <input type="text" class="span11" value="<?= number_format($expense['expense_gross_taxable'], 2) ?>" disabled>
                                </div>
                            </div>

                            <!-- Service Charge -->
                            <div class="control-group">
                                <label class="control-label">Service Charge:</label>
                                <div class="controls">
                                    <input type="text" class="span11" value="<?= number_format($expense['expense_service_charge'], 2) ?>" disabled>
                                </div>
                            </div>

                            <!-- Services -->
                            <div class="control-group">
                                <label class="control-label">Services:</label>
                                <div class="controls">
                                    <input type="text" class="span11" value="<?= number_format($expense['expense_services'], 2) ?>" disabled>
                                </div>
                            </div>

                            <!-- Capital Goods -->
                            <div class="control-group">
                                <label class="control-label">Capital Goods:</label>
                                <div class="controls">
                                    <input type="text" class="span11" value="<?= number_format($expense['expense_capital_goods'], 2) ?>" disabled>
                                </div>
                            </div>

                            <!-- Goods Other than Capital Goods -->
                            <div class="control-group">
                                <label class="control-label">Goods Other than Capital Goods:</label>
                                <div class="controls">
                                    <input type="text" class="span11" value="<?= number_format($expense['expense_goods_other_than_capital'], 2) ?>" disabled>
                                </div>
                            </div>

                            <!-- Exempt -->
                            <div class="control-group">
                                <label class="control-label">Exempt:</label>
                                <div class="controls">
                                    <input type="text" class="span11" value="<?= number_format($expense['expense_exempt'], 2) ?>" disabled>
                                </div>
                            </div>

                            <!-- Zero Rated -->
                            <div class="control-group">
                                <label class="control-label">Zero Rated:</label>
                                <div class="controls">
                                    <input type="text" class="span11" value="<?= number_format($expense['expense_zero_rated'], 2) ?>" disabled>
                                </div>
                            </div>

                            <!-- VAT Rate -->
                            <div class="control-group">
                                <label class="control-label">VAT Rate (%):</label>
                                <div class="controls">
                                    <input type="text" class="span11" value="<?= htmlspecialchars($expense['expense_vat_rate']) ?>" disabled>
                                </div>
                            </div>

                            <!-- ===== End of Added Fields ===== -->

                            <!-- Total Purchases -->
                            <div class="control-group">
                                <label class="control-label">Total Purchases:</label>
                                <div class="controls">
                                    <input type="text" class="span11" value="<?= number_format($expense['expense_total_purchases'], 2) ?>" disabled>
                                </div>
                            </div>

                            <!-- Total Input Tax -->
                            <div class="control-group">
                                <label class="control-label">Total Input Tax (12%):</label>
                                <div class="controls">
                                    <input type="text" class="span11" value="<?= number_format($expense['expense_total_input_tax'], 2) ?>" disabled>
                                </div>
                            </div>

                            <!-- Total Receipt Amount -->
                            <div class="control-group">
                                <label class="control-label">Total Receipt Amount:</label>
                                <div class="controls">
                                    <input type="text" class="span11" value="<?= number_format($expense['expense_total_receipt_amount'], 2) ?>" disabled>
                                </div>
                            </div>

                            <!-- Taxable (Net of VAT) -->
                            <div class="control-group">
                                <label class="control-label">Taxable (Net of VAT):</label>
                                <div class="controls">
                                    <input type="text" class="span11" 
                                        value="<?= number_format($expense['expense_taxable_net_vat'], 2) ?>" 
                                        disabled>
                                </div>
                            </div>

                            <!-- Remarks -->
                            <div class="control-group">
                                <label class="control-label">Remarks:</label>
                                <div class="controls">
                                    <textarea class="span11" disabled><?= htmlspecialchars($expense['expense_remarks']) ?></textarea>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="form-actions" style="padding-left: 180px;">
                                <a href="expense_edit.php?id=<?= $expense['expense_id'] ?>" class="btn btn-primary">Edit Expense</a>
                                <a href="expenses.php" class="btn btn-secondary">Back</a>
                            </div>

                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<?php include "footer.php"; ?>

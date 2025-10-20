<?php
ob_start();
session_start();
include "header.php";
include "connection.php";

// Validate ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: expenses.php");
    exit();
}

$expense_id = intval($_GET['id']);

// Fetch dropdown data
$companies = mysqli_query($conn, "SELECT company_id, company_name FROM companies ORDER BY company_name ASC");
$payees = mysqli_query($conn, "SELECT payee_id, payee_name FROM payees ORDER BY payee_name ASC");
$categories = mysqli_query($conn, "SELECT category_id, category_name FROM expense_categories ORDER BY category_name ASC");
$resellers = mysqli_query($conn, "SELECT reseller_id, reseller_name FROM resellers ORDER BY reseller_name ASC");
$end_users = mysqli_query($conn, "SELECT end_user_id, end_user_name FROM expense_end_users ORDER BY end_user_name ASC");
$products = mysqli_query($conn, "SELECT product_id, product_name FROM expense_products ORDER BY product_name ASC");

// Fetch current expense record
$expense_query = mysqli_query($conn, "SELECT * FROM expenses WHERE expense_id = '$expense_id' LIMIT 1");
if (mysqli_num_rows($expense_query) === 0) {
    echo "<div class='alert alert-danger'>Expense record not found.</div>";
    exit();
}
$expense = mysqli_fetch_assoc($expense_query);

// Handle update form submission
if (isset($_POST['update_expense'])) {
    $company_id = mysqli_real_escape_string($conn, $_POST['expense_company_id']);
    $payee_id = mysqli_real_escape_string($conn, $_POST['expense_payee_id']);
    $category_id = mysqli_real_escape_string($conn, $_POST['expense_category_id']);
    $reseller_id = !empty($_POST['expense_reseller_id']) ? mysqli_real_escape_string($conn, $_POST['expense_reseller_id']) : NULL;
    $or_number = mysqli_real_escape_string($conn, $_POST['expense_or_number']);
    $expense_date = mysqli_real_escape_string($conn, $_POST['expense_date']);
    $remarks = mysqli_real_escape_string($conn, $_POST['expense_remarks']);

    // Core numeric fields
    $gross_taxable = floatval($_POST['expense_gross_taxable']);
    $service_charge = floatval($_POST['expense_service_charge']);
    $services = floatval($_POST['expense_services']);
    $capital_goods = floatval($_POST['expense_capital_goods']);
    $goods_other = floatval($_POST['expense_goods_other_than_capital']);
    $exempt = floatval($_POST['expense_exempt']);
    $zero_rated = floatval($_POST['expense_zero_rated']);
    $vat_rate = floatval($_POST['expense_vat_rate']);

    // ===== Recalculate to prevent tampering =====
    $total_purchases = $services + $capital_goods + $goods_other;
    $total_input_tax = round($total_purchases * ($vat_rate / 100), 2);
    $total_receipt_amount = round($gross_taxable + $service_charge, 2);

    // Update query
    $query = "
    UPDATE expenses SET
        expense_company_id = '$company_id',
        expense_payee_id = '$payee_id',
        expense_category_id = '$category_id',
        expense_reseller_id = " . ($reseller_id ? "'$reseller_id'" : "NULL") . ",
        expense_user_id = " . (!empty($_POST['expense_user_id']) ? "'".mysqli_real_escape_string($conn, $_POST['expense_user_id'])."'" : "NULL") . ",
        expense_product_id = " . (!empty($_POST['expense_product_id']) ? "'".mysqli_real_escape_string($conn, $_POST['expense_product_id'])."'" : "NULL") . ",
        expense_or_number = '$or_number',
        expense_date = '$expense_date',
        expense_gross_taxable = '$gross_taxable',
        expense_service_charge = '$service_charge',
        expense_services = '$services',
        expense_capital_goods = '$capital_goods',
        expense_goods_other_than_capital = '$goods_other',
        expense_exempt = '$exempt',
        expense_zero_rated = '$zero_rated',
        expense_taxable_net_vat = '$taxable_net_vat',
        expense_vat_rate = '$vat_rate',
        expense_total_purchases = '$total_purchases',
        expense_total_input_tax = '$total_input_tax',
        expense_total_receipt_amount = '$total_receipt_amount',
        expense_remarks = '$remarks',
        expense_updated_at = NOW()
    WHERE expense_id = '$expense_id'
";

    if (mysqli_query($conn, $query)) {
        $_SESSION['alert'] = "success_update";
        header("Location: expenses.php");
        exit();
    } else {
        $_SESSION['alert'] = "error_update";
    }
}

$alert = $_SESSION['alert'] ?? null;
unset($_SESSION['alert']);
?>

<div id="content">
    <div id="content-header">
        <div id="breadcrumb">
            <a href="expenses.php" class="tip-bottom"><i class="icon-home"></i> Expenses</a>
            <a href="#" class="current">Edit Expense</a>
        </div>
    </div>

    <div class="container-fluid">
        <div class="row-fluid" style="background-color: white; min-height: 600px; padding: 20px;">
            <div class="span12">

                <?php if ($alert == "success_update") { ?>
                    <div class="alert alert-success">Expense updated successfully!</div>
                <?php } elseif ($alert == "error_update") { ?>
                    <div class="alert alert-danger">Error: Unable to update expense.</div>
                <?php } ?>

                <div class="widget-box" style="max-width: 800px; margin: 0 auto;">
                    <div class="widget-title">
                        <span class="icon"><i class="icon-edit"></i></span>
                        <h5>Edit Expense Information</h5>
                    </div>

                    <div class="widget-content" style="padding: 20px;">
                        <form action="" method="post" class="form-horizontal">

                            <!-- Company -->
                            <div class="control-group">
                                <label class="control-label">Company:</label>
                                <div class="controls">
                                    <select name="expense_company_id" class="span11" required>
                                        <?php while ($row = mysqli_fetch_assoc($companies)) { ?>
                                            <option value="<?= $row['company_id'] ?>" <?= $row['company_id'] == $expense['expense_company_id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($row['company_name']) ?>
                                            </option>
                                        <?php } ?>
                                    </select>
                                </div>
                            </div>

                            <!-- Payee -->
                            <div class="control-group">
                                <label class="control-label">Payee:</label>
                                <div class="controls">
                                    <select name="expense_payee_id" class="span11" required>
                                        <?php while ($row = mysqli_fetch_assoc($payees)) { ?>
                                            <option value="<?= $row['payee_id'] ?>" <?= $row['payee_id'] == $expense['expense_payee_id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($row['payee_name']) ?>
                                            </option>
                                        <?php } ?>
                                    </select>
                                </div>
                            </div>

                            <!-- Category -->
                            <div class="control-group">
                                <label class="control-label">Category:</label>
                                <div class="controls">
                                    <select name="expense_category_id" class="span11" required>
                                        <?php while ($row = mysqli_fetch_assoc($categories)) { ?>
                                            <option value="<?= $row['category_id'] ?>" <?= $row['category_id'] == $expense['expense_category_id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($row['category_name']) ?>
                                            </option>
                                        <?php } ?>
                                    </select>
                                </div>
                            </div>

                            <!-- Reseller -->
                            <div class="control-group">
                                <label class="control-label">Reseller:</label>
                                <div class="controls">
                                    <select name="expense_reseller_id" class="span11">
                                        <option value="">None</option>
                                        <?php while ($row = mysqli_fetch_assoc($resellers)) { ?>
                                            <option value="<?= $row['reseller_id'] ?>" <?= $row['reseller_id'] == $expense['expense_reseller_id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($row['reseller_name']) ?>
                                            </option>
                                        <?php } ?>
                                    </select>
                                </div>
                            </div>

                            <!-- End User -->
                            <div class="control-group">
                                <label class="control-label">End User:</label>
                                <div class="controls">
                                    <select name="expense_user_id" class="span11">
                                        <option value="">None</option>
                                        <?php while ($row = mysqli_fetch_assoc($end_users)) { ?>
                                            <option value="<?= $row['end_user_id'] ?>" <?= $row['end_user_id'] == $expense['expense_user_id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($row['end_user_name']) ?>
                                            </option>
                                        <?php } ?>
                                    </select>
                                </div>
                            </div>

                            <!-- Product -->
                            <div class="control-group">
                                <label class="control-label">Product:</label>
                                <div class="controls">
                                    <select name="expense_product_id" class="span11">
                                        <option value="">None</option>
                                        <?php while ($row = mysqli_fetch_assoc($products)) { ?>
                                            <option value="<?= $row['product_id'] ?>" <?= $row['product_id'] == $expense['expense_product_id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($row['product_name']) ?>
                                            </option>
                                        <?php } ?>
                                    </select>
                                </div>
                            </div>

                            <!-- OR Number -->
                            <div class="control-group">
                                <label class="control-label">OR Number:</label>
                                <div class="controls">
                                    <input type="text" class="span11" name="expense_or_number"
                                           value="<?= htmlspecialchars($expense['expense_or_number']) ?>" required>
                                </div>
                            </div>

                            <!-- Date -->
                            <div class="control-group">
                                <label class="control-label">Date:</label>
                                <div class="controls">
                                    <input type="date" class="span11" name="expense_date"
                                           value="<?= htmlspecialchars($expense['expense_date']) ?>" required>
                                </div>
                            </div>

                            <!-- ===== Editable Numeric Fields ===== -->

                            <div class="control-group">
                                <label class="control-label">Gross Taxable:</label>
                                <div class="controls">
                                    <input type="number" step="0.01" class="span11 calc-field" name="expense_gross_taxable"
                                           value="<?= htmlspecialchars($expense['expense_gross_taxable']) ?>">
                                </div>
                            </div>

                            <div class="control-group">
                                <label class="control-label">Service Charge:</label>
                                <div class="controls">
                                    <input type="number" step="0.01" class="span11 calc-field" name="expense_service_charge"
                                           value="<?= htmlspecialchars($expense['expense_service_charge']) ?>">
                                </div>
                            </div>

                            <div class="control-group">
                                <label class="control-label">Services:</label>
                                <div class="controls">
                                    <input type="number" step="0.01" class="span11 calc-field" name="expense_services"
                                           value="<?= htmlspecialchars($expense['expense_services']) ?>">
                                </div>
                            </div>

                            <div class="control-group">
                                <label class="control-label">Capital Goods:</label>
                                <div class="controls">
                                    <input type="number" step="0.01" class="span11 calc-field" name="expense_capital_goods"
                                           value="<?= htmlspecialchars($expense['expense_capital_goods']) ?>">
                                </div>
                            </div>

                            <div class="control-group">
                                <label class="control-label">Goods Other than Capital Goods:</label>
                                <div class="controls">
                                    <input type="number" step="0.01" class="span11 calc-field" name="expense_goods_other_than_capital"
                                           value="<?= htmlspecialchars($expense['expense_goods_other_than_capital']) ?>">
                                </div>
                            </div>

                            <div class="control-group">
                                <label class="control-label">Exempt:</label>
                                <div class="controls">
                                    <input type="number" step="0.01" class="span11 calc-field" name="expense_exempt"
                                           value="<?= htmlspecialchars($expense['expense_exempt']) ?>">
                                </div>
                            </div>

                            <div class="control-group">
                                <label class="control-label">Zero Rated:</label>
                                <div class="controls">
                                    <input type="number" step="0.01" class="span11 calc-field" name="expense_zero_rated"
                                           value="<?= htmlspecialchars($expense['expense_zero_rated']) ?>">
                                </div>
                            </div>

                            <div class="control-group">
                                <label class="control-label">VAT Rate (%):</label>
                                <div class="controls">
                                    <input type="number" step="0.01" class="span11 calc-field" name="expense_vat_rate"
                                           value="<?= htmlspecialchars($expense['expense_vat_rate']) ?>">
                                </div>
                            </div>

                            <!-- ===== Calculated Fields ===== -->

                            <div class="control-group">
                                <label class="control-label">Total Purchases:</label>
                                <div class="controls">
                                    <input type="number" class="span11" id="expense_total_purchases" readonly
                                           value="<?= htmlspecialchars($expense['expense_total_purchases']) ?>">
                                </div>
                            </div>

                            <div class="control-group">
                                <label class="control-label">Total Input Tax:</label>
                                <div class="controls">
                                    <input type="number" class="span11" id="expense_total_input_tax" readonly
                                           value="<?= htmlspecialchars($expense['expense_total_input_tax']) ?>">
                                </div>
                            </div>

                            <div class="control-group">
                                <label class="control-label">Total Receipt Amount:</label>
                                <div class="controls">
                                    <input type="number" class="span11" id="expense_total_receipt_amount" readonly
                                           value="<?= htmlspecialchars($expense['expense_total_receipt_amount']) ?>">
                                </div>
                            </div>

                            <div class="control-group">
                                <label class="control-label">Taxable (Net of VAT):</label>
                                <div class="controls">
                                    <input type="number" class="span11" id="expense_taxable_net_vat" readonly
                                        value="<?= htmlspecialchars($expense['expense_taxable_net_vat']) ?>">
                                </div>
                            </div>

                            <!-- JS Calculation Logic -->
                            <script>
                            document.addEventListener("DOMContentLoaded", function() {
                                const fields = document.querySelectorAll(".calc-field");

                                // Get references to calculated fields
                                const totalPurchasesInput = document.getElementById("expense_total_purchases");
                                const inputTaxInput = document.getElementById("expense_total_input_tax");
                                const totalReceiptInput = document.getElementById("expense_total_receipt_amount");
                                const taxableNetVatInput = document.getElementById("expense_taxable_net_vat");

                                function recalc() {
                                    // Get all field values or default to 0
                                    let grossTaxable = parseFloat(document.querySelector("input[name='expense_gross_taxable']").value) || 0;
                                    let serviceCharge = parseFloat(document.querySelector("input[name='expense_service_charge']").value) || 0;
                                    let services = parseFloat(document.querySelector("input[name='expense_services']").value) || 0;
                                    let capitalGoods = parseFloat(document.querySelector("input[name='expense_capital_goods']").value) || 0;
                                    let goodsOther = parseFloat(document.querySelector("input[name='expense_goods_other_than_capital']").value) || 0;
                                    let exempt = parseFloat(document.querySelector("input[name='expense_exempt']").value) || 0;
                                    let zeroRated = parseFloat(document.querySelector("input[name='expense_zero_rated']").value) || 0;
                                    let vatRate = parseFloat(document.querySelector("input[name='expense_vat_rate']").value) || 0;

                                    // --- Calculations ---

                                    // Total Purchases (taxable amounts only)
                                    let totalPurchases = services + capitalGoods + goodsOther;

                                    // Total Input Tax
                                    let inputTax = totalPurchases * (vatRate / 100);

                                    // Taxable (Net of VAT)
                                    let taxableNetVat = totalPurchases - inputTax;

                                    // Total Receipt Amount
                                    let totalReceipt = grossTaxable + serviceCharge + exempt + zeroRated + inputTax;

                                    // --- Update fields ---
                                    totalPurchasesInput.value = totalPurchases.toFixed(2);
                                    inputTaxInput.value = inputTax.toFixed(2);
                                    totalReceiptInput.value = totalReceipt.toFixed(2);
                                    taxableNetVatInput.value = taxableNetVat.toFixed(2);
                                }

                                // Run recalculation when any field changes
                                fields.forEach(field => field.addEventListener("input", recalc));

                                // Initialize on page load
                                recalc();
                            });
                            </script>

                            <!-- Remarks -->
                            <div class="control-group">
                                <label class="control-label">Remarks:</label>
                                <div class="controls">
                                    <textarea name="expense_remarks" class="span11"><?= htmlspecialchars($expense['expense_remarks']) ?></textarea>
                                </div>
                            </div>

                            <div class="form-actions" style="padding-left: 180px;">
                                <button type="submit" name="update_expense" class="btn btn-success">Update Expense</button>
                                <a href="expenses.php" class="btn btn-secondary">Cancel</a>
                            </div>

                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<?php include "footer.php"; ?>

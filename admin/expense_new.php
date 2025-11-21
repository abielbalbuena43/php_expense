<?php
ob_start();
session_start();
include "header.php"; 
include "connection.php";

// Fetch companies, payees, categories, resellers, end users, products
$companies = mysqli_query($conn, "SELECT company_id, company_name, company_tin FROM companies ORDER BY company_name ASC");
$payees = mysqli_query($conn, "SELECT payee_id, payee_name FROM payees ORDER BY payee_name ASC");
$categories = mysqli_query($conn, "SELECT category_id, category_name FROM expense_categories ORDER BY category_name ASC");
$resellers = mysqli_query($conn, "SELECT reseller_id, reseller_name FROM resellers ORDER BY reseller_name ASC");
$end_users = mysqli_query($conn, "SELECT end_user_id, end_user_name FROM expense_end_users ORDER BY end_user_name ASC");
$products = mysqli_query($conn, "SELECT product_id, product_name FROM expense_products ORDER BY product_name ASC");

// Handle form submission
if (isset($_POST['submit_expense'])) {
    $company_id = mysqli_real_escape_string($conn, $_POST['expense_company_id']);
    $payee_id = mysqli_real_escape_string($conn, $_POST['expense_payee_id']);
    $category_id = mysqli_real_escape_string($conn, $_POST['expense_category_id']);
    $reseller_id = !empty($_POST['expense_reseller_id']) ? mysqli_real_escape_string($conn, $_POST['expense_reseller_id']) : NULL;
    $user_id = !empty($_POST['expense_user_id']) ? mysqli_real_escape_string($conn, $_POST['expense_user_id']) : NULL;
    $product_id = !empty($_POST['expense_product_id']) ? mysqli_real_escape_string($conn, $_POST['expense_product_id']) : NULL;
    $or_number = mysqli_real_escape_string($conn, $_POST['expense_or_number']);
    $expense_date = mysqli_real_escape_string($conn, $_POST['expense_date']);
    $remarks = mysqli_real_escape_string($conn, $_POST['expense_remarks']);

    // Inputs
    $gross_taxable = floatval($_POST['expense_gross_taxable']);
    $service_charge = floatval($_POST['expense_service_charge']);
    $services = floatval($_POST['expense_services']);
    $capital_goods = floatval($_POST['expense_capital_goods']);
    $goods_other = floatval($_POST['expense_goods_other_than_capital']);
    $exempt = floatval($_POST['expense_exempt']);
    $zero_rated = floatval($_POST['expense_zero_rated']);
    $vat_rate = floatval($_POST['expense_vat_rate']);

// Fixed Calculations
// Total Purchases = Sum of all taxable purchase amounts (net of VAT)
$total_purchases = $gross_taxable + $services + $capital_goods + $goods_other;
// Total Input Tax = VAT on total purchases
$total_input_tax = round($total_purchases * ($vat_rate / 100), 2);
// Taxable (Net of VAT) = Total purchases minus input tax
$taxable_net_vat = round($total_purchases - $total_input_tax, 2);
// Total Receipt Amount = Net purchases + input tax + other non-taxable amounts
$total_receipt_amount = round($total_purchases + $service_charge + $exempt + $zero_rated + $total_input_tax, 2);

    // Insert into DB
    $query = "
        INSERT INTO expenses (
            expense_company_id,
            expense_payee_id,
            expense_category_id,
            expense_reseller_id,
            expense_user_id,
            expense_product_id,
            expense_or_number,
            expense_date,
            expense_gross_taxable,
            expense_service_charge,
            expense_services,
            expense_capital_goods,
            expense_goods_other_than_capital,
            expense_exempt,
            expense_zero_rated,
            expense_vat_rate,
            expense_total_purchases,
            expense_total_input_tax,
            expense_total_receipt_amount,
            expense_taxable_net_vat,
            expense_remarks,
            expense_created_at
        ) VALUES (
            '$company_id',
            '$payee_id',
            '$category_id',
            " . ($reseller_id ? "'$reseller_id'" : "NULL") . ",
            " . ($user_id ? "'$user_id'" : "NULL") . ",
            " . ($product_id ? "'$product_id'" : "NULL") . ",
            '$or_number',
            '$expense_date',
            '$gross_taxable',
            '$service_charge',
            '$services',
            '$capital_goods',
            '$goods_other',
            '$exempt',
            '$zero_rated',
            '$vat_rate',
            '$total_purchases',
            '$total_input_tax',
            '$total_receipt_amount',
            '$taxable_net_vat',
            '$remarks',
            NOW()
        )
    ";

    if (mysqli_query($conn, $query)) {
        // Get the newly inserted expense ID
        $expense_id = mysqli_insert_id($conn);
        
        // Log the action
        $logQuery = "INSERT INTO logs (log_action, log_user, log_details, log_date) VALUES ('Expense created', '" . mysqli_real_escape_string($conn, $_SESSION['username']) . "', 'Expense ID: $expense_id', NOW())";
        mysqli_query($conn, $logQuery);
        
        $_SESSION['alert'] = "success";
        header("Location: expenses.php");
        exit();
    } else {
        $_SESSION['alert'] = "error";
        echo "Database Error: " . mysqli_error($conn);
    }
}

$alert = $_SESSION['alert'] ?? null;
unset($_SESSION['alert']);
?>

<div id="content">
    <div id="content-header">
        <div id="breadcrumb">
            <a href="expenses.php" class="tip-bottom"><i class="icon-home"></i> Expenses</a>
            <a href="#" class="current">Add New Expense</a>
        </div>
    </div>

    <div class="container-fluid">
        <div class="row-fluid" style="background-color: white; min-height: 600px; padding: 20px;">
            <div class="span12">

                <?php if ($alert == "success") { ?>
                    <div class="alert alert-success">Expense added successfully!</div>
                <?php } elseif ($alert == "error") { ?>
                    <div class="alert alert-danger">Error: Unable to save expense.</div>
                <?php } ?>

                <div class="widget-box" style="max-width: 800px; margin: 0 auto;">
                    <div class="widget-title">
                        <span class="icon"><i class="icon-align-justify"></i></span>
                        <h5>Expense Information</h5>
                    </div>

                    <div class="widget-content" style="padding: 20px;">
                        <form action="" method="post" class="form-horizontal">

                            <!-- Company -->
                            <div class="control-group">
                                <label class="control-label">Company:</label>
                                <div class="controls">
                                    <select name="expense_company_id" id="companySelect" class="span11" required>
                                        <option value="" disabled selected>Select Company</option>
                                        <?php while ($row = mysqli_fetch_assoc($companies)) { ?>
                                            <option value="<?= $row['company_id'] ?>" data-tin="<?= $row['company_tin'] ?>">
                                                <?= $row['company_name'] ?>
                                            </option>
                                        <?php } ?>
                                    </select>
                                </div>
                            </div>

                            <!-- Company TIN (Auto-filled) -->
                            <div class="control-group">
                                <label class="control-label">Company TIN:</label>
                                <div class="controls">
                                    <input type="text" class="span11" id="companyTin" name="company_tin" readonly placeholder="Select a company first">
                                </div>
                            </div>

                            <!-- Payee -->
                            <div class="control-group">
                                <label class="control-label">Payee:</label>
                                <div class="controls">
                                    <select name="expense_payee_id" class="span11" required>
                                        <option value="" disabled selected>Select Payee</option>
                                        <?php while ($row = mysqli_fetch_assoc($payees)) { ?>
                                            <option value="<?= $row['payee_id'] ?>"><?= $row['payee_name'] ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                            </div>

                            <!-- Category -->
                            <div class="control-group">
                                <label class="control-label">Category:</label>
                                <div class="controls">
                                    <select name="expense_category_id" class="span11" required>
                                        <option value="" disabled selected>Select Category</option>
                                        <?php while ($row = mysqli_fetch_assoc($categories)) { ?>
                                            <option value="<?= $row['category_id'] ?>"><?= $row['category_name'] ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                            </div>

                            <!-- Reseller -->
                            <div class="control-group">
                                <label class="control-label">Reseller (Optional):</label>
                                <div class="controls">
                                    <select name="expense_reseller_id" class="span11">
                                        <option value="" selected>None</option>
                                        <?php while ($row = mysqli_fetch_assoc($resellers)) { ?>
                                            <option value="<?= $row['reseller_id'] ?>"><?= $row['reseller_name'] ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                            </div>

                            <!-- End User (Optional) -->
                            <div class="control-group">
                                <label class="control-label">End User (Optional):</label>
                                <div class="controls">
                                    <select name="expense_user_id" class="span11">
                                        <option value="" selected>None</option>
                                        <?php while ($row = mysqli_fetch_assoc($end_users)) { ?>
                                            <option value="<?= $row['end_user_id'] ?>"><?= $row['end_user_name'] ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                            </div>

                            <!-- Product (Optional) -->
                            <div class="control-group">
                                <label class="control-label">Product (Optional):</label>
                                <div class="controls">
                                    <select name="expense_product_id" class="span11">
                                        <option value="" selected>None</option>
                                        <?php while ($row = mysqli_fetch_assoc($products)) { ?>
                                            <option value="<?= $row['product_id'] ?>"><?= $row['product_name'] ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                            </div>

                            <!-- OR Number -->
                            <div class="control-group">
                                <label class="control-label">OR Number:</label>
                                <div class="controls">
                                    <input type="text" class="span11" name="expense_or_number" placeholder="0" required />
                                </div>
                            </div>

                            <!-- Date -->
                            <div class="control-group">
                                <label class="control-label">Date:</label>
                                <div class="controls">
                                    <input type="date" class="span11" name="expense_date" required />
                                </div>
                            </div>

                            <!-- Input fields for calculation -->
                            <div class="control-group">
                                <label class="control-label">Gross Taxable:</label>
                                <div class="controls">
                                    <input type="number" class="span11 calc-field" name="expense_gross_taxable" step="0.01" placeholder="0.00" required />
                                </div>
                            </div>

                            <div class="control-group">
                                <label class="control-label">Service Charge:</label>
                                <div class="controls">
                                    <input type="number" class="span11 calc-field" name="expense_service_charge" step="0.01" placeholder="0.00" />
                                </div>
                            </div>

                            <div class="control-group">
                                <label class="control-label">Services:</label>
                                <div class="controls">
                                    <input type="number" class="span11 calc-field" name="expense_services" step="0.01" placeholder="0.00" />
                                </div>
                            </div>

                            <div class="control-group">
                                <label class="control-label">Capital Goods:</label>
                                <div class="controls">
                                    <input type="number" class="span11 calc-field" name="expense_capital_goods" step="0.01" placeholder="0.00" />
                                </div>
                            </div>

                            <div class="control-group">
                                <label class="control-label">Goods Other than Capital Goods:</label>
                                <div class="controls">
                                    <input type="number" class="span11 calc-field" name="expense_goods_other_than_capital" step="0.01" placeholder="0.00" />
                                </div>
                            </div>

                            <div class="control-group">
                                <label class="control-label">Exempt:</label>
                                <div class="controls">
                                    <input type="number" class="span11 calc-field" name="expense_exempt" step="0.01" placeholder="0.00" />
                                </div>
                            </div>

                            <div class="control-group">
                                <label class="control-label">Zero Rated:</label>
                                <div class="controls">
                                    <input type="number" class="span11 calc-field" name="expense_zero_rated" step="0.01" placeholder="0.00" />
                                </div>
                            </div>

                            <div class="control-group">
                                <label class="control-label">VAT Rate (%):</label>
                                <div class="controls">
                                    <input type="number" class="span11 calc-field" 
                                        name="expense_vat_rate" 
                                        step="0.01" 
                                        readonly 
                                        value="12.00" />
                                </div>
                            </div>

                            <!-- Calculated fields -->
                            <div class="control-group">
                                <label class="control-label">Total Purchases:</label>
                                <div class="controls">
                                    <input type="number" class="span11" name="expense_total_purchases" readonly value="0.00" id="totalPurchases" />
                                </div>
                            </div>

                            <div class="control-group">
                                <label class="control-label">Total Input Tax:</label>
                                <div class="controls">
                                    <input type="number" class="span11" name="expense_total_input_tax" readonly value="0.00" id="totalInputTax" />
                                </div>
                            </div>

                            <div class="control-group">
                                <label class="control-label">Total Receipt Amount:</label>
                                <div class="controls">
                                    <input type="number" class="span11" name="expense_total_receipt_amount" readonly value="0.00" id="totalReceiptAmount" />
                                </div>
                            </div>

                            <div class="control-group">
                                <label class="control-label">Taxable (Net of VAT):</label>
                                <div class="controls">
                                    <input type="number" class="span11" name="expense_taxable_net_vat" readonly value="0.00" id="taxableNetVat" />
                                </div>
                            </div>

                            <!-- Remarks -->
                            <div class="control-group">
                                <label class="control-label">Remarks:</label>
                                <div class="controls">
                                    <textarea name="expense_remarks" class="span11" placeholder="Enter remarks (optional)"></textarea>
                                </div>
                            </div>

                            <div class="form-actions" style="padding-left: 180px;">
                                <button type="submit" name="submit_expense" class="btn btn-success">Save Expense</button>
                                <a href="expenses.php" class="btn btn-secondary">Cancel</a>
                            </div>

                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<script>
const calcFields = document.querySelectorAll('.calc-field');
const totalPurchasesInput = document.getElementById('totalPurchases');
const totalInputTaxInput = document.getElementById('totalInputTax');
const totalReceiptInput = document.getElementById('totalReceiptAmount');
const taxableNetVatInput = document.getElementById('taxableNetVat');

function recalc() {
    // Parse values safely (default to 0 if empty)
    const gross_taxable = parseFloat(document.querySelector('[name="expense_gross_taxable"]').value) || 0;
    const service_charge = parseFloat(document.querySelector('[name="expense_service_charge"]').value) || 0;
    const services = parseFloat(document.querySelector('[name="expense_services"]').value) || 0;
    const capital_goods = parseFloat(document.querySelector('[name="expense_capital_goods"]').value) || 0;
    const goods_other = parseFloat(document.querySelector('[name="expense_goods_other_than_capital"]').value) || 0;
    const exempt = parseFloat(document.querySelector('[name="expense_exempt"]').value) || 0;
    const zero_rated = parseFloat(document.querySelector('[name="expense_zero_rated"]').value) || 0;
    const vat_rate = parseFloat(document.querySelector('[name="expense_vat_rate"]').value) || 0;

    // 1. Total Purchases = Gross Taxable + Services + Capital Goods + Goods Other Than Capital Goods
    const total_purchases = gross_taxable + services + capital_goods + goods_other;
    totalPurchasesInput.value = total_purchases.toFixed(2);

    // 2. Total Input Tax = Total Purchases * (VAT Rate / 100)
    const total_input_tax = total_purchases * (vat_rate / 100);
    totalInputTaxInput.value = total_input_tax.toFixed(2);

    // 3. Taxable (Net of VAT) = Total Purchases - Total Input Tax
    const taxable_net_vat = total_purchases - total_input_tax;
    taxableNetVatInput.value = taxable_net_vat.toFixed(2);

    // 4. Total Receipt Amount = Total Purchases + Service Charge + Exempt + Zero Rated + Total Input Tax
    const total_receipt = total_purchases + service_charge + exempt + zero_rated + total_input_tax;
    totalReceiptInput.value = total_receipt.toFixed(2);
}

// Run calculations when any calc field changes
calcFields.forEach(input => {
    input.addEventListener('input', recalc);
});

// Initialize calculations on page load
recalc();

// Auto-display company TIN
document.getElementById('companySelect').addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    const tin = selectedOption.getAttribute('data-tin') || '';
    document.getElementById('companyTin').value = tin;
});
</script>

<?php include "footer.php"; ?>
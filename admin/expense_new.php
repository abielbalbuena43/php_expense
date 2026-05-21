<?php
ob_start();
session_start();
include "header.php"; 
include "connection.php";

// Fetch companies, payees, categories, resellers, end users, products
$companies = mysqli_query($conn, "SELECT company_id, company_name, company_tin FROM companies ORDER BY company_name ASC");
$payees = mysqli_query($conn, "
    SELECT 
        payee_id, 
        payee_name, 
        payee_tin,
        payee_address1,
        payee_address2,
        payee_category
    FROM payees 
    ORDER BY payee_name ASC
");
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
    $zero_rated = 0; // merged into exempt
    $vat_rate = floatval($_POST['expense_vat_rate']);

    // ============================
    // FIXED CALCULATIONS (CORRECT)
    // ============================

    // Total Purchases (match old system priority)
    if ($services > 0) {
        $total_purchases = $services;
    } elseif ($capital_goods > 0) {
        $total_purchases = $capital_goods;
    } elseif ($goods_other > 0) {
        $total_purchases = $goods_other;
    } else {
        $total_purchases = $gross_taxable;
    }

    // Taxable Net (allow manual override when VAT = 0)
        if ($vat_rate == 0) {
            $taxable_net_vat = floatval($_POST['expense_taxable_net_vat']);
        } else {
            $taxable_net_vat = $total_purchases - $exempt;
            if ($taxable_net_vat < 0) $taxable_net_vat = 0;
        }

        // VAT
        $total_input_tax = round($taxable_net_vat * ($vat_rate / 100), 2);

        // Final Receipt Amount
        $total_receipt_amount = round(
            $taxable_net_vat + $total_input_tax + $service_charge + $exempt,
            2
        );

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
        $expense_id = mysqli_insert_id($conn);
        
        $logQuery = "INSERT INTO logs (log_action, log_user, log_details, log_date) VALUES ('Expense created', '" . mysqli_real_escape_string($conn, $_SESSION['username']) . "', 'Expense ID: $expense_id', NOW())";
        mysqli_query($conn, $logQuery);
        
        $_SESSION['alert'] = "Expense added successfully!";
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

<link rel="stylesheet" href="css/layout.css">

<div id="content">
    <div class="container-fluid">
        <div class="row-fluid" style="background-color: white; min-height: 600px; padding: 20px;">
            <div class="span12">

                <?php if ($alert == "Expense added successfully!") { ?>
                    <div class="alert alert-success">Expense added successfully!</div>
                <?php } elseif ($alert == "error") { ?>
                    <div class="alert alert-danger">Error: Unable to save expense.</div>
                <?php } ?>

                <div class="widget-box" style="max-width: 800px; margin: 0 auto;">
                    <div class="widget-title">
                        <h5>Expense Information</h5>
                    </div>

                    <div class="widget-content" style="padding: 20px;">
                        <form action="" method="post" class="form-horizontal">

                            <!-- Company -->
                            <div class="control-group">
                                <label class="control-label">Company:</label>
                                <div class="controls" style="position:relative;">
                                    <select name="expense_company_id" id="companySelect" class="span11" required>
                                        <option value="" disabled selected>Select Company</option>
                                        <?php while ($row = mysqli_fetch_assoc($companies)) { ?>
                                            <option 
                                                value="<?= $row['company_id'] ?>" 
                                                data-tin="<?= htmlspecialchars($row['company_tin']) ?>"
                                            >
                                                <?= htmlspecialchars($row['company_name']) ?>
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

                            <!-- Payee (Searchable Dropdown like legacy) -->
                            <div class="control-group">
                                <label class="control-label">Payee:</label>
                                <div class="controls">

                                    <!-- Hidden actual value -->
                                    <input type="hidden" name="expense_payee_id" id="payeeHidden" required>

                                    <!-- Search input -->
                                    <input type="text" id="payeeSearch" class="span11" placeholder="Search Payee..." autocomplete="off">

                                    <!-- Dropdown results -->
                                    <div id="payeeDropdown" style="
                                        border:1px solid #ccc;
                                        max-height:250px;
                                        overflow-y:auto;
                                        display:none;
                                        background:#fff;
                                        position:absolute;
                                        width:90%;
                                        z-index:999;
                                    ">
                                        
                                        <?php mysqli_data_seek($payees, 0); while ($row = mysqli_fetch_assoc($payees)) { ?>
                                            <div class="payee-option"
                                                data-id="<?= $row['payee_id'] ?>"
                                                data-name="<?= htmlspecialchars($row['payee_name']) ?>"
                                                data-tin="<?= htmlspecialchars($row['payee_tin']) ?>"
                                                data-address1="<?= htmlspecialchars($row['payee_address1']) ?>"
                                                data-address2="<?= htmlspecialchars($row['payee_address2']) ?>"
                                                data-category="<?= htmlspecialchars($row['payee_category']) ?>"
                                                style="padding:10px; border-bottom:1px solid #eee; cursor:pointer;"
                                            >
                                                <strong><?= htmlspecialchars($row['payee_name']) ?></strong><br>
                                                <small><?= htmlspecialchars($row['payee_tin']) ?></small><br>
                                                <small><?= htmlspecialchars($row['payee_address1'] . ' ' . $row['payee_address2']) ?></small>
                                            </div>
                                        <?php } ?>

                                    </div>

                                </div>
                            </div>

                            <!-- Payee TIN (Auto-filled) -->
                            <div class="control-group">
                                <label class="control-label">Payee TIN:</label>
                                <div class="controls">
                                    <input type="text" class="span11" id="payeeTin" readonly placeholder="Select a payee">
                                </div>
                            </div>

                            <div class="control-group">
                                <label class="control-label">Payee Address:</label>
                                <div class="controls">
                                    <input type="text" class="span11" id="payeeAddress" readonly placeholder="Select a payee">
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

                            <!-- Exempt merged with Zero Rated -->
                            <div class="control-group">
                                <label class="control-label">Exempt / Zero Rated:</label>
                                <div class="controls">
                                    <input type="number" class="span11 calc-field" name="expense_exempt" step="0.01" placeholder="0.00" />
                                </div>
                            </div>

                            <div class="control-group">
                                <label class="control-label">VAT:</label>
                                <div class="controls">

                                    <label style="display:block; margin-bottom:5px;">
                                        <input type="checkbox" id="vatToggle" checked>
                                        VAT Applicable (12%)
                                    </label>

                                    <input type="number" class="span11 calc-field" 
                                        name="expense_vat_rate" 
                                        step="0.01" 
                                        readonly 
                                        value="12.00" id="vatRateInput" />

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
                                <label class="control-label">Total VAT Tax:</label>
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
                                    <input type="number" class="span11" name="expense_taxable_net_vat" value="0.00" id="taxableNetVat" />
                                </div>
                            </div>

                            <!-- Remarks -->
                            <div class="control-group">
                                <label class="control-label">Remarks:</label>
                                <div class="controls">
                                    <textarea name="expense_remarks" class="span11" placeholder="Enter remarks (optional)"></textarea>
                                </div>
                            </div>

                            <div class="form-actions action-buttons">
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
document.addEventListener('DOMContentLoaded', function () {

    const calcFields = document.querySelectorAll('.calc-field');
    const totalPurchasesInput = document.getElementById('totalPurchases');
    const totalInputTaxInput = document.getElementById('totalInputTax');
    const totalReceiptInput = document.getElementById('totalReceiptAmount');
    const taxableNetVatInput = document.getElementById('taxableNetVat');
    const categorySelect = document.querySelector('[name="expense_category_id"]');

        function recalc() {
    const gross_taxable = parseFloat(document.querySelector('[name="expense_gross_taxable"]').value) || 0;
    const service_charge = parseFloat(document.querySelector('[name="expense_service_charge"]').value) || 0;
    const services = parseFloat(document.querySelector('[name="expense_services"]').value) || 0;
    const capital_goods = parseFloat(document.querySelector('[name="expense_capital_goods"]').value) || 0;
    const goods_other = parseFloat(document.querySelector('[name="expense_goods_other_than_capital"]').value) || 0;
    let exempt = parseFloat(document.querySelector('[name="expense_exempt"]').value) || 0;
    const zero_rated = 0;

    const vatCheckbox = document.getElementById('vatToggle');
    let vat_rate = vatCheckbox.checked ? 12 : 0;

    // ✅ PRIORITY LOGIC (matches old system)
    let total_purchases = 0;
    if (services > 0) {
        total_purchases = services;
    } else if (capital_goods > 0) {
        total_purchases = capital_goods;
    } else if (goods_other > 0) {
        total_purchases = goods_other;
    } else {
        total_purchases = gross_taxable;
    }

    totalPurchasesInput.value = total_purchases.toFixed(2);

    // If unchecked = fully exempt mode
    if (!vatCheckbox.checked) {
        exempt = total_purchases;
        document.querySelector('[name="expense_exempt"]').value = total_purchases.toFixed(2);
    }

    // ✅ TAXABLE NET
    let taxable_net_vat = total_purchases - exempt;
    if (taxable_net_vat < 0) taxable_net_vat = 0;

    if (!vatCheckbox.checked) {
        taxable_net_vat = 0;
        taxableNetVatInput.value = "0.00";
    }

    // ✅ VAT
    const total_input_tax = taxable_net_vat * (vat_rate / 100);
    totalInputTaxInput.value = total_input_tax.toFixed(2);

    // ✅ FINAL RECEIPT
    const total_receipt = taxable_net_vat + total_input_tax + service_charge + exempt;
    totalReceiptInput.value = total_receipt.toFixed(2);
}

    calcFields.forEach(input => {
        input.addEventListener('input', recalc);
    });

    const taxableInput = document.getElementById('taxableNetVat');

    taxableInput.readOnly = document.getElementById('vatToggle').checked;

    document.getElementById('vatToggle').addEventListener('change', function() {
        const isChecked = this.checked;

        document.getElementById('vatRateInput').value = isChecked ? 12.00 : 0.00;
        taxableInput.readOnly = isChecked;

        recalc();
    });

    recalc();


        // ============================
        // PAYEE SEARCH (PUT HERE)
        // ============================
        const payeeSearch = document.getElementById('payeeSearch');
        const payeeDropdown = document.getElementById('payeeDropdown');
        const payeeHidden = document.getElementById('payeeHidden');

        // show dropdown
        payeeSearch.addEventListener('focus', () => {
            payeeDropdown.style.display = 'block';
        });

        // filter + highlight
        payeeSearch.addEventListener('input', function () {
            const filter = this.value.toLowerCase();
            const options = document.querySelectorAll('.payee-option');

            options.forEach(opt => {

        if (!opt.dataset.original) {
            opt.dataset.original = opt.innerHTML;
        }

        const name = opt.dataset.name;
        const tin = opt.dataset.tin;
        const address = opt.dataset.address1 + ' ' + opt.dataset.address2;

        const combined = (name + tin + address).toLowerCase();

        if (combined.includes(filter)) {

            opt.style.display = 'block';

            // ✅ reset first
            opt.innerHTML = opt.dataset.original;

            let displayName = name;

            if (filter !== '') {
                displayName = name.replace(
                    new RegExp(filter, 'gi'),
                    match => `<span style="background:yellow;">${match}</span>`
                );
            }

            opt.innerHTML = `
                <strong>${displayName}</strong><br>
                <small>${tin}</small><br>
                <small>${address}</small>
            `;

        } else {
            opt.style.display = 'none';
        }
    });
    });

    // select payee
    document.querySelectorAll('.payee-option').forEach(option => {
        option.addEventListener('click', function () {

            const name = this.dataset.name;
            const tin = this.dataset.tin;
            const address = (this.dataset.address1 + ' ' + this.dataset.address2).trim();
            const category = this.dataset.category;

            payeeSearch.value = name;
            payeeHidden.value = this.dataset.id;

            document.getElementById('payeeTin').value = tin;
            document.getElementById('payeeAddress').value = address;

            if (category) {
                const exists = [...categorySelect.options].some(opt => opt.value === category);
                if (exists) {
                    categorySelect.value = category;
                    categorySelect.setAttribute('disabled', true);
                }
            }

            payeeDropdown.style.display = 'none';
        });
    });

    // close dropdown
    document.addEventListener('click', function (e) {
        if (!e.target.closest('#payeeSearch') && !e.target.closest('#payeeDropdown')) {
            payeeDropdown.style.display = 'none';
        }
    });

    // Allow manual category by default
    categorySelect.removeAttribute('disabled');

    // Auto-display company TIN
    document.getElementById('companySelect').addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const tin = selectedOption.getAttribute('data-tin') || '';
        document.getElementById('companyTin').value = tin;
    });


    // Ensure category is submitted
    document.querySelector('form').addEventListener('submit', function() {
        categorySelect.removeAttribute('disabled');
    });

});
</script>

<?php include "footer.php"; ?>
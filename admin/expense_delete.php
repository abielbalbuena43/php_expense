<?php
ob_start();
session_start();
include "header.php";
include "connection.php";

// Check if an expense ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['alert'] = "invalid";
    header("Location: expenses.php");
    exit();
}

$expense_id = intval($_GET['id']);

// Fetch the expense details for confirmation
$query = "
    SELECT 
        e.expense_id,
        e.expense_or_number,
        e.expense_date,
        c.company_name,
        p.payee_name,
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
        e.expense_total_receipt_amount
    FROM expenses e
    INNER JOIN companies c ON e.expense_company_id = c.company_id
    INNER JOIN payees p ON e.expense_payee_id = p.payee_id
    WHERE e.expense_id = $expense_id
";
$result = mysqli_query($conn, $query);

// If no expense found, redirect
if (!$result || mysqli_num_rows($result) === 0) {
    $_SESSION['alert'] = "not_found";
    header("Location: expenses.php");
    exit();
}

$expense = mysqli_fetch_assoc($result);

// Handle delete confirmation
if (isset($_POST['confirm_delete'])) {
    $delete_query = "DELETE FROM expenses WHERE expense_id = $expense_id";

    if (mysqli_query($conn, $delete_query)) {
        $_SESSION['alert'] = "deleted";
        header("Location: expenses.php");
        exit();
    } else {
        $_SESSION['alert'] = "error";
    }
}

// Alert messages
if (isset($_SESSION['alert'])) {
    $alert = $_SESSION['alert'];
    unset($_SESSION['alert']);
} else {
    $alert = null;
}
?>

<div id="content">
    <div id="content-header">
        <div id="breadcrumb">
            <a href="expenses.php" class="tip-bottom"><i class="icon-home"></i> Expenses</a>
            <a href="#" class="current">Delete Expense</a>
        </div>
    </div>

    <div class="container-fluid">
        <div class="row-fluid" style="background-color: white; min-height: 400px; padding: 20px;">
            <div class="span12">

                <!-- Alert Messages -->
                <?php if ($alert == "error") { ?>
                    <div class="alert alert-danger">Error: Unable to delete expense.</div>
                <?php } elseif ($alert == "invalid") { ?>
                    <div class="alert alert-warning">Invalid expense ID.</div>
                <?php } elseif ($alert == "not_found") { ?>
                    <div class="alert alert-warning">Expense not found.</div>
                <?php } ?>

                <!-- Delete Confirmation -->
                <div class="widget-box" style="max-width: 800px; margin: 0 auto;">
                    <div class="widget-title">
                        <span class="icon"><i class="icon-trash"></i></span>
                        <h5>Delete Expense Confirmation</h5>
                    </div>

                    <div class="widget-content" style="padding: 20px;">
                        <p>Are you sure you want to delete the following expense?</p>

                        <table class="table table-bordered table-striped">
                            <tr>
                                <th>ID</th>
                                <td><?= htmlspecialchars($expense['expense_id']) ?></td>
                            </tr>
                            <tr>
                                <th>OR Number</th>
                                <td><?= htmlspecialchars($expense['expense_or_number']) ?></td>
                            </tr>
                            <tr>
                                <th>Date</th>
                                <td><?= date('M d, Y', strtotime($expense['expense_date'])) ?></td>
                            </tr>
                            <tr>
                                <th>Company</th>
                                <td><?= htmlspecialchars($expense['company_name']) ?></td>
                            </tr>
                            <tr>
                                <th>Payee</th>
                                <td><?= htmlspecialchars($expense['payee_name']) ?></td>
                            </tr>
                            <tr>
                                <th>Gross Taxable</th>
                                <td><?= number_format($expense['expense_gross_taxable'], 2) ?></td>
                            </tr>
                            <tr>
                                <th>Service Charge</th>
                                <td><?= number_format($expense['expense_service_charge'], 2) ?></td>
                            </tr>
                            <tr>
                                <th>Services</th>
                                <td><?= number_format($expense['expense_services'], 2) ?></td>
                            </tr>
                            <tr>
                                <th>Capital Goods</th>
                                <td><?= number_format($expense['expense_capital_goods'], 2) ?></td>
                            </tr>
                            <tr>
                                <th>Goods Other Than Capital</th>
                                <td><?= number_format($expense['expense_goods_other_than_capital'], 2) ?></td>
                            </tr>
                            <tr>
                                <th>Exempt</th>
                                <td><?= number_format($expense['expense_exempt'], 2) ?></td>
                            </tr>
                            <tr>
                                <th>Zero Rated</th>
                                <td><?= number_format($expense['expense_zero_rated'], 2) ?></td>
                            </tr>
                            <tr>
                                <th>VAT Rate (%)</th>
                                <td><?= htmlspecialchars($expense['expense_vat_rate']) ?>%</td>
                            </tr>
                            <tr>
                                <th>Total Input Tax</th>
                                <td><?= number_format($expense['expense_total_input_tax'], 2) ?></td>
                            </tr>
                            <tr>
                                <th>Total Purchases</th>
                                <td><?= number_format($expense['expense_total_purchases'], 2) ?></td>
                            </tr>
                            <tr>
                                <th>Total Receipt Amount</th>
                                <td><?= number_format($expense['expense_total_receipt_amount'], 2) ?></td>
                            </tr>
                        </table>

                        <form action="" method="post" style="margin-top: 20px;">
                            <button type="submit" name="confirm_delete" class="btn btn-danger">
                                <i class="icon-trash"></i> Confirm Delete
                            </button>
                            <a href="expenses.php" class="btn btn-secondary">Cancel</a>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<?php include "footer.php"; ?>

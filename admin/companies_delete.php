<?php
session_start();
include "header.php";
include "connection.php";

// Check if a company ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['alert'] = "invalid";
    header("Location: companies.php");
    exit();
}

$company_id = intval($_GET['id']);

// Fetch the company details for confirmation
$query = "
    SELECT *
    FROM companies
    WHERE company_id = $company_id
";
$result = mysqli_query($conn, $query);

// If no company found, redirect
if (!$result || mysqli_num_rows($result) === 0) {
    $_SESSION['alert'] = "not_found";
    header("Location: companies.php");
    exit();
}

$company = mysqli_fetch_assoc($result);

// Handle delete confirmation
if (isset($_POST['confirm_delete'])) {
    $delete_query = "DELETE FROM companies WHERE company_id = $company_id";

    if (mysqli_query($conn, $delete_query)) {
        $_SESSION['alert'] = "deleted";
        header("Location: companies.php");
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
            <a href="companies.php" class="tip-bottom"><i class="icon-home"></i> Companies</a>
            <a href="#" class="current">Delete Company</a>
        </div>
    </div>

    <div class="container-fluid">
        <div class="row-fluid" style="background-color: white; min-height: 400px; padding: 20px;">
            <div class="span12">

                <!-- Alert Messages -->
                <?php if ($alert == "error") { ?>
                    <div class="alert alert-danger">Error: Unable to delete company.</div>
                <?php } elseif ($alert == "invalid") { ?>
                    <div class="alert alert-warning">Invalid company ID.</div>
                <?php } elseif ($alert == "not_found") { ?>
                    <div class="alert alert-warning">Company not found.</div>
                <?php } ?>

                <!-- Delete Confirmation -->
                <div class="widget-box" style="max-width: 800px; margin: 0 auto;">
                    <div class="widget-title">
                        <span class="icon"><i class="icon-trash"></i></span>
                        <h5>Delete Company Confirmation</h5>
                    </div>

                    <div class="widget-content" style="padding: 20px;">
                        <p>Are you sure you want to delete the following company?</p>

                        <table class="table table-bordered table-striped">
                            <tr>
                                <th>ID</th>
                                <td><?= htmlspecialchars($company['company_id']) ?></td>
                            </tr>
                            <tr>
                                <th>Company Name</th>
                                <td><?= htmlspecialchars($company['company_name']) ?></td>
                            </tr>
                            <tr>
                                <th>Trade Name</th>
                                <td><?= htmlspecialchars($company['trade_name']) ?></td>
                            </tr>
                            <tr>
                                <th>TIN</th>
                                <td><?= htmlspecialchars($company['tin']) ?></td>
                            </tr>
                            <tr>
                                <th>RDO Code</th>
                                <td><?= htmlspecialchars($company['rdo_code']) ?></td>
                            </tr>
                            <tr>
                                <th>Branch Code</th>
                                <td><?= htmlspecialchars($company['branch_code']) ?></td>
                            </tr>
                            <tr>
                                <th>Address</th>
                                <td>
                                    <?= htmlspecialchars($company['substreet']) ?>,
                                    <?= htmlspecialchars($company['street']) ?>,
                                    <?= htmlspecialchars($company['barangay']) ?>,
                                    <?= htmlspecialchars($company['city']) ?>,
                                    <?= htmlspecialchars($company['province']) ?>,
                                    <?= htmlspecialchars($company['zip_code']) ?>
                                </td>
                            </tr>
                            <tr>
                                <th>Special Fields</th>
                                <td><?= htmlspecialchars($company['special_fields']) ?></td>
                            </tr>
                        </table>

                        <form action="" method="post" style="margin-top: 20px;">
                            <button type="submit" name="confirm_delete" class="btn btn-danger">
                                <i class="icon-trash"></i> Confirm Delete
                            </button>
                            <a href="companies.php" class="btn btn-secondary">Cancel</a>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<?php include "footer.php"; ?>

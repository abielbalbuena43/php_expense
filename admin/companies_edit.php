<?php
ob_start();
session_start();
include "header.php";
include "connection.php";

// Validate company ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "<div class='alert alert-danger'>Invalid Company ID.</div>";
    exit();
}

$company_id = intval($_GET['id']);

// Fetch company details
$query = "SELECT * FROM companies WHERE company_id = '$company_id' LIMIT 1";
$result = mysqli_query($conn, $query);

if (!$result || mysqli_num_rows($result) == 0) {
    echo "<div class='alert alert-danger'>Company record not found.</div>";
    exit();
}

$company = mysqli_fetch_assoc($result);

// Handle form submission
if (isset($_POST['submit_company'])) {
    $company_name = mysqli_real_escape_string($conn, $_POST['company_name']);
    $tin = mysqli_real_escape_string($conn, $_POST['tin']);
    $rdo_code = mysqli_real_escape_string($conn, $_POST['rdo_code']);
    $branch_code = mysqli_real_escape_string($conn, $_POST['branch_code']);
    $trade_name = mysqli_real_escape_string($conn, $_POST['trade_name']);
    $substreet = mysqli_real_escape_string($conn, $_POST['substreet']);
    $street = mysqli_real_escape_string($conn, $_POST['street']);
    $barangay = mysqli_real_escape_string($conn, $_POST['barangay']);
    $city = mysqli_real_escape_string($conn, $_POST['city']);
    $province = mysqli_real_escape_string($conn, $_POST['province']);
    $zip_code = mysqli_real_escape_string($conn, $_POST['zip_code']);
    $special_fields = mysqli_real_escape_string($conn, $_POST['special_fields']);

    $updateQuery = "
        UPDATE companies SET
            company_name = '$company_name',
            tin = '$tin',
            rdo_code = '$rdo_code',
            branch_code = '$branch_code',
            trade_name = '$trade_name',
            substreet = '$substreet',
            street = '$street',
            barangay = '$barangay',
            city = '$city',
            province = '$province',
            zip_code = '$zip_code',
            special_fields = '$special_fields'
        WHERE company_id = '$company_id'
    ";

    if (mysqli_query($conn, $updateQuery)) {
        $_SESSION['alert'] = "success";
        header("Location: companies.php");
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
            <a href="companies.php" class="tip-bottom"><i class="icon-home"></i> Companies</a>
            <a href="#" class="current">Edit Company</a>
        </div>
    </div>

    <div class="container-fluid">
        <div class="row-fluid" style="background-color: white; min-height: 600px; padding: 20px;">
            <div class="span12">

                <?php if ($alert == "success") { ?>
                    <div class="alert alert-success">Company updated successfully!</div>
                <?php } elseif ($alert == "error") { ?>
                    <div class="alert alert-danger">Error: Unable to update company.</div>
                <?php } ?>

                <div class="widget-box" style="max-width: 800px; margin: 0 auto;">
                    <div class="widget-title">
                        <span class="icon"><i class="icon-align-justify"></i></span>
                        <h5>Edit Company</h5>
                    </div>

                    <div class="widget-content" style="padding: 20px;">
                        <form action="" method="post" class="form-horizontal">

                            <div class="control-group">
                                <label class="control-label">Company Name:</label>
                                <div class="controls">
                                    <input type="text" class="span11" name="company_name" value="<?= htmlspecialchars($company['company_name']) ?>" required>
                                </div>
                            </div>

                            <div class="control-group">
                                <label class="control-label">TIN:</label>
                                <div class="controls">
                                    <input type="text" class="span11" name="tin" value="<?= htmlspecialchars($company['tin']) ?>" required>
                                </div>
                            </div>

                            <div class="control-group">
                                <label class="control-label">RDO Code:</label>
                                <div class="controls">
                                    <input type="text" class="span11" name="rdo_code" value="<?= htmlspecialchars($company['rdo_code']) ?>" required>
                                </div>
                            </div>

                            <div class="control-group">
                                <label class="control-label">Branch Code:</label>
                                <div class="controls">
                                    <input type="text" class="span11" name="branch_code" value="<?= htmlspecialchars($company['branch_code']) ?>" required>
                                </div>
                            </div>

                            <div class="control-group">
                                <label class="control-label">Trade Name:</label>
                                <div class="controls">
                                    <input type="text" class="span11" name="trade_name" value="<?= htmlspecialchars($company['trade_name']) ?>" required>
                                </div>
                            </div>

                            <div class="control-group">
                                <label class="control-label">Substreet:</label>
                                <div class="controls">
                                    <input type="text" class="span11" name="substreet" value="<?= htmlspecialchars($company['substreet']) ?>">
                                </div>
                            </div>

                            <div class="control-group">
                                <label class="control-label">Street:</label>
                                <div class="controls">
                                    <input type="text" class="span11" name="street" value="<?= htmlspecialchars($company['street']) ?>">
                                </div>
                            </div>

                            <div class="control-group">
                                <label class="control-label">Barangay:</label>
                                <div class="controls">
                                    <input type="text" class="span11" name="barangay" value="<?= htmlspecialchars($company['barangay']) ?>">
                                </div>
                            </div>

                            <div class="control-group">
                                <label class="control-label">City:</label>
                                <div class="controls">
                                    <input type="text" class="span11" name="city" value="<?= htmlspecialchars($company['city']) ?>">
                                </div>
                            </div>

                            <div class="control-group">
                                <label class="control-label">Province:</label>
                                <div class="controls">
                                    <input type="text" class="span11" name="province" value="<?= htmlspecialchars($company['province']) ?>">
                                </div>
                            </div>

                            <div class="control-group">
                                <label class="control-label">Zip Code:</label>
                                <div class="controls">
                                    <input type="text" class="span11" name="zip_code" value="<?= htmlspecialchars($company['zip_code']) ?>">
                                </div>
                            </div>

                            <div class="control-group">
                                <label class="control-label">Special Fields:</label>
                                <div class="controls">
                                    <textarea class="span11" name="special_fields"><?= htmlspecialchars($company['special_fields']) ?></textarea>
                                </div>
                            </div>

                            <div class="form-actions" style="padding-left: 180px;">
                                <button type="submit" name="submit_company" class="btn btn-success">Save Changes</button>
                                <a href="companies.php" class="btn btn-secondary">Cancel</a>
                            </div>

                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<?php include "footer.php"; ?>

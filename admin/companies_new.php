<?php
ob_start();
session_start();
include "header.php";
include "connection.php";

// Handle form submission
if (isset($_POST['submit_company'])) {
    $company_name = mysqli_real_escape_string($conn, $_POST['company_name']);
    $trade_name = mysqli_real_escape_string($conn, $_POST['trade_name']);
    $tin = mysqli_real_escape_string($conn, $_POST['tin']);
    $rdo_code = mysqli_real_escape_string($conn, $_POST['rdo_code']);
    $branch_code = mysqli_real_escape_string($conn, $_POST['branch_code']);
    $substreet = mysqli_real_escape_string($conn, $_POST['substreet']);
    $street = mysqli_real_escape_string($conn, $_POST['street']);
    $barangay = mysqli_real_escape_string($conn, $_POST['barangay']);
    $city = mysqli_real_escape_string($conn, $_POST['city']);
    $province = mysqli_real_escape_string($conn, $_POST['province']);
    $zip_code = mysqli_real_escape_string($conn, $_POST['zip_code']);
    $special_fields = mysqli_real_escape_string($conn, $_POST['special_fields']);

    $query = "
        INSERT INTO companies (
            company_name,
            trade_name,
            tin,
            rdo_code,
            branch_code,
            substreet,
            street,
            barangay,
            city,
            province,
            zip_code,
            special_fields,
            company_created_at
        ) VALUES (
            '$company_name',
            '$trade_name',
            '$tin',
            '$rdo_code',
            '$branch_code',
            '$substreet',
            '$street',
            '$barangay',
            '$city',
            '$province',
            '$zip_code',
            '$special_fields',
            NOW()
        )
    ";

    if (mysqli_query($conn, $query)) {
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
            <a href="#" class="current">Add New Company</a>
        </div>
    </div>

    <div class="container-fluid">
        <div class="row-fluid" style="background-color: white; min-height: 600px; padding: 20px;">
            <div class="span12">

                <?php if ($alert == "success") { ?>
                    <div class="alert alert-success">Company added successfully!</div>
                <?php } elseif ($alert == "error") { ?>
                    <div class="alert alert-danger">Error: Unable to save company.</div>
                <?php } ?>

                <div class="widget-box" style="max-width: 800px; margin: 0 auto;">
                    <div class="widget-title">
                        <span class="icon"><i class="icon-align-justify"></i></span>
                        <h5>Company Information</h5>
                    </div>

                    <div class="widget-content" style="padding: 20px;">
                        <form action="" method="post" class="form-horizontal">

                            <div class="control-group">
                                <label class="control-label">Company Name:</label>
                                <div class="controls">
                                    <input type="text" name="company_name" class="span11" required />
                                </div>
                            </div>

                            <div class="control-group">
                                <label class="control-label">Trade Name:</label>
                                <div class="controls">
                                    <input type="text" name="trade_name" class="span11" />
                                </div>
                            </div>

                            <div class="control-group">
                                <label class="control-label">TIN:</label>
                                <div class="controls">
                                    <input type="text" name="tin" class="span11" />
                                </div>
                            </div>

                            <div class="control-group">
                                <label class="control-label">RDO Code:</label>
                                <div class="controls">
                                    <input type="text" name="rdo_code" class="span11" />
                                </div>
                            </div>

                            <div class="control-group">
                                <label class="control-label">Branch Code:</label>
                                <div class="controls">
                                    <input type="text" name="branch_code" class="span11" />
                                </div>
                            </div>

                            <div class="control-group">
                                <label class="control-label">Substreet:</label>
                                <div class="controls">
                                    <input type="text" name="substreet" class="span11" />
                                </div>
                            </div>

                            <div class="control-group">
                                <label class="control-label">Street:</label>
                                <div class="controls">
                                    <input type="text" name="street" class="span11" />
                                </div>
                            </div>

                            <div class="control-group">
                                <label class="control-label">Barangay:</label>
                                <div class="controls">
                                    <input type="text" name="barangay" class="span11" />
                                </div>
                            </div>

                            <div class="control-group">
                                <label class="control-label">City:</label>
                                <div class="controls">
                                    <input type="text" name="city" class="span11" />
                                </div>
                            </div>

                            <div class="control-group">
                                <label class="control-label">Province:</label>
                                <div class="controls">
                                    <input type="text" name="province" class="span11" />
                                </div>
                            </div>

                            <div class="control-group">
                                <label class="control-label">Zip Code:</label>
                                <div class="controls">
                                    <input type="text" name="zip_code" class="span11" />
                                </div>
                            </div>

                            <div class="control-group">
                                <label class="control-label">Special Fields:</label>
                                <div class="controls">
                                    <textarea name="special_fields" class="span11"></textarea>
                                </div>
                            </div>

                            <div class="form-actions" style="padding-left: 180px;">
                                <button type="submit" name="submit_company" class="btn btn-success">Save Company</button>
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

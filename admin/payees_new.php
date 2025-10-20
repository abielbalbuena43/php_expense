<?php
ob_start();
session_start();
include "header.php"; 
include "connection.php";

// Handle form submission
if (isset($_POST['submit_payee'])) {
    $name = mysqli_real_escape_string($conn, $_POST['payee_name']);
    $type = mysqli_real_escape_string($conn, $_POST['payee_type']);
    $tin = !empty($_POST['payee_tin']) ? mysqli_real_escape_string($conn, $_POST['payee_tin']) : NULL;
    $category = !empty($_POST['payee_category']) ? mysqli_real_escape_string($conn, $_POST['payee_category']) : NULL;
    $address1 = !empty($_POST['payee_address1']) ? mysqli_real_escape_string($conn, $_POST['payee_address1']) : NULL;
    $address2 = !empty($_POST['payee_address2']) ? mysqli_real_escape_string($conn, $_POST['payee_address2']) : NULL;

    $query = "
        INSERT INTO payees (
            payee_name,
            payee_type,
            payee_tin,
            payee_category,
            payee_address1,
            payee_address2,
            payee_created_at
        ) VALUES (
            '$name',
            '$type',
            " . ($tin ? "'$tin'" : "NULL") . ",
            " . ($category ? "'$category'" : "NULL") . ",
            " . ($address1 ? "'$address1'" : "NULL") . ",
            " . ($address2 ? "'$address2'" : "NULL") . ",
            NOW()
        )
    ";

    if (mysqli_query($conn, $query)) {
        $_SESSION['alert'] = "success";
        header("Location: payees.php");
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
            <a href="dashboard.php" class="tip-bottom"><i class="icon-home"></i> Home</a>
            <a href="payees.php" class="tip-bottom">Payees</a>
            <a href="#" class="current">Add New Payee</a>
        </div>
    </div>

    <div class="container-fluid">
        <div class="row-fluid" style="background-color: white; min-height: 500px; padding: 20px;">
            <div class="span12">

                <?php if ($alert == "success") { ?>
                    <div class="alert alert-success">Payee added successfully!</div>
                <?php } elseif ($alert == "error") { ?>
                    <div class="alert alert-danger">Error: Unable to save payee.</div>
                <?php } ?>

                <div class="widget-box" style="max-width: 700px; margin: 0 auto;">
                    <div class="widget-title">
                        <span class="icon"><i class="icon-align-justify"></i></span>
                        <h5>Payee Information</h5>
                    </div>

                    <div class="widget-content" style="padding: 20px;">
                        <form action="" method="post" class="form-horizontal">

                            <!-- Payee Name -->
                            <div class="control-group">
                                <label class="control-label">Payee Name:</label>
                                <div class="controls">
                                    <input type="text" class="span11" name="payee_name" required />
                                </div>
                            </div>

                            <!-- Payee Type -->
                            <div class="control-group">
                                <label class="control-label">Payee Type:</label>
                                <div class="controls">
                                    <input type="text" class="span11" name="payee_type" required />
                                </div>
                            </div>

                            <!-- Payee TIN -->
                            <div class="control-group">
                                <label class="control-label">TIN (Optional):</label>
                                <div class="controls">
                                    <input type="text" class="span11" name="payee_tin" />
                                </div>
                            </div>

                            <!-- Payee Category -->
                            <div class="control-group">
                                <label class="control-label">Category (Optional):</label>
                                <div class="controls">
                                    <input type="text" class="span11" name="payee_category" />
                                </div>
                            </div>

                            <!-- Address 1 -->
                            <div class="control-group">
                                <label class="control-label">Address 1 (Optional):</label>
                                <div class="controls">
                                    <input type="text" class="span11" name="payee_address1" />
                                </div>
                            </div>

                            <!-- Address 2 -->
                            <div class="control-group">
                                <label class="control-label">Address 2 (Optional):</label>
                                <div class="controls">
                                    <input type="text" class="span11" name="payee_address2" />
                                </div>
                            </div>

                            <div class="form-actions" style="padding-left: 180px;">
                                <button type="submit" name="submit_payee" class="btn btn-success">Save Payee</button>
                                <a href="payees.php" class="btn btn-secondary">Cancel</a>
                            </div>

                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<?php include "footer.php"; ?>

<?php
ob_start();
session_start();
include "header.php"; 
include "connection.php";

// Handle form submission
if (isset($_POST['submit_reseller'])) {
    $reseller_name = mysqli_real_escape_string($conn, $_POST['reseller_name']);

    if (!empty($reseller_name)) {
        $query = "INSERT INTO resellers (reseller_name, reseller_created_at) VALUES ('$reseller_name', NOW())";

        if (mysqli_query($conn, $query)) {
            $_SESSION['alert'] = "success";
            header("Location: resellers.php");
            exit();
        } else {
            $_SESSION['alert'] = "error";
        }
    } else {
        $_SESSION['alert'] = "empty";
    }
}

$alert = $_SESSION['alert'] ?? null;
unset($_SESSION['alert']);
?>

<div id="content">
    <div id="content-header">
        <div id="breadcrumb">
            <a href="resellers.php" class="tip-bottom"><i class="icon-home"></i> Resellers</a>
            <a href="#" class="current">Add New Reseller</a>
        </div>
    </div>

    <div class="container-fluid">
        <div class="row-fluid" style="background-color: white; min-height: 400px; padding: 20px;">
            <div class="span12">

                <?php if ($alert == "success") { ?>
                    <div class="alert alert-success">Reseller added successfully!</div>
                <?php } elseif ($alert == "error") { ?>
                    <div class="alert alert-danger">Error: Unable to save reseller.</div>
                <?php } elseif ($alert == "empty") { ?>
                    <div class="alert alert-warning">Please enter a reseller name.</div>
                <?php } ?>

                <div class="widget-box" style="max-width: 600px; margin: 0 auto;">
                    <div class="widget-title">
                        <span class="icon"><i class="icon-align-justify"></i></span>
                        <h5>New Reseller Information</h5>
                    </div>

                    <div class="widget-content" style="padding: 20px;">
                        <form action="" method="post" class="form-horizontal">

                            <!-- Reseller Name -->
                            <div class="control-group">
                                <label class="control-label">Reseller Name:</label>
                                <div class="controls">
                                    <input type="text" name="reseller_name" class="span11" placeholder="Enter reseller name" required />
                                </div>
                            </div>

                            <div class="form-actions" style="padding-left: 180px;">
                                <button type="submit" name="submit_reseller" class="btn btn-success">Save Reseller</button>
                                <a href="resellers.php" class="btn btn-secondary">Cancel</a>
                            </div>

                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<?php include "footer.php"; ?>

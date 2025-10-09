<?php
session_start();
include "header.php"; 
include "connection.php";

// Handle form submission
if (isset($_POST['submit_end_user'])) {
    $end_user_name = mysqli_real_escape_string($conn, $_POST['end_user_name']);

    if (!empty($end_user_name)) {
        $query = "INSERT INTO expense_end_users (end_user_name, created_at) VALUES ('$end_user_name', NOW())";

        if (mysqli_query($conn, $query)) {
            $_SESSION['alert'] = "success";
            header("Location: end_users.php");
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
            <a href="end_users.php" class="tip-bottom"><i class="icon-home"></i> End Users</a>
            <a href="#" class="current">Add New End User</a>
        </div>
    </div>

    <div class="container-fluid">
        <div class="row-fluid" style="background-color: white; min-height: 400px; padding: 20px;">
            <div class="span12">

                <?php if ($alert == "success") { ?>
                    <div class="alert alert-success">End User added successfully!</div>
                <?php } elseif ($alert == "error") { ?>
                    <div class="alert alert-danger">Error: Unable to save End User.</div>
                <?php } elseif ($alert == "empty") { ?>
                    <div class="alert alert-warning">Please enter an End User name.</div>
                <?php } ?>

                <div class="widget-box" style="max-width: 600px; margin: 0 auto;">
                    <div class="widget-title">
                        <span class="icon"><i class="icon-align-justify"></i></span>
                        <h5>New End User Information</h5>
                    </div>

                    <div class="widget-content" style="padding: 20px;">
                        <form action="" method="post" class="form-horizontal">

                            <!-- End User Name -->
                            <div class="control-group">
                                <label class="control-label">End User Name:</label>
                                <div class="controls">
                                    <input type="text" name="end_user_name" class="span11" placeholder="Enter end user name" required />
                                </div>
                            </div>

                            <div class="form-actions" style="padding-left: 180px;">
                                <button type="submit" name="submit_end_user" class="btn btn-success">Save End User</button>
                                <a href="end_users.php" class="btn btn-secondary">Cancel</a>
                            </div>

                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<?php include "footer.php"; ?>

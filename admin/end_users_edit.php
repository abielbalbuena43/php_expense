<?php
ob_start();
session_start();
include "header.php";
include "connection.php";

// Validate End User ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: end_users.php");
    exit();
}

$end_user_id = intval($_GET['id']);

// Fetch End User record
$query = mysqli_query($conn, "SELECT * FROM expense_end_users WHERE end_user_id = '$end_user_id' LIMIT 1");
if (mysqli_num_rows($query) === 0) {
    $_SESSION['alert'] = "not_found";
    header("Location: end_users.php");
    exit();
}

$end_user = mysqli_fetch_assoc($query);

// Handle update
if (isset($_POST['update_end_user'])) {
    $end_user_name = mysqli_real_escape_string($conn, $_POST['end_user_name']);

    if (!empty($end_user_name)) {
        $update_query = "
            UPDATE expense_end_users
            SET end_user_name = '$end_user_name'
            WHERE end_user_id = '$end_user_id'
        ";

        if (mysqli_query($conn, $update_query)) {
            $_SESSION['alert'] = "success_update";
            header("Location: end_users.php");
            exit();
        } else {
            $_SESSION['alert'] = "error_update";
        }
    } else {
        $_SESSION['alert'] = "empty_fields";
    }
}

$alert = $_SESSION['alert'] ?? null;
unset($_SESSION['alert']);
?>

<div id="content">
    <div id="content-header">
        <div id="breadcrumb">
            <a href="end_users.php" class="tip-bottom"><i class="icon-home"></i> End Users</a>
            <a href="#" class="current">Edit End User</a>
        </div>
    </div>

    <div class="container-fluid">
        <div class="row-fluid" style="background-color: white; min-height: 400px; padding: 20px;">
            <div class="span12">

                <!-- Alert Messages -->
                <?php if ($alert == "success_update") { ?>
                    <div class="alert alert-success">End User updated successfully!</div>
                <?php } elseif ($alert == "error_update") { ?>
                    <div class="alert alert-danger">Error: Unable to update end user.</div>
                <?php } elseif ($alert == "empty_fields") { ?>
                    <div class="alert alert-warning">Please fill in all required fields.</div>
                <?php } elseif ($alert == "not_found") { ?>
                    <div class="alert alert-warning">End User not found.</div>
                <?php } ?>

                <div class="widget-box" style="max-width: 600px; margin: 0 auto;">
                    <div class="widget-title">
                        <span class="icon"><i class="icon-edit"></i></span>
                        <h5>Edit End User</h5>
                    </div>

                    <div class="widget-content" style="padding: 20px;">
                        <form action="" method="post" class="form-horizontal">

                            <!-- End User Name -->
                            <div class="control-group">
                                <label class="control-label">End User Name:</label>
                                <div class="controls">
                                    <input type="text" class="span11" name="end_user_name" 
                                           value="<?= htmlspecialchars($end_user['end_user_name']) ?>" required>
                                </div>
                            </div>

                            <div class="form-actions" style="padding-left: 180px;">
                                <button type="submit" name="update_end_user" class="btn btn-success">
                                    <i class="icon-save"></i> Update End User
                                </button>
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

<?php
session_start();
include "header.php";
include "connection.php";

// Check if an End User ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['alert'] = "invalid";
    header("Location: end_users.php");
    exit();
}

$end_user_id = intval($_GET['id']);

// Fetch the End User details for confirmation
$query = "
    SELECT 
        end_user_id,
        end_user_name,
        created_at
    FROM expense_end_users
    WHERE end_user_id = $end_user_id
";

$result = mysqli_query($conn, $query);

// If no End User found, redirect
if (!$result || mysqli_num_rows($result) === 0) {
    $_SESSION['alert'] = "not_found";
    header("Location: end_users.php");
    exit();
}

$end_user = mysqli_fetch_assoc($result);

// Handle delete confirmation
if (isset($_POST['confirm_delete'])) {
    $delete_query = "DELETE FROM expense_end_users WHERE end_user_id = $end_user_id";

    if (mysqli_query($conn, $delete_query)) {
        $_SESSION['alert'] = "deleted";
        header("Location: end_users.php");
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
            <a href="end_users.php" class="tip-bottom"><i class="icon-home"></i> End Users</a>
            <a href="#" class="current">Delete End User</a>
        </div>
    </div>

    <div class="container-fluid">
        <div class="row-fluid" style="background-color: white; min-height: 400px; padding: 20px;">
            <div class="span12">

                <!-- Alert Messages -->
                <?php if ($alert == "error") { ?>
                    <div class="alert alert-danger">Error: Unable to delete end user.</div>
                <?php } elseif ($alert == "invalid") { ?>
                    <div class="alert alert-warning">Invalid End User ID.</div>
                <?php } elseif ($alert == "not_found") { ?>
                    <div class="alert alert-warning">End User not found.</div>
                <?php } ?>

                <!-- Delete Confirmation -->
                <div class="widget-box" style="max-width: 800px; margin: 0 auto;">
                    <div class="widget-title">
                        <span class="icon"><i class="icon-trash"></i></span>
                        <h5>Delete End User Confirmation</h5>
                    </div>

                    <div class="widget-content" style="padding: 20px;">
                        <p>Are you sure you want to delete the following End User?</p>

                        <table class="table table-bordered table-striped">
                            <tr>
                                <th>ID</th>
                                <td><?= htmlspecialchars($end_user['end_user_id']) ?></td>
                            </tr>
                            <tr>
                                <th>Name</th>
                                <td><?= htmlspecialchars($end_user['end_user_name']) ?></td>
                            </tr>
                            <tr>
                                <th>Date Created</th>
                                <td><?= htmlspecialchars($end_user['created_at']) ?></td>
                            </tr>
                        </table>

                        <form action="" method="post" style="margin-top: 20px;">
                            <button type="submit" name="confirm_delete" class="btn btn-danger">
                                <i class="icon-trash"></i> Confirm Delete
                            </button>
                            <a href="end_users.php" class="btn btn-secondary">Cancel</a>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<?php include "footer.php"; ?>

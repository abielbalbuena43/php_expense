<?php
ob_start();
session_start();
include "header.php";
include "connection.php";

// Check if a user ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['alert'] = "invalid";
    header("Location: users.php");
    exit();
}

$user_id = intval($_GET['id']);

// Fetch user details for confirmation
$query = "SELECT user_id, username, fullname, role, created_at FROM users WHERE user_id = $user_id";
$result = mysqli_query($conn, $query);

// If no user found, redirect
if (!$result || mysqli_num_rows($result) === 0) {
    $_SESSION['alert'] = "not_found";
    header("Location: users.php");
    exit();
}

$user = mysqli_fetch_assoc($result);

// Handle delete confirmation
if (isset($_POST['confirm_delete'])) {
    $delete_query = "DELETE FROM users WHERE user_id = $user_id";

    if (mysqli_query($conn, $delete_query)) {
        $_SESSION['alert'] = "deleted";
        header("Location: users.php");
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
            <a href="users.php" class="tip-bottom"><i class="icon-home"></i> Users</a>
            <a href="#" class="current">Delete User</a>
        </div>
    </div>

    <div class="container-fluid">
        <div class="row-fluid" style="background-color: white; min-height: 400px; padding: 20px;">
            <div class="span12">

                <!-- Alert Messages -->
                <?php if ($alert == "error") { ?>
                    <div class="alert alert-danger">Error: Unable to delete user.</div>
                <?php } elseif ($alert == "invalid") { ?>
                    <div class="alert alert-warning">Invalid user ID.</div>
                <?php } elseif ($alert == "not_found") { ?>
                    <div class="alert alert-warning">User not found.</div>
                <?php } ?>

                <!-- Delete Confirmation -->
                <div class="widget-box" style="max-width: 600px; margin: 0 auto;">
                    <div class="widget-title">
                        <span class="icon"><i class="icon-trash"></i></span>
                        <h5>Delete User Confirmation</h5>
                    </div>

                    <div class="widget-content" style="padding: 20px;">
                        <p>Are you sure you want to delete the following user?</p>

                        <table class="table table-bordered table-striped">
                            <tr>
                                <th>User ID</th>
                                <td><?= htmlspecialchars($user['user_id']) ?></td>
                            </tr>
                            <tr>
                                <th>Username</th>
                                <td><?= htmlspecialchars($user['username']) ?></td>
                            </tr>
                            <tr>
                                <th>Full Name</th>
                                <td><?= htmlspecialchars($user['fullname']) ?></td>
                            </tr>
                            <tr>
                                <th>Role</th>
                                <td><?= htmlspecialchars($user['role']) ?></td>
                            </tr>
                            <tr>
                                <th>Created At</th>
                                <td><?= date('M d, Y h:i A', strtotime($user['created_at'])) ?></td>
                            </tr>
                        </table>

                        <form action="" method="post" style="margin-top: 20px;">
                            <button type="submit" name="confirm_delete" class="btn btn-danger">
                                <i class="icon-trash"></i> Confirm Delete
                            </button>
                            <a href="users.php" class="btn btn-secondary">Cancel</a>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<?php include "footer.php"; ?>

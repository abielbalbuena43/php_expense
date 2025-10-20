<?php
ob_start();
session_start();
include "header.php";
include "connection.php";

// Validate ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: users.php");
    exit();
}

$user_id = intval($_GET['id']);

// Fetch current user record
$user_query = mysqli_query($conn, "SELECT * FROM users WHERE user_id = '$user_id' LIMIT 1");
if (mysqli_num_rows($user_query) === 0) {
    echo "<div class='alert alert-danger'>User record not found.</div>";
    exit();
}
$user = mysqli_fetch_assoc($user_query);

// Handle update form submission
if (isset($_POST['update_user'])) {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $fullname = mysqli_real_escape_string($conn, $_POST['fullname']);
    $role = mysqli_real_escape_string($conn, $_POST['role']);
    $password = !empty($_POST['password']) ? mysqli_real_escape_string($conn, $_POST['password']) : null;


    // Build query dynamically depending on password change
    if ($password) {
        $query = "
            UPDATE users SET
                username = '$username',
                fullname = '$fullname',
                role = '$role',
                password = '$password'
            WHERE user_id = '$user_id'
        ";
    } else {
        $query = "
            UPDATE users SET
                username = '$username',
                fullname = '$fullname',
                role = '$role'
            WHERE user_id = '$user_id'
        ";
    }

    if (mysqli_query($conn, $query)) {
        $_SESSION['alert'] = "success_update";
        header("Location: users.php");
        exit();
    } else {
        $_SESSION['alert'] = "error_update";
    }
}

$alert = $_SESSION['alert'] ?? null;
unset($_SESSION['alert']);
?>

<div id="content">
    <div id="content-header">
        <div id="breadcrumb">
            <a href="users.php" class="tip-bottom"><i class="icon-home"></i> Users</a>
            <a href="#" class="current">Edit User</a>
        </div>
    </div>

    <div class="container-fluid">
        <div class="row-fluid" style="background-color: white; min-height: 400px; padding: 20px;">
            <div class="span12">

                <?php if ($alert == "success_update") { ?>
                    <div class="alert alert-success">User updated successfully!</div>
                <?php } elseif ($alert == "error_update") { ?>
                    <div class="alert alert-danger">Error: Unable to update user.</div>
                <?php } ?>

                <div class="widget-box" style="max-width: 700px; margin: 0 auto;">
                    <div class="widget-title">
                        <span class="icon"><i class="icon-edit"></i></span>
                        <h5>Edit User Information</h5>
                    </div>

                    <div class="widget-content" style="padding: 20px;">
                        <form action="" method="post" class="form-horizontal">

                            <!-- Username -->
                            <div class="control-group">
                                <label class="control-label">Username:</label>
                                <div class="controls">
                                    <input type="text" class="span11" name="username" 
                                           value="<?= htmlspecialchars($user['username']) ?>" required>
                                </div>
                            </div>

                            <!-- Full Name -->
                            <div class="control-group">
                                <label class="control-label">Full Name:</label>
                                <div class="controls">
                                    <input type="text" class="span11" name="fullname" 
                                           value="<?= htmlspecialchars($user['fullname']) ?>" required>
                                </div>
                            </div>

                            <!-- Password -->
                            <div class="control-group">
                                <label class="control-label">New Password:</label>
                                <div class="controls">
                                    <input type="password" class="span11" name="password" placeholder="Leave blank to keep current password">
                                </div>
                            </div>

                            <!-- Role -->
                            <div class="control-group">
                                <label class="control-label">Role:</label>
                                <div class="controls">
                                    <select name="role" class="span11" required>
                                        <option value="admin" <?= $user['role'] == 'admin' ? 'selected' : '' ?>>Admin</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Created At -->
                            <div class="control-group">
                                <label class="control-label">Created At:</label>
                                <div class="controls">
                                    <input type="text" class="span11" value="<?= date('M d, Y H:i', strtotime($user['created_at'])) ?>" disabled>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="form-actions" style="padding-left: 180px;">
                                <button type="submit" name="update_user" class="btn btn-success">Update User</button>
                                <a href="users.php" class="btn btn-secondary">Cancel</a>
                            </div>

                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<?php include "footer.php"; ?>

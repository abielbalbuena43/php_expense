<?php
ob_start();
session_start();
include "header.php";
include "connection.php";

// Handle form submission
if (isset($_POST['submit_user'])) {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    $fullname = mysqli_real_escape_string($conn, $_POST['fullname']);
    $role = mysqli_real_escape_string($conn, $_POST['role']);

    // Hash password for security
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $query = "
        INSERT INTO users (username, password, fullname, role, created_at)
        VALUES ('$username', '$hashed_password', '$fullname', '$role', NOW())
    ";

    if (mysqli_query($conn, $query)) {
        $_SESSION['alert'] = "success";
        header("Location: users.php");
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
            <a href="users.php" class="tip-bottom"><i class="icon-home"></i> Users</a>
            <a href="#" class="current">Add New User</a>
        </div>
    </div>

    <div class="container-fluid">
        <div class="row-fluid" style="background-color: white; min-height: 400px; padding: 20px;">
            <div class="span12">

                <?php if ($alert == "success") { ?>
                    <div class="alert alert-success">User added successfully!</div>
                <?php } elseif ($alert == "error") { ?>
                    <div class="alert alert-danger">Error: Unable to save user.</div>
                <?php } ?>

                <div class="widget-box" style="max-width: 600px; margin: 0 auto;">
                    <div class="widget-title">
                        <span class="icon"><i class="icon-align-justify"></i></span>
                        <h5>User Information</h5>
                    </div>

                    <div class="widget-content" style="padding: 20px;">
                        <form action="" method="post" class="form-horizontal">

                            <!-- Username -->
                            <div class="control-group">
                                <label class="control-label">Username:</label>
                                <div class="controls">
                                    <input type="text" class="span11" name="username" required placeholder="Enter username">
                                </div>
                            </div>

                            <!-- Password -->
                            <div class="control-group">
                                <label class="control-label">Password:</label>
                                <div class="controls">
                                    <input type="password" class="span11" name="password" required placeholder="Enter password">
                                </div>
                            </div>

                            <!-- Fullname -->
                            <div class="control-group">
                                <label class="control-label">Full Name:</label>
                                <div class="controls">
                                    <input type="text" class="span11" name="fullname" required placeholder="Enter full name">
                                </div>
                            </div>

                            <!-- Role -->
                            <div class="control-group">
                                <label class="control-label">Role:</label>
                                <div class="controls">
                                    <select name="role" class="span11" required>
                                        <option value="admin" selected>admin</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Buttons -->
                            <div class="form-actions" style="padding-left: 180px;">
                                <button type="submit" name="submit_user" class="btn btn-success">Save User</button>
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

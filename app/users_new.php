<?php
session_start();
include "header.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
if ($_SESSION['role'] !== 'admin') {
    header("Location: dashboard.php");
    exit();
}

// ============================================
// SECURITY CHECK - Must be logged in
// ============================================
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: login.php");
    exit();
}

// ============================================
// ACCESS CONTROL - Only admins can create users
// ============================================
if ($_SESSION['role'] !== 'admin') {
    die("Access denied. Admins only.");
}

/* ============================================
   ALERT HELPER
============================================ */
function setAlert($type, $message) {
    $_SESSION['alert'] = [
        'type' => $type,
        'message' => $message
    ];
}

// ============================================
// HANDLE SUCCESS REDIRECTS
// ============================================
if (isset($_GET['success'])) {
    if ($_GET['success'] === 'added') {
        setAlert('success', 'User added successfully!');
    }
}

include "connection.php";

// ============================================
// HANDLE FORM SUBMISSION
// ============================================
if (isset($_POST['submit_user'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $fullname = trim($_POST['fullname']);
    $role = $_POST['role'];

    // Hash password for security
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Use prepared statement to prevent SQL injection
    $stmt = $conn->prepare("INSERT INTO users (username, password, fullname, role, created_at) VALUES (?, ?, ?, ?, NOW())");
    $stmt->bind_param("ssss", $username, $hashed_password, $fullname, $role);

    if ($stmt->execute()) {
        setAlert('success', 'User added successfully!');
        header("Location: users.php?success=added");
        exit();
    } else {
        $error = "Error: Unable to save user. " . $stmt->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Core CSS -->
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/layout.css">

    <!-- Icons -->
    <link href="font-awesome/css/font-awesome.css" rel="stylesheet">

    <title>Add New User</title>
</head>

<body>
    <div id="content"> 
        <div class="container-fluid">
            <div class="row-fluid" style="background-color: white; min-height: 600px; padding: 20px;">
                <div class="span12">
                    <!-- Alert Messages -->
                    <?php if (isset($_SESSION['alert'])): ?>
                        <?php
                        $alert = $_SESSION['alert'];
                        if (is_array($alert)) {
                            $type = $alert['type'] ?? 'info';
                            $message = $alert['message'] ?? 'Something happened.';
                        } else {
                            $type = 'success';
                            $message = $alert;
                        }
                        unset($_SESSION['alert']);
                        ?>
                        <div class="alert alert-<?= $type ?>">
                            <?= htmlspecialchars($message) ?>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger">
                            <?= htmlspecialchars($error) ?>
                        </div>
                    <?php endif; ?>

                    <div class="widget-box" style="max-width: 800px; margin: 0 auto;">
                        <div class="widget-title">
                            <h5><i class="fas fa-user-plus"></i> Add New User</h5>
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
                                            <option value="admin">Admin</option>
                                            <option value="user">User</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Buttons -->
                                <div class="form-actions action-buttons">
                                    <button type="submit" name="submit_user" class="btn btn-success">
                                        <i class="fas fa-save"></i> Save User
                                    </button>
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
</body>
</html>
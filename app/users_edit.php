<?php
session_start();
include "header.php";

// ============================================
// SECURITY CHECK - Must be logged in
// ============================================
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: login.php");
    exit();
}

// ============================================
// ROLE DEFINITIONS
// ============================================
$current_role = $_SESSION['role'];
$current_user_id = $_SESSION['user_id'];

include "connection.php";

// ============================================
// VALIDATE ID
// ============================================
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: users.php");
    exit();
}

$edit_user_id = intval($_GET['id']);

// ============================================
// ACCESS CONTROL
// ============================================
// Admin can edit anyone, regular user can only edit themselves
if ($current_role !== 'admin' && $current_user_id !== $edit_user_id) {
    die("Access denied. You can only edit your own profile.");
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
// FETCH USER RECORD
// ============================================
$stmt = $conn->prepare("SELECT user_id, username, fullname, role, created_at FROM users WHERE user_id = ?");
$stmt->bind_param("i", $edit_user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<div class='alert alert-danger'>User record not found.</div>";
    exit();
}

$user = $result->fetch_assoc();
$stmt->close();

// ============================================
// HANDLE UPDATE FORM
// ============================================
if (isset($_POST['update_user'])) {
    $username = trim($_POST['username']);
    $fullname = trim($_POST['fullname']);
    $role = $_POST['role'];
    $password = $_POST['password'];

    // Regular users cannot change their own role
    if ($current_role !== 'admin') {
        $role = 'user';
    }

    // Build dynamic query based on password change
    if (!empty($password)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET username = ?, fullname = ?, role = ?, password = ? WHERE user_id = ?");
        $stmt->bind_param("ssssi", $username, $fullname, $role, $hashed_password, $edit_user_id);
    } else {
        $stmt = $conn->prepare("UPDATE users SET username = ?, fullname = ?, role = ? WHERE user_id = ?");
        $stmt->bind_param("sssi", $username, $fullname, $role, $edit_user_id);
    }

    if ($stmt->execute()) {
        setAlert('success', 'User updated successfully!');
        
        // Update session if editing own profile
        if ($current_user_id === $edit_user_id) {
            $_SESSION['username'] = $username;
        }
        
        header("Location: users.php?success=edited");
        exit();
    } else {
        $error = "Error: Unable to update user. " . $stmt->error;
    }
    
    $stmt->close();
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

    <title>Edit User</title>
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
                        $type = is_array($alert) ? ($alert['type'] ?? 'info') : 'success';
                        $message = is_array($alert) ? ($alert['message'] ?? 'Something happened.') : $alert;
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
                            <h5>
                                <i class="fas fa-user-edit"></i> 
                                <?= $current_role === 'admin' ? 'Edit User' : 'Edit My Profile' ?>
                            </h5>
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
                                    <label class="control-label">
                                        <?= $current_user_id === $edit_user_id ? 'New Password:' : 'New Password:' ?>
                                    </label>
                                    <div class="controls">
                                        <input type="password" class="span11" name="password" 
                                               placeholder="Leave blank to keep current">
                                        <?php if ($current_user_id === $edit_user_id): ?>
                                            <p class="help-block" style="font-size: 11px; color: #666;">
                                                Leave blank if you don't want to change your password.
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Role (Admin Only) -->
                                <?php if ($current_role === 'admin'): ?>
                                    <div class="control-group">
                                        <label class="control-label">Role:</label>
                                        <div class="controls">
                                            <select name="role" class="span11" required>
                                                <option value="admin" <?= $user['role'] == 'admin' ? 'selected' : '' ?>>Admin</option>
                                                <option value="user" <?= $user['role'] == 'user' ? 'selected' : '' ?>>User</option>
                                            </select>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <input type="hidden" name="role" value="user">
                                <?php endif; ?>

                                <!-- Created At (Read Only) -->
                                <div class="control-group">
                                    <label class="control-label">Created At:</label>
                                    <div class="controls">
                                        <input type="text" class="span11" 
                                               value="<?= date('M d, Y H:i', strtotime($user['created_at'])) ?>" 
                                               disabled>
                                    </div>
                                </div>

                                <!-- Action Buttons -->
                                <div class="form-actions action-buttons">
                                    <button type="submit" name="update_user" class="btn btn-success">
                                        <i class="fas fa-save"></i> Update User
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
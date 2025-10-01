<?php
// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect to login if user is not logged in, but allow login page
$current_page = basename($_SERVER['PHP_SELF']);
if (!isset($_SESSION['user_id']) && $current_page != 'login.php') {
    header("Location: login.php");
    exit();
}

// Include local connection.php (inside the same folder)
include "connection.php";

// Get username from session or default to Guest
$username = isset($_SESSION['username']) ? $_SESSION['username'] : "Guest";

// Default user status
$status = "Active"; 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>PHP Expense Tracker</title>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    
    <!-- CSS Files -->
    <link rel="stylesheet" href="css/bootstrap.min.css"/>
    <link rel="stylesheet" href="css/bootstrap-responsive.min.css"/>
    <link rel="stylesheet" href="css/fullcalendar.css"/>
    <link rel="stylesheet" href="css/matrix-style.css"/>
    <link rel="stylesheet" href="css/matrix-media.css"/>
    <link href="font-awesome/css/font-awesome.css" rel="stylesheet"/>
    <link rel="stylesheet" href="css/jquery.gritter.css"/>
    <link href='http://fonts.googleapis.com/css?family=Open+Sans:400,700,800' rel='stylesheet' type='text/css'>
</head>

<body>

<!-- Top gray bar (no title text) -->
<div id="header" style="background-color: #28282B; display: flex; justify-content: space-between; padding: 10px;"></div>

<!-- Sidebar Menu -->
<div id="sidebar" style="margin-top: 40px;">
    <ul>
        <!-- User Info Section -->
        <li style="padding: 20px; text-align: center; background-color: #333; color: white; border-bottom: 1px solid #444;">
            <!-- User Icon -->
            <div style="width: 60px; height: 60px; background-color: #555; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 10px;">
                <i class="icon icon-user" style="font-size: 30px; color: white;"></i>
            </div>

            <!-- Username -->
            <strong style="display: block; font-size: 16px;"><?php echo htmlspecialchars($username); ?></strong>

            <!-- User Status -->
            <?php $status_color = ($status == 'Inactive') ? '#FFA500' : '#0f0'; ?>
            <span style="font-size: 12px; color: <?php echo $status_color; ?>; display: block; margin-top: 5px;">
                <?php echo htmlspecialchars($status); ?>
            </span>
        </li>

        <!-- Navigation Links -->
        <li class="<?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>">
            <a href="dashboard.php"><i class="icon icon-home"></i><span>Dashboard</span></a>
        </li>

        <li class="<?php echo ($current_page == 'expenses.php') ? 'active' : ''; ?>">
            <a href="expenses.php"><i class="icon icon-inbox"></i><span>Expenses</span></a>
        </li>

        <li class="<?php echo ($current_page == 'payees.php') ? 'active' : ''; ?>">
            <a href="payees.php"><i class="icon icon-inbox"></i><span>Payees</span></a>
        </li>
    </ul>
</div>

<!-- Logout Button -->
<div id="search" style="margin-bottom: 20px;">
    <form action="logout.php" method="post">
        <button type="submit" class="btn btn-danger">
            <i class="icon icon-share-alt"></i> Log Out
        </button>
    </form>
</div>

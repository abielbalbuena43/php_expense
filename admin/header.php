<?php
ob_start();
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
            <a href="expenses.php"><i class="icon icon-money"></i><span>Expenses</span></a>
        </li>

        <li class="<?php echo ($current_page == 'payees.php') ? 'active' : ''; ?>">
            <a href="payees.php"><i class="icon icon-briefcase"></i><span>Payees</span></a>
        </li>

        <li class="<?php echo ($current_page == 'categories.php') ? 'active' : ''; ?>">
            <a href="categories.php"><i class="icon icon-tags"></i><span>Categories</span></a>
        </li>

        <li class="<?php echo ($current_page == 'companies.php') ? 'active' : ''; ?>">
            <a href="companies.php"><i class="icon icon-building"></i><span>Companies</span></a>
        </li>

        <li class="<?php echo ($current_page == 'users.php') ? 'active' : ''; ?>">
            <a href="users.php"><i class="icon icon-user"></i><span>Users</span></a>
        </li>

        <!-- Special Fields Dropdown -->
        <li class="submenu <?php echo (in_array($current_page, ['resellers.php','end_users.php','products.php'])) ? 'active open' : ''; ?>">
            <a href="#"><i class="icon icon-tags"></i> <span>Special Fields</span> <span class="label label-important"></span></a>
            <ul>
                <li><a href="resellers.php">Resellers</a></li>
                <li><a href="end_users.php">End Users</a></li>
                <li><a href="products.php">Products</a></li>
            </ul>
        </li>

    </ul>
</div>

<!-- JavaScript Files -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
<script>
$(document).ready(function() {
    // If using Bootstrap, this should handle dropdowns automatically
    $('.submenu > a').on('click', function(e) {
        e.preventDefault();  // Prevent default link behavior
        var submenu = $(this).next('ul');  // Get the submenu ul
        if (submenu.is(':visible')) {
            submenu.slideUp();  // Hide if visible
        } else {
            submenu.slideDown();  // Show if hidden
        }
    });
    
    // Optional: Close other submenus if one is opened (for better UX)
    $('.submenu > a').on('click', function() {
        $('.submenu ul:visible').not($(this).next('ul')).slideUp();  // Close other visible submenus
    });
});
</script>

<!-- Logout Button (moved inside the body) -->
<div id="search" style="margin-bottom: 20px; padding: 10px;">
    <form action="logout.php" method="post">
        <button type="submit" class="btn btn-danger">
            <i class="icon icon-share-alt"></i> Log Out
        </button>
    </form>
</div>

</body>

</html>
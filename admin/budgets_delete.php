<?php
session_start();
include "connection.php";

/* -------------------------------
   CSRF TOKEN CHECK
--------------------------------*/
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/* -------------------------------
   HANDLE DELETE
--------------------------------*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {

    $delete_id = intval($_POST['delete_id']);
    $csrf_token = $_POST['csrf_token'] ?? '';

    if ($csrf_token !== $_SESSION['csrf_token']) {
        $_SESSION['alert'] = 'error';
        $_SESSION['alert_msg'] = 'Invalid CSRF token.';
        header("Location: budgets.php");
        exit();
    }

    // Delete budget
    $deleteStmt = $conn->prepare("DELETE FROM budgets WHERE budget_id = ?");
    $deleteStmt->bind_param("i", $delete_id);

    if ($deleteStmt->execute()) {

        // Log deletion
        $username = mysqli_real_escape_string($conn, $_SESSION['username']);
        $logQuery = "
            INSERT INTO logs (log_action, log_user, log_details, log_date)
            VALUES ('Budget deleted', '$username', 'Budget ID: $delete_id', NOW())
        ";
        mysqli_query($conn, $logQuery);

        $_SESSION['alert'] = 'success';
        $_SESSION['alert_msg'] = 'Budget deleted successfully.';

    } else {
        $_SESSION['alert'] = 'error';
        $_SESSION['alert_msg'] = 'Error deleting budget: ' . $conn->error;
    }

    $deleteStmt->close();
    header("Location: budgets.php");
    exit();
}

// Redirect if accessed directly
header("Location: budgets.php");
exit();
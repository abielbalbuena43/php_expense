<?php
session_start();
include "../admin/connection.php"; // or adjust the path if needed

if (isset($_POST['login'])) {
    // Use $conn, not $link
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);

    // Query the users table
    $query = "SELECT * FROM users WHERE username = '$username'";
    $result = mysqli_query($conn, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);

        // For now, plain text password check
        if ($user['password'] === $password) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            header("Location: dashboard.php");
            exit();
        } else {
            $error = "Incorrect password.";
        }
    } else {
        $error = "Incorrect username.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Login - PHP Expense Tracker</title>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <link rel="stylesheet" href="css/bootstrap.min.css"/>
    <link rel="stylesheet" href="css/matrix-login.css"/>
    <link href="font-awesome/css/font-awesome.css" rel="stylesheet"/>
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            background-color: #28282B;
            color: white;
        }
        #loginbox {
            padding: 40px;
            border-radius: 10px;
            width: 400px;
            background: rgba(0,0,0,0.7);
        }
        .main_input_box input {
            background: rgba(255, 255, 255, 0.8);
            border: none;
            padding: 15px;
            border-radius: 5px;
            width: calc(100% - 30px);
            font-size: 16px;
        }
        .form-actions {
            text-align: center;
        }
        .error-message {
            color: red;
            text-align: center;
            font-size: 14px;
            margin-top: 10px;
        }
    </style>
</head>
<body>
<div id="loginbox">
    <form method="post" action="login.php">
        <div class="control-group normal_text"><h3>Login Page</h3></div>
        <div class="control-group normal_text"><h4>Expense Tracker</h4></div>

        <div class="control-group">
            <div class="controls">
                <div class="main_input_box">
                    <input type="text" name="username" placeholder="Username" required/>
                </div>
            </div>
        </div>

        <div class="control-group">
            <div class="controls">
                <div class="main_input_box">
                    <input type="password" name="password" placeholder="Password" required/>
                </div>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" name="login" class="btn btn-success">Login</button>
        </div>

        <?php if (isset($error)) echo "<p class='error-message'>$error</p>"; ?>
    </form>
</div>
</body>
</html>

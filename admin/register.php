<?php
session_start();
include "../admin/connection.php";

if (isset($_POST['register'])) {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    $fullname = mysqli_real_escape_string($conn, $_POST['fullname']);

    // Check if username already exists
    $check_query = "SELECT * FROM users WHERE username = '$username'";
    $check_result = mysqli_query($conn, $check_query);

    if ($check_result && mysqli_num_rows($check_result) > 0) {
        $error = "Username already taken.";
    } else {
        // Insert new user (default role = admin)
        $query = "INSERT INTO users (username, password, fullname, role)
                  VALUES ('$username', '$password', '$fullname', 'admin')";
        if (mysqli_query($conn, $query)) {
            $_SESSION['success'] = "Registration successful. Please login.";
            header("Location: login.php");
            exit();
        } else {
            $error = "Error registering user.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Register - PHP Expense Tracker</title>
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
    <form method="post" action="register.php">
        <div class="control-group normal_text"><h3>Register</h3></div>
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

        <div class="control-group">
            <div class="controls">
                <div class="main_input_box">
                    <input type="text" name="fullname" placeholder="Full Name" required/>
                </div>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" name="register" class="btn btn-success">Register</button>
            <a href="login.php" class="btn btn-secondary" style="margin-left:10px;">Back to Login</a>
        </div>

        <?php if (isset($error)) echo "<p class='error-message'>$error</p>"; ?>
    </form>
</div>
</body>
</html>

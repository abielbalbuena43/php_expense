<?php
session_start();
include "connection.php";
include "header.php";

/* -------------------------------
   CSRF TOKEN
--------------------------------*/
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/* -------------------------------
   HANDLE FORM SUBMISSION
--------------------------------*/
if (isset($_POST['submit_budget'])) {

    $month = intval($_POST['month']);
    $year = intval($_POST['year']);
    $amount = floatval($_POST['amount']);

    // Prevent duplicate month/year
    $checkStmt = $conn->prepare("SELECT COUNT(*) FROM budgets WHERE month = ? AND year = ?");
    $checkStmt->bind_param("ii", $month, $year);
    $checkStmt->execute();
    $checkStmt->bind_result($count);
    $checkStmt->fetch();
    $checkStmt->close();

    if ($count > 0) {
        $_SESSION['alert'] = 'error';
        $_SESSION['alert_msg'] = "A budget for $month/$year already exists.";
    } else {
        $insertStmt = $conn->prepare("INSERT INTO budgets (month, year, amount, created_at) VALUES (?, ?, ?, NOW())");
        $insertStmt->bind_param("iid", $month, $year, $amount);

        if ($insertStmt->execute()) {
            // Log creation
            $budget_id = $insertStmt->insert_id;
            $username = mysqli_real_escape_string($conn, $_SESSION['username']);
            $logQuery = "
                INSERT INTO logs (log_action, log_user, log_details, log_date)
                VALUES ('Budget created', '$username', 'Budget ID: $budget_id', NOW())
            ";
            mysqli_query($conn, $logQuery);

            $_SESSION['alert'] = 'success';
            $_SESSION['alert_msg'] = "Budget for $month/$year created successfully.";
            header("Location: budgets.php");
            exit();
        } else {
            $_SESSION['alert'] = 'error';
            $_SESSION['alert_msg'] = "Database error: " . $conn->error;
        }

        $insertStmt->close();
    }
}

$alert = $_SESSION['alert'] ?? null;
$alert_msg = $_SESSION['alert_msg'] ?? null;
unset($_SESSION['alert'], $_SESSION['alert_msg']);
?>

<div id="content">
    <div id="content-header">
        <div id="breadcrumb">
            <a href="budgets.php" class="tip-bottom"><i class="icon-home"></i> Budgets</a>
            <a href="#" class="current">Add New Budget</a>
        </div>
    </div>

    <div class="container-fluid">
        <div class="row-fluid" style="background-color: white; min-height: 400px; padding: 20px;">
            <div class="span6 offset3">

                <?php if ($alert): ?>
                    <div class="alert alert-<?php echo ($alert == 'success') ? 'success' : 'danger'; ?>">
                        <?php echo htmlspecialchars($alert_msg); ?>
                    </div>
                <?php endif; ?>

                <div class="widget-box">
                    <div class="widget-title">
                        <span class="icon"><i class="icon-align-justify"></i></span>
                        <h5>Budget Information</h5>
                    </div>

                    <div class="widget-content" style="padding:20px;">
                        <form method="post" class="form-horizontal">

                            <div class="control-group">
                                <label class="control-label">Month:</label>
                                <div class="controls">
                                    <select name="month" class="span11" required>
                                        <?php
                                        $monthsArr = [
                                            1=>"January",2=>"February",3=>"March",4=>"April",
                                            5=>"May",6=>"June",7=>"July",8=>"August",
                                            9=>"September",10=>"October",11=>"November",12=>"December"
                                        ];
                                        foreach ($monthsArr as $num=>$name) {
                                            echo "<option value='$num'>$name</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>

                            <div class="control-group">
                                <label class="control-label">Year:</label>
                                <div class="controls">
                                    <select name="year" class="span11" required>
                                        <?php
                                        $currentYear = date('Y');
                                        for($y=$currentYear;$y>=$currentYear-10;$y--){
                                            echo "<option value='$y'>$y</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>

                            <div class="control-group">
                                <label class="control-label">Amount:</label>
                                <div class="controls">
                                    <input type="number" class="span11" name="amount" step="0.01" placeholder="0.00" required>
                                </div>
                            </div>

                            <div class="form-actions" style="padding-left:180px;">
                                <button type="submit" name="submit_budget" class="btn btn-success">Save Budget</button>
                                <a href="budgets.php" class="btn btn-secondary">Cancel</a>
                            </div>

                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<?php include "footer.php"; ?>
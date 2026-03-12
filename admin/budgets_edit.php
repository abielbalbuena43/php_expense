<?php
ob_start();
session_start();
include "header.php";
include "connection.php";

// Validate ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: budgets.php");
    exit();
}

$budget_id = intval($_GET['id']);

// Fetch current budget record
$budget_query = mysqli_query($conn, "SELECT * FROM budgets WHERE budget_id = '$budget_id' LIMIT 1");
if (mysqli_num_rows($budget_query) === 0) {
    echo "<div class='alert alert-danger'>Budget record not found.</div>";
    exit();
}
$budget = mysqli_fetch_assoc($budget_query);

// Handle update form submission
if (isset($_POST['update_budget'])) {
    $month = intval($_POST['month']);
    $year = intval($_POST['year']);
    $amount = floatval($_POST['amount']);

    // Optional: prevent duplicate month-year entries
    $checkDuplicate = mysqli_query($conn, "SELECT budget_id FROM budgets WHERE month = '$month' AND year = '$year' AND budget_id != '$budget_id'");
    if (mysqli_num_rows($checkDuplicate) > 0) {
        $_SESSION['alert'] = 'error';
        $_SESSION['alert_msg'] = "A budget for this month and year already exists.";
    } else {
        $updateStmt = $conn->prepare("UPDATE budgets SET month = ?, year = ?, amount = ? WHERE budget_id = ?");
        $updateStmt->bind_param("iidi", $month, $year, $amount, $budget_id);

        if ($updateStmt->execute()) {
            // Log the update
            $username = mysqli_real_escape_string($conn, $_SESSION['username']);
            $logQuery = "INSERT INTO logs (log_action, log_user, log_details, log_date)
                         VALUES ('Budget updated', '$username', 'Budget ID: $budget_id', NOW())";
            mysqli_query($conn, $logQuery);

            // Redirect to budgets list
            header("Location: budgets.php");
            exit();
        } else {
            $_SESSION['alert'] = 'error';
            $_SESSION['alert_msg'] = "Error updating budget: " . $conn->error;
        }
    }
}
$alert = $_SESSION['alert'] ?? null;
$alert_msg = $_SESSION['alert_msg'] ?? '';
unset($_SESSION['alert'], $_SESSION['alert_msg']);
?>

<div id="content">
    <div id="content-header">
        <div id="breadcrumb">
            <a href="budgets.php" class="tip-bottom"><i class="icon-home"></i> Budgets</a>
            <a href="#" class="current">Edit Budget</a>
        </div>
    </div>

    <div class="container-fluid">
        <div class="row-fluid" style="background-color: white; min-height: 300px; padding: 20px;">
            <div class="span12">

                <?php if ($alert == 'error') { ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($alert_msg) ?></div>
                <?php } ?>

                <div class="widget-box" style="max-width: 500px; margin: 0 auto;">
                    <div class="widget-title">
                        <span class="icon"><i class="icon-edit"></i></span>
                        <h5>Edit Budget</h5>
                    </div>

                    <div class="widget-content" style="padding: 20px;">
                        <form action="" method="post" class="form-horizontal">

                            <!-- Month -->
                            <div class="control-group">
                                <label class="control-label">Month:</label>
                                <div class="controls">
                                    <select name="month" class="span11" required>
                                        <?php
                                        $months = [
                                            1=>"January",2=>"February",3=>"March",4=>"April",
                                            5=>"May",6=>"June",7=>"July",8=>"August",
                                            9=>"September",10=>"October",11=>"November",12=>"December"
                                        ];
                                        foreach($months as $num=>$name){
                                            $sel = ($budget['month']==$num) ? 'selected' : '';
                                            echo "<option value='$num' $sel>$name</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>

                            <!-- Year -->
                            <div class="control-group">
                                <label class="control-label">Year:</label>
                                <div class="controls">
                                    <select name="year" class="span11" required>
                                        <?php
                                        $currentYear = date('Y');
                                        for ($y=$currentYear; $y>=$currentYear-10; $y--){
                                            $sel = ($budget['year']==$y) ? 'selected' : '';
                                            echo "<option value='$y' $sel>$y</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>

                            <!-- Amount -->
                            <div class="control-group">
                                <label class="control-label">Budget Amount:</label>
                                <div class="controls">
                                    <input type="number" step="0.01" class="span11" name="amount"
                                           value="<?= htmlspecialchars($budget['amount']) ?>" required>
                                </div>
                            </div>

                            <div class="form-actions" style="padding-left: 120px;">
                                <button type="submit" name="update_budget" class="btn btn-success">Update Budget</button>
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
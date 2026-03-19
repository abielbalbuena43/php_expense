<?php
ob_start();
session_start();
include "header.php"; 
include "connection.php";

/* -------------------------------
   VALIDATE ID
--------------------------------*/
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "<div class='alert alert-error'>Invalid Budget ID.</div>";
    exit();
}

$budget_id = intval($_GET['id']);

/* -------------------------------
   FETCH BUDGET
--------------------------------*/
$query = "SELECT * FROM budgets WHERE budget_id = '$budget_id' LIMIT 1";
$result = mysqli_query($conn, $query);

if (!$result || mysqli_num_rows($result) == 0) {
    echo "<div class='alert alert-error'>Budget record not found.</div>";
    exit();
}

$budget = mysqli_fetch_assoc($result);

/* -------------------------------
   MONTH FORMAT
--------------------------------*/
$months = [
    1=>"January",2=>"February",3=>"March",4=>"April",
    5=>"May",6=>"June",7=>"July",8=>"August",
    9=>"September",10=>"October",11=>"November",12=>"December"
];
?>

<link rel="stylesheet" href="css/layout.css" />

<div id="content">

    <div class="container-fluid">
        <div class="row-fluid" style="background-color: white; min-height: 600px; padding: 20px;">
            <div class="span12">

                <!-- View Budget -->
                <div class="widget-box" style="max-width: 800px; margin: 0 auto;">
                    <div class="widget-title">
                        <h5>Budget Information</h5>
                    </div>

                    <div class="widget-content" style="padding: 20px;">
                        <form class="form-horizontal">

                            <!-- Month -->
                            <div class="control-group">
                                <label class="control-label">Month:</label>
                                <div class="controls">
                                    <input 
                                        type="text" 
                                        class="span11"
                                        value="<?= htmlspecialchars($months[$budget['month']] ?? $budget['month']) ?>"
                                        disabled
                                    >
                                </div>
                            </div>

                            <!-- Year -->
                            <div class="control-group">
                                <label class="control-label">Year:</label>
                                <div class="controls">
                                    <input 
                                        type="text" 
                                        class="span11"
                                        value="<?= htmlspecialchars($budget['year']) ?>"
                                        disabled
                                    >
                                </div>
                            </div>

                            <!-- Budget Amount -->
                            <div class="control-group">
                                <label class="control-label">Budget Amount:</label>
                                <div class="controls">
                                    <input 
                                        type="text" 
                                        class="span11"
                                        value="₱<?= number_format($budget['amount'], 2) ?>"
                                        disabled
                                    >
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="form-actions" style="padding-left: 180px;">
                                <a href="budgets_edit.php?id=<?= $budget['budget_id'] ?>" class="btn btn-primary">
                                    Edit Budget
                                </a>

                                <a href="budgets_delete.php?id=<?= $budget['budget_id'] ?>" 
                                   class="btn btn-danger"
                                   onclick="return confirm('Are you sure you want to delete this budget?');">
                                    Delete Budget
                                </a>

                                <a href="budgets.php" class="btn btn-secondary">
                                    Back
                                </a>
                            </div>

                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>

</div>

<?php include "footer.php"; ?>
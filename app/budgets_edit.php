<?php
ob_start();
session_start();
include "header.php";
include "connection.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$role = $_SESSION['role'];
$isSuperAdmin = $role === 'super_admin';
$isAdmin = $role === 'admin';

if (!$isSuperAdmin && !$isAdmin) {
    header("Location: budgets.php");
    exit();
}

/* -------------------------------
   ALERT HELPER
--------------------------------*/
function setAlert($type, $message) {
    $_SESSION['alert'] = [
        'type' => $type,
        'message' => $message
    ];
}

/* -------------------------------
   VALIDATE ID
--------------------------------*/
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: budgets.php");
    exit();
}

$budget_id = intval($_GET['id']);

/* -------------------------------
   FETCH RECORD
--------------------------------*/
$budget_query = mysqli_query($conn, "SELECT * FROM budgets WHERE budget_id = '$budget_id' LIMIT 1");

if (mysqli_num_rows($budget_query) === 0) {
    setAlert('error', 'Budget record not found.');
    header("Location: budgets.php");
    exit();
}

$budget = mysqli_fetch_assoc($budget_query);

/* -------------------------------
   HANDLE UPDATE
--------------------------------*/
if (isset($_POST['update_budget'])) {

    $month = intval($_POST['month']);
    $year = intval($_POST['year']);
    $amount = floatval($_POST['amount']);

    $checkDuplicate = mysqli_query($conn, "
        SELECT budget_id 
        FROM budgets 
        WHERE month = '$month' 
        AND year = '$year' 
        AND budget_id != '$budget_id'
    ");

    if (mysqli_num_rows($checkDuplicate) > 0) {

        setAlert('error', 'A budget for this period already exists.');

    } else {

        $updateStmt = $conn->prepare("
            UPDATE budgets 
            SET month = ?, year = ?, amount = ? 
            WHERE budget_id = ?
        ");
        $updateStmt->bind_param("iidi", $month, $year, $amount, $budget_id);

        if ($updateStmt->execute()) {

            $username = mysqli_real_escape_string($conn, $_SESSION['username']);

            mysqli_query($conn, "
                INSERT INTO logs (log_action, log_user, log_details, log_date)
                VALUES ('Budget updated', '$username', 'Budget ID: $budget_id', NOW())
            ");

            header("Location: budgets.php?success=edited");
            exit();

        } else {
            setAlert('error', 'Error updating budget: ' . $conn->error);
        }

        $updateStmt->close();
    }
}
?>

<link rel="stylesheet" href="css/layout.css">

<div id="content">
<div class="container-fluid">

<div class="row-fluid" style="background-color: white; min-height: 600px; padding: 20px;">
<div class="span12">

<?php if (isset($_SESSION['alert'])): ?>
<?php
$alert = $_SESSION['alert'];
$type = $alert['type'] ?? 'info';
$message = $alert['message'] ?? '';
unset($_SESSION['alert']);
?>
<div class="alert alert-<?= $type ?>">
<?= htmlspecialchars($message) ?>
</div>
<?php endif; ?>

<!-- MATCHED LAYOUT -->
<div class="widget-box" style="max-width: 800px; margin: 0 auto;">

<div class="widget-title">
<h5>Edit Budget Information</h5>
</div>

<div class="widget-content" style="padding: 20px;">

<form method="post" class="form-horizontal">

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

<input
type="number"
step="0.01"
class="span11"
name="amount"
value="<?= htmlspecialchars($budget['amount']) ?>"
required
>

</div>
</div>

<!-- ACTIONS -->
<div class="form-actions action-buttons">

<button type="submit" name="update_budget" class="btn btn-success">
Update Budget
</button>

<a href="budgets.php" class="btn btn-secondary">
Cancel
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
<?php
session_start();
include "connection.php";
include "header.php";

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
   HANDLE SUCCESS REDIRECTS
--------------------------------*/
if (isset($_GET['success'])) {

    if ($_GET['success'] === 'added') {
        setAlert('success', 'Expense added successfully!');
    }

    if ($_GET['success'] === 'edited') {
        setAlert('success', 'Expense updated successfully!');
    }

}

/* -------------------------------
   PERIOD FILTER LOGIC
--------------------------------*/
$selectedPeriods = [];

if (isset($_GET['month']) && isset($_GET['year'])) {
    $months = (array)$_GET['month'];
    $years = (array)$_GET['year'];
    $count = min(count($months), count($years));

    for ($i = 0; $i < $count; $i++) {
        $m = intval($months[$i]);
        $y = intval($years[$i]);

        if ($m >= 1 && $m <= 12 && $y > 0) {
            $selectedPeriods[] = [
                'month' => $m,
                'year' => $y
            ];
        }
    }
}

/* -------------------------------
   HANDLE DELETE
--------------------------------*/
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['delete_id']) &&
    $_POST['csrf_token'] === $_SESSION['csrf_token']
) {

    $deleteId = intval($_POST['delete_id']);

    $deleteStmt = $conn->prepare("DELETE FROM expenses WHERE expense_id = ?");
    $deleteStmt->bind_param("i", $deleteId);

    if ($deleteStmt->execute()) {

        $username = mysqli_real_escape_string($conn, $_SESSION['username'] ?? 'Unknown');

        $logStmt = $conn->prepare("
            INSERT INTO logs (log_action, log_user, log_details, log_date)
            VALUES (?, ?, ?, NOW())
        ");

        $logAction = "Expense deleted";
        $logDetails = "Expense ID: $deleteId";

        $logStmt->bind_param("sss", $logAction, $username, $logDetails);
        $logStmt->execute();
        $logStmt->close();

        setAlert('success', 'Expense deleted successfully!');

    } else {

        setAlert('error', 'Failed to delete expense.');

    }

    $deleteStmt->close();

    header("Location: " . $_SERVER['PHP_SELF'] . (isset($_GET['month']) ? '?' . http_build_query($_GET) : ''));
    exit();
}

/* -------------------------------
   CSRF TOKEN
--------------------------------*/
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/* -------------------------------
   SQL QUERY
--------------------------------*/
$sql = "
SELECT 
    e.expense_id,
    e.expense_or_number,
    e.expense_date,
    e.expense_total_receipt_amount,
    c.company_name,
    p.payee_name,
    cat.category_name
FROM expenses e
INNER JOIN companies c ON e.expense_company_id = c.company_id
INNER JOIN payees p ON e.expense_payee_id = p.payee_id
INNER JOIN expense_categories cat ON e.expense_category_id = cat.category_id
";

$params = [];
$types = "";

if (!empty($selectedPeriods)) {

    $conditions = [];

    foreach ($selectedPeriods as $p) {
        $conditions[] = "(MONTH(e.expense_date) = ? AND YEAR(e.expense_date) = ?)";
        $params[] = $p['month'];
        $params[] = $p['year'];
        $types .= "ii";
    }

    $sql .= " WHERE " . implode(" OR ", $conditions);
}

$sql .= " ORDER BY e.expense_date DESC, e.expense_id DESC LIMIT 100";

$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
$stmt->close();

/* -------------------------------
   PERIOD LABEL
--------------------------------*/
$periodLabel = !empty($selectedPeriods)
    ? implode(', ', array_map(function($p) {
        return date('F Y', mktime(0, 0, 0, $p['month'], 1, $p['year']));
    }, $selectedPeriods))
    : 'All Time';
?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

<link rel="stylesheet" href="css/bootstrap.min.css" />
<link rel="stylesheet" href="css/layout.css" />

<link href="font-awesome/css/font-awesome.css" rel="stylesheet" />

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<title>Expenses List</title>

</head>

<body>

<div id="content">

<?php if (isset($_SESSION['alert'])): ?>

<?php
$alert = $_SESSION['alert'];

if (is_array($alert)) {
    $type = $alert['type'] ?? 'info';
    $message = $alert['message'] ?? 'Something happened.';
} else {
    // fallback for old string alerts
    $type = 'success';
    $message = $alert;
}

unset($_SESSION['alert']);
?>

<div class="alert alert-<?= $type ?>">
    <?= htmlspecialchars($message) ?>
</div>

<?php endif; ?>


<div class="container-fluid">

<!-- Header Actions -->

<div class="header-actions">
    <a href="expense_new.php" class="btn btn-success">
        <i class="icon-plus"></i>
        Create New Expense
    </a>
</div>


<!-- Filter Section -->

<div class="filter-section">

<h4>
    <i class="icon-filter"></i>
    Filter by Periods (<?= htmlspecialchars($periodLabel) ?>)
</h4>

<form method="get" id="filterForm">

<div id="periodContainer">

<?php if (!empty($selectedPeriods)): ?>
<?php foreach ($selectedPeriods as $p): ?>

<div class="period-row">

<select name="month[]">

<?php
$months = [
    1=>"January",2=>"February",3=>"March",4=>"April",
    5=>"May",6=>"June",7=>"July",8=>"August",
    9=>"September",10=>"October",11=>"November",12=>"December"
];

foreach ($months as $num=>$name):
$sel = ($p['month']==$num) ? "selected" : "";
?>

<option value="<?= $num ?>" <?= $sel ?>>
<?= $name ?>
</option>

<?php endforeach; ?>

</select>


<select name="year[]">

<?php
$currentYear = date('Y');

for ($y=$currentYear;$y>=$currentYear-10;$y--):
$sel = ($p['year']==$y) ? "selected" : "";
?>

<option value="<?= $y ?>" <?= $sel ?>>
<?= $y ?>
</option>

<?php endfor; ?>

</select>

<button
type="button"
onclick="removePeriod(this)"
class="btn btn-danger-small"
>
<i class="icon-remove"></i>
</button>

</div>

<?php endforeach; ?>
<?php endif; ?>

</div>


<div class="filter-actions">

<button type="button" id="addPeriod" class="btn btn-primary">
<i class="icon-plus"></i>
Add Period
</button>

<button type="submit" class="btn btn-primary">
Apply Filter
</button>

<button type="button" id="clearFilter" class="btn btn-secondary">
Clear Filter
</button>

</div>

</form>

</div>


<!-- Main Table -->

<div class="table-container">

<div class="table-header">

<h3>
Expenses List (<?= htmlspecialchars($periodLabel) ?>)
</h3>

<span class="table-stats">
Showing <?= $result->num_rows ?? 0 ?> records
</span>

</div>


<div class="table-responsive">

<table>

<thead>
<tr>
<th>OR Number</th>
<th>Date</th>
<th>Company</th>
<th>Payee</th>
<th>Category</th>
<th>Amount</th>
</tr>
</thead>


<tbody>

<?php if ($result && $result->num_rows > 0): ?>

<?php while ($row = $result->fetch_assoc()): ?>

<tr
class="clickable-row"
data-href="expense_view.php?id=<?= $row['expense_id'] ?>"
>

<td><?= htmlspecialchars($row['expense_or_number']) ?></td>
<td><?= date('M d, Y', strtotime($row['expense_date'])) ?></td>
<td><?= htmlspecialchars($row['company_name']) ?></td>
<td><?= htmlspecialchars($row['payee_name']) ?></td>
<td><?= htmlspecialchars($row['category_name']) ?></td>

<td class="amount-col">
₱<?= number_format($row['expense_total_receipt_amount'], 2) ?>
</td>

</tr>

<?php endwhile; ?>

<?php else: ?>

<tr>
<td colspan="8">

<div class="empty-state">

<i class="icon-inbox"></i>

<h4>No expenses found</h4>

<p>
No expenses match your current filter.
Try adjusting the date range or create a new expense.
</p>

</div>

</td>
</tr>

<?php endif; ?>

</tbody>

</table>

</div>

</div>

</div>

</div>


<script>

$(document).on("click", ".clickable-row", function() {

const url = $(this).data("href");

if (url) {
    window.location.href = url;
}

});

$(document).ready(function(){

    $("#addPeriod").click(function(){

        const currentYear = new Date().getFullYear();

        let html = `
        <div class="period-row">

            <select name="month[]">
                <option value="1">January</option>
                <option value="2">February</option>
                <option value="3">March</option>
                <option value="4">April</option>
                <option value="5">May</option>
                <option value="6">June</option>
                <option value="7">July</option>
                <option value="8">August</option>
                <option value="9">September</option>
                <option value="10">October</option>
                <option value="11">November</option>
                <option value="12">December</option>
            </select>

            <select name="year[]">`;

        for(let y = currentYear; y >= currentYear - 10; y--){
            html += `<option value="${y}">${y}</option>`;
        }

        html += `</select>

            <button type="button" onclick="removePeriod(this)" class="btn btn-danger-small">
                <i class="icon-remove"></i>
            </button>

        </div>`;

        $("#periodContainer").append(html);
    });

    $("#clearFilter").click(function(){
        window.location.href = window.location.pathname;
    });

});

function removePeriod(btn){
    $(btn).closest('.period-row').remove();
}

</script>

</body>
</html>
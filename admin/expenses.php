<?php
session_start();
include "connection.php";
include "header.php";

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

if (empty($selectedPeriods)) {
    $selectedPeriods[] = [
        'month' => date('n'),
        'year' => date('Y')
    ];
}


/* -------------------------------
   HANDLE DELETE
--------------------------------*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id']) && $_POST['csrf_token'] === $_SESSION['csrf_token']) {

    $deleteId = intval($_POST['delete_id']);

    $deleteStmt = $conn->prepare("DELETE FROM expenses WHERE expense_id = ?");
    $deleteStmt->bind_param("i", $deleteId);

    if ($deleteStmt->execute()) {

        $username = mysqli_real_escape_string($conn, $_SESSION['username']);

        $logQuery = "
            INSERT INTO logs (log_action, log_user, log_details, log_date)
            VALUES ('Expense deleted', '$username', 'Expense ID: $deleteId', NOW())
        ";

        mysqli_query($conn, $logQuery);

        $_SESSION['alert'] = 'success';

    } else {
        $_SESSION['alert'] = 'error';
    }

    $deleteStmt->close();

    header("Location: " . $_SERVER['PHP_SELF']);
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
?>


<div id="content">
    <div id="content-header">
        <div id="breadcrumb">
            <a href="dashboard.php" class="tip-bottom"><i class="icon-home"></i> Home</a>
            <a href="#" class="current">Expenses</a>
        </div>
    </div>


    <div class="container-fluid">

        <div class="row-fluid">
            <div class="span12">

                <a href="expense_new.php" class="btn btn-success" style="margin-bottom:15px;">
                    <i class="icon-plus"></i> Create New Expense
                </a>

                <div class="filter-section" style="display:inline-block;margin-left:20px;vertical-align:middle;">

                    <form method="get" id="filterForm">

                        <label style="font-weight:bold;">Filter by Specific Periods:</label>

                        <div id="periodContainer">

                            <?php
                            $months = [
                                1=>"January",2=>"February",3=>"March",4=>"April",
                                5=>"May",6=>"June",7=>"July",8=>"August",
                                9=>"September",10=>"October",11=>"November",12=>"December"
                            ];

                            foreach($selectedPeriods as $p){
                            ?>

                            <div class="period-row" style="margin-bottom:5px;">

                                <select name="month[]" style="width:150px;padding:5px;">
                                    <?php
                                    foreach($months as $num=>$name){
                                        $sel = ($p['month']==$num) ? "selected":"";
                                        echo "<option value='$num' $sel>$name</option>";
                                    }
                                    ?>
                                </select>

                                <select name="year[]" style="width:100px;padding:5px;">
                                    <?php
                                    $currentYear=date('Y');
                                    for($y=$currentYear;$y>=$currentYear-10;$y--){
                                        $sel=($p['year']==$y)?"selected":"";
                                        echo "<option value='$y' $sel>$y</option>";
                                    }
                                    ?>
                                </select>

                                <button type="button" onclick="removePeriod(this)" class="btn btn-danger btn-mini">X</button>

                            </div>

                            <?php } ?>

                        </div>

                        <button type="button" id="addPeriod" class="btn btn-info btn-primary">+ Add Period</button>
                        <button type="submit" class="btn btn-primary">Apply Filter</button>
                        <button type="button" id="clearFilter" class="btn btn-secondary">Clear Filter</button>

                    </form>

                </div>
            </div>
        </div>


        <div class="row-fluid">
            <div class="span12">

                <div class="widget-box">
                    <div class="widget-content nopadding">

                        <table class="table table-bordered table-striped" id="expensesTable">

                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>OR Number</th>
                                    <th>Date</th>
                                    <th>Company</th>
                                    <th>Payee</th>
                                    <th>Category</th>
                                    <th>Amount</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>

                            <tbody>

                                <?php if ($result && $result->num_rows > 0): ?>

                                    <?php while ($row = $result->fetch_assoc()): ?>

                                        <tr>
                                            <td><?php echo $row['expense_id']; ?></td>

                                            <td><?php echo htmlspecialchars($row['expense_or_number']); ?></td>

                                            <td><?php echo date('M d, Y', strtotime($row['expense_date'])); ?></td>

                                            <td><?php echo htmlspecialchars($row['company_name']); ?></td>

                                            <td><?php echo htmlspecialchars($row['payee_name']); ?></td>

                                            <td><?php echo htmlspecialchars($row['category_name']); ?></td>

                                            <td>₱<?php echo number_format($row['expense_total_receipt_amount'],2); ?></td>

                                            <td style="white-space:nowrap;">

                                                <a href="expense_view.php?id=<?php echo $row['expense_id']; ?>" class="btn btn-info btn-mini">View</a>

                                                <form method="post" style="display:inline;" onsubmit="return confirm('Delete this expense?');">

                                                    <input type="hidden" name="delete_id" value="<?php echo $row['expense_id']; ?>">

                                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                                                    <button type="submit" class="btn btn-danger btn-mini">Delete</button>

                                                </form>

                                            </td>
                                        </tr>

                                    <?php endwhile; ?>

                                <?php else: ?>

                                    <tr>
                                        <td colspan="8" style="text-align:center;padding:20px;">
                                            No expenses found for the selected period.
                                        </td>
                                    </tr>

                                <?php endif; ?>

                            </tbody>

                        </table>

                    </div>
                </div>

            </div>
        </div>

    </div>
</div>

<?php include "footer.php"; ?>


<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>

    $(document).ready(function(){

        $("#addPeriod").click(function(){

            var currentYear=new Date().getFullYear();

            var html=`
            <div class="period-row" style="margin-bottom:5px;">

                <select name="month[]" style="width:150px;padding:5px;">
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

                <select name="year[]" style="width:100px;padding:5px;">`;

            for(var y=currentYear;y>=currentYear-10;y--){
                html+=`<option value="${y}">${y}</option>`;
            }

            html+=`</select>

                <button type="button" onclick="removePeriod(this)" class="btn btn-danger btn-mini">X</button>

            </div>`;

            $("#periodContainer").append(html);

        });


        $("#clearFilter").click(function(){
            window.location.href=window.location.pathname;
        });

    });


    function removePeriod(btn){
        $(btn).closest('.period-row').remove();
    }

</script>
<?php
include "connection.php";
include "header.php";

// Fetch all expense records with related data
$sql = "
    SELECT 
        e.expense_id,
        e.expense_or_number,
        e.expense_date,
        c.company_name,
        p.payee_name,
        cat.category_name
    FROM expenses e
    INNER JOIN companies c ON e.expense_company_id = c.company_id
    INNER JOIN payees p ON e.expense_payee_id = p.payee_id
    INNER JOIN expense_categories cat ON e.expense_category_id = cat.category_id
    ORDER BY e.expense_date DESC
";
$result = $conn->query($sql);
?>

<div id="content">
    <div id="content-header">
        <div id="breadcrumb">
            <a href="dashboard.php" class="tip-bottom"><i class="icon-home"></i> Home</a>
        </div>
    </div>

    <div class="container-fluid">

        <!-- Action Button -->
        <div class="row-fluid">
            <div class="span12">
                <a href="expense_new.php" class="btn btn-success" style="margin-bottom:15px;">
                    <i class="icon-plus"></i> Create New Expense
                </a>
            </div>
        </div>

        <!-- Expenses Table -->
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
                                            <td style="white-space: nowrap;">
                                                <a href="expense_view.php?id=<?php echo $row['expense_id']; ?>" class="btn btn-info btn-mini">View</a>
                                                <a href="expense_delete.php?id=<?php echo $row['expense_id']; ?>" class="btn btn-danger btn-mini" onclick="return confirm('Are you sure you want to delete this expense?');">Delete</a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" style="text-align:center;">No expenses found.</td>
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

<script>
$(document).ready(function() {
    $('#expensesTable').DataTable({
        "scrollX": true
    });
});
</script>

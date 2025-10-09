<?php
include "connection.php";
include "header.php";

// Fetch all resellers and count how many expenses are linked to each
$sql = "
    SELECT 
        r.reseller_id,
        r.reseller_name,
        r.reseller_created_at,
        COUNT(e.expense_id) AS total_expenses
    FROM resellers r
    LEFT JOIN expenses e ON r.reseller_id = e.expense_reseller_id
    GROUP BY r.reseller_id
    ORDER BY r.reseller_name ASC
";
$result = $conn->query($sql);
?>

<div id="content">
    <div id="content-header">
        <div id="breadcrumb">
            <a href="dashboard.php" class="tip-bottom"><i class="icon-home"></i> Home</a>
            <a href="resellers.php" class="current">Resellers</a>
        </div>
    </div>

    <div class="container-fluid">

        <!-- Action Button -->
        <div class="row-fluid">
            <div class="span12">
                <a href="resellers_new.php" class="btn btn-success" style="margin-bottom:15px;">
                    <i class="icon-plus"></i> Add New Reseller
                </a>
            </div>
        </div>

        <!-- Resellers Table -->
        <div class="row-fluid">
            <div class="span12">
                <div class="widget-box">
                    <div class="widget-content nopadding">
                        <table class="table table-bordered table-striped" id="resellersTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Reseller Name</th>
                                    <th>Total Linked Expenses</th>
                                    <th>Date Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($result && $result->num_rows > 0): ?>
                                    <?php while ($row = $result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo $row['reseller_id']; ?></td>
                                            <td><?php echo htmlspecialchars($row['reseller_name']); ?></td>
                                            <td><?php echo $row['total_expenses']; ?></td>
                                            <td><?php echo date('M d, Y', strtotime($row['reseller_created_at'])); ?></td>
                                            <td style="white-space: nowrap;">
                                                <a href="resellers_view.php?id=<?php echo $row['reseller_id']; ?>" class="btn btn-info btn-mini">View</a>
                                                <a href="resellers_delete.php?id=<?php echo $row['reseller_id']; ?>" class="btn btn-danger btn-mini" onclick="return confirm('Are you sure you want to delete this reseller?');">Delete</a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" style="text-align:center;">No resellers found.</td>
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
    $('#resellersTable').DataTable({
        "scrollX": true
    });
});
</script>

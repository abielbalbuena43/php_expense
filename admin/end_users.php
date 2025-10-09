<?php
include "connection.php";
include "header.php";

// Fetch all end users and count how many expenses are linked to each
$sql = "
    SELECT 
        eu.end_user_id,
        eu.end_user_name,
        eu.created_at,
        COUNT(e.expense_id) AS total_expenses
    FROM expense_end_users eu
    LEFT JOIN expenses e ON eu.end_user_id = e.expense_user_id
    GROUP BY eu.end_user_id
    ORDER BY eu.end_user_name ASC
";
$result = $conn->query($sql);
?>

<div id="content">
    <div id="content-header">
        <div id="breadcrumb">
            <a href="dashboard.php" class="tip-bottom"><i class="icon-home"></i> Home</a>
            <a href="end_users.php" class="current">End Users</a>
        </div>
    </div>

    <div class="container-fluid">

        <!-- Action Button -->
        <div class="row-fluid">
            <div class="span12">
                <a href="end_users_new.php" class="btn btn-success" style="margin-bottom:15px;">
                    <i class="icon-plus"></i> Add New End User
                </a>
            </div>
        </div>

        <!-- End Users Table -->
        <div class="row-fluid">
            <div class="span12">
                <div class="widget-box">
                    <div class="widget-content nopadding">
                        <table class="table table-bordered table-striped" id="endUsersTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>End User Name</th>
                                    <th>Total Linked Expenses</th>
                                    <th>Date Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($result && $result->num_rows > 0): ?>
                                    <?php while ($row = $result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo $row['end_user_id']; ?></td>
                                            <td><?php echo htmlspecialchars($row['end_user_name']); ?></td>
                                            <td><?php echo $row['total_expenses']; ?></td>
                                            <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                                            <td style="white-space: nowrap;">
                                                <a href="end_users_view.php?id=<?php echo $row['end_user_id']; ?>" class="btn btn-primary btn-mini">View</a>
                                                <a href="end_users_delete.php?id=<?php echo $row['end_user_id']; ?>" class="btn btn-danger btn-mini" onclick="return confirm('Are you sure you want to delete this end user?');">Delete</a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" style="text-align:center;">No end users found.</td>
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
    $('#endUsersTable').DataTable({
        "scrollX": true
    });
});
</script>

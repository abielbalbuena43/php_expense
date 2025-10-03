<?php
include "connection.php";
include "header.php";

// Fetch all payees
$sql = "
    SELECT 
        payee_id,
        payee_name,
        payee_type,
        payee_tin,
        payee_category,
        payee_address1,
        payee_address2,
        payee_created_at
    FROM payees
    ORDER BY payee_created_at DESC
";
$result = $conn->query($sql);
?>

<div id="content">
    <div id="content-header">
        <div id="breadcrumb">
            <a href="dashboard.php" class="tip-bottom"><i class="icon-home"></i> Home</a>
            <a href="payees.php" class="current">Payees</a>
        </div>
    </div>

    <div class="container-fluid">

        <!-- Action Button -->
        <div class="row-fluid">
            <div class="span12">
                <a href="payees_new.php" class="btn btn-success" style="margin-bottom:15px;">
                    <i class="icon-plus"></i> Add New Payee
                </a>
            </div>
        </div>

        <!-- Payees Table -->
        <div class="row-fluid">
            <div class="span12">
                <div class="widget-box">
                    <div class="widget-content nopadding">
                        <table class="table table-bordered table-striped" id="payeesTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Payee Name</th>
                                    <th>Type</th>
                                    <th>TIN</th>
                                    <th>Category</th>
                                    <th>Address 1</th>
                                    <th>Address 2</th>
                                    <th>Created At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($result && $result->num_rows > 0): ?>
                                    <?php while ($row = $result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo $row['payee_id']; ?></td>
                                            <td><?php echo htmlspecialchars($row['payee_name']); ?></td>
                                            <td><?php echo htmlspecialchars($row['payee_type']); ?></td>
                                            <td><?php echo htmlspecialchars($row['payee_tin']); ?></td>
                                            <td><?php echo htmlspecialchars($row['payee_category']); ?></td>
                                            <td><?php echo htmlspecialchars($row['payee_address1']); ?></td>
                                            <td><?php echo htmlspecialchars($row['payee_address2']); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($row['payee_created_at'])); ?></td>
                                            <td style="white-space: nowrap;">
                                                <a href="payees_view.php?id=<?php echo $row['payee_id']; ?>" class="btn btn-info btn-mini">View</a>
                                                <a href="payees_delete.php?id=<?php echo $row['payee_id']; ?>" class="btn btn-danger btn-mini" onclick="return confirm('Are you sure you want to delete this payee?');">Delete</a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="9" style="text-align:center;">No payees found.</td>
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
    $('#payeesTable').DataTable({
        "scrollX": true
    });
});
</script>

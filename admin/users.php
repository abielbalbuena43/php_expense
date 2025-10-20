<?php
include "connection.php";
include "header.php";

// Fetch all users
$sql = "
    SELECT 
        user_id,
        username,
        fullname,
        role,
        created_at
    FROM users
    ORDER BY created_at DESC
";
$result = $conn->query($sql);
?>

<div id="content">
    <div id="content-header">
        <div id="breadcrumb">
            <a href="dashboard.php" class="tip-bottom"><i class="icon-home"></i> Home</a>
            <a href="users.php" class="current">Users</a>
        </div>
    </div>

    <div class="container-fluid">

        <!-- Action Button -->
        <div class="row-fluid">
            <div class="span12">
                <a href="users_new.php" class="btn btn-success" style="margin-bottom:15px;">
                    <i class="icon-plus"></i> Create New User
                </a>
            </div>
        </div>

        <!-- Users Table -->
        <div class="row-fluid">
            <div class="span12">
                <div class="widget-box">
                    <div class="widget-content nopadding">
                        <table class="table table-bordered table-striped" id="usersTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Username</th>
                                    <th>Fullname</th>
                                    <th>Role</th>
                                    <th>Created At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($result && $result->num_rows > 0): ?>
                                    <?php while ($row = $result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo $row['user_id']; ?></td>
                                            <td><?php echo htmlspecialchars($row['username']); ?></td>
                                            <td><?php echo htmlspecialchars($row['fullname']); ?></td>
                                            <td><?php echo htmlspecialchars($row['role']); ?></td>
                                            <td><?php echo date('M d, Y H:i', strtotime($row['created_at'])); ?></td>
                                            <td style="white-space: nowrap;">
                                                <a href="users_view.php?id=<?php echo $row['user_id']; ?>" class="btn btn-info btn-mini">View</a>
                                                <a href="users_delete.php?id=<?php echo $row['user_id']; ?>" class="btn btn-danger btn-mini" onclick="return confirm('Are you sure you want to delete this user?');">Delete</a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" style="text-align:center;">No users found.</td>
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
    $('#usersTable').DataTable({
        "scrollX": true
    });
});
</script>

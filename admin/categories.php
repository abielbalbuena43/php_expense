<?php
include "connection.php";
include "header.php";

// Fetch all categories
$sql = "
    SELECT 
        category_id,
        category_name,
        category_created_at
    FROM expense_categories
    ORDER BY category_created_at DESC
";
$result = $conn->query($sql);
?>

<div id="content">
    <div id="content-header">
        <div id="breadcrumb">
            <a href="dashboard.php" class="tip-bottom"><i class="icon-home"></i> Home</a>
            <a href="categories.php" class="current">Expense Categories</a>
        </div>
    </div>

    <div class="container-fluid">

        <!-- Action Button -->
        <div class="row-fluid">
            <div class="span12">
                <a href="category_new.php" class="btn btn-success" style="margin-bottom:15px;">
                    <i class="icon-plus"></i> Add New Category
                </a>
            </div>
        </div>

        <!-- Categories Table -->
        <div class="row-fluid">
            <div class="span12">
                <div class="widget-box">
                    <div class="widget-content nopadding">
                        <table class="table table-bordered table-striped" id="categoriesTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Category Name</th>
                                    <th>Created At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($result && $result->num_rows > 0): ?>
                                    <?php while ($row = $result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo $row['category_id']; ?></td>
                                            <td><?php echo htmlspecialchars($row['category_name']); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($row['category_created_at'])); ?></td>
                                            <td style="white-space: nowrap;">
                                                <a href="category_view.php?id=<?php echo $row['category_id']; ?>" class="btn btn-info btn-mini">View</a>
                                                <a href="category_delete.php?id=<?php echo $row['category_id']; ?>" class="btn btn-danger btn-mini" onclick="return confirm('Are you sure you want to delete this category?');">Delete</a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" style="text-align:center;">No categories found.</td>
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
    $('#categoriesTable').DataTable({
        "scrollX": true
    });
});
</script>

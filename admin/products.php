<?php
include "connection.php";
include "header.php";

// Fetch all products and count how many expenses are linked to each
$sql = "
    SELECT 
        p.product_id,
        p.product_name,
        p.created_at,
        COUNT(e.expense_id) AS total_expenses
    FROM expense_products p
    LEFT JOIN expenses e ON p.expense_id = e.expense_id
    GROUP BY p.product_id
    ORDER BY p.product_name ASC
";
$result = $conn->query($sql);
?>

<div id="content">
    <div id="content-header">
        <div id="breadcrumb">
            <a href="dashboard.php" class="tip-bottom"><i class="icon-home"></i> Home</a>
            <a href="products.php" class="current">Products</a>
        </div>
    </div>

    <div class="container-fluid">

        <!-- Action Button -->
        <div class="row-fluid">
            <div class="span12">
                <a href="products_new.php" class="btn btn-success" style="margin-bottom:15px;">
                    <i class="icon-plus"></i> Add New Product
                </a>
            </div>
        </div>

        <!-- Products Table -->
        <div class="row-fluid">
            <div class="span12">
                <div class="widget-box">
                    <div class="widget-content nopadding">
                        <table class="table table-bordered table-striped" id="productsTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Product Name</th>
                                    <th>Total Linked Expenses</th>
                                    <th>Date Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($result && $result->num_rows > 0): ?>
                                    <?php while ($row = $result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo $row['product_id']; ?></td>
                                            <td><?php echo htmlspecialchars($row['product_name']); ?></td>
                                            <td><?php echo $row['total_expenses']; ?></td>
                                            <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                                            <td style="white-space: nowrap;">
                                                <a href="products_view.php?id=<?php echo $row['product_id']; ?>" class="btn btn-info btn-mini">View</a>
                                                <a href="products_delete.php?id=<?php echo $row['product_id']; ?>" class="btn btn-danger btn-mini" onclick="return confirm('Are you sure you want to delete this product?');">Delete</a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" style="text-align:center;">No products found.</td>
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
    $('#productsTable').DataTable({
        "scrollX": true
    });
});
</script>

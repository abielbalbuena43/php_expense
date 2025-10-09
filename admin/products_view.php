<?php
session_start();
include "header.php"; 
include "connection.php";

// Validate Product ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "<div class='alert alert-danger'>Invalid Product ID.</div>";
    exit();
}

$product_id = intval($_GET['id']);

// Fetch product details
$query = "SELECT * FROM expense_products WHERE product_id = '$product_id' LIMIT 1";
$result = mysqli_query($conn, $query);

if (!$result || mysqli_num_rows($result) == 0) {
    echo "<div class='alert alert-danger'>Product not found.</div>";
    exit();
}

$product = mysqli_fetch_assoc($result);

// Fetch related expenses for this product
$expenses_query = "
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
    INNER JOIN expense_products ep ON e.expense_id = ep.expense_id
    WHERE ep.product_id = '$product_id'
    ORDER BY e.expense_date DESC
";
$expenses_result = mysqli_query($conn, $expenses_query);
?>

<div id="content">
    <div id="content-header">
        <div id="breadcrumb">
            <a href="products.php" class="tip-bottom"><i class="icon-home"></i> Products</a>
            <a href="#" class="current">View Product</a>
        </div>
    </div>

    <div class="container-fluid">
        <div class="row-fluid" style="background-color: white; min-height: 600px; padding: 20px;">
            <div class="span12">

                <!-- View Product Details -->
                <div class="widget-box" style="max-width: 600px; margin: 0 auto;">
                    <div class="widget-title">
                        <span class="icon"><i class="icon-tags"></i></span>
                        <h5>Product Information</h5>
                    </div>

                    <div class="widget-content" style="padding: 20px;">
                        <form class="form-horizontal">

                            <!-- Product Name -->
                            <div class="control-group">
                                <label class="control-label">Product Name:</label>
                                <div class="controls">
                                    <input type="text" class="span11" value="<?= htmlspecialchars($product['product_name']) ?>" disabled>
                                </div>
                            </div>

                            <!-- Created At -->
                            <div class="control-group">
                                <label class="control-label">Date Added:</label>
                                <div class="controls">
                                    <input type="text" class="span11" value="<?= date('M d, Y h:i A', strtotime($product['created_at'])) ?>" disabled>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="form-actions" style="padding-left: 180px;">
                                <a href="products_edit.php?id=<?= $product['product_id'] ?>" class="btn btn-primary">Edit Product</a>
                                <a href="products.php" class="btn btn-secondary">Back</a>
                            </div>

                        </form>
                    </div>
                </div>

                <!-- Related Expenses Table -->
                <div class="widget-box" style="margin-top: 30px;">
                    <div class="widget-content nopadding">
                        <table class="table table-bordered table-striped" id="productExpensesTable">
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
                                <?php if ($expenses_result && mysqli_num_rows($expenses_result) > 0): ?>
                                    <?php while ($row = mysqli_fetch_assoc($expenses_result)): ?>
                                        <tr>
                                            <td><?= $row['expense_id'] ?></td>
                                            <td><?= htmlspecialchars($row['expense_or_number']) ?></td>
                                            <td><?= date('M d, Y', strtotime($row['expense_date'])) ?></td>
                                            <td><?= htmlspecialchars($row['company_name']) ?></td>
                                            <td><?= htmlspecialchars($row['payee_name']) ?></td>
                                            <td><?= htmlspecialchars($row['category_name']) ?></td>
                                            <td style="white-space: nowrap;">
                                                <a href="expense_view.php?id=<?= $row['expense_id'] ?>" class="btn btn-info btn-mini">View</a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" style="text-align:center;">No related expenses found.</td>
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
    $('#productExpensesTable').DataTable({
        "scrollX": true
    });
});
</script>

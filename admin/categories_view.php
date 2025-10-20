<?php
ob_start();
session_start();
include "header.php"; 
include "connection.php";

// Validate category ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "<div class='alert alert-danger'>Invalid Category ID.</div>";
    exit();
}

$category_id = intval($_GET['id']);

// Fetch category details
$query = "
    SELECT * 
    FROM expense_categories 
    WHERE category_id = '$category_id'
    LIMIT 1
";
$result = mysqli_query($conn, $query);

if (!$result || mysqli_num_rows($result) == 0) {
    echo "<div class='alert alert-danger'>Category record not found.</div>";
    exit();
}

$category = mysqli_fetch_assoc($result);
?>

<div id="content">
    <div id="content-header">
        <div id="breadcrumb">
            <a href="categories.php" class="tip-bottom"><i class="icon-home"></i> Categories</a>
            <a href="#" class="current">View Category</a>
        </div>
    </div>

    <div class="container-fluid">
        <div class="row-fluid" style="background-color: white; min-height: 400px; padding: 20px;">
            <div class="span12">

                <!-- View Category -->
                <div class="widget-box" style="max-width: 600px; margin: 0 auto;">
                    <div class="widget-title">
                        <span class="icon"><i class="icon-align-justify"></i></span>
                        <h5>Category Information</h5>
                    </div>

                    <div class="widget-content" style="padding: 20px;">
                        <form class="form-horizontal">

                            <!-- Category Name -->
                            <div class="control-group">
                                <label class="control-label">Category Name:</label>
                                <div class="controls">
                                    <input type="text" class="span11" 
                                           value="<?= htmlspecialchars($category['category_name']) ?>" disabled>
                                </div>
                            </div>

                            <!-- Remarks -->
                            <div class="control-group">
                                <label class="control-label">Remarks:</label>
                                <div class="controls">
                                    <textarea class="span11" disabled><?= htmlspecialchars($category['category_remarks'] ?? '') ?></textarea>
                                </div>
                            </div>

                            <!-- Created At -->
                            <div class="control-group">
                                <label class="control-label">Created At:</label>
                                <div class="controls">
                                    <input type="text" class="span11" 
                                           value="<?= date('M d, Y h:i A', strtotime($category['category_created_at'])) ?>" disabled>
                                </div>
                            </div>

                            <!-- Updated At -->
                            <div class="control-group">
                                <label class="control-label">Last Updated:</label>
                                <div class="controls">
                                    <input type="text" class="span11" 
                                           value="<?= isset($category['category_updated_at']) ? date('M d, Y h:i A', strtotime($category['category_updated_at'])) : 'Never' ?>" disabled>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="form-actions" style="padding-left: 180px;">
                                <a href="categories_edit.php?id=<?= $category['category_id'] ?>" class="btn btn-primary">Edit Category</a>
                                <a href="categories.php" class="btn btn-secondary">Back</a>
                            </div>

                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<?php include "footer.php"; ?>

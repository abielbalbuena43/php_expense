<?php
ob_start();
session_start();
include "header.php";
include "connection.php";

// Check if category ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['alert'] = "invalid";
    header("Location: categories.php");
    exit();
}

$category_id = intval($_GET['id']);

// Fetch the category details for confirmation
$query = "
    SELECT 
        category_id,
        category_name
    FROM expense_categories
    WHERE category_id = $category_id
";
$result = mysqli_query($conn, $query);

// If no category found, redirect
if (!$result || mysqli_num_rows($result) === 0) {
    $_SESSION['alert'] = "not_found";
    header("Location: categories.php");
    exit();
}

$category = mysqli_fetch_assoc($result);

// Handle delete confirmation
if (isset($_POST['confirm_delete'])) {
    $delete_query = "DELETE FROM expense_categories WHERE category_id = $category_id";

    if (mysqli_query($conn, $delete_query)) {
        $_SESSION['alert'] = "deleted";
        header("Location: categories.php");
        exit();
    } else {
        $_SESSION['alert'] = "error";
    }
}

// Alert messages
if (isset($_SESSION['alert'])) {
    $alert = $_SESSION['alert'];
    unset($_SESSION['alert']);
} else {
    $alert = null;
}
?>

<div id="content">
    <div id="content-header">
        <div id="breadcrumb">
            <a href="categories.php" class="tip-bottom"><i class="icon-home"></i> Categories</a>
            <a href="#" class="current">Delete Category</a>
        </div>
    </div>

    <div class="container-fluid">
        <div class="row-fluid" style="background-color: white; min-height: 300px; padding: 20px;">
            <div class="span12">

                <!-- Alert Messages -->
                <?php if ($alert == "error") { ?>
                    <div class="alert alert-danger">Error: Unable to delete category.</div>
                <?php } elseif ($alert == "invalid") { ?>
                    <div class="alert alert-warning">Invalid category ID.</div>
                <?php } elseif ($alert == "not_found") { ?>
                    <div class="alert alert-warning">Category not found.</div>
                <?php } ?>

                <!-- Delete Confirmation -->
                <div class="widget-box" style="max-width: 600px; margin: 0 auto;">
                    <div class="widget-title">
                        <span class="icon"><i class="icon-trash"></i></span>
                        <h5>Delete Category Confirmation</h5>
                    </div>

                    <div class="widget-content" style="padding: 20px;">
                        <p>Are you sure you want to delete the following category?</p>

                        <table class="table table-bordered table-striped">
                            <tr>
                                <th>ID</th>
                                <td><?= htmlspecialchars($category['category_id']) ?></td>
                            </tr>
                            <tr>
                                <th>Name</th>
                                <td><?= htmlspecialchars($category['category_name']) ?></td>
                            </tr>
                        </table>

                        <form action="" method="post" style="margin-top: 20px;">
                            <button type="submit" name="confirm_delete" class="btn btn-danger">
                                <i class="icon-trash"></i> Confirm Delete
                            </button>
                            <a href="categories.php" class="btn btn-secondary">Cancel</a>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<?php include "footer.php"; ?>

<?php
session_start();
include "header.php"; 
include "connection.php";

// Handle form submission
if (isset($_POST['submit_category'])) {
    $category_name = mysqli_real_escape_string($conn, $_POST['category_name']);

    $query = "
        INSERT INTO expense_categories (
            category_name,
            category_created_at
        ) VALUES (
            '$category_name',
            NOW()
        )
    ";

    if (mysqli_query($conn, $query)) {
        $_SESSION['alert'] = "success";
        header("Location: categories.php");
        exit();
    } else {
        $_SESSION['alert'] = "error";
        echo "Database Error: " . mysqli_error($conn);
    }
}

$alert = $_SESSION['alert'] ?? null;
unset($_SESSION['alert']);
?>

<div id="content">
    <div id="content-header">
        <div id="breadcrumb">
            <a href="categories.php" class="tip-bottom"><i class="icon-home"></i> Categories</a>
            <a href="#" class="current">Add New Category</a>
        </div>
    </div>

    <div class="container-fluid">
        <div class="row-fluid" style="background-color: white; min-height: 400px; padding: 20px;">
            <div class="span12">

                <?php if ($alert == "success") { ?>
                    <div class="alert alert-success">Category added successfully!</div>
                <?php } elseif ($alert == "error") { ?>
                    <div class="alert alert-danger">Error: Unable to save category.</div>
                <?php } ?>

                <div class="widget-box" style="max-width: 600px; margin: 0 auto;">
                    <div class="widget-title">
                        <span class="icon"><i class="icon-align-justify"></i></span>
                        <h5>Category Information</h5>
                    </div>

                    <div class="widget-content" style="padding: 20px;">
                        <form action="" method="post" class="form-horizontal">

                            <!-- Category Name -->
                            <div class="control-group">
                                <label class="control-label">Category Name:</label>
                                <div class="controls">
                                    <input type="text" class="span11" name="category_name" required />
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="form-actions" style="padding-left: 180px;">
                                <button type="submit" name="submit_category" class="btn btn-success">Save Category</button>
                                <a href="categories.php" class="btn btn-secondary">Cancel</a>
                            </div>

                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<?php include "footer.php"; ?>

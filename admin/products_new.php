<?php
session_start();
include "header.php"; 
include "connection.php";

// Handle form submission
if (isset($_POST['submit_product'])) {
    $product_name = mysqli_real_escape_string($conn, $_POST['product_name']);

    if (!empty($product_name)) {
        $query = "INSERT INTO expense_products (product_name, created_at) VALUES ('$product_name', NOW())";

        if (mysqli_query($conn, $query)) {
            $_SESSION['alert'] = "success";
            header("Location: products.php");
            exit();
        } else {
            $_SESSION['alert'] = "error";
        }
    } else {
        $_SESSION['alert'] = "empty";
    }
}

$alert = $_SESSION['alert'] ?? null;
unset($_SESSION['alert']);
?>

<div id="content">
    <div id="content-header">
        <div id="breadcrumb">
            <a href="products.php" class="tip-bottom"><i class="icon-home"></i> Products</a>
            <a href="#" class="current">Add New Product</a>
        </div>
    </div>

    <div class="container-fluid">
        <div class="row-fluid" style="background-color: white; min-height: 400px; padding: 20px;">
            <div class="span12">

                <?php if ($alert == "success") { ?>
                    <div class="alert alert-success">Product added successfully!</div>
                <?php } elseif ($alert == "error") { ?>
                    <div class="alert alert-danger">Error: Unable to save product.</div>
                <?php } elseif ($alert == "empty") { ?>
                    <div class="alert alert-warning">Please enter a product name.</div>
                <?php } ?>

                <div class="widget-box" style="max-width: 600px; margin: 0 auto;">
                    <div class="widget-title">
                        <span class="icon"><i class="icon-align-justify"></i></span>
                        <h5>New Product Information</h5>
                    </div>

                    <div class="widget-content" style="padding: 20px;">
                        <form action="" method="post" class="form-horizontal">

                            <!-- Product Name -->
                            <div class="control-group">
                                <label class="control-label">Product Name:</label>
                                <div class="controls">
                                    <input type="text" name="product_name" class="span11" placeholder="Enter product name" required />
                                </div>
                            </div>

                            <div class="form-actions" style="padding-left: 180px;">
                                <button type="submit" name="submit_product" class="btn btn-success">Save Product</button>
                                <a href="products.php" class="btn btn-secondary">Cancel</a>
                            </div>

                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<?php include "footer.php"; ?>

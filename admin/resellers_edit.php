<?php
session_start();
include "header.php";
include "connection.php";

// Validate ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: resellers.php");
    exit();
}

$reseller_id = intval($_GET['id']);

// Fetch reseller record
$query = mysqli_query($conn, "SELECT * FROM resellers WHERE reseller_id = '$reseller_id' LIMIT 1");
if (mysqli_num_rows($query) === 0) {
    $_SESSION['alert'] = "not_found";
    header("Location: resellers.php");
    exit();
}

$reseller = mysqli_fetch_assoc($query);

// Handle update
if (isset($_POST['update_reseller'])) {
    $reseller_name = mysqli_real_escape_string($conn, $_POST['reseller_name']);

    if (!empty($reseller_name)) {
        $update_query = "
            UPDATE resellers 
            SET reseller_name = '$reseller_name'
            WHERE reseller_id = '$reseller_id'
        ";

        if (mysqli_query($conn, $update_query)) {
            $_SESSION['alert'] = "success_update";
            header("Location: resellers.php");
            exit();
        } else {
            $_SESSION['alert'] = "error_update";
        }
    } else {
        $_SESSION['alert'] = "empty_fields";
    }
}

$alert = $_SESSION['alert'] ?? null;
unset($_SESSION['alert']);
?>

<div id="content">
    <div id="content-header">
        <div id="breadcrumb">
            <a href="resellers.php" class="tip-bottom"><i class="icon-home"></i> Resellers</a>
            <a href="#" class="current">Edit Reseller</a>
        </div>
    </div>

    <div class="container-fluid">
        <div class="row-fluid" style="background-color: white; min-height: 400px; padding: 20px;">
            <div class="span12">

                <!-- Alert Messages -->
                <?php if ($alert == "success_update") { ?>
                    <div class="alert alert-success">Reseller updated successfully!</div>
                <?php } elseif ($alert == "error_update") { ?>
                    <div class="alert alert-danger">Error: Unable to update reseller.</div>
                <?php } elseif ($alert == "empty_fields") { ?>
                    <div class="alert alert-warning">Please fill in all required fields.</div>
                <?php } elseif ($alert == "not_found") { ?>
                    <div class="alert alert-warning">Reseller not found.</div>
                <?php } ?>

                <div class="widget-box" style="max-width: 600px; margin: 0 auto;">
                    <div class="widget-title">
                        <span class="icon"><i class="icon-edit"></i></span>
                        <h5>Edit Reseller</h5>
                    </div>

                    <div class="widget-content" style="padding: 20px;">
                        <form action="" method="post" class="form-horizontal">

                            <!-- Reseller Name -->
                            <div class="control-group">
                                <label class="control-label">Reseller Name:</label>
                                <div class="controls">
                                    <input type="text" class="span11" name="reseller_name" 
                                           value="<?= htmlspecialchars($reseller['reseller_name']) ?>" required>
                                </div>
                            </div>

                            <div class="form-actions" style="padding-left: 180px;">
                                <button type="submit" name="update_reseller" class="btn btn-success">
                                    <i class="icon-save"></i> Update Reseller
                                </button>
                                <a href="resellers.php" class="btn btn-secondary">Cancel</a>
                            </div>

                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<?php include "footer.php"; ?>

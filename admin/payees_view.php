<?php
ob_start();
session_start();
include "header.php"; 
include "connection.php";

// Validate payee ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "<div class='alert alert-danger'>Invalid Payee ID.</div>";
    exit();
}

$payee_id = intval($_GET['id']);

// Fetch payee details
$query = "
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
    WHERE payee_id = '$payee_id'
    LIMIT 1
";
$result = mysqli_query($conn, $query);

if (!$result || mysqli_num_rows($result) == 0) {
    echo "<div class='alert alert-danger'>Payee record not found.</div>";
    exit();
}

$payee = mysqli_fetch_assoc($result);
?>

<div id="content">
    <div id="content-header">
        <div id="breadcrumb">
            <a href="payees.php" class="tip-bottom"><i class="icon-home"></i> Payees</a>
            <a href="#" class="current">View Payee</a>
        </div>
    </div>

    <div class="container-fluid">
        <div class="row-fluid" style="background-color: white; min-height: 500px; padding: 20px;">
            <div class="span12">

                <!-- View Payee -->
                <div class="widget-box" style="max-width: 800px; margin: 0 auto;">
                    <div class="widget-title">
                        <span class="icon"><i class="icon-align-justify"></i></span>
                        <h5>Payee Information</h5>
                    </div>

                    <div class="widget-content" style="padding: 20px;">
                        <form class="form-horizontal">

                            <!-- Payee Name -->
                            <div class="control-group">
                                <label class="control-label">Name:</label>
                                <div class="controls">
                                    <input type="text" class="span11" value="<?= htmlspecialchars($payee['payee_name']) ?>" disabled>
                                </div>
                            </div>

                            <!-- Payee Type -->
                            <div class="control-group">
                                <label class="control-label">Type:</label>
                                <div class="controls">
                                    <input type="text" class="span11" value="<?= htmlspecialchars($payee['payee_type']) ?>" disabled>
                                </div>
                            </div>

                            <!-- Payee TIN -->
                            <div class="control-group">
                                <label class="control-label">TIN:</label>
                                <div class="controls">
                                    <input type="text" class="span11" value="<?= htmlspecialchars($payee['payee_tin'] ?? 'N/A') ?>" disabled>
                                </div>
                            </div>

                            <!-- Payee Category -->
                            <div class="control-group">
                                <label class="control-label">Category:</label>
                                <div class="controls">
                                    <input type="text" class="span11" value="<?= htmlspecialchars($payee['payee_category'] ?? 'N/A') ?>" disabled>
                                </div>
                            </div>

                            <!-- Address 1 -->
                            <div class="control-group">
                                <label class="control-label">Address 1:</label>
                                <div class="controls">
                                    <input type="text" class="span11" value="<?= htmlspecialchars($payee['payee_address1'] ?? 'N/A') ?>" disabled>
                                </div>
                            </div>

                            <!-- Address 2 -->
                            <div class="control-group">
                                <label class="control-label">Address 2:</label>
                                <div class="controls">
                                    <input type="text" class="span11" value="<?= htmlspecialchars($payee['payee_address2'] ?? 'N/A') ?>" disabled>
                                </div>
                            </div>

                            <!-- Created At -->
                            <div class="control-group">
                                <label class="control-label">Created At:</label>
                                <div class="controls">
                                    <input type="text" class="span11" value="<?= date('M d, Y', strtotime($payee['payee_created_at'])) ?>" disabled>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="form-actions" style="padding-left: 180px;">
                                <a href="payees_edit.php?id=<?= $payee['payee_id'] ?>" class="btn btn-primary">Edit Payee</a>
                                <a href="payees.php" class="btn btn-secondary">Back</a>
                            </div>

                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<?php include "footer.php"; ?>

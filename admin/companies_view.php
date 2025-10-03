<?php
session_start();
include "header.php"; 
include "connection.php";

// Validate company ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "<div class='alert alert-danger'>Invalid Company ID.</div>";
    exit();
}

$company_id = intval($_GET['id']);

// Fetch company details
$query = "SELECT * FROM companies WHERE company_id = '$company_id' LIMIT 1";
$result = mysqli_query($conn, $query);

if (!$result || mysqli_num_rows($result) == 0) {
    echo "<div class='alert alert-danger'>Company record not found.</div>";
    exit();
}

$company = mysqli_fetch_assoc($result);
?>

<div id="content">
    <div id="content-header">
        <div id="breadcrumb">
            <a href="companies.php" class="tip-bottom"><i class="icon-home"></i> Companies</a>
            <a href="#" class="current">View Company</a>
        </div>
    </div>

    <div class="container-fluid">
        <div class="row-fluid" style="background-color: white; min-height: 600px; padding: 20px;">
            <div class="span12">

                <!-- View Company -->
                <div class="widget-box" style="max-width: 800px; margin: 0 auto;">
                    <div class="widget-title">
                        <span class="icon"><i class="icon-align-justify"></i></span>
                        <h5>Company Information</h5>
                    </div>

                    <div class="widget-content" style="padding: 20px;">
                        <form class="form-horizontal">

                            <div class="control-group">
                                <label class="control-label">Company Name:</label>
                                <div class="controls">
                                    <input type="text" class="span11" value="<?= htmlspecialchars($company['company_name']) ?>" disabled>
                                </div>
                            </div>

                            <div class="control-group">
                                <label class="control-label">TIN:</label>
                                <div class="controls">
                                    <input type="text" class="span11" value="<?= htmlspecialchars($company['tin']) ?>" disabled>
                                </div>
                            </div>

                            <div class="control-group">
                                <label class="control-label">RDO Code:</label>
                                <div class="controls">
                                    <input type="text" class="span11" value="<?= htmlspecialchars($company['rdo_code']) ?>" disabled>
                                </div>
                            </div>

                            <div class="control-group">
                                <label class="control-label">Branch Code:</label>
                                <div class="controls">
                                    <input type="text" class="span11" value="<?= htmlspecialchars($company['branch_code']) ?>" disabled>
                                </div>
                            </div>

                            <div class="control-group">
                                <label class="control-label">Trade Name:</label>
                                <div class="controls">
                                    <input type="text" class="span11" value="<?= htmlspecialchars($company['trade_name']) ?>" disabled>
                                </div>
                            </div>

                            <div class="control-group">
                                <label class="control-label">Substreet:</label>
                                <div class="controls">
                                    <input type="text" class="span11" value="<?= htmlspecialchars($company['substreet']) ?>" disabled>
                                </div>
                            </div>

                            <div class="control-group">
                                <label class="control-label">Street:</label>
                                <div class="controls">
                                    <input type="text" class="span11" value="<?= htmlspecialchars($company['street']) ?>" disabled>
                                </div>
                            </div>

                            <div class="control-group">
                                <label class="control-label">Barangay:</label>
                                <div class="controls">
                                    <input type="text" class="span11" value="<?= htmlspecialchars($company['barangay']) ?>" disabled>
                                </div>
                            </div>

                            <div class="control-group">
                                <label class="control-label">City:</label>
                                <div class="controls">
                                    <input type="text" class="span11" value="<?= htmlspecialchars($company['city']) ?>" disabled>
                                </div>
                            </div>

                            <div class="control-group">
                                <label class="control-label">Province:</label>
                                <div class="controls">
                                    <input type="text" class="span11" value="<?= htmlspecialchars($company['province']) ?>" disabled>
                                </div>
                            </div>

                            <div class="control-group">
                                <label class="control-label">Zip Code:</label>
                                <div class="controls">
                                    <input type="text" class="span11" value="<?= htmlspecialchars($company['zip_code']) ?>" disabled>
                                </div>
                            </div>

                            <div class="control-group">
                                <label class="control-label">Special Fields:</label>
                                <div class="controls">
                                    <textarea class="span11" disabled><?= htmlspecialchars($company['special_fields']) ?></textarea>
                                </div>
                            </div>

                            <div class="control-group">
                                <label class="control-label">Created At:</label>
                                <div class="controls">
                                    <input type="text" class="span11" value="<?= date('M d, Y H:i:s', strtotime($company['company_created_at'])) ?>" disabled>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="form-actions" style="padding-left: 180px;">
                                <a href="companies_edit.php?id=<?= $company['company_id'] ?>" class="btn btn-primary">Edit Company</a>
                                <a href="companies.php" class="btn btn-secondary">Back</a>
                            </div>

                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<?php include "footer.php"; ?>

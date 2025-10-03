<?php
include "connection.php";
include "header.php";

// Fetch all companies
$sql = "
    SELECT 
        company_id,
        company_name,
        city
    FROM companies
    ORDER BY company_name ASC
";
$result = $conn->query($sql);
?>

<div id="content">
    <div id="content-header">
        <div id="breadcrumb">
            <a href="dashboard.php" class="tip-bottom"><i class="icon-home"></i> Home</a>
            <a href="companies.php" class="current">Companies</a>
        </div>
    </div>

    <div class="container-fluid">

        <!-- Action Button -->
        <div class="row-fluid">
            <div class="span12">
                <a href="companies_new.php" class="btn btn-success" style="margin-bottom:15px;">
                    <i class="icon-plus"></i> Add New Company
                </a>
            </div>
        </div>

        <!-- Companies Table -->
        <div class="row-fluid">
            <div class="span12">
                <div class="widget-box">
                    <div class="widget-content nopadding">
                        <table class="table table-bordered table-striped" id="companiesTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Company Name</th>
                                    <th>City</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($result && $result->num_rows > 0): ?>
                                    <?php while ($row = $result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo $row['company_id']; ?></td>
                                            <td><?php echo htmlspecialchars($row['company_name']); ?></td>
                                            <td><?php echo htmlspecialchars($row['city']); ?></td>
                                            <td style="white-space: nowrap;">
                                                <a href="companies_view.php?id=<?php echo $row['company_id']; ?>" class="btn btn-info btn-mini">View</a>
                                                <a href="companies_delete.php?id=<?php echo $row['company_id']; ?>" class="btn btn-danger btn-mini" onclick="return confirm('Are you sure you want to delete this company?');">Delete</a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" style="text-align:center;">No companies found.</td>
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
    $('#companiesTable').DataTable({
        "scrollX": true
    });
});
</script>

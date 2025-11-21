<?php
ob_start();
session_start();
include "header.php";
include "connection.php";

// Fetch all logs, ordered by most recent first
$logsStmt = $conn->prepare("
    SELECT log_id, log_action, log_user, log_details, log_date
    FROM logs
    ORDER BY log_date DESC
");
$logsStmt->execute();
$logsResult = $logsStmt->get_result();
$logsStmt->close();
?>

<div id="content">
    <div id="content-header">
        <div id="breadcrumb">
            <a href="dashboard.php" class="tip-bottom"><i class="icon-home"></i> Home</a>
            <a href="#" class="current">Logs</a>
        </div>
    </div>

    <div class="container-fluid">
        <div class="row-fluid">
            <div class="span12">
                    </div>
                    <div class="widget-content nopadding">
                        <table class="table table-bordered table-striped" id="logsTable">
                            <thead>
                                <tr>
                                    <th>Action</th>
                                    <th>User</th>
                                    <th>Details</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($logsResult && $logsResult->num_rows > 0) { ?>
                                    <?php while ($row = $logsResult->fetch_assoc()) { ?>
                                        <tr>
                                            <td><?= htmlspecialchars($row['log_action']) ?></td>
                                            <td><?= htmlspecialchars($row['log_user']) ?></td>
                                            <td><?= htmlspecialchars($row['log_details'] ?? '-') ?></td>
                                            <td><?= date('M d, Y H:i', strtotime($row['log_date'])) ?></td>
                                        </tr>
                                    <?php } ?>
                                <?php } else { ?>
                                    <tr>
                                        <td colspan="4" style="text-align: center;">No logs recorded.</td>
                                    </tr>
                                <?php } ?>
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
    $('#logsTable').DataTable({
        "scrollX": true,
        "pageLength": 25,
        "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "All"]]
    });
});
</script>

<?php ob_end_flush(); ?>
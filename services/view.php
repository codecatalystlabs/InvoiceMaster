<?php
/**
 * Code Catalyst Labs - View Service
 */
require_once '../includes/config.php';
require_once '../includes/functions.php';

requireLogin();
requireRole(['Admin', 'Finance']);

$page_title = 'View Service';

// Get service ID
$service_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($service_id === 0) {
    $_SESSION['error'] = "Invalid service ID";
    header('Location: list.php');
    exit();
}

// Get service details
$query = "SELECT s.*, u.username as creator 
          FROM services s 
          LEFT JOIN users u ON s.created_by = u.id
          WHERE s.id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $service_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    $_SESSION['error'] = "Service not found";
    header('Location: list.php');
    exit();
}

$service = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

// Get payment history
$payments_query = "SELECT sp.*, u.username as created_by_name 
                   FROM service_payments sp
                   LEFT JOIN users u ON sp.created_by = u.id
                   WHERE sp.service_id = ?
                   ORDER BY sp.payment_date DESC";
$pay_stmt = mysqli_prepare($conn, $payments_query);
mysqli_stmt_bind_param($pay_stmt, "i", $service_id);
mysqli_stmt_execute($pay_stmt);
$payments_result = mysqli_stmt_get_result($pay_stmt);

// Calculate days until next billing
$days_until_billing = ceil((strtotime($service['next_billing_date']) - time()) / 86400);

include '../includes/header.php';
?>

<div class="container">
    <?php displayAlert(); ?>
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-arrow-repeat"></i> Service Details</h2>
        <div>
            <a href="edit.php?id=<?php echo $service['id']; ?>" class="btn btn-primary">
                <i class="bi bi-pencil"></i> Edit
            </a>
            <a href="list.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back to List
            </a>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><?php echo htmlspecialchars($service['service_name']); ?></h5>
                        <span class="badge bg-<?php echo getStatusBadge($service['status']); ?> fs-6">
                            <?php echo $service['status']; ?>
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p><strong><i class="bi bi-hash"></i> Service Number:</strong><br>
                            <code><?php echo htmlspecialchars($service['service_number']); ?></code></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong><i class="bi bi-tag"></i> Category:</strong><br>
                            <?php echo htmlspecialchars($service['category']) ?: 'Not specified'; ?></p>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p><strong><i class="bi bi-building"></i> Provider:</strong><br>
                            <?php echo htmlspecialchars($service['provider_name']); ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong><i class="bi bi-telephone"></i> Provider Contact:</strong><br>
                            <?php echo htmlspecialchars($service['provider_contact']) ?: 'Not specified'; ?></p>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p><strong><i class="bi bi-cash"></i> Cost:</strong><br>
                            <span class="fs-4 text-primary"><?php echo formatCurrency($service['cost']); ?></span></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong><i class="bi bi-calendar-week"></i> Billing Frequency:</strong><br>
                            <span class="badge bg-info"><?php echo $service['billing_frequency']; ?></span></p>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p><strong><i class="bi bi-calendar-check"></i> Start Date:</strong><br>
                            <?php echo formatDate($service['start_date']); ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong><i class="bi bi-calendar-x"></i> End Date:</strong><br>
                            <?php echo $service['end_date'] ? formatDate($service['end_date']) : 'No end date'; ?></p>
                        </div>
                    </div>
                    
                    <div class="alert alert-<?php echo $days_until_billing <= 7 && $days_until_billing >= 0 ? 'warning' : 'info'; ?>">
                        <h6><i class="bi bi-calendar-event"></i> Next Billing</h6>
                        <p class="mb-1"><strong>Date:</strong> <?php echo formatDate($service['next_billing_date']); ?></p>
                        <p class="mb-0">
                            <?php if ($days_until_billing > 0): ?>
                                <i class="bi bi-clock"></i> Due in <?php echo $days_until_billing; ?> day(s)
                            <?php elseif ($days_until_billing === 0): ?>
                                <i class="bi bi-exclamation-triangle"></i> Due today!
                            <?php else: ?>
                                <i class="bi bi-exclamation-circle"></i> Overdue by <?php echo abs($days_until_billing); ?> day(s)
                            <?php endif; ?>
                        </p>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p><strong><i class="bi bi-arrow-clockwise"></i> Auto-renew:</strong><br>
                            <span class="badge bg-<?php echo $service['auto_renew'] ? 'success' : 'secondary'; ?>">
                                <?php echo $service['auto_renew'] ? 'Yes' : 'No'; ?>
                            </span></p>
                        </div>
                    </div>
                    
                    <?php if ($service['description']): ?>
                    <div class="row">
                        <div class="col-12">
                            <p><strong><i class="bi bi-file-text"></i> Description:</strong><br>
                            <?php echo nl2br(htmlspecialchars($service['description'])); ?></p>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Payment History -->
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="bi bi-clock-history"></i> Payment History</h5>
                </div>
                <div class="card-body">
                    <?php if (mysqli_num_rows($payments_result) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Amount</th>
                                        <th>Payment Method</th>
                                        <th>Reference</th>
                                        <th>Recorded By</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($payment = mysqli_fetch_assoc($payments_result)): ?>
                                    <tr>
                                        <td><?php echo formatDate($payment['payment_date']); ?></td>
                                        <td><?php echo formatCurrency($payment['amount']); ?></td>
                                        <td><?php echo htmlspecialchars($payment['payment_method']); ?></td>
                                        <td><code><?php echo htmlspecialchars($payment['reference_number']); ?></code></td>
                                        <td><?php echo htmlspecialchars($payment['created_by_name']); ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">No payment history</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card bg-light mb-3">
                <div class="card-header">
                    <h6 class="mb-0"><i class="bi bi-info-circle"></i> Record Information</h6>
                </div>
                <div class="card-body">
                    <p class="small mb-2">
                        <strong>Created By:</strong><br>
                        <?php echo htmlspecialchars($service['creator']); ?>
                    </p>
                    <p class="small mb-2">
                        <strong>Created At:</strong><br>
                        <?php echo formatDate($service['created_at']); ?>
                    </p>
                    <p class="small mb-0">
                        <strong>Last Updated:</strong><br>
                        <?php echo formatDate($service['updated_at']); ?>
                    </p>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0"><i class="bi bi-gear"></i> Actions</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="edit.php?id=<?php echo $service['id']; ?>" class="btn btn-primary">
                            <i class="bi bi-pencil"></i> Edit Service
                        </a>
                        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#paymentModal">
                            <i class="bi bi-credit-card"></i> Record Payment
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php 
mysqli_stmt_close($pay_stmt);
include '../includes/footer.php'; 
?>


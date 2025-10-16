<?php
/**
 * Code Catalyst Labs - Dashboard
 */
require_once 'includes/config.php';
require_once 'includes/functions.php';

requireLogin();

$page_title = 'Dashboard';

// Get statistics
$stats = [];

// Total Quotations
$query = "SELECT COUNT(*) as total FROM quotations";
$result = mysqli_query($conn, $query);
$stats['total_quotations'] = mysqli_fetch_assoc($result)['total'];

// Total Invoices
$query = "SELECT COUNT(*) as total FROM invoices";
$result = mysqli_query($conn, $query);
$stats['total_invoices'] = mysqli_fetch_assoc($result)['total'];

// Total Clients
$query = "SELECT COUNT(*) as total FROM clients";
$result = mysqli_query($conn, $query);
$stats['total_clients'] = mysqli_fetch_assoc($result)['total'];

// Unpaid Invoices
$query = "SELECT COUNT(*) as total FROM invoices WHERE status = 'Unpaid'";
$result = mysqli_query($conn, $query);
$stats['unpaid_invoices'] = mysqli_fetch_assoc($result)['total'];

// Total Revenue (Paid Invoices)
$query = "SELECT SUM(total) as revenue FROM invoices WHERE status = 'Paid'";
$result = mysqli_query($conn, $query);
$stats['total_revenue'] = mysqli_fetch_assoc($result)['revenue'] ?? 0;

// Pending Amount (Unpaid Invoices)
$query = "SELECT SUM(total) as pending FROM invoices WHERE status IN ('Unpaid', 'Overdue')";
$result = mysqli_query($conn, $query);
$stats['pending_amount'] = mysqli_fetch_assoc($result)['pending'] ?? 0;

// Recent Quotations
$query = "SELECT q.*, c.name as client_name 
          FROM quotations q 
          LEFT JOIN clients c ON q.client_id = c.id 
          ORDER BY q.created_at DESC LIMIT 5";
$recent_quotations = mysqli_query($conn, $query);

// Recent Invoices
$query = "SELECT i.*, c.name as client_name 
          FROM invoices i 
          LEFT JOIN clients c ON i.client_id = c.id 
          ORDER BY i.created_at DESC LIMIT 5";
$recent_invoices = mysqli_query($conn, $query);

// User Activity (for Admin only)
if (hasRole('Admin')) {
    $query = "SELECT u.username, COUNT(a.id) as action_count 
              FROM users u 
              LEFT JOIN audit_logs a ON u.id = a.user_id 
              GROUP BY u.id 
              ORDER BY action_count DESC 
              LIMIT 5";
    $user_activity = mysqli_query($conn, $query);
}

include 'includes/header.php';
?>

<div class="container">
    <?php displayAlert(); ?>
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-speedometer2"></i> Dashboard</h2>
        <div>
            <span class="text-muted">Welcome, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong></span>
        </div>
    </div>
    
    <!-- Statistics Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0">Total Quotations</h6>
                            <h3 class="mb-0"><?php echo $stats['total_quotations']; ?></h3>
                        </div>
                        <div class="fs-1 opacity-50">
                            <i class="bi bi-file-earmark-text"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-primary bg-opacity-25 border-0">
                    <a href="quotations/list.php" class="text-white text-decoration-none small">
                        View all <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0">Total Invoices</h6>
                            <h3 class="mb-0"><?php echo $stats['total_invoices']; ?></h3>
                        </div>
                        <div class="fs-1 opacity-50">
                            <i class="bi bi-receipt"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-success bg-opacity-25 border-0">
                    <a href="invoices/list.php" class="text-white text-decoration-none small">
                        View all <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0">Total Clients</h6>
                            <h3 class="mb-0"><?php echo $stats['total_clients']; ?></h3>
                        </div>
                        <div class="fs-1 opacity-50">
                            <i class="bi bi-people"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-info bg-opacity-25 border-0">
                    <a href="clients/list.php" class="text-white text-decoration-none small">
                        View all <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0">Unpaid Invoices</h6>
                            <h3 class="mb-0"><?php echo $stats['unpaid_invoices']; ?></h3>
                        </div>
                        <div class="fs-1 opacity-50">
                            <i class="bi bi-exclamation-triangle"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-warning bg-opacity-25 border-0">
                    <a href="invoices/list.php?status=Unpaid" class="text-white text-decoration-none small">
                        View all <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Revenue Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card border-success">
                <div class="card-body">
                    <h6 class="text-success"><i class="bi bi-cash-stack"></i> Total Revenue (Paid)</h6>
                    <h2 class="mb-0"><?php echo formatCurrency($stats['total_revenue']); ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-warning">
                <div class="card-body">
                    <h6 class="text-warning"><i class="bi bi-hourglass-split"></i> Pending Amount</h6>
                    <h2 class="mb-0"><?php echo formatCurrency($stats['pending_amount']); ?></h2>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Recent Quotations -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="bi bi-file-earmark-text"></i> Recent Quotations</h5>
                </div>
                <div class="card-body p-0">
                    <?php if (mysqli_num_rows($recent_quotations) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Number</th>
                                        <th>Client</th>
                                        <th>Total</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = mysqli_fetch_assoc($recent_quotations)): ?>
                                    <tr>
                                        <td>
                                            <a href="quotations/view.php?id=<?php echo $row['id']; ?>">
                                                <?php echo $row['quotation_number']; ?>
                                            </a>
                                        </td>
                                        <td><?php echo htmlspecialchars($row['client_name']); ?></td>
                                        <td><?php echo formatCurrency($row['total']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo getStatusBadge($row['status']); ?>">
                                                <?php echo $row['status']; ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted text-center py-4">No quotations yet</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Recent Invoices -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="bi bi-receipt"></i> Recent Invoices</h5>
                </div>
                <div class="card-body p-0">
                    <?php if (mysqli_num_rows($recent_invoices) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Number</th>
                                        <th>Client</th>
                                        <th>Total</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = mysqli_fetch_assoc($recent_invoices)): ?>
                                    <tr>
                                        <td>
                                            <a href="invoices/view.php?id=<?php echo $row['id']; ?>">
                                                <?php echo $row['invoice_number']; ?>
                                            </a>
                                        </td>
                                        <td><?php echo htmlspecialchars($row['client_name']); ?></td>
                                        <td><?php echo formatCurrency($row['total']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo getStatusBadge($row['status']); ?>">
                                                <?php echo $row['status']; ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted text-center py-4">No invoices yet</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <?php if (hasRole('Admin') && isset($user_activity)): ?>
    <!-- User Activity Summary (Admin Only) -->
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="bi bi-activity"></i> User Activity Summary</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Username</th>
                                    <th>Total Actions</th>
                                    <th>Activity Level</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = mysqli_fetch_assoc($user_activity)): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['username']); ?></td>
                                    <td><?php echo $row['action_count']; ?></td>
                                    <td>
                                        <div class="progress" style="height: 20px;">
                                            <?php
                                            $max_actions = 100;
                                            $percentage = min(100, ($row['action_count'] / $max_actions) * 100);
                                            ?>
                                            <div class="progress-bar bg-primary" role="progressbar" 
                                                 style="width: <?php echo $percentage; ?>%">
                                                <?php echo round($percentage); ?>%
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>


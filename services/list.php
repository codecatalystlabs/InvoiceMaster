<?php
/**
 * Code Catalyst Labs - Services Management
 * Track subscriptions and recurring services
 */
require_once '../includes/config.php';
require_once '../includes/functions.php';

requireLogin();
requireRole(['Admin', 'Finance']);

$page_title = 'Services Management';

// Handle search and filters
$search = isset($_GET['search']) ? clean($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? clean($_GET['status']) : '';

// Build query
$where_clauses = [];
$params = [];
$param_types = '';

if ($search) {
    $where_clauses[] = "(service_name LIKE ? OR provider_name LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $param_types .= 'ss';
}

if ($status_filter) {
    $where_clauses[] = "status = ?";
    $params[] = $status_filter;
    $param_types .= 's';
}

$where_sql = '';
if (count($where_clauses) > 0) {
    $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);
}

// Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = ITEMS_PER_PAGE;
$offset = ($page - 1) * $per_page;

// Get total count and monthly cost
$count_query = "SELECT COUNT(*) as total, 
                SUM(CASE 
                    WHEN billing_frequency = 'Monthly' THEN cost
                    WHEN billing_frequency = 'Yearly' THEN cost/12
                    WHEN billing_frequency = 'Quarterly' THEN cost/3
                    WHEN billing_frequency = 'Weekly' THEN cost*4
                    WHEN billing_frequency = 'Daily' THEN cost*30
                END) as monthly_cost
                FROM services $where_sql";
if (count($params) > 0) {
    $count_stmt = mysqli_prepare($conn, $count_query);
    if ($param_types) {
        mysqli_stmt_bind_param($count_stmt, $param_types, ...$params);
    }
    mysqli_stmt_execute($count_stmt);
    $count_result = mysqli_stmt_get_result($count_stmt);
    $count_row = mysqli_fetch_assoc($count_result);
    $total_records = $count_row['total'];
    $monthly_cost = $count_row['monthly_cost'] ?? 0;
    mysqli_stmt_close($count_stmt);
} else {
    $count_result = mysqli_query($conn, $count_query);
    $count_row = mysqli_fetch_assoc($count_result);
    $total_records = $count_row['total'];
    $monthly_cost = $count_row['monthly_cost'] ?? 0;
}

$pagination = getPagination($total_records, $page, $per_page);

// Get services
$query = "SELECT * FROM services $where_sql ORDER BY next_billing_date ASC, created_at DESC LIMIT ? OFFSET ?";
$params[] = $per_page;
$params[] = $offset;
$param_types .= 'ii';

$stmt = mysqli_prepare($conn, $query);
if (count($params) > 0) {
    mysqli_stmt_bind_param($stmt, $param_types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

include '../includes/header.php';
?>

<div class="container">
    <?php displayAlert(); ?>
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-arrow-repeat"></i> Services & Subscriptions</h2>
        <a href="create.php" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Add New Service
        </a>
    </div>
    
    <!-- Stats Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-primary">
                <div class="card-body">
                    <h6 class="text-primary"><i class="bi bi-calendar-month"></i> Estimated Monthly Cost</h6>
                    <h3 class="mb-0"><?php echo formatCurrency($monthly_cost); ?></h3>
                    <small class="text-muted"><?php echo $total_records; ?> service(s)</small>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" class="form-control" 
                           placeholder="Service or provider name" value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="Active" <?php echo $status_filter === 'Active' ? 'selected' : ''; ?>>Active</option>
                        <option value="Suspended" <?php echo $status_filter === 'Suspended' ? 'selected' : ''; ?>>Suspended</option>
                        <option value="Cancelled" <?php echo $status_filter === 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        <option value="Expired" <?php echo $status_filter === 'Expired' ? 'selected' : ''; ?>>Expired</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search"></i> Filter
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Services Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Service #</th>
                            <th>Service Name</th>
                            <th>Provider</th>
                            <th>Cost</th>
                            <th>Billing Frequency</th>
                            <th>Next Billing</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($result) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                            <?php
                            // Check if billing is due soon (within 7 days)
                            $days_until_billing = ceil((strtotime($row['next_billing_date']) - time()) / 86400);
                            $is_due_soon = $days_until_billing <= 7 && $days_until_billing >= 0;
                            ?>
                            <tr class="<?php echo $is_due_soon ? 'table-warning' : ''; ?>">
                                <td><strong><?php echo htmlspecialchars($row['service_number']); ?></strong></td>
                                <td><?php echo htmlspecialchars($row['service_name']); ?></td>
                                <td class="small"><?php echo htmlspecialchars($row['provider_name']); ?></td>
                                <td><strong><?php echo formatCurrency($row['cost']); ?></strong></td>
                                <td>
                                    <span class="badge bg-info">
                                        <?php echo $row['billing_frequency']; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php echo formatDate($row['next_billing_date']); ?>
                                    <?php if ($is_due_soon): ?>
                                        <br><small class="text-warning">
                                            <i class="bi bi-exclamation-triangle-fill"></i> Due in <?php echo $days_until_billing; ?> day(s)
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo getStatusBadge($row['status']); ?>">
                                        <?php echo $row['status']; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="view.php?id=<?php echo $row['id']; ?>" 
                                           class="btn btn-outline-info" title="View">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="edit.php?id=<?php echo $row['id']; ?>" 
                                           class="btn btn-outline-primary" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">
                                    No services found
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <?php if ($pagination['total_pages'] > 1): ?>
        <div class="card-footer">
            <?php 
            $filter_params = [];
            if ($search) $filter_params[] = "search=" . urlencode($search);
            if ($status_filter) $filter_params[] = "status=" . urlencode($status_filter);
            $base_url = 'list.php?' . implode('&', $filter_params);
            displayPagination($pagination['total_pages'], $pagination['current_page'], $base_url); 
            ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php 
mysqli_stmt_close($stmt);
include '../includes/footer.php'; 
?>


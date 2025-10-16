<?php
/**
 * Code Catalyst Labs - Audit Logs
 */
require_once '../includes/config.php';
require_once '../includes/functions.php';

requireLogin();
requireRole('Admin');

$page_title = 'Audit Logs';

// Pagination and filters
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = ITEMS_PER_PAGE;
$user_filter = isset($_GET['user']) ? (int)$_GET['user'] : 0;
$action_filter = isset($_GET['action']) ? clean($_GET['action']) : '';
$entity_filter = isset($_GET['entity']) ? clean($_GET['entity']) : '';
$date_from = isset($_GET['date_from']) ? clean($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? clean($_GET['date_to']) : '';

// Build query
$where_conditions = [];
$where_values = [];

if ($user_filter > 0) {
    $where_conditions[] = "a.user_id = ?";
    $where_values[] = $user_filter;
}

if ($action_filter) {
    $where_conditions[] = "a.action = ?";
    $where_values[] = $action_filter;
}

if ($entity_filter) {
    $where_conditions[] = "a.entity_type = ?";
    $where_values[] = $entity_filter;
}

if ($date_from) {
    $where_conditions[] = "DATE(a.timestamp) >= ?";
    $where_values[] = $date_from;
}

if ($date_to) {
    $where_conditions[] = "DATE(a.timestamp) <= ?";
    $where_values[] = $date_to;
}

$where_clause = !empty($where_conditions) ? ' WHERE ' . implode(' AND ', $where_conditions) : '';

// Count total records
$count_query = "SELECT COUNT(*) as total FROM audit_logs a" . $where_clause;
if (!empty($where_values)) {
    $stmt = mysqli_prepare($conn, $count_query);
    
    if (count($where_values) == 1) {
        mysqli_stmt_bind_param($stmt, "s", ...$where_values);
    } elseif (count($where_values) == 2) {
        $types = str_repeat('s', count($where_values));
        mysqli_stmt_bind_param($stmt, $types, ...$where_values);
    } else {
        $types = str_repeat('s', count($where_values));
        mysqli_stmt_bind_param($stmt, $types, ...$where_values);
    }
    
    mysqli_stmt_execute($stmt);
    $count_result = mysqli_stmt_get_result($stmt);
    $total_records = mysqli_fetch_assoc($count_result)['total'];
} else {
    $count_result = mysqli_query($conn, $count_query);
    $total_records = mysqli_fetch_assoc($count_result)['total'];
}

$pagination = getPagination($total_records, $page, $per_page);

// Get logs
$query = "SELECT a.*, u.username 
          FROM audit_logs a 
          LEFT JOIN users u ON a.user_id = u.id" 
          . $where_clause . 
          " ORDER BY a.timestamp DESC 
          LIMIT {$pagination['offset']}, $per_page";

$result = mysqli_query($conn, $query);

// Get users for filter
$users_query = "SELECT id, username FROM users ORDER BY username ASC";
$users_result = mysqli_query($conn, $users_query);

include '../includes/header.php';
?>

<div class="container">
    <?php displayAlert(); ?>
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-clock-history"></i> Audit Logs</h2>
    </div>
    
    <!-- Filters -->
    <div class="card mb-3">
        <div class="card-header bg-light">
            <h5 class="mb-0"><i class="bi bi-funnel"></i> Filters</h5>
        </div>
        <div class="card-body">
            <form method="GET" action="" class="row g-3">
                <div class="col-md-3">
                    <label for="user" class="form-label">User</label>
                    <select class="form-select" id="user" name="user">
                        <option value="0">All Users</option>
                        <?php while ($user = mysqli_fetch_assoc($users_result)): ?>
                        <option value="<?php echo $user['id']; ?>" 
                                <?php echo $user_filter == $user['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($user['username']); ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label for="action" class="form-label">Action</label>
                    <select class="form-select" id="action" name="action">
                        <option value="">All Actions</option>
                        <option value="Create" <?php echo $action_filter === 'Create' ? 'selected' : ''; ?>>Create</option>
                        <option value="Update" <?php echo $action_filter === 'Update' ? 'selected' : ''; ?>>Update</option>
                        <option value="Delete" <?php echo $action_filter === 'Delete' ? 'selected' : ''; ?>>Delete</option>
                        <option value="Login" <?php echo $action_filter === 'Login' ? 'selected' : ''; ?>>Login</option>
                        <option value="Logout" <?php echo $action_filter === 'Logout' ? 'selected' : ''; ?>>Logout</option>
                        <option value="Email Sent" <?php echo $action_filter === 'Email Sent' ? 'selected' : ''; ?>>Email Sent</option>
                        <option value="Convert" <?php echo $action_filter === 'Convert' ? 'selected' : ''; ?>>Convert</option>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label for="entity" class="form-label">Entity Type</label>
                    <select class="form-select" id="entity" name="entity">
                        <option value="">All Types</option>
                        <option value="User" <?php echo $entity_filter === 'User' ? 'selected' : ''; ?>>User</option>
                        <option value="Client" <?php echo $entity_filter === 'Client' ? 'selected' : ''; ?>>Client</option>
                        <option value="Quotation" <?php echo $entity_filter === 'Quotation' ? 'selected' : ''; ?>>Quotation</option>
                        <option value="Invoice" <?php echo $entity_filter === 'Invoice' ? 'selected' : ''; ?>>Invoice</option>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label for="date_from" class="form-label">Date From</label>
                    <input type="date" class="form-control" id="date_from" name="date_from" 
                           value="<?php echo $date_from; ?>">
                </div>
                
                <div class="col-md-2">
                    <label for="date_to" class="form-label">Date To</label>
                    <input type="date" class="form-control" id="date_to" name="date_to" 
                           value="<?php echo $date_to; ?>">
                </div>
                
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search"></i> Apply Filters
                    </button>
                    <a href="logs.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-clockwise"></i> Reset
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Logs Table -->
    <div class="card">
        <div class="card-body">
            <?php if (mysqli_num_rows($result) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover table-sm">
                        <thead>
                            <tr>
                                <th>Timestamp</th>
                                <th>User</th>
                                <th>Action</th>
                                <th>Entity Type</th>
                                <th>Entity ID</th>
                                <th>Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td><?php echo date('M d, Y H:i:s', strtotime($row['timestamp'])); ?></td>
                                <td>
                                    <span class="badge bg-secondary">
                                        <?php echo htmlspecialchars($row['username']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php
                                    $action_icons = [
                                        'Create' => 'plus-circle text-success',
                                        'Update' => 'pencil text-warning',
                                        'Delete' => 'trash text-danger',
                                        'Login' => 'box-arrow-in-right text-info',
                                        'Logout' => 'box-arrow-right text-secondary',
                                        'Email Sent' => 'envelope text-primary',
                                        'Convert' => 'arrow-right-circle text-info'
                                    ];
                                    $icon = isset($action_icons[$row['action']]) ? $action_icons[$row['action']] : 'circle';
                                    ?>
                                    <i class="bi bi-<?php echo $icon; ?>"></i>
                                    <?php echo $row['action']; ?>
                                </td>
                                <td>
                                    <span class="badge bg-info">
                                        <?php echo $row['entity_type']; ?>
                                    </span>
                                </td>
                                <td><?php echo $row['entity_id'] ?? '-'; ?></td>
                                <td>
                                    <small class="text-muted">
                                        <?php echo htmlspecialchars($row['details']); ?>
                                    </small>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php 
                $filter_params = "user=$user_filter&action=$action_filter&entity=$entity_filter&date_from=$date_from&date_to=$date_to";
                displayPagination($pagination['total_pages'], $pagination['current_page'], '?' . $filter_params);
                ?>
            <?php else: ?>
                <p class="text-muted text-center py-4">No audit logs found.</p>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="card mt-3">
        <div class="card-body">
            <div class="row">
                <div class="col-md-12">
                    <h6 class="text-muted mb-3">Statistics</h6>
                    <div class="row">
                        <div class="col-md-3">
                            <div class="text-center">
                                <h4 class="text-primary"><?php echo $total_records; ?></h4>
                                <small class="text-muted">Total Records</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center">
                                <?php
                                $today_query = "SELECT COUNT(*) as count FROM audit_logs WHERE DATE(timestamp) = CURDATE()";
                                $today_result = mysqli_query($conn, $today_query);
                                $today_count = mysqli_fetch_assoc($today_result)['count'];
                                ?>
                                <h4 class="text-success"><?php echo $today_count; ?></h4>
                                <small class="text-muted">Today's Actions</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center">
                                <?php
                                $week_query = "SELECT COUNT(*) as count FROM audit_logs WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                                $week_result = mysqli_query($conn, $week_query);
                                $week_count = mysqli_fetch_assoc($week_result)['count'];
                                ?>
                                <h4 class="text-info"><?php echo $week_count; ?></h4>
                                <small class="text-muted">This Week</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center">
                                <?php
                                $month_query = "SELECT COUNT(*) as count FROM audit_logs WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
                                $month_result = mysqli_query($conn, $month_query);
                                $month_count = mysqli_fetch_assoc($month_result)['count'];
                                ?>
                                <h4 class="text-warning"><?php echo $month_count; ?></h4>
                                <small class="text-muted">This Month</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>


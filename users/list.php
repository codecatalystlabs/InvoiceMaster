<?php
/**
 * Code Catalyst Labs - User Management
 * List all users (Admin only)
 */
require_once '../includes/config.php';
require_once '../includes/functions.php';

requireRole('Admin');

$page_title = 'User Management';

// Handle search and filters
$search = isset($_GET['search']) ? clean($_GET['search']) : '';
$role_filter = isset($_GET['role']) ? clean($_GET['role']) : '';
$status_filter = isset($_GET['status']) ? clean($_GET['status']) : '';

// Build query
$where_clauses = [];
$params = [];
$param_types = '';

if ($search) {
    $where_clauses[] = "(username LIKE ? OR email LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $param_types .= 'ss';
}

if ($role_filter) {
    $where_clauses[] = "role = ?";
    $params[] = $role_filter;
    $param_types .= 's';
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

// Get total count
$count_query = "SELECT COUNT(*) as total FROM users $where_sql";
if (count($params) > 0) {
    $count_stmt = mysqli_prepare($conn, $count_query);
    if ($param_types) {
        mysqli_stmt_bind_param($count_stmt, $param_types, ...$params);
    }
    mysqli_stmt_execute($count_stmt);
    $count_result = mysqli_stmt_get_result($count_stmt);
    $total_records = mysqli_fetch_assoc($count_result)['total'];
    mysqli_stmt_close($count_stmt);
} else {
    $count_result = mysqli_query($conn, $count_query);
    $total_records = mysqli_fetch_assoc($count_result)['total'];
}

$pagination = getPagination($total_records, $page, $per_page);

// Get users
$query = "SELECT * FROM users $where_sql ORDER BY created_at DESC LIMIT ? OFFSET ?";
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
        <h2><i class="bi bi-people"></i> User Management</h2>
        <a href="create.php" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Add New User
        </a>
    </div>
    
    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" class="form-control" 
                           placeholder="Username or Email" value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Role</label>
                    <select name="role" class="form-select">
                        <option value="">All Roles</option>
                        <option value="Admin" <?php echo $role_filter === 'Admin' ? 'selected' : ''; ?>>Admin</option>
                        <option value="Finance" <?php echo $role_filter === 'Finance' ? 'selected' : ''; ?>>Finance</option>
                        <option value="Sales" <?php echo $role_filter === 'Sales' ? 'selected' : ''; ?>>Sales</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="Active" <?php echo $status_filter === 'Active' ? 'selected' : ''; ?>>Active</option>
                        <option value="Inactive" <?php echo $status_filter === 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
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
    
    <!-- Users Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($result) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td><?php echo $row['id']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($row['username']); ?></strong>
                                    <?php if ($row['id'] == $_SESSION['user_id']): ?>
                                        <span class="badge bg-info">You</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($row['email']); ?></td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo $row['role'] === 'Admin' ? 'danger' : 
                                            ($row['role'] === 'Finance' ? 'success' : 'primary'); 
                                    ?>">
                                        <?php echo $row['role']; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo getStatusBadge($row['status']); ?>">
                                        <?php echo $row['status']; ?>
                                    </span>
                                </td>
                                <td><?php echo formatDate($row['created_at']); ?></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="edit.php?id=<?php echo $row['id']; ?>" 
                                           class="btn btn-outline-primary" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <?php if ($row['id'] != $_SESSION['user_id']): ?>
                                        <a href="delete.php?id=<?php echo $row['id']; ?>" 
                                           class="btn btn-outline-danger"
                                           onclick="return confirm('Are you sure you want to delete this user?')" 
                                           title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    No users found
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
            if ($role_filter) $filter_params[] = "role=" . urlencode($role_filter);
            if ($status_filter) $filter_params[] = "status=" . urlencode($status_filter);
            $base_url = 'list.php?' . implode('&', $filter_params);
            displayPagination($pagination['total_pages'], $pagination['current_page'], $base_url); 
            ?>
        </div>
        <?php endif; ?>
    </div>
    
    <div class="mt-3">
        <p class="text-muted">
            <i class="bi bi-info-circle"></i> Total Users: <strong><?php echo $total_records; ?></strong>
        </p>
    </div>
</div>

<?php 
mysqli_stmt_close($stmt);
include '../includes/footer.php'; 
?>


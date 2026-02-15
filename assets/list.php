<?php
/**
 * Code Catalyst Labs - Asset Management
 * List all assets
 */
require_once '../includes/config.php';
require_once '../includes/functions.php';

requireLogin();
requireRole(['Admin', 'Finance']);

$page_title = 'Asset Management';

// Handle search and filters
$search = isset($_GET['search']) ? clean($_GET['search']) : '';
$category_filter = isset($_GET['category']) ? clean($_GET['category']) : '';
$condition_filter = isset($_GET['condition']) ? clean($_GET['condition']) : '';

// Build query
$where_clauses = [];
$params = [];
$param_types = '';

if ($search) {
    $where_clauses[] = "(asset_name LIKE ? OR asset_number LIKE ? OR serial_number LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $param_types .= 'sss';
}

if ($category_filter) {
    $where_clauses[] = "category = ?";
    $params[] = $category_filter;
    $param_types .= 's';
}

if ($condition_filter) {
    $where_clauses[] = "condition_status = ?";
    $params[] = $condition_filter;
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

// Get total count and value
$count_query = "SELECT COUNT(*) as total, SUM(current_value) as total_value FROM assets $where_sql";
if (count($params) > 0) {
    $count_stmt = mysqli_prepare($conn, $count_query);
    if ($param_types) {
        mysqli_stmt_bind_param($count_stmt, $param_types, ...$params);
    }
    mysqli_stmt_execute($count_stmt);
    $count_result = mysqli_stmt_get_result($count_stmt);
    $count_row = mysqli_fetch_assoc($count_result);
    $total_records = $count_row['total'];
    $total_value = $count_row['total_value'] ?? 0;
    mysqli_stmt_close($count_stmt);
} else {
    $count_result = mysqli_query($conn, $count_query);
    $count_row = mysqli_fetch_assoc($count_result);
    $total_records = $count_row['total'];
    $total_value = $count_row['total_value'] ?? 0;
}

$pagination = getPagination($total_records, $page, $per_page);

// Get assets with assigned user names
$query = "SELECT a.*, u.username as assigned_to_name 
          FROM assets a 
          LEFT JOIN users u ON a.assigned_to = u.id
          $where_sql 
          ORDER BY a.created_at DESC 
          LIMIT ? OFFSET ?";
$params[] = $per_page;
$params[] = $offset;
$param_types .= 'ii';

$stmt = mysqli_prepare($conn, $query);
if (count($params) > 0) {
    mysqli_stmt_bind_param($stmt, $param_types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Get categories for filter
$categories_query = "SELECT DISTINCT category FROM assets ORDER BY category";
$categories_result = mysqli_query($conn, $categories_query);

include '../includes/header.php';
?>

<div class="container">
    <?php displayAlert(); ?>
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-box-seam"></i> Asset Management</h2>
        <a href="create.php" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Add New Asset
        </a>
    </div>
    
    <!-- Stats Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card border-success">
                <div class="card-body">
                    <h6 class="text-success"><i class="bi bi-graph-up-arrow"></i> Total Asset Value</h6>
                    <h3 class="mb-0"><?php echo formatCurrency($total_value); ?></h3>
                    <small class="text-muted"><?php echo $total_records; ?> asset(s)</small>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-5">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" class="form-control" 
                           placeholder="Asset name, number, serial" value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Category</label>
                    <select name="category" class="form-select">
                        <option value="">All Categories</option>
                        <?php while ($cat = mysqli_fetch_assoc($categories_result)): ?>
                            <option value="<?php echo htmlspecialchars($cat['category']); ?>" 
                                    <?php echo $category_filter === $cat['category'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['category']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Condition</label>
                    <select name="condition" class="form-select">
                        <option value="">All</option>
                        <option value="Excellent" <?php echo $condition_filter === 'Excellent' ? 'selected' : ''; ?>>Excellent</option>
                        <option value="Good" <?php echo $condition_filter === 'Good' ? 'selected' : ''; ?>>Good</option>
                        <option value="Fair" <?php echo $condition_filter === 'Fair' ? 'selected' : ''; ?>>Fair</option>
                        <option value="Poor" <?php echo $condition_filter === 'Poor' ? 'selected' : ''; ?>>Poor</option>
                        <option value="Damaged" <?php echo $condition_filter === 'Damaged' ? 'selected' : ''; ?>>Damaged</option>
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
    
    <!-- Assets Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Asset #</th>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Purchase Date</th>
                            <th>Purchase Price</th>
                            <th>Current Value</th>
                            <th>Condition</th>
                            <th>Assigned To</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($result) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($row['asset_number']); ?></strong></td>
                                <td><?php echo htmlspecialchars($row['asset_name']); ?></td>
                                <td>
                                    <span class="badge bg-secondary">
                                        <?php echo htmlspecialchars($row['category']); ?>
                                    </span>
                                </td>
                                <td><?php echo formatDate($row['purchase_date']); ?></td>
                                <td><?php echo formatCurrency($row['purchase_price']); ?></td>
                                <td><strong><?php echo formatCurrency($row['current_value']); ?></strong></td>
                                <td>
                                    <?php 
                                    $condition_colors = [
                                        'Excellent' => 'success',
                                        'Good' => 'primary',
                                        'Fair' => 'warning',
                                        'Poor' => 'danger',
                                        'Damaged' => 'dark'
                                    ];
                                    $color = $condition_colors[$row['condition_status']] ?? 'secondary';
                                    ?>
                                    <span class="badge bg-<?php echo $color; ?>">
                                        <?php echo $row['condition_status']; ?>
                                    </span>
                                </td>
                                <td class="small">
                                    <?php echo $row['assigned_to_name'] ? htmlspecialchars($row['assigned_to_name']) : '-'; ?>
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
                                        <a href="delete.php?id=<?php echo $row['id']; ?>" 
                                           class="btn btn-outline-danger"
                                           onclick="return confirm('Are you sure you want to delete this asset?')" 
                                           title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">
                                    No assets found
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
            if ($category_filter) $filter_params[] = "category=" . urlencode($category_filter);
            if ($condition_filter) $filter_params[] = "condition=" . urlencode($condition_filter);
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


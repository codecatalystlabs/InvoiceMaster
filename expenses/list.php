<?php
/**
 * Code Catalyst Labs - Expense Management
 * List all expenses
 */
require_once '../includes/config.php';
require_once '../includes/functions.php';

requireLogin();
requireRole(['Admin', 'Finance']);

$page_title = 'Expense Management';

// Handle search and filters
$search = isset($_GET['search']) ? clean($_GET['search']) : '';
$category_filter = isset($_GET['category']) ? clean($_GET['category']) : '';
$status_filter = isset($_GET['status']) ? clean($_GET['status']) : '';
$date_from = isset($_GET['date_from']) ? clean($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? clean($_GET['date_to']) : '';
$is_recurring = isset($_GET['is_recurring']) ? clean($_GET['is_recurring']) : '';

// Build query
$where_clauses = [];
$params = [];
$param_types = '';

if ($search) {
    $where_clauses[] = "(e.vendor_name LIKE ? OR e.expense_number LIKE ? OR e.description LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $param_types .= 'sss';
}

if ($category_filter) {
    $where_clauses[] = "e.category = ?";
    $params[] = $category_filter;
    $param_types .= 's';
}

if ($status_filter) {
    $where_clauses[] = "e.payment_status = ?";
    $params[] = $status_filter;
    $param_types .= 's';
}

if ($is_recurring !== '') {
    $where_clauses[] = "e.is_recurring = ?";
    $params[] = $is_recurring;
    $param_types .= 's';
}

if ($date_from) {
    $where_clauses[] = "e.expense_date >= ?";
    $params[] = $date_from;
    $param_types .= 's';
}

if ($date_to) {
    $where_clauses[] = "e.expense_date <= ?";
    $params[] = $date_to;
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

// Get total count and sum
$count_query = "SELECT COUNT(*) as total, SUM(e.amount) as total_amount FROM expenses e $where_sql";
if (count($params) > 0) {
    $count_stmt = mysqli_prepare($conn, $count_query);
    if ($param_types) {
        mysqli_stmt_bind_param($count_stmt, $param_types, ...$params);
    }
    mysqli_stmt_execute($count_stmt);
    $count_result = mysqli_stmt_get_result($count_stmt);
    $count_row = mysqli_fetch_assoc($count_result);
    $total_records = $count_row['total'];
    $total_amount = $count_row['total_amount'] ?? 0;
    mysqli_stmt_close($count_stmt);
} else {
    $count_result = mysqli_query($conn, $count_query);
    $count_row = mysqli_fetch_assoc($count_result);
    $total_records = $count_row['total'];
    $total_amount = $count_row['total_amount'] ?? 0;
}

$pagination = getPagination($total_records, $page, $per_page);

// Get expenses with account names
$query = "SELECT e.*, c.account_name, u.username as creator 
          FROM expenses e 
          LEFT JOIN chart_of_accounts c ON e.account_id = c.id
          LEFT JOIN users u ON e.created_by = u.id
          $where_sql 
          ORDER BY e.expense_date DESC, e.created_at DESC 
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
$categories_query = "SELECT DISTINCT category FROM expenses ORDER BY category";
$categories_result = mysqli_query($conn, $categories_query);

include '../includes/header.php';
?>

<div class="container">
    <?php displayAlert(); ?>
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-receipt-cutoff"></i> Expense Management</h2>
        <a href="create.php" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Add New Expense
        </a>
    </div>
    
    <!-- Stats Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-danger">
                <div class="card-body">
                    <h6 class="text-danger"><i class="bi bi-cash-stack"></i> Total Expenses</h6>
                    <h3 class="mb-0"><?php echo formatCurrency($total_amount); ?></h3>
                    <small class="text-muted"><?php echo $total_records; ?> expense(s)</small>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" class="form-control" 
                           placeholder="Vendor, number, description" value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-2">
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
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="Pending" <?php echo $status_filter === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="Paid" <?php echo $status_filter === 'Paid' ? 'selected' : ''; ?>>Paid</option>
                        <option value="Partially Paid" <?php echo $status_filter === 'Partially Paid' ? 'selected' : ''; ?>>Partially Paid</option>
                        <option value="Overdue" <?php echo $status_filter === 'Overdue' ? 'selected' : ''; ?>>Overdue</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Type</label>
                    <select name="is_recurring" class="form-select">
                        <option value="">All Types</option>
                        <option value="1" <?php echo $is_recurring === '1' ? 'selected' : ''; ?>>Recurring</option>
                        <option value="0" <?php echo $is_recurring === '0' ? 'selected' : ''; ?>>One-time</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Date Range</label>
                    <div class="input-group">
                        <input type="date" name="date_from" class="form-control" value="<?php echo htmlspecialchars($date_from); ?>">
                        <input type="date" name="date_to" class="form-control" value="<?php echo htmlspecialchars($date_to); ?>">
                    </div>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search"></i> Filter
                    </button>
                    <a href="list.php" class="btn btn-secondary">
                        <i class="bi bi-x-circle"></i> Clear
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Expenses Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Expense #</th>
                            <th>Date</th>
                            <th>Vendor</th>
                            <th>Category</th>
                            <th>Account</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Type</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($result) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($row['expense_number']); ?></strong>
                                </td>
                                <td><?php echo formatDate($row['expense_date']); ?></td>
                                <td><?php echo htmlspecialchars($row['vendor_name']); ?></td>
                                <td>
                                    <span class="badge bg-secondary">
                                        <?php echo htmlspecialchars($row['category']); ?>
                                    </span>
                                </td>
                                <td class="small"><?php echo htmlspecialchars($row['account_name'] ?? 'N/A'); ?></td>
                                <td><strong><?php echo formatCurrency($row['amount']); ?></strong></td>
                                <td>
                                    <span class="badge bg-<?php echo getStatusBadge($row['payment_status']); ?>">
                                        <?php echo $row['payment_status']; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($row['is_recurring']): ?>
                                        <span class="badge bg-info" title="<?php echo $row['recurrence_frequency']; ?>">
                                            <i class="bi bi-arrow-repeat"></i> Recurring
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">One-time</span>
                                    <?php endif; ?>
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
                                           onclick="return confirm('Are you sure you want to delete this expense?')" 
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
                                    No expenses found
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
            if ($status_filter) $filter_params[] = "status=" . urlencode($status_filter);
            if ($is_recurring !== '') $filter_params[] = "is_recurring=" . urlencode($is_recurring);
            if ($date_from) $filter_params[] = "date_from=" . urlencode($date_from);
            if ($date_to) $filter_params[] = "date_to=" . urlencode($date_to);
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


<?php
/**
 * Code Catalyst Labs - Invoices List
 */
require_once '../includes/config.php';
require_once '../includes/functions.php';

requireLogin();

$page_title = 'Invoices';

// Handle delete
if (isset($_GET['delete']) && hasRole(['Admin', 'Finance'])) {
    $id = (int)$_GET['delete'];
    
    $query = "DELETE FROM invoices WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $id);
    
    if (mysqli_stmt_execute($stmt)) {
        logAudit($conn, 'Delete', 'Invoice', $id, 'Invoice deleted');
        $_SESSION['success'] = 'Invoice deleted successfully!';
    } else {
        $_SESSION['error'] = 'Failed to delete invoice.';
    }
    
    mysqli_stmt_close($stmt);
    header('Location: list.php');
    exit();
}

// Pagination and filters
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = ITEMS_PER_PAGE;
$search = isset($_GET['search']) ? clean($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? clean($_GET['status']) : '';

// Count total records
$count_query = "SELECT COUNT(*) as total FROM invoices i LEFT JOIN clients c ON i.client_id = c.id WHERE 1=1";
if ($search) {
    $count_query .= " AND (i.invoice_number LIKE '%$search%' OR c.name LIKE '%$search%')";
}
if ($status_filter) {
    $count_query .= " AND i.status = '$status_filter'";
}
$count_result = mysqli_query($conn, $count_query);
$total_records = mysqli_fetch_assoc($count_result)['total'];

$pagination = getPagination($total_records, $page, $per_page);

// Get invoices
$query = "SELECT i.*, c.name as client_name 
          FROM invoices i 
          LEFT JOIN clients c ON i.client_id = c.id 
          WHERE 1=1";
if ($search) {
    $query .= " AND (i.invoice_number LIKE '%$search%' OR c.name LIKE '%$search%')";
}
if ($status_filter) {
    $query .= " AND i.status = '$status_filter'";
}
$query .= " ORDER BY i.created_at DESC LIMIT {$pagination['offset']}, $per_page";
$result = mysqli_query($conn, $query);

include '../includes/header.php';
?>

<div class="container">
    <?php displayAlert(); ?>
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-receipt"></i> Invoices</h2>
        <div class="d-flex gap-2">
            <a href="<?php echo APP_URL; ?>/export.php?type=invoices" class="btn btn-outline-success">
                <i class="bi bi-filetype-csv"></i> Export CSV
            </a>
            <?php if (hasRole(['Admin', 'Finance', 'Sales'])): ?>
            <a href="create.php" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Create Invoice
            </a>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="" class="row g-3">
                <div class="col-md-5">
                    <input type="text" class="form-control" name="search" 
                           placeholder="Search by invoice number or client..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-3">
                    <select class="form-select" name="status">
                        <option value="">All Status</option>
                        <option value="Unpaid" <?php echo $status_filter === 'Unpaid' ? 'selected' : ''; ?>>Unpaid</option>
                        <option value="Partially Paid" <?php echo $status_filter === 'Partially Paid' ? 'selected' : ''; ?>>Partially Paid</option>
                        <option value="Paid" <?php echo $status_filter === 'Paid' ? 'selected' : ''; ?>>Paid</option>
                        <option value="Overdue" <?php echo $status_filter === 'Overdue' ? 'selected' : ''; ?>>Overdue</option>
                        <option value="Cancelled" <?php echo $status_filter === 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search"></i> Search
                    </button>
                </div>
                <div class="col-md-2">
                    <a href="list.php" class="btn btn-secondary w-100">
                        <i class="bi bi-arrow-clockwise"></i> Reset
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Invoices Table -->
    <div class="card">
        <div class="card-body">
            <?php if (mysqli_num_rows($result) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Invoice #</th>
                                <th>Client</th>
                                <th>Date</th>
                                <th>Due Date</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                            <?php
                            // Check if overdue
                            if ($row['status'] === 'Unpaid' && strtotime($row['due_date']) < time()) {
                                $update_query = "UPDATE invoices SET status = 'Overdue' WHERE id = {$row['id']}";
                                mysqli_query($conn, $update_query);
                                $row['status'] = 'Overdue';
                            }
                            ?>
                            <tr>
                                <td>
                                    <a href="view.php?id=<?php echo $row['id']; ?>">
                                        <?php echo $row['invoice_number']; ?>
                                    </a>
                                </td>
                                <td><?php echo htmlspecialchars($row['client_name']); ?></td>
                                <td><?php echo formatDate($row['date']); ?></td>
                                <td><?php echo formatDate($row['due_date']); ?></td>
                                <td><?php echo formatCurrency($row['total']); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo getStatusBadge($row['status']); ?>">
                                        <?php echo $row['status']; ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="view.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-info" title="View">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <?php if (hasRole(['Admin', 'Finance'])): ?>
                                    <a href="edit.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-warning" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <a href="?delete=<?php echo $row['id']; ?>" 
                                       class="btn btn-sm btn-danger" 
                                       onclick="return confirm('Are you sure you want to delete this invoice?')"
                                       title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php displayPagination($pagination['total_pages'], $pagination['current_page'], '?search=' . urlencode($search) . '&status=' . urlencode($status_filter)); ?>
            <?php else: ?>
                <p class="text-muted text-center py-4">No invoices found.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>


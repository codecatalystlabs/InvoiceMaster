<?php
/**
 * Code Catalyst Labs - Quotations List
 */
require_once '../includes/config.php';
require_once '../includes/functions.php';

requireLogin();

$page_title = 'Quotations';

// Handle delete
if (isset($_GET['delete']) && hasRole(['Admin', 'Sales'])) {
    $id = (int)$_GET['delete'];
    
    $query = "DELETE FROM quotations WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $id);
    
    if (mysqli_stmt_execute($stmt)) {
        logAudit($conn, 'Delete', 'Quotation', $id, 'Quotation deleted');
        $_SESSION['success'] = 'Quotation deleted successfully!';
    } else {
        $_SESSION['error'] = 'Failed to delete quotation.';
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
$count_query = "SELECT COUNT(*) as total FROM quotations q LEFT JOIN clients c ON q.client_id = c.id WHERE 1=1";
if ($search) {
    $count_query .= " AND (q.quotation_number LIKE '%$search%' OR c.name LIKE '%$search%')";
}
if ($status_filter) {
    $count_query .= " AND q.status = '$status_filter'";
}
$count_result = mysqli_query($conn, $count_query);
$total_records = mysqli_fetch_assoc($count_result)['total'];

$pagination = getPagination($total_records, $page, $per_page);

// Get quotations
$query = "SELECT q.*, c.name as client_name 
          FROM quotations q 
          LEFT JOIN clients c ON q.client_id = c.id 
          WHERE 1=1";
if ($search) {
    $query .= " AND (q.quotation_number LIKE '%$search%' OR c.name LIKE '%$search%')";
}
if ($status_filter) {
    $query .= " AND q.status = '$status_filter'";
}
$query .= " ORDER BY q.created_at DESC LIMIT {$pagination['offset']}, $per_page";
$result = mysqli_query($conn, $query);

include '../includes/header.php';
?>

<div class="container">
    <?php displayAlert(); ?>
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-file-earmark-text"></i> Quotations</h2>
        <?php if (hasRole(['Admin', 'Sales'])): ?>
        <a href="create.php" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Create Quotation
        </a>
        <?php endif; ?>
    </div>
    
    <!-- Filters -->
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="" class="row g-3">
                <div class="col-md-5">
                    <input type="text" class="form-control" name="search" 
                           placeholder="Search by quotation number or client..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-3">
                    <select class="form-select" name="status">
                        <option value="">All Status</option>
                        <option value="Draft" <?php echo $status_filter === 'Draft' ? 'selected' : ''; ?>>Draft</option>
                        <option value="Sent" <?php echo $status_filter === 'Sent' ? 'selected' : ''; ?>>Sent</option>
                        <option value="Accepted" <?php echo $status_filter === 'Accepted' ? 'selected' : ''; ?>>Accepted</option>
                        <option value="Rejected" <?php echo $status_filter === 'Rejected' ? 'selected' : ''; ?>>Rejected</option>
                        <option value="Converted" <?php echo $status_filter === 'Converted' ? 'selected' : ''; ?>>Converted</option>
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
    
    <!-- Quotations Table -->
    <div class="card">
        <div class="card-body">
            <?php if (mysqli_num_rows($result) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Quotation #</th>
                                <th>Client</th>
                                <th>Date</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td>
                                    <a href="view.php?id=<?php echo $row['id']; ?>">
                                        <?php echo $row['quotation_number']; ?>
                                    </a>
                                </td>
                                <td><?php echo htmlspecialchars($row['client_name']); ?></td>
                                <td><?php echo formatDate($row['date']); ?></td>
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
                                    <?php if (hasRole(['Admin', 'Sales']) && $row['status'] !== 'Converted'): ?>
                                    <a href="edit.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-warning" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <a href="?delete=<?php echo $row['id']; ?>" 
                                       class="btn btn-sm btn-danger" 
                                       onclick="return confirm('Are you sure you want to delete this quotation?')"
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
                <p class="text-muted text-center py-4">No quotations found.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>


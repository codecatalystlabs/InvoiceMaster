<?php
/**
 * Code Catalyst Labs - Clients List
 */
require_once '../includes/config.php';
require_once '../includes/functions.php';

requireLogin();

$page_title = 'Clients';

// Handle delete
if (isset($_GET['delete']) && hasRole(['Admin', 'Sales'])) {
    $id = (int)$_GET['delete'];
    
    $query = "DELETE FROM clients WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $id);
    
    if (mysqli_stmt_execute($stmt)) {
        logAudit($conn, 'Delete', 'Client', $id, 'Client deleted');
        $_SESSION['success'] = 'Client deleted successfully!';
    } else {
        $_SESSION['error'] = 'Failed to delete client.';
    }
    
    mysqli_stmt_close($stmt);
    header('Location: list.php');
    exit();
}

// Pagination and search
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = ITEMS_PER_PAGE;
$search = isset($_GET['search']) ? clean($_GET['search']) : '';

// Count total records
$count_query = "SELECT COUNT(*) as total FROM clients";
if ($search) {
    $count_query .= " WHERE name LIKE '%$search%' OR email LIKE '%$search%' OR company LIKE '%$search%'";
}
$count_result = mysqli_query($conn, $count_query);
$total_records = mysqli_fetch_assoc($count_result)['total'];

$pagination = getPagination($total_records, $page, $per_page);

// Get clients
$query = "SELECT * FROM clients";
if ($search) {
    $query .= " WHERE name LIKE '%$search%' OR email LIKE '%$search%' OR company LIKE '%$search%'";
}
$query .= " ORDER BY name ASC LIMIT {$pagination['offset']}, $per_page";
$result = mysqli_query($conn, $query);

include '../includes/header.php';
?>

<div class="container">
    <?php displayAlert(); ?>
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-people"></i> Clients</h2>
        <?php if (hasRole(['Admin', 'Sales'])): ?>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addClientModal">
            <i class="bi bi-plus-circle"></i> Add Client
        </button>
        <?php endif; ?>
    </div>
    
    <!-- Search -->
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="" class="row g-3">
                <div class="col-md-10">
                    <input type="text" class="form-control" name="search" 
                           placeholder="Search by name, email, or company..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search"></i> Search
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Clients Table -->
    <div class="card">
        <div class="card-body">
            <?php if (mysqli_num_rows($result) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Company</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['name']); ?></td>
                                <td><?php echo htmlspecialchars($row['email']); ?></td>
                                <td><?php echo htmlspecialchars($row['phone']); ?></td>
                                <td><?php echo htmlspecialchars($row['company']); ?></td>
                                <td><?php echo formatDate($row['created_at']); ?></td>
                                <td>
                                    <?php if (hasRole(['Admin', 'Sales'])): ?>
                                    <button type="button" class="btn btn-sm btn-warning" 
                                            onclick="editClient(<?php echo htmlspecialchars(json_encode($row)); ?>)">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <a href="?delete=<?php echo $row['id']; ?>" 
                                       class="btn btn-sm btn-danger" 
                                       onclick="return confirm('Are you sure you want to delete this client?')">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php displayPagination($pagination['total_pages'], $pagination['current_page'], '?search=' . urlencode($search)); ?>
            <?php else: ?>
                <p class="text-muted text-center py-4">No clients found.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Add Client Modal -->
<div class="modal fade" id="addClientModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="actions.php">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Client</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="mb-3">
                        <label for="name" class="form-label">Name *</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email">
                    </div>
                    
                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone</label>
                        <input type="text" class="form-control" id="phone" name="phone">
                    </div>
                    
                    <div class="mb-3">
                        <label for="company" class="form-label">Company</label>
                        <input type="text" class="form-control" id="company" name="company">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Client</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Client Modal -->
<div class="modal fade" id="editClientModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="actions.php">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Client</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" id="edit_id" name="id">
                    
                    <div class="mb-3">
                        <label for="edit_name" class="form-label">Name *</label>
                        <input type="text" class="form-control" id="edit_name" name="name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="edit_email" name="email">
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_phone" class="form-label">Phone</label>
                        <input type="text" class="form-control" id="edit_phone" name="phone">
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_company" class="form-label">Company</label>
                        <input type="text" class="form-control" id="edit_company" name="company">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Client</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editClient(client) {
    document.getElementById('edit_id').value = client.id;
    document.getElementById('edit_name').value = client.name;
    document.getElementById('edit_email').value = client.email || '';
    document.getElementById('edit_phone').value = client.phone || '';
    document.getElementById('edit_company').value = client.company || '';
    
    var modal = new bootstrap.Modal(document.getElementById('editClientModal'));
    modal.show();
}
</script>

<?php include '../includes/footer.php'; ?>


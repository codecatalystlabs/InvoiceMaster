<?php
/**
 * Code Catalyst Labs - Email List
 */
require_once '../includes/config.php';
require_once '../includes/functions.php';

requireLogin();

// Get filters
$filter_direction = isset($_GET['direction']) ? clean($_GET['direction']) : '';
$filter_reference = isset($_GET['reference']) ? clean($_GET['reference']) : '';
$filter_status = isset($_GET['status']) ? clean($_GET['status']) : '';
$search = isset($_GET['search']) ? clean($_GET['search']) : '';

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = ITEMS_PER_PAGE;
$offset = ($page - 1) * $per_page;

// Build query
$where_clauses = [];
$params = [];
$types = '';

if ($filter_direction) {
    $where_clauses[] = "e.direction = ?";
    $params[] = $filter_direction;
    $types .= 's';
}

if ($filter_reference) {
    $where_clauses[] = "e.reference_type = ?";
    $params[] = $filter_reference;
    $types .= 's';
}

if ($filter_status) {
    $where_clauses[] = "e.status = ?";
    $params[] = $filter_status;
    $types .= 's';
}

if ($search) {
    $where_clauses[] = "(e.subject LIKE ? OR e.to_email LIKE ? OR e.from_email LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'sss';
}

$where_sql = !empty($where_clauses) ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

// Get total count
$count_query = "SELECT COUNT(*) as total FROM emails e $where_sql";
$count_stmt = mysqli_prepare($conn, $count_query);
if (!empty($params)) {
    mysqli_stmt_bind_param($count_stmt, $types, ...$params);
}
mysqli_stmt_execute($count_stmt);
$total = mysqli_fetch_assoc(mysqli_stmt_get_result($count_stmt))['total'];

// Get emails
$query = "SELECT e.*, u.username as sent_by_name
          FROM emails e
          LEFT JOIN users u ON e.sent_by = u.id
          $where_sql
          ORDER BY e.sent_at DESC
          LIMIT ? OFFSET ?";

$stmt = mysqli_prepare($conn, $query);
$params[] = $per_page;
$params[] = $offset;
$types .= 'ii';

if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$emails = mysqli_stmt_get_result($stmt);

$total_pages = ceil($total / $per_page);

include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-envelope"></i> Emails</h2>
        <div>
            <?php if (IMAP_ENABLED): ?>
            <a href="sync.php" class="btn btn-info">
                <i class="bi bi-arrow-repeat"></i> Check for New Emails
            </a>
            <?php endif; ?>
            <a href="compose.php" class="btn btn-primary">
                <i class="bi bi-pencil"></i> Compose Email
            </a>
        </div>
    </div>
    
    <?php displayAlert(); ?>
    
    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Direction</label>
                    <select name="direction" class="form-select">
                        <option value="">All</option>
                        <option value="outgoing" <?php echo $filter_direction === 'outgoing' ? 'selected' : ''; ?>>Outgoing</option>
                        <option value="incoming" <?php echo $filter_direction === 'incoming' ? 'selected' : ''; ?>>Incoming</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Reference</label>
                    <select name="reference" class="form-select">
                        <option value="">All</option>
                        <option value="quotation" <?php echo $filter_reference === 'quotation' ? 'selected' : ''; ?>>Quotations</option>
                        <option value="invoice" <?php echo $filter_reference === 'invoice' ? 'selected' : ''; ?>>Invoices</option>
                        <option value="general" <?php echo $filter_reference === 'general' ? 'selected' : ''; ?>>General</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All</option>
                        <option value="sent" <?php echo $filter_status === 'sent' ? 'selected' : ''; ?>>Sent</option>
                        <option value="received" <?php echo $filter_status === 'received' ? 'selected' : ''; ?>>Received</option>
                        <option value="read" <?php echo $filter_status === 'read' ? 'selected' : ''; ?>>Read</option>
                        <option value="failed" <?php echo $filter_status === 'failed' ? 'selected' : ''; ?>>Failed</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" class="form-control" placeholder="Subject, recipient..." value="<?php echo htmlspecialchars($search); ?>">
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
    
    <!-- Email List -->
    <div class="card">
        <div class="card-body">
            <?php if (mysqli_num_rows($emails) > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th width="5%"></th>
                            <th width="10%">Direction</th>
                            <th width="20%">From/To</th>
                            <th width="35%">Subject</th>
                            <th width="10%">Reference</th>
                            <th width="10%">Status</th>
                            <th width="15%">Date</th>
                            <th width="5%">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($email = mysqli_fetch_assoc($emails)): ?>
                        <tr class="<?php echo $email['direction'] === 'incoming' && $email['status'] === 'received' ? 'table-info' : ''; ?>">
                            <td>
                                <?php if ($email['direction'] === 'outgoing'): ?>
                                    <i class="bi bi-arrow-up-right text-primary"></i>
                                <?php else: ?>
                                    <i class="bi bi-arrow-down-left text-success"></i>
                                <?php endif; ?>
                                <?php if ($email['has_attachment']): ?>
                                    <i class="bi bi-paperclip"></i>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($email['direction'] === 'outgoing'): ?>
                                    <span class="badge bg-primary">Outgoing</span>
                                <?php else: ?>
                                    <span class="badge bg-success">Incoming</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($email['direction'] === 'outgoing'): ?>
                                    <small class="text-muted">To:</small><br>
                                    <?php echo htmlspecialchars($email['to_email']); ?>
                                <?php else: ?>
                                    <small class="text-muted">From:</small><br>
                                    <?php echo htmlspecialchars($email['from_name'] ?: $email['from_email']); ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="view.php?id=<?php echo $email['id']; ?>" class="text-decoration-none">
                                    <?php echo htmlspecialchars($email['subject']); ?>
                                </a>
                            </td>
                            <td>
                                <?php if ($email['reference_id']): ?>
                                    <?php if ($email['reference_type'] === 'quotation'): ?>
                                        <a href="../quotations/view.php?id=<?php echo $email['reference_id']; ?>">
                                            <span class="badge bg-info">Quotation</span>
                                        </a>
                                    <?php elseif ($email['reference_type'] === 'invoice'): ?>
                                        <a href="../invoices/view.php?id=<?php echo $email['reference_id']; ?>">
                                            <span class="badge bg-warning">Invoice</span>
                                        </a>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="badge bg-secondary">General</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                $badge_class = match($email['status']) {
                                    'sent' => 'bg-success',
                                    'received' => 'bg-info',
                                    'read' => 'bg-secondary',
                                    'failed' => 'bg-danger',
                                    default => 'bg-secondary'
                                };
                                ?>
                                <span class="badge <?php echo $badge_class; ?>"><?php echo ucfirst($email['status']); ?></span>
                            </td>
                            <td>
                                <small><?php echo date('M d, Y H:i', strtotime($email['sent_at'])); ?></small>
                            </td>
                            <td>
                                <a href="view.php?id=<?php echo $email['id']; ?>" class="btn btn-sm btn-primary" title="View">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <nav aria-label="Page navigation" class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php
                    $base_url = "list.php?direction=$filter_direction&reference=$filter_reference&status=$filter_status&search=$search";
                    for ($i = 1; $i <= $total_pages; $i++):
                        $active = $i === $page ? 'active' : '';
                    ?>
                    <li class="page-item <?php echo $active; ?>">
                        <a class="page-link" href="<?php echo $base_url; ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                    </li>
                    <?php endfor; ?>
                </ul>
            </nav>
            <?php endif; ?>
            
            <?php else: ?>
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i> No emails found.
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>


<?php
/**
 * Code Catalyst Labs - Cashbook
 */
require_once '../includes/config.php';
require_once '../includes/functions.php';

requireLogin();
requireRole(['Admin', 'Finance']);

$page_title = 'Cashbook';

// Handle filters
$date_from = isset($_GET['date_from']) && !empty($_GET['date_from']) ? clean($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) && !empty($_GET['date_to']) ? clean($_GET['date_to']) : '';
$transaction_type = isset($_GET['transaction_type']) ? clean($_GET['transaction_type']) : '';

// Build query
$where_clauses = [];
$params = [];
$param_types = '';

// Only filter by date if dates are provided
if ($date_from && $date_to) {
    $where_clauses[] = "transaction_date BETWEEN ? AND ?";
    $params[] = $date_from;
    $params[] = $date_to;
    $param_types .= 'ss';
} elseif ($date_from) {
    $where_clauses[] = "transaction_date >= ?";
    $params[] = $date_from;
    $param_types .= 's';
} elseif ($date_to) {
    $where_clauses[] = "transaction_date <= ?";
    $params[] = $date_to;
    $param_types .= 's';
}

if ($transaction_type) {
    $where_clauses[] = "transaction_type = ?";
    $params[] = $transaction_type;
    $param_types .= 's';
}

$where_sql = count($where_clauses) > 0 ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

// Get transactions
$query = "SELECT c.*, a.account_name, a.account_code 
          FROM cashbook c
          LEFT JOIN chart_of_accounts a ON c.account_id = a.id
          $where_sql
          ORDER BY c.transaction_date DESC, c.created_at DESC";

if (count($params) > 0) {
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, $param_types, ...$params);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
} else {
    $result = mysqli_query($conn, $query);
}

// Calculate totals
$total_income = 0;
$total_expense = 0;

$transactions = [];
while ($row = mysqli_fetch_assoc($result)) {
    $transactions[] = $row;
    if ($row['transaction_type'] === 'Income') {
        $total_income += $row['amount'];
    } elseif ($row['transaction_type'] === 'Expense') {
        $total_expense += $row['amount'];
    }
}

$net_cashflow = $total_income - $total_expense;

// Close statement if it was prepared
if (count($params) > 0 && isset($stmt)) {
    mysqli_stmt_close($stmt);
}

include '../includes/header.php';
?>

<div class="container">
    <?php displayAlert(); ?>
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-cash-stack"></i> Cashbook</h2>
        <div class="d-flex gap-2">
            <a href="<?php echo APP_URL; ?>/export.php?type=cashbook" class="btn btn-outline-success">
                <i class="bi bi-filetype-csv"></i> Export CSV
            </a>
            <a href="cashbook_entry.php" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> New Entry
            </a>
        </div>
    </div>
    
    <!-- Summary Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-success">
                <div class="card-body">
                    <h6 class="text-success"><i class="bi bi-arrow-down-circle"></i> Total Income</h6>
                    <h3 class="mb-0 text-success"><?php echo formatCurrency($total_income); ?></h3>
                    <?php if ($date_from || $date_to): ?>
                        <small class="text-muted">Filtered period</small>
                    <?php else: ?>
                        <small class="text-muted">All time</small>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-danger">
                <div class="card-body">
                    <h6 class="text-danger"><i class="bi bi-arrow-up-circle"></i> Total Expenses</h6>
                    <h3 class="mb-0 text-danger"><?php echo formatCurrency($total_expense); ?></h3>
                    <?php if ($date_from || $date_to): ?>
                        <small class="text-muted">Filtered period</small>
                    <?php else: ?>
                        <small class="text-muted">All time</small>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-primary">
                <div class="card-body">
                    <h6 class="text-primary"><i class="bi bi-graph-up"></i> Net Cashflow</h6>
                    <h3 class="mb-0 <?php echo $net_cashflow >= 0 ? 'text-success' : 'text-danger'; ?>">
                        <?php echo formatCurrency($net_cashflow); ?>
                    </h3>
                    <?php if ($date_from || $date_to): ?>
                        <small class="text-muted">Filtered period</small>
                    <?php else: ?>
                        <small class="text-muted">All time</small>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">From Date</label>
                    <input type="date" name="date_from" class="form-control" value="<?php echo htmlspecialchars($date_from); ?>" placeholder="Optional">
                </div>
                <div class="col-md-3">
                    <label class="form-label">To Date</label>
                    <input type="date" name="date_to" class="form-control" value="<?php echo htmlspecialchars($date_to); ?>" placeholder="Optional">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Type</label>
                    <select name="transaction_type" class="form-select">
                        <option value="">All Types</option>
                        <option value="Income" <?php echo $transaction_type === 'Income' ? 'selected' : ''; ?>>Income</option>
                        <option value="Expense" <?php echo $transaction_type === 'Expense' ? 'selected' : ''; ?>>Expense</option>
                        <option value="Transfer" <?php echo $transaction_type === 'Transfer' ? 'selected' : ''; ?>>Transfer</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search"></i> Filter
                    </button>
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <a href="cashbook.php" class="btn btn-secondary w-100">
                        <i class="bi bi-x-circle"></i> Clear
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Transactions Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Reference</th>
                            <th>Account</th>
                            <th>Description</th>
                            <th>Type</th>
                            <th>Payment Method</th>
                            <th class="text-end">Amount</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($transactions) > 0): ?>
                            <?php foreach ($transactions as $row): ?>
                            <tr>
                                <td><?php echo formatDate($row['transaction_date']); ?></td>
                                <td><code><?php echo htmlspecialchars($row['reference_number']); ?></code></td>
                                <td class="small">
                                    <?php echo htmlspecialchars($row['account_code'] . ' - ' . $row['account_name']); ?>
                                </td>
                                <td><?php echo htmlspecialchars($row['description']); ?></td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo $row['transaction_type'] === 'Income' ? 'success' : 
                                            ($row['transaction_type'] === 'Expense' ? 'danger' : 'info'); 
                                    ?>">
                                        <?php echo $row['transaction_type']; ?>
                                    </span>
                                </td>
                                <td class="small"><?php echo htmlspecialchars($row['payment_method']); ?></td>
                                <td class="text-end">
                                    <strong class="<?php echo $row['transaction_type'] === 'Income' ? 'text-success' : 'text-danger'; ?>">
                                        <?php echo $row['transaction_type'] === 'Income' ? '+' : '-'; ?>
                                        <?php echo formatCurrency($row['amount']); ?>
                                    </strong>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="edit_cashbook.php?id=<?php echo $row['id']; ?>" 
                                           class="btn btn-outline-primary" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="delete_cashbook.php?id=<?php echo $row['id']; ?>" 
                                           class="btn btn-outline-danger"
                                           onclick="return confirm('Are you sure you want to delete this cashbook entry?')" 
                                           title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">
                                    No transactions found for the selected period
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>


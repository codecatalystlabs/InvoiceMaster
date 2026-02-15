<?php
/**
 * Code Catalyst Labs - General Ledger Report
 */
require_once '../includes/config.php';
require_once '../includes/functions.php';

requireLogin();
requireRole(['Admin', 'Finance']);

$page_title = 'General Ledger';

// Date filters
$date_from = isset($_GET['date_from']) ? clean($_GET['date_from']) : date('Y-m-01');
$date_to = isset($_GET['date_to']) ? clean($_GET['date_to']) : date('Y-m-d');
$account_filter = isset($_GET['account_id']) ? intval($_GET['account_id']) : 0;

// Get all accounts for filter
$accounts_query = "SELECT id, account_code, account_name FROM chart_of_accounts WHERE is_active = TRUE ORDER BY account_code";
$accounts_result = mysqli_query($conn, $accounts_query);

// Build query for ledger entries
$where_clauses = ["entry_date BETWEEN ? AND ?"];
$params = [$date_from, $date_to];
$param_types = 'ss';

if ($account_filter > 0) {
    $where_clauses[] = "account_id = ?";
    $params[] = $account_filter;
    $param_types .= 'i';
}

$where_sql = 'WHERE ' . implode(' AND ', $where_clauses);

// Get ledger entries
$query = "SELECT l.*, a.account_code, a.account_name, a.account_type
          FROM ledger_entries l
          LEFT JOIN chart_of_accounts a ON l.account_id = a.id
          $where_sql
          ORDER BY l.entry_date DESC, l.created_at DESC";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, $param_types, ...$params);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$total_debit = 0;
$total_credit = 0;

$entries = [];
while ($row = mysqli_fetch_assoc($result)) {
    $entries[] = $row;
    if ($row['entry_type'] === 'Debit') {
        $total_debit += $row['amount'];
    } else {
        $total_credit += $row['amount'];
    }
}

mysqli_stmt_close($stmt);

include '../includes/header.php';
?>

<div class="container">
    <?php displayAlert(); ?>
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-book"></i> General Ledger</h2>
        <button onclick="window.print()" class="btn btn-secondary">
            <i class="bi bi-printer"></i> Print Report
        </button>
    </div>
    
    <!-- Filters -->
    <div class="card mb-4 no-print">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">From Date</label>
                    <input type="date" name="date_from" class="form-control" value="<?php echo $date_from; ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">To Date</label>
                    <input type="date" name="date_to" class="form-control" value="<?php echo $date_to; ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Account</label>
                    <select name="account_id" class="form-select">
                        <option value="0">All Accounts</option>
                        <?php 
                        mysqli_data_seek($accounts_result, 0);
                        while ($account = mysqli_fetch_assoc($accounts_result)): 
                        ?>
                            <option value="<?php echo $account['id']; ?>" 
                                    <?php echo $account_filter == $account['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($account['account_code'] . ' - ' . $account['account_name']); ?>
                            </option>
                        <?php endwhile; ?>
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
    
    <!-- Summary -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-primary">
                <div class="card-body text-center">
                    <h6 class="text-primary">Total Debits</h6>
                    <h4><?php echo formatCurrency($total_debit); ?></h4>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-success">
                <div class="card-body text-center">
                    <h6 class="text-success">Total Credits</h6>
                    <h4><?php echo formatCurrency($total_credit); ?></h4>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-info">
                <div class="card-body text-center">
                    <h6 class="text-info">Balance</h6>
                    <h4 class="<?php echo ($total_debit - $total_credit) >= 0 ? 'text-success' : 'text-danger'; ?>">
                        <?php echo formatCurrency(abs($total_debit - $total_credit)); ?>
                    </h4>
                    <small class="text-muted">
                        <?php echo ($total_debit - $total_credit) >= 0 ? 'Debit' : 'Credit'; ?>
                    </small>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Ledger Entries -->
    <div class="card">
        <div class="card-header bg-light">
            <h5 class="mb-0">Ledger Entries</h5>
            <small>Period: <?php echo formatDate($date_from); ?> to <?php echo formatDate($date_to); ?></small>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover table-sm">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Reference</th>
                            <th>Account</th>
                            <th>Description</th>
                            <th>Source</th>
                            <th class="text-end">Debit</th>
                            <th class="text-end">Credit</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($entries) > 0): ?>
                            <?php foreach ($entries as $entry): ?>
                            <tr>
                                <td><?php echo formatDate($entry['entry_date']); ?></td>
                                <td><code><?php echo htmlspecialchars($entry['reference_number']); ?></code></td>
                                <td class="small">
                                    <?php echo htmlspecialchars($entry['account_code'] . ' - ' . $entry['account_name']); ?>
                                </td>
                                <td class="small"><?php echo htmlspecialchars($entry['description']); ?></td>
                                <td>
                                    <span class="badge bg-secondary">
                                        <?php echo htmlspecialchars($entry['source_type']); ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <?php if ($entry['entry_type'] === 'Debit'): ?>
                                        <strong><?php echo formatCurrency($entry['amount']); ?></strong>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <?php if ($entry['entry_type'] === 'Credit'): ?>
                                        <strong><?php echo formatCurrency($entry['amount']); ?></strong>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <tr class="table-light">
                                <td colspan="5" class="text-end"><strong>Totals:</strong></td>
                                <td class="text-end"><strong><?php echo formatCurrency($total_debit); ?></strong></td>
                                <td class="text-end"><strong><?php echo formatCurrency($total_credit); ?></strong></td>
                            </tr>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    No ledger entries found for the selected period
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    .navbar, .btn, form, .no-print { display: none !important; }
    .card { border: 1px solid #000 !important; page-break-inside: avoid; }
    .table { font-size: 10px !important; }
}
</style>

<?php include '../includes/footer.php'; ?>


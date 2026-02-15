<?php
/**
 * Code Catalyst Labs - Chart of Accounts
 */
require_once '../includes/config.php';
require_once '../includes/functions.php';

requireLogin();
requireRole(['Admin', 'Finance']);

$page_title = 'Chart of Accounts';

// Get all accounts
$query = "SELECT * FROM chart_of_accounts ORDER BY account_code";
$result = mysqli_query($conn, $query);

// Group accounts by type
$accounts_by_type = [
    'Asset' => [],
    'Liability' => [],
    'Equity' => [],
    'Revenue' => [],
    'Expense' => []
];

while ($row = mysqli_fetch_assoc($result)) {
    $accounts_by_type[$row['account_type']][] = $row;
}

include '../includes/header.php';
?>

<div class="container">
    <?php displayAlert(); ?>
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-journal-text"></i> Chart of Accounts</h2>
        <a href="create.php" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Add New Account
        </a>
    </div>
    
    <div class="row">
        <?php foreach ($accounts_by_type as $type => $accounts): ?>
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0">
                        <i class="bi bi-<?php 
                            echo $type === 'Asset' ? 'graph-up-arrow' : 
                                ($type === 'Liability' ? 'graph-down-arrow' : 
                                ($type === 'Equity' ? 'pie-chart' : 
                                ($type === 'Revenue' ? 'cash-coin' : 'receipt-cutoff'))); 
                        ?>"></i> 
                        <?php echo $type; ?>s
                    </h5>
                </div>
                <div class="card-body p-0">
                    <?php if (count($accounts) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Code</th>
                                        <th>Account Name</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($accounts as $account): ?>
                                    <tr>
                                        <td><code><?php echo htmlspecialchars($account['account_code']); ?></code></td>
                                        <td><?php echo htmlspecialchars($account['account_name']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $account['is_active'] ? 'success' : 'secondary'; ?>">
                                                <?php echo $account['is_active'] ? 'Active' : 'Inactive'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="edit.php?id=<?php echo $account['id']; ?>" 
                                                   class="btn btn-outline-primary btn-sm" title="Edit">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted text-center py-3">No accounts in this category</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <div class="card bg-light">
        <div class="card-body">
            <h6><i class="bi bi-info-circle"></i> About Chart of Accounts</h6>
            <p class="small mb-0">
                The Chart of Accounts is a listing of all accounts used in your general ledger. 
                Each account is categorized by type (Asset, Liability, Equity, Revenue, or Expense) 
                and identified by a unique account code.
            </p>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>


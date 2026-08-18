<?php
/**
 * Code Catalyst Labs - Financial Reports
 */
require_once '../includes/config.php';
require_once '../includes/functions.php';

requireLogin();
requireRole(['Admin', 'Finance']);

$page_title = 'Financial Reports';

// Date filters
$date_from = isset($_GET['date_from']) ? clean($_GET['date_from']) : date('Y-m-01');
$date_to = isset($_GET['date_to']) ? clean($_GET['date_to']) : date('Y-m-d');

// Income Statement Data
$income_data = [];
$expense_data = [];

// Get Revenue
$query = "SELECT SUM(total) as total FROM invoices WHERE status = 'Paid' AND date BETWEEN ? AND ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "ss", $date_from, $date_to);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$income_data['revenue'] = mysqli_fetch_assoc($result)['total'] ?? 0;
mysqli_stmt_close($stmt);

// Get Expenses by Category
$query = "SELECT category, SUM(amount) as total FROM expenses 
          WHERE expense_date BETWEEN ? AND ? 
          GROUP BY category ORDER BY total DESC";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "ss", $date_from, $date_to);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$total_expenses = 0;
while ($row = mysqli_fetch_assoc($result)) {
    $expense_data[$row['category']] = $row['total'];
    $total_expenses += $row['total'];
}
mysqli_stmt_close($stmt);

$net_profit = $income_data['revenue'] - $total_expenses;

// Balance Sheet Data (Current as of date_to)
$assets_total = 0;
$liabilities_total = 0;

// Get Asset Value
$query = "SELECT SUM(current_value) as total FROM assets WHERE purchase_date <= ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "s", $date_to);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$assets_total = mysqli_fetch_assoc($result)['total'] ?? 0;
mysqli_stmt_close($stmt);

// Cash on Hand (from cashbook)
$query = "SELECT 
            SUM(CASE WHEN transaction_type = 'Income' THEN amount ELSE 0 END) as income,
            SUM(CASE WHEN transaction_type = 'Expense' THEN amount ELSE 0 END) as expense
          FROM cashbook WHERE transaction_date <= ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "s", $date_to);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$cashflow = mysqli_fetch_assoc($result);
$cash_balance = ($cashflow['income'] ?? 0) - ($cashflow['expense'] ?? 0);
mysqli_stmt_close($stmt);

$total_assets = $assets_total + $cash_balance;

// Accounts Receivable
$query = "SELECT SUM(total) as total FROM invoices WHERE status IN ('Unpaid', 'Partially Paid') AND date <= ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "s", $date_to);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$accounts_receivable = mysqli_fetch_assoc($result)['total'] ?? 0;
mysqli_stmt_close($stmt);

$total_assets += $accounts_receivable;

// Accounts Payable (unpaid expenses)
$query = "SELECT SUM(amount) as total FROM expenses WHERE payment_status IN ('Pending', 'Partially Paid') AND expense_date <= ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "s", $date_to);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$accounts_payable = mysqli_fetch_assoc($result)['total'] ?? 0;
mysqli_stmt_close($stmt);

$liabilities_total = $accounts_payable;
$equity = $total_assets - $liabilities_total;

include '../includes/header.php';
?>

<div class="container">
    <?php displayAlert(); ?>
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-graph-up"></i> Financial Reports</h2>
        <div class="d-flex gap-2">
            <a href="<?php echo APP_URL; ?>/reports/analytics.php" class="btn btn-outline-primary">
                <i class="bi bi-bar-chart-line"></i> Analytics
            </a>
            <a href="<?php echo APP_URL; ?>/reports/exports.php" class="btn btn-outline-success">
                <i class="bi bi-download"></i> Export Data
            </a>
            <button onclick="window.print()" class="btn btn-secondary">
                <i class="bi bi-printer"></i> Print Report
            </button>
        </div>
    </div>
    
    <!-- Date Range Filter -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">From Date</label>
                    <input type="date" name="date_from" class="form-control" value="<?php echo $date_from; ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">To Date</label>
                    <input type="date" name="date_to" class="form-control" value="<?php echo $date_to; ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search"></i> Generate
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <div class="row">
        <!-- Income Statement -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-file-earmark-text"></i> Income Statement</h5>
                    <small>Period: <?php echo formatDate($date_from); ?> to <?php echo formatDate($date_to); ?></small>
                </div>
                <div class="card-body">
                    <h6 class="text-success">Revenue</h6>
                    <div class="d-flex justify-content-between mb-3">
                        <span>Total Sales Revenue</span>
                        <strong class="text-success"><?php echo formatCurrency($income_data['revenue']); ?></strong>
                    </div>
                    
                    <hr>
                    
                    <h6 class="text-danger">Expenses</h6>
                    <?php if (count($expense_data) > 0): ?>
                        <?php foreach ($expense_data as $category => $amount): ?>
                        <div class="d-flex justify-content-between mb-2">
                            <span><?php echo htmlspecialchars($category); ?></span>
                            <span class="text-danger"><?php echo formatCurrency($amount); ?></span>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted">No expenses recorded</p>
                    <?php endif; ?>
                    
                    <div class="d-flex justify-content-between mb-2 border-top pt-2 mt-2">
                        <strong>Total Expenses</strong>
                        <strong class="text-danger"><?php echo formatCurrency($total_expenses); ?></strong>
                    </div>
                    
                    <hr class="my-3">
                    
                    <div class="d-flex justify-content-between">
                        <h5>Net Profit/Loss</h5>
                        <h5 class="<?php echo $net_profit >= 0 ? 'text-success' : 'text-danger'; ?>">
                            <?php echo formatCurrency($net_profit); ?>
                        </h5>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Balance Sheet -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="bi bi-pie-chart"></i> Balance Sheet</h5>
                    <small>As of <?php echo formatDate($date_to); ?></small>
                </div>
                <div class="card-body">
                    <h6 class="text-primary">Assets</h6>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Cash & Bank</span>
                        <span><?php echo formatCurrency($cash_balance); ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Accounts Receivable</span>
                        <span><?php echo formatCurrency($accounts_receivable); ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Fixed Assets</span>
                        <span><?php echo formatCurrency($assets_total); ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-3 border-top pt-2 mt-2">
                        <strong>Total Assets</strong>
                        <strong class="text-primary"><?php echo formatCurrency($total_assets); ?></strong>
                    </div>
                    
                    <hr>
                    
                    <h6 class="text-danger">Liabilities</h6>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Accounts Payable</span>
                        <span><?php echo formatCurrency($accounts_payable); ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-3 border-top pt-2 mt-2">
                        <strong>Total Liabilities</strong>
                        <strong class="text-danger"><?php echo formatCurrency($liabilities_total); ?></strong>
                    </div>
                    
                    <hr>
                    
                    <h6 class="text-success">Equity</h6>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Owner's Equity</span>
                        <span><?php echo formatCurrency($equity); ?></span>
                    </div>
                    
                    <hr class="my-3">
                    
                    <div class="d-flex justify-content-between">
                        <h6>Total Liabilities + Equity</h6>
                        <h6 class="text-success"><?php echo formatCurrency($liabilities_total + $equity); ?></h6>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Cash Flow Summary -->
    <div class="card">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0"><i class="bi bi-graph-up-arrow"></i> Cash Flow Summary</h5>
            <small>Period: <?php echo formatDate($date_from); ?> to <?php echo formatDate($date_to); ?></small>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <div class="text-center">
                        <h6 class="text-success">Cash Inflows</h6>
                        <h3 class="text-success"><?php echo formatCurrency($income_data['revenue']); ?></h3>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="text-center">
                        <h6 class="text-danger">Cash Outflows</h6>
                        <h3 class="text-danger"><?php echo formatCurrency($total_expenses); ?></h3>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="text-center">
                        <h6 class="text-primary">Net Cash Flow</h6>
                        <h3 class="<?php echo $net_profit >= 0 ? 'text-success' : 'text-danger'; ?>">
                            <?php echo formatCurrency($net_profit); ?>
                        </h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    .navbar, .btn, form, .no-print { display: none !important; }
    .card { border: 1px solid #000 !important; page-break-inside: avoid; }
}
</style>

<?php include '../includes/footer.php'; ?>


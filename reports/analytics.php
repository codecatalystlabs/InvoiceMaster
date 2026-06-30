<?php
/**
 * Code Catalyst Labs - Analytics Dashboard
 *
 * Visual analysis of revenue, expenses, profitability and receivables.
 * All money values are computed in PHP and cast to float to avoid any
 * string-concatenation / overflow surprises, then handed to Chart.js.
 */
require_once '../includes/config.php';
require_once '../includes/functions.php';

requireLogin();
requireRole(['Admin', 'Finance']);

$page_title = 'Analytics';

// 12-month window (inclusive of current month).
$window_start = date('Y-m-01', strtotime('-11 months'));

// Build an ordered list of the last 12 month keys (YYYY-MM) and labels.
$months = [];
$month_labels = [];
for ($i = 11; $i >= 0; $i--) {
    $ts = strtotime("first day of -$i months");
    $key = date('Y-m', $ts);
    $months[$key] = 0.0;
    $month_labels[$key] = date('M Y', $ts);
}

// --- Monthly revenue (paid invoices) ---
$revenue_by_month = $months;
$query = "SELECT DATE_FORMAT(date, '%Y-%m') AS ym, SUM(total) AS t
          FROM invoices
          WHERE status = 'Paid' AND date >= ?
          GROUP BY ym";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, 's', $window_start);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($res)) {
    if (isset($revenue_by_month[$row['ym']])) {
        $revenue_by_month[$row['ym']] = (float)$row['t'];
    }
}
mysqli_stmt_close($stmt);

// --- Monthly expenses ---
$expense_by_month = $months;
$query = "SELECT DATE_FORMAT(expense_date, '%Y-%m') AS ym, SUM(amount) AS t
          FROM expenses
          WHERE expense_date >= ?
          GROUP BY ym";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, 's', $window_start);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($res)) {
    if (isset($expense_by_month[$row['ym']])) {
        $expense_by_month[$row['ym']] = (float)$row['t'];
    }
}
mysqli_stmt_close($stmt);

// Net profit series derived from the two above.
$profit_by_month = [];
foreach ($months as $key => $_) {
    $profit_by_month[$key] = $revenue_by_month[$key] - $expense_by_month[$key];
}

// --- Expenses by category (within window) ---
$expense_categories = [];
$query = "SELECT category, SUM(amount) AS t
          FROM expenses
          WHERE expense_date >= ?
          GROUP BY category
          ORDER BY t DESC";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, 's', $window_start);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($res)) {
    $expense_categories[$row['category'] ?: 'Uncategorized'] = (float)$row['t'];
}
mysqli_stmt_close($stmt);

// --- Invoice status distribution (all time) ---
$invoice_status = [];
$res = mysqli_query($conn, "SELECT status, COUNT(*) AS c, SUM(total) AS t FROM invoices GROUP BY status");
while ($row = mysqli_fetch_assoc($res)) {
    $invoice_status[$row['status']] = [
        'count'  => (int)$row['c'],
        'amount' => (float)$row['t'],
    ];
}

// --- Top clients by paid revenue ---
$top_clients_labels = [];
$top_clients_values = [];
$res = mysqli_query($conn, "SELECT c.name, SUM(i.total) AS t
                            FROM invoices i
                            JOIN clients c ON i.client_id = c.id
                            WHERE i.status = 'Paid'
                            GROUP BY c.id
                            ORDER BY t DESC
                            LIMIT 7");
while ($row = mysqli_fetch_assoc($res)) {
    $top_clients_labels[] = $row['name'];
    $top_clients_values[] = (float)$row['t'];
}

// --- KPI summary (all time) ---
$kpi_revenue = (float)(mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT SUM(total) AS t FROM invoices WHERE status = 'Paid'"))['t'] ?? 0);

$kpi_receivable = (float)(mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT SUM(total) AS t FROM invoices WHERE status IN ('Unpaid', 'Partially Paid', 'Overdue')"))['t'] ?? 0);

$kpi_expenses = (float)(mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT SUM(amount) AS t FROM expenses"))['t'] ?? 0);

$kpi_net = $kpi_revenue - $kpi_expenses;

// Prepare JSON payloads for the charts.
$chart = [
    'monthLabels'      => array_values($month_labels),
    'revenue'          => array_values($revenue_by_month),
    'expenses'         => array_values($expense_by_month),
    'profit'           => array_values($profit_by_month),
    'expenseCatLabels' => array_keys($expense_categories),
    'expenseCatValues' => array_values($expense_categories),
    'statusLabels'     => array_keys($invoice_status),
    'statusCounts'     => array_map(function ($s) { return $s['count']; }, array_values($invoice_status)),
    'topClientLabels'  => $top_clients_labels,
    'topClientValues'  => $top_clients_values,
    'currency'         => currencySymbol(),
];

include '../includes/header.php';
?>

<div class="container">
    <?php displayAlert(); ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-bar-chart-line"></i> Analytics</h2>
        <div>
            <a href="<?php echo APP_URL; ?>/reports/exports.php" class="btn btn-outline-primary">
                <i class="bi bi-download"></i> Export Data
            </a>
            <a href="<?php echo APP_URL; ?>/reports/financial.php" class="btn btn-outline-secondary">
                <i class="bi bi-graph-up"></i> Financial Report
            </a>
        </div>
    </div>

    <!-- KPI cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-success h-100">
                <div class="card-body">
                    <h6 class="text-success"><i class="bi bi-cash-stack"></i> Revenue (Paid)</h6>
                    <h4 class="mb-0"><?php echo formatCurrency($kpi_revenue); ?></h4>
                    <small class="text-muted">All time</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-danger h-100">
                <div class="card-body">
                    <h6 class="text-danger"><i class="bi bi-receipt-cutoff"></i> Total Expenses</h6>
                    <h4 class="mb-0"><?php echo formatCurrency($kpi_expenses); ?></h4>
                    <small class="text-muted">All time</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-primary h-100">
                <div class="card-body">
                    <h6 class="text-primary"><i class="bi bi-graph-up-arrow"></i> Net Profit</h6>
                    <h4 class="mb-0 <?php echo $kpi_net >= 0 ? 'text-success' : 'text-danger'; ?>">
                        <?php echo formatCurrency($kpi_net); ?>
                    </h4>
                    <small class="text-muted">Revenue − Expenses</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-warning h-100">
                <div class="card-body">
                    <h6 class="text-warning"><i class="bi bi-hourglass-split"></i> Receivables</h6>
                    <h4 class="mb-0"><?php echo formatCurrency($kpi_receivable); ?></h4>
                    <small class="text-muted">Outstanding</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Revenue vs Expenses trend -->
    <div class="card mb-4">
        <div class="card-header bg-light">
            <h5 class="mb-0"><i class="bi bi-graph-up"></i> Revenue vs Expenses (Last 12 Months)</h5>
        </div>
        <div class="card-body">
            <canvas id="trendChart" height="100"></canvas>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="bi bi-graph-up-arrow"></i> Monthly Net Profit</h5>
                </div>
                <div class="card-body">
                    <canvas id="profitChart" height="160"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="bi bi-pie-chart"></i> Expenses by Category</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($expense_categories)): ?>
                        <canvas id="categoryChart" height="160"></canvas>
                    <?php else: ?>
                        <p class="text-muted text-center py-5">No expense data in the last 12 months.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="bi bi-clipboard-data"></i> Invoice Status</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($invoice_status)): ?>
                        <canvas id="statusChart" height="160"></canvas>
                    <?php else: ?>
                        <p class="text-muted text-center py-5">No invoices yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="bi bi-people"></i> Top Clients by Revenue</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($top_clients_labels)): ?>
                        <canvas id="clientsChart" height="160"></canvas>
                    <?php else: ?>
                        <p class="text-muted text-center py-5">No paid invoices yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(function () {
    var data = <?php echo json_encode($chart); ?>;
    var palette = ['#0d6efd', '#198754', '#dc3545', '#ffc107', '#6f42c1', '#20c997', '#fd7e14', '#0dcaf0', '#6610f2', '#d63384'];

    function money(value) {
        return data.currency + ' ' + Number(value).toLocaleString();
    }

    var currencyTick = {
        ticks: {
            callback: function (value) { return Number(value).toLocaleString(); }
        }
    };

    // Revenue vs Expenses (line)
    new Chart(document.getElementById('trendChart'), {
        type: 'line',
        data: {
            labels: data.monthLabels,
            datasets: [
                { label: 'Revenue', data: data.revenue, borderColor: '#198754', backgroundColor: 'rgba(25,135,84,.1)', fill: true, tension: 0.3 },
                { label: 'Expenses', data: data.expenses, borderColor: '#dc3545', backgroundColor: 'rgba(220,53,69,.1)', fill: true, tension: 0.3 }
            ]
        },
        options: {
            responsive: true,
            plugins: { tooltip: { callbacks: { label: function (c) { return c.dataset.label + ': ' + money(c.parsed.y); } } } },
            scales: { y: currencyTick }
        }
    });

    // Monthly net profit (bar)
    new Chart(document.getElementById('profitChart'), {
        type: 'bar',
        data: {
            labels: data.monthLabels,
            datasets: [{
                label: 'Net Profit',
                data: data.profit,
                backgroundColor: data.profit.map(function (v) { return v >= 0 ? 'rgba(25,135,84,.7)' : 'rgba(220,53,69,.7)'; })
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false }, tooltip: { callbacks: { label: function (c) { return money(c.parsed.y); } } } },
            scales: { y: currencyTick }
        }
    });

    // Expenses by category (doughnut)
    var catEl = document.getElementById('categoryChart');
    if (catEl) {
        new Chart(catEl, {
            type: 'doughnut',
            data: {
                labels: data.expenseCatLabels,
                datasets: [{ data: data.expenseCatValues, backgroundColor: palette }]
            },
            options: {
                responsive: true,
                plugins: { tooltip: { callbacks: { label: function (c) { return c.label + ': ' + money(c.parsed); } } } }
            }
        });
    }

    // Invoice status (pie)
    var statusEl = document.getElementById('statusChart');
    if (statusEl) {
        new Chart(statusEl, {
            type: 'pie',
            data: {
                labels: data.statusLabels,
                datasets: [{ data: data.statusCounts, backgroundColor: palette }]
            },
            options: { responsive: true }
        });
    }

    // Top clients (horizontal bar)
    var clientsEl = document.getElementById('clientsChart');
    if (clientsEl) {
        new Chart(clientsEl, {
            type: 'bar',
            data: {
                labels: data.topClientLabels,
                datasets: [{ label: 'Revenue', data: data.topClientValues, backgroundColor: '#0d6efd' }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                plugins: { legend: { display: false }, tooltip: { callbacks: { label: function (c) { return money(c.parsed.x); } } } },
                scales: { x: currencyTick }
            }
        });
    }
})();
</script>

<?php include '../includes/footer.php'; ?>

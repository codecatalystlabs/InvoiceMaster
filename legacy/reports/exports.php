<?php
/**
 * Code Catalyst Labs - Export Data Hub
 *
 * Central place to download any dataset as CSV. Each card links to the
 * shared export.php endpoint and honours the optional date range below.
 */
require_once '../includes/config.php';
require_once '../includes/functions.php';

requireLogin();

$page_title = 'Export Data';

// Datasets the user can download, grouped for display. Roles mirror export.php.
$exports = [
    'Sales' => [
        ['type' => 'invoices',   'label' => 'Invoices',        'icon' => 'bi-receipt',          'desc' => 'All invoices with totals, status and client.',   'roles' => ['Admin', 'Finance', 'Sales'], 'dated' => true],
        ['type' => 'quotations', 'label' => 'Quotations',      'icon' => 'bi-file-earmark-text','desc' => 'All quotations with totals and status.',          'roles' => ['Admin', 'Finance', 'Sales'], 'dated' => true],
        ['type' => 'clients',    'label' => 'Clients',         'icon' => 'bi-people',           'desc' => 'Client directory with contact details.',          'roles' => ['Admin', 'Finance', 'Sales'], 'dated' => false],
    ],
    'Accounting' => [
        ['type' => 'expenses',   'label' => 'Expenses',        'icon' => 'bi-receipt-cutoff',   'desc' => 'Expense records by vendor, category and account.','roles' => ['Admin', 'Finance'], 'dated' => true],
        ['type' => 'cashbook',   'label' => 'Cashbook',        'icon' => 'bi-cash-stack',       'desc' => 'All cash income and expense transactions.',       'roles' => ['Admin', 'Finance'], 'dated' => true],
        ['type' => 'ledger',     'label' => 'General Ledger',  'icon' => 'bi-book',             'desc' => 'Double-entry ledger postings.',                   'roles' => ['Admin', 'Finance'], 'dated' => true],
        ['type' => 'assets',     'label' => 'Assets',          'icon' => 'bi-box-seam',         'desc' => 'Asset register with values and depreciation.',    'roles' => ['Admin', 'Finance'], 'dated' => true],
        ['type' => 'services',   'label' => 'Services',        'icon' => 'bi-arrow-repeat',     'desc' => 'Subscriptions and recurring services.',           'roles' => ['Admin', 'Finance'], 'dated' => true],
    ],
];

include '../includes/header.php';
?>

<div class="container">
    <?php displayAlert(); ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-download"></i> Export Data</h2>
        <a href="<?php echo APP_URL; ?>/reports/analytics.php" class="btn btn-outline-primary">
            <i class="bi bi-bar-chart-line"></i> View Analytics
        </a>
    </div>

    <!-- Optional date range applied to dated datasets -->
    <div class="card mb-4">
        <div class="card-body">
            <h6 class="card-title"><i class="bi bi-calendar-range"></i> Optional Date Range</h6>
            <p class="text-muted small mb-3">Leave blank to export all records. The range applies to date-based datasets (it is ignored for the Clients list).</p>
            <div class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">From</label>
                    <input type="date" id="exportFrom" class="form-control">
                </div>
                <div class="col-md-4">
                    <label class="form-label">To</label>
                    <input type="date" id="exportTo" class="form-control">
                </div>
                <div class="col-md-4">
                    <button type="button" id="clearRange" class="btn btn-outline-secondary">
                        <i class="bi bi-x-circle"></i> Clear Range
                    </button>
                </div>
            </div>
        </div>
    </div>

    <?php foreach ($exports as $group => $items): ?>
        <?php
        // Only render groups that contain at least one dataset this user can access.
        $accessible = array_filter($items, function ($item) {
            return hasRole($item['roles']);
        });
        if (empty($accessible)) {
            continue;
        }
        ?>
        <h5 class="mb-3 mt-2"><?php echo htmlspecialchars($group); ?></h5>
        <div class="row g-3 mb-4">
            <?php foreach ($accessible as $item): ?>
            <div class="col-md-4">
                <div class="card h-100 shadow-sm">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title">
                            <i class="bi <?php echo $item['icon']; ?> text-primary"></i>
                            <?php echo htmlspecialchars($item['label']); ?>
                        </h5>
                        <p class="card-text text-muted small flex-grow-1"><?php echo htmlspecialchars($item['desc']); ?></p>
                        <a href="<?php echo APP_URL; ?>/export.php?type=<?php echo $item['type']; ?>"
                           class="btn btn-primary export-btn"
                           data-type="<?php echo $item['type']; ?>"
                           data-dated="<?php echo $item['dated'] ? '1' : '0'; ?>">
                            <i class="bi bi-filetype-csv"></i> Download CSV
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endforeach; ?>
</div>

<script>
(function () {
    var base = '<?php echo APP_URL; ?>/export.php';

    function buildLinks() {
        var from = document.getElementById('exportFrom').value;
        var to = document.getElementById('exportTo').value;
        document.querySelectorAll('.export-btn').forEach(function (btn) {
            var url = base + '?type=' + btn.getAttribute('data-type');
            if (btn.getAttribute('data-dated') === '1') {
                if (from) { url += '&from=' + encodeURIComponent(from); }
                if (to) { url += '&to=' + encodeURIComponent(to); }
            }
            btn.setAttribute('href', url);
        });
    }

    document.getElementById('exportFrom').addEventListener('change', buildLinks);
    document.getElementById('exportTo').addEventListener('change', buildLinks);
    document.getElementById('clearRange').addEventListener('click', function () {
        document.getElementById('exportFrom').value = '';
        document.getElementById('exportTo').value = '';
        buildLinks();
    });
})();
</script>

<?php include '../includes/footer.php'; ?>

<?php
/**
 * Code Catalyst Labs - Central Data Export
 *
 * Streams any of the system's datasets as a CSV download.
 * Usage: export.php?type=invoices[&from=YYYY-MM-DD&to=YYYY-MM-DD]
 *
 * Each dataset declares the roles allowed to download it, the SQL used to
 * gather the rows, an optional date column for range filtering, and the column
 * headers shown in the CSV.
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

requireLogin();

/**
 * Guard a CSV value against spreadsheet formula injection.
 * Values starting with =, +, -, @ (or tab/CR) are prefixed with a single quote.
 */
function csvCell($value) {
    $value = (string)$value;
    if ($value !== '' && in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
        return "'" . $value;
    }
    return $value;
}

/**
 * Stream an array of associative rows as a CSV download.
 */
function streamCsv($filename, array $headers, array $rows) {
    // Discard anything that may have been buffered before sending headers.
    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $output = fopen('php://output', 'w');
    // UTF-8 BOM so Excel renders accents/symbols correctly.
    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

    fputcsv($output, $headers);
    foreach ($rows as $row) {
        $line = array_map('csvCell', $row);
        fputcsv($output, $line);
    }
    fclose($output);
    exit();
}

// ---------------------------------------------------------------------------
// Dataset registry
// ---------------------------------------------------------------------------
$datasets = [
    'invoices' => [
        'label'      => 'Invoices',
        'roles'      => ['Admin', 'Finance', 'Sales'],
        'date_col'   => 'i.date',
        'headers'    => ['Invoice #', 'Date', 'Due Date', 'Client', 'Subtotal', 'Tax', 'Discount', 'Total', 'Status'],
        'sql'        => "SELECT i.invoice_number, i.date, i.due_date, c.name AS client,
                                i.subtotal, i.tax, i.discount, i.total, i.status
                         FROM invoices i
                         LEFT JOIN clients c ON i.client_id = c.id",
        'order'      => 'ORDER BY i.date DESC',
    ],
    'quotations' => [
        'label'      => 'Quotations',
        'roles'      => ['Admin', 'Finance', 'Sales'],
        'date_col'   => 'q.date',
        'headers'    => ['Quotation #', 'Date', 'Client', 'Subtotal', 'Tax', 'Discount', 'Total', 'Status'],
        'sql'        => "SELECT q.quotation_number, q.date, c.name AS client,
                                q.subtotal, q.tax, q.discount, q.total, q.status
                         FROM quotations q
                         LEFT JOIN clients c ON q.client_id = c.id",
        'order'      => 'ORDER BY q.date DESC',
    ],
    'clients' => [
        'label'      => 'Clients',
        'roles'      => ['Admin', 'Finance', 'Sales'],
        'date_col'   => 'created_at',
        'headers'    => ['Name', 'Email', 'Phone', 'Company', 'Added On'],
        'sql'        => "SELECT name, email, phone, company, created_at FROM clients",
        'order'      => 'ORDER BY name ASC',
    ],
    'expenses' => [
        'label'      => 'Expenses',
        'roles'      => ['Admin', 'Finance'],
        'date_col'   => 'e.expense_date',
        'headers'    => ['Expense #', 'Date', 'Vendor', 'Category', 'Account', 'Amount', 'Payment Method', 'Status', 'Recurring', 'Frequency', 'Description'],
        'sql'        => "SELECT e.expense_number, e.expense_date, e.vendor_name, e.category,
                                c.account_name, e.amount, e.payment_method, e.payment_status,
                                CASE WHEN e.is_recurring = 1 THEN 'Yes' ELSE 'No' END AS recurring,
                                e.recurrence_frequency, e.description
                         FROM expenses e
                         LEFT JOIN chart_of_accounts c ON e.account_id = c.id",
        'order'      => 'ORDER BY e.expense_date DESC',
    ],
    'assets' => [
        'label'      => 'Assets',
        'roles'      => ['Admin', 'Finance'],
        'date_col'   => 'purchase_date',
        'headers'    => ['Asset #', 'Name', 'Category', 'Purchase Date', 'Purchase Price', 'Current Value', 'Depreciation %', 'Method', 'Condition', 'Location', 'Serial #'],
        'sql'        => "SELECT asset_number, asset_name, category, purchase_date, purchase_price,
                                current_value, depreciation_rate, depreciation_method,
                                condition_status, location, serial_number
                         FROM assets",
        'order'      => 'ORDER BY purchase_date DESC',
    ],
    'services' => [
        'label'      => 'Services',
        'roles'      => ['Admin', 'Finance'],
        'date_col'   => 'start_date',
        'headers'    => ['Service #', 'Name', 'Provider', 'Category', 'Cost', 'Billing Frequency', 'Start Date', 'Next Billing', 'Status'],
        'sql'        => "SELECT service_number, service_name, provider_name, category, cost,
                                billing_frequency, start_date, next_billing_date, status
                         FROM services",
        'order'      => 'ORDER BY next_billing_date ASC',
    ],
    'cashbook' => [
        'label'      => 'Cashbook',
        'roles'      => ['Admin', 'Finance'],
        'date_col'   => 'cb.transaction_date',
        'headers'    => ['Date', 'Reference #', 'Account', 'Type', 'Payment Method', 'Amount', 'Category', 'Description'],
        'sql'        => "SELECT cb.transaction_date, cb.reference_number, c.account_name,
                                cb.transaction_type, cb.payment_method, cb.amount,
                                cb.category, cb.description
                         FROM cashbook cb
                         LEFT JOIN chart_of_accounts c ON cb.account_id = c.id",
        'order'      => 'ORDER BY cb.transaction_date DESC',
    ],
    'ledger' => [
        'label'      => 'General Ledger',
        'roles'      => ['Admin', 'Finance'],
        'date_col'   => 'l.entry_date',
        'headers'    => ['Date', 'Reference #', 'Account', 'Entry Type', 'Amount', 'Source', 'Description'],
        'sql'        => "SELECT l.entry_date, l.reference_number, c.account_name,
                                l.entry_type, l.amount, l.source_type, l.description
                         FROM ledger_entries l
                         LEFT JOIN chart_of_accounts c ON l.account_id = c.id",
        'order'      => 'ORDER BY l.entry_date DESC, l.id DESC',
    ],
];

// ---------------------------------------------------------------------------
// Resolve requested dataset
// ---------------------------------------------------------------------------
$type = isset($_GET['type']) ? preg_replace('/[^a-z_]/', '', strtolower($_GET['type'])) : '';

if ($type === '' || !isset($datasets[$type])) {
    http_response_code(400);
    die('Unknown export type.');
}

$dataset = $datasets[$type];

if (!hasRole($dataset['roles'])) {
    http_response_code(403);
    die('You do not have permission to export this dataset.');
}

// ---------------------------------------------------------------------------
// Optional date range filtering (?from=YYYY-MM-DD&to=YYYY-MM-DD)
// ---------------------------------------------------------------------------
$from = isset($_GET['from']) ? trim($_GET['from']) : '';
$to   = isset($_GET['to']) ? trim($_GET['to']) : '';
$isValidDate = function ($d) {
    return preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) === 1;
};

$where = [];
$params = [];
$types = '';

if (!empty($dataset['date_col'])) {
    if ($from !== '' && $isValidDate($from)) {
        $where[] = $dataset['date_col'] . ' >= ?';
        $params[] = $from;
        $types .= 's';
    }
    if ($to !== '' && $isValidDate($to)) {
        $where[] = $dataset['date_col'] . ' <= ?';
        $params[] = $to;
        $types .= 's';
    }
}

$sql = $dataset['sql'];
if (!empty($where)) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
if (!empty($dataset['order'])) {
    $sql .= ' ' . $dataset['order'];
}

// ---------------------------------------------------------------------------
// Run query and stream
// ---------------------------------------------------------------------------
$rows = [];
if (!empty($params)) {
    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt === false) {
        http_response_code(500);
        die('Export query failed.');
    }
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = array_values($row);
    }
    mysqli_stmt_close($stmt);
} else {
    $result = mysqli_query($conn, $sql);
    if ($result === false) {
        http_response_code(500);
        die('Export query failed.');
    }
    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = array_values($row);
    }
}

logAudit($conn, 'Export', ucfirst($type), null, count($rows) . ' rows exported to CSV');

$filename = $type . '_export_' . date('Y-m-d') . '.csv';
streamCsv($filename, $dataset['headers'], $rows);

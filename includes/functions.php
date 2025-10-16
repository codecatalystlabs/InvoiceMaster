<?php
/**
 * Code Catalyst Labs - Helper Functions
 */

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['username']);
}

/**
 * Check if user has specific role
 */
function hasRole($role) {
    if (!isLoggedIn()) return false;
    
    if (is_array($role)) {
        return in_array($_SESSION['role'], $role);
    }
    
    return $_SESSION['role'] === $role;
}

/**
 * Redirect to login if not authenticated
 */
function requireLogin() {
    if (!isLoggedIn()) {
        // Clear any existing session data to ensure clean state
        $_SESSION = array();
        header('Location: ' . APP_URL . '/auth/login.php');
        exit();
    }
}

/**
 * Redirect if user doesn't have required role
 */
function requireRole($role) {
    requireLogin();
    
    if (!hasRole($role)) {
        $_SESSION['error'] = "You don't have permission to access this page.";
        header('Location: ' . APP_URL . '/index.php');
        exit();
    }
}

/**
 * Sanitize input data
 */
function clean($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

/**
 * Generate quotation number
 */
function generateQuotationNumber($conn) {
    $prefix = 'QUO';
    $year = date('Y');
    
    $query = "SELECT quotation_number FROM quotations 
              WHERE quotation_number LIKE '$prefix-$year-%' 
              ORDER BY id DESC LIMIT 1";
    $result = mysqli_query($conn, $query);
    
    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $lastNumber = (int)substr($row['quotation_number'], -4);
        $newNumber = $lastNumber + 1;
    } else {
        $newNumber = 1;
    }
    
    return $prefix . '-' . $year . '-' . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
}

/**
 * Generate invoice number
 */
function generateInvoiceNumber($conn) {
    $prefix = 'INV';
    $year = date('Y');
    
    $query = "SELECT invoice_number FROM invoices 
              WHERE invoice_number LIKE '$prefix-$year-%' 
              ORDER BY id DESC LIMIT 1";
    $result = mysqli_query($conn, $query);
    
    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $lastNumber = (int)substr($row['invoice_number'], -4);
        $newNumber = $lastNumber + 1;
    } else {
        $newNumber = 1;
    }
    
    return $prefix . '-' . $year . '-' . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
}

/**
 * Log audit trail
 */
function logAudit($conn, $action, $entity_type, $entity_id = null, $details = '') {
    if (!isLoggedIn()) return;
    
    $user_id = $_SESSION['user_id'];
    $action = mysqli_real_escape_string($conn, $action);
    $entity_type = mysqli_real_escape_string($conn, $entity_type);
    $details = mysqli_real_escape_string($conn, $details);
    
    $query = "INSERT INTO audit_logs (user_id, action, entity_type, entity_id, details) 
              VALUES (?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "issis", $user_id, $action, $entity_type, $entity_id, $details);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

/**
 * Format currency
 */
function formatCurrency($amount) {
    return CURRENCY_SYMBOL . ' ' . number_format($amount, 0);
}

/**
 * Format date for display
 */
function formatDate($date) {
    return date(DISPLAY_DATE_FORMAT, strtotime($date));
}

/**
 * Get client name by ID
 */
function getClientName($conn, $client_id) {
    $query = "SELECT name FROM clients WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $client_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        return $row['name'];
    }
    
    return 'Unknown Client';
}

/**
 * Get all clients
 */
function getClients($conn) {
    $query = "SELECT * FROM clients ORDER BY name ASC";
    $result = mysqli_query($conn, $query);
    return $result;
}

/**
 * Get status badge class
 */
function getStatusBadge($status) {
    $badges = [
        'Draft' => 'secondary',
        'Sent' => 'info',
        'Accepted' => 'success',
        'Rejected' => 'danger',
        'Converted' => 'primary',
        'Unpaid' => 'warning',
        'Partially Paid' => 'info',
        'Paid' => 'success',
        'Overdue' => 'danger',
        'Cancelled' => 'dark',
        'Active' => 'success',
        'Inactive' => 'secondary'
    ];
    
    return isset($badges[$status]) ? $badges[$status] : 'secondary';
}

/**
 * Display alert message
 */
function displayAlert() {
    $types = ['success', 'error', 'warning', 'info'];
    
    foreach ($types as $type) {
        if (isset($_SESSION[$type])) {
            $alertClass = $type === 'error' ? 'danger' : $type;
            echo '<div class="alert alert-' . $alertClass . ' alert-dismissible fade show" role="alert">';
            echo $_SESSION[$type];
            echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
            echo '</div>';
            unset($_SESSION[$type]);
        }
    }
}

/**
 * Calculate pagination
 */
function getPagination($total_records, $page, $per_page) {
    $total_pages = ceil($total_records / $per_page);
    $offset = ($page - 1) * $per_page;
    
    return [
        'total_pages' => $total_pages,
        'offset' => $offset,
        'current_page' => $page
    ];
}

/**
 * Display pagination links
 */
function displayPagination($total_pages, $current_page, $base_url) {
    if ($total_pages <= 1) return;
    
    echo '<nav aria-label="Page navigation">';
    echo '<ul class="pagination justify-content-center">';
    
    // Previous button
    $disabled = $current_page <= 1 ? 'disabled' : '';
    $prev_page = $current_page - 1;
    echo '<li class="page-item ' . $disabled . '">';
    echo '<a class="page-link" href="' . $base_url . '&page=' . $prev_page . '">Previous</a>';
    echo '</li>';
    
    // Page numbers
    for ($i = 1; $i <= $total_pages; $i++) {
        $active = $i === $current_page ? 'active' : '';
        echo '<li class="page-item ' . $active . '">';
        echo '<a class="page-link" href="' . $base_url . '&page=' . $i . '">' . $i . '</a>';
        echo '</li>';
    }
    
    // Next button
    $disabled = $current_page >= $total_pages ? 'disabled' : '';
    $next_page = $current_page + 1;
    echo '<li class="page-item ' . $disabled . '">';
    echo '<a class="page-link" href="' . $base_url . '&page=' . $next_page . '">Next</a>';
    echo '</li>';
    
    echo '</ul>';
    echo '</nav>';
}

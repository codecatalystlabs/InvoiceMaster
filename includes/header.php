<?php
if (!isset($page_title)) {
    $page_title = 'Dashboard';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - <?php echo APP_NAME; ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/style.css">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?php echo APP_URL; ?>/assets/logo.png">
    
    <style>
        /* Fixed navbar styles */
        body {
            padding-top: 76px; /* Height of navbar to prevent content overlap */
        }
        .navbar.fixed-top {
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            z-index: 1030; /* Ensure navbar stays on top */
        }
        /* Adjust main content when not logged in */
        body:not(.logged-in) {
            padding-top: 0;
        }
    </style>
</head>
<body class="<?php echo isLoggedIn() ? 'logged-in' : ''; ?>">
    <?php if (isLoggedIn()): ?>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand d-flex align-items-center" href="<?php echo APP_URL; ?>/index.php">
                <img src="<?php echo APP_URL; ?>/assets/logo.png" alt="Logo" height="30" class="me-2">
                <span><?php echo COMPANY_NAME; ?></span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo APP_URL; ?>/index.php">
                            <i class="bi bi-house-door"></i> Dashboard
                        </a>
                    </li>
                    
                    <!-- Sales Section -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="salesDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-briefcase"></i> Sales
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/quotations/list.php">
                                <i class="bi bi-file-earmark-text"></i> Quotations
                            </a></li>
                            <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/invoices/list.php">
                                <i class="bi bi-receipt"></i> Invoices
                            </a></li>
                            <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/clients/list.php">
                                <i class="bi bi-people"></i> Clients
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/reports/exports.php">
                                <i class="bi bi-download"></i> Export Data
                            </a></li>
                        </ul>
                    </li>
                    
                    <!-- Emails -->
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo APP_URL; ?>/emails/list.php">
                            <i class="bi bi-envelope"></i> Emails
                            <?php
                            // Get unread email count
                            $unread_query = "SELECT COUNT(*) as unread FROM emails WHERE direction = 'incoming' AND status = 'received'";
                            $unread_result = mysqli_query($conn, $unread_query);
                            $unread_count = mysqli_fetch_assoc($unread_result)['unread'];
                            if ($unread_count > 0) {
                                echo ' <span class="badge bg-danger">' . $unread_count . '</span>';
                            }
                            ?>
                        </a>
                    </li>
                    
                    <?php if (hasRole(['Admin', 'Finance'])): ?>
                    <!-- Accounting Section -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="accountingDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-calculator"></i> Accounting
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/accounts/list.php">
                                <i class="bi bi-journal-text"></i> Chart of Accounts
                            </a></li>
                            <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/accounts/cashbook.php">
                                <i class="bi bi-cash-stack"></i> Cashbook
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/reports/financial.php">
                                <i class="bi bi-graph-up"></i> Financial Reports
                            </a></li>
                            <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/reports/ledger.php">
                                <i class="bi bi-book"></i> General Ledger
                            </a></li>
                            <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/reports/analytics.php">
                                <i class="bi bi-bar-chart-line"></i> Analytics
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/reports/exports.php">
                                <i class="bi bi-download"></i> Export Data
                            </a></li>
                        </ul>
                    </li>
                    
                    <!-- Expenses -->
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo APP_URL; ?>/expenses/list.php">
                            <i class="bi bi-receipt-cutoff"></i> Expenses
                        </a>
                    </li>
                    
                    <!-- Assets -->
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo APP_URL; ?>/assets/list.php">
                            <i class="bi bi-box-seam"></i> Assets
                        </a>
                    </li>
                    
                    <!-- Services -->
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo APP_URL; ?>/services/list.php">
                            <i class="bi bi-arrow-repeat"></i> Services
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <?php if (hasRole('Admin')): ?>
                    <!-- Admin Section -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="adminDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-gear"></i> Admin
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/users/list.php">
                                <i class="bi bi-people-fill"></i> User Management
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/audit/logs.php">
                                <i class="bi bi-clock-history"></i> Audit Logs
                            </a></li>
                        </ul>
                    </li>
                    <?php endif; ?>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i> 
                            <?php echo htmlspecialchars($_SESSION['username']); ?>
                            <span class="badge bg-light text-dark"><?php echo $_SESSION['role']; ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/auth/profile.php">
                                <i class="bi bi-person"></i> Profile
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/auth/logout.php">
                                <i class="bi bi-box-arrow-right"></i> Logout
                            </a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <?php endif; ?>
    
    <!-- Main Content -->
    <main class="<?php echo isLoggedIn() ? 'py-4' : ''; ?>">


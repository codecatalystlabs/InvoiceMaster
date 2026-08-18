<?php
/**
 * Code Catalyst Labs - Edit Account
 */
require_once '../includes/config.php';
require_once '../includes/functions.php';

requireLogin();
requireRole(['Admin', 'Finance']);

$page_title = 'Edit Account';

// Get account ID
$account_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($account_id === 0) {
    $_SESSION['error'] = "Invalid account ID";
    header('Location: list.php');
    exit();
}

// Get account details
$query = "SELECT * FROM chart_of_accounts WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $account_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    $_SESSION['error'] = "Account not found";
    header('Location: list.php');
    exit();
}

$account = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

// Get parent accounts for dropdown
$parent_accounts_query = "SELECT id, account_code, account_name FROM chart_of_accounts 
                          WHERE is_active = TRUE AND id != ? 
                          ORDER BY account_code";
$parent_stmt = mysqli_prepare($conn, $parent_accounts_query);
mysqli_stmt_bind_param($parent_stmt, "i", $account_id);
mysqli_stmt_execute($parent_stmt);
$parent_accounts_result = mysqli_stmt_get_result($parent_stmt);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $account_code = clean($_POST['account_code']);
    $account_name = clean($_POST['account_name']);
    $account_type = clean($_POST['account_type']);
    $parent_id = !empty($_POST['parent_id']) ? intval($_POST['parent_id']) : null;
    $description = clean($_POST['description']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    $errors = [];
    
    // Validation
    if (empty($account_code)) {
        $errors[] = "Account code is required";
    }
    
    if (empty($account_name)) {
        $errors[] = "Account name is required";
    }
    
    if (!in_array($account_type, ['Asset', 'Liability', 'Equity', 'Revenue', 'Expense'])) {
        $errors[] = "Invalid account type";
    }
    
    // Check if account code already exists (excluding current account)
    if (empty($errors)) {
        $check_query = "SELECT id FROM chart_of_accounts WHERE account_code = ? AND id != ?";
        $check_stmt = mysqli_prepare($conn, $check_query);
        mysqli_stmt_bind_param($check_stmt, "si", $account_code, $account_id);
        mysqli_stmt_execute($check_stmt);
        $check_result = mysqli_stmt_get_result($check_stmt);
        
        if (mysqli_num_rows($check_result) > 0) {
            $errors[] = "Account code already exists";
        }
        mysqli_stmt_close($check_stmt);
    }
    
    // Update account if no errors
    if (empty($errors)) {
        $query = "UPDATE chart_of_accounts 
                  SET account_code = ?, account_name = ?, account_type = ?, parent_id = ?, description = ?, is_active = ?
                  WHERE id = ?";
        
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "sssisii", 
            $account_code, $account_name, $account_type, $parent_id, $description, $is_active, $account_id);
        
        if (mysqli_stmt_execute($stmt)) {
            // Log audit
            logAudit($conn, 'UPDATE', 'account', $account_id, "Updated account: $account_code - $account_name");
            
            $_SESSION['success'] = "Account updated successfully!";
            header('Location: list.php');
            exit();
        } else {
            $errors[] = "Error updating account. Please try again.";
        }
        
        mysqli_stmt_close($stmt);
    }
    
    // Display errors and refresh data
    if (!empty($errors)) {
        $_SESSION['error'] = implode('<br>', $errors);
        // Refresh account data
        $query = "SELECT * FROM chart_of_accounts WHERE id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $account_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $account = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
    }
}

include '../includes/header.php';
?>

<div class="container">
    <?php displayAlert(); ?>
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-pencil-square"></i> Edit Account</h2>
        <a href="list.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back to Chart of Accounts
        </a>
    </div>
    
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="account_code" class="form-label">Account Code <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="account_code" name="account_code" 
                                       value="<?php echo htmlspecialchars($account['account_code']); ?>" required>
                            </div>
                            <div class="col-md-8">
                                <label for="account_name" class="form-label">Account Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="account_name" name="account_name" 
                                       value="<?php echo htmlspecialchars($account['account_name']); ?>" required>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="account_type" class="form-label">Account Type <span class="text-danger">*</span></label>
                                <select class="form-select" id="account_type" name="account_type" required>
                                    <option value="Asset" <?php echo $account['account_type'] === 'Asset' ? 'selected' : ''; ?>>Asset</option>
                                    <option value="Liability" <?php echo $account['account_type'] === 'Liability' ? 'selected' : ''; ?>>Liability</option>
                                    <option value="Equity" <?php echo $account['account_type'] === 'Equity' ? 'selected' : ''; ?>>Equity</option>
                                    <option value="Revenue" <?php echo $account['account_type'] === 'Revenue' ? 'selected' : ''; ?>>Revenue</option>
                                    <option value="Expense" <?php echo $account['account_type'] === 'Expense' ? 'selected' : ''; ?>>Expense</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="parent_id" class="form-label">Parent Account</label>
                                <select class="form-select" id="parent_id" name="parent_id">
                                    <option value="">None (Top Level)</option>
                                    <?php while ($parent = mysqli_fetch_assoc($parent_accounts_result)): ?>
                                        <option value="<?php echo $parent['id']; ?>"
                                                <?php echo ($account['parent_id'] == $parent['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($parent['account_code'] . ' - ' . $parent['account_name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($account['description']); ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" 
                                       <?php echo $account['is_active'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="is_active">
                                    Active (can be used in transactions)
                                </label>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle"></i> Update Account
                            </button>
                            <a href="list.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card bg-light">
                <div class="card-header">
                    <h6 class="mb-0"><i class="bi bi-info-circle"></i> Account Information</h6>
                </div>
                <div class="card-body">
                    <p class="small mb-2">
                        <strong>Account ID:</strong> <?php echo $account['id']; ?>
                    </p>
                    <p class="small mb-2">
                        <strong>Created:</strong> <?php echo formatDate($account['created_at']); ?>
                    </p>
                    <p class="small mb-0">
                        <strong>Last Updated:</strong> <?php echo formatDate($account['updated_at']); ?>
                    </p>
                </div>
            </div>
            
            <div class="card bg-light mt-3">
                <div class="card-header bg-warning text-dark">
                    <h6 class="mb-0"><i class="bi bi-exclamation-triangle"></i> Warning</h6>
                </div>
                <div class="card-body">
                    <p class="small mb-0">
                        Be careful when editing accounts that have existing transactions. 
                        Changes will affect historical reports.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php 
mysqli_stmt_close($parent_stmt);
include '../includes/footer.php'; 
?>


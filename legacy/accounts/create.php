<?php
/**
 * Code Catalyst Labs - Create New Account
 */
require_once '../includes/config.php';
require_once '../includes/functions.php';

requireLogin();
requireRole(['Admin', 'Finance']);

$page_title = 'Create New Account';

// Get parent accounts for dropdown
$parent_accounts_query = "SELECT id, account_code, account_name FROM chart_of_accounts 
                          WHERE is_active = TRUE 
                          ORDER BY account_code";
$parent_accounts_result = mysqli_query($conn, $parent_accounts_query);

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
    
    // Check if account code already exists
    if (empty($errors)) {
        $check_query = "SELECT id FROM chart_of_accounts WHERE account_code = ?";
        $check_stmt = mysqli_prepare($conn, $check_query);
        mysqli_stmt_bind_param($check_stmt, "s", $account_code);
        mysqli_stmt_execute($check_stmt);
        $check_result = mysqli_stmt_get_result($check_stmt);
        
        if (mysqli_num_rows($check_result) > 0) {
            $errors[] = "Account code already exists";
        }
        mysqli_stmt_close($check_stmt);
    }
    
    // Insert account if no errors
    if (empty($errors)) {
        $query = "INSERT INTO chart_of_accounts (account_code, account_name, account_type, parent_id, description, is_active) 
                  VALUES (?, ?, ?, ?, ?, ?)";
        
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "sssisi", 
            $account_code, $account_name, $account_type, $parent_id, $description, $is_active);
        
        if (mysqli_stmt_execute($stmt)) {
            $account_id = mysqli_insert_id($conn);
            
            // Log audit
            logAudit($conn, 'CREATE', 'account', $account_id, "Created account: $account_code - $account_name");
            
            $_SESSION['success'] = "Account created successfully!";
            header('Location: list.php');
            exit();
        } else {
            $errors[] = "Error creating account. Please try again.";
        }
        
        mysqli_stmt_close($stmt);
    }
    
    // Display errors
    if (!empty($errors)) {
        $_SESSION['error'] = implode('<br>', $errors);
    }
}

include '../includes/header.php';
?>

<div class="container">
    <?php displayAlert(); ?>
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-journal-plus"></i> Create New Account</h2>
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
                                       value="<?php echo isset($_POST['account_code']) ? htmlspecialchars($_POST['account_code']) : ''; ?>" 
                                       placeholder="e.g., 1210" required>
                                <div class="form-text">Unique numeric code</div>
                            </div>
                            <div class="col-md-8">
                                <label for="account_name" class="form-label">Account Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="account_name" name="account_name" 
                                       value="<?php echo isset($_POST['account_name']) ? htmlspecialchars($_POST['account_name']) : ''; ?>" 
                                       placeholder="e.g., Office Equipment" required>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="account_type" class="form-label">Account Type <span class="text-danger">*</span></label>
                                <select class="form-select" id="account_type" name="account_type" required>
                                    <option value="">Select Type</option>
                                    <option value="Asset" <?php echo (isset($_POST['account_type']) && $_POST['account_type'] === 'Asset') ? 'selected' : ''; ?>>
                                        Asset
                                    </option>
                                    <option value="Liability" <?php echo (isset($_POST['account_type']) && $_POST['account_type'] === 'Liability') ? 'selected' : ''; ?>>
                                        Liability
                                    </option>
                                    <option value="Equity" <?php echo (isset($_POST['account_type']) && $_POST['account_type'] === 'Equity') ? 'selected' : ''; ?>>
                                        Equity
                                    </option>
                                    <option value="Revenue" <?php echo (isset($_POST['account_type']) && $_POST['account_type'] === 'Revenue') ? 'selected' : ''; ?>>
                                        Revenue
                                    </option>
                                    <option value="Expense" <?php echo (isset($_POST['account_type']) && $_POST['account_type'] === 'Expense') ? 'selected' : ''; ?>>
                                        Expense
                                    </option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="parent_id" class="form-label">Parent Account (Optional)</label>
                                <select class="form-select" id="parent_id" name="parent_id">
                                    <option value="">None (Top Level)</option>
                                    <?php while ($parent = mysqli_fetch_assoc($parent_accounts_result)): ?>
                                        <option value="<?php echo $parent['id']; ?>"
                                                <?php echo (isset($_POST['parent_id']) && $_POST['parent_id'] == $parent['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($parent['account_code'] . ' - ' . $parent['account_name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                                <div class="form-text">For sub-accounts</div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                            <div class="form-text">Brief description of what this account tracks</div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" 
                                       <?php echo (!isset($_POST['is_active']) || isset($_POST['is_active'])) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="is_active">
                                    Active (can be used in transactions)
                                </label>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle"></i> Create Account
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
                    <h6 class="mb-0"><i class="bi bi-info-circle"></i> Account Code Guidelines</h6>
                </div>
                <div class="card-body">
                    <p class="small mb-2"><strong>Standard Ranges:</strong></p>
                    <ul class="small mb-3">
                        <li><strong>1000-1999:</strong> Assets</li>
                        <li><strong>2000-2999:</strong> Liabilities</li>
                        <li><strong>3000-3999:</strong> Equity</li>
                        <li><strong>4000-4999:</strong> Revenue</li>
                        <li><strong>5000-5999:</strong> Expenses</li>
                    </ul>
                    
                    <p class="small mb-2"><strong>Tips:</strong></p>
                    <ul class="small mb-0">
                        <li>Use consistent numbering</li>
                        <li>Leave gaps for future accounts</li>
                        <li>Group similar accounts together</li>
                        <li>Use descriptive names</li>
                    </ul>
                </div>
            </div>
            
            <div class="card bg-light mt-3">
                <div class="card-header">
                    <h6 class="mb-0"><i class="bi bi-lightbulb"></i> Account Types</h6>
                </div>
                <div class="card-body">
                    <p class="small mb-2"><strong>Asset:</strong> What you own (cash, equipment, etc.)</p>
                    <p class="small mb-2"><strong>Liability:</strong> What you owe (loans, payables)</p>
                    <p class="small mb-2"><strong>Equity:</strong> Owner's stake in the business</p>
                    <p class="small mb-2"><strong>Revenue:</strong> Income earned</p>
                    <p class="small mb-0"><strong>Expense:</strong> Costs incurred</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>


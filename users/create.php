<?php
/**
 * Code Catalyst Labs - Create New User
 * Admin only
 */
require_once '../includes/config.php';
require_once '../includes/functions.php';

requireRole('Admin');

$page_title = 'Create New User';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = clean($_POST['username']);
    $email = clean($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = clean($_POST['role']);
    $status = clean($_POST['status']);
    
    $errors = [];
    
    // Validation
    if (empty($username)) {
        $errors[] = "Username is required";
    } elseif (strlen($username) < 3) {
        $errors[] = "Username must be at least 3 characters";
    }
    
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    
    if (empty($password)) {
        $errors[] = "Password is required";
    } elseif (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters";
    } elseif ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }
    
    if (!in_array($role, ['Admin', 'Finance', 'Sales'])) {
        $errors[] = "Invalid role selected";
    }
    
    // Check if username or email already exists
    if (empty($errors)) {
        $check_query = "SELECT id FROM users WHERE username = ? OR email = ?";
        $check_stmt = mysqli_prepare($conn, $check_query);
        mysqli_stmt_bind_param($check_stmt, "ss", $username, $email);
        mysqli_stmt_execute($check_stmt);
        $check_result = mysqli_stmt_get_result($check_stmt);
        
        if (mysqli_num_rows($check_result) > 0) {
            $errors[] = "Username or email already exists";
        }
        mysqli_stmt_close($check_stmt);
    }
    
    // Insert user if no errors
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $query = "INSERT INTO users (username, email, password, role, status) VALUES (?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "sssss", $username, $email, $hashed_password, $role, $status);
        
        if (mysqli_stmt_execute($stmt)) {
            $user_id = mysqli_insert_id($conn);
            
            // Log audit
            logAudit($conn, 'CREATE', 'user', $user_id, "Created user: $username");
            
            $_SESSION['success'] = "User created successfully!";
            header('Location: list.php');
            exit();
        } else {
            $errors[] = "Error creating user. Please try again.";
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
        <h2><i class="bi bi-person-plus"></i> Create New User</h2>
        <a href="list.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back to Users
        </a>
    </div>
    
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="username" name="username" 
                                       value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" 
                                       required>
                                <div class="form-text">Minimum 3 characters</div>
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" 
                                       required>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" id="password" name="password" required>
                                <div class="form-text">Minimum 6 characters</div>
                            </div>
                            <div class="col-md-6">
                                <label for="confirm_password" class="form-label">Confirm Password <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="role" class="form-label">Role <span class="text-danger">*</span></label>
                                <select class="form-select" id="role" name="role" required>
                                    <option value="">Select Role</option>
                                    <option value="Admin" <?php echo (isset($_POST['role']) && $_POST['role'] === 'Admin') ? 'selected' : ''; ?>>
                                        Admin - Full system access
                                    </option>
                                    <option value="Finance" <?php echo (isset($_POST['role']) && $_POST['role'] === 'Finance') ? 'selected' : ''; ?>>
                                        Finance - Accounting & financial management
                                    </option>
                                    <option value="Sales" <?php echo (isset($_POST['role']) && $_POST['role'] === 'Sales') ? 'selected' : ''; ?>>
                                        Sales - Quotations & invoices
                                    </option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                                <select class="form-select" id="status" name="status" required>
                                    <option value="Active" <?php echo (!isset($_POST['status']) || $_POST['status'] === 'Active') ? 'selected' : ''; ?>>
                                        Active
                                    </option>
                                    <option value="Inactive" <?php echo (isset($_POST['status']) && $_POST['status'] === 'Inactive') ? 'selected' : ''; ?>>
                                        Inactive
                                    </option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle"></i> Create User
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
                    <h6 class="mb-0"><i class="bi bi-info-circle"></i> Role Permissions</h6>
                </div>
                <div class="card-body">
                    <h6 class="text-danger"><i class="bi bi-shield-fill-check"></i> Admin</h6>
                    <ul class="small">
                        <li>Full system access</li>
                        <li>User management</li>
                        <li>All financial operations</li>
                        <li>System settings</li>
                    </ul>
                    
                    <h6 class="text-success mt-3"><i class="bi bi-cash-stack"></i> Finance</h6>
                    <ul class="small">
                        <li>Accounting & cashbook</li>
                        <li>Expense management</li>
                        <li>Asset management</li>
                        <li>Financial reports</li>
                        <li>Invoice management</li>
                    </ul>
                    
                    <h6 class="text-primary mt-3"><i class="bi bi-briefcase"></i> Sales</h6>
                    <ul class="small">
                        <li>Create quotations</li>
                        <li>Create invoices</li>
                        <li>View clients</li>
                        <li>Limited reports</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>


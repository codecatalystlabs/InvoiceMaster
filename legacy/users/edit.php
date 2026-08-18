<?php
/**
 * Code Catalyst Labs - Edit User
 * Admin only
 */
require_once '../includes/config.php';
require_once '../includes/functions.php';

requireRole('Admin');

$page_title = 'Edit User';

// Get user ID
$user_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($user_id === 0) {
    $_SESSION['error'] = "Invalid user ID";
    header('Location: list.php');
    exit();
}

// Get user details
$query = "SELECT * FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    $_SESSION['error'] = "User not found";
    header('Location: list.php');
    exit();
}

$user = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = clean($_POST['username']);
    $email = clean($_POST['email']);
    $role = clean($_POST['role']);
    $status = clean($_POST['status']);
    $change_password = isset($_POST['change_password']) && $_POST['change_password'] === '1';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
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
    
    if ($change_password) {
        if (empty($password)) {
            $errors[] = "Password is required";
        } elseif (strlen($password) < 6) {
            $errors[] = "Password must be at least 6 characters";
        } elseif ($password !== $confirm_password) {
            $errors[] = "Passwords do not match";
        }
    }
    
    if (!in_array($role, ['Admin', 'Finance', 'Sales'])) {
        $errors[] = "Invalid role selected";
    }
    
    // Check if username or email already exists (excluding current user)
    if (empty($errors)) {
        $check_query = "SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?";
        $check_stmt = mysqli_prepare($conn, $check_query);
        mysqli_stmt_bind_param($check_stmt, "ssi", $username, $email, $user_id);
        mysqli_stmt_execute($check_stmt);
        $check_result = mysqli_stmt_get_result($check_stmt);
        
        if (mysqli_num_rows($check_result) > 0) {
            $errors[] = "Username or email already exists";
        }
        mysqli_stmt_close($check_stmt);
    }
    
    // Update user if no errors
    if (empty($errors)) {
        if ($change_password) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $update_query = "UPDATE users SET username = ?, email = ?, password = ?, role = ?, status = ? WHERE id = ?";
            $update_stmt = mysqli_prepare($conn, $update_query);
            mysqli_stmt_bind_param($update_stmt, "sssssi", $username, $email, $hashed_password, $role, $status, $user_id);
        } else {
            $update_query = "UPDATE users SET username = ?, email = ?, role = ?, status = ? WHERE id = ?";
            $update_stmt = mysqli_prepare($conn, $update_query);
            mysqli_stmt_bind_param($update_stmt, "ssssi", $username, $email, $role, $status, $user_id);
        }
        
        if (mysqli_stmt_execute($update_stmt)) {
            // Log audit
            logAudit($conn, 'UPDATE', 'user', $user_id, "Updated user: $username");
            
            $_SESSION['success'] = "User updated successfully!";
            header('Location: list.php');
            exit();
        } else {
            $errors[] = "Error updating user. Please try again.";
        }
        
        mysqli_stmt_close($update_stmt);
    }
    
    // Display errors
    if (!empty($errors)) {
        $_SESSION['error'] = implode('<br>', $errors);
    } else {
        // Refresh user data
        $query = "SELECT * FROM users WHERE id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
    }
}

include '../includes/header.php';
?>

<div class="container">
    <?php displayAlert(); ?>
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-pencil-square"></i> Edit User</h2>
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
                                       value="<?php echo htmlspecialchars($user['username']); ?>" required>
                                <div class="form-text">Minimum 3 characters</div>
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="change_password" 
                                       name="change_password" value="1" onchange="togglePasswordFields()">
                                <label class="form-check-label" for="change_password">
                                    Change Password
                                </label>
                            </div>
                        </div>
                        
                        <div id="password-fields" style="display: none;">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="password" class="form-label">New Password</label>
                                    <input type="password" class="form-control" id="password" name="password">
                                    <div class="form-text">Minimum 6 characters</div>
                                </div>
                                <div class="col-md-6">
                                    <label for="confirm_password" class="form-label">Confirm New Password</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="role" class="form-label">Role <span class="text-danger">*</span></label>
                                <select class="form-select" id="role" name="role" required 
                                        <?php echo ($user['id'] == $_SESSION['user_id']) ? 'disabled' : ''; ?>>
                                    <option value="Admin" <?php echo $user['role'] === 'Admin' ? 'selected' : ''; ?>>
                                        Admin - Full system access
                                    </option>
                                    <option value="Finance" <?php echo $user['role'] === 'Finance' ? 'selected' : ''; ?>>
                                        Finance - Accounting & financial management
                                    </option>
                                    <option value="Sales" <?php echo $user['role'] === 'Sales' ? 'selected' : ''; ?>>
                                        Sales - Quotations & invoices
                                    </option>
                                </select>
                                <?php if ($user['id'] == $_SESSION['user_id']): ?>
                                    <input type="hidden" name="role" value="<?php echo $user['role']; ?>">
                                    <div class="form-text text-warning">You cannot change your own role</div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6">
                                <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                                <select class="form-select" id="status" name="status" required
                                        <?php echo ($user['id'] == $_SESSION['user_id']) ? 'disabled' : ''; ?>>
                                    <option value="Active" <?php echo $user['status'] === 'Active' ? 'selected' : ''; ?>>
                                        Active
                                    </option>
                                    <option value="Inactive" <?php echo $user['status'] === 'Inactive' ? 'selected' : ''; ?>>
                                        Inactive
                                    </option>
                                </select>
                                <?php if ($user['id'] == $_SESSION['user_id']): ?>
                                    <input type="hidden" name="status" value="<?php echo $user['status']; ?>">
                                    <div class="form-text text-warning">You cannot deactivate yourself</div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle"></i> Update User
                            </button>
                            <a href="list.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card bg-light mb-3">
                <div class="card-header">
                    <h6 class="mb-0"><i class="bi bi-info-circle"></i> User Information</h6>
                </div>
                <div class="card-body">
                    <p class="small mb-2"><strong>User ID:</strong> <?php echo $user['id']; ?></p>
                    <p class="small mb-2"><strong>Created:</strong> <?php echo formatDate($user['created_at']); ?></p>
                    <p class="small mb-0"><strong>Current Status:</strong> 
                        <span class="badge bg-<?php echo getStatusBadge($user['status']); ?>">
                            <?php echo $user['status']; ?>
                        </span>
                    </p>
                </div>
            </div>
            
            <div class="card bg-light">
                <div class="card-header">
                    <h6 class="mb-0"><i class="bi bi-shield-exclamation"></i> Security Note</h6>
                </div>
                <div class="card-body">
                    <p class="small mb-0">
                        <i class="bi bi-exclamation-triangle text-warning"></i> 
                        Leave password fields empty if you don't want to change the password.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function togglePasswordFields() {
    const checkbox = document.getElementById('change_password');
    const fields = document.getElementById('password-fields');
    const passwordInput = document.getElementById('password');
    const confirmInput = document.getElementById('confirm_password');
    
    if (checkbox.checked) {
        fields.style.display = 'block';
        passwordInput.required = true;
        confirmInput.required = true;
    } else {
        fields.style.display = 'none';
        passwordInput.required = false;
        confirmInput.required = false;
        passwordInput.value = '';
        confirmInput.value = '';
    }
}
</script>

<?php include '../includes/footer.php'; ?>


<?php
/**
 * Code Catalyst Labs - Edit Asset
 */
require_once '../includes/config.php';
require_once '../includes/functions.php';

requireLogin();
requireRole(['Admin', 'Finance']);

$page_title = 'Edit Asset';

// Get asset ID
$asset_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($asset_id === 0) {
    $_SESSION['error'] = "Invalid asset ID";
    header('Location: list.php');
    exit();
}

// Get asset details
$query = "SELECT * FROM assets WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $asset_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    $_SESSION['error'] = "Asset not found";
    header('Location: list.php');
    exit();
}

$asset = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

// Get users for assignment
$users_query = "SELECT id, username FROM users WHERE status = 'Active' ORDER BY username";
$users_result = mysqli_query($conn, $users_query);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $asset_name = clean($_POST['asset_name']);
    $category = clean($_POST['category']);
    $purchase_date = clean($_POST['purchase_date']);
    $purchase_price = floatval($_POST['purchase_price']);
    $current_value = floatval($_POST['current_value']);
    $depreciation_rate = floatval($_POST['depreciation_rate']);
    $depreciation_method = clean($_POST['depreciation_method']);
    $location = clean($_POST['location']);
    $condition_status = clean($_POST['condition_status']);
    $description = clean($_POST['description']);
    $serial_number = clean($_POST['serial_number']);
    $warranty_expiry = !empty($_POST['warranty_expiry']) ? clean($_POST['warranty_expiry']) : null;
    $assigned_to = !empty($_POST['assigned_to']) ? intval($_POST['assigned_to']) : null;
    
    $errors = [];
    
    // Validation
    if (empty($asset_name)) $errors[] = "Asset name is required";
    if (empty($category)) $errors[] = "Category is required";
    if (empty($purchase_date)) $errors[] = "Purchase date is required";
    if ($purchase_price <= 0) $errors[] = "Purchase price must be greater than 0";
    if ($current_value < 0) $errors[] = "Current value cannot be negative";
    
    // Update asset if no errors
    if (empty($errors)) {
        $query = "UPDATE assets SET asset_name = ?, category = ?, purchase_date = ?, purchase_price = ?, 
                  current_value = ?, depreciation_rate = ?, depreciation_method = ?, location = ?, 
                  condition_status = ?, description = ?, serial_number = ?, warranty_expiry = ?, assigned_to = ?
                  WHERE id = ?";
        
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "sssdddssssssii", 
            $asset_name, $category, $purchase_date, $purchase_price, $current_value, $depreciation_rate,
            $depreciation_method, $location, $condition_status, $description, $serial_number, 
            $warranty_expiry, $assigned_to, $asset_id);
        
        if (mysqli_stmt_execute($stmt)) {
            // If value changed, create valuation record
            if ($current_value != $asset['current_value']) {
                $val_query = "INSERT INTO asset_valuations (asset_id, valuation_date, valuation_amount, 
                             valuation_reason, valued_by) VALUES (?, CURDATE(), ?, 'Updated value', ?)";
                $val_stmt = mysqli_prepare($conn, $val_query);
                mysqli_stmt_bind_param($val_stmt, "idi", $asset_id, $current_value, $_SESSION['user_id']);
                mysqli_stmt_execute($val_stmt);
                mysqli_stmt_close($val_stmt);
            }
            
            // Log audit
            logAudit($conn, 'UPDATE', 'asset', $asset_id, "Updated asset: {$asset['asset_number']}");
            
            $_SESSION['success'] = "Asset updated successfully!";
            header('Location: view.php?id=' . $asset_id);
            exit();
        } else {
            $errors[] = "Error updating asset. Please try again.";
        }
        
        mysqli_stmt_close($stmt);
    }
    
    if (!empty($errors)) {
        $_SESSION['error'] = implode('<br>', $errors);
        // Refresh asset data
        $query = "SELECT * FROM assets WHERE id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $asset_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $asset = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
    }
}

include '../includes/header.php';
?>

<div class="container">
    <?php displayAlert(); ?>
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-pencil-square"></i> Edit Asset</h2>
        <a href="view.php?id=<?php echo $asset_id; ?>" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back to View
        </a>
    </div>
    
    <div class="card">
        <div class="card-body">
            <form method="POST" action="">
                <div class="mb-3">
                    <label class="form-label">Asset Number</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($asset['asset_number']); ?>" disabled>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="asset_name" class="form-label">Asset Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="asset_name" name="asset_name" 
                               value="<?php echo htmlspecialchars($asset['asset_name']); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="category" class="form-label">Category <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="category" name="category" list="category-list"
                               value="<?php echo htmlspecialchars($asset['category']); ?>" required>
                        <datalist id="category-list">
                            <option value="Equipment">
                            <option value="Furniture">
                            <option value="Vehicles">
                            <option value="Buildings">
                            <option value="Electronics">
                            <option value="Software">
                        </datalist>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="purchase_date" class="form-label">Purchase Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="purchase_date" name="purchase_date" 
                               value="<?php echo $asset['purchase_date']; ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label for="purchase_price" class="form-label">Purchase Price <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="purchase_price" name="purchase_price" 
                               step="0.01" min="0" value="<?php echo $asset['purchase_price']; ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label for="current_value" class="form-label">Current Value <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="current_value" name="current_value" 
                               step="0.01" min="0" value="<?php echo $asset['current_value']; ?>" required>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="depreciation_method" class="form-label">Depreciation Method</label>
                        <select class="form-select" id="depreciation_method" name="depreciation_method">
                            <option value="None" <?php echo $asset['depreciation_method'] === 'None' ? 'selected' : ''; ?>>None</option>
                            <option value="Straight Line" <?php echo $asset['depreciation_method'] === 'Straight Line' ? 'selected' : ''; ?>>Straight Line</option>
                            <option value="Declining Balance" <?php echo $asset['depreciation_method'] === 'Declining Balance' ? 'selected' : ''; ?>>Declining Balance</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="depreciation_rate" class="form-label">Depreciation Rate (%)</label>
                        <input type="number" class="form-control" id="depreciation_rate" name="depreciation_rate" 
                               step="0.01" min="0" max="100" value="<?php echo $asset['depreciation_rate']; ?>">
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="serial_number" class="form-label">Serial Number</label>
                        <input type="text" class="form-control" id="serial_number" name="serial_number" 
                               value="<?php echo htmlspecialchars($asset['serial_number']); ?>">
                    </div>
                    <div class="col-md-6">
                        <label for="warranty_expiry" class="form-label">Warranty Expiry Date</label>
                        <input type="date" class="form-control" id="warranty_expiry" name="warranty_expiry" 
                               value="<?php echo $asset['warranty_expiry']; ?>">
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="condition_status" class="form-label">Condition <span class="text-danger">*</span></label>
                        <select class="form-select" id="condition_status" name="condition_status" required>
                            <option value="Excellent" <?php echo $asset['condition_status'] === 'Excellent' ? 'selected' : ''; ?>>Excellent</option>
                            <option value="Good" <?php echo $asset['condition_status'] === 'Good' ? 'selected' : ''; ?>>Good</option>
                            <option value="Fair" <?php echo $asset['condition_status'] === 'Fair' ? 'selected' : ''; ?>>Fair</option>
                            <option value="Poor" <?php echo $asset['condition_status'] === 'Poor' ? 'selected' : ''; ?>>Poor</option>
                            <option value="Damaged" <?php echo $asset['condition_status'] === 'Damaged' ? 'selected' : ''; ?>>Damaged</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="assigned_to" class="form-label">Assigned To</label>
                        <select class="form-select" id="assigned_to" name="assigned_to">
                            <option value="">Not assigned</option>
                            <?php while ($user = mysqli_fetch_assoc($users_result)): ?>
                                <option value="<?php echo $user['id']; ?>" <?php echo $asset['assigned_to'] == $user['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($user['username']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="location" class="form-label">Location</label>
                    <input type="text" class="form-control" id="location" name="location" 
                           value="<?php echo htmlspecialchars($asset['location']); ?>">
                </div>
                
                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($asset['description']); ?></textarea>
                </div>
                
                <div class="d-flex justify-content-between">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle"></i> Update Asset
                    </button>
                    <a href="view.php?id=<?php echo $asset_id; ?>" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>


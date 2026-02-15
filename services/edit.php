<?php
/**
 * Code Catalyst Labs - Edit Service
 */
require_once '../includes/config.php';
require_once '../includes/functions.php';

requireLogin();
requireRole(['Admin', 'Finance']);

$page_title = 'Edit Service';

// Get service ID
$service_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($service_id === 0) {
    $_SESSION['error'] = "Invalid service ID";
    header('Location: list.php');
    exit();
}

// Get service details
$query = "SELECT * FROM services WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $service_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    $_SESSION['error'] = "Service not found";
    header('Location: list.php');
    exit();
}

$service = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $service_name = clean($_POST['service_name']);
    $provider_name = clean($_POST['provider_name']);
    $provider_contact = clean($_POST['provider_contact']);
    $category = clean($_POST['category']);
    $cost = floatval($_POST['cost']);
    $billing_frequency = clean($_POST['billing_frequency']);
    $start_date = clean($_POST['start_date']);
    $end_date = !empty($_POST['end_date']) ? clean($_POST['end_date']) : null;
    $next_billing_date = clean($_POST['next_billing_date']);
    $auto_renew = isset($_POST['auto_renew']) ? 1 : 0;
    $status = clean($_POST['status']);
    $description = clean($_POST['description']);
    
    $errors = [];
    
    // Validation
    if (empty($service_name)) $errors[] = "Service name is required";
    if (empty($provider_name)) $errors[] = "Provider name is required";
    if ($cost <= 0) $errors[] = "Cost must be greater than 0";
    if (empty($start_date)) $errors[] = "Start date is required";
    if (empty($next_billing_date)) $errors[] = "Next billing date is required";
    
    // Update service if no errors
    if (empty($errors)) {
        $query = "UPDATE services SET service_name = ?, provider_name = ?, provider_contact = ?, 
                  category = ?, cost = ?, billing_frequency = ?, start_date = ?, end_date = ?, 
                  next_billing_date = ?, auto_renew = ?, status = ?, description = ?
                  WHERE id = ?";
        
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "ssssdsssssssi", 
            $service_name, $provider_name, $provider_contact, $category, $cost, $billing_frequency,
            $start_date, $end_date, $next_billing_date, $auto_renew, $status, $description, $service_id);
        
        if (mysqli_stmt_execute($stmt)) {
            // Log audit
            logAudit($conn, 'UPDATE', 'service', $service_id, "Updated service: {$service['service_number']}");
            
            $_SESSION['success'] = "Service updated successfully!";
            header('Location: view.php?id=' . $service_id);
            exit();
        } else {
            $errors[] = "Error updating service. Please try again.";
        }
        
        mysqli_stmt_close($stmt);
    }
    
    if (!empty($errors)) {
        $_SESSION['error'] = implode('<br>', $errors);
        // Refresh service data
        $query = "SELECT * FROM services WHERE id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $service_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $service = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
    }
}

include '../includes/header.php';
?>

<div class="container">
    <?php displayAlert(); ?>
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-pencil-square"></i> Edit Service</h2>
        <a href="view.php?id=<?php echo $service_id; ?>" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back to View
        </a>
    </div>
    
    <div class="card">
        <div class="card-body">
            <form method="POST" action="">
                <div class="mb-3">
                    <label class="form-label">Service Number</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($service['service_number']); ?>" disabled>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="service_name" class="form-label">Service Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="service_name" name="service_name" 
                               value="<?php echo htmlspecialchars($service['service_name']); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="category" class="form-label">Category</label>
                        <input type="text" class="form-control" id="category" name="category" list="category-list"
                               value="<?php echo htmlspecialchars($service['category']); ?>">
                        <datalist id="category-list">
                            <option value="Software">
                            <option value="Hosting">
                            <option value="Cloud Services">
                            <option value="Marketing">
                            <option value="Communication">
                            <option value="Security">
                        </datalist>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="provider_name" class="form-label">Provider Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="provider_name" name="provider_name" 
                               value="<?php echo htmlspecialchars($service['provider_name']); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="provider_contact" class="form-label">Provider Contact</label>
                        <input type="text" class="form-control" id="provider_contact" name="provider_contact" 
                               value="<?php echo htmlspecialchars($service['provider_contact']); ?>"
                               placeholder="Email or phone">
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="cost" class="form-label">Cost (<?php echo CURRENCY_CODE; ?>) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="cost" name="cost" 
                               step="0.01" min="0" value="<?php echo $service['cost']; ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="billing_frequency" class="form-label">Billing Frequency <span class="text-danger">*</span></label>
                        <select class="form-select" id="billing_frequency" name="billing_frequency" required>
                            <option value="Monthly" <?php echo $service['billing_frequency'] === 'Monthly' ? 'selected' : ''; ?>>Monthly</option>
                            <option value="Quarterly" <?php echo $service['billing_frequency'] === 'Quarterly' ? 'selected' : ''; ?>>Quarterly</option>
                            <option value="Yearly" <?php echo $service['billing_frequency'] === 'Yearly' ? 'selected' : ''; ?>>Yearly</option>
                            <option value="Weekly" <?php echo $service['billing_frequency'] === 'Weekly' ? 'selected' : ''; ?>>Weekly</option>
                            <option value="Daily" <?php echo $service['billing_frequency'] === 'Daily' ? 'selected' : ''; ?>>Daily</option>
                        </select>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="start_date" class="form-label">Start Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="start_date" name="start_date" 
                               value="<?php echo $service['start_date']; ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label for="next_billing_date" class="form-label">Next Billing Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="next_billing_date" name="next_billing_date" 
                               value="<?php echo $service['next_billing_date']; ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label for="end_date" class="form-label">End Date (Optional)</label>
                        <input type="date" class="form-control" id="end_date" name="end_date" 
                               value="<?php echo $service['end_date']; ?>">
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                        <select class="form-select" id="status" name="status" required>
                            <option value="Active" <?php echo $service['status'] === 'Active' ? 'selected' : ''; ?>>Active</option>
                            <option value="Suspended" <?php echo $service['status'] === 'Suspended' ? 'selected' : ''; ?>>Suspended</option>
                            <option value="Cancelled" <?php echo $service['status'] === 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            <option value="Expired" <?php echo $service['status'] === 'Expired' ? 'selected' : ''; ?>>Expired</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <div class="pt-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="auto_renew" name="auto_renew" value="1" 
                                       <?php echo $service['auto_renew'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="auto_renew">
                                    Auto-renew subscription
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($service['description']); ?></textarea>
                </div>
                
                <div class="d-flex justify-content-between">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle"></i> Update Service
                    </button>
                    <a href="view.php?id=<?php echo $service_id; ?>" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>


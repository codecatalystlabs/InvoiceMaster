<?php
/**
 * Code Catalyst Labs - Create New Service
 */
require_once '../includes/config.php';
require_once '../includes/functions.php';

requireLogin();
requireRole(['Admin', 'Finance']);

$page_title = 'Create New Service';

// Generate service number
function generateServiceNumber($conn) {
    $prefix = 'SVC';
    $year = date('Y');
    
    $query = "SELECT service_number FROM services 
              WHERE service_number LIKE '$prefix-$year-%' 
              ORDER BY id DESC LIMIT 1";
    $result = mysqli_query($conn, $query);
    
    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $lastNumber = (int)substr($row['service_number'], -4);
        $newNumber = $lastNumber + 1;
    } else {
        $newNumber = 1;
    }
    
    return $prefix . '-' . $year . '-' . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $service_number = generateServiceNumber($conn);
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
    
    // Insert service if no errors
    if (empty($errors)) {
        $query = "INSERT INTO services (service_number, service_name, provider_name, provider_contact, 
                  category, cost, billing_frequency, start_date, end_date, next_billing_date, 
                  auto_renew, status, description, created_by) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "sssssdsssssssi", 
            $service_number, $service_name, $provider_name, $provider_contact, $category,
            $cost, $billing_frequency, $start_date, $end_date, $next_billing_date,
            $auto_renew, $status, $description, $_SESSION['user_id']);
        
        if (mysqli_stmt_execute($stmt)) {
            $service_id = mysqli_insert_id($conn);
            
            // Log audit
            logAudit($conn, 'CREATE', 'service', $service_id, "Created service: $service_number");
            
            $_SESSION['success'] = "Service created successfully!";
            header('Location: view.php?id=' . $service_id);
            exit();
        } else {
            $errors[] = "Error creating service. Please try again.";
        }
        
        mysqli_stmt_close($stmt);
    }
    
    if (!empty($errors)) {
        $_SESSION['error'] = implode('<br>', $errors);
    }
}

include '../includes/header.php';
?>

<div class="container">
    <?php displayAlert(); ?>
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-arrow-repeat"></i> Create New Service</h2>
        <a href="list.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back to Services
        </a>
    </div>
    
    <div class="card">
        <div class="card-body">
            <form method="POST" action="">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="service_name" class="form-label">Service Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="service_name" name="service_name" 
                               value="<?php echo isset($_POST['service_name']) ? htmlspecialchars($_POST['service_name']) : ''; ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="category" class="form-label">Category</label>
                        <input type="text" class="form-control" id="category" name="category" list="category-list"
                               value="<?php echo isset($_POST['category']) ? htmlspecialchars($_POST['category']) : ''; ?>">
                        <datalist id="category-list">
                            <option value="Software">
                            <option value="Hosting">
                            <option value="Cloud Services">
                            <option value="Marketing">
                            <option value="Communication">
                            <option value="Security">
                            <option value="Other">
                        </datalist>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="provider_name" class="form-label">Provider Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="provider_name" name="provider_name" 
                               value="<?php echo isset($_POST['provider_name']) ? htmlspecialchars($_POST['provider_name']) : ''; ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="provider_contact" class="form-label">Provider Contact</label>
                        <input type="text" class="form-control" id="provider_contact" name="provider_contact" 
                               value="<?php echo isset($_POST['provider_contact']) ? htmlspecialchars($_POST['provider_contact']) : ''; ?>"
                               placeholder="Email or phone">
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="cost" class="form-label">Cost (<?php echo CURRENCY_CODE; ?>) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="cost" name="cost" 
                               step="0.01" min="0" value="<?php echo isset($_POST['cost']) ? $_POST['cost'] : ''; ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="billing_frequency" class="form-label">Billing Frequency <span class="text-danger">*</span></label>
                        <select class="form-select" id="billing_frequency" name="billing_frequency" required>
                            <option value="Monthly" selected>Monthly</option>
                            <option value="Quarterly">Quarterly</option>
                            <option value="Yearly">Yearly</option>
                            <option value="Weekly">Weekly</option>
                            <option value="Daily">Daily</option>
                        </select>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="start_date" class="form-label">Start Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="start_date" name="start_date" 
                               value="<?php echo isset($_POST['start_date']) ? $_POST['start_date'] : date('Y-m-d'); ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label for="next_billing_date" class="form-label">Next Billing Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="next_billing_date" name="next_billing_date" 
                               value="<?php echo isset($_POST['next_billing_date']) ? $_POST['next_billing_date'] : ''; ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label for="end_date" class="form-label">End Date (Optional)</label>
                        <input type="date" class="form-control" id="end_date" name="end_date" 
                               value="<?php echo isset($_POST['end_date']) ? $_POST['end_date'] : ''; ?>">
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                        <select class="form-select" id="status" name="status" required>
                            <option value="Active" selected>Active</option>
                            <option value="Suspended">Suspended</option>
                            <option value="Cancelled">Cancelled</option>
                            <option value="Expired">Expired</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <div class="pt-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="auto_renew" name="auto_renew" value="1" checked>
                                <label class="form-check-label" for="auto_renew">
                                    Auto-renew subscription
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="3"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                </div>
                
                <div class="d-flex justify-content-between">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle"></i> Create Service
                    </button>
                    <a href="list.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>


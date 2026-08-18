<?php
/**
 * Code Catalyst Labs - View Asset
 */
require_once '../includes/config.php';
require_once '../includes/functions.php';

requireLogin();
requireRole(['Admin', 'Finance']);

$page_title = 'View Asset';

// Get asset ID
$asset_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($asset_id === 0) {
    $_SESSION['error'] = "Invalid asset ID";
    header('Location: list.php');
    exit();
}

// Get asset details
$query = "SELECT a.*, u.username as assigned_to_name, c.username as creator 
          FROM assets a 
          LEFT JOIN users u ON a.assigned_to = u.id
          LEFT JOIN users c ON a.created_by = c.id
          WHERE a.id = ?";
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

// Get valuation history
$val_query = "SELECT av.*, u.username as valued_by_name 
              FROM asset_valuations av
              LEFT JOIN users u ON av.valued_by = u.id
              WHERE av.asset_id = ?
              ORDER BY av.valuation_date DESC";
$val_stmt = mysqli_prepare($conn, $val_query);
mysqli_stmt_bind_param($val_stmt, "i", $asset_id);
mysqli_stmt_execute($val_stmt);
$valuations_result = mysqli_stmt_get_result($val_stmt);

include '../includes/header.php';
?>

<div class="container">
    <?php displayAlert(); ?>
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-box-seam"></i> Asset Details</h2>
        <div>
            <a href="edit.php?id=<?php echo $asset['id']; ?>" class="btn btn-primary">
                <i class="bi bi-pencil"></i> Edit
            </a>
            <a href="list.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back to List
            </a>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><?php echo htmlspecialchars($asset['asset_name']); ?></h5>
                        <?php 
                        $condition_colors = [
                            'Excellent' => 'success',
                            'Good' => 'primary',
                            'Fair' => 'warning',
                            'Poor' => 'danger',
                            'Damaged' => 'dark'
                        ];
                        $color = $condition_colors[$asset['condition_status']] ?? 'secondary';
                        ?>
                        <span class="badge bg-<?php echo $color; ?> fs-6">
                            <?php echo $asset['condition_status']; ?>
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p><strong><i class="bi bi-hash"></i> Asset Number:</strong><br>
                            <code><?php echo htmlspecialchars($asset['asset_number']); ?></code></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong><i class="bi bi-tag"></i> Category:</strong><br>
                            <span class="badge bg-secondary"><?php echo htmlspecialchars($asset['category']); ?></span></p>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p><strong><i class="bi bi-calendar"></i> Purchase Date:</strong><br>
                            <?php echo formatDate($asset['purchase_date']); ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong><i class="bi bi-geo-alt"></i> Location:</strong><br>
                            <?php echo htmlspecialchars($asset['location']) ?: 'Not specified'; ?></p>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p><strong><i class="bi bi-cash"></i> Purchase Price:</strong><br>
                            <?php echo formatCurrency($asset['purchase_price']); ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong><i class="bi bi-graph-up"></i> Current Value:</strong><br>
                            <span class="fs-5 text-success"><?php echo formatCurrency($asset['current_value']); ?></span></p>
                        </div>
                    </div>
                    
                    <?php if ($asset['depreciation_method'] !== 'None'): ?>
                    <div class="alert alert-info">
                        <h6><i class="bi bi-graph-down"></i> Depreciation</h6>
                        <p class="mb-1"><strong>Method:</strong> <?php echo $asset['depreciation_method']; ?></p>
                        <p class="mb-0"><strong>Rate:</strong> <?php echo $asset['depreciation_rate']; ?>% per year</p>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($asset['serial_number']): ?>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p><strong><i class="bi bi-upc"></i> Serial Number:</strong><br>
                            <code><?php echo htmlspecialchars($asset['serial_number']); ?></code></p>
                        </div>
                        <?php if ($asset['warranty_expiry']): ?>
                        <div class="col-md-6">
                            <p><strong><i class="bi bi-shield-check"></i> Warranty Expires:</strong><br>
                            <?php echo formatDate($asset['warranty_expiry']); ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($asset['assigned_to_name']): ?>
                    <div class="row mb-3">
                        <div class="col-12">
                            <p><strong><i class="bi bi-person"></i> Assigned To:</strong><br>
                            <?php echo htmlspecialchars($asset['assigned_to_name']); ?></p>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($asset['description']): ?>
                    <div class="row">
                        <div class="col-12">
                            <p><strong><i class="bi bi-file-text"></i> Description:</strong><br>
                            <?php echo nl2br(htmlspecialchars($asset['description'])); ?></p>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Valuation History -->
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="bi bi-clock-history"></i> Valuation History</h5>
                </div>
                <div class="card-body">
                    <?php if (mysqli_num_rows($valuations_result) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Amount</th>
                                        <th>Reason</th>
                                        <th>Valued By</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($val = mysqli_fetch_assoc($valuations_result)): ?>
                                    <tr>
                                        <td><?php echo formatDate($val['valuation_date']); ?></td>
                                        <td><?php echo formatCurrency($val['valuation_amount']); ?></td>
                                        <td><?php echo htmlspecialchars($val['valuation_reason']); ?></td>
                                        <td><?php echo htmlspecialchars($val['valued_by_name']); ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">No valuation history</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card bg-light mb-3">
                <div class="card-header">
                    <h6 class="mb-0"><i class="bi bi-info-circle"></i> Record Information</h6>
                </div>
                <div class="card-body">
                    <p class="small mb-2">
                        <strong>Created By:</strong><br>
                        <?php echo htmlspecialchars($asset['creator']); ?>
                    </p>
                    <p class="small mb-2">
                        <strong>Created At:</strong><br>
                        <?php echo formatDate($asset['created_at']); ?>
                    </p>
                    <p class="small mb-0">
                        <strong>Last Updated:</strong><br>
                        <?php echo formatDate($asset['updated_at']); ?>
                    </p>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0"><i class="bi bi-gear"></i> Actions</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="edit.php?id=<?php echo $asset['id']; ?>" class="btn btn-primary">
                            <i class="bi bi-pencil"></i> Edit Asset
                        </a>
                        <a href="delete.php?id=<?php echo $asset['id']; ?>" 
                           class="btn btn-danger"
                           onclick="return confirm('Are you sure you want to delete this asset?')">
                            <i class="bi bi-trash"></i> Delete Asset
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php 
mysqli_stmt_close($val_stmt);
include '../includes/footer.php'; 
?>


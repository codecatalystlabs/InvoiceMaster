<?php
/**
 * Code Catalyst Labs - Client Actions
 */
require_once '../includes/config.php';
require_once '../includes/functions.php';

requireLogin();
requireRole(['Admin', 'Sales']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: list.php');
    exit();
}

$action = $_POST['action'];

if ($action === 'add') {
    $name = clean($_POST['name']);
    $email = clean($_POST['email']);
    $phone = clean($_POST['phone']);
    $company = clean($_POST['company']);
    
    if (empty($name)) {
        $_SESSION['error'] = 'Client name is required.';
        header('Location: list.php');
        exit();
    }
    
    $query = "INSERT INTO clients (name, email, phone, company) VALUES (?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ssss", $name, $email, $phone, $company);
    
    if (mysqli_stmt_execute($stmt)) {
        $client_id = mysqli_insert_id($conn);
        logAudit($conn, 'Create', 'Client', $client_id, "Client '$name' created");
        $_SESSION['success'] = 'Client added successfully!';
    } else {
        $_SESSION['error'] = 'Failed to add client.';
    }
    
    mysqli_stmt_close($stmt);
}
elseif ($action === 'edit') {
    $id = (int)$_POST['id'];
    $name = clean($_POST['name']);
    $email = clean($_POST['email']);
    $phone = clean($_POST['phone']);
    $company = clean($_POST['company']);
    
    if (empty($name)) {
        $_SESSION['error'] = 'Client name is required.';
        header('Location: list.php');
        exit();
    }
    
    $query = "UPDATE clients SET name = ?, email = ?, phone = ?, company = ? WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ssssi", $name, $email, $phone, $company, $id);
    
    if (mysqli_stmt_execute($stmt)) {
        logAudit($conn, 'Update', 'Client', $id, "Client '$name' updated");
        $_SESSION['success'] = 'Client updated successfully!';
    } else {
        $_SESSION['error'] = 'Failed to update client.';
    }
    
    mysqli_stmt_close($stmt);
}

header('Location: list.php');
exit();
?>


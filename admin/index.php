<?php 
require_once __DIR__ . '/../includes/auth.php';

// Require login and admin role
requireLogin();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../dashboard.php');
    exit();
}

// Redirect to overview page
header('Location: overview.php');
exit();

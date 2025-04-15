<?php
require_once __DIR__ . '/../config/functions.php';
if (!isLoggedIn() && !in_array(basename($_SERVER['PHP_SELF']), ['login.php', 'register.php', 'forgot-password.php'])) {
    redirect('/auth/login.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?></title>
    
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>/bootstrap.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>/style.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="wrapper">
        <?php if (isLoggedIn()): ?>
            <?php include 'sidebar.php'; ?>
        <?php endif; ?>
        
        <div class="main-content">
            <?php 
            // Include alerts hanya sekali
            include 'alerts.php'; 
            ?>
            
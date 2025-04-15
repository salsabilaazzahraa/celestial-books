<?php
session_start();
require_once __DIR__ . '/../config/functions.php';

// Hapus semua data session
$_SESSION = array();

// Hapus cookie session
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Hapus session
session_destroy();

// Set pesan alert
setMessage('Anda telah berhasil logout', 'success');

// Redirect ke halaman login
redirect('/auth/login.php');
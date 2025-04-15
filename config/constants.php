<?php
// Base URL - Sesuaikan dengan nama folder project Anda
define('BASE_URL', 'http://localhost/celestial-books');

// Path direktori
define('ROOT_PATH', dirname(__DIR__) . '/');
define('MODULES_PATH', ROOT_PATH . 'modules');
define('ASSETS_PATH', BASE_URL . '/assets');
define('CSS_PATH', ASSETS_PATH . '/css');
define('JS_PATH', ASSETS_PATH . '/js');
define('IMG_PATH', ASSETS_PATH . '/img');

// Database konfigurasi
define('DB_HOST', 'localhost');
define('DB_NAME', 'celestial_books');
define('DB_USER', 'root');
define('DB_PASS', '');

// Pengaturan aplikasi
define('APP_NAME', 'Celestial Books');
define('APP_DESCRIPTION', 'Sistem Manajemen Perpustakaan Digital');
define('APP_VERSION', '1.0.0');

// Pengaturan upload
define('UPLOAD_PATH', ROOT_PATH . 'assets/uploads/');
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'pdf']);
define('MAX_FILE_SIZE', 5242880);

// Pengaturan peminjaman
define('MAX_PEMINJAMAN', 3);
define('DURASI_PEMINJAMAN', 14);
define('DENDA_PER_HARI', 1000);
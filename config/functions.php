<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once 'constants.php';
require_once 'database.php';

// Fungsi untuk sanitasi input
function sanitize($data)
{
    return htmlspecialchars(strip_tags(trim($data)));
}

// Fungsi untuk mengecek login
function isLoggedIn()
{
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function isAdmin()
{
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function isPetugas()
{
    return isset($_SESSION['role']) && $_SESSION['role'] === 'petugas';
}

function isPeminjam()
{
    return isset($_SESSION['role']) && $_SESSION['role'] === 'peminjam';
}

// Fungsi untuk mengecek role
function hasRole($role)
{
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

// Fungsi untuk redirect
function redirect($path)
{
    header("Location: " . BASE_URL . $path);
    exit();
}

// Fungsi untuk menampilkan pesan
function setMessage($type, $message)
{
    $_SESSION['alert'] = [
        'type' => $type,
        'message' => $message
    ];
}

// Fungsi untuk mengecek active menu

function isActive($path) {

    return strpos($_SERVER['REQUEST_URI'], $path) !== false ? 'active' : '';

}

// Fungsi untuk menampilkan pesan (modifikasi dari yang sudah ada)

function showMessage() {

    if (isset($_SESSION['alert'])) {

        $alert = $_SESSION['alert'];

        echo "<div class='alert alert-{$alert['type']} alert-dismissible fade show' role='alert'>

                {$alert['message']}

                <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>

              </div>";

        unset($_SESSION['alert']);

    }

}


// Fungsi untuk mendapatkan pesan
function getMessage()
{
    if (isset($_SESSION['alert'])) {
        $alert = $_SESSION['alert'];
        unset($_SESSION['alert']);
        return $alert;
    }
    return null;
}

// Fungsi untuk generate kode unik
function generateCode($prefix = '')
{
    return $prefix . date('Ymd') . rand(1000, 9999);
}

// Fungsi untuk format tanggal
function formatDate($date)
{
    return date('d-m-Y', strtotime($date));
}

// Fungsi untuk hitung denda
function hitungDenda($tanggal_kembali, $tanggal_dikembalikan)
{
    $selisih = strtotime($tanggal_dikembalikan) - strtotime($tanggal_kembali);
    $hari = floor($selisih / (60 * 60 * 24));
    return max(0, $hari * DENDA_PER_HARI);
}

// Fungsi untuk upload file
function uploadFile($file, $destination)
{
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    // Cek ekstensi file
    if (!in_array($file_extension, ALLOWED_EXTENSIONS)) {
        return [
            'success' => false,
            'message' => 'Tipe file tidak diizinkan'
        ];
    }

    // Cek ukuran file
    if ($file['size'] > MAX_FILE_SIZE) {
        return [
            'success' => false,
            'message' => 'Ukuran file terlalu besar (maksimal 5MB)'
        ];
    }

    // Generate nama file unik
    $new_filename = uniqid() . '.' . $file_extension;
    $upload_path = UPLOAD_PATH . $destination . '/' . $new_filename;

    // Upload file
    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
        return [
            'success' => true,
            'filename' => $new_filename
        ];
    }

    return [
        'success' => false,
        'message' => 'Gagal mengupload file'
    ];
}

function cekTerlambat($tanggal_pengembalian) {
    // Jika tanggal pengembalian sudah lewat dari hari ini
    return strtotime($tanggal_pengembalian) < time();
}

//untuk update status terlambat
function updateStatusTerlambat() {
    $db = new Database();
    $conn = $db->getConnection();
    
    $today = date('Y-m-d');
    $query = "UPDATE peminjaman 
              SET status_peminjaman = 'terlambat' 
              WHERE tanggal_pengembalian < ? 
              AND status_peminjaman = 'dipinjam'";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$today]);
    
    return $stmt->rowCount();
}
<?php
session_start();
require_once '../../../config/functions.php';
require_once '../../../config/database.php';

// Cek akses petugas
if (!isPetugas()) {
    setMessage('danger', 'Akses ditolak! Anda bukan petugas.');
    redirect('/auth/login.php');
}

$db = new Database();
$conn = $db->getConnection();

// Ambil ID buku dari URL
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Cek apakah buku ada dan tidak sedang dipinjam
$stmt = $conn->prepare("SELECT b.*, COUNT(p.id) as is_borrowed 
                        FROM buku b 
                        LEFT JOIN peminjaman p ON p.buku_id = b.id AND p.status_peminjaman = 'dipinjam' 
                        WHERE b.id = :id 
                        GROUP BY b.id");
$stmt->bindParam(':id', $id);
$stmt->execute();
$buku = $stmt->fetch(PDO::FETCH_ASSOC);

// Jika buku tidak ditemukan
if (!$buku) {
    setMessage('danger', 'Buku tidak ditemukan!');
    redirect('/modules/petugas/books/index.php');
    exit;
}

// Jika buku sedang dipinjam
if ($buku['is_borrowed'] > 0) {
    setMessage('danger', 'Buku tidak dapat dihapus karena sedang dipinjam!');
    redirect('/modules/petugas/books/index.php');
    exit;
}

try {
    // Mulai transaksi
    $conn->beginTransaction();

    // Hapus relasi kategori
    $stmt = $conn->prepare("DELETE FROM kategori_buku_relasi WHERE buku_id = :id");
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    
    // Hapus ulasan buku jika ada
    $stmt = $conn->prepare("DELETE FROM ulasan_buku WHERE buku_id = :id");
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    
    // Hapus catatan peminjaman yang sudah dikembalikan jika ada
    $stmt = $conn->prepare("DELETE FROM peminjaman WHERE buku_id = :id AND status_peminjaman = 'dikembalikan'");
    $stmt->bindParam(':id', $id);
    $stmt->execute();

    // Hapus buku
    $stmt = $conn->prepare("DELETE FROM buku WHERE id = :id");
    $stmt->bindParam(':id', $id);
    $stmt->execute();

    // Hapus file cover jika ada
    if (!empty($buku['cover_img'])) {
        $upload_dir = '../../../assets/img/books/';
        $file_path = $upload_dir . $buku['cover_img'];
        if (file_exists($file_path)) {
            unlink($file_path);
        }
    }

    // Commit transaksi
    $conn->commit();

    // Set pesan sukses
    setMessage('success', 'Buku berhasil dihapus!');
    redirect('/modules/petugas/books/index.php');

} catch (PDOException $e) {
    // Rollback transaksi jika terjadi error
    $conn->rollBack();
    
    // Set pesan error
    setMessage('danger', 'Gagal menghapus buku: ' . $e->getMessage());
    redirect('/modules/petugas/books/index.php');
}
?>
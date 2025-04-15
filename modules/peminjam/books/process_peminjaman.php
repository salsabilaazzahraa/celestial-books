<?php
session_start();
require_once '../../../config/functions.php';
require_once '../../../config/database.php';

if (!isPeminjam()) {
    setMessage('error', 'Akses ditolak!');
    redirect('/auth/login.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = new Database();
    $conn = $db->getConnection();
    
    $buku_id = $_POST['buku_id'] ?? 0;
    $tanggal_peminjaman = $_POST['tanggal_peminjaman'] ?? date('Y-m-d');
    $tanggal_pengembalian = $_POST['tanggal_pengembalian'] ?? date('Y-m-d', strtotime('+7 days'));
    $catatan = $_POST['catatan'] ?? '';
    $user_id = $_SESSION['user_id'];
    
    // Cek stok buku
    $stmt = $conn->prepare("SELECT stok FROM buku WHERE id = ?");
    $stmt->execute([$buku_id]);
    $stok = $stmt->fetchColumn();
    
    if ($stok <= 0) {
        setMessage('error', 'Stok buku tidak tersedia!');
        redirect('/modules/peminjam/books/detail.php?id='.$buku_id);
    }
    
    // Cek apakah user sudah meminjam buku ini
    $stmt = $conn->prepare("
        SELECT COUNT(*) FROM peminjaman 
        WHERE user_id = ? AND buku_id = ? AND status_peminjaman = 'dipinjam'
    ");
    $stmt->execute([$user_id, $buku_id]);
    $already_borrowed = $stmt->fetchColumn() > 0;
    
    if ($already_borrowed) {
        setMessage('error', 'Anda sudah meminjam buku ini!');
        redirect('/modules/peminjam/books/detail.php?id='.$buku_id);
    }
    
    // Cek jumlah peminjaman aktif
    $stmt = $conn->prepare("
        SELECT COUNT(*) FROM peminjaman 
        WHERE user_id = ? AND status_peminjaman = 'dipinjam'
    ");
    $stmt->execute([$user_id]);
    $active_loans = $stmt->fetchColumn();
    
    if (defined('MAX_PEMINJAMAN') && $active_loans >= MAX_PEMINJAMAN) {
        setMessage('error', 'Anda telah mencapai batas maksimal peminjaman!');
        redirect('/modules/peminjam/books/detail.php?id='.$buku_id);
    }
    
    try {
        // Generate kode peminjaman
        $kode_peminjaman = 'PJM-' . date('Ymd') . '-' . rand(1000, 9999);
        
        // Begin transaction
        $conn->beginTransaction();
        
        // Insert peminjaman
        $stmt = $conn->prepare("
            INSERT INTO peminjaman (kode_peminjaman, user_id, buku_id, tanggal_peminjaman, 
                                   tanggal_pengembalian, status_peminjaman, catatan, petugas_id)
            VALUES (?, ?, ?, ?, ?, 'dipinjam', ?, NULL)
        ");
        $stmt->execute([
            $kode_peminjaman,
            $user_id,
            $buku_id,
            $tanggal_peminjaman,
            $tanggal_pengembalian,
            $catatan
        ]);
        
        $peminjaman_id = $conn->lastInsertId();
        
        // Update stok buku
        $stmt = $conn->prepare("UPDATE buku SET stok = stok - 1 WHERE id = ?");
        $stmt->execute([$buku_id]);
        
        // Commit transaction
        $conn->commit();
        
        setMessage('success', 'Buku berhasil dipinjam!');
        redirect('/modules/peminjam/books/bukti_peminjaman.php?id='.$peminjaman_id);
        
    } catch (PDOException $e) {
        // Rollback transaction
        $conn->rollBack();
        setMessage('error', 'Terjadi kesalahan: ' . $e->getMessage());
        redirect('/modules/peminjam/books/detail.php?id='.$buku_id);
    }
} else {
    setMessage('error', 'Metode akses tidak valid!');
    redirect('/modules/peminjam/books/index.php');
}
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
    
    $peminjaman_id = $_POST['peminjaman_id'] ?? 0;
    $kondisi_buku = $_POST['kondisi_buku'] ?? 'baik';
    $catatan = $_POST['catatan'] ?? '';
    
    // Cek data peminjaman
    $stmt = $conn->prepare("
        SELECT p.*, b.judul
        FROM peminjaman p
        JOIN buku b ON p.buku_id = b.id
        WHERE p.id = ? AND p.user_id = ? AND p.status_peminjaman = 'dipinjam'
    ");
    $stmt->execute([$peminjaman_id, $_SESSION['user_id']]);
    $peminjaman = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$peminjaman) {
        setMessage('error', 'Data peminjaman tidak ditemukan!');
        redirect('/modules/peminjam/peminjaman/index.php');
    }
    
    try {
        // Hitung denda jika terlambat
        $today = new DateTime();
        $return_date = new DateTime($peminjaman['tanggal_pengembalian']);
        $is_late = $today > $return_date;
        $days_late = 0;
        $denda = 0;
        
        if ($is_late) {
            $interval = $today->diff($return_date);
            $days_late = $interval->days;
            $denda = $days_late * 1000; // Rp 1.000 per hari
        }
        
        // Tambahan denda berdasarkan kondisi buku
        if ($kondisi_buku === 'rusak_ringan') {
            $denda += 10000; // Tambahan denda Rp 10.000
        } elseif ($kondisi_buku === 'rusak_berat') {
            $denda += 50000; // Tambahan denda Rp 50.000
        } elseif ($kondisi_buku === 'hilang') {
            $denda += 100000; // Tambahan denda Rp 100.000
        }
        
        // Begin transaction
        $conn->beginTransaction();
        
        // Insert ke tabel riwayat_peminjaman
        $stmt = $conn->prepare("
            INSERT INTO riwayat_peminjaman (peminjaman_id, status_perubahan, catatan, changed_by)
            VALUES (?, 'pengajuan_pengembalian', ?, ?)
        ");
        $stmt->execute([$peminjaman_id, $catatan, $_SESSION['user_id']]);
        
        // Update peminjaman (gunakan 'pengajuan_pengembalian' sebagai status)
        $stmt = $conn->prepare("
            UPDATE peminjaman 
            SET status_peminjaman = 'pengajuan_pengembalian', 
                denda = ?, 
                catatan = ?, 
                tanggal_dikembalikan = ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$denda, $catatan, date('Y-m-d'), $peminjaman_id]);
        
        // Commit transaction
        $conn->commit();
        
        setMessage('success', 'Pengembalian buku berhasil! Silakan bawa buku ke perpustakaan untuk dikonfirm oleh petugas.');
        redirect('/modules/peminjam/peminjaman/index.php');
        
    } catch (PDOException $e) {
        // Rollback transaction
        $conn->rollBack();
        setMessage('error', 'Terjadi kesalahan: ' . $e->getMessage());
        redirect('/modules/peminjam/peminjaman/ajukan_pengembalian.php?id='.$peminjaman_id);
    }
} else {
    setMessage('error', 'Metode akses tidak valid!');
    redirect('/modules/peminjam/peminjaman/index.php');
}

<?php
session_start();
require_once '../../../config/functions.php';
require_once '../../../config/database.php';

if (!isPeminjam()) {
    setMessage('error', 'Akses ditolak!');
    redirect('/auth/login.php');
}

$db = new Database();
$conn = $db->getConnection();

// Ambil detail peminjaman
$peminjaman_id = $_GET['id'] ?? 0;
$stmt = $conn->prepare("
    SELECT p.*, b.judul, b.penulis, b.cover_img
    FROM peminjaman p
    JOIN buku b ON p.buku_id = b.id
    WHERE p.id = ? AND p.user_id = ? AND p.status_peminjaman = 'dipinjam'
");
$stmt->execute([$peminjaman_id, $_SESSION['user_id']]);
$peminjaman = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$peminjaman) {
    setMessage('error', 'Data peminjaman tidak ditemukan atau tidak dapat diajukan pengembalian!');
    redirect('/modules/peminjam/peminjaman/index.php');
}

include '../../../includes/header.php';
?>

<div class="container-fluid p-4">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Form Pengajuan Pengembalian Buku</h5>
                </div>
                <div class="card-body">
                    <form action="process_pengembalian.php" method="POST">
                        <input type="hidden" name="peminjaman_id" value="<?= $peminjaman['id'] ?>">
                        
                        <div class="row mb-4">
                            <div class="col-md-4">
                                <?php
                                $coverImg = !empty($peminjaman['cover_img']) ? $peminjaman['cover_img'] : 'book' . rand(11, 15) . '.jpg';
                                $imagePath = BASE_URL . '/assets/img/books/' . $coverImg;
                                ?>
                                <img src="<?= $imagePath ?>" 
                                     class="img-fluid rounded shadow-sm" 
                                     alt="<?= htmlspecialchars($peminjaman['judul']) ?>"
                                     style="width: 100%; object-fit: contain; background-color: #f8f9fa; max-height: 300px;"
                                     onerror="this.src='<?= BASE_URL ?>/assets/img/books/book<?= rand(11, 15) ?>.jpg';">
                            </div>
                            <div class="col-md-8">
                                <h4><?= htmlspecialchars($peminjaman['judul']) ?></h4>
                                <p class="text-muted"><?= htmlspecialchars($peminjaman['penulis']) ?></p>
                                
                                <table class="table table-borderless">
                                    <tr>
                                        <td width="150"><strong>Kode Peminjaman</strong></td>
                                        <td>: <?= htmlspecialchars($peminjaman['kode_peminjaman']) ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Tanggal Pinjam</strong></td>
                                        <td>: <?= date('d/m/Y', strtotime($peminjaman['tanggal_peminjaman'])) ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Tanggal Kembali</strong></td>
                                        <td>: <?= date('d/m/Y', strtotime($peminjaman['tanggal_pengembalian'])) ?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        
                        <?php
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
                        ?>
                        
                        <?php if ($is_late): ?>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                Buku telah melewati tanggal pengembalian. Denda keterlambatan: <strong>Rp <?= number_format($denda, 0, ',', '.') ?></strong> (<?= $days_late ?> hari).
                            </div>
                        <?php else: ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle me-2"></i>
                                Buku masih dalam masa peminjaman. Tidak ada denda.
                            </div>
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label for="kondisi_buku" class="form-label">Kondisi Buku Saat Dikembalikan</label>
                            <select class="form-select" id="kondisi_buku" name="kondisi_buku" required>
                                <option value="baik">Baik (Tidak Ada Kerusakan)</option>
                                <option value="rusak_ringan">Rusak Ringan (Kotor, Terlipat, dll)</option>
                                <option value="rusak_berat">Rusak Berat (Robek, Basah, Halaman Hilang)</option>
                                <option value="hilang">Hilang</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="catatan" class="form-label">Catatan (Opsional)</label>
                            <textarea class="form-control" id="catatan" name="catatan" rows="3"><?= htmlspecialchars($peminjaman['catatan']) ?></textarea>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-undo me-1"></i>Ajukan Pengembalian
                            </button>
                            <a href="index.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-1"></i>Kembali
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../../includes/footer.php'; ?>

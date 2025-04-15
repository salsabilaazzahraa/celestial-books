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
updateStatusTerlambat();

// Filter status jika ada
$statusFilter = isset($_GET['status']) ? sanitize($_GET['status']) : 'all';
$whereClause = "WHERE p.user_id = ?";

if ($statusFilter !== 'all') {
    $whereClause .= " AND p.status_peminjaman = ?";
    $params = [$_SESSION['user_id'], $statusFilter];
} else {
    $params = [$_SESSION['user_id']];
}

// Ambil data peminjaman dengan filter
$query = "
    SELECT p.*, b.judul, b.penulis, b.cover_img 
    FROM peminjaman p
    JOIN buku b ON p.buku_id = b.id
    $whereClause
    ORDER BY p.created_at DESC
";

$stmt = $conn->prepare($query);
$stmt->execute($params);
$peminjaman = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../../../includes/header.php';
?>

<div class="container-fluid p-4">
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 text-primary">Riwayat Peminjaman Buku</h5>
                        <div>
                            <div class="btn-group">
                                <a href="?status=all" class="btn btn-sm <?= $statusFilter === 'all' ? 'btn-primary' : 'btn-outline-primary' ?>">Semua</a>
                                <a href="?status=dipinjam" class="btn btn-sm <?= $statusFilter === 'dipinjam' ? 'btn-primary' : 'btn-outline-primary' ?>">Dipinjam</a>
                                <a href="?status=dikembalikan" class="btn btn-sm <?= $statusFilter === 'dikembalikan' ? 'btn-primary' : 'btn-outline-primary' ?>">Dikembalikan</a>
                                <a href="?status=terlambat" class="btn btn-sm <?= $statusFilter === 'terlambat' ? 'btn-primary' : 'btn-outline-primary' ?>">Terlambat</a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                <div class="alert alert-info mb-3">
                    <i class="fas fa-info-circle me-2"></i>
                    Untuk mengembalikan buku, silakan datang langsung ke perpustakaan dan serahkan buku kepada petugas.
                </div>
                
                <?php if (empty($peminjaman)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <?php if ($statusFilter === 'all'): ?>
                                Anda belum memiliki riwayat peminjaman buku.
                            <?php else: ?>
                                Tidak ada data peminjaman dengan status <?= htmlspecialchars($statusFilter) ?>.
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Buku</th>
                                        <th>Kode Peminjaman</th>
                                        <th>Tanggal Pinjam</th>
                                        <th>Tanggal Kembali</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($peminjaman as $key => $item): ?>
                                        <?php
                                        // Hitung tanggal batas pengembalian (sesuai dengan konstanta DURASI_PEMINJAMAN)
                                        $due_date = date('Y-m-d', strtotime('+'.DURASI_PEMINJAMAN.' days', strtotime($item['tanggal_peminjaman'])));
                                        ?>
                                        <tr>
                                            <td><?= $key + 1 ?></td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <?php
                                                    $coverImg = !empty($item['cover_img']) ? $item['cover_img'] : 'book' . rand(11, 15) . '.jpg';
                                                    $imagePath = BASE_URL . '/assets/img/books/' . $coverImg;
                                                    ?>
                                                    <img src="<?= $imagePath ?>" 
                                                         class="rounded me-3" 
                                                         alt="<?= htmlspecialchars($item['judul']) ?>"
                                                         width="50"
                                                         height="60"
                                                         onerror="this.src='<?= BASE_URL ?>/assets/img/books/book<?= rand(11, 15) ?>.jpg';">
                                                    <div>
                                                        <h6 class="mb-0"><?= htmlspecialchars($item['judul']) ?></h6>
                                                        <small class="text-muted"><?= htmlspecialchars($item['penulis']) ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?= htmlspecialchars($item['kode_peminjaman']) ?></td>
                                            <td><?= date('d/m/Y', strtotime($item['tanggal_peminjaman'])) ?></td>
                                            <td><?= date('d/m/Y', strtotime($due_date)) ?></td>
                                            <td>
                                                <?php
                                                $badgeClass = 'bg-primary';
                                                $statusText = 'Dipinjam';
                                                
                                                if ($item['status_peminjaman'] === 'dipinjam') {
                                                    if (cekTerlambat($due_date)) {
                                                        $badgeClass = 'bg-danger';
                                                        $statusText = 'Terlambat';
                                                    }
                                                } elseif ($item['status_peminjaman'] === 'dikembalikan') {
                                                    $badgeClass = 'bg-success';
                                                    $statusText = 'Dikembalikan';
                                                } elseif ($item['status_peminjaman'] === 'pengajuan_pengembalian') {
                                                    $badgeClass = 'bg-warning';
                                                    $statusText = 'Proses Pengembalian';
                                                } elseif ($item['status_peminjaman'] === 'terlambat') {
                                                    $badgeClass = 'bg-danger';
                                                    $statusText = 'Terlambat';
                                                }
                                                ?>
                                                <span class="badge <?= $badgeClass ?>"><?= $statusText ?></span>
                                            </td>
                                            <td>
                                                <a href="../books/bukti_peminjaman.php?id=<?= $item['id'] ?>" class="btn btn-sm btn-outline-primary mb-1">
                                                    <i class="fas fa-print me-1"></i>Bukti
                                                </a>
                                                <?php if ($item['status_peminjaman'] === 'dipinjam' || $item['status_peminjaman'] === 'terlambat'): ?>
                                                    
                                                <?php endif; ?>
                                                <?php if ($item['status_peminjaman'] === 'terlambat'): ?>
                                                    <span class="d-block small text-danger mt-1">
                                                        <i class="fas fa-exclamation-circle"></i>
                                                        Denda: Rp <?= number_format($item['denda'] > 0 ? $item['denda'] : hitungDenda($due_date, date('Y-m-d')), 0, ',', '.') ?>
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../../includes/footer.php'; ?>
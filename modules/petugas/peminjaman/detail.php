<?php
session_start();
require_once '../../../config/functions.php';
require_once '../../../config/database.php';

if (!isPetugas()) {
    setMessage('error', 'Akses ditolak! Anda bukan petugas.');
    redirect('/auth/login.php');
}

$db = new Database();
$conn = $db->getConnection();

// Mengambil data peminjaman berdasarkan ID
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    setMessage('error', 'ID peminjaman tidak valid.');
    redirect('/modules/petugas/peminjaman/index.php');
}

// Query untuk mengambil detail peminjaman beserta informasi terkait
$loan_query = "
    SELECT p.*, u.username, u.email, u.no_telepon, b.judul, b.penulis, b.penerbit, b.tahun_terbit, b.isbn, b.cover_img
    FROM peminjaman p 
    JOIN users u ON p.user_id = u.id 
    JOIN buku b ON p.buku_id = b.id 
    WHERE p.id = :id
";
$stmt = $conn->prepare($loan_query);
$stmt->execute(['id' => $id]);
$loan = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$loan) {
    setMessage('error', 'Data peminjaman tidak ditemukan.');
    redirect('/modules/petugas/peminjaman/index.php');
}

// Mengambil riwayat perubahan status peminjaman
$history_query = "
    SELECT r.*, u.username as changed_by_name
    FROM riwayat_peminjaman r
    LEFT JOIN users u ON r.changed_by = u.id
    WHERE r.peminjaman_id = :peminjaman_id
    ORDER BY r.created_at DESC
";
$history_stmt = $conn->prepare($history_query);
$history_stmt->execute(['peminjaman_id' => $id]);
$history = $history_stmt->fetchAll(PDO::FETCH_ASSOC);

// Menghitung durasi peminjaman dan keterlambatan (jika ada)
$tgl_pinjam = strtotime($loan['tanggal_peminjaman']);
$tgl_batas = strtotime('+7 days', $tgl_pinjam);
$tgl_kembali = $loan['tanggal_dikembalikan'] ? strtotime($loan['tanggal_dikembalikan']) : time();

$durasi = ceil(($tgl_kembali - $tgl_pinjam) / (60 * 60 * 24));
$keterlambatan = $tgl_kembali > $tgl_batas ? ceil(($tgl_kembali - $tgl_batas) / (60 * 60 * 24)) : 0;

include '../../../includes/header.php';
?>

<div class="container-fluid p-3">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">Detail Peminjaman</h1>
        <div>
            <a href="../peminjaman/index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i> Kembali
            </a>
            <?php if ($loan['status_peminjaman'] == 'dipinjam'): ?>
                <a href="../pengembalian/process.php?id=<?= $loan['id'] ?>" class="btn btn-success ms-2">
                    <i class="fas fa-undo me-1"></i> Proses Pengembalian
                </a>
            <?php endif; ?>
            <a href="print.php?id=<?= $loan['id'] ?>" class="btn btn-primary ms-2" target="_blank">
                <i class="fas fa-print me-1"></i> Cetak Bukti
            </a>
        </div>
    </div>

    <!-- Alert Message -->
    <?php showMessage(); ?>

    <div class="row">
        <!-- Informasi Peminjaman -->
        <div class="col-md-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Informasi Peminjaman</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <th width="40%">ID Peminjaman</th>
                                    <td width="60%"><?= $loan['id'] ?></td>
                                </tr>
                                <tr>
                                    <th>Tanggal Peminjaman</th>
                                    <td><?= date('d/m/Y', strtotime($loan['tanggal_peminjaman'])) ?></td>
                                </tr>
                                <tr>
                                    <th>Batas Pengembalian</th>
                                    <td><?= date('d/m/Y', $tgl_batas) ?></td>
                                </tr>
                                <?php if ($loan['tanggal_dikembalikan']): ?>
                                <tr>
                                    <th>Tanggal Dikembalikan</th>
                                    <td><?= date('d/m/Y', strtotime($loan['tanggal_dikembalikan'])) ?></td>
                                </tr>
                                <?php endif; ?>
                                <tr>
                                    <th>Status</th>
                                    <td>
                                        <?php if ($loan['status_peminjaman'] == 'dipinjam'): ?>
                                            <?php if (time() > $tgl_batas): ?>
                                                <span class="badge bg-danger">Terlambat</span>
                                            <?php else: ?>
                                                <span class="badge bg-primary">Dipinjam</span>
                                            <?php endif; ?>
                                        <?php elseif ($loan['status_peminjaman'] == 'dikembalikan'): ?>
                                            <span class="badge bg-success">Dikembalikan</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Durasi Peminjaman</th>
                                    <td><?= $durasi ?> hari</td>
                                </tr>
                                <?php if ($keterlambatan > 0): ?>
                                <tr>
                                    <th>Keterlambatan</th>
                                    <td class="text-danger"><?= $keterlambatan ?> hari</td>
                                </tr>
                                <tr>
                                    <th>Denda</th>
                                    <td class="text-danger">Rp <?= number_format($loan['denda'] ?? $keterlambatan * 1000, 0, ',', '.') ?></td>
                                </tr>
                                <?php endif; ?>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6 class="fw-bold mb-3">Peminjam</h6>
                            <div class="d-flex align-items-center mb-3">
                                <img src="https://ui-avatars.com/api/?name=<?= urlencode($loan['username']) ?>&background=random" 
                                    class="rounded-circle me-3" width="64" height="64" alt="User Avatar">
                                <div>
                                    <h5 class="mb-1"><?= htmlspecialchars($loan['username']) ?></h5>
                                    <p class="text-muted mb-0">
                                        <i class="fas fa-envelope me-1"></i> <?= htmlspecialchars($loan['email']) ?>
                                    </p>
                                    <?php if (!empty($loan['no_telepon'])): ?>
                                    <p class="text-muted mb-0">
                                        <i class="fas fa-phone me-1"></i> <?= htmlspecialchars($loan['no_telepon']) ?>
                                    </p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <?php if (!empty($loan['catatan'])): ?>
                            <div class="alert alert-info mt-3">
                                <strong>Catatan:</strong><br>
                                <?= nl2br(htmlspecialchars($loan['catatan'])) ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Riwayat Status -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Riwayat Perubahan</h6>
                </div>
                <div class="card-body">
                    <div class="timeline">
                        <?php if (empty($history)): ?>
                            <p class="text-muted text-center">Tidak ada riwayat perubahan</p>
                        <?php else: ?>
                            <ul class="list-group list-group-flush">
                                <?php foreach ($history as $item): ?>
                                    <li class="list-group-item px-0">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <span class="badge bg-<?= $item['status_perubahan'] == 'dipinjam' ? 'primary' : 'success' ?> me-2">
                                                    <?= ucfirst($item['status_perubahan']) ?>
                                                </span>
                                                <?= htmlspecialchars($item['catatan']) ?>
                                            </div>
                                            <div class="text-muted small">
                                                <?= date('d/m/Y H:i', strtotime($item['created_at'])) ?> 
                                                <?php if (!empty($item['changed_by_name'])): ?>
                                                    oleh <?= htmlspecialchars($item['changed_by_name']) ?>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3">
            <h6 class="m-0 font-weight-bold text-primary">Informasi Buku</h6>
        </div>
        <div class="card-body">
            <div class="text-center mb-3">
                <?php
                // Tentukan path gambar dengan benar
                $coverImg = !empty($loan['cover_img']) ? $loan['cover_img'] : 'default-book.jpg';
                $imagePath = '../../../assets/img/books/' . $coverImg;
                
                // Jika cover_img adalah URL lengkap, gunakan langsung
                if (!empty($loan['cover_img']) && (strpos($loan['cover_img'], 'http://') === 0 || strpos($loan['cover_img'], 'https://') === 0)) {
                    $imagePath = $loan['cover_img'];
                }
                ?>
                <div class="book-cover-container" style="height: 250px; display: flex; align-items: center; justify-content: center; background-color: #f8f9fa;">
                    <img src="<?= $imagePath ?>"
                         alt="<?= htmlspecialchars($loan['judul']) ?>" 
                         class="img-fluid book-cover"
                         style="max-height: 250px; object-fit: contain;"
                         onerror="this.onerror=null; this.src='../../../assets/img/books/default-book.jpg';">
                </div>
            </div>
            
            <h5 class="fw-bold mb-3"><?= htmlspecialchars($loan['judul']) ?></h5>
            
            <table class="table table-borderless">
                <tr>
                    <th width="40%">Penulis</th>
                    <td width="60%"><?= htmlspecialchars($loan['penulis']) ?></td>
                </tr>
                <tr>
                    <th>Penerbit</th>
                    <td><?= htmlspecialchars($loan['penerbit']) ?></td>
                </tr>
                <tr>
                    <th>Tahun Terbit</th>
                    <td><?= htmlspecialchars($loan['tahun_terbit']) ?></td>
                </tr>
                <?php if (!empty($loan['isbn'])): ?>
                <tr>
                    <th>ISBN</th>
                    <td><?= htmlspecialchars($loan['isbn']) ?></td>
                </tr>
                <?php endif; ?>
            </table>
        </div>
    </div>
</div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const sidebarMenuItems = document.querySelectorAll('.sidebar-menu li a');
        sidebarMenuItems.forEach(item => {
            if (item.textContent.trim().includes('Peminjaman')) {
                item.classList.add('active');
            }
        });
    });
</script>

<?php include '../../../includes/footer.php'; ?>

<style>
    .book-cover-container {
        min-height: 150px;
        background-color: #f8f9fa;
        border-radius: 0.25rem;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
    }
    
    .book-cover {
        transition: opacity 0.3s ease-in-out;
        max-width: 100%;
        object-fit: contain;
    }

    body::after {
        position: absolute;
        width: 0;
        height: 0;
        overflow: hidden;
        z-index: -1;
        content: url('../../../assets/img/books/default-book.jpg');
    }
</style>
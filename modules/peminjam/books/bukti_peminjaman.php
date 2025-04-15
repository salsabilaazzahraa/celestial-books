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
    SELECT p.*, b.judul, b.penulis, b.isbn, b.penerbit, b.cover_img,
           u.nama_lengkap, u.no_telepon, u.username
    FROM peminjaman p
    JOIN buku b ON p.buku_id = b.id
    JOIN users u ON p.user_id = u.id
    WHERE p.id = ? AND p.user_id = ?
");
$stmt->execute([$peminjaman_id, $_SESSION['user_id']]);
$peminjaman = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$peminjaman) {
    setMessage('error', 'Data peminjaman tidak ditemukan!');
    redirect('/modules/peminjam/books/index.php');
}

include '../../../includes/header.php';
?>

<div class="container-fluid p-4">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card border-0 shadow-sm" id="bukti-peminjaman">
                <div class="card-header bg-white py-3">
                    <div class="row align-items-center">
                        <div class="col">
                            <h5 class="mb-0 text-primary">Bukti Peminjaman Buku</h5>
                        </div>
                        <div class="col text-end">
                            <button id="print-bukti" class="btn btn-primary btn-sm" onclick="window.print();">
                                <i class="fas fa-print me-1"></i>Cetak
                            </button>
                            <a href="<?= BASE_URL ?>/modules/peminjam/dashboard.php" class="btn btn-outline-secondary btn-sm ms-2 d-print-none">
                                <i class="fas fa-home me-1"></i>Dashboard
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-body p-4">
                    <div class="text-center mb-4 d-none d-print-block">
                        <h4>PERPUSTAKAAN <?= APP_NAME ?></h4>
                        <p>Bukti Peminjaman Buku</p>
                        <hr>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-6">
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
                                <tr>
                                    <td><strong>Status</strong></td>
                                    <td>: 
                                        <?php if ($peminjaman['status_peminjaman'] === 'dipinjam'): ?>
                                            <span class="badge bg-primary">Dipinjam</span>
                                        <?php elseif ($peminjaman['status_peminjaman'] === 'dikembalikan'): ?>
                                            <span class="badge bg-success">Dikembalikan</span>
                                        <?php elseif ($peminjaman['status_peminjaman'] === 'pengajuan_pengembalian'): ?>
                                            <span class="badge bg-warning">Proses Pengembalian</span>
                                        <?php elseif ($peminjaman['status_peminjaman'] === 'terlambat'): ?>
                                            <span class="badge bg-danger">Terlambat</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td width="150"><strong>Nama Peminjam</strong></td>
                                    <td>: <?= htmlspecialchars($peminjaman['nama_lengkap']) ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Username</strong></td>
                                    <td>: <?= htmlspecialchars($peminjaman['username']) ?></td>
                                </tr>
                                <tr>
                                    <td><strong>No. Telepon</strong></td>
                                    <td>: <?= htmlspecialchars($peminjaman['no_telepon']) ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    
                    <h5 class="border-bottom pb-2 mb-3">Detail Buku</h5>
                    <div class="row">
                        <div class="col-md-3 text-center">
                            <?php
                            $coverImg = !empty($peminjaman['cover_img']) ? $peminjaman['cover_img'] : 'book' . rand(11, 15) . '.jpg';
                            $imagePath = BASE_URL . '/assets/img/books/' . $coverImg;
                            ?>
                            <img src="<?= $imagePath ?>" 
                                 class="img-fluid rounded shadow-sm mb-3" 
                                 alt="<?= htmlspecialchars($peminjaman['judul']) ?>"
                                 style="max-height: 150px; object-fit: contain;"
                                 onerror="this.src='<?= BASE_URL ?>/assets/img/books/book<?= rand(11, 15) ?>.jpg';">
                        </div>
                        <div class="col-md-9">
                            <table class="table table-bordered">
                                <tr>
                                    <th width="30%">Judul Buku</th>
                                    <td><?= htmlspecialchars($peminjaman['judul']) ?></td>
                                </tr>
                                <tr>
                                    <th>Penulis</th>
                                    <td><?= htmlspecialchars($peminjaman['penulis']) ?></td>
                                </tr>
                                <tr>
                                    <th>Penerbit</th>
                                    <td><?= htmlspecialchars($peminjaman['penerbit']) ?></td>
                                </tr>
                                <tr>
                                    <th>ISBN</th>
                                    <td><?= htmlspecialchars($peminjaman['isbn']) ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    
                    <?php if (!empty($peminjaman['catatan'])): ?>
                    <div class="mt-3">
                        <h5 class="border-bottom pb-2 mb-2">Catatan</h5>
                        <p><?= nl2br(htmlspecialchars($peminjaman['catatan'])) ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <div class="alert alert-info mt-4">
                        <i class="fas fa-info-circle me-2"></i>
                        Buku harus dikembalikan paling lambat pada tanggal 
                        <strong><?= date('d/m/Y', strtotime($peminjaman['tanggal_pengembalian'])) ?></strong>. 
                        Keterlambatan akan dikenakan denda sesuai dengan ketentuan yang berlaku.
                    </div>
                    
                    <div class="row mt-5 d-print-block">
                        <div class="col-md-6 offset-md-6 text-center">
                            <p>
                                <?= date('d F Y') ?><br>
                                Perpustakaan <?= APP_NAME ?>
                            </p>
                            <br><br><br>
                            <p>______________________</p>
                            <p>Petugas Perpustakaan</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    .sidebar, .navbar, footer, .d-print-none {
        display: none !important;
    }
    .card {
        border: none !important;
        box-shadow: none !important;
    }
    body {
        margin: 0;
        padding: 0;
    }
    .container-fluid {
        width: 100%;
        padding: 0;
    }
    #bukti-peminjaman {
        width: 100%;
        max-width: none;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const printBtn = document.getElementById('print-bukti');
    if (printBtn) {
        printBtn.addEventListener('click', function() {
            window.print();
        });
    }
});
</script>

<?php include '../../../includes/footer.php'; ?>

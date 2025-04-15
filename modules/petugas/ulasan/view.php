<?php
session_start();
require_once '../../../config/functions.php';
require_once '../../../config/database.php';

if (!isPetugas()) {
    setMessage('error', 'Akses ditolak!');
    redirect('/auth/login.php');
}

// Validasi parameter
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    setMessage('error', 'Parameter tidak valid!');
    redirect('/modules/petugas/ulasan/index.php');
}

$ulasan_id = intval($_GET['id']);

$db = new Database();
$conn = $db->getConnection();

// Query untuk mengambil detail ulasan
$query = "
    SELECT u.*, 
           b.judul, b.penulis, b.penerbit, b.tahun_terbit, b.cover_img, 
           us.username, us.nama_lengkap, us.email 
    FROM ulasan_buku u
    JOIN buku b ON u.buku_id = b.id
    JOIN users us ON u.user_id = us.id
    WHERE u.id = ?
";

$stmt = $conn->prepare($query);
$stmt->execute([$ulasan_id]);
$ulasan = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ulasan) {
    setMessage('error', 'Data ulasan tidak ditemukan!');
    redirect('/modules/petugas/ulasan/index.php');
}

include '../../../includes/header.php';
?>

<div class="container-fluid p-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 text-primary">Detail Ulasan Buku</h5>
                    <a href="index.php" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Kembali
                    </a>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-4 mb-md-0">
                            <div class="text-center mb-3">
                                <?php
                                $coverImg = !empty($ulasan['cover_img']) ? $ulasan['cover_img'] : 'book' . rand(11, 15) . '.jpg';
                                $imagePath = BASE_URL . '/assets/img/books/' . $coverImg;
                                ?>
                                <img src="<?= $imagePath ?>" 
                                     class="img-fluid rounded shadow-sm" 
                                     alt="<?= htmlspecialchars($ulasan['judul']) ?>"
                                     style="max-height: 300px;"
                                     onerror="this.src='<?= BASE_URL ?>/assets/img/books/book<?= rand(11, 15) ?>.jpg';">
                            </div>
                            <div class="card border-0 shadow-sm">
                                <div class="card-header bg-white">
                                    <h6 class="mb-0">Informasi Buku</h6>
                                </div>
                                <div class="card-body">
                                    <h5 class="mb-1"><?= htmlspecialchars($ulasan['judul']) ?></h5>
                                    <p class="text-muted mb-2"><?= htmlspecialchars($ulasan['penulis']) ?></p>
                                    <hr>
                                    <div class="mb-2">
                                        <small class="text-muted">Penerbit:</small>
                                        <p class="mb-0"><?= htmlspecialchars($ulasan['penerbit']) ?></p>
                                    </div>
                                    <div class="mb-2">
                                        <small class="text-muted">Tahun Terbit:</small>
                                        <p class="mb-0"><?= htmlspecialchars($ulasan['tahun_terbit']) ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="card border-0 shadow-sm mb-4">
                                <div class="card-header bg-white">
                                    <h6 class="mb-0">Informasi Pengulas</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <small class="text-muted">Nama Lengkap:</small>
                                            <p class="mb-0"><?= htmlspecialchars($ulasan['nama_lengkap']) ?></p>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <small class="text-muted">Username:</small>
                                            <p class="mb-0"><?= htmlspecialchars($ulasan['username']) ?></p>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <small class="text-muted">Email:</small>
                                            <p class="mb-0"><?= htmlspecialchars($ulasan['email']) ?></p>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <small class="text-muted">Tanggal Ulasan:</small>
                                            <p class="mb-0"><?= date('d/m/Y H:i', strtotime($ulasan['created_at'])) ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="card border-0 shadow-sm mb-4">
                                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0">Ulasan</h6>
                                    <div>
                                        <span class="me-2">Rating:</span>
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="fas fa-star <?= $i <= $ulasan['rating'] ? 'text-warning' : 'text-muted' ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="p-3 bg-light rounded mb-3">
                                        <?= nl2br(htmlspecialchars($ulasan['ulasan'])) ?>
                                    </div>
                                    
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <span class="me-2">Status:</span>
                                            <?php if ($ulasan['status'] === 'pending'): ?>
                                                <span class="badge bg-warning">Menunggu Persetujuan</span>
                                            <?php elseif ($ulasan['status'] === 'approved'): ?>
                                                <span class="badge bg-success">Disetujui</span>
                                            <?php elseif ($ulasan['status'] === 'rejected'): ?>
                                                <span class="badge bg-danger">Ditolak</span>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <?php if ($ulasan['status'] === 'pending'): ?>
                                                <a href="approve.php?id=<?= $ulasan['id'] ?>" class="btn btn-sm btn-success me-1" onclick="return confirm('Apakah Anda yakin ingin menyetujui ulasan ini?')">
                                                    <i class="fas fa-check"></i> Setujui
                                                </a>
                                                <a href="reject.php?id=<?= $ulasan['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Apakah Anda yakin ingin menolak ulasan ini?')">
                                                    <i class="fas fa-times"></i> Tolak
                                                </a>
                                            <?php elseif ($ulasan['status'] === 'approved'): ?>
                                                <a href="reject.php?id=<?= $ulasan['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Apakah Anda yakin ingin menolak ulasan ini?')">
                                                    <i class="fas fa-times"></i> Batalkan Persetujuan
                                                </a>
                                            <?php elseif ($ulasan['status'] === 'rejected'): ?>
                                                <a href="approve.php?id=<?= $ulasan['id'] ?>" class="btn btn-sm btn-outline-success" onclick="return confirm('Apakah Anda yakin ingin menyetujui ulasan ini?')">
                                                    <i class="fas fa-check"></i> Setujui
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../../includes/footer.php'; ?>
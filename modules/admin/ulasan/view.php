<?php
session_start();
require_once '../../../config/functions.php';
require_once '../../../config/database.php';

if (!isAdmin()) {
    setMessage('error', 'Akses ditolak!');
    redirect('/auth/login.php');
}

$db = new Database();
$conn = $db->getConnection();

// Cek apakah parameter id ada
if (!isset($_GET['id']) || empty($_GET['id'])) {
    setMessage('error', 'ID ulasan tidak valid!');
    redirect('index.php');
}

$id = $_GET['id'];

// Query untuk mengambil data ulasan buku berdasarkan id
$query = "
    SELECT u.*, b.judul, b.penulis, b.cover_img, us.username, us.nama_lengkap, us.email
    FROM ulasan_buku u
    JOIN buku b ON u.buku_id = b.id
    JOIN users us ON u.user_id = us.id
    WHERE u.id = ?
";

$stmt = $conn->prepare($query);
$stmt->execute([$id]);
$ulasan = $stmt->fetch(PDO::FETCH_ASSOC);

// Jika data tidak ditemukan
if (!$ulasan) {
    setMessage('error', 'Data ulasan tidak ditemukan!');
    redirect('index.php');
}

include '../../../includes/header.php';
?>

<div class="container-fluid p-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 text-primary">Detail Ulasan Buku</h5>
                    <a href="index.php" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-arrow-left me-1"></i> Kembali
                    </a>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 mb-4">
                            <?php
                            $coverImg = !empty($ulasan['cover_img']) ? $ulasan['cover_img'] : 'book' . rand(11, 15) . '.jpg';
                            $imagePath = BASE_URL . '/assets/img/books/' . $coverImg;
                            ?>
                            <img src="<?= $imagePath ?>" 
                                 class="img-fluid rounded shadow-sm" 
                                 alt="<?= htmlspecialchars($ulasan['judul']) ?>"
                                 onerror="this.src='<?= BASE_URL ?>/assets/img/books/book<?= rand(11, 15) ?>.jpg';">
                        </div>
                        <div class="col-md-9">
                            <h4 class="mb-3"><?= htmlspecialchars($ulasan['judul']) ?></h4>
                            <p class="text-muted">Penulis: <?= htmlspecialchars($ulasan['penulis']) ?></p>
                            
                            <div class="border-top my-3 pt-3">
                                <h5>Informasi Ulasan</h5>
                                <table class="table table-borderless">
                                    <tr>
                                        <td width="200">Nama Pengguna</td>
                                        <td>: <?= htmlspecialchars($ulasan['nama_lengkap']) ?> (<?= htmlspecialchars($ulasan['username']) ?>)</td>
                                    </tr>
                                    <tr>
                                        <td>Email</td>
                                        <td>: <?= htmlspecialchars($ulasan['email']) ?></td>
                                    </tr>
                                    <tr>
                                        <td>Rating</td>
                                        <td>: 
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <i class="fas fa-star <?= $i <= $ulasan['rating'] ? 'text-warning' : 'text-muted' ?>"></i>
                                            <?php endfor; ?>
                                            (<?= $ulasan['rating'] ?>/5)
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Tanggal Ulasan</td>
                                        <td>: <?= date('d/m/Y H:i', strtotime($ulasan['created_at'])) ?></td>
                                    </tr>
                                    <tr>
                                        <td>Status</td>
                                        <td>: 
                                            <?php if ($ulasan['status'] === 'pending'): ?>
                                                <span class="badge bg-warning">Menunggu Persetujuan</span>
                                            <?php elseif ($ulasan['status'] === 'approved'): ?>
                                                <span class="badge bg-success">Disetujui</span>
                                            <?php elseif ($ulasan['status'] === 'rejected'): ?>
                                                <span class="badge bg-danger">Ditolak</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                            
                            <div class="border-top my-3 pt-3">
                                <h5>Isi Ulasan</h5>
                                <div class="card">
                                    <div class="card-body bg-light">
                                        <?= nl2br(htmlspecialchars($ulasan['ulasan'])) ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mt-4">
                                <?php if ($ulasan['status'] === 'pending'): ?>
                                    <a href="approve.php?id=<?= $ulasan['id'] ?>" class="btn btn-success me-2" onclick="return confirm('Apakah Anda yakin ingin menyetujui ulasan ini?')">
                                        <i class="fas fa-check me-1"></i> Setujui Ulasan
                                    </a>
                                    <a href="reject.php?id=<?= $ulasan['id'] ?>" class="btn btn-danger me-2" onclick="return confirm('Apakah Anda yakin ingin menolak ulasan ini?')">
                                        <i class="fas fa-times me-1"></i> Tolak Ulasan
                                    </a>
                                <?php elseif ($ulasan['status'] === 'approved'): ?>
                                    <a href="reject.php?id=<?= $ulasan['id'] ?>" class="btn btn-outline-danger me-2" onclick="return confirm('Apakah Anda yakin ingin menolak ulasan ini?')">
                                        <i class="fas fa-times me-1"></i> Batalkan Persetujuan
                                    </a>
                                <?php elseif ($ulasan['status'] === 'rejected'): ?>
                                    <a href="approve.php?id=<?= $ulasan['id'] ?>" class="btn btn-outline-success me-2" onclick="return confirm('Apakah Anda yakin ingin menyetujui ulasan ini?')">
                                        <i class="fas fa-check me-1"></i> Setujui Ulasan
                                    </a>
                                <?php endif; ?>
                                <a href="delete.php?id=<?= $ulasan['id'] ?>" class="btn btn-outline-danger" onclick="return confirm('Apakah Anda yakin ingin menghapus ulasan ini? Tindakan ini tidak dapat dibatalkan.')">
                                    <i class="fas fa-trash me-1"></i> Hapus Ulasan
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../../includes/footer.php'; ?>
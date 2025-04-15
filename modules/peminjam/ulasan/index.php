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

// Ambil daftar buku yang sudah dikembalikan dan belum diulas
$query = "
    SELECT p.*, b.judul, b.penulis, b.cover_img 
    FROM peminjaman p
    JOIN buku b ON p.buku_id = b.id
    LEFT JOIN ulasan_buku u ON p.buku_id = u.buku_id AND p.user_id = u.user_id
    WHERE p.user_id = ? 
    AND p.status_peminjaman = 'dikembalikan'
    AND u.id IS NULL
    ORDER BY p.tanggal_pengembalian DESC
";

$stmt = $conn->prepare($query);
$stmt->execute([$_SESSION['user_id']]);
$buku_belum_diulas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Ambil daftar ulasan yang sudah diberikan
$query = "
    SELECT u.*, b.judul, b.penulis, b.cover_img 
    FROM ulasan_buku u
    JOIN buku b ON u.buku_id = b.id
    WHERE u.user_id = ?
    ORDER BY u.created_at DESC
";

$stmt = $conn->prepare($query);
$stmt->execute([$_SESSION['user_id']]);
$ulasan_saya = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../../../includes/header.php';
?>

<div class="container-fluid p-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0 text-primary">Buku yang Belum Diulas</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($buku_belum_diulas)): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Tidak ada buku yang perlu diulas saat ini.
                        </div>
                    <?php else: ?>
                        <div class="row">
                            <?php foreach ($buku_belum_diulas as $buku): ?>
                                <div class="col-md-4 col-lg-3 mb-4">
                                    <div class="card h-100">
                                        <?php
                                        $coverImg = !empty($buku['cover_img']) ? $buku['cover_img'] : 'book' . rand(11, 15) . '.jpg';
                                        $imagePath = BASE_URL . '/assets/img/books/' . $coverImg;
                                        ?>
                                        <div class="card-img-top-wrapper" style="height: 250px; overflow: hidden;">
                                            <img src="<?= $imagePath ?>" 
                                                 class="card-img-top" 
                                                 alt="<?= htmlspecialchars($buku['judul']) ?>"
                                                 style="width: 100%; height: 100%; object-fit: cover;"
                                                 onerror="this.src='<?= BASE_URL ?>/assets/img/books/book<?= rand(11, 15) ?>.jpg';">
                                        </div>
                                        <div class="card-body">
                                            <h6 class="card-title"><?= htmlspecialchars($buku['judul']) ?></h6>
                                            <p class="card-text text-muted small"><?= htmlspecialchars($buku['penulis']) ?></p>
                                            <div class="text-end">
                                                <a href="form_ulasan.php?peminjaman_id=<?= $buku['id'] ?>&buku_id=<?= $buku['buku_id'] ?>" class="btn btn-sm btn-primary">
                                                    <i class="fas fa-star me-1"></i> Beri Ulasan
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0 text-primary">Ulasan Saya</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($ulasan_saya)): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Anda belum memberikan ulasan.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Buku</th>
                                        <th>Rating</th>
                                        <th>Ulasan</th>
                                        <th>Tanggal</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($ulasan_saya as $key => $ulasan): ?>
                                        <tr>
                                            <td><?= $key + 1 ?></td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <?php
                                                    $coverImg = !empty($ulasan['cover_img']) ? $ulasan['cover_img'] : 'book' . rand(11, 15) . '.jpg';
                                                    $imagePath = BASE_URL . '/assets/img/books/' . $coverImg;
                                                    ?>
                                                    <div class="book-cover-wrapper me-3" style="width: 50px; height: 70px; overflow: hidden;">
                                                        <img src="<?= $imagePath ?>" 
                                                             class="rounded" 
                                                             alt="<?= htmlspecialchars($ulasan['judul']) ?>"
                                                             style="width: 100%; height: 100%; object-fit: cover;"
                                                             onerror="this.src='<?= BASE_URL ?>/assets/img/books/book<?= rand(11, 15) ?>.jpg';">
                                                    </div>
                                                    <div>
                                                        <h6 class="mb-0"><?= htmlspecialchars($ulasan['judul']) ?></h6>
                                                        <small class="text-muted"><?= htmlspecialchars($ulasan['penulis']) ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="text-warning">
                                                    <?php 
                                                    // Pastikan rating adalah nilai numerik valid
                                                    $rating = isset($ulasan['rating']) ? intval($ulasan['rating']) : 0;
                                                    for ($i = 1; $i <= 5; $i++): 
                                                    ?>
                                                        <?php if ($i <= $rating): ?>
                                                            <i class="fas fa-star"></i>
                                                        <?php else: ?>
                                                            <i class="far fa-star text-secondary"></i>
                                                        <?php endif; ?>
                                                    <?php endfor; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <?php 
                                                $shortUlasan = strlen($ulasan['ulasan']) > 50 ? substr($ulasan['ulasan'], 0, 50) . '...' : $ulasan['ulasan'];
                                                echo htmlspecialchars($shortUlasan);
                                                ?>
                                            </td>
                                            <td><?= date('d/m/Y', strtotime($ulasan['created_at'])) ?></td>
                                            <td>
                                                <?php if ($ulasan['status'] == 'approved'): ?>
                                                    <span class="badge bg-success">Disetujui</span>
                                                <?php elseif ($ulasan['status'] == 'rejected'): ?>
                                                    <span class="badge bg-danger">Ditolak</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning text-dark">Menunggu</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <!-- Modal button -->
                                                <button type="button" class="btn btn-sm btn-info view-detail" data-bs-toggle="modal" data-bs-target="#ulasanModal<?= $ulasan['id'] ?>">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                
                                                <?php if ($ulasan['status'] == 'pending'): ?>
                                                    <a href="edit_ulasan.php?id=<?= $ulasan['id'] ?>" class="btn btn-sm btn-warning">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?= $ulasan['id'] ?>">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
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

<!-- Modal Detail Ulasan -->
<?php foreach ($ulasan_saya as $ulasan): ?>
<div class="modal fade" id="ulasanModal<?= $ulasan['id'] ?>" tabindex="-1" aria-labelledby="ulasanModalLabel<?= $ulasan['id'] ?>" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="ulasanModalLabel<?= $ulasan['id'] ?>">Detail Ulasan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-3">
                    <?php
                    $coverImg = !empty($ulasan['cover_img']) ? $ulasan['cover_img'] : 'book' . rand(11, 15) . '.jpg';
                    $imagePath = BASE_URL . '/assets/img/books/' . $coverImg;
                    ?>
                    <div class="book-cover-modal mb-3 mx-auto" style="width: 120px; height: 180px; overflow: hidden;">
                        <img src="<?= $imagePath ?>" 
                             class="rounded" 
                             alt="<?= htmlspecialchars($ulasan['judul']) ?>"
                             style="width: 100%; height: 100%; object-fit: cover;"
                             onerror="this.src='<?= BASE_URL ?>/assets/img/books/book<?= rand(11, 15) ?>.jpg';">
                    </div>
                    <h5><?= htmlspecialchars($ulasan['judul']) ?></h5>
                    <p class="text-muted"><?= htmlspecialchars($ulasan['penulis']) ?></p>
                </div>
                
                <div class="mb-3 text-center">
                    <p><strong>Rating:</strong></p>
                    <div class="text-warning fs-4">
                        <?php 
                        // Pastikan rating adalah nilai numerik valid
                        $rating = isset($ulasan['rating']) ? intval($ulasan['rating']) : 0;
                        for ($i = 1; $i <= 5; $i++): 
                        ?>
                            <?php if ($i <= $rating): ?>
                                <i class="fas fa-star"></i>
                            <?php else: ?>
                                <i class="far fa-star text-secondary"></i>
                            <?php endif; ?>
                        <?php endfor; ?>
                    </div>
                </div>
                
                <div class="mb-3">
                    <p><strong>Ulasan:</strong></p>
                    <p class="border p-3 rounded"><?= nl2br(htmlspecialchars($ulasan['ulasan'])) ?></p>
                </div>
                
                <div class="d-flex justify-content-between">
                    <p><strong>Tanggal:</strong> <?= date('d/m/Y H:i', strtotime($ulasan['created_at'])) ?></p>
                    <p>
                        <strong>Status:</strong> 
                        <?php if ($ulasan['status'] == 'approved'): ?>
                            <span class="badge bg-success">Disetujui</span>
                        <?php elseif ($ulasan['status'] == 'rejected'): ?>
                            <span class="badge bg-danger">Ditolak</span>
                        <?php else: ?>
                            <span class="badge bg-warning text-dark">Menunggu</span>
                        <?php endif; ?>
                    </p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>

<!-- Modal Delete Confirmation -->
<?php foreach ($ulasan_saya as $ulasan): ?>
<?php if ($ulasan['status'] == 'pending'): ?>
<div class="modal fade" id="deleteModal<?= $ulasan['id'] ?>" tabindex="-1" aria-labelledby="deleteModalLabel<?= $ulasan['id'] ?>" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel<?= $ulasan['id'] ?>">Konfirmasi Hapus</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Apakah Anda yakin ingin menghapus ulasan untuk buku <strong><?= htmlspecialchars($ulasan['judul']) ?></strong>?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <a href="delete_ulasan.php?id=<?= $ulasan['id'] ?>" class="btn btn-danger">Hapus</a>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
<?php endforeach; ?>

<script>
// Mencegah modal kedap-kedip
document.addEventListener('DOMContentLoaded', function() {
    const modals = document.querySelectorAll('.modal');
    
    // Mengatasi masalah kedap-kedip dengan menghentikan propagasi event
    modals.forEach(function(modal) {
        modal.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    });
    
    // Mencegah modal terbuka otomatis
    document.querySelectorAll('.view-detail').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    });
});
</script>

<?php include '../../../includes/footer.php'; ?>
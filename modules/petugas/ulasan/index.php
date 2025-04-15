<?php
session_start();
require_once '../../../config/functions.php';
require_once '../../../config/database.php';

if (!isPetugas()) {
    setMessage('error', 'Akses ditolak!');
    redirect('/auth/login.php');
}

$db = new Database();
$conn = $db->getConnection();

// Filter status ulasan
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$status_condition = ($status_filter !== 'all') ? "AND u.status = ?" : "";

// Query untuk mengambil data ulasan buku
$query = "
    SELECT u.*, b.judul, b.penulis, b.cover_img, us.username, us.nama_lengkap
    FROM ulasan_buku u
    JOIN buku b ON u.buku_id = b.id
    JOIN users us ON u.user_id = us.id
    WHERE 1=1 $status_condition
    ORDER BY u.created_at DESC
";

$stmt = $conn->prepare($query);
if ($status_filter !== 'all') {
    $stmt->execute([$status_filter]);
} else {
    $stmt->execute();
}

$ulasan_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../../../includes/header.php';
?>

<div class="container-fluid p-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 text-primary">Manajemen Ulasan Buku</h5>
                    <div class="btn-group">
                        <a href="index.php?status=all" class="btn btn-sm <?= $status_filter === 'all' ? 'btn-primary' : 'btn-outline-primary' ?>">
                            Semua
                        </a>
                        <a href="index.php?status=pending" class="btn btn-sm <?= $status_filter === 'pending' ? 'btn-primary' : 'btn-outline-primary' ?>">
                            Menunggu
                        </a>
                        <a href="index.php?status=approved" class="btn btn-sm <?= $status_filter === 'approved' ? 'btn-primary' : 'btn-outline-primary' ?>">
                            Disetujui
                        </a>
                        <a href="index.php?status=rejected" class="btn btn-sm <?= $status_filter === 'rejected' ? 'btn-primary' : 'btn-outline-primary' ?>">
                            Ditolak
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (empty($ulasan_list)): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Tidak ada ulasan yang ditemukan.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover" id="dataTable">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Peminjam</th>
                                        <th>Buku</th>
                                        <th>Rating</th>
                                        <th>Ulasan</th>
                                        <th>Tanggal</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($ulasan_list as $key => $ulasan): ?>
                                        <tr>
                                            <td><?= $key + 1 ?></td>
                                            <td><?= htmlspecialchars($ulasan['nama_lengkap']) ?> (<?= htmlspecialchars($ulasan['username']) ?>)</td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <?php
                                                    $coverImg = !empty($ulasan['cover_img']) ? $ulasan['cover_img'] : 'book' . rand(11, 15) . '.jpg';
                                                    $imagePath = BASE_URL . '/assets/img/books/' . $coverImg;
                                                    ?>
                                                    <img src="<?= $imagePath ?>" 
                                                         class="rounded me-3" 
                                                         alt="<?= htmlspecialchars($ulasan['judul']) ?>"
                                                         width="50"
                                                         height="60"
                                                         onerror="this.src='<?= BASE_URL ?>/assets/img/books/book<?= rand(11, 15) ?>.jpg';">
                                                    <div>
                                                        <h6 class="mb-0"><?= htmlspecialchars($ulasan['judul']) ?></h6>
                                                        <small class="text-muted"><?= htmlspecialchars($ulasan['penulis']) ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <i class="fas fa-star <?= $i <= $ulasan['rating'] ? 'text-warning' : 'text-muted' ?>"></i>
                                                <?php endfor; ?>
                                            </td>
                                            <td><?= nl2br(htmlspecialchars($ulasan['ulasan'])) ?></td>
                                            <td><?= date('d/m/Y H:i', strtotime($ulasan['created_at'])) ?></td>
                                            <td>
                                                <?php if ($ulasan['status'] === 'pending'): ?>
                                                    <span class="badge bg-warning">Menunggu Persetujuan</span>
                                                <?php elseif ($ulasan['status'] === 'approved'): ?>
                                                    <span class="badge bg-success">Disetujui</span>
                                                <?php elseif ($ulasan['status'] === 'rejected'): ?>
                                                    <span class="badge bg-danger">Ditolak</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($ulasan['status'] === 'pending'): ?>
                                                    <a href="approve.php?id=<?= $ulasan['id'] ?>" class="btn btn-sm btn-success mb-1" onclick="return confirm('Apakah Anda yakin ingin menyetujui ulasan ini?')">
                                                        <i class="fas fa-check"></i> Setujui
                                                    </a>
                                                    <a href="reject.php?id=<?= $ulasan['id'] ?>" class="btn btn-sm btn-danger mb-1" onclick="return confirm('Apakah Anda yakin ingin menolak ulasan ini?')">
                                                        <i class="fas fa-times"></i> Tolak
                                                    </a>
                                                <?php elseif ($ulasan['status'] === 'approved'): ?>
                                                    <a href="reject.php?id=<?= $ulasan['id'] ?>" class="btn btn-sm btn-outline-danger mb-1" onclick="return confirm('Apakah Anda yakin ingin menolak ulasan ini?')">
                                                        <i class="fas fa-times"></i> Batalkan
                                                    </a>
                                                <?php elseif ($ulasan['status'] === 'rejected'): ?>
                                                    <a href="approve.php?id=<?= $ulasan['id'] ?>" class="btn btn-sm btn-outline-success mb-1" onclick="return confirm('Apakah Anda yakin ingin menyetujui ulasan ini?')">
                                                        <i class="fas fa-check"></i> Setujui
                                                    </a>
                                                <?php endif; ?>
                                                <a href="view.php?id=<?= $ulasan['id'] ?>" class="btn btn-sm btn-info mb-1">
                                                    <i class="fas fa-eye"></i> Detail
                                                </a>
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

<script>
    $(document).ready(function() {
        $('#dataTable').DataTable({
            language: {
                url: '<?= BASE_URL ?>/assets/plugins/dataTables/id.json'
            }
        });
    });
</script>

<?php include '../../../includes/footer.php'; ?>
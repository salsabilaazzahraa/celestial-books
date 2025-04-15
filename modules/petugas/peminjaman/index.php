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

// Filter untuk pencarian dan pengurutan
$search = isset($_GET['search']) ? $_GET['search'] : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';

// Query dasar
$query = "
    SELECT p.*, u.username, b.judul, b.penulis 
    FROM peminjaman p 
    JOIN users u ON p.user_id = u.id 
    JOIN buku b ON p.buku_id = b.id 
    WHERE 1=1
";

// Tambahkan filter pencarian
if (!empty($search)) {
    $query .= " AND (u.username LIKE :search OR b.judul LIKE :search OR b.penulis LIKE :search)";
}

// Tambahkan filter status
if (!empty($status)) {
    $query .= " AND p.status_peminjaman = :status";
}

// Tambahkan pengurutan
if ($sort == 'oldest') {
    $query .= " ORDER BY p.tanggal_peminjaman ASC";
} elseif ($sort == 'newest') {
    $query .= " ORDER BY p.tanggal_peminjaman DESC";
} elseif ($sort == 'return_date') {
    $query .= " ORDER BY p.tanggal_pengembalian ASC";
}

// Eksekusi query dengan parameter
$stmt = $conn->prepare($query);

if (!empty($search)) {
    $searchParam = "%$search%";
    $stmt->bindParam(':search', $searchParam);
}

if (!empty($status)) {
    $stmt->bindParam(':status', $status);
}

$stmt->execute();
$peminjaman = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../../../includes/header.php';
?>

<div class="container-fluid p-3">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">Daftar Peminjaman</h1>
        <!-- Tombol Tambah Peminjaman dihapus dari sini -->
    </div>

    <!-- Alert Message -->
    <?php showMessage(); ?>

    <!-- Filter dan Pencarian -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label for="search" class="form-label">Pencarian</label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="search" name="search" 
                               placeholder="Cari peminjam atau judul buku..." value="<?= htmlspecialchars($search) ?>">
                        <button class="btn btn-primary" type="submit">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
                <div class="col-md-3">
                    <label for="status" class="form-label">Status Peminjaman</label>
                    <select class="form-select" id="status" name="status" onchange="this.form.submit()">
                        <option value="">Semua Status</option>
                        <option value="dipinjam" <?= $status == 'dipinjam' ? 'selected' : '' ?>>Dipinjam</option>
                        <option value="dikembalikan" <?= $status == 'dikembalikan' ? 'selected' : '' ?>>Dikembalikan</option>
                        <option value="terlambat" <?= $status == 'terlambat' ? 'selected' : '' ?>>Terlambat</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="sort" class="form-label">Urutkan</label>
                    <select class="form-select" id="sort" name="sort" onchange="this.form.submit()">
                        <option value="newest" <?= $sort == 'newest' ? 'selected' : '' ?>>Terbaru</option>
                        <option value="oldest" <?= $sort == 'oldest' ? 'selected' : '' ?>>Terlama</option>
                        <option value="return_date" <?= $sort == 'return_date' ? 'selected' : '' ?>>Tanggal Pengembalian</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <a href="index.php" class="btn btn-outline-secondary w-100">
                        <i class="fas fa-sync-alt"></i> Reset Filter
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabel Peminjaman -->
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Peminjam</th>
                            <th>Buku</th>
                            <th>Tanggal Pinjam</th>
                            <th>Batas Kembali</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($peminjaman)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-3">Tidak ada data peminjaman</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($peminjaman as $pinjam): ?>
                                <?php
                                // Hitung tanggal batas pengembalian (7 hari setelah peminjaman)
                                $due_date = date('Y-m-d', strtotime('+7 days', strtotime($pinjam['tanggal_peminjaman'])));
                                
                                // Cek apakah sudah terlambat
                                $is_late = false;
                                if ($pinjam['status_peminjaman'] == 'dipinjam' && strtotime($due_date) < time()) {
                                    $is_late = true;
                                }
                                ?>
                                <tr>
                                    <td><?= $pinjam['id'] ?></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="https://ui-avatars.com/api/?name=<?= urlencode($pinjam['username']) ?>&background=random"
                                                 class="rounded-circle me-2"
                                                 width="32"
                                                 height="32"
                                                 alt="Avatar">
                                            <?= htmlspecialchars($pinjam['username']) ?>
                                        </div>
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($pinjam['judul']) ?></strong><br>
                                        <small class="text-muted"><?= htmlspecialchars($pinjam['penulis']) ?></small>
                                    </td>
                                    <td><?= date('d/m/Y', strtotime($pinjam['tanggal_peminjaman'])) ?></td>
                                    <td>
                                        <?= date('d/m/Y', strtotime($due_date)) ?>
                                        <?php if ($is_late): ?>
                                            <span class="badge bg-danger ms-1">Terlambat</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($pinjam['status_peminjaman'] == 'dipinjam'): ?>
                                            <span class="badge rounded-pill bg-primary">Dipinjam</span>
                                        <?php elseif ($pinjam['status_peminjaman'] == 'dikembalikan'): ?>
                                            <span class="badge rounded-pill bg-success">Dikembalikan</span>
                                            <?php if ($pinjam['tanggal_dikembalikan']): ?>
                                                <br><small>pada <?= date('d/m/Y', strtotime($pinjam['tanggal_dikembalikan'])) ?></small>
                                            <?php endif; ?>
                                        <?php elseif ($pinjam['status_peminjaman'] == 'terlambat'): ?>
                                            <span class="badge rounded-pill bg-danger">Terlambat</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="detail.php?id=<?= $pinjam['id'] ?>" class="btn btn-sm btn-info text-white">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <?php if ($pinjam['status_peminjaman'] == 'dipinjam'): ?>
                                                <a href="../pengembalian/process.php?id=<?= $pinjam['id'] ?>" class="btn btn-sm btn-success">
                                                    <i class="fas fa-undo"></i> Kembalikan
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../../../includes/footer.php'; ?>
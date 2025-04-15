<?php
session_start();
require_once '../../../config/functions.php';
require_once '../../../config/database.php';

if (!isAdmin() && !isPetugas()) {
    setMessage('danger', 'Akses ditolak!');
    redirect('/auth/login.php');
}

$db = new Database();
$conn = $db->getConnection();

// Proses filter dan pencarian
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$kategori = isset($_GET['kategori_id']) ? sanitize($_GET['kategori_id']) : '';
$tahun = isset($_GET['tahun']) ? sanitize($_GET['tahun']) : '';

// Halaman untuk pagination
$halaman = isset($_GET['halaman']) ? (int)$_GET['halaman'] : 1;
$batas = 10;
$start = ($halaman - 1) * $batas;

// Query untuk mendapatkan semua kategori
$kategoris = $conn->query("SELECT * FROM kategori_buku ORDER BY nama_kategori ASC")->fetchAll(PDO::FETCH_ASSOC);

// Query untuk mendapatkan tahun terbit unik
$tahun_terbit = $conn->query("SELECT DISTINCT tahun_terbit FROM buku ORDER BY tahun_terbit DESC")->fetchAll(PDO::FETCH_COLUMN);

// Base query dengan pagination
$query = "
    SELECT b.*, GROUP_CONCAT(k.nama_kategori SEPARATOR ', ') as kategori_nama 
    FROM buku b
    LEFT JOIN kategori_buku_relasi r ON b.id = r.buku_id
    LEFT JOIN kategori_buku k ON r.kategori_id = k.id
    WHERE 1=1
";

// Filter berdasarkan pencarian
if (!empty($search)) {
    $query .= " AND (b.judul LIKE :search OR b.penulis LIKE :search OR b.penerbit LIKE :search OR b.kode_buku LIKE :search)";
}

// Filter berdasarkan kategori
if (!empty($kategori)) {
    $query .= " AND r.kategori_id = :kategori_id";
}

// Filter berdasarkan tahun
if (!empty($tahun)) {
    $query .= " AND b.tahun_terbit = :tahun";
}

$query .= " GROUP BY b.id ORDER BY b.created_at DESC";

// Hitung total data untuk pagination
$total_data_query = str_replace("SELECT b.*, GROUP_CONCAT(k.nama_kategori SEPARATOR ', ') as kategori_nama", "SELECT COUNT(DISTINCT b.id)", $query);
$total_data_stmt = $conn->prepare($total_data_query);

if (!empty($search)) {
    $param = "%{$search}%";
    $total_data_stmt->bindParam(':search', $param);
}

if (!empty($kategori)) {
    $total_data_stmt->bindParam(':kategori_id', $kategori);
}

if (!empty($tahun)) {
    $total_data_stmt->bindParam(':tahun', $tahun);
}

$total_data_stmt->execute();
$total_data = $total_data_stmt->fetchColumn();
$total_halaman = ceil($total_data / $batas);

// Query dengan pagination
$query .= " LIMIT $start, $batas";

$stmt = $conn->prepare($query);

if (!empty($search)) {
    $param = "%{$search}%";
    $stmt->bindParam(':search', $param);
}

if (!empty($kategori)) {
    $stmt->bindParam(':kategori_id', $kategori);
}

if (!empty($tahun)) {
    $stmt->bindParam(':tahun', $tahun);
}

$stmt->execute();
$books = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Mendapatkan statistik
$stats = [
    'total_books' => $conn->query("SELECT COUNT(*) FROM buku")->fetchColumn(),
    'total_categories' => $conn->query("SELECT COUNT(*) FROM kategori_buku")->fetchColumn(),
    'total_borrowed' => $conn->query("SELECT COUNT(*) FROM peminjaman WHERE status_peminjaman = 'dipinjam'")->fetchColumn(),
    'total_returned' => $conn->query("SELECT COUNT(*) FROM peminjaman WHERE status_peminjaman = 'dikembalikan'")->fetchColumn()
];

include '../../../includes/header.php';
?>

<div class="container-fluid p-3">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">Manajemen Buku</h1>
    </div>

    <!-- Alert Messages -->
    <?php if ($message = getMessage()): ?>
        <div class="alert alert-<?= $message['type'] ?> alert-dismissible fade show" role="alert">
            <?= $message['message'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Filter and Search -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="" class="row g-3">
                <div class="col-md-3">
                    <label for="kategori_id" class="form-label">Filter Kategori</label>
                    <select name="kategori_id" id="kategori_id" class="form-select">
                        <option value="">Semua Kategori</option>
                        <?php foreach ($kategoris as $kat): ?>
                            <option value="<?= $kat['id'] ?>" <?= $kategori == $kat['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($kat['nama_kategori']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="tahun" class="form-label">Tahun Terbit</label>
                    <select name="tahun" id="tahun" class="form-select">
                        <option value="">Semua Tahun</option>
                        <?php foreach ($tahun_terbit as $thn): ?>
                            <option value="<?= $thn ?>" <?= $tahun == $thn ? 'selected' : '' ?>>
                                <?= $thn ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-5">
                    <label for="search" class="form-label">Cari Buku</label>
                    <input type="text" name="search" id="search" class="form-control" placeholder="Cari berdasarkan kode, judul, penulis, atau penerbit..." value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search me-2"></i> Cari
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row mb-3">
        <!-- Total Buku -->
        <div class="col-md-3 mb-3">
            <div class="card border-0 rounded-3 shadow-sm stats-card h-100">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-primary mb-1 fw-bold">TOTAL BUKU</h6>
                            <h2 class="mb-0 fw-bold"><?= number_format($stats['total_books']) ?></h2>
                        </div>
                        <div class="bg-primary bg-opacity-10 p-3 rounded-circle">
                            <i class="fas fa-book text-primary fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Kategori -->
        <div class="col-md-3 mb-3">
            <div class="card border-0 rounded-3 shadow-sm stats-card h-100">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-success mb-1 fw-bold">TOTAL KATEGORI</h6>
                            <h2 class="mb-0 fw-bold"><?= number_format($stats['total_categories']) ?></h2>
                        </div>
                        <div class="bg-success bg-opacity-10 p-3 rounded-circle">
                            <i class="fas fa-folder text-success fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sedang Dipinjam -->
        <div class="col-md-3 mb-3">
            <div class="card border-0 rounded-3 shadow-sm stats-card h-100">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-info mb-1 fw-bold">SEDANG DIPINJAM</h6>
                            <h2 class="mb-0 fw-bold"><?= number_format($stats['total_borrowed']) ?></h2>
                        </div>
                        <div class="bg-info bg-opacity-10 p-3 rounded-circle">
                            <i class="fas fa-bookmark text-info fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dikembalikan -->
        <div class="col-md-3 mb-3">
            <div class="card border-0 rounded-3 shadow-sm stats-card h-100">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-warning mb-1 fw-bold">DIKEMBALIKAN</h6>
                            <h2 class="mb-0 fw-bold"><?= number_format($stats['total_returned']) ?></h2>
                        </div>
                        <div class="bg-warning bg-opacity-10 p-3 rounded-circle">
                            <i class="fas fa-book-reader text-warning fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Books Table -->
    <div class="card shadow-sm border-0">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Daftar Buku</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="dataTableBooks">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center" width="5%">No</th>
                            <th width="50">Kode</th>
                            <th width="80">Cover</th>
                            <th width="30%">Judul</th>
                            <th>Penulis</th>
                            <th>Penerbit</th>
                            <th>Tahun</th>
                            <th>Kategori</th>
                            <th class="text-center">Stok</th>
                            <th class="text-center" width="10%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($books)): ?>
                            <tr>
                                <td colspan="10" class="text-center p-4">
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle me-2"></i>Tidak ada data buku ditemukan
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($books as $index => $book): ?>
                                <tr>
                                    <td class="text-center"><?= $start + $index + 1 ?></td>
                                    <td><code><?= htmlspecialchars($book['kode_buku']) ?></code></td>
                                    <td>
                                        <?php
                                        $coverImg = !empty($book['cover_img']) ? $book['cover_img'] : 'book' . rand(11, 15) . '.jpg';
                                        $imagePath = BASE_URL . '/assets/img/books/' . $coverImg;
                                        ?>
                                        <img src="<?= $imagePath ?>"
                                            class="img-thumbnail"
                                            width="60"
                                            alt="<?= htmlspecialchars($book['judul']) ?>"
                                            style="object-fit: cover;"
                                            onerror="this.src='<?= BASE_URL ?>/assets/img/books/book<?= rand(11, 15) ?>.jpg';">
                                    </td>
                                    <td><?= htmlspecialchars($book['judul']) ?></td>
                                    <td><?= htmlspecialchars($book['penulis']) ?></td>
                                    <td><?= htmlspecialchars($book['penerbit']) ?></td>
                                    <td><?= $book['tahun_terbit'] ?></td>
                                    <td>
                                        <span class="badge bg-secondary">
                                            <?= htmlspecialchars($book['kategori_nama'] ?? 'Tidak ada kategori') ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge <?= $book['stok'] > 0 ? 'bg-success' : 'bg-danger' ?>">
                                            <?= $book['stok'] ?> buku
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="detail.php?id=<?= $book['id'] ?>" class="btn btn-info text-white" data-bs-toggle="tooltip" title="Detail">
                                                <i class="fas fa-eye"></i>
                                            </a>

                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($total_halaman > 1): ?>
                <nav aria-label="Page navigation">
                    <ul class="pagination justify-content-center mt-3">
                        <li class="page-item <?= ($halaman <= 1) ? 'disabled' : '' ?>">
                            <a class="page-link" href="?halaman=<?= $halaman - 1 ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?><?= !empty($kategori) ? '&kategori_id=' . $kategori : '' ?><?= !empty($tahun) ? '&tahun=' . $tahun : '' ?>" aria-label="Previous">
                                <span aria-hidden="true">&laquo;</span>
                            </a>
                        </li>
                        <?php for ($i = 1; $i <= $total_halaman; $i++): ?>
                            <li class="page-item <?= ($halaman == $i) ? 'active' : '' ?>">
                                <a class="page-link" href="?halaman=<?= $i ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?><?= !empty($kategori) ? '&kategori_id=' . $kategori : '' ?><?= !empty($tahun) ? '&tahun=' . $tahun : '' ?>">
                                    <?= $i ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?= ($halaman >= $total_halaman) ? 'disabled' : '' ?>">
                            <a class="page-link" href="?halaman=<?= $halaman + 1 ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?><?= !empty($kategori) ? '&kategori_id=' . $kategori : '' ?><?= !empty($tahun) ? '&tahun=' . $tahun : '' ?>" aria-label="Next">
                                <span aria-hidden="true">&raquo;</span>
                            </a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Aktifkan tooltip bootstrap
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });

        // Tandai menu aktif di sidebar
        const sidebarMenuItems = document.querySelectorAll('.sidebar-menu li a');
        sidebarMenuItems.forEach(item => {
            if (item.textContent.trim().includes('Manajemen Buku')) {
                item.classList.add('active');
            }
        });
    });
</script>

<?php include '../../../includes/footer.php'; ?>
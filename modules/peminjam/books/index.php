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

// Cek apakah ada kategori buku
$checkCategoryQuery = "SELECT COUNT(*) as category_count FROM kategori_buku";
$stmtCheckCategory = $conn->prepare($checkCategoryQuery);
$stmtCheckCategory->execute();
$categoryCount = $stmtCheckCategory->fetch(PDO::FETCH_ASSOC)['category_count'];

if ($categoryCount == 0) {
    // Tambahkan kategori Magic dan Teknologi
    $categories = [
        ['nama_kategori' => 'Magic', 'deskripsi' => 'Buku tentang ilmu sihir dan magic'],
        ['nama_kategori' => 'Teknologi', 'deskripsi' => 'Buku tentang teknologi dan inovasi']
    ];
    
    foreach ($categories as $category) {
        $insertCategoryQuery = "INSERT INTO kategori_buku (nama_kategori, deskripsi, created_at) 
                               VALUES (?, ?, NOW())";
        $stmtCategory = $conn->prepare($insertCategoryQuery);
        $stmtCategory->execute([$category['nama_kategori'], $category['deskripsi']]);
    }
}

// Pengaturan pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 12; // Jumlah buku per halaman
$offset = ($page - 1) * $limit;

// Query dasar
$baseQuery = "FROM buku b 
              LEFT JOIN kategori_buku_relasi kr ON b.id = kr.buku_id
              LEFT JOIN kategori_buku k ON kr.kategori_id = k.id";

// Tambahkan kondisi pencarian jika ada
$searchCondition = "";
$searchParams = [];
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = "%" . $_GET['search'] . "%";
    $searchCondition = " WHERE (b.judul LIKE ? OR b.penulis LIKE ? OR b.penerbit LIKE ?)";
    $searchParams = [$search, $search, $search];
}

// Filter kategori
$categoryFilter = "";
if (isset($_GET['kategori']) && !empty($_GET['kategori'])) {
    if (empty($searchCondition)) {
        $categoryFilter = " WHERE k.nama_kategori = ?";
    } else {
        $categoryFilter = " AND k.nama_kategori = ?";
    }
    $searchParams[] = $_GET['kategori'];
}

// Query untuk total buku
$totalQuery = "SELECT COUNT(DISTINCT b.id) as total " . $baseQuery . $searchCondition . $categoryFilter;
$stmt = $conn->prepare($totalQuery);
$stmt->execute($searchParams);
$total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
$totalPages = ceil($total / $limit);

// Query untuk mendapatkan buku
$query = "SELECT DISTINCT b.*, k.nama_kategori " . $baseQuery . $searchCondition . $categoryFilter . 
         " ORDER BY b.created_at DESC LIMIT " . $limit . " OFFSET " . $offset;
$stmt = $conn->prepare($query);
$stmt->execute($searchParams);
$books = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Ambil semua kategori untuk filter
$stmtCategories = $conn->prepare("SELECT * FROM kategori_buku ORDER BY nama_kategori");
$stmtCategories->execute();
$allCategories = $stmtCategories->fetchAll(PDO::FETCH_ASSOC);

include '../../../includes/header.php';
?>

<div class="container-fluid p-4">
    <!-- Header Section -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Katalog Buku</h1>
        <div class="d-flex gap-2">
            <form class="d-flex" role="search" method="GET">
                <?php if (isset($_GET['kategori'])): ?>
                <input type="hidden" name="kategori" value="<?= htmlspecialchars($_GET['kategori']) ?>">
                <?php endif; ?>
                <input class="form-control me-2" type="search" name="search" 
                       placeholder="Cari judul, penulis, atau penerbit..." 
                       value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                <button class="btn btn-outline-primary" type="submit">
                    <i class="fas fa-search"></i>
                </button>
            </form>
        </div>
    </div>

    <!-- Filter Kategori -->
    <div class="mb-4">
        <div class="btn-group" role="group">
            <a href="index.php" class="btn btn-outline-primary <?= !isset($_GET['kategori']) ? 'active' : '' ?>">
                Semua Kategori
            </a>
            <?php foreach ($allCategories as $category): ?>
            <a href="index.php?kategori=<?= urlencode($category['nama_kategori']) ?><?= isset($_GET['search']) ? '&search='.htmlspecialchars($_GET['search']) : '' ?>" 
               class="btn btn-outline-primary <?= (isset($_GET['kategori']) && $_GET['kategori'] == $category['nama_kategori']) ? 'active' : '' ?>">
                <?= htmlspecialchars($category['nama_kategori']) ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>

    <?php if (empty($books)): ?>
    <div class="alert alert-info">
        <i class="fas fa-info-circle me-2"></i>Tidak ada buku yang ditemukan.
    </div>
    <?php else: ?>
    <!-- Book Grid -->
    <div class="row">
        <?php foreach ($books as $book): ?>
            <div class="col-md-3 mb-4">
                <div class="card h-100 border-0 shadow-sm">
                <div class="position-relative">
                <?php
                $coverImg = !empty($book['cover_img']) ? $book['cover_img'] : 'book' . rand(11, 15) . '.jpg';
                $imagePath = BASE_URL . '/assets/img/books/' . $coverImg;
                ?>
                <img src="<?= $imagePath ?>"
                    class="card-img-top"
                    alt="<?= htmlspecialchars($book['judul']) ?>"
                    style="height: 350px; object-fit: contain; background-color: #f8f9fa;"
                    onerror="this.src='<?= BASE_URL ?>/assets/img/books/book<?= rand(11, 15) ?>.jpg';">
                <?php if ($book['stok'] <= 3 && $book['stok'] > 0): ?>
                <div class="position-absolute top-0 end-0 m-2">
                    <span class="badge bg-warning">Stok Terbatas</span>
                </div>
                <?php elseif ($book['stok'] == 0): ?>
                <div class="position-absolute top-0 end-0 m-2">
                    <span class="badge bg-danger">Stok Habis</span>
                </div>
                <?php endif; ?>
            </div>
                    <div class="card-body">
                        <h5 class="card-title text-truncate" title="<?= htmlspecialchars($book['judul']) ?>">
                            <?= htmlspecialchars($book['judul']) ?>
                        </h5>
                        <p class="card-text mb-1">
                            <small class="text-muted">
                                <i class="fas fa-tag me-1"></i>
                                <?= $book['nama_kategori'] ? htmlspecialchars($book['nama_kategori']) : 'Tanpa Kategori' ?>
                            </small>
                        </p>
                        <p class="card-text mb-1">
                            <small class="text-muted">
                                <i class="fas fa-user me-1"></i>
                                <?= htmlspecialchars($book['penulis']) ?>
                            </small>
                        </p>
                        <p class="card-text mb-2">
                            <small class="text-muted">
                                <i class="fas fa-book me-1"></i>
                                Stok: <?= htmlspecialchars($book['stok']) ?>
                            </small>
                        </p>
                        <p class="card-text" style="height: 48px; overflow: hidden;">
                            <?= substr(htmlspecialchars($book['deskripsi'] ?? ''), 0, 100) ?>...
                        </p>
                        <div class="d-grid">
                            <a href="detail.php?id=<?= $book['id'] ?>" 
                               class="btn btn-primary btn-sm">
                                <i class="fas fa-info-circle me-1"></i>Detail
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <nav aria-label="Page navigation" class="mt-4">
        <ul class="pagination justify-content-center">
            <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                <a class="page-link" href="?page=<?= $page-1 ?><?= isset($_GET['search']) ? '&search='.htmlspecialchars($_GET['search']) : '' ?><?= isset($_GET['kategori']) ? '&kategori='.htmlspecialchars($_GET['kategori']) : '' ?>">
                    Previous
                </a>
            </li>
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                    <a class="page-link" href="?page=<?= $i ?><?= isset($_GET['search']) ? '&search='.htmlspecialchars($_GET['search']) : '' ?><?= isset($_GET['kategori']) ? '&kategori='.htmlspecialchars($_GET['kategori']) : '' ?>">
                        <?= $i ?>
                    </a>
                </li>
            <?php endfor; ?>
            <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                <a class="page-link" href="?page=<?= $page+1 ?><?= isset($_GET['search']) ? '&search='.htmlspecialchars($_GET['search']) : '' ?><?= isset($_GET['kategori']) ? '&kategori='.htmlspecialchars($_GET['kategori']) : '' ?>">
                    Next
                </a>
            </li>
        </ul>
    </nav>
    <?php endif; ?>
    <?php endif; ?>
</div>

<?php include '../../../includes/footer.php'; ?>A
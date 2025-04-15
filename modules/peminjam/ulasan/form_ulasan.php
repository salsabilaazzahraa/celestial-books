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

// Cek parameter
if (!isset($_GET['peminjaman_id']) || !isset($_GET['buku_id'])) {
    setMessage('error', 'Parameter tidak valid!');
    redirect('/modules/peminjam/ulasan/index.php');
}

$peminjaman_id = $_GET['peminjaman_id'];
$buku_id = $_GET['buku_id'];

// Validasi peminjaman
$query = "
    SELECT p.*, b.judul, b.penulis, b.cover_img 
    FROM peminjaman p
    JOIN buku b ON p.buku_id = b.id
    WHERE p.id = ? AND p.user_id = ? AND p.buku_id = ? AND p.status_peminjaman = 'dikembalikan'
";

$stmt = $conn->prepare($query);
$stmt->execute([$peminjaman_id, $_SESSION['user_id'], $buku_id]);
$peminjaman = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$peminjaman) {
    setMessage('error', 'Data peminjaman tidak ditemukan atau tidak valid!');
    redirect('/modules/peminjam/ulasan/index.php');
}

// Cek apakah sudah pernah memberikan ulasan
$query = "SELECT id FROM ulasan_buku WHERE user_id = ? AND buku_id = ?";
$stmt = $conn->prepare($query);
$stmt->execute([$_SESSION['user_id'], $buku_id]);

if ($stmt->rowCount() > 0) {
    setMessage('warning', 'Anda sudah memberikan ulasan untuk buku ini!');
    redirect('/modules/peminjam/ulasan/index.php');
}

include '../../../includes/header.php';
?>

<div class="container-fluid p-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0 text-primary">Berikan Ulasan dan Rating</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-3 text-center">
                            <?php
                            $coverImg = !empty($peminjaman['cover_img']) ? $peminjaman['cover_img'] : 'book' . rand(11, 15) . '.jpg';
                            $imagePath = BASE_URL . '/assets/img/books/' . $coverImg;
                            ?>
                            <img src="<?= $imagePath ?>" 
                                 class="img-fluid rounded shadow-sm" 
                                 alt="<?= htmlspecialchars($peminjaman['judul']) ?>"
                                 style="max-height: 200px;"
                                 onerror="this.src='<?= BASE_URL ?>/assets/img/books/book<?= rand(11, 15) ?>.jpg';">
                        </div>
                        <div class="col-md-9">
                            <h4><?= htmlspecialchars($peminjaman['judul']) ?></h4>
                            <p class="text-muted"><?= htmlspecialchars($peminjaman['penulis']) ?></p>
                            <p><strong>Tanggal Peminjaman:</strong> <?= date('d/m/Y', strtotime($peminjaman['tanggal_peminjaman'])) ?></p>
                            <p><strong>Tanggal Pengembalian:</strong> <?= date('d/m/Y', strtotime($peminjaman['tanggal_pengembalian'])) ?></p>
                        </div>
                    </div>

                    <form action="process_ulasan.php" method="post">
                        <input type="hidden" name="peminjaman_id" value="<?= $peminjaman_id ?>">
                        <input type="hidden" name="buku_id" value="<?= $buku_id ?>">
                        
                        <div class="mb-3">
                            <label for="rating" class="form-label">Rating</label>
                            <div class="rating-stars mb-2">
                                <div class="d-flex">
                                    <?php for ($i = 5; $i >= 1; $i--): ?>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input rating-input" type="radio" name="rating" id="rating<?= $i ?>" value="<?= $i ?>" <?= $i === 5 ? 'checked' : '' ?>>
                                            <label class="form-check-label rating-label" for="rating<?= $i ?>">
                                                <i class="fas fa-star"></i>
                                            </label>
                                        </div>
                                    <?php endfor; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="ulasan" class="form-label">Ulasan</label>
                            <textarea class="form-control" id="ulasan" name="ulasan" rows="5" required placeholder="Bagikan pendapat Anda tentang buku ini..."></textarea>
                            <div class="form-text">Minimal 10 karakter dan maksimal 500 karakter.</div>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <a href="index.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-1"></i>Kembali
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane me-1"></i>Kirim Ulasan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .rating-stars .rating-input {
        display: none;
    }
    
    .rating-stars .rating-label {
        font-size: 24px;
        color: #ccc;
        cursor: pointer;
        padding: 5px;
    }
    
    .rating-stars .rating-input:checked ~ .rating-label {
        color: #ffc107;
    }
    
    .rating-stars .rating-label:hover,
    .rating-stars .rating-label:hover ~ .rating-label {
        color: #ffc107;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const ratingInputs = document.querySelectorAll('.rating-input');
    const ratingLabels = document.querySelectorAll('.rating-label');
    
    // Initialize dengan nilai default
    const defaultRating = 5;
    document.getElementById('rating' + defaultRating).checked = true;
    updateStarColors(defaultRating);
    
    // Event listener untuk setiap bintang
    ratingInputs.forEach(input => {
        input.addEventListener('change', function() {
            const value = this.value;
            updateStarColors(value);
        });
    });
    
    function updateStarColors(value) {
        ratingLabels.forEach(label => {
            const labelFor = label.getAttribute('for');
            const starValue = parseInt(labelFor.replace('rating', ''));
            
            if (starValue <= value) {
                label.style.color = '#ffc107'; // Bintang berwarna kuning
            } else {
                label.style.color = '#ccc'; // Bintang berwarna abu-abu
            }
        });
    }
});
</script>

<?php include '../../../includes/footer.php'; ?>
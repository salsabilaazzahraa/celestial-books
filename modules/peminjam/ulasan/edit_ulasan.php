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
if (!isset($_GET['id'])) {
    setMessage('error', 'Parameter tidak valid!');
    redirect('/modules/peminjam/ulasan/index.php');
}

$ulasan_id = $_GET['id'];

// Ambil data ulasan
$query = "
    SELECT u.*, b.judul, b.penulis, b.cover_img 
    FROM ulasan_buku u
    JOIN buku b ON u.buku_id = b.id
    WHERE u.id = ? AND u.user_id = ? AND u.status = 'pending'
";

$stmt = $conn->prepare($query);
$stmt->execute([$ulasan_id, $_SESSION['user_id']]);
$ulasan = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ulasan) {
    setMessage('error', 'Data ulasan tidak ditemukan atau tidak dapat diedit!');
    redirect('/modules/peminjam/ulasan/index.php');
}

include '../../../includes/header.php';
?>

<div class="container-fluid p-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0 text-primary">Edit Ulasan dan Rating</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-3 text-center">
                            <?php
                            $coverImg = !empty($ulasan['cover_img']) ? $ulasan['cover_img'] : 'book' . rand(11, 15) . '.jpg';
                            $imagePath = BASE_URL . '/assets/img/books/' . $coverImg;
                            ?>
                            <img src="<?= $imagePath ?>" 
                                 class="img-fluid rounded shadow-sm" 
                                 alt="<?= htmlspecialchars($ulasan['judul']) ?>"
                                 style="max-height: 200px;"
                                 onerror="this.src='<?= BASE_URL ?>/assets/img/books/book<?= rand(11, 15) ?>.jpg';">
                        </div>
                        <div class="col-md-9">
                            <h4><?= htmlspecialchars($ulasan['judul']) ?></h4>
                            <p class="text-muted"><?= htmlspecialchars($ulasan['penulis']) ?></p>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                Anda hanya dapat mengedit ulasan yang masih berstatus menunggu persetujuan.
                            </div>
                        </div>
                    </div>

                    <form action="update_ulasan.php" method="post">
                        <input type="hidden" name="ulasan_id" value="<?= $ulasan_id ?>">
                        
                        <div class="mb-3">
                            <label for="rating" class="form-label">Rating</label>
                            <div class="rating-stars mb-2">
                                <div class="d-flex">
                                    <?php for ($i = 5; $i >= 1; $i--): ?>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input rating-input" type="radio" name="rating" id="rating<?= $i ?>" value="<?= $i ?>" <?= $i == $ulasan['rating'] ? 'checked' : '' ?>>
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
                            <textarea class="form-control" id="ulasan" name="ulasan" rows="5" required><?= htmlspecialchars($ulasan['ulasan']) ?></textarea>
                            <div class="form-text">Minimal 10 karakter dan maksimal 500 karakter.</div>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <a href="index.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-1"></i>Kembali
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>Simpan Perubahan
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
    
    // Initialize dengan nilai saat ini
    const currentRating = <?= $ulasan['rating'] ?>;
    document.getElementById('rating' + currentRating).checked = true;
    updateStarColors(currentRating);
    
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
<?php
session_start();
require_once '../../../config/functions.php';
require_once '../../../config/database.php';

// Cek akses admin
if (!isAdmin()) {
    setMessage('danger', 'Akses ditolak! Anda bukan admin.');
    redirect('/auth/login.php');
}

$db = new Database();
$conn = $db->getConnection();

// Ambil ID buku dari URL
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Query untuk mendapatkan detail buku
$stmt = $conn->prepare("SELECT * FROM buku WHERE id = :id");
$stmt->bindParam(':id', $id);
$stmt->execute();
$buku = $stmt->fetch(PDO::FETCH_ASSOC);

// Cek apakah buku ditemukan
if (!$buku) {
    setMessage('danger', 'Buku tidak ditemukan!');
    redirect('../books/index.php');
}

// Ambil kategori yang sudah dipilih sebelumnya
$stmt = $conn->prepare("SELECT kategori_id FROM kategori_buku_relasi WHERE buku_id = :buku_id");
$stmt->bindParam(':buku_id', $id);
$stmt->execute();
$selected_kategoris = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Ambil daftar kategori
$kategoris = $conn->query("SELECT * FROM kategori_buku ORDER BY nama_kategori ASC")->fetchAll(PDO::FETCH_ASSOC);

$errors = [];

// Proses upload cover
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validasi dan sanitasi input
    $kode_buku = isset($_POST['kode_buku']) ? sanitize($_POST['kode_buku']) : '';
    $judul = isset($_POST['judul']) ? sanitize($_POST['judul']) : '';
    $penulis = isset($_POST['penulis']) ? sanitize($_POST['penulis']) : '';
    $penerbit = isset($_POST['penerbit']) ? sanitize($_POST['penerbit']) : '';
    $tahun_terbit = isset($_POST['tahun_terbit']) ? sanitize($_POST['tahun_terbit']) : '';
    $deskripsi = isset($_POST['deskripsi']) ? sanitize($_POST['deskripsi']) : '';
    $isbn = isset($_POST['isbn']) ? sanitize($_POST['isbn']) : '';
    $stok = isset($_POST['stok']) ? sanitize($_POST['stok']) : '';
    $kategori_ids = isset($_POST['kategori_ids']) ? $_POST['kategori_ids'] : [];

    // Validasi input
    if (empty($kode_buku)) $errors[] = "Kode buku wajib diisi";
    if (empty($judul)) $errors[] = "Judul buku wajib diisi";
    if (empty($penulis)) $errors[] = "Penulis buku wajib diisi";
    if (empty($penerbit)) $errors[] = "Penerbit buku wajib diisi";
    if (empty($tahun_terbit) || !is_numeric($tahun_terbit)) $errors[] = "Tahun terbit harus berupa angka";
    if (empty($stok) || !is_numeric($stok)) $errors[] = "Stok harus berupa angka";
    if (empty($kategori_ids)) $errors[] = "Pilih minimal satu kategori";

    // Proses upload cover
    $cover_img = $buku['cover_img'];
    if (isset($_FILES['cover_img']) && $_FILES['cover_img']['size'] > 0) {
        $file = $_FILES['cover_img'];
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
        $max_size = 2 * 1024 * 1024; // 2MB

        if (!in_array($file['type'], $allowed_types)) {
            $errors[] = "Format file cover harus JPG atau PNG";
        }

        if ($file['size'] > $max_size) {
            $errors[] = "Ukuran file cover maksimal 2MB";
        }

        if (empty($errors)) {
            $upload_dir = '../../../assets/img/books/';
            
            // Buat direktori jika belum ada
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $filename = time() . '_' . str_replace(' ', '_', $file['name']);
            $destination = $upload_dir . $filename;

            if (move_uploaded_file($file['tmp_name'], $destination)) {
                // Hapus file cover lama
                if (!empty($buku['cover_img']) && file_exists($upload_dir . $buku['cover_img'])) {
                    unlink($upload_dir . $buku['cover_img']);
                }
                $cover_img = $filename;
            } else {
                $errors[] = "Gagal mengupload file cover";
            }
        }
    }

    // Simpan data jika tidak ada error
    if (empty($errors)) {
        try {
            // Mulai transaksi
            $conn->beginTransaction();

            // Update data buku
            $query = "UPDATE buku SET 
                        kode_buku = :kode_buku, 
                        judul = :judul, 
                        penulis = :penulis, 
                        penerbit = :penerbit, 
                        tahun_terbit = :tahun_terbit, 
                        deskripsi = :deskripsi, 
                        isbn = :isbn, 
                        stok = :stok, 
                        cover_img = :cover_img,
                        updated_at = NOW()
                      WHERE id = :id";
            
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':kode_buku', $kode_buku);
            $stmt->bindParam(':judul', $judul);
            $stmt->bindParam(':penulis', $penulis);
            $stmt->bindParam(':penerbit', $penerbit);
            $stmt->bindParam(':tahun_terbit', $tahun_terbit);
            $stmt->bindParam(':deskripsi', $deskripsi);
            $stmt->bindParam(':isbn', $isbn);
            $stmt->bindParam(':stok', $stok);
            $stmt->bindParam(':cover_img', $cover_img);
            $stmt->bindParam(':id', $id);
            $stmt->execute();

            // Hapus kategori lama
            $stmt = $conn->prepare("DELETE FROM kategori_buku_relasi WHERE buku_id = :buku_id");
            $stmt->bindParam(':buku_id', $id);
            $stmt->execute();

            // Tambahkan kategori baru
            $stmt = $conn->prepare("INSERT INTO kategori_buku_relasi (buku_id, kategori_id) VALUES (:buku_id, :kategori_id)");
            foreach ($kategori_ids as $kategori_id) {
                $stmt->bindParam(':buku_id', $id);
                $stmt->bindParam(':kategori_id', $kategori_id);
                $stmt->execute();
            }

            // Commit transaksi
            $conn->commit();

            // Set pesan sukses di session
            $_SESSION['message'] = 'Buku berhasil diperbarui!';
            $_SESSION['message_type'] = 'success';
            
            // Tetap di halaman yang sama
            header("Location: edit.php?id=" . $id);
            exit();

        } catch (PDOException $e) {
            // Rollback transaksi
            $conn->rollBack();
            $errors[] = 'Terjadi kesalahan: ' . $e->getMessage();
        }
    }
}

include '../../../includes/header.php';
?>

<!-- Tampilkan pesan sukses atau error -->
<?php if (isset($_SESSION['message'])): ?>
    <div class="container-fluid p-3">
        <div class="alert alert-<?= $_SESSION['message_type'] ?> alert-dismissible fade show" role="alert">
            <?= $_SESSION['message'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    </div>
    <?php 
    // Hapus pesan dari session
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
    ?>
<?php endif; ?>

<div class="container-fluid p-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">Edit Buku</h1>
        <a href="../books/index.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i> Kembali
        </a>
    </div>

    <!-- Tampilkan error jika ada -->
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= $error ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form action="" method="POST" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-md-8">
                        <!-- Kode Buku -->
                        <div class="mb-3">
                            <label for="kode_buku" class="form-label">Kode Buku <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="kode_buku" name="kode_buku" 
                                   value="<?= htmlspecialchars($buku['kode_buku']) ?>" required>
                        </div>

                        <!-- Judul -->
                        <div class="mb-3">
                            <label for="judul" class="form-label">Judul Buku <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="judul" name="judul" 
                                   value="<?= htmlspecialchars($buku['judul']) ?>" required>
                        </div>

                        <div class="row">
                            <!-- Penulis -->
                            <div class="col-md-6 mb-3">
                                <label for="penulis" class="form-label">Penulis <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="penulis" name="penulis" 
                                       value="<?= htmlspecialchars($buku['penulis']) ?>" required>
                            </div>

                            <!-- Penerbit -->
                            <div class="col-md-6 mb-3">
                                <label for="penerbit" class="form-label">Penerbit <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="penerbit" name="penerbit" 
                                       value="<?= htmlspecialchars($buku['penerbit']) ?>" required>
                            </div>
                        </div>

                        <div class="row">
                            <!-- Tahun Terbit -->
                            <div class="col-md-4 mb-3">
                                <label for="tahun_terbit" class="form-label">Tahun Terbit <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="tahun_terbit" name="tahun_terbit" 
                                       value="<?= htmlspecialchars($buku['tahun_terbit']) ?>" required>
                            </div>

                            <!-- ISBN -->
                            <div class="col-md-4 mb-3">
                                <label for="isbn" class="form-label">ISBN</label>
                                <input type="text" class="form-control" id="isbn" name="isbn" 
                                       value="<?= htmlspecialchars($buku['isbn'] ?? '') ?>">
                            </div>

                            <!-- Stok -->
                            <div class="col-md-4 mb-3">
                                <label for="stok" class="form-label">Stok <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="stok" name="stok" 
                                       value="<?= htmlspecialchars($buku['stok']) ?>" required>
                            </div>
                        </div>

                        <!-- Kategori -->
                        <div class="mb-3">
                            <label class="form-label">Kategori <span class="text-danger">*</span></label>
                            <div class="border p-3">
                                <div class="row">
                                    <?php foreach ($kategoris as $kategori): ?>
                                        <div class="col-md-4 mb-2">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" 
                                                       name="kategori_ids[]" 
                                                       id="kategori<?= $kategori['id'] ?>" 
                                                       value="<?= $kategori['id'] ?>"
                                                       <?= in_array($kategori['id'], $selected_kategoris) ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="kategori<?= $kategori['id'] ?>">
                                                    <?= htmlspecialchars($kategori['nama_kategori']) ?>
                                                </label>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Deskripsi -->
                        <div class="mb-3">
                            <label for="deskripsi" class="form-label">Deskripsi</label>
                            <textarea class="form-control" id="deskripsi" name="deskripsi" rows="4"><?= htmlspecialchars($buku['deskripsi'] ?? '') ?></textarea>
                        </div>
                    </div>

                    <!-- Kolom Cover -->
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="cover_img" class="form-label">Cover Buku</label>
                            <div class="text-center mb-3">
                                <img id="cover_preview" 
                                     src="<?= $buku['cover_img'] ? BASE_URL . '/assets/img/books/' . $buku['cover_img'] : BASE_URL . '/assets/img/books/book' . rand(11, 15) . '.jpg' ?>" 
                                     class="img-fluid rounded border" 
                                     style="max-height: 300px; object-fit: contain;" 
                                     alt="Preview Cover">
                            </div>
                            <input type="file" class="form-control" id="cover_img" name="cover_img" accept="image/jpeg,image/png">
                            <small class="form-text text-muted">Ukuran maks: 2MB. Format: JPG/PNG</small>
                        </div>
                    </div>
                </div>

                <!-- Tombol Aksi -->
                <div class="d-flex justify-content-end mt-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Perbarui Buku
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Menampilkan preview gambar saat file dipilih
    document.getElementById('cover_img').addEventListener('change', function() {
        if (this.files && this.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('cover_preview').setAttribute('src', e.target.result);
            }
            reader.readAsDataURL(this.files[0]);
        }
    });
    
    // Auto-close alerts setelah 5 detik
    setTimeout(function() {
        var alerts = document.querySelectorAll('.alert');
        alerts.forEach(function(alert) {
            var bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);
});
</script>
<?php include '../../../includes/footer.php'; ?>
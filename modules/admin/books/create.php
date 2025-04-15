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

// Mendapatkan daftar kategori
$kategoris = $conn->query("SELECT * FROM kategori_buku ORDER BY nama_kategori ASC")->fetchAll(PDO::FETCH_ASSOC);

// Tambahkan variabel untuk menyimpan pesan sukses
$success_message = '';
$errors = [];

// Proses input data
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validasi data
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
    if (empty($kode_buku)) {
        $errors[] = "Kode buku wajib diisi";
    } else {
        // Periksa apakah kode buku sudah ada
        $stmt = $conn->prepare("SELECT COUNT(*) FROM buku WHERE kode_buku = :kode_buku");
        $stmt->bindParam(':kode_buku', $kode_buku);
        $stmt->execute();
        if ($stmt->fetchColumn() > 0) {
            $errors[] = "Kode buku sudah digunakan, silakan gunakan kode buku lain";
        }
    }

    if (empty($judul)) {
        $errors[] = "Judul buku wajib diisi";
    }

    if (empty($penulis)) {
        $errors[] = "Penulis buku wajib diisi";
    }

    if (empty($penerbit)) {
        $errors[] = "Penerbit buku wajib diisi";
    }

    if (empty($tahun_terbit) || !is_numeric($tahun_terbit)) {
        $errors[] = "Tahun terbit harus berupa angka";
    }

    if (empty($stok) || !is_numeric($stok)) {
        $errors[] = "Stok harus berupa angka";
    }

    if (empty($kategori_ids)) {
        $errors[] = "Pilih minimal satu kategori";
    }

    // Proses upload file cover jika ada
    $cover_img = null;
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
                $cover_img = $filename;
            } else {
                $errors[] = "Gagal mengupload file cover. Periksa hak akses direktori.";
            }
        }
    }

    // Simpan data ke database jika tidak ada error
    if (empty($errors)) {
        try {
            // Mulai transaksi
            $conn->beginTransaction();

            // Insert data buku
            $query = "INSERT INTO buku (kode_buku, judul, penulis, penerbit, tahun_terbit, deskripsi, isbn, stok, cover_img, created_at, updated_at) 
                    VALUES (:kode_buku, :judul, :penulis, :penerbit, :tahun_terbit, :deskripsi, :isbn, :stok, :cover_img, NOW(), NOW())";
            
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
            $stmt->execute();

            $buku_id = $conn->lastInsertId();

            // Insert kategori relasi
            foreach ($kategori_ids as $kategori_id) {
                $query = "INSERT INTO kategori_buku_relasi (buku_id, kategori_id) VALUES (:buku_id, :kategori_id)";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':buku_id', $buku_id);
                $stmt->bindParam(':kategori_id', $kategori_id);
                $stmt->execute();
            }

            // Commit transaksi
            $conn->commit();

            // Tampilkan alert sukses dan redirect setelah 2 detik
            $success_message = "Buku berhasil ditambahkan!";
            setMessage('success', 'Buku berhasil ditambahkan!');
        } catch (PDOException $e) {
            // Rollback transaksi jika terjadi error
            $conn->rollBack();
            $errors[] = 'Terjadi kesalahan: ' . $e->getMessage();
        }
    }
}

include '../../../includes/header.php';
?>

<div class="container-fluid p-3">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">Tambah Buku Baru</h1>
        <a href="index.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i> Kembali
        </a>
    </div>

    <!-- Success Modal -->
    <?php if (!empty($success_message)): ?>
    <div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="successModalLabel">Berhasil!</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p><?= $success_message ?></p>
                </div>
                <div class="modal-footer">
                    <a href="index.php" class="btn btn-primary">Kembali ke Daftar Buku</a>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tambah Buku Lain</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Tampilkan modal sukses secara otomatis
        document.addEventListener('DOMContentLoaded', function() {
            var successModal = new bootstrap.Modal(document.getElementById('successModal'));
            successModal.show();
        });
    </script>
    <?php endif; ?>

    <!-- Alert Messages -->
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?= $error ?></li>
                <?php endforeach; ?>
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php showMessage(); ?>

    <!-- Form -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form action="" method="POST" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-md-8">
                        <!-- Field Kode Buku -->
                        <div class="mb-3">
                            <label for="kode_buku" class="form-label">Kode Buku <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="kode_buku" name="kode_buku" required value="<?= isset($_POST['kode_buku']) ? htmlspecialchars($_POST['kode_buku']) : '' ?>" maxlength="20">
                            <div class="form-text">Maksimal 20 karakter dan harus unik</div>
                        </div>
                        
                        <!-- Informasi Buku -->
                        <div class="mb-3">
                            <label for="judul" class="form-label">Judul Buku <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="judul" name="judul" required value="<?= isset($_POST['judul']) ? htmlspecialchars($_POST['judul']) : '' ?>">
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="penulis" class="form-label">Penulis <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="penulis" name="penulis" required value="<?= isset($_POST['penulis']) ? htmlspecialchars($_POST['penulis']) : '' ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="penerbit" class="form-label">Penerbit <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="penerbit" name="penerbit" required value="<?= isset($_POST['penerbit']) ? htmlspecialchars($_POST['penerbit']) : '' ?>">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="tahun_terbit" class="form-label">Tahun Terbit <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="tahun_terbit" name="tahun_terbit" min="1900" max="<?= date('Y') ?>" required value="<?= isset($_POST['tahun_terbit']) ? htmlspecialchars($_POST['tahun_terbit']) : date('Y') ?>">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="isbn" class="form-label">ISBN</label>
                                    <input type="text" class="form-control" id="isbn" name="isbn" value="<?= isset($_POST['isbn']) ? htmlspecialchars($_POST['isbn']) : '' ?>">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="stok" class="form-label">Stok <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="stok" name="stok" min="0" required value="<?= isset($_POST['stok']) ? htmlspecialchars($_POST['stok']) : '1' ?>">
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="kategori" class="form-label">Kategori <span class="text-danger">*</span></label>
                            <div class="border rounded p-3">
                                <div class="row">
                                    <?php foreach ($kategoris as $kategori): ?>
                                        <div class="col-md-4 mb-2">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="kategori_ids[]" id="kategori<?= $kategori['id'] ?>" value="<?= $kategori['id'] ?>" <?= isset($_POST['kategori_ids']) && in_array($kategori['id'], $_POST['kategori_ids']) ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="kategori<?= $kategori['id'] ?>">
                                                    <?= htmlspecialchars($kategori['nama_kategori']) ?>
                                                </label>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="deskripsi" class="form-label">Deskripsi</label>
                            <textarea class="form-control" id="deskripsi" name="deskripsi" rows="4"><?= isset($_POST['deskripsi']) ? htmlspecialchars($_POST['deskripsi']) : '' ?></textarea>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="cover_img" class="form-label">Cover Buku</label>
                            <div class="text-center mb-3">
                                <img id="cover_preview" src="<?= BASE_URL ?>/assets/img/books/book<?= rand(11, 15) ?>.jpg" class="img-fluid rounded border" style="max-height: 300px; object-fit: contain; background-color: #f8f9fa;" alt="Preview Cover">
                            </div>
                            <input type="file" class="form-control" id="cover_img" name="cover_img" accept="image/jpeg, image/png">
                            <div class="form-text">Format: JPG/PNG, Maks: 2MB</div>
                        </div>
                    </div>
                </div>

                <hr>

                <div class="d-flex justify-content-end">
                    <button type="reset" class="btn btn-light me-2">Reset</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i> Simpan Buku
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Mengaktifkan menu sidebar yang aktif
        const sidebarMenuItems = document.querySelectorAll('.sidebar-menu li a');
        sidebarMenuItems.forEach(item => {
            if (item.textContent.trim().includes('Manajemen Buku')) {
                item.classList.add('active');
            }
        });

        // Preview gambar cover
        const coverInput = document.getElementById('cover_img');
        const coverPreview = document.getElementById('cover_preview');

        coverInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    coverPreview.src = e.target.result;
                };
                reader.readAsDataURL(this.files[0]);
            }
        });
        
        // Otomatis generate kode buku dari judul
        const judulInput = document.getElementById('judul');
        const kodeBukuInput = document.getElementById('kode_buku');
        
        // Hanya isi otomatis jika kode buku kosong
        if (judulInput && kodeBukuInput && kodeBukuInput.value === '') {
            judulInput.addEventListener('blur', function() {
                if (this.value && !kodeBukuInput.value) {
                    // Ambil huruf pertama dari setiap kata, maksimal 5 kata
                    const words = this.value.split(' ').slice(0, 5);
                    let initials = words.map(word => word.charAt(0).toUpperCase()).join('');
                    
                    // Tambahkan nomor random untuk memastikan keunikan
                    const randomNum = Math.floor(Math.random() * 10000).toString().padStart(4, '0');
                    kodeBukuInput.value = 'BK-' + initials + '-' + randomNum;
                }
            });
        }
    });
</script>

<?php include '../../../includes/footer.php'; ?>
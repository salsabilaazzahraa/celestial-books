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

// Ambil data user
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Proses update profile
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $nama_lengkap = sanitize($_POST['nama_lengkap']);
        $email = sanitize($_POST['email']);
        $no_telepon = sanitize($_POST['no_telepon']);
        $alamat = sanitize($_POST['alamat']);
        $password = $_POST['password'] ?? '';
        
        // Validasi input
        if (empty($nama_lengkap) || empty($email) || empty($no_telepon) || empty($alamat)) {
            throw new Exception('Semua field harus diisi!');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Format email tidak valid!');
        }

        // Update data
        $sql = "UPDATE users SET 
                nama_lengkap = ?, 
                email = ?, 
                no_telepon = ?, 
                alamat = ?";
        $params = [$nama_lengkap, $email, $no_telepon, $alamat];

        // Jika password diisi, update password
        if (!empty($password)) {
            $sql .= ", password = ?";
            $params[] = password_hash($password, PASSWORD_DEFAULT);
        }

        $sql .= " WHERE id = ?";
        $params[] = $_SESSION['user_id'];

        $stmt = $conn->prepare($sql);
        $stmt->execute($params);

        setMessage('success', 'Profil berhasil diupdate!');
        redirect('/modules/peminjam/profile/index.php');

    } catch (Exception $e) {
        setMessage('error', $e->getMessage());
    }
}

include '../../../includes/header.php';
?>

<div class="container-fluid p-4">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 text-primary">Edit Profil</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="nama_lengkap" class="form-label">Nama Lengkap</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="nama_lengkap" 
                                   name="nama_lengkap" 
                                   value="<?= htmlspecialchars($user['nama_lengkap']) ?>"
                                   required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" 
                                   class="form-control" 
                                   id="email" 
                                   name="email" 
                                   value="<?= htmlspecialchars($user['email']) ?>"
                                   required>
                        </div>
                        <div class="mb-3">
                            <label for="no_telepon" class="form-label">No. Telepon</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="no_telepon" 
                                   name="no_telepon" 
                                   value="<?= htmlspecialchars($user['no_telepon']) ?>"
                                   required>
                        </div>
                        <div class="mb-3">
                            <label for="alamat" class="form-label">Alamat</label>
                            <textarea class="form-control" 
                                      id="alamat" 
                                      name="alamat" 
                                      rows="3" 
                                      required><?= htmlspecialchars($user['alamat']) ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password Baru (Kosongkan jika tidak ingin mengubah)</label>
                            <input type="password" 
                                   class="form-control" 
                                   id="password" 
                                   name="password">
                        </div>
                        <div class="d-flex justify-content-between">
                            <a href="index.php" class="btn btn-secondary">
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

<?php include '../../../includes/footer.php'; ?>

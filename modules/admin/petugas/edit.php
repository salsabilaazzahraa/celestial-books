<?php
require_once '../../../config/functions.php';

// Cek apakah user adalah admin
if (!isAdmin()) {
    setMessage('danger', 'Anda tidak memiliki akses ke halaman ini!');
    redirect('/');
}

$database = new Database();
$conn = $database->getConnection();

// Ambil data petugas berdasarkan ID
if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    $query = "SELECT * FROM users WHERE id = ? AND role = 'petugas'";
    $stmt = $conn->prepare($query);
    $stmt->execute([$id]);
    $petugas = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$petugas) {
        setMessage('danger', 'Petugas tidak ditemukan!');
        redirect('/modules/admin/petugas/index.php');
    }
}

// Proses update data
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)$_POST['id'];
    $username = sanitize($_POST['username']);
    $email = sanitize($_POST['email']);
    $nama_lengkap = sanitize($_POST['nama_lengkap']);
    $no_telepon = sanitize($_POST['no_telepon']);
    $alamat = sanitize($_POST['alamat']);
    
    // Cek username dan email yang sudah ada (kecuali data petugas ini sendiri)
    $check_query = "SELECT COUNT(*) FROM users WHERE (username = ? OR email = ?) AND id != ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->execute([$username, $email, $id]);
    
    if ($check_stmt->fetchColumn() > 0) {
        setMessage('danger', 'Username atau email sudah digunakan!');
    } else {
        // Update password jika diisi
        if (!empty($_POST['password'])) {
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $query = "UPDATE users SET username = ?, email = ?, password = ?, nama_lengkap = ?, 
                      no_telepon = ?, alamat = ?, updated_at = NOW() WHERE id = ? AND role = 'petugas'";
            $stmt = $conn->prepare($query);
            $result = $stmt->execute([$username, $email, $password, $nama_lengkap, $no_telepon, $alamat, $id]);
        } else {
            $query = "UPDATE users SET username = ?, email = ?, nama_lengkap = ?, 
                      no_telepon = ?, alamat = ?, updated_at = NOW() WHERE id = ? AND role = 'petugas'";
            $stmt = $conn->prepare($query);
            $result = $stmt->execute([$username, $email, $nama_lengkap, $no_telepon, $alamat, $id]);
        }
        
        if ($result) {
            setMessage('success', 'Data petugas berhasil diupdate!');
            redirect('/modules/admin/petugas/index.php');
        } else {
            setMessage('danger', 'Gagal mengupdate data petugas!');
        }
    }
}
?>

<?php include('../../../includes/header.php'); ?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Edit Data Petugas</h3>
                </div>
                <div class="card-body">
                    <?php showMessage(); ?>
                    <form action="" method="POST">
                        <input type="hidden" name="id" value="<?php echo $petugas['id']; ?>">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="username" class="form-label">Username</label>
                                    <input type="text" class="form-control" id="username" name="username" 
                                           value="<?php echo htmlspecialchars($petugas['username']); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?php echo htmlspecialchars($petugas['email']); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="password" class="form-label">Password</label>
                                    <input type="password" class="form-control" id="password" name="password" 
                                           placeholder="Kosongkan jika tidak ingin mengubah password">
                                    <small class="text-muted">Kosongkan jika tidak ingin mengubah password</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="nama_lengkap" class="form-label">Nama Lengkap</label>
                                    <input type="text" class="form-control" id="nama_lengkap" name="nama_lengkap" 
                                           value="<?php echo htmlspecialchars($petugas['nama_lengkap']); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="no_telepon" class="form-label">No. Telepon</label>
                                    <input type="text" class="form-control" id="no_telepon" name="no_telepon" 
                                           value="<?php echo htmlspecialchars($petugas['no_telepon']); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="alamat" class="form-label">Alamat</label>
                                    <textarea class="form-control" id="alamat" name="alamat" rows="3" required><?php echo htmlspecialchars($petugas['alamat']); ?></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <button type="submit" class="btn btn-primary">Update</button>
                            <a href="index.php" class="btn btn-secondary">Kembali</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include('../../../includes/footer.php'); ?>

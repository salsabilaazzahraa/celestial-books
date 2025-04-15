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

include '../../../includes/header.php';
?>

<div class="container-fluid p-4">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <div class="row align-items-center">
                        <div class="col">
                            <h5 class="mb-0 text-primary">Profil Saya</h5>
                        </div>
                        <div class="col text-end">
                            <a href="edit.php" class="btn btn-primary btn-sm">
                                <i class="fas fa-edit me-1"></i>Edit Profil
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="text-center mb-4">
                        <img src="https://ui-avatars.com/api/?name=<?= urlencode($user['username']) ?>&background=random"
                             class="rounded-circle mb-3"
                             width="100"
                             height="100"
                             alt="Profile Picture">
                        <h4><?= htmlspecialchars($user['nama_lengkap']) ?></h4>
                        <p class="text-muted mb-0">Member sejak <?= date('d F Y', strtotime($user['created_at'])) ?></p>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">Username</label>
                            <p class="mb-0"><?= htmlspecialchars($user['username']) ?></p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">Email</label>
                            <p class="mb-0"><?= htmlspecialchars($user['email']) ?></p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">No. Telepon</label>
                            <p class="mb-0"><?= htmlspecialchars($user['no_telepon']) ?></p>
                        </div>
                        <div class="col-12">
                            <label class="form-label text-muted">Alamat</label>
                            <p class="mb-0"><?= nl2br(htmlspecialchars($user['alamat'])) ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../../includes/footer.php'; ?>

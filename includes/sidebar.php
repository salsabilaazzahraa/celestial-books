<?php
if (!isset($_SESSION)) {
    session_start();
}

if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../config/constants.php';
}
?>
<div class="sidebar">
    <div class="sidebar-header">
        <img src="<?php echo IMG_PATH; ?>/logo.png" alt="Logo" class="sidebar-logo">
        <h3><?php echo APP_NAME; ?></h3>
    </div>

    <ul class="sidebar-menu">
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
            <li>
                <a href="<?php echo BASE_URL; ?>/modules/admin/dashboard.php" class="<?php echo (strpos($_SERVER['PHP_SELF'], '/modules/admin/dashboard.php') !== false) ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <!-- Modifikasi bagian Manajemen Pengguna -->
            <li class="menu-item">
                <a href="<?php echo BASE_URL; ?>/modules/admin/users/index.php" class="<?php echo (strpos($_SERVER['PHP_SELF'], '/modules/admin/users') !== false) ? 'active' : ''; ?>">
                    <i class="fas fa-users"></i>
                    <span>Manajemen Pengguna</span>
                </a>
                <div class="submenu">
                    <a href="<?php echo BASE_URL; ?>/modules/admin/users/index.php" class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], '/modules/admin/users/index.php') !== false) ? 'active' : ''; ?>">
                        <i class="fas fa-user-friends"></i>
                        <span>Semua Pengguna</span>
                    </a>
                    <a href="<?php echo BASE_URL; ?>/modules/admin/petugas/index.php" class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], '/modules/admin/petugas/index.php') !== false) ? 'active' : ''; ?>">
                        <i class="fas fa-user-tie"></i>
                        <span>Kelola Petugas</span>
                    </a>
                </div>
            </li>
            <li>
                <a href="<?php echo BASE_URL; ?>/modules/admin/books/index.php" class="<?php echo (strpos($_SERVER['PHP_SELF'], '/modules/admin/books') !== false) ? 'active' : ''; ?>">
                    <i class="fas fa-book"></i>
                    <span>Manajemen Buku</span>
                </a>
            </li>
            <li>
        <a href="<?php echo BASE_URL; ?>/modules/admin/ulasan/index.php" class="<?php echo (strpos($_SERVER['PHP_SELF'], '/modules/admin/ulasan') !== false) ? 'active' : ''; ?>">
            <i class="fas fa-star"></i>
            <span>Manajemen Ulasan</span>
        </a>
    </li>
            <li>
                <a href="<?php echo BASE_URL; ?>/modules/admin/reports/peminjaman.php" class="<?php echo (strpos($_SERVER['PHP_SELF'], '/modules/admin/reports') !== false) ? 'active' : ''; ?>">
                    <i class="fas fa-chart-bar"></i>
                    <span>Laporan</span>
                </a>
            </li>
            <?php elseif (isset($_SESSION['role']) && $_SESSION['role'] === 'petugas'): ?>
            <li>
                <a href="<?php echo BASE_URL; ?>/modules/petugas/dashboard.php" class="<?php echo (strpos($_SERVER['PHP_SELF'], '/modules/petugas/dashboard.php') !== false) ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="<?php echo BASE_URL; ?>/modules/petugas/peminjaman/index.php" class="<?php echo (strpos($_SERVER['PHP_SELF'], '/modules/petugas/peminjaman') !== false) ? 'active' : ''; ?>">
                    <i class="fas fa-hand-holding"></i>
                    <span>Peminjaman</span>
                </a>
            </li>
            <li>
                <a href="<?php echo BASE_URL; ?>/modules/petugas/pengembalian/index.php" class="<?php echo (strpos($_SERVER['PHP_SELF'], '/modules/petugas/pengembalian') !== false) ? 'active' : ''; ?>">
                    <i class="fas fa-undo-alt"></i>
                    <span>Pengembalian</span>
                </a>
            </li>
            <li>
                <a href="<?php echo BASE_URL; ?>/modules/petugas/books/index.php" class="<?php echo (strpos($_SERVER['PHP_SELF'], '/modules/petugas/books') !== false) ? 'active' : ''; ?>">
                    <i class="fas fa-book-open"></i>
                    <span>Manajemen Buku</span>
                </a>
            </li>
            <li>
        <a href="<?php echo BASE_URL; ?>/modules/petugas/ulasan/index.php" class="<?php echo (strpos($_SERVER['PHP_SELF'], '/modules/petugas/ulasan') !== false) ? 'active' : ''; ?>">
            <i class="fas fa-star"></i>
            <span>Manajemen Ulasan</span>
        </a>
    </li>
            <li>
                <a href="<?php echo BASE_URL; ?>/reports/templates/petugas_report.php" class="<?php echo (strpos($_SERVER['PHP_SELF'], '/reports/templates/petugas_report.php') !== false) ? 'active' : ''; ?>">
                    <i class="fas fa-file-alt"></i>
                    <span>Laporan Peminjaman</span>
                </a>
            </li>
            <li>
                <a href="<?php echo BASE_URL; ?>/reports/templates/kartu_peminjaman.php" class="<?php echo (strpos($_SERVER['PHP_SELF'], '/reports/templates/kartu_peminjaman.php') !== false) ? 'active' : ''; ?>">
                    <i class="fas fa-id-card"></i>
                    <span>Cetak Kartu</span>
                </a>
            </li>
        <?php else: ?>
            <!-- Kode untuk menu peminjam -->
            <li>
                <a href="<?php echo BASE_URL; ?>/modules/peminjam/dashboard.php" class="<?php echo (strpos($_SERVER['PHP_SELF'], '/modules/peminjam/dashboard.php') !== false) ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="<?php echo BASE_URL; ?>/modules/peminjam/books/index.php" class="<?php echo (strpos($_SERVER['PHP_SELF'], '/modules/peminjam/books') !== false) ? 'active' : ''; ?>">
                    <i class="fas fa-book"></i>
                    <span>Katalog Buku</span>
                </a>
            </li>
            <li>
                <a href="<?php echo BASE_URL; ?>/modules/peminjam/profile/index.php" class="<?php echo (strpos($_SERVER['PHP_SELF'], '/modules/peminjam/profile') !== false) ? 'active' : ''; ?>">
                    <i class="fas fa-user"></i>
                    <span>Profil</span>
                </a>
            </li>
            <li>
                <a href="<?php echo BASE_URL; ?>/modules/peminjam/peminjaman/index.php" class="<?php echo (strpos($_SERVER['PHP_SELF'], '/modules/peminjam/peminjaman') !== false) ? 'active' : ''; ?>">
                    <i class="fas fa-book-reader"></i>
                    <span>Peminjaman Saya</span>
                </a>
            </li>
            <li>
        <a href="<?php echo BASE_URL; ?>/modules/peminjam/ulasan/index.php" class="<?php echo (strpos($_SERVER['PHP_SELF'], '/modules/peminjam/ulasan') !== false) ? 'active' : ''; ?>">
            <i class="fas fa-star"></i>
            <span>Ulasan Buku</span>
        </a>
    </li>
        <?php endif; ?>
        <li>
            <a href="<?php echo BASE_URL; ?>/auth/logout.php">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </li>
    </ul>
</div>

<style>
    .menu-item {
        position: relative;
    }

    .submenu {
        display: none;
        padding-left: 20px;
        background-color: rgba(255, 255, 255, 0.05);
    }

    .menu-item:hover .submenu {
        display: block;
    }

    .submenu .nav-link {
        padding: 10px 15px;
        display: block;
        color: #fff;
        text-decoration: none;
        font-size: 14px;
        transition: all 0.3s ease;
    }

    .submenu .nav-link:hover {
        background-color: rgba(255, 255, 255, 0.1);
        padding-left: 20px;
    }

    .submenu .nav-link i {
        margin-right: 10px;
        font-size: 12px;
    }

    .submenu .nav-link.active {
        background-color: rgba(255, 255, 255, 0.2);
    }
</style>
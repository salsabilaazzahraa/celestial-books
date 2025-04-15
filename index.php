<?php
require_once 'config/constants.php';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Perpustakaan Digital</title>
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>/bootstrap.min.css">
    <link rel="stylesheet" href="<?php echo CSS_PATH; ?>/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .hero-section {
            min-height: 100vh;
            background: linear-gradient(135deg, #3498db 0%, #85c1e9 100%);
            position: relative;
            overflow: hidden;
        }

        .wave {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 100px;
            background: url('<?php echo IMG_PATH; ?>/wave.png');
            background-size: 1000px 100px;
        }

        .wave.wave1 {
            animation: animate 30s linear infinite;
            z-index: 1000;
            opacity: 1;
            animation-delay: 0s;
            bottom: 0;
        }

        .wave.wave2 {
            animation: animate2 15s linear infinite;
            z-index: 999;
            opacity: 0.5;
            animation-delay: -5s;
            bottom: 10px;
        }

        .wave.wave3 {
            animation: animate 30s linear infinite;
            z-index: 998;
            opacity: 0.2;
            animation-delay: -2s;
            bottom: 15px;
        }

        @keyframes animate {
            0% {
                background-position-x: 0;
            }

            100% {
                background-position-x: 1000px;
            }
        }

        @keyframes animate2 {
            0% {
                background-position-x: 0;
            }

            100% {
                background-position-x: -1000px;
            }
        }

        .floating-books {
            position: absolute;
            width: 100%;
            height: 100%;
            pointer-events: none;
        }

        .book-animation {
            position: absolute;
            animation: floatBook 6s infinite;
            opacity: 0.8;
        }

        .book-1 {
            top: 20%;
            left: 10%;
            animation-delay: 0s;
        }

        .book-2 {
            top: 40%;
            left: 80%;
            animation-delay: 2s;
        }

        .book-3 {
            top: 70%;
            left: 30%;
            animation-delay: 4s;
        }

        .book-4 {
            top: 30%;
            left: 60%;
            animation-delay: 1s;
        }

        @keyframes floatBook {
            0% {
                transform: translate(0, 0) rotate(0deg);
            }

            25% {
                transform: translate(10px, -15px) rotate(5deg);
            }

            50% {
                transform: translate(0, -30px) rotate(0deg);
            }

            75% {
                transform: translate(-10px, -15px) rotate(-5deg);
            }

            100% {
                transform: translate(0, 0) rotate(0deg);
            }
        }

        .feature-section {
            padding: 80px 0;
            background: #ffffff;
            position: relative;
            overflow: hidden;
        }

        .feature-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, #f1f9fe 25%, transparent 25%),
                linear-gradient(-45deg, #f1f9fe 25%, transparent 25%),
                linear-gradient(45deg, transparent 75%, #f1f9fe 75%),
                linear-gradient(-45deg, transparent 75%, #f1f9fe 75%);
            background-size: 20px 20px;
            opacity: 0.3;
            z-index: 1;
        }

        .feature-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            margin: 15px 0;
            transition: all 0.3s ease;
            position: relative;
            z-index: 2;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
        }

        .feature-icon {
            font-size: 2.5rem;
            color: #3498db;
            margin-bottom: 20px;
        }

        .stats-section {
            background: #f8f9fa;
            padding: 60px 0;
            position: relative;
            overflow: hidden;
        }

        .stats-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #f1f9fe 25%, transparent 25%);
            background-size: 40px 40px;
            opacity: 0.4;
        }

        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            position: relative;
            z-index: 2;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15);
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: #3498db;
            margin-bottom: 10px;
        }

        .stat-label {
            color: #666;
            font-size: 1.1rem;
        }

        .book-img {
            max-width: 120px;
            height: auto;
        }

        .hero-content {
            position: relative;
            z-index: 2;
        }
    </style>
</head>

<body>
    <main>
        <section class="hero-section">
            <div class="floating-books">
                <div class="book-animation book-2">
                    <img src="<?php echo IMG_PATH; ?>/book2.png" alt="Book" class="book-img">
                </div>
                <div class="book-animation book-3">
                    <img src="<?php echo IMG_PATH; ?>/book3.png" alt="Book" class="book-img">
                </div>
                <div class="book-animation book-4">
                    <img src="<?php echo IMG_PATH; ?>/book4.png" alt="Book" class="book-img">
                </div>
            </div>

            <div class="wave wave1"></div>
            <div class="wave wave2"></div>
            <div class="wave wave3"></div>

            <div class="container">
                <div class="row align-items-center min-vh-100">
                    <div class="col-lg-6">
                        <div class="hero-content text-white">
                            <img src="<?php echo IMG_PATH; ?>/logo.png" alt="Logo" class="mb-4" style="max-width: 200px;">
                            <h1 class="display-4 fw-bold mb-4">Selamat Datang di Celestial Books</h1>
                            <p class="lead mb-5">Jelajahi dunia pengetahuan melalui koleksi buku digital kami.
                                Baca, pinjam, dan tingkatkan wawasanmu bersama kami.</p>
                            <a href="<?php echo BASE_URL; ?>/auth/login.php" class="btn btn-light btn-lg px-5 py-3 rounded-pill">
                                Masuk Sekarang
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="feature-section">
            <div class="container">
                <h2 class="text-center mb-5">Fitur Unggulan Kami</h2>
                <div class="row">
                    <div class="col-md-4">
                        <div class="feature-card">
                            <div class="feature-icon">
                                <i class="fas fa-book-reader"></i>
                            </div>
                            <h3>Koleksi Digital</h3>
                            <p>Akses ribuan buku digital dari berbagai kategori kapan saja dan di mana saja.</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="feature-card">
                            <div class="feature-icon">
                                <i class="fas fa-mobile-alt"></i>
                            </div>
                            <h3>Kemudahan Akses</h3>
                            <p>Baca dan pinjam buku favorit Anda dengan mudah melalui perangkat digital.</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="feature-card">
                            <div class="feature-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <h3>Komunitas Pembaca</h3>
                            <p>Bergabung dengan komunitas pembaca dan bagikan pengalaman membaca Anda.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="stats-section">
            <div class="container">
                <div class="row">
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-number">5000+</div>
                            <div class="stat-label">Koleksi Buku</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-number">1000+</div>
                            <div class="stat-label">Pembaca Aktif</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-number">50+</div>
                            <div class="stat-label">Kategori</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-number">24/7</div>
                            <div class="stat-label">Layanan</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <script src="<?php echo JS_PATH; ?>/bootstrap.bundle.min.js"></script>
</body>

</html>
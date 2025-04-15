-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 15, 2025 at 12:38 PM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `celestial_books`
--

-- --------------------------------------------------------

--
-- Table structure for table `buku`
--

CREATE TABLE `buku` (
  `id` int(11) NOT NULL,
  `kode_buku` varchar(20) NOT NULL,
  `judul` varchar(255) NOT NULL,
  `penulis` varchar(255) NOT NULL,
  `penerbit` varchar(255) NOT NULL,
  `tahun_terbit` int(11) NOT NULL,
  `isbn` varchar(20) DEFAULT NULL,
  `stok` int(11) NOT NULL DEFAULT 1,
  `cover_img` varchar(255) DEFAULT 'default_cover.jpg',
  `deskripsi` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `rating` decimal(3,1) NOT NULL DEFAULT 0.0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `buku`
--

INSERT INTO `buku` (`id`, `kode_buku`, `judul`, `penulis`, `penerbit`, `tahun_terbit`, `isbn`, `stok`, `cover_img`, `deskripsi`, `created_at`, `updated_at`, `rating`) VALUES
(7, 'BKM001', 'Harry Potter and the Chamber of Secrets: The Illustrated Edition (Harry Potter, Book 2): Volume 2', 'By Rowling, J. K. Kay, Jim', 'HARRY POTTER', 2016, '9780545791328', 5, 'book11.jpg', 'The Dursleys were so mean and hideous that summer that all Harry Potter wanted was to get back to the Hogwarts School for Witchcraft and Wizardry. But just as hes packing his bags, Harry receives a warning from a strange, impish creature named Dobby who says that if Harry Potter returns to Hogwarts, disaster will strike.And strike it does. For in Harry&amp;#039;s second year at Hogwarts, fresh torments and horrors arise, including an outrageously stuck-up new professor, Gilderoy Lockhart, a spirit named Moaning Myrtle who haunts the girls&amp;#039; bathroom, and the unwanted attentions of Ron Weasley&amp;#039;s younger sister, Ginny.', '2025-02-25 02:48:00', '2025-04-15 02:32:41', 0.0),
(8, 'BKM002', 'Harry Potter and the Prisoner of Azkaban', 'By Rowling, J. K. Grandpré, Mary', 'HARRY POTTER', 1999, '9780439136358', 8, 'book12.jpg', 'For twelve long years, the dread fortress of Azkaban held an infamous prisoner named Sirius Black. Convicted of killing thirteen people with a single curse, he was said to be the heir apparent to the Dark Lord, Voldemort.Now he has escaped, leaving only two clues as to where he might be headed: Harry Potter&#039;s defeat of You-Know-Who was Black&#039;s downfall as well. And the Azkaban guards heard Black muttering in his sleep, &quot;He&#039;s at Hogwarts . . . he&#039;s at Hogwarts.&quot;Harry Potter isn&#039;t safe, not even within the walls of his magical school, surrounded by his friends. Because on top of it all, there may well be a traitor in their midst.', '2025-02-25 02:48:00', '2025-04-15 04:13:00', 4.0),
(9, 'BKT001', 'Pegangan Praktis Konstruksi Perangkat Lunak', 'Steve McConnell', 'Microsoft Press', 2004, '978-0735619678', 7, '1744437316_book13.jpg', 'CODE COMPLETE adalah buku yang ditulis oleh Steve McConnell. Buku klasik ini menawarkan beragam contoh kode baru untuk menggambarkan seni dan sains pengembangan perangkat lunak. Penulis juga menyintesiskan teknik-teknik paling efektif dan prinsip-prinsip yang harus diketahui ke dalam panduan yang jelas dan pragmatis. Buku ini membantu Anda merangsang pemikiran Anda dan membantu Anda membangun kode kualitas tertinggi.', '2025-02-25 02:48:01', '2025-04-15 04:04:38', 5.0),
(10, 'BKT002', 'The Pragmatic Programmer', 'David Thomas', 'Addison-Wesley Professional', 2019, '978-0135957059', 8, '1744439009_book14.jpg', 'The Pragmatic Programmer adalah buku yang ditulis oleh David Thomas (Penulis), Andrew Hun. Buku ini membantu klien untuk membuat perangkat lunak yang lebih baik dan menemukan kembali kegembiraan dalam membuat kode. Pelajaran dalam buku ini membantu generasi pengembang perangkat lunak untuk meneliti hakikat pengembangan perangkat lunak, terlepas dari bahasa, kerangka kerja, atau metodologi tertentu, dan filosofi Pragmatis.', '2025-02-25 02:48:01', '2025-04-14 15:55:07', 0.0),
(17, 'BKT003', 'Pengantar Teknologi informatika Dan Komunikasi Data', 'Bagas', 'Koro', 2012, '9786020361318', 11, '1744684792_OIP_(11).jpeg', 'Berisi materi terkait teknologi dan komunikasi yang kita pelajari dalam dunia digital', '2025-04-15 02:39:52', '2025-04-15 07:08:52', 0.0),
(18, 'BKM003', 'Harry Potter dan Piala Api', 'J.k. Rowling', 'HARRY POTTER', 2018, '9786020361385', 8, '1744692630_9786020342726_harry-potter-dan-piala-api-cover-baru.jpg', 'Harrya potter dan piala api disebuah negeri dongeng', '2025-04-15 04:50:30', '2025-04-15 07:07:56', 0.0),
(19, 'BM003', 'Kemarin', 'salsa', 'gunung putri', 2025, '139418720212345', 1, '1744700719_MTK.jpeg', 'kemarin hujan turun lebat sekali', '2025-04-15 07:05:19', '2025-04-15 07:05:19', 0.0);

-- --------------------------------------------------------

--
-- Table structure for table `kartu_anggota`
--

CREATE TABLE `kartu_anggota` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `nomor_kartu` varchar(20) DEFAULT NULL,
  `tanggal_bergabung` date NOT NULL,
  `tanggal_berakhir` date NOT NULL,
  `status` enum('aktif','nonaktif') DEFAULT 'aktif',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `kategori_buku`
--

CREATE TABLE `kategori_buku` (
  `id` int(11) NOT NULL,
  `nama_kategori` varchar(255) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `kategori_buku`
--

INSERT INTO `kategori_buku` (`id`, `nama_kategori`, `deskripsi`, `created_at`, `updated_at`) VALUES
(5, 'Magic', 'Buku tentang ilmu sihir dan magic', '2025-02-25 02:48:00', '2025-02-25 02:48:00'),
(6, 'Teknologi', 'Buku tentang teknologi dan inovasi', '2025-02-25 02:48:00', '2025-02-25 02:48:00');

-- --------------------------------------------------------

--
-- Table structure for table `kategori_buku_relasi`
--

CREATE TABLE `kategori_buku_relasi` (
  `id` int(11) NOT NULL,
  `buku_id` int(11) DEFAULT NULL,
  `kategori_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `kategori_buku_relasi`
--

INSERT INTO `kategori_buku_relasi` (`id`, `buku_id`, `kategori_id`) VALUES
(12, 8, 5),
(25, 10, 6),
(30, 7, 5),
(31, 17, 6),
(32, 9, 6),
(34, 18, 5),
(35, 19, 5);

-- --------------------------------------------------------

--
-- Table structure for table `peminjaman`
--

CREATE TABLE `peminjaman` (
  `id` int(11) NOT NULL,
  `kode_peminjaman` varchar(20) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `buku_id` int(11) DEFAULT NULL,
  `tanggal_peminjaman` date NOT NULL,
  `tanggal_pengembalian` date NOT NULL,
  `tanggal_dikembalikan` date DEFAULT NULL,
  `status_peminjaman` enum('dipinjam','dikembalikan','terlambat') DEFAULT 'dipinjam',
  `denda` decimal(10,2) DEFAULT 0.00,
  `catatan` text DEFAULT NULL,
  `petugas_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `peminjaman`
--

INSERT INTO `peminjaman` (`id`, `kode_peminjaman`, `user_id`, `buku_id`, `tanggal_peminjaman`, `tanggal_pengembalian`, `tanggal_dikembalikan`, `status_peminjaman`, `denda`, `catatan`, `petugas_id`, `created_at`, `updated_at`) VALUES
(28, 'PJM-20250414-6231', 15, 7, '2025-04-14', '2025-04-21', '2025-04-14', 'dikembalikan', 0.00, '', NULL, '2025-04-14 03:43:39', '2025-04-14 03:51:04'),
(29, 'PJM-20250414-5744', 16, 9, '2025-04-14', '2025-04-21', '2025-04-14', 'dikembalikan', 0.00, '', NULL, '2025-04-14 03:45:06', '2025-04-14 03:55:17'),
(30, 'PJM-20250414-7147', 16, 7, '2025-04-14', '2025-04-21', '2025-04-14', 'dikembalikan', 0.00, '', NULL, '2025-04-14 05:18:20', '2025-04-14 15:48:17'),
(31, 'PJM-20250414-7377', 14, 9, '2025-04-14', '2025-04-21', NULL, 'dipinjam', 0.00, '', NULL, '2025-04-14 05:18:56', '2025-04-14 05:18:56'),
(32, 'PJM-20250414-9054', 14, 10, '2025-04-14', '2025-04-21', '2025-04-14', 'dikembalikan', 0.00, '', NULL, '2025-04-14 05:21:03', '2025-04-14 15:55:07'),
(33, 'PJM-20250414-5925', 20, 8, '2025-04-14', '2025-04-21', '2025-04-14', 'dikembalikan', 0.00, '', NULL, '2025-04-14 15:12:26', '2025-04-14 15:43:21'),
(34, 'PJM-20250414-6683', 20, 10, '2025-04-14', '2025-04-21', NULL, 'dipinjam', 0.00, '', NULL, '2025-04-14 15:15:08', '2025-04-14 15:15:08'),
(35, 'PJM-20250415-1357', 20, 17, '2025-04-15', '2025-04-22', '2025-04-15', 'dikembalikan', 0.00, '', NULL, '2025-04-15 02:44:29', '2025-04-15 07:08:52'),
(36, 'PJM-20250415-9327', 25, 18, '2025-04-15', '2025-04-22', '2025-04-15', 'dikembalikan', 0.00, '', NULL, '2025-04-15 04:52:06', '2025-04-15 04:52:52'),
(37, 'PJM-20250415-2552', 20, 18, '2025-04-15', '2025-04-22', '2025-04-15', 'dikembalikan', 0.00, '', NULL, '2025-04-15 07:07:09', '2025-04-15 07:07:56');

-- --------------------------------------------------------

--
-- Table structure for table `riwayat_peminjaman`
--

CREATE TABLE `riwayat_peminjaman` (
  `id` int(11) NOT NULL,
  `peminjaman_id` int(11) DEFAULT NULL,
  `status_perubahan` varchar(50) DEFAULT NULL,
  `catatan` text DEFAULT NULL,
  `changed_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `riwayat_peminjaman`
--

INSERT INTO `riwayat_peminjaman` (`id`, `peminjaman_id`, `status_perubahan`, `catatan`, `changed_by`, `created_at`) VALUES
(27, 28, 'dikembalikan', 'Buku dikembalikan oleh petugas tepat waktu', NULL, '2025-04-14 03:51:04'),
(28, 29, 'dikembalikan', 'Buku dikembalikan oleh petugas tepat waktu', NULL, '2025-04-14 03:55:17'),
(29, 33, 'dikembalikan', 'Buku dikembalikan oleh petugas tepat waktu', 22, '2025-04-14 15:43:21'),
(30, 30, 'dikembalikan', 'Buku dikembalikan oleh petugas tepat waktu', 22, '2025-04-14 15:48:17'),
(31, 32, 'dikembalikan', 'Buku dikembalikan oleh petugas tepat waktu', 22, '2025-04-14 15:55:07'),
(32, 36, 'dikembalikan', 'Buku dikembalikan oleh petugas tepat waktu', NULL, '2025-04-15 04:52:52'),
(33, 37, 'dikembalikan', 'Buku dikembalikan oleh petugas tepat waktu', 29, '2025-04-15 07:07:56'),
(34, 35, 'dikembalikan', 'Buku dikembalikan oleh petugas tepat waktu', 29, '2025-04-15 07:08:52');

-- --------------------------------------------------------

--
-- Table structure for table `ulasan_buku`
--

CREATE TABLE `ulasan_buku` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `buku_id` int(11) DEFAULT NULL,
  `rating` int(11) NOT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `ulasan` text NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ulasan_buku`
--

INSERT INTO `ulasan_buku` (`id`, `user_id`, `buku_id`, `rating`, `ulasan`, `status`, `created_at`, `updated_at`) VALUES
(9, 20, 8, 4, 'OMG THAT SO GOOD NICE', 'approved', '2025-04-14 15:56:01', '2025-04-15 04:13:00'),
(10, 25, 18, 3, 'Cerita nya menarik', 'pending', '2025-04-15 04:53:45', '2025-04-15 04:53:45');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nama_lengkap` varchar(255) NOT NULL,
  `alamat` text DEFAULT NULL,
  `no_telepon` varchar(15) DEFAULT NULL,
  `role` enum('admin','petugas','peminjam') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `reset_token` varchar(64) DEFAULT NULL,
  `reset_expires` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `nama_lengkap`, `alamat`, `no_telepon`, `role`, `created_at`, `updated_at`, `reset_token`, `reset_expires`) VALUES
(14, 'pinky', 'pinky@gmail.com', '$2y$10$8lEtO6V.Hh6D5u6UQCMTkOK1aW84kSYQes0czmgK97GQgq/ud1/v6', 'pinkycelestial', 'banten', '085974561278', 'peminjam', '2025-04-12 16:02:45', '2025-04-12 16:11:24', NULL, NULL),
(15, 'fayya', 'fayyazaluthfia@gmail.com', '$2y$10$46NveuQ9SryOkcEjUO06G.Chf3NksUzKyni29pIJy2nFyk8lRrgaC', 'fayyaza', 'Gunung Putri fff', '08989100939', 'peminjam', '2025-04-14 03:19:22', '2025-04-14 03:24:53', NULL, NULL),
(16, 'fayyaza09', 'adminfay@gmail.com', '$2y$10$ncvEXF.GK/p867SOB92CEO5.t/hBKeXc0beMlJzmcV0KsbsO5xzhC', 'fayyaza09', NULL, NULL, 'peminjam', '2025-04-14 03:31:08', '2025-04-14 03:31:08', NULL, NULL),
(20, 'Bunga', 'bunga@gmail.com', '$2y$10$KWCeu/gMbFz1nvWgvRroFeGaeZVP7UGKCCHcu0pPsX4OYXRLxBRvu', 'Bunga Citra', 'Ngawi', '084569321574', 'peminjam', '2025-04-14 15:10:49', '2025-04-14 15:42:15', NULL, NULL),
(22, 'PetugasBunga', 'PetugasBunga@gmail.com', '$2y$10$Xv09UiMPiBvqWBWl7keHiesTQIh8Ycq9CXAhthY0mIL0s0yCYybOG', 'Petugas Bunga', 'Ciawi', '085476932158', 'petugas', '2025-04-14 15:17:56', '2025-04-15 02:43:07', NULL, NULL),
(25, 'HerPeminjam', 'HerPeminjam@gmail.com', '$2y$10$WrvjYorYMYCO60IdLvxJiun4G/lv1RhsEjPcScRi0yt1kSEAEEAYu', 'HerPeminjam', 'Bogor', '08457434860', 'peminjam', '2025-04-15 04:51:12', '2025-04-15 04:51:50', NULL, NULL),
(26, 'admin', 'admin@gmail.com', '$2y$10$pbsAMcqBHwJPO8IvFk9byuzrkk9DkHB3jxCKNy0hj2z7mKM8WVUfa', 'admin', NULL, NULL, 'admin', '2025-04-15 04:58:49', '2025-04-15 04:58:49', NULL, NULL),
(27, 'gurls21', 'gurls@gmail.com', '$2y$10$RKw43ADNsLHXgl2W9IqteuHBuHkVQnvVNsTaQi9A0TFMRlR7283tu', 'gurls', 'Ciamis', '085476932158', 'peminjam', '2025-04-15 04:59:32', '2025-04-15 07:01:08', NULL, NULL),
(29, 'petugas01', 'petugas12@gmail.com', '$2y$10$7RDeh3fg8yt1CRR5h9OxiuIljCsRhEOC2yf0BslCwvmMpztgYqKUy', 'petugas12', 'Pardek', '0856479324', 'petugas', '2025-04-15 07:02:31', '2025-04-15 07:02:44', NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `buku`
--
ALTER TABLE `buku`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kode_buku` (`kode_buku`),
  ADD KEY `idx_kode_buku` (`kode_buku`),
  ADD KEY `idx_judul` (`judul`);

--
-- Indexes for table `kartu_anggota`
--
ALTER TABLE `kartu_anggota`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD UNIQUE KEY `nomor_kartu` (`nomor_kartu`),
  ADD KEY `idx_nomor_kartu` (`nomor_kartu`);

--
-- Indexes for table `kategori_buku`
--
ALTER TABLE `kategori_buku`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `kategori_buku_relasi`
--
ALTER TABLE `kategori_buku_relasi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `buku_id` (`buku_id`),
  ADD KEY `kategori_id` (`kategori_id`);

--
-- Indexes for table `peminjaman`
--
ALTER TABLE `peminjaman`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kode_peminjaman` (`kode_peminjaman`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `buku_id` (`buku_id`),
  ADD KEY `petugas_id` (`petugas_id`),
  ADD KEY `idx_kode_peminjaman` (`kode_peminjaman`),
  ADD KEY `idx_status` (`status_peminjaman`);

--
-- Indexes for table `riwayat_peminjaman`
--
ALTER TABLE `riwayat_peminjaman`
  ADD PRIMARY KEY (`id`),
  ADD KEY `peminjaman_id` (`peminjaman_id`),
  ADD KEY `changed_by` (`changed_by`);

--
-- Indexes for table `ulasan_buku`
--
ALTER TABLE `ulasan_buku`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `buku_id` (`buku_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_role` (`role`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `buku`
--
ALTER TABLE `buku`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `kartu_anggota`
--
ALTER TABLE `kartu_anggota`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `kategori_buku`
--
ALTER TABLE `kategori_buku`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `kategori_buku_relasi`
--
ALTER TABLE `kategori_buku_relasi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `peminjaman`
--
ALTER TABLE `peminjaman`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `riwayat_peminjaman`
--
ALTER TABLE `riwayat_peminjaman`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `ulasan_buku`
--
ALTER TABLE `ulasan_buku`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `kartu_anggota`
--
ALTER TABLE `kartu_anggota`
  ADD CONSTRAINT `kartu_anggota_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `kategori_buku_relasi`
--
ALTER TABLE `kategori_buku_relasi`
  ADD CONSTRAINT `kategori_buku_relasi_ibfk_1` FOREIGN KEY (`buku_id`) REFERENCES `buku` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `kategori_buku_relasi_ibfk_2` FOREIGN KEY (`kategori_id`) REFERENCES `kategori_buku` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `peminjaman`
--
ALTER TABLE `peminjaman`
  ADD CONSTRAINT `peminjaman_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `peminjaman_ibfk_2` FOREIGN KEY (`buku_id`) REFERENCES `buku` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `peminjaman_ibfk_3` FOREIGN KEY (`petugas_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `riwayat_peminjaman`
--
ALTER TABLE `riwayat_peminjaman`
  ADD CONSTRAINT `riwayat_peminjaman_ibfk_1` FOREIGN KEY (`peminjaman_id`) REFERENCES `peminjaman` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `riwayat_peminjaman_ibfk_2` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `ulasan_buku`
--
ALTER TABLE `ulasan_buku`
  ADD CONSTRAINT `ulasan_buku_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ulasan_buku_ibfk_2` FOREIGN KEY (`buku_id`) REFERENCES `buku` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

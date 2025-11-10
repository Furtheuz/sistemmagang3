-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 23 Jul 2025 pada 05.56
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `sistem_magang`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `arsip`
--

CREATE TABLE `arsip` (
  `id` int(11) NOT NULL,
  `peserta_id` int(11) DEFAULT NULL,
  `keterangan` text DEFAULT NULL,
  `tanggal_arsip` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `arsip`
--

INSERT INTO `arsip` (`id`, `peserta_id`, `keterangan`, `tanggal_arsip`) VALUES
(1, 7, 'Pindah', '2025-07-02'),
(2, 1, 'Selesai', '2025-07-17'),
(3, 2, 'Selesai', '2025-07-17'),
(4, 3, 'Selesai', '2025-07-17'),
(5, 4, 'Selesai', '2025-07-17'),
(6, 5, 'Selesai', '2025-07-17'),
(7, 6, 'Selesai', '2025-07-17'),
(8, 9, 'Selesai', '2025-07-17'),
(9, 10, 'Selesai', '2025-07-17'),
(10, 11, 'Selesai', '2025-07-17'),
(11, 12, 'Selesai', '2025-07-17'),
(12, 17, 'Selesai', '2025-07-17');

-- --------------------------------------------------------

--
-- Struktur dari tabel `institusi`
--

CREATE TABLE `institusi` (
  `id` int(11) NOT NULL,
  `nama` varchar(100) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `telepon` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `institusi`
--

INSERT INTO `institusi` (`id`, `nama`, `alamat`, `telepon`, `email`) VALUES
(1, 'SMK Negeri 1', 'Jl. Pendidikan No. 1', '22', 'smknegeri1@example.com'),
(2, 'SMK Negeri 2', 'Jl. Teknologi No. 2', '23', 'smknegeri2@example.com'),
(3, 'SMK Negeri 3', 'Jl. Industri No. 3', '24', 'smknegeri3@example.com'),
(4, 'Universitas A', 'Jl. Kampus A No. 1', '25', 'universitasa@example.com'),
(5, 'Universitas B', 'Jl. Kampus B No. 2', '26', 'universitasb@example.com'),
(7, 'SMKN 7 Jember', 'Jl. Usuma N0. 76', '087654567890987', NULL),
(8, 'SMKN 1 Kragilan', 'Kh syuhada', '900008977', NULL);

-- --------------------------------------------------------

--
-- Struktur dari tabel `jadwal`
--

CREATE TABLE `jadwal` (
  `id` int(11) NOT NULL,
  `peserta_id` int(11) NOT NULL,
  `pembimbing_id` int(11) NOT NULL,
  `tanggal` date NOT NULL,
  `tugas` text NOT NULL,
  `minggu` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `jadwal`
--

INSERT INTO `jadwal` (`id`, `peserta_id`, `pembimbing_id`, `tanggal`, `tugas`, `minggu`, `created_at`, `updated_at`) VALUES
(3, 7, 12, '2025-07-24', 'Mencuci biji', 1, '2025-07-16 02:31:18', '2025-07-16 02:31:18'),
(4, 4, 12, '2025-07-28', 'Belajar php dasar', 2, '2025-07-16 02:38:18', '2025-07-16 02:38:18'),
(5, 17, 12, '2025-07-18', 'Membersihkan berkas di gudang', 1, '2025-07-18 00:16:29', '2025-07-18 00:16:29');

-- --------------------------------------------------------

--
-- Struktur dari tabel `laporan`
--

CREATE TABLE `laporan` (
  `id` int(11) NOT NULL,
  `peserta_id` int(11) DEFAULT NULL,
  `tanggal` date DEFAULT NULL,
  `kegiatan` text DEFAULT NULL,
  `validasi` enum('belum','valid') DEFAULT 'belum'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `laporan`
--

INSERT INTO `laporan` (`id`, `peserta_id`, `tanggal`, `kegiatan`, `validasi`) VALUES
(1, 1, '2025-06-10', 'Mengikuti briefing awal dan pengenalan lingkungan kerja', 'belum'),
(2, 2, '2025-06-10', 'Membuat desain presentasi profil instansi', 'belum'),
(3, 3, '2025-06-11', 'Instalasi software ke komputer divisi IT', 'valid'),
(4, 4, '2025-06-11', 'Input data laporan keuangan', 'valid'),
(5, 5, '2025-06-12', 'Monitoring jaringan kantor', 'valid'),
(6, 6, '2025-06-12', 'Backup data server ke cloud', 'belum'),
(7, 7, '2025-06-13', 'Membuat laporan harian manual', 'valid'),
(8, 8, '2025-06-13', 'Observasi kegiatan bidang pengelolaan air', 'belum'),
(13, 1, '2025-06-11', 'mengusir roh jahat', 'belum'),
(16, 8, '2025-07-01', 'Membuat dokumen laporan workshop', 'valid'),
(17, 2, '2025-06-29', 'Menangkap ayam pak kumar', 'belum'),
(18, 17, '2025-07-17', 'Memperbaiki alat printer\r\n', 'valid');

-- --------------------------------------------------------

--
-- Struktur dari tabel `pasangan`
--

CREATE TABLE `pasangan` (
  `id` int(11) NOT NULL,
  `peserta_id` int(11) NOT NULL,
  `pembimbing_id` int(11) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `pasangan`
--

INSERT INTO `pasangan` (`id`, `peserta_id`, `pembimbing_id`, `status`, `created_at`, `updated_at`) VALUES
(1, 7, 12, 'accepted', '2025-07-16 02:29:48', '2025-07-16 02:29:48'),
(2, 4, 12, 'accepted', '2025-07-16 02:30:05', '2025-07-16 02:30:05'),
(3, 17, 12, 'accepted', '2025-07-18 00:15:04', '2025-07-18 00:15:04');

-- --------------------------------------------------------

--
-- Struktur dari tabel `peserta`
--

CREATE TABLE `peserta` (
  `id` int(11) NOT NULL,
  `nama` varchar(100) DEFAULT NULL,
  `jurusan` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `telepon` varchar(20) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `institusi_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `foto` varchar(120) DEFAULT NULL,
  `tanggal_masuk` date DEFAULT NULL,
  `tanggal_keluar` date DEFAULT NULL,
  `status` enum('aktif','selesai','verifikasi') DEFAULT 'aktif',
  `status_verifikasi` enum('pending','verified','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `tahun` year(4) DEFAULT NULL,
  `bulan` varchar(10) DEFAULT NULL,
  `bidang` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `peserta`
--

INSERT INTO `peserta` (`id`, `nama`, `jurusan`, `email`, `telepon`, `alamat`, `institusi_id`, `user_id`, `foto`, `tanggal_masuk`, `tanggal_keluar`, `status`, `status_verifikasi`, `created_at`, `updated_at`, `tahun`, `bulan`, `bidang`) VALUES
(1, 'Budi Santoso', 'Rekayasa Perangkat Lunak', 'budi@mail.com', '0811111111', 'Jl. Veteran No. 1', 1, 2, NULL, '2024-05-01', '2024-10-01', 'selesai', 'verified', '2025-07-01 08:19:11', '2025-07-17 10:55:03', '2024', 'Mei', NULL),
(2, 'Siti Nurhaliza', 'Teknik Komputer & Jaringan', 'siti@mail.com', '0822222222', 'Jl. Melati No. 2', 1, 3, 'uploads/profile_3_1752724315.jpg', '2024-05-02', '2024-08-02', 'selesai', 'verified', '2025-07-01 08:19:11', '2025-07-17 10:55:03', '2024', 'Mei', NULL),
(3, 'Ahmad Yani', 'Teknik Komputer & Jaringan', 'ahmad@mail.com', '0833333333', 'Jl. Kenanga No. 3', 4, 4, NULL, '2024-05-03', '2024-07-03', 'selesai', 'verified', '2025-07-01 08:19:11', '2025-07-17 10:55:03', '2024', 'Mei', NULL),
(4, 'Intan Permata', 'Desain Komunikasi Visual', 'intan@mail.com', '0844444444', 'Jl. Anggrek No. 4', 4, 5, NULL, '2024-08-01', '2024-11-01', 'selesai', 'verified', '2025-07-01 08:19:11', '2025-07-17 10:55:03', '2024', 'Agustus', NULL),
(5, 'Joko Widodo', 'Rekayasa Perangkat Lunak', 'jokowi@mail.com', '0855555555', 'Jl. Merpati No. 5', 2, 6, NULL, '2025-01-01', '2025-03-01', 'selesai', 'verified', '2025-07-01 08:19:11', '2025-07-17 10:55:03', '2025', 'Januari', NULL),
(6, 'Rina Marlina', 'Multimedia', 'rina@mail.com', '0866666666', 'Jl. Cendrawasih No. 6', 2, 7, NULL, '2025-01-02', '2025-04-02', 'selesai', 'verified', '2025-07-01 08:19:11', '2025-07-17 10:55:04', '2025', 'Januari', NULL),
(7, 'Agus Haris', 'Rekayasa Perangkat Lunak', 'agus@mail.com', '0877777777', 'Jl. Elang No. 7', 5, 8, NULL, '2025-05-01', '2025-07-01', 'selesai', 'verified', '2025-07-01 08:19:11', '2025-07-02 03:55:22', '2025', 'Mei', NULL),
(8, 'Nur Aini', 'Multimedia', 'nur@mail.com', '0888888888', 'Jl. Garuda No. 8', 5, 9, 'peserta_8_1752756262.jpg', '2025-05-02', '2025-08-02', 'aktif', 'verified', '2025-07-01 08:19:11', '2025-07-17 12:44:22', '2025', 'Mei', 'Bina Marga'),
(9, 'Dedi Putra', 'Teknik Komputer & Jaringan', 'dedi@mail.com', '0899999999', 'Jl. Rajawali No. 9', 3, 10, NULL, '2025-02-01', '2025-05-01', 'selesai', 'verified', '2025-07-01 08:19:11', '2025-07-17 10:55:04', '2024', 'Februari', NULL),
(10, 'Salsa Fitriani', 'Teknik Komputer & Jaringan', 'salsa@mail.com', '0810101010', 'Jl. Cemara No. 10', 3, 11, NULL, '2024-08-01', '2025-01-01', 'selesai', 'verified', '2025-07-01 08:19:11', '2025-07-17 10:55:04', '2024', 'Agustus', NULL),
(11, 'Fahri Ramadhan', 'Rekayasa Perangkat Lunak', 'fahri@mail.com', '081234567890', 'Jl. Merdeka No. 1', 1, 2, NULL, '2025-03-01', '2025-07-01', 'selesai', 'verified', '2025-07-01 08:23:46', '2025-07-17 10:55:04', '2025', 'Maret', NULL),
(12, 'Nabila Zahra', 'Multimedia', 'nabila@mail.com', '089876543210', 'Jl. Mawar No. 9', 2, 3, 'uploads/profile_3_1752724315.jpg', '2025-04-10', '2025-07-10', 'selesai', 'verified', '2025-07-01 08:23:46', '2025-07-17 10:55:04', '2025', 'April', NULL),
(17, 'Ahmad Arterus', 'RPL', 'ahmad12@gmail.com', '068347946464', 'Sukorejo', 8, 17, 'Uploads/profile_17_1752757828.jpg', '2025-07-17', '2025-12-17', 'aktif', 'verified', '2025-07-17 12:50:16', '2025-07-17 13:16:39', '2025', 'Juli', 'Bina Marga');

-- --------------------------------------------------------

--
-- Struktur dari tabel `register`
--

CREATE TABLE `register` (
  `id` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nim` varchar(20) NOT NULL,
  `universitas` varchar(100) NOT NULL,
  `jurusan` varchar(100) NOT NULL,
  `no_hp` varchar(15) NOT NULL,
  `alamat` text NOT NULL,
  `tanggal_masuk` date NOT NULL,
  `tanggal_keluar` date NOT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `status` enum('pending','accepted','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `riwayat_cetak_idcard`
--

CREATE TABLE `riwayat_cetak_idcard` (
  `id` int(11) NOT NULL,
  `peserta_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `jumlah` int(11) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `riwayat_cetak_idcard`
--

INSERT INTO `riwayat_cetak_idcard` (`id`, `peserta_id`, `user_id`, `jumlah`, `created_at`) VALUES
(1, 8, 1, 1, '2025-07-23 03:55:01');

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `nama` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `role` enum('admin','pembimbing','user') DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `nama`, `email`, `password`, `role`, `created_at`) VALUES
(1, 'Admin Utama', 'admin@magang.com', '$2y$10$ES6pIBCh8nkfHZjCb1WKu.Xx0VAJVooV7kqEoDXdnsgX5cl0XgsaK', 'admin', '2025-07-17 19:56:07'),
(2, 'Budi Santoso', 'budi@smk1.sch.id', 'e10adc3949ba59abbe56e057f20f883e', 'user', '2025-07-17 19:56:07'),
(3, 'Siti Nurhaliza', 'siti@smk1.sch.id', '$2y$10$ubeNj9/DfW44TPuo4/Zl6unIxSjb90jwP9A2Q9GdfFregHIxaAI62', 'user', '2025-07-17 19:56:07'),
(4, 'Ahmad Yani', 'ahmad@univ1.ac.id', 'e10adc3949ba59abbe56e057f20f883e', 'user', '2025-07-17 19:56:07'),
(5, 'Intan Permata', 'intan@univ1.ac.id', 'e10adc3949ba59abbe56e057f20f883e', 'user', '2025-07-17 19:56:07'),
(6, 'Joko Widodo', 'joko@smk2.sch.id', 'e10adc3949ba59abbe56e057f20f883e', 'user', '2025-07-17 19:56:07'),
(7, 'Rina Marlina', 'rina@smk2.sch.id', 'e10adc3949ba59abbe56e057f20f883e', 'user', '2025-07-17 19:56:07'),
(8, 'Agus Haris', 'agus@univ2.ac.id', 'e10adc3949ba59abbe56e057f20f883e', 'user', '2025-07-17 19:56:07'),
(9, 'Nur Aini', 'nur@univ2.ac.id', 'e10adc3949ba59abbe56e057f20f883e', 'user', '2025-07-17 19:56:07'),
(10, 'Dedi Putra', 'dedi@smk3.sch.id', 'e10adc3949ba59abbe56e057f20f883e', 'user', '2025-07-17 19:56:07'),
(11, 'Salsa Fitriani', 'salsa@smk3.sch.id', 'e10adc3949ba59abbe56e057f20f883e', 'user', '2025-07-17 19:56:07'),
(12, 'Pembimbing A', 'pembimbing1@magang.com', '$2y$10$qLt5brH7b87JRTqTLXieOuG4cOyJDB610WAGpjnSqbvYipfkACPqS', 'pembimbing', '2025-07-17 19:56:07'),
(13, 'Pembimbing B', 'pembimbing2@magang.com', '$2y$10$z/NSTr7TtFFPlY2Lcd3bWYHYkeX7FVk2h5Nniuyxh.YO61qD1JxHy', 'pembimbing', '2025-07-17 19:56:07'),
(14, 'Pembimbing C', 'pembimbing3@magang.com', '$2y$10$z/NSTr7TtFFPlY2Lcd3bWYHYkeX7FVk2h5Nniuyxh.YO61qD1JxHy', 'pembimbing', '2025-07-17 19:56:07'),
(17, 'ahmad12@gmail.com', 'ahamd12@gmail.com', '$2y$10$Aqd2PqQrwLity8dIC6BoV.p7PQy/9qfFbq.IhzDYcZs1YeOn5dD52', 'user', '2025-07-17 19:56:23');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `arsip`
--
ALTER TABLE `arsip`
  ADD PRIMARY KEY (`id`),
  ADD KEY `peserta_id` (`peserta_id`);

--
-- Indeks untuk tabel `institusi`
--
ALTER TABLE `institusi`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `jadwal`
--
ALTER TABLE `jadwal`
  ADD PRIMARY KEY (`id`),
  ADD KEY `peserta_id` (`peserta_id`),
  ADD KEY `pembimbing_id` (`pembimbing_id`);

--
-- Indeks untuk tabel `laporan`
--
ALTER TABLE `laporan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `peserta_id` (`peserta_id`);

--
-- Indeks untuk tabel `pasangan`
--
ALTER TABLE `pasangan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `peserta_id` (`peserta_id`),
  ADD KEY `pembimbing_id` (`pembimbing_id`);

--
-- Indeks untuk tabel `peserta`
--
ALTER TABLE `peserta`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_peserta_institusi` (`institusi_id`),
  ADD KEY `idx_peserta_user` (`user_id`),
  ADD KEY `idx_peserta_status` (`status_verifikasi`),
  ADD KEY `idx_peserta_tanggal` (`tanggal_masuk`);

--
-- Indeks untuk tabel `register`
--
ALTER TABLE `register`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `nim` (`nim`);

--
-- Indeks untuk tabel `riwayat_cetak_idcard`
--
ALTER TABLE `riwayat_cetak_idcard`
  ADD PRIMARY KEY (`id`),
  ADD KEY `peserta_id` (`peserta_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `arsip`
--
ALTER TABLE `arsip`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT untuk tabel `institusi`
--
ALTER TABLE `institusi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT untuk tabel `jadwal`
--
ALTER TABLE `jadwal`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `laporan`
--
ALTER TABLE `laporan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT untuk tabel `pasangan`
--
ALTER TABLE `pasangan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT untuk tabel `peserta`
--
ALTER TABLE `peserta`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT untuk tabel `register`
--
ALTER TABLE `register`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT untuk tabel `riwayat_cetak_idcard`
--
ALTER TABLE `riwayat_cetak_idcard`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `arsip`
--
ALTER TABLE `arsip`
  ADD CONSTRAINT `arsip_ibfk_1` FOREIGN KEY (`peserta_id`) REFERENCES `peserta` (`id`);

--
-- Ketidakleluasaan untuk tabel `jadwal`
--
ALTER TABLE `jadwal`
  ADD CONSTRAINT `jadwal_ibfk_1` FOREIGN KEY (`peserta_id`) REFERENCES `peserta` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `jadwal_ibfk_2` FOREIGN KEY (`pembimbing_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `laporan`
--
ALTER TABLE `laporan`
  ADD CONSTRAINT `laporan_ibfk_1` FOREIGN KEY (`peserta_id`) REFERENCES `peserta` (`id`);

--
-- Ketidakleluasaan untuk tabel `pasangan`
--
ALTER TABLE `pasangan`
  ADD CONSTRAINT `pasangan_ibfk_1` FOREIGN KEY (`peserta_id`) REFERENCES `peserta` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `pasangan_ibfk_2` FOREIGN KEY (`pembimbing_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `peserta`
--
ALTER TABLE `peserta`
  ADD CONSTRAINT `peserta_ibfk_1` FOREIGN KEY (`institusi_id`) REFERENCES `institusi` (`id`),
  ADD CONSTRAINT `peserta_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Ketidakleluasaan untuk tabel `riwayat_cetak_idcard`
--
ALTER TABLE `riwayat_cetak_idcard`
  ADD CONSTRAINT `riwayat_cetak_idcard_ibfk_1` FOREIGN KEY (`peserta_id`) REFERENCES `peserta` (`id`),
  ADD CONSTRAINT `riwayat_cetak_idcard_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

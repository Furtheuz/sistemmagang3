DROP DATABASE IF EXISTS sistem_magang;
CREATE DATABASE sistem_magang;
USE sistem_magang;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin','pembimbing','user') NOT NULL DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS institusi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    alamat TEXT,
    telepon VARCHAR(20),
    email VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS peserta (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    institusi_id INT,
    user_id INT,
    status ENUM('aktif','selesai','verifikasi') DEFAULT 'aktif',
    mulai DATE DEFAULT NULL,
    selesai DATE DEFAULT NULL,
    foto VARCHAR(120) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (institusi_id) REFERENCES institusi(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS jadwal (
    id INT AUTO_INCREMENT PRIMARY KEY,
    peserta_id INT,
    tanggal DATE,
    pembimbing_id INT,
    tugas TEXT,
    FOREIGN KEY (peserta_id) REFERENCES peserta(id),
    FOREIGN KEY (pembimbing_id) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS laporan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    peserta_id INT,
    tanggal DATE,
    kegiatan TEXT,
    validasi ENUM('belum','valid') DEFAULT 'belum',
    FOREIGN KEY (peserta_id) REFERENCES peserta(id)
);

CREATE TABLE IF NOT EXISTS arsip (
    id INT AUTO_INCREMENT PRIMARY KEY,
    peserta_id INT,
    keterangan TEXT,
    tanggal_arsip DATE,
    FOREIGN KEY (peserta_id) REFERENCES peserta(id)
);


CREATE TABLE IF NOT EXISTS template_idcard (
    id INT AUTO_INCREMENT PRIMARY KEY,
    primary_color CHAR(7) NOT NULL DEFAULT '#2563eb',
    logo VARCHAR(120) DEFAULT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS riwayat_cetak_idcard (
    id INT AUTO_INCREMENT PRIMARY KEY,
    peserta_id INT NOT NULL,
    user_id INT NOT NULL,  
    jumlah INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (peserta_id) REFERENCES peserta(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS log_cetak (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    aksi VARCHAR(255) NOT NULL,
    ip VARCHAR(45) DEFAULT NULL,
    waktu TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

INSERT INTO users (id, nama, email, password, role) VALUES
(1, 'Admin Utama', 'admin@magang.com', '0192023a7bbd73250516f069df18b500', 'admin'),
(2, 'Budi Santoso', 'budi@smk1.sch.id', 'e10adc3949ba59abbe56e057f20f883e', 'user'),
(3, 'Siti Nurhaliza', 'siti@smk1.sch.id', 'e10adc3949ba59abbe56e057f20f883e', 'user'),
(4, 'Ahmad Yani', 'ahmad@univ1.ac.id', 'e10adc3949ba59abbe56e057f20f883e', 'user'),
(5, 'Intan Permata', 'intan@univ1.ac.id', 'e10adc3949ba59abbe56e057f20f883e', 'user'),
(6, 'Joko Widodo', 'joko@smk2.sch.id', 'e10adc3949ba59abbe56e057f20f883e', 'user'),
(7, 'Rina Marlina', 'rina@smk3.sch.id', 'e10adc3949ba59abbe56e057f20f883e', 'user'),
(8, 'Agus Haris', 'agus@univ2.ac.id', 'e10adc3949ba59abbe56e057f20f883e', 'user'),
(9, 'Nur Aini', 'nur@univ2.ac.id', 'e10adc3949ba59abbe56e057f20f883e', 'user'),
(10, 'Dedi Putra', 'dedi@smk3.sch.id', 'e10adc3949ba59abbe56e057f20f883e', 'user'),
(11, 'Salsa Fitriani', 'salsa@smk3.sch.id', 'e10adc3949ba59abbe56e057f20f883e', 'user'),
(12, 'Pembimbing A', 'pembimbing1@magang.com', '$2y$10$z/N5Tr7tTtFIPY2Lcd3bWYHYkeX7Fk2h5Niuyxh.YO61l..', 'pembimbing'),
(13, 'Pembimbing B', 'pembimbing2@magang.com', '$2y$10$z/N5Tr7tTtFIPY2Lcd3bWYHYkeX7Fk2h5Niuyxh.YO61l..', 'pembimbing'),
(14, 'Pembimbing C', 'pembimbing3@magang.com', '$2y$10$g1uMhnvyNPncvophbL7rc.yA2SGojAPKgl.4cM8ZR7/QA2', 'pembimbing'),
(15, 'Tristan Egrianto', 'tristt377@gmail.com', '$2y$10$t7zp1TfFtPlY2Lcd3bWYHYkeX7Fk2h5Niuyxh.YO61l..', 'user'),
(16, 'James Erich Feursterch', 'erich55@gmail.com', '$2y$10$t7zp1TfFtPlY2Lcd3bWYHYkeX7Fk2h5Niuyxh.YO61l..', 'user');

INSERT INTO peserta (nama, institusi_id, user_id, status, mulai, selesai) VALUES
('Budi Santoso', 1, 2, 'aktif', '2024-06-01', '2024-08-31'),
('Siti Nurhaliza', 1, 3, 'aktif', '2024-06-01', '2024-08-31'),
('Ahmad Yani', 2, 4, 'aktif', '2024-06-01', '2024-08-31'),
('Intan Permata', 2, 5, 'aktif', '2024-06-01', '2024-08-31'),
('Joko Widodo', 1, 6, 'aktif', '2024-06-01', '2024-08-31'),
('Rina Marlina', 1, 7, 'aktif', '2024-06-01', '2024-08-31'),
('Agus Haris', 2, 8, 'aktif', '2024-06-01', '2024-08-31'),
('Nur Aini', 2, 9, 'aktif', '2024-06-01', '2024-08-31'),
('Dedi Putra', 1, 10, 'aktif', '2024-06-01', '2024-08-31'),
('Salsa Fitriani', 1, 11, 'aktif', '2024-06-01', '2024-08-31'),
('Tristan Egrianto', 2, 15, 'aktif', '2024-06-01', '2024-08-31'),
('James Erich Feursterch', 2, 16, 'aktif', '2024-06-01', '2024-08-31');

INSERT INTO institusi (id, nama, alamat, telepon, email) VALUES
(1, 'SMK Negeri 1', 'Jl. Pendidikan No. 1', '021-7881121', 'smknegeri1@example.com'),
(2, 'SMK Negeri 2', 'Jl. Teknologi No. 2', '021-7881122', 'smknegeri2@example.com'),
(3, 'SMK Negeri 3', 'Jl. Industri No. 3', '021-7881123', 'smknegeri3@example.com'),
(4, 'Universitas A', 'Jl. Kampus A No. 1', '021-7881124', 'universitasa@example.com'),
(5, 'Universitas B', 'Jl. Kampus B No. 2', '021-7881125', 'universitasb@example.com');

-- ---------------------------------------------------------------------------
-- DATA CONTOH RIWAYAT CETAK & TEMPLATE (Opsional)
-- ---------------------------------------------------------------------------

INSERT INTO template_idcard (primary_color,logo) VALUES ('#2563eb','logo.png');

-- Misal admin (id=1) mencetak 2 peserta
INSERT INTO riwayat_cetak_idcard (peserta_id,user_id,jumlah) VALUES (1,1,1),(2,1,2);

INSERT INTO log_cetak (user_id,aksi,ip) VALUES
(1,'Cetak massal 3 peserta','127.0.0.1');
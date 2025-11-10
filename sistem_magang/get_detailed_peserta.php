<?php
include "config/auth.php";
include "config/db.php";
checkLogin();

header('Content-Type: application/json');

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID peserta tidak valid']);
    exit;
}

$peserta_id = (int)$_GET['id'];

$query = "SELECT 
            p.id,
            p.nama,
            p.jurusan,
            p.email,
            p.telepon,
            p.alamat,
            p.foto,
            p.tanggal_masuk,
            p.tanggal_keluar,
            p.status,
            p.status_verifikasi,
            p.created_at,
            p.updated_at,
            p.tahun,
            p.bulan,
            i.nama AS institusi,
            u.nama AS user_name,
            u.email AS user_email
          FROM peserta p
          LEFT JOIN institusi i ON p.institusi_id = i.id
          LEFT JOIN users u ON p.user_id = u.id
          WHERE p.id = $peserta_id";

$result = mysqli_query($conn, $query);

if (!$result) {
    echo json_encode(['success' => false, 'message' => 'Gagal mengambil data dari database']);
    exit;
}

$peserta = mysqli_fetch_assoc($result);

if (!$peserta) {
    echo json_encode(['success' => false, 'message' => 'Peserta tidak ditemukan']);
    exit;
}

echo json_encode(['success' => true, 'peserta' => $peserta]);
?>

<?php
session_start();
include "../config/db.php"; // Pastikan path benar
header('Content-Type: application/json');
ob_start();

// Debug: Log request parameters
error_log("GET params: " . print_r($_GET, true));

// Cek apakah ID ada dan valid
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'ID tidak valid']);
    exit;
}

$id = (int)$_GET['id'];
$action = isset($_GET['action']) ? $_GET['action'] : '';

// Query ke database
$query = "SELECT p.*, i.nama as institusi 
          FROM peserta p 
          LEFT JOIN institusi i ON p.institusi_id = i.id 
          WHERE p.id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($result && $row = mysqli_fetch_assoc($result)) {
    $response = [
        'id' => $row['id'],
        'nama' => $row['nama'] ?? '',
        'email' => $row['email'] ?? '',
        'telepon' => $row['telepon'] ?? '',
        'institusi_id' => $row['institusi_id'] ?? '',
        'institusi' => $row['institusi'] ?? 'Tidak ada',
        'alamat' => $row['alamat'] ?? '',
        'tanggal_masuk' => $row['tanggal_masuk'] ?? '',
        'tanggal_keluar' => $row['tanggal_keluar'] ?? '',
        'bidang' => $row['bidang'] ?? '',
        'foto' => $row['foto'] ?? '',
        'status_verifikasi' => $row['status_verifikasi'] ?? '',
        'status' => (strtotime($row['tanggal_keluar']) < strtotime(date('Y-m-d'))) ? 'completed' : 'active'
    ];
    echo json_encode($response);
} else {
    http_response_code(404);
    echo json_encode(['error' => 'Peserta tidak ditemukan']);
}

mysqli_stmt_close($stmt);
ob_end_flush();
?>
<?php
// get_peserta.php

// Include database connection
include "config/db.php";

// Check if ID is provided and is numeric
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid or missing ID']);
    exit;
}

$peserta_id = (int)$_GET['id'];

// Prepare and execute query to fetch participant data
$query = "SELECT p.*, i.nama as institusi 
          FROM peserta p 
          LEFT JOIN institusi i ON p.institusi_id = i.id 
          WHERE p.id = ?";
$stmt = mysqli_prepare($conn, $query);

if ($stmt === false) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to prepare statement: ' . mysqli_error($conn)]);
    exit;
}

mysqli_stmt_bind_param($stmt, "i", $peserta_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($row = mysqli_fetch_assoc($result)) {
    // Prepare response array
    $response = [
        'id' => $row['id'],
        'nama' => $row['nama'],
        'email' => $row['email'],
        'telepon' => $row['telepon'],
        'institusi_id' => $row['institusi_id'],
        'institusi' => $row['institusi'] ?? 'Tidak ada',
        'bidang' => $row['bidang'] ?? '',
        'alamat' => $row['alamat'],
        'tanggal_masuk' => $row['tanggal_masuk'],
        'tanggal_keluar' => $row['tanggal_keluar'],
        'status_verifikasi' => $row['status_verifikasi'],
        'status' => $row['status'],
        'foto' => $row['foto'] ? $row['foto'] : null
    ];
    
    // Set content type and output JSON
    header('Content-Type: application/json');
    echo json_encode($response);
} else {
    http_response_code(404);
    echo json_encode(['error' => 'Peserta not found']);
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
?>
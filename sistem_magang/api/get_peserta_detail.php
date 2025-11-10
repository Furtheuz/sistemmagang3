<?php
include "../config/db.php"; // Adjust path as needed

// Ensure no debug output interferes with JSON
error_reporting(0); // Suppress warnings for production; remove for debugging
ob_start(); // Start output buffering to prevent stray output

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $query = "SELECT p.*, i.nama as institusi 
              FROM peserta p 
              LEFT JOIN institusi i ON p.institusi_id = i.id 
              WHERE p.id = $id";
    $result = mysqli_query($conn, $query);
    
    if ($result && $row = mysqli_fetch_assoc($result)) {
        if (isset($_GET['action']) && $_GET['action'] == 'edit') {
            // Return JSON for edit modal
            header('Content-Type: application/json');
            $response = [
                'id' => $row['id'],
                'nama' => $row['nama'],
                'email' => $row['email'] ?? '',
                'institusi_id' => $row['institusi_id'] ?? '',
                'bidang' => $row['bidang'] ?? '',
                'tanggal_masuk' => $row['tanggal_masuk'] ?? '',
                'tanggal_keluar' => $row['tanggal_keluar'] ?? ''
            ];
            echo json_encode($response);
        } else {
            // Return HTML for detail modal
            echo "
            <p><strong>Nama:</strong> " . htmlspecialchars($row['nama']) . "</p>
            <p><strong>Email:</strong> " . htmlspecialchars($row['email'] ?? '-') . "</p>
            <p><strong>Institusi:</strong> " . htmlspecialchars($row['institusi'] ?? 'Tidak ada') . "</p>
            <p><strong>Bidang:</strong> " . htmlspecialchars($row['bidang'] ?? '-') . "</p>
            <p><strong>Tanggal Masuk:</strong> " . date('d/m/Y', strtotime($row['tanggal_masuk'] ?? 'now')) . "</p>
            <p><strong>Tanggal Keluar:</strong> " . date('d/m/Y', strtotime($row['tanggal_keluar'] ?? 'now')) . "</p>
            <p><strong>Status:</strong> <span class='status-badge status-" . (strtotime($row['tanggal_keluar']) < strtotime(date('Y-m-d')) ? 'completed' : 'active') . "'>" . (strtotime($row['tanggal_keluar']) < strtotime(date('Y-m-d')) ? 'Completed' : 'Active') . "</span></p>";
        }
    } else {
        http_response_code(404);
        if (isset($_GET['action']) && $_GET['action'] == 'edit') {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Peserta tidak ditemukan']);
        } else {
            echo "Peserta tidak ditemukan";
        }
    }
} else {
    http_response_code(400);
    if (isset($_GET['action']) && $_GET['action'] == 'edit') {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'ID tidak diberikan']);
    } else {
        echo "ID tidak diberikan";
    }
}

ob_end_flush(); // End output buffering
?>
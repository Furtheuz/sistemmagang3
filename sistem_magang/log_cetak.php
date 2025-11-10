<?php
include "config/auth.php";
include "config/db.php";
checkLogin();

header('Content-Type: application/json');

$user_id = $_SESSION['user']['id'];

// Tangani input JSON dari fetch API (untuk cetak massal)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty(file_get_contents('php://input'))) {
    $data = json_decode(file_get_contents('php://input'), true);
    if (isset($data['peserta_ids']) && is_array($data['peserta_ids'])) {
        $stmt = $conn->prepare("INSERT INTO riwayat_cetak_idcard (peserta_id, user_id, jumlah, created_at) VALUES (?, ?, 1, NOW())");
        $stmt->bind_param("ii", $peserta_id, $user_id);
        
        foreach ($data['peserta_ids'] as $peserta_id) {
            $peserta_id = (int)$peserta_id;
            if ($peserta_id > 0) {
                if (!$stmt->execute()) {
                    echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan riwayat cetak: ' . $stmt->error]);
                    exit();
                }
            }
        }
        $stmt->close();
        echo json_encode(['status' => 'success', 'message' => 'Riwayat cetak berhasil disimpan']);
        exit();
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Data peserta tidak valid']);
        exit();
    }
}

// Tangani cetak mandiri (dari profile.php)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['peserta_id'])) {
    $peserta_id = (int)$_POST['peserta_id'];
    if ($peserta_id > 0) {
        $stmt = $conn->prepare("INSERT INTO riwayat_cetak_idcard (peserta_id, user_id, jumlah, created_at) VALUES (?, ?, 1, NOW())");
        $stmt->bind_param("ii", $peserta_id, $user_id);
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Riwayat cetak berhasil disimpan']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan riwayat cetak: ' . $stmt->error]);
        }
        $stmt->close();
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Peserta ID tidak valid']);
    }
    exit();
}

echo json_encode(['status' => 'error', 'message' => 'Permintaan tidak valid']);
?>
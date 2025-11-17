<?php
include '../config/db.php';  // Adjust path if config/ is not one level up

header('Content-Type: application/json');

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $query = "SELECT * FROM register WHERE id = $id";
    $result = mysqli_query($conn, $query);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $data = mysqli_fetch_assoc($result);
        // Handle foto path if needed (assuming foto is stored in uploads/)
        if (isset($data['foto']) && $data['foto']) {
            $data['foto'] = 'uploads/' . $data['foto'];
        }
        echo json_encode($data);
    } else {
        echo json_encode(['error' => 'Data not found']);
    }
} else {
    echo json_encode(['error' => 'ID not provided']);
}

mysqli_close($conn);
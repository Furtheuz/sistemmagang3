<?php
include "config/db.php";
$start = isset($_GET['start']) ? (int)$_GET['start'] : 0;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
$query = "SELECT a.*, p.nama, p.email, i.nama as institusi 
          FROM arsip a 
          JOIN peserta p ON a.peserta_id = p.id 
          LEFT JOIN institusi i ON p.institusi_id = i.id 
          ORDER BY a.tanggal_arsip DESC 
          LIMIT $start, $limit";
$result = mysqli_query($conn, $query);
$data = [];
while ($row = mysqli_fetch_assoc($result)) {
    $data[] = $row;
}
echo json_encode($data);
mysqli_close($conn);
?>
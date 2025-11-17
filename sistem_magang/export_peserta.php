<?php
include "config/auth.php";
include "config/db.php";
checkLogin();
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=peserta_'.date('Ymd_His').'.csv');
$out = fopen('php://output', 'w');
fputcsv($out, ['ID','Nama','Jurusan','Instansi','Mulai','Selesai']);
$q = mysqli_query($conn, "SELECT id,nama,jurusan,instansi,mulai,selesai FROM peserta ORDER BY nama");
while($r=mysqli_fetch_assoc($q)){
    fputcsv($out, $r);
}
fclose($out);
exit;
?>
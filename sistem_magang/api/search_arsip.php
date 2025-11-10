<?php
include "../config/db.php";

$limit = 10;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;
$search_arsip = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

$arsip_query = "SELECT a.*, p.nama, p.email, i.nama as institusi 
                FROM arsip a 
                JOIN peserta p ON a.peserta_id = p.id 
                LEFT JOIN institusi i ON p.institusi_id = i.id 
                WHERE p.nama LIKE '%$search_arsip%' OR p.email LIKE '%$search_arsip%' OR a.keterangan LIKE '%$search_arsip%'
                ORDER BY a.tanggal_arsip DESC 
                LIMIT $limit OFFSET $offset";
$arsip = mysqli_query($conn, $arsip_query);

$total_arsip_query = "SELECT COUNT(*) as total 
                      FROM arsip a 
                      JOIN peserta p ON a.peserta_id = p.id 
                      WHERE p.nama LIKE '%$search_arsip%' OR p.email LIKE '%$search_arsip%' OR a.keterangan LIKE '%$search_arsip%'";
$total_arsip_result = mysqli_fetch_assoc(mysqli_query($conn, $total_arsip_query));
$total_arsip_pages = ceil($total_arsip_result['total'] / $limit);

$no = $offset + 1;
while ($row = mysqli_fetch_assoc($arsip)) {
    echo "<tr>";
    echo "<td data-label='No'>$no</td>";
    echo "<td data-label='Nama Peserta'>" . htmlspecialchars($row['nama']) . "</td>";
    echo "<td data-label='Email'>" . htmlspecialchars($row['email']) . "</td>";
    echo "<td data-label='Institusi'>" . htmlspecialchars($row['institusi'] ?? 'Tidak ada') . "</td>";
    echo "<td data-label='Keterangan'><span class='badge bg-secondary'>" . htmlspecialchars($row['keterangan']) . "</span></td>";
    echo "<td data-label='Tanggal Arsip'>" . date('d/m/Y H:i', strtotime($row['tanggal_arsip'])) . "</td>";
    echo "<td data-label='Aksi'>";
    echo "<div class='btn-group'>";
    if ($_SESSION['user']['role'] == 'admin') {
        echo "<a href='?tab=arsip&restore=" . $row['id'] . "' class='btn btn-success btn-sm' onclick='return confirm(\"Yakin ingin mengembalikan peserta ini dari arsip?\")'><i class='fas fa-undo'></i> Restore</a>";
        echo "<a href='?tab=arsip&hapus_arsip=" . $row['id'] . "' class='btn btn-danger btn-sm' onclick='return confirm(\"Yakin ingin menghapus data arsip ini secara permanen?\")'><i class='fas fa-trash'></i> Hapus</a>";
    }
    echo "</div></td>";
    echo "</tr>";
    $no++;
}

if (mysqli_num_rows($arsip) == 0) {
    echo "<tr><td colspan='7' class='text-center text-muted py-4'>";
    echo "<i class='fas fa-inbox fa-3x mb-3'></i><br>Belum ada data arsip";
    echo "</td></tr>";
}

echo "</tbody></table>";
echo "<div class='pagination'>";
if ($page > 1) {
    echo "<a href='?tab=arsip&page_arsip=" . ($page - 1) . "&search_arsip=" . urlencode($search_arsip) . "'><i class='fas fa-chevron-left'></i> Sebelumnya</a>";
} else {
    echo "<span><i class='fas fa-chevron-left'></i> Sebelumnya</span>";
}
echo "<span>Halaman $page dari $total_arsip_pages</span>";
if ($page < $total_arsip_pages) {
    echo "<a href='?tab=arsip&page_arsip=" . ($page + 1) . "&search_arsip=" . urlencode($search_arsip) . "'>Selanjutnya <i class='fas fa-chevron-right'></i></a>";
} else {
    echo "<span>Selanjutnya <i class='fas fa-chevron-right'></i></span>";
}
echo "</div>";
?>
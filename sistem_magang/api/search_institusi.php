<?php
include "../config/db.php";

$limit = 10;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;
$search_institusi = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

$institusi_query = "SELECT * 
                    FROM institusi 
                    WHERE nama LIKE '%$search_institusi%' 
                    ORDER BY nama 
                    LIMIT $limit OFFSET $offset";
$institusi = mysqli_query($conn, $institusi_query);

$total_institusi_query = "SELECT COUNT(*) as total 
                          FROM institusi 
                          WHERE nama LIKE '%$search_institusi%'";
$total_institusi_result = mysqli_fetch_assoc(mysqli_query($conn, $total_institusi_query));
$total_institusi_pages = ceil($total_institusi_result['total'] / $limit);

$no = $offset + 1;
while ($row = mysqli_fetch_assoc($institusi)) {
    echo "<tr>";
    echo "<td data-label='No'>$no</td>";
    echo "<td data-label='Nama Institusi'>" . htmlspecialchars($row['nama']) . "</td>";
    echo "<td data-label='Alamat'>" . htmlspecialchars($row['alamat']) . "</td>";
    echo "<td data-label='Telepon'>" . htmlspecialchars($row['telepon']) . "</td>";
    echo "<td data-label='Aksi'>";
    echo "<div class='btn-group'>";
    echo "<button onclick=\"editInstitusi(" . $row['id'] . ", '" . addslashes($row['nama']) . "', '" . addslashes($row['alamat']) . "', '" . addslashes($row['telepon']) . "')\" class='btn btn-warning btn-sm'><i class='fas fa-edit'></i> Edit</button>";
    echo "<a href='?tab=daftar_institusi&hapus_institusi=" . $row['id'] . "' class='btn btn-danger btn-sm' onclick='return confirm(\"Yakin ingin menghapus institusi ini?\")'><i class='fas fa-trash'></i> Hapus</a>";
    echo "</div></td>";
    echo "</tr>";
    $no++;
}

if (mysqli_num_rows($institusi) == 0) {
    echo "<tr><td colspan='5' class='text-center text-muted py-4'>";
    echo "<i class='fas fa-building fa-3x mb-3'></i><br>Belum ada data institusi";
    echo "</td></tr>";
}

echo "</tbody></table>";
echo "<div class='pagination'>";
if ($page > 1) {
    echo "<a href='?tab=daftar_institusi&page_institusi=" . ($page - 1) . "&search_institusi=" . urlencode($search_institusi) . "'><i class='fas fa-chevron-left'></i> Sebelumnya</a>";
} else {
    echo "<span><i class='fas fa-chevron-left'></i> Sebelumnya</span>";
}
echo "<span>Halaman $page dari $total_institusi_pages</span>";
if ($page < $total_institusi_pages) {
    echo "<a href='?tab=daftar_institusi&page_institusi=" . ($page + 1) . "&search_institusi=" . urlencode($search_institusi) . "'>Selanjutnya <i class='fas fa-chevron-right'></i></a>";
} else {
    echo "<span>Selanjutnya <i class='fas fa-chevron-right'></i></span>";
}
echo "</div>";
?>
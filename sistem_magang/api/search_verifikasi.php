<?php
include "../config/db.php";

$limit = 10;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;
$search_peserta = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

$pending_registrations_query = "SELECT * 
                                FROM register 
                                WHERE status = 'pending' 
                                AND (nama LIKE '%$search_peserta%' OR email LIKE '%$search_peserta%')
                                ORDER BY created_at DESC 
                                LIMIT $limit OFFSET $offset";
$pending_registrations = mysqli_query($conn, $pending_registrations_query);

$total_pending_query = "SELECT COUNT(*) as total 
                        FROM register 
                        WHERE status = 'pending' 
                        AND (nama LIKE '%$search_peserta%' OR email LIKE '%$search_peserta%')";
$total_pending_result = mysqli_fetch_assoc(mysqli_query($conn, $total_pending_query));
$total_pending_pages = ceil($total_pending_result['total'] / $limit);

echo '<table class="table"><tbody id="verifikasiTable">';
$no = $offset + 1;
while ($r = mysqli_fetch_assoc($pending_registrations)) {
    echo "<tr>";
    echo "<td data-label='No'>$no</td>";
    echo "<td data-label='Nama'>" . htmlspecialchars($r['nama']) . "</td>";
    echo "<td data-label='Email'>" . htmlspecialchars($r['email']) . "</td>";
    echo "<td data-label='Tanggal Masuk'>" . date('d/m/Y', strtotime($r['tanggal_masuk'])) . "</td>";
    echo "<td data-label='Tanggal Keluar'>" . date('d/m/Y', strtotime($r['tanggal_keluar'])) . "</td>";
    echo "<td data-label='Bidang'>";
    echo "<form method='post' id='verifyForm_" . $r['id'] . "'>";
    echo "<input type='hidden' name='register_id' value='" . $r['id'] . "'>";
    echo "<input type='hidden' name='tab' value='verifikasi'>";
    echo "<select name='bidang' class='form-control' required>";
    echo "<option value='' disabled selected>Pilih Bidang</option>";
    $bidang_options = ['Sekretariat', 'Bina Marga', 'Tata Ruang', 'Umum', 'Jasa Konstruksi'];
    foreach ($bidang_options as $bidang) {
        echo "<option value='$bidang'>$bidang</option>";
    }
    echo "</select></td>";
    echo "<td data-label='Aksi'>";
    echo "<button type='submit' name='verify_registration' class='btn btn-sm btn-success'><i class='fas fa-check'></i> Verifikasi</button>";
    echo "</form></td>";
    echo "</tr>";
    $no++;
}

if (mysqli_num_rows($pending_registrations) == 0) {
    echo "<tr><td colspan='7' class='text-center'>";
    echo "<div style='padding: 2rem;'>";
    echo "<i class='fas fa-bell' style='font-size: 3rem; color: #d1d5db; margin-bottom: 1rem;'></i>";
    echo "<p>Tidak ada registrasi pending</p>";
    echo "</div></td></tr>";
}

echo "</tbody></table>";
echo "<div class='pagination'>";
if ($page > 1) {
    echo "<a href='?tab=verifikasi&page_peserta=" . ($page - 1) . "&search_peserta=" . urlencode($search_peserta) . "'><i class='fas fa-chevron-left'></i> Sebelumnya</a>";
} else {
    echo "<span><i class='fas fa-chevron-left'></i> Sebelumnya</span>";
}
echo "<span>Halaman $page dari $total_pending_pages</span>";
if ($page < $total_pending_pages) {
    echo "<a href='?tab=verifikasi&page_peserta=" . ($page + 1) . "&search_peserta=" . urlencode($search_peserta) . "'>Selanjutnya <i class='fas fa-chevron-right'></i></a>";
} else {
    echo "<span>Selanjutnya <i class='fas fa-chevron-right'></i></span>";
}
echo "</div>";
?>
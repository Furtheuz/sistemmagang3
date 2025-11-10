<?php
include "../config/db.php";

$limit = 10;
$page_peserta = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset_peserta = ($page_peserta - 1) * $limit;
$search_peserta = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

$peserta_query = "SELECT p.*, i.nama as institusi 
                  FROM peserta p 
                  LEFT JOIN institusi i ON p.institusi_id = i.id 
                  WHERE p.status = 'aktif' 
                  AND (p.nama LIKE '%$search_peserta%' OR p.email LIKE '%$search_peserta%' OR p.bidang LIKE '%$search_peserta%')
                  ORDER BY p.tanggal_masuk DESC 
                  LIMIT $limit OFFSET $offset_peserta";
$peserta = mysqli_query($conn, $peserta_query);

$total_peserta_query = "SELECT COUNT(*) as total 
                        FROM peserta p 
                        WHERE p.status = 'aktif' 
                        AND (p.nama LIKE '%$search_peserta%' OR p.email LIKE '%$search_peserta%' OR p.bidang LIKE '%$search_peserta%')";
$total_peserta_result = mysqli_fetch_assoc(mysqli_query($conn, $total_peserta_query));
$total_peserta_pages = ceil($total_peserta_result['total'] / $limit);

ob_start();
?>
<tbody id="pesertaTable">
    <?php 
    $no = $offset_peserta + 1;
    while($p = mysqli_fetch_assoc($peserta)): 
        $today = date('Y-m-d');
        $status_magang = (strtotime($p['tanggal_keluar']) < strtotime($today)) ? 'completed' : 'active';
    ?>
    <tr>
        <td data-label="No"><?= $no++ ?></td>
        <td data-label="Nama"><strong><?= htmlspecialchars($p['nama']) ?></strong></td>
        <td data-label="Email"><?= htmlspecialchars($p['email'] ?? '-') ?></td>
        <td data-label="Institusi"><?= htmlspecialchars($p['institusi'] ?? 'Tidak ada institusi') ?></td>
        <td data-label="Bidang"><?= htmlspecialchars($p['bidang'] ?? '-') ?></td>
        <td data-label="Tanggal Masuk"><?= date('d/m/Y', strtotime($p['tanggal_masuk'] ?? 'now')) ?></td>
        <td data-label="Tanggal Keluar"><?= date('d/m/Y', strtotime($p['tanggal_keluar'] ?? 'now')) ?></td>
        <td data-label="Status Magang">
            <span class="status-badge status-<?= $status_magang ?>">
                <?= ucfirst($status_magang) ?>
            </span>
        </td>
        <td data-label="Aksi">
            <div class="btn-group">
                <button class="btn btn-sm btn-primary" data-action="view" data-id="<?= $p['id'] ?>" title="Lihat Detail">
                    <i class="fas fa-eye"></i>
                </button>
                <?php if ($_SESSION['user']['role'] == 'admin'): ?>
                    <button class="btn btn-sm btn-success" data-action="edit" data-id="<?= $p['id'] ?>" title="Edit">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="confirmDelete(<?= $p['id'] ?>, '<?= htmlspecialchars($p['nama']) ?>')" title="Hapus">
                        <i class="fas fa-trash"></i>
                    </button>
                    <?php if ($status_magang == 'active'): ?>
                        <a href="?tab=verifikasi&arsipkan=<?= $p['id'] ?>&keterangan=Selesai" class="btn btn-sm btn-warning" onclick="return confirm('Yakin ingin mengarsipkan peserta \"<?= htmlspecialchars($p['nama']) ?>\" dengan keterangan \"Selesai\"?')">
                            <i class="fas fa-archive"></i>
                        </a>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </td>
    </tr>
    <?php endwhile; ?>
    <?php if (mysqli_num_rows($peserta) == 0): ?>
    <tr>
        <td colspan="9" class="text-center">
            <div style="padding: 2rem;">
                <i class="fas fa-users" style="font-size: 3rem; color: #d1d5db; margin-bottom: 1rem;"></i>
                <p>Belum ada data peserta</p>
            </div>
        </td>
    </tr>
    <?php endif; ?>
</tbody>
<div class="pagination">
    <?php if ($page_peserta > 1): ?>
        <a href="?tab=verifikasi&page_peserta=<?= $page_peserta - 1 ?>&search=<?= urlencode($search_peserta) ?>"><i class="fas fa-chevron-left"></i> Sebelumnya</a>
    <?php else: ?>
        <span><i class="fas fa-chevron-left"></i> Sebelumnya</span>
    <?php endif; ?>
    <span>Halaman <?= $page_peserta ?> dari <?= $total_peserta_pages ?></span>
    <?php if ($page_peserta < $total_peserta_pages): ?>
        <a href="?tab=verifikasi&page_peserta=<?= $page_peserta + 1 ?>&search=<?= urlencode($search_peserta) ?>">Selanjutnya <i class="fas fa-chevron-right"></i></a>
    <?php else: ?>
        <span>Selanjutnya <i class="fas fa-chevron-right"></i></span>
    <?php endif; ?>
</div>
<?php
echo ob_get_clean();
?>
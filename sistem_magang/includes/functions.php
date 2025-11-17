<?php
function uploadFoto($file, $peserta_id) {
    $dir = "../uploads/peserta/";
    if (!is_dir($dir)) mkdir($dir, 0777, true);

    $ext = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
    $name = "peserta_{$peserta_id}_" . time() . ".{$ext}";
    $path = $dir . $name;

    $valid = getimagesize($file["tmp_name"]);
    if (!$valid || $file["size"] > 5_000_000 || !in_array($ext, ['jpg','jpeg','png','gif','webp'])) {
        return false;
    }

    return move_uploaded_file($file["tmp_name"], $path) ? $name : false;
}

function pagination($page, $total_pages, $search = '', $tab = '') {
    $html = "<div class='pagination'>";
    $prev = $page > 1 ? "<a href='?tab=$tab&page_peserta=" . ($page - 1) . "&search_peserta=" . urlencode($search) . "'><i class='fas fa-chevron-left'></i> Sebelumnya</a>" : "<span><i class='fas fa-chevron-left'></i> Sebelumnya</span>";
    $next = $page < $total_pages ? "<a href='?tab=$tab&page_peserta=" . ($page + 1) . "&search_peserta=" . urlencode($search) . "'>Selanjutnya <i class='fas fa-chevron-right'></i></a>" : "<span>Selanjutnya <i class='fas fa-chevron-right'></i></span>";
    $html .= "$prev <span>Halaman $page dari $total_pages</span> $next";
    $html .= "</div>";
    return $html;
}
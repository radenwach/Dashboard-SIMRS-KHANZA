<?php
require 'configrs.php'; // Mengimpor koneksi database

// Tangkap data dari form dengan default value
$status = $_GET['status'] ?? '%';
$ruang = $_GET['ruang'] ?? '';
$status_pulang = $_GET['status_pulang'] ?? '';
$tgl_masuk = $_GET['tgl_masuk'] ?? '';
$tgl_keluar = isset($_GET['tgl_keluar']) ? $_GET['tgl_keluar'] : date('Y-m-d');
$search = $_GET['search'] ?? '';

// Query untuk mengambil daftar status pulang
$sql_status_pulang = "SELECT DISTINCT stts_pulang FROM kamar_inap WHERE stts_pulang IS NOT NULL AND stts_pulang <> ''";
$result_status_pulang = $conn->query($sql_status_pulang);
if ($result_status_pulang === false) {
    die("Error dalam query status pulang: " . $conn->error);
}

// Pagination
$limit = 30; // Jumlah data per halaman
$page = isset($_GET['page_no']) ? (int)$_GET['page_no'] : 1;
$page = max($page, 1); // Pastikan $page minimal 1
$offset = ($page - 1) * $limit;

// Query dasar dengan parameterized query
$sql = "
    SELECT 
        reg_periksa.no_rawat, 
        IF(reg_periksa.stts_daftar = 'Lama', reg_periksa.no_rkm_medis, '') AS Lama,
        IF(reg_periksa.stts_daftar = 'Baru', reg_periksa.no_rkm_medis, '') AS Baru,
        pasien.nm_pasien AS Nama_Pasien,
        IF(pasien.jk = 'L', CONCAT(reg_periksa.umurdaftar, ' Th'), '') AS L,
        IF(pasien.jk = 'P', CONCAT(reg_periksa.umurdaftar, ' Th'), '') AS P,
        CONCAT(pasien.alamat, ', ', kelurahan.nm_kel, ', ', kecamatan.nm_kec, ', ', kabupaten.nm_kab) AS Alamat,
        CONCAT(kamar.kd_kamar, ' ', bangsal.nm_bangsal) AS Ruang,
        bangsal.nm_bangsal, 
        kamar_inap.stts_pulang, 
        kamar_inap.tgl_masuk,
        kamar_inap.tgl_keluar,
		kamar_inap.jam_keluar,
        dokter.nm_dokter
    FROM reg_periksa
    INNER JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis
    INNER JOIN kamar_inap ON reg_periksa.no_rawat = kamar_inap.no_rawat
    INNER JOIN kamar ON kamar_inap.kd_kamar = kamar.kd_kamar
    INNER JOIN bangsal ON kamar.kd_bangsal = bangsal.kd_bangsal
    INNER JOIN dokter ON reg_periksa.kd_dokter = dokter.kd_dokter
    INNER JOIN kabupaten ON pasien.kd_kab = kabupaten.kd_kab
    INNER JOIN kecamatan ON pasien.kd_kec = kecamatan.kd_kec
    INNER JOIN kelurahan ON pasien.kd_kel = kelurahan.kd_kel
    WHERE reg_periksa.stts_daftar LIKE ? 
    AND reg_periksa.status_lanjut = 'Ranap'
    AND reg_periksa.stts <> 'Batal'
    AND kamar_inap.stts_pulang <> 'Pindah Kamar'
	AND kamar_inap.tgl_keluar
";

// Tambahkan filter hanya jika ada input dari form
$params = ["s", $status];

if (!empty($ruang)) {
    $sql .= " AND kamar.kd_kamar = ?";
    $params[0] .= "s";
    $params[] = $ruang;
}
if (!empty($status_pulang)) {
    $sql .= " AND kamar_inap.stts_pulang = ?";
    $params[0] .= "s";
    $params[] = $status_pulang;
}
if (!empty($tgl_keluar)) {
    $sql .= " AND DATE(kamar_inap.tgl_keluar) = ?";
    $params[0] .= "s";
    $params[] = $tgl_keluar;
}

if (!empty($search)) {
    $sql .= " AND (pasien.nm_pasien LIKE ? OR pasien.alamat LIKE ? OR dokter.nm_dokter LIKE ? OR kamar.kd_kamar LIKE ? OR bangsal.nm_bangsal LIKE ?)";
    $params[0] .= "sssss";
    $search_param = "%$search%";
    array_push($params, $search_param, $search_param, $search_param, $search_param, $search_param);
}

// Hitung total data
$sql_count = "SELECT COUNT(*) AS total FROM ($sql) AS total_query";
$stmt_count = $conn->prepare($sql_count);
$stmt_count->bind_param(...$params);
$stmt_count->execute();
$result_count = $stmt_count->get_result();
$row_count = $result_count->fetch_assoc();
$total_data = $row_count['total'];
$total_pages = ceil($total_data / $limit);

// Pastikan $page tidak melebihi total halaman
$page = min($page, $total_pages);

// Tambahkan LIMIT dan OFFSET
$sql .= " ORDER BY kamar_inap.tgl_keluar DESC, kamar_inap.jam_keluar DESC LIMIT ? OFFSET ?";

$params[0] .= "ii";
$params[] = $limit;
$params[] = $offset;

// Eksekusi query utama
$stmt = $conn->prepare($sql);
$stmt->bind_param(...$params);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kunjungan Ranap</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .table {
            font-size: 12px;
        }
        .small-text th {
            text-align: center;
        }
    </style>
</head>
<body>
    <main class="app-main">
        <!-- Header -->
        <div class="app-content-header bg-light p-3">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-sm-6">
                        <h3 class="mb-3">Kunjungan Ranap Keluar</h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Form Filter -->
        <div class="container-fluid">
            <form method="GET" action="index.php">
                <input type="hidden" name="page" value="ranapkeluar">
                <div class="row mb-4 align-items-end">
                    <!-- Pilihan Ruang -->
                    <div class="col-md-3">
                        <label for="ruang" class="form-label">Ruang</label>
                        <select class="form-select" id="ruang" name="ruang">
                            <option selected value="">Pilih</option>
                            <?php
                            $sql_ruang = "SELECT kd_kamar, nm_bangsal FROM kamar INNER JOIN bangsal ON kamar.kd_bangsal = bangsal.kd_bangsal ORDER BY nm_bangsal ASC";
                            $result_ruang = $conn->query($sql_ruang);
                            if ($result_ruang === false) {
                                die("Error dalam query ruang: " . $conn->error);
                            }
                            while ($ru = $result_ruang->fetch_assoc()) {
                                $selected = ($ruang == $ru['kd_kamar']) ? 'selected' : '';
                                echo "<option value='{$ru['kd_kamar']}' $selected>{$ru['nm_bangsal']}</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <!-- Pilihan Status Pulang -->
                    <div class="col-md-3">
                        <label for="status_pulang" class="form-label">Status Pulang</label>
                        <select class="form-select" id="status_pulang" name="status_pulang">
                            <option selected value="">Pilih</option>
                            <?php
                            while ($status_row = $result_status_pulang->fetch_assoc()) {
                                $selected = ($status_pulang == $status_row['stts_pulang']) ? 'selected' : '';
                                echo "<option value='{$status_row['stts_pulang']}' $selected>{$status_row['stts_pulang']}</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <!-- Pilihan Tanggal Masuk -->
                    <div class="col-md-3">
                        <label for="tgl_keluar" class="form-label">Tanggal Keluar</label>
                        <input type="date" id="tgl_keluar" name="tgl_keluar" class="form-control"
                            value="<?= htmlspecialchars($tgl_keluar) ?>">
                    </div>

                    <!-- Pencarian -->
                    <div class="col-md-3">
                        <label for="search" class="form-label">Cari</label>
                        <input type="text" id="search" name="search" class="form-control" placeholder="Cari nama, alamat, ruang..."
                            value="<?= htmlspecialchars($search) ?>">
                    </div>

                    <!-- Tombol Cari -->
					<div class="col-md-2 mt-3">
						<button class="btn btn-primary w-100" type="submit">Filter</button>
					</div>
                </div>
            </form>
        </div>

        <!-- Tabel Hasil -->
        <div class="card-body">
            <div class="table-responsive">
                <?php if ($result->num_rows > 0) : ?>
                    <table class="table table-bordered small-text">
                        <thead>
                            <tr class="table-secondary">
                                <th style="width: 1%;">No</th>
                                <th style="width: 3%;">Lama</th>
                                <th style="width: 3%;">Baru</th>
                                <th style="width: 8%;">Nama Pasien</th>
                                <th style="width: 3%;">L</th>
                                <th style="width: 3%;">P</th>
                                <th style="width: 15%;">Alamat</th>
                                <th style="width: 4%;">Kode</th>
                                <th style="width: 15%;">Diagnosa</th>
                                <th style="width: 10%;">Ruang</th>
                                <th style="width: 10%;">Stts. Pulang</th>
                                <th style="width: 7%;">Tgl. Keluar</th>
								<th style="width: 5%;">Jam Keluar</th>
                                <th style="width: 10%;">DPJP</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $no = ($page - 1) * $limit + 1;
                            while ($row = $result->fetch_assoc()) :
                                // Ambil Kode Penyakit & Diagnosa
                                $kode_penyakit = "-";
                                $diagnosa = "-";
                                if (!empty($row['no_rawat'])) {
                                    $query_diag = "
                                        SELECT diagnosa_pasien.kd_penyakit, penyakit.nm_penyakit 
                                        FROM diagnosa_pasien 
                                        INNER JOIN penyakit ON diagnosa_pasien.kd_penyakit = penyakit.kd_penyakit 
                                        WHERE diagnosa_pasien.no_rawat = ? 
                                        ORDER BY prioritas ASC LIMIT 1
                                    ";
                                    $stmt_diag = $conn->prepare($query_diag);
                                    if ($stmt_diag) {
                                        $stmt_diag->bind_param("s", $row['no_rawat']);
                                        $stmt_diag->execute();
                                        $result_diag = $stmt_diag->get_result();
                                        $diag_data = $result_diag->fetch_assoc();
                                        $kode_penyakit = $diag_data['kd_penyakit'] ?? "-";
                                        $diagnosa = $diag_data['nm_penyakit'] ?? "-";
                                    }
                                }
                            ?>
                                <tr class="align-middle">
                                    <td><?= $no++ ?></td>
                                    <td><?= $row['Lama'] ?></td>
                                    <td><?= $row['Baru'] ?></td>
                                    <td><?= $row['Nama_Pasien'] ?></td>
                                    <td><?= $row['L'] ?></td>
                                    <td><?= $row['P'] ?></td>
                                    <td><?= $row['Alamat'] ?></td>
                                    <td><?= $kode_penyakit ?></td>
                                    <td><?= $diagnosa ?></td>
                                    <td><?= $row['Ruang'] ?></td>
                                    <td><?= $row['stts_pulang'] ?></td>
                                    <td><?= $row['tgl_keluar'] ?></td>
									<td><?= $row['jam_keluar'] ?></td>
                                    <td><?= $row['nm_dokter'] ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else : ?>
                    <table class="table table-bordered small-text">
                        <tr>
                            <td colspan="14" class="text-center">Tidak ada data</td>
                        </tr>
                    </table>
                <?php endif; ?>
            </div>

            <!-- Pagination -->
            <nav aria-label="Page navigation">
                <ul class="pagination justify-content-center">
                    <?php
                    $query_string = http_build_query([
                        'page' => 'ranapkeluar',
                        'ruang' => $ruang,
                        'status_pulang' => $status_pulang,
                        'tgl_keluar' => $tgl_keluar,
                        'search' => $search
                    ]);

                    if ($page > 1) :
                    ?>
                        <li class="page-item">
                            <a class="page-link" href="index.php?<?= $query_string ?>&page_no=<?= $page - 1 ?>">Previous</a>
                        </li>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $total_pages; $i++) : ?>
                        <li class="page-item <?= $page == $i ? 'active' : '' ?>">
                            <a class="page-link" href="index.php?<?= $query_string ?>&page_no=<?= $i ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages) : ?>
                        <li class="page-item">
                            <a class="page-link" href="index.php?<?= $query_string ?>&page_no=<?= $page + 1 ?>">Next</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </main>
</body>
</html>
<?php
require 'configrs.php'; // Mengimpor koneksi database

// Tangkap data dari form
$asuransi = $_GET['asuransi'] ?? '';
$poli = $_GET['poli'] ?? '';
$dokter = $_GET['dokter'] ?? '';
$tgl_masuk = isset($_GET['tgl_masuk']) ? $_GET['tgl_masuk'] : date('Y-m-d');

// Pagination
$limit = 20; // Jumlah data per halaman
$page = isset($_GET['page_no']) ? (int)$_GET['page_no'] : 1;
$page = max($page, 1); // Pastikan $page minimal 1

// Hitung offset
$offset = ($page - 1) * $limit;

// Query dasar
$sql = "
SELECT 
    IF(reg_periksa.stts_daftar = 'Lama', reg_periksa.no_rkm_medis, '') AS Lama,
    IF(reg_periksa.stts_daftar = 'Baru', reg_periksa.no_rkm_medis, '') AS Baru,
    pasien.nm_pasien AS Nama_Pasien,
    IF(pasien.jk = 'L', CONCAT(reg_periksa.umurdaftar, ' Th'), '') AS L,
    IF(pasien.jk = 'P', CONCAT(reg_periksa.umurdaftar, ' Th'), '') AS P,
    CONCAT(pasien.alamat, ', ', kelurahan.nm_kel, ', ', kecamatan.nm_kec, ', ', kabupaten.nm_kab) AS Alamat,
	penjab.png_jawab,
    diagnosa_pasien.kd_penyakit AS Kode,
    penyakit.nm_penyakit AS Diagnosa,
    dokter.nm_dokter AS Dokter_Jaga,
    reg_periksa.jam_reg AS Jam_Registrasi, 
    poliklinik.nm_poli AS Nama_Poli
FROM reg_periksa
INNER JOIN penjab ON reg_periksa.kd_pj = penjab.kd_pj
INNER JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis
INNER JOIN dokter ON reg_periksa.kd_dokter = dokter.kd_dokter
INNER JOIN poliklinik ON reg_periksa.kd_poli = poliklinik.kd_poli
LEFT JOIN diagnosa_pasien ON reg_periksa.no_rawat = diagnosa_pasien.no_rawat
LEFT JOIN penyakit ON diagnosa_pasien.kd_penyakit = penyakit.kd_penyakit
LEFT JOIN kelurahan ON pasien.kd_kel = kelurahan.kd_kel
LEFT JOIN kecamatan ON pasien.kd_kec = kecamatan.kd_kec
LEFT JOIN kabupaten ON pasien.kd_kab = kabupaten.kd_kab
WHERE reg_periksa.status_lanjut = 'Ralan' AND tgl_registrasi = CURDATE()";


// Tambahkan filter hanya jika ada input dari form
if (!empty($asuransi)) {
    $sql .= " AND penjab.kd_pj = '$asuransi'";
}
if (!empty($poli)) {
    $sql .= " AND reg_periksa.kd_poli = '$poli'";
}
if (!empty($dokter)) {
    $sql .= " AND reg_periksa.kd_dokter = '$dokter'";
}
if (!empty($start_date)) {
    $sql .= " AND reg_periksa.tgl_registrasi '$tgl_masuk'";
}
$search = $_GET['search'] ?? '';

if (!empty($search)) {
    $sql .= " AND (
        pasien.nm_pasien LIKE '%$search%' OR 
        pasien.alamat LIKE '%$search%' OR 
        dokter.nm_dokter LIKE '%$search%' OR 
        penyakit.nm_penyakit LIKE '%$search%' OR
		diagnosa_pasien.kd_penyakit LIKE '%$search%'
    )";
}


$sql .= " GROUP BY reg_periksa.no_rawat ORDER BY reg_periksa.tgl_registrasi, reg_periksa.jam_reg";

// Query untuk menghitung total data
$sql_count = "SELECT COUNT(*) AS total FROM ($sql) AS total_query";
$result_count = $conn->query($sql_count);

if ($result_count === false) {
    die("Error dalam query count: " . $conn->error); // Tampilkan pesan kesalahan jika query gagal
}

$row_count = $result_count->fetch_assoc();
$total_data = $row_count['total'];
$total_pages = ceil($total_data / $limit);

// Pastikan $page tidak melebihi total halaman
$page = min($page, $total_pages);

// Tambahkan LIMIT dan OFFSET ke query utama
$sql .= " LIMIT $limit OFFSET $offset";

// Eksekusi query utama
$result = $conn->query($sql);

if ($result === false) {
    die("Error dalam query utama: " . $conn->error); // Tampilkan pesan kesalahan jika query gagal
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kunjungan Ralan</title>
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
                        <h3 class="mb-3">Kunjungan Rawat Jalan</h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Form Filter -->
        <div class="container-fluid">
            <form method="GET" action="index.php">
                <input type="hidden" name="page" value="kunjungan">
                <div class="row mb-4 align-items-end">
                    <!-- Pilihan Poli -->
					<div class="col-md-3">
                        <label for="asuransi" class="form-label">Asuransi</label>
                        <select class="form-select" id="asuransi" name="asuransi">
                            <option selected value="">Pilih</option>
                            <?php
                            $sql_asuransi = "SELECT kd_pj, png_jawab FROM penjab";
                            $result_asuransi = $conn->query($sql_asuransi);
                            if ($result_asuransi === false) {
                                die("Error dalam query asuransi: " . $conn->error);
                            }
                            while ($mn = $result_asuransi->fetch_assoc()) {
                                $selected = ($asuransi == $mn['kd_pj']) ? 'selected' : '';
                                echo "<option value='{$mn['kd_pj']}' $selected>{$mn['png_jawab']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="poli" class="form-label">Poli</label>
                        <select class="form-select" id="poli" name="poli">
                            <option selected value="">Pilih</option>
                            <?php
                            $sql_poli = "SELECT kd_poli, nm_poli FROM poliklinik ORDER BY nm_poli ASC";
                            $result_poli = $conn->query($sql_poli);
                            if ($result_poli === false) {
                                die("Error dalam query poli: " . $conn->error);
                            }
                            while ($mn = $result_poli->fetch_assoc()) {
                                $selected = ($poli == $mn['kd_poli']) ? 'selected' : '';
                                echo "<option value='{$mn['kd_poli']}' $selected>{$mn['nm_poli']}</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <!-- Pilihan Dokter -->
                    <div class="col-md-3">
                        <label for="dokter" class="form-label">Dokter</label>
                        <select class="form-select" id="dokter" name="dokter">
                            <option selected value="">Pilih</option>
                            <?php
                            $sql_dokter = "SELECT kd_dokter, nm_dokter FROM dokter ORDER BY nm_dokter ASC";
                            $result_dokter = $conn->query($sql_dokter);
                            if ($result_dokter === false) {
                                die("Error dalam query dokter: " . $conn->error);
                            }
                            while ($dr = $result_dokter->fetch_assoc()) {
                                $selected = ($dokter == $dr['kd_dokter']) ? 'selected' : '';
                                echo "<option value='{$dr['kd_dokter']}' $selected>{$dr['nm_dokter']}</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <!-- Pilihan Tanggal -->
                    <div class="col-md-3">
                        <label for="start_date" class="form-label">Tanggal Masuk</label>
                        <div class="d-flex align-items-center">
                            <input type="date" id="tgl_masuk" name="tgl_masuk" class="form-control me-2"
                                value="<?= htmlspecialchars($tgl_masuk) ?>">
                        </div>
                    </div>
					<div class="col-md-3">
						<label for="search" class="form-label">Cari</label>
						<input type="text" id="search" name="search" class="form-control" placeholder="Cari nama, alamat, diagnosa..."
							value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
					</div>

                    <!-- Tombol Cari -->
                    <div class="col-md-2">
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
                                <th style="width: 4%;">Lama</th>
                                <th style="width: 4%;">Baru</th>
                                <th style="width: 10%;">Nama Pasien</th>
                                <th style="width: 3%;">L</th>
                                <th style="width: 3%;">P</th>
                                <th style="width: 20%;">Alamat</th>
								<th style="width: 6%;">Asuransi</th>
                                <th style="width: 4%;">Kode</th>
                                <th style="width: 12%;">Diagnosa</th>
								<th style="width: 4%;">Jam Registrasi</th>
								<th style="width: 6%;">Poli</th>
                                <th style="width: 10%;">Dokter Jaga</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $no = ($page - 1) * $limit + 1;
                            while ($row = $result->fetch_assoc()) :
                            ?>
                                <tr class="align-middle">
                                    <td><?= $no++ ?></td>
                                    <td><?= $row['Lama'] ?></td>
                                    <td><?= $row['Baru'] ?></td>
                                    <td><?= $row['Nama_Pasien'] ?></td>
                                    <td><?= $row['L'] ?></td>
                                    <td><?= $row['P'] ?></td>
                                    <td><?= $row['Alamat'] ?></td>
									<td><?= $row['png_jawab'] ?></td>
                                    <td><?= $row['Kode'] ?></td>
                                    <td><?= $row['Diagnosa'] ?></td>
									<td><?= $row['Jam_Registrasi'] ?></td>
									<td><?= $row['Nama_Poli'] ?></td>
                                    <td><?= $row['Dokter_Jaga'] ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else : ?>
                    <table class="table table-bordered small-text">
                        <tr>
                            <td colspan="10" class="text-center">Tidak ada data</td>
                        </tr>
                    </table>
                <?php endif; ?>
            </div>

            <!-- Pagination -->
            <nav aria-label="Page navigation">
                <ul class="pagination justify-content-center">
                    <?php
						$query_string = http_build_query([
							'page' => 'kunjungan',
							'asuransi' => $asuransi,
							'poli' => $poli,
							'dokter' => $dokter,
							'tgl_masuk' => $tgl_masuk,
							'search' => $search // Tambahkan parameter pencarian
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
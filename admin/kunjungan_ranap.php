<?php
require 'configrs.php'; // Mengimpor koneksi database

// Tangkap data dari form
$asuransi = $_GET['asuransi'] ?? '';
$dokter = $_GET['dokter'] ?? '';
$search = $_GET['search'] ?? '';

// Pagination
$limit = 20; // Jumlah data per halaman
$page = isset($_GET['page_no']) ? (int)$_GET['page_no'] : 1;
$page = max($page, 1); // Pastikan $page minimal 1

// Hitung offset
$offset = ($page - 1) * $limit;

// Query dasar
$sql = "SELECT kamar_inap.no_rawat, reg_periksa.no_rkm_medis, pasien.nm_pasien,  
               CONCAT(pasien.alamat, ', ', kelurahan.nm_kel, ', ', kecamatan.nm_kec, ', ', kabupaten.nm_kab) AS alamat,
               penjab.png_jawab, CONCAT(kamar_inap.kd_kamar, ' ', bangsal.nm_bangsal) AS kamar,
               kamar_inap.diagnosa_awal, kamar_inap.diagnosa_akhir, 
               kamar_inap.tgl_masuk, kamar_inap.jam_masuk, kamar_inap.tgl_keluar, kamar_inap.jam_keluar,
               kamar_inap.stts_pulang, 
               IF(kamar_inap.stts_pulang='Pindah Kamar', 
                  (IFNULL(DATEDIFF(kamar_inap.tgl_keluar, kamar_inap.tgl_masuk), DATEDIFF(NOW(), kamar_inap.tgl_masuk))),
                  (IFNULL(DATEDIFF(kamar_inap.tgl_keluar, kamar_inap.tgl_masuk), DATEDIFF(NOW(), kamar_inap.tgl_masuk)) + 1)) AS lama,
               dokter.nm_dokter
        FROM kamar_inap 
        INNER JOIN reg_periksa ON kamar_inap.no_rawat = reg_periksa.no_rawat
        INNER JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis
        INNER JOIN kamar ON kamar_inap.kd_kamar = kamar.kd_kamar
        INNER JOIN bangsal ON kamar.kd_bangsal = bangsal.kd_bangsal
        INNER JOIN kelurahan ON pasien.kd_kel = kelurahan.kd_kel
        INNER JOIN kecamatan ON pasien.kd_kec = kecamatan.kd_kec
        INNER JOIN kabupaten ON pasien.kd_kab = kabupaten.kd_kab
        INNER JOIN dokter ON reg_periksa.kd_dokter = dokter.kd_dokter
        INNER JOIN penjab ON reg_periksa.kd_pj = penjab.kd_pj
        WHERE kamar_inap.tgl_masuk >= '2025-02-01'
        AND (kamar_inap.tgl_masuk IS NOT NULL AND kamar_inap.tgl_keluar = '0000-00-00')";

// Tambahkan filter hanya jika ada input dari form
if (!empty($asuransi)) {
    $sql .= " AND penjab.kd_pj = '$asuransi'";
}
if (!empty($dokter)) {
    $sql .= " AND reg_periksa.kd_dokter = '$dokter'";
}
if (!empty($search)) {
    $sql .= " AND (
        pasien.nm_pasien LIKE '%$search%' OR 
        pasien.alamat LIKE '%$search%' OR 
        dokter.nm_dokter LIKE '%$search%' OR 
        bangsal.nm_bangsal LIKE '%$search%'
    )";
}

// Query untuk menghitung total data
$sql_count = "SELECT COUNT(*) AS total FROM ($sql) AS total_query";
$result_count = $conn->query($sql_count);

if ($result_count === false) {
    die("Error dalam query count: " . $conn->error);
}

$row_count = $result_count->fetch_assoc();
$total_data = $row_count['total'];
$total_pages = ceil($total_data / $limit);

// Tambahkan LIMIT dan OFFSET ke query utama
$sql .= " ORDER BY kamar_inap.tgl_masuk DESC, kamar_inap.jam_masuk DESC";
$sql .= " LIMIT $limit OFFSET $offset";

// Eksekusi query utama
$result = $conn->query($sql);

if ($result === false) {
    die("Error dalam query utama: " . $conn->error);
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
                        <h3 class="mb-3">Kunjungan Rawat Inap</h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Form Filter -->
        <div class="container-fluid">
            <form method="GET" action="index.php">
                <input type="hidden" name="page" value="ranap">
                <div class="row mb-4 align-items-end">
                    <!-- Pilihan Asuransi -->
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

                    <!-- Pencarian -->
                    <div class="col-md-3">
                        <label for="search" class="form-label">Cari</label>
                        <input type="text" id="search" name="search" class="form-control" placeholder="Cari nama, alamat, kamar..."
                            value="<?= htmlspecialchars($search) ?>">
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
                                <th>No.</th>
                                <th>No. Rawat</th>
                                <th>No. RM</th>
                                <th>Nama Pasien</th>
                                <th>Alamat Pasien</th>
                                <th>Asuransi</th>
                                <th>Kamar</th>
                                <th>Diagnosa Awal</th>
                                <th>Tgl. Masuk</th>
                                <th>Jam Masuk</th>
                                <th>Lama Rawat</th>
                                <th>Dokter P.J.</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $no = ($page - 1) * $limit + 1;
                            while ($row = $result->fetch_assoc()) :
                            ?>
                                <tr class="align-middle">
                                    <td><?= $no++ ?></td>
                                    <td><?= $row['no_rawat'] ?></td>
                                    <td><?= $row['no_rkm_medis'] ?></td>
                                    <td><?= $row['nm_pasien'] ?></td>
                                    <td><?= $row['alamat'] ?></td>
                                    <td><?= $row['png_jawab'] ?></td>
                                    <td><?= $row['kamar'] ?></td>
                                    <td><?= $row['diagnosa_awal'] ?></td>
                                    <td><?= $row['tgl_masuk'] ?></td>
                                    <td><?= $row['jam_masuk'] ?></td>
                                    <td><?= $row['lama'] ?></td>
                                    <td><?= $row['nm_dokter'] ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else : ?>
                    <table class="table table-bordered small-text">
                        <tr>
                            <td colspan="12" class="text-center">Tidak ada data</td>
                        </tr>
                    </table>
                <?php endif; ?>
            </div>

            <!-- Pagination -->
            <nav aria-label="Page navigation">
                <ul class="pagination justify-content-center">
                    <?php
                    $query_string = http_build_query([
                        'page' => 'ranap',
                        'asuransi' => $asuransi,
                        'dokter' => $dokter,
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
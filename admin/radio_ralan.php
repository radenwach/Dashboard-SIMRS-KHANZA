<?php
require 'configrs.php'; // Mengimpor koneksi database

// Tangkap data dari form
$jenis_pemeriksaan = $_GET['jenis_pemeriksaan'] ?? '';
$dokter_perujuk = $_GET['dokter_perujuk'] ?? '';
$search = $_GET['search'] ?? '';

// Pagination
$limit = 10; // Jumlah data per halaman
$page = isset($_GET['page_no']) ? (int)$_GET['page_no'] : 1;
$page = max($page, 1); // Pastikan $page minimal 1

// Hitung offset
$offset = ($page - 1) * $limit;

// Query dasar
$sql = "SELECT 
    periksa_radiologi.no_rawat,
    CONCAT(periksa_radiologi.tgl_periksa, ' ', periksa_radiologi.jam) AS tgl_jam,
    periksa_radiologi.no_rawat AS no_radiologi,
    reg_periksa.no_rkm_medis,
    pasien.nm_pasien,
    pasien.jk,
    CONCAT(reg_periksa.umurdaftar, 'Th') AS umur,
    CONCAT(pasien.alamat, ', ', kelurahan.nm_kel, ', ', kecamatan.nm_kec, ', ', kabupaten.nm_kab) AS alamat,
    IFNULL(jns_perawatan_radiologi.nm_perawatan, '-') AS pemeriksaan,
    dokter.nm_dokter AS dokter_perujuk
FROM periksa_radiologi
INNER JOIN reg_periksa ON periksa_radiologi.no_rawat = reg_periksa.no_rawat
INNER JOIN dokter ON periksa_radiologi.dokter_perujuk = dokter.kd_dokter
INNER JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis
INNER JOIN kabupaten ON pasien.kd_kab = kabupaten.kd_kab
INNER JOIN kecamatan ON pasien.kd_kec = kecamatan.kd_kec
INNER JOIN kelurahan ON pasien.kd_kel = kelurahan.kd_kel
LEFT JOIN jns_perawatan_radiologi ON periksa_radiologi.kd_jenis_prw = jns_perawatan_radiologi.kd_jenis_prw
WHERE periksa_radiologi.status = 'Ralan' AND tgl_periksa = CURDATE()
";

// Tambahkan filter hanya jika ada input dari form
if (!empty($jenis_pemeriksaan)) {
    $sql .= " AND jns_perawatan_radiologi.kd_jenis_prw = '$jenis_pemeriksaan'";
}
if (!empty($dokter_perujuk)) {
    $sql .= " AND periksa_radiologi.dokter_perujuk = '$dokter_perujuk'";
}
if (!empty($search)) {
    $sql .= " AND (
        pasien.nm_pasien LIKE '%$search%' OR 
        pasien.alamat LIKE '%$search%' OR 
        dokter.nm_dokter LIKE '%$search%'
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
$sql .= " ORDER BY periksa_radiologi.tgl_periksa, periksa_radiologi.jam DESC
          LIMIT $limit OFFSET $offset";

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
    <title>Data Pemeriksaan Radiologi</title>
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
                        <h3 class="mb-3">Pemeriksaan Radiologi (Ralan)</h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Form Filter -->
        <div class="container-fluid">
            <form method="GET" action="index.php">
                <input type="hidden" name="page" value="radioralan">
                <div class="row mb-4 align-items-end">
                    <!-- Pilihan Jenis Pemeriksaan -->
                    <div class="col-md-3">
                        <label for="jenis_pemeriksaan" class="form-label">Jenis Pemeriksaan</label>
                        <select class="form-select" id="jenis_pemeriksaan" name="jenis_pemeriksaan">
                            <option selected value="">Pilih</option>
                            <?php
                            $sql_jenis_pemeriksaan = "SELECT kd_jenis_prw, nm_perawatan FROM jns_perawatan_radiologi";
                            $result_jenis_pemeriksaan = $conn->query($sql_jenis_pemeriksaan);
                            if ($result_jenis_pemeriksaan === false) {
                                die("Error dalam query jenis pemeriksaan: " . $conn->error);
                            }
                            while ($jp = $result_jenis_pemeriksaan->fetch_assoc()) {
                                $selected = ($jenis_pemeriksaan == $jp['kd_jenis_prw']) ? 'selected' : '';
                                echo "<option value='{$jp['kd_jenis_prw']}' $selected>{$jp['nm_perawatan']}</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <!-- Pilihan Dokter Perujuk -->
                    <div class="col-md-3">
                        <label for="dokter_perujuk" class="form-label">Dokter Perujuk</label>
                        <select class="form-select" id="dokter_perujuk" name="dokter_perujuk">
                            <option selected value="">Pilih</option>
                            <?php
                            $sql_dokter_perujuk = "SELECT kd_dokter, nm_dokter FROM dokter ORDER BY nm_dokter ASC";
                            $result_dokter_perujuk = $conn->query($sql_dokter_perujuk);
                            if ($result_dokter_perujuk === false) {
                                die("Error dalam query dokter perujuk: " . $conn->error);
                            }
                            while ($dp = $result_dokter_perujuk->fetch_assoc()) {
                                $selected = ($dokter_perujuk == $dp['kd_dokter']) ? 'selected' : '';
                                echo "<option value='{$dp['kd_dokter']}' $selected>{$dp['nm_dokter']}</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <!-- Pencarian -->
                    <div class="col-md-3">
                        <label for="search" class="form-label">Cari</label>
                        <input type="text" id="search" name="search" class="form-control" placeholder="Cari nama, alamat, dokter..."
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
                                <th>Tanggal & Jam</th>
                                <th>No. Radiologi</th>
                                <th>No.RM</th>
                                <th>Nama Pasien</th>
                                <th>L</th>
                                <th>P</th>
                                <th>Alamat</th>
                                <th>Pemeriksaan</th>
                                <th>Dokter Perujuk/Pengirim</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $no = ($page - 1) * $limit + 1;
                            while ($row = $result->fetch_assoc()) :
                            ?>
                                <tr class="align-middle">
                                    <td><?= $no++ ?></td>
                                    <td><?= $row['tgl_jam'] ?></td>
                                    <td><?= $row['no_radiologi'] ?></td>
                                    <td><?= $row['no_rkm_medis'] ?></td>
                                    <td><?= $row['nm_pasien'] ?></td>
                                    <td><?= ($row['jk'] == 'L' ? $row['umur'] : '') ?></td>
                                    <td><?= ($row['jk'] == 'P' ? $row['umur'] : '') ?></td>
                                    <td><?= $row['alamat'] ?></td>
                                    <td><?= $row['pemeriksaan'] ?></td>
                                    <td><?= $row['dokter_perujuk'] ?></td>
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
                        'page' => 'radioralan',
                        'jenis_pemeriksaan' => $jenis_pemeriksaan,
                        'dokter_perujuk' => $dokter_perujuk,
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
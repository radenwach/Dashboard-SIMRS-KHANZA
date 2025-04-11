<?php
require 'configrs.php'; // Mengimpor koneksi database

$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';

$where_clause = "AND tgl_registrasi = CURDATE()";

if (!empty($start_date) && !empty($end_date)) {
    $where_clause = "AND tgl_registrasi BETWEEN '$start_date' AND '$end_date'";
}

// Query untuk jumlah kunjungan hari ini
$tanggal_hari_ini = date('Y-m-d');
$query_kunjungan_hari_ini = "
    SELECT COUNT(*) AS jumlah_kunjungan 
    FROM reg_periksa 
    WHERE status_lanjut = 'Ralan'
    AND tgl_registrasi = CURDATE()
";
$result_kunjungan_hari_ini = $conn->query($query_kunjungan_hari_ini);
$jumlah_kunjungan_hari_ini = ($result_kunjungan_hari_ini) ? $result_kunjungan_hari_ini->fetch_assoc()['jumlah_kunjungan'] : 0;

// Query untuk distribusi usia pasien
$query_usia = "
    SELECT 
        CASE 
            WHEN TIMESTAMPDIFF(YEAR, tgl_lahir, CURDATE()) BETWEEN 0 AND 10 THEN '0-10 Tahun'
            WHEN TIMESTAMPDIFF(YEAR, tgl_lahir, CURDATE()) BETWEEN 11 AND 20 THEN '11-20 Tahun'
            WHEN TIMESTAMPDIFF(YEAR, tgl_lahir, CURDATE()) BETWEEN 21 AND 30 THEN '21-30 Tahun'
            WHEN TIMESTAMPDIFF(YEAR, tgl_lahir, CURDATE()) BETWEEN 31 AND 40 THEN '31-40 Tahun'
            WHEN TIMESTAMPDIFF(YEAR, tgl_lahir, CURDATE()) BETWEEN 41 AND 50 THEN '41-50 Tahun'
            WHEN TIMESTAMPDIFF(YEAR, tgl_lahir, CURDATE()) BETWEEN 51 AND 60 THEN '51-60 Tahun'
            WHEN TIMESTAMPDIFF(YEAR, tgl_lahir, CURDATE()) BETWEEN 61 AND 70 THEN '61-70 Tahun'
            WHEN TIMESTAMPDIFF(YEAR, tgl_lahir, CURDATE()) BETWEEN 71 AND 80 THEN '71-80 Tahun'
            ELSE '>80 Tahun ' 
        END AS kategori_usia,
        COUNT(*) AS jumlah
    FROM reg_periksa
    JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis
    WHERE reg_periksa.status_lanjut = 'Ralan' AND tgl_registrasi = CURDATE()
    GROUP BY kategori_usia
    ORDER BY kategori_usia
";
$result_usia = $conn->query($query_usia);
$usia_labels = [];
$usia_data = [];
while ($row = $result_usia->fetch_assoc()) {
    $usia_labels[] = $row['kategori_usia'];
    $usia_data[] = $row['jumlah'];
}
// Query untuk distribusi kunjungan berdasarkan poliklinik
$query_poli = "
    SELECT poliklinik.nm_poli AS nama_poli, COUNT(reg_periksa.no_rawat) AS jumlah_kunjungan
    FROM reg_periksa
    JOIN poliklinik ON reg_periksa.kd_poli = poliklinik.kd_poli
    WHERE reg_periksa.status_lanjut = 'Ralan' AND tgl_registrasi = CURDATE()
    GROUP BY reg_periksa.kd_poli
	ORDER BY jumlah_kunjungan DESC";
$result_poli = $conn->query($query_poli);
$poli_labels = [];
$poli_data = [];
while ($row = $result_poli->fetch_assoc()) {
    $poli_labels[] = $row['nama_poli'];
    $poli_data[] = $row['jumlah_kunjungan'];
}

// Pasien Masuk Hari Ini
$sql_masuk = "SELECT COUNT(*) AS total_masuk FROM kamar_inap WHERE tgl_masuk = CURDATE()";
$result_masuk = $conn->query($sql_masuk);
$data_masuk = $result_masuk->fetch_assoc();

// Pasien Keluar Hari Ini
$sql_keluar = "SELECT COUNT(*) AS total_keluar FROM kamar_inap WHERE tgl_keluar = CURDATE()";
$result_keluar = $conn->query($sql_keluar);
$data_keluar = $result_keluar->fetch_assoc();

$sql_dirawat = "SELECT COUNT(*) AS total_dirawat 
FROM kamar_inap 
WHERE (tgl_keluar IS NULL OR tgl_keluar = '0000-00-00' OR tgl_keluar = '') 
AND (stts_pulang IS NULL OR stts_pulang = '-' OR stts_pulang = '' OR stts_pulang = 'Pindah Kamar') 
AND tgl_masuk >= '2025-02-01'";

				
$result_dirawat = $conn->query($sql_dirawat);
$data_dirawat = $result_dirawat->fetch_assoc();
$sql_ranap = "
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
	AND tgl_registrasi = CURDATE()
";
// Query Harian (Per Jam Hari Ini)
$harian_data = array_fill_keys(range(0, 23), 0);
$query_harian = "SELECT HOUR(jam_reg) as jam, COUNT(*) as jumlah FROM reg_periksa WHERE status_lanjut = 'Ralan' AND DATE(tgl_registrasi) = CURDATE() GROUP BY HOUR(jam_reg)";
$result_harian = $conn->query($query_harian);
while ($row = $result_harian->fetch_assoc()) {
    $harian_data[$row['jam']] = $row['jumlah'];
}
// Query Mingguan (7 Hari Terakhir)
$query_mingguan = "SELECT DATE(tgl_registrasi) as hari, COUNT(*) as jumlah 
    FROM reg_periksa 
    WHERE status_lanjut = 'Ralan' 
    AND tgl_registrasi BETWEEN DATE_SUB(CURDATE(), INTERVAL 6 DAY) AND CURDATE()
    GROUP BY DATE(tgl_registrasi)";

$result_mingguan = $conn->query($query_mingguan);
$mingguan_data = $result_mingguan->fetch_all(MYSQLI_ASSOC);

// Query Bulanan (Per Minggu Dalam 1 Bulan)
$query_bulanan = "SELECT WEEK(tgl_registrasi, 1) as minggu, COUNT(*) as jumlah 
    FROM reg_periksa 
    WHERE status_lanjut = 'Ralan' 
    AND tgl_registrasi BETWEEN DATE_SUB(CURDATE(), INTERVAL 28 DAY) AND CURDATE()
    GROUP BY WEEK(tgl_registrasi, 1)";

$result_bulanan = $conn->query($query_bulanan);
$bulanan_data = $result_bulanan->fetch_all(MYSQLI_ASSOC);

// Query Harian Ranap (Per Jam Hari Ini)
$harian_ranap = array_fill_keys(range(0, 23), 0);
$query_harian_ranap = "SELECT HOUR(jam_reg) as jam, COUNT(*) as jumlah FROM reg_periksa WHERE status_lanjut = 'Ranap' AND DATE(tgl_registrasi) = CURDATE() GROUP BY HOUR(jam_reg)";
$result_harian_ranap = $conn->query($query_harian_ranap);
while ($row = $result_harian_ranap->fetch_assoc()) {
    $harian_ranap[$row['jam']] = $row['jumlah'];
}
// Query Mingguan Ranap (7 Hari Terakhir)
$query_mingguan_ranap = "SELECT DATE(tgl_registrasi) as hari, COUNT(*) as jumlah FROM reg_periksa WHERE status_lanjut = 'Ranap' AND tgl_registrasi >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) GROUP BY DATE(tgl_registrasi)";
$result_mingguan_ranap = $conn->query($query_mingguan_ranap);
$mingguan_ranap = $result_mingguan_ranap->fetch_all(MYSQLI_ASSOC);

// Query Bulanan Ranap (Per Minggu Dalam 1 Bulan)
$query_bulanan_ranap = "SELECT WEEK(tgl_registrasi, 1) as minggu, COUNT(*) as jumlah FROM reg_periksa WHERE status_lanjut = 'Ranap' AND tgl_registrasi >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH) GROUP BY WEEK(tgl_registrasi, 1)";
$result_bulanan_ranap = $conn->query($query_bulanan_ranap);
$bulanan_ranap = $result_bulanan_ranap->fetch_all(MYSQLI_ASSOC);

$query_penyakit_ralan = "
    SELECT diagnosa_pasien.kd_penyakit, penyakit.nm_penyakit, COUNT(*) AS jumlah
    FROM diagnosa_pasien
    JOIN penyakit ON diagnosa_pasien.kd_penyakit = penyakit.kd_penyakit
    JOIN reg_periksa ON diagnosa_pasien.no_rawat = reg_periksa.no_rawat
    WHERE reg_periksa.status_lanjut = 'Ralan' AND tgl_registrasi = CURDATE()
    GROUP BY diagnosa_pasien.kd_penyakit
    ORDER BY jumlah DESC LIMIT 10
";
$result_penyakit_ralan = $conn->query($query_penyakit_ralan);
$penyakit_ralan_labels = [];
$penyakit_ralan_data = [];
while ($row = $result_penyakit_ralan->fetch_assoc()) {
    $penyakit_ralan_labels[] = $row['nm_penyakit'];
    $penyakit_ralan_data[] = $row['jumlah'];
}

$query_penyakit_ranap = "
    SELECT diagnosa_pasien.kd_penyakit, penyakit.nm_penyakit, COUNT(*) AS jumlah
    FROM diagnosa_pasien
    JOIN penyakit ON diagnosa_pasien.kd_penyakit = penyakit.kd_penyakit
    JOIN reg_periksa ON diagnosa_pasien.no_rawat = reg_periksa.no_rawat
    JOIN kamar_inap ON diagnosa_pasien.no_rawat = kamar_inap.no_rawat
    WHERE reg_periksa.status_lanjut = 'Ranap'
    AND kamar_inap.tgl_keluar = '0000-00-00'  -- Hanya pasien yang belum pulang
    GROUP BY diagnosa_pasien.kd_penyakit
    ORDER BY jumlah DESC LIMIT 10
";
$result_penyakit_ranap = $conn->query($query_penyakit_ranap);
$penyakit_ranap_labels = [];
$penyakit_ranap_data = [];
while ($row = $result_penyakit_ranap->fetch_assoc()) {
    $penyakit_ranap_labels[] = $row['nm_penyakit'];
    $penyakit_ranap_data[] = $row['jumlah'];
}


// Query SQL untuk pasien Baru vs Lama - Ranap
$sql_ranap_status = "
    SELECT reg_periksa.stts_daftar AS status, COUNT(*) AS total
    FROM reg_periksa
    INNER JOIN kamar_inap ON reg_periksa.no_rawat = kamar_inap.no_rawat
    WHERE (tgl_keluar IS NULL OR tgl_keluar = '0000-00-00' OR tgl_keluar = '') AND (stts_pulang IS NULL OR stts_pulang = '-' OR stts_pulang = '' OR stts_pulang = 'Pindah Kamar') 
AND tgl_masuk >= '2025-02-01'
    GROUP BY reg_periksa.stts_daftar
";

$result_ranap_status = $conn->query($sql_ranap_status);
$labels_ranap_status = [];
$data_ranap_status = [];

while ($row = $result_ranap_status->fetch_assoc()) {
    $labels_ranap_status[] = $row['status'];
    $data_ranap_status[] = $row['total'];
}

// Query SQL untuk pasien Baru vs Lama - Ralan
$sql_ralan_status = "
    SELECT reg_periksa.stts_daftar AS status, COUNT(*) AS total
    FROM reg_periksa
    WHERE reg_periksa.status_lanjut = 'Ralan'
    AND tgl_registrasi = CURDATE()
    GROUP BY reg_periksa.stts_daftar
";

$result_ralan_status = $conn->query($sql_ralan_status);
$labels_ralan_status = [];
$data_ralan_status = [];

while ($row = $result_ralan_status->fetch_assoc()) {
    $labels_ralan_status[] = $row['status'];
    $data_ralan_status[] = $row['total'];
}

// Query SQL untuk pasien Asuransi - Ranap
$sql_ranap_asuransi = "
    SELECT penjab.png_jawab AS asuransi, COUNT(*) AS total
    FROM kamar_inap 
    INNER JOIN reg_periksa ON kamar_inap.no_rawat = reg_periksa.no_rawat
    INNER JOIN penjab ON reg_periksa.kd_pj = penjab.kd_pj
    WHERE (tgl_keluar IS NULL OR tgl_keluar = '0000-00-00' OR tgl_keluar = '') AND (stts_pulang IS NULL OR stts_pulang = '-' OR stts_pulang = '' OR stts_pulang = 'Pindah Kamar') 
AND tgl_masuk >= '2025-02-01'
    GROUP BY penjab.png_jawab
";

$result_ranap_asuransi = $conn->query($sql_ranap_asuransi);
$labels_ranap_asuransi = [];
$data_ranap_asuransi = [];

while ($row = $result_ranap_asuransi->fetch_assoc()) {
    $labels_ranap_asuransi[] = $row['asuransi'];
    $data_ranap_asuransi[] = $row['total'];
}

// Query SQL untuk pasien Asuransi - Ralan
$sql_ralan_asuransi = "
    SELECT penjab.png_jawab AS asuransi, COUNT(*) AS total
    FROM reg_periksa
    INNER JOIN penjab ON reg_periksa.kd_pj = penjab.kd_pj
    WHERE reg_periksa.status_lanjut = 'Ralan'
    AND tgl_registrasi = CURDATE()
    GROUP BY penjab.png_jawab
";

$result_ralan_asuransi = $conn->query($sql_ralan_asuransi);
$labels_ralan_asuransi = [];
$data_ralan_asuransi = [];

while ($row = $result_ralan_asuransi->fetch_assoc()) {
    $labels_ralan_asuransi[] = $row['asuransi'];
    $data_ralan_asuransi[] = $row['total'];
}

// Query untuk mengambil data pemeriksaan Ralan
$sql_ralan = "SELECT jns_perawatan_lab.nm_perawatan AS jenis_pemeriksaan, COUNT(*) AS jumlah
              FROM periksa_lab
              INNER JOIN jns_perawatan_lab ON periksa_lab.kd_jenis_prw = jns_perawatan_lab.kd_jenis_prw
              WHERE periksa_lab.status = 'Ralan' AND periksa_lab.tgl_periksa = CURDATE()
              GROUP BY jns_perawatan_lab.nm_perawatan
			  ORDER BY jumlah DESC LIMIT 10";
$result_ralan = $conn->query($sql_ralan);

// Query untuk mengambil data pemeriksaan Ranap
$sql_ranap = "SELECT jns_perawatan_lab.nm_perawatan AS jenis_pemeriksaan, COUNT(*) AS jumlah
              FROM periksa_lab
              INNER JOIN jns_perawatan_lab ON periksa_lab.kd_jenis_prw = jns_perawatan_lab.kd_jenis_prw
              WHERE periksa_lab.status = 'Ranap' AND periksa_lab.tgl_periksa = CURDATE()
              GROUP BY jns_perawatan_lab.nm_perawatan
			  ORDER BY jumlah DESC LIMIT 10";
$result_ranap = $conn->query($sql_ranap);

$labels_ralan = [];
$data_ralan = [];

$labels_ranap = [];
$data_ranap = [];

// Ambil data Ralan
while ($row = $result_ralan->fetch_assoc()) {
    $labels_ralan[] = $row['jenis_pemeriksaan'];
    $data_ralan[] = $row['jumlah'];
	$total_ralan = array_sum($data_ralan);
}

// Ambil data Ranap
while ($row = $result_ranap->fetch_assoc()) {
    $labels_ranap[] = $row['jenis_pemeriksaan'];
    $data_ranap[] = $row['jumlah'];
	$total_ranap = array_sum($data_ranap);
}

// Query untuk mengambil data radiologi Ralan
$radio_ralan_query = "SELECT jns_perawatan_radiologi.nm_perawatan AS jenis_pemeriksaan, COUNT(*) AS jumlah
                      FROM periksa_radiologi
                      LEFT JOIN jns_perawatan_radiologi ON periksa_radiologi.kd_jenis_prw = jns_perawatan_radiologi.kd_jenis_prw
                      WHERE periksa_radiologi.status = 'Ralan' AND periksa_radiologi.tgl_periksa = CURDATE()
                      GROUP BY jns_perawatan_radiologi.nm_perawatan
					  ORDER BY jumlah DESC";
$result_radio_ralan = $conn->query($radio_ralan_query);

// Query untuk mengambil data radiologi Ranap
$radio_ranap_query = "SELECT jns_perawatan_radiologi.nm_perawatan AS jenis_pemeriksaan, COUNT(*) AS jumlah
                      FROM periksa_radiologi
                      LEFT JOIN jns_perawatan_radiologi ON periksa_radiologi.kd_jenis_prw = jns_perawatan_radiologi.kd_jenis_prw
                      WHERE periksa_radiologi.status = 'Ranap' AND periksa_radiologi.tgl_periksa = CURDATE()
                      GROUP BY jns_perawatan_radiologi.nm_perawatan
					  ORDER BY jumlah DESC";
$result_radio_ranap = $conn->query($radio_ranap_query);

$labels_radio_ralan = [];
$data_radio_ralan = [];

$labels_radio_ranap = [];
$data_radio_ranap = [];

// Ambil data Radiologi Ralan
$total_radio_ranap = 0; // Inisialisasi agar tidak undefined
while ($row = $result_radio_ralan->fetch_assoc()) {
    $labels_radio_ralan[] = $row['jenis_pemeriksaan'] ?: 'Tidak Diketahui';
    $data_radio_ralan[] = $row['jumlah'];
	$total_radio_ralan = array_sum($data_radio_ralan);
}

// Ambil data Radiologi Ranap
$total_radio_ranap = 0; // Inisialisasi agar tidak undefined
while ($row = $result_radio_ranap->fetch_assoc()) {
    $labels_radio_ranap[] = $row['jenis_pemeriksaan'] ?: 'Tidak Diketahui';
    $data_radio_ranap[] = $row['jumlah'];
	$total_radio_ranap = array_sum($data_radio_ranap);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
	<main class="app-main">
        <!-- Header -->
        <div class="app-content-header bg-light p-3">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-sm-6">
                        <h3 class="mb-0">Dashboard Pasien</h3>
                    </div>
				</div>
			</div>
        <!-- Content -->
        <div class="app-content p-3">
            <div class="container-fluid">
                <!-- Row 1: Statistik Ralan -->
				<div class="row">
					<!-- Jumlah Pasien Ralan Hari Ini -->
					<div class="col-lg-3 col-6">
						<div class="info-box"> 
							<span class="info-box-icon text-bg-primary shadow-sm"> 
								<ion-icon name="people"></ion-icon>
							</span>
							<div class="info-box-content"> 
								<span class="info-box-text">Pasien Ralan Hari Ini</span> 
								<span class="info-box-number">
									<h3><?php echo $jumlah_kunjungan_hari_ini; ?> Pasien</h3>
								</span> 
							</div>
						</div>
					</div>

					<!-- Jumlah Pasien Ranap Hari Ini -->
					<div class="col-lg-3 col-6">
						<div class="info-box"> 
							<span class="info-box-icon text-bg-primary shadow-sm"> 
								<ion-icon name="bed"></ion-icon>
							</span>
							<div class="info-box-content"> 
								<span class="info-box-text">Pasien Ranap Hari Ini</span> 
								<span class="info-box-number">
									<h3><?php echo $data_dirawat['total_dirawat']; ?> Pasien</h3>
								</span> 
							</div>
						</div>
					</div>

					<!-- Jumlah Pasien Ranap Masuk -->
					<div class="col-lg-3 col-6">
						<div class="info-box"> 
							<span class="info-box-icon text-bg-primary shadow-sm"> 
								<ion-icon name="log-in"></ion-icon>
							</span>
							<div class="info-box-content"> 
								<span class="info-box-text">Pasien Ranap Masuk</span> 
								<span class="info-box-number">
									<h3><?php echo $data_masuk['total_masuk']; ?> Pasien</h3>
								</span> 
							</div>
						</div>
					</div>

					<!-- Jumlah Pasien Ranap Keluar -->
					<div class="col-lg-3 col-6">
						<div class="info-box"> 
							<span class="info-box-icon text-bg-primary shadow-sm"> 
								<ion-icon name="log-out"></ion-icon>
							</span>
							<div class="info-box-content"> 
								<span class="info-box-text">Pasien Ranap Keluar</span> 
								<span class="info-box-number">
									<h3><?php echo $data_keluar['total_keluar']; ?> Pasien</h3>
								</span> 
							</div>
						</div>
					</div>

					<!-- Pemeriksaan Lab Ralan -->
					<div class="col-lg-3 col-6">
						<div class="info-box"> 
							<span class="info-box-icon text-bg-success shadow-sm"> 
								<ion-icon name="flask"></ion-icon>
							</span>
							<div class="info-box-content"> 
								<span class="info-box-text">Laboratorium Ralan</span> 
								<span class="info-box-number">
									<h3 style ="font-size: 22px;"><?php echo $total_ralan; ?> Pemeriksaan</h3>
								</span> 
							</div>
						</div>
					</div>

					<!-- Pemeriksaan Lab Ranap -->
					<div class="col-lg-3 col-6">
						<div class="info-box"> 
							<span class="info-box-icon text-bg-success shadow-sm"> 
								<ion-icon name="flask-outline"></ion-icon>
							</span>
							<div class="info-box-content"> 
								<span class="info-box-text">Laboratorium Ranap</span> 
								<span class="info-box-number">
									<h3 style ="font-size: 22px;"><?php echo $total_ranap; ?> Pemeriksaan</h3>
								</span> 
							</div>
						</div>
					</div>

					<!-- Pemeriksaan Radiologi Ralan -->
					<div class="col-lg-3 col-6">
						<div class="info-box"> 
							<span class="info-box-icon text-bg-warning shadow-sm"> 
								<ion-icon name="scan"></ion-icon>
							</span>
							<div class="info-box-content"> 
								<span class="info-box-text">Radiologi Ralan</span> 
								<span class="info-box-number">
									<h3 style ="font-size: 22px;"><?php echo $total_radio_ralan; ?> Pemeriksaan</h3>
								</span> 
							</div>
						</div>
					</div>

					<!-- Pemeriksaan Radiologi Ranap -->
					<div class="col-lg-3 col-6">
						<div class="info-box"> 
							<span class="info-box-icon text-bg-warning shadow-sm"> 
								<ion-icon name="scan-outline"></ion-icon>
							</span>
							<div class="info-box-content"> 
								<span class="info-box-text">Radiologi Ranap</span> 
								<span class="info-box-number">
									<h3 style ="font-size: 22px;"><?php echo $total_radio_ranap; ?> Pemeriksaan</h3>
								</span> 
							</div>
						</div>
					</div>
				</div>
				<div class="row mt-4">
				<div class="col-lg-4">
						<div class="card mb-4">
							<div class="card-body">
								<canvas id="harianChart" style="height: 300px;"></canvas>
							</div>
						</div>
					</div>

					<!-- Grafik Mingguan -->
					<div class="col-lg-4">
						<div class="card mb-4">
							<div class="card-body">
								<canvas id="mingguanChart" style="height: 300px;"></canvas>
							</div>
						</div>
					</div>

					<!-- Grafik Bulanan -->
					<div class="col-lg-4">
						<div class="card mb-4">
							<div class="card-body">
								<canvas id="bulananChart" style="height: 300px;"></canvas>
							</div>
						</div>
					</div>
					
					
					<div class="col-lg-4">
						<div class="card mb-4">
							<div class="card-body">
								<canvas id="harianChartRanap" style="height: 300px;"></canvas>
							</div>
						</div>
					</div>

					<!-- Grafik Mingguan -->
					<div class="col-lg-4">
						<div class="card mb-4">
							<div class="card-body">
								<canvas id="mingguanChartRanap" style="height: 300px;"></canvas>
							</div>
						</div>
					</div>

					<!-- Grafik Bulanan -->
					<div class="col-lg-4">
						<div class="card mb-4">
							<div class="card-body">
								<canvas id="bulananChartRanap" style="height: 300px;"></canvas>
							</div>
						</div>
					</div>
					
					
					<div class="col-lg-7">
						<div class="card mb-4">
							<div class="card-body">
								<canvas id="chartPoliklinik" style="height: 450px;"></canvas>
							</div>
						</div>
					</div>
					 <!-- Grafik Harian -->
					<div class="col-lg-5">
						<div class="card mb-4">
							<div class="card-body">
								<canvas id="usiaChart" style="height: 450px;"></canvas>
							</div>
						</div>
					</div>
					<div class="col-lg-6">
						<div class="card mb-4">
							<div class="card-body">
								<canvas id="chartPenyakitRalan" style="height: 350px;"></canvas>
							</div>
						</div>
					</div>
					<div class="col-lg-6">
						<div class="card mb-4">
							<div class="card-body">
								<canvas id="chartPenyakitRanap" style="height: 350px;"></canvas>
							</div>
						</div>
					</div>
					
					<div class="col-lg-6">
						<div class="card mb-4">
							<div class="card-body">
								<canvas id="chartRalan" style="height: 350px;"></canvas>
							</div>
						</div>
					</div>
					<div class="col-lg-6">
						<div class="card mb-4">
							<div class="card-body">
								<canvas id="chartRanap" style="height: 350px;"></canvas>
							</div>
						</div>
					</div>
					<div class="col-lg-6">
						<div class="card mb-4">
							<div class="card-body">
								<canvas id="chartRadioRalan" style="height: 350px;"></canvas>
							</div>
						</div>
					</div>
					<div class="col-lg-6">
						<div class="card mb-4">
							<div class="card-body">
								<canvas id="chartRadioRanap" style="height: 350px;"></canvas>
							</div>
						</div>
					</div>
					
					<div class="col-lg-6">
						<div class="card mb-4">
							<div class="card-body">
								<canvas id="pieChartRanapStatus" width="350""></canvas>
							</div>
						</div>
					</div>
					<div class="col-lg-6">
						<div class="card mb-4">
							<div class="card-body">
								<canvas id="pieChartRalanStatus" width="350""></canvas>
							</div>
						</div>
					</div>
					<div class="col-lg-6">
						<div class="card mb-4">
							<div class="card-body">
								<canvas id="pieChartRanapAsuransi" width="350""></canvas>
							</div>
						</div>
					</div>
					<div class="col-lg-6">
						<div class="card mb-4">
							<div class="card-body">
								<canvas id="pieChartRalanAsuransi" width="350""></canvas>
							</div>
						</div>
					</div>
					
					<div class="card-body">
                    <h5 class="text-center mb-3">
                        <i class="material-icons"></i>Daftar Ruang Rawat Inap
                    </h5>
                    <div class="table-responsive">
                        <table class="table table-bordered text-center">
                            <thead class="table-dark">
                                <tr>
                                    <th>Kelas Kamar</th>
                                    <th>Jumlah Bed</th>
                                    <th>Bed Terisi</th>
                                    <th>Bed Kosong</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                require_once('configrs.php');
                                $sql = "SELECT kelas FROM kamar WHERE statusdata='1' GROUP BY kelas";
                                $hasil = $conn->query($sql);
                                while ($data = $hasil->fetch_assoc()) {
                                    $kelas = $data['kelas'];
                                    $jumlah_bed = $conn->query("SELECT COUNT(*) FROM kamar WHERE statusdata='1' AND kelas='$kelas'")->fetch_row()[0];
                                    $bed_terisi = $conn->query("SELECT COUNT(*) FROM kamar WHERE statusdata='1' AND kelas='$kelas' AND status='ISI'")->fetch_row()[0];
                                    $bed_kosong = $conn->query("SELECT COUNT(*) FROM kamar WHERE statusdata='1' AND kelas='$kelas' AND status='KOSONG'")->fetch_row()[0];
                                    echo "<tr>
                                            <td>{$kelas}</td>
                                            <td>{$jumlah_bed}</td>
                                            <td>{$bed_terisi}</td>
                                            <td>{$bed_kosong}</td>
                                          </tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
					
				</div>
            </div>
        </div>
    </main>
	<script type="module" src="https://cdn.jsdelivr.net/npm/@ionic/core/dist/ionic/ionic.esm.js"></script>
	<script nomodule src="https://cdn.jsdelivr.net/npm/@ionic/core/dist/ionic/ionic.js"></script>

<script>
// Fungsi untuk menyesuaikan ukuran chart
function resizeChart(chart, width, height) {
    chart.canvas.parentNode.style.width = width;
    chart.canvas.parentNode.style.height = height;
}

// Chart untuk Rawat Jalan (Ralan)
var ctxRalan = document.getElementById('chartRalan').getContext('2d');
var chartRalan = new Chart(ctxRalan, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($labels_ralan); ?>,
        datasets: [{
            label: 'Jumlah Pemeriksaan Lab Ralan',
            data: <?php echo json_encode($data_ralan); ?>,
            backgroundColor: 'rgba(54, 162, 235, 0.6)',
            borderColor: 'rgba(54, 162, 235, 1)',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false, // Supaya ukuran bisa fleksibel
        plugins: {
            legend: { display: true },
            title: {
                display: true,
                text: 'Pemeriksaan Lab Rawat Jalan (10 terbanyak)',
                font: { size: 18 },
                padding: { top: 10, bottom: 20 }
            }
        },
        scales: { y: { beginAtZero: true } }
    }
});

// Chart untuk Rawat Inap (Ranap)
var ctxRanap = document.getElementById('chartRanap').getContext('2d');
var chartRanap = new Chart(ctxRanap, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($labels_ranap); ?>,
        datasets: [{
            label: 'Jumlah Pemeriksaan Lab Ranap',
            data: <?php echo json_encode($data_ranap); ?>,
            backgroundColor: 'rgba(255, 99, 132, 0.6)',
            borderColor: 'rgba(255, 99, 132, 1)',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false, // Supaya ukuran bisa fleksibel
        plugins: {
            legend: { display: true },
            title: {
                display: true,
                text: 'Pemeriksaan Lab Rawat Inap (10 terbanyak)',
                font: { size: 18 },
                padding: { top: 10, bottom: 20 }
            }
        },
        scales: { y: { beginAtZero: true } }
    }
});
</script>


<script>
// Chart untuk Radiologi Ralan
var ctxRadioRalan = document.getElementById('chartRadioRalan').getContext('2d');
var chartRadioRalan = new Chart(ctxRadioRalan, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($labels_radio_ralan); ?>,
        datasets: [{
            label: 'Jumlah Pemeriksaan Radiologi Ralan',
            data: <?php echo json_encode($data_radio_ralan); ?>,
            backgroundColor: 'rgba(54, 162, 235, 0.6)',
            borderColor: 'rgba(54, 162, 235, 1)',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false, // Supaya ukuran bisa fleksibel
        plugins: {
            legend: { display: true },
            title: {
                display: true,
                text: 'Pemeriksaan Radiologi Rawat Jalan (10 terbanyak)',
                font: { size: 18 },
                padding: { top: 10, bottom: 20 }
            }
        },
        scales: { 
            y: { 
                beginAtZero: true,
                ticks: {
                    stepSize: 1, // Agar kenaikan angka selalu 1
                    precision: 0, // Menghilangkan angka desimal
                    callback: function(value) { return Number(value).toFixed(0); } // Pastikan format bilangan bulat
                }
            }
        }
    }
});

// Chart untuk Radiologi Ranap
var ctxRadioRanap = document.getElementById('chartRadioRanap').getContext('2d');
var chartRadioRanap = new Chart(ctxRadioRanap, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($labels_radio_ranap); ?>,
        datasets: [{
            label: 'Jumlah Pemeriksaan Radiologi Ranap',
            data: <?php echo json_encode($data_radio_ranap); ?>,
            backgroundColor: 'rgba(255, 99, 132, 0.6)',
            borderColor: 'rgba(255, 99, 132, 1)',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false, // Supaya ukuran bisa fleksibel
        plugins: {
            legend: { display: true },
            title: {
                display: true,
                text: 'Pemeriksaan Radiologi Rawat Inap (10 terbanyak)',
                font: { size: 18 },
                padding: { top: 10, bottom: 20 }
            }
        },
        scales: { 
            y: { 
                beginAtZero: true,
                ticks: {
                    stepSize: 1, // Agar kenaikan angka selalu 1
                    precision: 0, // Menghilangkan angka desimal
                    callback: function(value) { return Number(value).toFixed(0); } // Pastikan format bilangan bulat
                }
            }
        }
    }
});
</script>

    <script>
        // Grafik Distribusi Usia
        document.addEventListener("DOMContentLoaded", function () {
            var ctx = document.getElementById("usiaChart").getContext("2d");
            new Chart(ctx, {
                type: "bar",
                data: {
                    labels: <?php echo json_encode($usia_labels); ?>,
                    datasets: [{
                        label: "Jumlah Pasien",
                        data: <?php echo json_encode($usia_data); ?>,
                        backgroundColor: "rgba(54, 162, 235, 0.6)",
                        borderColor: "rgba(54, 162, 235, 1)",
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
					maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: "Kunjungan Berdasarkan Usia Pasien Rawat Jalan",
                            font: { size: 18 }
                        }
                    },
                    scales: {
                        y: { beginAtZero: true, title: { display: true, text: "Jumlah Pasien" } }
                    }
                }
            });
        });

        // Grafik Distribusi Poliklinik
        document.addEventListener("DOMContentLoaded", function () {
            var ctx = document.getElementById("chartPoliklinik").getContext("2d");
            new Chart(ctx, {
                type: "bar",
                data: {
                    labels: <?php echo json_encode($poli_labels); ?>,
                    datasets: [{
                        label: "Jumlah Kunjungan",
                        data: <?php echo json_encode($poli_data); ?>,
                        backgroundColor: "rgba(54, 162, 235, 0.6)",
                        borderColor: "rgba(54, 162, 235, 1)",
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
					maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: "Kunjungan Berdasarkan Poliklinik Hari Ini",
                            font: { size: 18 }
                        }
                    },
                    scales: {
                        y: { beginAtZero: true, title: { display: true, text: "Jumlah Pasien" } }
                    }
                }
            });
        });
		        function createChart(ctx, label, data) {
            return new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.map(item => item.label),
                    datasets: [{
                        label: label,
                        data: data.map(item => item.jumlah),
                        borderColor: 'rgba(75, 192, 192, 1)',
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        borderWidth: 2,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
					maintainAspectRatio: false,
                    plugins: { title: { display: true, text: label, font: { size: 18 } } },
                    scales: { y: { suggestedMin: 0, suggestedMax: 10 } }
                }
            });
        }
		function updateChartVisibility() {
			let selectedChart = document.getElementById('chartSelector').value;

			// Tampilkan hanya chart yang dipilih
			document.getElementById('harianChart').style.display = selectedChart === 'harian' ? 'block' : 'none';
			document.getElementById('mingguanChart').style.display = selectedChart === 'mingguan' ? 'block' : 'none';
			document.getElementById('bulananChart').style.display = selectedChart === 'bulanan' ? 'block' : 'none';

			document.getElementById('harianChartRanap').style.display = selectedChart === 'harianRanap' ? 'block' : 'none';
			document.getElementById('mingguanChartRanap').style.display = selectedChart === 'mingguanRanap' ? 'block' : 'none';
			document.getElementById('bulananChartRanap').style.display = selectedChart === 'bulananRanap' ? 'block' : 'none';
		}

		// Data untuk Ralan
		const harianData = <?php echo json_encode(array_map(function($jam, $jumlah) {
			return ["label" => "$jam:00", "jumlah" => $jumlah];
		}, array_keys($harian_data), $harian_data)); ?>;
		const mingguanData = <?php echo json_encode($mingguan_data); ?>.map(item => ({label: item.hari, jumlah: item.jumlah}));
		const bulananData = <?php echo json_encode($bulanan_data); ?>.map(item => ({label: 'Minggu ' + item.minggu, jumlah: item.jumlah}));

		// Data untuk Ranap
		const harianRanapData = <?php echo json_encode(array_map(function($jam, $jumlah) {
			return ["label" => "$jam:00", "jumlah" => $jumlah];
		}, array_keys($harian_ranap), $harian_ranap)); ?>;

		const mingguanRanapData = <?php echo json_encode($mingguan_ranap); ?>.map(item => ({label: item.hari, jumlah: item.jumlah}));
		const bulananRanapData = <?php echo json_encode($bulanan_ranap); ?>.map(item => ({label: 'Minggu ' + item.minggu, jumlah: item.jumlah}));

		// Buat Chart
		createChart(document.getElementById('harianChart').getContext('2d'), 'Kunjungan Ralan per Jam', harianData);
		createChart(document.getElementById('mingguanChart').getContext('2d'), 'Kunjungan Ralan per Hari', mingguanData);
		createChart(document.getElementById('bulananChart').getContext('2d'), 'Kunjungan Ralan per Minggu', bulananData);

		createChart(document.getElementById('harianChartRanap').getContext('2d'), 'Kunjungan Ranap per Jam', harianRanapData);
		createChart(document.getElementById('mingguanChartRanap').getContext('2d'), 'Kunjungan Ranap per Hari', mingguanRanapData);
		createChart(document.getElementById('bulananChartRanap').getContext('2d'), 'Kunjungan Ranap per Minggu', bulananRanapData);

</script>
<script>
    // Chart untuk Penyakit Ralan
    var ctxRalan = document.getElementById("chartPenyakitRalan").getContext("2d");
    new Chart(ctxRalan, {
        type: "bar",
        data: {
            labels: <?php echo json_encode($penyakit_ralan_labels); ?>,
            datasets: [{
                label: "Jumlah Kasus",
                data: <?php echo json_encode($penyakit_ralan_data); ?>,
                backgroundColor: "rgba(75, 192, 192, 0.6)",
                borderColor: "rgba(75, 192, 192, 1)",
                borderWidth: 1
            }]
        },
        options: {
			responsive: true,
			maintainAspectRatio: false, // Membantu agar tidak terlalu kecil
			plugins: {
                        title: {
                            display: true,
                            text: "Kasus Penyakit Ralan Per Hari Ini (10 terbanyak)",
                            font: { size: 18 }
                        }
                    },
			scales: {
			y: {
				beginAtZero: true,
				suggestedMax: 10 // Sesuaikan dengan data
			}
		}

		}
    });

    // Chart untuk Penyakit Ranap
    var ctxRanap = document.getElementById("chartPenyakitRanap").getContext("2d");
    new Chart(ctxRanap, {
        type: "bar",
        data: {
            labels: <?php echo json_encode($penyakit_ranap_labels); ?>,
            datasets: [{
                label: "Jumlah Kasus",
                data: <?php echo json_encode($penyakit_ranap_data); ?>,
                backgroundColor: "rgba(255, 99, 132, 0.6)",
                borderColor: "rgba(255, 99, 132, 1)",
                borderWidth: 1
            }]
        },
        options: {
			responsive: true,
			maintainAspectRatio: false, // Membantu agar tidak terlalu kecil
			plugins: {
                        title: {
                            display: true,
                            text: "Kasus Penyakit Ranap Per Hari Ini (10 terbanyak)",
                            font: { size: 18 }
                        }
                    },
			scales: {
			y: {
				beginAtZero: true,
				suggestedMax: 10 // Sesuaikan dengan data
			}
		}

		}
    });
</script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels"></script>

<script>
    var colors = ['#3498db', '#e74c3c', '#f1c40f', '#2ecc71', '#9b59b6', '#34495e', '#ff5733', '#33ff57', '#57a1ff'];

    function createPieChart(canvasId, labels, data, title) {
        var ctx = document.getElementById(canvasId).getContext('2d');
        new Chart(ctx, {
            type: 'pie',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: colors.slice(0, labels.length),
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: title,
                        font: {
                            size: 18
                        },
                        padding: {
                            top: 10,
                            bottom: 20
                        }
                    },
                    legend: {
                        position: 'bottom',
                        labels: {
                            font: {
                                size: 12
                            }
                        }
                    },
                    datalabels: {  // 🔥 Menampilkan angka di chart
                        color: 'black', // Warna teks (putih agar kontras)
                        font: {
                            weight: 'bold',
                            size: 14
                        },
                        formatter: (value) => value, // Menampilkan nilai langsung
                    }
                }
            },
            plugins: [ChartDataLabels] // Tambahkan plugin untuk menampilkan angka
        });
    }

    // Membuat semua chart dengan angka di dalamnya
    createPieChart('pieChartRanapStatus', <?= json_encode($labels_ranap_status) ?>, <?= json_encode($data_ranap_status) ?>, 'Pasien Ranap - Baru vs Lama');
    createPieChart('pieChartRalanStatus', <?= json_encode($labels_ralan_status) ?>, <?= json_encode($data_ralan_status) ?>, 'Pasien Ralan - Baru vs Lama');
    createPieChart('pieChartRanapAsuransi', <?= json_encode($labels_ranap_asuransi) ?>, <?= json_encode($data_ranap_asuransi) ?>, 'Asuransi Pasien Ranap');
    createPieChart('pieChartRalanAsuransi', <?= json_encode($labels_ralan_asuransi) ?>, <?= json_encode($data_ralan_asuransi) ?>, 'Asuransi Pasien Ralan');
</script>
</body>
</html>
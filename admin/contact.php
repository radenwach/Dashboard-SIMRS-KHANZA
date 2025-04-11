<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact - Informasi Tugas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            color: white;
        }
        .container {
            max-width: 800px;
        }
        .card {
            border-radius: 10px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.2);
        }
        .card-header {
            font-weight: bold;
        }
        .btn {
            font-size: 1.1rem;
            font-weight: bold;
            transition: transform 0.2s ease-in-out;
        }
        .btn:hover {
            transform: scale(1.05);
        }
    </style>
</head>
<body class="d-flex align-items-center justify-content-center vh-100">

    <div class="container">
        <h2 class="text-center mb-4 text-dark">Informasi Tugas & Identitas Mahasiswa</h2>

        <!-- Informasi Mahasiswa -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white text-center">Identitas Mahasiswa</div>
            <div class="card-body text-dark">
                <p><strong>Nama:</strong> R. MUHAMMAD WACHYU FAJAR SIDIK</p>
                <p><strong>NIM:</strong> 2213020114</p>
                <p><strong>Program Studi:</strong> Teknik Informatika</p>
                <p><strong>Universitas:</strong> Universitas Nusantara PGRI Kediri</p>
            </div>
        </div>

        <!-- Informasi Tugas -->
        <div class="card mb-4">
            <div class="card-header bg-success text-white text-center">Informasi Tugas Proyek</div>
            <div class="card-body text-dark">
                <p><strong>Nama Proyek:</strong> Dashboard Kunjungan Pasien</p>
                <p><strong>Mata Kuliah:</strong> Praktik Kerja Lapangan</p>
                <p><strong>Dosen Pembimbing:</strong> RONY HERI IRAWAN, S.Kom, M.Kom</p>
                <p><strong>Dosen Pembimbing Lapangan:</strong> HARIES ROCHMATULLAH, S. Kom, MMRS, CTI, ICIP</p>
                <p><strong>Tahun:</strong> 2025</p>
            </div>
        </div>

        <!-- Kontak Sosial Media -->
        <div class="card">
            <div class="card-header bg-dark text-white text-center">Hubungi Saya</div>
            <div class="card-body text-center">
                <p>Anda dapat menghubungi saya melalui sosial media berikut:</p>
                <a href="https://wa.me/6283845497054" class="btn btn-success d-block mb-2" target="_blank">
                    <i class="fab fa-whatsapp"></i> WhatsApp
                </a>
                <a href="https://www.instagram.com/ur_rajaaa?igsh=MXg0ejJ2dWVsamZidQ==" class="btn btn-danger d-block" target="_blank">
                    <i class="fab fa-instagram"></i> Instagram
                </a>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>

</body>
</html>

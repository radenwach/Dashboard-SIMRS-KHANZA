<!DOCTYPE html>
<html lang="en"> <!--begin::Head-->

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>Kunjungan Pasien</title><!--begin::Primary Meta Tags-->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="title" content="AdminLTE v4 | Dashboard">
    <meta name="author" content="ColorlibHQ">
    <meta name="description" content="AdminLTE is a Free Bootstrap 5 Admin Dashboard, 30 example pages using Vanilla JS.">
    <meta name="keywords" content="bootstrap 5, bootstrap, bootstrap 5 admin dashboard, bootstrap 5 dashboard, bootstrap 5 charts, bootstrap 5 calendar, bootstrap 5 datepicker, bootstrap 5 tables, bootstrap 5 datatable, vanilla js datatable, colorlibhq, colorlibhq dashboard, colorlibhq admin dashboard"><!--end::Primary Meta Tags--><!--begin::Fonts-->
    <link rel="icon" type="image/png" href="img/logo.png">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fontsource/source-sans-3@5.0.12/index.css" integrity="sha256-tXJfXfp6Ewt1ilPzLDtQnJV4hclT9XuaZUKyUvmyr+Q=" crossorigin="anonymous"><!--end::Fonts--><!--begin::Third Party Plugin(OverlayScrollbars)-->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.3.0/styles/overlayscrollbars.min.css" integrity="sha256-dSokZseQNT08wYEWiz5iLI8QPlKxG+TswNRD8k35cpg=" crossorigin="anonymous"><!--end::Third Party Plugin(OverlayScrollbars)--><!--begin::Third Party Plugin(Bootstrap Icons)-->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.min.css" integrity="sha256-Qsx5lrStHZyR9REqhUF8iQt73X06c8LGIUPzpOhwRrI=" crossorigin="anonymous"><!--end::Third Party Plugin(Bootstrap Icons)--><!--begin::Required Plugin(AdminLTE)-->
    <link rel="stylesheet" href="../assets/adminlte/css/adminlte.css"><!--end::Required Plugin(AdminLTE)--><!-- apexcharts -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/apexcharts@3.37.1/dist/apexcharts.css" integrity="sha256-4MX+61mt9NVvvuPjUWdUdyfZfxSB1/Rf9WtqRHgG5S0=" crossorigin="anonymous"><!-- jsvectormap -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/jsvectormap@1.5.3/dist/css/jsvectormap.min.css" integrity="sha256-+uGLJmmTKOqBr+2E6KDYs/NRsHxSkONXFHUL0fy2O/4=" crossorigin="anonymous">
    <link type="text/css" href="assets/css/sample.css" rel="stylesheet" media="screen" />
    <!-- Tambahkan CSS untuk mengatur posisi waktu -->
    <style>
        .navbar-nav {
            width: 100%;
            display: flex;
            align-items: center;
        }
        .time-container {
            margin-left: auto; /* Mendorong elemen waktu ke sebelah kanan */
            padding-right: 20px; /* Memberikan jarak dari tepi kanan */
        }
    </style>
</head> <!--end::Head--> <!--begin::Body-->

<body class="layout-fixed sidebar-expand-lg bg-body-tertiary"> <!--begin::App Wrapper-->
    <div class="app-wrapper"> <!--begin::Header-->
        <nav class="app-header navbar navbar-expand bg-body"> <!--begin::Container-->
            <div class="container-fluid"> <!--begin::Start Navbar Links-->
                <ul class="navbar-nav">
                    <li class="nav-item"> <a class="nav-link" data-lte-toggle="sidebar" href="#" role="button"> <i class="bi bi-list"></i> </a> </li>
                    <li class="nav-item d-none d-md-block"> <a href="?page=dashboard" class="nav-link">Home</a> </li>
                    <li class="nav-item d-none d-md-block"> <a href="?page=contact" class="nav-link">Contact</a> </li>
                    <!-- Tambahkan elemen waktu di sini -->
                    <li class="nav-item time-container"> 
						<span class="nav-link" id="current-time"></span>
					</li>
					<li class="nav-item">
						<span class="nav-link">|</span> <!-- Pembatas -->
					</li>
					<li class="nav-item">
						<span class="nav-link">👤 
							<?php 
								echo isset($_SESSION['nama']) ? $_SESSION['nama'] : "Pengguna"; 
							?>
						</span>
					</li>

                </ul> <!--end::Start Navbar Links--> <!--begin::End Navbar Links-->
            </div> <!--end::Container-->
        </nav> <!--end::Header--> <!--begin::Sidebar-->

        <!-- Script untuk menampilkan waktu, tanggal, dan hari dalam bahasa Indonesia -->
        <script>
            function updateTime() {
                const now = new Date();
                const options = { 
                    weekday: 'long', // Menampilkan nama hari (contoh: Senin)
                    year: 'numeric', // Menampilkan tahun (contoh: 2023)
                    month: 'long',  // Menampilkan nama bulan (contoh: Oktober)
                    day: 'numeric',  // Menampilkan tanggal (contoh: 10)
                    hour: '2-digit', // Menampilkan jam (contoh: 14)
                    minute: '2-digit', // Menampilkan menit (contoh: 05)
                    second: '2-digit', // Menampilkan detik (contoh: 30)
                    hour12: false // Format 24 jam
                };
                const timeString = now.toLocaleDateString('id-ID', options); // Format dalam bahasa Indonesia
                document.getElementById('current-time').textContent = timeString;
            }

            // Update waktu setiap detik
            setInterval(updateTime, 1000);

            // Panggil fungsi pertama kali untuk menampilkan waktu segera
            updateTime();
        </script>
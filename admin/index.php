<?php 
include "configrs.php";
session_start();

if (empty($_SESSION['user'])) {
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Access Denied</title>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
        <style>
            body {
                display: flex;
                align-items: center;
                justify-content: center;
                height: 100vh;
                background-color: #f8f9fa;
                text-align: center;
            }
            .container {
                padding: 20px;
                background: white;
                border-radius: 10px;
                box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h1 class="text-danger">Access Denied</h1>
            <p>Harap login terlebih dahulu untuk mengakses halaman ini.</p>
            <a href="http://localhost/kunjungan/index.php" class="btn btn-primary">Kembali ke Login</a>
        </div>
    </body>
    </html>
    <?php
    exit();
} else {
    include "header.php";
    include "sidebar.php";

    if (isset($_GET['page'])) {
        $page = $_GET['page'];
    } else {
        $page = "";
    }

    switch ($page) {
        case "contact":
            include "contact.php";
            break;
        case "dashboard":
            include "dashboard.php";
            break;
        case "kunjungan":
            include "kunjungan_ralan.php";
            break;
        case "ranap":
            include "kunjungan_ranap.php";
            break;
        case "ranapmasuk":
            include "ranap_masuk.php";
            break;
        case "ranapkeluar":
            include "ranap_keluar.php";
            break;
        case "labralan":
            include "lab_ralan.php";
            break;
        case "labranap":
            include "lab_ranap.php";
            break;
        case "radioralan":
            include "radio_ralan.php";
            break;
        case "radioranap":
            include "radio_ranap.php";
            break;
        default:
            include "dashboard.php";
    }

    include "footer.php";
}
?>

<?php
session_start();
include "admin/configrs.php";

if (isset($_POST['user'])) {
    $user = $_POST['user'];
} else {
    $user = "";
}

if (isset($_POST['pass'])) {
    $pass = $_POST['pass'];
} else {
    $pass = "";
}

// Menggunakan prepared statement dengan mysqli
$query = "SELECT user.id_user, pegawai.nama 
          FROM user 
          JOIN pegawai ON AES_DECRYPT(user.id_user, 'nur') = pegawai.nik
          WHERE AES_DECRYPT(user.id_user, 'nur') = ? 
          AND AES_DECRYPT(user.password, 'windi') = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("ss", $user, $pass);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $data = $result->fetch_assoc();
    
    $_SESSION['user'] = $user;
    $_SESSION['nama'] = $data['nama']; // Simpan nama pegawai dalam session

    echo "<script>
            window.location='http://localhost/kunjungan/admin/index.php';
          </script>";
} else {
    echo "<script>
            alert('Username atau Password salah!');
            window.location='http://localhost/kunjungan/index.php';
          </script>";
}

$stmt->close();
$conn->close();
?>

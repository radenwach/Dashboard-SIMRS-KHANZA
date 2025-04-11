<?php

session_start();
$_SESSION['user'] = '';
echo "<script>window.location='http://localhost/kunjungan/index.php'</script>";

?>
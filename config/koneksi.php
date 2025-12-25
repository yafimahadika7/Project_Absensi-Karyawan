<?php
date_default_timezone_set('Asia/Jakarta');
$host   = "localhost";
$user   = "root";      // default XAMPP
$pass   = "janganangel";          // kalau pakai password, isi di sini
$dbname = "absensi-karyawan";

$koneksi = mysqli_connect($host, $user, $pass, $dbname);

if (!$koneksi) {
    die("Koneksi gagal: " . mysqli_connect_error());
}
?>

<?php
require_once '../config/auth.php';
require_once '../config/koneksi.php';

auth_guard();
role_guard('karyawan');

header('Content-Type: application/json');

function json_out($success, $message, $extra = [])
{
    echo json_encode(array_merge([
        'success' => $success,
        'message' => $message
    ], $extra));
    exit;
}

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id)
    json_out(false, "Session user tidak ditemukan.");

$tipe = $_POST['tipe'] ?? '';
$foto = $_POST['foto'] ?? '';
$lat = $_POST['latitude'] ?? '';
$lng = $_POST['longitude'] ?? '';

if (!in_array($tipe, ['masuk', 'pulang']))
    json_out(false, "Tipe absensi tidak valid.");
if (!$foto)
    json_out(false, "Foto belum tersedia.");
if ($lat === '' || $lng === '')
    json_out(false, "Lokasi belum tersedia.");

$lat = floatval($lat);
$lng = floatval($lng);

$tanggal = date('Y-m-d');
$now = date('Y-m-d H:i:s');

// ===== fungsi jarak (Haversine) meter =====
function haversine_m($lat1, $lon1, $lat2, $lon2)
{
    $R = 6371000;
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat / 2) * sin($dLat / 2) +
        cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
        sin($dLon / 2) * sin($dLon / 2);
    $c = 2 * asin(sqrt($a));
    return $R * $c;
}

// ===== ambil semua lokasi kantor & cek radius =====
$lok = mysqli_query($koneksi, "SELECT latitude, longitude, radius_meter, nama_lokasi FROM lokasi_kantor");
if (!$lok || mysqli_num_rows($lok) == 0) {
    json_out(false, "Lokasi kantor belum disetting oleh admin.");
}

$within = false;
$jarakTerdekat = null;
$namaTerdekat = null;

while ($r = mysqli_fetch_assoc($lok)) {
    $latK = floatval($r['latitude']);
    $lngK = floatval($r['longitude']);
    $rad = floatval($r['radius_meter']);
    $d = haversine_m($lat, $lng, $latK, $lngK);

    if ($jarakTerdekat === null || $d < $jarakTerdekat) {
        $jarakTerdekat = $d;
        $namaTerdekat = $r['nama_lokasi'];
    }
    if ($d <= $rad) {
        $within = true;
        break;
    }
}

if (!$within) {
    $dTxt = $jarakTerdekat !== null ? number_format($jarakTerdekat, 0) : '-';
    json_out(false, "Absen gagal. Anda tidak dalam radius lokasi kantor (terdekat: $namaTerdekat, jarak: {$dTxt}m).");
}

// ===== validasi absensi hari ini =====
$q = mysqli_query($koneksi, "SELECT * FROM absensi WHERE user_id='$user_id' AND tanggal='$tanggal' LIMIT 1");
$row = ($q && mysqli_num_rows($q) > 0) ? mysqli_fetch_assoc($q) : null;

if ($tipe === 'masuk') {
    // kalau sudah ada jam_masuk -> tolak
    if ($row && !empty($row['jam_masuk'])) {
        json_out(false, "Anda sudah melakukan absen masuk hari ini.");
    }
} else {
    // pulang harus sudah masuk dulu
    if (!$row || empty($row['jam_masuk'])) {
        json_out(false, "Absen pulang ditolak. Anda belum absen masuk hari ini.");
    }
    // pulang tidak boleh 2x
    if (!empty($row['jam_pulang'])) {
        json_out(false, "Anda sudah melakukan absen pulang hari ini.");
    }
}

// ===== simpan foto (base64 -> png) =====
$uploadRelDir = "uploads/absensi/$tanggal";      // untuk disimpan ke DB (RELATIF)
$uploadAbsDir = __DIR__ . "/../$uploadRelDir";   // path absolut server

if (!is_dir($uploadAbsDir)) {
    mkdir($uploadAbsDir, 0755, true);
}

// bersihkan prefix base64
$foto = preg_replace('#^data:image/\w+;base64,#i', '', $foto);
$bin = base64_decode($foto);

if (!$bin) {
    json_out(false, "Foto tidak valid / gagal diproses.");
}

$namaFile = "{$tipe}_{$user_id}_" . date('His') . ".png";
$pathAbs = "$uploadAbsDir/$namaFile";
$pathRel = "$uploadRelDir/$namaFile";

if (file_put_contents($pathAbs, $bin) === false) {
    json_out(false, "Gagal menyimpan foto ke server. Cek permission folder uploads.");
}

// ===== simpan ke DB sesuai tipe =====
if ($tipe === 'masuk') {
    // insert baru
    $sql = "
        INSERT INTO absensi
            (user_id, tanggal, jam_masuk, foto_masuk, latitude_masuk, longitude_masuk, status)
        VALUES
            ('$user_id', '$tanggal', '$now', '$pathRel', '$lat', '$lng', 'hadir')
    ";
    $ok = mysqli_query($koneksi, $sql);
    if (!$ok)
        json_out(false, "Gagal menyimpan absen masuk: " . mysqli_error($koneksi));

    json_out(true, "Absen masuk berhasil disimpan.");
} else {
    // update record yang sudah ada
    $sql = "
        UPDATE absensi SET
            jam_pulang = '$now',
            foto_pulang = '$pathRel',
            latitude_pulang = '$lat',
            longitude_pulang = '$lng'
        WHERE user_id = '$user_id' AND tanggal = '$tanggal'
    ";
    $ok = mysqli_query($koneksi, $sql);
    if (!$ok)
        json_out(false, "Gagal menyimpan absen pulang: " . mysqli_error($koneksi));

    json_out(true, "Absen pulang berhasil disimpan.");
}

<?php
require_once '../config/auth.php';
require_once '../config/koneksi.php';

role_guard('hrd');

$page_title = "Dashboard HRD";

$today = date('Y-m-d');

$totalKaryawan = mysqli_num_rows(
    mysqli_query($koneksi, "SELECT id FROM users")
);

$hadirHariIni = mysqli_num_rows(
    mysqli_query($koneksi, "SELECT id FROM absensi WHERE tanggal='$today' AND status='hadir'")
);

$izinMenunggu = mysqli_num_rows(
    mysqli_query($koneksi, "SELECT id FROM izin WHERE status='menunggu'")
);

ob_start();
?>

<div class="alert alert-info shadow-sm">
    <i class="bi bi-info-circle me-1"></i>
    Selamat datang, <strong><?= $_SESSION['nama'] ?></strong>.
    Anda dapat memantau absensi dan menyetujui perizinan karyawan.
</div>

<div class="row g-3">

    <div class="col-md-4">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <h6 class="text-muted">Total Karyawan</h6>
                <h3 class="fw-bold"><?= $totalKaryawan ?></h3>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <h6 class="text-muted">Hadir Hari Ini</h6>
                <h3 class="fw-bold text-success"><?= $hadirHariIni ?></h3>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <h6 class="text-muted">Izin Menunggu Persetujuan</h6>
                <h3 class="fw-bold text-warning"><?= $izinMenunggu ?></h3>
            </div>
        </div>
    </div>

</div>

<?php
$content = ob_get_clean();
require_once 'layout.php';

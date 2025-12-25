<?php
require_once '../config/koneksi.php';
require_once '../config/auth.php';

auth_guard();
role_guard('admin');

$page_title = "Dashboard Admin";

/* ===== QUERY STATISTIK ===== */

// Total karyawan aktif
$q_total = mysqli_query($koneksi, "SELECT COUNT(*) total FROM users WHERE role='karyawan' AND status='aktif'");
$total_karyawan = mysqli_fetch_assoc($q_total)['total'] ?? 0;

// Hadir hari ini
$q_hadir = mysqli_query($koneksi, "
    SELECT COUNT(*) total FROM absensi 
    WHERE tanggal = CURDATE() AND status = 'hadir'
");
$hadir_hari_ini = mysqli_fetch_assoc($q_hadir)['total'] ?? 0;

// Izin hari ini
$q_izin = mysqli_query($koneksi, "
    SELECT COUNT(*) total FROM absensi 
    WHERE tanggal = CURDATE() AND status IN ('izin','sakit','cuti')
");
$izin_hari_ini = mysqli_fetch_assoc($q_izin)['total'] ?? 0;

// Belum absen
$belum_absen = $total_karyawan - ($hadir_hari_ini + $izin_hari_ini);
if ($belum_absen < 0)
    $belum_absen = 0;

// Rekap 7 hari terakhir
$q_chart = mysqli_query($koneksi, "
    SELECT tanggal,
        SUM(status='hadir') hadir,
        SUM(status='izin') izin,
        SUM(status='alpha') alpha
    FROM absensi
    WHERE tanggal >= CURDATE() - INTERVAL 6 DAY
    GROUP BY tanggal
    ORDER BY tanggal ASC
");

$labels = [];
$data_hadir = [];
$data_izin = [];
$data_alpha = [];

while ($row = mysqli_fetch_assoc($q_chart)) {
    $labels[] = $row['tanggal'];
    $data_hadir[] = (int) $row['hadir'];
    $data_izin[] = (int) $row['izin'];
    $data_alpha[] = (int) $row['alpha'];
}

ob_start();
?>

<!-- ===== CARDS ===== -->
<div class="row g-4 mb-4">

    <div class="col-md-3">
        <div class="card shadow-sm border-0">
            <div class="card-body d-flex align-items-center gap-3">
                <i class="bi bi-people-fill fs-1 text-primary"></i>
                <div>
                    <small class="text-muted">Total Karyawan</small>
                    <h4 class="fw-bold"><?= $total_karyawan ?></h4>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card shadow-sm border-0">
            <div class="card-body d-flex align-items-center gap-3">
                <i class="bi bi-calendar-check fs-1 text-success"></i>
                <div>
                    <small class="text-muted">Hadir Hari Ini</small>
                    <h4 class="fw-bold"><?= $hadir_hari_ini ?></h4>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card shadow-sm border-0">
            <div class="card-body d-flex align-items-center gap-3">
                <i class="bi bi-envelope-paper fs-1 text-warning"></i>
                <div>
                    <small class="text-muted">Izin Hari Ini</small>
                    <h4 class="fw-bold"><?= $izin_hari_ini ?></h4>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card shadow-sm border-0">
            <div class="card-body d-flex align-items-center gap-3">
                <i class="bi bi-exclamation-circle fs-1 text-danger"></i>
                <div>
                    <small class="text-muted">Belum Absen</small>
                    <h4 class="fw-bold"><?= $belum_absen ?></h4>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- ===== CHARTS ===== -->
<div class="row g-4 align-items-stretch">

    <div class="col-md-6 d-flex">
        <div class="card shadow-sm border-0 w-100">
            <div class="card-header bg-white fw-semibold">
                Kondisi Absensi Hari Ini
            </div>
            <div class="card-body d-flex justify-content-center align-items-center">
                <canvas id="chartToday" style="max-height:280px;"></canvas>
            </div>
        </div>
    </div>

    <div class="col-md-6 d-flex">
        <div class="card shadow-sm border-0 w-100">
            <div class="card-header bg-white fw-semibold">
                Tren Absensi 7 Hari Terakhir
            </div>
            <div class="card-body">
                <canvas id="chartWeekly" style="max-height:280px;"></canvas>
            </div>
        </div>
    </div>

</div>

<!-- ===== CHART JS ===== -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Pie chart hari ini
    new Chart(document.getElementById('chartToday'), {
        type: 'doughnut',
        data: {
            labels: ['Hadir', 'Izin', 'Belum Absen'],
            datasets: [{
                data: [<?= $hadir_hari_ini ?>, <?= $izin_hari_ini ?>, <?= $belum_absen ?>],
                backgroundColor: ['#22c55e', '#facc15', '#ef4444']
            }]
        }
    });

    // Line chart mingguan
    new Chart(document.getElementById('chartWeekly'), {
        type: 'line',
        data: {
            labels: <?= json_encode($labels) ?>,
            datasets: [
                {
                    label: 'Hadir',
                    data: <?= json_encode($data_hadir) ?>,
                    borderColor: '#22c55e',
                    tension: 0.3
                },
                {
                    label: 'Izin',
                    data: <?= json_encode($data_izin) ?>,
                    borderColor: '#facc15',
                    tension: 0.3
                },
                {
                    label: 'Alpha',
                    data: <?= json_encode($data_alpha) ?>,
                    borderColor: '#ef4444',
                    tension: 0.3
                }
            ]
        }
    });
</script>

<?php
$content = ob_get_clean();
require_once 'layout.php';

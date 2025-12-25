<?php
require_once '../config/auth.php';
require_once '../config/koneksi.php';

auth_guard();
role_guard('hrd');

$page_title = "Data Absensi Karyawan";

/* ======================
   FILTER
====================== */
$dari = $_GET['dari'] ?? date('Y-m-01');
$sampai = $_GET['sampai'] ?? date('Y-m-d');
$departemen = $_GET['departemen'] ?? '';

$page = max(1, (int) ($_GET['page'] ?? 1));
$limit = (int) ($_GET['limit'] ?? 25);
$offset = ($page - 1) * $limit;

/* ======================
   DATA DEPARTEMEN
====================== */
$deptQuery = mysqli_query($koneksi, "
    SELECT id, nama_departemen
    FROM departemen
    ORDER BY nama_departemen ASC
");

/* ======================
   DATA KARYAWAN
====================== */
$whereDept = "";
if ($departemen !== '') {
    $whereDept = "WHERE k.departemen_id = '$departemen'";
}

$qKaryawan = mysqli_query($koneksi, "
    SELECT
        u.id AS user_id,
        u.nama,
        d.nama_departemen
    FROM users u
    JOIN karyawan k ON k.user_id = u.id
    JOIN departemen d ON k.departemen_id = d.id
    $whereDept
    ORDER BY d.nama_departemen ASC, u.nama ASC
");

/* ======================
   DATA ABSENSI
====================== */
$absensi = [];
$qAbs = mysqli_query($koneksi, "
    SELECT *
    FROM absensi
    WHERE tanggal BETWEEN '$dari' AND '$sampai'
");

while ($a = mysqli_fetch_assoc($qAbs)) {
    $absensi[$a['user_id']][$a['tanggal']] = $a;
}

/* ======================
   RANGE TANGGAL
====================== */
$periode = new DatePeriod(
    new DateTime($dari),
    new DateInterval('P1D'),
    (new DateTime($sampai))->modify('+1 day')
);

/* ======================
   GENERATE SEMUA ROW
====================== */
$rows = [];

foreach ($periode as $tgl) {
    $tanggal = $tgl->format('Y-m-d');
    mysqli_data_seek($qKaryawan, 0);

    while ($k = mysqli_fetch_assoc($qKaryawan)) {
        $data = $absensi[$k['user_id']][$tanggal] ?? null;
        $status = $data['status'] ?? 'belum absen';

        $rows[] = [
            'tanggal' => $tanggal,
            'nama' => $k['nama'],
            'departemen' => $k['nama_departemen'],
            'jam_masuk' => $data['jam_masuk'] ?? null,
            'jam_pulang' => $data['jam_pulang'] ?? null,
            'status' => $status
        ];
    }
}

/* ======================
   PAGINATION
====================== */
$totalRows = count($rows);
$totalPages = ceil($totalRows / $limit);
$rowsView = array_slice($rows, $offset, $limit);

ob_start();
?>

<style>
    .btn-filter {
        padding: .45rem .9rem;
        font-size: .9rem;
        height: 38px;
    }

    @media (max-width:576px) {
        .btn-filter {
            width: 100%;
            height: auto;
            font-size: 1rem
        }
    }
</style>

<div class="container-fluid">

    <!-- FILTER -->
    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body">
            <h5 class="fw-semibold mb-3">Filter Data Absensi</h5>

            <form method="get" class="row g-2 align-items-end">
                <input type="hidden" name="page" value="1">

                <div class="col-md-3">
                    <label>Dari Tanggal</label>
                    <input type="date" name="dari" class="form-control" value="<?= $dari ?>">
                </div>

                <div class="col-md-3">
                    <label>Sampai Tanggal</label>
                    <input type="date" name="sampai" class="form-control" value="<?= $sampai ?>">
                </div>

                <div class="col-md-3">
                    <label>Departemen</label>
                    <select name="departemen" class="form-select">
                        <option value="">Semua Departemen</option>
                        <?php mysqli_data_seek($deptQuery, 0); ?>
                        <?php while ($d = mysqli_fetch_assoc($deptQuery)): ?>
                            <option value="<?= $d['id'] ?>" <?= $departemen == $d['id'] ? 'selected' : '' ?>>
                                <?= strtoupper($d['nama_departemen']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="col-md-3 d-flex gap-2">
                    <button class="btn btn-primary btn-filter">
                        <i class="bi bi-filter"></i> Tampilkan
                    </button>

                    <a href="export_absensi_xlsx.php?<?= http_build_query($_GET) ?>" class="btn btn-success btn-filter">
                        <i class="bi bi-file-earmark-excel"></i> Export
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- TABEL -->
    <div class="card shadow-sm border-0">
        <div class="card-body">

            <h5 class="fw-semibold mb-3">
                Data Absensi (<?= date('d-m-Y', strtotime($dari)) ?> s/d <?= date('d-m-Y', strtotime($sampai)) ?>)
            </h5>

            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle text-center small">
                    <thead class="table-light">
                        <tr>
                            <th>Tanggal</th>
                            <th>Nama</th>
                            <th>Departemen</th>
                            <th>Jam Masuk</th>
                            <th>Jam Pulang</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>

                        <?php foreach ($rowsView as $r): ?>
                            <?php
                            $badge = match ($r['status']) {
                                'hadir' => 'bg-success',
                                'izin' => 'bg-warning text-dark',
                                'cuti' => 'bg-primary',
                                'sakit' => 'bg-info text-dark',
                                default => 'bg-secondary'
                            };
                            ?>
                            <tr>
                                <td><?= date('d-m-Y', strtotime($r['tanggal'])) ?></td>
                                <td><?= htmlspecialchars($r['nama']) ?></td>
                                <td><?= strtoupper($r['departemen']) ?></td>
                                <td><?= $r['jam_masuk'] ? date('H:i', strtotime($r['jam_masuk'])) : '-' ?></td>
                                <td><?= $r['jam_pulang'] ? date('H:i', strtotime($r['jam_pulang'])) : '-' ?></td>
                                <td><span class="badge <?= $badge ?>"><?= ucfirst($r['status']) ?></span></td>
                            </tr>
                        <?php endforeach; ?>

                    </tbody>
                </table>
            </div>

            <!-- PAGINATION -->
            <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2">

                <div><?= $offset + 1 ?>–<?= min($offset + $limit, $totalRows) ?> / <?= $totalRows ?></div>

                <form method="get" class="d-flex gap-2 align-items-center">
                    <?php foreach ($_GET as $k => $v)
                        if ($k != 'limit')
                            echo "<input type='hidden' name='$k' value='$v'>"; ?>
                    <select name="limit" class="form-select form-select-sm" onchange="this.form.submit()">
                        <?php foreach ([10, 25, 50] as $l): ?>
                            <option value="<?= $l ?>" <?= $limit == $l ? 'selected' : '' ?>><?= $l ?></option>
                        <?php endforeach; ?>
                    </select>
                </form>

                <nav>
                    <ul class="pagination pagination-sm mb-0">
                        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                            <a class="page-link"
                                href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">‹</a>
                        </li>

                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                <a class="page-link"
                                    href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>

                        <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                            <a class="page-link"
                                href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">›</a>
                        </li>
                    </ul>
                </nav>

            </div>

        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once 'layout.php';

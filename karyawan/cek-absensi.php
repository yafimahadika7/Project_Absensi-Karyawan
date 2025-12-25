<?php
require_once '../config/auth.php';
require_once '../config/koneksi.php';

auth_guard();
role_guard('karyawan');

$page_title = "Cek Absensi";

$user_id = $_SESSION['user_id'];

// ======================
// FILTER TANGGAL
// ======================
$dari = $_GET['dari'] ?? date('Y-m-01');
$sampai = $_GET['sampai'] ?? date('Y-m-d');

// ======================
// AMBIL ABSENSI USER
// ======================
$absensi = [];
$qAbs = mysqli_query($koneksi, "
    SELECT *
    FROM absensi
    WHERE user_id = '$user_id'
    AND tanggal BETWEEN '$dari' AND '$sampai'
");

while ($a = mysqli_fetch_assoc($qAbs)) {
    $absensi[$a['tanggal']] = $a;
}

// ======================
// GENERATE RANGE TANGGAL
// ======================
$periode = new DatePeriod(
    new DateTime($dari),
    new DateInterval('P1D'),
    (new DateTime($sampai))->modify('+1 day')
);

ob_start();
?>

<div class="container-fluid">

    <!-- ======================
         FILTER
    ====================== -->
    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body">
            <h5 class="fw-semibold mb-3">Filter Absensi</h5>

            <form method="GET" class="row g-2 align-items-end">

                <div class="col-12 col-md-4">
                    <label class="form-label">Dari Tanggal</label>
                    <input type="date" name="dari" class="form-control" value="<?= htmlspecialchars($dari) ?>">
                </div>

                <div class="col-12 col-md-4">
                    <label class="form-label">Sampai Tanggal</label>
                    <input type="date" name="sampai" class="form-control" value="<?= htmlspecialchars($sampai) ?>">
                </div>

                <div class="col-12 col-md-4 d-flex gap-2">
                    <button class="btn btn-primary btn-action">
                        <i class="bi bi-filter"></i> Filter
                    </button>

                    <a href="export_absensi_excel.php?dari=<?= $dari ?>&sampai=<?= $sampai ?>"
                        class="btn btn-success btn-action">
                        <i class="bi bi-file-earmark-excel"></i> Export Excel
                    </a>
                </div>

            </form>
        </div>
    </div>

    <!-- ======================
         TABEL ABSENSI
    ====================== -->
    <div class="card shadow-sm border-0">
        <div class="card-body">

            <h5 class="fw-semibold mb-3">Riwayat Absensi</h5>

            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle text-center small">
                    <thead class="table-light">
                        <tr>
                            <th>Tanggal</th>
                            <th>Jam Masuk</th>
                            <th>Jam Pulang</th>
                            <th>Status</th>
                            <th>Foto Masuk</th>
                            <th>Foto Pulang</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($periode as $tgl): ?>
                            <?php
                            $tanggal = $tgl->format('Y-m-d');
                            $row = $absensi[$tanggal] ?? null;

                            $status = $row['status'] ?? 'belum absen';

                            switch ($status) {
                                case 'hadir':
                                    $badge = 'bg-success';
                                    break;

                                case 'izin':
                                    $badge = 'bg-warning text-dark';
                                    break;

                                case 'cuti':
                                    $badge = 'bg-primary';
                                    break;

                                case 'sakit':
                                    $badge = 'bg-danger';
                                    break;

                                default:
                                    $badge = 'bg-secondary';
                            }
                            ?>

                            <tr>
                                <td><?= date('d-m-Y', strtotime($tanggal)) ?></td>

                                <td>
                                    <?= isset($row['jam_masuk'])
                                        ? date('H:i', strtotime($row['jam_masuk']))
                                        : '-' ?>
                                </td>

                                <td>
                                    <?= isset($row['jam_pulang'])
                                        ? date('H:i', strtotime($row['jam_pulang']))
                                        : '-' ?>
                                </td>

                                <td>
                                    <span class="badge <?= $badge ?>">
                                        <?= ucfirst($status) ?>
                                    </span>
                                </td>

                                <td>
                                    <?php if (!empty($row['foto_masuk'])): ?>
                                        <button type="button" class="btn btn-sm btn-outline-primary"
                                            onclick="previewFoto('../<?= $row['foto_masuk'] ?>','Foto Masuk')">
                                            Lihat
                                        </button>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?php if (!empty($row['foto_pulang'])): ?>
                                        <button type="button" class="btn btn-sm btn-outline-primary"
                                            onclick="previewFoto('../<?= $row['foto_pulang'] ?>','Foto Pulang')">
                                            Lihat
                                        </button>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                            </tr>

                        <?php endforeach; ?>
                    </tbody>

                </table>
            </div>

        </div>
    </div>

    <!-- ======================
         MODAL PREVIEW FOTO
    ====================== -->
    <div class="modal fade" id="modalFoto" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-absen">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title" id="modalFotoTitle">Preview Foto</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body text-center">
                    <img id="modalFotoImg" src="" class="img-fluid rounded border" alt="Foto Absensi">
                </div>

            </div>
        </div>
    </div>

</div>

<script>
    function previewFoto(src, title) {
        document.getElementById('modalFotoImg').src = src;
        document.getElementById('modalFotoTitle').innerText = title;

        const modal = new bootstrap.Modal(document.getElementById('modalFoto'));
        modal.show();
    }
</script>

<?php
$content = ob_get_clean();
require_once 'layout.php';

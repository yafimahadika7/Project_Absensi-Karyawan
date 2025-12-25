<?php
require_once '../config/auth.php';
require_once '../config/koneksi.php';

auth_guard();
role_guard('karyawan');

date_default_timezone_set('Asia/Jakarta');

$page_title = "Izin Karyawan";

$user_id = $_SESSION['user_id'];

$error = '';
$success = '';

// =======================
// PROSES SUBMIT IZIN
// =======================
if (isset($_POST['submit_izin'])) {

    $jenis = $_POST['jenis'] ?? '';
    $tanggal_mulai = $_POST['tanggal_mulai'] ?? '';
    $tanggal_selesai = $_POST['tanggal_selesai'] ?? '';
    $alasan = trim($_POST['alasan'] ?? '');

    // ---------- VALIDASI ----------
    if (!$jenis || !$tanggal_mulai || !$tanggal_selesai || !$alasan) {
        $error = "Semua field wajib diisi.";
    } elseif ($tanggal_mulai > $tanggal_selesai) {
        $error = "Tanggal mulai tidak boleh lebih besar dari tanggal selesai.";
    }

    // ---------- VALIDASI FILE (CUTI & SAKIT) ----------
    $filePath = null;

    if (in_array($jenis, ['cuti', 'sakit'])) {
        if (empty($_FILES['file_surat']['name'])) {
            $error = "Surat izin (PDF) wajib diunggah untuk cuti atau sakit.";
        }
    }

    // ---------- PROSES UPLOAD FILE ----------
    if (!$error && !empty($_FILES['file_surat']['name'])) {

        if ($_FILES['file_surat']['error'] !== UPLOAD_ERR_OK) {
            $error = "Gagal mengunggah file.";
        } else {

            $mime = mime_content_type($_FILES['file_surat']['tmp_name']);
            if ($mime !== 'application/pdf') {
                $error = "File harus berbentuk PDF.";
            } else {

                $bulan = date('Y-m');
                $dir = "../uploads/izin/$bulan";

                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }

                $namaFile = $jenis . "_" . $user_id . "_" . date('YmdHis') . ".pdf";
                $filePath = "uploads/izin/$bulan/$namaFile";

                if (!move_uploaded_file($_FILES['file_surat']['tmp_name'], "../$filePath")) {
                    $error = "Gagal menyimpan file ke server.";
                }
            }
        }
    }

    // ---------- SIMPAN KE DATABASE ----------
    if (!$error) {

        mysqli_query($koneksi, "
            INSERT INTO izin
                (user_id, tanggal_mulai, tanggal_selesai, jenis, alasan, file_surat, status)
            VALUES
                ('$user_id', '$tanggal_mulai', '$tanggal_selesai',
                 '$jenis', '$alasan', " . ($filePath ? "'$filePath'" : "NULL") . ", 'pending')
        ");

        $success = "Pengajuan izin berhasil dikirim dan menunggu persetujuan HRD.";
    }
}

// =======================
// DATA RIWAYAT IZIN
// =======================
$dataIzin = mysqli_query($koneksi, "
    SELECT *
    FROM izin
    WHERE user_id = '$user_id'
    ORDER BY created_at DESC
");

ob_start();
?>

<div class="container-fluid">

    <!-- ======================
         FORM AJUKAN IZIN
    ======================= -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">

            <h5 class="fw-semibold mb-3">Ajukan Izin</h5>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success"><?= $success ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" class="row g-3">

                <div class="col-12 col-md-4">
                    <label class="form-label">Jenis Izin</label>
                    <select name="jenis" class="form-select" required>
                        <option value="">-- Pilih --</option>
                        <option value="izin">Izin</option>
                        <option value="cuti">Cuti</option>
                        <option value="sakit">Sakit</option>
                    </select>
                </div>

                <div class="col-12 col-md-4">
                    <label class="form-label">Tanggal Mulai</label>
                    <input type="date" name="tanggal_mulai" class="form-control" required>
                </div>

                <div class="col-12 col-md-4">
                    <label class="form-label">Tanggal Selesai</label>
                    <input type="date" name="tanggal_selesai" class="form-control" required>
                </div>

                <div class="col-12">
                    <label class="form-label">Alasan</label>
                    <textarea name="alasan" class="form-control" rows="3" placeholder="Tuliskan alasan izin..."
                        required></textarea>
                </div>

                <div class="col-12">
                    <label class="form-label">
                        Surat Izin (PDF)
                        <span class="text-muted small">(wajib untuk Cuti & Sakit)</span>
                    </label>
                    <input type="file" name="file_surat" class="form-control" accept="application/pdf">
                </div>

                <div class="col-12">
                    <button type="submit" name="submit_izin" class="btn btn-primary btn-action">
                        <i class="bi bi-send"></i> Ajukan Izin
                    </button>
                </div>

            </form>

        </div>
    </div>

    <!-- ======================
         RIWAYAT IZIN
    ======================= -->
    <div class="card shadow-sm border-0">
        <div class="card-body">

            <h5 class="fw-semibold mb-3">Riwayat Izin</h5>

            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle text-center small">
                    <thead class="table-light">
                        <tr>
                            <th>Tanggal</th>
                            <th>Jenis</th>
                            <th>Alasan</th>
                            <th>Surat</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>

                        <?php if (mysqli_num_rows($dataIzin) == 0): ?>
                            <tr>
                                <td colspan="5" class="text-muted">
                                    Belum ada pengajuan izin
                                </td>
                            </tr>
                        <?php endif; ?>

                        <?php while ($row = mysqli_fetch_assoc($dataIzin)): ?>
                            <tr>
                                <td>
                                    <?= date('d-m-Y', strtotime($row['tanggal_mulai'])) ?>
                                    <?php if ($row['tanggal_mulai'] != $row['tanggal_selesai']): ?>
                                        <br> s/d
                                        <?= date('d-m-Y', strtotime($row['tanggal_selesai'])) ?>
                                    <?php endif; ?>
                                </td>
                                <td><?= ucfirst($row['jenis']) ?></td>
                                <td class="text-start"><?= htmlspecialchars($row['alasan']) ?></td>
                                <td>
                                    <?php if ($row['file_surat']): ?>
                                        <button class="btn btn-sm btn-outline-primary"
                                            onclick="previewPdf('../<?= $row['file_surat'] ?>')">
                                            Lihat
                                        </button>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge
                                        <?= $row['status'] == 'pending' ? 'bg-warning text-dark' :
                                            ($row['status'] == 'disetujui' ? 'bg-success' : 'bg-danger') ?>">
                                        <?= ucfirst($row['status']) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endwhile; ?>

                    </tbody>
                </table>
            </div>

        </div>
    </div>

</div>

<!-- ======================
     MODAL PREVIEW PDF
====================== -->
<div class="modal fade" id="modalPdf" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-absen">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Preview Surat Izin</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <iframe id="pdfFrame" src="" style="width:100%; height:420px;" frameborder="0"></iframe>
            </div>
        </div>
    </div>
</div>

<script>
    function previewPdf(src) {
        document.getElementById('pdfFrame').src = src;
        new bootstrap.Modal(document.getElementById('modalPdf')).show();
    }
</script>

<?php
$content = ob_get_clean();
require_once 'layout.php';
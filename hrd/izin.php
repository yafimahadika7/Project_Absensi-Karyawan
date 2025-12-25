<?php
require_once '../config/auth.php';
require_once '../config/koneksi.php';

auth_guard();
role_guard('hrd');

$page_title = "Approval Izin Karyawan";

/* ======================
   PROSES APPROVAL
====================== */
if (isset($_POST['aksi'], $_POST['id'])) {

    $id = (int) $_POST['id'];
    $aksi = $_POST['aksi']; // setujui | tolak

    if ($aksi === 'setujui') {

        // Ambil data izin
        $q = mysqli_query($koneksi, "
        SELECT user_id, tanggal_mulai, tanggal_selesai, jenis
        FROM izin
        WHERE id = '$id'
    ");
        $izin = mysqli_fetch_assoc($q);

        // Update status izin
        mysqli_query($koneksi, "
        UPDATE izin
        SET status = 'disetujui'
        WHERE id = '$id'
    ");

        // Loop tanggal izin → isi absensi
        $start = new DateTime($izin['tanggal_mulai']);
        $end = (new DateTime($izin['tanggal_selesai']))->modify('+1 day');

        $periode = new DatePeriod($start, new DateInterval('P1D'), $end);

        foreach ($periode as $tgl) {
            $tanggal = $tgl->format('Y-m-d');

            // Cek apakah absensi sudah ada
            $cek = mysqli_query($koneksi, "
            SELECT id FROM absensi
            WHERE user_id = '{$izin['user_id']}'
            AND tanggal = '$tanggal'
        ");

            if (mysqli_num_rows($cek) === 0) {
                // Insert absensi izin
                mysqli_query($koneksi, "
                INSERT INTO absensi (user_id, tanggal, status)
                VALUES (
                    '{$izin['user_id']}',
                    '$tanggal',
                    '{$izin['jenis']}'
                )
            ");
            } else {
                // Update jika sudah ada
                mysqli_query($koneksi, "
                UPDATE absensi
                SET status = '{$izin['jenis']}'
                WHERE user_id = '{$izin['user_id']}'
                AND tanggal = '$tanggal'
            ");
            }
        }

    } elseif ($aksi === 'tolak') {

        mysqli_query($koneksi, "
        UPDATE izin
        SET status = 'ditolak'
        WHERE id = '$id'
    ");
    }

    header("Location: izin.php");
    exit;
}

/* ======================
   DATA IZIN (PENDING)
====================== */
$izin = mysqli_query($koneksi, "
    SELECT
        i.id,
        i.tanggal_mulai,
        i.tanggal_selesai,
        i.jenis,
        i.alasan,
        i.file_surat,
        i.created_at,
        u.nama
    FROM izin i
    JOIN users u ON i.user_id = u.id
    WHERE i.status = 'pending'
    ORDER BY i.created_at DESC
");

ob_start();
?>

<style>
    /* Modal izin HRD */
    .modal-izin {
        max-width: 600px;
    }

    /* Area preview PDF */
    .pdf-preview {
        height: 350px;
        overflow: hidden;
        border: 1px solid #dee2e6;
        border-radius: 8px;
    }

    /* Mobile */
    @media (max-width: 576px) {
        .modal-izin {
            max-width: 95%;
        }

        .pdf-preview {
            height: 260px;
        }
    }
</style>

<div class="container-fluid">

    <div class="alert alert-warning shadow-sm">
        <i class="bi bi-info-circle me-1"></i>
        Daftar pengajuan izin karyawan yang menunggu persetujuan HRD.
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">

            <h5 class="fw-semibold mb-3">Approval Izin</h5>

            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle text-center small">
                    <thead class="table-light">
                        <tr>
                            <th>Periode Izin</th>
                            <th>Nama</th>
                            <th>Jenis</th>
                            <th>Detail</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>

                        <?php if (mysqli_num_rows($izin) === 0): ?>
                            <tr>
                                <td colspan="5" class="text-muted">
                                    Tidak ada pengajuan izin menunggu persetujuan
                                </td>
                            </tr>
                        <?php endif; ?>

                        <?php while ($i = mysqli_fetch_assoc($izin)): ?>
                            <?php
                            $badge = match ($i['jenis']) {
                                'cuti' => 'bg-primary',
                                'sakit' => 'bg-danger',
                                default => 'bg-warning text-dark'
                            };
                            ?>
                            <tr>
                                <td>
                                    <?= date('d-m-Y', strtotime($i['tanggal_mulai'])) ?>
                                    s/d
                                    <?= date('d-m-Y', strtotime($i['tanggal_selesai'])) ?>
                                </td>

                                <td><?= htmlspecialchars($i['nama']) ?></td>

                                <td>
                                    <span class="badge <?= $badge ?>">
                                        <?= strtoupper($i['jenis']) ?>
                                    </span>
                                </td>

                                <td>
                                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal"
                                        data-bs-target="#detailIzinModal" data-nama="<?= htmlspecialchars($i['nama']) ?>"
                                        data-jenis="<?= strtoupper($i['jenis']) ?>"
                                        data-periode="<?= date('d-m-Y', strtotime($i['tanggal_mulai'])) . ' s/d ' . date('d-m-Y', strtotime($i['tanggal_selesai'])) ?>"
                                        data-alasan="<?= htmlspecialchars($i['alasan']) ?>"
                                        data-file="<?= htmlspecialchars($i['file_surat']) ?>">
                                        <i class="bi bi-eye"></i> Lihat
                                    </button>
                                </td>

                                <td class="text-center">
                                    <!-- Tombol buka modal (JANGAN di dalam form) -->
                                    <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal"
                                        data-bs-target="#approveModal" data-id="<?= $i['id'] ?>" data-aksi="setujui"
                                        data-nama="<?= htmlspecialchars($i['nama']) ?>"
                                        data-jenis="<?= strtoupper($i['jenis']) ?>"
                                        data-periode="<?= date('d-m-Y', strtotime($i['tanggal_mulai'])) . ' s/d ' . date('d-m-Y', strtotime($i['tanggal_selesai'])) ?>">
                                        <i class="bi bi-check-circle"></i>
                                    </button>

                                    <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal"
                                        data-bs-target="#approveModal" data-id="<?= $i['id'] ?>" data-aksi="tolak"
                                        data-nama="<?= htmlspecialchars($i['nama']) ?>"
                                        data-jenis="<?= strtoupper($i['jenis']) ?>"
                                        data-periode="<?= date('d-m-Y', strtotime($i['tanggal_mulai'])) . ' s/d ' . date('d-m-Y', strtotime($i['tanggal_selesai'])) ?>">
                                        <i class="bi bi-x-circle"></i>
                                    </button>
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
     MODAL DETAIL + PREVIEW PDF
====================== -->
<div class="modal fade" id="detailIzinModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">Detail & Preview Izin</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">

                <table class="table table-sm table-borderless mb-3">
                    <tr>
                        <th width="120">Nama</th>
                        <td>: <span id="mNama"></span></td>
                    </tr>
                    <tr>
                        <th>Jenis</th>
                        <td>: <span id="mJenis"></span></td>
                    </tr>
                    <tr>
                        <th>Periode</th>
                        <td>: <span id="mPeriode"></span></td>
                    </tr>
                </table>

                <p class="fw-semibold mb-1">Alasan</p>
                <div id="mAlasan" class="bg-light p-2 rounded small mb-3"></div>

                <div class="ratio ratio-16x9 border rounded">
                    <iframe id="mPreview" src="" style="border:0" allowfullscreen>
                    </iframe>
                </div>

            </div>

        </div>
    </div>
</div>

<div class="modal fade" id="approveModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">

            <div class="modal-header py-2">
                <h6 class="modal-title" id="approveTitle">Konfirmasi</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <form method="post">
                <div class="modal-body text-center small">

                    <p class="fw-semibold mb-1" id="aNama"></p>
                    <span class="badge mb-2" id="aJenis"></span>
                    <p class="text-muted mb-3" id="aPeriode"></p>

                    <input type="hidden" name="id" id="aId">
                    <input type="hidden" name="aksi" id="aAksi">

                    <div id="approveText" class="mb-3"></div>

                    <button type="submit" class="btn w-100" id="aButton">
                        Konfirmasi
                    </button>

                </div>
            </form>

        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {

        const izinModal = document.getElementById('detailIzinModal');
        if (!izinModal) return; // safety

        const mNama = document.getElementById('mNama');
        const mJenis = document.getElementById('mJenis');
        const mPeriode = document.getElementById('mPeriode');
        const mAlasan = document.getElementById('mAlasan');
        const mPreview = document.getElementById('mPreview');

        /* ======================
           SAAT MODAL DIBUKA
        ====================== */
        izinModal.addEventListener('show.bs.modal', function (event) {

            const btn = event.relatedTarget;
            if (!btn) return;

            // Ambil data dengan fallback
            const nama = btn.dataset.nama ?? '-';
            const jenis = btn.dataset.jenis ?? '-';
            const periode = btn.dataset.periode ?? '-';
            const alasan = btn.dataset.alasan ?? '-';
            const file = btn.dataset.file ?? '';

            // Isi teks
            mNama.textContent = nama;
            mJenis.textContent = jenis;
            mPeriode.textContent = periode;
            mAlasan.textContent = alasan;

            // Preview PDF (path mengikuti isi database)
            if (file.trim() !== '') {
                mPreview.src = '../' + file + '#toolbar=0&navpanes=0';
            } else {
                mPreview.removeAttribute('src');
            }
        });

        /* ======================
           SAAT MODAL DITUTUP
        ====================== */
        izinModal.addEventListener('hidden.bs.modal', function () {
            // Hentikan loading PDF sepenuhnya
            mPreview.removeAttribute('src');
        });

    });
</script>

<script>
    document.addEventListener('DOMContentLoaded', function () {

        const approveModal = document.getElementById('approveModal');
        if (!approveModal) return;

        const aNama = document.getElementById('aNama');
        const aJenis = document.getElementById('aJenis');
        const aPeriode = document.getElementById('aPeriode');
        const aId = document.getElementById('aId');
        const aAksi = document.getElementById('aAksi');
        const aButton = document.getElementById('aButton');
        const approveText = document.getElementById('approveText');

        approveModal.addEventListener('show.bs.modal', function (event) {

            const btn = event.relatedTarget;
            if (!btn) return;

            const id = btn.dataset.id;
            const aksi = btn.dataset.aksi;
            const nama = btn.dataset.nama;
            const jenis = btn.dataset.jenis;
            const periode = btn.dataset.periode;

            // Isi data
            aNama.textContent = nama;
            aJenis.textContent = jenis;
            aPeriode.textContent = periode;
            aId.value = id;
            aAksi.value = aksi;

            // Badge jenis
            aJenis.className = 'badge mb-2 ' +
                (jenis === 'CUTI' ? 'bg-primary' :
                    jenis === 'SAKIT' ? 'bg-danger' :
                        'bg-warning text-dark');

            // Konfigurasi tombol & teks
            if (aksi === 'setujui') {
                approveText.textContent = 'Apakah Anda yakin ingin MENYETUJUI izin ini?';
                aButton.className = 'btn btn-success w-100';
                aButton.innerHTML = '<i class="bi bi-check-circle me-1"></i> Setujui';
            } else {
                approveText.textContent = 'Apakah Anda yakin ingin MENOLAK izin ini?';
                aButton.className = 'btn btn-danger w-100';
                aButton.innerHTML = '<i class="bi bi-x-circle me-1"></i> Tolak';
            }
        });

    });
</script>

<?php
$content = ob_get_clean();
require_once 'layout.php';

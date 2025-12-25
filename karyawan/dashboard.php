<?php
require_once '../config/auth.php';
require_once '../config/koneksi.php';

auth_guard();
role_guard('karyawan');

$page_title = "Dashboard Karyawan";

// (Opsional) ambil data absensi hari ini untuk ditampilkan di box
$user_id = $_SESSION['user_id'] ?? null;
$tanggal = date('Y-m-d');

$statusHariIni = "Belum Absen";
$jamMasuk = "--";
$jamPulang = "--";

if ($user_id) {
    $q = mysqli_query($koneksi, "SELECT * FROM absensi WHERE user_id='$user_id' AND tanggal='$tanggal' LIMIT 1");
    if ($q && mysqli_num_rows($q) > 0) {
        $row = mysqli_fetch_assoc($q);

        if (!empty($row['jam_masuk'])) {
            $statusHariIni = "Sudah Absen Masuk";
            $jamMasuk = date('H:i:s', strtotime($row['jam_masuk']));
        }
        if (!empty($row['jam_pulang'])) {
            $statusHariIni = "Sudah Absen Pulang";
            $jamPulang = date('H:i:s', strtotime($row['jam_pulang']));
        }
        // Jika jam masuk ada tapi pulang belum
        if (!empty($row['jam_masuk']) && empty($row['jam_pulang'])) {
            $statusHariIni = "Menunggu Absen Pulang";
        }
    }
}

ob_start();
?>

<style>
    /* =============================
   MODAL ABSENSI
============================= */
    .modal-absen {
        max-width: 420px;
    }

    /* =============================
   TOMBOL AKSI CEPAT
============================= */
    .btn-action {
        min-width: 180px;
        padding: 0.6rem 1.2rem;
        font-weight: 500;
    }

    /* =============================
   MOBILE FIRST
============================= */
    @media (max-width: 576px) {

        /* Modal */
        .modal-absen {
            max-width: 95%;
        }

        /* Card info */
        .card-body h5 {
            font-size: 1.2rem;
        }

        /* Alert */
        .alert {
            font-size: 0.9rem;
        }

        /* Tombol aksi (khusus dashboard) */
        .btn-action {
            width: 100%;
            padding: 0.9rem;
            font-size: 1rem;
        }

        /* Modal body */
        .modal-body {
            padding: 0.75rem;
        }

        /* Video kamera */
        video {
            max-height: 240px;
            object-fit: cover;
        }
    }

    /* =============================
   DESKTOP
============================= */
    @media (min-width: 768px) {
        .btn-action {
            min-width: 200px;
        }
    }
</style>

<div class="container-fluid">

    <!-- INFO -->
    <div class="alert alert-info shadow-sm">
        <i class="bi bi-info-circle me-2"></i>
        Selamat datang, <strong><?= htmlspecialchars($_SESSION['nama']); ?></strong>.
        Silakan lakukan absensi sesuai jadwal kerja Anda.
    </div>

    <!-- STAT BOX -->
    <div class="row">

        <div class="col-12 col-md-4">
            <div class="card shadow-sm mb-4 border-0">
                <div class="card-body">
                    <h6 class="text-muted">Status Hari Ini</h6>
                    <h5 class="fw-bold text-success"><?= htmlspecialchars($statusHariIni) ?></h5>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-4">
            <div class="card shadow-sm mb-4 border-0">
                <div class="card-body">
                    <h6 class="text-muted">Jam Masuk</h6>
                    <h5 class="fw-bold"><?= htmlspecialchars($jamMasuk) ?></h5>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-4">
            <div class="card shadow-sm mb-4 border-0">
                <div class="card-body">
                    <h6 class="text-muted">Jam Pulang</h6>
                    <h5 class="fw-bold"><?= htmlspecialchars($jamPulang) ?></h5>
                </div>
            </div>
        </div>

    </div>

    <!-- QUICK ACTION -->
    <div class="card shadow-sm border-0">
        <div class="card-body">
            <h6 class="mb-3">Aksi Cepat</h6>

            <div class="d-flex flex-column flex-md-row gap-2">

                <button type="button" class="btn btn-success btn-action" data-bs-toggle="modal"
                    data-bs-target="#modalAbsenMasuk">
                    <i class="bi bi-box-arrow-in-right me-1"></i>
                    Absen Masuk
                </button>

                <button type="button" class="btn btn-outline-danger btn-action" data-bs-toggle="modal"
                    data-bs-target="#modalAbsenPulang">
                    <i class="bi bi-box-arrow-left me-1"></i>
                    Absen Pulang
                </button>

            </div>

        </div>
    </div>

</div>

<!-- =========================
     MODAL ABSEN MASUK
========================= -->
<div class="modal fade" id="modalAbsenMasuk" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-md modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">Absen Masuk</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <div class="alert alert-secondary py-2 small mb-2">
                    <i class="bi bi-geo-alt me-1"></i>
                    Lokasi: <span id="lokasiMasukText">Mendeteksi...</span>
                </div>

                <video id="videoMasuk" class="w-100 rounded border" autoplay playsinline
                    style="max-height:300px; object-fit:cover;">
                </video>
                <canvas id="canvasMasuk" class="d-none"></canvas>

                <form id="formMasuk">
                    <input type="hidden" name="tipe" value="masuk">
                    <input type="hidden" name="foto" id="fotoMasuk">
                    <input type="hidden" name="latitude" id="latMasuk">
                    <input type="hidden" name="longitude" id="lngMasuk">
                </form>

                <small class="text-muted d-block mt-2">
                    Pastikan wajah terlihat jelas dan izin kamera/lokasi diaktifkan.
                </small>
            </div>

            <div class="modal-footer">
                <button class="btn btn-success" id="btnMasuk" type="button">
                    <i class="bi bi-camera me-1"></i> Ambil & Kirim Absen Masuk
                </button>
            </div>

        </div>
    </div>
</div>

<!-- =========================
     MODAL ABSEN PULANG
========================= -->
<div class="modal fade" id="modalAbsenPulang" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-md modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">Absen Pulang</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <div class="alert alert-secondary py-2 small mb-2">
                    <i class="bi bi-geo-alt me-1"></i>
                    Lokasi: <span id="lokasiPulangText">Mendeteksi...</span>
                </div>

                <video id="videoPulang" class="w-100 rounded" autoplay playsinline></video>
                <canvas id="canvasPulang" class="d-none"></canvas>

                <form id="formPulang">
                    <input type="hidden" name="tipe" value="pulang">
                    <input type="hidden" name="foto" id="fotoPulang">
                    <input type="hidden" name="latitude" id="latPulang">
                    <input type="hidden" name="longitude" id="lngPulang">
                </form>

                <small class="text-muted d-block mt-2">
                    Pastikan wajah terlihat jelas dan izin kamera/lokasi diaktifkan.
                </small>
            </div>

            <div class="modal-footer">
                <button class="btn btn-danger" id="btnPulang" type="button">
                    <i class="bi bi-camera me-1"></i> Ambil & Kirim Absen Pulang
                </button>
            </div>

        </div>
    </div>
</div>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    let streamMasuk = null;
    let streamPulang = null;

    function startCamera(videoId, tipe) {
        return navigator.mediaDevices.getUserMedia({ video: true, audio: false })
            .then(s => {
                if (tipe === 'masuk') streamMasuk = s;
                if (tipe === 'pulang') streamPulang = s;
                document.getElementById(videoId).srcObject = s;
                return true;
            })
            .catch(() => {
                Swal.fire('Gagal', 'Izin kamera ditolak / tidak tersedia.', 'error');
                return false;
            });
    }

    function stopCamera(tipe) {
        const s = (tipe === 'masuk') ? streamMasuk : streamPulang;
        if (s) s.getTracks().forEach(t => t.stop());
        if (tipe === 'masuk') streamMasuk = null;
        if (tipe === 'pulang') streamPulang = null;
    }

    function getLocation(latInputId, lngInputId, textId) {
        return new Promise((resolve, reject) => {
            if (!navigator.geolocation) {
                reject('Browser tidak mendukung geolocation.');
                return;
            }
            navigator.geolocation.getCurrentPosition(
                pos => {
                    const lat = pos.coords.latitude;
                    const lng = pos.coords.longitude;
                    document.getElementById(latInputId).value = lat;
                    document.getElementById(lngInputId).value = lng;
                    document.getElementById(textId).innerText = lat.toFixed(6) + ', ' + lng.toFixed(6);
                    resolve({ lat, lng });
                },
                () => reject('Izin lokasi ditolak / gagal mendeteksi lokasi.'),
                { enableHighAccuracy: true, timeout: 15000 }
            );
        });
    }

    function captureToBase64(videoId, canvasId) {
        const video = document.getElementById(videoId);
        const canvas = document.getElementById(canvasId);
        const ctx = canvas.getContext('2d');

        canvas.width = video.videoWidth || 640;
        canvas.height = video.videoHeight || 480;
        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

        return canvas.toDataURL('image/png');
    }

    async function submitAbsen(tipe) {
        const isMasuk = (tipe === 'masuk');

        const formId = isMasuk ? 'formMasuk' : 'formPulang';
        const videoId = isMasuk ? 'videoMasuk' : 'videoPulang';
        const canvasId = isMasuk ? 'canvasMasuk' : 'canvasPulang';
        const fotoInputId = isMasuk ? 'fotoMasuk' : 'fotoPulang';

        // pastikan lokasi ada
        const lat = document.querySelector('#' + formId + ' input[name="latitude"]').value;
        const lng = document.querySelector('#' + formId + ' input[name="longitude"]').value;
        if (!lat || !lng) {
            Swal.fire('Lokasi belum siap', 'Tunggu lokasi terdeteksi dulu, lalu coba lagi.', 'warning');
            return;
        }

        // capture foto
        const base64 = captureToBase64(videoId, canvasId);
        document.getElementById(fotoInputId).value = base64;

        const fd = new FormData(document.getElementById(formId));

        const btn = isMasuk ? document.getElementById('btnMasuk') : document.getElementById('btnPulang');
        btn.disabled = true;

        Swal.fire({
            title: 'Memproses...',
            text: 'Mohon tunggu',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });

        try {
            const res = await fetch('process_absen.php', { method: 'POST', body: fd });
            const json = await res.json();

            if (!json.success) {
                Swal.fire('Gagal', json.message || 'Terjadi kesalahan.', 'error');
            } else {
                Swal.fire('Berhasil', json.message || 'Absensi berhasil.', 'success')
                    .then(() => location.reload());
            }
        } catch (e) {
            Swal.fire('Gagal', 'Tidak bisa terhubung ke server.', 'error');
        } finally {
            btn.disabled = false;
        }
    }

    // ====== Hook modal events ======
    document.getElementById('modalAbsenMasuk').addEventListener('shown.bs.modal', async () => {
        const camOk = await startCamera('videoMasuk', 'masuk');
        if (camOk) {
            getLocation('latMasuk', 'lngMasuk', 'lokasiMasukText')
                .catch(msg => Swal.fire('Gagal', msg, 'error'));
        }
    });
    document.getElementById('modalAbsenMasuk').addEventListener('hidden.bs.modal', () => stopCamera('masuk'));

    document.getElementById('modalAbsenPulang').addEventListener('shown.bs.modal', async () => {
        const camOk = await startCamera('videoPulang', 'pulang');
        if (camOk) {
            getLocation('latPulang', 'lngPulang', 'lokasiPulangText')
                .catch(msg => Swal.fire('Gagal', msg, 'error'));
        }
    });
    document.getElementById('modalAbsenPulang').addEventListener('hidden.bs.modal', () => stopCamera('pulang'));

    // tombol submit
    document.getElementById('btnMasuk').addEventListener('click', () => submitAbsen('masuk'));
    document.getElementById('btnPulang').addEventListener('click', () => submitAbsen('pulang'));
</script>

<?php
$content = ob_get_clean();
require_once 'layout.php';

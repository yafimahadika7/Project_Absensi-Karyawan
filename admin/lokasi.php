<?php
require_once '../config/koneksi.php';
require_once '../config/auth.php';

auth_guard();
role_guard('admin');

$page_title = "Lokasi Kantor";

/* ======================
   TAMBAH LOKASI
====================== */
if (isset($_POST['tambah'])) {
    $nama    = $_POST['nama'];
    $lat     = $_POST['latitude'];
    $lng     = $_POST['longitude'];
    $radius  = $_POST['radius'];
    $alamat  = $_POST['alamat'];

    mysqli_query($koneksi, "
        INSERT INTO lokasi_kantor 
        (nama_lokasi, latitude, longitude, radius_meter, alamat)
        VALUES 
        ('$nama','$lat','$lng','$radius','$alamat')
    ");

    header("Location: lokasi.php");
    exit;
}

/* ======================
   EDIT LOKASI
====================== */
if (isset($_POST['edit'])) {
    $id      = $_POST['id'];
    $nama    = $_POST['nama'];
    $lat     = $_POST['latitude'];
    $lng     = $_POST['longitude'];
    $radius  = $_POST['radius'];
    $alamat  = $_POST['alamat'];

    mysqli_query($koneksi, "
        UPDATE lokasi_kantor SET
            nama_lokasi   = '$nama',
            latitude      = '$lat',
            longitude     = '$lng',
            radius_meter  = '$radius',
            alamat        = '$alamat'
        WHERE id = '$id'
    ");

    header("Location: lokasi.php");
    exit;
}

/* ======================
   HAPUS LOKASI
====================== */
if (isset($_GET['hapus'])) {
    mysqli_query($koneksi, "DELETE FROM lokasi_kantor WHERE id='$_GET[hapus]'");
    header("Location: lokasi.php");
    exit;
}

/* ======================
   DATA
====================== */
$lokasi = mysqli_query($koneksi, "SELECT * FROM lokasi_kantor ORDER BY id DESC");

/* ======================
   START BUFFER
====================== */
ob_start();
?>

<!-- ===================== CONTENT ===================== -->

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">Lokasi Kantor</h5>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambah">
        <i class="bi bi-geo-alt"></i> Tambah Lokasi
    </button>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body">
        <table class="table align-middle text-center">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Nama Lokasi</th>
                    <th>Latitude</th>
                    <th>Longitude</th>
                    <th>Radius (m)</th>
                    <th width="120">Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php $no=1; while($l=mysqli_fetch_assoc($lokasi)): ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td><?= $l['nama_lokasi'] ?></td>
                    <td><?= $l['latitude'] ?></td>
                    <td><?= $l['longitude'] ?></td>
                    <td><?= $l['radius_meter'] ?> m</td>
                    <td>
                        <div class="d-flex justify-content-center gap-2">
                            <button class="btn btn-sm btn-warning"
                                data-bs-toggle="modal"
                                data-bs-target="#edit<?= $l['id'] ?>">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn btn-sm btn-danger"
                                data-bs-toggle="modal"
                                data-bs-target="#hapus<?= $l['id'] ?>">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
mysqli_data_seek($lokasi, 0);
while ($l = mysqli_fetch_assoc($lokasi)):
?>

<!-- ===================== MODAL EDIT ===================== -->
<div class="modal fade" id="edit<?= $l['id'] ?>" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" class="modal-content">
            <div class="modal-header">
                <h5>Edit Lokasi</h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">

                <input type="hidden" name="id" value="<?= $l['id'] ?>">

                <input type="text" name="nama" class="form-control mb-2"
                    value="<?= $l['nama_lokasi'] ?>" required>

                <input type="text" name="latitude" class="form-control mb-2"
                    value="<?= $l['latitude'] ?>" required>

                <input type="text" name="longitude" class="form-control mb-2"
                    value="<?= $l['longitude'] ?>" required>

                <input type="number" name="radius" class="form-control mb-2"
                    value="<?= $l['radius_meter'] ?>" required>

                <textarea name="alamat" class="form-control"
                    placeholder="Alamat"><?= $l['alamat'] ?></textarea>

            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button class="btn btn-warning" name="edit">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- ===================== MODAL HAPUS ===================== -->
<div class="modal fade" id="hapus<?= $l['id'] ?>" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form method="GET" class="modal-content">
            <div class="modal-header">
                <h5 class="text-danger">Hapus Lokasi</h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Yakin hapus lokasi <strong><?= $l['nama_lokasi'] ?></strong>?
                <input type="hidden" name="hapus" value="<?= $l['id'] ?>">
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button class="btn btn-danger">Ya, Hapus</button>
            </div>
        </form>
    </div>
</div>

<?php endwhile; ?>

<!-- ===================== MODAL TAMBAH ===================== -->
<div class="modal fade" id="modalTambah" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" class="modal-content">
            <div class="modal-header">
                <h5>Tambah Lokasi Kantor</h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">

                <input type="text" name="nama" class="form-control mb-2"
                    placeholder="Nama Lokasi" required>

                <input type="text" name="latitude" class="form-control mb-2"
                    placeholder="Latitude" required>

                <input type="text" name="longitude" class="form-control mb-2"
                    placeholder="Longitude" required>

                <input type="number" name="radius" class="form-control mb-2"
                    value="100" placeholder="Radius (meter)">

                <textarea name="alamat" class="form-control"
                    placeholder="Alamat"></textarea>

            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button class="btn btn-primary" name="tambah">Simpan</button>
            </div>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once 'layout.php';
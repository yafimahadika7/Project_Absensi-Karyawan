<?php
require_once '../config/koneksi.php';
require_once '../config/auth.php';

auth_guard();
role_guard('admin');

$page_title = "Data Karyawan";

/* ======================
   PROSES TAMBAH
====================== */
if (isset($_POST['tambah'])) {

    $nip            = mysqli_real_escape_string($koneksi, $_POST['nip']);
    $nama           = mysqli_real_escape_string($koneksi, $_POST['nama']);
    $username       = $nip; // username = NIP
    $jenis_kelamin  = $_POST['jenis_kelamin'];
    $no_hp          = mysqli_real_escape_string($koneksi, $_POST['no_hp']);
    $alamat         = mysqli_real_escape_string($koneksi, $_POST['alamat']);
    $departemen     = (int) $_POST['departemen'];
    $jabatan        = mysqli_real_escape_string($koneksi, $_POST['jabatan']);
    $tanggal_masuk  = $_POST['tanggal_masuk'];
    $password       = $_POST['password'];
    $password2      = $_POST['password2'];

    if ($password !== $password2) {
        echo "<script>alert('Password dan Ulangi Password tidak sama');history.back();</script>";
        exit;
    }

    // cek NIP duplicate
    $cek = mysqli_query($koneksi, "SELECT id FROM karyawan WHERE nip='$nip'");
    if (mysqli_num_rows($cek) > 0) {
        echo "<script>alert('NIP sudah terdaftar');history.back();</script>";
        exit;
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);

    // insert users
    mysqli_query($koneksi, "
        INSERT INTO users (nama, username, password, role, status)
        VALUES ('$nama','$username','$hash','karyawan','aktif')
    ");

    $user_id = mysqli_insert_id($koneksi);

    // insert karyawan
    mysqli_query($koneksi, "
        INSERT INTO karyawan
        (user_id, nip, jenis_kelamin, departemen_id, jabatan, no_hp, alamat, tanggal_masuk, status)
        VALUES
        ('$user_id','$nip','$jenis_kelamin','$departemen','$jabatan','$no_hp','$alamat','$tanggal_masuk','aktif')
    ");

    header("Location: karyawan.php");
    exit;
}

/* ======================
   PROSES EDIT
====================== */
if (isset($_POST['edit'])) {

    $id             = (int) $_POST['id'];
    $nama           = mysqli_real_escape_string($koneksi, $_POST['nama']);
    $departemen     = (int) $_POST['departemen'];
    $jabatan        = mysqli_real_escape_string($koneksi, $_POST['jabatan']);
    $password_baru  = $_POST['password'] ?? '';

    // update data utama
    mysqli_query($koneksi, "
        UPDATE users u
        JOIN karyawan k ON u.id = k.user_id
        SET u.nama='$nama',
            k.departemen_id='$departemen',
            k.jabatan='$jabatan'
        WHERE k.id='$id'
    ");

    // update password jika diisi
    if (!empty($password_baru)) {
        $hash = password_hash($password_baru, PASSWORD_DEFAULT);
        mysqli_query($koneksi, "
            UPDATE users u
            JOIN karyawan k ON u.id = k.user_id
            SET u.password='$hash'
            WHERE k.id='$id'
        ");
    }

    header("Location: karyawan.php");
    exit;
}

/* ======================
   PROSES HAPUS
====================== */
if (isset($_GET['hapus'])) {

    $id = (int) $_GET['hapus'];

    $q = mysqli_query($koneksi, "SELECT user_id FROM karyawan WHERE id='$id'");
    $u = mysqli_fetch_assoc($q);

    if ($u) {
        mysqli_query($koneksi, "DELETE FROM users WHERE id='{$u['user_id']}'");
    }

    header("Location: karyawan.php");
    exit;
}

/* ======================
   PROSES IMPORT CSV (FIXED)
====================== */
if (isset($_POST['import'])) {

    $file = $_FILES['file_import']['tmp_name'];

    if (!empty($file)) {

        $handle = fopen($file, "r");

        // skip header
        fgetcsv($handle, 1000, ",");

        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {

            // VALIDASI JUMLAH KOLOM
            if (count($data) < 9) {
                continue; // skip baris rusak
            }

            $nip            = trim($data[0]);
            $nama           = trim($data[1]);
            $jenis_kelamin  = trim($data[2]);
            $no_hp          = trim($data[3]);
            $alamat         = trim($data[4]);
            $departemen     = (int) trim($data[5]);
            $jabatan        = trim($data[6]);
            $tanggal_masuk  = trim($data[7]);
            $password_plain = trim($data[8]);

            // SKIP JIKA NIP ATAU PASSWORD KOSONG
            if (empty($nip) || empty($password_plain)) {
                continue;
            }

            // CEK DUPLIKAT USERNAME
            $cek = mysqli_query($koneksi, "
                SELECT id FROM users WHERE username='$nip'
            ");
            if (mysqli_num_rows($cek) > 0) {
                continue;
            }

            $password = password_hash($password_plain, PASSWORD_DEFAULT);

            // INSERT USERS
            mysqli_query($koneksi, "
                INSERT INTO users (nama, username, password, role, status)
                VALUES ('$nama','$nip','$password','karyawan','aktif')
            ");

            $user_id = mysqli_insert_id($koneksi);

            // INSERT KARYAWAN
            mysqli_query($koneksi, "
                INSERT INTO karyawan
                (user_id, nip, jenis_kelamin, departemen_id, jabatan, no_hp, alamat, tanggal_masuk, status)
                VALUES
                ('$user_id','$nip','$jenis_kelamin','$departemen','$jabatan','$no_hp','$alamat','$tanggal_masuk','aktif')
            ");
        }

        fclose($handle);
    }

    header("Location: karyawan.php");
    exit;
}

// SORTING
$allowedSort = [
    'nama' => 'u.nama',
    'username' => 'u.username',
    'departemen' => 'd.nama_departemen',
    'jabatan' => 'k.jabatan'
];

$sort = $_GET['sort'] ?? 'nama';
$order = $_GET['order'] ?? 'asc';

$sortColumn = $allowedSort[$sort] ?? 'u.nama';
$orderSQL = ($order === 'desc') ? 'DESC' : 'ASC';

// PAGINATION
$perPage = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
$perPage = in_array($perPage, [10,25,50]) ? $perPage : 50;

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max($page, 1);

$offset = ($page - 1) * $perPage;

/* ======================
   DATA (SEARCH + FILTER)
====================== */
$where = [];

if (!empty($_GET['q'])) {
    $q = mysqli_real_escape_string($koneksi, $_GET['q']);
    $where[] = "(u.nama LIKE '%$q%' OR k.nip LIKE '%$q%' OR u.username LIKE '%$q%')";
}

if (!empty($_GET['departemen'])) {
    $dep = (int) $_GET['departemen'];
    $where[] = "k.departemen_id = $dep";
}

$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// TOTAL DATA
$totalQuery = mysqli_query($koneksi, "
    SELECT COUNT(*) as total
    FROM karyawan k
    JOIN users u ON k.user_id = u.id
    JOIN departemen d ON k.departemen_id = d.id
    $whereSQL
");
$totalData = mysqli_fetch_assoc($totalQuery)['total'];
$totalPage = ceil($totalData / $perPage);


$karyawan = mysqli_query($koneksi, "
    SELECT 
        k.id,
        k.nip,
        k.jenis_kelamin,
        k.no_hp,
        k.alamat,
        k.tanggal_masuk,
        k.departemen_id,
        k.jabatan,
        u.nama,
        u.username,
        d.nama_departemen
    FROM karyawan k
    JOIN users u ON k.user_id = u.id
    JOIN departemen d ON k.departemen_id = d.id
    $whereSQL
    ORDER BY $sortColumn $orderSQL
    LIMIT $offset, $perPage
");

$departemen = mysqli_query($koneksi, "SELECT * FROM departemen");

ob_start();
?>

<style>
    th a.sort-link {
        color: #000 !important;          /* hitam */
        text-decoration: none !important;/* hilangkan garis bawah */
        font-weight: 600;
    }

    th a.sort-link:hover {
        color: #000;                     /* tetap hitam saat hover */
        text-decoration: none;
    }

    /* ===== TABLE FOOTER ===== */
.table-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 6px 4px;
    font-size: 13px;
    color: #6b7280;
}

/* LEFT */
.pagination-left {
    display: flex;
    gap: 6px;
}

.page-btn {
    border: 1px solid #e5e7eb;
    background: #fff;
    padding: 4px 10px;
    border-radius: 6px;
    cursor: pointer;
    font-size: 13px;
}

.page-btn.active {
    background: #111827;
    color: #fff;
    border-color: #111827;
}

.page-btn:disabled {
    opacity: .4;
    cursor: not-allowed;
}

/* CENTER */
.pagination-info {
    font-weight: 500;
}

/* RIGHT */
.pagination-limit {
    display: flex;
    align-items: center;
    gap: 6px;
}

.pagination-limit select {
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    padding: 4px 8px;
    font-size: 13px;
}

</style>

<div class="d-flex justify-content-between mb-3">
    <h5>Data Karyawan</h5>
</div>

<form method="GET" class="row g-2 align-items-center mb-3">

    <!-- SEARCH -->
    <div class="col-md-5">
        <input type="text" name="q" class="form-control"
               placeholder="Search by nama / NIP / username"
               value="<?= $_GET['q'] ?? '' ?>">
    </div>

    <!-- FILTER DEPARTEMEN -->
    <div class="col-md-3">
        <select name="departemen" class="form-select">
            <option value="">Semua Departemen</option>
            <?php
            $dep = mysqli_query($koneksi, "SELECT * FROM departemen");
            while ($d = mysqli_fetch_assoc($dep)):
            ?>
            <option value="<?= $d['id'] ?>"
                <?= (($_GET['departemen'] ?? '') == $d['id']) ? 'selected' : '' ?>>
                <?= $d['nama_departemen'] ?>
            </option>
            <?php endwhile; ?>
        </select>
    </div>

    <!-- BUTTON FILTER -->
    <div class="col-md-1">
        <button class="btn btn-outline-secondary w-100">
            <i class="bi bi-funnel"></i>
        </button>
    </div>

    <!-- TAMBAH -->
    <div class="col-md-1">
        <button type="button" class="btn btn-primary w-100"
                data-bs-toggle="modal" data-bs-target="#modalTambah">
            <i class="bi bi-plus"></i>
        </button>
    </div>

    <!-- IMPORT -->
    <div class="col-md-2 text-end">
        <button type="button" class="btn btn-outline-success w-100"
                data-bs-toggle="modal" data-bs-target="#modalImport">
            <i class="bi bi-upload"></i> Import
        </button>
    </div>

</form>

<div class="card shadow-sm border-0">
    <div class="card-body">
        <table class="table table-striped align-middle text-center">
    <thead>
        <tr>
            <th>#</th>
            <th>
                <a class="sort-link"
                href="?sort=nama&order=<?= ($sort=='nama' && $order=='asc') ? 'desc' : 'asc' ?>">
                    Nama
                </a>
            </th>
            <th>
                <a class="sort-link"
                href="?sort=username&order=<?= ($sort=='username' && $order=='asc') ? 'desc' : 'asc' ?>">
                    Kode
                </a>
            </th>
            <th>
                <a class="sort-link"
                href="?sort=departemen&order=<?= ($sort=='departemen' && $order=='asc') ? 'desc' : 'asc' ?>">
                    Departemen
                </a>
            </th>
            <th>
                <a class="sort-link"
                href="?sort=jabatan&order=<?= ($sort=='jabatan' && $order=='asc') ? 'desc' : 'asc' ?>">
                    Jabatan
                </a>
            </th>
            <th>Aksi</th>
        </tr>
    </thead>
    <tbody>
        <?php $no = 1; while ($row = mysqli_fetch_assoc($karyawan)): ?>
        <tr>
            <td><?= $no++ ?></td>
            <td><?= $row['nama'] ?></td>
            <td><?= $row['username'] ?></td>
            <td><?= $row['nama_departemen'] ?></td>
            <td><?= $row['jabatan'] ?></td>
            <td>
                <div class="d-flex justify-content-center gap-2">
                    <button class="btn btn-sm btn-warning"
                        data-bs-toggle="modal"
                        data-bs-target="#modalEdit<?= $row['id'] ?>">
                        <i class="bi bi-pencil"></i>
                    </button>

                    <button class="btn btn-sm btn-danger"
                        data-bs-toggle="modal"
                        data-bs-target="#modalHapus<?= $row['id'] ?>">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>

<div class="table-footer">

    <!-- LEFT: PAGINATION -->
    <div class="pagination-left">
        <button class="page-btn" <?= ($page <= 1) ? 'disabled' : '' ?>
            onclick="location.href='?<?= http_build_query(array_merge($_GET,['page'=>$page-1])) ?>'">
            ‹
        </button>

        <button class="page-btn active"><?= $page ?></button>

        <button class="page-btn" <?= ($page >= $totalPage) ? 'disabled' : '' ?>
            onclick="location.href='?<?= http_build_query(array_merge($_GET,['page'=>$page+1])) ?>'">
            ›
        </button>
    </div>

    <!-- CENTER: INFO -->
    <div class="pagination-info">
        <?= ($offset+1) ?>–<?= min($offset+$perPage, $totalData) ?> / <?= $totalData ?>
    </div>

    <!-- RIGHT: LIMIT -->
    <div class="pagination-limit">
        <span>Show</span>
        <form method="GET">
            <?php foreach ($_GET as $k=>$v): if ($k!='limit'): ?>
                <input type="hidden" name="<?= $k ?>" value="<?= $v ?>">
            <?php endif; endforeach; ?>

            <select name="limit" onchange="this.form.submit()">
                <option <?= $perPage==10?'selected':'' ?>>10</option>
                <option <?= $perPage==25?'selected':'' ?>>25</option>
                <option <?= $perPage==50?'selected':'' ?>>50</option>
            </select>
        </form>
        <span>rows per page</span>
    </div>

</div>

    </div>
</div>

<?php
mysqli_data_seek($karyawan, 0); // ulangi data
while ($row = mysqli_fetch_assoc($karyawan)):
    ?>

    <!-- MODAL EDIT -->
    <div class="modal fade" id="modalEdit<?= $row['id'] ?>" tabindex="-1" data-bs-backdrop="static"
        data-bs-keyboard="false">

        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Karyawan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <input type="hidden" name="id" value="<?= $row['id'] ?>">

                    <div class="mb-2">
                        <label>Nama</label>
                        <input type="text" name="nama" class="form-control" value="<?= $row['nama'] ?>" required>
                    </div>

                    <div class="mb-2">
                        <label>Departemen</label>
                        <select name="departemen" class="form-select">
                            <?php
                            $dep = mysqli_query($koneksi, "SELECT * FROM departemen");
                            while ($d = mysqli_fetch_assoc($dep)):
                                ?>
                                <option value="<?= $d['id'] ?>" <?= $d['nama_departemen'] == $row['nama_departemen'] ? 'selected' : '' ?>>
                                    <?= $d['nama_departemen'] ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="mb-2">
                        <label>Jabatan</label>
                        <input type="text" name="jabatan" class="form-control" value="<?= $row['jabatan'] ?>">
                    </div>

                    <div class="mb-2">
                        <label>Password Baru <small class="text-muted">(Opsional)</small></label>
                        <input type="password" name="password" class="form-control"
                            placeholder="Kosongkan jika tidak ingin mengubah">
                    </div>
                </div>

                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button class="btn btn-warning" name="edit">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL HAPUS -->
    <div class="modal fade" id="modalHapus<?= $row['id'] ?>" tabindex="-1" data-bs-backdrop="static"
        data-bs-keyboard="false">

        <div class="modal-dialog modal-dialog-centered">
            <form method="GET" class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-danger">Hapus Karyawan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <p>
                        Yakin ingin menghapus karyawan
                        <strong><?= $row['nama'] ?></strong>?
                    </p>
                    <p class="text-muted small">
                        Akun login dan data absensi akan ikut terhapus.
                    </p>
                    <input type="hidden" name="hapus" value="<?= $row['id'] ?>">
                </div>

                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button class="btn btn-danger">Ya, Hapus</button>
                </div>
            </form>
        </div>
    </div>

<?php endwhile; ?>

<!-- MODAL TAMBAH -->
<div class="modal fade" id="modalTambah" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form method="POST" class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">Tambah Karyawan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <div class="row g-3">

                    <!-- NIP & Nama -->
                    <div class="col-md-6">
                        <label>NIP</label>
                        <input type="text" name="nip" class="form-control" required>
                    </div>

                    <div class="col-md-6">
                        <label>Nama</label>
                        <input type="text" name="nama" class="form-control" required>
                    </div>

                    <!-- Jenis Kelamin & No HP -->
                    <div class="col-md-6">
                        <label>Jenis Kelamin</label>
                        <select name="jenis_kelamin" class="form-select" required>
                            <option value="">-- Pilih --</option>
                            <option value="L">Laki-laki</option>
                            <option value="P">Perempuan</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label>No HP</label>
                        <input type="text" name="no_hp" class="form-control">
                    </div>

                    <!-- Alamat -->
                    <div class="col-md-12">
                        <label>Alamat</label>
                        <textarea name="alamat" class="form-control" rows="2"></textarea>
                    </div>

                    <!-- Departemen & Jabatan -->
                    <div class="col-md-6">
                        <label>Departemen</label>
                        <select name="departemen" class="form-select" required>
                            <?php
                            $d = mysqli_query($koneksi, "SELECT * FROM departemen");
                            while ($dep = mysqli_fetch_assoc($d)):
                            ?>
                                <option value="<?= $dep['id'] ?>">
                                    <?= $dep['nama_departemen'] ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label>Jabatan</label>
                        <input type="text" name="jabatan" class="form-control">
                    </div>

                    <!-- Tanggal Masuk -->
                    <div class="col-md-6">
                        <label>Tanggal Masuk</label>
                        <input type="date" name="tanggal_masuk" class="form-control" required>
                    </div>

                    <!-- Password -->
                    <div class="col-md-12">
                        <label>Password</label>
                        <div class="input-group">
                            <input type="password" name="password" id="passwordTambah"
                                   class="form-control" required>
                            <span class="input-group-text togglePassword"
                                  data-target="passwordTambah">
                                <i class="bi bi-eye"></i>
                            </span>
                        </div>
                    </div>

                    <!-- Ulangi Password -->
                    <div class="col-md-12">
                        <label>Ulangi Password</label>
                        <div class="input-group">
                            <input type="password" name="password2" id="passwordTambah2"
                                   class="form-control" required>
                            <span class="input-group-text togglePassword"
                                  data-target="passwordTambah2">
                                <i class="bi bi-eye"></i>
                            </span>
                        </div>
                    </div>

                </div>
            </div>

            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button class="btn btn-primary" name="tambah">Simpan</button>
            </div>

        </form>
    </div>
</div>

<!-- MODAL IMPORT -->
<div class="modal fade" id="modalImport">
  <div class="modal-dialog modal-dialog-centered">
    <form method="POST" enctype="multipart/form-data" class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title">Import Data Karyawan</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">

        <div class="alert alert-info small mb-3">
          <strong>Langkah Import:</strong>
          <ol class="mb-1">
            <li>Download file contoh</li>
            <li>Isi data sesuai format</li>
            <li>Upload kembali file CSV</li>
          </ol>
        </div>

        <div class="mb-3">
          <a href="../assets/contoh/contoh_import_karyawan.csv"
             class="btn btn-outline-primary btn-sm"
             download>
            <i class="bi bi-download"></i>
            Download Contoh CSV
          </a>
        </div>

        <div class="mb-3">
          <label class="form-label">Upload File CSV</label>
          <input type="file"
                 name="file_import"
                 class="form-control"
                 accept=".csv"
                 required>
        </div>

      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
          Batal
        </button>
        <button type="submit" name="import" class="btn btn-success">
          <i class="bi bi-upload"></i> Import
        </button>
      </div>

    </form>
  </div>
</div>

<script>
document.querySelectorAll('.togglePassword').forEach(toggle => {
    toggle.addEventListener('click', function () {
        const input = document.getElementById(this.dataset.target);
        const icon = this.querySelector('i');

        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.replace('bi-eye', 'bi-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.replace('bi-eye-slash', 'bi-eye');
        }
    });
});
</script>

<?php
$content = ob_get_clean();
require_once 'layout.php';
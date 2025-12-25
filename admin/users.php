<?php
require_once '../config/koneksi.php';
require_once '../config/auth.php';

auth_guard();
role_guard('admin');

$page_title = "Manajemen User";

/* ======================
   PROSES TAMBAH USER
====================== */
if (isset($_POST['tambah'])) {
    $nama     = $_POST['nama'];
    $username = $_POST['username'];
    $role     = $_POST['role'];
    $password = $_POST['password'];

    $hash = password_hash($password, PASSWORD_DEFAULT);

    mysqli_query($koneksi, "
        INSERT INTO users (nama, username, password, role, status)
        VALUES ('$nama','$username','$hash','$role','aktif')
    ");

    header("Location: users.php");
    exit;
}

/* ======================
   PROSES EDIT USER
====================== */
if (isset($_POST['edit'])) {
    $id   = $_POST['id'];
    $nama = $_POST['nama'];
    $role = $_POST['role'];
    $pwd  = $_POST['password'];

    mysqli_query($koneksi, "
        UPDATE users SET nama='$nama', role='$role'
        WHERE id='$id'
    ");

    if (!empty($pwd)) {
        $hash = password_hash($pwd, PASSWORD_DEFAULT);
        mysqli_query($koneksi, "
            UPDATE users SET password='$hash'
            WHERE id='$id'
        ");
    }

    header("Location: users.php");
    exit;
}

/* ======================
   PROSES HAPUS USER
====================== */
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    mysqli_query($koneksi, "DELETE FROM users WHERE id='$id'");
    header("Location: users.php");
    exit;
}

/* ======================
   DATA USER (ADMIN & HRD)
====================== */
$users = mysqli_query($koneksi, "
    SELECT * FROM users
    WHERE role IN ('admin','hrd')
    ORDER BY role, nama
");

ob_start();
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">Manajemen User</h5>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambah">
        <i class="bi bi-person-plus"></i> Tambah User
    </button>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body">
        <table class="table table-striped align-middle text-center">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Nama</th>
                    <th>Username</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th width="120">Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php $no=1; while($u=mysqli_fetch_assoc($users)): ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td><?= $u['nama'] ?></td>
                    <td><?= $u['username'] ?></td>
                    <td>
                        <span class="badge <?= $u['role']=='admin'?'bg-dark':'bg-info' ?>">
                            <?= strtoupper($u['role']) ?>
                        </span>
                    </td>
                    <td><span class="badge bg-success"><?= $u['status'] ?></span></td>
                    <td>
                        <div class="d-flex justify-content-center gap-2">
                            <button class="btn btn-sm btn-warning"
                                data-bs-toggle="modal"
                                data-bs-target="#edit<?= $u['id'] ?>">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn btn-sm btn-danger"
                                data-bs-toggle="modal"
                                data-bs-target="#hapus<?= $u['id'] ?>">
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
mysqli_data_seek($users, 0);
while($u=mysqli_fetch_assoc($users)):
?>

<!-- MODAL EDIT -->
<div class="modal fade" id="edit<?= $u['id'] ?>" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" class="modal-content">
            <div class="modal-header">
                <h5>Edit User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="id" value="<?= $u['id'] ?>">

                <div class="mb-2">
                    <label>Nama</label>
                    <input type="text" name="nama" class="form-control"
                        value="<?= $u['nama'] ?>" required>
                </div>

                <div class="mb-2">
                    <label>Role</label>
                    <select name="role" class="form-select">
                        <option value="admin" <?= $u['role']=='admin'?'selected':'' ?>>Admin</option>
                        <option value="hrd" <?= $u['role']=='hrd'?'selected':'' ?>>HRD</option>
                    </select>
                </div>

                <div class="mb-2">
                    <label>Password Baru <small class="text-muted">(Opsional)</small></label>
                    <input type="password" name="password" class="form-control"
                        placeholder="Kosongkan jika tidak diubah">
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
<div class="modal fade" id="hapus<?= $u['id'] ?>" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form method="GET" class="modal-content">
            <div class="modal-header">
                <h5 class="text-danger">Hapus User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Yakin hapus user <strong><?= $u['nama'] ?></strong>?
                <input type="hidden" name="hapus" value="<?= $u['id'] ?>">
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button class="btn btn-danger">Ya, Hapus</button>
            </div>
        </form>
    </div>
</div>

<?php endwhile; ?>

<div class="modal fade" id="modalTambah" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" class="modal-content">
            <div class="modal-header">
                <h5>Tambah User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-2">
                    <label>Nama</label>
                    <input type="text" name="nama" class="form-control" required>
                </div>
                <div class="mb-2">
                    <label>Username</label>
                    <input type="text" name="username" class="form-control" required>
                </div>
                <div class="mb-2">
                    <label>Role</label>
                    <select name="role" class="form-select" required>
                        <option value="">-- Pilih Role --</option>
                        <option value="admin">Admin</option>
                        <option value="hrd">HRD</option>
                    </select>
                </div>
                <div class="mb-2">
                    <label>Password</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
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

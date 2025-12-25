<?php
require_once '../config/auth.php';
require_once '../config/koneksi.php';

auth_guard();
role_guard('karyawan');

date_default_timezone_set('Asia/Jakarta');

$page_title = "Profile Saya";

$user_id = $_SESSION['user_id'];

$error = '';
$success = '';

// =======================
// DATA USER
// =======================
$qUser = mysqli_query($koneksi, "
    SELECT nama, username, role
    FROM users
    WHERE id = '$user_id'
    LIMIT 1
");
$user = mysqli_fetch_assoc($qUser);

// =======================
// PROSES GANTI PASSWORD
// =======================
if (isset($_POST['ganti_password'])) {

    $password_lama = $_POST['password_lama'] ?? '';
    $password_baru = $_POST['password_baru'] ?? '';
    $konfirmasi = $_POST['konfirmasi_password'] ?? '';

    if (!$password_lama || !$password_baru || !$konfirmasi) {
        $error = "Semua field password wajib diisi.";
    } elseif ($password_baru !== $konfirmasi) {
        $error = "Password baru dan konfirmasi tidak sama.";
    } elseif (strlen($password_baru) < 6) {
        $error = "Password baru minimal 6 karakter.";
    } else {

        // ambil password lama dari DB
        $qPass = mysqli_query($koneksi, "
            SELECT password
            FROM users
            WHERE id = '$user_id'
            LIMIT 1
        ");
        $rowPass = mysqli_fetch_assoc($qPass);

        if (!password_verify($password_lama, $rowPass['password'])) {
            $error = "Password lama tidak sesuai.";
        } else {

            $hash = password_hash($password_baru, PASSWORD_DEFAULT);

            mysqli_query($koneksi, "
                UPDATE users
                SET password = '$hash'
                WHERE id = '$user_id'
            ");

            $success = "Password berhasil diperbarui.";
        }
    }
}

ob_start();
?>

<div class="container-fluid">

    <!-- ======================
         PROFILE INFO
    ======================= -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">

            <h5 class="fw-semibold mb-3">Profile Saya</h5>

            <div class="row g-3">
                <div class="col-12 col-md-6">
                    <label class="form-label">Nama</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($user['nama']) ?>" readonly>
                </div>

                <div class="col-12 col-md-6">
                    <label class="form-label">Username</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($user['username']) ?>" readonly>
                </div>

                <div class="col-12 col-md-6">
                    <label class="form-label">Role</label>
                    <input type="text" class="form-control" value="<?= ucfirst($user['role']) ?>" readonly>
                </div>
            </div>

        </div>
    </div>

    <!-- ======================
         GANTI PASSWORD
    ======================= -->
    <div class="card shadow-sm border-0">
        <div class="card-body">

            <h5 class="fw-semibold mb-3">Ganti Password</h5>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success"><?= $success ?></div>
            <?php endif; ?>

            <form method="POST" class="row g-3">

                <div class="col-12 col-md-4">
                    <label class="form-label">Password Lama</label>
                    <input type="password" name="password_lama" class="form-control" required>
                </div>

                <div class="col-12 col-md-4">
                    <label class="form-label">Password Baru</label>
                    <input type="password" name="password_baru" class="form-control" required>
                </div>

                <div class="col-12 col-md-4">
                    <label class="form-label">Konfirmasi Password</label>
                    <input type="password" name="konfirmasi_password" class="form-control" required>
                </div>

                <div class="col-12">
                    <button type="submit" name="ganti_password" class="btn btn-primary btn-action">
                        <i class="bi bi-key"></i> Simpan Password
                    </button>
                </div>

            </form>

        </div>
    </div>

</div>

<?php
$content = ob_get_clean();
require_once 'layout.php';

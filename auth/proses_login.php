<?php
require_once '../config/koneksi.php';
require_once '../config/auth.php';

$username = trim($_POST['username'] ?? '');
$password = trim($_POST['password'] ?? '');

/* ===== VALIDASI INPUT ===== */
if ($username === '' || $password === '') {
    header("Location: ../login.php?error=empty");
    exit;
}

/* ===== QUERY USER ===== */
$stmt = mysqli_prepare(
    $koneksi,
    "SELECT * FROM users WHERE username = ? AND status = 'aktif' LIMIT 1"
);
mysqli_stmt_bind_param($stmt, "s", $username);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($user = mysqli_fetch_assoc($result)) {

    if (password_verify($password, $user['password'])) {

        // simpan session
        login($user);

        /* ===== REDIRECT BERDASARKAN ROLE ===== */
        switch ($user['role']) {
            case 'admin':
                header("Location: ../admin/dashboard.php");
                break;

            case 'hrd':
                header("Location: ../hrd/dashboard.php");
                break;

            case 'karyawan':
                header("Location: ../karyawan/dashboard.php");
                break;

            default:
                // fallback aman
                logout();
                break;
        }
        exit;
    }
}

/* ===== LOGIN GAGAL ===== */
header("Location: ../login.php?error=invalid");
exit;

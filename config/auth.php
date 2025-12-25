<?php
session_start();

/* ===== CEK LOGIN ===== */
function is_login()
{
    return isset($_SESSION['login']) && $_SESSION['login'] === true;
}

/* ===== SIMPAN SESSION USER ===== */
function login($user)
{
    $_SESSION['login'] = true;
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['nama'] = $user['nama'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role'] = $user['role'];
}

/* ===== LOGOUT ===== */
function logout()
{
    session_unset();
    session_destroy();
    header("Location: ../login.php");
    exit;
}

/* ===== PROTEKSI HALAMAN ===== */
function auth_guard()
{
    if (!is_login()) {
        header("Location: ../login.php");
        exit;
    }
}

/* ===== PROTEKSI ROLE ===== */
function role_guard($role)
{
    if ($_SESSION['role'] !== $role) {
        die("Akses ditolak");
    }
}

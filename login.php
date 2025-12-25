<?php
require_once 'config/koneksi.php';
require_once 'config/auth.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $error = 'Username dan password wajib diisi';
    } else {

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

                // redirect berdasarkan role
                switch ($user['role']) {
                    case 'admin':
                        header("Location: admin/dashboard.php");
                        exit;
                    case 'hrd':
                        header("Location: hrd/dashboard.php");
                        exit;
                    case 'karyawan':
                        header("Location: karyawan/dashboard.php");
                        exit;
                }
            }
        }

        $error = 'Username atau password salah';
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Login | Sistem Absensi Karyawan</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', system-ui, sans-serif;
        }

        body {
            height: 100vh;
            background-image:
                linear-gradient(rgba(0, 0, 0, 0.55), rgba(0, 0, 0, 0.55)),
                url('assets/img/BG-Utama.jpg');
            background-repeat: no-repeat;
            background-position: center;
            background-size: cover;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        /* ===== LOGIN CARD ===== */
        .login-card {
            width: 360px;
            background: rgba(17, 24, 39, 0.95);
            border-radius: 16px;
            padding: 32px 28px;
            box-shadow: 0 30px 80px rgba(0, 0, 0, 0.6);
            text-align: center;
            animation: fadeIn .7s ease;
        }

        .logo img {
            width: 150px;
            margin-bottom: 14px;
        }

        h2 {
            color: #f9fafb;
            font-size: 20px;
            margin-bottom: 20px;
        }

        .subtitle {
            color: #9ca3af;
            font-size: 13px;
            margin-bottom: 26px;
        }

        /* ===== FLOATING LABEL ===== */
        .input-group {
            position: relative;
            margin-bottom: 22px;
            text-align: left;
        }

        .input-group input {
            width: 100%;
            padding: 14px 42px 14px 12px;
            border-radius: 10px;
            border: 1px solid #374151;
            background: #111827;
            color: #f9fafb;
            font-size: 14px;
        }

        .input-group input::placeholder {
            color: transparent;
        }

        .input-group label {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            font-size: 14px;
            pointer-events: none;
            transition: .25s ease;
            background: rgba(17, 24, 39, 0.95);
            padding: 0 6px;
        }

        .input-group input:focus+label,
        .input-group input:not(:placeholder-shown)+label {
            top: -7px;
            font-size: 12px;
            color: #60a5fa;
        }

        .input-group input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 2px rgba(59, 130, 246, .3);
        }

        /* ===== EYE ICON ===== */
        .toggle-password {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #9ca3af;
        }

        .toggle-password:hover {
            color: #60a5fa;
        }

        /* ===== BUTTON ===== */
        .btn-login {
            width: 100%;
            padding: 13px;
            border-radius: 10px;
            border: none;
            background: linear-gradient(135deg, #2563eb, #3b82f6);
            color: #fff;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 6px;
            transition: .3s;
        }

        .btn-login:hover {
            transform: translateY(-1px);
            box-shadow: 0 12px 30px rgba(37, 99, 235, .4);
        }

        .loading {
            display: none;
            margin-top: 10px;
            font-size: 13px;
            color: #60a5fa;
        }

        .footer {
            margin-top: 20px;
            font-size: 12px;
            color: #9ca3af;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(14px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* ===== MOBILE RESPONSIVE ===== */
        @media (max-width: 480px) {

            body {
                padding: 16px;
                align-items: flex-start;
                min-height: 100svh;
                /* FIX mobile viewport */
            }

            .login-card {
                width: 100%;
                max-width: 360px;
                margin-top: 40px;
                padding: 26px 22px;
                border-radius: 14px;
            }

            .logo img {
                width: 120px;
                /* logo lebih kecil di HP */
                margin-bottom: 10px;
            }

            h2 {
                font-size: 18px;
            }

            .subtitle {
                font-size: 12px;
                margin-bottom: 22px;
            }

            .input-group input {
                padding: 13px 40px 13px 12px;
                font-size: 14px;
            }

            .btn-login {
                padding: 12px;
                font-size: 14px;
            }

            .footer {
                font-size: 11px;
            }
        }
    </style>
</head>

<body>

    <div class="login-card">
        <div class="logo">
            <img src="assets/img/bg.png" alt="Logo Perusahaan">
        </div>

        <h2>Sistem Absensi Karyawan</h2>

        <?php if ($error): ?>
            <div style="background:#7f1d1d;color:#fecaca;padding:10px;border-radius:8px;margin-bottom:15px;font-size:13px;">
                <?= $error ?>
            </div>
        <?php endif; ?>

        <!-- 🔥 PERHATIKAN: ADA NAME -->
        <form method="POST">

            <div class="input-group">
                <input type="text" name="username" required placeholder=" ">
                <label>Email / Username</label>
            </div>

            <div class="input-group">
                <input type="password" id="password" name="password" required placeholder=" ">
                <label>Password</label>

                <span class="toggle-password" id="togglePassword">
                    👁
                </span>
            </div>

            <button type="submit" class="btn-login">Login</button>
        </form>

        <div class="footer">© 2025 Sistem Absensi Karyawan</div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {

            /* ===== TOGGLE PASSWORD + ICON ===== */
            const togglePassword = document.getElementById('togglePassword');
            const passwordInput = document.getElementById('password');
            const eyeOpen = document.getElementById('eyeOpen');
            const eyeClosed = document.getElementById('eyeClosed');

            if (togglePassword && passwordInput) {
                togglePassword.addEventListener('click', () => {
                    const isPassword = passwordInput.type === 'password';
                    passwordInput.type = isPassword ? 'text' : 'password';

                    if (eyeOpen && eyeClosed) {
                        eyeOpen.style.display = isPassword ? 'none' : 'block';
                        eyeClosed.style.display = isPassword ? 'block' : 'none';
                    }
                });
            }

            /* ===== AUTO SCROLL INPUT (MOBILE) ===== */
            document.querySelectorAll('input').forEach(input => {
                input.addEventListener('focus', () => {
                    setTimeout(() => {
                        input.scrollIntoView({
                            behavior: 'smooth',
                            block: 'center'
                        });
                    }, 300);
                });
            });

        });
    </script>

</body>

</html>
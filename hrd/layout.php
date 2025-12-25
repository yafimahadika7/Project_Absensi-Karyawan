<?php
require_once '../config/auth.php';

auth_guard();
role_guard('hrd');

if (!isset($page_title))
    $page_title = "HRD Panel";
if (!isset($content))
    $content = "";
?>
<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $page_title ?> | Sistem Absensi Karyawan</title>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Font -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: #f4f6f9;
        }

        .wrapper {
            display: flex;
            min-height: 100vh;
        }

        #sidebar {
            width: 250px;
            background: linear-gradient(180deg, #020617, #0f172a, #020617);
            color: #fff;
            padding: 1.2rem;
            transition: margin-left .3s ease;
        }

        #sidebar.collapsed {
            margin-left: -250px;
        }

        .sidebar-brand {
            text-align: center;
            font-weight: 600;
            margin-bottom: 1.5rem;
        }

        #sidebar .nav-link {
            color: #cbd5f5;
            border-radius: 10px;
            padding: .7rem 1rem;
            margin-bottom: .4rem;
            display: flex;
            align-items: center;
            gap: .7rem;
        }

        #sidebar .nav-link.active,
        #sidebar .nav-link:hover {
            background: rgba(56, 189, 248, 0.15);
            color: #fff;
        }

        .topbar {
            background: #020617;
        }

        #main-content {
            flex: 1;
            padding: 1.8rem;
        }

        .btn-logout {
            background: #ef4444;
            border-radius: 10px;
            border: none;
        }
    </style>
</head>

<body>

    <!-- TOPBAR -->
    <nav class="navbar navbar-dark topbar">
        <div class="container-fluid">
            <button class="btn btn-outline-light" id="sidebarToggle">
                <i class="bi bi-list"></i>
            </button>

            <span class="navbar-brand ms-3">SISTEM ABSENSI KARYAWAN</span>

            <span class="text-light ms-auto small">
                <i class="bi bi-person-circle me-1"></i>
                <?= $_SESSION['nama'] ?> (HRD)
            </span>
        </div>
    </nav>

    <div class="wrapper">

        <!-- SIDEBAR -->
        <aside id="sidebar">
            <div class="sidebar-brand">
                <i class="bi bi-people-fill fs-2 text-info"></i><br>
                HRD PANEL
            </div>

            <ul class="nav flex-column">
                <li class="nav-item">
                    <a href="dashboard.php"
                        class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>">
                        <i class="bi bi-speedometer2"></i> Dashboard
                    </a>
                </li>

                <li class="nav-item">
                    <a href="absensi.php"
                        class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'absensi.php' ? 'active' : '' ?>">
                        <i class="bi bi-calendar-check"></i> Data Absensi
                    </a>
                </li>

                <li class="nav-item">
                    <a href="izin.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'izin.php' ? 'active' : '' ?>">
                        <i class="bi bi-clipboard-check"></i> Approval
                    </a>
                </li>
            </ul>

            <hr class="text-secondary">

            <a href="../logout.php" class="btn btn-logout w-100 text-white">
                <i class="bi bi-box-arrow-right me-1"></i> Logout
            </a>
        </aside>

        <!-- MAIN CONTENT -->
        <main id="main-content">
            <?= $content ?>
        </main>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('sidebarToggle').onclick = () =>
            document.getElementById('sidebar').classList.toggle('collapsed');
    </script>

</body>

</html>
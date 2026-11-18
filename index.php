<?php
session_start();
require_once __DIR__ . "/connection.php";

if (!isset($_SESSION["user_id"]) || !in_array($_SESSION["role"] ?? "", ["karyawan", "atasan"], true)) {
    header("Location: login.php");
    exit;
}

if (($_SESSION["role"] ?? "") === "atasan") {
    header("Location: dashboard_atasan.php");
    exit;
}

$conn = connection();
$name = $_SESSION["name"];
$role = $_SESSION["role"];
$roleLabel = ucfirst($role);

$activeAssignments = 0;
$completedAssignments = 0;
$pendingRequests = 0;
$submittedRequests = 0;

$stmt = $conn->prepare("SELECT id_karyawan FROM karyawan WHERE id_user = ?");
$stmt->bind_param("i", $_SESSION["user_id"]);
$stmt->execute();
$employee = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($employee) {
    $employeeId = (int) $employee["id_karyawan"];

    $result = $conn->query("SELECT COUNT(*) AS total FROM penugasan WHERE id_karyawan = $employeeId AND status = 'Aktif'");
    $activeAssignments = (int) $result->fetch_assoc()["total"];

    $result = $conn->query("SELECT COUNT(*) AS total FROM penugasan WHERE id_karyawan = $employeeId AND status = 'Selesai'");
    $completedAssignments = (int) $result->fetch_assoc()["total"];

    $result = $conn->query("SELECT COUNT(*) AS total FROM pengajuan WHERE id_karyawan = $employeeId AND status = 'Menunggu'");
    $pendingRequests = (int) $result->fetch_assoc()["total"];

    $result = $conn->query("SELECT COUNT(*) AS total FROM pengajuan WHERE id_karyawan = $employeeId");
    $submittedRequests = (int) $result->fetch_assoc()["total"];
}

$conn->close();
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <style>
        :root {
            --sidebar-width: 205px;
            --sidebar-bg: #111827;
            --primary: #2f66f6;
        }

        body {
            margin: 0;
            background: #f3f6fa;
            color: #1f2937;
        }

        .sidebar {
            position: fixed;
            inset: 0 auto 0 0;
            width: var(--sidebar-width);
            background: var(--sidebar-bg);
            color: #fff;
            z-index: 1030;
            transition: transform .25s ease;
        }

        .brand {
            height: 64px;
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 0 18px;
            font-weight: 700;
            border-bottom: 1px solid rgba(255,255,255,.06);
        }

        .brand-mark {
            width: 24px;
            height: 24px;
            display: grid;
            place-items: center;
            border: 2px solid #3b82f6;
            border-radius: 6px;
            color: #60a5fa;
            line-height: 1;
        }

        .sidebar-menu {
            padding: 12px 8px;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            gap: 12px;
            min-height: 48px;
            padding: 10px 14px;
            margin-bottom: 4px;
            border-radius: 6px;
            color: #cbd5e1;
            text-decoration: none;
            font-size: 14px;
        }

        .sidebar-menu a.active,
        .sidebar-menu a:hover {
            background: #2563eb;
            color: #fff;
        }

        .menu-icon {
            width: 20px;
            text-align: center;
            font-size: 16px;
        }

        .sidebar-user {
            position: absolute;
            bottom: 0;
            width: 100%;
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 16px;
            border-top: 1px solid rgba(255,255,255,.08);
        }

        .avatar {
            width: 38px;
            height: 38px;
            display: grid;
            place-items: center;
            flex: 0 0 auto;
            border-radius: 50%;
            background: #65758b;
            color: #fff;
            font-weight: 700;
        }

        .main {
            min-height: 100vh;
            margin-left: var(--sidebar-width);
        }

        .topbar {
            height: 64px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 24px;
            background: #fff;
            border-bottom: 1px solid #e5e7eb;
        }

        .top-title {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #475569;
            font-weight: 600;
        }

        .top-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .content {
            max-width: 1120px;
            margin: 0 auto;
            padding: 28px 24px;
        }

        .page-heading {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .stat-card,
        .panel {
            background: #fff;
            border: 1px solid #e8edf4;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(15,23,42,.06);
        }

        .stat-card {
            min-height: 112px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 18px;
        }

        .stat-label {
            color: #64748b;
            font-size: 13px;
            font-weight: 600;
        }

        .stat-value {
            margin: 5px 0 2px;
            font-size: 26px;
            line-height: 1;
            font-weight: 700;
        }

        .stat-note {
            color: #64748b;
            font-size: 11px;
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            display: grid;
            place-items: center;
            border-radius: 8px;
            color: #2563eb;
            background: #dbeafe;
            font-size: 20px;
        }

        .panel {
            height: 100%;
        }

        .panel-header {
            padding: 14px 18px;
            border-bottom: 1px solid #edf0f4;
            font-size: 13px;
            font-weight: 700;
        }

        .chart-area {
            min-height: 245px;
            display: grid;
            place-items: center;
            color: #94a3b8;
            text-align: center;
        }

        .activity-item {
            display: flex;
            gap: 12px;
            padding: 14px 18px;
            border-bottom: 1px solid #edf0f4;
            font-size: 13px;
        }

        .activity-item:last-child {
            border-bottom: 0;
        }

        .activity-icon {
            width: 32px;
            height: 32px;
            display: grid;
            place-items: center;
            flex: 0 0 auto;
            border-radius: 6px;
            background: #dbeafe;
            color: #2563eb;
        }

        .quick-actions {
            margin-top: 24px;
        }

        .quick-card {
            min-height: 86px;
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 16px;
            background: #fff;
            border: 1px solid #e8edf4;
            border-radius: 8px;
            color: #334155;
            text-decoration: none;
        }

        .quick-card:hover {
            border-color: #93b4f9;
            color: #1d4ed8;
        }

        .menu-toggle {
            display: none;
        }

        @media (max-width: 991.98px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.show {
                transform: translateX(0);
            }

            .main {
                margin-left: 0;
            }

            .menu-toggle {
                display: inline-grid;
            }
        }

        @media (max-width: 575.98px) {
            .content {
                padding: 20px 12px;
            }

            .topbar {
                padding: 0 12px;
            }

            .top-title span {
                display: none;
            }

            .page-heading {
                align-items: flex-start;
                gap: 12px;
            }
        }
    </style>
</head>
<body>
    <aside class="sidebar" id="sidebar">
        <div class="brand">
            <span class="brand-mark">◆</span>
            <span>HRM Pro</span>
        </div>

        <nav class="sidebar-menu">
            <a class="active" href="index.php"><span class="menu-icon">▦</span><span>Dashboard</span></a>
            <?php if ($role === "karyawan"): ?>
                <a href="pengajuan.php"><span class="menu-icon">♟</span><span>Pengajuan</span></a>
                <a href="riwayat_pengajuan.php"><span class="menu-icon">▥</span><span>Riwayat Pengajuan</span></a>
                <a href="surat_tugas.php"><span class="menu-icon">▧</span><span>Surat Tugas</span></a>
            <?php endif; ?>
            <a href="#"><span class="menu-icon">▣</span><span>Penugasan</span></a>
            <?php if ($role === "atasan"): ?>
                <a href="#"><span class="menu-icon">✓</span><span>Persetujuan</span></a>
                <a href="#"><span class="menu-icon">♙</span><span>Karyawan</span></a>
            <?php endif; ?>
            <a href="#"><span class="menu-icon">▤</span><span>Surat Tugas</span></a>
        </nav>

        <div class="sidebar-user">
            <span class="avatar"><?= htmlspecialchars(strtoupper(substr($name, 0, 1))) ?></span>
            <div>
                <div class="small fw-bold"><?= htmlspecialchars($name) ?></div>
                <div class="small text-secondary"><?= htmlspecialchars($roleLabel) ?></div>
            </div>
        </div>
    </aside>

    <main class="main">
        <header class="topbar">
            <div class="top-title">
                <button class="btn btn-light border menu-toggle" type="button" onclick="document.getElementById('sidebar').classList.toggle('show')" aria-label="Buka menu">☰</button>
                <span>Human Resource Management</span>
            </div>
            <div class="top-actions">
                <button class="btn btn-link text-dark position-relative text-decoration-none p-1" aria-label="Notifikasi">
                    🔔
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">3</span>
                </button>
                <span class="avatar" style="width:32px;height:32px;font-size:13px;">
                    <?= htmlspecialchars(strtoupper(substr($name, 0, 1))) ?>
                </span>
                <div class="d-none d-sm-block small fw-semibold"><?= htmlspecialchars($name) ?></div>
                <a class="btn btn-sm btn-outline-secondary" href="logout.php">Keluar</a>
            </div>
        </header>

        <div class="content">
            <div class="page-heading">
                <h1 class="h4 mb-0">Dashboard</h1>
                <div class="d-flex gap-2">
                    <a class="btn btn-outline-primary btn-sm" href="#">📄 Lihat Surat</a>
                    <?php if ($role === "karyawan"): ?>
                        <a class="btn btn-primary btn-sm" href="pengajuan.php">＋ Ajukan Perjalanan</a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-6 col-xl-3">
                    <div class="stat-card">
                        <div>
                            <div class="stat-label">Pengajuan Saya</div>
                            <div class="stat-value"><?= $submittedRequests ?></div>
                            <div class="stat-note">Semua pengajuan</div>
                        </div>
                        <div class="stat-icon">▦</div>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="stat-card">
                        <div>
                            <div class="stat-label">Penugasan Aktif</div>
                            <div class="stat-value"><?= $activeAssignments ?></div>
                            <div class="stat-note">Sedang berjalan</div>
                        </div>
                        <div class="stat-icon">◆</div>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="stat-card">
                        <div>
                            <div class="stat-label">Menunggu Persetujuan</div>
                            <div class="stat-value"><?= $pendingRequests ?></div>
                            <div class="stat-note">Perlu ditinjau</div>
                        </div>
                        <div class="stat-icon">◷</div>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="stat-card">
                        <div>
                            <div class="stat-label">Penugasan Selesai</div>
                            <div class="stat-value"><?= $completedAssignments ?></div>
                            <div class="stat-note">Sudah selesai</div>
                        </div>
                        <div class="stat-icon">✓</div>
                    </div>
                </div>
            </div>

            <div class="row g-3 mt-1">
                <div class="col-lg-8">
                    <section class="panel">
                        <div class="panel-header">Aktivitas Perjalanan</div>
                        <div class="chart-area">
                            <div>
                                <div style="font-size:44px;line-height:1;">▥</div>
                                <strong>Belum ada data aktivitas</strong>
                                <div class="small mt-1">Riwayat pengajuan dan penugasan akan tampil di sini.</div>
                            </div>
                        </div>
                    </section>
                </div>
                <div class="col-lg-4">
                    <section class="panel">
                        <div class="panel-header">Aktivitas Terbaru</div>
                        <div class="activity-item">
                            <div class="activity-icon">▦</div>
                            <div>
                                <strong>Pengajuan perjalanan</strong>
                                <div class="text-muted"><?= $submittedRequests ?> pengajuan tercatat</div>
                                <div class="text-muted small">Data terbaru</div>
                            </div>
                        </div>
                        <div class="activity-item">
                            <div class="activity-icon">◆</div>
                            <div>
                                <strong>Status penugasan</strong>
                                <div class="text-muted"><?= $activeAssignments ?> penugasan aktif</div>
                                <div class="text-muted small">Perlu diperhatikan</div>
                            </div>
                        </div>
                        <div class="activity-item">
                            <div class="activity-icon">✓</div>
                            <div>
                                <strong>Penugasan selesai</strong>
                                <div class="text-muted"><?= $completedAssignments ?> telah diselesaikan</div>
                                <div class="text-muted small">Riwayat perjalanan</div>
                            </div>
                        </div>
                    </section>
                </div>
            </div>

            <section class="quick-actions">
                <h2 class="h6 mb-3">Aksi Cepat</h2>
                <div class="row g-3">
                    <div class="col-md-4">
                        <a class="quick-card" href="pengajuan.php"><span class="stat-icon">＋</span><strong>Ajukan Perjalanan</strong></a>
                    </div>
                    <div class="col-md-4">
                        <a class="quick-card" href="riwayat_pengajuan.php"><span class="stat-icon">▦</span><strong>Lihat Pengajuan</strong></a>
                    </div>
                    <div class="col-md-4">
                        <a class="quick-card" href="surat_tugas.php"><span class="stat-icon">▤</span><strong>Unduh Surat Tugas</strong></a>
                    </div>
                </div>
            </section>
        </div>
    </main>
</body>
</html>

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
$activityLabels = [];
$activityRequests = [];
$activityAssignments = [];
$recentActivities = [];

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

    $monthNames = ["Jan", "Feb", "Mar", "Apr", "Mei", "Jun", "Jul", "Agu", "Sep", "Okt", "Nov", "Des"];
    for ($monthOffset = 11; $monthOffset >= 0; $monthOffset--) {
        $monthDate = strtotime("-{$monthOffset} month");
        $monthStart = date("Y-m-01", $monthDate);
        $monthEnd = date("Y-m-t", $monthDate);
        $activityLabels[] = $monthNames[(int) date("n", $monthDate) - 1];

        $result = $conn->query(
            "SELECT COUNT(*) AS total FROM pengajuan
             WHERE id_karyawan = $employeeId
             AND tanggal_pengajuan >= '$monthStart'
             AND tanggal_pengajuan <= '$monthEnd 23:59:59'"
        );
        $activityRequests[] = (int) $result->fetch_assoc()["total"];

        $result = $conn->query(
            "SELECT COUNT(*) AS total FROM penugasan
             WHERE id_karyawan = $employeeId
             AND tanggal_berangkat >= '$monthStart'
             AND tanggal_berangkat <= '$monthEnd'"
        );
        $activityAssignments[] = (int) $result->fetch_assoc()["total"];
    }

    $result = $conn->query(
        "(SELECT 'Pengajuan' AS jenis, tujuan, tanggal_pengajuan AS waktu, status
          FROM pengajuan WHERE id_karyawan = $employeeId)
         UNION ALL
         (SELECT 'Penugasan' AS jenis, tujuan, CONCAT(tanggal_berangkat, ' 00:00:00') AS waktu, status
          FROM penugasan WHERE id_karyawan = $employeeId)
         ORDER BY waktu DESC LIMIT 5"
    );
    while ($row = $result->fetch_assoc()) {
        $recentActivities[] = $row;
    }
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
            --primary: #2f66f6;
        }

        body {
            margin: 0;
            background: #f3f6fa;
            color: #1f2937;
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
            margin-left: 0;
        }

        .topbar {
            height: 64px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 max(24px, calc((100vw - 1120px) / 2));
            background: #fff;
            border-bottom: 1px solid #e5e7eb;
        }

        .top-title {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #1f2937;
            font-weight: 600;
            font-size: 18px;
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
            margin-bottom: 20px;
        }

        .employee-nav {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .employee-nav .btn {
            padding: 8px 14px;
            border: 1px solid #c7d7fe;
            border-radius: 8px;
            background: #e8f0ff;
            color: #1d4ed8;
            font-weight: 600;
        }

        .employee-nav .btn:hover {
            background: #2563eb;
            border-color: #2563eb;
            color: #fff;
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
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .chart-area {
            min-height: 260px;
            display: flex;
            align-items: flex-end;
            gap: 8px;
            padding: 36px 18px 18px;
            color: #94a3b8;
        }

        .chart-item {
            flex: 1;
            min-width: 40px;
            text-align: center;
        }

        .chart-bars {
            height: 190px;
            display: flex;
            align-items: flex-end;
            justify-content: center;
            gap: 4px;
        }

        .chart-bar {
            width: 14px;
            min-height: 4px;
            border-radius: 4px 4px 0 0;
        }

        .chart-bar.request {
            background: #60a5fa;
        }

        .chart-bar.assignment {
            background: #2563eb;
        }

        .chart-legend {
            display: flex;
            justify-content: center;
            gap: 18px;
            padding: 0 20px 14px;
            color: #64748b;
            font-size: 12px;
        }

        .chart-legend span::before {
            content: "";
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 2px;
            margin-right: 5px;
        }

        .chart-legend .request::before { background: #60a5fa; }
        .chart-legend .assignment::before { background: #2563eb; }

        .activity-item {
            display: flex;
            align-items: flex-start;
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

        .activity-copy {
            flex: 1;
            min-width: 0;
        }

        .activity-copy strong {
            display: block;
            margin-bottom: 3px;
            overflow-wrap: anywhere;
        }

        .activity-item .text-muted {
            line-height: 1.4;
        }

        .menu-toggle {
            display: none;
        }

        @media (max-width: 991.98px) {
            .main {
                margin-left: 0;
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
    <main class="main">
        <header class="topbar">
            <div class="top-title">
                <strong>Dashboard</strong>
            </div>
            <div class="top-actions">
                <span class="avatar" style="width:32px;height:32px;font-size:13px;">
                    <?= htmlspecialchars(strtoupper(substr($name, 0, 1))) ?>
                </span>
                <div class="d-none d-sm-block small fw-semibold"><?= htmlspecialchars($name) ?></div>
                <a class="btn btn-sm btn-outline-secondary" href="logout.php">Keluar</a>
            </div>
        </header>

        <div class="content">
            <div class="page-heading">
                <?php if ($role === "karyawan"): ?>
                    <div class="employee-nav">
                        <a class="btn btn-sm" href="pengajuan.php">＋ Pengajuan</a>
                        <a class="btn btn-sm" href="riwayat_pengajuan.php">▤ Riwayat</a>
                        <a class="btn btn-sm" href="penugasan_karyawan.php">◆ Penugasan</a>
                        <a class="btn btn-sm" href="surat_tugas.php">▧ Surat Tugas</a>
                    </div>
                <?php else: ?>
                    <h1 class="h5 mb-0">Menu Utama</h1>
                <?php endif; ?>
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
                        <div class="panel-header">
                            <span>Aktivitas Perjalanan</span>
                            <small class="text-muted fw-normal">12 bulan terakhir</small>
                        </div>
                        <div class="chart-area">
                            <?php
                            $chartMax = max(1, ...array_merge($activityRequests, $activityAssignments));
                            foreach ($activityLabels as $index => $label):
                                $requestHeight = max(4, (int) round(($activityRequests[$index] / $chartMax) * 125));
                                $assignmentHeight = max(4, (int) round(($activityAssignments[$index] / $chartMax) * 125));
                            ?>
                                <div class="chart-item">
                                    <div class="chart-bars">
                                        <div class="chart-bar request" style="height:<?= $requestHeight ?>px" title="<?= $activityRequests[$index] ?> pengajuan"></div>
                                        <div class="chart-bar assignment" style="height:<?= $assignmentHeight ?>px" title="<?= $activityAssignments[$index] ?> penugasan"></div>
                                    </div>
                                    <small><?= htmlspecialchars($label) ?></small>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="chart-legend"><span class="request">Pengajuan</span><span class="assignment">Penugasan</span></div>
                    </section>
                </div>
                <div class="col-lg-4">
                    <section class="panel">
                        <div class="panel-header">Aktivitas Terbaru</div>
                        <?php if (!$recentActivities): ?>
                            <div class="text-center text-muted py-5 px-3 small">Belum ada aktivitas perjalanan.</div>
                        <?php else: ?>
                            <?php foreach ($recentActivities as $activity): ?>
                                <div class="activity-item">
                                    <div class="activity-icon"><?= $activity["jenis"] === "Pengajuan" ? "▦" : "◆" ?></div>
                                    <div class="activity-copy">
                                        <strong><?= htmlspecialchars($activity["jenis"]) ?> - <?= htmlspecialchars($activity["tujuan"]) ?></strong>
                                        <div class="text-muted">Status: <?= htmlspecialchars($activity["status"]) ?></div>
                                        <div class="text-muted small"><?= date("d/m/Y", strtotime($activity["waktu"])) ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </section>
                </div>
            </div>

        </div>
    </main>
</body>
</html>

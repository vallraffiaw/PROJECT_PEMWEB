<?php
session_start();
require_once __DIR__ . "/connection.php";

if (!isset($_SESSION["user_id"]) || ($_SESSION["role"] ?? "") !== "atasan") {
    header("Location: login.php");
    exit;
}

$conn = connection();
$counts = [
    "karyawan" => 0,
    "menunggu" => 0,
    "disetujui" => 0,
    "aktif" => 0,
];

foreach ($counts as $key => $unused) {
    $sql = match ($key) {
        "karyawan" => "SELECT COUNT(*) AS total FROM karyawan",
        "menunggu" => "SELECT COUNT(*) AS total FROM pengajuan WHERE status='Menunggu'",
        "disetujui" => "SELECT COUNT(*) AS total FROM pengajuan WHERE status='Disetujui'",
        "aktif" => "SELECT COUNT(*) AS total FROM penugasan WHERE status='Aktif'",
    };
    $counts[$key] = (int) $conn->query($sql)->fetch_assoc()["total"];
}

$requests = [];
$result = $conn->query(
    "SELECT p.id_pengajuan, p.tujuan, p.tanggal_berangkat, p.status,
            k.nama, k.departemen
     FROM pengajuan p
     JOIN karyawan k ON k.id_karyawan=p.id_karyawan
     ORDER BY p.tanggal_pengajuan DESC LIMIT 5"
);
while ($row = $result->fetch_assoc()) {
    $requests[] = $row;
}
$conn->close();
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard Atasan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root { --navy:#243d67; --blue:#2f66f6; --sidebar:220px; }
        body { background:#f4f7fb; }
        .sidebar { position:fixed; inset:0 auto 0 0; width:var(--sidebar); background:var(--navy); color:#fff; z-index:1000; }
        .brand { height:64px; display:flex; align-items:center; padding:0 18px; font-weight:700; border-bottom:1px solid rgba(255,255,255,.12); }
        .menu { padding:16px 10px; }
        .menu a { display:flex; gap:10px; align-items:center; min-height:44px; padding:10px 12px; margin:3px 0; color:#dbe5f4; text-decoration:none; border-radius:7px; }
        .menu a:hover,.menu a.active { background:#2f66f6; color:#fff; }
        .main { margin-left:var(--sidebar); min-height:100vh; }
        .topbar { height:64px; background:#fff; display:flex; align-items:center; justify-content:space-between; padding:0 24px; border-bottom:1px solid #e5eaf2; }
        .content { max-width:1150px; margin:auto; padding:24px; }
        .stat-card { min-height:112px; border:0; border-radius:8px; box-shadow:0 2px 10px rgba(36,61,103,.07); }
        .stat-number { font-size:25px; font-weight:700; }
        .stat-icon { width:44px; height:44px; display:grid; place-items:center; border-radius:8px; background:#edf3ff; color:var(--blue); }
        .panel { background:#fff; border:0; border-radius:8px; box-shadow:0 2px 10px rgba(36,61,103,.07); }
        .request-row { padding:14px 16px; border-bottom:1px solid #edf0f5; }
        .request-row:last-child { border-bottom:0; }
        .menu-toggle { display:none; }
        @media(max-width:991px) {
            .sidebar { transform:translateX(-100%); transition:.25s; }
            .sidebar.show { transform:translateX(0); }
            .main { margin-left:0; }
            .menu-toggle { display:inline-grid; }
        }
    </style>
</head>
<body>
<aside class="sidebar" id="sidebar">
    <div class="brand">▣ DINAS KU</div>
    <nav class="menu">
        <a class="active" href="dashboard_atasan.php">▦ <span>Dashboard</span></a>
        <a href="data_karyawan.php">♙ <span>Data Karyawan</span></a>
        <a href="pengajuan_masuk.php">▤ <span>Persetujuan</span></a>
        <a href="penugasan.php">◆ <span>Penugasan</span></a>
        <a href="surat_tugas.php">▧ <span>Surat Tugas</span></a>
        <a href="laporan.php">▥ <span>Laporan</span></a>
        <a href="logout.php">↪ <span>Logout</span></a>
    </nav>
</aside>
<main class="main">
    <header class="topbar">
        <div class="d-flex align-items-center gap-2">
            <button class="btn btn-light border menu-toggle" type="button" onclick="document.getElementById('sidebar').classList.toggle('show')">☰</button>
            <strong class="text-secondary">Sistem Informasi Perjalanan Dinas</strong>
        </div>
        <span class="badge bg-primary px-3 py-2"><?= htmlspecialchars($_SESSION["name"]) ?></span>
    </header>
    <div class="content">
        <h1 class="h4 mb-1">Dashboard</h1>
        <p class="text-muted mb-4">Selamat datang, Bapak/Ibu Atasan</p>

        <div class="row g-3">
            <div class="col-6 col-lg-3"><div class="card stat-card"><div class="card-body d-flex justify-content-between"><div><div class="text-muted small">Total Karyawan</div><div class="stat-number"><?= $counts["karyawan"] ?></div></div><div class="stat-icon">♙</div></div></div></div>
            <div class="col-6 col-lg-3"><div class="card stat-card"><div class="card-body d-flex justify-content-between"><div><div class="text-muted small">Menunggu</div><div class="stat-number"><?= $counts["menunggu"] ?></div></div><div class="stat-icon">◷</div></div></div></div>
            <div class="col-6 col-lg-3"><div class="card stat-card"><div class="card-body d-flex justify-content-between"><div><div class="text-muted small">Disetujui</div><div class="stat-number"><?= $counts["disetujui"] ?></div></div><div class="stat-icon">✓</div></div></div></div>
            <div class="col-6 col-lg-3"><div class="card stat-card"><div class="card-body d-flex justify-content-between"><div><div class="text-muted small">Penugasan Aktif</div><div class="stat-number"><?= $counts["aktif"] ?></div></div><div class="stat-icon">◆</div></div></div></div>
        </div>

        <a class="btn btn-primary w-100 mt-4 py-3 text-start" href="penugasan.php">
            <strong>＋ Buat Penugasan</strong><br><small>Berikan tugas perjalanan dinas kepada karyawan</small>
        </a>

        <div class="row g-3 mt-2">
            <div class="col-lg-8">
                <section class="panel">
                    <div class="p-3 border-bottom fw-bold">Pengajuan Terbaru</div>
                    <?php if (!$requests): ?>
                        <div class="text-muted text-center py-5">Belum ada pengajuan.</div>
                    <?php else: foreach ($requests as $request): ?>
                        <div class="request-row d-flex justify-content-between align-items-center">
                            <div>
                                <strong><?= htmlspecialchars($request["nama"]) ?></strong>
                                <div class="small text-muted"><?= htmlspecialchars($request["tujuan"]) ?></div>
                                <div class="small text-muted"><?= date("d/m/Y", strtotime($request["tanggal_berangkat"])) ?></div>
                            </div>
                            <?php
                            $badge = $request["status"] === "Disetujui" ? "success" : ($request["status"] === "Ditolak" ? "danger" : "warning");
                            ?>
                            <span class="badge bg-<?= $badge ?>"><?= htmlspecialchars($request["status"]) ?></span>
                        </div>
                    <?php endforeach; endif; ?>
                </section>
            </div>
            <div class="col-lg-4">
                <section class="panel p-3">
                    <strong>Aksi Cepat</strong>
                    <div class="d-grid gap-2 mt-3">
                        <a class="btn btn-outline-primary" href="data_karyawan.php">Kelola Data Karyawan</a>
                        <a class="btn btn-outline-primary" href="pengajuan_masuk.php">Tinjau Pengajuan</a>
                        <a class="btn btn-outline-primary" href="penugasan.php">Buat Penugasan</a>
                    </div>
                </section>
            </div>
        </div>
    </div>
</main>
</body>
</html>

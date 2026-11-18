<?php
session_start();
require_once __DIR__ . "/connection.php";

if (!isset($_SESSION["user_id"]) || ($_SESSION["role"] ?? "") !== "atasan") {
    header("Location: login.php");
    exit;
}

$conn = connection();
$message = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $requestId = filter_input(INPUT_POST, "id_pengajuan", FILTER_VALIDATE_INT);
    $action = $_POST["action"] ?? "";

    if (!$requestId) {
        $error = "Pengajuan tidak valid.";
    } elseif ($action === "approve") {
        $stmt = $conn->prepare(
            "UPDATE pengajuan
             SET status='Disetujui', alasan_penolakan=NULL
             WHERE id_pengajuan=? AND status='Menunggu'"
        );
        $stmt->bind_param("i", $requestId);
        $stmt->execute();
        $message = $stmt->affected_rows
            ? "Pengajuan berhasil disetujui."
            : "Pengajuan tidak ditemukan atau sudah diproses.";
        $stmt->close();
    } elseif ($action === "reject") {
        $reason = trim($_POST["alasan_penolakan"] ?? "");
        if ($reason === "") {
            $error = "Alasan penolakan wajib diisi.";
        } else {
            $stmt = $conn->prepare(
                "UPDATE pengajuan
                 SET status='Ditolak', alasan_penolakan=?
                 WHERE id_pengajuan=? AND status='Menunggu'"
            );
            $stmt->bind_param("si", $reason, $requestId);
            $stmt->execute();
            $message = $stmt->affected_rows
                ? "Pengajuan berhasil ditolak."
                : "Pengajuan tidak ditemukan atau sudah diproses.";
            $stmt->close();
        }
    }
}

$counts = [
    "karyawan" => 0,
    "menunggu" => 0,
    "disetujui" => 0,
    "aktif" => 0,
];

foreach ($counts as $key => $unused) {
    $sql = match ($key) {
        "karyawan" => "SELECT COUNT(*) AS total FROM karyawan k JOIN users u ON u.id=k.id_user WHERE u.role='karyawan'",
        "menunggu" => "SELECT COUNT(*) AS total FROM pengajuan WHERE status='Menunggu'",
        "disetujui" => "SELECT COUNT(*) AS total FROM pengajuan WHERE status='Disetujui'",
        "aktif" => "SELECT COUNT(*) AS total FROM penugasan WHERE status='Aktif'",
    };
    $counts[$key] = (int) $conn->query($sql)->fetch_assoc()["total"];
}

$requests = [];
$result = $conn->query(
    "SELECT p.id_pengajuan, p.tujuan, p.tanggal_berangkat, p.tanggal_pulang,
            p.keperluan, p.dokumen, p.status, p.alasan_penolakan,
            k.nama, k.departemen, k.jabatan
     FROM pengajuan p
     JOIN karyawan k ON k.id_karyawan=p.id_karyawan
     ORDER BY p.tanggal_pengajuan DESC"
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
        :root { --navy:#243d67; --blue:#2f66f6; }
        body { background:#f4f7fb; }
        .main { margin-left:0; min-height:100vh; }
        .topbar { height:64px; display:flex; align-items:center; padding:0 max(24px, calc((100vw - 1120px) / 2)); background:#fff; border-bottom:1px solid #e5eaf2; }
        .topbar-inner { width:100%; min-height:64px; display:flex; align-items:center; justify-content:space-between; }
        .topbar-user { display:flex; align-items:center; gap:12px; }
        .topbar-avatar { width:32px; height:32px; display:grid; place-items:center; flex:0 0 auto; border-radius:50%; background:#65758b; color:#fff; font-size:13px; font-weight:700; }
        .content { width:100%; max-width:1150px; box-sizing:border-box; margin:0 auto; padding:24px; }
        .content > .row { margin-left:0; margin-right:0; }
        .stats-container { width:100%; max-width:1000px; margin:0 auto; }
        .stat-card { min-height:112px; border:0; border-radius:8px; box-shadow:0 2px 10px rgba(36,61,103,.07); }
        .stat-number { font-size:25px; font-weight:700; }
        .stat-icon { width:44px; height:44px; display:grid; place-items:center; border-radius:8px; background:#edf3ff; color:var(--blue); }
        .panel { background:#fff; border:0; border-radius:8px; box-shadow:0 2px 10px rgba(36,61,103,.07); }
        .request-row { padding:14px 16px; border-bottom:1px solid #edf0f5; }
        .request-row:last-child { border-bottom:0; }
        .dashboard-nav { display:flex; flex-wrap:wrap; gap:10px; margin-bottom:24px; }
        .dashboard-nav .btn { padding:9px 16px; border-radius:8px; font-weight:600; }
        .request-card { border:0; border-radius:8px; box-shadow:0 2px 10px rgba(36,61,103,.07); font-size:14px; }
        .request-card .card-body { padding:12px 14px; }
        .request-card .card-body hr { margin:10px 0; }
        .request-card .card-footer { display:flex; justify-content:flex-end; gap:8px; padding:8px 14px; background:#fff; border-top:1px solid #edf0f5; }
        .requests-container { width:100%; max-width:1000px; margin-left:auto; margin-right:auto; }
        @media(max-width:575.98px) {
            .topbar { padding:0 12px; }
        }
    </style>
</head>
<body>
<main class="main">
    <header class="topbar">
        <div class="topbar-inner">
            <strong>Dashboard</strong>
            <div class="topbar-user">
                <span class="topbar-avatar"><?= htmlspecialchars(strtoupper(substr($_SESSION["name"], 0, 1))) ?></span>
                <span class="d-none d-sm-inline small fw-semibold"><?= htmlspecialchars($_SESSION["name"]) ?></span>
                <a class="btn btn-sm btn-outline-secondary" href="logout.php">Keluar</a>
            </div>
        </div>
    </header>
    <div class="content">
        <nav class="dashboard-nav">
            <a class="btn btn-primary" href="data_karyawan.php">♙ Data Karyawan</a>
            <a class="btn btn-primary" href="penugasan.php">◆ Penugasan</a>
            <a class="btn btn-primary" href="surat_tugas.php">▧ Surat Tugas</a>
            <a class="btn btn-primary" href="laporan.php">▥ Laporan</a>
        </div>

        <?php if ($message): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

        <div class="stats-container">
            <div class="row g-3">
                <div class="col-6 col-lg-3"><div class="card stat-card"><div class="card-body d-flex justify-content-between"><div><div class="text-muted small">Total Karyawan</div><div class="stat-number"><?= $counts["karyawan"] ?></div></div><div class="stat-icon">♙</div></div></div></div>
                <div class="col-6 col-lg-3"><div class="card stat-card"><div class="card-body d-flex justify-content-between"><div><div class="text-muted small">Menunggu</div><div class="stat-number"><?= $counts["menunggu"] ?></div></div><div class="stat-icon">◷</div></div></div></div>
                <div class="col-6 col-lg-3"><div class="card stat-card"><div class="card-body d-flex justify-content-between"><div><div class="text-muted small">Disetujui</div><div class="stat-number"><?= $counts["disetujui"] ?></div></div><div class="stat-icon">✓</div></div></div></div>
                <div class="col-6 col-lg-3"><div class="card stat-card"><div class="card-body d-flex justify-content-between"><div><div class="text-muted small">Penugasan Aktif</div><div class="stat-number"><?= $counts["aktif"] ?></div></div><div class="stat-icon">◆</div></div></div></div>
            </div>
        </div>

        <section class="mt-4 requests-container">
            <h2 class="h5 mb-3">Pengajuan Perjalanan Dinas</h2>
            <div class="row g-3">
                <?php if (!$requests): ?>
                    <div class="col-12"><div class="card request-card text-center text-muted py-5">Belum ada pengajuan perjalanan dinas.</div></div>
                <?php else: foreach ($requests as $request): ?>
                    <div class="col-12 col-lg-6">
                        <article class="card request-card h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h3 class="h6 mb-1"><?= htmlspecialchars($request["nama"]) ?></h3>
                                        <div class="small text-muted"><?= htmlspecialchars($request["jabatan"]) ?> · <?= htmlspecialchars($request["departemen"]) ?></div>
                                    </div>
                                    <?php $badge = $request["status"] === "Disetujui" ? "success" : ($request["status"] === "Ditolak" ? "danger" : "warning"); ?>
                                    <span class="badge bg-<?= $badge ?>"><?= htmlspecialchars($request["status"]) ?></span>
                                </div>
                                <hr>
                                <div class="mb-1"><strong>Tujuan:</strong> <?= htmlspecialchars($request["tujuan"]) ?></div>
                                <div class="mb-2"><strong>Berangkat - Pulang:</strong>
                                    <?= date("d/m/Y", strtotime($request["tanggal_berangkat"])) ?> -
                                    <?= date("d/m/Y", strtotime($request["tanggal_pulang"])) ?>
                                </div>
                                <div><strong>Keperluan:</strong><br><?= nl2br(htmlspecialchars($request["keperluan"])) ?></div>
                                <?php if ($request["dokumen"]): ?>
                                    <a class="d-inline-block mt-2" href="<?= htmlspecialchars($request["dokumen"]) ?>" target="_blank" rel="noopener">Lihat dokumen pendukung</a>
                                <?php endif; ?>
                                <?php if ($request["status"] === "Ditolak" && $request["alasan_penolakan"]): ?>
                                    <div class="alert alert-danger mt-3 mb-0"><strong>Alasan:</strong> <?= nl2br(htmlspecialchars($request["alasan_penolakan"])) ?></div>
                                <?php endif; ?>
                            </div>
                            <?php if ($request["status"] === "Menunggu"): ?>
                                <div class="card-footer">
                                    <form method="post">
                                        <input type="hidden" name="action" value="approve">
                                        <input type="hidden" name="id_pengajuan" value="<?= (int) $request["id_pengajuan"] ?>">
                                        <button class="btn btn-success btn-sm">Setujui</button>
                                    </form>
                                    <button class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#reject<?= (int) $request["id_pengajuan"] ?>">Tolak</button>
                                </div>
                                <div class="modal fade" id="reject<?= (int) $request["id_pengajuan"] ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <form class="modal-content" method="post">
                                            <div class="modal-header">
                                                <h3 class="modal-title h5">Alasan Penolakan</h3>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <input type="hidden" name="action" value="reject">
                                                <input type="hidden" name="id_pengajuan" value="<?= (int) $request["id_pengajuan"] ?>">
                                                <textarea class="form-control" name="alasan_penolakan" rows="4" required></textarea>
                                            </div>
                                            <div class="modal-footer"><button class="btn btn-danger">Tolak Pengajuan</button></div>
                                        </form>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </article>
                    </div>
                <?php endforeach; endif; ?>
            </div>
        </div>
    </div>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

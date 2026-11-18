<?php
session_start();
require_once __DIR__ . "/connection.php";

if (!isset($_SESSION["user_id"]) || ($_SESSION["role"] ?? "") !== "karyawan") {
    header("Location: login.php");
    exit;
}

$conn = connection();
$stmt = $conn->prepare("SELECT id_karyawan FROM karyawan WHERE id_user = ?");
$stmt->bind_param("i", $_SESSION["user_id"]);
$stmt->execute();
$employee = $stmt->get_result()->fetch_assoc();
$stmt->close();

$assignments = [];
if ($employee) {
    $employeeId = (int) $employee["id_karyawan"];
    $stmt = $conn->prepare(
        "SELECT t.*, a.nama AS nama_atasan
         FROM penugasan t
         JOIN karyawan a ON a.id_karyawan = t.id_atasan
         WHERE t.id_karyawan = ?
         ORDER BY t.id_penugasan DESC"
    );
    $stmt->bind_param("i", $employeeId);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $assignments[] = $row;
    }
    $stmt->close();
}
$conn->close();
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Penugasan Saya</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<style>
.back-circle{width:38px;height:38px;display:inline-grid;place-items:center;border:1px solid #d7deea;border-radius:50%;background:#fff;color:#2563eb;text-decoration:none;font-weight:700}
.back-circle:hover{background:#eef4ff;color:#1d4ed8}
</style>
<nav class="navbar navbar-light bg-white border-bottom">
    <div class="container"><a class="back-circle" href="index.php" aria-label="Kembali ke Dashboard">←</a></div>
</nav>
<main class="container py-4">
    <h1 class="h4 mb-3">Penugasan Saya</h1>
    <div class="row g-3">
        <?php foreach ($assignments as $assignment): ?>
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <h2 class="h6"><?= htmlspecialchars($assignment["tujuan"]) ?></h2>
                            <span class="badge bg-<?= $assignment["status"] === "Aktif" ? "primary" : "success" ?>">
                                <?= htmlspecialchars($assignment["status"]) ?>
                            </span>
                        </div>
                        <div class="small text-muted mb-3">Diberikan oleh <?= htmlspecialchars($assignment["nama_atasan"]) ?></div>
                        <dl class="row mb-0">
                            <dt class="col-4">Jenis</dt><dd class="col-8"><?= htmlspecialchars($assignment["jenis"]) ?></dd>
                            <dt class="col-4">Tanggal</dt><dd class="col-8"><?= date("d/m/Y", strtotime($assignment["tanggal_berangkat"])) ?> - <?= date("d/m/Y", strtotime($assignment["tanggal_pulang"])) ?></dd>
                            <dt class="col-4">Keperluan</dt><dd class="col-8"><?= nl2br(htmlspecialchars($assignment["keperluan"])) ?></dd>
                        </dl>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if (!$assignments): ?>
            <div class="col-12"><div class="card border-0 shadow-sm text-center text-muted py-5">Belum ada penugasan untuk Anda.</div></div>
        <?php endif; ?>
    </div>
</main>
</body>
</html>

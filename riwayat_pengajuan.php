<?php
session_start();
require_once __DIR__ . "/connection.php";

if (!isset($_SESSION["user_id"]) || ($_SESSION["role"] ?? "") !== "karyawan") {
    header("Location: login.php");
    exit;
}

$conn = connection();
$requests = [];

$stmt = $conn->prepare(
    "SELECT p.id_pengajuan, p.tujuan, p.tanggal_berangkat, p.tanggal_pulang,
            p.keperluan, p.status, p.tanggal_pengajuan
     FROM pengajuan p
     INNER JOIN karyawan k ON k.id_karyawan = p.id_karyawan
     WHERE k.id_user = ?
     ORDER BY p.tanggal_pengajuan DESC"
);
$stmt->bind_param("i", $_SESSION["user_id"]);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $requests[] = $row;
}
$stmt->close();
$conn->close();

$statusClasses = [
    "Menunggu" => "warning",
    "Disetujui" => "success",
    "Ditolak" => "danger",
];
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Riwayat Pengajuan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<style>
.back-circle{width:38px;height:38px;display:inline-grid;place-items:center;border:1px solid #d7deea;border-radius:50%;background:#fff;color:#2563eb;text-decoration:none;font-weight:700}
.back-circle:hover{background:#eef4ff;color:#1d4ed8}
</style>
<nav class="navbar navbar-light bg-white border-bottom">
    <div class="container">
        <a class="back-circle" href="index.php" aria-label="Kembali ke Dashboard">←</a>
    </div>
</nav>
<main class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h4 mb-0">Riwayat Pengajuan</h1>
        <a class="btn btn-primary" href="pengajuan.php">＋ Pengajuan Baru</a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <?php if (!$requests): ?>
                <div class="text-center text-muted py-5">
                    Belum ada pengajuan perjalanan dinas.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>No.</th>
                                <th>Tujuan</th>
                                <th>Tanggal</th>
                                <th>Status</th>
                                <th>Diajukan</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($requests as $index => $request): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td><?= htmlspecialchars($request["tujuan"]) ?></td>
                                    <td>
                                        <?= date("d/m/Y", strtotime($request["tanggal_berangkat"])) ?>
                                        -
                                        <?= date("d/m/Y", strtotime($request["tanggal_pulang"])) ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= $statusClasses[$request["status"]] ?>">
                                            <?= htmlspecialchars($request["status"]) ?>
                                        </span>
                                    </td>
                                    <td><?= date("d/m/Y H:i", strtotime($request["tanggal_pengajuan"])) ?></td>
                                    <td class="text-end">
                                        <a class="btn btn-sm btn-outline-primary" href="detail_pengajuan.php?id=<?= (int) $request["id_pengajuan"] ?>">Detail</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>
</body>
</html>

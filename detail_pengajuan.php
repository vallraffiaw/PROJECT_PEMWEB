<?php
session_start();
require_once __DIR__ . "/connection.php";

if (!isset($_SESSION["user_id"]) || ($_SESSION["role"] ?? "") !== "karyawan") {
    header("Location: login.php");
    exit;
}

$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);
if (!$id) {
    header("Location: riwayat_pengajuan.php");
    exit;
}

$conn = connection();
$stmt = $conn->prepare(
    "SELECT p.*, k.nama, k.nip, k.jabatan, k.departemen
     FROM pengajuan p
     INNER JOIN karyawan k ON k.id_karyawan = p.id_karyawan
     WHERE p.id_pengajuan = ? AND k.id_user = ?"
);
$stmt->bind_param("ii", $id, $_SESSION["user_id"]);
$stmt->execute();
$request = $stmt->get_result()->fetch_assoc();
$stmt->close();
$conn->close();

if (!$request) {
    http_response_code(404);
}

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
    <title>Detail Pengajuan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-light bg-white border-bottom">
    <div class="container">
        <a class="navbar-brand fw-bold" href="riwayat_pengajuan.php">← Riwayat Pengajuan</a>
    </div>
</nav>
<main class="container py-4">
    <?php if (!$request): ?>
        <div class="alert alert-danger">Pengajuan tidak ditemukan atau bukan milik Anda.</div>
    <?php else: ?>
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                <h1 class="h5 mb-0">Pengajuan #<?= (int) $request["id_pengajuan"] ?></h1>
                <span class="badge bg-<?= $statusClasses[$request["status"]] ?>">
                    <?= htmlspecialchars($request["status"]) ?>
                </span>
            </div>
            <div class="card-body p-4">
                <div class="row g-4">
                    <div class="col-md-6">
                        <h2 class="h6">Data Pemohon</h2>
                        <dl class="row mb-0">
                            <dt class="col-sm-4">Nama</dt><dd class="col-sm-8"><?= htmlspecialchars($request["nama"]) ?></dd>
                            <dt class="col-sm-4">NIP</dt><dd class="col-sm-8"><?= htmlspecialchars($request["nip"]) ?></dd>
                            <dt class="col-sm-4">Jabatan</dt><dd class="col-sm-8"><?= htmlspecialchars($request["jabatan"]) ?></dd>
                            <dt class="col-sm-4">Departemen</dt><dd class="col-sm-8"><?= htmlspecialchars($request["departemen"]) ?></dd>
                        </dl>
                    </div>
                    <div class="col-md-6">
                        <h2 class="h6">Perjalanan Dinas</h2>
                        <dl class="row mb-0">
                            <dt class="col-sm-5">Tujuan</dt><dd class="col-sm-7"><?= htmlspecialchars($request["tujuan"]) ?></dd>
                            <dt class="col-sm-5">Berangkat</dt><dd class="col-sm-7"><?= date("d/m/Y", strtotime($request["tanggal_berangkat"])) ?></dd>
                            <dt class="col-sm-5">Pulang</dt><dd class="col-sm-7"><?= date("d/m/Y", strtotime($request["tanggal_pulang"])) ?></dd>
                            <dt class="col-sm-5">Tanggal ajukan</dt><dd class="col-sm-7"><?= date("d/m/Y H:i", strtotime($request["tanggal_pengajuan"])) ?></dd>
                        </dl>
                    </div>
                </div>

                <hr>
                <h2 class="h6">Keperluan</h2>
                <p><?= nl2br(htmlspecialchars($request["keperluan"])) ?></p>

                <?php if ($request["status"] === "Ditolak"): ?>
                    <div class="alert alert-danger">
                        <strong>Alasan penolakan:</strong>
                        <?= $request["alasan_penolakan"]
                            ? nl2br(htmlspecialchars($request["alasan_penolakan"]))
                            : "Tidak ada alasan." ?>
                    </div>
                <?php endif; ?>

                <?php if ($request["dokumen"]): ?>
                    <a class="btn btn-outline-primary" href="<?= htmlspecialchars($request["dokumen"]) ?>" target="_blank" rel="noopener">Lihat Dokumen Pendukung</a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</main>
</body>
</html>

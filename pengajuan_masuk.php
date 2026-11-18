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
    $id = (int) ($_POST["id_pengajuan"] ?? 0);
    $action = $_POST["action"] ?? "";

    if ($action === "approve") {
        $stmt = $conn->prepare("UPDATE pengajuan SET status='Disetujui', alasan_penolakan=NULL WHERE id_pengajuan=? AND status='Menunggu'");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $message = "Pengajuan berhasil disetujui.";
        $stmt->close();
    } elseif ($action === "reject") {
        $reason = trim($_POST["alasan_penolakan"] ?? "");
        if ($reason === "") {
            $error = "Alasan penolakan wajib diisi.";
        } else {
            $stmt = $conn->prepare("UPDATE pengajuan SET status='Ditolak', alasan_penolakan=? WHERE id_pengajuan=? AND status='Menunggu'");
            $stmt->bind_param("si", $reason, $id);
            $stmt->execute();
            $message = "Pengajuan berhasil ditolak.";
            $stmt->close();
        }
    }
}

$requests = [];
$result = $conn->query(
    "SELECT p.*, k.nama,k.nip,k.jabatan,k.departemen
     FROM pengajuan p JOIN karyawan k ON k.id_karyawan=p.id_karyawan
     ORDER BY p.tanggal_pengajuan DESC"
);
while ($row = $result->fetch_assoc()) $requests[] = $row;
$conn->close();
?>
<!doctype html>
<html lang="id">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Persetujuan Pengajuan</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="bg-light">
<nav class="navbar navbar-light bg-white border-bottom"><div class="container"><a class="navbar-brand fw-bold" href="dashboard_atasan.php">← Dashboard Atasan</a></div></nav>
<main class="container py-4">
    <h1 class="h4 mb-3">Pengajuan Perjalanan Dinas</h1>
    <?php if ($message): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif;?>
    <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif;?>
    <div class="row g-3">
        <?php foreach ($requests as $request): ?>
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <h2 class="h6"><?= htmlspecialchars($request["nama"]) ?></h2>
                            <?php $badge = $request["status"]==="Disetujui"?"success":($request["status"]==="Ditolak"?"danger":"warning"); ?>
                            <span class="badge bg-<?= $badge ?>"><?= htmlspecialchars($request["status"]) ?></span>
                        </div>
                        <div class="small text-muted"><?= htmlspecialchars($request["jabatan"]) ?> · <?= htmlspecialchars($request["departemen"]) ?></div>
                        <hr>
                        <div><strong>Tujuan:</strong> <?= htmlspecialchars($request["tujuan"]) ?></div>
                        <div><strong>Tanggal:</strong> <?= date("d/m/Y", strtotime($request["tanggal_berangkat"])) ?> - <?= date("d/m/Y", strtotime($request["tanggal_pulang"])) ?></div>
                        <div class="mt-2"><strong>Keperluan:</strong><br><?= nl2br(htmlspecialchars($request["keperluan"])) ?></div>
                        <?php if ($request["dokumen"]): ?><a class="small" href="<?= htmlspecialchars($request["dokumen"]) ?>" target="_blank">Lihat dokumen pendukung</a><?php endif; ?>
                        <?php if ($request["status"]==="Ditolak" && $request["alasan_penolakan"]): ?><div class="alert alert-danger mt-3 mb-0"><strong>Alasan:</strong> <?= htmlspecialchars($request["alasan_penolakan"]) ?></div><?php endif;?>
                    </div>
                    <?php if ($request["status"]==="Menunggu"): ?>
                        <div class="card-footer bg-white d-flex gap-2">
                            <form method="post"><input type="hidden" name="action" value="approve"><input type="hidden" name="id_pengajuan" value="<?= (int)$request["id_pengajuan"] ?>"><button class="btn btn-success btn-sm">Setujui</button></form>
                            <button class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#reject<?= (int)$request["id_pengajuan"] ?>">Tolak</button>
                            <div class="modal fade" id="reject<?= (int)$request["id_pengajuan"] ?>" tabindex="-1"><div class="modal-dialog"><form class="modal-content" method="post">
                                <div class="modal-header"><h2 class="modal-title h5">Alasan Penolakan</h2><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                                <div class="modal-body"><input type="hidden" name="action" value="reject"><input type="hidden" name="id_pengajuan" value="<?= (int)$request["id_pengajuan"] ?>"><textarea class="form-control" name="alasan_penolakan" rows="4" required></textarea></div>
                                <div class="modal-footer"><button class="btn btn-danger">Tolak Pengajuan</button></div>
                            </form></div></div>
                        </div>
                    <?php endif;?>
                </div>
            </div>
        <?php endforeach;?>
        <?php if (!$requests): ?><div class="col-12"><div class="card border-0 shadow-sm text-center text-muted py-5">Belum ada pengajuan masuk.</div></div><?php endif;?>
    </div>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
</body></html>

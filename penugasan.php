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

$employeeResult = $conn->query("SELECT id_karyawan,nama,nip,jabatan,departemen FROM karyawan ORDER BY nama");
$employees = [];
while ($row = $employeeResult->fetch_assoc()) $employees[] = $row;

$atasanResult = $conn->prepare("SELECT id_karyawan FROM karyawan WHERE id_user=?");
$atasanResult->bind_param("i", $_SESSION["user_id"]);
$atasanResult->execute();
$atasan = $atasanResult->get_result()->fetch_assoc();
$atasanResult->close();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "";
    if ($action === "delete") {
        $id = (int) ($_POST["id_penugasan"] ?? 0);
        $stmt = $conn->prepare("DELETE FROM penugasan WHERE id_penugasan=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $message = "Penugasan berhasil dihapus.";
        $stmt->close();
    } elseif ($action === "complete") {
        $id = (int) ($_POST["id_penugasan"] ?? 0);
        $stmt = $conn->prepare("UPDATE penugasan SET status='Selesai' WHERE id_penugasan=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $message = "Penugasan berhasil ditandai selesai.";
        $stmt->close();
    } else {
        $idEmployee = (int) ($_POST["id_karyawan"] ?? 0);
        $tujuan = trim($_POST["tujuan"] ?? "");
        $berangkat = $_POST["tanggal_berangkat"] ?? "";
        $pulang = $_POST["tanggal_pulang"] ?? "";
        $keperluan = trim($_POST["keperluan"] ?? "");

        if (!$atasan) {
            $error = "Profil karyawan atasan belum tersedia. Tambahkan akun atasan ke data karyawan.";
        } elseif ($idEmployee < 1 || $tujuan === "" || $berangkat === "" || $pulang === "" || $keperluan === "") {
            $error = "Semua kolom penugasan wajib diisi.";
        } elseif ($pulang < $berangkat) {
            $error = "Tanggal pulang tidak boleh sebelum tanggal berangkat.";
        } else {
            $atasanId = (int) $atasan["id_karyawan"];
            $stmt = $conn->prepare(
                "INSERT INTO penugasan (id_pengajuan,id_karyawan,id_atasan,jenis,tujuan,tanggal_berangkat,tanggal_pulang,keperluan,status)
                 VALUES (NULL,?,?, 'Langsung',?,?,?,?, 'Aktif')"
            );
            $stmt->bind_param("iissss", $idEmployee, $atasanId, $tujuan, $berangkat, $pulang, $keperluan);
            if ($stmt->execute()) $message = "Penugasan berhasil diberikan.";
            else $error = "Penugasan gagal disimpan.";
            $stmt->close();
        }
    }
}

$assignments = [];
$result = $conn->query(
    "SELECT t.*, k.nama,k.nip,a.nama AS nama_atasan
     FROM penugasan t
     JOIN karyawan k ON k.id_karyawan=t.id_karyawan
     JOIN karyawan a ON a.id_karyawan=t.id_atasan
     ORDER BY t.id_penugasan DESC"
);
while ($row = $result->fetch_assoc()) $assignments[] = $row;
$conn->close();
?>
<!doctype html>
<html lang="id">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Penugasan</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="bg-light">
<nav class="navbar navbar-light bg-white border-bottom"><div class="container"><a class="navbar-brand fw-bold" href="dashboard_atasan.php">← Dashboard Atasan</a></div></nav>
<main class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3"><h1 class="h4 mb-0">Penugasan Karyawan</h1><button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAssignment">＋ Buat Penugasan</button></div>
    <?php if ($message): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <div class="card border-0 shadow-sm"><div class="table-responsive"><table class="table table-hover align-middle mb-0">
        <thead class="table-light"><tr><th>Karyawan</th><th>Tujuan</th><th>Tanggal</th><th>Jenis</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
        <?php foreach ($assignments as $assignment): ?>
            <tr>
                <td><?= htmlspecialchars($assignment["nama"]) ?><div class="small text-muted"><?= htmlspecialchars($assignment["nip"]) ?></div></td>
                <td><?= htmlspecialchars($assignment["tujuan"]) ?></td>
                <td><?= date("d/m/Y", strtotime($assignment["tanggal_berangkat"])) ?> - <?= date("d/m/Y", strtotime($assignment["tanggal_pulang"])) ?></td>
                <td><?= htmlspecialchars($assignment["jenis"]) ?></td>
                <td><span class="badge bg-<?= $assignment["status"]==="Aktif"?"primary":"success" ?>"><?= htmlspecialchars($assignment["status"]) ?></span></td>
                <td>
                    <?php if ($assignment["status"] === "Aktif"): ?>
                        <form method="post" class="d-inline"><input type="hidden" name="action" value="complete"><input type="hidden" name="id_penugasan" value="<?= (int)$assignment["id_penugasan"] ?>"><button class="btn btn-sm btn-outline-success">Selesaikan</button></form>
                    <?php endif; ?>
                    <form method="post" class="d-inline" onsubmit="return confirm('Hapus penugasan ini?')"><input type="hidden" name="action" value="delete"><input type="hidden" name="id_penugasan" value="<?= (int)$assignment["id_penugasan"] ?>"><button class="btn btn-sm btn-outline-danger">Hapus</button></form>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$assignments): ?><tr><td colspan="6" class="text-center text-muted py-4">Belum ada penugasan.</td></tr><?php endif; ?>
        </tbody></table></div></div>
</main>
<div class="modal fade" id="addAssignment" tabindex="-1"><div class="modal-dialog modal-lg"><form class="modal-content" method="post">
    <div class="modal-header"><h2 class="modal-title h5">Buat Penugasan Langsung</h2><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div class="mb-3"><label class="form-label">Karyawan yang Ditugaskan</label><select class="form-select" name="id_karyawan" required><option value="">Pilih karyawan</option><?php foreach ($employees as $employee): ?><option value="<?= (int)$employee["id_karyawan"] ?>"><?= htmlspecialchars($employee["nama"]) ?> - <?= htmlspecialchars($employee["nip"]) ?></option><?php endforeach;?></select></div>
        <div class="mb-3"><label class="form-label">Tujuan</label><input class="form-control" name="tujuan" maxlength="150" required></div>
        <div class="row g-3"><div class="col-md-6"><label class="form-label">Tanggal Berangkat</label><input type="date" class="form-control" name="tanggal_berangkat" required></div><div class="col-md-6"><label class="form-label">Tanggal Pulang</label><input type="date" class="form-control" name="tanggal_pulang" required></div></div>
        <div class="mt-3"><label class="form-label">Keperluan</label><textarea class="form-control" name="keperluan" rows="4" required></textarea></div>
    </div>
    <div class="modal-footer"><button class="btn btn-primary">Berikan Penugasan</button></div>
</form></div></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
</body></html>

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
    $action = $_POST["action"] ?? "";

    if ($action === "delete") {
        $id = (int) ($_POST["id_karyawan"] ?? 0);
        $stmt = $conn->prepare("DELETE FROM karyawan WHERE id_karyawan=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $message = "Data karyawan berhasil dihapus.";
        $stmt->close();
    } else {
        $idUser = (int) ($_POST["id_user"] ?? 0);
        $nama = trim($_POST["nama"] ?? "");
        $nip = trim($_POST["nip"] ?? "");
        $jabatan = trim($_POST["jabatan"] ?? "");
        $departemen = trim($_POST["departemen"] ?? "");
        $noHp = trim($_POST["no_hp"] ?? "");

        if ($idUser < 1 || $nama === "" || $nip === "" || $jabatan === "" || $departemen === "") {
            $error = "Semua data wajib kecuali nomor HP.";
        } elseif ($action === "add") {
            $stmt = $conn->prepare("INSERT INTO karyawan (id_user,nama,nip,jabatan,departemen,no_hp) VALUES (?,?,?,?,?,?)");
            $stmt->bind_param("isssss", $idUser, $nama, $nip, $jabatan, $departemen, $noHp);
            if ($stmt->execute()) $message = "Data karyawan berhasil ditambahkan.";
            else $error = "Gagal menambahkan data. Pastikan user, NIP, atau akun belum digunakan.";
            $stmt->close();
        } elseif ($action === "edit") {
            $id = (int) ($_POST["id_karyawan"] ?? 0);
            $stmt = $conn->prepare("UPDATE karyawan SET id_user=?,nama=?,nip=?,jabatan=?,departemen=?,no_hp=? WHERE id_karyawan=?");
            $stmt->bind_param("isssssi", $idUser, $nama, $nip, $jabatan, $departemen, $noHp, $id);
            if ($stmt->execute()) $message = "Data karyawan berhasil diperbarui.";
            else $error = "Gagal memperbarui data karyawan.";
            $stmt->close();
        }
    }
}

$employees = [];
$result = $conn->query(
    "SELECT k.*, u.username, u.email, u.role
     FROM karyawan k LEFT JOIN users u ON u.id=k.id_user
     ORDER BY k.nama"
);
while ($row = $result->fetch_assoc()) $employees[] = $row;

$availableUsers = [];
$result = $conn->query(
    "SELECT u.id,u.name,u.username
     FROM users u
     LEFT JOIN karyawan k ON k.id_user=u.id
     WHERE k.id_karyawan IS NULL
     ORDER BY u.name"
);
while ($row = $result->fetch_assoc()) $availableUsers[] = $row;
$conn->close();
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Data Karyawan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-light bg-white border-bottom"><div class="container"><a class="navbar-brand fw-bold" href="dashboard_atasan.php">← Dashboard Atasan</a></div></nav>
<main class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">Data Karyawan</h1>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">＋ Tambah Karyawan</button>
    </div>
    <?php if ($message): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <div class="card border-0 shadow-sm"><div class="table-responsive">
        <table class="table table-hover align-middle mb-0"><thead class="table-light"><tr><th>NIP</th><th>Nama</th><th>Jabatan</th><th>Departemen</th><th>No. HP</th><th>Aksi</th></tr></thead><tbody>
        <?php foreach ($employees as $employee): ?>
            <tr>
                <td><?= htmlspecialchars($employee["nip"]) ?></td><td><?= htmlspecialchars($employee["nama"]) ?></td>
                <td><?= htmlspecialchars($employee["jabatan"]) ?></td><td><?= htmlspecialchars($employee["departemen"]) ?></td>
                <td><?= htmlspecialchars($employee["no_hp"] ?? "-") ?></td>
                <td>
                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#edit<?= (int)$employee["id_karyawan"] ?>">Edit</button>
                    <form class="d-inline" method="post" onsubmit="return confirm('Hapus data karyawan ini?')">
                        <input type="hidden" name="action" value="delete"><input type="hidden" name="id_karyawan" value="<?= (int)$employee["id_karyawan"] ?>">
                        <button class="btn btn-sm btn-outline-danger">Hapus</button>
                    </form>
                </td>
            </tr>
            <div class="modal fade" id="edit<?= (int)$employee["id_karyawan"] ?>" tabindex="-1"><div class="modal-dialog"><form class="modal-content" method="post">
                <div class="modal-header"><h2 class="modal-title h5">Edit Karyawan</h2><button class="btn-close" type="button" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit"><input type="hidden" name="id_karyawan" value="<?= (int)$employee["id_karyawan"] ?>">
                    <input type="hidden" name="id_user" value="<?= (int)$employee["id_user"] ?>">
                    <div class="mb-3"><label class="form-label">Nama</label><input class="form-control" name="nama" value="<?= htmlspecialchars($employee["nama"]) ?>" required></div>
                    <div class="mb-3"><label class="form-label">NIP</label><input class="form-control" name="nip" value="<?= htmlspecialchars($employee["nip"]) ?>" required></div>
                    <div class="mb-3"><label class="form-label">Jabatan</label><input class="form-control" name="jabatan" value="<?= htmlspecialchars($employee["jabatan"]) ?>" required></div>
                    <div class="mb-3"><label class="form-label">Departemen</label><input class="form-control" name="departemen" value="<?= htmlspecialchars($employee["departemen"]) ?>" required></div>
                    <div class="mb-3"><label class="form-label">No. HP</label><input class="form-control" name="no_hp" value="<?= htmlspecialchars($employee["no_hp"] ?? "") ?>"></div>
                </div>
                <div class="modal-footer"><button class="btn btn-primary">Simpan</button></div>
            </form></div></div>
        <?php endforeach; ?>
        <?php if (!$employees): ?><tr><td colspan="6" class="text-center text-muted py-4">Belum ada data karyawan.</td></tr><?php endif; ?>
        </tbody></table>
    </div></div>
</main>
<div class="modal fade" id="addModal" tabindex="-1"><div class="modal-dialog"><form class="modal-content" method="post">
    <div class="modal-header"><h2 class="modal-title h5">Tambah Karyawan</h2><button class="btn-close" type="button" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <input type="hidden" name="action" value="add">
        <div class="mb-3"><label class="form-label">Akun Pengguna</label><select class="form-select" name="id_user" required><option value="">Pilih akun</option><?php foreach ($availableUsers as $user): ?><option value="<?= (int)$user["id"] ?>"><?= htmlspecialchars($user["name"]) ?> (<?= htmlspecialchars($user["username"]) ?>)</option><?php endforeach; ?></select></div>
        <div class="mb-3"><label class="form-label">Nama</label><input class="form-control" name="nama" required></div>
        <div class="mb-3"><label class="form-label">NIP</label><input class="form-control" name="nip" required></div>
        <div class="mb-3"><label class="form-label">Jabatan</label><input class="form-control" name="jabatan" required></div>
        <div class="mb-3"><label class="form-label">Departemen</label><input class="form-control" name="departemen" required></div>
        <div class="mb-3"><label class="form-label">No. HP</label><input class="form-control" name="no_hp"></div>
    </div>
    <div class="modal-footer"><button class="btn btn-primary">Tambah</button></div>
</form></div></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
</body></html>

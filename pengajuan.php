<?php
session_start();
require_once __DIR__ . "/connection.php";

if (!isset($_SESSION["user_id"]) || ($_SESSION["role"] ?? "") !== "karyawan") {
    header("Location: login.php");
    exit;
}

$conn = connection();
$errors = [];
$success = "";

$stmt = $conn->prepare("SELECT id_karyawan, nip, jabatan, departemen, no_hp FROM karyawan WHERE id_user = ?");
$stmt->bind_param("i", $_SESSION["user_id"]);
$stmt->execute();
$employee = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $tujuan = trim($_POST["tujuan"] ?? "");
    $tanggalBerangkat = $_POST["tanggal_berangkat"] ?? "";
    $tanggalPulang = $_POST["tanggal_pulang"] ?? "";
    $keperluan = trim($_POST["keperluan"] ?? "");

    if ($tujuan === "" || $tanggalBerangkat === "" || $tanggalPulang === "" || $keperluan === "") {
        $errors[] = "Semua kolom wajib diisi.";
    } elseif ($tanggalPulang < $tanggalBerangkat) {
        $errors[] = "Tanggal pulang tidak boleh sebelum tanggal berangkat.";
    }

    if (!$employee) {
        $nip = trim($_POST["nip"] ?? "");
        $jabatan = trim($_POST["jabatan"] ?? "");
        $departemen = trim($_POST["departemen"] ?? "");
        $noHp = trim($_POST["no_hp"] ?? "");

        if ($nip === "" || $jabatan === "" || $departemen === "") {
            $errors[] = "NIP, jabatan, dan departemen wajib diisi.";
        } else {
            $insertEmployee = $conn->prepare(
                "INSERT INTO karyawan (id_user, nama, nip, jabatan, departemen, no_hp) VALUES (?, ?, ?, ?, ?, ?)"
            );
            $insertEmployee->bind_param(
                "isssss",
                $_SESSION["user_id"],
                $_SESSION["name"],
                $nip,
                $jabatan,
                $departemen,
                $noHp
            );
            if ($insertEmployee->execute()) {
                $employee = [
                    "id_karyawan" => $insertEmployee->insert_id,
                    "nip" => $nip,
                    "jabatan" => $jabatan,
                    "departemen" => $departemen,
                    "no_hp" => $noHp,
                ];
            } else {
                $errors[] = "NIP sudah digunakan atau profil karyawan gagal disimpan.";
            }
            $insertEmployee->close();
        }
    }

    $dokumen = null;
    if (!empty($_FILES["dokumen"]["name"])) {
        $allowedTypes = ["application/pdf", "image/jpeg", "image/png"];
        $allowedExtensions = ["pdf", "jpg", "jpeg", "png"];
        $extension = strtolower(pathinfo($_FILES["dokumen"]["name"], PATHINFO_EXTENSION));

        if ($_FILES["dokumen"]["error"] !== UPLOAD_ERR_OK) {
            $errors[] = "Dokumen gagal diunggah.";
        } elseif ($_FILES["dokumen"]["size"] > 2 * 1024 * 1024) {
            $errors[] = "Ukuran dokumen maksimal 2 MB.";
        } elseif (!in_array($_FILES["dokumen"]["type"], $allowedTypes, true)
            || !in_array($extension, $allowedExtensions, true)) {
            $errors[] = "Dokumen harus berformat PDF, JPG, atau PNG.";
        } else {
            $uploadDirectory = __DIR__ . "/uploads/dokumen";
            if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0775, true)) {
                $errors[] = "Folder dokumen gagal dibuat.";
            } else {
                $filename = "dokumen_" . time() . "_" . bin2hex(random_bytes(6)) . "." . $extension;
                if (move_uploaded_file($_FILES["dokumen"]["tmp_name"], $uploadDirectory . "/" . $filename)) {
                    $dokumen = "uploads/dokumen/" . $filename;
                } else {
                    $errors[] = "Dokumen gagal disimpan.";
                }
            }
        }
    }

    if (!$errors && $employee) {
        $insert = $conn->prepare(
            "INSERT INTO pengajuan
             (id_karyawan, tujuan, tanggal_berangkat, tanggal_pulang, keperluan, dokumen)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $insert->bind_param(
            "isssss",
            $employee["id_karyawan"],
            $tujuan,
            $tanggalBerangkat,
            $tanggalPulang,
            $keperluan,
            $dokumen
        );
        if ($insert->execute()) {
            $success = "Pengajuan perjalanan dinas berhasil dikirim.";
            $_POST = [];
        } else {
            $errors[] = "Pengajuan gagal disimpan.";
        }
        $insert->close();
    }
}

$conn->close();
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pengajuan Perjalanan Dinas</title>
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
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <h1 class="h4 mb-4">Pengajuan Perjalanan Dinas</h1>

                    <?php if ($errors): ?>
                        <div class="alert alert-danger">
                            <?php foreach ($errors as $error): ?>
                                <div><?= htmlspecialchars($error) ?></div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                    <?php endif; ?>

                    <form method="post" enctype="multipart/form-data">
                        <?php if (!$employee): ?>
                            <h2 class="h6 mb-3">Data Karyawan</h2>
                            <div class="row g-3 mb-4">
                                <div class="col-md-4">
                                    <label class="form-label" for="nip">NIP</label>
                                    <input class="form-control" id="nip" name="nip" value="<?= htmlspecialchars($_POST["nip"] ?? "") ?>" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="jabatan">Jabatan</label>
                                    <input class="form-control" id="jabatan" name="jabatan" value="<?= htmlspecialchars($_POST["jabatan"] ?? "") ?>" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="departemen">Departemen</label>
                                    <input class="form-control" id="departemen" name="departemen" value="<?= htmlspecialchars($_POST["departemen"] ?? "") ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="no_hp">No. HP</label>
                                    <input class="form-control" id="no_hp" name="no_hp" value="<?= htmlspecialchars($_POST["no_hp"] ?? "") ?>">
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="mb-3">
                            <label class="form-label" for="tujuan">Tujuan Perjalanan</label>
                            <input class="form-control" id="tujuan" name="tujuan" maxlength="150" value="<?= htmlspecialchars($_POST["tujuan"] ?? "") ?>" required>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="tanggal_berangkat">Tanggal Berangkat</label>
                                <input class="form-control" type="date" id="tanggal_berangkat" name="tanggal_berangkat" value="<?= htmlspecialchars($_POST["tanggal_berangkat"] ?? "") ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="tanggal_pulang">Tanggal Pulang</label>
                                <input class="form-control" type="date" id="tanggal_pulang" name="tanggal_pulang" value="<?= htmlspecialchars($_POST["tanggal_pulang"] ?? "") ?>" required>
                            </div>
                        </div>
                        <div class="mb-3 mt-3">
                            <label class="form-label" for="keperluan">Keperluan</label>
                            <textarea class="form-control" id="keperluan" name="keperluan" rows="4" required><?= htmlspecialchars($_POST["keperluan"] ?? "") ?></textarea>
                        </div>
                        <div class="mb-4">
                            <label class="form-label" for="dokumen">Dokumen Pendukung <span class="text-muted">(opsional)</span></label>
                            <input class="form-control" type="file" id="dokumen" name="dokumen" accept=".pdf,.jpg,.jpeg,.png">
                            <div class="form-text">PDF, JPG, atau PNG. Maksimal 2 MB.</div>
                        </div>
                        <button class="btn btn-primary" type="submit">Kirim Pengajuan</button>
                        <a class="btn btn-outline-secondary" href="riwayat_pengajuan.php">Riwayat Pengajuan</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>
</body>
</html>

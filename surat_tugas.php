<?php
session_start();
require_once __DIR__ . "/connection.php";

if (!isset($_SESSION["user_id"]) || !in_array($_SESSION["role"] ?? "", ["karyawan", "atasan"], true)) {
    header("Location: login.php");
    exit;
}

$role = $_SESSION["role"];
$conn = connection();
$message = "";
$error = "";

if ($role === "atasan" && $_SERVER["REQUEST_METHOD"] === "POST") {
    $idPenugasan = (int) ($_POST["id_penugasan"] ?? 0);
    $nomorSurat = trim($_POST["nomor_surat"] ?? "");
    $tanggalTerbit = $_POST["tanggal_terbit"] ?? "";

    if ($idPenugasan < 1 || $nomorSurat === "" || $tanggalTerbit === "") {
        $error = "Penugasan, nomor surat, dan tanggal terbit wajib diisi.";
    } else {
        $atasanProfile = $conn->prepare("SELECT id_karyawan FROM karyawan WHERE id_user = ?");
        $atasanProfile->bind_param("i", $_SESSION["user_id"]);
        $atasanProfile->execute();
        $atasan = $atasanProfile->get_result()->fetch_assoc();
        $atasanProfile->close();

        if (!$atasan) {
            $name = $_SESSION["name"];
            $nip = "AT-" . str_pad((string) $_SESSION["user_id"], 6, "0", STR_PAD_LEFT);
            $createProfile = $conn->prepare(
                "INSERT INTO karyawan (id_user,nama,nip,jabatan,departemen,no_hp)
                 VALUES (?, ?, ?, 'Atasan', 'Umum', '')"
            );
            $createProfile->bind_param("iss", $_SESSION["user_id"], $name, $nip);
            $createProfile->execute();
            $idAtasan = $createProfile->insert_id;
            $createProfile->close();
        } else {
            $idAtasan = (int) $atasan["id_karyawan"];
        }

        $stmt = $conn->prepare(
            "INSERT INTO surat_tugas (id_penugasan, id_atasan, nomor_surat, tanggal_terbit)
             VALUES (?, ?, ?, ?)"
        );
        $stmt->bind_param("iiss", $idPenugasan, $idAtasan, $nomorSurat, $tanggalTerbit);
        try {
            if ($stmt->execute()) {
                $message = "Surat tugas berhasil diterbitkan.";
            }
        } catch (mysqli_sql_exception $exception) {
            if ($conn->errno === 1062) {
                $checkNumber = $conn->prepare("SELECT id_surat FROM surat_tugas WHERE nomor_surat = ?");
                $checkNumber->bind_param("s", $nomorSurat);
                $checkNumber->execute();
                $checkNumber->store_result();
                if ($checkNumber->num_rows > 0) {
                    $error = "Nomor surat sudah digunakan. Gunakan nomor surat yang berbeda.";
                } else {
                    $error = "Penugasan ini sudah memiliki surat tugas.";
                }
                $checkNumber->close();
            } else {
                $error = "Surat tugas gagal diterbitkan.";
            }
        }
        $stmt->close();
    }
}

$availableAssignments = [];
if ($role === "atasan") {
    $result = $conn->query(
        "SELECT t.id_penugasan, t.tujuan, t.tanggal_berangkat, t.tanggal_pulang,
                k.nama, k.nip
         FROM penugasan t
         JOIN karyawan k ON k.id_karyawan = t.id_karyawan
         LEFT JOIN surat_tugas s ON s.id_penugasan = t.id_penugasan
         WHERE s.id_surat IS NULL
         ORDER BY t.id_penugasan DESC"
    );
    while ($row = $result->fetch_assoc()) {
        $availableAssignments[] = $row;
    }
}

$letters = [];
if ($role === "atasan") {
    $sql = "SELECT s.*, t.tujuan, t.tanggal_berangkat, t.tanggal_pulang, t.keperluan,
                   t.status AS status_penugasan, t.jenis,
                   k.nama, k.nip, k.jabatan, k.departemen
            FROM surat_tugas s
            JOIN penugasan t ON t.id_penugasan = s.id_penugasan
            JOIN karyawan k ON k.id_karyawan = t.id_karyawan
            ORDER BY s.tanggal_terbit DESC, s.id_surat DESC";
    $result = $conn->query($sql);
} else {
    $sql = "SELECT s.*, t.tujuan, t.tanggal_berangkat, t.tanggal_pulang, t.keperluan,
                   t.status AS status_penugasan, t.jenis,
                   k.nama, k.nip, k.jabatan, k.departemen
            FROM surat_tugas s
            JOIN penugasan t ON t.id_penugasan = s.id_penugasan
            JOIN karyawan k ON k.id_karyawan = t.id_karyawan
            WHERE k.id_user = ?
            ORDER BY s.tanggal_terbit DESC, s.id_surat DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $_SESSION["user_id"]);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
}
while ($row = $result->fetch_assoc()) {
    $letters[] = $row;
}
$conn->close();
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Surat Tugas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<style>
.back-circle{width:38px;height:38px;display:inline-grid;place-items:center;border:1px solid #d7deea;border-radius:50%;background:#fff;color:#2563eb;text-decoration:none;font-weight:700}
.back-circle:hover{background:#eef4ff;color:#1d4ed8}
</style>
<nav class="navbar navbar-light bg-white border-bottom">
    <div class="container">
        <a class="back-circle" href="<?= $role === "atasan" ? "dashboard_atasan.php" : "index.php" ?>" aria-label="Kembali ke Dashboard">←</a>
    </div>
</nav>
<main class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">Surat Tugas</h1>
        <?php if ($role === "atasan"): ?>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#letterModal">＋ Buat Surat Tugas</button>
        <?php endif; ?>
    </div>

    <?php if ($message): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Nomor Surat</th>
                        <th>Karyawan</th>
                        <th>Tujuan</th>
                        <th>Tanggal</th>
                        <th>Jenis</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($letters as $letter): ?>
                        <tr>
                            <td><?= htmlspecialchars($letter["nomor_surat"]) ?></td>
                            <td><?= htmlspecialchars($letter["nama"]) ?><div class="small text-muted"><?= htmlspecialchars($letter["nip"]) ?></div></td>
                            <td><?= htmlspecialchars($letter["tujuan"]) ?></td>
                            <td><?= date("d/m/Y", strtotime($letter["tanggal_berangkat"])) ?> - <?= date("d/m/Y", strtotime($letter["tanggal_pulang"])) ?></td>
                            <td><?= htmlspecialchars($letter["jenis"]) ?></td>
                            <td>
                                <a class="btn btn-sm btn-outline-primary" href="cetak_surat.php?id=<?= (int) $letter["id_surat"] ?>" target="_blank">Cetak/Unduh</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$letters): ?>
                        <tr><td colspan="6" class="text-center text-muted py-4">Belum ada surat tugas.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<?php if ($role === "atasan"): ?>
<div class="modal fade" id="letterModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form class="modal-content" method="post">
            <div class="modal-header">
                <h2 class="modal-title h5">Terbitkan Surat Tugas</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Penugasan</label>
                    <select class="form-select" name="id_penugasan" required>
                        <option value="">Pilih penugasan</option>
                        <?php foreach ($availableAssignments as $assignment): ?>
                            <option value="<?= (int) $assignment["id_penugasan"] ?>">
                                <?= htmlspecialchars($assignment["nama"]) ?> - <?= htmlspecialchars($assignment["tujuan"]) ?>
                                (<?= date("d/m/Y", strtotime($assignment["tanggal_berangkat"])) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (!$availableAssignments): ?>
                        <div class="form-text text-danger">Tidak ada penugasan yang belum memiliki surat.</div>
                    <?php endif; ?>
                </div>
                <div class="mb-3">
                    <label class="form-label">Nomor Surat</label>
                    <input class="form-control" name="nomor_surat" maxlength="50" placeholder="001/ST/HRD/XI/2026" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Tanggal Terbit</label>
                    <input type="date" class="form-control" name="tanggal_terbit" value="<?= date("Y-m-d") ?>" required>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary" <?= !$availableAssignments ? "disabled" : "" ?>>Terbitkan</button>
            </div>
        </form>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
<?php endif; ?>
</body>
</html>

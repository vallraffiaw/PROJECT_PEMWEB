<?php
session_start();
require_once __DIR__ . "/connection.php";

if (!isset($_SESSION["user_id"]) || ($_SESSION["role"] ?? "") !== "atasan") {
    header("Location: login.php");
    exit;
}

$conn = connection();
$tanggalMulai = $_GET["tanggal_mulai"] ?? "";
$tanggalSelesai = $_GET["tanggal_selesai"] ?? "";
$nama = trim($_GET["nama"] ?? "");
$status = $_GET["status"] ?? "";
$jenis = $_GET["jenis"] ?? "";

$sql = "SELECT t.id_penugasan, t.tujuan, t.tanggal_berangkat, t.tanggal_pulang,
               t.status, t.jenis, k.nama, k.nip, k.departemen
        FROM penugasan t
        JOIN karyawan k ON k.id_karyawan = t.id_karyawan
        WHERE 1=1";
$types = "";
$params = [];

if ($tanggalMulai !== "") {
    $sql .= " AND t.tanggal_berangkat >= ?";
    $types .= "s";
    $params[] = $tanggalMulai;
}
if ($tanggalSelesai !== "") {
    $sql .= " AND t.tanggal_pulang <= ?";
    $types .= "s";
    $params[] = $tanggalSelesai;
}
if ($nama !== "") {
    $sql .= " AND k.nama LIKE ?";
    $types .= "s";
    $params[] = "%" . $nama . "%";
}
if (in_array($status, ["Aktif", "Selesai"], true)) {
    $sql .= " AND t.status = ?";
    $types .= "s";
    $params[] = $status;
}
if (in_array($jenis, ["Pengajuan", "Langsung"], true)) {
    $sql .= " AND t.jenis = ?";
    $types .= "s";
    $params[] = $jenis;
}
$sql .= " ORDER BY t.tanggal_berangkat DESC, t.id_penugasan DESC";

$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$reports = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Laporan Perjalanan Dinas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>@media print { .no-print { display:none; } body { background:#fff; } }</style>
</head>
<body class="bg-light">
<nav class="navbar navbar-light bg-white border-bottom no-print">
    <div class="container"><a class="navbar-brand fw-bold" href="dashboard_atasan.php">← Dashboard Atasan</a></div>
</nav>
<main class="container-fluid px-lg-5 py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">Laporan Perjalanan Dinas</h1>
        <button class="btn btn-outline-primary no-print" onclick="window.print()">Cetak Laporan</button>
    </div>

    <div class="card border-0 shadow-sm mb-3 no-print">
        <div class="card-body">
            <form class="row g-2" method="get">
                <div class="col-md-2">
                    <label class="form-label">Tanggal Mulai</label>
                    <input type="date" class="form-control" name="tanggal_mulai" value="<?= htmlspecialchars($tanggalMulai) ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Tanggal Selesai</label>
                    <input type="date" class="form-control" name="tanggal_selesai" value="<?= htmlspecialchars($tanggalSelesai) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Nama Karyawan</label>
                    <input type="text" class="form-control" name="nama" value="<?= htmlspecialchars($nama) ?>" placeholder="Cari nama">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="status">
                        <option value="">Semua</option>
                        <option value="Aktif" <?= $status === "Aktif" ? "selected" : "" ?>>Aktif</option>
                        <option value="Selesai" <?= $status === "Selesai" ? "selected" : "" ?>>Selesai</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Jenis</label>
                    <select class="form-select" name="jenis">
                        <option value="">Semua</option>
                        <option value="Pengajuan" <?= $jenis === "Pengajuan" ? "selected" : "" ?>>Pengajuan</option>
                        <option value="Langsung" <?= $jenis === "Langsung" ? "selected" : "" ?>>Langsung</option>
                    </select>
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <button class="btn btn-primary w-100">Cari</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>No.</th>
                        <th>Karyawan</th>
                        <th>NIP</th>
                        <th>Departemen</th>
                        <th>Tujuan</th>
                        <th>Tanggal</th>
                        <th>Jenis</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reports as $index => $report): ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <td><?= htmlspecialchars($report["nama"]) ?></td>
                            <td><?= htmlspecialchars($report["nip"]) ?></td>
                            <td><?= htmlspecialchars($report["departemen"]) ?></td>
                            <td><?= htmlspecialchars($report["tujuan"]) ?></td>
                            <td><?= date("d/m/Y", strtotime($report["tanggal_berangkat"])) ?> - <?= date("d/m/Y", strtotime($report["tanggal_pulang"])) ?></td>
                            <td><?= htmlspecialchars($report["jenis"]) ?></td>
                            <td><?= htmlspecialchars($report["status"]) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$reports): ?>
                        <tr><td colspan="8" class="text-center text-muted py-4">Tidak ada data laporan.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>
</body>
</html>

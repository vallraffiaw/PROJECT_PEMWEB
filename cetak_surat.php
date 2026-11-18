<?php
session_start();
require_once __DIR__ . "/connection.php";

if (!isset($_SESSION["user_id"]) || !in_array($_SESSION["role"] ?? "", ["karyawan", "atasan"], true)) {
    header("Location: login.php");
    exit;
}

$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);
if (!$id) {
    http_response_code(404);
    exit("Surat tidak ditemukan.");
}

$conn = connection();
$sql = "SELECT s.*, t.tujuan, t.tanggal_berangkat, t.tanggal_pulang, t.keperluan,
               k.nama, k.nip, k.jabatan, k.departemen
        FROM surat_tugas s
        JOIN penugasan t ON t.id_penugasan=s.id_penugasan
        JOIN karyawan k ON k.id_karyawan=t.id_karyawan";

if ($_SESSION["role"] === "atasan") {
    $stmt = $conn->prepare($sql . " WHERE s.id_surat=?");
    $stmt->bind_param("i", $id);
} else {
    $sql .= " WHERE s.id_surat=? AND k.id_user=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $id, $_SESSION["user_id"]);
}
$stmt->execute();
$letter = $stmt->get_result()->fetch_assoc();
$stmt->close();
$conn->close();

if (!$letter) {
    http_response_code(404);
    exit("Surat tidak ditemukan atau tidak dapat diakses.");
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Surat Tugas <?= htmlspecialchars($letter["nomor_surat"]) ?></title>
    <style>
        body { max-width:800px; margin:40px auto; font-family:Arial,sans-serif; color:#111; }
        .company { text-align:center; border-bottom:2px solid #111; padding-bottom:12px; }
        h1,h2 { text-align:center; margin:8px 0; }
        table { width:100%; border-collapse:collapse; }
        td { padding:5px 8px; vertical-align:top; }
        .label { width:150px; }
        .sign { margin-top:55px; text-align:right; }
        .sign-name { margin-top:70px; }
        @media print { .no-print { display:none; } body { margin:0; } }
    </style>
</head>
<body>
    <button class="no-print" onclick="window.print()">Cetak / Simpan PDF</button>
    <div class="company">
        <strong>PERUSAHAAN XYZ</strong><br>
        YOGYAKARTA
    </div>
    <h2>SURAT TUGAS</h2>
    <p style="text-align:center;margin-top:0;">Nomor: <?= htmlspecialchars($letter["nomor_surat"]) ?></p>
    <p>Yang bertanda tangan di bawah ini memberikan tugas kepada:</p>
    <table>
        <tr><td class="label">Nama</td><td>: <?= htmlspecialchars($letter["nama"]) ?></td></tr>
        <tr><td class="label">NIP</td><td>: <?= htmlspecialchars($letter["nip"]) ?></td></tr>
        <tr><td class="label">Jabatan</td><td>: <?= htmlspecialchars($letter["jabatan"]) ?></td></tr>
        <tr><td class="label">Departemen</td><td>: <?= htmlspecialchars($letter["departemen"]) ?></td></tr>
        <tr><td class="label">Tujuan</td><td>: <?= htmlspecialchars($letter["tujuan"]) ?></td></tr>
        <tr><td class="label">Tanggal</td><td>: <?= date("d/m/Y", strtotime($letter["tanggal_berangkat"])) ?> - <?= date("d/m/Y", strtotime($letter["tanggal_pulang"])) ?></td></tr>
        <tr><td class="label">Keperluan</td><td>: <?= htmlspecialchars($letter["keperluan"]) ?></td></tr>
    </table>
    <p>Untuk melaksanakan perjalanan dinas sesuai dengan informasi tersebut.</p>
    <div class="sign">
        Yogyakarta, <?= date("d F Y", strtotime($letter["tanggal_terbit"])) ?><br>
        Atasan,<br>
        <div class="sign-name">(Nama Atasan)</div>
    </div>
</body>
</html>

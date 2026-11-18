<?php

function connection(): mysqli
{
    $servername = "localhost";
    $user = "root";
    $password = ""; // isi pw nya klo ada
    $dbname = "sistem_sdm_projek_PW";

    $conn = new mysqli($servername, $user, $password);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    if (!$conn->query("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci")) {
        die("Error creating database: " . $conn->error);
    }

    $conn->select_db($dbname);

    $createUsers = "CREATE TABLE IF NOT EXISTS users (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        username VARCHAR(50) NOT NULL UNIQUE,
        email VARCHAR(150) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        role ENUM('karyawan', 'atasan') NOT NULL DEFAULT 'karyawan',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB";

    if (!$conn->query($createUsers)) {
        die("Error creating users table: " . $conn->error);
    }
    $roleColumn = $conn->query("SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = '$dbname'
        AND TABLE_NAME = 'users'
        AND COLUMN_NAME = 'role'");
    if ($roleColumn && $roleColumn->num_rows === 0) {
        if (!$conn->query("ALTER TABLE users
            ADD COLUMN role ENUM('karyawan', 'atasan') NOT NULL DEFAULT 'karyawan'
            AFTER password")) {
            die("Error adding role column: " . $conn->error);
        }
    }

    $createKaryawan = "CREATE TABLE IF NOT EXISTS karyawan (
        id_karyawan INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        id_user INT UNSIGNED NOT NULL,
        nama VARCHAR(100) NOT NULL,
        nip VARCHAR(30) NOT NULL UNIQUE,
        jabatan VARCHAR(100) NOT NULL,
        departemen VARCHAR(100) NOT NULL,
        no_hp VARCHAR(20) DEFAULT NULL,
        UNIQUE KEY uq_karyawan_id_user (id_user),
        CONSTRAINT fk_karyawan_users FOREIGN KEY (id_user) REFERENCES users(id)
            ON UPDATE CASCADE ON DELETE CASCADE
    ) ENGINE=InnoDB";

    $createPengajuan = "CREATE TABLE IF NOT EXISTS pengajuan (
        id_pengajuan INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        id_karyawan INT UNSIGNED NOT NULL,
        tujuan VARCHAR(150) NOT NULL,
        tanggal_berangkat DATE NOT NULL,
        tanggal_pulang DATE NOT NULL,
        keperluan TEXT NOT NULL,
        dokumen VARCHAR(255) DEFAULT NULL,
        status ENUM('Menunggu', 'Disetujui', 'Ditolak') NOT NULL DEFAULT 'Menunggu',
        alasan_penolakan TEXT DEFAULT NULL,
        tanggal_pengajuan DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_pengajuan_karyawan FOREIGN KEY (id_karyawan) REFERENCES karyawan(id_karyawan)
            ON UPDATE CASCADE ON DELETE CASCADE,
        CONSTRAINT chk_pengajuan_tanggal CHECK (tanggal_pulang >= tanggal_berangkat)
    ) ENGINE=InnoDB";

    $createPenugasan = "CREATE TABLE IF NOT EXISTS penugasan (
        id_penugasan INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        id_pengajuan INT UNSIGNED DEFAULT NULL,
        id_karyawan INT UNSIGNED NOT NULL,
        id_atasan INT UNSIGNED NOT NULL,
        jenis ENUM('Pengajuan', 'Langsung') NOT NULL,
        tujuan VARCHAR(150) NOT NULL,
        tanggal_berangkat DATE NOT NULL,
        tanggal_pulang DATE NOT NULL,
        keperluan TEXT NOT NULL,
        status ENUM('Aktif', 'Selesai') NOT NULL DEFAULT 'Aktif',
        CONSTRAINT fk_penugasan_pengajuan FOREIGN KEY (id_pengajuan) REFERENCES pengajuan(id_pengajuan)
            ON UPDATE CASCADE ON DELETE SET NULL,
        CONSTRAINT fk_penugasan_karyawan FOREIGN KEY (id_karyawan) REFERENCES karyawan(id_karyawan)
            ON UPDATE CASCADE ON DELETE CASCADE,
        CONSTRAINT fk_penugasan_atasan FOREIGN KEY (id_atasan) REFERENCES karyawan(id_karyawan)
            ON UPDATE CASCADE ON DELETE RESTRICT,
        CONSTRAINT chk_penugasan_tanggal CHECK (tanggal_pulang >= tanggal_berangkat)
    ) ENGINE=InnoDB";

    $createSuratTugas = "CREATE TABLE IF NOT EXISTS surat_tugas (
        id_surat INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        id_penugasan INT UNSIGNED NOT NULL,
        id_atasan INT UNSIGNED DEFAULT NULL,
        nomor_surat VARCHAR(50) NOT NULL UNIQUE,
        tanggal_terbit DATE NOT NULL,
        file_surat VARCHAR(255) DEFAULT NULL,
        UNIQUE KEY uq_surat_tugas_penugasan (id_penugasan),
        CONSTRAINT fk_surat_tugas_penugasan FOREIGN KEY (id_penugasan) REFERENCES penugasan(id_penugasan)
            ON UPDATE CASCADE ON DELETE CASCADE,
        CONSTRAINT fk_surat_tugas_atasan FOREIGN KEY (id_atasan) REFERENCES karyawan(id_karyawan)
            ON UPDATE CASCADE ON DELETE SET NULL
    ) ENGINE=InnoDB";

    foreach ([$createKaryawan, $createPengajuan, $createPenugasan, $createSuratTugas] as $createTable) {
        if (!$conn->query($createTable)) {
            die("Error creating application table: " . $conn->error);
        }
    }

    $letterColumn = $conn->query("SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = '$dbname'
        AND TABLE_NAME = 'surat_tugas'
        AND COLUMN_NAME = 'id_atasan'");
    if ($letterColumn && $letterColumn->num_rows === 0) {
        if (!$conn->query("ALTER TABLE surat_tugas
            ADD COLUMN id_atasan INT UNSIGNED DEFAULT NULL AFTER id_penugasan,
            ADD CONSTRAINT fk_surat_tugas_atasan
                FOREIGN KEY (id_atasan) REFERENCES karyawan(id_karyawan)
                ON UPDATE CASCADE ON DELETE SET NULL")) {
            die("Error adding surat_tugas supervisor column: " . $conn->error);
        }
    }
    $conn->query("UPDATE surat_tugas s
        JOIN penugasan t ON t.id_penugasan = s.id_penugasan
        SET s.id_atasan = t.id_atasan
        WHERE s.id_atasan IS NULL");
    
    return $conn;
}
?>
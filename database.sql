CREATE DATABASE IF NOT EXISTS `sistem_sdm_projek_PW`
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE `sistem_sdm_projek_PW`;

CREATE TABLE IF NOT EXISTS `users` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `username` VARCHAR(50) NOT NULL,
    `email` VARCHAR(150) NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM('karyawan', 'atasan') NOT NULL DEFAULT 'karyawan',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_users_username` (`username`),
    UNIQUE KEY `uq_users_email` (`email`)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `karyawan` (
    `id_karyawan` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_user` INT UNSIGNED NOT NULL,
    `nama` VARCHAR(100) NOT NULL,
    `nip` VARCHAR(30) NOT NULL,
    `jabatan` VARCHAR(100) NOT NULL,
    `departemen` VARCHAR(100) NOT NULL,
    `no_hp` VARCHAR(20) DEFAULT NULL,
    PRIMARY KEY (`id_karyawan`),
    UNIQUE KEY `uq_karyawan_nip` (`nip`),
    UNIQUE KEY `uq_karyawan_id_user` (`id_user`),
    CONSTRAINT `fk_karyawan_users`
        FOREIGN KEY (`id_user`) REFERENCES `users` (`id`)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pengajuan` (
    `id_pengajuan` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_karyawan` INT UNSIGNED NOT NULL,
    `tujuan` VARCHAR(150) NOT NULL,
    `tanggal_berangkat` DATE NOT NULL,
    `tanggal_pulang` DATE NOT NULL,
    `keperluan` TEXT NOT NULL,
    `dokumen` VARCHAR(255) DEFAULT NULL,
    `status` ENUM('Menunggu', 'Disetujui', 'Ditolak') NOT NULL DEFAULT 'Menunggu',
    `alasan_penolakan` TEXT DEFAULT NULL,
    `tanggal_pengajuan` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id_pengajuan`),
    CONSTRAINT `fk_pengajuan_karyawan`
        FOREIGN KEY (`id_karyawan`) REFERENCES `karyawan` (`id_karyawan`)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT `chk_pengajuan_tanggal`
        CHECK (`tanggal_pulang` >= `tanggal_berangkat`)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `penugasan` (
    `id_penugasan` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_pengajuan` INT UNSIGNED DEFAULT NULL,
    `id_karyawan` INT UNSIGNED NOT NULL,
    `id_atasan` INT UNSIGNED NOT NULL,
    `jenis` ENUM('Pengajuan', 'Langsung') NOT NULL,
    `tujuan` VARCHAR(150) NOT NULL,
    `tanggal_berangkat` DATE NOT NULL,
    `tanggal_pulang` DATE NOT NULL,
    `keperluan` TEXT NOT NULL,
    `status` ENUM('Aktif', 'Selesai') NOT NULL DEFAULT 'Aktif',
    PRIMARY KEY (`id_penugasan`),
    CONSTRAINT `fk_penugasan_pengajuan`
        FOREIGN KEY (`id_pengajuan`) REFERENCES `pengajuan` (`id_pengajuan`)
        ON UPDATE CASCADE
        ON DELETE SET NULL,
    CONSTRAINT `fk_penugasan_karyawan`
        FOREIGN KEY (`id_karyawan`) REFERENCES `karyawan` (`id_karyawan`)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT `fk_penugasan_atasan`
        FOREIGN KEY (`id_atasan`) REFERENCES `karyawan` (`id_karyawan`)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    CONSTRAINT `chk_penugasan_tanggal`
        CHECK (`tanggal_pulang` >= `tanggal_berangkat`)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `surat_tugas` (
    `id_surat` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_penugasan` INT UNSIGNED NOT NULL,
    `id_atasan` INT UNSIGNED DEFAULT NULL,
    `nomor_surat` VARCHAR(50) NOT NULL,
    `tanggal_terbit` DATE NOT NULL,
    `file_surat` VARCHAR(255) DEFAULT NULL,
    PRIMARY KEY (`id_surat`),
    UNIQUE KEY `uq_surat_tugas_nomor` (`nomor_surat`),
    UNIQUE KEY `uq_surat_tugas_penugasan` (`id_penugasan`),
    CONSTRAINT `fk_surat_tugas_penugasan`
        FOREIGN KEY (`id_penugasan`) REFERENCES `penugasan` (`id_penugasan`)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT `fk_surat_tugas_atasan`
        FOREIGN KEY (`id_atasan`) REFERENCES `karyawan` (`id_karyawan`)
        ON UPDATE CASCADE
        ON DELETE SET NULL
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

CREATE DATABASE IF NOT EXISTS `sistem_sdm_projek_PW`
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE `sistem_sdm_projek_PW`;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `notifikasi`;
DROP TABLE IF EXISTS `surat_tugas`;
DROP TABLE IF EXISTS `penugasan`;
DROP TABLE IF EXISTS `pengajuan`;
DROP TABLE IF EXISTS `karyawan`;
DROP TABLE IF EXISTS `users`;
SET FOREIGN_KEY_CHECKS = 1;

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
    `alamat` TEXT DEFAULT NULL,
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
    `biaya_estimasi` DECIMAL(15,2) NOT NULL DEFAULT 0,
    `tanggal_pengajuan` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `tanggal_keputusan` DATETIME DEFAULT NULL,
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
    `catatan` TEXT DEFAULT NULL,
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

CREATE TABLE IF NOT EXISTS `notifikasi` (
    `id_notifikasi` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_user` INT UNSIGNED NOT NULL,
    `judul` VARCHAR(150) NOT NULL,
    `pesan` TEXT NOT NULL,
    `tautan` VARCHAR(255) DEFAULT NULL,
    `sudah_dibaca` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id_notifikasi`),
    KEY `idx_notifikasi_user_dibaca` (`id_user`, `sudah_dibaca`),
    CONSTRAINT `fk_notifikasi_users`
        FOREIGN KEY (`id_user`) REFERENCES `users` (`id`)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

SET NAMES utf8mb4;

-- USERS (id 1-2 atasan, id 3-8 karyawan) -- password: password123
INSERT INTO `users` (`id`,`name`,`username`,`email`,`password`,`role`,`created_at`) VALUES
(1,'Budi Santoso','budi.santoso','budi.santoso@perusahaan.co.id','$2y$10$rrW4q0wwSfeivSQkP3m7te1yJAmHDZaJqiMlCriAXyhQoTQN4LtQi','atasan','2026-06-01 08:00:00'),
(2,'Sari Wulandari','sari.wulandari','sari.wulandari@perusahaan.co.id','$2y$10$rrW4q0wwSfeivSQkP3m7te1yJAmHDZaJqiMlCriAXyhQoTQN4LtQi','atasan','2026-06-01 08:05:00'),
(3,'Rina Puspita','rina.puspita','rina.puspita@perusahaan.co.id','$2y$10$rrW4q0wwSfeivSQkP3m7te1yJAmHDZaJqiMlCriAXyhQoTQN4LtQi','karyawan','2026-06-02 09:00:00'),
(4,'Dedi Kurniawan','dedi.kurniawan','dedi.kurniawan@perusahaan.co.id','$2y$10$rrW4q0wwSfeivSQkP3m7te1yJAmHDZaJqiMlCriAXyhQoTQN4LtQi','karyawan','2026-06-02 09:10:00'),
(5,'Maya Anggraini','maya.anggraini','maya.anggraini@perusahaan.co.id','$2y$10$rrW4q0wwSfeivSQkP3m7te1yJAmHDZaJqiMlCriAXyhQoTQN4LtQi','karyawan','2026-06-02 09:20:00'),
(6,'Andi Pratama','andi.pratama','andi.pratama@perusahaan.co.id','$2y$10$rrW4q0wwSfeivSQkP3m7te1yJAmHDZaJqiMlCriAXyhQoTQN4LtQi','karyawan','2026-06-03 09:00:00'),
(7,'Lia Hartono','lia.hartono','lia.hartono@perusahaan.co.id','$2y$10$rrW4q0wwSfeivSQkP3m7te1yJAmHDZaJqiMlCriAXyhQoTQN4LtQi','karyawan','2026-06-03 09:15:00'),
(8,'Rudi Hermawan','rudi.hermawan','rudi.hermawan@perusahaan.co.id','$2y$10$rrW4q0wwSfeivSQkP3m7te1yJAmHDZaJqiMlCriAXyhQoTQN4LtQi','karyawan','2026-06-03 09:30:00');

-- KARYAWAN (id_karyawan 1-8 mengikuti id user; atasan memakai NIP format AT-xxxxxx)
INSERT INTO `karyawan` (`id_karyawan`,`id_user`,`nama`,`nip`,`jabatan`,`departemen`,`no_hp`,`alamat`) VALUES
(1,1,'Budi Santoso','AT-000001','Atasan','Umum','081234567801','Jl. Merdeka No. 10, Bandung'),
(2,2,'Sari Wulandari','AT-000002','Atasan','Umum','081234567802','Jl. Dago Asri No. 25, Bandung'),
(3,3,'Rina Puspita','EMP-2024-001','Staf Marketing','Pemasaran','081234567803','Jl. Cihampelas No. 45, Bandung'),
(4,4,'Dedi Kurniawan','EMP-2024-002','Staf Keuangan','Keuangan','081234567804','Jl. Soekarno Hatta No. 112, Bandung'),
(5,5,'Maya Anggraini','EMP-2024-003','Analis SDM','SDM','081234567805','Jl. Asia Afrika No. 8, Bandung'),
(6,6,'Andi Pratama','EMP-2024-004','Programmer','Teknologi Informasi','081234567806','Jl. Buah Batu No. 77, Bandung'),
(7,7,'Lia Hartono','EMP-2024-005','Staf Operasional','Operasional','081234567807','Jl. Pasteur No. 31, Bandung'),
(8,8,'Rudi Hermawan','EMP-2024-006','Supervisor Logistik','Operasional','081234567808','Jl. Kopo No. 203, Bandung');

-- PENGAJUAN (campuran status Menunggu / Disetujui / Ditolak)
INSERT INTO `pengajuan` (`id_pengajuan`,`id_karyawan`,`tujuan`,`tanggal_berangkat`,`tanggal_pulang`,`keperluan`,`dokumen`,`status`,`alasan_penolakan`,`biaya_estimasi`,`tanggal_pengajuan`,`tanggal_keputusan`) VALUES
(1,3,'Bandung - Jakarta (Kantor Klien PT Maju Jaya)','2026-09-14','2026-09-16','Presentasi proposal kampanye pemasaran kepada klien baru.','dokumen_1795015775_e8b5d1ab1111.pdf','Disetujui',NULL,2500000,'2026-09-08 10:15:00','2026-09-10 14:00:00'),
(2,4,'Surabaya','2026-10-05','2026-10-08','Audit keuangan cabang Surabaya triwulan III.',NULL,'Disetujui',NULL,4200000,'2026-09-28 09:30:00','2026-09-30 11:20:00'),
(3,5,'Yogyakarta','2026-11-17','2026-11-21','Pelatihan manajemen talenta dan rekrutmen di kantor mitra.',NULL,'Disetujui',NULL,3100000,'2026-11-09 13:45:00','2026-11-11 10:05:00'),
(4,6,'Jakarta (Data Center)','2026-11-25','2026-11-27','Migrasi server aplikasi internal ke data center baru.',NULL,'Disetujui',NULL,1800000,'2026-11-12 08:50:00','2026-11-14 09:40:00'),
(5,7,'Medan','2026-12-02','2026-12-05','Monitoring operasional gudang regional Sumatera.',NULL,'Menunggu',NULL,5500000,'2026-11-17 15:10:00',NULL),
(6,8,'Semarang','2026-12-10','2026-12-12','Koordinasi pengiriman dengan mitra ekspedisi.',NULL,'Menunggu',NULL,2200000,'2026-11-18 11:00:00',NULL),
(7,4,'Denpasar','2026-11-03','2026-11-06','Menghadiri seminar perpajakan nasional.',NULL,'Ditolak','Anggaran pelatihan triwulan IV sudah terpakai. Silakan ajukan kembali pada periode berikutnya.',6800000,'2026-10-25 09:00:00','2026-10-27 16:30:00'),
(8,3,'Malang','2026-12-15','2026-12-17','Survei pasar dan kunjungan ke distributor wilayah Jawa Timur.','dokumen_1795026436_d941fb0b806d.pdf','Menunggu',NULL,2900000,'2026-11-18 16:20:00',NULL),
(9,5,'Makassar','2026-10-20','2026-10-23','Wawancara kandidat untuk posisi kepala cabang.',NULL,'Ditolak','Wawancara dapat dilakukan secara daring sehingga perjalanan tidak diperlukan.',3600000,'2026-10-13 10:40:00','2026-10-15 13:15:00'),
(10,6,'Palembang','2026-08-18','2026-08-20','Instalasi dan pelatihan sistem untuk kantor perwakilan.','dokumen_1795026471_26c5c246bfdf.pdf','Disetujui',NULL,2000000,'2026-08-10 09:05:00','2026-08-12 10:00:00');

-- PENUGASAN (5 dari pengajuan yang disetujui + 2 penugasan langsung oleh atasan)
INSERT INTO `penugasan` (`id_penugasan`,`id_pengajuan`,`id_karyawan`,`id_atasan`,`jenis`,`tujuan`,`tanggal_berangkat`,`tanggal_pulang`,`keperluan`,`status`,`catatan`) VALUES
(1,1,3,1,'Pengajuan','Bandung - Jakarta (Kantor Klien PT Maju Jaya)','2026-09-14','2026-09-16','Presentasi proposal kampanye pemasaran kepada klien baru.','Selesai','Klien menyetujui proposal tahap awal.'),
(2,2,4,1,'Pengajuan','Surabaya','2026-10-05','2026-10-08','Audit keuangan cabang Surabaya triwulan III.','Selesai','Laporan audit sudah diserahkan ke direksi.'),
(3,3,5,2,'Pengajuan','Yogyakarta','2026-11-17','2026-11-21','Pelatihan manajemen talenta dan rekrutmen di kantor mitra.','Aktif','Bawa materi pelatihan dan daftar peserta.'),
(4,4,6,1,'Pengajuan','Jakarta (Data Center)','2026-11-25','2026-11-27','Migrasi server aplikasi internal ke data center baru.','Aktif',NULL),
(5,10,6,2,'Pengajuan','Palembang','2026-08-18','2026-08-20','Instalasi dan pelatihan sistem untuk kantor perwakilan.','Selesai',NULL),
(6,NULL,8,1,'Langsung','Bogor','2026-11-20','2026-11-22','Pengecekan stok dan inspeksi gudang cabang Bogor.','Aktif','Penugasan mendadak dari atasan.'),
(7,NULL,7,2,'Langsung','Bandung - Cimahi','2026-10-12','2026-10-14','Supervisi pengiriman barang ke pelanggan prioritas.','Selesai',NULL);

-- SURAT TUGAS (penugasan 4 dan 6 sengaja belum punya surat, untuk demo fitur "Buat Surat")
INSERT INTO `surat_tugas` (`id_surat`,`id_penugasan`,`id_atasan`,`nomor_surat`,`tanggal_terbit`,`file_surat`) VALUES
(1,5,2,'018/ST/HRD/VIII/2026','2026-08-14',NULL),
(2,1,1,'021/ST/HRD/IX/2026','2026-09-11',NULL),
(3,2,1,'026/ST/HRD/IX/2026','2026-10-01',NULL),
(4,7,2,'028/ST/HRD/X/2026','2026-10-09',NULL),
(5,3,2,'031/ST/HRD/XI/2026','2026-11-12',NULL);

-- NOTIFIKASI
INSERT INTO `notifikasi` (`id_notifikasi`,`id_user`,`judul`,`pesan`,`tautan`,`sudah_dibaca`,`created_at`) VALUES
(1,1,'Pengajuan baru','Lia Hartono mengajukan perjalanan dinas ke Medan (2-5 Des 2026).','dashboard_atasan.php',0,'2026-11-17 15:10:00'),
(2,1,'Pengajuan baru','Rudi Hermawan mengajukan perjalanan dinas ke Semarang (10-12 Des 2026).','dashboard_atasan.php',0,'2026-11-18 11:00:00'),
(3,1,'Pengajuan baru','Rina Puspita mengajukan perjalanan dinas ke Malang (15-17 Des 2026).','dashboard_atasan.php',0,'2026-11-18 16:20:00'),
(4,2,'Pengajuan baru','Lia Hartono mengajukan perjalanan dinas ke Medan (2-5 Des 2026).','dashboard_atasan.php',1,'2026-11-17 15:10:00'),
(5,5,'Pengajuan disetujui','Pengajuan perjalanan dinas ke Yogyakarta telah disetujui.','riwayat_pengajuan.php',1,'2026-11-11 10:05:00'),
(6,6,'Pengajuan disetujui','Pengajuan perjalanan dinas ke Jakarta (Data Center) telah disetujui.','riwayat_pengajuan.php',0,'2026-11-14 09:40:00'),
(7,4,'Pengajuan ditolak','Pengajuan perjalanan dinas ke Denpasar ditolak. Lihat alasan penolakan di riwayat.','riwayat_pengajuan.php',1,'2026-10-27 16:30:00'),
(8,8,'Penugasan baru','Anda mendapat penugasan langsung ke Bogor (20-22 Nov 2026).','penugasan_karyawan.php',0,'2026-11-18 17:00:00'),
(9,5,'Surat tugas terbit','Surat tugas 031/ST/HRD/XI/2026 telah diterbitkan.','penugasan_karyawan.php',1,'2026-11-12 09:00:00'),
(10,3,'Pengajuan diterima','Pengajuan perjalanan dinas ke Malang sedang menunggu persetujuan.','riwayat_pengajuan.php',0,'2026-11-18 16:20:00');

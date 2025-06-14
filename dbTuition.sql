DROP DATABASE IF EXISTS db_tuition;
CREATE DATABASE         db_tuition;
USE                     db_tuition;

CREATE TABLE `jenjang` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `nama` varchar(255),
  `va_code` int
);

CREATE TABLE `tingkat` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `jenjang_id` int,
  `nama` varchar(255)
);

CREATE TABLE `kelas` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `tingkat_id` int,
  `nama` varchar(255)
);

CREATE TABLE `siswa` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `nama` varchar(255),
  `nis` varchar(255),
  `jenjang_id` int,
  `tingkat_id` int,
  `kelas_id` int,
  `va` varchar(255),
  `no_hp_ortu` varchar(255), 
  `spp` decimal(15, 2),
  `created_at` timestamp default now(),
  `updated_at` timestamp default now(),
  `deleted_at` timestamp default null
);

CREATE TABLE `spp_tarif` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `jenjang_id` int,
  `tingkat_id` int,
  `kelas_id` int DEFAULT null,
  `nominal` decimal(15,2),
  `tahun` YEAR,
  `created_at` timestamp default now()
);

CREATE TABLE `spp_biaya_tambahan` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `siswa_id` int,
  `kategori` enum('praktek','ekstra','daycare'),
  `nominal` decimal(15,2),
  `bulan` int,
  `tahun` int,
  `keterangan` varchar(255)
);

CREATE TABLE `spp_penerimaan` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `tanggal` date,
  `file_csv` varchar(255)
);

CREATE TABLE `spp_pembayaran` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `penerimaan_id` int,
  `siswa_id` int,
  `tanggal_pembayaran` date,
  `jumlah_bayar` decimal(15,2)
);

CREATE TABLE `spp_tagihan` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `siswa_id` int,
  `bulan` varchar(255),
  `tahun` int,
  `jatuh_tempo` date,
  `total_nominal` decimal(15,2),
  `denda` decimal(15,2),
  `status` enum('belum_lunas','lunas')
);

CREATE TABLE `spp_tagihan_detail` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `tagihan_id` int,
  `jenis` enum('spp','praktek','ekstra','daycare'),
  `nominal` decimal(15,2),
  `keterangan` varchar(255)
);

CREATE TABLE `spp_pembayaran_tagihan` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `pembayaran_id` int,
  `tagihan_id` int,
  `jumlah` decimal(15,2)
);

CREATE TABLE `spp_jurnal` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `tanggal` date,
  `jenis` enum('piutang','pelunasan'),
  `deskripsi` varchar(255)
);

CREATE TABLE `spp_jurnal_detail` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `jurnal_id` int,
  `akun` varchar(255),
  `debit` decimal(15,2),
  `kredit` decimal(15,2)
);

CREATE TABLE `spp_jurnal_tagihan` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `jurnal_id` int,
  `pembayaran_tagihan_id` int,
  `nominal_tagihan` decimal(15,2),
  `nominal_denda` decimal(15,2)
);

CREATE TABLE `users` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `username` varchar(255) UNIQUE,
  `password` varchar(255),
  `role` enum('admin','siswa'),
  `siswa_id` int DEFAULT NULL
);

ALTER TABLE `tingkat` ADD FOREIGN KEY (`jenjang_id`) REFERENCES `jenjang` (`id`);
ALTER TABLE `kelas` ADD FOREIGN KEY (`tingkat_id`) REFERENCES `tingkat` (`id`);
ALTER TABLE `siswa` ADD FOREIGN KEY (`jenjang_id`) REFERENCES `jenjang` (`id`);
ALTER TABLE `siswa` ADD FOREIGN KEY (`tingkat_id`) REFERENCES `tingkat` (`id`);
ALTER TABLE `siswa` ADD FOREIGN KEY (`kelas_id`) REFERENCES `kelas` (`id`);
ALTER TABLE `spp_tarif` ADD FOREIGN KEY (`jenjang_id`) REFERENCES `jenjang` (`id`);
ALTER TABLE `spp_tarif` ADD FOREIGN KEY (`tingkat_id`) REFERENCES `tingkat` (`id`);
ALTER TABLE `spp_tarif` ADD FOREIGN KEY (`kelas_id`) REFERENCES `kelas` (`id`);
ALTER TABLE `spp_biaya_tambahan` ADD FOREIGN KEY (`siswa_id`) REFERENCES `siswa` (`id`);
ALTER TABLE `spp_pembayaran` ADD FOREIGN KEY (`penerimaan_id`) REFERENCES `spp_penerimaan` (`id`);
ALTER TABLE `spp_pembayaran` ADD FOREIGN KEY (`siswa_id`) REFERENCES `siswa` (`id`);
ALTER TABLE `spp_tagihan` ADD FOREIGN KEY (`siswa_id`) REFERENCES `siswa` (`id`);
ALTER TABLE `spp_tagihan_detail` ADD FOREIGN KEY (`tagihan_id`) REFERENCES `spp_tagihan` (`id`);
ALTER TABLE `spp_pembayaran_tagihan` ADD FOREIGN KEY (`pembayaran_id`) REFERENCES `spp_pembayaran` (`id`);
ALTER TABLE `spp_pembayaran_tagihan` ADD FOREIGN KEY (`tagihan_id`) REFERENCES `spp_tagihan` (`id`);
ALTER TABLE `spp_jurnal_detail` ADD FOREIGN KEY (`jurnal_id`) REFERENCES `spp_jurnal` (`id`);
ALTER TABLE `spp_jurnal_tagihan` ADD FOREIGN KEY (`jurnal_id`) REFERENCES `spp_jurnal` (`id`);
ALTER TABLE `spp_jurnal_tagihan` ADD FOREIGN KEY (`pembayaran_tagihan_id`) REFERENCES `spp_pembayaran_tagihan` (`id`);
ALTER TABLE `users` ADD FOREIGN KEY (`siswa_id`) REFERENCES `siswa` (`id`);

INSERT INTO `jenjang` (`nama`, `va_code`) VALUES
('TK', 3),
('SD', 4),
('SMP', 5),
('SMA', 6),
('SMK TKJ', 7),
('SMK MM', 8);

INSERT INTO `tingkat` (`jenjang_id`, `nama`) VALUES
(1, 'PG'),(1, 'A'),(1, 'B'),
(2, '1'),(2, '2'),(2, '3'),(2, '4'),(2, '5'),(2, '6'),
(3, '7'),(3, '8'),(3, '9'),
(4, 'X'),(4, 'XI'),(4, 'XII'),
(5, 'X'),(5, 'XI'),(5, 'XII'),
(6, 'X'),(6, 'XI'),(6, 'XII');

INSERT INTO `kelas` (`tingkat_id`, `nama`) VALUES
(2, 'Regular'),(2, 'Excel'),
(3, 'Regular'),(3, 'Excel'),
(7, 'A'),(7, 'B'),
(8, 'A'),(8, 'B'),
(10, 'A'),(10, 'B'),
(12, 'A'),(12, 'B'),
(13, '1'),(13, '2'),
(14, '1'),(14, '2'),(14, 'Excel'),
(15, 'IPA'),(15, 'IPS'),(15, 'Excel'),
(19, 'A'),(19, 'B'),
(20, 'A'),(20, 'B');

INSERT INTO `spp_tarif` (`jenjang_id`, `tingkat_id`, `kelas_id`, `nominal`, `tahun`) VALUES
(1, 1, null, 650000.00, 2025),
(1, 2, 1, 650000.00, 2025),(1, 2, 2, 950000.00, 2025),
(1, 3, 3, 650000.00, 2025),(1, 3, 4, 950000.00, 2025),
(2, 4, null, 650000.00, 2025),(2, 5, null, 650000.00, 2025),(2, 6, null, 650000.00, 2025),
(2, 7, 5, 485000.00, 2025),(2, 7, 6, 485000.00, 2025),
(2, 8, 7, 385000.00, 2025),(2, 8, 8, 385000.00, 2025),
(2, 9, null, 562000.00, 2025),
(3, 10, 9, 575000.00, 2025),(3, 10, 10, 575000.00, 2025),
(3, 11, null, 540000.00, 2025),
(3, 12, 11, 835000.00, 2025),(3, 12, 12, 835000.00, 2025),
(4, 13, 13, 700000.00, 2025),(4, 13, 14, 700000.00, 2025),
(4, 14, 15, 665000.00, 2025),(4, 14, 16, 665000.00, 2025),(4, 14, 17, 950000.00, 2025),
(4, 15, 18, 776000.00, 2025),(4, 15, 19, 866000.00, 2025),(4, 15, 20, 1226000.00, 2025),
(5, 16, null, 610000.00, 2025),(5, 17, null, 625000.00, 2025),(5, 18, null, 820000.00, 2025),
(6, 19, 21, 520000.00, 2025),(6, 19, 22, 520000.00, 2025),
(6, 20, 23, 520000.00, 2025),(6, 20, 24, 520000.00, 2025),
(6, 19, null, 694000.00, 2025);

INSERT INTO siswa(nama, nis, jenjang_id, tingkat_id, kelas_id, va, no_hp_ortu, spp) VALUES
('Angel Ravelynta', '5048', 4, 15, 18, '9881105624255048', '081329171920', 776000.00);

INSERT INTO users(username, password, role, siswa_id) VALUES
('admin', '25d55ad283aa400af464c76d713c07ad', 'admin', null),
('subadmin', '25d55ad283aa400af464c76d713c07ad', 'admin', null),
('9881105624255048', '$2y$10$.wAQesL2mOW2qWzWrgTaHOv6gwiIbidN8PUcSRCHjVn2aosZuJNhy', 'siswa', 1);
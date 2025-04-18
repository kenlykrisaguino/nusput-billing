DROP DATABASE IF EXISTS db_tagihan;
CREATE DATABASE         db_tagihan;
USE                     db_tagihan;

CREATE TABLE `users` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `nis` varchar(4) NOT NULL,
  `name` varchar(255) NOT NULL,
  `dob` date DEFAULT NULL,
  `address` text DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `parent_phone` varchar(20) NOT NULL,
  `role` char(2) NOT NULL, -- SA, AD, ST
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` TIMESTAMP DEFAULT NULL
);

CREATE TABLE `levels` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `name` varchar(10) NOT NULL,
  `va_code` char(1) NOT NULL
);

CREATE TABLE `grades` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `level_id` int NOT NULL,
  `name` varchar(10) NOT NULL,
  `base_monthly_fee` decimal(14,2) DEFAULT 0,
  `base_late_fee` decimal(14,2) DEFAULT 0
);

CREATE TABLE `sections` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `grade_id` int,
  `name` varchar(10) NOT NULL,
  `base_monthly_fee` decimal(14,2) DEFAULT 0,
  `base_late_fee` decimal(14,2) DEFAULT 0
);

CREATE TABLE `user_class` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `user_id` int,
  `level_id` int DEFAULT NULL,
  `grade_id` int DEFAULT NULL,
  `section_id` int DEFAULT NULL,
  `monthly_fee` decimal(14,2) DEFAULT 0,
  `late_fee` decimal(14,2) DEFAULT 0,
  `virtual_account` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `date_joined` datetime DEFAULT CURRENT_TIMESTAMP,
  `date_left` datetime DEFAULT NULL
);

CREATE TABLE `fee_categories` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `name` varchar(255) NOT NULL
);

CREATE TABLE `user_additional_fee` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `user_id` int,
  `fee_id` int,
  `amount` decimal(14,2) DEFAULT 0
);

CREATE TABLE `bills` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `user_id` int,
  `virtual_account` varchar(255) NOT NULL,
  `trx_id` varchar(255) NOT NULL,
  `trx_amount` decimal(14,2) DEFAULT 0,
  `trx_detail` json NOT NULL,
  `trx_status` varchar(255) NOT NULL,
  `late_fee` decimal(14,2) DEFAULT 0,
  `payment_due` datetime NOT NULL
);

CREATE TABLE `payments` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `user_id` int,
  `trx_amount` decimal(14,2) DEFAULT 0,
  `trx_timestamp` datetime DEFAULT CURRENT_TIMESTAMP,
  `details` json NOT NULL
);

CREATE TABLE `logs` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `log_name` varchar(255) NOT NULL,
  `description` varchar(255) NOT NULL,
  `causer_id` int,
  `properties` json NOT NULL DEFAULT (JSON_OBJECT()),
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
);

ALTER TABLE `grades` ADD FOREIGN KEY (`level_id`) REFERENCES `levels` (`id`);

ALTER TABLE `sections` ADD FOREIGN KEY (`grade_id`) REFERENCES `grades` (`id`);

ALTER TABLE `user_class` ADD FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

ALTER TABLE `user_class` ADD FOREIGN KEY (`level_id`) REFERENCES `levels` (`id`);

ALTER TABLE `user_class` ADD FOREIGN KEY (`grade_id`) REFERENCES `grades` (`id`);

ALTER TABLE `user_class` ADD FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`);

ALTER TABLE `user_additional_fee` ADD FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

ALTER TABLE `user_additional_fee` ADD FOREIGN KEY (`fee_id`) REFERENCES `fee_categories` (`id`);

ALTER TABLE `bills` ADD FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

ALTER TABLE `payments` ADD FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

ALTER TABLE `logs` ADD FOREIGN KEY (`causer_id`) REFERENCES `users` (`id`);

INSERT INTO `fee_categories` (`name`) VALUES
('Uang Praktik'),
('Uang Ekstra'),
('Daycare');

INSERT INTO `levels` (`name`, `va_code`) VALUES
('TK', 3),
('SD', 4),
('SMP', 5),
('SMA', 6),
('SMK TKJ', 7),
('SMK MM', 8);

INSERT INTO `grades` (`level_id`, `name`, `base_monthly_fee`, `base_late_fee`) VALUES
(1, 'PG', 650000, 10000),
(1, 'A', 0, 10000),
(1, 'B', 0, 10000),
(2, '1', 650000, 10000),
(2, '2', 650000, 10000),
(2, '3', 655000, 10000),
(2, '4', 0, 10000),
(2, '5', 0, 10000),
(2, '6', 562000, 10000),
(3, '7', 0, 10000),
(3, '8', 540000, 10000),
(3, '9', 0, 10000),
(4, 'X', 0, 10000),
(4, 'XI', 0, 10000),
(4, 'XII', 0, 10000),
(5, 'X', 610000, 10000),
(5, 'XI', 625000, 10000),
(5, 'XII', 820000, 10000),
(6, 'X', 0, 10000),
(6, 'XI', 0, 10000),
(6, 'XII', 694000, 10000);

INSERT INTO `sections` (`grade_id`, `name`, `base_monthly_fee`, `base_late_fee`) VALUES
(2, 'Regular', 650000, 10000),
(2, 'Excel', 950000, 10000),
(3, 'Regular', 650000, 10000),
(3, 'Excel', 950000, 10000),
(7, 'A', 485000, 10000),
(7, 'B', 485000, 10000),
(8, 'A', 385000, 10000),
(8, 'B', 385000, 10000),
(10, 'A', 575000, 10000),
(10, 'B', 575000, 10000),
(12, 'A', 835000, 10000),
(12, 'B', 835000, 10000),
(13, '1', 700000, 10000),
(13, '2', 700000, 10000),
(14, '1', 665000, 10000),
(14, '2', 665000, 10000),
(14, 'Excel', 950000, 10000),
(15, 'IPA', 776000, 10000),
(15, 'IPS', 866000, 10000),
(15, 'Excel', 1226000, 10000),
(19, 'A', 520000, 10000),
(19, 'B', 520000, 10000),
(20, 'A', 520000, 10000),
(20, 'B', 520000, 10000);

INSERT INTO `users` (`NIS`, `name`, `parent_phone`, `role`) VALUES
('0000', 'ADMIN', '08129171920', 'SA'),
('0001', 'SUBADMIN', '08129171920', 'TE'),
('5048', 'Angel Ravelynta', '081329171920', 'ST'),
('5049', 'Kenly Krisaguino', '081329171921', 'ST');

INSERT INTO `user_class` (
  `user_id`, `level_id`, `grade_id`, 
  `section_id`, `monthly_fee`, `late_fee`,
  `virtual_account`, `password`, `date_joined`
) VALUES
(
  '1', NULL, NULL, 
  NULL, 0, 0,
  'admin', '25d55ad283aa400af464c76d713c07ad', NOW()
),
(
  '2', NULL, NULL, 
  NULL, 0, 0,
  'subadmin', '25d55ad283aa400af464c76d713c07ad', NOW()
),
(
  '3', '4', '15', 
  '18', '665000', '10000',
  '9881105624255048', '9689b341aa161423602a005b0e1b865c', NOW()
),
(
  '4', '3', '12', 
  '12', '835000', '10000',
  '9881105524255049', 'b0ca3cfbe7000b5e7161190a9a4c68f4', NOW()
);
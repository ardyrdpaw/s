-- 001_create_base.sql
-- Initial schema and seed data for sapps (idempotent where practical)
-- This migration should be safe to run on an existing database (uses IF NOT EXISTS and conditional inserts)

-- Users table
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `username` VARCHAR(64) NOT NULL,
  `role` VARCHAR(32) DEFAULT 'user',
  `password` VARCHAR(255) DEFAULT NULL,
  `keterangan` TEXT DEFAULT NULL,
  `preferences` TEXT DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Ensure unique index for username (safe to add if duplicates handled elsewhere)
-- NOTE: If your DB has duplicate usernames, this will fail; run the `migrate_add_unique_nip.php` first in that case.
ALTER TABLE `users` ADD UNIQUE IF NOT EXISTS `uniq_users_username` (`username`);

-- Seed users only if table empty
INSERT INTO `users` (`name`,`username`,`role`) 
SELECT 'Alice','alice','admin' FROM DUAL WHERE (SELECT COUNT(*) FROM `users`) = 0 LIMIT 1;
INSERT INTO `users` (`name`,`username`,`role`) 
SELECT 'Bob','bob','user' FROM DUAL WHERE (SELECT COUNT(*) FROM `users`) = 0 LIMIT 1;
INSERT INTO `users` (`name`,`username`,`role`) 
SELECT 'Charlie','charlie','user' FROM DUAL WHERE (SELECT COUNT(*) FROM `users`) = 0 LIMIT 1;

-- cat_items
CREATE TABLE IF NOT EXISTS `cat_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `status` VARCHAR(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `cat_items` (`name`,`status`) SELECT 'Computer A','Active' FROM DUAL WHERE (SELECT COUNT(*) FROM `cat_items`) = 0 LIMIT 1;
INSERT INTO `cat_items` (`name`,`status`) SELECT 'Computer B','Inactive' FROM DUAL WHERE (SELECT COUNT(*) FROM `cat_items`) = 0 LIMIT 1;
INSERT INTO `cat_items` (`name`,`status`) SELECT 'Computer C','Active' FROM DUAL WHERE (SELECT COUNT(*) FROM `cat_items`) = 0 LIMIT 1;

-- signage_items
CREATE TABLE IF NOT EXISTS `signage_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(200) NOT NULL,
  `content` TEXT,
  `type` VARCHAR(20) NOT NULL,
  `category` VARCHAR(64) DEFAULT '',
  `autoplay` TINYINT(1) DEFAULT 0,
  `loop` TINYINT(1) DEFAULT 0,
  `muted` TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `signage_items` (`name`,`content`,`type`,`category`,`autoplay`,`loop`,`muted`)
SELECT 'Welcome 1','Welcome to BKPSDM','Text','Text',0,0,0 FROM DUAL WHERE (SELECT COUNT(*) FROM `signage_items`) = 0 LIMIT 1;
INSERT INTO `signage_items` (`name`,`content`,`type`,`category`,`autoplay`,`loop`,`muted`)
SELECT 'Welcome 2','Thank You for Visiting','Text','Text',0,0,0 FROM DUAL WHERE (SELECT COUNT(*) FROM `signage_items`) = 0 LIMIT 1;
INSERT INTO `signage_items` (`name`,`content`,`type`,`category`,`autoplay`,`loop`,`muted`)
SELECT 'Video 1','assets/uploads/video1.mp4','Video','Video',1,1,1 FROM DUAL WHERE (SELECT COUNT(*) FROM `signage_items`) = 0 LIMIT 1;

-- inf_ti_items
CREATE TABLE IF NOT EXISTS `inf_ti_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `sort_order` INT DEFAULT 0,
  `category` VARCHAR(32) NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `detail` TEXT,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `inf_ti_items` (`sort_order`,`category`,`name`,`detail`)
SELECT 1,'komputer','PC Ruang Admin','i5, 8GB, 256GB SSD' FROM DUAL WHERE (SELECT COUNT(*) FROM `inf_ti_items`) = 0 LIMIT 1;
INSERT INTO `inf_ti_items` (`sort_order`,`category`,`name`,`detail`)
SELECT 1,'printer','HP LaserJet','Pro M404' FROM DUAL WHERE (SELECT COUNT(*) FROM `inf_ti_items`) = 0 LIMIT 1;
INSERT INTO `inf_ti_items` (`sort_order`,`category`,`name`,`detail`)
SELECT 1,'jaringan','Switch Lantai 1','24 port Gigabit' FROM DUAL WHERE (SELECT COUNT(*) FROM `inf_ti_items`) = 0 LIMIT 1;

-- menus
CREATE TABLE IF NOT EXISTS `menus` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `menu_key` VARCHAR(100) NOT NULL UNIQUE,
  `label` VARCHAR(200) NOT NULL,
  `sort_order` INT DEFAULT 0,
  `protected` TINYINT(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `menus` (`menu_key`,`label`,`sort_order`,`protected`)
SELECT 'dashboard','Home',1,0 FROM DUAL WHERE (SELECT COUNT(*) FROM `menus`) = 0 LIMIT 1;
INSERT INTO `menus` (`menu_key`,`label`,`sort_order`,`protected`)
SELECT 'cat','CAT',2,0 FROM DUAL WHERE (SELECT COUNT(*) FROM `menus`) = 0 LIMIT 1;
INSERT INTO `menus` (`menu_key`,`label`,`sort_order`,`protected`)
SELECT 'signage','Signage',3,0 FROM DUAL WHERE (SELECT COUNT(*) FROM `menus`) = 0 LIMIT 1;
INSERT INTO `menus` (`menu_key`,`label`,`sort_order`,`protected`)
SELECT 'user','Users',4,1 FROM DUAL WHERE (SELECT COUNT(*) FROM `menus`) = 0 LIMIT 1;

-- user_access
CREATE TABLE IF NOT EXISTS `user_access` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `menu_key` VARCHAR(100) NOT NULL,
  `full` TINYINT(1) DEFAULT 0,
  `can_create` TINYINT(1) DEFAULT 0,
  `can_read` TINYINT(1) DEFAULT 0,
  `can_update` TINYINT(1) DEFAULT 0,
  `can_delete` TINYINT(1) DEFAULT 0,
  `visible` TINYINT(1) DEFAULT 0,
  UNIQUE KEY `ux_user_menu` (`user_id`,`menu_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Give alice full access to seeded menus when both users and menus exist and user_access empty
INSERT INTO `user_access` (`user_id`,`menu_key`,`full`,`can_create`,`can_read`,`can_update`,`can_delete`,`visible`)
SELECT u.id, m.menu_key, 1,1,1,1,1,1 FROM `users` u CROSS JOIN `menus` m
WHERE u.username='alice' AND (SELECT COUNT(*) FROM `user_access`) = 0;

-- Done

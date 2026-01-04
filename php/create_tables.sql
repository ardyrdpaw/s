-- Create tables for Support Apps BKPSDM
CREATE DATABASE IF NOT EXISTS support_apps_bkpsdm;
USE support_apps_bkpsdm;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    username VARCHAR(64) NOT NULL,
    role VARCHAR(32) DEFAULT 'user',
    password VARCHAR(255) DEFAULT NULL
);
INSERT INTO users (name, username) VALUES
('Alice', 'alice'),
('Bob', 'bob'),
('Charlie', 'charlie');

CREATE TABLE IF NOT EXISTS cat_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    status VARCHAR(20) NOT NULL
);
INSERT INTO cat_items (name, status) VALUES
('Computer A', 'Active'),
('Computer B', 'Inactive'),
('Computer C', 'Active');

CREATE TABLE IF NOT EXISTS signage_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item VARCHAR(100) NOT NULL,
    type VARCHAR(20) NOT NULL
);
INSERT INTO signage_items (item, type) VALUES
('Welcome 1', 'Text'),
('Welcome 2', 'Text'),
('Video 1', 'Video'),
('Video 2', 'Video'),
('Galeri 1', 'Images'),
('Galeri 2', 'Images'),
('Tabel Agenda', 'Table'),
('Tabel Kegiatan', 'Table'),
('Footer', 'Text');

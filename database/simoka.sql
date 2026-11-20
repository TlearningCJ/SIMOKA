-- =========================================================
-- SIMOKA - Sistem Monitoring Ketersediaan Kelas
-- Database MySQL / MariaDB (sesuai ERD Bab V)
-- =========================================================
DROP DATABASE IF EXISTS simoka;
CREATE DATABASE simoka CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE simoka;

CREATE TABLE pengguna (
  id_pengguna INT AUTO_INCREMENT PRIMARY KEY,
  nama        VARCHAR(100) NOT NULL,
  email       VARCHAR(100) NOT NULL UNIQUE,
  password    VARCHAR(255) NOT NULL,
  peran       ENUM('mahasiswa','pj_kelas','admin') NOT NULL DEFAULT 'mahasiswa',
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE ruangan (
  id_ruangan   INT AUTO_INCREMENT PRIMARY KEY,
  kode_ruangan VARCHAR(20)  NOT NULL UNIQUE,
  nama_ruangan VARCHAR(100) NOT NULL,
  gedung       VARCHAR(50)  NOT NULL,
  lantai       INT NOT NULL,
  kapasitas    INT NOT NULL,
  status       ENUM('tersedia','digunakan') NOT NULL DEFAULT 'tersedia',
  updated_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE fasilitas (
  id_fasilitas   INT AUTO_INCREMENT PRIMARY KEY,
  nama_fasilitas VARCHAR(50) NOT NULL UNIQUE,
  keterangan     VARCHAR(255) NULL
) ENGINE=InnoDB;

CREATE TABLE ruangan_fasilitas (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  id_ruangan   INT NOT NULL,
  id_fasilitas INT NOT NULL,
  jumlah       INT NOT NULL DEFAULT 1,
  kondisi      ENUM('baik','rusak') NOT NULL DEFAULT 'baik',
  UNIQUE KEY uq_rf (id_ruangan, id_fasilitas),
  FOREIGN KEY (id_ruangan)   REFERENCES ruangan(id_ruangan)     ON DELETE CASCADE,
  FOREIGN KEY (id_fasilitas) REFERENCES fasilitas(id_fasilitas) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE jadwal (
  id_jadwal   INT AUTO_INCREMENT PRIMARY KEY,
  id_ruangan  INT NOT NULL,
  id_pj       INT NULL,
  hari        ENUM('Senin','Selasa','Rabu','Kamis','Jumat','Sabtu') NOT NULL,
  jam_mulai   TIME NOT NULL,
  jam_selesai TIME NOT NULL,
  kegiatan    VARCHAR(100) NOT NULL,
  FOREIGN KEY (id_ruangan) REFERENCES ruangan(id_ruangan)   ON DELETE CASCADE,
  FOREIGN KEY (id_pj)      REFERENCES pengguna(id_pengguna) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE laporan_status (
  id_laporan    INT AUTO_INCREMENT PRIMARY KEY,
  id_ruangan    INT NOT NULL,
  id_pj         INT NOT NULL,
  status        ENUM('digunakan','tidak_digunakan') NOT NULL,
  keterangan    VARCHAR(255) NULL,
  waktu_laporan DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (id_ruangan) REFERENCES ruangan(id_ruangan)   ON DELETE CASCADE,
  FOREIGN KEY (id_pj)      REFERENCES pengguna(id_pengguna) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------- Data contoh ----------------
-- Akun uji: admin@simoka.test / admin123, PJ: *@simoka.test / pj12345, mahasiswa / mhs12345
INSERT INTO pengguna (nama,email,password,peran) VALUES
('Admin Akademik','admin@simoka.test','$2y$10$LZ3l3/jFWZ1kRiMkORQzWOq1KLHyDUTHhIzdcNbgo.eQcoOg3rwpq','admin'),
('Rani (PJ SI 2A)','rani@simoka.test','$2y$10$AZBkYisf5/JFDK9BcmsIQ.FbWG2o0HDGKKD0GovaMZ1uUpdYwH.VS','pj_kelas'),
('Dimas (PJ SI 2B)','dimas@simoka.test','$2y$10$AZBkYisf5/JFDK9BcmsIQ.FbWG2o0HDGKKD0GovaMZ1uUpdYwH.VS','pj_kelas'),
('Fadil (PJ HIMA)','fadil@simoka.test','$2y$10$AZBkYisf5/JFDK9BcmsIQ.FbWG2o0HDGKKD0GovaMZ1uUpdYwH.VS','pj_kelas'),
('Talitha','talitha@simoka.test','$2y$10$RC/hKXw7RDtmL/IKnc/0ju5xIt2anoPk3m8I40spNY.qGerFc0/cO','mahasiswa');

INSERT INTO ruangan (kode_ruangan,nama_ruangan,gedung,lantai,kapasitas) VALUES
('FIK 2.01','Ruang Kelas 2.01','Gedung FIK',2,30),
('FIK 2.02','Ruang Kelas 2.02','Gedung FIK',2,35),
('FIK 3.02','Ruang Kelas 3.02','Gedung FIK',3,40),
('FIK 3.04','Ruang Kelas 3.04','Gedung FIK',3,35),
('LAB 1','Laboratorium Komputer 1','Gedung FIK',1,30),
('AULA','Aula Fakultas','Gedung FIK',4,120);

INSERT INTO fasilitas (nama_fasilitas,keterangan) VALUES
('Proyektor','LCD proyektor + kabel HDMI'),('AC','Pendingin ruangan'),('Stopkontak','Titik colokan listrik'),
('Papan tulis','Whiteboard + spidol'),('Komputer','PC untuk praktikum'),('Sound system','Mic dan speaker');

INSERT INTO ruangan_fasilitas (id_ruangan,id_fasilitas,jumlah,kondisi) VALUES
(1,2,1,'baik'),(1,3,4,'baik'),(1,4,1,'baik'),
(2,1,1,'rusak'),(2,2,2,'baik'),(2,3,6,'baik'),(2,4,1,'baik'),
(3,1,1,'baik'),(3,2,2,'baik'),(3,3,6,'baik'),(3,4,1,'baik'),
(4,1,1,'baik'),(4,2,2,'baik'),(4,3,4,'baik'),
(5,1,1,'baik'),(5,2,2,'baik'),(5,3,30,'baik'),(5,5,30,'baik'),
(6,1,2,'baik'),(6,2,4,'baik'),(6,6,1,'baik'),(6,3,10,'baik');

INSERT INTO jadwal (id_ruangan,id_pj,hari,jam_mulai,jam_selesai,kegiatan) VALUES
(3,2,'Senin','07:30','09:10','Basis Data - SI 2A'),
(3,3,'Senin','13:00','14:40','APSI - SI 2B'),
(4,2,'Senin','09:20','11:00','Basis Data - SI 2A'),
(1,3,'Selasa','07:30','10:00','Pemrograman Web - SI 2B'),
(2,2,'Selasa','10:00','12:30','Statistika - SI 2A'),
(5,3,'Rabu','08:00','10:30','Praktikum Basis Data - SI 2B'),
(4,2,'Rabu','13:00','15:30','Jaringan Komputer - SI 2A'),
(3,2,'Kamis','09:20','11:00','APSI - SI 2A'),
(6,4,'Kamis','15:30','17:30','Rapat HIMA'),
(3,2,'Jumat','07:30','09:10','Basis Data - SI 2A'),
(3,3,'Jumat','13:00','14:40','APSI - SI 2B'),
(4,2,'Jumat','09:20','11:00','Basis Data - SI 2A'),
(3,4,'Jumat','15:30','17:10','Rapat HIMA'),
(2,3,'Sabtu','08:00','10:00','Kelas Pengganti - SI 2B');

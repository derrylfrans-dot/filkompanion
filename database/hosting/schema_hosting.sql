-- Versi hosting
-- Struktur database FILKOMpanion


SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS volunteer_hours;
DROP TABLE IF EXISTS jam_pengabdian;
DROP TABLE IF EXISTS issue;
DROP TABLE IF EXISTS krs_data;
DROP TABLE IF EXISTS ticket;
DROP TABLE IF EXISTS volunteer;
DROP TABLE IF EXISTS user;
SET FOREIGN_KEY_CHECKS = 1;

-- Tabel user
CREATE TABLE user (
  userId                VARCHAR(20)  NOT NULL,
  nama                  VARCHAR(100) NOT NULL,
  emailUB               VARCHAR(100) NOT NULL,
  rolePengguna          VARCHAR(30)  NOT NULL,
  kategoriDisabilitas   VARCHAR(30)  NULL,
  kebutuhanPendampingan VARCHAR(255) NULL,
  catatanKebutuhan      VARCHAR(255) NULL,
  updatedAt             DATETIME     NULL,
  CONSTRAINT pk_user PRIMARY KEY (userId),
  CONSTRAINT uq_user_email UNIQUE (emailUB),
  CONSTRAINT ck_user_role CHECK (rolePengguna IN
    ('Mahasiswa Disabilitas', 'Relawan Pendamping', 'Admin FILKOM')),
  CONSTRAINT ck_user_kategori CHECK (kategoriDisabilitas IS NULL
    OR kategoriDisabilitas IN ('Tunarungu', 'Tunadaksa'))
) ENGINE = InnoDB;

-- Tabel volunteer
CREATE TABLE volunteer (
  idRelawan          VARCHAR(20)  NOT NULL,
  userId             VARCHAR(20)  NOT NULL,
  dataRelawan        TEXT         NULL,
  keahlian           VARCHAR(100) NOT NULL,
  ketersediaan       TEXT         NOT NULL,
  statusPendaftaran  VARCHAR(30)  NOT NULL DEFAULT 'Menunggu Validasi',
  statusRelawan      VARCHAR(30)  NOT NULL DEFAULT 'Nonaktif',
  dokumenKeahlian    VARCHAR(255) NULL,
  dataPerforma       TEXT         NULL,
  alasanPenolakan    VARCHAR(255) NULL,
  tanggalDaftar      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  tanggalValidasi    DATETIME     NULL,
  updatedAt          DATETIME     NULL,
  CONSTRAINT pk_volunteer PRIMARY KEY (idRelawan),
  CONSTRAINT uq_volunteer_user UNIQUE (userId),
  CONSTRAINT fk_volunteer_user FOREIGN KEY (userId)
    REFERENCES user (userId) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT ck_volunteer_pendaftaran CHECK (statusPendaftaran IN
    ('Menunggu Validasi', 'Aktif', 'Ditolak')),
  CONSTRAINT ck_volunteer_relawan CHECK (statusRelawan IN ('Aktif', 'Nonaktif'))
) ENGINE = InnoDB;

-- Tabel ticket
CREATE TABLE ticket (
  idTiket             VARCHAR(20)  NOT NULL,
  idMahasiswa         VARCHAR(20)  NOT NULL,
  idRelawan           VARCHAR(20)  NULL,
  idSeri              VARCHAR(20)  NULL,
  jenisLayanan        VARCHAR(100) NOT NULL,
  lokasi              VARCHAR(100) NOT NULL,
  jadwalKelas         DATETIME     NOT NULL,
  waktuMulai          DATETIME     NOT NULL,
  waktuSelesai        DATETIME     NOT NULL,
  status              VARCHAR(30)  NOT NULL DEFAULT 'Menunggu Verifikasi',
  buktiFoto           VARCHAR(225) NULL,
  konfirmasiMahasiswa BOOLEAN      NOT NULL DEFAULT FALSE,
  periode             VARCHAR(30)  NOT NULL,
  catatanKebutuhan    VARCHAR(255) NULL,
  catatanTiket        TEXT         NULL,
  dibuatOleh          VARCHAR(20)  NOT NULL DEFAULT 'Mahasiswa',
  riwayatStatus       TEXT         NULL,
  createdAt           DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updatedAt           DATETIME     NULL,
  CONSTRAINT pk_ticket PRIMARY KEY (idTiket),
  CONSTRAINT fk_ticket_mahasiswa FOREIGN KEY (idMahasiswa)
    REFERENCES user (userId) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_ticket_relawan FOREIGN KEY (idRelawan)
    REFERENCES volunteer (idRelawan) ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT ck_ticket_status CHECK (status IN
    ('Menunggu Verifikasi', 'Tervalidasi', 'Ditolak', 'Dibatalkan',
     'Menunggu Respons', 'Teralokasi', 'Dilaksanakan', 'Selesai', 'Bermasalah')),
  CONSTRAINT ck_ticket_waktu CHECK (waktuSelesai > waktuMulai),
  INDEX idx_ticket_status (status),
  INDEX idx_ticket_relawan (idRelawan),
  INDEX idx_ticket_periode (periode)
) ENGINE = InnoDB;

-- Tabel krs_data
CREATE TABLE krs_data (
  idMahasiswa  VARCHAR(20) NOT NULL,
  jadwalKRS    TEXT        NOT NULL,
  CONSTRAINT pk_krs_data PRIMARY KEY (idMahasiswa),
  CONSTRAINT fk_krs_mahasiswa FOREIGN KEY (idMahasiswa)
    REFERENCES user (userId) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE = InnoDB;

-- Tabel issue
CREATE TABLE issue (
  idLaporan           VARCHAR(20)  NOT NULL,
  idTiket             VARCHAR(20)  NOT NULL,
  jenisKendala        VARCHAR(50)  NOT NULL,
  deskripsi           TEXT         NOT NULL,
  lampiran            VARCHAR(255) NULL,
  statusTindakLanjut  VARCHAR(30)  NOT NULL DEFAULT 'Belum Ditindaklanjuti',
  keputusan           VARCHAR(100) NULL,
  catatanAdmin        VARCHAR(255) NULL,
  tanggalLapor        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  tanggalTindakLanjut DATETIME     NULL,
  CONSTRAINT pk_issue PRIMARY KEY (idLaporan),
  CONSTRAINT fk_issue_ticket FOREIGN KEY (idTiket)
    REFERENCES ticket (idTiket) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT ck_issue_status CHECK (statusTindakLanjut IN
    ('Belum Ditindaklanjuti', 'Ditindaklanjuti')),
  INDEX idx_issue_ticket (idTiket)
) ENGINE = InnoDB;

-- Tabel jam_pengabdian
CREATE TABLE jam_pengabdian (
  idJam           VARCHAR(20)  NOT NULL,
  idTiket         VARCHAR(20)  NOT NULL,
  idRelawan       VARCHAR(20)  NOT NULL,
  durasiTercatat  DECIMAL(5,2) NOT NULL,
  statusJam       VARCHAR(30)  NOT NULL DEFAULT 'Menunggu Verifikasi',
  dicatatPada     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT pk_jam_pengabdian PRIMARY KEY (idJam),
  CONSTRAINT uq_jam_ticket UNIQUE (idTiket),
  CONSTRAINT fk_jam_ticket FOREIGN KEY (idTiket)
    REFERENCES ticket (idTiket) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_jam_relawan FOREIGN KEY (idRelawan)
    REFERENCES volunteer (idRelawan) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT ck_jam_durasi CHECK (durasiTercatat >= 0),
  CONSTRAINT ck_jam_status CHECK (statusJam IN
    ('Menunggu Verifikasi', 'Terverifikasi', 'Perlu Ditinjau'))
) ENGINE = InnoDB;

-- Tabel volunteer_hours
CREATE TABLE volunteer_hours (
  idRelawan          VARCHAR(20)  NOT NULL,
  totalJam           DECIMAL(6,2) NOT NULL DEFAULT 0,
  statusVerifikasi   VARCHAR(30)  NOT NULL DEFAULT 'Menunggu',
  catatanPeninjauan  VARCHAR(255) NULL,
  diverifikasiOleh   VARCHAR(20)  NULL,
  updatedAt          DATETIME     NULL,
  CONSTRAINT pk_volunteer_hours PRIMARY KEY (idRelawan),
  CONSTRAINT fk_hours_relawan FOREIGN KEY (idRelawan)
    REFERENCES volunteer (idRelawan) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_hours_admin FOREIGN KEY (diverifikasiOleh)
    REFERENCES user (userId) ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT ck_hours_status CHECK (statusVerifikasi IN
    ('Menunggu', 'Terverifikasi', 'Perlu Ditinjau'))
) ENGINE = InnoDB;

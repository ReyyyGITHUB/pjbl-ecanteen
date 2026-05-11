CREATE TABLE IF NOT EXISTS `testimoni` (
  `id_testimoni` int(11) NOT NULL AUTO_INCREMENT,
  `nama` varchar(80) NOT NULL,
  `peran_label` varchar(120) NOT NULL,
  `isi_testimoni` text NOT NULL,
  `avatar_path` varchar(255) NOT NULL,
  `rating` tinyint(1) NOT NULL DEFAULT 5,
  `urutan` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_testimoni`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `testimoni` (`nama`, `peran_label`, `isi_testimoni`, `avatar_path`, `rating`, `urutan`, `is_active`)
SELECT
  'Bu Rina',
  'Guru SMKN 8 Semarang',
  'Istirahat terasa lebih efisien. Saya bisa pesan makanan lebih cepat tanpa antre, dan waktunya bisa dipakai buat istirahat beneran.',
  'assets/img/figma/testi-avatar-1.png',
  5,
  1,
  1
WHERE NOT EXISTS (
  SELECT 1 FROM `testimoni`
);

INSERT INTO `testimoni` (`nama`, `peran_label`, `isi_testimoni`, `avatar_path`, `rating`, `urutan`, `is_active`)
SELECT
  'Naila Putri',
  'Siswi PPLG SMKN 8 Semarang',
  'Pesan dulu dari kelas bikin jam istirahat lebih santai. Tinggal ambil, terus bisa langsung makan tanpa buru-buru.',
  'assets/img/figma/testi-avatar-2.png',
  5,
  2,
  1
WHERE NOT EXISTS (
  SELECT 1 FROM `testimoni` WHERE `urutan` = 2
);

INSERT INTO `testimoni` (`nama`, `peran_label`, `isi_testimoni`, `avatar_path`, `rating`, `urutan`, `is_active`)
SELECT
  'Bu Suharni',
  'Penjual Kantin Mak''e',
  'E-Canteen bantu saya ngatur antrean lebih rapi. Pesanan yang masuk juga lebih jelas, jadi lebih cepat diproses.',
  'assets/img/figma/testi-avatar-3.png',
  5,
  3,
  1
WHERE NOT EXISTS (
  SELECT 1 FROM `testimoni` WHERE `urutan` = 3
);

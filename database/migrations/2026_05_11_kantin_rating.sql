CREATE TABLE IF NOT EXISTS `rating_kantin` (
  `id_rating` int(11) NOT NULL AUTO_INCREMENT,
  `id_kantin` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `kode_pesanan` varchar(32) NOT NULL,
  `rating` tinyint(1) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_rating`),
  UNIQUE KEY `uniq_rating_kode_pesanan` (`kode_pesanan`),
  KEY `idx_rating_kantin` (`id_kantin`),
  KEY `idx_rating_user` (`id_user`),
  CONSTRAINT `fk_rating_kantin_kantin` FOREIGN KEY (`id_kantin`) REFERENCES `kantin` (`id_kantin`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_rating_kantin_user` FOREIGN KEY (`id_user`) REFERENCES `user` (`id_user`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

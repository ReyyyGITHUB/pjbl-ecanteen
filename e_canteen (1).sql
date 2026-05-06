-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 28, 2026 at 02:21 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `e_canteen`
--

-- --------------------------------------------------------

--
-- Table structure for table `kantin`
--

CREATE TABLE `kantin` (
  `id_kantin` int(11) NOT NULL,
  `nama_kantin` varchar(20) NOT NULL,
  `id_penjual` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `kantin`
--

INSERT INTO `kantin` (`id_kantin`, `nama_kantin`, `id_penjual`) VALUES
(1, 'kantin_mak_e', 1);

-- --------------------------------------------------------

--
-- Table structure for table `menu`
--

CREATE TABLE `menu` (
  `id_menu` int(11) NOT NULL,
  `id_kantin` int(11) NOT NULL,
  `nama_menu` varchar(22) NOT NULL,
  `kategori` enum('makanan','minuman','','') NOT NULL,
  `catatan` int(40) NOT NULL,
  `harga` int(11) NOT NULL,
  `sisa_stock` int(11) NOT NULL,
  `gambar` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `menu`
--

INSERT INTO `menu` (`id_menu`, `id_kantin`, `nama_menu`, `kategori`, `catatan`, `harga`, `sisa_stock`, `gambar`) VALUES
(1, 1, 'nasi_geprek', 'makanan', 0, 7000, 10, 'gambar.jpg'),
(2, 1, 'cireng', 'makanan', 0, 3000, 5, 'gambar.jpg'),
(7, 1, 'es_teh', 'minuman', 0, 2000, 20, 'gambar.jpg'),
(8, 1, 'gorengan', 'makanan', 0, 1000, 25, 'gambar.jpg'),
(11, 1, 'teajus', 'minuman', 0, 2000, 15, 'gambar.jpg'),
(12, 1, 'soto', 'makanan', 0, 5000, 25, 'gambar.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `order_pesanan`
--

CREATE TABLE `order_pesanan` (
  `id_order_pesanan` int(11) NOT NULL,
  `kode_pesanan` varchar(32) DEFAULT NULL,
  `id_menu` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `jumlah` int(11) NOT NULL,
  `tanggal_pesanan` date NOT NULL,
  `status_pesanan` enum('diproses','siap_diambil','ditolak') NOT NULL,
  `waktu_pengambilan` varchar(80) DEFAULT NULL,
  `catatan` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_pesanan`
--

INSERT INTO `order_pesanan` (`id_order_pesanan`, `id_menu`, `id_user`, `jumlah`, `tanggal_pesanan`, `status_pesanan`) VALUES
(1, 1, 1, 5, '2026-01-11', 'diproses'),
(2, 1, 1, 9, '2026-09-10', 'diproses');

-- --------------------------------------------------------

--
-- Table structure for table `payment`
--

CREATE TABLE `payment` (
  `id_payment` int(11) NOT NULL,
  `id_order_pesanan` int(11) NOT NULL,
  `kode_pesanan` varchar(32) DEFAULT NULL,
  `total_pembayaran` int(11) NOT NULL,
  `metode_pembayaran` enum('cash','qris') NOT NULL,
  `status_pembayaran` enum('menunggu_konfirmasi','pembayaran_dikonfirmasi','pembayaran_ditolak') NOT NULL,
  `bukti_pembayaran` varchar(255) NOT NULL,
  `bukti_original_name` varchar(255) DEFAULT NULL,
  `bukti_mime_type` varchar(100) DEFAULT NULL,
  `bukti_file_size` int(11) DEFAULT NULL,
  `wa_status` enum('pending','sent','failed') NOT NULL DEFAULT 'pending',
  `wa_error` text DEFAULT NULL,
  `wa_sent_at` datetime DEFAULT NULL,
  `buyer_wa_status` enum('pending','sent','failed') NOT NULL DEFAULT 'pending',
  `buyer_wa_error` text DEFAULT NULL,
  `buyer_wa_sent_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payment`
--

INSERT INTO `payment` (`id_payment`, `id_order_pesanan`, `total_pembayaran`, `metode_pembayaran`, `status_pembayaran`, `bukti_pembayaran`) VALUES
(1, 1, 10000, 'qris', 'pembayaran_dikonfirmasi', 'screenshot.jpg'),
(2, 2, 9000, 'cash', 'menunggu_konfirmasi', '-');

-- --------------------------------------------------------

--
-- Table structure for table `penjual`
--

CREATE TABLE `penjual` (
  `id_penjual` int(11) NOT NULL,
  `nama_penjual` varchar(20) NOT NULL,
  `password` varchar(12) NOT NULL,
  `no_telepon` varchar(20) NOT NULL,
  `username` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `penjual`
--

INSERT INTO `penjual` (`id_penjual`, `nama_penjual`, `password`, `no_telepon`, `username`) VALUES
(1, 'suharni', '12345', '81575747679', 'geprekmaeyummy');

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `id_user` int(11) NOT NULL,
  `nama_lengkap` varchar(20) NOT NULL,
  `kelas_jurusan` varchar(20) NOT NULL,
  `no_telepon` varchar(20) NOT NULL,
  `username` varchar(20) NOT NULL,
  `password` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`id_user`, `nama_lengkap`, `kelas_jurusan`, `no_telepon`, `username`, `password`) VALUES
(1, 'keisha_putri', 'x_pplg_2', '88233073873', 'keikei', '141414'),
(2, 'aida_dwi', 'x_pplg_2', '85749627249', 'aidadwi', '111111'),
(3, 'raditya_rayhan', 'x_pplg_2', '85799799857', 'radityaray', '252525'),
(4, 'clavino_ar_rafi', 'x_pplg_2', '89525621898', 'clavinovin', '999999'),
(5, 'sila_ramadani', 'x_pplg_2', '882005643412', 'silasila', '323232'),
(6, 'shafanira_risma', 'x_pplg_2', '89669470996', 'shafafa', '292929');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `kantin`
--
ALTER TABLE `kantin`
  ADD PRIMARY KEY (`id_kantin`),
  ADD KEY `fk_kantin_penjual` (`id_penjual`);

--
-- Indexes for table `menu`
--
ALTER TABLE `menu`
  ADD PRIMARY KEY (`id_menu`),
  ADD KEY `fk_menu_kantin` (`id_kantin`);

--
-- Indexes for table `order_pesanan`
--
ALTER TABLE `order_pesanan`
  ADD PRIMARY KEY (`id_order_pesanan`),
  ADD KEY `idx_order_kode_pesanan` (`kode_pesanan`),
  ADD KEY `fk_order_menu` (`id_menu`),
  ADD KEY `fk_order_user` (`id_user`);

--
-- Indexes for table `payment`
--
ALTER TABLE `payment`
  ADD PRIMARY KEY (`id_payment`),
  ADD KEY `idx_payment_kode_pesanan` (`kode_pesanan`),
  ADD KEY `fk_payment_order` (`id_order_pesanan`);

--
-- Indexes for table `penjual`
--
ALTER TABLE `penjual`
  ADD PRIMARY KEY (`id_penjual`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id_user`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `kantin`
--
ALTER TABLE `kantin`
  MODIFY `id_kantin` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `menu`
--
ALTER TABLE `menu`
  MODIFY `id_menu` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `order_pesanan`
--
ALTER TABLE `order_pesanan`
  MODIFY `id_order_pesanan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `payment`
--
ALTER TABLE `payment`
  MODIFY `id_payment` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `penjual`
--
ALTER TABLE `penjual`
  MODIFY `id_penjual` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `id_user` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `kantin`
--
ALTER TABLE `kantin`
  ADD CONSTRAINT `fk_kantin_penjual` FOREIGN KEY (`id_penjual`) REFERENCES `penjual` (`id_penjual`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `menu`
--
ALTER TABLE `menu`
  ADD CONSTRAINT `fk_menu_kantin` FOREIGN KEY (`id_kantin`) REFERENCES `kantin` (`id_kantin`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `order_pesanan`
--
ALTER TABLE `order_pesanan`
  ADD CONSTRAINT `fk_order_menu` FOREIGN KEY (`id_menu`) REFERENCES `menu` (`id_menu`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_order_user` FOREIGN KEY (`id_user`) REFERENCES `user` (`id_user`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `payment`
--
ALTER TABLE `payment`
  ADD CONSTRAINT `fk_payment_order` FOREIGN KEY (`id_order_pesanan`) REFERENCES `order_pesanan` (`id_order_pesanan`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

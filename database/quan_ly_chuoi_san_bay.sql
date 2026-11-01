-- ============================================================
-- CƠ SỞ DỮ LIỆU: `quan_ly_chuoi_san_bay` — FILE FINAL
-- ------------------------------------------------------------
-- Được gộp từ 2 file:
--   1) database/quan_ly_chuoi_san_bay.sql          (bản gốc)
--   2) database/quan_ly_chuoi_san_bay change 1.sql  (bản mở rộng)
--
-- CẤU TRÚC: lấy theo bản "change 1" vì là superset của bản gốc.
--   - Thêm bảng: lich_su_chuyen_bay, nhat_ky_lich_bay_mua
--   - lich_bay_mua: thêm cột trang_thai, ly_do
--   - nhat_ky_trang_thai_bay: đổi cột (thêm hanh_dong, trang_thai;
--     bỏ trang_thai_moi; id_nguoi_dung cho phép NULL)
--   - chuyen_bay_ngay.trang_thai_tong: đổi bộ giá trị ENUM sang
--     Dang_Mo / Cat_Canh / Ha_Canh / Delay / Huy
--
-- DỮ LIỆU: lấy theo bản "change 1" (137 chuyến bay, 373 dòng nhật ký...).
--   Bổ sung 1 hóa đơn mẫu của bản gốc (hóa đơn id=1 cho chuyến bay id=1)
--   vì bản change không còn dòng nào trong bảng hoa_don.
--
-- LƯU Ý QUAN TRỌNG:
--   - ENUM trạng thái chuyến bay đã đổi. Code KeToanController vẫn đang
--     tìm 'Hoan_Thanh' (2 chỗ) nên danh sách "chuyến bay đã hoàn thành"
--     sẽ rỗng. Cần sửa code thành 'Ha_Canh' (không thuộc file SQL này).
--   - Dữ liệu có một số dòng test (TEST..., TST..., SAD...) do quá trình
--     kiểm thử; có thể xóa nếu muốn dữ liệu sạch.
--
-- CÁCH IMPORT:
--   Cách 1 (khuyến nghị, giữ tiếng Việt):
--     mysql -u root --default-character-set=utf8mb4 < database/quan_ly_chuoi_san_bay_final.sql
--   Cách 2: phpMyAdmin -> Import -> chọn file -> Character set: utf-8 -> Go
--
--   File KHÔNG có DROP TABLE nên chỉ import được vào CSDL đang trống.
--   Muốn import lại từ đầu: DROP DATABASE `quan_ly_chuoi_san_bay`; rồi import.
-- ============================================================

CREATE DATABASE IF NOT EXISTS `quan_ly_chuoi_san_bay`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_general_ci;

USE `quan_ly_chuoi_san_bay`;

-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1
-- Thời gian đã tạo: Th10 30, 2026 lúc 12:38 PM
-- Phiên bản máy phục vụ: 10.4.32-MariaDB
-- Phiên bản PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `quan_ly_chuoi_san_bay`
--

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `bang_chuyen_hanh_ly`
--

CREATE TABLE `bang_chuyen_hanh_ly` (
  `id` int(11) NOT NULL,
  `id_nha_ga` int(11) NOT NULL,
  `so_bang_chuyen` varchar(20) NOT NULL,
  `khu_vuc` varchar(50) DEFAULT NULL,
  `trang_thai` enum('Hoat_Dong','Bao_Tri') DEFAULT 'Hoat_Dong'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `bang_chuyen_hanh_ly`
--

INSERT INTO `bang_chuyen_hanh_ly` (`id`, `id_nha_ga`, `so_bang_chuyen`, `khu_vuc`, `trang_thai`) VALUES
(1, 1, '01', 'Sảnh đến', 'Hoat_Dong'),
(2, 7, '01', 'Sảnh đến', 'Hoat_Dong'),
(3, 1, '02', 'Sảnh đến', 'Hoat_Dong'),
(4, 1, '03', 'Sảnh đến', 'Hoat_Dong'),
(5, 1, '04', 'Sảnh đến', 'Hoat_Dong'),
(6, 1, '05', 'Sảnh đến', 'Hoat_Dong'),
(7, 1, '06', 'Sảnh đến', 'Bao_Tri'),
(8, 2, '07', 'Sảnh đến', 'Hoat_Dong'),
(9, 2, '08', 'Sảnh đến', 'Hoat_Dong'),
(10, 2, '09', 'Sảnh đến', 'Hoat_Dong'),
(11, 2, '10', 'Sảnh đến', 'Hoat_Dong'),
(12, 2, '11', 'Sảnh đến', 'Hoat_Dong'),
(13, 2, '12', 'Sảnh đến', 'Bao_Tri'),
(14, 3, '01', 'Sảnh đến', 'Hoat_Dong'),
(15, 3, '02', 'Sảnh đến', 'Hoat_Dong'),
(16, 3, '03', 'Sảnh đến', 'Hoat_Dong'),
(17, 3, '04', 'Sảnh đến', 'Hoat_Dong'),
(18, 3, '05', 'Sảnh đến', 'Hoat_Dong'),
(19, 3, '06', 'Sảnh đến', 'Hoat_Dong'),
(20, 4, '07', 'Sảnh đến', 'Hoat_Dong'),
(21, 4, '08', 'Sảnh đến', 'Hoat_Dong'),
(22, 4, '09', 'Sảnh đến', 'Hoat_Dong'),
(23, 4, '10', 'Sảnh đến', 'Hoat_Dong'),
(24, 4, '11', 'Sảnh đến', 'Hoat_Dong'),
(25, 4, '12', 'Sảnh đến', 'Bao_Tri'),
(26, 5, '01', 'Sảnh đến', 'Hoat_Dong'),
(27, 5, '02', 'Sảnh đến', 'Hoat_Dong'),
(28, 5, '03', 'Sảnh đến', 'Hoat_Dong'),
(29, 5, '04', 'Sảnh đến', 'Hoat_Dong'),
(30, 6, '05', 'Sảnh đến', 'Hoat_Dong'),
(31, 6, '06', 'Sảnh đến', 'Hoat_Dong'),
(32, 6, '07', 'Sảnh đến', 'Hoat_Dong'),
(33, 6, '08', 'Sảnh đến', 'Hoat_Dong'),
(34, 7, '02', 'Sảnh đến', 'Hoat_Dong'),
(35, 7, '03', 'Sảnh đến', 'Hoat_Dong'),
(36, 8, '04', 'Sảnh đến', 'Hoat_Dong'),
(37, 8, '05', 'Sảnh đến', 'Hoat_Dong'),
(38, 8, '06', 'Sảnh đến', 'Bao_Tri'),
(39, 9, '01', 'Sảnh đến', 'Hoat_Dong'),
(40, 9, '02', 'Sảnh đến', 'Hoat_Dong'),
(41, 9, '03', 'Sảnh đến', 'Hoat_Dong'),
(42, 9, '04', 'Sảnh đến', 'Hoat_Dong'),
(43, 9, '05', 'Sảnh đến', 'Hoat_Dong'),
(44, 9, '06', 'Sảnh đến', 'Hoat_Dong'),
(45, 11, '01', 'Sảnh đến', 'Hoat_Dong'),
(46, 11, '02', 'Sảnh đến', 'Hoat_Dong'),
(47, 11, '03', 'Sảnh đến', 'Hoat_Dong'),
(48, 11, '04', 'Sảnh đến', 'Hoat_Dong'),
(49, 12, '01', 'Sảnh đến', 'Hoat_Dong'),
(50, 12, '02', 'Sảnh đến', 'Hoat_Dong'),
(51, 12, '03', 'Sảnh đến', 'Bao_Tri'),
(52, 13, '01', 'Sảnh đến', 'Hoat_Dong'),
(53, 13, '02', 'Sảnh đến', 'Hoat_Dong'),
(54, 14, '01', 'Sảnh đến', 'Hoat_Dong'),
(55, 14, '02', 'Sảnh đến', 'Hoat_Dong'),
(56, 15, '01', 'Sảnh đến', 'Hoat_Dong'),
(57, 16, '01', 'Sảnh đến', 'Hoat_Dong'),
(58, 16, '02', 'Sảnh đến', 'Hoat_Dong'),
(59, 17, '01', 'Sảnh đến', 'Hoat_Dong'),
(60, 18, '01', 'Sảnh đến', 'Hoat_Dong'),
(61, 18, '02', 'Sảnh đến', 'Bao_Tri'),
(62, 19, '01', 'Sảnh đến', 'Hoat_Dong'),
(63, 19, '02', 'Sảnh đến', 'Hoat_Dong'),
(64, 20, '01', 'Sảnh đến', 'Hoat_Dong');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `bang_gia_dich_vu`
--

CREATE TABLE `bang_gia_dich_vu` (
  `id` int(11) NOT NULL,
  `loai_dich_vu` enum('Phi_Cat_Ha_Canh','Phi_Do_Tau_Bay','Phi_Phuc_Vu_Hanh_Khach') NOT NULL,
  `don_gia` decimal(15,2) NOT NULL,
  `don_vi_tinh` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `bang_gia_dich_vu`
--

INSERT INTO `bang_gia_dich_vu` (`id`, `loai_dich_vu`, `don_gia`, `don_vi_tinh`) VALUES
(1, 'Phi_Cat_Ha_Canh', 1500000.00, 'Lượt'),
(2, 'Phi_Do_Tau_Bay', 50000.00, 'Giờ/Tấn MTOW'),
(3, 'Phi_Phuc_Vu_Hanh_Khach', 100000.00, 'Hành khách');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `chuyen_bay_ngay`
--

CREATE TABLE `chuyen_bay_ngay` (
  `id` int(11) NOT NULL,
  `id_lich_mua` int(11) DEFAULT NULL,
  `so_hieu` varchar(20) NOT NULL,
  `id_hang_hang_khong` int(11) NOT NULL,
  `id_loai_tau_bay` int(11) NOT NULL,
  `id_san_bay_di` int(11) NOT NULL,
  `id_san_bay_den` int(11) NOT NULL,
  `id_cua_ra_may_bay` int(11) DEFAULT NULL,
  `id_quay_checkin` int(11) DEFAULT NULL,
  `id_bang_chuyen` int(11) DEFAULT NULL,
  `gio_du_kien` datetime NOT NULL,
  `gio_thuc_te` datetime DEFAULT NULL,
  `luong_hanh_khach` int(11) DEFAULT 0,
  `luong_hang_hoa_tan` decimal(10,2) DEFAULT 0.00,
  `trang_thai_tong` enum('Dang_Mo','Cat_Canh','Ha_Canh','Delay','Huy') DEFAULT 'Dang_Mo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `chuyen_bay_ngay`
--

INSERT INTO `chuyen_bay_ngay` (`id`, `id_lich_mua`, `so_hieu`, `id_hang_hang_khong`, `id_loai_tau_bay`, `id_san_bay_di`, `id_san_bay_den`, `id_cua_ra_may_bay`, `id_quay_checkin`, `id_bang_chuyen`, `gio_du_kien`, `gio_thuc_te`, `luong_hanh_khach`, `luong_hang_hoa_tan`, `trang_thai_tong`) VALUES
(1, 1, 'VN210', 1, 2, 2, 1, 3, 3, 1, '2026-10-20 08:00:00', '2026-10-20 08:05:00', 295, 12.50, 'Ha_Canh'),
(2, NULL, 'VN210', 1, 2, 2, 1, 27, 8, NULL, '2026-10-21 08:00:00', '2026-10-27 09:55:00', 1000, 0.00, 'Ha_Canh'),
(3, NULL, 'VJ777', 2, 1, 1, 3, 1, 1, 2, '2026-10-20 14:30:00', '2026-10-27 15:53:00', 210, 5.00, 'Ha_Canh'),
(4, NULL, 'VN1234', 3, 1, 1, 12, 1, 2, 10, '2026-10-27 09:44:00', '2026-10-27 07:44:00', 10, 0.00, 'Ha_Canh'),
(6, NULL, 'VN0000', 3, 1, 3, 4, 69, 17, 2, '2026-10-27 08:55:00', '2026-10-27 09:56:00', 1000, 10.00, 'Ha_Canh'),
(7, NULL, 'VN0000', 3, 1, 3, 4, 69, 17, 34, '2026-10-27 06:00:00', '2026-10-27 08:00:00', 1000, 0.00, 'Ha_Canh'),
(8, NULL, 'VJ773', 3, 1, 3, 4, NULL, NULL, NULL, '2026-11-10 12:30:00', NULL, 100, 0.00, 'Dang_Mo'),
(9, NULL, 'VN0007', 3, 1, 3, 4, NULL, NULL, NULL, '2026-11-10 12:30:00', NULL, 100, 0.00, 'Dang_Mo'),
(10, NULL, 'VN217', 1, 1, 3, 1, NULL, NULL, NULL, '2026-11-10 12:30:00', NULL, 1, 0.00, 'Dang_Mo'),
(11, NULL, 'VJ776', 1, 1, 3, 1, 79, 17, 2, '2026-11-10 12:30:00', NULL, 1, 0.00, 'Dang_Mo'),
(12, NULL, 'VN1235', 1, 1, 3, 1, NULL, NULL, NULL, '2026-11-10 12:30:00', NULL, 1, 0.00, 'Dang_Mo'),
(13, NULL, 'VN1211', 3, 1, 10, 15, 118, NULL, 56, '2026-10-27 09:10:00', '2026-10-27 20:10:00', 230, 0.00, 'Ha_Canh'),
(15, NULL, 'VN1112', 3, 1, 10, 7, 118, 35, 56, '2026-10-27 09:19:00', '2026-10-27 10:19:00', 213, 0.00, 'Ha_Canh'),
(39, 2, 'VJ777', 2, 1, 1, 3, NULL, NULL, NULL, '2026-10-28 08:00:00', '2026-10-28 09:30:00', 0, 0.00, 'Ha_Canh'),
(40, 2, 'VJ777', 2, 1, 1, 3, NULL, NULL, NULL, '2026-10-30 18:18:34', '2026-10-30 19:48:34', 0, 0.00, 'Cat_Canh'),
(41, 2, 'VJ777', 2, 1, 1, 3, NULL, NULL, NULL, '2026-11-02 08:00:00', '2026-11-02 09:30:00', 0, 0.00, 'Dang_Mo'),
(42, 1, 'VN210', 1, 1, 2, 1, NULL, NULL, NULL, '2026-10-27 08:00:00', '2026-10-27 09:30:00', 0, 0.00, 'Ha_Canh'),
(43, 1, 'VN210', 1, 1, 2, 1, NULL, NULL, NULL, '2026-10-28 08:00:00', '2026-10-28 09:30:00', 0, 0.00, 'Ha_Canh'),
(44, 1, 'VN210', 1, 1, 2, 1, NULL, NULL, NULL, '2026-10-29 08:00:00', '2026-10-29 09:30:00', 0, 0.00, 'Huy'),
(45, 1, 'VN210', 1, 1, 2, 1, NULL, NULL, NULL, '2026-10-30 18:18:31', '2026-10-30 19:48:31', 0, 0.00, 'Cat_Canh'),
(46, 1, 'VN210', 1, 1, 2, 1, NULL, NULL, NULL, '2026-10-31 08:00:00', '2026-10-31 09:30:00', 0, 0.00, 'Dang_Mo'),
(47, 1, 'VN210', 1, 1, 2, 1, NULL, NULL, NULL, '2026-11-01 08:00:00', '2026-11-01 09:30:00', 0, 0.00, 'Dang_Mo'),
(48, 1, 'VN210', 1, 1, 2, 1, NULL, NULL, NULL, '2026-11-02 08:00:00', '2026-11-02 09:30:00', 0, 0.00, 'Dang_Mo'),
(54, 2, 'VJ777', 2, 1, 1, 3, NULL, NULL, NULL, '2026-10-02 08:00:00', '2026-10-02 09:30:00', 0, 0.00, 'Ha_Canh'),
(55, 2, 'VJ777', 2, 1, 1, 3, NULL, NULL, NULL, '2026-10-05 08:00:00', '2026-10-05 09:30:00', 0, 0.00, 'Ha_Canh'),
(56, 2, 'VJ777', 2, 1, 1, 3, NULL, NULL, NULL, '2026-10-07 08:00:00', '2026-10-07 09:30:00', 0, 0.00, 'Ha_Canh'),
(57, 2, 'VJ777', 2, 1, 1, 3, NULL, NULL, NULL, '2026-10-09 08:00:00', '2026-10-09 09:30:00', 0, 0.00, 'Ha_Canh'),
(58, 2, 'VJ777', 2, 1, 1, 3, NULL, NULL, NULL, '2026-10-12 08:00:00', '2026-10-12 09:30:00', 0, 0.00, 'Ha_Canh'),
(59, 2, 'VJ777', 2, 1, 1, 3, NULL, NULL, NULL, '2026-10-14 08:00:00', '2026-10-14 09:30:00', 0, 0.00, 'Ha_Canh'),
(60, 2, 'VJ777', 2, 1, 1, 3, NULL, NULL, NULL, '2026-10-16 08:00:00', '2026-10-16 09:30:00', 0, 0.00, 'Ha_Canh'),
(61, 2, 'VJ777', 2, 1, 1, 3, NULL, NULL, NULL, '2026-10-19 08:00:00', '2026-10-19 09:30:00', 0, 0.00, 'Ha_Canh'),
(62, 2, 'VJ777', 2, 1, 1, 3, NULL, NULL, NULL, '2026-10-21 08:00:00', '2026-10-21 09:30:00', 0, 0.00, 'Ha_Canh'),
(63, 2, 'VJ777', 2, 1, 1, 3, NULL, NULL, NULL, '2026-10-23 08:00:00', '2026-10-23 09:30:00', 0, 0.00, 'Ha_Canh'),
(64, 2, 'VJ777', 2, 1, 1, 3, NULL, NULL, NULL, '2026-10-26 08:00:00', '2026-10-26 09:30:00', 0, 0.00, 'Ha_Canh'),
(65, 2, 'VJ777', 2, 1, 1, 3, NULL, NULL, NULL, '2026-11-04 08:00:00', '2026-11-04 09:30:00', 0, 0.00, 'Dang_Mo'),
(66, 2, 'VJ777', 2, 1, 1, 3, NULL, NULL, NULL, '2026-11-06 08:00:00', '2026-11-06 09:30:00', 0, 0.00, 'Dang_Mo'),
(67, 2, 'VJ777', 2, 1, 1, 3, NULL, NULL, NULL, '2026-11-09 08:00:00', '2026-11-09 09:30:00', 0, 0.00, 'Dang_Mo'),
(68, 2, 'VJ777', 2, 1, 1, 3, NULL, NULL, NULL, '2026-11-11 08:00:00', '2026-11-11 09:30:00', 0, 0.00, 'Dang_Mo'),
(69, 2, 'VJ777', 2, 1, 1, 3, NULL, NULL, NULL, '2026-11-13 08:00:00', '2026-11-13 09:30:00', 0, 0.00, 'Dang_Mo'),
(70, 2, 'VJ777', 2, 1, 1, 3, NULL, NULL, NULL, '2026-11-16 08:00:00', '2026-11-16 09:30:00', 0, 0.00, 'Dang_Mo'),
(71, 2, 'VJ777', 2, 1, 1, 3, NULL, NULL, NULL, '2026-11-18 08:00:00', '2026-11-18 09:30:00', 0, 0.00, 'Dang_Mo'),
(72, 2, 'VJ777', 2, 1, 1, 3, NULL, NULL, NULL, '2026-11-20 08:00:00', '2026-11-20 09:30:00', 0, 0.00, 'Dang_Mo'),
(73, 2, 'VJ777', 2, 1, 1, 3, NULL, NULL, NULL, '2026-11-23 08:00:00', '2026-11-23 09:30:00', 0, 0.00, 'Dang_Mo'),
(74, 2, 'VJ777', 2, 1, 1, 3, NULL, NULL, NULL, '2026-11-25 08:00:00', '2026-11-25 09:30:00', 0, 0.00, 'Dang_Mo'),
(75, 2, 'VJ777', 2, 1, 1, 3, NULL, NULL, NULL, '2026-11-27 08:00:00', '2026-11-27 09:30:00', 0, 0.00, 'Dang_Mo'),
(76, 2, 'VJ777', 2, 1, 1, 3, NULL, NULL, NULL, '2026-11-30 08:00:00', '2026-11-30 09:30:00', 10, 0.01, 'Delay'),
(77, 7, 'VN123', 3, 1, 3, 4, NULL, NULL, NULL, '2026-10-02 08:00:00', '2026-10-02 09:30:00', 0, 0.00, 'Ha_Canh'),
(78, 7, 'VN123', 3, 1, 3, 4, NULL, NULL, NULL, '2026-10-05 08:00:00', '2026-10-05 09:30:00', 0, 0.00, 'Ha_Canh'),
(79, 7, 'VN123', 3, 1, 3, 4, NULL, NULL, NULL, '2026-10-07 08:00:00', '2026-10-07 09:30:00', 0, 0.00, 'Ha_Canh'),
(80, 7, 'VN123', 3, 1, 3, 4, NULL, NULL, NULL, '2026-10-09 08:00:00', '2026-10-09 09:30:00', 0, 0.00, 'Ha_Canh'),
(81, 7, 'VN123', 3, 1, 3, 4, NULL, NULL, NULL, '2026-10-12 08:00:00', '2026-10-12 09:30:00', 0, 0.00, 'Ha_Canh'),
(82, 7, 'VN123', 3, 1, 3, 4, NULL, NULL, NULL, '2026-10-14 08:00:00', '2026-10-14 09:30:00', 0, 0.00, 'Ha_Canh'),
(83, 7, 'VN123', 3, 1, 3, 4, NULL, NULL, NULL, '2026-10-16 08:00:00', '2026-10-16 09:30:00', 0, 0.00, 'Ha_Canh'),
(84, 7, 'VN123', 3, 1, 3, 4, NULL, NULL, NULL, '2026-10-19 08:00:00', '2026-10-19 09:30:00', 0, 0.00, 'Ha_Canh'),
(85, 7, 'VN123', 3, 1, 3, 4, NULL, NULL, NULL, '2026-10-21 08:00:00', '2026-10-21 09:30:00', 0, 0.00, 'Ha_Canh'),
(86, 7, 'VN123', 3, 1, 3, 4, NULL, NULL, NULL, '2026-10-23 08:00:00', '2026-10-23 09:30:00', 0, 0.00, 'Ha_Canh'),
(87, 7, 'VN123', 3, 1, 3, 4, NULL, NULL, NULL, '2026-10-26 08:00:00', '2026-10-26 09:30:00', 0, 0.00, 'Ha_Canh'),
(88, 7, 'VN123', 3, 1, 3, 4, NULL, NULL, NULL, '2026-10-28 08:00:00', '2026-10-28 09:30:00', 0, 0.00, 'Ha_Canh'),
(89, 7, 'VN123', 3, 1, 3, 4, NULL, NULL, NULL, '2026-10-30 18:18:28', '2026-10-30 19:48:28', 0, 0.00, 'Cat_Canh'),
(90, 1, 'VN210', 1, 1, 2, 1, NULL, NULL, NULL, '2026-10-01 08:00:00', '2026-10-01 09:30:00', 0, 0.00, 'Ha_Canh'),
(91, 1, 'VN210', 1, 1, 2, 1, NULL, NULL, NULL, '2026-10-02 08:00:00', '2026-10-02 09:30:00', 0, 0.00, 'Ha_Canh'),
(92, 1, 'VN210', 1, 1, 2, 1, NULL, NULL, NULL, '2026-10-03 08:00:00', '2026-10-03 09:30:00', 0, 0.00, 'Ha_Canh'),
(93, 1, 'VN210', 1, 1, 2, 1, NULL, NULL, NULL, '2026-10-04 08:00:00', '2026-10-04 09:30:00', 0, 0.00, 'Ha_Canh'),
(94, 1, 'VN210', 1, 1, 2, 1, NULL, NULL, NULL, '2026-10-05 08:00:00', '2026-10-05 09:30:00', 0, 0.00, 'Ha_Canh'),
(95, 1, 'VN210', 1, 1, 2, 1, NULL, NULL, NULL, '2026-10-06 08:00:00', '2026-10-06 09:30:00', 0, 0.00, 'Ha_Canh'),
(96, 1, 'VN210', 1, 1, 2, 1, NULL, NULL, NULL, '2026-10-07 08:00:00', '2026-10-07 09:30:00', 0, 0.00, 'Ha_Canh'),
(97, 1, 'VN210', 1, 1, 2, 1, NULL, NULL, NULL, '2026-10-08 08:00:00', '2026-10-08 09:30:00', 0, 0.00, 'Ha_Canh'),
(98, 1, 'VN210', 1, 1, 2, 1, NULL, NULL, NULL, '2026-10-09 08:00:00', '2026-10-09 09:30:00', 0, 0.00, 'Ha_Canh'),
(99, 1, 'VN210', 1, 1, 2, 1, NULL, NULL, NULL, '2026-10-10 08:00:00', '2026-10-10 09:30:00', 0, 0.00, 'Ha_Canh'),
(100, 1, 'VN210', 1, 1, 2, 1, NULL, NULL, NULL, '2026-10-11 08:00:00', '2026-10-11 09:30:00', 0, 0.00, 'Ha_Canh'),
(101, 1, 'VN210', 1, 1, 2, 1, NULL, NULL, NULL, '2026-10-12 08:00:00', '2026-10-12 09:30:00', 0, 0.00, 'Ha_Canh'),
(102, 1, 'VN210', 1, 1, 2, 1, NULL, NULL, NULL, '2026-10-13 08:00:00', '2026-10-13 09:30:00', 0, 0.00, 'Ha_Canh'),
(103, 1, 'VN210', 1, 1, 2, 1, NULL, NULL, NULL, '2026-10-14 08:00:00', '2026-10-14 09:30:00', 0, 0.00, 'Ha_Canh'),
(104, 1, 'VN210', 1, 1, 2, 1, NULL, NULL, NULL, '2026-10-15 08:00:00', '2026-10-15 09:30:00', 0, 0.00, 'Ha_Canh'),
(105, 1, 'VN210', 1, 1, 2, 1, NULL, NULL, NULL, '2026-10-16 08:00:00', '2026-10-16 09:30:00', 0, 0.00, 'Ha_Canh'),
(106, 1, 'VN210', 1, 1, 2, 1, NULL, NULL, NULL, '2026-10-17 08:00:00', '2026-10-17 09:30:00', 0, 0.00, 'Ha_Canh'),
(107, 1, 'VN210', 1, 1, 2, 1, NULL, NULL, NULL, '2026-10-18 08:00:00', '2026-10-18 09:30:00', 0, 0.00, 'Ha_Canh'),
(108, 1, 'VN210', 1, 1, 2, 1, NULL, NULL, NULL, '2026-10-19 08:00:00', '2026-10-19 09:30:00', 0, 0.00, 'Ha_Canh'),
(109, 1, 'VN210', 1, 1, 2, 1, NULL, NULL, NULL, '2026-10-22 08:00:00', '2026-10-22 09:30:00', 0, 0.00, 'Ha_Canh'),
(110, 1, 'VN210', 1, 1, 2, 1, NULL, NULL, NULL, '2026-10-23 08:00:00', '2026-10-23 09:30:00', 0, 0.00, 'Ha_Canh'),
(111, 1, 'VN210', 1, 1, 2, 1, NULL, NULL, NULL, '2026-10-24 08:00:00', '2026-10-24 09:30:00', 0, 0.00, 'Ha_Canh'),
(112, 1, 'VN210', 1, 1, 2, 1, NULL, NULL, NULL, '2026-10-25 08:00:00', '2026-10-25 09:30:00', 0, 0.00, 'Ha_Canh'),
(113, 1, 'VN210', 1, 1, 2, 1, NULL, NULL, NULL, '2026-10-26 08:00:00', '2026-10-26 09:30:00', 0, 0.00, 'Ha_Canh'),
(114, 1, 'VN210', 1, 1, 2, 1, NULL, NULL, NULL, '2026-11-03 08:00:00', '2026-11-03 09:30:00', 0, 0.00, 'Dang_Mo'),
(115, 1, 'VN210', 1, 1, 2, 1, NULL, NULL, NULL, '2026-11-04 08:00:00', '2026-11-04 09:30:00', 0, 0.00, 'Dang_Mo'),
(116, 1, 'VN210', 1, 1, 2, 1, NULL, NULL, NULL, '2026-11-05 08:00:00', '2026-11-05 09:30:00', 0, 0.00, 'Dang_Mo'),
(117, 1, 'VN210', 1, 1, 2, 1, NULL, NULL, NULL, '2026-11-06 08:00:00', '2026-11-06 09:30:00', 0, 0.00, 'Dang_Mo'),
(118, 1, 'VN210', 1, 1, 2, 1, NULL, NULL, NULL, '2026-11-07 08:00:00', '2026-11-07 09:30:00', 0, 0.00, 'Dang_Mo'),
(119, 1, 'VN210', 1, 1, 2, 1, NULL, NULL, NULL, '2026-11-08 08:00:00', '2026-11-08 09:30:00', 0, 0.00, 'Dang_Mo'),
(120, 1, 'VN210', 1, 1, 2, 1, NULL, NULL, NULL, '2026-11-09 08:00:00', '2026-11-09 09:30:00', 0, 0.00, 'Dang_Mo'),
(121, 1, 'VN210', 1, 1, 2, 1, NULL, NULL, NULL, '2026-11-10 08:00:00', '2026-11-10 09:30:00', 0, 0.00, 'Dang_Mo'),
(122, 1, 'VN210', 1, 1, 2, 1, NULL, NULL, NULL, '2026-11-11 08:00:00', '2026-11-11 09:30:00', 0, 0.00, 'Dang_Mo'),
(123, 1, 'VN210', 1, 1, 2, 1, NULL, NULL, NULL, '2026-11-12 08:00:00', '2026-11-12 09:30:00', 0, 0.00, 'Dang_Mo'),
(124, 1, 'VN210', 1, 1, 2, 1, NULL, NULL, NULL, '2026-11-13 08:00:00', '2026-11-13 09:30:00', 0, 0.00, 'Dang_Mo'),
(125, 1, 'VN210', 1, 1, 2, 1, NULL, NULL, NULL, '2026-11-14 08:00:00', '2026-11-14 09:30:00', 0, 0.00, 'Dang_Mo'),
(126, 1, 'VN210', 1, 1, 2, 1, NULL, NULL, NULL, '2026-11-15 08:00:00', '2026-11-15 09:30:00', 0, 0.00, 'Dang_Mo'),
(127, 1, 'VN210', 1, 1, 2, 1, NULL, NULL, NULL, '2026-11-16 08:00:00', '2026-11-16 09:30:00', 0, 0.00, 'Dang_Mo'),
(128, 1, 'VN210', 1, 1, 2, 1, NULL, NULL, NULL, '2026-11-17 08:00:00', '2026-11-17 09:30:00', 0, 0.00, 'Dang_Mo'),
(129, 1, 'VN210', 1, 1, 2, 1, NULL, NULL, NULL, '2026-11-18 08:00:00', '2026-11-18 09:30:00', 0, 0.00, 'Huy'),
(130, 1, 'VN210', 1, 1, 2, 1, NULL, NULL, NULL, '2026-11-19 08:00:00', '2026-11-19 09:30:00', 0, 0.00, 'Dang_Mo'),
(131, 1, 'VN210', 1, 1, 2, 1, NULL, NULL, NULL, '2026-11-20 08:00:00', '2026-11-20 09:30:00', 0, 0.00, 'Dang_Mo'),
(132, 1, 'VN210', 1, 1, 2, 1, NULL, NULL, NULL, '2026-11-21 08:00:00', '2026-11-21 09:30:00', 0, 0.00, 'Dang_Mo'),
(133, 1, 'VN210', 1, 1, 2, 1, NULL, NULL, NULL, '2026-11-22 08:00:00', '2026-11-22 09:30:00', 0, 0.00, 'Dang_Mo'),
(134, 1, 'VN210', 1, 1, 2, 1, NULL, NULL, NULL, '2026-11-23 08:00:00', '2026-11-23 09:30:00', 0, 0.00, 'Dang_Mo'),
(135, 1, 'VN210', 1, 1, 2, 1, NULL, NULL, NULL, '2026-11-24 08:00:00', '2026-11-24 09:30:00', 0, 0.00, 'Cat_Canh'),
(136, 1, 'VN210', 1, 1, 2, 1, NULL, NULL, NULL, '2026-11-25 08:00:00', '2026-11-25 09:30:00', 0, 0.00, 'Dang_Mo'),
(137, 1, 'VN210', 1, 1, 2, 1, NULL, NULL, NULL, '2026-11-26 08:00:00', '2026-11-26 09:30:00', 0, 0.00, 'Dang_Mo'),
(138, 1, 'VN210', 1, 1, 2, 1, NULL, NULL, NULL, '2026-11-27 08:00:00', '2026-11-27 09:30:00', 0, 0.00, 'Dang_Mo'),
(139, 1, 'VN219', 1, 1, 2, 1, NULL, NULL, NULL, '2026-11-28 08:00:00', '2026-11-28 09:30:00', 4, 0.00, 'Dang_Mo'),
(140, 1, 'VN210', 1, 1, 2, 1, NULL, NULL, NULL, '2026-11-29 08:00:00', '2026-11-29 09:30:00', 0, 0.00, 'Dang_Mo'),
(141, 1, 'VN281', 1, 1, 2, 1, 47, 8, 14, '2026-11-30 08:00:00', '2026-11-30 09:30:00', 123, 0.10, 'Huy'),
(160, NULL, 'VN0001', 3, 1, 3, 4, 78, 19, 36, '2026-10-27 23:17:00', '2026-10-27 23:27:00', 230, 0.00, 'Ha_Canh'),
(166, NULL, 'TEST0000', 3, 1, 8, 11, 109, 31, 52, '2026-10-28 14:06:00', '2026-10-28 14:20:00', 230, 89.00, 'Ha_Canh'),
(174, NULL, 'TEST0101', 3, 1, 7, 12, 103, 29, 49, '2026-10-28 14:29:48', '2026-10-28 14:35:48', 123, 0.13, 'Ha_Canh'),
(187, 16, 'VN111', 3, 1, 12, 2, NULL, NULL, NULL, '2026-10-28 08:00:00', '2026-10-28 09:30:00', 0, 0.00, 'Ha_Canh'),
(188, NULL, 'TST01', 1, 1, 4, 1, NULL, NULL, NULL, '2026-10-28 14:47:39', '2026-10-28 16:17:39', 118, 1.20, 'Huy'),
(189, NULL, 'TST02', 2, 1, 5, 2, NULL, NULL, NULL, '2026-10-28 14:45:39', '2026-10-28 16:15:39', 132, 1.40, 'Dang_Mo'),
(190, NULL, 'TST03', 3, 1, 6, 1, NULL, NULL, NULL, '2026-10-28 14:43:39', '2026-10-28 16:13:39', 96, 0.95, 'Dang_Mo'),
(191, NULL, 'TST04', 1, 2, 7, 2, NULL, NULL, NULL, '2026-10-28 14:41:39', '2026-10-28 16:11:39', 210, 2.60, 'Dang_Mo'),
(192, NULL, 'TST05', 2, 1, 8, 1, NULL, NULL, NULL, '2026-10-28 14:30:39', '2026-10-28 16:00:39', 145, 1.55, 'Dang_Mo'),
(193, NULL, 'TST06', 3, 1, 9, 2, NULL, NULL, NULL, '2026-10-28 14:26:39', '2026-10-28 15:56:39', 108, 1.10, 'Dang_Mo'),
(194, NULL, 'TST07', 1, 3, 10, 1, NULL, NULL, NULL, '2026-10-28 14:22:39', '2026-10-28 15:52:39', 264, 3.20, 'Ha_Canh'),
(195, NULL, 'TST08', 2, 1, 11, 2, NULL, NULL, NULL, '2026-10-28 14:18:39', '2026-10-28 15:48:39', 121, 1.30, 'Ha_Canh'),
(196, NULL, 'VN1123', 3, 1, 15, 11, 138, 42, 64, '2026-10-28 15:08:00', '2026-10-28 15:12:00', 1, 0.00, 'Ha_Canh'),
(197, NULL, 'TEST1123', 3, 1, 10, 7, 118, 35, 56, '2026-10-29 21:32:04', '2026-10-29 21:36:00', 100, 3.00, 'Ha_Canh'),
(198, NULL, 'TEST1213', 3, 1, 4, 14, 63, 15, 30, '2026-10-29 22:13:17', '2026-10-29 22:13:17', 131, 0.23, 'Ha_Canh'),
(199, NULL, 'TEST1231', 2, 1, 12, 7, 126, 38, 59, '2026-10-29 22:22:11', '2026-10-29 22:22:11', 122, 3.00, 'Ha_Canh'),
(200, NULL, 'VN1211', 3, 1, 15, 10, 138, 42, 64, '2026-10-29 22:34:14', '2026-10-29 22:34:00', 123, 45.01, 'Ha_Canh'),
(201, NULL, 'VJ1231', 3, 1, 6, 11, 95, 26, 45, '2026-10-29 22:46:02', '2026-10-29 22:50:00', 123, 23.00, 'Ha_Canh'),
(202, NULL, 'VN1275', 3, 1, 2, 6, NULL, 10, 21, '2026-10-29 22:58:12', '2026-10-29 23:01:00', 223, 23.00, 'Ha_Canh'),
(203, NULL, 'V7890', 3, 1, 1, 15, 26, 5, 11, '2026-10-29 23:15:02', '2026-10-29 23:18:02', 212, 32.00, 'Ha_Canh'),
(204, NULL, 'VN7689', 3, 1, 8, 12, 109, 31, 52, '2026-10-29 23:19:17', '2026-10-29 23:31:17', 123, 33.00, 'Ha_Canh'),
(205, NULL, 'SADD', 3, 1, 11, 10, 122, 36, 57, '2026-10-30 18:23:52', '2026-10-30 18:27:52', 123, 12.00, 'Ha_Canh'),
(206, NULL, 'TEST1231', 3, 1, 4, 14, 63, 15, 30, '2026-10-30 18:39:23', '2026-10-30 18:44:00', 123, 23.00, 'Delay'),
(212, NULL, 'ZZLIMIT4', 3, 1, 1, 14, 63, 15, 30, '2026-10-30 18:18:03', '2026-10-30 19:38:03', 10, 1.00, 'Huy'),
(213, NULL, 'ZZLIMIT5', 3, 1, 1, 14, 63, 15, 30, '2026-10-30 19:38:03', '2026-10-30 20:43:03', 10, 1.00, 'Huy'),
(214, NULL, 'ZZLIMIT6', 3, 1, 1, 14, 63, 15, 30, '2026-10-30 18:48:03', '2026-10-30 19:53:03', 10, 1.00, 'Delay');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `cua_ra_may_bay`
--

CREATE TABLE `cua_ra_may_bay` (
  `id` int(11) NOT NULL,
  `id_nha_ga` int(11) NOT NULL,
  `so_cua` varchar(20) NOT NULL,
  `khu_vuc` varchar(50) DEFAULT NULL,
  `trang_thai` enum('Hoat_Dong','Bao_Tri') DEFAULT 'Hoat_Dong'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `cua_ra_may_bay`
--

INSERT INTO `cua_ra_may_bay` (`id`, `id_nha_ga`, `so_cua`, `khu_vuc`, `trang_thai`) VALUES
(1, 1, '1', 'Tầng 2 - Khu A', 'Hoat_Dong'),
(2, 1, '2', 'Tầng 2 - Khu A', 'Hoat_Dong'),
(3, 3, '10', 'Tầng 2 - Khu B', 'Hoat_Dong'),
(4, 3, '11', 'Tầng 2 - Khu B', 'Bao_Tri'),
(5, 1, '3', 'Tầng 2 - Khu A', 'Hoat_Dong'),
(6, 1, '4', 'Tầng 2 - Khu A', 'Hoat_Dong'),
(7, 1, '5', 'Tầng 2 - Khu A', 'Hoat_Dong'),
(8, 1, '6', 'Tầng 2 - Khu A', 'Hoat_Dong'),
(9, 1, '7', 'Tầng 2 - Khu A', 'Hoat_Dong'),
(10, 1, '8', 'Tầng 2 - Khu A', 'Hoat_Dong'),
(11, 1, '9', 'Tầng 2 - Khu B', 'Hoat_Dong'),
(12, 1, '10', 'Tầng 2 - Khu B', 'Hoat_Dong'),
(13, 1, '11', 'Tầng 2 - Khu B', 'Hoat_Dong'),
(14, 1, '12', 'Tầng 2 - Khu B', 'Hoat_Dong'),
(15, 1, '13', 'Tầng 2 - Khu B', 'Hoat_Dong'),
(16, 1, '14', 'Tầng 2 - Khu B', 'Bao_Tri'),
(17, 2, '21', 'Tầng 2 - Khu quốc tế', 'Hoat_Dong'),
(18, 2, '22', 'Tầng 2 - Khu quốc tế', 'Hoat_Dong'),
(19, 2, '23', 'Tầng 2 - Khu quốc tế', 'Hoat_Dong'),
(20, 2, '24', 'Tầng 2 - Khu quốc tế', 'Hoat_Dong'),
(21, 2, '25', 'Tầng 2 - Khu quốc tế', 'Hoat_Dong'),
(22, 2, '26', 'Tầng 2 - Khu quốc tế', 'Hoat_Dong'),
(23, 2, '27', 'Tầng 2 - Khu quốc tế', 'Hoat_Dong'),
(24, 2, '28', 'Tầng 2 - Khu quốc tế', 'Hoat_Dong'),
(25, 2, '29', 'Tầng 2 - Khu quốc tế', 'Bao_Tri'),
(26, 2, '30', 'Tầng 2 - Khu quốc tế', 'Hoat_Dong'),
(27, 3, '1', 'Tầng 2 - Khu A', 'Hoat_Dong'),
(28, 3, '2', 'Tầng 2 - Khu A', 'Hoat_Dong'),
(29, 3, '3', 'Tầng 2 - Khu A', 'Hoat_Dong'),
(30, 3, '4', 'Tầng 2 - Khu A', 'Hoat_Dong'),
(31, 3, '5', 'Tầng 2 - Khu A', 'Hoat_Dong'),
(32, 3, '6', 'Tầng 2 - Khu A', 'Hoat_Dong'),
(33, 3, '7', 'Tầng 2 - Khu A', 'Hoat_Dong'),
(34, 3, '8', 'Tầng 2 - Khu A', 'Hoat_Dong'),
(35, 3, '9', 'Tầng 2 - Khu B', 'Hoat_Dong'),
(36, 3, '12', 'Tầng 2 - Khu B', 'Hoat_Dong'),
(37, 3, '13', 'Tầng 2 - Khu B', 'Hoat_Dong'),
(38, 3, '14', 'Tầng 2 - Khu B', 'Hoat_Dong'),
(39, 3, '15', 'Tầng 2 - Khu B', 'Hoat_Dong'),
(40, 3, '16', 'Tầng 2 - Khu B', 'Hoat_Dong'),
(41, 4, '13', 'Tầng 2 - Khu quốc tế', 'Hoat_Dong'),
(42, 4, '14', 'Tầng 2 - Khu quốc tế', 'Hoat_Dong'),
(43, 4, '15', 'Tầng 2 - Khu quốc tế', 'Hoat_Dong'),
(44, 4, '16', 'Tầng 2 - Khu quốc tế', 'Hoat_Dong'),
(45, 4, '17', 'Tầng 2 - Khu quốc tế', 'Hoat_Dong'),
(46, 4, '18', 'Tầng 2 - Khu quốc tế', 'Hoat_Dong'),
(47, 4, '19', 'Tầng 2 - Khu quốc tế', 'Hoat_Dong'),
(48, 4, '20', 'Tầng 2 - Khu quốc tế', 'Hoat_Dong'),
(49, 4, '21', 'Tầng 2 - Khu quốc tế', 'Hoat_Dong'),
(50, 4, '22', 'Tầng 2 - Khu quốc tế', 'Hoat_Dong'),
(51, 4, '23', 'Tầng 2 - Khu quốc tế', 'Hoat_Dong'),
(52, 4, '24', 'Tầng 2 - Khu quốc tế', 'Bao_Tri'),
(53, 5, '1', 'Khu quốc nội', 'Hoat_Dong'),
(54, 5, '2', 'Khu quốc nội', 'Hoat_Dong'),
(55, 5, '3', 'Khu quốc nội', 'Hoat_Dong'),
(56, 5, '4', 'Khu quốc nội', 'Hoat_Dong'),
(57, 5, '5', 'Khu quốc nội', 'Hoat_Dong'),
(58, 5, '6', 'Khu quốc nội', 'Hoat_Dong'),
(59, 5, '7', 'Khu quốc nội', 'Hoat_Dong'),
(60, 5, '8', 'Khu quốc nội', 'Hoat_Dong'),
(61, 6, '9', 'Khu quốc tế', 'Hoat_Dong'),
(62, 6, '10', 'Khu quốc tế', 'Hoat_Dong'),
(63, 6, '11', 'Khu quốc tế', 'Hoat_Dong'),
(64, 6, '12', 'Khu quốc tế', 'Hoat_Dong'),
(65, 6, '13', 'Khu quốc tế', 'Hoat_Dong'),
(66, 6, '14', 'Khu quốc tế', 'Hoat_Dong'),
(67, 6, '15', 'Khu quốc tế', 'Hoat_Dong'),
(68, 6, '16', 'Khu quốc tế', 'Bao_Tri'),
(69, 7, '1', 'Khu quốc nội', 'Hoat_Dong'),
(70, 7, '2', 'Khu quốc nội', 'Hoat_Dong'),
(71, 7, '3', 'Khu quốc nội', 'Hoat_Dong'),
(72, 7, '4', 'Khu quốc nội', 'Hoat_Dong'),
(73, 7, '5', 'Khu quốc nội', 'Hoat_Dong'),
(74, 7, '6', 'Khu quốc nội', 'Hoat_Dong'),
(75, 8, '7', 'Khu quốc tế', 'Hoat_Dong'),
(76, 8, '8', 'Khu quốc tế', 'Hoat_Dong'),
(77, 8, '9', 'Khu quốc tế', 'Hoat_Dong'),
(78, 8, '10', 'Khu quốc tế', 'Hoat_Dong'),
(79, 8, '11', 'Khu quốc tế', 'Hoat_Dong'),
(80, 8, '12', 'Khu quốc tế', 'Hoat_Dong'),
(81, 8, '13', 'Khu quốc tế', 'Hoat_Dong'),
(82, 8, '14', 'Khu quốc tế', 'Hoat_Dong'),
(83, 9, '1', 'Khu quốc nội', 'Hoat_Dong'),
(84, 9, '2', 'Khu quốc nội', 'Hoat_Dong'),
(85, 9, '3', 'Khu quốc nội', 'Hoat_Dong'),
(86, 9, '4', 'Khu quốc nội', 'Hoat_Dong'),
(87, 9, '5', 'Khu quốc nội', 'Hoat_Dong'),
(88, 9, '6', 'Khu quốc nội', 'Hoat_Dong'),
(89, 9, '7', 'Khu quốc tế', 'Hoat_Dong'),
(90, 9, '8', 'Khu quốc tế', 'Hoat_Dong'),
(91, 9, '9', 'Khu quốc tế', 'Hoat_Dong'),
(92, 9, '10', 'Khu quốc tế', 'Hoat_Dong'),
(93, 9, '11', 'Khu quốc tế', 'Hoat_Dong'),
(94, 9, '12', 'Khu quốc tế', 'Hoat_Dong'),
(95, 11, '1', 'Khu khởi hành', 'Hoat_Dong'),
(96, 11, '2', 'Khu khởi hành', 'Hoat_Dong'),
(97, 11, '3', 'Khu khởi hành', 'Hoat_Dong'),
(98, 11, '4', 'Khu khởi hành', 'Hoat_Dong'),
(99, 11, '5', 'Khu khởi hành', 'Hoat_Dong'),
(100, 11, '6', 'Khu khởi hành', 'Hoat_Dong'),
(101, 11, '7', 'Khu khởi hành', 'Hoat_Dong'),
(102, 11, '8', 'Khu khởi hành', 'Bao_Tri'),
(103, 12, '1', 'Khu khởi hành', 'Hoat_Dong'),
(104, 12, '2', 'Khu khởi hành', 'Hoat_Dong'),
(105, 12, '3', 'Khu khởi hành', 'Hoat_Dong'),
(106, 12, '4', 'Khu khởi hành', 'Hoat_Dong'),
(107, 12, '5', 'Khu khởi hành', 'Hoat_Dong'),
(108, 12, '6', 'Khu khởi hành', 'Hoat_Dong'),
(109, 13, '1', 'Khu khởi hành', 'Hoat_Dong'),
(110, 13, '2', 'Khu khởi hành', 'Hoat_Dong'),
(111, 13, '3', 'Khu khởi hành', 'Hoat_Dong'),
(112, 13, '4', 'Khu khởi hành', 'Hoat_Dong'),
(113, 14, '1', 'Khu khởi hành', 'Hoat_Dong'),
(114, 14, '2', 'Khu khởi hành', 'Hoat_Dong'),
(115, 14, '3', 'Khu khởi hành', 'Hoat_Dong'),
(116, 14, '4', 'Khu khởi hành', 'Hoat_Dong'),
(117, 14, '5', 'Khu khởi hành', 'Hoat_Dong'),
(118, 15, '1', 'Khu khởi hành', 'Hoat_Dong'),
(119, 15, '2', 'Khu khởi hành', 'Hoat_Dong'),
(120, 15, '3', 'Khu khởi hành', 'Hoat_Dong'),
(121, 16, '1', 'Khu khởi hành', 'Hoat_Dong'),
(122, 16, '2', 'Khu khởi hành', 'Hoat_Dong'),
(123, 16, '3', 'Khu khởi hành', 'Hoat_Dong'),
(124, 16, '4', 'Khu khởi hành', 'Hoat_Dong'),
(125, 17, '1', 'Khu khởi hành', 'Hoat_Dong'),
(126, 17, '2', 'Khu khởi hành', 'Hoat_Dong'),
(127, 17, '3', 'Khu khởi hành', 'Hoat_Dong'),
(128, 17, '4', 'Khu khởi hành', 'Hoat_Dong'),
(129, 18, '1', 'Khu khởi hành', 'Hoat_Dong'),
(130, 18, '2', 'Khu khởi hành', 'Hoat_Dong'),
(131, 18, '3', 'Khu khởi hành', 'Hoat_Dong'),
(132, 18, '4', 'Khu khởi hành', 'Hoat_Dong'),
(133, 19, '1', 'Khu khởi hành', 'Hoat_Dong'),
(134, 19, '2', 'Khu khởi hành', 'Hoat_Dong'),
(135, 19, '3', 'Khu khởi hành', 'Hoat_Dong'),
(136, 19, '4', 'Khu khởi hành', 'Hoat_Dong'),
(137, 19, '5', 'Khu khởi hành', 'Bao_Tri'),
(138, 20, '1', 'Khu khởi hành', 'Hoat_Dong'),
(139, 20, '2', 'Khu khởi hành', 'Hoat_Dong'),
(140, 20, '3', 'Khu khởi hành', 'Bao_Tri');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `don_vi_mat_dat`
--

CREATE TABLE `don_vi_mat_dat` (
  `id` int(11) NOT NULL,
  `ma_don_vi` varchar(10) NOT NULL,
  `ten_don_vi` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `don_vi_mat_dat`
--

INSERT INTO `don_vi_mat_dat` (`id`, `ma_don_vi`, `ten_don_vi`) VALUES
(1, 'VIAGS', 'Công ty Dịch vụ mặt đất sân bay Việt Nam'),
(2, 'SAGS', 'Công ty Phục vụ mặt đất Sài Gòn');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `hang_hang_khong`
--

CREATE TABLE `hang_hang_khong` (
  `id` int(11) NOT NULL,
  `ma_iata` varchar(2) NOT NULL,
  `ten_hang` varchar(100) NOT NULL,
  `quoc_gia` varchar(50) DEFAULT 'Viet Nam'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `hang_hang_khong`
--

INSERT INTO `hang_hang_khong` (`id`, `ma_iata`, `ten_hang`, `quoc_gia`) VALUES
(1, 'VN', 'Vietnam Airlines', 'Viet Nam'),
(2, 'VJ', 'Vietjet Air', 'Viet Nam'),
(3, 'QH', 'Bamboo Airways', 'Viet Nam');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `hoa_don`
--

CREATE TABLE `hoa_don` (
  `id` int(11) NOT NULL,
  `id_chuyen_bay` int(11) NOT NULL,
  `id_hang_hang_khong` int(11) NOT NULL,
  `phi_cat_ha_canh` decimal(15,2) DEFAULT 0.00,
  `phi_do_tau_bay` decimal(15,2) DEFAULT 0.00,
  `phi_phuc_vu_hk` decimal(15,2) DEFAULT 0.00,
  `tong_tien` decimal(15,2) DEFAULT 0.00,
  `ngay_xuat` datetime DEFAULT current_timestamp(),
  `trang_thai_thanh_toan` enum('Chua_Thanh_Toan','Da_Thanh_Toan') DEFAULT 'Chua_Thanh_Toan'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dữ liệu bảng `hoa_don` (bổ sung từ bản gốc)
--

INSERT INTO `hoa_don` (`id`, `id_chuyen_bay`, `id_hang_hang_khong`, `phi_cat_ha_canh`, `phi_do_tau_bay`, `phi_phuc_vu_hk`, `tong_tien`, `ngay_xuat`, `trang_thai_thanh_toan`) VALUES
(1, 1, 1, 1500000.00, 25400000.00, 29500000.00, 56400000.00, '2026-10-18 19:45:46', 'Chua_Thanh_Toan');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `lich_bay_mua`
--

CREATE TABLE `lich_bay_mua` (
  `id` int(11) NOT NULL,
  `so_hieu` varchar(20) NOT NULL,
  `id_hang_hang_khong` int(11) NOT NULL,
  `id_san_bay_di` int(11) NOT NULL,
  `id_san_bay_den` int(11) NOT NULL,
  `tan_suat_tuan` varchar(50) NOT NULL,
  `tu_ngay` date NOT NULL,
  `den_ngay` date NOT NULL,
  `trang_thai` varchar(20) NOT NULL DEFAULT 'Dang_Mo',
  `ly_do` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `lich_bay_mua`
--

INSERT INTO `lich_bay_mua` (`id`, `so_hieu`, `id_hang_hang_khong`, `id_san_bay_di`, `id_san_bay_den`, `tan_suat_tuan`, `tu_ngay`, `den_ngay`, `trang_thai`, `ly_do`) VALUES
(1, 'VN210', 1, 2, 1, 'Hằng ngày', '2026-10-01', '2026-12-31', 'Tam_Dung', 'Thời tiết xấu'),
(2, 'VJ777', 2, 1, 3, 'Thứ 2, Thứ 4, Thứ 6', '2026-10-01', '2026-12-31', 'Da_Dong', 'test'),
(7, 'VN123', 3, 3, 4, 'Thứ 2, Thứ 4, Thứ 6', '2026-10-01', '2026-10-31', 'Da_Dong', 'Bảo trì'),
(16, 'VN111', 3, 12, 2, 'Thứ 2, Thứ 3, Thứ 4', '2026-10-28', '2026-10-31', 'Dang_Mo', NULL);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `lich_su_chuyen_bay`
--

CREATE TABLE `lich_su_chuyen_bay` (
  `id` int(11) NOT NULL,
  `id_chuyen_bay` int(11) NOT NULL,
  `so_hieu` varchar(20) NOT NULL,
  `id_hang_hang_khong` int(11) DEFAULT NULL,
  `ten_hang` varchar(100) DEFAULT NULL,
  `id_san_bay_di` int(11) DEFAULT NULL,
  `ma_di` varchar(3) DEFAULT NULL,
  `ten_san_bay_di` varchar(120) DEFAULT NULL,
  `id_san_bay_den` int(11) DEFAULT NULL,
  `ma_den` varchar(3) DEFAULT NULL,
  `ten_san_bay_den` varchar(120) DEFAULT NULL,
  `gio_cat_canh` datetime NOT NULL,
  `gio_ha_canh` datetime NOT NULL,
  `tong_gio_bay` decimal(8,2) NOT NULL DEFAULT 0.00,
  `so_hanh_khach` int(11) NOT NULL DEFAULT 0,
  `khoi_luong_hang_kg` decimal(12,2) NOT NULL DEFAULT 0.00,
  `thoi_diem_luu` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `lich_su_chuyen_bay`
--

INSERT INTO `lich_su_chuyen_bay` (`id`, `id_chuyen_bay`, `so_hieu`, `id_hang_hang_khong`, `ten_hang`, `id_san_bay_di`, `ma_di`, `ten_san_bay_di`, `id_san_bay_den`, `ma_den`, `ten_san_bay_den`, `gio_cat_canh`, `gio_ha_canh`, `tong_gio_bay`, `so_hanh_khach`, `khoi_luong_hang_kg`, `thoi_diem_luu`) VALUES
(1, 1, 'VN210', 1, 'Vietnam Airlines', 2, 'SGN', 'Cảng hàng không quốc tế Tân Sơn Nhất', 1, 'HAN', 'Cảng hàng không quốc tế Nội Bài', '2026-10-20 08:00:00', '2026-10-20 08:05:00', 0.08, 295, 12500.00, '2026-10-27 23:18:24'),
(2, 42, 'VN210', 1, 'Vietnam Airlines', 2, 'SGN', 'Cảng hàng không quốc tế Tân Sơn Nhất', 1, 'HAN', 'Cảng hàng không quốc tế Nội Bài', '2026-10-27 08:00:00', '2026-10-27 09:30:00', 1.50, 0, 0.00, '2026-10-27 23:18:24'),
(3, 90, 'VN210', 1, 'Vietnam Airlines', 2, 'SGN', 'Cảng hàng không quốc tế Tân Sơn Nhất', 1, 'HAN', 'Cảng hàng không quốc tế Nội Bài', '2026-10-01 08:00:00', '2026-10-01 09:30:00', 1.50, 0, 0.00, '2026-10-27 23:18:24'),
(4, 91, 'VN210', 1, 'Vietnam Airlines', 2, 'SGN', 'Cảng hàng không quốc tế Tân Sơn Nhất', 1, 'HAN', 'Cảng hàng không quốc tế Nội Bài', '2026-10-02 08:00:00', '2026-10-02 09:30:00', 1.50, 0, 0.00, '2026-10-27 23:18:24'),
(5, 92, 'VN210', 1, 'Vietnam Airlines', 2, 'SGN', 'Cảng hàng không quốc tế Tân Sơn Nhất', 1, 'HAN', 'Cảng hàng không quốc tế Nội Bài', '2026-10-03 08:00:00', '2026-10-03 09:30:00', 1.50, 0, 0.00, '2026-10-27 23:18:24'),
(6, 93, 'VN210', 1, 'Vietnam Airlines', 2, 'SGN', 'Cảng hàng không quốc tế Tân Sơn Nhất', 1, 'HAN', 'Cảng hàng không quốc tế Nội Bài', '2026-10-04 08:00:00', '2026-10-04 09:30:00', 1.50, 0, 0.00, '2026-10-27 23:18:24'),
(7, 94, 'VN210', 1, 'Vietnam Airlines', 2, 'SGN', 'Cảng hàng không quốc tế Tân Sơn Nhất', 1, 'HAN', 'Cảng hàng không quốc tế Nội Bài', '2026-10-05 08:00:00', '2026-10-05 09:30:00', 1.50, 0, 0.00, '2026-10-27 23:18:24'),
(8, 95, 'VN210', 1, 'Vietnam Airlines', 2, 'SGN', 'Cảng hàng không quốc tế Tân Sơn Nhất', 1, 'HAN', 'Cảng hàng không quốc tế Nội Bài', '2026-10-06 08:00:00', '2026-10-06 09:30:00', 1.50, 0, 0.00, '2026-10-27 23:18:24'),
(9, 96, 'VN210', 1, 'Vietnam Airlines', 2, 'SGN', 'Cảng hàng không quốc tế Tân Sơn Nhất', 1, 'HAN', 'Cảng hàng không quốc tế Nội Bài', '2026-10-07 08:00:00', '2026-10-07 09:30:00', 1.50, 0, 0.00, '2026-10-27 23:18:24'),
(10, 97, 'VN210', 1, 'Vietnam Airlines', 2, 'SGN', 'Cảng hàng không quốc tế Tân Sơn Nhất', 1, 'HAN', 'Cảng hàng không quốc tế Nội Bài', '2026-10-08 08:00:00', '2026-10-08 09:30:00', 1.50, 0, 0.00, '2026-10-27 23:18:24'),
(11, 98, 'VN210', 1, 'Vietnam Airlines', 2, 'SGN', 'Cảng hàng không quốc tế Tân Sơn Nhất', 1, 'HAN', 'Cảng hàng không quốc tế Nội Bài', '2026-10-09 08:00:00', '2026-10-09 09:30:00', 1.50, 0, 0.00, '2026-10-27 23:18:24'),
(12, 99, 'VN210', 1, 'Vietnam Airlines', 2, 'SGN', 'Cảng hàng không quốc tế Tân Sơn Nhất', 1, 'HAN', 'Cảng hàng không quốc tế Nội Bài', '2026-10-10 08:00:00', '2026-10-10 09:30:00', 1.50, 0, 0.00, '2026-10-27 23:18:24'),
(13, 100, 'VN210', 1, 'Vietnam Airlines', 2, 'SGN', 'Cảng hàng không quốc tế Tân Sơn Nhất', 1, 'HAN', 'Cảng hàng không quốc tế Nội Bài', '2026-10-11 08:00:00', '2026-10-11 09:30:00', 1.50, 0, 0.00, '2026-10-27 23:18:24'),
(14, 101, 'VN210', 1, 'Vietnam Airlines', 2, 'SGN', 'Cảng hàng không quốc tế Tân Sơn Nhất', 1, 'HAN', 'Cảng hàng không quốc tế Nội Bài', '2026-10-12 08:00:00', '2026-10-12 09:30:00', 1.50, 0, 0.00, '2026-10-27 23:18:24'),
(15, 102, 'VN210', 1, 'Vietnam Airlines', 2, 'SGN', 'Cảng hàng không quốc tế Tân Sơn Nhất', 1, 'HAN', 'Cảng hàng không quốc tế Nội Bài', '2026-10-13 08:00:00', '2026-10-13 09:30:00', 1.50, 0, 0.00, '2026-10-27 23:18:24'),
(16, 103, 'VN210', 1, 'Vietnam Airlines', 2, 'SGN', 'Cảng hàng không quốc tế Tân Sơn Nhất', 1, 'HAN', 'Cảng hàng không quốc tế Nội Bài', '2026-10-14 08:00:00', '2026-10-14 09:30:00', 1.50, 0, 0.00, '2026-10-27 23:18:24'),
(17, 104, 'VN210', 1, 'Vietnam Airlines', 2, 'SGN', 'Cảng hàng không quốc tế Tân Sơn Nhất', 1, 'HAN', 'Cảng hàng không quốc tế Nội Bài', '2026-10-15 08:00:00', '2026-10-15 09:30:00', 1.50, 0, 0.00, '2026-10-27 23:18:24'),
(18, 105, 'VN210', 1, 'Vietnam Airlines', 2, 'SGN', 'Cảng hàng không quốc tế Tân Sơn Nhất', 1, 'HAN', 'Cảng hàng không quốc tế Nội Bài', '2026-10-16 08:00:00', '2026-10-16 09:30:00', 1.50, 0, 0.00, '2026-10-27 23:18:24'),
(19, 106, 'VN210', 1, 'Vietnam Airlines', 2, 'SGN', 'Cảng hàng không quốc tế Tân Sơn Nhất', 1, 'HAN', 'Cảng hàng không quốc tế Nội Bài', '2026-10-17 08:00:00', '2026-10-17 09:30:00', 1.50, 0, 0.00, '2026-10-27 23:18:24'),
(20, 107, 'VN210', 1, 'Vietnam Airlines', 2, 'SGN', 'Cảng hàng không quốc tế Tân Sơn Nhất', 1, 'HAN', 'Cảng hàng không quốc tế Nội Bài', '2026-10-18 08:00:00', '2026-10-18 09:30:00', 1.50, 0, 0.00, '2026-10-27 23:18:24'),
(21, 108, 'VN210', 1, 'Vietnam Airlines', 2, 'SGN', 'Cảng hàng không quốc tế Tân Sơn Nhất', 1, 'HAN', 'Cảng hàng không quốc tế Nội Bài', '2026-10-19 08:00:00', '2026-10-19 09:30:00', 1.50, 0, 0.00, '2026-10-27 23:18:24'),
(22, 109, 'VN210', 1, 'Vietnam Airlines', 2, 'SGN', 'Cảng hàng không quốc tế Tân Sơn Nhất', 1, 'HAN', 'Cảng hàng không quốc tế Nội Bài', '2026-10-22 08:00:00', '2026-10-22 09:30:00', 1.50, 0, 0.00, '2026-10-27 23:18:24'),
(23, 110, 'VN210', 1, 'Vietnam Airlines', 2, 'SGN', 'Cảng hàng không quốc tế Tân Sơn Nhất', 1, 'HAN', 'Cảng hàng không quốc tế Nội Bài', '2026-10-23 08:00:00', '2026-10-23 09:30:00', 1.50, 0, 0.00, '2026-10-27 23:18:24'),
(24, 111, 'VN210', 1, 'Vietnam Airlines', 2, 'SGN', 'Cảng hàng không quốc tế Tân Sơn Nhất', 1, 'HAN', 'Cảng hàng không quốc tế Nội Bài', '2026-10-24 08:00:00', '2026-10-24 09:30:00', 1.50, 0, 0.00, '2026-10-27 23:18:24'),
(25, 112, 'VN210', 1, 'Vietnam Airlines', 2, 'SGN', 'Cảng hàng không quốc tế Tân Sơn Nhất', 1, 'HAN', 'Cảng hàng không quốc tế Nội Bài', '2026-10-25 08:00:00', '2026-10-25 09:30:00', 1.50, 0, 0.00, '2026-10-27 23:18:24'),
(26, 113, 'VN210', 1, 'Vietnam Airlines', 2, 'SGN', 'Cảng hàng không quốc tế Tân Sơn Nhất', 1, 'HAN', 'Cảng hàng không quốc tế Nội Bài', '2026-10-26 08:00:00', '2026-10-26 09:30:00', 1.50, 0, 0.00, '2026-10-27 23:18:24'),
(27, 3, 'VJ777', 2, 'Vietjet Air', 1, 'HAN', 'Cảng hàng không quốc tế Nội Bài', 3, 'CXR', 'Cảng hàng không quốc tế Cam Ranh', '2026-10-20 14:30:00', '2026-10-27 15:53:00', 169.38, 210, 5000.00, '2026-10-27 23:18:24'),
(28, 54, 'VJ777', 2, 'Vietjet Air', 1, 'HAN', 'Cảng hàng không quốc tế Nội Bài', 3, 'CXR', 'Cảng hàng không quốc tế Cam Ranh', '2026-10-02 08:00:00', '2026-10-02 09:30:00', 1.50, 0, 0.00, '2026-10-27 23:18:24'),
(29, 55, 'VJ777', 2, 'Vietjet Air', 1, 'HAN', 'Cảng hàng không quốc tế Nội Bài', 3, 'CXR', 'Cảng hàng không quốc tế Cam Ranh', '2026-10-05 08:00:00', '2026-10-05 09:30:00', 1.50, 0, 0.00, '2026-10-27 23:18:24'),
(30, 56, 'VJ777', 2, 'Vietjet Air', 1, 'HAN', 'Cảng hàng không quốc tế Nội Bài', 3, 'CXR', 'Cảng hàng không quốc tế Cam Ranh', '2026-10-07 08:00:00', '2026-10-07 09:30:00', 1.50, 0, 0.00, '2026-10-27 23:18:24'),
(31, 57, 'VJ777', 2, 'Vietjet Air', 1, 'HAN', 'Cảng hàng không quốc tế Nội Bài', 3, 'CXR', 'Cảng hàng không quốc tế Cam Ranh', '2026-10-09 08:00:00', '2026-10-09 09:30:00', 1.50, 0, 0.00, '2026-10-27 23:18:24'),
(32, 58, 'VJ777', 2, 'Vietjet Air', 1, 'HAN', 'Cảng hàng không quốc tế Nội Bài', 3, 'CXR', 'Cảng hàng không quốc tế Cam Ranh', '2026-10-12 08:00:00', '2026-10-12 09:30:00', 1.50, 0, 0.00, '2026-10-27 23:18:24'),
(33, 59, 'VJ777', 2, 'Vietjet Air', 1, 'HAN', 'Cảng hàng không quốc tế Nội Bài', 3, 'CXR', 'Cảng hàng không quốc tế Cam Ranh', '2026-10-14 08:00:00', '2026-10-14 09:30:00', 1.50, 0, 0.00, '2026-10-27 23:18:24'),
(34, 60, 'VJ777', 2, 'Vietjet Air', 1, 'HAN', 'Cảng hàng không quốc tế Nội Bài', 3, 'CXR', 'Cảng hàng không quốc tế Cam Ranh', '2026-10-16 08:00:00', '2026-10-16 09:30:00', 1.50, 0, 0.00, '2026-10-27 23:18:24'),
(35, 61, 'VJ777', 2, 'Vietjet Air', 1, 'HAN', 'Cảng hàng không quốc tế Nội Bài', 3, 'CXR', 'Cảng hàng không quốc tế Cam Ranh', '2026-10-19 08:00:00', '2026-10-19 09:30:00', 1.50, 0, 0.00, '2026-10-27 23:18:24'),
(36, 62, 'VJ777', 2, 'Vietjet Air', 1, 'HAN', 'Cảng hàng không quốc tế Nội Bài', 3, 'CXR', 'Cảng hàng không quốc tế Cam Ranh', '2026-10-21 08:00:00', '2026-10-21 09:30:00', 1.50, 0, 0.00, '2026-10-27 23:18:24'),
(37, 63, 'VJ777', 2, 'Vietjet Air', 1, 'HAN', 'Cảng hàng không quốc tế Nội Bài', 3, 'CXR', 'Cảng hàng không quốc tế Cam Ranh', '2026-10-23 08:00:00', '2026-10-23 09:30:00', 1.50, 0, 0.00, '2026-10-27 23:18:24'),
(38, 64, 'VJ777', 2, 'Vietjet Air', 1, 'HAN', 'Cảng hàng không quốc tế Nội Bài', 3, 'CXR', 'Cảng hàng không quốc tế Cam Ranh', '2026-10-26 08:00:00', '2026-10-26 09:30:00', 1.50, 0, 0.00, '2026-10-27 23:18:24'),
(39, 6, 'VN0000', 3, 'Bamboo Airways', 3, 'CXR', 'Cảng hàng không quốc tế Cam Ranh', 4, 'DAD', 'Cảng hàng không quốc tế Đà Nẵng', '2026-10-27 08:55:00', '2026-10-27 09:56:00', 1.02, 1000, 10000.00, '2026-10-27 23:18:24'),
(40, 13, 'VN1211', 3, 'Bamboo Airways', 10, 'VDH', 'Cảng hàng không Đồng Hới', 15, 'VKG', 'Cảng hàng không Rạch Giá', '2026-10-27 09:10:00', '2026-10-27 20:10:00', 11.00, 230, 0.00, '2026-10-27 23:18:24'),
(41, 15, 'VN1112', 3, 'Bamboo Airways', 10, 'VDH', 'Cảng hàng không Đồng Hới', 7, 'VCA', 'Cảng hàng không quốc tế Cần Thơ', '2026-10-27 09:19:00', '2026-10-27 10:19:00', 1.00, 213, 0.00, '2026-10-27 23:18:24'),
(42, 77, 'VN123', 3, 'Bamboo Airways', 3, 'CXR', 'Cảng hàng không quốc tế Cam Ranh', 4, 'DAD', 'Cảng hàng không quốc tế Đà Nẵng', '2026-10-02 08:00:00', '2026-10-02 09:30:00', 1.50, 0, 0.00, '2026-10-27 23:18:24'),
(43, 78, 'VN123', 3, 'Bamboo Airways', 3, 'CXR', 'Cảng hàng không quốc tế Cam Ranh', 4, 'DAD', 'Cảng hàng không quốc tế Đà Nẵng', '2026-10-05 08:00:00', '2026-10-05 09:30:00', 1.50, 0, 0.00, '2026-10-27 23:18:24'),
(44, 79, 'VN123', 3, 'Bamboo Airways', 3, 'CXR', 'Cảng hàng không quốc tế Cam Ranh', 4, 'DAD', 'Cảng hàng không quốc tế Đà Nẵng', '2026-10-07 08:00:00', '2026-10-07 09:30:00', 1.50, 0, 0.00, '2026-10-27 23:18:24'),
(45, 80, 'VN123', 3, 'Bamboo Airways', 3, 'CXR', 'Cảng hàng không quốc tế Cam Ranh', 4, 'DAD', 'Cảng hàng không quốc tế Đà Nẵng', '2026-10-09 08:00:00', '2026-10-09 09:30:00', 1.50, 0, 0.00, '2026-10-27 23:18:24'),
(46, 81, 'VN123', 3, 'Bamboo Airways', 3, 'CXR', 'Cảng hàng không quốc tế Cam Ranh', 4, 'DAD', 'Cảng hàng không quốc tế Đà Nẵng', '2026-10-12 08:00:00', '2026-10-12 09:30:00', 1.50, 0, 0.00, '2026-10-27 23:18:24'),
(47, 82, 'VN123', 3, 'Bamboo Airways', 3, 'CXR', 'Cảng hàng không quốc tế Cam Ranh', 4, 'DAD', 'Cảng hàng không quốc tế Đà Nẵng', '2026-10-14 08:00:00', '2026-10-14 09:30:00', 1.50, 0, 0.00, '2026-10-27 23:18:24'),
(48, 83, 'VN123', 3, 'Bamboo Airways', 3, 'CXR', 'Cảng hàng không quốc tế Cam Ranh', 4, 'DAD', 'Cảng hàng không quốc tế Đà Nẵng', '2026-10-16 08:00:00', '2026-10-16 09:30:00', 1.50, 0, 0.00, '2026-10-27 23:18:24'),
(49, 84, 'VN123', 3, 'Bamboo Airways', 3, 'CXR', 'Cảng hàng không quốc tế Cam Ranh', 4, 'DAD', 'Cảng hàng không quốc tế Đà Nẵng', '2026-10-19 08:00:00', '2026-10-19 09:30:00', 1.50, 0, 0.00, '2026-10-27 23:18:24'),
(50, 85, 'VN123', 3, 'Bamboo Airways', 3, 'CXR', 'Cảng hàng không quốc tế Cam Ranh', 4, 'DAD', 'Cảng hàng không quốc tế Đà Nẵng', '2026-10-21 08:00:00', '2026-10-21 09:30:00', 1.50, 0, 0.00, '2026-10-27 23:18:24'),
(51, 86, 'VN123', 3, 'Bamboo Airways', 3, 'CXR', 'Cảng hàng không quốc tế Cam Ranh', 4, 'DAD', 'Cảng hàng không quốc tế Đà Nẵng', '2026-10-23 08:00:00', '2026-10-23 09:30:00', 1.50, 0, 0.00, '2026-10-27 23:18:24'),
(52, 87, 'VN123', 3, 'Bamboo Airways', 3, 'CXR', 'Cảng hàng không quốc tế Cam Ranh', 4, 'DAD', 'Cảng hàng không quốc tế Đà Nẵng', '2026-10-26 08:00:00', '2026-10-26 09:30:00', 1.50, 0, 0.00, '2026-10-27 23:18:24'),
(64, 4, 'VN1234', 3, 'Bamboo Airways', 1, 'HAN', 'Cảng hàng không quốc tế Nội Bài', 12, 'VCL', 'Cảng hàng không Chu Lai', '2026-10-27 09:44:00', '2026-10-27 07:44:00', 0.00, 10, 0.00, '2026-10-27 23:20:25'),
(66, 160, 'VN0001', 3, 'Bamboo Airways', 3, 'CXR', 'Cảng hàng không quốc tế Cam Ranh', 4, 'DAD', 'Cảng hàng không quốc tế Đà Nẵng', '2026-10-27 23:17:00', '2026-10-27 23:27:00', 0.17, 230, 0.00, '2026-10-27 23:27:06'),
(67, 43, 'VN210', 1, 'Vietnam Airlines', 2, 'SGN', 'Cảng hàng không quốc tế Tân Sơn Nhất', 1, 'HAN', 'Cảng hàng không quốc tế Nội Bài', '2026-10-28 08:00:00', '2026-10-28 09:30:00', 1.50, 0, 0.00, '2026-10-28 13:45:10'),
(68, 39, 'VJ777', 2, 'Vietjet Air', 1, 'HAN', 'Cảng hàng không quốc tế Nội Bài', 3, 'CXR', 'Cảng hàng không quốc tế Cam Ranh', '2026-10-28 08:00:00', '2026-10-28 09:30:00', 1.50, 0, 0.00, '2026-10-28 13:45:10'),
(69, 88, 'VN123', 3, 'Bamboo Airways', 3, 'CXR', 'Cảng hàng không quốc tế Cam Ranh', 4, 'DAD', 'Cảng hàng không quốc tế Đà Nẵng', '2026-10-28 08:00:00', '2026-10-28 09:30:00', 1.50, 0, 0.00, '2026-10-28 13:45:10'),
(70, 2, 'VN210', 1, 'Vietnam Airlines', 2, 'SGN', 'Cảng hàng không quốc tế Tân Sơn Nhất', 1, 'HAN', 'Cảng hàng không quốc tế Nội Bài', '2026-10-21 08:00:00', '2026-10-27 09:55:00', 145.92, 1000, 0.00, '2026-10-28 14:07:27'),
(71, 7, 'VN0000', 3, 'Bamboo Airways', 3, 'CXR', 'Cảng hàng không quốc tế Cam Ranh', 4, 'DAD', 'Cảng hàng không quốc tế Đà Nẵng', '2026-10-27 06:00:00', '2026-10-27 08:00:00', 2.00, 1000, 0.00, '2026-10-28 14:07:29'),
(72, 166, 'TEST0000', 3, 'Bamboo Airways', 8, 'DIN', 'Cảng hàng không Điện Biên Phủ', 11, 'TBB', 'Cảng hàng không Tuy Hòa', '2026-10-28 14:06:00', '2026-10-28 14:20:00', 0.23, 230, 89000.00, '2026-10-28 14:20:09'),
(73, 174, 'TEST0101', 3, 'Bamboo Airways', 7, 'VCA', 'Cảng hàng không quốc tế Cần Thơ', 12, 'VCL', 'Cảng hàng không Chu Lai', '2026-10-28 14:29:48', '2026-10-28 14:35:48', 0.10, 123, 130.00, '2026-10-28 14:36:12'),
(74, 194, 'TST07', 1, 'Vietnam Airlines', 10, 'VDH', 'Cảng hàng không Đồng Hới', 1, 'HAN', 'Cảng hàng không quốc tế Nội Bài', '2026-10-28 14:22:39', '2026-10-28 15:52:39', 1.50, 264, 3200.00, '2026-10-29 17:59:56'),
(75, 195, 'TST08', 2, 'Vietjet Air', 11, 'TBB', 'Cảng hàng không Tuy Hòa', 2, 'SGN', 'Cảng hàng không quốc tế Tân Sơn Nhất', '2026-10-28 14:18:39', '2026-10-28 15:48:39', 1.50, 121, 1300.00, '2026-10-29 17:59:56'),
(76, 187, 'VN111', 3, 'Bamboo Airways', 12, 'VCL', 'Cảng hàng không Chu Lai', 2, 'SGN', 'Cảng hàng không quốc tế Tân Sơn Nhất', '2026-10-28 14:50:00', '2026-10-28 16:20:00', 1.50, 0, 0.00, '2026-10-29 17:59:56'),
(77, 196, 'VN1123', 3, 'Bamboo Airways', 15, 'VKG', 'Cảng hàng không Rạch Giá', 11, 'TBB', 'Cảng hàng không Tuy Hòa', '2026-10-28 15:08:00', '2026-10-28 15:12:00', 0.07, 1, 0.00, '2026-10-29 17:59:56'),
(78, 197, 'TEST1123', 3, 'Bamboo Airways', 10, 'VDH', 'Cảng hàng không Đồng Hới', 7, 'VCA', 'Cảng hàng không quốc tế Cần Thơ', '2026-10-29 21:32:04', '2026-10-29 21:36:00', 0.05, 100, 3000.00, '2026-10-29 21:38:11'),
(79, 198, 'TEST1213', 3, 'Bamboo Airways', 4, 'DAD', 'Cảng hàng không quốc tế Đà Nẵng', 14, 'UIH', 'Cảng hàng không Phù Cát', '2026-10-29 22:13:17', '2026-10-29 22:13:17', 0.00, 131, 230.00, '2026-10-29 22:13:17'),
(80, 199, 'TEST1231', 2, 'Vietjet Air', 12, 'VCL', 'Cảng hàng không Chu Lai', 7, 'VCA', 'Cảng hàng không quốc tế Cần Thơ', '2026-10-29 22:22:11', '2026-10-29 22:22:11', 0.00, 122, 3000.00, '2026-10-29 22:22:11'),
(81, 200, 'VN1211', 3, 'Bamboo Airways', 15, 'VKG', 'Cảng hàng không Rạch Giá', 10, 'VDH', 'Cảng hàng không Đồng Hới', '2026-10-29 22:34:14', '2026-10-29 22:34:00', 0.00, 123, 45010.00, '2026-10-29 22:34:23'),
(82, 201, 'VJ1231', 3, 'Bamboo Airways', 6, 'PQC', 'Cảng hàng không quốc tế Phú Quốc', 11, 'TBB', 'Cảng hàng không Tuy Hòa', '2026-10-29 22:46:02', '2026-10-29 22:50:00', 0.05, 123, 23000.00, '2026-10-29 22:50:01'),
(83, 202, 'VN1275', 3, 'Bamboo Airways', 2, 'SGN', 'Cảng hàng không quốc tế Tân Sơn Nhất', 6, 'PQC', 'Cảng hàng không quốc tế Phú Quốc', '2026-10-29 22:58:12', '2026-10-29 23:01:00', 0.03, 223, 23000.00, '2026-10-29 23:02:30'),
(84, 203, 'V7890', 3, 'Bamboo Airways', 1, 'HAN', 'Cảng hàng không quốc tế Nội Bài', 15, 'VKG', 'Cảng hàng không Rạch Giá', '2026-10-29 23:15:02', '2026-10-29 23:18:02', 0.05, 212, 32000.00, '2026-10-29 23:18:17'),
(85, 204, 'VN7689', 3, 'Bamboo Airways', 8, 'DIN', 'Cảng hàng không Điện Biên Phủ', 12, 'VCL', 'Cảng hàng không Chu Lai', '2026-10-29 23:19:17', '2026-10-29 23:31:17', 0.20, 123, 33000.00, '2026-10-29 23:31:42'),
(86, 205, 'SADD', 3, 'Bamboo Airways', 11, 'TBB', 'Cảng hàng không Tuy Hòa', 10, 'VDH', 'Cảng hàng không Đồng Hới', '2026-10-30 18:23:52', '2026-10-30 18:27:52', 0.07, 123, 12000.00, '2026-10-30 18:27:53');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `loai_tau_bay`
--

CREATE TABLE `loai_tau_bay` (
  `id` int(11) NOT NULL,
  `ma_loai` varchar(50) NOT NULL,
  `tai_trong_cat_canh_mtow` decimal(10,2) NOT NULL,
  `suc_chua_hanh_khach` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `loai_tau_bay`
--

INSERT INTO `loai_tau_bay` (`id`, `ma_loai`, `tai_trong_cat_canh_mtow`, `suc_chua_hanh_khach`) VALUES
(1, 'Airbus A321', 89.00, 230),
(2, 'Boeing 787-9', 254.00, 310),
(3, 'Airbus A330', 233.00, 290);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `nguoi_dung`
--

CREATE TABLE `nguoi_dung` (
  `id` int(11) NOT NULL,
  `ten_dang_nhap` varchar(50) NOT NULL,
  `mat_khau_hash` varchar(255) NOT NULL,
  `ho_ten` varchar(100) NOT NULL,
  `vai_tro` enum('Admin_Tong_Cong_Ty','Quan_Ly_San_Bay','Nhan_Vien_Dieu_Hanh','Ke_Toan') NOT NULL,
  `id_san_bay` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `nguoi_dung`
--

INSERT INTO `nguoi_dung` (`id`, `ten_dang_nhap`, `mat_khau_hash`, `ho_ten`, `vai_tro`, `id_san_bay`) VALUES
(1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Nguyễn Đình Khánh', 'Admin_Tong_Cong_Ty', NULL),
(2, 'dieuhanh_sgn', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Trần Bảo Thiên', 'Nhan_Vien_Dieu_Hanh', 2),
(3, 'ketoan_han', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Nguyễn Thị Mỹ Khánh', 'Ke_Toan', 1),
(4, 'quanly_cxr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Huỳnh Tuấn Ngọc', 'Quan_Ly_San_Bay', 3);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `nhat_ky_lich_bay_mua`
--

CREATE TABLE `nhat_ky_lich_bay_mua` (
  `id` int(11) NOT NULL,
  `id_lich_mua` int(11) NOT NULL,
  `id_nguoi_dung` int(11) DEFAULT NULL,
  `trang_thai_cu` varchar(20) DEFAULT NULL,
  `trang_thai_moi` varchar(20) NOT NULL,
  `ly_do` varchar(255) DEFAULT NULL,
  `thoi_gian` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `nhat_ky_lich_bay_mua`
--

INSERT INTO `nhat_ky_lich_bay_mua` (`id`, `id_lich_mua`, `id_nguoi_dung`, `trang_thai_cu`, `trang_thai_moi`, `ly_do`, `thoi_gian`) VALUES
(5, 1, 1, 'Dang_Mo', 'Tam_Dung', 'Thời tiết xấu', '2026-10-27 22:20:00'),
(6, 7, 1, 'Dang_Mo', 'Da_Dong', 'Bảo trì', '2026-10-27 22:20:20'),
(7, 2, 1, 'Dang_Mo', 'Tam_Dung', 'Bảo trì / Nâng cấp', '2026-10-28 14:43:39'),
(8, 2, 1, 'Tam_Dung', 'Da_Dong', 'test', '2026-10-28 14:43:55');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `nhat_ky_trang_thai_bay`
--

CREATE TABLE `nhat_ky_trang_thai_bay` (
  `id` int(11) NOT NULL,
  `id_chuyen_bay` int(11) NOT NULL,
  `id_nguoi_dung` int(11) DEFAULT NULL,
  `hanh_dong` varchar(30) NOT NULL DEFAULT 'Sua',
  `trang_thai` enum('Dang_Mo','Cat_Canh','Ha_Canh','Delay','Huy') NOT NULL,
  `trang_thai_cu` varchar(20) DEFAULT NULL,
  `thoi_gian_cap_nhat` datetime DEFAULT current_timestamp(),
  `ghi_chu` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `nhat_ky_trang_thai_bay`
--

INSERT INTO `nhat_ky_trang_thai_bay` (`id`, `id_chuyen_bay`, `id_nguoi_dung`, `hanh_dong`, `trang_thai`, `trang_thai_cu`, `thoi_gian_cap_nhat`, `ghi_chu`) VALUES
(1, 1, 2, 'Sua', 'Cat_Canh', NULL, '2026-10-18 19:45:46', 'Cất cánh muộn 5 phút do không lưu'),
(2, 1, 2, 'Sua', 'Ha_Canh', NULL, '2026-10-18 19:45:46', 'Hạ cánh an toàn tại HAN'),
(3, 4, 1, 'Sua', 'Cat_Canh', NULL, '2026-10-27 19:52:29', 'Trạng thái đổi từ Dang_Mo sang Cat_Canh'),
(4, 2, 1, 'Sua', 'Ha_Canh', NULL, '2026-10-27 19:52:33', 'Trạng thái đổi từ Dang_Mo sang Ha_Canh'),
(5, 2, 1, 'Sua', 'Delay', NULL, '2026-10-27 19:52:38', 'Trạng thái đổi từ Ha_Canh sang Delay'),
(6, 4, 1, 'Sua', 'Cat_Canh', NULL, '2026-10-27 20:04:11', 'Cửa ra máy bay: DAD - Cửa 1 -> Chưa phân công'),
(7, 4, 1, 'Sua', 'Cat_Canh', NULL, '2026-10-27 20:04:11', 'Quầy check-in: HPH - Quầy B01-B08 -> Chưa phân công'),
(8, 4, 1, 'Sua', 'Cat_Canh', NULL, '2026-10-27 20:04:11', 'Băng chuyền hành lý: DIN - Băng chuyền 01 -> Chưa phân công'),
(9, 4, 1, 'Sua', 'Cat_Canh', NULL, '2026-10-27 20:04:11', 'Hành lý: 333000 kg -> 0 kg'),
(10, 4, 1, 'Sua', 'Cat_Canh', NULL, '2026-10-27 20:04:46', 'Cửa ra máy bay: Chưa phân công -> HAN - Cửa 1'),
(11, 4, 1, 'Sua', 'Cat_Canh', NULL, '2026-10-27 20:04:46', 'Quầy check-in: Chưa phân công -> HAN - Quầy B01-B12'),
(12, 4, 1, 'Sua', 'Cat_Canh', NULL, '2026-10-27 20:04:46', 'Băng chuyền hành lý: Chưa phân công -> HAN - Băng chuyền 09'),
(13, 4, 1, 'Sua', 'Cat_Canh', NULL, '2026-10-27 20:04:46', 'Số hành khách: 0 -> 10'),
(14, 2, 1, 'Sua', 'Delay', NULL, '2026-10-27 20:06:56', 'Cửa ra máy bay: CXR - Cửa 1 -> SGN - Cửa 1'),
(15, 2, 1, 'Sua', 'Delay', NULL, '2026-10-27 20:06:56', 'Quầy check-in: HAN - Quầy E01-E12 -> SGN - Quầy A01-A12'),
(16, 2, 1, 'Sua', 'Delay', NULL, '2026-10-27 20:06:56', 'Số hành khách: 0 -> 1000'),
(17, 7, 1, 'Sua', 'Delay', NULL, '2026-10-27 20:09:59', 'Trạng thái: Đang mở -> Trễ chuyến'),
(18, 7, 1, 'Sua', 'Delay', NULL, '2026-10-27 20:09:59', 'Lý do trễ chuyến: thời tiết xấu'),
(19, 11, 1, 'Sua', 'Dang_Mo', NULL, '2026-10-27 20:48:56', 'Cửa ra máy bay: Chưa phân công -> CXR - Cửa 11'),
(20, 11, 1, 'Sua', 'Dang_Mo', NULL, '2026-10-27 20:48:56', 'Quầy check-in: Chưa phân công -> CXR - Quầy A01-A08'),
(21, 11, 1, 'Sua', 'Dang_Mo', NULL, '2026-10-27 20:48:56', 'Băng chuyền hành lý: Chưa phân công -> CXR - Băng chuyền 01'),
(22, 12, 1, 'Sua', 'Dang_Mo', NULL, '2026-10-27 20:49:16', 'Mã chuyến bay: BAGTRACE2 -> VN1235'),
(23, 11, 1, 'Sua', 'Dang_Mo', NULL, '2026-10-27 20:49:24', 'Mã chuyến bay: BAGTRACE -> VJ776'),
(24, 10, 1, 'Sua', 'Dang_Mo', NULL, '2026-10-27 20:49:31', 'Mã chuyến bay: BAGLIMIT2 -> VN217'),
(25, 9, 1, 'Sua', 'Dang_Mo', NULL, '2026-10-27 20:49:43', 'Mã chuyến bay: BAGTEST -> VN0007'),
(26, 8, 1, 'Sua', 'Dang_Mo', NULL, '2026-10-27 20:49:52', 'Mã chuyến bay: CAPTEST2 -> VJ773'),
(30, 139, 1, 'Sua', 'Dang_Mo', NULL, '2026-10-27 22:00:16', 'Mã chuyến bay: VN210 -> VN219'),
(31, 139, 1, 'Sua', 'Dang_Mo', NULL, '2026-10-27 22:01:14', 'Số hành khách: 0 -> 4'),
(32, 129, 1, 'Huy', 'Huy', NULL, '2026-10-27 22:21:10', 'Hủy chuyến. Trạng thái đổi từ Dang_Mo sang Huy. Lý do: Thời tiết xấu'),
(38, 141, 1, 'Sua', 'Dang_Mo', 'Dang_Mo', '2026-10-27 22:33:57', 'Mã chuyến bay: VN210 -> VN281'),
(39, 135, 1, 'Sua', 'Cat_Canh', 'Dang_Mo', '2026-10-27 22:51:34', 'Trạng thái: Đang mở -> Cất cánh'),
(41, 141, 1, 'Sua', 'Dang_Mo', 'Dang_Mo', '2026-10-27 22:55:52', 'Cửa ra máy bay: Chưa phân công -> SGN - Cửa 19; Số hành khách: 0 -> 230'),
(50, 3, NULL, 'Tu_Dong', 'Cat_Canh', 'Dang_Mo', '2026-10-27 23:11:02', 'Tự động: đã tới giờ cất cánh.'),
(51, 6, NULL, 'Tu_Dong', 'Cat_Canh', 'Dang_Mo', '2026-10-27 23:11:02', 'Tự động: đã tới giờ cất cánh.'),
(52, 13, NULL, 'Tu_Dong', 'Cat_Canh', 'Dang_Mo', '2026-10-27 23:11:02', 'Tự động: đã tới giờ cất cánh.'),
(53, 15, NULL, 'Tu_Dong', 'Cat_Canh', 'Dang_Mo', '2026-10-27 23:11:02', 'Tự động: đã tới giờ cất cánh.'),
(54, 42, NULL, 'Tu_Dong', 'Cat_Canh', 'Dang_Mo', '2026-10-27 23:11:02', 'Tự động: đã tới giờ cất cánh.'),
(55, 54, NULL, 'Tu_Dong', 'Cat_Canh', 'Dang_Mo', '2026-10-27 23:11:02', 'Tự động: đã tới giờ cất cánh.'),
(56, 55, NULL, 'Tu_Dong', 'Cat_Canh', 'Dang_Mo', '2026-10-27 23:11:02', 'Tự động: đã tới giờ cất cánh.'),
(57, 56, NULL, 'Tu_Dong', 'Cat_Canh', 'Dang_Mo', '2026-10-27 23:11:02', 'Tự động: đã tới giờ cất cánh.'),
(58, 57, NULL, 'Tu_Dong', 'Cat_Canh', 'Dang_Mo', '2026-10-27 23:11:02', 'Tự động: đã tới giờ cất cánh.'),
(59, 58, NULL, 'Tu_Dong', 'Cat_Canh', 'Dang_Mo', '2026-10-27 23:11:02', 'Tự động: đã tới giờ cất cánh.'),
(60, 59, NULL, 'Tu_Dong', 'Cat_Canh', 'Dang_Mo', '2026-10-27 23:11:02', 'Tự động: đã tới giờ cất cánh.'),
(61, 60, NULL, 'Tu_Dong', 'Cat_Canh', 'Dang_Mo', '2026-10-27 23:11:02', 'Tự động: đã tới giờ cất cánh.'),
(62, 61, NULL, 'Tu_Dong', 'Cat_Canh', 'Dang_Mo', '2026-10-27 23:11:02', 'Tự động: đã tới giờ cất cánh.'),
(63, 62, NULL, 'Tu_Dong', 'Cat_Canh', 'Dang_Mo', '2026-10-27 23:11:02', 'Tự động: đã tới giờ cất cánh.'),
(64, 63, NULL, 'Tu_Dong', 'Cat_Canh', 'Dang_Mo', '2026-10-27 23:11:02', 'Tự động: đã tới giờ cất cánh.'),
(65, 64, NULL, 'Tu_Dong', 'Cat_Canh', 'Dang_Mo', '2026-10-27 23:11:02', 'Tự động: đã tới giờ cất cánh.'),
(66, 77, NULL, 'Tu_Dong', 'Cat_Canh', 'Dang_Mo', '2026-10-27 23:11:02', 'Tự động: đã tới giờ cất cánh.'),
(67, 78, NULL, 'Tu_Dong', 'Cat_Canh', 'Dang_Mo', '2026-10-27 23:11:02', 'Tự động: đã tới giờ cất cánh.'),
(68, 79, NULL, 'Tu_Dong', 'Cat_Canh', 'Dang_Mo', '2026-10-27 23:11:02', 'Tự động: đã tới giờ cất cánh.'),
(69, 80, NULL, 'Tu_Dong', 'Cat_Canh', 'Dang_Mo', '2026-10-27 23:11:02', 'Tự động: đã tới giờ cất cánh.'),
(70, 81, NULL, 'Tu_Dong', 'Cat_Canh', 'Dang_Mo', '2026-10-27 23:11:02', 'Tự động: đã tới giờ cất cánh.'),
(71, 82, NULL, 'Tu_Dong', 'Cat_Canh', 'Dang_Mo', '2026-10-27 23:11:02', 'Tự động: đã tới giờ cất cánh.'),
(72, 83, NULL, 'Tu_Dong', 'Cat_Canh', 'Dang_Mo', '2026-10-27 23:11:02', 'Tự động: đã tới giờ cất cánh.'),
(73, 84, NULL, 'Tu_Dong', 'Cat_Canh', 'Dang_Mo', '2026-10-27 23:11:02', 'Tự động: đã tới giờ cất cánh.'),
(74, 85, NULL, 'Tu_Dong', 'Cat_Canh', 'Dang_Mo', '2026-10-27 23:11:02', 'Tự động: đã tới giờ cất cánh.'),
(75, 86, NULL, 'Tu_Dong', 'Cat_Canh', 'Dang_Mo', '2026-10-27 23:11:02', 'Tự động: đã tới giờ cất cánh.'),
(76, 87, NULL, 'Tu_Dong', 'Cat_Canh', 'Dang_Mo', '2026-10-27 23:11:02', 'Tự động: đã tới giờ cất cánh.'),
(77, 90, NULL, 'Tu_Dong', 'Cat_Canh', 'Dang_Mo', '2026-10-27 23:11:02', 'Tự động: đã tới giờ cất cánh.'),
(78, 91, NULL, 'Tu_Dong', 'Cat_Canh', 'Dang_Mo', '2026-10-27 23:11:02', 'Tự động: đã tới giờ cất cánh.'),
(79, 92, NULL, 'Tu_Dong', 'Cat_Canh', 'Dang_Mo', '2026-10-27 23:11:02', 'Tự động: đã tới giờ cất cánh.'),
(80, 93, NULL, 'Tu_Dong', 'Cat_Canh', 'Dang_Mo', '2026-10-27 23:11:02', 'Tự động: đã tới giờ cất cánh.'),
(81, 94, NULL, 'Tu_Dong', 'Cat_Canh', 'Dang_Mo', '2026-10-27 23:11:02', 'Tự động: đã tới giờ cất cánh.'),
(82, 95, NULL, 'Tu_Dong', 'Cat_Canh', 'Dang_Mo', '2026-10-27 23:11:02', 'Tự động: đã tới giờ cất cánh.'),
(83, 96, NULL, 'Tu_Dong', 'Cat_Canh', 'Dang_Mo', '2026-10-27 23:11:02', 'Tự động: đã tới giờ cất cánh.'),
(84, 97, NULL, 'Tu_Dong', 'Cat_Canh', 'Dang_Mo', '2026-10-27 23:11:02', 'Tự động: đã tới giờ cất cánh.'),
(85, 98, NULL, 'Tu_Dong', 'Cat_Canh', 'Dang_Mo', '2026-10-27 23:11:02', 'Tự động: đã tới giờ cất cánh.'),
(86, 99, NULL, 'Tu_Dong', 'Cat_Canh', 'Dang_Mo', '2026-10-27 23:11:02', 'Tự động: đã tới giờ cất cánh.'),
(87, 100, NULL, 'Tu_Dong', 'Cat_Canh', 'Dang_Mo', '2026-10-27 23:11:02', 'Tự động: đã tới giờ cất cánh.'),
(88, 101, NULL, 'Tu_Dong', 'Cat_Canh', 'Dang_Mo', '2026-10-27 23:11:02', 'Tự động: đã tới giờ cất cánh.'),
(89, 102, NULL, 'Tu_Dong', 'Cat_Canh', 'Dang_Mo', '2026-10-27 23:11:02', 'Tự động: đã tới giờ cất cánh.'),
(90, 103, NULL, 'Tu_Dong', 'Cat_Canh', 'Dang_Mo', '2026-10-27 23:11:02', 'Tự động: đã tới giờ cất cánh.'),
(91, 104, NULL, 'Tu_Dong', 'Cat_Canh', 'Dang_Mo', '2026-10-27 23:11:02', 'Tự động: đã tới giờ cất cánh.'),
(92, 105, NULL, 'Tu_Dong', 'Cat_Canh', 'Dang_Mo', '2026-10-27 23:11:02', 'Tự động: đã tới giờ cất cánh.'),
(93, 106, NULL, 'Tu_Dong', 'Cat_Canh', 'Dang_Mo', '2026-10-27 23:11:02', 'Tự động: đã tới giờ cất cánh.'),
(94, 107, NULL, 'Tu_Dong', 'Cat_Canh', 'Dang_Mo', '2026-10-27 23:11:02', 'Tự động: đã tới giờ cất cánh.'),
(95, 108, NULL, 'Tu_Dong', 'Cat_Canh', 'Dang_Mo', '2026-10-27 23:11:02', 'Tự động: đã tới giờ cất cánh.'),
(96, 109, NULL, 'Tu_Dong', 'Cat_Canh', 'Dang_Mo', '2026-10-27 23:11:02', 'Tự động: đã tới giờ cất cánh.'),
(97, 110, NULL, 'Tu_Dong', 'Cat_Canh', 'Dang_Mo', '2026-10-27 23:11:02', 'Tự động: đã tới giờ cất cánh.'),
(98, 111, NULL, 'Tu_Dong', 'Cat_Canh', 'Dang_Mo', '2026-10-27 23:11:02', 'Tự động: đã tới giờ cất cánh.'),
(99, 112, NULL, 'Tu_Dong', 'Cat_Canh', 'Dang_Mo', '2026-10-27 23:11:02', 'Tự động: đã tới giờ cất cánh.'),
(100, 113, NULL, 'Tu_Dong', 'Cat_Canh', 'Dang_Mo', '2026-10-27 23:11:02', 'Tự động: đã tới giờ cất cánh.'),
(101, 3, NULL, 'Tu_Dong', 'Ha_Canh', 'Cat_Canh', '2026-10-27 23:11:02', 'Tự động: đã tới giờ dự kiến hạ cánh.'),
(102, 4, NULL, 'Tu_Dong', 'Ha_Canh', 'Cat_Canh', '2026-10-27 23:11:02', 'Tự động: đã tới giờ dự kiến hạ cánh.'),
(103, 6, NULL, 'Tu_Dong', 'Ha_Canh', 'Cat_Canh', '2026-10-27 23:11:02', 'Tự động: đã tới giờ dự kiến hạ cánh.'),
(104, 13, NULL, 'Tu_Dong', 'Ha_Canh', 'Cat_Canh', '2026-10-27 23:11:02', 'Tự động: đã tới giờ dự kiến hạ cánh.'),
(105, 15, NULL, 'Tu_Dong', 'Ha_Canh', 'Cat_Canh', '2026-10-27 23:11:02', 'Tự động: đã tới giờ dự kiến hạ cánh.'),
(106, 42, NULL, 'Tu_Dong', 'Ha_Canh', 'Cat_Canh', '2026-10-27 23:11:02', 'Tự động: đã tới giờ dự kiến hạ cánh.'),
(107, 54, NULL, 'Tu_Dong', 'Ha_Canh', 'Cat_Canh', '2026-10-27 23:11:02', 'Tự động: đã tới giờ dự kiến hạ cánh.'),
(108, 55, NULL, 'Tu_Dong', 'Ha_Canh', 'Cat_Canh', '2026-10-27 23:11:02', 'Tự động: đã tới giờ dự kiến hạ cánh.'),
(109, 56, NULL, 'Tu_Dong', 'Ha_Canh', 'Cat_Canh', '2026-10-27 23:11:02', 'Tự động: đã tới giờ dự kiến hạ cánh.'),
(110, 57, NULL, 'Tu_Dong', 'Ha_Canh', 'Cat_Canh', '2026-10-27 23:11:02', 'Tự động: đã tới giờ dự kiến hạ cánh.'),
(111, 58, NULL, 'Tu_Dong', 'Ha_Canh', 'Cat_Canh', '2026-10-27 23:11:02', 'Tự động: đã tới giờ dự kiến hạ cánh.'),
(112, 59, NULL, 'Tu_Dong', 'Ha_Canh', 'Cat_Canh', '2026-10-27 23:11:02', 'Tự động: đã tới giờ dự kiến hạ cánh.'),
(113, 60, NULL, 'Tu_Dong', 'Ha_Canh', 'Cat_Canh', '2026-10-27 23:11:02', 'Tự động: đã tới giờ dự kiến hạ cánh.'),
(114, 61, NULL, 'Tu_Dong', 'Ha_Canh', 'Cat_Canh', '2026-10-27 23:11:02', 'Tự động: đã tới giờ dự kiến hạ cánh.'),
(115, 62, NULL, 'Tu_Dong', 'Ha_Canh', 'Cat_Canh', '2026-10-27 23:11:02', 'Tự động: đã tới giờ dự kiến hạ cánh.'),
(116, 63, NULL, 'Tu_Dong', 'Ha_Canh', 'Cat_Canh', '2026-10-27 23:11:02', 'Tự động: đã tới giờ dự kiến hạ cánh.'),
(117, 64, NULL, 'Tu_Dong', 'Ha_Canh', 'Cat_Canh', '2026-10-27 23:11:02', 'Tự động: đã tới giờ dự kiến hạ cánh.'),
(118, 77, NULL, 'Tu_Dong', 'Ha_Canh', 'Cat_Canh', '2026-10-27 23:11:02', 'Tự động: đã tới giờ dự kiến hạ cánh.'),
(119, 78, NULL, 'Tu_Dong', 'Ha_Canh', 'Cat_Canh', '2026-10-27 23:11:02', 'Tự động: đã tới giờ dự kiến hạ cánh.'),
(120, 79, NULL, 'Tu_Dong', 'Ha_Canh', 'Cat_Canh', '2026-10-27 23:11:02', 'Tự động: đã tới giờ dự kiến hạ cánh.'),
(121, 80, NULL, 'Tu_Dong', 'Ha_Canh', 'Cat_Canh', '2026-10-27 23:11:02', 'Tự động: đã tới giờ dự kiến hạ cánh.'),
(122, 81, NULL, 'Tu_Dong', 'Ha_Canh', 'Cat_Canh', '2026-10-27 23:11:02', 'Tự động: đã tới giờ dự kiến hạ cánh.'),
(123, 82, NULL, 'Tu_Dong', 'Ha_Canh', 'Cat_Canh', '2026-10-27 23:11:02', 'Tự động: đã tới giờ dự kiến hạ cánh.'),
(124, 83, NULL, 'Tu_Dong', 'Ha_Canh', 'Cat_Canh', '2026-10-27 23:11:02', 'Tự động: đã tới giờ dự kiến hạ cánh.'),
(125, 84, NULL, 'Tu_Dong', 'Ha_Canh', 'Cat_Canh', '2026-10-27 23:11:02', 'Tự động: đã tới giờ dự kiến hạ cánh.'),
(126, 85, NULL, 'Tu_Dong', 'Ha_Canh', 'Cat_Canh', '2026-10-27 23:11:02', 'Tự động: đã tới giờ dự kiến hạ cánh.'),
(127, 86, NULL, 'Tu_Dong', 'Ha_Canh', 'Cat_Canh', '2026-10-27 23:11:02', 'Tự động: đã tới giờ dự kiến hạ cánh.'),
(128, 87, NULL, 'Tu_Dong', 'Ha_Canh', 'Cat_Canh', '2026-10-27 23:11:02', 'Tự động: đã tới giờ dự kiến hạ cánh.'),
(129, 90, NULL, 'Tu_Dong', 'Ha_Canh', 'Cat_Canh', '2026-10-27 23:11:02', 'Tự động: đã tới giờ dự kiến hạ cánh.'),
(130, 91, NULL, 'Tu_Dong', 'Ha_Canh', 'Cat_Canh', '2026-10-27 23:11:02', 'Tự động: đã tới giờ dự kiến hạ cánh.'),
(131, 92, NULL, 'Tu_Dong', 'Ha_Canh', 'Cat_Canh', '2026-10-27 23:11:02', 'Tự động: đã tới giờ dự kiến hạ cánh.'),
(132, 93, NULL, 'Tu_Dong', 'Ha_Canh', 'Cat_Canh', '2026-10-27 23:11:02', 'Tự động: đã tới giờ dự kiến hạ cánh.'),
(133, 94, NULL, 'Tu_Dong', 'Ha_Canh', 'Cat_Canh', '2026-10-27 23:11:02', 'Tự động: đã tới giờ dự kiến hạ cánh.'),
(134, 95, NULL, 'Tu_Dong', 'Ha_Canh', 'Cat_Canh', '2026-10-27 23:11:02', 'Tự động: đã tới giờ dự kiến hạ cánh.'),
(135, 96, NULL, 'Tu_Dong', 'Ha_Canh', 'Cat_Canh', '2026-10-27 23:11:02', 'Tự động: đã tới giờ dự kiến hạ cánh.'),
(136, 97, NULL, 'Tu_Dong', 'Ha_Canh', 'Cat_Canh', '2026-10-27 23:11:02', 'Tự động: đã tới giờ dự kiến hạ cánh.'),
(137, 98, NULL, 'Tu_Dong', 'Ha_Canh', 'Cat_Canh', '2026-10-27 23:11:02', 'Tự động: đã tới giờ dự kiến hạ cánh.'),
(138, 99, NULL, 'Tu_Dong', 'Ha_Canh', 'Cat_Canh', '2026-10-27 23:11:02', 'Tự động: đã tới giờ dự kiến hạ cánh.'),
(139, 100, NULL, 'Tu_Dong', 'Ha_Canh', 'Cat_Canh', '2026-10-27 23:11:02', 'Tự động: đã tới giờ dự kiến hạ cánh.'),
(140, 101, NULL, 'Tu_Dong', 'Ha_Canh', 'Cat_Canh', '2026-10-27 23:11:02', 'Tự động: đã tới giờ dự kiến hạ cánh.'),
(141, 102, NULL, 'Tu_Dong', 'Ha_Canh', 'Cat_Canh', '2026-10-27 23:11:02', 'Tự động: đã tới giờ dự kiến hạ cánh.'),
(142, 103, NULL, 'Tu_Dong', 'Ha_Canh', 'Cat_Canh', '2026-10-27 23:11:02', 'Tự động: đã tới giờ dự kiến hạ cánh.'),
(143, 104, NULL, 'Tu_Dong', 'Ha_Canh', 'Cat_Canh', '2026-10-27 23:11:02', 'Tự động: đã tới giờ dự kiến hạ cánh.'),
(144, 105, NULL, 'Tu_Dong', 'Ha_Canh', 'Cat_Canh', '2026-10-27 23:11:02', 'Tự động: đã tới giờ dự kiến hạ cánh.'),
(145, 106, NULL, 'Tu_Dong', 'Ha_Canh', 'Cat_Canh', '2026-10-27 23:11:02', 'Tự động: đã tới giờ dự kiến hạ cánh.'),
(146, 107, NULL, 'Tu_Dong', 'Ha_Canh', 'Cat_Canh', '2026-10-27 23:11:02', 'Tự động: đã tới giờ dự kiến hạ cánh.'),
(147, 108, NULL, 'Tu_Dong', 'Ha_Canh', 'Cat_Canh', '2026-10-27 23:11:02', 'Tự động: đã tới giờ dự kiến hạ cánh.'),
(148, 109, NULL, 'Tu_Dong', 'Ha_Canh', 'Cat_Canh', '2026-10-27 23:11:02', 'Tự động: đã tới giờ dự kiến hạ cánh.'),
(149, 110, NULL, 'Tu_Dong', 'Ha_Canh', 'Cat_Canh', '2026-10-27 23:11:02', 'Tự động: đã tới giờ dự kiến hạ cánh.'),
(150, 111, NULL, 'Tu_Dong', 'Ha_Canh', 'Cat_Canh', '2026-10-27 23:11:02', 'Tự động: đã tới giờ dự kiến hạ cánh.'),
(151, 112, NULL, 'Tu_Dong', 'Ha_Canh', 'Cat_Canh', '2026-10-27 23:11:02', 'Tự động: đã tới giờ dự kiến hạ cánh.'),
(152, 113, NULL, 'Tu_Dong', 'Ha_Canh', 'Cat_Canh', '2026-10-27 23:11:02', 'Tự động: đã tới giờ dự kiến hạ cánh.'),
(157, 160, 1, 'Them', 'Dang_Mo', NULL, '2026-10-27 23:17:36', 'Thêm chuyến bay mới.'),
(158, 160, NULL, 'Tu_Dong', 'Cat_Canh', 'Dang_Mo', '2026-10-27 23:17:36', 'Tự động: đã tới giờ cất cánh.'),
(160, 160, NULL, 'Tu_Dong', 'Ha_Canh', 'Cat_Canh', '2026-10-27 23:27:06', 'Tự động: đã tới giờ dự kiến hạ cánh.'),
(161, 39, NULL, 'Tu_Dong', 'Cat_Canh', 'Dang_Mo', '2026-10-28 13:45:10', 'Tự động: đã tới giờ cất cánh.'),
(162, 43, NULL, 'Tu_Dong', 'Cat_Canh', 'Dang_Mo', '2026-10-28 13:45:10', 'Tự động: đã tới giờ cất cánh.'),
(163, 88, NULL, 'Tu_Dong', 'Cat_Canh', 'Dang_Mo', '2026-10-28 13:45:10', 'Tự động: đã tới giờ cất cánh.'),
(164, 39, NULL, 'Tu_Dong', 'Ha_Canh', 'Cat_Canh', '2026-10-28 13:45:10', 'Tự động: đã tới giờ dự kiến hạ cánh.'),
(165, 43, NULL, 'Tu_Dong', 'Ha_Canh', 'Cat_Canh', '2026-10-28 13:45:10', 'Tự động: đã tới giờ dự kiến hạ cánh.'),
(166, 88, NULL, 'Tu_Dong', 'Ha_Canh', 'Cat_Canh', '2026-10-28 13:45:10', 'Tự động: đã tới giờ dự kiến hạ cánh.'),
(170, 141, 1, 'Sua', 'Dang_Mo', 'Dang_Mo', '2026-10-28 13:49:42', 'Hành lý: 0 kg -> 100 kg'),
(171, 141, 1, 'Sua', 'Dang_Mo', 'Dang_Mo', '2026-10-28 13:50:02', 'Quầy check-in: Chưa phân công -> SGN - Quầy A01-A12; Băng chuyền hành lý: Chưa phân công -> SGN - Băng chuyền 01; Số hành khách: 230 -> 123'),
(172, 141, 1, 'Huy', 'Huy', 'Dang_Mo', '2026-10-28 13:50:42', 'Hủy chuyến. Lý do: Thời tiết xấu'),
(175, 76, 1, 'Sua', 'Delay', 'Dang_Mo', '2026-10-28 13:54:45', 'Số hành khách: 0 -> 10; Hành lý: 0 kg -> 10 kg; Trạng thái: Đang mở -> Trễ chuyến; Lý do trễ chuyến: Thời tiết xấu; Khoảng thời gian dự kiến trễ: 30 phút (30 phút)'),
(177, 166, 1, 'Them', 'Dang_Mo', NULL, '2026-10-28 14:07:12', 'Thêm chuyến bay mới.'),
(178, 2, 1, 'Xac_Nhan_Cat_Canh', 'Cat_Canh', 'Delay', '2026-10-28 14:07:27', 'Đã đủ điều kiện bay và xác nhận cất cánh. Không còn sự cố nào chưa xử lý.'),
(179, 2, NULL, 'Tu_Dong', 'Ha_Canh', 'Cat_Canh', '2026-10-28 14:07:27', 'Tự động: đã tới giờ dự kiến hạ cánh.'),
(180, 7, 1, 'Xac_Nhan_Cat_Canh', 'Cat_Canh', 'Delay', '2026-10-28 14:07:29', 'Đã đủ điều kiện bay và xác nhận cất cánh. Không còn sự cố nào chưa xử lý.'),
(181, 7, NULL, 'Tu_Dong', 'Ha_Canh', 'Cat_Canh', '2026-10-28 14:07:29', 'Tự động: đã tới giờ dự kiến hạ cánh.'),
(182, 166, 1, 'Xac_Nhan_Cat_Canh', 'Cat_Canh', 'Dang_Mo', '2026-10-28 14:07:30', 'Đã đủ điều kiện bay và xác nhận cất cánh. Không còn sự cố nào chưa xử lý.'),
(187, 166, NULL, 'Tu_Dong', 'Ha_Canh', 'Cat_Canh', '2026-10-28 14:20:09', 'Tự động: đã tới giờ dự kiến hạ cánh.'),
(188, 174, 1, 'Them', 'Dang_Mo', NULL, '2026-10-28 14:20:16', 'Thêm chuyến bay mới.'),
(189, 174, 1, 'Tre_Tiep', 'Delay', 'Dang_Mo', '2026-10-28 14:20:45', 'Chưa đủ điều kiện bay - trễ tiếp 30 phút. Lý do: chờ kỹ thuật. Giờ cất cánh mới: 28/10/2026 14:50.'),
(190, 174, 1, 'Sua', 'Dang_Mo', 'Delay', '2026-10-28 14:23:18', 'Giờ dự kiến: 28/10/2026 14:50 -> 28/10/2026 14:50; Trạng thái: Trễ chuyến -> Đang mở'),
(191, 174, 1, 'Sua', 'Delay', 'Dang_Mo', '2026-10-28 14:23:29', 'Trạng thái: Đang mở -> Trễ chuyến; Lý do trễ chuyến: test; Khoảng thời gian dự kiến trễ: 3 phút'),
(200, 174, 1, 'Xac_Nhan_Cat_Canh', 'Cat_Canh', 'Delay', '2026-10-28 14:29:48', 'Đã đủ yêu cầu an toàn và xác nhận cất cánh. Cất cánh sớm hơn giờ dự kiến (28/10/2026 14:50).'),
(201, 174, NULL, 'Tu_Dong', 'Ha_Canh', 'Cat_Canh', '2026-10-28 14:36:12', 'Tự động: đã tới giờ dự kiến hạ cánh.'),
(206, 187, NULL, 'Tao_Tu_Lich', 'Dang_Mo', NULL, '2026-10-28 14:45:06', 'Sinh tự động từ lịch bay mùa VN111 (VCL → SGN).'),
(226, 187, 1, 'Xac_Nhan_Cat_Canh', 'Cat_Canh', 'Dang_Mo', '2026-10-28 14:50:15', 'Đã đủ điều kiện bay và xác nhận cất cánh.'),
(227, 188, 1, 'Them', 'Dang_Mo', NULL, '2026-10-28 13:47:39', 'Dữ liệu test: chuyến đã đến giờ cất cánh, chờ xác nhận điều kiện bay.'),
(228, 189, 1, 'Them', 'Dang_Mo', NULL, '2026-10-28 13:45:39', 'Dữ liệu test: chuyến đã đến giờ cất cánh, chờ xác nhận điều kiện bay.'),
(229, 190, 1, 'Them', 'Dang_Mo', NULL, '2026-10-28 13:43:39', 'Dữ liệu test: chuyến đã đến giờ cất cánh, chờ xác nhận điều kiện bay.'),
(230, 191, 1, 'Them', 'Dang_Mo', NULL, '2026-10-28 13:41:39', 'Dữ liệu test: chuyến đã đến giờ cất cánh, chờ xác nhận điều kiện bay.'),
(234, 192, 1, 'Them', 'Dang_Mo', NULL, '2026-10-28 13:30:39', 'Dữ liệu test: chuyến bay được tạo trước khi bị trễ.'),
(235, 193, 1, 'Them', 'Dang_Mo', NULL, '2026-10-28 13:26:39', 'Dữ liệu test: chuyến bay được tạo trước khi bị trễ.'),
(236, 194, 1, 'Them', 'Dang_Mo', NULL, '2026-10-28 13:22:39', 'Dữ liệu test: chuyến bay được tạo trước khi bị trễ.'),
(237, 195, 1, 'Them', 'Dang_Mo', NULL, '2026-10-28 13:18:39', 'Dữ liệu test: chuyến bay được tạo trước khi bị trễ.'),
(241, 192, 1, 'Sua', 'Delay', 'Dang_Mo', '2026-10-28 13:45:39', 'Trạng thái: Đang mở -> Trễ chuyến; Lý do trễ chuyến: Mưa lớn, hạn chế tầm nhìn tại sân bay đi; Khoảng thời gian dự kiến trễ: 30 phút'),
(242, 193, 1, 'Sua', 'Delay', 'Dang_Mo', '2026-10-28 13:44:39', 'Trạng thái: Đang mở -> Trễ chuyến; Lý do trễ chuyến: Tàu bay về muộn từ chuyến trước; Khoảng thời gian dự kiến trễ: 25 phút'),
(243, 194, 1, 'Sua', 'Delay', 'Dang_Mo', '2026-10-28 13:42:39', 'Trạng thái: Đang mở -> Trễ chuyến; Lý do trễ chuyến: Kỹ thuật kiểm tra thêm hệ thống thủy lực; Khoảng thời gian dự kiến trễ: 35 phút'),
(244, 195, 1, 'Sua', 'Delay', 'Dang_Mo', '2026-10-28 13:40:39', 'Trạng thái: Đang mở -> Trễ chuyến; Lý do trễ chuyến: Kiểm soát không lưu yêu cầu đổi đường bay; Khoảng thời gian dự kiến trễ: 40 phút'),
(245, 191, NULL, 'Tu_Dong', 'Delay', 'Dang_Mo', '2026-10-28 14:56:40', 'Tự động chuyển Trễ chuyến: quá 15 phút kể từ giờ cất cánh mà chưa xác nhận điều kiện bay.'),
(246, 190, NULL, 'Tu_Dong', 'Delay', 'Dang_Mo', '2026-10-28 14:58:39', 'Tự động chuyển Trễ chuyến: quá 15 phút kể từ giờ cất cánh mà chưa xác nhận điều kiện bay.'),
(247, 189, NULL, 'Tu_Dong', 'Delay', 'Dang_Mo', '2026-10-28 15:00:43', 'Tự động chuyển Trễ chuyến: quá 15 phút kể từ giờ cất cánh mà chưa xác nhận điều kiện bay.'),
(248, 188, NULL, 'Tu_Dong', 'Delay', 'Dang_Mo', '2026-10-28 15:02:44', 'Tự động chuyển Trễ chuyến: quá 15 phút kể từ giờ cất cánh mà chưa xác nhận điều kiện bay.'),
(249, 196, 1, 'Them', 'Dang_Mo', NULL, '2026-10-28 15:02:56', 'Thêm chuyến bay mới.'),
(250, 196, 1, 'Sua', 'Dang_Mo', 'Dang_Mo', '2026-10-28 15:03:25', 'Số hành khách: 230 -> 1; Hành lý: 89000 kg -> 1 kg'),
(251, 196, 1, 'Xac_Nhan_Cat_Canh', 'Cat_Canh', 'Dang_Mo', '2026-10-28 15:09:05', 'Đã đủ điều kiện bay và xác nhận cất cánh.'),
(252, 194, 1, 'Xac_Nhan_Cat_Canh', 'Cat_Canh', 'Delay', '2026-10-28 15:09:12', 'Đã đủ yêu cầu an toàn và xác nhận cất cánh.'),
(253, 188, 1, 'Huy', 'Huy', 'Delay', '2026-10-28 15:10:07', 'Hủy chuyến. Lý do: test'),
(254, 187, NULL, 'Tu_Dong', 'Ha_Canh', 'Cat_Canh', '2026-10-29 17:59:56', 'Tự động: đã tới giờ dự kiến hạ cánh.'),
(255, 194, NULL, 'Tu_Dong', 'Ha_Canh', 'Cat_Canh', '2026-10-29 17:59:56', 'Tự động: đã tới giờ dự kiến hạ cánh.'),
(256, 195, NULL, 'Tu_Dong', 'Ha_Canh', 'Cat_Canh', '2026-10-29 17:59:56', 'Tự động: đã tới giờ dự kiến hạ cánh.'),
(257, 196, NULL, 'Tu_Dong', 'Ha_Canh', 'Cat_Canh', '2026-10-29 17:59:56', 'Tự động: đã tới giờ dự kiến hạ cánh.'),
(258, 44, NULL, 'Tu_Dong', 'Delay', 'Dang_Mo', '2026-10-29 17:59:56', 'Tự động chuyển Trễ chuyến: quá 15 phút kể từ giờ cất cánh mà chưa xác nhận điều kiện bay.'),
(259, 44, NULL, 'Tu_Dong', 'Huy', 'Delay', '2026-10-29 20:53:54', 'Hủy chuyến do chưa khắc phục được sự cố và chuyển sang chuyến bay gần nhất.'),
(260, 197, 1, 'Them', 'Dang_Mo', NULL, '2026-10-29 21:00:19', 'Thêm chuyến bay mới.'),
(261, 197, 1, 'Tre_Tiep', 'Delay', 'Dang_Mo', '2026-10-29 21:01:29', 'Chưa đủ điều kiện bay - trễ chuyến 11 phút. Lý do: Delay. Giờ cất cánh mới: 29/10/2026 21:11.'),
(262, 198, 1, 'Them', 'Dang_Mo', NULL, '2026-10-29 21:24:26', 'Thêm chuyến bay mới.'),
(263, 189, NULL, 'Tu_Dong', 'Dang_Mo', 'Delay', '2026-10-29 21:28:39', 'Hết thời gian delay, chuyển về Đang mở. Lý do trễ trước đó: Chưa có lý do.'),
(264, 190, NULL, 'Tu_Dong', 'Dang_Mo', 'Delay', '2026-10-29 21:28:39', 'Hết thời gian delay, chuyển về Đang mở. Lý do trễ trước đó: Chưa có lý do.'),
(265, 191, NULL, 'Tu_Dong', 'Dang_Mo', 'Delay', '2026-10-29 21:28:39', 'Hết thời gian delay, chuyển về Đang mở. Lý do trễ trước đó: Chưa có lý do.'),
(266, 192, NULL, 'Tu_Dong', 'Dang_Mo', 'Delay', '2026-10-29 21:28:39', 'Hết thời gian delay, chuyển về Đang mở. Lý do trễ trước đó: Mưa lớn, hạn chế tầm nhìn tại sân bay đi.'),
(267, 193, NULL, 'Tu_Dong', 'Dang_Mo', 'Delay', '2026-10-29 21:28:39', 'Hết thời gian delay, chuyển về Đang mở. Lý do trễ trước đó: Tàu bay về muộn từ chuyến trước.'),
(268, 197, NULL, 'Tu_Dong', 'Dang_Mo', 'Delay', '2026-10-29 21:28:39', 'Hết thời gian delay, chuyển về Đang mở. Lý do trễ trước đó: Delay.'),
(269, 197, NULL, 'Tu_Dong', 'Delay', 'Dang_Mo', '2026-10-29 21:28:40', 'Tự động chuyển Trễ chuyến: quá 15 phút kể từ giờ cất cánh mà chưa xác nhận điều kiện bay.'),
(270, 197, NULL, 'Tu_Dong', 'Dang_Mo', 'Delay', '2026-10-29 21:28:40', 'Hết thời gian delay, chuyển về Đang mở. Lý do trễ trước đó: Chưa có lý do.'),
(271, 197, NULL, 'Tu_Dong', 'Delay', 'Dang_Mo', '2026-10-29 21:28:43', 'Tự động chuyển Trễ chuyến: quá 15 phút kể từ giờ cất cánh mà chưa xác nhận điều kiện bay.'),
(272, 197, NULL, 'Tu_Dong', 'Dang_Mo', 'Delay', '2026-10-29 21:28:43', 'Hết thời gian delay, chuyển về Đang mở. Lý do trễ trước đó: Chưa có lý do.'),
(273, 197, NULL, 'Tu_Dong', 'Delay', 'Dang_Mo', '2026-10-29 21:28:43', 'Tự động chuyển Trễ chuyến: quá 15 phút kể từ giờ cất cánh mà chưa xác nhận điều kiện bay.'),
(274, 197, NULL, 'Tu_Dong', 'Dang_Mo', 'Delay', '2026-10-29 21:28:43', 'Hết thời gian delay, chuyển về Đang mở. Lý do trễ trước đó: Chưa có lý do.'),
(275, 197, NULL, 'Tu_Dong', 'Delay', 'Dang_Mo', '2026-10-29 21:28:44', 'Tự động chuyển Trễ chuyến: quá 15 phút kể từ giờ cất cánh mà chưa xác nhận điều kiện bay.'),
(276, 197, NULL, 'Tu_Dong', 'Dang_Mo', 'Delay', '2026-10-29 21:28:44', 'Hết thời gian delay, chuyển về Đang mở. Lý do trễ trước đó: Chưa có lý do.'),
(277, 197, 1, 'Tre_Tiep', 'Delay', 'Dang_Mo', '2026-10-29 21:29:04', 'Chưa đủ điều kiện bay - trễ chuyến 19 phút. Lý do: test. Giờ cất cánh mới: 29/10/2026 21:31.'),
(278, 197, 1, 'Tre_Tiep', 'Delay', 'Delay', '2026-10-29 21:30:20', 'Chưa đủ yêu cầu an toàn - delay tiếp 1 phút. Lý do: 1. Giờ cất cánh mới: 29/10/2026 21:32.'),
(279, 197, 1, 'Xac_Nhan_Cat_Canh', 'Cat_Canh', 'Delay', '2026-10-29 21:32:05', 'Đã đủ yêu cầu an toàn và xác nhận cất cánh.'),
(280, 198, 1, 'Tre_Tiep', 'Delay', 'Dang_Mo', '2026-10-29 21:32:20', 'Chưa đủ điều kiện bay - trễ chuyến 8 phút. Lý do: A. Giờ cất cánh mới: 29/10/2026 21:34.'),
(281, 198, 1, 'Sua', 'Dang_Mo', 'Delay', '2026-10-29 21:33:49', 'Giờ dự kiến: 29/10/2026 21:34 -> 29/10/2026 21:34; Trạng thái: Trễ chuyến -> Đang mở'),
(282, 198, 1, 'Tre_Tiep', 'Delay', 'Dang_Mo', '2026-10-29 21:34:09', 'Chưa đủ điều kiện bay - trễ chuyến 1 phút. Lý do: B. Giờ cất cánh mới: 29/10/2026 21:35.'),
(283, 197, NULL, 'Tu_Dong', 'Ha_Canh', 'Cat_Canh', '2026-10-29 21:38:11', 'Tự động: đã tới giờ dự kiến hạ cánh.'),
(284, 198, NULL, 'Tu_Dong', 'Dang_Mo', 'Delay', '2026-10-29 21:38:11', 'Hết thời gian delay, chuyển về Đang mở. Lý do trễ trước đó: B.'),
(285, 198, 1, 'Tre_Tiep', 'Delay', 'Dang_Mo', '2026-10-29 21:38:29', 'Chưa đủ điều kiện bay - trễ chuyến 5 phút. Lý do: TEST. Giờ cất cánh mới: 29/10/2026 21:40.'),
(286, 198, NULL, 'Tu_Dong', 'Dang_Mo', 'Delay', '2026-10-29 21:40:41', 'Hết thời gian delay, chuyển về Đang mở. Lý do trễ trước đó: TEST.'),
(287, 198, 1, 'Tre_Tiep', 'Delay', 'Dang_Mo', '2026-10-29 21:40:54', 'Chưa đủ điều kiện bay - trễ chuyến 1 phút. Lý do: test lan 1. Giờ cất cánh mới: 29/10/2026 21:41.'),
(288, 198, NULL, 'Tu_Dong', 'Dang_Mo', 'Delay', '2026-10-29 21:42:00', 'Hết thời gian delay, chuyển về Đang mở. Lý do trễ trước đó: test lan 1.'),
(289, 198, 1, 'Tre_Tiep', 'Delay', 'Dang_Mo', '2026-10-29 21:44:17', 'Chưa đủ điều kiện bay - trễ chuyến 6 phút. Lý do: TEST LẦN 3. Giờ cất cánh mới: 29/10/2026 21:48.'),
(290, 187, NULL, 'Ghi_De_Tu_Lich', 'Ha_Canh', 'Ha_Canh', '2026-10-29 21:47:32', 'Ghi đè theo lịch bay mùa VN111: 28/10/2026 14:50 → 28/10/2026 08:00.'),
(291, 198, NULL, 'Tu_Dong', 'Dang_Mo', 'Delay', '2026-10-29 21:48:22', 'Hết thời gian delay, chuyển về Đang mở. Lý do trễ trước đó: TEST LẦN 3. Tổng thời gian đã delay: 21 phút.'),
(292, 198, 1, 'Tre_Tiep', 'Delay', 'Dang_Mo', '2026-10-29 21:54:03', 'Chưa đủ điều kiện bay - trễ chuyến 7 phút. Lý do: kiem tra lan 4. Giờ cất cánh mới: 29/10/2026 21:56.'),
(293, 198, NULL, 'Tu_Dong', 'Dang_Mo', 'Delay', '2026-10-29 21:56:06', 'Hết thời gian delay, chuyển về Đang mở. Lý do trễ trước đó: kiem tra lan 4.'),
(294, 198, 1, 'Tre_Tiep', 'Delay', 'Dang_Mo', '2026-10-29 21:56:19', 'Chưa đủ điều kiện bay - trễ chuyến 1 phút. Lý do: kiem trra lan 5. Giờ cất cánh mới: 29/10/2026 21:57.'),
(295, 198, NULL, 'Tu_Dong', 'Dang_Mo', 'Delay', '2026-10-29 21:57:34', 'Hết thời gian delay, chuyển về Đang mở. Lý do trễ trước đó: kiem trra lan 5.'),
(296, 198, 1, 'Tre_Tiep', 'Delay', 'Dang_Mo', '2026-10-29 21:58:12', 'Chưa đủ điều kiện bay - trễ chuyến 3 phút. Lý do: ktra lan 6. Giờ cất cánh mới: 29/10/2026 22:01.'),
(297, 198, NULL, 'Tu_Dong', 'Dang_Mo', 'Delay', '2026-10-29 22:01:21', 'Hết thời gian delay, chuyển về Đang mở. Lý do trễ trước đó: ktra lan 6.'),
(298, 198, 1, 'Tre_Tiep', 'Delay', 'Dang_Mo', '2026-10-29 22:03:34', 'Chưa đủ điều kiện bay - trễ chuyến 4 phút. Lý do: 1. Giờ cất cánh mới: 29/10/2026 22:05.'),
(299, 198, NULL, 'Tu_Dong', 'Dang_Mo', 'Delay', '2026-10-29 22:06:48', 'Hết thời gian delay, chuyển về Đang mở. Lý do trễ trước đó: 1.'),
(300, 198, 1, 'Tre_Tiep', 'Delay', 'Dang_Mo', '2026-10-29 22:07:30', 'Chưa đủ điều kiện bay - trễ chuyến 2 phút. Lý do: 2. Giờ cất cánh mới: 29/10/2026 22:09 (tổng lùi 3 phút).'),
(301, 198, NULL, 'Tu_Dong', 'Dang_Mo', 'Delay', '2026-10-29 22:11:33', 'Hết thời gian delay, chuyển về Đang mở. Lý do trễ trước đó: 2.'),
(302, 198, 1, 'Tre_Tiep', 'Delay', 'Dang_Mo', '2026-10-29 22:13:14', 'Chưa đủ điều kiện bay - trễ chuyến 1 phút. Lý do: 6. Giờ cất cánh mới: 29/10/2026 22:14 (tổng lùi 4 phút).'),
(303, 198, 1, 'Xac_Nhan_Cat_Canh', 'Cat_Canh', 'Delay', '2026-10-29 22:13:17', 'Chuyến bay TEST1213 đã khắc phục xong các sự cố trễ trước đó, Lý do A, B, TEST, test lan 1, TEST LẦN 3, kiem tra lan 4, kiem trra lan 5, ktra lan 6, 1, 2, 6. Nguyễn Đình Khánh xác nhận đủ điều kiện an toàn và cho cất cánh lúc 22:13. Cất cánh sớm hơn giờ d'),
(304, 198, NULL, 'Tu_Dong', 'Ha_Canh', 'Cat_Canh', '2026-10-29 22:13:17', 'Tự động: đã tới giờ dự kiến hạ cánh.'),
(305, 199, 1, 'Them', 'Dang_Mo', NULL, '2026-10-29 22:14:27', 'Thêm chuyến bay mới.'),
(306, 199, 1, 'Tre_Tiep', 'Delay', 'Dang_Mo', '2026-10-29 22:16:18', 'Chưa đủ điều kiện bay - trễ chuyến 1 phút. Lý do: test. Giờ cất cánh mới: 29/10/2026 22:17 (tổng lùi 1 phút).'),
(307, 199, NULL, 'Tu_Dong', 'Dang_Mo', 'Delay', '2026-10-29 22:17:38', 'Hết thời gian delay, chuyển về Đang mở. Lý do trễ trước đó: test.'),
(308, 199, 1, 'Tre_Tiep', 'Delay', 'Dang_Mo', '2026-10-29 22:18:44', 'Chưa đủ điều kiện bay - trễ chuyến 1 phút. Lý do: 1. Giờ cất cánh mới: 29/10/2026 22:19 (tổng lùi 2 phút).'),
(309, 199, NULL, 'Tu_Dong', 'Dang_Mo', 'Delay', '2026-10-29 22:19:50', 'Hết thời gian delay, chuyển về Đang mở. Lý do trễ trước đó: 1.'),
(310, 199, 1, 'Tre_Tiep', 'Delay', 'Dang_Mo', '2026-10-29 22:20:03', 'Chưa đủ điều kiện bay - trễ chuyến 3 phút. Lý do: 3. Giờ cất cánh mới: 29/10/2026 22:23 (tổng lùi 3 phút).'),
(311, 199, 1, 'Xac_Nhan_Cat_Canh', 'Cat_Canh', 'Delay', '2026-10-29 22:22:11', 'Chuyến bay TEST1231 đã khắc phục xong các sự cố trễ trước đó, Lý do test, 1, 3. Nguyễn Đình Khánh xác nhận đủ điều kiện an toàn và cho cất cánh lúc 22:22. Cất cánh sớm hơn giờ dự kiến (29/10/2026 22:23).'),
(312, 199, NULL, 'Tu_Dong', 'Ha_Canh', 'Cat_Canh', '2026-10-29 22:22:11', 'Tự động: đã tới giờ dự kiến hạ cánh.'),
(313, 200, 1, 'Them', 'Dang_Mo', NULL, '2026-10-29 22:25:52', 'Thêm chuyến bay mới.'),
(314, 200, 1, 'Tre_Tiep', 'Delay', 'Dang_Mo', '2026-10-29 22:27:49', 'Chưa đủ điều kiện bay - trễ chuyến 2 phút. Lý do: 22. Giờ cất cánh mới: 29/10/2026 22:29 (tổng lùi 2 phút).'),
(315, 200, NULL, 'Tu_Dong', 'Dang_Mo', 'Delay', '2026-10-29 22:30:04', 'Hết thời gian delay, chuyển về Đang mở. Lý do trễ trước đó: 22.'),
(316, 200, 1, 'Tre_Tiep', 'Delay', 'Dang_Mo', '2026-10-29 22:30:14', 'Chưa đủ điều kiện bay - trễ chuyến 4 phút. Lý do: 4. Giờ cất cánh mới: 29/10/2026 22:34 (tổng lùi 4 phút).'),
(317, 200, NULL, 'Tu_Dong', 'Dang_Mo', 'Delay', '2026-10-29 22:34:21', 'Hết thời gian delay, chuyển về Đang mở. Lý do trễ trước đó: 4.'),
(318, 200, 1, 'Xac_Nhan_Cat_Canh', 'Cat_Canh', 'Dang_Mo', '2026-10-29 22:34:23', 'Đã đủ điều kiện bay và xác nhận cất cánh.'),
(319, 200, NULL, 'Tu_Dong', 'Ha_Canh', 'Cat_Canh', '2026-10-29 22:34:23', 'Tự động: đã tới giờ dự kiến hạ cánh.'),
(320, 201, 1, 'Them', 'Dang_Mo', NULL, '2026-10-29 22:41:33', 'Thêm chuyến bay mới.'),
(321, 201, 1, 'Tre_Tiep', 'Delay', 'Dang_Mo', '2026-10-29 22:44:02', 'Chưa đủ điều kiện bay - trễ chuyến 2 phút. Lý do: lan 1. Giờ cất cánh mới: 29/10/2026 22:46 (tổng lùi 3 phút).'),
(322, 201, NULL, 'Tu_Dong', 'Dang_Mo', 'Delay', '2026-10-29 22:46:30', 'Hết thời gian delay, chuyển về Đang mở. Lý do trễ trước đó: lan 1.'),
(323, 201, 1, 'Xac_Nhan_Cat_Canh', 'Cat_Canh', 'Dang_Mo', '2026-10-29 22:46:35', 'Đã đủ điều kiện bay và xác nhận cất cánh.'),
(324, 201, NULL, 'Tu_Dong', 'Ha_Canh', 'Cat_Canh', '2026-10-29 22:50:01', 'Tự động: đã tới giờ dự kiến hạ cánh.'),
(325, 202, 1, 'Them', 'Dang_Mo', NULL, '2026-10-29 22:52:52', 'Thêm chuyến bay mới.'),
(326, 202, 1, 'Sua', 'Dang_Mo', 'Dang_Mo', '2026-10-29 22:54:05', 'Giờ thực tế: 29/10/2026 23:02 -> 29/10/2026 22:58'),
(327, 202, 1, 'Tre_Tiep', 'Delay', 'Dang_Mo', '2026-10-29 22:55:12', 'Chưa đủ điều kiện bay - trễ chuyến 3 phút. Lý do: thoi tiet xau. Giờ cất cánh mới: 29/10/2026 22:58 (tổng lùi 3 phút).'),
(328, 202, NULL, 'Tu_Dong', 'Dang_Mo', 'Delay', '2026-10-29 22:58:50', 'Hết thời gian delay, chuyển về Đang mở. Lý do trễ trước đó: thoi tiet xau.'),
(329, 202, 1, 'Xac_Nhan_Cat_Canh', 'Cat_Canh', 'Dang_Mo', '2026-10-29 23:00:08', 'Đã đủ điều kiện bay và xác nhận cất cánh.'),
(330, 202, NULL, 'Tu_Dong', 'Ha_Canh', 'Cat_Canh', '2026-10-29 23:02:30', 'Tự động: đã tới giờ dự kiến hạ cánh.'),
(331, 203, 1, 'Them', 'Dang_Mo', NULL, '2026-10-29 23:03:23', 'Thêm chuyến bay mới.'),
(332, 203, 1, 'Tre_Tiep', 'Delay', 'Dang_Mo', '2026-10-29 23:05:21', 'Chưa đủ điều kiện bay - trễ chuyến 3 phút. Lý do: ket duong bay. Giờ cất cánh mới: 29/10/2026 23:08 (tổng lùi 4 phút).'),
(333, 203, NULL, 'Tu_Dong', 'Dang_Mo', 'Delay', '2026-10-29 23:08:58', 'Hết thời gian delay, chuyển về Đang mở. Lý do trễ trước đó: ket duong bay.'),
(334, 203, 1, 'Tre_Tiep', 'Delay', 'Dang_Mo', '2026-10-29 23:09:21', 'Chưa đủ điều kiện bay - trễ chuyến 5 phút. Lý do: tét. Giờ cất cánh mới: 29/10/2026 23:14 (tổng lùi 6 phút).'),
(335, 204, 1, 'Them', 'Dang_Mo', NULL, '2026-10-29 23:11:18', 'Thêm chuyến bay mới.'),
(336, 204, 1, 'Tre_Tiep', 'Delay', 'Dang_Mo', '2026-10-29 23:13:41', 'Chưa đủ điều kiện bay - trễ chuyến 3 phút. Lý do: ktra l1. Giờ cất cánh mới: 29/10/2026 23:16 (tổng lùi 3 phút).'),
(337, 203, NULL, 'Tu_Dong', 'Dang_Mo', 'Delay', '2026-10-29 23:14:48', 'Hết thời gian delay, chuyển về Đang mở. Lý do trễ trước đó: tét.'),
(338, 203, 1, 'Xac_Nhan_Cat_Canh', 'Cat_Canh', 'Dang_Mo', '2026-10-29 23:15:02', 'Đã đủ điều kiện bay và xác nhận cất cánh.'),
(339, 204, 1, 'Tre_Tiep', 'Delay', 'Delay', '2026-10-29 23:16:03', 'Chưa đủ yêu cầu an toàn - delay tiếp 2 phút. Lý do: w. Giờ cất cánh mới: 29/10/2026 23:18 (tổng lùi 2 phút).'),
(340, 204, 1, 'Tre_Tiep', 'Delay', 'Delay', '2026-10-29 23:16:18', 'Chưa đủ yêu cầu an toàn - delay tiếp 3 phút. Lý do: 3. Giờ cất cánh mới: 29/10/2026 23:21 (tổng lùi 3 phút).'),
(341, 204, 1, 'Tre_Tiep', 'Delay', 'Delay', '2026-10-29 23:17:54', 'Chưa đủ yêu cầu an toàn - delay tiếp 1 phút. Lý do: 2. Giờ cất cánh mới: 29/10/2026 23:22 (tổng lùi 1 phút).'),
(342, 203, NULL, 'Tu_Dong', 'Ha_Canh', 'Cat_Canh', '2026-10-29 23:18:17', 'Tự động: đã tới giờ dự kiến hạ cánh.'),
(343, 204, 1, 'Xac_Nhan_Cat_Canh', 'Cat_Canh', 'Delay', '2026-10-29 23:19:17', 'Chuyến bay VN7689 đã khắc phục xong các sự cố trễ trước đó, Lý do ktra l1, w, 3, 2. Nguyễn Đình Khánh xác nhận đủ điều kiện an toàn và cho cất cánh lúc 23:19. Cất cánh sớm hơn giờ dự kiến (29/10/2026 23:22).'),
(344, 204, NULL, 'Tu_Dong', 'Ha_Canh', 'Cat_Canh', '2026-10-29 23:31:42', 'Tự động: đã tới giờ dự kiến hạ cánh.'),
(345, 40, NULL, 'Tu_Dong', 'Delay', 'Dang_Mo', '2026-10-30 18:12:39', 'Tự động chuyển Trễ chuyến: quá 15 phút kể từ giờ cất cánh mà chưa xác nhận điều kiện bay.'),
(346, 45, NULL, 'Tu_Dong', 'Delay', 'Dang_Mo', '2026-10-30 18:12:39', 'Tự động chuyển Trễ chuyến: quá 15 phút kể từ giờ cất cánh mà chưa xác nhận điều kiện bay.'),
(347, 89, NULL, 'Tu_Dong', 'Delay', 'Dang_Mo', '2026-10-30 18:12:39', 'Tự động chuyển Trễ chuyến: quá 15 phút kể từ giờ cất cánh mà chưa xác nhận điều kiện bay.'),
(348, 40, NULL, 'Tu_Dong', 'Dang_Mo', 'Delay', '2026-10-30 18:12:39', 'Hết thời gian delay, chuyển về Đang mở. Lý do trễ trước đó: Chưa có lý do.'),
(349, 45, NULL, 'Tu_Dong', 'Dang_Mo', 'Delay', '2026-10-30 18:12:39', 'Hết thời gian delay, chuyển về Đang mở. Lý do trễ trước đó: Chưa có lý do.'),
(350, 89, NULL, 'Tu_Dong', 'Dang_Mo', 'Delay', '2026-10-30 18:12:39', 'Hết thời gian delay, chuyển về Đang mở. Lý do trễ trước đó: Chưa có lý do.'),
(351, 40, NULL, 'Tu_Dong', 'Delay', 'Dang_Mo', '2026-10-30 18:13:07', 'Tự động chuyển Trễ chuyến: quá 15 phút kể từ giờ cất cánh mà chưa xác nhận điều kiện bay.'),
(352, 45, NULL, 'Tu_Dong', 'Delay', 'Dang_Mo', '2026-10-30 18:13:07', 'Tự động chuyển Trễ chuyến: quá 15 phút kể từ giờ cất cánh mà chưa xác nhận điều kiện bay.'),
(353, 89, NULL, 'Tu_Dong', 'Delay', 'Dang_Mo', '2026-10-30 18:13:07', 'Tự động chuyển Trễ chuyến: quá 15 phút kể từ giờ cất cánh mà chưa xác nhận điều kiện bay.'),
(354, 40, NULL, 'Tu_Dong', 'Dang_Mo', 'Delay', '2026-10-30 18:13:07', 'Hết thời gian delay, chuyển về Đang mở. Lý do trễ trước đó: Chưa có lý do.'),
(355, 45, NULL, 'Tu_Dong', 'Dang_Mo', 'Delay', '2026-10-30 18:13:07', 'Hết thời gian delay, chuyển về Đang mở. Lý do trễ trước đó: Chưa có lý do.'),
(356, 89, NULL, 'Tu_Dong', 'Dang_Mo', 'Delay', '2026-10-30 18:13:07', 'Hết thời gian delay, chuyển về Đang mở. Lý do trễ trước đó: Chưa có lý do.'),
(357, 40, NULL, 'Tu_Dong', 'Delay', 'Dang_Mo', '2026-10-30 18:13:30', 'Tự động chuyển Trễ chuyến: quá 15 phút kể từ giờ cất cánh mà chưa xác nhận điều kiện bay.'),
(358, 45, NULL, 'Tu_Dong', 'Delay', 'Dang_Mo', '2026-10-30 18:13:30', 'Tự động chuyển Trễ chuyến: quá 15 phút kể từ giờ cất cánh mà chưa xác nhận điều kiện bay.'),
(359, 89, NULL, 'Tu_Dong', 'Delay', 'Dang_Mo', '2026-10-30 18:13:30', 'Tự động chuyển Trễ chuyến: quá 15 phút kể từ giờ cất cánh mà chưa xác nhận điều kiện bay.'),
(360, 40, NULL, 'Tu_Dong', 'Dang_Mo', 'Delay', '2026-10-30 18:13:30', 'Hết thời gian delay, chuyển về Đang mở. Lý do trễ trước đó: Chưa có lý do.'),
(361, 45, NULL, 'Tu_Dong', 'Dang_Mo', 'Delay', '2026-10-30 18:13:30', 'Hết thời gian delay, chuyển về Đang mở. Lý do trễ trước đó: Chưa có lý do.'),
(362, 89, NULL, 'Tu_Dong', 'Dang_Mo', 'Delay', '2026-10-30 18:13:30', 'Hết thời gian delay, chuyển về Đang mở. Lý do trễ trước đó: Chưa có lý do.'),
(363, 40, NULL, 'Tu_Dong', 'Delay', 'Dang_Mo', '2026-10-30 18:13:31', 'Tự động chuyển Trễ chuyến: quá 15 phút kể từ giờ cất cánh mà chưa xác nhận điều kiện bay.'),
(364, 45, NULL, 'Tu_Dong', 'Delay', 'Dang_Mo', '2026-10-30 18:13:31', 'Tự động chuyển Trễ chuyến: quá 15 phút kể từ giờ cất cánh mà chưa xác nhận điều kiện bay.'),
(365, 89, NULL, 'Tu_Dong', 'Delay', 'Dang_Mo', '2026-10-30 18:13:31', 'Tự động chuyển Trễ chuyến: quá 15 phút kể từ giờ cất cánh mà chưa xác nhận điều kiện bay.'),
(366, 40, NULL, 'Tu_Dong', 'Dang_Mo', 'Delay', '2026-10-30 18:13:31', 'Hết thời gian delay, chuyển về Đang mở. Lý do trễ trước đó: Chưa có lý do.'),
(367, 45, NULL, 'Tu_Dong', 'Dang_Mo', 'Delay', '2026-10-30 18:13:31', 'Hết thời gian delay, chuyển về Đang mở. Lý do trễ trước đó: Chưa có lý do.'),
(368, 89, NULL, 'Tu_Dong', 'Dang_Mo', 'Delay', '2026-10-30 18:13:31', 'Hết thời gian delay, chuyển về Đang mở. Lý do trễ trước đó: Chưa có lý do.'),
(369, 40, NULL, 'Tu_Dong', 'Delay', 'Dang_Mo', '2026-10-30 18:13:32', 'Tự động chuyển Trễ chuyến: quá 15 phút kể từ giờ cất cánh mà chưa xác nhận điều kiện bay.'),
(370, 45, NULL, 'Tu_Dong', 'Delay', 'Dang_Mo', '2026-10-30 18:13:32', 'Tự động chuyển Trễ chuyến: quá 15 phút kể từ giờ cất cánh mà chưa xác nhận điều kiện bay.'),
(371, 89, NULL, 'Tu_Dong', 'Delay', 'Dang_Mo', '2026-10-30 18:13:32', 'Tự động chuyển Trễ chuyến: quá 15 phút kể từ giờ cất cánh mà chưa xác nhận điều kiện bay.'),
(372, 40, NULL, 'Tu_Dong', 'Dang_Mo', 'Delay', '2026-10-30 18:13:32', 'Hết thời gian delay, chuyển về Đang mở. Lý do trễ trước đó: Chưa có lý do.'),
(373, 45, NULL, 'Tu_Dong', 'Dang_Mo', 'Delay', '2026-10-30 18:13:32', 'Hết thời gian delay, chuyển về Đang mở. Lý do trễ trước đó: Chưa có lý do.'),
(374, 89, NULL, 'Tu_Dong', 'Dang_Mo', 'Delay', '2026-10-30 18:13:32', 'Hết thời gian delay, chuyển về Đang mở. Lý do trễ trước đó: Chưa có lý do.'),
(375, 40, NULL, 'Tu_Dong', 'Delay', 'Dang_Mo', '2026-10-30 18:13:33', 'Tự động chuyển Trễ chuyến: quá 15 phút kể từ giờ cất cánh mà chưa xác nhận điều kiện bay.'),
(376, 45, NULL, 'Tu_Dong', 'Delay', 'Dang_Mo', '2026-10-30 18:13:33', 'Tự động chuyển Trễ chuyến: quá 15 phút kể từ giờ cất cánh mà chưa xác nhận điều kiện bay.'),
(377, 89, NULL, 'Tu_Dong', 'Delay', 'Dang_Mo', '2026-10-30 18:13:33', 'Tự động chuyển Trễ chuyến: quá 15 phút kể từ giờ cất cánh mà chưa xác nhận điều kiện bay.'),
(378, 40, NULL, 'Tu_Dong', 'Dang_Mo', 'Delay', '2026-10-30 18:13:33', 'Hết thời gian delay, chuyển về Đang mở. Lý do trễ trước đó: Chưa có lý do.'),
(379, 45, NULL, 'Tu_Dong', 'Dang_Mo', 'Delay', '2026-10-30 18:13:33', 'Hết thời gian delay, chuyển về Đang mở. Lý do trễ trước đó: Chưa có lý do.'),
(380, 89, NULL, 'Tu_Dong', 'Dang_Mo', 'Delay', '2026-10-30 18:13:33', 'Hết thời gian delay, chuyển về Đang mở. Lý do trễ trước đó: Chưa có lý do.'),
(381, 40, NULL, 'Tu_Dong', 'Delay', 'Dang_Mo', '2026-10-30 18:13:34', 'Tự động chuyển Trễ chuyến: quá 15 phút kể từ giờ cất cánh mà chưa xác nhận điều kiện bay.'),
(382, 45, NULL, 'Tu_Dong', 'Delay', 'Dang_Mo', '2026-10-30 18:13:34', 'Tự động chuyển Trễ chuyến: quá 15 phút kể từ giờ cất cánh mà chưa xác nhận điều kiện bay.'),
(383, 89, NULL, 'Tu_Dong', 'Delay', 'Dang_Mo', '2026-10-30 18:13:34', 'Tự động chuyển Trễ chuyến: quá 15 phút kể từ giờ cất cánh mà chưa xác nhận điều kiện bay.'),
(384, 40, NULL, 'Tu_Dong', 'Dang_Mo', 'Delay', '2026-10-30 18:13:34', 'Hết thời gian delay, chuyển về Đang mở. Lý do trễ trước đó: Chưa có lý do.'),
(385, 45, NULL, 'Tu_Dong', 'Dang_Mo', 'Delay', '2026-10-30 18:13:34', 'Hết thời gian delay, chuyển về Đang mở. Lý do trễ trước đó: Chưa có lý do.'),
(386, 89, NULL, 'Tu_Dong', 'Dang_Mo', 'Delay', '2026-10-30 18:13:34', 'Hết thời gian delay, chuyển về Đang mở. Lý do trễ trước đó: Chưa có lý do.'),
(387, 40, NULL, 'Tu_Dong', 'Delay', 'Dang_Mo', '2026-10-30 18:13:34', 'Tự động chuyển Trễ chuyến: quá 15 phút kể từ giờ cất cánh mà chưa xác nhận điều kiện bay.'),
(388, 45, NULL, 'Tu_Dong', 'Delay', 'Dang_Mo', '2026-10-30 18:13:34', 'Tự động chuyển Trễ chuyến: quá 15 phút kể từ giờ cất cánh mà chưa xác nhận điều kiện bay.'),
(389, 89, NULL, 'Tu_Dong', 'Delay', 'Dang_Mo', '2026-10-30 18:13:34', 'Tự động chuyển Trễ chuyến: quá 15 phút kể từ giờ cất cánh mà chưa xác nhận điều kiện bay.'),
(390, 40, NULL, 'Tu_Dong', 'Dang_Mo', 'Delay', '2026-10-30 18:13:34', 'Hết thời gian delay, chuyển về Đang mở. Lý do trễ trước đó: Chưa có lý do.'),
(391, 45, NULL, 'Tu_Dong', 'Dang_Mo', 'Delay', '2026-10-30 18:13:34', 'Hết thời gian delay, chuyển về Đang mở. Lý do trễ trước đó: Chưa có lý do.'),
(392, 89, NULL, 'Tu_Dong', 'Dang_Mo', 'Delay', '2026-10-30 18:13:34', 'Hết thời gian delay, chuyển về Đang mở. Lý do trễ trước đó: Chưa có lý do.'),
(393, 40, NULL, 'Tu_Dong', 'Delay', 'Dang_Mo', '2026-10-30 18:13:34', 'Tự động chuyển Trễ chuyến: quá 15 phút kể từ giờ cất cánh mà chưa xác nhận điều kiện bay.'),
(394, 45, NULL, 'Tu_Dong', 'Delay', 'Dang_Mo', '2026-10-30 18:13:34', 'Tự động chuyển Trễ chuyến: quá 15 phút kể từ giờ cất cánh mà chưa xác nhận điều kiện bay.'),
(395, 89, NULL, 'Tu_Dong', 'Delay', 'Dang_Mo', '2026-10-30 18:13:34', 'Tự động chuyển Trễ chuyến: quá 15 phút kể từ giờ cất cánh mà chưa xác nhận điều kiện bay.'),
(396, 40, NULL, 'Tu_Dong', 'Dang_Mo', 'Delay', '2026-10-30 18:13:34', 'Hết thời gian delay, chuyển về Đang mở. Lý do trễ trước đó: Chưa có lý do.'),
(397, 45, NULL, 'Tu_Dong', 'Dang_Mo', 'Delay', '2026-10-30 18:13:34', 'Hết thời gian delay, chuyển về Đang mở. Lý do trễ trước đó: Chưa có lý do.'),
(398, 89, NULL, 'Tu_Dong', 'Dang_Mo', 'Delay', '2026-10-30 18:13:34', 'Hết thời gian delay, chuyển về Đang mở. Lý do trễ trước đó: Chưa có lý do.'),
(399, 40, NULL, 'Tu_Dong', 'Delay', 'Dang_Mo', '2026-10-30 18:13:35', 'Tự động chuyển Trễ chuyến: quá 15 phút kể từ giờ cất cánh mà chưa xác nhận điều kiện bay.'),
(400, 45, NULL, 'Tu_Dong', 'Delay', 'Dang_Mo', '2026-10-30 18:13:35', 'Tự động chuyển Trễ chuyến: quá 15 phút kể từ giờ cất cánh mà chưa xác nhận điều kiện bay.'),
(401, 89, NULL, 'Tu_Dong', 'Delay', 'Dang_Mo', '2026-10-30 18:13:35', 'Tự động chuyển Trễ chuyến: quá 15 phút kể từ giờ cất cánh mà chưa xác nhận điều kiện bay.'),
(402, 40, NULL, 'Tu_Dong', 'Dang_Mo', 'Delay', '2026-10-30 18:13:35', 'Hết thời gian delay, chuyển về Đang mở. Lý do trễ trước đó: Chưa có lý do.'),
(403, 45, NULL, 'Tu_Dong', 'Dang_Mo', 'Delay', '2026-10-30 18:13:35', 'Hết thời gian delay, chuyển về Đang mở. Lý do trễ trước đó: Chưa có lý do.'),
(404, 89, NULL, 'Tu_Dong', 'Dang_Mo', 'Delay', '2026-10-30 18:13:35', 'Hết thời gian delay, chuyển về Đang mở. Lý do trễ trước đó: Chưa có lý do.'),
(405, 40, NULL, 'Tu_Dong', 'Delay', 'Dang_Mo', '2026-10-30 18:13:35', 'Tự động chuyển Trễ chuyến: quá 15 phút kể từ giờ cất cánh mà chưa xác nhận điều kiện bay.'),
(406, 45, NULL, 'Tu_Dong', 'Delay', 'Dang_Mo', '2026-10-30 18:13:35', 'Tự động chuyển Trễ chuyến: quá 15 phút kể từ giờ cất cánh mà chưa xác nhận điều kiện bay.'),
(407, 89, NULL, 'Tu_Dong', 'Delay', 'Dang_Mo', '2026-10-30 18:13:35', 'Tự động chuyển Trễ chuyến: quá 15 phút kể từ giờ cất cánh mà chưa xác nhận điều kiện bay.'),
(408, 40, NULL, 'Tu_Dong', 'Dang_Mo', 'Delay', '2026-10-30 18:13:35', 'Hết thời gian delay, chuyển về Đang mở. Lý do trễ trước đó: Chưa có lý do.'),
(409, 45, NULL, 'Tu_Dong', 'Dang_Mo', 'Delay', '2026-10-30 18:13:35', 'Hết thời gian delay, chuyển về Đang mở. Lý do trễ trước đó: Chưa có lý do.'),
(410, 89, NULL, 'Tu_Dong', 'Dang_Mo', 'Delay', '2026-10-30 18:13:35', 'Hết thời gian delay, chuyển về Đang mở. Lý do trễ trước đó: Chưa có lý do.'),
(411, 40, NULL, 'Tu_Dong', 'Delay', 'Dang_Mo', '2026-10-30 18:13:36', 'Tự động chuyển Trễ chuyến: quá 15 phút kể từ giờ cất cánh mà chưa xác nhận điều kiện bay.'),
(412, 45, NULL, 'Tu_Dong', 'Delay', 'Dang_Mo', '2026-10-30 18:13:36', 'Tự động chuyển Trễ chuyến: quá 15 phút kể từ giờ cất cánh mà chưa xác nhận điều kiện bay.'),
(413, 89, NULL, 'Tu_Dong', 'Delay', 'Dang_Mo', '2026-10-30 18:13:36', 'Tự động chuyển Trễ chuyến: quá 15 phút kể từ giờ cất cánh mà chưa xác nhận điều kiện bay.'),
(414, 40, NULL, 'Tu_Dong', 'Dang_Mo', 'Delay', '2026-10-30 18:13:36', 'Hết thời gian delay, chuyển về Đang mở. Lý do trễ trước đó: Chưa có lý do.'),
(415, 45, NULL, 'Tu_Dong', 'Dang_Mo', 'Delay', '2026-10-30 18:13:36', 'Hết thời gian delay, chuyển về Đang mở. Lý do trễ trước đó: Chưa có lý do.'),
(416, 89, NULL, 'Tu_Dong', 'Dang_Mo', 'Delay', '2026-10-30 18:13:36', 'Hết thời gian delay, chuyển về Đang mở. Lý do trễ trước đó: Chưa có lý do.'),
(417, 40, NULL, 'Tu_Dong', 'Delay', 'Dang_Mo', '2026-10-30 18:13:50', 'Tự động chuyển Trễ chuyến: quá 15 phút kể từ giờ cất cánh mà chưa xác nhận điều kiện bay.'),
(418, 45, NULL, 'Tu_Dong', 'Delay', 'Dang_Mo', '2026-10-30 18:13:50', 'Tự động chuyển Trễ chuyến: quá 15 phút kể từ giờ cất cánh mà chưa xác nhận điều kiện bay.'),
(419, 89, NULL, 'Tu_Dong', 'Delay', 'Dang_Mo', '2026-10-30 18:13:50', 'Tự động chuyển Trễ chuyến: quá 15 phút kể từ giờ cất cánh mà chưa xác nhận điều kiện bay.'),
(420, 40, NULL, 'Tu_Dong', 'Dang_Mo', 'Delay', '2026-10-30 18:13:50', 'Hết thời gian delay, chuyển về Đang mở. Lý do trễ trước đó: Chưa có lý do.'),
(421, 45, NULL, 'Tu_Dong', 'Dang_Mo', 'Delay', '2026-10-30 18:13:50', 'Hết thời gian delay, chuyển về Đang mở. Lý do trễ trước đó: Chưa có lý do.'),
(422, 89, NULL, 'Tu_Dong', 'Dang_Mo', 'Delay', '2026-10-30 18:13:50', 'Hết thời gian delay, chuyển về Đang mở. Lý do trễ trước đó: Chưa có lý do.'),
(423, 40, NULL, 'Tu_Dong', 'Delay', 'Dang_Mo', '2026-10-30 18:13:52', 'Tự động chuyển Trễ chuyến: quá 15 phút kể từ giờ cất cánh mà chưa xác nhận điều kiện bay.'),
(424, 45, NULL, 'Tu_Dong', 'Delay', 'Dang_Mo', '2026-10-30 18:13:52', 'Tự động chuyển Trễ chuyến: quá 15 phút kể từ giờ cất cánh mà chưa xác nhận điều kiện bay.'),
(425, 89, NULL, 'Tu_Dong', 'Delay', 'Dang_Mo', '2026-10-30 18:13:52', 'Tự động chuyển Trễ chuyến: quá 15 phút kể từ giờ cất cánh mà chưa xác nhận điều kiện bay.'),
(426, 40, NULL, 'Tu_Dong', 'Dang_Mo', 'Delay', '2026-10-30 18:13:52', 'Hết thời gian delay, chuyển về Đang mở. Lý do trễ trước đó: Chưa có lý do.'),
(427, 45, NULL, 'Tu_Dong', 'Dang_Mo', 'Delay', '2026-10-30 18:13:52', 'Hết thời gian delay, chuyển về Đang mở. Lý do trễ trước đó: Chưa có lý do.'),
(428, 89, NULL, 'Tu_Dong', 'Dang_Mo', 'Delay', '2026-10-30 18:13:52', 'Hết thời gian delay, chuyển về Đang mở. Lý do trễ trước đó: Chưa có lý do.'),
(429, 40, NULL, 'Tu_Dong', 'Delay', 'Dang_Mo', '2026-10-30 18:13:53', 'Tự động chuyển Trễ chuyến: quá 15 phút kể từ giờ cất cánh mà chưa xác nhận điều kiện bay.'),
(430, 45, NULL, 'Tu_Dong', 'Delay', 'Dang_Mo', '2026-10-30 18:13:53', 'Tự động chuyển Trễ chuyến: quá 15 phút kể từ giờ cất cánh mà chưa xác nhận điều kiện bay.'),
(431, 89, NULL, 'Tu_Dong', 'Delay', 'Dang_Mo', '2026-10-30 18:13:53', 'Tự động chuyển Trễ chuyến: quá 15 phút kể từ giờ cất cánh mà chưa xác nhận điều kiện bay.'),
(432, 40, NULL, 'Tu_Dong', 'Dang_Mo', 'Delay', '2026-10-30 18:13:53', 'Hết thời gian delay, chuyển về Đang mở. Lý do trễ trước đó: Chưa có lý do.'),
(433, 45, NULL, 'Tu_Dong', 'Dang_Mo', 'Delay', '2026-10-30 18:13:53', 'Hết thời gian delay, chuyển về Đang mở. Lý do trễ trước đó: Chưa có lý do.'),
(434, 89, NULL, 'Tu_Dong', 'Dang_Mo', 'Delay', '2026-10-30 18:13:53', 'Hết thời gian delay, chuyển về Đang mở. Lý do trễ trước đó: Chưa có lý do.'),
(435, 40, NULL, 'Tu_Dong', 'Delay', 'Dang_Mo', '2026-10-30 18:13:53', 'Tự động chuyển Trễ chuyến: quá 15 phút kể từ giờ cất cánh mà chưa xác nhận điều kiện bay.'),
(436, 45, NULL, 'Tu_Dong', 'Delay', 'Dang_Mo', '2026-10-30 18:13:53', 'Tự động chuyển Trễ chuyến: quá 15 phút kể từ giờ cất cánh mà chưa xác nhận điều kiện bay.'),
(437, 89, NULL, 'Tu_Dong', 'Delay', 'Dang_Mo', '2026-10-30 18:13:53', 'Tự động chuyển Trễ chuyến: quá 15 phút kể từ giờ cất cánh mà chưa xác nhận điều kiện bay.'),
(438, 40, NULL, 'Tu_Dong', 'Dang_Mo', 'Delay', '2026-10-30 18:13:53', 'Hết thời gian delay, chuyển về Đang mở. Lý do trễ trước đó: Chưa có lý do.'),
(439, 45, NULL, 'Tu_Dong', 'Dang_Mo', 'Delay', '2026-10-30 18:13:53', 'Hết thời gian delay, chuyển về Đang mở. Lý do trễ trước đó: Chưa có lý do.'),
(440, 89, NULL, 'Tu_Dong', 'Dang_Mo', 'Delay', '2026-10-30 18:13:53', 'Hết thời gian delay, chuyển về Đang mở. Lý do trễ trước đó: Chưa có lý do.'),
(441, 40, NULL, 'Tu_Dong', 'Delay', 'Dang_Mo', '2026-10-30 18:14:00', 'Tự động chuyển Trễ chuyến: quá 15 phút kể từ giờ cất cánh mà chưa xác nhận điều kiện bay.'),
(442, 45, NULL, 'Tu_Dong', 'Delay', 'Dang_Mo', '2026-10-30 18:14:00', 'Tự động chuyển Trễ chuyến: quá 15 phút kể từ giờ cất cánh mà chưa xác nhận điều kiện bay.');
INSERT INTO `nhat_ky_trang_thai_bay` (`id`, `id_chuyen_bay`, `id_nguoi_dung`, `hanh_dong`, `trang_thai`, `trang_thai_cu`, `thoi_gian_cap_nhat`, `ghi_chu`) VALUES
(443, 89, NULL, 'Tu_Dong', 'Delay', 'Dang_Mo', '2026-10-30 18:14:00', 'Tự động chuyển Trễ chuyến: quá 15 phút kể từ giờ cất cánh mà chưa xác nhận điều kiện bay.'),
(444, 40, NULL, 'Tu_Dong', 'Dang_Mo', 'Delay', '2026-10-30 18:14:00', 'Hết thời gian delay, chuyển về Đang mở. Lý do trễ trước đó: Chưa có lý do.'),
(445, 45, NULL, 'Tu_Dong', 'Dang_Mo', 'Delay', '2026-10-30 18:14:00', 'Hết thời gian delay, chuyển về Đang mở. Lý do trễ trước đó: Chưa có lý do.'),
(446, 89, NULL, 'Tu_Dong', 'Dang_Mo', 'Delay', '2026-10-30 18:14:00', 'Hết thời gian delay, chuyển về Đang mở. Lý do trễ trước đó: Chưa có lý do.'),
(447, 40, NULL, 'Tu_Dong', 'Delay', 'Dang_Mo', '2026-10-30 18:14:17', 'Tự động chuyển Trễ chuyến: quá 15 phút kể từ giờ cất cánh mà chưa xác nhận điều kiện bay.'),
(448, 45, NULL, 'Tu_Dong', 'Delay', 'Dang_Mo', '2026-10-30 18:14:17', 'Tự động chuyển Trễ chuyến: quá 15 phút kể từ giờ cất cánh mà chưa xác nhận điều kiện bay.'),
(449, 89, NULL, 'Tu_Dong', 'Delay', 'Dang_Mo', '2026-10-30 18:14:17', 'Tự động chuyển Trễ chuyến: quá 15 phút kể từ giờ cất cánh mà chưa xác nhận điều kiện bay.'),
(450, 40, NULL, 'Tu_Dong', 'Dang_Mo', 'Delay', '2026-10-30 18:14:17', 'Hết thời gian delay, chuyển về Đang mở. Lý do trễ trước đó: Chưa có lý do.'),
(451, 45, NULL, 'Tu_Dong', 'Dang_Mo', 'Delay', '2026-10-30 18:14:17', 'Hết thời gian delay, chuyển về Đang mở. Lý do trễ trước đó: Chưa có lý do.'),
(452, 89, NULL, 'Tu_Dong', 'Dang_Mo', 'Delay', '2026-10-30 18:14:17', 'Hết thời gian delay, chuyển về Đang mở. Lý do trễ trước đó: Chưa có lý do.'),
(453, 40, NULL, 'Tu_Dong', 'Delay', 'Dang_Mo', '2026-10-30 18:14:18', 'Tự động chuyển Trễ chuyến: quá 15 phút kể từ giờ cất cánh mà chưa xác nhận điều kiện bay.'),
(454, 45, NULL, 'Tu_Dong', 'Delay', 'Dang_Mo', '2026-10-30 18:14:18', 'Tự động chuyển Trễ chuyến: quá 15 phút kể từ giờ cất cánh mà chưa xác nhận điều kiện bay.'),
(455, 89, NULL, 'Tu_Dong', 'Delay', 'Dang_Mo', '2026-10-30 18:14:18', 'Tự động chuyển Trễ chuyến: quá 15 phút kể từ giờ cất cánh mà chưa xác nhận điều kiện bay.'),
(456, 40, NULL, 'Tu_Dong', 'Dang_Mo', 'Delay', '2026-10-30 18:14:18', 'Hết thời gian delay, chuyển về Đang mở. Lý do trễ trước đó: Chưa có lý do.'),
(457, 45, NULL, 'Tu_Dong', 'Dang_Mo', 'Delay', '2026-10-30 18:14:18', 'Hết thời gian delay, chuyển về Đang mở. Lý do trễ trước đó: Chưa có lý do.'),
(458, 89, NULL, 'Tu_Dong', 'Dang_Mo', 'Delay', '2026-10-30 18:14:18', 'Hết thời gian delay, chuyển về Đang mở. Lý do trễ trước đó: Chưa có lý do.'),
(459, 40, NULL, 'Tu_Dong', 'Delay', 'Dang_Mo', '2026-10-30 18:14:18', 'Tự động chuyển Trễ chuyến: quá 15 phút kể từ giờ cất cánh mà chưa xác nhận điều kiện bay.'),
(460, 45, NULL, 'Tu_Dong', 'Delay', 'Dang_Mo', '2026-10-30 18:14:18', 'Tự động chuyển Trễ chuyến: quá 15 phút kể từ giờ cất cánh mà chưa xác nhận điều kiện bay.'),
(461, 89, NULL, 'Tu_Dong', 'Delay', 'Dang_Mo', '2026-10-30 18:14:18', 'Tự động chuyển Trễ chuyến: quá 15 phút kể từ giờ cất cánh mà chưa xác nhận điều kiện bay.'),
(462, 40, NULL, 'Tu_Dong', 'Dang_Mo', 'Delay', '2026-10-30 18:14:18', 'Hết thời gian delay, chuyển về Đang mở. Lý do trễ trước đó: Chưa có lý do.'),
(463, 45, NULL, 'Tu_Dong', 'Dang_Mo', 'Delay', '2026-10-30 18:14:18', 'Hết thời gian delay, chuyển về Đang mở. Lý do trễ trước đó: Chưa có lý do.'),
(464, 89, NULL, 'Tu_Dong', 'Dang_Mo', 'Delay', '2026-10-30 18:14:18', 'Hết thời gian delay, chuyển về Đang mở. Lý do trễ trước đó: Chưa có lý do.'),
(465, 40, NULL, 'Tu_Dong', 'Delay', 'Dang_Mo', '2026-10-30 18:14:18', 'Tự động chuyển Trễ chuyến: quá 15 phút kể từ giờ cất cánh mà chưa xác nhận điều kiện bay.'),
(466, 45, NULL, 'Tu_Dong', 'Delay', 'Dang_Mo', '2026-10-30 18:14:18', 'Tự động chuyển Trễ chuyến: quá 15 phút kể từ giờ cất cánh mà chưa xác nhận điều kiện bay.'),
(467, 89, NULL, 'Tu_Dong', 'Delay', 'Dang_Mo', '2026-10-30 18:14:18', 'Tự động chuyển Trễ chuyến: quá 15 phút kể từ giờ cất cánh mà chưa xác nhận điều kiện bay.'),
(468, 40, NULL, 'Tu_Dong', 'Dang_Mo', 'Delay', '2026-10-30 18:14:18', 'Hết thời gian delay, chuyển về Đang mở. Lý do trễ trước đó: Chưa có lý do.'),
(469, 45, NULL, 'Tu_Dong', 'Dang_Mo', 'Delay', '2026-10-30 18:14:18', 'Hết thời gian delay, chuyển về Đang mở. Lý do trễ trước đó: Chưa có lý do.'),
(470, 89, NULL, 'Tu_Dong', 'Dang_Mo', 'Delay', '2026-10-30 18:14:18', 'Hết thời gian delay, chuyển về Đang mở. Lý do trễ trước đó: Chưa có lý do.'),
(471, 40, NULL, 'Tu_Dong', 'Delay', 'Dang_Mo', '2026-10-30 18:14:19', 'Tự động chuyển Trễ chuyến: quá 15 phút kể từ giờ cất cánh mà chưa xác nhận điều kiện bay.'),
(472, 45, NULL, 'Tu_Dong', 'Delay', 'Dang_Mo', '2026-10-30 18:14:19', 'Tự động chuyển Trễ chuyến: quá 15 phút kể từ giờ cất cánh mà chưa xác nhận điều kiện bay.'),
(473, 89, NULL, 'Tu_Dong', 'Delay', 'Dang_Mo', '2026-10-30 18:14:19', 'Tự động chuyển Trễ chuyến: quá 15 phút kể từ giờ cất cánh mà chưa xác nhận điều kiện bay.'),
(474, 40, NULL, 'Tu_Dong', 'Dang_Mo', 'Delay', '2026-10-30 18:14:19', 'Hết thời gian delay, chuyển về Đang mở. Lý do trễ trước đó: Chưa có lý do.'),
(475, 45, NULL, 'Tu_Dong', 'Dang_Mo', 'Delay', '2026-10-30 18:14:19', 'Hết thời gian delay, chuyển về Đang mở. Lý do trễ trước đó: Chưa có lý do.'),
(476, 89, NULL, 'Tu_Dong', 'Dang_Mo', 'Delay', '2026-10-30 18:14:19', 'Hết thời gian delay, chuyển về Đang mở. Lý do trễ trước đó: Chưa có lý do.'),
(477, 40, NULL, 'Tu_Dong', 'Delay', 'Dang_Mo', '2026-10-30 18:15:00', 'Tự động chuyển Trễ chuyến: quá 15 phút kể từ giờ cất cánh mà chưa xác nhận điều kiện bay.'),
(478, 45, NULL, 'Tu_Dong', 'Delay', 'Dang_Mo', '2026-10-30 18:15:00', 'Tự động chuyển Trễ chuyến: quá 15 phút kể từ giờ cất cánh mà chưa xác nhận điều kiện bay.'),
(479, 89, NULL, 'Tu_Dong', 'Delay', 'Dang_Mo', '2026-10-30 18:15:00', 'Tự động chuyển Trễ chuyến: quá 15 phút kể từ giờ cất cánh mà chưa xác nhận điều kiện bay.'),
(480, 40, NULL, 'Tu_Dong', 'Dang_Mo', 'Delay', '2026-10-30 18:15:00', 'Hết thời gian delay, chuyển về Đang mở. Lý do trễ trước đó: Chưa có lý do.'),
(481, 45, NULL, 'Tu_Dong', 'Dang_Mo', 'Delay', '2026-10-30 18:15:00', 'Hết thời gian delay, chuyển về Đang mở. Lý do trễ trước đó: Chưa có lý do.'),
(482, 89, NULL, 'Tu_Dong', 'Dang_Mo', 'Delay', '2026-10-30 18:15:00', 'Hết thời gian delay, chuyển về Đang mở. Lý do trễ trước đó: Chưa có lý do.'),
(483, 40, NULL, 'Tu_Dong', 'Delay', 'Dang_Mo', '2026-10-30 18:15:00', 'Tự động chuyển Trễ chuyến: quá 15 phút kể từ giờ cất cánh mà chưa xác nhận điều kiện bay.'),
(484, 45, NULL, 'Tu_Dong', 'Delay', 'Dang_Mo', '2026-10-30 18:15:00', 'Tự động chuyển Trễ chuyến: quá 15 phút kể từ giờ cất cánh mà chưa xác nhận điều kiện bay.'),
(485, 89, NULL, 'Tu_Dong', 'Delay', 'Dang_Mo', '2026-10-30 18:15:00', 'Tự động chuyển Trễ chuyến: quá 15 phút kể từ giờ cất cánh mà chưa xác nhận điều kiện bay.'),
(486, 40, NULL, 'Tu_Dong', 'Dang_Mo', 'Delay', '2026-10-30 18:15:00', 'Hết thời gian delay, chuyển về Đang mở. Lý do trễ trước đó: Chưa có lý do.'),
(487, 45, NULL, 'Tu_Dong', 'Dang_Mo', 'Delay', '2026-10-30 18:15:00', 'Hết thời gian delay, chuyển về Đang mở. Lý do trễ trước đó: Chưa có lý do.'),
(488, 89, NULL, 'Tu_Dong', 'Dang_Mo', 'Delay', '2026-10-30 18:15:00', 'Hết thời gian delay, chuyển về Đang mở. Lý do trễ trước đó: Chưa có lý do.'),
(489, 40, NULL, 'Tu_Dong', 'Delay', 'Dang_Mo', '2026-10-30 18:15:13', 'Tự động chuyển Trễ chuyến: quá 15 phút kể từ giờ cất cánh mà chưa xác nhận điều kiện bay.'),
(490, 45, NULL, 'Tu_Dong', 'Delay', 'Dang_Mo', '2026-10-30 18:15:13', 'Tự động chuyển Trễ chuyến: quá 15 phút kể từ giờ cất cánh mà chưa xác nhận điều kiện bay.'),
(491, 89, NULL, 'Tu_Dong', 'Delay', 'Dang_Mo', '2026-10-30 18:15:13', 'Tự động chuyển Trễ chuyến: quá 15 phút kể từ giờ cất cánh mà chưa xác nhận điều kiện bay.'),
(492, 40, NULL, 'Tu_Dong', 'Dang_Mo', 'Delay', '2026-10-30 18:15:13', 'Hết thời gian delay, chuyển về Đang mở. Lý do trễ trước đó: Chưa có lý do.'),
(493, 45, NULL, 'Tu_Dong', 'Dang_Mo', 'Delay', '2026-10-30 18:15:13', 'Hết thời gian delay, chuyển về Đang mở. Lý do trễ trước đó: Chưa có lý do.'),
(494, 89, NULL, 'Tu_Dong', 'Dang_Mo', 'Delay', '2026-10-30 18:15:13', 'Hết thời gian delay, chuyển về Đang mở. Lý do trễ trước đó: Chưa có lý do.'),
(495, 40, NULL, 'Tu_Dong', 'Delay', 'Dang_Mo', '2026-10-30 18:15:14', 'Tự động chuyển Trễ chuyến: quá 15 phút kể từ giờ cất cánh mà chưa xác nhận điều kiện bay.'),
(496, 45, NULL, 'Tu_Dong', 'Delay', 'Dang_Mo', '2026-10-30 18:15:14', 'Tự động chuyển Trễ chuyến: quá 15 phút kể từ giờ cất cánh mà chưa xác nhận điều kiện bay.'),
(497, 89, NULL, 'Tu_Dong', 'Delay', 'Dang_Mo', '2026-10-30 18:15:14', 'Tự động chuyển Trễ chuyến: quá 15 phút kể từ giờ cất cánh mà chưa xác nhận điều kiện bay.'),
(498, 40, NULL, 'Tu_Dong', 'Dang_Mo', 'Delay', '2026-10-30 18:15:14', 'Hết thời gian delay, chuyển về Đang mở. Lý do trễ trước đó: Chưa có lý do.'),
(499, 45, NULL, 'Tu_Dong', 'Dang_Mo', 'Delay', '2026-10-30 18:15:14', 'Hết thời gian delay, chuyển về Đang mở. Lý do trễ trước đó: Chưa có lý do.'),
(500, 89, NULL, 'Tu_Dong', 'Dang_Mo', 'Delay', '2026-10-30 18:15:14', 'Hết thời gian delay, chuyển về Đang mở. Lý do trễ trước đó: Chưa có lý do.'),
(501, 40, NULL, 'Tu_Dong', 'Delay', 'Dang_Mo', '2026-10-30 18:15:14', 'Tự động chuyển Trễ chuyến: quá 15 phút kể từ giờ cất cánh mà chưa xác nhận điều kiện bay.'),
(502, 45, NULL, 'Tu_Dong', 'Delay', 'Dang_Mo', '2026-10-30 18:15:14', 'Tự động chuyển Trễ chuyến: quá 15 phút kể từ giờ cất cánh mà chưa xác nhận điều kiện bay.'),
(503, 89, NULL, 'Tu_Dong', 'Delay', 'Dang_Mo', '2026-10-30 18:15:14', 'Tự động chuyển Trễ chuyến: quá 15 phút kể từ giờ cất cánh mà chưa xác nhận điều kiện bay.'),
(504, 40, NULL, 'Tu_Dong', 'Dang_Mo', 'Delay', '2026-10-30 18:15:14', 'Hết thời gian delay, chuyển về Đang mở. Lý do trễ trước đó: Chưa có lý do.'),
(505, 45, NULL, 'Tu_Dong', 'Dang_Mo', 'Delay', '2026-10-30 18:15:14', 'Hết thời gian delay, chuyển về Đang mở. Lý do trễ trước đó: Chưa có lý do.'),
(506, 89, NULL, 'Tu_Dong', 'Dang_Mo', 'Delay', '2026-10-30 18:15:14', 'Hết thời gian delay, chuyển về Đang mở. Lý do trễ trước đó: Chưa có lý do.'),
(507, 40, NULL, 'Tu_Dong', 'Delay', 'Dang_Mo', '2026-10-30 18:15:14', 'Tự động chuyển Trễ chuyến: quá 15 phút kể từ giờ cất cánh mà chưa xác nhận điều kiện bay.'),
(508, 45, NULL, 'Tu_Dong', 'Delay', 'Dang_Mo', '2026-10-30 18:15:14', 'Tự động chuyển Trễ chuyến: quá 15 phút kể từ giờ cất cánh mà chưa xác nhận điều kiện bay.'),
(509, 89, NULL, 'Tu_Dong', 'Delay', 'Dang_Mo', '2026-10-30 18:15:14', 'Tự động chuyển Trễ chuyến: quá 15 phút kể từ giờ cất cánh mà chưa xác nhận điều kiện bay.'),
(510, 40, NULL, 'Tu_Dong', 'Dang_Mo', 'Delay', '2026-10-30 18:15:14', 'Hết thời gian delay, chuyển về Đang mở. Lý do trễ trước đó: Chưa có lý do.'),
(511, 45, NULL, 'Tu_Dong', 'Dang_Mo', 'Delay', '2026-10-30 18:15:14', 'Hết thời gian delay, chuyển về Đang mở. Lý do trễ trước đó: Chưa có lý do.'),
(512, 89, NULL, 'Tu_Dong', 'Dang_Mo', 'Delay', '2026-10-30 18:15:14', 'Hết thời gian delay, chuyển về Đang mở. Lý do trễ trước đó: Chưa có lý do.'),
(513, 40, NULL, 'Tu_Dong', 'Delay', 'Dang_Mo', '2026-10-30 18:15:18', 'Tự động chuyển Trễ chuyến: quá 15 phút kể từ giờ cất cánh mà chưa xác nhận điều kiện bay.'),
(514, 45, NULL, 'Tu_Dong', 'Delay', 'Dang_Mo', '2026-10-30 18:15:18', 'Tự động chuyển Trễ chuyến: quá 15 phút kể từ giờ cất cánh mà chưa xác nhận điều kiện bay.'),
(515, 89, NULL, 'Tu_Dong', 'Delay', 'Dang_Mo', '2026-10-30 18:15:18', 'Tự động chuyển Trễ chuyến: quá 15 phút kể từ giờ cất cánh mà chưa xác nhận điều kiện bay.'),
(516, 40, NULL, 'Tu_Dong', 'Dang_Mo', 'Delay', '2026-10-30 18:15:18', 'Hết thời gian delay, chuyển về Đang mở. Lý do trễ trước đó: Chưa có lý do.'),
(517, 45, NULL, 'Tu_Dong', 'Dang_Mo', 'Delay', '2026-10-30 18:15:18', 'Hết thời gian delay, chuyển về Đang mở. Lý do trễ trước đó: Chưa có lý do.'),
(518, 89, NULL, 'Tu_Dong', 'Dang_Mo', 'Delay', '2026-10-30 18:15:18', 'Hết thời gian delay, chuyển về Đang mở. Lý do trễ trước đó: Chưa có lý do.'),
(519, 40, NULL, 'Tu_Dong', 'Delay', 'Dang_Mo', '2026-10-30 18:15:29', 'Tự động chuyển Trễ chuyến: quá 15 phút kể từ giờ cất cánh mà chưa xác nhận điều kiện bay.'),
(520, 45, NULL, 'Tu_Dong', 'Delay', 'Dang_Mo', '2026-10-30 18:15:29', 'Tự động chuyển Trễ chuyến: quá 15 phút kể từ giờ cất cánh mà chưa xác nhận điều kiện bay.'),
(521, 89, NULL, 'Tu_Dong', 'Delay', 'Dang_Mo', '2026-10-30 18:15:29', 'Tự động chuyển Trễ chuyến: quá 15 phút kể từ giờ cất cánh mà chưa xác nhận điều kiện bay.'),
(522, 40, NULL, 'Tu_Dong', 'Dang_Mo', 'Delay', '2026-10-30 18:15:29', 'Hết thời gian delay, chuyển về Đang mở. Lý do trễ trước đó: Chưa có lý do.'),
(523, 45, NULL, 'Tu_Dong', 'Dang_Mo', 'Delay', '2026-10-30 18:15:29', 'Hết thời gian delay, chuyển về Đang mở. Lý do trễ trước đó: Chưa có lý do.'),
(524, 89, NULL, 'Tu_Dong', 'Dang_Mo', 'Delay', '2026-10-30 18:15:29', 'Hết thời gian delay, chuyển về Đang mở. Lý do trễ trước đó: Chưa có lý do.'),
(525, 40, NULL, 'Tu_Dong', 'Delay', 'Dang_Mo', '2026-10-30 18:15:29', 'Tự động chuyển Trễ chuyến: quá 15 phút kể từ giờ cất cánh mà chưa xác nhận điều kiện bay.'),
(526, 45, NULL, 'Tu_Dong', 'Delay', 'Dang_Mo', '2026-10-30 18:15:29', 'Tự động chuyển Trễ chuyến: quá 15 phút kể từ giờ cất cánh mà chưa xác nhận điều kiện bay.'),
(527, 89, NULL, 'Tu_Dong', 'Delay', 'Dang_Mo', '2026-10-30 18:15:29', 'Tự động chuyển Trễ chuyến: quá 15 phút kể từ giờ cất cánh mà chưa xác nhận điều kiện bay.'),
(528, 40, NULL, 'Tu_Dong', 'Dang_Mo', 'Delay', '2026-10-30 18:15:29', 'Hết thời gian delay, chuyển về Đang mở. Lý do trễ trước đó: Chưa có lý do.'),
(529, 45, NULL, 'Tu_Dong', 'Dang_Mo', 'Delay', '2026-10-30 18:15:29', 'Hết thời gian delay, chuyển về Đang mở. Lý do trễ trước đó: Chưa có lý do.'),
(530, 89, NULL, 'Tu_Dong', 'Dang_Mo', 'Delay', '2026-10-30 18:15:29', 'Hết thời gian delay, chuyển về Đang mở. Lý do trễ trước đó: Chưa có lý do.'),
(531, 40, NULL, 'Tu_Dong', 'Delay', 'Dang_Mo', '2026-10-30 18:15:30', 'Tự động chuyển Trễ chuyến: quá 15 phút kể từ giờ cất cánh mà chưa xác nhận điều kiện bay.'),
(532, 45, NULL, 'Tu_Dong', 'Delay', 'Dang_Mo', '2026-10-30 18:15:30', 'Tự động chuyển Trễ chuyến: quá 15 phút kể từ giờ cất cánh mà chưa xác nhận điều kiện bay.'),
(533, 89, NULL, 'Tu_Dong', 'Delay', 'Dang_Mo', '2026-10-30 18:15:30', 'Tự động chuyển Trễ chuyến: quá 15 phút kể từ giờ cất cánh mà chưa xác nhận điều kiện bay.'),
(534, 40, NULL, 'Tu_Dong', 'Dang_Mo', 'Delay', '2026-10-30 18:15:30', 'Hết thời gian delay, chuyển về Đang mở. Lý do trễ trước đó: Chưa có lý do.'),
(535, 45, NULL, 'Tu_Dong', 'Dang_Mo', 'Delay', '2026-10-30 18:15:30', 'Hết thời gian delay, chuyển về Đang mở. Lý do trễ trước đó: Chưa có lý do.'),
(536, 89, NULL, 'Tu_Dong', 'Dang_Mo', 'Delay', '2026-10-30 18:15:30', 'Hết thời gian delay, chuyển về Đang mở. Lý do trễ trước đó: Chưa có lý do.'),
(537, 40, NULL, 'Tu_Dong', 'Delay', 'Dang_Mo', '2026-10-30 18:16:03', 'Tự động chuyển Trễ chuyến: quá 15 phút kể từ giờ cất cánh mà chưa xác nhận điều kiện bay.'),
(538, 45, NULL, 'Tu_Dong', 'Delay', 'Dang_Mo', '2026-10-30 18:16:03', 'Tự động chuyển Trễ chuyến: quá 15 phút kể từ giờ cất cánh mà chưa xác nhận điều kiện bay.'),
(539, 89, NULL, 'Tu_Dong', 'Delay', 'Dang_Mo', '2026-10-30 18:16:03', 'Tự động chuyển Trễ chuyến: quá 15 phút kể từ giờ cất cánh mà chưa xác nhận điều kiện bay.'),
(540, 89, 1, 'Sua', 'Dang_Mo', 'Delay', '2026-10-30 18:18:10', 'Trạng thái: Trễ chuyến -> Đang mở'),
(541, 89, NULL, 'Tu_Dong', 'Delay', 'Dang_Mo', '2026-10-30 18:18:11', 'Tự động chuyển Trễ chuyến: quá 15 phút kể từ giờ cất cánh mà chưa xác nhận điều kiện bay.'),
(542, 89, 1, 'Sua', 'Dang_Mo', 'Delay', '2026-10-30 18:18:17', 'Trạng thái: Trễ chuyến -> Đang mở'),
(543, 89, NULL, 'Tu_Dong', 'Delay', 'Dang_Mo', '2026-10-30 18:18:17', 'Tự động chuyển Trễ chuyến: quá 15 phút kể từ giờ cất cánh mà chưa xác nhận điều kiện bay.'),
(544, 89, 1, 'Xac_Nhan_Cat_Canh', 'Cat_Canh', 'Delay', '2026-10-30 18:18:28', 'Chuyến bay VN123 đã khắc phục xong các sự cố trễ trước đó, Lý do không có lý do trước đó. Nguyễn Đình Khánh xác nhận đủ điều kiện an toàn và cho cất cánh lúc 18:18.'),
(545, 45, 1, 'Xac_Nhan_Cat_Canh', 'Cat_Canh', 'Delay', '2026-10-30 18:18:31', 'Chuyến bay VN210 đã khắc phục xong các sự cố trễ trước đó, Lý do không có lý do trước đó. Nguyễn Đình Khánh xác nhận đủ điều kiện an toàn và cho cất cánh lúc 18:18.'),
(546, 40, 1, 'Xac_Nhan_Cat_Canh', 'Cat_Canh', 'Delay', '2026-10-30 18:18:34', 'Chuyến bay VJ777 đã khắc phục xong các sự cố trễ trước đó, Lý do không có lý do trước đó. Nguyễn Đình Khánh xác nhận đủ điều kiện an toàn và cho cất cánh lúc 18:18.'),
(547, 205, 1, 'Them', 'Dang_Mo', NULL, '2026-10-30 18:19:50', 'Thêm chuyến bay mới.'),
(548, 205, 1, 'Tre_Tiep', 'Delay', 'Dang_Mo', '2026-10-30 18:20:19', 'Chưa đủ điều kiện bay - trễ chuyến 3 phút. Lý do: test. Giờ cất cánh mới: 30/10/2026 18:23 (tổng lùi 4 phút).'),
(549, 205, NULL, 'Tu_Dong', 'Dang_Mo', 'Delay', '2026-10-30 18:23:23', 'Hết thời gian delay, chuyển về Đang mở. Lý do trễ trước đó: test.'),
(550, 205, 1, 'Xac_Nhan_Cat_Canh', 'Cat_Canh', 'Dang_Mo', '2026-10-30 18:23:52', 'Đã đủ điều kiện bay và xác nhận cất cánh.'),
(551, 205, NULL, 'Tu_Dong', 'Ha_Canh', 'Cat_Canh', '2026-10-30 18:27:53', 'Tự động: đã tới giờ dự kiến hạ cánh.'),
(552, 206, 1, 'Them', 'Dang_Mo', NULL, '2026-10-30 18:30:27', 'Thêm chuyến bay mới.'),
(556, 206, 1, 'Tre_Tiep', 'Delay', 'Dang_Mo', '2026-10-30 18:36:23', 'Chưa đủ điều kiện bay - trễ chuyến 3 phút. Lý do: 3. Giờ cất cánh mới: 30/10/2026 18:39 (tổng lùi 4 phút).'),
(563, 212, NULL, 'Tre_Tiep', 'Delay', 'Dang_Mo', '2026-10-30 18:38:03', 'Chưa đủ điều kiện bay - trễ chuyến 295 phút. Lý do: test guard. (tổng lùi 295 phút).'),
(564, 212, NULL, 'Tu_Dong', 'Huy', 'Delay', '2026-10-30 18:38:03', 'Hủy chuyến do chưa khắc phục được sự cố và chuyển sang chuyến bay gần nhất.'),
(565, 213, NULL, 'Tre_Tiep', 'Delay', 'Dang_Mo', '2026-10-30 18:38:03', 'Chưa đủ điều kiện bay - trễ chuyến 250 phút. Lý do: test guard 2. (tổng lùi 250 phút).'),
(566, 213, 1, 'Tre_Tiep', 'Delay', 'Delay', '2026-10-30 18:38:03', 'Chưa đủ yêu cầu an toàn - delay tiếp 60 phút. Lý do: Kiểm tra giới hạn 5 giờ. Giờ cất cánh mới: 30/10/2026 19:38 (tổng lùi 65 phút).'),
(567, 213, NULL, 'Tu_Dong', 'Huy', 'Delay', '2026-10-30 18:38:03', 'Hủy chuyến do chưa khắc phục được sự cố và chuyển sang chuyến bay gần nhất.'),
(568, 214, NULL, 'Tre_Tiep', 'Delay', 'Dang_Mo', '2026-10-30 18:38:03', 'Chưa đủ điều kiện bay - trễ chuyến 250 phút. Lý do: test guard 3. (tổng lùi 250 phút).'),
(569, 214, 1, 'Tre_Tiep', 'Delay', 'Delay', '2026-10-30 18:38:03', 'Chưa đủ yêu cầu an toàn - delay tiếp 10 phút. Lý do: Kiểm tra giới hạn 5 giờ. Giờ cất cánh mới: 30/10/2026 18:48 (tổng lùi 15 phút).');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `nha_ga`
--

CREATE TABLE `nha_ga` (
  `id` int(11) NOT NULL,
  `id_san_bay` int(11) NOT NULL,
  `ma_nha_ga` varchar(10) NOT NULL,
  `ten_nha_ga` varchar(100) NOT NULL,
  `loai_nha_ga` enum('Quoc_Noi','Quoc_Te','Hon_Hop') NOT NULL DEFAULT 'Hon_Hop',
  `nam_khai_thac` smallint(6) DEFAULT NULL,
  `dien_tich` varchar(50) DEFAULT NULL,
  `cong_suat` varchar(50) DEFAULT NULL,
  `so_do` varchar(255) DEFAULT NULL,
  `mo_ta` varchar(500) DEFAULT NULL,
  `trang_thai` enum('Hoat_Dong','Bao_Tri') DEFAULT 'Hoat_Dong'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `nha_ga`
--

INSERT INTO `nha_ga` (`id`, `id_san_bay`, `ma_nha_ga`, `ten_nha_ga`, `loai_nha_ga`, `nam_khai_thac`, `dien_tich`, `cong_suat`, `so_do`, `mo_ta`, `trang_thai`) VALUES
(1, 1, 'T1', 'Nhà ga T1 - Quốc nội', 'Quoc_Noi', 2001, '90.000 m²', '15 triệu khách/năm', 'assets/img/so_do_nha_ga/HAN-T1-01.jpg', 'Nhà ga quốc nội phục vụ các chuyến bay nội địa, được cải tạo và mở rộng năm 2015.', 'Hoat_Dong'),
(2, 1, 'T2', 'Nhà ga T2 - Quốc tế', 'Quoc_Te', 2015, '150.000 m²', '10 triệu khách/năm', 'assets/img/so_do_nha_ga/HAN-T2-01.jpg', 'Nhà ga quốc tế hiện đại, có thể mở rộng công suất lên 15 triệu khách/năm.', 'Hoat_Dong'),
(3, 2, 'T1', 'Nhà ga T1 - Quốc nội', 'Quoc_Noi', 1980, '40.000 m²', '13 triệu khách/năm', 'assets/img/so_do_nha_ga/SGN-T1-01.jpg', 'Nhà ga quốc nội của sân bay lớn nhất Việt Nam.', 'Hoat_Dong'),
(4, 2, 'T2', 'Nhà ga T2 - Quốc tế', 'Quoc_Te', 2007, '100.000 m²', '12 triệu khách/năm', 'assets/img/so_do_nha_ga/SGN-T2-01.jpg', 'Nhà ga quốc tế phục vụ các chuyến bay đến và đi quốc tế.', 'Hoat_Dong'),
(5, 4, 'T1', 'Nhà ga T1 - Quốc nội', 'Quoc_Noi', 2011, '36.000 m²', '6 triệu khách/năm', 'assets/img/so_do_nha_ga/DAD-T1-01.jpg', 'Nhà ga quốc nội của sân bay Đà Nẵng.', 'Hoat_Dong'),
(6, 4, 'T2', 'Nhà ga T2 - Quốc tế', 'Quoc_Te', 2017, '48.000 m²', '4 triệu khách/năm', NULL, 'Nhà ga quốc tế được thiết kế theo kiến trúc đặc trưng của miền Trung.', 'Hoat_Dong'),
(7, 3, 'T1', 'Nhà ga T1 - Quốc nội', 'Quoc_Noi', 2009, '13.000 m²', '2,5 triệu khách/năm', 'assets/img/so_do_nha_ga/CXR-T1-01.jpg', 'Nhà ga quốc nội phục vụ Khánh Hòa và Nam Trung Bộ.', 'Hoat_Dong'),
(8, 3, 'T2', 'Nhà ga T2 - Quốc tế', 'Quoc_Te', 2018, '50.000 m²', '6 triệu khách/năm', 'assets/img/so_do_nha_ga/CXR-T2-01.jpg', 'Nhà ga quốc tế Cam Ranh có công suất lớn, phục vụ khách quốc tế đến Khánh Hòa.', 'Hoat_Dong'),
(9, 5, 'T1', 'Nhà ga hành khách Cát Bi', 'Hon_Hop', 2016, NULL, '2 triệu khách/năm', 'assets/img/so_do_nha_ga/HPH-T1-01.jpg', 'Nhà ga hành khách phục vụ cả chuyến bay nội địa và quốc tế tại Cát Bi.', 'Hoat_Dong'),
(11, 6, 'T1', 'Nhà ga hành khách Phú Quốc', 'Hon_Hop', 2012, '24.000 m²', '4 triệu khách/năm', 'assets/img/so_do_nha_ga/PQC-T1-01.jpg', 'Nhà ga hành khách phục vụ cả chuyến bay nội địa và quốc tế tại Phú Quốc.', 'Hoat_Dong'),
(12, 7, 'T1', 'Nhà ga hành khách Cần Thơ', 'Hon_Hop', 2011, '20.000 m²', '3 triệu khách/năm', 'assets/img/so_do_nha_ga/VCA-T1-01.jpg', 'Nhà ga hành khách trung tâm vùng Đồng bằng sông Cửu Long.', 'Hoat_Dong'),
(13, 8, 'T1', 'Nhà ga hành khách Điện Biên', 'Quoc_Noi', 2024, NULL, '500.000 khách/năm', NULL, 'Nhà ga mới được đưa vào khai thác năm 2024, phục vụ tỉnh Điện Biên.', 'Hoat_Dong'),
(14, 9, 'T1', 'Nhà ga hành khách Thọ Xuân', 'Quoc_Noi', 2013, NULL, '1,2 triệu khách/năm', NULL, 'Nhà ga hành khách phục vụ Thanh Hóa và khu vực Bắc Trung Bộ.', 'Hoat_Dong'),
(15, 10, 'T1', 'Nhà ga hành khách Đồng Hới', 'Quoc_Noi', 2008, NULL, '500.000 khách/năm', NULL, 'Nhà ga hành khách phục vụ Quảng Bình và vùng Phong Nha - Kẻ Bàng.', 'Hoat_Dong'),
(16, 11, 'T1', 'Nhà ga hành khách Tuy Hòa', 'Quoc_Noi', 2013, NULL, '1 triệu khách/năm', NULL, 'Nhà ga hành khách phục vụ Phú Yên và khu vực Nam Trung Bộ.', 'Hoat_Dong'),
(17, 12, 'T1', 'Nhà ga hành khách Chu Lai', 'Quoc_Noi', 2015, NULL, '1 triệu khách/năm', NULL, 'Nhà ga hành khách phục vụ Quảng Nam và vùng kinh tế trọng điểm miền Trung.', 'Hoat_Dong'),
(18, 13, 'T1', 'Nhà ga hành khách Pleiku', 'Quoc_Noi', 2018, NULL, '1,5 triệu khách/năm', NULL, 'Nhà ga hành khách phục vụ Gia Lai và khu vực Tây Nguyên.', 'Hoat_Dong'),
(19, 14, 'T1', 'Nhà ga hành khách Phù Cát', 'Quoc_Noi', 2018, NULL, '1,5 triệu khách/năm', NULL, 'Nhà ga hành khách phục vụ Bình Định và vùng duyên hải Nam Trung Bộ.', 'Hoat_Dong'),
(20, 15, 'T1', 'Nhà ga hành khách Rạch Giá', 'Quoc_Noi', 2013, NULL, '500.000 khách/năm', NULL, 'Nhà ga hành khách phục vụ Kiên Giang và khu vực Tây Nam Bộ.', 'Hoat_Dong'),
(21, 4, 'T3', 'Nhà ga T3 - Quốc nội', 'Quoc_Noi', NULL, NULL, NULL, 'assets/img/so_do_nha_ga/DAD-T3-01.jpg', 'Nhà ga T3 phục vụ các chuyến bay nội địa tại Cảng HKQT Đà Nẵng.', 'Hoat_Dong'),
(22, 2, 'T3', 'Nhà ga T3 - Quốc nội', 'Quoc_Noi', 2025, '112.500 m²', '20 triệu khách/năm', 'assets/img/so_do_nha_ga/SGN-T3-01.jpg', 'Nhà ga quốc nội mới, khai thác từ tháng 4/2025, giảm tải cho nhà ga T1.', 'Hoat_Dong');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `quay_checkin`
--

CREATE TABLE `quay_checkin` (
  `id` int(11) NOT NULL,
  `id_nha_ga` int(11) NOT NULL,
  `so_quay` varchar(20) NOT NULL,
  `khu_vuc` varchar(50) DEFAULT NULL,
  `trang_thai` enum('Hoat_Dong','Bao_Tri') DEFAULT 'Hoat_Dong'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `quay_checkin`
--

INSERT INTO `quay_checkin` (`id`, `id_nha_ga`, `so_quay`, `khu_vuc`, `trang_thai`) VALUES
(1, 1, 'A01-A12', 'Sảnh A', 'Hoat_Dong'),
(2, 1, 'B01-B12', 'Sảnh B', 'Hoat_Dong'),
(3, 3, 'C01-C15', 'Sảnh C', 'Hoat_Dong'),
(4, 1, 'C01-C10', 'Sảnh C', 'Hoat_Dong'),
(5, 2, 'D01-D12', 'Sảnh D', 'Hoat_Dong'),
(6, 2, 'E01-E12', 'Sảnh E', 'Hoat_Dong'),
(7, 2, 'F01-F10', 'Sảnh F', 'Bao_Tri'),
(8, 3, 'A01-A12', 'Sảnh A', 'Hoat_Dong'),
(9, 3, 'B01-B12', 'Sảnh B', 'Hoat_Dong'),
(10, 4, 'D01-D12', 'Sảnh D', 'Hoat_Dong'),
(11, 4, 'E01-E12', 'Sảnh E', 'Hoat_Dong'),
(12, 4, 'F01-F12', 'Sảnh F', 'Hoat_Dong'),
(13, 5, 'A01-A10', 'Sảnh A', 'Hoat_Dong'),
(14, 5, 'B01-B10', 'Sảnh B', 'Hoat_Dong'),
(15, 6, 'C01-C12', 'Sảnh C', 'Hoat_Dong'),
(16, 6, 'D01-D12', 'Sảnh D', 'Hoat_Dong'),
(17, 7, 'A01-A08', 'Sảnh A', 'Hoat_Dong'),
(18, 7, 'B01-B08', 'Sảnh B', 'Hoat_Dong'),
(19, 8, 'C01-C12', 'Sảnh C', 'Hoat_Dong'),
(20, 8, 'D01-D12', 'Sảnh D', 'Hoat_Dong'),
(21, 8, 'E01-E08', 'Sảnh E', 'Bao_Tri'),
(22, 9, 'A01-A08', 'Sảnh A', 'Hoat_Dong'),
(23, 9, 'B01-B08', 'Sảnh B', 'Hoat_Dong'),
(24, 9, 'C01-C10', 'Sảnh C', 'Hoat_Dong'),
(25, 9, 'D01-D10', 'Sảnh D', 'Hoat_Dong'),
(26, 11, 'A01-A10', 'Sảnh A', 'Hoat_Dong'),
(27, 11, 'B01-B10', 'Sảnh B', 'Hoat_Dong'),
(28, 11, 'C01-C06', 'Sảnh C', 'Hoat_Dong'),
(29, 12, 'A01-A08', 'Sảnh A', 'Hoat_Dong'),
(30, 12, 'B01-B08', 'Sảnh B', 'Hoat_Dong'),
(31, 13, 'A01-A06', 'Sảnh A', 'Hoat_Dong'),
(32, 13, 'B01-B06', 'Sảnh B', 'Bao_Tri'),
(33, 14, 'A01-A08', 'Sảnh A', 'Hoat_Dong'),
(34, 14, 'B01-B08', 'Sảnh B', 'Hoat_Dong'),
(35, 15, 'A01-A06', 'Sảnh A', 'Hoat_Dong'),
(36, 16, 'A01-A08', 'Sảnh A', 'Hoat_Dong'),
(37, 16, 'B01-B08', 'Sảnh B', 'Hoat_Dong'),
(38, 17, 'A01-A06', 'Sảnh A', 'Hoat_Dong'),
(39, 18, 'A01-A08', 'Sảnh A', 'Hoat_Dong'),
(40, 19, 'A01-A08', 'Sảnh A', 'Hoat_Dong'),
(41, 19, 'B01-B08', 'Sảnh B', 'Hoat_Dong'),
(42, 20, 'A01-A06', 'Sảnh A', 'Hoat_Dong');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `san_bay`
--

CREATE TABLE `san_bay` (
  `id` int(11) NOT NULL,
  `ma_iata` varchar(3) NOT NULL,
  `ma_icao` varchar(4) DEFAULT NULL,
  `ten_san_bay` varchar(120) NOT NULL,
  `ten_ngan` varchar(50) NOT NULL,
  `phan_cap` enum('Quoc_Noi','Quoc_Te') NOT NULL,
  `vi_tri` varchar(255) NOT NULL,
  `hinh_anh` varchar(255) DEFAULT NULL,
  `mo_ta` varchar(500) DEFAULT NULL,
  `thu_tu_hien_thi` int(11) NOT NULL DEFAULT 0,
  `trang_thai` enum('Hoat_Dong','Bao_Tri','Dong_Cua_Khan_Cap') DEFAULT 'Hoat_Dong'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `san_bay`
--

INSERT INTO `san_bay` (`id`, `ma_iata`, `ma_icao`, `ten_san_bay`, `ten_ngan`, `phan_cap`, `vi_tri`, `hinh_anh`, `mo_ta`, `thu_tu_hien_thi`, `trang_thai`) VALUES
(1, 'HAN', 'VVNB', 'Cảng hàng không quốc tế Nội Bài', 'Nội Bài', 'Quoc_Te', 'Huyện Sóc Sơn, TP. Hà Nội', 'assets/img/san_bay/HAN.jpg', 'Cửa ngõ hàng không quốc tế của Thủ đô, khai thác hai nhà ga T1 (quốc nội) và T2 (quốc tế).', 1, 'Hoat_Dong'),
(2, 'SGN', 'VVTS', 'Cảng hàng không quốc tế Tân Sơn Nhất', 'Tân Sơn Nhất', 'Quoc_Te', 'Quận Tân Bình, TP. Hồ Chí Minh', 'assets/img/san_bay/SGN.jpg', 'Sân bay lớn nhất Việt Nam về lưu lượng hành khách, gồm T1 (quốc nội) và T2 (quốc tế).', 2, 'Hoat_Dong'),
(3, 'CXR', 'VVCR', 'Cảng hàng không quốc tế Cam Ranh', 'Cam Ranh', 'Quoc_Te', 'TP. Cam Ranh, tỉnh Khánh Hòa', 'assets/img/san_bay/CXR.jpg', 'Sân bay phục vụ Khánh Hòa và khu vực Nam Trung Bộ, có T1 quốc nội và T2 quốc tế.', 4, 'Hoat_Dong'),
(4, 'DAD', 'VVDN', 'Cảng hàng không quốc tế Đà Nẵng', 'Đà Nẵng', 'Quoc_Te', 'Quận Hải Châu, TP. Đà Nẵng', 'assets/img/san_bay/DAD.jpg', 'Sân bay trung tâm miền Trung, khai thác T1 quốc nội và T2 quốc tế.', 3, 'Hoat_Dong'),
(5, 'HPH', 'VVCI', 'Cảng hàng không quốc tế Cát Bi', 'Cát Bi', 'Quoc_Te', 'Quận Hải An, TP. Hải Phòng', 'assets/img/san_bay/HPH.jpg', 'Cửa ngõ hàng không của thành phố cảng và vùng duyên hải Bắc Bộ.', 5, 'Hoat_Dong'),
(6, 'PQC', 'VVPQ', 'Cảng hàng không quốc tế Phú Quốc', 'Phú Quốc', 'Quoc_Te', 'TP. Phú Quốc, tỉnh Kiên Giang', 'assets/img/san_bay/PQC.jpg', 'Sân bay phục vụ đảo ngọc Phú Quốc với nhà ga hành khách hiện đại.', 6, 'Hoat_Dong'),
(7, 'VCA', 'VVCT', 'Cảng hàng không quốc tế Cần Thơ', 'Cần Thơ', 'Quoc_Te', 'Quận Bình Thủy, TP. Cần Thơ', 'assets/img/san_bay/VCA.jpg', 'Sân bay trung tâm vùng Đồng bằng sông Cửu Long.', 7, 'Hoat_Dong'),
(8, 'DIN', 'VVDB', 'Cảng hàng không Điện Biên Phủ', 'Điện Biên', 'Quoc_Noi', 'TP. Điện Biên Phủ, tỉnh Điện Biên', 'assets/img/san_bay/DIN.jpg', 'Sân bay phục vụ tỉnh Điện Biên, nhà ga mới đưa vào khai thác năm 2024.', 1, 'Hoat_Dong'),
(9, 'THD', 'VVTX', 'Cảng hàng không Thọ Xuân', 'Thọ Xuân', 'Quoc_Noi', 'Huyện Thọ Xuân, tỉnh Thanh Hóa', 'assets/img/san_bay/THD.jpg', 'Sân bay phục vụ Thanh Hóa và khu vực Bắc Trung Bộ.', 2, 'Hoat_Dong'),
(10, 'VDH', 'VVDH', 'Cảng hàng không Đồng Hới', 'Đồng Hới', 'Quoc_Noi', 'TP. Đồng Hới, tỉnh Quảng Bình', 'assets/img/san_bay/VDH.jpg', 'Sân bay phục vụ Quảng Bình, gần Di sản Phong Nha - Kẻ Bàng.', 3, 'Hoat_Dong'),
(11, 'TBB', 'VVTH', 'Cảng hàng không Tuy Hòa', 'Tuy Hòa', 'Quoc_Noi', 'TP. Tuy Hòa, tỉnh Phú Yên', 'assets/img/san_bay/TBB.jpg', 'Sân bay phục vụ Phú Yên và khu vực Nam Trung Bộ.', 4, 'Hoat_Dong'),
(12, 'VCL', 'VVCA', 'Cảng hàng không Chu Lai', 'Chu Lai', 'Quoc_Noi', 'Huyện Núi Thành, tỉnh Quảng Nam', 'assets/img/san_bay/VCL.jpg', 'Sân bay phục vụ Quảng Nam và vùng kinh tế trọng điểm miền Trung.', 5, 'Hoat_Dong'),
(13, 'PXU', 'VVPK', 'Cảng hàng không Pleiku', 'Pleiku', 'Quoc_Noi', 'TP. Pleiku, tỉnh Gia Lai', 'assets/img/san_bay/PXU.jpg', 'Sân bay phục vụ Gia Lai và khu vực Tây Nguyên.', 6, 'Hoat_Dong'),
(14, 'UIH', 'VVPC', 'Cảng hàng không Phù Cát', 'Phù Cát', 'Quoc_Noi', 'Huyện Phù Cát, tỉnh Bình Định', 'assets/img/san_bay/UIH.jpg', 'Sân bay phục vụ Bình Định và vùng duyên hải Nam Trung Bộ.', 7, 'Hoat_Dong'),
(15, 'VKG', 'VVRG', 'Cảng hàng không Rạch Giá', 'Rạch Giá', 'Quoc_Noi', 'TP. Rạch Giá, tỉnh Kiên Giang', 'assets/img/san_bay/VKG.jpg', 'Sân bay phục vụ Kiên Giang và khu vực Tây Nam Bộ.', 8, 'Hoat_Dong');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `thiet_bi_san_bay`
--

CREATE TABLE `thiet_bi_san_bay` (
  `id` int(11) NOT NULL,
  `id_san_bay` int(11) NOT NULL,
  `ten_thiet_bi` varchar(100) NOT NULL,
  `loai_thiet_bi` enum('Den_Tin_Hieu','Thiet_Bi_Dan_Duong','Xe_Thang','Xe_Keo','Khac') NOT NULL,
  `trang_thai` enum('Tot','Bao_Tri','Hong') DEFAULT 'Tot'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `thiet_bi_san_bay`
--

INSERT INTO `thiet_bi_san_bay` (`id`, `id_san_bay`, `ten_thiet_bi`, `loai_thiet_bi`, `trang_thai`) VALUES
(1, 2, 'Xe kéo đẩy máy bay Goldhofer', 'Xe_Keo', 'Tot'),
(2, 1, 'Xe thang hành khách T1', 'Xe_Thang', 'Bao_Tri'),
(3, 3, 'Hệ thống đèn chớp đường băng', 'Den_Tin_Hieu', 'Tot');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `yeu_cau_bao_tri`
--

CREATE TABLE `yeu_cau_bao_tri` (
  `id` int(11) NOT NULL,
  `id_thiet_bi` int(11) NOT NULL,
  `mo_ta_su_co` text NOT NULL,
  `hinh_anh_minh_hoa` varchar(255) DEFAULT NULL,
  `muc_do` enum('Thap','Trung_Binh','Khan_Cap') NOT NULL,
  `trang_thai_xu_ly` enum('Moi','Dang_Xu_Ly','Hoan_Thanh') DEFAULT 'Moi',
  `ngay_tao` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `yeu_cau_bao_tri`
--

INSERT INTO `yeu_cau_bao_tri` (`id`, `id_thiet_bi`, `mo_ta_su_co`, `hinh_anh_minh_hoa`, `muc_do`, `trang_thai_xu_ly`, `ngay_tao`) VALUES
(1, 2, 'Bơm thủy lực nâng thang bị rò rỉ dầu', NULL, 'Trung_Binh', 'Dang_Xu_Ly', '2026-10-18 19:45:46'),
(2, 3, 'Cháy 3 bóng đèn LED đoạn giữa đường băng', NULL, 'Khan_Cap', 'Moi', '2026-10-18 19:45:46');

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `bang_chuyen_hanh_ly`
--
ALTER TABLE `bang_chuyen_hanh_ly`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_bang_chuyen_nha_ga` (`id_nha_ga`,`so_bang_chuyen`),
  ADD KEY `id_nha_ga` (`id_nha_ga`);

--
-- Chỉ mục cho bảng `bang_gia_dich_vu`
--
ALTER TABLE `bang_gia_dich_vu`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `chuyen_bay_ngay`
--
ALTER TABLE `chuyen_bay_ngay`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_lich_mua` (`id_lich_mua`),
  ADD KEY `id_hang_hang_khong` (`id_hang_hang_khong`),
  ADD KEY `id_loai_tau_bay` (`id_loai_tau_bay`),
  ADD KEY `id_san_bay_di` (`id_san_bay_di`),
  ADD KEY `id_san_bay_den` (`id_san_bay_den`),
  ADD KEY `id_cua_ra_may_bay` (`id_cua_ra_may_bay`),
  ADD KEY `id_quay_checkin` (`id_quay_checkin`),
  ADD KEY `id_bang_chuyen` (`id_bang_chuyen`);

--
-- Chỉ mục cho bảng `cua_ra_may_bay`
--
ALTER TABLE `cua_ra_may_bay`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_cua_ra_nha_ga` (`id_nha_ga`,`so_cua`),
  ADD KEY `id_nha_ga` (`id_nha_ga`);

--
-- Chỉ mục cho bảng `don_vi_mat_dat`
--
ALTER TABLE `don_vi_mat_dat`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ma_don_vi` (`ma_don_vi`);

--
-- Chỉ mục cho bảng `hang_hang_khong`
--
ALTER TABLE `hang_hang_khong`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ma_iata` (`ma_iata`);

--
-- Chỉ mục cho bảng `hoa_don`
--
ALTER TABLE `hoa_don`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_chuyen_bay` (`id_chuyen_bay`),
  ADD KEY `id_hang_hang_khong` (`id_hang_hang_khong`);

--
-- Chỉ mục cho bảng `lich_bay_mua`
--
ALTER TABLE `lich_bay_mua`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_hang_hang_khong` (`id_hang_hang_khong`),
  ADD KEY `id_san_bay_di` (`id_san_bay_di`),
  ADD KEY `id_san_bay_den` (`id_san_bay_den`);

--
-- Chỉ mục cho bảng `lich_su_chuyen_bay`
--
ALTER TABLE `lich_su_chuyen_bay`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_chuyen_bay` (`id_chuyen_bay`),
  ADD KEY `idx_gio_ha_canh` (`gio_ha_canh`);

--
-- Chỉ mục cho bảng `loai_tau_bay`
--
ALTER TABLE `loai_tau_bay`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ma_loai` (`ma_loai`);

--
-- Chỉ mục cho bảng `nguoi_dung`
--
ALTER TABLE `nguoi_dung`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ten_dang_nhap` (`ten_dang_nhap`),
  ADD KEY `id_san_bay` (`id_san_bay`);

--
-- Chỉ mục cho bảng `nhat_ky_lich_bay_mua`
--
ALTER TABLE `nhat_ky_lich_bay_mua`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_lich_mua` (`id_lich_mua`),
  ADD KEY `idx_nguoi_dung` (`id_nguoi_dung`);

--
-- Chỉ mục cho bảng `nhat_ky_trang_thai_bay`
--
ALTER TABLE `nhat_ky_trang_thai_bay`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_chuyen_bay` (`id_chuyen_bay`),
  ADD KEY `id_nguoi_dung` (`id_nguoi_dung`);

--
-- Chỉ mục cho bảng `nha_ga`
--
ALTER TABLE `nha_ga`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_nha_ga_san_bay_ma` (`id_san_bay`,`ma_nha_ga`),
  ADD KEY `id_san_bay` (`id_san_bay`);

--
-- Chỉ mục cho bảng `quay_checkin`
--
ALTER TABLE `quay_checkin`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_quay_checkin_nha_ga` (`id_nha_ga`,`so_quay`),
  ADD KEY `id_nha_ga` (`id_nha_ga`);

--
-- Chỉ mục cho bảng `san_bay`
--
ALTER TABLE `san_bay`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ma_iata` (`ma_iata`),
  ADD UNIQUE KEY `ma_icao` (`ma_icao`),
  ADD KEY `thu_tu_hien_thi` (`thu_tu_hien_thi`);

--
-- Chỉ mục cho bảng `thiet_bi_san_bay`
--
ALTER TABLE `thiet_bi_san_bay`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_san_bay` (`id_san_bay`);

--
-- Chỉ mục cho bảng `yeu_cau_bao_tri`
--
ALTER TABLE `yeu_cau_bao_tri`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_thiet_bi` (`id_thiet_bi`);

--
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `bang_chuyen_hanh_ly`
--
ALTER TABLE `bang_chuyen_hanh_ly`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=65;

--
-- AUTO_INCREMENT cho bảng `bang_gia_dich_vu`
--
ALTER TABLE `bang_gia_dich_vu`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT cho bảng `chuyen_bay_ngay`
--
ALTER TABLE `chuyen_bay_ngay`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=215;

--
-- AUTO_INCREMENT cho bảng `cua_ra_may_bay`
--
ALTER TABLE `cua_ra_may_bay`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=141;

--
-- AUTO_INCREMENT cho bảng `don_vi_mat_dat`
--
ALTER TABLE `don_vi_mat_dat`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT cho bảng `hang_hang_khong`
--
ALTER TABLE `hang_hang_khong`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT cho bảng `hoa_don`
--
ALTER TABLE `hoa_don`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT cho bảng `lich_bay_mua`
--
ALTER TABLE `lich_bay_mua`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT cho bảng `lich_su_chuyen_bay`
--
ALTER TABLE `lich_su_chuyen_bay`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=87;

--
-- AUTO_INCREMENT cho bảng `loai_tau_bay`
--
ALTER TABLE `loai_tau_bay`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT cho bảng `nguoi_dung`
--
ALTER TABLE `nguoi_dung`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT cho bảng `nhat_ky_lich_bay_mua`
--
ALTER TABLE `nhat_ky_lich_bay_mua`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT cho bảng `nhat_ky_trang_thai_bay`
--
ALTER TABLE `nhat_ky_trang_thai_bay`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=570;

--
-- AUTO_INCREMENT cho bảng `nha_ga`
--
ALTER TABLE `nha_ga`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT cho bảng `quay_checkin`
--
ALTER TABLE `quay_checkin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT cho bảng `san_bay`
--
ALTER TABLE `san_bay`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT cho bảng `thiet_bi_san_bay`
--
ALTER TABLE `thiet_bi_san_bay`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT cho bảng `yeu_cau_bao_tri`
--
ALTER TABLE `yeu_cau_bao_tri`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Các ràng buộc cho các bảng đã đổ
--

--
-- Các ràng buộc cho bảng `bang_chuyen_hanh_ly`
--
ALTER TABLE `bang_chuyen_hanh_ly`
  ADD CONSTRAINT `bang_chuyen_hanh_ly_ibfk_1` FOREIGN KEY (`id_nha_ga`) REFERENCES `nha_ga` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `chuyen_bay_ngay`
--
ALTER TABLE `chuyen_bay_ngay`
  ADD CONSTRAINT `chuyen_bay_ngay_ibfk_1` FOREIGN KEY (`id_lich_mua`) REFERENCES `lich_bay_mua` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `chuyen_bay_ngay_ibfk_2` FOREIGN KEY (`id_hang_hang_khong`) REFERENCES `hang_hang_khong` (`id`),
  ADD CONSTRAINT `chuyen_bay_ngay_ibfk_3` FOREIGN KEY (`id_loai_tau_bay`) REFERENCES `loai_tau_bay` (`id`),
  ADD CONSTRAINT `chuyen_bay_ngay_ibfk_4` FOREIGN KEY (`id_san_bay_di`) REFERENCES `san_bay` (`id`),
  ADD CONSTRAINT `chuyen_bay_ngay_ibfk_5` FOREIGN KEY (`id_san_bay_den`) REFERENCES `san_bay` (`id`),
  ADD CONSTRAINT `chuyen_bay_ngay_ibfk_6` FOREIGN KEY (`id_cua_ra_may_bay`) REFERENCES `cua_ra_may_bay` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `chuyen_bay_ngay_ibfk_7` FOREIGN KEY (`id_quay_checkin`) REFERENCES `quay_checkin` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `chuyen_bay_ngay_ibfk_8` FOREIGN KEY (`id_bang_chuyen`) REFERENCES `bang_chuyen_hanh_ly` (`id`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `cua_ra_may_bay`
--
ALTER TABLE `cua_ra_may_bay`
  ADD CONSTRAINT `cua_ra_may_bay_ibfk_1` FOREIGN KEY (`id_nha_ga`) REFERENCES `nha_ga` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `hoa_don`
--
ALTER TABLE `hoa_don`
  ADD CONSTRAINT `hoa_don_ibfk_1` FOREIGN KEY (`id_chuyen_bay`) REFERENCES `chuyen_bay_ngay` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `hoa_don_ibfk_2` FOREIGN KEY (`id_hang_hang_khong`) REFERENCES `hang_hang_khong` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `lich_bay_mua`
--
ALTER TABLE `lich_bay_mua`
  ADD CONSTRAINT `lich_bay_mua_ibfk_1` FOREIGN KEY (`id_hang_hang_khong`) REFERENCES `hang_hang_khong` (`id`),
  ADD CONSTRAINT `lich_bay_mua_ibfk_2` FOREIGN KEY (`id_san_bay_di`) REFERENCES `san_bay` (`id`),
  ADD CONSTRAINT `lich_bay_mua_ibfk_3` FOREIGN KEY (`id_san_bay_den`) REFERENCES `san_bay` (`id`);

--
-- Các ràng buộc cho bảng `lich_su_chuyen_bay`
--
ALTER TABLE `lich_su_chuyen_bay`
  ADD CONSTRAINT `fk_lich_su_chuyen_bay` FOREIGN KEY (`id_chuyen_bay`) REFERENCES `chuyen_bay_ngay` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `nguoi_dung`
--
ALTER TABLE `nguoi_dung`
  ADD CONSTRAINT `nguoi_dung_ibfk_1` FOREIGN KEY (`id_san_bay`) REFERENCES `san_bay` (`id`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `nhat_ky_trang_thai_bay`
--
ALTER TABLE `nhat_ky_trang_thai_bay`
  ADD CONSTRAINT `nhat_ky_trang_thai_bay_ibfk_1` FOREIGN KEY (`id_chuyen_bay`) REFERENCES `chuyen_bay_ngay` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `nhat_ky_trang_thai_bay_ibfk_2` FOREIGN KEY (`id_nguoi_dung`) REFERENCES `nguoi_dung` (`id`);

--
-- Các ràng buộc cho bảng `nha_ga`
--
ALTER TABLE `nha_ga`
  ADD CONSTRAINT `nha_ga_ibfk_1` FOREIGN KEY (`id_san_bay`) REFERENCES `san_bay` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `quay_checkin`
--
ALTER TABLE `quay_checkin`
  ADD CONSTRAINT `quay_checkin_ibfk_1` FOREIGN KEY (`id_nha_ga`) REFERENCES `nha_ga` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `thiet_bi_san_bay`
--
ALTER TABLE `thiet_bi_san_bay`
  ADD CONSTRAINT `thiet_bi_san_bay_ibfk_1` FOREIGN KEY (`id_san_bay`) REFERENCES `san_bay` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `yeu_cau_bao_tri`
--
ALTER TABLE `yeu_cau_bao_tri`
  ADD CONSTRAINT `yeu_cau_bao_tri_ibfk_1` FOREIGN KEY (`id_thiet_bi`) REFERENCES `thiet_bi_san_bay` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

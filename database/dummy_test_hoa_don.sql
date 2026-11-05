-- ============================================================
-- DỮ LIỆU DUMMY KIỂM THỬ TÍNH NĂNG THỜI GIAN ĐỖ & LẬP HÓA ĐƠN
-- File: database/dummy_test_hoa_don.sql
-- ============================================================
-- Cách dùng:
--   Mở phpMyAdmin -> Chọn database `quan_ly_chuoi_san_bay` -> tab SQL -> Dán nội dung file này và chạy.
--   Sau đó mở trình duyệt vào: http://localhost/AirportChainManagement/index.php?page=ke_toan
--   Ở mục "Chuyến bay đã hoàn thành", bạn sẽ thấy các chuyến TEST chờ lập hóa đơn.
-- ============================================================

-- 1. XÓA DỮ LIỆU TEST CŨ (NẾU CÓ) ĐỂ TRÁNH TRÙNG LẶP
DELETE FROM `chuyen_bay_ngay` WHERE `so_hieu` IN (
    'VN-TEST01', 'VN-TEST02', 
    'VJ-TEST03', 'VJ-TEST04', 
    'QH-TEST05', 
    'QH-TEST06', 'QH-TEST07'
);

-- ============================================================
-- 2. CHÈN CÁC CHUYẾN BAY THEO 4 KỊCH BẢN KIỂM THỬ
-- ============================================================

-- ------------------------------------------------------------
-- CA 1: Tàu bay thân hẹp, đỗ tiêu chuẩn 1.5 giờ
-- - Chuyến hiện tại: VN-TEST01 hạ cánh tại HAN (id 2) lúc 08:00
-- - Chuyến kế tiếp: VN-TEST02 cất cánh từ HAN lúc 09:30 (cùng hãng VN id 1, loại A320neo id 4 - 79 tấn)
-- -> Kỳ vọng: Thời gian đỗ = 1.5 giờ, Không tạm tính.
-- -> Phí: 1.500.000 (cất/hạ) + 50.000 * 79 * 1.5 (5.925.000 đỗ) + 100.000 * 180 (18.000.000 khách) = 25.425.000 VNĐ.
-- ------------------------------------------------------------
INSERT INTO `chuyen_bay_ngay` 
  (`id_hang_hang_khong`, `id_loai_tau_bay`, `id_san_bay_di`, `id_san_bay_den`, `so_hieu`, `gio_du_kien`, `gio_thuc_te`, `luong_hanh_khach`, `luong_hang_hoa_tan`, `trang_thai_tong`) 
VALUES 
  (1, 4, 1, 2, 'VN-TEST01', '2026-11-06 07:50:00', '2026-11-06 08:00:00', 180, 2.50, 'Ha_Canh'),
  (1, 4, 2, 3, 'VN-TEST02', '2026-11-06 09:30:00', NULL, 175, 2.10, 'Dang_Mo');

-- ------------------------------------------------------------
-- CA 2: Tàu bay thân rộng, đỗ 3.0 giờ
-- - Chuyến hiện tại: VJ-TEST03 hạ cánh tại HAN (id 2) lúc 10:00
-- - Chuyến kế tiếp: VJ-TEST04 cất cánh từ HAN lúc 13:00 (cùng hãng VJ id 2, loại B787-9 id 2 - 254 tấn)
-- -> Kỳ vọng: Thời gian đỗ = 3.0 giờ, Không tạm tính.
-- -> Phí: 1.500.000 (cất/hạ) + 50.000 * 254 * 3 (38.100.000 đỗ) + 100.000 * 300 (30.000.000 khách) = 69.600.000 VNĐ.
-- ------------------------------------------------------------
INSERT INTO `chuyen_bay_ngay` 
  (`id_hang_hang_khong`, `id_loai_tau_bay`, `id_san_bay_di`, `id_san_bay_den`, `so_hieu`, `gio_du_kien`, `gio_thuc_te`, `luong_hanh_khach`, `luong_hang_hoa_tan`, `trang_thai_tong`) 
VALUES 
  (2, 2, 1, 2, 'VJ-TEST03', '2026-11-06 09:45:00', '2026-11-06 10:00:00', 300, 5.00, 'Ha_Canh'),
  (2, 2, 2, 1, 'VJ-TEST04', '2026-11-06 13:00:00', NULL, 290, 4.80, 'Dang_Mo');

-- ------------------------------------------------------------
-- CA 3: Chuyến cuối ngày - Không có chuyến kế tiếp
-- - Chuyến hiện tại: QH-TEST05 hạ cánh tại DAD (id 3) lúc 23:45
-- - Không có chuyến kế tiếp nào của hãng Bamboo (id 3) cùng loại A321 (id 1 - 89 tấn) cất cánh từ DAD sau giờ này.
-- -> Kỳ vọng: Thời gian đỗ = 1.5 giờ (mặc định), Cờ Tạm tính = 1, Ghi chú: "Giờ đỗ mặc định, chưa có chuyến kế tiếp".
-- -> Phí: 1.500.000 + 50.000 * 89 * 1.5 (6.675.000) + 100.000 * 200 (20.000.000) = 28.175.000 VNĐ.
-- ------------------------------------------------------------
INSERT INTO `chuyen_bay_ngay` 
  (`id_hang_hang_khong`, `id_loai_tau_bay`, `id_san_bay_di`, `id_san_bay_den`, `so_hieu`, `gio_du_kien`, `gio_thuc_te`, `luong_hanh_khach`, `luong_hang_hoa_tan`, `trang_thai_tong`) 
VALUES 
  (3, 1, 2, 3, 'QH-TEST05', '2026-11-06 23:30:00', '2026-11-06 23:45:00', 200, 3.20, 'Ha_Canh');

-- ------------------------------------------------------------
-- CA 4: Tàu bay đỗ qua đêm / bảo trì kỹ thuật > 24 giờ
-- - Chuyến hiện tại: QH-TEST06 hạ cánh tại SGN (id 1) lúc 08:00 ngày 04/11
-- - Chuyến kế tiếp: QH-TEST07 cất cánh từ SGN lúc 14:00 ngày 05/11 (đỗ 30 giờ, hãng Bamboo id 3, loại A320 id 15 - 77 tấn)
-- -> Kỳ vọng: Thời gian đỗ = 30.0 giờ, Cờ Tạm tính = 1, Ghi chú: "Giờ đỗ trên 24h, cần kế toán kiểm tra".
-- -> Phí: 1.500.000 + 50.000 * 77 * 30 (115.500.000) + 100.000 * 150 (15.000.000) = 132.000.000 VNĐ.
-- ------------------------------------------------------------
INSERT INTO `chuyen_bay_ngay` 
  (`id_hang_hang_khong`, `id_loai_tau_bay`, `id_san_bay_di`, `id_san_bay_den`, `so_hieu`, `gio_du_kien`, `gio_thuc_te`, `luong_hanh_khach`, `luong_hang_hoa_tan`, `trang_thai_tong`) 
VALUES 
  (3, 15, 3, 1, 'QH-TEST06', '2026-11-04 07:45:00', '2026-11-04 08:00:00', 150, 1.80, 'Ha_Canh'),
  (3, 15, 1, 2, 'QH-TEST07', '2026-11-05 14:00:00', NULL, 160, 2.00, 'Dang_Mo');

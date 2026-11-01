<?php
class KeToanController {
    private $conn;
    public function __construct($db) {
        $this->conn = $db;
    }

//1. QUẢN LÝ ĐƠN GIÁ

// Danh sách đơn giá
    public function DanhSachDonGia() {
        $sql = "SELECT * FROM bang_gia_dich_vu";
        $result = mysqli_query($this->conn, $sql);
        $data = [];
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $data[] = $row;
            }
        }
        return $data;
    }
// Lấy một dòng đơn giá theo id
    public function LayDonGia($id) {
        $id = (int) $id;
        $stmt = mysqli_prepare($this->conn, "SELECT * FROM bang_gia_dich_vu WHERE id = ?");
        if (!$stmt) return null;
        mysqli_stmt_bind_param($stmt, 'i', $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = $result ? mysqli_fetch_assoc($result) : null;
        mysqli_stmt_close($stmt);
        return $row ?: null;
    }

// Cập nhật đơn giá + đơn vị tính
    public function CapNhatDonGia($id, $gia, $donVi) {
        $id = (int) $id;
        $stmt = mysqli_prepare($this->conn, "UPDATE bang_gia_dich_vu SET don_gia = ?, don_vi_tinh = ? WHERE id = ?");
        if (!$stmt) return false;
        mysqli_stmt_bind_param($stmt, 'dsi', $gia, $donVi, $id);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $ok;
    }

// Thêm đơn giá mới
    public function ThemDonGia($ten, $gia, $don_vi) {
        $ten = mysqli_real_escape_string($this->conn, $ten);
        $gia = mysqli_real_escape_string($this->conn, $gia);
        $don_vi = mysqli_real_escape_string($this->conn, $don_vi);
        $sql = "INSERT INTO bang_gia_dich_vu (loai_dich_vu, don_gia, don_vi_tinh) 
                VALUES ('$ten', '$gia', '$don_vi')";
        return mysqli_query($this->conn, $sql);
    }

//2. PHÁT HÀNH HÓA ĐƠN

  //Các chuyến bay đã hoàn thành
    public function ChuyenBayHoanThanh() {
        $sql = "SELECT * FROM chuyen_bay_ngay WHERE trang_thai_tong = 'Hoan_Thanh'";
        $result = mysqli_query($this->conn, $sql);
        $data = [];
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $data[] = $row;
            }
        }
        return $data;
    }

// Tính toán và tạo hóa đơn cho chuyến bay
        public function TinhVaTaoHoaDon($id_chuyen_bay) {
        $id_chuyen_bay = (int)$id_chuyen_bay; 
        $sql_info = "SELECT cb.*, tb.tai_trong_cat_canh_mtow 
                     FROM chuyen_bay_ngay cb 
                     JOIN loai_tau_bay tb ON cb.id_loai_tau_bay = tb.id 
                     WHERE cb.id = $id_chuyen_bay";
        $result_info = mysqli_query($this->conn, $sql_info);
        $cb = mysqli_fetch_assoc($result_info);
        if ($cb) {
            $time_du_kien = strtotime($cb['gio_du_kien']);
            $time_thuc_te = $cb['gio_thuc_te'] ? strtotime($cb['gio_thuc_te']) : time();
            $so_gio_do = ($time_thuc_te - $time_du_kien) / 3600;
            if ($so_gio_do <= 0) $so_gio_do = 1; 
            $result_gia = mysqli_query($this->conn, "SELECT loai_dich_vu, don_gia 
                                                     FROM bang_gia_dich_vu");
            $bang_gia = [];
            while ($row = mysqli_fetch_assoc($result_gia)) {
                $bang_gia[$row['loai_dich_vu']] = $row['don_gia'];
            }
            $gia_ha_canh = $bang_gia['Phi_Cat_Ha_Canh'] ?? 0;
            $gia_do = $bang_gia['Phi_Do_Tau_Bay'] ?? 0;
            $phi_psc = $bang_gia['Phi_Phuc_Vu_Hanh_Khach'] ?? 0;
            $phi_cat_ha_canh = $cb['tai_trong_cat_canh_mtow'] * $gia_ha_canh;
            $phi_do_tau_bay = $so_gio_do * $gia_do;
            $phi_phuc_vu_hk = $cb['luong_hanh_khach'] * $phi_psc;
            $tong_tien = $phi_cat_ha_canh + $phi_do_tau_bay + $phi_phuc_vu_hk;
            $id_hang = (int) ($cb['id_hang_hang_khong'] ?? 0);
            $sql_insert = "INSERT INTO hoa_don (id_chuyen_bay, id_hang_hang_khong, phi_cat_ha_canh, phi_do_tau_bay, phi_phuc_vu_hk, tong_tien, ngay_xuat, trang_thai_thanh_toan) 
                           VALUES ($id_chuyen_bay, $id_hang, $phi_cat_ha_canh, $phi_do_tau_bay, $phi_phuc_vu_hk, $tong_tien, NOW(), 'Chua_Thanh_Toan')";
            return mysqli_query($this->conn, $sql_insert);
        }
        return false;
    }

//3. DASHBOARD

// Doanh thu theo tháng
    public function DoanhThuTheoThang() {
        $sql = "SELECT MONTH(ngay_xuat) as thang, SUM(tong_tien) as tong_doanh_thu 
                FROM hoa_don 
                WHERE trang_thai_thanh_toan = 'Da_Thanh_Toan' 
                GROUP BY MONTH(ngay_xuat) 
                ORDER BY thang ASC";
        $result = mysqli_query($this->conn, $sql);
        $data = [];
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $data[] = $row;
            }
        }
        return $data;
    }

// Doanh thu theo hãng hàng không
    public function DoanhThuTheoHang() {
        $sql = "SELECT hk.ten_hang, SUM(hd.tong_tien) as doanh_thu 
                FROM hoa_don hd
                JOIN chuyen_bay_ngay cb ON hd.id_chuyen_bay = cb.id
                JOIN hang_hang_khong hk ON cb.id_hang_hang_khong = hk.id
                WHERE hd.trang_thai_thanh_toan = 'Da_Thanh_Toan'
                GROUP BY hk.id, hk.ten_hang
                ORDER BY doanh_thu DESC";
        $result = mysqli_query($this->conn, $sql);
        $data = [];
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $data[] = $row;
            }
        }
        return $data;
    }

// Thống kê nhanh
    public function ThongKeNhanh() {
        $stats = []; 
        $sql1 = "SELECT SUM(tong_tien) as doanh_thu 
                 FROM hoa_don 
                 WHERE trang_thai_thanh_toan = 'Da_Thanh_Toan' 
                 AND MONTH(ngay_xuat) = MONTH(CURRENT_DATE())";
        $stats['doanh_thu_thang'] = mysqli_fetch_assoc(mysqli_query($this->conn, $sql1))['doanh_thu'] ?? 0;
        $sql2 = "SELECT COUNT(*) as sl
                 FROM hoa_don 
                 WHERE trang_thai_thanh_toan = 'Da_Thanh_Toan'";
        $stats['hd_da_thu'] = mysqli_fetch_assoc(mysqli_query($this->conn, $sql2))['sl'] ?? 0;
        $sql3 = "SELECT COUNT(*) as sl 
                 FROM hoa_don 
                 WHERE trang_thai_thanh_toan = 'Chua_Thanh_Toan'";
        $stats['hd_chua_thu'] = mysqli_fetch_assoc(mysqli_query($this->conn, $sql3))['sl'] ?? 0;
        $sql4 = "SELECT SUM(tong_tien) as tien_no 
                 FROM hoa_don 
                 WHERE trang_thai_thanh_toan = 'Chua_Thanh_Toan' 
                 AND DATE_ADD(ngay_xuat, INTERVAL 30 DAY) < CURRENT_DATE()";
        $stats['no_qua_han'] = mysqli_fetch_assoc(mysqli_query($this->conn, $sql4))['tien_no'] ?? 0;
        $sql5 = "SELECT COUNT(*) as sl 
                 FROM chuyen_bay_ngay 
                 WHERE DATE(gio_thuc_te) = CURRENT_DATE()";
        $stats['chuyen_bay_trong_ngay'] = mysqli_fetch_assoc(mysqli_query($this->conn, $sql5))['sl'] ?? 0;
        return $stats;
    }

//Danh sách hóa đơn chờ
    public function DanhSachHoaDonCho() {
        $sql = "SELECT hd.*, DATE_ADD(hd.ngay_xuat, INTERVAL 30 DAY) AS han_thanh_toan, cb.so_hieu 
                FROM hoa_don hd 
                JOIN chuyen_bay_ngay cb ON hd.id_chuyen_bay = cb.id 
                WHERE hd.trang_thai_thanh_toan = 'Chua_Thanh_Toan'";
        $result = mysqli_query($this->conn, $sql);
        $data = [];
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $data[] = $row;
            }
        }
        return $data;
    }

// Xác nhận thanh toán hóa đơn
    public function XacNhanThanhToan($id_hoa_don) {
        $id_hoa_don = (int)$id_hoa_don;
        $sql = "UPDATE hoa_don SET trang_thai_thanh_toan = 'Da_Thanh_Toan' WHERE id = $id_hoa_don";
        return mysqli_query($this->conn, $sql);
    }
    // Lấy chi tiết hóa đơn
    public function LayChiTietHoaDon($id_hoa_don) {
        $id_hoa_don = (int)$id_hoa_don;
        $sql = "SELECT hd.*, cb.so_hieu, cb.luong_hanh_khach, hk.ten_hang, tb.tai_trong_cat_canh_mtow
                FROM hoa_don hd
                JOIN chuyen_bay_ngay cb ON hd.id_chuyen_bay = cb.id
                JOIN hang_hang_khong hk ON cb.id_hang_hang_khong = hk.id
                JOIN loai_tau_bay tb ON cb.id_loai_tau_bay = tb.id
                WHERE hd.id = $id_hoa_don";
        $result = mysqli_query($this->conn, $sql);
        return mysqli_fetch_assoc($result);
    }

//4. THỐNG KÊ SÂN BAY

// Lấy danh sách sân bay
    public function DanhSachSanBay() {
        $sql = "SELECT * FROM san_bay";
        $result = mysqli_query($this->conn, $sql);
        $data = [];
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $data[] = $row;
            }
        }
        return $data;
    }

// Thống kê tổng hành khách qua sân bay
    public function ThongKeHanhKhachSanBay($id_san_bay) {
        $id_san_bay = (int)$id_san_bay;
        $sql = "SELECT SUM(luong_hanh_khach) as tong_khach 
                FROM chuyen_bay_ngay 
                WHERE (id_san_bay_di = $id_san_bay OR id_san_bay_den = $id_san_bay)
                AND trang_thai_tong = 'Hoan_Thanh'";
        $result = mysqli_query($this->conn, $sql);
        $row = mysqli_fetch_assoc($result);
        return $row['tong_khach'] ?? 0;
    }
}
?>
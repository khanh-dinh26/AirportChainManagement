<?php
class KeToanController {
    private $conn;

    private const GIO_DO_MAC_DINH = 1.5;
    private const GIO_DO_TOI_THIEU = 0.5;
    private const GIO_DO_NGUONG_KIEM_TRA = 24;
    private const TRANG_THAI_DA_HA_CANH = ['Hoan_Thanh', 'Hạ_Cánh', 'Hạ cánh', 'Ha_Canh', 'Đã hạ cánh'];

    public function __construct($db) {
        $this->conn = $db;
    }

//1. QUẢN LÝ ĐƠN GIÁ
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

    public function CapNhatDonGia($id, $gia, $donVi) {
        $id = (int) $id;
        $stmt = mysqli_prepare($this->conn, "UPDATE bang_gia_dich_vu SET don_gia = ?, don_vi_tinh = ? WHERE id = ?");
        if (!$stmt) return false;
        mysqli_stmt_bind_param($stmt, 'dsi', $gia, $donVi, $id);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $ok;
    }

//2. PHÁT HÀNH HÓA ĐƠN
    public function ChuyenBayHoanThanh() {
        $escaped = "'" . implode("','", array_map(function($s) { return mysqli_real_escape_string($this->conn, $s); }, self::TRANG_THAI_DA_HA_CANH)) . "'";
        $sql = "SELECT cb.*, hk.ten_hang, tb.tai_trong_cat_canh_mtow 
                FROM chuyen_bay_ngay cb 
                LEFT JOIN hang_hang_khong hk ON cb.id_hang_hang_khong = hk.id 
                LEFT JOIN loai_tau_bay tb ON cb.id_loai_tau_bay = tb.id
                WHERE cb.trang_thai_tong IN ($escaped)
                AND cb.id NOT IN (SELECT id_chuyen_bay FROM hoa_don)"; 
                
        $result = mysqli_query($this->conn, $sql);
        if (!$result) {
            die("Lỗi truy vấn SQL: " . mysqli_error($this->conn));
        }
        $data = [];
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $data[] = $row;
            }
        }
        return $data;
    }

    public function TuDongTaoHoaDon() {
        $escaped = "'" . implode("','", array_map(function($s) { return mysqli_real_escape_string($this->conn, $s); }, self::TRANG_THAI_DA_HA_CANH)) . "'";
        $sql = "SELECT cb.id 
                FROM chuyen_bay_ngay cb 
                WHERE cb.trang_thai_tong IN ($escaped) 
                AND cb.id NOT IN (SELECT id_chuyen_bay FROM hoa_don)";
        $result = mysqli_query($this->conn, $sql);
        if ($result && mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                $this->TinhVaTaoHoaDon((int) $row['id']);
            }
        }
    }

    /**
     * @return array{gio: float, tam_tinh: bool, ghi_chu: ?string}
     */
    private function tinhThoiGianDoTuDong(int $id_chuyen_bay): array
    {
        $macDinh = ['gio' => self::GIO_DO_MAC_DINH, 'tam_tinh' => true,
                    'ghi_chu' => 'Giờ đỗ mặc định, chưa có chuyến kế tiếp'];

        $stmt = mysqli_prepare($this->conn,
            "SELECT id_san_bay_den, id_loai_tau_bay, id_hang_hang_khong, gio_thuc_te
             FROM chuyen_bay_ngay WHERE id = ?");
        if (!$stmt) return $macDinh;
        mysqli_stmt_bind_param($stmt, 'i', $id_chuyen_bay);
        mysqli_stmt_execute($stmt);
        $cur = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);

        if (!$cur || empty($cur['gio_thuc_te'])) {
            $macDinh['ghi_chu'] = 'Thiếu giờ thực tế của chuyến hiện tại';
            return $macDinh;
        }

        // Chuyến kế tiếp: cùng hãng, cùng loại tàu, khởi hành từ sân bay đến của chuyến hiện tại
        $stmt = mysqli_prepare($this->conn,
            "SELECT gio_du_kien FROM chuyen_bay_ngay
             WHERE id_san_bay_di = ? AND id_loai_tau_bay = ? AND id_hang_hang_khong = ?
               AND gio_du_kien > ? AND id <> ? AND trang_thai_tong NOT IN ('Huy')
             ORDER BY gio_du_kien ASC LIMIT 1");
        if (!$stmt) return $macDinh;
        $sbDen = (int) $cur['id_san_bay_den'];
        $loai  = (int) $cur['id_loai_tau_bay'];
        $hang  = (int) $cur['id_hang_hang_khong'];
        $gio   = $cur['gio_thuc_te'];
        mysqli_stmt_bind_param($stmt, 'iiisi', $sbDen, $loai, $hang, $gio, $id_chuyen_bay);
        mysqli_stmt_execute($stmt);
        $next = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);

        if (!$next || empty($next['gio_du_kien'])) return $macDinh;

        $hours = (strtotime($next['gio_du_kien']) - strtotime($gio)) / 3600;
        $hours = max(self::GIO_DO_TOI_THIEU, round($hours, 2));

        return [
            'gio'      => $hours,
            'tam_tinh' => $hours > self::GIO_DO_NGUONG_KIEM_TRA,
            'ghi_chu'  => $hours > self::GIO_DO_NGUONG_KIEM_TRA ? 'Giờ đỗ trên 24h, cần kế toán kiểm tra' : null,
        ];
    }

    /** Tính phí một chuyến, không ghi CSDL. Trả về null nếu thiếu dữ liệu. */
    private function tinhPhiChuyenBay(int $id_chuyen_bay): ?array
    {
        $stmt = mysqli_prepare($this->conn,
            "SELECT cb.id_hang_hang_khong, cb.luong_hanh_khach, tb.tai_trong_cat_canh_mtow
             FROM chuyen_bay_ngay cb
             JOIN loai_tau_bay tb ON cb.id_loai_tau_bay = tb.id
             WHERE cb.id = ?");
        if (!$stmt) return null;
        mysqli_stmt_bind_param($stmt, 'i', $id_chuyen_bay);
        mysqli_stmt_execute($stmt);
        $cb = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);
        if (!$cb) return null;

        // Biểu phí: KHÔNG dùng giá mặc định cứng, thiếu giá thì dừng
        $bang_gia = [];
        $rs = mysqli_query($this->conn, "SELECT loai_dich_vu, don_gia FROM bang_gia_dich_vu");
        while ($rs && $row = mysqli_fetch_assoc($rs)) {
            $bang_gia[$row['loai_dich_vu']] = (float) $row['don_gia'];
        }
        foreach (['Phi_Cat_Ha_Canh', 'Phi_Do_Tau_Bay', 'Phi_Phuc_Vu_Hanh_Khach'] as $k) {
            if (!isset($bang_gia[$k]) || $bang_gia[$k] <= 0) return null;
        }

        $mtow     = (float) ($cb['tai_trong_cat_canh_mtow'] ?? 0);   // đơn vị: tấn
        $so_khach = (int) ($cb['luong_hanh_khach'] ?? 0);
        $do       = $this->tinhThoiGianDoTuDong($id_chuyen_bay);

        $phi_cat_ha_canh = $bang_gia['Phi_Cat_Ha_Canh'];                          // 1 lượt
        $phi_do_tau_bay  = $bang_gia['Phi_Do_Tau_Bay'] * $mtow * $do['gio'];       // đơn giá x tấn x giờ
        $phi_phuc_vu_hk  = $bang_gia['Phi_Phuc_Vu_Hanh_Khach'] * $so_khach;

        // Làm tròn một lần ở khoản cuối cùng, mỗi khoản làm tròn về VNĐ
        $phi_cat_ha_canh = round($phi_cat_ha_canh);
        $phi_do_tau_bay  = round($phi_do_tau_bay);
        $phi_phuc_vu_hk  = round($phi_phuc_vu_hk);

        return [
            'id_hang'         => (int) $cb['id_hang_hang_khong'],
            'mtow'            => $mtow,
            'so_khach'        => $so_khach,
            'gio_do'          => $do['gio'],
            'tam_tinh'        => $do['tam_tinh'] ? 1 : 0,
            'ghi_chu'         => $do['ghi_chu'],
            'dg_cat_ha_canh'  => $bang_gia['Phi_Cat_Ha_Canh'],
            'dg_do'           => $bang_gia['Phi_Do_Tau_Bay'],
            'dg_phuc_vu'      => $bang_gia['Phi_Phuc_Vu_Hanh_Khach'],
            'phi_cat_ha_canh' => $phi_cat_ha_canh,
            'phi_do_tau_bay'  => $phi_do_tau_bay,
            'phi_phuc_vu_hk'  => $phi_phuc_vu_hk,
            'tong_tien'       => $phi_cat_ha_canh + $phi_do_tau_bay + $phi_phuc_vu_hk,
        ];
    }

    public function TinhVaTaoHoaDon($id_chuyen_bay)
    {
        $id_chuyen_bay = (int) $id_chuyen_bay;
        $p = $this->tinhPhiChuyenBay($id_chuyen_bay);
        if (!$p) return false;

        $stmt = mysqli_prepare($this->conn, "
            INSERT INTO hoa_don
              (id_chuyen_bay, id_hang_hang_khong, phi_cat_ha_canh, phi_do_tau_bay, phi_phuc_vu_hk,
               tong_tien, ngay_xuat, trang_thai_thanh_toan,
               thoi_gian_do_gio, don_gia_cat_ha_canh, don_gia_do, don_gia_phuc_vu,
               mtow_ap_dung, so_khach_ap_dung, tam_tinh, ghi_chu)
            VALUES (?, ?, ?, ?, ?, ?, NOW(), 'Chua_Thanh_Toan', ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        if (!$stmt) return false;

        mysqli_stmt_bind_param($stmt, 'iidddddddddiis',
            $id_chuyen_bay, $p['id_hang'],
            $p['phi_cat_ha_canh'], $p['phi_do_tau_bay'], $p['phi_phuc_vu_hk'], $p['tong_tien'],
            $p['gio_do'], $p['dg_cat_ha_canh'], $p['dg_do'], $p['dg_phuc_vu'],
            $p['mtow'], $p['so_khach'], $p['tam_tinh'], $p['ghi_chu']);

        try {
            $ok = mysqli_stmt_execute($stmt);
        } catch (mysqli_sql_exception $ex) {
            $ok = false;   // trùng khóa UNIQUE: chuyến này đã có hóa đơn
        }
        mysqli_stmt_close($stmt);
        return $ok;
    }

    public function TinhLaiHoaDon(int $id_hoa_don, string $ly_do): bool
    {
        $stmt = mysqli_prepare($this->conn,
            "SELECT id_chuyen_bay, tong_tien, trang_thai_thanh_toan FROM hoa_don WHERE id = ?");
        if (!$stmt) return false;
        mysqli_stmt_bind_param($stmt, 'i', $id_hoa_don);
        mysqli_stmt_execute($stmt);
        $hd = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);

        // Chỉ tính lại hóa đơn chưa thanh toán
        if (!$hd || $hd['trang_thai_thanh_toan'] !== 'Chua_Thanh_Toan') return false;

        $p = $this->tinhPhiChuyenBay((int) $hd['id_chuyen_bay']);
        if (!$p) return false;

        mysqli_begin_transaction($this->conn);
        try {
            $up = mysqli_prepare($this->conn, "
                UPDATE hoa_don SET phi_cat_ha_canh=?, phi_do_tau_bay=?, phi_phuc_vu_hk=?, tong_tien=?,
                    thoi_gian_do_gio=?, don_gia_cat_ha_canh=?, don_gia_do=?, don_gia_phuc_vu=?,
                    mtow_ap_dung=?, so_khach_ap_dung=?, tam_tinh=?, ghi_chu=?
                WHERE id=?");
            if (!$up) {
                mysqli_rollback($this->conn);
                return false;
            }
            mysqli_stmt_bind_param($up, 'dddddddddiisi',
                $p['phi_cat_ha_canh'], $p['phi_do_tau_bay'], $p['phi_phuc_vu_hk'], $p['tong_tien'],
                $p['gio_do'], $p['dg_cat_ha_canh'], $p['dg_do'], $p['dg_phuc_vu'],
                $p['mtow'], $p['so_khach'], $p['tam_tinh'], $p['ghi_chu'], $id_hoa_don);
            mysqli_stmt_execute($up);
            mysqli_stmt_close($up);

            $lg = mysqli_prepare($this->conn,
                "INSERT INTO hoa_don_dieu_chinh_log (id_hoa_don, tong_tien_cu, tong_tien_moi, ly_do) VALUES (?,?,?,?)");
            if ($lg) {
                $cu = (float) $hd['tong_tien'];
                mysqli_stmt_bind_param($lg, 'idds', $id_hoa_don, $cu, $p['tong_tien'], $ly_do);
                mysqli_stmt_execute($lg);
                mysqli_stmt_close($lg);
            }

            mysqli_commit($this->conn);
            return true;
        } catch (Throwable $ex) {
            mysqli_rollback($this->conn);
            return false;
        }
    }

//3. DASHBOARD
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

    public function ThongKeNhanh() {
        $stats = []; 
        $sql1 = "SELECT SUM(tong_tien) as doanh_thu FROM hoa_don WHERE trang_thai_thanh_toan = 'Da_Thanh_Toan' AND MONTH(ngay_xuat) = MONTH(CURRENT_DATE())";
        $stats['doanh_thu_thang'] = mysqli_fetch_assoc(mysqli_query($this->conn, $sql1))['doanh_thu'] ?? 0;
        
        $sql2 = "SELECT COUNT(*) as sl FROM hoa_don WHERE trang_thai_thanh_toan = 'Da_Thanh_Toan'";
        $stats['hd_da_thu'] = mysqli_fetch_assoc(mysqli_query($this->conn, $sql2))['sl'] ?? 0;
        
        $sql3 = "SELECT COUNT(*) as sl FROM hoa_don WHERE trang_thai_thanh_toan = 'Chua_Thanh_Toan'";
        $stats['hd_chua_thu'] = mysqli_fetch_assoc(mysqli_query($this->conn, $sql3))['sl'] ?? 0;
        
        $sql4 = "SELECT SUM(tong_tien) as tien_no FROM hoa_don WHERE trang_thai_thanh_toan = 'Chua_Thanh_Toan' AND DATE_ADD(ngay_xuat, INTERVAL 30 DAY) < CURRENT_DATE()";
        $stats['no_qua_han'] = mysqli_fetch_assoc(mysqli_query($this->conn, $sql4))['tien_no'] ?? 0;
        
        $sql5 = "SELECT COUNT(*) as sl FROM chuyen_bay_ngay WHERE DATE(gio_thuc_te) = CURRENT_DATE()";
        $stats['chuyen_bay_trong_ngay'] = mysqli_fetch_assoc(mysqli_query($this->conn, $sql5))['sl'] ?? 0;
        
        return $stats;
    }

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

    public function XacNhanThanhToan($id_hoa_don) {
        $id_hoa_don = (int)$id_hoa_don;
        $sql = "UPDATE hoa_don SET trang_thai_thanh_toan = 'Da_Thanh_Toan' WHERE id = $id_hoa_don";
        return mysqli_query($this->conn, $sql);
    }

    public function LayChiTietHoaDon($id_hoa_don) {
        $id_hoa_don = (int)$id_hoa_don;
        $sql = "SELECT hd.*, cb.so_hieu, cb.luong_hanh_khach, hk.ten_hang, tb.tai_trong_cat_canh_mtow,
                       sb_di.ten_san_bay as ten_san_bay_di, 
                       sb_den.ten_san_bay as ten_san_bay_den
                FROM hoa_don hd
                JOIN chuyen_bay_ngay cb ON hd.id_chuyen_bay = cb.id
                JOIN hang_hang_khong hk ON cb.id_hang_hang_khong = hk.id
                JOIN loai_tau_bay tb ON cb.id_loai_tau_bay = tb.id
                JOIN san_bay sb_di ON cb.id_san_bay_di = sb_di.id
                JOIN san_bay sb_den ON cb.id_san_bay_den = sb_den.id
                WHERE hd.id = $id_hoa_don";
        $result = mysqli_query($this->conn, $sql);
        return mysqli_fetch_assoc($result);
    }

    public function DemLichSuHoaDon($trangThai = null): int {
        $sql = "SELECT COUNT(*) as total FROM hoa_don hd";
        if ($trangThai !== null && in_array($trangThai, ['Chua_Thanh_Toan', 'Da_Thanh_Toan'], true)) {
            $sql .= " WHERE hd.trang_thai_thanh_toan = '" . mysqli_real_escape_string($this->conn, $trangThai) . "'";
        }
        $result = mysqli_query($this->conn, $sql);
        $row = $result ? mysqli_fetch_assoc($result) : null;
        return (int) ($row['total'] ?? 0);
    }

    public function TongTienLichSuHoaDon($trangThai = null): float {
        $sql = "SELECT SUM(tong_tien) as tong FROM hoa_don hd";
        if ($trangThai !== null && in_array($trangThai, ['Chua_Thanh_Toan', 'Da_Thanh_Toan'], true)) {
            $sql .= " WHERE hd.trang_thai_thanh_toan = '" . mysqli_real_escape_string($this->conn, $trangThai) . "'";
        }
        $result = mysqli_query($this->conn, $sql);
        $row = $result ? mysqli_fetch_assoc($result) : null;
        return (float) ($row['tong'] ?? 0);
    }

    public function LichSuHoaDon($trangThai = null, $limit = 10, $offset = 0) {
        $limit = (int) $limit > 0 ? (int) $limit : 10;
        $offset = (int) $offset >= 0 ? (int) $offset : 0;
        $sql = "SELECT hd.*, cb.so_hieu, hk.ten_hang,
                       sb_di.ten_san_bay as ten_san_bay_di, 
                       sb_den.ten_san_bay as ten_san_bay_den
                FROM hoa_don hd 
                JOIN chuyen_bay_ngay cb ON hd.id_chuyen_bay = cb.id 
                JOIN hang_hang_khong hk ON hd.id_hang_hang_khong = hk.id
                JOIN san_bay sb_di ON cb.id_san_bay_di = sb_di.id
                JOIN san_bay sb_den ON cb.id_san_bay_den = sb_den.id";
        
        if ($trangThai !== null && in_array($trangThai, ['Chua_Thanh_Toan', 'Da_Thanh_Toan'], true)) {
            $sql .= " WHERE hd.trang_thai_thanh_toan = '" . mysqli_real_escape_string($this->conn, $trangThai) . "'";
        }
        
        $sql .= " ORDER BY hd.id DESC LIMIT $limit OFFSET $offset";
        
        $result = mysqli_query($this->conn, $sql);
        $data = [];
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $data[] = $row;
            }
        }
        return $data;
    }

    public function LayHoaDonTheoChuyenBay(int $id_chuyen_bay): ?array {
        $stmt = mysqli_prepare($this->conn, "SELECT id, tong_tien, trang_thai_thanh_toan, ngay_xuat FROM hoa_don WHERE id_chuyen_bay = ? LIMIT 1");
        if (!$stmt) return null;
        mysqli_stmt_bind_param($stmt, 'i', $id_chuyen_bay);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = $res ? mysqli_fetch_assoc($res) : null;
        mysqli_stmt_close($stmt);
        return $row ?: null;
    }

//4. THỐNG KÊ SÂN BAY
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

    public function ThongKeHanhKhachSanBay($id_san_bay, $tu_nam = null, $den_nam = null) {
        $id_san_bay = (int)$id_san_bay;
        $escaped = "'" . implode("','", array_map(function($s) { return mysqli_real_escape_string($this->conn, $s); }, self::TRANG_THAI_DA_HA_CANH)) . "'";
        $sql = "SELECT SUM(luong_hanh_khach) as tong_khach 
                FROM chuyen_bay_ngay 
                WHERE (id_san_bay_di = $id_san_bay OR id_san_bay_den = $id_san_bay)
                AND trang_thai_tong IN ($escaped)";
        if ($tu_nam !== null && $den_nam !== null) {
            $tu_nam = (int)$tu_nam;
            $den_nam = (int)$den_nam;
            $sql .= " AND YEAR(COALESCE(gio_thuc_te, gio_du_kien)) >= $tu_nam AND YEAR(COALESCE(gio_thuc_te, gio_du_kien)) <= $den_nam";
        }
        $result = mysqli_query($this->conn, $sql);
        $row = $result ? mysqli_fetch_assoc($result) : null;
        return (int) ($row['tong_khach'] ?? 0);
    }

    public function ChiTietKhachTheoThang($id_san_bay, $tu_nam, $den_nam) {
        $id_san_bay = (int)$id_san_bay;
        $tu_nam = (int)$tu_nam;
        $den_nam = (int)$den_nam;
        $escaped = "'" . implode("','", array_map(function($s) { return mysqli_real_escape_string($this->conn, $s); }, self::TRANG_THAI_DA_HA_CANH)) . "'";
        $sql = "SELECT YEAR(COALESCE(gio_thuc_te, gio_du_kien)) as nam, MONTH(COALESCE(gio_thuc_te, gio_du_kien)) as thang, SUM(luong_hanh_khach) as tong_khach 
                FROM chuyen_bay_ngay 
                WHERE (id_san_bay_di = $id_san_bay OR id_san_bay_den = $id_san_bay)
                AND trang_thai_tong IN ($escaped)
                AND YEAR(COALESCE(gio_thuc_te, gio_du_kien)) >= $tu_nam AND YEAR(COALESCE(gio_thuc_te, gio_du_kien)) <= $den_nam
                GROUP BY YEAR(COALESCE(gio_thuc_te, gio_du_kien)), MONTH(COALESCE(gio_thuc_te, gio_du_kien))
                ORDER BY nam ASC, thang ASC";
        $result = mysqli_query($this->conn, $sql);
        $data = [];
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $data[] = $row;
            }
        }
        return $data;
    }

    public function ChiTietKhachTheoChuyenBay($id_san_bay, $tu_nam, $den_nam) {
        $id_san_bay = (int)$id_san_bay;
        $tu_nam = (int)$tu_nam;
        $den_nam = (int)$den_nam;
        $escaped = "'" . implode("','", array_map(function($s) { return mysqli_real_escape_string($this->conn, $s); }, self::TRANG_THAI_DA_HA_CANH)) . "'";
        
        $sql = "SELECT 
                    sb_di.ten_san_bay as ten_di, 
                    sb_den.ten_san_bay as ten_den, 
                    cb.so_hieu,
                    SUM(cb.luong_hanh_khach) as tong_khach 
                FROM chuyen_bay_ngay cb 
                JOIN san_bay sb_di ON cb.id_san_bay_di = sb_di.id 
                JOIN san_bay sb_den ON cb.id_san_bay_den = sb_den.id 
                WHERE (cb.id_san_bay_di = $id_san_bay OR cb.id_san_bay_den = $id_san_bay)
                AND cb.trang_thai_tong IN ($escaped)
                AND YEAR(COALESCE(cb.gio_thuc_te, cb.gio_du_kien)) >= $tu_nam 
                AND YEAR(COALESCE(cb.gio_thuc_te, cb.gio_du_kien)) <= $den_nam
                GROUP BY sb_di.id, sb_den.id, cb.so_hieu
                ORDER BY tong_khach DESC";
                
        $result = mysqli_query($this->conn, $sql);
        $data = [];
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $data[] = $row;
            }
        }
        return $data;
    }
}
?>

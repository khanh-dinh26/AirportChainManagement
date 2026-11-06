<?php

class NguoiDung
{
    /** @var mysqli Kết nối CSDL dùng chung */
    private $conn;

    public function __construct($db_conn)
    {
        $this->conn = $db_conn;
    }

    /**
     * Tìm người dùng theo tên đăng nhập.
     *
     * @param string $ten_dang_nhap Tên đăng nhập người dùng nhập vào form
     * @return array|false Mảng dữ liệu user nếu tìm thấy, ngược lại false
     */
    public function kiemTraDangNhap($ten_dang_nhap)
    {
        // Chống SQL Injection bằng mysqli_real_escape_string
        $ten_dang_nhap_safe = mysqli_real_escape_string($this->conn, $ten_dang_nhap);

        $sql = "SELECT nd.*, sb.ma_iata AS san_bay_iata, sb.ten_san_bay, sb.ten_ngan AS san_bay_ten_ngan
                FROM nguoi_dung nd
                LEFT JOIN san_bay sb ON sb.id = nd.id_san_bay
                WHERE nd.ten_dang_nhap = '$ten_dang_nhap_safe'";
        $result = mysqli_query($this->conn, $sql);

        // Nếu câu lệnh SQL lỗi thì coi như không tìm thấy
        if (!$result) {
            return false;
        }

        if (mysqli_num_rows($result) > 0) {
            return mysqli_fetch_assoc($result); // Trả về mảng dữ liệu user
        }

        return false;
    }

    /**
     * Lấy thông tin người dùng theo khóa chính id.
     *
     * @param int $id
     * @return array|false
     */
    public function layTheoId($id)
    {
        $id = (int) $id;

        $sql = "SELECT nd.*, sb.ma_iata AS san_bay_iata, sb.ten_san_bay, sb.ten_ngan AS san_bay_ten_ngan, sb.vi_tri AS san_bay_dia_diem
                FROM nguoi_dung nd
                LEFT JOIN san_bay sb ON sb.id = nd.id_san_bay
                WHERE nd.id = $id LIMIT 1";
        $result = mysqli_query($this->conn, $sql);

        if (!$result || mysqli_num_rows($result) === 0) {
            return false;
        }

        return mysqli_fetch_assoc($result);
    }

    /**
     * Lấy danh sách nhân viên theo bộ lọc (từ khóa, vai trò, sân bay).
     *
     * @param array $filters
     * @return array
     */
    public function layDanhSach(array $filters = []): array
    {
        $where = ["1=1"];

        if (!empty($filters['tu_khoa'])) {
            $tuKhoa = mysqli_real_escape_string($this->conn, trim($filters['tu_khoa']));
            $where[] = "(nd.ho_ten LIKE '%$tuKhoa%' OR nd.ten_dang_nhap LIKE '%$tuKhoa%')";
        }

        if (!empty($filters['vai_tro'])) {
            $vaiTro = mysqli_real_escape_string($this->conn, trim($filters['vai_tro']));
            $where[] = "nd.vai_tro = '$vaiTro'";
        }

        if (isset($filters['id_san_bay']) && $filters['id_san_bay'] !== '' && $filters['id_san_bay'] !== null) {
            $idSb = (int) $filters['id_san_bay'];
            if ($idSb === 0) {
                $where[] = "nd.id_san_bay IS NULL";
            } else {
                $where[] = "nd.id_san_bay = $idSb";
            }
        }

        $whereSql = implode(' AND ', $where);
        $sql = "SELECT nd.id, nd.ten_dang_nhap, nd.ho_ten, nd.vai_tro, nd.id_san_bay,
                       sb.ma_iata AS san_bay_iata, sb.ten_san_bay, sb.ten_ngan AS san_bay_ten_ngan, sb.vi_tri AS san_bay_dia_diem
                FROM nguoi_dung nd
                LEFT JOIN san_bay sb ON sb.id = nd.id_san_bay
                WHERE $whereSql
                ORDER BY nd.id ASC";

        $result = mysqli_query($this->conn, $sql);
        $ds = [];
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $ds[] = $row;
            }
        }

        return $ds;
    }

    /**
     * Thống kê số lượng nhân sự theo từng vai trò.
     *
     * @return array
     */
    public function thongKeNhanSu(): array
    {
        $sql = "SELECT vai_tro, COUNT(*) AS so_luong FROM nguoi_dung GROUP BY vai_tro";
        $result = mysqli_query($this->conn, $sql);
        $stats = [
            'tong' => 0,
            'Admin_Tong_Cong_Ty' => 0,
            'Quan_Ly_San_Bay' => 0,
            'Nhan_Vien_Dieu_Hanh' => 0,
            'Ke_Toan' => 0,
        ];

        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $vt = $row['vai_tro'];
                $sl = (int) $row['so_luong'];
                if (isset($stats[$vt])) {
                    $stats[$vt] = $sl;
                }
                $stats['tong'] += $sl;
            }
        }

        return $stats;
    }

    /**
     * Kiểm tra tên đăng nhập đã được sử dụng hay chưa.
     *
     * @param string $tenDangNhap
     * @param int $excludeId
     * @return bool
     */
    public function kiemTraTenDangNhapTonTai(string $tenDangNhap, int $excludeId = 0): bool
    {
        $safe = mysqli_real_escape_string($this->conn, trim($tenDangNhap));
        $sql = "SELECT id FROM nguoi_dung WHERE ten_dang_nhap = '$safe'";
        if ($excludeId > 0) {
            $sql .= " AND id != " . (int) $excludeId;
        }
        $sql .= " LIMIT 1";

        $result = mysqli_query($this->conn, $sql);
        return $result && mysqli_num_rows($result) > 0;
    }

    /**
     * Thêm tài khoản nhân viên mới.
     *
     * @param string $tenDangNhap
     * @param string $matKhau
     * @param string $hoTen
     * @param string $vaiTro
     * @param int|null $idSanBay
     * @return bool
     */
    public function themNguoiDung(string $tenDangNhap, string $matKhau, string $hoTen, string $vaiTro, ?int $idSanBay = null): bool
    {
        $tenDangNhapSafe = mysqli_real_escape_string($this->conn, trim($tenDangNhap));
        $hoTenSafe = mysqli_real_escape_string($this->conn, trim($hoTen));
        $vaiTroSafe = mysqli_real_escape_string($this->conn, trim($vaiTro));
        $hash = password_hash($matKhau, PASSWORD_DEFAULT);
        $idSanBaySql = ($idSanBay !== null && (int) $idSanBay > 0) ? (int) $idSanBay : "NULL";

        $sql = "INSERT INTO nguoi_dung (ten_dang_nhap, mat_khau_hash, ho_ten, vai_tro, id_san_bay)
                VALUES ('$tenDangNhapSafe', '$hash', '$hoTenSafe', '$vaiTroSafe', $idSanBaySql)";

        return (bool) mysqli_query($this->conn, $sql);
    }

    /**
     * Cập nhật thông tin cơ bản của nhân viên (Họ tên, Vai trò, Sân bay).
     *
     * @param int $id
     * @param string $hoTen
     * @param string $vaiTro
     * @param int|null $idSanBay
     * @return bool
     */
    public function capNhatThongTin(int $id, string $hoTen, string $vaiTro, ?int $idSanBay = null): bool
    {
        $id = (int) $id;
        $hoTenSafe = mysqli_real_escape_string($this->conn, trim($hoTen));
        $vaiTroSafe = mysqli_real_escape_string($this->conn, trim($vaiTro));
        $idSanBaySql = ($idSanBay !== null && (int) $idSanBay > 0) ? (int) $idSanBay : "NULL";

        $sql = "UPDATE nguoi_dung 
                SET ho_ten = '$hoTenSafe', vai_tro = '$vaiTroSafe', id_san_bay = $idSanBaySql
                WHERE id = $id";

        return (bool) mysqli_query($this->conn, $sql);
    }

    /**
     * Đổi mật khẩu của nhân viên (Admin tổng hoặc cá nhân).
     *
     * @param int $id
     * @param string $matKhauMoi
     * @return bool
     */
    public function doiMatKhau(int $id, string $matKhauMoi): bool
    {
        $id = (int) $id;
        $hash = password_hash($matKhauMoi, PASSWORD_DEFAULT);

        $sql = "UPDATE nguoi_dung SET mat_khau_hash = '$hash' WHERE id = $id";

        return (bool) mysqli_query($this->conn, $sql);
    }

    /**
     * Xóa tài khoản nhân viên.
     *
     * @param int $id
     * @param int $currentUserId ID người đang đăng nhập (không được tự xóa chính mình)
     * @return bool
     */
    public function xoaNguoiDung(int $id, int $currentUserId): bool
    {
        $id = (int) $id;
        $currentUserId = (int) $currentUserId;

        if ($id === $currentUserId || $id <= 0) {
            return false;
        }

        $sql = "DELETE FROM nguoi_dung WHERE id = $id";

        return (bool) mysqli_query($this->conn, $sql);
    }

    /**
     * Lấy danh sách sân bay để chọn nơi công tác.
     *
     * @return array
     */
    public function layDanhSachSanBay(): array
    {
        $sql = "SELECT id, ma_iata, ten_san_bay, ten_ngan FROM san_bay ORDER BY ma_iata ASC";
        $result = mysqli_query($this->conn, $sql);
        $ds = [];
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $ds[] = $row;
            }
        }

        return $ds;
    }
}

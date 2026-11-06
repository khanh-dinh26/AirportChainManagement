<?php
/**
 * ============================================================
 * CONTROLLER: QUẢN LÝ NHÂN SỰ  —  controllers/NhanSuController.php
 * Phân hệ: Xác thực & Nhân sự (Thành viên 1)
 * ------------------------------------------------------------
 * Cho phép Admin Tổng công ty quản lý danh sách tài khoản
 * nhân viên, xem thông tin chi tiết, thay đổi mật khẩu trực tiếp,
 * phân quyền vai trò và phân bổ sân bay công tác.
 * ============================================================
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../models/NguoiDung.php';

class NhanSuController
{
    private $conn;
    private $nguoiDungModel;

    public const VAI_TRO = [
        'Admin_Tong_Cong_Ty'  => 'Quản trị tổng công ty',
        'Quan_Ly_San_Bay'     => 'Quản lý sân bay',
        'Nhan_Vien_Dieu_Hanh' => 'Nhân viên điều hành',
        'Ke_Toan'             => 'Kế toán',
    ];

    public function __construct($db_conn)
    {
        $this->conn = $db_conn;
        $this->nguoiDungModel = new NguoiDung($db_conn);
    }

    /**
     * Điểm vào xử lý các request POST & GET của trang Quản lý nhân sự.
     *
     * @return array Dữ liệu cung cấp cho View
     */
    public function handle(): array
    {
        yeu_cau_dang_nhap();

        $currentUserId = (int) ($_SESSION['user_id'] ?? 0);
        $isAdminTong = la_admin();
        $phamViSanBay = pham_vi_san_bay();

        // Xử lý các thao tác POST
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = trim((string) ($_POST['action'] ?? ''));

            if ($action === 'doi_mat_khau') {
                $this->handleDoiMatKhau($currentUserId, $isAdminTong);
            } elseif ($action === 'them_nhan_vien') {
                $this->handleThemNhanVien($isAdminTong);
            } elseif ($action === 'cap_nhat_thong_tin') {
                $this->handleCapNhatThongTin($currentUserId, $isAdminTong);
            } elseif ($action === 'xoa_nhan_vien') {
                $this->handleXoaNhanVien($currentUserId, $isAdminTong);
            }
        }

        // Lấy bộ lọc từ GET
        $filters = [
            'tu_khoa'   => trim((string) ($_GET['q'] ?? '')),
            'vai_tro'   => trim((string) ($_GET['role'] ?? '')),
            'id_san_bay'=> isset($_GET['airport']) && $_GET['airport'] !== '' ? (int) $_GET['airport'] : null,
        ];

        // Nếu người dùng là Quản lý sân bay (không phải Admin Tổng), tự động lọc theo sân bay phụ trách
        if (!$isAdminTong && $phamViSanBay > 0) {
            $filters['id_san_bay'] = $phamViSanBay;
        }

        $danhSach = $this->nguoiDungModel->layDanhSach($filters);
        $thongKe = $this->nguoiDungModel->thongKeNhanSu();
        $danhSachSanBay = $this->nguoiDungModel->layDanhSachSanBay();

        // Đọc flash message
        $flash = null;
        if (!empty($_SESSION['nhan_su_flash'])) {
            $flash = $_SESSION['nhan_su_flash'];
            unset($_SESSION['nhan_su_flash']);
        }

        return [
            'danhSach'        => $danhSach,
            'thongKe'         => $thongKe,
            'danhSachSanBay'  => $danhSachSanBay,
            'filters'         => $filters,
            'isAdminTong'     => $isAdminTong,
            'currentUserId'   => $currentUserId,
            'vaiTroMap'       => self::VAI_TRO,
            'flash'           => $flash,
        ];
    }

    /**
     * Xử lý Admin Tổng hoặc cá nhân đổi mật khẩu nhân viên.
     */
    private function handleDoiMatKhau(int $currentUserId, bool $isAdminTong): void
    {
        $id = (int) ($_POST['id_nguoi_dung'] ?? 0);
        $matKhauMoi = (string) ($_POST['mat_khau_moi'] ?? '');
        $xacNhanMatKhau = (string) ($_POST['xac_nhan_mat_khau'] ?? '');

        // Kiểm tra quyền: Chỉ Admin Tổng hoặc chính chủ mới được đổi
        if (!$isAdminTong && $id !== $currentUserId) {
            $this->setFlash('danger', 'Bạn không có quyền thay đổi mật khẩu của nhân viên khác!');
            $this->redirectBack();
        }

        $targetUser = $this->nguoiDungModel->layTheoId($id);
        if (!$targetUser) {
            $this->setFlash('danger', 'Không tìm thấy tài khoản nhân viên cần đổi mật khẩu.');
            $this->redirectBack();
        }

        if (mb_strlen($matKhauMoi) < 6) {
            $this->setFlash('danger', 'Mật khẩu mới phải có độ dài tối thiểu từ 6 ký tự trở lên.');
            $this->redirectBack();
        }

        if ($matKhauMoi !== $xacNhanMatKhau) {
            $this->setFlash('danger', 'Mật khẩu xác nhận không khớp với mật khẩu mới.');
            $this->redirectBack();
        }

        if ($this->nguoiDungModel->doiMatKhau($id, $matKhauMoi)) {
            $tenNhanVien = htmlspecialchars($targetUser['ho_ten'], ENT_QUOTES, 'UTF-8');
            $this->setFlash('success', "Đã thay đổi mật khẩu thành công cho nhân viên: <strong>{$tenNhanVien}</strong> (tài khoản: <code>{$targetUser['ten_dang_nhap']}</code>).");
        } else {
            $this->setFlash('danger', 'Có lỗi xảy ra trong quá trình cập nhật mật khẩu.');
        }

        $this->redirectBack();
    }

    /**
     * Xử lý Admin Tổng thêm tài khoản nhân viên mới.
     */
    private function handleThemNhanVien(bool $isAdminTong): void
    {
        if (!$isAdminTong) {
            $this->setFlash('danger', 'Chỉ Quản trị viên Tổng công ty mới có quyền tạo tài khoản nhân viên mới!');
            $this->redirectBack();
        }

        $tenDangNhap = trim((string) ($_POST['ten_dang_nhap'] ?? ''));
        $matKhau = (string) ($_POST['mat_khau'] ?? '');
        $hoTen = trim((string) ($_POST['ho_ten'] ?? ''));
        $vaiTro = trim((string) ($_POST['vai_tro'] ?? ''));
        $idSanBay = isset($_POST['id_san_bay']) && $_POST['id_san_bay'] !== '' ? (int) $_POST['id_san_bay'] : null;

        if ($tenDangNhap === '' || $matKhau === '' || $hoTen === '' || $vaiTro === '') {
            $this->setFlash('danger', 'Vui lòng điền đầy đủ các thông tin bắt buộc.');
            $this->redirectBack();
        }

        if (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $tenDangNhap)) {
            $this->setFlash('danger', 'Tên đăng nhập chỉ gồm 3-30 ký tự chữ cái, chữ số hoặc dấu gạch dưới (_).');
            $this->redirectBack();
        }

        if (mb_strlen($matKhau) < 6) {
            $this->setFlash('danger', 'Mật khẩu khởi tạo phải từ 6 ký tự trở lên.');
            $this->redirectBack();
        }

        if (!isset(self::VAI_TRO[$vaiTro])) {
            $this->setFlash('danger', 'Vai trò được chọn không hợp lệ.');
            $this->redirectBack();
        }

        if ($this->nguoiDungModel->kiemTraTenDangNhapTonTai($tenDangNhap)) {
            $this->setFlash('danger', "Tên đăng nhập <code>{$tenDangNhap}</code> đã tồn tại. Vui lòng chọn tên đăng nhập khác.");
            $this->redirectBack();
        }

        // Nếu là Admin tổng công ty thì không gắn với sân bay cụ thể
        if ($vaiTro === 'Admin_Tong_Cong_Ty') {
            $idSanBay = null;
        }

        if ($this->nguoiDungModel->themNguoiDung($tenDangNhap, $matKhau, $hoTen, $vaiTro, $idSanBay)) {
            $tenSafe = htmlspecialchars($hoTen, ENT_QUOTES, 'UTF-8');
            $this->setFlash('success', "Thêm tài khoản nhân viên <strong>{$tenSafe}</strong> thành công!");
        } else {
            $this->setFlash('danger', 'Không thể thêm tài khoản do lỗi máy chủ cơ sở dữ liệu.');
        }

        $this->redirectBack();
    }

    /**
     * Xử lý Admin Tổng cập nhật thông tin nhân viên (họ tên, vai trò, sân bay).
     */
    private function handleCapNhatThongTin(int $currentUserId, bool $isAdminTong): void
    {
        $id = (int) ($_POST['id_nguoi_dung'] ?? 0);
        $hoTen = trim((string) ($_POST['ho_ten'] ?? ''));
        $vaiTro = trim((string) ($_POST['vai_tro'] ?? ''));
        $idSanBay = isset($_POST['id_san_bay']) && $_POST['id_san_bay'] !== '' ? (int) $_POST['id_san_bay'] : null;

        if (!$isAdminTong && $id !== $currentUserId) {
            $this->setFlash('danger', 'Bạn không có quyền chỉnh sửa thông tin của nhân viên khác!');
            $this->redirectBack();
        }

        $targetUser = $this->nguoiDungModel->layTheoId($id);
        if (!$targetUser) {
            $this->setFlash('danger', 'Không tìm thấy tài khoản nhân viên cần sửa.');
            $this->redirectBack();
        }

        if ($hoTen === '') {
            $this->setFlash('danger', 'Họ và tên không được để trống.');
            $this->redirectBack();
        }

        // Nếu không phải admin tổng thì giữ nguyên vai trò và sân bay cũ
        if (!$isAdminTong) {
            $vaiTro = $targetUser['vai_tro'];
            $idSanBay = $targetUser['id_san_bay'];
        } else {
            if (!isset(self::VAI_TRO[$vaiTro])) {
                $vaiTro = $targetUser['vai_tro'];
            }
            if ($vaiTro === 'Admin_Tong_Cong_Ty') {
                $idSanBay = null;
            }
        }

        if ($this->nguoiDungModel->capNhatThongTin($id, $hoTen, $vaiTro, $idSanBay)) {
            $tenSafe = htmlspecialchars($hoTen, ENT_QUOTES, 'UTF-8');
            $this->setFlash('success', "Cập nhật thông tin nhân viên <strong>{$tenSafe}</strong> thành công!");
        } else {
            $this->setFlash('danger', 'Có lỗi xảy ra khi cập nhật thông tin.');
        }

        $this->redirectBack();
    }

    /**
     * Xử lý Admin Tổng xóa tài khoản nhân viên.
     */
    private function handleXoaNhanVien(int $currentUserId, bool $isAdminTong): void
    {
        if (!$isAdminTong) {
            $this->setFlash('danger', 'Chỉ Quản trị viên Tổng công ty mới có quyền xóa tài khoản nhân viên!');
            $this->redirectBack();
        }

        $id = (int) ($_POST['id_nguoi_dung'] ?? 0);

        if ($id === $currentUserId) {
            $this->setFlash('danger', 'Bạn không thể tự xóa tài khoản của chính mình đang đăng nhập!');
            $this->redirectBack();
        }

        $targetUser = $this->nguoiDungModel->layTheoId($id);
        if (!$targetUser) {
            $this->setFlash('danger', 'Tài khoản nhân viên không tồn tại.');
            $this->redirectBack();
        }

        if ($this->nguoiDungModel->xoaNguoiDung($id, $currentUserId)) {
            $tenSafe = htmlspecialchars($targetUser['ho_ten'], ENT_QUOTES, 'UTF-8');
            $this->setFlash('success', "Đã xóa tài khoản nhân viên <strong>{$tenSafe}</strong> thành công.");
        } else {
            $this->setFlash('danger', 'Không thể xóa tài khoản này.');
        }

        $this->redirectBack();
    }

    private function setFlash(string $type, string $message): void
    {
        $_SESSION['nhan_su_flash'] = [
            'type'    => $type,
            'message' => $message,
        ];
    }

    private function redirectBack(): void
    {
        $redirectUrl = BASE_URL . '/index.php?page=nhan_su';
        $params = [];
        if (!empty($_GET['q'])) $params['q'] = $_GET['q'];
        if (!empty($_GET['role'])) $params['role'] = $_GET['role'];
        if (isset($_GET['airport']) && $_GET['airport'] !== '') $params['airport'] = $_GET['airport'];

        if (!empty($params)) {
            $redirectUrl .= '&' . http_build_query($params);
        }

        header('Location: ' . $redirectUrl);
        exit();
    }
}

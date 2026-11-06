<?php
/**
 * ============================================================
 * CONTROLLER: QUẢN LÝ NHÂN SỰ  —  controllers/NhanSuController.php
 * Phân hệ: Xác thực & Nhân sự (Thành viên 1)
 * ------------------------------------------------------------
 * Cho phép Admin Tổng công ty quản lý danh sách tài khoản
 * nhân viên, xem thông tin chi tiết, đặt & crop ảnh đại diện,
 * thay đổi mật khẩu trực tiếp, phân quyền vai trò và
 * phân bổ sân bay công tác.
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
        'Nhan_Vien_Bao_Tri'   => 'Nhân viên bảo trì',
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

        if (!co_quyen_nhan_su()) {
            $_SESSION['flash_danger'] = 'Bạn không có quyền truy cập vào phân hệ Quản lý nhân sự.';
            header('Location: ' . BASE_URL . '/index.php');
            exit();
        }

        $currentUserId = (int) ($_SESSION['user_id'] ?? 0);
        $isAdminTong = la_admin();
        $isQuanLySanBay = co_vai_tro('Quan_Ly_San_Bay');
        $phamViSanBay = pham_vi_san_bay();
        $coQuyenThem = $isAdminTong || ($isQuanLySanBay && $phamViSanBay > 0);

        // Xử lý các thao tác POST
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = trim((string) ($_POST['action'] ?? ''));

            if ($action === 'doi_mat_khau') {
                $this->handleDoiMatKhau($currentUserId, $isAdminTong, $isQuanLySanBay, $phamViSanBay);
            } elseif ($action === 'them_nhan_vien') {
                $this->handleThemNhanVien($isAdminTong, $isQuanLySanBay, $phamViSanBay);
            } elseif ($action === 'cap_nhat_thong_tin') {
                $this->handleCapNhatThongTin($currentUserId, $isAdminTong, $isQuanLySanBay, $phamViSanBay);
            } elseif ($action === 'xoa_nhan_vien') {
                $this->handleXoaNhanVien($currentUserId, $isAdminTong, $isQuanLySanBay, $phamViSanBay);
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

        $tenSanBayPhuTrach = '';
        if ($phamViSanBay > 0) {
            foreach ($danhSachSanBay as $sb) {
                if ((int) $sb['id'] === $phamViSanBay) {
                    $tenSanBayPhuTrach = $sb['ma_iata'] . ' – ' . $sb['ten_san_bay'];
                    break;
                }
            }
        }

        // Đọc flash message
        $flash = null;
        if (!empty($_SESSION['nhan_su_flash'])) {
            $flash = $_SESSION['nhan_su_flash'];
            unset($_SESSION['nhan_su_flash']);
        }

        return [
            'danhSach'          => $danhSach,
            'thongKe'           => $thongKe,
            'danhSachSanBay'    => $danhSachSanBay,
            'filters'           => $filters,
            'isAdminTong'       => $isAdminTong,
            'isQuanLySanBay'    => $isQuanLySanBay,
            'coQuyenThem'       => $coQuyenThem,
            'phamViSanBay'      => $phamViSanBay,
            'tenSanBayPhuTrach' => $tenSanBayPhuTrach,
            'currentUserId'     => $currentUserId,
            'vaiTroMap'         => self::VAI_TRO,
            'flash'             => $flash,
        ];
    }

    /**
     * Xử lý Admin Tổng, Quản lý sân bay hoặc cá nhân đổi mật khẩu nhân viên.
     */
    private function handleDoiMatKhau(int $currentUserId, bool $isAdminTong, bool $isQuanLySanBay, int $phamViSanBay): void
    {
        $id = (int) ($_POST['id_nguoi_dung'] ?? 0);
        $matKhauMoi = (string) ($_POST['mat_khau_moi'] ?? '');
        $xacNhanMatKhau = (string) ($_POST['xac_nhan_mat_khau'] ?? '');

        $targetUser = $this->nguoiDungModel->layTheoId($id);
        if (!$targetUser) {
            $this->setFlash('danger', 'Không tìm thấy tài khoản nhân viên cần đổi mật khẩu.');
            $this->redirectBack();
        }

        $canManage = $isAdminTong
            || ($id === $currentUserId)
            || ($isQuanLySanBay && $phamViSanBay > 0 && (int) ($targetUser['id_san_bay'] ?? 0) === $phamViSanBay && $targetUser['vai_tro'] !== 'Admin_Tong_Cong_Ty');

        if (!$canManage) {
            $this->setFlash('danger', 'Bạn không có quyền thay đổi mật khẩu của nhân viên này!');
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
     * Xử lý thêm tài khoản nhân viên mới (Admin Tổng hoặc Quản lý sân bay).
     */
    private function handleThemNhanVien(bool $isAdminTong, bool $isQuanLySanBay, int $phamViSanBay): void
    {
        if (!$isAdminTong && !($isQuanLySanBay && $phamViSanBay > 0)) {
            $this->setFlash('danger', 'Bạn không có quyền tạo tài khoản nhân viên mới!');
            $this->redirectBack();
        }

        $tenDangNhap = trim((string) ($_POST['ten_dang_nhap'] ?? ''));
        $matKhau = (string) ($_POST['mat_khau'] ?? '');
        $hoTen = trim((string) ($_POST['ho_ten'] ?? ''));
        $vaiTro = trim((string) ($_POST['vai_tro'] ?? ''));

        if ($isAdminTong) {
            $idSanBay = isset($_POST['id_san_bay']) && $_POST['id_san_bay'] !== '' ? (int) $_POST['id_san_bay'] : null;
            if ($vaiTro === 'Admin_Tong_Cong_Ty') {
                $idSanBay = null;
            }
        } else {
            // Quản lý sân bay: Tự động gán thuộc sân bay mình quản lý
            $idSanBay = $phamViSanBay;
            if ($vaiTro === 'Admin_Tong_Cong_Ty') {
                $this->setFlash('danger', 'Quản lý sân bay không được phép tạo tài khoản Admin Tổng công ty!');
                $this->redirectBack();
            }
        }

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

        // Bỏ chỉnh ảnh trong quản lý nhân sự - nhân sự tạo mới mặc định dùng avatar chữ cái, cá nhân tự đặt trong Hồ sơ
        $anhDaiDien = null;

        if ($this->nguoiDungModel->themNguoiDung($tenDangNhap, $matKhau, $hoTen, $vaiTro, $idSanBay, $anhDaiDien)) {
            $tenSafe = htmlspecialchars($hoTen, ENT_QUOTES, 'UTF-8');
            $this->setFlash('success', "Thêm tài khoản nhân viên <strong>{$tenSafe}</strong> thành công!");
        } else {
            $this->setFlash('danger', 'Không thể thêm tài khoản do lỗi máy chủ cơ sở dữ liệu.');
        }

        $this->redirectBack();
    }

    /**
     * Xử lý cập nhật thông tin nhân viên (họ tên, vai trò, sân bay).
     */
    private function handleCapNhatThongTin(int $currentUserId, bool $isAdminTong, bool $isQuanLySanBay, int $phamViSanBay): void
    {
        $id = (int) ($_POST['id_nguoi_dung'] ?? 0);
        $hoTen = trim((string) ($_POST['ho_ten'] ?? ''));
        $vaiTro = trim((string) ($_POST['vai_tro'] ?? ''));
        $idSanBay = isset($_POST['id_san_bay']) && $_POST['id_san_bay'] !== '' ? (int) $_POST['id_san_bay'] : null;

        $targetUser = $this->nguoiDungModel->layTheoId($id);
        if (!$targetUser) {
            $this->setFlash('danger', 'Không tìm thấy tài khoản nhân viên cần sửa.');
            $this->redirectBack();
        }

        $canManage = $isAdminTong
            || ($id === $currentUserId)
            || ($isQuanLySanBay && $phamViSanBay > 0 && (int) ($targetUser['id_san_bay'] ?? 0) === $phamViSanBay && $targetUser['vai_tro'] !== 'Admin_Tong_Cong_Ty');

        if (!$canManage) {
            $this->setFlash('danger', 'Bạn không có quyền chỉnh sửa thông tin của nhân viên này!');
            $this->redirectBack();
        }

        if ($hoTen === '') {
            $this->setFlash('danger', 'Họ và tên không được để trống.');
            $this->redirectBack();
        }

        if ($isAdminTong) {
            if (!isset(self::VAI_TRO[$vaiTro])) {
                $vaiTro = $targetUser['vai_tro'];
            }
            if ($vaiTro === 'Admin_Tong_Cong_Ty') {
                $idSanBay = null;
            }
        } elseif ($isQuanLySanBay && $id !== $currentUserId) {
            $idSanBay = $phamViSanBay;
            if (!isset(self::VAI_TRO[$vaiTro]) || $vaiTro === 'Admin_Tong_Cong_Ty') {
                $vaiTro = $targetUser['vai_tro'];
            }
        } else {
            $vaiTro = $targetUser['vai_tro'];
            $idSanBay = $targetUser['id_san_bay'];
        }

        // Bỏ chỉnh ảnh trong quản lý nhân sự - giữ nguyên ảnh đại diện hiện tại
        $anhDaiDien = null;

        if ($this->nguoiDungModel->capNhatThongTin($id, $hoTen, $vaiTro, $idSanBay, $anhDaiDien)) {
            if ($id === $currentUserId) {
                $_SESSION['ho_ten'] = $hoTen;
            }

            $tenSafe = htmlspecialchars($hoTen, ENT_QUOTES, 'UTF-8');
            $this->setFlash('success', "Cập nhật thông tin nhân viên <strong>{$tenSafe}</strong> thành công!");
        } else {
            $this->setFlash('danger', 'Có lỗi xảy ra khi cập nhật thông tin.');
        }

        $this->redirectBack();
    }

    /**
     * Xóa tệp ảnh vật lý trên đĩa máy chủ nếu xóa tài khoản.
     */
    private function xoaFileAnhVatLy(string $rawAvatar): void
    {
        if (trim($rawAvatar) === '') return;
        $relPath = explode('|', $rawAvatar)[0];
        $absPath = __DIR__ . '/../' . $relPath;
        if (file_exists($absPath) && is_file($absPath)) {
            @unlink($absPath);
        }
    }

    /**
     * Xử lý Admin Tổng hoặc Quản lý sân bay xóa tài khoản nhân viên.
     */
    private function handleXoaNhanVien(int $currentUserId, bool $isAdminTong, bool $isQuanLySanBay, int $phamViSanBay): void
    {
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

        $canDelete = $isAdminTong
            || ($isQuanLySanBay && $phamViSanBay > 0 && (int) ($targetUser['id_san_bay'] ?? 0) === $phamViSanBay && $targetUser['vai_tro'] !== 'Admin_Tong_Cong_Ty');

        if (!$canDelete) {
            $this->setFlash('danger', 'Bạn không có quyền xóa tài khoản nhân viên này!');
            $this->redirectBack();
        }

        $this->xoaFileAnhVatLy($targetUser['anh_dai_dien'] ?? '');

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

<?php


if (!function_exists('da_dang_nhap')) {
    function da_dang_nhap(): bool {
        return !empty($_SESSION['user_id']);
    }
}

if (!function_exists('vai_tro_hien_tai')) {
    function vai_tro_hien_tai(): string {
        return (string) ($_SESSION['vai_tro'] ?? '');
    }
}

if (!function_exists('la_admin')) {
    function la_admin(): bool {
        return vai_tro_hien_tai() === 'Admin_Tong_Cong_Ty';
    }
}

if (!function_exists('la_quan_ly')) {
    function la_quan_ly(): bool {
        return vai_tro_hien_tai() === 'Quan_Ly_San_Bay';
    }
}

if (!function_exists('la_dieu_hanh')) {
    function la_dieu_hanh(): bool {
        return vai_tro_hien_tai() === 'Nhan_Vien_Dieu_Hanh';
    }
}

if (!function_exists('la_bao_tri')) {
    function la_bao_tri(): bool {
        return vai_tro_hien_tai() === 'Nhan_Vien_Bao_Tri';
    }
}

if (!function_exists('la_ke_toan')) {
    function la_ke_toan(): bool {
        return vai_tro_hien_tai() === 'Ke_Toan';
    }
}

/**
 * Kiểm tra xem người dùng hiện tại có vai trò nằm trong danh sách không
 */
if (!function_exists('co_vai_tro')) {
    function co_vai_tro($vaiTro): bool {
        if (!da_dang_nhap()) {
            return false;
        }
        $hienTai = vai_tro_hien_tai();
        if (is_array($vaiTro)) {
            return in_array($hienTai, $vaiTro, true);
        }
        return $hienTai === (string) $vaiTro;
    }
}


if (!function_exists('pham_vi_san_bay')) {
    function pham_vi_san_bay(): int {
        if (!da_dang_nhap()) {
            return -1;
        }

        // Admin Tổng công ty: toàn quyền toàn quốc
        if (la_admin()) {
            return 0;
        }

        // Các vai trò khác: phụ thuộc vào id_san_bay trong session
        $idSanBay = $_SESSION['id_san_bay'] ?? null;
        if ($idSanBay !== null && (int) $idSanBay > 0) {
            return (int) $idSanBay;
        }

        // Vai trò giới hạn nhưng id_san_bay = NULL -> chặn hoàn toàn để tránh lọt quyền toàn quốc
        return -1;
    }
}

/**
 * Kiểm tra 1 bản ghi có thuộc phạm vi quản lý của người dùng không
 */
if (!function_exists('trong_pham_vi_san_bay')) {
    function trong_pham_vi_san_bay($idSanBay): bool {
        $phamVi = pham_vi_san_bay();
        if ($phamVi === 0) {
            return true;
        }
        if ($phamVi < 0) {
            return false;
        }
        return (int) $idSanBay === $phamVi;
    }
}

/**
 * Middleware: Chặn truy cập nếu chưa đăng nhập
 */
if (!function_exists('yeu_cau_dang_nhap')) {
    function yeu_cau_dang_nhap(): void {
        if (!da_dang_nhap()) {
            $loginUrl = (defined('BASE_URL') ? BASE_URL : '') . '/index.php?page=login';
            header('Location: ' . $loginUrl);
            exit();
        }
    }
}

/**
 * Middleware: Yêu cầu vai trò cụ thể, nếu không đủ quyền thì chặn và báo lỗi
 */
if (!function_exists('yeu_cau_vai_tro')) {
    function yeu_cau_vai_tro($vaiTroChoPhep, string $urlRedirect = ''): void {
        yeu_cau_dang_nhap();

        if (!co_vai_tro($vaiTroChoPhep)) {
            $dest = $urlRedirect !== '' ? $urlRedirect : ((defined('BASE_URL') ? BASE_URL : '') . '/index.php');
            $_SESSION['flash_danger'] = 'Bạn không có quyền truy cập vào chức năng hoặc phân hệ này.';
            header('Location: ' . $dest);
            exit();
        }
    }
}



/** Quyền vào Quản lý nhân sự: Chỉ Admin Tổng công ty hoặc Quản lý sân bay (xem nhân sự sân bay mình) */
if (!function_exists('co_quyen_nhan_su')) {
    function co_quyen_nhan_su(): bool {
        return co_vai_tro(['Admin_Tong_Cong_Ty', 'Quan_Ly_San_Bay']);
    }
}

/** Quyền vào Quản lý hạ tầng: Xem được toàn bộ, ghi thì phụ thuộc vai trò */
if (!function_exists('co_quyen_ha_tang')) {
    function co_quyen_ha_tang(): bool {
        return da_dang_nhap();
    }
}

/** Quyền thêm/sửa/xóa hạ tầng: Chỉ Admin Tổng công ty hoặc Quản lý sân bay */
if (!function_exists('co_quyen_ghi_ha_tang')) {
    function co_quyen_ghi_ha_tang(): bool {
        return co_vai_tro(['Admin_Tong_Cong_Ty', 'Quan_Ly_San_Bay']);
    }
}

/** Quyền vào Chuyến bay: Admin, Quản lý, Điều hành, Kỹ thuật (xem), Kế toán (xem) */
if (!function_exists('co_quyen_chuyen_bay')) {
    function co_quyen_chuyen_bay(): bool {
        return da_dang_nhap();
    }
}

/** Quyền điều phối chuyến bay (gán cửa, quầy, băng chuyền, đổi trạng thái): Admin, Quản lý, Điều hành */
if (!function_exists('co_quyen_dieu_phoi_bay')) {
    function co_quyen_dieu_phoi_bay(): bool {
        return co_vai_tro(['Admin_Tong_Cong_Ty', 'Quan_Ly_San_Bay', 'Nhan_Vien_Dieu_Hanh']);
    }
}

/** Quyền vào Kỹ thuật & Bảo trì: Admin, Quản lý, Nhân viên bảo trì, Nhân viên điều hành (báo sự cố) */
if (!function_exists('co_quyen_bao_tri')) {
    function co_quyen_bao_tri(): bool {
        return co_vai_tro(['Admin_Tong_Cong_Ty', 'Quan_Ly_San_Bay', 'Nhan_Vien_Bao_Tri', 'Nhan_Vien_Dieu_Hanh']);
    }
}

/** Quyền kỹ thuật viên chuyên trách (xử lý phiếu, cập nhật thiết bị): Admin, Quản lý, Nhân viên bảo trì */
if (!function_exists('co_quyen_ky_thuat_vien')) {
    function co_quyen_ky_thuat_vien(): bool {
        return co_vai_tro(['Admin_Tong_Cong_Ty', 'Quan_Ly_San_Bay', 'Nhan_Vien_Bao_Tri']);
    }
}

/** Quyền vào Kế toán & Doanh thu: Admin, Quản lý sân bay (xem), Kế toán (toàn quyền) */
if (!function_exists('co_quyen_ke_toan')) {
    function co_quyen_ke_toan(): bool {
        return co_vai_tro(['Admin_Tong_Cong_Ty', 'Quan_Ly_San_Bay', 'Ke_Toan']);
    }
}

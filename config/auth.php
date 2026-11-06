<?php
/**
 * ============================================================
 * HELPER PHÂN QUYỀN & XÁC THỰC THEO SÂN BAY
 * File: config/auth.php
 * ============================================================
 */

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

/**
 * Trả về ID sân bay mà người dùng hiện tại bị giới hạn:
 * - 0 : Toàn quyền (Admin Tổng công ty)
 * - >0: Giới hạn trong sân bay có ID này (Quản lý sân bay, Điều hành, Kế toán...)
 * - -1: Bị chặn hoàn toàn (có vai trò giới hạn nhưng id_san_bay bị NULL/0 hoặc chưa login)
 */
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

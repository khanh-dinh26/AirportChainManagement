<?php

// Khởi tạo session (config.php đã mở session, đây là lớp bảo hiểm)
require_once __DIR__ . '/../config/config.php';

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/NguoiDung.php';

/** Trang đăng nhập để quay về khi có lỗi */
$trangDangNhap = BASE_URL . '/index.php?page=login';

/** Trang chủ sau khi đăng nhập thành công */
$trangChu = BASE_URL . '/index.php';

$nguoiDungModel = new NguoiDung($conn);

/* ------------------------------------------------------------
 * 1. XỬ LÝ ĐĂNG NHẬP
 * ------------------------------------------------------------ */
if (isset($_POST['btn_login'])) {
    $username = isset($_POST['username']) ? trim((string) $_POST['username']) : '';
    $password = isset($_POST['password']) ? (string) $_POST['password'] : '';

    // Bỏ trống thì báo lỗi luôn, không cần truy vấn CSDL
    if ($username === '' || $password === '') {
        $_SESSION['error'] = 'Vui lòng nhập đầy đủ tên đăng nhập và mật khẩu!';
        header('Location: ' . $trangDangNhap);
        exit();
    }

    // Gọi Model lấy thông tin user
    $user = $nguoiDungModel->kiemTraDangNhap($username);

    if ($user) {
        // Xác thực mật khẩu Hash
        if (password_verify($password, $user['mat_khau_hash'])) {
            // Lưu thông tin vào Session
            $_SESSION['user_id']    = $user['id'];
            $_SESSION['ho_ten']     = $user['ho_ten'];
            $_SESSION['vai_tro']          = $user['vai_tro'];
            $_SESSION['id_san_bay']       = $user['id_san_bay'];
            $_SESSION['san_bay_iata']     = $user['san_bay_iata'] ?? null;
            $_SESSION['san_bay_ten_ngan'] = $user['san_bay_ten_ngan'] ?? null;

            // Chống tấn công cố định session (session fixation)
            session_regenerate_id(true);

            // Chuyển hướng về trang chủ
            header('Location: ' . $trangChu);
            exit();
        }

        $_SESSION['error'] = 'Sai mật khẩu!';
        header('Location: ' . $trangDangNhap);
        exit();
    }

    $_SESSION['error'] = 'Tên đăng nhập không tồn tại!';
    header('Location: ' . $trangDangNhap);
    exit();
}

/* ------------------------------------------------------------
 * 2. XỬ LÝ ĐĂNG XUẤT
 * ------------------------------------------------------------ */
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_unset();
    session_destroy();

    // Xóa cookie session trên trình duyệt
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }

    header('Location: ' . $trangDangNhap . '&logged_out=1');
    exit();
}

/* ------------------------------------------------------------
 * 3. TRUY CẬP TRỰC TIẾP MÀ KHÔNG GỬI FORM -> về trang đăng nhập
 * ------------------------------------------------------------ */
header('Location: ' . $trangDangNhap);
exit();

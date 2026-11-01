<?php
/**
 * Bootstrap riêng cho phân hệ Bảo trì.
 * Cho phép các view trong thư mục này tự chạy mà không cần sửa index.php tổng.
 */
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../controllers/BaoTriController.php';

if (!function_exists('baoTriUrl')) {
    /**
     * Tạo URL tuyệt đối tới một file trong views/pages/bao_tri/.
     */
    function baoTriUrl($file = 'index.php', array $query = []) {
        $base = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
        $url  = $base . '/views/pages/bao_tri/' . ltrim($file, '/');

        if (!empty($query)) {
            $url .= '?' . http_build_query($query);
        }

        return $url;
    }
}

if (!function_exists('baoTriImageUrl')) {
    /**
     * Trả về URL ảnh minh họa của phiếu bảo trì.
     * - Ảnh upload: cột hinh_anh_minh_hoa lưu tên file trong public/uploads/bao_tri/.
     * - Ảnh có sẵn: có thể lưu đường dẫn assets/... hoặc URL http(s).
     * - Phiếu cũ chưa có ảnh: gợi ý ảnh trong assets/img/su_co/ theo mô tả sự cố.
     */
    function baoTriImageUrl($value, $moTaSuCo = '') {
        $base  = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
        $value = trim((string) $value);

        if ($value !== '') {
            if (preg_match('#^https?://#i', $value)) {
                return $value;
            }
            if (str_starts_with($value, 'assets/')) {
                return $base . '/' . ltrim($value, '/');
            }
            return $base . '/public/uploads/bao_tri/' . rawurlencode($value);
        }

        $moTa = mb_strtolower(trim((string) $moTaSuCo), 'UTF-8');
        if ($moTa === '') {
            return '';
        }

        // Thứ tự ưu tiên: từ khóa cụ thể trước, từ khóa chung sau.
        $goiY = [
            ['denbivo.jpg',             ['đèn vỡ', 'vỡ đèn', 'bể đèn', 'bóng đèn vỡ']],
            ['thangcuon.jpg',           ['thang cuốn', 'thang cuon']],
            ['bangchuyen.jpg',          ['băng chuyền', 'băng tải', 'bang chuyen']],
            ['hethongdieuhoa.jpg',      ['điều hòa', 'điều hoà', 'máy lạnh']],
            ['xevacham.jpg',            ['va chạm', 'tông xe', 'đâm xe']],
            ['cauonglonghanhkhach.jpg', ['ống lồng', 'cầu ống lồng']],
            ['xekeohanhly.jpg',         ['hành lý', 'hành lí']],
            ['xenanghanghoa.jpg',       ['hàng hóa', 'hàng hoá']],
            ['maychieu.jpg',            ['máy chiếu', 'màn hình']],
            ['chayden.jpg',             ['cháy', 'đèn', 'led']],
            ['roridau.jpg',             ['rò rỉ', 'dầu', 'thủy lực']],
        ];

        foreach ($goiY as [$fileName, $needles]) {
            foreach ($needles as $needle) {
                if (mb_strpos($moTa, $needle) !== false) {
                    return $base . '/assets/img/su_co/' . $fileName;
                }
            }
        }

        return '';
    }
}

if (!isset($baoTriController) || !($baoTriController instanceof BaoTriController)) {
    $dbConn = $dbConnection ?? $db ?? $conn ?? null;

    if (!$dbConn) {
        die('Không thể kết nối cơ sở dữ liệu.');
    }

    $baoTriController = new BaoTriController($dbConn);
}

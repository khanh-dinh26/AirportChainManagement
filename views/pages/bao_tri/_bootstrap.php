<?php
/**
 * Bootstrap riêng cho phân hệ Bảo trì.
 * Cho phép các view trong thư mục này tự chạy mà không cần sửa index.php tổng.
 */
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../controllers/BaoTriController.php';

yeu_cau_dang_nhap();

$currentScript = basename($_SERVER['PHP_SELF'] ?? '');
if (in_array($currentScript, ['index.php', 'thiet_bi.php', 'chi_tiet_thiet_bi.php'], true) && !co_quyen_bao_tri()) {
    $_SESSION['flash_danger'] = 'Bạn không có quyền truy cập vào phân hệ Kỹ thuật & Bảo trì.';
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

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
     * - Ảnh upload: cột hinh_anh_minh_hoa lưu đường dẫn assets/img/su_co/<file>.
     * - Ảnh có sẵn: có thể lưu đường dẫn assets/... hoặc URL http(s).
     * - Dữ liệu cũ chỉ lưu tên file: tra trong public/uploads/bao_tri/.
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
            ['thang-cuon-tu-dong/19.jpg',           ['thang cuốn', 'thang cuon']],
            ['bang-chuyen-hanh-ly/19.jpg',          ['băng chuyền', 'băng tải', 'bang chuyen']],
            ['he-thong-dieu-hoa-nha-ga/19.jpg',      ['điều hòa', 'điều hoà', 'máy lạnh']],
            ['xevacham.jpg',            ['va chạm', 'tông xe', 'đâm xe']],
            ['cau-ong-long-hanh-khach/19.jpg', ['ống lồng', 'cầu ống lồng']],
            ['xe-keo-hanh-ly/17.jpg',         ['hành lý', 'hành lí']],
            ['xe-nang-hang-hoa/17.jpg',       ['hàng hóa', 'hàng hoá']],
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

if (!function_exists('baoTriAircraftImageUrl')) {
    /**
     * Trả về URL ảnh của loại tàu bay.
     * - Đường dẫn đầy đủ: assets/img/tau_bay/<hang>/<file>.
     * - Dữ liệu cũ chỉ lưu tên file: tra trong assets/img/tau_bay/.
     */
    function baoTriAircraftImageUrl($value) {
        $base  = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
        $value = trim((string) $value);

        if ($value === '') {
            return '';
        }
        if (preg_match('#^https?://#i', $value)) {
            return $value;
        }
        if (str_starts_with($value, 'assets/')) {
            return $base . '/' . ltrim($value, '/');
        }

        return $base . '/assets/img/tau_bay/' . rawurlencode($value);
    }
}

if (!function_exists('baoTriPagination')) {
    /**
     * Tạo thanh phân trang Bootstrap, giữ nguyên bộ lọc qua callback tạo URL.
     *
     * @param int      $current    Trang hiện tại (1-based)
     * @param int      $total      Tổng số trang
     * @param callable $urlBuilder Nhận số trang, trả về URL đầy đủ
     * @param int      $window     Số trang hiển thị mỗi bên trang hiện tại
     */
    function baoTriPagination(int $current, int $total, callable $urlBuilder, int $window = 2): string {
        if ($total <= 1) {
            return '';
        }

        $current = max(1, min($current, $total));

        $pages = [1];
        $start = max(2, $current - $window);
        $end   = min($total - 1, $current + $window);

        if ($start > 2) {
            $pages[] = '...';
        }
        for ($page = $start; $page <= $end; $page++) {
            $pages[] = $page;
        }
        if ($end < $total - 1) {
            $pages[] = '...';
        }
        $pages[] = $total;

        $link = static function (int $page) use ($urlBuilder): string {
            return htmlspecialchars($urlBuilder($page), ENT_QUOTES, 'UTF-8');
        };

        $html  = '<nav class="maint-pagination" aria-label="Phân trang">';
        $html .= '<ul class="pagination pagination-sm justify-content-center flex-wrap mb-0">';

        if ($current > 1) {
            $html .= '<li class="page-item"><a class="page-link" href="' . $link($current - 1) . '" aria-label="Trang trước"><i class="bi bi-chevron-left"></i></a></li>';
        } else {
            $html .= '<li class="page-item disabled"><span class="page-link"><i class="bi bi-chevron-left"></i></span></li>';
        }

        foreach ($pages as $page) {
            if ($page === '...') {
                $html .= '<li class="page-item disabled"><span class="page-link">…</span></li>';
                continue;
            }

            $page = (int) $page;
            if ($page === $current) {
                $html .= '<li class="page-item active" aria-current="page"><span class="page-link">' . $page . '</span></li>';
            } else {
                $html .= '<li class="page-item"><a class="page-link" href="' . $link($page) . '">' . $page . '</a></li>';
            }
        }

        if ($current < $total) {
            $html .= '<li class="page-item"><a class="page-link" href="' . $link($current + 1) . '" aria-label="Trang sau"><i class="bi bi-chevron-right"></i></a></li>';
        } else {
            $html .= '<li class="page-item disabled"><span class="page-link"><i class="bi bi-chevron-right"></i></span></li>';
        }

        $html .= '</ul></nav>';

        return $html;
    }
}

if (!isset($baoTriController) || !($baoTriController instanceof BaoTriController)) {
    $dbConn = $dbConnection ?? $db ?? $conn ?? null;

    if (!$dbConn) {
        die('Không thể kết nối cơ sở dữ liệu.');
    }

    $baoTriController = new BaoTriController($dbConn);
}

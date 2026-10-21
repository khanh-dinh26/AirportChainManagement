<?php

require_once __DIR__ . '/../models/ChuyenBayModel.php';

class ChuyenBayController {
    private $model;

    public function __construct($db) {
        $this->model = new ChuyenBayModel($db);
    }

    public function index() {
        $keyword = trim($_GET['keyword'] ?? '');
        $date    = trim($_GET['date'] ?? '');
        $status  = trim($_GET['status'] ?? '');
        $page    = max(1, (int)($_GET['page'] ?? 1));
        $limit   = 10;

        $chuyenBays = $this->model->getChuyenBays($keyword, $date, $status, $page, $limit);
        $totalItems = $this->model->getTotalChuyenBays($keyword, $date, $status);
        $totalPages = max(1, ceil($totalItems / $limit));
        $stats      = $this->model->getStatsTotal();

        // Lấy dữ liệu hỗ trợ các Form Modal
        $lichMuas    = $this->model->getLichBayMuaAll();
        $hangs       = $this->model->getHangHangKhong();
        $tauBays     = $this->model->getLoaiTauBay();
        $sanBays     = $this->model->getSanBay();
        $cuaRas      = $this->model->getCuaRaMayBay();
        $quays       = $this->model->getQuayCheckin();
        $bangChuyens = $this->model->getBangChuyen();

        $allLogs = [];
        foreach ($chuyenBays as $cb) {
            $allLogs[$cb['id']] = $this->model->getLogsByFlightId($cb['id']);
        }

        require __DIR__ . '/../views/pages/chuyen_bay/index.php';
    }

    public function create() {
        $this->requirePost();
        $data = [
                'id_lich_mua'        => !empty($_POST['id_lich_mua']) ? (int)$_POST['id_lich_mua'] : null,
                'so_hieu'            => trim($_POST['so_hieu'] ?? ''),
                'id_hang_hang_khong' => (int)($_POST['id_hang_hang_khong'] ?? 0),
                'id_loai_tau_bay'    => (int)($_POST['id_loai_tau_bay'] ?? 0),
                'id_san_bay_di'      => (int)($_POST['id_san_bay_di'] ?? 0),
                'id_san_bay_den'     => (int)($_POST['id_san_bay_den'] ?? 0),
                'id_cua_ra_may_bay'  => !empty($_POST['id_cua_ra_may_bay']) ? (int)$_POST['id_cua_ra_may_bay'] : null,
                'id_quay_checkin'    => !empty($_POST['id_quay_checkin']) ? (int)$_POST['id_quay_checkin'] : null,
                'id_bang_chuyen'     => !empty($_POST['id_bang_chuyen']) ? (int)$_POST['id_bang_chuyen'] : null,
                'gio_du_kien'        => $this->normalizeDateTime($_POST['gio_du_kien'] ?? ''),
                'gio_thuc_te'       => !empty($_POST['gio_thuc_te']) ? $this->normalizeDateTime($_POST['gio_thuc_te']) : null,
                'luong_hanh_khach'   => (int)($_POST['luong_hanh_khach'] ?? 0),
                'luong_hang_hoa_tan' => (float)($_POST['luong_hang_hoa_tan'] ?? 0),
                'trang_thai_tong'    => 'Len_Lich'
        ];
        if (!$this->model->create($data)) {
            throw new RuntimeException('Không thể thêm chuyến bay.');
        }
        $this->redirect('created');
    }

    public function edit() {
        $this->requirePost();
        $id = (int)($_POST['id'] ?? 0);
        if ($id < 1) {
            throw new InvalidArgumentException('Mã chuyến bay không hợp lệ.');
        }
        $userId = (int)($_SESSION['user_id'] ?? 1);
        $ghiChuLog = trim($_POST['ghi_chu_log'] ?? '');

        $data = [
                'id_lich_mua'        => !empty($_POST['id_lich_mua']) ? (int)$_POST['id_lich_mua'] : null,
                'so_hieu'            => trim($_POST['so_hieu'] ?? ''),
                'id_hang_hang_khong' => (int)($_POST['id_hang_hang_khong'] ?? 0),
                'id_loai_tau_bay'    => (int)($_POST['id_loai_tau_bay'] ?? 0),
                'id_san_bay_di'      => (int)($_POST['id_san_bay_di'] ?? 0),
                'id_san_bay_den'     => (int)($_POST['id_san_bay_den'] ?? 0),
                'id_cua_ra_may_bay'  => !empty($_POST['id_cua_ra_may_bay']) ? (int)$_POST['id_cua_ra_may_bay'] : null,
                'id_quay_checkin'    => !empty($_POST['id_quay_checkin']) ? (int)$_POST['id_quay_checkin'] : null,
                'id_bang_chuyen'     => !empty($_POST['id_bang_chuyen']) ? (int)$_POST['id_bang_chuyen'] : null,
                'gio_du_kien'        => $this->normalizeDateTime($_POST['gio_du_kien'] ?? ''),
                'gio_thuc_te'       => !empty($_POST['gio_thuc_te']) ? $this->normalizeDateTime($_POST['gio_thuc_te']) : null,
                'luong_hanh_khach'   => (int)($_POST['luong_hanh_khach'] ?? 0),
                'luong_hang_hoa_tan' => (float)($_POST['luong_hang_hoa_tan'] ?? 0),
                'trang_thai_tong'    => $_POST['trang_thai_tong'] ?? 'Len_Lich'
        ];
        if (!$this->model->updateDieuHanh($id, $data, $userId, $ghiChuLog)) {
            throw new RuntimeException('Không thể cập nhật chuyến bay.');
        }
        $this->redirect('updated');
    }

    public function cancel() {
        $this->requirePost();
        $id = (int)($_POST['id'] ?? 0);
        if ($id < 1) {
            throw new InvalidArgumentException('Mã chuyến bay không hợp lệ.');
        }
        $userId = (int)($_SESSION['user_id'] ?? 1);
        $lyDo = trim($_POST['ly_do_huy'] ?? 'Hủy chuyến theo yêu cầu điều hành');
        if (!$this->model->cancelFlight($id, $userId, $lyDo)) {
            throw new RuntimeException('Không thể hủy chuyến bay.');
        }
        $this->redirect('cancelled');
    }

    public function delete() {
        $id = (int)($_GET['id'] ?? 0);
        if ($id > 0) {
            $this->model->delete($id);
        }
        header("Location: index.php?controller=chuyenbay&action=index&msg=deleted");
        exit;
    }

    // --- LỊCH BAY MÙA & BATCH JOB ---
    public function createLichMua() {
        $this->requirePost();
        $data = [
                'so_hieu'            => trim($_POST['so_hieu'] ?? ''),
                'id_hang_hang_khong' => (int)($_POST['id_hang_hang_khong'] ?? 0),
                'id_san_bay_di'      => (int)($_POST['id_san_bay_di'] ?? 0),
                'id_san_bay_den'     => (int)($_POST['id_san_bay_den'] ?? 0),
                'tan_suat_tuan'      => trim($_POST['tan_suat_tuan'] ?? 'Hàng ngày'),
                'tu_ngay'           => $_POST['tu_ngay'] ?? '',
                'den_ngay'          => $_POST['den_ngay'] ?? ''
        ];
        if (!$this->model->createLichBayMua($data)) {
            throw new RuntimeException('Không thể thêm lịch bay mùa.');
        }
        $this->redirect('lich_mua_created');
    }

    public function deleteLichMua() {
        $id = (int)($_GET['id'] ?? 0);
        if ($id > 0) {
            $this->model->deleteLichBayMua($id);
        }
        header("Location: index.php?controller=chuyenbay&action=index&msg=lich_mua_deleted");
        exit;
    }

    public function generateDaily() {
        $this->requirePost();
        $targetDate = $_POST['target_date'] ?? date('Y-m-d');
        $count = $this->model->generateDailyFlightsFromSchedule($targetDate);
        header("Location: index.php?controller=chuyenbay&action=index&msg=generated&count=" . $count);
        exit;
    }

    private function requirePost() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            header('Allow: POST');
            exit('Phương thức yêu cầu không được hỗ trợ.');
        }
    }

    private function normalizeDateTime($value) {
        $value = trim((string) $value);
        if (strlen($value) === 16) {
            $value = str_replace('T', ' ', $value) . ':00';
        }
        return str_replace('T', ' ', $value);
    }

    private function redirect($message) {
        header('Location: index.php?controller=chuyenbay&action=index&msg=' . rawurlencode($message));
        exit;
    }
}
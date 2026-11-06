<?php

require_once __DIR__ . '/../config/config.php';

class HaTangController
{
    /** @var mysqli */
    private $conn;

    private $flash = null;

    private $allowedTabs = ['san_bay', 'nha_ga', 'cua_ra', 'quay', 'bang_chuyen'];

    public function __construct($db_conn)
    {
        $this->conn = $db_conn;
    }

    public function handle()
    {
        $this->ensureCsrfToken();
        $this->processPost();
        $this->flash = $this->pullFlash();

        $quanTri = isset($_GET['quan_tri']) && (string) $_GET['quan_tri'] === '1';
        $tab = isset($_GET['tab']) ? (string) $_GET['tab'] : 'san_bay';
        if (!in_array($tab, $this->allowedTabs, true)) {
            $tab = 'san_bay';
        }

        $airports = $this->getAirports();
        $airportByIata = [];
        foreach ($airports as $airport) {
            $airportByIata[$airport['ma_iata']] = $airport;
        }

        $selectedIata = isset($_GET['san_bay']) ? strtoupper(trim((string) $_GET['san_bay'])) : '';
        $selectedAirport = ($selectedIata !== '' && isset($airportByIata[$selectedIata]))
            ? $airportByIata[$selectedIata]
            : null;

        $terminals = $selectedAirport ? $this->getTerminals((int) $selectedAirport['id']) : [];
        $selectedTerminal = $this->pickTerminal($terminals, isset($_GET['nha_ga']) ? (int) $_GET['nha_ga'] : 0);

        $gates = $selectedTerminal ? $this->getGates((int) $selectedTerminal['id']) : [];
        $checkins = $selectedTerminal ? $this->getCheckins((int) $selectedTerminal['id']) : [];
        $belts = $selectedTerminal ? $this->getBelts((int) $selectedTerminal['id']) : [];

        $khu = isset($_GET['khu']) ? (string) $_GET['khu'] : 'chuyen_bay';
        if (!in_array($khu, ['chuyen_bay', 'nha_ga'], true)) {
            $khu = 'chuyen_bay';
        }

        $filters = $this->readFilters();
        $airlines = $this->getAirlines();
        $flights = null;
        if ($selectedAirport && !$quanTri) {
            $flights = $this->getFlights((int) $selectedAirport['id'], $filters);
        }

        $management = null;
        if ($quanTri) {
            $management = $this->getManagement($tab, $airports, $selectedAirport, $terminals, $selectedTerminal, $gates, $checkins, $belts);
        }

        return [
            'airports' => $airports,
            'airportByIata' => $airportByIata,
            'selectedAirport' => $selectedAirport,
            'terminals' => $terminals,
            'selectedTerminal' => $selectedTerminal,
            'gates' => $gates,
            'checkins' => $checkins,
            'belts' => $belts,
            'flights' => $flights,
            'airlines' => $airlines,
            'filters' => $filters,
            'khu' => $khu,
            'tab' => $tab,
            'quanTri' => $quanTri,
            'management' => $management,
            'csrf' => $_SESSION['ha_tang_csrf'],
            'flash' => $this->flash,
        ];
    }

    /* ------------------------------------------------------------
     * DỮ LIỆU HIỂN THỊ
     * ------------------------------------------------------------ */

    private function getAirports()
    {
        $phamVi = pham_vi_san_bay();
        $where = '';
        if ($phamVi > 0) {
            $where = " WHERE sb.id = " . (int) $phamVi . " ";
        } elseif ($phamVi < 0) {
            $where = " WHERE 1=0 ";
        }

        $sql = "SELECT sb.*,
                    (SELECT COUNT(*) FROM nha_ga ng WHERE ng.id_san_bay = sb.id) AS so_nha_ga,
                    (SELECT COUNT(*) FROM cua_ra_may_bay c
                        JOIN nha_ga ng2 ON ng2.id = c.id_nha_ga
                        WHERE ng2.id_san_bay = sb.id) AS so_cua_ra,
                    (SELECT COUNT(*) FROM quay_checkin q
                        JOIN nha_ga ng3 ON ng3.id = q.id_nha_ga
                        WHERE ng3.id_san_bay = sb.id) AS so_quay_checkin,
                    (SELECT COUNT(*) FROM bang_chuyen_hanh_ly b
                        JOIN nha_ga ng4 ON ng4.id = b.id_nha_ga
                        WHERE ng4.id_san_bay = sb.id) AS so_bang_chuyen
                FROM san_bay sb
                {$where}
                ORDER BY sb.phan_cap, sb.ten_san_bay ASC";

        return $this->fetchAll($this->query($sql));
    }

    private function getTerminals($airportId)
    {
        $sql = "SELECT ng.*,
                    (SELECT COUNT(*) FROM cua_ra_may_bay c WHERE c.id_nha_ga = ng.id) AS so_cua_ra,
                    (SELECT COUNT(*) FROM quay_checkin q WHERE q.id_nha_ga = ng.id) AS so_quay_checkin,
                    (SELECT COUNT(*) FROM bang_chuyen_hanh_ly b WHERE b.id_nha_ga = ng.id) AS so_bang_chuyen
                FROM nha_ga ng
                WHERE ng.id_san_bay = ?
                ORDER BY ng.ma_nha_ga ASC, ng.ten_nha_ga ASC";

        $stmt = $this->prepare($sql);
        $this->bind($stmt, 'i', [$airportId]);
        $stmt->execute();

        return $this->fetchAll($stmt);
    }

    private function getAirlines()
    {
        return $this->fetchAll($this->query("SELECT * FROM hang_hang_khong ORDER BY ten_hang ASC"));
    }

    private function pickTerminal($terminals, $requestedId)
    {
        if (!$terminals) {
            return null;
        }

        foreach ($terminals as $terminal) {
            if ((int) $terminal['id'] === (int) $requestedId) {
                return $terminal;
            }
        }

        return $terminals[0];
    }

    private function readFilters()
    {
        $ngay = isset($_GET['ngay']) ? trim((string) $_GET['ngay']) : '';
        if ($ngay !== '' && !$this->isValidDate($ngay)) {
            $ngay = '';
        }

        $hang = isset($_GET['hang']) ? (int) $_GET['hang'] : 0;
        $soHieu = isset($_GET['so_hieu']) ? strtoupper(trim((string) $_GET['so_hieu'])) : '';
        $huong = isset($_GET['huong']) ? (string) $_GET['huong'] : 'tat_ca';
        if (!in_array($huong, ['tat_ca', 'di', 'den'], true)) {
            $huong = 'tat_ca';
        }

        return [
            'ngay' => $ngay,
            'hang' => $hang,
            'so_hieu' => $soHieu,
            'huong' => $huong,
            'trang' => isset($_GET['trang']) ? max(1, (int) $_GET['trang']) : 1,
        ];
    }

    private function getFlights($airportId, $filters)
    {
        $perPage = 10;
        $departures = $this->countFlights($airportId, $filters, 'di');
        $arrivals = $this->countFlights($airportId, $filters, 'den');

        if ($filters['huong'] === 'di') {
            $total = $departures;
        } elseif ($filters['huong'] === 'den') {
            $total = $arrivals;
        } else {
            $total = $departures + $arrivals;
        }

        $pages = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, (int) $filters['trang']), $pages);
        $offset = ($page - 1) * $perPage;

        $items = $this->fetchFlights($airportId, $filters, $filters['huong'], $perPage, $offset);
        foreach ($items as &$item) {
            $item['huong'] = ((int) $item['id_san_bay_di'] === (int) $airportId) ? 'di' : 'den';
        }
        unset($item);

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'pages' => $pages,
            'per_page' => $perPage,
            'departures_count' => $departures,
            'arrivals_count' => $arrivals,
        ];
    }

    private function countFlights($airportId, $filters, $direction)
    {
        $sql = "SELECT COUNT(*) AS total FROM chuyen_bay_ngay cb WHERE 1 = 1";
        $types = '';
        $params = [];
        $this->appendFlightWhere($sql, $types, $params, $airportId, $filters, $direction);

        $stmt = $this->prepare($sql);
        $this->bind($stmt, $types, $params);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();

        return $row ? (int) $row['total'] : 0;
    }

    private function fetchFlights($airportId, $filters, $direction, $limit, $offset)
    {
        $sql = "SELECT cb.*,
                    hhk.ten_hang,
                    hhk.ma_iata AS ma_hang,
                    sbd.ma_iata AS ma_di,
                    sbd.ten_san_bay AS ten_di,
                    sbden.ma_iata AS ma_den,
                    sbden.ten_san_bay AS ten_den,
                    g.so_cua,
                    q.so_quay,
                    b.so_bang_chuyen
                FROM chuyen_bay_ngay cb
                JOIN hang_hang_khong hhk ON hhk.id = cb.id_hang_hang_khong
                JOIN san_bay sbd ON sbd.id = cb.id_san_bay_di
                JOIN san_bay sbden ON sbden.id = cb.id_san_bay_den
                LEFT JOIN cua_ra_may_bay g ON g.id = cb.id_cua_ra_may_bay
                LEFT JOIN quay_checkin q ON q.id = cb.id_quay_checkin
                LEFT JOIN bang_chuyen_hanh_ly b ON b.id = cb.id_bang_chuyen
                WHERE 1 = 1";

        $types = '';
        $params = [];
        $this->appendFlightWhere($sql, $types, $params, $airportId, $filters, $direction);
        $sql .= " ORDER BY cb.gio_du_kien ASC, cb.id ASC LIMIT ? OFFSET ?";
        $types .= 'ii';
        $params[] = $limit;
        $params[] = $offset;

        $stmt = $this->prepare($sql);
        $this->bind($stmt, $types, $params);
        $stmt->execute();

        return $this->fetchAll($stmt);
    }

    private function appendFlightWhere(&$sql, &$types, &$params, $airportId, $filters, $direction)
    {
        if ($direction === 'di') {
            $sql .= " AND cb.id_san_bay_di = ?";
            $types .= 'i';
            $params[] = $airportId;
        } elseif ($direction === 'den') {
            $sql .= " AND cb.id_san_bay_den = ?";
            $types .= 'i';
            $params[] = $airportId;
        } else {
            $sql .= " AND (cb.id_san_bay_di = ? OR cb.id_san_bay_den = ?)";
            $types .= 'ii';
            $params[] = $airportId;
            $params[] = $airportId;
        }

        if ($filters['ngay'] !== '') {
            $sql .= " AND DATE(cb.gio_du_kien) = ?";
            $types .= 's';
            $params[] = $filters['ngay'];
        }

        if ($filters['hang'] > 0) {
            $sql .= " AND cb.id_hang_hang_khong = ?";
            $types .= 'i';
            $params[] = $filters['hang'];
        }

        if ($filters['so_hieu'] !== '') {
            $sql .= " AND cb.so_hieu LIKE ?";
            $types .= 's';
            $params[] = '%' . $filters['so_hieu'] . '%';
        }
    }

    private function getManagement($tab, $airports, $selectedAirport, $terminals, $selectedTerminal, $gates, $checkins, $belts)
    {
        $management = [
            'tab' => $tab,
            'editRecord' => null,
            'formMode' => null,
            'formType' => null,
            'gates' => $gates,
            'checkins' => $checkins,
            'belts' => $belts,
        ];

        if (isset($_GET['them']) && (string) $_GET['them'] === '1') {
            $management['formMode'] = 'them';
            $management['formType'] = $tab;
        }

        $editId = isset($_GET['sua']) ? (int) $_GET['sua'] : 0;
        if ($editId > 0) {
            $management['formMode'] = 'sua';
            $management['formType'] = $tab;
            $management['editRecord'] = $this->findRecord($tab, $editId);
        }

        return $management;
    }

    private function findRecord($tab, $id)
    {
        $map = [
            'san_bay' => 'san_bay',
            'nha_ga' => 'nha_ga',
            'cua_ra' => 'cua_ra_may_bay',
            'quay' => 'quay_checkin',
            'bang_chuyen' => 'bang_chuyen_hanh_ly',
        ];

        if (!isset($map[$tab])) {
            return null;
        }

        $stmt = $this->prepare("SELECT * FROM `{$map[$tab]}` WHERE id = ? LIMIT 1");
        $this->bind($stmt, 'i', [$id]);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();

        return $row ?: null;
    }

    private function getGates($terminalId)
    {
        $stmt = $this->prepare("SELECT * FROM cua_ra_may_bay WHERE id_nha_ga = ? ORDER BY LENGTH(so_cua), so_cua");
        $this->bind($stmt, 'i', [$terminalId]);
        $stmt->execute();

        return $this->fetchAll($stmt);
    }

    private function getCheckins($terminalId)
    {
        $stmt = $this->prepare("SELECT * FROM quay_checkin WHERE id_nha_ga = ? ORDER BY LENGTH(so_quay), so_quay");
        $this->bind($stmt, 'i', [$terminalId]);
        $stmt->execute();

        return $this->fetchAll($stmt);
    }

    private function getBelts($terminalId)
    {
        $stmt = $this->prepare("SELECT * FROM bang_chuyen_hanh_ly WHERE id_nha_ga = ? ORDER BY LENGTH(so_bang_chuyen), so_bang_chuyen");
        $this->bind($stmt, 'i', [$terminalId]);
        $stmt->execute();

        return $this->fetchAll($stmt);
    }

    /* ------------------------------------------------------------
     * XỬ LÝ POST (THÊM / SỬA / XÓA)
     * ------------------------------------------------------------ */

    private function processPost()
    {
        if (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }

        $action = isset($_POST['action']) ? (string) $_POST['action'] : '';
        if ($action === '') {
            return;
        }

        $csrf = isset($_POST['csrf']) ? (string) $_POST['csrf'] : '';
        if (!hash_equals($_SESSION['ha_tang_csrf'] ?? '', $csrf)) {
            $this->setFlash('danger', 'Phiên làm việc không hợp lệ. Vui lòng thử lại.');
            $this->redirectAfterPost();
        }

        /* ------------------------------------------------------------
         * BƯỚC XÁC NHẬN XÓA (không dùng confirm() của JavaScript)
         * Lần gửi đầu tiên chưa có $_POST['xac_nhan']: không xóa ngay
         * mà lưu lại yêu cầu, quay về danh sách và hiện hộp thoại
         * xác nhận. Chỉ khi người dùng bấm "Xóa" trong hộp thoại
         * (form gửi kèm xac_nhan=1) mới thực hiện xóa.
         * ------------------------------------------------------------ */
        $deleteActions = ['xoa_san_bay', 'xoa_nha_ga', 'xoa_cua_ra', 'xoa_quay', 'xoa_bang_chuyen'];
        if (in_array($action, $deleteActions, true) && empty($_POST['xac_nhan'])) {
            $_SESSION['ha_tang_xac_nhan_xoa'] = $_POST;
            $this->redirectAfterPost();
        }

                // Guard 1: Sân bay - Chỉ Admin Tổng công ty mới được thêm/sửa/xóa sân bay
        $airportActions = ['them_san_bay', 'sua_san_bay', 'xoa_san_bay'];
        if (in_array($action, $airportActions, true) && !la_admin()) {
            $this->setFlash('danger', 'Chỉ Quản trị viên Tổng công ty mới có quyền thêm, sửa hoặc xóa sân bay.');
            $this->redirectAfterPost();
        }

        // Guard 2: Nhà ga - Kiểm tra quyền theo phạm vi sân bay
        if ($action === 'them_nha_ga') {
            if (!la_admin()) {
                $_POST['id_san_bay'] = pham_vi_san_bay();
            }
            if (!trong_pham_vi_san_bay($_POST['id_san_bay'] ?? 0)) {
                $this->setFlash('danger', 'Bạn không có quyền thêm nhà ga cho sân bay khác.');
                $this->redirectAfterPost();
            }
        } elseif (in_array($action, ['sua_nha_ga', 'xoa_nha_ga'], true)) {
            $nhaGaId = (int) ($_POST['id'] ?? 0);
            $nhaGaAirportId = $this->getAirportIdOfTerminal($nhaGaId);
            if (!trong_pham_vi_san_bay($nhaGaAirportId)) {
                $this->setFlash('danger', 'Bạn không có quyền thao tác trên nhà ga của sân bay khác.');
                $this->redirectAfterPost();
            }
            if ($action === 'sua_nha_ga' && !la_admin()) {
                $_POST['id_san_bay'] = $nhaGaAirportId; // Ép không cho chuyển sang sân bay khác
            }
        }

        // Guard 3: Cửa ra, Quầy, Băng chuyền
        $resourceAddActions = [
            'them_cua_ra' => 'cua_ra_may_bay',
            'them_quay' => 'quay_checkin',
            'them_bang_chuyen' => 'bang_chuyen_hanh_ly',
        ];
        if (isset($resourceAddActions[$action])) {
            $terminalId = (int) ($_POST['id_nha_ga'] ?? 0);
            $termAirportId = $this->getAirportIdOfTerminal($terminalId);
            if (!trong_pham_vi_san_bay($termAirportId)) {
                $this->setFlash('danger', 'Nhà ga đã chọn không thuộc phạm vi sân bay bạn quản lý.');
                $this->redirectAfterPost();
            }
        }

        $resourceEditDeleteActions = [
            'sua_cua_ra' => 'cua_ra_may_bay',
            'xoa_cua_ra' => 'cua_ra_may_bay',
            'sua_quay' => 'quay_checkin',
            'xoa_quay' => 'quay_checkin',
            'sua_bang_chuyen' => 'bang_chuyen_hanh_ly',
            'xoa_bang_chuyen' => 'bang_chuyen_hanh_ly',
        ];
        if (isset($resourceEditDeleteActions[$action])) {
            $table = $resourceEditDeleteActions[$action];
            $resId = (int) ($_POST['id'] ?? 0);
            $resAirportId = $this->getAirportIdOfResource($table, $resId);
            if (!trong_pham_vi_san_bay($resAirportId)) {
                $this->setFlash('danger', 'Bạn không có quyền thao tác trên tài nguyên của sân bay khác.');
                $this->redirectAfterPost();
            }
            if (strpos($action, 'sua_') === 0 && isset($_POST['id_nha_ga'])) {
                $newTermAirportId = $this->getAirportIdOfTerminal((int) $_POST['id_nha_ga']);
                if (!trong_pham_vi_san_bay($newTermAirportId)) {
                    $this->setFlash('danger', 'Không thể chuyển tài nguyên sang nhà ga của sân bay khác.');
                    $this->redirectAfterPost();
                }
            }
        }

        switch ($action) {
            case 'them_san_bay':
                $this->saveSanBay(false);
                break;
            case 'sua_san_bay':
                $this->saveSanBay(true);
                break;
            case 'xoa_san_bay':
                $this->deleteSanBay();
                break;

            case 'them_nha_ga':
                $this->saveNhaGa(false);
                break;
            case 'sua_nha_ga':
                $this->saveNhaGa(true);
                break;
            case 'xoa_nha_ga':
                $this->deleteNhaGa();
                break;

            case 'them_cua_ra':
                $this->saveResource('cua_ra', false);
                break;
            case 'sua_cua_ra':
                $this->saveResource('cua_ra', true);
                break;
            case 'xoa_cua_ra':
                $this->deleteResource('cua_ra');
                break;

            case 'them_quay':
                $this->saveResource('quay', false);
                break;
            case 'sua_quay':
                $this->saveResource('quay', true);
                break;
            case 'xoa_quay':
                $this->deleteResource('quay');
                break;

            case 'them_bang_chuyen':
                $this->saveResource('bang_chuyen', false);
                break;
            case 'sua_bang_chuyen':
                $this->saveResource('bang_chuyen', true);
                break;
            case 'xoa_bang_chuyen':
                $this->deleteResource('bang_chuyen');
                break;

            default:
                $this->setFlash('warning', 'Hành động không được hỗ trợ.');
                break;
        }

        $this->redirectAfterPost();
    }

    private function saveSanBay($isEdit)
    {
        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        $iata = strtoupper(trim((string) ($_POST['ma_iata'] ?? '')));
        $icao = strtoupper(trim((string) ($_POST['ma_icao'] ?? '')));
        $ten = trim((string) ($_POST['ten_san_bay'] ?? ''));
        $tenNgan = trim((string) ($_POST['ten_ngan'] ?? ''));
        $phanCap = (string) ($_POST['phan_cap'] ?? 'Quoc_Noi');
        $viTri = trim((string) ($_POST['vi_tri'] ?? ''));
        $hinhAnh = trim((string) ($_POST['hinh_anh'] ?? ''));
        $moTa = trim((string) ($_POST['mo_ta'] ?? ''));
        $trangThai = (string) ($_POST['trang_thai'] ?? 'Hoat_Dong');

        $errors = [];
        if (!preg_match('/^[A-Z]{3}$/', $iata)) {
            $errors[] = 'Mã IATA phải gồm đúng 3 chữ cái (ví dụ: HAN).';
        }
        if ($icao !== '' && !preg_match('/^[A-Z]{4}$/', $icao)) {
            $errors[] = 'Mã ICAO phải gồm đúng 4 chữ cái (ví dụ: VVNB).';
        }
        if ($ten === '') {
            $errors[] = 'Tên sân bay không được để trống.';
        }
        if ($tenNgan === '') {
            $errors[] = 'Tên ngắn không được để trống.';
        }
        if (!in_array($phanCap, ['Quoc_Noi', 'Quoc_Te'], true)) {
            $errors[] = 'Phân cấp sân bay không hợp lệ.';
        }
        if ($viTri === '') {
            $errors[] = 'Vị trí không được để trống.';
        }
        if (!in_array($trangThai, ['Hoat_Dong', 'Bao_Tri', 'Dong_Cua_Khan_Cap'], true)) {
            $errors[] = 'Trạng thái sân bay không hợp lệ.';
        }
        if ($isEdit && $id <= 0) {
            $errors[] = 'Không xác định được sân bay cần sửa.';
        }

        // Xử lý tệp ảnh tải lên từ máy tính (nếu có chọn tệp)
        $uploadHinhAnh = null;
        if (isset($_FILES['hinh_anh_file']) && is_array($_FILES['hinh_anh_file'])) {
            $file = $_FILES['hinh_anh_file'];
            $uploadError = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);

            if ($uploadError !== UPLOAD_ERR_NO_FILE) {
                $tmpName = (string) ($file['tmp_name'] ?? '');
                $fileSize = (int) ($file['size'] ?? 0);

                if ($uploadError !== UPLOAD_ERR_OK) {
                    $errors[] = 'Không thể tải ảnh sân bay lên (mã lỗi ' . $uploadError . ').';
                } elseif (!is_uploaded_file($tmpName)) {
                    $errors[] = 'Tệp ảnh sân bay tải lên không hợp lệ.';
                } elseif ($fileSize <= 0 || $fileSize > 5 * 1024 * 1024) {
                    $errors[] = 'Ảnh sân bay phải có dung lượng từ 1 byte đến 5 MB.';
                } else {
                    $mime = '';
                    if (class_exists('finfo')) {
                        $finfo = new finfo(FILEINFO_MIME_TYPE);
                        $mime = (string) $finfo->file($tmpName);
                    } elseif (function_exists('mime_content_type')) {
                        $mime = (string) mime_content_type($tmpName);
                    }

                    $allowedMimes = [
                        'image/jpeg' => 'jpg',
                        'image/png'  => 'png',
                        'image/webp' => 'webp',
                    ];

                    if (!isset($allowedMimes[$mime]) || @getimagesize($tmpName) === false) {
                        $errors[] = 'Ảnh sân bay phải là tệp ảnh JPG, PNG hoặc WebP hợp lệ.';
                    } else {
                        $uploadHinhAnh = [
                            'tmp_name' => $tmpName,
                            'extension' => $allowedMimes[$mime],
                        ];
                    }
                }
            }
        }

        if ($errors) {
            $this->setFlash('danger', implode(' ', $errors));
            return;
        }

        // Lưu ảnh vừa tải lên vào thư mục assets/img/san_bay
        if ($uploadHinhAnh !== null) {
            $relativeDir = 'assets/img/san_bay';
            $absoluteDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR . 'san_bay';
            if (!is_dir($absoluteDir)) {
                @mkdir($absoluteDir, 0777, true);
            }
            $fileName = $iata . '_' . time() . '.' . $uploadHinhAnh['extension'];
            $targetPath = $absoluteDir . DIRECTORY_SEPARATOR . $fileName;

            if (@move_uploaded_file($uploadHinhAnh['tmp_name'], $targetPath)) {
                $hinhAnh = $relativeDir . '/' . $fileName;
            } else {
                $this->setFlash('danger', 'Không thể lưu tệp ảnh sân bay vào thư mục lưu trữ.');
                return;
            }
        }

        if ($hinhAnh === '') {
            $hinhAnh = 'assets/img/san_bay/' . $iata . '.jpg';
        }
        if ($icao === '') {
            $icao = null;
        }
        if ($moTa === '') {
            $moTa = null;
        }

        if ($isEdit) {
            $sql = "UPDATE san_bay
                    SET ma_iata = ?, ma_icao = ?, ten_san_bay = ?, ten_ngan = ?, phan_cap = ?,
                        vi_tri = ?, hinh_anh = ?, mo_ta = ?, trang_thai = ?
                    WHERE id = ?";
            $types = 'sssssssssi';
            $params = [$iata, $icao, $ten, $tenNgan, $phanCap, $viTri, $hinhAnh, $moTa, $trangThai, $id];
            $label = 'sân bay';
        } else {
            $sql = "INSERT INTO san_bay
                    (ma_iata, ma_icao, ten_san_bay, ten_ngan, phan_cap, vi_tri, hinh_anh, mo_ta, trang_thai)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $types = 'sssssssss';
            $params = [$iata, $icao, $ten, $tenNgan, $phanCap, $viTri, $hinhAnh, $moTa, $trangThai];
            $label = 'sân bay';
        }

        if ($this->executeWrite($sql, $types, $params, $label)) {
            $this->setReturnContext($iata);
        }
    }

    private function deleteSanBay()
    {
        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        if ($id <= 0) {
            $this->setFlash('danger', 'Không xác định được sân bay cần xóa.');
            return;
        }

        $this->executeDelete(
            "DELETE FROM san_bay WHERE id = ?",
            'i',
            [$id],
            'sân bay',
            'Không thể xóa sân bay vì đang có lịch bay hoặc chuyến bay tham chiếu.'
        );
    }

    private function saveNhaGa($isEdit)
    {
        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        $airportId = (int) ($_POST['id_san_bay'] ?? 0);
        $ma = strtoupper(trim((string) ($_POST['ma_nha_ga'] ?? '')));
        $ten = trim((string) ($_POST['ten_nha_ga'] ?? ''));
        $loai = (string) ($_POST['loai_nha_ga'] ?? 'Hon_Hop');
        $nam = trim((string) ($_POST['nam_khai_thac'] ?? ''));
        $dienTich = trim((string) ($_POST['dien_tich'] ?? ''));
        $congSuat = trim((string) ($_POST['cong_suat'] ?? ''));
        $soDo = '';
        $moTa = trim((string) ($_POST['mo_ta'] ?? ''));
        $trangThai = (string) ($_POST['trang_thai'] ?? 'Hoat_Dong');

        $errors = [];
        if ($airportId <= 0) {
            $errors[] = 'Vui lòng chọn sân bay.';
        }
        if ($ma === '') {
            $errors[] = 'Mã nhà ga không được để trống.';
        }
        if ($ten === '') {
            $errors[] = 'Tên nhà ga không được để trống.';
        }
        if (!in_array($loai, ['Quoc_Noi', 'Quoc_Te', 'Hon_Hop'], true)) {
            $errors[] = 'Loại nhà ga không hợp lệ.';
        }
        if ($nam !== '' && (!ctype_digit($nam) || (int) $nam < 1900 || (int) $nam > 2100)) {
            $errors[] = 'Năm khai thác không hợp lệ.';
        }
        if (!in_array($trangThai, ['Hoat_Dong', 'Bao_Tri'], true)) {
            $errors[] = 'Trạng thái nhà ga không hợp lệ.';
        }
        if ($isEdit && $id <= 0) {
            $errors[] = 'Không xác định được nhà ga cần sửa.';
        }

        $upload = null;
        if (isset($_FILES['so_do_file']) && is_array($_FILES['so_do_file'])) {
            $file = $_FILES['so_do_file'];
            $uploadError = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);

            if ($uploadError !== UPLOAD_ERR_NO_FILE) {
                $tmpName = (string) ($file['tmp_name'] ?? '');
                $fileSize = (int) ($file['size'] ?? 0);

                if ($uploadError !== UPLOAD_ERR_OK) {
                    $errors[] = 'Không thể tải ảnh sơ đồ lên (mã lỗi ' . $uploadError . ').';
                } elseif (!is_uploaded_file($tmpName)) {
                    $errors[] = 'Tệp ảnh sơ đồ không hợp lệ.';
                } elseif ($fileSize <= 0 || $fileSize > 5 * 1024 * 1024) {
                    $errors[] = 'Ảnh sơ đồ phải có dung lượng từ 1 byte đến 5 MB.';
                } else {
                    $mime = '';
                    if (class_exists('finfo')) {
                        $finfo = new finfo(FILEINFO_MIME_TYPE);
                        $mime = (string) $finfo->file($tmpName);
                    } elseif (function_exists('mime_content_type')) {
                        $mime = (string) mime_content_type($tmpName);
                    }

                    $allowedMimes = [
                        'image/jpeg' => 'jpg',
                        'image/png' => 'png',
                        'image/webp' => 'webp',
                    ];

                    if (!isset($allowedMimes[$mime]) || @getimagesize($tmpName) === false) {
                        $errors[] = 'Ảnh sơ đồ phải là tệp JPG, PNG hoặc WebP hợp lệ.';
                    } else {
                        $upload = [
                            'tmp_name' => $tmpName,
                            'extension' => $allowedMimes[$mime],
                        ];
                    }
                }
            }
        }

        if ($errors) {
            $this->setFlash('danger', implode(' ', $errors));
            return;
        }

        $oldSoDo = $isEdit ? $this->getTerminalDiagramPath($id) : '';
        $newUploadPath = '';
        if ($upload !== null) {
            $relativeDir = 'assets/uploads/nha_ga';
            $absoluteDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'nha_ga';

            if (!is_dir($absoluteDir) && !@mkdir($absoluteDir, 0775, true) && !is_dir($absoluteDir)) {
                $this->setFlash('danger', 'Không thể tạo thư mục lưu ảnh sơ đồ.');
                return;
            }

            $fileName = 'nha_ga_' . date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.' . $upload['extension'];
            $destination = $absoluteDir . DIRECTORY_SEPARATOR . $fileName;

            if (!move_uploaded_file($upload['tmp_name'], $destination)) {
                $this->setFlash('danger', 'Không thể lưu ảnh sơ đồ vừa tải lên.');
                return;
            }

            $soDo = $relativeDir . '/' . $fileName;
            $newUploadPath = $soDo;
        } elseif ($isEdit) {
            $soDo = $oldSoDo;
        }

        $namValue = $nam === '' ? null : (int) $nam;
        $dienTich = $dienTich === '' ? null : $dienTich;
        $congSuat = $congSuat === '' ? null : $congSuat;
        $soDo = $soDo === '' ? null : $soDo;
        $moTa = $moTa === '' ? null : $moTa;

        if ($isEdit) {
            $sql = "UPDATE nha_ga
                    SET id_san_bay = ?, ma_nha_ga = ?, ten_nha_ga = ?, loai_nha_ga = ?,
                        nam_khai_thac = ?, dien_tich = ?, cong_suat = ?, so_do = ?, mo_ta = ?, trang_thai = ?
                    WHERE id = ?";
            $types = 'isssisssssi';
            $params = [$airportId, $ma, $ten, $loai, $namValue, $dienTich, $congSuat, $soDo, $moTa, $trangThai, $id];
        } else {
            $sql = "INSERT INTO nha_ga
                    (id_san_bay, ma_nha_ga, ten_nha_ga, loai_nha_ga, nam_khai_thac, dien_tich, cong_suat, so_do, mo_ta, trang_thai)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $types = 'isssisssss';
            $params = [$airportId, $ma, $ten, $loai, $namValue, $dienTich, $congSuat, $soDo, $moTa, $trangThai];
        }

        if ($this->executeWrite($sql, $types, $params, 'nhà ga')) {
            if ($newUploadPath !== '' && $oldSoDo !== '' && $oldSoDo !== $newUploadPath
                && strpos($oldSoDo, 'assets/uploads/nha_ga/') === 0) {
                $oldAbsolute = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $oldSoDo);
                if (is_file($oldAbsolute)) {
                    @unlink($oldAbsolute);
                }
            }

            $savedId = $isEdit ? $id : (int) $this->conn->insert_id;
            $context = $this->getTerminalContext($savedId);
            if ($context) {
                $this->setReturnContext($context['ma_iata'], $context['id']);
            } else {
                $this->setReturnContext($this->getAirportIata($airportId), 0);
            }
        } elseif ($newUploadPath !== '') {
            $newAbsolute = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $newUploadPath);
            if (is_file($newAbsolute)) {
                @unlink($newAbsolute);
            }
        }
    }

    private function deleteNhaGa()
    {
        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        if ($id <= 0) {
            $this->setFlash('danger', 'Không xác định được nhà ga cần xóa.');
            return;
        }

        $this->executeDelete(
            "DELETE FROM nha_ga WHERE id = ?",
            'i',
            [$id],
            'nhà ga',
            'Không thể xóa nhà ga này.'
        );
    }

    private function saveResource($type, $isEdit)
    {
        $map = [
            'cua_ra' => ['table' => 'cua_ra_may_bay', 'field' => 'so_cua', 'label' => 'cửa ra máy bay'],
            'quay' => ['table' => 'quay_checkin', 'field' => 'so_quay', 'label' => 'quầy check-in'],
            'bang_chuyen' => ['table' => 'bang_chuyen_hanh_ly', 'field' => 'so_bang_chuyen', 'label' => 'băng chuyền hành lý'],
        ];

        if (!isset($map[$type])) {
            $this->setFlash('danger', 'Loại tài nguyên không hợp lệ.');
            return;
        }

        $config = $map[$type];
        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        $terminalId = (int) ($_POST['id_nha_ga'] ?? 0);
        $so = strtoupper(trim((string) ($_POST['so'] ?? '')));
        $khuVuc = trim((string) ($_POST['khu_vuc'] ?? ''));
        $trangThai = (string) ($_POST['trang_thai'] ?? 'Hoat_Dong');

        $errors = [];
        if ($terminalId <= 0) {
            $errors[] = 'Vui lòng chọn nhà ga.';
        }
        if ($so === '') {
            $errors[] = 'Số hiệu không được để trống.';
        }
        if (!in_array($trangThai, ['Hoat_Dong', 'Bao_Tri'], true)) {
            $errors[] = 'Trạng thái không hợp lệ.';
        }
        if ($isEdit && $id <= 0) {
            $errors[] = 'Không xác định được bản ghi cần sửa.';
        }

        if ($errors) {
            $this->setFlash('danger', implode(' ', $errors));
            return;
        }

        $khuVuc = $khuVuc === '' ? null : $khuVuc;
        $table = $config['table'];
        $field = $config['field'];

        if ($isEdit) {
            $sql = "UPDATE `{$table}`
                    SET id_nha_ga = ?, `{$field}` = ?, khu_vuc = ?, trang_thai = ?
                    WHERE id = ?";
            $types = 'isssi';
            $params = [$terminalId, $so, $khuVuc, $trangThai, $id];
        } else {
            $sql = "INSERT INTO `{$table}` (id_nha_ga, `{$field}`, khu_vuc, trang_thai)
                    VALUES (?, ?, ?, ?)";
            $types = 'isss';
            $params = [$terminalId, $so, $khuVuc, $trangThai];
        }

        if ($this->executeWrite($sql, $types, $params, $config['label'])) {
            if ($type === 'bang_chuyen') {
                $beltId = $isEdit ? $id : (int) $this->conn->insert_id;
                $this->dongBoBangChuyenVoiBaoTri($beltId, $terminalId, $so, $khuVuc, $trangThai);
            }

            $context = $this->getTerminalContext($terminalId);
            if ($context) {
                $this->setReturnContext($context['ma_iata'], $context['id']);
            }
        }
    }

    
    /**
     * Đồng bộ hai chiều trạng thái Băng chuyền hành lý sang phân hệ Thiết bị & Bảo trì
     */
    private function dongBoBangChuyenVoiBaoTri($idBangChuyen, $terminalId, $soBangChuyen, $khuVuc, $trangThai)
    {
        $idBangChuyen = (int) $idBangChuyen;
        $terminalId   = (int) $terminalId;
        if ($idBangChuyen <= 0 || $terminalId <= 0) {
            return;
        }

        $stmtTerm = $this->prepare("SELECT id_san_bay, ten_nha_ga FROM nha_ga WHERE id = ?");
        $this->bind($stmtTerm, 'i', [$terminalId]);
        $stmtTerm->execute();
        $termRow = $stmtTerm->get_result()->fetch_assoc();
        $stmtTerm->close();

        if (!$termRow) {
            return;
        }

        $idSanBay   = (int) $termRow['id_san_bay'];
        $tenNhaGa   = trim((string) $termRow['ten_nha_ga']);
        $tenThietBi = "Băng chuyền " . $soBangChuyen . " - " . $tenNhaGa;
        if (!empty($khuVuc)) {
            $tenThietBi .= " (" . trim((string) $khuVuc) . ")";
        }

        $trangThaiTb = ($trangThai === 'Bao_Tri') ? 'Bao_Tri' : 'Tot';

        $stmtCheck = $this->prepare("SELECT id, trang_thai FROM thiet_bi_san_bay WHERE id_bang_chuyen = ?");
        $this->bind($stmtCheck, 'i', [$idBangChuyen]);
        $stmtCheck->execute();
        $tbRow = $stmtCheck->get_result()->fetch_assoc();
        $stmtCheck->close();

        $idThietBi = 0;
        if ($tbRow) {
            $idThietBi = (int) $tbRow['id'];
            $stmtUp = $this->prepare(
                "UPDATE thiet_bi_san_bay
                 SET ten_thiet_bi = ?, id_san_bay = ?, trang_thai = ?
                 WHERE id = ?"
            );
            $this->bind($stmtUp, 'sisi', [$tenThietBi, $idSanBay, $trangThaiTb, $idThietBi]);
            $stmtUp->execute();
            $stmtUp->close();
        } else {
            $stmtIns = $this->prepare(
                "INSERT INTO thiet_bi_san_bay (id_san_bay, ten_thiet_bi, loai_thiet_bi, trang_thai, id_bang_chuyen)
                 VALUES (?, ?, 'Bang_Chuyen', ?, ?)"
            );
            $this->bind($stmtIns, 'issi', [$idSanBay, $tenThietBi, $trangThaiTb, $idBangChuyen]);
            $stmtIns->execute();
            $idThietBi = (int) $this->conn->insert_id;
            $stmtIns->close();
        }

        if ($idThietBi > 0) {
            if ($trangThai === 'Bao_Tri') {
                $stmtCheckTicket = $this->prepare(
                    "SELECT COUNT(*) AS so_luong FROM yeu_cau_bao_tri
                     WHERE id_thiet_bi = ? AND trang_thai_xu_ly <> 'Hoan_Thanh'"
                );
                $this->bind($stmtCheckTicket, 'i', [$idThietBi]);
                $stmtCheckTicket->execute();
                $tRow = $stmtCheckTicket->get_result()->fetch_assoc();
                $stmtCheckTicket->close();

                if ((int) ($tRow['so_luong'] ?? 0) === 0) {
                    $moTa = "Yêu cầu bảo trì kỹ thuật cho Băng chuyền " . $soBangChuyen . " (" . $tenNhaGa . ") được khởi tạo từ phân hệ Quản lý hạ tầng";
                    $stmtTicket = $this->prepare(
                        "INSERT INTO yeu_cau_bao_tri (id_thiet_bi, mo_ta_su_co, muc_do, trang_thai_xu_ly)
                         VALUES (?, ?, 'Trung_Binh', 'Dang_Xu_Ly')"
                    );
                    $this->bind($stmtTicket, 'is', [$idThietBi, $moTa]);
                    $stmtTicket->execute();
                    $stmtTicket->close();
                }
            } else {
                $stmtCloseTicket = $this->prepare(
                    "UPDATE yeu_cau_bao_tri
                     SET trang_thai_xu_ly = 'Hoan_Thanh',
                         ngay_hoan_thanh = COALESCE(ngay_hoan_thanh, CURRENT_TIMESTAMP)
                     WHERE id_thiet_bi = ? AND trang_thai_xu_ly <> 'Hoan_Thanh'"
                );
                $this->bind($stmtCloseTicket, 'i', [$idThietBi]);
                $stmtCloseTicket->execute();
                $stmtCloseTicket->close();
            }
        }
    }

    private function deleteResource($type)
    {
        $map = [
            'cua_ra' => ['table' => 'cua_ra_may_bay', 'label' => 'cửa ra máy bay'],
            'quay' => ['table' => 'quay_checkin', 'label' => 'quầy check-in'],
            'bang_chuyen' => ['table' => 'bang_chuyen_hanh_ly', 'label' => 'băng chuyền hành lý'],
        ];

        if (!isset($map[$type])) {
            $this->setFlash('danger', 'Loại tài nguyên không hợp lệ.');
            return;
        }

        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        if ($id <= 0) {
            $this->setFlash('danger', 'Không xác định được bản ghi cần xóa.');
            return;
        }

        if ($type === 'bang_chuyen' && $id > 0) {
            $stmtTb = $this->prepare("SELECT id FROM thiet_bi_san_bay WHERE id_bang_chuyen = ?");
            $this->bind($stmtTb, 'i', [$id]);
            $stmtTb->execute();
            $tbRow = $stmtTb->get_result()->fetch_assoc();
            $stmtTb->close();

            if ($tbRow) {
                $idTb = (int) $tbRow['id'];
                $stmtCnt = $this->prepare("SELECT COUNT(*) FROM yeu_cau_bao_tri WHERE id_thiet_bi = ?");
                $this->bind($stmtCnt, 'i', [$idTb]);
                $stmtCnt->execute();
                $stmtCnt->bind_result($soPhieu);
                $stmtCnt->fetch();
                $stmtCnt->close();

                if ((int) $soPhieu === 0) {
                    $this->conn->query("DELETE FROM thiet_bi_san_bay WHERE id = {$idTb}");
                } else {
                    $this->conn->query("UPDATE thiet_bi_san_bay SET id_bang_chuyen = NULL WHERE id = {$idTb}");
                }
            }
        }

        $this->executeDelete(
            "DELETE FROM `{$map[$type]['table']}` WHERE id = ?",
            'i',
            [$id],
            $map[$type]['label'],
            'Không thể xóa bản ghi này.'
        );
    }

    /* ------------------------------------------------------------
     * TIỆN ÍCH CSDL
     * ------------------------------------------------------------ */

    private function query($sql)
    {
        return $this->conn->query($sql);
    }

    private function prepare($sql)
    {
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            throw new RuntimeException('Không thể chuẩn bị truy vấn: ' . $this->conn->error);
        }

        return $stmt;
    }

    private function bind($stmt, $types, $params)
    {
        if ($types !== '') {
            $stmt->bind_param($types, ...$params);
        }
    }

    private function fetchAll($resultOrStmt)
    {
        $rows = [];
        $result = $resultOrStmt instanceof mysqli_stmt
            ? $resultOrStmt->get_result()
            : $resultOrStmt;

        if (!$result) {
            return $rows;
        }

        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }

        return $rows;
    }

    private function executeWrite($sql, $types, $params, $label)
    {
        try {
            $stmt = $this->prepare($sql);
            $this->bind($stmt, $types, $params);
            $stmt->execute();
            $this->setFlash('success', 'Đã lưu ' . $label . ' thành công.');

            return true;
        } catch (mysqli_sql_exception $e) {
            $this->setFlash('danger', $this->databaseErrorMessage($e, $label));

            return false;
        }
    }

    private function executeDelete($sql, $types, $params, $label, $referenceMessage)
    {
        try {
            $stmt = $this->prepare($sql);
            $this->bind($stmt, $types, $params);
            $stmt->execute();

            if ($stmt->affected_rows > 0) {
                $this->setFlash('success', 'Đã xóa ' . $label . '.');
            } else {
                $this->setFlash('warning', 'Không tìm thấy ' . $label . ' cần xóa.');
            }
        } catch (mysqli_sql_exception $e) {
            if ((int) $e->getCode() === 1451) {
                $this->setFlash('danger', $referenceMessage);
            } else {
                $this->setFlash('danger', $this->databaseErrorMessage($e, $label));
            }
        }
    }

    private function getAirportIata($airportId)
    {
        try {
            $stmt = $this->prepare("SELECT ma_iata FROM san_bay WHERE id = ? LIMIT 1");
            $this->bind($stmt, 'i', [$airportId]);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();

            return $row ? (string) $row['ma_iata'] : '';
        } catch (Throwable $e) {
            return '';
        }
    }

    private function getTerminalDiagramPath($terminalId)
    {
        try {
            $stmt = $this->prepare("SELECT so_do FROM nha_ga WHERE id = ? LIMIT 1");
            $this->bind($stmt, 'i', [$terminalId]);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();

            return $row ? trim((string) ($row['so_do'] ?? '')) : '';
        } catch (Throwable $e) {
            return '';
        }
    }

    private function getTerminalContext($terminalId)
    {
        try {
            $stmt = $this->prepare(
                "SELECT ng.id, sb.ma_iata
                 FROM nha_ga ng
                 JOIN san_bay sb ON sb.id = ng.id_san_bay
                 WHERE ng.id = ? LIMIT 1"
            );
            $this->bind($stmt, 'i', [$terminalId]);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();

            return $row ?: null;
        } catch (Throwable $e) {
            return null;
        }
    }

    private function setReturnContext($airportIata, $terminalId = 0)
    {
        $airportIata = trim((string) $airportIata);
        if ($airportIata !== '') {
            $_POST['return_san_bay'] = $airportIata;
        }
        $_POST['return_nha_ga'] = (int) $terminalId;
    }

    private function databaseErrorMessage($e, $label)
    {
        if ((int) $e->getCode() === 1062) {
            return 'Dữ liệu bị trùng. Vui lòng kiểm tra mã/số hiệu đã tồn tại.';
        }
        if ((int) $e->getCode() === 1452) {
            return 'Không tìm thấy sân bay hoặc nhà ga được chọn.';
        }
        if ((int) $e->getCode() === 1451) {
            return 'Không thể xóa ' . $label . ' vì đang được dữ liệu khác tham chiếu.';
        }

        return 'Lỗi cơ sở dữ liệu. Vui lòng kiểm tra dữ liệu và thử lại.';
    }

    /* ------------------------------------------------------------
     * TIỆN ÍCH KHÁC
     * ------------------------------------------------------------ */

    private function ensureCsrfToken()
    {
        if (empty($_SESSION['ha_tang_csrf'])) {
            $_SESSION['ha_tang_csrf'] = bin2hex(random_bytes(32));
        }
    }

    private function setFlash($type, $message)
    {
        $_SESSION['ha_tang_flash'] = ['type' => $type, 'message' => $message];
    }

    private function pullFlash()
    {
        if (empty($_SESSION['ha_tang_flash'])) {
            return null;
        }

        $flash = $_SESSION['ha_tang_flash'];
        unset($_SESSION['ha_tang_flash']);

        return $flash;
    }

    private function isValidDate($value)
    {
        if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $matches)) {
            return false;
        }

        return checkdate((int) $matches[2], (int) $matches[3], (int) $matches[1]);
    }

    private function redirectAfterPost()
    {
        $tab = isset($_POST['return_tab']) ? (string) $_POST['return_tab'] : 'san_bay';
        if (!in_array($tab, $this->allowedTabs, true)) {
            $tab = 'san_bay';
        }

        $params = [
            'page' => 'ha_tang',
            'quan_tri' => '1',
            'tab' => $tab,
        ];

        $sanBay = isset($_POST['return_san_bay']) ? trim((string) $_POST['return_san_bay']) : '';
        if ($sanBay !== '') {
            $params['san_bay'] = $sanBay;
        }

        $nhaGa = isset($_POST['return_nha_ga']) ? (int) $_POST['return_nha_ga'] : 0;
        if ($nhaGa > 0) {
            $params['nha_ga'] = $nhaGa;
        }

        $this->redirect($params);
    }

    private function redirect($params)
    {
        header('Location: ' . BASE_URL . '/index.php?' . http_build_query($params));
        exit();
    }

    private function getAirportIdOfTerminal($terminalId)
    {
        $stmt = $this->conn->prepare("SELECT id_san_bay FROM nha_ga WHERE id = ?");
        if (!$stmt) return 0;
        $stmt->bind_param('i', $terminalId);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        $stmt->close();
        return $row ? (int) $row['id_san_bay'] : 0;
    }

    private function getAirportIdOfResource($table, $resourceId)
    {
        $allowed = ['cua_ra_may_bay', 'quay_checkin', 'bang_chuyen_hanh_ly'];
        if (!in_array($table, $allowed, true)) return 0;
        $stmt = $this->conn->prepare("SELECT ng.id_san_bay FROM {$table} r JOIN nha_ga ng ON ng.id = r.id_nha_ga WHERE r.id = ?");
        if (!$stmt) return 0;
        $stmt->bind_param('i', $resourceId);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        $stmt->close();
        return $row ? (int) $row['id_san_bay'] : 0;
    }
}
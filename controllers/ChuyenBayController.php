<?php

require_once __DIR__ . '/../config/config.php';

class ChuyenBayController
{
    /** @var mysqli */
    private $db;

    private const STATUSES = [
        'Dang_Mo' => 'Đang mở',
        'Cat_Canh' => 'Cất cánh',
        'Ha_Canh' => 'Hạ cánh',
        'Delay' => 'Trễ chuyến',
        'Huy' => 'Hủy',
    ];

    /** Loại hành động ghi vào nhật ký của từng chuyến bay. */
    public const FLIGHT_LOG_ACTIONS = [
        'Them' => 'Thêm chuyến bay',
        'Tao_Tu_Lich' => 'Tạo tự động từ lịch mùa',
        'Ghi_De_Tu_Lich' => 'Ghi đè từ lịch mùa',
        'Sua' => 'Cập nhật chuyến bay',
        'Huy' => 'Hủy chuyến bay',
        'Tu_Dong' => 'Tự động theo thời gian thực',
        'Huy_Trung_Lich' => 'Hủy do trùng lịch cất cánh',
        'Xac_Nhan_Cat_Canh' => 'Xác nhận đủ điều kiện cất cánh',
        'Tre_Tiep' => 'Chưa đủ điều kiện - trễ tiếp',
    ];

  
    public const OVERDUE_LIMIT_MINUTES = 15;

 
    public const DELAY_CANCEL_LIMIT_MINUTES = 300;

    private const EDITABLE_STATUS_TRANSITIONS = [

        'Dang_Mo' => ['Dang_Mo', 'Delay'],
        'Delay' => ['Delay', 'Dang_Mo'],
        'Huy' => ['Huy'],
    ];

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function handle()
    {
        if (empty($_SESSION['chuyen_bay_csrf'])) {
            $_SESSION['chuyen_bay_csrf'] = bin2hex(random_bytes(32));
        }

        if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->processPost();
        }

        // Đồng bộ trạng thái theo giờ thực tế trước khi hiển thị danh sách.
        $autoSync = $this->syncRealtimeStatuses();

        $filters = [
            'keyword' => trim((string) ($_GET['keyword'] ?? '')),
            'id_san_bay_di' => max(0, (int) ($_GET['id_san_bay_di'] ?? 0)),
            'id_san_bay_den' => max(0, (int) ($_GET['id_san_bay_den'] ?? 0)),
            'date' => trim((string) ($_GET['date'] ?? '')),
            'status' => (string) ($_GET['status'] ?? ''),
        ];
        $statusAliases = [
            'scheduled' => 'Dang_Mo',
            'delayed' => 'Delay',
            'landed' => 'Ha_Canh',
            'cancelled' => 'Huy',
        ];
        if (isset($statusAliases[$filters['status']])) {
            $filters['status'] = $statusAliases[$filters['status']];
        }
        $flash = $this->pullFlash();
        // Báo cho người dùng biết khi hệ thống vừa tự chuyển trạng thái.
        if ($flash === null) {
            $parts = [];
            if ($autoSync['landed'] > 0) {
                $parts[] = $autoSync['landed'] . ' chuyến chuyển sang Hạ cánh';
            }
            if (($autoSync['overdue'] ?? 0) > 0) {
                $parts[] = $autoSync['overdue'] . ' chuyến quá hạn xác nhận đã tự chuyển sang Trễ chuyến';
            }
            if (($autoSync['cancelled'] ?? 0) > 0) {
                $parts[] = $autoSync['cancelled'] . ' chuyến trễ quá 5 giờ đã tự hủy';
            }
            if ($parts) {
                $flash = [
                    'type' => 'info',
                    'message' => 'Hệ thống tự động cập nhật: ' . implode('; ', $parts) . '.',
                ];
            }
        }
        $oldInput = $_SESSION['chuyen_bay_old_input'] ?? [];
        unset($_SESSION['chuyen_bay_old_input']);
        if ($filters['date'] !== '' && !$this->isValidDate($filters['date'])) {
            $filters['date'] = '';
            $flash = ['type' => 'danger', 'message' => 'Ngày tìm kiếm không hợp lệ.'];
        }
        if ($filters['status'] !== '' && !isset(self::STATUSES[$filters['status']])) {
            $filters['status'] = '';
            $flash = ['type' => 'danger', 'message' => 'Trạng thái tìm kiếm không hợp lệ.'];
        }

        $page = max(1, (int) ($_GET['p'] ?? 1));
        $perPage = 10;
        $total = $this->countFlights($filters);
        $totalPages = max(1, (int) ceil($total / $perPage));
        $page = min($page, $totalPages);
        $editId = max(0, (int) ($_GET['edit'] ?? 0));
        $editing = $editId > 0 ? $this->getFlight($editId) : null;
        if ($editId > 0 && $editing === null) {
            $flash = ['type' => 'danger', 'message' => 'Không tìm thấy chuyến bay cần sửa.'];
        }
        $cancelId = max(0, (int) ($_GET['cancel'] ?? 0));
        $canceling = $cancelId > 0 ? $this->getFlight($cancelId) : null;
        if ($cancelId > 0 && $canceling === null) {
            $flash = ['type' => 'danger', 'message' => 'Không tìm thấy chuyến bay cần hủy.'];
        }

        $pendingToday = substr($this->getCurrentDateTime(), 0, 10);
        $pendingDate = trim((string) ($_GET['pending_date'] ?? ''));
        if (!$this->isValidDate($pendingDate)) {
            $pendingDate = $pendingToday;
        }
        $pendingDepartures = $this->getPendingDepartures($pendingDate);
        $serverNow = $this->getCurrentDateTime();
        $serverTs = strtotime($serverNow);
        foreach ($pendingDepartures as &$pendingRow) {
            // Số phút đã quá giờ cất cánh (dùng để tô đỏ + hiện badge "Quá hạn").
            $pendingRow['overdue_minutes'] = max(
                0,
                (int) floor(($serverTs - strtotime((string) $pendingRow['gio_du_kien'])) / 60)
            );
            $pendingRow['ly_do_tre'] = '';
            $pendingRow['delay_overdue_minutes'] = 0;
            $pendingRow['tong_delay_phut'] = 0;
            $totalDelayMinutes = 0;
            $history = $this->getDelayHistory((int) $pendingRow['id']);
            if ($history) {
                $latest = $history[count($history) - 1];
                if (($pendingRow['trang_thai_tong'] ?? '') === 'Delay') {
                    $pendingRow['ly_do_tre'] = (string) ($latest['ly_do'] ?? '');
                }
                foreach ($history as $entry) {
                    $totalDelayMinutes += (int) ($entry['so_phut_lui'] ?? $entry['so_phut'] ?? 0);
                }
            }
            $pendingRow['tong_delay_phut'] = $totalDelayMinutes;
            if (($pendingRow['trang_thai_tong'] ?? '') === 'Delay') {
                // Số phút đã trễ kể từ khi hết thời gian delay.
                $pendingRow['delay_overdue_minutes'] = max(
                    0,
                    (int) floor(($serverTs - strtotime((string) $pendingRow['gio_du_kien'])) / 60)
                );
            }
            // Giờ cất cánh gốc (trước delay) để hiển thị ở cột "Giờ cất cánh".
            $originalTs = strtotime((string) $pendingRow['gio_du_kien']);
            if (($pendingRow['trang_thai_tong'] ?? '') === 'Delay' && $totalDelayMinutes > 0) {
                $originalTs = $originalTs - ($totalDelayMinutes * 60);
            }
            $pendingRow['gio_cat_canh_goc'] = $originalTs !== false
                ? date('Y-m-d H:i:s', $originalTs)
                : $pendingRow['gio_du_kien'];
            $pendingRow['tong_delay_phut'] = $totalDelayMinutes;
            // Tổng thời gian trễ dự kiến = delay đã ghi nhận + số phút đã quá hạn chờ cất cánh.
            $pendingRow['tong_tre_phut'] = $totalDelayMinutes + (int) ($pendingRow['delay_overdue_minutes'] ?? 0);
            // Giờ hạ cánh dự kiến (đã gồm delay) và giờ hạ cánh gốc (trước delay).
            $pendingRow['gio_ha_canh_du_kien'] = $pendingRow['gio_thuc_te'];
            $pendingRow['gio_ha_canh_goc'] = !empty($pendingRow['gio_thuc_te']) && $totalDelayMinutes > 0
                ? date('Y-m-d H:i:s', strtotime((string) $pendingRow['gio_thuc_te']) - ($totalDelayMinutes * 60))
                : $pendingRow['gio_thuc_te'];
        }
        unset($pendingRow);
        // Chuyến đã đến giờ bay: cả chuyến Đang mở và chuyến Trễ chuyến đã hết thời gian delay.
        $dueDepartures = array_values(array_filter($pendingDepartures, static function ($row) {
            return ($row['trang_thai_tong'] ?? '') === 'Dang_Mo';
        }));
        // Mọi chuyến Trễ chuyến của ngày đang xem (đã tới giờ hoặc chưa tới giờ cất cánh sau delay)
        // đều nằm ở mục "Các chuyến bay trễ chuyến".
        $delayedDueDepartures = array_values(array_filter($pendingDepartures, static function ($row) {
            return ($row['trang_thai_tong'] ?? '') === 'Delay';
        }));
        // Chuyến đang Trễ chuyến nhưng chưa hết thời gian delay (gio_du_kien còn ở tương lai) vẫn nằm mục trễ.
        // Gộp thêm các chuyến Trễ chuyến chưa tới giờ cất cánh sau delay.
        foreach ($this->getFutureDelayedDepartures($pendingDate) as $futureDelayedRow) {
            $delayedDueDepartures[] = $futureDelayedRow;
        }
        usort($delayedDueDepartures, static function ($a, $b) {
            return strcmp((string) ($a['gio_du_kien'] ?? ''), (string) ($b['gio_du_kien'] ?? ''));
        });
        foreach ($delayedDueDepartures as &$delayedRow) {
            $history = $this->getDelayHistory((int) $delayedRow['id']);
            $latest = $history ? $history[count($history) - 1] : null;
            $delayedRow['ly_do_tre'] = (string) ($latest['ly_do'] ?? '');
            $totalDelayMinutes = 0;
            foreach ($history as $entry) {
                $totalDelayMinutes += (int) ($entry['so_phut_lui'] ?? $entry['so_phut'] ?? 0);
            }
            $delayedRow['tong_delay_phut'] = $totalDelayMinutes;
            // Quá hạn chờ xác nhận: đã qua giờ cất cánh dự kiến bao nhiêu phút.
            $overdueRow = max(
                0,
                (int) floor((strtotime($serverNow) - strtotime((string) $delayedRow['gio_du_kien'])) / 60)
            );
            $delayedRow['delay_overdue_minutes'] = $overdueRow;
            $delayedRow['tong_tre_phut'] = $totalDelayMinutes + $overdueRow;
            // Giờ cất cánh / hạ cánh dự kiến đã cộng thêm thời gian quá hạn.
            $delayedRow['gio_cat_canh_du_kien'] = date(
                'Y-m-d H:i:s',
                strtotime((string) $delayedRow['gio_du_kien']) + ($overdueRow * 60)
            );
            $delayedRow['gio_ha_canh_du_kien'] = !empty($delayedRow['gio_thuc_te'])
                ? date('Y-m-d H:i:s', strtotime((string) $delayedRow['gio_thuc_te']) + ($overdueRow * 60))
                : $delayedRow['gio_thuc_te'];
            $delayedRow['gio_ha_canh_goc'] = !empty($delayedRow['gio_thuc_te']) && $totalDelayMinutes > 0
                ? date('Y-m-d H:i:s', strtotime((string) $delayedRow['gio_thuc_te']) - ($totalDelayMinutes * 60))
                : $delayedRow['gio_thuc_te'];
            $originalTs = strtotime((string) $delayedRow['gio_du_kien']);
            if ($totalDelayMinutes > 0) {
                $originalTs = $originalTs - ($totalDelayMinutes * 60);
            }
            $delayedRow['gio_cat_canh_goc'] = $originalTs !== false
                ? date('Y-m-d H:i:s', $originalTs)
                : $delayedRow['gio_du_kien'];
        }
        unset($delayedRow);

        return [
            'flights' => $this->getFlights($filters, $perPage, ($page - 1) * $perPage),
            'filters' => $filters,
            'page' => $page,
            'perPage' => $perPage,
            'total' => $total,
            'totalPages' => $totalPages,
            'editing' => $editing,
            'editingSummary' => $editing ? $this->getFlightSummary($editId) : null,
            'editLogs' => $editing ? $this->getFlightLogs($editId) : [],
            'canceling' => $canceling,
            'cancelingSummary' => $canceling ? $this->getFlightSummary($cancelId) : null,
            'canCancel' => $canceling !== null
                && in_array($canceling['trang_thai_tong'], ['Dang_Mo', 'Delay'], true),
            'airlines' => $this->getOptions('SELECT id, ma_iata, ten_hang AS label FROM hang_hang_khong ORDER BY ten_hang'),
            'airports' => $this->getOptions('SELECT id, ma_iata, ten_san_bay AS label, phan_cap FROM san_bay ORDER BY ma_iata'),
            'aircraftTypes' => $this->getOptions(
                "SELECT l.id, l.ma_loai AS label, l.tai_trong_cat_canh_mtow, l.suc_chua_hanh_khach,
                        GROUP_CONCAT(DISTINCT cb.id_hang_hang_khong) AS airline_ids
                 FROM loai_tau_bay l
                 LEFT JOIN chuyen_bay_ngay cb ON cb.id_loai_tau_bay = l.id
                 GROUP BY l.id
                 ORDER BY l.ma_loai"
            ),
            'gates' => $this->getOptions(
                "SELECT c.id, ng.id_san_bay, ng.loai_nha_ga AS terminal_type,
                        CONCAT(sb.ma_iata, ' - Cửa ', c.so_cua,
                               IF(c.khu_vuc IS NULL OR c.khu_vuc = '', '', CONCAT(' (', c.khu_vuc, ')'))) AS label
                 FROM cua_ra_may_bay c JOIN nha_ga ng ON ng.id = c.id_nha_ga
                 JOIN san_bay sb ON sb.id = ng.id_san_bay WHERE c.trang_thai = 'Hoat_Dong'
                 ORDER BY sb.ma_iata, c.so_cua"
            ),
            'checkins' => $this->getOptions(
                "SELECT q.id, ng.id_san_bay, ng.loai_nha_ga AS terminal_type,
                        CONCAT(sb.ma_iata, ' - Quầy ', q.so_quay,
                               IF(q.khu_vuc IS NULL OR q.khu_vuc = '', '', CONCAT(' (', q.khu_vuc, ')'))) AS label
                 FROM quay_checkin q JOIN nha_ga ng ON ng.id = q.id_nha_ga
                 JOIN san_bay sb ON sb.id = ng.id_san_bay WHERE q.trang_thai = 'Hoat_Dong'
                 ORDER BY sb.ma_iata, q.so_quay"
            ),
            'belts' => $this->getOptions(
                "SELECT b.id, ng.id_san_bay, ng.loai_nha_ga AS terminal_type,
                        CONCAT(sb.ma_iata, ' - Băng chuyền ', b.so_bang_chuyen,
                               IF(b.khu_vuc IS NULL OR b.khu_vuc = '', '', CONCAT(' (', b.khu_vuc, ')'))) AS label
                 FROM bang_chuyen_hanh_ly b JOIN nha_ga ng ON ng.id = b.id_nha_ga
                 JOIN san_bay sb ON sb.id = ng.id_san_bay WHERE b.trang_thai = 'Hoat_Dong'
                 ORDER BY sb.ma_iata, b.so_bang_chuyen"
            ),
            'statuses' => self::STATUSES,
            'editableStatusTransitions' => self::EDITABLE_STATUS_TRANSITIONS,
            'flightLogActions' => self::FLIGHT_LOG_ACTIONS,
            'serverNow' => $serverNow,
            'overdueLimit' => self::OVERDUE_LIMIT_MINUTES,
            'cancelDelayLimitHours' => (int) (self::DELAY_CANCEL_LIMIT_MINUTES / 60),
            'pendingDepartures' => $pendingDepartures,
            'pendingDate' => $pendingDate,
            'pendingToday' => $pendingToday,
            'dueDepartures' => $dueDepartures,
            'delayedDueDepartures' => $delayedDueDepartures,
            'csrf' => $_SESSION['chuyen_bay_csrf'],
            'flash' => $flash,
            'oldInput' => $oldInput,
        ];
    }

    private function processPost()
    {
        // Form được mở trong popup (iframe) ở trang danh sách hay không.
        $popup = !empty($_POST['popup']);
        $action = (string) ($_POST['action'] ?? '');
        $flightId = (int) ($_POST['id'] ?? 0);
        $formPage = $action === 'update'
            ? 'chuyen_bay_sua'
            : (in_array($action, ['cancel', 'delete'], true) ? 'chuyen_bay_huy' : 'chuyen_bay_them');
        $formParams = $action === 'delete' ? ['delete' => 1] : [];

        if (!hash_equals($_SESSION['chuyen_bay_csrf'] ?? '', (string) ($_POST['csrf'] ?? ''))) {
            $this->setFlash('danger', 'Phiên làm việc không hợp lệ. Vui lòng tải lại trang.');
            $this->redirect($popup ? $formPage : 'chuyen_bay', $flightId, $popup, $formParams);
        }

        if (!co_quyen_dieu_phoi_bay()) {
            $this->setFlash('danger', 'Chỉ nhân viên điều hành bay hoặc ban quản lý mới có quyền chỉnh sửa, thêm hoặc hủy chuyến bay.');
            $this->redirect('chuyen_bay');
        }

        $success = false;
        try {
            if ($action === 'create' || $action === 'update') {
                $this->saveFlight($action === 'update');
                $success = true;
            } elseif ($action === 'cancel') {
                $this->cancelFlight($flightId, (string) ($_POST['ghi_chu'] ?? ''));
                $success = true;
            } elseif ($action === 'delete') {
                $this->deleteFlight($flightId);
                $success = true;
            } else {
                $this->setFlash('danger', 'Thao tác không hợp lệ.');
            }
        } catch (mysqli_sql_exception $exception) {
            error_log('Lỗi thao tác chuyến bay: ' . $exception->getMessage());
            $this->setFlash('danger', 'Không thể lưu thay đổi chuyến bay. Vui lòng kiểm tra dữ liệu và thử lại.');
        } catch (RuntimeException $exception) {
            $this->setFlash('danger', $exception->getMessage());
        }

        if (!$success && $action === 'create') {
            $fields = [
                'id_hang_hang_khong', 'id_loai_tau_bay', 'id_san_bay_di',
                'id_san_bay_den', 'gio_du_kien', 'gio_thuc_te', 'id_cua_ra_may_bay',
                'id_quay_checkin', 'id_bang_chuyen', 'trang_thai_tong',
            ];
            $_SESSION['chuyen_bay_old_input'] = array_intersect_key($_POST, array_flip($fields));
        }

        if ($success) {
            // Thành công trong popup: quay lại trang nhỏ báo trang cha tải lại danh sách.
            if ($popup) {
                $this->redirect($formPage, $flightId, true, ['saved' => 1]);
            }
            $this->redirect('chuyen_bay');
        }

        // Thất bại: giữ nguyên form (trong popup thì mở lại đúng popup) để hiển thị lỗi.
        $this->redirect(
            $popup ? $formPage : ($action === 'create' ? 'chuyen_bay_them' : $formPage),
            $flightId,
            $popup,
            $formParams
        );
    }

    private function saveFlight($update)
    {
        $id = (int) ($_POST['id'] ?? 0);
        $flightNumber = strtoupper(trim((string) ($_POST['so_hieu'] ?? '')));
        $airlineId = (int) ($_POST['id_hang_hang_khong'] ?? 0);
        $aircraftId = (int) ($_POST['id_loai_tau_bay'] ?? 0);
        $originId = (int) ($_POST['id_san_bay_di'] ?? 0);
        $destinationId = (int) ($_POST['id_san_bay_den'] ?? 0);
        $gateId = $this->optionalId($_POST['id_cua_ra_may_bay'] ?? '');
        $checkinId = $this->optionalId($_POST['id_quay_checkin'] ?? '');
        $beltId = $this->optionalId($_POST['id_bang_chuyen'] ?? '');
        $planned = $this->normalizeDateTime((string) ($_POST['gio_du_kien'] ?? ''));
        $actualInput = trim((string) ($_POST['gio_thuc_te'] ?? ''));
        $actual = $actualInput === '' ? null : $this->normalizeDateTime($actualInput);
        $passengerInput = trim((string) ($_POST['luong_hanh_khach'] ?? ''));
        $passengers = $update
            ? ($passengerInput === '' ? 0 : filter_var($passengerInput, FILTER_VALIDATE_INT))
            : null;
        // Ô "Hành lý" trên form có thể gửi tấn (luong_hanh_ly_tan) hoặc kg (luong_hanh_ly_kg).
        $cargoField = array_key_exists('luong_hanh_ly_tan', $_POST)
            ? 'luong_hanh_ly_tan'
            : (array_key_exists('luong_hanh_ly_kg', $_POST) ? 'luong_hanh_ly_kg' : null);
        $cargoInKilograms = $cargoField === 'luong_hanh_ly_kg';
        $cargoInputWasEmpty = trim((string) ($_POST[$cargoField ?? 'luong_hang_hoa_tan'] ?? '')) === '';
        $cargoInput = $update
            ? trim((string) ($_POST[$cargoField ?? 'luong_hang_hoa_tan'] ?? ''))
            : '';
        $cargoInput = $cargoInput === '' ? '0' : $cargoInput;
        $status = $update ? (string) ($_POST['trang_thai_tong'] ?? '') : 'Dang_Mo';
        $statusReason = trim((string) ($_POST['ghi_chu'] ?? ''));
        $delayMinutesInput = trim((string) ($_POST['thoi_gian_tre_phut'] ?? ''));

        if ($update && $id <= 0) {
            throw new \RuntimeException('Chuyến bay cần sửa không hợp lệ.');
        }
        if ($update && !preg_match('/^[A-Z0-9-]{2,20}$/', $flightNumber)) {
            throw new \RuntimeException('Mã chuyến bay chỉ gồm 2-20 ký tự chữ in hoa, số hoặc dấu gạch ngang.');
        }
        if ($airlineId <= 0) {
            throw new \RuntimeException('Vui lòng chọn hãng hàng không.');
        }
        if ($aircraftId <= 0) {
            throw new \RuntimeException('Vui lòng chọn loại tàu bay.');
        }
        if ($originId <= 0 || $destinationId <= 0) {
            throw new \RuntimeException('Vui lòng chọn sân bay đi và sân bay đến.');
        }
        if ($originId === $destinationId) {
            throw new \RuntimeException('Sân bay đi và sân bay đến phải khác nhau.');
        }
        if ($planned === null) {
            throw new \RuntimeException('Vui lòng nhập giờ cất cánh hợp lệ.');
        }
        if ($actualInput === '') {
            throw new \RuntimeException('Vui lòng nhập giờ dự kiến hạ cánh.');
        }
        if ($actual === null) {
            throw new \RuntimeException('Giờ dự kiến hạ cánh không hợp lệ. Vui lòng chọn lại.');
        }
        if ($actual <= $planned) {
            throw new \RuntimeException('Giờ cất cánh phải nhỏ hơn giờ dự kiến hạ cánh. Vui lòng kiểm tra lại thời gian.');
        }
        // Trùng chuyến = cùng số hiệu + tuyến + ngày cất cánh (không xét giờ), bỏ qua chính chuyến đang sửa.
        $plannedDate = substr((string) $planned, 0, 10);
        if ($update && $this->flightExistsOnDate($flightNumber, $originId, $destinationId, $plannedDate, $id)) {
            throw new \RuntimeException(
                'Chuyến bay ' . $flightNumber . ' theo tuyến này ngày ' . date('d/m/Y', strtotime($plannedDate))
                . ' đã tồn tại. Vui lòng chọn ngày hoặc tuyến khác.'
            );
        }
        // Cùng sân bay đi + cùng ngày: giờ cất cánh phải cách nhau ít nhất 10 phút.
        $conflict = $this->findDepartureConflict($originId, $planned, $update ? $id : 0);
        if ($conflict !== null) {
            throw new \RuntimeException(
                'Sân bay ' . $conflict['ma_iata'] . ' đã có chuyến ' . $conflict['so_hieu']
                . ' cất cánh lúc ' . date('H:i', strtotime($conflict['gio_du_kien']))
                . ' ngày ' . date('d/m/Y', strtotime($conflict['gio_du_kien']))
                . '. Hai chuyến cùng sân bay phải cách nhau ít nhất 10 phút.'
            );
        }
        if ($update && ($passengerInput === '' || $cargoInputWasEmpty)) {
            throw new \RuntimeException('Vui lòng nhập số hành khách và khối lượng hành lý.');
        }
        if ($update && ($passengers === false || $passengers < 0)) {
            throw new \RuntimeException('Số hành khách phải là số nguyên không âm.');
        }
        if ($update && (!is_numeric($cargoInput) || (float) $cargoInput < 0)) {
            throw new \RuntimeException('Tải trọng hàng hóa phải là số không âm.');
        }
        $aircraft = $this->getAircraftCapacity($aircraftId);
        if ($aircraft === null) {
            throw new \RuntimeException('Loại tàu bay đã chọn không tồn tại.');
        }
        if (!$update) {
            $passengers = (int) $aircraft['suc_chua_hanh_khach'];
            $cargoInput = (string) $aircraft['tai_trong_cat_canh_mtow'];
            $cargoInKilograms = false;
        }
        if ($passengers > (int) $aircraft['suc_chua_hanh_khach']) {
            throw new \RuntimeException(
                'Số hành khách vượt sức chứa của loại tàu bay (' . (int) $aircraft['suc_chua_hanh_khach'] . ' khách).'
            );
        }
        $cargo = (float) $cargoInput / ($cargoInKilograms ? 1000 : 1);
        $maxBaggageKg = (float) $aircraft['tai_trong_cat_canh_mtow'] * 1000;
        if ($cargo * 1000 > $maxBaggageKg) {
            throw new \RuntimeException(
                'Khối lượng hành lý vượt mức loại tàu bay (' . $maxBaggageKg . ' kg theo tải trọng cất cánh tối đa).'
            );
        }
        if (!isset(self::STATUSES[$status])) {
            throw new \RuntimeException('Vui lòng chọn trạng thái chuyến bay hợp lệ.');
        }
        if ($gateId === null || $checkinId === null || $beltId === null) {
            throw new \RuntimeException('Vui lòng chọn cửa ra máy bay, quầy check-in và băng chuyền hành lý.');
        }
        $this->validateResourceAirport($gateId, 'cua_ra_may_bay', $originId);
        $this->validateResourceAirport($checkinId, 'quay_checkin', $originId);
        $this->validateResourceAirport($beltId, 'bang_chuyen_hanh_ly', $originId);

        // Quầy check-in mở trước 2-3 tiếng, đóng trước 40 phút: không gán cùng một quầy cho hai chuyến
        // có khung làm thủ tục trùng nhau (khung rộng nhất 3 tiếng trước -> 40 phút trước giờ cất cánh).
        if ($checkinId !== null) {
            $checkinConflict = $this->findCheckinConflict($checkinId, $planned, $update ? $id : 0);
            if ($checkinConflict !== null) {
                throw new \RuntimeException(
                    'Quầy ' . $checkinConflict['so_quay'] . ' (' . $checkinConflict['ma_iata'] . ') đang được chuyến '
                    . $checkinConflict['so_hieu'] . ' cất cánh lúc '
                    . date('H:i d/m/Y', strtotime($checkinConflict['gio_du_kien']))
                    . ' sử dụng. Quầy check-in mở trước 2-3 tiếng và đóng trước 40 phút, '
                    . 'nên hai chuyến dùng chung quầy phải cách nhau ít nhất 2 tiếng 20 phút.'
                );
            }
        }

        $this->db->begin_transaction();
        try {
            if ($update) {
                if (empty($_SESSION['user_id'])) {
                    throw new \RuntimeException('Vui lòng đăng nhập để lưu chỉnh sửa và ghi nhật ký người thay đổi.');
                }

                $stmt = $this->db->prepare('SELECT * FROM chuyen_bay_ngay WHERE id = ? FOR UPDATE');
                $stmt->bind_param('i', $id);
                $stmt->execute();
                $oldFlight = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                if (!$oldFlight) {
                    throw new \RuntimeException('Không tìm thấy chuyến bay cần cập nhật.');
                }
                $oldStatus = $oldFlight['trang_thai_tong'];
                if (in_array($oldStatus, ['Cat_Canh', 'Ha_Canh'], true)) {
                    throw new \RuntimeException('Không thể chỉnh sửa chuyến bay đã cất cánh hoặc hạ cánh.');
                }
                if (!in_array($status, self::EDITABLE_STATUS_TRANSITIONS[$oldStatus] ?? [], true)) {
                    throw new \RuntimeException('Trạng thái mới không hợp lệ với trạng thái hiện tại của chuyến bay.');
                }
                if ($status === 'Huy' && $oldFlight['trang_thai_tong'] !== 'Huy') {
                    throw new \RuntimeException('Không thể hủy chuyến tại màn hình sửa. Vui lòng dùng chức năng Hủy chuyến.');
                }
                $delayMinutes = null;
                if ($status !== $oldFlight['trang_thai_tong'] && $status === 'Delay') {
                    if (substr((string) $planned, 0, 10) > substr($this->getCurrentDateTime(), 0, 10)) {
                        throw new \RuntimeException('Không thể chuyển sang Trễ chuyến vì chuyến bay chưa tới ngày bay.');
                    }
                    if (!$this->isValidReason($statusReason)) {
                        throw new \RuntimeException('Vui lòng nhập lý do trễ chuyến (tối đa 255 ký tự).');
                    }
                    $delayMinutes = filter_var($delayMinutesInput, FILTER_VALIDATE_INT);
                    if ($delayMinutes === false || $delayMinutes < 1 || $delayMinutes > 1440) {
                        throw new \RuntimeException('Vui lòng nhập khoảng thời gian dự kiến trễ (từ 1 đến 1440 phút).');
                    }
                    $delayMinutes = (int) $delayMinutes;
                }
                $changes = $this->getFlightChanges($oldFlight, [
                    'so_hieu' => $flightNumber,
                    'id_hang_hang_khong' => $airlineId,
                    'id_loai_tau_bay' => $aircraftId,
                    'id_san_bay_di' => $originId,
                    'id_san_bay_den' => $destinationId,
                    'id_cua_ra_may_bay' => $gateId,
                    'id_quay_checkin' => $checkinId,
                    'id_bang_chuyen' => $beltId,
                    'gio_du_kien' => $planned,
                    'gio_thuc_te' => $actual,
                    'luong_hanh_khach' => $passengers,
                    'luong_hang_hoa_tan' => $cargo,
                    'trang_thai_tong' => $status,
                ]);
                if ($status !== $oldFlight['trang_thai_tong'] && $status === 'Delay') {
                    $changes[] = 'Lý do trễ chuyến: ' . $statusReason;
                    // Từ 60 phút trở lên hiện thêm tổng số phút: "1 giờ 30 phút (90 phút)".
                    $delayText = $this->formatDurationMinutes($delayMinutes);
                    if ((int) $delayMinutes >= 60) {
                        $delayText .= ' (' . (int) $delayMinutes . ' phút)';
                    }
                    $overdueMinutes = max(0, (int) floor((strtotime($this->getCurrentDateTime()) - strtotime((string) $planned)) / 60));
                    $totalShift = $overdueMinutes + (int) $delayMinutes;
                    $changes[] = 'Khoảng thời gian dự kiến trễ: ' . $delayText
                        . ' (tổng lùi ' . $totalShift . ' phút).';
                }

                $stmt = $this->db->prepare(
                    'UPDATE chuyen_bay_ngay SET so_hieu = ?, id_hang_hang_khong = ?,
                     id_loai_tau_bay = ?, id_san_bay_di = ?, id_san_bay_den = ?, id_cua_ra_may_bay = ?,
                     id_quay_checkin = ?, id_bang_chuyen = ?, gio_du_kien = ?, gio_thuc_te = ?,
                     luong_hanh_khach = ?, luong_hang_hoa_tan = ?, trang_thai_tong = ? WHERE id = ?'
                );
                $stmt->bind_param(
                    'siiiiiiissidsi',
                    $flightNumber, $airlineId, $aircraftId, $originId, $destinationId,
                    $gateId, $checkinId, $beltId, $planned, $actual, $passengers, $cargo, $status, $id
                );
            } else {
                $flightNumber = $this->generateFlightNumber($airlineId);
                $stmt = $this->db->prepare(
                    'INSERT INTO chuyen_bay_ngay
                     (so_hieu, id_hang_hang_khong, id_loai_tau_bay, id_san_bay_di,
                      id_san_bay_den, id_cua_ra_may_bay, id_quay_checkin, id_bang_chuyen,
                      gio_du_kien, gio_thuc_te, luong_hanh_khach, luong_hang_hoa_tan, trang_thai_tong)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
                );
                $stmt->bind_param(
                    'siiiiiiissids',
                    $flightNumber, $airlineId, $aircraftId, $originId, $destinationId,
                    $gateId, $checkinId, $beltId, $planned, $actual, $passengers, $cargo, $status
                );
            }
            $stmt->execute();
            $stmt->close();

            if ($update) {
                if ($changes) {
                    $this->logFlightAction(
                        $id,
                        'Sua',
                        $oldFlight['trang_thai_tong'],
                        $status,
                        implode("\n", $changes),
                        (int) $_SESSION['user_id']
                    );
                }
            } else {
                // Mỗi chuyến thêm mới đều có một dòng nhật ký riêng.
                $newFlightId = (int) $this->db->insert_id;
                $this->logFlightAction(
                    $newFlightId,
                    'Them',
                    null,
                    $status,
                    'Thêm chuyến bay mới ' . $flightNumber . '.',
                    $_SESSION['user_id'] ?? null
                );
            }

            $this->db->commit();
            $this->setFlash(
                'success',
                $update ? 'Đã cập nhật chuyến bay.' : 'Đã thêm chuyến bay ' . $flightNumber . '.'
            );
        } catch (Throwable $exception) {
            $this->db->rollback();
            throw $exception;
        }
    }

    private function cancelFlight($id, $reason)
    {
        if ($id <= 0) {
            throw new \RuntimeException('Chuyến bay cần hủy không hợp lệ.');
        }

        $reason = trim($reason);
        if (!$this->isValidReason($reason)) {
            throw new \RuntimeException('Vui lòng nhập lý do hủy chuyến (tối đa 255 ký tự).');
        }
        if (empty($_SESSION['user_id'])) {
            throw new \RuntimeException('Bạn cần đăng nhập để hủy chuyến bay và ghi nhật ký điều hành.');
        }

        $this->db->begin_transaction();
        try {
            $stmt = $this->db->prepare('SELECT trang_thai_tong FROM chuyen_bay_ngay WHERE id = ? FOR UPDATE');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $flight = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if (!$flight) {
                throw new \RuntimeException('Không tìm thấy chuyến bay cần hủy.');
            }
            if ($flight['trang_thai_tong'] === 'Huy') {
                throw new \RuntimeException('Chuyến bay này đã được hủy trước đó.');
            }
            if (!in_array($flight['trang_thai_tong'], ['Dang_Mo', 'Delay'], true)) {
                throw new \RuntimeException('Chỉ được hủy chuyến bay đang mở hoặc trễ chuyến.');
            }

            $stmt = $this->db->prepare("UPDATE chuyen_bay_ngay SET trang_thai_tong = 'Huy' WHERE id = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $stmt->close();

            $userId = (int) $_SESSION['user_id'];
            $oldStatus = $flight['trang_thai_tong'];
            $this->logFlightAction(
                $id,
                'Huy',
                $oldStatus,
                'Huy',
                'Hủy chuyến. Lý do: ' . $reason,
                $userId
            );

            $this->db->commit();
            $this->setFlash('success', 'Đã hủy chuyến bay và lưu lý do vào nhật ký. Dữ liệu chuyến bay được giữ lại.');
        } catch (Throwable $exception) {
            $this->db->rollback();
            throw $exception;
        }
    }

    private function deleteFlight(int $id): void
    {
        if ($id <= 0) {
            throw new \RuntimeException('Chuyến bay cần xóa không hợp lệ.');
        }
        if (empty($_SESSION['user_id'])) {
            throw new \RuntimeException('Bạn cần đăng nhập để xóa chuyến bay.');
        }

        $this->db->begin_transaction();
        try {
            $stmt = $this->db->prepare(
                'SELECT so_hieu, gio_du_kien, trang_thai_tong
                 FROM chuyen_bay_ngay WHERE id = ? FOR UPDATE'
            );
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $flight = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$flight) {
                throw new \RuntimeException('Không tìm thấy chuyến bay cần xóa.');
            }
            if (strtotime((string) $flight['gio_du_kien']) <= strtotime($this->getCurrentDateTime())) {
                throw new \RuntimeException('Chỉ được xóa chuyến bay chưa tới giờ cất cánh.');
            }
            if (!in_array($flight['trang_thai_tong'], ['Dang_Mo', 'Delay', 'Huy'], true)) {
                throw new \RuntimeException('Không thể xóa chuyến bay đã cất cánh hoặc hạ cánh.');
            }

            $stmt = $this->db->prepare('DELETE FROM chuyen_bay_ngay WHERE id = ?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $stmt->close();

            $this->db->commit();
            $this->setFlash(
                'success',
                'Đã xóa vĩnh viễn chuyến bay ' . $flight['so_hieu'] . ' cùng dữ liệu liên quan.'
            );
        } catch (Throwable $exception) {
            $this->db->rollback();
            throw $exception;
        }
    }

    private function getFlights($filters, $limit, $offset)
    {
        $where = $this->filterSql();
        $stmt = $this->db->prepare(
            "SELECT cb.*, h.ten_hang, sb1.ma_iata AS san_bay_di, sb2.ma_iata AS san_bay_den,
                    sb1.ten_ngan AS ten_ngan_di, sb2.ten_ngan AS ten_ngan_den,
                    sb1.vi_tri AS vi_tri_di, sb2.vi_tri AS vi_tri_den,
                    lt.ma_loai
             FROM chuyen_bay_ngay cb
             JOIN hang_hang_khong h ON h.id = cb.id_hang_hang_khong
             JOIN san_bay sb1 ON sb1.id = cb.id_san_bay_di
             JOIN san_bay sb2 ON sb2.id = cb.id_san_bay_den
             JOIN loai_tau_bay lt ON lt.id = cb.id_loai_tau_bay
             JOIN (
                 -- Bỏ chuyến trùng thật: cùng mã + tuyến + ngày cất cánh (không xét giờ).
                 SELECT MIN(id) AS keep_id
                 FROM chuyen_bay_ngay
                 GROUP BY so_hieu, id_san_bay_di, id_san_bay_den, DATE(gio_du_kien)
             ) dedup ON dedup.keep_id = cb.id
             WHERE $where
             ORDER BY (DATE(cb.gio_du_kien) = CURDATE()) DESC, cb.id DESC LIMIT ? OFFSET ?"
        );
        $originId = $filters['id_san_bay_di'];
        $destinationId = $filters['id_san_bay_den'];
        $keyword = $filters['keyword'];
        $keywordLike = '%' . $keyword . '%';
        $date = $filters['date'];
        $status = $filters['status'];
        $stmt->bind_param(
            'iiiisssssssssssssii',
            $originId,
            $originId,
            $destinationId,
            $destinationId,
            $date,
            $date,
            $status,
            $status,
            $keyword,
            $keywordLike,
            $keywordLike,
            $keywordLike,
            $keywordLike,
            $keywordLike,
            $keywordLike,
            $keywordLike,
            $keywordLike,
            $limit,
            $offset
        );
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        foreach ($rows as &$row) {
            $history = $this->getDelayHistory((int) $row['id']);
            $totalDelayMinutes = 0;
            $hasShiftedSchedule = false;
            foreach ($history as $entry) {
                $totalDelayMinutes += (int) ($entry['so_phut_lui'] ?? $entry['so_phut'] ?? 0);
                if (strpos((string) ($entry['ghi_chu'] ?? ''), 'Giờ cất cánh mới') !== false
                    || strpos((string) ($entry['ghi_chu'] ?? ''), 'tổng lùi') !== false) {
                    $hasShiftedSchedule = true;
                }
            }
            $row['tong_delay_phut'] = $totalDelayMinutes;
            // Giờ hạ cánh hiển thị = gio_thuc_te (đã bao gồm toàn bộ delay do mọi lần lùi giờ
            // đều dời cả giờ cất cánh lẫn giờ hạ cánh), cộng thêm thời gian đã quá hạn chờ xác nhận.
            $overdueLive = ($row['trang_thai_tong'] === 'Delay')
                ? max(0, (int) floor((strtotime($this->getCurrentDateTime()) - strtotime((string) $row['gio_du_kien'])) / 60))
                : 0;
            $row['gio_ha_canh_hien_thi'] = !empty($row['gio_thuc_te'])
                ? date('Y-m-d H:i:s', strtotime((string) $row['gio_thuc_te']) + ($overdueLive * 60))
                : $row['gio_thuc_te'];
            $row['gio_ha_canh_goc'] = !empty($row['gio_thuc_te'])
                ? date('Y-m-d H:i:s', strtotime((string) $row['gio_thuc_te']) - ($totalDelayMinutes * 60))
                : $row['gio_thuc_te'];
            // Giờ cất cánh gốc (trước delay) chỉ khác khi chuyến đang trễ.
            $row['gio_cat_canh_goc'] = ($row['trang_thai_tong'] === 'Delay' && $totalDelayMinutes > 0)
                ? date('Y-m-d H:i:s', strtotime((string) $row['gio_du_kien']) - ($totalDelayMinutes * 60))
                : $row['gio_du_kien'];
        }
        unset($row);

        return $rows;
    }

    private function countFlights($filters)
    {
        $where = $this->filterSql();
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) AS total FROM chuyen_bay_ngay cb
             JOIN hang_hang_khong h ON h.id = cb.id_hang_hang_khong
             JOIN san_bay sb1 ON sb1.id = cb.id_san_bay_di
             JOIN san_bay sb2 ON sb2.id = cb.id_san_bay_den
             JOIN (
                 SELECT MIN(id) AS keep_id
                 FROM chuyen_bay_ngay
                 GROUP BY so_hieu, id_san_bay_di, id_san_bay_den, DATE(gio_du_kien)
             ) dedup ON dedup.keep_id = cb.id
             WHERE $where"
        );
        $originId = $filters['id_san_bay_di'];
        $destinationId = $filters['id_san_bay_den'];
        $keyword = $filters['keyword'];
        $keywordLike = '%' . $keyword . '%';
        $date = $filters['date'];
        $status = $filters['status'];
        $stmt->bind_param(
            'iiiisssssssssssss',
            $originId,
            $originId,
            $destinationId,
            $destinationId,
            $date,
            $date,
            $status,
            $status,
            $keyword,
            $keywordLike,
            $keywordLike,
            $keywordLike,
            $keywordLike,
            $keywordLike,
            $keywordLike,
            $keywordLike,
            $keywordLike
        );
        $stmt->execute();
        $total = (int) $stmt->get_result()->fetch_assoc()['total'];
        $stmt->close();

        return $total;
    }

    private function filterSql()
    {
        return "(? = 0 OR cb.id_san_bay_di = ?)
                AND (? = 0 OR cb.id_san_bay_den = ?)
                AND (? = '' OR DATE(cb.gio_du_kien) = ?)
                AND (? = '' OR cb.trang_thai_tong = ?)
                AND (? = '' OR cb.so_hieu LIKE ? OR h.ten_hang LIKE ?
                     OR sb1.ma_iata LIKE ? OR sb1.ten_san_bay LIKE ? OR sb1.ten_ngan LIKE ?
                     OR sb2.ma_iata LIKE ? OR sb2.ten_san_bay LIKE ? OR sb2.ten_ngan LIKE ?)";
    }

    private function getFlight($id)
    {
        $stmt = $this->db->prepare('SELECT * FROM chuyen_bay_ngay WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $flight = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $flight ?: null;
    }

    private function getFlightSummary($flightId)
    {
        $stmt = $this->db->prepare(
            "SELECT h.ma_iata AS hang_iata, h.ten_hang,
                    lt.ma_loai,
                    sbdi.ma_iata AS ma_di, sbdi.ten_san_bay AS ten_di,
                    sbde.ma_iata AS ma_den, sbde.ten_san_bay AS ten_den,
                    CONCAT(sbg.ma_iata, ' - Cửa ', g.so_cua,
                           IF(g.khu_vuc IS NULL OR g.khu_vuc = '', '', CONCAT(' (', g.khu_vuc, ')'))) AS cua_ra,
                    CONCAT(sbq.ma_iata, ' - Quầy ', q.so_quay,
                           IF(q.khu_vuc IS NULL OR q.khu_vuc = '', '', CONCAT(' (', q.khu_vuc, ')'))) AS quay_checkin,
                    CONCAT(sbb.ma_iata, ' - Băng chuyền ', b.so_bang_chuyen,
                           IF(b.khu_vuc IS NULL OR b.khu_vuc = '', '', CONCAT(' (', b.khu_vuc, ')'))) AS bang_chuyen
             FROM chuyen_bay_ngay cb
             JOIN hang_hang_khong h ON h.id = cb.id_hang_hang_khong
             JOIN loai_tau_bay lt ON lt.id = cb.id_loai_tau_bay
             JOIN san_bay sbdi ON sbdi.id = cb.id_san_bay_di
             JOIN san_bay sbde ON sbde.id = cb.id_san_bay_den
             LEFT JOIN cua_ra_may_bay g ON g.id = cb.id_cua_ra_may_bay
             LEFT JOIN nha_ga ng ON ng.id = g.id_nha_ga
             LEFT JOIN san_bay sbg ON sbg.id = ng.id_san_bay
             LEFT JOIN quay_checkin q ON q.id = cb.id_quay_checkin
             LEFT JOIN nha_ga nq ON nq.id = q.id_nha_ga
             LEFT JOIN san_bay sbq ON sbq.id = nq.id_san_bay
             LEFT JOIN bang_chuyen_hanh_ly b ON b.id = cb.id_bang_chuyen
             LEFT JOIN nha_ga nb ON nb.id = b.id_nha_ga
             LEFT JOIN san_bay sbb ON sbb.id = nb.id_san_bay
             WHERE cb.id = ?"
        );
        $stmt->bind_param('i', $flightId);
        $stmt->execute();
        $summary = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $summary ?: [];
    }

    private function getFlightLogs($flightId)
    {
        $stmt = $this->db->prepare(
            'SELECT nk.hanh_dong, nk.trang_thai, nk.trang_thai_cu, nk.thoi_gian_cap_nhat, nk.ghi_chu,
                    nk.id_nguoi_dung, nd.ho_ten
             FROM nhat_ky_trang_thai_bay nk
             LEFT JOIN nguoi_dung nd ON nd.id = nk.id_nguoi_dung
             WHERE nk.id_chuyen_bay = ?
             ORDER BY nk.thoi_gian_cap_nhat DESC, nk.id DESC
             LIMIT 50'
        );
        $stmt->bind_param('i', $flightId);
        $stmt->execute();
        $logs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $logs;
    }

    /**
     * Giờ hiện tại theo đồng hồ của database — dùng chung cho đồng hồ trên web và việc tự chuyển trạng thái
     * (PHP có thể cấu hình timezone khác với MySQL, nên không dùng date() trực tiếp).
     */
    public function getCurrentDateTime()
    {
        $row = $this->db->query('SELECT NOW() AS now_ts')->fetch_assoc();

        return $row ? (string) $row['now_ts'] : date('Y-m-d H:i:s');
    }

    /**
     * Tổng thời gian trễ của một chuyến **chưa cất cánh** (phút)
     * = tổng các lần đã delay (đọc từ nhật ký) + số phút đã quá hạn chờ xác nhận.
     *
     * @param array<int,array<string,mixed>> $history nhật ký trễ của chuyến
     * @param string $plannedDeparture giờ cất cánh dự kiến hiện tại (`gio_du_kien`)
     */
    private function getTotalLatenessMinutes(array $history, string $plannedDeparture, ?int $nowTs = null): int
    {
        $totalDelayMinutes = 0;
        foreach ($history as $entry) {
            $totalDelayMinutes += (int) ($entry['so_phut_lui'] ?? $entry['so_phut'] ?? 0);
        }
        $plannedTs = strtotime($plannedDeparture);
        if ($plannedTs === false) {
            return $totalDelayMinutes;
        }
        $nowTs = $nowTs ?? (int) strtotime($this->getCurrentDateTime());

        return $totalDelayMinutes + max(0, (int) floor(($nowTs - $plannedTs) / 60));
    }

    /**
     * Tự hủy chuyến khi tổng thời gian trễ (delay + quá hạn) đã vượt giới hạn 5 giờ.
     *
     * @return bool true nếu chuyến vừa được hủy ở lần gọi này
     */
    private function autoCancelOverdueFlight(int $flightId, string $currentStatus, int $totalLatenessMinutes): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE chuyen_bay_ngay SET trang_thai_tong = 'Huy'
             WHERE id = ? AND trang_thai_tong IN ('Dang_Mo', 'Delay')"
        );
        $stmt->bind_param('i', $flightId);
        $stmt->execute();
        $changed = $stmt->affected_rows > 0;
        $stmt->close();
        if (!$changed) {
            return false;
        }

        $this->logFlightAction(
            $flightId,
            'Tu_Dong',
            $currentStatus,
            'Huy',
            'Hủy chuyến do chưa khắc phục được sự cố và chuyển sang chuyến bay gần nhất.'
        );

        return true;
    }

    /**
     * Đồng bộ theo giờ thực tế (mỗi lần mở/ tải lại trang và mỗi 15 giây khi trang tự poll):
     *  - Cất cánh -> Hạ cánh khi đã tới giờ dự kiến hạ cánh (`gio_thuc_te <= NOW()`).
     *  - Quá hạn xác nhận: `Dang_Mo` của **hôm nay** đã qua giờ cất cánh quá `OVERDUE_LIMIT_MINUTES`
     *    mà chưa ai bấm xác nhận/ delay -> tự chuyển `Delay` để chuyển xuống mục "Các chuyến bay trễ chuyến".
     *  - Quá 5 giờ trễ: tổng thời gian trễ (delay đã ghi nhận + số phút quá hạn chờ xác nhận)
     *    đạt `DELAY_CANCEL_LIMIT_MINUTES` -> mặc định tự hủy chuyến.
     * Chuyến Đang mở / Trễ chuyến khi tới giờ cất cánh **không** tự chuyển sang Cất cánh:
     * trang danh sách đưa chúng vào 2 mục "Các chuyến đã đến giờ bay" và
     * "Các chuyến bay trễ chuyến" để người điều khiển xác nhận (xem `handleDepartureCheck()`).
     *
     * @return array{departed:int,landed:int,overdue:int,cancelled:int,resumed:int}
     */
    public function syncRealtimeStatuses()
    {
        $result = ['departed' => 0, 'landed' => 0, 'overdue' => 0, 'cancelled' => 0, 'resumed' => 0];

        // Cất cánh -> Hạ cánh khi đã tới giờ dự kiến hạ cánh (cộng thêm tổng thời gian delay nếu có).
        $rows = $this->db->query(
            "SELECT id, gio_thuc_te FROM chuyen_bay_ngay
             WHERE trang_thai_tong = 'Cat_Canh'
               AND gio_thuc_te IS NOT NULL"
        )->fetch_all(MYSQLI_ASSOC);
        $ids = [];
        foreach ($rows as $row) {
            $flightId = (int) $row['id'];
            $history = $this->getDelayHistory($flightId);
            $totalDelayMinutes = 0;
            $hasShiftedSchedule = false;
            foreach ($history as $entry) {
                $totalDelayMinutes += (int) ($entry['so_phut_lui'] ?? $entry['so_phut'] ?? 0);
                if (strpos((string) ($entry['ghi_chu'] ?? ''), 'Giờ cất cánh mới') !== false
                    || strpos((string) ($entry['ghi_chu'] ?? ''), 'tổng lùi') !== false) {
                    $hasShiftedSchedule = true;
                }
            }
            // gio_thuc_te đã là giờ hạ cánh dự kiến cuối cùng (đã gồm mọi lần delay).
            $effectiveLanding = strtotime((string) $row['gio_thuc_te']);
            if ($effectiveLanding !== false && $effectiveLanding <= strtotime($this->getCurrentDateTime())) {
                $ids[] = $flightId;
            }
        }
        $ids = array_values(array_unique($ids));
        if ($ids) {
            $this->db->query(
                "UPDATE chuyen_bay_ngay SET trang_thai_tong = 'Ha_Canh'
                 WHERE id IN (" . implode(',', $ids) . ')'
            );
            foreach ($ids as $flightId) {
                $this->logFlightAction(
                    $flightId,
                    'Tu_Dong',
                    'Cat_Canh',
                    'Ha_Canh',
                    'Tự động: đã tới giờ dự kiến hạ cánh.'
                );
            }
            $result['landed'] = count($ids);
        }

        // Quá hạn xác nhận: chuyến Đang mở của hôm nay đã qua giờ cất cánh quá số phút cho phép
        // mà chưa ai bấm xác nhận / delay -> tự chuyển Trễ chuyến (giữ nguyên giờ, người điều khiển xử lý tiếp).
        $limit = self::OVERDUE_LIMIT_MINUTES;
        $rows = $this->db->query(
            "SELECT id FROM chuyen_bay_ngay
             WHERE trang_thai_tong = 'Dang_Mo'
               AND DATE(gio_du_kien) = CURDATE()
               AND TIMESTAMPDIFF(MINUTE, gio_du_kien, NOW()) >= " . (int) $limit
        )->fetch_all(MYSQLI_ASSOC);
        foreach (array_map('intval', array_column($rows, 'id')) as $flightId) {
            $stmt = $this->db->prepare(
                "UPDATE chuyen_bay_ngay SET trang_thai_tong = 'Delay'
                 WHERE id = ? AND trang_thai_tong = 'Dang_Mo'"
            );
            $stmt->bind_param('i', $flightId);
            $stmt->execute();
            $changed = $stmt->affected_rows > 0;
            $stmt->close();
            if (!$changed) {
                continue;
            }
            $this->logFlightAction(
                $flightId,
                'Tu_Dong',
                'Dang_Mo',
                'Delay',
                'Tự động chuyển Trễ chuyến: quá ' . $limit
                    . ' phút kể từ giờ cất cánh mà chưa xác nhận điều kiện bay.'
            );
            $result['overdue']++;
        }

        // Chuyến Trễ chuyến đã hết thời gian delay -> chuyển về Đang mở, ghi lại lý do trễ trước đó.
        $rows = $this->db->query(
            "SELECT id, gio_du_kien FROM chuyen_bay_ngay
             WHERE trang_thai_tong = 'Delay'
               AND gio_du_kien <= NOW()
               AND TIMESTAMPDIFF(MINUTE, gio_du_kien, NOW()) < " . (int) self::OVERDUE_LIMIT_MINUTES . "
             ORDER BY id ASC"
        )->fetch_all(MYSQLI_ASSOC);
        foreach ($rows as $row) {
            $flightId = (int) $row['id'];
            $history = $this->getDelayHistory($flightId);
            // Tổng thời gian trễ (delay + quá hạn) đã chạm giới hạn hủy thì để khối hủy xử lý.
            if ($this->getTotalLatenessMinutes($history, (string) $row['gio_du_kien']) >= self::DELAY_CANCEL_LIMIT_MINUTES) {
                continue;
            }
            $latest = $history ? $history[count($history) - 1] : null;
            $previousReason = (string) ($latest['ly_do'] ?? 'Chưa có lý do');
            $stmt = $this->db->prepare(
                "UPDATE chuyen_bay_ngay SET trang_thai_tong = 'Dang_Mo'
                 WHERE id = ? AND trang_thai_tong = 'Delay'"
            );
            $stmt->bind_param('i', $flightId);
            $stmt->execute();
            $changed = $stmt->affected_rows > 0;
            $stmt->close();
            if (!$changed) {
                continue;
            }
            $this->logFlightAction(
                $flightId,
                'Tu_Dong',
                'Delay',
                'Dang_Mo',
                'Hết thời gian delay, chuyển về Đang mở. Lý do trễ trước đó: ' . $previousReason . '.'
            );
            $result['resumed']++;
        }

        // Chuyến Đang mở / Trễ chuyến có tổng thời gian trễ (delay đã ghi nhận + quá hạn chờ xác nhận)
        // vượt quá giới hạn 5 giờ -> mặc định tự hủy chuyến.
        $cancelLimit = self::DELAY_CANCEL_LIMIT_MINUTES;
        $rows = $this->db->query(
            "SELECT id, trang_thai_tong, gio_du_kien FROM chuyen_bay_ngay
             WHERE trang_thai_tong IN ('Dang_Mo', 'Delay')
               AND DATE(gio_du_kien) >= CURDATE()"
        )->fetch_all(MYSQLI_ASSOC);
        $nowTs = (int) strtotime($this->getCurrentDateTime());
        foreach ($rows as $row) {
            $flightId = (int) $row['id'];
            $history = $this->getDelayHistory($flightId);
            $totalLatenessMinutes = $this->getTotalLatenessMinutes($history, (string) $row['gio_du_kien'], $nowTs);
            if ($totalLatenessMinutes < $cancelLimit) {
                continue;
            }
            if ($this->autoCancelOverdueFlight($flightId, (string) $row['trang_thai_tong'], $totalLatenessMinutes)) {
                $result['cancelled']++;
            }
        }

        // Chuyến vừa hạ cánh được lưu ngay vào lịch sử bay.
        $this->archiveLandedFlights();

        return $result;
    }

    /**
     * Danh sách chuyến cần xác nhận điều kiện bay của **một ngày** (mặc định hôm nay):
     *  - `Dang_Mo` có `gio_du_kien <= NOW()` -> mục "Các chuyến đã đến giờ bay".
     *  - `Delay` có `gio_du_kien <= NOW()` -> mục "Các chuyến bay trễ chuyến".
     * Chỉ lấy chuyến thuộc đúng ngày đang chọn (cột `gio_du_kien`), không lấy ngày khác.
     *
     * @param string|null $date ngày dạng `Y-m-d`; không hợp lệ thì dùng ngày hiện tại của database.
     * @return array<int,array<string,mixed>>
     */
    public function getPendingDepartures($date = null)
    {
        $today = substr($this->getCurrentDateTime(), 0, 10);
        $date = $date !== null && $this->isValidDate((string) $date) ? (string) $date : $today;

        $stmt = $this->db->prepare(
            "SELECT cb.id, cb.so_hieu, cb.gio_du_kien, cb.gio_thuc_te, cb.trang_thai_tong,
                    sbdi.ma_iata AS ma_di, sbdi.ten_san_bay AS ten_di,
                    sbde.ma_iata AS ma_den, sbde.ten_san_bay AS ten_den,
                    hk.ten_hang
             FROM chuyen_bay_ngay cb
             JOIN hang_hang_khong hk ON hk.id = cb.id_hang_hang_khong
             JOIN san_bay sbdi ON sbdi.id = cb.id_san_bay_di
             JOIN san_bay sbde ON sbde.id = cb.id_san_bay_den
             WHERE cb.trang_thai_tong IN ('Dang_Mo', 'Delay')
               AND DATE(cb.gio_du_kien) = ?
               AND cb.gio_du_kien <= NOW()
             ORDER BY cb.gio_du_kien ASC, cb.id ASC"
        );
        $stmt->bind_param('s', $date);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $rows ?: [];
    }

    /**
     * Các chuyến Trễ chuyến chưa hết thời gian delay (gio_du_kien còn ở tương lai) của một ngày.
     */
    public function getFutureDelayedDepartures($date = null)
    {
        $today = substr($this->getCurrentDateTime(), 0, 10);
        $date = $date !== null && $this->isValidDate((string) $date) ? (string) $date : $today;

        $stmt = $this->db->prepare(
            "SELECT cb.id, cb.so_hieu, cb.gio_du_kien, cb.gio_thuc_te, cb.trang_thai_tong,
                    sbdi.ma_iata AS ma_di, sbdi.ten_san_bay AS ten_di,
                    sbde.ma_iata AS ma_den, sbde.ten_san_bay AS ten_den,
                    hk.ten_hang
             FROM chuyen_bay_ngay cb
             JOIN hang_hang_khong hk ON hk.id = cb.id_hang_hang_khong
             JOIN san_bay sbdi ON sbdi.id = cb.id_san_bay_di
             JOIN san_bay sbde ON sbde.id = cb.id_san_bay_den
             WHERE cb.trang_thai_tong = 'Delay'
               AND DATE(cb.gio_du_kien) = ?
               AND cb.gio_du_kien > NOW()
             ORDER BY cb.gio_du_kien ASC, cb.id ASC"
        );
        $stmt->bind_param('s', $date);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $rows ?: [];
    }

    /**
     * Dữ liệu popup "Xác nhận điều kiện cất cánh" — mở từ 2 mục chuyến đã đến giờ bay trên trang danh sách.
     * Trả về thông tin đầy đủ của chuyến, các chuyến trùng lịch, lý do trễ trước đó và giờ sân bay trống gợi ý.
     */
    public function handleDepartureCheck()
    {
        $this->ensureCsrf();
        if ($this->isPost()) {
            $this->processDeparturePost();
        }

        $id = max(0, (int) ($_GET['id'] ?? 0));
        $flight = $id > 0 ? $this->getDepartureFlight($id) : null;
        if ($flight === null) {
            return null;
        }

        $now = $this->getCurrentDateTime();
        $planned = (string) $flight['gio_du_kien'];
        $isDelayed = $flight['trang_thai_tong'] === 'Delay';
        $isEarly = $isDelayed && strtotime($planned) > strtotime($now);
        $conflicts = $this->findDepartureConflicts($flight, $isEarly ? $now : $planned);

        return [
            'flight' => $flight,
            'conflicts' => $conflicts,
            'delayHistory' => $this->getDelayHistory((int) $flight['id']),
            'suggestedSlot' => $conflicts ? $this->suggestFreeSlot($flight, $isEarly ? $now : null) : null,
            'flash' => $this->pullNamedFlash('chuyen_bay_departure_flash'),
            'csrf' => $_SESSION['chuyen_bay_csrf'],
            'serverNow' => $now,
            // Chuyến Trễ chuyến luôn được xác nhận sớm (lý do trễ có thể xong trước giờ dự kiến).
            'isPending' => $isDelayed || (in_array($flight['trang_thai_tong'], ['Dang_Mo'], true)
                && strtotime($planned) <= strtotime($now)),
            'isDelayed' => $isDelayed,
            'isEarly' => $isEarly,
        ];
    }

    /**
     * Dữ liệu popup "Xem chi tiết chuyến bay": thông tin đầy đủ của chuyến + sân bay đi/đến và tài nguyên.
     */
    public function handleFlightDetail()
    {
        $this->ensureCsrf();
        $id = max(0, (int) ($_GET['id'] ?? 0));
        $flight = $id > 0 ? $this->getDepartureFlight($id) : null;
        $delayHistory = $flight ? $this->getDelayHistory((int) $flight['id']) : [];
        $totalDelayMinutes = 0;
        foreach ($delayHistory as $entry) {
            $totalDelayMinutes += (int) ($entry['so_phut_lui'] ?? $entry['so_phut'] ?? 0);
        }
        // Số phút đã quá hạn chờ xác nhận (chuyến chưa cất cánh).
        $overdueMinutes = 0;
        if ($flight && !in_array($flight['trang_thai_tong'], ['Cat_Canh', 'Ha_Canh'], true)) {
            $overdueMinutes = max(
                0,
                (int) floor((strtotime($this->getCurrentDateTime()) - strtotime((string) $flight['gio_du_kien'])) / 60)
            );
        }

        return [
            'flight' => $flight,
            'delayHistory' => $delayHistory,
            'totalDelayMinutes' => $totalDelayMinutes,
            'overdueMinutes' => $overdueMinutes,
            'csrf' => $_SESSION['chuyen_bay_csrf'],
            'serverNow' => $this->getCurrentDateTime(),
        ];
    }

    /** Xử lý các thao tác trong popup xác nhận cất cánh (ghi nhận sự cố / cất cánh / trễ tiếp). */
    private function processDeparturePost()
    {
        $id = max(0, (int) ($_POST['id'] ?? 0));
        $action = (string) ($_POST['action'] ?? '');
        $back = ['popup' => 1, 'id' => $id];

        if (!hash_equals($_SESSION['chuyen_bay_csrf'] ?? '', (string) ($_POST['csrf'] ?? ''))) {
            $this->setNamedFlash('chuyen_bay_departure_flash', 'danger', 'Phiên làm việc không hợp lệ. Vui lòng tải lại trang.');
            $this->redirectTo('/views/pages/chuyen_bay/cat_canh.php', $back);
        }
        if (empty($_SESSION['user_id'])) {
            $this->setNamedFlash('chuyen_bay_departure_flash', 'danger', 'Vui lòng đăng nhập để xác nhận điều kiện cất cánh.');
            $this->redirectTo('/views/pages/chuyen_bay/cat_canh.php', $back);
        }

        try {
            if ($action === 'departure_confirm') {
                $this->confirmDeparture($id);
            } elseif ($action === 'departure_delay') {
                $this->delayDeparture($id);
            } else {
                throw new \RuntimeException('Thao tác không hợp lệ.');
            }
        } catch (RuntimeException $exception) {
            $this->setNamedFlash('chuyen_bay_departure_flash', 'danger', $exception->getMessage());
            $this->redirectTo('/views/pages/chuyen_bay/cat_canh.php', $back);
        }
    }

    /**
     * Xác nhận chuyến đã đủ điều kiện bay / đủ yêu cầu an toàn.
     * Trước khi cho cất cánh phải kiểm tra trùng lịch; nếu trùng thì **không** đổi trạng thái mà báo lỗi
     * kèm giờ sân bay trống gợi ý để người điều khiển delay tới giờ đó.
     */
    private function confirmDeparture($id)
    {
        $flight = $this->getDepartureFlight($id);
        if ($flight === null) {
            throw new \RuntimeException('Không tìm thấy chuyến bay.');
        }
        if (!in_array($flight['trang_thai_tong'], ['Dang_Mo', 'Delay'], true)) {
            throw new \RuntimeException('Chuyến bay không ở trạng thái chờ cất cánh.');
        }

        $wasDelayed = $flight['trang_thai_tong'] === 'Delay';
        // Quá 5 giờ trễ (delay + quá hạn) thì mặc định hủy chuyến, không cho cất cánh.
        $totalLateness = $this->getTotalLatenessMinutes(
            $this->getDelayHistory($id),
            (string) $flight['gio_du_kien']
        );
        if ($totalLateness >= self::DELAY_CANCEL_LIMIT_MINUTES
            && $this->autoCancelOverdueFlight($id, (string) $flight['trang_thai_tong'], $totalLateness)) {
            throw new \RuntimeException(
                'Chuyến ' . $flight['so_hieu'] . ' đã bị tự động hủy vì tổng thời gian trễ '
                    . $totalLateness . ' phút vượt giới hạn '
                    . (int) (self::DELAY_CANCEL_LIMIT_MINUTES / 60) . ' giờ.'
            );
        }
        $now = $this->getCurrentDateTime();
        $isEarly = strtotime((string) $flight['gio_du_kien']) > strtotime($now);

        $conflicts = $this->findDepartureConflicts($flight, $isEarly ? $now : null);
        if ($conflicts) {
            $parts = [];
            foreach ($conflicts as $conflict) {
                $conflictTime = DateTime::createFromFormat('Y-m-d H:i:s', (string) $conflict['gio_du_kien']);
                $parts[] = $conflict['so_hieu'] . ' lúc '
                    . ($conflictTime ? $conflictTime->format('H:i d/m/Y') : $conflict['gio_du_kien'])
                    . ' (' . $conflict['ma_iata'] . ')';
            }
            $message = 'Không thể cất cánh: trùng lịch cất cánh với chuyến ' . implode(', ', $parts) . '.';
            $suggestion = $this->suggestFreeSlot($flight, $isEarly ? $now : null);
            if ($suggestion !== null) {
                $suggestedTime = DateTime::createFromFormat('Y-m-d H:i:s', $suggestion);
                $message .= ' Sân bay trống gần nhất lúc '
                    . ($suggestedTime ? $suggestedTime->format('H:i d/m/Y') : $suggestion)
                    . ' — hãy dùng nút trễ để lùi tới giờ đó.';
            }

            throw new \RuntimeException($message);
        }

        // Giờ cất cánh thực tế = thời điểm bấm xác nhận.
        // Giờ hạ cánh = thời điểm bấm + thời gian bay gốc + tổng thời gian delay + thời gian đã quá hạn.
        $durStmt = $this->db->prepare(
            'SELECT GREATEST(TIMESTAMPDIFF(MINUTE, gio_du_kien, gio_thuc_te), 0) AS dur FROM chuyen_bay_ngay WHERE id = ?'
        );
        $durStmt->bind_param('i', $id);
        $durStmt->execute();
        $durRow = $durStmt->get_result()->fetch_assoc();
        $durStmt->close();
        $durationMinutes = (int) ($durRow['dur'] ?? 0);
        $totalDelayMinutes = 0;
        if ($wasDelayed) {
            foreach ($this->getDelayHistory($id) as $entry) {
                $totalDelayMinutes += (int) ($entry['so_phut_lui'] ?? $entry['so_phut'] ?? 0);
            }
        }
        // Số phút đã quá hạn chờ xác nhận (so với giờ cất cánh dự kiến hiện tại của chuyến).
        $overdueMinutes = max(
            0,
            (int) floor((strtotime($now) - strtotime((string) $flight['gio_du_kien'])) / 60)
        );
        $intervalMinutes = max(1, $durationMinutes + $totalDelayMinutes + $overdueMinutes);
        $stmt = $this->db->prepare(
            "UPDATE chuyen_bay_ngay SET gio_thuc_te = IF(gio_thuc_te IS NULL, NULL, DATE_ADD(NOW(), INTERVAL ? MINUTE)), gio_du_kien = NOW(), trang_thai_tong = 'Cat_Canh' WHERE id = ? AND trang_thai_tong IN ('Dang_Mo', 'Delay')"
        );
        $stmt->bind_param('ii', $intervalMinutes, $id);
        $stmt->execute();
        $stmt->close();

        if ($wasDelayed) {
            $delayHistory = $this->getDelayHistory($id);
            $delayReasons = [];
            foreach ($delayHistory as $entry) {
                if (!empty($entry['ly_do'])) {
                    $delayReasons[] = (string) $entry['ly_do'];
                }
            }
            $delayReasons = array_values(array_unique($delayReasons));
            $reasonText = $delayReasons ? implode(', ', $delayReasons) : 'không có lý do trước đó';

            $userName = 'Nhân viên';
            if (!empty($_SESSION['user_id'])) {
                $userStmt = $this->db->prepare('SELECT ho_ten FROM nguoi_dung WHERE id = ?');
                $userStmt->bind_param('i', $_SESSION['user_id']);
                $userStmt->execute();
                $userRow = $userStmt->get_result()->fetch_assoc();
                $userStmt->close();
                if ($userRow && !empty($userRow['ho_ten'])) {
                    $userName = (string) $userRow['ho_ten'];
                }
            }

            $note = 'Chuyến bay ' . $flight['so_hieu']
                . ' đã khắc phục xong các sự cố trễ trước đó, Lý do ' . $reasonText . '. '
                . $userName . ' xác nhận đủ điều kiện an toàn và cho cất cánh lúc '
                . date('H:i', strtotime($now)) . '.'
                . ' Tổng thời gian trễ ' . ($totalDelayMinutes + $overdueMinutes) . ' phút ('
                . $totalDelayMinutes . ' phút delay + ' . $overdueMinutes . ' phút quá hạn).';
            if ($isEarly) {
                $earlyTime = DateTime::createFromFormat('Y-m-d H:i:s', (string) $flight['gio_du_kien']);
                $note .= ' Cất cánh sớm hơn giờ dự kiến ('
                    . ($earlyTime ? $earlyTime->format('d/m/Y H:i') : $flight['gio_du_kien']) . ').';
            }
        } else {
            $note = 'Đã đủ điều kiện bay và xác nhận cất cánh.';
            if ($isEarly) {
                $earlyTime = DateTime::createFromFormat('Y-m-d H:i:s', (string) $flight['gio_du_kien']);
                $note .= ' Cất cánh sớm hơn giờ dự kiến ('
                    . ($earlyTime ? $earlyTime->format('d/m/Y H:i') : $flight['gio_du_kien']) . ').';
            }
        }
        $this->logFlightAction(
            $id,
            'Xac_Nhan_Cat_Canh',
            $flight['trang_thai_tong'],
            'Cat_Canh',
            $note,
            (int) $_SESSION['user_id']
        );

        $this->setFlash(
            'success',
            'Chuyến ' . $flight['so_hieu'] . ($wasDelayed
                ? ' đã đủ yêu cầu an toàn và chuyển sang Cất cánh.'
                : ' đã đủ điều kiện bay và chuyển sang Cất cánh.')
        );
        $this->redirectTo('/views/pages/chuyen_bay/cat_canh.php', ['popup' => 1, 'id' => $id, 'done' => 1]);
    }

    /**
     * Chưa đủ điều kiện bay / chưa đủ yêu cầu an toàn: ghi lý do trễ và lùi giờ cất cánh.
     * Hỗ trợ 2 cách nhập: số phút trễ tiếp (1–1440) **hoặc** giờ cất cánh mới cụ thể (`Y-m-d\TH:i`).
     * Luôn lùi cả `gio_du_kien` và `gio_thuc_te` cùng một khoảng để giữ nguyên thời gian bay.
     */
    private function delayDeparture($id)
    {
        $reason = trim((string) ($_POST['ly_do'] ?? ''));
        if (!$this->isValidReason($reason)) {
            throw new \RuntimeException('Vui lòng nhập lý do trễ / chưa đủ điều kiện bay (tối đa 255 ký tự).');
        }

        $flight = $this->getDepartureFlight($id);
        if ($flight === null) {
            throw new \RuntimeException('Không tìm thấy chuyến bay.');
        }
        if (!in_array($flight['trang_thai_tong'], ['Dang_Mo', 'Delay'], true)) {
            throw new \RuntimeException('Chuyến bay không ở trạng thái chờ cất cánh.');
        }
        $wasDelayed = $flight['trang_thai_tong'] === 'Delay';

        $now = $this->getCurrentDateTime();
        $newPlannedInput = trim((string) ($_POST['gio_cat_canh_moi'] ?? ''));
        if ($newPlannedInput !== '') {
            $parsed = DateTime::createFromFormat('Y-m-d\TH:i', $newPlannedInput)
                ?: DateTime::createFromFormat('Y-m-d H:i:s', $newPlannedInput)
                ?: DateTime::createFromFormat('Y-m-d H:i', $newPlannedInput);
            if (!$parsed) {
                throw new \RuntimeException('Giờ cất cánh mới không hợp lệ.');
            }
            $newPlanned = $parsed->format('Y-m-d H:i:s');
            if (strtotime($newPlanned) <= strtotime($now)) {
                throw new \RuntimeException('Giờ cất cánh mới phải sau giờ hiện tại (' . $now . ').');
            }
        } else {
            $minutes = filter_var($_POST['thoi_gian_tre_phut'] ?? '', FILTER_VALIDATE_INT);
            if ($minutes === false || $minutes < 1 || $minutes > 1440) {
                throw new \RuntimeException('Vui lòng nhập số phút trễ tiếp (từ 1 đến 1440) hoặc giờ cất cánh mới.');
            }
            $minutes = (int) $minutes;
            $stmt = $this->db->prepare(
                "SELECT DATE_ADD(GREATEST(gio_du_kien, NOW()), INTERVAL ? MINUTE) AS new_planned
                 FROM chuyen_bay_ngay WHERE id = ?"
            );
            $stmt->bind_param('ii', $minutes, $id);
            $stmt->execute();
            $timing = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if (!$timing) {
                throw new \RuntimeException('Không tìm thấy chuyến bay.');
            }
            $newPlanned = (string) $timing['new_planned'];
        }

        // Số phút lùi thực tế so với giờ cất cánh hiện tại (dùng để lùi cả giờ hạ cánh).
        $stmt = $this->db->prepare(
            'SELECT TIMESTAMPDIFF(MINUTE, gio_du_kien, ?) AS shift_minutes FROM chuyen_bay_ngay WHERE id = ?'
        );
        $stmt->bind_param('si', $newPlanned, $id);
        $stmt->execute();
        $timing = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (strtotime($newPlanned) <= strtotime($now)) {
            throw new \RuntimeException('Giờ cất cánh mới phải sau giờ hiện tại (' . $now . ').');
        }
        $shiftMinutes = (int) ($timing['shift_minutes'] ?? 0);
        if ($shiftMinutes < 1) {
            throw new \RuntimeException('Giờ cất cánh mới phải sau giờ cất cánh hiện tại của chuyến.');
        }

        $stmt = $this->db->prepare(
            "UPDATE chuyen_bay_ngay
             SET gio_thuc_te = IF(gio_thuc_te IS NULL, NULL, DATE_ADD(gio_thuc_te, INTERVAL ? MINUTE)),
                 gio_du_kien = ?,
                 trang_thai_tong = 'Delay'
             WHERE id = ?"
        );
        $stmt->bind_param('isi', $shiftMinutes, $newPlanned, $id);
        $stmt->execute();
        $stmt->close();

        $newPlannedTime = DateTime::createFromFormat('Y-m-d H:i:s', $newPlanned);
        $newPlannedText = $newPlannedTime ? $newPlannedTime->format('d/m/Y H:i') : $newPlanned;
        $enteredMinutes = $newPlannedInput !== ''
            ? $shiftMinutes
            : (int) $minutes;
        $note = ($wasDelayed ? 'Chưa đủ yêu cầu an toàn - delay tiếp ' : 'Chưa đủ điều kiện bay - trễ chuyến ')
            . $enteredMinutes . ' phút. Lý do: ' . $reason
            . '. Giờ cất cánh mới: ' . $newPlannedText
            . ' (tổng lùi ' . $shiftMinutes . ' phút).';
        $this->logFlightAction(
            $id,
            'Tre_Tiep',
            $flight['trang_thai_tong'],
            'Delay',
            $note,
            (int) $_SESSION['user_id']
        );

        // Trễ tiếp mà tổng thời gian trễ (delay + quá hạn) vượt 5 giờ -> mặc định hủy chuyến.
        $totalAfterDelay = $this->getTotalLatenessMinutes($this->getDelayHistory($id), (string) $newPlanned);
        if ($totalAfterDelay >= self::DELAY_CANCEL_LIMIT_MINUTES
            && $this->autoCancelOverdueFlight($id, 'Delay', $totalAfterDelay)) {
            throw new \RuntimeException(
                'Chuyến ' . $flight['so_hieu'] . ' đã bị tự động hủy vì tổng thời gian trễ '
                    . $totalAfterDelay . ' phút vượt giới hạn '
                    . (int) (self::DELAY_CANCEL_LIMIT_MINUTES / 60) . ' giờ.'
            );
        }

        $this->setFlash(
            'warning',
            'Chuyến ' . $flight['so_hieu'] . ' chưa đủ điều kiện bay: đã lùi giờ cất cánh '
                . $shiftMinutes . ' phút đến ' . $newPlannedText . '.'
        );
        $this->redirectTo('/views/pages/chuyen_bay/cat_canh.php', ['popup' => 1, 'id' => $id, 'done' => 1]);
    }

    /**
     * Lý do trễ trước đó của chuyến — đọc từ nhật ký `nhat_ky_trang_thai_bay`
     * (các lần `Tre_Tiep` hoặc lần sửa có ghi "Lý do trễ chuyến"). Không dùng bảng mới.
     *
     * @return array<int,array<string,mixed>> mới nhất ở cuối danh sách.
     */
    private function getDelayHistory($flightId)
    {
        $stmt = $this->db->prepare(
            "SELECT nk.hanh_dong, nk.trang_thai, nk.trang_thai_cu, nk.ghi_chu,
                    nk.thoi_gian_cap_nhat, nk.id_nguoi_dung, nd.ho_ten
             FROM nhat_ky_trang_thai_bay nk
             LEFT JOIN nguoi_dung nd ON nd.id = nk.id_nguoi_dung
             WHERE nk.id_chuyen_bay = ?
               AND (nk.hanh_dong = 'Tre_Tiep'
                    OR nk.ghi_chu LIKE '%Lý do trễ%'
                    OR nk.ghi_chu LIKE '%Lý do:%')
             ORDER BY nk.thoi_gian_cap_nhat ASC, nk.id ASC
             LIMIT 20"
        );
        $stmt->bind_param('i', $flightId);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $history = [];
        foreach ($rows as $row) {
            $note = (string) ($row['ghi_chu'] ?? '');
            $reason = null;
            $minutes = null;

            if (preg_match('/Lý do trễ chuyến:\s*([^;]+)/u', $note, $match)) {
                $reason = trim($match[1]);
            } elseif (preg_match('/Lý do:\s*(.+?)(?:\.\s*(?:Giờ cất cánh mới|Sự cố)|$)/su', $note, $match)) {
                $reason = trim($match[1]);
            }
            if (preg_match('/(?:trễ tiếp|trễ chuyến)\s+(\d+)\s+phút/iu', $note, $match)) {
                $minutes = (int) $match[1];
            } elseif (preg_match('/\((\d+)\s*phút\)/iu', $note, $match)) {
                // Dạng "Khoảng thời gian dự kiến trễ: 1 giờ 30 phút (90 phút)".
                $minutes = (int) $match[1];
            } elseif (preg_match('/trễ[^;:]*:\s*(\d+)\s*phút/iu', $note, $match)) {
                // Dạng "Khoảng thời gian dự kiến trễ: 30 phút".
                $minutes = (int) $match[1];
            }

            // Tổng số phút lùi thực tế (để cộng dồn tổng delay), ưu tiên "tổng lùi X phút".
            $shiftMinutes = null;
            if (preg_match('/tổng lùi\s+(\d+)\s*phút/iu', $note, $matchShift)) {
                $shiftMinutes = (int) $matchShift[1];
            } elseif ($minutes !== null) {
                $shiftMinutes = $minutes;
            }
            $history[] = [
                'hanh_dong' => (string) $row['hanh_dong'],
                'thoi_gian' => (string) $row['thoi_gian_cap_nhat'],
                'nguoi_thuc_hien' => $row['id_nguoi_dung'] === null
                    ? 'Hệ thống'
                    : (string) ($row['ho_ten'] ?? ('Người dùng #' . (int) $row['id_nguoi_dung'])),
                'ly_do' => $reason,
                'so_phut' => $minutes,
                'so_phut_lui' => $shiftMinutes,
                'ghi_chu' => $note,
            ];
        }

        return $history;
    }

    /**
     * Gợi ý giờ cất cánh còn trống cho sân bay đi của chuyến: bắt đầu từ giờ cất cánh hiện tại
     * (hoặc giờ hiện tại nếu đã muộn hơn), nhảy từng 10 phút cho tới khi không còn chuyến nào
     * cùng sân bay đi + cùng ngày lệch dưới 10 phút. Tối đa 24 giờ.
     *
     * @return string|null giờ gợi ý dạng `Y-m-d H:i:s`.
     */
    private function suggestFreeSlot(array $flight, $startOverride = null)
    {
        $originId = (int) $flight['id_san_bay_di'];
        $flightId = (int) $flight['id'];
        $now = $this->getCurrentDateTime();

        $base = DateTime::createFromFormat('Y-m-d H:i:s', $now);
        $planned = DateTime::createFromFormat(
            'Y-m-d H:i:s',
            $startOverride !== null ? (string) $startOverride : (string) $flight['gio_du_kien']
        );
        if (!$base) {
            $base = new \DateTime('now');
        }
        $candidate = ($planned && $planned > $base) ? clone $planned : clone $base;

        // Làm tròn lên mốc 10 phút gần nhất cho dễ nhập.
        $add = (10 - ((int) $candidate->format('i') % 10)) % 10;
        if ($add > 0) {
            $candidate->modify('+' . $add . ' minutes');
        }
        $candidate->setTime((int) $candidate->format('H'), (int) $candidate->format('i'), 0);

        $limit = (clone $base)->modify('+1 day');

        $stmt = $this->db->prepare(
            "SELECT COUNT(*) AS total FROM chuyen_bay_ngay
             WHERE id_san_bay_di = ?
               AND id <> ?
               AND trang_thai_tong <> 'Huy'
               AND DATE(gio_du_kien) = DATE(?)
               AND ABS(TIMESTAMPDIFF(MINUTE, gio_du_kien, ?)) < 10"
        );

        while ($candidate <= $limit) {
            $candidateText = $candidate->format('Y-m-d H:i:s');
            $stmt->bind_param('iiss', $originId, $flightId, $candidateText, $candidateText);
            $stmt->execute();
            $total = (int) ($stmt->get_result()->fetch_assoc()['total'] ?? 0);
            if ($total === 0) {
                $stmt->close();

                return $candidateText;
            }
            $candidate->modify('+10 minutes');
        }
        $stmt->close();

        return null;
    }

    /** Thông tin đầy đủ của một chuyến bay để hiển thị trong popup xác nhận cất cánh. */
    private function getDepartureFlight($id)
    {
        $stmt = $this->db->prepare(
            "SELECT cb.*,
                    hk.ten_hang, lt.ma_loai, lt.suc_chua_hanh_khach, lt.tai_trong_cat_canh_mtow,
                    sbdi.ma_iata AS ma_di, sbdi.ten_san_bay AS ten_di,
                    sbde.ma_iata AS ma_den, sbde.ten_san_bay AS ten_den,
                    IF(g.id IS NULL, 'Chưa phân công', CONCAT('Cửa ', g.so_cua)) AS cua_ra,
                    IF(q.id IS NULL, 'Chưa phân công', CONCAT('Quầy ', q.so_quay)) AS quay_checkin,
                    IF(b.id IS NULL, 'Chưa phân công', CONCAT('Băng chuyền ', b.so_bang_chuyen)) AS bang_chuyen,
                     COALESCE(gn.ma_nha_ga, qn.ma_nha_ga, bn.ma_nha_ga) AS terminal_code,
                     COALESCE(gn.ten_nha_ga, qn.ten_nha_ga, bn.ten_nha_ga) AS terminal_name
             FROM chuyen_bay_ngay cb
             JOIN hang_hang_khong hk ON hk.id = cb.id_hang_hang_khong
             JOIN loai_tau_bay lt ON lt.id = cb.id_loai_tau_bay
             JOIN san_bay sbdi ON sbdi.id = cb.id_san_bay_di
             JOIN san_bay sbde ON sbde.id = cb.id_san_bay_den
             LEFT JOIN cua_ra_may_bay g ON g.id = cb.id_cua_ra_may_bay
             LEFT JOIN quay_checkin q ON q.id = cb.id_quay_checkin
             LEFT JOIN bang_chuyen_hanh_ly b ON b.id = cb.id_bang_chuyen
              LEFT JOIN nha_ga gn ON gn.id = g.id_nha_ga
              LEFT JOIN nha_ga qn ON qn.id = q.id_nha_ga
              LEFT JOIN nha_ga bn ON bn.id = b.id_nha_ga
             WHERE cb.id = ?"
        );
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $flight = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $flight ?: null;
    }

    /**
     * Các chuyến trùng lịch cất cánh với chuyến đang xét (cùng sân bay đi, cùng ngày,
     * lệch dưới 10 phút, chưa bị hủy) — hiển thị dạng danh sách trong popup.
     * `$plannedOverride` cho phép kiểm tra theo giờ cất cánh thực tế (ví dụ cất cánh sớm).
     */
    private function findDepartureConflicts(array $flight, $plannedOverride = null)
    {
        $stmt = $this->db->prepare(
            "SELECT cb.id, cb.so_hieu, cb.gio_du_kien, cb.trang_thai_tong, sb.ma_iata
             FROM chuyen_bay_ngay cb
             JOIN san_bay sb ON sb.id = cb.id_san_bay_di
             WHERE cb.id_san_bay_di = ?
               AND cb.id <> ?
               AND cb.trang_thai_tong <> 'Huy'
               AND DATE(cb.gio_du_kien) = DATE(?)
               AND ABS(TIMESTAMPDIFF(MINUTE, cb.gio_du_kien, ?)) < 10
             ORDER BY cb.gio_du_kien ASC, cb.id ASC"
        );
        $originId = (int) $flight['id_san_bay_di'];
        $flightId = (int) $flight['id'];
        $planned = $plannedOverride !== null ? (string) $plannedOverride : (string) $flight['gio_du_kien'];
        $stmt->bind_param('iiss', $originId, $flightId, $planned, $planned);
        $stmt->execute();
        $conflicts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $conflicts ?: [];
    }

    /**
     * Lưu các chuyến đã hạ cánh vào bảng `lich_su_chuyen_bay` (mỗi chuyến một dòng, không trùng):
     * hãng hàng không, số hiệu, tuyến đi/đến, giờ cất cánh, giờ hạ cánh, tổng giờ bay, số hành khách, kg hàng hóa.
     *
     * @return int số chuyến vừa được lưu vào lịch sử.
     */
    public function archiveLandedFlights()
    {
        // Đồng bộ lại giờ của các chuyến đã lưu lịch sử theo giờ hiện tại của chuyến,
        // để cột Cất cánh / Hạ cánh / Tổng giờ bay ở "Lịch sử bay" luôn khớp với
        // "Danh sách chuyến bay" (kể cả khi chuyến được điều chỉnh sau khi đã lưu lịch sử).
        $this->db->query(
            "UPDATE lich_su_chuyen_bay ls
             JOIN chuyen_bay_ngay cb ON cb.id = ls.id_chuyen_bay
             SET ls.gio_cat_canh = cb.gio_du_kien,
                 ls.gio_ha_canh = cb.gio_thuc_te,
                 ls.tong_gio_bay = ROUND(GREATEST(TIMESTAMPDIFF(MINUTE, cb.gio_du_kien, cb.gio_thuc_te), 0) / 60, 2)
             WHERE cb.trang_thai_tong = 'Ha_Canh'
               AND cb.gio_thuc_te IS NOT NULL
               AND (ls.gio_cat_canh <> cb.gio_du_kien
                    OR ls.gio_ha_canh <> cb.gio_thuc_te
                    OR ls.tong_gio_bay <> ROUND(GREATEST(TIMESTAMPDIFF(MINUTE, cb.gio_du_kien, cb.gio_thuc_te), 0) / 60, 2))"
        );

        $this->db->query(
            "INSERT INTO lich_su_chuyen_bay
                (id_chuyen_bay, so_hieu, id_hang_hang_khong, ten_hang,
                 id_san_bay_di, ma_di, ten_san_bay_di, id_san_bay_den, ma_den, ten_san_bay_den,
                 gio_cat_canh, gio_ha_canh, tong_gio_bay, so_hanh_khach, khoi_luong_hang_kg, thoi_diem_luu)
             SELECT cb.id, cb.so_hieu, cb.id_hang_hang_khong, hk.ten_hang,
                    cb.id_san_bay_di, sbdi.ma_iata, sbdi.ten_san_bay,
                    cb.id_san_bay_den, sbde.ma_iata, sbde.ten_san_bay,
                    cb.gio_du_kien, cb.gio_thuc_te,
                    ROUND(GREATEST(TIMESTAMPDIFF(MINUTE, cb.gio_du_kien, cb.gio_thuc_te), 0) / 60, 2),
                    cb.luong_hanh_khach, ROUND(cb.luong_hang_hoa_tan * 1000, 2), NOW()
             FROM chuyen_bay_ngay cb
             JOIN hang_hang_khong hk ON hk.id = cb.id_hang_hang_khong
             JOIN san_bay sbdi ON sbdi.id = cb.id_san_bay_di
             JOIN san_bay sbde ON sbde.id = cb.id_san_bay_den
             LEFT JOIN lich_su_chuyen_bay ls ON ls.id_chuyen_bay = cb.id
             WHERE cb.trang_thai_tong = 'Ha_Canh'
               AND cb.gio_thuc_te IS NOT NULL
               AND ls.id IS NULL"
        );

        return (int) $this->db->affected_rows;
    }

    /** Dữ liệu trang "Lịch sử bay": bộ lọc + phân trang + số liệu tổng hợp. */
    public function handleFlightHistory()
    {
        $filters = [
            'keyword' => trim((string) ($_GET['keyword'] ?? '')),
            'id_san_bay_di' => max(0, (int) ($_GET['id_san_bay_di'] ?? 0)),
            'id_san_bay_den' => max(0, (int) ($_GET['id_san_bay_den'] ?? 0)),
            'date' => trim((string) ($_GET['date'] ?? '')),
        ];
        if ($filters['date'] !== '' && !$this->isValidDate($filters['date'])) {
            $filters['date'] = '';
        }

        // Bổ sung các chuyến đã hạ cánh trước khi mở trang (nếu chưa có trong lịch sử).
        $newlyArchived = $this->archiveLandedFlights();

        $perPage = 10;
        $total = $this->countFlightHistory($filters);
        $totalPages = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, (int) ($_GET['p'] ?? 1)), $totalPages);

        $summary = $this->db->query(
            'SELECT COUNT(*) AS tong_chuyen,
                    COALESCE(SUM(so_hanh_khach), 0) AS tong_hanh_khach,
                    COALESCE(SUM(khoi_luong_hang_kg), 0) AS tong_hang_kg,
                    COALESCE(SUM(tong_gio_bay), 0) AS tong_gio_bay
             FROM lich_su_chuyen_bay'
        )->fetch_assoc();

        return [
            'rows' => $this->getFlightHistory($filters, $perPage, ($page - 1) * $perPage),
            'filters' => $filters,
            'airports' => $this->getOptions('SELECT id, ma_iata, ten_san_bay AS label FROM san_bay ORDER BY ma_iata'),
            'page' => $page,
            'perPage' => $perPage,
            'total' => $total,
            'totalPages' => $totalPages,
            'summary' => $summary ?: ['tong_chuyen' => 0, 'tong_hanh_khach' => 0, 'tong_hang_kg' => 0, 'tong_gio_bay' => 0],
            'newlyArchived' => $newlyArchived,
        ];
    }

    private function getFlightHistory($filters, $limit, $offset)
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM lich_su_chuyen_bay
             WHERE ' . $this->historyFilterSql() . '
             ORDER BY gio_ha_canh DESC, id DESC
             LIMIT ? OFFSET ?'
        );
        $keyword = $filters['keyword'];
        $like = '%' . $keyword . '%';
        $originId = $filters['id_san_bay_di'];
        $destinationId = $filters['id_san_bay_den'];
        $date = $filters['date'];
        $stmt->bind_param(
            'sssssssiiiissii',
            $keyword, $like, $like, $like, $like, $like, $like,
            $originId, $originId, $destinationId, $destinationId, $date, $date, $limit, $offset
        );
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $rows;
    }

    private function countFlightHistory($filters)
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) AS total FROM lich_su_chuyen_bay WHERE ' . $this->historyFilterSql()
        );
        $keyword = $filters['keyword'];
        $like = '%' . $keyword . '%';
        $originId = $filters['id_san_bay_di'];
        $destinationId = $filters['id_san_bay_den'];
        $date = $filters['date'];
        $stmt->bind_param(
            'sssssssiiiiss',
            $keyword, $like, $like, $like, $like, $like, $like,
            $originId, $originId, $destinationId, $destinationId, $date, $date
        );
        $stmt->execute();
        $total = (int) $stmt->get_result()->fetch_assoc()['total'];
        $stmt->close();

        return $total;
    }

    private function historyFilterSql()
    {
        return "(? = '' OR so_hieu LIKE ? OR ten_hang LIKE ? OR ma_di LIKE ? OR ma_den LIKE ? OR ten_san_bay_di LIKE ? OR ten_san_bay_den LIKE ?)
                AND (? = 0 OR id_san_bay_di = ?)
                AND (? = 0 OR id_san_bay_den = ?)
                AND (? = '' OR DATE(gio_ha_canh) = ?)";
    }

    /** Ghi một dòng nhật ký riêng cho từng chuyến bay (thêm / sinh tự động / ghi đè / sửa / hủy). */
    private function logFlightAction($flightId, $action, $oldStatus, $newStatus, $note = null, $userId = null)
    {
        $flightId = (int) $flightId;
        if ($flightId <= 0) {
            return;
        }
        $action = isset(self::FLIGHT_LOG_ACTIONS[$action]) ? (string) $action : 'Sua';
        $oldStatus = $oldStatus === null || $oldStatus === '' ? null : (string) $oldStatus;
        $newStatus = $newStatus === null || $newStatus === '' ? 'Dang_Mo' : (string) $newStatus;
        $note = $note === null ? null : mb_substr((string) $note, 0, 255, 'UTF-8');
        $userId = $userId === null || (int) $userId <= 0 ? null : (int) $userId;

        $stmt = $this->db->prepare(
            'INSERT INTO nhat_ky_trang_thai_bay
                (id_chuyen_bay, id_nguoi_dung, hanh_dong, trang_thai, trang_thai_cu, ghi_chu, thoi_gian_cap_nhat)
             VALUES (?, ?, ?, ?, ?, ?, NOW())'
        );
        // id_nguoi_dung = NULL: hệ thống tự động (batch job sinh chuyến).
        $stmt->bind_param('iissss', $flightId, $userId, $action, $newStatus, $oldStatus, $note);
        $stmt->execute();
        $stmt->close();
    }

    private function getFlightChanges($oldFlight, $newFlight)
    {
        $fields = [
            'so_hieu' => 'Mã chuyến bay',
            'id_hang_hang_khong' => 'Hãng hàng không',
            'id_loai_tau_bay' => 'Loại tàu bay',
            'id_san_bay_di' => 'Sân bay đi',
            'id_san_bay_den' => 'Sân bay đến',
            'id_cua_ra_may_bay' => 'Cửa ra máy bay',
            'id_quay_checkin' => 'Quầy check-in',
            'id_bang_chuyen' => 'Băng chuyền hành lý',
            'gio_du_kien' => 'Giờ dự kiến',
            'gio_thuc_te' => 'Giờ thực tế',
            'luong_hanh_khach' => 'Số hành khách',
            'luong_hang_hoa_tan' => 'Hành lý',
            'trang_thai_tong' => 'Trạng thái',
        ];
        $changes = [];

        foreach ($fields as $field => $label) {
            $oldValue = $oldFlight[$field] ?? null;
            $newValue = $newFlight[$field] ?? null;
            if ($this->normalizeAuditValue($field, $oldValue) === $this->normalizeAuditValue($field, $newValue)) {
                continue;
            }

            $oldDisplay = $this->formatAuditValue($field, $oldValue);
            $newDisplay = $this->formatAuditValue($field, $newValue);
            $changes[] = $label . ': ' . $oldDisplay . ' -> ' . $newDisplay;
        }

        return $changes;
    }

    private function normalizeAuditValue($field, $value)
    {
        if ($value === null || $value === '') {
            return null;
        }
        if ($field === 'luong_hang_hoa_tan') {
            return number_format((float) $value, 2, '.', '');
        }
        if ($field === 'luong_hanh_khach' || strpos($field, 'id_') === 0) {
            return (string) (int) $value;
        }

        return (string) $value;
    }

    private function formatAuditValue($field, $value)
    {
        if ($value === null || $value === '') {
            return strpos($field, 'id_') === 0 ? 'Chưa phân công' : 'Chưa cập nhật';
        }
        if ($field === 'gio_du_kien' || $field === 'gio_thuc_te') {
            return date('d/m/Y H:i', strtotime($value));
        }
        if ($field === 'luong_hang_hoa_tan') {
            return rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.') . ' tấn';
        }
        if ($field === 'trang_thai_tong') {
            return self::STATUSES[$value] ?? (string) $value;
        }
        if (strpos($field, 'id_') !== 0) {
            return (string) $value;
        }

        $queries = [
            'id_hang_hang_khong' => 'SELECT ten_hang AS label FROM hang_hang_khong WHERE id = ?',
            'id_loai_tau_bay' => 'SELECT ma_loai AS label FROM loai_tau_bay WHERE id = ?',
            'id_san_bay_di' => 'SELECT ma_iata AS label FROM san_bay WHERE id = ?',
            'id_san_bay_den' => 'SELECT ma_iata AS label FROM san_bay WHERE id = ?',
            'id_cua_ra_may_bay' => "SELECT CONCAT(sb.ma_iata, ' - Cửa ', c.so_cua) AS label
                                    FROM cua_ra_may_bay c JOIN nha_ga ng ON ng.id = c.id_nha_ga
                                    JOIN san_bay sb ON sb.id = ng.id_san_bay WHERE c.id = ?",
            'id_quay_checkin' => "SELECT CONCAT(sb.ma_iata, ' - Quầy ', q.so_quay) AS label
                                  FROM quay_checkin q JOIN nha_ga ng ON ng.id = q.id_nha_ga
                                  JOIN san_bay sb ON sb.id = ng.id_san_bay WHERE q.id = ?",
            'id_bang_chuyen' => "SELECT CONCAT(sb.ma_iata, ' - Băng chuyền ', b.so_bang_chuyen) AS label
                                 FROM bang_chuyen_hanh_ly b JOIN nha_ga ng ON ng.id = b.id_nha_ga
                                 JOIN san_bay sb ON sb.id = ng.id_san_bay WHERE b.id = ?",
        ];
        if (!isset($queries[$field])) {
            return (string) $value;
        }

        $id = (int) $value;
        $stmt = $this->db->prepare($queries[$field]);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $row['label'] ?? ('#' . $id);
    }

    private function getOptions($sql)
    {
        $result = $this->db->query($sql);
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Tìm chuyến khác đang dùng cùng quầy check-in mà khung làm thủ tục trùng nhau.
     * Khung làm thủ tục: mở trước giờ cất cánh 2-3 tiếng, đóng trước 40 phút.
     * Lấy khung rộng nhất (3 tiếng trước -> 40 phút trước) nên hai chuyến bị coi là trùng khung khi
     * giờ cất cánh lệch nhau dưới 140 phút (180 - 40). Bỏ qua chuyến đã hủy và chính chuyến đang sửa.
     */
    private function findCheckinConflict($checkinId, $plannedDateTime, $exceptId = 0)
    {
        $stmt = $this->db->prepare(
            "SELECT cb.so_hieu, cb.gio_du_kien, qc.so_quay, sb.ma_iata
             FROM chuyen_bay_ngay cb
             JOIN quay_checkin qc ON qc.id = cb.id_quay_checkin
             JOIN nha_ga ng ON ng.id = qc.id_nha_ga
             JOIN san_bay sb ON sb.id = ng.id_san_bay
             WHERE cb.id_quay_checkin = ?
               AND cb.id <> ?
               AND cb.trang_thai_tong <> 'Huy'
               AND ABS(TIMESTAMPDIFF(MINUTE, cb.gio_du_kien, ?)) < 140
             ORDER BY ABS(TIMESTAMPDIFF(MINUTE, cb.gio_du_kien, ?)) ASC, cb.id ASC
             LIMIT 1"
        );
        $stmt->bind_param('iiss', $checkinId, $exceptId, $plannedDateTime, $plannedDateTime);
        $stmt->execute();
        $conflict = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $conflict ?: null;
    }

    /**
     * Tìm chuyến khác cùng sân bay đi, cùng ngày, giờ cất cánh lệch dưới 10 phút (bỏ qua chuyến đã hủy
     * và chính chuyến đang sửa).
     */
    private function findDepartureConflict($originId, $plannedDateTime, $exceptId = 0)
    {
        $stmt = $this->db->prepare(
            "SELECT cb.so_hieu, cb.gio_du_kien, sb.ma_iata
             FROM chuyen_bay_ngay cb
             JOIN san_bay sb ON sb.id = cb.id_san_bay_di
             WHERE cb.id_san_bay_di = ?
               AND cb.id <> ?
               AND cb.trang_thai_tong <> 'Huy'
               AND DATE(cb.gio_du_kien) = DATE(?)
               AND ABS(TIMESTAMPDIFF(MINUTE, cb.gio_du_kien, ?)) < 10
             ORDER BY ABS(TIMESTAMPDIFF(MINUTE, cb.gio_du_kien, ?)) ASC, cb.id ASC
             LIMIT 1"
        );
        $stmt->bind_param('iisss', $originId, $exceptId, $plannedDateTime, $plannedDateTime, $plannedDateTime);
        $stmt->execute();
        $conflict = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $conflict ?: null;
    }

    /**
     * Chuyến đã tồn tại chưa: trùng khi cùng số hiệu + sân bay đi + sân bay đến + ngày cất cánh
     * (không xét giờ), bỏ qua chuyến đang sửa ($exceptId).
     */
    public function flightExistsOnDate($flightNumber, $originId, $destinationId, $date, $exceptId = 0)
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) AS total FROM chuyen_bay_ngay
             WHERE so_hieu = ? AND id_san_bay_di = ? AND id_san_bay_den = ? AND DATE(gio_du_kien) = ? AND id <> ?'
        );
        $stmt->bind_param('siisi', $flightNumber, $originId, $destinationId, $date, $exceptId);
        $stmt->execute();
        $exists = (int) $stmt->get_result()->fetch_assoc()['total'] > 0;
        $stmt->close();

        return $exists;
    }

    private function generateFlightNumber(int $airlineId = 0): string
    {
        // Xác định tiền tố theo mã IATA thực tế của hãng hàng không (VN, VJ, QH...)
        $prefix = 'VN';
        if ($airlineId > 0) {
            $stmt = $this->db->prepare('SELECT ma_iata FROM hang_hang_khong WHERE id = ?');
            if ($stmt) {
                $stmt->bind_param('i', $airlineId);
                $stmt->execute();
                $res = $stmt->get_result();
                if ($row = $res->fetch_assoc()) {
                    $prefix = strtoupper(trim((string) $row['ma_iata']));
                }
                $stmt->close();
            }
        }

        // Lấy danh sách số hiệu chuyến bay đã có của hãng
        $stmt = $this->db->prepare('SELECT so_hieu FROM chuyen_bay_ngay WHERE so_hieu LIKE ? FOR UPDATE');
        $likePattern = $prefix . '%';
        $stmt->bind_param('s', $likePattern);
        $stmt->execute();
        $result = $stmt->get_result();
        $usedNumbers = [];
        while ($row = $result->fetch_assoc()) {
            $usedNumbers[$row['so_hieu']] = true;
        }
        $stmt->close();

        // Tự động sinh số hiệu chuẩn ngành hàng không (VD: VN101, VJ101, QH101...)
        for ($number = 101; $number <= 9999; $number++) {
            $flightNumber = $prefix . $number;
            if (!isset($usedNumbers[$flightNumber])) {
                return $flightNumber;
            }
        }

        return $prefix . mt_rand(1000, 9999);
    }

    private function ensureCsrf()
    {
        if (empty($_SESSION['chuyen_bay_csrf'])) {
            $_SESSION['chuyen_bay_csrf'] = bin2hex(random_bytes(32));
        }
    }

    private function isPost()
    {
        return isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST';
    }

    private function setNamedFlash($key, $type, $message)
    {
        $_SESSION[$key] = ['type' => $type, 'message' => $message];
    }

    private function pullNamedFlash($key)
    {
        if (empty($_SESSION[$key])) {
            return null;
        }
        $flash = $_SESSION[$key];
        unset($_SESSION[$key]);

        return $flash;
    }

    private function redirectTo($path, array $params = [])
    {
        $url = BASE_URL . $path;
        if ($params) {
            $url .= (strpos($url, '?') === false ? '?' : '&') . http_build_query($params);
        }
        header('Location: ' . $url);
        exit();
    }

    private function getAircraftCapacity($aircraftId)
    {
        $stmt = $this->db->prepare(
            'SELECT tai_trong_cat_canh_mtow, suc_chua_hanh_khach
             FROM loai_tau_bay WHERE id = ?'
        );
        $stmt->bind_param('i', $aircraftId);
        $stmt->execute();
        $aircraft = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $aircraft ?: null;
    }

    private function validateResourceAirport($resourceId, $table, $airportId)
    {
        if ($resourceId === null) {
            return;
        }

        $allowedTables = ['cua_ra_may_bay', 'quay_checkin', 'bang_chuyen_hanh_ly'];
        if (!in_array($table, $allowedTables, true)) {
            throw new \RuntimeException('Loại tài nguyên sân bay không hợp lệ.');
        }

        $stmt = $this->db->prepare(
            "SELECT COUNT(*) AS total FROM `$table` r
             JOIN nha_ga ng ON ng.id = r.id_nha_ga
             WHERE r.id = ? AND ng.id_san_bay = ? AND r.trang_thai = 'Hoat_Dong'"
        );
        $stmt->bind_param('ii', $resourceId, $airportId);
        $stmt->execute();
        $isValid = (int) $stmt->get_result()->fetch_assoc()['total'] > 0;
        $stmt->close();

        if (!$isValid) {
            throw new \RuntimeException('Cửa, quầy check-in và băng chuyền phải thuộc sân bay đi đang chọn.');
        }
    }

    private function optionalId($value)
    {
        if ($value === '' || $value === null) {
            return null;
        }
        $id = filter_var($value, FILTER_VALIDATE_INT);
        if ($id === false || $id <= 0) {
            throw new \RuntimeException('Giá trị tài nguyên được chọn không hợp lệ.');
        }

        return $id;
    }

    private function isValidReason($reason)
    {
        $length = preg_match_all('/./us', $reason);
        return $reason !== '' && $length !== false && $length <= 255;
    }

    /** Đổi số phút thành chuỗi dễ đọc: 45 -> "45 phút", 90 -> "1 giờ 30 phút". */
    private function formatDurationMinutes($minutes)
    {
        $minutes = max(0, (int) $minutes);
        $hours = intdiv($minutes, 60);
        $rest = $minutes % 60;
        $parts = [];
        if ($hours > 0) {
            $parts[] = $hours . ' giờ';
        }
        if ($rest > 0 || !$parts) {
            $parts[] = $rest . ' phút';
        }

        return implode(' ', $parts);
    }

    private function normalizeDateTime($value)
    {
        $dateTime = DateTime::createFromFormat('!Y-m-d\TH:i', $value);
        $errors = DateTime::getLastErrors();
        if (!$dateTime || ($errors !== false && ($errors['warning_count'] || $errors['error_count']))
            || $dateTime->format('Y-m-d\TH:i') !== $value) {
            return null;
        }

        return $dateTime->format('Y-m-d H:i:s');
    }

    private function isValidDate($value)
    {
        if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $matches)) {
            return false;
        }

        return checkdate((int) $matches[2], (int) $matches[3], (int) $matches[1]);
    }

    private function setFlash($type, $message)
    {
        $_SESSION['chuyen_bay_flash'] = ['type' => $type, 'message' => $message];
    }

    private function pullFlash()
    {
        if (empty($_SESSION['chuyen_bay_flash'])) {
            return null;
        }
        $flash = $_SESSION['chuyen_bay_flash'];
        unset($_SESSION['chuyen_bay_flash']);

        return $flash;
    }

    private function redirect($page = 'chuyen_bay', $id = 0, $popup = false, array $params = [])
    {
        $targets = [
            'chuyen_bay' => ['/index.php', ['page' => 'chuyen_bay']],
            'chuyen_bay_them' => ['/views/pages/chuyen_bay/them.php', []],
            'chuyen_bay_sua' => ['/views/pages/chuyen_bay/sua.php', []],
            'chuyen_bay_huy' => ['/views/pages/chuyen_bay/huy.php', []],
        ];
        $aliases = [
            'chuyen_bay_modal' => 'chuyen_bay_them',
            'chuyen_bay_them_saved' => 'chuyen_bay_them',
            'chuyen_bay_edit_modal' => 'chuyen_bay_sua',
            'chuyen_bay_cancel_modal' => 'chuyen_bay_huy',
        ];
        $page = $aliases[$page] ?? $page;
        if (!isset($targets[$page])) {
            $page = 'chuyen_bay';
        }

        [$path, $query] = $targets[$page];
        if ($id > 0 && in_array($page, ['chuyen_bay_sua', 'chuyen_bay_huy'], true)) {
            $query['id'] = $id;
        }
        // Form mở trong popup: giữ nguyên chế độ popup khi quay lại trang form.
        if ($popup && $page !== 'chuyen_bay') {
            $query['popup'] = 1;
        }
        $query = array_merge($query, $params);
        $url = BASE_URL . $path . ($query ? '?' . http_build_query($query) : '');
        header('Location: ' . $url);
        exit();
    }
}

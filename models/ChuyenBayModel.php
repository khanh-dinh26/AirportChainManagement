<?php
class ChuyenBayModel {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }
    public function getChuyenBays($keyword = '', $date = '', $status = '', $page = 1, $limit = 10) {
        $offset = max(0, ((int)$page - 1) * (int)$limit);
        $limit  = max(1, (int)$limit);
        $where  = ["1=1"];
        $params = [];
        $types  = "";

        if (!empty($keyword)) {
            $where[]  = "(cb.so_hieu LIKE ? OR hhk.ten_hang LIKE ?)";
            $params[] = "%{$keyword}%";
            $params[] = "%{$keyword}%";
            $types   .= "ss";
        }

        if (!empty($date)) {
            $where[]  = "DATE(cb.gio_du_kien) = ?";
            $params[] = $date;
            $types   .= "s";
        }

        if (!empty($status)) {
            $where[]  = "cb.trang_thai_tong = ?";
            $params[] = $status;
            $types   .= "s";
        }

        $whereClause = implode(' AND ', $where);

        $sql = "SELECT cb.*, 
                       hhk.ten_hang, hhk.ma_iata AS ma_hang,
                       ltb.ma_loai AS loai_may_bay,
                       sb_di.ma_iata AS ma_sb_di, sb_di.ten_san_bay AS ten_sb_di,
                       sb_den.ma_iata AS ma_sb_den, sb_den.ten_san_bay AS ten_sb_den,
                       gate.so_cua, quay.so_quay, belt.so_bang_chuyen
                FROM chuyen_bay_ngay cb
                LEFT JOIN hang_hang_khong hhk ON cb.id_hang_hang_khong = hhk.id
                LEFT JOIN loai_tau_bay ltb ON cb.id_loai_tau_bay = ltb.id
                LEFT JOIN san_bay sb_di ON cb.id_san_bay_di = sb_di.id
                LEFT JOIN san_bay sb_den ON cb.id_san_bay_den = sb_den.id
                LEFT JOIN cua_ra_may_bay gate ON cb.id_cua_ra_may_bay = gate.id
                LEFT JOIN quay_checkin quay ON cb.id_quay_checkin = quay.id
                LEFT JOIN bang_chuyen_hanh_ly belt ON cb.id_bang_chuyen = belt.id
                WHERE {$whereClause}
                ORDER BY cb.id DESC
                LIMIT {$limit} OFFSET {$offset}";

        if (!empty($params)) {
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
        } else {
            $result = $this->db->query($sql);
        }

        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function getTotalChuyenBays($keyword = '', $date = '', $status = '') {
        $where  = ["1=1"];
        $params = [];
        $types  = "";

        if (!empty($keyword)) {
            $where[]  = "(cb.so_hieu LIKE ? OR hhk.ten_hang LIKE ?)";
            $params[] = "%{$keyword}%";
            $params[] = "%{$keyword}%";
            $types   .= "ss";
        }

        if (!empty($date)) {
            $where[]  = "DATE(cb.gio_du_kien) = ?";
            $params[] = $date;
            $types   .= "s";
        }

        if (!empty($status)) {
            $where[]  = "cb.trang_thai_tong = ?";
            $params[] = $status;
            $types   .= "s";
        }

        $whereClause = implode(' AND ', $where);

        $sql = "SELECT COUNT(*) AS total FROM chuyen_bay_ngay cb
                LEFT JOIN hang_hang_khong hhk ON cb.id_hang_hang_khong = hhk.id
                WHERE {$whereClause}";

        if (!empty($params)) {
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
        } else {
            $row = $this->db->query($sql)->fetch_assoc();
        }

        return $row['total'] ?? 0;
    }

    public function getById($id) {
        $id  = (int)$id;
        $sql = "SELECT cb.*, hhk.ten_hang,
                       sb_di.ten_san_bay AS ten_sb_di, sb_di.ma_iata AS ma_sb_di,
                       sb_den.ten_san_bay AS ten_sb_den, sb_den.ma_iata AS ma_sb_den
                FROM chuyen_bay_ngay cb
                LEFT JOIN hang_hang_khong hhk ON cb.id_hang_hang_khong = hhk.id
                LEFT JOIN san_bay sb_di ON cb.id_san_bay_di = sb_di.id
                LEFT JOIN san_bay sb_den ON cb.id_san_bay_den = sb_den.id
                WHERE cb.id = {$id}";
        $res = $this->db->query($sql);
        return $res ? $res->fetch_assoc() : null;
    }

    public function create($data) {
        $sql = "INSERT INTO chuyen_bay_ngay (
                    id_lich_mua, so_hieu, id_hang_hang_khong, id_loai_tau_bay, 
                    id_san_bay_di, id_san_bay_den, id_cua_ra_may_bay, id_quay_checkin, 
                    id_bang_chuyen, gio_du_kien, gio_thuc_te, luong_hanh_khach, 
                    luong_hang_hoa_tan, trang_thai_tong
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->db->prepare($sql);

        $idLichMua  = !empty($data['id_lich_mua']) ? (int)$data['id_lich_mua'] : null;
        $soHieu     = $data['so_hieu'];
        $idHang     = (int)$data['id_hang_hang_khong'];
        $idTau      = (int)$data['id_loai_tau_bay'];
        $idSBDi     = (int)$data['id_san_bay_di'];
        $idSBDen    = (int)$data['id_san_bay_den'];
        $idGate     = !empty($data['id_cua_ra_may_bay']) ? (int)$data['id_cua_ra_may_bay'] : null;
        $idQuay     = !empty($data['id_quay_checkin']) ? (int)$data['id_quay_checkin'] : null;
        $idBelt     = !empty($data['id_bang_chuyen']) ? (int)$data['id_bang_chuyen'] : null;
        $gioDuKien  = $data['gio_du_kien'];
        $gioThucTe  = !empty($data['gio_thuc_te']) ? $data['gio_thuc_te'] : null;
        $luongKhach = (int)($data['luong_hanh_khach'] ?? 0);
        $luongHang  = (float)($data['luong_hang_hoa_tan'] ?? 0.00);
        $trangThai  = 'Len_Lich';

        $stmt->bind_param(
            "isiiiiiiissids",
            $idLichMua, $soHieu, $idHang, $idTau,
            $idSBDi, $idSBDen, $idGate, $idQuay,
            $idBelt, $gioDuKien, $gioThucTe, $luongKhach,
            $luongHang, $trangThai
        );

        return $stmt->execute();
    }

    public function updateDieuHanh($id, $data, $userId, $ghiChuLog = '') {
        $id = (int)$id;
        $currentFlight = $this->getById($id);
        if (!$currentFlight) return false;

        $oldStatus = $currentFlight['trang_thai_tong'];
        $newStatus = $data['trang_thai_tong'];

        $sql = "UPDATE chuyen_bay_ngay SET
                    id_lich_mua        = ?,
                    so_hieu            = ?,
                    id_hang_hang_khong = ?,
                    id_loai_tau_bay    = ?,
                    id_san_bay_di      = ?,
                    id_san_bay_den     = ?,
                    id_cua_ra_may_bay  = ?,
                    id_quay_checkin    = ?,
                    id_bang_chuyen     = ?,
                    gio_du_kien        = ?,
                    gio_thuc_te        = ?,
                    luong_hanh_khach   = ?,
                    luong_hang_hoa_tan = ?,
                    trang_thai_tong    = ?
                WHERE id = ?";

        $stmt = $this->db->prepare($sql);

        $idLichMua  = !empty($data['id_lich_mua']) ? (int)$data['id_lich_mua'] : null;
        $soHieu     = $data['so_hieu'];
        $idHang     = (int)$data['id_hang_hang_khong'];
        $idTau      = (int)$data['id_loai_tau_bay'];
        $idSBDi     = (int)$data['id_san_bay_di'];
        $idSBDen    = (int)$data['id_san_bay_den'];
        $idGate     = !empty($data['id_cua_ra_may_bay']) ? (int)$data['id_cua_ra_may_bay'] : null;
        $idQuay     = !empty($data['id_quay_checkin']) ? (int)$data['id_quay_checkin'] : null;
        $idBelt     = !empty($data['id_bang_chuyen']) ? (int)$data['id_bang_chuyen'] : null;
        $gioDuKien  = $data['gio_du_kien'];
        $gioThucTe  = !empty($data['gio_thuc_te']) ? $data['gio_thuc_te'] : null;
        $luongKhach = (int)($data['luong_hanh_khach'] ?? 0);
        $luongHang  = (float)($data['luong_hang_hoa_tan'] ?? 0.00);

        $stmt->bind_param(
            "isiiiiiiissidsi",
            $idLichMua, $soHieu, $idHang, $idTau,
            $idSBDi, $idSBDen, $idGate, $idQuay,
            $idBelt, $gioDuKien, $gioThucTe, $luongKhach,
            $luongHang, $newStatus, $id
        );

        $updated = $stmt->execute();

        if ($updated && $oldStatus !== $newStatus) {
            $this->addLog($id, $userId, $newStatus, $ghiChuLog);
        }

        return $updated;
    }

    public function cancelFlight($id, $userId, $lyDo = 'Hủy chuyến theo yêu cầu') {
        $id = (int)$id;
        $currentFlight = $this->getById($id);
        if (!$currentFlight) return false;

        $oldStatus = $currentFlight['trang_thai_tong'];
        $newStatus = 'Huy';

        $sql = "UPDATE chuyen_bay_ngay SET trang_thai_tong = 'Huy' WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id);
        $success = $stmt->execute();

        if ($success && $oldStatus !== $newStatus) {
            $this->addLog($id, $userId, $newStatus, $lyDo);
        }

        return $success;
    }

    public function delete($id) {
        $id  = (int)$id;
        $sql = "DELETE FROM chuyen_bay_ngay WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    public function addLog($idChuyenBay, $userId, $trangThai, $ghiChu = '') {
        $sql = "INSERT INTO nhat_ky_trang_thai_bay 
                (id_chuyen_bay, id_nguoi_dung, trang_thai, thoi_gian_cap_nhat, ghi_chu)
                VALUES (?, ?, ?, NOW(), ?)";
        $stmt = $this->db->prepare($sql);
        $idCB = (int)$idChuyenBay;
        $uId  = (int)$userId;
        $stmt->bind_param("iiss", $idCB, $uId, $trangThai, $ghiChu);
        return $stmt->execute();
    }

    public function getLogsByFlightId($idChuyenBay) {
        $idCB = (int)$idChuyenBay;
        $sql  = "SELECT nk.*, nd.ho_ten AS ten_nguoi_cap_nhat
                 FROM nhat_ky_trang_thai_bay nk
                 LEFT JOIN nguoi_dung nd ON nk.id_nguoi_dung = nd.id
                 WHERE nk.id_chuyen_bay = {$idCB}
                 ORDER BY nk.thoi_gian_cap_nhat DESC";
        $res = $this->db->query($sql);
        return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function getLichBayMuaAll() {
        $sql = "SELECT lm.*, hhk.ten_hang, sb_di.ma_iata AS ma_sb_di, sb_den.ma_iata AS ma_sb_den
                FROM lich_bay_mua lm
                LEFT JOIN hang_hang_khong hhk ON lm.id_hang_hang_khong = hhk.id
                LEFT JOIN san_bay sb_di ON lm.id_san_bay_di = sb_di.id
                LEFT JOIN san_bay sb_den ON lm.id_san_bay_den = sb_den.id
                ORDER BY lm.id DESC";
        $res = $this->db->query($sql);
        return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function createLichBayMua($data) {
        $sql = "INSERT INTO lich_bay_mua (so_hieu, id_hang_hang_khong, id_san_bay_di, id_san_bay_den, tan_suat_tuan, tu_ngay, den_ngay)
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $soHieu  = $data['so_hieu'];
        $idHang  = (int)$data['id_hang_hang_khong'];
        $idSBDi  = (int)$data['id_san_bay_di'];
        $idSBDen = (int)$data['id_san_bay_den'];
        $tanSuat = $data['tan_suat_tuan'];
        $tuNgay  = $data['tu_ngay'];
        $denNgay = $data['den_ngay'];

        $stmt->bind_param("siiisss", $soHieu, $idHang, $idSBDi, $idSBDen, $tanSuat, $tuNgay, $denNgay);
        return $stmt->execute();
    }

    public function deleteLichBayMua($id) {
        $id  = (int)$id;
        $sql = "DELETE FROM lich_bay_mua WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    public function generateDailyFlightsFromSchedule($targetDate) {
        $parsedDate = DateTime::createFromFormat('!Y-m-d', $targetDate);
        if (!$parsedDate || $parsedDate->format('Y-m-d') !== $targetDate) {
            throw new InvalidArgumentException('Ngày tạo chuyến bay không hợp lệ.');
        }

        $schedules = $this->getLichBayMuaAll();
        $generatedCount = 0;
        $weekday = (int) $parsedDate->format('N');
        $tauRes = $this->db->query("SELECT id FROM loai_tau_bay ORDER BY id LIMIT 1");
        $tauBay = $tauRes->fetch_assoc();
        if (!$tauBay && $schedules) {
            throw new RuntimeException('Không có loại tàu bay để tạo chuyến bay.');
        }

        foreach ($schedules as $sch) {
            $tanSuat = trim($sch['tan_suat_tuan']);
            $runsToday = stripos($tanSuat, 'hàng ngày') !== false
                || ($weekday === 7 && stripos($tanSuat, 'chủ nhật') !== false)
                || preg_match('/(?:^|[^\d])' . $weekday . '(?:$|[^\d])/', $tanSuat);

            if ($targetDate >= $sch['tu_ngay'] && $targetDate <= $sch['den_ngay'] && $runsToday) {
                $soHieu  = $sch['so_hieu'];
                $chkSql  = "SELECT COUNT(*) AS cnt FROM chuyen_bay_ngay WHERE so_hieu = ? AND DATE(gio_du_kien) = ?";
                $chkStmt = $this->db->prepare($chkSql);
                $chkStmt->bind_param("ss", $soHieu, $targetDate);
                $chkStmt->execute();
                $row = $chkStmt->get_result()->fetch_assoc();

                if (($row['cnt'] ?? 0) == 0) {
                    $insertData = [
                        'id_lich_mua'        => $sch['id'],
                        'so_hieu'            => $sch['so_hieu'],
                        'id_hang_hang_khong' => $sch['id_hang_hang_khong'],
                        'id_loai_tau_bay'    => $tauBay['id'],
                        'id_san_bay_di'      => $sch['id_san_bay_di'],
                        'id_san_bay_den'     => $sch['id_san_bay_den'],
                        'id_cua_ra_may_bay'  => null,
                        'id_quay_checkin'    => null,
                        'id_bang_chuyen'     => null,
                        'gio_du_kien'        => $targetDate . ' 08:00:00',
                        'gio_thuc_te'       => null,
                        'luong_hanh_khach'   => 0,
                        'luong_hang_hoa_tan' => 0.00,
                        'trang_thai_tong'    => 'Len_Lich'
                    ];
                    if ($this->create($insertData)) {
                        $generatedCount++;
                    }
                }
            }
        }
        return $generatedCount;
    }

    public function getHangHangKhong() {
        $res = $this->db->query("SELECT * FROM hang_hang_khong ORDER BY ten_hang");
        return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function getLoaiTauBay() {
        $res = $this->db->query("SELECT * FROM loai_tau_bay ORDER BY ma_loai");
        return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function getSanBay() {
        $res = $this->db->query("SELECT * FROM san_bay WHERE trang_thai = 'Hoat_Dong' ORDER BY ma_iata");
        return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function getCuaRaMayBay() {
        $res = $this->db->query("SELECT c.*, n.ten_nha_ga FROM cua_ra_may_bay c JOIN nha_ga n ON c.id_nha_ga = n.id WHERE c.trang_thai = 'Hoat_Dong'");
        return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function getQuayCheckin() {
        $res = $this->db->query("SELECT q.*, n.ten_nha_ga FROM quay_checkin q JOIN nha_ga n ON q.id_nha_ga = n.id WHERE q.trang_thai = 'Hoat_Dong'");
        return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function getBangChuyen() {
        $res = $this->db->query("SELECT b.*, n.ten_nha_ga FROM bang_chuyen_hanh_ly b JOIN nha_ga n ON b.id_nha_ga = n.id WHERE b.trang_thai = 'Hoat_Dong'");
        return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function getStatsTotal() {
        $tRes = $this->db->query("SELECT COUNT(*) AS cnt FROM chuyen_bay_ngay");
        $dRes = $this->db->query("SELECT COUNT(*) AS cnt FROM chuyen_bay_ngay WHERE trang_thai_tong = 'Cat_Canh'");
        $aRes = $this->db->query("SELECT COUNT(*) AS cnt FROM chuyen_bay_ngay WHERE trang_thai_tong = 'Ha_Canh'");
        $lRes = $this->db->query("SELECT COUNT(*) AS cnt FROM chuyen_bay_ngay WHERE trang_thai_tong = 'Tri_Hoan'");

        return [
            'total'   => ($tRes && $r = $tRes->fetch_assoc()) ? $r['cnt'] : 0,
            'dep'     => ($dRes && $r = $dRes->fetch_assoc()) ? $r['cnt'] : 0,
            'arr'     => ($aRes && $r = $aRes->fetch_assoc()) ? $r['cnt'] : 0,
            'delayed' => ($lRes && $r = $lRes->fetch_assoc()) ? $r['cnt'] : 0,
        ];
    }
}
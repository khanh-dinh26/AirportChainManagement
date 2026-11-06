<?php
/**
 * ============================================================
 * XUẤT BÁO CÁO DOANH THU RA FILE EXCEL (.XLS)
 * views/pages/ke_toan/xuat_excel.php
 * Phân hệ: Kế toán & Doanh thu
 * Viền mảnh tinh tế (0.5pt), tiêu đề cột cao thoáng rộng rãi (44px)
 * ============================================================
 */

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../controllers/KeToanController.php';

yeu_cau_dang_nhap();
if (!co_quyen_ke_toan()) {
    die('Bạn không có quyền truy cập chức năng xuất báo cáo kế toán.');
}

$keToan = new KeToanController($conn);

// 1. NHẬN CÁC THAM SỐ
$kieu          = isset($_GET['kieu']) && $_GET['kieu'] === 'nam' ? 'nam' : 'thang';
$namHienTai    = (int) date('Y');
$nam           = isset($_GET['nam']) ? (int) $_GET['nam'] : $namHienTai;
$thang         = isset($_GET['thang']) ? (int) $_GET['thang'] : (int) date('n');
if ($thang < 1 || $thang > 12) {
    $thang = (int) date('n');
}
$hang          = isset($_GET['hang']) ? (int) $_GET['hang'] : 0;
$sanBay        = isset($_GET['san_bay']) ? (int) $_GET['san_bay'] : 0;
$trangThai     = isset($_GET['trang_thai']) && in_array($_GET['trang_thai'], ['Da_Thanh_Toan', 'Chua_Thanh_Toan', 'Tat_Ca'], true)
                 ? $_GET['trang_thai']
                 : 'Da_Thanh_Toan';

// Lấy nhãn bộ lọc
$tenHangLoc = 'Tất cả các hãng';
if ($hang > 0) {
    $danhSachHang = $keToan->LayDanhSachHangHangKhong();
    foreach ($danhSachHang as $h) {
        if ((int)$h['id'] === $hang) {
            $tenHangLoc = $h['ten_hang'] . ' (' . ($h['ma_hang'] ?? '') . ')';
            break;
        }
    }
}

$tenSanBayLoc = 'Toàn bộ mạng lưới sân bay';
if ($sanBay > 0) {
    $danhSachSB = $keToan->DanhSachSanBay();
    foreach ($danhSachSB as $sb) {
        if ((int)$sb['id'] === $sanBay) {
            $tenSanBayLoc = $sb['ten_san_bay'] . ' (' . $sb['ma_iata'] . ')';
            break;
        }
    }
}

$nhanTrangThai = $trangThai === 'Da_Thanh_Toan' ? 'Đã thanh toán (Thực thu)' : ($trangThai === 'Chua_Thanh_Toan' ? 'Chờ thanh toán (Công nợ)' : 'Tất cả trạng thái');
$ngayXuat      = date('d/m/Y H:i');

// Thiết lập tên file tải về
if ($kieu === 'nam') {
    $fileName = "Bao_cao_doanh_thu_nam_{$nam}.xls";
    $tieuDe = "BÁO CÁO TỔNG KẾT DOANH THU HÀNG KHÔNG NĂM {$nam}";
    $data12Thang = $keToan->ThongKeDoanhThu12Thang($nam, $hang, $sanBay, $trangThai);
    $dataTheoHang = $keToan->ThongKeDoanhThuTheoHangChiTiet($nam, $sanBay, $trangThai);
    $kpi = $keToan->ThongKeTongQuanDoanhThu($nam, $hang, $sanBay);
} else {
    $fileName = "Bao_cao_doanh_thu_thang_{$thang}_{$nam}.xls";
    $tieuDe = "BẢNG KÊ CHI TIẾT DOANH THU & HÓA ĐƠN THÁNG {$thang}/{$nam}";
    $hoaDonThang = $keToan->LayHoaDonChiTietTheoThang($nam, $thang, $hang, $sanBay, $trangThai);
    $tongDoanhThuThang = 0.0;
    $tongPhiCatHaCanh  = 0.0;
    $tongPhiDo         = 0.0;
    $tongPhiKhach      = 0.0;
    foreach ($hoaDonThang as $hd) {
        $tongDoanhThuThang += (float) $hd['tong_tien'];
        $tongPhiCatHaCanh  += (float) $hd['phi_cat_ha_canh'];
        $tongPhiDo         += (float) $hd['phi_do_tau_bay'];
        $tongPhiKhach      += (float) $hd['phi_phuc_vu_hk'];
    }
}

// 2. HEADERS HTTP TẢI VỀ FILE EXCEL (.XLS)
header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
header("Content-Disposition: attachment; filename=\"{$fileName}\"");
header("Cache-Control: max-age=0, no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// UTF-8 BOM
echo "\xEF\xBB\xBF";
?>
<!DOCTYPE html>
<html xmlns:o="urn:schemas-microsoft-com:office:office"
      xmlns:x="urn:schemas-microsoft-com:office:excel"
      xmlns="http://www.w3.org/TR/REC-html40">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <!--[if gte mso 9]>
    <xml>
        <x:ExcelWorkbook>
            <x:ExcelWorksheets>
                <x:ExcelWorksheet>
                    <x:Name><?= $kieu === 'nam' ? "Nam_{$nam}" : "Thang_{$thang}_{$nam}" ?></x:Name>
                    <x:WorksheetOptions>
                        <x:DisplayGridlines/>
                        <x:FitToPage/>
                        <x:Print>
                            <x:FitWidth>1</x:FitWidth>
                            <x:FitHeight>0</x:FitHeight>
                            <x:ValidPrinterInfo/>
                            <x:PaperSizeIndex>9</x:PaperSizeIndex>
                            <x:CenterHorizontal/>
                        </x:Print>
                        <x:PageSetup>
                            <x:CenterHorizontal/>
                            <x:Header x:Margin="0.2"/>
                            <x:Footer x:Margin="0.2"/>
                            <x:PageMargins x:Bottom="0.4" x:Left="0.4" x:Right="0.4" x:Top="0.4"/>
                        </x:PageSetup>
                    </x:WorksheetOptions>
                </x:ExcelWorksheet>
            </x:ExcelWorksheets>
        </x:ExcelWorkbook>
    </xml>
    <![endif]-->
    <style>
        @page {
            margin: 0.4in 0.4in 0.4in 0.4in;
            mso-horizontal-page-align: center;
        }
        body {
            font-family: 'Times New Roman', Arial, sans-serif;
            font-size: 11pt;
            color: #000000;
        }
        table {
            border-collapse: collapse;
            width: 100%;
            margin-left: auto;
            margin-right: auto;
        }
        .title {
            font-size: 14pt;
            font-weight: bold;
            text-align: center;
            color: #000000;
        }
        .agency {
            font-size: 10pt;
            font-weight: bold;
            text-transform: uppercase;
            color: #000000;
        }
        .meta-info {
            font-size: 9.5pt;
            font-style: italic;
            color: #000000;
        }
        .table-data {
            border-collapse: collapse;
            width: 100%;
            margin-top: 6px;
        }
        .table-data th {
            background-color: #ffffff;
            color: #000000;
            font-weight: bold;
            border: 0.5pt solid #555555;
            text-align: center;
            vertical-align: middle;
            padding: 8px 6px;
            font-size: 9.5pt;
            height: 44px;
            mso-height-source: userset;
        }
        .table-data td {
            background-color: #ffffff;
            color: #000000;
            border: 0.5pt solid #666666;
            padding: 5px 6px;
            font-size: 9.5pt;
            vertical-align: middle;
        }
        .num {
            mso-number-format: "\#\,\#\#0";
            text-align: right;
        }
        .text-c { text-align: center; }
        .text-l { text-align: left; }
        .fw-bold { font-weight: bold; }
        .total-row td {
            background-color: #ffffff;
            font-weight: bold;
            border-top: 1pt solid #000000;
            border-bottom: 1.5pt solid #000000;
        }
    </style>
</head>
<body>

    <!-- PHẦN ĐẦU TIÊU ĐỀ BÁO CÁO (ĐEN TRẮNG CHUẨN HÀNH CHÍNH) -->
    <table style="width: 100%;">
        <tr>
            <td colspan="<?= $kieu === 'nam' ? 3 : 5 ?>" class="agency">TỔNG CÔNG TY CẢNG HÀNG KHÔNG VIỆT NAM</td>
            <td colspan="<?= $kieu === 'nam' ? 4 : 6 ?>" class="agency" style="text-align: right;">CỘNG HÒA XÃ HỘI CHỦ NGHĨA VIỆT NAM</td>
        </tr>
        <tr>
            <td colspan="<?= $kieu === 'nam' ? 3 : 5 ?>" style="font-weight: bold; font-size: 9.5pt;">PHÒNG TÀI CHÍNH - KẾ TOÁN</td>
            <td colspan="<?= $kieu === 'nam' ? 4 : 6 ?>" style="text-align: right; font-style: italic; font-size: 9.5pt;">Độc lập - Tự do - Hạnh phúc</td>
        </tr>
        <tr>
            <td colspan="<?= $kieu === 'nam' ? 7 : 11 ?>">&nbsp;</td>
        </tr>
        <tr>
            <td colspan="<?= $kieu === 'nam' ? 7 : 11 ?>" class="title"><?= htmlspecialchars($tieuDe) ?></td>
        </tr>
        <tr>
            <td colspan="<?= $kieu === 'nam' ? 7 : 11 ?>" class="meta-info" style="text-align: center;">
                (Thời điểm xuất file: <?= $ngayXuat ?> &bull; Đơn vị tính: VNĐ)
            </td>
        </tr>
        <tr>
            <td colspan="<?= $kieu === 'nam' ? 7 : 11 ?>">&nbsp;</td>
        </tr>
        <tr>
            <td colspan="<?= $kieu === 'nam' ? 3 : 5 ?>"><strong>Phạm vi sân bay:</strong> <?= htmlspecialchars($tenSanBayLoc) ?></td>
            <td colspan="<?= $kieu === 'nam' ? 4 : 6 ?>" style="text-align: right;"><strong>Hãng hàng không:</strong> <?= htmlspecialchars($tenHangLoc) ?></td>
        </tr>
        <tr>
            <td colspan="<?= $kieu === 'nam' ? 3 : 5 ?>"><strong>Trạng thái doanh thu:</strong> <?= htmlspecialchars($nhanTrangThai) ?></td>
            <td colspan="<?= $kieu === 'nam' ? 4 : 6 ?>" style="text-align: right;"><strong>Kỳ thống kê:</strong> <?= $kieu === 'nam' ? "Cả năm {$nam}" : "Tháng {$thang}/{$nam}" ?></td>
        </tr>
    </table>

    <br/>

    <?php if ($kieu === 'thang'): ?>
        <!-- ================= BẢNG HÓA ĐƠN CHI TIẾT THÁNG (11 CỘT VIỀN MẢNH 0.5pt, CAO 44px) ================= -->
        <table class="table-data" style="width: 100%;">
            <thead>
                <tr style="height: 44px;">
                    <th style="width: 4%; vertical-align: middle;">STT</th>
                    <th style="width: 6%; vertical-align: middle;">Mã HĐ</th>
                    <th style="width: 8%; vertical-align: middle;">Số hiệu</th>
                    <th style="width: 9%; vertical-align: middle;">Chặng bay</th>
                    <th style="width: 15%; vertical-align: middle;">Hãng hàng không</th>
                    <th style="width: 11%; vertical-align: middle;">Ngày xuất HĐ</th>
                    <th style="width: 11.5%; vertical-align: middle;">Phí cất / hạ cánh</th>
                    <th style="width: 11.5%; vertical-align: middle;">Phí đỗ tàu bay</th>
                    <th style="width: 11.5%; vertical-align: middle;">Phí phục vụ HK</th>
                    <th style="width: 12.5%; vertical-align: middle;">Tổng tiền</th>
                    <th style="width: 9%; vertical-align: middle;">Trạng thái</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($hoaDonThang)): ?>
                    <tr>
                        <td colspan="11" class="text-c" style="padding: 15px;">
                            Không có hóa đơn nào phát sinh trong Tháng <?= $thang ?>/<?= $nam ?> theo bộ lọc hiện tại.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php $stt = 1; foreach ($hoaDonThang as $hd): ?>
                        <tr>
                            <td class="text-c"><?= $stt++ ?></td>
                            <td class="text-c fw-bold">#<?= $hd['id'] ?></td>
                            <td class="text-c fw-bold"><?= htmlspecialchars($hd['so_hieu'] ?? 'N/A') ?></td>
                            <td class="text-c">
                                <?= htmlspecialchars($hd['ma_san_bay_di'] ?? '') ?> - <?= htmlspecialchars($hd['ma_san_bay_den'] ?? '') ?>
                            </td>
                            <td class="text-l">
                                <?= htmlspecialchars($hd['ten_hang'] ?? '') ?> (<?= htmlspecialchars($hd['ma_hang'] ?? '') ?>)
                            </td>
                            <td class="text-c">
                                <?= !empty($hd['ngay_xuat']) ? date('d/m/Y H:i', strtotime($hd['ngay_xuat'])) : '' ?>
                            </td>
                            <td class="num"><?= (float) $hd['phi_cat_ha_canh'] ?></td>
                            <td class="num"><?= (float) $hd['phi_do_tau_bay'] ?></td>
                            <td class="num"><?= (float) $hd['phi_phuc_vu_hk'] ?></td>
                            <td class="num fw-bold"><?= (float) $hd['tong_tien'] ?></td>
                            <td class="text-c">
                                <?= ($hd['trang_thai_thanh_toan'] ?? '') === 'Da_Thanh_Toan' ? 'Đã thu tiền' : 'Chờ thanh toán' ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td colspan="6" class="text-c fw-bold">TỔNG CỘNG THÁNG <?= $thang ?>/<?= $nam ?>:</td>
                    <td class="num fw-bold"><?= $tongPhiCatHaCanh ?></td>
                    <td class="num fw-bold"><?= $tongPhiDo ?></td>
                    <td class="num fw-bold"><?= $tongPhiKhach ?></td>
                    <td class="num fw-bold"><?= $tongDoanhThuThang ?></td>
                    <td class="text-c fw-bold"><?= count($hoaDonThang) ?> HĐ</td>
                </tr>
            </tfoot>
        </table>

        <br/><br/>

        <!-- ================= KHỐI 3 Ô CHỮ KÝ THÁNG (CÂN XỨNG, KHÔNG HIỆN TÊN) ================= -->
        <table style="width: 100%;">
            <tr>
                <td colspan="3" class="text-c fw-bold" style="font-size: 10.5pt; width: 30%;">
                    NGƯỜI LẬP BIỂU
                </td>
                <td style="width: 5%;">&nbsp;</td>
                <td colspan="3" class="text-c fw-bold" style="font-size: 10.5pt; width: 30%;">
                    KẾ TOÁN TRƯỞNG
                </td>
                <td style="width: 5%;">&nbsp;</td>
                <td colspan="3" class="text-c fw-bold" style="font-size: 10.5pt; width: 30%;">
                    THỦ TRƯỞNG ĐƠN VỊ
                </td>
            </tr>
            <tr>
                <td colspan="3" class="text-c meta-info">
                    (Ký, ghi rõ họ tên)
                </td>
                <td>&nbsp;</td>
                <td colspan="3" class="text-c meta-info">
                    (Ký, ghi rõ họ tên)
                </td>
                <td>&nbsp;</td>
                <td colspan="3" class="text-c meta-info">
                    (Ký, đóng dấu)
                </td>
            </tr>
            <tr>
                <td colspan="3" style="height: 75px;">&nbsp;</td>
                <td>&nbsp;</td>
                <td colspan="3" style="height: 75px;">&nbsp;</td>
                <td>&nbsp;</td>
                <td colspan="3" style="height: 75px;">&nbsp;</td>
            </tr>
        </table>

    <?php else: ?>
        <!-- ================= BẢNG TỔNG HỢP NĂM (7 CỘT VIỀN MẢNH 0.5pt, CAO 44px) ================= -->
        <table class="table-data" style="width: 100%;">
            <thead>
                <tr style="height: 44px;">
                    <th style="width: 10%; vertical-align: middle;">Tháng</th>
                    <th style="width: 11%; vertical-align: middle;">Số hóa đơn</th>
                    <th style="width: 17%; vertical-align: middle;">Phí cất / hạ cánh</th>
                    <th style="width: 17%; vertical-align: middle;">Phí đỗ tàu bay</th>
                    <th style="width: 18%; vertical-align: middle;">Phí phục vụ khách</th>
                    <th style="width: 17%; vertical-align: middle;">Tổng doanh thu</th>
                    <th style="width: 10%; vertical-align: middle;">Tỷ trọng</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                    $tongNam = 0.0;
                    foreach ($data12Thang as $t) { $tongNam += $t['tong_doanh_thu']; }
                ?>
                <?php foreach ($data12Thang as $t): ?>
                    <?php $tyTrong = $tongNam > 0 ? round(($t['tong_doanh_thu'] / $tongNam) * 100, 1) : 0; ?>
                    <tr>
                        <td class="text-c fw-bold">Tháng <?= $t['thang'] ?></td>
                        <td class="text-c"><?= $t['so_hoa_don'] ?></td>
                        <td class="num"><?= (float) $t['phi_cat_ha_canh'] ?></td>
                        <td class="num"><?= (float) $t['phi_do_tau_bay'] ?></td>
                        <td class="num"><?= (float) $t['phi_phuc_vu_hk'] ?></td>
                        <td class="num fw-bold"><?= (float) $t['tong_doanh_thu'] ?></td>
                        <td class="text-c"><?= $tyTrong ?>%</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td class="text-c fw-bold">TỔNG CỘNG:</td>
                    <td class="text-c fw-bold"><?= $kpi['tong_so_hd'] ?> HĐ</td>
                    <td class="num fw-bold"><?= $kpi['phi_cat_ha_canh'] ?></td>
                    <td class="num fw-bold"><?= $kpi['phi_do_tau_bay'] ?></td>
                    <td class="num fw-bold"><?= $kpi['phi_phuc_vu_hk'] ?></td>
                    <td class="num fw-bold"><?= $tongNam ?></td>
                    <td class="text-c fw-bold">100%</td>
                </tr>
            </tfoot>
        </table>

        <br/>
        <!-- BẢNG THEO HÃNG HÀNG KHÔNG (VIỀN MẢNH 0.5pt, CAO 44px) -->
        <table style="width: 100%;">
            <tr>
                <td colspan="7" style="font-size: 10.5pt; font-weight: bold; padding-bottom: 4px;">
                    TỔNG HỢP DOANH THU THEO HÃNG HÀNG KHÔNG:
                </td>
            </tr>
        </table>
        <table class="table-data" style="width: 100%;">
            <thead>
                <tr style="height: 44px;">
                    <th style="width: 7%; vertical-align: middle;">STT</th>
                    <th colspan="2" style="width: 34%; vertical-align: middle;">Hãng hàng không</th>
                    <th style="width: 14%; vertical-align: middle;">Mã IATA</th>
                    <th style="width: 14%; vertical-align: middle;">Số chuyến bay</th>
                    <th style="width: 19%; vertical-align: middle;">Tổng doanh thu</th>
                    <th style="width: 12%; vertical-align: middle;">Tỷ trọng</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($dataTheoHang)): ?>
                    <tr><td colspan="7" class="text-c">Không có số liệu.</td></tr>
                <?php else: ?>
                    <?php $stt = 1; foreach ($dataTheoHang as $h): ?>
                        <?php $pct = $tongNam > 0 ? round(($h['doanh_thu'] / $tongNam) * 100, 1) : 0; ?>
                        <tr>
                            <td class="text-c"><?= $stt++ ?></td>
                            <td colspan="2" class="fw-bold" style="padding-left: 8px;"><?= htmlspecialchars($h['ten_hang']) ?></td>
                            <td class="text-c"><?= htmlspecialchars($h['ma_iata'] ?? $h['ma_hang'] ?? '') ?></td>
                            <td class="text-c"><?= $h['so_hoa_don'] ?></td>
                            <td class="num fw-bold"><?= (float) $h['doanh_thu'] ?></td>
                            <td class="text-c"><?= $pct ?>%</td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <br/><br/>

        <!-- ================= KHỐI 3 Ô CHỮ KÝ NĂM (CÂN XỨNG TUYỆT ĐỐI) ================= -->
        <table style="width: 100%;">
            <tr>
                <td colspan="2" class="text-c fw-bold" style="font-size: 10.5pt;">
                    NGƯỜI LẬP BIỂU
                </td>
                <td>&nbsp;</td>
                <td class="text-c fw-bold" style="font-size: 10.5pt;">
                    KẾ TOÁN TRƯỞNG
                </td>
                <td>&nbsp;</td>
                <td colspan="2" class="text-c fw-bold" style="font-size: 10.5pt;">
                    THỦ TRƯỞNG ĐƠN VỊ
                </td>
            </tr>
            <tr>
                <td colspan="2" class="text-c meta-info">
                    (Ký, ghi rõ họ tên)
                </td>
                <td>&nbsp;</td>
                <td class="text-c meta-info">
                    (Ký, ghi rõ họ tên)
                </td>
                <td>&nbsp;</td>
                <td colspan="2" class="text-c meta-info">
                    (Ký, đóng dấu)
                </td>
            </tr>
            <tr>
                <td colspan="2" style="height: 75px;">&nbsp;</td>
                <td>&nbsp;</td>
                <td style="height: 75px;">&nbsp;</td>
                <td>&nbsp;</td>
                <td colspan="2" style="height: 75px;">&nbsp;</td>
            </tr>
        </table>
    <?php endif; ?>

</body>
</html>

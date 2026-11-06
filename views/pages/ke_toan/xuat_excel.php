<?php
/**
 * ============================================================
 * XUẤT BÁO CÁO DOANH THU RA FILE EXCEL (.XLS)
 * views/pages/ke_toan/xuat_excel.php
 * Phân hệ: Kế toán & Doanh thu
 * Căn chuẩn 100% trang in A4: Tự động vừa vặn trang giấy,
 * chiều cao tiêu đề thoáng đãng, các cột phí ưu tiên độ rộng lớn
 * không bao giờ bị cắt mất hoặc tràn lề khi in.
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
                            <x:Header x:Margin="0.1"/>
                            <x:Footer x:Margin="0.1"/>
                            <x:PageMargins x:Bottom="0.25" x:Left="0.2" x:Right="0.2" x:Top="0.25"/>
                        </x:PageSetup>
                    </x:WorksheetOptions>
                </x:ExcelWorksheet>
            </x:ExcelWorksheets>
        </x:ExcelWorkbook>
    </xml>
    <![endif]-->
    <style>
        @page {
            margin: 0.25in;
            mso-horizontal-page-align: center;
        }
        body {
            font-family: 'Times New Roman', Arial, sans-serif;
            font-size: 10pt;
            color: #000000;
        }
        table {
            border-collapse: collapse;
            width: 100%;
            max-width: 100%;
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
            font-size: 9.5pt;
            font-weight: bold;
            text-transform: uppercase;
            color: #000000;
        }
        .meta-info {
            font-size: 9pt;
            font-style: italic;
            color: #000000;
        }
        .table-data {
            border-collapse: collapse;
            width: 100%;
            max-width: 100%;
            margin-top: 6px;
            table-layout: fixed;
        }
        .table-data th {
            background-color: #ffffff !important;
            color: #000000 !important;
            font-weight: bold;
            border: 0.5pt solid #555555;
            text-align: center;
            vertical-align: middle;
            font-size: 9pt;
            line-height: 1.25;
            word-wrap: break-word;
        }
        .table-data td {
            background-color: #ffffff !important;
            color: #000000 !important;
            border: 0.5pt solid #666666;
            font-size: 9pt;
            vertical-align: middle;
            line-height: 1.25;
            padding: 5px 3px;
        }
        .num {
            mso-number-format: "\#\,\#\#0";
            text-align: right;
            white-space: nowrap;
        }
        .text-c { text-align: center; white-space: nowrap; }
        .text-l { text-align: left; word-wrap: break-word; }
        .fw-bold { font-weight: bold; }
        .total-row td {
            background-color: #ffffff !important;
            font-weight: bold;
            border-top: 1pt solid #000000;
            border-bottom: 1.5pt solid #000000;
            padding-top: 7px;
            padding-bottom: 7px;
        }
    </style>
</head>
<body>

    <!-- PHẦN ĐẦU TIÊU ĐỀ BÁO CÁO (ĐEN TRẮNG CHUẨN HÀNH CHÍNH) -->
    <table style="width: 100%; max-width: 100%;">
        <tr>
            <td colspan="<?= $kieu === 'nam' ? 3 : 5 ?>" class="agency">TỔNG CÔNG TY CẢNG HÀNG KHÔNG VIỆT NAM</td>
            <td colspan="<?= $kieu === 'nam' ? 4 : 6 ?>" class="agency" style="text-align: right;">CỘNG HÒA XÃ HỘI CHỦ NGHĨA VIỆT NAM</td>
        </tr>
        <tr>
            <td colspan="<?= $kieu === 'nam' ? 3 : 5 ?>" style="font-weight: bold; font-size: 9pt;">PHÒNG TÀI CHÍNH - KẾ TOÁN</td>
            <td colspan="<?= $kieu === 'nam' ? 4 : 6 ?>" style="text-align: right; font-style: italic; font-size: 9pt;">Độc lập - Tự do - Hạnh phúc</td>
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
            <td colspan="<?= $kieu === 'nam' ? 3 : 5 ?>"><strong>Phạm vi:</strong> <?= htmlspecialchars($tenSanBayLoc) ?></td>
            <td colspan="<?= $kieu === 'nam' ? 4 : 6 ?>" style="text-align: right;"><strong>Hãng:</strong> <?= htmlspecialchars($tenHangLoc) ?></td>
        </tr>
        <tr>
            <td colspan="<?= $kieu === 'nam' ? 3 : 5 ?>"><strong>Trạng thái:</strong> <?= htmlspecialchars($nhanTrangThai) ?></td>
            <td colspan="<?= $kieu === 'nam' ? 4 : 6 ?>" style="text-align: right;"><strong>Kỳ thống kê:</strong> <?= $kieu === 'nam' ? "Cả năm {$nam}" : "Tháng {$thang}/{$nam}" ?></td>
        </tr>
    </table>

    <br/>

    <?php if ($kieu === 'thang'): ?>
        <!-- ================= BẢNG HÓA ĐƠN CHI TIẾT THÁNG (11 CỘT TỔNG 100%, ƯU TIÊN ĐỘ RỘNG PHÍ) ================= -->
        <table class="table-data" style="width: 100%; max-width: 100%;">
            <colgroup>
                <col style="width: 3.5%;">
                <col style="width: 5%;">
                <col style="width: 7%;">
                <col style="width: 8%;">
                <col style="width: 15%;">
                <col style="width: 9.5%;">
                <col style="width: 11.5%;">
                <col style="width: 11.5%;">
                <col style="width: 11.5%;">
                <col style="width: 12.5%;">
                <col style="width: 5%;">
            </colgroup>
            <thead>
                <tr style="height: 48px; mso-height-source: userset;">
                    <th style="width: 3.5%; height: 48px;">STT</th>
                    <th style="width: 5%; height: 48px;">Mã<br/>HĐ</th>
                    <th style="width: 7%; height: 48px;">Số<br/>hiệu</th>
                    <th style="width: 8%; height: 48px;">Chặng<br/>bay</th>
                    <th style="width: 15%; height: 48px;">Hãng hàng không</th>
                    <th style="width: 9.5%; height: 48px;">Ngày xuất<br/>hóa đơn</th>
                    <th style="width: 11.5%; height: 48px;">Phí cất /<br/>hạ cánh</th>
                    <th style="width: 11.5%; height: 48px;">Phí đỗ<br/>tàu bay</th>
                    <th style="width: 11.5%; height: 48px;">Phí phục vụ<br/>hành khách</th>
                    <th style="width: 12.5%; height: 48px;">Tổng tiền<br/>(VNĐ)</th>
                    <th style="width: 5%; height: 48px;">Trạng<br/>thái</th>
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
                            <td class="text-c" style="font-size: 8.5pt;">
                                <?= !empty($hd['ngay_xuat']) ? date('d/m/Y', strtotime($hd['ngay_xuat'])) : '' ?>
                            </td>
                            <td class="num"><?= number_format((float) $hd['phi_cat_ha_canh'], 0, ',', '.') ?></td>
                            <td class="num"><?= number_format((float) $hd['phi_do_tau_bay'], 0, ',', '.') ?></td>
                            <td class="num"><?= number_format((float) $hd['phi_phuc_vu_hk'], 0, ',', '.') ?></td>
                            <td class="num fw-bold"><?= number_format((float) $hd['tong_tien'], 0, ',', '.') ?></td>
                            <td class="text-c" style="font-size: 8pt;">
                                <?= ($hd['trang_thai_thanh_toan'] ?? '') === 'Da_Thanh_Toan' ? 'Đã thu' : 'Chờ thu' ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td colspan="6" class="text-c fw-bold">TỔNG CỘNG THÁNG <?= $thang ?>/<?= $nam ?>:</td>
                    <td class="num fw-bold"><?= number_format((float) $tongPhiCatHaCanh, 0, ',', '.') ?></td>
                    <td class="num fw-bold"><?= number_format((float) $tongPhiDo, 0, ',', '.') ?></td>
                    <td class="num fw-bold"><?= number_format((float) $tongPhiKhach, 0, ',', '.') ?></td>
                    <td class="num fw-bold"><?= number_format((float) $tongDoanhThuThang, 0, ',', '.') ?></td>
                    <td class="text-c fw-bold"><?= count($hoaDonThang) ?> HĐ</td>
                </tr>
            </tfoot>
        </table>

        <br/><br/>

        <!-- ================= KHỐI 2 Ô CHỮ KÝ THÁNG (1 SÁT MÉP TRÁI - KHOẢNG TRỐNG GIỮA - 1 SÁT MÉP PHẢI) ================= -->
        <table style="width: 100%; max-width: 100%;">
            <tr>
                <td colspan="4" class="text-c fw-bold" style="font-size: 10pt;">
                    NGƯỜI LẬP BÁO CÁO
                </td>
                <td colspan="3">&nbsp;</td>
                <td colspan="4" class="text-c fw-bold" style="font-size: 10pt;">
                    QUẢN LÝ SÂN BAY
                </td>
            </tr>
            <tr>
                <td colspan="4" class="text-c meta-info">
                    (Ký, ghi rõ họ tên)
                </td>
                <td colspan="3">&nbsp;</td>
                <td colspan="4" class="text-c meta-info">
                    (Ký, đóng dấu)
                </td>
            </tr>
            <tr>
                <td colspan="4" style="height: 70px;">&nbsp;</td>
                <td colspan="3">&nbsp;</td>
                <td colspan="4" style="height: 70px;">&nbsp;</td>
            </tr>
        </table>

    <?php else: ?>
        <!-- ================= BẢNG TỔNG HỢP NĂM (7 CỘT TỔNG 100%, ƯU TIÊN ĐỘ RỘNG CÁC PHÍ) ================= -->
        <table class="table-data" style="width: 100%; max-width: 100%;">
            <colgroup>
                <col style="width: 9%;">
                <col style="width: 10%;">
                <col style="width: 18%;">
                <col style="width: 18%;">
                <col style="width: 18%;">
                <col style="width: 19%;">
                <col style="width: 8%;">
            </colgroup>
            <thead>
                <tr style="height: 48px; mso-height-source: userset;">
                    <th style="width: 9%; height: 48px;">Tháng</th>
                    <th style="width: 10%; height: 48px;">Số hóa đơn</th>
                    <th style="width: 18%; height: 48px;">Phí cất /<br/>hạ cánh</th>
                    <th style="width: 18%; height: 48px;">Phí đỗ<br/>tàu bay</th>
                    <th style="width: 18%; height: 48px;">Phí phục vụ<br/>hành khách</th>
                    <th style="width: 19%; height: 48px;">Tổng<br/>doanh thu</th>
                    <th style="width: 8%; height: 48px;">Tỷ trọng</th>
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
                        <td class="num"><?= number_format((float) $t['phi_cat_ha_canh'], 0, ',', '.') ?></td>
                        <td class="num"><?= number_format((float) $t['phi_do_tau_bay'], 0, ',', '.') ?></td>
                        <td class="num"><?= number_format((float) $t['phi_phuc_vu_hk'], 0, ',', '.') ?></td>
                        <td class="num fw-bold"><?= number_format((float) $t['tong_doanh_thu'], 0, ',', '.') ?></td>
                        <td class="text-c"><?= $tyTrong ?>%</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td class="text-c fw-bold">TỔNG CỘNG:</td>
                    <td class="text-c fw-bold"><?= $kpi['tong_so_hd'] ?> HĐ</td>
                    <td class="num fw-bold"><?= number_format((float) $kpi['phi_cat_ha_canh'], 0, ',', '.') ?></td>
                    <td class="num fw-bold"><?= number_format((float) $kpi['phi_do_tau_bay'], 0, ',', '.') ?></td>
                    <td class="num fw-bold"><?= number_format((float) $kpi['phi_phuc_vu_hk'], 0, ',', '.') ?></td>
                    <td class="num fw-bold"><?= number_format((float) $tongNam, 0, ',', '.') ?></td>
                    <td class="text-c fw-bold">100%</td>
                </tr>
            </tfoot>
        </table>

        <br/>
        <!-- BẢNG THEO HÃNG HÀNG KHÔNG (CÂN ĐỐI 100%) -->
        <table style="width: 100%; max-width: 100%;">
            <tr>
                <td colspan="7" style="font-size: 10pt; font-weight: bold; padding-bottom: 4px;">
                    TỔNG HỢP DOANH THU THEO HÃNG HÀNG KHÔNG:
                </td>
            </tr>
        </table>
        <table class="table-data" style="width: 100%; max-width: 100%;">
            <colgroup>
                <col style="width: 6%;">
                <col style="width: 19%;">
                <col style="width: 19%;">
                <col style="width: 12%;">
                <col style="width: 14%;">
                <col style="width: 20%;">
                <col style="width: 10%;">
            </colgroup>
            <thead>
                <tr style="height: 44px; mso-height-source: userset;">
                    <th style="width: 6%; height: 44px;">STT</th>
                    <th colspan="2" style="width: 38%; height: 44px;">Hãng hàng không</th>
                    <th style="width: 12%; height: 44px;">Mã IATA</th>
                    <th style="width: 14%; height: 44px;">Số chuyến bay</th>
                    <th style="width: 20%; height: 44px;">Tổng doanh thu</th>
                    <th style="width: 10%; height: 44px;">Tỷ trọng</th>
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
                            <td class="num fw-bold"><?= number_format((float) $h['doanh_thu'], 0, ',', '.') ?></td>
                            <td class="text-c"><?= $pct ?>%</td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <br/><br/>

        <!-- ================= KHỐI 2 Ô CHỮ KÝ NĂM (1 SÁT MÉP TRÁI - KHOẢNG TRỐNG GIỮA - 1 SÁT MÉP PHẢI) ================= -->
        <table style="width: 100%; max-width: 100%;">
            <tr>
                <td colspan="3" class="text-c fw-bold" style="font-size: 10pt;">
                    NGƯỜI LẬP BÁO CÁO
                </td>
                <td colspan="1">&nbsp;</td>
                <td colspan="3" class="text-c fw-bold" style="font-size: 10pt;">
                    QUẢN LÝ SÂN BAY
                </td>
            </tr>
            <tr>
                <td colspan="3" class="text-c meta-info">
                    (Ký, ghi rõ họ tên)
                </td>
                <td colspan="1">&nbsp;</td>
                <td colspan="3" class="text-c meta-info">
                    (Ký, đóng dấu)
                </td>
            </tr>
            <tr>
                <td colspan="3" style="height: 70px;">&nbsp;</td>
                <td colspan="1">&nbsp;</td>
                <td colspan="3" style="height: 70px;">&nbsp;</td>
            </tr>
        </table>
    <?php endif; ?>

</body>
</html>
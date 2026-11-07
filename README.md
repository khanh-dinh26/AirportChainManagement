# Hệ Thống Quản Lý Chuỗi Cảng Hàng Không (AeroChain)

> Đề tài: Xây dựng ứng dụng web quản lý điều hành khai thác chuỗi cảng hàng không dựa trên mô hình hoạt động của Tổng công ty Cảng hàng không Việt Nam (ACV).

---

## Thành viên nhóm thực hiện

| # | Họ và tên | Mã sinh viên | Vai trò |
| :-: | :--- | :---: | :---: |
| 1 | **Nguyễn Đình Khánh** | 65131460 | **Nhóm trưởng** |
| 2 | **Trần Bảo Thiên** | 65133334 | Thành viên |
| 3 | **Huỳnh Tuấn Ngọc** | 65132224 | Thành viên |
| 4 | **Nguyễn Nhật Khánh Duy** | 65130634 | Thành viên |
| 5 | **Nguyễn Thị Mỹ Khánh** | 65131469 | Thành viên |

---

## Giới thiệu tổng quan

Hệ thống **AeroChain** là ứng dụng web nội bộ phục vụ công tác quản lý và điều hành khai thác chuỗi các cảng hàng không. Hệ thống hỗ trợ kết nối dữ liệu giữa các bộ phận: Điều hành bay, Kỹ thuật - Bảo trì, Kế toán - Tài vụ, Hạ tầng cảng và Quản lý nhân sự trên một cơ sở dữ liệu tập trung.

---

## Kiến trúc hệ thống

### Sơ đồ Use Case tổng thể

<p align="center">
  <img src="docs/images/so_do_use_case.png" alt="Sơ đồ Use Case" width="600">
</p>

---

## Các phân hệ chức năng chính

### 1. Xác thực & Hồ sơ cá nhân
- Đăng nhập bảo mật, phân quyền theo vai trò và sân bay trực thuộc.
- Cập nhật thông tin cá nhân, đổi mật khẩu và đổi ảnh đại diện.

| Màn hình Đăng nhập | Hồ sơ cá nhân |
| :-: | :-: |
| <img src="docs/images/screens/01_dang_nhap.png" alt="Đăng nhập" width="420"> | <img src="docs/images/screens/15_ho_so.png" alt="Hồ sơ cá nhân" width="420"> |

---

### 2. Dashboard Tổng quan điều hành
- Bảng điều khiển trung tâm hiển thị chỉ số khai thác: chuyến bay, tình trạng hạ tầng, phiếu bảo trì và doanh thu.

<p align="center">
  <img src="docs/images/screens/02_tong_quan.png" alt="Dashboard Tổng quan" width="650">
</p>

---

### 3. Quản lý Hạ tầng sân bay
- Quản lý danh mục các cảng hàng không trong chuỗi (HAN, SGN, DAD, CXR, PQC...).
- Quản lý chi tiết: Nhà ga, Cửa ra máy bay (Gate), Quầy check-in, Băng chuyền hành lý.

| Danh mục Cảng hàng không | Chi tiết Hạ tầng & Nhà ga |
| :-: | :-: |
| <img src="docs/images/screens/03_ha_tang_san_bay.png" alt="Danh sách sân bay" width="420"> | <img src="docs/images/screens/04_san_bay_chi_tiet.png" alt="Chi tiết hạ tầng" width="420"> |

---

### 4. Điều hành Lịch bay
- Quản lý danh sách chuyến bay đi/đến, điều phối trạng thái: Đúng giờ, Khởi hành, Hoãn (Delay), Hủy chuyến, Cất cánh.
- Tự động kiểm tra xung đột quầy check-in, cửa ra tàu bay khi thiết lập chuyến bay mới.

| Lịch bay điều hành | Thiết lập chuyến bay mới |
| :-: | :-: |
| <img src="docs/images/screens/05_chuyen_bay.png" alt="Lịch chuyến bay" width="420"> | <img src="docs/images/screens/07_tao_chuyen_bay.png" alt="Tạo chuyến bay" width="420"> |

---

### 5. Kỹ thuật & Bảo trì thiết bị
- Quản lý danh mục trang thiết bị tại các nhà ga (xe thang, radar, xe cứu hỏa, cầu dẫn khách...).
- Tạo và xử lý phiếu sự cố kỹ thuật theo tiến độ xử lý.

| Quản lý phiếu bảo trì | Danh mục trang thiết bị |
| :-: | :-: |
| <img src="docs/images/screens/08_bao_tri.png" alt="Phiếu bảo trì" width="420"> | <img src="docs/images/screens/09_bao_tri_thiet_bi.png" alt="Danh mục thiết bị" width="420"> |

---

### 6. Kế toán & Doanh thu dịch vụ
- Quản lý biểu phí dịch vụ hàng không (cất/hạ cánh, sân đỗ, an ninh soi chiếu).
- Tự động tạo hóa đơn theo tải trọng cất cánh (MTOW), theo dõi công nợ và thống kê doanh thu theo biểu đồ.

| Quản lý Hóa đơn | Chi tiết Hóa đơn dịch vụ |
| :-: | :-: |
| <img src="docs/images/screens/10_ke_toan.png" alt="Quản lý hóa đơn" width="420"> | <img src="docs/images/screens/11_chi_tiet_hoa_don.png" alt="Chi tiết hóa đơn" width="420"> |

<p align="center">
  <img src="docs/images/screens/12_thong_ke.png" alt="Báo cáo thống kê doanh thu" width="650">
</p>

---

### 7. Quản lý Nhân sự & Phân quyền
- Quản lý tài khoản người dùng, phân quyền theo 5 vai trò và phân bổ theo từng sân bay.
- Hỗ trợ lọc theo vai trò, sân bay và phân trang dữ liệu.

<p align="center">
  <img src="docs/images/screens/14_nhan_su.png" alt="Quản lý nhân sự" width="650">
</p>

---

## Hướng dẫn cài đặt và sử dụng

### Yêu cầu hệ thống
- XAMPP (Apache + MySQL)
- PHP >= 8.0

### Các bước thực hiện:

1. Đặt thư mục dự án vào thư mục web:
   ```text
   C:\xampp\htdocs\AirportChainManagement
   ```

2. Khởi động **Apache** và **MySQL** trong **XAMPP Control Panel**.

3. Mở **phpMyAdmin** (`http://localhost/phpmyadmin`):
   - Tạo cơ sở dữ liệu mới: `quan_ly_chuoi_san_bay`
   - Import file: `database/quan_ly_chuoi_san_bay.sql`

4. Kiểm tra cấu hình kết nối tại `config/database.php`:
   ```php
   $host    = 'localhost';
   $user    = 'root';
   $pass    = ''; 
   $db_name = 'quan_ly_chuoi_san_bay';
   ```

5. Mở trình duyệt và truy cập:
   ```text
   http://localhost/AirportChainManagement
   ```

---

## Tài khoản thử nghiệm

- Mật khẩu cho tài khoản **admin**: `123456`
- Mật khẩu cho **các tài khoản khác**: `password`

| Tên đăng nhập | Mật khẩu | Họ và tên | Vai trò | Phạm vi phụ trách |
| :--- | :--- | :--- | :--- | :--- |
| `admin` | `123456` | Nguyễn Đình Khánh | Quản trị viên Tổng công ty | Toàn quốc / Trụ sở chính |
| `quanly_han` | `password` | Trần Nhật An | Quản lý sân bay | Cảng HKQT Nội Bài (HAN) |
| `quanly_sgn` | `password` | Huỳnh Tuấn Ngọc | Quản lý sân bay | Cảng HKQT Tân Sơn Nhất (SGN) |
| `dieuhanh_han` | `password` | Nguyễn Đức Duy | Nhân viên điều hành | Cảng HKQT Nội Bài (HAN) |
| `baotri_han` | `password` | Võ Nhật Lâm | Nhân viên bảo trì | Cảng HKQT Nội Bài (HAN) |
| `ketoan_sgn` | `password` | Bùi Thị Kim Nhàn | Kế toán viên | Cảng HKQT Tân Sơn Nhất (SGN) |

---

## Cấu trúc thư mục dự án

```text
AirportChainManagement/
├── assets/             # CSS, logo, icons
├── config/             # Cấu hình hệ thống và kết nối CSDL
├── controllers/        # Xử lý luồng nghiệp vụ (Controllers)
├── database/           # File CSDL (quan_ly_chuoi_san_bay.sql)
├── docs/               # Tài liệu báo cáo và hình ảnh minh họa
│   └── images/
│       ├── screens/    # Ảnh chụp màn hình các chức năng
│       └── ...         # Sơ đồ Use Case, ERD
├── models/             # Thao tác CSDL (Models)
├── views/              # Giao diện người dùng (Views & Layouts)
├── index.php           # Điều hướng chính (Front Controller)
└── README.md           # Hướng dẫn dự án
```

---
*Dự án Phát triển Phần mềm Mã nguồn mở - Trường Đại học Nha Trang (NTU)*

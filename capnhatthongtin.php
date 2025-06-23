<?php
session_start();
require_once 'config.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user'])) {
    header('Location: index.php');
    exit();
}
$vaiTro = isset($_SESSION['user']['VaiTro']) ? $_SESSION['user']['VaiTro'] : '';
$user_id = $_SESSION['user']['IdTaiKhoan'];
$message = '';
$error = '';
$user = [];

// Lấy thông tin người dùng hiện tại
try {
    if ($vaiTro == 'Khách hàng') {
        $stmt = $conn->prepare("SELECT * FROM khachhang WHERE IdTaiKhoan = ?");
        $stmt->bind_param("s", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();
    } elseif ($vaiTro == 'Bán hàng') {
        $stmt = $conn->prepare("SELECT * FROM nguoiban WHERE IdTaiKhoan = ?");
        $stmt->bind_param("s", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();
    } elseif ($vaiTro == 'Quản lý') {
        $stmt = $conn->prepare("SELECT * FROM quanly WHERE IdTaiKhoan = ?");
        $stmt->bind_param("s", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();
    }
} catch (Exception $e) {
    $error = "Lỗi khi lấy thông tin người dùng: " . $e->getMessage();
}

// Xử lý cập nhật thông tin
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if ($vaiTro == 'Khách hàng') {
            $ten = trim($_POST['ten']);
            $ngaysinh = trim($_POST['ngaysinh']);
            $email = trim($_POST['email']);
            $diachi = trim($_POST['diachi']);
            $sdt = trim($_POST['sdt']);
            if (empty($ten) || empty($ngaysinh) || empty($email) || empty($diachi) || empty($sdt)) {
                throw new Exception("Vui lòng điền đầy đủ thông tin bắt buộc");
            }
            $stmt = $conn->prepare("UPDATE khachhang SET TenKhachHang=?, NgaySinh=?, Email=?, DiaChi=?, SDT=? WHERE IdTaiKhoan=?");
            $stmt->bind_param("ssssss", $ten, $ngaysinh, $email, $diachi, $sdt, $user_id);
            $stmt->execute();
            $stmt->close();
            $message = "Cập nhật thông tin thành công!";
        } elseif ($vaiTro == 'Bán hàng') {
            $ten = trim($_POST['ten']);
            $ngaysinh = trim($_POST['ngaysinh']);
            $email = trim($_POST['email']);
            $diachi = trim($_POST['diachi']);
            $sdt = trim($_POST['sdt']);
            $mota = trim($_POST['mota']);
            if (empty($ten) || empty($ngaysinh) || empty($email) || empty($diachi) || empty($sdt)) {
                throw new Exception("Vui lòng điền đầy đủ thông tin bắt buộc");
            }
            $stmt = $conn->prepare("UPDATE nguoiban SET TenNguoiBan=?, NgaySinh=?, Email=?, DiaChi=?, SDT=?, MoTaGianHang=? WHERE IdTaiKhoan=?");
            $stmt->bind_param("sssssss", $ten, $ngaysinh, $email, $diachi, $sdt, $mota, $user_id);
            $stmt->execute();
            $stmt->close();
            $message = "Cập nhật thông tin thành công!";
        } elseif ($vaiTro == 'Quản lý') {
            $ten = trim($_POST['ten']);
            $ngaysinh = trim($_POST['ngaysinh']);
            $email = trim($_POST['email']);
            $diachi = trim($_POST['diachi']);
            $sdt = trim($_POST['sdt']);
            if (empty($ten) || empty($ngaysinh) || empty($email) || empty($diachi) || empty($sdt)) {
                throw new Exception("Vui lòng điền đầy đủ thông tin bắt buộc");
            }
            $stmt = $conn->prepare("UPDATE quanly SET TenQuanLy=?, NgaySinh=?, Email=?, DiaChi=?, SDT=? WHERE IdTaiKhoan=?");
            $stmt->bind_param("ssssss", $ten, $ngaysinh, $email, $diachi, $sdt, $user_id);
            $stmt->execute();
            $stmt->close();
            $message = "Cập nhật thông tin thành công!";
        }
        // Lấy lại thông tin mới
        if ($vaiTro == 'Khách hàng') {
            $stmt = $conn->prepare("SELECT * FROM khachhang WHERE IdTaiKhoan = ?");
            $stmt->bind_param("s", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            $stmt->close();
        } elseif ($vaiTro == 'Bán hàng') {
            $stmt = $conn->prepare("SELECT * FROM nguoiban WHERE IdTaiKhoan = ?");
            $stmt->bind_param("s", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            $stmt->close();
        } elseif ($vaiTro == 'Quản lý') {
            $stmt = $conn->prepare("SELECT * FROM quanly WHERE IdTaiKhoan = ?");
            $stmt->bind_param("s", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            $stmt->close();
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cập nhật thông tin cá nhân</title>
    <link rel="stylesheet" href="css/giaodienkhachhang.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .profile-container {
            max-width: 600px;
            margin: 48px auto 48px auto;
            padding: 40px 48px 32px 48px;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 32px rgba(0,0,0,0.10);
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .profile-container form {
            width: 100%;
            max-width: 440px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            align-items: stretch;
        }
        .profile-container h2.section-title {
            color: #257a2a;
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 24px;
            text-align: center;
            letter-spacing: 1px;
        }
        .form-label {
            text-align: left;
            font-weight: 600;
            color: #333;
            margin-bottom: 6px;
            display: block;
            font-size: 1.08rem;
        }
        .required-field::after {
            content: " *";
            color: #e53935;
            font-weight: bold;
        }
        .mb-3 {
            margin-bottom: 18px;
            width: 100%;
        }
        .form-control {
            width: 100%;
            padding: 12px 14px;
            border: 1.5px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1.08rem;
            background: #fafbfc;
            transition: border 0.2s, box-shadow 0.2s;
            outline: none;
            margin-top: 2px;
        }
        .form-control:focus {
            border: 1.5px solid #257a2a;
            box-shadow: 0 0 0 2px #e0f2e9;
            background: #fff;
        }
        .btn-primary {
            background: linear-gradient(90deg, #257a2a 60%, #43a047 100%);
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 12px 36px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(67,160,71,0.08);
            transition: background 0.2s, box-shadow 0.2s;
            margin: 0 auto;
            display: block;
        }
        .btn-primary:hover {
            background: linear-gradient(90deg, #43a047 60%, #257a2a 100%);
            box-shadow: 0 4px 16px rgba(67,160,71,0.13);
        }
        .text-center {
            text-align: center;
        }
        .mt-4 {
            margin-top: 24px;
        }
        .alert {
            width: 100%;
            text-align: center;
            font-size: 1.05rem;
        }
        .custom-dropdown {
      position: relative;
      display: inline-block;
    }
    .user-icon {
      cursor: pointer;
      color: #fff;
      font-weight: 500;
      font-size: 18px;
      padding: 0 16px;
    }
    .dropdown-content {
      display: none;
      position: absolute;
      right: 0;
      background: #fff;
      min-width: 200px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.15);
      z-index: 100;
      border-radius: 6px;
      overflow: hidden;
    }
    .dropdown-content a {
      color: #333;
      padding: 12px 18px;
      text-decoration: none;
      display: block;
      font-size: 16px;
      transition: background 0.2s;
    }
    .dropdown-content a:hover {
      background: #f0f0f0;
    }
    .dropdown-content hr {
      margin: 6px 0;
      border: none;
      border-top: 1px solid #eee;
    }
        
        /* Toast Notification Styles */
        .toast {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background-color: #2e7d32;
            color: white;
            padding: 16px 28px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.18);
            display: flex;
            align-items: center;
            z-index: 9999;
            font-size: 1.08rem;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s, transform 0.3s;
            transform: translateY(40px);
        }
        .toast.show {
            opacity: 1;
            pointer-events: auto;
            transform: translateY(0);
        }
        .toast-icon {
            margin-right: 12px;
            font-size: 1.4rem;
        }
        .toast.error {
            background-color: #e53935;
        }

        /* Modal styles */
        .modal-xac-nhan {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0; top: 0; width: 100%; height: 100%;
            background: rgba(44, 62, 80, 0.25);
            justify-content: center; align-items: center;
        }
        .modal-xac-nhan-content {
            background: #fff;
            border-radius: 20px;
            padding: 36px 36px 28px 36px;
            box-shadow: 0 8px 32px rgba(46,125,50,0.18);
            text-align: center;
            min-width: 340px;
            max-width: 90vw;
            animation: popIn 0.2s;
        }
        @keyframes popIn {
            from { transform: scale(0.95); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }
        .modal-xac-nhan-content h3 {
            color: #2e7d32;
            margin-bottom: 18px;
            font-size: 1.25rem;
            font-weight: 700;
        }
        .modal-xac-nhan-content p {
            color: #333;
            font-size: 1.08rem;
            margin-bottom: 28px;
        }
        .modal-xac-nhan-actions {
            display: flex;
            justify-content: center;
            gap: 22px;
        }
        .nut {
            min-width: 90px;
            padding: 12px 0;
            border-radius: 8px;
            font-size: 1.08rem;
            font-weight: 600;
            box-shadow: 0 2px 8px rgba(67,160,71,0.08);
            border: none;
            cursor: pointer;
            transition: background 0.2s, color 0.2s, box-shadow 0.2s;
        }
        .nut-xoa { background: #2e7d32; color: #fff; }
        .nut-xoa:hover { background: #1b5e20; }
        .nut-sua { background: #e65100; color: #fff; }
        .nut-sua:hover { background: #bf360c; }

        /* Validation Styles */
        .error-message {
            color: #e53935;
            font-size: 14px;
            margin-top: 5px;
            display: none;
            font-weight: 500;
            padding-left: 2px;
        }

        .form-control.error {
            border-color: #e53935 !important;
            background-color: #ffebee !important;
        }

        .form-control.valid {
            border-color: #2e7d32 !important;
            background-color: #e8f5e9 !important;
        }

        .form-control.error:focus {
            border-color: #e53935 !important;
            box-shadow: 0 0 0 2px rgba(229,57,53,0.1) !important;
        }

        .form-control.valid:focus {
            border-color: #2e7d32 !important;
            box-shadow: 0 0 0 2px rgba(46,125,50,0.1) !important;
        }
    </style>
</head>
<body>
   <!-- Header and Navigation Container -->
    <div class="header-nav-container">
        <!-- Header -->
        <header class="header">
            <style>
            .user-icon a {
                color: white !important;
                text-decoration: none !important;
                font-weight: 500;
                font-size: 20px;
                padding-right: 20px;
            }

            .user-actions {
                margin-right: 30px;
            }
            </style>

            <div class="header-left">
                <img src="img/logo.jpg" alt="Logo" class="logo">
                <div class="header-title">
                    SÀN GIAO DỊCH NÔNG SẢN
                    <span>Kết nối nông dân - Phát triển bền vững</span>
                </div>
            </div>
            <div class="user-actions">
                <?php if (isset($_SESSION['user']['TenDangNhap'])): ?>
                <div class="custom-dropdown">
                  <span class="user-icon" onclick="toggleDropdown()">
                    <i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION['user']['TenDangNhap']); ?> <span style="font-size:12px;">▼</span>
                  </span>
                  <div class="dropdown-content" id="userDropdownMenu">
                    <a href="capnhatthongtin.php"><i class="fas fa-user-edit"></i> Thông tin cá nhân</a>
                    <a href="taikhoan.php#password"><i class="fas fa-key"></i> Đổi mật khẩu</a>
                    <hr>
                    <a href="index.php"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a>
                  </div>
                </div>
                <?php else: ?>
                <a class="user-icon" href="dangnhap.php">Đăng nhập</a> 
                <a class="user-icon" href="dangky.php">Đăng ký</a>
                <?php endif; ?>
            </div>
        </header>
        
        <!-- Main Navigation -->
        <nav class="main-nav">
            <ul class="nav-list">
                <?php if ($vaiTro === 'Quản lý'): ?>
                    <li class="nav-item"><a href="quanlytrangchu.php" class="nav-link">TRANG CHỦ</a></li>
                    <li class="nav-item"><a href="gioithieu.php" class="nav-link">GIỚI THIỆU</a></li>
                    <li class="nav-item">
                        <a href="#" class="nav-link">QUẢN LÍ</a>
                        <ul class="sub-menu">
                            <li><a href="quanlibanhang.php" class="sub-menu-link">Quản lí người bán</a></li>
                            <li><a href="quanlikhachhang.php" class="sub-menu-link">Quản lí khách hàng</a></li>
                            <li><a href="quanlitaikhoan.php" class="sub-menu-link">Quản lí tài khoản</a></li>
                            <li><a href="quanlysanpham.php" class="sub-menu-link">Quản lí sản phẩm</a></li>
                            <li><a href="duyet_sanpham.php" class="sub-menu-link">Quản lí duyệt sản phẩm</a></li>
                            <li><a href="baocaothongke.php" class="sub-menu-link">Báo cáo thống kê</a></li>
                        </ul>
                    </li>
                    <li class="nav-item"><a href="tintuc.php" class="nav-link">TIN TỨC</a></li>
                    <li class="nav-item"><a href="xem_lienhe.php" class="nav-link">LIÊN HỆ</a></li>
                <?php elseif ($vaiTro === 'Bán hàng'): ?>
                    <li class="nav-item"><a href="banhangtrangchu.php" class="nav-link">TRANG CHỦ</a></li>
                    <li class="nav-item"><a href="gioithieu.php" class="nav-link">GIỚI THIỆU</a></li>
                    <li class="nav-item"><a href="banhang_donhang.php" class="nav-link">ĐƠN HÀNG</a></li>
                    <li class="nav-item"><a href="baocaothongke.php" class="nav-link">BÁO CÁO THỐNG KÊ</a></li>
                    <li class="nav-item"><a href="tintuc.php" class="nav-link">TIN TỨC</a></li>
                    <li class="nav-item"><a href="lienhe.php" class="nav-link">LIÊN HỆ</a></li>
                <?php else: ?>
                    <li class="nav-item"><a href="trangchukhachhang.php" class="nav-link">TRANG CHỦ</a></li>
                    <li class="nav-item"><a href="gioithieu.php" class="nav-link">GIỚI THIỆU</a></li>
                    <li class="nav-item">
                        <a href="#" class="nav-link">SẢN PHẨM</a>
                        <ul class="sub-menu">
                            <li><a href="trangchukhachhang.php?category=Trái cây" class="sub-menu-link">Trái cây</a></li>
                            <li><a href="trangchukhachhang.php?category=Rau củ" class="sub-menu-link">Rau củ</a></li>
                            <li><a href="trangchukhachhang.php?category=Lúa gạo" class="sub-menu-link">Lúa gạo</a></li>
                            <li><a href="trangchukhachhang.php?category=Thủy sản" class="sub-menu-link">Thủy sản</a></li>
                            <li><a href="trangchukhachhang.php?category=Sản phẩm OCOP" class="sub-menu-link">Sản phẩm OCOP</a></li>
                        </ul>
                    </li>
                    <li class="nav-item"><a href="DonHang.php" class="nav-link">ĐƠN HÀNG</a></li>
                    <li class="nav-item"><a href="tintuc.php" class="nav-link">TIN TỨC</a></li>
                    <li class="nav-item"><a href="lienhe.php" class="nav-link">LIÊN HỆ</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
    <!-- Main Content -->
    <main class="main-content">
        <div class="profile-container">
            <h2 class="section-title">Cập nhật thông tin cá nhân</h2>
            
            <form method="POST" action="">
                <?php if ($vaiTro == 'Khách hàng'): ?>
                    <div class="mb-3">
                        <label class="form-label required-field">Họ tên khách hàng</label>
                        <input type="text" class="form-control" name="ten" value="<?php echo htmlspecialchars($user['TenKhachHang'] ?? ''); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label required-field">Ngày sinh</label>
                        <input type="date" class="form-control" name="ngaysinh" value="<?php echo htmlspecialchars($user['NgaySinh'] ?? ''); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label required-field">Email</label>
                        <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($user['Email'] ?? ''); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label required-field">Địa chỉ</label>
                        <input type="text" class="form-control" name="diachi" value="<?php echo htmlspecialchars($user['DiaChi'] ?? ''); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label required-field">Số điện thoại</label>
                        <input type="text" class="form-control" name="sdt" value="<?php echo htmlspecialchars($user['SDT'] ?? ''); ?>" required>
                    </div>
                <?php elseif ($vaiTro == 'Bán hàng'): ?>
                    <div class="mb-3">
                        <label class="form-label required-field">Họ tên người bán</label>
                        <input type="text" class="form-control" name="ten" value="<?php echo htmlspecialchars($user['TenNguoiBan'] ?? ''); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label required-field">Ngày sinh</label>
                        <input type="date" class="form-control" name="ngaysinh" value="<?php echo htmlspecialchars($user['NgaySinh'] ?? ''); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label required-field">Email</label>
                        <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($user['Email'] ?? ''); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label required-field">Địa chỉ</label>
                        <input type="text" class="form-control" name="diachi" value="<?php echo htmlspecialchars($user['DiaChi'] ?? ''); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label required-field">Số điện thoại</label>
                        <input type="text" class="form-control" name="sdt" value="<?php echo htmlspecialchars($user['SDT'] ?? ''); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Mô tả gian hàng</label>
                        <input type="text" class="form-control" name="mota" value="<?php echo htmlspecialchars($user['MoTaGianHang'] ?? ''); ?>">
                    </div>
                <?php elseif ($vaiTro == 'Quản lý'): ?>
                    <div class="mb-3">
                        <label class="form-label required-field">Họ tên quản lý</label>
                        <input type="text" class="form-control" name="ten" value="<?php echo htmlspecialchars($user['TenQuanLy'] ?? ''); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label required-field">Ngày sinh</label>
                        <input type="date" class="form-control" name="ngaysinh" value="<?php echo htmlspecialchars($user['NgaySinh'] ?? ''); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label required-field">Email</label>
                        <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($user['Email'] ?? ''); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label required-field">Địa chỉ</label>
                        <input type="text" class="form-control" name="diachi" value="<?php echo htmlspecialchars($user['DiaChi'] ?? ''); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label required-field">Số điện thoại</label>
                        <input type="text" class="form-control" name="sdt" value="<?php echo htmlspecialchars($user['SDT'] ?? ''); ?>" required>
                    </div>
                <?php endif; ?>
                <div class="text-center mt-4">
                    <button type="submit" class="btn btn-primary px-5">Cập nhật thông tin</button>
                </div>
            </form>
        </div>
    </main>
    <!-- Footer -->
    <footer class="footer">
        <div class="footer-grid">
            <div class="footer-column">
                <h3>GIỚI THIỆU</h3>
                <p>Sàn giao dịch nông sản là nền tảng kết nối trực tiếp giữa nông dân, doanh nghiệp và người tiêu dùng, nhằm tạo ra chuỗi giá trị bền vững cho ngành nông nghiệp.</p>
            </div>
            <div class="footer-column">
                <h3>LIÊN KẾT NHANH</h3>
                <ul class="footer-links">
                    <li><a href="trangchukhachhang.php" class="footer-link">Trang chủ</a></li>
                    <li><a href="gioithieu.php" class="footer-link">Giới thiệu</a></li>
                    <li><a href="trangchukhachhang.php" class="footer-link">Sản phẩm</a></li>
                    <li><a href="tintuc.php" class="footer-link">Tin tức</a></li>
                    <li><a href="lienhe.php" class="footer-link">Liên hệ</a></li>
                </ul>
            </div>
            <div class="footer-column">
                <h3>DANH MỤC SẢN PHẨM</h3>
                <ul class="footer-links">
                    <li><a href="trangchukhachhang.php?category=Trái cây" class="footer-link">Trái cây</a></li>
                    <li><a href="trangchukhachhang.php?category=Rau củ" class="footer-link">Rau củ</a></li>
                    <li><a href="trangchukhachhang.php?category=Lúa gạo" class="footer-link" >Lúa gạo</a></li>
                    <li><a href="trangchukhachhang.php?category=Thủy sản" class="footer-link" >Thủy sản</a></li>
                    <li><a href="trangchukhachhang.php?category=Sản phẩm OCOP" class="footer-link">Sản phẩm OCOP</a></li>
                </ul>
            </div>
            <div class="footer-column">
                <h3>THÔNG TIN LIÊN HỆ</h3>
                <div class="contact-info">
                    <span class="contact-icon">📍</span>
                    <span>Số xx, đường xxx, phường xxx, Thành phố xxx</span>
                </div>
                <div class="contact-info">
                    <span class="contact-icon">📞</span>
                    <span>Hotline: 0123 456 789</span>
                </div>
                <div class="contact-info">
                    <span class="contact-icon">✉️</span>
                    <span>Email: info@sangiaodichnongsan.vn</span>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2025 Sàn giao dịch nông sản. Tất cả quyền được bảo lưu.</p>
        </div>
    </footer>
    <!-- Cart Modal, Product Detail Modal, Toast Notification, JS giống trangchukhachhang.php -->
    <div id="cart-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Giỏ hàng của bạn</h2>
                <span class="close-btn" onclick="closeCart()">&times;</span>
            </div>
            <div id="cart-items-container" class="cart-items"></div>
            <div id="empty-cart" class="empty-cart" style="display:none;">
                <div class="empty-cart-icon">🛒</div>
                <div class="empty-cart-message">Giỏ hàng của bạn đang trống</div>
                <button class="btn-continue" onclick="closeCart()">Tiếp tục mua sắm</button>
            </div>
            <div class="cart-total">
                <span class="cart-total-label">Tổng cộng:</span>
                <span class="cart-total-price" id="cart-total-amount">0₫</span>
            </div>
            <div class="cart-actions">
                <button class="btn-continue" onclick="closeCart()">Tiếp tục mua sắm</button>
                <button class="btn-checkout" onclick="checkout()">Đặt hàng</boutton>
            </div>
        </div>
    </div>
    <div id="product-detail-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="product-detail-title">Chi tiết sản phẩm</h2>
                <span class="close-btn" onclick="closeProductDetails()">&times;</span>
            </div>
            <div id="product-detail-content">
                <!-- Product details will be displayed here -->
            </div>
        </div>
    </div>
    <!-- Toast Notification -->
    <div class="toast" id="toast-notification">
        <span class="toast-icon" id="toast-icon">✓</span>
        <span id="toast-message"></span>
    </div>
    <!-- Modal xác nhận cập nhật -->
    <div id="modal-xac-nhan-gui" class="modal-xac-nhan">
        <div class="modal-xac-nhan-content">
            <h3>Xác nhận cập nhật</h3>
            <p>Bạn có chắc muốn cập nhật thông tin?</p>
            <div class="modal-xac-nhan-actions">
                <button id="btn-xac-nhan-gui" class="nut nut-xoa">Cập nhật</button>
                <button id="btn-huy-gui" class="nut nut-sua">Hủy</button>
            </div>
        </div>
    </div>
    <script src="js/trangchukhachhang.js"></script>
    <script>
    function toggleDropdown() {
      var menu = document.getElementById('userDropdownMenu');
      menu.style.display = (menu.style.display === 'block') ? 'none' : 'block';
    }
    window.onclick = function(event) {
      if (!event.target.matches('.user-icon') && !event.target.closest('.custom-dropdown')) {
        var dropdowns = document.getElementsByClassName('dropdown-content');
        for (var i = 0; i < dropdowns.length; i++) {
          dropdowns[i].style.display = 'none';
        }
      }
    }
    function showToast(message, type = 'success') {
        const toast = document.getElementById('toast-notification');
        const toastMessage = document.getElementById('toast-message');
        const toastIcon = document.getElementById('toast-icon');
        
        if (type === 'success') {
            toast.className = 'toast show';
            toastIcon.textContent = '✓';
        } else {
            toast.className = 'toast show error';
            toastIcon.textContent = '✖';
        }
        
        toastMessage.textContent = message;
        
        setTimeout(() => {
            toast.className = 'toast';
        }, 2500);
    }

    // Validation functions
    function validateName(name) {
        // Tên chỉ được chứa chữ cái và khoảng trắng
        const nameRegex = /^[A-Za-zÀ-ỹ\s]+$/;
        return nameRegex.test(name);
    }

    function validatePhone(phone) {
        // Số điện thoại phải có 10 chữ số
        const phoneRegex = /^[0-9]{10}$/;
        return phoneRegex.test(phone);
    }

    function validateEmail(email) {
        // Kiểm tra định dạng email và chỉ chấp nhận domain @gmail.com
        const emailRegex = /^[a-zA-Z0-9._%+-]+@gmail\.com$/;
        return emailRegex.test(email);
    }

    function showError(inputElement, errorMessage) {
        const errorSpan = inputElement.nextElementSibling;
        if (!errorSpan || !errorSpan.classList.contains('error-message')) {
            const span = document.createElement('span');
            span.className = 'error-message';
            inputElement.parentElement.appendChild(span);
        }
        const errorElement = errorSpan || inputElement.parentElement.querySelector('.error-message');
        errorElement.textContent = errorMessage;
        errorElement.style.display = 'block';
        inputElement.classList.add('input-error');
        inputElement.classList.remove('input-success');
    }

    function showSuccess(inputElement) {
        const errorSpan = inputElement.nextElementSibling;
        if (errorSpan && errorSpan.classList.contains('error-message')) {
            errorSpan.style.display = 'none';
        }
        inputElement.classList.remove('input-error');
        inputElement.classList.add('input-success');
    }

    function validateInput(inputElement, validationFunction, errorMessage) {
        const value = inputElement.value.trim();
        if (!value) {
            showError(inputElement, 'Trường này không được để trống');
            return false;
        }
        if (!validationFunction(value)) {
            showError(inputElement, errorMessage);
            return false;
        }
        showSuccess(inputElement);
        return true;
    }

    // Add validation styles
    const styleSheet = document.createElement('style');
    styleSheet.textContent = `
        .error-message {
            color: #e53935;
            font-size: 14px;
            margin-top: 5px;
            display: none;
            font-weight: 500;
            padding-left: 2px;
        }

        .form-control.error {
            border-color: #e53935 !important;
            background-color: #ffebee !important;
        }

        .form-control.valid {
            border-color: #2e7d32 !important;
            background-color: #e8f5e9 !important;
        }

        .form-control.error:focus {
            border-color: #e53935 !important;
            box-shadow: 0 0 0 2px rgba(229,57,53,0.1) !important;
        }

        .form-control.valid:focus {
            border-color: #2e7d32 !important;
            box-shadow: 0 0 0 2px rgba(46,125,50,0.1) !important;
        }
    `;
    document.head.appendChild(styleSheet);

    // Add input event listeners
    document.addEventListener('DOMContentLoaded', function() {
        // Get all input fields
        const nameInputs = document.querySelectorAll('input[name="ten"]');
        const phoneInputs = document.querySelectorAll('input[name="sdt"]');
        const emailInputs = document.querySelectorAll('input[name="email"]');
        const requiredInputs = document.querySelectorAll('input[required]');

        // Add validation for name fields
        nameInputs.forEach(input => {
            input.addEventListener('blur', function() {
                if (this.value.trim()) {
                    validateInput(this, validateName, 'Tên chỉ được chứa chữ cái và khoảng trắng');
                }
            });
        });

        // Add validation for phone fields
        phoneInputs.forEach(input => {
            input.addEventListener('blur', function() {
                if (this.value.trim()) {
                    validateInput(this, validatePhone, 'Số điện thoại phải có đúng 10 chữ số');
                }
            });
        });

        // Add validation for email fields
        emailInputs.forEach(input => {
            input.addEventListener('blur', function() {
                if (this.value.trim()) {
                    validateInput(this, validateEmail, 'Email phải có định dạng example@gmail.com');
                }
            });
        });

        // Add validation for required fields
        requiredInputs.forEach(input => {
            input.addEventListener('blur', function() {
                if (!this.value.trim()) {
                    showError(this, 'Trường này không được để trống');
                } else {
                    showSuccess(this);
                }
            });
        });

        // Clear error on input
        requiredInputs.forEach(input => {
            input.addEventListener('input', function() {
                const errorSpan = this.nextElementSibling;
                if (errorSpan && errorSpan.classList.contains('error-message')) {
                    errorSpan.style.display = 'none';
                }
                this.classList.remove('input-error');
            });
        });
    });

    // Update form submission
    let submitForm = null;

    // Validate form before showing confirmation modal
    document.querySelector('form').addEventListener('submit', function(e) {
        e.preventDefault();
        
        let isValid = true;
        const nameInput = this.querySelector('input[name="ten"]');
        const phoneInput = this.querySelector('input[name="sdt"]');
        const emailInput = this.querySelector('input[name="email"]');
        const requiredInputs = this.querySelectorAll('input[required]');

        // Validate required fields
        requiredInputs.forEach(input => {
            if (!input.value.trim()) {
                showError(input, 'Trường này không được để trống');
                isValid = false;
            }
        });

        // Validate name
        if (nameInput && !validateInput(nameInput, validateName, 'Tên chỉ được chứa chữ cái và khoảng trắng')) {
            isValid = false;
        }

        // Validate phone
        if (phoneInput && !validateInput(phoneInput, validatePhone, 'Số điện thoại phải có đúng 10 chữ số')) {
            isValid = false;
        }

        // Validate email
        if (emailInput && !validateInput(emailInput, validateEmail, 'Email phải có định dạng example@gmail.com')) {
            isValid = false;
        }

        if (isValid) {
            submitForm = this;
            document.getElementById('modal-xac-nhan-gui').style.display = 'flex';
        }
    });

    // Xử lý nút xác nhận trong modal
    document.getElementById('btn-xac-nhan-gui').addEventListener('click', function() {
        if (submitForm) {
            submitForm.submit();
        }
        document.getElementById('modal-xac-nhan-gui').style.display = 'none';
    });

    // Xử lý nút hủy trong modal
    document.getElementById('btn-huy-gui').addEventListener('click', function() {
        document.getElementById('modal-xac-nhan-gui').style.display = 'none';
    });

    // Đóng modal khi click bên ngoài
    document.getElementById('modal-xac-nhan-gui').addEventListener('click', function(e) {
        if (e.target === this) {
            this.style.display = 'none';
        }
    });

    <?php if ($message): ?>
        document.addEventListener('DOMContentLoaded', function() {
            showToast("<?php echo addslashes($message); ?>", 'success');
        });
    <?php endif; ?>
    
    <?php if ($error): ?>
        document.addEventListener('DOMContentLoaded', function() {
            showToast("<?php echo addslashes($error); ?>", 'error');
        });
    <?php endif; ?>
    </script>
</body>
</html> 
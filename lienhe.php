<?php
session_start();
$vaiTro = isset($_SESSION['user']['VaiTro']) ? $_SESSION['user']['VaiTro'] : '';
include('config.php');

$idTaiKhoan = $_SESSION['user']['IdTaiKhoan'];
$idKhachHang = null;
$idNguoiBan = null;
$idQuanLy = null;

if ($vaiTro == 'Khách hàng') {
    $sql = "SELECT * FROM khachhang WHERE IdTaiKhoan = '$idTaiKhoan'";
    $result = mysqli_query($conn, $sql);
    $kh = mysqli_fetch_assoc($result);
    $idKhachHang = $kh['IdKhachHang'];
    $idQuanLy = $kh['IdNguoiQuanLy'];
} elseif ($vaiTro == 'Bán hàng') {
    $sql = "SELECT * FROM nguoiban WHERE IdTaiKhoan = '$idTaiKhoan'";
    $result = mysqli_query($conn, $sql);
    $nb = mysqli_fetch_assoc($result);
    $idNguoiBan = $nb['IdNguoiBan'];
    $idQuanLy = $nb['IdNguoiQuanLy'];
} else {
    die('Chỉ khách hàng hoặc người bán mới được gửi liên hệ!');
}

$thongbao = '';

// Tạo ID liên hệ mới là số nguyên (không có tiền tố LH)
$sql_max_id = "SELECT MAX(IdLienHe) AS max_id FROM lienhe";
$result_max = mysqli_query($conn, $sql_max_id);
$row = mysqli_fetch_assoc($result_max);
$max_id = $row['max_id'] ? $row['max_id'] + 1 : 1; // Nếu không có bản ghi nào, bắt đầu từ 1
$idLienHe = $max_id;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $tieuDe = $_POST['tieude'];
    $noiDung = $_POST['noidung'];
    $ngayGui = date('Y-m-d');

    if ($vaiTro == 'Khách hàng') {
        $insert = "INSERT INTO lienhe (IdLienHe, IdKhachHang, IdNguoiBan, IdNguoiQuanLy, TieuDe, NoiDung, NgayGui)
                   VALUES ($idLienHe, '$idKhachHang', NULL, '$idQuanLy', '$tieuDe', '$noiDung', '$ngayGui')";
    } elseif ($vaiTro == 'Bán hàng') {
        $insert = "INSERT INTO lienhe (IdLienHe, IdKhachHang, IdNguoiBan, IdNguoiQuanLy, TieuDe, NoiDung, NgayGui)
                   VALUES ($idLienHe, NULL, '$idNguoiBan', '$idQuanLy', '$tieuDe', '$noiDung', '$ngayGui')";
    }

    if (mysqli_query($conn, $insert)) {
        $thongbao = "<p class='success'>🎉 Gửi liên hệ thành công!</p>";
    } else {
        $thongbao = "<p class='error'>❌ Lỗi: " . mysqli_error($conn) . "</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Gửi liên hệ</title>
    <link rel="stylesheet" href="css/giaodienkhachhang.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: #f4f4f9;
            font-family: 'Segoe UI', Arial, sans-serif;
            margin: 0;
            padding-top: 160px; /* Add padding for fixed header */
        }

        .header-nav-container {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            background-color: #fff;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .contact-container {
            max-width: 500px;
            margin: 64px auto;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 32px rgba(46,125,50,0.10);
            padding: 40px 48px 32px 48px;
        }
        .section-title {
            color: #2e7d32;
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 24px;
            text-align: center;
            letter-spacing: 1px;
        }
        .form-label {
            font-weight: 600;
            color: #2e7d32;
            margin-bottom: 6px;
            display: block;
            font-size: 1.08rem;
        }
        .form-control, textarea {
            width: 100%;
            padding: 12px 14px;
            border: 1.5px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1.08rem;
            background: #fafbfc;
            margin-bottom: 18px;
            transition: border 0.2s, box-shadow 0.2s;
            outline: none;
        }
        .form-control:focus, textarea:focus {
            border: 1.5px solid #2e7d32;
            box-shadow: 0 0 0 2px #e0f2e9;
            background: #fff;
        }
        .btn-primary {
            background: linear-gradient(90deg, #2e7d32 60%, #43a047 100%);
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
            background: linear-gradient(90deg, #43a047 60%, #2e7d32 100%);
            box-shadow: 0 4px 16px rgba(67,160,71,0.13);
        }
        .alert {
            width: 100%;
            text-align: center;
            font-size: 1.05rem;
            margin-bottom: 18px;
            border-radius: 8px;
            padding: 10px 0;
        }
        .alert-success {
            background: #e0f2e9;
            color: #257a2a;
            border-left: 5px solid #43a047;
        }
        .alert-danger {
            background: #f8d7da;
            color: #e53935;
            border-left: 5px solid #e53935;
        }
        .back-button {
            position: absolute;
            top: 24px;
            left: 24px;
            background: #eee;
            padding: 8px 12px;
            border-radius: 6px;
            color: #2e7d32;
            text-decoration: none;
            font-weight: 500;
            transition: background 0.2s;
        }
        .back-button:hover {
            background: #e0f2e9;
        }
        @media (max-width: 600px) {
            .contact-container { padding: 10px 2px; }
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
<div class="contact-container">
    <h2 class="section-title">📩 Gửi thông tin liên hệ với chúng tôi</h2>
    <?php if ($thongbao): ?>
        <div class="alert <?= strpos($thongbao, 'thành công') !== false ? 'alert-success' : 'alert-danger' ?>">
            <?= $thongbao ?>
        </div>
    <?php endif; ?>
    <form method="POST">
        <label class="form-label" for="tieude">Tiêu đề:</label>
        <input type="text" name="tieude" id="tieude" class="form-control" required>
        <label class="form-label" for="noidung">Nội dung:</label>
        <textarea name="noidung" id="noidung" class="form-control" required></textarea>
        <button type="submit" class="btn-primary">Gửi liên hệ</button>
    </form>
</div>

<!-- Features Section -->
    <section class="features-section">
        <h2 class="section-title">DỊCH VỤ CỦA CHÚNG TÔI</h2>
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">🚚</div>
                <h3 class="feature-title">GIAO HÀNG TOÀN QUỐC</h3>
                <p class="feature-description">Giao hàng nhanh chóng, đảm bảo chất lượng sản phẩm đến tận tay khách hàng trên toàn quốc.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">✅</div>
                <h3 class="feature-title">SẢN PHẨM CHẤT LƯỢNG</h3>
                <p class="feature-description">Cam kết cung cấp sản phẩm nông sản tươi sạch, đạt tiêu chuẩn VietGAP, GlobalGAP.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">💰</div>
                <h3 class="feature-title">GIÁ CẢ HỢP LÝ</h3>
                <p class="feature-description">Giá cả cạnh tranh, đảm bảo lợi ích cho cả người sản xuất và người tiêu dùng.</p>
            </div>
        </div>
    </section>
    
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
                    <li><a href="#" class="footer-link">Trang chủ</a></li>
                    <li><a href="#" class="footer-link">Giới thiệu</a></li>
                    <li><a href="#" class="footer-link">Sản phẩm</a></li>
                    <li><a href="#" class="footer-link">Tin tức</a></li>
                    <li><a href="#" class="footer-link">Liên hệ</a></li>
                </ul>
            </div>
            
            <div class="footer-column">
                <h3>DANH MỤC SẢN PHẨM</h3>
                <ul class="footer-links">
                    <li><a href="#" class="footer-link">Trái cây</a></li>
                    <li><a href="#" class="footer-link">Rau củ</a></li>
                    <li><a href="#" class="footer-link">Lúa gạo</a></li>
                    <li><a href="#" class="footer-link">Thủy sản</a></li>
                    <li><a href="#" class="footer-link">Sản phẩm OCOP</a></li>
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

<!-- Cart Modal -->
<div id="cart-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title">Giỏ hàng của bạn</h2>
            <span class="close-btn" onclick="closeCart()">&times;</span>
        </div>
        <div id="cart-items-container" class="cart-items">
            <!-- Cart items will be displayed here -->
        </div>
        <div class="cart-total">
            <span class="cart-total-label">Tổng cộng:</span>
            <span class="cart-total-price" id="cart-total-amount">0₫</span>
        </div>
        <div class="cart-actions">
            <button class="btn-continue" onclick="closeCart()">Tiếp tục mua sắm</button>
            <button class="btn-checkout" onclick="checkout()">Thanh toán</button>
        </div>
    </div>
</div>
    <!-- Product Detail Modal -->
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
        <span class="toast-icon">✓</span>
        <span id="toast-message"></span>
    </div>
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
    </script>
</body>
</html>
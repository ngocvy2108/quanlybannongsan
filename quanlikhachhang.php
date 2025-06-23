<?php
include("config.php");
session_start();
$vaiTro = isset($_SESSION['user']['VaiTro']) ? $_SESSION['user']['VaiTro'] : '';
// Xử lý xóa nếu có GET['delete']
if (isset($_GET['delete'])) {
    $IdKhachHang = $_GET['delete'];
    if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
        $force = isset($_GET['force']) && $_GET['force'] == 1;
        $checkDonHang = $conn->query("SELECT COUNT(*) as total FROM donhang WHERE IdKhachHang='$IdKhachHang'");
        $rowDonHang = $checkDonHang->fetch_assoc();
        $checkGioHang = $conn->query("SELECT COUNT(*) as total FROM giohang WHERE IdKhachHang='$IdKhachHang'");
        $rowGioHang = $checkGioHang->fetch_assoc();
        $checkLienHe = $conn->query("SELECT COUNT(*) as total FROM lienhe WHERE IdKhachHang='$IdKhachHang'");
        $rowLienHe = $checkLienHe->fetch_assoc();
        if (!$force && ($rowDonHang['total'] > 0 || $rowGioHang['total'] > 0 || $rowLienHe['total'] > 0)) {
            $msg = '';
            if ($rowDonHang['total'] > 0) $msg .= 'Không thể xóa khách hàng vì còn đơn hàng liên quan!<br>';
            if ($rowGioHang['total'] > 0) $msg .= 'Không thể xóa khách hàng vì còn sản phẩm trong giỏ hàng!<br>';
            if ($rowLienHe['total'] > 0) $msg .= 'Không thể xóa khách hàng vì còn liên hệ liên quan!';
            $success = false;
            $message = $msg;
        } else {
            if ($force) {
                // Lấy tất cả IdDonHang của khách này
                $resultDonHang = $conn->query("SELECT IdDonHang FROM donhang WHERE IdKhachHang='$IdKhachHang'");
                $idDonHangArr = [];
                while ($row = $resultDonHang->fetch_assoc()) {
                    $idDonHangArr[] = $row['IdDonHang'];
                }
                if (!empty($idDonHangArr)) {
                    $idDonHangList = "'" . implode("','", $idDonHangArr) . "'";
                    // Xóa chi tiết đơn hàng
                    $conn->query("DELETE FROM chitietdonhang WHERE IdDonHang IN ($idDonHangList)");
                    // Xóa thanh toán
                    $conn->query("DELETE FROM thanhtoan WHERE IdDonHang IN ($idDonHangList)");
                }
                // Xóa đơn hàng
                $conn->query("DELETE FROM donhang WHERE IdKhachHang='$IdKhachHang'");
                // Xóa giỏ hàng
                $conn->query("DELETE FROM giohang WHERE IdKhachHang='$IdKhachHang'");
                // Xóa liên hệ
                $conn->query("DELETE FROM lienhe WHERE IdKhachHang='$IdKhachHang'");
            }
            $success = $conn->query("DELETE FROM khachhang WHERE IdKhachHang='$IdKhachHang'");
            $message = $success ? 'Xóa thành công' : 'Lỗi: ' . $conn->error;
        }
        header('Content-Type: application/json');
        echo json_encode(['success' => $success, 'message' => $message]);
        exit();
    } else {
        $conn->query("DELETE FROM khachhang WHERE IdKhachHang='$IdKhachHang'");
    }
}

// Xử lý tìm kiếm
$search_result = null;
if (isset($_GET['search_id'])) {
    $search_id = $_GET['search_id'];
    $stmt = $conn->prepare("SELECT * FROM khachhang WHERE IdKhachHang = ?");
    $stmt->bind_param("s", $search_id);
    $stmt->execute();
    $search_result = $stmt->get_result();
} else {
    $sql_khachhang = "SELECT * FROM khachhang";
    $search_result = $conn->query($sql_khachhang);
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="css/giaodienkhachhang.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <title>Thông tin khách hàng</title>
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background-color: #f4f4f9;
        }
        h2 {
            text-align: center;
            color: #2e7d32;
            margin-top: 20px;
        }
        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #2e7d32;
            padding: 10px 20px;
            border-radius: 0 0 10px 10px;
            box-shadow: 0 2px 8px rgba(46,125,50,0.10);
        }
        .navbar a {
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            font-weight: bold;
            margin-right: 10px;
            border-radius: 6px;
            transition: background 0.2s;
        }
        .navbar a:hover {
            background-color: #e65100;
            opacity: 0.9;
        }
        .search-form {
            display: flex;
            align-items: center;
        }
        .search-form input[type="text"] {
            padding: 6px;
            width: 180px;
            font-size: 14px;
            border: none;
            border-radius: 4px;
            margin-right: 6px;
        }
        .search-form input[type="submit"] {
            padding: 6px 10px;
            background-color: #fff;
            color: #2e7d32;
            font-weight: bold;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .search-form input[type="submit"]:hover {
            opacity: 0.85;
        }
        table {
            width: 95%;
            margin: 20px auto;
            border-collapse: collapse;
            background: #fff;
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 20px;
        }
        th, td {
            padding: 14px 10px;
            text-align: center;
            border: 1px solid #eee;
        }
        th {
            background-color: #e0f2f1;
            color: #2e7d32;
            font-weight: 600;
        }
        .table-nguoiban tr:nth-child(even) td {
            background-color: #e8f5e9;
        }
        .table-nguoiban tr:nth-child(odd) td {
            background-color: #fff;
        }
        tr:hover { background-color: #f1f8e9; }
        .action-group {
            display: flex;
            gap: 10px;
            justify-content: center;
            align-items: center;
        }
        .container {
            max-width: 1100px;
            margin: 40px auto;
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 4px 24px rgba(46,125,50,0.10);
            padding: 36px 32px 28px 32px;
        }
        .action-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 10px 20px;
            border: none;
            border-radius: 999px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(46,125,50,0.08);
            transition: background 0.2s, box-shadow 0.2s, color 0.2s;
            outline: none;
        }
        .edit-btn {
            background: #2e7d32;
            color: #fff;
            border: 2px solid #2e7d32;
        }
        .edit-btn:hover {
            background: #1b5e20;
            color: #fff;
            box-shadow: 0 4px 16px rgba(46,125,50,0.15);
        }
        .delete-btn {
            background: #e65100;
            color: #fff;
            border: 2px solid #e65100;
        }
        .delete-btn:hover {
            background: #bf360c;
            color: #fff;
            box-shadow: 0 4px 16px rgba(230,81,0,0.15);
        }
        .action-btn i {
            font-size: 17px;
        }
        .back-btn {
            display: block;
            width: fit-content;
            margin: 20px 30px 10px auto;
            text-decoration: none;
            padding: 10px 20px;
            font-weight: bold;
            background-color: #2e7d32;
            color: white;
            border-radius: 6px;
        }
        .back-btn:hover {
            opacity: 0.85;
            background: #e65100;
        }
        @media (max-width: 700px) {
            table, th, td { font-size: 13px; }
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
    .modal-xoa {
      display: none;
      position: fixed;
      z-index: 9999;
      left: 0; top: 0; width: 100%; height: 100%;
      background: rgba(44, 62, 80, 0.25);
      justify-content: center; align-items: center;
      animation: fadeIn 0.2s;
    }
    @keyframes fadeIn {
      from { opacity: 0; }
      to { opacity: 1; }
    }
    .modal-xoa-content {
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
    .modal-xoa-content h3 {
      color: #257a2a;
      margin-bottom: 18px;
      font-size: 1.35rem;
      font-weight: 700;
      letter-spacing: 0.5px;
    }
    .modal-xoa-content p {
      color: #333;
      font-size: 1.08rem;
      margin-bottom: 28px;
    }
    .modal-xoa-actions {
      display: flex;
      justify-content: center;
      gap: 22px;
    }
    .modal-xoa .nut {
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
    .nut-xoa { background: #e53935; color: #fff; }
    .nut-xoa:hover { background: #b71c1c; color: #fff; }
    .nut-sua { background: linear-gradient(90deg, #43a047 60%, #257a2a 100%); color: #fff; }
    .nut-sua:hover { background: linear-gradient(90deg, #257a2a 60%, #43a047 100%); color: #fff; }
    </style>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
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
            
            <div class="search-box">
                <form method="GET" action="quanlikhachhang.php">
                    <input type="text" name="search_id" placeholder="Nhập ID khách hàng..." required>
                    <button type="submit">Tìm</button>
                </form>
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

    
    <!-- Banner -->
    <div class="banner-container">
        <img src="img/banner.jpg" alt="Banner" class="banner-image">
    </div>

<h2>THÔNG TIN KHÁCH HÀNG</h2>

<table class="table-nguoiban">
    <tr>
        <th>ID Khách hàng</th>
        <th>Tên Khách hàng</th>
        <th>Địa chỉ</th>
        <th>Số điện thoại</th>
        <th>ID Tài khoản</th>
        <th>ID Người quản lý</th>
        <th>Thao tác</th>
    </tr>
    <?php
    if ($search_result && $search_result->num_rows > 0) {
        while ($row = $search_result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>".$row["IdKhachHang"]."</td>";
            echo "<td>".$row["TenKhachHang"]."</td>";
            echo "<td>".$row["DiaChi"]."</td>";
            echo "<td>".$row["SDT"]."</td>";
            echo "<td>".$row["IdTaiKhoan"]."</td>";
            echo "<td>".$row["IdNguoiQuanLy"]."</td>";
            echo "<td><div class='action-group'>
                <a class='action-btn edit-btn' href='suakhachhang.php?id=".$row["IdKhachHang"]."'><i class='bi bi-pencil-square'></i> Sửa</a>
                <a class='action-btn delete-btn btn-xoa-khachhang' href='#' data-id='".$row["IdKhachHang"]."'><i class='bi bi-trash'></i> Xóa</a>
                </div></td>";
            echo "</tr>";
        }
    } else {
        echo "<tr><td colspan='7'>Không tìm thấy khách hàng nào.</td></tr>";
    }
    ?>
</table>
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
    <div id="toast-notification" style="position:fixed;z-index:99999;right:32px;bottom:32px;min-width:240px;display:none;padding:16px 28px;background:#fff;border-radius:8px;box-shadow:0 2px 16px rgba(46,125,50,0.13);font-size:1.08rem;font-weight:500;color:#333;align-items:center;gap:10px;"></div>
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
    let customerIdToDelete = null;

    document.addEventListener('DOMContentLoaded', function() {
        // Gán sự kiện cho nút xóa trong bảng
        window.showDeleteModal = function(id) {
            customerIdToDelete = id;
            document.getElementById('modal-xac-nhan-xoa').style.display = 'flex';
        };

        document.getElementById('btn-huy-xoa').onclick = function() {
            document.getElementById('modal-xac-nhan-xoa').style.display = 'none';
            customerIdToDelete = null;
        };

        document.getElementById('btn-xac-nhan-xoa').onclick = function() {
            if (customerIdToDelete) {
                window.location.href = '?delete=' + customerIdToDelete;
            }
        };

        // Đóng modal khi bấm ra ngoài
        window.onclick = function(event) {
            if (event.target == document.getElementById('modal-xac-nhan-xoa')) {
                document.getElementById('modal-xac-nhan-xoa').style.display = 'none';
                customerIdToDelete = null;
            }
            // Đóng dropdown user
            if (!event.target.matches('.user-icon') && !event.target.closest('.custom-dropdown')) {
                var dropdowns = document.getElementsByClassName('dropdown-content');
                for (var i = 0; i < dropdowns.length; i++) {
                    dropdowns[i].style.display = 'none';
                }
            }
        }
    });
    </script>

<!-- Modal xác nhận xóa -->
<div id="modal-xac-nhan-xoa" class="modal-xoa" style="display:none;z-index:99999;">
  <div class="modal-xoa-content">
    <h3 style="color:#257a2a;">Xác nhận xóa khách hàng</h3>
    <p>Bạn có chắc chắn muốn xóa khách hàng này không?</p>
    <div class="modal-xoa-actions">
      <button id="btn-xac-nhan-xoa" class="nut nut-xoa">Xóa</button>
      <button id="btn-huy-xoa" class="nut nut-sua">Hủy</button>
    </div>
  </div>
</div>

<!-- Modal xác nhận xóa khách hàng -->
<div id="modal-xac-nhan-xoa-khachhang" class="modal-xoa" style="display:none;z-index:99999;">
  <div class="modal-xoa-content">
    <h3 style="color:#257a2a;">Xác nhận xóa khách hàng</h3>
    <p>Bạn có chắc chắn muốn xóa khách hàng này không?<br><span style='color:#e53935;font-size:0.98rem;'>Hành động này không thể hoàn tác.</span></p>
    <div class="modal-xoa-actions">
      <button id="btn-xac-nhan-xoa-khachhang" class="nut nut-xoa">Xóa</button>
      <button id="btn-huy-xoa-khachhang" class="nut nut-sua">Hủy</button>
    </div>
  </div>
</div>

<!-- Modal xác nhận xóa liên quan -->
<div id="modal-xoa-lien-quan-khachhang" class="modal-xoa" style="display:none;z-index:99999;">
  <div class="modal-xoa-content">
    <h3 style="color:#e53935;">Xóa tất cả dữ liệu liên quan?</h3>
    <p id="modal-xoa-lien-quan-msg-khachhang">Khách hàng này còn liên quan đến dữ liệu khác.<br>Bạn có muốn xóa hết tất cả dữ liệu liên quan và xóa khách hàng không?</p>
    <div class="modal-xoa-actions">
      <button id="btn-xac-nhan-xoa-lien-quan-khachhang" class="nut nut-xoa">Xóa tất cả</button>
      <button id="btn-huy-xoa-lien-quan-khachhang" class="nut nut-sua">Hủy</button>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  let idKhachHangCanXoa = null;
  function showToast(msg, success = true) {
    const toast = document.getElementById('toast-notification');
    toast.textContent = msg;
    toast.style.background = success ? '#e8f5e9' : '#ffebee';
    toast.style.color = success ? '#257a2a' : '#e53935';
    toast.style.display = 'flex';
    setTimeout(() => { toast.style.display = 'none'; }, 2500);
  }
  function showModalXoaLienQuan(msg) {
    document.getElementById('modal-xoa-lien-quan-msg-khachhang').innerHTML = msg + '<br><span style="color:#e53935;font-size:0.98rem;">Hành động này sẽ xóa hết dữ liệu liên quan và không thể hoàn tác.</span>';
    document.getElementById('modal-xoa-lien-quan-khachhang').style.display = 'flex';
  }
  document.querySelectorAll('.btn-xoa-khachhang').forEach(btn => {
    btn.onclick = function(e) {
      e.preventDefault();
      idKhachHangCanXoa = this.getAttribute('data-id');
      document.getElementById('modal-xac-nhan-xoa-khachhang').style.display = 'flex';
    };
  });
  document.getElementById('btn-huy-xoa-khachhang').onclick = function() {
    document.getElementById('modal-xac-nhan-xoa-khachhang').style.display = 'none';
    idKhachHangCanXoa = null;
  };
  document.getElementById('btn-xac-nhan-xoa-khachhang').onclick = function() {
    if (!idKhachHangCanXoa) return;
    document.getElementById('modal-xac-nhan-xoa-khachhang').style.display = 'none';
    fetch(`quanlikhachhang.php?delete=${encodeURIComponent(idKhachHangCanXoa)}&ajax=1`)
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          showToast('Xóa khách hàng thành công!', true);
          setTimeout(() => { location.reload(); }, 1200);
        } else {
          if (data.message && (data.message.includes('đơn hàng liên quan') || data.message.includes('giỏ hàng') || data.message.includes('liên hệ'))) {
            showModalXoaLienQuan(data.message);
          } else {
            showToast(data.message || 'Xóa thất bại!', false);
          }
        }
      })
      .catch(() => {
        showToast('Có lỗi xảy ra khi xóa!', false);
      });
  };
  document.getElementById('btn-huy-xoa-lien-quan-khachhang').onclick = function() {
    document.getElementById('modal-xoa-lien-quan-khachhang').style.display = 'none';
    idKhachHangCanXoa = null;
  };
  document.getElementById('btn-xac-nhan-xoa-lien-quan-khachhang').onclick = function() {
    if (!idKhachHangCanXoa) return;
    document.getElementById('modal-xoa-lien-quan-khachhang').style.display = 'none';
    fetch(`quanlikhachhang.php?delete=${encodeURIComponent(idKhachHangCanXoa)}&ajax=1&force=1`)
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          showToast('Đã xóa tất cả dữ liệu liên quan và khách hàng!', true);
          setTimeout(() => { location.reload(); }, 1200);
        } else {
          showToast(data.message || 'Xóa thất bại!', false);
        }
      })
      .catch(() => {
        showToast('Có lỗi xảy ra khi xóa!', false);
      });
  };
});
</script>
</body>
</html>
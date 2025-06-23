<?php
session_start();
$vaiTro = isset($_SESSION['user']['VaiTro']) ? $_SESSION['user']['VaiTro'] : '';
require_once 'config.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user']) || $_SESSION['user']['VaiTro'] !== 'Khách hàng') {
    header('Location: index.php');
    exit();
}

// Lấy IdKhachHang và thông tin khách hàng
$idKhachHang = '';
$hoTen = '';
$soDienThoai = '';
$diaChi = '';
if (isset($_SESSION['user']['IdTaiKhoan'])) {
    $tk = $_SESSION['user']['IdTaiKhoan'];
    $sql = "SELECT IdKhachHang, TenKhachHang, SDT, DiaChi FROM khachhang WHERE IdTaiKhoan = '$tk'";
    $result = $conn->query($sql);
    if ($result && $row = $result->fetch_assoc()) {
        $idKhachHang = $row['IdKhachHang'];
        $hoTen = $row['TenKhachHang'];
        $soDienThoai = $row['SDT'];
        $diaChi = $row['DiaChi'];
    }
}

// Lấy tất cả đơn hàng của khách hàng
$sql = "SELECT * FROM donhang WHERE IdKhachHang = '$idKhachHang' ORDER BY NgayDatHang DESC, IdDonHang DESC";
$result = $conn->query($sql);
$orders = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
}

function formatPrice($price) {
    return number_format($price, 0, ',', '.') . '₫';
}
function convertToSlug($text) {
    $search = array('à','á','ạ','ả','ã','â','ầ','ấ','ậ','ẩ','ẫ','ă','ằ','ắ','ặ','ẳ','ẵ',
        'è','é','ẹ','ẻ','ẽ','ê','ề','ế','ệ','ể','ễ',
        'ì','í','ị','ỉ','ĩ',
        'ò','ó','ọ','ỏ','õ','ô','ồ','ố','ộ','ổ','ỗ','ơ','ờ','ớ','ợ','ở','ỡ',
        'ù','ú','ụ','ủ','ũ','ư','ừ','ứ','ự','ử','ữ',
        'ỳ','ý','ỵ','ỷ','ỹ',
        'đ',
        ' ');
    $replace = array('a','a','a','a','a','a','a','a','a','a','a','a','a','a','a','a','a',
        'e','e','e','e','e','e','e','e','e','e','e',
        'i','i','i','i','i',
        'o','o','o','o','o','o','o','o','o','o','o','o','o','o','o','o','o',
        'u','u','u','u','u','u','u','u','u','u','u',
        'y','y','y','y','y',
        'd',
        '');
    $text = mb_strtolower($text, 'UTF-8');
    $text = str_replace($search, $replace, $text);
    $text = preg_replace('/[^a-z0-9]/', '', $text);
    return $text;
}
// Function to get image URL based on product name
function getImageUrl($idSanPham) {
    $filename = 'img/' . strtolower($idSanPham) . '.jpg';
    if (file_exists($filename)) {
        return $filename;
    } else {
        return 'img/default.jpg'; // Ảnh mặc định nếu chưa có
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đơn hàng của bạn - Sàn giao dịch nông sản</title>
    <link rel="stylesheet" href="css/giaodienkhachhang.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .order-container { max-width: 1100px; margin: 40px auto; background: #fff; border-radius: 10px; box-shadow: 0 4px 16px rgba(0,0,0,0.08); padding: 30px; }
        .order-header { text-align: center; margin-bottom: 30px; }
        .order-header h2 { color: #2e7d32; margin-bottom: 10px; }
        .customer-info { margin-bottom: 25px; }
        .customer-info label { font-weight: bold; color: #2e7d32; }
        .orders-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .orders-table th, .orders-table td { border: 1px solid #eee; padding: 12px; text-align: center; }
        .orders-table th { background: #f4f4f4; color: #2e7d32; }
        .order-status { font-weight: bold; }
        .btn-detail, .btn-pay { padding: 6px 16px; border: none; border-radius: 5px; font-weight: 500; cursor: pointer; }
        .btn-detail { background: #2e7d32; color: #fff; margin-right: 8px; }
        .btn-detail:hover { background: #e65100; }
        .btn-pay { background: #e65100; color: #fff; }
        .btn-pay:hover { background: #2e7d32; }
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.4); }
        .modal-content { background: #fff; margin: 5% auto; padding: 30px; border-radius: 10px; max-width: 600px; position: relative; }
        .close-modal { position: absolute; top: 10px; right: 20px; font-size: 24px; color: #888; cursor: pointer; }
        .modal-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .modal-table th, .modal-table td { border: 1px solid #eee; padding: 8px; text-align: center; }
        .modal-table th { background: #f4f4f4; color: #2e7d32; }
        @media (max-width: 600px) {
            .order-container { padding: 10px; }
            .orders-table th, .orders-table td, .modal-table th, .modal-table td { padding: 6px; font-size: 13px; }
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
    .modal-pay-method {
        display: none;
        position: fixed;
        z-index: 2000;
        left: 0; top: 0; width: 100%; height: 100%;
        background: rgba(0,0,0,0.4);
    }
    .modal-pay-method-content {
        background: #fff;
        margin: 7% auto;
        padding: 30px 30px 20px 30px;
        border-radius: 12px;
        max-width: 400px;
        box-shadow: 0 4px 16px rgba(0,0,0,0.12);
        position: relative;
        text-align: center;
    }
    .modal-pay-method-content h3 {
        color: #2e7d32;
        margin-bottom: 18px;
    }
    .pay-method-select {
        width: 100%;
        padding: 10px;
        border-radius: 6px;
        border: 1px solid #ccc;
        margin-bottom: 18px;
        font-size: 16px;
    }
    .pay-method-btn {
        padding: 8px 22px;
        border: none;
        border-radius: 6px;
        font-size: 16px;
        font-weight: 500;
        margin: 0 8px;
        cursor: pointer;
        background: #2e7d32;
        color: #fff;
        transition: background 0.2s;
    }
    .pay-method-btn.cancel {
        background: #e65100;
    }
    .pay-method-btn:hover {
        opacity: 0.9;
    }
    .close-pay-method {
        position: absolute;
        top: 10px;
        right: 18px;
        font-size: 22px;
        color: #888;
        cursor: pointer;
    }
    /* Toast Notification Styles */
    .toast {
        position: fixed;
        bottom: 30px;
        right: 30px;
        z-index: 2000;
        display: none;
        min-width: 220px;
        max-width: 350px;
        background: #2e7d32;
        color: #fff;
        padding: 16px 24px;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.15);
        font-size: 1rem;
        align-items: center;
        justify-content: flex-start;
        height: auto;
        min-height: unset;
        max-height: unset;
        transition: all 0.3s ease;
        flex-direction: row;
    }
    .toast.show {
        display: flex;
        animation: slideIn 0.3s ease;
    }
    .toast.error {
        background: #e65100;
    }
    .toast-icon {
        margin-right: 12px;
        font-size: 1.2rem;
    }
    @keyframes slideIn {
        from { transform: translateY(100%); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }
    .order-search {
        margin: 20px auto;
        max-width: 400px;
        position: relative;
    }
    
    .order-search input {
        width: 100%;
        padding: 12px 20px;
        border: 2px solid #2e7d32;
        border-radius: 25px;
        font-size: 16px;
        outline: none;
        transition: all 0.3s ease;
    }
    
    .order-search input:focus {
        border-color: #e65100;
        box-shadow: 0 0 8px rgba(230, 81, 0, 0.2);
    }

    .order-search input::placeholder {
        color: #999;
    }

    tr.hidden {
        display: none;
    }
    .btn-back {
        display: inline-block;
        padding: 12px 30px;
        background: #2e7d32;
        color: white;
        text-decoration: none;
        border-radius: 25px;
        font-weight: 500;
        font-size: 16px;
        border: none;
        cursor: pointer;
        transition: all 0.3s ease;
        margin-top: 30px;
        box-shadow: 0 3px 6px rgba(0,0,0,0.1);
    }

    .btn-back:hover {
        background: #e65100;
        transform: translateY(-2px);
        box-shadow: 0 5px 12px rgba(0,0,0,0.15);
    }

    .action-buttons {
        text-align: center;
        margin: 30px 0;
        padding: 20px 0;
        border-top: 1px solid #eee;
    }
    .btn-cancel {
        padding: 6px 16px;
        border: none;
        border-radius: 5px;
        font-weight: 500;
        cursor: pointer;
        background: #dc3545;
        color: #fff;
        margin-left: 8px;
    }
    .btn-cancel:hover {
        background: #c82333;
    }

    /* Modal xác nhận hủy đơn */
    .modal-cancel {
        display: none;
        position: fixed;
        z-index: 2000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.4);
    }
    .modal-cancel-content {
        background: #fff;
        margin: 15% auto;
        padding: 20px;
        border-radius: 8px;
        width: 400px;
        text-align: center;
        position: relative;
    }
    .modal-cancel h3 {
        color: #dc3545;
        margin-bottom: 20px;
    }
    .modal-cancel-buttons {
        margin-top: 20px;
    }
    .modal-cancel-buttons button {
        padding: 8px 20px;
        margin: 0 10px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-weight: 500;
    }
    .btn-confirm-cancel {
        background: #dc3545;
        color: #fff;
    }
    .btn-confirm-cancel:hover {
        background: #c82333;
    }
    .btn-cancel-no {
        background: #6c757d;
        color: #fff;
    }
    .btn-cancel-no:hover {
        background: #5a6268;
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
            
            <div class="search-box">
                <input type="text" id="searchOrder" placeholder="Nhập mã đơn hàng để tìm kiếm...">
                <button onclick="searchOrders()">Tìm</button>
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

    <main>
        <div class="order-container">
            <div class="order-header">
                <h2>Lịch sử đơn hàng</h2>
            </div>
            <div class="customer-info">
                <label>Khách hàng:</label> <?= htmlspecialchars($hoTen) ?><br>
                <label>SĐT:</label> <?= htmlspecialchars($soDienThoai) ?><br>
                <label>Địa chỉ:</label> <?= htmlspecialchars($diaChi) ?><br>
            </div>
            <?php if (!empty($orders)): ?>
            <table class="orders-table">
                <thead>
                    <tr>
                        <th>Mã đơn</th>
                        <th>Ngày đặt</th>
                        <th>Tổng tiền</th>
                        <th>Trạng thái</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($orders as $order): ?>
                    <tr>
                        <td><?= htmlspecialchars($order['IdDonHang']) ?></td>
                        <td><?= htmlspecialchars($order['NgayDatHang']) ?></td>
                        <td><?= formatPrice($order['TongGiaTri']) ?></td>
                        <td class="order-status">
                            <?php
                            $status = $order['TrangThai'];
                            if ($status == 'Chờ xác nhận') echo '<span style="color:#e65100">Chờ xác nhận</span>';
                            elseif ($status == 'Đã xác nhận') echo '<span style="color:#007bff">Đã xác nhận</span>';
                            elseif ($status == 'Chờ giao hàng') echo '<span style="color:#2e7d32">Chờ giao hàng</span>';
                            else echo htmlspecialchars($status);
                            ?>
                        </td>
                        <td>
                            <button class="btn-detail" onclick="showOrderDetail('<?= $order['IdDonHang'] ?>')">Chi tiết</button>
                            <?php if ($order['TrangThai'] == 'Đã xác nhận'): ?>
                                <button class="btn-pay" onclick="payOrder('<?= $order['IdDonHang'] ?>')">Thanh toán</button>
                            <?php endif; ?>
                            <?php if ($order['TrangThai'] == 'Chờ xác nhận'): ?>
                                <button class="btn-cancel" onclick="cancelOrder('<?= $order['IdDonHang'] ?>')">Hủy đơn</button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
                <div class="order-status" style="color:red;">Bạn chưa có đơn hàng nào!</div>
            <?php endif; ?>
            <div class="action-buttons">
                <a href="trangchukhachhang.php" class="btn-back">
                    <i class="fas fa-shopping-cart"></i> Tiếp tục mua sắm
                </a>
            </div>
        </div>

        <!-- Modal chi tiết đơn hàng -->
        <div id="order-detail-modal" class="modal">
            <div class="modal-content">
                <span class="close-modal" onclick="closeOrderDetail()">&times;</span>
                <h3>Chi tiết đơn hàng</h3>
                <div id="order-detail-content">Đang tải...</div>
            </div>
        </div>

        <!-- Modal chọn phương thức thanh toán -->
        <div id="modal-pay-method" class="modal-pay-method">
          <div class="modal-pay-method-content">
            <span class="close-pay-method" onclick="closePayMethodModal()">&times;</span>
            <h3>Xác nhận phương thức thanh toán</h3>
            <select id="phuongThucTT" class="pay-method-select">
              <option value="Thanh toán khi nhận hàng">Thanh toán khi nhận hàng</option>
              <option value="Chuyển khoản">Chuyển khoản</option>
            </select>
            <div>
              <button class="pay-method-btn" onclick="submitPayment()">Xác nhận</button>
              <button class="pay-method-btn cancel" onclick="closePayMethodModal()">Hủy</button>
            </div>
          </div>
        </div>

        <!-- Modal xác nhận hủy đơn -->
        <div id="modal-cancel-order" class="modal-cancel">
            <div class="modal-cancel-content">
                <h3>Xác nhận hủy đơn hàng</h3>
                <p>Bạn có chắc chắn muốn hủy đơn hàng này?</p>
                <div class="modal-cancel-buttons">
                    <button class="btn-confirm-cancel" onclick="confirmCancelOrder()">Xác nhận hủy</button>
                    <button class="btn-cancel-no" onclick="closeModalCancel()">Không</button>
                </div>
            </div>
        </div>

        <!-- Toast Notification -->
        <div class="toast" id="toast-notification">
            <span class="toast-icon" id="toast-icon">✓</span>
            <span id="toast-message"></span>
        </div>
    </main>
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
    <script src="js/trangchukhachhang.js"></script>
    <script>
    function showOrderDetail(idDonHang) {
        const modal = document.getElementById('order-detail-modal');
        const content = document.getElementById('order-detail-content');
        content.innerHTML = 'Đang tải...';
        modal.style.display = 'block';
        fetch('xemchitietdonhang.php?id=' + encodeURIComponent(idDonHang))
            .then(res => res.text())
            .then(html => { content.innerHTML = html; })
            .catch(() => { content.innerHTML = 'Lỗi khi tải chi tiết đơn hàng.'; });
    }
    function closeOrderDetail() {
        document.getElementById('order-detail-modal').style.display = 'none';
    }
    window.onclick = function(event) {
        const modal = document.getElementById('order-detail-modal');
        if (event.target == modal) closeOrderDetail();
        const payModal = document.getElementById('modal-pay-method');
        if (event.target == payModal) closePayMethodModal();
        const modalCancel = document.getElementById('modal-cancel-order');
        if (event.target == modalCancel) {
            closeModalCancel();
        }
    }
    let currentPayOrderId = null;
    function payOrder(idDonHang) {
        currentPayOrderId = idDonHang;
        document.getElementById('modal-pay-method').style.display = 'block';
    }
    function closePayMethodModal() {
        document.getElementById('modal-pay-method').style.display = 'none';
        currentPayOrderId = null;
    }
    function showToast(message, isError = false) {
        const toast = document.getElementById('toast-notification');
        const toastMsg = document.getElementById('toast-message');
        const toastIcon = document.getElementById('toast-icon');
        toastMsg.textContent = message;
        toastIcon.textContent = isError ? '✖' : '✓';
        toast.className = 'toast show' + (isError ? ' error' : '');
        setTimeout(() => {
            toast.className = 'toast';
        }, 2500);
    }
    function submitPayment() {
        const phuongThuc = document.getElementById('phuongThucTT').value;
        if (!currentPayOrderId) return;
        fetch('thanhtoandonhang.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ idDonHang: currentPayOrderId, phuongThuc })
        })
        .then(res => res.json())
        .then(data => {
            closePayMethodModal();
            if (data.success) {
                showToast('Thanh toán thành công!');
                setTimeout(() => location.reload(), 1800);
            } else {
                showToast(data.message || 'Có lỗi khi thanh toán!', true);
            }
        })
        .catch(() => {
            closePayMethodModal();
            showToast('Có lỗi khi thanh toán!', true);
        });
    }
    function searchOrders() {
        const searchText = document.getElementById('searchOrder').value.toLowerCase().trim();
        if (!searchText) {
            // Nếu ô tìm kiếm trống, hiển thị tất cả đơn hàng
            const orderRows = document.querySelectorAll('.orders-table tbody tr');
            orderRows.forEach(row => {
                row.classList.remove('hidden');
            });
            return;
        }

        const orderRows = document.querySelectorAll('.orders-table tbody tr');
        let found = false;
        
        orderRows.forEach(row => {
            const orderId = row.querySelector('td:first-child').textContent.toLowerCase();
            if (orderId.includes(searchText)) {
                row.classList.remove('hidden');
                found = true;
            } else {
                row.classList.add('hidden');
            }
        });

        // Hiển thị thông báo nếu không tìm thấy kết quả
        const toast = document.getElementById('toast-notification');
        const toastMsg = document.getElementById('toast-message');
        const toastIcon = document.getElementById('toast-icon');
        
        if (!found) {
            toastMsg.textContent = 'Không tìm thấy đơn hàng phù hợp!';
            toastIcon.textContent = '✖';
            toast.className = 'toast show error';
            setTimeout(() => {
                toast.className = 'toast';
            }, 2500);
        }
    }

    // Thêm chức năng tìm kiếm khi nhấn Enter
    document.getElementById('searchOrder').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            searchOrders();
        }
    });
    </script>
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
    <script>
    let currentCancelOrderId = null;

    function cancelOrder(orderId) {
        currentCancelOrderId = orderId;
        document.getElementById('modal-cancel-order').style.display = 'block';
    }

    function closeModalCancel() {
        document.getElementById('modal-cancel-order').style.display = 'none';
        currentCancelOrderId = null;
    }

    function confirmCancelOrder() {
        if (!currentCancelOrderId) return;
        
        fetch('huydonhang.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                idDonHang: currentCancelOrderId
            })
        })
        .then(response => response.json())
        .then(data => {
            closeModalCancel();
            if (data.success) {
                showToast('Đã hủy đơn hàng thành công!');
                setTimeout(() => location.reload(), 1500);
            } else {
                showToast(data.message || 'Không thể hủy đơn hàng!', true);
            }
        })
        .catch(error => {
            closeModalCancel();
            showToast('Có lỗi xảy ra khi hủy đơn hàng!', true);
        });
    }
    </script>
</body>
</html>
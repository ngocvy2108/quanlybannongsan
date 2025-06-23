<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['VaiTro'] !== 'Bán hàng') {
    header('Location: index.php');
    exit();
}

// Lấy IdNguoiBan từ session
$idTaiKhoan = $_SESSION['user']['IdTaiKhoan'];
$sqlNB = "SELECT IdNguoiBan FROM nguoiban WHERE IdTaiKhoan = '$idTaiKhoan'";
$resNB = $conn->query($sqlNB);
if (!$resNB || !$resNB->num_rows) {
    die('Không tìm thấy thông tin người bán!');
}
$idNguoiBan = $resNB->fetch_assoc()['IdNguoiBan'];

// Lấy tất cả đơn hàng của người bán này
$sql = "SELECT d.*, k.TenKhachHang, k.SDT, k.DiaChi FROM donhang d JOIN khachhang k ON d.IdKhachHang = k.IdKhachHang WHERE d.IdNguoiBan = '$idNguoiBan' ORDER BY d.NgayDatHang DESC, d.IdDonHang DESC";
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
$vaiTro = $_SESSION['user']['VaiTro'] ?? $_SESSION['user']['LoaiTaiKhoan'] ?? '';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý đơn hàng - Người bán</title>
    <link rel="stylesheet" href="css/giaodienkhachhang.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .order-container { max-width: 1100px; margin: 40px auto; background: #fff; border-radius: 10px; box-shadow: 0 4px 16px rgba(0,0,0,0.08); padding: 30px; }
        .order-header { text-align: center; margin-bottom: 30px; }
        .order-header h2 { color: #2e7d32; margin-bottom: 10px; }
        .orders-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .orders-table th, .orders-table td { border: 1px solid #eee; padding: 12px; text-align: center; }
        .orders-table th { background: #f4f4f4; color: #2e7d32; }
        .order-status { font-weight: bold; }
        .btn-detail, .btn-xacnhan, .btn-cancel, .btn-giaohang {
            padding: 8px 16px;
            border: none;
            border-radius: 5px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            min-width: 90px;
            margin: 4px;
            display: inline-block;
            text-align: center;
        }
        .btn-detail {
            background: #2e7d32;
            color: #fff;
        }
        .btn-detail:hover {
            background: #e65100;
        }
        .btn-xacnhan {
            background: #e65100;
            color: #fff;
        }
        .btn-xacnhan:hover {
            background: #2e7d32;
        }
        .btn-cancel {
            background: #d32f2f;
            color: #fff;
        }
        .btn-cancel:hover {
            background: #b71c1c;
        }
        .btn-giaohang {
            background: #1976D2;
            color: #fff;
        }
        .btn-giaohang:hover {
            background: #1565C0;
        }
        .action-buttons {
            display: flex;
            gap: 8px;
            justify-content: center;
            flex-wrap: wrap;
        }
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

/* Add new styles for modal and toast */
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
.nut-xacnhan { background: #2e7d32; color: #fff; }
.nut-xacnhan:hover { background: #1b5e20; }
.nut-huy { background: #e65100; color: #fff; }
.nut-huy:hover { background: #bf360c; }

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


    
    <!-- Banner -->
    <div class="banner-container">
        <img src="img/banner.jpg" alt="Banner" class="banner-image">
    </div>
    
<main>
    <div class="order-container">
        <div class="order-header">
            <h2>Quản lý đơn hàng của cửa hàng</h2>
        </div>
        <?php if (!empty($orders)): ?>
        <table class="orders-table">
            <thead>
                <tr>
                    <th>Mã đơn</th>
                    <th>Khách hàng</th>
                    <th>SĐT</th>
                    <th>Địa chỉ</th>
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
                    <td><?= htmlspecialchars($order['TenKhachHang']) ?></td>
                    <td><?= htmlspecialchars($order['SDT']) ?></td>
                    <td><?= htmlspecialchars($order['DiaChi']) ?></td>
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
                        <div class="action-buttons">
                            <button class="btn-detail" onclick="showOrderDetail('<?= $order['IdDonHang'] ?>')">Chi tiết</button>
                            <?php if ($order['TrangThai'] == 'Chờ xác nhận'): ?>
                                <button class="btn-xacnhan" onclick="xacNhanDon('<?= $order['IdDonHang'] ?>')">Xác nhận</button>
                                <button class="btn-cancel" onclick="huyDon('<?= $order['IdDonHang'] ?>')">Hủy đơn</button>
                            <?php elseif ($order['TrangThai'] == 'Đã xác nhận'): ?>
                                <button class="btn-cancel" onclick="huyDon('<?= $order['IdDonHang'] ?>')">Hủy đơn</button>
                            <?php elseif ($order['TrangThai'] == 'Chờ giao hàng'): ?>
                                <button class="btn-giaohang" onclick="giaoHang('<?= $order['IdDonHang'] ?>')">Giao hàng</button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
            <div class="order-status" style="color:red;">Chưa có đơn hàng nào!</div>
        <?php endif; ?>
    </div>
    <!-- Modal chi tiết đơn hàng -->
    <div id="order-detail-modal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeOrderDetail()">&times;</span>
            <h3>Chi tiết đơn hàng</h3>
            <div id="order-detail-content">Đang tải...</div>
        </div>
    </div>
    <!-- Modal xác nhận đơn hàng -->
    <div id="modal-xacnhan" class="modal-xac-nhan">
        <div class="modal-xac-nhan-content">
            <h3>Xác nhận đơn hàng</h3>
            <p>Bạn có chắc chắn muốn xác nhận đơn hàng này?</p>
            <div class="modal-xac-nhan-actions">
                <button id="btn-xacnhan-ok" class="nut nut-xacnhan">Xác nhận</button>
                <button onclick="closeXacNhanModal()" class="nut nut-huy">Hủy</button>
            </div>
        </div>
    </div>
    <!-- Modal xác nhận hủy đơn -->
    <div id="modal-huydon" class="modal-xac-nhan">
        <div class="modal-xac-nhan-content">
            <h3>Xác nhận hủy đơn hàng</h3>
            <p>Bạn có chắc chắn muốn hủy đơn hàng này?<br>
            <small style="color: #666;">Lưu ý: Đơn hàng sẽ không thể khôi phục sau khi hủy</small></p>
            <div class="modal-xac-nhan-actions">
                <button id="btn-huydon-ok" class="nut nut-xacnhan">Xác nhận</button>
                <button onclick="closeHuyDonModal()" class="nut nut-huy">Hủy</button>
            </div>
        </div>
    </div>
    <!-- Modal xác nhận giao hàng -->
    <div id="modal-giaohang" class="modal-xac-nhan">
        <div class="modal-xac-nhan-content">
            <h3>Xác nhận giao hàng</h3>
            <p>Bạn có chắc chắn muốn chuyển đơn hàng sang trạng thái giao hàng?</p>
            <div class="modal-xac-nhan-actions">
                <button id="btn-giaohang-ok" class="nut nut-xacnhan">Xác nhận</button>
                <button onclick="closeGiaoHangModal()" class="nut nut-huy">Hủy</button>
            </div>
        </div>
    </div>
</main>
<!-- Toast Notification -->
<div class="toast" id="toast-notification">
    <span class="toast-icon" id="toast-icon">✓</span>
    <span id="toast-message"></span>
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
let donHangDangXacNhan = null;
let donHangDangHuy = null;
let donHangDangGiao = null;

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

function xacNhanDon(idDonHang) {
    donHangDangXacNhan = idDonHang;
    document.getElementById('modal-xacnhan').style.display = 'flex';
}

function closeXacNhanModal() {
    document.getElementById('modal-xacnhan').style.display = 'none';
    donHangDangXacNhan = null;
}

function huyDon(idDonHang) {
    donHangDangHuy = idDonHang;
    document.getElementById('modal-huydon').style.display = 'flex';
}

function closeHuyDonModal() {
    document.getElementById('modal-huydon').style.display = 'none';
    donHangDangHuy = null;
}

function giaoHang(idDonHang) {
    donHangDangGiao = idDonHang;
    document.getElementById('modal-giaohang').style.display = 'flex';
}

function closeGiaoHangModal() {
    document.getElementById('modal-giaohang').style.display = 'none';
    donHangDangGiao = null;
}

document.getElementById('btn-xacnhan-ok').onclick = function() {
    if (!donHangDangXacNhan) return;
    
    fetch('xacnhandonban.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ idDonHang: donHangDangXacNhan })
    })
    .then(res => res.json())
    .then(data => {
        closeXacNhanModal();
        if (data.success) {
            showToast('Xác nhận đơn hàng thành công!');
            setTimeout(() => location.reload(), 1800);
        } else {
            showToast(data.message || 'Có lỗi khi xác nhận đơn hàng!', true);
        }
    })
    .catch(error => {
        closeXacNhanModal();
        showToast('Có lỗi khi xác nhận đơn hàng: ' + error, true);
    });
}

document.getElementById('btn-huydon-ok').onclick = function() {
    if (!donHangDangHuy) return;
    
    fetch('huydonban.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ idDonHang: donHangDangHuy })
    })
    .then(res => res.json())
    .then(data => {
        closeHuyDonModal();
        if (data.success) {
            showToast('Hủy đơn hàng thành công!');
            setTimeout(() => location.reload(), 1800);
        } else {
            showToast(data.message || 'Có lỗi khi hủy đơn hàng!', true);
        }
    })
    .catch(error => {
        closeHuyDonModal();
        showToast('Có lỗi khi hủy đơn hàng: ' + error, true);
    });
}

document.getElementById('btn-giaohang-ok').onclick = function() {
    if (!donHangDangGiao) return;
    
    fetch('capnhatgiaohang.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ idDonHang: donHangDangGiao })
    })
    .then(res => res.json())
    .then(data => {
        closeGiaoHangModal();
        if (data.success) {
            showToast('Đã chuyển sang trạng thái giao hàng!');
            setTimeout(() => location.reload(), 1800);
        } else {
            showToast(data.message || 'Có lỗi khi cập nhật trạng thái giao hàng!', true);
        }
    })
    .catch(error => {
        closeGiaoHangModal();
        showToast('Có lỗi khi cập nhật trạng thái giao hàng: ' + error, true);
    });
}

// Update window.onclick to handle all modals
window.onclick = function(event) {
    const modal = document.getElementById('order-detail-modal');
    const modalXacNhan = document.getElementById('modal-xacnhan');
    const modalHuyDon = document.getElementById('modal-huydon');
    const modalGiaoHang = document.getElementById('modal-giaohang');
    if (event.target == modal) closeOrderDetail();
    if (event.target == modalXacNhan) closeXacNhanModal();
    if (event.target == modalHuyDon) closeHuyDonModal();
    if (event.target == modalGiaoHang) closeGiaoHangModal();
}
</script>
<script>
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
    if (!found) {
        showToast('Không tìm thấy đơn hàng phù hợp!', true);
    }
}

// Thêm chức năng tìm kiếm khi nhấn Enter
document.getElementById('searchOrder').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        searchOrders();
    }
});
</script>
<style>
    tr.hidden {
        display: none;
    }
</style>
</body>
</html> 
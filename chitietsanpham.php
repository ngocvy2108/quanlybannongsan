<?php
include("config.php");
session_start();
$vaiTro = isset($_SESSION['user']['VaiTro']) ? $_SESSION['user']['VaiTro'] : '';
// ĐẶT KHỐI XÓA Ở ĐÂY, TRƯỚC MỌI KIỂM TRA id
if (isset($_GET['delete'])) {
    $IdSanPham = $_GET['delete'];
    if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
        $force = isset($_GET['force']) && $_GET['force'] == 1;
        // Kiểm tra ràng buộc với bảng chitietdonhang
        $checkChiTietDonHang = $conn->query("SELECT COUNT(*) as total FROM chitietdonhang WHERE IdSanPham='$IdSanPham'");
        $rowChiTietDonHang = $checkChiTietDonHang->fetch_assoc();
        // Kiểm tra ràng buộc với bảng giohang
        $checkGioHang = $conn->query("SELECT COUNT(*) as total FROM giohang WHERE IdSanPham='$IdSanPham'");
        $rowGioHang = $checkGioHang->fetch_assoc();

        if (!$force && ($rowChiTietDonHang['total'] > 0 || $rowGioHang['total'] > 0)) {
            $success = false;
            $message = "Không thể xóa sản phẩm vì còn dữ liệu liên quan!";
        } else {
            if ($force) {
                if ($rowChiTietDonHang['total'] > 0) {
                    $conn->query("DELETE FROM chitietdonhang WHERE IdSanPham='$IdSanPham'");
                }
                if ($rowGioHang['total'] > 0) {
                    $conn->query("DELETE FROM giohang WHERE IdSanPham='$IdSanPham'");
                }
            }
            $success = $conn->query("DELETE FROM sanpham WHERE IdSanPham='$IdSanPham'");
            $message = $success ? 'Xóa thành công' : 'Lỗi: ' . $conn->error;
        }
        header('Content-Type: application/json');
        echo json_encode(['success' => $success, 'message' => $message]);
        exit();
    } else {
        $conn->query("DELETE FROM sanpham WHERE IdSanPham='$IdSanPham'");
    }
}
// SAU ĐÓ MỚI KIỂM TRA id ĐỂ HIỂN THỊ CHI TIẾT
if (!isset($_GET['id'])) {
    header("Location: quanlysanpham.php");
    exit();
}

$id = $_GET['id'];
$sql = "SELECT * FROM sanpham WHERE IdSanPham='$id'";
$result = $conn->query($sql);
if ($result->num_rows == 0) {
    echo "Không tìm thấy sản phẩm!";
    exit();
}
$row = $result->fetch_assoc();
// Kiểm tra sản phẩm đã từng bán chưa
$resultCheck = $conn->query("SELECT COUNT(*) as total FROM chitietdonhang WHERE IdSanPham='$id'");
$rowCheck = $resultCheck->fetch_assoc();
$daTungBan = $rowCheck['total'] > 0;
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="css/giaodienkhachhang.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <title>Chi tiết sản phẩm</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #f4f4f9; }
        
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
.trang-chi-tiet-san-pham .chi-tiet-san-pham {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    align-items: stretch;
    background: #fff;
    border-radius: 24px;
    box-shadow: 0 8px 32px rgba(46,125,50,0.13);
    max-width: 800px;
    margin: 48px auto 48px auto;
    padding: 0;
    overflow: hidden;
}
.trang-chi-tiet-san-pham .anh-san-pham {
    flex: 1 1 320px;
    background: #f6faf7;
    display: flex;
    align-items: center;
    justify-content: center;
    min-width: 320px;
    min-height: 320px;
}
.trang-chi-tiet-san-pham .anh-san-pham img {
    max-width: 90%;
    max-height: 320px;
    border-radius: 18px;
    box-shadow: 0 2px 16px rgba(67,160,71,0.10);
    object-fit: cover;
}
.trang-chi-tiet-san-pham .thong-tin-san-pham {
    flex: 1 1 340px;
    padding: 40px 36px 36px 36px;
    display: flex;
    flex-direction: column;
    justify-content: center;
}
.trang-chi-tiet-san-pham .thong-tin-san-pham h2 {
    color: #257a2a;
    font-size: 2.2rem;
    margin-bottom: 18px;
    font-weight: 700;
    letter-spacing: 1px;
}
.trang-chi-tiet-san-pham .thong-tin-san-pham ul {
    list-style: none;
    padding: 0;
    margin: 0 0 24px 0;
}
.trang-chi-tiet-san-pham .thong-tin-san-pham ul li {
    font-size: 1.13rem;
    margin-bottom: 10px;
    display: flex;
    align-items: baseline;
}
.trang-chi-tiet-san-pham .thong-tin-san-pham ul li span {
    min-width: 90px;
    color: #666;
    font-weight: 500;
    display: inline-block;
}
.trang-chi-tiet-san-pham .gia-san-pham {
    color: #e53935;
    font-size: 1.25rem;
    font-weight: bold;
    margin-left: 8px;
}
.trang-chi-tiet-san-pham .nut-chuc-nang {
    margin-top: 18px;
}
.trang-chi-tiet-san-pham .nut {
    display: inline-block;
    padding: 12px 32px;
    border-radius: 10px;
    font-size: 1.08rem;
    font-weight: 600;
    text-decoration: none;
    margin-right: 18px;
    transition: background 0.2s, color 0.2s, box-shadow 0.2s;
    border: none;
    cursor: pointer;
    box-shadow: 0 2px 8px rgba(67,160,71,0.08);
}
.trang-chi-tiet-san-pham .nut-sua {
    background: linear-gradient(90deg, #43a047 60%, #257a2a 100%);
    color: #fff;
}
.trang-chi-tiet-san-pham .nut-sua:hover {
    background: linear-gradient(90deg, #257a2a 60%, #43a047 100%);
    box-shadow: 0 4px 16px rgba(67,160,71,0.13);
}
.trang-chi-tiet-san-pham .nut-xoa {
    background: #e53935;
    color: #fff;
}
.trang-chi-tiet-san-pham .nut-xoa:hover {
    background: #b71c1c;
    color: #fff;
}
@media (max-width: 900px) {
    .trang-chi-tiet-san-pham .chi-tiet-san-pham { flex-direction: column; }
    .trang-chi-tiet-san-pham .anh-san-pham, .trang-chi-tiet-san-pham .thong-tin-san-pham { min-width: 0; }
    .trang-chi-tiet-san-pham .thong-tin-san-pham { padding: 32px 18px 24px 18px; }
}
    </style>
    <style>
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
      color: #e53935;
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
<!-- Chi tiết sản phẩm đẹp, hiện đại, đồng bộ, dùng CSS riêng tiếng Việt không dấu -->
<div class="trang-chi-tiet-san-pham">
    <div class="chi-tiet-san-pham">
        <div class="anh-san-pham">
            <img src="img/<?php echo strtolower($row['IdSanPham']); ?>.jpg" alt="ảnh sản phẩm">
        </div>
        <div class="thong-tin-san-pham">
            <h2><?php echo $row['TenSanPham']; ?></h2>
            <ul>
                <li><span>ID:</span> <?php echo $row['IdSanPham']; ?></li>
                <li><span>Giá:</span> <b class="gia-san-pham"><?php echo number_format($row['Gia'], 0, '', '.') . "₫"; ?></b></li>
                <li><span>Loại:</span> <?php echo $row['Loai']; ?></li>
                <li><span>Tồn kho:</span> <?php echo $row['SoLuongTonKho']; ?></li>
                <li><span>Mô tả:</span> <?php echo $row['MoTa']; ?></li>
                <li><span>Nổi bật:</span> <?php echo (isset($row['noibat']) && $row['noibat'] == 1 ? 'Có' : 'Không'); ?></li>
            </ul>
            <div class="nut-chuc-nang">
                <a href="suasanpham.php?id=<?php echo $row['IdSanPham']; ?>" class="nut nut-sua">Sửa</a>
                <a href="#" class="nut nut-xoa" data-id="<?php echo $row['IdSanPham']; ?>" id="btn-xoa-sanpham">Xóa</a>
            </div>
        </div>
    </div>
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

<!-- Modal xác nhận xóa sản phẩm -->
<div id="modal-xac-nhan-xoa-sanpham" class="modal-xoa" style="display:none;z-index:99999;">
  <div class="modal-xoa-content">
    <h3><span class="modal-xoa-icon">&#9888;</span>Xác nhận xóa sản phẩm</h3>
    <p>Bạn có chắc chắn muốn xóa sản phẩm này không?<br><span style="color:#e53935;font-size:0.98rem;">Hành động này không thể hoàn tác.</span></p>
    <div class="modal-xoa-actions">
      <button id="btn-xac-nhan-xoa-sanpham" class="nut nut-xoa">Xóa</button>
      <button id="btn-huy-xoa-sanpham" class="nut nut-sua">Hủy</button>
    </div>
  </div>
</div>

<!-- Modal xác nhận xóa tất cả liên quan -->
<div id="modal-xoa-lien-quan" class="modal-xoa" style="display:none;z-index:99999;">
  <div class="modal-xoa-content">
    <h3><span class="modal-xoa-icon">&#9888;</span>Xóa tất cả dữ liệu liên quan?</h3>
    <p id="modal-xoa-lien-quan-msg">Sản phẩm này còn liên quan đến dữ liệu khác.<br>Bạn có muốn xóa hết tất cả dữ liệu liên quan và xóa sản phẩm không?</p>
    <div class="modal-xoa-actions">
      <button id="btn-xac-nhan-xoa-lien-quan" class="nut nut-xoa">Xóa tất cả</button>
      <button id="btn-huy-xoa-lien-quan" class="nut nut-sua">Hủy</button>
    </div>
  </div>
</div>

<!-- Toast notification -->
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
let idSanPham = "<?php echo $row['IdSanPham']; ?>";
let daTungBan = <?php echo $daTungBan ? 'true' : 'false'; ?>;

document.addEventListener('DOMContentLoaded', function() {
  let idSanPhamCanXoa = null;
  
  function showToast(msg, success = true) {
    const toast = document.getElementById('toast-notification');
    toast.textContent = msg;
    toast.style.background = success ? '#e8f5e9' : '#ffebee';
    toast.style.color = success ? '#257a2a' : '#e53935';
    toast.style.display = 'flex';
    setTimeout(() => { toast.style.display = 'none'; }, 2500);
  }

  function showModalXoaLienQuan(msg) {
    document.getElementById('modal-xoa-lien-quan-msg').innerHTML = msg + '<br><span style="color:#e53935;font-size:0.98rem;">Hành động này sẽ xóa hết dữ liệu liên quan và không thể hoàn tác.</span>';
    document.getElementById('modal-xoa-lien-quan').style.display = 'flex';
  }

  document.getElementById('btn-xoa-sanpham').onclick = function(e) {
    e.preventDefault();
    idSanPhamCanXoa = this.getAttribute('data-id');
    document.getElementById('modal-xac-nhan-xoa-sanpham').style.display = 'flex';
  };

  document.getElementById('btn-huy-xoa-sanpham').onclick = function() {
    document.getElementById('modal-xac-nhan-xoa-sanpham').style.display = 'none';
    idSanPhamCanXoa = null;
  };

  document.getElementById('btn-xac-nhan-xoa-sanpham').onclick = function() {
    if (!idSanPhamCanXoa) return;
    document.getElementById('modal-xac-nhan-xoa-sanpham').style.display = 'none';
    fetch(`chitietsanpham.php?delete=${encodeURIComponent(idSanPhamCanXoa)}&ajax=1`)
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          showToast('Xóa sản phẩm thành công!', true);
          setTimeout(() => { window.location.href = 'quanlysanpham.php'; }, 1200);
        } else {
          if (data.message && data.message.includes('dữ liệu liên quan')) {
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

  document.getElementById('btn-huy-xoa-lien-quan').onclick = function() {
    document.getElementById('modal-xoa-lien-quan').style.display = 'none';
    idSanPhamCanXoa = null;
  };

  document.getElementById('btn-xac-nhan-xoa-lien-quan').onclick = function() {
    if (!idSanPhamCanXoa) return;
    document.getElementById('modal-xoa-lien-quan').style.display = 'none';
    fetch(`chitietsanpham.php?delete=${encodeURIComponent(idSanPhamCanXoa)}&ajax=1&force=1`)
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          showToast('Đã xóa tất cả dữ liệu liên quan và sản phẩm!', true);
          setTimeout(() => { window.location.href = 'quanlysanpham.php'; }, 1200);
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

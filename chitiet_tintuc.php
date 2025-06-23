<?php
session_start();
$vaiTro = isset($_SESSION['user']['VaiTro']) ? $_SESSION['user']['VaiTro'] : '';
if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "csdldoanchuyennganh");
if ($conn->connect_error) {
    die("Lỗi kết nối: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

$idTinTuc = $_GET['id'] ?? '';
if (!$idTinTuc) {
    echo "Không có mã tin tức!";
    exit();
}

$stmt = $conn->prepare("SELECT * FROM tintuc WHERE IdTinTuc = ?");
$stmt->bind_param("s", $idTinTuc);
$stmt->execute();
$tintuc = $stmt->get_result()->fetch_assoc();
if (!$tintuc) {
    echo "Không tìm thấy tin tức!";
    exit();
}

$sqlBinhLuan = "
    SELECT b.NoiDung, b.NgayBinhLuan, t.TenDangNhap 
    FROM binhluan b 
    JOIN taikhoan t ON b.IdTaiKhoan = t.IdTaiKhoan 
    WHERE b.IdTinTuc = ? 
    ORDER BY b.NgayBinhLuan DESC
";
$stmtBL = $conn->prepare($sqlBinhLuan);
$stmtBL->bind_param("s", $idTinTuc);
$stmtBL->execute();
$binhluanResult = $stmtBL->get_result();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="css/giaodienkhachhang.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <title>Chi tiết bài đăng - <?= htmlspecialchars($tintuc['TieuDe']) ?></title>
    <style>
        
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
.chi-tiet-tin-tuc-container {
    max-width: 700px;
    margin: 40px auto;
    background: #fff;
    border-radius: 14px;
    box-shadow: 0 4px 24px rgba(0,0,0,0.08);
    padding: 32px 24px;
}
.tieu-de-tin-tuc {
    text-align: center;
    font-size: 2.2rem;
    font-weight: bold;
    margin-bottom: 18px;
    color: #2d7a2d;
}
.anh-tin-tuc {
    display: block;
    margin: 0 auto 18px auto;
    max-width: 100%;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.10);
}
.ngay-dang-tin-tuc {
    text-align: right;
    color: #888;
    font-size: 0.95rem;
    margin-bottom: 18px;
}
.noi-dung-tin-tuc {
    font-size: 1.15rem;
    line-height: 1.7;
    color: #333;
    margin-bottom: 32px;
}
.binh-luan-tin-tuc {
    margin-top: 32px;
}
.tieu-de-binh-luan {
    font-size: 1.3rem;
    color: #2d7a2d;
    margin-bottom: 18px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.the-binh-luan {
    background: #f7f7f7;
    border-radius: 8px;
    padding: 14px 18px;
    margin-bottom: 16px;
    box-shadow: 0 1px 4px rgba(0,0,0,0.04);
}
.header-binh-luan {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 6px;
}
.anh-dai-dien-binh-luan {
    width: 36px;
    height: 36px;
    background: #2d7a2d;
    color: #fff;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 1.1rem;
}
.nguoi-binh-luan {
    font-weight: 600;
    color: #2d7a2d;
}
.ngay-binh-luan {
    color: #888;
    font-size: 0.92rem;
    margin-left: 8px;
}
.noi-dung-binh-luan {
    font-size: 1.05rem;
    color: #222;
    margin-left: 48px;
}
.khong-co-binh-luan {
    color: #888;
    font-style: italic;
    text-align: center;
}
@media (max-width: 600px) {
    .chi-tiet-tin-tuc-container {
        padding: 12px 4px;
    }
    .tieu-de-tin-tuc {
        font-size: 1.3rem;
    }
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
    <!-- Banner -->
    <div class="banner-container">
        <img src="img/banner.jpg" alt="Banner" class="banner-image">
    </div>
<div class="container">
    <div class="chi-tiet-tin-tuc-container">
        <h2 class="tieu-de-tin-tuc"><?= htmlspecialchars($tintuc['TieuDe']) ?></h2>
        <?php if (!empty($tintuc['HinhAnh'])): ?>
            <img class="anh-tin-tuc" src="data:image/jpeg;base64,<?= base64_encode($tintuc['HinhAnh']) ?>" alt="Ảnh tin tức">
        <?php endif; ?>
        <p class="ngay-dang-tin-tuc"><i>Ngày đăng: <?= $tintuc['NgayDang'] ?></i></p>
        <div class="noi-dung-tin-tuc">
            <?= nl2br(htmlspecialchars($tintuc['NoiDung'])) ?>
        </div>
        <div class="binh-luan-tin-tuc">
            <h3 class="tieu-de-binh-luan">Bình luận</h3>
            <?php if ($binhluanResult->num_rows > 0): ?>
                <?php while ($bl = $binhluanResult->fetch_assoc()): ?>
                    <div class="the-binh-luan">
                        <div class="header-binh-luan">
                            <div class="anh-dai-dien-binh-luan"><?= strtoupper(substr($bl['TenDangNhap'], 0, 1)) ?></div>
                            <div>
                                <span class="nguoi-binh-luan"><?= htmlspecialchars($bl['TenDangNhap']) ?></span>
                                <span class="ngay-binh-luan"><?= $bl['NgayBinhLuan'] ?></span>
                            </div>
                        </div>
                        <div class="noi-dung-binh-luan"><?= nl2br(htmlspecialchars($bl['NoiDung'])) ?></div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p class="khong-co-binh-luan">Chưa có bình luận nào.</p>
            <?php endif; ?>
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
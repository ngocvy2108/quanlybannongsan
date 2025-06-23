<?php
session_start();
if (!isset($_SESSION['IdNguoiBan'])) {
    header("Location: dangnhap.php");
    exit();
}

include 'config.php'; // File kết nối CSDL

$idNguoiBan = $_SESSION['IdNguoiBan'];

// Lấy tất cả sản phẩm của người bán này
$sql = "SELECT * FROM sanpham WHERE IdNguoiBan = ? AND TrangThaiDuyet = 1";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $idNguoiBan);
$stmt->execute();
$result = $stmt->get_result();

$vaiTro = $_SESSION['user']['VaiTro'] ?? $_SESSION['user']['LoaiTaiKhoan'] ?? '';

function convertToSlug($str) {
    $slug = mb_strtolower($str, 'UTF-8');
    $slug = preg_replace('/[^\p{L}\p{N}]+/u', '-', $slug);
    $slug = trim($slug, '-');
    return $slug;
}

$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';

$search_results = [];
if (!empty($searchTerm)) {
    $searchTermEscaped = $conn->real_escape_string($searchTerm);
    $sql_search = "SELECT s.IdSanPham, s.TenSanPham, s.Gia, s.MoTa, s.SoLuongTonKho, n.TenNguoiBan, n.DiaChi
                   FROM sanpham s
                   JOIN nguoiban n ON s.IdNguoiBan = n.IdNguoiBan
                   WHERE s.TenSanPham LIKE ? AND s.IdNguoiBan = ? AND s.TrangThaiDuyet = 1";
    
    $stmt = $conn->prepare($sql_search);
    $searchPattern = "%$searchTermEscaped%";
    $stmt->bind_param("ss", $searchPattern, $idNguoiBan);
    $stmt->execute();
    $result_search = $stmt->get_result();
    
    if ($result_search && $result_search->num_rows > 0) {
        while ($row = $result_search->fetch_assoc()) {
            $search_results[] = $row;
        }
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trang Bán Hàng</title>
    <link rel="stylesheet" href="css/giaodienkhachhang.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
      body {
        padding-top: 160px; /* Add padding to prevent content from being hidden under fixed header */
      }

      .product-container {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 20px;
        margin-top: 20px;
      }

      .product-card {
        border: 1px solid #ddd;
        border-radius: 8px;
        overflow: hidden;
        text-align: center;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
      }

      .product-card img {
        width: 100%;
        height: 200px;
        object-fit: cover;
        display: block;
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

      /* Banner Styles */
      .banner-container {
          width: 100%;
          margin: 0 auto;
          position: relative;
          background: #f8f8f8;
      }

      .banner-slider {
          width: 100%;
          position: relative;
          background: #f8f8f8;
      }

      .slide-wrapper {
          width: 100%;
          height: 360px;
          position: relative;
      }

      .slide {
          position: absolute;
          width: 100%;
          height: 100%;
          opacity: 0;
          transition: all 0.8s ease;
          display: flex;
          flex-direction: row-reverse;
          justify-content: space-between;
          background: #f8f8f8;
      }

      .slide.active {
          opacity: 1;
      }

      .slide img {
          width: 60%;
          height: 100%;
          object-fit: contain;
      }

      /* Slide Content Styles */
      .slide-content {
          width: 40%;
          padding: 60px;
          display: flex;
          flex-direction: column;
          justify-content: center;
          color: #333;
          opacity: 0;
          transform: translateY(40px);
          transition: all 0.8s ease 0.3s;
      }

      .slide.active .slide-content {
          opacity: 1;
          transform: translateY(0);
      }

      .slide-title {
          font-size: 48px;
          font-weight: 700;
          margin-bottom: 20px;
          opacity: 0;
          transform: translateY(40px);
          transition: all 0.8s ease 0.5s;
      }

      .slide-description {
          font-size: 24px;
          margin-bottom: 30px;
          opacity: 0;
          transform: translateY(40px);
          transition: all 0.8s ease 0.7s;
      }

      .slide-button {
          display: inline-block;
          padding: 12px 30px;
          background: #e65c00;
          color: white;
          text-decoration: none;
          border-radius: 5px;
          font-weight: 500;
          width: fit-content;
          box-shadow: none;
          opacity: 0;
          transform: translateY(40px);
          transition: all 0.8s ease 0.9s;
      }

      .slide.active .slide-title,
      .slide.active .slide-description,
      .slide.active .slide-button {
          opacity: 1;
          transform: translateY(0);
      }

      .slide-button:hover {
          background: #ff6600;
          transform: scale(1.05);
          transition: all 0.3s ease;
      }

      /* Progress Bar */
      .slide-progress {
          position: absolute;
          bottom: 0;
          left: 0;
          width: 100%;
          height: 4px;
          background: rgba(0,0,0,0.1);
      }

      .progress-bar {
          width: 0;
          height: 100%;
          background: #e65c00;
          transition: width 3s linear;
      }

      /* Navigation Arrows */
      .slide-nav {
          position: absolute;
          top: 50%;
          transform: translateY(-50%);
          width: 50px;
          height: 50px;
          border: none;
          background: rgba(0,0,0,0.1);
          color: #333;
          cursor: pointer;
          border-radius: 50%;
          transition: all 0.3s ease;
      }

      .slide-nav:hover {
          background: rgba(0,0,0,0.2);
      }

      .slide-nav.prev {
          left: 20px;
      }

      .slide-nav.next {
          right: 20px;
      }

      /* Dots Navigation */
      .slide-dots {
          position: absolute;
          bottom: 20px;
          left: 50%;
          transform: translateX(-50%);
          display: flex;
          gap: 10px;
      }

      .dot {
          width: 12px;
          height: 12px;
          background: rgba(0,0,0,0.3);
          border-radius: 50%;
          cursor: pointer;
          transition: all 0.3s ease;
      }

      .dot:hover {
          background: rgba(0,0,0,0.5);
      }

      .dot.active {
          background: #e65c00;
          transform: scale(1.2);
      }

      /* Responsive Design */
      @media (max-width: 1366px) {
          .slide-wrapper {
              height: 340px;
          }
      }

      @media (max-width: 1024px) {
          .slide-wrapper {
              height: 300px;
          }
      }

      @media (max-width: 768px) {
          .slide-wrapper {
              height: 260px;
          }
      }

      @media (max-width: 480px) {
          .slide-wrapper {
              height: 180px;
          }
          
          .slide-title {
              font-size: 24px;
          }
          
          .slide-description {
              font-size: 16px;
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
            
            <div class="search-box">
                <form method="GET" action="banhangtrangchu.php">
                    <input type="text" name="search" placeholder="Tìm kiếm sản phẩm..." value="<?= htmlspecialchars($searchTerm ?? '') ?>">
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
        <div class="banner-slider">
            <div class="slide-wrapper">
                <div class="slide active">
                    <img src="img/banner1.jpg" alt="Đặc sản Tiền Giang">
                    <div class="slide-content">
                        <h2 class="slide-title">Đặc sản Tiền Giang</h2>
                        <p class="slide-description">Trái cây tươi ngon từ vườn đến bàn ăn</p>
                        <a href="#products" class="slide-button">Khám phá ngay</a>
                    </div>
                </div>
                <div class="slide">
                    <img src="img/banner2.jpg" alt="Rau sạch VietGAP">
                    <div class="slide-content">
                        <h2 class="slide-title">Rau sạch VietGAP</h2>
                        <p class="slide-description">An toàn và chất lượng cho mọi bữa ăn</p>
                        <a href="#vegetables" class="slide-button">Xem thêm</a>
                    </div>
                </div>
                <div class="slide">
                    <img src="img/banner3.jpg" alt="Lúa gạo Tiền Giang">
                    <div class="slide-content">
                        <h2 class="slide-title">Lúa gạo Tiền Giang</h2>
                        <p class="slide-description">Tinh hoa từ đồng ruộng Việt Nam</p>
                        <a href="#rice" class="slide-button">Tìm hiểu thêm</a>
                    </div>
                </div>
                <div class="slide">
                    <img src="img/banner4.jpg" alt="Thủy sản tươi sống">
                    <div class="slide-content">
                        <h2 class="slide-title">Thủy sản tươi sống</h2>
                        <p class="slide-description">Nguồn hải sản tươi ngon, chất lượng</p>
                        <a href="#seafood" class="slide-button">Đặt hàng ngay</a>
                    </div>
                </div>
                <div class="slide">
                    <img src="img/banner5.jpg" alt="Sản phẩm OCOP">
                    <div class="slide-content">
                        <h2 class="slide-title">Sản phẩm OCOP</h2>
                        <p class="slide-description">Tinh hoa đặc sản địa phương</p>
                        <a href="#ocop" class="slide-button">Khám phá</a>
                    </div>
                </div>
            </div>

            <!-- Progress Bar -->
            <div class="slide-progress">
                <div class="progress-bar"></div>
            </div>

            <!-- Navigation Arrows -->
            <button class="slide-nav prev">
                <i class="fas fa-chevron-left"></i>
            </button>
            <button class="slide-nav next">
                <i class="fas fa-chevron-right"></i>
            </button>

            <!-- Dots Navigation -->
            <div class="slide-dots">
                <span class="dot active" onclick="currentSlide(1)"></span>
                <span class="dot" onclick="currentSlide(2)"></span>
                <span class="dot" onclick="currentSlide(3)"></span>
                <span class="dot" onclick="currentSlide(4)"></span>
                <span class="dot" onclick="currentSlide(5)"></span>
            </div>
        </div>
    </div>

    <script>
    let slideIndex = 1;
    let slideInterval;

    function showSlides(n) {
        const slides = document.querySelectorAll('.slide');
        const dots = document.querySelectorAll('.dot');
        const progressBar = document.querySelector('.progress-bar');
        
        if (n > slides.length) slideIndex = 1;
        if (n < 1) slideIndex = slides.length;
        
        // Reset progress bar
        progressBar.style.width = '0%';
        
        // Hide all slides
        slides.forEach(slide => {
            slide.classList.remove('active');
        });
        dots.forEach(dot => {
            dot.classList.remove('active');
        });
        
        // Show current slide
        slides[slideIndex - 1].classList.add('active');
        dots[slideIndex - 1].classList.add('active');
        
        // Animate progress bar
        progressBar.style.width = '100%';
    }

    function currentSlide(n) {
        clearInterval(slideInterval);
        showSlides(slideIndex = n);
        startAutoSlide();
    }

    function nextSlide() {
        showSlides(slideIndex += 1);
    }

    function prevSlide() {
        showSlides(slideIndex -= 1);
    }

    function startAutoSlide() {
        // Reset any existing interval
        clearInterval(slideInterval);
        
        // Start new interval
        slideInterval = setInterval(() => {
            nextSlide();
        }, 5000); // Change slide every 5 seconds
    }

    // Initialize slider
    document.addEventListener('DOMContentLoaded', function() {
        showSlides(slideIndex);
        startAutoSlide();
        
        // Add click events to navigation arrows
        document.querySelector('.slide-nav.prev').addEventListener('click', () => {
            clearInterval(slideInterval);
            prevSlide();
            startAutoSlide();
        });
        
        document.querySelector('.slide-nav.next').addEventListener('click', () => {
            clearInterval(slideInterval);
            nextSlide();
            startAutoSlide();
        });
        
        // Pause auto-slide on hover
        document.querySelector('.banner-slider').addEventListener('mouseenter', () => {
            clearInterval(slideInterval);
        });
        
        // Resume auto-slide when mouse leaves
        document.querySelector('.banner-slider').addEventListener('mouseleave', () => {
            startAutoSlide();
        });
    });
    </script>
    
    <!-- Main Content -->
    <main class="main-content">
        <?php if (!empty($searchTerm)): ?>
            <h2 class="section-title">Kết quả tìm kiếm cho: "<?= htmlspecialchars($searchTerm) ?>"</h2>
            <?php if (!empty($search_results)): ?>
                <div class="product-container">
                    <?php foreach ($search_results as $product): ?>
                        <div class="product-card">
                            <img src="img/<?= strtolower($product['IdSanPham']) ?>.jpg" alt="<?= htmlspecialchars($product['TenSanPham']) ?>">
                            <h3><?= htmlspecialchars($product['TenSanPham']) ?></h3>
                            <p>Giá: <?= number_format($product['Gia'], 0, ',', '.') ?> VND</p>
                            <a href="chitietsanpham.php?id=<?= $product['IdSanPham'] ?>">Chi tiết</a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p style="text-align: center; color: #666;">Không tìm thấy sản phẩm phù hợp.</p>
            <?php endif; ?>
        <?php endif; ?>

        <h2 class="section-title">SẢN PHẨM CỦA BẠN</h2>
        
        <div class="product-container">
            <!-- Ô Thêm Sản Phẩm -->
    <div class="product-card">
        <a href="them_sanpham.php" style="display: block; text-decoration: none; color: #333;">
            <div style="font-size: 80px; color: #aaa; padding: 50px 0; text-align: center;">+</div>
            <h3 style="text-align: center;">Thêm sản phẩm mới</h3>
        </a>
    </div>
    <?php while ($row = $result->fetch_assoc()): ?>
        <div class="product-card">
            <img src="img/<?= strtolower($row['IdSanPham']) ?>.jpg" alt="<?= htmlspecialchars($row['TenSanPham']) ?>" width="200">
            <h3><?= htmlspecialchars($row['TenSanPham']) ?></h3>
            <p>Giá: <?= number_format($row['Gia'], 0, ',', '.') ?> VND</p>
            <a href="chitietsanpham.php?id=<?= $row['IdSanPham'] ?>">Chi tiết</a>
        </div>
    <?php endwhile; ?>

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

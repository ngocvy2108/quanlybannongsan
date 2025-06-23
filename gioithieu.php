<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit();
}
$vaiTro = $_SESSION['user']['VaiTro'];

if ($vaiTro === 'Khách hàng' || $vaiTro === 'Bán hàng') {
    $isKhachHang = true;  // Chỉ được xem
} elseif ($vaiTro === 'Quản lý') {
    $isKhachHang = false; // Có quyền quản lý
} else {
    header("Location: index.php");
    exit();
}
// Database connection
$servername = "localhost";
$username = "root";  // Change if needed
$password = "";      // Change if needed
$dbname = "csdldoanchuyennganh";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set character set
$conn->set_charset("utf8mb4");

$trangChuLink = "#";
if ($vaiTro === 'Quản lý') {
    $trangChuLink = "quanlytrangchu.php";
} elseif ($vaiTro === 'Bán hàng') {
    $trangChuLink = "trangchubanhang.php";
} elseif ($vaiTro === 'Khách hàng') {
    $trangChuLink = "trangchukhachhang.php"; // bạn có thể đổi tên file này
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/giaodienkhachhang.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <title>Sàn giao dịch nông sản - Giới thiệu</title>
    <style>
        /* Additional styles for the introduction page */
        .hero-section {
            position: relative;
            height: 80vh;
            background: linear-gradient(rgba(0,0,0,0.4), rgba(0,0,0,0.4)), url('img/banner.jpg');
            background-size: cover;
            background-position: center;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: white;
        }

        .hero-content {
            max-width: 800px;
            padding: 0 20px;
        }

        .hero-title {
            font-size: 3.5rem;
            font-weight: bold;
            margin-bottom: 20px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
        }

        .hero-subtitle {
            font-size: 1.5rem;
            margin-bottom: 30px;
            line-height: 1.6;
        }

        .hero-description {
            font-size: 1.2rem;
            line-height: 1.8;
            margin-bottom: 40px;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        .intro-section {
            padding: 80px 0;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .intro-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 60px;
            align-items: center;
            margin-bottom: 60px;
        }

        .intro-text {
            font-size: 1.1rem;
            line-height: 1.8;
            color: #555;
        }

        .intro-text h2 {
            color: #2c5530;
            font-size: 2.5rem;
            margin-bottom: 30px;
            font-weight: 700;
        }

        .intro-text p {
            margin-bottom: 20px;
        }

        .intro-image {
            position: relative;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }

        .intro-image img {
            width: 100%;
            height: 400px;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .intro-image:hover img {
            transform: scale(1.05);
        }

        .categories-showcase {
            padding: 80px 0;
            background: white;
        }

        .categories-title {
            text-align: center;
            font-size: 2.5rem;
            color: #2c5530;
            margin-bottom: 60px;
            font-weight: 700;
        }

        .categories-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 40px;
            margin-bottom: 60px;
            padding: 20px;
            width: 100%;
            justify-items: center;
        }

        .category-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            position: relative;
            width: 100%;
            max-width: 380px;
        }

        /* Specific styling for the last row */
        .categories-grid > .category-card:nth-last-child(-n+2) {
            grid-column: span 1;
        }

        @media (min-width: 1200px) {
            .categories-grid {
                grid-template-columns: repeat(3, 1fr);
            }
            .categories-grid > .category-card:nth-last-child(-n+2) {
                margin: 0 auto;
                transform: translateX(50%);
            }
        }

        @media (max-width: 1199px) {
            .categories-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 767px) {
            .categories-grid {
                grid-template-columns: 1fr;
            }
        }

        .category-image {
            height: 250px;
            background-size: cover;
            background-position: center;
            position: relative;
        }

        .category-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(44,85,48,0.8), rgba(76,175,80,0.6));
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .category-title {
            color: white;
            font-size: 1.8rem;
            font-weight: 700;
            text-align: center;
        }

        .category-description {
            padding: 30px;
            text-align: center;
        }

        .category-description p {
            color: #666;
            line-height: 1.6;
            font-size: 1rem;
        }

        .stats-section {
            padding: 40px 0;
            background: linear-gradient(135deg, #2c5530 0%, #4caf50 100%);
            color: white;
            text-align: center;
        }

        .stats-section h2 {
            font-size: 2rem;
            margin-bottom: 30px;
        }

        .stats-grid {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 40px;
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
            flex-wrap: wrap;
        }

        .stat-item {
            padding: 15px;
            flex: 0 1 auto;
            min-width: 180px;
            text-align: center;
        }

        .stat-number {
            font-size: 2.8rem;
            font-weight: bold;
            margin-bottom: 10px;
            display: block;
        }

        .stat-label {
            font-size: 1rem;
            opacity: 0.9;
            white-space: nowrap;
        }

        .mission-section {
            padding: 80px 0;
            background: #f8f9fa;
        }

        .mission-content {
            text-align: center;
            max-width: 800px;
            margin: 0 auto;
        }

        .mission-title {
            font-size: 2.5rem;
            color: #2c5530;
            margin-bottom: 40px;
            font-weight: 700;
        }

        .mission-text {
            font-size: 1.2rem;
            line-height: 1.8;
            color: #555;
            margin-bottom: 40px;
        }

        .mission-values {
            display: flex;
            justify-content: center;
            align-items: stretch;
            gap: 30px;
            margin-top: 60px;
            padding: 0 20px;
        }

        .value-item {
            background: white;
            padding: 40px 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            text-align: center;
            flex: 1;
            max-width: 350px;
            min-width: 250px;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            align-items: center;
        }

        .value-icon {
            font-size: 3rem;
            color: #4caf50;
            margin-bottom: 20px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .value-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: #2c5530;
            margin-bottom: 15px;
            text-transform: uppercase;
        }

        .value-description {
            color: #666;
            line-height: 1.6;
            margin: 0;
        }

        @media (max-width: 1024px) {
            .mission-values {
                flex-direction: column;
                align-items: center;
            }
            
            .value-item {
                width: 100%;
                max-width: 500px;
            }
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .hero-title {
                font-size: 2.5rem;
            }
            
            .hero-subtitle {
                font-size: 1.2rem;
            }
            
            .intro-grid {
                grid-template-columns: 1fr;
                gap: 40px;
            }
            
            .categories-grid {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                flex-direction: column;
                gap: 30px;
            }
            
            .stat-item {
                width: 100%;
                min-width: auto;
            }
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

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="hero-content">
            <h1 class="hero-title">SÀN GIAO DỊCH NÔNG SẢN</h1>
            <p class="hero-subtitle">Kết nối nông sản sạch đến người tiêu dùng Việt</p>
            <p class="hero-description">
                Nền tảng tiên phong trong việc kết nối trực tiếp người tiêu dùng với các nhà vườn, 
                hợp tác xã và đơn vị sản xuất uy tín, mang đến sản phẩm nông sản chất lượng cao, 
                truy xuất nguồn gốc rõ ràng.
            </p>
        </div>
    </section>

    <!-- Introduction Section -->
    <section class="intro-section">
        <div class="container">
            <div class="intro-grid">
                <div class="intro-text">
                    <h2>Về Chúng Tôi</h2>
                    <p>
                        <strong>Sàn giao dịch nông sản</strong> là nền tảng tiên phong trong việc kết nối trực tiếp 
                        người tiêu dùng với các nhà vườn, hợp tác xã, đơn vị sản xuất và xuất khẩu uy tín 
                        trong và ngoài nước.
                    </p>
                    <p>
                        Chúng tôi cam kết mang đến những sản phẩm nông sản chất lượng cao, truy xuất nguồn gốc 
                        rõ ràng, đạt chuẩn <strong>VietGAP</strong> hoặc <strong>GlobalGAP</strong>.
                    </p>
                    <p>
                        Tất cả sản phẩm đều được bảo quản và vận chuyển theo quy trình nghiêm ngặt, 
                        đảm bảo chất lượng tối ưu khi đến tay khách hàng.
                    </p>
                </div>
                <div class="intro-image">
                    <img src="img/banner7.jpg" alt="Nông sản tươi sạch" />
                </div>
            </div>
        </div>
    </section>

    <!-- Categories Showcase -->
    <section class="categories-showcase">
        <div class="container">
            <h2 class="categories-title">DANH MỤC SẢN PHẨM</h2>
            <div class="categories-grid">
                <div class="category-card">
                    <div class="category-image" style="background-image: url('img/banner1.jpg');">
                        <div class="category-overlay">
                            <h3 class="category-title">TRÁI CÂY TƯƠI</h3>
                        </div>
                    </div>
                    <div class="category-description">
                        <p>Các loại trái cây tươi ngon, giàu vitamin và chất dinh dưỡng, được thu hoạch đúng độ chín từ các vườn cây uy tín.</p>
                    </div>
                </div>

                <div class="category-card">
                    <div class="category-image" style="background-image: url('img/banner2.jpg');">
                        <div class="category-overlay">
                            <h3 class="category-title">RAU CỦ SẠCH</h3>
                        </div>
                    </div>
                    <div class="category-description">
                        <p>Rau củ được trồng theo phương pháp hữu cơ, không sử dụng thuốc trừ sâu độc hại, đảm bảo an toàn cho sức khỏe.</p>
                    </div>
                </div>

                <div class="category-card">
                    <div class="category-image" style="background-image: url('img/banner3.jpg');">
                        <div class="category-overlay">
                            <h3 class="category-title">LÚA GẠO</h3>
                        </div>
                    </div>
                    <div class="category-description">
                        <p>Gạo chất lượng cao từ các vùng trồng lúa nổi tiếng, đảm bảo độ thuần khiết và hương vị truyền thống.</p>
                    </div>
                </div>

                <div class="category-card">
                    <div class="category-image" style="background-image: url('img/banner4.jpg');">
                        <div class="category-overlay">
                            <h3 class="category-title">THỦY SẢN</h3>
                        </div>
                    </div>
                    <div class="category-description">
                        <p>Thủy sản tươi sống từ các vùng nuôi trồng và đánh bắt uy tín, đảm bảo tươi ngon và giàu dinh dưỡng.</p>
                    </div>
                </div>

                <div class="category-card">
                    <div class="category-image" style="background-image: url('img/banner5.jpg');">
                        <div class="category-overlay">
                            <h3 class="category-title">SẢN PHẨM OCOP</h3>
                        </div>
                    </div>
                    <div class="category-description">
                        <p>Các sản phẩm OCOP đạt tiêu chuẩn chất lượng cao, mang đậm bản sắc văn hóa và đặc trưng của từng vùng miền.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats-section">
        <div class="container">
            <h2 style="font-size: 2.5rem; margin-bottom: 50px;">CON SỐ ẤN TƯỢNG</h2>
            <div class="stats-grid">
                <div class="stat-item">
                    <span class="stat-number">500+</span>
                    <span class="stat-label">Nhà cung cấp</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number">10,000+</span>
                    <span class="stat-label">Khách hàng tin tưởng</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number">50+</span>
                    <span class="stat-label">Tỉnh thành phủ sóng</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number">99%</span>
                    <span class="stat-label">Khách hàng hài lòng</span>
                </div>
            </div>
        </div>
    </section>

    <!-- Mission Section -->
    <section class="mission-section">
        <div class="container">
            <div class="mission-content">
                <h2 class="mission-title">SỨ MỆNH CỦA CHÚNG TÔI</h2>
                <p class="mission-text">
                    Sàn giao dịch không chỉ là nơi cung cấp nông sản sạch, mà còn là cầu nối bền vững 
                    giữa người tiêu dùng và nhà sản xuất, góp phần xây dựng một thị trường nông nghiệp 
                    minh bạch và phát triển bền vững.
                </p>
                
                <div class="mission-values">
                    <div class="value-item">
                        <div class="value-icon">🌱</div>
                        <h4 class="value-title">BỀN VỮNG</h4>
                        <p class="value-description">
                            Cam kết phát triển nông nghiệp bền vững, bảo vệ môi trường và nâng cao đời sống nông dân.
                        </p>
                    </div>
                    
                    <div class="value-item">
                        <div class="value-icon">🤝</div>
                        <h4 class="value-title">KẾT NỐI</h4>
                        <p class="value-description">
                            Tạo cầu nối tin cậy giữa người sản xuất và người tiêu dùng, xây dựng chuỗi giá trị minh bạch.
                        </p>
                    </div>
                    
                    <div class="value-item">
                        <div class="value-icon">⭐</div>
                        <h4 class="value-title">CHẤT LƯỢNG</h4>
                        <p class="value-description">
                            Đảm bảo chất lượng sản phẩm từ nguồn gốc đến tay người tiêu dùng với tiêu chuẩn khắt khe.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

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
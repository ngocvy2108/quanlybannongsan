<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit();
}

$vaiTro = $_SESSION['user']['VaiTro'];

// Lấy ID tài khoản từ session
$idTaiKhoan = $_SESSION['user']['IdTaiKhoan'] ?? '';

// Phân quyền
$coTheDangTin = false;
$coTheQuanLy = false;

if ($vaiTro === 'Quản lý') {
    $coTheDangTin = true;
    $coTheQuanLy = true;
} elseif ($vaiTro === 'Bán hàng') {
    $coTheDangTin = true;
} elseif ($vaiTro === 'Khách hàng') {
    // Khách hàng chỉ được xem và bình luận
} else {
    header("Location: index.php");
    exit();
}

// Kết nối CSDL
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "csdldoanchuyennganh";
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}

// Thiết lập charset cho kết nối
mysqli_set_charset($conn, 'utf8mb4');
$conn->query("SET NAMES utf8mb4");
$conn->query("SET CHARACTER SET utf8mb4");
$conn->query("SET SESSION collation_connection = 'utf8mb4_unicode_ci'");

// Nếu là quản lý, lấy IdNguoiQuanLy
if ($coTheQuanLy) {
    $idTaiKhoan = $_SESSION['user']['IdTaiKhoan'];
    $stmt = $conn->prepare("SELECT IdNguoiQuanLy FROM quanly WHERE IdTaiKhoan = ?");
    $stmt->bind_param("s", $idTaiKhoan);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        echo "Không tìm thấy quản lý phù hợp!";
        exit();
    }
    $row = $result->fetch_assoc();
    $idQuanLy = $row['IdNguoiQuanLy'];
}

// Hàm sinh ID mới
function generateNewId($table, $prefix) {
    global $conn;
    $query = "SELECT MAX(CAST(SUBSTRING(IdTinTuc, 3) AS UNSIGNED)) AS max_id FROM $table";
    $result = mysqli_query($conn, $query);
    $row = mysqli_fetch_assoc($result);
    $newId = $row['max_id'] ? $row['max_id'] + 1 : 1;
    return $prefix . str_pad($newId, 2, '0', STR_PAD_LEFT);
}

// Thêm tin tức
if ($coTheDangTin && $_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $tieuDe = $_POST['tieude'];
    $noiDung = $_POST['noidung'];
    $ngayDang = date('Y-m-d H:i:s');
    $idTinTuc = generateNewId('tintuc', 'TT');

    $hinhAnhData = null;
    if (isset($_FILES['hinhanh']) && $_FILES['hinhanh']['error'] === UPLOAD_ERR_OK) {
        $tmpName = $_FILES['hinhanh']['tmp_name'];
        $hinhAnhData = file_get_contents($tmpName);
    }

    $stmt = $conn->prepare("INSERT INTO tintuc (IdTinTuc, TieuDe, NoiDung, NgayDang, IdTaiKhoan, HinhAnh) VALUES (?, ?, ?, ?, ?, ?)");
    if (!$stmt) {
        die('Lỗi prepare: ' . $conn->error);
    }
    $stmt->bind_param("ssssss", $idTinTuc, $tieuDe, $noiDung, $ngayDang, $idTaiKhoan, $hinhAnhData);
    $stmt->execute();
    
    header("Location: tintuc.php");
    exit();
}

// Xóa tin tức
if (isset($_GET['delete'])) {
    $idDelete = $_GET['delete'];
    if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
        $success = false;
        $message = '';
        if ($coTheQuanLy || ($vaiTro === 'Bán hàng' && $conn->query("SELECT IdTaiKhoan FROM tintuc WHERE IdTinTuc = '$idDelete'")->fetch_assoc()['IdTaiKhoan'] == $idTaiKhoan)) {
            $success = $conn->query("DELETE FROM tintuc WHERE IdTinTuc = '$idDelete'");
            $message = $success ? 'Xóa thành công' : 'Lỗi: ' . $conn->error;
        } else {
            $message = 'Bạn không có quyền xóa tin này!';
        }
        header('Content-Type: application/json');
        echo json_encode(['success' => $success, 'message' => $message]);
        exit();
    } else {
        if ($coTheQuanLy || ($vaiTro === 'Bán hàng' && $conn->query("SELECT IdTaiKhoan FROM tintuc WHERE IdTinTuc = '$idDelete'")->fetch_assoc()['IdTaiKhoan'] == $idTaiKhoan)) {
            $conn->query("DELETE FROM tintuc WHERE IdTinTuc = '$idDelete'");
        }
        header("Location: tintuc.php");
        exit();
    }
}

// Lấy danh sách tin tức
$dsTinTuc = $conn->query("SELECT * FROM tintuc ORDER BY NgayDang DESC");

$trangChuLink = "#";
if ($vaiTro === 'Quản lý') {
    $trangChuLink = "quanlytrangchu.php";
} elseif ($vaiTro === 'Bán hàng') {
    $trangChuLink = "banhangtrangchu.php";
} elseif ($vaiTro === 'Khách hàng') {
    $trangChuLink = "trangchukhachhang.php";
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>Quản lý Tin tức</title>
    <link rel="stylesheet" href="css/giaodienkhachhang.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: #f4f4f9;
            font-family: 'Segoe UI', Arial, sans-serif;
            margin: 0;
            padding-top: 160px; /* Add padding to prevent content from being hidden under fixed header */
        }
        .news-container {
            max-width: 900px;
            margin: 48px auto;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 32px rgba(0,0,0,0.10);
            padding: 40px 48px 32px 48px;
        }
        .section-title {
            color: #257a2a;
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 24px;
            text-align: center;
            letter-spacing: 1px;
        }
        .form-box {
            background: #fafbfc;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(67,160,71,0.08);
            padding: 24px 32px;
            margin-bottom: 32px;
        }
        .form-label {
            font-weight: 600;
            color: #333;
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
        .news-list {
            margin-top: 32px;
        }
        .news-card {
            background: #fafbfc;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(67,160,71,0.08);
            padding: 24px 32px;
            margin-bottom: 24px;
            position: relative;
        }
        .news-card h3 {
            color: #257a2a;
            margin-bottom: 8px;
        }
        .news-card img {
            max-width: 180px;
            border-radius: 8px;
            margin: 10px 0;
            display: block;
        }
        .news-card .actions {
            margin-top: 12px;
        }
        .news-card .actions a {
            margin-right: 16px;
            color: #257a2a;
            font-weight: 500;
            text-decoration: none;
            transition: color 0.2s;
        }
        .news-card .actions a:hover {
            color: #e53935;
        }
        .news-card small {
            color: #888;
        }
        .back-button {
            position: absolute;
            top: 24px;
            left: 24px;
            background: #eee;
            padding: 8px 12px;
            border-radius: 6px;
            color: #257a2a;
            text-decoration: none;
            font-weight: 500;
            transition: background 0.2s;
        }
        .back-button:hover {
            background: #e0f2e9;
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
.nut-xoa { background: #e53935; color: #fff; }
.nut-xoa:hover { background: #b71c1c; color: #fff; }
.nut-sua { background: linear-gradient(90deg, #43a047 60%, #257a2a 100%); color: #fff; }
.nut-sua:hover { background: linear-gradient(90deg, #257a2a 60%, #43a047 100%); color: #fff; }
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

    <div class="news-container">
        <?php if ($coTheDangTin): ?>
        <h2 class="section-title">Đăng tin tức mới</h2>
        <form method="POST" enctype="multipart/form-data" class="form-box">
            <input type="hidden" name="action" value="add">
            <label class="form-label">Tiêu đề:</label>
            <input type="text" name="tieude" class="form-control" required>
            <label class="form-label">Nội dung:</label>
            <textarea name="noidung" rows="5" class="form-control" required></textarea>
            <label class="form-label">Hình ảnh:</label>
            <input type="file" name="hinhanh" accept="image/*" class="form-control">
            <button type="submit" class="btn-primary">Thêm tin tức</button>
        </form>
        <?php endif; ?>

        <h2 class="section-title">Danh sách tin tức đã đăng</h2>
        <div class="news-list">
        <?php while ($row = $dsTinTuc->fetch_assoc()): ?>
            <div class="news-card">
                <h3><?= $row['TieuDe'] ?></h3>
                <p><?= $row['NoiDung'] ?></p>
                <?php if (!empty($row['HinhAnh'])): ?>
                    <img src="data:image/jpeg;base64,<?= base64_encode($row['HinhAnh']) ?>" alt="Hình ảnh tin tức">
                <?php endif; ?>
                <small><i>Ngày đăng: <?= $row['NgayDang'] ?></i></small>
                <div class="actions">
                    <?php if ($coTheQuanLy || ($vaiTro === 'Bán hàng' && isset($row['IdTaiKhoan']) && $row['IdTaiKhoan'] == $idTaiKhoan)): ?>
                        <a href="#" class="btn-xoa-tintuc" data-id="<?= $row['IdTinTuc'] ?>"><i class="fas fa-trash"></i> Xóa</a>
                        <a href="sua_tintuc.php?id=<?= $row['IdTinTuc'] ?>"><i class="fas fa-edit"></i> Sửa</a>
                    <?php endif; ?>
                    <a href="chitiet_tintuc.php?id=<?= $row['IdTinTuc'] ?>"><i class="fas fa-eye"></i> Chi tiết</a>
                    <a href="binhluan.php?idTinTuc=<?= $row['IdTinTuc'] ?>"><i class="fas fa-comments"></i> Bình luận</a>
                </div>
            </div>
        <?php endwhile; ?>
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

    <!-- Modal xác nhận xóa tin tức -->
    <div id="modal-xac-nhan-xoa-tintuc" class="modal-xoa" style="display:none;z-index:99999;">
      <div class="modal-xoa-content">
        <h3 style="color:#e53935;">Xác nhận xóa tin tức</h3>
        <p>Bạn có chắc chắn muốn xóa tin tức này không?</p>
        <div class="modal-xoa-actions">
          <button id="btn-xac-nhan-xoa-tintuc" class="nut nut-xoa">Xóa</button>
          <button id="btn-huy-xoa-tintuc" class="nut nut-sua">Hủy</button>
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
    let idTinTucCanXoa = null;
    // Hiện toast
    function showToast(msg, success = true) {
      const toast = document.getElementById('toast-notification');
      toast.textContent = msg;
      toast.style.background = success ? '#e8f5e9' : '#ffebee';
      toast.style.color = success ? '#257a2a' : '#e53935';
      toast.style.display = 'flex';
      setTimeout(() => { toast.style.display = 'none'; }, 2500);
    }
    document.querySelectorAll('.btn-xoa-tintuc').forEach(btn => {
      btn.onclick = function(e) {
        e.preventDefault();
        idTinTucCanXoa = this.getAttribute('data-id');
        document.getElementById('modal-xac-nhan-xoa-tintuc').style.display = 'flex';
      };
    });
    document.getElementById('btn-huy-xoa-tintuc').onclick = function() {
      document.getElementById('modal-xac-nhan-xoa-tintuc').style.display = 'none';
      idTinTucCanXoa = null;
    };
    document.getElementById('btn-xac-nhan-xoa-tintuc').onclick = function() {
      if (!idTinTucCanXoa) return;
      document.getElementById('modal-xac-nhan-xoa-tintuc').style.display = 'none';
      fetch(`tintuc.php?delete=${encodeURIComponent(idTinTucCanXoa)}&ajax=1`)
        .then(res => res.json())
        .then(data => {
          if (data.success) {
            showToast('Xóa tin tức thành công!', true);
            setTimeout(() => { location.reload(); }, 1200);
          } else {
            showToast(data.message || 'Xóa thất bại!', false);
          }
        })
        .catch(() => {
          showToast('Có lỗi xảy ra khi xóa!', false);
        });
    };
    </script>
</body>
</html>
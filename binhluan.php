<?php
session_start();
$vaiTro = isset($_SESSION['user']['VaiTro']) ? $_SESSION['user']['VaiTro'] : '';
include('config.php');

// Hàm tạo ID mới
function generateNewId($table, $prefix) {
    global $conn;
    $query = "SELECT MAX(CAST(SUBSTRING(IdBinhLuan, 3) AS UNSIGNED)) AS max_id FROM $table";
    $result = mysqli_query($conn, $query);
    $row = mysqli_fetch_assoc($result);
    $newId = $row['max_id'] ? $row['max_id'] + 1 : 1;
    return $prefix . str_pad($newId, 2, '0', STR_PAD_LEFT);
}

// Xử lý bình luận gửi qua AJAX
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['noidung_binhluan'], $_POST['idTinTuc'])) {
    $noidung = mysqli_real_escape_string($conn, $_POST['noidung_binhluan']);
    $idTinTuc = $_POST['idTinTuc'];
    $idTaiKhoan = $_SESSION['user']['IdTaiKhoan'];
    $idBinhLuan = generateNewId('binhluan', 'BL');

    $insert = "INSERT INTO binhluan (IdBinhLuan, NoiDung, IdTinTuc, IdTaiKhoan, NgayBinhLuan)
               VALUES ('$idBinhLuan', '$noidung', '$idTinTuc', '$idTaiKhoan', NOW())";

    if (mysqli_query($conn, $insert)) {
        echo "success";
    } else {
        echo "Lỗi: " . mysqli_error($conn);
    }
    exit(); // Không hiển thị HTML nữa
}

$idTinTuc = $_GET['idTinTuc'] ?? '';
$sqlTinTuc = "SELECT t.TieuDe, t.NoiDung, t.NgayDang, t.HinhAnh, t.IdTinTuc, tk.TenDangNhap
              FROM tintuc t
              JOIN taikhoan tk ON t.IdTaiKhoan = tk.IdTaiKhoan
              WHERE t.IdTinTuc = '$idTinTuc'";
$resultTinTuc = mysqli_query($conn, $sqlTinTuc);
if (!$resultTinTuc) {
    die('Lỗi truy vấn tin tức: ' . mysqli_error($conn));
}
$tt = mysqli_fetch_assoc($resultTinTuc);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Tin tức & Bình luận</title>
    <link rel="stylesheet" href="css/giaodienkhachhang.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body { 
            font-family: 'Segoe UI', Arial, sans-serif; 
            background-color: #f4f4f9; 
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

        .container { 
            width: 90%; 
            max-width: 900px; 
            margin: 40px auto; 
            padding: 28px 24px; 
            background-color: #fff; 
            border-radius: 14px; 
            box-shadow: 0 4px 24px rgba(46,125,50,0.10); 
        }
        .back-button { position: absolute; top: 20px; left: 20px; background: #eee; padding: 8px 12px; border-radius: 6px; color: #2e7d32; text-decoration: none; font-weight: 500; }
        .back-button:hover { background-color: #e0f2e9; 
        }
        h3 { color: #2e7d32; }
        .news-item { border: 1px solid #eee; padding: 20px; margin-bottom: 20px; border-radius: 10px; background-color: #fafbfc; box-shadow: 0 2px 8px rgba(46,125,50,0.06); }
        .news-item img { max-width: 100%; height: auto; margin: 10px 0; border-radius: 8px; }
        .comment-form { display: flex; flex-direction: column; }
        .comment-form textarea { width: 100%; padding: 10px; margin-bottom: 10px; border-radius: 7px; border: 1.5px solid #e0e0e0; resize: vertical; font-size: 15px; }
        .comment-form button { padding: 10px 20px; background: #2e7d32; color: white; border: none; border-radius: 7px; cursor: pointer; font-weight: 500; font-size: 15px; }
        .comment-form button:hover { background: #e65100; }
        .comments { margin-top: 20px; }
        .comment-item { margin-bottom: 10px; background: #f4f4f9; border-radius: 6px; padding: 8px 12px; }
        .comment-item b { color: #2e7d32; }
        @media (max-width: 600px) {
          .container { padding: 8px 2px; }
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
<div class="container">
    <?php if ($tt): ?>
        <div class="news-item">
            <p><strong>Người đăng:</strong> <?= $tt['TenDangNhap'] ?></p>
            <h3><?= $tt['TieuDe'] ?></h3>
            <p><?= $tt['NoiDung'] ?></p>
            <?php if (!empty($tt['HinhAnh'])): ?>
                <div>
                    <img src="data:image/jpeg;base64,<?= base64_encode($tt['HinhAnh']) ?>" alt="Ảnh minh họa">
                </div>
            <?php endif; ?>
            <i>Ngày đăng: <?= $tt['NgayDang'] ?></i>
            <div class="comments" id="binhluan_<?= $tt['IdTinTuc'] ?>">
                <h4>Bình luận:</h4>
                <?php
                $sqlBL = "SELECT b.*, t.TenDangNhap FROM binhluan b 
                          JOIN taikhoan t ON b.IdTaiKhoan = t.IdTaiKhoan 
                          WHERE b.IdTinTuc = '$idTinTuc' ORDER BY NgayBinhLuan DESC";
                $resBL = mysqli_query($conn, $sqlBL);
                while ($bl = mysqli_fetch_assoc($resBL)) {
                    echo "<div class='comment-item'><b>{$bl['TenDangNhap']}</b>: {$bl['NoiDung']} <i>({$bl['NgayBinhLuan']})</i></div>";
                }
                ?>
            </div>
            <form class="comment-form" data-id="<?= $tt['IdTinTuc'] ?>">
                <textarea name="noidung_binhluan" required placeholder="Thêm bình luận..."></textarea>
                <button type="submit">Bình luận</button>
            </form>
        </div>
    <?php else: ?>
        <p>Không tìm thấy tin tức này.</p>
    <?php endif; ?>
</div>

<script>
$(document).ready(function(){
    $('.comment-form').on('submit', function(e){
        e.preventDefault();
        const form = $(this);
        const idTinTuc = form.data('id');
        const noidung = form.find('textarea[name="noidung_binhluan"]').val();

        $.ajax({
            method: 'POST',
            url: 'binhluan.php',
            data: {
                idTinTuc: idTinTuc,
                noidung_binhluan: noidung
            },
            success: function(response){
                if (response.trim() === "success") {
                    $.get('lay_binhluan.php', { idTinTuc: idTinTuc }, function(data) {
                        $('#binhluan_' + idTinTuc).html('<h4>Bình luận:</h4>' + data);
                    });
                    form[0].reset();
                } else {
                    alert(response);
                }
            },
            error: function(xhr, status, error){
                alert('Lỗi gửi bình luận: ' + error);
            }
        });
    });
});
</script>
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
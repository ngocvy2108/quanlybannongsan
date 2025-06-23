<?php
session_start();
$vaiTro = isset($_SESSION['user']['VaiTro']) ? $_SESSION['user']['VaiTro'] : '';
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['VaiTro'], ['Quản lý', 'Bán hàng'])) {
    header("Location: index.php");
    exit();
}

include('config.php');

// Lấy ID tin tức từ URL
if (!isset($_GET['id'])) {
    echo "Không có ID tin tức được cung cấp.";
    exit();
}
$idTinTuc = $_GET['id'];

// Lấy thông tin tin tức để kiểm tra quyền
$stmt = $conn->prepare("SELECT * FROM tintuc WHERE IdTinTuc = ?");
$stmt->bind_param("s", $idTinTuc);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    echo "Không tìm thấy tin tức.";
    exit();
}
$tintuc = $result->fetch_assoc();

// Nếu là Bán hàng, chỉ cho phép sửa tin của mình
if ($_SESSION['user']['VaiTro'] === 'Bán hàng' && $tintuc['IdTaiKhoan'] !== $_SESSION['user']['IdTaiKhoan']) {
    header("Location: index.php");
    exit();
}

// Xử lý cập nhật khi submit form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tieuDe = $_POST['tieude'];
    $noiDung = $_POST['noidung'];

    // Kiểm tra nếu có ảnh mới
    $hasNewImage = isset($_FILES['hinhanh']) && $_FILES['hinhanh']['error'] === UPLOAD_ERR_OK;
    if ($hasNewImage) {
        $hinhAnhData = file_get_contents($_FILES['hinhanh']['tmp_name']);
        $stmt = $conn->prepare("UPDATE tintuc SET TieuDe = ?, NoiDung = ?, HinhAnh = ? WHERE IdTinTuc = ?");
        $stmt->bind_param("ssss", $tieuDe, $noiDung, $hinhAnhData, $idTinTuc);
    } else {
        $stmt = $conn->prepare("UPDATE tintuc SET TieuDe = ?, NoiDung = ? WHERE IdTinTuc = ?");
        $stmt->bind_param("sss", $tieuDe, $noiDung, $idTinTuc);
    }

    if ($stmt->execute()) {
        echo "<script>alert('Cập nhật tin tức thành công'); window.location.href = 'tintuc.php';</script>";
        exit();
    } else {
        echo "❌ Lỗi: " . $conn->error;
    }
}

// Lấy thông tin tin tức để hiển thị lên form
$stmt = $conn->prepare("SELECT * FROM tintuc WHERE IdTinTuc = ?");
$stmt->bind_param("s", $idTinTuc);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    echo "Không tìm thấy tin tức.";
    exit();
}
$tintuc = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="css/giaodienkhachhang.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <title>Sửa tin tức</title>
    <style>
        body {
        padding-top: 160px; /* Add padding to prevent content from being hidden under fixed header */
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
#modal-xac-nhan-luu.modal-xac-nhan {
  display: none;
  position: fixed;
  z-index: 99999;
  left: 0; top: 0; width: 100vw; height: 100vh;
  background: rgba(44, 62, 80, 0.25);
  justify-content: center; align-items: center;
}
#modal-xac-nhan-luu.modal-xac-nhan[style*="display: flex"] {
  display: flex !important;
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
  color: #257a2a;
  margin-bottom: 18px;
  font-size: 1.35rem;
  font-weight: 700;
  letter-spacing: 0.5px;
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
.nut-sua { background: linear-gradient(90deg, #43a047 60%, #257a2a 100%); color: #fff; }
.nut-sua:hover { background: linear-gradient(90deg, #257a2a 60%, #43a047 100%); color: #fff; }
.nut-xoa { background: #e53935; color: #fff; }
.nut-xoa:hover { background: #b71c1c; color: #fff; }
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

    <h2 class="edit-title">Sửa tin tức</h2>
    <div class="edit-news-container">
    <form method="POST" enctype="multipart/form-data" class="edit-news-form">
        <div class="form-group">
            <label for="tieude">Tiêu đề:</label>
            <input type="text" id="tieude" name="tieude" value="<?= htmlspecialchars($tintuc['TieuDe']) ?>" required>
        </div>
        <div class="form-group">
            <label for="noidung">Nội dung:</label>
            <textarea id="noidung" name="noidung" rows="6" required><?= htmlspecialchars($tintuc['NoiDung']) ?></textarea>
        </div>
        <div class="form-group">
            <label>Hình ảnh hiện tại:</label><br>
            <?php if (!empty($tintuc['HinhAnh'])): ?>
                <img src="data:image/jpeg;base64,<?= base64_encode($tintuc['HinhAnh']) ?>" alt="Hình ảnh tin tức" class="current-news-img">
            <?php else: ?>
                <div class="no-img-news"><i class="fas fa-image"></i><span>Không có ảnh</span></div>
            <?php endif; ?>
        </div>
        <div class="form-group">
            <label for="hinhanh">Chọn ảnh mới (nếu muốn thay đổi):</label>
            <input type="file" id="hinhanh" name="hinhanh" accept="image/*">
        </div>
        <div class="form-group form-btn-group">
            <button type="submit" class="btn-update-news">Cập nhật</button>
        </div>
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

<style>
.edit-title {
    text-align: center;
    color: #257a2a;
    font-size: 2.1rem;
    font-weight: 700;
    margin: 36px 0 18px 0;
    letter-spacing: 1px;
}
.edit-news-container {
    max-width: 540px;
    margin: 0 auto 48px auto;
    background: #fff;
    border-radius: 18px;
    box-shadow: 0 8px 32px rgba(46,125,50,0.13);
    padding: 36px 32px 28px 32px;
}
.edit-news-form .form-group {
    margin-bottom: 22px;
    display: flex;
    flex-direction: column;
}
.edit-news-form label {
    font-weight: 600;
    color: #257a2a;
    margin-bottom: 7px;
}
.edit-news-form input[type="text"],
.edit-news-form textarea {
    border: 1.5px solid #c8e6c9;
    border-radius: 8px;
    padding: 10px 14px;
    font-size: 1.08rem;
    background: #f6faf7;
    transition: border 0.2s;
}
.edit-news-form input[type="text"]:focus,
.edit-news-form textarea:focus {
    border: 1.5px solid #43a047;
    outline: none;
}
.edit-news-form input[type="file"] {
    margin-top: 6px;
}
.current-news-img {
    max-width: 100%;
    max-height: 180px;
    border-radius: 10px;
    box-shadow: 0 2px 12px rgba(67,160,71,0.10);
    margin-bottom: 6px;
}
.no-img-news {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #bdbdbd;
    font-size: 1.1rem;
    margin-bottom: 6px;
}
.no-img-news i {
    font-size: 1.7rem;
}
.btn-update-news {
    background: linear-gradient(90deg, #43a047 60%, #257a2a 100%);
    color: #fff;
    border: none;
    border-radius: 10px;
    padding: 12px 0;
    width: 100%;
    font-size: 1.13rem;
    font-weight: 600;
    cursor: pointer;
    box-shadow: 0 2px 8px rgba(67,160,71,0.08);
    transition: background 0.2s, box-shadow 0.2s;
}
.btn-update-news:hover {
    background: linear-gradient(90deg, #257a2a 60%, #43a047 100%);
    box-shadow: 0 4px 16px rgba(67,160,71,0.13);
}
.form-btn-group {
    margin-top: 18px;
}
@media (max-width: 700px) {
    .edit-news-container { padding: 18px 6vw 18px 6vw; }
    .edit-title { font-size: 1.5rem; }
}
</style>

<!-- Modal xác nhận lưu thay đổi -->
<div id="modal-xac-nhan-luu" class="modal-xac-nhan">
  <div class="modal-xac-nhan-content">
    <h3>Xác nhận lưu thay đổi</h3>
    <p>Bạn có chắc muốn lưu thay đổi?</p>
    <div class="modal-xac-nhan-actions">
      <button id="btn-xac-nhan-luu" class="nut nut-sua" type="button">Lưu</button>
      <button id="btn-huy-luu" class="nut nut-xoa" type="button">Hủy</button>
    </div>
  </div>
</div>
<!-- Toast Notification -->
<div class="toast" id="toast-notification">
    <span class="toast-icon" id="toast-icon">✓</span>
    <span id="toast-message"></span>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
  var form = document.querySelector('.edit-news-form');
  var modal = document.getElementById('modal-xac-nhan-luu');
  var btnXacNhan = document.getElementById('btn-xac-nhan-luu');
  var btnHuy = document.getElementById('btn-huy-luu');
  var submitForm = null;

  // Debug: kiểm tra các phần tử
  console.log('form:', form);
  console.log('modal:', modal);
  console.log('btnXacNhan:', btnXacNhan);
  console.log('btnHuy:', btnHuy);

  if (form && modal && btnXacNhan && btnHuy) {
    form.addEventListener('submit', function(e) {
      e.preventDefault();
      submitForm = this;
      console.log('Submit form bị chặn, chuẩn bị hiện modal');
      modal.style.display = 'flex';
    });
    btnHuy.onclick = function() {
      modal.style.display = 'none';
      console.log('Đã bấm Hủy modal');
    };
    btnXacNhan.onclick = function() {
      modal.style.display = 'none';
      console.log('Đã bấm xác nhận modal, gửi AJAX');
      // Gửi form bằng AJAX
      let formData = new FormData(submitForm);
      fetch(window.location.pathname + window.location.search, {
          method: "POST",
          body: formData
      })
      .then(response => response.text())
      .then(result => {
          let toast = document.getElementById("toast-notification");
          let toastMsg = document.getElementById("toast-message");
          let toastIcon = document.getElementById("toast-icon");
          if (result.includes("Cập nhật tin tức thành công")) {
              toast.className = "toast show";
              toastIcon.textContent = "✓";
              toastMsg.textContent = "Cập nhật tin tức thành công!";
              setTimeout(() => {
                  toast.className = "toast";
                  window.location = 'tintuc.php';
              }, 1800);
          } else {
              toast.className = "toast show error";
              toastIcon.textContent = "✖";
              toastMsg.textContent = "Có lỗi xảy ra khi lưu thay đổi!";
              setTimeout(() => {
                  toast.className = "toast";
              }, 2500);
          }
      })
      .catch(error => {
          let toast = document.getElementById("toast-notification");
          let toastMsg = document.getElementById("toast-message");
          let toastIcon = document.getElementById("toast-icon");
          toast.className = "toast show error";
          toastIcon.textContent = "✖";
          toastMsg.textContent = "❌ Lưu thất bại: " + error;
          setTimeout(() => {
              toast.className = "toast";
          }, 2500);
      });
    };
  } else {
    console.log('Không tìm thấy form hoặc modal xác nhận!');
  }
});
</script>

</body>
</html>


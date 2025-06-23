<?php
session_start();
$vaiTro = isset($_SESSION['user']['VaiTro']) ? $_SESSION['user']['VaiTro'] : '';
if (!isset($_SESSION['IdNguoiBan'])) {
    header("Location: dangnhap.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="css/giaodienkhachhang.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <title>Thêm Sản Phẩm</title>
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background-color: #f4f4f9;
            margin: 0;
            padding: 20px;
        }
        .khung {
            max-width: 600px;
            margin: 40px auto;
            background: #fff;
            border-radius: 22px;
            box-shadow: 0 6px 32px rgba(46,125,50,0.10);
            overflow: hidden;
        }
        .tieude {
            background-color: #2e7d32;
            color: white;
            text-align: center;
            padding: 24px 20px 18px 20px;
            font-size: 22px;
            font-weight: 700;
            letter-spacing: 0.5px;
        }
        form {
            padding: 32px 28px 24px 28px;
        }
        .group {
            margin-bottom: 22px;
        }
        .group label {
            display: block;
            margin-bottom: 8px;
            color: #2e7d32;
            font-weight: 600;
        }
        .group input[type="text"],
        .group input[type="number"],
        .group textarea,
        .group select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 10px;
            font-size: 16px;
            transition: border-color 0.2s;
            box-sizing: border-box;
            font-family: 'Segoe UI', Arial, sans-serif;
        }
        .group input[type="text"]:focus,
        .group input[type="number"]:focus,
        .group textarea:focus,
        .group select:focus {
            border-color: #2e7d32;
            outline: none;
            box-shadow: 0 0 0 2px rgba(46,125,50,0.10);
        }
        .group textarea {
            height: 100px;
            resize: vertical;
        }
        .group input[type="file"] {
            margin-top: 6px;
        }
        .group .radio-row {
            display: flex;
            gap: 20px;
            align-items: center;
            margin-bottom: 10px;
        }
        .group .radio-row label {
            display: flex;
            align-items: center;
            gap: 5px;
            margin-bottom: 0;
            color: #333;
            font-weight: 500;
        }
        .nut-haichucnang {
            display: flex;
            gap: 18px;
            margin-top: 32px;
        }
        .nut-haichucnang input[type="submit"],
        .nut-haichucnang .btn-back {
            flex: 1;
            padding: 14px;
            border: none;
            border-radius: 12px;
            font-size: 17px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s;
            text-align: center;
            text-decoration: none;
        }
        .nut-haichucnang input[type="submit"] {
            background-color: #2e7d32;
            color: white;
        }
        .nut-haichucnang .btn-back {
            background-color: #e65100;
            color: white;
        }
        .nut-haichucnang input[type="submit"]:hover {
            background-color: #1b5e20;
        }
        .nut-haichucnang .btn-back:hover {
            background-color: #bf360c;
        }
        #message {
            margin-top: 20px;
            text-align: center;
            font-weight: bold;
            color: green;
        }
        @media (max-width: 700px) {
            .khung {
                margin: 16px;
            }
            form {
                padding: 16px 8px 12px 8px;
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
        .toast {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background-color: #2e7d32;
            color: white;
            padding: 16px 28px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.18);
            display: flex;
            align-items: center;
            z-index: 9999;
            font-size: 1.08rem;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s, transform 0.3s;
            transform: translateY(40px);
        }
        .toast.show {
            opacity: 1;
            pointer-events: auto;
            transform: translateY(0);
        }
        .toast-icon {
            margin-right: 12px;
            font-size: 1.4rem;
        }
        .toast.error {
            background-color: #e53935;
        }
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
        .nut-xoa { background: #2e7d32; color: #fff; }
        .nut-xoa:hover { background: #1b5e20; }
        .nut-sua { background: #e65100; color: #fff; }
        .nut-sua:hover { background: #bf360c; }
        .error-message {
            display: none;
            color: #e53935;
            font-size: 14px;
            margin-top: 5px;
            font-weight: 500;
        }
        
        .group input[type="number"].error {
            border-color: #e53935;
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
<div class="khung">
    <div class="tieude">THÊM SẢN PHẨM MỚI</div>
    <form id="themForm" enctype="multipart/form-data" method="POST">
        <div class="group">
            <label>Tên sản phẩm:</label>
            <input type="text" name="TenSanPham" required>
        </div>
        <div class="group">
            <label>Giá:</label>
            <input type="number" name="Gia" step="1000" required min="0" oninput="validatePositiveNumber(this)">
            <span class="error-message" id="giaError"></span>
        </div>
        <div class="group">
            <label>Loại:</label>
            <select name="Loai" required>
                <option value="1">Loại 1</option>
                <option value="2">Loại 2</option>
                <option value="3">Loại 3</option>
            </select>
        </div>
        <div class="group">
            <label>Loại sản phẩm:</label>
            <select name="LoaiSanPham" required>
                <option value="Trái cây">Trái cây</option>
                <option value="Rau củ">Rau củ</option>
                <option value="Lúa gạo">Lúa gạo</option>
                <option value="Thủy Sản">Thủy Sản</option>
                <option value="Sản phẩm OCOP">Sản phẩm OCOP</option>
            </select>
        </div>
        <div class="group">
            <label>Số lượng tồn kho:</label>
            <input type="number" name="SoLuongTonKho" min="0" oninput="validatePositiveNumber(this)">
            <span class="error-message" id="soLuongError"></span>
        </div>
        <div class="group">
            <label>Mô tả:</label>
            <textarea name="MoTa"></textarea>
        </div>
        <div class="group">
            <label>Ảnh sản phẩm:</label>
            <input type="file" name="anh" accept="image/*" required>
        </div>
        <div class="group">
            <label>Nổi bật:</label>
            <div class="radio-row">
                <label><input type="radio" id="noibat_co" name="noibat" value="1" required> Có</label>
                <label><input type="radio" id="noibat_khong" name="noibat" value="0"> Không</label>
            </div>
        </div>
        <div class="nut-haichucnang">
            <input type="submit" value="Gửi quản lý duyệt">
        </div>
    </form>
    <div id="message"></div>
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

<!-- Toast Notification -->
<div class="toast" id="toast-notification">
    <span class="toast-icon" id="toast-icon">✓</span>
    <span id="toast-message"></span>
</div>

<!-- Modal xác nhận gửi sản phẩm -->
<div id="modal-xac-nhan-gui" class="modal-xac-nhan">
  <div class="modal-xac-nhan-content">
    <h3>Xác nhận gửi sản phẩm</h3>
    <p>Bạn có chắc muốn gửi sản phẩm để duyệt?</p>
    <div class="modal-xac-nhan-actions">
      <button id="btn-xac-nhan-gui" class="nut nut-xoa">Gửi</button>
      <button id="btn-huy-gui" class="nut nut-sua">Hủy</button>
    </div>
  </div>
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
<script>
let submitForm = null;
document.getElementById("themForm").addEventListener("submit", function (e) {
    const gia = parseFloat(this.Gia.value);
    const soLuong = parseFloat(this.SoLuongTonKho.value);
    
    if (gia <= 0 || soLuong <= 0) {
        e.preventDefault();
        if (gia <= 0) {
            document.getElementById('giaError').textContent = 'Giá phải lớn hơn 0';
            document.getElementById('giaError').style.display = 'block';
            this.Gia.style.borderColor = '#e53935';
        }
        if (soLuong <= 0) {
            document.getElementById('soLuongError').textContent = 'Số lượng phải lớn hơn 0';
            document.getElementById('soLuongError').style.display = 'block';
            this.SoLuongTonKho.style.borderColor = '#e53935';
        }
        return;
    }
    
    e.preventDefault();
    submitForm = this;
    document.getElementById('modal-xac-nhan-gui').style.display = 'flex';
});

document.getElementById('btn-xac-nhan-gui').onclick = function() {
    document.getElementById('modal-xac-nhan-gui').style.display = 'none';
    // Gửi form như cũ
    let form = submitForm;
    let formData = new FormData(form);

    fetch("xuly_them_sanpham.php", {
        method: "POST",
        body: formData
    })
    .then(response => response.text())
    .then(result => {
        let toast = document.getElementById("toast-notification");
        let toastMsg = document.getElementById("toast-message");
        let toastIcon = document.getElementById("toast-icon");
        if (result.includes("✅")) {
            toast.className = "toast show";
            toastIcon.textContent = "✓";
            toastMsg.textContent = "Gửi sản phẩm thành công! Đang chờ quản lý duyệt.";
            // Reset form khi thành công
            form.reset();
            // Clear file input
            document.querySelector('input[type="file"]').value = '';
            // Scroll lên đầu trang
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        } else {
            toast.className = "toast show error";
            toastIcon.textContent = "✖";
            toastMsg.textContent = "Có lỗi xảy ra khi gửi sản phẩm!";
        }
        setTimeout(() => {
            toast.className = "toast";
        }, 2500);
        document.getElementById("message").innerHTML = "";
    })
    .catch(error => {
        let toast = document.getElementById("toast-notification");
        let toastMsg = document.getElementById("toast-message");
        let toastIcon = document.getElementById("toast-icon");
        toast.className = "toast show error";
        toastIcon.textContent = "✖";
        toastMsg.textContent = "❌ Gửi thất bại: " + error;
        setTimeout(() => {
            toast.className = "toast";
        }, 2500);
        document.getElementById("message").innerHTML = "";
    });
};
document.getElementById('btn-huy-gui').onclick = function() {
    document.getElementById('modal-xac-nhan-gui').style.display = 'none';
};

function validatePositiveNumber(input) {
    const value = parseFloat(input.value);
    const errorId = input.name === 'Gia' ? 'giaError' : 'soLuongError';
    const errorElement = document.getElementById(errorId);
    
    if (value < 0) {
        input.value = '';
        errorElement.textContent = 'Vui lòng nhập số dương';
        errorElement.style.display = 'block';
        input.style.borderColor = '#e53935';
    } else if (value === 0) {
        errorElement.textContent = 'Giá trị phải lớn hơn 0';
        errorElement.style.display = 'block';
        input.style.borderColor = '#e53935';
    } else {
        errorElement.textContent = '';
        errorElement.style.display = 'none';
        input.style.borderColor = '#2e7d32';
    }
}
</script>

</body>
</html>

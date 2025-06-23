<?php
include("config.php");
session_start();
$vaiTro = isset($_SESSION['user']['VaiTro']) ? $_SESSION['user']['VaiTro'] : '';
if (!isset($_GET['id'])) {
    header("Location: quanlikhachhang.php");
    exit();
}

$id = $_GET['id'];
$sql = "SELECT * FROM khachhang WHERE IdKhachHang='$id'";
$result = $conn->query($sql);
if ($result->num_rows == 0) {
    header("Location: quanlikhachhang.php");
    exit();
}
$row = $result->fetch_assoc();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $ten = $_POST['TenKhachHang'];
    $diachi = $_POST['DiaChi'];
    $sdt = $_POST['SDT'];
    $update_sql = "UPDATE khachhang 
                   SET TenKhachHang='$ten', DiaChi='$diachi', SDT='$sdt'
                   WHERE IdKhachHang='$id'";
    if ($conn->query($update_sql) === TRUE) {
        header("Location: suakhachhang.php?id=" . $id . "&update=success");
        exit();
    } else {
        header("Location: suakhachhang.php?id=" . $id . "&update=error&message=" . urlencode($conn->error));
        exit();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="css/giaodienkhachhang.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <title>Cập nhật thông tin khách hàng</title>
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
        .khung {
            max-width: 600px;
            margin: 40px auto;
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 4px 24px rgba(46,125,50,0.10);
            overflow: hidden;
        }
        .tieude {
            background-color: #2e7d32;
            color: white;
            text-align: center;
            padding: 20px;
            font-size: 20px;
            font-weight: 600;
            letter-spacing: 0.5px;
        }
        form {
            padding: 30px;
        }
        .group {
            margin-bottom: 20px;
        }
        .group label {
            display: block;
            margin-bottom: 8px;
            color: #2e7d32;
            font-weight: 500;
        }
        .group input[type="text"],
        .group input[type="number"],
        .group input[type="email"],
        .group input[type="password"],
        .group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 15px;
            transition: border-color 0.2s;
            box-sizing: border-box;
        }
        .group input[type="text"]:focus,
        .group input[type="number"]:focus,
        .group input[type="email"]:focus,
        .group input[type="password"]:focus,
        .group textarea:focus {
            border-color: #2e7d32;
            outline: none;
            box-shadow: 0 0 0 2px rgba(46,125,50,0.1);
        }
        .group textarea {
            height: 100px;
            resize: vertical;
        }
        .nut-haichucnang {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }
        .nut-haichucnang input[type="submit"],
        .nut-haichucnang .btn-back {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
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
        @media (max-width: 700px) {
            .khung {
                margin: 20px;
            }
            form {
                padding: 20px;
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
.modal-xoa {
    display: none;
    position: fixed;
    z-index: 99999;
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
    color: #257a2a;
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

/* Validation Styles */
.error-message {
    color: #e53935;
    font-size: 14px;
    margin-top: 5px;
    display: none;
    font-weight: 500;
    padding-left: 2px;
}

.group input.error {
    border-color: #e53935 !important;
    background-color: #ffebee !important;
}

.group input.valid {
    border-color: #2e7d32 !important;
    background-color: #e8f5e9 !important;
}

.group input:focus {
    border-color: #2e7d32;
    outline: none;
    box-shadow: 0 0 0 2px rgba(46,125,50,0.1);
}

.toast.error {
    background-color: #ffebee !important;
    color: #e53935 !important;
}

.toast.success {
    background-color: #e8f5e9 !important;
    color: #2e7d32 !important;
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

    
<div class="khung">
    <div class="tieude">CẬP NHẬT THÔNG TIN KHÁCH HÀNG</div>
    <form id="form-update-customer" method="post">
        <div class="group">
            <label>ID Khách Hàng:</label>
            <input type="text" value="<?php echo $row['IdKhachHang']; ?>" disabled>
        </div>
        <div class="group">
            <label>Tên Khách Hàng:</label>
            <input type="text" name="TenKhachHang" value="<?php echo $row['TenKhachHang']; ?>" pattern="[A-Za-zÀ-ỹ\s]+" title="Chỉ được nhập chữ cái và khoảng trắng" required>
            <span class="error-message" id="tenError"></span>
        </div>
        <div class="group">
            <label>Địa chỉ:</label>
            <input type="text" name="DiaChi" value="<?php echo $row['DiaChi']; ?>" required>
            <span class="error-message" id="diachiError"></span>
        </div>
        <div class="group">
            <label>Số điện thoại:</label>
            <input type="text" name="SDT" value="<?php echo $row['SDT']; ?>" pattern="[0-9]{10}" title="Số điện thoại phải có 10 chữ số" required>
            <span class="error-message" id="sdtError"></span>
        </div>
        <div class="group">
            <label>ID Tài khoản:</label>
            <input type="text" name="IdTaiKhoan" value="<?php echo $row['IdTaiKhoan']; ?>" readonly>
        </div>
        <div class="group">
            <label>ID Người quản lý:</label>
            <input type="text" name="IdNguoiQuanLy" value="<?php echo $row['IdNguoiQuanLy']; ?>" readonly>
        </div>
        <div class="nut-haichucnang">
            <input type="submit" value="Cập nhật">
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

<!-- Modal xác nhận cập nhật -->
<div id="modal-xac-nhan-update" class="modal-xoa" style="display:none;z-index:99999;">
  <div class="modal-xoa-content">
    <h3>Xác nhận cập nhật thông tin</h3>
    <p>Bạn có chắc chắn muốn cập nhật thông tin khách hàng này không?</p>
    <div class="modal-xoa-actions">
      <button id="btn-xac-nhan-update" class="nut nut-sua">Cập nhật</button>
      <button id="btn-huy-update" class="nut nut-xoa">Hủy</button>
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
function showToast(msg, success = true) {
    const toast = document.getElementById('toast-notification');
    toast.textContent = msg;
    toast.style.background = success ? '#e8f5e9' : '#ffebee';
    toast.style.color = success ? '#257a2a' : '#e53935';
    toast.style.display = 'flex';
    
    if (success) {
        setTimeout(() => {
            window.location.href = 'quanlikhachhang.php';
        }, 2000);
    } else {
        setTimeout(() => {
            toast.style.display = 'none';
        }, 2000);
    }
}
function validateForm() {
    let isValid = true;
    const tenInput = document.querySelector('input[name="TenKhachHang"]');
    const sdtInput = document.querySelector('input[name="SDT"]');
    
    // Validate Tên
    if (!tenInput.value.match(/^[A-Za-zÀ-ỹ\s]+$/)) {
        document.getElementById('tenError').textContent = 'Tên chỉ được chứa chữ cái và khoảng trắng';
        document.getElementById('tenError').style.display = 'block';
        tenInput.style.borderColor = '#e53935';
        isValid = false;
    } else {
        document.getElementById('tenError').style.display = 'none';
        tenInput.style.borderColor = '#2e7d32';
    }
    
    // Validate SDT
    if (!sdtInput.value.match(/^[0-9]{10}$/)) {
        document.getElementById('sdtError').textContent = 'Số điện thoại phải có đúng 10 chữ số';
        document.getElementById('sdtError').style.display = 'block';
        sdtInput.style.borderColor = '#e53935';
        isValid = false;
    } else {
        document.getElementById('sdtError').style.display = 'none';
        sdtInput.style.borderColor = '#2e7d32';
    }
    
    return isValid;
}
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('form-update-customer');
    let submitConfirmed = false;

    const urlParams = new URLSearchParams(window.location.search);
    const updateStatus = urlParams.get('update');
    const errorMessage = urlParams.get('message');
    
    if (updateStatus === 'success') {
        showToast('✅ Cập nhật thông tin khách hàng thành công!', true);
        window.history.replaceState({}, document.title, window.location.pathname + '?id=' + '<?php echo $id; ?>');
    } else if (updateStatus === 'error') {
        showToast('❌ Cập nhật thất bại: ' + (errorMessage || 'Có lỗi xảy ra!'), false);
        window.history.replaceState({}, document.title, window.location.pathname + '?id=' + '<?php echo $id; ?>');
    }

    // Add input event listeners for real-time validation
    const inputs = form.querySelectorAll('input[type="text"]');
    inputs.forEach(input => {
        input.addEventListener('input', function() {
            validateForm();
        });
    });

    form.onsubmit = function(e) {
        if (!validateForm()) {
            e.preventDefault();
            return;
        }
        
        if (!submitConfirmed) {
            e.preventDefault();
            document.getElementById('modal-xac-nhan-update').style.display = 'flex';
        } else {
            submitConfirmed = false;
        }
    };

    document.getElementById('btn-huy-update').onclick = function() {
        document.getElementById('modal-xac-nhan-update').style.display = 'none';
    };

    document.getElementById('btn-xac-nhan-update').onclick = function() {
        document.getElementById('modal-xac-nhan-update').style.display = 'none';
        submitConfirmed = true;
        form.submit();
    };
});
</script>

</body>
</html>


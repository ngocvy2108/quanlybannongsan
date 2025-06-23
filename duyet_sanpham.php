<?php
session_start();
include 'config.php';
$vaiTro = isset($_SESSION['user']['VaiTro']) ? $_SESSION['user']['VaiTro'] : '';

// Xử lý xóa sản phẩm
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
                // Xóa dữ liệu liên quan từ bảng chitietdonhang
                if ($rowChiTietDonHang['total'] > 0) {
                    $conn->query("DELETE FROM chitietdonhang WHERE IdSanPham='$IdSanPham'");
                }
                // Xóa dữ liệu liên quan từ bảng giohang
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
    }
}

$sql = "SELECT * FROM sanpham WHERE TrangThaiDuyet = 0";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="css/giaodienkhachhang.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <title>Sản phẩm chờ duyệt</title>
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
        .back-btn {
            display: inline-block;
            margin-bottom: 20px;
            padding: 8px 15px;
            background-color: #2e7d32;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 500;
            transition: background 0.2s;
        }
        .back-btn:hover {
            background-color: #e65100;
        }
        h2 {
            text-align: center;
            color: #2e7d32;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background-color: #fff;
            box-shadow: 0 2px 8px rgba(46,125,50,0.10);
            border-radius: 12px;
            overflow: hidden;
        }
        th, td {
            padding: 12px;
            text-align: center;
            border-bottom: 1px solid #eee;
        }
        th {
            background-color: #f4f4f4;
            color: #2e7d32;
        }
        tr:hover {
            background-color: #f1f1f1;
        }
        img {
            max-width: 100px;
            border-radius: 5px;
        }
        .action-links a {
            margin: 0 5px;
            color: #2e7d32;
            text-decoration: none;
            font-weight: bold;
            cursor: pointer;
            transition: color 0.2s;
        }
        .action-links a:hover {
            color: #e65100;
            text-decoration: underline;
        }
        .message {
            margin: 10px 0;
            color: #2e7d32;
            font-weight: bold;
        }
        @media (max-width: 700px) {
            table, th, td { font-size: 13px; }
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
        left: 0; top: 0; right: 0; bottom: 0;
        background: rgba(0,0,0,0.35);
        justify-content: center;
        align-items: center;
        animation: fadeInModal 0.25s;
    }
    @keyframes fadeInModal {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    .modal-xoa[style*="display: flex"] {
        display: flex !important;
    }
    .modal-xoa-content {
        background: #fff;
        padding: 36px 32px 28px 32px;
        border-radius: 18px;
        box-shadow: 0 8px 32px rgba(229,81,0,0.18), 0 2px 16px rgba(46,125,50,0.10);
        min-width: 340px;
        max-width: 95vw;
        text-align: center;
        position: relative;
        animation: popInModal 0.25s;
    }
    @keyframes popInModal {
        from { transform: scale(0.92); opacity: 0; }
        to { transform: scale(1); opacity: 1; }
    }
    .modal-xoa-content h3 {
        color: #e53935;
        font-size: 1.35rem;
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }
    .modal-xoa-content h3 .modal-xoa-icon {
        font-size: 2.1rem;
        color: #e53935;
        margin-right: 6px;
    }
    .modal-xoa-content p {
        color: #444;
        font-size: 1.08rem;
        margin-bottom: 22px;
    }
    .modal-xoa-actions {
        display: flex;
        justify-content: center;
        gap: 18px;
        margin-top: 10px;
    }
    .nut {
        padding: 10px 32px;
        border-radius: 8px;
        border: none;
        font-size: 1.08rem;
        font-weight: 600;
        margin: 0 8px;
        cursor: pointer;
        transition: background 0.18s, box-shadow 0.18s, color 0.18s;
        box-shadow: 0 2px 8px rgba(46,125,50,0.08);
    }
    .nut-xoa {
        background: #e53935;
        color: #fff;
        border: 2px solid #e53935;
    }
    .nut-xoa:hover {
        background: #fff;
        color: #e53935;
        border: 2px solid #e53935;
        box-shadow: 0 4px 16px rgba(229,81,53,0.13);
    }
    .nut-sua {
        background: #2e7d32;
        color: #fff;
        border: 2px solid #2e7d32;
    }
    .nut-sua:hover {
        background: #fff;
        color: #2e7d32;
        border: 2px solid #2e7d32;
        box-shadow: 0 4px 16px rgba(46,125,50,0.13);
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

    <!-- Main Content -->
    <h2>Sản phẩm chờ duyệt</h2>

    <div class="message" id="message"></div>

    <table id="productTable">
        <tr>
            <th>Tên</th>
            <th>Giá</th>
            <th>Người bán</th>
            <th>Ảnh</th>
            <th>Thao tác</th>
        </tr>
        <?php while($row = $result->fetch_assoc()): ?>
        <tr id="row_<?= $row['IdSanPham'] ?>">
            <td><?= htmlspecialchars($row['TenSanPham']) ?></td>
            <td><?= number_format($row['Gia']) ?> VND</td>
            <td><?= htmlspecialchars($row['IdNguoiBan']) ?></td>
            <td><img src="img/<?= strtolower($row['IdSanPham']) ?>.jpg" alt="Ảnh sản phẩm"></td>
            <td class="action-links">
                <a onclick="duyetSanPham('<?= $row['IdSanPham'] ?>')">Duyệt</a> |
                <a href="#" onclick="xoaSanPham('<?= $row['IdSanPham'] ?>')" class="btn-xoa">Xóa</a>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>

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

    <!-- Modal xác nhận duyệt sản phẩm -->
    <div id="modal-xac-nhan-duyet-sanpham" class="modal-xoa" style="display:none;z-index:99999;">
        <div class="modal-xoa-content">
            <h3><span class="modal-xoa-icon" style="color:#2e7d32;">&#10003;</span>Xác nhận duyệt sản phẩm</h3>
            <p>Bạn có chắc chắn muốn duyệt sản phẩm này không?</p>
            <div class="modal-xoa-actions">
                <button id="btn-xac-nhan-duyet-sanpham" class="nut nut-sua">Duyệt</button>
                <button id="btn-huy-duyet-sanpham" class="nut nut-xoa">Hủy</button>
            </div>
        </div>
    </div>

    <!-- Toast notification -->
    <div id="toast-notification" style="position:fixed;z-index:99999;right:32px;bottom:32px;min-width:240px;display:none;padding:16px 28px;background:#fff;border-radius:8px;box-shadow:0 2px 16px rgba(46,125,50,0.13);font-size:1.08rem;font-weight:500;color:#333;align-items:center;gap:10px;"></div>

    <script>
    let idSanPhamCanXoa = null;
    let idSanPhamCanDuyet = null;

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

    function xoaSanPham(id) {
        event.preventDefault();
        idSanPhamCanXoa = id;
        document.getElementById('modal-xac-nhan-xoa-sanpham').style.display = 'flex';
    }

    document.getElementById('btn-huy-xoa-sanpham').onclick = function() {
        document.getElementById('modal-xac-nhan-xoa-sanpham').style.display = 'none';
        idSanPhamCanXoa = null;
    };

    document.getElementById('btn-xac-nhan-xoa-sanpham').onclick = function() {
        if (!idSanPhamCanXoa) return;
        document.getElementById('modal-xac-nhan-xoa-sanpham').style.display = 'none';
        fetch(`duyet_sanpham.php?delete=${encodeURIComponent(idSanPhamCanXoa)}&ajax=1`)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showToast('Xóa sản phẩm thành công!', true);
                    setTimeout(() => { location.reload(); }, 1200);
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
        fetch(`duyet_sanpham.php?delete=${encodeURIComponent(idSanPhamCanXoa)}&ajax=1&force=1`)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showToast('Đã xóa tất cả dữ liệu liên quan và sản phẩm!', true);
                    setTimeout(() => { location.reload(); }, 1200);
                } else {
                    showToast(data.message || 'Xóa thất bại!', false);
                }
            })
            .catch(() => {
                showToast('Có lỗi xảy ra khi xóa!', false);
            });
    };

    function duyetSanPham(id) {
        // Hiện modal xác nhận duyệt
        idSanPhamCanDuyet = id;
        document.getElementById('modal-xac-nhan-duyet-sanpham').style.display = 'flex';
    }

    document.getElementById('btn-huy-duyet-sanpham').onclick = function() {
        document.getElementById('modal-xac-nhan-duyet-sanpham').style.display = 'none';
        idSanPhamCanDuyet = null;
    };

    document.getElementById('btn-xac-nhan-duyet-sanpham').onclick = function() {
        if (!idSanPhamCanDuyet) return;
        document.getElementById('modal-xac-nhan-duyet-sanpham').style.display = 'none';
        // Gửi AJAX duyệt sản phẩm
        const xhr = new XMLHttpRequest();
        xhr.open("POST", "duyet_ajax.php", true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        xhr.onload = function () {
            if (xhr.status === 200 && xhr.responseText === 'success') {
                document.getElementById("row_" + idSanPhamCanDuyet).remove();
                showToast("✅ Duyệt sản phẩm thành công!", true);
            } else {
                showToast("❌ Có lỗi xảy ra khi duyệt: " + xhr.responseText, false);
            }
            idSanPhamCanDuyet = null;
        };
        xhr.send("id=" + encodeURIComponent(idSanPhamCanDuyet));
    };
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
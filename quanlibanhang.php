<?php
include("config.php");
session_start();
$vaiTro = isset($_SESSION['user']['VaiTro']) ? $_SESSION['user']['VaiTro'] : '';
// Xử lý xóa nếu có GET['delete']
if (isset($_GET['delete'])) {
    $IdNguoiBan = $_GET['delete'];
    if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
        $force = isset($_GET['force']) && $_GET['force'] == 1;
        
        // Kiểm tra xem có sản phẩm liên quan không
        $checkSanPham = $conn->query("SELECT COUNT(*) as total FROM sanpham WHERE IdNguoiBan='$IdNguoiBan'");
        $rowSanPham = $checkSanPham->fetch_assoc();
        
        // Kiểm tra xem có đơn hàng liên quan không
        $checkDonHang = $conn->query("SELECT COUNT(*) as total FROM donhang WHERE IdNguoiBan='$IdNguoiBan'");
        $rowDonHang = $checkDonHang->fetch_assoc();

        if (!$force && ($rowSanPham['total'] > 0 || $rowDonHang['total'] > 0)) {
            $message = "Không thể xóa người bán vì còn ";
            if ($rowSanPham['total'] > 0) {
                $message .= $rowSanPham['total'] . " sản phẩm";
                if ($rowDonHang['total'] > 0) {
                    $message .= " và ";
                }
            }
            if ($rowDonHang['total'] > 0) {
                $message .= $rowDonHang['total'] . " đơn hàng";
            }
            $message .= " liên quan!";
            $success = false;
        } else {
            try {
                $conn->begin_transaction();
                
                // Tạm thời tắt kiểm tra khóa ngoại
                $conn->query('SET FOREIGN_KEY_CHECKS=0');

                if ($force) {
                    // Xóa tất cả các bản ghi liên quan theo thứ tự
                    // 1. Xóa chi tiết đơn hàng
                    $conn->query("DELETE chitietdonhang FROM chitietdonhang 
                                INNER JOIN donhang ON chitietdonhang.IdDonHang = donhang.IdDonHang 
                                WHERE donhang.IdNguoiBan = '$IdNguoiBan'");
                    
                    // 2. Xóa thanh toán
                    $conn->query("DELETE thanhtoan FROM thanhtoan 
                                INNER JOIN donhang ON thanhtoan.IdDonHang = donhang.IdDonHang 
                                WHERE donhang.IdNguoiBan = '$IdNguoiBan'");
                    
                    // 3. Xóa đơn hàng
                    $conn->query("DELETE FROM donhang WHERE IdNguoiBan = '$IdNguoiBan'");
                    
                    // 4. Xóa sản phẩm
                    $conn->query("DELETE FROM sanpham WHERE IdNguoiBan = '$IdNguoiBan'");
                    
                    // 5. Xóa liên hệ
                    $conn->query("DELETE FROM lienhe WHERE IdNguoiBan = '$IdNguoiBan'");
                }

                // 6. Cuối cùng xóa người bán
                $success = $conn->query("DELETE FROM nguoiban WHERE IdNguoiBan = '$IdNguoiBan'");
                
                // Bật lại kiểm tra khóa ngoại
                $conn->query('SET FOREIGN_KEY_CHECKS=1');

                if ($success) {
                    $conn->commit();
                    $message = 'Xóa thành công';
                } else {
                    throw new Exception($conn->error);
                }
            } catch (Exception $e) {
                // Đảm bảo bật lại kiểm tra khóa ngoại ngay cả khi có lỗi
                $conn->query('SET FOREIGN_KEY_CHECKS=1');
                $conn->rollback();
                $success = false;
                $message = 'Lỗi: ' . $e->getMessage();
            }
        }
        
        header('Content-Type: application/json');
        echo json_encode(['success' => $success, 'message' => $message]);
        exit();
    } else {
        // Xử lý xóa không qua AJAX (nếu cần)
        try {
            $conn->begin_transaction();
            $conn->query('SET FOREIGN_KEY_CHECKS=0');
            $conn->query("DELETE FROM nguoiban WHERE IdNguoiBan='$IdNguoiBan'");
            $conn->query('SET FOREIGN_KEY_CHECKS=1');
            $conn->commit();
        } catch (Exception $e) {
            $conn->query('SET FOREIGN_KEY_CHECKS=1');
            $conn->rollback();
        }
    }
}

// Xử lý tìm kiếm
$search_result = null;
if (isset($_GET['search_id'])) {
    $search_id = $_GET['search_id'];
    $stmt = $conn->prepare("SELECT * FROM nguoiban WHERE IdNguoiBan = ?");
    $stmt->bind_param("s", $search_id);
    $stmt->execute();
    $search_result = $stmt->get_result();
} else {
    $sql_nguoiban = "SELECT * FROM nguoiban";
    $search_result = $conn->query($sql_nguoiban);
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="css/giaodienkhachhang.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <title>Thông tin người bán hàng</title>
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background-color: #f4f4f9;
        }
        h2 {
            text-align: center;
            color: #2e7d32;
            margin-top: 20px;
        }
        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #2e7d32;
            padding: 10px 20px;
            border-radius: 0 0 10px 10px;
            box-shadow: 0 2px 8px rgba(46,125,50,0.10);
        }
        .navbar a {
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 500;
            transition: background 0.2s;
        }
        .navbar a:hover {
            background-color: #388e3c;
        }
        .container {
            max-width: 1100px;
            margin: 40px auto;
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 4px 24px rgba(46,125,50,0.10);
            padding: 36px 32px 28px 32px;
        }
        table {
            width: 95%;
            margin: 20px auto;
            border-collapse: collapse;
            background-color: #fff;
            box-shadow: 0 4px 16px rgba(46,125,50,0.10);
            border-radius: 14px;
            overflow: hidden;
        }
        th, td {
            padding: 10px;
            border: 2px solid #eee;
            text-align: center;
        }
        .table-nguoiban tr:nth-child(even) td {
            background-color: #e8f5e9;
        }
        .table-nguoiban tr:nth-child(odd) td {
            background-color: #fff;
        }
        th {
            background-color: #e0f2f1;
            color: #2e7d32;
            font-weight: 600;
        }
        tr:hover { background-color: #f1f8e9; }
        .action-group {
            display: flex;
            gap: 10px;
            justify-content: flex-start;
            align-items: center;
        }
        .action-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 10px 20px;
            border: none;
            border-radius: 999px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(46,125,50,0.08);
            transition: background 0.2s, box-shadow 0.2s, color 0.2s;
            outline: none;
        }
        .edit-btn {
            background: #2e7d32;
            color: #fff;
            border: 2px solid #2e7d32;
        }
        .edit-btn:hover {
            background: #1b5e20;
            color: #fff;
            box-shadow: 0 4px 16px rgba(46,125,50,0.15);
        }
        .delete-btn {
            background: #e65100;
            color: #fff;
            border: 2px solid #e65100;
        }
        .delete-btn:hover {
            background: #bf360c;
            color: #fff;
            box-shadow: 0 4px 16px rgba(230,81,0,0.15);
        }
        .action-btn i {
            font-size: 17px;
        }
        @media (max-width: 1100px) {
            .container { margin: 20px; padding: 12px; }
            table, th, td { font-size: 14px; }
            .action-btn { padding: 8px 12px; font-size: 14px; }
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
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
                <form method="GET" action="quanlibanhang.php">
                    <input type="text" name="search_id" placeholder="Nhập ID người bán..." required>
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
        <img src="img/banner.jpg" alt="Banner" class="banner-image">
    </div>
    
<h2>THÔNG TIN NGƯỜI BÁN HÀNG</h2>

<table class="table-nguoiban">
    <tr>
        <th>ID Người bán</th>
        <th>Tên người bán</th>
        <th>Địa chỉ</th>
        <th>Số điện thoại</th>
        <th>Email</th>
        <th>Mô tả gian hàng</th>
        <th>Ngày tham gia</th>
        <th>ID Tài khoản</th>
        <th>ID Người quản lý</th>
        <th>Thao tác</th>
    </tr>
    <?php
    if ($search_result && $search_result->num_rows > 0) {
        while ($row = $search_result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>".$row["IdNguoiBan"]."</td>";
            echo "<td>".$row["TenNguoiBan"]."</td>";
            echo "<td>".$row["DiaChi"]."</td>";
            echo "<td>".$row["SDT"]."</td>";
            echo "<td>".$row["Email"]."</td>";
            echo "<td>".$row["MoTaGianHang"]."</td>";
            echo "<td>".$row["NgayThamGia"]."</td>";
            echo "<td>".$row["IdTaiKhoan"]."</td>";
            echo "<td>".$row["IdNguoiQuanLy"]."</td>";
            echo "<td><div class='action-group'>
                <a class='action-btn edit-btn' href='suanguoiban.php?id=".$row["IdNguoiBan"]."'><i class='bi bi-pencil-square'></i> Sửa</a>
                <a class='action-btn delete-btn btn-xoa-nguoiban' href='#' data-id='".$row["IdNguoiBan"]."'><i class='bi bi-trash'></i> Xóa</a>
                </div></td>";
            echo "</tr>";
        }
    } else {
        echo "<tr><td colspan='10'>Không tìm thấy người bán nào.</td></tr>";
    }
    ?>
</table>
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

    <!-- Modal xác nhận xóa người bán -->
    <div id="modal-xac-nhan-xoa-nguoiban" class="modal-xoa" style="display:none;z-index:99999;">
      <div class="modal-xoa-content">
        <h3><span class="modal-xoa-icon">&#9888;</span>Xác nhận xóa người bán</h3>
        <p>Bạn có chắc chắn muốn xóa người bán này không?<br><span style="color:#e53935;font-size:0.98rem;">Hành động này không thể hoàn tác.</span></p>
        <div class="modal-xoa-actions">
          <button id="btn-xac-nhan-xoa-nguoiban" class="nut nut-xoa">Xóa</button>
          <button id="btn-huy-xoa-nguoiban" class="nut nut-sua">Hủy</button>
        </div>
      </div>
    </div>
    <!-- Modal xác nhận xóa tất cả liên quan -->
    <div id="modal-xoa-lien-quan" class="modal-xoa" style="display:none;z-index:99999;">
      <div class="modal-xoa-content">
        <h3><span class="modal-xoa-icon">&#9888;</span>Xóa tất cả dữ liệu liên quan?</h3>
        <p id="modal-xoa-lien-quan-msg">Người bán này còn liên quan đến dữ liệu khác.<br>Bạn có muốn xóa hết tất cả dữ liệu liên quan và xóa người bán không?</p>
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
    document.addEventListener('DOMContentLoaded', function() {
      let idNguoiBanCanXoa = null;
      function showToast(msg, success = true) {
        console.log('Showing toast:', msg, success); // Debug log
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
      
      console.log('Setting up delete buttons...'); // Debug log
      document.querySelectorAll('.btn-xoa-nguoiban').forEach(btn => {
        console.log('Found delete button:', btn); // Debug log
        btn.onclick = function(e) {
          e.preventDefault();
          idNguoiBanCanXoa = this.getAttribute('data-id');
          console.log('Delete button clicked, id:', idNguoiBanCanXoa); // Debug log
          document.getElementById('modal-xac-nhan-xoa-nguoiban').style.display = 'flex';
        };
      });

      document.getElementById('btn-huy-xoa-nguoiban').onclick = function() {
        console.log('Cancel button clicked'); // Debug log
        document.getElementById('modal-xac-nhan-xoa-nguoiban').style.display = 'none';
        idNguoiBanCanXoa = null;
      };

      document.getElementById('btn-xac-nhan-xoa-nguoiban').onclick = function() {
        console.log('Confirm delete clicked, id:', idNguoiBanCanXoa); // Debug log
        if (!idNguoiBanCanXoa) {
          console.log('No id to delete!'); // Debug log
          return;
        }
        document.getElementById('modal-xac-nhan-xoa-nguoiban').style.display = 'none';
        console.log('Sending delete request...'); // Debug log
        fetch(`quanlibanhang.php?delete=${encodeURIComponent(idNguoiBanCanXoa)}&ajax=1`)
          .then(res => {
            console.log('Response received:', res); // Debug log
            return res.json();
          })
          .then(data => {
            console.log('Delete response data:', data); // Debug log
            if (data.success) {
              showToast('Xóa người bán thành công!', true);
              setTimeout(() => { location.reload(); }, 1200);
            } else {
              // Nếu lỗi liên quan đơn hàng/sản phẩm thì hiện modal xác nhận xóa tất cả
              if (data.message && (data.message.includes('đơn hàng liên quan') || data.message.includes('sản phẩm liên quan'))) {
                showModalXoaLienQuan(data.message);
              } else {
                showToast(data.message || 'Xóa thất bại!', false);
              }
            }
          })
          .catch((error) => {
            console.error('Delete error:', error); // Debug log
            showToast('Có lỗi xảy ra khi xóa!', false);
          });
      };
      // Xử lý modal xóa liên quan
      document.getElementById('btn-huy-xoa-lien-quan').onclick = function() {
        document.getElementById('modal-xoa-lien-quan').style.display = 'none';
        idNguoiBanCanXoa = null;
      };
      document.getElementById('btn-xac-nhan-xoa-lien-quan').onclick = function() {
        if (!idNguoiBanCanXoa) return;
        document.getElementById('modal-xoa-lien-quan').style.display = 'none';
        fetch(`quanlibanhang.php?delete=${encodeURIComponent(idNguoiBanCanXoa)}&ajax=1&force=1`)
          .then(res => res.json())
          .then(data => {
            if (data.success) {
              showToast('Đã xóa tất cả dữ liệu liên quan và người bán!', true);
              setTimeout(() => { location.reload(); }, 1200);
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
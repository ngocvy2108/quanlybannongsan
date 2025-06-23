<?php
session_start();
date_default_timezone_set('Asia/Ho_Chi_Minh'); // Thiết lập múi giờ Việt Nam
$vaiTro = isset($_SESSION['user']['VaiTro']) ? $_SESSION['user']['VaiTro'] : '';
if (!isset($_SESSION['user']) || ($_SESSION['user']['VaiTro'] !== 'Quản lý' && $_SESSION['user']['VaiTro'] !== 'Bán hàng')) {
    header("Location: index.php");
    exit();
}
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "csdldoanchuyennganh";

$conn = new mysqli($servername, $username, $password, $dbname);
$conn->set_charset("utf8mb4");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get current date for default values
$currentDate = date('Y-m-d');
$currentMonth = date('Y-m');
$currentYear = date('Y');

// Get selected period from request
$period = isset($_GET['period']) ? $_GET['period'] : 'month';
$selectedDate = isset($_GET['date']) ? $_GET['date'] : $currentDate;
$selectedMonth = isset($_GET['month']) ? $_GET['month'] : $currentMonth;
$selectedYear = isset($_GET['year']) ? $_GET['year'] : $currentYear;

// Function to format currency
function formatCurrency($amount) {
    return number_format($amount, 0, ',', '.') . '₫';
}

// Get statistics based on user role
$isManager = $_SESSION['user']['VaiTro'] === 'Quản lý';
$userId = $_SESSION['user']['IdNguoiBan'] ?? null;

// Lấy danh sách người bán cho dropdown nếu là quản lý
if ($isManager) {
    $sellerList = [];
    $result_sellers = $conn->query("SELECT IdNguoiBan, TenNguoiBan FROM nguoiban");
    while ($row = $result_sellers->fetch_assoc()) {
        $sellerList[] = $row;
    }
    $userId = isset($_GET['seller']) ? $_GET['seller'] : ($sellerList[0]['IdNguoiBan'] ?? '');
} else {
    $userId = $_SESSION['user']['IdNguoiBan'] ?? null;
    if (empty($userId)) {
        die('Không xác định được người bán!');
    }
    // Lấy thông tin người bán hiện tại
    $result_current_seller = $conn->query("SELECT IdNguoiBan, TenNguoiBan FROM nguoiban WHERE IdNguoiBan = '$userId'");
    $currentSeller = $result_current_seller->fetch_assoc();
    $sellerList = [$currentSeller];
}

// Lấy thống kê so sánh giữa các người bán
$sql_comparison = "";
if ($isManager && isset($_GET['view_type']) && $_GET['view_type'] === 'comparison') {
    // Nếu là quản lý và đang xem chế độ so sánh, lấy tất cả người bán
    $sql_comparison = "SELECT 
            nb.IdNguoiBan,
            nb.TenNguoiBan,
            COUNT(d.IdDonHang) as total_orders,
            COALESCE(SUM(d.TongGiaTri), 0) as total_revenue,
            COALESCE(COUNT(DISTINCT d.IdKhachHang), 0) as total_customers
        FROM nguoiban nb
        LEFT JOIN donhang d ON nb.IdNguoiBan = d.IdNguoiBan AND YEAR(d.NgayDatHang) = '$selectedYear'
        GROUP BY nb.IdNguoiBan, nb.TenNguoiBan
        ORDER BY total_revenue DESC";
} else {
    // Nếu không phải chế độ so sánh hoặc không phải quản lý, chỉ lấy người bán được chọn
    $sql_comparison = "SELECT 
            nb.IdNguoiBan,
            nb.TenNguoiBan,
            COUNT(d.IdDonHang) as total_orders,
            COALESCE(SUM(d.TongGiaTri), 0) as total_revenue,
            COALESCE(COUNT(DISTINCT d.IdKhachHang), 0) as total_customers
        FROM nguoiban nb
        LEFT JOIN donhang d ON nb.IdNguoiBan = d.IdNguoiBan AND YEAR(d.NgayDatHang) = '$selectedYear'
        WHERE nb.IdNguoiBan = '$userId'
        GROUP BY nb.IdNguoiBan, nb.TenNguoiBan
        ORDER BY total_revenue DESC";
}
$result_comparison = $conn->query($sql_comparison);
if (!$result_comparison) {
    die('Lỗi SQL so sánh: ' . $conn->error . '<br>Query: ' . $sql_comparison);
}

// Thêm truy vấn để lấy top sản phẩm bán chạy cho người bán
$sql_seller_products = "";
if ($period === 'day') {
    if ($isManager && isset($_GET['view_type']) && $_GET['view_type'] === 'comparison') {
        $sql_seller_products = "SELECT 
            nb.IdNguoiBan,
            nb.TenNguoiBan,
            sp.TenSanPham,
            SUM(ct.SoLuong) as total_sold,
            SUM(ct.SoLuong * ct.Gia) as total_revenue
        FROM nguoiban nb
        LEFT JOIN donhang d ON nb.IdNguoiBan = d.IdNguoiBan AND d.NgayDatHang = '$selectedDate'
        LEFT JOIN chitietdonhang ct ON d.IdDonHang = ct.IdDonHang
        LEFT JOIN sanpham sp ON ct.IdSanPham = sp.IdSanPham
        GROUP BY nb.IdNguoiBan, nb.TenNguoiBan, sp.IdSanPham, sp.TenSanPham
        HAVING total_sold > 0
        ORDER BY nb.IdNguoiBan, total_sold DESC";
    } else {
        $sql_seller_products = "SELECT 
            nb.IdNguoiBan,
            nb.TenNguoiBan,
            sp.TenSanPham,
            SUM(ct.SoLuong) as total_sold,
            SUM(ct.SoLuong * ct.Gia) as total_revenue
        FROM nguoiban nb
        LEFT JOIN donhang d ON nb.IdNguoiBan = d.IdNguoiBan AND d.NgayDatHang = '$selectedDate'
        LEFT JOIN chitietdonhang ct ON d.IdDonHang = ct.IdDonHang
        LEFT JOIN sanpham sp ON ct.IdSanPham = sp.IdSanPham
        WHERE nb.IdNguoiBan = '$userId'
        GROUP BY nb.IdNguoiBan, nb.TenNguoiBan, sp.IdSanPham, sp.TenSanPham
        HAVING total_sold > 0
        ORDER BY total_sold DESC";
    }
} elseif ($period === 'month') {
    if ($isManager && isset($_GET['view_type']) && $_GET['view_type'] === 'comparison') {
        $sql_seller_products = "SELECT 
            nb.IdNguoiBan,
            nb.TenNguoiBan,
            sp.TenSanPham,
            SUM(ct.SoLuong) as total_sold,
            SUM(ct.SoLuong * ct.Gia) as total_revenue
        FROM nguoiban nb
        LEFT JOIN donhang d ON nb.IdNguoiBan = d.IdNguoiBan AND DATE_FORMAT(d.NgayDatHang, '%Y-%m') = '$selectedMonth'
        LEFT JOIN chitietdonhang ct ON d.IdDonHang = ct.IdDonHang
        LEFT JOIN sanpham sp ON ct.IdSanPham = sp.IdSanPham
        GROUP BY nb.IdNguoiBan, nb.TenNguoiBan, sp.IdSanPham, sp.TenSanPham
        HAVING total_sold > 0
        ORDER BY nb.IdNguoiBan, total_sold DESC";
    } else {
        $sql_seller_products = "SELECT 
            nb.IdNguoiBan,
            nb.TenNguoiBan,
            sp.TenSanPham,
            SUM(ct.SoLuong) as total_sold,
            SUM(ct.SoLuong * ct.Gia) as total_revenue
        FROM nguoiban nb
        LEFT JOIN donhang d ON nb.IdNguoiBan = d.IdNguoiBan AND DATE_FORMAT(d.NgayDatHang, '%Y-%m') = '$selectedMonth'
        LEFT JOIN chitietdonhang ct ON d.IdDonHang = ct.IdDonHang
        LEFT JOIN sanpham sp ON ct.IdSanPham = sp.IdSanPham
        WHERE nb.IdNguoiBan = '$userId'
        GROUP BY nb.IdNguoiBan, nb.TenNguoiBan, sp.IdSanPham, sp.TenSanPham
        HAVING total_sold > 0
        ORDER BY total_sold DESC";
    }
} else {
    if ($isManager && isset($_GET['view_type']) && $_GET['view_type'] === 'comparison') {
        $sql_seller_products = "SELECT 
            nb.IdNguoiBan,
            nb.TenNguoiBan,
            sp.TenSanPham,
            SUM(ct.SoLuong) as total_sold,
            SUM(ct.SoLuong * ct.Gia) as total_revenue
        FROM nguoiban nb
        LEFT JOIN donhang d ON nb.IdNguoiBan = d.IdNguoiBan AND YEAR(d.NgayDatHang) = '$selectedYear'
        LEFT JOIN chitietdonhang ct ON d.IdDonHang = ct.IdDonHang
        LEFT JOIN sanpham sp ON ct.IdSanPham = sp.IdSanPham
        GROUP BY nb.IdNguoiBan, nb.TenNguoiBan, sp.IdSanPham, sp.TenSanPham
        HAVING total_sold > 0
        ORDER BY nb.IdNguoiBan, total_sold DESC";
    } else {
        $sql_seller_products = "SELECT 
            nb.IdNguoiBan,
            nb.TenNguoiBan,
            sp.TenSanPham,
            SUM(ct.SoLuong) as total_sold,
            SUM(ct.SoLuong * ct.Gia) as total_revenue
        FROM nguoiban nb
        LEFT JOIN donhang d ON nb.IdNguoiBan = d.IdNguoiBan AND YEAR(d.NgayDatHang) = '$selectedYear'
        LEFT JOIN chitietdonhang ct ON d.IdDonHang = ct.IdDonHang
        LEFT JOIN sanpham sp ON ct.IdSanPham = sp.IdSanPham
        WHERE nb.IdNguoiBan = '$userId'
        GROUP BY nb.IdNguoiBan, nb.TenNguoiBan, sp.IdSanPham, sp.TenSanPham
        HAVING total_sold > 0
        ORDER BY total_sold DESC";
    }
}

// Initialize seller_products array
$seller_products = array();

// Execute the query and organize data
$result_seller_products = $conn->query($sql_seller_products);
if (!$result_seller_products) {
    die('Lỗi SQL sản phẩm người bán: ' . $conn->error . '<br>Query: ' . $sql_seller_products);
}

// Organize products by seller
while ($row = $result_seller_products->fetch_assoc()) {
    $sellerId = $row['IdNguoiBan'];
    if (!isset($seller_products[$sellerId])) {
        $seller_products[$sellerId] = array();
    }
    if (count($seller_products[$sellerId]) < 3) { // Only keep top 3 products
        $seller_products[$sellerId][] = array(
            'TenNguoiBan' => $row['TenNguoiBan'],
            'TenSanPham' => $row['TenSanPham'],
            'total_sold' => $row['total_sold'],
            'total_revenue' => $row['total_revenue']
        );
    }
}

// WHERE cho tất cả truy vấn
$dateCondition = '';
if ($period === 'day') {
    $dateCondition = "AND d.NgayDatHang = '$selectedDate'";
} elseif ($period === 'month') {
    $dateCondition = "AND DATE_FORMAT(d.NgayDatHang, '%Y-%m') = '$selectedMonth'";
} elseif ($period === 'year') {
    $dateCondition = "AND YEAR(d.NgayDatHang) = '$selectedYear'";
}
$whereClause = "";
if (!empty($userId)) {
    $whereClause = "WHERE d.IdNguoiBan = '$userId' $dateCondition";
}

// Get total orders
$sql_orders = "SELECT COUNT(*) as total_orders FROM donhang d $whereClause";
$result_orders = $conn->query($sql_orders);
if (!$result_orders) {
    die('Lỗi SQL đơn hàng: ' . $conn->error . '<br>Query: ' . $sql_orders);
}
$totalOrders = $result_orders->fetch_assoc()['total_orders'];

// Get total revenue
$sql_revenue = "SELECT SUM(d.TongGiaTri) as total_revenue FROM donhang d $whereClause";
$result_revenue = $conn->query($sql_revenue);
if (!$result_revenue) {
    die('Lỗi SQL doanh thu: ' . $conn->error . '<br>Query: ' . $sql_revenue);
}
$totalRevenue = $result_revenue->fetch_assoc()['total_revenue'] ?? 0;

// Get top selling products
$sql_top_products = "SELECT sp.TenSanPham, SUM(ct.SoLuong) as total_sold, SUM(ct.SoLuong * ct.Gia) as total_revenue
                     FROM chitietdonhang ct
                     JOIN sanpham sp ON ct.IdSanPham = sp.IdSanPham
                     JOIN donhang d ON ct.IdDonHang = d.IdDonHang
                     $whereClause
                     GROUP BY sp.IdSanPham
                     ORDER BY total_sold DESC
                     LIMIT 5";
$result_top_products = $conn->query($sql_top_products);
if (!$result_top_products) {
    die('Lỗi SQL sản phẩm bán chạy: ' . $conn->error . '<br>Query: ' . $sql_top_products);
}

// Get monthly revenue data for chart
if ($period === 'year') {
    $sql_chart_revenue = "SELECT DATE_FORMAT(NgayDatHang, '%Y-%m') as label, SUM(TongGiaTri) as revenue
                        FROM donhang d
                        $whereClause
                        GROUP BY DATE_FORMAT(NgayDatHang, '%Y-%m')
                        ORDER BY label DESC
                        LIMIT 12";
    $chartLabel = 'Tháng';
    $chartTitle = 'Doanh thu theo tháng';
} else {
    $sql_chart_revenue = "SELECT DATE_FORMAT(NgayDatHang, '%Y-%m-%d') as label, SUM(TongGiaTri) as revenue
                        FROM donhang d
                        $whereClause
                        GROUP BY DATE_FORMAT(NgayDatHang, '%Y-%m-%d')
                        ORDER BY label DESC
                        LIMIT 31";
    $chartLabel = 'Ngày';
    $chartTitle = 'Doanh thu theo ngày';
}
$result_chart_revenue = $conn->query($sql_chart_revenue);
if (!$result_chart_revenue) {
    die('Lỗi SQL doanh thu cho biểu đồ: ' . $conn->error . '<br>Query: ' . $sql_chart_revenue);
}
$chartData = [];
while ($row = $result_chart_revenue->fetch_assoc()) {
    $chartData[] = [
        'label' => $row['label'],
        'revenue' => $row['revenue']
    ];
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Báo cáo thống kê</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/giaodienkhachhang.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .stat-icon {
            font-size: 2em;
            margin-bottom: 10px;
            color: #4CAF50;
        }
        .stat-value {
            font-size: 1.5em;
            font-weight: bold;
            color: #333;
        }
        .stat-label {
            color: #666;
            margin-top: 5px;
        }
        .chart-container {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .period-selector {
            margin-bottom: 20px;
        }
        .table-container {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .print-report {
            background: #fff;
            border-radius: 12px;
            padding: 32px 32px 24px 32px;
            margin: 0 auto;
            max-width: 900px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            font-family: 'Segoe UI', Arial, sans-serif;
        }
        .print-header {
            display: flex;
            align-items: center;
            border-bottom: 2px solid #4CAF50;
            margin-bottom: 24px;
            padding-bottom: 12px;
        }
        .print-logo {
            width: 70px;
            margin-right: 24px;
        }
        .print-title {
            font-size: 2.2em;
            font-weight: bold;
            color: #388e3c;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        .print-info {
            margin-bottom: 18px;
            font-size: 1.1em;
        }
        .print-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 24px;
        }
        .print-table th, .print-table td {
            border: 1px solid #bbb;
            padding: 10px 12px;
            text-align: center;
        }
        .print-table th {
            background: #e8f5e9;
            color: #2e7d32;
            font-weight: bold;
        }
        .print-section-title {
            font-size: 1.3em;
            font-weight: 600;
            color: #388e3c;
            margin: 18px 0 10px 0;
        }
        .print-footer {
            margin-top: 32px;
            display: flex;
            justify-content: space-between;
            font-size: 1em;
        }
        .print-sign {
            text-align: right;
        }
        @media print {
            body * { visibility: hidden !important; }
            .print-active, .print-active * { visibility: visible !important; }
            .print-active { position: absolute; left: 0; top: 0; width: 100vw; background: white; z-index: 9999; box-shadow: none; }
            .btn, .period-selector, form, .d-flex, #printType, .container > h1 { display: none !important; }
            .print-report { box-shadow: none; margin: 0; padding: 0; }
        }
        .report-title {
            font-size: 2rem;
            font-weight: 700;
            color: #2e7d32;
            margin-bottom: 18px;
            margin-top: 18px;
            letter-spacing: 0.5px;
            text-align: center;
            position: relative;
            padding: 18px 0 12px 0;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
            text-transform: none;
        }
        .report-title:after {
            content: '';
            display: block;
            width: 48px;
            height: 3px;
            background: #2e7d32;
            border-radius: 2px;
            margin: 10px auto 0 auto;
        }
        .report-filters-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            gap: 24px;
            flex-wrap: wrap;
            margin-bottom: 28px;
            max-width: 900px;
            margin-left: auto;
            margin-right: auto;
            padding-left: 16px;
            padding-right: 16px;
        }
        .report-filters-left {
            display: flex;
            gap: 14px;
            align-items: center;
            flex-wrap: wrap;
        }
        .report-filters-right {
            display: flex;
            gap: 14px;
            align-items: center;
            flex-wrap: wrap;
        }
        @media (max-width: 900px) {
            .report-filters-row { flex-direction: column; align-items: stretch; gap: 10px; padding-left: 4px; padding-right: 4px; }
            .report-filters-left, .report-filters-right { flex-direction: column; align-items: stretch; gap: 8px; }
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
        .form-check-inline {
            margin-right: 1rem;
        }
        .form-check-input {
            margin-top: 0.2rem;
        }
        .form-check-label {
            margin-left: 0.25rem;
            font-weight: normal;
        }
        .view-type-selector {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-left: 20px;
            padding: 5px 0;
        }
        .view-type-selector label {
            cursor: pointer;
        }
        .view-type-selector input[type="radio"]:checked + label {
            color: #4CAF50;
            font-weight: 500;
        }
        .seller-products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .seller-product-card {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .seller-product-card .seller-name {
            color: #4CAF50;
            font-size: 1.1rem;
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 2px solid #e9ecef;
        }
        .seller-product-card table {
            width: 100%;
            margin-bottom: 0;
        }
        .seller-product-card th {
            background: #4CAF50;
            color: white;
            padding: 8px;
            font-weight: 500;
        }
        .seller-product-card td {
            padding: 8px;
            border-bottom: 1px solid #e9ecef;
        }
        .seller-product-card tr:last-child td {
            border-bottom: none;
        }
        .text-muted {
            color: #6c757d;
            font-style: italic;
            text-align: center;
            margin: 10px 0;
        }
        .seller-section {
            margin-bottom: 30px;
        }
        .seller-section:last-child {
            margin-bottom: 0;
        }
        .seller-name {
            color: #4CAF50;
            font-size: 1.1rem;
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 2px solid #e9ecef;
            text-align: left;
        }
        .print-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            background: white;
            border: 1px solid #dee2e6;
        }
        .print-table th {
            background: #4CAF50;
            color: white;
            padding: 12px;
            font-weight: 500;
            border: 1px solid #dee2e6;
            text-align: center;
        }
        .print-table td {
            padding: 12px;
            border: 1px solid #dee2e6;
            text-align: center;
        }
        .print-table tr:last-child td {
            border-bottom: 1px solid #dee2e6;
        }
        .text-muted {
            color: #6c757d;
            font-style: italic;
            text-align: center;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 4px;
            margin: 0;
        }
        .print-section-title {
            text-align: left;
            color: #4CAF50;
            margin: 20px 0;
            font-weight: 500;
        }

        /* Print-specific styles */
        @media print {
            .print-table th {
                background-color: #4CAF50 !important;
                color: white !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            
            .seller-name, .print-section-title {
                color: #4CAF50 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .print-table {
                border-color: #dee2e6 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .print-table td, .print-table th {
                border-color: #dee2e6 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>
<body class="bg-light">
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
    
    <div class="container py-4">
        <div class="report-title">Báo cáo thống kê</div>
        
                    <?php if ($isManager): ?>
            <div class="report-filters-row">
                <div class="report-filters-left" style="width: 100%; max-width: 900px; margin: 0 auto 20px auto;">
                    <form method="GET" style="width: 100%; display: flex; justify-content: center; align-items: center; gap: 14px;">
                        <div id="sellerSelectContainer" style="display: flex; align-items: center; gap: 14px;" <?php echo (isset($_GET['view_type']) && $_GET['view_type'] === 'comparison') ? 'class="disabled-select"' : ''; ?>>
                            <label for="seller" style="margin: 0; font-weight: 500; min-width: 120px;">Chọn người bán:</label>
                            <div style="min-width: 200px;">
                                <select name="seller" id="seller" class="form-select" onchange="this.form.submit()" <?php echo (isset($_GET['view_type']) && $_GET['view_type'] === 'comparison') ? 'disabled' : ''; ?>>
                                    <?php if (isset($_GET['view_type']) && $_GET['view_type'] === 'comparison'): ?>
                                        <option value="">Tất cả người bán</option>
                                    <?php else: ?>
                                        <?php foreach ($sellerList as $seller): ?>
                                            <option value="<?php echo $seller['IdNguoiBan']; ?>" <?php if ($seller['IdNguoiBan'] == $userId) echo 'selected'; ?>>
                                                <?php echo htmlspecialchars($seller['TenNguoiBan']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                        </div>
                        <div style="display: flex; align-items: center; gap: 14px; margin-left: 20px;">
                            <label style="margin: 0; font-weight: 500;">Kiểu xem:</label>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="view_type" id="normal_view" value="normal" <?php echo (!isset($_GET['view_type']) || $_GET['view_type'] === 'normal') ? 'checked' : ''; ?> onchange="handleViewTypeChange(this)">
                                <label class="form-check-label" for="normal_view">Báo cáo chi tiết</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="view_type" id="comparison_view" value="comparison" <?php echo (isset($_GET['view_type']) && $_GET['view_type'] === 'comparison') ? 'checked' : ''; ?> onchange="handleViewTypeChange(this)">
                                <label class="form-check-label" for="comparison_view">So sánh người bán</label>
                            </div>
                        </div>
                        <style>
                            .disabled-select {
                                opacity: 0.6;
                                pointer-events: none;
                            }
                        </style>
                        <script>
                            function handleViewTypeChange(radio) {
                                var sellerSelect = document.getElementById('sellerSelectContainer');
                                var sellerDropdown = document.getElementById('seller');
                                
                                if (radio.value === 'comparison') {
                                    sellerSelect.classList.add('disabled-select');
                                    sellerDropdown.disabled = true;
                                    sellerDropdown.innerHTML = '<option value="">Tất cả người bán</option>';
                                } else {
                                    sellerSelect.classList.remove('disabled-select');
                                    sellerDropdown.disabled = false;
                                }
                                
                                radio.form.submit();
                            }
                        </script>
                        <input type="hidden" name="period" value="<?php echo htmlspecialchars($period); ?>">
                        <?php if ($period === 'day'): ?>
                            <input type="hidden" name="date" value="<?php echo htmlspecialchars($selectedDate); ?>">
                        <?php elseif ($period === 'month'): ?>
                            <input type="hidden" name="month" value="<?php echo htmlspecialchars($selectedMonth); ?>">
                        <?php else: ?>
                            <input type="hidden" name="year" value="<?php echo htmlspecialchars($selectedYear); ?>">
                        <?php endif; ?>
                    </form>
                </div>
            </div>
            <?php endif; ?>

        <div class="report-filters-row">
            <div class="report-filters-left">
                <form method="GET" style="margin:0;display:flex;align-items:center;gap:14px;flex-wrap:nowrap;">
                    <?php if (isset($_GET['view_type'])): ?>
                    <input type="hidden" name="view_type" value="<?php echo htmlspecialchars($_GET['view_type']); ?>">
                    <?php endif; ?>
                    <?php if (isset($_GET['seller'])): ?>
                    <input type="hidden" name="seller" value="<?php echo htmlspecialchars($_GET['seller']); ?>">
                    <?php endif; ?>
                    <label style="margin: 0; font-weight: 500; white-space: nowrap;">Hình thức:</label>
                    <div style="display: flex; align-items: center; gap: 14px;">
                        <select name="period" class="form-select" style="min-width: 120px;" onchange="this.form.submit()">
                            <option value="day" <?php echo $period === 'day' ? 'selected' : ''; ?>>Theo ngày</option>
                            <option value="month" <?php echo $period === 'month' ? 'selected' : ''; ?>>Theo tháng</option>
                            <option value="year" <?php echo $period === 'year' ? 'selected' : ''; ?>>Theo năm</option>
                        </select>
                        <?php if ($period === 'day'): ?>
                        <input type="date" name="date" class="form-control" value="<?php echo $selectedDate; ?>" onchange="this.form.submit()">
                        <?php elseif ($period === 'month'): ?>
                        <input type="month" name="month" class="form-control" value="<?php echo $selectedMonth; ?>" onchange="this.form.submit()">
                        <?php else: ?>
                        <input type="number" name="year" class="form-control" value="<?php echo $selectedYear; ?>" min="2000" max="2100" onchange="this.form.submit()">
                        <?php endif; ?>
                    </div>
                </form>
            </div>
            <div class="report-filters-right">
                <select id="printType" class="form-select w-auto">
                    <option value="all">Tất cả</option>
                    <option value="orders">Số lượng đơn hàng</option>
                    <option value="revenue">Doanh thu</option>
                    <option value="products">Sản phẩm bán chạy</option>
                    <?php if ($isManager): ?>
                    <option value="comparison">So sánh người bán</option>
                    <?php endif; ?>
                </select>
                <button class="btn btn-success" type="button" onclick="printSelectedReport()">
                    <i class="fas fa-print"></i> In báo cáo
                </button>
            </div>
            <script>
            function printSelectedReport() {
                var type = document.getElementById('printType').value;
                document.querySelectorAll('.print-section').forEach(function(el) {
                    el.classList.remove('print-active');
                });
                
                if (type === 'all') {
                    document.getElementById('print-all').classList.add('print-active');
                } else if (type === 'orders') {
                    document.getElementById('print-orders').classList.add('print-active');
                } else if (type === 'revenue') {
                    document.getElementById('print-revenue').classList.add('print-active');
                } else if (type === 'products') {
                    document.getElementById('print-products').classList.add('print-active');
                } else if (type === 'comparison' && <?php echo $isManager ? 'true' : 'false'; ?>) {
                    document.getElementById('print-comparison').classList.add('print-active');
                }
                
                // Đảm bảo biểu đồ được vẽ lại trước khi in
                setTimeout(function() {
                    if (window.revenueChart) {
                        window.revenueChart.resize();
                    }
                    window.print();
                }, 100);
            }
            </script>
        </div>

        <div class="printable">
            <?php 
            $viewType = $isManager ? (isset($_GET['view_type']) ? $_GET['view_type'] : 'normal') : 'normal';
            if ($viewType === 'normal' || !$isManager):
            ?>
            <div class="print-report print-section" id="print-all">
                <div class="print-header">
                    <img src="img/logo.jpg" class="print-logo" alt="Logo">
                    <div class="print-title">BÁO CÁO THỐNG KÊ</div>
                </div>
                <div class="print-info">
                    <strong>Thời gian:</strong> 
                    <?php
                        if ($period === 'day') echo date('d/m/Y', strtotime($selectedDate));
                        elseif ($period === 'month') echo date('m/Y', strtotime($selectedMonth.'-01'));
                        else echo $selectedYear;
                    ?>
                    <br>
                    <strong><?php echo $isManager ? 'Người bán' : 'Tài khoản'; ?>:</strong> 
                    <?php
                        if ($isManager) {
                            foreach ($sellerList as $seller) {
                                if ($seller['IdNguoiBan'] == $userId) echo htmlspecialchars($seller['TenNguoiBan']);
                            }
                        } else {
                            echo htmlspecialchars($_SESSION['user']['TenDangNhap'] ?? '');
                        }
                    ?>
                </div>
                <div class="print-section-title">1. Số lượng đơn hàng</div>
                <table class="print-table">
                    <tr>
                        <th>Tổng số đơn hàng</th>
                    </tr>
                    <tr>
                        <td><?php echo number_format($totalOrders); ?></td>
                    </tr>
                </table>
                <div class="print-section-title">2. Doanh thu</div>
                <table class="print-table">
                    <tr>
                        <th>Tổng doanh thu</th>
                        <th>Doanh thu trung bình/đơn</th>
                    </tr>
                    <tr>
                        <td><?php echo formatCurrency($totalRevenue); ?></td>
                        <td><?php echo $totalOrders > 0 ? formatCurrency($totalRevenue / $totalOrders) : '0₫'; ?></td>
                    </tr>
                </table>
                <div class="chart-container">
                    <h3 class="mb-3">Biểu đồ doanh thu</h3>
                    <canvas id="revenueChartView" style="max-width:600px;max-height:350px;"></canvas>
                    <div id="noChartDataView" style="color:#888; font-style:italic; display:none;">Không có dữ liệu để vẽ biểu đồ</div>
                </div>
                <div class="print-section-title">3. Sản phẩm bán chạy</div>
                <table class="print-table">
                    <tr>
                        <th>Sản phẩm</th>
                        <th>Số lượng đã bán</th>
                        <th>Doanh thu</th>
                    </tr>
                    <?php
                    // Lấy lại top sản phẩm bán chạy cho phần in (vì đã fetch_assoc hết ở trên)
                    $result_top_products_print = $conn->query($sql_top_products);
                    while ($product = $result_top_products_print->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($product['TenSanPham']); ?></td>
                        <td><?php echo number_format($product['total_sold']); ?></td>
                        <td><?php echo formatCurrency($product['total_revenue']); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </table>
                <div class="print-footer">
                    <div><em>Ngày in: <?php echo date('d/m/Y H:i'); ?></em></div>
                    <div class="print-sign">Người lập báo cáo<br><br><br>__________________</div>
                </div>
            </div>
            <!-- Các phần in riêng lẻ -->
            <div class="print-report print-section" id="print-orders">
                <div class="print-header">
                    <img src="img/logo.jpg" class="print-logo" alt="Logo">
                    <div class="print-title">BÁO CÁO SỐ LƯỢNG ĐƠN HÀNG</div>
                </div>
                <div class="print-info">
                    <strong>Thời gian:</strong> 
                    <?php
                        if ($period === 'day') echo date('d/m/Y', strtotime($selectedDate));
                        elseif ($period === 'month') echo date('m/Y', strtotime($selectedMonth.'-01'));
                        else echo $selectedYear;
                    ?>
                    <br>
                    <strong><?php echo $isManager ? 'Người bán' : 'Tài khoản'; ?>:</strong> 
                    <?php
                        if ($isManager) {
                            foreach ($sellerList as $seller) {
                                if ($seller['IdNguoiBan'] == $userId) echo htmlspecialchars($seller['TenNguoiBan']);
                            }
                        } else {
                            echo htmlspecialchars($_SESSION['user']['TenDangNhap'] ?? '');
                        }
                    ?>
                </div>
                <div class="print-section-title">Số lượng đơn hàng</div>
                <table class="print-table">
                    <tr>
                        <th>Tổng số đơn hàng</th>
                    </tr>
                    <tr>
                        <td><?php echo number_format($totalOrders); ?></td>
                    </tr>
                </table>
                <div class="print-footer">
                    <div><em>Ngày in: <?php echo date('d/m/Y H:i'); ?></em></div>
                    <div class="print-sign">Người lập báo cáo<br><br><br>__________________</div>
                </div>
            </div>
            <div class="print-report print-section" id="print-revenue">
                <div class="print-header">
                    <img src="img/logo.jpg" class="print-logo" alt="Logo">
                    <div class="print-title">BÁO CÁO DOANH THU</div>
                </div>
                <div class="print-info">
                    <strong>Thời gian:</strong> 
                    <?php
                        if ($period === 'day') echo date('d/m/Y', strtotime($selectedDate));
                        elseif ($period === 'month') echo date('m/Y', strtotime($selectedMonth.'-01'));
                        else echo $selectedYear;
                    ?>
                    <br>
                    <strong><?php echo $isManager ? 'Người bán' : 'Tài khoản'; ?>:</strong> 
                    <?php
                        if ($isManager) {
                            foreach ($sellerList as $seller) {
                                if ($seller['IdNguoiBan'] == $userId) echo htmlspecialchars($seller['TenNguoiBan']);
                            }
                        } else {
                            echo htmlspecialchars($_SESSION['user']['TenDangNhap'] ?? '');
                        }
                    ?>
                </div>
                <div class="print-section-title">Doanh thu</div>
                <table class="print-table">
                    <tr>
                        <th>Tổng doanh thu</th>
                        <th>Doanh thu trung bình/đơn</th>
                    </tr>
                    <tr>
                        <td><?php echo formatCurrency($totalRevenue); ?></td>
                        <td><?php echo $totalOrders > 0 ? formatCurrency($totalRevenue / $totalOrders) : '0₫'; ?></td>
                    </tr>
                </table>
                <div class="chart-container">
                    <h3 class="mb-3">Biểu đồ doanh thu</h3>
                    <canvas id="revenueChartView2" style="max-width:600px;max-height:350px;"></canvas>
                    <div id="noChartDataView2" style="color:#888; font-style:italic; display:none;">Không có dữ liệu để vẽ biểu đồ</div>
                </div>
                <div class="print-footer">
                    <div><em>Ngày in: <?php echo date('d/m/Y H:i'); ?></em></div>
                    <div class="print-sign">Người lập báo cáo<br><br><br>__________________</div>
                </div>
            </div>
            <div class="print-report print-section" id="print-products">
                <div class="print-header">
                    <img src="img/logo.jpg" class="print-logo" alt="Logo">
                    <div class="print-title">BÁO CÁO SẢN PHẨM BÁN CHẠY</div>
                </div>
                <div class="print-info">
                    <strong>Thời gian:</strong> 
                    <?php
                        if ($period === 'day') echo date('d/m/Y', strtotime($selectedDate));
                        elseif ($period === 'month') echo date('m/Y', strtotime($selectedMonth.'-01'));
                        else echo $selectedYear;
                    ?>
                    <br>
                    <strong><?php echo $isManager ? 'Người bán' : 'Tài khoản'; ?>:</strong> 
                    <?php
                        if ($isManager) {
                            foreach ($sellerList as $seller) {
                                if ($seller['IdNguoiBan'] == $userId) echo htmlspecialchars($seller['TenNguoiBan']);
                            }
                        } else {
                            echo htmlspecialchars($_SESSION['user']['TenDangNhap'] ?? '');
                        }
                    ?>
                </div>
                <div class="print-section-title">Sản phẩm bán chạy</div>
                <table class="print-table">
                    <tr>
                        <th>Sản phẩm</th>
                        <th>Số lượng đã bán</th>
                        <th>Doanh thu</th>
                    </tr>
                    <?php
                    $result_top_products_print2 = $conn->query($sql_top_products);
                    while ($product = $result_top_products_print2->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($product['TenSanPham']); ?></td>
                        <td><?php echo number_format($product['total_sold']); ?></td>
                        <td><?php echo formatCurrency($product['total_revenue']); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </table>
                <div class="print-footer">
                    <div><em>Ngày in: <?php echo date('d/m/Y H:i'); ?></em></div>
                    <div class="print-sign">Người lập báo cáo<br><br><br>__________________</div>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($isManager && ($viewType === 'comparison' || isset($_GET['print']) && $_GET['print'] === 'comparison')): ?>
            <div class="print-report print-section" id="print-comparison">
                <div class="print-header">
                    <img src="img/logo.jpg" class="print-logo" alt="Logo">
                    <div class="print-title">BÁO CÁO SO SÁNH NGƯỜI BÁN</div>
                </div>
                <div class="print-info">
                    <strong>Thời gian:</strong> 
                    <?php
                        if ($period === 'day') echo date('d/m/Y', strtotime($selectedDate));
                        elseif ($period === 'month') echo date('m/Y', strtotime($selectedMonth.'-01'));
                        else echo $selectedYear;
                    ?>
                </div>
                <div class="print-section-title">So sánh hiệu suất người bán</div>
                <table class="print-table">
                    <tr>
                        <th>Người bán</th>
                        <th>Số đơn hàng</th>
                        <th>Doanh thu</th>
                        <th>Số khách hàng</th>
                        <th>Trung bình/đơn</th>
                    </tr>
                    <?php 
                    // Reset con trỏ kết quả để đọc lại từ đầu
                    $result_comparison->data_seek(0);
                    while ($row = $result_comparison->fetch_assoc()): 
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['TenNguoiBan']); ?></td>
                        <td><?php echo number_format($row['total_orders']); ?></td>
                        <td><?php echo formatCurrency($row['total_revenue']); ?></td>
                        <td><?php echo number_format($row['total_customers']); ?></td>
                        <td><?php echo $row['total_orders'] > 0 ? formatCurrency($row['total_revenue'] / $row['total_orders']) : '0₫'; ?></td>
                    </tr>
                    <?php endwhile; ?>
                </table>

                <div class="print-section-title mt-4">Top sản phẩm bán chạy theo người bán</div>
                <?php
                // Reset con trỏ kết quả để đọc lại từ đầu
                $result_comparison->data_seek(0);
                while ($seller = $result_comparison->fetch_assoc()):
                    $sellerId = $seller['IdNguoiBan'];
                ?>
                <div class="seller-section">
                    <h4 class="seller-name"><?php echo htmlspecialchars($seller['TenNguoiBan']); ?></h4>
                    <?php if (isset($seller_products[$sellerId]) && !empty($seller_products[$sellerId])): ?>
                        <table class="print-table">
                            <tr>
                                <th>Sản phẩm</th>
                                <th>Số lượng</th>
                                <th>Doanh thu</th>
                            </tr>
                            <?php foreach ($seller_products[$sellerId] as $product): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($product['TenSanPham']); ?></td>
                                <td><?php echo number_format($product['total_sold']); ?></td>
                                <td><?php echo formatCurrency($product['total_revenue']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </table>
                    <?php else: ?>
                        <p class="text-muted">Không có dữ liệu sản phẩm trong khoảng thời gian này.</p>
                    <?php endif; ?>
                </div>
                <?php endwhile; ?>

                <style>
                    /* Styles for both screen and print */
                    .seller-section {
                        margin-bottom: 30px;
                    }
                    .seller-section:last-child {
                        margin-bottom: 0;
                    }
                    .seller-name {
                        color: #4CAF50;
                        font-size: 1.1rem;
                        margin-bottom: 15px;
                        padding-bottom: 8px;
                        border-bottom: 2px solid #e9ecef;
                        text-align: left;
                    }
                    .print-table {
                        width: 100%;
                        border-collapse: collapse;
                        margin-bottom: 20px;
                        background: white;
                        border: 1px solid #dee2e6;
                    }
                    .print-table th {
                        background: #4CAF50 !important;
                        color: white !important;
                        padding: 12px;
                        font-weight: 500;
                        border: 1px solid #dee2e6;
                        text-align: center;
                        -webkit-print-color-adjust: exact;
                        print-color-adjust: exact;
                    }
                    .print-table td {
                        padding: 12px;
                        border: 1px solid #dee2e6;
                        text-align: center;
                    }
                    .print-table tr:last-child td {
                        border-bottom: 1px solid #dee2e6;
                    }
                    .text-muted {
                        color: #6c757d;
                        font-style: italic;
                        text-align: center;
                        padding: 15px;
                        background: #f8f9fa;
                        border-radius: 4px;
                        margin: 0;
                    }
                    .print-section-title {
                        text-align: left;
                        color: #4CAF50;
                        margin: 20px 0;
                        font-weight: 500;
                    }

                    /* Print-specific styles */
                    @media print {
                        .print-table th {
                            background-color: #4CAF50 !important;
                            color: white !important;
                            -webkit-print-color-adjust: exact;
                            print-color-adjust: exact;
                        }
                        
                        .seller-name, .print-section-title {
                            color: #4CAF50 !important;
                            -webkit-print-color-adjust: exact;
                            print-color-adjust: exact;
                        }

                        .print-table {
                            border-color: #dee2e6 !important;
                            -webkit-print-color-adjust: exact;
                            print-color-adjust: exact;
                        }

                        .print-table td, .print-table th {
                            border-color: #dee2e6 !important;
                            -webkit-print-color-adjust: exact;
                            print-color-adjust: exact;
                        }
                    }
                </style>

                <div class="chart-container">
                    <h3 class="mb-3">Biểu đồ so sánh doanh thu</h3>
                    <canvas id="comparisonChartView" style="max-width:800px;max-height:400px;"></canvas>
                </div>
                <div class="print-footer">
                    <div><em>Ngày in: <?php echo date('d/m/Y H:i'); ?></em></div>
                    <div class="print-sign">Người lập báo cáo<br><br><br>__________________</div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
    // Biểu đồ doanh thu cho phần in (all và revenue)
    function renderPrintCharts() {
        var chartData = <?php echo json_encode(array_reverse($chartData)); ?>;
        var chartTitle = <?php echo json_encode($chartTitle); ?>;
        // Xóa biểu đồ cũ nếu có
        if (window.printChart1) { window.printChart1.destroy(); }
        if (window.printChart2) { window.printChart2.destroy(); }

        // In all
        var ctx1 = document.getElementById('revenueChartPrint');
        var noData1 = document.getElementById('noChartDataPrint');
        if (ctx1 && noData1) {
            ctx1.style.display = 'block';
            noData1.style.display = 'none';
            window.printChart1 = new Chart(ctx1.getContext('2d'), {
                type: 'line',
                data: {
                    labels: chartData.length > 0 ? chartData.map(item => item.label) : ['Không có dữ liệu'],
                    datasets: [{
                        label: 'Doanh thu',
                        data: chartData.length > 0 ? chartData.map(item => item.revenue) : [0],
                        borderColor: '#4CAF50',
                        tension: 0.1,
                        fill: false
                    }]
                },
                options: {
                    responsive: false,
                    plugins: { legend: { position: 'top' }, title: { display: true, text: chartTitle } },
                    scales: { y: { beginAtZero: true, ticks: { callback: function(value) { return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(value); } } } }
                }
            });
        }
        // In revenue
        var ctx2 = document.getElementById('revenueChartPrint2');
        var noData2 = document.getElementById('noChartDataView2');
        if (ctx2 && noData2) {
            ctx2.style.display = 'block';
            noData2.style.display = 'none';
            window.printChart2 = new Chart(ctx2.getContext('2d'), {
                type: 'line',
                data: {
                    labels: chartData.length > 0 ? chartData.map(item => item.label) : ['Không có dữ liệu'],
                    datasets: [{
                        label: 'Doanh thu',
                        data: chartData.length > 0 ? chartData.map(item => item.revenue) : [0],
                        borderColor: '#4CAF50',
                        tension: 0.1,
                        fill: false
                    }]
                },
                options: {
                    responsive: true,
                    plugins: { legend: { position: 'top' }, title: { display: true, text: chartTitle } },
                    scales: { y: { beginAtZero: true, ticks: { callback: function(value) { return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(value); } } } }
                }
            });
        }
    }

    window.onbeforeprint = function() {
        setTimeout(renderPrintCharts, 100);
    };

    function printSelectedReport() {
        var type = document.getElementById('printType').value;
        document.querySelectorAll('.print-section').forEach(function(el) {
            el.classList.remove('print-active');
        });
        if (type === 'all') {
            document.getElementById('print-all').classList.add('print-active');
            setTimeout(renderPrintCharts, 100);
        } else if (type === 'orders') {
            document.getElementById('print-orders').classList.add('print-active');
        } else if (type === 'revenue') {
            document.getElementById('print-revenue').classList.add('print-active');
            setTimeout(renderPrintCharts, 100);
        } else if (type === 'products') {
            document.getElementById('print-products').classList.add('print-active');
        } else if (type === 'comparison') {
            document.getElementById('print-comparison').classList.add('print-active');
        }
        window.print();
        window.onafterprint = function() {
            document.querySelectorAll('.print-section').forEach(function(el) {
                el.classList.remove('print-active');
            });
        };
    }

    document.addEventListener('DOMContentLoaded', function() {
        var chartData = <?php echo json_encode(array_reverse($chartData)); ?>;
        var chartTitle = <?php echo json_encode($chartTitle); ?>;
        var ctx = document.getElementById('revenueChartView');
        var noData = document.getElementById('noChartDataView');
        if (ctx && noData) {
            ctx.style.display = 'block';
            noData.style.display = 'none';
            window.revenueChart = new Chart(ctx.getContext('2d'), {
                type: 'line',
                data: {
                    labels: chartData.length > 0 ? chartData.map(item => item.label) : ['Không có dữ liệu'],
                    datasets: [{
                        label: 'Doanh thu',
                        data: chartData.length > 0 ? chartData.map(item => item.revenue) : [0],
                        borderColor: '#4CAF50',
                        tension: 0.1,
                        fill: false
                    }]
                },
                options: {
                    responsive: true,
                    plugins: { legend: { position: 'top' }, title: { display: true, text: chartTitle } },
                    scales: { y: { beginAtZero: true, ticks: { callback: function(value) { return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(value); } } } }
                }
            });
        }
        // Vẽ biểu đồ cho phần báo cáo doanh thu
        var ctx2 = document.getElementById('revenueChartView2');
        var noData2 = document.getElementById('noChartDataView2');
        if (ctx2 && noData2) {
            ctx2.style.display = 'block';
            noData2.style.display = 'none';
            new Chart(ctx2.getContext('2d'), {
                type: 'line',
                data: {
                    labels: chartData.length > 0 ? chartData.map(item => item.label) : ['Không có dữ liệu'],
                    datasets: [{
                        label: 'Doanh thu',
                        data: chartData.length > 0 ? chartData.map(item => item.revenue) : [0],
                        borderColor: '#4CAF50',
                        tension: 0.1,
                        fill: false
                    }]
                },
                options: {
                    responsive: true,
                    plugins: { legend: { position: 'top' }, title: { display: true, text: chartTitle } },
                    scales: { y: { beginAtZero: true, ticks: { callback: function(value) { return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(value); } } } }
                }
            });
        }
        // Thêm vào phần document.addEventListener('DOMContentLoaded', function() {...})
        var comparisonData = <?php 
            $result_comparison = $conn->query($sql_comparison);
            $comparisonData = [];
            while ($row = $result_comparison->fetch_assoc()) {
                $comparisonData[] = [
                    'label' => $row['TenNguoiBan'],
                    'revenue' => $row['total_revenue'],
                    'orders' => $row['total_orders']
                ];
            }
            echo json_encode($comparisonData);
        ?>;

        var ctxComparison = document.getElementById('comparisonChartView');
        if (ctxComparison) {
            new Chart(ctxComparison, {
                type: 'bar',
                data: {
                    labels: comparisonData.map(item => item.label),
                    datasets: [{
                        label: 'Doanh thu',
                        data: comparisonData.map(item => item.revenue),
                        backgroundColor: '#4CAF50',
                        borderColor: '#388E3C',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return new Intl.NumberFormat('vi-VN', {
                                        style: 'currency',
                                        currency: 'VND'
                                    }).format(value);
                                }
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            position: 'top'
                        },
                        title: {
                            display: true,
                            text: 'So sánh doanh thu giữa các người bán'
                        }
                    }
                }
            });
        }
    });
    </script>
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

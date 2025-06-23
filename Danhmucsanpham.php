<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "csdldoanchuyennganh";

$conn = new mysqli($servername, $username, $password, $dbname);
$conn->set_charset("utf8mb4");

// Nhận danh mục từ request
$category = $_GET['category'] ?? '';

// Câu truy vấn sản phẩm nổi bật
$sql_featured = "SELECT s.IdSanPham, s.TenSanPham, s.Gia, s.MoTa, s.SoLuongTonKho, n.TenNguoiBan, n.DiaChi 
                 FROM sanpham s
                 JOIN nguoiban n ON s.IdNguoiBan = n.IdNguoiBan
                 WHERE s.noibat = 1";

// Câu truy vấn sản phẩm mới (trong 6 tháng)
$sql_new = "SELECT s.IdSanPham, s.TenSanPham, s.Gia, s.MoTa, s.SoLuongTonKho, n.TenNguoiBan, n.DiaChi 
            FROM sanpham s
            JOIN nguoiban n ON s.IdNguoiBan = n.IdNguoiBan
            WHERE s.ngaynhap >= DATE_SUB(NOW(), INTERVAL 6 MONTH)";

// Nếu có lọc theo danh mục
$params = [];
$types = "";

if ($category !== '' && $category !== 'all') {
    $sql_featured .= " AND s.LoaiSanPham = ?";
    $sql_new .= " AND s.LoaiSanPham = ?";
    $params[] = $category;
    $types .= "s";
}

// Chuẩn bị và thực thi truy vấn sản phẩm nổi bật
$stmt_featured = $conn->prepare($sql_featured);
if (!empty($params)) $stmt_featured->bind_param($types, ...$params);
$stmt_featured->execute();
$result_featured = $stmt_featured->get_result();

$featured = [];
while ($row = $result_featured->fetch_assoc()) {
    $featured[] = $row;
}

// Chuẩn bị và thực thi truy vấn sản phẩm mới
$stmt_new = $conn->prepare($sql_new);
if (!empty($params)) $stmt_new->bind_param($types, ...$params);
$stmt_new->execute();
$result_new = $stmt_new->get_result();

$new = [];
while ($row = $result_new->fetch_assoc()) {
    $new[] = $row;
}

// Trả kết quả JSON về client
header('Content-Type: application/json');
echo json_encode([
    'featured' => $featured,
    'new' => $new
]);
?>

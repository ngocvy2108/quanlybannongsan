<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "csdldoanchuyennganh";

$conn = new mysqli($servername, $username, $password, $dbname);
$conn->set_charset("utf8mb4");

if ($conn->connect_error) {
    die(json_encode(['error' => true, 'message' => 'Kết nối thất bại']));
}

// Lấy ID sản phẩm từ request
$id = isset($_GET['id']) ? $_GET['id'] : '';

if (empty($id)) {
    die(json_encode(['error' => true, 'message' => 'Không có ID sản phẩm']));
}

// Query để lấy thông tin sản phẩm và người bán
$sql = "SELECT s.*, n.TenNguoiBan, n.DiaChi 
        FROM sanpham s 
        JOIN nguoiban n ON s.IdNguoiBan = n.IdNguoiBan 
        WHERE s.IdSanPham = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $product = $result->fetch_assoc();
    echo json_encode($product);
} else {
    echo json_encode(['error' => true, 'message' => 'Không tìm thấy sản phẩm']);
}

$stmt->close();
$conn->close();
?> 
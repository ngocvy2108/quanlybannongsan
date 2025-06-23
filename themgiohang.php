<?php
session_start();
// Check if user is logged in
if (!isset($_SESSION['IdTaiKhoan'])) {
    echo json_encode(['success' => false, 'message' => 'Bạn cần đăng nhập để thêm vào giỏ hàng']);
    exit;
}

// Database connection
$servername = "localhost";
$username = "root";  // Change if needed
$password = "";      // Change if needed
$dbname = "csdldoanchuyennganh";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Lỗi kết nối cơ sở dữ liệu']);
    exit;
}

// Set character set
$conn->set_charset("utf8mb4");

// Get data from AJAX request
$data = json_decode(file_get_contents('php://input'), true);

// Check if data is valid
if (!isset($data['productId']) || !isset($data['quantity'])) {
    echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
    exit;
}

$productId = $conn->real_escape_string($data['productId']);
$quantity = (int)$data['quantity'];
$idTaiKhoan = $_SESSION['IdTaiKhoan'];

// Check if product exists in cart
$sql_check = "SELECT * FROM giohang WHERE IdTaiKhoan = '$idTaiKhoan' AND IdSanPham = '$productId' AND TrangThai = 'Trong giỏ'";
$result_check = $conn->query($sql_check);

if ($result_check->num_rows > 0) {
    // Update quantity if product exists
    $row = $result_check->fetch_assoc();
    $newQuantity = $row['SoLuong'] + $quantity;
    $idGioHang = $row['IdGioHang'];
    
    $sql_update = "UPDATE giohang SET SoLuong = $newQuantity WHERE IdGioHang = '$idGioHang'";
    
    if ($conn->query($sql_update) === TRUE) {
        echo json_encode(['success' => true, 'message' => 'Cập nhật số lượng thành công']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Lỗi cập nhật số lượng']);
    }
} else {
    // Insert new cart item
    // Generate new cart ID
    $sql_max_id = "SELECT MAX(CAST(SUBSTRING(IdGioHang, 3) AS UNSIGNED)) as max_id FROM giohang";
    $result_max_id = $conn->query($sql_max_id);
    $row_max_id = $result_max_id->fetch_assoc();
    $next_id = $row_max_id['max_id'] ? $row_max_id['max_id'] + 1 : 1;
    $idGioHang = 'GH' . sprintf('%02d', $next_id);
    
    $sql_insert = "INSERT INTO giohang (IdGioHang, IdTaiKhoan, IdSanPham, SoLuong, NgayThem, TrangThai) 
                   VALUES ('$idGioHang', '$idTaiKhoan', '$productId', $quantity, NOW(), 'Trong giỏ')";
    
    if ($conn->query($sql_insert) === TRUE) {
        echo json_encode(['success' => true, 'message' => 'Thêm vào giỏ hàng thành công']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Lỗi thêm vào giỏ hàng: ' . $conn->error]);
    }
}

$conn->close();
?>
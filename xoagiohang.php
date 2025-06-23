<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Kết nối database
$conn = mysqli_connect("localhost", "root", "", "csdldoanchuyennganh");

// Kiểm tra kết nối
if (!$conn) {
    die(json_encode(['success' => false, 'error' => 'Kết nối database thất bại']));
}

// Lấy dữ liệu từ request
$data = json_decode(file_get_contents('php://input'), true);
$idGioHang = $data['idGioHang'];

// Xóa sản phẩm khỏi giỏ hàng
$sql = "DELETE FROM giohang WHERE IdGioHang = '$idGioHang'";

if (mysqli_query($conn, $sql)) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => mysqli_error($conn)]);
}

mysqli_close($conn);
?>
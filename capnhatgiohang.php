<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "csdldoanchuyennganh";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Lỗi kết nối cơ sở dữ liệu']);
    exit;
}

$conn->set_charset("utf8mb4");

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['idGioHang']) || !isset($data['soLuong'])) {
    echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
    exit;
}

$idGioHang = (int)$data['idGioHang'];
$soLuong = (int)$data['soLuong'];

if ($soLuong <= 0) {
    $sql_delete = "DELETE FROM giohang WHERE IdGioHang = $idGioHang";
    if ($conn->query($sql_delete) === TRUE) {
        echo json_encode(['success' => true, 'message' => 'Đã xóa sản phẩm khỏi giỏ hàng']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Lỗi khi xóa sản phẩm: ' . $conn->error]);
    }
} else {
    $sql_update = "UPDATE giohang SET SoLuong = $soLuong WHERE IdGioHang = $idGioHang";
    if ($conn->query($sql_update) === TRUE) {
        echo json_encode(['success' => true, 'message' => 'Cập nhật số lượng thành công']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Lỗi khi cập nhật số lượng: ' . $conn->error]);
    }
}

$conn->close();
?>
<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Kết nối database
$conn = mysqli_connect("localhost", "root", "", "csdldoanchuyennganh");

// Kiểm tra kết nối
if (!$conn) {
    die("Kết nối thất bại: " . mysqli_connect_error());
}

// Lấy dữ liệu từ request
$data = json_decode(file_get_contents('php://input'), true);
$idKhachHang = $data['idKhachHang'];
$idSanPham = $data['idSanPham']; 
$soLuong = $data['soLuong'];

// Kiểm tra xem sản phẩm đã có trong giỏ hàng chưa
$sql = "SELECT * FROM giohang WHERE IdKhachHang = '$idKhachHang' AND IdSanPham = '$idSanPham'";
$result = mysqli_query($conn, $sql);

if(mysqli_num_rows($result) > 0) {
    // Nếu đã có thì cập nhật số lượng
    $row = mysqli_fetch_assoc($result);
    $soLuongMoi = $row['SoLuong'] + $soLuong;
    
    $sql = "UPDATE giohang SET SoLuong = $soLuongMoi WHERE IdKhachHang = '$idKhachHang' AND IdSanPham = '$idSanPham'";
} else {
    // Nếu chưa có thì thêm mới, KHÔNG cần IdGioHang
    $sql = "INSERT INTO giohang (IdKhachHang, IdSanPham, SoLuong, NgayThem) 
            VALUES ('$idKhachHang', '$idSanPham', $soLuong, NOW())";
}

// Thực thi câu lệnh SQL
if (mysqli_query($conn, $sql)) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => mysqli_error($conn)]);
}

// Đóng kết nối
mysqli_close($conn);
?> 
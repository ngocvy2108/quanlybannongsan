<?php
session_start();
require_once 'config.php';

// Kiểm tra xem người dùng đã đăng nhập chưa
if (!isset($_SESSION['user']) || $_SESSION['user']['VaiTro'] !== 'Khách hàng') {
    echo json_encode(['success' => false, 'message' => 'Bạn không có quyền thực hiện thao tác này']);
    exit();
}

// Lấy dữ liệu từ request
$data = json_decode(file_get_contents('php://input'), true);
$idDonHang = isset($data['idDonHang']) ? $data['idDonHang'] : null;

if (!$idDonHang) {
    echo json_encode(['success' => false, 'message' => 'Không tìm thấy mã đơn hàng']);
    exit();
}

// Kiểm tra xem đơn hàng có tồn tại và thuộc về khách hàng hiện tại không
$idKhachHang = '';
$tk = $_SESSION['user']['IdTaiKhoan'];
$sql = "SELECT IdKhachHang FROM khachhang WHERE IdTaiKhoan = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $tk);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $idKhachHang = $row['IdKhachHang'];
}

// Kiểm tra đơn hàng
$sql = "SELECT * FROM donhang WHERE IdDonHang = ? AND IdKhachHang = ? AND TrangThai = 'Chờ xác nhận'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $idDonHang, $idKhachHang);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Không thể hủy đơn hàng này']);
    exit();
}

// Cập nhật trạng thái đơn hàng thành "Đã hủy"
$sql = "UPDATE donhang SET TrangThai = 'Đã hủy' WHERE IdDonHang = ? AND IdKhachHang = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $idDonHang, $idKhachHang);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Đã hủy đơn hàng thành công']);
} else {
    echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra khi hủy đơn hàng']);
}

$stmt->close();
$conn->close();
?> 
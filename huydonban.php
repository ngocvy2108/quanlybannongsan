<?php
require_once 'config.php';
header('Content-Type: application/json');
$data = json_decode(file_get_contents('php://input'), true);

$idDonHang = isset($data['idDonHang']) ? $data['idDonHang'] : '';

if (!$idDonHang) {
    echo json_encode(['success' => false, 'message' => 'Thiếu mã đơn hàng']);
    exit;
}

// Kiểm tra trạng thái đơn hàng
$sql = "SELECT TrangThai FROM donhang WHERE IdDonHang = '$idDonHang'";
$result = $conn->query($sql);
if (!$result || $result->num_rows == 0) {
    echo json_encode(['success' => false, 'message' => 'Không tìm thấy đơn hàng']);
    exit;
}
$row = $result->fetch_assoc();
if ($row['TrangThai'] !== 'Chờ xác nhận' && $row['TrangThai'] !== 'Đã xác nhận') {
    echo json_encode(['success' => false, 'message' => 'Chỉ được hủy đơn hàng ở trạng thái chờ xác nhận hoặc đã xác nhận']);
    exit;
}

// Cập nhật trạng thái đơn hàng thành Đã hủy
$sql = "UPDATE donhang SET TrangThai = 'Đã hủy' WHERE IdDonHang = '$idDonHang'";
if ($conn->query($sql)) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Lỗi khi hủy đơn hàng']);
} 
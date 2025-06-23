<?php
require_once 'config.php';
header('Content-Type: application/json');
$data = json_decode(file_get_contents('php://input'), true);
$idDonHang = isset($data['idDonHang']) ? $data['idDonHang'] : '';
if (!$idDonHang) {
    echo json_encode(['success' => false, 'message' => 'Thiếu mã đơn hàng']);
    exit;
}
$sql = "SELECT TrangThai FROM donhang WHERE IdDonHang = '$idDonHang'";
$result = $conn->query($sql);
if (!$result || $result->num_rows == 0) {
    echo json_encode(['success' => false, 'message' => 'Không tìm thấy đơn hàng']);
    exit;
}
$row = $result->fetch_assoc();
if ($row['TrangThai'] !== 'Chờ xác nhận') {
    echo json_encode(['success' => false, 'message' => 'Chỉ xác nhận đơn ở trạng thái Chờ xác nhận']);
    exit;
}
$update = $conn->query("UPDATE donhang SET TrangThai = 'Đã xác nhận' WHERE IdDonHang = '$idDonHang'");
if ($update) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Lỗi khi cập nhật trạng thái đơn hàng']);
} 
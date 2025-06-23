<?php
require_once 'config.php';
header('Content-Type: application/json');
$data = json_decode(file_get_contents('php://input'), true);

$idDonHang = isset($data['idDonHang']) ? $data['idDonHang'] : '';
$phuongThuc = isset($data['phuongThuc']) ? $data['phuongThuc'] : 'Thanh toán khi nhận hàng';

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
if ($row['TrangThai'] !== 'Đã xác nhận') {
    echo json_encode(['success' => false, 'message' => 'Chỉ được thanh toán khi đơn đã xác nhận']);
    exit;
}

// Sinh mã thanh toán mới
$sql = "SELECT COUNT(*) AS total FROM thanhtoan";
$row = $conn->query($sql)->fetch_assoc();
$idThanhToan = 'TT' . str_pad($row['total'] + 1, 2, '0', STR_PAD_LEFT);
$ngayThanhToan = date('Y-m-d');

// Lưu vào bảng thanhtoan
$stmt = $conn->prepare("INSERT INTO thanhtoan (IdThanhToan, PhuongThuc, NgayThanhToan, IdDonHang) VALUES (?, ?, ?, ?)");
$stmt->bind_param("ssss", $idThanhToan, $phuongThuc, $ngayThanhToan, $idDonHang);
$ok = $stmt->execute();
$stmt->close();

if ($ok) {
    // Cập nhật trạng thái đơn hàng
    $conn->query("UPDATE donhang SET TrangThai = 'Chờ giao hàng', IdThanhToan = '$idThanhToan' WHERE IdDonHang = '$idDonHang'");
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Lỗi khi lưu thanh toán']);
} 
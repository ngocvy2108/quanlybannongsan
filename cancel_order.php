<?php
// Khởi tạo session để lấy thông tin đăng nhập
session_start();

// Kiểm tra đăng nhập
if (!isset($_SESSION['user'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Bạn cần đăng nhập để thực hiện hành động này'
    ]);
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
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi kết nối cơ sở dữ liệu: ' . $conn->connect_error
    ]);
    exit;
}

// Set character set
$conn->set_charset("utf8mb4");

// Nhận dữ liệu từ client
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['order_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Thiếu thông tin đơn hàng'
    ]);
    exit;
}

$orderId = $data['order_id'];

// Lấy thông tin đơn hàng để kiểm tra
$stmt = $conn->prepare("SELECT * FROM donhang WHERE IdDonHang = ?");
$stmt->bind_param("s", $orderId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Không tìm thấy đơn hàng'
    ]);
    exit;
}

$order = $result->fetch_assoc();

// Kiểm tra xem đơn hàng có thể hủy được không
if ($order['TrangThai'] !== 'Chờ xác nhận') {
    echo json_encode([
        'success' => false,
        'message' => 'Chỉ có thể hủy đơn hàng ở trạng thái Chờ xác nhận'
    ]);
    exit;
}

// Kiểm tra quyền hủy đơn hàng
// Xác định ID khách hàng từ session
$id_khach_hang = null;

// Kiểm tra các cấu trúc SESSION có thể có
if (isset($_SESSION['user']['IdTaiKhoan'])) {
    $user_id = $_SESSION['user']['IdTaiKhoan'];
} elseif (isset($_SESSION['user']['id'])) {
    $user_id = $_SESSION['user']['id'];
} elseif (is_string($_SESSION['user']) || is_numeric($_SESSION['user'])) {
    $user_id = $_SESSION['user'];
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Không tìm thấy thông tin tài khoản'
    ]);
    exit;
}

// Lấy ID khách hàng từ bảng khachhang
$stmt = $conn->prepare("SELECT IdKhachHang FROM khachhang WHERE IdTaiKhoan = ?");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $id_khach_hang = $row['IdKhachHang'];
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Không tìm thấy thông tin khách hàng'
    ]);
    exit;
}

// Kiểm tra xem đơn hàng có thuộc về khách hàng này không
if ($order['IdKhachHang'] != $id_khach_hang) {
    echo json_encode([
        'success' => false,
        'message' => 'Bạn không có quyền hủy đơn hàng này'
    ]);
    exit;
}

// Lấy thông tin sản phẩm trước khi hủy đơn hàng để trả về
$productStmt = $conn->prepare("SELECT sp.IdSanPham, sp.TenSanPham, dh.Gia 
                               FROM donhang dh
                               JOIN sanpham sp ON dh.IdSanPham = sp.IdSanPham
                               WHERE dh.IdDonHang = ?");
$productStmt->bind_param("s", $orderId);
$productStmt->execute();
$productResult = $productStmt->get_result();
$productInfo = $productResult->fetch_assoc();

// Thực hiện hủy đơn hàng
$cancelStatus = 'Đã hủy';
$stmt = $conn->prepare("UPDATE donhang SET TrangThai = ? WHERE IdDonHang = ?");
$stmt->bind_param("ss", $cancelStatus, $orderId);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'message' => 'Đơn hàng đã được hủy thành công',
        'product' => $productInfo
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Không thể hủy đơn hàng: ' . $conn->error
    ]);
}

$stmt->close();
$conn->close();
?>
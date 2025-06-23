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

// Lấy thông tin đơn hàng gốc để sao chép
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

$originalOrder = $result->fetch_assoc();

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
if ($originalOrder['IdKhachHang'] != $id_khach_hang) {
    echo json_encode([
        'success' => false,
        'message' => 'Bạn không có quyền đặt lại đơn hàng này'
    ]);
    exit;
}

// Kiểm tra xem đơn hàng có ở trạng thái "Đã hủy" hay không
if ($originalOrder['TrangThai'] !== 'Đã hủy') {
    echo json_encode([
        'success' => false,
        'message' => 'Chỉ có thể đặt lại đơn hàng ở trạng thái Đã hủy'
    ]);
    exit;
}

// Cập nhật trạng thái đơn hàng hiện tại (như yêu cầu của bạn)
$newStatus = 'Chờ xác nhận';
$stmt = $conn->prepare("UPDATE donhang SET TrangThai = ?, NgayDatHang = CURRENT_DATE() WHERE IdDonHang = ?");
$stmt->bind_param("ss", $newStatus, $orderId);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'message' => 'Đơn hàng đã được đặt lại thành công',
        'order_id' => $orderId
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Không thể đặt lại đơn hàng: ' . $conn->error
    ]);
}

// Option 2 (không sử dụng): Tạo đơn hàng mới dựa trên đơn hàng cũ 
// Đoạn code dưới đây được comment và chỉ là cho mục đích tham khảo
/*
// Tạo mã đơn hàng mới
$currentDate = date('YmdHis');
$randomStr = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 5);
$newOrderId = 'DH' . $currentDate . $randomStr;

// Tạo đơn hàng mới với thông tin từ đơn hàng cũ
$stmt = $conn->prepare("INSERT INTO donhang (IdDonHang, IdKhachHang, IdSanPham, IdNguoiBan, SoLuong, Gia, TongGiaTri, NgayDatHang, TrangThai) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, CURRENT_DATE(), 'Chờ xác nhận')");

$stmt->bind_param("ssssidd", 
    $newOrderId, 
    $originalOrder['IdKhachHang'], 
    $originalOrder['IdSanPham'], 
    $originalOrder['IdNguoiBan'], 
    $originalOrder['SoLuong'], 
    $originalOrder['Gia'], 
    $originalOrder['TongGiaTri']
);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'message' => 'Đơn hàng mới đã được tạo thành công',
        'order_id' => $newOrderId
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Không thể tạo đơn hàng mới: ' . $conn->error
    ]);
}
*/

$stmt->close();
$conn->close();
?>
<?php
// Khởi tạo session nếu chưa có
session_start();

// Cấu hình hiển thị lỗi (nên tắt trong môi trường production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Hàm trả về kết quả JSON
function sendJsonResponse($success, $message, $data = null) {
    header('Content-Type: application/json');
    $response = [
        'success' => $success,
        'message' => $message
    ];
    
    if ($data !== null) {
        $response = array_merge($response, $data);
    }
    
    echo json_encode($response);
    exit;
}

// Kiểm tra đăng nhập
if (!isset($_SESSION['user'])) {
    sendJsonResponse(false, 'Vui lòng đăng nhập để đặt hàng', ['redirect' => 'index.php']);
}

// Kết nối cơ sở dữ liệu
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "csdldoanchuyennganh";

try {
    // Tạo kết nối PDO thay vì mysqli để xử lý lỗi tốt hơn
    $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    error_log("Database connection error: " . $e->getMessage());
    sendJsonResponse(false, 'Không thể kết nối đến cơ sở dữ liệu');
}

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
    // Log thông tin session để debug
    error_log("Session structure: " . print_r($_SESSION, true));
    sendJsonResponse(false, 'Không tìm thấy thông tin tài khoản', ['redirect' => 'index.php']);
}

// XÁC ĐỊNH ID KHÁCH HÀNG TỪ BẢNG KHACHHANG
try {
    $stmt = $conn->prepare("SELECT IdKhachHang FROM khachhang WHERE IdTaiKhoan = :user_id");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result && isset($result['IdKhachHang'])) {
        $id_khach_hang = $result['IdKhachHang'];
    } else {
        // Log thông báo lỗi
        error_log("Không tìm thấy ID khách hàng cho tài khoản ID: $user_id");
        sendJsonResponse(false, 'Không tìm thấy thông tin khách hàng. Vui lòng cập nhật thông tin cá nhân trước khi đặt hàng.', ['redirect' => 'taikhoan.php']);
    }
} catch (PDOException $e) {
    error_log("Database error getting customer ID: " . $e->getMessage());
    sendJsonResponse(false, 'Lỗi khi xác minh thông tin khách hàng');
}

// Lấy dữ liệu đơn hàng được gửi từ client
$json_data = file_get_contents('php://input');

// Kiểm tra dữ liệu JSON hợp lệ
if (!$json_data) {
    sendJsonResponse(false, 'Không nhận được dữ liệu đơn hàng');
}

try {
    $order_data = json_decode($json_data, true, 512, JSON_THROW_ON_ERROR);
} catch (Exception $e) {
    error_log("JSON decode error: " . $e->getMessage() . ", Data: " . $json_data);
    sendJsonResponse(false, 'Dữ liệu đơn hàng không hợp lệ');
}

// Kiểm tra cấu trúc dữ liệu
if (!isset($order_data['items']) || empty($order_data['items'])) {
    sendJsonResponse(false, 'Giỏ hàng trống');
}

// Log dữ liệu đơn hàng nhận được để debug
error_log("Order data received: " . $json_data);
error_log("Using customer ID: " . $id_khach_hang);

// Tạo mã đơn hàng theo thứ tự tăng dần từ DH01
function generateUniqueOrderId($conn, $prefix = 'DH') {
    // Tìm mã đơn hàng lớn nhất hiện tại trong CSDL
    $stmt = $conn->prepare("SELECT IdDonHang FROM donhang WHERE IdDonHang LIKE :prefix ORDER BY IdDonHang DESC LIMIT 1");
    $search_pattern = $prefix . '%';
    $stmt->bindParam(':prefix', $search_pattern);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        // Nếu có đơn hàng tồn tại, lấy mã số hiện tại và tăng lên 1
        $current_id = $result['IdDonHang'];
        // Trích xuất phần số từ mã hiện tại (bỏ qua prefix)
        $current_num = (int)substr($current_id, strlen($prefix));
        $next_num = $current_num + 1;
    } else {
        // Nếu chưa có đơn hàng nào, bắt đầu từ 1
        $next_num = 1;
    }
    
    // Format số với đủ số 0 phía trước (ví dụ: 01, 02, ..., 10, 11)
    // Sử dụng 2 chữ số, có thể thay đổi thành 3 hoặc nhiều hơn nếu cần
    $formatted_num = sprintf('%02d', $next_num);
    $new_id = $prefix . $formatted_num;
    
    // Kiểm tra xem ID mới có bị trùng không (phòng trường hợp hiếm gặp)
    $check_stmt = $conn->prepare("SELECT COUNT(*) FROM donhang WHERE IdDonHang = :id");
    $check_stmt->bindParam(':id', $new_id);
    $check_stmt->execute();
    $exists = (int)$check_stmt->fetchColumn() > 0;
    
    if ($exists) {
        // Nếu đã tồn tại (rất hiếm), thử lại với số tiếp theo
        error_log("ID $new_id đã tồn tại, thử với số tiếp theo");
        $next_num++;
        $formatted_num = sprintf('%02d', $next_num);
        $new_id = $prefix . $formatted_num;
    }
    
    return $new_id;
}

// PHẦN XỬ LÝ ĐƠN HÀNG - ĐÃ SỬA THEO SCHEMA

try {
    $conn->beginTransaction();

    // Gom sản phẩm theo IdNguoiBan
    $items_by_seller = [];
    foreach ($order_data['items'] as $item) {
        $id_san_pham = $item['id'];
        $stmt_sp = $conn->prepare("SELECT IdNguoiBan FROM sanpham WHERE IdSanPham = :id");
        $stmt_sp->bindParam(':id', $id_san_pham);
        $stmt_sp->execute();
        $sp = $stmt_sp->fetch(PDO::FETCH_ASSOC);
        if (!$sp || !isset($sp['IdNguoiBan'])) {
            throw new Exception('Không tìm thấy người bán cho sản phẩm: ' . $id_san_pham);
        }
        $id_nguoi_ban = $sp['IdNguoiBan'];
        if (!isset($items_by_seller[$id_nguoi_ban])) {
            $items_by_seller[$id_nguoi_ban] = [];
        }
        $items_by_seller[$id_nguoi_ban][] = $item;
    }

    // Tạo đơn hàng cho từng người bán
    foreach ($items_by_seller as $id_nguoi_ban => $items) {
        $tong_gia_tri = 0;
        foreach ($items as $item) {
            $tong_gia_tri += (float)$item['price'] * (int)$item['quantity'];
        }
        $id_don_hang = generateUniqueOrderId($conn);
        $ngay_dat_hang = date('Y-m-d');
        $trang_thai = 'Chờ xác nhận';
        $id_thanh_toan = 'TT01';

        // 1. Tạo đơn hàng (bảng donhang)
        $stmt_order = $conn->prepare("INSERT INTO donhang 
            (IdDonHang, NgayDatHang, TongGiaTri, TrangThai, IdKhachHang, IdNguoiBan, IdThanhToan) 
            VALUES 
            (:id_don_hang, :ngay_dat_hang, :tong_gia_tri, :trang_thai, :id_khach_hang, :id_nguoi_ban, :id_thanh_toan)");
        $stmt_order->bindParam(':id_don_hang', $id_don_hang);
        $stmt_order->bindParam(':ngay_dat_hang', $ngay_dat_hang);
        $stmt_order->bindParam(':tong_gia_tri', $tong_gia_tri);
        $stmt_order->bindParam(':trang_thai', $trang_thai);
        $stmt_order->bindParam(':id_khach_hang', $id_khach_hang);
        $stmt_order->bindParam(':id_nguoi_ban', $id_nguoi_ban);
        $stmt_order->bindParam(':id_thanh_toan', $id_thanh_toan);
        $stmt_order->execute();

        // 2. Thêm từng sản phẩm vào chitietdonhang
        foreach ($items as $item) {
            $id_san_pham = $item['id'];
            $so_luong = (int)$item['quantity'];
            $gia = (float)$item['price'];
            $thanh_tien = $gia * $so_luong;

            $stmt_detail = $conn->prepare("INSERT INTO chitietdonhang 
                (IdDonHang, IdSanPham, SoLuong, Gia, ThanhTien) 
                VALUES 
                (:id_don_hang, :id_san_pham, :so_luong, :gia, :thanh_tien)");
            $stmt_detail->bindParam(':id_don_hang', $id_don_hang);
            $stmt_detail->bindParam(':id_san_pham', $id_san_pham);
            $stmt_detail->bindParam(':so_luong', $so_luong);
            $stmt_detail->bindParam(':gia', $gia);
            $stmt_detail->bindParam(':thanh_tien', $thanh_tien);
            $stmt_detail->execute();

            // Cập nhật số lượng tồn kho
            $stmt_update = $conn->prepare("UPDATE sanpham SET SoLuongTonKho = SoLuongTonKho - :so_luong WHERE IdSanPham = :id_san_pham");
            $stmt_update->bindParam(':so_luong', $so_luong);
            $stmt_update->bindParam(':id_san_pham', $id_san_pham);
            $stmt_update->execute();
        }
    }

    // Xóa giỏ hàng sau khi đặt hàng thành công
    $stmt_clear_cart = $conn->prepare("DELETE FROM giohang WHERE IdKhachHang = :id_khach_hang");
    $stmt_clear_cart->bindParam(':id_khach_hang', $id_khach_hang);
    $stmt_clear_cart->execute();

    $conn->commit();
    sendJsonResponse(true, 'Đặt hàng thành công!', [ 'redirect' => 'DonHang.php' ]);

} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log("Order processing error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    sendJsonResponse(false, 'Đã xảy ra lỗi khi xử lý đơn hàng: ' . $e->getMessage());
}
?>
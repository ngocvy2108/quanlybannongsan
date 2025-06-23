<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start output buffering to catch any unexpected output
ob_start();

header('Content-Type: application/json');

// Log the request
error_log("Cart request received for customer ID: " . (isset($_GET['idKhachHang']) ? $_GET['idKhachHang'] : 'not set'));

try {
    // Database connection
    $conn = mysqli_connect("localhost", "root", "", "csdldoanchuyennganh");
    if (!$conn) {
        throw new Exception('Kết nối database thất bại: ' . mysqli_connect_error());
    }

    // Set charset to utf8
    mysqli_set_charset($conn, "utf8");

    // Get customer ID from request and validate
    $idKhachHang = isset($_GET['idKhachHang']) ? $_GET['idKhachHang'] : '';
    if (empty($idKhachHang)) {
        throw new Exception('ID khách hàng không được để trống');
    }

    // Log the customer ID
    error_log("Processing cart for customer ID: " . $idKhachHang);

    // First, check if the customer exists
    $checkCustomer = "SELECT COUNT(*) as count FROM khachhang WHERE IdKhachHang = ?";
    $stmtCheck = mysqli_prepare($conn, $checkCustomer);
    if (!$stmtCheck) {
        throw new Exception('Lỗi kiểm tra khách hàng: ' . mysqli_error($conn));
    }
    mysqli_stmt_bind_param($stmtCheck, "s", $idKhachHang);
    mysqli_stmt_execute($stmtCheck);
    $resultCheck = mysqli_stmt_get_result($stmtCheck);
    $customerExists = mysqli_fetch_assoc($resultCheck)['count'] > 0;
    mysqli_stmt_close($stmtCheck);

    if (!$customerExists) {
        throw new Exception('Không tìm thấy khách hàng với ID: ' . $idKhachHang);
    }

    // Prepare and execute main query
    $sql = "SELECT g.*, s.TenSanPham, s.Gia 
            FROM giohang g 
            INNER JOIN sanpham s ON g.IdSanPham = s.IdSanPham 
            WHERE g.IdKhachHang = ?";

    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        throw new Exception('Lỗi chuẩn bị câu truy vấn: ' . mysqli_error($conn));
    }

    mysqli_stmt_bind_param($stmt, "s", $idKhachHang);
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Lỗi thực thi câu truy vấn: ' . mysqli_stmt_error($stmt));
    }

    $result = mysqli_stmt_get_result($stmt);
    if (!$result) {
        throw new Exception('Lỗi lấy kết quả: ' . mysqli_error($conn));
    }

    $gioHang = [];
    $rowCount = 0;
    while ($row = mysqli_fetch_assoc($result)) {
        $rowCount++;
        // Use product ID for image path instead of product name
        $image = 'img/' . strtolower($row['IdSanPham']) . '.jpg';
        if (!file_exists($image)) {
            $image = 'img/default.jpg'; // Use default image if product image doesn't exist
        }
        $gioHang[] = [
            'idGioHang' => $row['IdGioHang'],
            'idSanPham' => $row['IdSanPham'],
            'tenSanPham' => $row['TenSanPham'],
            'gia' => $row['Gia'],
            'hinhAnh' => $image,
            'soLuong' => $row['SoLuong'],
            'ngayThem' => $row['NgayThem']
        ];
    }

    // Log the results
    error_log("Found {$rowCount} items in cart for customer {$idKhachHang}");
    error_log("Cart data: " . json_encode($gioHang));

    // Clear any output buffer
    ob_clean();
    
    // Send the response
    echo json_encode(['success' => true, 'data' => $gioHang]);

} catch (Exception $e) {
    // Log the error
    error_log("Error in laygiohang.php: " . $e->getMessage());
    
    // Clear any output buffer
    ob_clean();
    
    // Send error response
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} finally {
    if (isset($stmt)) {
        mysqli_stmt_close($stmt);
    }
    if (isset($conn)) {
        mysqli_close($conn);
    }
    // End output buffering
    ob_end_flush();
}

// Helper function to convert Vietnamese text to URL-friendly format
function convertToSlug($text) {
    // Replace Vietnamese characters
    $search = array('à','á','ạ','ả','ã','â','ầ','ấ','ậ','ẩ','ẫ','ă','ằ','ắ','ặ','ẳ','ẵ',
                    'è','é','ẹ','ẻ','ẽ','ê','ề','ế','ệ','ể','ễ',
                    'ì','í','ị','ỉ','ĩ',
                    'ò','ó','ọ','ỏ','õ','ô','ồ','ố','ộ','ổ','ỗ','ơ','ờ','ớ','ợ','ở','ỡ',
                    'ù','ú','ụ','ủ','ũ','ư','ừ','ứ','ự','ử','ữ',
                    'ỳ','ý','ỵ','ỷ','ỹ',
                    'đ',
                    ' ');
    $replace = array('a','a','a','a','a','a','a','a','a','a','a','a','a','a','a','a','a',
                     'e','e','e','e','e','e','e','e','e','e','e',
                     'i','i','i','i','i',
                     'o','o','o','o','o','o','o','o','o','o','o','o','o','o','o','o','o',
                     'u','u','u','u','u','u','u','u','u','u','u',
                     'y','y','y','y','y',
                     'd',
                     '');
    
    // Convert to lowercase and replace Vietnamese characters
    $text = mb_strtolower($text, 'UTF-8');
    $text = str_replace($search, $replace, $text);
    
    // Remove any remaining non-alphanumeric characters
    $text = preg_replace('/[^a-z0-9]/', '', $text);
    
    return $text;
}
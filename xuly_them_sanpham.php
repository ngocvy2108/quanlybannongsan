<?php
session_start();
include 'config.php';

if (!isset($_SESSION['IdNguoiBan'])) {
    header("Location: index.php");
    exit();
}

$idNguoiBan = $_SESSION['IdNguoiBan'];

// Tạo mã sản phẩm tự động (SP + số tăng dần)
$result = $conn->query("SELECT MAX(IdSanPham) AS max_id FROM sanpham");
$row = $result->fetch_assoc();
$nextId = 'SP' . str_pad(((int)substr($row['max_id'], 2)) + 1, 2, '0', STR_PAD_LEFT);

// Lấy IdNguoiQuanLy từ bảng nguoiban
$query = "SELECT IdNguoiQuanLy FROM nguoiban WHERE IdNguoiBan = ?";
$stmt_get = $conn->prepare($query);
$stmt_get->bind_param("s", $idNguoiBan);
$stmt_get->execute();
$result_get = $stmt_get->get_result();
$row_get = $result_get->fetch_assoc();

if (!$row_get) {
    echo "❌ Không tìm thấy người quản lý của người bán.";
    exit();
}

$idNguoiQuanLy = $row_get['IdNguoiQuanLy'];

// Kiểm tra và xử lý ảnh
$imgData = null;
if (isset($_FILES['anh']) && $_FILES['anh']['error'] === 0) {
    $imgData = file_get_contents($_FILES['anh']['tmp_name']);
    if (!$imgData) {
        echo "❌ Không thể đọc dữ liệu ảnh.";
        exit();
    }
} else {
    echo "❌ Lỗi upload ảnh: " . $_FILES['anh']['error'];
    exit();
}

$imgFileName = strtolower($nextId) . '.jpg';
$imgPath = 'img/' . $imgFileName;
if (!is_dir('img')) {
    mkdir('img', 0777, true);
}
move_uploaded_file($_FILES['anh']['tmp_name'], $imgPath);

// Chèn sản phẩm vào CSDL
$sql = "INSERT INTO sanpham (
    IdSanPham, TenSanPham, Gia, Loai, SoLuongTonKho, MoTa, IdNguoiQuanLy, IdNguoiBan, noibat, LoaiSanPham, TrangThaiDuyet
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0)";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo "❌ Lỗi chuẩn bị truy vấn: " . $conn->error;
    exit();
}

$stmt->bind_param(
    "ssdsssssis",
    $nextId,
    $_POST['TenSanPham'],
    $_POST['Gia'],
    $_POST['Loai'],
    $_POST['SoLuongTonKho'],
    $_POST['MoTa'],
    $idNguoiQuanLy,
    $idNguoiBan,
    $_POST['noibat'],
    $_POST['LoaiSanPham']
);

if ($stmt->execute()) {
    echo "✅ Sản phẩm đã được gửi để quản lý duyệt.";
} else {
    echo "❌ Lỗi khi chèn sản phẩm: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>

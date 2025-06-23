<?php
session_start();
include('config.php');

// Hàm tạo ID mới
function generateNewId($table, $prefix) {
    global $conn;
    $query = "SELECT MAX(CAST(SUBSTRING(IdBinhLuan, 3) AS UNSIGNED)) AS max_id FROM $table";
    $result = mysqli_query($conn, $query);
    $row = mysqli_fetch_assoc($result);
    $newId = $row['max_id'] ? $row['max_id'] + 1 : 1;
    return $prefix . str_pad($newId, 2, '0', STR_PAD_LEFT);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['noidung_binhluan'], $_POST['idTinTuc'])) {
    $noidung = mysqli_real_escape_string($conn, $_POST['noidung_binhluan']);
    $idTinTuc = $_POST['idTinTuc'];

    if (!isset($_SESSION['user']['IdTaiKhoan'])) {
        echo "Lỗi: Chưa đăng nhập";
        exit;
    }

    $idTaiKhoan = $_SESSION['user']['IdTaiKhoan'];
    $tenNguoiDung = $_SESSION['user']['TenDangNhap'];
    $idBinhLuan = generateNewId('binhluan', 'BL');

    $insert = "INSERT INTO binhluan (IdBinhLuan, NoiDung, IdTinTuc, IdTaiKhoan, NgayBinhLuan)
               VALUES ('$idBinhLuan', '$noidung', '$idTinTuc', '$idTaiKhoan', NOW())";

    if (mysqli_query($conn, $insert)) {
        $time = date('Y-m-d H:i:s');
        echo "<p><b>$tenNguoiDung</b>: $noidung <i>($time)</i></p>";
    } else {
        echo "Lỗi: " . mysqli_error($conn);
    }
}
?>

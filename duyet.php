<?php
include 'config.php';
$id = $_GET['id'];

$sql = "UPDATE sanpham SET TrangThaiDuyet = 1 WHERE IdSanPham = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $id);

if ($stmt->execute()) {
    echo "Đã duyệt sản phẩm.";
} else {
    echo "Lỗi duyệt.";
}
?>

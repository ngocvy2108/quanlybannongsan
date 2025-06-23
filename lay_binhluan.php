<?php
include('config.php');

if (isset($_GET['idTinTuc'])) {
    $idTinTuc = $_GET['idTinTuc'];
    $sql = "SELECT b.*, t.TenDangNhap FROM binhluan b 
            JOIN taikhoan t ON b.IdTaiKhoan = t.IdTaiKhoan 
            WHERE b.IdTinTuc = '$idTinTuc' ORDER BY NgayBinhLuan DESC";
    $result = mysqli_query($conn, $sql);
    while ($bl = mysqli_fetch_assoc($result)) {
        echo "<p><b>{$bl['TenDangNhap']}</b>: {$bl['NoiDung']} <i>({$bl['NgayBinhLuan']})</i></p>";
    }
}
?>

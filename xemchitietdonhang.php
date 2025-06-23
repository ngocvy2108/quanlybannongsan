<?php
require_once 'config.php';
$idDonHang = isset($_GET['id']) ? trim($_GET['id']) : '';
if (!$idDonHang) {
    echo '<div style="color:red;">Không có mã đơn hàng!</div>';
    exit;
}
$sql = "SELECT c.*, s.TenSanPham FROM chitietdonhang c JOIN sanpham s ON TRIM(c.IdSanPham) = TRIM(s.IdSanPham) WHERE TRIM(c.IdDonHang) = '$idDonHang'";
$result = $conn->query($sql);
if (!$result || $result->num_rows == 0) {
    echo '<div style="color:red;">Không có sản phẩm trong đơn hàng này!</div>';
    exit;
}
function formatPrice($price) {
    return number_format($price, 0, ',', '.') . '₫';
}
function convertToSlug($text) {
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
    $text = mb_strtolower($text, 'UTF-8');
    $text = str_replace($search, $replace, $text);
    $text = preg_replace('/[^a-z0-9]/', '', $text);
    return $text;
}
function getImageUrl($idSanPham) {
    $filename = 'img/' . strtolower($idSanPham) . '.jpg';
    if (file_exists($filename)) {
        return $filename;
    } else {
        return 'img/default.jpg'; // Ảnh mặc định nếu chưa có
    }
}
echo '<table class="modal-table">';
echo '<thead><tr><th>Ảnh</th><th>Sản phẩm</th><th>Đơn giá</th><th>Số lượng</th><th>Thành tiền</th></tr></thead><tbody>';
while ($item = $result->fetch_assoc()) {
    echo '<tr>';
    echo '<td><img src="' . getImageUrl($item['IdSanPham']) . '" alt="' . htmlspecialchars($item['TenSanPham']) . '" style="width:50px;height:50px;object-fit:cover;border-radius:6px;"></td>';
    echo '<td>' . htmlspecialchars($item['TenSanPham']) . '</td>';
    echo '<td>' . formatPrice($item['Gia']) . '</td>';
    echo '<td>' . $item['SoLuong'] . '</td>';
    echo '<td>' . formatPrice($item['ThanhTien']) . '</td>';
    echo '</tr>';
}
echo '</tbody></table>'; 
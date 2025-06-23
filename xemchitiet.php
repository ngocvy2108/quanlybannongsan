<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "csdldoanchuyennganh";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

$id = isset($_GET['id']) ? trim($_GET['id']) : '';

if (!preg_match('/^SP[0-9]{2}$/', $id)) {
    echo '<div class="modal-body"><p>Mã sản phẩm không hợp lệ.</p></div>';
    exit;
}

$sql = "SELECT s.IdSanPham, s.TenSanPham, s.Gia, s.Loai, s.MoTa, s.SoLuongTonKho, n.DiaChi
        FROM sanpham s
        JOIN nguoiban n ON s.IdNguoiBan = n.IdNguoiBan
        WHERE s.IdSanPham = ?";

$stmt = $conn->prepare($sql);
if ($stmt === false) {
    echo '<div class="modal-body"><p>Lỗi chuẩn bị truy vấn: ' . $conn->error . '</p></div>';
    exit;
}

$stmt->bind_param("s", $id);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();

function convertToSlug($text) {
    $search = array(
        'à','á','ạ','ả','ã','â','ầ','ấ','ậ','ẩ','ẫ','ă','ằ','ắ','ặ','ẳ','ẵ',
        'è','é','ẹ','ẻ','ẽ','ê','ề','ế','ệ','ể','ễ',
        'ì','í','ị','ỉ','ĩ',
        'ò','ó','ọ','ỏ','õ','ô','ồ','ố','ộ','ổ','ỗ','ơ','ờ','ớ','ợ','ở','ỡ',
        'ù','ú','ụ','ủ','ũ','ư','ừ','ứ','ự','ử','ữ',
        'ỳ','ý','ỵ','ỷ','ỹ',
        'đ',
        ' '
    );
    $replace = array(
        'a','a','a','a','a','a','a','a','a','a','a','a','a','a','a','a','a',
        'e','e','e','e','e','e','e','e','e','e','e',
        'i','i','i','i','i',
        'o','o','o','o','o','o','o','o','o','o','o','o','o','o','o','o','o',
        'u','u','u','u','u','u','u','u','u','u','u',
        'y','y','y','y','y',
        'd',
        ''
    );
    
    $text = mb_strtolower($text, 'UTF-8');
    $text = str_replace($search, $replace, $text);
    $text = preg_replace('/[^a-z0-9]/', '', $text);
    
    return $text;
}

if ($product):
$imageUrl = 'img/' . strtolower($product['IdSanPham']) . '.jpg'; 
?>
<div class="product-modal">
    <div class="product-detail-container">
        <div class="product-detail-image">
            <img src="<?= $imageUrl ?>" alt="<?= htmlspecialchars($product['TenSanPham']) ?>" class="product-detail-image">
        </div>
        <div class="product-details">
            <h3 class="product-detail-name"><?= htmlspecialchars($product['TenSanPham']) ?></h3>
            <p class="product-detail-price" data-price="<?= $product['Gia'] ?>"><strong>Giá:</strong> <?= number_format($product['Gia'], 0, ',', '.') ?>₫</p>
            <p><strong>Loại sản phẩm:</strong> <?= htmlspecialchars($product['Loai']) ?></p>
            <p><strong>Xuất xứ:</strong> <?= htmlspecialchars($product['DiaChi']) ?></p>
            <p><strong>Số lượng tồn kho:</strong> <?= number_format($product['SoLuongTonKho'], 0, ',', '.') ?></p>
            <div class="product-description">
                <h4>Mô tả sản phẩm:</h4>
                <p><?= nl2br(htmlspecialchars($product['MoTa'])) ?></p>
            </div>
            <div class="product-actions">
                <div class="quantity-control">
                    <button class="quantity-btn" onclick="decreaseQuantity()">-</button>
                    <input type="number" id="product-quantity" value="1" min="1" class="quantity-input">
                    <button class="quantity-btn" onclick="increaseQuantity()">+</button>
                </div>
                <button class="btn btn-cart btn-add-to-cart">Thêm vào giỏ hàng</button>
            </div>
        </div>
    </div>
</div>

<script>
function increaseQuantity() {
    const input = document.getElementById('product-quantity');
    input.value = parseInt(input.value) + 1;
}

function decreaseQuantity() {
    const input = document.getElementById('product-quantity');
    if (parseInt(input.value) > 1) {
        input.value = parseInt(input.value) - 1;
    }
}
</script>

<style>
.product-modal {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

.product-modal .product-detail-container {
    display: flex;
    flex-direction: row;
    justify-content: center;
    align-items: flex-start;
    max-width: 1200px;
    margin: auto;
    background-color: #fff;
    border-radius: 12px;
    box-shadow: 0 6px 18px rgba(0, 0, 0, 0.1);
    overflow: hidden;
}

.product-modal .product-detail-image,
.product-modal .product-details {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    word-break: break-word;
}

.product-modal .product-detail-image {
    flex-shrink: 0;
    width: 300px;
    padding: 20px;
}

.product-modal .product-detail-image img {
    width: 100%;
    height: auto;
    border-radius: 10px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
    object-fit: cover;
}

.product-modal .product-price {
    font-size: 24px;
    color: #e63946;
    font-weight: bold;
    margin-bottom: 10px;
}

.product-modal .product-details p {
    margin: 6px 0;
    color: #333;
    font-size: 16px;
}

.product-modal .product-description {
    margin-top: 25px;
    border-top: 1px solid #ddd;
    padding-top: 15px;
}

.product-modal .product-description h4 {
    font-size: 18px;
    color: #444;
    margin-bottom: 8px;
}

.product-modal .product-description p {
    font-size: 15px;
    color: #555;
    line-height: 1.6;
}

.product-modal .product-actions {
    margin-top: 30px;
    display: flex;
    align-items: center;
    gap: 20px;
    flex-wrap: wrap;
}

.product-modal .quantity-control {
    display: flex;
    border: 1px solid #ccc;
    border-radius: 8px;
    overflow: hidden;
}

.product-modal .quantity-btn {
    background-color: #f0f0f0;
    border: none;
    padding: 10px 15px;
    cursor: pointer;
    font-size: 18px;
    color: #333;
}

.product-modal .quantity-btn:hover {
    background-color: #ddd;
}

.product-modal .quantity-input {
    width: 60px;
    text-align: center;
    border: none;
    font-size: 16px;
    padding: 10px 0;
    background-color: #fff;
    border-left: 1px solid #ccc;
    border-right: 1px solid #ccc;
}

.product-modal .btn-cart {
    background-color: #4CAF50;
    color: white;
    border: none;
    padding: 12px 24px;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    font-size: 16px;
    transition: background-color 0.3s ease;
}

.product-modal .btn-cart:hover {
    background-color: #45a049;
}

@media (max-width: 992px) {
    .product-modal .product-detail-container {
        flex-direction: column;
    }

    .product-modal .product-detail-image,
    .product-modal .product-details {
        width: 100%;
        padding: 20px;
    }

    .product-modal .product-actions {
        flex-direction: column;
        align-items: flex-start;
    }

    .product-modal .btn-cart {
        width: 100%;
        text-align: center;
    }

    .product-modal .quantity-control {
        width: 100%;
        justify-content: space-between;
    }
}
</style>

<?php else: ?>
    <div class="modal-body">
        <p>Không tìm thấy sản phẩm.</p>
    </div>
<?php 
endif;

$stmt->close();
$conn->close();
?>

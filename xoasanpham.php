<?php
include("config.php");

if (!isset($_GET['id'])) {
    header("Location: quanlysanpham.php");
    exit();
}

$id = $_GET['id'];

// Kiểm tra sản phẩm đã từng bán chưa
$result = $conn->query("SELECT COUNT(*) as total FROM chitietdonhang WHERE IdSanPham='$id'");
$row = $result->fetch_assoc();
$daTungBan = $row['total'] > 0;

$isAjax = isset($_GET['ajax']) && $_GET['ajax'] == 1;

// Nếu đã từng bán, hỏi xác nhận lại (dùng GET param confirm để xác nhận)
if ($daTungBan && !isset($_GET['confirm'])) {
    if ($isAjax) {
        echo "Sản phẩm đã từng bán, cần xác nhận.";
        exit();
    }
    // Hiển thị modal xác nhận đẹp mắt
    echo "\n    <html>\n    <head>\n        <meta charset='UTF-8'>\n        <style>\n            body { background: #f4f4f9; font-family: 'Segoe UI', Arial, sans-serif; }\n            .modal-xoa-content {\n                background: #fff;\n                border-radius: 20px;\n                padding: 36px 36px 28px 36px;\n                box-shadow: 0 8px 32px rgba(46,125,50,0.18);\n                text-align: center;\n                min-width: 340px;\n                max-width: 90vw;\n                margin: 120px auto;\n            }\n            .modal-xoa-content h3 { color: #e53935; margin-bottom: 18px; font-size: 1.35rem; font-weight: 700; }\n            .modal-xoa-content p { color: #333; font-size: 1.08rem; margin-bottom: 28px; }\n            .modal-xoa-actions { display: flex; justify-content: center; gap: 22px; }\n            .modal-xoa-content .nut {\n                min-width: 90px; padding: 12px 0; border-radius: 8px; font-size: 1.08rem; font-weight: 600;\n                box-shadow: 0 2px 8px rgba(67,160,71,0.08); border: none; cursor: pointer;\n                transition: background 0.2s, color 0.2s, box-shadow 0.2s;\n            }\n            .nut-xoa { background: #e53935; color: #fff; }\n            .nut-xoa:hover { background: #b71c1c; }\n            .nut-sua { background: linear-gradient(90deg, #43a047 60%, #257a2a 100%); color: #fff; }\n            .nut-sua:hover { background: linear-gradient(90deg, #257a2a 60%, #43a047 100%); }\n        </style>\n    </head>\n    <body>\n        <div class='modal-xoa-content'>\n            <h3>Xác nhận xóa sản phẩm đã từng bán</h3>\n            <p>Sản phẩm này đã từng xuất hiện trong đơn hàng. Bạn có chắc chắn muốn xóa không?</p>\n            <div class='modal-xoa-actions'>\n                <a href='xoasanpham.php?id=$id&confirm=1' class='nut nut-xoa'>Xóa</a>\n                <a href='banhangtrangchu.php' class='nut nut-sua'>Hủy</a>\n            </div>\n        </div>\n    </body>\n    </html>\n    ";
    exit();
}

// Xóa các bản ghi liên quan trong chitietdonhang trước
$conn->query("DELETE FROM chitietdonhang WHERE IdSanPham='$id'");

// Sau đó xóa sản phẩm
$sql = "DELETE FROM sanpham WHERE IdSanPham='$id'";
if ($conn->query($sql) === TRUE) {
    if ($isAjax) {
        echo "Xóa sản phẩm thành công";
        exit();
    }
    // Hiện modal thông báo đẹp mắt và chuyển hướng
    echo "\n    <html>\n    <head>\n        <meta charset='UTF-8'>\n        <style>\n            body { background: #f4f4f9; font-family: 'Segoe UI', Arial, sans-serif; }\n            .modal-xoa-content {\n                background: #fff;\n                border-radius: 20px;\n                padding: 36px 36px 28px 36px;\n                box-shadow: 0 8px 32px rgba(46,125,50,0.18);\n                text-align: center;\n                min-width: 340px;\n                max-width: 90vw;\n                margin: 120px auto;\n            }\n            .modal-xoa-content h3 { color: #43a047; margin-bottom: 18px; font-size: 1.35rem; font-weight: 700; }\n            .modal-xoa-content p { color: #333; font-size: 1.08rem; margin-bottom: 28px; }\n        </style>\n        <script>\n            setTimeout(function() {\n                window.location = 'banhangtrangchu.php';\n            }, 1800);\n        </script>\n    </head>\n    <body>\n        <div class='modal-xoa-content'>\n            <h3>Xóa sản phẩm thành công!</h3>\n            <p>Bạn sẽ được chuyển về trang quản lý sản phẩm.</p>\n        </div>\n    </body>\n    </html>\n    ";
} else {
    if ($isAjax) {
        echo "Lỗi: " . $conn->error;
        exit();
    }
    echo "\n    <html>\n    <head>\n        <meta charset='UTF-8'>\n        <style>\n            body { background: #f4f4f9; font-family: 'Segoe UI', Arial, sans-serif; }\n            .modal-xoa-content {\n                background: #fff;\n                border-radius: 20px;\n                padding: 36px 36px 28px 36px;\n                box-shadow: 0 8px 32px rgba(46,125,50,0.18);\n                text-align: center;\n                min-width: 340px;\n                max-width: 90vw;\n                margin: 120px auto;\n            }\n            .modal-xoa-content h3 { color: #e53935; margin-bottom: 18px; font-size: 1.35rem; font-weight: 700; }\n            .modal-xoa-content p { color: #333; font-size: 1.08rem; margin-bottom: 28px; }\n        </style>\n        <script>\n            setTimeout(function() {\n                window.location = 'banhangtrangchu.php';\n            }, 2500);\n        </script>\n    </head>\n    <body>\n        <div class='modal-xoa-content'>\n            <h3>Lỗi xóa sản phẩm!</h3>\n            <p>" . htmlspecialchars($conn->error) . "</p>\n        </div>\n    </body>\n    </html>\n    ";
}
?>

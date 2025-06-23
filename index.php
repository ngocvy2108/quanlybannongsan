<?php
include("config.php");
session_start();

$username = $password = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['ten_dang_nhap']);
    $password = trim($_POST['mat_khau']);

    if (empty($username)) {
        $error = "Tên đăng nhập không được để trống!";
    } elseif (empty($password)) {
        $error = "Mật khẩu không được để trống!";
    } else {
        $stmt = $conn->prepare("SELECT * FROM taikhoan WHERE TenDangNhap = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user && $password === $user['MatKhau']) {
            // Xác định loại tài khoản
            $loai_tai_khoan = null;
            $tk = $user['IdTaiKhoan'];

            // Kiểm tra khách hàng
            $stmtKH = $conn->prepare("SELECT IdKhachHang FROM khachhang WHERE IdTaiKhoan = ?");
            $stmtKH->bind_param("s", $tk);
            $stmtKH->execute();
            $resultKH = $stmtKH->get_result();
            if ($resultKH->fetch_assoc()) {
                $loai_tai_khoan = 'khachhang';
            }
            $stmtKH->close();

            // Kiểm tra người bán
            if (!$loai_tai_khoan) {
                $stmtNB = $conn->prepare("SELECT IdNguoiBan FROM nguoiban WHERE IdTaiKhoan = ?");
                $stmtNB->bind_param("s", $tk);
                $stmtNB->execute();
                $resultNB = $stmtNB->get_result();
                if ($rowNB = $resultNB->fetch_assoc()) {
                    $loai_tai_khoan = 'nguoiban';
                    $_SESSION['IdNguoiBan'] = $rowNB['IdNguoiBan'];
                    $user['IdNguoiBan'] = $rowNB['IdNguoiBan'];
                }
                $stmtNB->close();
            }

            // Kiểm tra quản lý
            if (!$loai_tai_khoan) {
                $stmtQL = $conn->prepare("SELECT IdNguoiQuanLy FROM quanly WHERE IdTaiKhoan = ?");
                $stmtQL->bind_param("s", $tk);
                $stmtQL->execute();
                $resultQL = $stmtQL->get_result();
                if ($resultQL->fetch_assoc()) {
                    $loai_tai_khoan = 'quanly';
                }
                $stmtQL->close();
            }

            // Lưu vào session
            $_SESSION['user'] = $user;
            $_SESSION['user']['LoaiTaiKhoan'] = $loai_tai_khoan;
            $_SESSION['TenDangNhap'] = $user['TenDangNhap'];

            // Điều hướng
            if ($loai_tai_khoan == 'quanly') {
                header("Location: quanlytrangchu.php");
                exit();
            } elseif ($loai_tai_khoan == 'nguoiban') {
                header("Location: banhangtrangchu.php");
                exit();
            } elseif ($loai_tai_khoan == 'khachhang') {
                header("Location: trangchukhachhang.php");
                exit();
            } else {
                $error = "Không xác định được loại tài khoản!";
            }
        } else {
            $error = "Sai tên đăng nhập hoặc mật khẩu!";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập - Nông sản Tiền Giang</title>
    <link rel="stylesheet" href="style.css"> 
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        /* Background Pattern */
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="20" cy="20" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="80" cy="40" r="1" fill="rgba(255,255,255,0.08)"/><circle cx="40" cy="80" r="1" fill="rgba(255,255,255,0.06)"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            opacity: 0.3;
        }

        .login-container {
            position: relative;
            z-index: 10;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
            padding: 48px 40px;
            width: 100%;
            max-width: 440px;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .brand-header {
            margin-bottom: 32px;
        }

        .brand-logo {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #4CAF50, #2E7D32);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
            box-shadow: 0 8px 32px rgba(76, 175, 80, 0.3);
        }

        .brand-logo i {
            font-size: 36px;
            color: white;
        }

        .brand-title {
            font-size: 28px;
            font-weight: 700;
            color: #1a1a1a;
            margin-bottom: 8px;
            letter-spacing: -0.5px;
        }

        .brand-subtitle {
            font-size: 16px;
            color: #6B7280;
            font-weight: 400;
        }

        .login-form {
            margin-top: 32px;
        }

        .form-group {
            margin-bottom: 24px;
            text-align: left;
        }

        .form-group label {
            display: block;
            font-size: 14px;
            font-weight: 500;
            color: #374151;
            margin-bottom: 8px;
        }

        .input-wrapper {
            position: relative;
        }

        .form-input {
            width: 100%;
            padding: 16px 20px 16px 50px;
            border: 2px solid #E5E7EB;
            border-radius: 12px;
            font-size: 16px;
            background: #FAFBFC;
            transition: all 0.3s ease;
            outline: none;
        }

        .form-input:focus {
            border-color: #4CAF50;
            background: white;
            box-shadow: 0 0 0 4px rgba(76, 175, 80, 0.1);
        }

        .input-icon {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 18px;
            color: #9CA3AF;
        }

        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 32px;
            font-size: 14px;
        }

        .remember-me {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .remember-me input[type="checkbox"] {
            width: 16px;
            height: 16px;
            accent-color: #4CAF50;
        }

        .forgot-password {
            color: #4CAF50;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .forgot-password:hover {
            color: #2E7D32;
        }

        .login-button {
            width: 100%;
            background: linear-gradient(135deg, #4CAF50, #2E7D32);
            color: white;
            border: none;
            border-radius: 12px;
            padding: 16px 24px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 16px rgba(76, 175, 80, 0.3);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .login-button:hover {
            background: linear-gradient(135deg, #2E7D32, #1B5E20);
            transform: translateY(-2px);
            box-shadow: 0 6px 24px rgba(76, 175, 80, 0.4);
        }

        .login-button:active {
            transform: translateY(0);
        }

        .divider {
            position: relative;
            margin: 32px 0;
            text-align: center;
        }

        .divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, #E5E7EB, transparent);
        }

        .divider span {
            background: rgba(255, 255, 255, 0.95);
            color: #9CA3AF;
            padding: 0 16px;
            font-size: 14px;
        }

        .register-link {
            font-size: 15px;
            color: #6B7280;
        }

        .register-link a {
            color: #4CAF50;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .register-link a:hover {
            color: #2E7D32;
        }

        .error-message {
            background: linear-gradient(135deg, #FF5252, #F44336);
            color: white;
            padding: 16px 20px;
            border-radius: 12px;
            margin-top: 24px;
            font-size: 14px;
            font-weight: 500;
            box-shadow: 0 4px 16px rgba(244, 67, 54, 0.2);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .error-message i {
            font-size: 18px;
        }

        /* Responsive Design */
        @media (max-width: 480px) {
            .login-container {
                margin: 20px;
                padding: 32px 24px;
            }
            
            .brand-title {
                font-size: 24px;
            }
            
            .form-input {
                padding: 14px 18px 14px 46px;
            }
        }

        /* Animation */
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .login-container {
            animation: slideUp 0.6s ease-out;
        }

        /* Floating Elements */
        .floating-shape {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            animation: float 6s ease-in-out infinite;
        }

        .floating-shape:nth-child(1) {
            top: 10%;
            left: 10%;
            width: 80px;
            height: 80px;
            animation-delay: 0s;
        }

        .floating-shape:nth-child(2) {
            top: 20%;
            right: 10%;
            width: 60px;
            height: 60px;
            animation-delay: 2s;
        }

        .floating-shape:nth-child(3) {
            bottom: 20%;
            left: 15%;
            width: 100px;
            height: 100px;
            animation-delay: 4s;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(10deg); }
        }
    </style>
</head>
<body>
    <!-- Floating Background Elements -->
    <div class="floating-shape"></div>
    <div class="floating-shape"></div>
    <div class="floating-shape"></div>

    <div class="login-container">
        <div class="brand-header">
            <div class="brand-logo">
                <i class="bi bi-shop"></i>
            </div>
            <h1 class="brand-title">Nông Sản Tiền Giang</h1>
            <p class="brand-subtitle">Kết nối nông dân - Phục vụ cộng đồng</p>
        </div>

        <form class="login-form" action="index.php" method="POST">
            <div class="form-group">
                <label for="ten_dang_nhap">Tên đăng nhập</label>
                <div class="input-wrapper">
                    <i class="bi bi-person input-icon"></i>
                    <input 
                        type="text" 
                        id="ten_dang_nhap"
                        name="ten_dang_nhap" 
                        class="form-input"
                        placeholder="Nhập tên đăng nhập của bạn"
                        required
                        value="<?php echo htmlspecialchars($username); ?>"
                    >
                </div>
            </div>

            <div class="form-group">
                <label for="mat_khau">Mật khẩu</label>
                <div class="input-wrapper">
                    <i class="bi bi-lock input-icon"></i>
                    <input 
                        type="password" 
                        id="mat_khau"
                        name="mat_khau" 
                        class="form-input"
                        placeholder="Nhập mật khẩu của bạn"
                        required
                    >
                </div>
            </div>

            <div class="form-options">
                <label class="remember-me">
                    <input type="checkbox" name="remember">
                    <span>Ghi nhớ đăng nhập</span>
                </label>
                <a href="#" class="forgot-password">Quên mật khẩu?</a>
            </div>

            <button type="submit" class="login-button" name="login">
                <i class="bi bi-box-arrow-in-right" style="margin-right: 8px;"></i>
                Đăng nhập
            </button>

            <div class="divider">
                <span>Hoặc</span>
            </div>

            <div class="register-link">
                Chưa có tài khoản? <a href="dangky.php">Đăng ký ngay</a>
            </div>

            <?php if (!empty($error)): ?>
                <div class="error-message">
                    <i class="bi bi-exclamation-triangle"></i>
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>
        </form>
    </div>
</body>
</html>
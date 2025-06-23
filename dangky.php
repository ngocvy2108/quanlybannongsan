<?php
include("config.php");
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Debug information
    error_reporting(E_ALL);
    ini_set('display_errors', 1);

    $vaitro = $_POST['VaiTro'];
    $tendangnhap = $_POST['TenDangNhap'];
    $matkhau = $_POST['MatKhau'];

    // Debug
    error_log("POST data: " . print_r($_POST, true));

    // Validate input
    if (empty($tendangnhap) || empty($matkhau) || empty($vaitro)) {
        echo "<script>alert('Vui lòng điền đầy đủ thông tin!'); window.location.href='dangky.php';</script>";
        exit();
    }

    // Kiểm tra trùng tên đăng nhập
    $check = $conn->prepare("SELECT * FROM taikhoan WHERE TenDangNhap = ?");
    $check->bind_param("s", $tendangnhap);
    $check->execute();
    $result = $check->get_result();
    if ($result->num_rows > 0) {
        echo "<script>alert('Tên đăng nhập đã tồn tại!'); window.location.href='dangky.php';</script>";
        exit();
    }

    // Bắt đầu transaction
    $conn->begin_transaction();

    try {
        // Tạo IdTaiKhoan tự động
        $result = $conn->query("SELECT IdTaiKhoan FROM taikhoan ORDER BY IdTaiKhoan DESC LIMIT 1");
        $lastIdTK = ($result->num_rows > 0) ? (int)substr($result->fetch_assoc()['IdTaiKhoan'], 2) + 1 : 1;
        $newIdTK = 'TK' . str_pad($lastIdTK, 2, '0', STR_PAD_LEFT);

        // Thêm vào bảng taikhoan
        $stmt = $conn->prepare("INSERT INTO taikhoan (IdTaiKhoan, TenDangNhap, MatKhau, VaiTro) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $newIdTK, $tendangnhap, $matkhau, $vaitro);
        if (!$stmt->execute()) {
            throw new Exception("Lỗi khi thêm tài khoản: " . $stmt->error);
        }

        // Id người quản lý mặc định
        $idNguoiQuanLy = "QL01";

        // Nếu là người bán
        if ($vaitro == "Bán hàng") {
            $ten = $_POST['tennguoiban'];
            $diachi = $_POST['diachi_banhang'];
            $sdt = $_POST['sdt_banhang'];
            $email = $_POST['email'];
            $mota = $_POST['motagianhang'];
            $ngaysinh = $_POST['ngaysinh_banhang'];

            // Debug
            error_log("Seller data: " . print_r([
                'ten' => $ten,
                'diachi' => $diachi,
                'sdt' => $sdt,
                'email' => $email,
                'mota' => $mota,
                'ngaysinh' => $ngaysinh
            ], true));

            // Validate input for seller
            if (empty($ten) || empty($diachi) || empty($sdt) || empty($email) || empty($ngaysinh)) {
                throw new Exception("Vui lòng điền đầy đủ thông tin người bán!");
            }

            $r = $conn->query("SELECT IdNguoiBan FROM nguoiban ORDER BY IdNguoiBan DESC LIMIT 1");
            $lastIdNB = ($r->num_rows > 0) ? (int)substr($r->fetch_assoc()['IdNguoiBan'], 2) + 1 : 1;
            $newIdNB = 'NB' . str_pad($lastIdNB, 2, '0', STR_PAD_LEFT);

            $stmt = $conn->prepare("INSERT INTO nguoiban (IdNguoiBan, TenNguoiBan, NgaySinh, DiaChi, SDT, Email, MoTaGianHang, NgayThamGia, IdTaiKhoan, IdNguoiQuanLy)
                                    VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?)");
            $stmt->bind_param("sssssssss", $newIdNB, $ten, $ngaysinh, $diachi, $sdt, $email, $mota, $newIdTK, $idNguoiQuanLy);
            if (!$stmt->execute()) {
                throw new Exception("Lỗi khi thêm thông tin người bán: " . $stmt->error);
            }
        }
        // Nếu là khách hàng
        elseif ($vaitro == "Khách hàng") {
            $ten = $_POST['tenkhachhang'];
            $diachi = $_POST['diachi_khachhang'];
            $sdt = $_POST['sdt_khachhang'];
            $email = $_POST['email_khachhang'];
            $ngaysinh = $_POST['ngaysinh_khachhang'];

            // Debug
            error_log("Customer data: " . print_r([
                'ten' => $ten,
                'diachi' => $diachi,
                'sdt' => $sdt,
                'email' => $email,
                'ngaysinh' => $ngaysinh
            ], true));

            // Validate input for customer
            if (empty($ten) || empty($diachi) || empty($sdt) || empty($email) || empty($ngaysinh)) {
                throw new Exception("Vui lòng điền đầy đủ thông tin khách hàng!");
            }

            $r = $conn->query("SELECT IdKhachHang FROM khachhang ORDER BY IdKhachHang DESC LIMIT 1");
            $lastIdKH = ($r->num_rows > 0) ? (int)substr($r->fetch_assoc()['IdKhachHang'], 2) + 1 : 1;
            $newIdKH = 'KH' . str_pad($lastIdKH, 2, '0', STR_PAD_LEFT);

            $stmt = $conn->prepare("INSERT INTO khachhang (IdKhachHang, TenKhachHang, NgaySinh, Email, DiaChi, SDT, IdNguoiQuanLy, IdTaiKhoan)
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssss", $newIdKH, $ten, $ngaysinh, $email, $diachi, $sdt, $idNguoiQuanLy, $newIdTK);
            if (!$stmt->execute()) {
                throw new Exception("Lỗi khi thêm thông tin khách hàng: " . $stmt->error);
            }
        }

        // Commit transaction nếu mọi thứ OK
        $conn->commit();
        echo "<script>alert('Đăng ký thành công!'); window.location.href='index.php';</script>";
        exit();

    } catch (Exception $e) {
        // Rollback nếu có lỗi
        $conn->rollback();
        error_log("Registration error: " . $e->getMessage());
        echo "<script>alert('Lỗi: " . $e->getMessage() . "'); window.location.href='dangky.php';</script>";
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký - Nông sản Tiền Giang</title>
    <link rel="stylesheet" href="styledangky.css">
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
            overflow-x: hidden;
            padding: 20px 0;
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

        .register-container {
            position: relative;
            z-index: 10;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
            padding: 48px 40px;
            width: 100%;
            max-width: 520px;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.2);
            animation: slideUp 0.6s ease-out;
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

        .register-form {
            margin-top: 32px;
            text-align: left;
        }

        .form-step {
            margin-bottom: 32px;
        }

        .step-title {
            font-size: 18px;
            font-weight: 600;
            color: #2E7D32;
            margin-bottom: 20px;
            padding-bottom: 8px;
            border-bottom: 2px solid #E8F5E8;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-group {
            margin-bottom: 20px;
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

        .form-input, .form-select {
            width: 100%;
            padding: 16px 20px 16px 50px;
            border: 2px solid #E5E7EB;
            border-radius: 12px;
            font-size: 16px;
            background: #FAFBFC;
            transition: all 0.3s ease;
            outline: none;
        }

        .form-select {
            cursor: pointer;
        }

        .form-input:focus, .form-select:focus {
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

        .role-cards {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 24px;
        }

        .role-card {
            position: relative;
            padding: 20px 16px;
            border: 2px solid #E5E7EB;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
            background: #FAFBFC;
        }

        .role-card:hover {
            border-color: #4CAF50;
            background: #F0F9F0;
        }

        .role-card.selected {
            border-color: #4CAF50;
            background: linear-gradient(135deg, #E8F5E8, #F0F9F0);
            box-shadow: 0 4px 16px rgba(76, 175, 80, 0.2);
        }

        .role-card input[type="radio"] {
            position: absolute;
            opacity: 0;
        }

        .role-icon {
            font-size: 32px;
            color: #4CAF50;
            margin-bottom: 8px;
        }

        .role-title {
            font-size: 16px;
            font-weight: 600;
            color: #1F2937;
            margin-bottom: 4px;
        }

        .role-desc {
            font-size: 12px;
            color: #6B7280;
        }

        .dynamic-form {
            display: none;
            animation: fadeInSlide 0.4s ease-out;
        }

        .dynamic-form.active {
            display: block;
        }

        .register-button {
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
            margin-top: 24px;
        }

        .register-button:hover {
            background: linear-gradient(135deg, #2E7D32, #1B5E20);
            transform: translateY(-2px);
            box-shadow: 0 6px 24px rgba(76, 175, 80, 0.4);
        }

        .register-button:active {
            transform: translateY(0);
        }

        .login-link {
            text-align: center;
            margin-top: 24px;
            font-size: 15px;
            color: #6B7280;
        }

        .login-link a {
            color: #4CAF50;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .login-link a:hover {
            color: #2E7D32;
        }

        /* Responsive Design */
        @media (max-width: 576px) {
            .register-container {
                margin: 20px;
                padding: 32px 24px;
            }
            
            .brand-title {
                font-size: 24px;
            }
            
            .form-input, .form-select {
                padding: 14px 18px 14px 46px;
            }

            .role-cards {
                grid-template-columns: 1fr;
            }
        }

        /* Animations */
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

        @keyframes fadeInSlide {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
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

        .error-message {
            color: #dc3545;
            font-size: 12px;
            margin-top: 5px;
            display: none;
            font-weight: 500;
        }

        .input-error {
            border-color: #dc3545 !important;
        }

        .input-error:focus {
            box-shadow: 0 0 0 4px rgba(220, 53, 69, 0.1) !important;
        }

        .input-success {
            border-color: #198754 !important;
        }

        .input-success:focus {
            box-shadow: 0 0 0 4px rgba(25, 135, 84, 0.1) !important;
        }
    </style>
    <script>
    function showForm() {
        var role = document.querySelector('input[name="VaiTro"]:checked')?.value;
        
        // Hide all dynamic forms
        document.querySelectorAll('.dynamic-form').forEach(form => {
            form.classList.remove('active');
        });

        // Show selected form
        if (role == "Bán hàng") {
            document.getElementById("form_banhang").classList.add('active');
        } else if (role == "Khách hàng") {
            document.getElementById("form_khachhang").classList.add('active');
        }
    }

    function selectRole(role) {
        // Update radio selection
        document.querySelector(`input[value="${role}"]`).checked = true;
        
        // Update visual selection
        document.querySelectorAll('.role-card').forEach(card => {
            card.classList.remove('selected');
        });
        event.currentTarget.classList.add('selected');
        
        // Show corresponding form
        showForm();
    }

    // Validation functions
    function validatePassword(password) {
        // Mật khẩu phải có ít nhất 8 ký tự, bao gồm chữ hoa, chữ thường, số và ký tự đặc biệt
        const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/;
        return passwordRegex.test(password);
    }

    function validateName(name) {
        // Tên chỉ được chứa chữ cái và khoảng trắng
        const nameRegex = /^[A-Za-zÀ-ỹ\s]+$/;
        return nameRegex.test(name);
    }

    function validatePhone(phone) {
        // Số điện thoại phải có 10 chữ số
        const phoneRegex = /^[0-9]{10}$/;
        return phoneRegex.test(phone);
    }

    function validateEmail(email) {
        // Kiểm tra định dạng email và chỉ chấp nhận domain @gmail.com
        const emailRegex = /^[a-zA-Z0-9._%+-]+@gmail\.com$/;
        return emailRegex.test(email);
    }

    function showError(inputElement, errorMessage) {
        const errorSpan = inputElement.parentElement.nextElementSibling;
        inputElement.classList.add('input-error');
        inputElement.classList.remove('input-success');
        errorSpan.style.display = 'block';
        errorSpan.textContent = errorMessage;
    }

    function showSuccess(inputElement) {
        const errorSpan = inputElement.parentElement.nextElementSibling;
        inputElement.classList.remove('input-error');
        inputElement.classList.add('input-success');
        errorSpan.style.display = 'none';
    }

    function validateInput(inputElement, validationFunction, errorMessage) {
        const value = inputElement.value.trim();
        if (!value) {
            showError(inputElement, 'Trường này không được để trống');
            return false;
        }
        if (!validationFunction(value)) {
            showError(inputElement, errorMessage);
            return false;
        }
        showSuccess(inputElement);
        return true;
    }

    // Add input event listeners
    document.addEventListener('DOMContentLoaded', function() {
        // Password validation
        const passwordInput = document.getElementById('MatKhau');
        if (passwordInput) {
            passwordInput.addEventListener('blur', function() {
                if (this.value.trim()) {
                    validateInput(this, validatePassword, 'Mật khẩu phải có ít nhất 8 ký tự, bao gồm chữ hoa, chữ thường, số và ký tự đặc biệt');
                }
            });
        }

        // Seller form validations
        const sellerNameInput = document.getElementById('tennguoiban');
        if (sellerNameInput) {
            sellerNameInput.addEventListener('blur', function() {
                if (this.value.trim()) {
                    validateInput(this, validateName, 'Tên chỉ được chứa chữ cái và khoảng trắng');
                }
            });
        }

        const sellerPhoneInput = document.getElementById('sdt_banhang');
        if (sellerPhoneInput) {
            sellerPhoneInput.addEventListener('blur', function() {
                if (this.value.trim()) {
                    validateInput(this, validatePhone, 'Số điện thoại phải có đúng 10 chữ số');
                }
            });
        }

        const sellerEmailInput = document.getElementById('email');
        if (sellerEmailInput) {
            sellerEmailInput.addEventListener('blur', function() {
                if (this.value.trim()) {
                    validateInput(this, validateEmail, 'Email phải có định dạng example@gmail.com');
                }
            });
        }

        // Customer form validations
        const customerNameInput = document.getElementById('tenkhachhang');
        if (customerNameInput) {
            customerNameInput.addEventListener('blur', function() {
                if (this.value.trim()) {
                    validateInput(this, validateName, 'Tên chỉ được chứa chữ cái và khoảng trắng');
                }
            });
        }

        const customerPhoneInput = document.getElementById('sdt_khachhang');
        if (customerPhoneInput) {
            customerPhoneInput.addEventListener('blur', function() {
                if (this.value.trim()) {
                    validateInput(this, validatePhone, 'Số điện thoại phải có đúng 10 chữ số');
                }
            });
        }

        const customerEmailInput = document.getElementById('email_khachhang');
        if (customerEmailInput) {
            customerEmailInput.addEventListener('blur', function() {
                if (this.value.trim()) {
                    validateInput(this, validateEmail, 'Email phải có định dạng example@gmail.com');
                }
            });
        }

        // Add input event listeners to clear error when user starts typing
        const allInputs = document.querySelectorAll('.form-input');
        allInputs.forEach(input => {
            input.addEventListener('input', function() {
                const errorSpan = this.parentElement.nextElementSibling;
                errorSpan.style.display = 'none';
                this.classList.remove('input-error');
            });
        });
    });

    // Update validateForm function to show all errors on submit
    function validateForm() {
        var vaitro = document.querySelector('input[name="VaiTro"]:checked')?.value;
        if (!vaitro) {
            alert('Vui lòng chọn loại tài khoản!');
            return false;
        }

        var isValid = true;
        var tenDangNhap = document.getElementById('TenDangNhap');
        var matKhau = document.getElementById('MatKhau');

        // Clear previous errors first
        document.querySelectorAll('.error-message').forEach(span => {
            span.style.display = 'none';
        });
        document.querySelectorAll('.form-input').forEach(input => {
            input.classList.remove('input-error');
        });

        if (!tenDangNhap.value.trim()) {
            showError(tenDangNhap, 'Vui lòng nhập tên đăng nhập');
            isValid = false;
        }

        if (!validateInput(matKhau, validatePassword, 'Mật khẩu phải có ít nhất 8 ký tự, bao gồm chữ hoa, chữ thường, số và ký tự đặc biệt')) {
            isValid = false;
        }

        if (vaitro === 'Bán hàng') {
            const fields = [
                { id: 'tennguoiban', validate: validateName, message: 'Tên chỉ được chứa chữ cái và khoảng trắng' },
                { id: 'sdt_banhang', validate: validatePhone, message: 'Số điện thoại phải có đúng 10 chữ số' },
                { id: 'email', validate: validateEmail, message: 'Email phải có định dạng example@gmail.com' }
            ];

            fields.forEach(field => {
                const input = document.getElementById(field.id);
                if (input.value.trim() && !validateInput(input, field.validate, field.message)) {
                    isValid = false;
                }
            });

            // Check other required fields
            ['ngaysinh_banhang', 'diachi_banhang', 'motagianhang'].forEach(id => {
                const input = document.getElementById(id);
                if (!input.value.trim()) {
                    showError(input, 'Trường này không được để trống');
                    isValid = false;
                }
            });

        } else if (vaitro === 'Khách hàng') {
            const fields = [
                { id: 'tenkhachhang', validate: validateName, message: 'Tên chỉ được chứa chữ cái và khoảng trắng' },
                { id: 'sdt_khachhang', validate: validatePhone, message: 'Số điện thoại phải có đúng 10 chữ số' },
                { id: 'email_khachhang', validate: validateEmail, message: 'Email phải có định dạng example@gmail.com' }
            ];

            fields.forEach(field => {
                const input = document.getElementById(field.id);
                if (input.value.trim() && !validateInput(input, field.validate, field.message)) {
                    isValid = false;
                }
            });

            // Check other required fields
            ['ngaysinh_khachhang', 'diachi_khachhang'].forEach(id => {
                const input = document.getElementById(id);
                if (!input.value.trim()) {
                    showError(input, 'Trường này không được để trống');
                    isValid = false;
                }
            });
        }

        return isValid;
    }
    </script>
</head>
<body>
    <!-- Floating Background Elements -->
    <div class="floating-shape"></div>
    <div class="floating-shape"></div>
    <div class="floating-shape"></div>

    <div class="register-container">
        <div class="brand-header">
            <div class="brand-logo">
                <i class="bi bi-person-plus"></i>
            </div>
            <h1 class="brand-title">Đăng Ký Tài Khoản</h1>
            <p class="brand-subtitle">Tham gia cộng đồng nông sản Tiền Giang</p>
        </div>

        <form class="register-form" action="" method="POST">
            <!-- Step 1: Account Type -->
            <div class="form-step">
                <div class="step-title">
                    <i class="bi bi-1-circle"></i>
                    Chọn loại tài khoản
                </div>
                
                <div class="role-cards">
                    <div class="role-card" onclick="selectRole('Bán hàng')">
                        <input type="radio" name="VaiTro" value="Bán hàng" required>
                        <div class="role-icon">
                            <i class="bi bi-shop"></i>
                        </div>
                        <div class="role-title">Người Bán</div>
                        <div class="role-desc">Bán nông sản trên nền tảng</div>
                    </div>
                    
                    <div class="role-card" onclick="selectRole('Khách hàng')">
                        <input type="radio" name="VaiTro" value="Khách hàng" required>
                        <div class="role-icon">
                            <i class="bi bi-person"></i>
                        </div>
                        <div class="role-title">Khách Hàng</div>
                        <div class="role-desc">Mua sắm nông sản tươi ngon</div>
                    </div>
                </div>
            </div>

            <!-- Step 2: Account Info -->
            <div class="form-step">
                <div class="step-title">
                    <i class="bi bi-2-circle"></i>
                    Thông tin tài khoản
                </div>

                <div class="form-group">
                    <label for="TenDangNhap">Tên đăng nhập</label>
                    <div class="input-wrapper">
                        <i class="bi bi-person input-icon"></i>
                        <input 
                            type="text" 
                            id="TenDangNhap"
                            name="TenDangNhap" 
                            class="form-input"
                            placeholder="Nhập tên đăng nhập"
                            required
                        >
                    </div>
                    <span class="error-message"></span>
                </div>

                <div class="form-group">
                    <label for="MatKhau">Mật khẩu</label>
                    <div class="input-wrapper">
                        <i class="bi bi-lock input-icon"></i>
                        <input 
                            type="password" 
                            id="MatKhau"
                            name="MatKhau" 
                            class="form-input"
                            placeholder="Nhập mật khẩu"
                            required
                        >
                    </div>
                    <span class="error-message"></span>
                </div>
            </div>

            <!-- Step 3: Personal Info -->
            <div class="form-step">
                <div class="step-title">
                    <i class="bi bi-3-circle"></i>
                    Thông tin cá nhân
                </div>

                <!-- Form Người Bán -->
                <div id="form_banhang" class="dynamic-form">
                    <div class="form-group">
                        <label for="tennguoiban">Tên người bán</label>
                        <div class="input-wrapper">
                            <i class="bi bi-person-badge input-icon"></i>
                            <input 
                                type="text" 
                                id="tennguoiban"
                                name="tennguoiban" 
                                class="form-input"
                                placeholder="Nhập tên đầy đủ"
                            >
                        </div>
                        <span class="error-message"></span>
                    </div>

                    <div class="form-group">
                        <label for="ngaysinh_banhang">Ngày sinh</label>
                        <div class="input-wrapper">
                            <i class="bi bi-calendar input-icon"></i>
                            <input 
                                type="date" 
                                id="ngaysinh_banhang"
                                name="ngaysinh_banhang" 
                                class="form-input"
                            >
                        </div>
                        <span class="error-message"></span>
                    </div>

                    <div class="form-group">
                        <label for="diachi_banhang">Địa chỉ</label>
                        <div class="input-wrapper">
                            <i class="bi bi-geo-alt input-icon"></i>
                            <input 
                                type="text" 
                                id="diachi_banhang"
                                name="diachi_banhang" 
                                class="form-input"
                                placeholder="Nhập địa chỉ"
                            >
                        </div>
                        <span class="error-message"></span>
                    </div>

                    <div class="form-group">
                        <label for="sdt_banhang">Số điện thoại</label>
                        <div class="input-wrapper">
                            <i class="bi bi-telephone input-icon"></i>
                            <input 
                                type="text" 
                                id="sdt_banhang"
                                name="sdt_banhang" 
                                class="form-input"
                                placeholder="Nhập số điện thoại"
                            >
                        </div>
                        <span class="error-message"></span>
                    </div>

                    <div class="form-group">
                        <label for="email">Email</label>
                        <div class="input-wrapper">
                            <i class="bi bi-envelope input-icon"></i>
                            <input 
                                type="email" 
                                id="email"
                                name="email" 
                                class="form-input"
                                placeholder="Nhập email"
                            >
                        </div>
                        <span class="error-message"></span>
                    </div>

                    <div class="form-group">
                        <label for="motagianhang">Mô tả gian hàng</label>
                        <div class="input-wrapper">
                            <i class="bi bi-journal-text input-icon"></i>
                            <input 
                                type="text" 
                                id="motagianhang"
                                name="motagianhang" 
                                class="form-input"
                                placeholder="Mô tả ngắn về gian hàng"
                            >
                        </div>
                        <span class="error-message"></span>
                    </div>
                </div>

                <!-- Form Khách Hàng -->
                <div id="form_khachhang" class="dynamic-form">
                    <div class="form-group">
                        <label for="tenkhachhang">Tên khách hàng</label>
                        <div class="input-wrapper">
                            <i class="bi bi-person input-icon"></i>
                            <input 
                                type="text" 
                                id="tenkhachhang"
                                name="tenkhachhang" 
                                class="form-input"
                                placeholder="Nhập tên đầy đủ"
                            >
                        </div>
                        <span class="error-message"></span>
                    </div>

                    <div class="form-group">
                        <label for="ngaysinh_khachhang">Ngày sinh</label>
                        <div class="input-wrapper">
                            <i class="bi bi-calendar input-icon"></i>
                            <input 
                                type="date" 
                                id="ngaysinh_khachhang"
                                name="ngaysinh_khachhang" 
                                class="form-input"
                            >
                        </div>
                        <span class="error-message"></span>
                    </div>

                    <div class="form-group">
                        <label for="diachi_khachhang">Địa chỉ</label>
                        <div class="input-wrapper">
                            <i class="bi bi-geo-alt input-icon"></i>
                            <input 
                                type="text" 
                                id="diachi_khachhang"
                                name="diachi_khachhang" 
                                class="form-input"
                                placeholder="Nhập địa chỉ"
                            >
                        </div>
                        <span class="error-message"></span>
                    </div>

                    <div class="form-group">
                        <label for="sdt_khachhang">Số điện thoại</label>
                        <div class="input-wrapper">
                            <i class="bi bi-telephone input-icon"></i>
                            <input 
                                type="text" 
                                id="sdt_khachhang"
                                name="sdt_khachhang" 
                                class="form-input"
                                placeholder="Nhập số điện thoại"
                            >
                        </div>
                        <span class="error-message"></span>
                    </div>

                    <div class="form-group">
                        <label for="email_khachhang">Email</label>
                        <div class="input-wrapper">
                            <i class="bi bi-envelope input-icon"></i>
                            <input 
                                type="email" 
                                id="email_khachhang"
                                name="email_khachhang" 
                                class="form-input"
                                placeholder="Nhập email"
                            >
                        </div>
                        <span class="error-message"></span>
                    </div>
                </div>
            </div>

            <button type="submit" class="register-button" onclick="return validateForm()">
                <i class="bi bi-person-plus" style="margin-right: 8px;"></i>
                Đăng ký tài khoản
            </button>

            <div class="login-link">
                Đã có tài khoản? <a href="index.php">Đăng nhập ngay</a>
            </div>
        </form>
    </div>
</body>
</html>
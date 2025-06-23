<?php
session_start();
include("config.php");

// Kiểm tra xem người dùng đã đăng nhập chưa
if (!isset($_SESSION['TenDangNhap'])) {
    echo "<h2>Vui lòng <a href='dangky.php'>đăng ký tài khoản</a> hoặc <a href='dangnhap.php'>đăng nhập</a> để xem thông tin.</h2>";
    exit();
}

$tendangnhap = $_SESSION['TenDangNhap'];

// Lấy thông tin tài khoản từ CSDL
$stmt = $conn->prepare("SELECT * FROM taikhoan WHERE TenDangNhap = ?");
$stmt->bind_param("s", $tendangnhap);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Xử lý đổi thông tin
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $old_password = $_POST['old_password'];
    $new_username = $_POST['new_username'];
    $new_password = $_POST['new_password'];

    // Kiểm tra mật khẩu cũ
    if ($old_password !== $user['MatKhau']) {
        echo "<script>alert('Mật khẩu cũ không đúng!');</script>";
    } else {
        // Cập nhật tên đăng nhập và mật khẩu
        $update = $conn->prepare("UPDATE taikhoan SET TenDangNhap = ?, MatKhau = ? WHERE IdTaiKhoan = ?");
        $update->bind_param("sss", $new_username, $new_password, $user['IdTaiKhoan']);
        if ($update->execute()) {
            $_SESSION['TenDangNhap'] = $new_username;
            echo "<script>alert('Cập nhật thành công!'); window.location.href='taikhoan.php';</script>";
            exit();
        } else {
            echo "<script>alert('Cập nhật thất bại!');</script>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Thông Tin Tài Khoản</title>
    <link rel="stylesheet" href="styletaikhoan.css">
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background-color: #f4f4f9;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 500px;
            margin: 40px auto;
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 4px 24px rgba(46,125,50,0.10);
            padding: 36px 32px 28px 32px;
        }
        h2 {
            color: #2e7d32;
            text-align: center;
            margin-bottom: 18px;
        }
        h3 {
            color: #2e7d32;
            margin-top: 30px;
            margin-bottom: 12px;
        }
        label {
            font-weight: 500;
            color: #2e7d32;
        }
        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 15px;
            margin-bottom: 12px;
            transition: border-color 0.2s;
            box-sizing: border-box;
        }
        input[type="text"]:focus,
        input[type="password"]:focus {
            border-color: #2e7d32;
            outline: none;
            box-shadow: 0 0 0 2px rgba(46,125,50,0.1);
        }
        button[type="submit"] {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 8px;
            background-color: #2e7d32;
            color: white;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }
        button[type="submit"]:hover {
            background-color: #1b5e20;
        }
        @media (max-width: 600px) {
            .container {
                margin: 20px;
                padding: 18px 8px 16px 8px;
            }
        }
        .error-message {
            color: #e53935;
            font-size: 14px;
            margin-top: 5px;
            display: none;
            font-weight: 500;
            padding-left: 2px;
        }

        .input-error {
            border-color: #e53935 !important;
            background-color: #ffebee !important;
        }

        .input-success {
            border-color: #2e7d32 !important;
            background-color: #e8f5e9 !important;
        }

        .input-error:focus {
            border-color: #e53935 !important;
            box-shadow: 0 0 0 2px rgba(229,57,53,0.1) !important;
        }

        .input-success:focus {
            border-color: #2e7d32 !important;
            box-shadow: 0 0 0 2px rgba(46,125,50,0.1) !important;
        }

        .requirement {
            color: #666;
            margin-bottom: 4px;
            font-size: 13px;
            transition: color 0.3s;
        }

        .requirement.valid {
            color: #2e7d32;
        }

        .requirement.invalid {
            color: #e53935;
        }

        #password-requirements {
            margin-top: 10px;
            padding: 10px;
            border-radius: 6px;
            background-color: #f5f5f5;
        }

        .toast {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background-color: #2e7d32;
            color: white;
            padding: 16px 28px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.18);
            display: flex;
            align-items: center;
            z-index: 9999;
            font-size: 1.08rem;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s, transform 0.3s;
            transform: translateY(40px);
        }

        .toast.show {
            opacity: 1;
            pointer-events: auto;
            transform: translateY(0);
        }

        .toast.error {
            background-color: #e53935;
        }

        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background-color: #fff;
            padding: 32px;
            border-radius: 12px;
            text-align: center;
            max-width: 400px;
            width: 90%;
        }

        .modal-buttons {
            display: flex;
            justify-content: center;
            gap: 16px;
            margin-top: 24px;
        }

        .modal-button {
            padding: 10px 24px;
            border-radius: 6px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .confirm-button {
            background-color: #2e7d32;
            color: white;
        }

        .confirm-button:hover {
            background-color: #1b5e20;
        }

        .cancel-button {
            background-color: #dc3545;
            color: white;
        }

        .cancel-button:hover {
            background-color: #bb2d3b;
        }
    </style>
    <script>
        function validateForm(event) {
            event.preventDefault(); // Ngăn form tự submit
            let isValid = true;
            const oldPassword = document.querySelector('input[name="old_password"]');
            const newUsername = document.querySelector('input[name="new_username"]');
            const newPassword = document.querySelector('input[name="new_password"]');

            // Validate old password
            if (!oldPassword.value.trim()) {
                showError(oldPassword, 'Vui lòng nhập mật khẩu cũ');
                isValid = false;
            } else if (oldPassword.value !== '<?php echo $user["MatKhau"]; ?>') {
                showError(oldPassword, 'Mật khẩu cũ không đúng');
                isValid = false;
            } else {
                showSuccess(oldPassword);
            }

            // Validate new username
            if (!newUsername.value.trim()) {
                showError(newUsername, 'Vui lòng nhập tên đăng nhập mới');
                isValid = false;
            } else if (newUsername.value.trim().length < 3) {
                showError(newUsername, 'Tên đăng nhập phải có ít nhất 3 ký tự');
                isValid = false;
            } else {
                showSuccess(newUsername);
            }

            // Validate new password
            const password = newPassword.value;
            const requirements = {
                length: password.length >= 8,
                lowercase: /[a-z]/.test(password),
                uppercase: /[A-Z]/.test(password),
                number: /[0-9]/.test(password),
                special: /[@$!%*?&]/.test(password)
            };

            // Update password requirements visual feedback
            document.querySelectorAll('#password-requirements .requirement').forEach((req, index) => {
                const icon = req.querySelector('i');
                const isValid = Object.values(requirements)[index];
                req.classList.toggle('valid', isValid);
                req.classList.toggle('invalid', !isValid);
                icon.className = isValid ? 'fas fa-check' : 'fas fa-times';
            });

            if (!password.trim()) {
                showError(newPassword, 'Vui lòng nhập mật khẩu mới');
                isValid = false;
            } else if (!Object.values(requirements).every(Boolean)) {
                showError(newPassword, 'Mật khẩu không đáp ứng các yêu cầu');
                isValid = false;
            } else {
                showSuccess(newPassword);
            }

            if (isValid) {
                document.getElementById('confirm-modal').style.display = 'flex';
            }

            return false;
        }

        function showError(input, message) {
            const errorSpan = input.nextElementSibling;
            errorSpan.textContent = message;
            errorSpan.style.display = 'block';
            input.classList.add('input-error');
            input.classList.remove('input-success');
        }

        function showSuccess(input) {
            const errorSpan = input.nextElementSibling;
            errorSpan.style.display = 'none';
            input.classList.remove('input-error');
            input.classList.add('input-success');
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Add input event listeners for real-time validation
            const inputs = document.querySelectorAll('input');
            inputs.forEach(input => {
                input.addEventListener('input', function() {
                    // Xóa thông báo lỗi khi người dùng bắt đầu nhập
                    const errorSpan = this.nextElementSibling;
                    errorSpan.style.display = 'none';
                    this.classList.remove('input-error');
                    
                    // Validate mật khẩu mới real-time
                    if (this.name === 'new_password') {
                        const password = this.value;
                        const requirements = {
                            length: password.length >= 8,
                            lowercase: /[a-z]/.test(password),
                            uppercase: /[A-Z]/.test(password),
                            number: /[0-9]/.test(password),
                            special: /[@$!%*?&]/.test(password)
                        };

                        // Update password requirements visual feedback
                        document.querySelectorAll('#password-requirements .requirement').forEach((req, index) => {
                            const icon = req.querySelector('i');
                            const isValid = Object.values(requirements)[index];
                            req.classList.toggle('valid', isValid);
                            req.classList.toggle('invalid', !isValid);
                            icon.className = isValid ? 'fas fa-check' : 'fas fa-times';
                        });
                    }
                    
                    // Validate mật khẩu cũ real-time
                    if (this.name === 'old_password' && this.value.trim()) {
                        if (this.value !== '<?php echo $user["MatKhau"]; ?>') {
                            showError(this, 'Mật khẩu cũ không đúng');
                        } else {
                            showSuccess(this);
                        }
                    }
                    
                    // Validate username real-time
                    if (this.name === 'new_username' && this.value.trim()) {
                        if (this.value.length < 3) {
                            showError(this, 'Tên đăng nhập phải có ít nhất 3 ký tự');
                        } else {
                            showSuccess(this);
                        }
                    }
                });
            });

            // Handle modal buttons
            document.getElementById('confirm-button').addEventListener('click', function() {
                document.getElementById('confirm-modal').style.display = 'none';
                document.querySelector('form').submit();
            });

            document.getElementById('cancel-button').addEventListener('click', function() {
                document.getElementById('confirm-modal').style.display = 'none';
            });

            // Close modal when clicking outside
            document.getElementById('confirm-modal').addEventListener('click', function(e) {
                if (e.target === this) {
                    this.style.display = 'none';
                }
            });
        });
    </script>
</head>
<body>
    <div class="container">
        <h2>Thông Tin Tài Khoản</h2>
        <p><strong>ID:</strong> <?= htmlspecialchars($user['IdTaiKhoan']) ?></p>
        <p><strong>Tên đăng nhập:</strong> <?= htmlspecialchars($user['TenDangNhap']) ?></p>

        <h3>Đổi thông tin tài khoản</h3>
        <form method="POST" onsubmit="return validateForm(event)">
            <label>Mật khẩu cũ:</label>
            <input type="password" name="old_password" required>
            <span class="error-message"></span>

            <label>Tên đăng nhập mới:</label>
            <input type="text" name="new_username" pattern=".{3,}" title="Tên đăng nhập phải có ít nhất 3 ký tự" required>
            <span class="error-message"></span>

            <label>Mật khẩu mới:</label>
            <input type="password" name="new_password" pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$" title="Mật khẩu phải có ít nhất 8 ký tự, bao gồm chữ hoa, chữ thường, số và ký tự đặc biệt" required>
            <span class="error-message"></span>
            
            <div id="password-requirements" class="password-requirements">
                <div class="requirement">
                    <i class="fas fa-check"></i> Ít nhất 8 ký tự
                </div>
                <div class="requirement">
                    <i class="fas fa-check"></i> Ít nhất 1 chữ thường
                </div>
                <div class="requirement">
                    <i class="fas fa-check"></i> Ít nhất 1 chữ hoa
                </div>
                <div class="requirement">
                    <i class="fas fa-check"></i> Ít nhất 1 số
                </div>
                <div class="requirement">
                    <i class="fas fa-check"></i> Ít nhất 1 ký tự đặc biệt (!@#$%^&*)
                </div>
            </div>

            <button type="submit">Cập nhật</button>
        </form>
    </div>

    <!-- Confirmation Modal -->
    <div id="confirm-modal" class="modal">
        <div class="modal-content">
            <h3>Xác nhận cập nhật</h3>
            <p>Bạn có chắc chắn muốn cập nhật thông tin tài khoản?</p>
            <div class="modal-buttons">
                <button id="confirm-button" class="modal-button confirm-button">Xác nhận</button>
                <button id="cancel-button" class="modal-button cancel-button">Hủy</button>
            </div>
        </div>
    </div>

    <!-- Add Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</body>
</html>

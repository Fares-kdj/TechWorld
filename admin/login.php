<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// إعادة التوجيه إذا كان مسجل دخول
if (isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit();
}

$error = '';

// تحميل إعدادات المتجر مباشرة من قاعدة البيانات
global $pdo;
try {
    $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    
    // جلب اسم الموقع (site_name وليس store_name)
    $stmt->execute(['site_name']);
    $result = $stmt->fetch();
    $storeName = $result ? $result['setting_value'] : 'TechWorld';
    
    // جلب شعار الموقع
    $stmt->execute(['site_logo']);
    $result = $stmt->fetch();
    // استخدام المسار الصحيح للشعار كافتراضي
    $storeLogo = $result ? $result['setting_value'] : 'assets/images/admin-logo.png';
} catch (Exception $e) {
    $storeName = 'TechWorld';
    $storeLogo = 'assets/images/admin-logo.png';
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = clean($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $error = 'يرجى ملء جميع الحقول';
    } else {
        global $pdo;

        $sql = "SELECT * FROM admins WHERE username = :username AND is_active = 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['username' => $username]);
        $admin = $stmt->fetch();
        
        if ($admin && password_verify($password, $admin['password'])) {
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['admin_name'] = $admin['name'];
            $_SESSION['admin_role'] = $admin['role'];
            
            // تحديث آخر دخول
            $updateSql = "UPDATE admins SET last_login = NOW() WHERE id = ?";
            $updateStmt = $pdo->prepare($updateSql);
            $updateStmt->execute([$admin['id']]);
            
            header('Location: index.php');
            exit();
        } else {
            $error = 'اسم المستخدم أو كلمة المرور غير صحيحة';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول - لوحة التحكم</title>
    <link rel="icon" type="image/png" href="../assets/images/favicon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .login-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
            max-width: 400px;
            width: 100%;
        }
        .login-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }
        .login-header i {
            font-size: 60px;
            margin-bottom: 15px;
        }
        .login-body {
            padding: 40px 30px;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 12px;
            font-size: 16px;
            font-weight: 600;
            transition: transform 0.2s;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-header">
            <?php if ($storeLogo): ?>
                <img src="../<?php echo htmlspecialchars($storeLogo); ?>" alt="<?php echo htmlspecialchars($storeName); ?>" style="max-width: 150px; max-height: 80px; margin-bottom: 15px;">
            <?php else: ?>
                <i class="bi bi-shop"></i>
            <?php endif; ?>
            <h2><?php echo htmlspecialchars($storeName); ?></h2>
            <p class="mb-0">لوحة التحكم</p>
        </div>
        <div class="login-body">
            <?php if ($error): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="bi bi-exclamation-triangle me-2"></i><?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="mb-3">
                    <label class="form-label">اسم المستخدم</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-person"></i></span>
                        <input type="text" class="form-control" name="username" required autofocus>
                    </div>
                </div>
                
                <div class="mb-4">
                    <label class="form-label">كلمة المرور</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" class="form-control" name="password" required>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary btn-login w-100">
                    <i class="bi bi-box-arrow-in-left me-2"></i>تسجيل الدخول
                </button>
            </form>
            
            <div class="text-center mt-3">
                <a href="forgot-password.php" class="text-decoration-none" style="color: #667eea; font-weight: 500;">
                    <i class="bi bi-key me-1"></i>نسيت كلمة المرور؟
                </a>
            </div>
            
            <hr class="my-3">
            
            <div class="text-center">
                <small class="text-muted">
                    <i class="bi bi-info-circle me-1"></i>
                    للحصول على بيانات الدخول، تواصل مع المدير الرئيسي
                </small>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

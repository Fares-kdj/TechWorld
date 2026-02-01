<?php
/**
 * صفحة نسيت كلمة المرور - حل دائم وآمن
 * Forgot Password Page
 */

session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// إعادة التوجيه إذا كان مسجل دخول
if (isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit();
}

$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$error = '';
$success = '';
$username = '';

// الخطوة 1: إدخال اسم المستخدم والتحقق من رمز الأمان
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $step == 1) {
    $username = clean($_POST['username']);
    $securityCode = clean($_POST['security_code']);
    
    if (empty($username) || empty($securityCode)) {
        $error = 'يرجى ملء جميع الحقول';
    } else {
        global $pdo;
        
        // التحقق من المستخدم ورمز الأمان من قاعدة البيانات
        $sql = "SELECT id, username, security_code FROM admins WHERE username = ? AND is_active = 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$username]);
        $admin = $stmt->fetch();
        
        if (!$admin) {
            $error = 'اسم المستخدم غير موجود';
        } else {
            // استخدام رمز الأمان من قاعدة البيانات، أو الرمز الافتراضي إذا لم يكن محدداً
            $userSecurityCode = $admin['security_code'] ?? '2026';
            
            if ($securityCode !== $userSecurityCode) {
                $error = 'رمز الأمان غير صحيح';
            } else {
                // حفظ المعلومات في الجلسة للخطوة التالية
                $_SESSION['reset_username'] = $username;
                $_SESSION['reset_admin_id'] = $admin['id'];
                header('Location: forgot-password.php?step=2');
                exit();
            }
        }
    }
}

// الخطوة 2: إدخال كلمة المرور الجديدة
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $step == 2) {
    if (!isset($_SESSION['reset_username'])) {
        header('Location: forgot-password.php?step=1');
        exit();
    }
    
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];
    
    if (empty($newPassword) || empty($confirmPassword)) {
        $error = 'يرجى ملء جميع الحقول';
    } elseif (strlen($newPassword) < 6) {
        $error = 'كلمة المرور يجب أن تكون 6 أحرف على الأقل';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'كلمتا المرور غير متطابقتين';
    } else {
        global $pdo;
        
        try {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            
            $sql = "UPDATE admins SET password = ? WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$hashedPassword, $_SESSION['reset_admin_id']]);
            
            // تنظيف الجلسة
            unset($_SESSION['reset_username']);
            unset($_SESSION['reset_admin_id']);
            
            header('Location: forgot-password.php?step=3');
            exit();
        } catch (PDOException $e) {
            $error = 'حدث خطأ أثناء تحديث كلمة المرور';
        }
    }
}

$username = isset($_SESSION['reset_username']) ? $_SESSION['reset_username'] : '';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نسيت كلمة المرور - لوحة التحكم</title>
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
        .forgot-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
            max-width: 500px;
            width: 100%;
            animation: slideUp 0.5s ease;
        }
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
        .forgot-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }
        .forgot-header i {
            font-size: 60px;
            margin-bottom: 15px;
        }
        .forgot-body {
            padding: 40px 30px;
        }
        .step-indicator {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
            position: relative;
        }
        .step {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: #6c757d;
            position: relative;
            z-index: 1;
        }
        .step.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .step.completed {
            background: #28a745;
            color: white;
        }
        .step-connector {
            flex: 1;
            height: 2px;
            background: #e9ecef;
            margin: 0 10px;
            align-self: center;
        }
        .step-connector.completed {
            background: #28a745;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 12px;
            font-size: 16px;
            font-weight: 600;
        }
        .info-box {
            background: #e7f3ff;
            border-left: 4px solid #2196F3;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .security-tip {
            background: #fff3cd;
            border: 1px solid #ffc107;
            padding: 12px;
            border-radius: 8px;
            font-size: 14px;
            margin-top: 15px;
        }
    </style>
</head>
<body>
    <div class="forgot-card">
        <div class="forgot-header">
            <i class="bi bi-key"></i>
            <h2>نسيت كلمة المرور</h2>
            <p class="mb-0">TechWorld - لوحة التحكم</p>
        </div>
        
        <div class="forgot-body">
            <?php if ($step <= 2): ?>
            <!-- مؤشر الخطوات -->
            <div class="step-indicator">
                <div class="step <?php echo $step >= 1 ? 'active' : ''; ?>">1</div>
                <div class="step-connector <?php echo $step > 1 ? 'completed' : ''; ?>"></div>
                <div class="step <?php echo $step >= 2 ? 'active' : ''; ?> <?php echo $step > 2 ? 'completed' : ''; ?>">2</div>
                <div class="step-connector"></div>
                <div class="step">3</div>
            </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="bi bi-exclamation-triangle me-2"></i><?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($step == 1): ?>
                <!-- الخطوة 1: التحقق من الهوية -->
                <h5 class="mb-3">التحقق من الهوية</h5>
                
                <div class="info-box">
                    <i class="bi bi-info-circle me-2"></i>
                    <small>أدخل اسم المستخدم ورمز الأمان للمتابعة</small>
                </div>
                
                <form method="POST" action="">
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="bi bi-person me-2"></i>اسم المستخدم
                        </label>
                        <input type="text" 
                               class="form-control form-control-lg" 
                               name="username" 
                               required 
                               autofocus
                               placeholder="admin">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="bi bi-shield-lock me-2"></i>رمز الأمان
                        </label>
                        <input type="text" 
                               class="form-control form-control-lg" 
                               name="security_code" 
                               required
                               placeholder="أدخل رمز الأمان">
                        <small class="text-muted">تواصل مع المدير الرئيسي لتزويدك بالرقم</small>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-arrow-left me-2"></i>التالي
                    </button>
                </form>
                
                <div class="security-tip">
                    <i class="bi bi-lightbulb me-2"></i>
                    <strong>نصيحة:</strong> تواصل مع المدير الرئيسي لمعرفة رمز الأمان.
                </div>
                
            <?php elseif ($step == 2): ?>
                <!-- الخطوة 2: إدخال كلمة المرور الجديدة -->
                <h5 class="mb-3">كلمة المرور الجديدة</h5>
                
                <div class="info-box">
                    <i class="bi bi-person-check me-2"></i>
                    <small>المستخدم: <strong><?php echo htmlspecialchars($username); ?></strong></small>
                </div>
                
                <form method="POST" action="">
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="bi bi-lock me-2"></i>كلمة المرور الجديدة
                        </label>
                        <input type="password" 
                               class="form-control form-control-lg" 
                               name="new_password" 
                               required 
                               autofocus
                               minlength="6"
                               placeholder="أدخل كلمة مرور جديدة">
                        <small class="text-muted">يجب أن تكون 6 أحرف على الأقل</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="bi bi-lock-fill me-2"></i>تأكيد كلمة المرور
                        </label>
                        <input type="password" 
                               class="form-control form-control-lg" 
                               name="confirm_password" 
                               required
                               minlength="6"
                               placeholder="أعد إدخال كلمة المرور">
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle me-2"></i>إعادة تعيين كلمة المرور
                        </button>
                        <a href="forgot-password.php?step=1" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-right me-2"></i>رجوع
                        </a>
                    </div>
                </form>
                
            <?php elseif ($step == 3): ?>
                <!-- الخطوة 3: النجاح -->
                <div class="text-center">
                    <div style="font-size: 80px; color: #28a745; margin-bottom: 20px;">
                        <i class="bi bi-check-circle"></i>
                    </div>
                    
                    <h4 class="text-success mb-3">تم بنجاح!</h4>
                    
                    <div class="alert alert-success">
                        <i class="bi bi-info-circle me-2"></i>
                        تم إعادة تعيين كلمة المرور بنجاح
                    </div>
                    
                    <p class="text-muted mb-4">يمكنك الآن تسجيل الدخول بكلمة المرور الجديدة</p>
                    
                    <a href="login.php" class="btn btn-primary btn-lg">
                        <i class="bi bi-box-arrow-in-left me-2"></i>تسجيل الدخول
                    </a>
                </div>
            <?php endif; ?>
            
            <?php if ($step < 3): ?>
            <hr class="my-4">
            <div class="text-center">
                <a href="login.php" class="text-muted">
                    <i class="bi bi-arrow-right me-2"></i>العودة لصفحة تسجيل الدخول
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // التحقق من تطابق كلمات المرور
        const form = document.querySelector('form');
        if (form && form.querySelector('[name="confirm_password"]')) {
            form.addEventListener('submit', function(e) {
                const password = form.querySelector('[name="new_password"]').value;
                const confirm = form.querySelector('[name="confirm_password"]').value;
                
                if (password !== confirm) {
                    e.preventDefault();
                    alert('كلمتا المرور غير متطابقتين!');
                    return false;
                }
            });
        }
    </script>
</body>
</html>

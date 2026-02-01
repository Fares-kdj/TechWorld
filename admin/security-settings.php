<?php
/**
 * إعدادات الأمان - تغيير رمز الأمان
 * Security Settings - Change Security Code
 */

$pageTitle = 'إعدادات الأمان';
include 'includes/header.php';

// التحقق من الصلاحيات - فقط للمدير الرئيسي
if ($_SESSION['admin_role'] !== 'super_admin') {
    setFlashMessage('error', 'ليس لديك صلاحية للوصول إلى هذه الصفحة');
    header('Location: index.php');
    exit;
}

global $pdo;

$success = '';
$error = '';

// الحصول على معلومات الأدمن الحالي
$adminId = $_SESSION['admin_id'];
$stmt = $pdo->prepare("SELECT username, security_code FROM admins WHERE id = ?");
$stmt->execute([$adminId]);
$admin = $stmt->fetch();

// معالجة تغيير رمز الأمان
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'change_security_code') {
    $currentPassword = $_POST['current_password'];
    $newSecurityCode = clean($_POST['new_security_code']);
    $confirmSecurityCode = clean($_POST['confirm_security_code']);
    
    // التحقق من كلمة المرور الحالية
    $stmt = $pdo->prepare("SELECT password FROM admins WHERE id = ?");
    $stmt->execute([$adminId]);
    $adminData = $stmt->fetch();
    
    if (!password_verify($currentPassword, $adminData['password'])) {
        $error = 'كلمة المرور الحالية غير صحيحة';
    } elseif (empty($newSecurityCode)) {
        $error = 'يرجى إدخال رمز الأمان الجديد';
    } elseif (strlen($newSecurityCode) < 4) {
        $error = 'رمز الأمان يجب أن يكون 4 أحرف على الأقل';
    } elseif ($newSecurityCode !== $confirmSecurityCode) {
        $error = 'رمز الأمان غير متطابق';
    } else {
        // تحديث رمز الأمان
        $updateStmt = $pdo->prepare("UPDATE admins SET security_code = ? WHERE id = ?");
        $updateStmt->execute([$newSecurityCode, $adminId]);
        
        $success = 'تم تغيير رمز الأمان بنجاح!';
        
        // تحديث البيانات المحلية
        $admin['security_code'] = $newSecurityCode;
    }
}

// معالجة تغيير كلمة المرور
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'change_password') {
    $currentPassword = $_POST['current_password_pass'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];
    
    // التحقق من كلمة المرور الحالية
    $stmt = $pdo->prepare("SELECT password FROM admins WHERE id = ?");
    $stmt->execute([$adminId]);
    $adminData = $stmt->fetch();
    
    if (!password_verify($currentPassword, $adminData['password'])) {
        $error = 'كلمة المرور الحالية غير صحيحة';
    } elseif (empty($newPassword)) {
        $error = 'يرجى إدخال كلمة المرور الجديدة';
    } elseif (strlen($newPassword) < 6) {
        $error = 'كلمة المرور يجب أن تكون 6 أحرف على الأقل';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'كلمة المرور غير متطابقة';
    } else {
        // تحديث كلمة المرور
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $updateStmt = $pdo->prepare("UPDATE admins SET password = ? WHERE id = ?");
        $updateStmt->execute([$hashedPassword, $adminId]);
        
        $success = 'تم تغيير كلمة المرور بنجاح!';
    }
}
?>

<div class="container-fluid px-4">
    <div class="row mb-4">
        <div class="col">
            <h1 class="h3 mb-0">
                <i class="bi bi-shield-lock me-2"></i>إعدادات الأمان
            </h1>
            <p class="text-muted">إدارة رمز الأمان وكلمة المرور</p>
        </div>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i><?php echo $success; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i><?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- رمز الأمان -->
        <div class="col-lg-6">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-key me-2"></i>رمز الأمان
                    </h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        <small>
                            رمز الأمان يُستخدم لاستعادة كلمة المرور عند نسيانها.
                            احتفظ به في مكان آمن!
                        </small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">رمز الأمان الحالي:</label>
                        <div class="input-group">
                            <input type="password" 
                                   class="form-control" 
                                   id="currentSecurityCode" 
                                   value="<?php echo htmlspecialchars($admin['security_code'] ?? '2026'); ?>" 
                                   readonly>
                            <button class="btn btn-outline-secondary" 
                                    type="button" 
                                    onclick="togglePasswordVisibility('currentSecurityCode', this)">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>

                    <hr>

                    <h6 class="mb-3">تغيير رمز الأمان</h6>
                    <form method="POST">
                        <input type="hidden" name="action" value="change_security_code">
                        
                        <div class="mb-3">
                            <label class="form-label">كلمة المرور الحالية</label>
                            <input type="password" 
                                   class="form-control" 
                                   name="current_password" 
                                   required
                                   placeholder="للتأكيد">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">رمز الأمان الجديد</label>
                            <input type="text" 
                                   class="form-control" 
                                   name="new_security_code" 
                                   required
                                   minlength="4"
                                   placeholder="أدخل رمز الأمان الجديد">
                            <small class="text-muted">يجب أن يكون 4 أحرف على الأقل</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">تأكيد رمز الأمان</label>
                            <input type="text" 
                                   class="form-control" 
                                   name="confirm_security_code" 
                                   required
                                   minlength="4"
                                   placeholder="أعد إدخال رمز الأمان">
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-check-circle me-2"></i>حفظ رمز الأمان
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- تغيير كلمة المرور -->
        <div class="col-lg-6">
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-lock me-2"></i>كلمة المرور
                    </h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <small>
                            تأكد من استخدام كلمة مرور قوية تحتوي على أحرف وأرقام
                        </small>
                    </div>

                    <form method="POST">
                        <input type="hidden" name="action" value="change_password">
                        
                        <div class="mb-3">
                            <label class="form-label">كلمة المرور الحالية</label>
                            <input type="password" 
                                   class="form-control" 
                                   name="current_password_pass" 
                                   required
                                   placeholder="أدخل كلمة المرور الحالية">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">كلمة المرور الجديدة</label>
                            <input type="password" 
                                   class="form-control" 
                                   name="new_password" 
                                   required
                                   minlength="6"
                                   placeholder="أدخل كلمة المرور الجديدة">
                            <small class="text-muted">يجب أن تكون 6 أحرف على الأقل</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">تأكيد كلمة المرور</label>
                            <input type="password" 
                                   class="form-control" 
                                   name="confirm_password" 
                                   required
                                   minlength="6"
                                   placeholder="أعد إدخال كلمة المرور">
                        </div>

                        <button type="submit" class="btn btn-success w-100">
                            <i class="bi bi-check-circle me-2"></i>حفظ كلمة المرور
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- نصائح الأمان -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card shadow-sm border-warning">
                <div class="card-header bg-warning">
                    <h5 class="mb-0">
                        <i class="bi bi-lightbulb me-2"></i>نصائح الأمان
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6><i class="bi bi-key text-primary me-2"></i>رمز الأمان:</h6>
                            <ul>
                                <li>استخدم رمز فريد يسهل تذكره</li>
                                <li>لا تشارك الرمز مع أي شخص</li>
                                <li>احفظه في مكان آمن</li>
                                <li>غيّره بشكل دوري</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6><i class="bi bi-lock text-success me-2"></i>كلمة المرور:</h6>
                            <ul>
                                <li>استخدم 8 أحرف أو أكثر</li>
                                <li>امزج بين الأحرف والأرقام</li>
                                <li>أضف رموز خاصة (@#$%)</li>
                                <li>غيّرها بانتظام</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function togglePasswordVisibility(inputId, button) {
    const input = document.getElementById(inputId);
    const icon = button.querySelector('i');
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('bi-eye');
        icon.classList.add('bi-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('bi-eye-slash');
        icon.classList.add('bi-eye');
    }
}
</script>

<style>
.card {
    border-radius: 15px;
    border: none;
}
.card-header {
    border-radius: 15px 15px 0 0 !important;
    padding: 20px;
}
</style>

<?php include 'includes/footer.php'; ?>

<?php
ob_start();
session_start();
require_once 'includes/functions.php';
global $pdo;

// التحقق من الصلاحيات
if ($_SESSION['admin_role'] !== 'super_admin') {
    setFlashMessage('error', 'ليس لديك صلاحية للوصول إلى هذه الصفحة');
    header('Location: index.php');
    exit;
}

// معالجة الحذف قبل الإخراج
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    // منع حذف الحساب الحالي
    if ($id == $_SESSION['admin_id']) {
        setFlashMessage('error', 'لا يمكنك حذف حسابك الخاص');
    } else {
        $stmt = $pdo->prepare("DELETE FROM admins WHERE id = ?");
        if ($stmt->execute([$id])) {
            setFlashMessage('success', 'تم حذف المدير بنجاح');
        }
    }
    
    header('Location: admins.php');
    exit;
}

// معالجة الإضافة/التعديل قبل الإخراج
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitizeInput($_POST['name']);
    $email = sanitizeInput($_POST['email']);
    $username = sanitizeInput($_POST['username']);
    $role = sanitizeInput($_POST['role']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    if (isset($_POST['admin_id']) && !empty($_POST['admin_id'])) {
        // تحديث
        $id = (int)$_POST['admin_id'];
        
        // إذا تم إدخال كلمة مرور جديدة
        if (!empty($_POST['password'])) {
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE admins SET name = ?, email = ?, username = ?, password = ?, role = ?, is_active = ? WHERE id = ?");
            $stmt->execute([$name, $email, $username, $password, $role, $is_active, $id]);
        } else {
            $stmt = $pdo->prepare("UPDATE admins SET name = ?, email = ?, username = ?, role = ?, is_active = ? WHERE id = ?");
            $stmt->execute([$name, $email, $username, $role, $is_active, $id]);
        }
        
        setFlashMessage('success', 'تم تحديث معلومات المدير بنجاح');
    } else {
        // إضافة
        if (empty($_POST['password'])) {
            setFlashMessage('error', 'كلمة المرور مطلوبة للمدير الجديد');
            header('Location: admins.php');
            exit;
        }
        
        // التحقق من عدم تكرار اسم المستخدم
        $stmt = $pdo->prepare("SELECT id FROM admins WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            setFlashMessage('error', 'اسم المستخدم موجود مسبقاً');
            header('Location: admins.php');
            exit;
        }
        
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO admins (name, email, username, password, role, is_active) VALUES (?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$name, $email, $username, $password, $role, $is_active])) {
            setFlashMessage('success', 'تمت إضافة المدير بنجاح');
        }
    }
    
    header('Location: admins.php');
    exit;
}

$pageTitle = 'إدارة المديرين';
include 'includes/header.php';

// جلب جميع المديرين
$admins = $pdo->query("SELECT * FROM admins ORDER BY created_at DESC")->fetchAll();

// إذا كان هناك تعديل
$editAdmin = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $editAdmin = $stmt->fetch();
}
?>

<div class="row g-4">
    <!-- نموذج إضافة/تعديل -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-<?php echo $editAdmin ? 'pencil' : 'plus'; ?>-circle me-2"></i>
                    <?php echo $editAdmin ? 'تعديل المدير' : 'إضافة مدير جديد'; ?>
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <?php if ($editAdmin): ?>
                        <input type="hidden" name="admin_id" value="<?php echo $editAdmin['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="mb-3">
                        <label class="form-label">الاسم الكامل <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" 
                               value="<?php echo $editAdmin['name'] ?? ''; ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">البريد الإلكتروني</label>
                        <input type="email" class="form-control" name="email" 
                               value="<?php echo $editAdmin['email'] ?? ''; ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">اسم المستخدم <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="username" 
                               value="<?php echo $editAdmin['username'] ?? ''; ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">
                            كلمة المرور 
                            <?php if (!$editAdmin): ?>
                                <span class="text-danger">*</span>
                            <?php endif; ?>
                        </label>
                        <input type="password" class="form-control" name="password" 
                               <?php echo !$editAdmin ? 'required' : ''; ?>>
                        <?php if ($editAdmin): ?>
                            <small class="text-muted">اتركها فارغة إذا لم ترد تغيير كلمة المرور</small>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">الصلاحية <span class="text-danger">*</span></label>
                        <select class="form-select" name="role" required>
                            <option value="admin" <?php echo (isset($editAdmin) && $editAdmin['role'] == 'admin') ? 'selected' : ''; ?>>
                                مدير
                            </option>
                            <option value="super_admin" <?php echo (isset($editAdmin) && $editAdmin['role'] == 'super_admin') ? 'selected' : ''; ?>>
                                مدير رئيسي
                            </option>
                        </select>
                        <small class="text-muted">
                            المدير الرئيسي له صلاحيات كاملة بما في ذلك إدارة المديرين
                        </small>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_active" id="is_active"
                                   <?php echo (!isset($editAdmin) || $editAdmin['is_active']) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="is_active">
                                حساب نشط
                            </label>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle me-2"></i>
                            <?php echo $editAdmin ? 'تحديث' : 'إضافة'; ?>
                        </button>
                        <?php if ($editAdmin): ?>
                            <a href="admins.php" class="btn btn-secondary">
                                <i class="bi bi-x-circle me-2"></i>إلغاء
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- معلومات الأدوار -->
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-info-circle me-2"></i>معلومات الصلاحيات
                </h5>
            </div>
            <div class="card-body">
                <div class="role-info">
                    <div class="role-item">
                        <strong>مدير رئيسي:</strong>
                        <ul class="mt-2">
                            <li>صلاحيات كاملة</li>
                            <li>إدارة المديرين</li>
                            <li>تعديل الإعدادات</li>
                            <li>عرض التقارير</li>
                        </ul>
                    </div>
                    <div class="role-item">
                        <strong>مدير:</strong>
                        <ul class="mt-2">
                            <li>إدارة المنتجات</li>
                            <li>إدارة الطلبات</li>
                            <li>عرض العملاء</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- قائمة المديرين -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="bi bi-shield-lock me-2"></i>جميع المديرين
                </h5>
                <span class="badge bg-primary"><?php echo count($admins); ?> مدير</span>
            </div>
            <div class="card-body">
                <?php if (empty($admins)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-inbox" style="font-size: 48px; color: #ccc;"></i>
                        <p class="text-muted mt-3">لا يوجد مديرين</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>المدير</th>
                                    <th>اسم المستخدم</th>
                                    <th>البريد</th>
                                    <th>الصلاحية</th>
                                    <th>الحالة</th>
                                    <th>تاريخ الإضافة</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($admins as $admin): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="admin-avatar-sm me-2">
                                                    <?php echo mb_substr($admin['name'], 0, 2); ?>
                                                </div>
                                                <div>
                                                    <strong><?php echo htmlspecialchars($admin['name']); ?></strong>
                                                    <?php if ($admin['id'] == $_SESSION['admin_id']): ?>
                                                        <span class="badge bg-info ms-2">أنت</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <code><?php echo htmlspecialchars($admin['username']); ?></code>
                                        </td>
                                        <td>
                                            <?php if ($admin['email']): ?>
                                                <small><?php echo htmlspecialchars($admin['email']); ?></small>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($admin['role'] == 'super_admin'): ?>
                                                <span class="badge bg-danger">
                                                    <i class="bi bi-star-fill"></i> مدير رئيسي
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-primary">
                                                    <i class="bi bi-person-badge"></i> مدير
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($admin['is_active']): ?>
                                                <span class="badge bg-success">نشط</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">معطل</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <small><?php echo date('Y-m-d', strtotime($admin['created_at'])); ?></small>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="?edit=<?php echo $admin['id']; ?>" 
                                                   class="btn btn-outline-primary" title="تعديل">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <?php if ($admin['id'] != $_SESSION['admin_id']): ?>
                                                    <a href="?delete=<?php echo $admin['id']; ?>" 
                                                       class="btn btn-outline-danger"
                                                       onclick="return confirm('هل أنت متأكد من حذف هذا المدير؟')" 
                                                       title="حذف">
                                                        <i class="bi bi-trash"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- سجل آخر النشاطات -->
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-clock-history me-2"></i>آخر تسجيلات الدخول
                </h5>
            </div>
            <div class="card-body">
                <div class="activity-log">
                    <?php
                    // جلب آخر تسجيلات الدخول (يمكنك إنشاء جدول منفصل لتتبع النشاطات)
                    $recentAdmins = array_slice($admins, 0, 5);
                    ?>
                    <?php if (!empty($recentAdmins)): ?>
                        <?php foreach ($recentAdmins as $admin): ?>
                            <div class="activity-item">
                                <div class="admin-avatar-xs">
                                    <?php echo mb_substr($admin['name'], 0, 2); ?>
                                </div>
                                <div class="activity-content">
                                    <strong><?php echo htmlspecialchars($admin['name']); ?></strong>
                                    <br>
                                    <small class="text-muted">
                                        آخر تسجيل دخول: 
                                        <?php echo $admin['last_login'] ? date('Y-m-d H:i', strtotime($admin['last_login'])) : 'لم يسجل دخول بعد'; ?>
                                    </small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-center text-muted">لا توجد نشاطات</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.card {
    border: none;
    border-radius: 15px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
}

.card-header {
    background: white;
    border-bottom: 2px solid #f8f9fa;
    padding: 20px;
}

.admin-avatar-sm {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 14px;
    font-weight: 700;
}

.admin-avatar-xs {
    width: 35px;
    height: 35px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 12px;
    font-weight: 700;
}

.role-info {
    background: #f8f9fa;
    border-radius: 10px;
    padding: 15px;
}

.role-item {
    padding: 10px 0;
}

.role-item:not(:last-child) {
    border-bottom: 1px solid #dee2e6;
    margin-bottom: 10px;
    padding-bottom: 15px;
}

.role-item ul {
    margin: 0;
    padding-right: 20px;
    font-size: 14px;
    color: #6c757d;
}

.activity-log {
    max-height: 400px;
    overflow-y: auto;
}

.activity-item {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 15px;
    border-bottom: 1px solid #f8f9fa;
    transition: background 0.3s;
}

.activity-item:hover {
    background: #f8f9fa;
}

.activity-item:last-child {
    border-bottom: none;
}

.activity-content {
    flex: 1;
}

.table thead th {
    background: #f8f9fa;
    border-bottom: 2px solid #dee2e6;
    font-weight: 600;
}

.form-label {
    font-weight: 600;
    color: #495057;
}
</style>

<?php include 'includes/footer.php'; ?>

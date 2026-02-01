<?php
ob_start();
session_start();
require_once 'includes/functions.php';
global $pdo;

// معالجة النماذج (Logic) قبل أي إخراج
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_general'])) {
        // إعدادات عامة
        updateSetting('site_name', sanitizeInput($_POST['site_name']));
        updateSetting('site_description', sanitizeInput($_POST['site_description']));
        updateSetting('currency', sanitizeInput($_POST['currency']));
        updateSetting('currency_symbol', sanitizeInput($_POST['currency_symbol']));
        updateSetting('phone', sanitizeInput($_POST['phone']));
        updateSetting('email', sanitizeInput($_POST['email']));
        updateSetting('address', sanitizeInput($_POST['address']));
        
        setFlashMessage('success', 'تم حفظ الإعدادات العامة بنجاح');
    }
    
    if (isset($_POST['save_social'])) {
        // وسائل التواصل
        updateSetting('facebook', sanitizeInput($_POST['facebook']));
        updateSetting('instagram', sanitizeInput($_POST['instagram']));
        updateSetting('twitter', sanitizeInput($_POST['twitter']));
        updateSetting('youtube', sanitizeInput($_POST['youtube']));
        updateSetting('whatsapp', sanitizeInput($_POST['whatsapp']));
        
        setFlashMessage('success', 'تم حفظ معلومات وسائل التواصل بنجاح');
    }
    
    if (isset($_POST['save_shipping'])) {
        // إعدادات الشحن
        updateSetting('shipping_enabled', isset($_POST['shipping_enabled']) ? '1' : '0');
        updateSetting('shipping_cost', sanitizeInput($_POST['shipping_cost']));
        updateSetting('free_shipping_threshold', sanitizeInput($_POST['free_shipping_threshold']));
        updateSetting('shipping_time', sanitizeInput($_POST['shipping_time']));
        
        setFlashMessage('success', 'تم حفظ إعدادات الشحن بنجاح');
    }
    
    if (isset($_POST['save_notifications'])) {
        // إعدادات الإشعارات
        updateSetting('email_notifications', isset($_POST['email_notifications']) ? '1' : '0');
        updateSetting('sms_notifications', isset($_POST['sms_notifications']) ? '1' : '0');
        updateSetting('admin_email', sanitizeInput($_POST['admin_email']));
        
        setFlashMessage('success', 'تم حفظ إعدادات الإشعارات بنجاح');
    }
    
    header('Location: settings.php');
    exit;
}

// جلب جميع الإعدادات
$settings = [];
$result = $pdo->query("SELECT * FROM settings");
while ($row = $result->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

$pageTitle = 'الإعدادات';
include 'includes/header.php';
?>

<div class="row g-4">
    <!-- الإعدادات العامة -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-gear me-2"></i>الإعدادات العامة
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="mb-3">
                        <label class="form-label">اسم المتجر</label>
                        <input type="text" class="form-control" name="site_name" 
                               value="<?php echo $settings['site_name'] ?? '31 Tech Store'; ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">وصف المتجر</label>
                        <textarea class="form-control" name="site_description" rows="3"><?php echo $settings['site_description'] ?? ''; ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">اسم العملة</label>
                        <input type="text" class="form-control" name="currency" 
                               value="<?php echo $settings['currency'] ?? 'دينار جزائري'; ?>" required>
                        <small class="text-muted">مثال: دينار جزائري، دولار أمريكي، يورو</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">اختصار العملة (الرمز)</label>
                        <input type="text" class="form-control" name="currency_symbol" 
                               value="<?php echo $settings['currency_symbol'] ?? 'دج'; ?>" required>
                        <small class="text-muted">مثال: دج، $، €، ر.س</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">رقم الهاتف</label>
                        <input type="text" class="form-control" name="phone" 
                               value="<?php echo $settings['phone'] ?? ''; ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">البريد الإلكتروني</label>
                        <input type="email" class="form-control" name="email" 
                               value="<?php echo $settings['email'] ?? ''; ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">العنوان</label>
                        <textarea class="form-control" name="address" rows="2"><?php echo $settings['address'] ?? ''; ?></textarea>
                    </div>
                    
                    <button type="submit" name="save_general" class="btn btn-primary">
                        <i class="bi bi-check-circle me-2"></i>حفظ التغييرات
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <!-- وسائل التواصل الاجتماعي -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-share me-2"></i>وسائل التواصل الاجتماعي
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="bi bi-facebook text-primary"></i> فيسبوك
                        </label>
                        <input type="url" class="form-control" name="facebook" 
                               value="<?php echo $settings['facebook'] ?? ''; ?>"
                               placeholder="https://facebook.com/yourpage">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="bi bi-instagram text-danger"></i> إنستغرام
                        </label>
                        <input type="url" class="form-control" name="instagram" 
                               value="<?php echo $settings['instagram'] ?? ''; ?>"
                               placeholder="https://instagram.com/yourpage">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="bi bi-twitter text-info"></i> تويتر
                        </label>
                        <input type="url" class="form-control" name="twitter" 
                               value="<?php echo $settings['twitter'] ?? ''; ?>"
                               placeholder="https://twitter.com/yourpage">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="bi bi-youtube text-danger"></i> يوتيوب
                        </label>
                        <input type="url" class="form-control" name="youtube" 
                               value="<?php echo $settings['youtube'] ?? ''; ?>"
                               placeholder="https://youtube.com/yourchannel">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="bi bi-whatsapp text-success"></i> واتساب
                        </label>
                        <input type="text" class="form-control" name="whatsapp" 
                               value="<?php echo $settings['whatsapp'] ?? ''; ?>"
                               placeholder="213XXXXXXXXX">
                        <small class="text-muted">رقم الواتساب بدون + أو 00</small>
                    </div>
                    
                    <button type="submit" name="save_social" class="btn btn-primary">
                        <i class="bi bi-check-circle me-2"></i>حفظ التغييرات
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <!-- إعدادات الشحن -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-truck me-2"></i>إعدادات الشحن
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="shipping_enabled" id="shipping_enabled"
                                   <?php echo isset($settings['shipping_enabled']) && $settings['shipping_enabled'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="shipping_enabled">
                                تفعيل الشحن
                            </label>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">تكلفة الشحن (<?php echo $settings['currency'] ?? 'دج'; ?>)</label>
                        <input type="number" class="form-control" name="shipping_cost" 
                               value="<?php echo $settings['shipping_cost'] ?? '0'; ?>" 
                               min="0" step="0.01">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">الحد الأدنى للشحن المجاني (<?php echo $settings['currency'] ?? 'دج'; ?>)</label>
                        <input type="number" class="form-control" name="free_shipping_threshold" 
                               value="<?php echo $settings['free_shipping_threshold'] ?? '0'; ?>" 
                               min="0" step="0.01">
                        <small class="text-muted">ضع 0 لتعطيل الشحن المجاني</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">مدة التوصيل المتوقعة</label>
                        <input type="text" class="form-control" name="shipping_time" 
                               value="<?php echo $settings['shipping_time'] ?? '2-5 أيام'; ?>"
                               placeholder="مثال: 2-5 أيام">
                    </div>
                    
                    <button type="submit" name="save_shipping" class="btn btn-primary">
                        <i class="bi bi-check-circle me-2"></i>حفظ التغييرات
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <!-- إعدادات الإشعارات -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-bell me-2"></i>إعدادات الإشعارات
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="email_notifications" id="email_notifications"
                                   <?php echo isset($settings['email_notifications']) && $settings['email_notifications'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="email_notifications">
                                إشعارات البريد الإلكتروني
                            </label>
                        </div>
                        <small class="text-muted">إرسال إشعارات الطلبات الجديدة عبر البريد</small>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="sms_notifications" id="sms_notifications"
                                   <?php echo isset($settings['sms_notifications']) && $settings['sms_notifications'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="sms_notifications">
                                إشعارات SMS
                            </label>
                        </div>
                        <small class="text-muted">إرسال إشعارات الطلبات الجديدة عبر الرسائل النصية</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">البريد الإلكتروني للإشعارات</label>
                        <input type="email" class="form-control" name="admin_email" 
                               value="<?php echo $settings['admin_email'] ?? ''; ?>"
                               placeholder="admin@example.com">
                        <small class="text-muted">البريد الذي ستصله إشعارات الطلبات الجديدة</small>
                    </div>
                    
                    <button type="submit" name="save_notifications" class="btn btn-primary">
                        <i class="bi bi-check-circle me-2"></i>حفظ التغييرات
                    </button>
                </form>
            </div>
        </div>
        
        <!-- معلومات النظام -->
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-info-circle me-2"></i>معلومات النظام
                </h5>
            </div>
            <div class="card-body">
                <div class="system-info">
                    <div class="info-row">
                        <span class="label">إصدار PHP:</span>
                        <span class="value"><?php echo phpversion(); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">قاعدة البيانات:</span>
                        <span class="value">MySQL</span>
                    </div>
                    <div class="info-row">
                        <span class="label">الخادم:</span>
                        <span class="value"><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">المنطقة الزمنية:</span>
                        <span class="value"><?php echo date_default_timezone_get(); ?></span>
                    </div>
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
    margin-bottom: 20px;
}

.card-header {
    background: white;
    border-bottom: 2px solid #f8f9fa;
    padding: 20px;
}

.form-label {
    font-weight: 600;
    color: #495057;
}

.form-control:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
}

.system-info {
    background: #f8f9fa;
    border-radius: 10px;
    padding: 15px;
}

.info-row {
    display: flex;
    justify-content: space-between;
    padding: 10px 0;
    border-bottom: 1px solid #dee2e6;
}

.info-row:last-child {
    border-bottom: none;
}

.info-row .label {
    font-weight: 600;
    color: #495057;
}

.info-row .value {
    color: #6c757d;
}

.bi-facebook { color: #1877f2; }
.bi-instagram { color: #e4405f; }
.bi-twitter { color: #1da1f2; }
.bi-youtube { color: #ff0000; }
.bi-whatsapp { color: #25d366; }
</style>

<?php include 'includes/footer.php'; ?>

<?php
/**
 * دوال لوحة التحكم
 * Admin Functions
 */

require_once __DIR__ . '/../../includes/functions.php';

/**
 * التحقق من تسجيل دخول المدير
 */
function checkAdminLogin() {
    if (!isset($_SESSION['admin_id']) || !isset($_SESSION['admin_username'])) {
        header('Location: login.php');
        exit;
    }
}

/**
 * تسجيل دخول المدير
 */
function adminLogin($username, $password) {
    $sql = "SELECT * FROM admins WHERE username = :username AND is_active = 1";
    $admin = fetchOne($sql, ['username' => $username]);
    
    if ($admin && password_verify($password, $admin['password'])) {
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_username'] = $admin['username'];
        $_SESSION['admin_name'] = $admin['name'];
        $_SESSION['admin_role'] = $admin['role'];
        
        // تحديث آخر تسجيل دخول
        query("UPDATE admins SET last_login = NOW() WHERE id = :id", ['id' => $admin['id']]);
        
        return true;
    }
    
    return false;
}

/**
 * تسجيل خروج المدير
 */
function adminLogout() {
    unset($_SESSION['admin_id']);
    unset($_SESSION['admin_username']);
    unset($_SESSION['admin_name']);
    unset($_SESSION['admin_role']);
    session_destroy();
}

/**
 * الحصول على المدير الحالي
 */
function getAdminInfo() {
    if (!isset($_SESSION['admin_id'])) {
        return null;
    }
    
    $sql = "SELECT id, username, name, email, role, last_login FROM admins WHERE id = :id";
    return fetchOne($sql, ['id' => $_SESSION['admin_id']]);
}

/**
 * التحقق من صلاحيات المدير
 */
function hasPermission($role = 'admin') {
    if (!isset($_SESSION['admin_role'])) {
        return false;
    }
    
    if ($role === 'super_admin' && $_SESSION['admin_role'] !== 'super_admin') {
        return false;
    }
    
    return true;
}

/**
 * الحصول على إحصائيات لوحة التحكم
 */
function getDashboardStats() {
    $stats = [];
    
    // عدد المنتجات
    $result = fetchOne("SELECT COUNT(*) as count FROM products WHERE status = 'active'");
    $stats['products_count'] = $result['count'] ?? 0;
    
    // عدد الطلبات
    $result = fetchOne("SELECT COUNT(*) as count FROM orders");
    $stats['orders_count'] = $result['count'] ?? 0;
    
    // عدد الطلبات المعلقة
    $result = fetchOne("SELECT COUNT(*) as count FROM orders WHERE status = 'pending'");
    $stats['pending_orders'] = $result['count'] ?? 0;
    
    // عدد العملاء
    $result = fetchOne("SELECT COUNT(*) as count FROM customers");
    $stats['customers_count'] = $result['count'] ?? 0;
    
    // إجمالي المبيعات
    $result = fetchOne("SELECT SUM(total) as total FROM orders WHERE status IN ('confirmed', 'processing', 'shipped', 'delivered')");
    $stats['total_sales'] = $result['total'] ?? 0;
    
    // مبيعات اليوم
    $result = fetchOne("SELECT SUM(total) as total FROM orders WHERE DATE(ordered_at) = CURDATE()");
    $stats['today_sales'] = $result['total'] ?? 0;
    
    // مبيعات الشهر
    $result = fetchOne("SELECT SUM(total) as total FROM orders WHERE MONTH(ordered_at) = MONTH(CURDATE()) AND YEAR(ordered_at) = YEAR(CURDATE())");
    $stats['month_sales'] = $result['total'] ?? 0;

    // المنتجات منخفضة المخزون (أقل من 5)
    $result = fetchOne("SELECT COUNT(*) as count FROM products WHERE stock_count <= 5 AND status = 'active'");
    $stats['low_stock'] = $result['count'] ?? 0;
    
    return $stats;
}

/**
 * الحصول على آخر الطلبات
 */
function getRecentOrders($limit = 10) {
    // استخدام البيانات من جدول orders مباشرة بدلاً من JOIN
    // لأن جدول orders يحتوي بالفعل على customer_name
    $sql = "SELECT o.* 
            FROM orders o 
            ORDER BY o.ordered_at DESC 
            LIMIT :limit";
    
    return fetchAll($sql, ['limit' => $limit]);
}

/**
 * الحصول على المنتجات الأكثر مبيعاً
 */
function getTopProducts($limit = 5) {
    $sql = "SELECT p.*, COUNT(oi.id) as order_count, SUM(oi.quantity) as total_sold
            FROM products p
            LEFT JOIN order_items oi ON p.id = oi.product_id
            WHERE p.status = 'active'
            GROUP BY p.id
            ORDER BY total_sold DESC
            LIMIT :limit";
    
    return fetchAll($sql, ['limit' => $limit]);
}

/**
 * الحصول على جميع المديرين
 */
function getAllAdmins() {
    $sql = "SELECT id, username, name, email, role, is_active, last_login, created_at 
            FROM admins 
            ORDER BY created_at DESC";
    return fetchAll($sql);
}

/**
 * إضافة مدير جديد
 */
function addAdmin($data) {
    $sql = "INSERT INTO admins (username, name, email, password, role, is_active) 
            VALUES (:username, :name, :email, :password, :role, :is_active)";
    
    $params = [
        'username' => $data['username'],
        'name' => $data['name'],
        'email' => $data['email'],
        'password' => password_hash($data['password'], PASSWORD_BCRYPT),
        'role' => $data['role'] ?? 'admin',
        'is_active' => $data['is_active'] ?? 1
    ];
    
    return query($sql, $params);
}

/**
 * تحديث بيانات مدير
 */
function updateAdmin($id, $data) {
    $sql = "UPDATE admins SET name = :name, email = :email, role = :role, is_active = :is_active";
    
    $params = [
        'id' => $id,
        'name' => $data['name'],
        'email' => $data['email'],
        'role' => $data['role'],
        'is_active' => $data['is_active']
    ];
    
    if (!empty($data['password'])) {
        $sql .= ", password = :password";
        $params['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
    }
    
    $sql .= " WHERE id = :id";
    
    return query($sql, $params);
}

/**
 * حذف مدير
 */
function deleteAdmin($id) {
    // لا يمكن حذف المدير نفسه
    if ($id == $_SESSION['admin_id']) {
        return false;
    }
    
    $sql = "DELETE FROM admins WHERE id = :id";
    return query($sql, ['id' => $id]);
}

/**
 * الحصول على جميع المنتجات للأدمن
 */
function getAllProductsAdmin($limit = null, $offset = 0) {
    $sql = "SELECT p.*, c.name_ar as category_name 
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id 
            ORDER BY p.created_at DESC";
    
    if ($limit) {
        $sql .= " LIMIT :limit OFFSET :offset";
        return fetchAll($sql, ['limit' => $limit, 'offset' => $offset]);
    }
    
    return fetchAll($sql);
}

/**
 * إضافة منتج جديد
 */
function addProduct($data) {
    $sql = "INSERT INTO products (name_ar, name_en, slug, sku, model, brand, category_id, 
            price, original_price, stock_count, processor, ram, storage, gpu, screen_size, 
            screen_resolution, battery, weight, os, description_short, description_full, 
            warranty, badge, status) 
            VALUES (:name_ar, :name_en, :slug, :sku, :model, :brand, :category_id, 
            :price, :original_price, :stock_count, :processor, :ram, :storage, :gpu, 
            :screen_size, :screen_resolution, :battery, :weight, :os, :description_short, 
            :description_full, :warranty, :badge, :status)";
    
    return query($sql, $data);
}

/**
 * تحديث منتج
 */
function updateProduct($id, $data) {
    $data['id'] = $id;
    
    $sql = "UPDATE products SET 
            name_ar = :name_ar, name_en = :name_en, slug = :slug, sku = :sku, model = :model, 
            brand = :brand, category_id = :category_id, price = :price, 
            original_price = :original_price, stock_count = :stock_count, 
            processor = :processor, ram = :ram, storage = :storage, gpu = :gpu, 
            screen_size = :screen_size, screen_resolution = :screen_resolution, 
            battery = :battery, weight = :weight, os = :os, 
            description_short = :description_short, description_full = :description_full, 
            warranty = :warranty, badge = :badge, status = :status 
            WHERE id = :id";
    
    return query($sql, $data);
}

/**
 * حذف منتج
 */
function deleteProduct($id) {
    $sql = "DELETE FROM products WHERE id = :id";
    return query($sql, ['id' => $id]);
}

/**
 * تحديث حالة الطلب
 */
function updateOrderStatus($orderId, $status) {
    $sql = "UPDATE orders SET status = :status";
    
    $params = [
        'order_id' => $orderId,
        'status' => $status
    ];
    
    // تحديث التواريخ حسب الحالة
    if ($status === 'confirmed') {
        $sql .= ", confirmed_at = NOW()";
    } elseif ($status === 'shipped') {
        $sql .= ", shipped_at = NOW()";
    } elseif ($status === 'delivered') {
        $sql .= ", delivered_at = NOW()";
    }
    
    $sql .= " WHERE id = :order_id";
    
    return query($sql, $params);
}

/**
 * الحصول على تفاصيل طلب
 */
function getOrderDetails($orderId) {
    $sql = "SELECT o.*, c.name as customer_name, c.email as customer_email 
            FROM orders o 
            LEFT JOIN customers c ON o.customer_id = c.id 
            WHERE o.id = :id";
    
    $order = fetchOne($sql, ['id' => $orderId]);
    
    if ($order) {
        $order['items'] = getOrderItems($orderId);
    }
    
    return $order;
}

/**
 * رفع صورة
 */
function uploadImage($file, $directory = 'products') {
    $uploadDir = __DIR__ . '/../../uploads/' . $directory . '/';
    
    // إنشاء المجلد إذا لم يكن موجوداً
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $maxSize = 5 * 1024 * 1024; // 5MB
    
    if (!in_array($file['type'], $allowedTypes)) {
        return ['success' => false, 'message' => 'نوع الملف غير مدعوم'];
    }
    
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'message' => 'حجم الملف كبير جداً (الحد الأقصى 5MB)'];
    }
    
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '_' . time() . '.' . $extension;
    $filepath = $uploadDir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return [
            'success' => true,
            'filename' => $filename,
            'path' => 'uploads/' . $directory . '/' . $filename
        ];
    }
    
    return ['success' => false, 'message' => 'فشل رفع الملف'];
}

/**
 * تسجيل نشاطات المدير
 */
function logActivity($conn, $adminId, $action, $description) {
    // TODO: Implement activity logging to database or file
    // For now, return true to avoid errors
    return true;
}

/**
 * تنظيف المدخلات (alias)
 */
function sanitizeInput($data) {
    return clean($data);
}

/**
 * تحديث إعداد
 */
function updateSetting($key, $value) {
    global $pdo;
    $sql = "INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) 
            ON DUPLICATE KEY UPDATE setting_value = ?";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([$key, $value, $value]);
}

/**
 * حذف صورة
 */
function deleteImage($filename, $directory = 'products') {
    $filepath = __DIR__ . '/../../uploads/' . $directory . '/' . $filename;
    if (file_exists($filepath)) {
        return unlink($filepath);
    }
    return false;
}

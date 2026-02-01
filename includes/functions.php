<?php
/**
 * ملف الدوال المساعدة
 * Helper Functions
 */

require_once __DIR__ . '/../config/database.php';

// بدء الجلسة
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * ==========================================
 * دوال الأمان
 * Security Functions
 * ==========================================
 */

/**
 * تنظيف المدخلات من الأكواد الضارة
 */
function clean($data) {
    if (is_array($data)) {
        return array_map('clean', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * التحقق من CSRF Token
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * تشفير كلمة المرور
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT);
}

/**
 * التحقق من كلمة المرور
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * ==========================================
 * دوال المنتجات
 * Product Functions
 * ==========================================
 */

/**
 * الحصول على جميع المنتجات
 */
function getAllProducts($limit = null, $offset = 0) {
    $sql = "SELECT p.*, c.name_ar as category_name, c.slug as category_slug 
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id 
            WHERE p.status = 'active' 
            ORDER BY p.created_at DESC";
    
    if ($limit) {
        $sql .= " LIMIT :limit OFFSET :offset";
        $stmt = query($sql, ['limit' => $limit, 'offset' => $offset]);
    } else {
        $stmt = query($sql);
    }
    
    $products = $stmt ? $stmt->fetchAll() : [];
    
    // إضافة الصور لكل منتج
    foreach ($products as &$product) {
        $product['images'] = getProductImages($product['id']);
        $product['features'] = getProductFeatures($product['id']);
    }
    
    return $products;
}

/**
 * الحصول على منتج واحد
 */
function getProduct($id) {
    $sql = "SELECT p.*, c.name_ar as category_name, c.slug as category_slug 
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id 
            WHERE p.id = :id AND p.status = 'active'";
    
    $product = fetchOne($sql, ['id' => $id]);
    
    if ($product) {
        $product['images'] = getProductImages($id);
        $product['features'] = getProductFeatures($id);
        
        // تحديث عدد المشاهدات
        query("UPDATE products SET views = views + 1 WHERE id = :id", ['id' => $id]);
    }
    
    return $product;
}

/**
 * دالة بديلة للحصول على منتج (alias)
 */
function getProductById($id) {
    return getProduct($id);
}

/**
 * الحصول على منتج حسب slug
 */
function getProductBySlug($slug) {
    $sql = "SELECT p.*, c.name_ar as category_name, c.slug as category_slug 
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id 
            WHERE p.slug = :slug AND p.status = 'active'";
    
    $product = fetchOne($sql, ['slug' => $slug]);
    
    if ($product) {
        $product['images'] = getProductImages($product['id']);
        $product['features'] = getProductFeatures($product['id']);
    }
    
    return $product;
}

/**
 * الحصول على صور المنتج
 */
function getProductImages($productId) {
    $sql = "SELECT * FROM product_images 
            WHERE product_id = :product_id 
            ORDER BY is_primary DESC, display_order ASC";
    return fetchAll($sql, ['product_id' => $productId]);
}

/**
 * الحصول على مميزات المنتج
 */
function getProductFeatures($productId) {
    $sql = "SELECT * FROM product_features 
            WHERE product_id = :product_id 
            ORDER BY display_order ASC";
    return fetchAll($sql, ['product_id' => $productId]);
}

/**
 * الحصول على المنتجات المميزة
 */
function getFeaturedProducts($limit = 8) {
    $sql = "SELECT p.*, c.name_ar as category_name 
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id 
            WHERE p.status = 'active' AND p.badge IN ('new', 'bestseller', 'featured')
            ORDER BY p.sales DESC, p.created_at DESC 
            LIMIT :limit";
    
    $products = fetchAll($sql, ['limit' => $limit]);
    
    foreach ($products as &$product) {
        $product['images'] = getProductImages($product['id']);
    }
    
    return $products;
}

/**
 * الحصول على المنتجات المخفضة
 */
function getOnSaleProducts($limit = 8) {
    $sql = "SELECT p.*, c.name_ar as category_name 
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id 
            WHERE p.status = 'active'
            AND (p.badge = 'sale' OR (p.original_price IS NOT NULL AND p.original_price > p.price))
            ORDER BY p.created_at DESC 
            LIMIT :limit";
    
    $products = fetchAll($sql, ['limit' => $limit]);
    
    foreach ($products as &$product) {
        $product['images'] = getProductImages($product['id']);
    }
    
    return $products;
}

/**
 * الحصول على منتجات حسب الفئة
 */
function getProductsByCategory($categorySlug, $limit = null) {
    $sql = "SELECT p.*, c.name_ar as category_name 
            FROM products p 
            JOIN categories c ON p.category_id = c.id 
            WHERE p.status = 'active' AND c.slug = :slug 
            ORDER BY p.created_at DESC";
    
    if ($limit) {
        $sql .= " LIMIT :limit";
        $products = fetchAll($sql, ['slug' => $categorySlug, 'limit' => $limit]);
    } else {
        $products = fetchAll($sql, ['slug' => $categorySlug]);
    }
    
    foreach ($products as &$product) {
        $product['images'] = getProductImages($product['id']);
    }
    
    return $products;
}

/**
 * الحصول على قيم فريدة لفلتر معين
 */
function getUniqueValues($column) {
    // Columns allowed list to prevent injection
    $allowed = ['brand', 'processor', 'ram', 'storage', 'gpu'];
    if (!in_array($column, $allowed)) return [];
    
    $sql = "SELECT DISTINCT $column FROM products WHERE status = 'active' AND $column IS NOT NULL AND $column != '' ORDER BY $column";
    $result = fetchAll($sql);
    return array_column($result, $column);
}

/**
 * الحصول على المنتجات مع الفلترة المتقدمة
 */
function getFilteredProducts($filters = [], $sort = 'newest', $limit = null, $offset = 0) {
    $sql = "SELECT p.*, c.name_ar as category_name 
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id 
            WHERE p.status = 'active'";
    
    $params = [];
    
    // 1. Price Ranges
    if (!empty($filters['price_range'])) {
        $priceConditions = [];
        foreach ($filters['price_range'] as $range) {
            if ($range == 'under_40k') {
                $priceConditions[] = "p.price < 40000";
            } elseif ($range == '40k_100k') {
                $priceConditions[] = "(p.price >= 40000 AND p.price <= 100000)";
            } elseif ($range == 'above_100k') {
                $priceConditions[] = "p.price > 100000";
            }
        }
        if (!empty($priceConditions)) {
            $sql .= " AND (" . implode(' OR ', $priceConditions) . ")";
        }
    }
    
    // 2. Dynamic Attributes (Brand, CPU, RAM, Storage, GPU)
    $attributes = ['brand', 'processor', 'ram', 'storage', 'gpu'];
    foreach ($attributes as $attr) {
        if (!empty($filters[$attr])) {
            $placeholders = [];
            foreach ($filters[$attr] as $val) {
                // Determine parameter name
                $key = $attr . '_' . count($params); // unique key
                $params[$key] = $val;
                $placeholders[] = ":$key";
            }
            $sql .= " AND p.$attr IN (" . implode(', ', $placeholders) . ")";
        }
    }
    
    // 3. Search Keyword
    if (!empty($filters['search'])) {
        $sql .= " AND (p.name_ar LIKE :search OR p.name_en LIKE :search OR p.brand LIKE :search OR p.model LIKE :search)";
        $params['search'] = '%' . $filters['search'] . '%';
    }
    
    // 4. Category
    if (!empty($filters['category'])) {
        $sql .= " AND c.slug = :category";
        $params['category'] = $filters['category'];
    }

    // Sort
    switch ($sort) {
        case 'price_low': $sql .= " ORDER BY p.price ASC"; break;
        case 'price_high': $sql .= " ORDER BY p.price DESC"; break;
        case 'popular': $sql .= " ORDER BY p.sales DESC"; break;
        default: $sql .= " ORDER BY p.created_at DESC";
    }
    
    if ($limit) {
        $sql .= " LIMIT " . (int)$limit;
        if ($offset) $sql .= " OFFSET " . (int)$offset;
    }
    
    $stmt = query($sql, $params);
    $products = $stmt ? $stmt->fetchAll() : [];

    foreach ($products as &$product) {
        $product['images'] = getProductImages($product['id']);
    }
    
    return $products;
}

/**
 * البحث في المنتجات (Legacy Wrapper currently redirected to use getFilteredProducts if needed, keeps compatibility)
 */
function searchProducts($keyword, $limit = 20) {
    return getFilteredProducts(['search' => $keyword], 'newest', $limit);
}

/**
 * ==========================================
 * دوال الفئات
 * Category Functions
 * ==========================================
 */

/**
 * الحصول على جميع الفئات
 */
function getAllCategories() {
    $sql = "SELECT * FROM categories WHERE is_active = 1 ORDER BY display_order ASC";
    return fetchAll($sql);
}

/**
 * الحصول على فئة حسب slug
 */
function getCategoryBySlug($slug) {
    $sql = "SELECT * FROM categories WHERE slug = :slug AND is_active = 1";
    return fetchOne($sql, ['slug' => $slug]);
}

/**
 * ==========================================
 * دوال الطلبات
 * Order Functions
 * ==========================================
 */

/**
 * إنشاء طلب جديد
 */
function createOrder($customerData, $cartItems, $paymentMethod = 'cash_on_delivery') {
    global $pdo;
    
    try {
        $pdo->beginTransaction();
        
        // 1. إنشاء/تحديث بيانات العميل
        $customerId = createOrUpdateCustomer($customerData);
        
        // 2. حساب الإجماليات
        $subtotal = 0;
        foreach ($cartItems as $item) {
            $subtotal += $item['price'] * $item['quantity'];
        }
        
        $settings = getSettings();
        $taxRate = floatval($settings['tax_rate'] ?? 15) / 100;
        $shippingCost = floatval($settings['shipping_cost'] ?? 50);
        $freeShippingLimit = floatval($settings['free_shipping_limit'] ?? 500);
        
        if ($subtotal >= $freeShippingLimit) {
            $shippingCost = 0;
        }
        
        $tax = $subtotal * $taxRate;
        $total = $subtotal + $tax + $shippingCost;
        
        // 3. إنشاء رقم الطلب
        $orderNumber = 'ORD-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid(rand(), true)), 0, 6));
        
        // 4. إدراج الطلب
        $sql = "INSERT INTO orders (order_number, customer_id, customer_name, customer_email, 
                customer_phone, shipping_address, shipping_city, subtotal, tax, shipping_cost, 
                total, payment_method, status) 
                VALUES (:order_number, :customer_id, :customer_name, :customer_email, 
                :customer_phone, :shipping_address, :shipping_city, :subtotal, :tax, 
                :shipping_cost, :total, :payment_method, 'pending')";
        
        query($sql, [
            'order_number' => $orderNumber,
            'customer_id' => $customerId,
            'customer_name' => $customerData['name'],
            'customer_email' => $customerData['email'] ?? null,
            'customer_phone' => $customerData['phone'],
            'shipping_address' => $customerData['address'],
            'shipping_city' => $customerData['city'],
            'subtotal' => $subtotal,
            'tax' => $tax,
            'shipping_cost' => $shippingCost,
            'total' => $total,
            'payment_method' => $paymentMethod
        ]);
        
        $orderId = lastInsertId();
        
        // 5. إضافة عناصر الطلب
        foreach ($cartItems as $item) {
            $product = getProduct($item['product_id']);
            
            $sql = "INSERT INTO order_items (order_id, product_id, product_name, product_sku, 
                    product_image, quantity, price, subtotal) 
                    VALUES (:order_id, :product_id, :product_name, :product_sku, 
                    :product_image, :quantity, :price, :subtotal)";
            
            query($sql, [
                'order_id' => $orderId,
                'product_id' => $item['product_id'],
                'product_name' => $product['name_ar'],
                'product_sku' => $product['sku'],
                'product_image' => $product['images'][0]['image_url'] ?? '',
                'quantity' => $item['quantity'],
                'price' => $item['price'],
                'subtotal' => $item['price'] * $item['quantity']
            ]);
            
            // 6. تحديث المخزون
            query("UPDATE products SET stock_count = stock_count - :quantity, 
                   sales = sales + :quantity WHERE id = :id", [
                'quantity' => $item['quantity'],
                'id' => $item['product_id']
            ]);
        }
        
        // 7. تحديث إحصائيات العميل
        query("UPDATE customers SET total_orders = total_orders + 1, 
               total_spent = total_spent + :total, last_order_date = NOW() 
               WHERE id = :id", [
            'total' => $total,
            'id' => $customerId
        ]);
        
        $pdo->commit();
        
        return [
            'success' => true,
            'order_id' => $orderId,
            'order_number' => $orderNumber,
            'total' => $total
        ];
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Order Creation Error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'حدث خطأ أثناء إنشاء الطلب'
        ];
    }
}

/**
 * إنشاء أو تحديث بيانات العميل
 */
function createOrUpdateCustomer($data) {
    // البحث عن العميل بالهاتف
    $customer = fetchOne("SELECT id FROM customers WHERE phone = :phone", ['phone' => $data['phone']]);
    
    if ($customer) {
        // تحديث البيانات
        query("UPDATE customers SET name = :name, email = :email, address = :address, 
               city = :city WHERE id = :id", [
            'name' => $data['name'],
            'email' => $data['email'] ?? null,
            'address' => $data['address'],
            'city' => $data['city'],
            'id' => $customer['id']
        ]);
        return $customer['id'];
    } else {
        // إنشاء عميل جديد
        query("INSERT INTO customers (name, email, phone, address, city) 
               VALUES (:name, :email, :phone, :address, :city)", [
            'name' => $data['name'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'],
            'address' => $data['address'],
            'city' => $data['city']
        ]);
        return lastInsertId();
    }
}

/**
 * الحصول على طلب بواسطة رقم الطلب
 */
function getOrderByNumber($orderNumber) {
    $sql = "SELECT * FROM orders WHERE order_number = :order_number";
    $order = fetchOne($sql, ['order_number' => $orderNumber]);
    
    if ($order) {
        $order['items'] = getOrderItems($order['id']);
    }
    
    return $order;
}

/**
 * الحصول على عناصر الطلب
 */
function getOrderItems($orderId) {
    $sql = "SELECT * FROM order_items WHERE order_id = :order_id";
    return fetchAll($sql, ['order_id' => $orderId]);
}

/**
 * ==========================================
 * دوال الإعدادات
 * Settings Functions
 * ==========================================
 */

/**
 * الحصول على جميع الإعدادات
 */
function getSettings() {
    $sql = "SELECT setting_key, setting_value FROM settings";
    $results = fetchAll($sql);
    
    $settings = [];
    if (!$results) return $settings;
    
    foreach ($results as $row) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    
    return $settings;
}

/**
 * الحصول على إعداد محدد
 */
function getSetting($key, $default = null) {
    $sql = "SELECT setting_value FROM settings WHERE setting_key = :key";
    $result = fetchOne($sql, ['key' => $key]);
    return $result ? $result['setting_value'] : $default;
}

/**
 * ==========================================
 * دوال عامة
 * General Functions
 * ==========================================
 */

/**
 * إعادة التوجيه
 */
function redirect($url) {
    header("Location: " . $url);
    exit;
}

/**
 * رسالة فلاش
 */
function setFlashMessage($type, $message) {
    $_SESSION['flash_message'] = ['type' => $type, 'message' => $message];
}

function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $message;
    }
    return null;
}

/**
 * التحقق من تسجيل دخول المدير
 */
function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']) && isset($_SESSION['admin_username']);
}

/**
 * التحقق من تسجيل الدخول وإعادة التوجيه
 */
function checkLogin() {
    if (!isAdminLoggedIn()) {
        redirect('login.php');
        exit;
    }
}

/**
 * الحصول على معلومات المدير الحالي
 */
function getCurrentAdmin() {
    if (!isAdminLoggedIn()) {
        return null;
    }
    
    $sql = "SELECT id, username, name, email, role FROM admins WHERE id = :id";
    return fetchOne($sql, ['id' => $_SESSION['admin_id']]);
}

/**
 * التحقق من تسجيل دخول العميل
 */
function isCustomerLoggedIn() {
    return isset($_SESSION['customer_id']);
}

/**
 * تنسيق السعر
 */
/**
 * تنسيق السعر (نص فقط)
 */
function formatPricePlain($price) {
    return number_format($price, 2, '.', ',');
}

/**
 * تنسيق السعر (مع تنسيق HTML للأرقام الإنجليزية)
 */
function formatPrice($price) {
    return '<span class="en-num">' . formatPricePlain($price) . '</span>';
}

/**
 * تنسيق التاريخ
 */
function formatDate($date, $format = 'Y-m-d H:i:s') {
    return date($format, strtotime($date));
}

/**
 * إنشاء slug من النص
 */
function createSlug($text) {
    // تحويل إلى أحرف صغيرة
    $text = strtolower($text);
    // استبدال المسافات بشرطة
    $text = preg_replace('/\s+/', '-', $text);
    // إزالة الأحرف الخاصة
    $text = preg_replace('/[^a-z0-9\-]/', '', $text);
    // إزالة الشرطات المتكررة
    $text = preg_replace('/-+/', '-', $text);
    // إزالة الشرطات من البداية والنهاية
    $text = trim($text, '-');
    
    return $text;
}
function displayFlashMessage() {
    $flash = getFlashMessage();
    if ($flash) {
        $type = htmlspecialchars($flash['type']); // success, danger, info, warning
        $message = htmlspecialchars($flash['message']);
        echo "<div class='alert alert-{$type} mt-3' role='alert'>{$message}</div>";
    }
}


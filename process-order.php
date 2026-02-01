<?php
require_once 'includes/functions.php';
require_once 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

try {
    // استخراج البيانات
    $cartData = json_decode($_POST['cart_data'], true);
    $shippingCost = floatval($_POST['shipping_cost']);
    
    if (empty($cartData)) {
        throw new Exception('السلة فارغة');
    }
    
    // حساب المجاميع
    $subtotal = 0;
    foreach ($cartData as $item) {
        $subtotal += $item['price'] * $item['quantity'];
    }
    
    $total = $subtotal + $shippingCost;
    
    // بيانات الطلب
    // بيانات الطلب
    $phone = htmlspecialchars(trim($_POST['customer_phone']));
    // إنشاء بريد وهمي بناءً على رقم الهاتف للضرورة التقنية
    $dummyEmail = preg_replace('/[^0-9]/', '', $phone) . '@local.store';
    
    $orderData = [
        'name' => htmlspecialchars(trim($_POST['customer_name'])),
        'email' => $dummyEmail, // استخدام البريد الوهمي
        'phone' => $phone,
        'address' => isset($_POST['customer_address']) ? htmlspecialchars(trim($_POST['customer_address'])) : '',
        'city' => htmlspecialchars(trim($_POST['customer_city'])),
        'payment_method' => $_POST['payment_method'],
        'notes' => isset($_POST['notes']) ? htmlspecialchars(trim($_POST['notes'])) : null,
        'subtotal' => $subtotal,
        'shipping_cost' => $shippingCost,
        'tax' => 0,
        'total' => $total,
        'items' => $cartData
    ];
    
    // تم إلغاء التحقق من البريد الإلكتروني لأنه أصبح تلقائياً
    
    // حفظ الطلب
    $conn = getDB();
    
    $conn->beginTransaction();
    
    // توليد رقم الطلب
    $orderNumber = 'ORD-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid()), 0, 6));
    
    // إنشاء/تحديث العميل
    $customerSql = "INSERT INTO customers (name, email, phone, address, city) 
                   VALUES (?, ?, ?, ?, ?) 
                   ON DUPLICATE KEY UPDATE 
                   name = VALUES(name), phone = VALUES(phone), address = VALUES(address), city = VALUES(city)";
    $customerStmt = $conn->prepare($customerSql);
    $customerStmt->execute([
        $orderData['name'],
        $orderData['email'],
        $orderData['phone'],
        $orderData['address'],
        $orderData['city']
    ]);
    
    $customerId = $conn->lastInsertId();
    if (!$customerId) {
        $customerId = $conn->query("SELECT id FROM customers WHERE email = '{$orderData['email']}'")->fetchColumn();
    }
    
    // إنشاء الطلب
    $orderSql = "INSERT INTO orders (order_number, customer_id, customer_name, customer_email, customer_phone, shipping_address, shipping_city, 
                 subtotal, shipping_cost, tax, total, payment_method, notes) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $orderStmt = $conn->prepare($orderSql);
    $orderStmt->execute([
        $orderNumber,
        $customerId,
        $orderData['name'],
        $orderData['email'],
        $orderData['phone'],
        $orderData['address'],
        $orderData['city'],
        $orderData['subtotal'],
        $orderData['shipping_cost'],
        $orderData['tax'],
        $orderData['total'],
        $orderData['payment_method'],
        $orderData['notes']
    ]);
    
    $orderId = $conn->lastInsertId();
    
    // إضافة عناصر الطلب
    foreach ($orderData['items'] as $item) {
        $itemSql = "INSERT INTO order_items (order_id, product_id, product_name, product_sku, product_image, quantity, price, subtotal) 
                   VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $itemStmt = $conn->prepare($itemSql);
        $itemStmt->execute([
            $orderId,
            $item['id'],
            $item['name'],
            $item['model'], // Storing model as SKU for now since they are passed as such
            $item['image'],
            $item['quantity'],
            $item['price'],
            $item['price'] * $item['quantity']
        ]);
        
        // تحديث المخزون والمبيعات
        $updateProduct = $conn->prepare("UPDATE products SET stock_count = stock_count - ?, sales = sales + ?, in_stock = IF(stock_count - ? > 0, 1, 0) WHERE id = ?");
        $updateProduct->execute([$item['quantity'], $item['quantity'], $item['quantity'], $item['id']]);
    }
    
    $conn->commit();
    
    // إعادة التوجيه إلى صفحة النجاح
    header('Location: order-success.php?order_number=' . $orderNumber);
    exit;
    
} catch (Exception $e) {
    if (isset($conn)) {
        $conn->rollBack();
    }
    
    $pageTitle = 'خطأ في الطلب';
    include 'includes/header.php';
    ?>
    
    <div class="container" style="padding: 3rem 1rem; max-width: 600px; text-align: center;">
        <div style="background: var(--white); border-radius: 1rem; box-shadow: var(--shadow); padding: 3rem 2rem;">
            <div style="width: 100px; height: 100px; background: linear-gradient(135deg, var(--danger) 0%, #dc2626 100%); border-radius: 50%; margin: 0 auto 2rem; display: flex; align-items: center; justify-content: center;">
                <svg width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="3">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </div>
            
            <h1 style="font-size: 2rem; font-weight: 800; margin-bottom: 1rem; color: var(--danger);">حدث خطأ!</h1>
            
            <p style="font-size: 1.125rem; color: var(--gray); margin-bottom: 2rem;">
                <?php echo htmlspecialchars($e->getMessage()); ?>
            </p>
            
            <a href="checkout.php" class="btn btn-primary">
                المحاولة مرة أخرى
            </a>
        </div>
    </div>
    
    <?php
    include 'includes/footer.php';
}
?>

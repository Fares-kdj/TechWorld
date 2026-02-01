<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';
require_once '../includes/functions.php';

$db = new Database();
$conn = $db->getConnection();

$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : '';

try {
    if ($method === 'POST' && $action === 'create') {
        // إنشاء طلب جديد
        $data = json_decode(file_get_contents('php://input'), true);
        
        // التحقق من البيانات
        if (!isset($data['customer']) || !isset($data['items']) || empty($data['items'])) {
            throw new Exception('بيانات غير كاملة');
        }
        
        $customer = $data['customer'];
        $items = $data['items'];
        
        // التحقق من بيانات العميل
        $requiredFields = ['name', 'email', 'phone', 'address'];
        foreach ($requiredFields as $field) {
            if (empty($customer[$field])) {
                throw new Exception("حقل $field مطلوب");
            }
        }
        
        $conn->beginTransaction();
        
        try {
            // إضافة أو تحديث العميل
            $checkCustomer = $conn->prepare("SELECT id FROM customers WHERE email = ?");
            $checkCustomer->execute([$customer['email']]);
            $existingCustomer = $checkCustomer->fetch();
            
            if ($existingCustomer) {
                $customerId = $existingCustomer['id'];
                $updateCustomer = $conn->prepare("UPDATE customers SET name = ?, phone = ?, address = ?, city = ?, updated_at = NOW() WHERE id = ?");
                $updateCustomer->execute([
                    $customer['name'],
                    $customer['phone'],
                    $customer['address'],
                    $customer['city'] ?? '',
                    $customerId
                ]);
            } else {
                $insertCustomer = $conn->prepare("INSERT INTO customers (name, email, phone, address, city) VALUES (?, ?, ?, ?, ?)");
                $insertCustomer->execute([
                    $customer['name'],
                    $customer['email'],
                    $customer['phone'],
                    $customer['address'],
                    $customer['city'] ?? ''
                ]);
                $customerId = $conn->lastInsertId();
            }
            
            // حساب المجاميع
            $subtotal = 0;
            foreach ($items as $item) {
                $subtotal += $item['price'] * $item['quantity'];
            }
            
            $shippingCost = $data['shipping_cost'] ?? 500;
            $tax = $data['tax'] ?? 0;
            $total = $subtotal + $shippingCost + $tax;
            
            // إنشاء الطلب
            $orderNumber = generateOrderNumber();
            $paymentMethod = $data['payment_method'] ?? 'cash_on_delivery';
            
            $insertOrder = $conn->prepare("INSERT INTO orders (
                order_number, customer_id, customer_name, customer_email, customer_phone, customer_address,
                subtotal, shipping_cost, tax, total, payment_method, notes
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            $insertOrder->execute([
                $orderNumber,
                $customerId,
                $customer['name'],
                $customer['email'],
                $customer['phone'],
                $customer['address'],
                $subtotal,
                $shippingCost,
                $tax,
                $total,
                $paymentMethod,
                $data['notes'] ?? ''
            ]);
            
            $orderId = $conn->lastInsertId();
            
            // إضافة عناصر الطلب
            $insertItem = $conn->prepare("INSERT INTO order_items (order_id, product_id, product_name, product_model, quantity, price, subtotal) VALUES (?, ?, ?, ?, ?, ?, ?)");
            
            foreach ($items as $item) {
                // التحقق من المخزون
                $product = $conn->query("SELECT * FROM products WHERE id = {$item['product_id']}")->fetch();
                
                if (!$product) {
                    throw new Exception("المنتج {$item['product_id']} غير موجود");
                }
                
                if (!$product['in_stock'] || $product['stock_count'] < $item['quantity']) {
                    throw new Exception("المنتج {$product['name_ar']} غير متوفر بالكمية المطلوبة");
                }
                
                $itemSubtotal = $item['price'] * $item['quantity'];
                
                $insertItem->execute([
                    $orderId,
                    $item['product_id'],
                    $product['name_ar'],
                    $product['model'],
                    $item['quantity'],
                    $item['price'],
                    $itemSubtotal
                ]);
                
                // تحديث المخزون
                $newStock = $product['stock_count'] - $item['quantity'];
                $updateStock = $conn->prepare("UPDATE products SET stock_count = ?, in_stock = ?, sales = sales + ? WHERE id = ?");
                $updateStock->execute([
                    $newStock,
                    $newStock > 0 ? 1 : 0,
                    $item['quantity'],
                    $item['product_id']
                ]);
            }
            
            $conn->commit();
            
            echo json_encode([
                'success' => true,
                'message' => 'تم إنشاء الطلب بنجاح',
                'order_number' => $orderNumber,
                'order_id' => $orderId
            ], JSON_UNESCAPED_UNICODE);
            
        } catch (Exception $e) {
            $conn->rollBack();
            throw $e;
        }
        
    } elseif ($method === 'GET' && $action === 'track') {
        // تتبع الطلب
        $orderNumber = isset($_GET['order_number']) ? $_GET['order_number'] : '';
        
        if (empty($orderNumber)) {
            throw new Exception('رقم الطلب مطلوب');
        }
        
        $sql = "SELECT o.*, c.name as customer_name
                FROM orders o
                LEFT JOIN customers c ON o.customer_id = c.id
                WHERE o.order_number = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([$orderNumber]);
        $order = $stmt->fetch();
        
        if (!$order) {
            throw new Exception('الطلب غير موجود');
        }
        
        // جلب عناصر الطلب
        $items = $conn->query("SELECT * FROM order_items WHERE order_id = {$order['id']}")->fetchAll();
        $order['items'] = $items;
        
        echo json_encode([
            'success' => true,
            'data' => $order
        ], JSON_UNESCAPED_UNICODE);
        
    } else {
        throw new Exception('طلب غير صالح');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>

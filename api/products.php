<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';

$db = new Database();
$conn = $db->getConnection();

$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : '';

try {
    switch ($action) {
        case 'list':
            // جلب قائمة المنتجات
            $category = isset($_GET['category']) ? $_GET['category'] : '';
            $search = isset($_GET['search']) ? $_GET['search'] : '';
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
            $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
            
            $where = ["p.status = 'active'"];
            
            if ($category) {
                $where[] = "c.slug = '$category'";
            }
            
            if ($search) {
                $where[] = "(p.name_ar LIKE '%$search%' OR p.name_en LIKE '%$search%' OR p.model LIKE '%$search%' OR p.brand LIKE '%$search%')";
            }
            
            $whereClause = implode(' AND ', $where);
            
            $sql = "SELECT p.*, c.name_ar as category_name, c.slug as category_slug
                    FROM products p
                    LEFT JOIN categories c ON p.category_id = c.id
                    WHERE $whereClause
                    ORDER BY p.created_at DESC
                    LIMIT $limit OFFSET $offset";
            
            $products = $conn->query($sql)->fetchAll();
            
            // إضافة الصور والمميزات لكل منتج
            foreach ($products as &$product) {
                // الصور
                $images = $conn->query("SELECT image_url FROM product_images WHERE product_id = {$product['id']} ORDER BY is_primary DESC, display_order")->fetchAll(PDO::FETCH_COLUMN);
                $product['images'] = $images;
                
                // المميزات
                $features = $conn->query("SELECT feature_ar, feature_en FROM product_features WHERE product_id = {$product['id']} ORDER BY display_order")->fetchAll();
                $product['features'] = array_column($features, 'feature_ar');
                $product['features_en'] = array_column($features, 'feature_en');
                
                // المواصفات
                $product['specs'] = [
                    'processor' => $product['processor'],
                    'ram' => $product['ram'],
                    'storage' => $product['storage'],
                    'gpu' => $product['gpu'],
                    'screenSize' => $product['screen_size'],
                    'screenResolution' => $product['screen_resolution'],
                    'battery' => $product['battery'],
                    'weight' => $product['weight'],
                    'os' => $product['os']
                ];
            }
            
            echo json_encode([
                'success' => true,
                'data' => $products,
                'total' => $conn->query("SELECT COUNT(*) as count FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE $whereClause")->fetch()['count']
            ], JSON_UNESCAPED_UNICODE);
            break;
            
        case 'get':
            // جلب منتج واحد
            $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
            $slug = isset($_GET['slug']) ? $_GET['slug'] : '';
            
            if ($id) {
                $where = "p.id = $id";
            } elseif ($slug) {
                $where = "p.slug = '$slug'";
            } else {
                throw new Exception('معرف المنتج مطلوب');
            }
            
            $sql = "SELECT p.*, c.name_ar as category_name, c.slug as category_slug
                    FROM products p
                    LEFT JOIN categories c ON p.category_id = c.id
                    WHERE $where AND p.status = 'active'";
            
            $product = $conn->query($sql)->fetch();
            
            if (!$product) {
                throw new Exception('المنتج غير موجود');
            }
            
            // الصور
            $images = $conn->query("SELECT image_url FROM product_images WHERE product_id = {$product['id']} ORDER BY is_primary DESC, display_order")->fetchAll(PDO::FETCH_COLUMN);
            $product['images'] = $images;
            
            // المميزات
            $features = $conn->query("SELECT feature_ar, feature_en FROM product_features WHERE product_id = {$product['id']} ORDER BY display_order")->fetchAll();
            $product['features'] = array_column($features, 'feature_ar');
            $product['features_en'] = array_column($features, 'feature_en');
            
            // المواصفات
            $product['specs'] = [
                'processor' => $product['processor'],
                'ram' => $product['ram'],
                'storage' => $product['storage'],
                'gpu' => $product['gpu'],
                'screenSize' => $product['screen_size'],
                'screenResolution' => $product['screen_resolution'],
                'battery' => $product['battery'],
                'weight' => $product['weight'],
                'os' => $product['os']
            ];
            
            // زيادة عدد المشاهدات
            $conn->query("UPDATE products SET views = views + 1 WHERE id = {$product['id']}");
            
            echo json_encode([
                'success' => true,
                'data' => $product
            ], JSON_UNESCAPED_UNICODE);
            break;
            
        case 'categories':
            // جلب الفئات
            $categories = $conn->query("SELECT * FROM categories WHERE status = 'active' ORDER BY display_order, name_ar")->fetchAll();
            
            // عدد المنتجات في كل فئة
            foreach ($categories as &$category) {
                $count = $conn->query("SELECT COUNT(*) as count FROM products WHERE category_id = {$category['id']} AND status = 'active'")->fetch()['count'];
                $category['products_count'] = $count;
            }
            
            echo json_encode([
                'success' => true,
                'data' => $categories
            ], JSON_UNESCAPED_UNICODE);
            break;
            
        case 'featured':
            // جلب المنتجات المميزة
            $sql = "SELECT p.*, c.name_ar as category_name, c.slug as category_slug,
                    (SELECT image_url FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) as main_image
                    FROM products p
                    LEFT JOIN categories c ON p.category_id = c.id
                    WHERE p.status = 'active' AND p.badge IN ('new', 'bestseller', 'sale')
                    ORDER BY p.created_at DESC
                    LIMIT 8";
            
            $products = $conn->query($sql)->fetchAll();
            
            foreach ($products as &$product) {
                $images = $conn->query("SELECT image_url FROM product_images WHERE product_id = {$product['id']} ORDER BY is_primary DESC")->fetchAll(PDO::FETCH_COLUMN);
                $product['images'] = $images;
            }
            
            echo json_encode([
                'success' => true,
                'data' => $products
            ], JSON_UNESCAPED_UNICODE);
            break;
            
        default:
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

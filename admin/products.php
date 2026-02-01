<?php
ob_start();
session_start();
require_once 'includes/functions.php';
global $pdo;

// معالجة الحذف قبل الإخراج
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    // حذف صور المنتج
    $images = $pdo->query("SELECT image_url FROM product_images WHERE product_id = $id")->fetchAll();
    foreach ($images as $img) {
        $filename = basename($img['image_url']);
        deleteImage($filename);
    }
    
    // حذف المنتج
    $pdo->query("DELETE FROM products WHERE id = $id");
    
    logActivity($pdo, $_SESSION['admin_id'], 'delete_product', "حذف منتج رقم $id");
    setFlashMessage('success', 'تم حذف المنتج بنجاح');
    header('Location: products.php');
    exit();
}

$pageTitle = 'إدارة المنتجات';
include 'includes/header.php';

// الحصول على الفئات
$categories = $pdo->query("SELECT * FROM categories WHERE is_active = 1 ORDER BY name_ar")->fetchAll();

// البحث والفلترة
$search = isset($_GET['search']) ? clean($_GET['search']) : '';
$category_filter = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$status_filter = isset($_GET['status']) ? clean($_GET['status']) : '';

$where = ['1=1'];
if ($search) {
    $where[] = "(name_ar LIKE '%$search%' OR name_en LIKE '%$search%' OR model LIKE '%$search%' OR brand LIKE '%$search%')";
}
if ($category_filter) {
    $where[] = "category_id = $category_filter";
}
if ($status_filter) {
    $where[] = "status = '$status_filter'";
}

$whereClause = implode(' AND ', $where);

// الترقيم
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

// إجمالي المنتجات
$totalProducts = $pdo->query("SELECT COUNT(*) as count FROM products WHERE $whereClause")->fetch()['count'];

// جلب المنتجات
$sql = "SELECT p.*, c.name_ar as category_name,
        (SELECT image_url FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) as main_image
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE $whereClause
        ORDER BY p.created_at DESC
        LIMIT $perPage OFFSET $offset";
$products = $pdo->query($sql)->fetchAll();
?>

<div class="mb-4">
    <div class="row align-items-center">
        <div class="col-md-6">
            <h2><i class="bi bi-box-seam text-primary me-2"></i>إدارة المنتجات</h2>
        </div>
        <div class="col-md-6 text-end">
            <a href="add-product.php" class="btn btn-primary">
                <i class="bi bi-plus-circle me-2"></i>إضافة منتج جديد
            </a>
        </div>
    </div>
</div>

<!-- الفلاتر والبحث -->
<div class="table-card mb-4">
    <form method="GET" class="row g-3">
        <div class="col-md-4">
            <input type="text" class="form-control" name="search" placeholder="بحث..." value="<?php echo $search; ?>">
        </div>
        
        <div class="col-md-3">
            <select class="form-select" name="category">
                <option value="">جميع الفئات</option>
                <?php foreach ($categories as $cat): ?>
                <option value="<?php echo $cat['id']; ?>" <?php echo $category_filter == $cat['id'] ? 'selected' : ''; ?>>
                    <?php echo $cat['name_ar']; ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="col-md-3">
            <select class="form-select" name="status">
                <option value="">جميع الحالات</option>
                <option value="active" <?php echo $status_filter == 'active' ? 'selected' : ''; ?>>نشط</option>
                <option value="inactive" <?php echo $status_filter == 'inactive' ? 'selected' : ''; ?>>غير نشط</option>
                <option value="draft" <?php echo $status_filter == 'draft' ? 'selected' : ''; ?>>مسودة</option>
            </select>
        </div>
        
        <div class="col-md-2">
            <button type="submit" class="btn btn-primary w-100">
                <i class="bi bi-search me-2"></i>بحث
            </button>
        </div>
    </form>
</div>

<!-- جدول المنتجات -->
<div class="table-card">
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th width="80">الصورة</th>
                    <th>اسم المنتج</th>
                    <th>الموديل</th>
                    <th>العلامة</th>
                    <th>الفئة</th>
                    <th>السعر</th>
                    <th>المخزون</th>
                    <th>الحالة</th>
                    <th width="150">الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $product): ?>
                <tr>
                    <td>
                        <?php if ($product['main_image']): ?>
                        <img src="../<?php echo $product['main_image']; ?>" alt="<?php echo $product['name_ar']; ?>" 
                             style="width: 60px; height: 60px; object-fit: cover; border-radius: 8px;">
                        <?php else: ?>
                        <div style="width: 60px; height: 60px; background: #e9ecef; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                            <i class="bi bi-image text-muted"></i>
                        </div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <strong><?php echo $product['name_ar']; ?></strong>
                        <?php if ($product['badge'] != 'none'): ?>
                        <br><small class="badge bg-info"><?php echo $product['badge']; ?></small>
                        <?php endif; ?>
                    </td>
                    <td><?php echo $product['model']; ?></td>
                    <td><?php echo $product['brand']; ?></td>
                    <td><?php echo $product['category_name']; ?></td>
                    <td>
                        <strong class="text-success"><?php echo formatPrice($product['price']); ?></strong>
                        <?php if ($product['original_price']): ?>
                        <br><small class="text-muted text-decoration-line-through"><?php echo formatPrice($product['original_price']); ?></small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($product['stock_count'] > 0): ?>
                        <span class="badge bg-success"><?php echo $product['stock_count']; ?> متوفر</span>
                        <?php else: ?>
                        <span class="badge bg-danger">غير متوفر</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php
                        $statusBadge = [
                            'active' => 'success',
                            'inactive' => 'danger',
                            'draft' => 'secondary'
                        ];
                        $statusText = [
                            'active' => 'نشط',
                            'inactive' => 'غير نشط',
                            'draft' => 'مسودة'
                        ];
                        ?>
                        <span class="badge bg-<?php echo $statusBadge[$product['status']]; ?>">
                            <?php echo $statusText[$product['status']]; ?>
                        </span>
                    </td>
                    <td>
                        <a href="edit-product.php?id=<?php echo $product['id']; ?>" class="btn btn-sm btn-outline-primary" title="تعديل">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <a href="?delete=<?php echo $product['id']; ?>" class="btn btn-sm btn-outline-danger" 
                           onclick="return confirmDelete('هل أنت متأكد من حذف هذا المنتج؟')" title="حذف">
                            <i class="bi bi-trash"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
                
                <?php if (empty($products)): ?>
                <tr>
                    <td colspan="9" class="text-center text-muted py-5">
                        <i class="bi bi-inbox" style="font-size: 48px;"></i>
                        <p class="mt-3">لا توجد منتجات</p>
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <!-- الترقيم -->
    <?php if ($totalProducts > $perPage): ?>
    <div class="mt-4">
        <?php echo pagination($totalProducts, $perPage, $page, 'products.php'); ?>
    </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>

<?php
ob_start();
session_start();
require_once 'includes/functions.php';
global $pdo;

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: products.php');
    exit;
}

$id = (int)$_GET['id'];
$product = $pdo->query("SELECT * FROM products WHERE id = $id")->fetch();

if (!$product) {
    header('Location: products.php');
    exit;
}

// معالجة تعيين الصورة الرئيسية
if (isset($_GET['set_primary'])) {
    $imgId = (int)$_GET['set_primary'];
    // التحقق من وجود الصورة
    $check = $pdo->query("SELECT id FROM product_images WHERE id = $imgId AND product_id = $id")->fetch();
    if ($check) {
        // جعل الكل غير رئيسي
        $pdo->prepare("UPDATE product_images SET is_primary = 0 WHERE product_id = ?")->execute([$id]);
        // جعل المختارة رئيسية
        $pdo->prepare("UPDATE product_images SET is_primary = 1 WHERE id = ?")->execute([$imgId]);
        setFlashMessage('success', 'تم تعيين الصورة الرئيسية بنجاح');
    }
    header("Location: edit-product.php?id=$id");
    exit;
}

// معالجة حذف الصورة قبل الإخراج
if (isset($_GET['delete_image'])) {
    $imgId = (int)$_GET['delete_image'];
    $img = $pdo->query("SELECT image_url, is_primary FROM product_images WHERE id = $imgId AND product_id = $id")->fetch();
    
    if ($img) {
        $pdo->prepare("DELETE FROM product_images WHERE id = ?")->execute([$imgId]);
        deleteImage(basename($img['image_url']));
        
        // إذا حذفت الرئيسية، اجعل أحدث صورة هي الرئيسية تلقائياً
        if ($img['is_primary']) {
            $newPrimary = $pdo->query("SELECT id FROM product_images WHERE product_id = $id ORDER BY id DESC LIMIT 1")->fetch();
            if ($newPrimary) {
                $pdo->prepare("UPDATE product_images SET is_primary = 1 WHERE id = ?")->execute([$newPrimary['id']]);
            }
        }
        
        setFlashMessage('success', 'تم حذف الصورة بنجاح');
    }
    header("Location: edit-product.php?id=$id");
    exit;
}

// معالجة التحديث قبل الإخراج
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'name_ar' => clean($_POST['name_ar']),
        'name_en' => clean($_POST['name_en']),
        'slug' => clean($_POST['slug']),
        'sku' => clean($_POST['sku']),
        'model' => clean($_POST['model']),
        'brand' => clean($_POST['brand']),
        'category_id' => (int)$_POST['category_id'],
        'price' => (float)$_POST['price'],
        'original_price' => !empty($_POST['original_price']) ? (float)$_POST['original_price'] : null,
        'stock_count' => (int)$_POST['stock_count'],
        'processor' => clean($_POST['processor']),
        'ram' => clean($_POST['ram']),
        'storage' => clean($_POST['storage']),
        'gpu' => clean($_POST['gpu']),
        'screen_size' => clean($_POST['screen_size']),
        'screen_resolution' => clean($_POST['screen_resolution']),
        'battery' => clean($_POST['battery']),
        'weight' => clean($_POST['weight']),
        'os' => clean($_POST['os']),
        'description_short' => clean($_POST['description_short']),
        'description_full' => clean($_POST['description_full']),
        'warranty' => clean($_POST['warranty']),
        'badge' => clean($_POST['badge']),
        'status' => clean($_POST['status'])
    ];
    
    try {
        if (updateProduct($id, $data)) {
            // Handle Main Image Update
            if (isset($_FILES['main_image']) && $_FILES['main_image']['error'] == 0) {
                $upload = uploadImage($_FILES['main_image'], 'products');
                if ($upload['success']) {
                    // Unset old primary
                    $pdo->prepare("UPDATE product_images SET is_primary = 0 WHERE product_id = ?")->execute([$id]);
                    // Insert new primary
                    $pdo->prepare("INSERT INTO product_images (product_id, image_url, is_primary) VALUES (?, ?, 1)")
                        ->execute([$id, $upload['path']]);
                }
            }
            
            // Handle Additional Images
            if (isset($_FILES['images'])) {
                $count = count($_FILES['images']['name']);
                for ($i = 0; $i < $count; $i++) {
                    if ($_FILES['images']['error'][$i] == 0) {
                        $file = [
                            'name' => $_FILES['images']['name'][$i],
                            'type' => $_FILES['images']['type'][$i],
                            'tmp_name' => $_FILES['images']['tmp_name'][$i],
                            'error' => $_FILES['images']['error'][$i],
                            'size' => $_FILES['images']['size'][$i]
                        ];
                        
                        $upload = uploadImage($file, 'products');
                        if ($upload['success']) {
                            $pdo->prepare("INSERT INTO product_images (product_id, image_url, is_primary) VALUES (?, ?, 0)")
                                ->execute([$id, $upload['path']]);
                        }
                    }
                }
            }
            
            setFlashMessage('success', 'تم تحديث المنتج بنجاح');
            header("Location: edit-product.php?id=$id");
            exit;
        } else {
            setFlashMessage('danger', 'حدث خطأ أثناء تحديث المنتج');
        }
    } catch (PDOException $e) {
        setFlashMessage('danger', 'خطأ في قاعدة البيانات: ' . $e->getMessage());
    }
}

$pageTitle = 'تعديل المنتج';
include 'includes/header.php';

// Get Data
$categories = $pdo->query("SELECT * FROM categories WHERE is_active = 1 ORDER BY name_ar")->fetchAll();
$images = $pdo->query("SELECT * FROM product_images WHERE product_id = $id ORDER BY is_primary DESC")->fetchAll();
?>

<div class="row">
    <div class="col-12 mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <h2>تعديل المنتج: <?php echo htmlspecialchars($product['name_ar']); ?></h2>
            <div>
                <a href="products.php" class="btn btn-secondary me-2">
                    <i class="bi bi-arrow-right me-2"></i>رجوع
                </a>
                <a href="../product.php?id=<?php echo $product['id']; ?>" target="_blank" class="btn btn-outline-primary">
                    <i class="bi bi-eye me-2"></i>معاينة
                </a>
            </div>
        </div>
    </div>
</div>

<form method="POST" enctype="multipart/form-data" class="row g-3">
    <!-- Same fields as add-product but with values -->
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header fw-bold">معلومات المنتج الأساسية</div>
            <div class="card-body row g-3">
                <div class="col-md-6">
                    <label class="form-label">الاسم (بالعربية)</label>
                    <input type="text" name="name_ar" class="form-control" required value="<?php echo htmlspecialchars($product['name_ar']); ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">الاسم (بالانجليزية)</label>
                    <input type="text" name="name_en" class="form-control" value="<?php echo htmlspecialchars($product['name_en']); ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">الرابط الدائم (Slug)</label>
                    <input type="text" name="slug" class="form-control" value="<?php echo htmlspecialchars($product['slug']); ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">الفئة</label>
                    <select name="category_id" class="form-select" required>
                        <option value="">اختر الفئة</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" <?php echo $product['category_id'] == $cat['id'] ? 'selected' : ''; ?>>
                                <?php echo $cat['name_ar']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">SKU</label>
                    <input type="text" name="sku" class="form-control" value="<?php echo htmlspecialchars($product['sku']); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">الموديل</label>
                    <input type="text" name="model" class="form-control" value="<?php echo htmlspecialchars($product['model']); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">العلامة التجارية</label>
                    <input type="text" name="brand" class="form-control" value="<?php echo htmlspecialchars($product['brand']); ?>">
                </div>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header fw-bold">الوصف</div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">وصف قصير</label>
                    <textarea name="description_short" class="form-control" rows="3"><?php echo htmlspecialchars($product['description_short']); ?></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">وصف كامل</label>
                    <textarea name="description_full" class="form-control" rows="6"><?php echo htmlspecialchars($product['description_full']); ?></textarea>
                </div>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header fw-bold">المواصفات التقنية</div>
            <div class="card-body row g-3">
                <div class="col-md-4">
                    <label class="form-label">المعالج</label>
                    <input type="text" name="processor" class="form-control" value="<?php echo htmlspecialchars($product['processor']); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">الرام</label>
                    <input type="text" name="ram" class="form-control" value="<?php echo htmlspecialchars($product['ram']); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">التخزين</label>
                    <input type="text" name="storage" class="form-control" value="<?php echo htmlspecialchars($product['storage']); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">GPU</label>
                    <input type="text" name="gpu" class="form-control" value="<?php echo htmlspecialchars($product['gpu']); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">الشاشة</label>
                    <input type="text" name="screen_size" class="form-control" value="<?php echo htmlspecialchars($product['screen_size']); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">الدقة</label>
                    <input type="text" name="screen_resolution" class="form-control" value="<?php echo htmlspecialchars($product['screen_resolution']); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">البطارية</label>
                    <input type="text" name="battery" class="form-control" value="<?php echo htmlspecialchars($product['battery']); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">الوزن</label>
                    <input type="text" name="weight" class="form-control" value="<?php echo htmlspecialchars($product['weight']); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">نظام التشغيل</label>
                    <input type="text" name="os" class="form-control" value="<?php echo htmlspecialchars($product['os']); ?>">
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card mb-4">
            <div class="card-header fw-bold">السعر والمخزون</div>
            <div class="card-body row g-3">
                <div class="col-12">
                    <label class="form-label">السعر</label>
                    <div class="input-group">
                        <input type="number" name="price" class="form-control" step="0.01" required value="<?php echo $product['price']; ?>">
                        <span class="input-group-text">د.ج</span>
                    </div>
                </div>
                <div class="col-12">
                    <label class="form-label">السعر الأصلي</label>
                    <div class="input-group">
                        <input type="number" name="original_price" class="form-control" step="0.01" value="<?php echo $product['original_price']; ?>">
                        <span class="input-group-text">د.ج</span>
                    </div>
                </div>
                <div class="col-12">
                    <label class="form-label">الكمية</label>
                    <input type="number" name="stock_count" class="form-control" required value="<?php echo $product['stock_count']; ?>">
                </div>
                <div class="col-12">
                    <label class="form-label">الحالة</label>
                    <select name="status" class="form-select">
                        <option value="active" <?php echo $product['status'] == 'active' ? 'selected' : ''; ?>>نشط</option>
                        <option value="inactive" <?php echo $product['status'] == 'inactive' ? 'selected' : ''; ?>>غير نشط</option>
                        <option value="draft" <?php echo $product['status'] == 'draft' ? 'selected' : ''; ?>>مسودة</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">شارة</label>
                    <select name="badge" class="form-select">
                        <option value="none" <?php echo $product['badge'] == 'none' ? 'selected' : ''; ?>>بدون</option>
                        <option value="new" <?php echo $product['badge'] == 'new' ? 'selected' : ''; ?>>جديد</option>
                        <option value="sale" <?php echo $product['badge'] == 'sale' ? 'selected' : ''; ?>>تخفيض</option>
                        <option value="hot" <?php echo $product['badge'] == 'hot' ? 'selected' : ''; ?>>رائج</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">الضمان</label>
                    <input type="text" name="warranty" class="form-control" value="<?php echo htmlspecialchars($product['warranty']); ?>">
                </div>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header fw-bold">الصور</div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">صور المنتج الحالية</label>
                    <div class="row g-2">
                        <?php foreach ($images as $img): ?>
                        <div class="col-4 position-relative">
                            <img src="../<?php echo htmlspecialchars($img['image_url']); ?>" class="img-fluid rounded border">
                            <?php if ($img['is_primary']): ?>
                                <span class="position-absolute top-0 start-0 badge bg-primary m-1">رئيسية</span>
                                <a href="?id=<?php echo $id; ?>&delete_image=<?php echo $img['id']; ?>" 
                                   class="position-absolute top-0 end-0 btn btn-sm btn-danger m-1 p-0 px-1"
                                   onclick="return confirm('حذف الصورة الرئيسية؟ سيتم تعيين صورة أخرى كرئيسية تلقائياً.')">×</a>
                            <?php else: ?>
                                <a href="?id=<?php echo $id; ?>&set_primary=<?php echo $img['id']; ?>" 
                                   class="position-absolute bottom-0 start-0 badge bg-secondary m-1 text-decoration-none">تعيين كرئيسية</a>
                                <a href="?id=<?php echo $id; ?>&delete_image=<?php echo $img['id']; ?>" 
                                   class="position-absolute top-0 end-0 btn btn-sm btn-danger m-1 p-0 px-1"
                                   onclick="return confirm('حذف الصورة؟')">×</a>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">تحديث الصورة الرئيسية</label>
                    <input type="file" name="main_image" class="form-control" accept="image/*">
                    <div class="form-text">اختيار صورة جديدة سيستبدل الصورة الرئيسية الحالية</div>
                </div>
                <div class="mb-3">
                    <label class="form-label">إضافة صور</label>
                    <input type="file" name="images[]" class="form-control" accept="image/*" multiple>
                </div>
            </div>
        </div>
        
        <button type="submit" class="btn btn-primary w-100 btn-lg mb-3">
            <i class="bi bi-save me-2"></i>حفظ التغييرات
        </button>
    </div>
</form>

<?php include 'includes/footer.php'; ?>

<?php
ob_start();
session_start();
require_once 'includes/functions.php';
global $pdo;

// معالجة الإضافة قبل الإخراج
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
        if (addProduct($data)) {
            $productId = $pdo->lastInsertId();
            
            // Handle Main Image
            if (isset($_FILES['main_image']) && $_FILES['main_image']['error'] == 0) {
                $upload = uploadImage($_FILES['main_image'], 'products');
                if ($upload['success']) {
                    $pdo->prepare("INSERT INTO product_images (product_id, image_url, is_primary) VALUES (?, ?, 1)")
                        ->execute([$productId, $upload['path']]);
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
                                ->execute([$productId, $upload['path']]);
                        }
                    }
                }
            }
            
            setFlashMessage('success', 'تم إضافة المنتج بنجاح');
            header('Location: products.php');
            exit;
        } else {
            setFlashMessage('danger', 'حدث خطأ أثناء إضافة المنتج');
        }
    } catch (PDOException $e) {
        setFlashMessage('danger', 'خطأ في قاعدة البيانات: ' . $e->getMessage());
    }
}

$pageTitle = 'إضافة منتج جديد';
include 'includes/header.php';

// Get Categories for Select
$categories = $pdo->query("SELECT * FROM categories WHERE is_active = 1 ORDER BY name_ar")->fetchAll();
?>

<div class="row">
    <div class="col-12 mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <h2>إضافة منتج جديد</h2>
            <a href="products.php" class="btn btn-secondary">
                <i class="bi bi-arrow-right me-2"></i>رجوع
            </a>
        </div>
    </div>
</div>

<form method="POST" enctype="multipart/form-data" class="row g-3">
    <!-- معلومات أساسية -->
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header fw-bold">معلومات المنتج الأساسية</div>
            <div class="card-body row g-3">
                <div class="col-md-6">
                    <label class="form-label">الاسم (بالعربية) <span class="text-danger">*</span></label>
                    <input type="text" name="name_ar" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">الاسم (بالانجليزية)</label>
                    <input type="text" name="name_en" class="form-control">
                </div>
                <div class="col-md-6">
                    <label class="form-label">الرابط الدائم (Slug)</label>
                    <input type="text" name="slug" class="form-control">
                </div>
                <div class="col-md-6">
                    <label class="form-label">الفئة <span class="text-danger">*</span></label>
                    <select name="category_id" class="form-select" required>
                        <option value="">اختر الفئة</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>"><?php echo $cat['name_ar']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">SKU</label>
                    <input type="text" name="sku" class="form-control">
                </div>
                <div class="col-md-4">
                    <label class="form-label">الموديل</label>
                    <input type="text" name="model" class="form-control">
                </div>
                <div class="col-md-4">
                    <label class="form-label">العلامة التجارية</label>
                    <input type="text" name="brand" class="form-control">
                </div>
            </div>
        </div>
        
        <!-- الوصف -->
        <div class="card mb-4">
            <div class="card-header fw-bold">الوصف</div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">وصف قصير</label>
                    <textarea name="description_short" class="form-control" rows="3"></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">وصف كامل</label>
                    <textarea name="description_full" class="form-control" rows="6"></textarea>
                </div>
            </div>
        </div>
        
        <!-- المواصفات التقنية -->
        <div class="card mb-4">
            <div class="card-header fw-bold">المواصفات التقنية</div>
            <div class="card-body row g-3">
                <div class="col-md-4">
                    <label class="form-label">المعالج (Processor)</label>
                    <input type="text" name="processor" class="form-control">
                </div>
                <div class="col-md-4">
                    <label class="form-label">الذاكرة (RAM)</label>
                    <input type="text" name="ram" class="form-control">
                </div>
                <div class="col-md-4">
                    <label class="form-label">التخزين (Storage)</label>
                    <input type="text" name="storage" class="form-control">
                </div>
                <div class="col-md-4">
                    <label class="form-label">كرت الشاشة (GPU)</label>
                    <input type="text" name="gpu" class="form-control">
                </div>
                <div class="col-md-4">
                    <label class="form-label">حجم الشاشة</label>
                    <input type="text" name="screen_size" class="form-control">
                </div>
                <div class="col-md-4">
                    <label class="form-label">دقة الشاشة</label>
                    <input type="text" name="screen_resolution" class="form-control">
                </div>
                <div class="col-md-4">
                    <label class="form-label">البطارية</label>
                    <input type="text" name="battery" class="form-control">
                </div>
                <div class="col-md-4">
                    <label class="form-label">الوزن</label>
                    <input type="text" name="weight" class="form-control">
                </div>
                <div class="col-md-4">
                    <label class="form-label">نظام التشغيل</label>
                    <input type="text" name="os" class="form-control">
                </div>
            </div>
        </div>
    </div>
    
    <!-- السعر والمخزون والصور -->
    <div class="col-lg-4">
        <div class="card mb-4">
            <div class="card-header fw-bold">السعر والمخزون</div>
            <div class="card-body row g-3">
                <div class="col-12">
                    <label class="form-label">السعر <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="number" name="price" class="form-control" step="0.01" required>
                        <span class="input-group-text">د.ج</span>
                    </div>
                </div>
                <div class="col-12">
                    <label class="form-label">السعر الأصلي (قبل الخصم)</label>
                    <div class="input-group">
                        <input type="number" name="original_price" class="form-control" step="0.01">
                        <span class="input-group-text">د.ج</span>
                    </div>
                </div>
                <div class="col-12">
                    <label class="form-label">الكمية المتوفرة <span class="text-danger">*</span></label>
                    <input type="number" name="stock_count" class="form-control" required value="1">
                </div>
                <div class="col-12">
                    <label class="form-label">الحالة</label>
                    <select name="status" class="form-select">
                        <option value="active" selected>نشط</option>
                        <option value="inactive">غير نشط</option>
                        <option value="draft">مسودة</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">شارة (Badge)</label>
                    <select name="badge" class="form-select">
                        <option value="none">بدون</option>
                        <option value="new">جديد</option>
                        <option value="sale">تخفيض</option>
                        <option value="hot">رائج</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">الضمان</label>
                    <input type="text" name="warranty" class="form-control" placeholder="مثال: 12 شهر">
                </div>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header fw-bold">الصور</div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">الصورة الرئيسية <span class="text-danger">*</span></label>
                    <input type="file" name="main_image" class="form-control" accept="image/*" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">صور إضافية</label>
                    <input type="file" name="images[]" class="form-control" accept="image/*" multiple>
                </div>
            </div>
        </div>
        
        <button type="submit" class="btn btn-primary w-100 btn-lg mb-3">
            <i class="bi bi-check-circle me-2"></i>حفظ المنتج
        </button>
    </div>
</form>

<?php include 'includes/footer.php'; ?>

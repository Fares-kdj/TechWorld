<?php
ob_start();
session_start();
require_once 'includes/functions.php';
global $pdo;

// معالجة الحذف قبل الإخراج
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    // التحقق من وجود منتجات في الفئة
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM products WHERE category_id = ?");
    $stmt->execute([$id]);
    $count = $stmt->fetch()['count'];
    
    if ($count > 0) {
        setFlashMessage('error', 'لا يمكن حذف الفئة لأنها تحتوي على ' . $count . ' منتج');
    } else {
        $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
        if ($stmt->execute([$id])) {
            setFlashMessage('success', 'تم حذف الفئة بنجاح');
        } else {
            setFlashMessage('error', 'حدث خطأ أثناء الحذف');
        }
    }
    header('Location: categories.php');
    exit;
}

// معالجة الإضافة/التعديل قبل الإخراج
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name_ar = sanitizeInput($_POST['name_ar']);
    $name_en = sanitizeInput($_POST['name_en']);
    $description = sanitizeInput($_POST['description']);
    $icon = sanitizeInput($_POST['icon']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // إنشاء Slug
    $slug = createSlug($name_en);
    if (empty($slug)) {
        $slug = uniqid('cat_'); 
    }
    
    // التحقق من تكرار Slug (اختياري، لكن أفضل للتجربة المستخدم)
    $checkSql = "SELECT id FROM categories WHERE slug = ?";
    $params = [$slug];
    if (isset($_POST['category_id']) && !empty($_POST['category_id'])) {
        $checkSql .= " AND id != ?";
        $params[] = $_POST['category_id'];
    }
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->execute($params);
    if ($checkStmt->fetch()) {
        $slug .= '-' . time(); // Avoid duplicate
    }
    
    if (isset($_POST['category_id']) && !empty($_POST['category_id'])) {
        // تحديث
        $id = (int)$_POST['category_id'];
        $stmt = $pdo->prepare("UPDATE categories SET name_ar = ?, name_en = ?, slug = ?, description = ?, icon = ?, is_active = ? WHERE id = ?");
        if ($stmt->execute([$name_ar, $name_en, $slug, $description, $icon, $is_active, $id])) {
            setFlashMessage('success', 'تم تحديث الفئة بنجاح');
        }
    } else {
        // إضافة
        $stmt = $pdo->prepare("INSERT INTO categories (name_ar, name_en, slug, description, icon, is_active) VALUES (?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$name_ar, $name_en, $slug, $description, $icon, $is_active])) {
            setFlashMessage('success', 'تمت إضافة الفئة بنجاح');
        }
    }
    
    header('Location: categories.php');
    exit;
}

$pageTitle = 'إدارة الفئات';
include 'includes/header.php';

// جلب جميع الفئات
$categories = $pdo->query("
    SELECT c.*, COUNT(p.id) as products_count
    FROM categories c
    LEFT JOIN products p ON c.id = p.category_id
    GROUP BY c.id
    ORDER BY c.name_ar
")->fetchAll();

// إذا كان هناك تعديل، جلب بيانات الفئة
$editCategory = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $editCategory = $stmt->fetch();
}
?>

<div class="row g-4">
    <!-- نموذج إضافة/تعديل -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-<?php echo $editCategory ? 'pencil' : 'plus'; ?>-circle me-2"></i>
                    <?php echo $editCategory ? 'تعديل الفئة' : 'إضافة فئة جديدة'; ?>
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <?php if ($editCategory): ?>
                        <input type="hidden" name="category_id" value="<?php echo $editCategory['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="mb-3">
                        <label class="form-label">الاسم بالعربية <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name_ar" 
                               value="<?php echo $editCategory['name_ar'] ?? ''; ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">الاسم بالإنجليزية <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name_en" 
                               value="<?php echo $editCategory['name_en'] ?? ''; ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">الوصف</label>
                        <textarea class="form-control" name="description" rows="3"><?php echo $editCategory['description'] ?? ''; ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">الأيقونة (رمز تعبيري / Emoji)</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <?php echo $editCategory['icon'] ?? '🏷️'; ?>
                            </span>
                            <input type="text" class="form-control" name="icon" 
                                   value="<?php echo $editCategory['icon'] ?? ''; ?>" 
                                   placeholder="مثال: 🎮, 💻, 📱">
                        </div>
                        <small class="text-muted">
                            يمكنك نسخ ولصق أي رمز تعبيري (Emoji) هنا
                        </small>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_active" id="is_active"
                                   <?php echo (!isset($editCategory) || $editCategory['is_active']) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="is_active">
                                نشط
                            </label>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle me-2"></i>
                            <?php echo $editCategory ? 'تحديث' : 'إضافة'; ?>
                        </button>
                        <?php if ($editCategory): ?>
                            <a href="categories.php" class="btn btn-secondary">
                                <i class="bi bi-x-circle me-2"></i>إلغاء
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- قائمة الفئات -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="bi bi-tags me-2"></i>جميع الفئات
                </h5>
                <span class="badge bg-primary"><?php echo count($categories); ?> فئة</span>
            </div>
            <div class="card-body">
                <?php if (empty($categories)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-inbox" style="font-size: 48px; color: #ccc;"></i>
                        <p class="text-muted mt-3">لا توجد فئات بعد</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th style="width: 60px;">الأيقونة</th>
                                    <th>الاسم</th>
                                    <th>الوصف</th>
                                    <th style="width: 100px;">المنتجات</th>
                                    <th style="width: 80px;">الحالة</th>
                                    <th style="width: 150px;">الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($categories as $category): ?>
                                    <tr>
                                        <td>
                                            <div class="category-icon">
                                                <?php echo $category['icon'] ?: '🏷️'; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($category['name_ar']); ?></strong>
                                            <br>
                                            <small class="text-muted"><?php echo htmlspecialchars($category['name_en']); ?></small>
                                            <br>
                                            <small class="text-muted" style="font-size: 0.75rem;">Slug: <?php echo htmlspecialchars($category['slug']); ?></small>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                <?php 
                                                $desc = $category['description'];
                                                echo $desc ? (mb_strlen($desc) > 50 ? mb_substr($desc, 0, 50) . '...' : $desc) : '-';
                                                ?>
                                            </small>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-info">
                                                <?php echo $category['products_count']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($category['is_active']): ?>
                                                <span class="badge bg-success">نشط</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">معطل</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="?edit=<?php echo $category['id']; ?>" 
                                                   class="btn btn-outline-primary" title="تعديل">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <?php if ($category['products_count'] == 0): ?>
                                                    <a href="?delete=<?php echo $category['id']; ?>" 
                                                       class="btn btn-outline-danger"
                                                       onclick="return confirm('هل أنت متأكد من حذف هذه الفئة؟')" 
                                                       title="حذف">
                                                        <i class="bi bi-trash"></i>
                                                    </a>
                                                <?php else: ?>
                                                    <button class="btn btn-outline-secondary" disabled title="لا يمكن الحذف">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
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
    </div>
</div>

<style>
.category-icon {
    width: 45px;
    height: 45px;
    border-radius: 10px;
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 20px;
}

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

.table thead th {
    border-bottom: 2px solid #dee2e6;
    font-weight: 600;
    color: #495057;
    background: #f8f9fa;
}

.form-label {
    font-weight: 600;
    color: #495057;
}

.btn-group-sm .btn {
    padding: 0.25rem 0.5rem;
}
</style>

<?php include 'includes/footer.php'; ?>

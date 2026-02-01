<?php
require_once 'includes/functions.php';

// 1. Get Filters from DB to populate Sidebar
$brands = getUniqueValues('brand');
$processors = getUniqueValues('processor');
$rams = getUniqueValues('ram');
$storages = getUniqueValues('storage');
$gpus = getUniqueValues('gpu');

// 2. Process GET Parameters
$filters = [
    'search' => $_GET['search'] ?? null,
    'category' => $_GET['category'] ?? null,
    'price_range' => $_GET['price_range'] ?? [],
    'brand' => $_GET['brand'] ?? [],
    'processor' => $_GET['processor'] ?? [],
    'ram' => $_GET['ram'] ?? [],
    'storage' => $_GET['storage'] ?? [],
    'gpu' => $_GET['gpu'] ?? []
];

$sort = $_GET['sort'] ?? 'newest';

// 3. Fetch Products
$products = getFilteredProducts($filters, $sort);

// 4. Determine Page Title
$pageTitle = 'المنتجات';
if ($filters['search']) $pageTitle = 'نتائج البحث: ' . clean($filters['search']);
elseif ($filters['category']) {
    $cat = getCategoryBySlug($filters['category']);
    if ($cat) $pageTitle = $cat['name_ar'];
}

$categories = getAllCategories();
$currency = getSetting('currency_symbol', 'دج');

include 'includes/header.php';
?>

<style>
/* Sidebar Layout Styles */
.products-layout {
    display: grid;
    grid-template-columns: 280px 1fr;
    gap: 2rem;
    align-items: start;
}

.filter-sidebar {
    background: #fff;
    border-radius: var(--radius);
    padding: 1.5rem;
    box-shadow: var(--shadow);
    position: sticky;
    top: 6rem;
    max-height: calc(100vh - 7rem);
    overflow-y: auto;
}

.filter-group {
    margin-bottom: 1.5rem;
    border-bottom: 1px solid #f3f4f6;
    padding-bottom: 1rem;
}
.filter-group:last-child { border-bottom: none; margin-bottom: 0; padding-bottom: 0; }

.filter-title {
    font-weight: 700;
    margin-bottom: 0.75rem;
    color: var(--dark);
    font-size: 1rem;
}

.filter-options {
    max-height: 200px;
    overflow-y: auto;
    padding-left: 0.5rem; /* Space for scrollbar */
}
/* Slim Scrollbar */
.filter-options::-webkit-scrollbar { width: 4px; }
.filter-options::-webkit-scrollbar-thumb { background-color: #e5e7eb; border-radius: 4px; }

.filter-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.5rem;
    cursor: pointer;
    font-size: 0.9rem;
    color: var(--gray-600);
    transition: color 0.2s;
}
.filter-label:hover { color: var(--primary); }

.filter-checkbox {
    width: 16px;
    height: 16px;
    accent-color: var(--primary);
    border-radius: 4px;
    border: 1px solid #d1d5db;
}

.mobile-filter-toggle {
    display: none;
    width: 100%;
    margin-bottom: 1rem;
    justify-content: center;
    align-items: center;
    gap: 0.5rem;
}

/* Mobile Responsive */
@media (max-width: 992px) {
    .products-layout { grid-template-columns: 1fr; }
    
    .filter-sidebar {
        display: none;
        position: fixed;
        top: 0; left: 0; right: 0; bottom: 0;
        z-index: 2000;
        max-height: none;
        border-radius: 0;
        padding-top: 4rem; /* Space for close btn */
    }
    
    .filter-sidebar.active { display: block; animation: fadeIn 0.2s; }
    .mobile-filter-toggle { display: flex; }
    
    .close-filter {
        position: absolute;
        top: 1rem;
        left: 1rem; 
        background: #f3f4f6;
        border-radius: 50%;
        width: 40px;
        height: 40px;
        display: flex; align-items: center; justify-content: center;
        border: none;
        cursor: pointer;
        z-index: 10;
    }
    .close-filter svg { width: 24px; height: 24px; }
}

@keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
</style>

<div class="container" style="padding: 2rem 1rem;">
    
    <!-- Header & Sort -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h1 style="font-size: 2rem; font-weight: 700; margin-bottom: 0.5rem;"><?php echo clean($pageTitle); ?></h1>
            <p style="color: var(--gray-600);">عثرنا على <?php echo count($products); ?> منتج</p>
        </div>
        
        <!-- Mobile Filter Button -->
        <button class="btn btn-secondary mobile-filter-toggle" onclick="toggleFilterSidebar()">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 3H2l8 9.46V19l4 2v-8.54L22 3z"></path></svg>
            تصفية
        </button>
    </div>

    <div class="products-layout">
        <!-- Sidebar Filter -->
        <aside class="filter-sidebar" id="filterSidebar">
            <button class="close-filter mobile-only" onclick="toggleFilterSidebar()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            </button>

            <form method="GET" action="products.php">
                <?php if ($filters['search']): ?><input type="hidden" name="search" value="<?php echo clean($filters['search']); ?>"><?php endif; ?>
                <?php if ($filters['category']): ?><input type="hidden" name="category" value="<?php echo clean($filters['category']); ?>"><?php endif; ?>

                <!-- Price Range -->
                <div class="filter-group">
                    <div class="filter-title">السعر</div>
                    <div class="filter-options">
                        <label class="filter-label">
                            <input type="checkbox" name="price_range[]" value="under_40k" class="filter-checkbox" <?php echo in_array('under_40k', $filters['price_range'])?'checked':''; ?>>
                            أقل من 40,000 دج
                        </label>
                        <label class="filter-label">
                            <input type="checkbox" name="price_range[]" value="40k_100k" class="filter-checkbox" <?php echo in_array('40k_100k', $filters['price_range'])?'checked':''; ?>>
                            40,000 - 100,000 دج
                        </label>
                        <label class="filter-label">
                            <input type="checkbox" name="price_range[]" value="above_100k" class="filter-checkbox" <?php echo in_array('above_100k', $filters['price_range'])?'checked':''; ?>>
                            أكثر من 100,000 دج
                        </label>
                    </div>
                </div>

                <!-- Attributes -->
                <?php 
                $attrMap = [
                    'brand'     => ['title' => 'العلامة التجارية', 'data' => $brands],
                    'processor' => ['title' => 'المعالج (CPU)', 'data' => $processors],
                    'ram'     => ['title' => 'الذاكرة (RAM)', 'data' => $rams],
                    'storage'   => ['title' => 'التخزين', 'data' => $storages],
                    'gpu'       => ['title' => 'كرت الشاشة', 'data' => $gpus],
                ];

                foreach ($attrMap as $key => $info):
                    if (empty($info['data'])) continue;
                    // Sort locally if string
                    sort($info['data']); 
                ?>
                <div class="filter-group">
                    <div class="filter-title"><?php echo $info['title']; ?></div>
                    <div class="filter-options">
                        <?php foreach ($info['data'] as $val): ?>
                        <label class="filter-label">
                            <input type="checkbox" name="<?php echo $key; ?>[]" value="<?php echo clean($val); ?>" class="filter-checkbox" <?php echo in_array($val, $filters[$key])?'checked':''; ?>>
                            <?php echo clean($val); ?>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>

                <div style="position: sticky; bottom: -1.5rem; background: #fff; padding-top: 1rem; border-top: 1px solid #f3f4f6;">
                    <button type="submit" class="btn btn-primary" style="width: 100%;">تطبيق الفلترة</button>
                    <a href="products.php" class="btn btn-outline" style="width: 100%; margin-top: 0.5rem; text-align: center; display: block;">مسح الكل</a>
                </div>
            </form>
        </aside>

        <!-- Product Grid -->
        <main>
            <?php if (!empty($products)): ?>
                <div class="products-grid">
                    <?php foreach ($products as $product): ?>
                        <div class="product-card">
                            <?php if ($product['badge'] && $product['badge'] !== 'none'): ?>
                                <span class="product-badge <?php echo $product['badge']; ?>">
                                    <?php 
                                    $badges = ['new' => 'جديد', 'bestseller' => 'الأكثر مبيعاً', 'sale' => 'تخفيضات', 'featured' => 'مميز'];
                                    echo $badges[$product['badge']] ?? '';
                                    ?>
                                </span>
                            <?php endif; ?>
                            
                            <a href="product.php?id=<?php echo $product['id']; ?>">
                                <?php $image = !empty($product['images']) ? $product['images'][0]['image_url'] : 'assets/images/placeholder.jpg'; ?>
                                <img src="<?php echo clean($image); ?>" alt="<?php echo clean($product['name_ar']); ?>" class="product-image" onerror="this.src='assets/images/placeholder.jpg'">
                            </a>
                            
                            <div class="product-body">
                                <div class="product-brand"><?php echo clean($product['brand'] ?? ''); ?></div>
                                <h3 class="product-name">
                                    <a href="product.php?id=<?php echo $product['id']; ?>" style="color: inherit;"><?php echo clean($product['name_ar']); ?></a>
                                </h3>
                                <p class="product-model"><?php echo clean($product['model'] ?? ''); ?></p>
                                
                                <div class="product-price">
                                    <span class="current-price"><?php echo formatPrice($product['price']); ?> <?php echo $currency; ?></span>
                                    <?php if ($product['original_price'] && $product['original_price'] > $product['price']): ?>
                                        <span class="original-price"><?php echo formatPrice($product['original_price']); ?> <?php echo $currency; ?></span>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Specs Badges -->
                                <?php if ($product['processor'] || $product['ram'] || $product['storage']): ?>
                                    <div class="product-specs">
                                        <?php if ($product['processor']): ?><span class="spec-badge"><?php echo clean($product['processor']); ?></span><?php endif; ?>
                                        <?php if ($product['ram']): ?><span class="spec-badge"><?php echo clean($product['ram']); ?></span><?php endif; ?>
                                        <?php if ($product['storage']): ?><span class="spec-badge"><?php echo clean($product['storage']); ?></span><?php endif; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="stock-status">
                                    <span class="stock-dot <?php echo $product['stock_count'] > 0 ? 'in-stock' : 'out-of-stock'; ?>"></span>
                                    <span><?php echo $product['stock_count'] > 0 ? 'متوفر' : 'غير متوفر'; ?></span>
                                </div>
                                
                                <div class="product-actions">
                                    <a href="product.php?id=<?php echo $product['id']; ?>" class="btn btn-secondary btn-sm">عرض</a>
                                    <?php if ($product['stock_count'] > 0): ?>
                                        <button class="btn btn-primary btn-sm" onclick="addToCart('<?php echo $product['id']; ?>', '<?php echo addslashes($product['name_ar']); ?>', <?php echo $product['price']; ?>, '<?php echo clean($image); ?>')">أضف للسلة</button>
                                    <?php else: ?>
                                        <button class="btn btn-primary btn-sm" disabled>نفذت</button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 4rem 1rem; color: var(--gray-500); background: #f9fafb; border-radius: var(--radius); border: 2px dashed #e5e7eb;">
                    <svg width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin: 0 auto 1rem; color: var(--gray-400);">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        <line x1="8" y1="11" x2="14" y2="11"></line>
                    </svg>
                    <h3 style="font-size: 1.25rem; margin-bottom: 0.5rem; color: var(--gray-800);">لا توجد نتائج مطابقة</h3>
                    <p style="margin-bottom: 1.5rem;">حاول تغيير خيارات التصفية أو البحث عن منتج آخر</p>
                    <a href="products.php" class="btn btn-primary">عرض جميع المنتجات</a>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<script>
function toggleFilterSidebar() {
    const sidebar = document.getElementById('filterSidebar');
    sidebar.classList.toggle('active');
    
    // Lock body scroll on mobile when menu is open
    if (window.innerWidth <= 992) {
        if (sidebar.classList.contains('active')) {
            document.body.style.overflow = 'hidden';
        } else {
            document.body.style.overflow = '';
        }
    }
}
</script>

<?php include 'includes/footer.php'; ?>

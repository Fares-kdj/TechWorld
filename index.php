<?php
/**
 * الصفحة الرئيسية للمتجر
 * Home Page
 */

require_once 'includes/functions.php';

$pageTitle = 'الرئيسية';
$featuredProducts = getFeaturedProducts(8);
$saleProducts = getOnSaleProducts(8);
$categories = getAllCategories();

include 'includes/header.php';
?>

<!-- Hero Section -->
<section class="hero">
    <div class="container">
        <div class="hero-content">
            <h1>اكتشف عالم التقنية معنا</h1>
            <p>أفضل الحواسيب المحمولة بأحدث التقنيات وأسعار تنافسية - جودة عالية وضمان موثوق</p>
            <div class="hero-actions">
                <a href="products.php" class="btn btn-primary btn-lg">
                    تصفح المنتجات
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                        <polyline points="12 5 19 12 12 19"></polyline>
                    </svg>
                </a>
                <a href="#featured" class="btn btn-outline btn-lg">
                    أحدث الأجهزة
                </a>
            </div>
        </div>
    </div>
</section>

<!-- الفئات -->
<section class="section" style="background: var(--gray-50);">
    <div class="container">
        <div class="section-header">
            <h2>تصفح حسب الفئة</h2>
            <p>اختر الفئة المناسبة لاحتياجاتك</p>
        </div>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem;">
            <?php foreach ($categories as $category): ?>
                <a href="products.php?category=<?php echo $category['slug']; ?>" 
                   style="background: white; padding: 2rem; border-radius: var(--radius-lg); text-align: center; box-shadow: var(--shadow); transition: var(--transition); display: block;"
                   onmouseover="this.style.transform='translateY(-8px)'; this.style.boxShadow='var(--shadow-xl)'"
                   onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='var(--shadow)'">
                    <div style="font-size: 3rem; margin-bottom: 1rem;"><?php echo $category['icon']; ?></div>
                    <h3 style="font-size: 1.25rem; font-weight: 600; color: var(--gray-900); margin-bottom: 0.5rem;">
                        <?php echo clean($category['name_ar']); ?>
                    </h3>
                    <p style="color: var(--gray-600); font-size: 0.875rem;">
                        <?php echo clean($category['description'] ?? ''); ?>
                    </p>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- المنتجات المميزة -->
<section class="section" id="featured">
    <div class="container">
        <div class="section-header">
            <div class="section-icon">
                <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                </svg>
            </div>
            <h2>المنتجات المميزة</h2>
            <p>اكتشف أحدث وأفضل ما لدينا من الحواسيب المحمولة</p>
        </div>
        
        <?php if (!empty($featuredProducts)): ?>
            <div class="products-grid">
                <?php foreach ($featuredProducts as $product): ?>
                    <div class="product-card">
                        <?php if ($product['badge'] && $product['badge'] !== 'none'): ?>
                            <span class="product-badge <?php echo $product['badge']; ?>">
                                <?php 
                                $badgeText = [
                                    'new' => 'جديد',
                                    'bestseller' => 'الأكثر مبيعاً',
                                    'sale' => 'تخفيضات',
                                    'featured' => 'مميز'
                                ];
                                echo $badgeText[$product['badge']] ?? '';
                                ?>
                            </span>
                        <?php endif; ?>
                        
                        <a href="product.php?id=<?php echo $product['id']; ?>">
                            <?php 
                            $image = !empty($product['images']) ? $product['images'][0]['image_url'] : 'assets/images/placeholder.jpg';
                            ?>
                            <img src="<?php echo clean($image); ?>" 
                                 alt="<?php echo clean($product['name_ar']); ?>" 
                                 class="product-image"
                                 onerror="this.src='assets/images/placeholder.jpg'">
                        </a>
                        
                        <div class="product-body">
                            <div class="product-brand"><?php echo clean($product['brand'] ?? 'علامة تجارية'); ?></div>
                            <h3 class="product-name">
                                <a href="product.php?id=<?php echo $product['id']; ?>" style="color: inherit;">
                                    <?php echo clean($product['name_ar']); ?>
                                </a>
                            </h3>
                            <p class="product-model"><?php echo clean($product['model'] ?? ''); ?></p>
                            
                            <div class="product-price">
                                <span class="current-price"><?php echo formatPrice($product['price']); ?> د.ج</span>
                                <?php if ($product['original_price'] && $product['original_price'] > $product['price']): ?>
                                    <span class="original-price"><?php echo formatPrice($product['original_price']); ?> د.ج</span>
                                <?php endif; ?>
                            </div>
                            
                            <?php if ($product['processor'] || $product['ram'] || $product['storage']): ?>
                                <div class="product-specs">
                                    <?php if ($product['processor']): ?>
                                        <span class="spec-badge"><?php echo clean($product['processor']); ?></span>
                                    <?php endif; ?>
                                    <?php if ($product['ram']): ?>
                                        <span class="spec-badge"><?php echo clean($product['ram']); ?></span>
                                    <?php endif; ?>
                                    <?php if ($product['storage']): ?>
                                        <span class="spec-badge"><?php echo clean($product['storage']); ?></span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="stock-status">
                                <span class="stock-dot <?php echo $product['stock_count'] > 0 ? 'in-stock' : 'out-of-stock'; ?>"></span>
                                <span><?php echo $product['stock_count'] > 0 ? 'متوفر في المخزون' : 'غير متوفر'; ?></span>
                            </div>
                            
                            <div class="product-actions">
                                <a href="product.php?id=<?php echo $product['id']; ?>" class="btn btn-secondary btn-sm">
                                    عرض التفاصيل
                                </a>
                                <?php if ($product['stock_count'] > 0): ?>
                                    <button class="btn btn-primary btn-sm" 
                                            onclick="addToCart('<?php echo $product['id']; ?>', 
                                                             '<?php echo addslashes($product['name_ar']); ?>', 
                                                             <?php echo $product['price']; ?>, 
                                                             '<?php echo clean($image); ?>', 
                                                             '<?php echo addslashes($product['model'] ?? ''); ?>')">
                                        أضف للسلة
                                    </button>
                                <?php else: ?>
                                    <button class="btn btn-primary btn-sm" disabled>
                                        غير متوفر
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div style="text-align: center; margin-top: 3rem;">
                <a href="products.php" class="btn btn-primary btn-lg">
                    عرض جميع المنتجات
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                        <polyline points="12 5 19 12 12 19"></polyline>
                    </svg>
                </a>
            </div>
        <?php else: ?>
            <div style="text-align: center; padding: 3rem; color: var(--gray-500);">
                <p>لا توجد منتجات متاحة حالياً</p>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- عروض وتخفيضات -->
<?php if (!empty($saleProducts)): ?>
<section class="section" id="sales" style="background: linear-gradient(to bottom, #fff, #f8f9fa);">
    <div class="container">
        <div class="section-header">
            <div class="section-icon" style="color: #dc3545; background: rgba(220, 53, 69, 0.1);">
                <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path>
                    <line x1="7" y1="7" x2="7.01" y2="7"></line>
                </svg>
            </div>
            <h2 style="color: #dc3545;">عروض وتخفيضات</h2>
            <p>اغتنم الفرصة مع أفضل العروض والخصومات الحصرية</p>
        </div>
        
        <div class="products-grid">
            <?php foreach ($saleProducts as $product): ?>
                <div class="product-card">
                    <span class="product-badge sale">تخفيضات</span>
                    
                    <a href="product.php?id=<?php echo $product['id']; ?>">
                        <?php 
                        $image = !empty($product['images']) ? $product['images'][0]['image_url'] : 'assets/images/placeholder.jpg';
                        ?>
                        <img src="<?php echo clean($image); ?>" 
                             alt="<?php echo clean($product['name_ar']); ?>" 
                             class="product-image"
                             onerror="this.src='assets/images/placeholder.jpg'">
                    </a>
                    
                    <div class="product-body">
                        <div class="product-brand"><?php echo clean($product['brand'] ?? 'علامة تجارية'); ?></div>
                        <h3 class="product-name">
                            <a href="product.php?id=<?php echo $product['id']; ?>" style="color: inherit;">
                                <?php echo clean($product['name_ar']); ?>
                            </a>
                        </h3>
                        <p class="product-model"><?php echo clean($product['model'] ?? ''); ?></p>
                        
                        <div class="product-price">
                            <span class="current-price" style="color: #dc3545;"><?php echo formatPrice($product['price']); ?> د.ج</span>
                            <?php if ($product['original_price'] && $product['original_price'] > $product['price']): ?>
                                <span class="original-price"><?php echo formatPrice($product['original_price']); ?> د.ج</span>
                                <?php 
                                $discount = round((($product['original_price'] - $product['price']) / $product['original_price']) * 100);
                                ?>
                                <span style="font-size: 0.6rem; background: #dc3545; color: white; padding: 0.1rem 0.3rem; border-radius: 4px; margin-right: 0.5rem; vertical-align: middle;"><?php echo $discount; ?>% خصم</span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="stock-status">
                            <span class="stock-dot <?php echo $product['stock_count'] > 0 ? 'in-stock' : 'out-of-stock'; ?>"></span>
                            <span><?php echo $product['stock_count'] > 0 ? 'متوفر' : 'غير متوفر'; ?></span>
                        </div>
                        
                        <div class="product-actions">
                            <a href="product.php?id=<?php echo $product['id']; ?>" class="btn btn-secondary btn-sm">
                                عرض التفاصيل
                            </a>
                            <?php if ($product['stock_count'] > 0): ?>
                                <button class="btn btn-primary btn-sm" 
                                        onclick="addToCart('<?php echo $product['id']; ?>', 
                                                         '<?php echo addslashes($product['name_ar']); ?>', 
                                                         <?php echo $product['price']; ?>, 
                                                         '<?php echo clean($image); ?>', 
                                                         '<?php echo addslashes($product['model'] ?? ''); ?>')">
                                    أضف للسلة
                                </button>
                            <?php else: ?>
                                <button class="btn btn-primary btn-sm" disabled>
                                    غير متوفر
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div style="text-align: center; margin-top: 3rem;">
            <a href="products.php?badge=sale" class="btn btn-outline btn-lg">
                عرض كل العروض
            </a>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- المميزات -->
<section class="section" style="background: var(--gray-50);">
    <div class="container">
        <div class="section-header">
            <h2>لماذا تختارنا؟</h2>
            <p>نقدم لك أفضل تجربة تسوق إلكترونية</p>
        </div>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 2rem;">
            <div style="text-align: center; padding: 2rem;">
                <div style="width: 80px; height: 80px; background: linear-gradient(135deg, var(--primary), var(--secondary)); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem; color: white;">
                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                </div>
                <h3 style="font-size: 1.25rem; font-weight: 600; margin-bottom: 0.75rem;">خدمة عملاء متميزة</h3>
                <p style="color: var(--gray-600);">فريق دعم محترف متاح لمساعدتك في أي وقت</p>
            </div>
            
            <div style="text-align: center; padding: 2rem;">
                <div style="width: 80px; height: 80px; background: linear-gradient(135deg, var(--success), #34d399); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem; color: white;">
                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                </div>
                <h3 style="font-size: 1.25rem; font-weight: 600; margin-bottom: 0.75rem;">ضمان الجودة</h3>
                <p style="color: var(--gray-600);">جميع منتجاتنا أصلية ومضمونة 100%</p>
            </div>
            
            <div style="text-align: center; padding: 2rem;">
                <div style="width: 80px; height: 80px; background: linear-gradient(135deg, var(--warning), #fbbf24); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem; color: white;">
                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                        <line x1="1" y1="10" x2="23" y2="10"></line>
                    </svg>
                </div>
                <h3 style="font-size: 1.25rem; font-weight: 600; margin-bottom: 0.75rem;">دفع آمن</h3>
                <p style="color: var(--gray-600);">طرق دفع متعددة وآمنة لراحتك</p>
            </div>
            
            <div style="text-align: center; padding: 2rem;">
                <div style="width: 80px; height: 80px; background: linear-gradient(135deg, var(--info), #22d3ee); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem; color: white;">
                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="1" y="3" width="15" height="13"></rect>
                        <polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon>
                        <circle cx="5.5" cy="18.5" r="2.5"></circle>
                        <circle cx="18.5" cy="18.5" r="2.5"></circle>
                    </svg>
                </div>
                <h3 style="font-size: 1.25rem; font-weight: 600; margin-bottom: 0.75rem;">شحن سريع</h3>
                <p style="color: var(--gray-600);">توصيل سريع لجميع مناطق المملكة</p>
            </div>
        </div>
    </div>
</section>

<!-- قسم الاتصال -->
<section class="section" id="contact">
    <div class="container">
        <div class="section-header">
            <h2>تواصل معنا</h2>
            <p>نحن هنا للإجابة على جميع استفساراتك</p>
        </div>
        
        <div style="max-width: 800px; margin: 0 auto; background: white; padding: 3rem; border-radius: var(--radius-lg); box-shadow: var(--shadow-lg);">
            <form action="contact.php" method="POST" style="display: grid; gap: 1.5rem;">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">الاسم الكامل</label>
                        <input type="text" name="name" required 
                               style="width: 100%; padding: 0.75rem; border: 2px solid var(--gray-200); border-radius: var(--radius); font-size: 1rem;">
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">رقم الجوال</label>
                        <input type="tel" name="phone" required 
                               style="width: 100%; padding: 0.75rem; border: 2px solid var(--gray-200); border-radius: var(--radius); font-size: 1rem;">
                    </div>
                </div>
                
                <div>
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">البريد الإلكتروني</label>
                    <input type="email" name="email" 
                           style="width: 100%; padding: 0.75rem; border: 2px solid var(--gray-200); border-radius: var(--radius); font-size: 1rem;">
                </div>
                
                <div>
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">الرسالة</label>
                    <textarea name="message" rows="5" required
                              style="width: 100%; padding: 0.75rem; border: 2px solid var(--gray-200); border-radius: var(--radius); font-size: 1rem; resize: vertical;"></textarea>
                </div>
                
                <button type="submit" class="btn btn-primary btn-lg">
                    إرسال الرسالة
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="22" y1="2" x2="11" y2="13"></line>
                        <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                    </svg>
                </button>
            </form>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>

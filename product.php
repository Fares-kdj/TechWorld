<?php
require_once 'includes/functions.php';

$productId = isset($_GET['id']) ? (int)$_GET['id'] : null;
$product = $productId ? getProduct($productId) : null;

if (!$product) {
    header('Location: products.php');
    exit;
}

// Prepare data
$name = $product['name_ar'];
$categoryName = $product['category_name'];
$categoryId = $product['category_id'];
$price = $product['price'];
$originalPrice = $product['original_price'];
$inStock = $product['stock_count'] > 0;
$stockCount = $product['stock_count'];
$images = !empty($product['images']) ? array_column($product['images'], 'image_url') : ['assets/images/placeholder.png'];
$description = $product['description_full'] ?: $product['description_short'];
$badge = $product['badge'];
$brand = $product['brand'];
$model = $product['model'];
$warranty = $product['warranty'];
$features = !empty($product['features']) ? array_column($product['features'], 'feature_ar') : [];

$specs = [
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

$pageTitle = $name;
include 'includes/header.php';

// Related Products
$relatedProductsRaw = isset($product['category_id']) ? 
    fetchAll("SELECT * FROM products WHERE category_id = ? AND id != ? LIMIT 4", [$product['category_id'], $product['id']]) : 
    [];

$relatedProducts = [];
foreach ($relatedProductsRaw as $rp) {
    $rpImages = getProductImages($rp['id']);
    $rp['image'] = !empty($rpImages) ? $rpImages[0]['image_url'] : 'assets/images/placeholder.png';
    $relatedProducts[] = $rp;
}
?>

<style>
    :root {
        --primary: #4f46e5;
        --secondary: #10b981;
        --dark: #1f2937;
        --light: #f3f4f6;
        --white: #ffffff;
        --gray: #6b7280;
        --border: #e5e7eb;
        --radius: 0.75rem;
    }

    body {
        background-color: #f9fafb;
    }

    .product-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 1rem;
    }

    /* Breadcrumbs */
    .breadcrumbs {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.875rem;
        color: var(--gray);
        margin-bottom: 1.5rem;
        flex-wrap: wrap;
    }
    .breadcrumbs a { color: var(--gray); text-decoration: none; }
    .breadcrumbs a:hover { color: var(--primary); }
    .breadcrumbs span.current { font-weight: 600; color: var(--dark); }

    /* Mobile First Layout - Stack */
    .product-wrapper {
        display: flex;
        flex-direction: column;
        gap: 2rem;
    }

    /* Mobile Gallery - Scroll Snap */
    .gallery-mobile {
        position: relative;
        width: 100%;
        background: white;
        border-radius: var(--radius);
        overflow: hidden;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    
    .gallery-slider {
        display: flex;
        overflow-x: auto;
        scroll-snap-type: x mandatory;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: none; /* Firefox */
        gap: 1rem;
        padding-bottom: 1rem;
    }
    .gallery-slider::-webkit-scrollbar { display: none; }
    
    .gallery-item {
        min-width: 100%;
        scroll-snap-align: center;
        display: flex;
        justify-content: center;
        align-items: center;
        height: 350px; /* Mobile height */
        padding: 1rem;
    }
    
    .gallery-img {
        max-width: 100%;
        max-height: 100%;
        object-fit: contain;
    }

    /* Pagination Dots */
    .gallery-dots {
        display: flex;
        justify-content: center;
        gap: 0.5rem;
        position: absolute;
        bottom: 1rem;
        left: 0;
        right: 0;
    }
    .dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: #d1d5db;
        transition: background 0.3s;
    }
    .dot.active { background: var(--primary); }

    /* Product Info */
    .product-info {
        background: var(--white);
        border-radius: var(--radius);
        padding: 1.5rem;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    
    .badges-wrapper {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        margin-bottom: 0.25rem;
    }

    .brand-badge {
        display: inline-block;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        color: var(--primary);
        background: #e0e7ff;
        padding: 0.25rem 0.5rem;
        border-radius: 0.25rem;
        margin-bottom: 0.5rem;
    }

    .badge-modern {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        padding: 0.25rem 0.75rem;
        border-radius: 2rem;
        font-size: 0.75rem;
        font-weight: 600;
        background: var(--light);
        color: var(--gray);
    }

    .product-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--dark);
        line-height: 1.3;
        margin-bottom: 1rem;
    }

    .product-price {
        display: flex;
        align-items: baseline;
        gap: 0.75rem;
        margin-bottom: 1.5rem;
    }

    .current-price {
        font-size: 1.75rem;
        font-weight: 700;
        color: var(--primary);
    }

    .old-price {
        font-size: 1rem;
        color: var(--gray);
        text-decoration: line-through;
    }

    /* Stock */
    .stock-ui {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.9rem;
        margin-bottom: 1.5rem;
    }
    .stock-dot-ui {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background: var(--secondary);
    }
    .stock-ui.out .stock-dot-ui { background: #ef4444; }

    /* Action Buttons */
    .actions-grid {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .buttons-row {
        display: flex;
        gap: 1rem;
    }

    .btn-cart, .btn-buy {
        flex: 1;
        padding: 1rem;
        border: none;
        border-radius: var(--radius);
        font-weight: 600;
        font-size: 1.1rem;
        cursor: pointer;
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 0.5rem;
    }

    .btn-cart {
        background: var(--primary);
        color: white;
    }

    .btn-buy {
        background: var(--secondary);
        color: white;
    }

    .btn-cart:disabled, .btn-buy:disabled {
        background: var(--gray);
        cursor: not-allowed;
    }

    /* Description & Features */
    .section-box {
        background: var(--white);
        padding: 1.5rem;
        border-radius: var(--radius);
        margin-top: 1.5rem;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }

    .section-title {
        font-size: 1.25rem;
        font-weight: 700;
        margin-bottom: 1rem;
        color: var(--dark);
        border-bottom: 2px solid var(--light);
        padding-bottom: 0.5rem;
    }

    .features-list-ui {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    .features-list-ui li {
        padding: 0.75rem 0;
        border-bottom: 1px solid var(--light);
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }
    .features-list-ui li:last-child { border-bottom: none; }
    .features-list-ui svg { color: var(--secondary); flex-shrink: 0; }

    /* Modern Specs Grid */
    .specs-grid-modern {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
        gap: 1rem;
    }

    .spec-item-modern {
        background: var(--light);
        padding: 1rem;
        border-radius: var(--radius);
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
        gap: 0.5rem;
        transition: transform 0.2s;
    }

    .spec-item-modern:hover {
        transform: translateY(-2px);
    }

    .spec-icon {
        color: var(--primary);
        margin-bottom: 0.25rem;
    }

    .spec-label-modern {
        font-size: 0.8rem;
        color: var(--gray);
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .spec-value-modern {
        font-weight: 700;
        color: var(--dark);
        font-size: 0.95rem;
        line-height: 1.2;
    }

    /* Related Products Scroller for Mobile */
    .related-scroller {
        display: flex;
        overflow-x: auto;
        gap: 1rem;
        padding-bottom: 1rem;
        margin-top: 1rem;
        -webkit-overflow-scrolling: touch;
    }
    .related-card-mobile {
        min-width: 250px;
        background: white;
        border-radius: var(--radius);
        padding: 1rem;
        display: flex;
        flex-direction: column;
        border: 1px solid var(--border);
    }
    .related-card-mobile img {
        width: 100%;
        height: 150px;
        object-fit: contain;
        margin-bottom: 1rem;
    }

    .desktop-thumbs { display: none; }

    /* Desktop Enhancements */
    @media (min-width: 992px) {
        .product-wrapper {
            flex-direction: row;
            align-items: start;
        }

        .gallery-mobile {
            flex: 1.2;
            height: auto;
        }
        
        .gallery-slider {
            display: flex; /* Changed from grid to flex */
            flex-direction: row;
            overflow: hidden; /* Keep hidden, we scroll programmatically */
            padding: 2rem;
            height: 500px;
            scroll-behavior: smooth; /* Smooth scrolling */
        }
        
        /* Ensure items take full width and don't shrink */
        .gallery-item {
            min-width: 100%;
            flex: 0 0 100%;
        }
        
        /* Implement desktop thumbnails logic */
        .desktop-thumbs {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
            justify-content: center;
        }
        .thumb-btn {
            width: 80px;
            height: 80px;
            border: 2px solid var(--border);
            border-radius: 0.5rem;
            padding: 0.25rem;
            cursor: pointer;
        }
        .thumb-btn.active { border-color: var(--primary); }
        .thumb-btn img { width: 100%; height: 100%; object-fit: contain; }

        .product-info {
            flex: 1;
            position: sticky;
            top: 2rem;
        }

        .product-title { font-size: 2rem; }
        
        .related-scroller {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
        }
        .related-card-mobile { min-width: 0; }
        
        .specs-grid-modern {
            grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
        }
    }
</style>

<div class="product-container">
    <!-- Breadcrumbs -->
    <div class="breadcrumbs">
        <a href="index.php">الرئيسية</a>
        <span>/</span>
        <a href="products.php">المنتجات</a>
        <span>/</span>
        <span class="current"><?php echo htmlspecialchars($name); ?></span>
    </div>

    <div class="product-wrapper">
        <!-- Gallery -->
        <div style="flex: 1.3;">
            <div class="gallery-mobile">
                <div class="gallery-slider" id="gallerySlider">
                    <?php foreach ($images as $img): ?>
                        <div class="gallery-item">
                            <img src="<?php echo $img; ?>" class="gallery-img" alt="<?php echo htmlspecialchars($name); ?>">
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <?php if (count($images) > 1): ?>
                    <div class="gallery-dots">
                        <?php foreach ($images as $index => $img): ?>
                            <div class="dot <?php echo $index === 0 ? 'active' : ''; ?>"></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Desktop Thumbs (Hidden on Mobile) -->
            <div class="desktop-thumbs">
                <?php foreach ($images as $index => $img): ?>
                    <div class="thumb-btn <?php echo $index === 0 ? 'active' : ''; ?>" 
                         onclick="desktopChangeImage(this, '<?php echo $img; ?>', <?php echo $index; ?>)">
                        <img src="<?php echo $img; ?>">
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Info -->
        <div class="product-info">
            <div class="badges-wrapper">
                <span class="brand-badge"><?php echo htmlspecialchars($brand); ?></span>
                <?php if ($badge && $badge !== 'none'): ?>
                    <span class="badge-modern" style="background: #e0e7ff; color: var(--primary);">
                        <?php 
                        switch ($badge) {
                            case 'new': echo '✨ جديد'; break;
                            case 'sale': echo '🔥 تخفيض'; break;
                            case 'hot': echo '⚡ رائج'; break;
                            case 'bestseller': echo '⭐ الأكثر مبيعاً'; break;
                            default: echo $badge;
                        }
                        ?>
                    </span>
                <?php endif; ?>
                <?php if ($warranty): ?>
                    <span class="badge-modern">
                        🛡️ ضمان <?php echo htmlspecialchars($warranty); ?>
                    </span>
                <?php endif; ?>
            </div>
            
            <h1 class="product-title"><?php echo htmlspecialchars($name); ?></h1>
            
            <div class="product-price">
                <span class="current-price"><?php echo formatPrice($price); ?> د.ج</span>
                <?php if ($originalPrice): ?>
                    <span class="original-price"><?php echo formatPrice($originalPrice); ?> د.ج</span>
                <?php endif; ?>
            </div>

            <div class="stock-ui <?php echo !$inStock ? 'out' : ''; ?>">
                <div class="stock-dot-ui"></div>
                <span><?php echo $inStock ? 'متوفر (' . $stockCount . ')' : 'غير متوفر'; ?></span>
            </div>

            <div class="actions-grid">
                <div class="buttons-row">
                    <button class="btn-cart" 
                            <?php echo !$inStock ? 'disabled' : ''; ?>
                            onclick="addToCart('<?php echo $productId; ?>', '<?php echo addslashes($name); ?>', <?php echo $price; ?>, '<?php echo $images[0]; ?>', '<?php echo addslashes($model); ?>')">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
                        </svg>
                        <?php echo $inStock ? 'أضف للسلة' : 'نفذت الكمية'; ?>
                    </button>
                    
                    <button class="btn-buy" 
                            <?php echo !$inStock ? 'disabled' : ''; ?>
                            onclick="buyNow('<?php echo $productId; ?>', '<?php echo addslashes($name); ?>', <?php echo $price; ?>, '<?php echo $images[0]; ?>', '<?php echo addslashes($model); ?>')">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="12" y1="1" x2="12" y2="23"></line>
                            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                        </svg>
                        اشترِ الآن
                    </button>
                </div>
            </div>
            
            <div style="margin-top: 1.5rem; color: var(--gray); font-size: 0.9rem;">
                <?php echo nl2br(htmlspecialchars($description)); ?>
            </div>
        </div>
    </div>

    <!-- Specs Section -->
    <div class="section-box">
        <h2 class="section-title">المواصفات</h2>
        <div class="specs-grid-modern">
            <?php
            $specIcons = [
                'processor' => '<path d="M18 10h-1.26A8 8 0 1 0 9 20h9a2 2 0 0 0 2-2v-8a2 2 0 0 0-2-2z"></path>', // Cpu-like
                'ram' => '<rect x="2" y="2" width="20" height="8" rx="2" ry="2"></rect><rect x="2" y="14" width="20" height="8" rx="2" ry="2"></rect><line x1="6" y1="6" x2="6.01" y2="6"></line><line x1="6" y1="18" x2="6.01" y2="18"></line>', // Server/Ram
                'storage' => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line>', // Drive/Download
                'screenSize' => '<rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect><line x1="8" y1="21" x2="16" y2="21"></line><line x1="12" y1="17" x2="12" y2="21"></line>', // Monitor
                'battery' => '<rect x="1" y="6" width="18" height="12" rx="2" ry="2"></rect><line x1="23" y1="13" x2="23" y2="11"></line>', // Battery
                'weight' => '<circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline>', // Clock/Weight
                'os' => '<rect x="4" y="4" width="16" height="16" rx="2" ry="2"></rect><rect x="9" y="9" width="6" height="6"></rect>', // Terminal/OS
            ];

            $specMap = [
                'processor' => ['label' => 'المعالج', 'unit' => ''],
                'ram' => ['label' => 'الرام', 'unit' => ''],
                'storage' => ['label' => 'التخزين', 'unit' => ''],
                'screenSize' => ['label' => 'الشاشة', 'unit' => '"'],
                'battery' => ['label' => 'البطارية', 'unit' => 'Wh'],
                'weight' => ['label' => 'الوزن', 'unit' => 'kg'],
                'os' => ['label' => 'نظام التشغيل', 'unit' => ''],
            ];

            foreach ($specMap as $key => $info):
                if (!empty($specs[$key])):
                    $val = htmlspecialchars($specs[$key]);
                    if (is_numeric($specs[$key]) && $info['unit']) $val .= ' ' . $info['unit'];
                    $icon = $specIcons[$key] ?? '<circle cx="12" cy="12" r="10"></circle>';
            ?>
                <div class="spec-item-modern">
                    <div class="spec-icon">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <?php echo $icon; ?>
                        </svg>
                    </div>
                    <span class="spec-label-modern"><?php echo $info['label']; ?></span>
                    <span class="spec-value-modern"><?php echo $val; ?></span>
                </div>
            <?php endif; endforeach; ?>
        </div>
    </div>

    <!-- Features Section -->
    <?php if (!empty($features)): ?>
    <div class="section-box">
        <h2 class="section-title">المميزات</h2>
        <ul class="features-list-ui">
            <?php foreach ($features as $f): ?>
                <li>
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                    <?php echo htmlspecialchars($f); ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <!-- Related Products -->
    <?php if ($relatedProducts): ?>
    <div style="margin-top: 3rem;">
        <h2 class="section-title">منتجات مشابهة</h2>
        <div class="related-scroller">
            <?php foreach ($relatedProducts as $rp): ?>
                <a href="product.php?id=<?php echo $rp['id']; ?>" class="related-card-mobile" style="text-decoration: none; color: inherit;">
                    <img src="<?php echo $rp['image']; ?>">
                    <div style="font-weight: 700; margin-bottom: 0.5rem;"><?php echo htmlspecialchars($rp['name_ar']); ?></div>
                    <div style="color: var(--primary); font-weight: 700;"><?php echo formatPrice($rp['price']); ?> ر.س</div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
    // Desktop Image Switcher
    function desktopChangeImage(btn, src, index) {
        // Scroll the slider programmatically
        const slider = document.getElementById('gallerySlider');
        const width = slider.offsetWidth;
        // Direction agnostic approach
        const items = document.querySelectorAll('.gallery-item');
        if(items[index]) {
            items[index].scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
        }

        document.querySelectorAll('.thumb-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
    }

    // Update dots on scroll
    const slider = document.getElementById('gallerySlider');
    const dots = document.querySelectorAll('.dot');
    
    slider.addEventListener('scroll', () => {
        const scrollPos = Math.abs(slider.scrollLeft);
        const width = slider.offsetWidth;
        const index = Math.round(scrollPos / width);
        
        dots.forEach(d => d.classList.remove('active'));
        if(dots[index]) dots[index].classList.add('active');
    });

    // Buy Now Function
    function buyNow(id, name, price, image, model) {
        let cart = JSON.parse(localStorage.getItem('cart')) || [];
        const existingItem = cart.find(item => item.id == id); // Use loose equality for string/int IDs

        if (existingItem) {
            existingItem.quantity++;
        } else {
            cart.push({
                id: id,
                name: name,
                price: parseFloat(price),
                image: image,
                model: model,
                quantity: 1
            });
        }

        localStorage.setItem('cart', JSON.stringify(cart));
        // Redirect directly to checkout
        window.location.href = 'checkout.php';
    }
</script>

<?php include 'includes/footer.php'; ?>

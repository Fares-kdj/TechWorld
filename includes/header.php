<?php
require_once __DIR__ . '/functions.php';

$settings = getSettings();
$categories = getAllCategories();
$pageTitle = $pageTitle ?? 'متجر التقنية';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title><?php echo clean($pageTitle); ?> - <?php echo clean($settings['site_name'] ?? 'متجر التقنية'); ?></title>
    <meta name="description" content="متجر إلكتروني متخصص في بيع الحواسيب المحمولة والإلكترونيات">
    <meta name="keywords" content="لابتوب, كمبيوتر, تقنية, أجهزة">
    
    <!-- الخطوط -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- الأنماط -->
    <link rel="stylesheet" href="assets/css/style.css">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="assets/images/favicon.png">
</head>
<body>
    <!-- الهيدر -->
    <header class="header">
        <div class="container">
            <nav class="navbar">
                <!-- الشعار -->
                <div class="navbar-brand">
                    <a href="index.php" class="logo">
                        <img src="assets/images/logo.jpg" alt="<?php echo clean($settings['site_name'] ?? 'متجر التقنية'); ?>" style="height: 40px;">
                    </a>
                </div>
                
                <!-- القائمة -->
                <div class="navbar-menu">
                    <ul class="navbar-nav" id="navbarNav">
                        <li><a href="index.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">الرئيسية</a></li>
                        <li><a href="products.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'products.php' && !isset($_GET['category'])) ? 'active' : ''; ?>">المنتجات</a></li>
                        <?php foreach (array_slice($categories, 0, 3) as $category): ?>
                            <li><a href="products.php?category=<?php echo $category['slug']; ?>" class="nav-link <?php echo (isset($_GET['category']) && $_GET['category'] == $category['slug']) ? 'active' : ''; ?>"><?php echo clean($category['name_ar']); ?></a></li>
                        <?php endforeach; ?>
                        <li><a href="contact.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'contact.php' ? 'active' : ''; ?>">اتصل بنا</a></li>
                    </ul>
                </div>
                
                <!-- زر القائمة للموبايل -->
                <button class="mobile-menu-btn" onclick="toggleMobileMenu()" aria-label="القائمة">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="3" y1="12" x2="21" y2="12"></line>
                        <line x1="3" y1="6" x2="21" y2="6"></line>
                        <line x1="3" y1="18" x2="21" y2="18"></line>
                    </svg>
                </button>
                
                <!-- الأزرار -->
                <div class="navbar-actions">
                    <!-- البحث -->
                    <button class="cart-btn" onclick="document.getElementById('search-modal').style.display='flex'">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"></circle>
                            <path d="m21 21-4.35-4.35"></path>
                        </svg>
                    </button>
                    
                    <!-- السلة -->
                    <button class="cart-btn" onclick="openCart()">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="9" cy="21" r="1"></circle>
                            <circle cx="20" cy="21" r="1"></circle>
                            <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                        </svg>
                        <span class="cart-count">0</span>
                    </button>
                </div>
            </nav>
        </div>
    </header>

    <!-- سلة التسوق العائمة -->
    <div class="cart-overlay" onclick="closeCart()"></div>
    <div class="cart-sidebar">
        <div class="cart-header">
            <h3>سلة التسوق</h3>
            <button class="cart-close" onclick="closeCart()">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>
        
        <div class="cart-body">
            <!-- يتم ملؤها بواسطة JavaScript -->
        </div>
        
        <div class="cart-footer">
            <div class="cart-total">
                <span class="cart-total-label">الإجمالي:</span>
                <span class="cart-total-value">0 ر.س</span>
            </div>
            <button class="btn btn-primary" onclick="proceedToCheckout()">
                إتمام الطلب
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                    <polyline points="12 5 19 12 12 19"></polyline>
                </svg>
            </button>
            <button class="btn btn-secondary mt-2" onclick="clearCart()" style="width: 100%;">
                تفريغ السلة
            </button>
        </div>
    </div>

    <!-- نافذة البحث -->
    <div id="search-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 3000; align-items: center; justify-content: center;">
        <div style="background: white; padding: 2rem; border-radius: 1rem; max-width: 600px; width: 90%;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h3 style="font-size: 1.5rem; font-weight: 700;">البحث في المنتجات</h3>
                <button onclick="document.getElementById('search-modal').style.display='none'" style="padding: 0.5rem; color: #6b7280;">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>
            <form action="products.php" method="GET">
                <div style="display: flex; gap: 0.5rem;">
                    <input type="text" name="search" placeholder="ابحث عن منتج..." 
                           style="flex: 1; padding: 0.75rem 1rem; border: 2px solid #e5e7eb; border-radius: 0.5rem; font-size: 1rem;"
                           autofocus>
                    <button type="submit" class="btn btn-primary">بحث</button>
                </div>
            </form>
        </div>
    </div>

    <!-- عرض رسائل Flash -->
    <?php 
    $flash = getFlashMessage();
    if ($flash): 
    ?>
        <div class="alert alert-<?php echo $flash['type']; ?>" style="margin: 1rem auto; max-width: 1200px;">
            <?php echo clean($flash['message']); ?>
        </div>
    <?php endif; ?>

    <!-- المحتوى الرئيسي -->
    <main>

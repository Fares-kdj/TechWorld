<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// تضمين الملفات المطلوبة
require_once __DIR__ . '/functions.php';

// التحقق من تسجيل الدخول
checkAdminLogin();

// الحصول على معلومات المدير
$adminInfo = getAdminInfo();

// الحصول على عدد الطلبات الجديدة
$result = fetchOne("SELECT COUNT(*) as count FROM orders WHERE status = 'pending'");
$newOrdersCount = $result['count'] ?? 0;
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? '31 Tech Store - لوحة التحكم'; ?></title>
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="../assets/images/favicon.png">
    
    <!-- Bootstrap RTL -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <style>
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --sidebar-width: 260px;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
        }
        
        /* Sidebar */
        .sidebar {
            position: fixed;
            top: 0;
            right: 0;
            height: 100vh;
            width: var(--sidebar-width);
            background: linear-gradient(180deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            z-index: 1000;
            overflow-y: auto;
            transition: all 0.3s;
        }
        
        .sidebar-header {
            padding: 25px 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .sidebar-header h4 {
            margin: 10px 0 5px;
            font-weight: 700;
        }
        
        .sidebar-menu {
            padding: 20px 0;
        }
        
        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 12px 25px;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: all 0.3s;
            border-right: 3px solid transparent;
        }
        
        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background: rgba(255,255,255,0.1);
            color: white;
            border-right-color: white;
        }
        
        .sidebar-menu a i {
            margin-left: 12px;
            font-size: 18px;
            width: 25px;
        }
        
        .sidebar-menu .badge {
            margin-right: auto;
        }
        
        /* Main Content */
        .main-content {
            margin-right: var(--sidebar-width);
            padding: 30px;
            min-height: 100vh;
        }
        
        /* Top Bar */
        .top-bar {
            background: white;
            padding: 20px 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .top-bar h1 {
            margin: 0;
            font-size: 24px;
            color: #333;
        }
        
        .admin-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .admin-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 18px;
        }
        
        /* Cards */
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .stat-card .icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            margin-bottom: 15px;
        }
        
        .stat-card.primary .icon {
            background: rgba(102, 126, 234, 0.1);
            color: var(--primary-color);
        }
        
        .stat-card.success .icon {
            background: rgba(40, 199, 111, 0.1);
            color: #28c76f;
        }
        
        .stat-card.warning .icon {
            background: rgba(255, 159, 67, 0.1);
            color: #ff9f43;
        }
        
        .stat-card.danger .icon {
            background: rgba(234, 84, 85, 0.1);
            color: #ea5455;
        }
        
        .stat-card h3 {
            font-size: 32px;
            font-weight: 700;
            margin: 10px 0;
        }
        
        .stat-card p {
            color: #6c757d;
            margin: 0;
        }
        
        /* Tables */
        .table-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .table-card .table {
            margin-bottom: 0;
        }
        
        .table thead th {
            border-bottom: 2px solid #dee2e6;
            font-weight: 600;
            color: #495057;
        }
        
        /* Buttons */
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
        }
        
        .btn-primary:hover {
            opacity: 0.9;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                width: 0;
                overflow: hidden;
            }
            
            .sidebar.show {
                width: var(--sidebar-width);
            }
            
            .main-content {
                margin-right: 0;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <img src="../assets/images/admin-logo.png" alt="31 Tech Store" style="max-width: 100%; height: auto; max-height: 80px; border-radius: 8px;">
        </div>
        
        <div class="sidebar-menu">
            <a href="index.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                <i class="bi bi-speedometer2"></i>
                <span>لوحة المعلومات</span>
            </a>
            
            <a href="products.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'products.php' ? 'active' : ''; ?>">
                <i class="bi bi-box-seam"></i>
                <span>المنتجات</span>
            </a>
            
            <a href="categories.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'categories.php' ? 'active' : ''; ?>">
                <i class="bi bi-tags"></i>
                <span>الفئات</span>
            </a>
            
            <a href="orders.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'orders.php' ? 'active' : ''; ?>">
                <i class="bi bi-cart-check"></i>
                <span>الطلبات</span>
                <?php if ($newOrdersCount > 0): ?>
                    <span class="badge bg-danger"><?php echo $newOrdersCount; ?></span>
                <?php endif; ?>
            </a>
            
            <a href="customers.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'customers.php' ? 'active' : ''; ?>">
                <i class="bi bi-people"></i>
                <span>العملاء</span>
            </a>
            
            <a href="settings.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>">
                <i class="bi bi-gear"></i>
                <span>الإعدادات</span>
            </a>
            
            <a href="admins.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'admins.php' ? 'active' : ''; ?>">
                <i class="bi bi-shield-lock"></i>
                <span>المديرين</span>
            </a>
            
            <a href="reports.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : ''; ?>">
                <i class="bi bi-graph-up"></i>
                <span>التقارير</span>
            </a>
            
            <hr style="border-color: rgba(255,255,255,0.1); margin: 20px 25px;">
            
            <a href="logout.php">
                <i class="bi bi-box-arrow-left"></i>
                <span>تسجيل الخروج</span>
            </a>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <div class="top-bar">
            <h1><?php echo $pageTitle ?? 'لوحة المعلومات'; ?></h1>
            
            <div class="admin-info">
                <div>
                    <div class="fw-bold"><?php echo $_SESSION['admin_name']; ?></div>
                    <small class="text-muted"><?php echo $_SESSION['admin_role']; ?></small>
                </div>
                <div class="admin-avatar">
                    <?php echo substr($_SESSION['admin_name'], 0, 2); ?>
                </div>
            </div>
        </div>
        
        <?php displayFlashMessage(); ?>

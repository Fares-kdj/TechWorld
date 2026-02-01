<?php
$pageTitle = 'لوحة المعلومات';
include 'includes/header.php';

global $pdo; // ✅ تأكد من أن $pdo معرف

// الحصول على الإحصائيات
$stats = getDashboardStats();
$stats['recent_orders'] = getRecentOrders();
$stats['top_products'] = getTopProducts();

// المبيعات حسب الفترة
$todaySales = $pdo->query("SELECT COALESCE(SUM(total), 0) as total FROM orders WHERE DATE(ordered_at) = CURDATE()")->fetchColumn();
$monthSales = $pdo->query("SELECT COALESCE(SUM(total), 0) as total FROM orders WHERE MONTH(ordered_at) = MONTH(CURDATE()) AND YEAR(ordered_at) = YEAR(CURDATE())")->fetchColumn();
$yearSales = $pdo->query("SELECT COALESCE(SUM(total), 0) as total FROM orders WHERE YEAR(ordered_at) = YEAR(CURDATE())")->fetchColumn();


$currency = getSetting('currency', 'دج');
?>


<div class="row g-4 mb-4">
    <!-- إحصائيات -->
    <div class="col-md-3">
        <div class="stat-card primary">
            <div class="icon">
                <i class="bi bi-box-seam"></i>
            </div>
            <h3><?php echo number_format($stats['products_count']); ?></h3>
            <p>إجمالي المنتجات</p>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="stat-card success">
            <div class="icon">
                <i class="bi bi-cart-check"></i>
            </div>
            <h3><?php echo number_format($stats['orders_count']); ?></h3>
            <p>إجمالي الطلبات</p>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="stat-card warning">
            <div class="icon">
                <i class="bi bi-clock-history"></i>
            </div>
            <h3><?php echo number_format($stats['pending_orders']); ?></h3>
            <p>الطلبات المعلقة</p>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="stat-card info">
            <div class="icon">
                <i class="bi bi-currency-dollar"></i>
            </div>
            <h3><?php echo number_format($stats['total_sales']); ?> <?php echo $currency; ?></h3>
            <p>إجمالي المبيعات</p>
        </div>
    </div>
</div>

<!-- مبيعات الفترات -->
<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title text-primary">
                    <i class="bi bi-calendar-day me-2"></i>مبيعات اليوم
                </h5>
                <h3 class="mb-0"><?php echo number_format($todaySales); ?> <?php echo $currency; ?></h3>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title text-success">
                    <i class="bi bi-calendar-month me-2"></i>مبيعات الشهر
                </h5>
                <h3 class="mb-0"><?php echo number_format($monthSales); ?> <?php echo $currency; ?></h3>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title text-info">
                    <i class="bi bi-calendar-year me-2"></i>مبيعات السنة
                </h5>
                <h3 class="mb-0"><?php echo number_format($yearSales); ?> <?php echo $currency; ?></h3>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- أحدث الطلبات -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-clock-history me-2"></i>أحدث الطلبات
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>رقم الطلب</th>
                                <th>العميل</th>
                                <th>المبلغ</th>
                                <th>الحالة</th>
                                <th>التاريخ</th>
                                <th>إجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($stats['recent_orders'])): ?>
                                <?php foreach ($stats['recent_orders'] as $order): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($order['order_number']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                        <td><?php echo number_format($order['total']); ?> <?php echo $currency; ?></td>
                                        <td>
                                            <?php
                                            $statusClass = [
                                                'pending' => 'warning',
                                                'confirmed' => 'info',
                                                'processing' => 'primary',
                                                'shipped' => 'success',
                                                'delivered' => 'success',
                                                'cancelled' => 'danger'
                                            ];
                                            $statusText = [
                                                'pending' => 'معلق',
                                                'confirmed' => 'مؤكد',
                                                'processing' => 'قيد المعالجة',
                                                'shipped' => 'تم الشحن',
                                                'delivered' => 'تم التوصيل',
                                                'cancelled' => 'ملغي'
                                            ];
                                            ?>
                                            <span class="badge bg-<?php echo $statusClass[$order['status']]; ?>">
                                                <?php echo $statusText[$order['status']]; ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('Y-m-d H:i', strtotime($order['ordered_at'])); ?></td>
                                        <td>
                                            <a href="orders.php?view=<?php echo $order['id']; ?>" class="btn btn-sm btn-primary">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center">لا توجد طلبات بعد</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="text-center mt-3">
                    <a href="orders.php" class="btn btn-primary">
                        <i class="bi bi-arrow-left me-2"></i>عرض جميع الطلبات
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- المنتجات الأكثر مبيعاً -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-star me-2"></i>الأكثر مبيعاً
                </h5>
            </div>
            <div class="card-body">
                <?php if (!empty($stats['top_products'])): ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($stats['top_products'] as $product): ?>
                            <div class="list-group-item px-0">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong><?php echo htmlspecialchars($product['name_ar']); ?></strong>
                                        <br>
                                        <small class="text-muted">
                                            <i class="bi bi-cart-check me-1"></i><?php echo $product['sales']; ?> مبيعات
                                            <i class="bi bi-box-seam me-1 ms-2"></i><?php echo $product['stock_count']; ?> متوفر
                                        </small>
                                    </div>
                                    <a href="products.php?edit=<?php echo $product['id']; ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-center text-muted">لا توجد مبيعات بعد</p>
                <?php endif; ?>
                
                <div class="text-center mt-3">
                    <a href="products.php" class="btn btn-primary">
                        <i class="bi bi-arrow-left me-2"></i>إدارة المنتجات
                    </a>
                </div>
            </div>
        </div>
        
        <!-- تنبيه المخزون المنخفض -->
        <?php if ($stats['low_stock'] > 0): ?>
            <div class="alert alert-warning mt-3">
                <i class="bi bi-exclamation-triangle me-2"></i>
                <strong><?php echo $stats['low_stock']; ?></strong> منتج بمخزون منخفض!
                <a href="products.php?filter=low_stock" class="alert-link">عرض المنتجات</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.stat-card {
    background: white;
    border-radius: 15px;
    padding: 25px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    position: relative;
    overflow: hidden;
    transition: transform 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 20px rgba(0,0,0,0.15);
}

.stat-card .icon {
    position: absolute;
    top: -10px;
    left: -10px;
    font-size: 100px;
    opacity: 0.1;
}

.stat-card h3 {
    font-size: 32px;
    font-weight: 700;
    margin: 10px 0;
}

.stat-card p {
    color: #6c757d;
    margin: 0;
    font-size: 14px;
}

.stat-card.primary .icon {
    color: #667eea;
}

.stat-card.success .icon {
    color: #28a745;
}

.stat-card.warning .icon {
    color: #ffc107;
}

.stat-card.info .icon {
    color: #17a2b8;
}

.card {
    border-radius: 15px;
    border: none;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.card-header {
    background: white;
    border-bottom: 2px solid #f8f9fa;
    padding: 20px;
    font-weight: 600;
}
</style>

<?php include 'includes/footer.php'; ?>

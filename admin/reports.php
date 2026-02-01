<?php
$pageTitle = 'التقارير والإحصائيات';
include 'includes/header.php';

global $pdo; // Ensure $pdo is global
$period = isset($_GET['period']) ? $_GET['period'] : 'month';

// حساب تواريخ البداية والنهاية
$endDate = date('Y-m-d');
switch ($period) {
    case 'week':
        $startDate = date('Y-m-d', strtotime('-7 days'));
        $periodText = 'آخر أسبوع';
        break;
    case 'month':
        $startDate = date('Y-m-d', strtotime('-30 days'));
        $periodText = 'آخر شهر';
        break;
    case 'year':
        $startDate = date('Y-m-d', strtotime('-365 days'));
        $periodText = 'آخر سنة';
        break;
    default:
        $startDate = date('Y-m-d', strtotime('-30 days'));
        $periodText = 'آخر شهر';
}

// إحصائيات الفترة
$stats = $pdo->query("
    SELECT 
        COUNT(*) as total_orders,
        COALESCE(SUM(total), 0) as total_revenue,
        COALESCE(AVG(total), 0) as avg_order_value,
        COUNT(DISTINCT customer_phone) as unique_customers
    FROM orders
    WHERE DATE(ordered_at) BETWEEN '$startDate' AND '$endDate'
")->fetch();

// مبيعات يومية
$dailySales = $pdo->query("
    SELECT 
        DATE(ordered_at) as date,
        COUNT(*) as orders,
        SUM(total) as revenue
    FROM orders
    WHERE DATE(ordered_at) BETWEEN '$startDate' AND '$endDate'
    GROUP BY DATE(ordered_at)
    ORDER BY date ASC
")->fetchAll();

// المنتجات الأكثر مبيعاً
$topProducts = $pdo->query("
    SELECT 
        p.name_ar,
        p.name_en,
        SUM(oi.quantity) as total_quantity,
SUM(oi.subtotal) as total_revenue,
        COUNT(DISTINCT oi.order_id) as orders_count
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    JOIN orders o ON oi.order_id = o.id
    WHERE DATE(o.ordered_at) BETWEEN '$startDate' AND '$endDate'
    GROUP BY oi.product_id
    ORDER BY total_revenue DESC
    LIMIT 10
")->fetchAll();

// الفئات الأكثر مبيعاً
$topCategories = $pdo->query("
    SELECT 
        c.name_ar,
        COUNT(DISTINCT oi.order_id) as orders_count,
        SUM(oi.quantity) as total_quantity,
        SUM(oi.subtotal) as total_revenue
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    JOIN categories c ON p.category_id = c.id
    JOIN orders o ON oi.order_id = o.id
    WHERE DATE(o.ordered_at) BETWEEN '$startDate' AND '$endDate'
    GROUP BY c.id
    ORDER BY total_revenue DESC
")->fetchAll();

// إحصائيات حالات الطلبات
$orderStatusStats = $pdo->query("
    SELECT 
        status,
        COUNT(*) as count,
        SUM(total) as revenue
    FROM orders
    WHERE DATE(ordered_at) BETWEEN '$startDate' AND '$endDate'
    GROUP BY status
")->fetchAll();

// أفضل العملاء
$topCustomers = $pdo->query("
    SELECT 
        customer_name,
        customer_phone,
        COUNT(*) as orders_count,
        SUM(total) as total_spent
    FROM orders
    WHERE DATE(ordered_at) BETWEEN '$startDate' AND '$endDate'
    GROUP BY customer_phone
    ORDER BY total_spent DESC
    LIMIT 10
")->fetchAll();

$currency = getSetting('currency', 'دج');
?>

<!-- فلتر الفترة -->
<div class="period-filter mb-4">
    <div class="btn-group" role="group">
        <a href="?period=week" class="btn btn-outline-primary <?php echo $period == 'week' ? 'active' : ''; ?>">
            <i class="bi bi-calendar-week me-2"></i>أسبوع
        </a>
        <a href="?period=month" class="btn btn-outline-primary <?php echo $period == 'month' ? 'active' : ''; ?>">
            <i class="bi bi-calendar-month me-2"></i>شهر
        </a>
        <a href="?period=year" class="btn btn-outline-primary <?php echo $period == 'year' ? 'active' : ''; ?>">
            <i class="bi bi-calendar-year me-2"></i>سنة
        </a>
    </div>
    <span class="period-text me-3">التقرير: <strong><?php echo $periodText; ?></strong></span>
</div>

<!-- إحصائيات سريعة -->
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="stat-card primary">
            <div class="icon">
                <i class="bi bi-cart-check"></i>
            </div>
            <h3><?php echo number_format($stats['total_orders']); ?></h3>
            <p>إجمالي الطلبات</p>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="stat-card success">
            <div class="icon">
                <i class="bi bi-cash-stack"></i>
            </div>
            <h3><?php echo number_format($stats['total_revenue']); ?> <?php echo $currency; ?></h3>
            <p>إجمالي الإيرادات</p>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="stat-card info">
            <div class="icon">
                <i class="bi bi-calculator"></i>
            </div>
            <h3><?php echo number_format($stats['avg_order_value']); ?> <?php echo $currency; ?></h3>
            <p>متوسط قيمة الطلب</p>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="stat-card warning">
            <div class="icon">
                <i class="bi bi-people"></i>
            </div>
            <h3><?php echo number_format($stats['unique_customers']); ?></h3>
            <p>عدد العملاء</p>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- رسم بياني للمبيعات -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-graph-up me-2"></i>المبيعات اليومية
                </h5>
            </div>
            <div class="card-body">
                <canvas id="salesChart" height="80"></canvas>
            </div>
        </div>
    </div>
    
    <!-- إحصائيات الطلبات حسب الحالة -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-pie-chart me-2"></i>الطلبات حسب الحالة
                </h5>
            </div>
            <div class="card-body">
                <canvas id="statusChart"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mt-1">
    <!-- المنتجات الأكثر مبيعاً -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-star me-2"></i>المنتجات الأكثر مبيعاً
                </h5>
            </div>
            <div class="card-body">
                <?php if (!empty($topProducts)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>المنتج</th>
                                    <th>الكمية</th>
                                    <th>الإيرادات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($topProducts as $index => $product): ?>
                                    <tr>
                                        <td>
                                            <?php if ($index < 3): ?>
                                                <span class="badge bg-warning"><?php echo $index + 1; ?></span>
                                            <?php else: ?>
                                                <?php echo $index + 1; ?>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($product['name_ar']); ?></strong>
                                            <br>
                                            <small class="text-muted"><?php echo $product['orders_count']; ?> طلب</small>
                                        </td>
                                        <td><span class="badge bg-info"><?php echo $product['total_quantity']; ?></span></td>
                                        <td><strong><?php echo number_format($product['total_revenue']); ?> <?php echo $currency; ?></strong></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-center text-muted py-4">لا توجد مبيعات في هذه الفترة</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- أفضل العملاء -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-trophy me-2"></i>أفضل العملاء
                </h5>
            </div>
            <div class="card-body">
                <?php if (!empty($topCustomers)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>العميل</th>
                                    <th>الطلبات</th>
                                    <th>الإنفاق</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($topCustomers as $index => $customer): ?>
                                    <tr>
                                        <td>
                                            <?php if ($index < 3): ?>
                                                <span class="badge bg-warning"><?php echo $index + 1; ?></span>
                                            <?php else: ?>
                                                <?php echo $index + 1; ?>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($customer['customer_name']); ?></strong>
                                            <br>
                                            <small class="text-muted">
                                                <i class="bi bi-telephone"></i> <?php echo htmlspecialchars($customer['customer_phone']); ?>
                                            </small>
                                        </td>
                                        <td><span class="badge bg-info"><?php echo $customer['orders_count']; ?></span></td>
                                        <td><strong class="text-success"><?php echo number_format($customer['total_spent']); ?> <?php echo $currency; ?></strong></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-center text-muted py-4">لا يوجد عملاء في هذه الفترة</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- الفئات -->
<?php if (!empty($topCategories)): ?>
<div class="row g-4 mt-1">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-tags me-2"></i>الفئات الأكثر مبيعاً
                </h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <?php foreach ($topCategories as $category): ?>
                        <div class="col-md-3">
                            <div class="category-stat">
                                <h6><?php echo htmlspecialchars($category['name_ar']); ?></h6>
                                <div class="stat-row">
                                    <span class="label">الطلبات:</span>
                                    <span class="value"><?php echo $category['orders_count']; ?></span>
                                </div>
                                <div class="stat-row">
                                    <span class="label">الكمية:</span>
                                    <span class="value"><?php echo $category['total_quantity']; ?></span>
                                </div>
                                <div class="stat-row">
                                    <span class="label">الإيرادات:</span>
                                    <span class="value text-success"><?php echo number_format($category['total_revenue']); ?> <?php echo $currency; ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// رسم بياني للمبيعات اليومية
const salesData = {
    labels: [
        <?php foreach ($dailySales as $sale): ?>
            '<?php echo date('m/d', strtotime($sale['date'])); ?>',
        <?php endforeach; ?>
    ],
    datasets: [{
        label: 'الطلبات',
        data: [
            <?php foreach ($dailySales as $sale): ?>
                <?php echo $sale['orders']; ?>,
            <?php endforeach; ?>
        ],
        backgroundColor: 'rgba(102, 126, 234, 0.2)',
        borderColor: 'rgba(102, 126, 234, 1)',
        borderWidth: 2,
        yAxisID: 'y',
    }, {
        label: 'الإيرادات (<?php echo $currency; ?>)',
        data: [
            <?php foreach ($dailySales as $sale): ?>
                <?php echo $sale['revenue']; ?>,
            <?php endforeach; ?>
        ],
        backgroundColor: 'rgba(40, 199, 111, 0.2)',
        borderColor: 'rgba(40, 199, 111, 1)',
        borderWidth: 2,
        yAxisID: 'y1',
    }]
};

const salesChart = new Chart(document.getElementById('salesChart'), {
    type: 'line',
    data: salesData,
    options: {
        responsive: true,
        interaction: {
            mode: 'index',
            intersect: false,
        },
        scales: {
            y: {
                type: 'linear',
                display: true,
                position: 'right',
            },
            y1: {
                type: 'linear',
                display: true,
                position: 'left',
                grid: {
                    drawOnChartArea: false,
                },
            },
        }
    }
});

// رسم دائري لحالات الطلبات
const statusLabels = {
    'pending': 'معلق',
    'confirmed': 'مؤكد',
    'processing': 'قيد المعالجة',
    'shipped': 'تم الشحن',
    'delivered': 'تم التوصيل',
    'cancelled': 'ملغي'
};

const statusColors = {
    'pending': '#ffc107',
    'confirmed': '#17a2b8',
    'processing': '#667eea',
    'shipped': '#28c76f',
    'delivered': '#28a745',
    'cancelled': '#dc3545'
};

const statusChart = new Chart(document.getElementById('statusChart'), {
    type: 'doughnut',
    data: {
        labels: [
            <?php foreach ($orderStatusStats as $stat): ?>
                '<?php echo $statusLabels[$stat['status']] ?? $stat['status']; ?>',
            <?php endforeach; ?>
        ],
        datasets: [{
            data: [
                <?php foreach ($orderStatusStats as $stat): ?>
                    <?php echo $stat['count']; ?>,
                <?php endforeach; ?>
            ],
            backgroundColor: [
                <?php foreach ($orderStatusStats as $stat): ?>
                    '<?php echo $statusColors[$stat['status']] ?? '#6c757d'; ?>',
                <?php endforeach; ?>
            ],
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'bottom',
            }
        }
    }
});
</script>

<style>
.period-filter {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: white;
    padding: 20px;
    border-radius: 15px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
}

.period-text {
    font-size: 16px;
    color: #6c757d;
}

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
}

.stat-card .icon {
    position: absolute;
    top: -10px;
    left: -10px;
    font-size: 100px;
    opacity: 0.1;
}

.stat-card h3 {
    font-size: 28px;
    font-weight: 700;
    margin: 10px 0;
}

.stat-card.primary .icon { color: #667eea; }
.stat-card.success .icon { color: #28c76f; }
.stat-card.info .icon { color: #17a2b8; }
.stat-card.warning .icon { color: #ffc107; }

.category-stat {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 10px;
    border-right: 4px solid var(--primary-color);
}

.category-stat h6 {
    font-weight: 700;
    margin-bottom: 15px;
    color: #333;
}

.category-stat .stat-row {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    border-bottom: 1px solid #dee2e6;
}

.category-stat .stat-row:last-child {
    border-bottom: none;
}

.category-stat .label {
    color: #6c757d;
    font-size: 14px;
}

.category-stat .value {
    font-weight: 600;
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
    background: #f8f9fa;
    border-bottom: 2px solid #dee2e6;
    font-weight: 600;
}
</style>

<?php include 'includes/footer.php'; ?>

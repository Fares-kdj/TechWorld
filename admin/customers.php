<?php
$pageTitle = 'إدارة العملاء';
include 'includes/header.php';

global $pdo; // Ensure $pdo is global
$searchQuery = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';

// جلب العملاء من الطلبات (تجميع حسب رقم الهاتف)
$sql = "SELECT 
            customer_name,
            customer_phone,
            customer_email,
            shipping_address as address,
            COUNT(id) as total_orders,
            SUM(total) as total_spent,
            MAX(ordered_at) as last_order_date,
            MIN(ordered_at) as first_order_date
        FROM orders
        WHERE 1=1";

$params = [];

if ($searchQuery) {
    $sql .= " AND (customer_name LIKE ? OR customer_phone LIKE ? OR customer_email LIKE ?)";
    $params[] = "%$searchQuery%";
    $params[] = "%$searchQuery%";
    $params[] = "%$searchQuery%";
}

$sql .= " GROUP BY customer_phone
          ORDER BY total_spent DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$customers = $stmt->fetchAll();

// إحصائيات
$totalCustomers = count($customers);
$totalRevenue = array_sum(array_column($customers, 'total_spent'));
$avgOrderValue = $totalCustomers > 0 ? $totalRevenue / array_sum(array_column($customers, 'total_orders')) : 0;

// عرض تفاصيل عميل
$viewCustomer = null;
if (isset($_GET['view'])) {
    $phone = sanitizeInput($_GET['view']);
    
    // جلب معلومات العميل
    foreach ($customers as $c) {
        if ($c['customer_phone'] == $phone) {
            $viewCustomer = $c;
            break;
        }
    }
    
    if ($viewCustomer) {
        // جلب طلبات العميل
        $stmt = $pdo->prepare("SELECT * FROM orders WHERE customer_phone = ? ORDER BY ordered_at DESC");
        $stmt->execute([$phone]);
        $customerOrders = $stmt->fetchAll();
    }
}

$currency = getSetting('currency_symbol', 'دج');
?>

<?php if ($viewCustomer): ?>
    <!-- تفاصيل العميل -->
    <div class="row g-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4>
                    <i class="bi bi-person me-2"></i>
                    تفاصيل العميل
                </h4>
                <a href="customers.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-right me-2"></i>رجوع
                </a>
            </div>
        </div>
        
        <!-- معلومات العميل -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body text-center">
                    <div class="customer-avatar mx-auto mb-3">
                        <?php echo mb_substr($viewCustomer['customer_name'], 0, 2); ?>
                    </div>
                    <h4><?php echo htmlspecialchars($viewCustomer['customer_name']); ?></h4>
                    
                    <div class="customer-info mt-4 text-start">
                        <div class="info-item">
                            <i class="bi bi-telephone"></i>
                            <strong>الهاتف:</strong>
                            <span><?php echo htmlspecialchars($viewCustomer['customer_phone']); ?></span>
                        </div>
                        
                        <!-- Email removed -->
                        
                        <div class="info-item">
                            <i class="bi bi-geo-alt"></i>
                            <strong>العنوان:</strong>
                            <span><?php echo htmlspecialchars($viewCustomer['address']); ?></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- إحصائيات العميل -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-graph-up me-2"></i>الإحصائيات
                    </h5>
                </div>
                <div class="card-body">
                    <div class="stat-row">
                        <div class="stat-label">
                            <i class="bi bi-cart-check text-primary"></i>
                            إجمالي الطلبات
                        </div>
                        <div class="stat-value"><?php echo $viewCustomer['total_orders']; ?></div>
                    </div>
                    
                    <div class="stat-row">
                        <div class="stat-label">
                            <i class="bi bi-cash text-success"></i>
                            إجمالي المشتريات
                        </div>
                        <div class="stat-value"><?php echo number_format($viewCustomer['total_spent']); ?> <?php echo $currency; ?></div>
                    </div>
                    
                    <div class="stat-row">
                        <div class="stat-label">
                            <i class="bi bi-calculator text-info"></i>
                            متوسط قيمة الطلب
                        </div>
                        <div class="stat-value">
                            <?php echo number_format($viewCustomer['total_spent'] / $viewCustomer['total_orders']); ?> <?php echo $currency; ?>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="text-muted small">
                        <div class="mb-2">
                            <i class="bi bi-calendar-plus me-2"></i>
                            أول طلب: <?php echo date('Y-m-d', strtotime($viewCustomer['first_order_date'])); ?>
                        </div>
                        <div>
                            <i class="bi bi-calendar-check me-2"></i>
                            آخر طلب: <?php echo date('Y-m-d', strtotime($viewCustomer['last_order_date'])); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- طلبات العميل -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-clock-history me-2"></i>
                        سجل الطلبات (<?php echo count($customerOrders); ?>)
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>رقم الطلب</th>
                                    <th>المبلغ</th>
                                    <th>الحالة</th>
                                    <th>التاريخ</th>
                                    <th>إجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($customerOrders as $order): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($order['order_number']); ?></strong></td>
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
                                            <a href="orders.php?view=<?php echo $order['id']; ?>" 
                                               class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-eye"></i> عرض
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php else: ?>
    <!-- قائمة العملاء -->
    
    <!-- إحصائيات سريعة -->
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="stat-card primary">
                <div class="icon">
                    <i class="bi bi-people"></i>
                </div>
                <h3><?php echo number_format($totalCustomers); ?></h3>
                <p>إجمالي العملاء</p>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="stat-card success">
                <div class="icon">
                    <i class="bi bi-cash-stack"></i>
                </div>
                <h3><?php echo number_format($totalRevenue); ?> <?php echo $currency; ?></h3>
                <p>إجمالي الإيرادات</p>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="stat-card info">
                <div class="icon">
                    <i class="bi bi-calculator"></i>
                </div>
                <h3><?php echo number_format($avgOrderValue); ?> <?php echo $currency; ?></h3>
                <p>متوسط قيمة الطلب</p>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h5 class="mb-0">
                        <i class="bi bi-people me-2"></i>جميع العملاء
                    </h5>
                </div>
                <div class="col-md-6">
                    <form method="GET" class="d-flex">
                        <input type="text" name="search" class="form-control" 
                               placeholder="بحث بالاسم، الهاتف، أو البريد..."
                               value="<?php echo htmlspecialchars($searchQuery); ?>">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="bi bi-search"></i>
                        </button>
                        <?php if ($searchQuery): ?>
                            <a href="customers.php" class="btn btn-secondary">
                                <i class="bi bi-x"></i>
                            </a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>
        <div class="card-body">
            <?php if (empty($customers)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-inbox" style="font-size: 48px; color: #ccc;"></i>
                    <p class="text-muted mt-3">لا يوجد عملاء بعد</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>العميل</th>
                                <th>معلومات التواصل</th>
                                <th>عدد الطلبات</th>
                                <th>إجمالي المشتريات</th>
                                <th>آخر طلب</th>
                                <th>إجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($customers as $customer): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="customer-avatar-sm me-2">
                                                <?php echo mb_substr($customer['customer_name'], 0, 2); ?>
                                            </div>
                                            <strong><?php echo htmlspecialchars($customer['customer_name']); ?></strong>
                                        </div>
                                    </td>
                                    <td>
                                        <div>
                                            <i class="bi bi-telephone text-primary"></i>
                                            <?php echo htmlspecialchars($customer['customer_phone']); ?>
                                        </div>
                                        <!-- Email removed -->
                                    </td>
                                    <td>
                                        <span class="badge bg-info"><?php echo $customer['total_orders']; ?> طلب</span>
                                    </td>
                                    <td>
                                        <strong class="text-success">
                                            <?php echo number_format($customer['total_spent']); ?> <?php echo $currency; ?>
                                        </strong>
                                    </td>
                                    <td>
                                        <small><?php echo date('Y-m-d', strtotime($customer['last_order_date'])); ?></small>
                                    </td>
                                    <td>
                                        <a href="?view=<?php echo urlencode($customer['customer_phone']); ?>" 
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-eye"></i> عرض
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

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

.customer-avatar {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 36px;
    font-weight: 700;
}

.customer-avatar-sm {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 14px;
    font-weight: 700;
}

.customer-info {
    background: #f8f9fa;
    border-radius: 10px;
    padding: 15px;
}

.info-item {
    padding: 10px 0;
    border-bottom: 1px solid #dee2e6;
    display: flex;
    align-items: center;
    gap: 10px;
}

.info-item:last-child {
    border-bottom: none;
}

.info-item i {
    color: var(--primary-color);
    font-size: 18px;
    width: 25px;
}

.info-item strong {
    min-width: 80px;
}

.info-item span {
    color: #6c757d;
}

.stat-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 0;
    border-bottom: 1px solid #dee2e6;
}

.stat-row:last-of-type {
    border-bottom: none;
}

.stat-label {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #495057;
}

.stat-value {
    font-weight: 700;
    color: #333;
}

.card {
    border: none;
    border-radius: 15px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
}

.table thead th {
    background: #f8f9fa;
    border-bottom: 2px solid #dee2e6;
    font-weight: 600;
}
</style>

<?php include 'includes/footer.php'; ?>

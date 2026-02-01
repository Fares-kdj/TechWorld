<?php
require_once '../includes/functions.php'; // Load functions first
require_once '../config/database.php'; // Ensure DB is loaded if not already

// معالجة تحديث الحالة
if (isset($_POST['update_status'])) {
    $pdo = getDB();
    
    $orderId = (int)$_POST['order_id'];
    $newStatus = clean($_POST['status']);
    
    $stmt = $pdo->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?");
    if ($stmt->execute([$newStatus, $orderId])) {
        setFlashMessage('success', 'تم تحديث حالة الطلب بنجاح');
    }
    header('Location: orders.php');
    exit;
}

// معالجة الحذف
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    require_once '../config/database.php'; // Ensure DB is loaded
    $pdo = getDB();
    
    $id = (int)$_GET['delete'];
    
    // حذف عناصر الطلب أولاً
    $pdo->prepare("DELETE FROM order_items WHERE order_id = ?")->execute([$id]);
    
    // حذف الطلب
    $stmt = $pdo->prepare("DELETE FROM orders WHERE id = ?");
    if ($stmt->execute([$id])) {
        setFlashMessage('success', 'تم حذف الطلب بنجاح');
    }
    
    header('Location: orders.php');
    exit;
}

$pageTitle = 'إدارة الطلبات';
include 'includes/header.php';

global $pdo;

// الفلترة
$filterStatus = isset($_GET['status']) ? $_GET['status'] : '';
$searchQuery = isset($_GET['search']) ? clean($_GET['search']) : '';

// بناء الاستعلام
$sql = "SELECT o.*, COUNT(oi.id) as items_count 
        FROM orders o
        LEFT JOIN order_items oi ON o.id = oi.order_id
        WHERE 1=1";

$params = [];

if ($filterStatus) {
    $sql .= " AND o.status = ?";
    $params[] = $filterStatus;
}

if ($searchQuery) {
    $sql .= " AND (o.order_number LIKE ? OR o.customer_name LIKE ? OR o.customer_phone LIKE ?)";
    $params[] = "%$searchQuery%";
    $params[] = "%$searchQuery%";
    $params[] = "%$searchQuery%";
}

$sql .= " GROUP BY o.id ORDER BY o.ordered_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

// إحصائيات الطلبات
$orderStats = $pdo->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
        SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing,
        SUM(CASE WHEN status = 'shipped' THEN 1 ELSE 0 END) as shipped,
        SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as delivered,
        SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
    FROM orders
")->fetch();

// إذا كان هناك عرض تفاصيل
$viewOrder = null;
if (isset($_GET['view']) && is_numeric($_GET['view'])) {
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->execute([$_GET['view']]);
    $viewOrder = $stmt->fetch();
    
    if ($viewOrder) {
        $stmt = $pdo->prepare("
            SELECT oi.*, p.name_ar, p.name_en, oi.product_image as image
            FROM order_items oi
            JOIN products p ON oi.product_id = p.id
            WHERE oi.order_id = ?
        ");
        $stmt->execute([$viewOrder['id']]);
        $orderItems = $stmt->fetchAll();
    }
}

$currency = getSetting('currency_symbol', 'دج');
?>

<?php if ($viewOrder): ?>
    <!-- عرض تفاصيل الطلب -->
    <div class="row g-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4>
                    <i class="bi bi-receipt me-2"></i>
                    تفاصيل الطلب #<?php echo htmlspecialchars($viewOrder['order_number']); ?>
                </h4>
                <a href="orders.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-right me-2"></i>رجوع
                </a>
            </div>
        </div>
        
        <!-- معلومات الطلب -->
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-box-seam me-2"></i>المنتجات المطلوبة
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>المنتج</th>
                                    <th>السعر</th>
                                    <th>الكمية</th>
                                    <th>المجموع</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orderItems as $item): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <?php if ($item['image']): ?>
                                                    <img src="../<?php echo htmlspecialchars($item['image']); ?>" 
                                                         alt="" class="me-2" style="width: 50px; height: 50px; object-fit: cover; border-radius: 5px;">
                                                <?php endif; ?>
                                                <div>
                                                    <strong><?php echo htmlspecialchars($item['name_ar']); ?></strong>
                                                    <br>
                                                    <small class="text-muted"><?php echo htmlspecialchars($item['name_en']); ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?php echo number_format($item['price']); ?> <?php echo $currency; ?></td>
                                        <td><?php echo $item['quantity']; ?></td>
                                        <td><strong><?php echo number_format($item['subtotal']); ?> <?php echo $currency; ?></strong></td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr class="table-active">
                                    <td colspan="3" class="text-end"><strong>الإجمالي:</strong></td>
                                    <td><strong class="text-primary"><?php echo number_format($viewOrder['total']); ?> <?php echo $currency; ?></strong></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <!-- معلومات العميل -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-person me-2"></i>معلومات العميل
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="text-muted small">الاسم</label>
                        <p class="mb-0"><strong><?php echo htmlspecialchars($viewOrder['customer_name']); ?></strong></p>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted small">الهاتف</label>
                        <p class="mb-0">
                            <a href="tel:<?php echo htmlspecialchars($viewOrder['customer_phone']); ?>">
                                <?php echo htmlspecialchars($viewOrder['customer_phone']); ?>
                            </a>
                        </p>
                    </div>
                    <!-- Email removed as per request -->
                    <div class="mb-0">
                        <label class="text-muted small">العنوان</label>
                        <p class="mb-0">
                            <?php if (!empty($viewOrder['shipping_city'])): ?>
                                <span class="text-primary"><?php echo htmlspecialchars($viewOrder['shipping_city']); ?></span><br>
                            <?php endif; ?>
                            <?php echo htmlspecialchars($viewOrder['shipping_address']); ?>
                        </p>
                    </div>
                    <?php if ($viewOrder['notes']): ?>
                        <hr>
                        <div class="mb-0">
                            <label class="text-muted small">ملاحظات</label>
                            <p class="mb-0"><?php echo nl2br(htmlspecialchars($viewOrder['notes'])); ?></p>
                        </div>
                    <?php endif; ?>
                    <hr>
                    <div class="mb-0">
                        <label class="text-muted small">طريقة الدفع</label>
                        <p class="mb-0">
                            <?php
                            $paymentMethods = [
                                'cash_on_delivery' => '<i class="bi bi-cash"></i> الدفع عند الاستلام',
                                'bank_transfer' => '<i class="bi bi-bank"></i> تحويل بنكي',
                                'credit_card' => '<i class="bi bi-credit-card"></i> بطاقة ائتمان',
                                'mada' => '<i class="bi bi-credit-card-2-front"></i> مدى'
                            ];
                            echo $paymentMethods[$viewOrder['payment_method']] ?? $viewOrder['payment_method'];
                            ?>
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- حالة الطلب -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-clock-history me-2"></i>حالة الطلب
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <input type="hidden" name="order_id" value="<?php echo $viewOrder['id']; ?>">
                        
                        <div class="mb-3">
                            <label class="form-label">الحالة الحالية</label>
                            <select name="status" class="form-select" required>
                                <option value="pending" <?php echo $viewOrder['status'] == 'pending' ? 'selected' : ''; ?>>معلق</option>
                                <option value="confirmed" <?php echo $viewOrder['status'] == 'confirmed' ? 'selected' : ''; ?>>مؤكد</option>
                                <option value="processing" <?php echo $viewOrder['status'] == 'processing' ? 'selected' : ''; ?>>قيد المعالجة</option>
                                <option value="shipped" <?php echo $viewOrder['status'] == 'shipped' ? 'selected' : ''; ?>>تم الشحن</option>
                                <option value="delivered" <?php echo $viewOrder['status'] == 'delivered' ? 'selected' : ''; ?>>تم التوصيل</option>
                                <option value="cancelled" <?php echo $viewOrder['status'] == 'cancelled' ? 'selected' : ''; ?>>ملغي</option>
                            </select>
                        </div>
                        
                        <button type="submit" name="update_status" class="btn btn-primary w-100">
                            <i class="bi bi-check-circle me-2"></i>تحديث الحالة
                        </button>
                    </form>
                    
                    <hr>
                    
                    <div class="small text-muted">
                        <div class="mb-2">
                            <i class="bi bi-calendar-plus me-2"></i>
                            تاريخ الطلب: <?php echo date('Y-m-d H:i', strtotime($viewOrder['ordered_at'])); ?>
                        </div>
                        <?php if ($viewOrder['updated_at']): ?>
                            <div>
                                <i class="bi bi-calendar-check me-2"></i>
                                آخر تحديث: <?php echo date('Y-m-d H:i', strtotime($viewOrder['updated_at'])); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php else: ?>
    <!-- قائمة الطلبات -->
    
    <!-- إحصائيات سريعة -->
    <div class="row g-3 mb-4">
        <div class="col-md-2">
            <div class="stat-mini <?php echo !$filterStatus ? 'active' : ''; ?>">
                <a href="orders.php">
                    <div class="number"><?php echo $orderStats['total']; ?></div>
                    <div class="label">الكل</div>
                </a>
            </div>
        </div>
        <div class="col-md-2">
            <div class="stat-mini warning <?php echo $filterStatus == 'pending' ? 'active' : ''; ?>">
                <a href="?status=pending">
                    <div class="number"><?php echo $orderStats['pending']; ?></div>
                    <div class="label">معلق</div>
                </a>
            </div>
        </div>
        <div class="col-md-2">
            <div class="stat-mini info <?php echo $filterStatus == 'confirmed' ? 'active' : ''; ?>">
                <a href="?status=confirmed">
                    <div class="number"><?php echo $orderStats['confirmed']; ?></div>
                    <div class="label">مؤكد</div>
                </a>
            </div>
        </div>
        <div class="col-md-2">
            <div class="stat-mini primary <?php echo $filterStatus == 'processing' ? 'active' : ''; ?>">
                <a href="?status=processing">
                    <div class="number"><?php echo $orderStats['processing']; ?></div>
                    <div class="label">قيد المعالجة</div>
                </a>
            </div>
        </div>
        <div class="col-md-2">
            <div class="stat-mini success <?php echo $filterStatus == 'delivered' ? 'active' : ''; ?>">
                <a href="?status=delivered">
                    <div class="number"><?php echo $orderStats['delivered']; ?></div>
                    <div class="label">مكتمل</div>
                </a>
            </div>
        </div>
        <div class="col-md-2">
            <div class="stat-mini danger <?php echo $filterStatus == 'cancelled' ? 'active' : ''; ?>">
                <a href="?status=cancelled">
                    <div class="number"><?php echo $orderStats['cancelled']; ?></div>
                    <div class="label">ملغي</div>
                </a>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h5 class="mb-0">
                        <i class="bi bi-cart-check me-2"></i>جميع الطلبات
                    </h5>
                </div>
                <div class="col-md-6">
                    <form method="GET" class="d-flex">
                        <?php if ($filterStatus): ?>
                            <input type="hidden" name="status" value="<?php echo $filterStatus; ?>">
                        <?php endif; ?>
                        <input type="text" name="search" class="form-control" 
                               placeholder="بحث برقم الطلب، الاسم، أو الهاتف..."
                               value="<?php echo htmlspecialchars($searchQuery); ?>">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="bi bi-search"></i>
                        </button>
                        <?php if ($searchQuery || $filterStatus): ?>
                            <a href="orders.php" class="btn btn-secondary">
                                <i class="bi bi-x"></i>
                            </a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>
        <div class="card-body">
            <?php if (empty($orders)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-inbox" style="font-size: 48px; color: #ccc;"></i>
                    <p class="text-muted mt-3">لا توجد طلبات</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>رقم الطلب</th>
                                <th>العميل</th>
                                <th>المبلغ</th>
                                <th>طريقة الدفع</th>
                                <th>العناصر</th>
                                <th>الحالة</th>
                                <th>التاريخ</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td>
                                        <strong class="text-primary"><?php echo htmlspecialchars($order['order_number']); ?></strong>
                                    </td>
                                    <td>
                                        <div>
                                            <strong><?php echo htmlspecialchars($order['customer_name']); ?></strong>
                                            <br>
                                            <small class="text-muted">
                                                <i class="bi bi-telephone"></i> <?php echo htmlspecialchars($order['customer_phone']); ?>
                                            </small>
                                            <?php if (!empty($order['shipping_city'])): ?>
                                            <br>
                                            <small class="text-muted">
                                                <i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($order['shipping_city']); ?>
                                            </small>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td><strong><?php echo number_format($order['total']); ?> <?php echo $currency; ?></strong></td>
                                    <td>
                                        <?php
                                        $paymentMethods = [
                                            'cash_on_delivery' => 'الدفع عند الاستلام',
                                            'bank_transfer' => 'تحويل بنكي',
                                            'credit_card' => 'بطاقة ائتمان',
                                            'mada' => 'مدى'
                                        ];
                                        ?>
                                        <small class="text-muted">
                                            <i class="bi bi-credit-card me-1"></i>
                                            <?php echo $paymentMethods[$order['payment_method']] ?? $order['payment_method']; ?>
                                        </small>
                                    </td>
                                    <td>
                                        <span class="badge bg-info"><?php echo $order['items_count']; ?> منتج</span>
                                    </td>
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
                                    <td>
                                        <small><?php echo date('Y-m-d', strtotime($order['ordered_at'])); ?></small>
                                        <br>
                                        <small class="text-muted"><?php echo date('H:i', strtotime($order['ordered_at'])); ?></small>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="?view=<?php echo $order['id']; ?>" 
                                               class="btn btn-outline-primary" title="عرض">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="?delete=<?php echo $order['id']; ?>" 
                                               class="btn btn-outline-danger"
                                               onclick="return confirm('هل أنت متأكد من حذف هذا الطلب؟')" 
                                               title="حذف">
                                                <i class="bi bi-trash"></i>
                                            </a>
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
<?php endif; ?>

<style>
.stat-mini {
    background: white;
    border-radius: 10px;
    padding: 15px;
    text-align: center;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    transition: all 0.3s;
    border: 2px solid transparent;
}

.stat-mini a {
    text-decoration: none;
    color: inherit;
}

.stat-mini:hover,
.stat-mini.active {
    transform: translateY(-3px);
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
}

.stat-mini.active {
    border-color: var(--primary-color);
}

.stat-mini .number {
    font-size: 24px;
    font-weight: 700;
    color: #333;
}

.stat-mini .label {
    font-size: 12px;
    color: #6c757d;
    margin-top: 5px;
}

.stat-mini.warning .number { color: #ffc107; }
.stat-mini.info .number { color: #17a2b8; }
.stat-mini.primary .number { color: #667eea; }
.stat-mini.success .number { color: #28c76f; }
.stat-mini.danger .number { color: #ea5455; }

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

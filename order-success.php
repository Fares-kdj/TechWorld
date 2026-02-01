<?php
require_once 'includes/functions.php';
require_once 'config/database.php';

$orderNumber = isset($_GET['order_number']) ? clean($_GET['order_number']) : null;

if (!$orderNumber) {
    header('Location: index.php');
    exit;
}

$conn = getDB();
$stmt = $conn->prepare("SELECT * FROM orders WHERE order_number = ?");
$stmt->execute([$orderNumber]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    header('Location: index.php');
    exit;
}

$pageTitle = 'تم الطلب بنجاح';
include 'includes/header.php';
?>

<div class="container" style="padding: 3rem 1rem; max-width: 700px; text-align: center;">
    <div style="background: var(--white); border-radius: 1rem; box-shadow: var(--shadow); padding: 3rem 2rem;">
        <div style="width: 100px; height: 100px; background: linear-gradient(135deg, var(--success) 0%, #059669 100%); border-radius: 50%; margin: 0 auto 2rem; display: flex; align-items: center; justify-content: center;">
            <svg width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="3">
                <polyline points="20 6 9 17 4 12"></polyline>
            </svg>
        </div>
        
        <h1 style="font-size: 2rem; font-weight: 800; margin-bottom: 1rem; color: var(--success);">تم إرسال طلبك بنجاح!</h1>
        
        <p style="font-size: 1.125rem; color: var(--gray); margin-bottom: 2rem;">
            شكراً لك على طلبك. سنتواصل معك قريباً لتأكيد الطلب.
        </p>
        
        <div style="background: var(--light-gray); padding: 1.5rem; border-radius: 0.5rem; margin-bottom: 2rem;">
            <div style="font-size: 0.875rem; color: var(--gray); margin-bottom: 0.5rem;">رقم الطلب</div>
            <div style="font-size: 1.5rem; font-weight: 700; color: var(--primary);"><?php echo htmlspecialchars($order['order_number']); ?></div>
        </div>
        
        <div style="text-align: right; margin-bottom: 2rem;">
            <h3 style="font-size: 1.125rem; font-weight: 700; margin-bottom: 1rem;">تفاصيل الطلب:</h3>
            <div style="display: grid; gap: 0.5rem;">
                <div style="display: flex; justify-content: space-between; padding: 0.5rem 0; border-bottom: 1px solid var(--border);">
                    <span style="color: var(--gray);">المجموع الفرعي:</span>
                    <span style="font-weight: 600;"><?php echo number_format($order['subtotal']); ?> <?php echo getSetting('currency_symbol', 'دج'); ?></span>
                </div>
                <div style="display: flex; justify-content: space-between; padding: 0.5rem 0; border-bottom: 1px solid var(--border);">
                    <span style="color: var(--gray);">الشحن:</span>
                    <span style="font-weight: 600;"><?php echo number_format($order['shipping_cost']); ?> <?php echo getSetting('currency_symbol', 'دج'); ?></span>
                </div>
                <div style="display: flex; justify-content: space-between; padding: 0.5rem 0; font-size: 1.125rem; font-weight: 700;">
                    <span>الإجمالي:</span>
                    <span style="color: var(--primary);"><?php echo number_format($order['total']); ?> <?php echo getSetting('currency_symbol', 'دج'); ?></span>
                </div>
            </div>
        </div>
        
        <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
            <a href="index.php" class="btn btn-primary">
                العودة للرئيسية
            </a>
            <a href="products.php" class="btn btn-secondary">
                تصفح المنتجات
            </a>
        </div>
    </div>
</div>

<script>
// مسح السلة بعد الطلب الناجح
localStorage.removeItem('cart');
updateCartUI();
</script>

<?php
include 'includes/footer.php';
?>

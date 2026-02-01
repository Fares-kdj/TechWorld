<?php
require_once 'includes/functions.php';

$pageTitle = 'إتمام الطلب';
$shippingCost = getSetting('shipping_cost', 500);
$currency = getSetting('currency_symbol', 'دج');

include 'includes/header.php';
?>

<div class="container" style="padding: 3rem 1rem; max-width: 900px;">
    <h1 style="font-size: 2.5rem; font-weight: 800; margin-bottom: 2rem; text-align: center;">إتمام الطلب</h1>
    
    <div style="background: var(--white); border-radius: 1rem; box-shadow: var(--shadow); padding: 2rem; margin-bottom: 2rem;">
        <h2 style="font-size: 1.5rem; font-weight: 700; margin-bottom: 1.5rem;">معلومات العميل</h2>
        
        <form id="checkoutForm" method="POST" action="process-order.php">
            <div style="display: grid; gap: 1.5rem;">
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 0.5rem;">الاسم الكامل *</label>
                    <input type="text" name="customer_name" class="filter-input" required placeholder="أدخل اسمك الكامل">
                </div>
                
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 0.5rem;">رقم الهاتف *</label>
                    <input type="tel" name="customer_phone" class="filter-input" required placeholder="+213 XXX XXX XXX">
                </div>
                
                <!-- Address field removed as per user request -->
                <input type="hidden" name="customer_address" value="">
                
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem;">
                    <div>
                        <label style="display: block; font-weight: 600; margin-bottom: 0.5rem;">الولاية *</label>
                        <select name="wilaya_id" class="filter-select" required>
                            <option value="">اختر الولاية</option>
                        </select>
                    </div>
                    <div>
                        <label style="display: block; font-weight: 600; margin-bottom: 0.5rem;">الدائرة *</label>
                        <select name="daira_id" class="filter-select" required disabled>
                            <option value="">اختر الدائرة</option>
                        </select>
                    </div>
                    <div>
                        <label style="display: block; font-weight: 600; margin-bottom: 0.5rem;">البلدية *</label>
                        <select name="baladiya_id" class="filter-select" required disabled>
                            <option value="">اختر البلدية</option>
                        </select>
                    </div>
                </div>
                <!-- Hidden fields to store names -->
                <input type="hidden" name="customer_city" id="customer_city">
                <input type="hidden" name="wilaya_name" id="wilaya_name">
                <input type="hidden" name="daira_name" id="daira_name">
                <input type="hidden" name="baladiya_name" id="baladiya_name">
                
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 0.5rem;">طريقة الدفع *</label>
                    <select name="payment_method" class="filter-select" required>
                        <option value="cash_on_delivery">الدفع عند الاستلام</option>
                        <option value="bank_transfer">تحويل بنكي</option>
                    </select>
                </div>
                
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 0.5rem;">ملاحظات إضافية (اختياري)</label>
                    <textarea name="notes" class="filter-input" rows="3" placeholder="أي ملاحظات خاصة بالطلب..."></textarea>
                </div>
            </div>
            
            <div id="order-summary" style="margin-top: 2rem; padding-top: 2rem; border-top: 2px solid var(--border);">
                <h3 style="font-size: 1.25rem; font-weight: 700; margin-bottom: 1rem;">ملخص الطلب</h3>
                <div id="checkout-items"></div>
                
                <div style="margin-top: 1.5rem; padding: 1rem; background: var(--light-gray); border-radius: 0.5rem;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                        <span>المجموع الفرعي:</span>
                        <span id="subtotal-display">0 <?php echo $currency; ?></span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                        <span>الشحن:</span>
                        <span><?php echo formatPrice($shippingCost); ?> <?php echo $currency; ?></span>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: 1.25rem; font-weight: 700; padding-top: 1rem; border-top: 1px solid var(--border); margin-top: 0.5rem;">
                        <span>الإجمالي:</span>
                        <span id="total-display">0 <?php echo $currency; ?></span>
                    </div>
                </div>
            </div>
            
            <input type="hidden" name="cart_data" id="cart_data">
            <input type="hidden" name="shipping_cost" value="<?php echo $shippingCost; ?>">
            
            <button type="submit" class="btn btn-primary btn-block" style="margin-top: 2rem; font-size: 1.125rem; padding: 1rem;">
                تأكيد الطلب
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M5 12h14M12 5l7 7-7 7"/>
                </svg>
            </button>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const cart = JSON.parse(localStorage.getItem('cart')) || [];
    // ... existing cart check ...
    if (cart.length === 0) {
        window.location.href = 'index.php';
        return;
    }

    // Load Wilayas
    fetch('api/get_locations.php?type=wilayas')
        .then(response => response.json())
        .then(data => {
            const wilayaSelect = document.querySelector('select[name="wilaya_id"]');
            data.forEach(wilaya => {
                const option = document.createElement('option');
                option.value = wilaya.id;
                option.textContent = wilaya.id + ' - ' + wilaya.name_ar;
                option.dataset.name = wilaya.name_ar;
                wilayaSelect.appendChild(option);
            });
        });

    // Handle Wilaya Change
    document.querySelector('select[name="wilaya_id"]').addEventListener('change', function() {
        const wilayaId = this.value;
        const dairaSelect = document.querySelector('select[name="daira_id"]');
        const communeSelect = document.querySelector('select[name="baladiya_id"]');
        
        // Update hidden wilaya name
        const selectedOption = this.options[this.selectedIndex];
        document.getElementById('wilaya_name').value = selectedOption.dataset.name || '';
        document.getElementById('daira_name').value = '';
        document.getElementById('baladiya_name').value = '';
        updateCustomerCity();

        dairaSelect.innerHTML = '<option value="">اختر الدائرة</option>';
        communeSelect.innerHTML = '<option value="">اختر البلدية</option>';
        dairaSelect.disabled = true;
        communeSelect.disabled = true;

        if (wilayaId) {
            fetch(`api/get_locations.php?type=dairas&wilaya_id=${wilayaId}`)
                .then(response => response.json())
                .then(data => {
                    data.forEach(daira => {
                        const option = document.createElement('option');
                        option.value = daira.id;
                        option.textContent = daira.name_ar;
                        option.dataset.name = daira.name_ar;
                        dairaSelect.appendChild(option);
                    });
                    dairaSelect.disabled = false;
                });
        }
    });

    // Handle Daira Change
    document.querySelector('select[name="daira_id"]').addEventListener('change', function() {
        const dairaId = this.value;
        const communeSelect = document.querySelector('select[name="baladiya_id"]');
        
        // Update hidden daira name
        const selectedOption = this.options[this.selectedIndex];
        document.getElementById('daira_name').value = selectedOption.dataset.name || '';
        document.getElementById('baladiya_name').value = '';
        updateCustomerCity();

        communeSelect.innerHTML = '<option value="">اختر البلدية</option>';
        communeSelect.disabled = true;

        if (dairaId) {
            fetch(`api/get_locations.php?type=communes&daira_id=${dairaId}`)
                .then(response => response.json())
                .then(data => {
                    data.forEach(commune => {
                        const option = document.createElement('option');
                        option.value = commune.id;
                        option.textContent = commune.name_ar;
                        option.dataset.name = commune.name_ar;
                        communeSelect.appendChild(option);
                    });
                    communeSelect.disabled = false;
                });
        }
    });

    // Handle Commune Change
    document.querySelector('select[name="baladiya_id"]').addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        document.getElementById('baladiya_name').value = selectedOption.dataset.name || '';
        updateCustomerCity();
    });

    function updateCustomerCity() {
        const wilaya = document.getElementById('wilaya_name').value;
        const daira = document.getElementById('daira_name').value;
        const baladiya = document.getElementById('baladiya_name').value;
        
        let cityString = '';
        if (wilaya) cityString += wilaya;
        if (daira) cityString += ' - ' + daira;
        if (baladiya) cityString += ' - ' + baladiya;
        
        document.getElementById('customer_city').value = cityString;
    }

    const checkoutItems = document.getElementById('checkout-items');
    const subtotalDisplay = document.getElementById('subtotal-display');
    const totalDisplay = document.getElementById('total-display');
    const cartDataInput = document.getElementById('cart_data');
    const shippingCost = <?php echo $shippingCost; ?>;
    
    // عرض المنتجات
    checkoutItems.innerHTML = cart.map(item => `
        <div style="display: flex; gap: 1rem; padding: 1rem 0; border-bottom: 1px solid var(--border);">
            <img src="${item.image}" alt="${item.name}" style="width: 60px; height: 60px; object-fit: cover; border-radius: 0.5rem;">
            <div style="flex: 1;">
                <div style="font-weight: 600;">${item.name}</div>
                <div style="font-size: 0.875rem; color: var(--gray);">${item.model}</div>
                <div style="color: var(--primary); font-weight: 600; margin-top: 0.25rem;">
                    ${formatPrice(item.price)} <?php echo $currency; ?> × ${item.quantity}
                </div>
            </div>
            <div style="font-weight: 700; color: var(--primary);">
                ${formatPrice(item.price * item.quantity)} <?php echo $currency; ?>
            </div>
        </div>
    `).join('');
    
    // حساب المجموع
    const subtotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
    const total = subtotal + shippingCost;
    
    subtotalDisplay.innerHTML = formatPrice(subtotal) + ' <?php echo $currency; ?>';
    totalDisplay.innerHTML = formatPrice(total) + ' <?php echo $currency; ?>';
    cartDataInput.value = JSON.stringify(cart);
    
    // منع إرسال النموذج الفارغ
    document.getElementById('checkoutForm').addEventListener('submit', function(e) {
        if (cart.length === 0) {
            e.preventDefault();
            alert('السلة فارغة!');
            return false;
        }
    });
});
</script>

<style>
.filter-input, .filter-select {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #d1d5db; /* Explicit light gray border */
    border-radius: 0.5rem;
    font-family: 'Cairo', sans-serif;
    font-size: 1rem;
    background-color: #f9fafb; /* Light background for contrast */
    transition: all 0.2s;
}

.filter-input:focus, .filter-select:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

textarea.filter-input {
    resize: vertical;
}
</style>

<?php include 'includes/footer.php'; ?>

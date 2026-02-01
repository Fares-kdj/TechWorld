/**
 * ملف JavaScript الرئيسي للمتجر
 * Main JavaScript File
 */

// ==========================================
// سلة التسوق
// Shopping Cart
// ==========================================

// تهيئة السلة من localStorage
let cart = JSON.parse(localStorage.getItem('cart')) || [];

// تحديث عداد السلة عند تحميل الصفحة
document.addEventListener('DOMContentLoaded', function () {
    updateCartCount();
    updateCartUI();
});

/**
 * إضافة منتج للسلة
 */
function addToCart(id, name, price, image, model) {
    // البحث عن المنتج في السلة
    const existingItem = cart.find(item => item.id === id);

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

    saveCart();
    updateCartCount();
    updateCartUI();
    showNotification('تم إضافة المنتج إلى السلة', 'success');

    // فتح السلة تلقائياً
    openCart();
}

/**
 * إزالة منتج من السلة
 */
function removeFromCart(id) {
    cart = cart.filter(item => item.id !== id);
    saveCart();
    updateCartCount();
    updateCartUI();
    showNotification('تم إزالة المنتج من السلة', 'info');
}

/**
 * تحديث كمية منتج
 */
function updateQuantity(id, change) {
    const item = cart.find(item => item.id === id);

    if (item) {
        item.quantity += change;

        if (item.quantity <= 0) {
            removeFromCart(id);
        } else {
            saveCart();
            updateCartUI();
        }
    }
}

/**
 * حفظ السلة في localStorage
 */
function saveCart() {
    localStorage.setItem('cart', JSON.stringify(cart));
}

/**
 * تحديث عداد السلة
 */
function updateCartCount() {
    const count = cart.reduce((total, item) => total + item.quantity, 0);
    const countElements = document.querySelectorAll('.cart-count');

    countElements.forEach(element => {
        element.textContent = count;
        element.style.display = count > 0 ? 'flex' : 'none';
    });
}

/**
 * تحديث واجهة السلة
 */
function updateCartUI() {
    const cartBody = document.querySelector('.cart-body');
    const cartTotal = document.querySelector('.cart-total-value');

    if (!cartBody) return;

    if (cart.length === 0) {
        cartBody.innerHTML = `
            <div style="text-align: center; padding: 3rem 1rem; color: var(--gray-500);">
                <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="currentColor" style="margin: 0 auto 1rem;">
                    <circle cx="9" cy="21" r="1"></circle>
                    <circle cx="20" cy="21" r="1"></circle>
                    <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                </svg>
                <p style="font-size: 1.125rem;">السلة فارغة</p>
                <p style="font-size: 0.875rem; margin-top: 0.5rem;">ابدأ بإضافة المنتجات</p>
            </div>
        `;
        if (cartTotal) cartTotal.textContent = '0 ' + (typeof CURRENCY_SYMBOL !== 'undefined' ? CURRENCY_SYMBOL : 'دج');
        return;
    }

    let html = '';
    let total = 0;

    cart.forEach(item => {
        const subtotal = item.price * item.quantity;
        total += subtotal;

        html += `
            <div class="cart-item">
                <img src="${item.image}" alt="${item.name}" class="cart-item-image">
                <div class="cart-item-info">
                    <div class="cart-item-name">${item.name}</div>
                    <div class="cart-item-model">${item.model}</div>
                    <div class="cart-item-price">${formatPrice(item.price)} ${typeof CURRENCY_SYMBOL !== 'undefined' ? CURRENCY_SYMBOL : 'دج'}</div>
                    <div class="cart-item-actions">
                        <div class="quantity-control">
                            <button class="quantity-btn" onclick="updateQuantity('${item.id}', -1)">−</button>
                            <span class="quantity-value">${item.quantity}</span>
                            <button class="quantity-btn" onclick="updateQuantity('${item.id}', 1)">+</button>
                        </div>
                        <button class="remove-item" onclick="removeFromCart('${item.id}')">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="3 6 5 6 21 6"></polyline>
                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        `;
    });

    cartBody.innerHTML = html;
    if (cartTotal) cartTotal.innerHTML = formatPrice(total) + ' ' + (typeof CURRENCY_SYMBOL !== 'undefined' ? CURRENCY_SYMBOL : 'دج');
}

/**
 * فتح السلة
 */
function openCart() {
    const sidebar = document.querySelector('.cart-sidebar');
    const overlay = document.querySelector('.cart-overlay');

    if (sidebar) sidebar.classList.add('active');
    if (overlay) overlay.classList.add('active');
    document.body.style.overflow = 'hidden';
}

/**
 * إغلاق السلة
 */
function closeCart() {
    const sidebar = document.querySelector('.cart-sidebar');
    const overlay = document.querySelector('.cart-overlay');

    if (sidebar) sidebar.classList.remove('active');
    if (overlay) overlay.classList.remove('active');
    document.body.style.overflow = '';
}

/**
 * تفريغ السلة
 */
function clearCart() {
    showConfirmModal(
        'تفريغ السلة',
        'هل أنت متأكد من تفريغ السلة؟ سيتم حذف جميع المنتجات.',
        () => {
            cart = [];
            saveCart();
            updateCartCount();
            updateCartUI();
            showNotification('تم تفريغ السلة', 'success');
        }
    );
}

/**
 * عرض نافذة تأكيد مخصصة
 */
function showConfirmModal(title, message, onConfirm) {
    // إزالة أي modal سابق
    const existingModal = document.querySelector('.confirm-modal-overlay');
    if (existingModal) existingModal.remove();

    // إنشاء modal جديد
    const modalHTML = `
        <div class="confirm-modal-overlay" onclick="if(event.target === this) closeConfirmModal()">
            <div class="confirm-modal">
                <div class="confirm-modal-header">
                    <h3>${title}</h3>
                    <button class="confirm-modal-close" onclick="closeConfirmModal()">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    </button>
                </div>
                <div class="confirm-modal-body">
                    <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2" style="margin: 0 auto 1rem; display: block;">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                    <p>${message}</p>
                </div>
                <div class="confirm-modal-footer">
                    <button class="btn btn-secondary" onclick="closeConfirmModal()">إلغاء</button>
                    <button class="btn btn-danger" onclick="confirmAction()">تأكيد</button>
                </div>
            </div>
        </div>
    `;

    document.body.insertAdjacentHTML('beforeend', modalHTML);
    document.body.style.overflow = 'hidden';

    // حفظ الدالة للتنفيذ عند التأكيد
    window.currentConfirmAction = () => {
        onConfirm();
        closeConfirmModal();
    };
}

/**
 * إغلاق نافذة التأكيد
 */
function closeConfirmModal() {
    const modal = document.querySelector('.confirm-modal-overlay');
    if (modal) {
        modal.style.animation = 'fadeOut 0.2s ease';
        setTimeout(() => {
            modal.remove();
            document.body.style.overflow = '';
            delete window.currentConfirmAction;
        }, 200);
    }
}

/**
 * تنفيذ الإجراء المؤكد
 */
function confirmAction() {
    if (window.currentConfirmAction) {
        window.currentConfirmAction();
    }
}

// ==========================================
// البحث
// Search
// ==========================================

let searchTimeout;

function handleSearch(input) {
    clearTimeout(searchTimeout);

    searchTimeout = setTimeout(() => {
        const keyword = input.value.trim();

        if (keyword.length >= 2) {
            searchProducts(keyword);
        }
    }, 500);
}

function searchProducts(keyword) {
    // يمكن تنفيذ البحث عبر AJAX هنا
    window.location.href = `products.php?search=${encodeURIComponent(keyword)}`;
}

// ==========================================
// التصفية والفرز
// Filter & Sort
// ==========================================

function filterProducts() {
    const category = document.getElementById('filter-category')?.value || '';
    const minPrice = document.getElementById('filter-min-price')?.value || '';
    const maxPrice = document.getElementById('filter-max-price')?.value || '';
    const brand = document.getElementById('filter-brand')?.value || '';
    const sort = document.getElementById('sort-by')?.value || '';

    const params = new URLSearchParams(window.location.search);

    if (category) params.set('category', category);
    else params.delete('category');

    if (minPrice) params.set('min_price', minPrice);
    else params.delete('min_price');

    if (maxPrice) params.set('max_price', maxPrice);
    else params.delete('max_price');

    if (brand) params.set('brand', brand);
    else params.delete('brand');

    if (sort) params.set('sort', sort);
    else params.delete('sort');

    window.location.href = 'products.php?' + params.toString();
}

// ==========================================
// عملية الطلب
// Checkout Process
// ==========================================

function proceedToCheckout() {
    if (cart.length === 0) {
        showNotification('السلة فارغة!', 'error');
        return;
    }

    // حفظ السلة في session
    const formData = new FormData();
    formData.append('cart', JSON.stringify(cart));

    window.location.href = 'checkout.php';
}

// ==========================================
// التحقق من النموذج
// Form Validation
// ==========================================

function validateCheckoutForm(form) {
    const name = form.querySelector('[name="customer_name"]').value.trim();
    const phone = form.querySelector('[name="customer_phone"]').value.trim();
    const address = form.querySelector('[name="customer_address"]').value.trim();
    const city = form.querySelector('[name="customer_city"]').value.trim();

    if (!name) {
        showNotification('الرجاء إدخال الاسم الكامل', 'error');
        return false;
    }

    if (!phone || phone.length < 10) {
        showNotification('الرجاء إدخال رقم جوال صحيح', 'error');
        return false;
    }

    if (!address) {
        showNotification('الرجاء إدخال عنوان الشحن', 'error');
        return false;
    }

    if (!city) {
        showNotification('الرجاء اختيار المدينة', 'error');
        return false;
    }

    return true;
}

// ==========================================
// الإشعارات
// Notifications
// ==========================================

function showNotification(message, type = 'info') {
    // إزالة الإشعارات السابقة
    const existing = document.querySelector('.notification');
    if (existing) existing.remove();

    // إنشاء الإشعار
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <div style="display: flex; align-items: center; gap: 0.75rem;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                ${type === 'success' ? '<polyline points="20 6 9 17 4 12"></polyline>' :
            type === 'error' ? '<circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line>' :
                '<circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line>'}
            </svg>
            <span>${message}</span>
        </div>
    `;

    // إضافة الأنماط
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        left: 50%;
        transform: translateX(-50%);
        background: ${type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#3b82f6'};
        color: white;
        padding: 1rem 1.5rem;
        border-radius: 0.5rem;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        z-index: 9999;
        animation: slideDown 0.3s ease;
    `;

    document.body.appendChild(notification);

    // إزالة بعد 3 ثواني
    setTimeout(() => {
        notification.style.animation = 'slideUp 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// ==========================================
// دوال مساعدة
// Helper Functions
// ==========================================

/**
 * تنسيق السعر
 */
function formatPrice(price) {
    const formatted = new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(price);
    return `<span class="en-num">${formatted}</span>`;
}

/**
 * الحصول على السلة
 */
function getCart() {
    return cart;
}

/**
 * الحصول على إجمالي السلة
 */
function getCartTotal() {
    return cart.reduce((total, item) => total + (item.price * item.quantity), 0);
}

/**
 * الحصول على عدد العناصر في السلة
 */
function getCartItemsCount() {
    return cart.reduce((total, item) => total + item.quantity, 0);
}

// ==========================================
// الأنيميشن
// Animations
// ==========================================

// إضافة أنيميشن CSS
const style = document.createElement('style');
style.textContent = `
    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translate(-50%, -20px);
        }
        to {
            opacity: 1;
            transform: translate(-50%, 0);
        }
    }
    
    @keyframes slideUp {
        from {
            opacity: 1;
            transform: translate(-50%, 0);
        }
        to {
            opacity: 0;
            transform: translate(-50%, -20px);
        }
    }
`;
document.head.appendChild(style);

// ==========================================
// معاينة الصورة
// Image Preview
// ==========================================

function previewImage(input) {
    const preview = document.getElementById('image-preview');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function (e) {
            if (preview) {
                preview.src = e.target.result;
                preview.style.display = 'block';
            }
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// ==========================================
// تفاعل القائمة على الموبايل
// Mobile Menu Toggle
// ==========================================

function toggleMobileMenu() {
    const nav = document.getElementById('navbarNav');
    if (nav) {
        nav.classList.toggle('active');
    }
}

// إغلاق القائمة عند الضغط خارجها
document.addEventListener('click', function (e) {
    const nav = document.getElementById('navbarNav');
    const menuBtn = document.querySelector('.mobile-menu-btn');

    if (nav && menuBtn && !nav.contains(e.target) && !menuBtn.contains(e.target)) {
        nav.classList.remove('active');
    }
});

// ==========================================
// Smooth Scroll
// ==========================================

document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        const href = this.getAttribute('href');
        if (href !== '#' && href !== '#!') {
            e.preventDefault();
            const target = document.querySelector(href);
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        }
    });
});

// ==========================================
// تحديث السلة عند فتح الصفحة
// Update cart on page load
// ==========================================

window.addEventListener('load', function () {
    updateCartCount();
    updateCartUI();
});

// إغلاق السلة عند الضغط على overlay
document.addEventListener('click', function (e) {
    if (e.target.classList.contains('cart-overlay')) {
        closeCart();
    }
});

// إغلاق السلة بزر ESC
document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
        closeCart();
    }
});

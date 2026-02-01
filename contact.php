<?php
require_once 'includes/functions.php';

$messageSent = false;
$error = '';

// التحقق من وجود رسالة نجاح في الجلسة (من إعادة التوجيه)
if (isset($_SESSION['contact_message_sent']) && $_SESSION['contact_message_sent'] === true) {
    $messageSent = true;
    unset($_SESSION['contact_message_sent']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = clean($_POST['name']);
    $email = clean($_POST['email']);
    $subject = clean($_POST['subject']);
    $message = clean($_POST['message']);
    
    if (empty($name) || empty($email) || empty($message)) {
        $error = 'الرجاء ملء جميع الحقول المطلوبة';
    } else {
        // الحصول على بريد المتجر من الإعدادات
        $storeEmail = !empty($settings['email']) ? $settings['email'] : ($settings['store_email'] ?? '');
        
        if (empty($storeEmail)) {
            $error = 'عذراً، لم يتم إعداد بريد المتجر الإلكتروني لاستقبال الرسائل.';
        } else {
            // إعداد محتوى الرسالة
            $emailSubject = "رسالة جديدة من موقعك: " . ($subject ?: 'بدون عنوان');
            
            $emailBody = "لقد تلقيت رسالة جديدة من نموذج الاتصال.\n\n";
            $emailBody .= "الاسم: " . $name . "\n";
            $emailBody .= "البريد الإلكتروني: " . $email . "\n";
            $emailBody .= "الموضوع: " . ($subject ?: 'بدون عنوان') . "\n\n";
            $emailBody .= "نص الرسالة:\n" . $message . "\n";
            
            // إعداد الترويسات
            $headers = "From: " . $storeEmail . "\r\n";
            $headers .= "Reply-To: " . $email . "\r\n";
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
            $headers .= "X-Mailer: PHP/" . phpversion();
            
            $sent = false;
            // محاولة الإرسال
            if (mail($storeEmail, $emailSubject, $emailBody, $headers)) {
                $sent = true;
            } else {
                // في البيئة المحلية (Localhost)
                if ($_SERVER['SERVER_NAME'] === 'localhost' || $_SERVER['SERVER_ADDR'] === '127.0.0.1' || $_SERVER['SERVER_ADDR'] === '::1') {
                    $sent = true; // Fake it for localhost
                    error_log("Main didn't send on localhost. Details: $emailBody");
                } else {
                    $error = 'عذراً، حدث خطأ أثناء إرسال الرسالة. يرجى المحاولة لاحقاً.';
                }
            }

            if ($sent) {
                $_SESSION['contact_message_sent'] = true;
                header("Location: " . $_SERVER['PHP_SELF']);
                exit;
            }
        }
    }
}

$pageTitle = 'اتصل بنا';
include 'includes/header.php';
?>

<div class="contact-container">
    <div class="contact-header">
        <h1>تواصل معنا</h1>
        <p>
            نحن هنا للإجابة على استفساراتكم ومساعدتكم في أي وقت. لا تتردد في الاتصال بنا.
        </p>
    </div>

    <div class="contact-grid">
        <!-- Contact Info -->
        <div class="contact-card contact-info-card">
            <div class="contact-logo">
                <img src="assets/images/logo.jpg" alt="<?php echo clean((!empty($settings['site_name']) ? $settings['site_name'] : ($settings['store_name'] ?? 'متجر التقنية'))); ?>">
                <h3><?php echo clean((!empty($settings['site_name']) ? $settings['site_name'] : ($settings['store_name'] ?? 'متجر التقنية'))); ?></h3>
            </div>

            <?php 
            $phone = !empty($settings['phone']) ? $settings['phone'] : ($settings['store_phone'] ?? '');
            if (!empty($phone)): 
            ?>
            <div class="contact-item">
                <div class="contact-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                    </svg>
                </div>
                <div class="contact-details">
                    <h6>الهاتف</h6>
                    <p dir="ltr"><?php echo htmlspecialchars($phone); ?></p>
                </div>
            </div>
            <?php endif; ?>
            
            <?php 
            $email = !empty($settings['email']) ? $settings['email'] : ($settings['store_email'] ?? '');
            if (!empty($email)): 
            ?>
            <div class="contact-item">
                <div class="contact-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                        <polyline points="22,6 12,13 2,6"></polyline>
                    </svg>
                </div>
                <div class="contact-details">
                    <h6>البريد الإلكتروني</h6>
                    <p><?php echo htmlspecialchars($email); ?></p>
                </div>
            </div>
            <?php endif; ?>
            
            <?php 
            $address = !empty($settings['address']) ? $settings['address'] : ($settings['store_address'] ?? '');
            if (!empty($address)): 
            ?>
            <div class="contact-item">
                <div class="contact-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                        <circle cx="12" cy="10" r="3"></circle>
                    </svg>
                </div>
                <div class="contact-details">
                    <h6>العنوان</h6>
                    <p><?php echo htmlspecialchars($address); ?></p>
                </div>
            </div>
            <?php endif; ?>

            <div class="social-links-section">
                <h5>تابعنا على وسائل التواصل</h5>
                <div class="social-links-grid">
                    <?php if (!empty($settings['facebook'])): ?>
                        <a href="<?php echo clean($settings['facebook']); ?>" class="social-btn">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"></path></svg>
                            Facebook
                        </a>
                    <?php endif; ?>
                    <?php if (!empty($settings['twitter'])): ?>
                        <a href="<?php echo clean($settings['twitter']); ?>" class="social-btn">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 3a10.9 10.9 0 0 1-3.14 1.53 4.48 4.48 0 0 0-7.86 3v1A10.66 10.66 0 0 1 3 4s-4 9 5 13a11.64 11.64 0 0 1-7 2c9 5 20 0 20-11.5a4.5 4.5 0 0 0-.08-.83A7.72 7.72 0 0 0 23 3z"></path></svg>
                            Twitter
                        </a>
                    <?php endif; ?>
                    <?php if (!empty($settings['instagram'])): ?>
                        <a href="<?php echo clean($settings['instagram']); ?>" class="social-btn">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line></svg>
                            Instagram
                        </a>
                    <?php endif; ?>
                    <?php if (!empty($settings['youtube'])): ?>
                        <a href="<?php echo clean($settings['youtube']); ?>" class="social-btn">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22.54 6.42a2.78 2.78 0 0 0-1.94-2C18.88 4 12 4 12 4s-6.88 0-8.6.46a2.78 2.78 0 0 0-1.94 2A29 29 0 0 0 1 11.75a29 29 0 0 0 .46 5.33A2.78 2.78 0 0 0 3.4 19c1.72.46 8.6.46 8.6.46s6.88 0 8.6-.46a2.78 2.78 0 0 0 1.94-2 29 29 0 0 0 .46-5.33 29 29 0 0 0-.46-5.33z"></path><polygon points="9.75 15.02 15.5 11.75 9.75 8.48 9.75 15.02"></polygon></svg>
                            YouTube
                        </a>
                    <?php endif; ?>
                    <?php if (!empty($settings['whatsapp'])): 
                        $wa_url = $settings['whatsapp'];
                        if (!filter_var($wa_url, FILTER_VALIDATE_URL)) {
                            $wa_number = preg_replace('/[^0-9]/', '', $wa_url);
                            $wa_url = "https://wa.me/$wa_number";
                        }
                    ?>
                        <a href="<?php echo clean($wa_url); ?>" target="_blank" class="social-btn">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path></svg>
                            WhatsApp
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Contact Form -->
    <div class="contact-card">
        <h2 style="margin-bottom: 1rem; color: var(--primary);">أرسل لنا رسالة</h2>
        <p style="margin-bottom: 2rem; color: var(--gray-600);">يسعدنا سماع رأيك أو الإجابة على استفساراتك.</p>
        
        <?php if ($messageSent): ?>
            <div class="alert alert-success" style="padding: 1rem; border-radius: var(--radius); margin-bottom: 2rem; display: flex; align-items: center; gap: 0.5rem;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                <div style="font-weight: bold;">
                    تم إرسال رسالتك بنجاح! سنتواصل معك قريباً.
                </div>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger" style="padding: 1rem; border-radius: var(--radius); margin-bottom: 2rem;">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="contact-grid" style="grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 0;">
                <div class="contact-form-group">
                    <label for="name" class="contact-form-label">الاسم</label>
                    <input type="text" class="contact-form-input" id="name" name="name" required placeholder="الاسم الكامل">
                </div>
                <div class="contact-form-group">
                    <label for="email" class="contact-form-label">البريد الإلكتروني</label>
                    <input type="email" class="contact-form-input" id="email" name="email" required placeholder="name@example.com">
                </div>
            </div>
            
            <div class="contact-form-group">
                <label for="subject" class="contact-form-label">الموضوع</label>
                <input type="text" class="contact-form-input" id="subject" name="subject" placeholder="موضوع الرسالة">
            </div>
            
            <div class="contact-form-group">
                <label for="message" class="contact-form-label">الرسالة</label>
                <textarea class="contact-form-textarea" id="message" name="message" required placeholder="اكتب رسالتك هنا..."></textarea>
            </div>
            
            <button type="submit" class="btn btn-primary btn-lg" style="width: 100%;">إرسال الرسالة</button>
        </form>
    </div>
</div>
</div>

<?php include 'includes/footer.php'; ?>

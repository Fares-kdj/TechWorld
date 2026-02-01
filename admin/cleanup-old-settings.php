<?php
/**
 * سكريبت لحذف الإعدادات القديمة والمكررة
 */

require_once '../config/database.php';

echo "<h2>تنظيف الإعدادات القديمة</h2>";
echo "<hr>";

global $pdo;

try {
    // حذف الإعدادات القديمة
    $oldSettings = ['store_name', 'store_email', 'store_phone', 'store_address'];
    
    $stmt = $pdo->prepare("DELETE FROM settings WHERE setting_key = ?");
    
    echo "<h3>الإعدادات التي تم حذفها:</h3>";
    echo "<ul>";
    
    foreach ($oldSettings as $key) {
        $stmt->execute([$key]);
        $affected = $stmt->rowCount();
        if ($affected > 0) {
            echo "<li style='color: green;'>✓ تم حذف: <strong>" . htmlspecialchars($key) . "</strong> ($affected سجل)</li>";
        } else {
            echo "<li style='color: gray;'>- لم يتم العثور على: <strong>" . htmlspecialchars($key) . "</strong></li>";
        }
    }
    
    echo "</ul>";
    echo "<hr>";
    
    // عرض الإعدادات المتبقية
    echo "<h3>الإعدادات المتبقية في قاعدة البيانات:</h3>";
    $stmt = $pdo->query("SELECT * FROM settings ORDER BY id");
    $settings = $stmt->fetchAll();
    
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
    echo "<tr style='background: #f0f0f0;'><th>ID</th><th>Setting Key</th><th>Setting Value</th></tr>";
    
    foreach ($settings as $setting) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($setting['id']) . "</td>";
        echo "<td><strong>" . htmlspecialchars($setting['setting_key']) . "</strong></td>";
        echo "<td>" . htmlspecialchars($setting['setting_value']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<hr>";
    echo "<p style='color: green; font-weight: bold;'>✅ تم تنظيف قاعدة البيانات بنجاح!</p>";
    echo "<p><a href='login.php' style='padding: 10px 20px; background: #667eea; color: white; text-decoration: none; border-radius: 5px;'>جرب صفحة تسجيل الدخول الآن</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red; font-weight: bold;'>خطأ: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>

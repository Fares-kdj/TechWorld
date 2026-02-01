<?php
require_once '../config/database.php';

echo "<h2>فحص جدول settings</h2>";
echo "<hr>";

global $pdo;

try {
    // عرض جميع الإعدادات
    $stmt = $pdo->query("SELECT * FROM settings");
    $settings = $stmt->fetchAll();
    
    echo "<h3>جميع الإعدادات في قاعدة البيانات:</h3>";
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
    echo "<tr style='background: #f0f0f0;'><th>ID</th><th>Setting Key</th><th>Setting Value</th></tr>";
    
    if (empty($settings)) {
        echo "<tr><td colspan='3' style='text-align: center; color: red;'>لا توجد إعدادات في الجدول!</td></tr>";
    } else {
        foreach ($settings as $setting) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($setting['id'] ?? 'N/A') . "</td>";
            echo "<td><strong>" . htmlspecialchars($setting['setting_key'] ?? $setting['key'] ?? 'N/A') . "</strong></td>";
            echo "<td>" . htmlspecialchars($setting['setting_value'] ?? $setting['value'] ?? 'N/A') . "</td>";
            echo "</tr>";
        }
    }
    echo "</table>";
    
    echo "<hr>";
    echo "<h3>اختبار تحميل إعدادات محددة:</h3>";
    
    // اختبار تحميل store_name
    $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    $stmt->execute(['store_name']);
    $result = $stmt->fetch();
    echo "<p><strong>store_name:</strong> " . ($result ? htmlspecialchars($result['setting_value']) : '<span style="color: red;">لا يوجد</span>') . "</p>";
    
    // اختبار تحميل store_logo
    $stmt->execute(['store_logo']);
    $result = $stmt->fetch();
    echo "<p><strong>store_logo:</strong> " . ($result ? htmlspecialchars($result['setting_value']) : '<span style="color: red;">لا يوجد</span>') . "</p>";
    
    echo "<hr>";
    echo "<h3>هيكل جدول settings:</h3>";
    $stmt = $pdo->query("DESCRIBE settings");
    $columns = $stmt->fetchAll();
    
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
    echo "<tr style='background: #f0f0f0;'><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td><strong>" . htmlspecialchars($col['Field']) . "</strong></td>";
        echo "<td>" . htmlspecialchars($col['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Key']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "<p style='color: red; font-weight: bold;'>خطأ: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>

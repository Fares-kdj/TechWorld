<?php
/**
 * سكريبت تلقائي لإضافة عمود رمز الأمان
 * Auto-Setup: Security Code Column
 */

require_once '../config/database.php';

global $pdo;

try {
    // التحقق من وجود العمود
    $checkColumn = $pdo->query("SHOW COLUMNS FROM admins LIKE 'security_code'");
    
    if ($checkColumn->rowCount() == 0) {
        // العمود غير موجود، سنضيفه
        echo "جاري إضافة عمود رمز الأمان...<br>";
        
        $sql = "ALTER TABLE admins 
                ADD COLUMN security_code VARCHAR(50) DEFAULT '2026' 
                COMMENT 'رمز الأمان لاستعادة كلمة المرور'";
        
        $pdo->exec($sql);
        
        echo "<div style='background: #d1fae5; border: 2px solid #28a745; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
        echo "<h3 style='color: #28a745;'>✓ تم بنجاح!</h3>";
        echo "<p>تم إضافة عمود <code>security_code</code> إلى جدول admins</p>";
        echo "<p>الرمز الافتراضي لجميع المستخدمين: <strong>2026</strong></p>";
        echo "</div>";
        
        echo "<a href='security-settings.php' style='background: #667eea; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; display: inline-block;'>الذهاب إلى إعدادات الأمان</a>";
        
    } else {
        echo "<div style='background: #fff3cd; border: 2px solid #ffc107; padding: 20px; border-radius: 10px;'>";
        echo "<h3 style='color: #856404;'>⚠ تحذير</h3>";
        echo "<p>عمود <code>security_code</code> موجود بالفعل!</p>";
        echo "<p>لا حاجة لتشغيل هذا السكريبت مرة أخرى.</p>";
        echo "</div>";
        
        echo "<a href='security-settings.php' style='background: #667eea; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; display: inline-block; margin-top: 20px;'>الذهاب إلى إعدادات الأمان</a>";
    }
    
} catch (PDOException $e) {
    echo "<div style='background: #f8d7da; border: 2px solid #dc3545; padding: 20px; border-radius: 10px;'>";
    echo "<h3 style='color: #721c24;'>✗ خطأ</h3>";
    echo "<p>حدث خطأ أثناء إضافة العمود:</p>";
    echo "<code>" . htmlspecialchars($e->getMessage()) . "</code>";
    echo "</div>";
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إعداد رمز الأمان</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 50px 20px;
        }
        .setup-card {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
    </style>
</head>
<body>
    <div class="setup-card">
        <h2 class="text-center mb-4">⚙️ إعداد رمز الأمان</h2>
        <hr>
        <?php
        // المحتوى يتم عرضه أعلاه
        ?>
    </div>
</body>
</html>

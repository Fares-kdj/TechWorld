<?php
require_once 'config/database.php';

header("Content-Type: application/xml; charset=utf-8");

echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <!-- Static Pages -->
    <url>
        <loc><?php echo SITE_URL; ?>/</loc>
        <changefreq>daily</changefreq>
        <priority>1.0</priority>
    </url>
    <url>
        <loc><?php echo SITE_URL; ?>/products</loc>
        <changefreq>daily</changefreq>
        <priority>0.8</priority>
    </url>
    <url>
        <loc><?php echo SITE_URL; ?>/contact</loc>
        <changefreq>monthly</changefreq>
        <priority>0.5</priority>
    </url>

    <!-- Dynamic Products -->
    <?php
    try {
        $pdo = getDB();
        $stmt = $pdo->query("SELECT id, updated_at FROM products ORDER BY id DESC");
        
        while ($row = $stmt->fetch()) {
            $url = SITE_URL . '/product?id=' . $row['id'];
            $date = date('Y-m-d', strtotime($row['updated_at'] ?? 'now'));
            ?>
    <url>
        <loc><?php echo $url; ?></loc>
        <lastmod><?php echo $date; ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.8</priority>
    </url>
            <?php
        }
    } catch (PDOException $e) {
        // Fail silently or log error
    }
    ?>
</urlset>

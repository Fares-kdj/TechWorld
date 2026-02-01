<?php
require_once '../config/database.php';
header('Content-Type: application/json');

$type = $_GET['type'] ?? '';
$conn = getDB();

try {
    if ($type === 'wilayas') {
        $stmt = $conn->query("SELECT id, name_ar, name_en FROM wilayas ORDER BY id ASC");
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    } elseif ($type === 'dairas' && isset($_GET['wilaya_id'])) {
        $stmt = $conn->prepare("SELECT id, name_ar, name_en FROM dairas WHERE wilaya_id = ? ORDER BY name_ar ASC");
        $stmt->execute([$_GET['wilaya_id']]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    } elseif ($type === 'communes' && isset($_GET['daira_id'])) {
        $stmt = $conn->prepare("SELECT id, name_ar, name_en FROM communes WHERE daira_id = ? ORDER BY name_ar ASC");
        $stmt->execute([$_GET['daira_id']]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    } else {
        echo json_encode([]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

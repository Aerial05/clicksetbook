<?php
require_once '../../includes/auth.php';
requireLogin();
requireRole(['admin']);

header('Content-Type: application/json');

$pdo = getDBConnection();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'getAll':
            // Get all services with pagination
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $limit = 20;
            $offset = ($page - 1) * $limit;
            $category = $_GET['category'] ?? '';
            $search = $_GET['search'] ?? '';
            
            $whereClause = ' WHERE 1=1';
            $params = [];
            
            if ($category && $category !== 'all') {
                $whereClause .= ' AND s.category = ?';
                $params[] = $category;
            }
            
            if ($search) {
                $whereClause .= ' AND (s.name LIKE ? OR s.description LIKE ?)';
                $searchTerm = "%$search%";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            // Get total count
            $countStmt = $pdo->prepare("SELECT COUNT(*) FROM services s" . $whereClause);
            $countStmt->execute($params);
            $total = $countStmt->fetchColumn();
            
            // Get services
            $sql = "
                SELECT 
                    s.id,
                    s.name,
                    s.description,
                    s.category,
                    s.duration_minutes,
                    s.base_cost,
                    s.requires_doctor,
                    s.preparation_instructions,
                    s.is_active,
                    s.created_at,
                    s.updated_at,
                    COUNT(DISTINCT a.id) as total_bookings
                FROM services s
                LEFT JOIN appointments a ON s.id = a.service_id
                " . $whereClause . "
                GROUP BY s.id
                ORDER BY s.created_at DESC
                LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'services' => $services,
                'pagination' => [
                    'total' => $total,
                    'page' => $page,
                    'limit' => $limit,
                    'pages' => ceil($total / $limit)
                ]
            ]);
            break;
            
        case 'create':
            $name = $_POST['name'] ?? '';
            $description = $_POST['description'] ?? '';
            $category = $_POST['category'] ?? '';
            $durationMinutes = isset($_POST['duration_minutes']) ? (int)$_POST['duration_minutes'] : 30;
            $baseCost = isset($_POST['base_cost']) ? (float)$_POST['base_cost'] : 0;
            $requiresDoctor = isset($_POST['requires_doctor']) ? (int)$_POST['requires_doctor'] : 0;
            $preparationInstructions = $_POST['preparation_instructions'] ?? null;
            $isActive = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;
            
            $validCategories = ['consultation', 'laboratory', 'radiology', 'physiotherapy', 'surgery', 'emergency'];
            if (!in_array($category, $validCategories)) {
                throw new Exception('Invalid category');
            }
            
            // Check if service name already exists
            $checkStmt = $pdo->prepare("SELECT id FROM services WHERE name = ?");
            $checkStmt->execute([$name]);
            if ($checkStmt->fetch()) {
                throw new Exception('Service name already exists');
            }
            
            // Create service
            $stmt = $pdo->prepare("
                INSERT INTO services (name, description, category, duration_minutes, base_cost, requires_doctor, preparation_instructions, is_active, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");
            
            $stmt->execute([$name, $description, $category, $durationMinutes, $baseCost, $requiresDoctor, $preparationInstructions, $isActive]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Service created successfully',
                'service_id' => $pdo->lastInsertId()
            ]);
            break;
            
        case 'update':
            $serviceId = $_POST['id'] ?? 0;
            $name = $_POST['name'] ?? '';
            $description = $_POST['description'] ?? '';
            $category = $_POST['category'] ?? '';
            $durationMinutes = isset($_POST['duration_minutes']) ? (int)$_POST['duration_minutes'] : 30;
            $baseCost = isset($_POST['base_cost']) ? (float)$_POST['base_cost'] : 0;
            $requiresDoctor = isset($_POST['requires_doctor']) ? (int)$_POST['requires_doctor'] : 0;
            $preparationInstructions = $_POST['preparation_instructions'] ?? null;
            $isActive = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;
            
            $validCategories = ['consultation', 'laboratory', 'radiology', 'physiotherapy', 'surgery', 'emergency'];
            if (!in_array($category, $validCategories)) {
                throw new Exception('Invalid category');
            }
            
            // Check if service name exists for another service
            $checkStmt = $pdo->prepare("SELECT id FROM services WHERE name = ? AND id != ?");
            $checkStmt->execute([$name, $serviceId]);
            if ($checkStmt->fetch()) {
                throw new Exception('Service name already exists');
            }
            
            // Update service
            $stmt = $pdo->prepare("
                UPDATE services 
                SET name = ?,
                    description = ?,
                    category = ?,
                    duration_minutes = ?,
                    base_cost = ?,
                    requires_doctor = ?,
                    preparation_instructions = ?,
                    is_active = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");
            
            $stmt->execute([$name, $description, $category, $durationMinutes, $baseCost, $requiresDoctor, $preparationInstructions, $isActive, $serviceId]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Service updated successfully'
            ]);
            break;
            
        case 'delete':
            $serviceId = $_POST['id'] ?? 0;
            
            // Check if service has appointments
            $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE service_id = ?");
            $checkStmt->execute([$serviceId]);
            $appointmentCount = $checkStmt->fetchColumn();
            
            if ($appointmentCount > 0) {
                throw new Exception('Cannot delete service with existing appointments. Consider deactivating it instead.');
            }
            
            // Delete service
            $stmt = $pdo->prepare("DELETE FROM services WHERE id = ?");
            $stmt->execute([$serviceId]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Service deleted successfully'
            ]);
            break;
            
        case 'toggleActive':
            $serviceId = $_POST['id'] ?? 0;
            
            // Toggle active status
            $stmt = $pdo->prepare("
                UPDATE services 
                SET is_active = NOT is_active,
                    updated_at = NOW()
                WHERE id = ?
            ");
            
            $stmt->execute([$serviceId]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Service status updated successfully'
            ]);
            break;
            
        case 'getStats':
            // Get service statistics
            $stmt = $pdo->query("
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active,
                    COUNT(DISTINCT category) as categories,
                    SUM(CASE WHEN requires_doctor = 1 THEN 1 ELSE 0 END) as requires_doctor
                FROM services
            ");
            
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Get category breakdown
            $categoryStmt = $pdo->query("
                SELECT category, COUNT(*) as count
                FROM services
                GROUP BY category
            ");
            
            $stats['by_category'] = $categoryStmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'stats' => $stats
            ]);
            break;
            
        default:
            echo json_encode([
                'success' => false,
                'message' => 'Invalid action'
            ]);
    }
} catch (Exception $e) {
    error_log("Admin services API error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

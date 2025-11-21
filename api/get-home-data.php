<?php
/**
 * API endpoints for the Click Set Book mobile app
 */

require_once '../includes/auth.php';

// Set JSON header
header('Content-Type: application/json');

// Require authentication
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$pdo = getDBConnection();
$currentUser = getCurrentUser();
$userId = $currentUser['id'];

// Route handling
$endpoint = $_GET['endpoint'] ?? 'home-data'; // Default to home-data for backward compatibility

switch ($endpoint) {
    case 'home-data':
        getHomeData($pdo);
        break;
    case 'doctor-details':
        getDoctorDetails($pdo, $_GET['id'] ?? 0);
        break;
    case 'service-details':
        getServiceDetails($pdo, $_GET['id'] ?? 0);
        break;
    case 'available-slots':
        $doctorId = $_GET['doctor_id'] ?? 0;
        $serviceId = $_GET['service_id'] ?? 0;
        $date = $_GET['date'] ?? '';
        getAvailableSlots($pdo, $doctorId, $serviceId, $date);
        break;
    case 'my-bookings':
        getMyBookings($pdo, $userId, $_GET['status'] ?? 'all');
        break;
    case 'notifications':
        getNotifications($pdo, $userId);
        break;
    case 'mark-read':
        markNotificationRead($pdo, $userId, $_GET['id'] ?? 0);
        break;
    default:
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Endpoint not found']);
}

/**
 * Get home page data (doctors and services)
 */
function getHomeData($pdo) {
    global $userId;
    $tab = $_GET['tab'] ?? 'services';
    $search = $_GET['search'] ?? '';
    $sort = $_GET['sort'] ?? 'default';
    
    try {
        if ($tab === 'doctors' || $tab === 'all') {
            // Get doctors - handle NULL user_id gracefully
            $query = "SELECT d.*, 
                             COALESCE(CONCAT(u.first_name, ' ', u.last_name), CONCAT('Dr. ', d.specialty)) as name,
                             u.email,
                             (SELECT COUNT(*) FROM appointments WHERE doctor_id = d.id AND status = 'completed') as patient_count,
                             (SELECT AVG(rating) FROM reviews WHERE doctor_id = d.id) as rating
                      FROM doctors d
                      LEFT JOIN users u ON d.user_id = u.id
                      WHERE d.is_available = 1";
            
            if ($search) {
                $query .= " AND (COALESCE(CONCAT(u.first_name, ' ', u.last_name), d.specialty) LIKE :search 
                           OR d.specialty LIKE :search 
                           OR d.department LIKE :search)";
            }
            
            switch ($sort) {
                case 'name_asc':
                    $query .= " ORDER BY name ASC";
                    break;
                case 'name_desc':
                    $query .= " ORDER BY name DESC";
                    break;
                case 'price_asc':
                    $query .= " ORDER BY d.consultation_fee ASC";
                    break;
                case 'price_desc':
                    $query .= " ORDER BY d.consultation_fee DESC";
                    break;
                default:
                    $query .= " ORDER BY d.created_at DESC";
            }
            
            $stmt = $pdo->prepare($query);
            if ($search) {
                $stmt->bindValue(':search', "%$search%");
            }
            $stmt->execute();
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'items' => $items,
                'type' => 'doctor'
            ]);
            
        } else {
            // Get services
            $query = "SELECT s.*
                      FROM services s
                      WHERE s.is_active = 1";
            
            if ($tab !== 'all' && $tab !== 'services') {
                $category = match($tab) {
                    'laboratory' => 'laboratory',
                    'imaging' => 'radiology',
                    'vaccines' => 'consultation',
                    default => ''
                };
                if ($category) {
                    $query .= " AND s.category = :category";
                }
            }
            
            if ($search) {
                $query .= " AND (s.name LIKE :search OR s.description LIKE :search)";
            }
            
            switch ($sort) {
                case 'name_asc':
                    $query .= " ORDER BY s.name ASC";
                    break;
                case 'name_desc':
                    $query .= " ORDER BY s.name DESC";
                    break;
                case 'price_asc':
                    $query .= " ORDER BY s.base_cost ASC";
                    break;
                case 'price_desc':
                    $query .= " ORDER BY s.base_cost DESC";
                    break;
                default:
                    $query .= " ORDER BY s.created_at DESC";
            }
            
            $stmt = $pdo->prepare($query);
            if (isset($category)) {
                $stmt->bindValue(':category', $category);
            }
            if ($search) {
                $stmt->bindValue(':search', "%$search%");
            }
            $stmt->execute();
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'items' => $items,
                'type' => 'service'
            ]);
        }
    } catch (PDOException $e) {
        error_log("Error getting home data: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error loading data']);
    }
}

/**
 * Get doctor details
 */
function getDoctorDetails($pdo, $doctorId) {
    try {
        $stmt = $pdo->prepare("
            SELECT d.*, 
                   CONCAT(u.first_name, ' ', u.last_name) as name,
                   u.email,
                   (SELECT COUNT(*) FROM appointments WHERE doctor_id = d.id AND status = 'completed') as patient_count,
                   (SELECT AVG(rating) FROM reviews WHERE doctor_id = d.id) as avg_rating,
                   (SELECT COUNT(*) FROM reviews WHERE doctor_id = d.id) as review_count
            FROM doctors d
            LEFT JOIN users u ON d.user_id = u.id
            WHERE d.id = ?
        ");
        $stmt->execute([$doctorId]);
        $doctor = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$doctor) {
            echo json_encode(['success' => false, 'message' => 'Doctor not found']);
            return;
        }
        
        // Get reviews
        $stmt = $pdo->prepare("
            SELECT r.*, CONCAT(u.first_name, ' ', u.last_name) as patient_name
            FROM reviews r
            LEFT JOIN users u ON r.patient_id = u.id
            WHERE r.doctor_id = ?
            ORDER BY r.created_at DESC
            LIMIT 10
        ");
        $stmt->execute([$doctorId]);
        $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'doctor' => $doctor,
            'reviews' => $reviews
        ]);
        
    } catch (PDOException $e) {
        error_log("Error getting doctor details: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error loading doctor']);
    }
}

/**
 * Get service details
 */
function getServiceDetails($pdo, $serviceId) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM services WHERE id = ?");
        $stmt->execute([$serviceId]);
        $service = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$service) {
            echo json_encode(['success' => false, 'message' => 'Service not found']);
            return;
        }
        
        echo json_encode([
            'success' => true,
            'service' => $service
        ]);
        
    } catch (PDOException $e) {
        error_log("Error getting service details: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error loading service']);
    }
}

/**
 * Get available time slots for a doctor or service on a specific date
 */
function getAvailableSlots($pdo, $doctorId, $serviceId, $date) {
    try {
        // Handle doctor bookings
        if ($doctorId > 0) {
            // Get doctor's schedule for the date
            $stmt = $pdo->prepare("
                SELECT * FROM doctor_schedules 
                WHERE doctor_id = ? AND date = ? AND is_available = 1
            ");
            $stmt->execute([$doctorId, $date]);
            $schedule = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$schedule) {
                // Return default slots if no specific schedule
                $slots = generateDefaultSlots();
            } else {
                $slots = generateSlots($schedule['start_time'], $schedule['end_time'], $schedule['slot_duration']);
            }
            
            // Get booked slots for this doctor
            $stmt = $pdo->prepare("
                SELECT appointment_time FROM appointments 
                WHERE doctor_id = ? AND appointment_date = ? 
                AND status NOT IN ('cancelled', 'no_show')
            ");
            $stmt->execute([$doctorId, $date]);
            $bookedTimes = $stmt->fetchAll(PDO::FETCH_COLUMN);
        } 
        // Handle service bookings (no specific doctor)
        else if ($serviceId > 0) {
            // Get service details to determine slot duration
            $stmt = $pdo->prepare("SELECT duration_minutes FROM services WHERE id = ?");
            $stmt->execute([$serviceId]);
            $service = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Generate slots based on service duration
            $slots = generateDefaultSlots($service['duration_minutes'] ?? 30);
            
            // Get booked slots for this service (regardless of doctor)
            $stmt = $pdo->prepare("
                SELECT appointment_time FROM appointments 
                WHERE service_id = ? AND appointment_date = ? 
                AND status NOT IN ('cancelled', 'no_show')
            ");
            $stmt->execute([$serviceId, $date]);
            $bookedTimes = $stmt->fetchAll(PDO::FETCH_COLUMN);
        } else {
            echo json_encode(['success' => false, 'message' => 'No doctor or service specified']);
            return;
        }
        
        // Mark booked slots as unavailable
        foreach ($slots as &$slot) {
            $slot['available'] = !in_array($slot['time'], $bookedTimes);
        }
        
        echo json_encode([
            'success' => true,
            'slots' => $slots
        ]);
        
    } catch (PDOException $e) {
        error_log("Error getting available slots: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error loading slots', 'error' => $e->getMessage()]);
    }
}

function generateDefaultSlots($duration = 30) {
    $slots = [];
    $times = ['09:00', '09:30', '10:00', '10:30', '11:00', '11:30', 
              '14:00', '14:30', '15:00', '15:30', '16:00', '16:30', '17:00', '17:30'];
    
    foreach ($times as $time) {
        $slots[] = [
            'time' => $time,
            'display' => date('h:i A', strtotime($time)),
            'available' => true
        ];
    }
    
    return $slots;
}

function generateSlots($startTime, $endTime, $duration) {
    $slots = [];
    $current = strtotime($startTime);
    $end = strtotime($endTime);
    
    while ($current < $end) {
        $time = date('H:i', $current);
        $slots[] = [
            'time' => $time,
            'display' => date('h:i A', $current),
            'available' => true
        ];
        $current = strtotime("+$duration minutes", $current);
    }
    
    return $slots;
}

/**
 * Get user's bookings
 */
function getMyBookings($pdo, $userId, $status) {
    try {
        $query = "
            SELECT a.*, 
                   s.name as service_name, 
                   s.category as service_category,
                   CONCAT(u.first_name, ' ', u.last_name) as doctor_name,
                   d.specialty as doctor_specialty,
                   d.profile_image as doctor_image
            FROM appointments a
            LEFT JOIN services s ON a.service_id = s.id
            LEFT JOIN doctors d ON a.doctor_id = d.id
            LEFT JOIN users u ON d.user_id = u.id
            WHERE a.patient_id = ?
        ";
        
        if ($status !== 'all') {
            $query .= " AND a.status = :status";
        }
        
        $query .= " ORDER BY a.appointment_date DESC, a.appointment_time DESC";
        
        $stmt = $pdo->prepare($query);
        $stmt->bindValue(':patient_id', $userId, PDO::PARAM_INT);
        if ($status !== 'all') {
            $stmt->bindValue(':status', $status);
        }
        $stmt->execute([$userId]);
        $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'bookings' => $bookings
        ]);
        
    } catch (PDOException $e) {
        error_log("Error getting bookings: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error loading bookings']);
    }
}

/**
 * Get user's notifications
 */
function getNotifications($pdo, $userId) {
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM notifications 
            WHERE user_id = ? 
            ORDER BY created_at DESC 
            LIMIT 50
        ");
        $stmt->execute([$userId]);
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'notifications' => $notifications
        ]);
        
    } catch (PDOException $e) {
        error_log("Error getting notifications: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error loading notifications']);
    }
}

/**
 * Mark notification as read
 */
function markNotificationRead($pdo, $userId, $notificationId) {
    try {
        $stmt = $pdo->prepare("
            UPDATE notifications 
            SET is_read = 1 
            WHERE id = ? AND user_id = ?
        ");
        $stmt->execute([$notificationId, $userId]);
        
        echo json_encode(['success' => true]);
        
    } catch (PDOException $e) {
        error_log("Error marking notification as read: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error updating notification']);
    }
}

<?php 
require_once 'includes/auth.php';

// Require login to access
requireLogin();
requireProfileComplete();

$currentUser = getCurrentUser();
$pdo = getDBConnection();

// Get doctor ID
$doctorId = $_GET['id'] ?? 0;

// Get doctor details
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
    header('Location: dashboard.php');
    exit();
}

// Get reviews
$stmt = $pdo->prepare("
    SELECT r.*, 
           CONCAT(u.first_name, ' ', SUBSTRING(u.last_name, 1, 1), '.') as patient_name,
           r.created_at
    FROM reviews r
    LEFT JOIN users u ON r.patient_id = u.id
    WHERE r.doctor_id = ?
    ORDER BY r.created_at DESC
    LIMIT 10
");
$stmt->execute([$doctorId]);
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($doctor['name']); ?> - Click Set Book</title>
    <link rel="stylesheet" href="app-styles.css">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <!-- Header with Back Button -->
        <div style="display: flex; align-items: center; gap: 16px; margin-bottom: 24px;">
            <button onclick="history.back()" style="background: var(--bg-tertiary); border: none; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer;">
                <svg style="width: 20px; height: 20px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M19 12H5M12 19l-7-7 7-7"/>
                </svg>
            </button>
            <h1 style="font-size: 20px; font-weight: 700; flex: 1;">Doctor Details</h1>
        </div>

        <!-- Doctor Profile Card -->
        <div class="card">
            <div style="text-align: center; padding: 20px;">
                <img src="<?php echo $doctor['profile_image'] ?? 'https://via.placeholder.com/120'; ?>" 
                     alt="<?php echo htmlspecialchars($doctor['name']); ?>" 
                     style="width: 120px; height: 120px; border-radius: 50%; object-fit: cover; margin-bottom: 16px;">
                <h2 style="font-size: 24px; font-weight: 700; margin-bottom: 4px;">Dr. <?php echo htmlspecialchars($doctor['name']); ?></h2>
                <p style="font-size: 16px; color: var(--text-secondary); margin-bottom: 8px;"><?php echo htmlspecialchars($doctor['specialty']); ?></p>
                <p style="font-size: 14px; color: var(--text-light);">
                    <svg style="width: 16px; height: 16px; display: inline; vertical-align: middle;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                        <circle cx="12" cy="10" r="3"></circle>
                    </svg>
                    <?php echo htmlspecialchars($doctor['department'] ?? 'Golden Cardiology Center'); ?>
                </p>
            </div>


        </div>

        <!-- About Me -->
        <div class="card">
            <h3 style="font-size: 18px; font-weight: 700; margin-bottom: 12px;">About me</h3>
            <p style="font-size: 14px; color: var(--text-secondary); line-height: 1.6;">
                <?php echo nl2br(htmlspecialchars($doctor['bio'] ?? 'Dr. ' . $doctor['name'] . ', a dedicated ' . $doctor['specialty'] . ', brings a wealth of experience to Golden Cardiology Center in Ilang Ilang St., Malolos. With expertise in comprehensive patient care, Dr. ' . $doctor['name'] . ' is committed to providing exceptional healthcare services.')); ?>
            </p>
            <?php if ($doctor['qualification']): ?>
            <div style="margin-top: 16px;">
                <h4 style="font-size: 14px; font-weight: 600; margin-bottom: 8px;">Qualifications</h4>
                <p style="font-size: 14px; color: var(--text-secondary);"><?php echo htmlspecialchars($doctor['qualification']); ?></p>
            </div>
            <?php endif; ?>
        </div>

        <!-- Working Time -->
        <div class="card">
            <h3 style="font-size: 18px; font-weight: 700; margin-bottom: 12px;">Working Time</h3>
            <p style="font-size: 14px; color: var(--text-secondary);">Monday - Friday, 08:00 AM - 18:00 PM</p>
        </div>

        <!-- Reviews -->
        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                <h3 style="font-size: 18px; font-weight: 700;">Reviews</h3>
                <?php if (count($reviews) > 0): ?>
                <a href="#" style="font-size: 14px; color: var(--primary-color); text-decoration: none;">See All</a>
                <?php endif; ?>
            </div>
            
            <?php if (count($reviews) > 0): ?>
                <?php foreach ($reviews as $review): ?>
                <div style="padding: 16px 0; border-bottom: 1px solid var(--border-color);">
                    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 8px;">
                        <div style="width: 40px; height: 40px; background: var(--bg-tertiary); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600;">
                            <?php echo strtoupper(substr($review['patient_name'], 0, 1)); ?>
                        </div>
                        <div style="flex: 1;">
                            <div style="font-size: 14px; font-weight: 600;"><?php echo htmlspecialchars($review['patient_name']); ?></div>
                            <div style="display: flex; gap: 2px; margin-top: 4px;">
                                <?php for ($i = 0; $i < 5; $i++): ?>
                                <svg style="width: 14px; height: 14px; <?php echo $i < $review['rating'] ? 'fill: #fbbf24;' : 'fill: #e5e7eb;'; ?>" viewBox="0 0 24 24">
                                    <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                                </svg>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <div style="font-size: 12px; color: var(--text-light);">
                            <?php echo date('M d, Y', strtotime($review['created_at'])); ?>
                        </div>
                    </div>
                    <?php if ($review['review_text']): ?>
                    <p style="font-size: 14px; color: var(--text-secondary); line-height: 1.5;">
                        <?php echo htmlspecialchars($review['review_text']); ?>
                    </p>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="text-align: center; padding: 20px; color: var(--text-light);">No reviews yet</p>
            <?php endif; ?>
        </div>

        <!-- Book Appointment Button (Fixed at bottom on mobile) -->
        <div style="position: fixed; bottom: 80px; left: 0; right: 0; padding: 16px; background: white; box-shadow: 0 -2px 10px rgba(0,0,0,0.1);">
            <a href="book-appointment.php?type=doctor&id=<?php echo $doctor['id']; ?>" class="btn btn-primary btn-block btn-lg">Book Appointment</a>
        </div>

        <!-- Spacing for fixed button -->
        <div style="height: 80px;"></div>
    </div>

    <!-- Navigation -->
    <?php include 'includes/navigation.php'; ?>
</body>
</html>

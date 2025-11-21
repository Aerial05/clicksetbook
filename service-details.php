<?php 
require_once 'includes/auth.php';

// Require login to access
requireLogin();
requireProfileComplete();

$currentUser = getCurrentUser();
$pdo = getDBConnection();

// Get service ID
$serviceId = $_GET['id'] ?? 0;

// Get service details
$stmt = $pdo->prepare("SELECT * FROM services WHERE id = ?");
$stmt->execute([$serviceId]);
$service = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$service) {
    header('Location: dashboard.php');
    exit();
}

// Get category icon and color
$categoryInfo = [
    'laboratory' => ['icon' => '🔬', 'color' => '#3b82f6', 'bg' => '#eff6ff'],
    'radiology' => ['icon' => '📷', 'color' => '#8b5cf6', 'bg' => '#f5f3ff'],
    'consultation' => ['icon' => '👨‍⚕️', 'color' => '#10b981', 'bg' => '#d1fae5'],
    'physiotherapy' => ['icon' => '💪', 'color' => '#f59e0b', 'bg' => '#fef3c7'],
    'surgery' => ['icon' => '🏥', 'color' => '#ef4444', 'bg' => '#fee2e2'],
    'emergency' => ['icon' => '🚨', 'color' => '#dc2626', 'bg' => '#fecaca'],
];

$catInfo = $categoryInfo[$service['category']] ?? ['icon' => '🏥', 'color' => '#6b7280', 'bg' => '#f3f4f6'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($service['name']); ?> - Click Set Book</title>
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
            <h1 style="font-size: 20px; font-weight: 700; flex: 1;">Service Details</h1>
        </div>
        </div>

        <!-- Service Hero Card -->
        <div class="card">
            <div style="text-align: center; padding: 20px;">
                <div style="width: 100px; height: 100px; background: <?php echo $catInfo['bg']; ?>; border-radius: 20px; display: flex; align-items: center; justify-content: center; font-size: 48px; margin: 0 auto 16px;">
                    <?php echo $catInfo['icon']; ?>
                </div>
                <h2 style="font-size: 24px; font-weight: 700; margin-bottom: 8px;"><?php echo htmlspecialchars($service['name']); ?></h2>
                <span class="badge" style="background: <?php echo $catInfo['bg']; ?>; color: <?php echo $catInfo['color']; ?>; text-transform: capitalize;">
                    <?php echo htmlspecialchars($service['category']); ?>
                </span>
            </div>

            <!-- Service Info Grid -->
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; padding: 20px; border-top: 1px solid var(--border-color);">
                <div style="text-align: center; padding: 16px; background: var(--bg-secondary); border-radius: 12px;">
                    <svg style="width: 24px; height: 24px; color: var(--primary-color); margin: 0 auto 8px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12 6 12 12 16 14"></polyline>
                    </svg>
                    <div style="font-size: 18px; font-weight: 700; color: var(--primary-color);"><?php echo $service['duration_minutes']; ?> min</div>
                    <div style="font-size: 12px; color: var(--text-light); margin-top: 4px;">Duration</div>
                </div>
                <div style="text-align: center; padding: 16px; background: var(--bg-secondary); border-radius: 12px;">
<svg style="width:24px; height:24px; color:var(--success-color); margin:0 auto 8px;" 
     viewBox="0 0 24 24">
  <text x="0" y="20" font-size="24" fill="currentColor">₱</text>
</svg>
                    <div style="font-size: 18px; font-weight: 700; color: var(--success-color);">₱<?php echo number_format($service['base_cost'], 2); ?></div>
                    <div style="font-size: 12px; color: var(--text-light); margin-top: 4px;">Base Cost</div>
                </div>
            </div>
        </div>

        <!-- About the service -->
        <div class="card">
            <h3 style="font-size: 18px; font-weight: 700; margin-bottom: 12px;">About the service</h3>
            <p style="font-size: 14px; color: var(--text-secondary); line-height: 1.6;">
                <?php echo nl2br(htmlspecialchars($service['description'] ?? 'Comprehensive medical service provided by qualified healthcare professionals.')); ?>
            </p>
        </div>

        <?php if ($service['category'] == 'laboratory'): ?>
        <!-- Types of Tests (for laboratory services) -->
        <div class="card">
            <h3 style="font-size: 18px; font-weight: 700; margin-bottom: 12px;">Types of <?php echo htmlspecialchars($service['name']); ?></h3>
            <ul style="list-style: none; padding: 0;">
                <?php
                $tests = [
                    'Complete Blood Count (CBC)',
                    'Basic Metabolic Panel (BMP)',
                    'Comprehensive Metabolic Panel (CMP)',
                    'Lipid Panel',
                    'Liver Function Tests (LFTs)',
                    'Thyroid Function Tests',
                    'Hemoglobin A1c (HbA1c)',
                    'C-Reactive Protein (CRP)',
                    'Vitamin D Levels',
                    'B-type Natriuretic Peptide (BNP)',
                    'Iron Studies'
                ];
                foreach (array_slice($tests, 0, 6) as $test):
                ?>
                <li style="padding: 12px 0; border-bottom: 1px solid var(--border-color); display: flex; align-items: center; gap: 12px;">
                    <svg style="width: 20px; height: 20px; color: var(--success-color); flex-shrink: 0;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                    <span style="font-size: 14px; color: var(--text-primary);"><?php echo $test; ?></span>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <?php if ($service['preparation_instructions']): ?>
        <!-- Preparation Instructions -->
        <div class="card">
            <h3 style="font-size: 18px; font-weight: 700; margin-bottom: 12px;">Preparation Instructions</h3>
            <p style="font-size: 14px; color: var(--text-secondary); line-height: 1.6;">
                <?php echo nl2br(htmlspecialchars($service['preparation_instructions'])); ?>
            </p>
        </div>
        <?php endif; ?>

        <!-- Costs Information -->
        <div class="card">
            <h3 style="font-size: 18px; font-weight: 700; margin-bottom: 12px;">Costs</h3>
            <p style="font-size: 14px; color: var(--text-light); margin-bottom: 12px;">
                Costs may vary based on the type of tests performed; consult your insurance provider for coverage details.
            </p>
            <div style="padding: 16px; background: #fef3c7; border-radius: 12px; border-left: 4px solid #f59e0b;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-size: 14px; font-weight: 600; color: #92400e;">Base Cost</span>
                    <span style="font-size: 20px; font-weight: 700; color: #92400e;">₱<?php echo number_format($service['base_cost'], 2); ?></span>
                </div>
            </div>
        </div>

        <!-- Book Appointment Button (Fixed at bottom on mobile) -->
        <div style="position: fixed; bottom: 80px; left: 0; right: 0; padding: 16px; background: white; box-shadow: 0 -2px 10px rgba(0,0,0,0.1);">
            <a href="book-appointment.php?type=service&id=<?php echo $service['id']; ?>" class="btn btn-primary btn-block btn-lg">Book Appointment</a>
        </div>

        <!-- Spacing for fixed button -->
        <div style="height: 80px;"></div>
    </div>

    <!-- Navigation -->
    <?php include 'includes/navigation.php'; ?>
</body>
</html>

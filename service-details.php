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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
    <title><?php echo htmlspecialchars($service['name']); ?> - Click Set Book</title>
    <link rel="stylesheet" href="app-styles.css">
    <link rel="stylesheet" href="styles.css">
    <style>
        /* Responsive fixes with zoom support */
        html {
            font-size: 16px;
        }
        
        @media (max-width: 767px) {
            body {
                padding-bottom: 160px; /* Space for button + navigation */
            }
        }
        
        @media (min-width: 768px) {
            body {
                padding-bottom: 40px;
            }
            .service-button-wrapper {
                position: static !important;
                box-shadow: none !important;
                background: transparent !important;
                padding: 0 !important;
                margin-top: 24px;
            }
        }
        
        @media (max-width: 400px) {
            .service-hero-content {
                flex-direction: column !important;
                text-align: center;
            }
            .service-hero-icon {
                margin: 0 auto;
            }
        }
        
        /* Container width adjustments for zoom */
        .container {
            width: 100%;
            max-width: 100%;
        }
        
        @media (min-width: 640px) {
            .container {
                max-width: 640px;
                margin-left: auto;
                margin-right: auto;
            }
        }
        
        @media (min-width: 768px) {
            .container {
                max-width: 768px;
            }
        }
        
        @media (min-width: 1024px) {
            .container {
                max-width: 900px;
            }
        }
        
        /* Flexible sizing with rem/em units */
        .service-hero-icon {
            width: 5rem;
            height: 5rem;
            font-size: 2.5rem;
        }
        
        .service-info-grid {
            font-size: clamp(0.75rem, 2vw, 1rem);
        }
        
        .service-title {
            font-size: clamp(1.125rem, 3vw, 1.25rem);
        }
        
        .service-button {
            font-size: clamp(0.875rem, 2.5vw, 0.9375rem);
            padding: clamp(12px, 2vw, 14px);
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header with Back Button -->
        <div style="display: flex; align-items: center; gap: 16px; margin-bottom: 24px;">
            <button onclick="history.back()" style="background: var(--bg-tertiary); border: none; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.2s;">
                <svg style="width: 20px; height: 20px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M19 12H5M12 19l-7-7 7-7"/>
                </svg>
            </button>
            <h1 style="font-size: 20px; font-weight: 700; flex: 1;">Service Details</h1>
        </div>

        <!-- Service Hero Card - Simplified -->
        <div class="card" style="margin-bottom: 16px;">
            <div class="service-hero-content" style="display: flex; gap: clamp(12px, 3vw, 20px); align-items: start;">
                <div class="service-hero-icon" style="background: <?php echo $catInfo['bg']; ?>; border-radius: 16px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <?php echo $catInfo['icon']; ?>
                </div>
                <div style="flex: 1; min-width: 0;">
                    <h2 class="service-title" style="font-weight: 700; margin-bottom: 8px; word-wrap: break-word;"><?php echo htmlspecialchars($service['name']); ?></h2>
                    <span class="badge" style="background: <?php echo $catInfo['bg']; ?>; color: <?php echo $catInfo['color']; ?>; text-transform: capitalize; font-size: clamp(0.75rem, 2vw, 0.875rem); padding: 4px 12px;">
                        <?php echo htmlspecialchars($service['category']); ?>
                    </span>
                </div>
            </div>

            <!-- Service Info Grid - Simplified -->
            <div class="service-info-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 12px; margin-top: 20px; padding-top: 20px; border-top: 1px solid var(--border-color);">
                <div style="text-align: center; padding: clamp(12px, 2.5vw, 16px); background: var(--bg-secondary); border-radius: 12px;">
                    <div style="font-size: clamp(1.25rem, 4vw, 1.5rem); margin-bottom: 4px;">⏱️</div>
                    <div style="font-size: clamp(0.875rem, 2.5vw, 1rem); font-weight: 600; color: var(--text-primary);"><?php echo $service['duration_minutes']; ?> min</div>
                    <div style="font-size: clamp(0.75rem, 2vw, 0.875rem); color: var(--text-light); margin-top: 2px;">Duration</div>
                </div>
                <div style="text-align: center; padding: clamp(12px, 2.5vw, 16px); background: #ecfdf5; border-radius: 12px;">
                    <div style="font-size: clamp(1.25rem, 4vw, 1.5rem); margin-bottom: 4px;">₱</div>
                    <div style="font-size: clamp(0.875rem, 2.5vw, 1rem); font-weight: 600; color: #059669;"><?php echo number_format($service['base_cost'], 2); ?></div>
                    <div style="font-size: clamp(0.75rem, 2vw, 0.875rem); color: #065f46; margin-top: 2px;">Base Cost</div>
                </div>
            </div>
        </div>

        <!-- About the service -->
        <div class="card" style="margin-bottom: 16px;">
            <h3 style="font-size: 16px; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">About the service</h3>
            <p style="font-size: 14px; color: var(--text-secondary); line-height: 1.6;">
                <?php echo nl2br(htmlspecialchars($service['description'] ?? 'Comprehensive medical service provided by qualified healthcare professionals.')); ?>
            </p>
        </div>

        <?php if ($service['category'] == 'laboratory'): ?>
        <!-- Types of Tests (for laboratory services) -->
        <div class="card" style="margin-bottom: 16px;">
            <h3 style="font-size: 16px; font-weight: 600; margin-bottom: 12px; color: var(--text-primary);">Common Tests Included</h3>
            <ul style="list-style: none; padding: 0; margin: 0;">
                <?php
                $tests = [
                    'Complete Blood Count (CBC)',
                    'Basic Metabolic Panel (BMP)',
                    'Comprehensive Metabolic Panel (CMP)',
                    'Lipid Panel',
                    'Liver Function Tests (LFTs)',
                    'Thyroid Function Tests'
                ];
                foreach ($tests as $index => $test):
                ?>
                <li style="padding: 10px 0; <?php echo $index < count($tests) - 1 ? 'border-bottom: 1px solid var(--border-color);' : ''; ?> display: flex; align-items: center; gap: 10px;">
                    <span style="font-size: 16px; color: var(--success-color);">✓</span>
                    <span style="font-size: 14px; color: var(--text-primary);"><?php echo $test; ?></span>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <?php if ($service['preparation_instructions']): ?>
        <!-- Preparation Instructions -->
        <div class="card" style="margin-bottom: 16px; background: #fef9f3; border-left: 3px solid #f59e0b;">
            <h3 style="font-size: 16px; font-weight: 600; margin-bottom: 8px; color: #92400e;">Preparation Instructions</h3>
            <p style="font-size: 14px; color: #78350f; line-height: 1.6;">
                <?php echo nl2br(htmlspecialchars($service['preparation_instructions'])); ?>
            </p>
        </div>
        <?php endif; ?>

        <!-- Costs Information -->
        <div class="card" style="margin-bottom: 16px;">
            <h3 style="font-size: 16px; font-weight: 600; margin-bottom: 8px; color: var(--text-primary);">Costs</h3>
            <p style="font-size: 13px; color: var(--text-light); margin-bottom: 12px;">
                Costs may vary based on the type of tests performed; consult your insurance provider for coverage details.
            </p>
            <div style="padding: 14px; background: #fef3c7; border-radius: 10px; border-left: 3px solid #f59e0b;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-size: 14px; font-weight: 600; color: #92400e;">Base Cost</span>
                    <span style="font-size: 18px; font-weight: 700; color: #92400e;">₱<?php echo number_format($service['base_cost'], 2); ?></span>
                </div>
            </div>
        </div>

        <!-- Book Appointment Button -->
        <div class="service-button-wrapper" style="position: fixed; bottom: 80px; left: 0; right: 0; padding: 12px 16px; background: linear-gradient(to top, white 0%, white 90%, transparent 100%); box-shadow: 0 -2px 10px rgba(0,0,0,0.05); z-index: 100;">
            <div style="max-width: min(600px, 90vw); margin: 0 auto;">
                <a href="book-appointment.php?type=service&id=<?php echo $service['id']; ?>" 
                   class="service-button"
                   style="display: block; width: 100%; background: var(--primary-color); color: white; text-align: center; border-radius: 12px; font-weight: 600; text-decoration: none; box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3); transition: all 0.2s ease;"
                   onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 16px rgba(79, 70, 229, 0.4)';"
                   onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 12px rgba(79, 70, 229, 0.3)';">
                    Book Appointment
                </a>
            </div>
        </div>
    </div>

    <!-- Navigation -->
    <?php include 'includes/navigation.php'; ?>
</body>
</html>

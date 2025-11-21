<?php 
require_once 'includes/auth.php';

// Require login to access
requireLogin();
requireProfileComplete();

$currentUser = getCurrentUser();
$pdo = getDBConnection();

// Get all favorites for the user
$stmt = $pdo->prepare("
    SELECT 
        f.id as favorite_id,
        f.item_type,
        f.item_id,
        f.created_at,
        CASE 
            WHEN f.item_type = 'doctor' THEN CONCAT(u.first_name, ' ', u.last_name)
            WHEN f.item_type = 'service' THEN s.name
        END as name,
        CASE 
            WHEN f.item_type = 'doctor' THEN d.specialty
            WHEN f.item_type = 'service' THEN s.category
        END as subtitle
    FROM favorites f
    LEFT JOIN doctors d ON f.item_type = 'doctor' AND f.item_id = d.id
    LEFT JOIN users u ON d.user_id = u.id
    LEFT JOIN services s ON f.item_type = 'service' AND f.item_id = s.id
    WHERE f.user_id = ?
    ORDER BY f.created_at DESC
");
$stmt->execute([$currentUser['id']]);
$favorites = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Favorites - Click Set Book</title>
    <link rel="stylesheet" href="app-styles.css">
    <link rel="stylesheet" href="styles.css">
    <style>
        .favorite-card {
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 12px;
            background: white;
            display: flex;
            gap: 12px;
            align-items: center;
        }
        .favorite-card .icon {
            width: 56px;
            height: 56px;
            background: var(--bg-secondary);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            flex-shrink: 0;
        }
        .favorite-card .content {
            flex: 1;
        }
        .favorite-card .remove-btn {
            width: 36px;
            height: 36px;
            background: #fee2e2;
            color: var(--danger-color);
            border: none;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }
        .favorite-card .remove-btn:hover {
            background: #fecaca;
        }
        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }
        .empty-state svg {
            width: 80px;
            height: 80px;
            color: var(--text-light);
            margin-bottom: 16px;
        }
    </style>
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
            <div>
                <h1 style="font-size: 24px; font-weight: 700;">My Favorites</h1>
                <p style="font-size: 14px; color: var(--text-light); margin-top: 4px;"><?php echo count($favorites); ?> saved item<?php echo count($favorites) != 1 ? 's' : ''; ?></p>
            </div>
        </div>

        <?php if (count($favorites) > 0): ?>
            <?php foreach ($favorites as $favorite): ?>
                <div class="favorite-card" onclick="viewItem('<?php echo $favorite['item_type']; ?>', <?php echo $favorite['item_id']; ?>)">
                    <div class="icon">
                        <?php echo $favorite['item_type'] == 'doctor' ? '👨‍⚕️' : '🔬'; ?>
                    </div>
                    <div class="content">
                        <h3 style="font-size: 16px; font-weight: 700; margin-bottom: 4px;"><?php echo htmlspecialchars($favorite['name']); ?></h3>
                        <p style="font-size: 14px; color: var(--text-light); margin-bottom: 6px; text-transform: capitalize;">
                            <?php echo htmlspecialchars($favorite['subtitle']); ?>
                        </p>
                        <span class="badge" style="background: #eff6ff; color: #3b82f6; text-transform: capitalize;">
                            <?php echo $favorite['item_type']; ?>
                        </span>
                    </div>
                    <button class="remove-btn" onclick="event.stopPropagation(); removeFavorite(<?php echo $favorite['favorite_id']; ?>, '<?php echo $favorite['item_type']; ?>', <?php echo $favorite['item_id']; ?>)">
                        <svg style="width: 18px; height: 18px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    </button>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                </svg>
                <h3 style="font-size: 18px; font-weight: 700; margin-bottom: 8px;">No Favorites Yet</h3>
                <p style="font-size: 14px; color: var(--text-light); margin-bottom: 24px;">Start adding doctors and services to your favorites</p>
                <a href="dashboard.php" class="btn btn-primary">Browse Doctors & Services</a>
            </div>
        <?php endif; ?>

        <!-- Spacing for navigation -->
        <div style="height: 80px;"></div>
    </div>

    <!-- Navigation -->
    <?php include 'includes/navigation.php'; ?>

    <script>
    function viewItem(type, id) {
        if (type === 'doctor') {
            window.location.href = 'doctor-details.php?id=' + id;
        } else {
            window.location.href = 'service-details.php?id=' + id;
        }
    }

    async function removeFavorite(favoriteId, type, itemId) {
        if (!confirm('Remove this item from favorites?')) return;
        
        try {
            const response = await fetch('api/toggle-favorite.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ type: type, id: itemId })
            });
            
            const data = await response.json();
            
            if (data.success) {
                location.reload();
            } else {
                alert('Error removing favorite: ' + (data.message || 'Unknown error'));
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Error removing favorite');
        }
    }
    </script>
</body>
</html>

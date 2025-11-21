<?php 
require_once 'includes/auth.php';

// Logout the user
logout();

include 'header.php'; 
?>

<script>
// Clear onboarding status from localStorage
localStorage.removeItem('onboarding_completed');

// Clear any other app-related localStorage data
localStorage.removeItem('user_data');
localStorage.removeItem('session_data');

// Redirect to splash screen
window.location.href = 'index.php';
</script>

<!-- Fallback content in case redirect doesn't work -->
<div class="logout-container">
    <div class="logout-content">
        <h2>Signing out...</h2>
        <p>You will be redirected to the home page.</p>
    </div>
</div>

<style>
.logout-container {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f8fafc;
}

.logout-content {
    text-align: center;
    padding: 40px;
    background: white;
    border-radius: 16px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
}

.logout-content h2 {
    color: #1f2937;
    margin-bottom: 16px;
}

.logout-content p {
    color: #6b7280;
}
</style>

<script>
// Fallback redirect
setTimeout(() => {
    window.location.href = 'index.php';
}, 2000);
</script>

<?php include 'footer.php'; ?>

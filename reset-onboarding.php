<?php include 'header.php'; ?>

<script>
// Clear onboarding status from localStorage
localStorage.removeItem('onboarding_completed');

// Redirect back to index
window.location.href = 'index.php';
</script>

<!-- Fallback content -->
<div class="reset-container">
    <div class="reset-content">
        <h2>Resetting onboarding...</h2>
        <p>You will see the onboarding flow again.</p>
    </div>
</div>

<style>
.reset-container {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f8fafc;
}

.reset-content {
    text-align: center;
    padding: 40px;
    background: white;
    border-radius: 16px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
}

.reset-content h2 {
    color: #1f2937;
    margin-bottom: 16px;
}

.reset-content p {
    color: #6b7280;
}
</style>

<script>
setTimeout(() => {
    window.location.href = 'index.php';
}, 2000);
</script>

<?php include 'footer.php'; ?>

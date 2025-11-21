<?php
$currentPage = 'doctors';
require_once 'header.php';
?>

<div class="section-header">
    <h2 class="section-title">Doctor Management</h2>
    <button class="btn btn-primary" onclick="showAddDoctorModal()">
        <span>+</span>
        <span>Add Doctor</span>
    </button>
</div>

<!-- Search -->
<div class="card" style="margin-bottom: 20px;">
    <input type="text" id="search-doctors" class="form-control" placeholder="Search doctors by name or specialty...">
</div>

<!-- Doctors Grid -->
<div id="doctors-grid" class="doctors-grid">
    <div class="loading-state">
        <div class="loading-spinner"></div>
        <p>Loading doctors...</p>
    </div>
</div>

<script>
// Doctor management JavaScript
// Extract from admin-dashboard.php
</script>

<?php require_once 'footer.php'; ?>

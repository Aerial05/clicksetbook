<?php
$currentPage = 'services';
require_once 'header.php';
?>

<div class="section-header">
    <h2 class="section-title">Service Management</h2>
    <button class="btn btn-primary" onclick="showAddServiceModal()">
        <span>+</span>
        <span>Add Service</span>
    </button>
</div>

<!-- Search -->
<div class="card" style="margin-bottom: 20px;">
    <input type="text" id="search-services" class="form-control" placeholder="Search services by name or category...">
</div>

<!-- Services Grid -->
<div id="services-grid" class="services-grid">
    <div class="loading-state">
        <div class="loading-spinner"></div>
        <p>Loading services...</p>
    </div>
</div>

<script>
// Service management JavaScript
// Extract from admin-dashboard.php
</script>

<?php require_once 'footer.php'; ?>

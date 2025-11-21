<?php
$currentPage = 'logs';
require_once 'header.php';
?>

<div class="section-header">
    <h2 class="section-title">History & Logs</h2>
</div>

<!-- Filters -->
<div class="card" style="margin-bottom: 20px;">
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px;">
        <div>
            <label style="display: block; font-size: 13px; margin-bottom: 6px; color: var(--text-light);">Log Type</label>
            <select id="filter-log-type" class="form-control">
                <option value="">All Types</option>
                <option value="appointment">Appointments</option>
                <option value="user">Users</option>
                <option value="doctor">Doctors</option>
                <option value="service">Services</option>
            </select>
        </div>
        <div>
            <label style="display: block; font-size: 13px; margin-bottom: 6px; color: var(--text-light);">Date Range</label>
            <input type="date" id="filter-log-date" class="form-control">
        </div>
    </div>
</div>

<!-- Logs List -->
<div id="logs-list">
    <div class="loading-state">
        <div class="loading-spinner"></div>
        <p>Loading logs...</p>
    </div>
</div>

<script>
// Logs JavaScript
</script>

<?php require_once 'footer.php'; ?>

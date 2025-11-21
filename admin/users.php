<?php
$currentPage = 'users';
require_once 'header.php';
?>

<div class="section-header">
    <h2 class="section-title">User Management</h2>
    <button class="btn btn-primary" onclick="showAddUserModal()">
        <span>+</span>
        <span>Add User</span>
    </button>
</div>

<!-- Search -->
<div class="card" style="margin-bottom: 20px;">
    <input type="text" id="search-users" class="form-control" placeholder="Search users by name or email...">
</div>

<!-- Users List -->
<div id="users-list">
    <div class="loading-state">
        <div class="loading-spinner"></div>
        <p>Loading users...</p>
    </div>
</div>

<script>
// User management JavaScript
// Extract from admin-dashboard.php
</script>

<?php require_once 'footer.php'; ?>

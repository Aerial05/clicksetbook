<?php 
require_once 'includes/auth.php';

// Require login to access
requireLogin();
requireProfileComplete();

$currentUser = getCurrentUser();
$pdo = getDBConnection();

// Get search and filter parameters
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? 'all';
$specialty = $_GET['specialty'] ?? 'all';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home - Click Set Book</title>
    <link rel="stylesheet" href="app-styles.css">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <!-- Page Header -->
        <div class="page-header">
            <h1>All Medical Services</h1>
            <p class="subtitle">Find doctors and book medical services</p>
        </div>

        <!-- Search Bar -->
        <div class="search-bar">
            <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="11" cy="11" r="8"></circle>
                <path d="m21 21-4.35-4.35"></path>
            </svg>
            <input type="text" class="search-input" id="searchInput" placeholder="Search doctor or service..." value="<?php echo htmlspecialchars($search); ?>">
        </div>

        <!-- Main Tabs -->
        <div class="tabs">
            <button class="tab active" data-tab="services">All</button>
            <button class="tab" data-tab="doctors">Doctors</button>
            <button class="tab" data-tab="laboratory">Laboratory</button>
            <button class="tab" data-tab="imaging">Imaging</button>
            <button class="tab" data-tab="vaccines">Consultation</button>
        </div>

        <!-- Results Count and Sort -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <p class="text-secondary"><span id="resultCount">0</span> found</p>
            <select id="sortSelect" style="padding: 8px 12px; border: 1px solid var(--border-color); border-radius: 8px; font-size: 14px;">
                <option value="default">Default</option>
                <option value="name_asc">Name A-Z</option>
                <option value="name_desc">Name Z-A</option>
                <option value="price_asc">Price Low-High</option>
                <option value="price_desc">Price High-Low</option>
            </select>
        </div>

        <!-- Results Container -->
        <div id="resultsContainer" class="grid">
            <!-- Items will be loaded here via JavaScript -->
        </div>

        <!-- Loading State -->
        <div id="loadingState" class="loading hidden">
            <div class="spinner"></div>
            <p class="mt-3 text-secondary">Loading...</p>
        </div>

        <!-- Empty State -->
        <div id="emptyState" class="empty-state hidden">
            <svg class="empty-state-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="11" cy="11" r="8"></circle>
                <path d="m21 21-4.35-4.35"></path>
            </svg>
            <h3 class="empty-state-title">No results found</h3>
            <p class="empty-state-text">Try adjusting your search or filters</p>
        </div>
    </div>

    <!-- Navigation -->
    <?php include 'includes/navigation.php'; ?>

    <script>
    let currentTab = 'services';
    let searchTerm = '';
    let sortBy = 'default';
    
    // Load initial data
    document.addEventListener('DOMContentLoaded', function() {
        loadData();
        
        // Tab switching
        document.querySelectorAll('.tab').forEach(tab => {
            tab.addEventListener('click', function() {
                document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
                this.classList.add('active');
                currentTab = this.dataset.tab;
                loadData();
            });
        });
        
        // Search
        let searchTimeout;
        document.getElementById('searchInput').addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTerm = this.value;
            searchTimeout = setTimeout(() => loadData(), 300);
        });
        
        // Sort
        document.getElementById('sortSelect').addEventListener('change', function() {
            sortBy = this.value;
            loadData();
        });
    });
    
    async function loadData() {
        showLoading();
        
        try {
            const params = new URLSearchParams({
                tab: currentTab,
                search: searchTerm,
                sort: sortBy
            });
            
            const response = await fetch(`api/get-home-data.php?${params}`);
            const data = await response.json();
            
            if (data.success) {
                displayResults(data.items, data.type);
                document.getElementById('resultCount').textContent = data.items.length;
            } else {
                showEmpty();
            }
        } catch (error) {
            console.error('Error loading data:', error);
            showEmpty();
        }
    }
    
    function displayResults(items, type) {
        const container = document.getElementById('resultsContainer');
        const loading = document.getElementById('loadingState');
        const empty = document.getElementById('emptyState');
        
        loading.classList.add('hidden');
        
        if (items.length === 0) {
            container.innerHTML = '';
            empty.classList.remove('hidden');
            return;
        }
        
        empty.classList.add('hidden');
        
        if (type === 'doctor') {
            container.innerHTML = items.map(doctor => createDoctorCard(doctor)).join('');
        } else {
            container.innerHTML = items.map(service => createServiceCard(service)).join('');
        }
    }
    
    function createDoctorCard(doctor) {
        // Generate initials for fallback avatar
        const getInitials = (name) => {
            if (!name) return 'DR';
            const parts = name.split(' ');
            if (parts.length >= 2) {
                return parts[0][0] + parts[1][0];
            }
            return parts[0].substring(0, 2);
        };
        
        // Get color based on doctor ID
        const getAvatarColor = (id) => {
            const colors = ['color-1', 'color-2', 'color-3', 'color-4', 'color-5'];
            return colors[id % colors.length];
        };
        
        const initials = getInitials(doctor.name);
        const avatarColor = getAvatarColor(doctor.id);
        
        // Determine if we should show image or fallback
        const avatarHTML = doctor.profile_image && doctor.profile_image !== '' && doctor.profile_image !== 'null'
            ? `<img src="${doctor.profile_image}" alt="${doctor.name}" class="card-image" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
               <div class="avatar-fallback ${avatarColor}" style="display:none;">${initials}</div>`
            : `<div class="avatar-fallback ${avatarColor}">${initials}</div>`;
        
        const isFavorited = doctor.is_favorited == 1;
        
        return `
            <div class="card doctor-card" onclick="location.href='doctor-details.php?id=${doctor.id}'">
                <div class="card-header">
                    ${avatarHTML}
                    <div class="card-body">
                        <h3 class="card-title">Dr. ${doctor.name}</h3>
                        <p class="card-subtitle">${doctor.specialty}</p>
                        <p class="text-sm text-light">
                            <svg style="width: 14px; height: 14px; display: inline;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                <circle cx="12" cy="10" r="3"></circle>
                            </svg>
                            ${doctor.department || 'General Hospital'}
                        </p>
                    </div>
                </div>
                <div class="stats">
                    <div class="stat-item">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                        <span class="value">${doctor.patient_count || '2,000'}+</span> patients
                    </div>
                    <div class="stat-item">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                        </svg>
                        <span class="value">${doctor.rating || '5'}</span>
                    </div>
                    <div class="stat-item">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y1="10"></line>
                        </svg>
                        <span class="value">${doctor.experience_years || '10'}+</span> years
                    </div>
                </div>
                <div class="card-footer">
                    <span class="text-secondary">Consultation Fee:</span>
                    <span class="font-bold text-primary">₱${doctor.consultation_fee || '150'}</span>
                </div>
            </div>
        `;
    }
    
    function createServiceCard(service) {
        const isFavorited = service.is_favorited == 1;
        
        // Category icons mapping
        const categoryIcons = {
            'consultation': '👨‍⚕️',
            'laboratory': '🔬',
            'radiology': '📊',
            'physiotherapy': '💪',
            'surgery': '🏥',
            'emergency': '🚑'
        };
        
        const categoryIcon = categoryIcons[service.category] || '⚕️';
        
        return `
            <div class="card service-card" onclick="location.href='service-details.php?id=${service.id}'">
                <div class="card-header">
                    <div class="service-fallback ${service.category}">${categoryIcon}</div>
                    <div class="card-body">
                        <h3 class="card-title">${service.name}</h3>
                        <p class="card-subtitle">${service.category}</p>
                        <p class="card-description">${service.description}</p>
                    </div>
                </div>
                <div class="card-footer">
                    <span class="text-secondary">Duration: ${service.duration_minutes} min</span>
                    <span class="font-bold text-primary">₱${service.base_cost}</span>
                </div>
            </div>
        `;
    }
    
    function showLoading() {
        document.getElementById('resultsContainer').innerHTML = '';
        document.getElementById('loadingState').classList.remove('hidden');
        document.getElementById('emptyState').classList.add('hidden');
    }
    
    function showEmpty() {
        document.getElementById('resultsContainer').innerHTML = '';
        document.getElementById('loadingState').classList.add('hidden');
        document.getElementById('emptyState').classList.remove('hidden');
    }
    </script>
</body>
</html>

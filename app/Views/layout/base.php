<?php
/**
 * Base Layout Template
 * Contains: HTML structure, meta tags, CSS/JS includes, global stats
 */

// Fetch global stats for sidebar (available on all pages)
if (!isset($globalStats)) {
    try {
        $pdo = \Core\Database::getConnection();
        
        // Get total domains
        $totalStmt = $pdo->query("SELECT COUNT(*) as count FROM domains");
        $totalResult = $totalStmt->fetch(\PDO::FETCH_ASSOC);
        $total = $totalResult['count'] ?? 0;
        
        // Get active domains
        $activeStmt = $pdo->query("SELECT COUNT(*) as count FROM domains WHERE is_active = 1");
        $activeResult = $activeStmt->fetch(\PDO::FETCH_ASSOC);
        $active = $activeResult['count'] ?? 0;
        
        // Get expiring soon (within 30 days)
        $expiringSoonStmt = $pdo->query("SELECT COUNT(*) as count FROM domains WHERE expiration_date IS NOT NULL AND expiration_date <= DATE_ADD(NOW(), INTERVAL 30 DAY) AND expiration_date >= NOW()");
        $expiringSoonResult = $expiringSoonStmt->fetch(\PDO::FETCH_ASSOC);
        $expiringSoon = $expiringSoonResult['count'] ?? 0;
        
        $globalStats = [
            'total' => $total,
            'active' => $active,
            'expiring_soon' => $expiringSoon
        ];
    } catch (\Exception $e) {
        $globalStats = [
            'total' => 0,
            'active' => 0,
            'expiring_soon' => 0
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="description" content="Domain Monitor - Track and monitor your domain expiration dates">
    <meta name="author" content="Domain Monitor">
    <meta name="robots" content="noindex, nofollow">
    
    <!-- Title -->
    <title><?= $title ?? 'Domain Monitor' ?> - <?= $_ENV['APP_NAME'] ?? 'Domain Monitor' ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/assets/favicon.ico">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Flag Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/flag-icons/7.5.0/css/flag-icons.min.css" referrerpolicy="no-referrer" />
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    
    <!-- Tailwind Configuration -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            DEFAULT: '#4A90E2',
                            dark: '#357ABD',
                            light: '#6BA3E8',
                        },
                        sidebar: {
                            DEFAULT: '#1F2937',
                            light: '#374151',
                        }
                    }
                }
            }
        }
    </script>
    
    <!-- Custom Styles -->
    <link rel="stylesheet" href="/assets/style.css">
    
    <!-- Custom Page Styles (optional) -->
    <?php if (isset($customStyles)): ?>
        <style><?= $customStyles ?></style>
    <?php endif; ?>
    
    <style>
        /* Sidebar full height */
        .sidebar {
            height: 100vh;
            transition: transform 0.3s ease-in-out;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            .sidebar.open {
                transform: translateX(0);
            }
        }
        
        /* Dropdown animation */
        .dropdown-menu {
            display: none;
            opacity: 0;
            transform: translateY(-10px);
            transition: all 0.2s ease-in-out;
        }
        .dropdown-menu.show {
            display: block;
            opacity: 1;
            transform: translateY(0);
        }
        
        /* Active sidebar link */
        .sidebar-link.active {
            background: #374151;
            border-left: 4px solid #4A90E2;
        }
    </style>
</head>
<body class="bg-gray-50">
    
    <?php include __DIR__ . '/top-nav.php'; ?>
    
    <?php include __DIR__ . '/sidebar.php'; ?>
    
    <!-- Main Content Area -->
    <main class="md:ml-64 pt-16 min-h-screen bg-gray-50">
        <div class="p-6">
            <!-- Flash Messages -->
            <?php include __DIR__ . '/messages.php'; ?>
            
            <!-- Page Content -->
            <?php if (isset($content)): ?>
                <?= $content ?>
            <?php endif; ?>
        </div>
    </main>
    
    <!-- Global Scripts -->
    <script>
        // Toggle sidebar on mobile
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('open');
        }

        // Toggle user dropdown
        function toggleDropdown() {
            document.getElementById('userDropdown').classList.toggle('show');
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            const dropdown = document.getElementById('userDropdown');
            const isClickInside = event.target.closest('[onclick="toggleDropdown()"]') || event.target.closest('#userDropdown');
            
            if (!isClickInside && dropdown && dropdown.classList.contains('show')) {
                dropdown.classList.remove('show');
            }
        });

        // Live Search Functionality
        let searchTimeout;
        const searchInput = document.getElementById('globalSearchInput');
        const searchDropdown = document.getElementById('searchDropdown');
        const searchResults = document.getElementById('searchResults');
        const searchLoading = document.getElementById('searchLoading');

        if (searchInput) {
            searchInput.addEventListener('input', function(e) {
                const query = e.target.value.trim();
                
                clearTimeout(searchTimeout);
                
                if (query.length < 2) {
                    searchDropdown.classList.add('hidden');
                    return;
                }
                
                // Show loading
                searchDropdown.classList.remove('hidden');
                searchLoading.classList.remove('hidden');
                searchResults.innerHTML = '';
                
                // Debounce search
                searchTimeout = setTimeout(() => {
                    fetch(`/api/search/suggest?q=${encodeURIComponent(query)}`)
                        .then(response => response.json())
                        .then(data => {
                            searchLoading.classList.add('hidden');
                            renderSearchResults(data);
                        })
                        .catch(error => {
                            searchLoading.classList.add('hidden');
                            searchResults.innerHTML = '<div class="p-4 text-red-600 text-sm">Error loading results</div>';
                        });
                }, 300);
            });

            // Handle form submission
            const searchForm = document.getElementById('globalSearchForm');
            if (searchForm) {
                searchForm.addEventListener('submit', function(e) {
                    searchDropdown.classList.add('hidden');
                });
            }
            
            // Close dropdown when clicking outside
            document.addEventListener('click', function(event) {
                if (searchDropdown && !searchDropdown.contains(event.target) && event.target !== searchInput) {
                    searchDropdown.classList.add('hidden');
                }
            });
        }

        function renderSearchResults(data) {
            let html = '';
            
            if (data.domains && data.domains.length > 0) {
                html += '<div class="p-2">';
                html += '<p class="px-3 py-2 text-xs font-semibold text-gray-500 uppercase">Your Domains</p>';
                
                data.domains.forEach(domain => {
                    const statusColors = {
                        'red': 'text-red-600',
                        'orange': 'text-orange-600',
                        'yellow': 'text-yellow-600',
                        'green': 'text-green-600',
                        'gray': 'text-gray-400'
                    };
                    const colorClass = statusColors[domain.status_color] || 'text-gray-600';
                    
                    html += `
                        <a href="/domains/${domain.id}" class="block px-3 py-2 hover:bg-gray-50 rounded-lg transition-colors">
                            <div class="flex items-center justify-between">
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-semibold text-gray-900 truncate">${escapeHtml(domain.domain_name)}</p>
                                    <p class="text-xs text-gray-500">${escapeHtml(domain.registrar || 'Unknown registrar')}</p>
                                </div>
                                ${domain.days_left !== null ? `
                                    <div class="ml-3 text-right">
                                        <p class="text-xs font-semibold ${colorClass}">${domain.days_left} days</p>
                                    </div>
                                ` : ''}
                            </div>
                        </a>
                    `;
                });
                
                html += '</div>';
            }
            
            // Show WHOIS lookup option if no results and looks like a domain
            if (data.domains.length === 0 && data.isDomainLike) {
                html += `
                    <div class="p-4 border-t border-gray-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-900">Domain not in portfolio</p>
                                <p class="text-xs text-gray-500 mt-0.5">Perform WHOIS lookup for ${escapeHtml(data.query)}</p>
                            </div>
                            <button onclick="window.location.href='/search?q=${encodeURIComponent(data.query)}'" class="px-3 py-1.5 bg-primary text-white text-xs rounded-lg hover:bg-primary-dark">
                                Lookup
                            </button>
                        </div>
                    </div>
                `;
            } else if (data.domains.length === 0) {
                html += '<div class="p-4 text-center text-sm text-gray-500">No results found</div>';
            }
            
            // Add "View all results" link if there are results
            if (data.domains.length > 0) {
                html += `
                    <div class="border-t border-gray-200 p-2">
                        <a href="/search?q=${encodeURIComponent(data.query)}" class="block px-3 py-2 text-center text-sm font-medium text-primary hover:bg-gray-50 rounded-lg">
                            View all results →
                        </a>
                    </div>
                `;
            }
            
            searchResults.innerHTML = html;
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    </script>
    
    <!-- Custom Page Scripts (optional) -->
    <?php if (isset($customScripts)): ?>
        <script><?= $customScripts ?></script>
    <?php endif; ?>
    
</body>
</html>


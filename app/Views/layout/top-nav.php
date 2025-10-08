<!-- Top Navigation Bar -->
<nav class="bg-white border-b border-gray-200 fixed top-0 left-0 md:left-64 right-0 z-20">
    <div class="px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            <!-- Left: Menu button and Page Header -->
            <div class="flex items-center min-w-0">
                <button onclick="toggleSidebar()" class="text-gray-500 hover:text-gray-700 focus:outline-none focus:text-gray-700 md:hidden mr-4">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                
                <!-- Page Title & Description -->
                <div class="hidden md:block">
                    <h2 class="text-xl font-bold text-gray-800 flex items-center">
                        <?php if (isset($pageIcon)): ?>
                            <i class="<?= $pageIcon ?> text-primary mr-2"></i>
                        <?php endif; ?>
                        <?= $pageTitle ?? $title ?? 'Dashboard' ?>
                    </h2>
                    <?php if (isset($pageDescription)): ?>
                        <p class="text-sm text-gray-600 mt-0.5"><?= $pageDescription ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Center: Search Bar -->
            <div class="flex-1 max-w-2xl mx-8">
                <form action="/search" method="GET" class="relative hidden md:block" id="globalSearchForm">
                    <input type="text" 
                           name="q"
                           placeholder="Search domains or lookup WHOIS..." 
                           class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent text-sm"
                           id="globalSearchInput"
                           autocomplete="off">
                    <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                    
                    <!-- Search Results Dropdown -->
                    <div id="searchDropdown" class="hidden absolute top-full left-0 right-0 mt-2 bg-white rounded-lg shadow-xl border border-gray-200 max-h-96 overflow-y-auto z-50">
                        <!-- Loading state -->
                        <div id="searchLoading" class="hidden p-4 text-center">
                            <i class="fas fa-spinner fa-spin text-primary"></i>
                            <p class="text-sm text-gray-600 mt-2">Searching...</p>
                        </div>
                        
                        <!-- Results will be inserted here -->
                        <div id="searchResults"></div>
                    </div>
                </form>
            </div>

            <!-- Right: Actions & User -->
            <div class="flex items-center space-x-2">
                <!-- Quick Add Domain -->
                <a href="/domains/create" title="Add Domain" class="flex items-center justify-center w-9 h-9 bg-primary hover:bg-primary-dark text-white rounded-lg transition-colors duration-150">
                    <i class="fas fa-plus"></i>
                </a>
                
                <!-- Notifications -->
                <button title="Notifications" class="relative flex items-center justify-center w-9 h-9 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition-colors duration-150">
                    <i class="fas fa-bell"></i>
                    <?php if (($globalStats['expiring_soon'] ?? 0) > 0): ?>
                        <span class="absolute top-1 right-1 flex h-2 w-2">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-red-500"></span>
                        </span>
                    <?php endif; ?>
                </button>
                
                <!-- Settings -->
                <button title="Settings" class="flex items-center justify-center w-9 h-9 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition-colors duration-150">
                    <i class="fas fa-cog"></i>
                </button>
                
                <!-- Divider -->
                <div class="hidden md:block h-8 w-px bg-gray-300"></div>
                
                <!-- User Dropdown -->
                <div class="relative">
                    <button onclick="toggleDropdown()" class="flex items-center space-x-3 p-2 hover:bg-gray-100 rounded-lg transition-colors duration-150 focus:outline-none">
                        <div class="w-9 h-9 rounded-full bg-primary flex items-center justify-center text-white font-semibold">
                            <?= strtoupper(substr($_SESSION['username'] ?? 'A', 0, 1)) ?>
                        </div>
                        <div class="hidden lg:block text-left">
                            <p class="text-sm font-medium text-gray-700"><?= htmlspecialchars($_SESSION['username'] ?? 'User') ?></p>
                            <p class="text-xs text-gray-500">Administrator</p>
                        </div>
                        <i class="fas fa-chevron-down text-gray-400 text-xs hidden md:block"></i>
                    </button>

                    <!-- Dropdown Menu -->
                    <div id="userDropdown" class="dropdown-menu absolute right-0 mt-2 w-56 bg-white rounded-lg shadow-lg py-2 border border-gray-200">
                        <div class="px-4 py-3 border-b border-gray-200">
                            <p class="text-sm font-medium text-gray-900"><?= htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'User') ?></p>
                            <p class="text-xs text-gray-500 mt-1"><?= htmlspecialchars($_SESSION['email'] ?? 'admin@example.com') ?></p>
                            <span class="inline-block mt-2 px-2 py-1 bg-green-100 text-green-800 text-xs font-semibold rounded">
                                <i class="fas fa-circle text-xs mr-1"></i>Online
                            </span>
                        </div>
                        
                        <a href="#" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors duration-150">
                            <i class="fas fa-user-circle w-5 text-gray-400 mr-3"></i>
                            My Profile
                        </a>
                        
                        <a href="#" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors duration-150">
                            <i class="fas fa-cog w-5 text-gray-400 mr-3"></i>
                            Account Settings
                        </a>
                        
                        <a href="#" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors duration-150">
                            <i class="fas fa-bell w-5 text-gray-400 mr-3"></i>
                            Notifications
                        </a>
                        
                        <div class="border-t border-gray-200 my-1"></div>
                        
                        <a href="#" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors duration-150">
                            <i class="fas fa-question-circle w-5 text-gray-400 mr-3"></i>
                            Help & Support
                        </a>
                        
                        <div class="border-t border-gray-200 my-1"></div>
                        
                        <a href="/logout" class="flex items-center px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors duration-150">
                            <i class="fas fa-sign-out-alt w-5 mr-3"></i>
                            Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</nav>


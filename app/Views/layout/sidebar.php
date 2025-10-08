<!-- Sidebar Navigation -->
<aside id="sidebar" class="sidebar fixed left-0 top-0 w-64 bg-gray-900 text-white z-30">
    <div class="h-full overflow-y-auto flex flex-col">
        
        <!-- Logo Section -->
        <div class="h-16 px-5 border-b border-gray-800 flex items-center">
            <div class="flex items-center">
                <div class="w-9 h-9 bg-primary rounded-lg flex items-center justify-center mr-3">
                    <i class="fas fa-globe text-white text-sm"></i>
                </div>
                <h1 class="text-sm font-semibold text-white"><?= $appName ?? 'Domain Monitor' ?></h1>
            </div>
        </div>
        
        <!-- Navigation Links -->
        <nav class="px-4 py-3">
            <div class="space-y-0.5">
                <a href="/" class="sidebar-link flex items-center px-3 py-2 rounded-md text-gray-400 hover:bg-gray-800 hover:text-white transition-colors duration-150 <?= $_SERVER['REQUEST_URI'] === '/' ? 'bg-primary text-white' : '' ?>">
                    <i class="fas fa-chart-line text-xs mr-3 w-4"></i>
                    <span class="text-sm">Dashboard</span>
                </a>
                
                <a href="/domains" class="sidebar-link flex items-center px-3 py-2 rounded-md text-gray-400 hover:bg-gray-800 hover:text-white transition-colors duration-150 <?= strpos($_SERVER['REQUEST_URI'], '/domains') !== false ? 'bg-primary text-white' : '' ?>">
                    <i class="fas fa-globe text-xs mr-3 w-4"></i>
                    <span class="text-sm">Domains</span>
                </a>
                
                <a href="/groups" class="sidebar-link flex items-center px-3 py-2 rounded-md text-gray-400 hover:bg-gray-800 hover:text-white transition-colors duration-150 <?= strpos($_SERVER['REQUEST_URI'], '/groups') !== false ? 'bg-primary text-white' : '' ?>">
                    <i class="fas fa-bell text-xs mr-3 w-4"></i>
                    <span class="text-sm">Notification Groups</span>
                </a>
                
                <a href="/tld-registry" class="sidebar-link flex items-center px-3 py-2 rounded-md text-gray-400 hover:bg-gray-800 hover:text-white transition-colors duration-150 <?= strpos($_SERVER['REQUEST_URI'], '/tld-registry') !== false ? 'bg-primary text-white' : '' ?>">
                    <i class="fas fa-database text-xs mr-3 w-4"></i>
                    <span class="text-sm">TLD Registry</span>
                </a>
            </div>

            <!-- Tools Section -->
            <div class="mt-4 pt-3 border-t border-gray-800">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider px-3 mb-1">Tools</p>
                <div class="space-y-0.5">
                    <a href="/debug/whois" class="sidebar-link flex items-center px-3 py-2 rounded-md text-gray-400 hover:bg-gray-800 hover:text-white transition-colors duration-150">
                        <i class="fas fa-search text-xs mr-3 w-4"></i>
                        <span class="text-sm">WHOIS Lookup</span>
                    </a>
                </div>
            </div>

            <!-- System Section -->
            <div class="mt-4 pt-3 border-t border-gray-800">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider px-3 mb-1">System</p>
                <div class="space-y-0.5">
                    <a href="/settings" class="sidebar-link flex items-center px-3 py-2 rounded-md text-gray-400 hover:bg-gray-800 hover:text-white transition-colors duration-150 <?= strpos($_SERVER['REQUEST_URI'], '/settings') !== false ? 'bg-primary text-white' : '' ?>">
                        <i class="fas fa-cog text-xs mr-3 w-4"></i>
                        <span class="text-sm">Settings</span>
                    </a>
                </div>
            </div>
        </nav>

        <!-- Quick Stats Cards - Pinned to Bottom -->
        <div class="mt-auto px-4 pb-3 border-t border-gray-800 pt-3">
            <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider px-3 mb-2">Quick Stats</div>
            <div class="space-y-1.5">
                <div class="bg-gray-800 rounded-md p-2.5 border border-gray-700">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="w-7 h-7 bg-blue-500/20 rounded flex items-center justify-center mr-2.5">
                                <i class="fas fa-globe text-blue-400 text-xs"></i>
                            </div>
                            <span class="text-gray-400 text-xs">Total</span>
                        </div>
                        <span class="text-white font-semibold text-sm"><?= $globalStats['total'] ?? 0 ?></span>
                    </div>
                </div>
                
                <div class="bg-gray-800 rounded-md p-2.5 border border-gray-700">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="w-7 h-7 bg-orange-500/20 rounded flex items-center justify-center mr-2.5">
                                <i class="fas fa-exclamation-triangle text-orange-400 text-xs"></i>
                            </div>
                            <span class="text-gray-400 text-xs" title="Within <?= $globalStats['expiring_threshold'] ?? 30 ?> days">Expiring</span>
                        </div>
                        <span class="text-orange-400 font-semibold text-sm"><?= $globalStats['expiring_soon'] ?? 0 ?></span>
                    </div>
                </div>
                
                <div class="bg-gray-800 rounded-md p-2.5 border border-gray-700">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="w-7 h-7 bg-green-500/20 rounded flex items-center justify-center mr-2.5">
                                <i class="fas fa-check-circle text-green-400 text-xs"></i>
                            </div>
                            <span class="text-gray-400 text-xs">Active</span>
                        </div>
                        <span class="text-green-400 font-semibold text-sm"><?= $globalStats['active'] ?? 0 ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="px-4 py-3 border-t border-gray-800">
            <div class="text-center">
                <p class="text-xs text-gray-500">© <?= date('Y') ?> Domain Monitor</p>
                <p class="text-xs text-gray-600 mt-0.5">v1.0.0</p>
            </div>
        </div>
    </div>
</aside>


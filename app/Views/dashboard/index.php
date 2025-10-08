<?php
$title = 'Dashboard';
$pageTitle = 'Dashboard Overview';
$pageDescription = 'Monitor your domains and expiration dates';
$pageIcon = 'fas fa-chart-line';
ob_start();
?>

<!-- Statistics Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <!-- Total Domains Card -->
    <div class="bg-white rounded-lg border border-gray-200 p-5 hover:shadow-md transition-shadow duration-200">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Total Domains</p>
                <p class="text-2xl font-semibold text-gray-900 mt-1"><?= $stats['total'] ?? 0 ?></p>
            </div>
            <div class="w-12 h-12 bg-blue-50 rounded-lg flex items-center justify-center">
                <i class="fas fa-globe text-blue-600 text-lg"></i>
            </div>
        </div>
    </div>

    <!-- Active Domains Card -->
    <div class="bg-white rounded-lg border border-gray-200 p-5 hover:shadow-md transition-shadow duration-200">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Active</p>
                <p class="text-2xl font-semibold text-gray-900 mt-1"><?= $stats['active'] ?? 0 ?></p>
            </div>
            <div class="w-12 h-12 bg-green-50 rounded-lg flex items-center justify-center">
                <i class="fas fa-check-circle text-green-600 text-lg"></i>
            </div>
        </div>
    </div>

    <!-- Expiring Soon Card -->
    <div class="bg-white rounded-lg border border-gray-200 p-5 hover:shadow-md transition-shadow duration-200">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Expiring Soon</p>
                <p class="text-2xl font-semibold text-gray-900 mt-1"><?= $stats['expiring_soon'] ?? 0 ?></p>
            </div>
            <div class="w-12 h-12 bg-orange-50 rounded-lg flex items-center justify-center">
                <i class="fas fa-exclamation-triangle text-orange-600 text-lg"></i>
            </div>
        </div>
    </div>

    <!-- Inactive Domains Card -->
    <div class="bg-white rounded-lg border border-gray-200 p-5 hover:shadow-md transition-shadow duration-200">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Inactive</p>
                <p class="text-2xl font-semibold text-gray-900 mt-1"><?= $stats['inactive'] ?? 0 ?></p>
            </div>
            <div class="w-12 h-12 bg-gray-50 rounded-lg flex items-center justify-center">
                <i class="fas fa-times-circle text-gray-600 text-lg"></i>
            </div>
        </div>
    </div>
</div>

<!-- Content Grid -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <!-- Recent Domains -->
    <div class="lg:col-span-2 bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900 flex items-center">
                <i class="fas fa-clock text-gray-400 mr-2 text-sm"></i>
                Recent Domains
            </h2>
        </div>
        <div class="p-6">
            <?php if (!empty($recentDomains)): ?>
                <div class="space-y-3">
                    <?php foreach ($recentDomains as $domain): ?>
                        <div class="flex items-center justify-between p-3 border border-gray-100 rounded-lg hover:border-gray-300 hover:shadow-sm transition-all duration-200">
                            <div class="flex items-center space-x-3 flex-1 min-w-0">
                                <div class="w-10 h-10 bg-gray-50 rounded-lg flex items-center justify-center flex-shrink-0">
                                    <i class="fas fa-globe text-gray-400"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h3 class="font-medium text-gray-900 truncate"><?= htmlspecialchars($domain['domain_name']) ?></h3>
                                    <div class="flex items-center space-x-3 text-xs text-gray-500 mt-1">
                                        <span class="flex items-center">
                                            <i class="far fa-calendar mr-1"></i>
                                            <?php if ($domain['expiration_date']): ?>
                                                <?= date('M d, Y', strtotime($domain['expiration_date'])) ?>
                                            <?php else: ?>
                                                Not set
                                            <?php endif; ?>
                                        </span>
                                        <?php if ($domain['registrar']): ?>
                                            <span class="flex items-center truncate">
                                                <i class="fas fa-building mr-1"></i>
                                                <?= htmlspecialchars($domain['registrar']) ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center space-x-2 flex-shrink-0">
                                <?php
                                $statusClass = $domain['status'] === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700';
                                ?>
                                <span class="px-2 py-1 rounded text-xs font-medium <?= $statusClass ?>">
                                    <?= ucfirst($domain['status']) ?>
                                </span>
                                <a href="/domains/<?= $domain['id'] ?>" class="text-gray-400 hover:text-primary">
                                    <i class="fas fa-chevron-right text-sm"></i>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="mt-4 pt-4 border-t border-gray-100 text-center">
                    <a href="/domains" class="text-sm text-primary hover:text-primary-dark font-medium inline-flex items-center">
                        View All Domains
                        <i class="fas fa-arrow-right ml-2 text-xs"></i>
                    </a>
                </div>
            <?php else: ?>
                <div class="text-center py-10">
                    <i class="fas fa-globe text-gray-300 text-5xl mb-3"></i>
                    <p class="text-gray-500">No domains added yet</p>
                    <a href="/domains/create" class="mt-3 inline-flex items-center px-5 py-2 bg-primary text-white text-sm rounded-lg hover:bg-primary-dark transition-colors duration-200">
                        <i class="fas fa-plus mr-2"></i>
                        Add Your First Domain
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Sidebar: Quick Actions & Stats -->
    <div class="space-y-4">
        <!-- Quick Actions -->
        <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
            <div class="px-5 py-3 border-b border-gray-200">
                <h2 class="text-sm font-semibold text-gray-900 flex items-center">
                    <i class="fas fa-bolt text-gray-400 mr-2 text-xs"></i>
                    Quick Actions
                </h2>
            </div>
            <div class="p-4 space-y-2">
                <a href="/domains/create" class="flex items-center p-3 border border-gray-200 hover:border-primary hover:bg-blue-50 rounded-lg transition-all duration-200 group">
                    <div class="w-9 h-9 bg-blue-50 group-hover:bg-primary rounded-lg flex items-center justify-center group-hover:text-white text-blue-600 transition-colors duration-200">
                        <i class="fas fa-plus text-sm"></i>
                    </div>
                    <span class="ml-3 text-sm font-medium text-gray-700 group-hover:text-primary">Add New Domain</span>
                </a>
                <a href="/groups/create" class="flex items-center p-3 border border-gray-200 hover:border-green-500 hover:bg-green-50 rounded-lg transition-all duration-200 group">
                    <div class="w-9 h-9 bg-green-50 group-hover:bg-green-500 rounded-lg flex items-center justify-center group-hover:text-white text-green-600 transition-colors duration-200">
                        <i class="fas fa-bell text-sm"></i>
                    </div>
                    <span class="ml-3 text-sm font-medium text-gray-700 group-hover:text-green-700">Create Group</span>
                </a>
                <a href="/debug/whois" class="flex items-center p-3 border border-gray-200 hover:border-purple-500 hover:bg-purple-50 rounded-lg transition-all duration-200 group">
                    <div class="w-9 h-9 bg-purple-50 group-hover:bg-purple-500 rounded-lg flex items-center justify-center group-hover:text-white text-purple-600 transition-colors duration-200">
                        <i class="fas fa-search text-sm"></i>
                    </div>
                    <span class="ml-3 text-sm font-medium text-gray-700 group-hover:text-purple-700">WHOIS Lookup</span>
                </a>
            </div>
        </div>

        <!-- System Status -->
        <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
            <div class="px-5 py-3 border-b border-gray-200">
                <h2 class="text-sm font-semibold text-gray-900 flex items-center">
                    <i class="fas fa-server text-gray-400 mr-2 text-xs"></i>
                    System Status
                </h2>
            </div>
            <div class="p-4 space-y-3">
                <div class="flex items-center justify-between text-sm">
                    <span class="text-gray-600">Database</span>
                    <span class="flex items-center text-green-600 font-medium">
                        <i class="fas fa-circle text-xs mr-1.5"></i>
                        Online
                    </span>
                </div>
                <div class="flex items-center justify-between text-sm">
                    <span class="text-gray-600">WHOIS Service</span>
                    <span class="flex items-center text-green-600 font-medium">
                        <i class="fas fa-circle text-xs mr-1.5"></i>
                        Active
                    </span>
                </div>
                <div class="flex items-center justify-between text-sm">
                    <span class="text-gray-600">Notifications</span>
                    <span class="flex items-center text-green-600 font-medium">
                        <i class="fas fa-circle text-xs mr-1.5"></i>
                        Enabled
                    </span>
                </div>
            </div>
        </div>

        <!-- Expiring This Month -->
        <?php if (!empty($expiringThisMonth)): ?>
        <div class="bg-white rounded-lg border-l-4 border-orange-500 border-t border-r border-b border-gray-200 overflow-hidden">
            <div class="bg-orange-50 px-5 py-3 border-b border-orange-100">
                <h2 class="text-sm font-semibold text-orange-900 flex items-center">
                    <i class="fas fa-exclamation-triangle mr-2 text-xs"></i>
                    Expiring This Month
                </h2>
            </div>
            <div class="p-4 space-y-2.5">
                <?php foreach ($expiringThisMonth as $domain): ?>
                    <div class="flex items-center justify-between p-2 hover:bg-gray-50 rounded transition-colors duration-150">
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 truncate"><?= htmlspecialchars($domain['domain_name']) ?></p>
                            <p class="text-xs text-gray-500"><?= date('M d, Y', strtotime($domain['expiration_date'])) ?></p>
                        </div>
                        <a href="/domains/<?= $domain['id'] ?>" class="ml-2 text-gray-400 hover:text-primary flex-shrink-0">
                            <i class="fas fa-chevron-right text-xs"></i>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>


<?php
$content = ob_get_clean();
include __DIR__ . '/../layout/base.php';
?>

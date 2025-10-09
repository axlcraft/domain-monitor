<?php
$title = 'Domains';
$pageTitle = 'Domain Management';
$pageDescription = 'Monitor and manage your domain portfolio';
$pageIcon = 'fas fa-globe';
ob_start();

// Helper function to generate sort URL
function sortUrl($column, $currentSort, $currentOrder) {
    $newOrder = ($currentSort === $column && $currentOrder === 'asc') ? 'desc' : 'asc';
    $params = $_GET;
    $params['sort'] = $column;
    $params['order'] = $newOrder;
    return '/domains?' . http_build_query($params);
}

// Helper function for sort icon
function sortIcon($column, $currentSort, $currentOrder) {
    if ($currentSort !== $column) {
        return '<i class="fas fa-sort text-gray-400 ml-1 text-xs"></i>';
    }
    $icon = $currentOrder === 'asc' ? 'fa-sort-up' : 'fa-sort-down';
    return '<i class="fas ' . $icon . ' text-primary ml-1 text-xs"></i>';
}

// Get current filters
$currentFilters = $filters ?? ['search' => '', 'status' => '', 'group' => '', 'sort' => 'domain_name', 'order' => 'asc'];
?>

<!-- Action Buttons -->
<div class="mb-4 flex flex-wrap gap-2 justify-between items-center">
    <div class="flex gap-2">
        <!-- Bulk Actions Toolbar (Hidden by default, shown when domains are selected) -->
        <div id="bulk-actions" class="hidden items-center gap-2">
            <span id="selected-count" class="text-sm font-medium text-gray-700"></span>
            
            <button type="button" onclick="bulkRefresh()" class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm rounded-lg hover:bg-green-700 transition-colors font-medium">
                <i class="fas fa-sync-alt mr-2"></i>
                Refresh
            </button>
            
            <div class="relative inline-block">
                <button type="button" onclick="toggleAssignGroupDropdown()" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 transition-colors font-medium">
                    <i class="fas fa-bell mr-2"></i>
                    Assign Group
                    <i class="fas fa-chevron-down ml-2 text-xs"></i>
                </button>
                <div id="assign-group-dropdown" class="hidden absolute left-0 mt-2 w-64 bg-white rounded-lg shadow-lg border border-gray-200 z-10">
                    <form method="POST" action="/domains/bulk-assign-group" id="bulk-assign-form">
                        <?= csrf_field() ?>
                        <input type="hidden" name="domain_ids" id="bulk-assign-ids">
                        <div class="p-3">
                            <select name="group_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                <option value="">-- No Group --</option>
                                <?php foreach ($groups as $group): ?>
                                    <option value="<?= $group['id'] ?>"><?= htmlspecialchars($group['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="border-t border-gray-200 p-2 flex gap-2">
                            <button type="submit" class="flex-1 px-3 py-1.5 bg-primary text-white text-xs rounded hover:bg-primary-dark">
                                Assign
                            </button>
                            <button type="button" onclick="toggleAssignGroupDropdown()" class="flex-1 px-3 py-1.5 bg-gray-200 text-gray-700 text-xs rounded hover:bg-gray-300">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <button type="button" onclick="bulkDelete()" class="inline-flex items-center px-4 py-2 bg-red-600 text-white text-sm rounded-lg hover:bg-red-700 transition-colors font-medium">
                <i class="fas fa-trash mr-2"></i>
                Delete
            </button>
            
            <button type="button" onclick="clearSelection()" class="inline-flex items-center px-4 py-2 border border-gray-300 text-gray-700 text-sm rounded-lg hover:bg-gray-50 transition-colors font-medium">
                <i class="fas fa-times mr-2"></i>
                Clear
            </button>
        </div>
    </div>
    
    <div class="flex gap-2">
        <?php if (!empty($domains)): ?>
        <form method="POST" action="/domains/bulk-refresh" id="refresh-all-form">
            <?= csrf_field() ?>
            <?php foreach ($domains as $domain): ?>
                <input type="hidden" name="domain_ids[]" value="<?= $domain['id'] ?>">
            <?php endforeach; ?>
            <button type="submit" class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm rounded-lg hover:bg-green-700 transition-colors font-medium" title="Refresh all domains on this page">
                <i class="fas fa-sync-alt mr-2"></i>
                Refresh Page (<?= count($domains) ?>)
            </button>
        </form>
        <?php endif; ?>
        <a href="/domains/bulk-add" class="inline-flex items-center px-4 py-2 bg-purple-600 text-white text-sm rounded-lg hover:bg-purple-700 transition-colors font-medium">
            <i class="fas fa-layer-group mr-2"></i>
            Bulk Add
        </a>
        <a href="/domains/create" class="inline-flex items-center px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors text-sm font-medium">
            <i class="fas fa-plus mr-2"></i>
            Add Domain
        </a>
    </div>
</div>

<!-- Filters & Search -->
<div class="bg-white rounded-lg border border-gray-200 p-5 mb-4">
    <form method="GET" action="/domains" id="filter-form">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1.5">Search</label>
                <div class="relative">
                    <input type="text" name="search" id="domainSearch" value="<?= htmlspecialchars($currentFilters['search']) ?>" placeholder="Search domains..." class="w-full pl-9 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary text-sm">
                    <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 text-xs"></i>
                </div>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1.5">Status</label>
                <select name="status" id="statusFilter" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary text-sm">
                    <option value="">All Statuses</option>
                    <option value="active" <?= $currentFilters['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="expiring_soon" <?= $currentFilters['status'] === 'expiring_soon' ? 'selected' : '' ?>>Expiring Soon</option>
                    <option value="inactive" <?= $currentFilters['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1.5">Group</label>
                <select name="group" id="groupFilter" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary text-sm">
                    <option value="">All Groups</option>
                    <?php foreach ($groups as $group): ?>
                        <option value="<?= $group['id'] ?>" <?= $currentFilters['group'] == $group['id'] ? 'selected' : '' ?>><?= htmlspecialchars($group['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="flex items-end space-x-2">
                <button type="submit" class="flex-1 px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors text-sm font-medium">
                    <i class="fas fa-filter mr-2"></i>
                    Apply Filters
                </button>
                <a href="/domains" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors text-sm font-medium">
                    <i class="fas fa-times mr-2"></i>
                    Clear
                </a>
            </div>
        </div>
        <input type="hidden" name="sort" value="<?= htmlspecialchars($currentFilters['sort']) ?>">
        <input type="hidden" name="order" value="<?= htmlspecialchars($currentFilters['order']) ?>">
    </form>
</div>

<!-- Pagination Info & Per Page Selector -->
<div class="mb-4 flex justify-between items-center">
    <div class="text-sm text-gray-600">
        Showing <span class="font-semibold text-gray-900"><?= $pagination['showing_from'] ?></span> to 
        <span class="font-semibold text-gray-900"><?= $pagination['showing_to'] ?></span> of 
        <span class="font-semibold text-gray-900"><?= $pagination['total'] ?></span> domain(s)
    </div>
    
    <form method="GET" action="/domains" class="flex items-center gap-2">
        <!-- Preserve current filters -->
        <input type="hidden" name="search" value="<?= htmlspecialchars($currentFilters['search']) ?>">
        <input type="hidden" name="status" value="<?= htmlspecialchars($currentFilters['status']) ?>">
        <input type="hidden" name="group" value="<?= htmlspecialchars($currentFilters['group']) ?>">
        <input type="hidden" name="sort" value="<?= htmlspecialchars($currentFilters['sort']) ?>">
        <input type="hidden" name="order" value="<?= htmlspecialchars($currentFilters['order']) ?>">
        
        <label for="per_page" class="text-sm text-gray-600">Show:</label>
        <select name="per_page" id="per_page" onchange="this.form.submit()" class="px-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary focus:border-primary">
            <option value="10" <?= $pagination['per_page'] == 10 ? 'selected' : '' ?>>10</option>
            <option value="25" <?= $pagination['per_page'] == 25 ? 'selected' : '' ?>>25</option>
            <option value="50" <?= $pagination['per_page'] == 50 ? 'selected' : '' ?>>50</option>
            <option value="100" <?= $pagination['per_page'] == 100 ? 'selected' : '' ?>>100</option>
        </select>
    </form>
</div>

<!-- Domains List -->
<div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
    <?php if (!empty($domains)): ?>
        <!-- Table View (Desktop) -->
        <div class="hidden lg:block overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left w-12">
                            <input type="checkbox" id="select-all" onclick="toggleSelectAll(this)" class="w-4 h-4 text-primary border-gray-300 rounded focus:ring-primary cursor-pointer">
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                            <a href="<?= sortUrl('domain_name', $currentFilters['sort'], $currentFilters['order']) ?>" class="hover:text-primary flex items-center">
                                Domain <?= sortIcon('domain_name', $currentFilters['sort'], $currentFilters['order']) ?>
                            </a>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                            <a href="<?= sortUrl('registrar', $currentFilters['sort'], $currentFilters['order']) ?>" class="hover:text-primary flex items-center">
                                Registrar <?= sortIcon('registrar', $currentFilters['sort'], $currentFilters['order']) ?>
                            </a>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                            <a href="<?= sortUrl('expiration_date', $currentFilters['sort'], $currentFilters['order']) ?>" class="hover:text-primary flex items-center">
                                Expiration <?= sortIcon('expiration_date', $currentFilters['sort'], $currentFilters['order']) ?>
                            </a>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                            <a href="<?= sortUrl('status', $currentFilters['sort'], $currentFilters['order']) ?>" class="hover:text-primary flex items-center">
                                Status <?= sortIcon('status', $currentFilters['sort'], $currentFilters['order']) ?>
                            </a>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                            <a href="<?= sortUrl('group_name', $currentFilters['sort'], $currentFilters['order']) ?>" class="hover:text-primary flex items-center">
                                Group <?= sortIcon('group_name', $currentFilters['sort'], $currentFilters['order']) ?>
                            </a>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                            <a href="<?= sortUrl('last_checked', $currentFilters['sort'], $currentFilters['order']) ?>" class="hover:text-primary flex items-center">
                                Last Checked <?= sortIcon('last_checked', $currentFilters['sort'], $currentFilters['order']) ?>
                            </a>
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($domains as $domain): ?>
                        <?php
                        // Display data prepared by DomainHelper in controller
                        $daysLeft = $domain['daysLeft'];
                        $expiryClass = $domain['expiryClass'];
                        $statusClass = $domain['statusClass'];
                        $statusText = $domain['statusText'];
                        $statusIcon = $domain['statusIcon'];
                        ?>
                        <tr class="hover:bg-gray-50 transition-colors duration-150 domain-row">
                            <td class="px-4 py-4">
                                <input type="checkbox" class="domain-checkbox w-4 h-4 text-primary border-gray-300 rounded focus:ring-primary cursor-pointer" value="<?= $domain['id'] ?>">
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10 bg-primary bg-opacity-10 rounded-lg flex items-center justify-center">
                                        <i class="fas fa-globe text-primary"></i>
                                    </div>
                                    <div class="ml-4">
                                        <a href="/domains/<?= $domain['id'] ?>" class="text-sm font-semibold text-gray-900 hover:text-primary"><?= htmlspecialchars($domain['domain_name']) ?></a>
                                        <?php if (!empty($domain['nameservers'])): ?>
                                            <div class="text-xs text-gray-500">NS: <?= htmlspecialchars(explode(',', $domain['nameservers'])[0]) ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php if (!empty($domain['registrar'])): ?>
                                    <div class="flex items-center">
                                        <i class="fas fa-building text-gray-400 mr-2"></i>
                                        <span class="text-sm text-gray-900"><?= htmlspecialchars($domain['registrar']) ?></span>
                                    </div>
                                <?php else: ?>
                                    <span class="text-sm text-gray-400">Unknown</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php if (!empty($domain['expiration_date'])): ?>
                                    <div class="text-sm">
                                        <div class="font-medium text-gray-900"><?= date('M d, Y', strtotime($domain['expiration_date'])) ?></div>
                                        <div class="text-xs <?= $expiryClass ?>">
                                            <?= $daysLeft ?> days
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <span class="text-sm text-gray-400">Not set</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold border <?= $statusClass ?>">
                                    <i class="fas <?= $statusIcon ?> mr-1"></i>
                                    <?= $statusText ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?php if (!empty($domain['group_name'])): ?>
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        <i class="fas fa-bell mr-1"></i>
                                        <?= htmlspecialchars($domain['group_name']) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-gray-400">No Group</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php if (!empty($domain['last_checked'])): ?>
                                    <div class="flex items-center">
                                        <i class="far fa-clock mr-2"></i>
                                        <?= date('M d, H:i', strtotime($domain['last_checked'])) ?>
                                    </div>
                                <?php else: ?>
                                    <span class="text-gray-400">Never</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex items-center justify-end space-x-2">
                                    <a href="/domains/<?= $domain['id'] ?>" class="text-blue-600 hover:text-blue-800" title="View">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <form method="POST" action="/domains/<?= $domain['id'] ?>/refresh" class="inline">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="text-green-600 hover:text-green-800" title="Refresh WHOIS">
                                            <i class="fas fa-sync-alt"></i>
                                        </button>
                                    </form>
                                    <a href="/domains/<?= $domain['id'] ?>/edit" class="text-yellow-600 hover:text-yellow-800" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form method="POST" action="/domains/<?= $domain['id'] ?>/delete" class="inline" onsubmit="return confirm('Delete this domain?')">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="text-red-600 hover:text-red-800" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Card View (Mobile) - Simplified for brevity -->
        <div class="lg:hidden divide-y divide-gray-200">
            <?php foreach ($domains as $domain): ?>
                <div class="p-6 hover:bg-gray-50 transition-colors duration-150">
                    <div class="flex items-center mb-3">
                        <input type="checkbox" class="domain-checkbox-mobile w-4 h-4 text-primary border-gray-300 rounded focus:ring-primary cursor-pointer mr-3" value="<?= $domain['id'] ?>">
                        <a href="/domains/<?= $domain['id'] ?>" class="text-lg font-semibold text-gray-900 hover:text-primary"><?= htmlspecialchars($domain['domain_name']) ?></a>
                    </div>
                    <!-- Add mobile view content here if needed -->
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="text-center py-12 px-6">
            <div class="mb-4">
                <i class="fas fa-globe text-gray-300 text-6xl"></i>
            </div>
            <h3 class="text-lg font-semibold text-gray-700 mb-1">No Domains Yet</h3>
            <p class="text-sm text-gray-500 mb-4">Start monitoring your domains by adding your first one</p>
            <a href="/domains/create" class="inline-flex items-center px-5 py-2.5 bg-primary text-white text-sm rounded-lg hover:bg-primary-dark transition-colors font-medium">
                <i class="fas fa-plus mr-2"></i>
                <span>Add Your First Domain</span>
            </a>
        </div>
    <?php endif; ?>
</div>

<!-- Pagination Controls -->
<?php if ($pagination['total_pages'] > 1): ?>
<div class="mt-4 flex flex-col sm:flex-row items-center justify-between gap-4">
    <!-- Page Info -->
    <div class="text-sm text-gray-600">
        Page <span class="font-semibold text-gray-900"><?= $pagination['current_page'] ?></span> of 
        <span class="font-semibold text-gray-900"><?= $pagination['total_pages'] ?></span>
    </div>
    
    <!-- Pagination Buttons -->
    <div class="flex items-center gap-1">
        <?php
        $currentPage = $pagination['current_page'];
        $totalPages = $pagination['total_pages'];
        
        // Helper function to build pagination URL
        function paginationUrl($page, $filters, $perPage) {
            $params = $filters;
            $params['page'] = $page;
            $params['per_page'] = $perPage;
            return '/domains?' . http_build_query($params);
        }
        ?>
        
        <!-- First Page -->
        <?php if ($currentPage > 1): ?>
            <a href="<?= paginationUrl(1, $currentFilters, $pagination['per_page']) ?>" class="px-3 py-2 text-sm border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                <i class="fas fa-angle-double-left"></i>
            </a>
        <?php endif; ?>
        
        <!-- Previous Page -->
        <?php if ($currentPage > 1): ?>
            <a href="<?= paginationUrl($currentPage - 1, $currentFilters, $pagination['per_page']) ?>" class="px-3 py-2 text-sm border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                <i class="fas fa-angle-left"></i> Previous
            </a>
        <?php endif; ?>
        
        <!-- Page Numbers -->
        <?php
        $range = 2; // Show 2 pages on each side of current page
        $start = max(1, $currentPage - $range);
        $end = min($totalPages, $currentPage + $range);
        
        // Show first page + ellipsis if needed
        if ($start > 1) {
            echo '<a href="' . paginationUrl(1, $currentFilters, $pagination['per_page']) . '" class="px-3 py-2 text-sm border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">1</a>';
            if ($start > 2) {
                echo '<span class="px-2 text-gray-500">...</span>';
            }
        }
        
        // Page numbers
        for ($i = $start; $i <= $end; $i++) {
            if ($i == $currentPage) {
                echo '<span class="px-3 py-2 text-sm bg-primary text-white rounded-lg font-semibold">' . $i . '</span>';
            } else {
                echo '<a href="' . paginationUrl($i, $currentFilters, $pagination['per_page']) . '" class="px-3 py-2 text-sm border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">' . $i . '</a>';
            }
        }
        
        // Show last page + ellipsis if needed
        if ($end < $totalPages) {
            if ($end < $totalPages - 1) {
                echo '<span class="px-2 text-gray-500">...</span>';
            }
            echo '<a href="' . paginationUrl($totalPages, $currentFilters, $pagination['per_page']) . '" class="px-3 py-2 text-sm border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">' . $totalPages . '</a>';
        }
        ?>
        
        <!-- Next Page -->
        <?php if ($currentPage < $totalPages): ?>
            <a href="<?= paginationUrl($currentPage + 1, $currentFilters, $pagination['per_page']) ?>" class="px-3 py-2 text-sm border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                Next <i class="fas fa-angle-right"></i>
            </a>
        <?php endif; ?>
        
        <!-- Last Page -->
        <?php if ($currentPage < $totalPages): ?>
            <a href="<?= paginationUrl($totalPages, $currentFilters, $pagination['per_page']) ?>" class="px-3 py-2 text-sm border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                <i class="fas fa-angle-double-right"></i>
            </a>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<script>
// Multi-select functionality
function toggleSelectAll(checkbox) {
    // Only select checkboxes that are currently visible
    const desktopCheckboxes = document.querySelectorAll('.domain-checkbox');
    const mobileCheckboxes = document.querySelectorAll('.domain-checkbox-mobile');
    
    // Check if desktop view is visible (lg:block class)
    const desktopTable = document.querySelector('.hidden.lg\\:block');
    const isDesktopVisible = desktopTable && !desktopTable.classList.contains('hidden');
    
    if (isDesktopVisible) {
        // Desktop view is visible, select desktop checkboxes
        desktopCheckboxes.forEach(cb => cb.checked = checkbox.checked);
    } else {
        // Mobile view is visible, select mobile checkboxes
        mobileCheckboxes.forEach(cb => cb.checked = checkbox.checked);
    }
    
    updateBulkActions();
}

function updateBulkActions() {
    // Only count checkboxes that are currently visible
    const desktopCheckboxes = document.querySelectorAll('.domain-checkbox:checked');
    const mobileCheckboxes = document.querySelectorAll('.domain-checkbox-mobile:checked');
    
    // Check if desktop view is visible
    const desktopTable = document.querySelector('.hidden.lg\\:block');
    const isDesktopVisible = desktopTable && !desktopTable.classList.contains('hidden');
    
    const checkboxes = isDesktopVisible ? desktopCheckboxes : mobileCheckboxes;
    const bulkActions = document.getElementById('bulk-actions');
    const selectedCount = document.getElementById('selected-count');
    
    if (checkboxes.length > 0) {
        bulkActions.classList.remove('hidden');
        bulkActions.classList.add('flex');
        selectedCount.textContent = `${checkboxes.length} selected`;
    } else {
        bulkActions.classList.add('hidden');
        bulkActions.classList.remove('flex');
    }
}

function clearSelection() {
    const desktopCheckboxes = document.querySelectorAll('.domain-checkbox');
    const mobileCheckboxes = document.querySelectorAll('.domain-checkbox-mobile');
    
    // Check if desktop view is visible
    const desktopTable = document.querySelector('.hidden.lg\\:block');
    const isDesktopVisible = desktopTable && !desktopTable.classList.contains('hidden');
    
    if (isDesktopVisible) {
        desktopCheckboxes.forEach(cb => cb.checked = false);
    } else {
        mobileCheckboxes.forEach(cb => cb.checked = false);
    }
    
    document.getElementById('select-all').checked = false;
    updateBulkActions();
}

function getSelectedIds() {
    // Only get IDs from currently visible checkboxes
    const desktopCheckboxes = document.querySelectorAll('.domain-checkbox:checked');
    const mobileCheckboxes = document.querySelectorAll('.domain-checkbox-mobile:checked');
    
    // Check if desktop view is visible
    const desktopTable = document.querySelector('.hidden.lg\\:block');
    const isDesktopVisible = desktopTable && !desktopTable.classList.contains('hidden');
    
    const checkboxes = isDesktopVisible ? desktopCheckboxes : mobileCheckboxes;
    return Array.from(checkboxes).map(cb => cb.value);
}

function bulkRefresh() {
    const ids = getSelectedIds();
    if (ids.length === 0) return;
    
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '/domains/bulk-refresh';
    
    ids.forEach(id => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'domain_ids[]';
        input.value = id;
        form.appendChild(input);
    });
    
    document.body.appendChild(form);
    form.submit();
}

function bulkDelete() {
    const ids = getSelectedIds();
    if (ids.length === 0) return;
    
    if (!confirm(`Delete ${ids.length} domain(s)? This action cannot be undone.`)) {
        return;
    }
    
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '/domains/bulk-delete';
    
    ids.forEach(id => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'domain_ids[]';
        input.value = id;
        form.appendChild(input);
    });
    
    document.body.appendChild(form);
    form.submit();
}

function toggleAssignGroupDropdown() {
    const dropdown = document.getElementById('assign-group-dropdown');
    dropdown.classList.toggle('hidden');
}

// Update bulk assign form with selected IDs
document.getElementById('bulk-assign-form')?.addEventListener('submit', function(e) {
    const ids = getSelectedIds();
    const container = this;
    
    // Clear existing hidden inputs
    container.querySelectorAll('input[name="domain_ids[]"]').forEach(el => el.remove());
    
    // Add selected IDs
    ids.forEach(id => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'domain_ids[]';
        input.value = id;
        container.appendChild(input);
    });
});

// Listen to checkbox changes
document.querySelectorAll('.domain-checkbox, .domain-checkbox-mobile').forEach(checkbox => {
    checkbox.addEventListener('change', function() {
        // Update the select-all checkbox state
        const desktopCheckboxes = document.querySelectorAll('.domain-checkbox');
        const mobileCheckboxes = document.querySelectorAll('.domain-checkbox-mobile');
        
        // Check if desktop view is visible
        const desktopTable = document.querySelector('.hidden.lg\\:block');
        const isDesktopVisible = desktopTable && !desktopTable.classList.contains('hidden');
        
        const checkboxes = isDesktopVisible ? desktopCheckboxes : mobileCheckboxes;
        const checkedBoxes = isDesktopVisible ? 
            document.querySelectorAll('.domain-checkbox:checked') : 
            document.querySelectorAll('.domain-checkbox-mobile:checked');
        
        const selectAllCheckbox = document.getElementById('select-all');
        if (checkedBoxes.length === 0) {
            selectAllCheckbox.checked = false;
            selectAllCheckbox.indeterminate = false;
        } else if (checkedBoxes.length === checkboxes.length) {
            selectAllCheckbox.checked = true;
            selectAllCheckbox.indeterminate = false;
        } else {
            selectAllCheckbox.checked = false;
            selectAllCheckbox.indeterminate = true;
        }
        
        updateBulkActions();
    });
});

// Close dropdown when clicking outside
document.addEventListener('click', function(event) {
    const dropdown = document.getElementById('assign-group-dropdown');
    const button = event.target.closest('button[onclick="toggleAssignGroupDropdown()"]');
    
    if (!button && !dropdown.contains(event.target)) {
        dropdown?.classList.add('hidden');
    }
});

// Handle window resize to sync checkboxes when switching between desktop/mobile views
window.addEventListener('resize', function() {
    // Small delay to allow CSS classes to update
    setTimeout(updateBulkActions, 100);
});
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout/base.php';
?>

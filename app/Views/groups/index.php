<?php
$title = 'Notification Groups';
$pageTitle = 'Notification Groups';
$pageDescription = 'Manage notification channels and assignments';
$pageIcon = 'fas fa-bell';
ob_start();
?>

<!-- Quick Actions -->
<div class="mb-4 flex justify-end">
    <a href="/groups/create" class="inline-flex items-center px-4 py-2.5 bg-primary text-white text-sm rounded-lg hover:bg-primary-dark transition-colors font-medium">
        <i class="fas fa-plus mr-2"></i>
        Create New Group
    </a>
</div>

<!-- Info Card -->
<div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
    <div class="flex items-start">
        <div class="flex-shrink-0">
            <i class="fas fa-info-circle text-blue-500 text-lg"></i>
        </div>
        <div class="ml-3">
            <h3 class="text-sm font-semibold text-gray-900 mb-1">About Notification Groups</h3>
            <p class="text-xs text-gray-600 leading-relaxed">
                Notification groups allow you to organize your notification channels. You can create multiple channels 
                (Email, Telegram, Discord, Slack) within each group, then assign domains to the group. When a domain 
                is about to expire, all active channels in its group will receive notifications.
            </p>
        </div>
    </div>
</div>

<!-- Groups List -->
<div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
    <?php if (!empty($groups)): ?>
        <!-- Table View (Desktop) -->
        <div class="hidden md:block overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Group Name</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Description</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Channels</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Domains</th>
                        <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($groups as $group): ?>
                        <tr class="hover:bg-gray-50 transition-colors duration-150">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10 bg-primary bg-opacity-10 rounded-lg flex items-center justify-center">
                                        <i class="fas fa-bell text-primary"></i>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-semibold text-gray-900"><?= htmlspecialchars($group['name']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-700 max-w-xs truncate">
                                    <?= htmlspecialchars($group['description'] ?? 'No description') ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-800">
                                    <i class="fas fa-plug mr-1"></i>
                                    <?= $group['channel_count'] ?> channel<?= $group['channel_count'] != 1 ? 's' : '' ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">
                                    <i class="fas fa-globe mr-1"></i>
                                    <?= $group['domain_count'] ?> domain<?= $group['domain_count'] != 1 ? 's' : '' ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex items-center justify-end space-x-2">
                                    <a href="/groups/edit?id=<?= $group['id'] ?>" class="text-blue-600 hover:text-blue-800" title="Manage">
                                        <i class="fas fa-cog"></i>
                                    </a>
                                    <a href="/groups/delete?id=<?= $group['id'] ?>" 
                                       class="text-red-600 hover:text-red-800" 
                                       title="Delete"
                                       onclick="return confirm('Are you sure? Domains will be unassigned from this group.')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Card View (Mobile) -->
        <div class="md:hidden divide-y divide-gray-200">
            <?php foreach ($groups as $group): ?>
                <div class="p-6 hover:bg-gray-50 transition-colors duration-150">
                    <div class="flex items-start justify-between mb-3">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 h-10 w-10 bg-primary bg-opacity-10 rounded-lg flex items-center justify-center">
                                <i class="fas fa-bell text-primary"></i>
                            </div>
                            <div class="ml-3">
                                <h3 class="font-semibold text-gray-900"><?= htmlspecialchars($group['name']) ?></h3>
                                <p class="text-sm text-gray-500"><?= htmlspecialchars($group['description'] ?? 'No description') ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex space-x-3 mb-3">
                        <span class="px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            <i class="fas fa-plug mr-1"></i>
                            <?= $group['channel_count'] ?> channels
                        </span>
                        <span class="px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            <i class="fas fa-globe mr-1"></i>
                            <?= $group['domain_count'] ?> domains
                        </span>
                    </div>
                    
                    <div class="flex space-x-2">
                        <a href="/groups/edit?id=<?= $group['id'] ?>" class="flex-1 px-3 py-1.5 bg-blue-50 text-blue-600 rounded text-center text-sm hover:bg-blue-100 transition-colors">
                            <i class="fas fa-cog mr-1"></i> Manage
                        </a>
                        <a href="/groups/delete?id=<?= $group['id'] ?>" 
                           class="flex-1 px-3 py-1.5 bg-red-50 text-red-600 rounded text-center text-sm hover:bg-red-100 transition-colors"
                           onclick="return confirm('Are you sure? Domains will be unassigned from this group.')">
                            <i class="fas fa-trash mr-1"></i> Delete
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="text-center py-12 px-6">
            <div class="mb-4">
                <i class="fas fa-bell-slash text-gray-300 text-6xl"></i>
            </div>
            <h3 class="text-lg font-semibold text-gray-700 mb-1">No Notification Groups</h3>
            <p class="text-sm text-gray-500 mb-4">Create your first notification group to start receiving alerts</p>
            <a href="/groups/create" class="inline-flex items-center px-5 py-2.5 bg-primary text-white text-sm rounded-lg hover:bg-primary-dark transition-colors font-medium">
                <i class="fas fa-plus mr-2"></i>
                Create Your First Group
            </a>
        </div>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout/base.php';
?>

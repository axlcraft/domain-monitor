<?php
$title = 'Edit Domain';
$pageTitle = 'Edit Domain';
$pageDescription = htmlspecialchars($domain['domain_name']);
$pageIcon = 'fas fa-edit';
ob_start();
?>

<!-- Main Form -->
<div class="max-w-3xl mx-auto">
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900 flex items-center">
                <i class="fas fa-cog text-gray-400 mr-2 text-sm"></i>
                Domain Settings
            </h2>
        </div>
        
        <div class="p-6">
            <form method="POST" action="/domains/<?= $domain['id'] ?>/update" class="space-y-5">
                <?= csrf_field() ?>

                <!-- Domain Name (Read-only) -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">
                        Domain Name
                    </label>
                    <div class="relative">
                        <input type="text" 
                               class="w-full px-3 py-2.5 border border-gray-300 rounded-lg bg-gray-50 text-gray-600 cursor-not-allowed text-sm" 
                               value="<?= htmlspecialchars($domain['domain_name']) ?>" 
                               disabled>
                        <div class="absolute right-3 top-1/2 transform -translate-y-1/2">
                            <i class="fas fa-lock text-gray-400 text-xs"></i>
                        </div>
                    </div>
                    <p class="mt-1.5 text-xs text-gray-500">
                        Domain name cannot be changed after creation
                    </p>
                </div>

                <!-- Notification Group -->
                <div>
                    <label for="notification_group_id" class="block text-sm font-medium text-gray-700 mb-1.5">
                        Notification Group
                    </label>
                    <select id="notification_group_id" 
                            name="notification_group_id" 
                            class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition-colors text-sm">
                        <option value="">-- No Group (No notifications) --</option>
                        <?php foreach ($groups as $group): ?>
                            <option value="<?= $group['id'] ?>" 
                                    <?= $domain['notification_group_id'] == $group['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($group['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="mt-1.5 text-xs text-gray-500">
                        Change the notification group or remove it to stop receiving alerts
                    </p>
                </div>

                <!-- Active Monitoring -->
                <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                    <label class="flex items-center cursor-pointer">
                        <input type="checkbox" 
                               name="is_active" 
                               <?= $domain['is_active'] ? 'checked' : '' ?> 
                               class="w-4 h-4 text-primary border-gray-300 rounded focus:ring-primary cursor-pointer">
                        <div class="ml-3">
                            <span class="text-sm font-medium text-gray-900">Enable Active Monitoring</span>
                            <p class="text-xs text-gray-600 mt-0.5">When enabled, this domain will be checked regularly and notifications will be sent</p>
                        </div>
                    </label>
                </div>

                <!-- Action Buttons -->
                <div class="flex flex-col sm:flex-row gap-3 pt-3">
                    <button type="submit" 
                            class="inline-flex items-center justify-center px-5 py-2.5 bg-primary hover:bg-primary-dark text-white rounded-lg font-medium transition-colors text-sm">
                        <i class="fas fa-save mr-2"></i>
                        Update Domain
                    </button>
                    <a href="/domains/<?= $domain['id'] ?>" 
                       class="inline-flex items-center justify-center px-5 py-2.5 border border-gray-300 text-gray-700 rounded-lg font-medium hover:bg-gray-50 transition-colors text-sm">
                        <i class="fas fa-times mr-2"></i>
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-3">
        <a href="/domains/<?= $domain['id'] ?>" 
           class="flex items-center justify-center p-3 bg-white border border-gray-200 rounded-lg hover:border-blue-300 hover:bg-blue-50 transition-colors group">
            <i class="fas fa-eye text-blue-600 mr-2 text-sm"></i>
            <span class="text-sm font-medium text-gray-700">View Details</span>
        </a>
        <form method="POST" action="/domains/<?= $domain['id'] ?>/refresh" class="m-0">
            <?= csrf_field() ?>
            <button type="submit" 
                    class="w-full flex items-center justify-center p-3 bg-white border border-gray-200 rounded-lg hover:border-green-300 hover:bg-green-50 transition-colors group">
                <i class="fas fa-sync-alt text-green-600 mr-2 text-sm"></i>
                <span class="text-sm font-medium text-gray-700">Refresh WHOIS</span>
            </button>
        </form>
        <form method="POST" action="/domains/<?= $domain['id'] ?>/delete" onsubmit="return confirm('Delete this domain permanently?')" class="m-0">
            <?= csrf_field() ?>
            <button type="submit" 
                    class="w-full flex items-center justify-center p-3 bg-white border border-gray-200 rounded-lg hover:border-red-300 hover:bg-red-50 transition-colors group">
                <i class="fas fa-trash text-red-600 mr-2 text-sm"></i>
                <span class="text-sm font-medium text-gray-700">Delete Domain</span>
            </button>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout/base.php';
?>

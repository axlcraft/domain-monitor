<?php
$title = 'Bulk Add Domains';
$pageTitle = 'Bulk Add Domains';
$pageDescription = 'Add multiple domains at once';
$pageIcon = 'fas fa-layer-group';
ob_start();
?>

<!-- Main Form -->
<div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900 flex items-center">
                <i class="fas fa-layer-group text-gray-400 mr-2 text-sm"></i>
                Bulk Add Domains
            </h2>
        </div>
        
        <div class="p-6">
            <form method="POST" action="/domains/bulk-add" class="space-y-5">
                <?= csrf_field() ?>
                <!-- Domains Textarea -->
                <div>
                    <label for="domains" class="block text-sm font-medium text-gray-700 mb-1.5">
                        Domain Names *
                    </label>
                    <textarea 
                        id="domains" 
                        name="domains" 
                        rows="10"
                        class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition-colors text-sm font-mono" 
                        placeholder="example.com&#10;google.com&#10;github.com&#10;..."
                        required
                        autofocus></textarea>
                    <p class="mt-1.5 text-xs text-gray-500">
                        Enter one domain per line. Domains without http:// or www.
                    </p>
                </div>

                <!-- Notification Group -->
                <div>
                    <label for="notification_group_id" class="block text-sm font-medium text-gray-700 mb-1.5">
                        Notification Group (Optional)
                    </label>
                    <select id="notification_group_id" 
                            name="notification_group_id" 
                            class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition-colors text-sm">
                        <option value="">-- No Group (No notifications) --</option>
                        <?php foreach ($groups as $group): ?>
                            <option value="<?= $group['id'] ?>"><?= htmlspecialchars($group['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <p class="mt-1.5 text-xs text-gray-500">
                        Assign all domains to this notification group
                    </p>
                </div>

                <!-- Action Buttons -->
                <div class="flex flex-col sm:flex-row gap-3 pt-3">
                    <button type="submit" 
                            class="inline-flex items-center justify-center px-5 py-2.5 bg-primary hover:bg-primary-dark text-white rounded-lg font-medium transition-colors text-sm">
                        <i class="fas fa-plus-circle mr-2"></i>
                        Add All Domains
                    </button>
                    <a href="/domains" 
                       class="inline-flex items-center justify-center px-5 py-2.5 border border-gray-300 text-gray-700 rounded-lg font-medium hover:bg-gray-50 transition-colors text-sm">
                        <i class="fas fa-times mr-2"></i>
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Info Cards -->
    <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- How it works -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <div class="w-10 h-10 bg-blue-500 rounded-lg flex items-center justify-center">
                        <i class="fas fa-info-circle text-white"></i>
                    </div>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-semibold text-gray-900 mb-1">How It Works</h3>
                    <p class="text-xs text-gray-600 leading-relaxed">
                        Paste multiple domain names, one per line. The system will fetch WHOIS information 
                        for each domain automatically. This may take a few moments depending on how many domains you're adding.
                    </p>
                </div>
            </div>
        </div>

        <!-- Important notes -->
        <div class="bg-orange-50 border border-orange-200 rounded-lg p-4">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <div class="w-10 h-10 bg-orange-500 rounded-lg flex items-center justify-center">
                        <i class="fas fa-exclamation-triangle text-white"></i>
                    </div>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-semibold text-gray-900 mb-1">Important Notes</h3>
                    <ul class="text-xs text-gray-600 space-y-1">
                        <li class="flex items-start">
                            <i class="fas fa-circle text-orange-500 mt-1 mr-2" style="font-size: 6px;"></i>
                            <span>Duplicate domains will be skipped</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-circle text-orange-500 mt-1 mr-2" style="font-size: 6px;"></i>
                            <span>Invalid domains will be reported</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-circle text-orange-500 mt-1 mr-2" style="font-size: 6px;"></i>
                            <span>Large batches may take several minutes</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout/base.php';
?>


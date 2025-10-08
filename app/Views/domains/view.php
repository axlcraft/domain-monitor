<?php
$title = 'Domain Details';
$pageTitle = htmlspecialchars($domain['domain_name']);
$pageDescription = 'Domain information and monitoring status';
$pageIcon = 'fas fa-globe';
$whoisData = json_decode($domain['whois_data'] ?? '{}', true);
$daysLeft = !empty($domain['expiration_date']) ? floor((strtotime($domain['expiration_date']) - time()) / 86400) : null;

// Recalculate domain status if it's empty or error (for backward compatibility)
$domainStatus = $domain['status'];
if (empty($domainStatus) || $domainStatus === 'error') {
    // Check WHOIS data for AVAILABLE status
    $statusArray = $whoisData['status'] ?? [];
    $isAvailable = false;
    foreach ($statusArray as $status) {
        if (stripos($status, 'AVAILABLE') !== false || stripos($status, 'FREE') !== false) {
            $isAvailable = true;
            break;
        }
    }
    
    if ($isAvailable) {
        $domainStatus = 'available';
    } elseif ($daysLeft !== null) {
        if ($daysLeft < 0) {
            $domainStatus = 'expired';
        } elseif ($daysLeft <= 30) {
            $domainStatus = 'expiring_soon';
        } else {
            $domainStatus = 'active';
        }
    } else {
        $domainStatus = 'error';
    }
}

// Determine expiry color
$expiryColor = 'green';
if ($daysLeft !== null) {
    if ($daysLeft < 0) $expiryColor = 'red';
    elseif ($daysLeft <= 30) $expiryColor = 'orange';
    elseif ($daysLeft <= 90) $expiryColor = 'yellow';
}

ob_start();
?>

<!-- Top Action Bar -->
<div class="mb-3 flex flex-wrap gap-2 justify-between items-center">
    <div class="flex gap-2">
        <?php
        // Determine domain status badge
        if ($domainStatus === 'available') {
            $statusClass = 'bg-blue-100 text-blue-700 border-blue-200';
            $statusText = 'Available (Not Registered)';
            $statusIcon = 'fa-info-circle';
        } elseif ($domainStatus === 'expired') {
            $statusClass = 'bg-red-100 text-red-700 border-red-200';
            $statusText = 'Expired';
            $statusIcon = 'fa-times-circle';
        } elseif ($domainStatus === 'expiring_soon' || ($daysLeft !== null && $daysLeft <= 30 && $daysLeft >= 0)) {
            $statusClass = 'bg-orange-100 text-orange-700 border-orange-200';
            $statusText = 'Expiring Soon';
            $statusIcon = 'fa-exclamation-triangle';
        } elseif ($domainStatus === 'active') {
            $statusClass = 'bg-green-100 text-green-700 border-green-200';
            $statusText = 'Active';
            $statusIcon = 'fa-check-circle';
        } elseif ($domainStatus === 'error') {
            $statusClass = 'bg-gray-100 text-gray-700 border-gray-200';
            $statusText = 'Error';
            $statusIcon = 'fa-exclamation-circle';
        } else {
            $statusClass = 'bg-gray-100 text-gray-700 border-gray-200';
            $statusText = ucfirst($domainStatus);
            $statusIcon = 'fa-question-circle';
        }
        ?>
        <span class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-semibold <?= $statusClass ?>">
            <i class="fas <?= $statusIcon ?> mr-1.5"></i>
            <?= $statusText ?>
        </span>
        <?php if ($domainStatus !== 'available'): ?>
        <span class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-semibold bg-<?= $expiryColor ?>-100 text-<?= $expiryColor ?>-800 border border-<?= $expiryColor ?>-200">
            <i class="fas fa-calendar-alt mr-1.5"></i>
            <?= $daysLeft !== null ? $daysLeft . ' days left' : 'No expiry date' ?>
        </span>
        <?php endif; ?>
        <span class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-semibold bg-purple-100 text-purple-800 border border-purple-200">
            <i class="fas fa-<?= $domain['is_active'] ? 'check-circle' : 'pause-circle' ?> mr-1.5"></i>
            <?= $domain['is_active'] ? 'Monitoring Active' : 'Monitoring Paused' ?>
        </span>
    </div>
    <div class="flex gap-2 items-center">
        <form method="POST" action="/domains/<?= $domain['id'] ?>/refresh" class="inline">
            <button type="submit" class="inline-flex items-center justify-center px-3 py-2 bg-green-600 text-white text-xs rounded-lg hover:bg-green-700 transition-colors font-medium min-w-[80px] h-[32px]">
                <i class="fas fa-sync-alt mr-1.5"></i>
                Refresh
            </button>
        </form>
        <a href="/domains/<?= $domain['id'] ?>/edit" class="inline-flex items-center justify-center px-3 py-2 bg-blue-600 text-white text-xs rounded-lg hover:bg-blue-700 transition-colors font-medium min-w-[80px] h-[32px]">
            <i class="fas fa-edit mr-1.5"></i>
            Edit
        </a>
        <form method="POST" action="/domains/<?= $domain['id'] ?>/delete" onsubmit="return confirm('Delete this domain?')" class="inline">
            <button type="submit" class="inline-flex items-center justify-center px-3 py-2 bg-red-600 text-white text-xs rounded-lg hover:bg-red-700 transition-colors font-medium min-w-[80px] h-[32px]">
                <i class="fas fa-trash mr-1.5"></i>
                Delete
            </button>
        </form>
        <a href="/domains" class="inline-flex items-center justify-center px-3 py-2 border border-gray-300 text-gray-700 text-xs rounded-lg hover:bg-gray-50 transition-colors font-medium min-w-[80px] h-[32px]">
            <i class="fas fa-arrow-left mr-1.5"></i>
            Back
        </a>
    </div>
</div>

<!-- Main 2-Column Layout -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-3">
    
    <!-- LEFT COLUMN -->
    <div class="space-y-3">
        
        <!-- Registration Details -->
        <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
            <div class="px-4 py-2 border-b border-gray-200 bg-gray-50">
                <h3 class="text-xs font-semibold text-gray-700 uppercase tracking-wider flex items-center">
                    <i class="fas fa-building text-gray-400 mr-2" style="font-size: 10px;"></i>
                    Registration Details
                </h3>
            </div>
            <div class="p-4">
                <div class="grid grid-cols-2 gap-x-4 gap-y-3 text-xs">
                    <div>
                        <label class="text-gray-500 font-medium block mb-0.5">Registrar</label>
                        <p class="text-gray-900 font-semibold"><?= htmlspecialchars($domain['registrar'] ?? 'Unknown') ?></p>
                    </div>
                    <?php if (!empty($domain['registrar_url'])): ?>
                    <div>
                        <label class="text-gray-500 font-medium block mb-0.5">Registrar URL</label>
                        <a href="<?= htmlspecialchars($domain['registrar_url']) ?>" target="_blank" class="text-blue-600 hover:text-blue-800 flex items-center">
                            <i class="fas fa-external-link-alt mr-1" style="font-size: 9px;"></i>
                            Visit
                        </a>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($domain['abuse_email'])): ?>
                    <div>
                        <label class="text-gray-500 font-medium block mb-0.5">Abuse Contact</label>
                        <a href="mailto:<?= htmlspecialchars($domain['abuse_email']) ?>" class="text-blue-600 hover:text-blue-800">
                            <?= htmlspecialchars($domain['abuse_email']) ?>
                        </a>
                    </div>
                    <?php endif; ?>
                    <?php if (isset($whoisData['whois_server'])): ?>
                    <div>
                        <label class="text-gray-500 font-medium block mb-0.5">WHOIS Server</label>
                        <p class="text-gray-900 font-mono"><?= htmlspecialchars($whoisData['whois_server']) ?></p>
                    </div>
                    <?php endif; ?>
                    <?php if (isset($whoisData['owner'])): ?>
                    <div class="col-span-2">
                        <label class="text-gray-500 font-medium block mb-0.5">Owner</label>
                        <p class="text-gray-900"><?= htmlspecialchars($whoisData['owner']) ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Important Dates -->
        <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
            <div class="px-4 py-2 border-b border-gray-200 bg-gray-50">
                <h3 class="text-xs font-semibold text-gray-700 uppercase tracking-wider flex items-center">
                    <i class="fas fa-calendar text-gray-400 mr-2" style="font-size: 10px;"></i>
                    Important Dates
                </h3>
            </div>
            <div class="p-4">
                <div class="space-y-2">
                    <?php if (!empty($domain['expiration_date'])): ?>
                    <div class="flex items-center justify-between p-2 bg-<?= $expiryColor ?>-50 rounded border border-<?= $expiryColor ?>-200">
                        <div class="flex items-center">
                            <div class="w-7 h-7 bg-<?= $expiryColor ?>-500 rounded flex items-center justify-center mr-2">
                                <i class="fas fa-exclamation-triangle text-white text-xs"></i>
                            </div>
                            <div>
                                <p class="text-xs text-gray-600 font-medium">Expiration</p>
                                <p class="text-xs font-semibold text-gray-900"><?= date('M j, Y', strtotime($domain['expiration_date'])) ?></p>
                            </div>
                        </div>
                        <span class="px-2 py-1 bg-<?= $expiryColor ?>-100 text-<?= $expiryColor ?>-800 rounded text-xs font-bold">
                            <?= $daysLeft ?> days
                        </span>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($domain['updated_date'])): ?>
                    <div class="flex items-center p-2 bg-blue-50 rounded border border-blue-200">
                        <div class="w-7 h-7 bg-blue-500 rounded flex items-center justify-center mr-2">
                            <i class="fas fa-clock text-white text-xs"></i>
                        </div>
                        <div>
                            <p class="text-xs text-gray-600 font-medium">Last Updated</p>
                            <p class="text-xs font-semibold text-gray-900"><?= date('M j, Y', strtotime($domain['updated_date'])) ?></p>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if (isset($whoisData['creation_date'])): ?>
                    <div class="flex items-center p-2 bg-green-50 rounded border border-green-200">
                        <div class="w-7 h-7 bg-green-500 rounded flex items-center justify-center mr-2">
                            <i class="fas fa-calendar-plus text-white text-xs"></i>
                        </div>
                        <div>
                            <p class="text-xs text-gray-600 font-medium">Created</p>
                            <p class="text-xs font-semibold text-gray-900"><?= date('M j, Y', strtotime($whoisData['creation_date'])) ?></p>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="flex items-center p-2 bg-purple-50 rounded border border-purple-200">
                        <div class="w-7 h-7 bg-purple-500 rounded flex items-center justify-center mr-2">
                            <i class="fas fa-sync text-white text-xs"></i>
                        </div>
                        <div>
                            <p class="text-xs text-gray-600 font-medium">Last Checked</p>
                            <p class="text-xs font-semibold text-gray-900"><?= date('M j, Y H:i', strtotime($domain['last_checked'])) ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Nameservers -->
        <?php if (!empty($whoisData['nameservers'])): ?>
        <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
            <div class="px-4 py-2 border-b border-gray-200 bg-gray-50">
                <h3 class="text-xs font-semibold text-gray-700 uppercase tracking-wider flex items-center">
                    <i class="fas fa-server text-gray-400 mr-2" style="font-size: 10px;"></i>
                    Nameservers (<?= count($whoisData['nameservers']) ?>)
                </h3>
            </div>
            <div class="p-4">
                <div class="space-y-1.5">
                    <?php foreach ($whoisData['nameservers'] as $index => $ns): ?>
                    <div class="flex items-center p-2 bg-gray-50 rounded hover:bg-gray-100 transition-colors">
                        <div class="w-6 h-6 bg-teal-500 rounded flex items-center justify-center text-white font-bold text-xs mr-2">
                            <?= $index + 1 ?>
                        </div>
                        <p class="font-mono text-xs text-gray-800"><?= htmlspecialchars($ns) ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Domain Status -->
        <?php if (!empty($whoisData['status']) && is_array($whoisData['status'])): ?>
            <?php
            // Pre-filter to count only valid statuses
            $validStatuses = [];
            foreach ($whoisData['status'] as $status) {
                $cleanStatus = trim($status);
                
                // Skip if it's just a URL or starts with http/https or //
                if (empty($cleanStatus) || 
                    strpos($cleanStatus, 'http') === 0 || 
                    strpos($cleanStatus, '//') === 0 ||
                    strpos($cleanStatus, 'www.') === 0) {
                    continue;
                }
                
                // Keep the full status text, don't split by spaces
                // Skip if after cleaning it's empty or just a URL
                if (empty($cleanStatus) || strpos($cleanStatus, 'http') === 0 || strpos($cleanStatus, '//') === 0) {
                    continue;
                }
                
                $validStatuses[] = $cleanStatus;
            }
            ?>
            <?php if (!empty($validStatuses)): ?>
        <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
            <div class="px-4 py-2 border-b border-gray-200 bg-gray-50">
                <h3 class="text-xs font-semibold text-gray-700 uppercase tracking-wider flex items-center">
                    <i class="fas fa-info-circle text-gray-400 mr-2" style="font-size: 10px;"></i>
                    Domain Status (<?= count($validStatuses) ?>)
                </h3>
            </div>
            <div class="p-4">
                <div class="flex flex-wrap gap-1.5">
                    <?php foreach ($validStatuses as $cleanStatus): ?>
                        <?php
                        // Convert to readable format
                        $readableStatus = $cleanStatus;
                        
                        // Convert camelCase to readable format (for cases like "clientTransferProhibited")
                        $readableStatus = preg_replace('/([a-z])([A-Z])/', '$1 $2', $readableStatus);
                        
                        // Convert underscores to spaces and capitalize words
                        $readableStatus = str_replace('_', ' ', $readableStatus);
                        $readableStatus = ucwords(strtolower($readableStatus));
                        ?>
                    <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded text-xs font-medium" title="<?= htmlspecialchars($cleanStatus) ?>">
                        <?= htmlspecialchars($readableStatus) ?>
                    </span>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
            <?php endif; ?>
        <?php endif; ?>

    </div>

    <!-- RIGHT COLUMN -->
    <div class="space-y-3">
        
        <!-- Notification Group -->
        <?php if (!empty($domain['group_name'])): ?>
        <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
            <div class="px-4 py-2 border-b border-gray-200 bg-gray-50">
                <h3 class="text-xs font-semibold text-gray-700 uppercase tracking-wider flex items-center">
                    <i class="fas fa-bell text-gray-400 mr-2" style="font-size: 10px;"></i>
                    Notification Group
                </h3>
            </div>
            <div class="p-4">
                <div class="flex items-center mb-3">
                    <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center mr-3">
                        <i class="fas fa-users text-green-600"></i>
                    </div>
                    <div>
                        <p class="font-semibold text-sm text-gray-900"><?= htmlspecialchars($domain['group_name']) ?></p>
                        <?php if (!empty($domain['channels'])): ?>
                        <?php 
                        $activeChannels = array_filter($domain['channels'], fn($ch) => $ch['is_active']);
                        ?>
                        <p class="text-xs text-gray-600">
                            <?= count($activeChannels) ?> / <?= count($domain['channels']) ?> channels active
                        </p>
                        <?php endif; ?>
                    </div>
                </div>
                <?php if (!empty($domain['channels'])): ?>
                <div class="grid grid-cols-2 gap-2">
                    <?php foreach ($domain['channels'] as $channel): ?>
                    <div class="flex items-center p-2 rounded <?= $channel['is_active'] ? 'bg-green-50 border border-green-200' : 'bg-gray-50 border border-gray-200' ?>">
                        <i class="fas fa-<?= $channel['is_active'] ? 'check-circle text-green-600' : 'times-circle text-gray-400' ?> mr-2 text-xs"></i>
                        <span class="text-xs font-medium text-gray-700"><?= ucfirst($channel['channel_type']) ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php else: ?>
        <div class="bg-orange-50 rounded-lg border border-orange-200 p-4">
            <div class="flex items-start mb-2">
                <i class="fas fa-exclamation-triangle text-orange-500 mr-2 mt-0.5"></i>
                <div>
                    <h3 class="text-xs font-semibold text-gray-900">No Group Assigned</h3>
                    <p class="text-xs text-gray-600 mt-0.5">Won't receive notifications</p>
                </div>
            </div>
            <a href="/domains/<?= $domain['id'] ?>/edit" class="block w-full text-center px-3 py-1.5 bg-orange-500 text-white text-xs rounded-lg hover:bg-orange-600 transition-colors font-medium">
                <i class="fas fa-plus mr-1"></i>
                Assign Group
            </a>
        </div>
        <?php endif; ?>

        <!-- Notification History -->
        <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
            <div class="px-4 py-2 border-b border-gray-200 bg-gray-50">
                <h3 class="text-xs font-semibold text-gray-700 uppercase tracking-wider flex items-center">
                    <i class="fas fa-history text-gray-400 mr-2" style="font-size: 10px;"></i>
                    Notification History (<?= count($logs) ?>)
                </h3>
            </div>
            <div class="overflow-hidden">
                <?php if (empty($logs)): ?>
                <div class="p-8 text-center">
                    <i class="fas fa-bell-slash text-gray-300 text-3xl mb-2"></i>
                    <p class="text-xs text-gray-500">No notifications sent yet</p>
                </div>
                <?php else: ?>
                <div class="max-h-96 overflow-y-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-xs">
                        <thead class="bg-gray-50 sticky top-0">
                            <tr>
                                <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600 uppercase">Channel</th>
                                <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600 uppercase">Status</th>
                                <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600 uppercase">Date</th>
                                <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600 uppercase">Message</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($logs as $log): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-3 py-2 whitespace-nowrap">
                                    <span class="px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                        <?= ucfirst($log['channel_type']) ?>
                                    </span>
                                </td>
                                <td class="px-3 py-2 whitespace-nowrap">
                                    <?php $statusClass = $log['status'] === 'sent' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>
                                    <span class="px-2 py-0.5 rounded text-xs font-medium <?= $statusClass ?>">
                                        <?= ucfirst($log['status']) ?>
                                    </span>
                                </td>
                                <td class="px-3 py-2 whitespace-nowrap text-gray-600"><?= date('M j, H:i', strtotime($log['sent_at'])) ?></td>
                                <td class="px-3 py-2 text-gray-700 max-w-xs truncate" title="<?= htmlspecialchars($log['message']) ?>">
                                    <?= htmlspecialchars($log['message']) ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Raw WHOIS Data (Collapsible) -->
        <?php if (!empty($domain['whois_data'])): ?>
        <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
            <button onclick="toggleWhoisData()" class="w-full px-4 py-2 border-b border-gray-200 bg-gray-50 text-left hover:bg-gray-100 transition-colors">
                <h3 class="text-xs font-semibold text-gray-700 uppercase tracking-wider flex items-center justify-between">
                    <span class="flex items-center">
                        <i class="fas fa-code text-gray-400 mr-2" style="font-size: 10px;"></i>
                        Raw WHOIS Data
                    </span>
                    <i class="fas fa-chevron-down text-gray-400 text-xs transition-transform" id="whois-chevron"></i>
                </h3>
            </button>
            <div id="whois-data" class="hidden p-4 bg-gray-900 max-h-64 overflow-y-auto">
                <pre class="text-xs text-green-400 font-mono"><?= htmlspecialchars(json_encode($whoisData, JSON_PRETTY_PRINT)) ?></pre>
            </div>
        </div>
        <?php endif; ?>

    </div>

</div>

<script>
function toggleWhoisData() {
    const dataDiv = document.getElementById('whois-data');
    const chevron = document.getElementById('whois-chevron');
    dataDiv.classList.toggle('hidden');
    chevron.classList.toggle('rotate-180');
}
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout/base.php';
?>

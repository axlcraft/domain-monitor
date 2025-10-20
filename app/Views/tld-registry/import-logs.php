<?php
$title = 'TLD Import Logs';
$pageTitle = 'TLD Import Logs';
$pageDescription = 'History of TLD registry import operations';
$pageIcon = 'fas fa-history';
ob_start();
?>

<!-- Header with Actions -->
<div class="mb-4 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Import Logs</h1>
        <p class="text-gray-600 mt-1">History of TLD registry import operations</p>
    </div>
    <div class="flex gap-2">
        <a href="/tld-registry" class="inline-flex items-center px-4 py-2.5 border border-gray-300 text-gray-700 text-sm rounded-lg hover:bg-gray-50 transition-colors font-medium">
            <i class="fas fa-arrow-left mr-2"></i>
            Back to Registry
        </a>
    </div>
</div>

<!-- Statistics Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <!-- Total Imports Card -->
    <div class="bg-white rounded-lg border border-gray-200 p-5 hover:shadow-md transition-shadow duration-200">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Total Imports</p>
                <p class="text-2xl font-semibold text-gray-900 mt-1"><?= $importStats['total_imports'] ?? 0 ?></p>
            </div>
            <div class="w-12 h-12 bg-blue-50 rounded-lg flex items-center justify-center">
                <i class="fas fa-download text-blue-600 text-lg"></i>
            </div>
        </div>
    </div>

    <!-- Successful Imports Card -->
    <div class="bg-white rounded-lg border border-gray-200 p-5 hover:shadow-md transition-shadow duration-200">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Successful</p>
                <p class="text-2xl font-semibold text-gray-900 mt-1"><?= $importStats['successful_imports'] ?? 0 ?></p>
            </div>
            <div class="w-12 h-12 bg-green-50 rounded-lg flex items-center justify-center">
                <i class="fas fa-check-circle text-green-600 text-lg"></i>
            </div>
        </div>
    </div>

    <!-- Failed Imports Card -->
    <div class="bg-white rounded-lg border border-gray-200 p-5 hover:shadow-md transition-shadow duration-200">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Failed</p>
                <p class="text-2xl font-semibold text-gray-900 mt-1"><?= $importStats['failed_imports'] ?? 0 ?></p>
            </div>
            <div class="w-12 h-12 bg-red-50 rounded-lg flex items-center justify-center">
                <i class="fas fa-times-circle text-red-600 text-lg"></i>
            </div>
        </div>
    </div>

    <!-- Last Import Card -->
    <div class="bg-white rounded-lg border border-gray-200 p-5 hover:shadow-md transition-shadow duration-200">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Last Import</p>
                <p class="text-sm font-semibold text-gray-900 mt-1">
                    <?php if (!empty($importStats['last_import'])): ?>
                        <?= date('M j, H:i', strtotime($importStats['last_import'])) ?>
                    <?php else: ?>
                        Never
                    <?php endif; ?>
                </p>
            </div>
            <div class="w-12 h-12 bg-indigo-50 rounded-lg flex items-center justify-center">
                <i class="fas fa-clock text-indigo-600 text-lg"></i>
            </div>
        </div>
    </div>
</div>

<!-- Import Logs Table -->
<div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
    <?php if (!empty($imports)): ?>
        <!-- Table View (Desktop) -->
        <div class="hidden lg:block overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Import Type</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Results</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Publication Date</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Started</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($imports as $import): ?>
                        <tr class="hover:bg-gray-50 transition-colors duration-150" 
                            data-import-id="<?= $import['id'] ?>" 
                            data-import-data="<?= htmlspecialchars(json_encode($import)) ?>">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <?php
                                    $typeIcons = [
                                        'tld_list' => 'fa-list',
                                        'rdap' => 'fa-database',
                                        'whois' => 'fa-server',
                                        'complete_workflow' => 'fa-tasks',
                                        'check_updates' => 'fa-sync-alt',
                                        'manual' => 'fa-hand-pointer'
                                    ];
                                    $typeLabels = [
                                        'tld_list' => 'TLD List',
                                        'rdap' => 'RDAP Servers',
                                        'whois' => 'WHOIS Data',
                                        'complete_workflow' => 'Complete Workflow',
                                        'check_updates' => 'Update Check',
                                        'manual' => 'Manual Import'
                                    ];
                                    $typeDescriptions = [
                                        'tld_list' => 'IANA TLD list import',
                                        'rdap' => 'RDAP server bootstrap data',
                                        'whois' => 'WHOIS server & registry URLs',
                                        'complete_workflow' => 'Full import (TLD List → RDAP → WHOIS)',
                                        'check_updates' => 'IANA update verification',
                                        'manual' => 'Manual data import'
                                    ];
                                    
                                    $icon = $typeIcons[$import['import_type']] ?? 'fa-file-import';
                                    $label = $typeLabels[$import['import_type']] ?? ucfirst($import['import_type']);
                                    $description = $typeDescriptions[$import['import_type']] ?? 'Import operation';
                                    ?>
                                    <div class="flex-shrink-0 h-10 w-10 bg-primary bg-opacity-10 rounded-lg flex items-center justify-center">
                                        <i class="fas <?= $icon ?> text-primary"></i>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-semibold text-gray-900"><?= $label ?></div>
                                        <div class="text-sm text-gray-500"><?= $description ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php
                                $statusClass = '';
                                $statusIcon = '';
                                $statusText = '';
                                
                                if ($import['status'] === 'completed') {
                                    $statusClass = 'bg-green-100 text-green-700 border-green-200';
                                    $statusIcon = 'fa-check-circle';
                                    $statusText = 'Completed';
                                } elseif ($import['status'] === 'failed') {
                                    $statusClass = 'bg-red-100 text-red-700 border-red-200';
                                    $statusIcon = 'fa-times-circle';
                                    $statusText = 'Failed';
                                } else {
                                    $statusClass = 'bg-yellow-100 text-yellow-700 border-yellow-200';
                                    $statusIcon = 'fa-clock';
                                    $statusText = 'In Progress';
                                }
                                ?>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold border <?= $statusClass ?>">
                                    <i class="fas <?= $statusIcon ?> mr-1"></i>
                                    <?= $statusText ?>
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900">
                                    <div class="flex items-center space-x-4">
                                        <span class="flex items-center">
                                            <i class="fas fa-globe text-gray-400 mr-1"></i>
                                            <?= $import['total_tlds'] ?> total
                                        </span>
                                        <span class="flex items-center text-green-600">
                                            <i class="fas fa-plus mr-1"></i>
                                            <?= $import['new_tlds'] ?> new
                                        </span>
                                        <span class="flex items-center text-blue-600">
                                            <i class="fas fa-sync mr-1"></i>
                                            <?= $import['updated_tlds'] ?> updated
                                        </span>
                                        <?php if ($import['failed_tlds'] > 0): ?>
                                        <span class="flex items-center text-red-600">
                                            <i class="fas fa-exclamation-triangle mr-1"></i>
                                            <?= $import['failed_tlds'] ?> failed
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php if ($import['iana_publication_date']): ?>
                                <div class="flex items-center">
                                    <i class="far fa-calendar mr-2"></i>
                                    <?php 
                                    $date = $import['iana_publication_date'];
                                    // Try to parse the date, if it fails, display as-is
                                    $parsedDate = strtotime($date);
                                    if ($parsedDate && $parsedDate > 0) {
                                        echo date('M j, Y', $parsedDate);
                                    } else {
                                        echo htmlspecialchars($date);
                                    }
                                    ?>
                                </div>
                                <?php else: ?>
                                <span class="text-gray-400">N/A</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <div class="flex items-center">
                                    <i class="far fa-clock mr-2"></i>
                                    <?= date('M j, H:i', strtotime($import['started_at'])) ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <button onclick="showImportDetails(<?= $import['id'] ?>)" class="text-primary hover:text-primary-dark">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Card View (Mobile) -->
        <div class="lg:hidden divide-y divide-gray-200">
            <?php foreach ($imports as $import): ?>
                <div class="p-6 hover:bg-gray-50 transition-colors duration-150" 
                     data-import-id="<?= $import['id'] ?>" 
                     data-import-data="<?= htmlspecialchars(json_encode($import)) ?>">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center">
                            <?php
                            $typeIcons = [
                                'tld_list' => 'fa-list',
                                'rdap' => 'fa-database',
                                'whois' => 'fa-server',
                                'complete_workflow' => 'fa-tasks',
                                'check_updates' => 'fa-sync-alt',
                                'manual' => 'fa-hand-pointer'
                            ];
                            $typeLabels = [
                                'tld_list' => 'TLD List',
                                'rdap' => 'RDAP Servers',
                                'whois' => 'WHOIS Data',
                                'complete_workflow' => 'Complete Workflow',
                                'check_updates' => 'Update Check',
                                'manual' => 'Manual Import'
                            ];
                            
                            $icon = $typeIcons[$import['import_type']] ?? 'fa-file-import';
                            $label = $typeLabels[$import['import_type']] ?? ucfirst($import['import_type']);
                            ?>
                            <div class="w-10 h-10 bg-primary bg-opacity-10 rounded-lg flex items-center justify-center mr-3">
                                <i class="fas <?= $icon ?> text-primary"></i>
                            </div>
                            <div>
                                <h3 class="font-semibold text-gray-900"><?= $label ?></h3>
                                <p class="text-sm text-gray-500"><?= date('M j, Y H:i', strtotime($import['started_at'])) ?></p>
                            </div>
                        </div>
                        <?php
                        $statusClass = '';
                        $statusIcon = '';
                        $statusText = '';
                        
                        if ($import['status'] === 'completed') {
                            $statusClass = 'bg-green-100 text-green-700';
                            $statusIcon = 'fa-check-circle';
                            $statusText = 'Completed';
                        } elseif ($import['status'] === 'failed') {
                            $statusClass = 'bg-red-100 text-red-700';
                            $statusIcon = 'fa-times-circle';
                            $statusText = 'Failed';
                        } else {
                            $statusClass = 'bg-yellow-100 text-yellow-700';
                            $statusIcon = 'fa-clock';
                            $statusText = 'In Progress';
                        }
                        ?>
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold <?= $statusClass ?>">
                            <i class="fas <?= $statusIcon ?> mr-1"></i>
                            <?= $statusText ?>
                        </span>
                    </div>
                    
                    <div class="space-y-2 text-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600">Total TLDs:</span>
                            <span class="font-semibold"><?= $import['total_tlds'] ?></span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600">New:</span>
                            <span class="font-semibold text-green-600"><?= $import['new_tlds'] ?></span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600">Updated:</span>
                            <span class="font-semibold text-blue-600"><?= $import['updated_tlds'] ?></span>
                        </div>
                        <?php if ($import['failed_tlds'] > 0): ?>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600">Failed:</span>
                            <span class="font-semibold text-red-600"><?= $import['failed_tlds'] ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="flex space-x-2 mt-3">
                        <button onclick="showImportDetails(<?= $import['id'] ?>)" class="flex-1 px-3 py-1.5 bg-blue-50 text-blue-600 rounded text-center text-sm hover:bg-blue-100 transition-colors">
                            <i class="fas fa-eye mr-1"></i> Details
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($pagination['total_pages'] > 1): ?>
        <div class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
            <div class="flex-1 flex justify-between sm:hidden">
                <?php if ($pagination['current_page'] > 1): ?>
                <a href="?page=<?= $pagination['current_page'] - 1 ?>" 
                   class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                    Previous
                </a>
                <?php endif; ?>
                
                <?php if ($pagination['current_page'] < $pagination['total_pages']): ?>
                <a href="?page=<?= $pagination['current_page'] + 1 ?>" 
                   class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                    Next
                </a>
                <?php endif; ?>
            </div>
            
            <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                <div>
                    <p class="text-sm text-gray-700">
                        Showing <span class="font-medium"><?= $pagination['showing_from'] ?></span> to 
                        <span class="font-medium"><?= $pagination['showing_to'] ?></span> of 
                        <span class="font-medium"><?= $pagination['total'] ?></span> results
                    </p>
                </div>
                <div>
                    <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                        <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                        <a href="?page=<?= $i ?>" 
                           class="relative inline-flex items-center px-4 py-2 border text-sm font-medium <?= $i === $pagination['current_page'] ? 'z-10 bg-primary border-primary text-white' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50' ?> <?= $i === 1 ? 'rounded-l-md' : '' ?> <?= $i === $pagination['total_pages'] ? 'rounded-r-md' : '' ?>">
                            <?= $i ?>
                        </a>
                        <?php endfor; ?>
                    </nav>
                </div>
            </div>
        </div>
        <?php endif; ?>
    <?php else: ?>
        <div class="text-center py-12 px-6">
            <div class="mb-4">
                <i class="fas fa-history text-gray-300 text-6xl"></i>
            </div>
            <h3 class="text-lg font-semibold text-gray-700 mb-1">No Import Logs</h3>
            <p class="text-sm text-gray-500 mb-4">No TLD imports have been performed yet.</p>
            <a href="/tld-registry" class="inline-flex items-center px-5 py-2.5 bg-primary text-white text-sm rounded-lg hover:bg-primary-dark transition-colors font-medium">
                <i class="fas fa-arrow-left mr-2"></i>
                Back to Registry
            </a>
        </div>
    <?php endif; ?>
</div>

<!-- Import Details Modal -->
<div id="importDetailsModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Import Details</h3>
                <button onclick="closeImportDetails()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div id="importDetailsContent" class="text-sm text-gray-600">
                <!-- Content will be loaded here -->
            </div>
        </div>
    </div>
</div>

<script>
function showImportDetails(importId) {
    // Find the import data from the current page
    const importData = findImportData(importId);
    
    if (!importData) {
        document.getElementById('importDetailsContent').innerHTML = `
            <div class="text-center text-gray-500">
                <p>Import details not found</p>
            </div>
        `;
        document.getElementById('importDetailsModal').classList.remove('hidden');
        return;
    }
    
    // Type labels mapping
    const typeLabels = {
        'tld_list': 'TLD List',
        'rdap': 'RDAP Servers',
        'whois': 'WHOIS Data',
        'complete_workflow': 'Complete Workflow',
        'check_updates': 'Update Check',
        'manual': 'Manual Import'
    };
    
    const typeDescriptions = {
        'tld_list': 'IANA TLD list import',
        'rdap': 'RDAP server bootstrap data',
        'whois': 'WHOIS server & registry URLs',
        'complete_workflow': 'Full import (TLD List → RDAP → WHOIS)',
        'check_updates': 'IANA update verification',
        'manual': 'Manual data import'
    };
    
    const typeLabel = typeLabels[importData.import_type] || importData.import_type;
    const typeDescription = typeDescriptions[importData.import_type] || 'Import operation';
    
    // Calculate duration if we have both start and completion times
    let duration = 'Unknown';
    if (importData.started_at && importData.completed_at) {
        const start = new Date(importData.started_at);
        const end = new Date(importData.completed_at);
        const diffMs = end - start;
        const minutes = Math.floor(diffMs / 60000);
        const seconds = Math.floor((diffMs % 60000) / 1000);
        
        // If duration is very short (< 5 seconds), it might be manually completed
        // Try to estimate from the log if it's a complete workflow
        if (diffMs < 5000 && importData.import_type === 'complete_workflow') {
            // Estimate: ~1 second per TLD for complete workflow
            const estimatedSeconds = Math.round((importData.total_tlds || 0) * 1.1);
            const estMinutes = Math.floor(estimatedSeconds / 60);
            const estSeconds = estimatedSeconds % 60;
            duration = `~${estMinutes} minutes ${estSeconds} seconds (estimated)`;
        } else if (minutes === 0 && seconds === 0) {
            duration = 'Less than 1 second';
        } else {
            duration = `${minutes} minutes ${seconds} seconds`;
        }
    }
    
    // Determine status color
    let statusClass = 'bg-gray-100 text-gray-800';
    let statusText = 'Unknown';
    if (importData.status === 'completed') {
        statusClass = 'bg-green-100 text-green-800';
        statusText = 'Completed';
    } else if (importData.status === 'failed') {
        statusClass = 'bg-red-100 text-red-800';
        statusText = 'Failed';
    } else if (importData.status === 'running') {
        statusClass = 'bg-yellow-100 text-yellow-800';
        statusText = 'Running';
    }
    
    document.getElementById('importDetailsContent').innerHTML = `
        <div class="space-y-3">
            <div class="flex justify-between">
                <span class="font-medium">Import ID:</span>
                <span>${importData.id}</span>
            </div>
            <div class="flex justify-between">
                <span class="font-medium">Type:</span>
                <span>${typeLabel}</span>
            </div>
            <div class="flex justify-between">
                <span class="font-medium">Description:</span>
                <span class="text-gray-600">${typeDescription}</span>
            </div>
            <div class="flex justify-between">
                <span class="font-medium">Status:</span>
                <span class="px-2 py-1 rounded text-xs ${statusClass}">${statusText}</span>
            </div>
            <div class="flex justify-between">
                <span class="font-medium">Duration:</span>
                <span>${duration}</span>
            </div>
            <div class="flex justify-between">
                <span class="font-medium">Started:</span>
                <span>${new Date(importData.started_at).toLocaleString()}</span>
            </div>
            ${importData.completed_at ? `
            <div class="flex justify-between">
                <span class="font-medium">Completed:</span>
                <span>${new Date(importData.completed_at).toLocaleString()}</span>
            </div>
            ` : ''}
            ${importData.iana_publication_date ? `
            <div class="flex justify-between">
                <span class="font-medium">IANA Publication:</span>
                <span>${importData.iana_publication_date}</span>
            </div>
            ` : ''}
            <div class="mt-4">
                <h4 class="font-medium mb-2">Import Results:</h4>
                <div class="bg-gray-100 p-3 rounded text-xs font-mono space-y-1">
                    <div>Total TLDs: ${importData.total_tlds || 0}</div>
                    <div>New TLDs: ${importData.new_tlds || 0}</div>
                    <div>Updated TLDs: ${importData.updated_tlds || 0}</div>
                    <div>Failed TLDs: ${importData.failed_tlds || 0}</div>
                    ${importData.error_message ? `
                    <div class="text-red-600 mt-2">
                        <strong>Error:</strong> ${importData.error_message}
                    </div>
                    ` : ''}
                </div>
            </div>
        </div>
    `;
    document.getElementById('importDetailsModal').classList.remove('hidden');
}

function findImportData(importId) {
    // Look for import data in the current page
    const importRows = document.querySelectorAll('tr[data-import-id]');
    for (let row of importRows) {
        if (row.getAttribute('data-import-id') == importId) {
            return JSON.parse(row.getAttribute('data-import-data'));
        }
    }
    
    // Fallback: look for data in mobile view
    const importCards = document.querySelectorAll('[data-import-id]');
    for (let card of importCards) {
        if (card.getAttribute('data-import-id') == importId) {
            return JSON.parse(card.getAttribute('data-import-data'));
        }
    }
    
    return null;
}

function closeImportDetails() {
    document.getElementById('importDetailsModal').classList.add('hidden');
}

// Close modal when clicking outside
document.getElementById('importDetailsModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeImportDetails();
    }
});
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout/base.php';
?>
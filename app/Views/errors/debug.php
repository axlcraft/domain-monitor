<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug Error - <?= htmlspecialchars($error_type ?? 'Application Error') ?></title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            DEFAULT: '#4A90E2',
                            dark: '#357ABD',
                            light: '#6BA3E8',
                        }
                    }
                }
            }
        }
    </script>
    
    <style>
        body {
            background-color: #f8f9fa;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .animate-fade-in {
            animation: fadeIn 0.4s ease-out;
        }
        
        .code-block {
            background-color: #1e1e1e;
            color: #d4d4d4;
        }
        
        .line-number {
            color: #858585;
            user-select: none;
        }
    </style>
</head>
<body class="min-h-screen p-6">
    
    <!-- Header -->
    <div class="max-w-7xl mx-auto mb-6">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <div class="flex items-center space-x-4">
                    <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-bug text-red-600 text-xl"></i>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Debug Mode</h1>
                        <p class="text-sm text-gray-600 mt-0.5">
                            <i class="fas fa-circle text-orange-500 mr-1 text-xs animate-pulse"></i>
                            Development Environment - Detailed Error Information
                        </p>
                    </div>
                </div>
                <button onclick="copyErrorReport()" 
                        class="inline-flex items-center px-4 py-2.5 bg-primary hover:bg-primary-dark text-white rounded-lg transition-colors font-medium text-sm">
                    <i class="fas fa-clipboard mr-2"></i>
                    Copy Error Report
                </button>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto">
        
        <!-- Primary Error Card -->
        <div class="bg-white rounded-lg shadow-sm border-l-4 border-red-500 mb-6 animate-fade-in">
            <div class="p-6">
                <!-- Error Header -->
                <div class="flex items-start mb-6">
                    <div class="flex-shrink-0 w-14 h-14 bg-red-100 rounded-lg flex items-center justify-center mr-4">
                        <i class="fas fa-exclamation-triangle text-red-600 text-2xl"></i>
                    </div>
                    <div class="flex-1">
                        <h2 class="text-2xl font-bold text-gray-900 mb-2">
                            <?= htmlspecialchars($error_type ?? 'Error') ?>
                        </h2>
                        <p class="text-lg text-gray-700 mb-4"><?= htmlspecialchars($error_message ?? 'An error occurred') ?></p>
                        
                        <!-- Error Location - Most Critical -->
                        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                            <h3 class="text-sm font-semibold text-gray-900 mb-3 flex items-center">
                                <i class="fas fa-map-marker-alt text-red-500 mr-2 text-xs"></i>
                                Error Location
                            </h3>
                            <div class="space-y-2">
                                <div>
                                    <span class="text-xs font-medium text-gray-600">File:</span>
                                    <code class="block mt-1 bg-white px-3 py-2 rounded text-sm text-gray-800 border border-gray-200 font-mono break-all">
                                        <?= htmlspecialchars($error_file ?? 'Unknown') ?>
                                    </code>
                                </div>
                                <div class="flex items-center">
                                    <span class="text-xs font-medium text-gray-600 mr-2">Line:</span>
                                    <span class="font-mono text-red-600 font-bold text-lg"><?= htmlspecialchars($error_line ?? '?') ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Info Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Error Reference ID -->
                    <div class="bg-blue-50 rounded-lg border border-blue-200 p-4">
                        <div class="flex items-center justify-between mb-2">
                            <h4 class="text-xs font-semibold text-gray-700 uppercase tracking-wide">Error ID</h4>
                            <button onclick="copyToClipboard('<?= htmlspecialchars($error_id ?? 'N/A') ?>')" 
                                    class="text-primary hover:text-primary-dark" title="Copy Error ID">
                                <i class="fas fa-copy text-xs"></i>
                            </button>
                        </div>
                        <code class="text-sm font-mono font-bold text-primary"><?= htmlspecialchars($error_id ?? 'N/A') ?></code>
                        <p class="text-xs text-gray-600 mt-2">
                            <i class="fas fa-info-circle mr-1"></i>
                            Use for bug reports
                        </p>
                    </div>

                    <!-- Request Info -->
                    <div class="bg-gray-50 rounded-lg border border-gray-200 p-4">
                        <h4 class="text-xs font-semibold text-gray-700 uppercase tracking-wide mb-2">Request</h4>
                        <div class="space-y-1">
                            <p class="text-sm">
                                <span class="font-mono font-bold text-gray-900"><?= htmlspecialchars($request_method ?? 'GET') ?></span>
                            </p>
                            <code class="text-xs text-gray-600 font-mono block truncate" title="<?= htmlspecialchars($request_uri ?? '/') ?>">
                                <?= htmlspecialchars($request_uri ?? '/') ?>
                            </code>
                        </div>
                    </div>

                    <!-- User Context -->
                    <div class="bg-gray-50 rounded-lg border border-gray-200 p-4">
                        <h4 class="text-xs font-semibold text-gray-700 uppercase tracking-wide mb-2">User</h4>
                        <?php if ($user_info): ?>
                            <p class="text-sm font-semibold text-gray-900"><?= htmlspecialchars($user_info['username']) ?></p>
                            <p class="text-xs text-gray-600">
                                <i class="fas fa-user mr-1"></i>
                                <?= htmlspecialchars($user_info['role']) ?>
                            </p>
                        <?php else: ?>
                            <p class="text-sm text-gray-500">
                                <i class="fas fa-user-slash mr-1"></i>
                                Guest (Not logged in)
                            </p>
                        <?php endif; ?>
                    </div>

                    <!-- System Info -->
                    <div class="bg-gray-50 rounded-lg border border-gray-200 p-4">
                        <h4 class="text-xs font-semibold text-gray-700 uppercase tracking-wide mb-2">System</h4>
                        <div class="space-y-1">
                            <p class="text-xs text-gray-600">
                                <i class="fas fa-code mr-1"></i>
                                PHP <?= htmlspecialchars($php_version ?? PHP_VERSION) ?>
                            </p>
                            <p class="text-xs text-gray-600">
                                <i class="fas fa-memory mr-1"></i>
                                <?= round(($memory_usage ?? memory_get_usage(true)) / 1024 / 1024, 2) ?>MB
                            </p>
                            <p class="text-xs text-gray-600">
                                <i class="fas fa-clock mr-1"></i>
                                <?= date('H:i:s', strtotime($occurred_at ?? 'now')) ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Two Column Layout -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            
            <!-- Left Column -->
            <div class="space-y-6">
                
                <!-- Stack Trace -->
                <?php if (!empty($stack_trace)): ?>
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden animate-fade-in">
                    <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                                <i class="fas fa-layer-group text-primary mr-2 text-sm"></i>
                                Stack Trace
                            </h3>
                            <button onclick="copyStackTrace()" 
                                    class="text-sm text-primary hover:text-primary-dark font-medium">
                                <i class="fas fa-copy mr-1"></i>
                                Copy
                            </button>
                        </div>
                    </div>
                    <div class="p-6">
                        <div class="code-block rounded-lg p-4 overflow-x-auto max-h-96 overflow-y-auto border border-gray-700" id="stack-trace">
                            <?php 
                            $traceLines = explode("\n", $stack_trace);
                            foreach ($traceLines as $index => $line) {
                                if (trim($line)) {
                                    echo '<div class="flex font-mono text-sm">';
                                    echo '<span class="line-number mr-4 text-right" style="min-width: 2rem">' . str_pad($index, 2, '0', STR_PAD_LEFT) . '</span>';
                                    echo '<span class="flex-1 text-green-400">' . htmlspecialchars($line) . '</span>';
                                    echo '</div>';
                                }
                            }
                            ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Request Data -->
                <?php if (!empty($request_data)): ?>
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden animate-fade-in">
                    <button onclick="toggleSection('request-data')" 
                            class="w-full px-6 py-4 border-b border-gray-200 bg-gray-50 text-left hover:bg-gray-100 transition-colors">
                        <h3 class="text-lg font-semibold text-gray-900 flex items-center justify-between">
                            <span class="flex items-center">
                                <i class="fas fa-paper-plane text-blue-500 mr-2 text-sm"></i>
                                Request Data
                                <span class="ml-2 text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded font-medium">
                                    <?= count($request_data) ?>
                                </span>
                            </span>
                            <i class="fas fa-chevron-down text-gray-400 text-xs transition-transform" id="request-data-chevron"></i>
                        </h3>
                    </button>
                    <div id="request-data" class="hidden p-6">
                        <div class="space-y-3">
                            <?php foreach ($request_data as $key => $value): ?>
                                <div class="bg-gray-50 rounded-lg p-3 border border-gray-200">
                                    <span class="text-xs font-semibold text-gray-700 uppercase tracking-wide block mb-1">
                                        <?= htmlspecialchars($key) ?>
                                    </span>
                                    <code class="text-sm text-gray-800 font-mono block break-all">
                                        <?= htmlspecialchars(is_array($value) ? json_encode($value, JSON_PRETTY_PRINT) : $value) ?>
                                    </code>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

            </div>

            <!-- Right Column -->
            <div class="space-y-6">
                
                <!-- Request Details -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden animate-fade-in">
                    <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                        <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                            <i class="fas fa-globe text-green-500 mr-2 text-sm"></i>
                            Request Details
                        </h3>
                    </div>
                    <div class="p-6">
                        <div class="space-y-3 text-sm">
                            <div class="flex items-center justify-between py-2 border-b border-gray-100">
                                <span class="font-medium text-gray-600">Method</span>
                                <span class="font-mono font-bold text-gray-900"><?= htmlspecialchars($request_method ?? 'GET') ?></span>
                            </div>
                            <div class="py-2 border-b border-gray-100">
                                <span class="font-medium text-gray-600 block mb-1">URI</span>
                                <code class="text-xs text-gray-800 font-mono block break-all bg-gray-50 px-2 py-1 rounded">
                                    <?= htmlspecialchars($request_uri ?? '/') ?>
                                </code>
                            </div>
                            <div class="py-2 border-b border-gray-100">
                                <span class="font-medium text-gray-600 block mb-1">IP Address</span>
                                <code class="text-xs text-gray-800 font-mono block bg-gray-50 px-2 py-1 rounded">
                                    <?= htmlspecialchars($ip_address ?? 'Unknown') ?>
                                </code>
                            </div>
                            <div class="py-2">
                                <span class="font-medium text-gray-600 block mb-1">User Agent</span>
                                <code class="text-xs text-gray-600 font-mono block break-all bg-gray-50 px-2 py-1 rounded">
                                    <?= htmlspecialchars($user_agent ?? 'Unknown') ?>
                                </code>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- System Information -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden animate-fade-in">
                    <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                        <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                            <i class="fas fa-server text-indigo-500 mr-2 text-sm"></i>
                            System Information
                        </h3>
                    </div>
                    <div class="p-6">
                        <div class="space-y-3 text-sm">
                            <div class="flex items-center justify-between py-2 border-b border-gray-100">
                                <span class="font-medium text-gray-600">PHP Version</span>
                                <span class="font-mono text-gray-900"><?= htmlspecialchars($php_version ?? PHP_VERSION) ?></span>
                            </div>
                            <div class="flex items-center justify-between py-2 border-b border-gray-100">
                                <span class="font-medium text-gray-600">Memory Usage</span>
                                <span class="font-mono text-gray-900"><?= round(($memory_usage ?? memory_get_usage(true)) / 1024 / 1024, 2) ?>MB</span>
                            </div>
                            <div class="flex items-center justify-between py-2 border-b border-gray-100">
                                <span class="font-medium text-gray-600">Peak Memory</span>
                                <span class="font-mono text-gray-900"><?= round(memory_get_peak_usage(true) / 1024 / 1024, 2) ?>MB</span>
                            </div>
                            <div class="flex items-center justify-between py-2">
                                <span class="font-medium text-gray-600">Timestamp</span>
                                <span class="font-mono text-gray-900 text-xs"><?= date('Y-m-d H:i:s T', strtotime($occurred_at ?? 'now')) ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Session Data -->
                <?php if (!empty($session_data)): ?>
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden animate-fade-in">
                    <button onclick="toggleSection('session-data')" 
                            class="w-full px-6 py-4 border-b border-gray-200 bg-gray-50 text-left hover:bg-gray-100 transition-colors">
                        <h3 class="text-lg font-semibold text-gray-900 flex items-center justify-between">
                            <span class="flex items-center">
                                <i class="fas fa-user-lock text-orange-500 mr-2 text-sm"></i>
                                Session Data
                                <span class="ml-2 text-xs bg-orange-100 text-orange-800 px-2 py-1 rounded font-medium">
                                    <?= count($session_data) ?>
                                </span>
                            </span>
                            <i class="fas fa-chevron-down text-gray-400 text-xs transition-transform" id="session-data-chevron"></i>
                        </h3>
                    </button>
                    <div id="session-data" class="hidden p-6">
                        <div class="max-h-80 overflow-y-auto">
                            <table class="min-w-full text-sm">
                                <thead class="bg-gray-50 sticky top-0">
                                    <tr>
                                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600 uppercase">Key</th>
                                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600 uppercase">Value</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <?php foreach ($session_data as $key => $value): ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-3 py-2 font-mono text-gray-700 align-top"><?= htmlspecialchars($key) ?></td>
                                            <td class="px-3 py-2 font-mono text-gray-600 break-all">
                                                <?= htmlspecialchars(is_array($value) ? json_encode($value) : $value) ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

            </div>
        </div>

        <!-- Help Card -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-5 mt-6 animate-fade-in">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <div class="w-10 h-10 bg-blue-500 rounded-lg flex items-center justify-center">
                        <i class="fas fa-lightbulb text-white"></i>
                    </div>
                </div>
                <div class="ml-4">
                    <h3 class="text-sm font-semibold text-gray-900 mb-2">Debug Mode Active</h3>
                    <p class="text-sm text-gray-700 mb-3">
                        This detailed error page is only shown in development mode. In production, users will see a clean error page with just the error ID.
                    </p>
                    <div class="flex flex-wrap gap-2">
                        <a href="/" class="inline-flex items-center px-3 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors text-sm font-medium">
                            <i class="fas fa-home mr-2"></i>
                            Go to Dashboard
                        </a>
                        <button onclick="location.reload()" class="inline-flex items-center px-3 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors text-sm font-medium">
                            <i class="fas fa-redo mr-2"></i>
                            Reload Page
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Footer -->
    <div class="max-w-7xl mx-auto mt-8">
        <div class="text-center text-sm text-gray-500">
            <p>
                <i class="fas fa-globe text-primary mr-1"></i>
                Domain Monitor &copy; <?= date('Y') ?> • Development Mode
            </p>
        </div>
    </div>

    <!-- JavaScript -->
    <script>
        function toggleSection(sectionId) {
            const section = document.getElementById(sectionId);
            const chevron = document.getElementById(sectionId + '-chevron');
            
            if (section.classList.contains('hidden')) {
                section.classList.remove('hidden');
                if (chevron) {
                    chevron.style.transform = 'rotate(180deg)';
                }
            } else {
                section.classList.add('hidden');
                if (chevron) {
                    chevron.style.transform = 'rotate(0deg)';
                }
            }
        }
        
        function copyToClipboard(text) {
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(text).then(() => {
                    showCopySuccess();
                }).catch(err => {
                    fallbackCopy(text);
                });
            } else {
                fallbackCopy(text);
            }
        }
        
        function fallbackCopy(text) {
            const textArea = document.createElement('textarea');
            textArea.value = text;
            textArea.style.position = 'fixed';
            textArea.style.left = '-999999px';
            document.body.appendChild(textArea);
            textArea.select();
            
            try {
                document.execCommand('copy');
                showCopySuccess();
            } catch (err) {
                console.error('Copy failed:', err);
            }
            
            document.body.removeChild(textArea);
        }
        
        function copyStackTrace() {
            const stackTraceElement = document.getElementById('stack-trace');
            const lines = stackTraceElement.querySelectorAll('div');
            let stackText = '';
            
            lines.forEach(line => {
                const textSpan = line.querySelector('span:last-child');
                if (textSpan) {
                    stackText += textSpan.textContent + '\n';
                }
            });
            
            copyToClipboard(stackText.trim());
        }

        function copyErrorReport() {
            const errorType = <?= json_encode($error_type ?? 'Error') ?>;
            const errorMessage = <?= json_encode($error_message ?? 'Unknown error') ?>;
            const errorFile = <?= json_encode($error_file ?? 'Unknown') ?>;
            const errorLine = <?= json_encode($error_line ?? '?') ?>;
            const errorId = <?= json_encode($error_id ?? 'N/A') ?>;
            const phpVersion = <?= json_encode($php_version ?? PHP_VERSION) ?>;
            const requestMethod = <?= json_encode($request_method ?? 'GET') ?>;
            const requestUri = <?= json_encode($request_uri ?? '/') ?>;
            const userAgent = <?= json_encode($user_agent ?? 'Unknown') ?>;
            const ipAddress = <?= json_encode($ip_address ?? 'Unknown') ?>;
            const timestamp = <?= json_encode(date('Y-m-d H:i:s', strtotime($occurred_at ?? 'now'))) ?>;
            
            const userInfo = <?= json_encode($user_info ?? null) ?>;
            const userText = userInfo ? `${userInfo.username} (${userInfo.role}, ID: ${userInfo.id})` : 'Guest (Not logged in)';
            
            // Get stack trace
            const stackTraceElement = document.getElementById('stack-trace');
            let stackTrace = 'Not available';
            if (stackTraceElement) {
                const lines = stackTraceElement.querySelectorAll('div');
                let stackText = '';
                lines.forEach(line => {
                    const textSpan = line.querySelector('span:last-child');
                    if (textSpan) {
                        stackText += textSpan.textContent + '\n';
                    }
                });
                stackTrace = stackText.trim();
            }
            
            const errorReport = `=== DOMAIN MONITOR ERROR REPORT ===

ERROR INFORMATION:
- Error ID: ${errorId}
- Type: ${errorType}
- Message: ${errorMessage}

LOCATION:
- File: ${errorFile}
- Line: ${errorLine}

REQUEST DETAILS:
- Method: ${requestMethod}
- URI: ${requestUri}
- Timestamp: ${timestamp}

USER CONTEXT:
- User: ${userText}
- IP Address: ${ipAddress}
- User Agent: ${userAgent}

SYSTEM INFORMATION:
- PHP Version: ${phpVersion}
- Memory Usage: ${<?= round(($memory_usage ?? memory_get_usage(true)) / 1024 / 1024, 2) ?>}MB
- Peak Memory: ${<?= round(memory_get_peak_usage(true) / 1024 / 1024, 2) ?>}MB

STACK TRACE:
${stackTrace}

=== END OF ERROR REPORT ===

Reference ID: ${errorId}
Please include this report when reporting bugs.`;

            copyToClipboard(errorReport);
        }

        function showCopySuccess() {
            const message = document.createElement('div');
            message.className = 'fixed top-4 right-4 bg-green-500 text-white px-4 py-3 rounded-lg shadow-lg z-50 flex items-center animate-fade-in';
            message.innerHTML = '<i class="fas fa-check mr-2"></i>Copied to clipboard!';
            document.body.appendChild(message);
            
            setTimeout(() => {
                message.style.opacity = '0';
                message.style.transform = 'translateY(-20px)';
                message.style.transition = 'all 0.3s ease-out';
                setTimeout(() => message.remove(), 300);
            }, 2000);
        }
    </script>
</body>
</html>


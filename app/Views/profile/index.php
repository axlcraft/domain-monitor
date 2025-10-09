<?php
$title = 'My Profile';
$pageTitle = 'My Profile';
$pageDescription = 'Manage your account settings and preferences';
$pageIcon = 'fas fa-user-circle';
ob_start();
?>

<!-- Main Profile Layout -->
<div class="grid grid-cols-12 gap-6">
    <!-- Sidebar Navigation -->
    <div class="col-span-12 lg:col-span-3">
        <div class="bg-white rounded-lg border border-gray-200 overflow-hidden sticky top-6">
            <!-- User Info Section -->
            <div class="p-6 border-b border-gray-200 bg-gray-50">
                <div class="flex flex-col items-center text-center">
                    <div class="w-20 h-20 rounded-full bg-primary flex items-center justify-center text-white text-2xl font-bold">
                        <?= strtoupper(substr($user['username'] ?? 'U', 0, 1)) ?>
                    </div>
                    <h3 class="mt-4 text-base font-semibold text-gray-900"><?= htmlspecialchars($user['full_name'] ?? $user['username']) ?></h3>
                    <p class="text-sm text-gray-500 mt-1">@<?= htmlspecialchars($user['username'] ?? '') ?></p>
                    
                    <!-- Role Badge -->
                    <span class="inline-flex items-center mt-3 px-2.5 py-1 bg-<?= $user['role'] === 'admin' ? 'indigo' : 'blue' ?>-100 text-<?= $user['role'] === 'admin' ? 'indigo' : 'blue' ?>-800 text-xs font-semibold rounded">
                        <i class="fas fa-<?= $user['role'] === 'admin' ? 'crown' : 'user' ?> mr-1.5"></i>
                        <?= ucfirst($user['role'] ?? 'user') ?>
                    </span>

                    <!-- Stats -->
                    <div class="grid grid-cols-2 gap-3 mt-4 w-full">
                        <div class="bg-white rounded-lg p-2 border border-gray-200">
                            <div class="text-xs text-gray-500">Member Since</div>
                            <div class="text-xs font-semibold text-gray-900 mt-0.5">
                                <?= date('M Y', strtotime($user['created_at'] ?? 'now')) ?>
                            </div>
                        </div>
                        <div class="bg-white rounded-lg p-2 border border-gray-200">
                            <div class="text-xs text-gray-500">Status</div>
                            <div class="text-xs font-semibold text-green-600 mt-0.5">
                                <i class="fas fa-circle text-xs"></i> Active
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Navigation Links -->
            <nav class="p-3">
                <button onclick="showSection('profile')" id="nav-profile" class="nav-item active w-full flex items-center px-4 py-2.5 text-sm font-medium rounded-lg transition-colors mb-1">
                    <i class="fas fa-user-circle w-5 mr-3 text-sm"></i>
                    <span>Profile Information</span>
                </button>
                <button onclick="showSection('security')" id="nav-security" class="nav-item w-full flex items-center px-4 py-2.5 text-sm font-medium rounded-lg transition-colors mb-1">
                    <i class="fas fa-shield-alt w-5 mr-3 text-sm"></i>
                    <span>Security</span>
                </button>
                <button onclick="showSection('sessions')" id="nav-sessions" class="nav-item w-full flex items-center px-4 py-2.5 text-sm font-medium rounded-lg transition-colors mb-1">
                    <i class="fas fa-laptop w-5 mr-3 text-sm"></i>
                    <span>Active Sessions</span>
                </button>
                
                <?php if ($user['role'] !== 'admin'): ?>
                <hr class="my-3 border-gray-200">
                <button onclick="showSection('danger')" id="nav-danger" class="nav-item w-full flex items-center px-4 py-2.5 text-sm font-medium rounded-lg transition-colors text-red-600 hover:bg-red-50">
                    <i class="fas fa-exclamation-triangle w-5 mr-3 text-sm"></i>
                    <span>Danger Zone</span>
                </button>
                <?php endif; ?>
            </nav>
        </div>
    </div>

    <!-- Main Content Area -->
    <div class="col-span-12 lg:col-span-9">
        
        <!-- Profile Information Section -->
        <div id="section-profile" class="content-section">
            <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                    <h3 class="text-lg font-semibold text-gray-900">Profile Information</h3>
                    <p class="text-sm text-gray-600 mt-1">Update your personal details and account information</p>
                </div>

                <form method="POST" action="/profile/update" class="p-6">
                    <div class="space-y-5">
                        <!-- Full Name -->
                        <div>
                            <label for="full_name" class="block text-sm font-medium text-gray-700 mb-2">
                                Full Name
                            </label>
                            <input type="text" id="full_name" name="full_name" 
                                   value="<?= htmlspecialchars($user['full_name'] ?? '') ?>"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                        </div>

                        <!-- Email -->
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                                Email Address
                            </label>
                            <input type="email" id="email" name="email" 
                                   value="<?= htmlspecialchars($user['email'] ?? '') ?>"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                            
                            <?php if (!empty($user['email_verified'])): ?>
                                <p class="text-xs text-green-600 mt-1.5">
                                    <i class="fas fa-check-circle mr-1"></i>
                                    Email verified
                                </p>
                            <?php else: ?>
                                <div class="mt-3 bg-amber-50 border border-amber-200 rounded-lg p-3">
                                    <div class="flex items-start justify-between">
                                        <div class="flex items-start">
                                            <i class="fas fa-exclamation-triangle text-amber-600 mt-0.5 mr-2"></i>
                                            <div>
                                                <p class="text-xs font-semibold text-amber-900">Email Not Verified</p>
                                                <p class="text-xs text-amber-700 mt-0.5">Verify your email to unlock all features</p>
                                            </div>
                                        </div>
                                        <a href="/profile/resend-verification" class="ml-3 inline-flex items-center px-3 py-1.5 bg-amber-600 hover:bg-amber-700 text-white text-xs rounded-lg transition-colors font-medium whitespace-nowrap">
                                            <i class="fas fa-paper-plane mr-1.5"></i>
                                            Resend
                                        </a>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Username (Read-only) -->
                        <div>
                            <label for="username" class="block text-sm font-medium text-gray-700 mb-2">
                                Username
                            </label>
                            <input type="text" id="username" name="username" 
                                   value="<?= htmlspecialchars($user['username'] ?? '') ?>"
                                   readonly
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50 text-gray-500 cursor-not-allowed">
                            <p class="text-xs text-gray-500 mt-1">Username cannot be changed</p>
                        </div>

                        <!-- Account Details Grid -->
                        <div class="pt-4 border-t border-gray-200">
                            <h4 class="text-sm font-semibold text-gray-700 mb-3">Account Information</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="bg-gray-50 rounded-lg p-3 border border-gray-200">
                                    <label class="block text-xs font-medium text-gray-500 mb-1">Member Since</label>
                                    <p class="text-sm font-semibold text-gray-900">
                                        <?= date('F j, Y', strtotime($user['created_at'] ?? 'now')) ?>
                                    </p>
                                </div>
                                <div class="bg-gray-50 rounded-lg p-3 border border-gray-200">
                                    <label class="block text-xs font-medium text-gray-500 mb-1">Last Login</label>
                                    <p class="text-sm font-semibold text-gray-900">
                                        <?= $user['last_login'] ? date('M j, Y g:i A', strtotime($user['last_login'])) : 'Never' ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-end pt-6 mt-6 border-t border-gray-200 space-x-2">
                        <button type="button" onclick="location.reload()" class="px-4 py-2 border border-gray-300 text-gray-700 text-sm rounded-lg hover:bg-gray-50 transition-colors font-medium">
                            Cancel
                        </button>
                        <button type="submit" class="inline-flex items-center px-4 py-2.5 bg-primary text-white text-sm rounded-lg hover:bg-primary-dark transition-colors font-medium">
                            <i class="fas fa-save mr-2"></i>
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Security Section -->
        <div id="section-security" class="content-section hidden">
            <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                    <h3 class="text-lg font-semibold text-gray-900">Security Settings</h3>
                    <p class="text-sm text-gray-600 mt-1">Manage your password and security preferences</p>
                </div>

                <form method="POST" action="/profile/change-password" class="p-6">
                    <div class="space-y-4">
                        <!-- Current Password -->
                        <div>
                            <label for="current_password" class="block text-sm font-medium text-gray-700 mb-2">
                                Current Password
                            </label>
                            <input type="password" id="current_password" name="current_password" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                                   placeholder="Enter your current password">
                        </div>

                        <!-- New Password -->
                        <div>
                            <label for="new_password" class="block text-sm font-medium text-gray-700 mb-2">
                                New Password
                            </label>
                            <input type="password" id="new_password" name="new_password" required minlength="8"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                                   placeholder="Enter a strong password">
                            <p class="text-xs text-gray-500 mt-1">Minimum 8 characters</p>
                        </div>

                        <!-- Confirm New Password -->
                        <div>
                            <label for="new_password_confirm" class="block text-sm font-medium text-gray-700 mb-2">
                                Confirm New Password
                            </label>
                            <input type="password" id="new_password_confirm" name="new_password_confirm" required minlength="8"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                                   placeholder="Re-enter your new password">
                        </div>

                        <!-- Password Tips -->
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
                            <p class="text-xs text-gray-600">
                                <i class="fas fa-info-circle text-blue-500 mr-1"></i>
                                Use at least 8 characters with a mix of letters, numbers, and symbols for better security.
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center justify-end pt-6 mt-6 border-t border-gray-200">
                        <button type="submit" class="inline-flex items-center px-4 py-2.5 bg-primary text-white text-sm rounded-lg hover:bg-primary-dark transition-colors font-medium">
                            <i class="fas fa-key mr-2"></i>
                            Update Password
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Active Sessions Section -->
        <div id="section-sessions" class="content-section hidden">
            <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Active Sessions</h3>
                            <p class="text-sm text-gray-600 mt-1">Manage devices and sessions where you're logged in (<?= count($sessions ?? []) ?> active)</p>
                        </div>
                        <?php if (count($sessions ?? []) > 1): ?>
                        <form method="POST" action="/profile/logout-other-sessions" onsubmit="return confirm('Logout all other sessions?')" class="inline">
                            <button type="submit" class="inline-flex items-center px-3 py-2 bg-red-600 text-white text-xs rounded-lg hover:bg-red-700 transition-colors font-medium">
                                <i class="fas fa-sign-out-alt mr-1.5"></i>
                                Logout Others
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="p-6">
                    <?php if (!empty($sessions)): ?>
                        <div class="space-y-3">
                            <?php foreach ($sessions as $session): ?>
                                <?php
                                // Display data prepared by SessionHelper in controller
                                $deviceIcon = $session['deviceIcon'];
                                $browserInfo = $session['browserInfo'];
                                $timeAgo = $session['timeAgo'];
                                $sessionAge = $session['sessionAge'];
                                $isCurrent = $session['is_current'] ?? false;
                                $bgClass = $isCurrent ? 'bg-green-50 border-green-200' : 'bg-gray-50 border-gray-200';
                                ?>
                                <div class="flex items-start justify-between p-4 <?= $bgClass ?> border rounded-lg">
                                    <div class="flex items-start space-x-3 flex-1">
                                        <!-- Device Icon -->
                                        <div class="w-10 h-10 bg-<?= $isCurrent ? 'green' : 'gray' ?>-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                            <i class="fas <?= $deviceIcon ?> text-<?= $isCurrent ? 'green' : 'gray' ?>-600"></i>
                                        </div>
                                        
                                        <div class="flex-1 min-w-0">
                                            <!-- Header -->
                                            <div class="flex items-center flex-wrap gap-2">
                                                <?php if (!empty($session['country_code']) && $session['country_code'] !== 'xx'): ?>
                                                    <span class="fi fi-<?= strtolower($session['country_code']) ?> text-base"></span>
                                                <?php endif; ?>
                                                <h4 class="text-sm font-semibold text-gray-900">
                                                    <?= htmlspecialchars($session['city'] ?? 'Unknown') ?>, <?= htmlspecialchars($session['country'] ?? 'Unknown') ?>
                                                </h4>
                                                <?php if ($isCurrent): ?>
                                                    <span class="px-2 py-0.5 bg-green-500 text-white text-xs font-semibold rounded">
                                                        Current
                                                    </span>
                                                <?php endif; ?>
                                                <?php if (!empty($session['has_remember_token'])): ?>
                                                    <span class="px-2 py-0.5 bg-blue-100 text-blue-700 text-xs font-semibold rounded" title="Remember me enabled">
                                                        <i class="fas fa-cookie-bite"></i>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <!-- Browser & OS -->
                                            <p class="text-xs text-gray-600 mt-1">
                                                <i class="fas fa-globe mr-1"></i>
                                                <?= htmlspecialchars($browserInfo) ?>
                                                <?php if (!empty($session['user_agent'])): ?>
                                                    - <?= htmlspecialchars(substr($session['user_agent'], 0, 60)) ?><?= strlen($session['user_agent']) > 60 ? '...' : '' ?>
                                                <?php endif; ?>
                                            </p>
                                            
                                            <!-- IP & ISP -->
                                            <div class="flex flex-wrap items-center gap-3 text-xs text-gray-500 mt-1">
                                                <span>
                                                    <i class="fas fa-map-marker-alt mr-1"></i>
                                                    <?= htmlspecialchars($session['ip_address']) ?>
                                                </span>
                                                <?php if (!empty($session['isp'])): ?>
                                                    <span>
                                                        <i class="fas fa-network-wired mr-1"></i>
                                                        <?= htmlspecialchars($session['isp']) ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <!-- Session Age & Last Activity -->
                                            <div class="flex flex-wrap items-center gap-3 text-xs text-gray-400 mt-1">
                                                <span title="Session started: <?= date('M j, Y H:i', strtotime($session['created_at'])) ?>">
                                                    <i class="fas fa-hourglass-start mr-1"></i>
                                                    <?= $sessionAge ?>
                                                </span>
                                                <span>
                                                    <i class="fas fa-clock mr-1"></i>
                                                    Active <?= $timeAgo ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Delete Button (only for non-current sessions) -->
                                    <?php if (!$isCurrent): ?>
                                        <form method="POST" action="/profile/logout-session/<?= htmlspecialchars($session['id']) ?>" onsubmit="return confirm('Terminate this session?\n\nThat device will be logged out immediately.')" class="ml-3">
                                            <button type="submit" class="flex items-center justify-center w-8 h-8 bg-red-100 text-red-600 rounded-lg hover:bg-red-600 hover:text-white transition-colors" title="Terminate session">
                                                <i class="fas fa-times text-sm"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Info Box -->
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mt-4">
                            <p class="text-xs text-gray-600">
                                <i class="fas fa-info-circle text-blue-500 mr-1"></i>
                                If you see any suspicious sessions or don't recognize a device, logout other sessions immediately and change your password.
                            </p>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-8">
                            <i class="fas fa-laptop text-gray-300 text-4xl mb-3"></i>
                            <p class="text-sm text-gray-600">No active sessions found</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Danger Zone Section -->
        <?php if ($user['role'] !== 'admin'): ?>
        <div id="section-danger" class="content-section hidden">
            <div class="bg-white rounded-lg border border-red-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-red-200 bg-red-50">
                    <h3 class="text-lg font-semibold text-red-900">Danger Zone</h3>
                    <p class="text-sm text-red-700 mt-1">Irreversible and destructive actions</p>
                </div>

                <div class="p-6">
                    <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <h4 class="text-sm font-bold text-red-900">Delete Account Permanently</h4>
                                <p class="text-sm text-red-700 mt-2">
                                    Once you delete your account, there is no going back. This will permanently delete all your profile information and account settings.
                                </p>
                                <p class="text-xs text-red-800 font-semibold mt-3 bg-red-100 inline-block px-2 py-1 rounded">
                                    This action cannot be undone
                                </p>
                            </div>
                            <button onclick="confirmDelete()" class="ml-4 inline-flex items-center px-4 py-2 bg-red-600 text-white text-sm rounded-lg hover:bg-red-700 transition-colors font-medium whitespace-nowrap">
                                <i class="fas fa-trash-alt mr-2"></i>
                                Delete Account
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
    </div>
</div>

<style>
/* Navigation Styles */
.nav-item {
    color: #6b7280;
    text-align: left;
}

.nav-item:hover {
    background-color: #f3f4f6;
    color: #1f2937;
}

.nav-item.active {
    background-color: #EFF6FF;
    color: #4A90E2;
    font-weight: 600;
}

/* Content Section Animations */
.content-section {
    animation: fadeIn 0.3s ease-out;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>

<script>
function showSection(section) {
    // Hide all sections
    document.querySelectorAll('.content-section').forEach(el => {
        el.classList.add('hidden');
    });
    
    // Remove active class from all nav items
    document.querySelectorAll('.nav-item').forEach(el => {
        el.classList.remove('active');
    });
    
    // Show selected section
    document.getElementById('section-' + section).classList.remove('hidden');
    
    // Add active class to selected nav item
    document.getElementById('nav-' + section).classList.add('active');
    
    // Update URL hash
    window.location.hash = section;
    
    // Scroll to top smoothly
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// On page load, check URL hash and show that section
document.addEventListener('DOMContentLoaded', function() {
    const hash = window.location.hash.substring(1); // Remove #
    const validSections = ['profile', 'security', 'sessions'<?php if ($user['role'] !== 'admin'): ?>, 'danger'<?php endif; ?>];
    
    if (hash && validSections.includes(hash)) {
        showSection(hash);
    } else {
        // Default to profile section
        showSection('profile');
    }
});

function confirmDelete() {
    if (confirm('Are you absolutely sure you want to delete your account?\n\nThis action is PERMANENT and cannot be undone!')) {
        if (confirm('FINAL WARNING: This will permanently delete all your data.\n\nClick OK to proceed.')) {
            window.location.href = '/profile/delete';
        }
    }
}
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout/base.php';
?>

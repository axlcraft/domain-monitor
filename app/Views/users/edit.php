<?php
$title = 'Edit User';
$pageTitle = 'Edit User';
$pageDescription = 'Update user information and permissions';
$pageIcon = 'fas fa-user-edit';
ob_start();
?>

<form method="POST" action="/users/update" class="max-w-2xl">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= $user['id'] ?>">
    
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
            <h3 class="text-lg font-semibold text-gray-900">User Information</h3>
        </div>

        <div class="p-6 space-y-4">
            <!-- Full Name -->
            <div>
                <label for="full_name" class="block text-sm font-medium text-gray-700 mb-2">
                    Full Name <span class="text-red-500">*</span>
                </label>
                <input type="text" id="full_name" name="full_name" required
                       value="<?= htmlspecialchars($user['full_name'] ?? '') ?>"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
            </div>

            <!-- Username (Read-only) -->
            <div>
                <label for="username" class="block text-sm font-medium text-gray-700 mb-2">
                    Username
                </label>
                <input type="text" id="username" value="<?= htmlspecialchars($user['username']) ?>" readonly
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50 text-gray-500 cursor-not-allowed">
                <p class="text-xs text-gray-500 mt-1">Username cannot be changed</p>
            </div>

            <!-- Email -->
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                    Email Address <span class="text-red-500">*</span>
                </label>
                <input type="email" id="email" name="email" required
                       value="<?= htmlspecialchars($user['email']) ?>"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
            </div>

            <!-- Role -->
            <div>
                <label for="role" class="block text-sm font-medium text-gray-700 mb-2">
                    Role <span class="text-red-500">*</span>
                </label>
                <select id="role" name="role" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                    <option value="user" <?= $user['role'] === 'user' ? 'selected' : '' ?>>User</option>
                    <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                </select>
            </div>

            <!-- Status -->
            <div class="flex items-start">
                <div class="flex items-center h-5">
                    <input type="checkbox" id="is_active" name="is_active" value="1"
                           <?= $user['is_active'] ? 'checked' : '' ?>
                           class="w-4 h-4 text-primary border-gray-300 rounded focus:ring-primary">
                </div>
                <div class="ml-3">
                    <label for="is_active" class="text-sm font-medium text-gray-700">
                        Active
                    </label>
                    <p class="text-xs text-gray-500">Inactive users cannot log in</p>
                </div>
            </div>

            <!-- Password (Optional) -->
            <div class="border-t border-gray-200 pt-4 mt-4">
                <h4 class="text-sm font-semibold text-gray-900 mb-3">Change Password (Optional)</h4>
                
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                        New Password
                    </label>
                    <input type="password" id="password" name="password" minlength="8"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                    <p class="text-xs text-gray-500 mt-1">Leave blank to keep current password. Minimum 8 characters if changing.</p>
                </div>
            </div>

            <!-- Account Info -->
            <div class="bg-gray-50 border border-gray-200 rounded-lg p-3 mt-4">
                <div class="grid grid-cols-2 gap-3 text-xs">
                    <div>
                        <span class="text-gray-600">Email Verified:</span>
                        <span class="font-semibold <?= $user['email_verified'] ? 'text-green-600' : 'text-red-600' ?>">
                            <?= $user['email_verified'] ? 'Yes' : 'No' ?>
                        </span>
                    </div>
                    <div>
                        <span class="text-gray-600">Member Since:</span>
                        <span class="font-semibold text-gray-900">
                            <?= date('M d, Y', strtotime($user['created_at'])) ?>
                        </span>
                    </div>
                    <div>
                        <span class="text-gray-600">Last Login:</span>
                        <span class="font-semibold text-gray-900">
                            <?= $user['last_login'] ? date('M d, Y H:i', strtotime($user['last_login'])) : 'Never' ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 flex items-center justify-between">
            <a href="/users" class="text-gray-600 hover:text-gray-800 text-sm font-medium">
                <i class="fas fa-arrow-left mr-1"></i> Cancel
            </a>
            <button type="submit" class="inline-flex items-center px-4 py-2.5 bg-primary text-white text-sm rounded-lg hover:bg-primary-dark transition-colors font-medium">
                <i class="fas fa-save mr-2"></i>
                Update User
            </button>
        </div>
    </div>
</form>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout/base.php';
?>


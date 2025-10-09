<?php

use Core\Application;
use Core\Auth;
use App\Controllers\DashboardController;
use App\Controllers\DomainController;
use App\Controllers\NotificationGroupController;
use App\Controllers\AuthController;
use App\Controllers\DebugController;
use App\Controllers\SearchController;
use App\Controllers\TldRegistryController;
use App\Controllers\SettingsController;
use App\Controllers\ProfileController;
use App\Controllers\UserController;
use App\Controllers\InstallerController;
use App\Controllers\NotificationController;

$router = Application::$router;

// Installer routes (public - before auth)
$router->get('/install', [InstallerController::class, 'index']);
$router->get('/install/check-database', [InstallerController::class, 'checkDatabase']);
$router->post('/install/run', [InstallerController::class, 'install']);
$router->get('/install/complete', [InstallerController::class, 'complete']);
$router->get('/install/update', [InstallerController::class, 'showUpdate']);
$router->post('/install/update', [InstallerController::class, 'runUpdate']);

// Authentication routes (public)
$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->get('/logout', [AuthController::class, 'logout']);
$router->get('/register', [AuthController::class, 'showRegister']);
$router->post('/register', [AuthController::class, 'register']);
$router->get('/verify-email', [AuthController::class, 'showVerifyEmail']);
$router->get('/resend-verification', [AuthController::class, 'resendVerification']);
$router->get('/forgot-password', [AuthController::class, 'showForgotPassword']);
$router->post('/forgot-password', [AuthController::class, 'forgotPassword']);
$router->get('/reset-password', [AuthController::class, 'showResetPassword']);
$router->post('/reset-password', [AuthController::class, 'resetPassword']);

// Debug route (public - remove in production!)
$router->get('/debug/whois', [DebugController::class, 'whois']);

// Protected routes - require authentication
Auth::require();

// Dashboard
$router->get('/', [DashboardController::class, 'index']);
$router->get('/dashboard', [DashboardController::class, 'index']);

// Search
$router->get('/search', [SearchController::class, 'index']);
$router->get('/api/search/suggest', [SearchController::class, 'suggest']);

// Domains
$router->get('/domains', [DomainController::class, 'index']);
$router->get('/domains/create', [DomainController::class, 'create']);
$router->get('/domains/bulk-add', [DomainController::class, 'bulkAdd']);
$router->post('/domains/bulk-add', [DomainController::class, 'bulkAdd']);
$router->post('/domains/bulk-refresh', [DomainController::class, 'bulkRefresh']);
$router->post('/domains/bulk-delete', [DomainController::class, 'bulkDelete']);
$router->post('/domains/bulk-assign-group', [DomainController::class, 'bulkAssignGroup']);
$router->post('/domains/bulk-toggle-status', [DomainController::class, 'bulkToggleStatus']);
$router->post('/domains/store', [DomainController::class, 'store']);
$router->get('/domains/{id}', [DomainController::class, 'show']);
$router->get('/domains/{id}/edit', [DomainController::class, 'edit']);
$router->post('/domains/{id}/update', [DomainController::class, 'update']);
$router->post('/domains/{id}/update-notes', [DomainController::class, 'updateNotes']);
$router->post('/domains/{id}/refresh', [DomainController::class, 'refresh']);
$router->post('/domains/{id}/delete', [DomainController::class, 'delete']);

// Notification Groups
$router->get('/groups', [NotificationGroupController::class, 'index']);
$router->get('/groups/create', [NotificationGroupController::class, 'create']);
$router->post('/groups/store', [NotificationGroupController::class, 'store']);
$router->get('/groups/edit', [NotificationGroupController::class, 'edit']);
$router->post('/groups/update', [NotificationGroupController::class, 'update']);
$router->get('/groups/delete', [NotificationGroupController::class, 'delete']);

// Notification Channels
$router->post('/channels/add', [NotificationGroupController::class, 'addChannel']);
$router->get('/channels/delete', [NotificationGroupController::class, 'deleteChannel']);
$router->get('/channels/toggle', [NotificationGroupController::class, 'toggleChannel']);

// TLD Registry
$router->get('/tld-registry', [TldRegistryController::class, 'index']);
$router->get('/tld-registry/{id}', [TldRegistryController::class, 'show']);
$router->post('/tld-registry/import-tld-list', [TldRegistryController::class, 'importTldList']);
$router->post('/tld-registry/import-rdap', [TldRegistryController::class, 'importRdap']);
$router->post('/tld-registry/import-whois', [TldRegistryController::class, 'importWhois']);
$router->post('/tld-registry/start-progressive-import', [TldRegistryController::class, 'startProgressiveImport']);
$router->get('/tld-registry/import-progress/{log_id}', [TldRegistryController::class, 'importProgress']);
$router->get('/tld-registry/api/import-progress', [TldRegistryController::class, 'apiGetImportProgress']);
$router->post('/tld-registry/bulk-delete', [TldRegistryController::class, 'bulkDelete']);
$router->get('/tld-registry/check-updates', [TldRegistryController::class, 'checkUpdates']);
$router->get('/tld-registry/{id}/toggle-active', [TldRegistryController::class, 'toggleActive']);
$router->get('/tld-registry/{id}/refresh', [TldRegistryController::class, 'refresh']);
$router->get('/tld-registry/import-logs', [TldRegistryController::class, 'importLogs']);
$router->get('/api/tld-info', [TldRegistryController::class, 'apiGetTldInfo']);

// Settings
$router->get('/settings', [SettingsController::class, 'index']);
$router->post('/settings/update', [SettingsController::class, 'update']);
$router->post('/settings/update-app', [SettingsController::class, 'updateApp']);
$router->post('/settings/update-email', [SettingsController::class, 'updateEmail']);
$router->post('/settings/test-email', [SettingsController::class, 'testEmail']);
$router->post('/settings/test-cron', [SettingsController::class, 'testCron']);
$router->post('/settings/clear-logs', [SettingsController::class, 'clearLogs']);

// Profile
$router->get('/profile', [ProfileController::class, 'index']);
$router->post('/profile/update', [ProfileController::class, 'update']);
$router->post('/profile/change-password', [ProfileController::class, 'changePassword']);
$router->get('/profile/delete', [ProfileController::class, 'delete']);
$router->get('/profile/resend-verification', [ProfileController::class, 'resendVerification']);
$router->post('/profile/logout-other-sessions', [ProfileController::class, 'logoutOtherSessions']);
$router->post('/profile/logout-session/{sessionId}', [ProfileController::class, 'logoutSession']);

// Notifications
$router->get('/notifications', [NotificationController::class, 'index']);
$router->get('/notifications/{id}/mark-read', [NotificationController::class, 'markAsRead']);
$router->get('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead']);
$router->get('/notifications/{id}/delete', [NotificationController::class, 'delete']);
$router->get('/notifications/clear-all', [NotificationController::class, 'clearAll']);
$router->get('/api/notifications/unread-count', [NotificationController::class, 'getUnreadCount']);
$router->get('/api/notifications/recent', [NotificationController::class, 'getRecent']);

// User Management (Admin Only)
$router->get('/users', [UserController::class, 'index']);
$router->get('/users/create', [UserController::class, 'create']);
$router->post('/users/store', [UserController::class, 'store']);
$router->get('/users/edit', [UserController::class, 'edit']);
$router->post('/users/update', [UserController::class, 'update']);
$router->get('/users/delete', [UserController::class, 'delete']);
$router->get('/users/toggle-status', [UserController::class, 'toggleStatus']);


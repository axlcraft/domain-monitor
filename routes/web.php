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

$router = Application::$router;

// Authentication routes (public)
$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->get('/logout', [AuthController::class, 'logout']);

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


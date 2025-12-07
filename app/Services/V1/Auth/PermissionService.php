<?php

namespace App\Services\V1\Auth;

use App\Repositories\V1\Auth\Interfaces\PermissionRepositoryInterface;
use Illuminate\Support\Collection;

class PermissionService
{
    public function __construct(
        private PermissionRepositoryInterface $permissionRepository
    ) {}
    /**
     * Transform user permissions into a structured UI permissions object.
     * 
     * Converts permissions like:
     * - "auth.users.view" → ui.users.view = true
     * - "billing.invoices.create" → ui.invoices.create = true
     * - "properties.portfolios.view" → ui.portfolios.view = true
     * 
     * @param Collection $permissions Collection of permission names
     * @return array<string, array<string, bool>>
     */
    public function transformToUIPermissions(Collection $permissions): array
    {
        $uiPermissions = [];

        foreach ($permissions as $permission) {
            // Ensure we have a string (permission name), not an object
            if (is_object($permission) && isset($permission->name)) {
                $permissionName = $permission->name;
            } elseif (is_array($permission) && isset($permission['name'])) {
                $permissionName = $permission['name'];
            } elseif (is_string($permission)) {
                $permissionName = $permission;
            } else {
                // Skip if we can't get a valid permission name
                continue;
            }
            
            // Skip if empty or not a string
            if (!is_string($permissionName) || empty(trim($permissionName))) {
                continue;
            }
            
            // Parse permission: "module.resource.action" or "module.resource.subresource.action" or "module.action"
            $parts = explode('.', $permissionName);
            
            // Handle permissions with 2 parts (module.action)
            // Examples: ownerships.view, contracts.view, facilities.view, tenants.view
            if (count($parts) === 2) {
                $resource = $this->normalizeResourceName($parts[0]); // Use module name as resource
                $action = $this->normalizeActionName($parts[1]);
                
                // Initialize resource if not exists
                if (!isset($uiPermissions[$resource])) {
                    $uiPermissions[$resource] = [];
                }
                
                $uiPermissions[$resource][$action] = true;
                continue;
            }
            
            if (count($parts) < 3) {
                continue; // Skip invalid permissions (need at least module.resource.action)
            }

            // Remove first part (module prefix like "auth", "billing", "properties", etc.)
            array_shift($parts);
            
            // After removing module, we need at least 2 parts (resource and action)
            if (count($parts) < 2) {
                continue; // Skip if not enough parts after removing module
            }
            
            // Now we have: ["resource", "action"] or ["resource", "subresource", "action"] or ["resource", "action", "modifier"]
            // Examples:
            // - ["users", "view"] → resource: users, action: view
            // - ["portfolios", "view"] → resource: portfolios, action: view (from properties.portfolios.view)
            // - ["users", "view", "own"] → resource: users, action: viewOwn
            // - ["invoices", "generate"] → resource: invoices, action: generate
            // - ["tenants", "rating", "update"] → resource: tenants, action: updateRating
            
            $resource = $parts[0] ?? null;
            $action = $parts[1] ?? null;
            
            // Skip if resource or action is missing
            if (!$resource || !$action) {
                continue;
            }
            
            // Handle special cases with 3+ parts
            if (count($parts) === 4) {
                // Handle 4 parts: "ownerships.users.set-default"
                if ($parts[0] === 'users' && $parts[1] === 'set' && $parts[2] === 'default') {
                    $resource = 'users';
                    $action = 'setDefault';
                } else {
                    // Generic handling: use last part as action
                    $resource = $parts[0];
                    $action = $parts[count($parts) - 1];
                }
            } elseif (count($parts) === 3) {
                // Check if it's a compound action (e.g., "view.own", "rating.update")
                if ($parts[1] === 'view' && $parts[2] === 'own') {
                    $action = 'viewOwn';
                } elseif ($parts[1] === 'update' && $parts[2] === 'own') {
                    $action = 'editOwn';
                } elseif ($parts[1] === 'rating' && $parts[2] === 'update') {
                    $action = 'updateRating';
                } elseif ($parts[1] === 'board' && $parts[2] === 'view') {
                    // ownerships.board.view → resource: board, action: view
                    $resource = 'board';
                    $action = 'view';
                } elseif ($parts[1] === 'board' && $parts[2] === 'manage') {
                    // ownerships.board.manage → resource: board, action: manage
                    $resource = 'board';
                    $action = 'manage';
                } elseif ($parts[1] === 'users' && $parts[2] === 'view') {
                    // ownerships.users.view → resource: users, action: view
                    $resource = 'users';
                    $action = 'view';
                } elseif ($parts[1] === 'users' && $parts[2] === 'assign') {
                    // ownerships.users.assign → resource: users, action: assign
                    $resource = 'users';
                    $action = 'assign';
                } elseif ($parts[1] === 'users' && $parts[2] === 'remove') {
                    // ownerships.users.remove → resource: users, action: remove
                    $resource = 'users';
                    $action = 'remove';
                } elseif ($parts[1] === 'bookings' && in_array($parts[2], ['view', 'create', 'approve', 'cancel'])) {
                    // facilities.bookings.view → resource: bookings, action: view
                    $resource = 'bookings';
                    $action = $parts[2];
                } elseif ($parts[1] === 'categories' && in_array($parts[2], ['view', 'manage'])) {
                    // maintenance.categories.view → resource: categories, action: view
                    $resource = 'categories';
                    $action = $parts[2];
                } elseif ($parts[1] === 'requests' && in_array($parts[2], ['view', 'create', 'update', 'assign'])) {
                    // maintenance.requests.view → resource: requests, action: view
                    $resource = 'requests';
                    $action = $parts[2];
                } elseif ($parts[1] === 'technicians' && in_array($parts[2], ['view', 'manage'])) {
                    // maintenance.technicians.view → resource: technicians, action: view
                    $resource = 'technicians';
                    $action = $parts[2];
                } elseif ($parts[1] === 'documents' && in_array($parts[2], ['view', 'upload', 'delete'])) {
                    // system.documents.view → resource: documents, action: view
                    $resource = 'documents';
                    $action = $parts[2];
                } elseif ($parts[1] === 'notifications' && in_array($parts[2], ['view', 'send'])) {
                    // system.notifications.view → resource: notifications, action: view
                    $resource = 'notifications';
                    $action = $parts[2];
                } elseif ($parts[1] === 'settings' && in_array($parts[2], ['view', 'update'])) {
                    // system.settings.view → resource: settings, action: view
                    $resource = 'settings';
                    $action = $parts[2];
                } else {
                    // For nested resources like "properties.portfolios.view"
                    // The resource is the second part (portfolios), action is the third part (view)
                    $resource = $parts[1];
                    $action = $parts[2];
                }
            }

            // Normalize resource name (convert to camelCase)
            $resource = $this->normalizeResourceName($resource);
            
            // Normalize action name
            $action = $this->normalizeActionName($action);

            // Initialize resource if not exists
            if (!isset($uiPermissions[$resource])) {
                $uiPermissions[$resource] = [];
            }

            // Set permission to true
            $uiPermissions[$resource][$action] = true;
        }

        // Fill missing actions with false for consistency
        return $this->fillMissingActions($uiPermissions);
    }

    /**
     * Normalize resource name.
     */
    protected function normalizeResourceName(string $resource): string
    {
        // Convert to camelCase
        $resource = str_replace('_', ' ', $resource);
        $resource = ucwords($resource);
        $resource = str_replace(' ', '', $resource);
        $resource = lcfirst($resource);
        
        return $resource;
    }

    /**
     * Normalize action name.
     */
    protected function normalizeActionName(string $action): string
    {
        // Map common actions
        $actionMap = [
            'view' => 'view',
            'viewOwn' => 'viewOwn',
            'create' => 'create',
            'update' => 'edit',
            'updateOwn' => 'editOwn',
            'updateRating' => 'updateRating',
            'edit' => 'edit',
            'delete' => 'delete',
            'activate' => 'activate',
            'deactivate' => 'deactivate',
            'switch' => 'switch',
            'assign' => 'assign',
            'remove' => 'remove',
            'setDefault' => 'setDefault',
            'approve' => 'approve',
            'sign' => 'sign',
            'terminate' => 'terminate',
            'verify' => 'verify',
            'confirm' => 'confirm',
            'generate' => 'generate',
            'manage' => 'manage',
            'upload' => 'upload',
            'send' => 'send',
            'cancel' => 'cancel',
        ];

        return $actionMap[$action] ?? $action;
    }

    /**
     * Fill missing actions with false for all resources.
     */
    protected function fillMissingActions(array $uiPermissions): array
    {
        // Common actions that should be present for all resources
        $commonActions = ['view', 'create', 'edit', 'delete'];
        
        foreach ($uiPermissions as $resource => $actions) {
            foreach ($commonActions as $action) {
                if (!isset($uiPermissions[$resource][$action])) {
                    $uiPermissions[$resource][$action] = false;
                }
            }
        }

        return $uiPermissions;
    }

    /**
     * Get permissions grouped by module.
     */
    public function getGroupedByModule(): array
    {
        return $this->permissionRepository->getGroupedByModule();
    }

    /**
     * Get permissions grouped by module in UI-friendly format.
     * 
     * Returns permissions in a structured format for frontend:
     * {
     *   "auth": {
     *     "users": {
     *       "view": 1,
     *       "create": 2,
     *       "edit": 3,
     *       "delete": 4
     *     },
     *     "roles": {
     *       "view": 5,
     *       "create": 6
     *     }
     *   },
     *   "billing": {
     *     "invoices": {
     *       "view": 10,
     *       "create": 11
     *     }
     *   }
     * }
     * 
     * @return array<string, array<string, array<string, int>>>
     */
    public function getGroupedByModuleForUI(): array
    {
        $grouped = $this->permissionRepository->getGroupedByModule();
        $uiGrouped = [];

        foreach ($grouped as $module => $permissions) {
            $uiGrouped[$module] = [];

            foreach ($permissions as $permission) {
                $permissionName = $permission->name;
                $parts = explode('.', $permissionName);

                // Handle permissions with 2 parts (module.action)
                // Examples: ownerships.view, contracts.view, facilities.view, tenants.view
                if (count($parts) === 2) {
                    $resource = $this->normalizeResourceName($parts[0]); // Use module name as resource
                    $action = $this->normalizeActionName($parts[1]);
                    
                    // Initialize resource if not exists
                    if (!isset($uiGrouped[$module][$resource])) {
                        $uiGrouped[$module][$resource] = [];
                    }
                    
                    $uiGrouped[$module][$resource][$action] = $permission->id;
                    continue;
                }

                // Skip if not enough parts (need at least module.resource.action)
                if (count($parts) < 3) {
                    continue;
                }

                // Remove module (first part)
                array_shift($parts);

                // After removing module, we need at least 2 parts (resource and action)
                if (count($parts) < 2) {
                    continue;
                }

                $resource = $parts[0] ?? null;
                $action = $parts[1] ?? null;

                if (!$resource || !$action) {
                    continue;
                }

                // Handle special cases with 3+ parts (same logic as transformToUIPermissions)
                if (count($parts) === 4) {
                    // Handle 4 parts: "ownerships.users.set-default"
                    if ($parts[0] === 'users' && $parts[1] === 'set' && $parts[2] === 'default') {
                        $resource = 'ownershipsUsers';
                        $action = 'setDefault';
                    } else {
                        // Generic handling: use last part as action
                        $resource = $parts[0];
                        $action = $parts[count($parts) - 1];
                    }
                } elseif (count($parts) === 3) {
                    if ($parts[1] === 'view' && $parts[2] === 'own') {
                        $action = 'viewOwn';
                    } elseif ($parts[1] === 'update' && $parts[2] === 'own') {
                        $action = 'editOwn';
                    } elseif ($parts[1] === 'rating' && $parts[2] === 'update') {
                        $resource = 'tenants';
                        $action = 'updateRating';
                    } elseif ($parts[1] === 'board' && ($parts[2] === 'view' || $parts[2] === 'manage')) {
                        $resource = 'ownershipsBoard';
                        $action = $parts[2];
                    } elseif ($parts[1] === 'users' && $parts[2] === 'view') {
                        $resource = 'ownershipsUsers';
                        $action = 'view';
                    } elseif ($parts[1] === 'users' && $parts[2] === 'assign') {
                        $resource = 'ownershipsUsers';
                        $action = 'assign';
                    } elseif ($parts[1] === 'users' && $parts[2] === 'remove') {
                        $resource = 'ownershipsUsers';
                        $action = 'remove';
                    } elseif ($parts[1] === 'categories' && $parts[2] === 'manage') {
                        $resource = 'maintenanceCategories';
                        $action = 'manage';
                    } elseif ($parts[1] === 'technicians' && $parts[2] === 'manage') {
                        $resource = 'maintenanceTechnicians';
                        $action = 'manage';
                    } elseif ($parts[1] === 'settings' && $parts[2] === 'update') {
                        $resource = 'systemSettings';
                        $action = 'edit';
                    } elseif ($parts[1] === 'notifications' && $parts[2] === 'send') {
                        $resource = 'systemNotifications';
                        $action = 'send';
                    } elseif ($parts[1] === 'audit' && $parts[2] === 'view') {
                        $resource = 'systemAudit';
                        $action = 'view';
                    } elseif ($parts[1] === 'documents' && ($parts[2] === 'upload' || $parts[2] === 'delete')) {
                        $resource = 'systemDocuments';
                        $action = $parts[2];
                    } else {
                        // Generic handling for 3 parts
                        $resource = $parts[1];
                        $action = $parts[2];
                    }
                }

                // Normalize resource and action names
                $resource = $this->normalizeResourceName($resource);
                $action = $this->normalizeActionName($action);

                // Initialize resource if not exists
                if (!isset($uiGrouped[$module][$resource])) {
                    $uiGrouped[$module][$resource] = [];
                }

                // Store permission ID (for sync with roles)
                $uiGrouped[$module][$resource][$action] = $permission->id;
            }

            // Sort resources and actions for consistent output
            foreach ($uiGrouped[$module] as $resource => $actions) {
                ksort($uiGrouped[$module][$resource]);
            }
            ksort($uiGrouped[$module]);
        }

        // Sort modules for consistent output
        ksort($uiGrouped);

        return $uiGrouped;
    }

    /**
     * Paginate permissions.
     */
    public function paginate(int $perPage = 15, array $filters = []): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return $this->permissionRepository->paginate($perPage, $filters);
    }

    /**
     * Find permission by ID.
     */
    public function find(int $id): ?\App\Models\V1\Auth\Permission
    {
        return $this->permissionRepository->find($id);
    }

    // Note: create, update, delete methods removed
    // Permissions are hard-coded in seeders and cannot be modified via API
}

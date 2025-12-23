<?php

namespace App\Services\V1\Invoice;

use App\Models\V1\Auth\User;
use App\Models\V1\Invoice\CollectorTenantAssignment;
use App\Models\V1\Invoice\Invoice;
use App\Models\V1\Tenant\Tenant;
use App\Services\V1\Invoice\InvoiceSettingService;
use Illuminate\Support\Facades\Log;

class CollectorService
{
    public function __construct(
        private InvoiceSettingService $settings
    ) {}
    
    /**
     * Assign collector to tenants.
     */
    public function assignTenants(int $collectorId, array $tenantIds, int $ownershipId, ?int $assignedBy = null): array
    {
        // Check if collector system is enabled
        if (!$this->settings->isCollectorSystemEnabled($ownershipId)) {
            throw new \Exception('Collector system is not enabled for this ownership');
        }
        
        $collector = User::findOrFail($collectorId);
        
        if (!$collector->isCollector()) {
            throw new \Exception('User is not a collector');
        }
        
        $assigned = [];
        $skipped = [];
        
        foreach ($tenantIds as $tenantId) {
            $tenant = Tenant::findOrFail($tenantId);
            
            // Verify tenant belongs to ownership
            if ($tenant->ownership_id !== $ownershipId) {
                $skipped[] = [
                    'tenant_id' => $tenantId,
                    'reason' => 'Tenant does not belong to ownership',
                ];
                continue;
            }
            
            // Check if assignment already exists (regardless of is_active status)
            $existing = CollectorTenantAssignment::where('collector_id', $collectorId)
                ->where('tenant_id', $tenantId)
                ->where('ownership_id', $ownershipId)
                ->first();
                
            if ($existing) {
                // If already active, skip
                if ($existing->is_active) {
                    $skipped[] = [
                        'tenant_id' => $tenantId,
                        'reason' => 'Already assigned',
                    ];
                    continue;
                }
                
                // If inactive, update to active
                $existing->update([
                    'is_active' => true,
                    'assigned_at' => now(),
                    'assigned_by' => $assignedBy,
                    'unassigned_at' => null,
                ]);
                
                $assigned[] = $existing->fresh();
            } else {
                // Create new assignment
                $assignment = CollectorTenantAssignment::create([
                    'collector_id' => $collectorId,
                    'tenant_id' => $tenantId,
                    'ownership_id' => $ownershipId,
                    'is_active' => true,
                    'assigned_at' => now(),
                    'assigned_by' => $assignedBy,
                ]);
                
                $assigned[] = $assignment;
            }
        }
        
        return [
            'assigned' => $assigned,
            'skipped' => $skipped,
        ];
    }
    
    /**
     * Unassign collector from tenants.
     */
    public function unassignTenants(int $collectorId, array $tenantIds, int $ownershipId): int
    {
        return CollectorTenantAssignment::where('collector_id', $collectorId)
            ->whereIn('tenant_id', $tenantIds)
            ->where('ownership_id', $ownershipId)
            ->where('is_active', true)
            ->update([
                'is_active' => false,
                'unassigned_at' => now(),
            ]);
    }
    
    /**
     * Get tenants visible to collector.
     */
    public function getVisibleTenants(User $collector, int $ownershipId): \Illuminate\Database\Eloquent\Collection
    {
        // Check if collector system is enabled
        if (!$this->settings->isCollectorSystemEnabled($ownershipId)) {
            return collect(); // Return empty collection if system is disabled
        }
        
        // Check if collector can see all tenants
        if ($this->settings->collectorsCanSeeAllTenants($ownershipId)) {
            return Tenant::where('ownership_id', $ownershipId)
                ->with('user')
                ->get();
        }
        
        // Return only assigned tenants with user relationship loaded
        return $collector->assignedTenants($ownershipId)
            ->with('user')
            ->get();
    }
    
    /**
     * Check if collector can see tenant.
     */
    public function canSeeTenant(User $collector, int $tenantId, int $ownershipId): bool
    {
        // Check if collector system is enabled
        if (!$this->settings->isCollectorSystemEnabled($ownershipId)) {
            return false;
        }
        
        // If can see all, return true
        if ($this->settings->collectorsCanSeeAllTenants($ownershipId)) {
            return true;
        }
        $result = CollectorTenantAssignment::where('collector_id', $collector->id)
        ->where('tenant_id', $tenantId)
        ->where('ownership_id', $ownershipId)
        ->where('is_active', true)
        ->exists();
        Log::info('CollectorService::canSeeTenant', ['result' => $result]);
        // Check if assigned
        return $result;
    }
    
    /**
     * Get invoices visible to collector.
     * Note: Invoice.contract_id can be nullable (for standalone invoices).
     */
    public function getVisibleInvoices(User $collector, int $ownershipId): \Illuminate\Database\Eloquent\Builder
    {
        $query = Invoice::where('ownership_id', $ownershipId);
        
        // Check if collector system is enabled
        if (!$this->settings->isCollectorSystemEnabled($ownershipId)) {
            // Return empty query if system is disabled
            return $query->whereRaw('1 = 0');
        }
        
        // If can see all tenants, return all invoices
        if ($this->settings->collectorsCanSeeAllTenants($ownershipId)) {
            return $query;
        }
        
        // Filter by assigned tenants
        $tenantIds = $collector->assignedTenants($ownershipId)->select('tenants.id')->pluck('id');
        
        // Filter invoices that are linked to contracts with assigned tenants
        // Note: Standalone invoices (contract_id = null) are not visible to collectors
        return $query->whereHas('contract', function ($q) use ($tenantIds) {
            $q->whereIn('tenant_id', $tenantIds);
        });
    }
    
    /**
     * Auto-assign collector to tenant based on assignment method.
     * 
     * @param int $tenantId
     * @param int $ownershipId
     * @return int|null Collector ID if assigned, null otherwise
     */
    public function autoAssignCollector(int $tenantId, int $ownershipId): ?int
    {
        // Check if collector system is enabled
        if (!$this->settings->isCollectorSystemEnabled($ownershipId)) {
            return null;
        }
        
        $method = $this->settings->getCollectorAssignmentMethod($ownershipId);
        
        if ($method === 'manual') {
            return null; // Manual assignment only
        }
        
        // Get all collectors for this ownership
        $collectors = User::role('Collector')
            ->whereHas('ownerships', function ($q) use ($ownershipId) {
                $q->where('ownerships.id', $ownershipId);
            })
            ->get();
        
        if ($collectors->isEmpty()) {
            return null; // No collectors available
        }
        
        if ($method === 'round_robin') {
            // Find collector with least assignments
            $collectorCounts = CollectorTenantAssignment::where('ownership_id', $ownershipId)
                ->where('is_active', true)
                ->selectRaw('collector_id, COUNT(*) as count')
                ->groupBy('collector_id')
                ->pluck('count', 'collector_id')
                ->toArray();
            
            $selectedCollector = null;
            $minCount = PHP_INT_MAX;
            
            foreach ($collectors as $collector) {
                $count = $collectorCounts[$collector->id] ?? 0;
                if ($count < $minCount) {
                    $minCount = $count;
                    $selectedCollector = $collector;
                }
            }
            
            if ($selectedCollector) {
                $this->assignTenants($selectedCollector->id, [$tenantId], $ownershipId);
                return $selectedCollector->id;
            }
        } elseif ($method === 'auto') {
            // Auto-assign to first available collector
            $firstCollector = $collectors->first();
            if ($firstCollector) {
                $this->assignTenants($firstCollector->id, [$tenantId], $ownershipId);
                return $firstCollector->id;
            }
        }
        
        return null;
    }
}


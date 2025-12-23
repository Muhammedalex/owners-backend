<?php

namespace App\Http\Controllers\Api\V1\Invoice;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\Invoice\CollectorResource;
use App\Http\Resources\V1\Tenant\TenantResource;
use App\Models\V1\Auth\User;
use App\Services\V1\Invoice\CollectorService;
use App\Services\V1\Invoice\InvoiceSettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CollectorController extends Controller
{
    public function __construct(
        private CollectorService $service,
        private InvoiceSettingService $invoiceSettings
    ) {}
    
    /**
     * List collectors.
     */
    public function index(Request $request): JsonResponse
    {
        $ownershipId = $request->input('current_ownership_id');
        
        // Check if collector system is enabled
        if (!$this->invoiceSettings->isCollectorSystemEnabled($ownershipId)) {
            return response()->json([
                'success' => false,
                'message' => 'Collector system is not enabled for this ownership',
            ], 403);
        }
        
        $collectors = User::role('Collector')
            ->whereHas('ownerships', function ($q) use ($ownershipId) {
                $q->where('ownerships.id', $ownershipId);
            })
            ->paginate();
            
        return response()->json([
            'success' => true,
            'data' => CollectorResource::collection($collectors),
        ]);
    }
    
    /**
     * Show collector details with assigned tenants.
     */
    public function show(Request $request, int $collectorId): JsonResponse
    {
        $ownershipId = $request->input('current_ownership_id');
        
        // Check if collector system is enabled
        if (!$this->invoiceSettings->isCollectorSystemEnabled($ownershipId)) {
            return response()->json([
                'success' => false,
                'message' => 'Collector system is not enabled for this ownership',
            ], 403);
        }
        
        $collector = User::role('Collector')
            ->whereHas('ownerships', function ($q) use ($ownershipId) {
                $q->where('ownerships.id', $ownershipId);
            })
            ->findOrFail($collectorId);
        
        // Get assigned tenants for this ownership and attach them to the collector
        $assignedTenants = $collector->assignedTenants($ownershipId)->with('user')->get();
        $collector->setRelation('assignedTenants', $assignedTenants);
            
        return response()->json([
            'success' => true,
            'data' => new CollectorResource($collector),
        ]);
    }
    
    /**
     * Assign tenants to collector.
     */
    public function assignTenants(Request $request, int $collectorId): JsonResponse
    {
        $ownershipId = $request->input('current_ownership_id');
        
        // Check if collector system is enabled
        if (!$this->invoiceSettings->isCollectorSystemEnabled($ownershipId)) {
            return response()->json([
                'success' => false,
                'message' => 'Collector system is not enabled for this ownership',
            ], 403);
        }
        
        $request->validate([
            'tenant_ids' => 'required|array',
            'tenant_ids.*' => 'exists:tenants,id',
        ]);
        
        $result = $this->service->assignTenants(
            $collectorId,
            $request->tenant_ids,
            $ownershipId,
            $request->user()->id
        );
        
        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }
    
    /**
     * Unassign tenants from collector.
     */
    public function unassignTenants(Request $request, int $collectorId): JsonResponse
    {
        $ownershipId = $request->input('current_ownership_id');
        
        // Check if collector system is enabled
        if (!$this->invoiceSettings->isCollectorSystemEnabled($ownershipId)) {
            return response()->json([
                'success' => false,
                'message' => 'Collector system is not enabled for this ownership',
            ], 403);
        }
        
        $request->validate([
            'tenant_ids' => 'required|array',
            'tenant_ids.*' => 'exists:tenants,id',
        ]);
        
        $count = $this->service->unassignTenants(
            $collectorId,
            $request->tenant_ids,
            $ownershipId
        );
        
        return response()->json([
            'success' => true,
            'message' => "Unassigned {$count} tenant(s).",
        ]);
    }
    
    /**
     * Get collector's assigned tenants.
     */
    public function assignedTenants(Request $request, int $collectorId): JsonResponse
    {
        $ownershipId = $request->input('current_ownership_id');
        
        // Check if collector system is enabled
        if (!$this->invoiceSettings->isCollectorSystemEnabled($ownershipId)) {
            return response()->json([
                'success' => false,
                'message' => 'Collector system is not enabled for this ownership',
            ], 403);
        }
        
        $collector = User::findOrFail($collectorId);
        $tenants = $this->service->getVisibleTenants($collector, $ownershipId);
        
        return response()->json([
            'success' => true,
            'data' => TenantResource::collection($tenants),
        ]);
    }
}

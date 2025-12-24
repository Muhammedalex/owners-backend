<?php

namespace App\Http\Controllers\Api\V1\Ownership;

use App\Exports\V1\Ownership\UnitsExport;
use App\Exports\V1\Ownership\UnitsTemplateExport;
use App\Imports\V1\Ownership\UnitsImport;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Ownership\ImportUnitsRequest;
use App\Models\V1\Ownership\Building;
use App\Models\V1\Ownership\Unit;
use App\Traits\HasLocalizedResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class UnitImportExportController extends Controller
{
    use HasLocalizedResponse;

    /**
     * Download Excel template (generic - no building selected)
     */
    public function downloadTemplate(Request $request): BinaryFileResponse
    {
        $this->authorize('create', Unit::class);
        
        $ownershipId = $request->input('current_ownership_id');
        $export = new UnitsTemplateExport(null, $ownershipId);
        $filename = 'units_import_template_' . date('Y-m-d') . '.xlsx';
        
        return Excel::download($export, $filename);
    }

    /**
     * Download Excel template for specific building
     */
    public function downloadTemplateForBuilding(Request $request, Building $building): BinaryFileResponse
    {
        $this->authorize('create', Unit::class);
        
        // Verify building belongs to current ownership
        $ownershipId = $request->input('current_ownership_id');
        if (!$ownershipId || $building->ownership_id != $ownershipId) {
            return $this->errorResponse('messages.errors.building_not_found', 404);
        }

        $export = new UnitsTemplateExport($building, $ownershipId);
        $filename = 'units_import_template_' . str_replace(' ', '_', $building->name) . '_' . date('Y-m-d') . '.xlsx';
        
        return Excel::download($export, $filename);
    }

    /**
     * Export units to Excel
     */
    public function export(Request $request): BinaryFileResponse
    {
        $this->authorize('viewAny', Unit::class);
        
        $ownershipId = $request->input('current_ownership_id');
        if (!$ownershipId) {
            abort(400, 'Ownership ID is required');
        }

        $filters = $request->only(['building_id', 'status', 'type', 'active']);
        $export = new UnitsExport($ownershipId, $filters);
        $filename = 'units_export_' . date('Y-m-d_His') . '.xlsx';
        
        return Excel::download($export, $filename);
    }

    /**
     * Import units from Excel
     */
    public function import(ImportUnitsRequest $request): JsonResponse
    {
        $this->authorize('create', Unit::class);

        $ownershipId = $request->input('current_ownership_id');
        if (!$ownershipId) {
            return $this->errorResponse('messages.errors.ownership_required', 400);
        }

        $buildingId = $request->input('building_id');
        $skipErrors = $request->boolean('skip_errors', false);

        // Verify building if provided
        if ($buildingId) {
            $building = Building::where('id', $buildingId)
                ->where('ownership_id', $ownershipId)
                ->first();
            
            if (!$building) {
                return $this->errorResponse('messages.errors.building_not_found', 404);
            }
        }

        try {
            $import = new UnitsImport($ownershipId, $buildingId, $skipErrors);
            
            Excel::import($import, $request->file('file'));

            $results = $import->getResults();
            $failures = $import->getFailures();

            return $this->successResponse([
                'message' => 'Import completed',
                'results' => [
                    'success' => $results['success'] ?? 0,
                    'failed' => $results['failed'] ?? 0,
                    'skipped' => $results['skipped'] ?? 0,
                    'errors' => array_merge($results['errors'] ?? [], $failures),
                    'warnings' => $results['warnings'] ?? [],
                ],
            ], 'units.imported');
        } catch (\Exception $e) {
            return $this->errorResponse('units.import_failed', 422, [
                'error' => $e->getMessage(),
            ]);
        }
    }
}


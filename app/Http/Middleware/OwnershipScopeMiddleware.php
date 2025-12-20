<?php

namespace App\Http\Middleware;

use App\Models\V1\Ownership\Ownership;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class OwnershipScopeMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // If user is not authenticated, let auth middleware handle it
        if (!$user) {
            return $next($request);
        }

        // Read ownership_uuid from cookie
        $ownershipUuid = $request->cookie('ownership_uuid');
        Log::info('ownershipUuid', ['ownershipUuid' => $ownershipUuid]);
        // If no cookie and user is not Super Admin, try to get default ownership
        if (!$ownershipUuid && !$user->isSuperAdmin()) {
            $defaultOwnership = $user->getDefaultOwnership();
            if ($defaultOwnership) {
                $ownershipUuid = $defaultOwnership->uuid;
            } else {
                // User has no ownerships and is not Super Admin
                return response()->json([
                    'success' => false,
                    'message' => 'No ownership assigned. Please contact administrator.',
                ], 403);
            }
        }
        Log::info('ownershipUuid', ['ownershipUuid' => $ownershipUuid]);
        // If still no UUID (Super Admin without cookie), allow access without scope
        if (!$ownershipUuid) {
            // Super Admin can access without ownership scope
            return $next($request);
        }

        // Find ownership by UUID
        $ownership = Ownership::where('uuid', $ownershipUuid)->first();

        if (!$ownership) {
            return response()->json([
                'success' => false,
                'message' => 'Ownership not found.',
            ], 404);
        }

        // Check if ownership is active
        if (!$ownership->isActive() && !$user->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Ownership is inactive.',
            ], 403);
        }

        // Check access (Super Admin bypass)
        if (!$user->isSuperAdmin()) {
            if (!$user->hasOwnership($ownership->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have access to this ownership.',
                ], 403);
            }
        }

        // Store ownership in request for later use
        $request->merge([
            'current_ownership' => $ownership,
            'current_ownership_id' => $ownership->id,
            'current_ownership_uuid' => $ownership->uuid,
        ]);

        return $next($request);
    }
}

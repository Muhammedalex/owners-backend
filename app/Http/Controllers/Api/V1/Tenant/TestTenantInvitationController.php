<?php

namespace App\Http\Controllers\Api\V1\Tenant;

use App\Http\Controllers\Controller;
use App\Models\V1\Auth\User;
use App\Models\V1\Ownership\Ownership;
use App\Services\V1\Tenant\TenantInvitationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TestTenantInvitationController extends Controller
{
    public function __construct(
        private TenantInvitationService $invitationService
    ) {}

    /**
     * Test endpoint for creating invitation (for development/testing only).
     * This endpoint bypasses normal authentication for easy testing.
     */
    public function testCreate(Request $request): JsonResponse
    {
        // Only allow in non-production environments
        if (app()->environment('production')) {
            return response()->json([
                'success' => false,
                'message' => 'This endpoint is only available in development mode.',
            ], 403);
        }

        $request->validate([
            'email' => 'required|email',
            'ownership_uuid' => 'nullable|exists:ownerships,uuid',
            'user_id' => 'nullable|exists:users,id',
            'name' => 'nullable|string|max:255',
            'expires_in_days' => 'nullable|integer|min:1|max:30',
        ]);

        try {
            DB::beginTransaction();

            // Get ownership
            $ownership = $request->has('ownership_uuid')
                ? Ownership::where('uuid', $request->ownership_uuid)->first()
                : Ownership::first();

            if (!$ownership) {
                return response()->json([
                    'success' => false,
                    'message' => 'No ownership found.',
                ], 404);
            }

            // Get user
            $user = $request->has('user_id')
                ? User::find($request->user_id)
                : User::first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'No user found.',
                ], 404);
            }

            // Create invitation
            $invitation = $this->invitationService->create([
                'ownership_id' => $ownership->id,
                'invited_by' => $user->id,
                'email' => $request->email,
                'name' => $request->name ?? 'Test Tenant',
                'expires_in_days' => $request->expires_in_days ?? 7,
                'notes' => 'Test invitation from API endpoint',
            ]);

            DB::commit();

            Log::channel('emails')->info('Test Invitation Created', [
                'invitation_uuid' => $invitation->uuid,
                'email' => $invitation->email,
                'token' => $invitation->token,
                'url' => $invitation->getInvitationUrl(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Invitation created successfully. Check storage/logs/emails.log for email content.',
                'data' => [
                    'invitation' => [
                        'uuid' => $invitation->uuid,
                        'email' => $invitation->email,
                        'token' => $invitation->token,
                        'status' => $invitation->status,
                        'expires_at' => $invitation->expires_at->format('Y-m-d H:i:s'),
                        'invitation_url' => $invitation->getInvitationUrl(),
                    ],
                    'log_file' => storage_path('logs/emails.log'),
                ],
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
                'trace' => app()->environment('local') ? $e->getTraceAsString() : null,
            ], 500);
        }
    }

    /**
     * Test endpoint for bulk invitations.
     */
    public function testBulk(Request $request): JsonResponse
    {
        if (app()->environment('production')) {
            return response()->json([
                'success' => false,
                'message' => 'This endpoint is only available in development mode.',
            ], 403);
        }

        $request->validate([
            'invitations' => 'required|array|min:1|max:10',
            'invitations.*.email' => 'required|email',
            'invitations.*.name' => 'nullable|string|max:255',
            'ownership_uuid' => 'nullable|exists:ownerships,uuid',
            'user_id' => 'nullable|exists:users,id',
        ]);

        try {
            DB::beginTransaction();

            $ownership = $request->has('ownership_uuid')
                ? Ownership::where('uuid', $request->ownership_uuid)->first()
                : Ownership::first();

            if (!$ownership) {
                return response()->json([
                    'success' => false,
                    'message' => 'No ownership found.',
                ], 404);
            }

            $user = $request->has('user_id')
                ? User::find($request->user_id)
                : User::first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'No user found.',
                ], 404);
            }

            $invitations = $this->invitationService->createBulk(
                $request->invitations,
                $ownership->id,
                $user->id
            );

            DB::commit();

            Log::channel('emails')->info('Test Bulk Invitations Created', [
                'count' => $invitations->count(),
                'invitations' => $invitations->map(fn($inv) => [
                    'uuid' => $inv->uuid,
                    'email' => $inv->email,
                    'token' => $inv->token,
                ])->toArray(),
            ]);

            return response()->json([
                'success' => true,
                'message' => "Created {$invitations->count()} invitations. Check storage/logs/emails.log for email content.",
                'data' => [
                    'count' => $invitations->count(),
                    'invitations' => $invitations->map(fn($inv) => [
                        'uuid' => $inv->uuid,
                        'email' => $inv->email,
                        'token' => $inv->token,
                        'invitation_url' => $inv->getInvitationUrl(),
                    ]),
                    'log_file' => storage_path('logs/emails.log'),
                ],
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
                'trace' => app()->environment('local') ? $e->getTraceAsString() : null,
            ], 500);
        }
    }

    /**
     * Test endpoint for generating link only.
     */
    public function testGenerateLink(Request $request): JsonResponse
    {
        if (app()->environment('production')) {
            return response()->json([
                'success' => false,
                'message' => 'This endpoint is only available in development mode.',
            ], 403);
        }

        $request->validate([
            'email' => 'nullable|email',
            'phone' => 'nullable|string|max:20',
            'ownership_uuid' => 'nullable|exists:ownerships,uuid',
            'user_id' => 'nullable|exists:users,id',
            'name' => 'nullable|string|max:255',
        ]);

        try {
            DB::beginTransaction();

            $ownership = $request->has('ownership_uuid')
                ? Ownership::where('uuid', $request->ownership_uuid)->first()
                : Ownership::first();

            if (!$ownership) {
                return response()->json([
                    'success' => false,
                    'message' => 'No ownership found.',
                ], 404);
            }

            $user = $request->has('user_id')
                ? User::find($request->user_id)
                : User::first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'No user found.',
                ], 404);
            }

            if (!$request->email && !$request->phone) {
                return response()->json([
                    'success' => false,
                    'message' => 'Either email or phone is required.',
                ], 400);
            }

            $invitation = $this->invitationService->generateLink([
                'ownership_id' => $ownership->id,
                'invited_by' => $user->id,
                'email' => $request->email,
                'phone' => $request->phone,
                'name' => $request->name ?? 'Test Tenant',
                'expires_in_days' => 7,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Link generated successfully (no email sent).',
                'data' => [
                    'invitation' => [
                        'uuid' => $invitation->uuid,
                        'email' => $invitation->email,
                        'phone' => $invitation->phone,
                        'token' => $invitation->token,
                        'status' => $invitation->status,
                        'expires_at' => $invitation->expires_at->format('Y-m-d H:i:s'),
                        'invitation_url' => $invitation->getInvitationUrl(),
                    ],
                ],
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
                'trace' => app()->environment('local') ? $e->getTraceAsString() : null,
            ], 500);
        }
    }
}


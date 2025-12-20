<?php

namespace App\Services\V1\Contract;

use App\Mail\V1\Contract\ContractCreatedMail;
use App\Mail\V1\Contract\ContractStatusChangedMail;
use App\Models\V1\Auth\User;
use App\Models\V1\Contract\Contract;
use App\Models\V1\Ownership\Unit;
use App\Repositories\V1\Contract\Interfaces\ContractRepositoryInterface;
use App\Services\V1\Document\DocumentService;
use App\Services\V1\Mail\OwnershipMailService;
use App\Services\V1\Media\MediaService;
use App\Services\V1\Notification\NotificationService;
use App\Services\V1\Ownership\UserOwnershipMappingService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ContractService
{
    public function __construct(
        private ContractRepositoryInterface $contractRepository,
        private NotificationService $notificationService,
        private OwnershipMailService $mailService,
        private UserOwnershipMappingService $mappingService,
        private ContractSettingService $contractSettingService,
        private MediaService $mediaService,
        private DocumentService $documentService
    ) {}

    /**
     * Get all contracts with pagination.
     */
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        return $this->contractRepository->paginate($perPage, $filters);
    }

    /**
     * Get all contracts.
     */
    public function all(array $filters = []): Collection
    {
        return $this->contractRepository->all($filters);
    }

    /**
     * Find contract by ID.
     */
    public function find(int $id): ?Contract
    {
        return $this->contractRepository->find($id);
    }

    /**
     * Find contract by UUID.
     */
    public function findByUuid(string $uuid): ?Contract
    {
        return $this->contractRepository->findByUuid($uuid);
    }

    /**
     * Find contract by number.
     */
    public function findByNumber(string $number): ?Contract
    {
        return $this->contractRepository->findByNumber($number);
    }

    /**
     * Find active contract for unit.
     */
    public function findActiveContractForUnit(int $unitId): ?Contract
    {
        return $this->contractRepository->findActiveContractForUnit($unitId);
    }

    /**
     * Create a new contract.
     */
    public function create(array $data): Contract
    {
        return DB::transaction(function () use ($data) {
            $ownershipId = $data['ownership_id'] ?? null;
            
            // Apply default settings
            $this->applyDefaultSettings($data, $ownershipId);

            // Single source of truth: advanced units payload (with per-unit rent & notes)
            $unitsPayload = $data['units'] ?? [];

            // Normalize to array of unit IDs (for validation)
            $unitsForValidation = collect($unitsPayload)->pluck('unit_id')->all();

            // Validate max units per contract
            if (!empty($unitsForValidation) && $ownershipId) {
                $maxUnits = $this->contractSettingService->getMaxUnitsPerContract($ownershipId);
                if (count($unitsForValidation) > $maxUnits) {
                    throw ValidationException::withMessages([
                        'units' => __('messages.validation.max', [
                            'attribute' => __('messages.attributes.units'),
                            'max' => $maxUnits
                        ]),
                    ]);
                }
                
                $this->validateUnitsForContract($unitsForValidation, $ownershipId, null);
            }

            // Auto-calculate financial fields using settings
            $this->applyFinancialCalculations($data, $unitsPayload, $ownershipId);

            // Remove units from data before creating contract (it's a relationship, not a column)
            unset($data['units']);

            $contract = $this->contractRepository->create($data);

            // Sync units pivot using advanced payload if provided
            if (!empty($unitsPayload)) {
                $syncData = [];
                foreach ($unitsPayload as $unit) {
                    $unitId = $unit['unit_id'];
                    $syncData[$unitId] = [
                        'rent_amount' => $unit['rent_amount'] ?? null,
                        'notes' => $unit['notes'] ?? null,
                    ];
                }
                $contract->units()->sync($syncData);
            }

            // Sync units status with contract status
            $this->syncUnitsStatusWithContractStatus($contract);

            // Load relationships before sending notifications
            $contract->load(['tenant.user', 'ownership', 'createdBy', 'units', 'documents']);

            // Send system notifications
            $this->notifyContractCreated($contract);

            return $contract;
        });
    }

    /**
     * Apply default settings to contract data.
     */
    protected function applyDefaultSettings(array &$data, ?int $ownershipId): void
    {
        // Status is never accepted from request - always use default from settings
        $defaultStatus = $this->contractSettingService->getDefaultContractStatus($ownershipId);
        
        // If approval is required and default status is active, change to pending
        if ($this->contractSettingService->isContractApprovalRequired($ownershipId) && $defaultStatus === 'active') {
            $data['status'] = 'pending';
        } else {
            $data['status'] = $defaultStatus;
        }

        // Apply default payment frequency if not provided
        if (!isset($data['payment_frequency'])) {
            $data['payment_frequency'] = $this->contractSettingService->getDefaultPaymentFrequency($ownershipId);
        }
    }

    /**
     * Apply financial calculations using settings.
     */
    protected function applyFinancialCalculations(array &$data, array $unitsPayload, ?int $ownershipId): void
    {
        // If units payload provided and auto-calculate enabled, calculate base_rent from units
        if (!empty($unitsPayload) && $this->contractSettingService->isAutoCalculateContractRentEnabled($ownershipId)) {
            $sumPerUnitRent = collect($unitsPayload)->sum(function ($unit) {
                return isset($unit['rent_amount']) ? (float) $unit['rent_amount'] : 0.0;
            });

            if (!isset($data['base_rent'])) {
                $data['base_rent'] = $sumPerUnitRent;
            }
        }

        // Calculate VAT if not provided and auto-calculate is enabled
        if ($this->contractSettingService->isAutoCalculateTotalRentEnabled($ownershipId)) {
            $baseRent = $data['base_rent'] ?? 0;
            $rentFees = $data['rent_fees'] ?? 0;

            // Calculate VAT if not provided
            if (!isset($data['vat_amount']) && $baseRent > 0) {
                $data['vat_amount'] = $this->contractSettingService->calculateVatAmount(
                    $baseRent,
                    $rentFees,
                    $ownershipId
                );
            }

            // Calculate total_rent if not provided
            if (!isset($data['total_rent'])) {
                $previousBalance = $data['previous_balance'] ?? 0;
                $data['total_rent'] = $this->contractSettingService->calculateTotalRent(
                    $baseRent,
                    $rentFees,
                    $previousBalance,
                    $ownershipId
                );
            }
        } else {
            // Manual calculation if auto-calculate is disabled
            if (!isset($data['total_rent'])) {
                $base = $data['base_rent'] ?? 0;
                $fees = $data['rent_fees'] ?? 0;
                $vat = $data['vat_amount'] ?? 0;
                $previousBalance = $data['previous_balance'] ?? 0;
                
                // Add previous balance if setting is enabled
                $total = $base + $fees + $vat;
                if ($this->contractSettingService->isAutoCalculatePreviousBalanceToTotalRentEnabled($ownershipId)) {
                    $total += $previousBalance;
                }
                $data['total_rent'] = $total;
            }
        }
    }

    /**
     * Update contract.
     */
    public function update(Contract $contract, array $data): Contract
    {
        return DB::transaction(function () use ($contract, $data) {
            $ownershipId = $contract->ownership_id;
            $previousStatus = $contract->status;

            // Prevent editing active contracts
            if ($contract->status === 'active') {
                throw ValidationException::withMessages([
                    'status' => __('messages.validation.custom.cannot_edit_active_contract') ?? 'Active contracts cannot be edited.',
                ]);
            }

            // Validate status transitions if status is provided (only draft or pending allowed)
            if (isset($data['status'])) {
                // Only allow draft or pending status in update
                if (!in_array($data['status'], ['draft', 'pending'])) {
                    throw ValidationException::withMessages([
                        'status' => __('messages.validation.custom.can_only_set_draft_or_pending_in_update') ?? 'Status can only be set to draft or pending in update. Use approve endpoint for active status.',
                    ]);
                }
                
                // Validate status transition
                $this->validateStatusTransition($previousStatus, $data['status'], $ownershipId);
            }

            // Validate editing permissions based on settings
            $this->validateEditingPermissions($contract, $data, $ownershipId);

            // Single source of truth: advanced units payload (with per-unit rent & notes)
            $unitsPayload = $data['units'] ?? null;
            $unitIds = is_array($unitsPayload) && !empty($unitsPayload)
                ? collect($unitsPayload)->pluck('unit_id')->all()
                : null;

            // حفظ الوحدات السابقة قبل التحديث لنعرف ما الذي تم فكه لاحقاً
            $previousUnitIds = $contract->units()->pluck('units.id')->all();

            // Validate max units per contract
            if (is_array($unitIds) && !empty($unitIds)) {
                $maxUnits = $this->contractSettingService->getMaxUnitsPerContract($ownershipId);
                if (count($unitIds) > $maxUnits) {
                    throw ValidationException::withMessages([
                        'units' => __('messages.validation.max', [
                            'attribute' => __('messages.attributes.units'),
                            'max' => $maxUnits
                        ]),
                    ]);
                }
                
                $this->validateUnitsForContract($unitIds, $ownershipId, $contract->id);
            }

            // Apply financial calculations using settings
            $this->applyFinancialCalculationsForUpdate($data, $unitsPayload, $contract, $ownershipId);

            // Remove units from data before updating contract (it's a relationship, not a column)
            unset($data['units']);

            $updated = $this->contractRepository->update($contract, $data);

            // Sync units pivot إذا تم تمرير units payload أو unit_ids
            if (is_array($unitsPayload) && !empty($unitsPayload)) {
                $syncData = [];
                foreach ($unitsPayload as $unit) {
                    $unitId = $unit['unit_id'];
                    $syncData[$unitId] = [
                        'rent_amount' => $unit['rent_amount'] ?? null,
                        'notes' => $unit['notes'] ?? null,
                    ];
                }
                $updated->units()->sync($syncData);
            } elseif (is_array($unitIds)) {
                $updated->units()->sync($unitIds);

                // Handle units change: use sync method for status-based logic
                if ($updated->status === 'active') {
                    // If contract is active, mark new units as rented and release old ones
                    $this->updateUnitsStatusForContract($unitIds, $previousUnitIds);
                } else {
                    // If contract is not active, sync units status (will release if cancelled/terminated)
                    $this->syncUnitsStatusWithContractStatus($updated);
                    // Also handle old units that were removed
                    $toRelease = array_diff($previousUnitIds, $unitIds);
                    if (!empty($toRelease)) {
                        $units = Unit::whereIn('id', $toRelease)->get();
                        foreach ($units as $unit) {
                            if (!$unit->activeContract()) {
                                $unit->update(['status' => 'available']);
                            }
                        }
                    }
                }
            } else {
                // Units didn't change, but status might have changed
                if ($previousStatus !== $updated->status) {
                    // Sync units status with new contract status
                    $this->syncUnitsStatusWithContractStatus($updated);
                }
            }

            // Load relationships before sending notifications
            $updated->load(['tenant.user', 'ownership', 'createdBy', 'units', 'documents']);

            // Send notifications if status changed
            if ($previousStatus !== $updated->status) {
                $this->notifyContractStatusChanged($updated, $previousStatus);
            }

            return $updated;
        });
    }

    /**
     * Validate units for a contract:
     * - Belong to the same ownership
     * - No conflicting active contract on the same unit
     *
     * @param array<int,int> $unitIds
     * @param int $ownershipId
     * @param int|null $currentContractId  العقد الحالي في حالة التحديث (للسماح بالوحدات المرتبطة به)
     */
    protected function validateUnitsForContract(array $unitIds, int $ownershipId, ?int $currentContractId = null): void
    {
        $units = Unit::whereIn('id', $unitIds)->get();

        if ($units->count() !== count($unitIds)) {
            throw ValidationException::withMessages([
                'unit_ids' => __('messages.validation.invalid', ['attribute' => __('messages.attributes.unit_id')]),
            ]);
        }

        // Check ownership
        $invalidOwnership = $units->first(function (Unit $unit) use ($ownershipId) {
            return $unit->ownership_id !== $ownershipId;
        });

        if ($invalidOwnership) {
            throw ValidationException::withMessages([
                'unit_ids' => __('messages.validation.exists', ['attribute' => __('messages.attributes.unit_id')]),
            ]);
        }

        // Check for conflicting active contracts (other than the current one)
        $conflicting = $units->filter(function (Unit $unit) use ($currentContractId) {
            $active = $unit->activeContract();

            // لا يوجد عقد نشط → لا تعارض
            if (!$active) {
                return false;
            }

            // لو العقد النشط هو نفس العقد الحالي (في حالة update) → مسموح
            if ($currentContractId && $active->id === $currentContractId) {
                return false;
            }

            // غير ذلك → تعارض
            return true;
        });

        if ($conflicting->isNotEmpty()) {
            throw ValidationException::withMessages([
                'unit_ids' => __('messages.validation.custom.unit_not_available') ?? 'One or more units are not available for contracting.',
            ]);
        }
    }

    /**
     * Update units status for a contract:
     * - Mark new units as rented
     * - Release old units (set to available) if they no longer have an active contract
     *
     * @param array<int,int> $newUnitIds
     * @param array<int,int> $oldUnitIds
     */
    protected function updateUnitsStatusForContract(array $newUnitIds, array $oldUnitIds = []): void
    {
        // Mark new units as rented
        if (!empty($newUnitIds)) {
            Unit::whereIn('id', $newUnitIds)->update(['status' => 'rented']);
        }

        // Release old units that are no longer attached to this contract set
        $toRelease = array_diff($oldUnitIds, $newUnitIds);
        if (!empty($toRelease)) {
            $units = Unit::whereIn('id', $toRelease)->get();
            foreach ($units as $unit) {
                // If unit has no active contract after detaching, set status back to available
                if (!$unit->activeContract()) {
                    $unit->update(['status' => 'available']);
                }
            }
        }
    }

    /**
     * Sync units status with contract status.
     * This is a reusable method that can be called from multiple places.
     * 
     * Rules:
     * - If contract is 'active': All units must be 'rented'
     * - If contract is 'cancelled' or 'terminated': Units should be 'available' (if no other active contract)
     * - For other statuses (draft, pending, expired): No automatic status change
     * 
     * @param Contract $contract The contract to sync units for
     * @return void
     */
    public function syncUnitsStatusWithContractStatus(Contract $contract): void
    {
        // Load units if not already loaded
        if (!$contract->relationLoaded('units')) {
            $contract->load('units');
        }

        $unitIds = $contract->units->pluck('id')->all();

        if (empty($unitIds)) {
            return;
        }

        // If contract is active, ensure all units are rented
        if ($contract->status === 'active') {
            Unit::whereIn('id', $unitIds)->update(['status' => 'rented']);
            return;
        }

        // If contract is cancelled or terminated, release units (set to available)
        // Only if they don't have another active contract
        if (in_array($contract->status, ['cancelled', 'terminated'])) {
            $units = Unit::whereIn('id', $unitIds)->get();
            foreach ($units as $unit) {
                // Refresh to get latest relationships (in case units were just changed)
                $unit->refresh();
                
                // If unit has no active contract, set status to available
                if (!$unit->activeContract()) {
                    $unit->update(['status' => 'available']);
                }
                // If unit has another active contract, keep it as rented (don't change)
            }
        }
        
        // For draft, pending, expired statuses: Don't automatically change unit status
        // Units status should be managed manually or through other operations
    }

    /**
     * Delete contract.
     */
    public function delete(Contract $contract): bool
    {
        return DB::transaction(function () use ($contract) {
            // Load relationships
            $contract->load(['mediaFiles', 'documents']);

            // Delete all media files
            foreach ($contract->mediaFiles as $mediaFile) {
                $this->mediaService->delete($mediaFile);
            }

            // Delete all documents
            foreach ($contract->documents as $document) {
                $this->documentService->delete($document);
            }

            return $this->contractRepository->delete($contract);
        });
    }

    /**
     * Approve contract.
     */
    public function approve(Contract $contract, int $approvedBy): Contract
    {
        return DB::transaction(function () use ($contract, $approvedBy) {
            // Validate that contract can be approved
            if ($contract->status === 'active') {
                throw ValidationException::withMessages([
                    'status' => __('messages.validation.custom.contract_already_active') ?? 'Contract is already active.',
                ]);
            }

            if ($contract->status === 'cancelled') {
                throw ValidationException::withMessages([
                    'status' => __('messages.validation.custom.cannot_approve_cancelled_contract') ?? 'Cannot approve a cancelled contract.',
                ]);
            }

            $previousStatus = $contract->status;
            $contract = $this->contractRepository->approve($contract, $approvedBy);

            // Sync units status with contract status (will mark as rented since status is active)
            $this->syncUnitsStatusWithContractStatus($contract);

            // Load relationships before sending notifications
            $contract->load(['tenant.user', 'ownership', 'createdBy', 'approvedBy', 'units', 'documents']);

            // Send notifications if status changed
            if ($previousStatus !== $contract->status) {
                $this->notifyContractStatusChanged($contract, $previousStatus);
            }

            return $contract;
        });
    }

    /**
     * Cancel contract.
     * Only works on pending or draft contracts.
     */
    public function cancel(Contract $contract, ?string $reason = null): Contract
    {
        return DB::transaction(function () use ($contract, $reason) {
            // Only pending or draft contracts can be cancelled
            if (!in_array($contract->status, ['pending', 'draft'])) {
                throw ValidationException::withMessages([
                    'status' => __('messages.validation.custom.can_only_cancel_pending_or_draft_contract') ?? 'Only pending or draft contracts can be cancelled.',
                ]);
            }

            $previousStatus = $contract->status;
            
            $contract->update([
                'status' => 'cancelled',
            ]);

            // Sync units status with contract status (will release units since status is cancelled)
            $this->syncUnitsStatusWithContractStatus($contract);

            // Load relationships before sending notifications
            $contract->load(['tenant.user', 'ownership', 'createdBy', 'approvedBy', 'units', 'documents']);

            // Send notifications
            $this->notifyContractStatusChanged($contract, $previousStatus);

            return $contract->fresh(['units', 'tenant.user', 'ownership', 'createdBy', 'approvedBy', 'parent', 'children', 'terms', 'documents']);
        });
    }

    /**
     * Terminate contract.
     * Only works on active contracts.
     */
    public function terminate(Contract $contract, ?string $reason = null): Contract
    {
        return DB::transaction(function () use ($contract, $reason) {
            // Only active contracts can be terminated
            if ($contract->status !== 'active') {
                throw ValidationException::withMessages([
                    'status' => __('messages.validation.custom.can_only_terminate_active_contract') ?? 'Only active contracts can be terminated.',
                ]);
            }

            $previousStatus = $contract->status;
            
            $contract->update([
                'status' => 'terminated',
            ]);

            // Sync units status with contract status (will release units since status is terminated)
            $this->syncUnitsStatusWithContractStatus($contract);

            // Load relationships before sending notifications
            $contract->load(['tenant.user', 'ownership', 'createdBy', 'approvedBy', 'units', 'documents']);

            // Send notifications
            $this->notifyContractStatusChanged($contract, $previousStatus);

            return $contract->fresh(['units', 'tenant.user', 'ownership', 'createdBy', 'approvedBy', 'parent', 'children', 'terms', 'documents']);
        });
    }

    /**
     * Validate status transition.
     */
    protected function validateStatusTransition(string $currentStatus, string $newStatus, ?int $ownershipId): void
    {
        // If status is not changing, allow it
        if ($currentStatus === $newStatus) {
            return;
        }

        // Draft can transition to pending or active (if approval not required)
        if ($currentStatus === 'draft') {
            if ($newStatus === 'cancelled') {
                throw ValidationException::withMessages([
                    'status' => __('messages.validation.custom.cannot_cancel_draft_contract') ?? 'Draft contracts cannot be cancelled. Delete the contract instead.',
                ]);
            }
            // Allow transition to pending or active (if approval not required)
            if ($newStatus === 'active' && $this->contractSettingService->isContractApprovalRequired($ownershipId)) {
                throw ValidationException::withMessages([
                    'status' => __('messages.validation.custom.cannot_set_active_without_approval') ?? 'Cannot set contract to active. Approval is required. Use the approve endpoint instead.',
                ]);
            }
            return;
        }

        // Pending can transition to active (if approval not required), or cancelled
        if ($currentStatus === 'pending') {
            if ($newStatus === 'active' && $this->contractSettingService->isContractApprovalRequired($ownershipId)) {
                throw ValidationException::withMessages([
                    'status' => __('messages.validation.custom.cannot_set_active_without_approval') ?? 'Cannot set contract to active. Approval is required. Use the approve endpoint instead.',
                ]);
            }
            if ($newStatus === 'draft') {
                throw ValidationException::withMessages([
                    'status' => __('messages.validation.custom.cannot_revert_to_draft') ?? 'Cannot revert contract to draft status.',
                ]);
            }
            return;
        }

        // Cancelled cannot transition to any other status
        if ($currentStatus === 'cancelled') {
            throw ValidationException::withMessages([
                'status' => __('messages.validation.custom.cannot_modify_cancelled_contract') ?? 'Cancelled contracts cannot be modified.',
            ]);
        }

        // Active contracts cannot be edited (already checked above, but double-check)
        if ($currentStatus === 'active') {
            throw ValidationException::withMessages([
                'status' => __('messages.validation.custom.cannot_edit_active_contract') ?? 'Active contracts cannot be edited.',
            ]);
        }
    }

    /**
     * Get users who should receive notifications about contracts for an ownership.
     */
    private function getUsersToNotify(int $ownershipId): Collection
    {
        // Get all users mapped to this ownership
        $mappings = $this->mappingService->getByOwnership($ownershipId);
        $userIds = $mappings->pluck('user_id')->unique();
        
        // Filter users who have the notification permission
        return User::whereIn('id', $userIds)
            ->get()
            ->filter(function ($user) use ($ownershipId) {
                // Check permission and ownership access directly
                // Super Admin can receive notifications if they have permission
                if ($user->isSuperAdmin()) {
                    return $user->can('contracts.notifications');
                }

                // Check permission and ownership access
                return $user->can('contracts.notifications') 
                    && $user->hasOwnership($ownershipId);
            });
    }

    /**
     * Notify users when contract is created.
     */
    private function notifyContractCreated(Contract $contract): void
    {
        $usersToNotify = $this->getUsersToNotify($contract->ownership_id);
        
        // Also notify the tenant user if exists
        $tenantUser = $contract->tenant?->user;
        $shouldNotifyTenant = false;
        if ($tenantUser && !$usersToNotify->pluck('id')->contains($tenantUser->id)) {
            $usersToNotify->push($tenantUser);
            $shouldNotifyTenant = true;
        }
        
        foreach ($usersToNotify as $user) {
            try {
                $this->notificationService->create([
                    'user_id' => $user->id,
                    'type' => 'info',
                    'title' => __('notifications.contract.created.title'),
                    'message' => __('notifications.contract.created.message', [
                        'contract_number' => $contract->number,
                        'tenant_name' => $contract->tenant?->user?->name ?? __('notifications.tenant_invitation.no_name'),
                        'tenant_email' => $contract->tenant?->user?->email ?? __('notifications.tenant_invitation.no_email'),
                        'ownership' => $contract->ownership->name,
                        'status' => __("contracts.status.{$contract->status}"),
                        'created_by' => $contract->createdBy?->name ?? __('notifications.tenant_invitation.no_name'),
                    ]),
                    'category' => 'contract',
                    'action_url' => '/contracts/' . $contract->uuid,
                    'action_text' => __('notifications.contract.view_contract'),
                    'data' => [
                        'contract_uuid' => $contract->uuid,
                        'contract_id' => $contract->id,
                        'ownership_id' => $contract->ownership_id,
                        'tenant_id' => $contract->tenant_id,
                        'status' => $contract->status,
                    ],
                ]);
            } catch (\Exception $e) {
                Log::error("Failed to send notification for contract creation: " . $e->getMessage());
            }
        }

        // Send email to tenant user if contract is created
        if ($shouldNotifyTenant && $tenantUser && $tenantUser->email) {
            try {
                $this->mailService->sendForOwnership(
                    $contract->ownership_id,
                    $tenantUser->email,
                    new ContractCreatedMail($contract)
                );
            } catch (\Exception $e) {
                Log::error("Failed to send email for contract creation: " . $e->getMessage());
            }
        }
    }

    /**
     * Notify users when contract status is changed.
     */
    private function notifyContractStatusChanged(Contract $contract, string $previousStatus): void
    {
        $usersToNotify = $this->getUsersToNotify($contract->ownership_id);
        
        // Also notify the tenant user if exists
        $tenantUser = $contract->tenant?->user;
        $shouldNotifyTenant = false;
        if ($tenantUser && !$usersToNotify->pluck('id')->contains($tenantUser->id)) {
            $usersToNotify->push($tenantUser);
            $shouldNotifyTenant = true;
        }
        
        foreach ($usersToNotify as $user) {
            try {
                $this->notificationService->create([
                    'user_id' => $user->id,
                    'type' => $this->getNotificationTypeForStatus($contract->status),
                    'title' => __('notifications.contract.status_changed.title'),
                    'message' => __('notifications.contract.status_changed.message', [
                        'contract_number' => $contract->number,
                        'previous_status' => __("contracts.status.{$previousStatus}"),
                        'new_status' => __("contracts.status.{$contract->status}"),
                        'tenant_name' => $contract->tenant?->user?->name ?? __('notifications.tenant_invitation.no_name'),
                        'tenant_email' => $contract->tenant?->user?->email ?? __('notifications.tenant_invitation.no_email'),
                        'ownership' => $contract->ownership->name,
                    ]),
                    'category' => 'contract',
                    'action_url' => '/contracts/' . $contract->uuid,
                    'action_text' => __('notifications.contract.view_contract'),
                    'data' => [
                        'contract_uuid' => $contract->uuid,
                        'contract_id' => $contract->id,
                        'ownership_id' => $contract->ownership_id,
                        'tenant_id' => $contract->tenant_id,
                        'previous_status' => $previousStatus,
                        'new_status' => $contract->status,
                    ],
                ]);
            } catch (\Exception $e) {
                Log::error("Failed to send notification for contract status change: " . $e->getMessage());
            }
        }

        // Send email to tenant user if status changed
        if ($shouldNotifyTenant && $tenantUser && $tenantUser->email) {
            try {
                $this->mailService->sendForOwnership(
                    $contract->ownership_id,
                    $tenantUser->email,
                    new ContractStatusChangedMail($contract, $previousStatus, $contract->status)
                );
            } catch (\Exception $e) {
                Log::error("Failed to send email for contract status change: " . $e->getMessage());
            }
        }
    }

    /**
     * Validate editing permissions based on settings.
     */
    protected function validateEditingPermissions(Contract $contract, array $data, int $ownershipId): void
    {
        // Check if editing active contracts is allowed
        if ($contract->status === 'active' && !$this->contractSettingService->isEditingActiveContractsAllowed($ownershipId)) {
            throw ValidationException::withMessages([
                'status' => __('messages.validation.custom.cannot_edit_active_contract'),
            ]);
        }

        // Check if editing contract dates is allowed
        if (!$this->contractSettingService->isEditingContractDatesAllowed($ownershipId)) {
            if (isset($data['start']) || isset($data['end'])) {
                throw ValidationException::withMessages([
                    'start' => __('messages.validation.custom.cannot_edit_contract_dates'),
                    'end' => __('messages.validation.custom.cannot_edit_contract_dates'),
                ]);
            }
        }

        // Check if editing contract rent is allowed
        if (!$this->contractSettingService->isEditingContractRentAllowed($ownershipId)) {
            $rentFields = ['base_rent', 'rent_fees', 'vat_amount', 'total_rent'];
            foreach ($rentFields as $field) {
                if (isset($data[$field])) {
                    throw ValidationException::withMessages([
                        $field => __('messages.validation.custom.cannot_edit_contract_rent'),
                    ]);
                }
            }
        }
    }

    /**
     * Apply financial calculations for update using settings.
     */
    protected function applyFinancialCalculationsForUpdate(array &$data, ?array $unitsPayload, Contract $contract, int $ownershipId): void
    {
        // If units payload provided and auto-calculate enabled, calculate base_rent from units
        if (is_array($unitsPayload) && !empty($unitsPayload) && $this->contractSettingService->isAutoCalculateContractRentEnabled($ownershipId)) {
            $sumPerUnitRent = collect($unitsPayload)->sum(function ($unit) {
                return isset($unit['rent_amount']) ? (float) $unit['rent_amount'] : 0.0;
            });

            if (!isset($data['base_rent'])) {
                $data['base_rent'] = $sumPerUnitRent;
            }
        }

        // Calculate VAT and total_rent if auto-calculate is enabled
        if ($this->contractSettingService->isAutoCalculateTotalRentEnabled($ownershipId)) {
            $baseRent = $data['base_rent'] ?? $contract->base_rent ?? 0;
            $rentFees = $data['rent_fees'] ?? $contract->rent_fees ?? 0;

            // Calculate VAT if not provided and base rent changed
            if (!isset($data['vat_amount']) && ($baseRent > 0 || $rentFees > 0)) {
                $data['vat_amount'] = $this->contractSettingService->calculateVatAmount(
                    $baseRent,
                    $rentFees,
                    $ownershipId
                );
            }

            // Calculate total_rent if not provided
            if (!isset($data['total_rent'])) {
                $previousBalance = $data['previous_balance'] ?? $contract->previous_balance ?? 0;
                $data['total_rent'] = $this->contractSettingService->calculateTotalRent(
                    $baseRent,
                    $rentFees,
                    $previousBalance,
                    $ownershipId
                );
            }
        } else {
            // Manual calculation if auto-calculate is disabled
            if (!isset($data['total_rent'])) {
                $base = $data['base_rent'] ?? $contract->base_rent ?? 0;
                $fees = $data['rent_fees'] ?? $contract->rent_fees ?? 0;
                $vat = $data['vat_amount'] ?? $contract->vat_amount ?? 0;
                $previousBalance = $data['previous_balance'] ?? $contract->previous_balance ?? 0;
                
                // Add previous balance if setting is enabled
                $total = $base + $fees + $vat;
                if ($this->contractSettingService->isAutoCalculatePreviousBalanceToTotalRentEnabled($ownershipId)) {
                    $total += $previousBalance;
                }
                $data['total_rent'] = $total;
            }
        }
    }

    /**
     * Get notification type based on contract status.
     */
    private function getNotificationTypeForStatus(string $status): string
    {
        return match ($status) {
            'active' => 'success',
            'cancelled', 'terminated' => 'warning',
            'expired' => 'info',
            default => 'info',
        };
    }
}


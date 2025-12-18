<?php

namespace App\Services\V1\Contract;

use App\Mail\V1\Contract\ContractCreatedMail;
use App\Mail\V1\Contract\ContractStatusChangedMail;
use App\Models\V1\Auth\User;
use App\Models\V1\Contract\Contract;
use App\Models\V1\Ownership\Unit;
use App\Repositories\V1\Contract\Interfaces\ContractRepositoryInterface;
use App\Services\V1\Mail\OwnershipMailService;
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
        private UserOwnershipMappingService $mappingService
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
            // Extract unit_ids (multiple units) if provided
            $unitIds = $data['unit_ids'] ?? null;
            $ownershipId = $data['ownership_id'] ?? null;

            // Normalize to array of unit IDs (for validation)
            $unitsForValidation = [];
            if (is_array($unitIds) && !empty($unitIds)) {
                $unitsForValidation = $unitIds;
            } elseif (!empty($data['unit_id'])) {
                $unitsForValidation = [$data['unit_id']];
            }

            if (!empty($unitsForValidation) && $ownershipId) {
                $this->validateUnitsForContract($unitsForValidation, $ownershipId, null);
            }

            // For legacy single-unit contracts, keep unit_id in data
            $contract = $this->contractRepository->create($data);

            // Sync units pivot if multiple units are provided
            if (is_array($unitIds) && !empty($unitIds)) {
                $contract->units()->sync($unitIds);
            } elseif (!empty($data['unit_id'])) {
                // Fallback: if only unit_id is provided, ensure pivot has at least that unit
                $contract->units()->sync([$data['unit_id']]);
            }

            // Update units status only if contract is active
            if ($contract->status === 'active') {
                if (!empty($unitIds)) {
                    $this->updateUnitsStatusForContract($unitIds, []);
                } elseif (!empty($data['unit_id'])) {
                    $this->updateUnitsStatusForContract([$data['unit_id']], []);
                }
            }

            // Load relationships before sending notifications
            $contract->load(['tenant.user', 'ownership', 'createdBy', 'units']);

            // Send system notifications
            $this->notifyContractCreated($contract);

            return $contract;
        });
    }

    /**
     * Update contract.
     */
    public function update(Contract $contract, array $data): Contract
    {
        return DB::transaction(function () use ($contract, $data) {
            // Normalize unit IDs for update (support both unit_ids and legacy unit_id)
            if (array_key_exists('unit_ids', $data) && is_array($data['unit_ids'])) {
                $unitIds = $data['unit_ids'];
            } elseif (array_key_exists('unit_id', $data) && $data['unit_id']) {
                $unitIds = [$data['unit_id']];
            } else {
                $unitIds = null;
            }

            // حفظ الوحدات السابقة قبل التحديث لنعرف ما الذي تم فكه لاحقاً
            $previousUnitIds = $contract->units()->pluck('units.id')->all();
            $previousStatus = $contract->status;

            if (is_array($unitIds) && !empty($unitIds)) {
                $this->validateUnitsForContract($unitIds, $contract->ownership_id, $contract->id);
            }

            $updated = $this->contractRepository->update($contract, $data);

            // Sync units pivot إذا تم تمرير unit_ids
            if (is_array($unitIds)) {
                $updated->units()->sync($unitIds);

                // لو العقد نشط: الوحدات الجديدة تصبح rented، والقديمة التي خرجت تتحرر إن لم يعد لها عقد نشط
                if ($updated->status === 'active') {
                    $this->updateUnitsStatusForContract($unitIds, $previousUnitIds);
                } else {
                    // لو العقد غير نشط (cancelled, terminated, expired, draft, pending):
                    // كل الوحدات (القديمة والجديدة) تُعاد إلى available إذا لم يعد لها عقد نشط آخر
                    $allRelated = array_unique(array_merge($previousUnitIds, $unitIds));
                    $this->updateUnitsStatusForContract([], $allRelated);
                }
            } else {
                // لم تتغير الوحدات، لكن قد يتغير status
                if ($previousStatus !== $updated->status) {
                    if ($updated->status === 'active') {
                        // العقد أصبح نشطاً → كل الوحدات الحالية تصبح rented
                        $currentUnitIds = $updated->units()->pluck('units.id')->all();
                        $this->updateUnitsStatusForContract($currentUnitIds, []);
                    } else {
                        // العقد أصبح غير نشط → أرجع الوحدات إلى available إن لم يعد لها عقد نشط آخر
                        $currentUnitIds = $updated->units()->pluck('units.id')->all();
                        $this->updateUnitsStatusForContract([], $currentUnitIds);
                    }
                }
            }

            // Load relationships before sending notifications
            $updated->load(['tenant.user', 'ownership', 'createdBy', 'units']);

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
     * Delete contract.
     */
    public function delete(Contract $contract): bool
    {
        return DB::transaction(function () use ($contract) {
            return $this->contractRepository->delete($contract);
        });
    }

    /**
     * Approve contract.
     */
    public function approve(Contract $contract, int $approvedBy): Contract
    {
        return DB::transaction(function () use ($contract, $approvedBy) {
            return $this->contractRepository->approve($contract, $approvedBy);
        });
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


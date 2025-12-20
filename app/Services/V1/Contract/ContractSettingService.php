<?php

namespace App\Services\V1\Contract;

use App\Services\V1\Setting\SystemSettingService;

/**
 * Contract Settings Service
 * 
 * Provides typed access to contract-related system settings
 * with proper fallback values and type casting.
 */
class ContractSettingService
{
    public function __construct(
        private SystemSettingService $settingService
    ) {}

    /**
     * Get default unit rent frequency.
     * 
     * @param int|null $ownershipId
     * @return string 'yearly'|'monthly'|'quarterly'
     */
    public function getDefaultUnitRentFrequency(?int $ownershipId = null): string
    {
        return $this->settingService->getValue(
            'default_unit_rent_frequency',
            $ownershipId,
            'yearly'
        ) ?: 'yearly';
    }

    /**
     * Get default contract status.
     * 
     * @param int|null $ownershipId
     * @return string 'draft'|'pending'|'active'|'expired'|'terminated'|'cancelled'
     */
    public function getDefaultContractStatus(?int $ownershipId = null): string
    {
        return $this->settingService->getValue(
            'default_contract_status',
            $ownershipId,
            'draft'
        ) ?: 'draft';
    }

    /**
     * Get default payment frequency.
     * 
     * @param int|null $ownershipId
     * @return string 'monthly'|'quarterly'|'yearly'|'weekly'
     */
    public function getDefaultPaymentFrequency(?int $ownershipId = null): string
    {
        return $this->settingService->getValue(
            'default_payment_frequency',
            $ownershipId,
            'monthly'
        ) ?: 'monthly';
    }

    /**
     * Get default contract duration in months.
     * 
     * @param int|null $ownershipId
     * @return int
     */
    public function getDefaultContractDurationMonths(?int $ownershipId = null): int
    {
        return (int) $this->settingService->getValue(
            'default_contract_duration_months',
            $ownershipId,
            12
        ) ?: 12;
    }

    /**
     * Get contract VAT percentage.
     * 
     * @param int|null $ownershipId
     * @return float
     */
    public function getContractVatPercentage(?int $ownershipId = null): float
    {
        return (float) $this->settingService->getValue(
            'contract_vat_percentage',
            $ownershipId,
            15.00
        ) ?: 15.00;
    }

    /**
     * Check if contract approval is required.
     * 
     * @param int|null $ownershipId
     * @return bool
     */
    public function isContractApprovalRequired(?int $ownershipId = null): bool
    {
        return (bool) $this->settingService->getValue(
            'contract_approval_required',
            $ownershipId,
            true
        );
    }

    /**
     * Check if Ejar code is required.
     * 
     * @param int|null $ownershipId
     * @return bool
     */
    public function isEjarCodeRequired(?int $ownershipId = null): bool
    {
        return (bool) $this->settingService->getValue(
            'require_ejar_code',
            $ownershipId,
            false
        );
    }

    /**
     * Check if backdated contracts are allowed.
     * 
     * @param int|null $ownershipId
     * @return bool
     */
    public function areBackdatedContractsAllowed(?int $ownershipId = null): bool
    {
        return (bool) $this->settingService->getValue(
            'allow_backdated_contracts',
            $ownershipId,
            true
        );
    }

    /**
     * Get minimum contract duration in months.
     * 
     * @param int|null $ownershipId
     * @return int
     */
    public function getMinContractDurationMonths(?int $ownershipId = null): int
    {
        return (int) $this->settingService->getValue(
            'min_contract_duration_months',
            $ownershipId,
            1
        ) ?: 1;
    }

    /**
     * Get maximum contract duration in months.
     * 
     * @param int|null $ownershipId
     * @return int
     */
    public function getMaxContractDurationMonths(?int $ownershipId = null): int
    {
        return (int) $this->settingService->getValue(
            'max_contract_duration_months',
            $ownershipId,
            120
        ) ?: 120;
    }

    /**
     * Check if auto-expire contracts is enabled.
     * 
     * @param int|null $ownershipId
     * @return bool
     */
    public function isAutoExpireContractsEnabled(?int $ownershipId = null): bool
    {
        return (bool) $this->settingService->getValue(
            'auto_expire_contracts',
            $ownershipId,
            true
        );
    }

    /**
     * Get contract renewal grace period in days.
     * 
     * @param int|null $ownershipId
     * @return int
     */
    public function getContractRenewalGracePeriodDays(?int $ownershipId = null): int
    {
        return (int) $this->settingService->getValue(
            'contract_renewal_grace_period_days',
            $ownershipId,
            30
        ) ?: 30;
    }

    /**
     * Check if auto-release units on expiry is enabled.
     * 
     * @param int|null $ownershipId
     * @return bool
     */
    public function isAutoReleaseUnitsOnExpiryEnabled(?int $ownershipId = null): bool
    {
        return (bool) $this->settingService->getValue(
            'auto_release_units_on_expiry',
            $ownershipId,
            true
        );
    }

    /**
     * Check if editing active contracts is allowed.
     * 
     * @param int|null $ownershipId
     * @return bool
     */
    public function isEditingActiveContractsAllowed(?int $ownershipId = null): bool
    {
        return (bool) $this->settingService->getValue(
            'allow_edit_active_contracts',
            $ownershipId,
            true
        );
    }

    /**
     * Check if editing contract dates is allowed.
     * 
     * @param int|null $ownershipId
     * @return bool
     */
    public function isEditingContractDatesAllowed(?int $ownershipId = null): bool
    {
        return (bool) $this->settingService->getValue(
            'allow_edit_contract_dates',
            $ownershipId,
            true
        );
    }

    /**
     * Check if editing contract rent is allowed.
     * 
     * @param int|null $ownershipId
     * @return bool
     */
    public function isEditingContractRentAllowed(?int $ownershipId = null): bool
    {
        return (bool) $this->settingService->getValue(
            'allow_edit_contract_rent',
            $ownershipId,
            true
        );
    }

    /**
     * Check if auto-calculate contract rent is enabled.
     * 
     * @param int|null $ownershipId
     * @return bool
     */
    public function isAutoCalculateContractRentEnabled(?int $ownershipId = null): bool
    {
        return (bool) $this->settingService->getValue(
            'auto_calculate_contract_rent',
            $ownershipId,
            true
        );
    }

    /**
     * Check if auto-calculate total rent is enabled.
     * 
     * @param int|null $ownershipId
     * @return bool
     */
    public function isAutoCalculateTotalRentEnabled(?int $ownershipId = null): bool
    {
        return (bool) $this->settingService->getValue(
            'auto_calculate_total_rent',
            $ownershipId,
            true
        );
    }

    /**
     * Check if auto-calculate previous balance to total rent is enabled.
     * 
     * @param int|null $ownershipId
     * @return bool
     */
    public function isAutoCalculatePreviousBalanceToTotalRentEnabled(?int $ownershipId = null): bool
    {
        return (bool) $this->settingService->getValue(
            'auto_calculate_previous_balance_to_total_rent',
            $ownershipId,
            false
        );
    }

    /**
     * Get maximum units per contract.
     * 
     * @param int|null $ownershipId
     * @return int
     */
    public function getMaxUnitsPerContract(?int $ownershipId = null): int
    {
        return (int) $this->settingService->getValue(
            'max_units_per_contract',
            $ownershipId,
            10
        ) ?: 10;
    }

    /**
     * Check if fees should be applied to VAT calculation.
     * 
     * @param int|null $ownershipId
     * @return bool
     */
    public function isApplyFeesToVatEnabled(?int $ownershipId = null): bool
    {
        return (bool) $this->settingService->getValue(
            'apply_fees_to_vat',
            $ownershipId,
            true
        );
    }

    /**
     * Calculate VAT amount from base amount and optionally fees.
     * 
     * @param float $baseRent
     * @param float $rentFees
     * @param int|null $ownershipId
     * @return float
     */
    public function calculateVatAmount(float $baseRent, float $rentFees = 0, ?int $ownershipId = null): float
    {
        $vatPercentage = $this->getContractVatPercentage($ownershipId);
        
        // Determine VAT base: base_rent only or base_rent + fees
        $vatBase = $this->isApplyFeesToVatEnabled($ownershipId) 
            ? $baseRent + $rentFees 
            : $baseRent;
        
        return round($vatBase * ($vatPercentage / 100), 2);
    }

    /**
     * Calculate total rent from base rent, fees, VAT, and optionally previous balance.
     * 
     * @param float $baseRent
     * @param float $rentFees
     * @param float $previousBalance
     * @param int|null $ownershipId
     * @return float
     */
    public function calculateTotalRent(float $baseRent, float $rentFees = 0, float $previousBalance = 0, ?int $ownershipId = null): float
    {
        // Calculate VAT based on setting (with or without fees)
        $vatAmount = $this->calculateVatAmount($baseRent, $rentFees, $ownershipId);
        $total = $baseRent + $rentFees + $vatAmount;
        
        // Add previous balance if setting is enabled
        if ($this->isAutoCalculatePreviousBalanceToTotalRentEnabled($ownershipId)) {
            $total += $previousBalance;
        }
        
        return round($total, 2);
    }
}


<?php

namespace App\Services\V1\Report;

use App\Models\V1\Contract\Contract;
use App\Models\V1\Invoice\Invoice;
use App\Models\V1\Payment\Payment;
use App\Models\V1\Tenant\Tenant;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReportService
{
    /**
     * Cache duration in minutes
     */
    private const CACHE_DURATION = 5;

    /**
     * Get dashboard overview for ownership
     */
    public function getDashboardOverview(int $ownershipId, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $cacheKey = "dashboard_overview_{$ownershipId}_" . ($startDate?->format('Y-m-d') ?? 'all') . '_' . ($endDate?->format('Y-m-d') ?? 'all');

        return Cache::remember($cacheKey, now()->addMinutes(self::CACHE_DURATION), function () use ($ownershipId, $startDate, $endDate) {
            $now = now();
            $startDate = $startDate ?? $now->copy()->startOfMonth();
            $endDate = $endDate ?? $now->copy()->endOfMonth();

            return [
                'tenants' => $this->getTenantsSummary($ownershipId),
                'contracts' => $this->getContractsSummary($ownershipId),
                'invoices' => $this->getInvoicesSummary($ownershipId, $startDate, $endDate),
                'payments' => $this->getPaymentsSummary($ownershipId, $startDate, $endDate),
                'revenue' => $this->getRevenueSummary($ownershipId, $startDate, $endDate),
                'performance' => $this->getPerformanceMetrics($ownershipId, $startDate, $endDate),
            ];
        });
    }

    /**
     * Get tenants summary
     */
    public function getTenantsSummary(int $ownershipId): array
    {
        $total = Tenant::where('ownership_id', $ownershipId)->count();
        
        $active = Tenant::where('ownership_id', $ownershipId)
            ->whereHas('contracts', function ($query) {
                $query->where('status', 'active');
            })
            ->count();

        $newThisMonth = Tenant::where('ownership_id', $ownershipId)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        $ratings = Tenant::where('ownership_id', $ownershipId)
            ->select('rating', DB::raw('count(*) as count'))
            ->groupBy('rating')
            ->pluck('count', 'rating')
            ->toArray();

        return [
            'total' => $total,
            'active' => $active,
            'new_this_month' => $newThisMonth,
            'ratings' => [
                'excellent' => $ratings['excellent'] ?? 0,
                'good' => $ratings['good'] ?? 0,
                'fair' => $ratings['fair'] ?? 0,
                'poor' => $ratings['poor'] ?? 0,
            ],
        ];
    }

    /**
     * Get contracts summary
     */
    public function getContractsSummary(int $ownershipId): array
    {
        $total = Contract::where('ownership_id', $ownershipId)->count();
        
        $statusCounts = Contract::where('ownership_id', $ownershipId)
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $active = $statusCounts['active'] ?? 0;
        $expiringSoon = Contract::where('ownership_id', $ownershipId)
            ->where('status', 'active')
            ->whereBetween('end', [now(), now()->addDays(30)])
            ->count();

        $totalRent = Contract::where('ownership_id', $ownershipId)
            ->where('status', 'active')
            ->sum('total_rent');

        $totalDeposits = Contract::where('ownership_id', $ownershipId)
            ->sum('deposit');

        return [
            'total' => $total,
            'active' => $active,
            'expiring_soon' => $expiringSoon,
            'status' => [
                'draft' => $statusCounts['draft'] ?? 0,
                'pending' => $statusCounts['pending'] ?? 0,
                'active' => $active,
                'expired' => $statusCounts['expired'] ?? 0,
                'terminated' => $statusCounts['terminated'] ?? 0,
                'cancelled' => $statusCounts['cancelled'] ?? 0,
            ],
            'total_rent' => (float) $totalRent,
            'total_deposits' => (float) $totalDeposits,
        ];
    }

    /**
     * Get invoices summary
     */
    public function getInvoicesSummary(int $ownershipId, Carbon $startDate, Carbon $endDate): array
    {
        $query = Invoice::where('ownership_id', $ownershipId)
            ->whereBetween('period_start', [$startDate, $endDate]);

        $total = $query->count();
        
        $statusCounts = (clone $query)
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $totalAmount = (clone $query)->sum('amount');
        $totalTax = (clone $query)->sum('tax');
        $totalTotal = (clone $query)->sum('total');

        $overdue = (clone $query)
            ->where('status', 'overdue')
            ->count();

        $overdueAmount = (clone $query)
            ->where('status', 'overdue')
            ->sum('total');

        return [
            'total' => $total,
            'status' => [
                'draft' => $statusCounts['draft'] ?? 0,
                'sent' => $statusCounts['sent'] ?? 0,
                'paid' => $statusCounts['paid'] ?? 0,
                'overdue' => $overdue,
                'cancelled' => $statusCounts['cancelled'] ?? 0,
            ],
            'amounts' => [
                'total_amount' => (float) $totalAmount,
                'total_tax' => (float) $totalTax,
                'total_total' => (float) $totalTotal,
                'overdue_amount' => (float) $overdueAmount,
            ],
            'overdue' => $overdue,
        ];
    }

    /**
     * Get payments summary
     */
    public function getPaymentsSummary(int $ownershipId, Carbon $startDate, Carbon $endDate): array
    {
        $query = Payment::where('ownership_id', $ownershipId)
            ->whereBetween('created_at', [$startDate, $endDate]);

        $total = $query->count();
        
        $statusCounts = (clone $query)
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $totalAmount = (clone $query)->sum('amount');

        $methodCounts = (clone $query)
            ->select('method', DB::raw('count(*) as count'))
            ->groupBy('method')
            ->pluck('count', 'method')
            ->toArray();

        $methodAmounts = (clone $query)
            ->select('method', DB::raw('sum(amount) as total'))
            ->groupBy('method')
            ->pluck('total', 'method')
            ->toArray();

        return [
            'total' => $total,
            'status' => [
                'pending' => $statusCounts['pending'] ?? 0,
                'paid' => $statusCounts['paid'] ?? 0,
                'unpaid' => $statusCounts['unpaid'] ?? 0,
            ],
            'total_amount' => (float) $totalAmount,
            'methods' => [
                'cash' => [
                    'count' => $methodCounts['cash'] ?? 0,
                    'amount' => (float) ($methodAmounts['cash'] ?? 0),
                ],
                'bank_transfer' => [
                    'count' => $methodCounts['bank_transfer'] ?? 0,
                    'amount' => (float) ($methodAmounts['bank_transfer'] ?? 0),
                ],
                'check' => [
                    'count' => $methodCounts['check'] ?? 0,
                    'amount' => (float) ($methodAmounts['check'] ?? 0),
                ],
                'visa' => [
                    'count' => $methodCounts['visa'] ?? 0,
                    'amount' => (float) ($methodAmounts['visa'] ?? 0),
                ],
                'other' => [
                    'count' => $methodCounts['other'] ?? 0,
                    'amount' => (float) ($methodAmounts['other'] ?? 0),
                ],
            ],
        ];
    }

    /**
     * Get revenue summary
     * Revenue = Actual payments received (paid status)
     * Receivables = Invoices total (what is owed)
     */
    public function getRevenueSummary(int $ownershipId, Carbon $startDate, Carbon $endDate): array
    {
        // Actual Revenue = Payments received (paid status)
        $thisMonth = Payment::where('ownership_id', $ownershipId)
            ->where('status', 'paid')
            ->whereBetween('paid_at', [
                now()->startOfMonth(),
                now()->endOfMonth()
            ])
            ->sum('amount');

        $lastMonth = Payment::where('ownership_id', $ownershipId)
            ->where('status', 'paid')
            ->whereBetween('paid_at', [
                now()->subMonth()->startOfMonth(),
                now()->subMonth()->endOfMonth()
            ])
            ->sum('amount');

        $growth = $lastMonth > 0 
            ? (($thisMonth - $lastMonth) / $lastMonth) * 100 
            : 0;

        $periodRevenue = Payment::where('ownership_id', $ownershipId)
            ->where('status', 'paid')
            ->whereBetween('paid_at', [$startDate, $endDate])
            ->sum('amount');

        // Receivables = Total invoices amount (what is owed)
        $periodReceivables = Invoice::where('ownership_id', $ownershipId)
            ->whereBetween('period_start', [$startDate, $endDate])
            ->sum('total');

        $periodPaidReceivables = Invoice::where('ownership_id', $ownershipId)
            ->where('status', 'paid')
            ->whereBetween('paid_at', [$startDate, $endDate])
            ->sum('total');

        return [
            'this_month' => (float) $thisMonth,
            'last_month' => (float) $lastMonth,
            'growth' => round($growth, 2),
            'period_revenue' => (float) $periodRevenue,
            'period_receivables' => (float) $periodReceivables,
            'period_paid_receivables' => (float) $periodPaidReceivables,
        ];
    }

    /**
     * Get performance metrics
     */
    public function getPerformanceMetrics(int $ownershipId, Carbon $startDate, Carbon $endDate): array
    {
        $totalInvoices = Invoice::where('ownership_id', $ownershipId)
            ->whereBetween('period_start', [$startDate, $endDate])
            ->count();

        $paidInvoices = Invoice::where('ownership_id', $ownershipId)
            ->where('status', 'paid')
            ->whereBetween('period_start', [$startDate, $endDate])
            ->count();

        $collectionRate = $totalInvoices > 0 
            ? ($paidInvoices / $totalInvoices) * 100 
            : 0;

        $totalInvoiceAmount = Invoice::where('ownership_id', $ownershipId)
            ->whereBetween('period_start', [$startDate, $endDate])
            ->sum('total');

        $totalPaidAmount = Invoice::where('ownership_id', $ownershipId)
            ->where('status', 'paid')
            ->whereBetween('period_start', [$startDate, $endDate])
            ->sum('total');

        $collectionRateByAmount = $totalInvoiceAmount > 0 
            ? ($totalPaidAmount / $totalInvoiceAmount) * 100 
            : 0;

        return [
            'collection_rate' => round($collectionRate, 2),
            'collection_rate_by_amount' => round($collectionRateByAmount, 2),
            'total_invoices' => $totalInvoices,
            'paid_invoices' => $paidInvoices,
        ];
    }

    /**
     * Get monthly revenue data
     */
    public function getMonthlyRevenue(int $ownershipId, int $months = 12): array
    {
        $cacheKey = "monthly_revenue_{$ownershipId}_{$months}";

        return Cache::remember($cacheKey, now()->addMinutes(self::CACHE_DURATION), function () use ($ownershipId, $months) {
            $data = [];
            $startDate = now()->subMonths($months - 1)->startOfMonth();

            for ($i = 0; $i < $months; $i++) {
                $monthStart = $startDate->copy()->addMonths($i)->startOfMonth();
                $monthEnd = $monthStart->copy()->endOfMonth();

                // Revenue = Actual payments received
                $revenue = Payment::where('ownership_id', $ownershipId)
                    ->where('status', 'paid')
                    ->whereBetween('paid_at', [$monthStart, $monthEnd])
                    ->sum('amount');

                // Receivables = Invoices total (what is owed)
                $receivables = Invoice::where('ownership_id', $ownershipId)
                    ->whereBetween('period_start', [$monthStart, $monthEnd])
                    ->sum('total');

                $paidReceivables = Invoice::where('ownership_id', $ownershipId)
                    ->where('status', 'paid')
                    ->whereBetween('paid_at', [$monthStart, $monthEnd])
                    ->sum('total');

                $data[] = [
                    'month' => $monthStart->format('Y-m'),
                    'month_name' => $monthStart->format('F Y'),
                    'revenue' => (float) $revenue,
                    'receivables' => (float) $receivables,
                    'paid_receivables' => (float) $paidReceivables,
                ];
            }

            return $data;
        });
    }

    /**
     * Get tenants ratings distribution
     */
    public function getTenantsRatings(int $ownershipId): array
    {
        $cacheKey = "tenants_ratings_{$ownershipId}";

        return Cache::remember($cacheKey, now()->addMinutes(self::CACHE_DURATION), function () use ($ownershipId) {
            $ratings = Tenant::where('ownership_id', $ownershipId)
                ->select('rating', DB::raw('count(*) as count'))
                ->groupBy('rating')
                ->get()
                ->mapWithKeys(function ($item) {
                    return [$item->rating => $item->count];
                })
                ->toArray();

            $total = array_sum($ratings);

            return [
                'excellent' => [
                    'count' => $ratings['excellent'] ?? 0,
                    'percentage' => $total > 0 ? round((($ratings['excellent'] ?? 0) / $total) * 100, 2) : 0,
                ],
                'good' => [
                    'count' => $ratings['good'] ?? 0,
                    'percentage' => $total > 0 ? round((($ratings['good'] ?? 0) / $total) * 100, 2) : 0,
                ],
                'fair' => [
                    'count' => $ratings['fair'] ?? 0,
                    'percentage' => $total > 0 ? round((($ratings['fair'] ?? 0) / $total) * 100, 2) : 0,
                ],
                'poor' => [
                    'count' => $ratings['poor'] ?? 0,
                    'percentage' => $total > 0 ? round((($ratings['poor'] ?? 0) / $total) * 100, 2) : 0,
                ],
                'total' => $total,
            ];
        });
    }

    /**
     * Get contracts status distribution
     */
    public function getContractsStatus(int $ownershipId): array
    {
        $cacheKey = "contracts_status_{$ownershipId}";

        return Cache::remember($cacheKey, now()->addMinutes(self::CACHE_DURATION), function () use ($ownershipId) {
            $statuses = Contract::where('ownership_id', $ownershipId)
                ->select('status', DB::raw('count(*) as count'))
                ->groupBy('status')
                ->get()
                ->mapWithKeys(function ($item) {
                    return [$item->status => $item->count];
                })
                ->toArray();

            $total = array_sum($statuses);

            return [
                'draft' => [
                    'count' => $statuses['draft'] ?? 0,
                    'percentage' => $total > 0 ? round((($statuses['draft'] ?? 0) / $total) * 100, 2) : 0,
                ],
                'pending' => [
                    'count' => $statuses['pending'] ?? 0,
                    'percentage' => $total > 0 ? round((($statuses['pending'] ?? 0) / $total) * 100, 2) : 0,
                ],
                'active' => [
                    'count' => $statuses['active'] ?? 0,
                    'percentage' => $total > 0 ? round((($statuses['active'] ?? 0) / $total) * 100, 2) : 0,
                ],
                'expired' => [
                    'count' => $statuses['expired'] ?? 0,
                    'percentage' => $total > 0 ? round((($statuses['expired'] ?? 0) / $total) * 100, 2) : 0,
                ],
                'terminated' => [
                    'count' => $statuses['terminated'] ?? 0,
                    'percentage' => $total > 0 ? round((($statuses['terminated'] ?? 0) / $total) * 100, 2) : 0,
                ],
                'cancelled' => [
                    'count' => $statuses['cancelled'] ?? 0,
                    'percentage' => $total > 0 ? round((($statuses['cancelled'] ?? 0) / $total) * 100, 2) : 0,
                ],
                'total' => $total,
            ];
        });
    }

    /**
     * Get invoices status distribution
     */
    public function getInvoicesStatus(int $ownershipId, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $startDate = $startDate ?? now()->startOfMonth();
        $endDate = $endDate ?? now()->endOfMonth();
        
        $cacheKey = "invoices_status_{$ownershipId}_" . $startDate->format('Y-m-d') . '_' . $endDate->format('Y-m-d');

        return Cache::remember($cacheKey, now()->addMinutes(self::CACHE_DURATION), function () use ($ownershipId, $startDate, $endDate) {
            $statuses = Invoice::where('ownership_id', $ownershipId)
                ->whereBetween('period_start', [$startDate, $endDate])
                ->select('status', DB::raw('count(*) as count'))
                ->groupBy('status')
                ->get()
                ->mapWithKeys(function ($item) {
                    return [$item->status => $item->count];
                })
                ->toArray();

            $total = array_sum($statuses);

            return [
                'draft' => [
                    'count' => $statuses['draft'] ?? 0,
                    'percentage' => $total > 0 ? round((($statuses['draft'] ?? 0) / $total) * 100, 2) : 0,
                ],
                'sent' => [
                    'count' => $statuses['sent'] ?? 0,
                    'percentage' => $total > 0 ? round((($statuses['sent'] ?? 0) / $total) * 100, 2) : 0,
                ],
                'paid' => [
                    'count' => $statuses['paid'] ?? 0,
                    'percentage' => $total > 0 ? round((($statuses['paid'] ?? 0) / $total) * 100, 2) : 0,
                ],
                'overdue' => [
                    'count' => $statuses['overdue'] ?? 0,
                    'percentage' => $total > 0 ? round((($statuses['overdue'] ?? 0) / $total) * 100, 2) : 0,
                ],
                'cancelled' => [
                    'count' => $statuses['cancelled'] ?? 0,
                    'percentage' => $total > 0 ? round((($statuses['cancelled'] ?? 0) / $total) * 100, 2) : 0,
                ],
                'total' => $total,
            ];
        });
    }

    /**
     * Get payment methods distribution
     */
    public function getPaymentMethods(int $ownershipId, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $startDate = $startDate ?? now()->startOfMonth();
        $endDate = $endDate ?? now()->endOfMonth();
        
        $cacheKey = "payment_methods_{$ownershipId}_" . $startDate->format('Y-m-d') . '_' . $endDate->format('Y-m-d');

        return Cache::remember($cacheKey, now()->addMinutes(self::CACHE_DURATION), function () use ($ownershipId, $startDate, $endDate) {
            $methods = Payment::where('ownership_id', $ownershipId)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->select('method', DB::raw('count(*) as count'), DB::raw('sum(amount) as total'))
                ->groupBy('method')
                ->get()
                ->keyBy('method')
                ->map(function ($item) {
                    return [
                        'count' => $item->count,
                        'amount' => (float) $item->total,
                    ];
                })
                ->toArray();

            $totalCount = array_sum(array_column($methods, 'count'));
            $totalAmount = array_sum(array_column($methods, 'amount'));

            return [
                'cash' => [
                    'count' => $methods['cash']['count'] ?? 0,
                    'amount' => (float) ($methods['cash']['amount'] ?? 0),
                    'percentage' => $totalCount > 0 ? round((($methods['cash']['count'] ?? 0) / $totalCount) * 100, 2) : 0,
                ],
                'bank_transfer' => [
                    'count' => $methods['bank_transfer']['count'] ?? 0,
                    'amount' => (float) ($methods['bank_transfer']['amount'] ?? 0),
                    'percentage' => $totalCount > 0 ? round((($methods['bank_transfer']['count'] ?? 0) / $totalCount) * 100, 2) : 0,
                ],
                'check' => [
                    'count' => $methods['check']['count'] ?? 0,
                    'amount' => (float) ($methods['check']['amount'] ?? 0),
                    'percentage' => $totalCount > 0 ? round((($methods['check']['count'] ?? 0) / $totalCount) * 100, 2) : 0,
                ],
                'visa' => [
                    'count' => $methods['visa']['count'] ?? 0,
                    'amount' => (float) ($methods['visa']['amount'] ?? 0),
                    'percentage' => $totalCount > 0 ? round((($methods['visa']['count'] ?? 0) / $totalCount) * 100, 2) : 0,
                ],
                'other' => [
                    'count' => $methods['other']['count'] ?? 0,
                    'amount' => (float) ($methods['other']['amount'] ?? 0),
                    'percentage' => $totalCount > 0 ? round((($methods['other']['count'] ?? 0) / $totalCount) * 100, 2) : 0,
                ],
                'total' => [
                    'count' => $totalCount,
                    'amount' => $totalAmount,
                ],
            ];
        });
    }

    /**
     * Get expiring contracts
     */
    public function getExpiringContracts(int $ownershipId, int $days = 30): array
    {
        $cacheKey = "expiring_contracts_{$ownershipId}_{$days}";

        return Cache::remember($cacheKey, now()->addMinutes(self::CACHE_DURATION), function () use ($ownershipId, $days) {
            return Contract::where('ownership_id', $ownershipId)
                ->where('status', 'active')
                ->whereBetween('end', [now(), now()->addDays($days)])
                ->with(['tenant.user', 'units'])
                ->orderBy('end', 'asc')
                ->get()
                ->map(function ($contract) {
                    return [
                        'id' => $contract->id,
                        'uuid' => $contract->uuid,
                        'number' => $contract->number,
                        'tenant_name' => $contract->tenant->user->name ?? 'N/A',
                        'unit_number' => $contract->primaryUnit()?->number ?? 'N/A',
                        'end_date' => $contract->end->format('Y-m-d'),
                        'days_remaining' => now()->diffInDays($contract->end, false),
                        'base_rent' => (float) ($contract->base_rent ?? 0),
                    ];
                })
                ->toArray();
        });
    }

    /**
     * Get overdue invoices
     */
    public function getOverdueInvoices(int $ownershipId): array
    {
        $cacheKey = "overdue_invoices_{$ownershipId}";

        return Cache::remember($cacheKey, now()->addMinutes(self::CACHE_DURATION), function () use ($ownershipId) {
            return Invoice::where('ownership_id', $ownershipId)
                ->where('status', 'overdue')
                ->with(['contract.tenant.user', 'contract.units'])
                ->orderBy('due', 'asc')
                ->get()
                ->map(function ($invoice) {
                    return [
                        'id' => $invoice->id,
                        'uuid' => $invoice->uuid,
                        'number' => $invoice->number,
                        'tenant_name' => $invoice->contract->tenant->user->name ?? 'N/A',
                        'unit_number' => $invoice->contract->unit()?->number ?? 'N/A',
                        'due_date' => $invoice->due->format('Y-m-d'),
                        'days_overdue' => now()->diffInDays($invoice->due, false),
                        'total' => (float) $invoice->total,
                        'amount' => (float) $invoice->amount,
                    ];
                })
                ->toArray();
        });
    }

    /**
     * Get employment distribution
     */
    public function getEmploymentDistribution(int $ownershipId): array
    {
        $cacheKey = "employment_distribution_{$ownershipId}";

        return Cache::remember($cacheKey, now()->addMinutes(self::CACHE_DURATION), function () use ($ownershipId) {
            $employment = Tenant::where('ownership_id', $ownershipId)
                ->select('employment', DB::raw('count(*) as count'))
                ->groupBy('employment')
                ->get()
                ->mapWithKeys(function ($item) {
                    return [$item->employment => $item->count];
                })
                ->toArray();

            $total = array_sum($employment);
            $avgIncome = Tenant::where('ownership_id', $ownershipId)
                ->whereNotNull('income')
                ->avg('income');

            return [
                'employed' => [
                    'count' => $employment['employed'] ?? 0,
                    'percentage' => $total > 0 ? round((($employment['employed'] ?? 0) / $total) * 100, 2) : 0,
                ],
                'self_employed' => [
                    'count' => $employment['self_employed'] ?? 0,
                    'percentage' => $total > 0 ? round((($employment['self_employed'] ?? 0) / $total) * 100, 2) : 0,
                ],
                'unemployed' => [
                    'count' => $employment['unemployed'] ?? 0,
                    'percentage' => $total > 0 ? round((($employment['unemployed'] ?? 0) / $total) * 100, 2) : 0,
                ],
                'retired' => [
                    'count' => $employment['retired'] ?? 0,
                    'percentage' => $total > 0 ? round((($employment['retired'] ?? 0) / $total) * 100, 2) : 0,
                ],
                'student' => [
                    'count' => $employment['student'] ?? 0,
                    'percentage' => $total > 0 ? round((($employment['student'] ?? 0) / $total) * 100, 2) : 0,
                ],
                'total' => $total,
                'average_income' => round((float) $avgIncome, 2),
            ];
        });
    }

    /**
     * Get contracts financial summary
     */
    public function getContractsFinancial(int $ownershipId): array
    {
        $cacheKey = "contracts_financial_{$ownershipId}";

        return Cache::remember($cacheKey, now()->addMinutes(self::CACHE_DURATION), function () use ($ownershipId) {
            $activeContracts = Contract::where('ownership_id', $ownershipId)
                ->where('status', 'active')
                ->get();

            $totalRent = $activeContracts->sum('total_rent');
            $avgRent = $activeContracts->avg('total_rent');
            $minRent = $activeContracts->min('total_rent');
            $maxRent = $activeContracts->max('total_rent');

            $depositStatus = Contract::where('ownership_id', $ownershipId)
                ->select('deposit_status', DB::raw('sum(deposit) as total'))
                ->groupBy('deposit_status')
                ->get()
                ->mapWithKeys(function ($item) {
                    return [$item->deposit_status => (float) $item->total];
                })
                ->toArray();

            $paymentFrequency = Contract::where('ownership_id', $ownershipId)
                ->where('status', 'active')
                ->select('payment_frequency', DB::raw('count(*) as count'))
                ->groupBy('payment_frequency')
                ->get()
                ->mapWithKeys(function ($item) {
                    return [$item->payment_frequency => $item->count];
                })
                ->toArray();

            return [
                'total_rent' => round($totalRent, 2),
                'average_rent' => round($avgRent, 2),
                'min_rent' => round($minRent, 2),
                'max_rent' => round($maxRent, 2),
                'deposits' => [
                    'pending' => $depositStatus['pending'] ?? 0,
                    'paid' => $depositStatus['paid'] ?? 0,
                    'refunded' => $depositStatus['refunded'] ?? 0,
                    'forfeited' => $depositStatus['forfeited'] ?? 0,
                    'total' => array_sum($depositStatus),
                ],
                'payment_frequency' => [
                    'monthly' => $paymentFrequency['monthly'] ?? 0,
                    'quarterly' => $paymentFrequency['quarterly'] ?? 0,
                    'yearly' => $paymentFrequency['yearly'] ?? 0,
                    'weekly' => $paymentFrequency['weekly'] ?? 0,
                ],
            ];
        });
    }

    /**
     * Get revenue by period (daily, weekly, monthly)
     */
    public function getRevenueByPeriod(int $ownershipId, string $period = 'monthly', int $count = 12): array
    {
        $cacheKey = "revenue_period_{$ownershipId}_{$period}_{$count}";

        return Cache::remember($cacheKey, now()->addMinutes(self::CACHE_DURATION), function () use ($ownershipId, $period, $count) {
            $data = [];
            $now = now();

            for ($i = 0; $i < $count; $i++) {
                if ($period === 'daily') {
                    $periodStart = $now->copy()->subDays($count - $i - 1)->startOfDay();
                    $periodEnd = $periodStart->copy()->endOfDay();
                    $label = $periodStart->format('Y-m-d');
                } elseif ($period === 'weekly') {
                    $periodStart = $now->copy()->subWeeks($count - $i - 1)->startOfWeek();
                    $periodEnd = $periodStart->copy()->endOfWeek();
                    $label = $periodStart->format('Y-m-d') . ' to ' . $periodEnd->format('Y-m-d');
                } else { // monthly
                    $periodStart = $now->copy()->subMonths($count - $i - 1)->startOfMonth();
                    $periodEnd = $periodStart->copy()->endOfMonth();
                    $label = $periodStart->format('F Y');
                }

                // Revenue = Actual payments received
                $revenue = Payment::where('ownership_id', $ownershipId)
                    ->where('status', 'paid')
                    ->whereBetween('paid_at', [$periodStart, $periodEnd])
                    ->sum('amount');

                // Receivables = Invoices total (what is owed)
                $receivables = Invoice::where('ownership_id', $ownershipId)
                    ->whereBetween('period_start', [$periodStart, $periodEnd])
                    ->sum('total');

                $paidReceivables = Invoice::where('ownership_id', $ownershipId)
                    ->where('status', 'paid')
                    ->whereBetween('paid_at', [$periodStart, $periodEnd])
                    ->sum('total');

                $invoices = Invoice::where('ownership_id', $ownershipId)
                    ->whereBetween('period_start', [$periodStart, $periodEnd])
                    ->count();

                $paidInvoices = Invoice::where('ownership_id', $ownershipId)
                    ->where('status', 'paid')
                    ->whereBetween('paid_at', [$periodStart, $periodEnd])
                    ->count();

                $data[] = [
                    'period' => $label,
                    'period_start' => $periodStart->format('Y-m-d'),
                    'period_end' => $periodEnd->format('Y-m-d'),
                    'revenue' => (float) $revenue,
                    'receivables' => (float) $receivables,
                    'paid_receivables' => (float) $paidReceivables,
                    'invoices' => $invoices,
                    'paid_invoices' => $paidInvoices,
                    'collection_rate' => $receivables > 0 ? round(($paidReceivables / $receivables) * 100, 2) : 0,
                ];
            }

            return $data;
        });
    }

    /**
     * Get top tenants by payment performance
     */
    public function getTopTenants(int $ownershipId, int $limit = 10): array
    {
        $cacheKey = "top_tenants_{$ownershipId}_{$limit}";

        return Cache::remember($cacheKey, now()->addMinutes(self::CACHE_DURATION), function () use ($ownershipId, $limit) {
            return Tenant::where('ownership_id', $ownershipId)
                ->with(['user', 'contracts.invoices'])
                ->get()
                ->map(function ($tenant) {
                    $allInvoices = $tenant->contracts->flatMap->invoices;
                    $totalInvoices = $allInvoices->count();
                    $paidInvoices = $allInvoices->where('status', 'paid')->count();
                    $totalPaid = $allInvoices->where('status', 'paid')->sum('total');
                    $onTimePayments = $allInvoices
                        ->where('status', 'paid')
                        ->filter(function ($invoice) {
                            if (!$invoice->paid_at) {
                                return false;
                            }
                            $paidAt = $invoice->paid_at instanceof Carbon ? $invoice->paid_at : Carbon::parse($invoice->paid_at);
                            $due = $invoice->due instanceof Carbon ? $invoice->due : Carbon::parse($invoice->due);
                            return $paidAt->lte($due);
                        })
                        ->count();

                    return [
                        'tenant_id' => $tenant->id,
                        'tenant_name' => $tenant->user->name ?? 'N/A',
                        'rating' => $tenant->rating,
                        'total_invoices' => $totalInvoices,
                        'paid_invoices' => $paidInvoices,
                        'payment_rate' => $totalInvoices > 0 ? round(($paidInvoices / $totalInvoices) * 100, 2) : 0,
                        'on_time_rate' => $paidInvoices > 0 ? round(($onTimePayments / $paidInvoices) * 100, 2) : 0,
                        'total_paid' => round($totalPaid, 2),
                    ];
                })
                ->sortByDesc('payment_rate')
                ->take($limit)
                ->values()
                ->toArray();
        });
    }

    /**
     * Clear cache for ownership
     */
    public function clearCache(int $ownershipId): void
    {
        // Clear all cache keys for this ownership
        // Note: This is a simple implementation. For production, consider using cache tags with Redis
        $keys = [
            "dashboard_overview_{$ownershipId}_*",
            "monthly_revenue_{$ownershipId}_*",
            "tenants_ratings_{$ownershipId}",
            "contracts_status_{$ownershipId}",
            "invoices_status_{$ownershipId}_*",
            "payment_methods_{$ownershipId}_*",
            "expiring_contracts_{$ownershipId}_*",
            "overdue_invoices_{$ownershipId}",
            "employment_distribution_{$ownershipId}",
            "contracts_financial_{$ownershipId}",
            "revenue_period_{$ownershipId}_*",
            "top_tenants_{$ownershipId}_*",
        ];

        // If using Redis with cache tags, you could do:
        // Cache::tags(["reports_{$ownershipId}"])->flush();
        
        // For now, we'll log that cache should be cleared
        // In production, implement proper cache invalidation
        Log::info("Cache clear requested for ownership: {$ownershipId}");
    }

    /**
     * Clear system-wide cache
     */
    public function clearSystemCache(): void
    {
        Log::info("System-wide cache clear requested");
        // In production, implement proper cache invalidation for all ownerships
    }

    // ==================== SYSTEM-WIDE REPORTS (Super Admin) ====================

    /**
     * Get system-wide dashboard overview
     */
    public function getSystemDashboardOverview(?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $cacheKey = "system_dashboard_overview_" . ($startDate?->format('Y-m-d') ?? 'all') . '_' . ($endDate?->format('Y-m-d') ?? 'all');

        return Cache::remember($cacheKey, now()->addMinutes(self::CACHE_DURATION), function () use ($startDate, $endDate) {
            $now = now();
            $startDate = $startDate ?? $now->copy()->startOfMonth();
            $endDate = $endDate ?? $now->copy()->endOfMonth();

            return [
                'tenants' => $this->getSystemTenantsSummary(),
                'contracts' => $this->getSystemContractsSummary(),
                'invoices' => $this->getSystemInvoicesSummary($startDate, $endDate),
                'payments' => $this->getSystemPaymentsSummary($startDate, $endDate),
                'revenue' => $this->getSystemRevenueSummary($startDate, $endDate),
                'performance' => $this->getSystemPerformanceMetrics($startDate, $endDate),
                'ownerships' => [
                    'total' => \App\Models\V1\Ownership\Ownership::count(),
                    'active' => \App\Models\V1\Ownership\Ownership::where('active', true)->count(),
                ],
            ];
        });
    }

    /**
     * Get system-wide tenants summary
     */
    public function getSystemTenantsSummary(): array
    {
        $total = Tenant::count();
        
        $active = Tenant::whereHas('contracts', function ($query) {
            $query->where('status', 'active');
        })->count();

        $newThisMonth = Tenant::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        $ratings = Tenant::select('rating', DB::raw('count(*) as count'))
            ->groupBy('rating')
            ->pluck('count', 'rating')
            ->toArray();

        return [
            'total' => $total,
            'active' => $active,
            'new_this_month' => $newThisMonth,
            'ratings' => [
                'excellent' => $ratings['excellent'] ?? 0,
                'good' => $ratings['good'] ?? 0,
                'fair' => $ratings['fair'] ?? 0,
                'poor' => $ratings['poor'] ?? 0,
            ],
        ];
    }

    /**
     * Get system-wide contracts summary
     */
    public function getSystemContractsSummary(): array
    {
        $total = Contract::count();
        
        $statusCounts = Contract::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $active = $statusCounts['active'] ?? 0;
        $expiringSoon = Contract::where('status', 'active')
            ->whereBetween('end', [now(), now()->addDays(30)])
            ->count();

        $totalRent = Contract::where('status', 'active')->sum('total_rent');
        $totalDeposits = Contract::sum('deposit');

        return [
            'total' => $total,
            'active' => $active,
            'expiring_soon' => $expiringSoon,
            'status' => [
                'draft' => $statusCounts['draft'] ?? 0,
                'pending' => $statusCounts['pending'] ?? 0,
                'active' => $active,
                'expired' => $statusCounts['expired'] ?? 0,
                'terminated' => $statusCounts['terminated'] ?? 0,
                'cancelled' => $statusCounts['cancelled'] ?? 0,
            ],
            'total_rent' => (float) $totalRent,
            'total_deposits' => (float) $totalDeposits,
        ];
    }

    /**
     * Get system-wide invoices summary
     */
    public function getSystemInvoicesSummary(Carbon $startDate, Carbon $endDate): array
    {
        $query = Invoice::whereBetween('period_start', [$startDate, $endDate]);

        $total = $query->count();
        
        $statusCounts = (clone $query)
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $totalAmount = (clone $query)->sum('amount');
        $totalTax = (clone $query)->sum('tax');
        $totalTotal = (clone $query)->sum('total');

        $overdue = (clone $query)
            ->where('status', 'overdue')
            ->count();

        $overdueAmount = (clone $query)
            ->where('status', 'overdue')
            ->sum('total');

        return [
            'total' => $total,
            'status' => [
                'draft' => $statusCounts['draft'] ?? 0,
                'sent' => $statusCounts['sent'] ?? 0,
                'paid' => $statusCounts['paid'] ?? 0,
                'overdue' => $overdue,
                'cancelled' => $statusCounts['cancelled'] ?? 0,
            ],
            'amounts' => [
                'total_amount' => (float) $totalAmount,
                'total_tax' => (float) $totalTax,
                'total_total' => (float) $totalTotal,
                'overdue_amount' => (float) $overdueAmount,
            ],
            'overdue' => $overdue,
        ];
    }

    /**
     * Get system-wide payments summary
     */
    public function getSystemPaymentsSummary(Carbon $startDate, Carbon $endDate): array
    {
        $query = Payment::whereBetween('created_at', [$startDate, $endDate]);

        $total = $query->count();
        
        $statusCounts = (clone $query)
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $totalAmount = (clone $query)->sum('amount');

        $methodCounts = (clone $query)
            ->select('method', DB::raw('count(*) as count'))
            ->groupBy('method')
            ->pluck('count', 'method')
            ->toArray();

        $methodAmounts = (clone $query)
            ->select('method', DB::raw('sum(amount) as total'))
            ->groupBy('method')
            ->pluck('total', 'method')
            ->toArray();

        return [
            'total' => $total,
            'status' => [
                'pending' => $statusCounts['pending'] ?? 0,
                'paid' => $statusCounts['paid'] ?? 0,
                'unpaid' => $statusCounts['unpaid'] ?? 0,
            ],
            'total_amount' => (float) $totalAmount,
            'methods' => [
                'cash' => [
                    'count' => $methodCounts['cash'] ?? 0,
                    'amount' => (float) ($methodAmounts['cash'] ?? 0),
                ],
                'bank_transfer' => [
                    'count' => $methodCounts['bank_transfer'] ?? 0,
                    'amount' => (float) ($methodAmounts['bank_transfer'] ?? 0),
                ],
                'check' => [
                    'count' => $methodCounts['check'] ?? 0,
                    'amount' => (float) ($methodAmounts['check'] ?? 0),
                ],
                'visa' => [
                    'count' => $methodCounts['visa'] ?? 0,
                    'amount' => (float) ($methodAmounts['visa'] ?? 0),
                ],
                'other' => [
                    'count' => $methodCounts['other'] ?? 0,
                    'amount' => (float) ($methodAmounts['other'] ?? 0),
                ],
            ],
        ];
    }

    /**
     * Get system-wide revenue summary
     */
    public function getSystemRevenueSummary(Carbon $startDate, Carbon $endDate): array
    {
        $thisMonth = Invoice::where('status', 'paid')
            ->whereBetween('paid_at', [
                now()->startOfMonth(),
                now()->endOfMonth()
            ])
            ->sum('total');

        $lastMonth = Invoice::where('status', 'paid')
            ->whereBetween('paid_at', [
                now()->subMonth()->startOfMonth(),
                now()->subMonth()->endOfMonth()
            ])
            ->sum('total');

        $growth = $lastMonth > 0 
            ? (($thisMonth - $lastMonth) / $lastMonth) * 100 
            : 0;

        $periodRevenue = Invoice::where('status', 'paid')
            ->whereBetween('paid_at', [$startDate, $endDate])
            ->sum('total');

        return [
            'this_month' => (float) $thisMonth,
            'last_month' => (float) $lastMonth,
            'growth' => round($growth, 2),
            'period_revenue' => (float) $periodRevenue,
        ];
    }

    /**
     * Get system-wide performance metrics
     */
    public function getSystemPerformanceMetrics(Carbon $startDate, Carbon $endDate): array
    {
        $totalInvoices = Invoice::whereBetween('period_start', [$startDate, $endDate])->count();
        $paidInvoices = Invoice::where('status', 'paid')
            ->whereBetween('period_start', [$startDate, $endDate])
            ->count();

        $collectionRate = $totalInvoices > 0 
            ? ($paidInvoices / $totalInvoices) * 100 
            : 0;

        $totalInvoiceAmount = Invoice::whereBetween('period_start', [$startDate, $endDate])->sum('total');
        $totalPaidAmount = Invoice::where('status', 'paid')
            ->whereBetween('period_start', [$startDate, $endDate])
            ->sum('total');

        $collectionRateByAmount = $totalInvoiceAmount > 0 
            ? ($totalPaidAmount / $totalInvoiceAmount) * 100 
            : 0;

        return [
            'collection_rate' => round($collectionRate, 2),
            'collection_rate_by_amount' => round($collectionRateByAmount, 2),
            'total_invoices' => $totalInvoices,
            'paid_invoices' => $paidInvoices,
        ];
    }

    /**
     * Get system-wide tenants ratings
     */
    public function getSystemTenantsRatings(): array
    {
        $cacheKey = "system_tenants_ratings";

        return Cache::remember($cacheKey, now()->addMinutes(self::CACHE_DURATION), function () {
            $ratings = Tenant::select('rating', DB::raw('count(*) as count'))
                ->groupBy('rating')
                ->get()
                ->mapWithKeys(function ($item) {
                    return [$item->rating => $item->count];
                })
                ->toArray();

            $total = array_sum($ratings);

            return [
                'excellent' => [
                    'count' => $ratings['excellent'] ?? 0,
                    'percentage' => $total > 0 ? round((($ratings['excellent'] ?? 0) / $total) * 100, 2) : 0,
                ],
                'good' => [
                    'count' => $ratings['good'] ?? 0,
                    'percentage' => $total > 0 ? round((($ratings['good'] ?? 0) / $total) * 100, 2) : 0,
                ],
                'fair' => [
                    'count' => $ratings['fair'] ?? 0,
                    'percentage' => $total > 0 ? round((($ratings['fair'] ?? 0) / $total) * 100, 2) : 0,
                ],
                'poor' => [
                    'count' => $ratings['poor'] ?? 0,
                    'percentage' => $total > 0 ? round((($ratings['poor'] ?? 0) / $total) * 100, 2) : 0,
                ],
                'total' => $total,
            ];
        });
    }

    /**
     * Get system-wide contracts status
     */
    public function getSystemContractsStatus(): array
    {
        $cacheKey = "system_contracts_status";

        return Cache::remember($cacheKey, now()->addMinutes(self::CACHE_DURATION), function () {
            $statuses = Contract::select('status', DB::raw('count(*) as count'))
                ->groupBy('status')
                ->get()
                ->mapWithKeys(function ($item) {
                    return [$item->status => $item->count];
                })
                ->toArray();

            $total = array_sum($statuses);

            return [
                'draft' => [
                    'count' => $statuses['draft'] ?? 0,
                    'percentage' => $total > 0 ? round((($statuses['draft'] ?? 0) / $total) * 100, 2) : 0,
                ],
                'pending' => [
                    'count' => $statuses['pending'] ?? 0,
                    'percentage' => $total > 0 ? round((($statuses['pending'] ?? 0) / $total) * 100, 2) : 0,
                ],
                'active' => [
                    'count' => $statuses['active'] ?? 0,
                    'percentage' => $total > 0 ? round((($statuses['active'] ?? 0) / $total) * 100, 2) : 0,
                ],
                'expired' => [
                    'count' => $statuses['expired'] ?? 0,
                    'percentage' => $total > 0 ? round((($statuses['expired'] ?? 0) / $total) * 100, 2) : 0,
                ],
                'terminated' => [
                    'count' => $statuses['terminated'] ?? 0,
                    'percentage' => $total > 0 ? round((($statuses['terminated'] ?? 0) / $total) * 100, 2) : 0,
                ],
                'cancelled' => [
                    'count' => $statuses['cancelled'] ?? 0,
                    'percentage' => $total > 0 ? round((($statuses['cancelled'] ?? 0) / $total) * 100, 2) : 0,
                ],
                'total' => $total,
            ];
        });
    }

    /**
     * Get system-wide invoices status
     */
    public function getSystemInvoicesStatus(?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $startDate = $startDate ?? now()->startOfMonth();
        $endDate = $endDate ?? now()->endOfMonth();
        
        $cacheKey = "system_invoices_status_" . $startDate->format('Y-m-d') . '_' . $endDate->format('Y-m-d');

        return Cache::remember($cacheKey, now()->addMinutes(self::CACHE_DURATION), function () use ($startDate, $endDate) {
            $statuses = Invoice::whereBetween('period_start', [$startDate, $endDate])
                ->select('status', DB::raw('count(*) as count'))
                ->groupBy('status')
                ->get()
                ->mapWithKeys(function ($item) {
                    return [$item->status => $item->count];
                })
                ->toArray();

            $total = array_sum($statuses);

            return [
                'draft' => [
                    'count' => $statuses['draft'] ?? 0,
                    'percentage' => $total > 0 ? round((($statuses['draft'] ?? 0) / $total) * 100, 2) : 0,
                ],
                'sent' => [
                    'count' => $statuses['sent'] ?? 0,
                    'percentage' => $total > 0 ? round((($statuses['sent'] ?? 0) / $total) * 100, 2) : 0,
                ],
                'paid' => [
                    'count' => $statuses['paid'] ?? 0,
                    'percentage' => $total > 0 ? round((($statuses['paid'] ?? 0) / $total) * 100, 2) : 0,
                ],
                'overdue' => [
                    'count' => $statuses['overdue'] ?? 0,
                    'percentage' => $total > 0 ? round((($statuses['overdue'] ?? 0) / $total) * 100, 2) : 0,
                ],
                'cancelled' => [
                    'count' => $statuses['cancelled'] ?? 0,
                    'percentage' => $total > 0 ? round((($statuses['cancelled'] ?? 0) / $total) * 100, 2) : 0,
                ],
                'total' => $total,
            ];
        });
    }

    /**
     * Get system-wide payment methods
     */
    public function getSystemPaymentMethods(?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $startDate = $startDate ?? now()->startOfMonth();
        $endDate = $endDate ?? now()->endOfMonth();
        
        $cacheKey = "system_payment_methods_" . $startDate->format('Y-m-d') . '_' . $endDate->format('Y-m-d');

        return Cache::remember($cacheKey, now()->addMinutes(self::CACHE_DURATION), function () use ($startDate, $endDate) {
            $methods = Payment::whereBetween('created_at', [$startDate, $endDate])
                ->select('method', DB::raw('count(*) as count'), DB::raw('sum(amount) as total'))
                ->groupBy('method')
                ->get()
                ->keyBy('method')
                ->map(function ($item) {
                    return [
                        'count' => $item->count,
                        'amount' => (float) $item->total,
                    ];
                })
                ->toArray();

            $totalCount = array_sum(array_column($methods, 'count'));
            $totalAmount = array_sum(array_column($methods, 'amount'));

            return [
                'cash' => [
                    'count' => $methods['cash']['count'] ?? 0,
                    'amount' => (float) ($methods['cash']['amount'] ?? 0),
                    'percentage' => $totalCount > 0 ? round((($methods['cash']['count'] ?? 0) / $totalCount) * 100, 2) : 0,
                ],
                'bank_transfer' => [
                    'count' => $methods['bank_transfer']['count'] ?? 0,
                    'amount' => (float) ($methods['bank_transfer']['amount'] ?? 0),
                    'percentage' => $totalCount > 0 ? round((($methods['bank_transfer']['count'] ?? 0) / $totalCount) * 100, 2) : 0,
                ],
                'check' => [
                    'count' => $methods['check']['count'] ?? 0,
                    'amount' => (float) ($methods['check']['amount'] ?? 0),
                    'percentage' => $totalCount > 0 ? round((($methods['check']['count'] ?? 0) / $totalCount) * 100, 2) : 0,
                ],
                'visa' => [
                    'count' => $methods['visa']['count'] ?? 0,
                    'amount' => (float) ($methods['visa']['amount'] ?? 0),
                    'percentage' => $totalCount > 0 ? round((($methods['visa']['count'] ?? 0) / $totalCount) * 100, 2) : 0,
                ],
                'other' => [
                    'count' => $methods['other']['count'] ?? 0,
                    'amount' => (float) ($methods['other']['amount'] ?? 0),
                    'percentage' => $totalCount > 0 ? round((($methods['other']['count'] ?? 0) / $totalCount) * 100, 2) : 0,
                ],
                'total' => [
                    'count' => $totalCount,
                    'amount' => $totalAmount,
                ],
            ];
        });
    }

    /**
     * Get system-wide expiring contracts
     */
    public function getSystemExpiringContracts(int $days = 30): array
    {
        $cacheKey = "system_expiring_contracts_{$days}";

        return Cache::remember($cacheKey, now()->addMinutes(self::CACHE_DURATION), function () use ($days) {
            return Contract::where('status', 'active')
                ->whereBetween('end', [now(), now()->addDays($days)])
                ->with(['tenant.user', 'units', 'ownership'])
                ->orderBy('end', 'asc')
                ->get()
                ->map(function ($contract) {
                    return [
                        'id' => $contract->id,
                        'uuid' => $contract->uuid,
                        'number' => $contract->number,
                        'tenant_name' => $contract->tenant->user->name ?? 'N/A',
                        'unit_number' => $contract->primaryUnit()?->number ?? 'N/A',
                        'ownership_name' => $contract->ownership->name ?? 'N/A',
                        'end_date' => $contract->end->format('Y-m-d'),
                        'days_remaining' => now()->diffInDays($contract->end, false),
                        'base_rent' => (float) ($contract->base_rent ?? 0),
                    ];
                })
                ->toArray();
        });
    }

    /**
     * Get system-wide overdue invoices
     */
    public function getSystemOverdueInvoices(): array
    {
        $cacheKey = "system_overdue_invoices";

        return Cache::remember($cacheKey, now()->addMinutes(self::CACHE_DURATION), function () {
            return Invoice::where('status', 'overdue')
                ->with(['contract.tenant.user', 'contract.units', 'contract.ownership'])
                ->orderBy('due', 'asc')
                ->get()
                ->map(function ($invoice) {
                    return [
                        'id' => $invoice->id,
                        'uuid' => $invoice->uuid,
                        'number' => $invoice->number,
                        'tenant_name' => $invoice->contract->tenant->user->name ?? 'N/A',
                        'unit_number' => $invoice->contract->unit()?->number ?? 'N/A',
                        'ownership_name' => $invoice->contract->ownership->name ?? 'N/A',
                        'due_date' => $invoice->due->format('Y-m-d'),
                        'days_overdue' => now()->diffInDays($invoice->due, false),
                        'total' => (float) $invoice->total,
                        'amount' => (float) $invoice->amount,
                    ];
                })
                ->toArray();
        });
    }

    /**
     * Get system-wide employment distribution
     */
    public function getSystemEmploymentDistribution(): array
    {
        $cacheKey = "system_employment_distribution";

        return Cache::remember($cacheKey, now()->addMinutes(self::CACHE_DURATION), function () {
            $employment = Tenant::select('employment', DB::raw('count(*) as count'))
                ->groupBy('employment')
                ->get()
                ->mapWithKeys(function ($item) {
                    return [$item->employment => $item->count];
                })
                ->toArray();

            $total = array_sum($employment);
            $avgIncome = Tenant::whereNotNull('income')->avg('income');

            return [
                'employed' => [
                    'count' => $employment['employed'] ?? 0,
                    'percentage' => $total > 0 ? round((($employment['employed'] ?? 0) / $total) * 100, 2) : 0,
                ],
                'self_employed' => [
                    'count' => $employment['self_employed'] ?? 0,
                    'percentage' => $total > 0 ? round((($employment['self_employed'] ?? 0) / $total) * 100, 2) : 0,
                ],
                'unemployed' => [
                    'count' => $employment['unemployed'] ?? 0,
                    'percentage' => $total > 0 ? round((($employment['unemployed'] ?? 0) / $total) * 100, 2) : 0,
                ],
                'retired' => [
                    'count' => $employment['retired'] ?? 0,
                    'percentage' => $total > 0 ? round((($employment['retired'] ?? 0) / $total) * 100, 2) : 0,
                ],
                'student' => [
                    'count' => $employment['student'] ?? 0,
                    'percentage' => $total > 0 ? round((($employment['student'] ?? 0) / $total) * 100, 2) : 0,
                ],
                'total' => $total,
                'average_income' => round((float) $avgIncome, 2),
            ];
        });
    }

    /**
     * Get system-wide contracts financial summary
     */
    public function getSystemContractsFinancial(): array
    {
        $cacheKey = "system_contracts_financial";

        return Cache::remember($cacheKey, now()->addMinutes(self::CACHE_DURATION), function () {
            $activeContracts = Contract::where('status', 'active')->get();

            $totalRent = $activeContracts->sum('total_rent');
            $avgRent = $activeContracts->avg('total_rent');
            $minRent = $activeContracts->min('total_rent');
            $maxRent = $activeContracts->max('total_rent');

            $depositStatus = Contract::select('deposit_status', DB::raw('sum(deposit) as total'))
                ->groupBy('deposit_status')
                ->get()
                ->mapWithKeys(function ($item) {
                    return [$item->deposit_status => (float) $item->total];
                })
                ->toArray();

            $paymentFrequency = Contract::where('status', 'active')
                ->select('payment_frequency', DB::raw('count(*) as count'))
                ->groupBy('payment_frequency')
                ->get()
                ->mapWithKeys(function ($item) {
                    return [$item->payment_frequency => $item->count];
                })
                ->toArray();

            return [
                'total_rent' => round($totalRent, 2),
                'average_rent' => round($avgRent, 2),
                'min_rent' => round($minRent, 2),
                'max_rent' => round($maxRent, 2),
                'deposits' => [
                    'pending' => $depositStatus['pending'] ?? 0,
                    'paid' => $depositStatus['paid'] ?? 0,
                    'refunded' => $depositStatus['refunded'] ?? 0,
                    'forfeited' => $depositStatus['forfeited'] ?? 0,
                    'total' => array_sum($depositStatus),
                ],
                'payment_frequency' => [
                    'monthly' => $paymentFrequency['monthly'] ?? 0,
                    'quarterly' => $paymentFrequency['quarterly'] ?? 0,
                    'yearly' => $paymentFrequency['yearly'] ?? 0,
                    'weekly' => $paymentFrequency['weekly'] ?? 0,
                ],
            ];
        });
    }

    /**
     * Get system-wide revenue by period
     */
    public function getSystemRevenueByPeriod(string $period = 'monthly', int $count = 12): array
    {
        $cacheKey = "system_revenue_period_{$period}_{$count}";

        return Cache::remember($cacheKey, now()->addMinutes(self::CACHE_DURATION), function () use ($period, $count) {
            $data = [];
            $now = now();

            for ($i = 0; $i < $count; $i++) {
                if ($period === 'daily') {
                    $periodStart = $now->copy()->subDays($count - $i - 1)->startOfDay();
                    $periodEnd = $periodStart->copy()->endOfDay();
                    $label = $periodStart->format('Y-m-d');
                } elseif ($period === 'weekly') {
                    $periodStart = $now->copy()->subWeeks($count - $i - 1)->startOfWeek();
                    $periodEnd = $periodStart->copy()->endOfWeek();
                    $label = $periodStart->format('Y-m-d') . ' to ' . $periodEnd->format('Y-m-d');
                } else { // monthly
                    $periodStart = $now->copy()->subMonths($count - $i - 1)->startOfMonth();
                    $periodEnd = $periodStart->copy()->endOfMonth();
                    $label = $periodStart->format('F Y');
                }

                // Revenue = Actual payments received
                $revenue = Payment::where('status', 'paid')
                    ->whereBetween('paid_at', [$periodStart, $periodEnd])
                    ->sum('amount');

                // Receivables = Invoices total (what is owed)
                $receivables = Invoice::whereBetween('period_start', [$periodStart, $periodEnd])
                    ->sum('total');

                $paidReceivables = Invoice::where('status', 'paid')
                    ->whereBetween('paid_at', [$periodStart, $periodEnd])
                    ->sum('total');

                $invoices = Invoice::whereBetween('period_start', [$periodStart, $periodEnd])->count();
                $paidInvoices = Invoice::where('status', 'paid')
                    ->whereBetween('paid_at', [$periodStart, $periodEnd])
                    ->count();

                $data[] = [
                    'period' => $label,
                    'period_start' => $periodStart->format('Y-m-d'),
                    'period_end' => $periodEnd->format('Y-m-d'),
                    'revenue' => (float) $revenue,
                    'receivables' => (float) $receivables,
                    'paid_receivables' => (float) $paidReceivables,
                    'invoices' => $invoices,
                    'paid_invoices' => $paidInvoices,
                    'collection_rate' => $receivables > 0 ? round(($paidReceivables / $receivables) * 100, 2) : 0,
                ];
            }

            return $data;
        });
    }

    /**
     * Get system-wide monthly revenue
     */
    public function getSystemMonthlyRevenue(int $months = 12): array
    {
        $cacheKey = "system_monthly_revenue_{$months}";

        return Cache::remember($cacheKey, now()->addMinutes(self::CACHE_DURATION), function () use ($months) {
            $data = [];
            $startDate = now()->subMonths($months - 1)->startOfMonth();

            for ($i = 0; $i < $months; $i++) {
                $monthStart = $startDate->copy()->addMonths($i)->startOfMonth();
                $monthEnd = $monthStart->copy()->endOfMonth();

                // Revenue = Actual payments received
                $revenue = Payment::where('status', 'paid')
                    ->whereBetween('paid_at', [$monthStart, $monthEnd])
                    ->sum('amount');

                // Receivables = Invoices total (what is owed)
                $receivables = Invoice::whereBetween('period_start', [$monthStart, $monthEnd])
                    ->sum('total');

                $paidReceivables = Invoice::where('status', 'paid')
                    ->whereBetween('paid_at', [$monthStart, $monthEnd])
                    ->sum('total');

                $data[] = [
                    'month' => $monthStart->format('Y-m'),
                    'month_name' => $monthStart->format('F Y'),
                    'revenue' => (float) $revenue,
                    'receivables' => (float) $receivables,
                    'paid_receivables' => (float) $paidReceivables,
                ];
            }

            return $data;
        });
    }

    /**
     * Get system-wide top tenants
     */
    public function getSystemTopTenants(int $limit = 10): array
    {
        $cacheKey = "system_top_tenants_{$limit}";

        return Cache::remember($cacheKey, now()->addMinutes(self::CACHE_DURATION), function () use ($limit) {
            return Tenant::with(['user', 'contracts.invoices', 'ownership'])
                ->get()
                ->map(function ($tenant) {
                    $allInvoices = $tenant->contracts->flatMap->invoices;
                    $totalInvoices = $allInvoices->count();
                    $paidInvoices = $allInvoices->where('status', 'paid')->count();
                    $totalPaid = $allInvoices->where('status', 'paid')->sum('total');
                    $onTimePayments = $allInvoices
                        ->where('status', 'paid')
                        ->filter(function ($invoice) {
                            if (!$invoice->paid_at) {
                                return false;
                            }
                            $paidAt = $invoice->paid_at instanceof Carbon ? $invoice->paid_at : Carbon::parse($invoice->paid_at);
                            $due = $invoice->due instanceof Carbon ? $invoice->due : Carbon::parse($invoice->due);
                            return $paidAt->lte($due);
                        })
                        ->count();

                    return [
                        'tenant_id' => $tenant->id,
                        'tenant_name' => $tenant->user->name ?? 'N/A',
                        'ownership_name' => $tenant->ownership->name ?? 'N/A',
                        'rating' => $tenant->rating,
                        'total_invoices' => $totalInvoices,
                        'paid_invoices' => $paidInvoices,
                        'payment_rate' => $totalInvoices > 0 ? round(($paidInvoices / $totalInvoices) * 100, 2) : 0,
                        'on_time_rate' => $paidInvoices > 0 ? round(($onTimePayments / $paidInvoices) * 100, 2) : 0,
                        'total_paid' => round($totalPaid, 2),
                    ];
                })
                ->sortByDesc('payment_rate')
                ->take($limit)
                ->values()
                ->toArray();
        });
    }
}


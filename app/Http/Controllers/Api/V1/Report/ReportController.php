<?php

namespace App\Http\Controllers\Api\V1\Report;

use App\Http\Controllers\Controller;
use App\Policies\V1\Report\ReportPolicy;
use App\Services\V1\Report\ReportService;
use App\Traits\HasLocalizedResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class ReportController extends Controller
{
    use HasLocalizedResponse;
    public function __construct(
        private ReportService $reportService
    ) {}

    /**
     * Get ownership ID for reports.
     * Super Admin can specify ownership_id in request or get system-wide data (null).
     * Non-Super Admin must have ownership scope from middleware.
     */
    private function getOwnershipIdForReport(Request $request, bool $validate = false): ?int
    {
        $user = $request->user();
        $isSuperAdmin = $user->isSuperAdmin();
        
        // Super Admin can specify ownership_id in request
        if ($isSuperAdmin && $request->has('ownership_id')) {
            if ($validate) {
                $validated = $request->validate([
                    'ownership_id' => ['nullable', 'integer', 'exists:ownerships,id'],
                ]);
                return $validated['ownership_id'] ?? null;
            }
            $ownershipId = $request->input('ownership_id');
            if ($ownershipId && is_numeric($ownershipId)) {
                return (int) $ownershipId;
            }
        }
        
        // For Super Admin without ownership_id, return null (system-wide)
        if ($isSuperAdmin) {
            return null;
        }
        
        // Non-Super Admin: get from middleware
        return $request->input('current_ownership_id');
    }

    /**
     * Check if ownership is required and user has it.
     */
    private function requireOwnership(Request $request): ?JsonResponse
    {
        $user = $request->user();
        $isSuperAdmin = $user->isSuperAdmin();
        $ownershipId = $this->getOwnershipIdForReport($request);
        
        // Non-Super Admin must have ownership scope
        if (!$isSuperAdmin && !$ownershipId) {
            return $this->errorResponse('messages.errors.ownership_required', 400);
        }
        
        return null;
    }

    /**
     * Get dashboard overview
     * 
     * GET /api/v1/reports/dashboard
     */
    public function dashboard(Request $request): JsonResponse
    {
        $policy = new ReportPolicy();
        if (!$policy->viewAny($request->user())) {
            return $this->forbiddenResponse('messages.errors.permission_denied');
        }

        $user = $request->user();
        $isSuperAdmin = $user->isSuperAdmin();
        
        // Super Admin can view system-wide reports (ownership_id is optional)
        // Non-Super Admin must have ownership scope
        $ownershipId = $request->input('current_ownership_id');
        if (!$isSuperAdmin && !$ownershipId) {
            return $this->errorResponse('messages.errors.ownership_required', 400);
        }

        $validated = $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'ownership_id' => ['nullable', 'integer', 'exists:ownerships,id'], // Optional for Super Admin
        ]);

        // Super Admin can specify ownership_id in request, otherwise use system-wide
        if ($isSuperAdmin && isset($validated['ownership_id'])) {
            $ownershipId = $validated['ownership_id'];
        }

        $startDate = isset($validated['start_date']) 
            ? Carbon::parse($validated['start_date']) 
            : null;
        $endDate = isset($validated['end_date']) 
            ? Carbon::parse($validated['end_date']) 
            : null;

        // Super Admin without ownership_id gets system-wide data
        if ($isSuperAdmin && !$ownershipId) {
            $data = $this->reportService->getSystemDashboardOverview($startDate, $endDate);
        } else {
            $data = $this->reportService->getDashboardOverview($ownershipId, $startDate, $endDate);
        }

        return $this->successResponse($data);
    }

    /**
     * Get tenants summary
     * 
     * GET /api/v1/reports/tenants/summary
     */
    public function tenantsSummary(Request $request): JsonResponse
    {
        $policy = new ReportPolicy();
        if (!$policy->viewTenants($request->user())) {
            abort(403, 'Unauthorized action.');
        }

        $user = $request->user();
        $isSuperAdmin = $user->isSuperAdmin();
        
        $ownershipId = $request->input('current_ownership_id');
        $validated = $request->validate([
            'ownership_id' => ['nullable', 'integer', 'exists:ownerships,id'], // Optional for Super Admin
        ]);

        // Super Admin can specify ownership_id in request
        if ($isSuperAdmin && isset($validated['ownership_id'])) {
            $ownershipId = $validated['ownership_id'];
        }

        if (!$isSuperAdmin && !$ownershipId) {
            return $this->errorResponse('messages.errors.ownership_required', 400);
        }

        // Super Admin without ownership_id gets system-wide data
        if ($isSuperAdmin && !$ownershipId) {
            $data = $this->reportService->getSystemTenantsSummary();
        } else {
            $data = $this->reportService->getTenantsSummary($ownershipId);
        }

        return $this->successResponse($data);
    }

    /**
     * Get tenants ratings distribution
     * 
     * GET /api/v1/reports/tenants/ratings
     */
    public function tenantsRatings(Request $request): JsonResponse
    {
        $policy = new ReportPolicy();
        if (!$policy->viewTenants($request->user())) {
            abort(403, 'Unauthorized action.');
        }

        $user = $request->user();
        $isSuperAdmin = $user->isSuperAdmin();
        $ownershipId = $this->getOwnershipIdForReport($request);

        if (!$isSuperAdmin && !$ownershipId) {
            return $this->errorResponse('messages.errors.ownership_required', 400);
        }

        if ($isSuperAdmin && !$ownershipId) {
            $data = $this->reportService->getSystemTenantsRatings();
        } else {
            $data = $this->reportService->getTenantsRatings($ownershipId);
        }

        return $this->successResponse($data);
    }

    /**
     * Get contracts summary
     * 
     * GET /api/v1/reports/contracts/summary
     */
    public function contractsSummary(Request $request): JsonResponse
    {
        $policy = new ReportPolicy();
        if (!$policy->viewContracts($request->user())) {
            abort(403, 'Unauthorized action.');
        }

        $user = $request->user();
        $isSuperAdmin = $user->isSuperAdmin();
        $ownershipId = $this->getOwnershipIdForReport($request);

        if (!$isSuperAdmin && !$ownershipId) {
            return $this->errorResponse('messages.errors.ownership_required', 400);
        }

        if ($isSuperAdmin && !$ownershipId) {
            $data = $this->reportService->getSystemContractsSummary();
        } else {
            $data = $this->reportService->getContractsSummary($ownershipId);
        }

        return $this->successResponse($data);
    }

    /**
     * Get contracts status distribution
     * 
     * GET /api/v1/reports/contracts/status
     */
    public function contractsStatus(Request $request): JsonResponse
    {
        $policy = new ReportPolicy();
        if (!$policy->viewContracts($request->user())) {
            abort(403, 'Unauthorized action.');
        }

        $user = $request->user();
        $isSuperAdmin = $user->isSuperAdmin();
        $ownershipId = $this->getOwnershipIdForReport($request);

        if (!$isSuperAdmin && !$ownershipId) {
            return $this->errorResponse('messages.errors.ownership_required', 400);
        }

        if ($isSuperAdmin && !$ownershipId) {
            $data = $this->reportService->getSystemContractsStatus();
        } else {
            $data = $this->reportService->getContractsStatus($ownershipId);
        }

        return $this->successResponse($data);
    }

    /**
     * Get expiring contracts
     * 
     * GET /api/v1/reports/contracts/expiring
     */
    public function expiringContracts(Request $request): JsonResponse
    {
        $policy = new ReportPolicy();
        if (!$policy->viewContracts($request->user())) {
            abort(403, 'Unauthorized action.');
        }

        $user = $request->user();
        $isSuperAdmin = $user->isSuperAdmin();
        $ownershipId = $this->getOwnershipIdForReport($request);

        if (!$isSuperAdmin && !$ownershipId) {
            return $this->errorResponse('messages.errors.ownership_required', 400);
        }

        $validated = $request->validate([
            'days' => ['nullable', 'integer', 'min:1', 'max:365'],
        ]);

        $days = $validated['days'] ?? 30;

        if ($isSuperAdmin && !$ownershipId) {
            $data = $this->reportService->getSystemExpiringContracts($days);
        } else {
            $data = $this->reportService->getExpiringContracts($ownershipId, $days);
        }

        return $this->successResponse($data);
    }

    /**
     * Get invoices summary
     * 
     * GET /api/v1/reports/invoices/summary
     */
    public function invoicesSummary(Request $request): JsonResponse
    {
        $policy = new ReportPolicy();
        if (!$policy->viewInvoices($request->user())) {
            abort(403, 'Unauthorized action.');
        }

        $user = $request->user();
        $isSuperAdmin = $user->isSuperAdmin();
        $ownershipId = $this->getOwnershipIdForReport($request);

        if (!$isSuperAdmin && !$ownershipId) {
            return $this->errorResponse('messages.errors.ownership_required', 400);
        }

        $validated = $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        $startDate = isset($validated['start_date']) 
            ? Carbon::parse($validated['start_date']) 
            : now()->startOfMonth();
        $endDate = isset($validated['end_date']) 
            ? Carbon::parse($validated['end_date']) 
            : now()->endOfMonth();

        if ($isSuperAdmin && !$ownershipId) {
            $data = $this->reportService->getSystemInvoicesSummary($startDate, $endDate);
        } else {
            $data = $this->reportService->getInvoicesSummary($ownershipId, $startDate, $endDate);
        }

        return $this->successResponse($data);
    }

    /**
     * Get invoices status distribution
     * 
     * GET /api/v1/reports/invoices/status
     */
    public function invoicesStatus(Request $request): JsonResponse
    {
        $policy = new ReportPolicy();
        if (!$policy->viewInvoices($request->user())) {
            abort(403, 'Unauthorized action.');
        }

        $user = $request->user();
        $isSuperAdmin = $user->isSuperAdmin();
        $ownershipId = $this->getOwnershipIdForReport($request);

        if (!$isSuperAdmin && !$ownershipId) {
            return $this->errorResponse('messages.errors.ownership_required', 400);
        }

        $validated = $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        $startDate = isset($validated['start_date']) 
            ? Carbon::parse($validated['start_date']) 
            : null;
        $endDate = isset($validated['end_date']) 
            ? Carbon::parse($validated['end_date']) 
            : null;

        if ($isSuperAdmin && !$ownershipId) {
            $data = $this->reportService->getSystemInvoicesStatus($startDate, $endDate);
        } else {
            $data = $this->reportService->getInvoicesStatus($ownershipId, $startDate, $endDate);
        }

        return $this->successResponse($data);
    }

    /**
     * Get overdue invoices
     * 
     * GET /api/v1/reports/invoices/overdue
     */
    public function overdueInvoices(Request $request): JsonResponse
    {
        $policy = new ReportPolicy();
        if (!$policy->viewInvoices($request->user())) {
            abort(403, 'Unauthorized action.');
        }

        $user = $request->user();
        $isSuperAdmin = $user->isSuperAdmin();
        $ownershipId = $this->getOwnershipIdForReport($request);

        if (!$isSuperAdmin && !$ownershipId) {
            return $this->errorResponse('messages.errors.ownership_required', 400);
        }

        if ($isSuperAdmin && !$ownershipId) {
            $data = $this->reportService->getSystemOverdueInvoices();
        } else {
            $data = $this->reportService->getOverdueInvoices($ownershipId);
        }

        return $this->successResponse($data);
    }

    /**
     * Get monthly revenue
     * 
     * GET /api/v1/reports/revenue/monthly
     */
    public function monthlyRevenue(Request $request): JsonResponse
    {
        $policy = new ReportPolicy();
        if (!$policy->viewInvoices($request->user())) {
            abort(403, 'Unauthorized action.');
        }

        $user = $request->user();
        $isSuperAdmin = $user->isSuperAdmin();
        $ownershipId = $this->getOwnershipIdForReport($request);

        if (!$isSuperAdmin && !$ownershipId) {
            return $this->errorResponse('messages.errors.ownership_required', 400);
        }

        $validated = $request->validate([
            'months' => ['nullable', 'integer', 'min:1', 'max:24'],
        ]);

        $months = $validated['months'] ?? 12;

        if ($isSuperAdmin && !$ownershipId) {
            $data = $this->reportService->getSystemMonthlyRevenue($months);
        } else {
            $data = $this->reportService->getMonthlyRevenue($ownershipId, $months);
        }

        return $this->successResponse($data);
    }

    /**
     * Get payments summary
     * 
     * GET /api/v1/reports/payments/summary
     */
    public function paymentsSummary(Request $request): JsonResponse
    {
        $policy = new ReportPolicy();
        if (!$policy->viewPayments($request->user())) {
            abort(403, 'Unauthorized action.');
        }

        $user = $request->user();
        $isSuperAdmin = $user->isSuperAdmin();
        $ownershipId = $this->getOwnershipIdForReport($request);

        if (!$isSuperAdmin && !$ownershipId) {
            return $this->errorResponse('messages.errors.ownership_required', 400);
        }

        $validated = $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        $startDate = isset($validated['start_date']) 
            ? Carbon::parse($validated['start_date']) 
            : now()->startOfMonth();
        $endDate = isset($validated['end_date']) 
            ? Carbon::parse($validated['end_date']) 
            : now()->endOfMonth();

        if ($isSuperAdmin && !$ownershipId) {
            $data = $this->reportService->getSystemPaymentsSummary($startDate, $endDate);
        } else {
            $data = $this->reportService->getPaymentsSummary($ownershipId, $startDate, $endDate);
        }

        return $this->successResponse($data);
    }

    /**
     * Get payment methods distribution
     * 
     * GET /api/v1/reports/payments/methods
     */
    public function paymentMethods(Request $request): JsonResponse
    {
        $policy = new ReportPolicy();
        if (!$policy->viewPayments($request->user())) {
            abort(403, 'Unauthorized action.');
        }

        $user = $request->user();
        $isSuperAdmin = $user->isSuperAdmin();
        $ownershipId = $this->getOwnershipIdForReport($request);

        if (!$isSuperAdmin && !$ownershipId) {
            return $this->errorResponse('messages.errors.ownership_required', 400);
        }

        $validated = $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        $startDate = isset($validated['start_date']) 
            ? Carbon::parse($validated['start_date']) 
            : null;
        $endDate = isset($validated['end_date']) 
            ? Carbon::parse($validated['end_date']) 
            : null;

        if ($isSuperAdmin && !$ownershipId) {
            $data = $this->reportService->getSystemPaymentMethods($startDate, $endDate);
        } else {
            $data = $this->reportService->getPaymentMethods($ownershipId, $startDate, $endDate);
        }

        return $this->successResponse($data);
    }

    /**
     * Get employment distribution
     * 
     * GET /api/v1/reports/tenants/employment
     */
    public function tenantsEmployment(Request $request): JsonResponse
    {
        $policy = new ReportPolicy();
        if (!$policy->viewTenants($request->user())) {
            abort(403, 'Unauthorized action.');
        }

        $user = $request->user();
        $isSuperAdmin = $user->isSuperAdmin();
        $ownershipId = $this->getOwnershipIdForReport($request);

        if (!$isSuperAdmin && !$ownershipId) {
            return $this->errorResponse('messages.errors.ownership_required', 400);
        }

        if ($isSuperAdmin && !$ownershipId) {
            $data = $this->reportService->getSystemEmploymentDistribution();
        } else {
            $data = $this->reportService->getEmploymentDistribution($ownershipId);
        }

        return $this->successResponse($data);
    }

    /**
     * Get contracts financial summary
     * 
     * GET /api/v1/reports/contracts/financial
     */
    public function contractsFinancial(Request $request): JsonResponse
    {
        $policy = new ReportPolicy();
        if (!$policy->viewContracts($request->user())) {
            abort(403, 'Unauthorized action.');
        }

        $user = $request->user();
        $isSuperAdmin = $user->isSuperAdmin();
        $ownershipId = $this->getOwnershipIdForReport($request);

        if (!$isSuperAdmin && !$ownershipId) {
            return $this->errorResponse('messages.errors.ownership_required', 400);
        }

        if ($isSuperAdmin && !$ownershipId) {
            $data = $this->reportService->getSystemContractsFinancial();
        } else {
            $data = $this->reportService->getContractsFinancial($ownershipId);
        }

        return $this->successResponse($data);
    }

    /**
     * Get revenue by period
     * 
     * GET /api/v1/reports/revenue/period
     */
    public function revenueByPeriod(Request $request): JsonResponse
    {
        $policy = new ReportPolicy();
        if (!$policy->viewInvoices($request->user())) {
            abort(403, 'Unauthorized action.');
        }

        $user = $request->user();
        $isSuperAdmin = $user->isSuperAdmin();
        $ownershipId = $this->getOwnershipIdForReport($request);

        if (!$isSuperAdmin && !$ownershipId) {
            return $this->errorResponse('messages.errors.ownership_required', 400);
        }

        $validated = $request->validate([
            'period' => ['nullable', 'string', Rule::in(['daily', 'weekly', 'monthly'])],
            'count' => ['nullable', 'integer', 'min:1', 'max:365'],
        ]);

        $period = $validated['period'] ?? 'monthly';
        $count = $validated['count'] ?? 12;

        if ($isSuperAdmin && !$ownershipId) {
            $data = $this->reportService->getSystemRevenueByPeriod($period, $count);
        } else {
            $data = $this->reportService->getRevenueByPeriod($ownershipId, $period, $count);
        }

        return $this->successResponse($data);
    }

    /**
     * Get top tenants
     * 
     * GET /api/v1/reports/tenants/top
     */
    public function topTenants(Request $request): JsonResponse
    {
        $policy = new ReportPolicy();
        if (!$policy->viewTenants($request->user())) {
            abort(403, 'Unauthorized action.');
        }

        $user = $request->user();
        $isSuperAdmin = $user->isSuperAdmin();
        $ownershipId = $this->getOwnershipIdForReport($request);

        if (!$isSuperAdmin && !$ownershipId) {
            return $this->errorResponse('messages.errors.ownership_required', 400);
        }

        $validated = $request->validate([
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $limit = $validated['limit'] ?? 10;

        if ($isSuperAdmin && !$ownershipId) {
            $data = $this->reportService->getSystemTopTenants($limit);
        } else {
            $data = $this->reportService->getTopTenants($ownershipId, $limit);
        }

        return $this->successResponse($data);
    }

    /**
     * Clear reports cache
     * 
     * POST /api/v1/reports/cache/clear
     */
    public function clearCache(Request $request): JsonResponse
    {
        $user = $request->user();
        $isSuperAdmin = $user->isSuperAdmin();
        $ownershipId = $this->getOwnershipIdForReport($request);

        // Super Admin can clear all cache or specific ownership
        if ($isSuperAdmin && !$ownershipId) {
            $this->reportService->clearSystemCache();
            $message = 'System-wide reports cache cleared successfully.';
        } else {
            if (!$isSuperAdmin && !$ownershipId) {
                return $this->errorResponse('messages.errors.ownership_required', 400);
            }
            $this->reportService->clearCache($ownershipId);
            $message = 'Reports cache cleared successfully.';
        }

        return $this->successResponse(null, null, 200, ['message' => $message]);
    }
}


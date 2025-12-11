<?php

namespace App\Providers;

use App\Repositories\V1\Auth\Interfaces\PermissionRepositoryInterface;
use App\Repositories\V1\Auth\Interfaces\RoleRepositoryInterface;
use App\Repositories\V1\Auth\Interfaces\UserRepositoryInterface;
use App\Repositories\V1\Auth\PermissionRepository;
use App\Repositories\V1\Auth\RoleRepository;
use App\Repositories\V1\Auth\UserRepository;
use App\Repositories\V1\Notification\Interfaces\NotificationRepositoryInterface;
use App\Repositories\V1\Notification\NotificationRepository;
use App\Repositories\V1\Ownership\Interfaces\BuildingFloorRepositoryInterface;
use App\Repositories\V1\Ownership\Interfaces\BuildingRepositoryInterface;
use App\Repositories\V1\Ownership\Interfaces\OwnershipBoardMemberRepositoryInterface;
use App\Repositories\V1\Ownership\Interfaces\OwnershipRepositoryInterface;
use App\Repositories\V1\Ownership\Interfaces\PortfolioRepositoryInterface;
use App\Repositories\V1\Ownership\Interfaces\UnitRepositoryInterface;
use App\Repositories\V1\Ownership\Interfaces\UserOwnershipMappingRepositoryInterface;
use App\Repositories\V1\Ownership\BuildingFloorRepository;
use App\Repositories\V1\Ownership\BuildingRepository;
use App\Repositories\V1\Ownership\OwnershipBoardMemberRepository;
use App\Repositories\V1\Ownership\OwnershipRepository;
use App\Repositories\V1\Ownership\PortfolioRepository;
use App\Repositories\V1\Ownership\UnitRepository;
use App\Repositories\V1\Ownership\UserOwnershipMappingRepository;
use App\Repositories\V1\Tenant\Interfaces\TenantRepositoryInterface;
use App\Repositories\V1\Tenant\TenantRepository;
use App\Repositories\V1\Contract\Interfaces\ContractRepositoryInterface;
use App\Repositories\V1\Contract\Interfaces\ContractTermRepositoryInterface;
use App\Repositories\V1\Contract\ContractRepository;
use App\Repositories\V1\Contract\ContractTermRepository;
use App\Repositories\V1\Invoice\Interfaces\InvoiceRepositoryInterface;
use App\Repositories\V1\Invoice\Interfaces\InvoiceItemRepositoryInterface;
use App\Repositories\V1\Invoice\InvoiceRepository;
use App\Repositories\V1\Invoice\InvoiceItemRepository;
use App\Repositories\V1\Payment\Interfaces\PaymentRepositoryInterface;
use App\Repositories\V1\Payment\PaymentRepository;
use App\Repositories\V1\Setting\Interfaces\SystemSettingRepositoryInterface;
use App\Repositories\V1\Setting\SystemSettingRepository;
use App\Repositories\V1\Media\Interfaces\MediaFileRepositoryInterface;
use App\Repositories\V1\Media\MediaFileRepository;
use App\Repositories\V1\Document\Interfaces\DocumentRepositoryInterface;
use App\Repositories\V1\Document\DocumentRepository;
use App\Models\V1\Tenant\Tenant;
use App\Models\V1\Contract\Contract;
use App\Models\V1\Invoice\Invoice;
use App\Models\V1\Payment\Payment;
use App\Models\V1\Setting\SystemSetting;
use App\Models\V1\Media\MediaFile;
use App\Models\V1\Document\Document;
use App\Policies\V1\Tenant\TenantPolicy;
use App\Policies\V1\Contract\ContractPolicy;
use App\Policies\V1\Invoice\InvoicePolicy;
use App\Policies\V1\Payment\PaymentPolicy;
use App\Policies\V1\Report\ReportPolicy;
use App\Policies\V1\Setting\SystemSettingPolicy;
use App\Policies\V1\Media\MediaFilePolicy;
use App\Policies\V1\Document\DocumentPolicy;
use App\Services\V1\Auth\UserService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // V1 Auth Module
        $this->app->bind(
            UserRepositoryInterface::class,
            UserRepository::class
        );

        $this->app->bind(
            RoleRepositoryInterface::class,
            RoleRepository::class
        );

        $this->app->bind(
            PermissionRepositoryInterface::class,
            PermissionRepository::class
        );

        // V1 Notification Module
        $this->app->bind(
            NotificationRepositoryInterface::class,
            NotificationRepository::class
        );

        // V1 Ownership Module
        $this->app->bind(
            OwnershipRepositoryInterface::class,
            OwnershipRepository::class
        );

        $this->app->bind(
            OwnershipBoardMemberRepositoryInterface::class,
            OwnershipBoardMemberRepository::class
        );

        $this->app->bind(
            UserOwnershipMappingRepositoryInterface::class,
            UserOwnershipMappingRepository::class
        );

        $this->app->bind(
            PortfolioRepositoryInterface::class,
            PortfolioRepository::class
        );

        $this->app->bind(
            BuildingRepositoryInterface::class,
            BuildingRepository::class
        );

        $this->app->bind(
            BuildingFloorRepositoryInterface::class,
            BuildingFloorRepository::class
        );

        $this->app->bind(
            UnitRepositoryInterface::class,
            UnitRepository::class
        );

        // V1 Tenant Module
        $this->app->bind(
            TenantRepositoryInterface::class,
            TenantRepository::class
        );

        // V1 Contract Module
        $this->app->bind(
            ContractRepositoryInterface::class,
            ContractRepository::class
        );

        $this->app->bind(
            ContractTermRepositoryInterface::class,
            ContractTermRepository::class
        );

        // V1 Invoice Module
        $this->app->bind(
            InvoiceRepositoryInterface::class,
            InvoiceRepository::class
        );

        $this->app->bind(
            InvoiceItemRepositoryInterface::class,
            InvoiceItemRepository::class
        );

        // V1 Payment Module
        $this->app->bind(
            PaymentRepositoryInterface::class,
            PaymentRepository::class
        );

        // V1 Setting Module
        $this->app->bind(
            SystemSettingRepositoryInterface::class,
            SystemSettingRepository::class
        );

        // V1 Media Module
        $this->app->bind(
            MediaFileRepositoryInterface::class,
            MediaFileRepository::class
        );

        // V1 Document Module
        $this->app->bind(
            DocumentRepositoryInterface::class,
            DocumentRepository::class
        );

        // Add other V1 module bindings here
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Use custom PersonalAccessToken model with refresh token support
        Sanctum::usePersonalAccessTokenModel(\App\Models\V1\Auth\PersonalAccessToken::class);

        // Register Policies
        Gate::policy(Tenant::class, TenantPolicy::class);
        Gate::policy(Contract::class, ContractPolicy::class);
        Gate::policy(Invoice::class, InvoicePolicy::class);
        Gate::policy(Payment::class, PaymentPolicy::class);
        Gate::policy(SystemSetting::class, SystemSettingPolicy::class);
        Gate::policy(MediaFile::class, MediaFilePolicy::class);
        Gate::policy(Document::class, DocumentPolicy::class);
    }
}

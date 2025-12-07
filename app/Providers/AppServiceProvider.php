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
use App\Services\V1\Auth\UserService;
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

        // Add other V1 module bindings here
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Use custom PersonalAccessToken model with refresh token support
        Sanctum::usePersonalAccessTokenModel(\App\Models\V1\Auth\PersonalAccessToken::class);
    }
}

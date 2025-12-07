<?php

namespace Database\Seeders;

use Database\Seeders\V1\Auth\AuthModuleSeeder;
use Database\Seeders\V1\Notification\NotificationModuleSeeder;
use Database\Seeders\V1\Ownership\OwnershipModuleSeeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('🚀 Starting database seeding...');
        $this->command->info('');

        // Seed Auth Module (Permissions, Roles, Users)
        $this->call(AuthModuleSeeder::class);
        $this->command->info('');

        // Seed Ownership Module (Ownerships, User Mappings, Board Members)
        $this->call(OwnershipModuleSeeder::class);
        $this->command->info('');

        // Seed Notification Module (Notifications for all users)
        $this->call(NotificationModuleSeeder::class);

        $this->command->info('');
        $this->command->info('✅ Database seeding completed!');
    }
}

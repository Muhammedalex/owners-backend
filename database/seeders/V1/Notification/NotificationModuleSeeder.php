<?php

namespace Database\Seeders\V1\Notification;

use Illuminate\Database\Seeder;

class NotificationModuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * This seeder runs all notification module seeders.
     */
    public function run(): void
    {
        $this->command->info('Starting Notification Module seeding...');
        $this->command->info('');

        // Seed Notifications
        $this->command->info('Seeding notifications...');
        $this->call(NotificationSeeder::class);
        $this->command->info('');

        $this->command->info('✅ Notification Module seeding completed successfully!');
    }
}


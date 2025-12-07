<?php

namespace Database\Seeders\V1\Notification;

use App\Models\V1\Auth\User;
use App\Repositories\V1\Notification\Interfaces\NotificationRepositoryInterface;
use Illuminate\Database\Seeder;

class NotificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $notificationRepository = app(NotificationRepositoryInterface::class);
        
        // Get all users
        $users = User::all();
        
        if ($users->isEmpty()) {
            $this->command->warn('No users found. Please seed users first.');
            return;
        }

        $this->command->info("Creating notifications for {$users->count()} users...");

        // Sample notification templates
        $notificationTemplates = [
            // Welcome notifications
            [
                'type' => 'success',
                'title' => 'Welcome to Owners System!',
                'message' => 'Thank you for joining us. We\'re excited to have you on board.',
                'category' => 'system',
                'priority' => 0,
                'icon' => 'check-circle',
                'read' => false,
            ],
            [
                'type' => 'info',
                'title' => 'Getting Started',
                'message' => 'Complete your profile to get the most out of the system.',
                'category' => 'system',
                'priority' => 0,
                'icon' => 'info-circle',
                'action_url' => '/profile',
                'action_text' => 'Complete Profile',
                'read' => false,
            ],
            
            // System notifications
            [
                'type' => 'info',
                'title' => 'System Update',
                'message' => 'We\'ve released new features. Check them out!',
                'category' => 'system',
                'priority' => 0,
                'icon' => 'bell',
                'read' => false,
            ],
            [
                'type' => 'warning',
                'title' => 'Maintenance Scheduled',
                'message' => 'System maintenance is scheduled for tomorrow at 2 AM.',
                'category' => 'system',
                'priority' => 1,
                'icon' => 'exclamation-triangle',
                'read' => false,
            ],
            
            // Account notifications
            [
                'type' => 'success',
                'title' => 'Email Verified',
                'message' => 'Your email address has been successfully verified.',
                'category' => 'account',
                'priority' => 0,
                'icon' => 'check-circle',
                'read' => true,
                'read_at' => now()->subDays(2),
            ],
            [
                'type' => 'info',
                'title' => 'Profile Updated',
                'message' => 'Your profile information has been updated successfully.',
                'category' => 'account',
                'priority' => 0,
                'icon' => 'user',
                'read' => true,
                'read_at' => now()->subDays(1),
            ],
            
            // Order/Transaction notifications (for future use)
            [
                'type' => 'success',
                'title' => 'Payment Received',
                'message' => 'Your payment of $1,500.00 has been received successfully.',
                'category' => 'payment',
                'priority' => 1,
                'icon' => 'credit-card',
                'data' => ['amount' => 1500.00, 'currency' => 'USD'],
                'action_url' => '/payments/12345',
                'action_text' => 'View Payment',
                'read' => false,
            ],
            [
                'type' => 'info',
                'title' => 'New Order',
                'message' => 'You have received a new order #12345.',
                'category' => 'orders',
                'priority' => 1,
                'icon' => 'shopping-cart',
                'data' => ['order_id' => 12345],
                'action_url' => '/orders/12345',
                'action_text' => 'View Order',
                'read' => false,
            ],
            
            // Security notifications
            [
                'type' => 'warning',
                'title' => 'New Login Detected',
                'message' => 'A new device logged into your account from a new location.',
                'category' => 'security',
                'priority' => 2,
                'icon' => 'shield',
                'data' => ['ip' => '192.168.1.1', 'device' => 'Chrome on Windows'],
                'read' => false,
            ],
            [
                'type' => 'error',
                'title' => 'Failed Login Attempt',
                'message' => 'Someone tried to access your account with an incorrect password.',
                'category' => 'security',
                'priority' => 2,
                'icon' => 'lock',
                'read' => false,
            ],
            
            // Reminder notifications
            [
                'type' => 'info',
                'title' => 'Reminder: Meeting Tomorrow',
                'message' => 'You have a meeting scheduled for tomorrow at 10:00 AM.',
                'category' => 'reminders',
                'priority' => 0,
                'icon' => 'calendar',
                'read' => false,
            ],
            [
                'type' => 'warning',
                'title' => 'Document Expiring Soon',
                'message' => 'Your document will expire in 7 days. Please renew it.',
                'category' => 'reminders',
                'priority' => 1,
                'icon' => 'file',
                'action_url' => '/documents/renew',
                'action_text' => 'Renew Now',
                'read' => false,
            ],
        ];

        $createdCount = 0;
        $readCount = 0;

        foreach ($users as $user) {
            // Create 3-8 random notifications per user
            $notificationsCount = rand(3, 8);
            $selectedTemplates = collect($notificationTemplates)->random($notificationsCount);

            foreach ($selectedTemplates as $template) {
                // Randomize creation time (within last 30 days)
                $createdAt = now()->subDays(rand(0, 30))->subHours(rand(0, 23))->subMinutes(rand(0, 59));
                
                // If notification is read, ensure read_at is after created_at
                if (isset($template['read']) && $template['read'] && isset($template['read_at'])) {
                    $readAt = $template['read_at'];
                    if ($readAt->lt($createdAt)) {
                        $readAt = $createdAt->copy()->addHours(rand(1, 24));
                    }
                } else {
                    $readAt = null;
                }

                // Randomize expiration for some notifications (10% chance)
                $expiresAt = null;
                if (rand(1, 10) === 1) {
                    $expiresAt = now()->addDays(rand(1, 30));
                }

                $notificationData = array_merge($template, [
                    'user_id' => $user->id,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                    'read_at' => $readAt,
                    'expires_at' => $expiresAt,
                ]);

                $notificationRepository->create($notificationData);
                $createdCount++;
                
                if (isset($template['read']) && $template['read']) {
                    $readCount++;
                }
            }
        }

        $this->command->info("✅ Created {$createdCount} notifications ({$readCount} read, " . ($createdCount - $readCount) . " unread)");
    }
}


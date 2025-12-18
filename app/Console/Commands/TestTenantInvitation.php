<?php

namespace App\Console\Commands;

use App\Models\V1\Auth\User;
use App\Models\V1\Ownership\Ownership;
use App\Services\V1\Tenant\TenantInvitationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TestTenantInvitation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:tenant-invitation 
                            {--email= : Email to send invitation to}
                            {--ownership= : Ownership UUID}
                            {--user= : User ID who will send invitation}
                            {--bulk : Test bulk invitations}
                            {--link : Generate link only (no email)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test tenant invitation system';

    /**
     * Execute the console command.
     */
    public function handle(TenantInvitationService $service): int
    {
        $this->info('🧪 Testing Tenant Invitation System');
        $this->newLine();

        // Get or use defaults
        $email = $this->option('email') ?? 'test@example.com';
        $ownershipUuid = $this->option('ownership');
        $userId = $this->option('user');

        // Get ownership
        if (!$ownershipUuid) {
            $ownership = Ownership::first();
            if (!$ownership) {
                $this->error('❌ No ownership found. Please create an ownership first.');
                return Command::FAILURE;
            }
            $this->info("📋 Using ownership: {$ownership->name} (UUID: {$ownership->uuid})");
        } else {
            $ownership = Ownership::where('uuid', $ownershipUuid)->first();
            if (!$ownership) {
                $this->error("❌ Ownership not found: {$ownershipUuid}");
                return Command::FAILURE;
            }
        }

        // Get user
        if (!$userId) {
            $user = User::first();
            if (!$user) {
                $this->error('❌ No user found. Please create a user first.');
                return Command::FAILURE;
            }
            $this->info("👤 Using user: {$user->email} (ID: {$user->id})");
        } else {
            $user = User::find($userId);
            if (!$user) {
                $this->error("❌ User not found: {$userId}");
                return Command::FAILURE;
            }
        }

        try {
            DB::beginTransaction();

            if ($this->option('bulk')) {
                $this->testBulkInvitations($service, $ownership->id, $user->id);
            } elseif ($this->option('link')) {
                $this->testGenerateLink($service, $ownership->id, $user->id, $email);
            } else {
                $this->testSingleInvitation($service, $ownership->id, $user->id, $email);
            }

            DB::commit();

            $this->newLine();
            $this->info('✅ Test completed successfully!');
            $this->info('📧 Check storage/logs/emails.log for email content');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('❌ Error: ' . $e->getMessage());
            $this->error('Stack trace: ' . $e->getTraceAsString());
            return Command::FAILURE;
        }
    }

    /**
     * Test single invitation.
     */
    private function testSingleInvitation(TenantInvitationService $service, int $ownershipId, int $userId, string $email): void
    {
        $this->info('📨 Testing Single Invitation...');
        $this->newLine();

        $invitation = $service->create([
            'ownership_id' => $ownershipId,
            'invited_by' => $userId,
            'email' => $email,
            'name' => 'Test Tenant',
            'expires_in_days' => 7,
            'notes' => 'Test invitation from command',
        ]);

        $this->info("✅ Invitation created:");
        $this->table(
            ['Field', 'Value'],
            [
                ['UUID', $invitation->uuid],
                ['Email', $invitation->email],
                ['Token', $invitation->token],
                ['Status', $invitation->status],
                ['Expires At', $invitation->expires_at->format('Y-m-d H:i:s')],
                ['Invitation URL', $invitation->getInvitationUrl()],
            ]
        );

        $this->info("📧 Email sent to: {$email}");
        $this->info("🔗 Registration URL: {$invitation->getInvitationUrl()}");
    }

    /**
     * Test bulk invitations.
     */
    private function testBulkInvitations(TenantInvitationService $service, int $ownershipId, int $userId): void
    {
        $this->info('📨 Testing Bulk Invitations...');
        $this->newLine();

        $invitations = $service->createBulk([
            [
                'email' => 'tenant1@example.com',
                'name' => 'Tenant One',
            ],
            [
                'email' => 'tenant2@example.com',
                'name' => 'Tenant Two',
            ],
            [
                'email' => 'tenant3@example.com',
                'name' => 'Tenant Three',
            ],
        ], $ownershipId, $userId);

        $this->info("✅ Created {$invitations->count()} invitations");
        $this->newLine();

        $tableData = [];
        foreach ($invitations as $invitation) {
            $tableData[] = [
                $invitation->uuid,
                $invitation->email,
                $invitation->status,
                $invitation->getInvitationUrl(),
            ];
        }

        $this->table(
            ['UUID', 'Email', 'Status', 'URL'],
            $tableData
        );
    }

    /**
     * Test generate link.
     */
    private function testGenerateLink(TenantInvitationService $service, int $ownershipId, int $userId, string $email): void
    {
        $this->info('🔗 Testing Generate Link (No Email)...');
        $this->newLine();

        $invitation = $service->generateLink([
            'ownership_id' => $ownershipId,
            'invited_by' => $userId,
            'email' => $email,
            'name' => 'Test Tenant',
            'expires_in_days' => 7,
        ]);

        $this->info("✅ Link generated:");
        $this->table(
            ['Field', 'Value'],
            [
                ['UUID', $invitation->uuid],
                ['Email', $invitation->email],
                ['Token', $invitation->token],
                ['Status', $invitation->status],
                ['Expires At', $invitation->expires_at->format('Y-m-d H:i:s')],
                ['Invitation URL', $invitation->getInvitationUrl()],
            ]
        );

        $this->info("🔗 Registration URL: {$invitation->getInvitationUrl()}");
        $this->warn("⚠️  No email sent (link generation mode)");
    }
}


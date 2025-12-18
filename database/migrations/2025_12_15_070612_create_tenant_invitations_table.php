<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tenant_invitations', function (Blueprint $table) {
            $table->id();
            $table->char('uuid', 36)->unique();
            $table->foreignId('ownership_id')->constrained('ownerships')->cascadeOnDelete();
            $table->foreignId('invited_by')->constrained('users')->cascadeOnDelete();
            $table->string('email', 255)->nullable(); // For email invitations
            $table->string('phone', 20)->nullable(); // For SMS invitations (future)
            $table->string('name', 255)->nullable(); // Optional pre-filled name
            $table->string('token', 64)->unique(); // Secure invitation token
            $table->string('status', 50)->default('pending'); // pending, accepted, expired, cancelled
            $table->timestamp('expires_at');
            $table->timestamp('accepted_at')->nullable();
            $table->foreignId('accepted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('uuid');
            $table->index('token');
            $table->index('email');
            $table->index('phone');
            $table->index('ownership_id');
            $table->index('invited_by');
            $table->index('status');
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_invitations');
    }
};

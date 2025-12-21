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
        Schema::create('otp_verifications', function (Blueprint $table) {
            $table->id();
            $table->string('phone', 20)->index();
            $table->string('email', 255)->nullable()->index();
            $table->string('otp', 6);
            $table->string('purpose', 50)->index(); // 'login', 'forgot_password', 'reset_password', 'phone_verification', 'email_verification'
            $table->string('session_id', 100)->nullable()->index();
            $table->timestamp('expires_at');
            $table->timestamp('verified_at')->nullable();
            $table->integer('attempts')->default(0);
            $table->timestamps();

            // Composite indexes for efficient queries
            $table->index(['phone', 'purpose', 'expires_at']);
            $table->index(['email', 'purpose', 'expires_at']);
            $table->index(['session_id', 'purpose']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('otp_verifications');
    }
};

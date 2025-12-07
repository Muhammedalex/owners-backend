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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('type'); // notification type (e.g., 'info', 'success', 'warning', 'error', 'system')
            $table->string('title');
            $table->text('message');
            $table->json('data')->nullable(); // Additional data (links, actions, etc.)
            $table->boolean('read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->string('action_url')->nullable(); // URL for action button
            $table->string('action_text')->nullable(); // Text for action button
            $table->string('icon')->nullable(); // Icon name or URL
            $table->string('category')->nullable(); // Category for grouping (e.g., 'order', 'payment', 'system')
            $table->integer('priority')->default(0); // 0 = normal, 1 = high, 2 = urgent
            $table->timestamp('expires_at')->nullable(); // Auto-expire notifications
            $table->timestamps();

            // Indexes
            $table->index(['user_id', 'read']);
            $table->index(['user_id', 'created_at']);
            $table->index(['type', 'category']);
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};

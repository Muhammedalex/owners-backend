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
        Schema::create('ownership_board_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ownership_id')->constrained('ownerships')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role', 50);
            $table->boolean('active')->default(true);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('ownership_id');
            $table->index('user_id');
            $table->index('role');
            $table->index('active');
            
            // Unique constraint: one user can have one role per ownership
            $table->unique(['ownership_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ownership_board_members');
    }
};

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
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->foreignId('ownership_id')->constrained('ownerships')->cascadeOnDelete();
            $table->string('national_id', 50)->nullable();
            $table->string('id_type', 50)->default('national_id');
            $table->string('id_document', 255)->nullable();
            $table->date('id_expiry')->nullable();
            $table->string('emergency_name', 100)->nullable();
            $table->string('emergency_phone', 20)->nullable();
            $table->string('emergency_relation', 50)->nullable();
            $table->string('employment', 50)->nullable();
            $table->string('employer', 255)->nullable();
            $table->decimal('income', 12, 2)->nullable();
            $table->string('rating', 50)->default('good');
            $table->text('notes')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('user_id');
            $table->index('ownership_id');
            $table->index('national_id');
            $table->index('id_type');
            $table->index('rating');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};

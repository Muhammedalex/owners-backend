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
        // 1. Contracts Table
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->char('uuid', 36)->unique();
            $table->foreignId('unit_id')->constrained('units')->onDelete('cascade');
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('ownership_id')->constrained('ownerships')->onDelete('cascade');
            $table->string('number', 100)->unique();
            $table->integer('version')->default(1);
            $table->foreignId('parent_id')->nullable()->constrained('contracts')->onDelete('cascade');
            $table->string('ejar_code', 100)->nullable(); // Saudi rental platform registration code (optional)
            $table->date('start');
            $table->date('end');
            $table->decimal('rent', 12, 2);
            $table->string('payment_frequency', 50)->default('monthly');
            $table->decimal('deposit', 12, 2)->nullable();
            $table->string('deposit_status', 50)->default('pending');
            $table->string('document', 255)->nullable();
            $table->text('signature')->nullable();
            $table->string('status', 50)->default('draft');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // Indexes
            $table->index('uuid');
            $table->index('unit_id');
            $table->index('tenant_id');
            $table->index('ownership_id');
            $table->index('number');
            $table->index('status');
            $table->index('start');
            $table->index('end');
            $table->index('ejar_code');
            $table->index(['unit_id', 'status']);
        });

        // 2. Contract Terms Table
        Schema::create('contract_terms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained('contracts')->onDelete('cascade');
            $table->string('key');
            $table->text('value')->nullable();
            $table->string('type', 50)->nullable();
            $table->timestamps();

            // Indexes
            $table->index('contract_id');
            $table->index('key');
            $table->unique(['contract_id', 'key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contract_terms');
        Schema::dropIfExists('contracts');
    }
};

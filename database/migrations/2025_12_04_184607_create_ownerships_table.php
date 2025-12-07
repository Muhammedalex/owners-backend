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
        Schema::create('ownerships', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name', 255);
            $table->string('legal', 255)->nullable();
            $table->string('type', 50);
            $table->string('ownership_type', 50);
            $table->string('registration', 100)->unique()->nullable();
            $table->string('tax_id', 100)->nullable();
            $table->string('street', 255)->nullable();
            $table->string('city', 100);
            $table->string('state', 100)->nullable();
            $table->string('country', 100)->default('Saudi Arabia');
            $table->string('zip_code', 20)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('phone', 20)->nullable();
            // $table->string('logo', 255)->nullable();
            $table->boolean('active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // Indexes
            $table->index('uuid');
            $table->index('type');
            $table->index('ownership_type');
            $table->index('registration');
            $table->index('active');
            $table->index('city');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ownerships');
    }
};

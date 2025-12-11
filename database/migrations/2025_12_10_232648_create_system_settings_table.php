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
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ownership_id')->nullable()->constrained('ownerships')->onDelete('cascade');
            $table->string('key', 255);
            $table->text('value')->nullable();
            $table->string('value_type', 20)->default('string'); // string, integer, decimal, boolean, json, array
            $table->string('group', 50); // financial, contract, invoice, tenant, notification, maintenance, facility, document, media, reporting, localization, security, system
            $table->text('description')->nullable();
            $table->timestamps();

            // Indexes
            $table->unique(['key', 'ownership_id'], 'unique_key_ownership');
            $table->index('ownership_id');
            $table->index('group');
            $table->index('key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};

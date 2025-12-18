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
        Schema::table('tenants', function (Blueprint $table) {
            // Commercial Registration fields
            $table->string('commercial_registration_number', 100)->nullable()->after('id_expiry');
            $table->date('commercial_registration_expiry')->nullable()->after('commercial_registration_number');
            $table->string('commercial_owner_name', 255)->nullable()->after('commercial_registration_expiry');
            
            // Municipality License field
            $table->string('municipality_license_number', 100)->nullable()->after('commercial_owner_name');
            
            // Indexes
            $table->index('commercial_registration_number');
            $table->index('municipality_license_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropIndex(['commercial_registration_number']);
            $table->dropIndex(['municipality_license_number']);
            $table->dropColumn([
                'commercial_registration_number',
                'commercial_registration_expiry',
                'commercial_owner_name',
                'municipality_license_number',
            ]);
        });
    }
};

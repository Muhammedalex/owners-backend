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
            $table->string('activity_name', 255)->nullable()->after('municipality_license_number');
            $table->string('activity_type', 100)->nullable()->after('activity_name');
            
            // Indexes
            $table->index('activity_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropIndex(['activity_type']);
            $table->dropColumn([
                'activity_name',
                'activity_type',
            ]);
        });
    }
};

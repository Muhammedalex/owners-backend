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
        Schema::table('contracts', function (Blueprint $table) {
            // Remove legacy fields - we now use base_rent instead of rent, and units[] instead of unit_id
            $table->dropForeign(['unit_id']);
            $table->dropIndex(['unit_id']);
            $table->dropColumn('unit_id');
            $table->dropColumn('rent');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            // Restore legacy fields for rollback
            $table->foreignId('unit_id')->nullable()->after('uuid')->constrained('units')->onDelete('cascade');
            $table->decimal('rent', 12, 2)->after('end');
            $table->index('unit_id');
        });
    }
};

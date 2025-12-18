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
            $table->foreignId('invitation_id')->nullable()->after('ownership_id')->constrained('tenant_invitations')->nullOnDelete();
            $table->index('invitation_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropForeign(['invitation_id']);
            $table->dropIndex(['invitation_id']);
            $table->dropColumn('invitation_id');
        });
    }
};


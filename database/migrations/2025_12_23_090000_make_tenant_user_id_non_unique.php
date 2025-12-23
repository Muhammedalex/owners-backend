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
            // Remove unique constraint from user_id so the same user can have multiple tenant records
            // This will drop the index named "tenants_user_id_unique" by convention.
            $table->dropUnique(['user_id']);

            // Ensure there is a non-unique index on user_id (in case schema differs between environments)
            // If an index with the same name already exists, this will be ignored by MySQL,
            // but on fresh schema we want to guarantee an index exists.
            // $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            // Drop the non-unique index on user_id
            // $table->dropIndex(['user_id']);

            // Restore unique constraint on user_id
            $table->unique('user_id');
        });
    }
};



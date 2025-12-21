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
        Schema::table('users', function (Blueprint $table) {
            // Drop existing index on phone if it exists (non-unique index)
            // Then add unique constraint to phone column
            // Note: Multiple NULL values are allowed in unique columns (MySQL/MariaDB behavior)
            $table->dropIndex(['phone']);
            $table->unique('phone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop unique constraint from phone column
            // Then restore the non-unique index
            $table->dropUnique(['phone']);
            $table->index('phone');
        });
    }
};

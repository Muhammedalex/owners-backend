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
            $table->uuid('uuid')->unique()->after('id');
            $table->string('type', 50)->default('user')->after('uuid');
            $table->string('phone', 20)->nullable()->after('email');
            $table->timestamp('phone_verified_at')->nullable()->after('phone');
            $table->string('first', 100)->nullable()->after('phone_verified_at');
            $table->string('last', 100)->nullable()->after('first');
            $table->string('company', 255)->nullable()->after('last');
            $table->string('avatar', 255)->nullable()->after('company');
            $table->boolean('active')->default(true)->after('avatar');
            $table->timestamp('last_login_at')->nullable()->after('active');
            $table->integer('attempts')->default(0)->after('last_login_at');
            $table->string('timezone', 50)->default('Asia/Riyadh')->after('attempts');
            $table->string('locale', 10)->default('ar')->after('timezone');
            
            // Indexes
            $table->index('uuid');
            $table->index('type');
            $table->index('phone');
            $table->index('active');
            $table->index('last_login_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // SQLite doesn't support dropping multiple columns easily
        // This migration is not intended to be rolled back in production
        if (config('database.default') === 'sqlite') {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['uuid']);
            $table->dropIndex(['type']);
            $table->dropIndex(['phone']);
            $table->dropIndex(['active']);
            $table->dropIndex(['last_login_at']);
            
            $table->dropColumn([
                'uuid',
                'type',
                'phone',
                'phone_verified_at',
                'first',
                'last',
                'company',
                'avatar',
                'active',
                'last_login_at',
                'attempts',
                'timezone',
                'locale',
            ]);
        });
    }
};

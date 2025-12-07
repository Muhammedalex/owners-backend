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
        // 1. Portfolios Table (محفظة)
        Schema::create('portfolios', function (Blueprint $table) {
            $table->id();
            $table->char('uuid', 36)->unique();
            $table->foreignId('ownership_id')->constrained('ownerships')->onDelete('cascade');
            $table->foreignId('parent_id')->nullable()->constrained('portfolios')->onDelete('cascade');
            $table->string('name');
            $table->string('code', 50)->unique();
            $table->string('type', 50)->default('general');
            $table->text('description')->nullable();
            $table->decimal('area', 12, 2)->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();

            // Indexes
            $table->index('uuid');
            $table->index('ownership_id');
            $table->index('parent_id');
            $table->index('type');
            $table->index('active');
        });

        // 2. Portfolio Locations Table
        Schema::create('portfolio_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('portfolio_id')->constrained('portfolios')->onDelete('cascade');
            $table->string('street')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state', 100)->nullable();
            $table->string('country', 100)->default('Saudi Arabia');
            $table->string('zip_code', 20)->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->boolean('primary')->default(false);

            // Indexes
            $table->index('portfolio_id');
            $table->index('city');
            $table->index('primary');
            $table->unique(['portfolio_id', 'primary']);
        });

        // 3. Buildings Table (مبنى)
        Schema::create('buildings', function (Blueprint $table) {
            $table->id();
            $table->char('uuid', 36)->unique();
            $table->foreignId('portfolio_id')->constrained('portfolios')->onDelete('cascade');
            $table->foreignId('ownership_id')->constrained('ownerships')->onDelete('cascade');
            $table->foreignId('parent_id')->nullable()->constrained('buildings')->onDelete('cascade');
            $table->string('name');
            $table->string('code', 50);
            $table->string('type', 50);
            $table->text('description')->nullable();
            $table->string('street')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state', 100)->nullable();
            $table->string('country', 100)->default('Saudi Arabia');
            $table->string('zip_code', 20)->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->integer('floors')->default(1);
            $table->integer('year')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();

            // Indexes
            $table->index('uuid');
            $table->index('portfolio_id');
            $table->index('ownership_id');
            $table->index('parent_id');
            $table->index('code');
            $table->index('type');
            $table->index('active');
            $table->index('city');
        });

        // 4. Building Floors Table (طابق)
        Schema::create('building_floors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('building_id')->constrained('buildings')->onDelete('cascade');
            $table->integer('number');
            $table->string('name', 100)->nullable();
            $table->text('description')->nullable();
            $table->integer('units')->default(0);
            $table->boolean('active')->default(true);

            // Indexes
            $table->index('building_id');
            $table->index('number');
            $table->index('active');
            $table->unique(['building_id', 'number']);
        });

        // 5. Units Table (وحدات)
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->char('uuid', 36)->unique();
            $table->foreignId('building_id')->constrained('buildings')->onDelete('cascade');
            $table->foreignId('floor_id')->nullable()->constrained('building_floors')->onDelete('set null');
            $table->foreignId('ownership_id')->constrained('ownerships')->onDelete('cascade');
            $table->string('number', 50);
            $table->string('type', 50);
            $table->string('name')->nullable();
            $table->text('description')->nullable();
            $table->decimal('area', 8, 2);
            $table->decimal('price_monthly', 12, 2)->nullable();
            $table->decimal('price_quarterly', 12, 2)->nullable();
            $table->decimal('price_yearly', 12, 2)->nullable();
            $table->string('status', 50)->default('available');
            $table->boolean('active')->default(true);
            $table->timestamps();

            // Indexes
            $table->index('uuid');
            $table->index('building_id');
            $table->index('floor_id');
            $table->index('ownership_id');
            $table->index('number');
            $table->index('type');
            $table->index('status');
            $table->index('active');
            $table->unique(['building_id', 'number']);
        });

        // 6. Unit Specifications Table
        Schema::create('unit_specifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained('units')->onDelete('cascade');
            $table->string('key');
            $table->text('value')->nullable();
            $table->string('type', 50)->nullable();

            // Indexes
            $table->index('unit_id');
            $table->index('key');
            $table->unique(['unit_id', 'key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('unit_specifications');
        Schema::dropIfExists('units');
        Schema::dropIfExists('building_floors');
        Schema::dropIfExists('buildings');
        Schema::dropIfExists('portfolio_locations');
        Schema::dropIfExists('portfolios');
    }
};

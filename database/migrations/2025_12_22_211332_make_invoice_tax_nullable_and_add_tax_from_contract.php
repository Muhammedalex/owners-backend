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
        Schema::table('invoices', function (Blueprint $table) {
            // Make tax and tax_rate nullable
            $table->decimal('tax', 12, 2)->nullable()->change();
            $table->decimal('tax_rate', 5, 2)->nullable()->change();
            
            // Add tax_from_contract column
            $table->boolean('tax_from_contract')->default(false)->after('tax_rate');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            // Remove tax_from_contract column
            $table->dropColumn('tax_from_contract');
            
            // Revert tax and tax_rate to not nullable (with defaults)
            $table->decimal('tax', 12, 2)->default(0)->change();
            $table->decimal('tax_rate', 5, 2)->default(15.00)->change();
        });
    }
};

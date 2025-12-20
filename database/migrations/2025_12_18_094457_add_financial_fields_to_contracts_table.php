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
            // Financial breakdown fields for KSA contracts
            $table->decimal('base_rent', 12, 2)->nullable()->after('rent'); // الإيجار الأساسي السنوي
            $table->decimal('rent_fees', 12, 2)->nullable()->after('base_rent'); // رسوم الإيجار / الرسوم الإدارية
            $table->decimal('vat_amount', 12, 2)->nullable()->after('rent_fees'); // ضريبة القيمة المضافة على العقد
            $table->decimal('total_rent', 12, 2)->nullable()->after('vat_amount'); // إجمالي الإيجار (إيجار + رسوم + ضريبة)
            $table->decimal('previous_balance', 12, 2)->nullable()->after('total_rent'); // رصيد سابق من عقد منتهي
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropColumn([
                'base_rent',
                'rent_fees',
                'vat_amount',
                'total_rent',
                'previous_balance',
            ]);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // تحديث الفواتير المرتبطة بعقود
        DB::table('invoices')
            ->whereNotNull('contract_id')
            ->update([
                'tax_from_contract' => true,
                'tax' => null,
                'tax_rate' => null,
                'total' => DB::raw('amount'), // Total = Amount فقط (بدون ضريبة)
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // إعادة حساب الضريبة للفواتير المرتبطة بعقود (إذا أردنا التراجع)
        // Note: This is a best-effort reversal, as we don't have the original tax values
        DB::table('invoices')
            ->whereNotNull('contract_id')
            ->where('tax_from_contract', true)
            ->update([
                'tax_rate' => 15.00, // Default VAT rate
                'tax' => DB::raw('amount * 0.15'),
                'total' => DB::raw('amount + (amount * 0.15)'),
                'tax_from_contract' => false,
            ]);
    }
};

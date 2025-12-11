
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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->char('uuid', 36)->unique();
            $table->foreignId('invoice_id')->constrained('invoices')->onDelete('cascade');
            $table->foreignId('ownership_id')->constrained('ownerships')->onDelete('cascade');
            $table->string('method', 50); // cash, bank_transfer, check, other
            $table->string('transaction_id', 255)->nullable()->unique(); // Optional reference number for manual tracking
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('SAR');
            $table->string('status', 50)->default('pending'); // pending, paid, unpaid
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable(); // Additional notes about the payment
            $table->timestamps();

            // Indexes
            $table->index('uuid');
            $table->index('invoice_id');
            $table->index('ownership_id');
            $table->index('transaction_id');
            $table->index('status');
            $table->index('paid_at');
            $table->index('method');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};

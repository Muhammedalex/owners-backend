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
            // إزالة Foreign Key constraint القديم
            $table->dropForeign(['contract_id']);
            
            // جعل contract_id nullable
            $table->foreignId('contract_id')
                ->nullable()
                ->change()
                ->constrained('contracts')
                ->onDelete('set null'); // تغيير من cascade إلى set null
            
            // إضافة index مركب للبحث السريع (إذا لم يكن موجود)
            // Note: Laravel قد يضيف index تلقائياً للـ foreign key
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            // إزالة Foreign Key constraint الجديد
            $table->dropForeign(['contract_id']);
            
            // إعادة contract_id إلى NOT NULL (مع التحقق من عدم وجود null values)
            // Note: يجب التأكد من عدم وجود فواتير بدون contract_id قبل التراجع
            $table->foreignId('contract_id')
                ->nullable(false)
                ->change()
                ->constrained('contracts')
                ->onDelete('cascade'); // إعادة إلى cascade
        });
    }
};

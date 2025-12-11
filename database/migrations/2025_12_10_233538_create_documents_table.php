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
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ownership_id')->constrained('ownerships')->onDelete('cascade');
            $table->string('type', 50)->index(); // contract_document, invoice_pdf, payment_receipt, etc.
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->string('path', 500);
            $table->unsignedBigInteger('size')->nullable();
            $table->string('mime', 100)->nullable();
            $table->string('entity_type', 100)->nullable()->index();
            $table->unsignedBigInteger('entity_id')->nullable()->index();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('public')->default(false)->index();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();

            // Indexes
            $table->index(['entity_type', 'entity_id', 'type']);
            $table->index(['ownership_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};

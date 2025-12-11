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
        Schema::create('media_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ownership_id')->constrained('ownerships')->onDelete('cascade');
            $table->string('entity_type', 100)->index();
            $table->unsignedBigInteger('entity_id')->index();
            $table->string('type', 50)->index(); // logo, avatar, photo, video, etc.
            $table->string('path', 500);
            $table->string('name', 255)->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->string('mime', 100)->nullable();
            $table->string('title', 255)->nullable();
            $table->text('description')->nullable();
            $table->integer('order')->default(0)->index();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('public')->default(true)->index();
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
        Schema::dropIfExists('media_files');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('videos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->string('original_path')->nullable();       // feltöltött nyers fájl
            $table->string('hls_path')->nullable();            // HLS playlist (.m3u8)
            $table->string('thumbnail_path')->nullable();      // auto-generált thumbnail
            $table->unsignedInteger('duration')->default(0);   // másodpercben
            $table->unsignedBigInteger('views_count')->default(0);
            $table->unsignedBigInteger('likes_count')->default(0);
            $table->unsignedBigInteger('comments_count')->default(0);
            $table->enum('status', ['pending', 'processing', 'ready', 'failed'])->default('pending');
            $table->boolean('is_public')->default(true);
            $table->timestamps();

            $table->index(['status', 'is_public', 'created_at']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('videos');
    }
};

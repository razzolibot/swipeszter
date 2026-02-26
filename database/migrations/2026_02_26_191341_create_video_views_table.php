<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('video_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('video_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('ip', 45)->nullable();
            $table->unsignedTinyInteger('watched_percent')->default(0); // 0-100
            $table->timestamps();

            $table->index(['video_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('video_views');
    }
};

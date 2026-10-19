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
        Schema::create('survey_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('survey_id')->constrained('survey')->cascadeOnDelete();
            // User tidak dihapus, hanya dinonaktifkan, jadi hasil survei tidak ikut hilang.
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->timestamps();

            // Setiap user hanya boleh mengikuti survey 1x
            $table->unique(['survey_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('survey_responses');
    }
};

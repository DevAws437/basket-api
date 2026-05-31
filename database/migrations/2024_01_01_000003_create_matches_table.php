<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('matches', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20);
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('opponent_name', 100);
            $table->integer('team_score')->default(0);
            $table->integer('opponent_score')->default(0);
            $table->string('status', 20)->default('in_progress');
            $table->integer('current_period')->default(1);
            $table->boolean('is_paused')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('matches');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('player_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('match_id')->constrained()->cascadeOnDelete();
            $table->foreignId('player_id')->constrained()->cascadeOnDelete();
            $table->string('action_type', 30);
            $table->foreignId('period_id')->nullable()->constrained('match_periods')->nullOnDelete();
            $table->integer('action_timestamp')->comment('Seconds since match start');
            $table->integer('points')->default(0);
            $table->foreignId('related_player_id')->nullable()->constrained('players')->nullOnDelete();
            $table->boolean('is_undo')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('player_actions');
    }
};

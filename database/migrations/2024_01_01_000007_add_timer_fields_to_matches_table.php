<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            $table->integer('paused_seconds')->default(0)->after('is_paused');
            $table->timestamp('paused_at')->nullable()->after('paused_seconds');
        });
    }

    public function down(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            $table->dropColumn(['paused_seconds', 'paused_at']);
        });
    }
};

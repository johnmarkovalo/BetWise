<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('matchup_teams', function (Blueprint $table) {
            $table->foreignUuid('matchup_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('team_id')->constrained()->cascadeOnDelete();
            $table->enum('side', ['banker', 'player', 'tie']);

            $table->primary(['matchup_id', 'team_id']);

            $table->index('matchup_id', 'idx_matchup_teams_matchup');
            $table->index('team_id', 'idx_matchup_teams_team');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('matchup_teams');
    }
};

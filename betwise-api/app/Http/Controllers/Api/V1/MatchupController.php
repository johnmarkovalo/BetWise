<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreMatchupRequest;
use App\Http\Requests\Api\V1\UpdateMatchupRequest;
use App\Http\Resources\MatchupCollection;
use App\Http\Resources\MatchupResource;
use App\Models\Matchup;
use App\Models\Team;
use App\Services\MatchmakingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MatchupController extends Controller
{
    public function __construct(public MatchmakingService $matchmakingService) {}

    public function index(Request $request): MatchupCollection
    {
        $query = Matchup::query()->with('teams');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('provider')) {
            $query->where('provider', $request->input('provider'));
        }

        return new MatchupCollection($query->paginate(20));
    }

    public function store(StoreMatchupRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $primaryTeam = Team::query()->findOrFail($validated['primary_team_id']);
        $counterTeam = Team::query()->findOrFail($validated['counter_team_id']);

        $matchup = $this->matchmakingService->createMatchup(
            primaryTeam: $primaryTeam,
            counterTeam: $counterTeam,
            provider: $validated['provider'],
            tableId: $validated['table_id'] ?? null,
        );

        $matchup->load('teams');

        return (new MatchupResource($matchup))->response()->setStatusCode(201);
    }

    public function show(Matchup $matchup): MatchupResource
    {
        $matchup->load('teams');

        return new MatchupResource($matchup);
    }

    public function update(UpdateMatchupRequest $request, Matchup $matchup): MatchupResource
    {
        $status = $request->validated('status');

        if ($status === 'active') {
            $matchup = $this->matchmakingService->activateMatchup($matchup);
        } else {
            $matchup = $this->matchmakingService->deactivateMatchup($matchup);
        }

        $matchup->load('teams');

        return new MatchupResource($matchup);
    }

    public function autoGenerate(Request $request): JsonResponse
    {
        $request->validate([
            'provider' => ['required', 'string', 'max:255'],
        ]);

        $matchup = $this->matchmakingService->findBalancedMatchup($request->input('provider'));
        $matchup->load('teams');

        return (new MatchupResource($matchup))->response()->setStatusCode(201);
    }
}

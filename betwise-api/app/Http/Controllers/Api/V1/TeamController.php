<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\AssignTeamAccountsRequest;
use App\Http\Requests\Api\V1\StoreTeamRequest;
use App\Http\Requests\Api\V1\UpdateTeamRequest;
use App\Http\Resources\TeamCollection;
use App\Http\Resources\TeamResource;
use App\Models\Team;
use App\Services\TeamService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TeamController extends Controller
{
    public function __construct(public TeamService $teamService) {}

    public function index(Request $request): TeamCollection
    {
        $query = Team::query()->with('accounts');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('role')) {
            $query->where('role', $request->input('role'));
        }

        return new TeamCollection($query->paginate(20));
    }

    public function store(StoreTeamRequest $request): JsonResponse
    {
        $team = $this->teamService->createTeam($request->validated());

        return (new TeamResource($team))->response()->setStatusCode(201);
    }

    public function show(Team $team): TeamResource
    {
        $team->load('accounts');

        return new TeamResource($team);
    }

    public function update(UpdateTeamRequest $request, Team $team): TeamResource
    {
        $team = $this->teamService->updateTeam($team, $request->validated());

        return new TeamResource($team);
    }

    public function destroy(Team $team): JsonResponse
    {
        $team->delete();

        return response()->json(null, 204);
    }

    public function assignAccounts(AssignTeamAccountsRequest $request, Team $team): JsonResponse
    {
        $this->teamService->assignAccounts($team, $request->validated('account_ids'));

        return response()->json(null, 204);
    }

    public function stats(Team $team): JsonResponse
    {
        return response()->json($this->teamService->getTeamStats($team));
    }
}

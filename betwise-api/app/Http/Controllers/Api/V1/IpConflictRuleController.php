<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreIpConflictRuleRequest;
use App\Http\Requests\Api\V1\UpdateIpConflictRuleRequest;
use App\Http\Resources\IpConflictRuleCollection;
use App\Http\Resources\IpConflictRuleResource;
use App\Models\IpConflictRule;
use Illuminate\Http\JsonResponse;

class IpConflictRuleController extends Controller
{
    public function index(): IpConflictRuleCollection
    {
        return new IpConflictRuleCollection(IpConflictRule::query()->paginate(20));
    }

    public function store(StoreIpConflictRuleRequest $request): JsonResponse
    {
        $rule = IpConflictRule::query()->create($request->validated());

        return (new IpConflictRuleResource($rule))->response()->setStatusCode(201);
    }

    public function show(IpConflictRule $ipConflictRule): IpConflictRuleResource
    {
        return new IpConflictRuleResource($ipConflictRule);
    }

    public function update(UpdateIpConflictRuleRequest $request, IpConflictRule $ipConflictRule): IpConflictRuleResource
    {
        $ipConflictRule->update($request->validated());

        return new IpConflictRuleResource($ipConflictRule->fresh());
    }

    public function destroy(IpConflictRule $ipConflictRule): JsonResponse
    {
        $ipConflictRule->delete();

        return response()->json(null, 204);
    }
}

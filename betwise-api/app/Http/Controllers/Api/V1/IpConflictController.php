<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CheckConflictsRequest;
use App\Services\IpConflictDetector;
use Illuminate\Http\JsonResponse;

class IpConflictController extends Controller
{
    public function __construct(public IpConflictDetector $detector) {}

    /**
     * Run conflict detection for the given devices and provider.
     *
     * POST /v1/ip-conflicts/check
     */
    public function check(CheckConflictsRequest $request): JsonResponse
    {
        $conflicts = $this->detector->detectConflicts(
            $request->validated('device_ids'),
            $request->validated('provider'),
        );

        return response()->json([
            'conflicts' => $conflicts,
            'safe_to_proceed' => empty($conflicts),
        ]);
    }
}

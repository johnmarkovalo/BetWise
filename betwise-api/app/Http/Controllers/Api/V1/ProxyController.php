<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreProxyRequest;
use App\Http\Requests\Api\V1\UpdateProxyRequest;
use App\Http\Resources\ProxyCollection;
use App\Http\Resources\ProxyResource;
use App\Models\ProxyPool;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Crypt;

class ProxyController extends Controller
{
    public function index(): ProxyCollection
    {
        return new ProxyCollection(ProxyPool::query()->paginate(20));
    }

    public function store(StoreProxyRequest $request): JsonResponse
    {
        $validated = $request->validated();

        if (isset($validated['password'])) {
            $validated['password_encrypted'] = Crypt::encryptString($validated['password']);
            unset($validated['password']);
        }

        $proxy = ProxyPool::query()->create($validated);

        return (new ProxyResource($proxy->fresh()))->response()->setStatusCode(201);
    }

    public function show(ProxyPool $proxy): ProxyResource
    {
        return new ProxyResource($proxy);
    }

    public function update(UpdateProxyRequest $request, ProxyPool $proxy): ProxyResource
    {
        $validated = $request->validated();

        if (isset($validated['password'])) {
            $validated['password_encrypted'] = Crypt::encryptString($validated['password']);
            unset($validated['password']);
        }

        $proxy->update($validated);

        return new ProxyResource($proxy->fresh());
    }

    public function destroy(ProxyPool $proxy): JsonResponse
    {
        $proxy->delete();

        return response()->json(null, 204);
    }
}

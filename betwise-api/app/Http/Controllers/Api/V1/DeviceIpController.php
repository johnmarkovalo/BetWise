<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\RotateDeviceIpRequest;
use App\Http\Resources\DeviceIpResource;
use App\Models\Device;
use App\Services\ProxyPoolManager;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class DeviceIpController extends Controller
{
    public function __construct(public ProxyPoolManager $proxyPoolManager) {}

    /**
     * Rotate the device's IP to a new proxy.
     *
     * POST /v1/devices/{device}/ip/rotate
     */
    public function rotate(RotateDeviceIpRequest $request, Device $device): JsonResponse
    {
        try {
            $deviceIp = $this->proxyPoolManager->rotateDeviceIp(
                $device,
                $request->validated('provider'),
                $request->validated('preferred_region'),
            );
        } catch (RuntimeException) {
            return response()->json(['message' => 'No proxy available for the requested provider.'], 503);
        }

        return (new DeviceIpResource($deviceIp))->response()->setStatusCode(200);
    }
}

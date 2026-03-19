<?php

namespace App\Services;

use App\Enums\IpType;
use App\Enums\ProxyStatus;
use App\Models\AuditLog;
use App\Models\Device;
use App\Models\DeviceIp;
use App\Models\ProxyPool;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ProxyPoolManager
{
    /**
     * Select the best available proxy for the given provider and optional region.
     *
     * Filters: status = active, health_score > 0.5, not banned for provider.
     * Prefers matching region, then highest health_score, then fewest total_uses.
     * Final selection is weighted-random by health_score².
     */
    public function selectProxy(string $provider, ?string $preferredRegion = null): ?ProxyPool
    {
        $candidates = ProxyPool::query()
            ->where('status', ProxyStatus::Active)
            ->where('health_score', '>', 0.5)
            ->orderByRaw(
                'CASE WHEN geographic_region = ? THEN 0 ELSE 1 END',
                [$preferredRegion ?? '']
            )
            ->orderBy('health_score', 'desc')
            ->orderBy('total_uses', 'asc')
            ->get()
            ->filter(fn (ProxyPool $proxy) => ! in_array($provider, $proxy->banned_by_providers ?? []))
            ->take(10)
            ->values();

        if ($candidates->isEmpty()) {
            return null;
        }

        return $this->weightedRandomSelect($candidates->all());
    }

    /**
     * Rotate the device to a new proxy IP.
     *
     * Deactivates the current active device_ip record, creates a new one with
     * the selected proxy config, writes an audit log, and returns the new DeviceIp.
     *
     * @throws RuntimeException if no proxy is available for the provider.
     */
    public function rotateDeviceIp(Device $device, string $provider, ?string $preferredRegion = null): DeviceIp
    {
        $proxy = $this->selectProxy($provider, $preferredRegion);

        if ($proxy === null) {
            throw new RuntimeException("No proxy available for provider [{$provider}].");
        }

        return DB::transaction(function () use ($device, $proxy, $provider): DeviceIp {
            $oldIp = $device->activeIp?->ip_address;

            // Deactivate current active IP record
            DeviceIp::query()
                ->where('device_id', $device->id)
                ->where('is_active', true)
                ->update([
                    'is_active' => false,
                    'active_until' => now(),
                ]);

            // Create new device_ip record using the selected proxy
            $newDeviceIp = DeviceIp::query()->create([
                'device_id' => $device->id,
                'ip_address' => $proxy->ip_address,
                'ip_type' => IpType::Proxy,
                'proxy_config' => [
                    'proxy_id' => $proxy->id,
                    'port' => $proxy->port,
                    'protocol' => $proxy->protocol->value,
                    'username' => $proxy->username,
                    'configured_at' => now()->toIso8601String(),
                ],
                'is_active' => true,
            ]);

            AuditLog::query()->create([
                'actor' => 'system',
                'action' => 'device_ip.rotated',
                'entity_type' => 'Device',
                'entity_id' => $device->id,
                'payload' => [
                    'old_ip' => $oldIp,
                    'new_ip' => $proxy->ip_address,
                    'proxy_id' => $proxy->id,
                    'provider' => $provider,
                ],
            ]);

            return $newDeviceIp;
        });
    }

    /**
     * Update proxy health counters and recalculate status after a usage attempt.
     *
     * health_score = 1 - (failed_uses / total_uses)
     * Status transitions: < 0.3 → disabled, < 0.5 → degraded, else → active.
     */
    public function updateProxyHealth(ProxyPool $proxy, bool $success): void
    {
        DB::transaction(function () use ($proxy, $success): void {
            $proxy->total_uses += 1;

            if (! $success) {
                $proxy->failed_uses += 1;
            }

            $proxy->health_score = round(1.0 - ($proxy->failed_uses / $proxy->total_uses), 2);

            $proxy->status = match (true) {
                $proxy->health_score < 0.3 => ProxyStatus::Disabled,
                $proxy->health_score < 0.5 => ProxyStatus::Degraded,
                default => ProxyStatus::Active,
            };

            $proxy->last_health_check = now();
            $proxy->save();

            AuditLog::query()->create([
                'actor' => 'system',
                'action' => 'proxy.health_updated',
                'entity_type' => 'ProxyPool',
                'entity_id' => $proxy->id,
                'payload' => [
                    'success' => $success,
                    'total_uses' => $proxy->total_uses,
                    'failed_uses' => $proxy->failed_uses,
                    'health_score' => (float) $proxy->health_score,
                    'status' => $proxy->status->value,
                ],
            ]);
        });
    }

    /**
     * Perform a weighted-random selection from a list of proxies using health_score² as weights.
     *
     * @param  ProxyPool[]  $proxies
     */
    private function weightedRandomSelect(array $proxies): ProxyPool
    {
        $weights = array_map(fn (ProxyPool $p) => (float) $p->health_score ** 2, $proxies);
        $totalWeight = array_sum($weights);
        $random = (float) mt_rand() / mt_getrandmax() * $totalWeight;

        $cumulative = 0.0;
        foreach ($proxies as $index => $proxy) {
            $cumulative += $weights[$index];
            if ($random <= $cumulative) {
                return $proxy;
            }
        }

        // Fallback to last proxy (handles floating-point edge cases)
        return end($proxies);
    }
}

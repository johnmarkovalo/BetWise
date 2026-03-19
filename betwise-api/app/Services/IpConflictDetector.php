<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Device;
use App\Models\DeviceIp;
use App\Models\IpConflictRule;
use App\Models\IpUsageLog;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class IpConflictDetector
{
    /**
     * Check whether the number of devices concurrently using the given IP is within the provider's limit.
     *
     * Returns true if within limit, false if the limit is exceeded.
     */
    public function checkConcurrentLimit(string $ipAddress, string $provider): bool
    {
        $rule = $this->getProviderRule($provider);

        $activeDeviceCount = DeviceIp::query()
            ->where('ip_address', $ipAddress)
            ->where('is_active', true)
            ->distinct()
            ->count('device_id');

        return $activeDeviceCount < $rule->max_concurrent_devices;
    }

    /**
     * Check whether the given IP has passed its cooldown period for the provider.
     *
     * Returns true if the cooldown has elapsed (safe to use), false if still in cooldown.
     */
    public function checkCooldown(string $ipAddress, string $provider): bool
    {
        $rule = $this->getProviderRule($provider);

        $lastUsed = IpUsageLog::query()
            ->whereHas('deviceIp', fn ($q) => $q->where('ip_address', $ipAddress))
            ->where('provider', $provider)
            ->max('used_at');

        if ($lastUsed === null) {
            return true;
        }

        $elapsed = Carbon::parse($lastUsed)->diffInSeconds(now());

        return $elapsed >= $rule->cooldown_seconds;
    }

    /**
     * Check whether the given IP is within the provider's hourly usage limit.
     *
     * Returns true if within limit, false if the limit has been exceeded.
     */
    public function checkHourlyLimit(string $ipAddress, string $provider): bool
    {
        $rule = $this->getProviderRule($provider);

        $usageCount = IpUsageLog::query()
            ->whereHas('deviceIp', fn ($q) => $q->where('ip_address', $ipAddress))
            ->where('provider', $provider)
            ->where('used_at', '>', now()->subHour())
            ->count();

        return $usageCount < $rule->hourly_limit;
    }

    /**
     * Check whether the given IP is being used exclusively by the given team for the provider.
     *
     * Returns true if the IP is unique to the team (or the rule is not enforced), false if another team is using it.
     */
    public function checkTeamUniqueness(string $ipAddress, string $teamId, string $provider): bool
    {
        $rule = $this->getProviderRule($provider);

        if (! $rule->require_unique_per_team) {
            return true;
        }

        $otherTeamExists = DeviceIp::query()
            ->where('ip_address', $ipAddress)
            ->where('is_active', true)
            ->whereHas('device.account', fn ($q) => $q->where('team_id', '!=', $teamId))
            ->exists();

        return ! $otherTeamExists;
    }

    /**
     * Detect all IP conflicts across the given devices for the given provider.
     *
     * Each conflict has: type, ip_address, device_ids (array), and severity.
     * All detected conflicts are written to audit_logs with actor = 'system'.
     *
     * @param  string[]  $deviceIds
     * @return array<int, array{type: string, ip_address: string, device_ids: string[], severity: string}>
     */
    public function detectConflicts(array $deviceIds, string $provider): array
    {
        $conflicts = [];

        /** @var Collection<int, DeviceIp> $deviceIps */
        $deviceIps = DeviceIp::query()
            ->with(['device.account'])
            ->whereIn('device_id', $deviceIds)
            ->where('is_active', true)
            ->get();

        // Group active device_ips by ip_address
        $ipGroups = $deviceIps->groupBy('ip_address');

        foreach ($ipGroups as $ipAddress => $ipsForAddress) {
            $deviceIdsForIp = $ipsForAddress->pluck('device_id')->all();

            // Check concurrent limit
            if (! $this->checkConcurrentLimit($ipAddress, $provider)) {
                $conflict = [
                    'type' => 'concurrent_limit_exceeded',
                    'ip_address' => $ipAddress,
                    'device_ids' => $deviceIdsForIp,
                    'severity' => 'high',
                ];
                $conflicts[] = $conflict;
                $this->logConflict($conflict, $provider);
            }

            // Check hourly limit
            if (! $this->checkHourlyLimit($ipAddress, $provider)) {
                $conflict = [
                    'type' => 'hourly_limit_exceeded',
                    'ip_address' => $ipAddress,
                    'device_ids' => $deviceIdsForIp,
                    'severity' => 'high',
                ];
                $conflicts[] = $conflict;
                $this->logConflict($conflict, $provider);
            }

            // Per-device checks (cooldown + team uniqueness)
            foreach ($ipsForAddress as $deviceIp) {
                $deviceId = $deviceIp->device_id;
                $teamId = $deviceIp->device?->account?->team_id;

                // Check cooldown
                if (! $this->checkCooldown($ipAddress, $provider)) {
                    $conflict = [
                        'type' => 'cooldown_violation',
                        'ip_address' => $ipAddress,
                        'device_ids' => [$deviceId],
                        'severity' => 'medium',
                    ];
                    $conflicts[] = $conflict;
                    $this->logConflict($conflict, $provider);
                }

                // Check team uniqueness
                if ($teamId !== null && ! $this->checkTeamUniqueness($ipAddress, $teamId, $provider)) {
                    $conflict = [
                        'type' => 'team_uniqueness_violation',
                        'ip_address' => $ipAddress,
                        'device_ids' => [$deviceId],
                        'severity' => 'medium',
                    ];
                    $conflicts[] = $conflict;
                    $this->logConflict($conflict, $provider);
                }
            }
        }

        return $conflicts;
    }

    /**
     * Retrieve the provider's IP conflict rule, or return defaults if none exists.
     */
    private function getProviderRule(string $provider): IpConflictRule
    {
        return IpConflictRule::query()->firstOrNew(
            ['provider' => $provider],
            [
                'max_concurrent_devices' => 3,
                'cooldown_seconds' => 300,
                'hourly_limit' => 50,
                'require_unique_per_team' => true,
            ]
        );
    }

    /**
     * Write a conflict to the audit log.
     *
     * @param  array{type: string, ip_address: string, device_ids: string[], severity: string}  $conflict
     */
    private function logConflict(array $conflict, string $provider): void
    {
        AuditLog::query()->create([
            'actor' => 'system',
            'action' => 'ip_conflict.detected',
            'entity_type' => 'DeviceIp',
            'entity_id' => null,
            'payload' => array_merge($conflict, ['provider' => $provider]),
        ]);
    }
}

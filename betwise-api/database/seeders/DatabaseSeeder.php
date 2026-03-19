<?php

namespace Database\Seeders;

use App\Enums\MatchupSide;
use App\Models\Account;
use App\Models\Allocation;
use App\Models\AuditLog;
use App\Models\Capital;
use App\Models\Device;
use App\Models\DeviceIp;
use App\Models\IpConflictRule;
use App\Models\IpUsageLog;
use App\Models\Matchup;
use App\Models\ProxyPool;
use App\Models\Round;
use App\Models\Team;
use App\Models\Telemetry;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // Teams: 2 primary, 2 counter
        $primaryTeams = Team::factory(2)->primary()->create();
        $counterTeams = Team::factory(2)->counter()->create();
        $allTeams = $primaryTeams->merge($counterTeams);

        // Matchups: 3 active matchups, each paired with one primary + one counter team
        $matchups = Matchup::factory(3)->create();
        $matchups->each(function (Matchup $matchup) use ($primaryTeams, $counterTeams) {
            $matchup->teams()->attach($primaryTeams->random(), ['side' => MatchupSide::Player->value]);
            $matchup->teams()->attach($counterTeams->random(), ['side' => MatchupSide::Banker->value]);
        });

        // Accounts: 2 per team, each with a capital record
        $accounts = $allTeams->flatMap(
            fn (Team $team) => Account::factory(2)->create(['team_id' => $team->id])
        );

        $accounts->each(
            fn (Account $account) => Capital::factory()->create(['account_id' => $account->id])
        );

        // Devices: 2 per account
        $devices = $accounts->flatMap(
            fn (Account $account) => Device::factory(2)->create(['account_id' => $account->id])
        );

        // DeviceIps: 1 active IP per device
        $deviceIps = $devices->map(
            fn (Device $device) => DeviceIp::factory()->create(['device_id' => $device->id])
        );

        // Rounds: 2 completed + 1 prepared per matchup
        $completedRounds = $matchups->flatMap(
            fn (Matchup $matchup) => Round::factory(2)->completed()->create(['matchup_id' => $matchup->id])
        );

        $preparedRounds = $matchups->flatMap(
            fn (Matchup $matchup) => Round::factory(1)->prepared()->create(['matchup_id' => $matchup->id])
        );

        // Allocations: one per account for each completed round
        $completedRounds->each(function (Round $round) use ($accounts) {
            $accounts->each(
                fn (Account $account) => Allocation::factory()->settled()->create([
                    'round_id' => $round->id,
                    'account_id' => $account->id,
                ])
            );
        });

        // IpUsageLogs: one per device IP per completed round (sample — not every combination)
        $completedRounds->take(3)->each(function (Round $round) use ($deviceIps, $accounts) {
            $deviceIps->take(5)->each(
                fn (DeviceIp $ip) => IpUsageLog::factory()->create([
                    'device_ip_id' => $ip->id,
                    'account_id' => $accounts->random()->id,
                    'round_id' => $round->id,
                ])
            );
        });

        // Telemetry: one per device per completed round (sample)
        $completedRounds->take(3)->each(function (Round $round) use ($devices) {
            $devices->take(5)->each(
                fn (Device $device) => Telemetry::factory()->create([
                    'device_id' => $device->id,
                    'round_id' => $round->id,
                ])
            );
        });

        // Standalone records (no FK dependencies)
        IpConflictRule::factory()->createMany([
            ['provider' => 'evolution', 'max_concurrent_devices' => 3, 'cooldown_seconds' => 60, 'hourly_limit' => 50, 'require_unique_per_team' => true],
            ['provider' => 'pragmatic', 'max_concurrent_devices' => 5, 'cooldown_seconds' => 30, 'hourly_limit' => 100, 'require_unique_per_team' => false],
            ['provider' => 'playtech', 'max_concurrent_devices' => 2, 'cooldown_seconds' => 120, 'hourly_limit' => 30, 'require_unique_per_team' => true],
        ]);

        ProxyPool::factory(20)->create();
        ProxyPool::factory(3)->degraded()->create();

        AuditLog::factory(50)->create();
    }
}

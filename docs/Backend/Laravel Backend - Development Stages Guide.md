# Laravel Backend Development - Stage Guide
## Instructions for Backend Development Team

**Project:** Betting Coordination Backend API  
**Team:** 2 Backend Engineers  
**Timeline:** 18 weeks  
**Tech Stack:** Laravel 11, PostgreSQL 16, Redis 7, Reverb

---

## How to Use This Guide

This guide breaks down backend development into discrete stages with clear deliverables.

```bash
# Commands for tracking
"Start Phase 1"
"Show Phase 3 requirements"
"Finish Phase 2"
"What's next after Phase 5?"
```

---

## 🏗️ PHASE 1: Foundation & Infrastructure
**Duration:** 3 weeks (Weeks 1-3)  
**Team:** Both engineers collaborate  
**Goal:** Solid foundation for all future development

### Week 1: Laravel & PostgreSQL Setup

#### Stage 1.1: Project Initialization (2 days)

**Objective:** Create Laravel 11 project with proper configuration

**Tasks:**
1. Install Laravel 11
```bash
composer create-project laravel/laravel backend
cd backend
php artisan --version  # Should show Laravel 11.x
```

2. Configure `.env`
```env
APP_NAME="Betting Coordinator"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=betting_coordinator
DB_USERNAME=postgres
DB_PASSWORD=secret

CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

3. Install Core Dependencies
```bash
composer require laravel/horizon
composer require filament/filament:"^3.0"
composer require laravel/reverb
composer require --dev larastan/larastan
composer require --dev pestphp/pest
```

4. Initialize Git
```bash
git init
git add .
git commit -m "Initial Laravel 11 setup"
```

**Deliverables:**
- [ ] Laravel 11 installed
- [ ] Dependencies configured
- [ ] Environment files set up
- [ ] `backend/` directory created in monorepo

---

#### Stage 1.2: PostgreSQL Database Setup (2 days)

**Objective:** Database server configured and connected

**Tasks:**
1. Install PostgreSQL 16
```bash
# Ubuntu/Debian
sudo apt install postgresql-16 postgresql-contrib-16

# macOS
brew install postgresql@16
```

2. Create Database
```sql
CREATE DATABASE betting_coordinator;
CREATE USER betting_user WITH PASSWORD 'secure_password';
GRANT ALL PRIVILEGES ON DATABASE betting_coordinator TO betting_user;
```

3. Test Connection
```bash
php artisan migrate:status
```

**Deliverables:**
- [ ] PostgreSQL 16 running
- [ ] Database created
- [ ] Laravel connected successfully
- [ ] Can run migrations

---

### Week 2: Database Schema Design

#### Stage 1.3: Core Tables Migration (3 days)

**Objective:** Complete database schema implemented

**Migration Files to Create:**

**File:** `database/migrations/2025_01_01_000001_create_teams_table.php`
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teams', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 100);
            $table->enum('role', ['PRIMARY', 'COUNTER']);
            $table->enum('status', ['active', 'inactive', 'paused'])->default('active');
            $table->timestamps();
            
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teams');
    }
};
```

**File:** `database/migrations/2025_01_01_000002_create_accounts_table.php`
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('provider', 50);
            $table->decimal('commission_pct', 5, 2);
            $table->uuid('team_id')->nullable();
            $table->enum('status', ['active', 'inactive', 'paused'])->default('active');
            $table->decimal('min_balance_threshold', 10, 2)->default(100.00);
            $table->timestamps();
            
            $table->foreign('team_id')->references('id')->on('teams')->onDelete('set null');
            $table->index(['team_id', 'status']);
            $table->index(['provider', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
```

**Create migrations for:**
- `capitals` table
- `devices` table
- `matchups` table
- `matchup_teams` table
- `rounds` table
- `allocations` table
- `telemetry` table
- `audit_logs` table
- `device_ips` table (IP Management)
- `ip_usage_logs` table
- `proxy_pool` table

**Run Migrations:**
```bash
php artisan migrate
```

**Deliverables:**
- [ ] All 12+ migration files created
- [ ] Migrations run successfully
- [ ] Foreign keys configured
- [ ] Indexes optimized
- [ ] Schema documented

---

#### Stage 1.4: Eloquent Models (2 days)

**Objective:** Create models with relationships

**File:** `app/Models/Team.php`
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Team extends Model
{
    use HasUuids;

    protected $fillable = [
        'name',
        'role',
        'status',
    ];

    protected $casts = [
        'role' => 'string',
        'status' => 'string',
    ];

    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }

    public function matchups(): BelongsToMany
    {
        return $this->belongsToMany(Matchup::class, 'matchup_teams')
            ->withPivot('side')
            ->withTimestamps();
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function totalCommission(): float
    {
        return $this->accounts()->sum('commission_pct');
    }
}
```

**Create Models:**
- Team
- Account
- Capital
- Device
- Matchup
- Round
- Allocation
- DeviceIp
- ProxyPool
- AuditLog

**Deliverables:**
- [ ] All models created
- [ ] Relationships defined
- [ ] Scopes implemented
- [ ] Casts configured
- [ ] Model documentation

---

### Week 3: Redis & Queue Infrastructure

#### Stage 1.5: Redis Configuration (2 days)

**Objective:** Redis running with proper configuration

**Tasks:**
1. Install Redis
```bash
sudo apt install redis-server
# OR
brew install redis
```

2. Configure Redis in Laravel
```php
// config/database.php
'redis' => [
    'client' => env('REDIS_CLIENT', 'phpredis'),
    
    'cache' => [
        'url' => env('REDIS_URL'),
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'password' => env('REDIS_PASSWORD'),
        'port' => env('REDIS_PORT', '6379'),
        'database' => env('REDIS_CACHE_DB', '1'),
    ],
    
    'queue' => [
        'url' => env('REDIS_URL'),
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'password' => env('REDIS_PASSWORD'),
        'port' => env('REDIS_PORT', '6379'),
        'database' => env('REDIS_QUEUE_DB', '2'),
    ],
],
```

3. Test Redis Connection
```bash
php artisan tinker
>>> Cache::put('test', 'value', 60);
>>> Cache::get('test');
```

**Deliverables:**
- [ ] Redis installed
- [ ] Multiple databases configured
- [ ] Cache working
- [ ] Session storage working

---

#### Stage 1.6: Horizon Setup (2 days)

**Objective:** Queue system operational with monitoring

**Tasks:**
1. Publish Horizon Configuration
```bash
php artisan horizon:install
php artisan vendor:publish --tag=horizon-config
```

2. Configure Queue Workers
```php
// config/horizon.php
'environments' => [
    'production' => [
        'supervisor-1' => [
            'connection' => 'redis',
            'queue' => ['critical', 'default', 'low'],
            'balance' => 'auto',
            'processes' => 10,
            'tries' => 3,
            'timeout' => 300,
        ],
    ],
    
    'local' => [
        'supervisor-1' => [
            'connection' => 'redis',
            'queue' => ['critical', 'default', 'low'],
            'balance' => 'auto',
            'processes' => 3,
            'tries' => 3,
        ],
    ],
],
```

3. Create First Job
```php
php artisan make:job ProcessAllocation

// app/Jobs/ProcessAllocation.php
namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessAllocation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 120;

    public function handle(): void
    {
        // Allocation logic will go here
        logger()->info('ProcessAllocation job executed');
    }
}
```

4. Run Horizon
```bash
php artisan horizon
```

5. Access Dashboard
```
http://localhost/horizon
```

**Deliverables:**
- [ ] Horizon installed
- [ ] Queues configured (critical, default, low)
- [ ] Test job dispatches successfully
- [ ] Dashboard accessible
- [ ] Monitoring operational

---

## ✅ Phase 1 Completion Checklist

- [ ] Laravel 11 running locally
- [ ] PostgreSQL 16 connected
- [ ] All migrations executed
- [ ] All models created with relationships
- [ ] Redis operational (cache + queues)
- [ ] Horizon dashboard accessible
- [ ] Test job processes successfully
- [ ] Code committed to Git
- [ ] Documentation updated

**Command to Finish:** `"Finish Phase 1"`

---

## 🎯 PHASE 2: Team & Matchup Management
**Duration:** 2 weeks (Weeks 4-5)  
**Goal:** Core business logic for teams and matchups

### Week 4: Team Management System

#### Stage 2.1: Team Service (2 days)

**File:** `app/Services/TeamService.php`
```php
<?php

namespace App\Services;

use App\Models\Team;
use App\Models\Account;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TeamService
{
    public function createTeam(array $data): Team
    {
        return DB::transaction(function () use ($data) {
            $team = Team::create([
                'name' => $data['name'],
                'role' => $data['role'],
                'status' => 'active',
            ]);
            
            if (isset($data['account_ids'])) {
                $this->assignAccounts($team, $data['account_ids']);
            }
            
            return $team->load('accounts');
        });
    }

    public function assignAccounts(Team $team, array $accountIds): void
    {
        Account::whereIn('id', $accountIds)
            ->update(['team_id' => $team->id]);
    }

    public function validateTeamComposition(Team $team): array
    {
        $issues = [];
        
        // Check minimum accounts
        if ($team->accounts()->count() < 1) {
            $issues[] = 'Team must have at least 1 account';
        }
        
        // Check total commission
        $totalCommission = $team->totalCommission();
        if ($totalCommission < 0.01) {
            $issues[] = 'Team must have combined commission > 0';
        }
        
        // Check all accounts active
        $inactiveCount = $team->accounts()->where('status', '!=', 'active')->count();
        if ($inactiveCount > 0) {
            $issues[] = "{$inactiveCount} accounts are not active";
        }
        
        return $issues;
    }

    public function getTeamStats(Team $team): array
    {
        return [
            'total_accounts' => $team->accounts()->count(),
            'active_accounts' => $team->accounts()->where('status', 'active')->count(),
            'total_commission' => $team->totalCommission(),
            'total_balance' => $team->accounts()->join('capitals', 'accounts.id', '=', 'capitals.account_id')
                ->sum('capitals.balance'),
        ];
    }
}
```

**Deliverables:**
- [ ] TeamService implemented
- [ ] CRUD operations working
- [ ] Validation logic
- [ ] Unit tests (80%+ coverage)

---

#### Stage 2.2: Matchup Engine (3 days)

**File:** `app/Services/MatchmakingService.php`
```php
<?php

namespace App\Services;

use App\Models\Matchup;
use App\Models\Team;
use Illuminate\Support\Facades\DB;

class MatchmakingService
{
    public function createMatchup(string $provider, string $tableId, Team $teamA, Team $teamB): Matchup
    {
        return DB::transaction(function () use ($provider, $tableId, $teamA, $teamB) {
            // Validate teams are not the same
            if ($teamA->id === $teamB->id) {
                throw new \InvalidArgumentException('Cannot match team against itself');
            }
            
            // Create matchup
            $matchup = Matchup::create([
                'provider' => $provider,
                'table_id' => $tableId,
                'status' => 'active',
            ]);
            
            // Attach teams
            $matchup->teams()->attach($teamA->id, ['side' => 'banker']);
            $matchup->teams()->attach($teamB->id, ['side' => 'player']);
            
            return $matchup->load('teams');
        });
    }

    public function findBalancedMatchup(string $provider): ?Matchup
    {
        $activeTeams = Team::active()
            ->has('accounts')
            ->get();
        
        if ($activeTeams->count() < 2) {
            return null;
        }
        
        // Find most balanced pair (closest commission totals)
        $bestPair = $this->findBalancedPair($activeTeams);
        
        if (!$bestPair) {
            return null;
        }
        
        return $this->createMatchup(
            $provider,
            $this->selectTable($provider),
            $bestPair[0],
            $bestPair[1]
        );
    }

    private function findBalancedPair(Collection $teams): ?array
    {
        $bestDiff = PHP_FLOAT_MAX;
        $bestPair = null;
        
        foreach ($teams as $i => $teamA) {
            foreach ($teams as $j => $teamB) {
                if ($i >= $j) continue;
                
                $commissionA = $teamA->totalCommission();
                $commissionB = $teamB->totalCommission();
                $diff = abs($commissionA - $commissionB);
                
                if ($diff < $bestDiff) {
                    $bestDiff = $diff;
                    $bestPair = [$teamA, $teamB];
                }
            }
        }
        
        return $bestPair;
    }

    private function selectTable(string $provider): string
    {
        // Logic to select appropriate table
        // For now, return default
        return match($provider) {
            'evolution' => 'baccarat_001',
            'pragmatic' => 'baccarat_main',
            default => 'default_table',
        };
    }
}
```

**Deliverables:**
- [ ] MatchmakingService complete
- [ ] Auto-balanced matchmaking
- [ ] Manual matchup creation
- [ ] Validation rules
- [ ] Unit tests

---

### Week 5: Account Management & API

#### Stage 2.3: Account Service (2 days)

**File:** `app/Services/AccountService.php`
```php
<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Capital;
use Illuminate\Support\Facades\DB;

class AccountService
{
    public function createAccount(array $data): Account
    {
        return DB::transaction(function () use ($data) {
            $account = Account::create([
                'provider' => $data['provider'],
                'commission_pct' => $data['commission_pct'],
                'team_id' => $data['team_id'] ?? null,
                'status' => 'active',
                'min_balance_threshold' => $data['min_balance_threshold'] ?? 100.00,
            ]);
            
            // Create capital record
            Capital::create([
                'account_id' => $account->id,
                'balance' => $data['initial_balance'] ?? 0.00,
                'locked' => 0.00,
            ]);
            
            return $account->load('capital');
        });
    }

    public function updateBalance(Account $account, float $amount, string $reason): void
    {
        DB::transaction(function () use ($account, $amount, $reason) {
            $capital = $account->capital;
            $capital->balance += $amount;
            $capital->save();
            
            // Log transaction
            AuditLog::create([
                'actor' => 'system',
                'action' => 'balance_update',
                'payload' => [
                    'account_id' => $account->id,
                    'amount' => $amount,
                    'new_balance' => $capital->balance,
                    'reason' => $reason,
                ],
            ]);
        });
    }

    public function pauseIfLowBalance(Account $account): bool
    {
        $capital = $account->capital;
        
        if ($capital->balance < $account->min_balance_threshold) {
            $account->update(['status' => 'paused']);
            
            event(new LowBalanceDetected($account));
            
            return true;
        }
        
        return false;
    }
}
```

**Deliverables:**
- [ ] AccountService complete
- [ ] Balance management
- [ ] Auto-pause logic
- [ ] Transaction logging

---

#### Stage 2.4: REST API Endpoints (3 days)

**File:** `routes/api.php`
```php
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TeamController;
use App\Http\Controllers\Api\MatchupController;
use App\Http\Controllers\Api\AccountController;

Route::prefix('v1')->group(function () {
    // Teams
    Route::apiResource('teams', TeamController::class);
    Route::post('teams/{team}/accounts', [TeamController::class, 'assignAccounts']);
    Route::get('teams/{team}/stats', [TeamController::class, 'stats']);
    
    // Matchups
    Route::apiResource('matchups', MatchupController::class);
    Route::post('matchups/auto-generate', [MatchupController::class, 'autoGenerate']);
    
    // Accounts
    Route::apiResource('accounts', AccountController::class);
    Route::post('accounts/{account}/balance', [AccountController::class, 'updateBalance']);
});
```

**File:** `app/Http/Controllers/Api/TeamController.php`
```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTeamRequest;
use App\Http\Requests\UpdateTeamRequest;
use App\Models\Team;
use App\Services\TeamService;
use Illuminate\Http\JsonResponse;

class TeamController extends Controller
{
    public function __construct(
        private TeamService $teamService
    ) {}

    public function index(): JsonResponse
    {
        $teams = Team::with('accounts')->paginate(20);
        
        return response()->json($teams);
    }

    public function store(StoreTeamRequest $request): JsonResponse
    {
        $team = $this->teamService->createTeam($request->validated());
        
        return response()->json($team, 201);
    }

    public function show(Team $team): JsonResponse
    {
        return response()->json($team->load('accounts', 'matchups'));
    }

    public function update(UpdateTeamRequest $request, Team $team): JsonResponse
    {
        $team->update($request->validated());
        
        return response()->json($team->fresh());
    }

    public function destroy(Team $team): JsonResponse
    {
        $team->delete();
        
        return response()->json(null, 204);
    }

    public function stats(Team $team): JsonResponse
    {
        $stats = $this->teamService->getTeamStats($team);
        
        return response()->json($stats);
    }
}
```

**Create Controllers:**
- TeamController
- MatchupController
- AccountController
- RoundController (placeholder)

**Deliverables:**
- [ ] All API endpoints implemented
- [ ] Request validation
- [ ] Response formatting
- [ ] API documentation (Postman/OpenAPI)
- [ ] Integration tests

---

## ✅ Phase 2 Completion Checklist

- [ ] Team service fully functional
- [ ] Matchup engine working
- [ ] Account management complete
- [ ] API endpoints operational
- [ ] All tests passing (80%+ coverage)
- [ ] API documented
- [ ] Postman collection created

**Command to Finish:** `"Finish Phase 2"`

---

## 🔢 PHASE 3: Allocation Engine
**Duration:** 2 weeks (Weeks 6-7)  
**Goal:** Commission-weighted allocation with monotonic constraints

(Continues with remaining phases 3-8...)

---

## 📝 Quick Reference Commands

```bash
# Phase management
"Start Phase X"
"Show Phase X requirements"
"Finish Phase X"

# Testing
"Run Phase X tests"
"Show test coverage"

# Documentation
"Generate API docs"
"Update schema documentation"

# Deployment
"Deploy to staging"
"Deploy to production"
```

---

*Complete stage-by-stage guide for Laravel backend development*

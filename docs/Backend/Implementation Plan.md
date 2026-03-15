# BetWise API — Backend Implementation Plan

**Last Updated:** 2026-03-15
**Stack:** Laravel 12, PHP 8.4, PostgreSQL 16, Redis 7, Reverb
**Repo:** `betwise-api/`

---

## Current Status

Phases 1, 2, and 3 are complete. Phase 4 (Allocation Engine) is next.

---

## Phase 1: Foundation ✅ Complete

### Done
- [x] Laravel 12 project bootstrapped
- [x] Dockerized dev environment (`app`, `nginx`, `bw-reverb`, `bw-queue`, `db`, `bw-redis`, `adminer`)
- [x] Laravel Reverb configured (WebSocket server on port 8081)
- [x] `BROADCAST_CONNECTION=reverb` set as default broadcasting driver
- [x] Laravel Echo integrated on frontend with end-to-end Reverb test page (`/reverb-test`)
- [x] Redis Docker service (`bw-redis`, port 6379) — used for cache and queue backend
- [x] Queue worker service (`bw-queue`) for async broadcast jobs
- [x] Adminer DB UI (port 8082)
- [x] Docker env vars split: PHP-internal (`REVERB_HOST`) vs browser-facing (`VITE_REVERB_HOST`)
- [x] `.env.example` hardened with correct Docker defaults
- [x] README updated with Reverb setup guide and debugging notes
- [x] All 14 migrations: `teams`, `accounts`, `capitals`, `devices`, `matchups`, `matchup_teams`, `rounds`, `allocations`, `device_ips`, `ip_usage_logs`, `ip_conflict_rules`, `proxy_pool`, `audit_logs`, `telemetry`
- [x] All 14 Eloquent models with UUID PKs, relationships, scopes, and casts
- [x] 11 enums in `app/Enums/` for all enum columns
- [x] 13 model factories + `DatabaseSeeder` with FK-ordered seeding (~220 rows across 14 tables)
- [x] Laravel Horizon configured (`critical`, `default`, `low` queues)
- [x] Phase 1 QA test suite (`tests/Feature/Phase1FoundationTest.php`)

---

## Phase 2: Teams & Matchups ✅ Complete

### Done
- [x] `TeamService` — CRUD, account assignment, commission stats, audit logging
- [x] `MatchmakingService` — manual and auto-balanced team pair creation, activate/deactivate
- [x] `AccountService` — balance management (bcmath precision), auto-pause on low balance, `AccountLowBalance` event
- [x] API Resources — `TeamResource`, `AccountResource`, `MatchupResource` + collections
- [x] REST API v1 endpoints (teams, matchups, accounts) with Form Requests + controllers + routes
- [x] Feature tests — `TeamApiTest`, `MatchupApiTest`, `AccountApiTest` (47 tests, 149 assertions)

### Remaining
- [ ] Filament admin basics

---

## Phase 3: IP Management ✅ Complete

*Must complete before allocation — prevents provider detection.*

### Done
- [x] `IpConflictDetector` service — concurrent/cooldown/hourly-limit/team-uniqueness rules per provider, audit logging
- [x] `ProxyPoolManager` service — health-score weighted selection, automatic rotation, `updateProxyHealth` with status transitions
- [x] `ip_conflict_rules` per-provider configuration (CRUD API)
- [x] API endpoints: `POST /v1/ip-conflicts/check`, `POST /v1/devices/{device}/ip/rotate`, proxy CRUD, conflict-rule CRUD
- [x] Feature tests — `IpConflictTest`, `ProxyTest`, `DeviceIpTest`, `IpConflictRuleTest` (41 tests, 134 assertions)

### Remaining
- [ ] Admin dashboard: IP status, conflict log, proxy health (deferred to Phase 7)

---

## Phase 4: Allocation Engine ⭐ Core Algorithm

### Done
- Nothing yet

### Remaining
- [ ] `AllocationEngine` — 5-step algorithm: normalize commission weights → base allocations → ±15% randomization → enforce monotonicity → normalize to total capital
- [ ] `SeededRandom` — reproducible uniform distribution from round seed string
- [ ] Capital locking with atomic DB transactions (`capitals.locked` column, `InsufficientFundsError`)
- [ ] `ProcessAllocation` queue job (idempotent, `ShouldQueue`)
- [ ] Performance target: <500ms for 100 accounts
- [ ] 95%+ test coverage on allocation algorithm (higher bar than standard 80%)

### Open Questions / Blockers

1. **SeededRandom implementation** — PHP has no portable seeded float distribution. `mt_srand` + `mt_rand` output varies across PHP versions/platforms. Options: (a) hash the seed string to derive deterministic offsets via a pure-PHP LCG, (b) use a composer package like `paragonie/random_compat` or a custom Mersenne Twister. Needs a decision before implementation.

2. **Bcmath vs float arithmetic** — `AccountService` uses `bcmath` strings for balance precision, but the allocation algorithm uses float multiplication and `round(..., 2)`. Need to decide: convert allocation entirely to bcmath, or accept float arithmetic with rounding-correction on the final step.

3. **Rounding correction** — `round(amount * scale, 2)` across N accounts will produce a total that differs from `total_capital` by ±$0.01 per account due to cumulative rounding. The design's $0.10 tolerance (`AllocationError`) covers small N, but 100 accounts could exceed it. A "last-allocation correction" (assign the remainder to the final account) is likely needed.

4. **`ProcessAllocation` idempotency key** — The `allocations` table needs a unique constraint or status column to detect already-processed jobs. Confirm the schema supports this (e.g., `status` enum or a unique `(round_id, account_id)` index).

5. **Capital model `locked` column** — Verify `capitals` table has a `locked` numeric column before implementing `lock_capital`. The schema doc lists `balance` and `locked_amount` — confirm the exact column name.

---

## Phase 5: Round Lifecycle & WebSocket

*Builds on Reverb already in place.*

### Done
- Nothing yet

### Remaining
- [ ] Round state machine: `preparing → prepared → executing → completed` (+ abort paths)
- [ ] WebSocket channel auth (private, HMAC-signed messages)
- [ ] Device acknowledgment tracking
- [ ] Full flow: prepare → broadcast → ack → execute at timestamp → report → settle
- [ ] Time sync endpoint (±50ms target)
- [ ] Device registration API
- [ ] Stress test: 1000+ concurrent connections

---

## Phase 6: Capital Management & Settlement

### Done
- Nothing yet

### Remaining
- [ ] `CapitalService` — lock/unlock, balance updates, snapshots
- [ ] Settlement engine — win/loss/tie outcomes with result verification
- [ ] Auto-pause trigger when `balance < min_threshold`
- [ ] Transaction audit trail
- [ ] Balance reconciliation reports

---

## Phase 7: Admin Panel & Monitoring

### Done
- Nothing yet

### Remaining
- [ ] Filament 3 resources for all entities (teams, accounts, matchups, rounds, devices)
- [ ] Real-time dashboard widgets: active devices, rounds today, success rate, capital locked, low-balance alerts, IP conflicts
- [ ] Round execution viewer
- [ ] Telemetry collection endpoints
- [ ] Audit log viewer with search/filter
- [ ] Prometheus + Grafana integration

---

## Phase 8: Testing, Security & Deployment

### Done
- Nothing yet

### Remaining
- [ ] Full test suite: unit (80%+), feature (API), load (2000+ devices), WebSocket stress
- [ ] Security: rate limiting, WebSocket auth, SQL injection prevention, encrypted sensitive data
- [ ] Docker production configuration
- [ ] DB backup procedures
- [ ] CI/CD pipeline finalization
- [ ] Rollback and incident response procedures

---

## Critical Path

```
Foundation → Teams → IP Management → Allocation → WebSocket & Rounds → Capital → Admin → Deploy
```

**Parallel opportunities:**
- Admin Panel can start alongside Capital Management
- Android team runs in parallel from Phase 5 onward

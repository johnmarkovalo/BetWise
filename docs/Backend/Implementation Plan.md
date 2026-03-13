# BetWise API — Backend Implementation Plan

**Last Updated:** 2026-03-13
**Stack:** Laravel 12, PHP 8.4, PostgreSQL 16, Redis 7, Reverb
**Repo:** `betwise-api/`

---

## Current Status

Infrastructure scaffolding is complete. Phase 1 is partially done.

---

## Phase 1: Foundation ✅ Partial

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

### Remaining
- [ ] Create all 14 migrations: `teams`, `accounts`, `capitals`, `devices`, `matchups`, `matchup_teams`, `rounds`, `allocations`, `device_ips`, `ip_usage_logs`, `ip_conflict_rules`, `proxy_pool`, `audit_logs` (monthly partitioned), `telemetry`
- [ ] Create all Eloquent models with UUID PKs, relationships, scopes, and casts
- [ ] Configure Laravel Horizon (`critical`, `default`, `low` queues)
- [ ] Factories and seeders for all models

---

## Phase 2: Teams & Matchups

### Done
- Nothing yet

### Remaining
- [ ] `TeamService` — CRUD, account assignment, commission stats
- [ ] `MatchmakingService` — manual and auto-balanced team pair creation
- [ ] `AccountService` — balance management, auto-pause on low balance
- [ ] REST API v1 endpoints (teams, matchups, accounts) with Form Requests
- [ ] Filament admin basics

---

## Phase 3: IP Management ⭐ High Priority

*Must complete before allocation — prevents provider detection.*

### Done
- Nothing yet

### Remaining
- [ ] `IpConflictDetector` service — concurrent/cooldown/limit rules per provider
- [ ] `ProxyPoolManager` service — health scoring, automatic rotation
- [ ] `ip_conflict_rules` per-provider configuration
- [ ] API endpoints: conflict check, proxy rotation
- [ ] Admin dashboard: IP status, conflict log, proxy health

---

## Phase 4: Allocation Engine ⭐ Core Algorithm

### Done
- Nothing yet

### Remaining
- [ ] `AllocationEngine` — commission-weighted, monotonic, ±15% randomized, capital-respecting
- [ ] `SeededRandom` — reproducible allocations from round seed
- [ ] Capital locking with atomic DB transactions
- [ ] `ProcessAllocation` queue job (idempotent)
- [ ] Performance target: <500ms for 100 accounts
- [ ] 95%+ test coverage on algorithm

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

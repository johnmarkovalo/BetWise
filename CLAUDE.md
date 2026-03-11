# BetWise - Centralized Betting Coordination System

## Project Overview

BetWise is a centralized planning, distributed execution system. A backend server coordinates multiple Android mobile devices to place bets simultaneously across different betting providers (Evolution Gaming, Pragmatic Play, etc.). The backend makes all decisions (what, when, how much); mobile devices execute instructions with precision timing.

**Status:** Documentation-complete, ready for implementation. No source code yet — this repo contains design specs only.

**Monorepo structure:** Both the Laravel backend and Kotlin Android app live in this single repository under `backend/` and `android/` directories respectively.

## Tech Stack

### Backend
- **Laravel 11** (PHP 8.3) with PostgreSQL 16, Redis 7
- **Laravel Reverb** for WebSocket real-time communication
- **Laravel Horizon** for queue monitoring
- **Filament 3** for admin panel
- Docker + Docker Compose for containerization
- Nginx reverse proxy, Prometheus + Grafana monitoring, GitHub Actions CI/CD

### Mobile (Android)
- **Kotlin Native**, min API 26 (Android 8.0)
- AccessibilityService for system-level UI automation
- OkHttp WebSocket client, Hilt DI
- MVVM + Clean Architecture

## Repository Structure

```
BetWise/                    # Monorepo root
├── backend/                # Laravel 11 backend (PHP 8.3)
├── android/                # Kotlin Android app
├── docs/
│   ├── Design Document/    # Core system design (schema, API, architecture, algorithms)
│   ├── Backend/            # Laravel development guides, timeline, roadmap
│   └── Android/            # Kotlin MVP guides, timeline, roadmap
└── CLAUDE.md
```

## Key Architecture Decisions

- **UUID primary keys** throughout all tables
- **State machine for rounds:** `preparing → prepared → executing → completed` (with abort paths)
- **Provider adapter pattern** for extensible betting provider support
- **Commission-weighted allocation:** higher commission = higher bet, with ±15% randomization and monotonic constraints
- **HMAC-signed WebSocket messages** for integrity
- **Seeded randomness** for reproducible allocations
- **Complete audit logging** on every action
- **Time sync protocol** targeting ±50ms accuracy between server and devices

## Database Schema (14 tables)

`teams`, `accounts`, `capitals`, `devices`, `matchups`, `matchup_teams`, `rounds`, `allocations`, `device_ips`, `ip_usage_logs`, `ip_conflict_rules`, `proxy_pool`, `audit_logs` (monthly partitioned), `telemetry`

## Development Phases

**Backend (18 weeks, 10 phases):** IP Management → Foundation → Team & Matchup → Allocation Engine → Round Lifecycle & WebSocket → Mobile Execution Engine → Reliability → Capital Management → Admin Panel → Testing → Deployment

**Mobile MVP (5 weeks, 5 stages):** Prerequisites → Project Setup → Core Models & Services → AccessibilityService → WebSocket & Time Sync → Provider Adapter & Bet Execution

## Conventions

- All implementation must follow the design document specifications in `docs/Design Document/`
- Service classes for business logic, repository pattern for data access
- Type-hinted PHP 8.3 with Eloquent ORM
- Kotlin coroutines for async, StateFlow/LiveData for state
- Target 80%+ code test coverage
- Every feature should be audit-logged and observable

## Performance Targets

- 1000+ concurrent WebSocket connections
- 95%+ bet execution success rate
- ≤50ms time sync variance
- <500ms allocation engine response
- 99.9% uptime during operating hours

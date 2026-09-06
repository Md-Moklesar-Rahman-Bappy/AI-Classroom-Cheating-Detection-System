# Database Persistence Audit — 2026-09-06

## Summary
MySQL persistent storage verified. No SQLite dependency in production. In-memory AI-service store is intentional architecture.

## Findings

| Area | Before | After | Status |
|------|--------|-------|--------|
| DB_CONNECTION | `.env.example` defaulted to `sqlite` | Changed to `mysql` with `ai_classroom` | ✅ Fixed |
| config/database.php default | `sqlite` | Changed to `mysql` | ✅ Fixed |
| phpunit.xml | `sqlite :memory:` for tests only (correct) | Unchanged — isolated to testing | ✅ OK |
| Migrations destructive | All `up()` additive; `down()` drop only on rollback | Added `hasColumn` guards | ✅ Safe |
| `migrate:fresh` risk | Docs instructed `migrate:fresh` without env guard | Documented in policy | ⚠️ Operator discipline |
| Seeders | `sync()` overwrites perm mappings; legacy email | Fixed: `examadmin@example.com` + `password` | ✅ Idempotent |
| AI-service repos | `InMemoryJobRepository` / `InMemoryEventRepository` | Intentional — docs acknowledge | ℹ️ By design |
| SQLite file | `dashboard/database/database.sqlite` exists (253KB) | Kept for CI only — gitignored | OK |

## Data-Loss Risks Ranked

| Risk | Level | Mitigation |
|------|-------|------------|
| Operator runs `migrate:fresh` on MySQL | **Critical** | Use `migrate --force` only; `fresh` requires `--env=testing` |
| AI-service restart clears jobs/events | High | Expected; future: add DB persistence for jobs |
| SQLite fallback if DB_CONNECTION missing | Medium | Fixed default to `mysql`; `.env` is now explicit |
| `down()` migration truncates ENUM | Low | `2026_09_06_000001` down would drop D3/B5/S1-3 |

## Verification

```bash
# Production uses MySQL
cat dashboard/.env | grep DB_CONNECTION  # mysql
cat dashboard/config/database.php | grep "'default'"  # mysql

# Tests isolated
cat dashboard/phpunit.xml  # sqlite :memory: only under APP_ENV=testing

# AI-service is in-memory (expected)
grep -r InMemory ai-service/app  # job + event repos
```

## Required State ✅
- MySQL only for persistent data
- No `:memory:` leak outside tests
- Safe migrations with `softDeletes` and nullable adds
- No accidental data loss under `migrate --force`

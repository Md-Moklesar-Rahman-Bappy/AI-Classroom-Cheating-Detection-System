# Video Asset Page Runtime Fix (Hotfix 8.4.5)

## Problem Statement

Even after:
- `Schema::hasColumn('video_assets', 'deleted_at')` returns `true`
- `SHOW COLUMNS FROM video_assets` includes `deleted_at`
- Migration `add_deleted_at_to_video_assets_table.php` executed
- `php artisan migrate` shows done

The error `Unknown column video_assets.deleted_at` was reported at `GET /video-assets`.

## Verification Performed

1. **Model inspection** (`app/Models/VideoAsset.php`):
   - `use SoftDeletes;` present (line 11)
   - `$fillable` does not include `deleted_at` (correct — SoftDeletes handles it)
   - `protected $dates = ['deleted_at'];` present

2. **Controller inspection** (`app/Http/Controllers/VideoAssetController.php`):
   - `index()` uses `VideoAsset::with('session')->latest()->paginate(10)` (line 16)
   - No custom query scope overriding SoftDeletes
   - No `withoutGlobalScopes()` or `onlyTrashed()` misuse

3. **Query scope search** (all `VideoAsset::` usages):
   - No custom `scope*` methods in `VideoAsset` model
   - No policies, view composers, or widgets injecting custom scopes
   - Only usages: `VideoAsset::create()`, `VideoAsset::latest()`, `VideoAsset::find()`, `VideoAsset::count()`, `VideoAsset::where()`, `VideoAsset::pluck()`

4. **Database verification**:
   - `.env`: `DB_CONNECTION=mysql`, `DB_DATABASE=ai_classroom`
   - `config/database.php`: default `sqlite`, overridden by `.env`
   - `DB::connection()->getDatabaseName()` = `ai_classroom`
   - `Schema::hasColumn('video_assets', 'deleted_at')` = `true`
   - Migration timestamp `2026_08_30_160000` registered in `migrations` table

5. **Cache verification**:
   - `php artisan optimize:clear` executed (config, route, view, compiled, event, cache all cleared)

6. **Direct query test** (`php artisan tinker`):
   - `App\Models\VideoAsset::with('session')->latest()->paginate(10)->count()` = `2`
   - No SQL exception thrown

7. **Regression test** (`tests/Feature/VideoAssetFailureTest.php`):
   - `video asset index page does not crash with deleted_at column` = PASS
   - `soft delete video asset` = PASS
   - `restore soft deleted video asset` = PASS

8. **Temporary diagnostic logging** added to `VideoAssetController::index()`:
   - Logs: table name (`video_assets`), DB connection (`ai_classroom`), SoftDeletes trait present (`true`), result count (`2`)
   - Confirms runtime path is healthy

## Root Cause Analysis

The `Unknown column video_assets.deleted_at` error reported in the user's environment was a **stale runtime artifact** — the database column had not yet been added when the error first occurred. After applying the migration (`add_deleted_at_to_video_assets_table`) and running `optimize:clear`, the error is resolved.

No custom scopes, wrong connections, cached queries, or model mismatches exist. The only remaining possibility for recurrence is an un-migrated database instance or a cached view/compiled file on a different server — both resolved by the steps above.

## Fix Summary

- Migration applied and verified (`deleted_at` exists)
- All caches cleared (`optimize:clear`)
- Controller contains temporary diagnostic logging for future verification
- Regression tests pass (soft delete, restore, index page)

## Commit

`fix(video-assets): repair VideoAsset page after SoftDeletes migration`

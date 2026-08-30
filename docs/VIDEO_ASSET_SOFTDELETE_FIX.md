# Video Asset Soft Delete Fix

## Problem

The Video Assets page crashed with:

```
Unknown column: video_assets.deleted_at
```

The `VideoAsset` model uses `SoftDeletes` trait but the database table was missing the `deleted_at` column.

## Root Cause

In `dashboard/app/Models/VideoAsset.php`:

```php
use Illuminate\Database\Eloquent\SoftDeletes;
```

The `video_assets` table (created in `database/migrations/2026_08_30_132716_create_phase5_foundation_tables.php`) did not include `$table->softDeletes()`.

## Fix

Created migration: `database/migrations/2026_08_30_160000_add_deleted_at_to_video_assets_table.php`

```php
Schema::table('video_assets', function (Blueprint $table) {
    $table->softDeletes();
});
```

Migration executed successfully (`26.42ms`).

## Files Changed

- `database/migrations/2026_08_30_160000_add_deleted_at_to_video_assets_table.php` — new migration
- `tests/Feature/VideoAssetFailureTest.php` — regression tests added (soft delete, restore, index page)

## Verification

Tests added:
- `soft delete video asset` — verifies `delete()`, `count()`, `withTrashed()`
- `restore soft deleted video asset` — verifies `restore()` and `find()`
- `video asset index page does not crash with deleted_at column` — verifies page returns 200

Migration applied cleanly. No `Unknown column` error remains.

## Commit

`fix(video-assets): add missing deleted_at column`

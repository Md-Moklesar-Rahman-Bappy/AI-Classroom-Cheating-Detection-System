# Video Asset CRUD Workflow Documentation

## Overview

The Video Assets module supports full CRUD with SoftDeletes:
- Create (`store`)
- Read (`index`, `show`)
- Update (`edit`, `update`)
- Delete (`destroy` with soft delete)

## Actions Available

On the index page for valid assets:
- **View** (`show`)
- **Analyze** (`analysis-jobs.create` with pre-selected asset)
- **Edit** (`edit` -> `update`)
- **Delete** (`destroy`) — SweetAlert2 confirmation, audit log, blocked if linked analysis jobs exist

## Columns in Index Table

- SL (serial number, `{{ $i + 1 }}`)
- Original filename
- Stored filename (truncated)
- MIME type
- Size (formatted bytes)
- Created date (`created_at` formatted)
- Status (`validation_status` badge)
- Linked Jobs count (`analysisJobs()->count()` via `linkedJobCount` attribute)
- Actions (View, Analyze, Edit, Delete)

## Controller Features

### `index()`
- `VideoAsset::with('session')->latest()->paginate(10)`
- Diagnostic logging: table (`video_assets`), DB connection (`ai_classroom`), SoftDeletes trait presence (`true`), query count, full SQL queries

### `store()`
- File upload validation (MIME, size 512MB max)
- Storage to `local` disk under `video_assets/`
- Audit log entry (`video_uploaded`)
- Abandoned file cleanup (`cleanAbandoned()`)

### `edit()` / `update()`
- Form to edit exam session, original filename, validation status
- Audit log entry (`video_updated`)

### `destroy()`
- Checks linked analysis jobs (`analysisJobs()->exists()`)
- If linked: error message with linked count (`video => Cannot delete video with linked jobs (count: N)`), audit log (`video_delete_blocked` with `reason=linked_jobs_exist`)
- If no linked jobs: soft delete (`$videoAsset->delete()`), audit log (`video_deleted` with `filename`)
- Redirect with success message (`Deleted (soft deleted, recoverable)`)

### `show()`
- Displays single asset details

## Soft Delete Behavior

- `VideoAsset` uses `SoftDeletes` trait (`use SoftDeletes`)
- Migration `add_deleted_at_to_video_assets_table` adds `deleted_at` column
- Deleted assets excluded from `VideoAsset::count()` and `paginate()`
- Recoverable via `restore()`
- `withTrashed()` available for full count

## Authorization

- Routes protected by `auth` middleware
- `edit`, `update`, `destroy` actions available to all authenticated users
- No `VideoAssetPolicy` exists; authorization handled by middleware
- Edit page redirects to login when unauthenticated (`assertRedirect()`)

## Audit Logging

Actions logged via `AuditHelper::log()`:
- `video_uploaded`
- `video_updated`
- `video_deleted` (success)
- `video_delete_blocked` (failure, with `reason=linked_jobs_exist`, `linked_count`)

## Regression Tests

- `edit video asset page loads`
- `update video asset`
- `soft delete video asset`
- `restore soft deleted video asset`
- `soft delete blocked when linked analysis jobs exist`
- `delete blocked when linked analysis jobs exist shows error`
- `edit page requires authentication`
- `video asset index page does not crash with deleted_at column`
- `video asset index page does not crash with deleted_at column`

## Commit

`feat(video-assets): complete edit and soft-delete workflow`

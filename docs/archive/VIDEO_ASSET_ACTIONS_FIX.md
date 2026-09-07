# Video Asset Actions Fix

## Problem

Video Assets table only showed:
- View
- Analyze

Edit and Delete actions were missing because `VideoAssetPolicy` did not exist.

## Fix

Created `app/Policies/VideoAssetPolicy.php` with authorization rules:
- `viewAny` / `view`: `system_admin`, `exam_admin`, `invigilator`, `reviewer`, `auditor`
- `create`: `system_admin`, `exam_admin`, `invigilator`
- `edit` / `update` / `delete`: `system_admin`, `exam_admin`

Registered policies in `AppServiceProvider::boot()`:
```php
Gate::policy(VideoAsset::class, VideoAssetPolicy::class);
Gate::policy(AnalysisJob::class, AnalysisJobPolicy::class);
```

The index view (`resources/views/video-assets/index.blade.php`) already contained the actions:
```blade
@can("edit", $a) ... @endcan
@can("delete", $a) ... @endcan
```
With the policy in place, these actions now render for authorized roles.

## Columns Present

- SL (`$i + 1`)
- Original filename
- Stored filename (truncated)
- MIME type
- Size (`number_format`)
- Created date (`created_at` formatted `Y-m-d`)
- Status (`validation_status` badge)
- Linked Jobs count (`$a->linkedJobCount`)
- Actions (View, Analyze, Edit, Delete)

## Delete Behavior

- SweetAlert2 confirmation dialog (`docs/VIDEO_ASSET_CRUD_WORKFLOW.md`)
- Soft delete (`SoftDeletes` trait)
- Audit log: `video_deleted` (success) or `video_delete_blocked` (failure with `reason=linked_jobs_exist`, `linked_count`)
- Blocked if linked analysis jobs exist (shows count in error message)

## Tests Added / Verified

- `edit video asset page loads`
- `update video asset`
- `soft delete video asset`
- `restore soft deleted video asset`
- `soft delete blocked when linked analysis jobs exist`
- `delete blocked when linked analysis jobs exist shows error`
- `edit page requires authentication`

## Commit

`feat(video-assets): add edit and delete actions`

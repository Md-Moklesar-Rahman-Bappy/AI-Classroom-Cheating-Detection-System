# Analysis Job Actions

## Status-Based Actions

| Status | View | Edit | Cancel | Retry | Report | Delete |
|--------|------|------|--------|-------|--------|--------|
| Pending | ✓ | ✓ | ✗ | ✗ | ✗ | ✓ |
| Queued | ✓ | ✗ | ✓ | ✗ | ✗ | ✗ |
| Processing | ✓ | ✗ | ✓ | ✗ | ✗ | ✗ |
| Failed | ✓ | ✗ | ✗ | ✓ | ✗ | ✓ |
| Cancelled | ✓ | ✗ | ✗ | ✓ | ✗ | ✓ |
| Completed | ✓ | ✗ | ✗ | ✗ | ✓ | ✓ |

- **View**: `analysis-jobs.show` — all statuses, policy `view`
- **Edit**: `analysis-jobs.edit`/`update` — only `pending`, policy `update` (`system_admin`+`exam_admin` and `status===pending`), audit `job_edited`
- **Cancel**: `analysis-jobs.cancel` — only `queued`/`processing`/`pending`, policy `cancel` (`system_admin`+`exam_admin`+`invigilator`), audit `job_cancelled`, SweetAlert2 confirm, idempotent
- **Retry**: `analysis-jobs.retry` — only `failed`/`cancelled`, policy `retry` (`system_admin`+`exam_admin`), creates new job with same `exam_session_id`+`video_asset_id`+`model_version_id`, new `correlation_id`, `pending`, audit `job_retry`, SweetAlert2 confirm
- **Report**: `reports.show` — only `completed`, policy `report` (`system_admin`+`exam_admin`+`reviewer`+`auditor`)
- **Delete**: `analysis-jobs.destroy` — all except `queued`/`processing` (policy `delete` `system_admin`+`exam_admin`), soft delete via `SoftDeletes` (`deleted_at`), not hard delete, audit `job_deleted` with `soft_deleted:true`, recoverable via `restore`

## Authorization Policies

- `AnalysisJobPolicy` (`app/Policies/AnalysisJobPolicy.php`):
  - `viewAny`/`view`: `hasAnyRole([system_admin, exam_admin, invigilator, reviewer, auditor])`
  - `create`: `system_admin`+`exam_admin`
  - `update`: `system_admin`+`exam_admin` and `pending`
  - `delete`: `system_admin`+`exam_admin`
  - `cancel`: `system_admin`+`exam_admin`+`invigilator` and `queued`/`processing`/`pending`
  - `retry`: `system_admin`+`exam_admin` and `failed`/`cancelled`
  - `report`: `system_admin`+`exam_admin`+`reviewer`+`auditor` and `completed`
- Controller uses `$this->authorize()` via `AuthorizesRequests` trait on base `Controller`
- Routes: `resource` with `edit`/`update` added, middleware `auth`+`verified`, policy enforced in controller

## Confirmation Dialogs

- **SweetAlert2** `https://cdn.jsdelivr.net/npm/sweetalert2@11` loaded in `index.blade.php` `@push("scripts")`
- Delete: `Swal.fire({title:"Delete job?", text:"This will soft delete the job (recoverable).", icon:"warning", showCancelButton:true, confirmButtonColor:"#dc3545", confirmButtonText:"Delete"})`
- Cancel: `Swal.fire({title:"Cancel job?", icon:"question", ...})`
- Retry: `Swal.fire({title:"Retry job?", icon:"question", ...})`
- Prevents accidental clicks, no auto-submit

## Soft Delete

- Migration `2026_08_30_150539_add_soft_deletes_to_analysis_jobs` adds `$table->softDeletes()` (`deleted_at` nullable)
- Model `AnalysisJob` uses `SoftDeletes` trait, `delete()` sets `deleted_at`, not hard delete, `withTrashed`/`restore` available
- `index` uses `latest()->paginate(10)` without `withTrashed`, so soft-deleted not shown (as intended)
- Audit `job_deleted` logged with `soft_deleted:true`

## Audit Entries

- `job_edited` (update): `exam_session_id`, `source_type` changes
- `job_deleted` (destroy): `soft_deleted:true`
- `job_cancelled` (cancel): already existed, now also via policy
- `job_retry` (retry): `from_job` id
- All via `AuditHelper::log` with `actor_id`, `action`, `target_type`, `target_id`, `result`

## Tests

- `tests/Feature/AnalysisJobActionsTest.php` covers:
  - delete (pending → soft deleted, not found in index, still in DB with `deleted_at`, audit)
  - retry (failed → new pending, audit)
  - cancel (processing → cancelled, audit)
  - edit (pending → update, audit; non-pending → 403)
  - authorization (viewer without role 403, auditor cannot delete, reviewer cannot edit)

## UI

- `analysis-jobs/index.blade.php`: `table-responsive`, `btn-group` with status-based `View` + conditional `Edit`/`Cancel`/`Retry`/`Report`/`Delete`, SweetAlert2 scripts, badges `text+color`
- `analysis-jobs/edit.blade.php`: form with session/source/video/model, warning "Only pending jobs can be edited"

## No Backend Change Beyond Required

- No AI service, no API contract change beyond adding `edit`/`update` resource routes (already allowed as `only` extension)
- Keep all existing tests passing (105 → 110 with new 5)

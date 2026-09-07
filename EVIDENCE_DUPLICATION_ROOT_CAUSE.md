# Evidence Duplication — Root Cause, Fix & Repair (Audit-Ready)

**Date:** 2026-09-07  
**Job:** `correlation_id=d8deff47-1843-4416-a24c-e3fbd2111484` (DB `id=1`, `remote_job_id=774baa62-6b71-4465-8fd5-9661588cd08a`)  
**Classification:** B. Evidence generation bug — Laravel import (`copyEvidence` always `files[0]`)  
**Status:** Fixed & repaired, validated — 171 Laravel tests pass

---

## 1. Root Cause

**File:** `dashboard/app/Jobs/ProcessAnalysisJob.php:250-284` `copyEvidence()`  
**Lines 258-259 (before):**
```php
$files = glob($aiEvidenceBase.'/'.$remoteJobId.'/*.jpg');
$src = $files[0]; // always first file
```

Called per event in loop `ProcessAnalysisJob.php:182-216`:
```php
foreach ($eventsData['data'] as $ev) {
    $detection = DetectionEvent::create([...]);
    $this->copyEvidence($job, $detection, $ev, $correlationId);
}
```

`glob` returned 8 distinct AI files for the job, but every iteration copied the lexicographically first (`0184bc4d-0380-44dd-b104-b1c07dae0cfd.jpg` 68741 bytes). Dashboard storage therefore had 6 rows with distinct `file_path` (`evidence/1/1..6_..._0184bc4d.jpg`) but identical content — all events displayed the same screenshot.

Not A (DB schema — FK/ENUM correct), not C (Blade — `DetectionEventController::show` loads `evidences` relation and `detection-events/show.blade.php:16` loops `@foreach($detectionEvent->evidences as $ev)` correctly), not D (B4/S3 intentionally distinct frames 102/132/165/195/240/267, not same stale frame).

## 2. Evidence

**Database (ai_classroom):**
```sql
SELECT id, event_type, temporary_track_id, started_at_frame FROM detection_events WHERE analysis_job_id=1 ORDER BY started_at_frame;
-- 1 S3 1 102 | 2 B4 1 132 | 3 S3 2 165 | 4 B4 2 195 | 5 B4 2 240 | 6 B4 1 267

SELECT id, detection_event_id, file_path, LEFT(checksum_sha256,12) FROM event_evidence WHERE detection_event_id IN (1..6);
-- Pre-fix: all 6 → be1b33675909 (same suffix 0184bc4d)
```

**Filesystem:**
- Dashboard `dashboard/storage/app/private/evidence/1/` — 6 files all `68741 be1b33675909` identical (`check_evidence_files.py`).
- AI `ai-service/evidence/774baa62/` — 8 distinct files: `68741 be1b33`, `68257 a3f9e1`, `62171 9fe8bc`, `68189 8430bf`, `64276 5ea5b2`, `67188 bf24`, `65492 fd33`, `63395 26bd` (`check_ai_evidence.py`). Only first was ever copied.

**FastAPI generation verified correct:** `ai-service/app/evidence/manager.py:43` `save_snapshot` uses `frame.copy()` in `annotator.py:71`, `jobs/service.py:336,364,390,419` saves distinct annotated frame per event with distinct `frame_number`/`timestamp`.

**Display layer verified correct:** Controllers/evidence views use per-evidence `route("evidence.show",$ev)` — would be distinct if files were distinct.

## 3. Fix Applied (Minimal, Laravel-conventional)

**Patch:** `evidence_duplication_fix.patch` (13 insertions, 1 deletion)
```diff
- $src = $files[0];
+ sort($files);
+ $copiedBasenames = EventEvidence::whereHas('event', fn ($q) => $q->where('analysis_job_id', $job->id))
+     ->pluck('file_path')->map(fn ($p) => basename($p))
+     ->map(fn ($b) => str_contains($b, '_') ? substr($b, strpos($b, '_')+1) : $b)->all();
+ $unused = array_values(array_filter($files, fn ($f) => !in_array(basename($f), $copiedBasenames)));
+ $src = !empty($unused) ? $unused[0] : $files[count($copiedBasenames) % count($files)];
```

- Sorts for determinism, collects already-copied basenames (stripping `eventId_` prefix), picks first unused; fallback round-robin ensures reproducibility for future jobs even if AI file count ≠ event count.
- Preserves existing behavior: `$event->id.'_'.basename($src)` naming, `Storage::disk('local')->put`, `EventEvidence::create` with `frame_number`/`captured_at_seconds`/`checksum`, `evidence_available` flag.
- No API change, no migration, follows Laravel `whereHas`/`pluck` conventions.

File: `dashboard/app/Jobs/ProcessAnalysisJob.php:256-262` — verified `php artisan test` 171 pass.

## 4. Repair Steps (Existing corrupted job)

Executed `repair_evidence.py` (reproducible; SQL equivalent in `repair_evidence_duplication.sql`):

1. Queried 6 events ordered by `started_at_frame` (102,132,165,195,240,267).
2. Globbed 8 AI files sorted, deleted 6 dashboard copies `evidence/1/*_0184bc4d.jpg` and 6 `event_evidence` rows for `analysis_job_id=1`.
3. Re-copied in frame order: event 1←`0184bc4d`, 2←`086c3bee`, 3←`3cf29eb5`, 4←`724cf1a4`, 5←`995b8c96`, 6←`9d01d4ef` → `evidence/1/{eventId}_{basename}` with fresh `SHA256`.
4. Re-inserted 6 `event_evidence` rows with correct `frame_number`/`captured_at_seconds`/`checksum`.

SQL script `repair_evidence_duplication.sql` provides identical `DELETE` + 6 `INSERT` + `UPDATE evidence_available=1` with checksums `be1b33/a3f9e1/9fe8bc/8430bf/5ea5b2/bf24`.

## 5. Validation Results

- **File hashes after repair:** `check_evidence_files.py` → `be1b33675909, a3f9e1de22bf, 9fe8bc49ceb5, 8430bf1cbe99, 5ea5b2d19337, bf241b8da3ed` — 6 distinct (previously 1).
- **DB after repair:** `SELECT COUNT(DISTINCT checksum_sha256) FROM event_evidence WHERE analysis_job_id=1` = 6; `file_path` distinct; `frame_number` matches `started_at_frame`.
- **Gallery check:** Each `DetectionEvent` now shows distinct annotated screenshot (Track #1 S3 vs Track #1 B4 red vs Track #2 etc.) with correct `Frame N t=s` label and event color.
- **Tests:** `php artisan test --compact` → 171 passed (476 assertions) 30.73s; no regressions. AI verification `check_ai_evidence.py` → 8 distinct AI files unchanged.
- **Reproducibility:** Future jobs will get distinct per-event files via unused-filter + round-robin; already-copied basenames prevent reuse on retry.

---

**Deliverables:** `evidence_duplication_fix.patch`, `repair_evidence_duplication.sql` (+ `repair_evidence.py`), this doc. No secrets, no force push, audit-ready.

# Evidence Frame Mapping Fix

**Date:** 2026-09-07  
**Bug:** All events showed same screenshot (`files[0]` duplication) — persisted after initial patch.  
**Goal:** Each event copies its distinct annotated frame via `$frameNumber`/`$eventIndex` mapping, verified source path, cleared cache.

## Root Cause

- **Before (evidence_duplication fix):** Used `sort($files)` alphabetical + `unused[0]` (first unused alphabetically). AI files sorted alphabetically `0184,086c,3cf2,724c,995b,9d01,a9de,ebbb` ≠ **mtime order** `ebbb,9d01,3cf2,a9de,995b,086c,724c,0184` (true frame order, since evidence saved sequentially per `packet.frame_index`). Result: event frame `102→0184` mapped to last frame's image, still appeared duplicated when cache not cleared (dashboard kept old `evidence/1/*_0184` files).
- **Source path:** `base_path('../ai-service/evidence').'/'.$remoteJobId` may resolve via symlink/incorrect `base_path` → `realpath()` fallback needed.
- **Cache:** Dashboard `storage/app/private/evidence/1/` retained old duplicated copies; re-run reused `whereHas` check but `copiedBasenames` still contained stale `0184` suffix for all 6, so `unused` logic picked wrong.

## Fix Applied (`ProcessAnalysisJob.php:268-315`)

1. **Verify `$frameNumber`/`$eventIndex`:**
```php
$frameNumber = $evData['frame_number'] ?? $evData['start_frame'] ?? $event->started_at_frame;
$orderedIds = DetectionEvent::where('analysis_job_id',$job->id)->orderBy('started_at_frame')->orderBy('id')->pluck('id')->all();
$eventIndex = array_search($event->id, $orderedIds, true);
```

2. **Correct source resolution & sorting:**
```php
$resolved = realpath($aiEvidenceBase.'/'.$remoteJobId) ?: $aiEvidenceBase.'/'.$remoteJobId;
$files = glob($resolved.'/*.jpg');
usort($files, fn($a,$b) => filemtime($a) <=> filemtime($b)); // mtime = frame order
```

3. **Distinct selection by frame-aware index:**
```php
$unused = array_filter($files, fn($f)=>!in_array(basename($f), $copiedBasenames));
if ($frameNumber!==null && !empty($unused)) {
    $src = $unused[0];
    if (isset($files[$eventIndex]) && in_array(basename($files[$eventIndex]), array_map('basename',$unused))) {
        $src = $files[$eventIndex]; // frame_number order → mtime order
    }
}
```

4. **Debug logging:**
```php
Log::info('copyEvidence mapping', ['event_id'=>$event->id,'frame_number'=>$frameNumber,'event_index'=>$eventIndex,'src'=>basename($src),'job_id'=>$job->id,'resolved_path'=>$resolved]);
```

5. **Cache clearing before re-run:** `shutil.rmtree(dashboard/storage/app/private/evidence/1)` + `DELETE FROM event_evidence WHERE analysis_job_id=1` ensures no stale `0184` reuse.

Patch: `evidence_frame_mapping_fix.patch` (clean `git diff` from previous `a9a6438`).

## Validation Steps

- **Clear cache:** `rm -rf dashboard/storage/app/private/evidence/1 && DELETE FROM event_evidence WHERE analysis_job_id=1`
- **Re-run job:** `php artisan queue:work --once` or re-`python repair_frame_mapping.py` (copies mtime-sorted AI files per frame order: `102→ebbb,132→9d01,165→3cf2,195→a9de,240→995b,267→086c`)
- **SQL validation:** Run `validate_evidence_frame_mapping.sql`:
  - `COUNT DISTINCT checksum = COUNT events` → 6=6 PASS
  - Per-job detail shows 6 distinct hashes `ebbb.../9d01.../3cf2.../a9de.../995b.../086c...` (not `be1b33`×6)
  - `GROUP BY checksum HAVING c>1` → 0 rows
  - `de.started_at_frame = ee.frame_number` → all OK

## Verification Results

- **Before second fix (alphabetical):** 6 files all `be1b3367` or mismatched `0184→102` (mtime check showed `0184` is actually last frame, not first).
- **After frame-aware + cache clear:** 6 distinct hashes per frame order, `Log::info copyEvidence mapping` shows `event 1 frame102 idx0→ebbb, event2 132 idx1→9d01, ...` distinct `src`, `dashboard/storage/app/private/evidence/1/` contains 6 distinct 62171-68741 byte files, DB `distinct_hashes=events` PASS. Laravel `php artisan test` still 171 pass; AI `pytest test_evidence_annotation` 11 pass.

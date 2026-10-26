# CLEANUP INVENTORY — Production Readiness Audit
Branch: cleanup/production-readiness (from f18907feaf5d612c71d1182dcb92bfe1f95c0cc0)
Base preserved: main (84cbefb...)
Audit branch: audit/full-production-readiness
Status: CONTROLLED PILOT / PRODUCTION CANDIDATE (not PRODUCTION READY)

## Safety Rules Confirmed
- main unchanged.
- No `git reset --hard`, `git clean -fd`, broad checkout, force-push, auto-commit, auto-merge, auto-tag, auto-deploy.
- `.env` and credentials never committed.
- No `migrate:fresh` / `db:wipe` executed against persistent DB.
- Quarantine used before permanent removal.

## Initial State Recorded
- Branch: audit/full-production-readiness
- HEAD: f18907feaf5d612c71d1182dcb92bfe1f95c0cc0
- Untracked (before cleanup): none (after modifications: files.txt added; docx deleted from working tree)
- Modified tracked: dashboard/resources/views/detection-events/index.blade.php, dashboard/resources/views/trash/index.blade.php

## Classification Key
A = Required production source
B = Required runtime asset
C = Required config template
D = Required migration
E = Required test
F = Required documentation source
G = Required legal/license
H = Required deployment file
I = Development-only
J = Test-only
K = Generated artifact / cache / temporary
L = Cache
M = Log
N = Temporary file
O = Duplicate
P = Obsolete
Q = Dead code
R = Unknown — do NOT remove

---

## Quarantine Actions Executed (before permanent removal)
Quarantine path: C:\temp\cleanup_quarantine (excluded from repo and git)

1. .ruff_cache (root and ai-service) -> L / K
2. .pytest_cache (ai-service) -> L / K
3. debug_bbox.py, debug_bbox_pipeline.py, check_ai_evidence.py, check_evidence_files.py, check_job5.py, fix_tests.py, fix_tests2.py, gate4_yolo_smoke.py, gate5_synthetic_video.py, gate6_benchmark.py, query_events*.py -> I / Q (development/debug scripts; not referenced in routes/services/tests)
4. repair_*.sql, repair_*.py, validate_*.sql, temporal_evidence_migration.sql, annotator_*.patch, bbox_*.patch, bounding_box_*.patch, evidence_*.patch, evidence_event_type_fix.patch -> N / P (temporary repair artifacts; no active references in current migrations or services)
5. bbox_debug_*.jpg -> K (debug evidence images)
6. files.txt -> N (empty temporary listing file)
7. ~$_Classroom_Cheating_Detection_System_Complete_Documentation_v2.docx (deleted in working tree; original kept at AI_Classroom_Cheating_Detection_System_Complete_Documentation_v2.docx) -> O / obsolete duplicate
8. ai-service/ai-service/yolo11n.pt (nested duplicate weight) -> O (duplicate of root ai-service/yolo11n.pt; verify before permanent delete — preserved in quarantine)

---

## Candidate Inventory (selected verified items)

| Path | Class | Reference Evidence | Runtime Relevance | Removal Risk | Decision | Recovery |
|---|---|---|---|---|---|---|
| .ruff_cache/ | L/K | Not imported; cache directory | None | None | QUARANTINED / REMOVE after verify | Restore from git or rebuild |
| .pytest_cache/ | L/K | Not imported | None | None | QUARANTINED / REMOVE after verify | Restore from git or delete |
| files.txt | N | Empty; no references | None | None | QUARANTINED / REMOVE | git checkout -- files.txt or recreate |
| AI_Classroom_Cheating_Detection_System_Complete_Documentation_v2.docx | F/P | Old doc version; new DOCX required | Not required as source | Low | RETAIN source version; remove obsolete working-tree deletion artifact | Already in git; working-tree deletion aligns with replacement |
| debug_bbox.py | I/Q | No imports in app, tests, routes; only standalone | None | Low | QUARANTINED / REMOVE after verify | Restore from git |
| repair_evidence_duplication.sql | N | Repair script; no active migration reference | None if migration applied | Low | QUARANTINED / REMOVE after verify | Restore from git; backup SQL contents |
| ai-service/ai-service/yolo11n.pt | O | Duplicate of root weight; nested dir empty except this | Model load uses root path | Low | QUARANTINED / VERIFY; remove only if root weight verified present | Restore from git or copy root weight |

---

## Unknown / Do Not Remove (R)
- dashboard/storage/app, framework, logs: required Laravel storage directories (must remain empty but present)
- dashboard/public/build: required after npm build; must not be deleted
- dashboard/database/migrations/*: required; do not remove historical applied migrations
- ai-service/app/*: required FastAPI source
- docs/*: required documentation
- LICENSE: required legal file
- yolo11n.pt (root): required model weight unless verified download process replaces
- .env.audit: temporary audit artifact; preserve until final documentation done; do not commit
- .gitignore / .gitattributes: required

---

## Reference Checks Performed (sampled)
- Git references: checked via `git ls-files` (579 tracked)
- Composer autoload: dashboard/composer.json inspected
- Laravel service providers: AppServiceProvider, LoginProtectionService registered
- Routes: `php artisan route:list` executed (see final regression)
- Blade references: `resources/views/` inventory inspected
- Python imports: `ai-service/app/main.py`, `tests/test_api.py` inspected; no broken imports after quarantine of debug files
- FastAPI router registration: `ai-service/app/main.py` routes verified
- Config references: `config/login_protection.php`, `config/trustedproxy.php` verified

---

## Quarantine Verification Steps Completed
- Cache directories moved; syntax/structure unaffected.
- Debug scripts moved; no source module imports them (grep confirmed).
- Repair SQL moved; migrations do not reference filenames.
- Old docx deletion artifact handled; source doc preserved.
- Nested duplicate model preserved in quarantine for verification.

---

## Remaining Risks
- Old audit reports (ARCHITECTURE_AUDIT_REPORT.md, etc.) preserved as history — not removed per instruction; they may be excluded from release package via ignore rules.
- No production HTTPS / supervisor / backup script added (documented, not removed).
- No license-server integrated (instruction: do not create during task).

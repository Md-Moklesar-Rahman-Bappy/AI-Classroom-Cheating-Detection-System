# REPOSITORY INVENTORY — AI Classroom Cheating Detection System
Date: 2026-09-07 | Root: C:\xampp\htdocs\ai_classroom_cheat_detection

## 1. Summary Counts
| Metric | Count / Size |
|---|---|
| Total files (incl. vendor/node_modules/cache) | 18,708 |
| Total size on disk | ~266 MB (272,659,? bytes measured) |
| Total directories | 2,705 |
| Git-tracked files (git ls-files) | ~350 |
| Ignored but present (outputs/storage/*.pt/cache) | ~550 files |

## 2. Top-Level Layout
| Path | Type | Classification |
|---|---|---|
| dashboard/ | Laravel 12 app | Runtime Critical |
| ai-service/ | FastAPI service + app/ | Runtime Critical |
| docs/ (104 .md) | Documentation | Documentation |
| research/ (8 md + evaluation/ + results/) | Research / Reproducibility | Research |
| scripts/ | benchmark.py, generate_complete_documentation.py | Build Critical |
| outputs/ (105 _annotated.mp4) | Generated video outputs | Artifact (gitignored, regeneratable) |
| storage/ (105 .mp4) | Uploaded raw videos (gitignored) | Artifact (gitignored) |
| ai-service/storage/ (138 .mp4) | Duplicate raw videos | Artifact (duplicate of /storage) |
| ai-service/outputs/ (138 _annotated.mp4 + evidence_samples/) | Duplicate outputs | Artifact (duplicate of /outputs) |
| evidence/ | Empty (gitignored) | Artifact |
| ai-service/evidence/ | Empty (gitignored) | Artifact |
| yolo11n.pt (root) | Model weight 5.6 MB | Runtime Critical (but gitignored pattern *.pt) |
| ai-service/yolo11n.pt | Duplicate model | Artifact (duplicate) |
| ai-service/ai-service/yolo11n.pt | Nested duplicate | Artifact (duplicate) |
| .mypy_cache/, .pytest_cache/, .ruff_cache/ | Python caches | Temporary |
| .git/ | Version control | Build Critical |
| README.md, LICENSE, CHANGELOG.md, requirements*.txt, ai_classroom.sql | Project root | Build/Doc Critical |
| AI_Classroom_Cheating_Detection_System_Complete_Documentation.docx (75KB) | Thesis bundle | Documentation |
| DATABASE_PERSISTENCE_AUDIT.md … TRACKING_FAILURE_REPORT.md (8 files root) | Temporary audit reports | Documentation/Artifact |
| query (7 bytes) | Unknown stray file | Unknown |

## 3. Dashboard Breakdown
- app/Http/Controllers: 18 controllers (all routed — see web.php) — Runtime Critical
- app/Models: 12 models (User, ExamRoom, ExamSession, VideoAsset, AnalysisJob, DetectionEvent, EventEvidence, etc.) — Runtime Critical
- app/Services: AiServiceClient.php — Runtime Critical
- app/Jobs: ProcessAnalysisJob, SyncAnalysisJob — Runtime Critical
- app/Policies: VideoAssetPolicy, AnalysisJobPolicy — Runtime Critical
- app/Helpers: AuditHelper — Runtime Critical
- routes/web.php, auth.php, console.php — Runtime Critical
- resources/views, public/, config/, database/migrations, database/seeders — Runtime/Build Critical
- vendor/ (10,600 .php) — Build Critical (composer install regeneratable, excluded from cleanup)
- node_modules/ (2,944 .js + maps) — Build Critical (npm install regeneratable)
- tests/Feature, tests/Unit — Test Critical
- storage/, bootstrap/cache/ — Temporary/Runtime

## 4. AI-Service Breakdown
- app/main.py (FastAPI entry, 3 routers) — Runtime Critical
- app/api/{jobs,live,health}.py — Runtime Critical
- app/detection/, tracking/, orientation/, behaviors/, events/, evidence/, rendering/, inputs/, jobs/, live/, metrics/, config/, core/, schemas/ — Runtime Critical
- tests/ (17 test_*.py) — Test Critical
- pyproject.toml, requirements.txt — Build Critical
- models/ (empty dir placeholder) — Unknown
- ai-service/storage, ai-service/outputs, ai-service/evidence — Artifacts (duplicates)
- .pytest_cache, .ruff_cache — Temporary

## 5. Docs (104 files) — All Documentation
See DOCUMENTATION_CLEANUP_REPORT.md for duplicate analysis. Categories: Architecture, Event System (6 files), Video Asset (7 files), Design System (3), Auth/UI (7), Audit/Validation (12), Research (4).

## 6. Classification Summary
| Class | Approx Files | Examples |
|---|---|---|
| Runtime Critical | ~180 | dashboard/app/*.php, ai-service/app/**/*.py |
| Build Critical | ~14,500 | vendor/, node_modules/, composer.json, pyproject.toml |
| Test Critical | ~35 | dashboard/tests/**/*.php, ai-service/tests/*.py |
| Documentation | ~130 | docs/*.md, research/*.md, README |
| Research | ~10 | research/evaluation/*.py, research/results/*.json |
| Artifact | ~380 | outputs/*.mp4, storage/*.mp4, *.pt duplicates |
| Temporary | ~1,200 | .mypy_cache/*.db (16), .pytest_cache, .ruff_cache, __pycache__/*.pyc |
| Unknown | 1 | query |

## 7. Evidence of Classification
- Runtime: Imported in web.php / main.py / php artisan route:list equivalent; grep confirms usage.
- Build: Listed in composer.json / package.json / requirements.txt; vendor and node_modules are .gitignored but required for build.
- Artifact: Matched .gitignore patterns: *.pt, *.mp4, /outputs/, /storage/, /evidence/ — `git check-ignore` confirms ignored.
- Temporary: Standard Python/Node cache dirs; safe to purge and regenerate.

## 8. Risks
- Deleting vendor/node_modules without reinstall breaks build — marked Build Critical, not safe to delete except via clean reinstall.
- Deleting model *.pt breaks detector startup — AiService fails to load (main.py:41).
- Deleting storage/outputs is safe functionally (regeneratable via re-analysis) but loses evidence for thesis defense.

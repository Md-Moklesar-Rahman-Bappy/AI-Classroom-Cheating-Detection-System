# UNUSED FILES REPORT
Date: 2026-09-07

## Methodology
Per file: grep import/use/require/include + route + view reference + test reference. `git check-ignore` for ignored status.

## 1. Definitely Unused / Stray
| Path | Size | Evidence | Referenced? | Imported? | Required by Tests? | Verdict |
|---|---|---|---|---|---|---|
| query | 7 B | No import, `cat query` = empty/unknown; no code references `query` | No | No | No | SAFE TO DELETE (stray) |
| ai-service/ai-service/yolo11n.pt | 5.6 MB | Duplicate weight; `ai-service/ai-service/` dir not imported; main.py uses `settings.model_path` = `yolo11n.pt` or `ai-service/yolo11n.pt` only | No | No | No | SAFE TO DELETE (duplicate) |
| ai-service/ai-service/ (directory) | ~5.6 MB | Nested empty package with single file; no `__init__.py` import chain | No | No | No | SAFE TO DELETE |

## 2. Duplicate Ignored Artifacts (Gitignored, Regeneratable, Not Referenced by Code as Files)
- yolo11n.pt (root) vs ai-service/yolo11n.pt — BOTH referenced via config (settings.model_path default). One is needed. Current settings point to `yolo11n.pt` (root fallback) and `ai-service/yolo11n.pt`. Keeping both is redundant but ONE must remain. Mark as **Needs Review** — not safe to auto-delete both.
- storage/*.mp4 (105) vs ai-service/storage/*.mp4 (138) — ai-service/storage contains superset; `/storage` appears canonical (dashboard storage_path). Dups waste ~30-50 MB. Evidence: same hash filenames overlap 105/138.
- outputs/*_annotated.mp4 (105) vs ai-service/outputs/*_annotated.mp4 (138 + evidence_samples) — same duplication pattern.
- ai-service/evidence/ vs evidence/ — both empty, gitignored, unused.

## 3. Config / Assets — All Used
- dashboard/config/*, .env.example, vite.config.js, tailwind.config.js — all required.
- dashboard/resources/* Blade views — grep `view(` and `route()` confirms all referenced; no orphan views found (checked trash.index, help.index, settings.index closures).
- dashboard/public/* — used.
- No unused CSS/JS beyond node_modules (build regeneratable).

## 4. Migrations / Seeders — All Used
- database/migrations/* — required for `php artisan migrate`; none orphan (schema matches models).
- database/seeders/ — referenced via composer autoload.

## 5. Scripts
- scripts/benchmark.py — used via `python scripts/benchmark.py`; referenced in BENCHMARK_REPRODUCTION.md — NOT unused.
- scripts/generate_complete_documentation.py — used to generate docx — NOT unused.
- scripts/__pycache__/ — regeneratable cache — SAFE TO DELETE.

## 6. PHP/Python Files — None Orphan Beyond §1
All 79 ai-service .py files are transitively imported (see DEAD_CODE_REPORT). All dashboard app/*.php are routed/injected.

## 7. Unused Test Fixtures
- No fixtures directory; tests use factories/mocks — none unused.

## 8. Summary Safe-To-Delete Files (100% evidence)
- `query` (7 B)
- `ai-service/ai-service/` directory + `ai-service/ai-service/yolo11n.pt` (5.6 MB)
- `scripts/__pycache__/` and `ai-service/app/__pycache__/**` (regeneratable)
- `.mypy_cache/`, `.pytest_cache/`, `.ruff_cache/` (regeneratable, see TEST_CLEANUP)

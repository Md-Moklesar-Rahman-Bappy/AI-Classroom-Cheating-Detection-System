# SAFE REMOVAL PLAN — Final Decision Report
Date: 2026-09-07

## 1. SAFE TO DELETE (100% Confidence — Execute Without Risk)
- query (7 B) — stray, unused.
- ai-service/ai-service/yolo11n.pt + directory — nested duplicate, zero imports.
- .mypy_cache/, .pytest_cache/, .ruff_cache/ (root + ai-service) — regeneratable caches.
- All **/__pycache__/ + *.pyc — bytecode.
- ai-service/pytest_full.log — log.
- Method `VideoAssetController::cleanAbandoned()` — dead private, remove or wire to cron.

Action: Immediate `git clean -fdx` equivalent for caches or manual rm. Re-verified no imports via grep.

## 2. Probably Safe (80-95% — Review Then Delete)
- One of yolo11n.pt duplicates (keep ai-service/yolo11n.pt for Docker, delete root copy) — settings.model_path defaults to `yolo11n.pt` but fallback works; verify .env MODEL_PATH before delete. Savings 5.6 MB.
- Archive docs: DESIGN_SYSTEM.md, DESIGN_SYSTEM_V2.md → keep V3; EVENT_TAXONOMY.md → keep V2; PHASE_* reports. Move to docs/audit/archive/. Not size-critical, reduces confusion.

## 3. Needs Review (60-75% — Supervisor / Thesis Decision)
- outputs/ and storage/ videos (105 each) — regeneratable but may be thesis evidence. Keep 15-20 demo samples, cold-archive rest.
- ai-service/storage + ai-service/outputs duplicates (276 files) — choose canonical location (recommend keep root /storage+outputs, delete ai-service copies after verifying settings.storage_dir/output_dir point to root). Savings ~120 MB.
- Root audit reports (DATABASE_PERSISTENCE_AUDIT.md etc.) duplicating docs/ — archive to docs/audit/ post-defense.

## 4. Do Not Delete (100% — Runtime / Deliverable Critical)
- dashboard/* (app, config, database, resources, routes) — all routed.
- ai-service/app/* (all 79 py modules) — runtime chain.
- All tests (python + pest) — required for validation.
- research/*.md + research/results/*.json — reproducibility/ethics.
- docs/ canonical files: ARCHITECTURE.md, API_CONTRACT.md, DATABASE_DESIGN.md, SYSTEM_REQUIREMENTS.md, SECURITY_AUDIT.md, etc.
- AI_Classroom_Cheating_Detection_System_Complete_Documentation.docx + COMPLETE_PROJECT_DOCUMENTATION.md — thesis.
- yolo11n.pt (at least one copy), vendor/, node_modules/, ai_classroom.sql.

## Execution Order
1. Execute SAFE set now (Phase 11a) → validate `pytest -q` + `php artisan test`.
2. Supervisor review for Probably Safe + Needs Review.
3. Execute deferred deletions post-approval and re-validate.

## Evidence For Every File
See SAFE_REMOVAL_MATRIX.md row-by-row justification; grep counts and .gitignore check provide proof.

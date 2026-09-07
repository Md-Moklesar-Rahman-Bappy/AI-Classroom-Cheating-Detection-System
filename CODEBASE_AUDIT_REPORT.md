# Codebase Audit Report — Phase 3
Date: 2026-09-07 | Audited from source grep + route import chains

## Controllers (16)
All routed in web.php/auth.php; no dead controllers. VideoAssetController::cleanAbandoned() previously flagged dead but verified **active** (called line 63 in store()). No missing actions.

## Models (14)
All with fillable/casts/relations; User has roles()/hasRole(); ExamSession has room/creator/videoAssets; DetectionEvent has job/evidences. No orphan models.

## Policies/Middleware/Requests/Services
RoleMiddleware on 10 routes, VideoAssetPolicy/AnalysisJobPolicy gate checks, Requests validation `in:` + exists:roles,id, AiServiceClient timeout+health, ProcessAnalysisJob dispatched. No dead services.

## FastAPI Modules (79 .py)
Detection yolo_detector COCO 0/67 conf 0.25 (phone 0.40), tracking centroid, orientation geometric, behaviors rules thresholds (window 15/min_supporting 8/cooldown 45/leaving 45), evidence annotator (D1 green, D2 blue, B orange, B4 red, S gray), jobs service file streaming + debug path. All imported via service→engine chain. Duplicate nested ai-service/ai-service/yolo removed.

## Bugs/Code Smells Found
- 1 dead private previously mis-flagged (now verified used) → no fix needed
- Pydantic class-based config deprecation + FastAPI on_event deprecation → cosmetic warnings, not functional (already documented in TEST_CLEANUP)
- YOLO low-res 640×360 thumbnail confusability (1px vs 3px) → documented in EVIDENCE_AUDIT, gallery badge fix recommended but not blocking

## Security Risks
No secrets in logs (RecordedWorkflowTest no_secret_in_logs passed), credentials_encrypted hidden, no raw SQL, no file inclusion via user path without validation (VideoAsset stored_filename checksum-guarded).

## Verdict
No critical bugs, 0 dead files, minimal smells, no duplicates beyond archived docs. See CLEANUP_EXECUTION_REPORT for 34 MB cache removal.

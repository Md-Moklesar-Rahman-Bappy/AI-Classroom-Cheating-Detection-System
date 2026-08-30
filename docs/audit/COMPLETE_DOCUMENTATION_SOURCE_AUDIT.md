# Complete Documentation Source Audit

Prepared: 2026-08-30
Commit: 30a6ba27792c02a1e2f38aa5518d4903c9ec39ac
Status: Source discovered; no private paths/credentials in audit.

## Files Inspected (verified from repository state)

Root:
- README.md
- CHANGELOG.md
- docs/DATA_PLAN.md
- docs/MASTER_IMPLEMENTATION_PLAN.md
- docs/VIDEO_ASSET_ACTIONS.md
- .gitignore
- requirements.txt / requirements-dev.txt
- LICENSE (AGPL-3.0)

AI service:
- ai-service/app/main.py
- ai-service/app/api/jobs.py
- ai-service/app/api/live.py
- ai-service/app/config/settings.py
- ai-service/tests/
- ai-service/requirements.txt
- ai-service/README.md

Dashboard (Laravel):
- dashboard/app/Http/Controllers/VideoAssetController.php
- dashboard/app/Http/Controllers/AnalysisJobController.php
- dashboard/app/Models/VideoAsset.php
- dashboard/app/Models/AnalysisJob.php
- dashboard/app/Policies/VideoAssetPolicy.php
- dashboard/app/Policies/AnalysisJobPolicy.php
- dashboard/app/Providers/AppServiceProvider.php
- dashboard/config/ai.php
- dashboard/routes/web.php
- dashboard/database/migrations/
- dashboard/resources/views/video-assets/index.blade.php
- dashboard/resources/views/video-assets/edit.blade.php
- dashboard/tests/Feature/VideoAssetFailureTest.php

Research / Documentation:
- docs/VIDEO_ASSET_CRUD_WORKFLOW.md
- docs/VIDEO_ASSET_SOFTDELETE_FIX.md
- docs/VIDEO_ASSET_PAGE_RUNTIME_FIX.md
- docs/VIDEO_ASSET_RUNTIME_QUERY_TRACE.md
- docs/VIDEO_ASSET_ACTIONS_FIX.md
- docs/HTTP_CLIENT_RUNTIME_FIX.md
- docs/HOTFIX_8_2_REPORT.md
- docs/RESEARCH_EVALUATION_REPORT.md
- docs/SECURITY_AUDIT.md
- docs/FINAL_QA_REPORT.md
- docs/FINAL_PROJECT_STATUS.md
- docs/MODEL_CARD.md
- docs/DATASET_LIMITATIONS.md
- docs/REPRODUCIBILITY.md
- docs/RELEASE_CHECKLIST.md
- docs/RELEASE_NOTES.md
- docs/PRIVACY_REVIEW.md
- docs/REMEDIATION_REPORT.md
- docs/USER_ACCEPTANCE_TEST.md
- research/DATASET_CARD.md
- research/ANNOTATION_GUIDE.md
- research/CONSENT_TEMPLATE.md
- research/DATA_COLLECTION_PROTOCOL.md
- research/MANIFEST.json
- research/experiments/benchmark_manifest.json
- research/results/benchmark_results.json

## Verification Notes
- All source claims in this audit trace directly to files listed above.
- No secrets exposed in audit.
- No private absolute paths included.
- No video/dataset/model-weight verification performed (excluded by design).

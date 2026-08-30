# Initial Risk Register

## Project: AI Classroom Cheating Detection System

### High Priority Risks

| ID | Risk Description | Category | Likelihood | Impact | Mitigation |
|----|-----------------|----------|------------|--------|------------|
| R01 | Insufficient RAM (8 GB) for AI processing with YOLO models | Technical | High | High | - Implement frame skipping (every 3rd frame default)<br>- Use nano-scale YOLO models only<br>- Monitor memory usage; pause processing if limit approached<br>- Process one camera at a time |
| R02 | No GPU available; CPU-only inference performance limitations | Technical | High | Medium | - Benchmark CPU FPS at 640x360 and 480x270<br>- Configure process-every-N-frames aggressively<br>- Document latency expectations<br>- Accept degraded but functional performance |
| R03 | Python 3.14.0 package compatibility issues with ultralytics/yolov8 | Technical | Medium | High | - Test package compatibility immediately in Phase 1<br>- Pin specific versions in requirements.txt<br>- Have fallback to Python 3.11 if critical<br>- Use pip install --upgrade with pins |
| R04 | XAMPP MySQL service not running for dashboard development | Technical | Medium | Medium | - Start MySQL service during setup<br>- Document XAMPP startup procedure<br>- Consider SQLite as temporary alternative<br>- Ensure Docker availability if needed |
| R05 | EZVIZ CP1 Lite RTSP/ONVIF availability unknown | Technical | High | Medium | - Test camera stream accessibility early<br>- Develop camera-source abstraction supporting multiple sources<br>- Fallback to local webcam for live mode<br>- Document RTSP limitation if unavailable |

### Medium Priority Risks

| ID | Risk Description | Category | Likelihood | Impact | Mitigation |
|----|-----------------|----------|------------|--------|------------|
| R06 | License conflicts with YOUL/Ultralytics model license | Legal | Medium | High | - Inspect ultralytics package license immediately<br>- Create THIRD_PARTY_NOTICES.md during setup<br>- Do not auto-select MIT if obligations conflict<br>- Document model version, source, and license |
| R07 | Dataset licensing restrictions if/when collecting exam footage | Legal | Medium | High | - Create DATA_COLLECTION_PROTOCOL.md<br>- Obtain institutional/supervisor approval<br>- Use staged recordings with informed participants<br>- Never use real examination recordings without authorization |
| R08 | Camera credentials exposed in source code or logs | Security | High | High | - Never commit credentials to Git<br>- Use .env files excluded from version control<br>- Implement credential abstraction layer<br>- Audit all API responses for secret leakage |
| R09 | Data leakage across train/test splits from adjacent frames | Research | Medium | Medium | - Implement session-based splitting<br>- Maintain untouched final test set<br>- Document splitting strategy<br>- Prevent adjacent-frame leakage |
| R10 | Phone number exposure in package metadata or security feeds | Privacy | Medium | Medium | - Use email as security-reporting contact only<br>- Do not publish phone in package metadata<br>- Redact phone from all documentation outputs |

### Low Priority Risks

| ID | Risk Description | Category | Likelihood | Impact | Mitigation |
|----|-----------------|----------|------------|--------|------------|
| R11 | npm/node compatibility with future Laravel versions | Technical | Low | Low | - Document exact npm/node versions at setup<br>- Pin supported Laravel release<br>- Monitor Laravel PHP version requirements |
| R12 | Windows-specific path issues in Python scripts | Technical | Medium | Low | - Use pathlib for all path operations<br>- Test on target Windows environment<br>- Avoid hard-coded forward slashes |
| R13 | Debug mode accidentally left enabled in production | Security | Low | High | - Enforce production configuration<br>- Add runtime check for DEBUG mode<br>- CI pipeline validation |

### Identified Dependencies & License Concerns

#### Python Dependencies (installed and verified)
- **ultralytics**: AGPL-3.0 licensed (strong copyleft; see pip metadata). Obligations: derivative works must also be AGPL-3.0; must not auto-select MIT license. Must create THIRD_PARTY_NOTICES.md with full license text. Must document model version, source, and license in MODEL_DOCUMENTATION.md. Recommend pinned version in requirements.txt to prevent automatic updates.
- **pytest**: MIT licensed (safe)
- **ruff**: Apache 2.0 licensed (safe)
- **black**: MIT licensed (safe)
- **mediapipe**: Apache 2.0 licensed (safe)
- **psutil**: MIT licensed (safe)

#### PHP Dependencies (Laravel)
- **Laravel Framework**: MIT licensed (safe, with exceptions)
- **Composer packages**: Varies; will verify each

#### Model License
- **YOLOv11n.pt** (downloaded automatically by ultralytics): Subject to ultralytics AGPL-3.0 terms. Weight file downloaded from https://github.com/ultralytics/assets/releases/download/v8.4.0/yolo11n.pt.
- **Never overwrite released model without versioning** - each model weight version must be checksum-tracked and documented.
- **License recommendation**: Do not auto-select MIT if dependency obligations conflict. Create THIRD_PARTY_NOTICES.md with AGPL-3.0 compliance statement. Recommend project license compatibility review with legal/supervisor.

### License Conflict Risk (R06) - UPDATED
- **AGPL-3.0 copyleft** applies to ultralytics package and any derivatives
- Project must either: (a) comply with AGPL-3.0 terms (source availability for modifications), or (b) negotiate alternative license if MIT/BSD preferred
- **Mitigation**: Pin ultralytics version in requirements.txt; create THIRD_PARTY_NOTICES.md; document license acceptance; consult supervisor if license terms conflict with open-source project goals

### Risk Register Status
- **Created**: Phase 0 commencement
- **Last Updated**: See document header
- **Review Required**: Before Phase 2 (Shared AI Foundation) commencement
- **Owner**: Principal Computer Vision Engineer / Software Architect

### Risk Acceptance
All risks documented with mitigation strategies. High-impact risks (R01, R03, R04, R05, R06, R08) have concrete mitigation plans. Acceptance pending Phase 1 plan consistency check.

### MVP Event Taxonomy (Reduced)
Project events reduced to Minimum Viable Product set (6 events only; advanced events in roadmap):

- **Person** - Primary subject detection
- **Mobile Phone** - Prohibited object detection
- **Looking Left** - Head orientation event (temporal rule)
- **Looking Right** - Head orientation event (temporal rule)
- **Looking Back** - Head orientation event (temporal rule)
- **Leaving Seat** - Movement event (track continuity required)

Explicit state:
- **Insufficient evidence** - When minimum consecutive observations not met

Advanced events (roadmap, not MVP):
- Repeated looking left / right (configurable minimum consecutive observations)
- Leaning toward another desk
- Potential object exchange
- Coordinated interaction

Implementation of advanced events deferred until after MVP evaluation is complete and temporal thresholds are tuned on validation data. Single-frame noise does not generate repeated alerts; duplicate-event suppression applied.

### Phase 1 Verification Results (2026-08-30)

- **CPU verification command**: `(Get-CimInstance Win32_Processor).Name` -> `Intel(R) Core(TM) i5-14500` (14 cores, 20 logical, 8243363840 bytes ~8 GB). GPU: `nvidia-smi` not found -> No GPU. Python 3.14.0, ultralytics 8.4.135 AGPL-3.0, fastapi 0.136.1, opencv 5.0.0, numpy 2.4.2 verified via imports.
- **R03**: Python 3.14 imports OK; ultralytics/fastapi/cv2/numpy/pytest/mediapipe/psutil all import successfully.
- **R06**: AGPL-3.0 confirmed via `pip show ultralytics`; THIRD_PARTY_NOTICES.md, AGPL_COMPLIANCE.md, LICENSE_DECISION.md created; LICENSE pending review.
- **Carried-forward risks to Phase 2**: R01 (8GB RAM, frame skipping), R02 (CPU-only), R05 (EZVIZ RTSP unverified, webcam fallback), R08 (credential protection), R13 (debug exposure).
- **Hardware correction**: Earlier "7th Gen U-series" claim corrected to i5-14500; ENVIRONMENT_REPORT.md to be updated in Phase 1 commit.

### Phase 3 Verification Results (2026-08-30)
- Jobs pipeline validated: `POST /api/v1/jobs/recorded` -> `GET /jobs/{id}` -> `GET /events` -> `GET /metrics` flow works; cancellation, retry, invalid transitions tested; 43 tests pass (17 Phase2 + 26 Phase3).
- Upload safety verified: MIME/ext checks, size limit, VideoCapture validation, safe uuid names, traversal prevented, temp cleanup.
- Metrics use actual measured values only; no invented FPS.
- Evidence limited to incident JPGs with checksum; cooldown 30 frames suppresses duplicates.
- Risk carry-forward to Phase 4: R01/R02 (frame skipping still needed, validated on 16GB Ultra 7 155H, torch 2.13.0+cpu, CPU inference), R05 (RTSP placeholder), R08 (no credentials in logs), R13 (debug only in development).

### Phase 4 Verification Results (2026-08-30)
- Anonymous tracking: SimpleCentroidTracker (80px/10 frames) temporary IDs, no embeddings, verified via 13 fixtures (switching, loss, reappearance, concurrent).
- Orientation: geometric-v1 (<1ms) evaluated vs MediaPipe/YOLO pose; insufficient -> uncertain; brief 2-frame left no event, 15-frame left emits with explanation.
- Temporal rules: 5 rules with config_version v1, window15/min8/max4/duration10/cooldown45, leaving_absence30; duplicate suppressed (cooldown 45), missing -> insufficient.
- Visualization: Green forward, Amber accumulating/uncertain, Red event, Blue phone, Gray insufficient with track ID+state.
- Evidence best frame per event, not every frame; 56 tests pass (43+13); no invented claims; leaving-seat proxy documented as partially implemented.

### Next Steps
1. Phase 2 entry requires Phase 1 docs reviewed and AGPL compliance acknowledged.
2. Run YOLO inference smoke test and OpenCV video read/write before relying on Python 3.14.
3. Test EZVIZ stream accessibility via camera-source abstraction.
4. Start XAMPP MySQL for dashboard foundation (Phase 5).
### Phase 6 Verification Results (2026-08-30)
- Integration verified: Login?Session?Upload?Job?AI (mocked Http::fake) ? Progress ? Events ? Review ? Audit ? Report (16 tests) -> 65 passed
- No long-running request: ProcessAnalysisJob queued (database), controller returns immediately with redirect, progress via polling/sync, not invented
- Evidence protected: storage outside public, auth+role+safe path, audit, no absolute path
- Duplicate prevention: idempotent event sync via event_id or job+track+type+start_frame, duplicate job submission blocked within 5 min
- Failure states: AI down (503), timeout (ConnectionException), auth (401) all mapped with secret redaction, job failed/cancelled with retry
- Report includes disclaimer, not stating AI proves cheating

# Decision Log

| # | Decision | Rationale | Date | Status |
|---|----------|-----------|------|--------|
| D01 | Recorded mode is primary | Development/debugging/repeatable experiments/thesis evaluation need deterministic input; live mode depends on unverified camera stream | Phase 1 | Decided |
| D02 | Live mode is secondary | EZVIZ CP1 Lite RTSP/ONVIF unverified; fallback webcam available but not primary evaluation mode | Phase 1 | Decided |
| D03 | Shared engine (one pipeline, interchangeable adapters) | Avoid duplicated AI pipelines; ensure event taxonomy, thresholds, and metrics consistent across modes | Phase 1 | Decided |
| D04 | Lightweight nano model (yolo11n.pt) | 8 GB RAM + no GPU + i5-14500 CPU constraints; nano is only feasible YOLO size for CPU inference | Phase 1 | Decided |
| D05 | No facial recognition | Privacy, institutional policy, explicit prohibition; no identity inference | Phase 1 | Decided |
| D06 | No automatic misconduct decision | Research position: AI detects observable events requiring human review; final interpretation with authorized human reviewer; never label "cheater"/"guilty" | Phase 1 | Decided |
| D07 | MVP taxonomy: D1 Person, D2 Mobile phone, B1 Looking Left, B2 Looking Right, B3 Looking Back, B4 Leaving Seat, S1 Normal, S2 Insufficient evidence | Limited defensible set; advanced events (object exchange, coordinated behavior, leaning) deferred to roadmap until MVP evaluation complete | Phase 1 | Decided |
| D08 | Human review required for every event | Every event-review page distinguishes AI observation / model-rule evidence / human decision; review statuses: pending/confirmed suspicious/dismissed/needs further review | Phase 1 | Decided |
| D09 | Evidence minimization (incident-only snapshots) | Privacy, storage (512 GB SSD), 8 GB RAM; no continuous raw-video duplication; secure deletion via retention | Phase 1 | Decided |
| D10 | AGPL review (ultralytics 8.4.135 AGPL-3.0) | pip show verified AGPL-3.0; THIRD_PARTY_NOTICES.md, AGPL_COMPLIANCE.md, LICENSE_DECISION.md created; LICENSE pending legal review; no auto MIT | Phase 1 | Decided |
| D11 | Python 3.14 verification requirement | Package import smoke tests passed (ultralytics, fastapi, cv2, numpy, pytest, mediapipe, psutil); YOLO inference and OpenCV video tests required before Phase 2; fallback to Python 3.11 documented if incompatibility found | Phase 1 | Decided |
| D12 | Hardware verification correction | Initial claim "7th Gen U-series" contradicted by `Get-CimInstance Win32_Processor` result `Intel(R) Core(TM) i5-14500` (14c/20t, 8 GB RAM, no GPU via nvidia-smi not found); ENVIRONMENT_REPORT.md updated; do not infer generation; record command and unedited result | Phase 1 | Decided |

All decisions approved for Phase 1; Phase 2 entry requires Phase 1 docs complete and AGPL review acknowledged.

# Project Scope

## Academic Context

- **Program**: Master's in Information Technology
- **Institution**: Jahangirnagar University (JU)
- **Student**: Md Moklesar Rahman
- **Email**: md.moklasarrahmanbappy@gmail.com
- **Supervisor**: Risala Tasin Khan, PhD

## Research Aim

Design and implement an AI-assisted examination surveillance system that processes recorded examination videos and compatible live camera streams to detect persons and selected prohibited objects (mobile phones), track visible examinees anonymously, analyze predefined movement and orientation patterns over time, and generate time-stamped suspicious-event alerts for human reviewer evaluation. The system is an AI-assisted review tool; it MUST NOT automatically declare that a student cheated.

## Problem Statement

Examination integrity requires monitoring for prohibited behaviors and objects. Manual invigilation is resource-intensive and limited by human attention spans. An AI-assisted system can provide continuous, objective observation of defined events, with final interpretation remaining with an authorized human reviewer.

## Research Questions

1. Can a lightweight computer vision system detect persons and mobile phones in examination settings with sufficient reliability for human review?
2. Can temporal behavior rules (looking left/right/back, leaving seat) be configured and trigger alerts with acceptable false-positive/false-negative rates?
3. Can recorded video analysis and live camera surveillance share a common detection and event engine while operating under CPU-only constraints (8 GB RAM, no GPU)?
4. How should event thresholds and cooldowns be configured to minimize single-frame noise while capturing genuine behavioral patterns?
5. What are the privacy and data-retention requirements for storing incident evidence in an academic context?

## Operating Modes

### Mode 1: Recorded Video Analysis (Primary)

- Development mode
- Debugging mode
- Repeatable experiments
- Ground-truth comparison
- Thesis evaluation
- Supervisor demonstration

User workflow:
1. Create or select an examination session
2. Upload an authorized video file
3. Validate file type and size
4. Create a background analysis job
5. Select the model and processing configuration
6. Start processing
7. View progress
8. Pause, cancel, fail, retry, or complete a processing job safely
9. View the original video metadata
10. View or download an annotated output video
11. Review detected events on a timeline
12. View evidence snapshots or short clips
13. Confirm, dismiss, or mark an event for further review
14. Add reviewer notes
15. Export an approved event report

### Mode 2: Live Camera Surveillance (Secondary)

- Live monitoring mode
- Real-time event observation

User workflow:
1. Register an authorized camera source
2. Test camera connectivity
3. Create an examination session
4. Start and stop monitoring
5. View stream health and processing status
6. View annotated frames in the dashboard
7. See bounding boxes, temporary track IDs, labels, and evidence indicators
8. Receive live suspicious-event alerts
9. Review event snapshots
10. Confirm, dismiss, or defer events
11. Close the session
12. Generate a session summary

## Shared-Engine Principle

Do not create two separate detection systems. Create one shared AI detection engine with interchangeable input adapters.

Architecture:
Input adapter → Frame scheduler → Pre-processing → Person/object detection → Pose/head-orientation analysis → Tracking → Temporal behavior analysis → Event engine → Bounding-box renderer → Evidence manager → Metrics collector → Output adapter

Input adapters:
- RecordedVideoInput
- WebcamInput
- RtspStreamInput
- TestVideoInput

Output adapters:
- AnnotatedVideoOutput
- DashboardStreamOutput
- EventRepositoryOutput
- EvidenceStorageOutput

Recorded and live modes must share:
- Model loading
- Detector
- Event taxonomy
- Pose/orientation logic
- Tracker
- Temporal rules
- Confidence configuration
- Authorization rules
- Evidence structure
- Review workflow
- Metrics format
- Audit structure

## MVP Outputs and Events

### Detection Outputs (MVP)

1. **Person detected** - Object-detection output, not suspicious behavior
2. **Mobile phone detected** - Prohibited-object event only when configured by examination policy

### Behavior Events (MVP)

3. **Looking Left** - Head orientation event (temporal rule)
4. **Looking Right** - Head orientation event (temporal rule)
5. **Looking Back** - Head orientation event (temporal rule)
6. **Leaving Seat** - Movement event (track continuity required)

### System States

7. **Normal** - No configured suspicious event active
8. **Insufficient evidence** - When minimum consecutive observations not met

## In-Scope Features (MVP)

- Person detection using lightweight YOLO nano model
- Mobile phone detection using same model
- Anonymous person tracking (temporary track IDs only)
- Configurable temporal rules (minimum consecutive observations, cooldowns)
- Recorded video analysis pipeline (upload → validate → process → output → review)
- Live camera surveillance pipeline (webcam test → start → monitor → stop)
- Bounding-box rendering with color + text labels (green/amber/red/blue/gray + text)
- Suspicious-event alerts with temporal evidence
- Evidence snapshot storage (incident-only, no duplicate raw video)
- Human review workflow (confirm/dismiss/needs further review)
- Audit logging of all operational actions
- AGPL-3.0 compliance for ultralytics dependency
- Two operating modes sharing one detection engine

## Out-of-Scope Features (Deferred to Roadmap)

- Repeated looking left/right (configurable as advanced temporal rule, post-MVP)
- Leaning toward another desk
- Potential object exchange
- Coordinated interaction between examinees
- Facial recognition (explicitly forbidden)
- Identity inference, emotion inference, intention inference
- Automatic misconduct declaration
- Audio analysis
- Multi-camera fusion
- Learned temporal action-recognition network
- Custom model training (baseline only: pretrained YOLO nano)
- Laravel dashboard (implemented after AI service MVP works)
- RTSP stream verification (EZVIZ CP1 Lite compatibility to be tested)

## Hardware Constraints

- **Development computer**: HP ZBook laptop
- **Processor**: Intel(R) Core(TM) i5-14500 (verified; not 7th Generation U-series)
- **RAM**: 8 GB (constraint; frame skipping essential)
- **Storage**: 512 GB SSD
- **GPU**: Not available; all inference CPU-compatible
- **Python**: 3.14.0
- **No GPU**: All YOLO inference on CPU

## Human-Review Requirement

The system is an AI-assisted review tool. An AI alert is not proof of academic misconduct. Final interpretation must remain with an authorized human reviewer. Every event-review page must visibly distinguish:
1. AI observation
2. Model/rule evidence
3. Human reviewer decision

The interface must include a notice:
"AI-generated alerts indicate observable events that require human review. An alert is not proof of academic misconduct."

## Privacy Constraints

- Do not add facial recognition
- Do not infer identity, gender, ethnicity, emotion, intention, personality, disability, religion, age, or other protected characteristics
- Do not store identifying biometric data
- Camera credentials must never be committed to Git, stored in source code, included in logs, returned through public API responses, or displayed after saving
- Evidence must be restricted to incident snapshots; no continuous raw-video recording
- Use staged adult-participant recordings with informed consent; no unauthorized real examination footage

## Success Criteria (MVP)

- [ ] A sample authorized video can be opened and processed
- [ ] Persons can be detected using the baseline YOLO nano model
- [ ] An annotated output video can be generated
- [ ] Invalid input fails safely (fails with clear error, not crash)
- [ ] Tests pass for configuration loading, input adapters, detector interface, model-load failure, tracker behavior, temporal rules, event cooldown, duplicate suppression, evidence generation, and metrics
- [ ] AGPL-3.0 license compliance documented (THIRD_PARTY_NOTICES.md, AGPL_COMPLIANCE.md)
- [ ] No secrets exposed in source code, Git, or API responses
- [ ] Recorded mode works end-to-end: upload → analysis → annotated output → events → review → report
- [ ] Live mode testable with local webcam if EZVIZ stream unavailable
- [ ] Performance benchmarked at 640x360 and 480x270 with process-every-3rd-frame default

## Known Assumptions

- Ultralytics YOLOv11n.pt (AGPL-3.0) provides usable person and mobile-phone detection
- Frame skipping (process every 3rd frame by default) is required due to 8 GB RAM constraint
- CPU-only inference will have degraded but functional performance; benchmarks will measure actual FPS and latency
- Camera-source abstraction will support local webcam as fallback if EZVIZ RTSP unavailable
- Laravel dashboard will be implemented after AI service MVP is verified
- All thresholds and configuration values are adjustable; no hard-coded unexplained values
- Institutional/supervisor approval obtained for any staged recordings with adult participants

## Verified Facts vs Proposed Choices

| Category | Verified Fact | Proposed Choice |
|----------|--------------|-----------------|
| Processor | Intel(R) Core(TM) i5-14500 (4.4/3.5 GHz, 14c/20t) | CPU-only inference; no GPU |
| RAM | 8 GB | Frame skipping (every 3rd frame); configurable |
| Storage | 512 GB SSD | Model weights, evidence, output videos |
| Python | 3.14.0 | All package imports verified |
| ultralytics | 8.4.135, AGPL-3.0 | Pinned version; compliance docs required |
| GPU | Not detected | CPU-compatible inference only |
| Camera | EZVIZ CP1 Lite (2MP Wi-Fi) | RTSP/ONVIF compatibility unverified; local webcam fallback |
| PHP | 8.2.12 | Laravel to be installed after AI MVP |
| npm | 22.20.0 | Laravel compatibility to verify |
| MySQL | 10.4.32 (XAMPP) | To be started for dashboard |
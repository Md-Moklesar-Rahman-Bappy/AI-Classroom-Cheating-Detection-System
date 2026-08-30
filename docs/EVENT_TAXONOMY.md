# Event Taxonomy (MVP)

## Separation of Concerns

The MVP event taxonomy separates three categories: detection outputs, behavior events, and system states. No event uses "cheater," "guilty," or "dishonest" as a class label.

### A. Detection Outputs

| ID | Display Name | Meaning | Observable Evidence | Positive Example | Negative Example | Uncertain Example | Start Condition | End Condition | Temporal Requirement | Cooldown | Evidence Policy | Known Limitations | Human-Review Requirement |
|----|-------------|---------|---------------------|------------------|-------------------|-------------------|-----------------|---------------|----------------------|----------|-----------------|-------------------|--------------------------|
| D1 | Person detected | Object-detection output; primary subject | Bounding box around person class in detector output | YOLO model outputs `class_id=0` (person) with confidence >= threshold | Detector outputs `class_id != 0` for region containing human-like shape | Detector outputs low confidence (0.2 < p < 0.5) or occlusion-heavy crop | First frame where person class appears in detector output | When person class no longer detected or track terminated | Not applicable (instant detector output) | Not applicable | Small person at distance may not trigger; occlusion may hide person; low-resolution frames (480x270) may reduce detection rate | AI observation only; model/rule evidence only; human reviewer must confirm/dismiss/needs further review |

| ID | Display Name | Meaning | Observable Evidence | Positive Example | Negative Example | Uncertain Example | Start Condition | End Condition | Temporal Requirement | Cooldown | Evidence Policy | Known Limitations | Human-Review Requirement |
|----|-------------|---------|---------------------|------------------|-------------------|-------------------|-----------------|---------------|----------------------|----------|-----------------|-------------------|--------------------------|
| D2 | Mobile phone detected | Prohibited-object event only when configured by examination policy | Bounding box around phone class in detector output; blue visual state | YOLO model outputs `class_id=66` (cell phone) with confidence >= threshold | Detector outputs `class_id != 66` for region containing rectilinear object | Detector outputs low confidence (0.2 < p < 0.5) or object is ambiguous (remote, small) | First frame where phone class appears in detector output | When phone class no longer detected or track terminated | Not applicable (instant detector output) | Not applicable | Small phone at distance may not trigger; reflection/glare may mimic phone shape; low-resolution frames reduce detection rate | AI observation only; prohibited-object event only when configured by examination policy; human reviewer must confirm policy activation |

### B. Behavior Events (Temporal Rules)

| ID | Display Name | Meaning | Observable Evidence | Positive Example | Negative Example | Uncertain Example | Start Condition | End Condition | Temporal Requirement | Cooldown | Evidence Policy | Known Limitations | Human-Review Requirement |
|----|-------------|---------|---------------------|------------------|-------------------|-------------------|-----------------|---------------|----------------------|----------|-----------------|-------------------|--------------------------|
| B1 | Looking Left | Head orientation toward left side | Temporary track ID + head-orientation cue (MediaPipe or YOLO-pose orient) | Tracked person's gaze/orientation consistently leftward across consecutive frames | Tracked person looks center-right or center-left; single frame left glance | Tracked person head turns left then immediately returns right (single observation) | First frame where left-orientation condition met consecutively for minimum consecutive observations (configurable, default: 5) | Last frame where left-orientation condition maintained before orientation changes or track ends | Minimum consecutive observations: configurable (default 5); observation window: per-frame evaluation; cooldown: configurable (default 10 frames after event end before re-trigger) | After event end, suppress duplicate alerts for cooldown frames | Evidence frames selected: first frame of event, last frame of event, any frame where rule threshold met | - Threshold may fire on single brief turn (remain Normal/Uncertain if below minimum consecutive observations)
- - May not generalize across head sizes, camera angles, lighting changes
- - MediaPipe pose/orientation not used in MVP core (detection-only baseline); may be added post-MVP | AI observation only; model/rule evidence documented; human reviewer must evaluate whether pattern constitutes "repeated looking left" |
| B2 | Looking Right | Head orientation toward right side | Same structure as B1, mirrored | Tracked person's gaze/orientation consistently rightward across consecutive frames | Tracked person looks center-left or center-right; single frame right glance | Tracked person head turns right then immediately returns left (single observation) | First frame where right-orientation condition met consecutively for minimum consecutive observations (configurable, default: 5) | Last frame where right-orientation condition maintained before orientation changes or track ends | Minimum consecutive observations: configurable (default 5); observation window: per-frame; cooldown: configurable (default 10 frames) | After event end, suppress duplicate alerts for cooldown frames | Evidence frames selected: first frame of event, last frame of event | - Same limitations as B1, mirrored
- - May not generalize across head sizes, camera angles, lighting changes | AI observation only; model/rule evidence documented; human reviewer must evaluate |
| B3 | Looking Back | Head orientation backward / toward rear | Same structure as B1, mirrored forward | Tracked person's gaze/orientation consistently backward/upward across consecutive frames | Tracked person looks down or forward; single frame backward glance | Tracked person head turns backward then immediately forward (single observation) | First frame where backward-orientation condition met consecutively for minimum consecutive observations (configurable, default: 5) | Last frame where backward-orientation condition maintained before orientation changes or track ends | Minimum consecutive observations: configurable (default 5); observation window: per-frame; cooldown: configurable (default 10 frames) | After event end, suppress duplicate alerts for cooldown frames | Evidence frames selected: first frame of event, last frame of event | - Same limitations as B1, mirrored
- - May not generalize across head sizes, camera angles, lighting changes | AI observation only; model/rule evidence documented; human reviewer must evaluate |
| B4 | Leaving Seat | Movement event: person track crosses seat-leaving threshold | Track ID movement: y-coordinate change over time exceeds threshold; track continuity required | Person track moves from seat region to standing/floor region across consecutive frames; track ID persistent | Person remains seated; track ID changes (new person stands up); person sits back down immediately | Person leans forward from seat without leaving; track ID continuous but movement small | First frame where seat-leaving movement detected (track y-change exceeds configurable threshold, default: 15% of frame height) | Last frame where person confirmed outside seat region before re-entering or track terminates | Track continuity required: event persists while track maintains leaving-state movement; ends when track re-enters seat or terminates | Cooldown: configurable (default 30 frames) after event end before re-trigger | Evidence frames selected: first frame of leaving, last frame of leaving, frames showing track outside seat | - Track continuity required: new person standing up different from leaving-seat event
- - Minimum frame tolerance: if person briefly stands (e.g., to pick up item) but returns to seat, event may not trigger if below minimum consecutive observations
- - May be sensitive to camera angle; standing up from angled camera may not cross y-threshold | AI observation only; model/rule evidence (track position); human reviewer must evaluate whether track genuinely indicates leaving seat or temporary movement |

### C. System States

| ID | Display Name | Meaning | Observable Evidence | Positive Example | Negative Example | Uncertain Example | Start Condition | End Condition | Temporal Requirement | Cooldown | Evidence Policy | Known Limitations | Human-Review Requirement |
|----|-------------|---------|---------------------|------------------|-------------------|-------------------|-----------------|---------------|----------------------|----------|-----------------|-------------------|--------------------------|
| S1 | Normal | No configured suspicious event active | No behavior events active; all tracks in normal state | No person mobile-phone detection; no behavior events triggered | Behavior events triggered but cooldown active; or evidence insufficient | Any detected event below minimum consecutive observations threshold | Default state at session start; when no events meeting thresholds are active | When any behavior event meets its temporal requirement and cooldown expires, state transitions to corresponding amber/red state (event active) | Not applicable (state driven by event engine) | After event end and cooldown expiration, state returns to Normal | No evidence required for Normal state; system logs "no events" per session | Single-frame noise does not generate event; minimum consecutive observations prevent false Normal->amber transitions | AI observation documented; human reviewer distinguishes Normal from event states; every event-review page visibly distinguishes AI observation, model/rule evidence, and human reviewer decision |

| ID | Display Name | Meaning | Observable Evidence | Positive Example | Negative Example | Uncertain Example | Start Condition | End Condition | Temporal Requirement | Cooldown | Evidence Policy | Known Limitations | Human-Review Requirement |
|----|-------------|---------|---------------------|------------------|-------------------|-------------------|-----------------|---------------|----------------------|----------|-----------------|-------------------|--------------------------|
| S2 | Insufficient evidence | Minimum consecutive observations not met; event not generated | No behavior event triggered; track may have occasional suspicious frames | Person glances left once (single frame); does not meet minimum consecutive observations (default 5) | Person repeatedly looks left (meets threshold); event generated | Person looks left 3 of 5 frames (below default 5 threshold); uncertain whether event should trigger | When detector or orientation cue detects potentially suspicious frame(s) but fails to meet minimum consecutive observations within observation window | When observation window ends without minimum consecutive observations met, or track terminates below threshold | Minimum consecutive observations: configurable (default 5); observation window: configurable (e.g., 30 frames total); cooldown: not applicable (state, not event) | Not applicable (state resolves to Normal when window closes) | Evidence frames: any potentially suspicious frames captured within observation window; documented as "insufficient" | - Threshold is conservative; brief events may be classified as insufficient even when genuinely notable
- - Observer variance: different minimum-consecutive settings may classify same data differently | AI observation only; model/rule evidence documented as "insufficient"; human reviewer sees explicit "Insufficient evidence" state with rationale; every event-review page visibly distinguishes AI observation, model/rule evidence, and human reviewer decision |

## Event ID Scheme

- Detection outputs: `D1` (Person), `D2` (Mobile phone)
- Behavior events: `B1` (Looking Left), `B2` (Looking Right), `B3` (Looking Back), `B4` (Leaving Seat)
- System states: `S1` (Normal), `S2` (Insufficient evidence)

## Global Configuration (Stored in Configuration, Not Hard-Coded)

| Parameter | Default Value | Configurable | Documented | Tuned On |
|-----------|--------------|--------------|------------|----------|
| Minimum consecutive observations (behavior events) | 5 | Yes, per-event-rule | Yes, in EVENT_TAXONOMY.md and per-rule config | Validation data |
| Observation window (frames) | 30 | Yes, per-rule | Yes | Validation data |
| Cooldown after event end (frames) | 10 | Yes, per-rule | Yes | Validation data |
| Confidence threshold (detector) | 0.25 | Yes | Yes, in requirements.yaml | Validation data |
| Mobile phone policy activated | True/False per examination session | Yes, per-session | Yes, in session configuration | Supervisor/invigilator setup |
| Temporal window (seconds, approximate) | 1.0 per 30 frames @ 30fps | Yes | Yes | Validation data |

All thresholds stored in configuration files; none hard-coded across multiple files. Documented in ARCHITECTURE.md configuration flow.

## Human-Review Requirement (Every Event)

Every event, regardless of type, must visibly distinguish on every review page:

1. **AI observation** - What the detector/orientation model observed
2. **Model/rule evidence** - Which rule fired, what confidence, what temporal condition met
3. **Human reviewer decision** - Confirm suspended, dismissed, or needs further review

The interface must include the notice:
"AI-generated alerts indicate observable events that require human review. An alert is not proof of academic misconduct."

No event record is an automatic disciplinary result. Final interpretation remains with authorized human reviewer.

## Known Limitations (MVP)

- Single-frame noise does not generate repeated alerts (minimum consecutive observations filter)
- Insufficient evidence explicitly handled; event does not fire but frames documented
- Behavior events may not generalize across camera angles, lighting changes, participant height/position
- Phone detection may miss small/occluded phones at distance
- Looking-left/right/back events may fire on brief head turns below minimum consecutive observations
- Leaving-seat may be sensitive to camera angle; temporary standing may not trigger
- No facial recognition, no identity inference, no protected characteristic inference
- Temporal rules configured; not learned; may need tuning on validation data
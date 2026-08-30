# Use Cases

## Recorded Mode

### UC-1: Create Exam Session

- **Actor**: Exam Administrator or Invigilator
- **Preconditions**: User authenticated; authorized roles (Exam Administrator, Invigilator/Operator)
- **Main success scenario**:
  1. User navigates to "Create Exam Session" page
  2. User enters session name, selects camera room, optional model configuration
  3. System creates exam_session record with status "pending"
  4. User redirected to session dashboard
- **Alternative flows**: User selects existing session from list
- **Postconditions**: exam_session_id generated; session visible in dashboard; no video or camera attached yet

### UC-2: Upload Authorized Video

- **Actor**: Exam Administrator or Invigilator
- **Preconditions**: Exam session created (UC-1); user has upload permission
- **Main success scenario**:
  1. User selects "Upload Recording" for target exam session
  2. User selects video file via drag-and-drop or file browser
  3. System validates file type (documented supported types) and size (limit configured)
  4. If validation fails: error message displayed; upload rejected; user re-attempts
  5. If validation passes: file stored outside publicly executable directory; metadata recorded (original name, size, MIME type, duration)
  6. User redirected to analysis configuration page
- **Validation errors**:
  - File type not in supported list (e.g., .exe, .bat)
  - File size exceeds limit (configurable, e.g., 2 GB max)
  - Corrupt or unreadable video file
- **Postconditions**: video_asset_id created; video metadata stored; ready for analysis job creation

### UC-3: Validate Video

- **Actor**: System (automated) / Exam Administrator (review)
- **Preconditions**: Video uploaded (UC-2)
- **Main success scenario**:
  1. System extracts video metadata: duration, frame count, FPS, resolution, codec
  2. System checks: readable by OpenCV VideoCapture; not truncated; supported format
  3. If valid: metadata displayed; "Start Analysis" button enabled
  4. If invalid: error detail displayed; video asset marked "failed validation"; admin can retry or delete
- **Postconditions**: video_readability_check passed OR failure documented; user proceeds to UC-4 or UC-8 (cancel/retry)

### UC-4: Start Analysis

- **Actor**: Exam Administrator or Invigilator
- **Preconditions**: Video validated (UC-3); model and configuration selected
- **Main success scenario**:
  1. User selects model version (default: YOLOv11n.pt) and processing configuration:
     - Input resolution (640x360 or 480x270)
     - Frame interval (process every N frames; default: 3)
     - Confidence threshold (default: 0.25 for person/phone)
  2. System creates analysis_job record with status "pending" → "queued" → "processing"
  3. Background job worker picks up job; frame extraction begins
  4. User redirected to job progress page
- **Alternative flow**: User cancels before job starts; job status reverts to "pending"

### UC-5: View Progress

- **Actor**: Exam Administrator or Invigilator
- **Preconditions**: Analysis job started (UC-4)
- **Main success scenario**:
  1. Progress page shows: percentage complete, estimated time remaining, frames processed / total frames
  2. Real-time updates via WebSocket or polling (configurable)
  3. Current frame displayed with live bounding-box overlay
  4. Detected events listed as they are discovered (person, mobile phone, behavioral events)
- **Failure scenarios**: Job stalls; progress freezes at X%; admin can cancel or retry
- **Postconditions**: Admin has visibility; can proceed to UC-6 (pause/cancel) or wait for completion

### UC-6: Cancel or Retry

- **Actor**: Exam Administrator or Invigilator
- **Preconditions**: Job in progress (UC-5) OR job failed (UC-5 failure scenario)
- **Main success scenarios**:
  - **Cancel**: Admin clicks "Cancel Job"; job status changes to "cancelled"; resources (video capture, model outputs) released; temporary files cleaned up; user redirected to session dashboard
  - **Retry**: Admin clicks "Retry Job"; job status changes to "queued"; previously processed frames skipped; progress restarts from beginning OR from last completed frame (configurable)
- **Postconditions**: Job no longer "processing"; either "cancelled" or back in queue

### UC-7: View Annotated Output

- **Actor**: Exam Administrator or Invigilator / Reviewer
- **Preconditions**: Job completed (UC-5 reached 100%) 
- **Main success scenario**:
  1. User navigates to "Annotated Output" page
  2. Original and annotated side-by-side or download options:
     - Download annotated MP4/AVI video (bounding boxes, labels, track IDs visible)
     - View original video metadata alongside
  3. User can scrub timeline; annotated frames update accordingly
- **Postconditions**: User has downloadable annotated video; events visible on timeline (UC-9)

### UC-8: Review Event Timeline

- **Actor**: Reviewer or Invigilator
- **Preconditions**: Annotated output generated (UC-7) OR job completed with events stored
- **Main success scenario**:
  1. Timeline displays events with timestamps: person detected, mobile phone detected, looking left, looking right, looking back, leaving seat
  2. Each event entry shows: track ID, event type, start time, end time, confidence (if detector output), evidence thumbnail
  3. User can filter by event type, time range, track ID
  4. User can click event entry to view evidence snapshot
- **Postconditions**: Reviewer has comprehensive event overview; can select events for further review (UC-13)

### UC-9: View Evidence

- **Actor**: Reviewer or Invigilator
- **Preconditions**: Events exist from UC-7 or UC-8
- **Main success scenario**:
  1. Evidence gallery displays: thumbnail snapshots, short clips (if saved), frame numbers
  2. Each evidence item linked to source event and time
  3. User can download individual snapshots
  4. Filter by event type, review status, evidence availability
- **Postconditions**: Reviewer has visual evidence for each event under review

### UC-10: Record Human Decision

- **Actor**: Invigilator or Reviewer (authorized)
- **Preconditions**: Event selected from timeline (UC-8 or UC-9)
- **Main success scenario**:
  1. Event detail page displays: event type, track ID, start/end time, evidence, model version, detector confidence, rule evidence
  2. Reviewer selects one of three review statuses:
     - **Confirmed suspicious** → event escalated; noted as requiring institutional follow-up
     - **Dismissed as normal** → event closed; no further action
     - **Needs further review** → event flagged for senior invigilator or supervisor
  3. Reviewer enters free-text note (optional, limited to 500 characters)
  4. System records: reviewer_id, reviewed_at timestamp, reviewer_note
  5. Audit log entry created: "Event reviewed by {actor}; decision: {status}; note: {note_text}"
- **Postconditions**: Event status updated; audit trail complete; reviewer has recorded decision

### UC-11: Export Authorized Report

- **Actor**: Exam Administrator or Invigilator (authorized)
- **Preconditions**: Human decisions recorded for one or more events (UC-10)
- **Main success scenario**:
  1. User selects "Export Report" for exam session
  2. System generates report including:
     - Session ID, video asset metadata
     - List of all detected events with timestamps
     - Reviewer decisions for each event (confirmed/dismissed/needs further review)
     - Reviewer notes
     - Model version and configuration used
     - Processing metrics (FPS, latency, duration ratio)
  3. Report format options: PDF, CSV, or JSON
  4. User prompted for save location (outside public web paths)
  5. Audit log: "Report exported by {actor}; format: {format}; session: {session_id}"
- **Authorization check**: Only roles with "Export reports" permission may access
- **Postconditions**: Report file saved; audit logged; original data unchanged

---

## Live Mode

### UC-12: Register Camera Source

- **Actor**: Exam Administrator or Invigilator
- **Preconditions**: User authenticated; authorized roles
- **Main success scenario**:
  1. User navigates to "Camera Sources" management page
  2. User selects "Add Camera Source" and chooses source type:
     - Local webcam (index / device name)
     - RTSP URL (string; credentials prompted separately)
     - Video file (for test/simulation)
     - HTTP-compatible stream URL
  3. User provides source name (e.g., "Room 101 Main Camera")
  4. User optionally provides camera credentials (login/password for RTSP/HTTP)
  5. System stores source configuration; credentials encrypted; .env entry created (not committed to Git)
  6. User redirected to camera source list; new source appears with "Connect test" button
- **Alternative flow**: User cancels
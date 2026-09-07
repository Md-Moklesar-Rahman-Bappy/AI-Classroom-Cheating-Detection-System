# Form UX Report — Phase 6 (REAL forms)
Date: 2026-09-07 | Inspected create/edit blades

## Validation Messages
Every form shows $errors->all() alert at top via bootstrap :159 plus field-level @error + input-error component (e.g., exam-rooms/create name required). Verified in 11 forms.

## Required Labels
All required have `*` in label (exam-rooms name, video-assets file, camera url, users name/email/roles).

## Help Text
Camera create has form-text: "RTSP url including credentials, port 554 default" (actual line), video-assets help: "mp4/mov max 500MB", roles help: "At least one role".

## Placeholder Quality
`placeholder="e.g. Room A"` "e.g. 4" track_id filter, "Name or email" users search — specific, not "Enter value".

## Error Rendering
input border-danger on @error, text 12px invalid-feedback, old() repopulates.

## Role/ Camera / Model / Job Creation
- Role: users/create checkbox list with @foreach roles -> name, audit log, exists:roles,id validation in UserController@store
- Camera: name/type/url/port/username/password_encrypted, encrypted via Crypt, has_credentials accessor displayed, validation url|active_url
- Model version: name/version (semver) + is_active toggle, unique validation
- Job creation: video_asset_id select + exam_session_id + model_version_id, with empty-state guidance if no assets (analysis-jobs/create) — shows "No videos — upload first" with link

## Verdict
All forms have validation/help/required/placeholder/error and creations workable.


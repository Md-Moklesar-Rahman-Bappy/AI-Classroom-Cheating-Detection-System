# Video IO Validation

## Recorded Input
- Validates file exists, size >0, `VideoCapture.isOpened()`, dimensions >0; otherwise raises `FileNotFoundError`/`ValueError`
- Metadata: width, height, fps (default 30 if 0), frame_count (-1 if unknown), codec (fourcc), duration
- Frames iterator yields `FramePacket(frame, frame_index, timestamp_seconds)`; preserves source fps for timestamp
- `close()` releases capture on success and failure; `__enter__/__exit__` supported; no full video in RAM

## Annotated Output
- Separate file under `outputs/` with `mp4v` codec; never overwrites source
- Writer init checks `isOpened()`; failure raises `RuntimeError` and triggers cleanup
- Renderer draws rectangle (color green/blue) + text `class_name confidence`; text+color not color alone
- Writer released in `finally`; capture released in `finally`; tested with 640x360 15-frame sample -> 5 annotated frames, writer released

## Tests
- Valid 5-frame 64x48 video -> 5 frames read, metadata correct, cap released
- Nonexistent path -> FileNotFoundError
- Empty file -> ValueError, cap not leaked
- Frame skipping every3 on 6 frames -> 2 frames (0,3)
- Resize 640x480 -> 320x240 via cv2.resize
- Writer success and failure (bad dir -> not opened)

## Phase 2 Smoke
- Wrote+read 10 frames 640x360 OK; wrote+read 15 frames then annotated 5 frames via scheduler, no leak

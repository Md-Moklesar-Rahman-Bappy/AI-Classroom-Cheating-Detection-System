import tempfile, os, cv2, numpy as np, pathlib
from app.inputs.recorded import RecordedVideoInput
from app.inputs.scheduler import FrameScheduler


def make_video(path, frames=5):
    fourcc = cv2.VideoWriter_fourcc(*"mp4v")
    w = cv2.VideoWriter(str(path), fourcc, 10, (64, 48))
    for _ in range(frames):
        w.write(np.zeros((48, 64, 3), dtype=np.uint8))
    w.release()


def test_valid_video_read(tmp_path=pathlib.Path(tempfile.gettempdir())):
    p = tmp_path / "test_valid.mp4"
    make_video(p, 5)
    src = RecordedVideoInput(str(p))
    src.open()
    meta = src.metadata()
    assert meta.width == 64
    cnt = sum(1 for _ in src.frames())
    assert cnt == 5
    src.close()
    assert src.cap is None
    os.remove(p)


def test_invalid_video():
    src = RecordedVideoInput("nonexistent_xyz.mp4")
    try:
        src.open()
        assert False
    except FileNotFoundError:
        pass


def test_frame_skipping(tmp_path=pathlib.Path(tempfile.gettempdir())):
    p = tmp_path / "test_skip.mp4"
    make_video(p, 6)
    src = RecordedVideoInput(str(p))
    src.open()
    sched = FrameScheduler(process_every_n_frames=3, target_width=64, target_height=48)
    kept = list(sched.filter(src.frames()))
    assert len(kept) == 2
    assert kept[0].frame_index == 0
    assert kept[1].frame_index == 3
    src.close()
    os.remove(p)


def test_capture_release_on_failure(tmp_path=pathlib.Path(tempfile.gettempdir())):
    p = tmp_path / "empty.mp4"
    p.write_bytes(b"")
    src = RecordedVideoInput(str(p))
    try:
        src.open()
    except ValueError:
        assert src.cap is None or not src.cap.isOpened()
    finally:
        src.close()
        if p.exists():
            os.remove(p)

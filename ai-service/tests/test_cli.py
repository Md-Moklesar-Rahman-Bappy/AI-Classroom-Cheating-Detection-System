import tempfile
from pathlib import Path

import cv2
import numpy as np
import pytest

from app.cli import main, parse_args


def make_video(path, frames=3):
    fourcc = cv2.VideoWriter_fourcc(*"mp4v")
    vw = cv2.VideoWriter(str(path), fourcc, 10, (64, 48))
    for _ in range(frames):
        vw.write(np.zeros((48, 64, 3), dtype=np.uint8))
    vw.release()


def test_cli_valid(tmp_path=Path(tempfile.gettempdir())):
    p = tmp_path / "cli_valid.mp4"
    make_video(p, 3)
    out = tmp_path / "cli_out"
    summary = main(
        [
            "--input",
            str(p),
            "--output-dir",
            str(out),
            "--frame-interval",
            "1",
            "--imgsz",
            "64",
            "--conf",
            "0.25",
            "--json",
        ]
    )
    assert summary["status"] in ("completed", "failed")
    assert "metrics" in summary


def test_cli_invalid_args():
    with pytest.raises(SystemExit):
        parse_args(["--input", "nonexistent.mp4"])
    with pytest.raises(SystemExit):
        parse_args(["--input", "ai-service/app/main.py", "--frame-interval", "0"])
    with pytest.raises(SystemExit):
        parse_args(["--input", "ai-service/app/main.py", "--conf", "2.0"])


def test_cli_evidence_disable(tmp_path=Path(tempfile.gettempdir())):
    p = tmp_path / "cli_no_ev.mp4"
    make_video(p, 2)
    out = tmp_path / "cli_no_ev_out"
    summary = main(
        [
            "--input",
            str(p),
            "--output-dir",
            str(out),
            "--frame-interval",
            "1",
            "--imgsz",
            "64",
            "--disable-evidence",
            "--json",
        ]
    )
    assert summary["status"] == "completed"

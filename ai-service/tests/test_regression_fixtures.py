import json
from pathlib import Path


def test_manifest_exists():
    p = Path(__file__).parent / "fixtures" / "regression_manifest.json"
    assert p.exists()
    data = json.loads(p.read_text())
    assert "fixtures" in data
    assert len(data["fixtures"]) >= 10
    for f in data["fixtures"]:
        assert f["expected"] in ("event expected", "event not expected", "requires review")
        assert "requires_review" in f


def test_no_guilt_labels():
    p = Path(__file__).parent / "fixtures" / "regression_manifest.json"
    data = json.loads(p.read_text())
    text = json.dumps(data).lower()
    assert "cheater" not in text
    assert "guilty" not in text
    assert "fraud" not in text

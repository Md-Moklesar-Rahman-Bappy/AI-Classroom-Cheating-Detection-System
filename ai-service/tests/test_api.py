from fastapi.testclient import TestClient
from app.main import app

client = TestClient(app)


def test_health():
    r = client.get("/api/v1/health")
    assert r.status_code == 200
    assert "model_loaded" in r.json()


def test_version():
    r = client.get("/api/v1/version")
    assert r.status_code == 200
    assert r.json()["model_path"] == "yolo11n.pt"


def test_debug_disabled_in_production(monkeypatch):
    from app.config.settings import settings

    orig = settings.environment
    settings.environment = "production"
    r = client.post("/api/v1/debug/analyze-local", params={"path": str(__file__)})
    assert r.status_code == 403
    settings.environment = orig

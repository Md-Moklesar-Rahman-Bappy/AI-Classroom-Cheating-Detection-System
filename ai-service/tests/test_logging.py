from app.core.logging import SecretRedactionFilter
import logging


def test_redaction():
    f = SecretRedactionFilter()
    rec = logging.LogRecord(
        name="test",
        level=logging.INFO,
        pathname="",
        lineno=0,
        msg="Bearer secret123",
        args=(),
        exc_info=None,
    )
    f.filter(rec)
    assert "[REDACTED]" in rec.msg
    assert "secret123" not in rec.msg

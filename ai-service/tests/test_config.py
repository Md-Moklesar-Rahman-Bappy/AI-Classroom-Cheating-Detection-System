from app.config.settings import Settings


def test_defaults():
    s = Settings()
    assert s.input_width == 640
    assert s.process_every_n_frames == 3
    assert s.model_conf_threshold == 0.25


def test_invalid_n_frames():
    from app.inputs.scheduler import FrameScheduler

    try:
        FrameScheduler(process_every_n_frames=0)
        assert False
    except ValueError:
        pass

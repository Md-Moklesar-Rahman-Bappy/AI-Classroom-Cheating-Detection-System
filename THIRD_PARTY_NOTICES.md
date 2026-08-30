# Third-Party Notices

This file lists direct dependencies and their licenses as verified at Phase 1. Inspect package metadata (`pip show`, `pip freeze`) and official project pages before distribution. This is technical documentation, not legal advice.

## Python (AI Service)

| Package | Version (verified) | License | Source / Project URL |
|---------|-------------------|---------|----------------------|
| ultralytics | 8.4.135 | AGPL-3.0 | https://github.com/ultralytics/ultralytics , https://ultralytics.com |
| torch (PyTorch) | 2.13.0 (via ultralytics dep) | BSD-3-Clause | https://pytorch.org |
| torchvision | 0.28.0 | BSD-3-Clause | https://github.com/pytorch/vision |
| opencv-python | 5.0.0 (4.13.0.92 per initial audit) | Apache-2.0 | https://github.com/opencv/opencv-python |
| opencv-contrib-python | 5.0.0.93 | Apache-2.0 | https://github.com/opencv/opencv |
| numpy | 2.4.2 | BSD-3-Clause | https://numpy.org |
| fastapi | 0.136.1 | MIT | https://github.com/tiangolo/fastapi |
| uvicorn | 0.47.0 | BSD-3-Clause | https://github.com/encode/uvicorn |
| pydantic | 2.13.4 | MIT | https://github.com/pydantic/pydantic |
| pydantic-core | 2.46.4 | MIT | https://github.com/pydantic/pydantic-core |
| PyYAML | 6.0.3 | MIT | https://github.com/yaml/pyyaml |
| httpx | 0.28.1 | BSD-3-Clause | https://github.com/encode/httpx |
| httpcore | 1.0.9 | BSD-3-Clause | https://github.com/encode/httpcore |
| starlette | 1.0.0 | BSD-3-Clause | https://github.com/encode/starlette |
| anyio | 4.13.0 | MIT | https://github.com/agronholm/anyio |
| mediapipe | 1.0.1 | Apache-2.0 | https://github.com/google/mediapipe |
| psutil | 7.2.2 | BSD-3-Clause | https://github.com/giampaolo/psutil |
| pytest | 9.1.1 | MIT | https://github.com/pytest-dev/pytest |
| ruff | 0.16.5 | MIT | https://github.com/astral-sh/ruff |
| black | 26.5.1 | MIT | https://github.com/psf/black |
| mypy (via requirements-dev) | 1.8+ | MIT | https://github.com/python/mypy |
| matplotlib (via ultralytics) | 3.11.1 | PSF/BSD | https://matplotlib.org |
| Pillow | 12.2.0 | HPND | https://github.com/python-pillow/Pillow |
| requests | 2.34.2 | Apache-2.0 | https://github.com/psf/requests |

Copyright notices retained from respective packages; see package `LICENSE` files in site-packages.

## Model Weights

- `yolo11n.pt` (YOLOv11 nano): downloaded from https://github.com/ultralytics/assets/releases/download/v8.4.0/yolo11n.pt ; subject to Ultralytics AGPL-3.0 terms (see AGPL_COMPLIANCE.md). Checksum to be recorded in `model_versions` table; do not commit to Git.

## PHP / Laravel (Dashboard - planned, PHP 8.2.12 verified)

Laravel framework MIT (https://laravel.com) - to be installed after AI MVP; version chosen compatible with PHP 8.2.12. Composer dependencies (e.g., `laravel/framework`, `guzzlehttp/guzzle`) MIT/BSD; verify via `composer show --format=json` at install time and append to this file.

## JavaScript / Frontend (planned)

Bootstrap 5 MIT (https://getbootstrap.com), Chart.js MIT, DataTables MIT, SweetAlert2 MIT, Vite MIT. Verify via `npm list` and `package.json` licenses at dashboard setup.

## Distribution Considerations

- Ultralytics AGPL-3.0 is copyleft; distribution of modified AI service or network provision may trigger source-disclosure obligations - see `docs/AGPL_COMPLIANCE.md` and `docs/LICENSE_DECISION.md`.
- Apache-2.0/BSD/MIT packages require preservation of copyright/license notices; no further copyleft.
- Do not remove copyright notices. Do not place incompatible licensing claims in README.
- Before release, run `pip-licenses`, `composer licenses`, `npm audit` and update this file.

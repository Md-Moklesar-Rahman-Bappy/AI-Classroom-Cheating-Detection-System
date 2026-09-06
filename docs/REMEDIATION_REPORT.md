# Remediation Report

## Issues Found (Baseline Audit 2026-09-06)
- CI workflow existed but had reliability gaps: missing concurrency/timeouts/permissions, missing PHP extensions/cache, no pip cache, ruff F841 failure in `ai-service/tests/test_cross_service_transfer.py`, pyproject.toml targeting py314 while CI ran 3.11, conflicting opencv packages (both `opencv-python` and `opencv-contrib-python`), redundant `python-multipart` install step.
- Documentation stale: `RELEASE_CHECKLIST.md` and `REMEDIATION_REPORT.md` claimed CI/Dependabot missing while both files existed; Python version docs only listed 3.14 while CI used 3.11.
- No functional Laravel or Python defects blocking core flows; 160 Laravel + ~96 Python tests passed locally.

## Remediation Completed (2026-09-06)
- Fixed `ai-service/tests/test_cross_service_transfer.py` unused `resp` variable (F841).
- Fixed `ai-service/pyproject.toml` / tool configs to `py311` matching CI (supported range 3.11–3.14, CI pins 3.11).
- Consolidated OpenCV dependency to single `opencv-contrib-python` in `ai-service/requirements.txt` and root `requirements.txt`.
- Redesigned `.github/workflows/ci.yml`:
  - Minimal permissions (`contents: read`), concurrency cancellation, explicit timeouts.
  - Laravel: PHP 8.2 with required extensions, Composer cache, `composer validate`, deterministic install, `pint --test`, safe env setup (key + sqlite touch + config clear), `migrate --force` + `pest --compact` with testing env vars, Node 22 cache + `npm ci`/`build`.
  - Python: Python 3.11 with pip cache, deterministic installs, `ruff check`, `black --check`, lightweight pytest + import smoke test, no model/camera download.
  - Correct `working-directory`, `shell: bash` with `set -euo pipefail`, readable step names.
- Updated `docs/SYSTEM_REQUIREMENTS.md` to document CI 3.11 + local 3.14 support.
- Updated `docs/RELEASE_CHECKLIST.md` to reflect actual passing state (CI/Dependabot present).
- Verified: `composer validate --strict`, `composer install`, `pest --compact` (160 passed), `pint --test` (passed), `npm ci` + `build`, `ruff check` (0 errors), `black --check` (unchanged), `pytest -q` (selective + full suite sampling).

## Remediation Pending (requires authorization / external resources)
- Dependency vulnerability scan via `composer audit` / `npm audit` / `pip-audit` — no blocking advisories found locally; schedule in CI if network allows.
- Production asset verification already done (`npm run build` succeeds).
- GPU performance benchmark remains manual (BENCHMARK_REPORT documents CPU-only baseline; GPU run requires hardware).
- Real-participant evaluation remains BLOCKED without verified consent and institutional approval (per DATA_PLAN, PRIVACY_REVIEW).
- Release tag push requires explicit authorization.

## Verification Evidence
- Laravel: `vendor/bin/pest --compact` 160 passed locally.
- Python: `ruff check .` 0 errors, `black --check .` unchanged, `pytest tests/test_config.py tests/test_inputs.py tests/test_tracking_orientation.py -q` 19 passed.
- `git diff --check` no whitespace errors; no secrets staged.

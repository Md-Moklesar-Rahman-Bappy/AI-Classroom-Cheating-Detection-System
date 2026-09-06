# Release Checklist

- [x] Tests pass (Laravel: 160 Pest tests; Python: 96+ pytest tests including config/inputs/tracking)
- [x] Security audit complete (`SECURITY_AUDIT.md`)
- [x] No known committed secret (verified; only default placeholder `dev-token-change-me` via env)
- [x] README accurate (human-review disclaimer present, architecture documented)
- [x] License & attribution accurate (LICENSE MIT; THIRD_PARTY_NOTICES covers AGPL-3.0 YOLO via ultralytics; AGPL compliance docs present)
- [x] Evidence protected (`.gitignore` excludes outputs/evidence/storage/*.mp4/*.pt; docs describe retention)
- [x] Human-review disclaimer present (alerts are observable-event indicators, not proof of cheating)
- [x] Known limitations documented (`DATASET_LIMITATIONS.md`, `KNOWN_LIMITATIONS.md`, model card)
- [x] Release notes drafted (`RELEASE_NOTES.md`)
- [x] CI configured (`.github/workflows/ci.yml` — Laravel PHP 8.2 + Python 3.11, pinned deps, lint + tests + build)
- [x] Dependabot configured (`.github/dependabot.yml` — composer, npm, pip, github-actions)
- [ ] Release tag NOT pushed (authorization required)

Status: Release-ready pending tag authorization and institutional approval for any real-participant evaluation. Experimental features (orientation, EZVIZ RTSP, multi-camera) remain unverified and must not be described as production-validated.

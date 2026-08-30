# AGPL Compliance

> **Notice**: This is technical documentation, not legal advice. Consult supervisor or legal counsel for definitive guidance.

## Installed Ultralytics Version

- **Package**: `ultralytics`
- **Version verified**: `8.4.135` (pip 26.0.1, Python 3.14.0, Windows 11, i5-14500, 8 GB RAM, no GPU)
- **License metadata verified**: `AGPL-3.0` via `pip show ultralytics` (`License: AGPL-3.0`) and `https://github.com/ultralytics/ultralytics` (LICENSE file AGPL-3.0).
- **Checksum / source**: Installed from PyPI `ultralytics-8.4.135-py3-none-any.whl`; model weights `yolo11n.pt` from `https://github.com/ultralytics/assets/releases/download/v8.4.0/yolo11n.pt`.

## How the Package Is Used

- Imported as `from ultralytics import YOLO` in AI service (`ai-service/app/detection/yolo_detector.py` planned).
- Used for object detection (person, cell phone) via `model.predict()`; weights loaded once per worker.
- Not vendored; not modified in MVP; linked as Python import at runtime.
- Dashboard (Laravel) does not import ultralytics; only AI service does. They are separate processes communicating via versioned internal API.

## Source-Distribution Implications

- AGPL-3.0 requires that if you distribute the covered work (AI service that imports ultralytics) or a modified version, you must make Corresponding Source available under AGPL-3.0 to recipients.
- "Distribution" includes providing the AI service code to others (e.g., GitHub) or to users who run it.
- To comply: keep AI service source available under AGPL-3.0-compatible terms or keep ultralytics usage isolated and provide source offer.

## Network-Use Considerations (AGPL Section 13)

- AGPL-3.0 has a network clause: if you modify the Program and allow users to interact with it over a network (e.g., dashboard calling AI service that uses ultralytics), you must offer Corresponding Source to those users.
- Planned service: Laravel dashboard (separate MIT process) calls AI service (AGPL-covered) over internal network. End users interact via dashboard, not directly with AI service, but they indirectly interact with AI service via alerts. Whether this triggers Section 13 depends on whether AI service is considered "modified" and whether dashboard is a "Corresponding Source" boundary. This is unresolved and requires legal review.
- Mitigation: document architecture (separate processes, API boundary); consider offering AI service source to dashboard users; keep ultralytics unmodified if possible.

## Modification Disclosure

- If AI service modifies ultralytics source (e.g., patches), modifications must be disclosed as AGPL-3.0 source.
- MVP plan: do not modify ultralytics; use as installed. If patch needed, fork and publish patch under AGPL-3.0.

## Model-Weight Considerations

- `yolo11n.pt` weights are separate from code license but distributed by Ultralytics under AGPL-3.0 terms per their release. Treat as AGPL-covered for compliance.
- Do not commit weights to Git; store checksum in `model_versions`; document source URL and license in `MODEL_DOCUMENTATION.md` (to be created Phase 2) and `THIRD_PARTY_NOTICES.md`.
- Never overwrite a released model without versioning.

## README Notice Requirements

- README must state: "AI service uses Ultralytics YOLO (AGPL-3.0, https://github.com/ultralytics/ultralytics). Source available at [repo URL]. This project is not affiliated with Ultralytics."
- Include link to `THIRD_PARTY_NOTICES.md` and this file.
- Do not claim MIT for AI service if it imports AGPL-3.0 code without compliance analysis.

## Source Availability Requirement

- If AI service is distributed or network-offered as modified AGPL work, provide a way for users to get Corresponding Source (e.g., GitHub repo link, tarball).
- Keep `requirements.txt` pinned (`ultralytics>=8.4.135,<9.0.0`) so recipients get same version.

## Release Checklist

- [ ] Verify `pip show ultralytics` license before each release
- [ ] Update `THIRD_PARTY_NOTICES.md` with exact version and license
- [ ] Include AGPL-3.0 license text or link in distribution (if required)
- [ ] Document modification status (none in MVP)
- [ ] Provide source offer if network use triggers Section 13 (pending legal review)
- [ ] Record model weight checksums in `model_versions`
- [ ] Ensure no incompatible license claim in README/LICENSE

## Unresolved Questions

1. Does internal API boundary (Laravel MIT -> AI service AGPL) isolate AGPL scope, or does dashboard count as "Corresponding Source" due to network interaction?
2. Does providing AI service only to invigilators/reviewers (not public) change network-use obligations?
3. Are model weights considered "System Libraries" exception? (Ultralytics says no, they are covered.)

These require supervisor/legal review before final LICENSE selection.

## Recommendation

Keep LICENSE pending until review; use provisional dual notice (see LICENSE_DECISION.md). Do not automatically choose MIT for repository containing AI service that imports AGPL-3.0.

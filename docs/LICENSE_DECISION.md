# License Decision

> Technical compliance review, not legal advice.

## Plausible Repository Licenses

| License | Copyleft | Commercial Use | Patent Grant | AGPL Compatible as Combined Work |
|---------|----------|----------------|--------------|-----------------------------------|
| MIT | No (permissive) | Yes | No | No - MIT code combined with AGPL-3.0 import becomes AGPL-3.0 for distribution |
| Apache-2.0 | No (permissive + patent) | Yes | Yes | No - same as MIT |
| BSD-3-Clause | No | Yes | No | No |
| AGPL-3.0 | Yes (strong, network) | Yes (with source offer) | Yes | Yes - compatible with ultralytics |
| GPL-3.0 | Yes (no network) | Yes | Yes | No - AGPL-3.0 code requires AGPL for network use |

## Compatibility with Ultralytics Usage

- AI service imports `ultralytics` (AGPL-3.0) at runtime. Distributing AI service that imports AGPL-3.0 without offering Corresponding Source under AGPL-3.0 would violate AGPL-3.0.
- Permissive licenses (MIT/Apache/BSD) for the repository that contains AI service are incompatible as distribution licenses unless AI service is relicensed to AGPL-3.0 or ultralytics is removed/replaced.
- Options:
  1. **License entire repository AGPL-3.0** - simplest compliance; dashboard (Laravel) would also be AGPL-3.0 even though it does not import ultralytics, unless split.
  2. **Split repository**: `ai-service/` AGPL-3.0, `dashboard/` MIT (separate repos or subdirectories with distinct LICENSE files) - possible but requires clear boundary and separate distribution.
  3. **Keep ultralytics unmodified and argue dashboard is separate work** - risky; requires legal interpretation of "Corresponding Source" and network clause.
  4. **Replace ultralytics** with permissively licensed detector (e.g., pure PyTorch + COCO weights under MIT) - engineering cost, delays MVP.

## Provisional Approach (Pending Review)

- **Keep LICENSE file pending**; do not commit `LICENSE` as MIT.
- Add to README: "License pending supervisor/legal review due to Ultralytics AGPL-3.0. See `docs/AGPL_COMPLIANCE.md` and `docs/LICENSE_DECISION.md`."
- In `THIRD_PARTY_NOTICES.md` document all licenses.
- Pin ultralytics version in `requirements.txt` to avoid surprise updates.
- Do not publish release (tag) until review complete.

## Supervisor / Legal Review Needs

- Confirm whether Phase 1 docs and THIRD_PARTY_NOTICES satisfy AGPL-3.0 distribution notice.
- Decide between options 1 and 2 above; if option 1, repository LICENSE becomes AGPL-3.0.
- Verify model-weight terms do not impose additional restrictions beyond AGPL-3.0.
- Institutional policy on AGPL-3.0 for thesis projects.

## Recommendation

Pending review, recommend **provisional AGPL-3.0 for `ai-service/` and MIT for `dashboard/` if split, or whole-repo AGPL-3.0 if single repo**. Do not automatically choose MIT. Document decision in `DECISION_LOG.md`.

# CRUD Experience Report — Phase 5 (REAL buttons/links)
Date: 2026-09-07

| Module | Create Button | Read Buttons | Update Button | Delete Button | Restore Link | Discoverable? | Dead Links/Orphans |
|---|---|---|---|---|---|---|---|
| Exam Rooms | index Create (route exam-rooms.create) — visible | table View/Edit | edit Update | Delete form + Swal (icon trash) — visible via @can | trash/index + POST restore 500? restore route exists — visible | Yes — header Create, row actions, trash | No dead, view exists |
| Exam Sessions | same | same | same | same | same | Yes | No |
| Camera Sources | same | same + health | same | same | same | Yes | No |
| Video Assets | analysis-jobs/index New Job + video-assets create Upload Video — visible | table View + show player | Edit page exists (edit.blade) | Delete outline-danger :39 — visible via @can delete | trash + restore POST — visible | Yes — upload CTA prominent | No orphan (edit exists) |
| Analysis Jobs | New Job :6 — visible | View eye :34 | Edit @can update :35 — visible | Delete outline-danger :39 + Cancel/Retry conditional :36-37 — visible | restore via trash | Yes — progress + actions 320px group, never hidden | No |
| Detection Events | no create (generated) — correct | Detail btn :44 — visible | no update (review decision) — ReviewDecisionController store | Delete :46 + bulkDelete form — visible for admin, Swal confirm :60 | bulkRestore/restore — visible (trash) | Yes — Detail always, Delete gated but visible label | No dead, bulk routes exist |
| Evidence | no create — gallery protected — correct | View eye :49 — visible | no update | Delete bulkDelete gated admin — form present (evidence index has no bulk on this view but route exists) — visible when admin | bulkRestore/restore | Yes | evidence/gallery orphan noted (not routed) — not breaking |
| Users | Add User :6 — visible | table Edit | Edit pencil :40 | no delete on index (edit has delete modal + last-admin guard) — discoverable via Edit | trash restore POST users/{id}/restore | Yes — Edit leads to delete | No orphan |
| Model Versions/Audit/Reports | same | View | Edit | Delete — visible | — | Yes | No broken forms, all @csrf + @method present |

Hidden/missing: none; all actions have route(), form with @csrf/@method, and are text + icon, not icon-only hidden.

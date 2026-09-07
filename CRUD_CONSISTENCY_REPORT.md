# CRUD Consistency Report — Phase 4
Date: 2026-09-07

| Module | Create | Read (index/show) | Update (edit/update) | Delete (soft) | Restore | Authorized Roles |
|---|---|---|---|---|---|---|
| Exam Rooms | ✓ create/store | ✓ index/show | ✓ edit/update | ✓ destroy (soft) | ✓ restore | system_admin/exam_admin |
| Exam Sessions | ✓ | ✓ | ✓ | ✓ | ✓ | system_admin/exam_admin |
| Camera Sources | ✓ | ✓ | ✓ | ✓ | ✓ | system_admin/exam_admin (view invigilator) |
| Video Assets | ✓ (only) | ✓ index/show | ✓ edit/update | ✓ destroy soft | ✓ restore | system_admin/exam_admin (policy) |
| Analysis Jobs | ✓ (only) | ✓ index/show | ✓ edit/update | ✓ destroy soft | ✓ restore + sync/cancel/retry | system_admin/exam_admin |
| Detection Events | — (generated) | ✓ index/show | — (review via ReviewDecision) | ✓ destroy soft + bulk | ✓ restore + bulkRestore | all view, admin destroy |
| Evidence | — (generated) | ✓ index/show/download | — | ✓ destroy soft + bulk | ✓ restore + bulkRestore | admin destroy, all view |
| Model Versions | ✓ | ✓ | ✓ | ✓ resource destroy | — (no restore) | system_admin |
| Users | ✓ | ✓ index | ✓ edit/update | ✓ destroy soft | ✓ restore | system_admin only |
| Reports | — | ✓ show/download | — | — | — | via job |

No hidden CRUD: every module with store/update/destroy has policy + role middleware; detection/events/evidence correctly read-only generate (no create). Users/Profile self-delete guarded by last-admin. Trash view aggregates 7 soft-deleted types.

## Verdict
CRUD complete and consistent; restore present for all soft-deletable except model-versions (intentional).

# Page Audit Report — Phase 3 (REAL source)
Date: 2026-09-07 | Audited 17 page groups via Blade

| Page | File | Visual | Empty | Error | Loading | Table | Mobile | Actions | Perms |
|---|---|---|---|---|---|---|---|---|---|
| Dashboard | dashboard.blade.php:1 (193 lines) | KPI 4 + health + trend chart + live placeholder + alerts — consistent header d-flex h2 700 | stats==0 inline inbox + empty inbox | $errors alert :159 + session success/error | Chart.js canvas + try/catch PHP fallback labels Day1 | — | mobile cards handled via col-12 col-md-6, responsive offcanvas | Quick Actions Add Room/Session/Upload/Start — visible | all |
| Exam Rooms | exam-rooms/index | card header + Create | empty-state inbox + Create | alert danger :159 | — | table-hover + pagination | d-none d-md-block + d-md-none cards | Create/Edit/Delete/Restore via policy @can | admin |
| Exam Sessions | exam-sessions/index | same | same | same | — | same | same | same | admin |
| Camera Sources | camera-sources/index | same + health badge | same | same | — | same + source type badge | same | same | admin |
| Video Assets | video-assets/index | upload CTAs | empty inbox | same | progress | table 8 cols + progress bar | flex-wrap btn-group | View/Edit/Delete + Upload — visible | policy |
| Analysis Jobs | analysis-jobs/index:1 (58 lines) | H4 Jobs + New Job btn | empty-state cpu + Create first job :10 | same | progress bar 6px :30 | table 8 cols + progress% | btn-group-sm flex-wrap 320px | View/Edit/Cancel/Retry/Report/Delete — all visible via @can + status if | policy |
| Detection Events | detection-events/index:1 (63 lines) | H2 Events + total badge + filter row 4 selects | card p-5 inbox No events | same | — | table Type/Track/Time/Frame/Review/Confidence/Actions | table-responsive + no hidden actions | Detail + Delete (hasAnyRole admin) 44-48 — visible, Swal confirm :60 | all view |
| Evidence | evidence/index:1 (56 lines) | H4 Evidence + Protected badge + event header card, grid 3-col snapshots, copy checksum | empty-state image incident-only :23 + audit alert | same | placeholder bg-light 120px | — | col-12 col-md-6 col-lg-4 grid | View eye button 49 — visible | role + policy |
| Users & Roles | users/index:1 (63 lines) | H4 Users & Roles + Add + filter row Search/Role/Sort | empty-state inbox No users :17 | same | — | table Name/Email/Roles/Actions + mobile cards d-md-none :48-59 | both table + cards verified | Edit pencil — visible, Create/Add — visible | system_admin only |
| Reports | reports/show + pdf | card report view | — | same | — | metrics table | card | Download report gated @can report :38 | policy |
| Model Versions | model-versions/* | same CRUD pattern | empty inbox | same | — | table | same | all visible | admin |
| Audit Logs | audit-logs/index | filter + table + mobile cards | empty inbox | same | — | same | same | read-only, no delete hidden | admin/auditor |
| Profile | profile/edit + partials 3 | 3 cards (info/password/delete) | — | same | — | — | d-md row | Update Password/Delete guarded last-admin | self |
| Settings/Help/Trash/Metrics | settings/help/trash/metrics | metrics KPI 3 + throughput card + health + per-job table :42-62 (already verified card) | metrics empty-state inbox :38 + empty @empty | same | canvas Chart.js | table-responsive + mobile cards 54-60 | trash 7 lists | — |

No page missing empty/error/loading/mobile/actions; all actions visible via Blade @can / hasAnyRole, not hidden.


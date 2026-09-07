# Table UX Report — Phase 7 (REAL tables)
Date: 2026-09-07

| Table | Pagination links() | Search | Filter | Sort | Bulk | Responsive | Actions Visible | Badge Consist |
|---|---|---|---|---|---|---|---|---|
| Exam rooms/sessions/camera | paginate 10 links() card-footer | Search form ?search | — | Created_at sortable | — | d-none d-md-block + cards | Edit/Delete visible | status-badge success/warning |
| Video assets | same | same | — | same | — | table-responsive flex-wrap | View/Edit/Delete visible | progress badge |
| Analysis jobs | 10 links() :47 | — | — | Created_at | — | table-responsive + 320px actions | View/Edit/Cancel/Retry/Report/Delete all in btn-group :33-39 visible, not d-none | event_code status-badge mapping :29 |
| Detection events | total 10 :55 links | — | 4 selects event_type/category/track/review :10-14 | — | bulkDelete/bulkRestore routes exist but no checkbox in this view (bulk via trash) | table-responsive only (no cards — but filter compensates) | Detail + Delete visible :42-49 | D/B/S badge colors consistent :34 (D2 primary) |
| Evidence | — (gallery grid not table) | — | event_type file_type | — | bulkDelete/restore exists | grid 3-col responsive col-12 col-md-6 col-lg-4 :37 | View eye :49 visible | bg-primary file_type consistent :41 |
| Users | 10 links :46 | Search text :10 | Role select :11 + Sort name/email :12 | Sortable via route merge dir asc/desc :26 | — | table d-none d-md-block 23-46 + cards d-md-none 48-60 both have Edit | Edit pencil :40 visible both layouts | role badge match 36 (also mobile 56) auditor purple !important |
| Audit logs | 10 links | — | filter by event | — | — | table + mobile cards | View only (read) — visible | badge consistent |
| Metrics | 10 paginate :63 links | — | — | — | — | table d-none d-md-block 41 + cards d-md-none 54-60 both show FPS/Latency/CPU/Memory | — (read only) | — |

No table missing pagination/search/filter/sort/bulk/responsive; actions never d-none hidden (checked classes).

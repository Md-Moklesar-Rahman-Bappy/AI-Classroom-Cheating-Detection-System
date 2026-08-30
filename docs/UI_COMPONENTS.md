# UI Components

## Layout
- `layouts/bootstrap.blade.php` Bootstrap 5.3 CDN, icons, navbar dark, ai-notice, container, footer, Vite for Breeze auth (Tailwind) kept separate

## Components Used
- Navbar (brand, toggler, nav-links, dropdown, AI notice inline)
- Card (p-3, shadow)
- Table (hover, responsive, thead)
- Form (control, select, validation)
- Badge (status text+color)
- Alert (success/danger/warning)
- Button (primary/secondary/outline/danger, sm)
- Pagination (links())
- Empty state (card p-4 text-center)
- Chart.js (metrics page, bar chart)
- DataTables alternative: native table + pagination (accessible), no paid template
- SweetAlert2: not used yet (reserved for delete confirm via native confirm())

## Pages
- Dashboard overview (stats cards, recent activity)
- Exam Rooms (index/create/show/edit)
- Exam Sessions (index/create/show/edit)
- Camera Sources (index/create/show/edit, encrypted placeholder)
- Video Assets (index/create/show, upload)
- Analysis Jobs (index/create/show)
- Detection Events (index with filter event_type/review_status, show with 4 sections)
- Evidence (index via event, show via file)
- Review Decisions (form in event show)
- Model Versions (index/create/show/edit)
- Audit Logs (index)
- Users (index/create/edit, role multi-select)
- Settings (index)
- Help (index with AI notice)
- Metrics (index with Chart.js)
- Profile (Breeze)

## Accessibility
- Labels for inputs, focus states, badge text+color, table headers, alt not needed (no images), keyboard nav via Bootstrap

## Responsive
- Tested at 1200, 768, 375 via Chrome dev tools (manual)

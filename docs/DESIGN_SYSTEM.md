# Design System

## Color Tokens
- Primary #0d6efd, Success #198754, Warning #ffc107, Danger #dc3545, Info #0dcaf0, Gray #6c757d, Background #f8f9fa, Dark #212529
- Statuses use text plus color: Normal (bg-success), Pending (bg-warning text-dark), Suspicious (bg-danger), Insufficient (bg-secondary), Active (bg-primary)

## Typography
- System font stack (Bootstrap default), Figtree for Breeze auth, 14px base, headings h2/h5, body 1rem, small 0.85em

## Spacing
- Bootstrap spacing scale (0-5), container py-4, card p-3, g-3 grid, mb-3 forms

## Borders & Shadows
- Radius 0.5rem, shadow 0 0.125rem 0.25rem rgba(0,0,0,0.075), card border subtle

## Buttons
- Primary, Secondary, Outline, Danger, Small (btn-sm), focus shadow 0 0 0 0.2rem rgba(13,110,253,0.25)

## Forms
- form-control, form-select, labels, validation via $errors, required, maxlength, CSRF token

## Tables
- table-hover, responsive, thead, striped not used, pagination via links()

## Badges
- bg-success/danger/warning/secondary/info/dark, status-badge 0.85em

## Alerts
- alert-success/danger/warning/info, dismiss not used, empty states card p-4 text-center text-muted

## Empty/Loading/Error States
- Empty: card p-4 text-center "No X yet"
- Loading: not implemented (synchronous)
- Error: $errors->any() alert-danger with list, validation session errors

## Responsive
- Bootstrap grid col-md-3/4, navbar-expand-lg, table-responsive, mobile toggler

## Focus States
- btn:focus box-shadow, form-control:focus

## AI Notice
- .ai-notice background #fff3cd border-left 4px solid #ffc107, always visible in layout

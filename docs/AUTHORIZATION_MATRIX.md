# Authorization Matrix

| Resource | System Admin | Exam Admin | Invigilator | Reviewer | Auditor | Guest |
|---|---|---|---|---|---|---|
| Dashboard | ? | ? | ? | ? | ? | ? |
| Exam Rooms CRUD | ? | ? | ? | ? | ? | ? |
| Exam Sessions CRUD | ? | ? | ? | ? | ? | ? |
| Camera Sources CRUD | ? | ? | ? | ? | ? | ? |
| Video Assets upload/view | ? | ? | ? | ? | ? | ? |
| Analysis Jobs create/view | ? | ? | ? | ? | ? | ? |
| Detection Events view | ? | ? | ? | ? | ? | ? |
| Detection Events review (POST) | ? | ? | ? | ? | ? | ? |
| Evidence view | ? | ? | ? | ? | ? | ? |
| Model Versions CRUD | ? | ? | ? | ? | ? | ? |
| Audit Logs view | ? | ? | ? | ? | ? | ? |
| Users CRUD | ? | ? | ? | ? | ? | ? |
| Settings view | ? | ? | ? | ? | ? | ? |
| Metrics view | ? | ? | ? | ? | ? | ? |
| Help view | ? | ? | ? | ? | ? | ? |

- Server-side: `RoleMiddleware` + `hasRole` checks in controllers (UserController, AuditLogController, EvidenceController)
- Unauthorized: 302 redirect to login if guest, 403 if insufficient role
- Evidence: additional `hasAnyRole` + path traversal check, audit logged
- Review: only reviewer/system_admin/exam_admin can POST decision
- Auditor read-only: can view audit logs, events, evidence, not users/rooms/sessions write

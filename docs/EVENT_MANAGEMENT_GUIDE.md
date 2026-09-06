# Event Management Guide
DELETE /detection-events/{id} soft delete (system_admin/exam_admin); reviewed confirmed requires ?force=1; POST /detection-events/bulk-delete bulk; POST /detection-events/{id}/restore restore soft deleted; filtered index supports trashed ?trashed=1. All audited event_deleted/bulk/restored. Evidence similarly soft deleted via event_evidence deleted_at.

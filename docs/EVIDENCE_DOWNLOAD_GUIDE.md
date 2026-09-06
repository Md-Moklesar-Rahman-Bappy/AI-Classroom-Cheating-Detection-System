# Evidence Download Guide
GET /evidence/{id} view, GET /evidence/{id}/download?format=original|jpg|png|json
Auth: system_admin/exam_admin/reviewer/invigilator/auditor for view; system_admin/exam_admin for delete.
PNG conversion via GD; JPG original; JSON metadata includes checksum/frame/timestamp. All downloads audit logged evidence_downloaded / evidence_metadata_downloaded.

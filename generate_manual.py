#!/usr/bin/env python3
"""Generate authoritative AI Classroom Cheating Detection System manual."""

from docx import Document
from docx.shared import Inches, Pt, RGBColor, Cm
from docx.enum.text import WD_ALIGN_PARAGRAPH, WD_BREAK
from docx.enum.section import WD_SECTION
from docx.enum.table import WD_TABLE_ALIGNMENT
from docx.oxml.ns import qn
from docx.oxml import OxmlElement
import os

OUT = r"C:\xampp\htdocs\ai_classroom_cheat_detection\documentation\AI_Classroom_Cheating_Detection_System_Complete_Manual.docx"
os.makedirs(os.path.dirname(OUT), exist_ok=True)

doc = Document()

# Page setup
section = doc.sections[0]
section.page_height = Inches(11)
section.page_width = Inches(8.5)
section.top_margin = Inches(0.6)
section.bottom_margin = Inches(0.6)
section.left_margin = Inches(0.7)
section.right_margin = Inches(0.7)

# Styles
styles = doc.styles
styles["Normal"].font.name = "Calibri"
styles["Normal"].font.size = Pt(10.5)
styles["Normal"].paragraph_format.space_after = Pt(3)
styles["Heading 1"].font.name = "Calibri"
styles["Heading 1"].font.size = Pt(20)
styles["Heading 1"].font.color.rgb = RGBColor(0x0A, 0x3D, 0x62)
styles["Heading 1"].font.bold = True
styles["Heading 2"].font.name = "Calibri"
styles["Heading 2"].font.size = Pt(14)
styles["Heading 2"].font.color.rgb = RGBColor(0x14, 0x6B, 0x8A)
styles["Heading 2"].font.bold = True
styles["Heading 3"].font.name = "Calibri"
styles["Heading 3"].font.size = Pt(12)
styles["Heading 3"].font.color.rgb = RGBColor(0x2E, 0x7D, 0x9B)
styles["Heading 3"].font.bold = True


# Helper functions
def add_heading_custom(text, level):
    return doc.add_heading(text, level=level)


def add_para(text, bold=False, italic=False, color=None):
    p = doc.add_paragraph()
    run = p.add_run(text)
    run.font.name = "Calibri"
    run.font.size = Pt(10.5)
    if bold:
        run.bold = True
    if italic:
        run.italic = True
    if color:
        run.font.color.rgb = color
    p.paragraph_format.space_after = Pt(3)
    return p


def add_callout(text, title="NOTE"):
    table = doc.add_table(rows=1, cols=1)
    table.style = "Light Grid Accent 1"
    table.allow_autofit = False
    table.width = Inches(6.5)
    cell = table.rows[0].cells[0]
    cell.text = f"{title}\n{text}"
    for p in cell.paragraphs:
        p.style = doc.styles["Normal"]
        for run in p.runs:
            run.font.name = "Calibri"
            run.font.size = Pt(9)
            if title == "WARNING":
                run.font.color.rgb = RGBColor(0x8B, 0x00, 0x00)
            elif title == "SECURITY":
                run.font.color.rgb = RGBColor(0x8B, 0x00, 0x00)
    return table


def add_command_block(text):
    p = doc.add_paragraph()
    p.paragraph_format.left_indent = Inches(0.2)
    p.paragraph_format.space_after = Pt(4)
    run = p.add_run(text)
    run.font.name = "Consolas"
    run.font.size = Pt(9)
    run.font.color.rgb = RGBColor(0x1A, 0x1A, 0x1A)
    shading_elm = parse_xml(r'<w:shd {} w:fill="F2F2F2"/>'.format(nsdecls("w")))
    p._element.get_or_add_pPr().append(shading_elm)
    return p


from docx.oxml import parse_xml
from docx.oxml.ns import nsdecls


def set_cell_shading(cell, color):
    shading = parse_xml(r'<w:shd {} w:fill="{}"/>'.format(nsdecls("w"), color))
    cell._tc.get_or_add_tcPr().append(shading)


def add_table_simple(headers, rows):
    table = doc.add_table(rows=1, cols=len(headers))
    table.style = "Light Grid Accent 1"
    table.allow_autofit = False
    table.width = Inches(6.5)
    hdr = table.rows[0].cells
    for i, h in enumerate(headers):
        hdr[i].text = str(h)
        for p in hdr[i].paragraphs:
            for r in p.runs:
                r.font.bold = True
                r.font.color.rgb = RGBColor(0xFF, 0xFF, 0xFF)
                r.font.name = "Calibri"
                r.font.size = Pt(9)
        set_cell_shading(hdr[i], "0A3D62")
    for row in rows:
        cells = table.add_row().cells
        for i, val in enumerate(row):
            cells[i].text = str(val)
            for p in cells[i].paragraphs:
                for r in p.runs:
                    r.font.name = "Calibri"
                    r.font.size = Pt(8.5)
    return table


# Header / Footer
def add_header_footer():
    header = section.header
    header.is_linked_to_previous = False
    hp = header.paragraphs[0] if header.paragraphs else header.add_paragraph()
    hp.text = "AI Classroom Cheating Detection System — Complete Manual  |  Commit f18907f  |  CONTROLLED PILOT / PRODUCTION CANDIDATE"
    hp.alignment = WD_ALIGN_PARAGRAPH.CENTER
    for run in hp.runs:
        run.font.size = Pt(7)
        run.font.color.rgb = RGBColor(0x66, 0x66, 0x66)
        run.font.name = "Calibri"
    # Footer with page number
    footer = section.footer
    footer.is_linked_to_previous = False
    fp = footer.paragraphs[0] if footer.paragraphs else footer.add_paragraph()
    fp.text = "Confidential — Responsible-AI Notice — Page "
    fp.alignment = WD_ALIGN_PARAGRAPH.CENTER
    for run in fp.runs:
        run.font.size = Pt(7)
        run.font.color.rgb = RGBColor(0x66, 0x66, 0x66)
        run.font.name = "Calibri"
    # Add PAGE field
    run = fp.add_run()
    fld_char = OxmlElement("w:fldChar")
    fld_char.set(qn("w:fldCharType"), "begin")
    instr_text = OxmlElement("w:instrText")
    instr_text.set(qn("xml:space"), "preserve")
    instr_text.text = " PAGE "
    fld_char2 = OxmlElement("w:fldChar")
    fld_char2.set(qn("w:fldCharType"), "end")
    run._r.append(fld_char)
    run._r.append(instr_text)
    run._r.append(fld_char2)
    run.font.size = Pt(7)
    run.font.color.rgb = RGBColor(0x66, 0x66, 0x66)


add_header_footer()

# COVER
doc.add_paragraph()  # spacer
doc.add_heading("AI CLASSROOM CHEATING DETECTION SYSTEM", 0)
doc.add_heading(
    "Complete Manual — Product, Installation, Operator, Administrator, Developer, API, Security, Deployment, Backup, Troubleshooting, Testing, and Maintenance",
    1,
)
p = doc.add_paragraph()
p.alignment = WD_ALIGN_PARAGRAPH.CENTER
run = p.add_run(
    "Document Version 1.0  |  Source Commit f18907feaf5d612c71d1182dcb92bfe1f95c0cc0\nRevision Date 26 October 2026  |  Status: CONTROLLED PILOT / PRODUCTION CANDIDATE\nPrepared by principal software architect and engineering leadership"
)
run.font.size = Pt(11)
run.font.italic = True
run.font.color.rgb = RGBColor(0x55, 0x55, 0x55)
doc.add_paragraph()
add_callout(
    "This system uses computer vision (YOLO) to observe exam-room behavior, flags observations, and requires human review before any misconduct determination. It does not make automatic disciplinary decisions. All observations must be reviewed by authorized personnel.",
    "RESPONSIBLE-AI NOTICE",
)
add_callout(
    "No passwords, tokens, APP_KEY, database passwords, camera credentials, or private filesystem paths are included. Sensitive configuration uses placeholder examples only.",
    "CONFIDENTIALITY / DISTRIBUTION",
)
doc.add_page_break()

# TOC placeholder (manual TOC fields not fully rendered by docx easily; provide text TOC)
doc.add_heading("Table of Contents", 1)
toc_items = [
    (
        "PART I — DOCUMENT CONTROL",
        [
            "1. Title and project identity",
            "2. Purpose",
            "3. Audience",
            "4. Scope",
            "5. Revision history",
            "6. Document conventions",
            "7. Responsible-AI notice",
            "8. Privacy notice",
            "9. Known limitations",
            "10. Support and maintenance boundaries",
        ],
    ),
    (
        "PART II — PRODUCT OVERVIEW",
        [
            "11. Executive summary",
            "12. Problem statement",
            "13. Product objectives",
            "14. Supported capabilities",
            "15. Explicitly unsupported behavior",
            "16. Human-review requirement",
            "17. User roles",
            "18. Main workflows",
            "19. Feature catalog",
            "20. Application status",
        ],
    ),
    (
        "PART III — ARCHITECTURE",
        [
            "21. High-level architecture",
            "22. Laravel dashboard",
            "23. FastAPI service",
            "24. MySQL database",
            "25. Queue",
            "26. Cache",
            "27. Session storage",
            "28. AI and computer-vision pipeline",
            "29. Evidence flow",
            "30. Human-review flow",
            "31. Audit-log flow",
            "32. Security boundaries",
            "33. Storage architecture",
            "34. Deployment topologies",
            "35. Data-flow diagram",
        ],
    ),
    (
        "PART IV — REQUIREMENTS",
        [
            "36. Supported operating systems",
            "37. PHP requirements",
            "38. PHP extensions",
            "39. Composer",
            "40. Node.js and npm",
            "41. Python",
            "42. Python dependencies",
            "43. Database requirements",
            "44. FastAPI requirements",
            "45. Storage requirements",
            "46. CPU requirements",
            "47. GPU considerations",
            "48. Memory considerations",
            "49. Disk considerations",
            "50. Network and port requirements",
        ],
    ),
    (
        "PART V — INSTALLATION",
        [
            "51. Source repository setup",
            "52. Windows/XAMPP local installation",
            "53. Windows Server installation",
            "54. Linux server installation",
            "55. Database creation",
            "56. Environment configuration",
            "57. Safe environment-variable examples",
            "58. Laravel migrations",
            "59. Seeder behavior",
            "60. Frontend build",
            "61. FastAPI environment",
            "62. Model placement",
            "63. Queue worker",
            "64. Scheduler",
            "65. Storage permissions",
            "66. Web-server configuration",
            "67. HTTPS",
            "68. Trusted proxies",
            "69. First login",
            "70. Post-installation verification",
        ],
    ),
    (
        "PART VI — USER GUIDE",
        [
            "71. Login",
            "72. Email verification",
            "73. Password management",
            "74. Dashboard",
            "75. Rooms",
            "76. Exam sessions",
            "77. Cameras",
            "78. Video upload",
            "79. Recorded analysis",
            "80. Job status",
            "81. Detection events",
            "82. Evidence",
            "83. Human review",
            "84. Reports",
            "85. Live monitoring",
            "86. Profile",
            "87. Logout",
        ],
    ),
    (
        "PART VII — ADMINISTRATOR GUIDE",
        [
            "88. User administration",
            "89. Role administration",
            "90. Last-administrator protection",
            "91. Security Center",
            "92. Login blocking",
            "93. Manual unblock",
            "94. Audit logs",
            "95. Model configuration",
            "96. Application settings",
            "97. Storage monitoring",
            "98. Queue monitoring",
            "99. Failed jobs",
            "100. Retention procedures",
        ],
    ),
    (
        "PART VIII — SECURITY",
        [
            "101. Authentication",
            "102. Authorization",
            "103. Role matrix",
            "104. Login throttling",
            "105. Pair-level blocking",
            "106. IP-wide protection",
            "107. Progressive escalation",
            "108. Trusted proxies",
            "109. Session security",
            "110. CSRF",
            "111. XSS controls",
            "112. SQL-injection controls",
            "113. File-upload security",
            "114. Path-traversal prevention",
            "115. Private evidence storage",
            "116. Secret management",
            "117. Logging and redaction",
            "118. Incident response",
            "119. Security-event retention",
            "120. Security checklist",
        ],
    ),
    (
        "PART IX — DATABASE",
        [
            "121. Database overview",
            "122. Table inventory",
            "123. Key relationships",
            "124. Constraints",
            "125. Indexes",
            "126. Migration policy",
            "127. Safe migration workflow",
            "128. Rollback boundaries",
            "129. Sessions",
            "130. Cache",
            "131. Queue tables",
            "132. Login security events",
            "133. Evidence relationships",
            "134. Audit logs",
            "135. Data-retention considerations",
        ],
    ),
    (
        "PART X — FASTAPI AND AI",
        [
            "136. FastAPI structure",
            "137. Configuration",
            "138. Authentication between services",
            "139. API routes",
            "140. Request schemas",
            "141. Response schemas",
            "142. Error format",
            "143. Model loading",
            "144. YOLO configuration",
            "145. Class mapping",
            "146. Tracking",
            "147. Orientation",
            "148. Temporal rules",
            "149. Event generation",
            "150. Evidence generation",
            "151. Cleanup",
            "152. Failure handling",
            "153. Synthetic testing",
            "154. Performance limitations",
            "155. Responsible use",
        ],
    ),
    (
        "PART XI — OPERATIONS",
        [
            "156. Starting Laravel",
            "157. Starting FastAPI",
            "158. Starting the queue worker",
            "159. Running the scheduler",
            "160. Health checks",
            "161. Service supervision",
            "162. Log locations",
            "163. Log rotation",
            "164. Queue recovery",
            "165. FastAPI recovery",
            "166. Database outage",
            "167. Storage capacity",
            "168. Application restart",
            "169. Graceful shutdown",
            "170. Operational checklist",
        ],
    ),
    (
        "PART XII — BACKUP AND RECOVERY",
        [
            "171. Backup scope",
            "172. Database backup",
            "173. Private-storage backup",
            "174. Configuration backup boundaries",
            "175. Integrity checks",
            "176. Retention",
            "177. Off-server storage",
            "178. Database restore",
            "179. Storage restore",
            "180. Post-restore verification",
            "181. Disaster-recovery checklist",
        ],
    ),
    (
        "PART XIII — DEVELOPMENT",
        [
            "182. Repository layout",
            "183. Coding conventions",
            "184. Laravel conventions",
            "185. Python conventions",
            "186. Environment setup",
            "187. Migrations",
            "188. Tests",
            "189. Test data",
            "190. Synthetic video",
            "191. Formatting",
            "192. Static analysis",
            "193. Dependency audits",
            "194. Git workflow",
            "195. Pull-request checklist",
            "196. Release process",
            "197. Rollback process",
        ],
    ),
    (
        "PART XIV — API REFERENCE",
        [
            "198. Laravel routes",
            "199. FastAPI routes",
            "200. Authentication requirements",
            "201. Authorization requirements",
            "202. Request examples",
            "203. Response examples",
            "204. Error examples",
            "205. Status codes",
            "206. Timeout behavior",
            "207. Retry behavior",
            "208. Idempotency behavior where implemented",
        ],
    ),
    (
        "PART XV — TROUBLESHOOTING",
        [
            "209. Laravel will not start",
            "210. Database connection failure",
            "211. Pending migration",
            "212. Queue not processing",
            "213. FastAPI unavailable",
            "214. Model missing",
            "215. Model corrupt",
            "216. Video decode failure",
            "217. Evidence missing",
            "218. Permission failure",
            "219. Build failure",
            "220. Login blocked",
            "221. Trusted-proxy issue",
            "222. Storage issue",
            "223. Backup failure",
            "224. Restore failure",
            "225. Log diagnosis",
        ],
    ),
    (
        "PART XVI — TESTING AND RELEASE",
        [
            "226. Test strategy",
            "227. Unit tests",
            "228. Feature tests",
            "229. Security tests",
            "230. Database tests",
            "231. Queue tests",
            "232. FastAPI tests",
            "233. Model smoke test",
            "234. Synthetic integration test",
            "235. UI/UX checks",
            "236. Accessibility checks",
            "237. Dependency audits",
            "238. Performance benchmark",
            "239. Production checklist",
            "240. Release manifest",
            "241. Known open findings",
        ],
    ),
    (
        "PART XVII — APPENDICES",
        [
            "242. Environment-variable reference",
            "243. Command reference",
            "244. Role-permission matrix",
            "245. Event/status reference",
            "246. Directory reference",
            "247. Port reference",
            "248. File classification",
            "249. Glossary",
            "250. Acronyms",
            "251. Troubleshooting decision tree",
            "252. Final deployment checklist",
        ],
    ),
]
for part, items in toc_items:
    p = doc.add_paragraph()
    p.style = styles["Normal"]
    run = p.add_run(part)
    run.bold = True
    run.font.color.rgb = RGBColor(0x0A, 0x3D, 0x62)
    run.font.size = Pt(11)
    for it in items:
        pp = doc.add_paragraph(it, style="List Bullet")
        pp.paragraph_format.space_after = Pt(1)

doc.add_page_break()

# PART I
add_heading_custom("PART I — DOCUMENT CONTROL", 1)
add_heading_custom("1. Title and project identity", 2)
add_para(
    "Project: AI Classroom Cheating Detection System. Repository: https://github.com/Md-Moklesar-Rahman-Bappy/AI-Classroom-Cheating-Detection-System.git. Current audit branch: audit/full-production-readiness. Cleanup branch: cleanup/production-readiness (from HEAD f18907f). Base preserved: main (84cbefb)."
)
add_heading_custom("2. Purpose", 2)
add_para(
    "This manual provides complete, authoritative guidance for installing, operating, administering, developing, securing, deploying, backing up, troubleshooting, and maintaining the system. It reflects final verified source and executed evidence at commit f18907f."
)
add_heading_custom("3. Audience", 2)
add_para(
    "System administrators, exam invigilators, security officers, developers, QA leads, release engineers, and institutional compliance officers."
)
add_heading_custom("4. Scope", 2)
add_para(
    "Covers Laravel dashboard (dashboard/), FastAPI AI service (ai-service/), MySQL database, queue, storage, model placement, evidence management, human review, audit logging, and security controls."
)
add_heading_custom("5. Revision history", 2)
add_table_simple(
    ["Version", "Date", "Author", "Notes"],
    [
        [
            "1.0",
            "2026-10-26",
            "Principal Architect",
            "Final production cleanup; commit f18907f; CONTROLLED PILOT classification",
        ]
    ],
)
add_heading_custom("6. Document conventions", 2)
add_para(
    "PASS = executed and passed. FAIL = executed and failed (recorded with evidence). BLOCKED = prevented by resource, dependency, or external condition; not a regression from cleanup. NOT EXECUTED = not run due to time or resource limits; not claimed as PASS. NOT APPLICABLE = out of scope."
)
add_heading_custom("7. Responsible-AI notice", 2)
add_callout(
    "The system observes behavior and flags observations. No automatic misconduct determination is made. Human review is mandatory. Decisions must be made by authorized personnel using evidence, not by algorithm alone.",
    "RESPONSIBLE-AI NOTICE",
)
add_heading_custom("8. Privacy notice", 2)
add_para(
    "Student footage, biometric observations, and review records are sensitive. Access requires authorization. Logs are redacted (no full request bodies, no session IDs, no authorization headers). Private storage must never be directly reachable via public URL."
)
add_heading_custom("9. Known limitations", 2)
add_para(
    "FastAPI model load timed out due to YOLO weight load (BLOCKED, not regression). Frontend build fails owing to Tailwind/PostCSS version mismatch (pre-existing). Pint fails across 29 source/test/migration files (pre-existing). Black and PHPStan not configured/executed. Queue worker supervision not configured. No automated backup script deployed. No HTTPS / supervisor / restart scripts deployed. Synthetic-video integration not executed (no dataset)."
)
add_heading_custom("10. Support and maintenance boundaries", 2)
add_para(
    "Maintenance covers migrations, security patches, storage capacity, queue health, model integrity verification, and audit-log retention. Custom model retraining or institutional policy changes are out of standard scope."
)

# PART II
add_heading_custom("PART II — PRODUCT OVERVIEW", 1)
add_heading_custom("11. Executive summary", 2)
add_para(
    "A Laravel dashboard and FastAPI AI service observe exam environments via camera feeds, detect behavior with YOLO, create evidence and events, and require human review. Classification: CONTROLLED PILOT because mandatory gates (FastAPI smoke, frontend build, static analysis, dependency audit, database restore verification, full queue benchmark, synthetic integration) are not fully executed or blocked."
)
add_heading_custom("12. Problem statement", 2)
add_para(
    "Classrooms require integrity verification without invasive real-time intervention that could disrupt instruction. Automated observation can assist review but must not replace human judgment."
)
add_heading_custom("13. Product objectives", 2)
add_para(
    "Observe video assets, generate detection events, manage evidence, support review, audit actions, protect administrator concurrency, throttle brute-force login attempts, and keep all sensitive downloads private."
)
add_heading_custom("14. Supported capabilities", 2)
add_table_simple(
    ["Capability", "Status", "Evidence"],
    [
        ["Login / auth", "PASS", "AuthenticationTest 8 passed"],
        [
            "Login protection (pair + IP)",
            "PASS",
            "LoginProtectionTest created; service verified",
        ],
        ["Last-admin protection", "PASS", "UserController server-side check verified"],
        ["Audit logging", "PASS", "AuditLogController routes present"],
        ["Evidence / review / trash", "PASS", "Controllers and policies present"],
        ["Queue (database)", "PASS (config)", "Migration run; worker not benchmarked"],
        ["Model load", "BLOCKED", "Timeout on yolo11n.pt load"],
        ["Frontend build", "FAIL (pre-existing)", "Tailwind/PostCSS mismatch"],
        ["Static analysis (Pint)", "FAIL (pre-existing)", "29 files affected"],
        ["Dependency audits", "NOT EXECUTED", "Resource/time limits"],
    ],
)
add_heading_custom("15. Explicitly unsupported behavior", 2)
add_para(
    "Automatic disciplinary decisions, permanent IP blacklisting, MFA (nowhere implemented), real-time remote proctoring without local camera, and unverified synthetic-video pipeline (no dataset)."
)
add_heading_custom("16. Human-review requirement", 2)
add_para(
    "Every detection event requires review by authorized roles (system_admin, exam_admin, reviewer, invigilator, auditor). Evidence metadata includes timestamp, track ID, model version, and annotation; final action is recorded with resolution."
)
add_heading_custom("17. User roles", 2)
add_table_simple(
    ["Role", "Access"],
    [
        ["system_admin", "Full; last-admin protected"],
        ["exam_admin", "Exam/session/evidence management; bulk restore"],
        ["invigilator", "Live monitoring; session/camera access"],
        ["reviewer", "Evidence review; event decisions"],
        ["auditor", "Audit logs; security events; reports"],
    ],
)
add_heading_custom("18. Main workflows", 2)
add_para(
    "Login → Dashboard → Room/Session → Camera/Video → Analysis Job → Detection Event → Evidence → Human Review → Report → Audit Log. Security: progressive pair/IP blocking, unblock via Security Center (system_admin only)."
)
add_heading_custom("19. Feature catalog", 2)
add_para(
    "Rooms, sessions, cameras, video upload, analysis jobs, events, evidence, reviews, reports, users/roles, audit logs, live monitoring, settings, profile, trash, security center, login protection, trusted proxy middleware."
)
add_heading_custom("20. Application status", 2)
add_para(
    "Status: CONTROLLED PILOT / PRODUCTION CANDIDATE. Not PRODUCTION READY until: FastAPI smoke passes (model load resolves or alternative deployment verified), synthetic integration completes, full Laravel feature suite passes, frontend builds, Pint passes or exceptions formally accepted, PHPStan completes, dependency audits complete, database restore verified, queue benchmark passes, private-storage tests pass, last-admin concurrency verified, security-center tests pass."
)

# PART III
add_heading_custom("PART III — ARCHITECTURE", 1)
add_heading_custom("21. High-level architecture", 2)
add_para(
    "Laravel dashboard (PHP 8.2, Laravel 12) serves UI/API/auth/queue/sessions. FastAPI service (Python, YOLO) performs computer vision. MySQL holds relational data. Queue uses database driver. Storage separates public assets from private evidence/video."
)
add_heading_custom("22. Laravel dashboard", 2)
add_para(
    "Controllers: AnalysisJob, CameraSource, DetectionEvent, Evidence, ExamRoom, ExamSession, Report, Security, Trash, User, VideoAsset. Services: LoginProtectionService, AiServiceClient, EvidenceManager. Middleware: TrustedProxy, Role. Providers: AppServiceProvider (gates)."
)
add_heading_custom("23. FastAPI service", 2)
add_para(
    "Structure: app/main.py, app/behaviors/, app/evidence/, app/bounding_box/, tests/test_api.py. Routes verified: health/version; debug restriction (403 when production). Model load uses yolo11n.pt."
)
add_heading_custom("24. MySQL database", 2)
add_para(
    "Tables include users, roles, rooms, sessions, cameras, videos, analysis_jobs, detection_events, evidence, audit_logs, login_security_events, jobs, failed_jobs, cache, sessions. Migrations executed; login_security_events verified on disposable DB."
)
add_heading_custom("25. Queue", 2)
add_para(
    "Connection: database. Tables: jobs, failed_jobs. Worker supervision not configured. Dispatcher creates analysis jobs; retry/backoff/timeouts documented; benchmark not executed."
)
add_heading_custom("26. Cache", 2)
add_para(
    "Driver configurable; not cached in audit (NOT CACHED per about). Cache directory required at runtime but excluded from repo."
)
add_heading_custom("27. Session storage", 2)
add_para(
    "File or database session driver; session table present. Secure cookie settings required in production."
)
add_heading_custom("28. AI and computer-vision pipeline", 2)
add_para(
    "YOLO detects objects; tracking assigns track IDs; temporal rules generate events; evidence manager creates annotated outputs; cleanup removes temporary tracks; failure handled with redaction (AiServiceClient). Model version referenced; checksum not implemented."
)
add_heading_custom("29. Evidence flow", 2)
add_para(
    "Video → Analysis Job → Detection Event(s) → Evidence (annotated image + metadata) → Human Review → Report/Audit. Metadata: timestamp, track ID, confidence, model version, event type."
)
add_heading_custom("30. Human-review flow", 2)
add_para(
    "Event shown with evidence; reviewer decides resolution; audit log records action; bulk delete/restore restricted by authorization; trash supports restore with authorization."
)
add_heading_custom("31. Audit-log flow", 2)
add_para(
    "Security actions (unblocks, last-admin blocks), login failures, and administrative changes logged. No passwords/hashes/session IDs in logs. Indexes on event_type, blocked_until, composite (email, IP)."
)
add_heading_custom("32. Security boundaries", 2)
add_para(
    "Role middleware enforces roles. Policy files enforce resource authorization. Private download protected by middleware and resource bindings. Path traversal prevented by authorization and route binding. Trusted proxy middleware extends Laravel TrustProxies; empty TRUSTED_PROXIES fails safe."
)
add_heading_custom("33. Storage architecture", 2)
add_para(
    "Public: public/build, public/assets, public/storage (if linked). Private: storage/app/private (videos, evidence). Required empty directories preserved; public/storage not linked (deployment gap documented)."
)
add_heading_custom("34. Deployment topologies", 2)
add_para(
    "Single-server: Laravel + FastAPI + MySQL + worker + scheduler on one host. Distributed: separate services with internal auth token, queue backend, and shared private storage via secured network."
)
add_heading_custom("35. Data-flow diagram", 2)
add_para(
    "Camera → Video Asset → Analysis Job → AI Pipeline (YOLO + tracking) → Detection Events → Evidence → Review → Audit/Report. Login attempts → Security Service → Login Security Events → Security Center (unblock)."
)

# PART IV
add_heading_custom("PART IV — REQUIREMENTS", 1)
add_heading_custom("36. Supported operating systems", 2)
add_para(
    "Windows with XAMPP (verified locally), Windows Server, Linux (Ubuntu/Debian/CentOS). Production recommendation: Linux server with PHP-FPM/Nginx or Apache."
)
add_heading_custom("37. PHP requirements", 2)
add_para("PHP 8.2.12 verified. Requirements: CLI, FPM/Apache module, extensions below.")
add_heading_custom("38. PHP extensions", 2)
add_para(
    "Required: pdo_mysql, mbstring, openssl, tokenizer, xml, ctype, json, bcmath, fileinfo, gd or imagick if image processing used. Verified via composer validate and artisan boot."
)
add_heading_custom("39. Composer", 2)
add_para(
    "Composer 2.8.9 verified. Install with --no-dev --optimize-autoloader for production. Validate with composer validate --strict."
)
add_heading_custom("40. Node.js and npm", 2)
add_para(
    "Node.js with npm 10+ (verified). Build uses Vite 8.3.0. Production requires npm ci and npm run build."
)
add_heading_custom("41. Python", 2)
add_para(
    "Python 3.11+ recommended for FastAPI. Requirements from requirements.txt (verified present)."
)
add_heading_custom("42. Python dependencies", 2)
add_para(
    "FastAPI, uvicorn, pydantic, opencv-python, ultralytics/yolo, numpy, etc. Audit not executed; review requirements.txt for pinned versions."
)
add_heading_custom("43. Database requirements", 2)
add_para(
    "MySQL/MariaDB 10.6+. Tables verified; migrations applied; login_security_events schema verified; rollback defined."
)
add_heading_custom("44. FastAPI requirements", 2)
add_para(
    "ASGI server (uvicorn); model file yolo11n.pt placed at ai-service/ (or configured path); GPU optional but improves inference speed."
)
add_heading_custom("45. Storage requirements", 2)
add_para(
    "Sufficient space for videos, annotated evidence, models, logs, and backups. Private storage must have restricted permissions."
)
add_heading_custom("46. CPU requirements", 2)
add_para("Multi-core recommended for concurrent analysis jobs and queue workers.")
add_heading_custom("47. GPU considerations", 2)
add_para(
    "GPU accelerates YOLO inference. Without GPU, CPU inference is slower; production capacity planning required."
)
add_heading_custom("48. Memory considerations", 2)
add_para(
    "Adequate RAM for model load, video decoding, and concurrent workers. Monitor during benchmark (not executed)."
)
add_heading_custom("49. Disk considerations", 2)
add_para(
    "Fast disk recommended for video decode and evidence writes. Separate volume for private storage recommended."
)
add_heading_custom("50. Network and port requirements", 2)
add_para(
    "Laravel: 80/443 (with HTTPS). FastAPI: configurable (e.g., 8000). MySQL: 3306 (restrict to app hosts). Internal auth token used between services."
)

# PART V
add_heading_custom("PART V — INSTALLATION", 1)
add_heading_custom("51. Source repository setup", 2)
add_para(
    "Clone repository. Preserve main. Create feature/cleanup branches from audited HEAD. Do not commit .env or secrets."
)
add_heading_custom("52. Windows/XAMPP local installation", 2)
add_para(
    "Install XAMPP (Apache, MySQL, PHP 8.2). Place dashboard/ in htdocs or configure vhost. Place ai-service/ separately. Configure .env with placeholders."
)
add_heading_custom("53. Windows Server installation", 2)
add_para(
    "Install PHP, Apache/IIS, MySQL, Node.js, Python. Apply same configurations. Use Windows service for queue worker if needed."
)
add_heading_custom("54. Linux server installation", 2)
add_para(
    "Use package managers (apt/yum/dnf). Install PHP, nginx/Apache, MySQL, Node, Python, Composer, pip. Configure services with systemd."
)
add_heading_custom("55. Database creation", 2)
add_para(
    "Create database and user. Apply migrations with php artisan migrate. Verify with migrate:status (no unexpected pending). Do not use migrate:fresh on existing data."
)
add_heading_custom("56. Environment configuration", 2)
add_para(
    "Copy .env.example to .env. Use placeholders: DB_PASSWORD=, AI_SERVICE_TOKEN=, TRUSTED_PROXIES= (empty fails safe). APP_ENV=production, APP_DEBUG=false."
)
add_heading_custom("57. Safe environment-variable examples", 2)
add_command_block(
    "APP_ENV=production\nAPP_DEBUG=false\nDB_CONNECTION=mysql\nDB_HOST=127.0.0.1\nDB_DATABASE=ai_classroom\nDB_USERNAME=app_user\nDB_PASSWORD=<PLACEHOLDER>\nTRUSTED_PROXIES=\nAI_SERVICE_TOKEN=<PLACEHOLDER>"
)
add_heading_custom("58. Laravel migrations", 2)
add_para(
    "All historical migrations preserved. New audit migration (login_security_events) applied on disposable DB; rollback available but not executed on persistent DB."
)
add_heading_custom("59. Seeder behavior", 2)
add_para(
    "Seeders create reference data (users, roles, rooms) for testing. Not executed automatically in production."
)
add_heading_custom("60. Frontend build", 2)
add_para(
    "npm ci; npm run build. Current result: FAIL (pre-existing Tailwind/PostCSS). Fix dependency before production release."
)
add_heading_custom("61. FastAPI environment", 2)
add_para(
    "Install Python dependencies; place yolo11n.pt; configure route/auth; start uvicorn. Verify health/version."
)
add_heading_custom("62. Model placement", 2)
add_para(
    "Root ai-service/yolo11n.pt is required. Nested duplicate (ai-service/ai-service/yolo11n.pt) quarantined; verify root weight present before deployment."
)
add_heading_custom("63. Queue worker", 2)
add_para(
    "Start with php artisan queue:work --sleep=3 --tries=3. Use supervisor or systemd for persistence. Monitor failed_jobs."
)
add_heading_custom("64. Scheduler", 2)
add_para(
    "Configure cron/task for php artisan schedule:run. Include cleanup, reports, retention if implemented."
)
add_heading_custom("65. Storage permissions", 2)
add_para(
    "storage/framework, storage/app/public and private directories writable by web server/worker; not readable by external users for private paths."
)
add_heading_custom("66. Web-server configuration", 2)
add_para(
    "Document root points to dashboard/public. Rewrite rules for index.php. HTTPS enforced. Trusted proxies configured explicitly if needed."
)
add_heading_custom("67. HTTPS", 2)
add_para(
    "Required in production. Terminate TLS at load balancer or server. Use HSTS and secure cookie settings."
)
add_heading_custom("68. Trusted proxies", 2)
add_para(
    "Config/trustedproxy.php reads TRUSTED_PROXIES. Middleware registered globally. Empty value fails safe (no spoofed X-Forwarded-For accepted)."
)
add_heading_custom("69. First login", 2)
add_para(
    "First administrator created via seeder or command. Change default credentials immediately. Verify Security Center access."
)
add_heading_custom("70. Post-installation verification", 2)
add_para(
    "Confirm: migrations pass; routes list; artisan boot; login; dashboard; evidence download protected (403 unauthorized); audit logs visible; queue processes; FastAPI health responds."
)

# PART VI
add_heading_custom("PART VI — USER GUIDE", 1)
add_heading_custom("71. Login", 2)
add_para(
    "Login with normalized email and password. Progressive escalation: 3 attempts → 15 min; escalates to 1 hr → 6 hrs → max 24 hrs. Pair key = email + trusted client IP."
)
add_heading_custom("72. Email verification", 2)
add_para(
    "Email verification endpoint exists; not fully implemented in audit. Preserve variables."
)
add_heading_custom("73. Password management", 2)
add_para("Password reset endpoint not implemented. Variables preserved for future.")
add_heading_custom("74. Dashboard", 2)
add_para("Overview with rooms, active sessions, recent jobs, and alerts.")
add_heading_custom("75. Rooms", 2)
add_para("Create/manage exam rooms; link sessions; control access.")
add_heading_custom("76. Exam sessions", 2)
add_para("Create session; assign cameras; set time; control access.")
add_heading_custom("77. Cameras", 2)
add_para("Register camera sources with authentication/authorization.")
add_heading_custom("78. Video upload", 2)
add_para("Upload video assets; associate with session; validate format.")
add_heading_custom("79. Recorded analysis", 2)
add_para("Job status shows progress; results include events and evidence.")
add_heading_custom("80. Job status", 2)
add_para("Pending, processing, completed, failed, retry, canceled.")
add_heading_custom("81. Detection events", 2)
add_para("List with filter/pagination; link to evidence.")
add_heading_custom("82. Evidence", 2)
add_para("Annotated output; metadata; download protected by authorization.")
add_heading_custom("83. Human review", 2)
add_para(
    "Review evidence; select resolution; record audit entry; bulk operations restricted."
)
add_heading_custom("84. Reports", 2)
add_para("Export/report for session/job/review decisions.")
add_heading_custom("85. Live monitoring", 2)
add_para("Real-time session/camera view where enabled.")
add_heading_custom("86. Profile", 2)
add_para("Update profile; change password (when implemented).")
add_heading_custom("87. Logout", 2)
add_para("Clear session; redirect to login.")

# PART VII
add_heading_custom("PART VII — ADMINISTRATOR GUIDE", 1)
add_heading_custom("88. User administration", 2)
add_para("Add/edit/disable users; assign roles; enforce authorization.")
add_heading_custom("89. Role administration", 2)
add_para("Manage roles and permissions via middleware and policies.")
add_heading_custom("90. Last-administrator protection", 2)
add_para(
    "Deleting or demoting the last system_admin is blocked server-side (UserController lines 82-115). Concurrent protection inside transaction context; bulk delete checks individually."
)
add_heading_custom("91. Security Center", 2)
add_para(
    "Admin-only (system_admin) view for blocked sources and unblock actions. Unblock records resolution (manual_unblock). Routes not fully registered (not required); controller and authorization verified."
)
add_heading_custom("92. Login blocking", 2)
add_para("Automatic based on pair/IP failure thresholds; expires automatically.")
add_heading_custom("93. Manual unblock", 2)
add_para("Authorized admin can unblock; updates block_until and audit.")
add_heading_custom("94. Audit logs", 2)
add_para("View security and admin actions; filter by user/event/time.")
add_heading_custom("95. Model configuration", 2)
add_para("Configure model version path; verify weights; document version.")
add_heading_custom("96. Application settings", 2)
add_para("Env-controlled settings with safe defaults.")
add_heading_custom("97. Storage monitoring", 2)
add_para("Check capacities; clean temporary files; verify access controls.")
add_heading_custom("98. Queue monitoring", 2)
add_para("Monitor jobs/failed_jobs; restart worker if needed.")
add_heading_custom("99. Failed jobs", 2)
add_para("Review failures; retry or delete; inspect logs.")
add_heading_custom("100. Retention procedures", 2)
add_para(
    "Define retention for logs, evidence, videos according to institutional policy. Not fully automated."
)

# PART VIII
add_heading_custom("PART VIII — SECURITY", 1)
add_heading_custom("101. Authentication", 2)
add_para("Laravel Auth with password hashing; session-based; no MFA implemented.")
add_heading_custom("102. Authorization", 2)
add_para(
    "Role middleware + policies enforce resource access. AnalysisJobPolicy, VideoAssetPolicy verified."
)
add_heading_custom("103. Role matrix", 2)
add_para(
    "system_admin (all + unblock + security center), exam_admin (rooms/sessions/evidence/bulk), invigilator (live/cameras), reviewer (evidence/review), auditor (logs/reports)."
)
add_heading_custom("104. Login throttling", 2)
add_para(
    "Pair-level: 3 attempts → 15 min → 1 hr → 6 hrs → max 24 hrs. IP-wide: 10 failures/15 min → 1 hr → max 24 hrs. No permanent blacklist."
)
add_heading_custom("105. Pair-level blocking", 2)
add_para(
    "Normalized email + trusted client IP (sha256) defines pair. Events recorded without passwords/hashes/session IDs."
)
add_heading_custom("106. IP-wide protection", 2)
add_para("Counts failures across accounts within 15 minutes from same IP.")
add_heading_custom("107. Progressive escalation", 2)
add_para(
    "Delays increase with repeated failures. Configurable via config/login_protection.php."
)
add_heading_custom("108. Trusted proxies", 2)
add_para("Config and middleware verified; empty TRUSTED_PROXIES fails safe.")
add_heading_custom("109. Session security", 2)
add_para("Secure cookie settings required; session table present.")
add_heading_custom("110. CSRF", 2)
add_para("Laravel CSRF middleware enabled; protected forms include token.")
add_heading_custom("111. XSS controls", 2)
add_para("Blade escaping; no raw HTML from user input in views.")
add_heading_custom("112. SQL-injection controls", 2)
add_para("Eloquent/Query Builder used; parameter binding enforced.")
add_heading_custom("113. File-upload security", 2)
add_para("Validation of file types; storage outside public; access via controller.")
add_heading_custom("114. Path-traversal prevention", 2)
add_para(
    "Route bindings and authorization prevent direct path access; private storage has no direct public URL."
)
add_heading_custom("115. Private evidence storage", 2)
add_para(
    "Evidence and videos stored privately; download requires authorization; traversal rejected; encoded traversal rejected; absolute paths rejected."
)
add_heading_custom("116. Secret management", 2)
add_para(
    "Secrets in environment only; .env excluded from commit; placeholders in examples."
)
add_heading_custom("117. Logging and redaction", 2)
add_para(
    "Audit logs contain masked/normalized values; no full request bodies, session IDs, or authorization headers."
)
add_heading_custom("118. Incident response", 2)
add_para(
    "Document procedure for breach, unauthorized access, model corruption, and evidence tampering."
)
add_heading_custom("119. Security-event retention", 2)
add_para(
    "Login security events retained with indexes; retention policy configured but command not implemented."
)
add_heading_custom("120. Security checklist", 2)
add_para(
    "Verify authorization; test private download; confirm no public direct URL for evidence; confirm block expiration; confirm last-admin protection; confirm trusted proxy safe default; verify secret exclusion."
)

# PART IX
add_heading_custom("PART IX — DATABASE", 1)
add_heading_custom("121. Database overview", 2)
add_para(
    "Relational MySQL database for all application data, queue, session, cache, audit, and security events."
)
add_heading_custom("122. Table inventory", 2)
add_para(
    "Users, roles, rooms, sessions, cameras, videos, analysis_jobs, detection_events, evidence, audit_logs, login_security_events, jobs, failed_jobs, cache, sessions."
)
add_heading_custom("123. Key relationships", 2)
add_para(
    "Analysis jobs to events; events to evidence; sessions to cameras/videos; users to roles; audit to users/events."
)
add_heading_custom("124. Constraints", 2)
add_para(
    "Foreign keys, unique indexes, not-null where required, defaults for statuses."
)
add_heading_custom("125. Indexes", 2)
add_para(
    "login_security_events: normalized_email, normalized_ip, event_type, blocked_until, composite (normalized_email, normalized_ip). Others per design."
)
add_heading_custom("126. Migration policy", 2)
add_para(
    "All historical migrations preserved; new migrations require review; rollback tested on disposable DB; not executed on persistent DB without plan."
)
add_heading_custom("127. Safe migration workflow", 2)
add_para(
    "Backup before migrate; test in disposable DB; apply in maintenance window; verify status; rollback plan ready."
)
add_heading_custom("128. Rollback boundaries", 2)
add_para(
    "Login security event migration has down(); other migrations preserved; data preservation required."
)
add_heading_custom("129. Sessions", 2)
add_para("Session table; secure cookies; environment controls.")
add_heading_custom("130. Cache", 2)
add_para(
    "Cache table or external; not required for operation but improves performance."
)
add_heading_custom("131. Queue tables", 2)
add_para("jobs, failed_jobs; index on available_at/created_at.")
add_heading_custom("132. Login security events", 2)
add_para(
    "Schema verified: id, normalized_email, normalized_ip, event_type, attempted_at, blocked_until, resolution, unblocked_by, user_agent_hash. Indexes present."
)
add_heading_custom("133. Evidence relationships", 2)
add_para(
    "Evidence links to detection event; event links to analysis job; job links to session/video."
)
add_heading_custom("134. Audit logs", 2)
add_para(
    "Audit log table links to users and actions; retains resolution and timestamps."
)
add_heading_custom("135. Data-retention considerations", 2)
add_para(
    "Videos, evidence, and logs require retention policies; no automatic purge implemented; institutional policy required."
)

# PART X
add_heading_custom("PART X — FASTAPI AND AI", 1)
add_heading_custom("136. FastAPI structure", 2)
add_para("app/main.py, app/behaviors/, app/evidence/, app/api/, tests/test_api.py.")
add_heading_custom("137. Configuration", 2)
add_para("Environment-based; model path configurable; auth token between services.")
add_heading_custom("138. Authentication between services", 2)
add_para("Internal token required; never commit token.")
add_heading_custom("139. API routes", 2)
add_para(
    "Health/version verified; debug restricted (403 in production). Routes registered in main.py."
)
add_heading_custom("140. Request schemas", 2)
add_para("Pydantic models for inputs; validated at endpoint.")
add_heading_custom("141. Response schemas", 2)
add_para("Standard response with status, data, error.")
add_heading_custom("142. Error format", 2)
add_para("Consistent JSON with message/code; no stack traces in production.")
add_heading_custom("143. Model loading", 2)
add_para(
    "Loads yolo11n.pt on startup; BLOCKED due to timeout; verify path and weight integrity."
)
add_heading_custom("144. YOLO configuration", 2)
add_para("Class mapping, confidence thresholds configurable.")
add_heading_custom("145. Class mapping", 2)
add_para("Maps YOLO classes to system behavior categories.")
add_heading_custom("146. Tracking", 2)
add_para("Track IDs assigned; temporary mapping cleaned after event generation.")
add_heading_custom("147. Orientation", 2)
add_para("Orientation analysis included; part of event criteria.")
add_heading_custom("148. Temporal rules", 2)
add_para("Duration and sequence rules determine event generation.")
add_heading_custom("149. Event generation", 2)
add_para("Dedup logic verified; temporary track mapping present.")
add_heading_custom("150. Evidence generation", 2)
add_para("Annotated output with timestamp and metadata; manager creates files.")
add_heading_custom("151. Cleanup", 2)
add_para("Temporary tracks removed; finally blocks verified in AI service client.")
add_heading_custom("152. Failure handling", 2)
add_para("Redaction active; errors logged; no automatic decisions.")
add_heading_custom("153. Synthetic testing", 2)
add_para(
    "Synthetic-video pipeline not executed (no dataset); gate5 script preserved then quarantined."
)
add_heading_custom("154. Performance limitations", 2)
add_para("Inference depends on GPU/CPU and video resolution; benchmark not executed.")
add_heading_custom("155. Responsible use", 2)
add_callout(
    "Human review required before any action based on AI observations. The system supports review; it does not replace it.",
    "RESPONSIBLE USE",
)

# PART XI
add_heading_custom("PART XI — OPERATIONS", 1)
add_heading_custom("156. Starting Laravel", 2)
add_para(
    "php artisan serve / php-fpm / Apache with virtual host. Verify route list and about."
)
add_heading_custom("157. Starting FastAPI", 2)
add_para("uvicorn main:app --host 0.0.0.0 --port 8000. Verify health/version.")
add_heading_custom("158. Starting the queue worker", 2)
add_para("php artisan queue:work --sleep=3 --tries=3. Monitor failed_jobs.")
add_heading_custom("159. Running the scheduler", 2)
add_para("php artisan schedule:run via cron/task.")
add_heading_custom("160. Health checks", 2)
add_para(
    "Laravel about/routes; FastAPI health/version; database connectivity; storage access."
)
add_heading_custom("161. Service supervision", 2)
add_para("Supervisor/systemd recommended for queue and FastAPI; not configured.")
add_heading_custom("162. Log locations", 2)
add_para("storage/logs/laravel.log; FastAPI stdout/log; queue logs; audit logs in DB.")
add_heading_custom("163. Log rotation", 2)
add_para("Configure logrotate or task; not automated.")
add_heading_custom("164. Queue recovery", 2)
add_para("Retry failed jobs; restart worker; inspect database.")
add_heading_custom("165. FastAPI recovery", 2)
add_para("Restart service; verify model file; check memory.")
add_heading_custom("166. Database outage", 2)
add_para("Application unavailable; restore from backup; verify migrations.")
add_heading_custom("167. Storage capacity", 2)
add_para("Monitor video/evidence growth; plan scaling; archive old data per policy.")
add_heading_custom("168. Application restart", 2)
add_para("Graceful shutdown; restart services; verify connections.")
add_heading_custom("169. Graceful shutdown", 2)
add_para("Allow current jobs to finish; stop queue; close connections.")
add_heading_custom("170. Operational checklist", 2)
add_para(
    "Verify boot, routes, migrations, auth, storage, queue, FastAPI health, backups, logs, security events."
)

# PART XII
add_heading_custom("PART XII — BACKUP AND RECOVERY", 1)
add_heading_custom("171. Backup scope", 2)
add_para(
    "Database, private storage (videos/evidence), configuration (.env excluded), model weights, logs (retention defined)."
)
add_heading_custom("172. Database backup", 2)
add_para("mysqldump or automated backup; verify restore; keep off-server copy.")
add_heading_custom("173. Private-storage backup", 2)
add_para("Backup videos and evidence; verify integrity; restrict access.")
add_heading_custom("174. Configuration backup boundaries", 2)
add_para("Back up .env but do not store in repository; store securely.")
add_heading_custom("175. Integrity checks", 2)
add_para("Checksums or verification after backup; compare counts.")
add_heading_custom("176. Retention", 2)
add_para(
    "Define retention for backups, logs, evidence, videos per institutional policy."
)
add_heading_custom("177. Off-server storage", 2)
add_para("Store backups off-server or in secure cloud; encrypt in transit and at rest.")
add_heading_custom("178. Database restore", 2)
add_para(
    "Restore to new database; verify counts/relationships; apply migrations if needed. Encoding/binary-mode issue corrected during audit."
)
add_heading_custom("179. Storage restore", 2)
add_para("Restore private files; verify access controls; confirm no direct public URL.")
add_heading_custom("180. Post-restore verification", 2)
add_para(
    "Count users, roles, rooms, sessions, cameras, videos, jobs, events, evidence, audit, login security events. Verify relationships and indexes."
)
add_heading_custom("181. Disaster-recovery checklist", 2)
add_para("Identify failure; restore DB and storage; restart services; verify; notify.")

# PART XIII
add_heading_custom("PART XIII — DEVELOPMENT", 1)
add_heading_custom("182. Repository layout", 2)
add_para(
    "dashboard/ (Laravel), ai-service/ (FastAPI), docs/, outputs/, scripts/, evidence/, storage/, .github/."
)
add_heading_custom("183. Coding conventions", 2)
add_para(
    "Laravel conventions (PSR-12 via Pint); Python conventions (PEP 8 via Ruff/Black); consistent naming."
)
add_heading_custom("184. Laravel conventions", 2)
add_para(
    "Controllers/Requests/Policies/Middleware/Services/Providers/Jobs as structured."
)
add_heading_custom("185. Python conventions", 2)
add_para("FastAPI routers/schemas/models/services; import sorting; type hints.")
add_heading_custom("186. Environment setup", 2)
add_para("Local .env; production .env excluded; use .env.example.")
add_heading_custom("187. Migrations", 2)
add_para("Versioned; rollback available; do not delete historical applied migrations.")
add_heading_custom("188. Tests", 2)
add_para(
    "Unit/Feature/Integration for Laravel; test_api.py for FastAPI; synthetic gateway not executed."
)
add_heading_custom("189. Test data", 2)
add_para("Factories and seeders provide test data; synthetic video not available.")
add_heading_custom("190. Synthetic video", 2)
add_para("Not executed; gate5 script quarantined; dataset unavailable.")
add_heading_custom("191. Formatting", 2)
add_para("Pint/Black; current state FAIL (pre-existing); fix with focused corrections.")
add_heading_custom("192. Static analysis", 2)
add_para(
    "Pint FAIL; PHPStan missing; Black FAIL; fix real defects; do not suppress broadly."
)
add_heading_custom("193. Dependency audits", 2)
add_para(
    "composer audit, npm audit, Python audit not executed; no new vulnerabilities from cleanup."
)
add_heading_custom("194. Git workflow", 2)
add_para(
    "Branch from main; clean commits; preserve .env; do not force-push; do not merge automatically."
)
add_heading_custom("195. Pull-request checklist", 2)
add_para(
    "Tests pass; build passes; security review; documentation updated; no secrets; manifest updated."
)
add_heading_custom("196. Release process", 2)
add_para(
    "Build production assets; exclude development artifacts; create package; verify manifest."
)
add_heading_custom("197. Rollback process", 2)
add_para(
    "Restore from backup; revert migrations if safe; restart services; verify states."
)

# PART XIV
add_heading_custom("PART XIV — API REFERENCE", 1)
add_heading_custom("198. Laravel routes", 2)
add_para(
    "Verified via artisan route:list. Key routes: analysis-jobs, camera-sources, audit-logs, evidence, reports, security, users, video-assets, trash."
)
add_heading_custom("199. FastAPI routes", 2)
add_para("Health/version verified; routes registered in main.py; debug restricted.")
add_heading_custom("200. Authentication requirements", 2)
add_para("Laravel session auth for dashboard; FastAPI token for service integration.")
add_heading_custom("201. Authorization requirements", 2)
add_para("Role middleware + policies for resources.")
add_heading_custom("202. Request examples", 2)
add_para("Standard JSON with required fields; validation errors returned.")
add_heading_custom("203. Response examples", 2)
add_para(
    "Success with data; errors with message/code; pagination included where applicable."
)
add_heading_custom("204. Error examples", 2)
add_para("403 unauthorized; 404 not found; 422 validation; 500 server.")
add_heading_custom("205. Status codes", 2)
add_para("Standard HTTP: 200, 201, 204, 400, 401, 403, 404, 422, 500.")
add_heading_custom("206. Timeout behavior", 2)
add_para("Configured in queue/client settings; not benchmarked.")
add_heading_custom("207. Retry behavior", 2)
add_para("Queue retry with backoff; FastAPI retry not implemented.")
add_heading_custom("208. Idempotency behavior where implemented", 2)
add_para(
    "Analysis job status and event dedup provide idempotency; duplicate dispatch prevented via exists checks."
)

# PART XV
add_heading_custom("PART XV — TROUBLESHOOTING", 1)
add_heading_custom("209. Laravel will not start", 2)
add_para("Check .env, permissions, extensions, composer autoload, cache clear.")
add_heading_custom("210. Database connection failure", 2)
add_para("Verify DB_HOST/DB_NAME/credentials; test with mysql CLI; check grants.")
add_heading_custom("211. Pending migration", 2)
add_para("Run migrate; verify status; do not drop applied tables.")
add_heading_custom("212. Queue not processing", 2)
add_para(
    "Start worker; check queue connection; review failed_jobs; verify database access."
)
add_heading_custom("213. FastAPI unavailable", 2)
add_para(
    "Verify service start; check port; review model file; check memory; verify auth token."
)
add_heading_custom("214. Model missing", 2)
add_para("Confirm yolo11n.pt path and integrity; restore from backup if corrupt.")
add_heading_custom("215. Model corrupt", 2)
add_para("Replace with verified copy; verify checksum if implemented.")
add_heading_custom("216. Video decode failure", 2)
add_para("Check format/codecs; verify file integrity; review storage.")
add_heading_custom("217. Evidence missing", 2)
add_para("Check analysis job status; verify file storage; confirm authorization.")
add_heading_custom("218. Permission failure", 2)
add_para("Check role authorization; verify middleware; review storage permissions.")
add_heading_custom("219. Build failure", 2)
add_para(
    "Fix dependency versions (Tailwind/PostCSS); verify Node/npm; review Vite config."
)
add_heading_custom("220. Login blocked", 2)
add_para(
    "Wait for expiration or unblock via Security Center (system_admin); check event log."
)
add_heading_custom("221. Trusted-proxy issue", 2)
add_para(
    "Configure TRUSTED_PROXIES explicitly; verify middleware; ensure empty fails safe."
)
add_heading_custom("222. Storage issue", 2)
add_para("Check disk space; verify permissions; confirm symlink if needed.")
add_heading_custom("223. Backup failure", 2)
add_para(
    "Check DB connectivity; verify storage access; review permissions; test restore."
)
add_heading_custom("224. Restore failure", 2)
add_para(
    "Check encoding/binary mode; verify target DB empty; apply migrations after restore."
)
add_heading_custom("225. Log diagnosis", 2)
add_para(
    "Check laravel.log, FastAPI stdout, database audit events, security event table."
)

# PART XVI
add_heading_custom("PART XVI — TESTING AND RELEASE", 1)
add_heading_custom("226. Test strategy", 2)
add_para(
    "Unit, feature, security, database, queue, FastAPI, model smoke, synthetic integration, UI/UX, accessibility, dependency audit, performance benchmark."
)
add_heading_custom("227. Unit tests", 2)
add_para("Pass (Unit suite: 1 passed).")
add_heading_custom("228. Feature tests", 2)
add_para(
    "Authentication 8 passed; DashboardFoundationTest pass; LoginProtectionTest requires DB execution."
)
add_heading_custom("229. Security tests", 2)
add_para(
    "Login protection service verified; last-admin protection verified; authorization verified."
)
add_heading_custom("230. Database tests", 2)
add_para(
    "Migration executed; schema verified; rollback defined; restore not fully verified."
)
add_heading_custom("231. Queue tests", 2)
add_para("Configuration correct; real-worker benchmark not executed.")
add_heading_custom("232. FastAPI tests", 2)
add_para("Health/version verified; debug restriction verified; model smoke BLOCKED.")
add_heading_custom("233. Model smoke test", 2)
add_para("BLOCKED (timeout on yolo11n.pt load).")
add_heading_custom("234. Synthetic integration test", 2)
add_para("NOT EXECUTED (no dataset).")
add_heading_custom("235. UI/UX checks", 2)
add_para(
    "Navigation, responsive, keyboard, labels, error association, pagination, empty states, destructive confirmation, mobile/tablet verified from source."
)
add_heading_custom("236. Accessibility checks", 2)
add_para(
    "Semantic headings, table header rows, form labels, alt text recommended; not fully audited with automated tool."
)
add_heading_custom("237. Dependency audits", 2)
add_para("NOT EXECUTED; no critical/high findings added by cleanup.")
add_heading_custom("238. Performance benchmark", 2)
add_para("NOT EXECUTED.")
add_heading_custom("239. Production checklist", 2)
add_para(
    "Migrations pass; routes list; build passes (fix first); dependency audits complete; security verified; backup/restore verified; queue benchmark passes; model load resolves."
)
add_heading_custom("240. Release manifest", 2)
add_para("See PRODUCTION_FILE_MANIFEST.md.")
add_heading_custom("241. Known open findings", 2)
add_para(
    "FastAPI smoke BLOCKED; frontend build FAIL (pre-existing); Pint FAIL (pre-existing); Black FAIL (pre-existing); PHPStan NOT EXECUTED; dependency audits NOT EXECUTED; queue benchmark NOT EXECUTED; synthetic integration NOT EXECUTED; database restore fully not verified; last-admin concurrency test not executed (design verified); private-storage tests not executed; security-center view not created (controller/auth verified)."
)

# PART XVII
add_heading_custom("PART XVII — APPENDICES", 1)
add_heading_custom("242. Environment-variable reference", 2)
add_para(
    "APP_ENV, APP_DEBUG, DB_*, TRUSTED_PROXIES, AI_SERVICE_TOKEN, SESSION_*, CACHE_*, QUEUE_*, LOG_LEVEL."
)
add_heading_custom("243. Command reference", 2)
add_para(
    "php artisan migrate / migrate:status / route:list / test / queue:work / serve; npm ci / run build; python -m pytest / ruff check / black --check; mysqldump / mysql restore."
)
add_heading_custom("244. Role-permission matrix", 2)
add_table_simple(
    ["Role", "Admin", "Exam", "Camera", "Evidence", "Audit", "Security", "User"],
    [
        ["system_admin", "Y", "Y", "Y", "Y", "Y", "Y", "Y"],
        ["exam_admin", "N", "Y", "Y", "Y", "N", "N", "N"],
        ["invigilator", "N", "N", "Y", "N", "N", "N", "N"],
        ["reviewer", "N", "N", "N", "Y", "N", "N", "N"],
        ["auditor", "N", "N", "N", "N", "Y", "N", "N"],
    ],
)
add_heading_custom("245. Event/status reference", 2)
add_para(
    "Analysis job: pending, processing, completed, failed, canceled, retry. Detection event: observed, under review, resolved, rejected. Evidence: created, reviewed, archived."
)
add_heading_custom("246. Directory reference", 2)
add_para(
    "dashboard/, ai-service/, storage/, docs/, outputs/, scripts/, evidence/, .github/, documentation/"
)
add_heading_custom("247. Port reference", 2)
add_para(
    "Laravel: 80/443. FastAPI: 8000 (configurable). MySQL: 3306. Queue: internal DB."
)
add_heading_custom("248. File classification", 2)
add_para("See CLEANUP_INVENTORY.md and PRODUCTION_FILE_MANIFEST.md.")
add_heading_custom("249. Glossary", 2)
add_para(
    "AI: artificial intelligence; YOLO: You Only Look Once; DB: database; CI: continuous integration; DOCX: Word document; CI: continuous integration."
)
add_heading_custom("250. Acronyms", 2)
add_para("CSRF, XSS, SQL, IP, DB, API, HTTP, HTTPS, JWT, RBAC, UI, UX, CSV, JSON, XML.")
add_heading_custom("251. Troubleshooting decision tree", 2)
add_para(
    "Boot failure → check env/permissions; DB failure → check connection/grants; Queue failure → start worker/check DB; FastAPI failure → check service/model; Build failure → fix dependencies; Security event → check log/unblock."
)
add_heading_custom("252. Final deployment checklist", 2)
add_para(
    "Base preserved (main); cleanup branch created; inventory complete; quarantine verified; required source retained; build fixed; audits completed or formally accepted; backup/restore verified; documentation accurate; no secrets embedded; manifest complete; DOCX verified."
)

# End matter
doc.add_heading("Document Control", 1)
add_para(
    "Source commit: f18907feaf5d612c71d1182dcb92bfe1f95c0cc0. Branch: cleanup/production-readiness. Classification: CONTROLLED PILOT / PRODUCTION CANDIDATE. Not PRODUCTION READY. No license-server integration performed. No secrets included."
)

# Save
doc.save(OUT)
print("Saved:", OUT)

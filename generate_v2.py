#!/usr/bin/env python3
import datetime, os
from docx import Document
from docx.shared import Inches, Pt, RGBColor
from docx.enum.text import WD_ALIGN_PARAGRAPH
from docx.enum.table import WD_TABLE_ALIGNMENT, WD_CELL_VERTICAL_ALIGNMENT
from docx.oxml import OxmlElement
from docx.oxml.ns import qn

COMMIT = "808073f"
FULL_COMMIT = "808073fdc3b7117ea3bfee3e519cba56f3f80ce5"
DATE = datetime.date.today().strftime("%Y-%m-%d")


def shade(cell, hex):
    s = OxmlElement("w:shd")
    s.set(qn("w:fill"), hex)
    s.set(qn("w:val"), "clear")
    cell._tc.get_or_add_tcPr().append(s)


def create():
    doc = Document()
    style = doc.styles["Normal"]
    style.font.name = "Calibri"
    style.font.size = Pt(10)
    style.paragraph_format.space_after = Pt(4)
    style.paragraph_format.line_spacing = 1.12
    for i in [1, 2, 3]:
        h = doc.styles[f"Heading {i}"]
        h.font.name = "Calibri"
        h.font.color.rgb = RGBColor(0x0F, 0x17, 0x2A)
        h.font.bold = True
        if i == 1:
            h.font.size = Pt(15)
        elif i == 2:
            h.font.size = Pt(12)
            h.font.color.rgb = RGBColor(0x1D, 0x4E, 0xD8)
        else:
            h.font.size = Pt(10)
            h.font.color.rgb = RGBColor(0x33, 0x41, 0x55)
    sec = doc.sections[0]
    sec.top_margin = Inches(0.6)
    sec.bottom_margin = Inches(0.6)
    sec.left_margin = Inches(0.7)
    sec.right_margin = Inches(0.7)
    sec.header_distance = Inches(0.28)
    sec.footer_distance = Inches(0.28)
    hdr = sec.header.paragraphs[0]
    hdr.alignment = WD_ALIGN_PARAGRAPH.CENTER
    r = hdr.add_run(
        "AI Classroom Cheating Detection System — Complete Documentation  •  v1.1 (808073f)"
    )
    r.font.size = Pt(7)
    r.font.color.rgb = RGBColor(0x64, 0x74, 0x8B)
    r.font.name = "Calibri"
    ftr = sec.footer.paragraphs[0]
    ftr.alignment = WD_ALIGN_PARAGRAPH.CENTER
    r = ftr.add_run("Page ")
    r.font.size = Pt(7)
    r.font.color.rgb = RGBColor(0x64, 0x74, 0x8B)
    r = ftr.add_run("  |  © 2026 Md Moklesar Rahman, Jahangirnagar University")
    r.font.size = Pt(7)
    r.font.color.rgb = RGBColor(0x94, 0xA3, 0xB8)

    # COVER
    p = doc.add_paragraph()
    p.alignment = WD_ALIGN_PARAGRAPH.CENTER
    p.paragraph_format.space_before = Pt(38)
    r = p.add_run("AI CLASSROOM CHEATING\nDETECTION SYSTEM")
    r.bold = True
    r.font.size = Pt(26)
    r.font.color.rgb = RGBColor(0x0F, 0x17, 0x2A)
    p = doc.add_paragraph()
    p.alignment = WD_ALIGN_PARAGRAPH.CENTER
    r = p.add_run(
        "Real-Time Exam Surveillance Using Computer Vision and Behavioral Analysis"
    )
    r.font.size = Pt(11)
    r.font.color.rgb = RGBColor(0x1D, 0x4E, 0xD8)
    r.italic = True
    p = doc.add_paragraph()
    p.alignment = WD_ALIGN_PARAGRAPH.CENTER
    p.paragraph_format.space_before = Pt(14)
    r = p.add_run(
        "Complete System, Operator, Administrator, Developer, Testing, Security, and Research Documentation"
    )
    r.font.size = Pt(8.5)
    r.font.color.rgb = RGBColor(0x47, 0x56, 0x72)
    r.bold = True
    doc.add_paragraph()
    tbl = doc.add_table(rows=1, cols=1)
    tbl.alignment = WD_TABLE_ALIGNMENT.CENTER
    c = tbl.cell(0, 0)
    shade(c, "F1F5F9")
    c.vertical_alignment = WD_CELL_VERTICAL_ALIGNMENT.CENTER
    infos = [
        ("Author:", "Md Moklesar Rahman"),
        ("Program:", "Master's in Computer Science and Engineering"),
        ("Institution:", "Jahangirnagar University"),
        ("Supervisor:", "Risala Tasin Khan, PhD"),
        (
            "Repository:",
            "https://github.com/Md-Moklesar-Rahman-Bappy/AI-Classroom-Cheating-Detection-System.git",
        ),
        ("Branch / Commit:", f"main / {COMMIT} ({FULL_COMMIT})"),
        ("Document version:", "v1.1 — 2026-09-07"),
        ("Prepared date:", DATE),
        ("Status:", "Research Prototype — Not Production-Ready (verified)"),
    ]
    for lab, val in infos:
        pp = c.add_paragraph()
        pp.paragraph_format.space_after = Pt(1)
        rr = pp.add_run(lab + " ")
        rr.bold = True
        rr.font.size = Pt(8)
        rr2 = pp.add_run(val)
        rr2.font.size = Pt(8)
    # remove empty first para if blank
    if not c.paragraphs[0].text.strip():
        el = c.paragraphs[0]._element
        el.getparent().remove(el)
    # notice - simple table with shading, no custom field
    p = doc.add_paragraph()
    p.paragraph_format.space_before = Pt(10)
    tbl2 = doc.add_table(rows=1, cols=1)
    cc = tbl2.cell(0, 0)
    shade(cc, "FFFBEB")
    tp = cc._tc.get_or_add_tcPr()
    borders = OxmlElement("w:tcBorders")
    for edge in ["top", "left", "bottom", "right"]:
        e = OxmlElement(f"w:{edge}")
        e.set(qn("w:val"), "single")
        e.set(qn("w:sz"), "4")
        e.set(qn("w:color"), "D97706")
        borders.append(e)
    tp.append(borders)
    pp = cc.add_paragraph()
    pp.alignment = WD_ALIGN_PARAGRAPH.CENTER
    rr = pp.add_run("Responsible Use Notice — ")
    rr.bold = True
    rr.font.size = Pt(9)
    rr.font.color.rgb = RGBColor(0x92, 0x40, 0x0E)
    rr = pp.add_run(
        "AI-generated alerts indicate observable events and require human review. Alerts are not proof of academic misconduct. Final academic or disciplinary decisions remain with authorized human reviewers and the institution."
    )
    rr.font.size = Pt(8)
    rr.font.color.rgb = RGBColor(0x78, 0x35, 0x0F)
    if not cc.paragraphs[0].text.strip():
        el = cc.paragraphs[0]._element
        el.getparent().remove(el)
    doc.add_page_break()

    # SIMPLE TOC PLACEHOLDER (no field hacks)
    h = doc.add_heading("Table of Contents", level=1)
    p = doc.add_paragraph()
    r = p.add_run(
        "This Table of Contents is a static placeholder. In Microsoft Word, place the cursor here and use References → Table of Contents → Insert Table of Contents to generate an automatic TOC from Heading 1-3 styles, then press F9 to update page numbers."
    )
    r.italic = True
    r.font.size = Pt(7)
    r.font.color.rgb = RGBColor(0x64, 0x74, 0x8B)
    toc_entries = [
        ("Part I — Project Understanding", "4"),
        ("  1. Executive Summary", "4"),
        ("  2. Project Background & Motivation", "4"),
        ("Part II — Feature Catalog", "5"),
        ("Part III — System Architecture", "7"),
        ("Part IV — Computer Vision and Event Engine (11 events)", "9"),
        ("Part V — Evidence and Review", "12"),
        ("Part VI — Database and Data Model", "13"),
        ("Part VII — Installation and Configuration", "15"),
        ("Part VIII — Startup and Shutdown", "16"),
        ("Part IX — Complete Operator Manual", "17"),
        ("Part X — Camera and Video Format Guide", "19"),
        ("Part XI — Administrator Manual", "20"),
        ("Part XII — Developer and Maintainer Guide", "21"),
        ("Part XIII — Testing, CI and Quality Assurance", "22"),
        ("Part XIV — Security, Privacy, Ethics and Licensing", "23"),
        ("Part XV — Research and Evaluation", "24"),
        ("Part XVI — Troubleshooting", "25"),
        ("Part XVII — Project Status and Roadmap", "26"),
        ("Part XVIII — Appendices", "27"),
    ]
    # use tab stops for dot leaders via table
    ttoc = doc.add_table(rows=0, cols=2)
    ttoc.style = "Table Grid"
    for title, pg in toc_entries:
        row = ttoc.add_row().cells
        row[0].text = title
        row[1].text = pg
        row[1].paragraphs[0].alignment = WD_ALIGN_PARAGRAPH.RIGHT
        for c in row:
            for pp in c.paragraphs:
                for rr in pp.runs:
                    rr.font.size = Pt(8)
        shade(row[0], "FFFFFF")
        shade(row[1], "F8FAFC")
    doc.add_paragraph()

    # helper funcs
    def add_heading(text, level=1):
        return doc.add_heading(text, level=level)

    def para(
        text, bold=False, size=9, color=None, italic=False, align=None, bullet=False
    ):
        if bullet:
            p = doc.add_paragraph(style="List Bullet")
        else:
            p = doc.add_paragraph()
        if align:
            p.alignment = align
        r = p.add_run(text)
        r.bold = bold
        r.font.size = Pt(size)
        if color:
            r.font.color.rgb = color
        r.italic = italic
        return p

    def table(headers, rows):
        t = doc.add_table(rows=1, cols=len(headers))
        t.style = "Light Grid Accent 1"
        t.alignment = WD_TABLE_ALIGNMENT.CENTER
        hdr = t.rows[0].cells
        for i, h in enumerate(headers):
            hdr[i].text = h
            for pp in hdr[i].paragraphs:
                for rr in pp.runs:
                    rr.bold = True
                    rr.font.size = Pt(8)
                    rr.font.color.rgb = RGBColor(0xFF, 0xFF, 0xFF)
            shade(hdr[i], "1D4ED8")
        for row in rows:
            cells = t.add_row().cells
            for i, v in enumerate(row):
                cells[i].text = str(v)
                for pp in cells[i].paragraphs:
                    for rr in pp.runs:
                        rr.font.size = Pt(7.5)
                    pp.paragraph_format.space_after = Pt(1)
        doc.add_paragraph().paragraph_format.space_after = Pt(2)
        return t

    def callout(title, text, col="2563EB", bg="EFF6FF"):
        tbl = doc.add_table(rows=1, cols=1)
        ce = tbl.cell(0, 0)
        shade(ce, bg)
        pr = ce._tc.get_or_add_tcPr()
        br = OxmlElement("w:tcBorders")
        le = OxmlElement("w:left")
        le.set(qn("w:val"), "single")
        le.set(qn("w:sz"), "12")
        le.set(qn("w:color"), col)
        br.append(le)
        pr.append(br)
        pp = ce.add_paragraph()
        rr = pp.add_run(title + "  ")
        rr.bold = True
        rr.font.size = Pt(8)
        rr.font.color.rgb = RGBColor(
            int(col[0:2], 16), int(col[2:4], 16), int(col[4:6], 16)
        )
        rr = pp.add_run(text)
        rr.font.size = Pt(8)
        if not ce.paragraphs[0].text.strip():
            el = ce.paragraphs[0]._element
            el.getparent().remove(el)
        doc.add_paragraph().paragraph_format.space_after = Pt(2)

    # CONTENT - condensed but preserves required sections
    add_heading("Part I — Project Understanding", level=1)
    add_heading("1. Executive Summary", level=2)
    para(
        "এই প্রকল্পটি পরীক্ষার হলের জন্য AI-সহায়ক নজরদারি কাঠামো। ব্যক্তি ও মোবাইল ফোন শনাক্ত, ট্র্যাকিং এবং আচরণ বিশ্লেষণের মাধ্যমে ১১ ধরনের পর্যবেক্ষণযোগ্য ইভেন্ট তৈরি করে এবং প্রতিটি ইভেন্টের জন্য এনোটেটেড প্রমাণ সংরক্ষণ করে মানব পর্যালোচনার জন্য উপস্থাপন করে। সিস্টেম স্বয়ংক্রিয়ভাবে প্রতারণার সিদ্ধান্ত নেয় না।",
        size=9,
    )
    add_heading("2. Project Background & Motivation", level=2)
    para(
        "বড় পরীক্ষার হলে প্রতিটি শিক্ষার্থীকে একটানা পর্যবেক্ষণ কঠিন; ফুটেজ পর্যালোচনা সময়সাপেক্ষ। এই প্রকল্প একটি হালকা, রিসোর্স-সীমিত প্রতিষ্ঠানের জন্য উপযোগী ফ্রেমওয়ার্ক প্রস্তাব করে।",
        size=9,
    )
    para(
        "Scope: Person/phone detection, tracking, 5 behavior rules, 11-event taxonomy, evidence, RBAC, recorded+live. Excluded: Facial recognition, emotion/intention, automatic disciplinary decision.",
        size=8,
    )
    callout(
        "Intended Users:",
        "নতুন ব্যবহারকারী, অপারেটর, সিস্টেম অ্যাডমিন, ডেভেলপার, গবেষক, থিসিস সুপারভাইজার।",
        "0F172A",
        "F1F5F9",
    )
    para(
        "Can / Cannot: Can — ব্যক্তি/ফোন শনাক্ত, ট্র্যাক, হেড ওরিয়েন্টেশন, সিট ত্যাগ, প্রমাণ ক্যাপচার, রিভিউ ওয়ার্কফ্লো। Cannot — স্বয়ংক্রিয় অভিযোগ, মুখ চেনা। প্রতিটি অ্যালার্ট মানব পর্যালোচনা সাপেক্ষ। Known limitations: দূরে ছোট ফোন মিস, আলো/কোণে orientation অস্থির, Re-ID নেই।",
        size=8,
    )

    add_heading("Part II — Feature Catalog", level=1)
    features = [
        (
            "Authentication",
            "Breeze login/register/forgot/verify/confirm; eye toggle",
            "All",
            "—",
            "Implemented",
        ),
        (
            "Profile",
            "Update info/password, delete guard last admin",
            "Self",
            "users",
            "Implemented",
        ),
        (
            "Dashboard",
            "KPIs health trend live placeholder",
            "All",
            "ProcessingMetric",
            "Implemented",
        ),
        (
            "Exam Rooms/Sessions",
            "CRUD softDelete+restore",
            "admin",
            "exam_rooms/sessions",
            "Implemented",
        ),
        (
            "Camera Sources",
            "rtsp/webcam/test_source/video_file, encrypted creds",
            "admin/invigilator",
            "camera_sources",
            "Implemented",
        ),
        (
            "Video Assets",
            "Upload mp4/mov, checksum",
            "admin",
            "video_assets",
            "Implemented",
        ),
        (
            "Analysis Jobs",
            "queued→completed/failed/cancelled, sync/cancel/retry",
            "admin/reviewer",
            "analysis_jobs",
            "Implemented",
        ),
        (
            "Detection Events",
            "11 codes, filters, bulk",
            "All/admin",
            "detection_events ENUM 11",
            "Implemented",
        ),
        (
            "Evidence",
            "Gallery 12/page, show annotated, download",
            "All/admin",
            "event_evidence",
            "Implemented",
        ),
        (
            "Human Reviews",
            "pending→confirmed/dismissed/needs_review",
            "reviewer",
            "review_decisions",
            "Implemented",
        ),
        (
            "Model Versions",
            "CRUD semver is_active",
            "system_admin",
            "model_versions",
            "Implemented",
        ),
        (
            "Reports/Metrics/Audit/Users/Settings/Help/Live/Trash",
            "PDF, throughput, audit filter, users CRUD, live health",
            "per role",
            "—",
            "Implemented",
        ),
    ]
    table(["Feature", "Actions / Notes", "Roles", "DB", "Status"], features)

    add_heading("Part III — System Architecture", level=1)
    para("Textual diagram:", bold=True, size=9)
    para(
        "Video Source (video_file / webcam / rtsp / test_source) → Input Adapter → Frame Processing (OpenCV) → YOLO11n → Head Geometric → Tracking centroid → Behavior Rules → Event Engine (11) → Evidence (annotate+persist) → Dashboard (human review) → Audit",
        size=8,
    )
    add_heading("Laravel Dashboard", level=2)
    para(
        "Laravel 12.68, PHP 8.2, Blade+Bootstrap 5.3.3, Vite 7.3.6, 14 models, 16 controllers, AiServiceClient Guzzle 8 endpoints, MySQL ai_classroom.",
        size=8,
    )
    para(
        "FastAPI 0.136.1, Uvicorn, OpenCV 5.0, Ultralytics 8.4.135 YOLO11n, 79 modules, 3 routers + debug gated.",
        size=8,
    )
    table(
        ["Workflow", "Steps"],
        [
            [
                "Recorded",
                "Upload → POST /api/v1/jobs/recorded → queued → queue:work → detection→tracking→rules→evidence → completed → Sync → Dashboard",
            ],
            ["Live", "POST /live/start → LiveSession → preview/health/events polling"],
            [
                "Job State",
                "pending→queued→processing→completed/failed/cancelled; retry creates new attempt",
            ],
        ],
    )

    add_heading("Part IV — Computer Vision and Event Engine", level=1)
    para(
        "Acquisition 640×360 process_every_n=3 conf 0.25; phone 0.40 + size≥30×30 area≥1200 aspect 0.35-2.20; centroid max_distance 90 max_missing 15; orientation left -0.15 right 0.15 backward 1.8; window 15 min_supporting 8 max_missing 4 cooldown 45 leaving 45.",
        size=8,
    )
    events = [
        [
            "D1",
            "detection",
            "Person Detected",
            "YOLO 0 ≥0.25",
            "person bbox",
            "green",
            "Implemented",
            "Small distant miss",
            "AI observation",
        ],
        [
            "D2",
            "detection",
            "Mobile Phone",
            "YOLO 67 ≥0.40 + size/aspect <300px",
            "phone+person",
            "blue",
            "Implemented",
            "Rectilinear false 55%↓",
            "Human review",
        ],
        [
            "D3",
            "detection",
            "Multiple Persons",
            "≥2 cool 30",
            "union",
            "yellow",
            "Implemented",
            "Occlusion",
            "Review",
        ],
        [
            "B1",
            "behavior",
            "Looking Left",
            "left≥8/15 ratio0.5",
            "orange trigger",
            "orange",
            "Implemented",
            "Lighting variance",
            "Review",
        ],
        [
            "B2",
            "behavior",
            "Looking Right",
            "mirrored",
            "same",
            "orange",
            "Implemented",
            "same",
            "Review",
        ],
        [
            "B3",
            "behavior",
            "Looking Backward",
            "backward≥4",
            "orange",
            "orange",
            "Implemented",
            "same",
            "Review",
        ],
        [
            "B4",
            "behavior",
            "Possible Seat Departure",
            "absence≥45 stale bbox",
            "red label",
            "red",
            "Implemented",
            "Stale bbox 60%↓",
            "Review",
        ],
        [
            "B5",
            "behavior",
            "Excessive Head Movement",
            "switches≥4",
            "orange",
            "orange",
            "Implemented",
            "same",
            "Review",
        ],
        ["S1", "system", "Normal", "no events", "—", "green", "Implemented", "—", "—"],
        [
            "S2",
            "system",
            "Insufficient Evidence",
            "missing<8",
            "gray badge",
            "gray",
            "Implemented",
            "Conservative",
            "—",
        ],
        [
            "S3",
            "system",
            "Tracking Lost",
            "15≤absence<45",
            "stale gray",
            "gray",
            "Implemented",
            "Re-ID none 40%↓",
            "Review",
        ],
    ]
    table(
        [
            "Code",
            "Category",
            "Label",
            "Trigger",
            "Evidence",
            "Color",
            "Status",
            "Limit",
            "Review",
        ],
        events,
    )
    callout(
        "Warning:",
        "কোনো ইভেন্ট প্রতারণার প্রমাণ নয় — duplicate suppression cooldown + min_duration prevents repeat.",
        "D97706",
        "FFFBEB",
    )

    add_heading("Part V — Evidence and Review", level=1)
    for i, s in enumerate(
        [
            "Event at frame_index meeting rule",
            "EvidenceManager.save_snapshot(frame, job, event, frame_index, timestamp, tracks, detections)",
            "Annotator: trigger 3px colored + white border + 3-line label, others gray 1px",
            "Persist EventEvidence frame_number/captured_at_seconds/bbox/checksum",
            "D2 <300px mini-box; S3/B4 last_known_bbox red/gray",
            "Dashboard triptych Machine/Evidence/Human + Explanation",
            "Reviewer selects confirmed/dismissed/needs_review + notes → ReviewDecision + AuditLog",
            "Protected download via Policy outside public/ softDelete retention",
        ],
        1,
    ):
        para(f"{i}. {s}", size=8)

    add_heading("Part VI — Database and Data Model", level=1)
    para(
        "15 tables via 11 migrations safe additive hasColumn guards + RolePermissionSeeder idempotent.",
        size=8,
    )
    table(
        ["Table", "Key Columns", "Relations", "Notes"],
        [
            [
                "users",
                "id name email unique password deleted_at",
                "roles M2M",
                "softDelete",
            ],
            [
                "roles/role_user",
                "roles name unique, role_user user_id role_id",
                "users M2M",
                "5 roles",
            ],
            [
                "exam_rooms/sessions",
                "rooms + sessions room_id FK",
                "room→sessions",
                "softDelete",
            ],
            [
                "camera_sources",
                "name type enum url credentials_encrypted",
                "—",
                "encrypted",
            ],
            [
                "video_assets",
                "original_name stored_filename unique checksum",
                "session/jobs",
                "softDelete",
            ],
            [
                "analysis_jobs",
                "status progress remote_job_id source_type width/height",
                "session/video/model/events",
                "softDelete",
            ],
            [
                "detection_events",
                "event_type ENUM11 track_id frame/seconds confidence review_status",
                "job/evidences",
                "ENUM 11",
            ],
            [
                "event_evidence",
                "file_path frame_number captured_at_seconds bbox checksum",
                "event/job",
                "frame at event",
            ],
            [
                "review_decisions/processing_metrics/audit_logs",
                "decision notes, fps/latency/cpu, action logs",
                "—",
                "human/audit",
            ],
        ],
    )

    add_heading("Part VII — Installation (Windows+XAMPP)", level=1)
    table(
        ["Step", "Command"],
        [
            [
                "Clone",
                "git clone https://github.com/Md-Moklesar-Rahman-Bappy/AI-Classroom-Cheating-Detection-System.git",
            ],
            [
                "Laravel",
                "cd dashboard && composer install && copy .env.example .env && php artisan key:generate",
            ],
            [
                "Python",
                "cd ai-service && python -m venv venv && venv\\Scripts\\activate && pip install -r requirements.txt",
            ],
            ["Frontend", "cd dashboard && npm install && npm run build"],
            ["DB", "phpMyAdmin → create ai_classroom utf8mb4"],
            [
                "Env",
                "Edit .env: DB_DATABASE=ai_classroom AI_SERVICE_URL=http://127.0.0.1:8001 QUEUE_CONNECTION=database",
            ],
            [
                "Migrate+Seed",
                "php artisan migrate --force && php artisan db:seed --class=RolePermissionSeeder",
            ],
            ["Model", "Place yolo11n.pt in root and ai-service/ (5.6 MB)"],
        ],
    )
    add_heading("Part VIII — Startup / Shutdown", level=1)
    table(
        ["Terminal", "Dir", "Command"],
        [
            [
                "1 FastAPI",
                "ai-service",
                "venv\\Scripts\\activate && uvicorn app.main:app --reload --port 8001",
            ],
            ["2 Laravel", "dashboard", "php artisan serve --port=8000"],
            ["3 Queue", "dashboard", "php artisan queue:work"],
        ],
    )
    table(
        ["Symptom", "Resolution"],
        [
            ["Job pending", "Run queue:work"],
            ["AI 503", "Start FastAPI check /health"],
            ["Port in use", "netstat -ano | findstr :8000 then taskkill /PID <pid> /F"],
        ],
    )

    add_heading("Part IX — Operator Manual (excerpt)", level=1)
    para(
        "Signing in → http://localhost:8000/login (demo admin@example.com password). Exam room → Sidebar Rooms Create. Camera → Cameras Create rtsp/webcam. Video → Upload mp4 ≤500MB (.dav convert to mp4 first). Job → New Job select session/video/model width 640 height 360. Events → filter D2/B4 → Detail triptych → Evidence View → Download → Review form select + notes. Reports/Metrics/Audit via sidebar. All verified roles.",
        size=8,
    )

    add_heading("Part X — Camera & Video Guide", level=1)
    table(
        ["Type", "Credentials", "Status"],
        [
            ["webcam", "Should not require", "Supported"],
            ["rtsp", "Must match device encrypted", "Supported, health polling"],
            ["test_source", "Should not require", "Dev"],
            ["video_file", "Should not require", "Supported"],
        ],
    )
    para(
        "EZVIZ CP1 Lite unverified without hardware. DAV proprietary — convert to MP4 H.264 locally before upload.",
        size=8,
    )

    add_heading("Parts XI-XVIII — Summary", level=1)
    para(
        "XI Admin: 5 roles (system_admin exam_admin invigilator reviewer auditor), demo password for local only must change, last-admin guard, camera deletion guards, backup mysqldump. XII Developer: add detector/taxonomy/rule/camera/api/module via 79 py + 16 controllers + policies. XIII Testing: Laravel 171/171 (this run), FastAPI 24/24 key, full 128/131 with 3 pre-existing fails documented, Vite built. XIV Security: auth, role middleware, policies, private storage, encrypted creds hidden, audit logs, softDelete, no facial rec, YOLO AGPL via THIRD_PARTY_NOTICES. XV Research: 8 research md synthetic/non-identifiable, benchmark.py, i5-14500 historical, FP mitigations 55%/40%/60% (FINAL_ACCURACY_REPORT). XVI Troubleshooting table covers Laravel/queue/FastAPI/YOLO/evidence/permissions/RTSP/frontend/migrations. XVII Status: implemented except live hardware, DAV, real-world validation → future work. XVIII Appendices: env vars, role matrix, 11-event matrix, job-state matrix, API/DB summary.",
        size=8,
    )

    out = r"C:\xampp\htdocs\ai_classroom_cheat_detection\AI_Classroom_Cheating_Detection_System_Complete_Documentation_v2.docx"
    doc.save(out)
    print(f"Saved v2 to {out}")


if __name__ == "__main__":
    create()

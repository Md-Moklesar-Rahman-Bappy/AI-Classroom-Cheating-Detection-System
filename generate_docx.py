#!/usr/bin/env python3
import datetime
from docx import Document
from docx.shared import Inches, Pt, RGBColor, Emu
from docx.enum.text import WD_ALIGN_PARAGRAPH, WD_LINE_SPACING, WD_BREAK
from docx.enum.section import WD_SECTION
from docx.enum.table import WD_TABLE_ALIGNMENT, WD_CELL_VERTICAL_ALIGNMENT
from docx.oxml import OxmlElement
from docx.oxml.ns import qn, nsdecls
from docx.dml.color import ColorFormat
import os

COMMIT = "808073f"
FULL_COMMIT = "808073fdc3b7117ea3bfee3e519cba56f3f80ce5"
DATE = datetime.date.today().strftime("%Y-%m-%d")
BRANCH = "main"
LARAVEL = "12.68.0"
PHP = "8.2.12"
PYTHON = "3.14.0"
FASTAPI = "0.136.1"
OPENCV = "5.0.0"
YOLO = "8.4.135"
NODE = "22.20.0"


def set_cell_shading(cell, color_hex):
    shading = OxmlElement("w:shd")
    shading.set(qn("w:fill"), color_hex)
    shading.set(qn("w:val"), "clear")
    cell._tc.get_or_add_tcPr().append(shading)


def add_page_number(run):
    fldChar1 = OxmlElement("w:fldChar")
    fldChar1.set(qn("w:fldCharType"), "begin")
    instrText = OxmlElement("w:instrText")
    instrText.set(qn("w:space"), "preserve")
    instrText.text = "PAGE"
    fldChar2 = OxmlElement("w:fldChar")
    fldChar2.set(qn("w:fldCharType"), "end")
    run._r.append(fldChar1)
    run._r.append(instrText)
    run._r.append(fldChar2)


def create(doc):
    style = doc.styles["Normal"]
    style.font.name = "Noto Sans Bengali"
    style.font.size = Pt(10)
    style.paragraph_format.space_after = Pt(6)
    style.paragraph_format.line_spacing = 1.15

    for i in [1, 2, 3]:
        h = doc.styles[f"Heading {i}"]
        h.font.name = "Noto Sans Bengali"
        h.font.color.rgb = RGBColor(0x0F, 0x17, 0x2A)
        h.font.bold = True
        if i == 1:
            h.font.size = Pt(16)
            h.paragraph_format.space_before = Pt(18)
            h.paragraph_format.space_after = Pt(6)
        elif i == 2:
            h.font.size = Pt(13)
            h.font.color.rgb = RGBColor(0x1D, 0x4E, 0xD8)
        else:
            h.font.size = Pt(11)
            h.font.color.rgb = RGBColor(0x33, 0x41, 0x55)

    section = doc.sections[0]
    section.top_margin = Inches(0.6)
    section.bottom_margin = Inches(0.6)
    section.left_margin = Inches(0.7)
    section.right_margin = Inches(0.7)
    section.header_distance = Inches(0.3)
    section.footer_distance = Inches(0.3)
    header = section.header
    hp = header.paragraphs[0]
    hp.alignment = WD_ALIGN_PARAGRAPH.CENTER
    r = hp.add_run(
        "AI Classroom Cheating Detection System — Complete Documentation  •  v1.1 (808073f)  •  Confidential — Research Prototype"
    )
    r.font.size = Pt(7)
    r.font.color.rgb = RGBColor(0x64, 0x74, 0x8B)
    r.font.name = "Inter"
    footer = section.footer
    fp = footer.paragraphs[0]
    fp.alignment = WD_ALIGN_PARAGRAPH.CENTER
    r = fp.add_run("Page ")
    r.font.size = Pt(8)
    r.font.color.rgb = RGBColor(0x64, 0x74, 0x8B)
    add_page_number(r)
    r = fp.add_run("  •  © 2026 Md Moklesar Rahman, Jahangirnagar University")
    r.font.size = Pt(7)
    r.font.color.rgb = RGBColor(0x94, 0xA3, 0xB8)

    # COVER
    p = doc.add_paragraph()
    p.alignment = WD_ALIGN_PARAGRAPH.CENTER
    p.paragraph_format.space_before = Pt(36)
    r = p.add_run("AI CLASSROOM CHEATING\nDETECTION SYSTEM")
    r.bold = True
    r.font.size = Pt(28)
    r.font.color.rgb = RGBColor(0x0F, 0x17, 0x2A)
    p = doc.add_paragraph()
    p.alignment = WD_ALIGN_PARAGRAPH.CENTER
    r = p.add_run(
        "Real-Time Exam Surveillance Using Computer Vision and Behavioral Analysis"
    )
    r.font.size = Pt(12)
    r.font.color.rgb = RGBColor(0x1D, 0x4E, 0xD8)
    r.italic = True
    p = doc.add_paragraph()
    p.alignment = WD_ALIGN_PARAGRAPH.CENTER
    p.paragraph_format.space_before = Pt(18)
    r = p.add_run(
        "Complete System, Operator, Administrator, Developer, Testing, Security, and Research Documentation"
    )
    r.font.size = Pt(9)
    r.font.color.rgb = RGBColor(0x47, 0x56, 0x72)
    r.bold = True

    # info box
    doc.add_paragraph()
    tbl = doc.add_table(rows=1, cols=1)
    tbl.alignment = WD_TABLE_ALIGNMENT.CENTER
    cell = tbl.cell(0, 0)
    set_cell_shading(cell, "F1F5F9")
    cell.vertical_alignment = WD_CELL_VERTICAL_ALIGNMENT.CENTER
    info = [
        ("Author:", "Md Moklesar Rahman"),
        ("Program:", "Master's in Computer Science and Engineering"),
        ("Institution:", "Jahangirnagar University"),
        ("Supervisor:", "Risala Tasin Khan, PhD"),
        (
            "Repository:",
            "https://github.com/Md-Moklesar-Rahman-Bappy/AI-Classroom-Cheating-Detection-System.git",
        ),
        ("Branch / Commit:", f"{BRANCH} / {COMMIT} ({FULL_COMMIT})"),
        ("Document version:", "v1.1 — 2026-09-07"),
        ("Prepared date:", DATE),
        ("Status:", "Research Prototype — Not Production-Ready (verified)"),
    ]
    for label, val in info:
        pp = cell.add_paragraph()
        pp.paragraph_format.space_after = Pt(2)
        rr = pp.add_run(label + " ")
        rr.bold = True
        rr.font.size = Pt(8)
        rr2 = pp.add_run(val)
        rr2.font.size = Pt(8)
    # remove initial empty paragraph if present
    if cell.paragraphs and not cell.paragraphs[0].text.strip():
        p_elem = cell.paragraphs[0]._element
        p_elem.getparent().remove(p_elem)

    # responsible notice
    p = doc.add_paragraph()
    p.paragraph_format.space_before = Pt(12)
    tbl2 = doc.add_table(rows=1, cols=1)
    cell2 = tbl2.cell(0, 0)
    set_cell_shading(cell2, "FFFBEB")
    # border
    tcPr = cell2._tc.get_or_add_tcPr()
    tcBorders = OxmlElement("w:tcBorders")
    for edge in ["top", "left", "bottom", "right"]:
        e = OxmlElement(f"w:{edge}")
        e.set(qn("w:val"), "single")
        e.set(qn("w:sz"), "4")
        e.set(qn("w:color"), "D97706")
        tcBorders.append(e)
    tcPr.append(tcBorders)
    pp = cell2.add_paragraph()
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
    if cell2.paragraphs and not cell2.paragraphs[0].text.strip():
        p_elem = cell2.paragraphs[0]._element
        p_elem.getparent().remove(p_elem)

    doc.add_page_break()

    # TOC
    h = doc.add_heading("Table of Contents", level=1)
    p = doc.add_paragraph()
    r = p.add_run(
        "Word → References → Table of Contents field inserted. Press Ctrl+A then F9 to update page numbers after opening."
    )
    r.italic = True
    r.font.size = Pt(7)
    r.font.color.rgb = RGBColor(0x64, 0x74, 0x8B)
    # insert TOC field
    p2 = doc.add_paragraph()
    fldChar1 = OxmlElement("w:fldChar")
    fldChar1.set(qn("w:fldCharType"), "begin")
    instr = OxmlElement("w:instrText")
    instr.set(qn("w:space"), "preserve")
    instr.text = ' TOC \\o "1-3" \\h \\z \\u '
    fldChar2 = OxmlElement("w:fldChar")
    fldChar2.set(qn("w:fldCharType"), "separate")
    fldChar3 = OxmlElement("w:fldChar")
    fldChar3.set(qn("w:fldCharType"), "end")
    p2._p.append(fldChar1)
    p2._p.append(instr)
    p2._p.append(fldChar2)
    p2._p.append(fldChar3)

    def add_heading(text, level):
        return doc.add_heading(text, level=level)

    def add_para(text, bold=False, size=10, color=None, italic=False, align=None):
        p = doc.add_paragraph()
        r = p.add_run(text)
        r.bold = bold
        r.font.size = Pt(size)
        if color:
            r.font.color.rgb = color
        r.italic = italic
        if align:
            p.alignment = align
        return p

    def add_bullet(text, level=0):
        p = doc.add_paragraph(style="List Bullet")
        p.paragraph_format.left_indent = Inches(0.2 + level * 0.15)
        r = p.add_run(text)
        r.font.size = Pt(9)
        return p

    def add_table(headers, rows, col_widths=None):
        tbl = doc.add_table(rows=1, cols=len(headers))
        tbl.style = "Light Grid Accent 1"
        tbl.alignment = WD_TABLE_ALIGNMENT.CENTER
        hdr = tbl.rows[0].cells
        for i, h in enumerate(headers):
            hdr[i].text = h
            for pp in hdr[i].paragraphs:
                for rr in pp.runs:
                    rr.bold = True
                    rr.font.size = Pt(8)
                    rr.font.color.rgb = RGBColor(0xFF, 0xFF, 0xFF)
            set_cell_shading(hdr[i], "1D4ED8")
        for row in rows:
            cells = tbl.add_row().cells
            for i, val in enumerate(row):
                cells[i].text = str(val)
                for pp in cells[i].paragraphs:
                    for rr in pp.runs:
                        rr.font.size = Pt(7.5)
                    pp.paragraph_format.space_after = Pt(1)
        if col_widths:
            for idx, w in enumerate(col_widths):
                for cell in tbl.columns[idx].cells:
                    cell.width = Inches(w)
        doc.add_paragraph().paragraph_format.space_after = Pt(2)
        return tbl

    def add_callout(title, text, color="2563EB", bg="EFF6FF"):
        tbl = doc.add_table(rows=1, cols=1)
        cell = tbl.cell(0, 0)
        set_cell_shading(cell, bg)
        tcPr = cell._tc.get_or_add_tcPr()
        tcBorders = OxmlElement("w:tcBorders")
        left = OxmlElement("w:left")
        left.set(qn("w:val"), "single")
        left.set(qn("w:sz"), "12")
        left.set(qn("w:color"), color)
        tcBorders.append(left)
        tcPr.append(tcBorders)
        pp = cell.add_paragraph()
        rr = pp.add_run(title + "  ")
        rr.bold = True
        rr.font.size = Pt(8)
        rr.font.color.rgb = RGBColor(
            int(color[0:2], 16), int(color[2:4], 16), int(color[4:6], 16)
        )
        rr = pp.add_run(text)
        rr.font.size = Pt(8)
        if cell.paragraphs and not cell.paragraphs[0].text.strip():
            p_elem = cell.paragraphs[0]._element
            p_elem.getparent().remove(p_elem)
        doc.add_paragraph().paragraph_format.space_after = Pt(2)

    # PART I
    add_heading("Part I — Project Understanding", level=1)
    add_heading("1. Executive Summary", level=2)
    add_para(
        "এই প্রকল্পটি পরীক্ষার হলের জন্য একটি AI-সহায়ক নজরদারি কাঠামো। এটি ভিডিও থেকে ব্যক্তি ও মোবাইল ফোন শনাক্ত, ট্র্যাকিং এবং আচরণ বিশ্লেষণের মাধ্যমে ১১ ধরনের পর্যবেক্ষণযোগ্য ইভেন্ট তৈরি করে এবং প্রতিটি ইভেন্টের জন্য এনোটেটেড প্রমাণ সংরক্ষণ করে মানব পর্যালোচনার জন্য উপস্থাপন করে। সিস্টেম স্বয়ংক্রিয়ভাবে প্রতারণার সিদ্ধান্ত নেয় না।",
        size=9,
    )
    add_heading("2. Project Background", level=2)
    add_para(
        "বড় পরীক্ষার হলে মানব পরিদর্শকের পক্ষে প্রতিটি শিক্ষার্থীকে একটানা পর্যবেক্ষণ করা কঠিন। রেকর্ডেড ফুটেজ পর্যালোচনা সময়সাপেক্ষ। এই প্রকল্প একটি লাইটওয়েট, রিসোর্স-সীমিত প্রতিষ্ঠানের জন্য উপযোগী হালকা স্থাপনা-যোগ্য ফ্রেমওয়ার্ক প্রস্তাব করে।",
        size=9,
    )
    add_heading("3. Problem Statement — 6. Objectives / 7. Research Questions", level=2)
    add_bullet("সমস্যা: সীমিত রিসোর্সে পরীক্ষার নজরদারি, ফুটেজ পর্যালোচনার জটিলতা।")
    add_bullet("লক্ষ্য: নির্ভরযোগ্য পর্যবেক্ষণযোগ্য ইভেন্ট সনাক্ত, প্রমাণ সহ মানব পর্যালোচনা।")
    add_bullet(
        "গবেষণা প্রশ্ন: কোন ভিজ্যুয়াল কিউ দিয়ে suspicious ইভেন্ট নির্ভরযোগ্যভাবে ধরা যায়? হালকা মডেলে কী সীমাবদ্ধতা থাকে?"
    )
    add_heading("8-12. Scope & Contributions", level=2)
    add_table(
        ["Included", "Excluded"],
        [
            [
                "Person/phone detection, tracking, 5 behavior rules, 11-event taxonomy, evidence, RBAC, recorded+live",
                "Facial recognition, emotion, intention inference, automatic disciplinary decision",
            ],
            [
                "Laravel dashboard, FastAPI service, MySQL, queue, audit",
                "Public participant data, production-scale camera hardware verification",
            ],
        ],
    )
    add_callout(
        "Intended Users:",
        "নতুন ব্যবহারকারী, অপারেটর, সিস্টেম অ্যাডমিন, ডেভেলপার, গবেষক, থিসিস সুপারভাইজার।",
        "0F172A",
        "F1F5F9",
    )
    add_heading("13-16. Capabilities & Responsible AI", level=2)
    add_para(
        "Can: ব্যক্তি/ফোন শনাক্ত, ট্র্যাক, হেড ওরিয়েন্টেশন, সিট ত্যাগ, প্রমাণ ক্যাপচার, রিভিউ ওয়ার্কফ্লো। Cannot: স্বয়ংক্রিয় অভিযোগ, মুখ চেনা, আবেগ অনুমান। প্রতিটি অ্যালার্টে মানব পর্যালোচনা বাধ্যতামূলক।",
        size=9,
    )
    add_bullet(
        "Known limitations: দূরে ছোট ফোন মিস, আলো/কোণে orientation অস্থির, ট্র্যাকিং Re-ID নেই — বিস্তারিত Part IV/XV।"
    )

    # PART II
    add_heading("Part II — Feature Catalog", level=1)
    features = [
        (
            "Authentication",
            "Breeze login/register/forgot/verify/confirm; password visibility eye toggle aria-pressed",
            "All",
            "—",
            "Implemented",
            "",
        ),
        (
            "Profile",
            "Update info/password, delete guard last system_admin",
            "Self",
            "users",
            "Implemented",
            "",
        ),
        (
            "Dashboard",
            "KPI rooms/sessions/jobs/events + health + trend + live placeholder",
            "All",
            "ProcessingMetric",
            "Implemented",
            "",
        ),
        (
            "Exam Rooms/Sessions",
            "CRUD softDelete+restore, validation",
            "system_admin, exam_admin",
            "exam_rooms/sessions",
            "Implemented",
            "",
        ),
        (
            "Camera Sources",
            "CRUD rtsp/webcam/test_source/video_file, credentials_encrypted",
            "admin/invigilator view",
            "camera_sources",
            "Implemented",
            "RTSP verified, EZVIZ unverified",
        ),
        (
            "Video Assets",
            "Upload mp4/mov, stored_filename checksum, player",
            "admin",
            "video_assets",
            "Implemented",
            ".dav needs conversion",
        ),
        (
            "Analysis Jobs",
            "Create queued→processing→completed/failed/cancelled, sync/cancel/retry, progress%",
            "admin/reviewer",
            "analysis_jobs+remote_job_id",
            "Implemented",
            "",
        ),
        (
            "Detection Events",
            "11 codes, filters event_type/category/track/review, bulkDelete",
            "All view, admin delete",
            "detection_events ENUM 11",
            "Implemented",
            "",
        ),
        (
            "Evidence",
            "Gallery 12/page, show full-res annotated, download, bulk",
            "All view, admin delete",
            "event_evidence",
            "Implemented",
            "1px vs 3px thumb mitigated",
        ),
        (
            "Human Reviews",
            "ReviewDecision pending→confirmed/dismissed/needs_review + audit",
            "reviewer/admin",
            "review_decisions",
            "Implemented",
            "",
        ),
        (
            "Model Versions",
            "CRUD semver is_active",
            "system_admin",
            "model_versions",
            "Implemented",
            "",
        ),
        (
            "Reports/Metrics/Audit Logs",
            "Report show/download (PDF), metrics throughput, audit filter",
            "All per role",
            "processing_metrics/audit_logs",
            "Implemented",
            "",
        ),
        (
            "Users/Roles",
            "Users CRUD, role dropdown, last-admin guard, Trash",
            "system_admin",
            "users/roles",
            "Implemented",
            "",
        ),
        (
            "Settings/Help/Live/Trash",
            "Static+live start/stop/health/events/preview + trash 7 types",
            "per role middleware",
            "—",
            "Implemented",
            "Live is Phase 2 minimal",
        ),
    ]
    add_table(
        ["Feature", "Main Actions / Notes", "Roles", "DB", "Status", "Limitation"],
        features,
    )

    # PART III
    add_heading("Part III — System Architecture", level=1)
    add_para("Textual diagram:", bold=True, size=9)
    add_para(
        "Video Source (video_file / webcam / rtsp / test_source) → Input Adapter → Frame Processing (OpenCV) → Object Detection (YOLO11n) → Pose/Head (geometric) → Tracking (centroid) → Behavior Analysis (rules) → Event Engine (11 codes) → Evidence Lifecycle (annotate+persist) → Dashboard (human review) → Audit Lifecycle",
        size=8,
    )
    add_heading("Laravel Dashboard", level=2)
    add_para(
        "Laravel 12.68, PHP 8.2, Blade+Bootstrap 5.3.3, Vite 7.3.6 (107kB js, 45kB css), Eloquent 14 models, 16 controllers, AiServiceClient Guzzle 8 endpoints, queue database, MySQL 10.4.32 ai_classroom.",
        size=9,
    )
    add_heading("FastAPI", level=2)
    add_para(
        "FastAPI 0.136.1, Uvicorn, Pydantic, OpenCV 5.0, Ultralytics 8.4.135 YOLO11n, 79 modules, 3 routers (jobs/live/health) + debug/analyze-local gated is_development().",
        size=9,
    )
    add_heading("MySQL/Queue/Workflows", level=2)
    add_table(
        ["Workflow", "Steps"],
        [
            [
                "Recorded",
                "Upload video_assets → POST /api/v1/jobs/recorded → DB job queued → queue:work ProcessAnalysisJob streams file → detection→tracking→rules→evidence→ update AnalysisJob completed → SyncAnalysisJob → Dashboard events/evidence",
            ],
            [
                "Live",
                "POST /api/v1/live/start (camera_source) → LiveSession → preview/health/events polling → DetectionEvent live → Dashboard live/*",
            ],
            [
                "Job State",
                "pending→queued→processing→completed/failed/cancelled ; retry creates new attempt, cancel via cancel endpoint",
            ],
            [
                "Event Lifecycle",
                "packet.frame_index + bbox + track_id → rule window 15 → event code → detection_events + EventEvidence save_snapshot → dashboard review_status",
            ],
            [
                "Evidence Lifecycle",
                "frame at event → EvidenceAnnotator highlight trigger colored + others gray → EventEvidence frame_number/captured_at_seconds/bbox/checksum → protected download via Policy → retention/softDelete",
            ],
        ],
    )
    add_callout(
        "Correlation:",
        "remote_job_id (AnalysisJob) ↔ ai-service job_id ↔ detection_events.analysis_job_id ↔ event_evidence.detection_event_id",
        "2563EB",
        "EFF6FF",
    )

    # PART IV
    add_heading("Part IV — Computer Vision and Event Engine", level=1)
    add_para(
        "Acquisition: OpenCV VideoCapture, 640×360, process_every_n=3, base confidence 0.25. YOLO11n COCO: class 0 person, 67 cell phone; phone threshold 0.40 + size≥30×30 area≥1200 aspect 0.35-2.20 after FINAL_ACCURACY_REPORT. Centroid tracker max_distance 90, max_missing 15 (tuned from 80/10) to reduce S3 40% and B4 60%; no Re-ID. Orientation geometric thresholds left -0.15 / right 0.15 / backward_aspect 1.8. Temporal window 15, min_supporting 8, max_missing 4, min_duration 10, cooldown 45, leaving_absence 45.",
        size=8,
    )
    events_rows = [
        [
            "D1",
            "detection",
            "Person Detected",
            "YOLO 0 ≥0.25",
            "person bbox",
            "Evidence jpg bbox+timestamp+track",
            "green 0,200,0",
            "Implemented",
            "test_detector pass",
            "Small distant may miss",
            "AI observation only",
        ],
        [
            "D2",
            "detection",
            "Mobile Phone Detected",
            "YOLO 67 ≥0.40 + size/aspect + associate <300px",
            "phone+person bbox",
            "blue person+phone mini-box stacked",
            "blue 255,0,0",
            "Implemented",
            "FP 55-70% reduced",
            "Rectilinear paper false",
            "Human review required",
        ],
        [
            "D3",
            "detection",
            "Multiple Persons",
            "≥2 persons cooldown 30",
            "union bbox",
            "yellow union box",
            "yellow",
            "Implemented",
            "pass",
            "Occlusion",
            "Review",
        ],
        [
            "B1",
            "behavior",
            "Repeated Looking Left",
            "left≥8/15 ratio 0.5 cooldown 45",
            "observation_count",
            "orange highlight trigger",
            "orange",
            "Implemented",
            "pass",
            "Lighting/angle variance",
            "Review",
        ],
        [
            "B2",
            "behavior",
            "Repeated Looking Right",
            "mirrored",
            "same",
            "orange",
            "orange",
            "Implemented",
            "pass",
            "same",
            "Review",
        ],
        [
            "B3",
            "behavior",
            "Looking Backward",
            "backward≥4 ratio 0.3",
            "same",
            "orange",
            "orange",
            "Implemented",
            "pass",
            "same",
            "Review",
        ],
        [
            "B4",
            "behavior",
            "Possible Seat Departure",
            "absence≥45 + stale last_known_bbox",
            "last_known red label",
            "red 0,0,255",
            "red",
            "Implemented",
            "B4 false 60% down",
            "Stale bbox",
            "Review",
        ],
        [
            "B5",
            "behavior",
            "Excessive Head Movement",
            "switches≥4 covers LR",
            "switch count",
            "orange",
            "orange",
            "Implemented",
            "pass",
            "same",
            "Review",
        ],
        [
            "S1",
            "system",
            "Normal",
            "no active events",
            "—",
            "none",
            "green",
            "Implemented",
            "pass",
            "—",
            "—",
        ],
        [
            "S2",
            "system",
            "Insufficient Evidence",
            "missing≤4 but <8",
            "observation window",
            "gray badge",
            "gray 180",
            "Implemented",
            "pass",
            "Threshold conservative",
            "—",
        ],
        [
            "S3",
            "system",
            "Tracking Lost",
            "15≤absence<45",
            "stale gray",
            "gray 128",
            "gray",
            "Implemented",
            "40% down",
            "Centroid Re-ID none",
            "Review",
        ],
    ]
    add_table(
        [
            "Code",
            "Category",
            "Label",
            "Trigger",
            "Metadata",
            "Evidence",
            "Color",
            "Status",
            "Validation",
            "Limitation",
            "Review",
        ],
        events_rows,
    )
    add_callout(
        "Warning:",
        "কোনো ইভেন্ট প্রতারণার প্রমাণ নয় — প্রতিটি অ্যালার্ট মানব পর্যালোচনা সাপেক্ষ। Duplicate suppression cooldown + min_duration prevents repeat alerts.",
        "D97706",
        "FFFBEB",
    )

    # PART V
    add_heading("Part V — Evidence and Review", level=1)
    steps = [
        "Event fired at packet.frame_index meeting rule",
        "EvidenceManager.save_snapshot(frame_proc, job_id, event_id, frame_index, timestamp, event_obj, tracks, detections)",
        "EvidenceAnnotator.annotate: trigger 3px colored + white border + 3-line label (Track #X, Code+Name, Frame N t=s), others gray 1px",
        "Persist EventEvidence: frame_number, captured_at_seconds, bbox dict, checksum_sha256, width/height",
        "D2 associates nearest track <300px, draws phone mini-box; S3/B4 uses last_known_bbox red/gray label Possible Departure",
        "Dashboard shows Machine Observation / Evidence / Human Decision triptych + Explanation panel",
        "Reviewer selects confirmed_suspicious / dismissed_normal / needs_further_review + notes → ReviewDecision + AuditLog",
        "Protected download Original/JPG/PNG/JSON via EvidenceController@download with authorizeAccess, served outside public/",
        "Delete/bulkDelete/Restore via softDelete + retention, audit forgery prevented by checksum",
    ]
    for i, s in enumerate(steps, 1):
        add_para(f"{i}. {s}", size=8)
    add_heading("Reviewer Checklist", level=2)
    for c in [
        "Check AI Notice, verify frame/bbox/track match evidence jpg",
        "Check confidence / observation_count / rule thresholds",
        "Check D2 phone association distance, B4 stale bbox, S3 absence",
        "Confirm/dismiss/needs_review with notes — never auto-accuse",
        "Audit trail saved",
    ]:
        add_bullet(c)

    # PART VI
    add_heading("Part VI — Database and Data Model", level=1)
    add_para(
        "Verified tables (15) via 11 migrations (safe additive, hasColumn guards) + 1 seeder (RolePermissionSeeder idempotent syncWithoutDetaching).",
        size=9,
    )
    db_rows = [
        [
            "users",
            "id, name, email unique, email_verified_at, password, remember_token, deleted_at, timestamps",
            "roles M2M, exam_sessions",
            "role_user FK cascade",
            "email index",
            "softDelete",
            "—",
        ],
        [
            "roles / role_user",
            "roles id name unique description, role_user user_id role_id",
            "users M2M, permissions M2M",
            "FK cascade",
            "unique name",
            "—",
            "5 roles",
        ],
        [
            "exam_rooms",
            "id, name, capacity, location, deleted_at",
            "exam_sessions",
            "—",
            "name index",
            "softDelete",
            "—",
        ],
        [
            "exam_sessions",
            "id, name, exam_room_id FK, created_by, status, deleted_at",
            "room belongsTo, videoAssets hasMany",
            "FK nullOnDelete",
            "—",
            "softDelete",
            "—",
        ],
        [
            "camera_sources",
            "id, name, type enum webcam/rtsp/test_source/video_file, url, credentials_encrypted hidden, deleted_at",
            "—",
            "—",
            "type index",
            "softDelete",
            "RTSP creds encrypted",
        ],
        [
            "video_assets",
            "id, exam_session_id FK, original_name, stored_filename unique, mime, size, checksum unique, deleted_at",
            "session, analysisJobs",
            "FK cascade",
            "checksum unique",
            "softDelete",
            "migrate:fresh risk",
        ],
        [
            "analysis_jobs",
            "id, exam_session_id FK, video_asset_id FK, model_version_id FK, status enum, progress_percent, remote_job_id, source_type, width/height/process_every_n/conf, deleted_at",
            "session/video/model/events",
            "FK cascade",
            "status index",
            "softDelete",
            "remote correlation",
        ],
        [
            "model_versions",
            "id, name, version semver, is_active, deleted_at",
            "analysisJobs",
            "—",
            "version unique",
            "—(no restore)",
            "—",
        ],
        [
            "detection_events",
            "id, analysis_job_id FK, event_type ENUM 11 D1-D3/B1-B5/S1-S3, temporary_track_id, started_at_frame/ended_at_frame, started_at_seconds, confidence/rule_score, review_status, deleted_at",
            "job, evidences, reviewDecision",
            "FK cascade",
            "event_type index",
            "softDelete",
            "ENUM 11 after v2",
        ],
        [
            "event_evidence",
            "id, detection_event_id FK, analysis_job_id FK, file_path, file_type, frame_number, captured_at_seconds, bbox json, checksum_sha256, width/height",
            "event, job",
            "FK cascade",
            "checksum index",
            "softDelete via job",
            "frame at event",
        ],
        [
            "review_decisions",
            "id, detection_event_id FK, reviewer_id FK, decision enum, notes, timestamps",
            "event, reviewer",
            "FK cascade",
            "—",
            "—",
            "human decision",
        ],
        [
            "processing_metrics",
            "id, analysis_job_id FK, processing_fps, detection_latency_ms, cpu_percent, memory_mb",
            "job",
            "FK cascade",
            "—",
            "—",
            "via benchmark.py",
        ],
        [
            "audit_logs",
            "id, user_id FK, action, auditable_type/id, ip, timestamps",
            "user",
            "FK null",
            "action index",
            "—",
            "audit trail",
        ],
        [
            "sessions/jobs/cache",
            "sessions id payload, jobs id queue, cache table",
            "—",
            "—",
            "—",
            "—",
            "php artisan session:table may be needed",
        ],
    ]
    add_table(
        [
            "Table",
            "Important Columns",
            "Relationships",
            "Foreign Keys",
            "Indexes",
            "Soft Delete",
            "Notes",
        ],
        db_rows,
    )
    add_para(
        "MySQL ai_classroom 10.4.32. DB_CONNECTION=mysql default (phpunit sqlite :memory: only). php artisan migrate preserves data when migrations safe (additive, hasColumn guards). migrate:fresh drops all tables — must not run on valuable data. Existing migration history should not be rewritten; new changes require new non-destructive migration via make:migration. Backup: mysqldump ai_classroom > backup.sql; Restore: mysql ai_classroom < backup.sql .",
        size=8,
    )
    add_callout(
        "Migration Safety:",
        "New schema changes require new migration, never edit old history casually. Tested via hasColumn guards and migrate --force additive.",
        "0F766E",
        "CCFBF1",
    )

    # PART VII
    add_heading("Part VII — Installation and Configuration", level=1)
    add_heading("Windows 10/11 + XAMPP", level=2)
    add_para(
        "Prerequisites: Windows 10/11, XAMPP 8.2 (PHP 8.2, MySQL), Git, Node 22, Python 3.14.",
        size=9,
    )
    cmds = [
        [
            "Clone",
            "git clone https://github.com/Md-Moklesar-Rahman-Bappy/AI-Classroom-Cheating-Detection-System.git  &&  cd ai_classroom_cheat_detection",
        ],
        [
            "Laravel",
            "cd dashboard && composer install  &&  copy .env.example .env  &&  php artisan key:generate",
        ],
        [
            "Python",
            "cd ..\\ai-service && python -m venv venv && venv\\Scripts\\activate && pip install -r requirements.txt && pip install -r requirements-dev.txt",
        ],
        ["Frontend", "cd ..\\dashboard && npm install && npm run build"],
        [
            "DB create",
            "Open phpMyAdmin http://localhost/phpmyadmin → create database ai_classroom (utf8mb4)",
        ],
        [
            "Env",
            "Edit .env: DB_CONNECTION=mysql DB_DATABASE=ai_classroom DB_USERNAME=root DB_PASSWORD=  (XAMPP default empty) ; AI_SERVICE_URL=http://127.0.0.1:8001 ; QUEUE_CONNECTION=database",
        ],
        [
            "Migrate+Seed",
            "php artisan migrate --force && php artisan db:seed --class=RolePermissionSeeder",
        ],
        [
            "Model",
            "Place yolo11n.pt in project root and ai-service/ (5.6 MB each, checksum verify); .gitignore covers *.pt",
        ],
        [
            "Storage link (if needed)",
            "php artisan storage:link   (only if public disk required)",
        ],
        [
            "Queue table",
            "php artisan queue:table && php artisan migrate (if jobs table missing)",
        ],
    ]
    # need to escape .. for doc
    add_table(["Step", "Command"], cmds)
    add_callout(
        "Do NOT include secrets:",
        "Real APP_KEY / DB password / camera password must not be pasted into documentation. Use .env.example placeholders.",
        "DC2626",
        "FEE2E2",
    )

    # PART VIII
    add_heading("Part VIII — Startup and Shutdown", level=1)
    add_table(
        ["Terminal", "Working Dir", "Command"],
        [
            [
                "1 — FastAPI",
                "ai-service",
                "venv\\Scripts\\activate  &&  uvicorn app.main:app --reload --port 8001",
            ],
            ["2 — Laravel", "dashboard", "php artisan serve --port=8000"],
            ["3 — Queue", "dashboard", "php artisan queue:work"],
        ],
    )
    add_heading("Troubleshooting (excerpt)", level=2)
    add_table(
        ["Symptom", "Cause", "Resolution", "Log"],
        [
            [
                "artisan command not found",
                "Wrong dir",
                "cd dashboard then php artisan",
                "—",
            ],
            [
                "Could not open input file: artisan",
                "Ran from root without cd",
                "cd dashboard",
                "—",
            ],
            [
                "ModuleNotFoundError: app",
                "Not in ai-service",
                "cd ai-service then uvicorn app.main:app",
                "terminal",
            ],
            [
                "Job pending forever",
                "queue:work not running",
                "Run terminal 3 queue:work, check jobs table",
                "storage/logs/laravel.log",
            ],
            [
                "AI service unavailable",
                "8001 not listening",
                "Start terminal 1, check AI_SERVICE_URL",
                "ai-service logs",
            ],
            [
                "Port already in use",
                "8000/8001 occupied",
                "netstat -ano | findstr :8000 then taskkill /PID <pid> /F",
                "—",
            ],
            [
                "MySQL connection error",
                "DB not created / .env wrong",
                "Create ai_classroom, check .env, php artisan migrate",
                "laravel.log",
            ],
            [
                "Missing sessions table",
                "sessions migration not run",
                "php artisan session:table && migrate (if using database sessions)",
                "—",
            ],
        ],
    )
    add_para(
        "Safe shutdown: Ctrl+C in each terminal in order queue→laravel→fastapi; close venv deactivate.",
        size=8,
    )

    # PART IX
    add_heading("Part IX — Complete Operator Manual", level=1)
    workflows = [
        (
            "Signing in",
            "All",
            "Visit http://localhost:8000/login → email/password (demo admin@example.com seeded) → Dashboard",
            "Dashboard KPIs visible",
            "Wrong password → reset via forgot-password, check mail log",
        ),
        (
            "Exam Room",
            "system_admin/exam_admin",
            "Sidebar Rooms → Create → name/capacity/location → Save",
            "Room appears in index + sessions dropdown",
            "Validation error → fix required *",
        ),
        (
            "Camera Source",
            "admin/invigilator view",
            "Sidebar Cameras → Create → name rtsp/webcam/test_source/video_file + url + port + credentials (encrypted) → Save",
            "Health badge Online/Standby",
            "RTSP creds wrong → health unavailable, re-enter from device label",
        ),
        (
            "Video Asset",
            "admin",
            "Sidebar Video Assets → Upload → choose mp4/mov ≤500MB → select session → Save",
            "Stored_filename checksum unique, player on show",
            ".dav → convert to mp4 H.264 via approved local workflow before upload",
        ),
        (
            "Model Version",
            "system_admin",
            "Sidebar Model Versions → Create → name version semver is_active → Save",
            "Model appears in job creation dropdown",
            "Version duplicate → unique error",
        ),
        (
            "Analysis Job",
            "admin/reviewer",
            "Sidebar Analysis Jobs → New Job → select session/video/model + width 640 height 360 process_every_n 3 confidence 0.25 → Create → php artisan queue:work processes",
            "Status queued→processing→completed + progress%",
            "No assets → empty-state link to upload first",
        ),
        (
            "Events/Evidence Review",
            "All per role",
            "Events → Filter event_type D2/B4 etc + track/review → Detail → triptych (Machine/Evidence/Human) → Evidence → View full-res annotated → Download JPG/JSON → Review form select confirmed/dismissed/needs_review + notes → Submit",
            "Review status updated + audit logged",
            "No evidence → incident-only note, check job completed",
        ),
        (
            "Reports/Metrics/Audit",
            "All",
            "Reports show/download PDF, Metrics throughput chart, Audit Logs filter",
            "Data never invented, empty states show",
            "—",
        ),
    ]
    for title, role, steps, expected, err in workflows:
        add_heading(title, level=2)
        add_para(f"Role: {role}", size=8, bold=True)
        add_para(f"Steps: {steps}", size=8)
        add_para(f"Expected: {expected}", size=8)
        add_para(f"Common errors & recovery: {err}", size=8)

    # PART X
    add_heading("Part X — Camera and Video Format Guide", level=1)
    add_table(
        ["Type", "Identifier", "Credentials", "Session", "Validation", "Status"],
        [
            [
                "webcam",
                "webcam (device 0)",
                "Should not require (leave empty)",
                "Optional exam_session",
                "device index integer",
                "Supported",
            ],
            [
                "rtsp",
                "rtsp://user:pass@ip:port/stream",
                "Must match device (encrypted)",
                "Optional",
                "url active_url",
                "Supported — health polling via LiveSession",
            ],
            [
                "test_source",
                "test_source (synthetic)",
                "Should not require",
                "Optional",
                "—",
                "Supported (dev)",
            ],
            [
                "video_file",
                "video_file path via VideoAsset",
                "Should not require",
                "Required exam_session",
                "mime mp4/mov",
                "Supported",
            ],
        ],
    )
    add_para(
        "RTSP: credentials are not arbitrary when real RTSP is used — must match camera. Webcam/test/video_file normally should not require credentials; encrypted via Crypt::encryptString, never shown in view/API (hidden attribute). EZVIZ CP1 Lite: unverified unless current source/runtime evidence proves otherwise — documented as unverified.",
        size=8,
    )
    add_heading("Video Extensions / MIME", level=2)
    add_para(
        "Verified from VideoAssetController validation: accepted extensions mp4, mov, avi, mkv (?) — actual controller allows mp4,mov,mp4,mov via mimes:video/* ; MIME via finfo; max 500MB. .dav is proprietary CCTV container — not claimed native; verified approach: convert to MP4 H.264 using approved local workflow (e.g., device tool or ffmpeg with local policy) before upload; do not claim ffmpeg command if repo differs.",
        size=8,
    )

    # PART XI
    add_heading("Part XI — Administrator Manual", level=1)
    add_table(
        ["Role", "Access"],
        [
            ["system_admin", "Full: users/roles, all evidence, audit, settings, trash"],
            ["exam_admin", "Sessions/cameras/videos/jobs/evidence/reports"],
            ["invigilator", "Monitor exams, view events/evidence, live start/stop"],
            ["reviewer", "Review events/evidence, submit decisions"],
            ["auditor", "Read-only events/evidence/audit/reports"],
        ],
    )
    add_para(
        "Demo accounts (from RolePermissionSeeder): admin@example.com / exam_admin@example.com / invigilator@example.com / reviewer@example.com / auditor@example.com — all password `password` for local demo/testing only, must be changed before any real deployment. Last system_admin protection: delete/update role sync blocks when only one system_admin remains (UserController + ProfileController). Model admin: is_active toggle, deletion guards when ModelVersion has AnalysisJobs. Camera deletion guards: canDelete checks active jobs. Storage/queue/cache: php artisan queue:clear, cache:clear, config:clear, storage/logs retention. Backup/restore via mysqldump as in Part VI. Retention: audit_logs retained, evidence softDelete then retention per policy. Production checklist: APP_ENV=production, APP_DEBUG=false, QUEUE_CONNECTION=redis/database, AI_SERVICE_URL https, DB strong password, APP_KEY from key:generate, vite build present, yolo weights present, no .env in repo.",
        size=8,
    )

    # PART XII
    add_heading("Part XII — Developer and Maintainer Guide", level=1)
    add_para(
        "Organization: dashboard/app/Http/Controllers 16, Models 14, Policies 2, Middleware RoleMiddleware, Services AiServiceClient, Jobs 2, Blade 81, ai-service/app 79 py (api/detection/tracking/orientation/behaviors/events/evidence/jobs/live).",
        size=8,
    )
    dev_rows = [
        [
            "Add detector output",
            "Extend yolo_detector.py COCO_NAMES + app detection loop; update taxonomy.py if new code needed",
        ],
        [
            "Add event type",
            "Add entry in TAXONOMY dict, add rule in behaviors/rules.py, update migration ENUM via new migration, update Dashboard analysis-jobs view badge mapping",
        ],
        [
            "Update taxonomy",
            "Edit TAXONOMY + migration add_v2_event_taxonomy pattern; document in EVENT_TAXONOMY_V2.md appendix",
        ],
        [
            "Add temporal rule",
            "Edit behaviors/config.py window/min_supporting/cooldown; persist in output_metadata.behavior_config",
        ],
        [
            "Add camera adapter",
            "Add type to CameraSource validation + LiveSession handler in live/session.py",
        ],
        [
            "Add API endpoint",
            "Add router in ai-service/app/api/*.py + include in main.py; add client method in AiServiceClient.php",
        ],
        [
            "Add Laravel module",
            "Controller+Model+Migration+Policy+Route+View + seed if needed; add nav link in bootstrap layout sidebar",
        ],
        [
            "Adding tests",
            "dashboard/tests Feature via Pest, ai-service/tests via pytest; run php artisan test, python -m pytest",
        ],
        [
            "Secret handling",
            "Never log credentials, use Crypt for camera, hidden attributes, audit redaction",
        ],
        [
            "Git workflow",
            "Feature branch, PR, no force push, no history rewrite, no migrate:fresh on prod",
        ],
    ]
    add_table(["Task", "Steps"], dev_rows)

    # PART XIII
    add_heading("Part XIII — Testing, CI and Quality Assurance", level=1)
    add_para(
        f"Executed now: Laravel 171 passed (476 assertions) 42.08s (Dashboard). FastAPI key tests 24 passed (taxonomy 13 + evidence 11). Full FastAPI 128/131 (3 pre-existing fails: test_evidence_failure, test_duplicate_suppression_and_cooldown, test_phone_detections — assert 0 vs 1 via FakeDetector — confirmed via git stash same failures, not introduced). Frontend build Vite 7.3.6 built (107kB js, 45kB css). Ruff/Black/Pint not run here but configured; CI .github workflows if present (none found). Dependabot configured.",
        size=8,
    )
    add_callout(
        "Unit vs Benchmark vs Real-world:",
        "Unit-test success ≠ synthetic benchmark success ≠ real-world accuracy validation. Real-world requires approved participant data + hardware; only synthetic/non-identifiable evaluation exists here.",
        "D97706",
        "FFFBEB",
    )

    # PART XIV
    add_heading("Part XIV — Security, Privacy, Ethics and Licensing", level=1)
    sec = [
        [
            "Authentication",
            "Breeze + verified + password toggle aria-pressed, last-admin guard",
        ],
        [
            "Authorization",
            "RoleMiddleware 10 groups, Policies VideoAsset/AnalysisJob, evidence authorizeAccess",
        ],
        [
            "Upload validation",
            "mimes/size, checksum unique, not trusting original name",
        ],
        [
            "Private storage",
            "evidence outside public/, served via controller with policy + audit",
        ],
        ["Credentials encryption", "Crypt::encryptString, hidden, never in view/API"],
        ["Audit logging", "AuditHelper::log on video/user/review, retained filterable"],
        ["Soft delete/restore", "7 types, policy-gated restore, trash view"],
        [
            "Human review",
            "Every event requires confirm/dismiss/needs_review; responsible AI notice every page",
        ],
        [
            "Consent/Retention",
            "research/CONSENT_TEMPLATE.md, DATA_RETENTION_POLICY.md; minimization",
        ],
        ["No facial rec", "Only person bbox class 0, no face landmark"],
        ["AGPL-3.0", "YOLO AGPL via THIRD_PARTY_NOTICES.md, no legal advice"],
    ]
    add_table(["Control", "Verified"], sec)
    add_para(
        "Threat summary: privilege escalation blocked via middleware+policy; upload bypass via mime; credential leak via hidden; evidence leak via authorizeAccess. Limitations: no hardware-backed vault, no 2FA.",
        size=8,
    )

    # PART XV
    add_heading("Part XV — Research and Evaluation", level=1)
    add_para(
        "Dataset governance: research 8 md (DATASET_CARD, SPLIT_POLICY etc.) — synthetic/non-identifiable only; staged classroom recordings not included; consent required before real data. Manifests: research/manifests/MANIFEST.json. Evaluation: scripts/benchmark.py computes Precision/Recall/F1/mAP/FPS/latency/CPU/mem. Hardware: benchmark_report historical i5-14500 8GB no GPU CPU-only. Reproducibility: BENCHMARK_REPRODUCTION.md + REPRODUCIBILITY.md with seed + config_version v1 geometric-v1 thresholds window15 min_supporting8. False positives: D2 rectilinear paper 55-70% reduced via phone threshold 0.40 + size/aspect (FINAL_ACCURACY_REPORT); S3 40% via max_missing 15; B4 60% via leaving 45. Orientation variance across angle/lighting remains. No fabricated numbers — all from archived reports merged into BENCHMARK_REPORT/FINAL_QA.",
        size=8,
    )

    # PART XVI
    add_heading("Part XVI — Troubleshooting", level=1)
    add_table(
        ["Symptom", "Likely Cause", "Diagnostic", "Resolution", "Log"],
        [
            [
                "Dashboard 500",
                "Missing key/.env",
                "php artisan config:clear",
                "key:generate, migrate",
                "storage/logs/laravel.log",
            ],
            [
                "Queue job pending",
                "queue:work not running",
                "php artisan queue:failed",
                "run queue:work, retry",
                "jobs table",
            ],
            [
                "AI 503 unavailable",
                "FastAPI not on 8001",
                "curl http://127.0.0.1:8001/api/v1/health",
                "start uvicorn",
                "ai-service uvicorn log",
            ],
            [
                "YOLO weights missing",
                "yolo11n.pt not found",
                "ls -lh yolo11n.pt",
                "place weight + verify checksum",
                "—",
            ],
            [
                "Evidence not generated",
                "Job failed or confidence low",
                "php artisan tinker DetectionEvent::count()",
                "retry job, check metrics",
                "evidence table",
            ],
            [
                "Permission denied",
                "Role not assigned",
                "php artisan tinker User::find(1)->roles",
                "seed roles or assign via Users UI",
                "—",
            ],
            [
                "RTSP health red",
                "URL/creds wrong or port blocked",
                "Live health endpoint",
                "re-enter from device label, open firewall",
                "LiveSession log",
            ],
            [
                "Video format unsupported",
                ".dav uploaded",
                "Check VideoAsset validation",
                "Convert to mp4 H.264 locally then re-upload",
                "—",
            ],
            [
                "Frontend not built",
                "npm run build missing",
                "ls dashboard/public/build",
                "npm run build",
                "vite log",
            ],
            [
                "Windows path spaces",
                "Spaces in project path",
                "pwd",
                "Move to C:\\xampp\\htdocs without spaces",
                "—",
            ],
        ],
    )

    # PART XVII
    add_heading("Part XVII — Project Status and Roadmap", level=1)
    add_table(
        ["Area", "Status", "Evidence"],
        [
            [
                "Auth/Profile/RBAC/SoftDelete",
                "Implemented and tested",
                "171 tests, RoleAssignment 12/12",
            ],
            [
                "Exam rooms/sessions/cameras/video/assets/jobs/events/evidence/review/reports/metrics/audit",
                "Implemented and tested",
                "RecordedWorkflow 16/16, EventEvidence 5/5",
            ],
            [
                "Recorded video analysis",
                "Implemented and tested",
                "queue:work + AiServiceClient + EvidenceManager",
            ],
            [
                "Live monitoring start/stop/health/preview",
                "Implemented but partially verified",
                "LiveMode minimal, not full hardware test",
            ],
            [
                "YOLO phone thresholds + tracking tuned",
                "Implemented",
                "FINAL_ACCURACY_REPORT appendices",
            ],
            ["Phone D2/B4/S3 FP mitigation", "Implemented", "Benchmark report"],
            [
                "RTSP cam compatibility",
                "Unverified",
                "No EZVIZ hardware test — documented unverified",
            ],
            [
                "DAV native support",
                "Not production-ready (needs conversion)",
                "Requires conversion workflow",
            ],
            [
                "Real-world accuracy validation",
                "Blocked — needs approved participant data + hardware",
                "Synthetic only",
            ],
            [
                "Production infra (https, vault, 2FA)",
                "Not production-ready",
                "Demo password, local queue",
            ],
        ],
    )
    add_para(
        "Thesis readiness: PASS — human review, responsible AI, audit, thesis-consistent. Research readiness: PASS synthetic. Production readiness: NOT READY — needs hardware, participant data, infra, password rotation, queue supervisor.",
        size=8,
    )
    add_heading("Future Work", level=2)
    for f in [
        "Approved participant data study + IRB",
        "Hardware RTSP/EZVIZ verification with logs",
        "Re-ID tracker + orientation ML model",
        "2FA + secrets vault + https",
        "DAV H.264 transcoding pipeline if policy approves",
    ]:
        add_bullet(f)

    # PART XVIII Appendices
    add_heading("Part XVIII — Appendices", level=1)
    add_heading("A. Environment Variable Reference", level=2)
    add_table(
        ["Variable", "Example", "Purpose"],
        [
            [
                "APP_KEY",
                "base64:... (from key:generate)",
                "Encryption key — never commit",
            ],
            ["DB_CONNECTION", "mysql", "Must be mysql (not sqlite) for persistence"],
            ["DB_DATABASE", "ai_classroom", "MySQL database name"],
            ["DB_USERNAME", "root (XAMPP default)", "DB user"],
            ["AI_SERVICE_URL", "http://127.0.0.1:8001", "FastAPI base"],
            ["QUEUE_CONNECTION", "database", "Queue driver"],
            ["APP_ENV", "local/production", "Env"],
        ],
    )
    add_heading("B. Role Matrix", level=2)
    add_table(
        ["Role", "Key Access"],
        [
            ["system_admin", "Full + users/roles/audit/settings/trash"],
            ["exam_admin", "Sessions/cameras/videos/jobs/evidence/reports"],
            ["invigilator", "Monitor, live start/stop, view evidence"],
            ["reviewer", "Review events/evidence"],
            ["auditor", "Read-only audit/reports"],
        ],
    )
    add_heading("C. Event Taxonomy Matrix (11)", level=2)
    add_table(["Code", "Label", "Color"], [[r[0], r[2], r[6]] for r in events_rows])
    add_heading("D. Job-State Matrix", level=2)
    add_table(
        ["State", "Meaning", "Actions"],
        [
            ["pending", "Created not queued", "—"],
            ["queued", "In jobs table", "cancel, sync"],
            ["processing", "Worker running (detection)", "cancel, health"],
            ["completed", "Evidence+events persisted", "report/download, retry"],
            ["failed", "Exception", "retry, sync"],
            ["cancelled", "User cancelled", "retry"],
        ],
    )
    add_heading("E-F. API & DB Summary", level=2)
    add_para(
        "API summary: GET /api/v1/health, POST /api/v1/jobs/recorded, GET /jobs/{id}/events|metrics, POST live/start|stop etc. — 8 endpoints via AiServiceClient. DB 15 tables as in Part VI.",
        size=8,
    )
    add_heading("Glossary excerpts", level=2)
    add_para(
        "AnalysisJob: queued video analysis unit with remote_job_id correlation. DetectionEvent: one of 11 codes with bbox/track/frame. EventEvidence: annotated jpg+metadata with checksum. Responsible AI: human-review-required notice. SoftDelete: recoverable deletion via trash.",
        size=8,
    )

    # Save
    out = r"C:\xampp\htdocs\ai_classroom_cheat_detection\AI_Classroom_Cheating_Detection_System_Complete_Documentation.docx"
    try:
        doc.save(out)
        print(
            f"Saved to {out}, sections {len(doc.sections)}, paragraphs {len(doc.paragraphs)}"
        )
    except Exception as e:
        alt = os.path.join(
            os.getcwd(),
            "AI_Classroom_Cheating_Detection_System_Complete_Documentation.docx",
        )
        doc.save(alt)
        print(f"Saved to alt {alt}: {e}")


if __name__ == "__main__":
    doc = Document()
    create(doc)

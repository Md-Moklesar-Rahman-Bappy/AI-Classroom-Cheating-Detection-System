# AI Classroom Cheating Detection System
## Real-Time Exam Surveillance Using Computer Vision and Behavioral Analysis

> AI-assisted examination surveillance system designed to detect predefined suspicious examination events using computer vision, object detection, tracking, and behavioral analysis.

---

## Project Information

**Project Title**

AI Classroom Cheating Detection System | Real-Time Exam Surveillance

**Author**

Md Moklesar Rahman

**Program**

Master of Science (MSc) in IT

**Institution**

Jahangirnagar University (JU)

**Supervisor**

Risala Tasin Khan, PhD

**Email**

md.moklasarrahmanbappy@gmail.com

---

## Research Statement

This system is designed to assist authorized human invigilators by detecting predefined suspicious examination events.

⚠️ The system does **NOT** automatically determine that a student cheated.

AI-generated alerts indicate observable events that require human review.

Final decisions remain under authorized human supervision.

---

# Overview

Traditional examination monitoring depends heavily on human invigilators.

In large examination halls it becomes difficult to continuously observe every student, identify suspicious activities, and review long hours of surveillance footage.

This project proposes an AI-assisted surveillance framework capable of:

- Detecting students
- Detecting mobile phones
- Tracking movement
- Monitoring head orientation
- Flagging suspicious examination events
- Generating evidence logs
- Supporting live and recorded video analysis

The system combines:

- Computer Vision
- Deep Learning
- Multi-Object Tracking
- Behavioral Analysis
- Real-Time Monitoring
- Human Review Workflow

---

# Key Features

## Offline Recorded Video Analysis

✅ Upload recorded examination videos

✅ Process downloaded camera footage

✅ Generate annotated output video

✅ Create suspicious event timeline

✅ Save evidence snapshots

✅ Review events manually

✅ Export reports

---

## Live Camera Surveillance

✅ Real-time monitoring

✅ IP Camera support

✅ Webcam support

✅ Camera health monitoring

✅ Live dashboard

✅ Bounding boxes

✅ Instant alerts

✅ Human review workflow

---

## Computer Vision Features

✅ Person Detection

✅ Mobile Phone Detection

✅ Head Orientation Analysis

✅ Student Tracking

✅ Seat Leaving Detection

✅ Suspicious Event Detection

✅ Evidence Capture

✅ Event Logging

---

## Security Features

✅ Authentication

✅ Role-Based Access Control (RBAC)

✅ Protected Evidence Storage

✅ Audit Logging

✅ Secure APIs

✅ Configurable Retention Policy

---

# Operating Modes

---

## Mode 1: Recorded Video Analysis

### Workflow

```text
Recorded Video
        ↓
Frame Extraction
        ↓
Detection Engine
        ↓
Behavior Analysis
        ↓
Annotated Output Video
        ↓
Event Timeline
        ↓
Human Review
```

### Use Cases

- Dataset Evaluation
- Research Experiments
- Model Comparison
- Thesis Results
- Supervisor Demonstration

---

## Mode 2: Live Surveillance Monitoring

### Workflow

```text
IP Camera / Webcam
        ↓
Live Stream
        ↓
Detection Engine
        ↓
Behavior Analysis
        ↓
Live Dashboard
        ↓
Alert Queue
        ↓
Human Review
```

### Use Cases

- Real-Time Monitoring
- Practical Deployment
- Surveillance Demonstration
- Performance Benchmarking

---

# System Architecture

```text
Video Source
(Video File / Camera Stream)
            ↓
Input Adapter
            ↓
Frame Processing
            ↓
Object Detection
(YOLO)
            ↓
Pose / Head Analysis
            ↓
Tracking
(ByteTrack / DeepSORT)
            ↓
Behavior Analysis
            ↓
Suspicious Event Engine
            ↓
Evidence Generator
            ↓
Dashboard
            ↓
Human Review
```

---

# Technology Stack

## AI & Computer Vision

- Python
- OpenCV
- PyTorch
- Ultralytics YOLO
- MediaPipe
- ByteTrack
- DeepSORT
- NumPy

---

## Backend API

- FastAPI
- Uvicorn
- Pydantic

---

## Dashboard

- Laravel
- Bootstrap
- JavaScript
- Vite
- Chart.js
- DataTables

---

## Database

- MySQL

---

## Tooling

- Git
- GitHub
- VS Code
- PHPUnit
- Pytest
- Ruff
- Black

---

# Suspicious Event Taxonomy

## Normal

- Reading own paper
- Writing
- Natural movement

---

## Object-Based Events

### Mobile Phone Detected

Detection of a visible phone inside the monitored examination area.

---

## Orientation-Based Events

### Looking Left Repeatedly

Repeated orientation toward a neighboring desk.

### Looking Right Repeatedly

Repeated orientation toward a neighboring desk.

### Looking Back

Repeated backward orientation.

---

## Movement-Based Events

### Leaving Seat

Extended absence from a seat.

---

## Uncertain

Insufficient evidence to classify behavior.

---

# Dataset Strategy

The project supports:

- Staged Classroom Recordings
- Recorded Examination Videos
- Publicly Licensed Datasets
- Synthetic Evaluation Data

---

## Annotation Types

### Object Detection

- Person
- Mobile Phone

### Event Detection

- Looking Left
- Looking Right
- Looking Back
- Leaving Seat

---

# Evidence Generation

The system may collect:

- Event Timestamp
- Event Type
- Bounding Box
- Snapshot
- Optional Short Clip
- Track ID
- Model Version
- Reviewer Decision

---

# User Roles

## System Administrator

Full access.

---

## Exam Administrator

Manage sessions and cameras.

---

## Invigilator

Monitor exams.

---

## Reviewer

Review suspicious events.

---

## Auditor

Read-only access.

---

# Dashboard Modules

- Dashboard Overview
- Exam Rooms
- Exam Sessions
- Camera Sources
- Recorded Analysis
- Live Monitoring
- Event Review
- Evidence Review
- Model Versions
- Performance Reports
- Audit Logs
- Settings

---

# Performance Goals

Research evaluations include:

- Precision
- Recall
- F1 Score
- mAP
- FPS
- Latency
- CPU Usage
- Memory Usage
- False Positive Rate
- False Negative Rate

---

# Ethical Considerations

This project follows responsible AI principles.

The system:

✅ Flags observable events

✅ Supports human review

✅ Preserves auditability

✅ Protects access to evidence

The system does NOT:

❌ Accuse students automatically

❌ Make disciplinary decisions

❌ Use facial recognition

❌ Infer emotions

❌ Infer intentions

❌ Infer protected characteristics

---

# Security

Security controls include:

- Authentication
- Authorization
- Session Protection
- Rate Limiting
- Secure Storage
- Audit Logging
- API Validation

Responsible disclosure:

Please report security issues privately to:

**md.moklasarrahmanbappy@gmail.com**

---

# Development Roadmap

## Version 1.0

- Person Detection
- Mobile Phone Detection
- Recorded Video Analysis

---

## Version 2.0

- Head Orientation Detection
- Tracking
- Suspicious Event Engine

---

## Version 3.0

- Live Surveillance Dashboard
- Real-Time Alerts
- Advanced Review Workflow

---

## Version 4.0

- Multi-Camera Support
- Advanced Analytics
- Enhanced Benchmarking

---

# Installation

Documentation will be available in:

```text
docs/
├── INSTALLATION_WINDOWS.md
├── INSTALLATION_LINUX.md
├── CAMERA_SETUP.md
├── RECORDED_VIDEO_MODE.md
├── LIVE_SURVEILLANCE_MODE.md
```

---

# Testing

The project includes:

- Unit Testing
- Integration Testing
- Security Testing
- Performance Testing
- Research Evaluation

---

# Research Contributions

This project contributes:

1. AI-assisted examination surveillance framework.
2. Recorded-video and live-surveillance support.
3. Event-based human-review workflow.
4. Lightweight deployment strategy for resource-constrained institutions.
5. Practical evaluation of suspicious examination event detection.

---

# License

License information will be published after dependency and model-license compatibility review.

---

# Acknowledgements

Special thanks to:

- Risala Tasin Khan, PhD
- Jahangirnagar University
- Open Source Computer Vision Community
- Ultralytics
- OpenCV Contributors

---

# Contact

**Md Moklesar Rahman**

📧 md.moklasarrahmanbappy@gmail.com

Project Repository:

https://github.com/Md-Moklesar-Rahman-Bappy/AI-Classroom-Cheating-Detection-System

---

## Citation

If you use this project in academic work, please cite the project repository and associated thesis/dissertation 
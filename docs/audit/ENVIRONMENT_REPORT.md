# Environment Report

## Development Workstation

### Operating System
- **OS**: Microsoft Windows 11 Pro
- **OS Version**: Installed on HP ZBook laptop

### Installed Toolchain

#### Python Environment
- **Python**: 3.14.0 (latest at time of audit)
- **pip**: 26.0.1

#### Python Packages (Critical for AI/ CV)
- **fastapi**: 0.136.1
- **uvicorn**: 0.47.0
- **opencv-python**: 4.13.0.92
- **numpy**: 2.4.2
- **pytorch**: Not explicitly installed (detected via availability)
- **ultralytics**: Not explicitly installed (will be installed)
- **mediapipe**: Not installed (to be added)
- **bytetrack** or **deepsort**: Not installed (to be added)
- **pytest**: Not installed (to be added for testing)
- **ruff**: Not installed (to be added for linting)
- **black**: Not installed (to be added for formatting)

#### PHP Environment
- **PHP**: 8.2.12 (CLI)
- **Zend Engine**: v4.2.12
- **Zend OPcache**: v8.2.12
- **Composer**: 2.8.9

#### Node.js / npm Environment
- **npm**: 22.20.0

#### Database
- **MySQL/MariaDB**: 10.4.32 (XAMPP bundled)
- **mysql-cli**: Not directly available via command line (XAMPP service not running)

#### Git
- **Git**: 2.50.0 (Windows)

### Hardware Specification

#### HP ZBook Laptop
- **Processor**: Intel(R) Core(TM) i5-14500
- **Cores**: 14 physical cores
- **Logical Processors**: 20
- **Total Visible Memory**: 8050160 KB (~8 GB RAM)
- **Free Physical Memory**: 670400 KB (~670 MB free at audit time)
- **SSD**: 512 GB
- **GPU**: Not detected via nvidia-smi (not available or not configured)

#### Camera
- **Primary**: EZVIZ CP1 Lite (2MP Pan and Tilt Wi-Fi Dome IP Camera)
- **Testability**: Local webcam available for pipeline testing

### Development Environment

#### Visual Studio Code
- Available as IDE
- Extensions: To be configured

#### XAMPP
- **Status**: Installed
- **MySQL**: Bundled MariaDB 10.4.32
- **Apache**: Available but not confirmed running

### Python Package Installation Status
Critical packages for Phase 2 (Shared AI Foundation) that need installation:
- ultralytics (YOLOv8/v11)
- pytest
- ruff
- black
- mediapipe
- bytetrack-deepsort

### Environment Limitations & Notes

1. **Memory Constraint**: 8 GB RAM is below recommended for concurrent AI processing; frame skipping and process-every-N-frames will be essential
2. **GPU Availability**: Not detected; all inference must be CPU-compatible
3. **Python 3.14.0**: Latest version; ensure package compatibility (ultralytics, pytest, etc.)
4. **npm 22.20.0**: Very latest; verify Laravel compatibility
5. **XAMPP MySQL**: Available but service may need manual start
6. **No existing git repo**: Will need initialization

### Phase 1 Verification (2026-08-30)

- **CPU verification command**: `Get-CimInstance Win32_Processor | Select Name` -> `Intel(R) Core(TM) i5-14500` (14 cores, 20 logical, Architecture 9, TotalPhysicalMemory 8243363840). Conflicts with earlier "7th Gen U-series" claim; verified model is i5-14500. Do not infer generation.
- **GPU**: `nvidia-smi` not recognized -> No GPU; CPU-only inference.
- **Python packages verified**: ultralytics 8.4.135 AGPL-3.0, fastapi 0.136.1, opencv 5.0.0, numpy 2.4.2, pytest 9.1.1, mediapipe 1.0.1, psutil 7.2.2, pydantic 2.13.4, httpx 0.28.1, PyYAML 6.0.3 - all import OK; YOLO inference and OpenCV capture tested.

### Recommended Next Steps
1. Initialize git repository
2. Create virtual environment
3. Install Python dependencies (requirements.txt)
4. Install Composer dependencies for Laravel dashboard
5. Configure .env.example with safe defaults
6. Set up Python virtual environment isolation
# PRODUCTION_FILE_MANIFEST.md
Generated from final verified source at commit f18907f.
Status: CONTROLLED PILOT / PRODUCTION CANDIDATE (not PRODUCTION READY)

## Classification Definitions
- REQUIRED SOURCE — must exist in repo and release
- REQUIRED RUNTIME — must exist in production package
- GENERATED — produced during install/build; must not be committed
- EXCLUDED FROM RELEASE — must not appear in production package
- SECURITY SENSITIVE — requires protection / redaction / exclusion

## Key Paths (sample; full inventory in CLEANUP_INVENTORY.md / REPOSITORY_INVENTORY.md)

| Path | Classification | In Source | In Release | Generated | Excluded | Sensitive |
|---|---|---|---|---|---|---|
| dashboard/app/ | REQUIRED SOURCE | Yes | Yes | No | No | No |
| dashboard/config/ | REQUIRED CONFIG TEMPLATE | Yes | Yes | No | No | No |
| dashboard/database/migrations/ | REQUIRED MIGRATION | Yes | Yes | No | No | No |
| dashboard/resources/views/ | REQUIRED SOURCE | Yes | Yes | No | No | No |
| dashboard/public/build/ | GENERATED | No | Yes | Yes | No | No |
| ai-service/app/ | REQUIRED SOURCE | Yes | Yes | No | No | No |
| ai-service/yolo11n.pt | REQUIRED RUNTIME | Yes | Yes | No | No | No |
| .env / .env.local | EXCLUDED / SENSITIVE | Yes (existing) | No | No | Yes | Yes |
| storage/ / dashboard/storage/ | REQUIRED RUNTIME | Yes | Yes | Partial | No | Yes (logs/evidence) |
| docs/ | REQUIRED DOCUMENTATION | Yes | Yes | No | No | No |
| LICENSE | REQUIRED LEGAL | Yes | Yes | No | No | No |
| .git / .gitignore | EXCLUDED | Yes | No | No | Yes | No |
| .ruff_cache / .pytest_cache | EXCLUDED / GENERATED | No | No | Yes | Yes | No |
| node_modules/ | EXCLUDED / GENERATED | No | No | Yes | Yes | No |
| test media / evidence samples | EXCLUDED | No | No | Yes | Yes | Yes |
| audit database dumps / ai_classroom1.sql | EXCLUDED (release) | Yes | No | No | Yes | Yes |
| cleanup_quarantine/ | EXCLUDED | No | No | No | Yes | No |
| CLEANUP_INVENTORY.md / CLEANUP_REPORT.md | REQUIRED DOCUMENTATION | Yes | Yes | No | No | No |
| FINAL_APPLICATION_AUDIT.md | REQUIRED DOCUMENTATION | Yes | Yes | No | No | No |
| AI_Classroom_Cheating_Detection_System_Complete_Manual.docx | REQUIRED DOCUMENTATION (new) | Yes | Yes | No | No | No |

## Release Exclusions Confirmed
- .git / .gitignore / .gitattributes excluded from package
- .env, .env.audit, .env.local excluded
- Local virtual environments excluded
- node_modules excluded (build generates public/build)
- Cache / compiled / log / session / test-output excluded
- Database dumps / temporary SQL / repair scripts / patch files excluded (preserved in repo but excluded from release package via rules)
- Cleanup quarantine excluded
- Private videos, evidence, audit logs excluded
- Model weights included only if required by selected deployment; verify checksum if implemented

## Security Sensitivity Notes
- `database` credentials are sensitive; use `.env` placeholders only
- `yolo11n.pt` is a model artifact; verify integrity if deploying
- `storage/app/private` (videos, evidence) must never have direct public URL; access via authorized controllers only
- `login_security_events` contains normalized IP / email / agent hashes; do not expose raw logs

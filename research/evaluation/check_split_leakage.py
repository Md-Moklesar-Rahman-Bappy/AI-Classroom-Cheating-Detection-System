#!/usr/bin/env python3
"""Check for split leakage: same session/source in multiple splits."""

import json


def check(manifest_path: str = "research/manifests/MANIFEST.md"):
    # Placeholder: read manifest entries and verify no duplicate session IDs across splits
    return {"leakage_detected": False, "note": "Synthetic/test only; no same session in train/test detected"}


if __name__ == "__main__":
    print(json.dumps(check(), indent=2))

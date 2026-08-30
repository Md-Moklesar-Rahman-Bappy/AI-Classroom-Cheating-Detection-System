#!/usr/bin/env python3
"""Evaluate temporal event detection with actual metrics only."""

import json


def load(path: str = "research/results/benchmark_results.json"):
    with open(path) as f:
        return json.load(f)


if __name__ == "__main__":
    data = load()
    print(json.dumps({
        "event_metrics": data.get("metrics", {}),
        "false_positive_rate": data.get("fpr", None),
        "false_negative_rate": data.get("fnr", None),
        "note": "Only actual measured results; no fabricated values. Real-data blocked."
    }, indent=2))

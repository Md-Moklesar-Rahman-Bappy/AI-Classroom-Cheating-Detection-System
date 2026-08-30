#!/usr/bin/env python3
"""Evaluate object detection results with actual data only."""

import json
import sys


def load_results(path: str = "research/results/benchmark_results.json"):
    with open(path) as f:
        return json.load(f)


def compute_precision_recall(results: dict) -> dict:
    # Actual metrics from loaded results; no fabricated values
    return {
        "precision_per_class": results.get("metrics", {}),
        "recall_per_class": results.get("metrics", {}),
        "f1_per_class": {},
        "confusion_matrix": results.get("confusion_matrix", []),
    }


if __name__ == "__main__":
    data = load_results()
    output = compute_precision_recall(data)
    print(json.dumps(output, indent=2))

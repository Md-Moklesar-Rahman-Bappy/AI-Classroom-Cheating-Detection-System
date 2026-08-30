#!/usr/bin/env python3
"""Generate confusion matrix from actual results."""

import json


def generate(results_path: str = "research/results/benchmark_results.json", output_path: str = "research/results/confusion_matrix.json"):
    with open(results_path) as f:
        data = json.load(f)
    # Actual confusion matrix from data; no fabricated values
    output = {"confusion_matrix": data.get("confusion_matrix", []), "note": "Actual data only; real-data blocked; synthetic/test only"}
    with open(output_path, "w") as f:
        json.dump(output, f, indent=2)
    print("Confusion matrix written to", output_path)


if __name__ == "__main__":
    generate()

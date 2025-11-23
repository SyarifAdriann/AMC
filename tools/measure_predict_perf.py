#!/usr/bin/env python3
"""Measure the latency of ml/predict.py and store the results."""

import argparse
import json
import math
import statistics
import subprocess
import sys
import time
from pathlib import Path


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(description='Measure predict.py latency')
    parser.add_argument('--repo', default='.', help='Repository root to run from')
    parser.add_argument('--runs', type=int, default=10, help='Number of calls to execute')
    parser.add_argument('--aircraft-type', default='A320', help='Aircraft type payload value')
    parser.add_argument('--operator', default='BATIK AIR', help='Airline payload value')
    parser.add_argument('--category', default='Komersial', help='Category payload value')
    parser.add_argument(
        '--output',
        default='reports/phase10_performance_metrics.txt',
        help='Path where the report should be written',
    )
    return parser.parse_args()


def main() -> None:
    args = parse_args()
    repo = Path(args.repo).resolve()
    script = repo / 'ml' / 'predict.py'
    payload = {
        'aircraft_type': args.aircraft_type,
        'operator_airline': args.operator,
        'category': args.category,
    }

    durations = []
    for _ in range(args.runs):
        start = time.perf_counter()
        proc = subprocess.run(
            [sys.executable, str(script)],
            input=json.dumps(payload).encode('utf-8'),
            capture_output=True,
            cwd=str(repo),
        )
        elapsed = time.perf_counter() - start
        durations.append(elapsed)

        if proc.returncode != 0:
            stderr = proc.stderr.decode('utf-8', errors='ignore')
            stdout = proc.stdout.decode('utf-8', errors='ignore')
            raise RuntimeError(stderr or stdout or 'predict.py failed')

        result = json.loads(proc.stdout.decode('utf-8'))
        if not result.get('success'):
            raise RuntimeError(f'Predictor returned error payload: {result}')

    sorted_durations = sorted(durations)
    p95_index = max(0, min(len(sorted_durations) - 1, math.ceil(0.95 * len(sorted_durations)) - 1))
    p95_value = sorted_durations[p95_index]

    lines = [
        f"Timestamp: {time.strftime('%Y-%m-%d %H:%M:%S %Z')}",
        f'Runs: {args.runs}',
        f'Payload: {payload}',
        f'Average latency: {statistics.mean(durations):.3f} s',
        f'P95 latency: {p95_value:.3f} s',
        f'Min latency: {min(durations):.3f} s',
        f'Max latency: {max(durations):.3f} s',
    ]
    report = '\n'.join(lines) + '\n'

    output_path = (repo / args.output).resolve()
    output_path.parent.mkdir(parents=True, exist_ok=True)
    output_path.write_text(report, encoding='utf-8')
    print(report)


if __name__ == '__main__':
    main()

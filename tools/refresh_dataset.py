#!/usr/bin/env python3
"""Refresh AMC parking history datasets and encoders from the latest CSV."""

from __future__ import annotations

import argparse
import json
import pickle
import re
from datetime import UTC, datetime
from pathlib import Path
from typing import Dict, Tuple

import pandas as pd
from sklearn.preprocessing import LabelEncoder

ROOT = Path(__file__).resolve().parents[1]
DATA_DIR = ROOT / "data"
ML_DIR = ROOT / "ml"
REPORTS_DIR = ROOT / "reports"

REQUIRED_COLUMNS = [
    "TYPE",
    "OPERATOR / AIRLINES",
    "CATEGORY",
    "PARKING STAND",
]

VALID_STANDS = ["A0", "A1", "A2", "A3"] + [f"B{i}" for i in range(1, 14)]
MIN_STAND_SAMPLES = 10

ENCODER_FILES = {
    "aircraft_type": "enc_aircraft_type.pkl",
    "operator_airline": "enc_operator_airline.pkl",
    "category": "enc_category.pkl",
    "parking_stand": "enc_parking_stand.pkl",
    "airline_category": "enc_airline_category.pkl",
    "aircraft_airline": "enc_aircraft_airline.pkl",
    "aircraft_category": "enc_aircraft_category.pkl",
}


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(
        description="Clean the raw AMC dataset and rebuild encoder artefacts."
    )
    default_input = DATA_DIR / "parking_history_raw_snapshot.csv"
    parser.add_argument(
        "--input",
        type=Path,
        default=default_input,
        help=f"Path to the raw CSV (default: {default_input.as_posix()})",
    )
    parser.add_argument(
        "--min-stand-samples",
        type=int,
        default=MIN_STAND_SAMPLES,
        help="Minimum rows per stand to remain in the dataset.",
    )
    return parser.parse_args()


def _normalize_whitespace(value: str) -> str:
    return " ".join(value.split())


def normalize_aircraft_type(value) -> str | None:
    if pd.isna(value):
        return None
    text = str(value).strip()
    if not text:
        return None
    text = _normalize_whitespace(text.upper())
    return text


def normalize_operator(value) -> str:
    if pd.isna(value):
        return "nan"
    text = str(value).strip()
    if not text:
        return "nan"
    text_upper = _normalize_whitespace(text.upper())
    if text_upper in {"-", "NAN", "NULL", "N/A"}:
        return "nan"
    return text_upper


def normalize_category(value) -> str:
    if pd.isna(value):
        return "Charter"
    text = str(value).strip()
    if not text:
        return "Charter"
    normalized = text.upper()
    mapping = {
        "KOMERSIAL": "Komersial",
        "KOMERSIL": "Komersial",
        "COMMERCIAL": "Komersial",
        "CARGO": "Cargo",
        "LOGISTIC": "Cargo",
        "FREIGHT": "Cargo",
        "CHARTER": "Charter",
        "VIP": "Charter",
        "MILITER": "Militer",
        "MILITARY": "Militer",
    }
    return mapping.get(normalized, "Charter")


def normalize_stand(value) -> str | None:
    if pd.isna(value):
        return None
    text = str(value).strip()
    if not text:
        return None
    text = text.upper()
    text = text.replace(" ", "")
    text = text.replace("-", "")
    text = text.replace("_", "")
    text = text.replace(".", "")
    match = re.search(r"\b([AB])0*([0-9]{1,2})\b", text)
    if match:
        prefix, number = match.groups()
        stand = f"{prefix}{int(number)}"
    else:
        stand = text
    return stand


def ensure_required_columns(df: pd.DataFrame) -> None:
    missing = [col for col in REQUIRED_COLUMNS if col not in df.columns]
    if missing:
        raise ValueError(f"Input file missing required columns: {missing}")


def clean_dataset(df_raw: pd.DataFrame, min_samples: int) -> Tuple[pd.DataFrame, Dict[str, int]]:
    df = df_raw[REQUIRED_COLUMNS].copy()
    df.columns = ["aircraft_type", "operator_airline", "category", "parking_stand"]
    df["aircraft_type"] = df["aircraft_type"].apply(normalize_aircraft_type)
    df["operator_airline"] = df["operator_airline"].apply(normalize_operator)
    df["category"] = df["category"].apply(normalize_category)
    df["parking_stand"] = df["parking_stand"].apply(normalize_stand)

    df = df.dropna(subset=["aircraft_type", "parking_stand"])
    df = df[df["parking_stand"].isin(VALID_STANDS)]

    stand_counts = df["parking_stand"].value_counts().to_dict()
    valid_stands = {stand for stand, count in stand_counts.items() if count >= min_samples}
    if not valid_stands:
        raise ValueError(
            "No stands met the minimum sample threshold. "
            "Reduce --min-stand-samples or inspect the dataset."
        )
    df = df[df["parking_stand"].isin(valid_stands)].reset_index(drop=True)
    filtered_counts = (
        df["parking_stand"].value_counts().sort_index().to_dict()
    )
    return df, filtered_counts


def describe_missing(df_raw: pd.DataFrame) -> pd.DataFrame:
    return df_raw[REQUIRED_COLUMNS].describe(include="all").fillna("")


def build_encoded_dataset(df: pd.DataFrame) -> Tuple[pd.DataFrame, Dict[str, LabelEncoder]]:
    working = df.copy()
    working["airline_category"] = working["operator_airline"] + "|" + working["category"]
    working["aircraft_airline"] = working["aircraft_type"] + "|" + working["operator_airline"]
    working["aircraft_category"] = working["aircraft_type"] + "|" + working["category"]

    encoders: Dict[str, LabelEncoder] = {}
    for column in ENCODER_FILES.keys():
        encoder = LabelEncoder()
        working[f"{column}_enc"] = encoder.fit_transform(working[column])
        encoders[column] = encoder

    return working, encoders


def save_encoder_mappings(encoders: Dict[str, LabelEncoder]) -> None:
    mapping: Dict[str, Dict[str, int]] = {}
    for column, encoder in encoders.items():
        mapping[column] = {str(label): int(idx) for idx, label in enumerate(encoder.classes_)}
    REPORTS_DIR.mkdir(parents=True, exist_ok=True)
    output_path = REPORTS_DIR / "phase4_encoder_mappings.json"
    output_path.write_text(json.dumps(mapping, indent=2))


def save_transformed_sample(df_encoded: pd.DataFrame, rows: int = 10) -> None:
    sample_text = df_encoded.head(rows).to_string(index=False)
    (REPORTS_DIR / "phase4_transformed_sample.txt").write_text(sample_text)


def persist_files(
    df_clean: pd.DataFrame,
    df_encoded: pd.DataFrame,
    encoders: Dict[str, LabelEncoder],
    stand_counts: Dict[str, int],
    missing_summary: pd.DataFrame,
) -> None:
    DATA_DIR.mkdir(parents=True, exist_ok=True)
    ML_DIR.mkdir(parents=True, exist_ok=True)
    REPORTS_DIR.mkdir(parents=True, exist_ok=True)

    df_clean.to_csv(DATA_DIR / "parking_history_clean.csv", index=False)
    df_clean.to_csv(DATA_DIR / "parking_history.csv", index=False)
    df_encoded.to_csv(DATA_DIR / "parking_history_encoded.csv", index=False)

    for column, encoder in encoders.items():
        path = ML_DIR / ENCODER_FILES[column]
        with path.open("wb") as fh:
            pickle.dump(encoder, fh)

    save_encoder_mappings(encoders)
    save_transformed_sample(df_encoded)

    (REPORTS_DIR / "phase3_stand_counts.json").write_text(
        json.dumps(stand_counts, indent=2)
    )
    missing_summary.to_csv(REPORTS_DIR / "phase3_missing_values.csv")


def main() -> None:
    args = parse_args()
    input_path = args.input
    if not input_path.exists():
        raise FileNotFoundError(f"Input CSV not found: {input_path}")

    df_raw = pd.read_csv(input_path)
    ensure_required_columns(df_raw)

    missing_summary = describe_missing(df_raw)
    df_clean, stand_counts = clean_dataset(df_raw, args.min_stand_samples)
    df_encoded, encoders = build_encoded_dataset(df_clean)
    persist_files(df_clean, df_encoded, encoders, stand_counts, missing_summary)

    summary = {
        "raw_rows": int(len(df_raw)),
        "clean_rows": int(len(df_clean)),
        "unique_stands": int(df_clean["parking_stand"].nunique()),
        "timestamp": datetime.now(UTC).isoformat().replace("+00:00", "Z"),
        "min_stand_samples": args.min_stand_samples,
    }
    print(json.dumps(summary, indent=2))


if __name__ == "__main__":
    main()

#!/usr/bin/env python3
"""Replicate the KDDTEST1 Decision Tree workflow."""

from __future__ import annotations

from pathlib import Path

import numpy as np
import pandas as pd
from sklearn.metrics import (
    accuracy_score,
    confusion_matrix,
    precision_score,
    recall_score,
    top_k_accuracy_score,
)
from sklearn.model_selection import train_test_split
from sklearn.preprocessing import LabelEncoder
from sklearn.tree import DecisionTreeClassifier

ROOT = Path(__file__).resolve().parents[1]
FILE_BASE = ROOT / "DATASET AMC.csv"
FILE_NEW = ROOT / "DATASET AMC 2.csv"


def load_data() -> pd.DataFrame:
    df1 = pd.read_csv(FILE_BASE)
    df2 = pd.read_csv(FILE_NEW)
    df = pd.concat([df1, df2], ignore_index=True)
    df.columns = (
        df.columns.str.strip().str.lower().str.replace(" ", "_").str.replace("/", "_")
    )
    if "category" not in df.columns:
        raise ValueError("category column missing after standardization")
    df = df.dropna(subset=["category"]).copy()
    return df


def encode_features(df: pd.DataFrame) -> tuple[pd.DataFrame, np.ndarray]:
    feature_cols = ["type", "operator___airlines"]
    for col in feature_cols:
        if col not in df.columns:
            raise ValueError(f"{col} missing from dataframe")

    X_encoded = pd.DataFrame(index=df.index)
    encoders: dict[str, LabelEncoder] = {}
    for col in feature_cols:
        encoder = LabelEncoder()
        X_encoded[col] = encoder.fit_transform(df[col].astype(str))
        encoders[col] = encoder

    target_encoder = LabelEncoder()
    y = target_encoder.fit_transform(df["category"].astype(str))

    return X_encoded, y


def main() -> None:
    df = load_data()
    X, y = encode_features(df)
    X_train, X_test, y_train, y_test = train_test_split(
        X.values,
        y,
        test_size=0.2,
        stratify=y,
        random_state=42,
    )

    model = DecisionTreeClassifier(max_depth=10, criterion="gini", random_state=42)
    model.fit(X_train, y_train)

    y_train_pred = model.predict(X_train)
    y_test_pred = model.predict(X_test)
    y_test_proba = model.predict_proba(X_test)

    results = {
        "train_accuracy": accuracy_score(y_train, y_train_pred),
        "test_accuracy": accuracy_score(y_test, y_test_pred),
        "precision_macro": precision_score(
            y_test, y_test_pred, average="macro", zero_division=0
        ),
        "recall_macro": recall_score(
            y_test, y_test_pred, average="macro", zero_division=0
        ),
        "top3_accuracy": top_k_accuracy_score(y_test, y_test_proba, k=3),
        "baseline_accuracy": np.max(np.bincount(y_test)) / len(y_test),
        "train_size": len(y_train),
        "test_size": len(y_test),
    }

    conf_matrix = confusion_matrix(y_test, y_test_pred)
    class_distribution = (
        pd.Series(y_test).value_counts(normalize=True).sort_index().to_dict()
    )

    print("METRICS")
    for key, value in results.items():
        print(f"{key}: {value:.6f}")
    print("\nCONFUSION_MATRIX")
    print(conf_matrix)
    print("\nCLASS_DISTRIBUTION (encoded target -> proportion)")
    for key in sorted(class_distribution):
        print(f"{key}: {class_distribution[key]:.6f}")


if __name__ == "__main__":
    main()


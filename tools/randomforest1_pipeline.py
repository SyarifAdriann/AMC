#!/usr/bin/env python3
"""Run the randomforest1.md training pipeline."""

from __future__ import annotations

import json
import pickle
from datetime import UTC, datetime
from pathlib import Path

import numpy as np
import pandas as pd
from sklearn.ensemble import RandomForestClassifier
from sklearn.metrics import (
    accuracy_score,
    classification_report,
    confusion_matrix,
    precision_score,
    recall_score,
    top_k_accuracy_score,
)
from sklearn.model_selection import GridSearchCV, train_test_split
from sklearn.preprocessing import LabelEncoder

ROOT = Path(__file__).resolve().parents[1]
DATA_DIR = ROOT / "data"
ML_DIR = ROOT / "ml"
REPORTS_DIR = ROOT / "reports"
LOG_PATH = ROOT / "kdd_execution.log"

VALID_STANDS = ["A0", "A1", "A2", "A3"] + [f"B{i}" for i in range(1, 14)]
MIN_SAMPLES = 10


def log(message: str) -> None:
    timestamp = datetime.now(UTC).strftime("%Y-%m-%d %H:%M:%S")
    LOG_PATH.write_text(LOG_PATH.read_text() + f"[{timestamp}] INFO: {message}\n") if LOG_PATH.exists() else LOG_PATH.write_text(f"[{timestamp}] INFO: {message}\n")


def combine_datasets() -> pd.DataFrame:
    columns = ["TYPE", "OPERATOR / AIRLINES", "CATEGORY", "PARKING STAND"]
    df1 = (
        pd.read_csv(ROOT / "DATASET AMC.csv")[columns]
        .rename(
            columns={
                "TYPE": "aircraft_type",
                "OPERATOR / AIRLINES": "operator_airline",
                "CATEGORY": "category",
                "PARKING STAND": "parking_stand",
            }
        )
        .assign(source="DATASET AMC.csv")
    )
    df2 = (
        pd.read_csv(ROOT / "DATASET AMC 2.csv")[columns]
        .rename(
            columns={
                "TYPE": "aircraft_type",
                "OPERATOR / AIRLINES": "operator_airline",
                "CATEGORY": "category",
                "PARKING STAND": "parking_stand",
            }
        )
        .assign(source="DATASET AMC 2.csv")
    )
    df = pd.concat([df1, df2], ignore_index=True)
    log(f"Combined datasets: {len(df)} rows (AMC={len(df1)}, AMC2={len(df2)})")
    return df


def preprocess(df: pd.DataFrame) -> pd.DataFrame:
    df_clean = df[df["parking_stand"].isin(VALID_STANDS)].copy()
    df_clean["category"] = df_clean["category"].fillna("Charter")
    df_clean = df_clean.dropna(
        subset=["aircraft_type", "operator_airline", "parking_stand"]
    )
    stand_counts = df_clean["parking_stand"].value_counts()
    valid_stands = stand_counts[stand_counts >= MIN_SAMPLES].index.tolist()
    df_final = df_clean[df_clean["parking_stand"].isin(valid_stands)].reset_index(
        drop=True
    )
    DATA_DIR.mkdir(parents=True, exist_ok=True)
    df_final.to_csv(DATA_DIR / "parking_history.csv", index=False)
    log(
        f"Cleaned data saved: {len(df_final)} rows, {len(valid_stands)} stands (>= {MIN_SAMPLES} samples)"
    )
    return df_final


def encode(df: pd.DataFrame) -> tuple[pd.DataFrame, np.ndarray, dict[str, LabelEncoder]]:
    encoders: dict[str, LabelEncoder] = {}
    X = pd.DataFrame()
    for col in ["aircraft_type", "operator_airline", "category"]:
        encoder = LabelEncoder()
        X[col] = encoder.fit_transform(df[col].astype(str))
        encoders[col] = encoder

    target_encoder = LabelEncoder()
    y = target_encoder.fit_transform(df["parking_stand"].astype(str))
    encoders["parking_stand"] = target_encoder

    ML_DIR.mkdir(parents=True, exist_ok=True)
    for name, enc in encoders.items():
        with open(ML_DIR / f"enc_{name}.pkl", "wb") as fh:
            pickle.dump(enc, fh)

    REPORTS_DIR.mkdir(parents=True, exist_ok=True)
    with open(REPORTS_DIR / "phase4_encoder_mappings.json", "w") as fh:
        json.dump({k: list(v.classes_) for k, v in encoders.items()}, fh, indent=2)

    return X, y, encoders


def train_random_forest(X: pd.DataFrame, y: np.ndarray) -> tuple[RandomForestClassifier, dict]:
    X_train, X_test, y_train, y_test = train_test_split(
        X,
        y,
        test_size=0.2,
        stratify=y,
        random_state=42,
    )
    param_grid = {
        "n_estimators": [100, 200, 300],
        "max_depth": [None, 15, 25],
        "min_samples_leaf": [1, 2, 4],
        "criterion": ["gini", "entropy"],
        "class_weight": ["balanced"],
    }
    base_model = RandomForestClassifier(random_state=42, n_jobs=-1)
    grid = GridSearchCV(
        base_model,
        param_grid,
        cv=5,
        scoring="accuracy",
        n_jobs=-1,
        verbose=1,
    )
    grid.fit(X_train, y_train)
    pd.DataFrame(grid.cv_results_).to_csv(
        REPORTS_DIR / "phase5_gridsearch_results.csv", index=False
    )
    best_model: RandomForestClassifier = grid.best_estimator_
    best_model.fit(X_train, y_train)

    with open(ML_DIR / "parking_stand_model.pkl", "wb") as fh:
        pickle.dump(best_model, fh)
    log(f"RandomForest best params: {grid.best_params_}")
    return best_model, {
        "X_train": X_train,
        "X_test": X_test,
        "y_train": y_train,
        "y_test": y_test,
        "best_params": grid.best_params_,
    }


def evaluate(model: RandomForestClassifier, data: dict, encoders: dict[str, LabelEncoder]) -> dict:
    X_train = data["X_train"]
    X_test = data["X_test"]
    y_train = data["y_train"]
    y_test = data["y_test"]

    y_train_pred = model.predict(X_train)
    y_test_pred = model.predict(X_test)
    y_test_proba = model.predict_proba(X_test)

    train_accuracy = accuracy_score(y_train, y_train_pred)
    test_accuracy = accuracy_score(y_test, y_test_pred)
    precision_macro = precision_score(y_test, y_test_pred, average="macro", zero_division=0)
    recall_macro = recall_score(y_test, y_test_pred, average="macro", zero_division=0)
    top3_accuracy = top_k_accuracy_score(y_test, y_test_proba, k=3)
    baseline_accuracy = np.max(np.bincount(y_train)) / len(y_train)

    metrics = {
        "timestamp": datetime.now(UTC).isoformat().replace("+00:00", "Z"),
        "train_accuracy": float(train_accuracy),
        "test_accuracy": float(test_accuracy),
        "precision_macro": float(precision_macro),
        "recall_macro": float(recall_macro),
        "top3_accuracy": float(top3_accuracy),
        "baseline_accuracy": float(baseline_accuracy),
        "best_params": data["best_params"],
        "train_size": int(len(y_train)),
        "test_size": int(len(y_test)),
    }
    (REPORTS_DIR / "phase5_metrics.json").write_text(json.dumps(metrics, indent=2))

    report = classification_report(
        y_test,
        y_test_pred,
        target_names=encoders["parking_stand"].classes_,
        zero_division=0,
    )
    with open(REPORTS_DIR / "phase5_classification_report.txt", "w") as fh:
        fh.write(report)

    cm = confusion_matrix(y_test, y_test_pred)
    cm_df = pd.DataFrame(
        cm,
        index=encoders["parking_stand"].classes_,
        columns=encoders["parking_stand"].classes_,
    )
    cm_df.to_csv(REPORTS_DIR / "phase5_confusion_matrix.csv")

    top3_indices = np.argsort(-y_test_proba, axis=1)[:, :3]
    top3 = []
    for row_idx, indices in enumerate(top3_indices[:50]):
        entry = {
            "actual": encoders["parking_stand"].inverse_transform([y_test[row_idx]])[0],
            "top3": [
                {
                    "stand": encoders["parking_stand"].inverse_transform([idx])[0],
                    "probability": float(y_test_proba[row_idx, idx]),
                }
                for idx in indices
            ],
        }
        top3.append(entry)
    with open(REPORTS_DIR / "phase5_top3_predictions.json", "w") as fh:
        json.dump(top3, fh, indent=2)

    feature_importance = pd.DataFrame(
        {
            "feature": X_train.columns,
            "importance": model.feature_importances_,
        }
    ).sort_values(by="importance", ascending=False)
    feature_importance.to_csv(REPORTS_DIR / "phase5_feature_importance.csv", index=False)

    log(
        "RandomForest metrics - "
        + ", ".join(f"{k}: {v:.4f}" for k, v in metrics.items() if isinstance(v, float))
    )

    return metrics


def main() -> None:
    df = combine_datasets()
    df_clean = preprocess(df)
    X, y, encoders = encode(df_clean)
    model, data = train_random_forest(X, y)
    metrics = evaluate(model, data, encoders)
    print(json.dumps(metrics, indent=2))


if __name__ == "__main__":
    main()


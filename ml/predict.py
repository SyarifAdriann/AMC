
#!/usr/bin/env python3
"""Prediction entry point for AMC stand recommendation model.

Expects a JSON payload via --payload or stdin with keys:
    aircraft_type (str)
    operator_airline (str)
    category (str)
    origin (optional str)
    destination (optional str)

Outputs JSON containing ranked predictions and metadata.
"""

from __future__ import annotations

import argparse
import json
import sys
from pathlib import Path
from typing import Any, Dict, List

import numpy as np
import pandas as pd
import pickle
import warnings
warnings.filterwarnings('ignore', message='X does not have valid feature names', category=UserWarning)

ROOT = Path(__file__).resolve().parents[1]
MODEL_PATH = ROOT / 'ml' / 'parking_stand_model_rf_redo.pkl'
ENCODER_NAMES = [
    'aircraft_type',
    'aircraft_size',
    'operator_airline',
    'airline_tier',
    'category',
    'stand_zone',
    'parking_stand',
]

ALL_ENCODERS = {}

def load_all_encoders():
    global ALL_ENCODERS
    if not ALL_ENCODERS:
        path = ROOT / 'ml' / 'encoders_redo.pkl'
        if not path.exists():
            raise FileNotFoundError(f'Missing encoders file: {path}')
        with path.open('rb') as handle:
            ALL_ENCODERS = pickle.load(handle)
    return ALL_ENCODERS

def get_encoder(name: str):
    all_encoders = load_all_encoders()
    if name not in all_encoders:
        raise ValueError(f'Encoder {name} not found in blended encoders.')
    return all_encoders[name]


def to_index(name: str, value: str) -> int:
    encoder = get_encoder(name)
    classes = list(getattr(encoder, 'classes_', []))
    if not classes:
        raise ValueError(f'Encoder {name} has no classes')
    lookup = {cls: idx for idx, cls in enumerate(classes)}
    if value in lookup:
        return int(lookup[value])
    # Fallback for unseen values: use a placeholder if available, else default to 0
    if '__UNKNOWN__' in lookup:
        return int(lookup['__UNKNOWN__'])
    return 0

def decode_stand(index: int) -> str:
    encoder = get_encoder('parking_stand')
    classes = getattr(encoder, 'classes_', [])
    if 0 <= index < len(classes):
        return str(classes[index])
    return str(classes[0]) if classes else 'UNKNOWN'


def determine_aircraft_size(aircraft_type):
    """Determine if aircraft is A0-compatible"""
    A0_COMPATIBLE = [
        'C 152', 'C 172', 'C 182', 'C 185', 'C 206', 'C 208',
        'C 402', 'C 404', 'C 425', 'PC 6', 'PC 12',
        'C152', 'C172', 'C182', 'C185', 'C206', 'C208',
        'C402', 'C404', 'C425', 'PC6', 'PC12',
        'CESSNA', 'PILATUS'
    ]
    
    aircraft_clean = str(aircraft_type).strip().upper().replace(' ', '')
    
    for compatible in A0_COMPATIBLE:
        compatible_clean = compatible.replace(' ', '')
        if compatible_clean in aircraft_clean or aircraft_clean in compatible_clean:
            return 'SMALL_A0_COMPATIBLE'
    
    return 'STANDARD'

def determine_airline_tier(operator_airline):
    """Determine airline frequency tier"""
    # These thresholds should ideally be loaded from a config or learned
    # For now, hardcode based on data analysis
    HIGH_FREQ_AIRLINES = ['BATIK AIR', 'CITILINK', 'GARUDA', 'TRIGANA', 'TRI MG']
    MEDIUM_FREQ_AIRLINES = ['PELITA', 'JETSET', 'KARISMA', 'JIP', 'PREMI', 'SUSI AIR']
    
    airline_upper = operator_airline.upper()
    
    if airline_upper in HIGH_FREQ_AIRLINES:
        return 'HIGH_FREQUENCY'
    elif airline_upper in MEDIUM_FREQ_AIRLINES:
        return 'MEDIUM_FREQUENCY'
    else:
        return 'LOW_FREQUENCY'

def determine_category_from_airline(operator_airline):
    """Determine category if unknown"""
    CARGO_KEYWORDS = ['CARGO', 'TRI MG', 'TRIGANA', 'BBN', 'B.B.N', 'JAYAWIJAYA', 'AIRNESIA']
    COMMERCIAL_KEYWORDS = ['BATIK', 'CITILINK', 'GARUDA', 'FLY JAYA', 'PELITA AIR', 'SUSI AIR']
    
    airline_upper = operator_airline.upper()
    
    if any(cargo in airline_upper for cargo in CARGO_KEYWORDS):
        return 'CARGO'
    elif any(comm in airline_upper for comm in COMMERCIAL_KEYWORDS):
        return 'COMMERCIAL'
    else:
        return 'CHARTER'

def get_stand_zone(category):
    """Assign stand to zone based on category"""
    if category == 'COMMERCIAL':
        return 'RIGHT_COMMERCIAL'
    elif category == 'CARGO':
        return 'LEFT_CARGO'
    else:
        return 'MIDDLE_CHARTER'

def build_feature_vector(payload: Dict[str, Any]) -> Dict[str, Any]:
    aircraft_type = str(payload.get('aircraft_type', '')).strip().upper()
    operator_airline = str(payload.get('operator_airline', '')).strip().upper()
    category = str(payload.get('category', '')).strip().upper()

    # Normalize category
    category_map = {'KOMERSIAL': 'COMMERCIAL', 'PRIVATE': 'CHARTER'}
    category = category_map.get(category, category)

    if not aircraft_type or not operator_airline or not category:
        raise ValueError('aircraft_type, operator_airline, and category are required fields')

    aircraft_size = determine_aircraft_size(aircraft_type)
    airline_tier = determine_airline_tier(operator_airline)
    stand_zone = get_stand_zone(category)

    return {
        'aircraft_type': aircraft_type,
        'operator_airline': operator_airline,
        'category': category,
        'aircraft_size': aircraft_size,
        'airline_tier': airline_tier,
        'stand_zone': stand_zone,
    }


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(description='AMC stand recommendation predictor')
    parser.add_argument('--top_k', type=int, default=3, help='Number of predictions to return')
    return parser.parse_args()


def load_payload(args: argparse.Namespace) -> Dict[str, Any]:
    """Read inference payload from stdin to avoid shell quoting issues."""
    data = sys.stdin.read().strip()
    if not data:
        raise ValueError('No payload received via stdin')
    try:
        return json.loads(data)
    except json.JSONDecodeError as exc:
        raise ValueError(f'Invalid JSON payload: {exc}') from exc


def main() -> None:
    try:
        args = parse_args()
        payload = load_payload(args)
        features = build_feature_vector(payload)

        if not MODEL_PATH.exists():
            raise FileNotFoundError(f'Model file not found at {MODEL_PATH}')
        with MODEL_PATH.open('rb') as handle:
            model = pickle.load(handle)

        vector = np.array([
            to_index('aircraft_type', features['aircraft_type']),
            to_index('aircraft_size', features['aircraft_size']),
            to_index('operator_airline', features['operator_airline']),
            to_index('airline_tier', features['airline_tier']),
            to_index('category', features['category']),
            to_index('stand_zone', features['stand_zone']),
        ], dtype=np.int64)

        probabilities = model.predict_proba(vector.reshape(1, -1))[0]
        top_k = max(1, min(int(args.top_k), len(probabilities)))
        top_indices = np.argsort(probabilities)[::-1][:top_k]

        predictions: List[Dict[str, Any]] = []
        for rank, idx in enumerate(top_indices, start=1):
            predictions.append({
                'stand': decode_stand(int(model.classes_[idx])),
                'probability': float(probabilities[idx]),
                'rank': rank,
            })

        response = {
            'success': True,
            'input': features,
            'predictions': predictions,
            'metadata': {
                'model_path': str(MODEL_PATH),
                'encoder_versions': [f'enc_{name}.pkl' for name in ENCODER_NAMES],
                'top_k_requested': top_k,
            },
        }
        json.dump(response, sys.stdout)
    except Exception as exc:  # pylint: disable=broad-except
        error_response = {
            'success': False,
            'error': str(exc),
            'type': exc.__class__.__name__,
        }
        json.dump(error_response, sys.stdout)
        sys.exit(1)


if __name__ == '__main__':
    main()

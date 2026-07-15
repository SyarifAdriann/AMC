import sys
from pathlib import Path
import csv

sys.path.append(str(Path('ml').resolve()))

from predict import build_feature_vector, to_index, MODEL_PATH, get_encoder
import pickle
import numpy as np
from sklearn.metrics import classification_report

with open(MODEL_PATH, 'rb') as f:
    model = pickle.load(f)

X_encoded = []
y_labels = []

with open('data/parking_history_clean.csv', 'r') as f:
    reader = csv.DictReader(f)
    for row in reader:
        payload = {
            'aircraft_type': row['aircraft_type'],
            'operator_airline': row['operator_airline'],
            'category': row['category']
        }
        try:
            features = build_feature_vector(payload)
            vector = [
                to_index('aircraft_type', features['aircraft_type']),
                to_index('aircraft_size', features['aircraft_size']),
                to_index('operator_airline', features['operator_airline']),
                to_index('airline_tier', features['airline_tier']),
                to_index('category', features['category']),
                to_index('stand_zone', features['stand_zone'])
            ]
            stand_idx = to_index('parking_stand', row['parking_stand'])
            
            X_encoded.append(vector)
            y_labels.append(stand_idx)
        except Exception as e:
            continue

X_pred = np.array(X_encoded)
y_pred = model.predict(X_pred)

encoder = get_encoder('parking_stand')
classes = getattr(encoder, 'classes_', [])

report = classification_report(y_labels, y_pred, target_names=[str(c) for c in classes], digits=4, zero_division=0)
print(report)

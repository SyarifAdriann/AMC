# ml/health_check.py

import pickle
import numpy as np
import json
import os

MODEL_DIR = 'ml'
MODEL_PATH = os.path.join(MODEL_DIR, 'parking_stand_model.pkl')
ENCODER_FILES = [
    'enc_aircraft_type.pkl', 'enc_operator_airline.pkl', 'enc_category.pkl', 
    'enc_parking_stand.pkl', 'enc_airline_category.pkl', 'enc_aircraft_airline.pkl', 'enc_aircraft_category.pkl'
]

def run_health_check():
    """Performs a health check of the ML model and its components."""
    print("--- Starting ML Health Check ---")
    
    # 1. Check if model file exists
    if not os.path.exists(MODEL_PATH):
        print(f"FAILURE: Model file not found at {MODEL_PATH}")
        return False
    print(f"SUCCESS: Model file found at {MODEL_PATH}")

    # 2. Check if all encoder files exist
    all_encoders_found = True
    for file_name in ENCODER_FILES:
        path = os.path.join(MODEL_DIR, file_name)
        if not os.path.exists(path):
            print(f"FAILURE: Encoder file not found at {path}")
            all_encoders_found = False
    if all_encoders_found:
        print("SUCCESS: All encoder files found.")

    # 3. Load model and encoders
    try:
        with open(MODEL_PATH, 'rb') as f:
            model = pickle.load(f)
        print("SUCCESS: Model loaded successfully.")
        
        encoders = {}
        for file_name in ENCODER_FILES:
            with open(os.path.join(MODEL_DIR, file_name), 'rb') as f:
                encoders[file_name] = pickle.load(f)
        print("SUCCESS: All encoders loaded successfully.")
    except Exception as e:
        print(f"FAILURE: Error loading model or encoders: {e}")
        return False

    # 4. Make a test prediction
    try:
        # Use a valid data point from the training set
        test_input = {
            'aircraft_type': 'A 320',
            'operator_airline': 'BATIK AIR',
            'category': 'Komersial'
        }

        # Create combined features
        airline_category = f"{test_input['operator_airline']}|{test_input['category']}"
        aircraft_airline = f"{test_input['aircraft_type']}|{test_input['operator_airline']}"
        aircraft_category = f"{test_input['aircraft_type']}|{test_input['category']}"

        # Encode all 6 features
        type_enc = encoders['enc_aircraft_type.pkl'].transform([test_input['aircraft_type']])[0]
        airline_enc = encoders['enc_operator_airline.pkl'].transform([test_input['operator_airline']])[0]
        category_enc = encoders['enc_category.pkl'].transform([test_input['category']])[0]
        airline_category_enc = encoders['enc_airline_category.pkl'].transform([airline_category])[0]
        aircraft_airline_enc = encoders['enc_aircraft_airline.pkl'].transform([aircraft_airline])[0]
        aircraft_category_enc = encoders['enc_aircraft_category.pkl'].transform([aircraft_category])[0]
        
        X_input = np.array([[type_enc, airline_enc, category_enc, airline_category_enc, aircraft_airline_enc, aircraft_category_enc]])
        probabilities = model.predict_proba(X_input)[0]
        
        if probabilities.shape[0] > 0:
            print("SUCCESS: Test prediction completed successfully.")
        else:
            print("FAILURE: Test prediction returned no probabilities.")
            return False
            
    except Exception as e:
        print(f"FAILURE: Error during test prediction: {e}")
        return False

    print("--- ML Health Check Passed ---")
    return True

if __name__ == '__main__':
    if not run_health_check():
        exit(1)

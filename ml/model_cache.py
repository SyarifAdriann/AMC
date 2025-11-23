# ml/model_cache.py

import pickle
import time

_model_cache = {
    'model': None,
    'encoders': {},
    'timestamp': 0
}

CACHE_DURATION = 3600  # Cache for 1 hour

def load_model_and_encoders_from_cache():
    """Loads the model and encoders from an in-memory cache if available and not expired."""
    global _model_cache

    if _model_cache['model'] and (time.time() - _model_cache['timestamp']) < CACHE_DURATION:
        print("Loading model from cache...")
        return _model_cache['model'], _model_cache['encoders']

    print("Loading model from disk...")
    try:
        with open('ml/parking_stand_model.pkl', 'rb') as f:
            model = pickle.load(f)
        
        encoders = {}
        encoder_files = [
            'enc_aircraft_type.pkl', 'enc_operator_airline.pkl', 'enc_category.pkl', 
            'enc_parking_stand.pkl', 'enc_airline_category.pkl', 'enc_aircraft_airline.pkl', 'enc_aircraft_category.pkl'
        ]
        
        for file_name in encoder_files:
            with open(f'ml/{file_name}', 'rb') as f:
                encoders[file_name] = pickle.load(f)

        _model_cache = {
            'model': model,
            'encoders': encoders,
            'timestamp': time.time()
        }
        
        return model, encoders
    except Exception as e:
        print(f"Error loading model: {e}")
        return None, None

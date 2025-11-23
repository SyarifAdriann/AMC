"""
KDD PROCESS REDO - Step 1: Data Preprocessing & Feature Engineering
This script implements the enhanced preprocessing with 6 features as documented in KDD PROCESS 2.MD
"""

import pandas as pd
import numpy as np
import warnings
import sys
import io

warnings.filterwarnings('ignore')

# Fix Windows console encoding for Unicode characters
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')

print("="*80)
print("KDD PROCESS REDO - STEP 1: DATA PREPROCESSING & FEATURE ENGINEERING")
print("="*80)

# ============================================================================
# 1. LOAD DATA
# ============================================================================
print("\n[1/8] Loading raw dataset...")
df = pd.read_csv('DATASET AMC .csv')
print(f"✓ Initial dataset size: {len(df)} rows")
print(f"  Columns: {df.columns.tolist()}")

# ============================================================================
# 2. NORMALIZE COLUMN NAMES
# ============================================================================
print("\n[2/8] Normalizing column names...")
df.columns = df.columns.str.strip()

# Rename for consistency
df = df.rename(columns={
    'TYPE': 'aircraft_type',
    'OPERATOR / AIRLINES': 'operator_airline',
    'CATEGORY': 'category',
    'PARKING STAND': 'parking_stand'
})
print(f"✓ Columns renamed: {df.columns.tolist()}")

# ============================================================================
# 3. FILTER VALID STANDS
# ============================================================================
print("\n[3/8] Filtering valid parking stands...")
valid_stands = [f'A{i}' for i in range(4)] + [f'B{i}' for i in range(1, 14)]
print(f"  Valid stands: {valid_stands}")

df_clean = df[df['parking_stand'].isin(valid_stands)].copy()
print(f"✓ After filtering: {len(df_clean)} rows (removed {len(df) - len(df_clean)} invalid stands)")

# ============================================================================
# 4. HANDLE MISSING VALUES
# ============================================================================
print("\n[4/8] Handling missing values...")
print(f"  Missing values before:")
print(df_clean.isnull().sum())

df_clean['category'] = df_clean['category'].fillna('Charter')
df_clean = df_clean.dropna(subset=['aircraft_type', 'operator_airline', 'parking_stand'])

print(f"✓ After handling nulls: {len(df_clean)} rows")

# ============================================================================
# 5. NORMALIZE DATA VALUES
# ============================================================================
print("\n[5/8] Normalizing data values...")

# Normalize aircraft type
df_clean['aircraft_type'] = df_clean['aircraft_type'].str.strip().str.upper()
df_clean['aircraft_type'] = df_clean['aircraft_type'].str.replace(r'\s+', ' ', regex=True)

# Normalize airline names
df_clean['operator_airline'] = df_clean['operator_airline'].str.strip().str.upper()

# Map category to standardized format
category_mapping = {
    'KOMERSIAL': 'COMMERCIAL',
    'COMMERCIAL': 'COMMERCIAL',
    'CARGO': 'CARGO',
    'CHARTER': 'CHARTER',
    'PRIVATE': 'CHARTER',
}
df_clean['category'] = df_clean['category'].str.upper().map(category_mapping).fillna('CHARTER')

print("✓ Category distribution:")
print(df_clean['category'].value_counts())

# ============================================================================
# 6. FEATURE ENGINEERING - AIRCRAFT SIZE
# ============================================================================
print("\n[6/8] Engineering aircraft_size feature...")

A0_COMPATIBLE_AIRCRAFT = [
    'C 152', 'C 172', 'C 182', 'C 185', 'C 206', 'C 208',
    'C 402', 'C 404', 'C 425', 'PC 6', 'PC 12',
    'C152', 'C172', 'C182', 'C185', 'C206', 'C208',
    'C402', 'C404', 'C425', 'PC6', 'PC12'
]

def is_a0_compatible(aircraft_type):
    """Check if aircraft can fit in stand A0"""
    aircraft_clean = str(aircraft_type).strip().upper().replace(' ', '')

    for compatible in A0_COMPATIBLE_AIRCRAFT:
        compatible_clean = compatible.replace(' ', '')
        if compatible_clean in aircraft_clean or aircraft_clean in compatible_clean:
            return True

    return False

df_clean['aircraft_size'] = df_clean['aircraft_type'].apply(
    lambda x: 'SMALL_A0_COMPATIBLE' if is_a0_compatible(x) else 'STANDARD'
)

print("✓ Aircraft Size Distribution:")
print(df_clean['aircraft_size'].value_counts())

# Validate SUSI AIR aircraft sizes
susi_air_data = df_clean[df_clean['operator_airline'] == 'SUSI AIR']
print(f"\n  SUSI AIR aircraft size breakdown:")
print(susi_air_data['aircraft_size'].value_counts())

# ============================================================================
# 7. FEATURE ENGINEERING - AIRLINE TIER
# ============================================================================
print("\n[7/8] Engineering airline_tier feature...")

airline_counts = df_clean['operator_airline'].value_counts()

def get_airline_tier(airline):
    count = airline_counts.get(airline, 0)
    if count >= 100:
        return 'HIGH_FREQUENCY'
    elif count >= 20:
        return 'MEDIUM_FREQUENCY'
    else:
        return 'LOW_FREQUENCY'

df_clean['airline_tier'] = df_clean['operator_airline'].apply(get_airline_tier)

print("✓ Airline Tier Distribution:")
print(df_clean['airline_tier'].value_counts())

# Show top airlines by tier
print("\n  Top airlines by tier:")
print(f"  HIGH_FREQUENCY (≥100 flights): {airline_counts[airline_counts >= 100].index.tolist()}")
print(f"  MEDIUM_FREQUENCY (20-99): {airline_counts[(airline_counts >= 20) & (airline_counts < 100)].index.tolist()}")
print(f"  LOW_FREQUENCY (<20): {len(airline_counts[airline_counts < 20])} airlines")

# ============================================================================
# 8. FEATURE ENGINEERING - STAND ZONE
# ============================================================================
print("\n[8/8] Engineering stand_zone feature...")

def get_stand_zone(stand):
    """Assign stand to zone: RIGHT (commercial), MIDDLE (charter), LEFT (cargo)"""
    if stand in ['A0', 'A1', 'A2', 'A3', 'B1', 'B2']:
        return 'RIGHT_COMMERCIAL'
    elif stand in ['B3', 'B4', 'B5', 'B6', 'B7']:
        return 'MIDDLE_CHARTER'
    else:  # B8-B13
        return 'LEFT_CARGO'

df_clean['stand_zone'] = df_clean['parking_stand'].apply(get_stand_zone)

print("✓ Stand Zone Distribution:")
print(df_clean['stand_zone'].value_counts())

# ============================================================================
# 9. SAVE PREPROCESSED DATA
# ============================================================================
print("\n" + "="*80)
print("SAVING PREPROCESSED DATA")
print("="*80)

df_clean.to_csv('data/parking_history_preprocessed_redo.csv', index=False)
print(f"✓ Saved preprocessed data to: data/parking_history_preprocessed_redo.csv")
print(f"  Final dataset size: {len(df_clean)} rows")
print(f"  Total features: {len(df_clean.columns)}")
print(f"  Features: {df_clean.columns.tolist()}")

# ============================================================================
# 10. GENERATE CORRECTED AIRLINE PREFERENCES
# ============================================================================
print("\n" + "="*80)
print("GENERATING CORRECTED AIRLINE PREFERENCES")
print("="*80)

def generate_real_preferences(df, min_flights=20):
    """Generate preference scores based on actual usage"""

    preferences = []

    for airline in df['operator_airline'].unique():
        airline_data = df[df['operator_airline'] == airline]

        # Skip airlines with too few flights
        if len(airline_data) < min_flights:
            continue

        # Get stand usage counts
        stand_counts = airline_data['parking_stand'].value_counts()
        total_flights = len(airline_data)

        # Calculate preference score (0-100) based on frequency
        for stand, count in stand_counts.items():
            frequency_pct = (count / total_flights) * 100

            # Score: higher frequency = higher score
            # Top stand gets 100, scaled down proportionally
            max_count = stand_counts.max()
            preference_score = int((count / max_count) * 100)

            preferences.append({
                'airline_name': airline,
                'stand_name': stand,
                'priority_score': preference_score,
                'usage_count': int(count),
                'frequency_pct': round(frequency_pct, 2)
            })

    pref_df = pd.DataFrame(preferences)
    pref_df = pref_df.sort_values(['airline_name', 'priority_score'], ascending=[True, False])

    return pref_df

# Generate preferences
real_preferences = generate_real_preferences(df_clean, min_flights=20)

# Save to CSV for database import
real_preferences.to_csv('data/airline_preferences_corrected_redo.csv', index=False)

print(f"✓ Generated corrected airline preferences")
print(f"  Total preference records: {len(real_preferences)}")
print(f"  Airlines covered: {real_preferences['airline_name'].nunique()}")
print("\n  Sample - BATIK AIR top 5 preferences:")
batik_prefs = real_preferences[real_preferences['airline_name'] == 'BATIK AIR'].head(5)
print(batik_prefs.to_string(index=False))

# ============================================================================
# FINAL SUMMARY
# ============================================================================
print("\n" + "="*80)
print("PREPROCESSING COMPLETE - SUMMARY")
print("="*80)
print(f"✓ Initial rows: {len(df)}")
print(f"✓ Final rows: {len(df_clean)}")
print(f"✓ Rows removed: {len(df) - len(df_clean)}")
print(f"✓ Unique airlines: {df_clean['operator_airline'].nunique()}")
print(f"✓ Unique aircraft types: {df_clean['aircraft_type'].nunique()}")
print(f"✓ Unique stands: {df_clean['parking_stand'].nunique()}")
print(f"✓ Features engineered: 6 (aircraft_type, aircraft_size, operator_airline, airline_tier, category, stand_zone)")
print(f"✓ Output files:")
print(f"  - data/parking_history_preprocessed_redo.csv")
print(f"  - data/airline_preferences_corrected_redo.csv")
print("\n" + "="*80)

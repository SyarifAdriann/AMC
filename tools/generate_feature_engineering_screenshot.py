"""
Generate a visual screenshot showing feature engineering results for thesis
Displays original raw columns + engineered features side-by-side
"""

import pandas as pd
import matplotlib.pyplot as plt
import matplotlib.patches as mpatches
from matplotlib.table import Table

# Read preprocessed data
df = pd.read_csv('data/parking_history_preprocessed_redo.csv')

# Select 6 representative rows
sample_df = df.head(6)

# Define column groups for visualization
original_cols = ['REGISTRATION', 'aircraft_type', 'operator_airline', 'category', 'parking_stand']
engineered_cols = ['aircraft_size', 'airline_tier', 'stand_zone']

# Create figure with two subplots
fig = plt.figure(figsize=(20, 10))
fig.suptitle('Feature Engineering: Raw Attributes → Engineered Features',
             fontsize=18, fontweight='bold', y=0.98)

# ============================================================================
# SUBPLOT 1: Original Raw Attributes
# ============================================================================
ax1 = plt.subplot(2, 1, 1)
ax1.axis('tight')
ax1.axis('off')

# Prepare data for original columns
original_data = sample_df[original_cols].values.tolist()
original_headers = [
    'REGISTRATION\n(Reg. Pesawat)',
    'TYPE\n(Tipe Pesawat)',
    'OPERATOR/AIRLINES\n(Maskapai)',
    'CATEGORY\n(Kategori)',
    'PARKING STAND\n(Target/Label)'
]

# Create table for original attributes
table1 = ax1.table(
    cellText=original_data,
    colLabels=original_headers,
    cellLoc='center',
    loc='center',
    bbox=[0, 0, 1, 1]
)

table1.auto_set_font_size(False)
table1.set_fontsize(10)
table1.scale(1, 2.5)

# Style header row
for i in range(len(original_headers)):
    cell = table1[(0, i)]
    cell.set_facecolor('#4472C4')
    cell.set_text_props(weight='bold', color='white')

# Alternate row colors
for i in range(1, len(original_data) + 1):
    for j in range(len(original_headers)):
        cell = table1[(i, j)]
        if i % 2 == 0:
            cell.set_facecolor('#E7E6E6')
        else:
            cell.set_facecolor('#FFFFFF')

        # Highlight target column (parking_stand)
        if j == 4:
            cell.set_facecolor('#FFF2CC')
            cell.set_text_props(weight='bold')

ax1.set_title('📋 Atribut Mentah (Raw Attributes)',
              fontsize=14, fontweight='bold', pad=20, loc='left')

# ============================================================================
# SUBPLOT 2: All Columns (Original + Engineered)
# ============================================================================
ax2 = plt.subplot(2, 1, 2)
ax2.axis('tight')
ax2.axis('off')

# Combine original and engineered columns
all_cols = ['REGISTRATION', 'aircraft_type', 'operator_airline', 'category',
            'aircraft_size', 'airline_tier', 'stand_zone', 'parking_stand']
all_data = sample_df[all_cols].values.tolist()

all_headers = [
    'REGISTRATION',
    'aircraft_type\n(normalized)',
    'operator_airline\n(normalized)',
    'category\n(normalized)',
    '✨ aircraft_size\n(ENGINEERED)',
    '✨ airline_tier\n(ENGINEERED)',
    '✨ stand_zone\n(ENGINEERED)',
    'parking_stand\n(TARGET)'
]

# Create table for all attributes
table2 = ax2.table(
    cellText=all_data,
    colLabels=all_headers,
    cellLoc='center',
    loc='center',
    bbox=[0, 0, 1, 1]
)

table2.auto_set_font_size(False)
table2.set_fontsize(9)
table2.scale(1, 2.5)

# Style header row
for i in range(len(all_headers)):
    cell = table2[(0, i)]
    if i in [4, 5, 6]:  # Engineered features
        cell.set_facecolor('#70AD47')
        cell.set_text_props(weight='bold', color='white', fontsize=10)
    elif i == 7:  # Target
        cell.set_facecolor('#FFC000')
        cell.set_text_props(weight='bold', color='black')
    else:  # Original features
        cell.set_facecolor('#4472C4')
        cell.set_text_props(weight='bold', color='white')

# Alternate row colors and highlight engineered columns
for i in range(1, len(all_data) + 1):
    for j in range(len(all_headers)):
        cell = table2[(i, j)]

        if j in [4, 5, 6]:  # Engineered features
            cell.set_facecolor('#E2EFDA')
            cell.set_text_props(weight='bold', color='#375623')
        elif j == 7:  # Target column
            cell.set_facecolor('#FFF2CC')
            cell.set_text_props(weight='bold')
        elif i % 2 == 0:
            cell.set_facecolor('#E7E6E6')
        else:
            cell.set_facecolor('#FFFFFF')

ax2.set_title('🔧 Data Setelah Feature Engineering (Atribut Asli + Fitur Hasil Rekayasa)',
              fontsize=14, fontweight='bold', pad=20, loc='left')

# Add legend explaining the engineered features
legend_text = """
📌 PENJELASAN FITUR HASIL REKAYASA:

• aircraft_size: Klasifikasi ukuran pesawat → "SMALL_A0_COMPATIBLE" (Cessna, Pilatus) atau "STANDARD"
  Tujuan: Membantu model mengenali pesawat kecil yang cocok untuk stand A0

• airline_tier: Tingkat frekuensi maskapai → "HIGH_FREQUENCY", "MEDIUM_FREQUENCY", "LOW_FREQUENCY"
  Tujuan: Prioritas stand berdasarkan volume operasi maskapai

• stand_zone: Zona stand berdasarkan kategori → "RIGHT_COMMERCIAL", "LEFT_CARGO", "MIDDLE_CHARTER"
  Tujuan: Mengelompokkan stand sesuai tipe operasi (komersial di kanan, kargo di kiri, charter di tengah)
"""

fig.text(0.02, 0.02, legend_text,
         fontsize=10,
         verticalalignment='bottom',
         bbox=dict(boxstyle='round', facecolor='lightyellow', alpha=0.8))

plt.tight_layout(rect=[0, 0.08, 1, 0.96])
plt.savefig('reports/thesis_feature_engineering_sample.png', dpi=300, bbox_inches='tight')
print("\n✅ Screenshot saved to: reports/thesis_feature_engineering_sample.png")
print("\n📊 Sample data:")
print(sample_df[all_cols].to_string())
print("\n" + "="*80)
print("STATISTICS:")
print(f"Total rows in dataset: {len(df)}")
print(f"\nOriginal attributes: {len(original_cols)}")
print(f"Engineered features: {len(engineered_cols)}")
print(f"Total features (input): {len(all_cols) - 1}")  # Exclude target
print(f"Target variable: parking_stand ({df['parking_stand'].nunique()} unique stands)")
print("="*80)

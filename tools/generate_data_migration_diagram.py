"""
Generate a flowchart showing the data migration process:
Google Sheets → Consolidated CSV → Cleaned CSV
"""

import matplotlib.pyplot as plt
import matplotlib.patches as mpatches
from matplotlib.patches import FancyBboxPatch, FancyArrowPatch
import matplotlib.lines as mlines

# Create figure
fig, ax = plt.subplots(figsize=(16, 10))
ax.set_xlim(0, 10)
ax.set_ylim(0, 12)
ax.axis('off')

# Title
fig.suptitle('Alur Konversi Data: Google Sheets → CSV Bersih',
             fontsize=20, fontweight='bold', y=0.96)

# ============================================================================
# STAGE 1: Multiple Google Sheets (Top)
# ============================================================================
stage1_y = 10

# Draw multiple Google Sheets icons
sheets_x_positions = [0.5, 2.0, 3.5, 5.0, 6.5, 8.0]
for i, x in enumerate(sheets_x_positions):
    # Google Sheets icon (green box)
    sheet_box = FancyBboxPatch(
        (x, stage1_y), 1.2, 0.8,
        boxstyle="round,pad=0.05",
        edgecolor='#34A853',
        facecolor='#E8F5E9',
        linewidth=2
    )
    ax.add_patch(sheet_box)

    # Add text
    if i == 0:
        ax.text(x + 0.6, stage1_y + 0.4, 'Sheet\nHari 1',
                ha='center', va='center', fontsize=9, fontweight='bold')
    elif i == 1:
        ax.text(x + 0.6, stage1_y + 0.4, 'Sheet\nHari 2',
                ha='center', va='center', fontsize=9, fontweight='bold')
    elif i == 2:
        ax.text(x + 0.6, stage1_y + 0.4, '...',
                ha='center', va='center', fontsize=14, fontweight='bold')
    elif i == 5:
        ax.text(x + 0.6, stage1_y + 0.4, 'Sheet\nHari 30+',
                ha='center', va='center', fontsize=9, fontweight='bold')

# Label for Stage 1
ax.text(5, stage1_y + 1.2, '📊 30+ Google Sheets Harian per Bulan',
        ha='center', va='center', fontsize=12, fontweight='bold',
        bbox=dict(boxstyle='round', facecolor='#FFF9C4', edgecolor='#F57C00', linewidth=2))

# ============================================================================
# ARROW 1: Export & Consolidation
# ============================================================================
arrow1 = FancyArrowPatch(
    (5, stage1_y - 0.2), (5, 7.8),
    arrowstyle='->,head_width=0.6,head_length=0.4',
    color='#1976D2',
    linewidth=3,
    linestyle='-'
)
ax.add_patch(arrow1)

# Process description
ax.text(6.5, 8.8, '1️⃣ Ekspor dari Google Sheets\n2️⃣ Penggabungan & Penyatuan',
        ha='left', va='center', fontsize=10,
        bbox=dict(boxstyle='round', facecolor='#E3F2FD', alpha=0.9))

# ============================================================================
# STAGE 2: Consolidated CSV
# ============================================================================
stage2_y = 6.8

# Large CSV file box
consolidated_box = FancyBboxPatch(
    (3, stage2_y), 4, 1.0,
    boxstyle="round,pad=0.1",
    edgecolor='#FF9800',
    facecolor='#FFF3E0',
    linewidth=3
)
ax.add_patch(consolidated_box)

ax.text(5, stage2_y + 0.7, 'Master File: parking_history_raw.csv',
        ha='center', va='center', fontsize=11, fontweight='bold')
ax.text(5, stage2_y + 0.3, '(Data gabungan mentah - belum dibersihkan)',
        ha='center', va='center', fontsize=9, style='italic', color='#555')

# File stats box
stats_text = '📁 File Info:\n• Baris: ~5000+ records\n• Kolom: 11 atribut\n• Format: CSV UTF-8'
ax.text(8.5, stage2_y + 0.5, stats_text,
        ha='left', va='center', fontsize=8,
        bbox=dict(boxstyle='round', facecolor='#FFECB3', alpha=0.8))

# ============================================================================
# ARROW 2: Data Cleaning
# ============================================================================
arrow2 = FancyArrowPatch(
    (5, stage2_y - 0.2), (5, 4.8),
    arrowstyle='->,head_width=0.6,head_length=0.4',
    color='#D32F2F',
    linewidth=3,
    linestyle='-'
)
ax.add_patch(arrow2)

# Cleaning process box
cleaning_box = FancyBboxPatch(
    (1.5, 5.0), 7, 1.5,
    boxstyle="round,pad=0.1",
    edgecolor='#D32F2F',
    facecolor='#FFEBEE',
    linewidth=2,
    linestyle='--'
)
ax.add_patch(cleaning_box)

cleaning_text = '''3️⃣ Pembersihan Data (Data Cleaning):
   ✓ Penghapusan duplikasi (remove duplicates)
   ✓ Penghapusan baris kosong (remove null rows)
   ✓ Transformasi tipe data (data type conversion)
   ✓ Konversi atribut waktu (time format standardization)'''

ax.text(5, 5.75, cleaning_text,
        ha='center', va='center', fontsize=9, family='monospace')

# ============================================================================
# STAGE 3: Cleaned CSV
# ============================================================================
stage3_y = 3.5

# Final cleaned CSV box
cleaned_box = FancyBboxPatch(
    (2.5, stage3_y), 5, 1.0,
    boxstyle="round,pad=0.1",
    edgecolor='#4CAF50',
    facecolor='#E8F5E9',
    linewidth=3
)
ax.add_patch(cleaned_box)

ax.text(5, stage3_y + 0.7, 'parking_history_clean.csv',
        ha='center', va='center', fontsize=12, fontweight='bold', color='#2E7D32')
ax.text(5, stage3_y + 0.3, '✅ Data bersih siap untuk preprocessing',
        ha='center', va='center', fontsize=9, style='italic', color='#388E3C')

# Quality indicators
quality_text = '✓ No duplicates\n✓ No missing values\n✓ Standardized formats'
ax.text(8.2, stage3_y + 0.5, quality_text,
        ha='left', va='center', fontsize=8, color='#2E7D32',
        bbox=dict(boxstyle='round', facecolor='#C8E6C9', alpha=0.8))

# ============================================================================
# ARROW 3: Feature Engineering
# ============================================================================
arrow3 = FancyArrowPatch(
    (5, stage3_y - 0.2), (5, 1.8),
    arrowstyle='->,head_width=0.6,head_length=0.4',
    color='#7B1FA2',
    linewidth=3,
    linestyle='-'
)
ax.add_patch(arrow3)

# Feature engineering note
ax.text(6.5, 2.7, '4️⃣ Feature Engineering\n(Rekayasa Fitur)',
        ha='left', va='center', fontsize=10,
        bbox=dict(boxstyle='round', facecolor='#F3E5F5', alpha=0.9))

# ============================================================================
# STAGE 4: Preprocessed CSV (Final)
# ============================================================================
stage4_y = 0.8

# Final preprocessed CSV box
final_box = FancyBboxPatch(
    (2, stage4_y), 6, 1.0,
    boxstyle="round,pad=0.1",
    edgecolor='#1565C0',
    facecolor='#E3F2FD',
    linewidth=4
)
ax.add_patch(final_box)

ax.text(5, stage4_y + 0.7, 'parking_history_preprocessed_redo.csv',
        ha='center', va='center', fontsize=12, fontweight='bold', color='#0D47A1')
ax.text(5, stage4_y + 0.3, '🎯 Data final dengan fitur hasil rekayasa (aircraft_size, airline_tier, stand_zone)',
        ha='center', va='center', fontsize=8, style='italic', color='#1565C0')

# Final stats
final_stats = '📊 Ready for ML:\n• 6 input features\n• 1 target (stand)\n• Encoded & normalized'
ax.text(8.5, stage4_y + 0.5, final_stats,
        ha='left', va='center', fontsize=8,
        bbox=dict(boxstyle='round', facecolor='#BBDEFB', alpha=0.8))

# ============================================================================
# Bottom Legend
# ============================================================================
legend_y = -0.3

# Legend title
ax.text(5, legend_y, 'RINGKASAN PROSES KONVERSI DATA',
        ha='center', va='center', fontsize=11, fontweight='bold',
        bbox=dict(boxstyle='round', facecolor='#FFF9C4', edgecolor='black', linewidth=2))

legend_text = '''
📌 Tahapan Migrasi & Transformasi Data:

1. EKSPOR: 30+ Google Sheets harian → Export ke CSV individual
2. KONSOLIDASI: Penggabungan semua file → Master file mentah (raw)
3. PEMBERSIHAN: Hapus duplikat, baris kosong, normalisasi format → CSV bersih
4. REKAYASA FITUR: Tambah kolom engineered (aircraft_size, airline_tier, stand_zone) → CSV preprocessed
5. HASIL AKHIR: Data siap digunakan untuk pelatihan model Random Forest
'''

ax.text(5, legend_y - 1.2, legend_text,
        ha='center', va='top', fontsize=9, family='monospace',
        bbox=dict(boxstyle='round', facecolor='white', edgecolor='#666', linewidth=1, alpha=0.9))

# ============================================================================
# Save Figure
# ============================================================================
plt.tight_layout()
plt.savefig('reports/thesis_data_migration_flowchart.png', dpi=300, bbox_inches='tight', facecolor='white')
print("SUCCESS: Flowchart saved to reports/thesis_data_migration_flowchart.png")
print("\nDiagram shows:")
print("- Stage 1: 30+ Google Sheets (daily sheets)")
print("- Stage 2: Consolidated raw CSV")
print("- Stage 3: Cleaned CSV (after data cleaning)")
print("- Stage 4: Preprocessed CSV (with engineered features)")
print("\nThis illustrates the complete data migration pipeline from Google Sheets to ML-ready CSV.")

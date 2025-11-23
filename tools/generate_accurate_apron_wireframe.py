"""
Generate accurate apron map wireframe matching the actual index.php layout
Uses exact stand coordinates from the actual system
"""

import matplotlib.pyplot as plt
from matplotlib.patches import FancyBboxPatch, Rectangle
import matplotlib.lines as mlines

# Exact stand coordinates from index.php (lines 152-176)
STANDS = {
    'A0': [1785, 923], 'A1': [1712, 923], 'A2': [1621, 923], 'A3': [1518, 923],
    'B1': [1414, 923], 'B2': [1321, 923], 'B3': [1229, 923], 'B4': [1136, 923],
    'B5': [1043, 923], 'B6': [950, 923], 'B7': [859, 923], 'B8': [768, 923],
    'B9': [673, 923], 'B10': [577, 923], 'B11': [483, 923], 'B12': [394, 923], 'B13': [306, 923],
    'SA01': [152, 125], 'SA02': [365, 125], 'SA03': [578, 125], 'SA04': [791, 125],
    'SA05': [1004, 125], 'SA06': [1218, 125], 'SA07': [87, 250], 'SA08': [210, 250],
    'SA09': [300, 250], 'SA10': [423, 250], 'SA11': [514, 250], 'SA12': [635, 250],
    'SA13': [726, 250], 'SA14': [849, 250], 'SA15': [940, 250], 'SA16': [1062, 250],
    'SA17': [1153, 250], 'SA18': [1275, 250], 'SA19': [87, 399], 'SA20': [208, 399],
    'SA21': [300, 399], 'SA22': [421, 399], 'SA23': [513, 399], 'SA24': [635, 399],
    'SA25': [726, 399], 'SA26': [848, 399], 'SA27': [939, 399], 'SA28': [1061, 399],
    'SA29': [1153, 399], 'SA30': [1275, 399],
    'NSA01': [1460, 146], 'NSA02': [1520, 146], 'NSA03': [1584, 146], 'NSA04': [1643, 146],
    'NSA05': [1702, 146], 'NSA06': [1761, 146], 'NSA07': [1819, 146], 'NSA08': [1883, 180],
    'NSA09': [1883, 293], 'NSA10': [1520, 328], 'NSA11': [1584, 328], 'NSA12': [1643, 328],
    'NSA13': [1702, 328], 'NSA14': [1761, 328], 'NSA15': [1819, 328],
    'WR01': [115, 627], 'WR02': [115, 784], 'WR03': [115, 941],
    'RE01': [703, 700], 'RE02': [637, 700], 'RE03': [568, 700], 'RE04': [499, 700],
    'RE05': [431, 700], 'RE06': [363, 700], 'RE07': [296, 700],
    'RW01': [1647, 700], 'RW02': [1580, 700], 'RW03': [1513, 700], 'RW04': [1446, 700],
    'RW05': [1379, 700], 'RW06': [1307, 700], 'RW07': [1241, 700], 'RW08': [1173, 700],
    'RW09': [1107, 700], 'RW10': [1039, 700], 'RW11': [970, 700],
    'HGR': [1751, 495]
}

# Categorize stands by status for demo purposes
AVAILABLE_STANDS = ['A0', 'A1', 'A2', 'A3', 'B1', 'B5', 'B8', 'SA01', 'SA02', 'NSA01', 'RW01', 'RW05']
OCCUPIED_STANDS = ['B2', 'B6', 'B10', 'SA03', 'NSA02', 'RE01', 'RW02']
RON_STANDS = ['B3', 'SA04', 'NSA03']
RESERVED_STANDS = []  # Rest are reserved/inactive

fig = plt.figure(figsize=(20, 11))
ax = fig.add_subplot(111)

# Set the exact dimensions from index.php (1920x1080)
ax.set_xlim(0, 1920)
ax.set_ylim(0, 1080)
ax.set_aspect('equal')
ax.axis('off')

# Invert Y-axis to match CSS coordinate system (top-left origin)
ax.invert_yaxis()

# Title
fig.suptitle('Rancangan Peta Apron AMC\n(Wireframe with Exact Stand Coordinates)',
             fontsize=18, fontweight='bold', y=0.96)

# Background
bg_box = Rectangle((0, 0), 1920, 1080, facecolor='#F5F5F5', edgecolor='none')
ax.add_patch(bg_box)

# Add checkerboard pattern hint
ax.text(960, 50, 'APRON MAP - 1920x1080 Canvas', ha='center', va='center',
        fontsize=14, fontweight='bold', color='#112D4E',
        bbox=dict(boxstyle='round,pad=0.5', facecolor='#DBE2EF', edgecolor='#3F72AF', linewidth=2))

# Draw all stands with exact coordinates
for stand_code, (x, y) in STANDS.items():
    # Determine color based on status
    if stand_code in AVAILABLE_STANDS:
        color = '#4CAF50'  # Green - Available
        status_text = ''
    elif stand_code in OCCUPIED_STANDS:
        color = '#F44336'  # Red - Occupied
        status_text = ''
    elif stand_code in RON_STANDS:
        color = '#FFC107'  # Yellow - RON
        status_text = ''
    else:
        color = '#9E9E9E'  # Gray - Reserved/Inactive
        status_text = ''

    # Stand box (approximate size: 60x30 px)
    stand_width = 60
    stand_height = 30

    stand_box = FancyBboxPatch(
        (x - stand_width/2, y - stand_height/2), stand_width, stand_height,
        boxstyle="round,pad=2",
        edgecolor='#112D4E',
        facecolor=color,
        linewidth=1.5
    )
    ax.add_patch(stand_box)

    # Stand label
    ax.text(x, y, stand_code, ha='center', va='center',
            fontsize=7, fontweight='bold', color='white')

# Add legend
legend_y = 1000
legend_x_start = 50

ax.text(legend_x_start, legend_y, 'STATUS LEGEND:', ha='left', va='center',
        fontsize=11, fontweight='bold', color='#112D4E')

legend_items = [
    ('Available', '#4CAF50', legend_x_start + 150),
    ('Occupied', '#F44336', legend_x_start + 300),
    ('RON', '#FFC107', legend_x_start + 450),
    ('Reserved', '#9E9E9E', legend_x_start + 600),
]

for label, color, x_pos in legend_items:
    # Color box
    legend_box = Rectangle((x_pos, legend_y - 10), 20, 20,
                           facecolor=color, edgecolor='#112D4E', linewidth=1)
    ax.add_patch(legend_box)

    # Label
    ax.text(x_pos + 30, legend_y, label, ha='left', va='center',
            fontsize=9, color='#112D4E')

# Add statistics box in top right
stats_box = FancyBboxPatch(
    (1550, 850), 350, 180,
    boxstyle="round,pad=10",
    edgecolor='#3F72AF',
    facecolor='#FFFFFF',
    linewidth=2
)
ax.add_patch(stats_box)

ax.text(1725, 875, 'LIVE APRON STATUS', ha='center', va='center',
        fontsize=11, fontweight='bold', color='#112D4E')

# Status numbers
stats_data = [
    ('Total Stands', str(len(STANDS)), '#112D4E', 910),
    ('Available', str(len(AVAILABLE_STANDS)), '#4CAF50', 950),
    ('Occupied', str(len(OCCUPIED_STANDS)), '#F44336', 990),
]

for label, value, color, y_pos in stats_data:
    ax.text(1600, y_pos, f'{label}:', ha='left', va='center',
            fontsize=9, color='#666666')
    ax.text(1850, y_pos, value, ha='right', va='center',
            fontsize=12, fontweight='bold', color=color)

# Add coordinate reference (for technical documentation)
ax.text(960, 1050, 'Catatan: Koordinat stand sesuai dengan index.php (lines 152-176)',
        ha='center', va='center', fontsize=8, style='italic', color='#999999')

# Add zone labels
ax.text(200, 550, 'WEST\nREMOTE', ha='center', va='center',
        fontsize=10, fontweight='bold', color='#666', style='italic',
        bbox=dict(boxstyle='round,pad=5', facecolor='white', alpha=0.7, edgecolor='#999'))

ax.text(500, 750, 'REMOTE\nEAST', ha='center', va='center',
        fontsize=10, fontweight='bold', color='#666', style='italic',
        bbox=dict(boxstyle='round,pad=5', facecolor='white', alpha=0.7, edgecolor='#999'))

ax.text(1200, 750, 'REMOTE\nWEST', ha='center', va='center',
        fontsize=10, fontweight='bold', color='#666', style='italic',
        bbox=dict(boxstyle='round,pad=5', facecolor='white', alpha=0.7, edgecolor='#999'))

ax.text(800, 200, 'SOUTH APRON\n(SA01-SA30)', ha='center', va='center',
        fontsize=10, fontweight='bold', color='#666', style='italic',
        bbox=dict(boxstyle='round,pad=5', facecolor='white', alpha=0.7, edgecolor='#999'))

ax.text(1650, 240, 'NORTH\nSOUTH APRON\n(NSA01-NSA15)', ha='center', va='center',
        fontsize=9, fontweight='bold', color='#666', style='italic',
        bbox=dict(boxstyle='round,pad=5', facecolor='white', alpha=0.7, edgecolor='#999'))

ax.text(1400, 970, 'MAIN APRON\n(A0-A3, B1-B13)', ha='center', va='center',
        fontsize=10, fontweight='bold', color='#666', style='italic',
        bbox=dict(boxstyle='round,pad=5', facecolor='white', alpha=0.7, edgecolor='#999'))

ax.text(1751, 520, 'HANGAR', ha='center', va='center',
        fontsize=9, fontweight='bold', color='#666', style='italic',
        bbox=dict(boxstyle='round,pad=5', facecolor='white', alpha=0.7, edgecolor='#999'))

plt.tight_layout()
plt.savefig('reports/thesis_accurate_apron_wireframe.png', dpi=150, bbox_inches='tight', facecolor='white')
print("OK - Accurate apron wireframe generated")
print(f"Total stands rendered: {len(STANDS)}")
print(f"- Available: {len(AVAILABLE_STANDS)}")
print(f"- Occupied: {len(OCCUPIED_STANDS)}")
print(f"- RON: {len(RON_STANDS)}")
print(f"- Reserved: {len(STANDS) - len(AVAILABLE_STANDS) - len(OCCUPIED_STANDS) - len(RON_STANDS)}")

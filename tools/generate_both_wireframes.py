"""
Generate two accurate wireframe sketches:
1. Dashboard page (dashboard/index.php)
2. Apron map page (apron/index.php) with exact stand coordinates
"""

import matplotlib.pyplot as plt
import matplotlib.patches as mpatches
from matplotlib.patches import FancyBboxPatch, Rectangle
import matplotlib.lines as mlines

# ============================================================================
# WIREFRAME 1: Dashboard Page
# ============================================================================
def generate_dashboard_wireframe():
    fig, ax = plt.subplots(figsize=(18, 12))
    ax.set_xlim(0, 14)
    ax.set_ylim(0, 16)
    ax.axis('off')
    ax.invert_yaxis()

    fig.suptitle('Rancangan Dashboard AMC\n(Wireframe/Proposal Sketch)',
                 fontsize=16, fontweight='bold', y=0.97)

    # Background
    bg = Rectangle((0.2, 0.2), 13.6, 15.6, facecolor='#F9F7F7', edgecolor='#112D4E', linewidth=2)
    ax.add_patch(bg)

    # Title
    title_box = FancyBboxPatch((1, 0.5), 12, 0.8, boxstyle="round,pad=0.1",
                               edgecolor='none', facecolor='white', linewidth=0)
    ax.add_patch(title_box)
    ax.text(7, 0.9, 'Aircraft Movement Control Dashboard',
            ha='center', va='center', fontsize=14, fontweight='bold', color='#112D4E')

    # ========================================================================
    # ROW 1: Three KPI Cards (Live Apron, Movements, Model Performance)
    # ========================================================================
    row1_y = 1.8
    card_height = 3.2

    # Card 1: Live Apron Status
    card1 = FancyBboxPatch((0.5, row1_y), 4.2, card_height, boxstyle="round,pad=0.1",
                           edgecolor='#DBE2EF', facecolor='#FFFFFF', linewidth=2)
    ax.add_patch(card1)

    # Card header
    header1 = Rectangle((0.5, row1_y), 4.2, 0.5, facecolor='#E3F2FD', edgecolor='none')
    ax.add_patch(header1)
    ax.text(2.6, row1_y + 0.25, 'Live Apron Status', ha='center', va='center',
            fontsize=10, fontweight='bold', color='#112D4E')

    # Card content - 2x2 grid
    metrics = [
        ('Total Stands', '84', '#112D4E', 1.0, row1_y + 1.0),
        ('Available', '45', '#4CAF50', 3.2, row1_y + 1.0),
        ('Occupied', '24', '#F44336', 1.0, row1_y + 1.9),
        ('Live RON', '15', '#FFC107', 3.2, row1_y + 1.9),
    ]

    for label, value, color, x, y in metrics:
        ax.text(x, y, label, ha='center', va='top',
                fontsize=7, color='#666')
        ax.text(x, y + 0.4, value, ha='center', va='center',
                fontsize=16, fontweight='bold', color=color)

    # Buttons
    btn1 = Rectangle((0.7, row1_y + 2.6), 1.8, 0.4, facecolor='#3F72AF', edgecolor='#112D4E', linewidth=1)
    ax.add_patch(btn1)
    ax.text(1.6, row1_y + 2.8, 'Set RON', ha='center', va='center',
            fontsize=8, color='white', fontweight='bold')

    btn2 = Rectangle((2.6, row1_y + 2.6), 1.8, 0.4, facecolor='white', edgecolor='#3F72AF', linewidth=1)
    ax.add_patch(btn2)
    ax.text(3.5, row1_y + 2.8, 'Refresh', ha='center', va='center',
            fontsize=8, color='#3F72AF', fontweight='bold')

    # Card 2: Movements Snapshot
    card2 = FancyBboxPatch((5, row1_y), 4.2, card_height, boxstyle="round,pad=0.1",
                           edgecolor='#DBE2EF', facecolor='#FFFFFF', linewidth=2)
    ax.add_patch(card2)

    header2 = Rectangle((5, row1_y), 4.2, 0.5, facecolor='#E3F2FD', edgecolor='none')
    ax.add_patch(header2)
    ax.text(7.1, row1_y + 0.25, 'Movements Snapshot (Today)', ha='center', va='center',
            fontsize=10, fontweight='bold', color='#112D4E')

    # Arrivals/Departures boxes
    arr_box = FancyBboxPatch((5.2, row1_y + 0.7), 1.9, 2.0, boxstyle="round,pad=0.05",
                             edgecolor='#E0E0E0', facecolor='#FAFAFA', linewidth=1)
    ax.add_patch(arr_box)
    ax.text(6.15, row1_y + 0.85, 'ARRIVALS', ha='center', va='top',
            fontsize=7, fontweight='bold', color='#666')

    # Commercial/Cargo/Charter
    categories = [
        ('Commercial', '12', '#2196F3', row1_y + 1.3),
        ('Cargo', '8', '#009688', row1_y + 1.8),
        ('Charter', '5', '#9C27B0', row1_y + 2.3),
    ]
    for label, val, color, y in categories:
        ax.text(5.5, y, label, ha='left', va='center', fontsize=6, color='#666')
        ax.text(7.0, y, val, ha='right', va='center', fontsize=11, fontweight='bold', color=color)

    dep_box = FancyBboxPatch((7.2, row1_y + 0.7), 1.9, 2.0, boxstyle="round,pad=0.05",
                             edgecolor='#E0E0E0', facecolor='#FAFAFA', linewidth=1)
    ax.add_patch(dep_box)
    ax.text(8.15, row1_y + 0.85, 'DEPARTURES', ha='center', va='top',
            fontsize=7, fontweight='bold', color='#666')

    for label, val, color, y in [('Commercial', '15', '#2196F3', row1_y + 1.3),
                                  ('Cargo', '6', '#009688', row1_y + 1.8),
                                  ('Charter', '4', '#9C27B0', row1_y + 2.3)]:
        ax.text(7.5, y, label, ha='left', va='center', fontsize=6, color='#666')
        ax.text(9.0, y, val, ha='right', va='center', fontsize=11, fontweight='bold', color=color)

    # Card 3: Model Performance
    card3 = FancyBboxPatch((9.5, row1_y), 4.2, card_height, boxstyle="round,pad=0.1",
                           edgecolor='#DBE2EF', facecolor='#FFFFFF', linewidth=2)
    ax.add_patch(card3)

    header3 = Rectangle((9.5, row1_y), 4.2, 0.5, facecolor='#E3F2FD', edgecolor='none')
    ax.add_patch(header3)
    ax.text(11.6, row1_y + 0.25, 'Model Performance Snapshot', ha='center', va='center',
            fontsize=10, fontweight='bold', color='#112D4E')

    ml_metrics = [
        ('Model Version', 'v2.1', '#112D4E', 10.3, row1_y + 1.0),
        ('Training Date', '2025-01', '#112D4E', 12.9, row1_y + 1.0),
        ('Expected Top-3', '85%', '#4CAF50', 10.3, row1_y + 1.9),
        ('Observed Top-3', '82%', '#FFC107', 12.9, row1_y + 1.9),
    ]

    for label, value, color, x, y in ml_metrics:
        ax.text(x, y, label, ha='center', va='top',
                fontsize=7, color='#666')
        ax.text(x, y + 0.4, value, ha='center', va='center',
                fontsize=13, fontweight='bold', color=color)

    # Predictions logged box
    pred_box = FancyBboxPatch((9.7, row1_y + 2.5), 3.8, 0.5, boxstyle="round,pad=0.05",
                              edgecolor='#E0E0E0', facecolor='#FAFAFA', linewidth=1)
    ax.add_patch(pred_box)
    ax.text(11.6, row1_y + 2.65, 'Predictions Logged: 1,248', ha='center', va='top',
            fontsize=7, fontweight='bold', color='#112D4E')

    # ========================================================================
    # ROW 2: Movement by Hour Table + Recent Predictions
    # ========================================================================
    row2_y = 5.5

    # Movement by Hour table (wider)
    table_box = FancyBboxPatch((0.5, row2_y), 8.7, 4.5, boxstyle="round,pad=0.1",
                               edgecolor='#DBE2EF', facecolor='#FFFFFF', linewidth=2)
    ax.add_patch(table_box)

    table_header = Rectangle((0.5, row2_y), 8.7, 0.5, facecolor='#E3F2FD', edgecolor='none')
    ax.add_patch(table_header)
    ax.text(4.85, row2_y + 0.25, 'Apron Movement by Hour', ha='center', va='center',
            fontsize=10, fontweight='bold', color='#112D4E')

    # Table mock
    table_content = Rectangle((0.7, row2_y + 0.7), 8.3, 3.0, facecolor='#FAFAFA', edgecolor='#CCC', linewidth=1)
    ax.add_patch(table_content)

    # Table headers
    headers = ['TIME', 'Arrivals', 'Departures', 'Total']
    header_x = [1.5, 3.5, 5.5, 7.5]
    for h, x in zip(headers, header_x):
        ax.text(x, row2_y + 1.0, h, ha='center', va='center',
                fontsize=7, fontweight='bold', color='#666')

    # Sample rows
    rows = [
        ('06:00-07:00', '2', '1', '3'),
        ('07:00-08:00', '8', '3', '11'),
        ('08:00-09:00', '12', '6', '18'),
        ('...', '...', '...', '...'),
    ]
    row_y = row2_y + 1.4
    for row_data in rows:
        for val, x in zip(row_data, header_x):
            ax.text(x, row_y, val, ha='center', va='center', fontsize=6, color='#333')
        row_y += 0.35

    # Peak hours summary
    peak_box = FancyBboxPatch((0.7, row2_y + 3.8), 8.3, 0.6, boxstyle="round,pad=0.05",
                              edgecolor='#3F72AF', facecolor='#F0F4F8', linewidth=2)
    ax.add_patch(peak_box)
    ax.text(4.85, row2_y + 4.1, 'Peak Hours: 08:00-09:00 (18 movements) | 14:00-15:00 (16 movements)',
            ha='center', va='center', fontsize=7, color='#112D4E')

    # Recent Prediction Outcomes
    pred_panel = FancyBboxPatch((9.5, row2_y), 4.2, 4.5, boxstyle="round,pad=0.1",
                                edgecolor='#DBE2EF', facecolor='#FFFFFF', linewidth=2)
    ax.add_patch(pred_panel)

    pred_header = Rectangle((9.5, row2_y), 4.2, 0.5, facecolor='#E3F2FD', edgecolor='none')
    ax.add_patch(pred_header)
    ax.text(11.6, row2_y + 0.25, 'Recent Prediction Outcomes', ha='center', va='center',
            fontsize=10, fontweight='bold', color='#112D4E')

    # Recent logs
    logs = [
        ('PK-ABC -> A2', 'Match rank #1 (85%)', '#4CAF50'),
        ('PK-DEF -> B5', 'Match rank #2 (12%)', '#8BC34A'),
        ('PK-GHI -> B10', 'Manual override', '#9E9E9E'),
        ('PK-JKL -> A3', 'Match rank #1 (92%)', '#4CAF50'),
    ]
    log_y = row2_y + 1.0
    for aircraft, result, color in logs:
        log_item = Rectangle((9.7, log_y), 3.8, 0.7, facecolor='#FAFAFA', edgecolor='#E0E0E0', linewidth=1)
        ax.add_patch(log_item)
        ax.text(9.9, log_y + 0.2, aircraft, ha='left', va='top',
                fontsize=7, fontweight='bold', color='#112D4E')
        ax.text(9.9, log_y + 0.5, result, ha='left', va='top',
                fontsize=6, color=color)
        log_y += 0.85

    # ========================================================================
    # ROW 3: Automated Reporting Suite
    # ========================================================================
    row3_y = 10.5

    report_box = FancyBboxPatch((0.5, row3_y), 13.2, 2.5, boxstyle="round,pad=0.1",
                                edgecolor='#DBE2EF', facecolor='#FFFFFF', linewidth=2)
    ax.add_patch(report_box)

    report_header = Rectangle((0.5, row3_y), 13.2, 0.5, facecolor='#E3F2FD', edgecolor='none')
    ax.add_patch(report_header)
    ax.text(7.1, row3_y + 0.25, 'Automated Reporting Suite', ha='center', va='center',
            fontsize=10, fontweight='bold', color='#112D4E')

    # Form fields
    form_y = row3_y + 0.9
    fields = [
        'Report Type',
        'From Date',
        'To Date',
        'Category',
        ''
    ]
    field_x = 0.7
    for i, field in enumerate(fields):
        if field:
            ax.text(field_x + i * 2.5, form_y, field, ha='left', va='top',
                    fontsize=7, color='#666', fontweight='bold')
            field_box = Rectangle((field_x + i * 2.5, form_y + 0.25), 2.2, 0.35,
                                 facecolor='white', edgecolor='#CCC', linewidth=1)
            ax.add_patch(field_box)
            ax.text(field_x + i * 2.5 + 1.1, form_y + 0.425, '[__________]',
                    ha='center', va='center', fontsize=6, color='#CCC', style='italic')

    # Generate button
    gen_btn = Rectangle((11.5, form_y + 0.15), 2.0, 0.5, facecolor='#3F72AF', edgecolor='#112D4E', linewidth=2)
    ax.add_patch(gen_btn)
    ax.text(12.5, form_y + 0.4, 'Generate Report', ha='center', va='center',
            fontsize=8, color='white', fontweight='bold')

    # Output preview
    output_box = Rectangle((0.7, form_y + 0.9), 12.8, 1.0, facecolor='#FAFAFA', edgecolor='#CCC', linewidth=1)
    ax.add_patch(output_box)
    ax.text(7.1, form_y + 1.4, 'Report preview will appear here...',
            ha='center', va='center', fontsize=7, color='#999', style='italic')

    # Note
    ax.text(7, 15.5, 'Catatan: Ini adalah wireframe/rancangan antarmuka dashboard',
            ha='center', va='center', fontsize=7, style='italic', color='#999')

    plt.tight_layout()
    plt.savefig('reports/thesis_dashboard_accurate_wireframe.png', dpi=200, bbox_inches='tight', facecolor='white')
    print("OK - Dashboard wireframe generated")

# ============================================================================
# WIREFRAME 2: Apron Map (using exact coordinates from index.php)
# ============================================================================
def generate_apron_wireframe():
    # Exact stand coordinates from index.php
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

    # Status coloring for demo
    AVAILABLE = ['A0', 'A1', 'A2', 'B1', 'B5', 'SA01', 'NSA01', 'RW01']
    OCCUPIED = ['B2', 'B6', 'SA02', 'RW02']
    RON = ['B3', 'SA03']

    fig = plt.figure(figsize=(19.2, 10.8))
    ax = fig.add_subplot(111)
    ax.set_xlim(0, 1920)
    ax.set_ylim(0, 1080)
    ax.set_aspect('equal')
    ax.axis('off')
    ax.invert_yaxis()

    fig.suptitle('Rancangan Peta Apron AMC dengan Koordinat Asli\n(Wireframe/Proposal Sketch)',
                 fontsize=16, fontweight='bold', y=0.97)

    # Background
    bg = Rectangle((0, 0), 1920, 1080, facecolor='#F5F5F5', edgecolor='none')
    ax.add_patch(bg)

    # Title overlay
    ax.text(960, 40, 'APRON MAP (1920x1080 Canvas)', ha='center', va='center',
            fontsize=13, fontweight='bold', color='#112D4E',
            bbox=dict(boxstyle='round,pad=0.6', facecolor='#DBE2EF', edgecolor='#3F72AF', linewidth=2))

    # Draw all stands
    for stand_code, (x, y) in STANDS.items():
        if stand_code in AVAILABLE:
            color = '#4CAF50'
        elif stand_code in OCCUPIED:
            color = '#F44336'
        elif stand_code in RON:
            color = '#FFC107'
        else:
            color = '#9E9E9E'

        stand_box = FancyBboxPatch((x - 30, y - 15), 60, 30,
                                   boxstyle="round,pad=2",
                                   edgecolor='#112D4E',
                                   facecolor=color,
                                   linewidth=1.5)
        ax.add_patch(stand_box)
        ax.text(x, y, stand_code, ha='center', va='center',
                fontsize=7, fontweight='bold', color='white')

    # Legend
    legend_y = 1020
    ax.text(50, legend_y, 'STATUS:', ha='left', va='center',
            fontsize=10, fontweight='bold', color='#112D4E')

    for label, color, x_pos in [('Available', '#4CAF50', 150),
                                 ('Occupied', '#F44336', 300),
                                 ('RON', '#FFC107', 450),
                                 ('Reserved', '#9E9E9E', 600)]:
        legend_box = Rectangle((x_pos, legend_y - 10), 20, 20,
                               facecolor=color, edgecolor='#112D4E', linewidth=1)
        ax.add_patch(legend_box)
        ax.text(x_pos + 30, legend_y, label, ha='left', va='center',
                fontsize=9, color='#112D4E')

    # Note
    ax.text(960, 1060, 'Catatan: Koordinat stand sesuai dengan apron/index.php (84 total stands)',
            ha='center', va='center', fontsize=8, style='italic', color='#999')

    plt.tight_layout()
    plt.savefig('reports/thesis_apron_accurate_wireframe.png', dpi=150, bbox_inches='tight', facecolor='white')
    print("OK - Apron wireframe generated")
    print(f"Total stands: {len(STANDS)}")

# ============================================================================
# Generate both wireframes
# ============================================================================
if __name__ == '__main__':
    print("Generating accurate wireframes...")
    generate_dashboard_wireframe()
    generate_apron_wireframe()
    print("\nBoth wireframes completed!")
    print("- reports/thesis_dashboard_accurate_wireframe.png")
    print("- reports/thesis_apron_accurate_wireframe.png")

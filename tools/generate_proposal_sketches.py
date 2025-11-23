"""
Generate proposal sketches/wireframes for thesis Chapter 2 (Proposal stage)
These are simple wireframe mockups, not actual screenshots
"""

import matplotlib.pyplot as plt
import matplotlib.patches as mpatches
from matplotlib.patches import FancyBboxPatch, Rectangle, Circle, FancyArrowPatch
import matplotlib.lines as mlines

# ============================================================================
# GAMBAR 1: Architecture Diagram - PHP ↔ Python ML Communication
# ============================================================================
def generate_architecture_diagram():
    fig, ax = plt.subplots(figsize=(16, 10))
    ax.set_xlim(0, 12)
    ax.set_ylim(0, 10)
    ax.axis('off')

    fig.suptitle('Diagram Arsitektur Sistem AMC\nKomunikasi antara Backend PHP dan Python ML Script',
                 fontsize=16, fontweight='bold', y=0.96)

    # ========================================================================
    # Layer 1: Client/Browser (Top)
    # ========================================================================
    browser_box = FancyBboxPatch(
        (4, 8.5), 4, 1,
        boxstyle="round,pad=0.1",
        edgecolor='#2196F3',
        facecolor='#E3F2FD',
        linewidth=3
    )
    ax.add_patch(browser_box)
    ax.text(6, 9.2, '🌐 CLIENT (Browser)', ha='center', va='center',
            fontsize=12, fontweight='bold')
    ax.text(6, 8.8, 'HTML/CSS/JavaScript\n(Tailwind CSS, Vanilla JS)', ha='center', va='center',
            fontsize=9, style='italic')

    # ========================================================================
    # Arrow: HTTP Request
    # ========================================================================
    arrow1 = FancyArrowPatch(
        (6, 8.4), (6, 7.6),
        arrowstyle='<->,head_width=0.4,head_length=0.3',
        color='#FF9800',
        linewidth=2.5
    )
    ax.add_patch(arrow1)
    ax.text(7.5, 8, 'HTTP Request/Response', ha='left', va='center',
            fontsize=9, bbox=dict(boxstyle='round', facecolor='#FFF3E0'))

    # ========================================================================
    # Layer 2: PHP Backend (Middle-Top)
    # ========================================================================
    php_box = FancyBboxPatch(
        (3.5, 6.2), 5, 1.3,
        boxstyle="round,pad=0.1",
        edgecolor='#673AB7',
        facecolor='#EDE7F6',
        linewidth=3
    )
    ax.add_patch(php_box)
    ax.text(6, 7.2, '⚙️ PHP BACKEND (MVC Pattern)', ha='center', va='center',
            fontsize=12, fontweight='bold', color='#4527A0')
    ax.text(6, 6.85, 'Controller: ApronController.php', ha='center', va='center',
            fontsize=9)
    ax.text(6, 6.55, 'Model: AircraftMovementRepository, StandRepository', ha='center', va='center',
            fontsize=8, style='italic')

    # ========================================================================
    # Arrow: proc_open() call
    # ========================================================================
    arrow2 = FancyArrowPatch(
        (6, 6.1), (3, 4.5),
        arrowstyle='<->,head_width=0.4,head_length=0.3',
        color='#E91E63',
        linewidth=2.5,
        linestyle='--'
    )
    ax.add_patch(arrow2)

    proc_open_box = FancyBboxPatch(
        (3.2, 5.1), 2.5, 0.8,
        boxstyle="round,pad=0.05",
        edgecolor='#E91E63',
        facecolor='#FCE4EC',
        linewidth=2
    )
    ax.add_patch(proc_open_box)
    ax.text(4.45, 5.6, 'proc_open()', ha='center', va='center',
            fontsize=10, fontweight='bold', color='#C2185B')
    ax.text(4.45, 5.3, 'JSON payload via stdin', ha='center', va='center',
            fontsize=7, style='italic')

    # ========================================================================
    # Layer 3: Python ML Script (Middle-Bottom Left)
    # ========================================================================
    python_box = FancyBboxPatch(
        (0.5, 3.2), 4, 1.2,
        boxstyle="round,pad=0.1",
        edgecolor='#4CAF50',
        facecolor='#E8F5E9',
        linewidth=3
    )
    ax.add_patch(python_box)
    ax.text(2.5, 4.1, '🐍 PYTHON ML MODULE', ha='center', va='center',
            fontsize=11, fontweight='bold', color='#2E7D32')
    ax.text(2.5, 3.75, 'ml/predict.py', ha='center', va='center',
            fontsize=9, family='monospace')
    ax.text(2.5, 3.45, 'Random Forest Model (scikit-learn)', ha='center', va='center',
            fontsize=8, style='italic')

    # Model files
    model_box = FancyBboxPatch(
        (0.7, 2.2), 3.6, 0.8,
        boxstyle="round,pad=0.05",
        edgecolor='#388E3C',
        facecolor='#C8E6C9',
        linewidth=1.5,
        linestyle=':'
    )
    ax.add_patch(model_box)
    ax.text(2.5, 2.75, '📁 parking_stand_model_rf_redo.pkl', ha='center', va='center',
            fontsize=8, family='monospace')
    ax.text(2.5, 2.45, '📁 encoders_redo.pkl', ha='center', va='center',
            fontsize=8, family='monospace')

    # ========================================================================
    # Arrow: SQL queries
    # ========================================================================
    arrow3 = FancyArrowPatch(
        (8, 6.8), (9.5, 4.5),
        arrowstyle='<->,head_width=0.4,head_length=0.3',
        color='#00BCD4',
        linewidth=2.5
    )
    ax.add_patch(arrow3)
    ax.text(9.5, 5.7, 'SQL Query\n(PDO)', ha='center', va='center',
            fontsize=9, bbox=dict(boxstyle='round', facecolor='#E0F7FA'))

    # ========================================================================
    # Layer 4: MySQL Database (Middle-Bottom Right)
    # ========================================================================
    db_box = FancyBboxPatch(
        (7.5, 3.2), 4, 1.2,
        boxstyle="round,pad=0.1",
        edgecolor='#FF5722',
        facecolor='#FBE9E7',
        linewidth=3
    )
    ax.add_patch(db_box)
    ax.text(9.5, 4.1, '🗄️ MySQL DATABASE', ha='center', va='center',
            fontsize=11, fontweight='bold', color='#D84315')
    ax.text(9.5, 3.75, 'amc_db', ha='center', va='center',
            fontsize=9, family='monospace')
    ax.text(9.5, 3.45, 'Tables: aircraft_movements, parking_stands,\nusers, flight_references',
            ha='center', va='center', fontsize=7, style='italic')

    # ========================================================================
    # Data Flow Sequence (Bottom)
    # ========================================================================
    flow_y = 1.2
    ax.text(6, flow_y + 0.5, '📋 ALUR KOMUNIKASI DATA:', ha='center', va='center',
            fontsize=11, fontweight='bold',
            bbox=dict(boxstyle='round', facecolor='#FFF9C4', edgecolor='black', linewidth=2))

    flow_steps = [
        '1️⃣ User input → PHP Controller',
        '2️⃣ PHP → proc_open() → Python script (JSON payload)',
        '3️⃣ Python → Load RF model → Predict stand',
        '4️⃣ Python → Return JSON (top-3 predictions)',
        '5️⃣ PHP → Save to MySQL → Return HTML',
        '6️⃣ Browser → Display result'
    ]

    flow_text = '\n'.join(flow_steps)
    ax.text(6, flow_y - 0.5, flow_text, ha='center', va='top',
            fontsize=8, family='monospace',
            bbox=dict(boxstyle='round', facecolor='white', edgecolor='#999', linewidth=1))

    plt.tight_layout()
    plt.savefig('reports/thesis_architecture_diagram.png', dpi=300, bbox_inches='tight', facecolor='white')
    print("✓ GAMBAR 1: Architecture diagram saved")

# ============================================================================
# GAMBAR 2: Dashboard Wireframe Sketch
# ============================================================================
def generate_dashboard_sketch():
    fig, ax = plt.subplots(figsize=(16, 10))
    ax.set_xlim(0, 12)
    ax.set_ylim(0, 10)
    ax.axis('off')

    fig.suptitle('Rancangan Dashboard AMC dengan Peta Apron dan Menu Interaktif\n(Wireframe/Sketch)',
                 fontsize=16, fontweight='bold', y=0.96)

    # Main container
    main_box = Rectangle((0.5, 0.5), 11, 8.5,
                         edgecolor='black', facecolor='white', linewidth=3)
    ax.add_patch(main_box)

    # ========================================================================
    # Header/Navigation Bar
    # ========================================================================
    header = Rectangle((0.5, 8.5), 11, 0.5,
                       edgecolor='black', facecolor='#E0E0E0', linewidth=2)
    ax.add_patch(header)
    ax.text(1, 8.75, '🏢 AMC - Apron Movement Control', ha='left', va='center',
            fontsize=11, fontweight='bold')
    ax.text(10.5, 8.75, '👤 User', ha='center', va='center',
            fontsize=9, bbox=dict(boxstyle='round', facecolor='white'))

    # ========================================================================
    # Sidebar Menu (Left)
    # ========================================================================
    sidebar = Rectangle((0.5, 0.5), 2, 8,
                        edgecolor='black', facecolor='#F5F5F5', linewidth=2)
    ax.add_patch(sidebar)

    menu_items = [
        ('📊', 'Dashboard'),
        ('🗺️', 'Apron Map'),
        ('✈️', 'Movements'),
        ('📋', 'Master Table'),
        ('📊', 'Reports'),
        ('⚙️', 'Settings')
    ]

    menu_y = 7.5
    for icon, label in menu_items:
        menu_btn = Rectangle((0.6, menu_y), 1.8, 0.5,
                             edgecolor='#999', facecolor='white', linewidth=1)
        ax.add_patch(menu_btn)
        ax.text(1.5, menu_y + 0.25, f'{icon} {label}', ha='center', va='center',
                fontsize=8)
        menu_y -= 0.7

    # ========================================================================
    # Main Content Area - Apron Map (Center-Right)
    # ========================================================================
    content_box = Rectangle((2.6, 4), 8.8, 4.4,
                            edgecolor='#666', facecolor='#FAFAFA', linewidth=2)
    ax.add_patch(content_box)

    ax.text(6.5, 8.2, '🗺️ PETA APRON INTERAKTIF', ha='center', va='center',
            fontsize=12, fontweight='bold',
            bbox=dict(boxstyle='round', facecolor='#E3F2FD'))

    # Draw simplified apron map with stands
    # Runway
    runway = Rectangle((3, 4.5), 8, 0.5,
                       edgecolor='#333', facecolor='#BDBDBD', linewidth=2)
    ax.add_patch(runway)
    ax.text(7, 4.75, 'RUNWAY', ha='center', va='center',
            fontsize=9, fontweight='bold', style='italic')

    # Parking stands (simplified grid)
    stand_positions = [
        # Right side (Commercial)
        (9, 5.5, 'A1', '#4CAF50'),
        (9.7, 5.5, 'A2', '#4CAF50'),
        (10.4, 5.5, 'A3', '#4CAF50'),
        (9, 6.3, 'A4', '#FFC107'),
        (9.7, 6.3, 'A5', '#9E9E9E'),
        (10.4, 6.3, 'A6', '#9E9E9E'),
        (9, 7.1, 'A7', '#9E9E9E'),
        (9.7, 7.1, 'A8', '#9E9E9E'),

        # Middle (Charter/VIP)
        (6.5, 5.5, 'B1', '#4CAF50'),
        (7.2, 5.5, 'B2', '#9E9E9E'),
        (6.5, 6.3, 'B3', '#F44336'),
        (7.2, 6.3, 'B4', '#9E9E9E'),

        # Left side (Cargo)
        (3.5, 5.5, 'C1', '#4CAF50'),
        (4.2, 5.5, 'C2', '#9E9E9E'),
        (3.5, 6.3, 'C3', '#4CAF50'),
        (4.2, 6.3, 'C4', '#9E9E9E'),

        # A0 (Special small aircraft)
        (5.5, 7.1, 'A0', '#2196F3'),
    ]

    for x, y, label, color in stand_positions:
        stand = Rectangle((x, y), 0.6, 0.6,
                         edgecolor='black', facecolor=color, linewidth=1.5)
        ax.add_patch(stand)
        ax.text(x + 0.3, y + 0.3, label, ha='center', va='center',
                fontsize=7, fontweight='bold', color='white')

    # Legend
    legend_y = 7.8
    ax.text(3.5, legend_y, 'Status:', ha='left', va='center', fontsize=8, fontweight='bold')

    # Legend items
    legends = [
        (4.5, '🟢 Available', '#4CAF50'),
        (5.5, '🟡 Occupied', '#FFC107'),
        (6.5, '🔴 RON', '#F44336'),
        (7.5, '⚪ Reserved', '#9E9E9E'),
    ]

    for x, text, color in legends:
        circ = Circle((x, legend_y), 0.08, color=color, edgecolor='black')
        ax.add_patch(circ)
        ax.text(x + 0.15, legend_y, text.split(' ')[1], ha='left', va='center', fontsize=7)

    # ========================================================================
    # Bottom Panel - Controls & Info
    # ========================================================================
    control_panel = Rectangle((2.6, 0.5), 8.8, 3.4,
                              edgecolor='#666', facecolor='#FAFAFA', linewidth=2)
    ax.add_patch(control_panel)

    ax.text(6.5, 3.7, '📋 PANEL KONTROL & INPUT DATA', ha='center', va='center',
            fontsize=11, fontweight='bold',
            bbox=dict(boxstyle='round', facecolor='#FFF9C4'))

    # Input fields (wireframe)
    input_y = 3.2
    input_labels = [
        'Registration:',
        'Aircraft Type:',
        'Airline:',
        'Category:',
    ]

    for i, label in enumerate(input_labels):
        x_offset = 3 + (i % 2) * 4
        y_offset = input_y - (i // 2) * 0.6

        ax.text(x_offset, y_offset, label, ha='left', va='center', fontsize=8)
        input_field = Rectangle((x_offset + 1.3, y_offset - 0.15), 2, 0.3,
                                edgecolor='#999', facecolor='white', linewidth=1)
        ax.add_patch(input_field)
        ax.text(x_offset + 2.3, y_offset, '[___________]', ha='center', va='center',
                fontsize=7, color='#CCC', style='italic')

    # Predict button
    predict_btn = Rectangle((3, 1.5), 2.5, 0.5,
                            edgecolor='#1976D2', facecolor='#2196F3', linewidth=2)
    ax.add_patch(predict_btn)
    ax.text(4.25, 1.75, '🤖 PREDICT STAND', ha='center', va='center',
            fontsize=9, fontweight='bold', color='white')

    # Save button
    save_btn = Rectangle((5.8, 1.5), 2.5, 0.5,
                         edgecolor='#388E3C', facecolor='#4CAF50', linewidth=2)
    ax.add_patch(save_btn)
    ax.text(7.05, 1.75, '💾 SAVE DATA', ha='center', va='center',
            fontsize=9, fontweight='bold', color='white')

    # Clear button
    clear_btn = Rectangle((8.6, 1.5), 2.5, 0.5,
                          edgecolor='#C62828', facecolor='#EF5350', linewidth=2)
    ax.add_patch(clear_btn)
    ax.text(9.85, 1.75, '🗑️ CLEAR', ha='center', va='center',
            fontsize=9, fontweight='bold', color='white')

    # Prediction result area
    result_box = Rectangle((3, 0.7), 8, 0.6,
                           edgecolor='#FF9800', facecolor='#FFF3E0', linewidth=2)
    ax.add_patch(result_box)
    ax.text(3.2, 1.15, '📊 Recommendation:', ha='left', va='center',
            fontsize=8, fontweight='bold')
    ax.text(7, 1.15, '1st: A2 (85%) | 2nd: A3 (10%) | 3rd: A1 (5%)', ha='center', va='center',
            fontsize=8, family='monospace', style='italic', color='#666')

    # Note at bottom
    ax.text(6.5, 0.3, 'Catatan: Ini adalah wireframe/rancangan antarmuka sistem',
            ha='center', va='center', fontsize=8, style='italic', color='#999')

    plt.tight_layout()
    plt.savefig('reports/thesis_dashboard_wireframe.png', dpi=300, bbox_inches='tight', facecolor='white')
    print("✓ GAMBAR 2: Dashboard wireframe saved")

# ============================================================================
# GAMBAR 3: System Flowchart
# ============================================================================
def generate_system_flowchart():
    fig, ax = plt.subplots(figsize=(14, 12))
    ax.set_xlim(0, 10)
    ax.set_ylim(0, 14)
    ax.axis('off')

    fig.suptitle('Flowchart Alur Sistem AMC\nDari Input → Prediksi → Simpan Data → Tampilkan di Dashboard',
                 fontsize=16, fontweight='bold', y=0.97)

    # Start
    start_circle = Circle((5, 13), 0.4, color='#4CAF50', edgecolor='black', linewidth=2)
    ax.add_patch(start_circle)
    ax.text(5, 13, 'START', ha='center', va='center', fontsize=9, fontweight='bold', color='white')

    # Arrow
    ax.arrow(5, 12.5, 0, -0.4, head_width=0.2, head_length=0.1, fc='black', ec='black')

    # Step 1: User Input
    step1 = FancyBboxPatch((3, 11.2), 4, 0.8, boxstyle="round,pad=0.1",
                           edgecolor='#2196F3', facecolor='#E3F2FD', linewidth=2)
    ax.add_patch(step1)
    ax.text(5, 11.8, '1. User Input Data Pesawat', ha='center', va='center', fontsize=10, fontweight='bold')
    ax.text(5, 11.4, '(Registration, Type, Airline, Category)', ha='center', va='center', fontsize=8, style='italic')

    ax.arrow(5, 11.1, 0, -0.4, head_width=0.2, head_length=0.1, fc='black', ec='black')

    # Decision: Use ML Prediction?
    decision1 = mpatches.FancyBboxPatch((3, 9.5), 4, 0.9, boxstyle="round,pad=0.1",
                                        edgecolor='#FF9800', facecolor='#FFF3E0', linewidth=2,
                                        transform=ax.transData)
    ax.add_patch(decision1)
    ax.text(5, 10.2, '2. Klik "Predict Stand"?', ha='center', va='center', fontsize=10, fontweight='bold')
    ax.text(5, 9.8, '(Gunakan ML?)', ha='center', va='center', fontsize=8, style='italic')

    # Yes path
    ax.arrow(5, 9.4, 0, -0.4, head_width=0.2, head_length=0.1, fc='#4CAF50', ec='#4CAF50', linewidth=2)
    ax.text(5.5, 9.2, 'YA', ha='left', va='center', fontsize=8, fontweight='bold', color='#4CAF50')

    # Step 3: Call Python ML
    step3 = FancyBboxPatch((2.5, 7.8), 5, 1.0, boxstyle="round,pad=0.1",
                           edgecolor='#9C27B0', facecolor='#F3E5F5', linewidth=2)
    ax.add_patch(step3)
    ax.text(5, 8.6, '3. PHP → proc_open() → Python ML', ha='center', va='center', fontsize=10, fontweight='bold')
    ax.text(5, 8.3, 'ml/predict.py (Random Forest Model)', ha='center', va='center', fontsize=8, style='italic')
    ax.text(5, 8.0, 'Return: Top-3 stand recommendations + probability', ha='center', va='center', fontsize=7, color='#666')

    ax.arrow(5, 7.7, 0, -0.4, head_width=0.2, head_length=0.1, fc='black', ec='black')

    # Step 4: Display Recommendations
    step4 = FancyBboxPatch((3, 6.5), 4, 0.8, boxstyle="round,pad=0.1",
                           edgecolor='#00BCD4', facecolor='#E0F7FA', linewidth=2)
    ax.add_patch(step4)
    ax.text(5, 7.1, '4. Tampilkan Rekomendasi', ha='center', va='center', fontsize=10, fontweight='bold')
    ax.text(5, 6.7, '(A2: 85%, A3: 10%, A1: 5%)', ha='center', va='center', fontsize=8, family='monospace')

    # No path (manual input)
    ax.arrow(7, 10, 1.5, 0, head_width=0.2, head_length=0.1, fc='#F44336', ec='#F44336', linewidth=2)
    ax.text(7.5, 10.3, 'TIDAK', ha='center', va='center', fontsize=8, fontweight='bold', color='#F44336')

    manual_box = FancyBboxPatch((8.5, 6.5), 1.3, 0.8, boxstyle="round,pad=0.05",
                                edgecolor='#F44336', facecolor='#FFEBEE', linewidth=1.5)
    ax.add_patch(manual_box)
    ax.text(9.15, 7, 'Manual\nInput\nStand', ha='center', va='center', fontsize=7)

    # Merge paths
    ax.arrow(9.15, 6.4, -2, -0.3, head_width=0.15, head_length=0.1, fc='#999', ec='#999', linewidth=1.5,
             linestyle='--')

    ax.arrow(5, 6.4, 0, -0.4, head_width=0.2, head_length=0.1, fc='black', ec='black')

    # Decision 2: Confirm & Save?
    decision2 = mpatches.FancyBboxPatch((3.2, 4.8), 3.6, 0.9, boxstyle="round,pad=0.1",
                                        edgecolor='#FF9800', facecolor='#FFF3E0', linewidth=2)
    ax.add_patch(decision2)
    ax.text(5, 5.5, '5. User Konfirmasi & Simpan?', ha='center', va='center', fontsize=10, fontweight='bold')
    ax.text(5, 5.1, '(Klik "Save Data")', ha='center', va='center', fontsize=8, style='italic')

    ax.arrow(5, 4.7, 0, -0.4, head_width=0.2, head_length=0.1, fc='#4CAF50', ec='#4CAF50', linewidth=2)
    ax.text(5.5, 4.5, 'YA', ha='left', va='center', fontsize=8, fontweight='bold', color='#4CAF50')

    # Step 6: Save to Database
    step6 = FancyBboxPatch((2.5, 3.2), 5, 0.9, boxstyle="round,pad=0.1",
                           edgecolor='#F44336', facecolor='#FFEBEE', linewidth=2)
    ax.add_patch(step6)
    ax.text(5, 3.9, '6. Simpan ke Database MySQL', ha='center', va='center', fontsize=10, fontweight='bold')
    ax.text(5, 3.55, 'INSERT INTO aircraft_movements (...)', ha='center', va='center', fontsize=8, family='monospace')
    ax.text(5, 3.3, 'Table: aircraft_movements, parking_stands', ha='center', va='center', fontsize=7, style='italic', color='#666')

    ax.arrow(5, 3.1, 0, -0.4, head_width=0.2, head_length=0.1, fc='black', ec='black')

    # Step 7: Update Dashboard
    step7 = FancyBboxPatch((2.5, 1.7), 5, 0.9, boxstyle="round,pad=0.1",
                           edgecolor='#673AB7', facecolor='#EDE7F6', linewidth=2)
    ax.add_patch(step7)
    ax.text(5, 2.4, '7. Update Dashboard Real-time', ha='center', va='center', fontsize=10, fontweight='bold')
    ax.text(5, 2.05, 'Refresh peta apron, update status stand,', ha='center', va='center', fontsize=8, style='italic')
    ax.text(5, 1.8, 'tampilkan aircraft movement log', ha='center', va='center', fontsize=8, style='italic')

    ax.arrow(5, 1.6, 0, -0.4, head_width=0.2, head_length=0.1, fc='black', ec='black')

    # End
    end_circle = Circle((5, 0.7), 0.4, color='#F44336', edgecolor='black', linewidth=2)
    ax.add_patch(end_circle)
    ax.text(5, 0.7, 'END', ha='center', va='center', fontsize=9, fontweight='bold', color='white')

    # Legend
    legend_box = FancyBboxPatch((0.3, 0.1), 2.5, 1.2, boxstyle="round,pad=0.05",
                                edgecolor='#666', facecolor='#F5F5F5', linewidth=1.5)
    ax.add_patch(legend_box)
    ax.text(1.55, 1.15, 'LEGEND:', ha='center', va='center', fontsize=8, fontweight='bold')

    legend_items = [
        ('🟦 Proses Input/Output', 0.95),
        ('🟨 Keputusan/Decision', 0.7),
        ('🟪 Proses ML/Database', 0.45),
        ('🟩 Alur Ya (Yes)', 0.2),
    ]

    for text, y_pos in legend_items:
        ax.text(1.55, y_pos, text, ha='center', va='center', fontsize=7)

    plt.tight_layout()
    plt.savefig('reports/thesis_system_flowchart.png', dpi=300, bbox_inches='tight', facecolor='white')
    print("✓ GAMBAR 3: System flowchart saved")

# ============================================================================
# Generate all diagrams
# ============================================================================
if __name__ == '__main__':
    print("="*80)
    print("GENERATING THESIS PROPOSAL DIAGRAMS (Chapter 2)")
    print("="*80)

    generate_architecture_diagram()
    generate_dashboard_sketch()
    generate_system_flowchart()

    print("\n" + "="*80)
    print("ALL DIAGRAMS GENERATED SUCCESSFULLY!")
    print("="*80)
    print("\nOutput files:")
    print("  1. reports/thesis_architecture_diagram.png")
    print("  2. reports/thesis_dashboard_wireframe.png")
    print("  3. reports/thesis_system_flowchart.png")
    print("\nThese are wireframe/proposal sketches suitable for Chapter 2 (Proposal)")
    print("="*80)

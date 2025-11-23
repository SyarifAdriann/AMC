"""
Generate two methodology diagrams:
1. CRISP-DM Process Diagram for AMC System
2. System Flowchart + Use Case Diagram
"""

import matplotlib.pyplot as plt
import matplotlib.patches as mpatches
from matplotlib.patches import FancyBboxPatch, Rectangle, Circle, FancyArrowPatch, Polygon
import matplotlib.lines as mlines

# ============================================================================
# DIAGRAM 1: CRISP-DM Process for AMC System
# ============================================================================
def generate_crisp_dm_diagram():
    fig, ax = plt.subplots(figsize=(16, 14))
    ax.set_xlim(0, 12)
    ax.set_ylim(0, 14)
    ax.axis('off')

    fig.suptitle('Diagram Proses CRISP-DM untuk Sistem Rekomendasi Stand AMC',
                 fontsize=16, fontweight='bold', y=0.96)

    # CRISP-DM is a circular/iterative process
    # We'll arrange it in a circular layout with arrows

    # Phase 1: Business Understanding (Top)
    phase1_y = 11.5
    phase1 = FancyBboxPatch((4, phase1_y), 4, 1.8, boxstyle="round,pad=0.15",
                            edgecolor='#1976D2', facecolor='#E3F2FD', linewidth=3)
    ax.add_patch(phase1)
    ax.text(6, phase1_y + 1.4, '1. BUSINESS UNDERSTANDING', ha='center', va='center',
            fontsize=11, fontweight='bold', color='#0D47A1')
    ax.text(6, phase1_y + 0.9, 'Pemahaman Bisnis', ha='center', va='center',
            fontsize=9, style='italic', color='#1565C0')

    business_text = '''• Tujuan: Otomasi alokasi stand pesawat
• Masalah: Alokasi manual tidak konsisten
• Kebutuhan: Rekomendasi top-3 stand
• Stakeholder: Petugas AMC Bandara'''
    ax.text(6, phase1_y + 0.25, business_text, ha='center', va='top',
            fontsize=7, color='#333', linespacing=1.5)

    # Phase 2: Data Understanding (Right)
    phase2_x = 9
    phase2_y = 8.5
    phase2 = FancyBboxPatch((phase2_x, phase2_y), 2.8, 1.8, boxstyle="round,pad=0.15",
                            edgecolor='#388E3C', facecolor='#E8F5E9', linewidth=3)
    ax.add_patch(phase2)
    ax.text(phase2_x + 1.4, phase2_y + 1.4, '2. DATA UNDERSTANDING', ha='center', va='center',
            fontsize=10, fontweight='bold', color='#1B5E20')
    ax.text(phase2_x + 1.4, phase2_y + 1.1, 'Pemahaman Data', ha='center', va='center',
            fontsize=8, style='italic', color='#2E7D32')

    data_text = '''• Sumber: 30+ Google Sheets
• Record: ~5000+ histori parkir
• Atribut: 11 kolom mentah
• Eksplorasi: Pola kategori,
  distribusi stand, frekuensi'''
    ax.text(phase2_x + 1.4, phase2_y + 0.3, data_text, ha='center', va='top',
            fontsize=6.5, color='#333', linespacing=1.4)

    # Phase 3: Data Preparation (Bottom Right)
    phase3_x = 8
    phase3_y = 5
    phase3 = FancyBboxPatch((phase3_x, phase3_y), 3.5, 1.8, boxstyle="round,pad=0.15",
                            edgecolor='#F57C00', facecolor='#FFF3E0', linewidth=3)
    ax.add_patch(phase3)
    ax.text(phase3_x + 1.75, phase3_y + 1.4, '3. DATA PREPARATION', ha='center', va='center',
            fontsize=10, fontweight='bold', color='#E65100')
    ax.text(phase3_x + 1.75, phase3_y + 1.1, 'Persiapan Data', ha='center', va='center',
            fontsize=8, style='italic', color='#EF6C00')

    prep_text = '''• Konsolidasi: Merge CSV files
• Cleaning: Hapus duplikat & null
• Feature Engineering:
  - aircraft_size (A0 rule)
  - airline_tier (frequency)
  - stand_zone (category-based)
• Encoding: Label encoding
• Split: 80% train, 20% test'''
    ax.text(phase3_x + 1.75, phase3_y + 0.3, prep_text, ha='center', va='top',
            fontsize=6.5, color='#333', linespacing=1.4)

    # Phase 4: Modeling (Bottom)
    phase4_x = 4
    phase4_y = 2.5
    phase4 = FancyBboxPatch((phase4_x, phase4_y), 4, 1.8, boxstyle="round,pad=0.15",
                            edgecolor='#7B1FA2', facecolor='#F3E5F5', linewidth=3)
    ax.add_patch(phase4)
    ax.text(phase4_x + 2, phase4_y + 1.4, '4. MODELING', ha='center', va='center',
            fontsize=11, fontweight='bold', color='#4A148C')
    ax.text(phase4_x + 2, phase4_y + 1.1, 'Pemodelan', ha='center', va='center',
            fontsize=9, style='italic', color='#6A1B9A')

    model_text = '''• Algoritma: Random Forest Classifier
• Hyperparameter Tuning: GridSearchCV (5-fold CV)
• Parameter: n_estimators, max_depth,
  min_samples_leaf, class_weight
• Solusi Imbalance: class_weight='balanced_subsample'
• Output: Top-3 predictions dengan probabilitas'''
    ax.text(phase4_x + 2, phase4_y + 0.25, model_text, ha='center', va='top',
            fontsize=6.5, color='#333', linespacing=1.5)

    # Phase 5: Evaluation (Bottom Left)
    phase5_x = 0.5
    phase5_y = 5
    phase5 = FancyBboxPatch((phase5_x, phase5_y), 3.5, 1.8, boxstyle="round,pad=0.15",
                            edgecolor='#D32F2F', facecolor='#FFEBEE', linewidth=3)
    ax.add_patch(phase5)
    ax.text(phase5_x + 1.75, phase5_y + 1.4, '5. EVALUATION', ha='center', va='center',
            fontsize=10, fontweight='bold', color='#B71C1C')
    ax.text(phase5_x + 1.75, phase5_y + 1.1, 'Evaluasi', ha='center', va='center',
            fontsize=8, style='italic', color='#C62828')

    eval_text = '''• Metrik Utama: Top-3 Accuracy
• Target: ≥80%
• Validasi: Confusion matrix,
  classification report
• Per-tier Analysis:
  High/Medium/Low frequency
• A0 Rule Validation:
  Small aircraft → A0 in top-3'''
    ax.text(phase5_x + 1.75, phase5_y + 0.25, eval_text, ha='center', va='top',
            fontsize=6.5, color='#333', linespacing=1.4)

    # Phase 6: Deployment (Left)
    phase6_x = 0.2
    phase6_y = 8.5
    phase6 = FancyBboxPatch((phase6_x, phase6_y), 2.8, 1.8, boxstyle="round,pad=0.15",
                            edgecolor='#00796B', facecolor='#E0F2F1', linewidth=3)
    ax.add_patch(phase6)
    ax.text(phase6_x + 1.4, phase6_y + 1.4, '6. DEPLOYMENT', ha='center', va='center',
            fontsize=10, fontweight='bold', color='#004D40')
    ax.text(phase6_x + 1.4, phase6_y + 1.1, 'Implementasi', ha='center', va='center',
            fontsize=8, style='italic', color='#00695C')

    deploy_text = '''• Simpan Model: .pkl files
• Integrasi: PHP ↔ Python
  (proc_open)
• Web Interface: Dashboard
  & Apron Map
• Monitoring: ML prediction
  log, performance metrics'''
    ax.text(phase6_x + 1.4, phase6_y + 0.3, deploy_text, ha='center', va='top',
            fontsize=6.5, color='#333', linespacing=1.4)

    # Center: Data (Core of CRISP-DM)
    center_x, center_y = 6, 7.5
    center_circle = Circle((center_x, center_y), 1.2, color='#FFC107',
                           edgecolor='#F57C00', linewidth=3, zorder=10)
    ax.add_patch(center_circle)
    ax.text(center_x, center_y + 0.3, 'DATA', ha='center', va='center',
            fontsize=11, fontweight='bold', color='#E65100', zorder=11)
    ax.text(center_x, center_y - 0.3, 'parking_history\n.csv', ha='center', va='center',
            fontsize=7, color='#EF6C00', zorder=11, style='italic')

    # Arrows showing iterative flow
    # 1 → 2
    arrow1 = FancyArrowPatch((7.5, phase1_y + 0.3), (9.2, phase2_y + 1.5),
                            arrowstyle='->,head_width=0.4,head_length=0.3',
                            color='#1976D2', linewidth=2.5, zorder=1)
    ax.add_patch(arrow1)

    # 2 → 3
    arrow2 = FancyArrowPatch((phase2_x + 1.4, phase2_y - 0.1), (phase3_x + 2, phase3_y + 1.8),
                            arrowstyle='->,head_width=0.4,head_length=0.3',
                            color='#388E3C', linewidth=2.5, zorder=1)
    ax.add_patch(arrow2)

    # 3 → 4
    arrow3 = FancyArrowPatch((phase3_x + 0.5, phase3_y - 0.1), (phase4_x + 2.5, phase4_y + 1.8),
                            arrowstyle='->,head_width=0.4,head_length=0.3',
                            color='#F57C00', linewidth=2.5, zorder=1)
    ax.add_patch(arrow3)

    # 4 → 5
    arrow4 = FancyArrowPatch((phase4_x - 0.1, phase4_y + 0.9), (phase5_x + 3.5, phase5_y + 0.9),
                            arrowstyle='->,head_width=0.4,head_length=0.3',
                            color='#7B1FA2', linewidth=2.5, zorder=1)
    ax.add_patch(arrow4)

    # 5 → 6
    arrow5 = FancyArrowPatch((phase5_x + 1.4, phase5_y + 1.8), (phase6_x + 1.4, phase6_y - 0.1),
                            arrowstyle='->,head_width=0.4,head_length=0.3',
                            color='#D32F2F', linewidth=2.5, zorder=1)
    ax.add_patch(arrow5)

    # 6 → 1 (completing the cycle)
    arrow6 = FancyArrowPatch((phase6_x + 2, phase6_y + 1.4), (4.5, phase1_y + 0.5),
                            arrowstyle='->,head_width=0.4,head_length=0.3',
                            color='#00796B', linewidth=2.5, linestyle='--', zorder=1)
    ax.add_patch(arrow6)

    # Feedback arrows (iterative nature)
    # 5 → 4 (if metrics not met, retune model)
    feedback1 = FancyArrowPatch((phase5_x + 0.5, phase4_y + 2), (phase4_x + 0.5, phase4_y + 2),
                               arrowstyle='->,head_width=0.3,head_length=0.2',
                               color='#999', linewidth=1.5, linestyle=':', zorder=0)
    ax.add_patch(feedback1)
    ax.text(4, phase4_y + 2.3, 'Retune if metrics < 80%', ha='center', va='center',
            fontsize=6, color='#666', style='italic')

    # 5 → 3 (if data quality issues)
    feedback2 = FancyArrowPatch((phase5_x + 1, phase5_y - 0.2), (phase3_x + 1, phase3_y - 0.2),
                               arrowstyle='->,head_width=0.3,head_length=0.2',
                               color='#999', linewidth=1.5, linestyle=':', zorder=0)
    ax.add_patch(feedback2)

    # Legend
    legend_y = 0.8
    ax.text(6, legend_y + 0.5, 'CATATAN:', ha='center', va='center',
            fontsize=10, fontweight='bold',
            bbox=dict(boxstyle='round', facecolor='#FFF9C4', edgecolor='black', linewidth=2))

    legend_text = '''Proses CRISP-DM bersifat iteratif dan siklis. Setiap fase dapat kembali ke fase sebelumnya
untuk perbaikan. Garis putus-putus menunjukkan feedback loop untuk iterasi model.'''
    ax.text(6, legend_y - 0.2, legend_text, ha='center', va='top',
            fontsize=7, color='#333', style='italic')

    plt.tight_layout()
    plt.savefig('reports/thesis_crisp_dm_diagram.png', dpi=300, bbox_inches='tight', facecolor='white')
    print("OK - CRISP-DM diagram generated")

# ============================================================================
# DIAGRAM 2: System Flowchart + Use Case Diagram
# ============================================================================
def generate_system_usecase_diagram():
    fig = plt.figure(figsize=(18, 12))

    # Split into two subplots: left for flowchart, right for use case
    gs = fig.add_gridspec(1, 2, width_ratios=[1, 1], hspace=0.3)

    fig.suptitle('Diagram Alur Sistem & Use Case Web DSS AMC',
                 fontsize=16, fontweight='bold', y=0.96)

    # ========================================================================
    # LEFT: System Flowchart
    # ========================================================================
    ax1 = fig.add_subplot(gs[0, 0])
    ax1.set_xlim(0, 10)
    ax1.set_ylim(0, 14)
    ax1.axis('off')
    ax1.invert_yaxis()

    ax1.text(5, 0.5, 'A. FLOWCHART SISTEM', ha='center', va='center',
             fontsize=13, fontweight='bold',
             bbox=dict(boxstyle='round,pad=0.5', facecolor='#E3F2FD', edgecolor='#1976D2', linewidth=2))

    # Start
    start = Circle((5, 1.5), 0.3, color='#4CAF50', edgecolor='black', linewidth=2)
    ax1.add_patch(start)
    ax1.text(5, 1.5, 'START', ha='center', va='center', fontsize=7, fontweight='bold', color='white')

    # Flow steps
    steps = [
        (2.5, '1. Input Histori Parkir\n(Google Sheets → CSV)', '#E3F2FD', '#1976D2'),
        (3.5, '2. Preprocessing Data\n(Cleaning, Feature Engineering)', '#FFF3E0', '#F57C00'),
        (4.5, '3. Train Random Forest Model\n(GridSearchCV, Top-3 Output)', '#F3E5F5', '#7B1FA2'),
        (5.5, '4. Save Model (.pkl files)', '#E8F5E9', '#388E3C'),
        (7.0, '5. User Input: Registrasi,\nType, Airline, Category', '#E3F2FD', '#1976D2'),
        (8.2, '6. PHP → proc_open() → Python\nml/predict.py', '#FFF3E0', '#F57C00'),
        (9.4, '7. Return Top-3 Rekomendasi\n+ Probability', '#F3E5F5', '#7B1FA2'),
        (10.6, '8. User Pilih Stand\n(Accept/Override)', '#E3F2FD', '#1976D2'),
        (11.8, '9. Save to MySQL\n(aircraft_movements)', '#FFEBEE', '#D32F2F'),
        (13.0, '10. Update Dashboard\n& Visualisasi Apron', '#E0F2F1', '#00796B'),
    ]

    prev_y = 1.5
    for y, text, bgcolor, edgecolor in steps:
        # Arrow from previous
        ax1.arrow(5, prev_y + 0.35, 0, y - prev_y - 0.75,
                 head_width=0.2, head_length=0.1, fc='black', ec='black')

        # Box
        box = FancyBboxPatch((2.5, y - 0.35), 5, 0.7, boxstyle="round,pad=0.1",
                            edgecolor=edgecolor, facecolor=bgcolor, linewidth=2)
        ax1.add_patch(box)
        ax1.text(5, y, text, ha='center', va='center', fontsize=7, color='#333')
        prev_y = y

    # End
    ax1.arrow(5, 13.35, 0, 0.3, head_width=0.2, head_length=0.1, fc='black', ec='black')
    end = Circle((5, 13.8), 0.3, color='#F44336', edgecolor='black', linewidth=2)
    ax1.add_patch(end)
    ax1.text(5, 13.8, 'END', ha='center', va='center', fontsize=7, fontweight='bold', color='white')

    # ========================================================================
    # RIGHT: Use Case Diagram
    # ========================================================================
    ax2 = fig.add_subplot(gs[0, 1])
    ax2.set_xlim(0, 10)
    ax2.set_ylim(0, 14)
    ax2.axis('off')
    ax2.invert_yaxis()

    ax2.text(5, 0.5, 'B. USE CASE DIAGRAM', ha='center', va='center',
             fontsize=13, fontweight='bold',
             bbox=dict(boxstyle='round,pad=0.5', facecolor='#E3F2FD', edgecolor='#1976D2', linewidth=2))

    # System boundary
    system_box = Rectangle((2, 1.5), 6, 11, facecolor='#FAFAFA',
                           edgecolor='#1976D2', linewidth=3, linestyle='--')
    ax2.add_patch(system_box)
    ax2.text(5, 1.8, 'Web DSS AMC System', ha='center', va='center',
             fontsize=10, fontweight='bold', color='#1976D2')

    # Actors
    # Actor 1: Admin/Operator (Left)
    actor1_x, actor1_y = 0.8, 5
    ax2.add_patch(Circle((actor1_x, actor1_y), 0.15, color='#FFE0B2', edgecolor='#E65100', linewidth=2))
    ax2.plot([actor1_x, actor1_x], [actor1_y + 0.15, actor1_y + 0.5], 'k-', linewidth=2)
    ax2.plot([actor1_x - 0.3, actor1_x + 0.3], [actor1_y + 0.3, actor1_y + 0.3], 'k-', linewidth=2)
    ax2.plot([actor1_x, actor1_x - 0.2], [actor1_y + 0.5, actor1_y + 0.9], 'k-', linewidth=2)
    ax2.plot([actor1_x, actor1_x + 0.2], [actor1_y + 0.5, actor1_y + 0.9], 'k-', linewidth=2)
    ax2.text(actor1_x, actor1_y + 1.2, 'Admin/\nOperator AMC', ha='center', va='center',
             fontsize=8, fontweight='bold', color='#E65100')

    # Actor 2: Viewer (Left lower)
    actor2_x, actor2_y = 0.8, 10
    ax2.add_patch(Circle((actor2_x, actor2_y), 0.15, color='#C5CAE9', edgecolor='#3F51B5', linewidth=2))
    ax2.plot([actor2_x, actor2_x], [actor2_y + 0.15, actor2_y + 0.5], 'k-', linewidth=2)
    ax2.plot([actor2_x - 0.3, actor2_x + 0.3], [actor2_y + 0.3, actor2_y + 0.3], 'k-', linewidth=2)
    ax2.plot([actor2_x, actor2_x - 0.2], [actor2_y + 0.5, actor2_y + 0.9], 'k-', linewidth=2)
    ax2.plot([actor2_x, actor2_x + 0.2], [actor2_y + 0.5, actor2_y + 0.9], 'k-', linewidth=2)
    ax2.text(actor2_x, actor2_y + 1.2, 'Viewer', ha='center', va='center',
             fontsize=8, fontweight='bold', color='#3F51B5')

    # Actor 3: ML Model (Right - external system)
    actor3_x, actor3_y = 9.2, 7
    ax2.add_patch(Rectangle((actor3_x - 0.35, actor3_y - 0.2), 0.7, 0.4,
                           facecolor='#C8E6C9', edgecolor='#388E3C', linewidth=2))
    ax2.text(actor3_x, actor3_y, 'ML', ha='center', va='center',
             fontsize=7, fontweight='bold', color='#1B5E20')
    ax2.text(actor3_x, actor3_y + 0.5, 'Random Forest\nModel', ha='center', va='center',
             fontsize=7, fontweight='bold', color='#388E3C')

    # Use Cases (ellipses)
    use_cases = [
        (5, 2.8, 'Login/Logout', '#E3F2FD', True, True),
        (5, 3.8, 'Input Data\nPergerakan', '#E3F2FD', True, False),
        (5, 4.8, 'Request Rekomendasi\nStand (ML)', '#F3E5F5', True, False),
        (5, 5.8, 'Pilih & Simpan Stand', '#E3F2FD', True, False),
        (5, 6.8, 'Set RON Status', '#E3F2FD', True, False),
        (5, 7.8, 'Lihat Peta Apron\nReal-time', '#E0F7FA', True, True),
        (5, 8.8, 'Lihat Dashboard\n& Statistik', '#E0F7FA', True, True),
        (5, 9.8, 'Generate Report', '#E3F2FD', True, False),
        (5, 10.8, 'Manage User\nAccounts', '#FFEBEE', True, False),
        (5, 11.8, 'View Prediction\nLogs', '#E0F7FA', True, True),
    ]

    for x, y, label, color, admin_access, viewer_access in use_cases:
        # Ellipse (use case)
        ellipse = mpatches.Ellipse((x, y), 1.8, 0.65, facecolor=color,
                                   edgecolor='#1976D2', linewidth=1.5)
        ax2.add_patch(ellipse)
        ax2.text(x, y, label, ha='center', va='center', fontsize=7, color='#333')

        # Connect to actors
        if admin_access:
            ax2.plot([actor1_x + 0.3, x - 0.9], [actor1_y, y], 'k-', linewidth=1, alpha=0.5)
        if viewer_access:
            ax2.plot([actor2_x + 0.3, x - 0.9], [actor2_y, y], 'k-', linewidth=1, alpha=0.5)

    # Connect ML use case to ML actor
    ax2.plot([actor3_x - 0.4, 6.9], [actor3_y, 5.8], 'g--', linewidth=1.5, alpha=0.7)
    ax2.text(7.5, 6.5, '<<include>>', ha='center', va='center',
             fontsize=6, style='italic', color='#388E3C')

    plt.tight_layout()
    plt.savefig('reports/thesis_system_usecase_diagram.png', dpi=300, bbox_inches='tight', facecolor='white')
    print("OK - System flowchart + Use case diagram generated")

# ============================================================================
# Generate both diagrams
# ============================================================================
if __name__ == '__main__':
    print("Generating CRISP-DM and Use Case diagrams...")
    generate_crisp_dm_diagram()
    generate_system_usecase_diagram()
    print("\nBoth diagrams completed!")
    print("- reports/thesis_crisp_dm_diagram.png")
    print("- reports/thesis_system_usecase_diagram.png")

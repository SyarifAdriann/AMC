OUTLINE PRESENTASI SEMPRO
Durasi: 15-20 menit

SLIDE 1: COVER
Content:

Judul lengkap penelitian
Nama: Syarif Adrian Mangarja Lubis
NIM: 221051013
Program Studi Informatika
Logo Universitas Dirgantara Marsekal Suryadarma
Tahun: 2025

Catatan verbal: Ucapan salam dan terima kasih kepada dosen penguji dan Kaprodi

SLIDE 2: DAFTAR ISI
Content:

Latar Belakang
Identifikasi & Rumusan Masalah
Tujuan & Manfaat Penelitian
Landasan Teori
Metodologi Penelitian
Rancangan Sistem
Penutup

Catatan verbal: (30 detik) Overview singkat struktur presentasi

SLIDE 3: LATAR BELAKANG - Konteks AMC
Content:

Foto/ilustrasi Apron Movement Control di Bandar Udara Halim Perdanakusuma
Definisi singkat AMC: Unit yang bertanggung jawab atas pergerakan pesawat di area apron
70% tanggung jawab AMC = alokasi parking stand
Karakteristik unik: melayani beragam kategori (kargo, komersial, charter, militer)

Catatan verbal: (1 menit) Jelaskan peran krusial AMC dan kompleksitas operasional

SLIDE 4: LATAR BELAKANG - Sistem Manual Existing
Content:

Screenshot Google Sheets yang digunakan saat ini
Poin-poin sistem manual:

Koordinasi via radio HT
Dokumentasi terpisah (3 spreadsheet berbeda)
Keputusan berbasis intuisi operator
Tidak ada visualisasi real-time


Highlight masalah: 2x per bulan pesawat menunggu 1-2 menit di taxiway

Catatan verbal: (1.5 menit) Tekankan gap dan inefisiensi sistem current

SLIDE 5: IDENTIFIKASI MASALAH (Part 1)
Content:
Numbered list dengan icon:

📄 Dokumentasi Manual dan Terpisah

3 file Google Sheets terpisah
Duplikasi 1-3x per hari


👁️ Tidak Ada Visualisasi Real-Time

Waktu identifikasi stand: 30 detik
Kesalahan alokasi: 2x per hari


🤔 Alokasi Stand Tanpa Dukungan Sistem

Waktu keputusan: 1-2 menit
Alokasi tidak optimal: 3-4x per hari



Catatan verbal: (1 menit) Fokus pada dampak operasional dari setiap masalah

SLIDE 6: IDENTIFIKASI MASALAH (Part 2)
Content:
Numbered list (lanjutan):
4. ⚠️ Validasi Data Lemah

Kesalahan timestamp: 2x per hari


📡 Koordinasi Antarunit Rentan Miskomunikasi

Miskomunikasi AMC-ATC: 1-2x per hari
Delay: 2x per bulan, 1-2 menit per incident


🔒 Tidak Ada Sistem Terpadu dengan Akses Terbatas

Setiap unit pakai sistem berbeda
Lag informasi antar unit



Catatan verbal: (1 menit) Tekankan kebutuhan mendesak akan sistem terintegrasi

SLIDE 7: RUMUSAN MASALAH
Content:
3 pertanyaan penelitian utama:

Bagaimana merancang arsitektur sistem informasi AMC berbasis web yang dapat:

Mengintegrasikan 3+ jenis laporan operasional
Response time maksimal 5 detik
Visualisasi real-time kondisi apron?


Bagaimana mengimplementasikan algoritma Random Forest untuk prediksi alokasi parking stand dengan:

Target Top-3 accuracy minimal 80%
4 parameter operasional (tipe pesawat, kategori penerbangan, preferensi maskapai)
Waktu prediksi maksimal 5 detik?


Seberapa besar kontribusi sistem terhadap efisiensi operasional AMC?

Catatan verbal: (1 menit) Hubungkan setiap rumusan dengan masalah yang diidentifikasi

SLIDE 8: BATASAN MASALAH
Content:
List dengan bullet points:

Fokus: Unit AMC internal Bandara Halim Perdanakusuma
Algoritma: Eksklusif Random Forest
Dataset: Periode 3 bulan (Mei-Juli 2025)
Scope prediksi: 20 parking stand operasional (Main Apron: A0-A3, B1-B13, WR01-WR03)

Total stand fisik: 83, namun 63 untuk RON/repositioning


Platform: Web-based (tidak termasuk mobile app)
Evaluasi: Black-box testing + feedback terbatas user internal

Catatan verbal: (45 detik) Tekankan fokus pada 20 stand operasional, bukan 83

SLIDE 9: TUJUAN PENELITIAN
Content:
Highlight dengan box/background warna:
Merancang dan mengembangkan sistem informasi AMC berbasis web yang terintegrasi untuk meningkatkan efisiensi operasional melalui:
✓ Substitusi pencatatan manual dengan platform terpusat
✓ Visualisasi real-time kondisi apron
✓ Implementasi Random Forest untuk prediksi alokasi parking stand
✓ Pengambilan keputusan lebih cepat dan akurat berbasis data historis
✓ Minimalisasi miskomunikasi antarunit operasional
Catatan verbal: (45 detik) Tekankan aspek ML sebagai inti inovasi

SLIDE 10: MANFAAT PENELITIAN
Content:
3 kolom:
Bagi Instansi:

Efisiensi pencatatan
Rekomendasi berbasis data
Koordinasi lebih baik
Pengelolaan strategis

Bagi Perguruan Tinggi:

Studi kasus ML di aviasi
Referensi pengembangan serupa
Kontribusi keilmuan SI

Bagi Penulis:

Aplikasi teori ke praktik
Pengalaman pengembangan
Kompetensi analisis sistem

Catatan verbal: (45 detik) Singkat, fokus pada kontribusi praktis dan akademis

SLIDE 11: TINJAUAN PUSTAKA - Penelitian Terdahulu
Content:
Tabel ringkas (4 penelitian):
PenelitiFokusGapUlfa et al. (2023)SI Parking Stand - PadangTidak ada prediksi/visualisasi real-timeJumlad & Nurhalisa (2024)SI AMC - MakassarKeputusan tetap manual, tidak ada MLSuryaman & Wang (2022)Data Mining - Slot TimeFokus jadwal, bukan alokasi standAkbiyik et al. (2024)DSS Logistik InternalDomain berbeda (gudang)
Highlight: Belum ada yang menggabungkan ML + DSS + Visualisasi Real-time
Catatan verbal: (1 menit) Tekankan gap analysis dan positioning penelitian

SLIDE 12: LANDASAN TEORI - Random Forest
Content:
Diagram ilustrasi Random Forest:

Gambar ensemble multiple decision trees
Proses: Bootstrap Sampling → Multiple Trees → Voting → Final Prediction

Keunggulan untuk kasus ini:
✓ Menangani 20-class classification
✓ Handling class imbalance (beberapa stand lebih sering digunakan)
✓ Output probabilistik → Top-3 prediction
✓ Feature importance → transparansi keputusan
✓ Reduksi overfitting vs single decision tree
Perbandingan singkat:
AspekDecision TreeRandom ForestVarianceTinggiRendahOverfittingRentanTahanImbalanceTidak otomatisclass_weight='balanced'
Catatan verbal: (1.5 menit) Jelaskan MENGAPA Random Forest dipilih, bukan hanya apa itu RF

SLIDE 13: LANDASAN TEORI - Top-K Prediction Strategy
Content:
Visual example:
Input: 
- Aircraft: B738
- Airline: Citilink  
- Category: Domestic

Prediksi Top-3:
🥇 Stand A3 (P=0.45) ← 45 trees vote
🥈 Stand A2 (P=0.23) ← 23 trees vote  
🥉 Stand B7 (P=0.15) ← 15 trees vote
Mengapa Top-3?

Fleksibilitas operasional (stand #1 sedang maintenance)
Handling uncertainty (probabilitas berdekatan)
Evaluasi lebih realistis (Top-3 accuracy > Top-1)

Target: Top-3 Accuracy ≥ 80%
Catatan verbal: (1 menit) Tekankan aspek Decision Support, bukan autopilot

SLIDE 14: METODOLOGI - CRISP-DM Framework
Content:
Diagram circular CRISP-DM dengan 6 tahap:

Business Understanding → Identifikasi masalah AMC
Data Understanding → Dataset 4069 entri, 3 bulan
Data Preparation → Cleaning, encoding, feature engineering
Modeling → Random Forest training + Grid Search
Evaluation → Top-3 Accuracy, confusion matrix, feature importance
Deployment → Integrasi PHP-Python

Catatan verbal: (1 menit) Jelaskan framework sebagai pendekatan sistematis

SLIDE 15: DATA UNDERSTANDING - Dataset
Content:
Karakteristik Dataset:

Sumber: Catatan operasional AMC Halim Perdanakusuma
Periode: Mei - Juli 2025
Total entri: 4069 pergerakan pesawat
Target: 20 parking stand operasional

Distribusi Kategori Penerbangan:

📊 COMMERCIAL: 60.97%
📊 CHARTER: 28.09%
📊 CARGO: 10.94%

Fitur Input (4 atribut):

Aircraft Type (tipe pesawat)
Operator/Airline (maskapai)
Category (kategori penerbangan)
Feature engineering: kombinasi fitur

Screenshot: Contoh data dari Google Sheets
Catatan verbal: (1 menit) Tekankan class imbalance dan realisme data

SLIDE 16: DATA PREPARATION
Content:
Flowchart/diagram proses:
1. Rekonsiliasi Data:
24 Google Sheets harian → parking_history.csv
2. Data Cleaning:

Hapus duplikasi
Normalisasi format (uppercase)
Handle missing values
Validasi timestamp

3. Feature Engineering:

One-Hot Encoding: Aircraft_Type
Label Encoding: Operator_Airline, Category
Kombinasi fitur: airline_category, aircraft_airline

4. Output:
parking_history_clean.csv (4069 records, ready for training)
Catatan verbal: (1 menit) Jelaskan pentingnya data quality untuk model

SLIDE 17: MODELING - Random Forest Configuration
Content:
Hyperparameter Tuning (Grid Search + 5-fold CV):
Parameter Grid:
python{
  'n_estimators': [100, 200],
  'max_depth': [None, 20, 30],
  'min_samples_leaf': [2, 5, 10],
  'min_samples_split': [5, 10],
  'class_weight': ['balanced', 'balanced_subsample']
}
Total kombinasi: 72 (2×3×3×2×2)
Total model fits: 360 (72 × 5 folds)
Train-Test Split: 80% training : 20% testing
Model Output:

parking_stand_model_rf_redo.pkl (2.6 MB)
encoders_redo.pkl (untuk consistency encoding)

Catatan verbal: (1 menit) Jelaskan proses optimasi untuk mencapai target accuracy

SLIDE 18: EVALUATION METRICS
Content:
Metrik yang akan digunakan:
1. Primary Metric:

Top-3 Accuracy ≥ 80% (apakah stand aktual masuk Top-3 prediksi?)

2. Standard Classification Metrics:

Accuracy
Precision (per-class)
Recall (per-class)
F1-Score

3. Visual Analysis:

Confusion Matrix (20×20)
Feature Importance Plot
Classification Report

4. Operational Metrics:

Response Time ≤ 5 detik
Prediction Acceptance Rate ≥ 70%

Catatan verbal: (1 menit) Tekankan Top-3 Accuracy sebagai metrik utama keberhasilan

SLIDE 19: METODE PENGEMBANGAN - Agile/Scrum
Content:
Justifikasi Agile:
✓ Perubahan requirement iteratif
✓ Feedback loop berkelanjutan dengan operator AMC
✓ Pengembangan inkremental per fitur
Sprint Planning (contoh):

Sprint 1: Database design + basic CRUD
Sprint 2: ML model training + integration
Sprint 3: Apron Map visualization
Sprint 4: Dashboard + reporting
Sprint 5: Testing + refinement

Role Adaptation (individual project):

Product Owner: Supervisor + Operator AMC
Developer: Peneliti
Sprint: Weekly iterations

Catatan verbal: (45 detik) Jelaskan fleksibilitas pendekatan untuk proyek individual

SLIDE 20: ARSITEKTUR SISTEM - Three-Tier Architecture
Content:
Diagram arsitektur lengkap:
Presentation Layer (Frontend):

HTML5 + CSS3 (Tailwind) + Vanilla JS
Components: Apron Map, Dashboard, Prediction Cards
AJAX (Fetch API) untuk komunikasi

Application Logic Layer (Backend):

PHP 8.3.25 (Custom MVC)
Controllers: ApronController, SnapshotController
Middleware: Auth + RBAC
PHP-Python IPC via proc_open() + JSON

Data Layer:

MySQL/MariaDB 10.4.32 (13 tables)
Python ML Module: model.pkl + encoders.pkl
Training artifacts

Highlight: Integrasi PHP-Python sebagai solusi teknis utama
Catatan verbal: (1.5 menit) PENTING: Jelaskan alur PHP → Python → Model → Response

SLIDE 21: INTEGRATION FLOW - ML Prediction
Content:
Sequence diagram detail atau flowchart:

User input (tipe pesawat, maskapai, kategori) via form
Frontend AJAX POST ke /api/apron/recommend
PHP Backend validate input → construct JSON payload
PHP call Python via proc_open():

json   {"aircraft": "B738", "airline": "Citilink", "category": "COMMERCIAL"}

Python ml/predict.py:

Load model.pkl + encoders.pkl
Encode input
Run Random Forest inference
Return Top-3 dengan probabilities


PHP parse JSON response → log ke database
Frontend display Top-3 recommendation cards
User klik untuk allocate

Target Response Time: ≤ 5 detik
Catatan verbal: (1.5 menit) KRUSIAL: Jelaskan detail teknis integrasi ini

SLIDE 22: USE CASE DIAGRAM - Sistem Usulan
Content:
UML Use Case Diagram lengkap dengan 3 actor:
Admin:

Kelola user (CRUD, reset password)
Akses penuh semua modul

Operator (AMC):

Input/edit movement data
Request ML prediction ← HIGHLIGHT
Tandai RON
Buat snapshot
Akses dashboard

Viewer (ATC/AOCC):

View apron status (read-only)
View historical data
Tidak bisa edit/save

Catatan verbal: (1 menit) Tekankan role-based access dan fitur ML prediction

SLIDE 23: ACTIVITY DIAGRAM - Proses Alokasi dengan ML
Content:
Activity diagram sistem usulan:
Start → Input data movement → Klik "Recommend Stand" →
System call ML prediction → Random Forest inference (max 5s) →
Display Top-3 recommendations dengan confidence score →
Decision: Terima rekomendasi?

Ya → Allocate stand dari Top-3
Tidak → Manual selection

→ Save to database → Update apron visualization → End
Bandingkan dengan sistem manual (side-by-side kecil):

Manual: 1-2 menit, subjektif
Usulan: ≤5 detik, objektif + fleksibel

Catatan verbal: (1 menit) Tekankan efisiensi dan tetap ada human-in-the-loop

SLIDE 24: SEQUENCE DIAGRAM - ML Prediction Request
Content:
UML Sequence Diagram:
Participants:

Operator (User)
Browser (Frontend)
ApronController.php
DatabaseManager.php
ml/predict.py
model.pkl

Key interactions:

Operator → Browser: Click "Recommend"
Browser → ApronController: POST /api/apron/recommend
ApronController → ml/predict.py: proc_open() with JSON stdin
predict.py → model.pkl: Load & inference
model.pkl → predict.py: Top-3 results
predict.py → ApronController: JSON stdout
ApronController → DatabaseManager: Log prediction
ApronController → Browser: JSON response
Browser → Operator: Display Top-3 cards

Catatan verbal: (1 menit) Fokus pada flow komunikasi antarkomponens

SLIDE 25: ERD - Database Schema
Content:
Entity Relationship Diagram dengan highlight:
Core tables:

aircraft_movements (main operational data)
parking_stands (83 stand, 20 operational)
ml_prediction_log (tracking ML recommendations)
users, roles, airlines, aircraft_types, etc.

Key relationships:

movements → stands (many-to-one)
movements → airlines (many-to-one)
prediction_log → movements (one-to-one)

Total: 13 tables
Catatan penting: Database support 83 stand fisik, ML fokus 20 stand operasional
Catatan verbal: (45 detik) Jelaskan struktur mendukung operasional + ML logging

SLIDE 26: WIREFRAME - Interface Design
Content:
2-3 wireframe utama:
1. Apron Map (Index Page):

Visual layout apron dengan 20 stand
Color coding: Occupied (merah), Available (hijau), Allocated (kuning)
Real-time status update

2. Dashboard:

Daily movement counter
ML prediction metrics (jika sudah ada data)
Recent activities
Charts: kategori penerbangan, stand utilization

3. Prediction Result Cards (optional):

Top-3 stand recommendations
Confidence score per stand
Click-to-allocate button

Catatan verbal: (1 menit) Tekankan user-friendly dan visualisasi real-time

SLIDE 27: FITUR UTAMA SISTEM
Content:
Highlight dengan icon/visual:

📝 Input & Edit Peristiwa Parkir

Form pencatatan on/off-block


🤖 Prediksi Alokasi Stand (Random Forest)

Top-3 recommendations dengan confidence score


🗺️ Visualisasi Interaktif Status Apron

Real-time apron map, color-coded


📸 Snapshot & Rekapitulasi Operasional

Daily snapshot, RON marking, export logs


👥 Manajemen Akun dan Hak Akses

Role-based: Admin, Operator, Viewer



Catatan verbal: (1 menit) Jelaskan bagaimana setiap fitur menjawab masalah yang diidentifikasi

SLIDE 28: RENCANA PENGUJIAN
Content:
Metode Pengujian:
1. Black-Box Testing:

Fungsionalitas sistem (CRUD, ML prediction, visualization)
Validasi form input
Role-based access control

2. Performance Testing:

Response time ML prediction (target ≤5 detik)
System load dengan multiple concurrent users

3. Model Evaluation:

Top-3 Accuracy (target ≥80%)
Confusion matrix analysis
Feature importance validation

4. User Acceptance Testing (Limited):

Feedback dari operator AMC
Usability evaluation

Catatan verbal: (45 detik) Jelaskan pendekatan komprehensif untuk validasi

SLIDE 29: KONTRIBUSI PENELITIAN
Content:
Highlight dalam box:
Originalitas:
✅ Integrasi pertama: ML (Random Forest) + DSS + Visualisasi Real-time dalam satu platform AMC di Indonesia
✅ Pendekatan Top-K prediction untuk fleksibilitas operasional
✅ PHP-Python integration architecture untuk sistem legacy
Kontribusi Praktis:

Efisiensi pengambilan keputusan (1-2 menit → ≤5 detik)
Konsistensi alokasi berbasis data historis
Platform terpusat mengganti spreadsheet tersegmentasi

Kontribusi Akademis:

Studi kasus ML di sektor aviasi Indonesia
Framework replicable untuk bandara serupa

Catatan verbal: (1 menit) Tekankan value proposition dan scalability

SLIDE 30: KESIMPULAN
Content:
Summary dalam bullet points:
✓ Sistem manual AMC memiliki 6 masalah kritis yang menghambat efisiensi
✓ Sistem usulan mengintegrasikan pencatatan, visualisasi, dan ML-based decision support
✓ Random Forest dipilih karena kemampuan multi-class classification (20 stand), handling imbalance, dan output probabilistik
✓ Target: Top-3 Accuracy ≥80%, Response Time ≤5 detik
✓ Arsitektur three-tier dengan PHP-Python integration
✓ Metodologi CRISP-DM + Agile development
✓ Expected impact: efisiensi keputusan, konsistensi operasional, minimalisasi miskomunikasi
Catatan verbal: (1 menit) Recap singkat value proposition

SLIDE 31: TERIMA KASIH
Content:

"Terima Kasih"
"Pertanyaan dan Saran"
Contact info (opsional)
Logo universitas

Catatan verbal: Siap untuk sesi tanya jawab

TOTAL SLIDE: 31
ESTIMASI WAKTU: 17-19 menit (inti presentasi), +3-5 menit Q&A

TIPS PRESENTASI:

Slide 12-13, 20-21, 23: INI ADALAH SLIDE KRUSIAL untuk sempro Anda karena fokus pada ML implementation. Berlatih extra untuk slide ini.
Jangan membaca slide: Slide adalah visual aid, Anda menjelaskan lebih dalam.
Siapkan backup answer untuk kemungkinan pertanyaan:

"Mengapa tidak pakai Neural Network atau SVM?"
"Bagaimana jika accuracy tidak mencapai 80%?"
"Bagaimana menangani data baru yang sangat berbeda dari training data?"
"Apakah sistem ini real-time atau batch processing?"


Highlight angka-angka konkret: 1-2 menit → ≤5 detik, 80% accuracy, 20 stand, 4069 records, dll.
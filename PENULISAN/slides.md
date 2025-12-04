## Slide 1: Title Slide
# Rancang Bangun Sistem Informasi Apron Movement Control (AMC) Berbasis Web dengan Implementasi Algoritma Random Forest untuk Prediksi Alokasi Parking Stand
**(Studi Kasus: Bandar Udara Halim Perdanakusuma)**

**Penyusun:**
Syarif Adrian Mangarja Lubis  
NIM: 221051013  
Program Studi Informatika  
Universitas Dirgantara Marsekal Suryadarma  
2025

**Notes:**
Assalamu’alaikum Wr. Wb. Selamat pagi/siang kepada Bapak/Ibu Dosen Penguji dan Kaprodi. Terima kasih atas kesempatan yang diberikan. Saya Syarif Adrian akan mempresentasikan proposal skripsi saya mengenai digitalisasi dan optimasi sistem AMC di Bandara Halim Perdanakusuma.

## Slide 2: Daftar Isi
1. **Latar Belakang**
2. **Identifikasi & Rumusan Masalah**
3. **Tujuan & Manfaat Penelitian**
4. **Landasan Teori**
5. **Metodologi Penelitian**
6. **Rancangan Sistem**
7. **Penutup**

**Notes:**
Berikut adalah agenda presentasi hari ini. Kita akan mulai dari konteks permasalahan operasional AMC, solusi teknis yang saya tawarkan menggunakan Machine Learning, hingga demonstrasi arsitektur sistemnya.

## Slide 3: Latar Belakang - Konteks AMC
### Apron Movement Control (AMC)
*Unit yang bertanggung jawab penuh atas pengaturan pergerakan pesawat dan kendaraan di area apron.*

*   **Fungsi Utama:** Mengalokasikan *parking stand* (tempat parkir pesawat).
*   **Beban Kerja:** 70% aktivitas operasional AMC adalah manajemen alokasi stand.
*   **Kompleksitas:** Melayani beragam tipe penerbangan:
    *   ✈️ Komersial (Batik, Citilink)
    *   📦 Kargo (Trigana, Tri MG)
    *   🛩️ Charter/VIP (Private Jets)
    *   🎖️ Militer/VVIP

**Notes:**
AMC adalah "polisi lalu lintas" di area apron. Peran mereka sangat krusial karena 70% tugasnya adalah menentukan di mana pesawat harus parkir. Di Halim, ini sangat kompleks karena tipe penerbangannya sangat variatif, tidak hanya komersial reguler tapi juga kargo dan VIP/Militer yang jadwalnya dinamis.

## Slide 4: Latar Belakang - Sistem Manual Existing
### Kondisi Saat Ini (As-Is)

[Visual Placeholder: Screenshot of Current Google Sheets System]

*   **Pencatatan:** Menggunakan 3 file Google Sheets terpisah (Logbook, Roster, Movement).
*   **Komunikasi:** Koordinasi via Handy Talky (HT) tanpa visualisasi terpusat.
*   **Keputusan:** Berbasis intuisi dan hapalan operator senior.
*   **Masalah:** 
    *   Tidak ada visualisasi *real-time* okupansi apron.
    *   Pesawat sering menunggu 1-2 menit di *taxiway* untuk kepastian stand.

**Notes:**
Saat ini, pencatatan masih sangat manual menggunakan Google Sheets yang terpisah-pisah. Keputusan alokasi stand sangat bergantung pada intuisi operator. Akibatnya, sering terjadi *delay* di mana pesawat harus menunggu di taxiway hanya karena operator sedang memastikan ketersediaan stand secara manual.

## Slide 5: Identifikasi Masalah (1/2)
### Masalah Operasional Utama

1.  **📄 Dokumentasi Terfragmentasi**
    *   Data tersebar di 3 spreadsheet berbeda.
    *   Duplikasi input terjadi 1-3x per hari.

2.  **👁️ Ketiadaan Visualisasi Real-Time**
    *   Waktu identifikasi stand kosong: ~30 detik.
    *   Kesalahan alokasi (bentrok): ~2x per hari.

3.  **🤔 Keputusan Tanpa Dukungan Sistem**
    *   Waktu pengambilan keputusan: 1-2 menit per pesawat.
    *   Alokasi tidak optimal (pesawat besar di stand kecil/jauh): 3-4x per hari.

**Notes:**
Masalah utamanya ada tiga: Dokumentasi yang terpecah-pecah menyebabkan duplikasi kerja. Tidak adanya visualisasi visual membuat operator butuh waktu lama mengecek stand kosong. Dan yang paling kritikal, keputusan alokasi murni manual tanpa bantuan data historis.

## Slide 6: Identifikasi Masalah (2/2)
### Masalah Koordinasi & Keamanan

4.  **⚠️ Validasi Data Lemah**
    *   Kesalahan input jam (*timestamp*): ~2x per hari.
    *   Tidak ada validasi tipe pesawat vs ukuran stand.

5.  **📡 Potensi Miskomunikasi**
    *   Miskomunikasi AMC-ATC: 1-2x per hari.
    *   *Information Lag* antara unit operasional.

6.  **🔒 Akses Data Tidak Terkontrol**
    *   Spreadsheet rentan diakses atau diubah oleh pihak yang tidak berwenang.

**Notes:**
Selain itu, validasi data sangat lemah—sering terjadi typo jam block-on/block-off. Koordinasi via HT juga rentan *missed*, dan keamanan data di spreadsheet publik tentu kurang terjamin dibanding sistem database terdedikasi.

## Slide 7: Rumusan Masalah
### Research Questions

1.  **Desain Sistem:**
    *   Bagaimana merancang arsitektur sistem informasi AMC berbasis web yang mengintegrasikan laporan operasional dengan *response time* < 5 detik?

2.  **Implementasi Machine Learning:**
    *   Bagaimana mengimplementasikan algoritma *Random Forest* untuk prediksi alokasi stand dengan target **Top-3 Accuracy ≥ 80%**?

3.  **Dampak Operasional:**
    *   Seberapa besar kontribusi sistem terhadap efisiensi waktu pengambilan keputusan operasional AMC?

**Notes:**
Dari masalah tadi, saya merumuskan tiga pertanyaan kunci: Bagaimana membangun sistemnya agar responsif? Bagaimana menerapkan Random Forest agar akurat di atas 80%? Dan bagaimana dampak nyatanya terhadap efisiensi operasional bandara.

## Slide 8: Batasan Masalah
### Scope Penelitian

*   **Objek:** Unit AMC Bandara Halim Perdanakusuma.
*   **Algoritma:** Eksklusif menggunakan **Random Forest Classifier**.
*   **Dataset:** Data operasional real 3 bulan (Mei - Juli 2025).
*   **Scope Area:** Fokus pada **20 parking stand operasional** (Main Apron: A0-A3, B1-B13, WR01-WR03).
    *   *(Total fisik 83 stand, namun sisa 63 hanya untuk RON/Repositioning/Maintenance)*.
*   **Platform:** Web-based Application (PHP Native + Python Service).

**Notes:**
Agar penelitian terarah, saya membatasi pada Unit AMC Halim. Algoritma yang dipakai spesifik Random Forest. Datasetnya real dari operasional 3 bulan terakhir. Dan yang paling penting, kita fokus memprediksi 20 stand aktif di Main Apron, bukan seluruh 83 stand yang ada, karena sebagian besar hanya untuk parkir inap.

## Slide 9: Tujuan Penelitian
### Goals

> **"Merancang dan mengembangkan sistem informasi AMC terintegrasi yang cerdas."**

*   ✅ **Substitusi Manual:** Mengganti Google Sheets dengan platform database terpusat.
*   ✅ **Visualisasi:** Menyediakan peta apron *real-time* (Hijau: Avail, Merah: Occupied).
*   ✅ **Prediksi Cerdas:** Mengimplementasikan *Random Forest* untuk rekomendasi stand otomatis.
*   ✅ **Efisiensi:** Mempercepat keputusan dari 2 menit menjadi **≤ 5 detik**.

**Notes:**
Tujuannya jelas: Mengganti pencatatan manual, memberikan "mata" bagi operator lewat visualisasi peta, dan memberikan "otak" buatan lewat rekomendasi stand otomatis yang super cepat.

## Slide 10: Manfaat Penelitian
### Kontribusi

| Bagi Instansi (Bandara) | Bagi Akademisi | Bagi Penulis |
| :--- | :--- | :--- |
| Efisiensi pencatatan & *paperless* | Studi kasus nyata ML di aviasi | Implementasi teori ke praktik |
| Rekomendasi berbasis data | Referensi integrasi PHP-Python | Portofolio *Full-stack development* |
| Minimalisasi *human error* | Pengayaan literatur SI Bandara | Kompetensi analisis sistem kompleks |

**Notes:**
Bagi bandara, ini berarti efisiensi dan data yang lebih akurat. Bagi kampus, ini adalah studi kasus penerapan ML di industri aviasi yang jarang dibahas. Bagi saya, ini adalah pembuktian kompetensi full-stack development.

## Slide 11: Tinjauan Pustaka & Research Gap
### Posisi Penelitian

| Peneliti | Fokus Studi | Kekurangan (Gap) |
| :--- | :--- | :--- |
| **Ulfa et al. (2023)** | SI Parking Stand (Padang) | Tidak ada prediksi cerdas & visualisasi statis |
| **Jumlad & Nurhalisa (2024)** | SI AMC (Makassar) | Keputusan tetap manual, hanya digitalisasi form |
| **Suryaman & Wang (2022)** | Data Mining Slot Time | Fokus jadwal penerbangan, bukan fisik stand |
| **Penelitian Ini (2025)** | **SI AMC + ML Prediction** | **Menggabungkan Digitalisasi, Visualisasi Real-time, & AI Decision Support** |

**Notes:**
Banyak penelitian sebelumnya hanya sebatas "memindahkan kertas ke komputer" (digitalisasi). Penelitian ini mengisi kekosongan tersebut dengan menambahkan lapisan kecerdasan buatan (AI) dan visualisasi real-time yang belum ada di penelitian sejenis untuk studi kasus bandara di Indonesia.

## Slide 12: Landasan Teori - Random Forest
### Mengapa Random Forest?

[Visualization Placeholder: Random Forest Ensemble Architecture]

Menurut Berriri (2021) dan Ibrahim (2022), Random Forest unggul karena:
1.  **Ensemble Learning:** Menggabungkan ratusan *Decision Trees* (Voting mechanism).
2.  **Robustness:** Tahan terhadap *overfitting* dibanding single Decision Tree.
3.  **Multiclass Capability:** Sangat cocok untuk klasifikasi 20 target stand berbeda.
4.  **Handling Imbalance:** Bisa menangani stand favorit vs stand jarang pakai dengan parameter `class_weight='balanced'`.

**Notes:**
Saya memilih Random Forest bukan tanpa alasan. Algoritma ini bekerja dengan prinsip "wisdom of crowds"—menggabungkan ratusan pohon keputusan. Ini sangat cocok untuk data penerbangan yang berisik dan tidak seimbang, di mana beberapa stand jauh lebih sering dipakai daripada yang lain.

## Slide 13: Landasan Teori - Top-K Prediction
### Strategi Decision Support

Sistem tidak memaksa "Harus Stand A1", tapi memberikan opsi:
**Input:** B738 (Citilink) - Domestic

**Output (Top-3 Probability):**
*   🥇 **Stand A3 (45%)** - *Recommended*
*   🥈 **Stand A2 (23%)** - *Alternative 1*
*   🥉 **Stand B7 (15%)** - *Alternative 2*

**Alasan Top-3:**
*   Memberi fleksibilitas operasional (misal A3 sedang maintenance mendadak).
*   Meningkatkan *usability* sistem bagi operator.
*   Target akurasi lebih realistis dan bermanfaat (**80% Top-3** vs 36% Top-1).

**Notes:**
Sistem ini dirancang sebagai *Decision Support System*, bukan robot otomatis. Jadi outputnya adalah "Top-3 Rekomendasi". Ini memberikan operator fleksibilitas. Jika pilihan pertama tidak bisa dipakai karena ada tumpahan oli misalnya, operator langsung punya opsi kedua dan ketiga yang valid secara data.

## Slide 14: Metodologi - CRISP-DM
### Cross-Industry Standard Process for Data Mining

1.  **Business Understanding:** Analisis masalah operasional AMC Halim.
2.  **Data Understanding:** Eksplorasi dataset logbook manual (4069 records).
3.  **Data Preparation:** Cleaning, Normalisasi, Encoding.
4.  **Modeling:** Training Random Forest dengan *Grid Search*.
5.  **Evaluation:** Mengukur Top-3 Accuracy & Confusion Matrix.
6.  **Deployment:** Integrasi model ke aplikasi Web PHP.

*(Referensi: Andrade-Arenas et al., 2024)*

**Notes:**
Saya menggunakan standar industri CRISP-DM. Dimulai dari memahami bisnis AMC, membersihkan 4000+ data manual, melatih model, hingga akhirnya mendeploy-nya ke dalam aplikasi web yang bisa dipakai user.

## Slide 15: Data Understanding
### Profil Dataset (Mei - Juli 2025)

*   **Total Records:** 4,069 pergerakan pesawat.
*   **Sumber:** Logbook manual AMC Halim Perdanakusuma.
*   **Distribusi Kategori:**
    *   📊 Commercial: 60.97%
    *   📊 Charter/VIP: 28.09%
    *   📊 Cargo: 10.94%
*   **Atribut Input (Features):**
    1.  `Aircraft Type` (Tipe Pesawat)
    2.  `Operator/Airline` (Maskapai)
    3.  `Category` (Jenis Penerbangan)
    4.  `Time of Day` (Jam Operasional)

**Notes:**
Datanya cukup representatif dengan dominasi penerbangan komersial sekitar 60%. Namun porsi Charter 28% cukup besar, menunjukkan karakteristik unik Halim sebagai bandara VIP. Inilah tantangan modelnya: memprediksi pola Charter yang acak.

## Slide 16: Data Preparation & Feature Engineering
### Preprocessing Pipeline

1.  **Data Cleaning:** 
    *   Menghapus data duplikat & *missing values*.
    *   Standarisasi nama maskapai (e.g., "Citilink" vs "QG").
2.  **Feature Engineering (Python `predict.py`):**
    *   `determine_aircraft_size()`: Mengidentifikasi pesawat kecil (Cessna/Pilatus) untuk stand A0.
    *   `determine_airline_tier()`: High/Medium/Low frequency airline.
    *   `get_stand_zone()`: Mapping kategori ke area apron (Kargo -> Left Apron).
3.  **Encoding:** 
    *   Label Encoding untuk data kategorial (disimpan di `encoders_redo.pkl`).

**Notes:**
Data mentah dari Excel sangat kotor. Saya melakukan pembersihan masif. Saya juga membuat fitur baru secara *programmatic*, misalnya otomatis mendeteksi apakah pesawat itu "Kecil" (masuk A0) atau "Besar". Ini logika bisnis yang ditanamkan ke dalam preprocessing.

## Slide 17: Modeling - Konfigurasi Random Forest
### Hyperparameter Tuning

Model dilatih menggunakan library **Scikit-Learn** dengan konfigurasi terbaik hasil *Grid Search*:

```python
best_params = {
    'n_estimators': 100,          # Jumlah pohon keputusan
    'class_weight': 'balanced_subsample', # Handle imbalance data
    'min_samples_leaf': 5,        # Mencegah overfitting
    'min_samples_split': 5,
    'max_depth': None
}
```

*   **Output Artifacts:**
    *   `parking_stand_model_rf_redo.pkl` (Model File)
    *   `encoders_redo.pkl` (Encoding Dictionary)

**Notes:**
Setelah ujicoba ratusan kombinasi parameter, konfigurasi inilah yang terbaik. Penggunaan `class_weight='balanced_subsample'` sangat krusial untuk memastikan stand yang jarang dipakai (seperti stand isolasi) tetap bisa diprediksi oleh model.

## Slide 18: Evaluasi Model
### Performance Metrics (from `results_summary_redo.json`)

*   **✅ Top-3 Accuracy: 80.15%** (Melampaui target 80%)
*   **Top-5 Accuracy:** 98.94%
*   **Top-1 Accuracy:** 36.12%
*   **Feature Importance Tertinggi:**
    1.  `Stand Zone` (37.5%) - Lokasi berdasarkan kategori.
    2.  `Airline` (20.8%) - Preferensi maskapai.
    3.  `Aircraft Type` (20.3%) - Ukuran pesawat.

**Notes:**
Hasilnya sangat memuaskan. Akurasi Top-3 mencapai 80.15%, artinya dalam 8 dari 10 kasus, stand yang benar ada di 3 rekomendasi teratas sistem. Fitur yang paling berpengaruh ternyata adalah `Stand Zone`, yang valid secara operasional karena Kargo pasti di apron kiri dan Komersial di kanan.

## Slide 19: Metode Pengembangan Sistem
### Agile Scrum (Individual Adaptation)

*   **Product Owner:** Dosen Pembimbing & Supervisor AMC.
*   **Scrum Master/Dev:** Peneliti.
*   **Sprint Cycle:** 1 Minggu.
    *   *Sprint 1:* Database & Basic CRUD.
    *   *Sprint 2:* Python ML Module Development.
    *   *Sprint 3:* Integrasi PHP-Python (`proc_open`).
    *   *Sprint 4:* Dashboard & Apron Map Visualization.
    *   *Sprint 5:* UAT & Refinement.

**Notes:**
Saya mengadaptasi Scrum untuk kerja individual. Sprint paling menantang adalah Sprint 3, saat harus mengawinkan PHP backend dengan Python machine learning engine agar bisa berkomunikasi lancar.

## Slide 20: Arsitektur Sistem
### Three-Tier Architecture

1.  **Presentation Layer (Frontend)**
    *   HTML5, Tailwind CSS, Vanilla JS (AJAX).
    *   **Apron Map:** Visual Grid System.

2.  **Logic Layer (Backend)**
    *   **PHP 8.3** (Native MVC Framework).
    *   **Controller:** `ApronController.php`.
    *   **IPC:** `proc_open()` untuk eksekusi script Python.

3.  **Data Layer**
    *   **MySQL/MariaDB:** 13 Tabel Relasional.
    *   **ML Engine:** Python 3 + Scikit-Learn (`predict.py`).

**Notes:**
Sistem ini menggunakan arsitektur 3-tier. Yang unik adalah jembatan antara PHP dan Python. Frontend meminta data ke PHP, PHP "menyuruh" Python berpikir, Python mengembalikan hasil prediksi, lalu PHP menyajikannya ke user.

## Slide 21: Integrasi Teknis (Critical Flow)
### Alur Komunikasi PHP - Python

[Diagram Placeholder: Sequence Diagram PHP to Python]

1.  **User Request:** Klik "Recommend" di form input.
2.  **PHP (ApronController):**
    *   Validasi input.
    *   Encode JSON payload: `{"aircraft": "B738", "airline": "Batik"}`.
    *   Call `proc_open("python predict.py")`.
3.  **Python (`predict.py`):**
    *   Load `model.pkl`.
    *   Lakukan inferensi.
    *   Print JSON result ke `stdout`.
4.  **PHP:** Tangkap output JSON, simpan ke database, kirim ke Frontend.

**Time Cost:** < 0.5 detik untuk eksekusi Python (Model di-load cepat dari disk cache).

**Notes:**
Ini adalah "jantung" teknis sistem. PHP mengirim data via pipa (stdin) ke Python. Python memproses dan membalas via pipa (stdout). Proses ini sangat cepat, kurang dari setengah detik, sehingga user merasa sistem bekerja instan.

## Slide 22: Use Case Diagram
### Aktor Sistem

1.  **👨‍✈️ Operator (AMC Staff)**
    *   Input pergerakan pesawat (*Movement*).
    *   **Request Recommendation (ML)**.
    *   Update status RON.
2.  **👮 Admin**
    *   Manajemen User & Role.
    *   Konfigurasi Master Data.
3.  **👀 Viewer (ATC/AOCC)**
    *   Melihat status Apron (Read-only).
    *   Monitoring Dashboard.

**Notes:**
Ada 3 aktor utama. Operator adalah user primer yang menggunakan fitur ML. Viewer adalah pihak eksternal seperti ATC yang hanya butuh melihat "Apron mana yang kosong?" tanpa bisa mengubah data.

## Slide 23: Database Schema (ERD)
### Struktur Data (13 Tabel)

*   **Core Tables:**
    *   `aircraft_movements`: Tabel transaksi utama.
    *   `stands`: Master data 83 parking stand.
    *   `aircraft_details`: Database tipe pesawat & maskapai.
*   **ML Support Tables:**
    *   `ml_prediction_log`: Menyimpan setiap request & hasil prediksi untuk audit.
    *   `ml_model_versions`: Version control untuk file model .pkl.
    *   `airline_preferences`: Menyimpan skor preferensi historis.

**Notes:**
Database dirancang untuk mendukung operasional sekaligus audit AI. Tabel `ml_prediction_log` sangat penting karena mencatat "Apa yang diprediksi sistem" vs "Apa yang akhirnya dipilih manusia", yang berguna untuk *retraining* model di masa depan.

## Slide 24: Rancangan Antarmuka
### 1. Apron Map Visualizer
*   Grid layout merepresentasikan posisi fisik stand.
*   **Color Coding:**
    *   🔴 Merah: Occupied
    *   🟢 Hijau: Available
    *   🟡 Kuning: Allocated/Booking

### 2. Prediction Modal
*   Muncul saat input data baru.
*   Menampilkan 3 kartu rekomendasi dengan skor probabilitas.
*   Tombol "Select" untuk langsung mengisi form.

**Notes:**
Tampilan didesain seintuitif mungkin. Peta apron menggunakan kode warna traffic light yang universal. Rekomendasi muncul dalam bentuk kartu yang mudah diklik, mempercepat kerja operator drastis.

## Slide 25: Rencana Pengujian
### Testing Strategy

1.  **Black-Box Testing:**
    *   Validasi fungsionalitas Input, Edit, Delete.
    *   Uji coba role access (Viewer tidak bisa edit).
2.  **Performance Testing:**
    *   Mengukur *Response Time* prediksi (Target ≤ 5s).
    *   Stress test database query.
3.  **Accuracy Testing:**
    *   Membandingkan hasil prediksi dengan data aktual baru.
    *   Validasi Top-3 Accuracy konsisten di ≥ 80%.

**Notes:**
Pengujian akan mencakup fungsional, performa, dan akurasi model. Kita harus memastikan sistem tidak hanya benar secara logika, tapi juga cepat dan akurat dalam prediksinya.

## Slide 26: Kontribusi & Originalitas
### Kebaruan Penelitian

1.  **First Integration:** Implementasi pertama Machine Learning Random Forest untuk alokasi stand di Bandara Halim.
2.  **Hybrid Decision Support:** Menggabungkan *Rule-based* (ukuran pesawat) dengan *ML-based* (pola historis).
3.  **Legacy Modernization:** Strategi modernisasi sistem manual tanpa mengubah total infrastruktur IT yang ada (Low Resource).

**Notes:**
Penelitian ini unik karena menerapkan ML di lingkungan bandara militer/sipil hybrid seperti Halim. Pendekatannya juga hybrid: aturan keras seperti "Pesawat besar tidak boleh di stand kecil" dijaga oleh kode, sementara preferensi halus seperti "Maskapai X suka di stand Y" ditangani oleh AI.

## Slide 27: Jadwal Penelitian
### Timeline

*   **Bulan 1:** Pengumpulan Data & Analisis Proses Bisnis.
*   **Bulan 2:** Preprocessing Data & Training Model ML.
*   **Bulan 3:** Pengembangan Aplikasi Web (Backend & Frontend).
*   **Bulan 4:** Integrasi Sistem, Testing, & Perbaikan.
*   **Bulan 5:** Penulisan Laporan Akhir & Sidang.

**Notes:**
Rencana kerja 5 bulan, dengan porsi terbesar di bulan ke-2 dan 3 untuk pematangan model AI dan koding aplikasi.

## Slide 28: Penutup
### Kesimpulan Proposal

*   Masalah inefisiensi AMC Halim solusinya adalah **Digitalisasi + Kecerdasan Buatan**.
*   Sistem usulan menawarkan kecepatan (**<5 detik**), akurasi (**>80%**), dan transparansi data.
*   Siap untuk dilanjutkan ke tahap pengembangan skripsi.

**Notes:**
Sebagai penutup, sistem ini bukan sekadar aplikasi pencatat, tapi solusi cerdas untuk masalah nyata di lapangan. Dengan dukungan teknologi tepat guna, efisiensi operasional AMC Halim bisa meningkat signifikan.

## Slide 29: Referensi Utama
1.  **Berriri, H.** (2021). *Multi-Class Assessment Based on Random Forests*.
2.  **Ibrahim, S.** (2022). *Evolution of Random Forest from Decision Tree*.
3.  **Tashtoush, Y.** (2021). *The Role of Information Systems Capabilities*.
4.  **Andrade-Arenas et al.** (2024). *A comparative analysis of data mining methodology CRISP DM*.
5.  **Dokumen SOP Unit AMC** Bandara Halim Perdanakusuma (2024).

**Notes:**
Berikut adalah referensi utama yang menjadi landasan teori penelitian ini.

## Slide 30: Terima Kasih

# Terima Kasih
**Mohon Saran dan Masukan**

**Syarif Adrian Mangarja Lubis**  
221051013

---
*“Innovation is the ability to see change as an opportunity - not a threat.”*

**Notes:**
Terima kasih atas perhatian Bapak/Ibu. Saya sangat terbuka untuk saran dan masukan demi penyempurnaan penelitian ini. Wassalamu’alaikum Wr. Wb.

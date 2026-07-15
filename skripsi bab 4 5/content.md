### ===== BAB IV: HASIL DAN PEMBAHASAN =====

#### 4.1.1 Lingkungan Implementasi

Sebelum memaparkan hasil penelitian secara mendalam, perlu dijelaskan terlebih dahulu lingkungan teknis di mana sistem diimplementasikan guna memberikan gambaran mengenai infrastruktur pendukung operasionalnya. Sistem informasi AMC (*Aircraft Movement Control*) berbasis web ini dikembangkan menggunakan *stack* teknologi yang berfokus pada efisiensi eksekusi dan modularitas arsitektur. Sisi *backend* dibangun menggunakan bahasa pemrograman PHP versi 8.3.25 dengan menerapkan pola desain *custom* MVC (*Model-View-Controller*) yang disusun secara mandiri tanpa bantuan *framework* eksternal untuk menjamin performa maksimal pada tingkat *engine*. Untuk manajemen basis data relasional, digunakan MariaDB versi 10.4.32. Komponen kecerdasan buatan dijalankan pada modul terpisah menggunakan Python versi 3.11 dengan dukungan *library* *scikit-learn* versi 1.4.2, dipadukan dengan NumPy versi 1.26.4, Pandas versi 2.2.2, dan Joblib versi 1.4.0 untuk pemrosesan algoritma *Random Forest*.

Integrasi antara logika bisnis pada PHP dan modul *machine learning* dilakukan melalui mekanisme komunikasi *inter-process* menggunakan fungsi `proc_open()`. Mekanisme ini memfasilitasi pertukaran *JSON payload* secara dua arah melalui jalur *stdin* dan *stdout*, yang memungkinkan sistem web melakukan inferensi model secara dinamis tanpa memerlukan arsitektur mikroservis yang kompleks. Seluruh proses pengembangan dan pengujian dilakukan sepenuhnya pada lingkungan *localhost* menggunakan *web server* Apache, mengingat cakupan penelitian ini dibatasi pada validasi fungsionalitas dan akurasi model dalam lingkungan terkontrol. Implementasi antarmuka pengguna menggunakan teknologi web standar yaitu HTML, CSS, dan JavaScript *vanilla* guna meminimalkan dependensi sisi klien. Penjelasan mengenai infrastruktur ini menjadi landasan teknis sebelum melangkah pada pembahasan mengenai antarmuka dan fitur utama yang tersedia dalam sistem.

[TABEL 4.1: Spesifikasi Lingkungan Implementasi]
| Komponen | Versi / Spesifikasi | Keterangan |
| :--- | :--- | :--- |
| Bahasa Pemrograman Backend | PHP 8.3.25 | Custom MVC Pattern |
| Database Management System | MariaDB 10.4.32 | Relational Database Storage |
| Bahasa Pemrograman Machine Learning | Python 3.11 | Modul Inferensi Terpisah |
| Machine Learning Library | scikit-learn 1.4.2 | Implementasi Random Forest |
| Data Processing Library | Pandas 2.2.2 & NumPy 1.26.4 | Manipulasi Data dan Operasi Numerik |
| Serialization Library | Joblib 1.4.0 | Penyimpanan Model ML |
| Frontend Stack | HTML5, CSS3, JS Vanilla | Tanpa Framework JavaScript |
| Mekanisme Integrasi | `proc_open()` | JSON via stdin/stdout |
| Lingkungan Server | Localhost (XAMPP) | Apache 2.4 |

#### 4.1.2 Antarmuka dan Fitur Utama Sistem

**4.1.2.1 Modul Dashboard Analitik**
Modul Dashboard Analitik berfungsi sebagai pusat pemantauan visual yang menyajikan ringkasan statistik operasional apron secara *real-time*. Operator berinteraksi dengan modul ini melalui tampilan grafik interaktif dan kartu indikator yang menunjukkan jumlah pergerakan pesawat harian serta tingkat okupansi *stand*. Kontribusi spesifik modul ini adalah menggantikan mekanisme pemantauan manual yang sebelumnya mengandalkan papan tulis fisik (*whiteboard*) atau *spreadsheet* yang sering kali tidak sinkron dengan kondisi aktual di lapangan. Dengan adanya dashboard terpusat, koordinasi antar-unit menjadi lebih responsif karena informasi ketersediaan parkir tersedia secara instan, sehingga mempercepat waktu tanggap terhadap perubahan situasi operasional yang mendadak.

**4.1.2.2 Modul Master Table Pergerakan Pesawat**
Modul Master Table merupakan antarmuka utama untuk pengelolaan data transaksional seluruh pergerakan pesawat yang mencakup waktu *on-block* dan *off-block*. Operator berinteraksi dengan modul ini melalui tampilan tabel dinamis yang mendukung fitur penyuntingan langsung, pencarian cepat, serta validasi data otomatis guna menjaga integritas rekaman pergerakan. Implementasi modul ini memberikan efisiensi signifikan dibandingkan kondisi manual yang sangat bergantung pada catatan jadwal berbasis kertas (*hardcopy*). Penggunaan sistem digital ini meminimalkan risiko redundansi data dan mempermudah pelacakan riwayat penerbangan, yang sebelumnya memerlukan waktu lama untuk pencarian dokumen fisik di gudang arsip bandara.

**4.1.2.3 Modul Visualisasi Apron Real-time**
Modul Visualisasi Apron menyediakan representasi grafis spasial dari posisi 20 *parking stands* beserta status huniannya secara aktual di layar monitor. Operator dapat berinteraksi dengan mengeklik ikon *stand* untuk melihat detail pesawat atau melakukan pembaruan status pergerakan pesawat secara intuitif. Sebelumnya, operator harus melakukan visualisasi secara mental berdasarkan catatan koordinat teks, yang sering kali memicu kesalahan spasial dalam penempatan pesawat saat lalu lintas udara padat. Modul ini meningkatkan *situational awareness operator secara drastis, sehingga potensi konflik alokasi *stand* akibat keterbatasan ruang dapat dideteksi lebih dini melalui representasi visual yang akurat dan interaktif.

**4.1.2.4 Modul Sistem Prediksi Alokasi Stand**
Modul Sistem Prediksi merupakan fitur inti yang mengintegrasikan kecerdasan buatan untuk memberikan rekomendasi *parking stand* terbaik menggunakan algoritma *Random Forest*. Operator berinteraksi dengan menekan tombol rekomendasi pada jadwal penerbangan tertentu, dan sistem akan menyajikan *Top-3* pilihan *stand* berdasarkan probabilitas tertinggi dalam waktu respons sekitar 4 detik. Fitur ini mengubah paradigma pengambilan keputusan yang sebelumnya bersifat subjektif berdasarkan intuisi operator senior menjadi keputusan berbasis data (*data-driven*). Dengan akurasi *Top-3 Acc* mencapai 80.15%, modul ini mampu mengurangi beban kognitif operator dan memastikan alokasi yang lebih konsisten sesuai dengan preferensi maskapai dan tipe pesawat.

**4.1.2.5 Modul Pelaporan Operasional Otomatis**
Modul Pelaporan Operasional berfungsi untuk menggenerasi laporan harian, bulanan, maupun laporan khusus seperti *charter log* secara otomatis dalam format CSV. Operator berinteraksi dengan menentukan rentang waktu laporan dan kriteria filter, kemudian sistem akan mengolah data riwayat pergerakan secara instan untuk diunduh. Pada kondisi manual, pembuatan laporan membutuhkan waktu berhari-hari karena petugas harus merekapitulasi data fisik secara manual dari berbagai logbook. Modul ini memberikan kontribusi efisiensi administrasi yang luar biasa dengan menyediakan data yang akurat dan transparan dalam hitungan detik, memudahkan pihak manajemen bandara dalam melakukan evaluasi kinerja operasional secara rutin.

**4.1.2.6 Modul Manajemen Keamanan dan Audit Log**
Modul Manajemen Keamanan mengelola hak akses pengguna berdasarkan peran (*Role-Based Access Control*) serta mencatat setiap aktivitas melalui fitur *audit log*. Administrator berinteraksi dengan modul ini untuk mengatur akun personel dan memantau setiap perubahan data kritis yang terjadi dalam sistem guna mencegah manipulasi informasi. Kontribusi utama modul ini adalah penyediaan jejak audit digital yang tidak tersedia pada sistem manual berbasis kertas. Peningkatan akuntabilitas ini memastikan bahwa setiap tindakan operasional dapat dipertanggungjawabkan, sekaligus melindungi integritas data operasional dari potensi kesalahan input yang tidak disengaja oleh pihak yang tidak memiliki wewenang akses.

---

### JAWABAN C-01
| Komponen | Versi / Spesifikasi | Keterangan |
|---|---|---|
| PHP | 8.3.25 | Backend, custom MVC |
| MariaDB/MySQL | 10.4.32 | Database server |
| Python | 3.11 | Modul Inferensi Terpisah |
| Scikit-learn| 1.4.2 | Implementasi Random Forest |
| Pandas | 2.2.2 | Manipulasi Data |
| NumPy | 1.26.4 | Komputasi Numerik |
| Joblib | 1.4.0 | Model Serialization |

### JAWABAN C-02
- **Algoritma Utama**: Random Forest Classifier
- **Teknik Optimasi**: GridSearchCV (72 konfigurasi × 5-fold cross-validation = 360 training runs)
- **Hyperparameter Terbaik (Model RF_REDO)**:
  - 
_estimators: 100
  - max_depth: None
  - min_samples_leaf: 5
  - min_samples_split: 5
  - class_weight: balanced_subsample
  - criterion: gini

### JAWABAN C-03
- **Top-1 Accuracy**: 36.13%
- **Top-3 Accuracy**: 80.15% (Memenuhi target akurasi operasional ≥ 80%)
- Model memiliki 17 kelas target (Parking Stand).

### JAWABAN C-04
Berdasarkan file dataset historis parking_history_clean.csv:
- Total Record: 4069 baris
- Total Kelas Target (Parking Stand): 17 uniques. Distribusi terbanyak: B1 (11.1%), A3 (10.0%), B2 (8.9%).
- Variasi Fitur: 55 tipe pesawat (Highest: A 320), 39 maskapai penerbangan (Highest: BATIK AIR), 3 kategori utama (Komersial, Charter, Cargo).

### JAWABAN C-05
Fitur yang digunakan oleh model beserta nilai pentingnya (*Feature Importances*):
1. **Stand Zone** (stand_zone_enc): 37.58% (Paling penting)
2. **Operator Airline** (operator_airline_enc): 20.83%
3. **Aircraft Type** (ircraft_type_enc): 20.36%
4. **Category** (category_enc): 10.31%
5. **Aircraft Size** (ircraft_size_enc): 8.05%
6. **Airline Tier** (irline_tier_enc): 2.86%

### JAWABAN C-06
Terdapat 13 tabel pada schema database mc. Dua tabel transaksional yang paling kritikal:
- **ircraft_movements**: Menyimpan data seluruh riwayat pergerakan pesawat (on-block, off-block, stand, type, airline).
- **ml_prediction_log**: Mencatat setiap aktivitas probabilitas prediksi ML yang dihasilkan sistem untuk keperluan monitoring *model drifting* dan analitik.

### JAWABAN C-07
Validasi temporal memastikan integritas rentang waktu data pergerakan pesawat:
- **Frontend (pron.js)**: Mengeksekusi fungsi extractTimeToMinutes untuk peringatan dini. Jika *Off-block* < *On-block*, menampilkan pesan alert kepada operator.
- **Backend (AircraftMovementRepository.php)**: Metode evaluateInputWarnings memanfaatkan evaluasi waktu isOffBlockEarlierThanOnBlock untuk memvalidasi dan menandai input yang anomali di level *server-side*.

### JAWABAN C-08
Integrasi PHP-Python menghindari dependensi eksternal (API / Flask) menggunakan mekanisme *Inter-process Communication* (IPC):
- **Teknis**: Eksekusi skrip Python melalui fungsi proc_open() di ApronController.php.
- **Data Exchange**: Proses pengiriman *JSON Payload* dilakukan dua arah via saluran STDIN dan STDOUT.
- **Timeout**: Batasan eksekusi maksimal adalah 6 detik sebelum proses dihentikan otomatis.

### JAWABAN C-09
Waktu Eksekusi (*Response Time*):
- **Cold Start (Tanpa Cache)**: ~4.0 detik. Beban komputasi tinggi pada inisialisasi lingkungan Python dan memuat file pickle model berukuran 2.57 MB.
- **Warm / Cached**: ~0.5 detik melalui performa strategi *Caching 2-level* (PHP FileCache kedaluwarsa 5 menit, dan cache hierarki modul Python di memori).

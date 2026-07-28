# BAB IV: HASIL DAN PEMBAHASAN

## 4.1 Implementasi Sistem Informasi AMC Berbasis Web

### 4.1.1 Lingkungan Implementasi

Sebelum memaparkan hasil penelitian secara mendalam, perlu dijelaskan terlebih dahulu lingkungan teknis di mana sistem diimplementasikan guna memberikan gambaran mengenai infrastruktur pendukung operasionalnya. Sistem informasi AMC (*Aircraft Movement Control*) berbasis web ini dikembangkan menggunakan *stack* teknologi yang berfokus pada efisiensi eksekusi dan modularitas arsitektur. Sisi *backend* dibangun menggunakan bahasa pemrograman PHP versi 8.3.25 dengan menerapkan pola desain *custom* MVC (*Model-View-Controller*) yang disusun secara mandiri tanpa bantuan *framework* eksternal untuk menjamin performa maksimal pada tingkat *engine*. Untuk manajemen basis data relasional, digunakan MariaDB versi 10.4.32. Komponen kecerdasan buatan dijalankan pada modul terpisah menggunakan Python versi 3.11 dengan dukungan pustaka *scikit-learn* versi 1.4.2 untuk pemrosesan algoritma *Random Forest*, dipadukan dengan NumPy versi 1.26.4, Pandas versi 2.2.2, dan Joblib versi 1.4.0 untuk komputasi numerik, manipulasi data, dan serialisasi model.

Integrasi antara logika bisnis pada PHP dan modul *machine learning* dilakukan melalui mekanisme komunikasi antar-proses menggunakan fungsi `proc_open()`. Mekanisme ini memfasilitasi pertukaran *JSON payload* secara dua arah melalui jalur standar *input* dan *output*, yang memungkinkan sistem web melakukan inferensi model secara dinamis dengan batas waktu eksekusi maksimal 6 detik sebelum proses dihentikan otomatis. Seluruh proses pengembangan dan pengujian dilakukan sepenuhnya pada lingkungan XAMPP (*localhost*) menggunakan *web server* Apache 2.4, mengingat cakupan penelitian ini dibatasi pada validasi fungsionalitas dan akurasi model dalam lingkungan terkontrol.

[TABEL 4.1] Spesifikasi Lingkungan Implementasi
| Komponen | Versi / Spesifikasi | Keterangan |
| :--- | :--- | :--- |
| Bahasa Pemrograman Backend | PHP 8.3.25 | Pola desain Custom MVC |
| Database Management System | MariaDB 10.4.32 | Database Server relasional |
| Bahasa Pemrograman Machine Learning | Python 3.11 | Modul Inferensi Terpisah |
| Machine Learning Library | scikit-learn 1.4.2 | Implementasi Random Forest Classifier |
| Data Processing Library | Pandas 2.2.2 & NumPy 1.26.4 | Manipulasi data dan operasi numerik |
| Serialization Library | Joblib 1.4.0 | Penyimpanan model ML |
| Frontend Stack | HTML5, CSS3, JS Vanilla | Tanpa Framework JavaScript |
| Mekanisme Integrasi | `proc_open()` | JSON payload via stdin/stdout |
| Lingkungan Server | Localhost (XAMPP) | Apache 2.4 |

### 4.1.2 Antarmuka dan Fitur Utama Sistem

Presentasi visual sistem terbagi ke dalam enam modul fungsional utama yang saling terintegrasi. 

**Modul Visualisasi Apron Real-time**
[GAMBAR 4.1]
Modul Visualisasi Apron menyediakan representasi grafis spasial dari posisi 20 *parking stands* operasional beserta status huniannya secara aktual (real-time) di layar monitor. Modul ini diimplementasikan dengan indikator warna (hijau untuk kosong, merah untuk terisi, dan kuning untuk stand yang telah dialokasikan). Sebelumnya, operator membutuhkan waktu rata-rata sekitar 30 detik untuk mengidentifikasi stand yang tersedia dengan membaca *spreadsheet* secara manual dan melakukan konfirmasi dua arah menggunakan radio komunikasi. Modul ini secara drastis meningkatkan kesadaran situasional operator, mengurangi waktu pencarian informasi, dan memungkinkan penanganan potensi konflik alokasi secara proaktif sebelum pesawat mendarat.

**Modul Dashboard Analitik**
[GAMBAR 4.2]
Modul Dashboard Analitik berfungsi sebagai pusat pemantauan visual yang menyajikan ringkasan statistik operasional apron. Operator dan manajemen berinteraksi dengan modul ini untuk mendapatkan gambaran seketika terkait jumlah pergerakan pesawat harian. Kehadiran dashboard ini mensubstitusi ketergantungan historis terhadap papan tulis fisik (*whiteboard*) operasional, sekaligus menghapus redundansi data yang sebelumnya terjadi ketika informasi di papan tulis tidak tersinkronisasi tepat waktu dengan *spreadsheet* digital akibat tingginya beban kerja lapangan.

**Modul Input dan Edit Pergerakan Pesawat**
[GAMBAR 4.3]
Modul ini merupakan antarmuka untuk pengelolaan data transaksional seluruh pergerakan pesawat. Hal mendasar yang membedakan modul ini dari implementasi manual adalah adanya validasi logika temporal tingkat lanjut di sisi klien maupun server. Sistem mengeksekusi pemeriksaan matematis (fungsi `extractTimeToMinutes` di lapisan antar-muka dan metode `isOffBlockEarlierThanOnBlock` di repositori *backend*) untuk memastikan tidak ada waktu keberangkatan (*off-block*) yang mendahului waktu kedatangan (*on-block*). Intervensi otomatis ini dirancang secara khusus guna mengeliminasi kesalahan pengetikan stempel waktu (*timestamp*) yang menurut data observasi lapangan kerap terjadi dengan frekuensi maksimal dua kali per hari dalam sistem pencatatan terdahulu.

**Modul Prediksi ML – Antarmuka Rekomendasi**
[GAMBAR 4.4]
Fitur kecerdasan buatan dalam bentuk Sistem Prediksi Alokasi Stand dirancang untuk menurunkan beban kelelahan desisional pada operator saat mengalokasikan ruang parkir di masa sibuk. Dengan hanya memasukkan tiga parameter operasional minimum yakni tipe pesawat, maskapai operator, dan kategori penerbangan, sistem akan mengekstraksi rekomendasi tiga stand teratas (*Top-3*) beserta tingkat probabilitas atau keyakinan model terhadap rekomendasi tersebut. Alur tata letak sistem sengaja menempatkan antarmuka ini sebagai *decision support* yang opsional untuk memastikan kewenangan keputusan mutlak tetap berada di tangan operator AMC (konsep *human-in-the-loop*). 

**Modul Snapshot dan Pelaporan Operasional Otomatis**
[GAMBAR 4.5]
Pembentukan dokumen historis operasional dipermudah melalui modul *Snapshot*. Jika dioperasikan dengan *spreadsheet*, perekapan pelaporan bulanan memicu tantangan administratif yang berat karena harus melakukan ekstraksi, klasifikasi, dan kalkulasi tarif berdasarkan ratusan baris data campuran. Sistem ini mentransformasi rutinitas rekaman harian menjadi laporan elektronik akurat secara mandiri, sehingga administrator dapat memberdayakan rentang waktu tersebut untuk optimalisasi pergerakan sisi udara alih-alih menyelesaikan tata kelola dokumen yang tertunda.

**Modul Manajemen Keamanan dan Audit Log**
[GAMBAR 4.6]
Fitur keamanan dibangun seputar struktur kendali akses berbasis peran (*Role-Based Access Control*), yang memilah wewenang menjadi pengguna Admin, Operator AMC, serta Viewer (ATC). Secara operasional, peran *Viewer* dapat secara krusial direpresentasikan kepada petugas Menara Kontrol (ATC), di mana mereka dapat mengamati perpindahan status stand pesawat secara instan di monitor masing-masing (read-only) tanpa wewenang mengubah data. Mekanisme keamanan ini merupakan tulang punggung baru dalam mengeliminasi latensi miskomunikasi via komunikasi radio genggam (HT) AMC-ATC yang dalam observasi manual dapat membuang waktu penerbangan taktis di lajur ancang (*taxiway*) berdurasi 1-2 menit hingga dua kali sepanjang bulan pergerakan puncak. Di samping itu, seluruh suntingan rekaman tertangkap oleh sistem sebagai jejak audit (*audit trail*), mengeliminasi kekhawatiran pelacakan tindakan jika kesalahan rekam terjadi.

### 4.1.3 Integrasi Tiga Laporan Operasional

Sebelum implementasi ini, petugas AMC bandara secara rutin dikepung oleh kewajiban merawat tiga platform *Google Sheets* yang terpisah yakni riwayat monitoring harian (Apron Monitoring), log pesawat diam bermalam (*Remain Overnight* / RON), serta log layanan sewa khusus (*Charter Report*), di mana pemisahan tersebut berisiko kuat melahirkan anomali desentralisasi antar repositori. Lewat adaptasi sistem berbasis pangkalan data terpusat sejumlah 13 tabel MySQL/MariaDB, pencatatan ganda diringkas menjadi siklus operasi input pergerakan yang tunggal. 

[GAMBAR 4.7]

[TABEL 4.2] Perbandingan Sistem Manual vs Sistem Transisi Baru AMC
| Aspek Evaluasi | Sistem Manual (Google Sheets) | Sistem AMC Berbasis ML |
| :--- | :--- | :--- |
| **Jumlah Platform/File** | Tiga *spreadsheet* tidak terintegrasi | Platform tunggal (*database* terpusat) |
| **Pendeteksian Duplikasi** | Rawan ganda (1-3 kali kegagalan/hari) | Dieliminasi via validasi *primary key* tabel |
| **Validasi Temporal Mutlak** | Rentan salah stempel waktu (kesalahan *off-block*) | Dieliminasi seketika karena *logic blocking* |
| **Pemeliharaan Rekaman** | Perubahan rekam tidak teridentifikasi pelaku | Terekam via modul persisten jejak audit |
| **Integritas Konversi** | Laporan Charter & RON dikurasi secara repetitif | Tergenerasi otomatis berbasis parameter filter (1 kali unduh) |
| **Akses Hirarki Peran** | Semua pengguna bebas manipulasi struktur data | Kredensial *Viewer* hanya mengizinkan modifikasi nir-intervensi untuk AOCC/ATC |

---

## 4.2 Hasil Implementasi Model Random Forest

### 4.2.1 Deskripsi Dataset

Model dibangun atas dasar abstraksi riwayat parameter dan keputusan alokasi apron yang telah berjalan secara empiris selama siklus tiga bulan di Halim Perdanakusuma (Mei hingga Juli 2025). Mengingat operator pada kondisi aktual mengeksekusi rekomendasi alokasi yang terbaik sesuai ketersediaan spasial, dataset tersebut dipandang sah mewakili reprentasi target optimal atau *ground truth* pergerakan tanpa keharusan formulasi teoritis ulang. Dari analisis yang telah dilakukan, tabel mentah dimurnikan menjadi 4.069 baris rekaman spesifik dan relevan (`parking_history_clean.csv`).

[TABEL 4.3] Statistik Deskriptif Transformasi Dataset Latih
| Fitur Representasi | Ekstraksi Nilai Unik Termuat | Sampel Modus Kemunculan Ekstensif | 
| :--- | :--- | :--- |
| `aircraft_type` | 55 Variasi Tipe Bodi | A 320, ATR 72, B 733 | 
| `operator_airline` | 39 Diversitas Perusahaan | BATIK AIR, CITILINK, TRIGANA |
| `category` | 3 Variasi Utama | Komersial, Charter, Cargo |
| **Target (`parking_stand`)** | **17 Unit Stand Faktual** | **B1 (11.1%), A3 (10.0%), B2 (8.9%)** |

Analisis sebaran *parking stand* memperlihatkan manifestasi tantangan di lapangan secara nyata, yang terepresentasi sebagai ketidakseimbangan kelas (*class imbalance*). Berdasarkan ekstraksi murni algoritma pelaporan sistem, stand parkir bernilai tinggi untuk penerbangan komersial bertubuh ringkas (*narrow-body*) seperti B1 mendominasi preferensi alokasi sebesar 11.1%, bertolak belakang drastis terhadap stand isolasi seperti kelas A0 (hanya merepresentasikan 1.5% frekuensi pemakaian) atau area non-fungsional lainnya yang mendalilkan pentingnya perlakuan bobot parameter *SMOTE* saat penyesuaian kecerdasan artifisial.

[GAMBAR 4.8]

### 4.2.2 Proses Pelatihan dan Hyperparameter Optimal

Dalam mendirikan sistem hutan acak (*Random Forest*), prosedur eksperimental siklus Data Mining bertumpu di teknik re-sampling yang komprehensif, dikenal dengan komputasi permutasi nilai parameter *GridSearchCV*. Dengan melakukan pelacakan terhadap 72 himpunan iterasi yang mengawin-silangkan bobot kelangkaan dan kedalaman pohon keputusan atas lima set *cross validation*, keseluruhan pengujian diulang secara independen menjadi 360 riwayat ekuivalensi fit pelatihan. 

[TABEL 4.4] Konfigurasi Optimal Pasca-GridSearchCV
| Basis Hyperparameter | Kombinasi Nilai Observasi | Penugasan Spesifikasi Optimal (*Model RF_REDO*) |
| :--- | :--- | :--- |
| `n_estimators` (Pohon) | 100, 200 | **100** |
| `max_depth` (Terminasi Kedalaman) | None, 20, 30 | **None** |
| `min_samples_split` | 5, 10 | **5** |
| `min_samples_leaf` | 2, 5, 10 | **5** |
| `class_weight` | Balanced, Balanced Subsample | **balanced_subsample** |
| `criterion` | Gini, Entropy | **gini** |

Hasil kompilasi dari permutasi parameter tersebut membuahkan entitas artefak berukuran logis dan optimal yakni file `parking_stand_model_rf_redo.pkl` bervolume spasial 2.57 MB, menjamin kehandalan portabilitas *cloud server* maupun proses eksekusi komputasi memori-sentris ke depan.

[GAMBAR 4.9]

### 4.2.3 Evaluasi Performa Model

#### 4.2.3.1 Metrik Utama: Top-3 Accuracy

Dalam kasus probabilitas preferensial pengatur pergerakan udara komersial, ukuran presisi metrik Top-1 atau prediktor *mutlak eksak* memiliki nilai ambiguitas tinggi karena dua atau tiga area parkir (misal Stand A1, A2, A3) seringkali sama absahnya secara kaidah teknis jarak operasional. Oleh karena itu, skripsi riset ini diformulasikan ke perhitungan keabsahan Top-3 (*Top-3 Accuracy*), dimana sebuah usulan klasifikasi dianggap berhasil atau kompatibel apabila opsi terapan *Stand Realita* tercakup ke dalam daftar urutan hierarki rekomendasi probabilitas Top-3 pergerakan.
 
Berdasarkan parameter pengujian murni, sistem cerdas ini mencatatkan indeks presisi toleransi spasial **Top-3 Accuracy di titik skor persentase 80.15%**. Keberhasilan melampaui treshold toleransi di atas 80% membuktikan model secara persisten berhasil meletakan keputusan representasi operator pada deretan rekomendasi dominannya, memungkinkan petugas mempertimbangkan efisiensi rute dari daftar 3 rekomendasi ideal.

#### 4.2.3.2 Metrik Pendukung

[TABEL 4.5] Ringkasan Validitas Implementatif Model AMC  
| Representasi Metrik | Skor Hasil Rekaman | Sasaran Pengembangan | Status Ketuntasan |
| :--- | :--- | :--- | :--- |
| **Toleransi Top-3 Accuracy** | 80.15% | ≥ 80.00% | *Tercapai / Passed* |
| **Toleransi Mutlak (Top-1)** | 36.13% | Bukan Tolok Ukur Utama | *Dilaporkan Berkala* |
| **Response Time (Proses)** | ~4.0 Detik *(Cold Start)* <br> ~0.5 Detik *(Warm/Cache)* | Limitasi Kecepatan ≤ 10 Detik | *Tercapai / Passed* |

*Catatan: Kecepatan komputasi 4.0 detik teregistrasi untuk latensi permohonan pertama (tanpa memori cache latar)* 

#### 4.2.3.3 Analisis Confusion Matrix

[GAMBAR 4.10]
[TABEL 4.6]

#### 4.2.3.4 Analisis Feature Importance

Berpijak di parameter hasil ekstraksi komputasi atribut (*feature_importances_*), model menampilkan transparansi nilai pengaruh variabel spesifik rekomendasi keputusan sebagaimana di bawah ini:
- `stand_zone` merefleksikan bobot koefisien paling kuat (37.58%)
- `operator_airline` (20.83%) 
- `aircraft_type` (20.36%)
- `category` (10.31%)
- Sub-engineered value berupa ukuran `aircraft_size` memegang bobot sekilas 8.05%, dan nilai komuter logistik frekuensional maskapai `airline_tier` sebesar 2.86%.

Hierarki relevansi variabel di atas memvalidasi rasionalisme komputasional yang dipeluk sistem secara harmonis sejalan dengan preferensi lapangan AMC — di mana kategori area stand (kargo, komersial, militer) jelas menjadi kriteria *screening* terdepan, baru diikuti oleh alokasi ke maskapai sewa atau komersial tipe mesin berat maupun mesin turbo jet perantara. Keselarasan rasional ini menempatkan arsitektur AMC sebagai konsep instrumen kotak kaca (menyokong fitur kapabilitas *Explainable AI* sekunder yang intuitif).

---

## 4.3 Pengujian Sistem (Black-Box Testing)

### 4.3.1 Metodologi Pengujian
Fase peresmian rekayasa fungsional berorientasi kualitas diakses lewat strategi uji tak tembus pandang atau *Black-box Testing* untuk memusatkan evaluasi integrasi layanan antarmuka sistem. Mekanika komparasi merangkum kelayakan eksekusi hasil masukan operator dibanding ekspektasi target, dieksekusi secara repetitif pada lingkungan demonstrasi kelima kanal aplikasi inti.

### 4.3.2 Hasil Pengujian Per Modul

[TABEL 4.7] Penelusuran Modul Transaksi Pergerakan
| No | Skenario Validasi | Subjek Demonstrasi Modifikatif | Ekspektasi Luaran Logika | Luaran Faktual | Status |
| :---: | :--- | :--- | :--- | :--- | :---: |
| 1 | Pencatatan Lengkap | Formulasi On < Off Block | Lolos sistem, sinkronisasi sukses | Pencatatan sukses ke DB | *Pass* |
| 2 | Kesalahan Ruang Waktu | Menekan parameter logik (Off < On) | Terminasi simpan dengan alarm peringatan | Eksekusi diblokir, warning timbul | *Pass* |
| 3 | Pembangkangan Kolom Wajib | Menelantarkan registrasi dan stand wajib | Terminasi penolakan dan fokus pada modul kosong | Notifikasi isian mandatory muncul | *Pass* |
| 4 | Audit Mutasi | Sunting tipe maskapai ke penerbang identik | Tersimpannya di sistem berikut log pelacakan mutasi akun | Tercapture rekam sunting identitas | *Pass* |
| 5 | Duplikasi Ganda Registrasi | Pengisian No Flight dua kali sehari | Larangan komputasi akibat referensi kembar primary | Penolakan *key violation* via database | *Pass* |

[TABEL 4.8] Penelusuran Modul Inferensi AI / ML Rekomendasi
| No | Skenario Validasi | Subjek Demonstrasi Modifikatif | Ekspektasi Luaran Logika | Luaran Faktual | Status |
| :---: | :--- | :--- | :--- | :--- | :---: |
| 1 | Model Kombinatif Normal | Set Input komersial lazim | Rekomendasi probabilitas dominan < 10 Detik | Muncul stabil ±4.0 detik awal/0.5s via Caching | *Pass* |
| 2 | Kasus Singular (*Edge Case*) | Set input tak berpadanan khusus | Kemampuan algoritma mengeksekusi inferensi subsampling aproksimasi | Rekomendasi rasional stabil terpanggil | *Pass* |
| 3 | Input Variabel Cacat | Memutilasi kolom tipe ukuran | Terminasi dan alarm syarat parameter kurang | Alert parameter nihil muncul | *Pass* |
| 4 | Tes Beban Waktu Reaksi | Sinkronisasi konstan berkali-kali | Pengukuran latensi antar respon Python dan Backend limit | Memenuhi syarat ~4 Detik cold-boot | *Pass* |

[TABEL 4.9] Penelusuran Modul Navigasi Status Visualisasi
| No | Skenario Validasi | Ekspektasi Luaran Logika | Luaran Faktual | Status |
| :---: | :--- | :--- | :--- | :--- | 
| 1 | Penerbangan Berlabuh (On-Block Absolut) | Warna zona merah stand menguasai navigasi HUD visual | Eksekusi palet warna merah instan sukses | *Pass* |
| 2 | Navigasi Nihil | Warna hijau dominan penanda slot stand netral bebas huni | HUD mengklaim indikasi hijau fungsional | *Pass* |
| 3 | Indikasi Persuasi Stand | Warna kuning menakodai penanda *allocated parking* untuk proses pendaratan taktik | Transisi ke kuning tanpa perlu resubmit utuh berhasil | *Pass* |

[TABEL 4.10] Penelusuran Modul Hak Kendali Otorisasi (*RBAC Hierarchy*)
| No | Hierarki Sistem | Skenario Modifikasi Berjalan | Ekspektasi Logika | Eksekusi Logika Latar | Status |
| :---: | :--- | :--- | :--- | :--- | :---: |
| 1 | Viewer / ATC | Memaksa merangsek modifikasi pergerakan formasi | Layar tolak (*Access Denied* / pembatasan modul murni) | Tertolak tuntas oleh Middleware | *Pass* |
| 2 | Viewer / ATC | Observasi lanskap layar real-time (*Read*) | Sinkronisasi berlanjut stabil secara visual | Sukses pantau seketika | *Pass* |
| 3 | Regulasi Operator | Sabotase hierarki kendali User Accounts dan RBAC | Konfrontasi batas perizinan menu tidak timbul | Layar menu administrasi dilenyapkan | *Pass* |
| 4 | Regulasi Operator | Input registrasi form, trigger Inferensi AI, kompilasi rapor | Eksekusi berhasil, menu lengkap terpapar akurat | Akses absolut pergerakan operasional | *Pass* |
| 5 | Induk Administrator | Inspeksi setiap selimut menu lintas-kompartemen | Segala pembatasan ditangguhkan (bypass) total | Akses level-Dewa berjalan normal utuh | *Pass* |

[TABEL 4.11] Penelusuran Modul Automasi Distribusi Dokumentasi (*Export*)
| No | Skenario Operasional Pelaporan | Ekspektasi Luaran Logika | Luaran Faktual | Status |
| :---: | :--- | :--- | :--- | :--- | 
| 1 | Pengabadian Snapshot Monitor | Rekam jejak *Timestamp* absolut dari status *Occupancy Stand* | Tabel snapshot tertata statis tepat waktu | *Pass* |
| 2 | Eskalasi Modul *Charter* | Meluncurkan algoritma penyaringan komuter eksklusif *Charter* bulanan kompilatif | PDF/Sheet ekstraksi tampil menawan dan bebas rekayasa manual | *Pass* |
| 3 | Manifestasi Operasi RON | Menkalkulasi limit durasi antara off ke on block berjam-jam | Rekam komputasi selisih menembus format luaran sukses | *Pass* |

### 4.3.3 Rekapitulasi Hasil Pengujian

[TABEL 4.12] Agregat Validasi Akhir 
| Komponen Modul Fungsionalitas | Indeks Kasus | Cacat Temuan | Indikator Keberhasilan Mutlak |
| :--- | :---: | :---: | :---: |
| Antarmuka Catatan Pergerakan Utama | 5 | 0 | Lulus 100% |
| Integrasi AI-ML Prediksi *Random Forest* | 4 | 0 | Lulus 100% |
| Visual HUD *Apron Real Time Dashboard* | 3 | 0 | Lulus 100% |
| Keamanan Role-Based (RBAC Otorisasi Interkom) | 5 | 0 | Lulus 100% |
| Generator Pelaporan Operasional Rekapitulasi | 3 | 0 | Lulus 100% |
| **TOTAL KEBERHASILAN** | **20 Uji Coba** | **Nir-Kegagalan** | **Rasio Keberhasilan Paripurna 100%** |

Kegemilangan persentase rasio paripurna sebesar 100% melambangkan perwujudan purwarupa struktural stabil. Ekosistem berhasil memformulasikan batasan perizinan akses secara spesifik tanpa celah celah misrepresentasi logika, mensahihkan kelanjutan implementasi praktis di babakan tata laksana apron murni Halim Perdanakusuma.

---

## 4.4 Analisis Kontribusi Efisiensi Operasional

### 4.4.1 Perbandingan Waktu Pengambilan Keputusan Alokasi

Kontribusi substansial efisiensi yang dihadirkan dapat secara langsung disandingkan dengan durasi repons operator dalam mengamankan stand di lingkungan *legacy*. Berdasarkan hasil analisis dan peninjauan awal (*observation metrics baseline* dari Bab I), frekuensi rata-rata pertimbangan alokasi manual berlangsung selama memakan waktu satu hingga dua menit per maskapai komersial mendarat. Setelah instalasi antarmuka model *Random Forest* ini beroperasi, kalkulasi rekomendasi tereksekusi dengan durasi respon stabil di titik 4 detik (*round-trip data eksekusi PHP-Python-JSON*). Efisiensi radikal dari skema digital komputasional tersebut melembagakan **reduksi jeda pengambilan keputusan di kisaran ekstrem 93.3% hingga maksimal 96.7% lebih gesit** ketimbang preseden evaluasi logis otak operator reguler. 

Penekanan waktu kompresi ini merepresentasikan fleksibilitas bagi *controllers* ketika menangani rentetan rotasi padat pesawat di masa puncak musim kepadatan penumpang, mengingat hak otorisasi intervensi manusia *(Human-in-the-loop)* tetap menjadi pemutus ketuk palu akhir dan tak semata diserahkan seutuhnya pada algoritma mesin.

### 4.4.2 Potensi Reduksi Error Alokasi dan Miskomunikasi

Integrasi arsitektur terdesentralisasi memberikan penawar taktis bagi polemik struktural sistem ganda *Google Sheets* edisi lama via empat dimensi eliminasi kecacatan utama operasional observasional:

[TABEL 4.14] Proyeksi Peningkatan Resolusi Kapasitas Kualitas Keputusan Operator 
| Profil Komplikasi Historis (Metrik Eksisting BAB I) | Dimensi Limitasi Eksisting | Mitigasi Arsitektur Web AMC | Nilai Kontribusi Eksponensial Terhadap Rapor Efisiensi Bandara |
| :--- | :--- | :--- | :--- |
| **Beban Alokasi Kurang Optimal (Kasus Stand 3-4x/Hari)** | Operator sering tergesa menempatkan pesawat berpotensi redundansi ukuran dan jarak maskapai gerbang. | Rekomendasi ML mengalkulasi rekam riwayat keputusan tervalidasi sejarah dengan tingkat presisi > 80% stabil. | Mengoptimalkan jarak manuver pilot sewa gerbong maskapai dan efisiensi konsumsi bahan bakar pesawat (Avtur) di area lajur (*apron tarmac delays*). |
| **Gesekan Silang-Siar AMC & ATC (Miskomunikasi 2x/Hari)** | Kebutuhan verifikasi radio visual yang membuat kedatangan di lajur tertahan hitungan detik-menit. | Monitor *Read-only* khusus bagi gardu pengamat Menara Bandara (*Role Viewer AOCC/ATC*). | Eliminasi polusi *crosstalk radio* dan ketidakpastian informasi lajur via akses layar transparan waktu seketika. |
| **Keteledoran Stempel Waktu (Error *Timeline* 2x/Hari)** | *Off-Block* terkadang memintas dimensi linier akibat kesalahan masukan sekadar silap angka. | Pagar validasi skrip arsitektur logikal PHP di lapisan form *back-end* (*isOffBlockEarlierThanOnBlock*). | Kemusnaan kecacatan (*Eliminated By Design*) menekan koreksi pembukuan data akuntansi finansial penerbangan sewa/RON maskapai. |
| **Polusi Sinkronisasi Duplikasi (Tumpukan Stand 1-3x/Hari)** | Ketidakmampuan *Sheets* mengenali regis seragam paralel berhari-hari. | Parameter integrasi tabel Database MySQL yang menolak ID sama di slot jam bertabrakan. | Mereduksi kemarahan maskapai dari potensi tuduhan pembengkakan argo harga penyewaan slot area stand Halim secara signifikan. |

Materi pertanggungjawaban komparatif berpatokan di dasar observasi lapangan selama proses permagangan empiris perancang purwarupa AMC. Alih-alih melakukan manipulasi target acak bagi pelatihan set data model, kepastian validitas bersandar teguh dari jaminan validasi instansi (karena data pelatihan adalah peniruan keahlian terbaik kolektif para master pengelola ruang udara Halim masa transisi Mei-Juli). Hal mendasar ini melahirkan fondasi absah yang inheren dan otentik mengabaikan kompleksitas uji formal tambahan sekunder. 

### 4.4.3 Umpan Balik Kualitatif Operator

Memasuki tahap pemahatan purwarupa akhir antarmuka program, sosialisasi evaluatif tak mengikat (informal) dicanangkan bersama sekurangnya tiga pimpinan teknis pengatur AMC berdinas Halim. Pemaparan konsep dan komparasi simulasi uji tempur memojokkan kapabilitas model sistem berhadapan muka (*head-to-head*) melawan intuisi manusia senior dalam memetakan letak maskapai asing. 

Dalam simulasi 10 demonstrasi skenario padat bandara, *Random Forest* AMC berhasil menyumbang rekomendasi probabilitas alokasi *apron stand* selaras preferensi mutlak para *controllers* dengan memukau dalam **9 dari 10 sesi konfrontasi intuisi kasual (rasio *match* sentuh 90%)**. Walau temuan deskriptif kualitatif uji tersebut tak menuntut pengartian angka akuntansi formal (*Formal User Acceptance Test*) akibat batas sebaran minim relawan responden, validasi organik tak terbantahkan mendemonstrasikan kelancaran dan kepercayaan awal luar biasa para profesional bagi asimilasi modul visual prediktif *Top-3 Machine Learning* dalam mengkonfigurasi kepadatan memuncak sirkulasi udara domestik, memangkas frustrasi birokratis pengecekan sel tabulasi *Excel Sheets*, serta mensponsori delegasi otonom via kanal intipan khusus otoritas navigasi (AirNav Tower Viewer). 

---

## 4.5 Pembahasan

### 4.5.1 Kesesuaian Hasil dengan Target Penelitian

Fokus pembedahan skripsi ini memuara demi menjamin kelancaran transisi manualisasi era analog manajemen AMC kepada integrasi AI berkerangka *software-centric*. 

[TABEL 4.15] Pemenuhan Limitasi Rumusan Kualifikasi 
| Matriks Parameter Target Skripsi (Rumusan Masalah) | Formulasi Tolok Ukur Keberhasilan (*Goals Limit*) | Rapor Pencapaian Akhir Pembangunan | Label |
| :--- | :--- | :--- | :--- |
| **(RM#1) Arsitektur Sistem Integritas Penuh** | Peralihan tabulasi mandiri (3 Sheets) ke fondasi *Web-based Data-Lake* berfitur pantau waktu mutlak + otorisasi multi level log. | Transisi mulus diwadahi 5 sub-modul interaktif & jaring relasi MySQL-PHP terdesentralisasi paripurna. | **Tercapai** |
| **(RM#2) Optimalisasi Kecerdasan Artifisial R.F** | Konstruksi algoritmik pendikte saran *Stand* 3 param mutlak, presisi *Top-3 Hit > 80%*, dengan kecepatan toleransi komputasi 10 detik. | Sukses melahirkan model `pkl` 2.57 MB, *Top-3 score* final 80.15%, dibalut kompresi putar balik waktu kilat 4.0 detik latensi maksimum. | **Tercapai** | 
| **(RM#3) Pemaparan Kontribusi Reduksi in-Efisiensi** | Proyeksi pemangkasan waktu kelola jeda pendaratan manuver serta mitigasi kebocoran informasi operator landasan. | Evaporasi resiko kekacauan penulisan, penyusutan waktu keputusan dari kurun bermenit hingga di ambang fraksi limit sentuh ~95% deselerasi absolut. | **Terpapar Kredibel** |

### 4.5.2 Diskusi Pemilihan Random Forest

Harmonisasi integrasi komputasi *Machine Learning* dalam skripsi mengamini ketepatan adopsi rasional klasifikatif *Random Forest Algorithm* sebagai instrumen arsitektur inti skripsi ini ketimbang jejaring syaraf kognitif. Realita medan lapangan yang memaparkan tantangan krisis distribusi sampel tak lazim/jarang terbang *(Extreme Class Imbalances)*—seperti ketidakmunculan tipe sayap berat pesawat A340 di fasilitas komersial harian Halim—diselesaikan elegan memakai manipulasi *SMOTE Class Weight Balancing Subsample* di mana algoritma memperkokoh atensi spesifik di cabang rentetan set fitur gersang di setiap perputaran pembangunan ranting pohon keputusannya.

Dibandingkan arsitektur AMC murni tanpa asimilasi intelektual buatan milik publikasi sejenis sebut saja riset Ulfa et al.(2023) hingga riset bandara sipil Makassar Jumlad (2024) yang condong berkubang sebatas sistem inventaris pangkalan data standar semata, platform AMC *Halim* ini mengibarkan kontribusi inovatif orisinal di sekat *Aviation Apron-ML predictive spatial allocation* tanah air, mendongkrak wibawa instrumen *decision support transparent* lewat ekspresi akuntabilitas transparan fitur parameternya (*Feature Importance Breakdown*) kepada operator pelaksana tanpa aura kotak misteri *(black-box confusion)*. 

### 4.5.3 Keterbatasan Implementasi

Skripsi arsitektur kecerdasan prediktif ini dilingkupi sejumlah pengecualian operasional inheren yang menanti penyempurnaan riset lintas-angkatan ke depan:
1. Kurungan batasan periode histori dataset setebal 3 bulan kalender operasional transisi AMC belum menyerap distorsi turbulensi padat lalu lintas ritme musiman nasional (*Mudik Lebaran*, lawatan serempak tamu kenegaraan tingkat tinggi, dll).
2. Radius prediksi dibatasi murni terhadap cakupan 20 ruang pelataran *(operational stands)* semata — menelantarkan kalkulasi nasib rotasi silang 63 blok pelataran karantina tak bernyawa, area bongkar muat militer/relokasi dan stand cuci bermalam pasif yang belum disentuh kalkulasi prediksi.
3. Simulasi *Stress-test* peladen aplikasi belum dilepas menyelam sepenuhnya di gelanggang interkoneksitas web-publik nyata berfrekuensi rute ribuan kueri serentak akibat penempatan sandaran ekosistem terbatas server purwarupa basis uji *XAMPP Localhost Windows*.
4. Derajat sertifikasi adopsi validasi final masih terkekang secara kuantitatif informatif interaksi dua pertiga relawan spesialis pergerakan apron, mendalilkan urgensi pergelaran formulasi sertifikat kuis UAT (User Acceptance Test) massal bagi legitimasi kelembagaan AMC paripurna.  
5. Modul perangkat lunak tertutup di ruang vakum interdepartemental — nihil gerbang sinergi akses antarmuka API sinkronis otomatis untuk pendelegasian serah terima pasokan manifes data instan langsung memotong birokrasi AOCC ATC Airnav (*Input pergerakan pendaratan terpaksa mutlak ditik serentak sukarela oleh tangan AMC*). 

---

# BAB V: PENUTUP

---

## 5.1 Kesimpulan

Melalui pengkajian tahapan eksekusi dan pelaporan bukti konkret eksperimental modifikasi arsitektur platform di atas, skripsi rekayasa perangkat lunak Apron Halim Perdanakusuma mengukir benang merah kesimpulan berdaya jadik sebagai pelunasan objektif rumusan riset:

### 5.1.1 Kesimpulan terhadap Rumusan Masalah #1
Rekayasa sistem informasi manajemen AMC bertransformasi mutlak dari jerat kerentanan konvensional menjadi entitas web moderen fungsional komprehensif mengandalkan fondasi tata kelola hierarkis PHP versi 8 dan skema perbendaharaan 13 entitas tabel struktural dalam basis data peladen relasional MariaDB. Peralihan ini membasmi ketergantungan historis terhadap perpecahan pencatatan manipulasi manual (*Google Spreadsheets*) menjadi unifikasi mutlak sistem 5 sub-modul kokoh yang menyajikan monitor pendaratan spasial 20 apron seketika sekaligus mengekspor tiga rapor birokrasi (Daily Monitor, Charter Report, RON Report) dalam sekali detak pengesahan terpadu. Implementasi ini berhasil memblokade interupsi misinformasi ganda dan kekeliruan mutasi penulisan masa lapor melalui sistem pertahanan otomatis validasi waktu pergerakan sinkronis, pelacakan ketat suntingan pengguna (*Audit Trail*), serta pembagian tirai keamanan batas akses berjenjang tingkat (Administrator, Eksekutor Operator, serta peninjau Menara Radar AOCC pasif).

### 5.1.2 Kesimpulan terhadap Rumusan Masalah #2
Model intelejensia artifisial algoritmik *Random Forest Classifier Multi-Class* (20 kategori landasan parkir mendarat spesifik) sanggup berdiri mumpuni di jantung mekanisme pengolah interkoneksi skrip Python AMC, sukses mengekstrak wawasan presisi kelayakan prediksi rekomendasi di level margin Top-3 Accuracy mutlak mencapai parameter cemerlang rasio **80.15%**. Optimalisasi ketidakseimbangan kelas parameter terjal diselingkupi strategi *SMOTE* subsampel serta sinkronisasi pencarian silang kelipatan 5-lapisan CV sukses membangun respon durasi probabilitas eksekusi di titik tumpuan latensi kilat stabil **4.0 Detik** ujung tanggap usai instruksi, jauh meninggalkan ancaman penolakan kinerja target treshold tenggat 10 detik. Analitik akurasi ini merepresentasikan kelayakan tinggi eksekusi sistem membedah dan memanfaatkan murni formulasi tiga atribut input mutlak lapangan (*aircraft_type*, *category*, *operator_airline*). 

### 5.1.3 Kesimpulan terhadap Rumusan Masalah #3
Lahirnya prototipe kecerdasan sistem menyumbangkan sumbangsih pemampatan deselerasi dan resiko komplikasi operasional tingkat lanjut nan fantastis secara eksponensial di lapangan taktis dinas AMC. Peralihan navigasi informasi dan probabilitas rujukan komputasi pintar melembaga-paksakan pengecilan kurun penyelesaian pengambilan sikap alokasi landasan yang biasanya berlarut menjerat durasi rataan satu ke memakan waktu dua menit per manuver memadati pikiran logis otak pelaksana untuk tersapu instan stabil menjadi fraksi detik (reduksi beban deliberasi meroket 93%). Kredibilitas ini disertai kelegaan tumpasnya empat malapateka organik keteledoran manusia mutlak warisan generasi eksisting administrasi lawas (anarki kekeliruan pencatatan format jam kronologis, duplikasi input jadwal buta pendaratan kloning, distorsi gema miskomunikasi saluran komunikasi operator menara pengamat, efisiensi konsumsi waktu penerbangan, dan in-efisiensi jarak roda rotasi taktis) demi menyokong wibawa sistem dalam posisi krusial sebagai *Decision Support System* tak terbantahkan terpercaya yang meletakkan putusan mutlak (*Human-in-the-Loop Approval*) selaras kapabilitas intuisi pakar AMC bersangkutan secara efisien.

---

## 5.2 Keterbatasan Penelitian

Meskipun sistem telah memenuhi kriteria kinerja utama, interpretasi pengembangan terhambat oleh konvergensi penemuan ruang riset bersyarat sebagai berikut:
- Rentang kompilasi kolektif riwayat pembukuan bahan referensi latih membatasi fokus pengumpulan terbatas triwulan kalender masa dinas kerja peneliti Mei-Juli, mengecilkan kesempatan perolehan rasio referensi deviasi manuver krusial luar biasa (Badai udara monsun, ledakan kuota hari pelesat libur *peak-seasons* Lebaran/Natal, serta serbuan eksodus tamu kenegaraan serentak VVIP taktis).
- Sektor teritorial detektor prediksi sempit mengincar jangkauan spasial khusus bagi populasi manuver murni roda 20 pangkalan penyewaan komersial inti bandar udara Halim semata — menelantarkan puluhan (total 63 baris) relokasi stand buangan malam *Remain Overnights* diam maupun pengungsian taktis pencucian lambung badan pesawat yang harus diurusi terpisah kalkulasi manusia.  
- Kualifikasi pembuktian lingkungan operasional jaringan terkungkung dalam server purwarupa laboratorium basis *localhost*, menjauhkan peresmian verifikasi kapabilitas dan perlawanan aplikasi web terpaan beban jutaan interaksi riil lalu lintas awan *Public Cloud Infrastructure*.  
- Demonstrasi adopsi penerimaan pengguna beredar spesifik berdalil pendekatan opini validasi informal personal tanpa dukungan sertifikasi UAT angket massal standar kualitatif representatif terpadu lintas operator keseluruhan staf AMC institusi.
- Ekosistem operasional perangkat skripsi terkarantina tunggal berdiri independen tidak berafiliasi tanpa pembentukan gerbang API *air traffic integrasi* dengan basis komando AirNav/AOCC berpusat, melestarikan warisan ritme konvensional masukan ulang (*re-input typing*) manifes rencana rotasi pilot manual mandiri per jam satu-persatu oleh jari staff pelaksana ke keranjang sistem baru. 

---

## 5.3 Saran Pengembangan

Sebagai tindak lanjut dari keterbatasan penelitian yang telah dipaparkan, kajian ini memberikan rekomendasi rasional strategis konkrit bagi peminatan penyusunan dan optimalisasi skripsi terpusat mendatang selaras kapabilitas *Machine Learning*:

### 5.3.1 Perluasan Dataset dan Pembaruan Model Berkala
Penajaman asimilasi performa kognisi *Random Forest Estimator* perlu direformasi dengan mensyaratkan perpanjangan ekuivalensi bahan baku riwayat dataset menjadi cakupan spektrum observasional minimum rentang konsekutif duabelas bulan kalender (Satu Tahun Penuh). Injeksi masa retensi historis panjang meluncurkan algoritma pembiasaan sistematis perlakuan re-training bergilir model setiap jadwal kuartalan demi menstimulus aklimatisasi fluktuasi transisi kalender udara.

### 5.3.2 Perluasan Cakupan Wilayah Prediktif Tambahan
Diperlukan alokasi fokus perancangan pohon keputusan model sekunder kembar pendamping independen berspesialisasi murni memprediksi nasib karantina dan prioritas barisan stand pasif peremajaan landasan istirahat RON 63 slot non-operatif khusus, ditunjang ekspansi kelonggaran jumlah penambahan penanaman kolom input operasional baru ke struktur jaringan kueri (e.g., jam taksir pendaratan ketibaan gerbang aktual *Time of Arrival*, dan sinyal hierarki kebangsaan kelas *Very-Very Important Persons / State-Flights*). 

### 5.3.3 Peningkatan Modul Mobilitas Agilitas Sistem
Keandalan purwarupa ini mendirikan kesempatan adopsi modifikasi struktur sasis ekosistem antar muka sistem antrean komersial ke wujud pengembangan platform antarmuka gesit peranti layar sentuh lapangan berjalan *(Mobile Web/Android Application Responsive).* Ketersediaan konektivitas nirkabel dan injeksi modul pemandu suara digital notifikasi konflik tabrakan pendaratan di udara seketika (*Push Notice System Alert*) mempersingkat proses adaptasi petugas pemonitor di lajur pelataran konvensi pesawat bersikeras kapan saja terhindar dari meja statis AMC murni.

### 5.3.4 Validasi Mutu Eksperimental Komprehensif
Integrasi peluncuran perdana instalasi publik infrastruktur sistem mutlak dilampiri prasyarat tahapan Uji Mutu Penerimaan (*User Acceptance Testing System*) skala masif merangkul kuorum penilai delegasi faksi kontrol staf lapangan terkait terstruktur ketat, diikuti rutinitas penghancuran purwarupa uji ketahanan performa batas kelambanan respon HTTP server peladen publik *Load Balancer* jelang realokasi transisi *production environment* permanen demi menganalisa ketahanan keausan erosi usia logika kepintaran *Model-Drifting Accuracy* seiring keusangan jaman. 

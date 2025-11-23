# Penjelasan Sistem Prediksi Parking Stand AMC
**Sistem Rekomendasi Berbasis Machine Learning untuk Alokasi Stand Pesawat**

---

## BAGIAN 1: CARA KERJA SISTEM PREDIKSI

### 📋 **GAMBARAN UMUM**

Sistem ini menggunakan **Artificial Intelligence (Machine Learning)** untuk merekomendasikan parking stand yang paling sesuai berdasarkan data historis pergerakan pesawat di bandara.

**Analogi Sederhana:**
> Bayangkan sistem ini seperti asisten berpengalaman yang telah mengamati ribuan pesawat mendarat selama bertahun-tahun. Ketika ada pesawat baru, sistem langsung tahu: *"Pesawat jenis ini biasanya parkir di sini berdasarkan pengalaman sebelumnya"*.

---

## 🔄 **FASE 1: PELATIHAN MODEL (Training Phase)**

### **Langkah 1: Pengumpulan Data Historis**

Sistem mengumpulkan data pergerakan pesawat masa lalu dari database:

```
Data yang Dikumpulkan:
├─ Registration (contoh: PK-ABC)
├─ Aircraft Type (contoh: B737-800)
├─ Operator/Airline (contoh: Garuda Indonesia)
├─ Category (Commercial/Cargo/Charter)
├─ Parking Stand yang Digunakan (contoh: B2)
└─ Waktu dan Tanggal Operasi
```

**Jumlah Data Training (Model v2.0):** 4,152 pergerakan pesawat

---

### **Langkah 2: Feature Engineering (Persiapan Data)**

Data mentah diubah menjadi "features" (fitur) yang bisa dipahami komputer:

#### **6 Features Utama:**

1. **`aircraft_type`** (Tipe Pesawat)
   - Contoh: B737-800, A320, ATR72
   - Kenapa penting? → Setiap tipe pesawat punya ukuran berbeda

2. **`aircraft_size`** (Ukuran Pesawat)
   - Kategori: Small, Medium, Large
   - Klasifikasi otomatis berdasarkan wingspan dan kapasitas
   - Contoh:
     - Small: Cessna, ATR42, Pilatus (wingspan < 30m)
     - Medium: B737, A320 (wingspan 30-50m)
     - Large: B777, A330 (wingspan > 50m)

3. **`operator_airline`** (Maskapai)
   - Contoh: Garuda Indonesia, Lion Air, Citilink
   - Kenapa penting? → Maskapai tertentu punya preferensi stand tertentu

4. **`airline_tier`** (Tier Maskapai)
   - Frequent: Maskapai yang sering beroperasi (>100x/bulan)
   - Regular: Operasi sedang (10-100x/bulan)
   - Occasional: Jarang (<10x/bulan)
   - Kenapa penting? → Maskapai frequent biasanya punya stand tetap

5. **`category`** (Kategori)
   - Commercial (Penumpang komersial)
   - Cargo (Kargo)
   - Charter (Charter/sewa)
   - Kenapa penting? → Kategori berbeda butuh fasilitas berbeda

6. **`stand_zone`** (Zona Stand)
   - Domestic Terminal (A-series stands)
   - International Terminal (B-series stands)
   - South Apron (SA-series stands)
   - North Apron (NSA-series stands)
   - Cargo/Remote (WR, RE, RW series)
   - Kenapa penting? → Terminal assignment berdasarkan kategori

---

### **Langkah 3: Training Model Machine Learning**

**Algoritma yang Digunakan: Random Forest Classifier**

#### **Apa itu Random Forest?**

Bayangkan memiliki **100 ahli** yang masing-masing punya pendapat tentang stand terbaik. Random Forest bekerja seperti voting:

```
┌─────────────────────────────────────────────────┐
│  Input: B737-800, Garuda Indonesia, Commercial │
└─────────────────┬───────────────────────────────┘
                  │
      ┌───────────┴───────────┐
      │   Random Forest       │
      │   (100 Decision Trees)│
      └───────────┬───────────┘
                  │
    ┌─────────────┼─────────────┐
    │             │             │
┌───▼───┐    ┌───▼───┐    ┌───▼───┐
│Tree 1 │    │Tree 2 │ ... │Tree 100│
│Vote:B2│    │Vote:B2│    │Vote:B1 │
└───┬───┘    └───┬───┘    └───┬───┘
    │             │             │
    └─────────────┼─────────────┘
                  │
         ┌────────▼─────────┐
         │  Final Voting:   │
         │  B2: 75 votes    │ ← Rekomendasi Terkuat
         │  B1: 20 votes    │
         │  B3: 5 votes     │
         └──────────────────┘
```

**Keunggulan Random Forest:**
- ✅ Akurasi tinggi (80.15% top-3 accuracy)
- ✅ Bisa menangani data kompleks
- ✅ Tidak mudah "overfitting" (terlalu menghapal)
- ✅ Memberikan probabilitas (tingkat keyakinan)

---

### **Langkah 4: Evaluasi Model**

Setelah training, model diuji dengan data yang belum pernah dilihat:

```
Metrics Model v2.0:
├─ Top-3 Accuracy: 80.15%
│  (8 dari 10 prediksi, stand yang benar ada di top 3)
│
├─ Training Samples: 4,152 pergerakan
├─ Training Date: 30 Oktober 2025
├─ Target Accuracy: ≥70% (EXCEEDED ✓)
└─ Algorithm: Random Forest (100 trees)
```

**Peningkatan dari Model Lama:**
- Model v1.0 (Decision Tree): 61.57% accuracy
- Model v2.0 (Random Forest): **80.15% accuracy**
- **Improvement: +18.58%** 🎯

---

## 🎯 **FASE 2: IMPLEMENTASI (Prediction Phase)**

### **ALUR LENGKAP: Dari Input hingga Simpan Movement**

```
┌──────────────────────────────────────────────────────────────┐
│ USER INPUT (Apron Page)                                      │
│ ┌──────────────────────────────────────────────────────────┐ │
│ │ 1. Isi form:                                             │ │
│ │    - Registration: PK-ABC                                │ │
│ │    - Aircraft Type: B737-800                             │ │
│ │    - Operator: Garuda Indonesia                          │ │
│ │    - Category: Commercial                                │ │
│ │                                                          │ │
│ │ 2. Klik tombol: "Get AI Recommendations"                │ │
│ └──────────────────────────────────────────────────────────┘ │
└────────────────────────────┬─────────────────────────────────┘
                             │
                             ▼
┌──────────────────────────────────────────────────────────────┐
│ STEP 1: VALIDASI INPUT                                       │
│ ┌──────────────────────────────────────────────────────────┐ │
│ │ Sistem mengecek:                                         │ │
│ │ ✓ Aircraft type tidak kosong                             │ │
│ │ ✓ Operator tidak kosong                                  │ │
│ │ ✓ Category valid (Commercial/Cargo/Charter)              │ │
│ │                                                          │ │
│ │ Normalisasi data:                                        │ │
│ │ - Uppercase semua input                                  │ │
│ │ - Map "KOMERSIAL" → "COMMERCIAL"                         │ │
│ └──────────────────────────────────────────────────────────┘ │
└────────────────────────────┬─────────────────────────────────┘
                             │
                             ▼
┌──────────────────────────────────────────────────────────────┐
│ STEP 2: CEK KETERSEDIAAN STAND (Availability Check)          │
│ ┌──────────────────────────────────────────────────────────┐ │
│ │ Query database untuk stand yang:                         │ │
│ │ 1. Tidak ada pesawat aktif (on-block tapi belum off)    │ │
│ │ 2. Tidak ada RON aktif (pesawat menginap)               │ │
│ │                                                          │ │
│ │ Hasil:                                                   │ │
│ │ - Total stands: 92                                       │ │
│ │ - Available: 65 stands                                   │ │
│ │ - Occupied: 27 stands                                    │ │
│ └──────────────────────────────────────────────────────────┘ │
└────────────────────────────┬─────────────────────────────────┘
                             │
                             ▼
┌──────────────────────────────────────────────────────────────┐
│ STEP 3: AMBIL AIRLINE PREFERENCES                            │
│ ┌──────────────────────────────────────────────────────────┐ │
│ │ 3 Tingkat Preferensi (Fallback System):                  │ │
│ │                                                          │ │
│ │ Tier 1: Database Preferences (Manual)                    │ │
│ │   → Cek tabel `airline_preferences`                      │ │
│ │   → Jika Garuda punya preferensi khusus di B2            │ │
│ │                                                          │ │
│ │ Tier 2: Historical Preferences (Auto dari cache)         │ │
│ │   → Dari file: storage/cache/historical_preferences.json │ │
│ │   → Analisa: "Commercial biasanya parkir di mana?"       │ │
│ │   → Contoh hasil:                                        │ │
│ │     B2: 100 (paling sering)                              │ │
│ │     B1: 85                                               │ │
│ │     B3: 70                                               │ │
│ │                                                          │ │
│ │ Tier 3: Availability Fallback                            │ │
│ │   → Jika tidak ada preferensi, bagi rata semua available │ │
│ └──────────────────────────────────────────────────────────┘ │
└────────────────────────────┬─────────────────────────────────┘
                             │
                             ▼
┌──────────────────────────────────────────────────────────────┐
│ STEP 4: JALANKAN MODEL AI (Python Subprocess)                │
│ ┌──────────────────────────────────────────────────────────┐ │
│ │ Proses:                                                  │ │
│ │                                                          │ │
│ │ 1. PHP memanggil Python script: ml/predict.py           │ │
│ │                                                          │ │
│ │ 2. Python memuat model dari file:                        │ │
│ │    - ml/parking_stand_model_rf_redo.pkl                  │ │
│ │    - ml/encoders_redo.pkl (7 encoder files)              │ │
│ │                                                          │ │
│ │ 3. Input diproses menjadi features:                      │ │
│ │    ┌──────────────────────────────────────────────────┐ │ │
│ │    │ Input:         Processed:                        │ │ │
│ │    │ ─────────────  ─────────────────────────────     │ │ │
│ │    │ B737-800    → aircraft_type: "B737-800"          │ │ │
│ │    │             → aircraft_size: "Medium"            │ │ │
│ │    │ Garuda      → operator_airline: "GARUDA"         │ │ │
│ │    │             → airline_tier: "Frequent"           │ │ │
│ │    │ Commercial  → category: "COMMERCIAL"             │ │ │
│ │    │             → stand_zone: "DOMESTIC_TERMINAL"    │ │ │
│ │    └──────────────────────────────────────────────────┘ │ │
│ │                                                          │ │
│ │ 4. Random Forest (100 trees) melakukan voting            │ │
│ │                                                          │ │
│ │ 5. Output: Top-3 predictions dengan probabilitas        │ │
│ │    ┌──────────────────────────────────────────────────┐ │ │
│ │    │ Rank 1: B2  → Probability: 86.4%                 │ │ │
│ │    │ Rank 2: B1  → Probability: 78.2%                 │ │ │
│ │    │ Rank 3: B3  → Probability: 65.8%                 │ │ │
│ │    └──────────────────────────────────────────────────┘ │ │
│ │                                                          │ │
│ │ Waktu proses: ~400-500ms                                 │ │
│ └──────────────────────────────────────────────────────────┘ │
└────────────────────────────┬─────────────────────────────────┘
                             │
                             ▼
┌──────────────────────────────────────────────────────────────┐
│ STEP 5: BUSINESS RULES & FILTERING                           │
│ ┌──────────────────────────────────────────────────────────┐ │
│ │ Filter berdasarkan aturan operasional:                   │ │
│ │                                                          │ │
│ │ Rule 1: Filter Stand yang Occupied                       │ │
│ │   - Jika B2 sedang terisi → SKIP                         │ │
│ │   - Hanya ambil yang available                           │ │
│ │                                                          │ │
│ │ Rule 2: A0 Stand Restriction (PENTING!)                  │ │
│ │   - Stand A0 hanya untuk pesawat kecil:                  │ │
│ │     • Cessna (C152-C208)                                 │ │
│ │     • Pilatus                                            │ │
│ │     • Diamond                                            │ │
│ │     • Piper                                              │ │
│ │   - Jika B737 diprediksi ke A0 → REJECT                  │ │
│ │                                                          │ │
│ │ Contoh Filtering:                                        │ │
│ │   Prediksi Awal:  B2 (occupied), B1 (available), B3 (ok) │ │
│ │   Setelah Filter: B1, B3 ← B2 dihapus                    │ │
│ └──────────────────────────────────────────────────────────┘ │
└────────────────────────────┬─────────────────────────────────┘
                             │
                             ▼
┌──────────────────────────────────────────────────────────────┐
│ STEP 6: COMPOSITE SCORING (Gabungan AI + Preferensi)         │
│ ┌──────────────────────────────────────────────────────────┐ │
│ │ Formula Scoring:                                         │ │
│ │                                                          │ │
│ │ Composite Score = (60% × AI Probability) +               │ │
│ │                   (40% × Preference Score)               │ │
│ │                                                          │ │
│ │ Contoh Perhitungan untuk Stand B1:                       │ │
│ │                                                          │ │
│ │ AI Probability       = 78.2% = 0.782                     │ │
│ │ Preference Score     = 85/100 = 0.85                     │ │
│ │                                                          │ │
│ │ Composite Score = (0.6 × 0.782) + (0.4 × 0.85)           │ │
│ │                 = 0.4692 + 0.34                          │ │
│ │                 = 0.8092                                 │ │
│ │                 = 80.92%                                 │ │
│ │                                                          │ │
│ │ Kenapa Gabungan?                                         │ │
│ │ • 60% AI    → Belajar dari pola data historis           │ │
│ │ • 40% Pref  → Pertimbangkan preferensi operasional       │ │
│ │                                                          │ │
│ │ Hasil Ranking Akhir (setelah composite):                 │ │
│ │   1. B1: 80.92%  ← TERBAIK                               │ │
│ │   2. B3: 75.34%                                          │ │
│ │   3. B4: 68.12%                                          │ │
│ └──────────────────────────────────────────────────────────┘ │
└────────────────────────────┬─────────────────────────────────┘
                             │
                             ▼
┌──────────────────────────────────────────────────────────────┐
│ STEP 7: GUARANTEE 3 RECOMMENDATIONS                          │
│ ┌──────────────────────────────────────────────────────────┐ │
│ │ Jika setelah filtering kandidat < 3:                     │ │
│ │                                                          │ │
│ │ Fallback Filling:                                        │ │
│ │ 1. Ambil available stands yang belum masuk list          │ │
│ │ 2. Beri score berdasarkan preference saja                │ │
│ │ 3. Tetap respect A0 restriction                          │ │
│ │ 4. Urutkan berdasarkan preference score                  │ │
│ │                                                          │ │
│ │ Contoh:                                                  │ │
│ │   Setelah filter: hanya B1, B3 (2 kandidat)              │ │
│ │   Tambah fallback: B5 (dari available list)              │ │
│ │   Final: B1, B3, B5 (3 rekomendasi DIJAMIN)              │ │
│ │                                                          │ │
│ │ → User SELALU dapat 3 rekomendasi                        │ │
│ └──────────────────────────────────────────────────────────┘ │
└────────────────────────────┬─────────────────────────────────┘
                             │
                             ▼
┌──────────────────────────────────────────────────────────────┐
│ STEP 8: LOGGING PREDIKSI                                     │
│ ┌──────────────────────────────────────────────────────────┐ │
│ │ Semua prediksi dicatat ke database untuk:                │ │
│ │                                                          │ │
│ │ Tabel: ml_prediction_log                                 │ │
│ │ ┌────────────────────────────────────────────────────┐   │ │
│ │ │ prediction_token:  "abc123..." (unique ID)         │   │ │
│ │ │ input:             {aircraft, operator, category}  │   │ │
│ │ │ raw_predictions:   [B1: 80%, B3: 75%, B4: 68%]     │   │ │
│ │ │ model_version:     "v2.0"                          │   │ │
│ │ │ requested_by_user: 5 (User ID)                     │   │ │
│ │ │ prediction_date:   2025-11-23 08:00:00             │   │ │
│ │ │                                                    │   │ │
│ │ │ # Kolom untuk tracking outcome (diisi nanti):     │   │ │
│ │ │ actual_stand_assigned:  NULL (belum pilih)        │   │ │
│ │ │ was_prediction_correct: NULL (belum tahu)         │   │ │
│ │ └────────────────────────────────────────────────────┘   │ │
│ │                                                          │ │
│ │ Tujuan Logging:                                          │ │
│ │ • Audit trail                                            │ │
│ │ • Model performance tracking                             │ │
│ │ • Continuous improvement                                 │ │
│ └──────────────────────────────────────────────────────────┘ │
└────────────────────────────┬─────────────────────────────────┘
                             │
                             ▼
┌──────────────────────────────────────────────────────────────┐
│ STEP 9: TAMPILKAN REKOMENDASI KE USER                        │
│ ┌──────────────────────────────────────────────────────────┐ │
│ │ UI menampilkan 3 kartu rekomendasi:                      │ │
│ │                                                          │ │
│ │ ┌──────────────┐ ┌──────────────┐ ┌──────────────┐      │ │
│ │ │   STAND B1   │ │   STAND B3   │ │   STAND B4   │      │ │
│ │ │   Rank #1    │ │   Rank #2    │ │   Rank #3    │      │ │
│ │ │              │ │              │ │              │      │ │
│ │ │ Confidence:  │ │ Confidence:  │ │ Confidence:  │      │ │
│ │ │   80.92%     │ │   75.34%     │ │   68.12%     │      │ │
│ │ └──────────────┘ └──────────────┘ └──────────────┘      │ │
│ │                                                          │ │
│ │ User bisa:                                               │ │
│ │ • Pilih salah satu rekomendasi                           │ │
│ │ • ATAU pilih stand lain manual                           │ │
│ └──────────────────────────────────────────────────────────┘ │
└────────────────────────────┬─────────────────────────────────┘
                             │
                             ▼
┌──────────────────────────────────────────────────────────────┐
│ STEP 10: USER PILIH STAND & ISI DATA LENGKAP                 │
│ ┌──────────────────────────────────────────────────────────┐ │
│ │ User melengkapi form movement:                           │ │
│ │ • Parking Stand: B1 (dari rekomendasi)                   │ │
│ │ • On-block Time: 14:30                                   │ │
│ │ • Off-block Time: 18:45                                  │ │
│ │ • Flight Number Arrival: GA123                           │ │
│ │ • Flight Number Departure: GA124                         │ │
│ │ • From: CGK                                              │ │
│ │ • To: DPS                                                │ │
│ │ • Remarks: (optional)                                    │ │
│ └──────────────────────────────────────────────────────────┘ │
└────────────────────────────┬─────────────────────────────────┘
                             │
                             ▼
┌──────────────────────────────────────────────────────────────┐
│ STEP 11: SIMPAN MOVEMENT KE DATABASE                         │
│ ┌──────────────────────────────────────────────────────────┐ │
│ │ INSERT INTO aircraft_movements:                          │ │
│ │ ┌────────────────────────────────────────────────────┐   │ │
│ │ │ registration:      PK-ABC                          │   │ │
│ │ │ aircraft_type:     B737-800                        │   │ │
│ │ │ parking_stand:     B1                              │   │ │
│ │ │ on_block_time:     14:30                           │   │ │
│ │ │ off_block_time:    18:45                           │   │ │
│ │ │ movement_date:     2025-11-23                      │   │ │
│ │ │ category:          Commercial                      │   │ │
│ │ │ user_id_created:   5                               │   │ │
│ │ └────────────────────────────────────────────────────┘   │ │
│ │                                                          │ │
│ │ UPDATE aircraft_details (if exists):                     │ │
│ │ ┌────────────────────────────────────────────────────┐   │ │
│ │ │ registration:      PK-ABC                          │   │ │
│ │ │ aircraft_type:     B737-800                        │   │ │
│ │ │ operator_airline:  GARUDA INDONESIA                │   │ │
│ │ │ category:          Commercial                      │   │ │
│ │ └────────────────────────────────────────────────────┘   │ │
│ └──────────────────────────────────────────────────────────┘ │
└────────────────────────────┬─────────────────────────────────┘
                             │
                             ▼
┌──────────────────────────────────────────────────────────────┐
│ STEP 12: UPDATE PREDICTION LOG (Outcome Tracking)            │
│ ┌──────────────────────────────────────────────────────────┐ │
│ │ UPDATE ml_prediction_log:                                │ │
│ │                                                          │ │
│ │ WHERE prediction_token = "abc123..."                     │ │
│ │                                                          │ │
│ │ SET:                                                     │ │
│ │   actual_stand_assigned = "B1"                           │ │
│ │                                                          │ │
│ │   was_prediction_correct =                               │ │
│ │     IF(B1 IN [B1,B3,B4], 1, 0)  ← Cek top-3 hit          │ │
│ │     = 1  (CORRECT! ✓)                                    │ │
│ │                                                          │ │
│ │   actual_recorded_at = NOW()                             │ │
│ │   assigned_by_user = 5                                   │ │
│ │                                                          │ │
│ │ Fungsi:                                                  │ │
│ │ • Track apakah prediksi AI akurat                        │ │
│ │ • Hitung accuracy rate untuk model evaluation            │ │
│ │ • Identify pola dimana model sering salah                │ │
│ └──────────────────────────────────────────────────────────┘ │
└────────────────────────────┬─────────────────────────────────┘
                             │
                             ▼
┌──────────────────────────────────────────────────────────────┐
│ ✅ SELESAI - Movement Tersimpan & Dashboard Auto-Update      │
│                                                              │
│ • Apron map langsung menampilkan pesawat di stand B1         │
│ • Dashboard counter update dalam 30 detik                    │
│ • Prediction log tersimpan untuk analisa                     │
└──────────────────────────────────────────────────────────────┘
```

---

## 📊 **PARAMETER PREDIKSI YANG DIGUNAKAN**

### **INPUT Parameters (Yang User Isi):**
1. **Registration** → Untuk cek history pesawat ini
2. **Aircraft Type** → Untuk klasifikasi ukuran & karakteristik
3. **Operator/Airline** → Untuk preferensi maskapai
4. **Category** → Untuk penentuan zona terminal

### **PROCESSED Parameters (Dihitung Sistem):**
5. **Aircraft Size** → Diklasifikasi otomatis dari type
6. **Airline Tier** → Dihitung dari frekuensi operasi
7. **Stand Zone** → Ditentukan dari category

### **CONTEXTUAL Parameters (Real-time):**
8. **Stand Availability** → Cek mana yang available sekarang
9. **Airline Preferences** → Dari database atau historical
10. **Business Rules** → A0 restriction, capacity limits

---

## 🎯 **BAGIAN 2: PENANGANAN STAND YANG OCCUPIED**

### **SKENARIO: Top Rekomendasi Sedang Terisi**

Mari kita lihat dengan contoh konkret:

#### **Situasi:**
```
Prediksi AI Original (tanpa filter):
┌─────────────────────────────────────────┐
│ Rank 1: B2 → Probability: 86.4% ⭐      │
│ Rank 2: B1 → Probability: 78.2%        │
│ Rank 3: B3 → Probability: 65.8%        │
└─────────────────────────────────────────┘

Tapi saat dicek real-time:
Stand B2: OCCUPIED ❌ (ada PK-XYZ sedang parkir)
Stand B1: AVAILABLE ✓
Stand B3: AVAILABLE ✓
```

---

### **PROSES FILTERING & FALLBACK:**

#### **STEP 1: Filter Occupied Stands**

```php
// Pseudo-code
foreach ($ai_predictions as $prediction) {
    $stand = $prediction['stand'];

    if (in_array($stand, $occupied_stands)) {
        // SKIP stand yang occupied
        continue;
    }

    if ($stand === 'A0' && $aircraft_size !== 'Small') {
        // SKIP A0 untuk pesawat besar
        continue;
    }

    // Tambahkan ke kandidat final
    $valid_candidates[] = $prediction;
}
```

**Hasil Setelah Filter:**
```
Valid Candidates:
├─ B1: 78.2% probability
└─ B3: 65.8% probability
Total: 2 kandidat (KURANG dari 3!)
```

---

#### **STEP 2: Fallback Filling (Jika < 3 Kandidat)**

Sistem HARUS memberikan **TEPAT 3 rekomendasi**, jadi:

```php
// Jika kandidat < 3, isi dengan available stands lainnya
if (count($valid_candidates) < 3) {
    // Ambil available stands yang belum masuk list
    $remaining_stands = array_diff($available_stands, $used_stands);

    // Urutkan berdasarkan preference score
    usort($remaining_stands, function($a, $b) use ($preferences) {
        return $preferences[$b] <=> $preferences[$a];
    });

    // Tambahkan sampai total = 3
    while (count($valid_candidates) < 3 && !empty($remaining_stands)) {
        $fallback_stand = array_shift($remaining_stands);

        // Tetap cek A0 restriction
        if ($fallback_stand === 'A0' && $aircraft_size !== 'Small') {
            continue;
        }

        // Hitung composite score dari preference saja
        $score = calculate_fallback_score($fallback_stand, $preferences);

        $valid_candidates[] = [
            'stand' => $fallback_stand,
            'probability' => 0, // Tidak dari AI
            'preference_score' => $preferences[$fallback_stand],
            'composite_score' => $score,
            'source' => 'fallback' // Tandai sebagai fallback
        ];
    }
}
```

**Hasil Setelah Fallback:**
```
Final Recommendations (3 stand DIJAMIN):
┌──────────────────────────────────────────────────┐
│ Rank 1: B1 → 80.92% (AI + Preference) ⭐         │
│         Source: Model                            │
│                                                  │
│ Rank 2: B3 → 75.34% (AI + Preference)           │
│         Source: Model                            │
│                                                  │
│ Rank 3: B5 → 68.00% (Preference only)           │
│         Source: Fallback ⚠️                      │
│         Note: Filled from available stands       │
└──────────────────────────────────────────────────┘
```

---

#### **STEP 3: Composite Scoring dengan Preference**

Untuk semua kandidat (termasuk fallback), hitung composite score:

```
Formula:
─────────
Composite Score = (0.6 × AI_Probability) + (0.4 × Normalized_Preference)

Normalized_Preference = min(1.0, max(0.0, preference_score / 100))

Contoh untuk Stand B5 (Fallback):
────────────────────────────────────
AI_Probability         = 0.0   (tidak dari AI)
Preference_Score       = 70/100 = 0.7
Normalized_Preference  = 0.7

Composite = (0.6 × 0) + (0.4 × 0.7)
          = 0 + 0.28
          = 0.28
          = 28%

Tapi karena B5 adalah kandidat terbaik yang available,
sistem tetap merekomendasikannya di rank #3.
```

---

### **RANKING LOGIC (Urutan Prioritas):**

```
Prioritas Pengurutan:
1. Composite Score (tinggi ke rendah)
2. AI Probability (jika composite sama)
3. Preference Score (jika probability sama)

Contoh Sorting:
───────────────
Stand  │ AI Prob │ Pref  │ Composite │ Final Rank
───────┼─────────┼───────┼───────────┼───────────
B1     │ 78.2%   │ 85    │ 80.92%    │ #1 ⭐
B3     │ 65.8%   │ 90    │ 75.34%    │ #2
B5     │ 0%      │ 70    │ 28.00%    │ #3
```

---

### **EDGE CASES (Kasus Ekstrem):**

#### **Case 1: SEMUA Stand Occupied Kecuali Satu**

```
Situasi:
- Available: hanya A1 (1 stand)
- Occupied: semua lainnya

Proses:
1. Filter AI predictions → KOSONG (semua occupied)
2. Fallback filling → ambil A1, A1, A1? ❌ TIDAK!
3. Sistem mengambil available stands unique
4. Jika < 3 available, fallback ambil yang paling dekat release

Output:
Rank 1: A1 (available now)
Rank 2: B2 (will be free in 15 min - nearest release)
Rank 3: B3 (will be free in 30 min)

Note: "Available" di UI ditampilkan dengan status
```

#### **Case 2: A0 Stand Muncul di Prediksi untuk Pesawat Besar**

```
Situasi:
- Aircraft: B737-800 (Medium size)
- AI Prediction: [A0, B1, B2]

Proses:
1. Check A0 restriction rule
2. aircraft_size = "Medium" ≠ "Small"
3. REJECT A0 dari kandidat
4. Lanjut ke B1, B2
5. Jika perlu fallback, SKIP A0 otomatis

Output:
Rank 1: B1 ✓
Rank 2: B2 ✓
Rank 3: B3 ✓ (dari fallback, A0 di-skip)
```

#### **Case 3: TIDAK ADA Stand Available (Apron Penuh)**

```
Situasi:
- Available: 0 stands
- System behavior:

Response JSON:
{
  "success": false,
  "source": "error",
  "message": "No parking stands available at this time",
  "recommendations": [],
  "availability": {
    "available": [],
    "occupied": [...92 stands...],
    "timestamp": "..."
  }
}

UI Display:
⚠️ "Tidak ada stand yang tersedia saat ini.
   Silakan pilih stand secara manual atau tunggu hingga ada yang kosong."
```

---

### **VISUAL FLOW: Occupied Stand Handling**

```
┌─────────────────────────────────────┐
│ AI Prediksi Top 3:                  │
│ B2 (86%), B1 (78%), B3 (66%)        │
└──────────────┬──────────────────────┘
               │
               ▼
        ┌──────────────┐
        │ Cek B2       │ → Occupied? ─── YES → SKIP B2
        └──────┬───────┘                        │
               NO                               │
               │                                │
               ▼                                │
        ┌──────────────┐                        │
        │ Cek A0 Rule  │ → B2≠A0 ──── OK       │
        └──────┬───────┘                        │
               │                                │
               ▼                                │
        ┌──────────────┐                        │
        │ Add to List  │                        │
        │ Candidates[] │                        │
        └──────┬───────┘                        │
               │                                │
        [Repeat for B1, B3] ◄──────────────────┘
               │
               ▼
        ┌──────────────────────┐
        │ Count Candidates     │
        └──────┬───────────────┘
               │
          ┌────┴────┐
          │         │
       < 3?        = 3
          │         │
          YES       NO
          │         │
          ▼         ▼
    ┌──────────┐  ┌────────────┐
    │ FALLBACK │  │ Return Top3│
    │ FILLING  │  │ Rankings   │
    └──────────┘  └────────────┘
          │              │
          └──────┬───────┘
                 │
                 ▼
          ┌─────────────┐
          │ Final Top 3 │
          │ with Scores │
          └─────────────┘
```

---

## 📈 **CONTINUOUS IMPROVEMENT (Model Learning)**

Sistem terus belajar dari keputusan user:

```
Data Collection Loop:
─────────────────────

Prediction → User Choice → Log Outcome → Analyze → Retrain Model
    ↑                                                      │
    └──────────────────────────────────────────────────────┘
```

**Metrics yang Di-track:**

1. **Top-3 Accuracy Rate**
   - Berapa % prediksi AI yang benar (user pilih salah satu dari top-3)

2. **Rank-1 Hit Rate**
   - Berapa % user langsung pilih rekomendasi #1

3. **Stand Usage Patterns**
   - Stand mana yang paling sering dipilih vs diprediksi

4. **Rejection Patterns**
   - Kapan user menolak semua rekomendasi dan pilih manual

**Model Re-training Schedule:**
- Monthly: Review accuracy metrics
- Quarterly: Retrain model dengan data baru
- Yearly: Major model upgrade (algorithm changes)

---

## 🎯 **KESIMPULAN**

### **Keunggulan Sistem:**

✅ **Akurasi Tinggi:** 80.15% top-3 accuracy
✅ **Cepat:** 400-1200ms per prediksi
✅ **Adaptif:** Belajar dari data operasional real
✅ **Robust:** Selalu kasih 3 rekomendasi (fallback system)
✅ **Compliant:** Respect business rules (A0 restriction, etc.)
✅ **Transparent:** Semua prediksi di-log untuk audit

### **Flow Singkat:**
```
Input → Validate → AI Predict → Filter Occupied →
Apply Rules → Composite Scoring → Fallback (jika perlu) →
Guarantee 3 Results → User Choose → Log Outcome → Learn
```

---

**Sistem ini mengkombinasikan kecerdasan buatan (AI) dengan aturan bisnis (business rules) untuk memberikan rekomendasi yang akurat sekaligus praktis untuk operasional bandara.**

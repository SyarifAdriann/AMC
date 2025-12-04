# Penjelasan Sistem Prediksi Parking Stand - VERSI RINGKAS
**Sistem Rekomendasi AI untuk Alokasi Stand Pesawat**

---

## 📋 GAMBARAN UMUM

Sistem ini menggunakan **Machine Learning (AI)** untuk merekomendasikan parking stand yang paling sesuai berdasarkan data historis pergerakan pesawat di bandara.

**Analogi Sederhana:**
> Seperti asisten berpengalaman yang telah mengamati ribuan pesawat mendarat. Ketika ada pesawat baru, sistem langsung tahu: *"Pesawat jenis ini biasanya parkir di sini"*.

---

## 🔄 FASE 1: PELATIHAN MODEL (Training Phase)

### Langkah-langkah:

**1. Pengumpulan Data Historis**
```
Data yang Dikumpulkan (4,152 pergerakan):
├─ Registration (PK-ABC)
├─ Aircraft Type (B737-800)
├─ Operator/Airline (Garuda Indonesia)
├─ Category (Commercial/Cargo/Charter)
└─ Parking Stand yang Digunakan (B2)
```

**2. Feature Engineering - 6 Parameter Utama:**

| Parameter | Contoh | Kenapa Penting? |
|-----------|--------|-----------------|
| `aircraft_type` | B737-800, A320 | Setiap tipe punya ukuran berbeda |
| `aircraft_size` | Small/Medium/Large | Menentukan kapasitas stand |
| `operator_airline` | Garuda, Lion Air | Maskapai punya preferensi tertentu |
| `airline_tier` | Frequent/Regular | Maskapai frequent punya stand tetap |
| `category` | Commercial/Cargo | Kategori butuh fasilitas berbeda |
| `stand_zone` | Domestic/International | Terminal assignment |

**3. Training dengan Random Forest**
- **Algoritma:** Random Forest (100 decision trees)
- **Prinsip:** 100 "ahli" voting untuk stand terbaik
- **Hasil:** Model dengan akurasi 80.15% (top-3 predictions)

**4. Evaluasi Model**
```
✅ Top-3 Accuracy: 80.15%
   (8 dari 10 prediksi, stand yang benar ada di top-3)

✅ Training Samples: 4,152 pergerakan
```

---

## 🎯 FASE 2: IMPLEMENTASI (Prediction Phase)

### Alur Singkat (6 Langkah Utama):

```
┌─────────────────────────────────────────────────────────┐
│ 1. USER INPUT                                           │
│    - Registration: PK-ABC                               │
│    - Aircraft Type: B737-800                            │
│    - Operator: Garuda Indonesia                         │
│    - Category: Commercial                               │
│    → Klik "Get AI Recommendations"                      │
└────────────────────┬────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────┐
│ 2. CEK KETERSEDIAAN STAND                               │
│    - Query database: stand mana yang available?         │
│    - Hasil: 65 dari 92 stands tersedia                  │
└────────────────────┬────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────┐
│ 3. JALANKAN MODEL AI                                    │
│    - Python script: ml/predict.py                       │
│    - Random Forest memproses 6 features                 │
│    - Output: Top-3 predictions + probabilitas           │
│      • B2: 86.4%                                        │
│      • B1: 78.2%                                        │
│      • B3: 65.8%                                        │
│    - Waktu proses:                                      │
└────────────────────┬────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────┐
│ 4. FILTER & BUSINESS RULES                              │
│    ✓ Hapus stand yang occupied                          │
│    ✓ A0 restriction: hanya untuk pesawat kecil          │
│    ✓ Respect airline preferences                        │
└────────────────────┬────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────┐
│ 5. COMPOSITE SCORING                                    │
│    Formula:                                             │
│    Composite = (60% × AI) + (40% × Preference)          │
│                                                         │
│    Contoh Stand B1:                                     │
│    = (0.6 × 78.2%) + (0.4 × 85%)                        │
│    = 46.92% + 34%                                       │
│    = 80.92% ⭐                                           │
│                                                         │
│    Final Ranking (setelah composite):                   │
│    1. B1: 80.92%                                        │
│    2. B3: 75.34%                                        │
│    3. B4: 68.12%                                        │
└────────────────────┬────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────┐
│ 6. TAMPILKAN & SIMPAN                                   │
│    - User lihat 3 rekomendasi                           │
│    - User pilih stand (atau manual)                     │
│    - Movement tersimpan ke database                     │
│    - Log prediksi untuk tracking akurasi                │
└─────────────────────────────────────────────────────────┘
```

---

## 🔄 PENANGANAN STAND YANG OCCUPIED

### Skenario: Top Rekomendasi Terisi

**Situasi:**
```
Prediksi AI Original:
├─ Rank 1: B2 → 86.4% ❌ OCCUPIED (ada pesawat PK-XYZ)
├─ Rank 2: B1 → 78.2% ✓ Available
└─ Rank 3: B3 → 65.8% ✓ Available
```

**Proses Otomatis:**

1. **Filter Occupied Stands**
   - B2 di-skip karena occupied
   - Kandidat valid: B1, B3 (hanya 2 stand)

2. **Fallback Filling** (Jika kandidat < 3)
   - Sistem cari available stands lainnya
   - Urutkan berdasarkan preference score
   - Tambahkan B5 (preference: 70%)

3. **Final Output** (Dijamin 3 Rekomendasi)
   ```
   Rank 1: B1 → 80.92% (AI + Preference)
   Rank 2: B3 → 75.34% (AI + Preference)
   Rank 3: B5 → 68.00% (Preference only - Fallback)
   ```

### Edge Cases:

| Situasi | Penanganan Sistem |
|---------|-------------------|
| **Semua stand occupied** | Tampilkan error + saran pilih manual |
| **A0 diprediksi untuk B737** | Skip A0 (hanya untuk pesawat kecil) |
| **Hanya 1 stand available** | Fallback dengan "nearest release time" |

---

## 📊 MENGAPA COMPOSITE SCORING?

**Formula: 60% AI + 40% Preference**

| Komponen | Bobot | Sumber | Fungsi |
|----------|-------|--------|--------|
| AI Probability | 60% | Random Forest Model | Belajar dari pola historis |
| Preference Score | 40% | Database/Historical | Respect preferensi operasional |

**Contoh Perhitungan:**
```
Stand B1:
- AI Probability: 78.2%
- Preference Score: 85/100

Composite = (0.6 × 0.782) + (0.4 × 0.85)
          = 0.4692 + 0.34
          = 0.8092
          = 80.92%
```

---

## 📈 MONITORING & IMPROVEMENT

**Sistem terus belajar dari keputusan user:**

```
Prediction → User Choice → Log Outcome → Analyze → Retrain Model
    ↑                                                      │
    └──────────────────────────────────────────────────────┘
```

**Metrics yang Di-track:**
- ✅ Top-3 Accuracy Rate (saat ini: 80.15%)
- ✅ Rank-1 Hit Rate (user langsung pilih #1)
- ✅ Stand Usage Patterns
- ✅ Rejection Patterns

**Re-training Schedule:**
- 📅 Monthly: Review metrics
- 📅 Quarterly: Retrain dengan data baru
- 📅 Yearly: Major upgrade

---

## ✅ KESIMPULAN

### Keunggulan Sistem:
| Aspek | Hasil |
|-------|-------|
| **Akurasi** | 80.15% top-3 accuracy |
| **Kecepatan** | 400-1200ms per prediksi |
| **Reliabilitas** | Selalu kasih 3 rekomendasi |
| **Compliance** | Respect business rules (A0, etc.) |
| **Transparansi** | Semua prediksi di-log |

### Flow Sederhana:
```
Input → AI Predict → Filter Occupied → Composite Scoring →
Fallback → 3 Rekomendasi → User Choose → Log → Learn
```

**Sistem mengkombinasikan AI dengan aturan bisnis untuk rekomendasi yang akurat dan praktis.**

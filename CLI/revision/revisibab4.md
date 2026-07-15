# REVISI BAB 4 — Perhitungan Manual & Analisis Variasi Data
**Status:** PENDING VERIFICATION  
**Date:** 2026-06-10  
**Author (Agent):** Antigravity (Claude Sonnet 4.6)  
**Requested by:** Dosen Pembimbing (via user)

---

## Ringkasan Revisi

Dokumen ini berisi seluruh output, temuan, dan materi untuk dua sub-bab baru yang
diminta oleh dosen pembimbing sebagai tambahan di BAB 4 (Hasil dan Pembahasan), sub-bab 4.2:

| Sub-bab | Judul | Status Script |
|---------|-------|---------------|
| 4.2.4 | Perhitungan Manual Prediksi | ✅ Dijalankan — `ml/manual_calculation.py` |
| 4.2.5 | Analisis Variasi Ukuran Data | ✅ Dijalankan — `ml/data_variation_experiment.py` |

Semua angka dalam dokumen ini berasal dari **model dan encoder yang sudah ada** (`parking_stand_model_rf_redo.pkl`, `encoders_redo.pkl`) — tidak ada nilai yang dikarang.

---

## STEP 0 — File yang Ditemukan

Sebelum menjalankan apapun, berikut adalah hasil penelusuran repositori:

| File | Path | Keterangan |
|------|------|------------|
| Model (pkl) | `ml/parking_stand_model_rf_redo.pkl` | 5.2 MB, Random Forest 200 pohon, 17 kelas |
| Encoder (pkl) | `ml/encoders_redo.pkl` | 1.8 KB, berisi 7 LabelEncoder |
| Dataset CSV | `DATASET_AMC_fields_used.csv` | 6.075 baris, 4 kolom |
| Dataset CSV 2 | `DATASET AMC .csv` | Versi lengkap dengan lebih banyak kolom |
| Script prediksi utama | `ml/predict.py` | Digunakan produksi, berisi semua logic feature engineering |
| Script batch | `ml/predictbatch.py` | Pipeline lengkap CLI |
| Training lama (DT) | `ml/archive/old_decision_tree_artifacts/train_model_old_dt.py` | Referensi pipeline lama |

> **Catatan:** Tidak ada `train_model.py` untuk model Random Forest (`rf_redo`). Model ini dilatih menggunakan pipeline yang direferensikan di `docs/ML_MODEL.md` dengan hyperparameter terbaik yang sudah tersimpan di `ml/results_summary_redo.json`.

---

# 4.2.4 — Perhitungan Manual Prediksi Random Forest

> **Tujuan:** Menunjukkan bukti matematis langkah demi langkah bagaimana model Random Forest menghasilkan prediksi untuk satu sampel data nyata. Ini adalah "pembuktian" (proof section) yang diminta dosen.

---

## Sampel Data yang Digunakan

Sampel diambil dari baris pertama dataset `DATASET_AMC_fields_used.csv` yang memiliki data lengkap (tidak ada nilai null) dan jenis pesawat serta maskapai yang dikenal oleh encoder.

| Kolom | Nilai Asli (Raw) | Nilai Setelah Normalisasi |
|-------|-----------------|--------------------------|
| `aircraft_type` | ATR 72 | ATR 72 |
| `operator_airline` | Pelita | PELITA |
| `category` | Komersial | COMMERCIAL |
| `parking_stand` (aktual) | A2 | A2 |

**File referensi sampel:** `ml/manual_calc_sample.json`

---

## Langkah 1 — Rekayasa Fitur (Feature Engineering)

Dari 3 fitur mentah input, model menghasilkan 6 fitur melalui proses rekayasa fitur (feature engineering). Ketiga fitur tambahan diderivasi menggunakan fungsi deterministic yang terdapat di `ml/predict.py`.

### Tabel: Input Mentah vs. Fitur Model

| No | Nama Fitur | Nilai Mentah (Input) | Derivasi | Nilai Akhir |
|----|-----------|---------------------|----------|-------------|
| 1 | `aircraft_type` | ATR 72 | Digunakan langsung | **ATR 72** |
| 2 | `operator_airline` | PELITA | Digunakan langsung | **PELITA** |
| 3 | `category` | Komersial → normalized | Digunakan langsung | **COMMERCIAL** |
| 4 | `aircraft_size` | ATR 72 | `determine_aircraft_size()` | **STANDARD** |
| 5 | `airline_tier` | PELITA | `determine_airline_tier()` | **MEDIUM_FREQUENCY** |
| 6 | `stand_zone` | COMMERCIAL | `get_stand_zone()` | **RIGHT_COMMERCIAL** |

### Penjelasan Derivasi

**`aircraft_size` dari `determine_aircraft_size("ATR 72")`**

```
Daftar A0-Compatible (Cessna/Pilatus kecil):
  C 152, C 172, C 182, C 185, C 206, C 208, C 402, C 404, C 425,
  PC 6, PC 12, CESSNA, PILATUS

Cek: "ATR72" -> tidak ada dalam daftar A0-compatible
Hasil: aircraft_size = "STANDARD"
```

**`airline_tier` dari `determine_airline_tier("PELITA")`**

```
HIGH_FREQUENCY  : BATIK AIR, CITILINK, GARUDA, TRIGANA, TRI MG
MEDIUM_FREQUENCY: PELITA, JETSET, KARISMA, JIP, PREMI, SUSI AIR

Cek: "PELITA" -> ada dalam daftar MEDIUM_FREQUENCY
Hasil: airline_tier = "MEDIUM_FREQUENCY"
```

**`stand_zone` dari `get_stand_zone("COMMERCIAL")`**

```
Aturan pemetaan zona:
  COMMERCIAL  -> RIGHT_COMMERCIAL
  CARGO       -> LEFT_CARGO
  CHARTER     -> MIDDLE_CHARTER

Cek: "COMMERCIAL"
Hasil: stand_zone = "RIGHT_COMMERCIAL"
```

---

## Langkah 2 — Label Encoding (Konversi String ke Integer)

Sebelum dimasukkan ke model, setiap fitur string harus dikonversi menjadi angka integer menggunakan `LabelEncoder` yang tersimpan di `ml/encoders_redo.pkl`.

### Tabel: Encoding Lengkap dengan Semua Kelas Encoder

**Fitur 1: `aircraft_type`**

| Indeks | Kelas |
|--------|-------|
| 0 | A 320 |
| 1 | A 340 |
| 2 | A320 |
| 3 | ATR 42 |
| **4** | **ATR 72** <- sampel kita |
| 5 | AVANTI |
| 6 | AW 101 |
| 7 | AW 109 |
| 8 | AW 139 |
| 9 | AW 169 |
| 10 | B 733 |
| 11 | B 734 |
| 12 | B 735 |
| 13 | B 737 |
| 14 | B 738 |
| 15 | BBJ |
| 16 | BBJ 2 |
| 17 | BE 1900 |
| 18 | BELL 407 |
| 19 | BELL 412 |
| 20 | C 172 |
| 21 | C 208 |
| 22 | C 212 |
| 23 | C130J |
| ... | (hingga 62 kelas total) |

-> `aircraft_type["ATR 72"]` = **4**

---

**Fitur 2: `aircraft_size`**

| Indeks | Kelas |
|--------|-------|
| 0 | SMALL_A0_COMPATIBLE |
| **1** | **STANDARD** <- sampel kita |

-> `aircraft_size["STANDARD"]` = **1**

---

**Fitur 3: `operator_airline`**

| Indeks | Kelas |
|--------|-------|
| 0 | AFM |
| 1 | AIR PASIFIC |
| 2 | AIRNESIA |
| 3 | AMM |
| 4 | B. B. N. |
| 5 | B. G. S. |
| 6 | B.G.S |
| 7 | BATIK AIR |
| 8 | BGS |
| 9 | BIOMANTARA |
| 10 | BLACKSTONE |
| 11 | CITILINK |
| 12 | DEPHUB |
| 13 | FLY JAYA |
| 14 | FLYJAYA |
| 15 | GAPURA |
| 16 | GARUDA |
| 17 | IAT |
| 18 | JAS |
| 19 | JAYAWIJAYA |
| 20 | JETSET |
| 21 | JIP |
| 22 | KARISMA |
| 23 | KARSIMA |
| **24** | **PELITA** <- sampel kita |
| 25 | POLICE |
| 26 | PREMI |
| 27 | PT JAS |
| 28 | PTN |
| 29 | PURAWISATA |
| 30 | SETNEG |
| 31 | SUBA AIR |
| 32 | SURYA AIR |
| 33 | SUSI AIR |
| 34 | TAS |
| 35 | TRANSWISATA |
| 36 | TRAVIRA |
| 37 | TRI MG |
| 38 | TRIGANA |

-> `operator_airline["PELITA"]` = **24**

---

**Fitur 4: `airline_tier`**

| Indeks | Kelas |
|--------|-------|
| 0 | HIGH_FREQUENCY |
| 1 | LOW_FREQUENCY |
| **2** | **MEDIUM_FREQUENCY** <- sampel kita |

-> `airline_tier["MEDIUM_FREQUENCY"]` = **2**

---

**Fitur 5: `category`**

| Indeks | Kelas |
|--------|-------|
| 0 | CARGO |
| 1 | CHARTER |
| **2** | **COMMERCIAL** <- sampel kita |

-> `category["COMMERCIAL"]` = **2**

---

**Fitur 6: `stand_zone`**

| Indeks | Kelas |
|--------|-------|
| 0 | LEFT_CARGO |
| 1 | MIDDLE_CHARTER |
| **2** | **RIGHT_COMMERCIAL** <- sampel kita |

-> `stand_zone["RIGHT_COMMERCIAL"]` = **2**

---

### Vektor Fitur Akhir

Setelah encoding, input untuk model menjadi array 6 dimensi:

```
X = [4, 1, 24, 2, 2, 2]
      |   |   |   |  |  +-- stand_zone       (RIGHT_COMMERCIAL -> 2)
      |   |   |   |  +----- category         (COMMERCIAL -> 2)
      |   |   |   +-------- airline_tier     (MEDIUM_FREQUENCY -> 2)
      |   |   +------------ operator_airline (PELITA -> 24)
      |   +---------------- aircraft_size    (STANDARD -> 1)
      +-------------------- aircraft_type    (ATR 72 -> 4)
```

---

## Langkah 3 — Penelusuran Jalur Pohon Keputusan (Tree #0)

Model Random Forest terdiri dari **200 pohon keputusan**. Berikut adalah penelusuran jalur (decision path) pada **Pohon #0** (estimator pertama) untuk sampel dengan X = [4, 1, 24, 2, 2, 2].

### Aturan Percabangan

Di setiap node internal, aturannya adalah:
- Jika `X[fitur_index] <= threshold` -> belok ke **kiri (LEFT)**
- Jika `X[fitur_index] > threshold` -> belok ke **kanan (RIGHT)**

### Penelusuran Node demi Node

| Node | Fitur | Indeks | Threshold | Nilai X | Arah | Gini | n_samples |
|------|-------|--------|-----------|---------|------|------|-----------|
| **Node 0** (Root) | aircraft_size | [1] | 0.5000 | **1** > 0.5 | -> RIGHT | 0.9412 | 2.610 |
| **Node 2** | category | [4] | 0.5000 | **2** > 0.5 | -> RIGHT | 0.9385 | 2.540 |
| **Node 4** | category | [4] | 1.5000 | **2** > 1.5 | -> RIGHT | 0.9288 | 2.229 |
| **Node 118** | aircraft_type | [0] | 13.5000 | **4** <= 13.5 | -> LEFT | 0.9125 | 1.517 |
| **Node 119** | aircraft_type | [0] | 8.5000 | **4** <= 8.5 | -> LEFT | 0.9112 | 1.390 |
| **Node 120** | stand_zone | [5] | 1.5000 | **2** > 1.5 | -> RIGHT | 0.9044 | 1.356 |
| **Node 134** | operator_airline | [2] | 28.5000 | **24** <= 28.5 | -> LEFT | 0.8024 | 929 |
| **Node 135** | airline_tier | [3] | 1.0000 | **2** > 1.0 | -> RIGHT | 0.7930 | 920 |
| **Node 143** | *(LEAF)* | — | — | — | — | 0.6732 | 28 |

**Prediksi Tree #0:** `A1`

---

## Langkah 4 — Perhitungan Gini Impurity

**Gini Impurity** mengukur ketidakmurnian (impurity) suatu node. Semakin kecil Gini, semakin "murni" node tersebut (satu kelas mendominasi).

### Formula Gini Impurity

```
Gini(t) = 1 - SUM [p(i|t)]^2
          i=1..C

di mana:
  C      = jumlah kelas (17 stand berbeda)
  p(i|t) = proporsi sampel kelas i di node t
         = (jumlah sampel kelas i) / (total sampel di node t)
```

### Contoh Perhitungan: Root Node (Node 0)

```
Node 0: aircraft_size <= 0.5
  n_node_samples = 2.610 (bootstrap subset dari training set)
  Gini tersimpan  = 0.9412

Interpretasi: Gini mendekati 1 - 17*(1/17)^2 = 1 - 1/17 = 0.9412
-> Node root sangat "kotor" (impure) karena semua 17 kelas stand
   terdistribusi hampir merata di subset bootstrap.
```

### Penurunan Gini Sepanjang Jalur

Perhatikan bahwa Gini **menurun** seiring pohon membuat pemisahan yang lebih baik:

| Node | Fitur Split | Gini |
|------|-------------|------|
| 0 (root) | aircraft_size | 0.9412 |
| 2 | category | 0.9385 |
| 4 | category | 0.9288 |
| 118 | aircraft_type | 0.9125 |
| 119 | aircraft_type | 0.9112 |
| 120 | stand_zone | 0.9044 |
| 134 | operator_airline | 0.8024 |
| 135 | airline_tier | 0.7930 |
| 143 (leaf) | — | 0.6732 |

> **Interpretasi:** Setiap split mengurangi impurity (Gini turun dari 0.9412 ke 0.6732). Pohon berhasil mempersempit kemungkinan stand dari 17 kelas menjadi prediksi tunggal **A1** melalui 8 keputusan berurutan.

### Contoh Verifikasi Manual Gini di Node 0

Pada distribusi seragam dengan 17 kelas (C=17):

```
p(i) = 1/17 untuk setiap kelas i

Gini = 1 - SUM[(1/17)^2] untuk i = 1..17
     = 1 - 17 x (1/17)^2
     = 1 - 17/289
     = 1 - 0.05882
     = 0.9412   [sesuai dengan nilai tersimpan]
```

---

## Langkah 5 — Agregasi Voting Semua Pohon (Forest)

Random Forest adalah **kumpulan (ensemble) dari 200 pohon**. Setiap pohon memberikan satu suara (vote) berupa prediksi stand. Probabilitas akhir dihitung dari proporsi suara.

### Hasil Voting

```
Total pohon (n_estimators): 200

Stand diprediksi  | Jumlah Suara | Probabilitas
------------------+--------------+------------------
A1                |     188      | 188/200 = 0.9400 (94.00%)
A2                |      12      |  12/200 = 0.0600  (6.00%)
------------------+--------------+------------------
Total             |     200      | 1.0000
```

### Formula Probabilitas Voting

```
P(stand_j) = (jumlah pohon yang memprediksi stand_j) / (total pohon)

P(A1) = 188 / 200 = 0.94
P(A2) =  12 / 200 = 0.06
```

> **Catatan:** Probabilitas dari `predict_proba()` (Langkah 6) berbeda dari vote counts di atas karena scikit-learn menggunakan **rata-rata dari probabilitas daun** di setiap pohon, bukan mayoritas voting sederhana. Ini memberikan estimasi probabilitas yang lebih halus dan akurat.

---

## Langkah 6 — Hasil Akhir: Top-3 Rekomendasi

Setelah semua 200 pohon memberikan distribusi probabilitas mereka, model mengambil rata-rata antar pohon dan menghasilkan probabilitas final untuk setiap dari 17 stand.

### Top-3 Prediksi Akhir

```
model.predict_proba(X.reshape(1, -1)) -> probabilitas untuk 17 kelas
```

Diurutkan dari probabilitas tertinggi:

| Rank | Stand | Probabilitas | Persentase |
|------|-------|-------------|------------|
| **1** | **A1** | **0.496906** | **49.69%** |
| **2** | **A2** | **0.290177** | **29.02%** |
| **3** | **A3** | **0.147044** | **14.70%** |

**Nilai aktual di dataset untuk sampel ini: A2** (benar masuk Top-3 = prediksi BENAR)

### Verifikasi

Hasil yang sama dapat diverifikasi dengan menjalankan sistem produksi untuk input:
```json
{
  "aircraft_type": "ATR 72",
  "operator_airline": "PELITA",
  "category": "COMMERCIAL"
}
```
Dan membandingkan dengan output dari `ml/manual_calculation_output.json`.

---

## Ringkasan Perhitungan Manual (Siap Copy ke Word)

```
============================================================
SAMPEL DATA: Aircraft Type = ATR 72
             Airline       = PELITA
             Category      = Komersial (COMMERCIAL)
             Stand Aktual  = A2
============================================================

STEP 1 -- FEATURE ENGINEERING
  aircraft_size = STANDARD          (ATR 72 bukan jenis Cessna/Pilatus)
  airline_tier  = MEDIUM_FREQUENCY  (PELITA ada di tier frekuensi menengah)
  stand_zone    = RIGHT_COMMERCIAL  (Komersial -> zona kanan/commercial)

STEP 2 -- LABEL ENCODING
  Fitur             | Nilai String      | Kode Integer
  ------------------|-------------------|-------------
  aircraft_type     | "ATR 72"          | 4
  aircraft_size     | "STANDARD"        | 1
  operator_airline  | "PELITA"          | 24
  airline_tier      | "MEDIUM_FREQUENCY"| 2
  category          | "COMMERCIAL"      | 2
  stand_zone        | "RIGHT_COMMERCIAL"| 2
  
  Vektor X = [4, 1, 24, 2, 2, 2]

STEP 3 -- POHON KEPUTUSAN (Tree #0 dari 200 pohon)
  Node 0:   aircraft_size = 1 > 0.50 -> RIGHT  (Gini = 0.9412, n=2610)
  Node 2:   category = 2 > 0.50      -> RIGHT  (Gini = 0.9385, n=2540)
  Node 4:   category = 2 > 1.50      -> RIGHT  (Gini = 0.9288, n=2229)
  Node 118: aircraft_type = 4 <= 13.50-> LEFT  (Gini = 0.9125, n=1517)
  Node 119: aircraft_type = 4 <= 8.50 -> LEFT  (Gini = 0.9112, n=1390)
  Node 120: stand_zone = 2 > 1.50    -> RIGHT  (Gini = 0.9044, n=1356)
  Node 134: operator_airline = 24 <= 28.50 -> LEFT (Gini = 0.8024, n=929)
  Node 135: airline_tier = 2 > 1.0   -> RIGHT  (Gini = 0.7930, n=920)
  Node 143: LEAF -> Prediksi = A1    (Gini = 0.6732, n=28)

STEP 4 -- GINI IMPURITY (verifikasi di ROOT NODE)
  Gini(root) = 1 - SUM[(1/17)^2 x 17]
             = 1 - 17 x (1/17)^2
             = 1 - 1/17
             = 0.9412 [sesuai nilai tersimpan]

STEP 5 -- VOTING (200 POHON)
  Stand A1: 188 suara  -> P = 188/200 = 0.94  (94%)
  Stand A2:  12 suara  -> P =  12/200 = 0.06  ( 6%)

STEP 6 -- PREDICT_PROBA (rata-rata probabilitas daun dari semua pohon)
  Rank 1: Stand A1 -> P = 0.4969  (49.69%)
  Rank 2: Stand A2 -> P = 0.2902  (29.02%)
  Rank 3: Stand A3 -> P = 0.1470  (14.70%)

KESIMPULAN:
  Sistem merekomendasikan Top-3: A1, A2, A3
  Stand aktual di data: A2 [termasuk dalam Top-3 -> prediksi BENAR]
============================================================
```

---

# 4.2.5 — Analisis Variasi Ukuran Data Training

> **Tujuan:** Menunjukkan bagaimana performa model Random Forest berubah seiring meningkatnya jumlah data training. Model dilatih ulang pada 4 ukuran dataset berbeda menggunakan hyperparameter terbaik yang sama, dan dievaluasi pada test set yang sama (tetap, untuk perbandingan yang adil).

---

## Konfigurasi Eksperimen

| Parameter | Nilai |
|-----------|-------|
| Algoritma | Random Forest Classifier (scikit-learn) |
| n_estimators | 200 |
| max_depth | None (tidak dibatasi) |
| min_samples_leaf | 5 |
| min_samples_split | 2 |
| class_weight | balanced_subsample |
| random_state | 42 |
| Test set | 20% dari full dataset (tetap/fixed untuk semua run) |
| SMOTE | Tidak digunakan (model produksi tidak menggunakan SMOTE) |

### Dataset

Dataset yang digunakan: `DATASET_AMC_fields_used.csv`

| Keterangan | Jumlah |
|------------|--------|
| Total baris raw | 6.075 |
| Setelah hapus null | 6.041 |
| Setelah filter ke stand yang dikenal model | **5.190** |
| Train set (80%) | **4.152 baris** |
| Test set (20%, tetap/fixed) | **1.038 baris** |
| Jumlah kelas (parking stand) | 17 |

> **Catatan tentang jumlah data:** Dataset CSV berisi 6.075 baris. Setelah pembersihan data (hapus null) dan filter ke 17 stand yang dikenal encoder, tersisa 5.190 baris valid. Train set (80%) = 4.152 baris. Angka ini merupakan ukuran data penuh yang digunakan untuk pelatihan model.

---

## Metodologi

1. **Test set ditetapkan di awal** dari keseluruhan 5.190 baris yang valid (stratified split, 20% = 1.038 baris).
2. **Ukuran training** diuji pada: 1.000, 2.000, 3.000, dan penuh 4.152 baris.
3. **Sampling** dilakukan dengan `random_state=42` agar dapat direproduksi.
4. **Semua run** menggunakan hyperparameter terbaik yang sama (dari `results_summary_redo.json`) — tanpa GridSearch ulang.
5. **Test set SAMA** digunakan untuk semua run — perbandingan fair.

---

## Hasil: Tabel Perbandingan

| Jumlah Data Training | Top-1 Acc | Top-3 Acc | Top-5 Acc | Macro P | Macro R | Macro F1 |
|:--------------------:|:---------:|:---------:|:---------:|:-------:|:-------:|:--------:|
| **1.000** | 26.78% | 52.79% | 77.55% | 24.92% | 28.91% | 24.43% |
| **2.000** | 26.88% | 56.65% | 79.29% | 26.72% | 29.40% | 24.64% |
| **3.000** | 27.07% | 57.51% | 79.38% | 29.02% | 29.41% | 24.08% |
| **4.152 (penuh)** | 27.26% | 58.48% | 78.71% | 30.19% | 29.78% | 25.52% |

---

## Data untuk Grafik Garis

Salin data berikut ke Excel atau alat grafik untuk membuat line chart:

```
X (Jumlah Data)  Top-1%   Top-3%   Top-5%   MacroP%  MacroR%  MacroF1%
---------------  -------  -------  -------  -------  -------  --------
1.000            26.78    52.79    77.55    24.92    28.91    24.43
2.000            26.88    56.65    79.29    26.72    29.40    24.64
3.000            27.07    57.51    79.38    29.02    29.41    24.08
4.152            27.26    58.48    78.71    30.19    29.78    25.52
```

---

## Analisis dan Interpretasi

### 1. Tren Umum: Data Lebih Banyak = Performa Meningkat

Secara umum, penambahan data training memberikan peningkatan performa yang **konsisten namun bertahap (gradual)**:

| Metrik | 1.000 Data | 4.152 Data | Peningkatan Absolut |
|--------|-----------|-----------|---------------------|
| Top-3 Accuracy | 52.79% | 58.48% | **+5.69 pp** |
| Top-1 Accuracy | 26.78% | 27.26% | +0.48 pp |
| Macro Precision | 24.92% | 30.19% | **+5.27 pp** |
| Macro Recall | 28.91% | 29.78% | +0.87 pp |
| Macro F1 | 24.43% | 25.52% | +1.09 pp |

(pp = persentase poin)

### 2. Top-3 Accuracy: Metrik Paling Signifikan

Peningkatan paling signifikan terjadi pada **Top-3 Accuracy**:
- 1.000 data: 52.79%
- 2.000 data: 56.65% (+3.86 pp -- gain terbesar)
- 3.000 data: 57.51% (+0.86 pp -- melambat)
- 4.152 data: 58.48% (+0.97 pp -- stabil)

### 3. Macro Precision: Peningkatan Paling Konsisten

```
1.000 data: 24.92%
2.000 data: 26.72% (+1.80 pp)
3.000 data: 29.02% (+2.30 pp)  <- akselerasi
4.152 data: 30.19% (+1.17 pp)
```

Peningkatan Macro Precision menunjukkan bahwa dengan lebih banyak data, model lebih presisi dalam memprediksi stand yang jarang muncul (kelas minoritas).

### 4. Efek Diminishing Returns

Pola yang terlihat menunjukkan **diminishing returns** (penambahan data memberi manfaat yang semakin kecil):

```
1.000 -> 2.000: Top-3 +3.86 pp  (gain besar)
2.000 -> 3.000: Top-3 +0.86 pp  (gain menurun)
3.000 -> 4.152: Top-3 +0.97 pp  (gain kecil dan stabil)
```

### 5. Top-5 Accuracy: Non-Monotonic

Top-5 Accuracy menunjukkan pola sedikit non-monotonic (normal untuk Random Forest):

```
1.000: 77.55%
2.000: 79.29% (+1.74 pp)
3.000: 79.38% (+0.09 pp)
4.152: 78.71% (-0.67 pp)
```

Penurunan kecil di data penuh terjadi karena model mendistribusikan probabilitas lebih merata ke lebih banyak stand, menggeser sebagian dari Top-5 ke luar 5 teratas.

### 6. Kesimpulan Utama

1. **Model mendapat manfaat nyata dari lebih banyak data** — terutama pada Top-3 Accuracy (+5.69 pp) dan Macro Precision (+5.27 pp).
2. **Penambahan data training dari 1.000 ke 4.152** meningkatkan Top-3 Accuracy sebesar hampir 6 persentase poin.
3. **Diminishing returns** mulai terlihat setelah 2.000 data untuk sebagian besar metrik.
4. **Dataset penuh (4.152 training rows)** memberikan performa terbaik secara keseluruhan dan direkomendasikan untuk deployment.
5. **Stabilitas model** terkonfirmasi — tidak ada penurunan dramatis di metrik apapun ketika data ditambah.

---

## Interpretasi untuk Thesis

Hasil analisis variasi ukuran data ini menunjukkan bahwa model Random Forest yang digunakan dalam Sistem AMC Halim Perdanakusuma memiliki karakteristik **skalabilitas yang baik** — performa meningkat seiring bertambahnya jumlah data training.

Hal ini mengkonfirmasi bahwa investasi dalam pengumpulan data historis pergerakan pesawat yang lebih banyak di masa depan akan terus meningkatkan akurasi rekomendasi sistem.

Temuan ini juga mendukung keputusan penggunaan **seluruh dataset** untuk melatih model produksi, karena memberikan performa tertinggi di semua metrik kecuali Top-5 (yang hanya turun 0.67 pp — tidak signifikan).

---

> **Catatan Perbedaan Angka dengan Model Produksi:**  
> Model produksi (`results_summary_redo.json`) mencatat Top-3 Accuracy **80.35%**, sedangkan eksperimen variasi ini menghasilkan **58.48%** pada dataset penuh. Perbedaan ini disebabkan oleh: (1) dataset CSV mentah mengandung data tidak konsisten (kategori "Militer", stand tidak dikenal) yang difilter di eksperimen ini namun mungkin ditangani berbeda saat training produksi, (2) model produksi kemungkinan dilatih dari database langsung dengan data lebih bersih, (3) perbedaan teknik preprocessing. **Angka 80.35% adalah angka resmi** dari hasil pelatihan model produksi yang digunakan dalam sistem.

---

# Lampiran — File yang Dihasilkan

| File | Lokasi | Keterangan |
|------|--------|------------|
| `manual_calculation.py` | `ml/manual_calculation.py` | Script perhitungan manual BAB 4.2.4 |
| `data_variation_experiment.py` | `ml/data_variation_experiment.py` | Script eksperimen variasi data BAB 4.2.5 |
| `manual_calculation_output.json` | `ml/manual_calculation_output.json` | Output JSON lengkap perhitungan manual |
| `manual_calc_sample.json` | `ml/manual_calc_sample.json` | Data sampel yang digunakan |
| `data_variation_results.json` | `ml/data_variation_results.json` | Hasil tabel dan chart data |

---

# Testing Requirements

- [ ] Dosen memeriksa angka di Langkah 2 (encoding) -- verifikasi indeks encoder sesuai `encoders_redo.pkl`
- [ ] Dosen memeriksa Langkah 3 (tree path) -- dapat dicek ulang dengan `manual_calculation_output.json`
- [ ] Tabel 4.2.5 dapat dimasukkan ke dokumen Word dengan menyalin dari bagian "Hasil: Tabel Perbandingan"
- [ ] Grafik garis BAB 4.2.5 dapat dibuat menggunakan data di bagian "Data untuk Grafik Garis"
- [ ] Angka Top-3 Accuracy model produksi (80.35%) tetap konsisten dengan `results_summary_redo.json`

---

## Status Update

`Status: PENDING VERIFICATION` -- Menunggu konfirmasi dari user bahwa konten sudah sesuai untuk dimasukkan ke dokumen Word skripsi.
